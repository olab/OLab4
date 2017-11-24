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
	$query = "	SELECT a.*,b.`organisation_id` 
				FROM `assignments` a
				JOIN `courses` b
				ON a.`course_id` = b.`course_id` 
				WHERE a.`assignment_id` = ".$db->qstr($ASSIGNMENT_ID)."
				AND a.`assignment_active` = '1'";
	$assignment = $db->GetRow($query);
	if (isset($_GET["sid"]) && $tmp = clean_input($_GET["sid"], "int")) {
		/** @todo this needs to make sure the user is a teacher for the course if this way is used, otherwise students could add another student's proxy*/
		$query = "SELECT * FROM `assignment_contacts` WHERE `assignment_id` = ".$db->qstr($ASSIGNMENT_ID)." AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getID());
		if ($iscontact = $db->GetRow($query)) {
			$USER_ID = $tmp;
		} elseif ($assignment && $ENTRADA_ACL->amIAllowed(new GradebookResource($assignment["course_id"], $assignment["organisation_id"]), "update")) {
			$iscontact = true;
			$USER_ID = $tmp;
		} else {
			$USER_ID = false;
		}

	} else {
		$USER_ID = false;
	}	
	if ($USER_ID) {			
		if ($assignment) {
			$query			= "SELECT a.*, b.`course_id`, b.`assignment_title`
                                FROM `assignment_files` AS a
                                JOIN `assignments` AS b 
                                ON a.`assignment_id` = b.`assignment_id`
                                JOIN `".AUTH_DATABASE."`.`user_data` AS c
                                ON a.`proxy_id` = c.`id`
                                WHERE `file_active` = '1'
                                AND a.`assignment_id` = ".$db->qstr($ASSIGNMENT_ID)."
                                AND b.`assignment_active` = '1'
                                AND a.`proxy_id` = ".$db->qstr($USER_ID);
            
            if ((int)$assignment['max_file_uploads'] > 1) {
                $file_records	= $db->GetAll($query);
                $student = $db->GetRow("SELECT * FROM `".AUTH_DATABASE."`.`user_data` WHERE `id`=".$db->qstr($USER_ID));
                
                //More than one file, will have to output a .zip
                if ($file_records) {
                    $dir = FILE_STORAGE_PATH."/zips";
                    if (!file_exists($dir)) {
                        mkdir($dir, 0777);
                    }
                    $zip_prefix = str_replace(array("/", " "), "_", $student['number']."_".$student['username']."_".$assignment['assignment_title']);
                    $zip_file_name = $zip_prefix.'.zip';
                    $zipname = $dir."/".$zip_file_name;
                    
                    $zip = new ZipArchive();
                    $res = $zip->open($zipname, ZipArchive::OVERWRITE);
                    
                    if ($res !== true) {
                        $ERROR++;
                        $ERRORSTR[] = "<strong>Unable to create the file archive.</strong><br /><br />The archive of files was not created. Please try again later.";
                    } else {
                        foreach ($file_records as $file) {
                            $version_query = "SELECT *
                                             FROM `assignment_file_versions`
                                             WHERE `assignment_id`=".$db->qstr($ASSIGNMENT_ID)."
                                             AND `proxy_id`=".$db->qstr($USER_ID)."
                                             AND `file_active`=1
                                             AND `afile_id`=".$db->qstr($file['afile_id'])."
                                             ORDER BY `file_version` DESC
                                             LIMIT 1";
                            $version_result = $db->GetRow($version_query);
                            if ($version_result) {
                                $file_path = FILE_STORAGE_PATH."/A".$version_result['afversion_id'];
                                if (file_exists($file_path) && is_readable($file_path)) {
                                    $extension = pathinfo($version_result['file_filename'], PATHINFO_EXTENSION);
                                    if ($assignment["anonymous_marking"]) {
                                        $zip->addFile($file_path, $zip_prefix . "/" . $version_result['afversion_id'] . "." . $extension);
                                    } else {
                                        $zip->addFile($file_path, $zip_prefix . "/" . $version_result['file_filename']);
                                    }
                                }
                            }
                        }
                        
                        $zip->close();
                        $download_file = $zipname;
                        if (file_exists($download_file) && is_readable($download_file)) {
                            ob_clear_open_buffers();
                            
                            header("Pragma: public");
                            header("Expires: 0");
                            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                            header("Content-Type: application/zip");
                            header("Content-Disposition: attachment; filename=\"".$zip_file_name."\"");
                            header("Content-Length: ".filesize($download_file));
                            header("Content-Transfer-Encoding: binary\n");
                            echo file_get_contents($download_file, FILE_BINARY);
                            add_statistic("assignment:".$ASSIGNMENT_ID, "file_zip_download", "assignment_id", $ASSIGNMENT_ID);
                            exit;
                        }
                    }
                } else {
                    header("Location: ".ENTRADA_URL."/profile/gradebook/assignments?section=submit&id=".$COURSE_ID . "&assignment_id=" . $ASSIGNMENT_ID);
                    //echo 'Invalid id specified. Redirect to submit page.';
                    exit;
                }
            } else {
                $file_record = $db->GetRow($query);
                
                if ($file_record) {
                    $FILE_ID = $file_record["afile_id"];

                        /**
                         * Download the latest version.
                         */
                        $query	= "SELECT *
                                    FROM `assignment_file_versions`
                                    WHERE `assignment_id` = ".$db->qstr($ASSIGNMENT_ID)."
                                    AND `proxy_id` = ".$db->qstr($USER_ID)."
                                    AND `file_active` = '1'
                                    ORDER BY `file_version` DESC
                                    LIMIT 0, 1";
                        $result	= $db->GetRow($query);
                        if ($result) {
                            $file_version = array();
                            $file_version["afversion_id"] = $result["afversion_id"];
                            $file_version["file_mimetype"] = $result["file_mimetype"];
                            $file_version["file_filename"] = $result["file_filename"];
                            $file_version["file_filesize"] = (int) $result["file_filesize"];
                        }
                        if (($file_version) && (is_array($file_version))) {
                            if ((file_exists($download_file = FILE_STORAGE_PATH."/A".$file_version["afversion_id"])) && (is_readable($download_file))) {

                                ob_clear_open_buffers();

                                /**
                                 * Determine method that the file should be accessed (downloaded or viewed)
                                 * and send the proper headers to the client.
                                 */
                                header("Pragma: public");
                                header("Expires: 0");
                                header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                                header("Content-Type: ".$file_version["file_mimetype"]);
                                header("Content-Disposition: attachment; filename=\"".$file_version["file_filename"]."\"");
                                header("Content-Length: ".filesize($download_file));
                                header("Content-Transfer-Encoding: binary\n");
                                add_statistic("community:".$COMMUNITY_ID.":shares", "file_download", "csfile_id", $ASSIGNMENT_ID);
                                echo file_get_contents($download_file, FILE_BINARY);
                                exit;
                            }
                        }

                        //No file to download.
                        if ((!$ERROR) || (!$NOTICE)) {
                            $url = ENTRADA_URL."/admin/gradebook/assignments/?".replace_query(array("step" => false, "section" => "grade", "id" => $COURSE_ID, "assignment_id" => $ASSIGNMENT_ID));
                            $NOTICE++;
                            $NOTICESTR[] = "<strong>No assignment file to download yet.</strong><br /><br />You will now be redirected to the <strong>Drop Box</strong> page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
                            $ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000)";
                        }

                        if ($NOTICE) {
                            echo display_notice();
                        }
                        if ($ERROR) {
                            echo display_error();
                        }
                } else {
                    header("Location: ".ENTRADA_URL."/profile/gradebook/assignments?section=submit&id=".$COURSE_ID . "&assignment_id=" . $ASSIGNMENT_ID);
                    //echo 'Invalid id specified. Redirect to submit page.';
                    exit;
                }
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
