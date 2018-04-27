<?php
class Entrada_Filter_Domain extends Entrada_Filter_Base {
    
    function init() {
        global $ENTRADA_USER;
        
        $this->setKey(DOMAIN_OBJECTIVE_CODE);
        $this->setLabel("Domain");
        
        $items = array();
        
        // Get Objective root node
        $parent = Models_Objective::fetchRowByCode(DOMAIN_OBJECTIVE_CODE, 1, $ENTRADA_USER->getActiveOrganisation());
        
        if ($parent) {
            $children = Models_Objective::fetchAllByOrganisationParentID($ENTRADA_USER->getActiveOrganisation(),$parent->getID());   
        }
        
        if (isset($children) && ! empty($children)) {
            foreach ($children as $item) {
                $data = array(
                    "label" => $item->getCode()." - ".$item->getName(),
                    "value" => $item->getID(),
                    "sort_field" => $item->getName()
                );
                
                $items[] = $data;
            }
        }
        $this->setItems($items);
        $this->setSortable(true);
        
    }
}