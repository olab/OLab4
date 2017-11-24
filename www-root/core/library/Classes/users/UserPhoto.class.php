<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * NOTE this relies on STORAGE_USER_PHOTOS being defined. If this changes, this WILL break.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@queensu.ca>
 * @copyright Copyright 2011 Queen's University. All Rights Reserved.
*/

 class UserPhoto {
 	const OFFICIAL = "official";
 	const UPLOADED = "upload";
 	
 	public static $types = array(self::OFFICIAL, self::UPLOADED);
 	
 	private $photo_mimetype;
 	private $photo_id;
 	private $proxy_id;
 	private $photo_active;
 	private $photo_type;
 	private $updated_date;
 	
 	function __construct($photo_id, $proxy_id, $photo_mimetype, $photo_filesize, $photo_active, $photo_type, $updated_date) {
 		$this->photo_id = $photo_id;
 		$this->proxy_id = $proxy_id;
 		$this->photo_mimetype = $photo_mimetype;
 		$this->photo_filesize = $photo_filesize;
 		$this->photo_active = $photo_active;
 		$this->photo_type = (int)$photo_type;
 		$this->updated_date = $updated_date;
 	}
 	
 	public static function fromArray(array $arr) {
 		return new self($arr['photo_id'], $arr['proxy_id'], $arr['photo_mimetype'], $arr['photo_filesize'], $arr['photo_active'], $arr['photo_type'], $arr['updated_date']);
 	}
 	
 	public function isActive() {
 		return (bool) $this->photo_active;
 	}
 	
 	public function getID() {
 		return $this->photo_id;
 	}
 	
 	public function getUserID() {
 		return $this->proxy_id;
 	}
 	
 	public function getMimetype() {
 		return $this->photo_mimetype;
 	}
 	
 	public function getFilesize() {
 		return $this->filesize();
 	}
 	
 	public function getPhotoType() {
 		return (1 === $this->photo_type) ? self::UPLOADED : self::OFFICIAL;
 	}
 	
 	public function getFilename() {
 		return webservice_url("photo", array($this->getUserID(), $this->getPhotoType()));
 	}

     public function getThumbnail() {
         return webservice_url("photo", array($this->getUserID(), $this->getPhotoType(), "thumbnail"));
     }
 	
 	public static function get($user_id, $type = self::OFFICIAL) {
 		if (in_array($type, self::$types)) { //validate type
 			if (@file_exists(STORAGE_USER_PHOTOS."/".$user_id."-".$type)) { //validate file existence
 				//the two types get handled differently -- UPLOADED files are in the DB, OFFICIAL files are not, and some info has to be manufactured 
	 			switch ($type) {
	 				case self::OFFICIAL:
	 					$photo = array(
	 						"proxy_id" => $user_id,
	 						"photo_filesize" => @filesize(STORAGE_USER_PHOTOS."/".$user_id."-".$type),
	 						"photo_active" => 1,
	 						"photo_type" => 0
	 					);
	 					return self::fromArray($photo);
 					break;
	 				case self::UPLOADED:
	 					global $db;
	 					$query = "SELECT * FROM `".AUTH_DATABASE."`.`user_photos` WHERE `proxy_id` = ?";
						$result	= $db->GetRow($query, array($user_id));
						if ($result) { //ensure it's in the database, too
							return self::fromArray($result);
						}
 					break;
	 			}
	 		}
			
			return false;
 		}
 	}
 }
