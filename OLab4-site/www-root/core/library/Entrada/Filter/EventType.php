<?php
class Entrada_Filter_EventType extends Entrada_Filter_Base {
    
    function init() {
        global $ENTRADA_USER;
        
        $this->setKey("event_type_id");
        $this->setLabel("Group Category");
        
        $items = array();
        
        $event_lu_types = Models_EventType::fetchAllByOrganisationID($ENTRADA_USER->getActiveOrganisation(),1);
        
        if (isset($event_lu_types) && ! empty($event_lu_types)) {
            foreach ($event_lu_types as $item) {
            	$data = array(
            		"label" => $item->getEventTypeTitle(),
            		"value" => $item->getID(),
            		"sort_field" => $item->getEventTypeTitle()
            	);
            	
            	$items[] = $data;
            }
        }
        $this->setItems($items);
        $this->setSortable(true);
    }
}