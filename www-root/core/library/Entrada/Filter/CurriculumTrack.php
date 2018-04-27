<?php
class Entrada_Filter_CurriculumTrack extends Entrada_Filter_Base {
    
    function init() {
        global $ENTRADA_USER;
        
        $this->setKey("curriculum_track_id");
        $this->setLabel("Stream");
        
        $items = array();
        
        $active_stream = Models_Curriculum_Track::fetchAllByOrg($ENTRADA_USER->getActiveOrganisation());
        if (isset($active_stream) && ! empty($active_stream)) {
            foreach ($active_stream as $item) {
            	$data = array(
            		"label" => $item->getCurriculumTrackName(),
            		"value" => $item->getID()
            	);
            	
            	$items[] = $data;
            }
        }
        $this->setItems($items);
        
    }
}