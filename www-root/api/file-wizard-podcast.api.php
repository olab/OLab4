<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Loads the Podcast upload wizard when a student wants to upload a podcast file
 * to a specific learning event page.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/
@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

ob_start("on_checkout");

if((!isset($_SESSION["isAuthorized"])) || (!(bool) $_SESSION["isAuthorized"])) {
	echo "<div id=\"scripts-on-open\" style=\"display: none;\">\n";
	echo "alert('It appears as though your session has expired; you will now be taken back to the login page.');\n";
	echo "if(window.opener) {\n";
	echo "	window.opener.location = '".ENTRADA_URL.((isset($_SERVER["REQUEST_URI"])) ? "?url=".rawurlencode(clean_input($_SERVER["REQUEST_URI"], array("nows", "url"))) : "")."';\n";
	echo "	top.window.close();\n";
	echo "} else {\n";
	echo "	window.location = '".ENTRADA_URL.((isset($_SERVER["REQUEST_URI"])) ? "?url=".rawurlencode(clean_input($_SERVER["REQUEST_URI"], array("nows", "url"))) : "")."';\n";
	echo "}\n";
	echo "</div>\n";
	exit;
} elseif((!isset($_SESSION["details"]["allow_podcasting"])) || (!(bool) $_SESSION["details"]["allow_podcasting"])) {
	echo "<div id=\"scripts-on-open\" style=\"display: none;\">\n";
	echo "alert('You do not have the appropriate permission level to add podcasts to learning events.');\n";
	echo "if(window.opener) {\n";
	echo "	window.opener.location = '".ENTRADA_URL.((isset($_SERVER["REQUEST_URI"])) ? "?url=".rawurlencode(clean_input($_SERVER["REQUEST_URI"], array("nows", "url"))) : "")."';\n";
	echo "	top.window.close();\n";
	echo "} else {\n";
	echo "	window.location = '".ENTRADA_URL.((isset($_SERVER["REQUEST_URI"])) ? "?url=".rawurlencode(clean_input($_SERVER["REQUEST_URI"], array("nows", "url"))) : "")."';\n";
	echo "}\n";
	echo "</div>\n";
	exit;
} else {
	$ACTION				= "add";
	$EVENT_ID			= 0;

	if((isset($_GET["step"])) && ((int) trim($_GET["step"]))) {
		$STEP = (int) trim($_GET["step"]);
	}

	if((isset($_GET["id"])) && ((int) trim($_GET["id"]))) {
		$EVENT_ID	= (int) trim($_GET["id"]);
	}

	$modal_onload = array();
	if($EVENT_ID) {
		$query	= "
				SELECT a.*, b.`audience_value` AS `event_cohort`
				FROM `events` AS a
				LEFT JOIN `event_audience` AS b
				ON b.`event_id` = a.`event_id`
				WHERE a.`event_id` = ".$db->qstr($EVENT_ID)."
				AND b.`audience_type` = 'cohort'";
		$result	= $db->GetRow($query);
		if($result) {
			if((!isset($_SESSION["details"]["allow_podcasting"])) || (!(bool) $_SESSION["details"]["allow_podcasting"]) || (($_SESSION["details"]["allow_podcasting"] != "all") && ($_SESSION["details"]["allow_podcasting"] != $result["event_cohort"]))) {
				$modal_onload[] = "closeWizard()";

				$ERROR++;
				$ERRORSTR[]	= "Your account does not have the permissions required to use this feature. If you believe you are receiving this message in error please us for assistance.";

				echo display_error();

				application_log("error", "User does not have access to the podcast file upload wizard.");
			} else {
				/**
				 * Add file form.
				 */

				// Error Checking
				switch($STEP) {
					case 2 :
						/**
						 * In this error checking we are working backwards along the internal javascript
						 * steps timeline. This is so the JS_INITSTEP variable is set to the lowest page
						 * number that contains errors.
						 */

						$PROCESSED["event_id"] 	= $EVENT_ID;
						$PROCESSED["required"]		= 0;
						$PROCESSED["timeframe"]		= "post";
						$PROCESSED["file_category"]	= "podcast";
						$PROCESSED["release_date"]	= 0;
						$PROCESSED["release_until"]	= 0;
						$PROCESSED["accesses"]		= 0;
						$PROCESSED["updated_date"]	= time();
						$PROCESSED["updated_by"]	= $ENTRADA_USER->getID();

						/**
						 * Step 3 Error Checking
						 */
						if(isset($_FILES["filename"])) {
							switch($_FILES["filename"]["error"]) {
								case 0 :
									if(@in_array(strtolower(trim($_FILES["filename"]["type"])), $VALID_PODCASTS)) {
										$PROCESSED["file_type"]		= trim($_FILES["filename"]["type"]);
										$PROCESSED["file_size"]		= (int) trim($_FILES["filename"]["size"]);
										$PROCESSED["file_name"]		= useable_filename(trim($_FILES["filename"]["name"]));

										if((isset($_POST["file_title"])) && (trim($_POST["file_title"]))) {
											$PROCESSED["file_title"]	= trim($_POST["file_title"]);
										} else {
											$PROCESSED["file_title"]	= $PROCESSED["file_name"];
										}
									} else {
										$modal_onload[]		= "alert('The podcast file that uploaded does not appear to be a valid podcast file.\\n\\nPlease make sure you upload an MP3, MP4, M4A, MOV or PDF document.".trim($_FILES["filename"]["type"])."')";

										$ERROR++;
										$ERRORSTR[]		= "q1";
									}
								break;
								case 1 :
								case 2 :
									$modal_onload[]		= "alert('The file that was uploaded is too big for this form. Please decrease the filesize and try again.')";

									$ERROR++;
									$ERRORSTR[]		= "q1";
								break;
								case 3 :
									$modal_onload[]		= "alert('The file that was uploaded did not complete the upload process or was interupted. Please try again.')";

									$ERROR++;
									$ERRORSTR[]		= "q1";
								break;
								case 4 :
									$modal_onload[]		= "alert('You did not select a file on your computer to upload. Please select a local file.')";

									$ERROR++;
									$ERRORSTR[]		= "q1";
								break;
								case 6 :
								case 7 :
									$modal_onload[]		= "alert('Unable to store the new file on the server. We have been informed of this error, please try again later.')";

									$ERROR++;
									$ERRORSTR[]		= "q1";

									application_log("error", "File upload error: ".(($_FILES["filename"]["error"] == 6) ? "Missing a temporary folder." : "Failed to write file to disk."));
								break;
								default :
									application_log("error", "Unrecognized file upload error number [".$_FILES["filename"]["error"]."].");
								break;
							}
						} else {
							$modal_onload[]		= "alert('To upload a file to this event you must select a file to upload from your computer.')";

							$ERROR++;
							$ERRORSTR[]		= "q1";
						}

						if((isset($_POST["file_notes"])) && ($file_notes = clean_input($_POST["file_notes"], array("notags", "trim")))) {
							$PROCESSED["file_notes"] = $file_notes;
						} else {
							$ERROR++;
							$ERRORSTR[] = "q3";
						}

						if(!$ERROR) {
							$query	= "
									SELECT *
									FROM `event_files`
									WHERE `event_id` = ".$db->qstr($EVENT_ID)."
									AND `file_name` = ".$db->qstr($PROCESSED["file_name"]);
							$result	= $db->GetRow($query);
							if($result) {
								$modal_onload[]		= "alert('A file named ".addslashes($PROCESSED["file_name"])." already exists in this teaching event.\\n\\nIf this is an updated version, please delete the old file before adding this one.')";

								$ERROR++;
								$ERRORSTR[]		= "q2";
							} else {
								if(!DEMO_MODE) {
									if(($db->AutoExecute("event_files", $PROCESSED, "INSERT")) && ($EFILE_ID = $db->Insert_Id())) {
                                        $resource_entity = new Models_Event_Resource_Entity(
                                            array(
                                                "event_id" => $EVENT_ID,
                                                "entity_type" => 1,
                                                "entity_value" => $EFILE_ID,
                                                "release_date" => 0,
                                                "release_until" => 0,
                                                "updated_date" => time(),
                                                "updated_by" => $ENTRADA_USER->getID(),
                                                "active" => 1
                                            )
                                        );
                                        
                                        if (!$resource_entity->insert()) {
                                            $modal_onload[]		= "alert('An error occured while attempting to save this podcast resource. The MEdTech Unit has been informed of this error, please try again later')";
                                            
                                            $ERROR++;
                                            $ERRORSTR[]		= "q2";
                                            application_log("error", "An error occured while attempting to save the event_resource_entity record DB said: " . $db->ErrorMsg());
                                        }
                                        
										if((@is_dir(FILE_STORAGE_PATH)) && (@is_writable(FILE_STORAGE_PATH))) {
											if(@file_exists(FILE_STORAGE_PATH."/".$EFILE_ID)) {
												application_log("notice", "File ID [".$EFILE_ID."] already existed and was overwritten with newer file.");
											}
	
											if(@move_uploaded_file($_FILES["filename"]["tmp_name"], FILE_STORAGE_PATH."/".$EFILE_ID)) {
												application_log("success", "File ID [".$EFILE_ID."] was successfully added to the database and filesystem for event [".$EVENT_ID."].");
											} else {
												$modal_onload[]		= "alert('The new file was not successfully saved. The MEdTech Unit has been informed of this error, please try again later.')";
	
												$ERROR++;
												$ERRORSTR[]		= "q1";
	
												application_log("error", "The move_uploaded_file function failed to move temporary file over to final location.");
											}
										} else {
											$modal_onload[]		= "alert('The new file was not successfully saved. The MEdTech Unit has been informed of this error, please try again later.')";
	
											$ERROR++;
											$ERRORSTR[]		= "q1";
	
											application_log("error", "Either the FILE_STORAGE_PATH doesn't exist on the server or is not writable by PHP.");
										}
									} else {
										$modal_onload[]		= "alert('The new file was not successfully saved. The MEdTech Unit has been informed of this error, please try again later.')";
	
										$ERROR++;
										$ERRORSTR[]		= "q1";
	
										application_log("error", "Unable to insert the file into the database for event ID [".$EVENT_ID."]. Database said: ".$db->ErrorMsg());
									}
								} else {
									switch($PROCESSED["file_category"]) {
										case "lecture_notes":
											$PROCESSED["file_type"] = filetype(DEMO_NOTES);
											$PROCESSED["file_size"] = filesize(DEMO_NOTES);
											$PROCESSED["file_name"] = basename(DEMO_NOTES);
											$DEMO_FILE = DEMO_NOTES;
											break;
										case "lecture_slides":
											$PROCESSED["file_type"] = filetype(DEMO_SLIDES);
											$PROCESSED["file_size"] = filesize(DEMO_SLIDES);
											$PROCESSED["file_name"] = basename(DEMO_SLIDES);
											$DEMO_FILE = DEMO_SLIDES;
											break;
										case "podcast":
											$PROCESSED["file_type"] = filetype(DEMO_PODCAST);
											$PROCESSED["file_size"] = filesize(DEMO_PODCAST);
											$PROCESSED["file_name"] = basename(DEMO_PODCAST);
											$DEMO_FILE = DEMO_PODCAST;
											break;
										case "other":
										default:
											$PROCESSED["file_type"] = filetype(DEMO_FILE);
											$PROCESSED["file_size"] = filesize(DEMO_FILE);
											$PROCESSED["file_name"] = basename(DEMO_FILE);
											$DEMO_FILE = DEMO_FILE;
											break;
									}
									if(($db->AutoExecute("event_files", $PROCESSED, "INSERT")) && ($EFILE_ID = $db->Insert_Id())) {
										if((@is_dir(FILE_STORAGE_PATH)) && (@is_writable(FILE_STORAGE_PATH))) {
											if(@file_exists(FILE_STORAGE_PATH."/".$EFILE_ID)) {
												application_log("notice", "File ID [".$EFILE_ID."] already existed and was overwritten with newer file.");
											}
											
											if(@copy($DEMO_FILE, FILE_STORAGE_PATH."/".$EFILE_ID)) {
												application_log("success", "Success, however, since this is the Entrada demo site we do not allow uploading of files. Instead we've linked the information you just entered to a file that already exists on the demo server for demonstration purposes.");
											} else {
												$modal_onload[]		= "alert('The new file was not successfully saved. The MEdTech Unit has been informed of this error, please try again later.')";

												$ERROR++;
												$ERRORSTR[]		= "q5";
												$JS_INITSTEP	= 3;

												application_log("error", "The move_uploaded_file function failed to move temporary file over to final location.");
											}
										} else {
											$modal_onload[]		= "alert('The new file was not successfully saved. The MEdTech Unit has been informed of this error, please try again later.')";

											$ERROR++;
											$ERRORSTR[]		= "q5";
											$JS_INITSTEP	= 3;

											application_log("error", "Either the FILE_STORAGE_PATH doesn't exist on the server or is not writable by PHP.");
										}
									} else {
										$modal_onload[]		= "alert('The new file was not successfully saved. The MEdTech Unit has been informed of this error, please try again later.')";

										$ERROR++;
										$ERRORSTR[]		= "q5";
										$JS_INITSTEP	= 3;

										application_log("error", "Unable to insert the file into the database for event ID [".$EVENT_ID."]. Database said: ".$db->ErrorMsg());
									}
								}
							}
						}

						if($ERROR) {
							$STEP = 1;
						}
					break;
					case 1 :
					default :
						continue;
					break;
				}

				// Display Add Step
				switch($STEP) {
					case 2 :
						$modal_onload[] = "parentReload()";
						?>
						<div class="modal-dialog" id="file-add-wizard">
							<div id="wizard">
                                <h3 class="border-below">Podcast Upload Wizard</h3>
								<div id="body">
									<h2>Podcast Added Successfully</h2>
		
									<div class="display-success">
										<?php if(!DEMO_MODE) { ?>
											You have successfully added <strong><?php echo html_encode($PROCESSED["file_title"]); ?></strong> to this event.
										<?php
										} else { ?>
											You are in demo mode therefore <strong><?php echo html_encode($PROCESSED["file_title"]); ?></strong> as been replaced with our default demo file and has been linked to this event.
										<?php } ?>
									</div>
								</div>
								<div id="footer">
									<input type="button" class="btn" value="Close" onclick="closeWizard()" style="float: left; margin: 4px 0px 4px 10px" />
									<input type="button" class="btn btn-primary" value="Add Another" onclick="restartWizard('<?php echo ENTRADA_URL; ?>/api/file-wizard-podcast.api.php?id=<?php echo $EVENT_ID; ?>')" style="float: right; margin: 4px 10px 4px 0px" />
								</div>
							</div>
						</div>
						<?php
					break;
					case 1 :
					default :
						?>
						<div class="modal-dialog" id="file-add-wizard">
							<div id="wizard">
								<form target="upload-frame" id="wizard-form" action="<?php echo ENTRADA_URL; ?>/api/file-wizard-podcast.api.php?id=<?php echo $EVENT_ID; ?>&amp;step=2" method="post" enctype="multipart/form-data" style="display: inline">
								<input type="hidden" name="MAX_UPLOAD_FILESIZE" value="<?php echo MAX_UPLOAD_FILESIZE; ?>" />
                                <h3 class="border-below">Podcast Upload Wizard</h3>
								<div id="body">
									<h2 id="step-title">Adding new podcast file</h2>
									<div id="step1">
										<div id="q1" class="wizard-question<?php echo ((in_array("q1", $ERRORSTR)) ? " display-error" : ""); ?>">
											<div style="font-size: 13px">Please select the podcast file to upload from your computer:</div>
											<div style="padding-left: 65px; padding-right: 10px; padding-top: 10px">
												<input type="file" id="filename" name="filename" value="" size="25" onchange="grabFilename()" /><br /><br />
												<?php
												if((isset($PROCESSED["file_name"])) && (!in_array("q5", $ERRORSTR))) {
													echo "<div class=\"display-notice\" style=\"margin-bottom: 0px\">Since there was an error in your previous request, you will need to re-select the local file from your computer in order to upload it. We apologize for the inconvenience; however, this is a security precaution.</div>";
												} else {
													echo "<span class=\"content-small\"><strong>Note:</strong> The maximum allowable filesize of a podcast is ".readable_size(MAX_UPLOAD_FILESIZE).".</span>";
												}
												?>
											</div>
										</div>
										
										<div id="q2" class="wizard-question<?php echo ((in_array("q2", $ERRORSTR)) ? " display-error" : ""); ?>">
											<div style="font-size: 13px">You can <span style="font-style: oblique">optionally</span> provide a different title for this podcast.</div>
											<div style="padding-left: 65px; padding-right: 10px; padding-top: 10px">
												<label for="file_title" class="form-nrequired">File Title:</label> <span class="content-small"><strong>Example:</strong> Podcast Of Event 1</span><br />
												<input type="text" id="file_title" name="file_title" value="<?php echo ((isset($PROCESSED["file_title"])) ? html_encode($PROCESSED["file_title"]) : ""); ?>" maxlength="128" style="width: 350px;" />
											</div>
										</div>
										
										<div id="q3" class="wizard-question<?php echo ((in_array("q3", $ERRORSTR)) ? " display-error" : ""); ?>">
											<div style="font-size: 13px">You <span style="font-style: oblique">must</span> provide a description for this podcast.</div>
											<div style="padding-left: 65px; padding-right: 10px; padding-top: 10px">
												<label for="file_notes" class="form-required">File Description:</label><br />
												<textarea id="file_notes" name="file_notes" style="width: 350px; height: 75px"><?php echo ((isset($PROCESSED["file_notes"])) ? html_encode($PROCESSED["file_notes"]) : ""); ?></textarea>
											</div>
										</div>
									</div>
								</div>
								<div id="footer">
									<input type="button" class="btn" value="Close" onclick="closeWizard()" style="float: left; margin: 4px 0px 4px 10px" />
									<input type="button" class="btn btn-primary" value="Upload" onclick="submitPodcast()" style="float: right; margin: 4px 10px 4px 0px" />
								</div>
								<div id="uploading-window" style="width: 100%; height: 100%;">
									<div style="display: table; width: 100%; height: 100%; _position: relative; overflow: hidden">
										<div style=" _position: absolute; _top: 50%;display: table-cell; vertical-align: middle;">
											<div style="_position: relative; _top: -50%; width: 100%; text-align: center">
												<span style="color: #003366; font-size: 18px; font-weight: bold">
													<img src="<?php echo ENTRADA_URL; ?>/images/loading.gif" width="32" height="32" alt="File Uploading" title="Please wait while this file is being uploaded." style="vertical-align: middle" /> Please Wait: this file is being uploaded.
												</span>
												<br /><br />
												This can take time depending on your connection speed and the filesize.
											</div>
										</div>
									</div>
								</div>
								</form>
							</div>
						</div>
						<?php
					break;
				}
				?>
				<div id="scripts-on-open" style="display: none;">
				<?php
					foreach ($modal_onload as $string) {
						echo $string.";\n";
					}
				?>
				</div>
				<?php
			}
		} else {
			$ERROR++;
			$ERRORSTR[] = "The provided event identifier does not exist in this system.";

			echo display_error();

			application_log("error", "File wizard was accessed without a valid event id.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "You must provide an event identifier when using the file wizard.";

		echo display_error();

		application_log("error", "File wizard was accessed without any event id.");
	}
}