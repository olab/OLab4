<?php
class Entrada_Filter_ProgramCompetency extends Entrada_Filter_Base {
    
    function init() {
        global $ENTRADA_USER;
        
        $this->setKey(PROGRAM_COMPETENCY_OBJECTIVE_CODE);
        $this->setLabel("Program Competency");
        
        // Get Objective root node
        $parent = Models_Objective::fetchRowByCode(PROGRAM_COMPETENCY_OBJECTIVE_CODE, 1, $ENTRADA_USER->getActiveOrganisation());
        
        $items = array();
        if ($parent) {
            $children = Models_Objective::fetchAllByOrganisationParentID($ENTRADA_USER->getActiveOrganisation(),$parent->getID());
        }
        
        if (isset($children) && ! empty($children)) {
            foreach ($children as $item) {
            	$data = array(
            		"label" => $item->getCode()." - ".$item->getName(),
            		"value" => $item->getID()
            	);
            	
            	$items[] = $data;
            }
        }
        $this->setItems($items);
        
    }
}