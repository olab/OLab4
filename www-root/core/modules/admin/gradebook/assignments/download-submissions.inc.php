<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to view the details of / download the specified file within a folder.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Brandon Thorn <brandon.thorn@queensu.ca>
  * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 * 
*/
if ((!defined("IN_GRADEBOOK"))) {
	exit;
} 

if ($ASSIGNMENT_ID) {
	$query = "	SELECT a.*,b.`organisation_id`,b.`course_code` 
				FROM `assignments` a
				JOIN `courses` b
				ON a.`course_id` = b.`course_id` 
				WHERE a.`assignment_id` = ".$db->qstr($ASSIGNMENT_ID)."
				AND a.`assignment_active` = '1'";
	$assignment = $db->GetRow($query);	

	/** @todo this needs to make sure the user is a teacher for the course if this way is used, otherwise students could add another student's proxy*/
	$query = "SELECT * FROM `assignment_contacts` WHERE `assignment_id` = ".$db->qstr($ASSIGNMENT_ID)." AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getID());
	if ($iscontact = $db->GetRow($query)) {
		$USER_ID = $tmp;
	} elseif ($assignment && $ENTRADA_ACL->amIAllowed(new GradebookResource($assignment["course_id"], $assignment["organisation_id"]), "update")) {
		$iscontact = true;	
	} else {
		$iscontact = false;
	}

	if ($iscontact) {			
		if ($assignment) {

			/**
			 * Download the latest version.
			 */
			$query	= " SELECT a.*, CONCAT_WS('_',b.`firstname`,b.`lastname`) AS `username`, b.`number`, a.`afversion_id` FROM `assignment_file_versions` AS a
						JOIN `".AUTH_DATABASE."`.`user_data` AS b 
						ON a.`proxy_id` = b.`id` 
						WHERE `afversion_id` IN(
							SELECT MAX(`afversion_id`) FROM `assignment_file_versions` AS a
							JOIN `assignment_files` AS b
							ON a.`afile_id` = b.`afile_id`
							AND b.`file_type` = 'submission'
							WHERE a.`assignment_id` = ".$db->qstr($ASSIGNMENT_ID)." 
							GROUP BY a.`afile_id`
						)";
			$results = $db->GetAll($query);
			$dir = FILE_STORAGE_PATH."/zips";
			if (!file_exists($dir)) {
			    mkdir ($dir, 0777);
			}
            $zip_prefix = str_replace(array("/", " ") , "_", $assignment["course_code"]."_".$assignment["assignment_title"]);
			$zip_file_name = $zip_prefix.'.zip';
			$zipname = $dir."/".$zip_file_name;
			if ($results) {
                $zip = new ZipArchive();
                $res = $zip->open($zipname,ZipArchive::OVERWRITE);
                if ($res !== true) {
                    $ERROR++;
                    $ERRORSTR[] = "<strong>Unable to create the file archive.</strong><br /><br />The archive of files was not created. Please try again later.";
                } else {
                        foreach ($results as $file) {
                            $submission_file = FILE_STORAGE_PATH."/A".$file["afversion_id"];
                            if (file_exists($submission_file) && is_readable($submission_file)) {
								$extension = pathinfo($file["file_filename"], PATHINFO_EXTENSION);
                                if ((int)$assignment['max_file_uploads'] > 1) {
									if ($assignment["anonymous_marking"]) {
										$inner_filename = $zip_prefix."/".$file["number"] . "/" . $file["afversion_id"] . "." . $extension;
									} else {
										$inner_filename = $zip_prefix."/".$file["number"]. "_" . $file["username"] . "/" . $file["file_filename"];
									}

                                } else {
									if ($assignment["anonymous_marking"]) {
										$inner_filename = $zip_prefix . "/" . $file["number"] . "_"  . $file["afversion_id"] . "." . $extension;
									} else {
										$inner_filename = $zip_prefix . "/" . $file["number"] . "_" . $file["username"] . "_" . $file["file_filename"];
									}
                                }
                                $zip->addFile($submission_file, $inner_filename);	
                            }							
                        }
                        $file_version = array();
                        $file_version["file_mimetype"] = "application/zip";
                        $file_version["file_filename"] = $zipname;
                        $zip->close();
                }
			}
			if (($file_version) && (is_array($file_version))) {
				$download_file = $zipname;
				if (file_exists($download_file) && is_readable($download_file)) {
                    ob_clear_open_buffers();

					/**
					 * Determine method that the file should be accessed (downloaded or viewed)
					 * and send the proper headers to the client.
					 */
                    header("Pragma: public");
                    header("Expires: 0");
                    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                    header("Content-Type: ".$file_version["file_mimetype"]);
                    header("Content-Disposition: attachment; filename=\"".$zip_file_name."\"");
                    header("Content-Length: ".filesize($download_file));
                    header("Content-Transfer-Encoding: binary\n");
					echo file_get_contents($download_file, FILE_BINARY);
                    add_statistic("assignment:".$ASSIGNMENT_ID, "file_zip_download", "assignment_id", $ASSIGNMENT_ID);
					exit;
				}

			}
			if ((!$ERROR) && (!$NOTICE)) {
				$url = ENTRADA_URL."/admin/gradebook/?".replace_query(array("step" => false, "section" => "view", "id" => $COURSE_ID));
				$NOTICE++;
				$NOTICESTR[] = "<strong>No assignment files to download yet.</strong><br /><br />You will now be redirected to the <strong>Gradebook</strong> page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
				$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
			}

			if ($NOTICE) {
				echo display_notice();
			}
			if ($ERROR) {
				echo display_error();
			}
		} else {
				application_log("error", "The provided file id was invalid [".$ASSIGNMENT_ID."] (View File).");
				//header("Location: ".ENTRADA_URL."/profile/gradebook/assignments?section=submit&id=".$ASSIGNMENT_ID);
				echo 'Invalid id specified. No assignment found for that id.';
				exit;		
		}

	} else {
		echo 'You do not have authorization to view this resource';
	}
} else {
	application_log("error", "No assignment id was provided to view. (View File)");
	echo 'No id specified';
	
	exit;
}
