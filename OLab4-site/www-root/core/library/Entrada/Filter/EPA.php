<?php
class Entrada_Filter_EPA extends Entrada_Filter_Base {
    
    function init() {
        global $ENTRADA_USER;
        
        $this->setKey(EPA_OBJECTIVE_CODE);
        $this->setLabel("EPA");
        
        $items = array();
        
        // Get Objective root node
        $parent = Models_Objective::fetchRowByCode(EPA_OBJECTIVE_CODE, 1, $ENTRADA_USER->getActiveOrganisation());
        
        if ($parent) {
            $children = Models_Objective::fetchAllByOrganisationParentID($ENTRADA_USER->getActiveOrganisation(),$parent->getID());
        }
        
        if (isset($children) && ! empty($children)) {
            foreach ($children as $item) {
                $data = array(
                    "label" => $item->getCode(),
                    "value" => $item->getID()
                );
                
                $items[] = $data;
            }
        }
        $this->setItems($items);
        
    }
}