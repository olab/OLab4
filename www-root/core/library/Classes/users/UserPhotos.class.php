<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 *
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@queensu.ca>
 * @copyright Copyright 2011 Queen's University. All Rights Reserved.
*/

/**
 * Simple Collection for managing a user's photos (not the photos of multiple users)
 * 
 * @author Jonathan Fingland
 *
 */
class UserPhotos extends Collection {
	
	/**
	 * Returns a Collection of User photos belonging to the provided user_id
	 * @param $user_id
	 * @return UserPhotos
	 */
	public static function get($user_id) {
		$photos = array();
		$official_photo = UserPhoto::get($user_id, UserPhoto::OFFICIAL);
		$uploaded_photo = UserPhoto::get($user_id, UserPhoto::UPLOADED);
		if ($official_photo) {
			$photos[] = $official_photo;
		}
		if ($uploaded_photo) {
			$photos[] = $uploaded_photo;
		}
		return new self($photos);
	}
	
    /*
     * Returns the Uploaded photo if set, otherwise returns the Official photo,
     * obeys the user permission levels, unless the $ENTRADA USER is a Medtech
     * 
	 * @param int          $user_id
	 * @return UserPhoto   $output
     */
    public static function getPhotoWithPrivacyLevel($user_id) {
        global $ENTRADA_USER;
        
        $user = User::fetchRowByID($user_id);
        $privacy_level = $user->getPrivacyLevel();
        
        if ($privacy_level > 1 || (strtolower($ENTRADA_USER->GetActiveGroup()) === "medtech") || ($ENTRADA_USER->GetID() === $user->GetID())) {
            $official_photo = UserPhoto::get($user_id, UserPhoto::OFFICIAL);
            $uploaded_photo = UserPhoto::get($user_id, UserPhoto::UPLOADED);

            if ($uploaded_photo) {
                $output = $uploaded_photo;
            } else if ($official_photo) {
                $output = $official_photo;
            } else {
                $output = false;
            }
        } else {
            $output = false;
        }
        
        return $output;
    }
	
} 
