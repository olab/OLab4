<?php

/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Excel report handler for JSON
 * 
 * @author Organisation: Ottawa University
 * @author Unit: School of Medicine
 * @author Developer: Tomas Orta<trodrig4@uottawa.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 *
 */

class Entrada_Reporting_CSVReportHandler
{

    private $filename = '';
    private $captions = array();
    private $columns = array();
    private $output;

    public function __construct($fn)
    {
        application_log("default", "Filename: ".$fn);
        
        $this->filename = $fn;
        //header('Content-Type: application/csv');
		//header('Content-Disposition: attachment; filename="' . $this->filename . '"');
		
		header('Content-Encoding: UTF-8');
		header('Content-type: text/csv; charset=UTF-8');
		header('Content-Disposition: attachment; filename="' . $this->filename . '"');
		header("Pragma: public");
		header("Expires: 0");
		echo "\xEF\xBB\xBF"; // UTF-8 BOM
		$this->output = '';
    }

    private function CaptionHead_($data)
    {
        global $translate;
        // Peek at the first row of the array to get the title headers.
        $ids = array_keys(get_object_vars(reset($data)));
        
        foreach ($ids as $id) {
            $this->captions[$id] = $id;
            $this->columns[] = $id;
        }
        return implode(",", $this->captions) . "\n";
    }
    
    public function processCellInput($data) {
        $cell = "";
        if (!empty($data) && is_array($data)) {
            $cell = implode(",",$data);
        } else if (!empty($data)) {
            $cell = $data;
        }
        return '"' . $cell . '"';
    }

    public function _ExcelExportData($json)
    {
        if (is_array($json)) {
            $this->output = $this->output . $this->CaptionHead_($json);
            
            foreach ($json as $row) {
                $objvars = get_object_vars($row);
                $arrRow = array();
                foreach ($this->columns as $id) {
                    $arrRow[] = $this->processCellInput($objvars[$id]);
                }
                $this->output = $this->output . implode(",",$arrRow)."\n";
            }
        }
        
        echo $this->output;
    }
}