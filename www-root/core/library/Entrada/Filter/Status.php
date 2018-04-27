<?php
class Entrada_Filter_Status extends Entrada_Filter_Base {
    
    function init() {
        global $ENTRADA_USER;
        
        $this->setKey("objective_status_id");
        $this->setLabel("Status");
        
        $items = array();
        
        $children = Models_Objective_Status::fetchAllStatuses();
        
        if (isset($children) && ! empty($children)) {
            foreach ($children as $item) {
                $data = array(
                    "label" => $item->getDescription(),
                    "value" => $item->getID()
                );
                
                $items[] = $data;
            }
        }
        $this->setItems($items);
        
    }
}