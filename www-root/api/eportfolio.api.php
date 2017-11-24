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
 * API to handle interaction with eportfolio module.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
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

if((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("eportfolio", "read", false)) {
	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {

	$request = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));
	
	$request_var = "_".$request;
	
	$method = clean_input(${$request_var}["method"], array("trim", "striptags"));
	
	if (isset(${$request_var}["proxy_id"]) && $tmp_input = clean_input(${$request_var}["proxy_id"], "int")) {
		$proxy_id = $tmp_input;
	}

	switch ($request) {
		case "POST" :
			switch ($method) {
				case "media-entry" :
				case "create-entry" :
					if(isset(${$request_var}["pentry_id"]) && $tmp_input = clean_input(${$request_var}["pentry_id"], "int")) {
						$PROCESSED["pentry_id"] = $tmp_input;
					}
					
					if(isset(${$request_var}["pfartifact_id"]) && $tmp_input = clean_input(${$request_var}["pfartifact_id"], "int")) {
						$PROCESSED["pfartifact_id"] = $tmp_input;
					} else {
						add_error("Invalid portfolio entry artifact id");
					}
					
					if (${$request_var}["type"] && $tmp_input = clean_input(${$request_var}["type"], array("trim", "striptags"))) {
						$PROCESSED["type"] = $tmp_input;
					}
					
					if (isset($PROCESSED["type"]) && $PROCESSED["type"] == "file") {
						if (isset(${$request_var}["filename"]) && $tmp_input = clean_input(${$request_var}["filename"], "trim")) {
							$PROCESSED["filename"] = $tmp_input;
						} else {
							add_error("No file");
						}
					}
					
					if(${$request_var}["description"] && $tmp_input = clean_input(${$request_var}["description"], array("trim"))) {
						if (isset($PROCESSED["type"])) {
							switch ($PROCESSED["type"]) {
								case "reflection":
								case "file":
									$PROCESSED["description"] = $tmp_input;
								break;
								case "url":
									if (isset($tmp_input)) {
										$PROCESSED["description"] = clean_input($tmp_input, array("striptags"));
									}
								break;
							}
						}
						$PROCESSED["description"] = $tmp_input;
					} else {
						$PROCESSED["description"] = "";
					}
					
					if(${$request_var}["title"] && $tmp_input = clean_input(${$request_var}["title"], array("trim", "striptags"))) {
						$PROCESSED["title"] = $tmp_input;
					} else {
						add_error("<strong>Title</strong> is a Required field.");
					}
					
					if (isset($_FILES) && $_FILES["file"]["name"] && $tmp_input = clean_input($_FILES["file"]["name"], array("trim", "striptags"))) {
                        if ($PROCESSED["filename"]) {
                            $PROCESSED["filename"] = preg_replace('/[^a-zA-Z0-9-_\.]/', '', str_replace(" ", "-", trim($PROCESSED["filename"])));
                        } else if ($tmp_input) {
						$PROCESSED["filename"] = preg_replace('/[^a-zA-Z0-9-_\.]/', '', str_replace(" ", "-", trim($tmp_input)));
                        } else {
                            add_error("An error ocurred while attempting to clean the name of this file. Please try again.");
                        }
						
						$allowed_mime_types = array(
							"image/jpeg", "image/png", "application/pdf", 
							"application/x-pdf", "application/excel", 
							"application/vnd.ms-excel", "application/msword", 
							"application/mspowerpoint", "application/vnd.ms-powerpoint", 
							"text/richtext", "application/rtf", "application/x-rtf", 
							"application/zip"
						);
						
						$finfo = new finfo(FILEINFO_MIME);

						$type = $finfo->file($_FILES["file"]["tmp_name"]);
						
						$mime_type = explode("; ", $type);
						
					}
					
					if (isset($PROCESSED["pfartifact_id"]) && !$ERROR) {
						
						$PROCESSED["proxy_id"] = $ENTRADA_USER->getID(); // @todo: this needs to be fixed
						$PROCESSED["submitted_date"] = time();
						$PROCESSED["reviewed_by"] = "0";
						$PROCESSED["reviewed_date"] = "0";
						$PROCESSED["flag"] = "0";
						$PROCESSED["flagged_by"] = "0";
						$PROCESSED["flagged_date"] = "0";
						$PROCESSED["order"] = "0";
						$PROCESSED["updated_date"] = date(time());
						$PROCESSED["updated_by"] = $ENTRADA_USER->getID();
						$_edata = array();
						$_edata["description"] = $PROCESSED["description"];
						$_edata["title"] = $PROCESSED["title"];
						
						if ($PROCESSED["filename"]) {
							$_edata["filename"] = $PROCESSED["filename"];
						}
						
						$PROCESSED["_edata"] = serialize($_edata);
						
						if (isset($PROCESSED["pentry_id"])) {
							$pentry = Models_Eportfolio_Entry::fetchRow($PROCESSED["pentry_id"]);
							if ($pentry->fromArray($PROCESSED)->update()) {
								$PROCESSED["_edata"] = unserialize($PROCESSED["_edata"]);
								echo json_encode(array("status" => "success", "data" => array("pentry_id" => $pentry->getID(), "type" => $pentry->getType(), "edata" => $pentry->getEdataDecoded(), "submitted_date" => $PROCESSED["submitted_date"])));
							} else {
								echo json_encode(array("status" => "error", "data" => "fail"));
							}
						} else {
                            $allowed_types = array("url", "reflection");
                            if (!empty($PROCESSED["filename"]) || in_array($PROCESSED["type"], $allowed_types)) {
							$pentry = new Models_Eportfolio_Entry();
							if ($pentry->fromArray($PROCESSED)->insert()) {
								if ($PROCESSED["filename"]) {
									if ($pentry->saveFile($_FILES["file"]["tmp_name"])) {
										if (isset($_POST["isie"]) && $_POST["isie"] == "isie") {
											header('Location: '.ENTRADA_URL.'/profile/eportfolio#'.$pfolder->getID());
										} else {
											echo json_encode(array("status" => "success", "data" => array("pentry_id" => $pentry->getID(), "type" => $pentry->getType(), "edata" => $pentry->getEdataDecoded(), "submitted_date" => $PROCESSED["submitted_date"])));
										}
									} else {
										if (isset($_POST["isie"]) && $_POST["isie"] == "isie") {
											header('Location: '.ENTRADA_URL.'/profile/eportfolio#'.$pfolder->getID());
										} else {
											echo json_encode(array("status" => "error", "data" => "Failed to save file"));
										}
									}
								} else {
									echo json_encode(array("status" => "success", "data" => array("pentry_id" => $pentry->getID(), "type" => $pentry->getType(), "edata" => $pentry->getEdataDecoded(), "submitted_date" => $PROCESSED["submitted_date"])));
								}
							} else {
								if (isset($_POST["isie"]) && $_POST["isie"] == "isie") {
									header('Location: '.ENTRADA_URL.'/profile/eportfolio#'.$pfolder->getID());
								} else {
									echo json_encode(array("error" => "error", "data" => "Unable to create portfolio entry."));
								}
							}
                            } else {
                                add_error("An error occured while attempting to upload the file. Please try again.");
                                error_log("User [".$PROCESSED["proxy_id"]."] attempted to create portfolio entry but filename was invalid. Files: [".  serialize($_FILES)."], PROCESSED: [".serialize($PROCESSED)."]");
                            }
						}	
					} else {
						echo json_encode(array("status" => "error", "data" => $ERRORSTR));
					}
				break;
				case "create-artifact" :
					if(${$request_var}["pfolder_id"] && $tmp_input = clean_input(${$request_var}["pfolder_id"], "int")) {
						$PROCESSED["pfolder_id"] = $tmp_input;
					} else {
						add_error("Invalid portolio folder ID provided.");
					}
					
					if(isset(${$request_var}["pfartifact_id"]) && $tmp_input = clean_input(${$request_var}["pfartifact_id"], "int")) {
						$PROCESSED["pfartifact_id"] = $tmp_input;
					}
					
					if(${$request_var}["title"] && $tmp_input = clean_input(${$request_var}["title"], array("trim", "striptags"))) {
						$PROCESSED["title"] = $tmp_input;
					} else {
						add_error("You must provide a <strong>Title</strong> for this Artifact.");
					}
					
					if(${$request_var}["description"] && $tmp_input = clean_input(${$request_var}["description"], array("trim"))) {
						$PROCESSED["description"] = $tmp_input;
					} else {
						add_error("<strong>Description</strong> is a required field.");
					}
					
					if(${$request_var}["start_date"] && $tmp_input = strtotime(clean_input(${$request_var}["start_date"], array("trim", "striptags")))) {
						$PROCESSED["start_date"] = $tmp_input;
					} else {
						$PROCESSED["start_date"] = 0;
					}
					
					if(${$request_var}["finish_date"] && $tmp_input = strtotime(clean_input(${$request_var}["finish_date"], array("trim", "striptags")))) {
						$PROCESSED["finish_date"] = $tmp_input;
					} else {
						$PROCESSED["finish_date"] = 0;
					}
					
					if ($PROCESSED["finish_date"] > 0 && $PROCESSED["finish_date"] < $PROCESSED["start_date"]) {
						add_error("The finish date can not be before the start date.");
					}
					
					if(${$request_var}["allow_commenting"] && !empty(${$request_var}["allow_commenting"])) {
						$PROCESSED["allow_commenting"] = 1;
					} else {
						$PROCESSED["allow_commenting"] = 0;
					}
					
					if (!$ERROR) {
						
						$PROCESSED["artifact_id"] = 2;
						if ($ENTRADA_USER->getGroup() == "student") {
							$PROCESSED["proxy_id"] = $ENTRADA_USER->getID();
						} else {
							// Proxy ID has to be set to 0 for the artifact to be available to everyone.
							$PROCESSED["proxy_id"] = 0;
						}
						$PROCESSED["submitted_date"] = time();
						$PROCESSED["updated_date"] = date(time());
						$PROCESSED["updated_by"] = $ENTRADA_USER->getID();
						$PROCESSED["order"] = 0;
						$PROCESSED["active"] = 1;

						if (isset($PROCESSED["pfartifact_id"])) {
							$pfartifact = Models_Eportfolio_Folder_Artifact::fetchRow($PROCESSED["pfartifact_id"]);
							if ($pfartifact->fromArray($PROCESSED)->update()) { 
								echo json_encode(array("status" => "success", "data" => array("pfartifact_id" => $pfartifact->getID(), "pfolder_id" => $pfartifact->getPfolderID(), "title" => $pfartifact->getTitle(), "description" => $pfartifact->getDescription())));
							} else {
								echo json_encode(array("error" => "error", "data" => "Unable to create folder artifact."));
							}
						} else {
							$pfartifact = new Models_Eportfolio_Folder_Artifact();
							if ($pfartifact->fromArray($PROCESSED)->insert()) {
								echo json_encode(array("status" => "success", "data" => array("pfartifact_id" => $pfartifact->getID(), "pfolder_id" => $pfartifact->getPfolderID(), "title" => $pfartifact->getTitle(), "description" => $pfartifact->getDescription())));
							} else {
								echo json_encode(array("error" => "error", "data" => array("Unable to create portfolio entry.")));
							}
						}
						
					} else {
						echo json_encode(array("status" => "error", "data" => $ERRORSTR));
					}
				break;
				case "create-folder" :
					if(${$request_var}["portfolio_id"] && $tmp_input = clean_input(${$request_var}["portfolio_id"], "int")) {
						$PROCESSED["portfolio_id"] = $tmp_input;
					} else {
						add_error("Invalid portfolio ID.");
					}
					
					if(${$request_var}["description"] && $tmp_input = clean_input(${$request_var}["description"], array("trim", "striptags"))) {
						$PROCESSED["description"] = $tmp_input;
					}
					
					if(${$request_var}["title"] && $tmp_input = clean_input(${$request_var}["title"], array("trim", "striptags"))) {
						$PROCESSED["title"] = $tmp_input;
					} else {
						add_error("Invalid title.");
					}
					
					if(${$request_var}["allow_learner_artifacts"] && $tmp_input = clean_input(${$request_var}["allow_learner_artifacts"], array("int"))) {
						$PROCESSED["allow_learner_artifacts"] = $tmp_input;
					} else {
						$PROCESSED["allow_learner_artifacts"] = 0;
					}
					
					if (isset($PROCESSED["portfolio_id"])) {
						$PROCESSED["order"] = 1;
						$PROCESSED["updated_by"] = $ENTRADA_USER->getID();
						$PROCESSED["updated_date"] = time();
						
						$pfolder = new Models_Eportfolio_Folder();
						
						if ($pfolder->fromArray($PROCESSED)->insert()) {
							echo json_encode(array("status" => "success", "data" => $pfolder->toArray()));
						} else {
							echo json_encode(array("error" => "error", "data" => "Unable to create portfolio entry."));
						}
						
					} else {
						echo json_encode(array("status" => "error", "data" => $ERRORSTR));
					}
				break;
				case "edit-folder" :
					if(${$request_var}["pfolder_id"] && $tmp_input = clean_input(${$request_var}["pfolder_id"], "int")) {
						$PROCESSED["pfolder_id"] = $tmp_input;
					} else {
						add_error("Invalid folder ID.");
					}
					
					if(${$request_var}["description"] && $tmp_input = clean_input(${$request_var}["description"], array("trim", "striptags"))) {
						$PROCESSED["description"] = $tmp_input;
					}
					
					if(${$request_var}["title"] && $tmp_input = clean_input(${$request_var}["title"], array("trim", "striptags"))) {
						$PROCESSED["title"] = $tmp_input;
					} else {
						add_error("Invalid title.");
					}
					
					if(${$request_var}["allow_learner_artifacts"] && $tmp_input = clean_input(${$request_var}["allow_learner_artifacts"], array("int"))) {
						$PROCESSED["allow_learner_artifacts"] = $tmp_input;
					} else {
						$PROCESSED["allow_learner_artifacts"] = 0;
					}
					
					if (isset($PROCESSED["pfolder_id"])) {
						$PROCESSED["order"] = 1;
						$PROCESSED["updated_by"] = $ENTRADA_USER->getID();
						$PROCESSED["updated_date"] = time();
						
						$pfolder = Models_Eportfolio_Folder::fetchRow($PROCESSED["pfolder_id"]);
						
						if ($pfolder->fromArray($PROCESSED)->update()) {
							echo json_encode(array("status" => "success", "data" => $pfolder->toArray()));
						} else {
							echo json_encode(array("error" => "error", "data" => "Unable to create portfolio entry."));
						}
						
					} else {
						echo json_encode(array("status" => "error", "data" => $ERRORSTR));
					}
				break;
				case "add-pentry-comment" :
					if(${$request_var}["entry-comment"] && $tmp_input = clean_input(${$request_var}["entry-comment"], array("trim", "allowedtags"))) {
						$PROCESSED["comment"] = $tmp_input;
					} else {
						add_error("The comment field can not be empty.");
					}
					
					if(${$request_var}["pentry_id"] && $tmp_input = clean_input(${$request_var}["pentry_id"], array("int"))) {
						$PROCESSED["pentry_id"] = $tmp_input;
					} else {
						add_error("An error occured attempting to attach comment to entry.");
					}
					
					if (!$ERROR) {
						
						$PROCESSED["proxy_id"] = $ENTRADA_USER->getID();
						$PROCESSED["submitted_date"] = time();
						$PROCESSED["flag"] = 0;
						$PROCESSED["active"] = 1;
						$PROCESSED["updated_date"] = date(time());
						$PROCESSED["updated_by"] = $ENTRADA_USER->getID();
						
						$comment = new Models_Eportfolio_Entry_Comment($PROCESSED);
						
						if ($comment->insert()) {
							$comment_data = $comment->toArray();
							$commentor = User::fetchRowByID($comment->getProxyID());
							$comment_data["commentor"] = $commentor->getFullname(false);
							$comment_data["submitted_date"] = date("Y-m-d H:i", $comment_data["submitted_date"]);
							echo json_encode(array("status" => "success", "data" => $comment_data));
						} else {
							echo json_encode(array("status" => "error", "data" => "An error occurred when attempting to store the comment."));
						}
					}
				break;
				case "delete-pentry-comment" :
					if(${$request_var}["pecomment_id"] && $tmp_input = clean_input(${$request_var}["pecomment_id"], array("int"))) {
						$PROCESSED["pecomment_id"] = $tmp_input;
					} else {
						add_error("An error occured attempting to attach comment to entry.");
					}
					
					if (!$ERROR) {
						$comment = Models_Eportfolio_Entry_Comment::fetchRow($PROCESSED["pecomment_id"]);
						$arr = array("active" => "0");
						if ($comment->fromArray($arr)->update()) {
							echo json_encode(array("status" => "success", "data" => $comment->toArray()));
						}
					}
				break;
				case "pentry-flag" :
					if(${$request_var}["action"] && $tmp_input = clean_input(${$request_var}["action"], array("trim", "striptags"))) {
						$PROCESSED["action"] = $tmp_input;
					} else {
						add_error("Invalid pentry_id.");
					}
					
					if(${$request_var}["pentry_id"] && $tmp_input = clean_input(${$request_var}["pentry_id"], array("int"))) {
						$PROCESSED["pentry_id"] = $tmp_input;
					} else {
						add_error("Invalid pentry_id.");
					}
					
					if (!$ERROR) {
						
						$entry = Models_Eportfolio_Entry::fetchRow($PROCESSED["pentry_id"]);
						
						if ($PROCESSED["action"] == "flag") {
							$flag["flag"] = 1;
						} else {
							$flag["flag"] = "0";
						}
						$flag["flagged_by"] = $ENTRADA_USER->getID();
						$flag["flagged_date"] = time();
						
						if ($entry->fromArray($flag)->update()) {
							echo json_encode(array("status" => "success", "data" => $entry->toArray()));
						}
						
					}
				break;
				case "pentry-review" :
					if(${$request_var}["action"] && $tmp_input = clean_input(${$request_var}["action"], array("trim", "striptags"))) {
						$PROCESSED["action"] = $tmp_input;
					} else {
						add_error("Invalid action specified.");
					}
					
					if(${$request_var}["pentry_id"] && $tmp_input = clean_input(${$request_var}["pentry_id"], array("int"))) {
						$PROCESSED["pentry_id"] = $tmp_input;
					} else {
						add_error("Invalid pentry_id.");
					}
					
					if (!$ERROR) {
						
						$entry = Models_Eportfolio_Entry::fetchRow($PROCESSED["pentry_id"]);
						
						if ($PROCESSED["action"] == "review") {
							$review["reviewed_date"] = time();
						} else {
							$review["reviewed_date"] = 0;
						}
						$review["reviewed_by"] = $ENTRADA_USER->getID();
						
						if ($entry->fromArray($review)->update()) {
							echo json_encode(array("status" => "success", "data" => $entry->toArray()));
						}
						
					}
				break;
				case "delete-entry" :
					if (isset(${$request_var}["pentry_id"]) && $tmp_input = clean_input(${$request_var}["pentry_id"], array("int"))) {
						$PROCESSED["pentry_id"] = $tmp_input;
					} else {
						add_error("Invalid pentry_id.");
					}
					
					if (!$ERROR) {
						$entry = Models_Eportfolio_Entry::fetchRow($PROCESSED["pentry_id"]);
						$PROCESSED["active"] = "0";
						if ($entry->fromArray($PROCESSED)->update()) {
							echo json_encode(array("status" => "success", "data" => $entry->toArray()));
						} else {
							echo json_encode(array("status" => "error", "data" => "Unable to remove artifact entry."));
						}
					}
				break;
				case "delete-artifact" :
					if(isset(${$request_var}["pfartifact_id"]) && $tmp_input = clean_input(${$request_var}["pfartifact_id"], "int")) {
						$PROCESSED["pfartifact_id"] = $tmp_input;
					} else {
						add_error("Invalid portfolio entry artifact id: " . ${$request_var}["pfartifact_id"] . " " . $method);
					}
					
					if (!$ERROR) {
						$pfartifact = Models_Eportfolio_Folder_Artifact::fetchRow($PROCESSED["pfartifact_id"]);
						if ($ENTRADA_USER->getGroup() != "student" || $ENTRADA_USER->getGroup() == "student" && $pfartifact->getProxyID() == $ENTRADA_USER->getID()) {
							if ($pfartifact->fromArray(array("active" => 0))->update()) {
								echo json_encode(array("status" => "success", "data" => $pfartifact->toArray()));
							} else {
								echo json_encode(array("status" => "error", "data" => "Unable to remove artifact."));
							}
						} else {
							echo json_encode(array("status" => "error", "data" => "You are not authorized to remove this artifact."));
						}
					}
				break;
				case "delete-folder" :
					if(isset(${$request_var}["pfolder_id"]) && $tmp_input = clean_input(${$request_var}["pfolder_id"], "int")) {
						$PROCESSED["pfolder_id"] = $tmp_input;
					} else {
						add_error("Invalid entry folder id: " . $_GET["pfartifact_id"] . " " . $method);
					}
					
					if (!$ERROR) {
						$pfolder = Models_Eportfolio_Folder::fetchRow($PROCESSED["pfolder_id"]);
						
						if ($pfolder->fromArray(array("active" => 0))->update()) {
							echo json_encode(array("status" => "success", "data" => $pfolder->toArray()));
						} else {
							echo json_encode(array("status" => "error", "data" => "Failed to delete folder"));
						}
					} else {
						echo json_encode(array("status" => "error", "data" => $ERRORS));
					}
				break;
				case "delete-advisor-student" :
					if(isset(${$request_var}["student_id"]) && $tmp_input = clean_input(${$request_var}["student_id"], "int")) {
						$PROCESSED["student_id"] = $tmp_input;
					} else {
						add_error("Invalid student id");
					}
					if(isset(${$request_var}["advisor_id"]) && $tmp_input = clean_input(${$request_var}["advisor_id"], "int")) {
						$PROCESSED["advisor_id"] = $tmp_input;
					} else {
						add_error("Invalid advisor id");
					}
					if (!$ERROR) {
						if (Models_Eportfolio_Advisor::deleteRelation($PROCESSED["advisor_id"], $PROCESSED["student_id"])) {
							echo json_encode(array("status" => "success", "data" => array("student_id" => $PROCESSED["student_id"])));
						} else {
							echo json_encode(array("status" => "error", "data" => array("student_id" => $PROCESSED["student_id"])));
						}
					}
				break;
				case "add-advisor-students" :
					if(isset(${$request_var}["student_ids"]) && $tmp_input = clean_input(${$request_var}["student_ids"], array("trim", "striptags"))) {
						$student_ids = explode(",", $tmp_input);
						foreach ($student_ids as $id) {
							$s[] = (int) $id;
						}
						if (empty($s)) {
							add_error("Invalid student ID");
						}
					} else {
						add_error("Invalid student id");
					}
					
                    if(isset(${$request_var}["advisor_id"]) && $tmp_input = clean_input(${$request_var}["advisor_id"], "int")) {
						$PROCESSED["advisor_id"] = $tmp_input;
					} else {
						add_error("Invalid advisor id");
					}
                    
					if (!$ERROR) {
						$i = 0;
						foreach ($s as $student_id) {
							if (Models_Eportfolio_Advisor::addRelation($PROCESSED["advisor_id"], $student_id)) {
                                $tmp_student = User::fetchRowByID($student_id);
                                $students[$i]["advisor"]	= $PROCESSED["advisor_id"];
                                $students[$i]["id"]			= $student_id;
                                $students[$i]["firstname"]	= $tmp_student->getFirstname();
                                $students[$i]["lastname"]	= $tmp_student->getLastname();
                                $i++;
                            }
						}
                        if ($students) {
                            echo json_encode(array("status" => "success", "data" => $students));
                        } else {
                            echo json_encode(array("status" => "error", "data" => array("Failed to add students")));
                        }
					}
				break;
				case "add-advisors" :
					if(isset(${$request_var}["advisor_ids"]) && $tmp_input = clean_input(${$request_var}["advisor_ids"], array("trim", "striptags"))) {
						$advisor_ids = explode(",", $tmp_input);
						foreach ($advisor_ids as $id) {
							$a[] = (int) $id;
						}
						if (empty($a)) {
							add_error("Invalid advisor ID");
						}
					} else {
						add_error("Invalid advisor id");
					}
					
					if (!$ERROR) {
						$i = 0;
						foreach ($a as $advisor_id) {
							$advisor = new Models_Eportfolio_Advisor();
							if (!$advisor->fromArray(array("proxy_id" => $advisor_id))->insert()) {
								add_error("Failed to insert advisor");
							} else {
								$advisor_data = Models_Eportfolio_Advisor::fetchRow($advisor_id); 
								$advisors[$i] = $advisor_data->toArray();
								$i++;
							}
						}
						if (!$ERROR) {
							echo json_encode(array("status" => "success", "data" => $advisors));
						} else {
							echo json_encode(array("status" => "error", "data" => $ERRORSTR));
						}
					} else {
						echo json_encode(array("status" => "error", "data" => $ERRORSTR));
					}
				break;
				case "delete-portfolio" :
					if(isset(${$request_var}["portfolio_id"]) && $tmp_input = clean_input(${$request_var}["portfolio_id"], "int")) {
						$PROCESSED["portfolio_id"] = $tmp_input;
					} else {
						add_error("Invalid portfolio id");
					}
					if (!$ERROR) {
						$portfolio = Models_Eportfolio::fetchRow($PROCESSED["portfolio_id"]);
						if ($portfolio->fromArray(array("active" => 0))->update()) {
							echo json_encode(array("status" => "success", "data" => "Successfully deleted portfolio"));
						} else {
							echo json_encode(array("status" => "error", "data" => "Failed to delete portfolio"));
						}
					} else {
						echo json_encode(array("status" => "error", "data" => $ERRORSTR));
					}
				break;
				case "create-portfolio" :
					if(isset(${$request_var}["portfolio_id"]) && $tmp_input = clean_input(${$request_var}["portfolio_id"], "int")) {
						$PROCESSED["portfolio_id"] = $tmp_input;
						$method = "update";
					} else {
						$method = "insert";
					}
					
					if(isset(${$request_var}["group_id"]) && $tmp_input = clean_input(${$request_var}["group_id"], "int")) {
						$PROCESSED["group_id"] = $tmp_input;
					} else {
						if (!isset($PROCESSED["portfolio_id"])) {
							add_error("Invalid group id");
						}
					}
					if(isset(${$request_var}["portfolio_name"]) && $tmp_input = clean_input(${$request_var}["portfolio_name"], array("trim", "striptags"))) {
						$PROCESSED["portfolio_name"] = $tmp_input;
					} else {
						if (!isset($PROCESSED["portfolio_id"])) {
							add_error("Invalid group name");
						}
					}
					if(isset(${$request_var}["start_date"]) && $tmp_input = clean_input(${$request_var}["start_date"], array("trim", "striptags"))) {
						$PROCESSED["start_date"] = strtotime($tmp_input);
					} else {
						add_error("Invalid start date");
					}
					if(isset(${$request_var}["finish_date"]) && $tmp_input = clean_input(${$request_var}["finish_date"], array("trim", "striptags"))) {
						$PROCESSED["finish_date"] = strtotime($tmp_input);
					} else {
						add_error("Invalid finish date");
					}
					if(isset(${$request_var}["export"]) && $tmp_input = clean_input(${$request_var}["export"], array("int"))) {
						$PROCESSED["allow_student_export"] = "1";
					} else {
						$PROCESSED["allow_student_export"] = "0";
					}
					if (!$ERROR) {
						$PROCESSED["organisation_id"] = $ENTRADA_USER->getActiveOrganisation();
						$PROCESSED["updated_by"] = $ENTRADA_USER->getID();
						$portfolio = new Models_Eportfolio();
						$portfolio->fromArray($PROCESSED);
						if ($portfolio->$method()) {
							echo json_encode(array("status" => "success", "data" => $portfolio->toArray()));
						} else {
							echo json_encode(array("status" => "error", "data" => "Unable to create new portfolio"));
						}
					} else {
						echo json_encode(array("status" => "error", "data" => $ERRORSTR));
					}
				break;
				case "copy-portfolio" :
					if(isset(${$request_var}["portfolio_id"]) && $tmp_input = clean_input(${$request_var}["portfolio_id"], "int")) {
						$portfolio_id = $tmp_input;
					} else {
						add_error("Invalid portfolio id provided.");
					}
					if(isset(${$request_var}["group_id"]) && $tmp_input = clean_input(${$request_var}["group_id"], "int")) {
						$PROCESSED["group_id"] = $tmp_input;
					} else {
						add_error("Invalid group id");
					}
					if(isset(${$request_var}["portfolio_name"]) && $tmp_input = clean_input(${$request_var}["portfolio_name"], array("trim", "striptags"))) {
						$PROCESSED["portfolio_name"] = $tmp_input;
					} else {
						add_error("Invalid group name");
					}
					if(isset(${$request_var}["start_date"]) && $tmp_input = clean_input(${$request_var}["start_date"], array("trim", "striptags"))) {
						$PROCESSED["start_date"] = strtotime($tmp_input);
					} else {
						add_error("Invalid start date");
					}
					if(isset(${$request_var}["finish_date"]) && $tmp_input = clean_input(${$request_var}["finish_date"], array("trim", "striptags"))) {
						$PROCESSED["finish_date"] = strtotime($tmp_input);
					} else {
						add_error("Invalid finish date");
					}
					if(isset(${$request_var}["export"]) && $tmp_input = clean_input(${$request_var}["export"], array("int"))) {
						$PROCESSED["allow_student_export"] = "1";
					} else {
						$PROCESSED["allow_student_export"] = "0";
					}
					if (!$ERROR) {
						$PROCESSED["organisation_id"] = $ENTRADA_USER->getActiveOrganisation();
						$PROCESSED["updated_by"] = $ENTRADA_USER->getID();
						$portfolio = new Models_Eportfolio();
						$portfolio->fromArray($PROCESSED);
						if ($portfolio->insert()) {
							if ($portfolio->copy($portfolio_id)) {
								echo json_encode(array("status" => "success", "data" => $portfolio->toArray()));
							} else {
								echo json_encode(array("status" => "error", "data" => array("Created new portfolio but failed to copy previous portfolio.")));
							}
						} else {
							echo json_encode(array("status" => "error", "data" => "Unable to create new portfolio"));
						}
					} else {
						echo json_encode(array("status" => "error", "data" => $ERRORSTR));
					}
				break;
				default :
					application_log("error", "eportfolio.api.php POST called but no valid method provided [".$method."]");
				break;
			}
		break;
		case "GET" :
			switch ($method) {
				case "get-portfolio" :
					if (${$request_var}["portfolio_id"] && $tmp_input = clean_input(${$request_var}["portfolio_id"], "int")) {
						$PROCESSED["portfolio_id"] = $tmp_input;
					}

					if ($PROCESSED["portfolio_id"]) {
						$portfolio = Models_Eportfolio::fetchRow($PROCESSED["portfolio_id"]);
						if ($portfolio) {
							$p_data = $portfolio->toArray();
							echo json_encode(array("status" => "success", "data" => $p_data));
						} else {
							echo json_encode(array("status" => "error", "data" => "No portfolio found with this portfolio ID."));
						}
					} else {
						echo json_encode(array("status" => "error", "data" => "Invalid portfolio ID."));
					}
				break;
				case "get-portfolio-members" :
					if (${$request_var}["portfolio_id"] && $tmp_input = clean_input(${$request_var}["portfolio_id"], "int")) {
						$PROCESSED["portfolio_id"] = $tmp_input;
					}
					
					$flagged = false;
					if (${$request_var}["flagged"] && ${$request_var}["flagged"] == true) {
						$flagged = true;
					}
					
					if (${$request_var}["proxy_id"] && $tmp_input = clean_input(${$request_var}["proxy_id"], "int")) {
						$PROCESSED["proxy_id"] = $tmp_input;
					}

					if ($PROCESSED["portfolio_id"]) {
						$portfolio = Models_Eportfolio::fetchRow($PROCESSED["portfolio_id"]);
						if ($portfolio) {
							$group = $portfolio->getGroup($flagged, isset($PROCESSED["proxy_id"]) ? $PROCESSED["proxy_id"] : false);
							echo json_encode(array("status" => "success", "data" => $group));
						} else {
							echo json_encode(array("status" => "error", "data" => "No portfolio found with this portfolio ID."));
						}
					} else {
						echo json_encode(array("status" => "error", "data" => "Invalid portfolio ID."));
					}
				break;
				case "get-artifacts" :
					$artifacts = Models_Eportfolio_Artifact::fetchAll();
					if ($artifacts) {
						foreach ($artifacts as $artifact) {
							$a_data[] = $artifact->toArray();
						}
						echo json_encode(array("status" => "success", "data" => $a_data));
					} else {
						echo json_encode(array("error" => "success", "data" => "Could not find any artifacts."));
					}
				break;
				case "get-folders" :
					if (${$request_var}["portfolio_id"] && $tmp_input = clean_input(${$request_var}["portfolio_id"], "int")) {
						$PROCESSED["portfolio_id"] = $tmp_input;
					}

					$flagged = false;
					if (${$request_var}["flagged"] && ${$request_var}["flagged"] == true) {
						$flagged = true;
					}
					
					if (${$request_var}["proxy_id"] && $tmp_input = clean_input(${$request_var}["proxy_id"], "int")) {
						$PROCESSED["proxy_id"] = $tmp_input;
					}
					
					if ($PROCESSED["portfolio_id"]) {
						$folders = Models_Eportfolio_Folder::fetchAll($PROCESSED["portfolio_id"], $flagged, $PROCESSED["proxy_id"]);
						if ($folders) {
							$f_data = array();
							foreach ($folders as $folder) {
								$f_data[$folder->getID()] = $folder->toArray();
							}
							echo json_encode(array("status" => "success", "data" => $f_data));
						} else {
							echo json_encode(array("status" => "error", "data" => "No folders attached to this portfolio ID."));
						}
					} else {
						echo json_encode(array("status" => "error", "data" => "Invalid portfolio ID."));
					}
				break;
				case "get-folder" :
					if (${$request_var}["pfolder_id"] && $tmp_input = clean_input(${$request_var}["pfolder_id"], "int")) {
						$PROCESSED["pfolder_id"] = $tmp_input;
					}
					
					if ($PROCESSED["pfolder_id"]) {
						$folder = Models_Eportfolio_Folder::fetchRow($PROCESSED["pfolder_id"]);
						if ($folder) {
							$f_data = $folder->toArray();
							echo json_encode(array("status" => "success", "data" => $f_data));
						} else {
							echo json_encode(array("status" => "error", "data" => "No folder found with this ID."));
						}
					} else {
						echo json_encode(array("status" => "error", "data" => "No portfolio folder ID or invalid portfolio folder ID."));
					}
				break;
				case "get-folder-artifacts" :
					if (${$request_var}["pfolder_id"] && $tmp_input = clean_input(${$request_var}["pfolder_id"], "int")) {
						$PROCESSED["pfolder_id"] = $tmp_input;
					}
					
					if ($PROCESSED["pfolder_id"]) {
						$folder_artifacts = Models_Eportfolio_Folder_Artifact::fetchAll($PROCESSED["pfolder_id"], (isset($proxy_id) ? $proxy_id : NULL));
						if ($folder_artifacts) {
							$fa_data = array();
							foreach ($folder_artifacts as $folder_artifact) {
								$fa_data[] = $folder_artifact->toArray();
							}
							echo json_encode(array("status" => "success", "data" => $fa_data));
						} else {
							echo json_encode(array("status" => "error", "data" => "No artifacts attached to this portfolio folder ID. "));
						}
					} else {
						echo json_encode(array("status" => "error", "data" => "No portfolio folder ID or invalid portfolio folder ID."));
					}
				break;
				case "get-folder-artifact" :
					if (${$request_var}["pfartifact_id"] && $tmp_input = clean_input(${$request_var}["pfartifact_id"], "int")) {
						$PROCESSED["pfartifact_id"] = $tmp_input;
					}
					
					if ($PROCESSED["pfartifact_id"]) {
						$folder_artifact = Models_Eportfolio_Folder_Artifact::fetchRow($PROCESSED["pfartifact_id"]);
						if ($folder_artifact) {
							$fa_data = $folder_artifact->toArray();
							echo json_encode(array("status" => "success", "data" => $fa_data));
						} else {
							echo json_encode(array("status" => "error", "data" => "No artifacts attached to this portfolio folder ID."));
						}
					} else {
						echo json_encode(array("status" => "error", "data" => "No portfolio folder artifact ID or invalid portfolio folder artifact ID."));
					}
				break;
				case "get-artifact-entries" :
					if (isset(${$request_var}["pfartifact_id"]) && $tmp_input = clean_input(${$request_var}["pfartifact_id"], "int")) {
						$PROCESSED["pfartifact_id"] = $tmp_input;
					}
					
					if ($PROCESSED["pfartifact_id"]) {
						$artifact_entries = Models_Eportfolio_Entry::fetchAll($PROCESSED["pfartifact_id"], (isset($proxy_id) ? $proxy_id : NULL));
						if ($artifact_entries) {
							$ae_data = array();
							$i = 0;
							foreach ($artifact_entries as $artifact_entry) {
								$ae_data[$i]["entry"] = $artifact_entry->toArray();
								$comments = $artifact_entry->getComments();
								if ($comments) {
									$j = 0;
									foreach ($comments as $comment) {
										$commentor = User::fetchRowByID($comment->getProxyID());
										$comments_array[$j] = $comment->toArray();
										$comments_array[$j]["submitted_date"] = date("Y-m-d H:i", $comments_array[$j]["submitted_date"]);
										$comments_array[$j]["commentor"] = $commentor->getFullname(false);
										$j++;
									}
									$ae_data[$i]["comments"] = $comments_array;
								}
								$i++;
							}
							echo json_encode(array("status" => "success", "data" => $ae_data));
						} else {
							echo json_encode(array("status" => "error", "data" => "No entries attached to this artifact."));
						}
					} else {
						echo json_encode(array("status" => "error", "data" => "No artifact ID or invalid artifact ID."));
					}
				break;
				case "get-entry" :
					if (isset(${$request_var}["pentry_id"]) && $tmp_input = clean_input(${$request_var}["pentry_id"], "int")) {
						$PROCESSED["pentry_id"] = $tmp_input;
					}
					
					if ($PROCESSED["pentry_id"]) {
						$entry = Models_Eportfolio_Entry::fetchRow($PROCESSED["pentry_id"]);
						
						if ($entry) {
							$e_data = $entry->toArray();
							echo json_encode(array("status" => "success", "data" => $e_data));
						} else {
							echo json_encode(array("status" => "error", "data" => "No entry found with this ID."));
						}
					} else {
						echo json_encode(array("status" => "error", "data" => "No entry ID or invalid entry ID."));
					}
				break;
				case "get-advisor-students" :
					if (isset(${$request_var}["padvisor_proxy_id"]) && $tmp_input = clean_input(${$request_var}["padvisor_proxy_id"], "int")) {
						$PROCESSED["padvisor_proxy_id"] = $tmp_input;
					}
					
					if ($PROCESSED["padvisor_proxy_id"]) {
						$advisor = Models_Eportfolio_Advisor::fetchRow($PROCESSED["padvisor_proxy_id"], $ENTRADA_USER->getActiveOrganisation());
						if ($advisor) {
							$related_users = $advisor->getRelated();
							if ($related_users) {
								$users = array();
								$i = 0;
								foreach ($related_users as $user) {
									$u = User::fetchRowByID($user["to"]);
									$users[$i]["fullname"] = $u->getFullname(false);
									$users[$i]["proxy_id"] = $user["to"];
									$i++;
								}
								echo json_encode(array("status" => "success", "data" => $users));
							} else {
								echo json_encode(array("status" => "error", "data" => array("There are no students associated with this advisor")));
							}
						} else {
							echo json_encode(array("status" => "error", "data" => array("A problem occurred when fetching this advisor.")));
						}
					} else {
						echo json_encode(array("status" => "error", "data" => array("An invalid advisor ID was provided.")));
					}
				break;
				case "get-is-artifact-owner" :
					if(isset(${$request_var}["pfartifact_id"]) && $tmp_input = clean_input(${$request_var}["pfartifact_id"], "int")) {
						$PROCESSED["pfartifact_id"] = $tmp_input;
					} else {
						add_error("Invalid portfolio entry artifact id");
					}
					//echo json_encode($ENTRADA_ACL->amIAllowed(EportfolioArtifactOwnerResource($PROCESSED["pfartifact_id"]), "delete"));
				break;
				case "get-cohorts" :
					$query = "SELECT a.`group_id`, a.`group_name`, a.`start_date`, a.`expire_date`
								FROM `groups` AS a
								JOIN `group_organisations` AS b
								ON a.`group_id` = b.`group_id`
								LEFT JOIN `portfolios` AS c
								ON a.`group_id` = c.`group_id` AND c.`active` = 1
								WHERE a.`group_active` = '1'
								AND b.`organisation_id` = ?
								AND IF (a.`start_date` IS NOT NULL AND a.`start_date` <> 0, UNIX_TIMESTAMP(NOW()) BETWEEN a.`start_date` AND a.`expire_date`, '1' = '1')
								AND c.`portfolio_id` IS NULL
								ORDER BY a.`group_id`";
					$results = $db->GetAll($query, $ENTRADA_USER->getActiveOrganisation());
					if (isset($results) && $results) {
						echo json_encode(array("status" => "success", "data" => $results));
					} else {
						echo json_encode(array("status" => "success", "data" => array("No active groups are attached to this organisation.")));
					}
				break;
				default :
					application_log("error", "eportfolio.api.php GET called but no valid method provided [".$method."]");
				break;
			}
		break;
	}
	
}