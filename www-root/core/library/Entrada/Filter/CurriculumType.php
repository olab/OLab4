<?php
class Entrada_Filter_CurriculumType extends Entrada_Filter_Base {
    
    function init() {
        global $ENTRADA_USER;
        
        $this->setKey("cperiod_id");
        $this->setLabel("Year of Study");
        
        $items = array();
        
        $active_term_of_study = Models_Curriculum_Type::fetchAllByOrg($ENTRADA_USER->getActiveOrganisation());
        if (isset($active_term_of_study) && ! empty($active_term_of_study)) {
            foreach ($active_term_of_study as $term_study) {
            	$data = array(
            		"label" => $term_study->getCurriculumTypeName(),
            		"value" => $term_study->getID(),
            	    "items" => $this->getSubItems($term_study->getID())
            	);
            	
            	$items[] = $data;
            }
        }
        $this->setItems($items);
        
    }
    
    function getSubItems($curriculum_type_id) {
        $data = array();
        
        $curriculum_period_object = new Models_Curriculum_Period();
        $periods = $curriculum_period_object->getAllByFinishDateCurriculumType($curriculum_type_id);
        if (isset($periods) && !empty($periods)) {
            foreach ($periods as $period) {
                $period_data = array(
                    "value"  => $period["cperiod_id"],
                    "label"  => $period["curriculum_period_title"]
                );
                $data[] = $period_data;
            }
        }
        
        return $data;
    }
}