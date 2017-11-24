<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 *
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
*/

/**
 * template system for handling xml based complex strings/structures
 * @author Jonathan Fingland
 *
 */
class Template {
	
	/**
	 * 
	 * @var DOMDocument
	 */
	private $_template_data;
	private $_restore_points = array();
	
	function __construct($filename="") {
		if ($filename) {
			$this->loadFile($filename);
		}
	}
	
	/**
	 * 
	 * @param string $filename file with xml template data
	 */
	public function loadFile($filename) {
		$filename = realpath($filename);
		if (file_exists($filename)) {
			$this->_template_data = new DOMDocument();
			$this->_template_data->preserveWhiteSpace = false;
			$this->_template_data->load($filename);
			//note the above instruction to NOT preserve whitespace is required. PHP does not handle cdata sections properly if there is *any* text around them. this could obviously mess up a <pre> section if you have one 
			$this->createRestorePoint();
		} else {
			throw new RuntimeException("File not found: " .$filename);
		} 
	}
	
	/**
	 * Function to load template data from a string instead of a file. e.g. the template is parsed first by php
	 * @param unknown_type $str
	 */
	public function loadString($str) {
		$this->_template_data = new DOMDocument();
		$this->_template_data->preserveWhiteSpace = false;
		$this->_template_data->loadXML($str);
		$this->createRestorePoint();
	}
	
	/**
	 * Adds a new restore point
	 */
	public function createRestorePoint() {
		return array_push($this->_restore_points, $this->_template_data->cloneNode(true)) - 1;
	}
	
	/**
	 * Restores the template to a previously defined restore point. numerical index. negative values OK
	 * @param int $point
	 * @return bool
	 */
	public function restore($point = null) {
		$rp_len = count($this->_restore_points);
		if ($rp_len == 0) { //no restore points
			return false;
		}
		if (is_int($point)) {
			if ($point < 0) {
				$point =  $rp_len + $point; 
			}
			if ($point >= $rp_len) {
				return false;
			}
		} else {
			$point = $rp_len-1;
		}
		$this->_template_data = $this->_restore_points[$point]->cloneNode(true); 
		return true;
	}
	
	/**
	 * Alias for restore(0);
	 * Resets template to original loaded state and destroys the other restore points
	 * @return bool
	 */
	public function reset() {
		$success = $this->restore(0);
		$this->_restore_points = array( $this->_restore_points[0] );
		return $success;
	}
	
	/**
	 * Applies the bound variables to the selected template
	 * @param array $bind_array (optional)
	 * @param array $select (optional)
	 */
	public function apply(array $bind_array = array(), array $select = array()){
		$working_template = $this->getTemplate($select);
			
		//now get every descendent and process with bound values
		$xpath = new DOMXpath($this->_template_data);
		$n_query = "./descendant-or-self::text()";
		$nodes = $xpath->query($n_query, $working_template);
		if ($nodes->length > 0) {
			for($i=0,$len = $nodes->length; $i<$len;$i++) {
				$node = $nodes->item($i);
				$text = $node->wholeText;
				$text_len = strlen($text);
				$text = substitute_vars($text, $bind_array);
				$node->replaceData(0,$text_len,$text);
			}
		}
	}
	
	/**
	 * Retrieves the selected template
	 * @param array $select (optional)
	 * @return DOMNode
	 */
	private function getTemplate(array $select = array()) {
		$selector ="";
		if (count($select > 0)) {
			reset($select);
			$selector = "[";
			do {
				$selector .= "@".key($select)."='".current($select)."'";
			} while(next($select) && ($selector .= " and "));
			$selector .="]";
		}
		
		if ($this->_template_data) {
			$xpath = new DOMXpath($this->_template_data);
			$t_query = "//template".$selector;
			
			$t_data = $xpath->query($t_query);
		
			//only take the first result, if any
			if ($t_data->length > 0) {
				return $t_data->item(0);
			}
		} else {
			throw new Exception("Template not loaded.");
		}
	}
	
	/**
	 * Returns the result and restores the template to the last restore point. optionally accepts bind values
	 * @param array $select (optional)
	 * @param array $bind_array (optional)
	 */
	public function getResult(array $bind_array = array(), array $select = array()) {
		$working_template = $this->getTemplate($select);
		if ($working_template) {
			$this->apply($bind_array, $select); //NOTE: this modifies $working_template!  
			$sxl = simplexml_import_dom($working_template);
			$this->restore(); //undo the changes to the template for the next getResult()
			return $sxl;
		}
	}

}