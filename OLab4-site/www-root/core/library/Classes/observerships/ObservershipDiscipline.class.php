<?php
class ObservershipDiscipline {
	private $discipline_id;
	private $discipline_title;
	
	function __construct($id,$title) {
		$this->discipline_id = $id;
		$this->discipline_title = $title;
	}
	
	public function getID(){
		return $this->discipline_id;
	}
	
	public function getTitle() {
		return $this->discipline_title;
	}
	
	public static function get($id) {
		global $db;
		$query = "SELECT * from observership_disciplines where id=".$db->qstr($id);
		$result = $db->getRow($query);
		if ($result) {
			$disc =  new ObservershipDiscipline($result['id'], $result['discipline_title'] );
			return $disc;
		}	
	}
}