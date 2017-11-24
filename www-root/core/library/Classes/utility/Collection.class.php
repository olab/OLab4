<?php

/**
 * Collection is an Array like structure used for abstracting many common methods required on arrays or similar structures
 * @author Jonathan Fingland
 *
 */
class Collection implements Iterator, ArrayAccess, Countable {
    private $position = 0;
    protected $container = array();  

    /**
     * Constructor with optional array parameter to initialize the collection 
     * @param array $array
     */
    public function __construct(array $array=array()) {
        $this->position = 0;
        if (is_array($array)) {
        	$this->container = $array; 
        }
    }

    public function push($value) {
    	array_push($this->container, $value);
    }
    
    public function unshift($value) {
    	array_unshift($this->container, $value);
    }
    
    public function pop() {
    	$value = array_pop($this->container);
    	if (!$this->valid()) {
    		$this->rewind();
    	}
    	return $value;
    }
    
    public function shift() {
    	$value = array_shift($this->container);
    	if (!$this->valid()) {
    		$this->rewind();
    	}
    	return $value;
    }
    
    function rewind() {
        $this->position = 0;
    }

    function current() {
        return $this->container[$this->position];
    }

    function key() {
        return $this->position;
    }

    function next() {
        ++$this->position;
    }
    
    function prev() {
    	--$this->position;
    }

    function valid() {
        return isset($this->container[$this->position]);
    }
    
	public function offsetSet($offset, $value) {
        $this->container[$offset] = $value;
    }
    public function offsetExists($offset) {
        return isset($this->container[$offset]);
    }
    public function offsetUnset($offset) {
        unset($this->container[$offset]);
    }
    public function offsetGet($offset) {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }
    
    public function count() {
    	return count($this->container);
    }
    
    public function contains($element, $strict = true) {
    	return in_array($element, $this->container, $strict);
    }
    
    /**
     * Sorts the internal collection by the specified field. 
     * @param string $direction 'asc' or 'desc'
     * @param string $sort_by field name to sort by. internal object dependent
     */
    public function sort ($direction = 'asc', $sort_by='') {
		$this->_sort($sort_by);    	
    	
		if ($direction=='desc') {
			$this->container = array_reverse($this->container);
		}
	} 
	
	private function _sort($sort_by) {
		if (count($this->container) < 2) return;
    	
		$left = new self();
		$right = new self();
		
		$pivot = array_shift($this->container);
		
		foreach ($this->container as $k => $element) {
			if ($element->compare($pivot,$sort_by) < 0 ) {
				$left->container[] = $element;
			} else {
				$right->container[] = $element;
			}	
		}
		$left->_sort($sort_by);
		$right->_sort($sort_by);
		
		$this->container = array_merge($left->container, array($pivot), $right->container);
	}
	
	/**
	 * works much the same as array_filter. supply a calback to return a boolean for each element. truthy elements are retained
	 * @param callback $callback
	 */
	public function filter($callback=null) {
		//array_merge is used as array_filter preserves keys... which we don't want.
		$this->container = array_merge(array_filter($this->container,$callback));
	}
}