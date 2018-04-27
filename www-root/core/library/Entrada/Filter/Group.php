<?php
class Entrada_Filter_Group extends Entrada_Filter_Base {
    
    function init() {
        global $ENTRADA_USER;
        
        $this->setKey("group_id");
        $this->setLabel("Cohort");
        
        $items = array();
        
        $active_cohorts = Models_Group::fetchAllByGroupType('cohort', $ENTRADA_USER->getActiveOrganisation(), '');
        if (isset($active_cohorts) && ! empty($active_cohorts)) {
            foreach ($active_cohorts as $item) {
            	$data = array(
            		"label" => $item->getGroupName(),
            		"value" => $item->getID()
            	);
            	
            	$items[] = $data;
            }
        }
        $this->setItems($items);
        
    }
}