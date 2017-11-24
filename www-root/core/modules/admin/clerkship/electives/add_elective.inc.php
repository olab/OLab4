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
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CLERKSHIP")) || (!defined("IN_ELECTIVES"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('electives', 'update')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	if (isset($_POST["ids"])) {
		$_SESSION["ids"] = $_POST["ids"];
	} else if (isset($_GET["ids"])) {
		$_SESSION["ids"] = array(htmlentities($_GET["ids"]));
		
	}
	$user_ids = $_SESSION["ids"];
	if (count($user_ids) == 1) {
		$student_name	= get_account_data("firstlast", $user_ids[0]);
	} else {
		$student_name	= "Multiple Students";
	}
	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/admin/clerkship".(count($user_ids) == 1 ? "/clerk?ids=".$user_ids[0] : ""), "title" => $student_name);
	
	$previous_grad_year	= "";
	$msg				= "";
	foreach ($_SESSION["ids"] as $value) {
		$gradQuery 	= "SELECT `role` FROM `".AUTH_DATABASE."`.`user_access` WHERE `user_id` = ".$db->qstr($value)." AND `app_id` = ".$db->qstr(AUTH_APP_ID);
		$gradResult	= $db->GetRow($gradQuery);
		$grad_year 	= $gradResult["role"];
		
		if ($previous_grad_year != "" && $grad_year != $previous_grad_year) {
			$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/clerkship?section=student_search\\'', 5000)";

			$ERROR++;
			$ERRORSTR[]	= "You cannot add an elective to multiple students with different graduating years.  You will now be redirected back to search.";
			
			break;
		}
		$previous_grad_year	= $grad_year;
	}
	
	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/admin/clerkship?".replace_query(array("section" => "add")), "title" => "Adding Elective");

	echo "<h1>Adding Elective</h1>\n";

	// Error Checking
	switch ($STEP) {
		case 2 :
			/**
			 * Required field "geo_location" / Geographic Location.
			 */
			if ((isset($_POST["geo_location"])) && ($geo_location = clean_input($_POST["geo_location"], array("notags", "trim")))) {
				$PROCESSED["geo_location"] = $geo_location;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Geographic Location</strong> field is required.";
			}
			/**
			 * Required field "category_id" / Elective Period.
			 */
			if ((isset($_POST["category_id_name"])) && ($category_id = clean_input($_POST["category_id_name"], "int"))) {
				$PROCESSED["category_id"] = $category_id;
				if ($_POST["category_id_name"] == 0) {
					$ERROR++;
					$ERRORSTR[] = "The <strong>Elective Period</strong> field is required.";
				}
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Elective Period</strong> field is required.";
			}

			/**
			 * Required field "department_id" / Elective Department.
			 */
			if ((isset($_POST["department_id"])) && ($department_id = clean_input($_POST["department_id"], "int"))) {
				$PROCESSED["department_id"] = $department_id;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Department</strong> field is required.";
			}

			/**
			 * Required field "discipline_id" / Elective Discipline.
			 */
			if ((isset($_POST["discipline_id"])) && ($discipline_id = clean_input($_POST["discipline_id"], "int"))) {
				$PROCESSED["discipline_id"] = $discipline_id;
				if ($_POST["discipline_id"] == 0) {
					$ERROR++;
					$ERRORSTR[] = "The <strong>Discipline</strong> field is required.";
				}
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Discipline</strong> field is required.";
			}
			
			/**
			 * Non-required field "sub_discipline" / Sub-Discipline .
			 */
			if ((isset($_POST["sub_discipline"])) && ($sub_discipline = clean_input($_POST["sub_discipline"], array("notags", "trim")))) {
				$PROCESSED["sub_discipline"] = $sub_discipline;
			}
			
            $event_date = validate_calendar("Elective", "event", false);
            if ((isset($event_date)) && ((int) $event_date)) {
                $PROCESSED["event_start"]   = (int) $event_date;
				$PROCESSED["event_finish"]  = $PROCESSED["event_start"] + (clean_input($_POST["event_finish_name"], array("int")) * ONE_WEEK) - 10800;
				$start_stamp                = $PROCESSED["event_start"];
				$end_stamp                  = $PROCESSED["event_finish"];
				foreach ($_SESSION["ids"] as $value) {
					$dateCheckQuery = "SELECT `event_title`, `event_start`, `event_finish`   
					FROM `".CLERKSHIP_DATABASE."`.`events`, `".CLERKSHIP_DATABASE."`.`electives`, `".CLERKSHIP_DATABASE."`.`event_contacts`
					WHERE `".CLERKSHIP_DATABASE."`.`events`.`event_id` = `".CLERKSHIP_DATABASE."`.`electives`.`event_id`
					AND `".CLERKSHIP_DATABASE."`.`events`.`event_id` = `".CLERKSHIP_DATABASE."`.`event_contacts`.`event_id`
					AND `".CLERKSHIP_DATABASE."`.`event_contacts`.`etype_id` = ".$db->qstr($value)." 
					AND `".CLERKSHIP_DATABASE."`.`events`.`event_type` = \"elective\"
					AND `".CLERKSHIP_DATABASE."`.`events`.`event_status` != \"trash\"
					AND ((".$db->qstr($start_stamp)." >= `".CLERKSHIP_DATABASE."`.`events`.`event_start` 
					AND ".$db->qstr($start_stamp)." <= `".CLERKSHIP_DATABASE."`.`events`.`event_finish`)
					OR (".$db->qstr($end_stamp)." >= `".CLERKSHIP_DATABASE."`.`events`.`event_start` 
					AND ".$db->qstr($end_stamp)." <= `".CLERKSHIP_DATABASE."`.`events`.`event_finish`)
					OR (`".CLERKSHIP_DATABASE."`.`events`.`event_start` >= ".$db->qstr($start_stamp)." 
					AND `".CLERKSHIP_DATABASE."`.`events`.`event_finish` <= ".$db->qstr($end_stamp)."))";
					
					if ($dateCheck	= $db->GetAll($dateCheckQuery))  {
                        $dateError = "";
						$dateErrorCtr = 0;
						foreach ($dateCheck as $dateValue) {
							$dateErrorCtr++;
							$dateError .= "<br /><tt>" . $dateValue["event_title"] . "<br />  *  Starts: ". date("Y-m-d", $dateValue["event_start"]) . "<br />  * Finishes: " . date("Y-m-d", $dateValue["event_finish"])."</tt><br />";
						}
						$ERROR++;
						if ($dateErrorCtr == 1) {
							$ERRORSTR[] = "This elective conflicts with the following elective:<br />".$dateError;
						}  else {
							$ERRORSTR[] = "This elective conflicts with the following electives:<br />".$dateError;
						}
					}
				}
            }
			
			/**
			 * Required field "schools_id" / Host School.
			 */
			if ((isset($_POST["schools_id"])) && ($medical_school = clean_input($_POST["schools_id"], array("int")))) {
				$PROCESSED["schools_id"] = $medical_school;
				if ($medical_school == "99999") {
					if ((isset($_POST["other_medical_school"])) && ($other = clean_input($_POST["other_medical_school"], array("notags", "trim")))) {
						$PROCESSED["other_medical_school"] = $other;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Other</strong> field is required.";
					}
				} else {
					$PROCESSED["other_medical_school"] = "";
				}
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Host School</strong> field is required.";
			}
			
			/**
			 * Required field "objective" / Objective.
			 */
			if ((isset($_POST["objective"])) && ($objective = clean_input($_POST["objective"], array("notags", "trim")))) {
				$PROCESSED["objective"] = $objective;
				if (strlen($objective) > 300)
				{
					$ERROR++;
					$ERRORSTR[] = "<strong>Objective</strong> can only contain a maximum of 300 characters.";
				}
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Objective</strong> field is required.";
			}
					
			/**
			 * Non-required field "preceptor_prefix" / Preceptor Prefix.
			 */
			if ((isset($_POST["preceptor_prefix"])) && ($preceptor_prefix = clean_input($_POST["preceptor_prefix"], array("notags", "trim")))) {
				$PROCESSED["preceptor_prefix"] = $preceptor_prefix;
			}
			
			/**
			 * Non-required field "preceptor_first_name" / Preceptor First Name.
			 */
			if ((isset($_POST["preceptor_first_name"])) && ($preceptor_first_name = clean_input($_POST["preceptor_first_name"], array("notags", "trim")))) {
				$PROCESSED["preceptor_first_name"] = $preceptor_first_name;
			}
			
			/**
			 * Required field "preceptor_last_name" / Preceptor Last Name.
			 */
			if ((isset($_POST["preceptor_last_name"])) && ($preceptor_last_name = clean_input($_POST["preceptor_last_name"], array("notags", "trim")))) {
				$PROCESSED["preceptor_last_name"] = $preceptor_last_name;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Preceptor Last Name</strong> field is required.";
			}
			
			/**
			 * Required field "address" / Address.
			 */
			if ((isset($_POST["address"])) && ($address = clean_input($_POST["address"], array("notags", "trim")))) {
				$PROCESSED["address"] = $address;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Address</strong> field is required.";
			}
			
			/**
			 * Required field "countries_id" / Country.
			 */
			if ((isset($_POST["countries_id"])) && ($countries_id = clean_input($_POST["countries_id"], "int"))) {
				$PROCESSED["countries_id"] = $countries_id;
				//Province is required if the `countries_id` has provinces related to it in the database.
				$query = "	SELECT count(`province`) FROM `global_lu_provinces`
							WHERE `country_id` = ".$db->qstr($PROCESSED["countries_id"])."
							GROUP BY `country_id`";
				$province_required = ($db->GetOne($query) ? true : false);
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Country</strong> field is required.";
				$province_required = false;
			}
			
			/**
			 * Required field "prov_state" / Prov / State.
			 */
			if ((isset($_POST["prov_state"])) && ($prov_state = clean_input($_POST["prov_state"], array("notags", "trim")))) {
				$PROCESSED["prov_state"] = htmlentities($prov_state);
				if (strlen($prov_state) > 100) {
					$ERROR++;
					$ERRORSTR[] = "The <strong>Prov / State</strong> can only contain a maximum of 100 characters.";
				} else {
                    $query = "SELECT `province_id` FROM `global_lu_provinces` WHERE `province` = ".$db->qstr($PROCESSED["prov_state"]);
                    $province_id = $db->GetOne($query);
                    if ($province_id) {
                        $PROCESSED["province_id"] = $province_id;
                    } else {
                        $PROCESSED["province_id"] = 0;
                    }
                }
			} elseif($province_required) {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Prov / State</strong> field is required.";
			}
			
			/**
			 * Required field "city" / City.
			 */
			if ((isset($_POST["city"])) && ($city = clean_input($_POST["city"], array("notags", "trim"))) && strpos($city, ",") === false) {
				$PROCESSED["city"] = $city;
			} else {
				$ERROR++;
                $ERRORSTR[] = "The <strong>City</strong> field is required, and should not contain either the province/state or any commas.";
			}
			
			/**
			 * Non-required field "postal_zip_code" / Postal / Zip Code.
			 */
			if ((isset($_POST["postal_zip_code"])) && ($postal_zip_code = clean_input($_POST["postal_zip_code"], array("notags", "trim")))) {
				$PROCESSED["postal_zip_code"] = strtoupper(str_replace(" ", "", $postal_zip_code));
			}
			
			/**
			 * Non-required field "fax" / Fax.
			 */
			if ((isset($_POST["fax"])) && ($fax = clean_input($_POST["fax"], array("notags", "trim")))) {
				$PROCESSED["fax"] = $fax;
			}
			
			/**
			 * Non-required field "phone" / Phone.
			 */
			if ((isset($_POST["phone"])) && ($phone = clean_input($_POST["phone"], array("notags", "trim")))) {
				$PROCESSED["phone"] = $phone;
			}
			
			/**
			 * Required field "email" /  Email.
			 */
			if ((isset($_POST["email"])) && ($email = clean_input($_POST["email"], array("notags", "trim", "emailcontent")))) {
				$PROCESSED["email"] = $email;
				if (!valid_address($email)) {
					$ERROR++;
					$ERRORSTR[] = "The <strong>Email</strong> you provided is not valid.";
				}
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Email</strong> field is required.";
			}
			
			/**
			 * Required field "status" /  Status.
			 */
			if ((isset($_POST["event_status"])) && ($status = clean_input($_POST["event_status"], array("notags")))) {
				$PROCESSED["event_status"] = $status;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Status</strong> field is required.";
			}
			if (!$ERROR) {
				$PROCESSED["updated_date"]			= time();
				$PROCESSED["updated_by"]			= $ENTRADA_USER->getID();
				
				$EVENT["category_id"]				= $PROCESSED["category_id"];
				$query = "	SELECT `region_id` FROM `".CLERKSHIP_DATABASE."`.`regions`
							WHERE `countries_id` = ".$db->qstr($PROCESSED["countries_id"])."
							AND ".($PROCESSED["province_id"] ? "`province_id` = ".$db->qstr($PROCESSED["province_id"]) : "`prov_state` = ".$db->qstr($PROCESSED["prov_state"]))."
							AND `region_name` LIKE ".$db->qstr($PROCESSED["city"])."
							AND `region_active` = 1";
				$region_id = $db->GetOne($query);
				
				if ($region_id) {
					$PROCESSED["region_id"] = clean_input($region_id, "int");
					$EVENT["region_id"] = clean_input($region_id, "int");
				} else {
					$REGION = array();
					$REGION["countries_id"] = $PROCESSED["countries_id"];
					$REGION["prov_state"] = $PROCESSED["prov_state"];
					$REGION["province_id"] = $PROCESSED["province_id"];
					$REGION["region_name"] = $PROCESSED["city"];
					if ($db->AutoExecute(CLERKSHIP_DATABASE.".regions", $REGION, "INSERT") && ( $region_id = $db->Insert_Id())) {
						$PROCESSED["region_id"] = clean_input($region_id, "int");
						$EVENT["region_id"] = clean_input($region_id, "int");
					} else {
						$ERROR++;
						$ERRORSTR[] = "A region could not be added to the system for this elective. The system administrator was informed of this error; please try again later.";

						application_log("error", "There was an error inserting a new region for a newly created elective. Database said: ".$db->ErrorMsg());
						$PROCESSED["region_id"] = 0;
						$EVENT["region_id"] = 0;
					}
				}
				$EVENT["event_title"]				= clerkship_categories_title($PROCESSED["department_id"], $levels = 3);
				$EVENT["event_start"]               = $PROCESSED["event_start"];
				$EVENT["event_finish"]				= $PROCESSED["event_finish"];
				$EVENT["event_type"]				= "elective";
				$EVENT["event_status"]				= $PROCESSED["event_status"];
				$EVENT["modified_last"]				= $PROCESSED["updated_date"];
				$EVENT["modified_by"]				= $PROCESSED["updated_by"];
				$EVENT["rotation_id"]				= 10;
				foreach ($_SESSION["ids"] as $value) {
					if ($db->AutoExecute(CLERKSHIP_DATABASE.".events", $EVENT, "INSERT")) {
						if ($EVENT_ID = $db->Insert_Id()) {
							$check_query	= "SELECT * 
							FROM `".CLERKSHIP_DATABASE."`.`events` 
							WHERE `event_type`= \"elective\" 
							AND `event_status` = \"approval\" 
							ORDER BY `event_start` ASC";
							
							$check_results	= $db->GetAll($check_query);
								
							if ($check_results) {
								$url = ENTRADA_URL."/admin/clerkship";
							} else {
								$url = ENTRADA_URL."/admin/clerkship?section=student_search";
							}
							
								$CONTACTS["event_id"]				= $EVENT_ID;
								$CONTACTS["econtact_type"]			= "student";
								$CONTACTS["etype_id"]				= $value;
								
								if ($db->AutoExecute(CLERKSHIP_DATABASE.".event_contacts", $CONTACTS, "INSERT")) {
									$mail = new Zend_Mail("iso-8859-1");
									$international_msg	= "";
                                                                        
									if ($PROCESSED["event_status"] == "published") {
										
										$student_email 		= get_account_data("email", $value);
										$student_name 		= get_account_data("firstlast", $value);
																				
										// Check if international elective, if so email international rep in UGE
										if ($PROCESSED["geo_location"] == "International")
										{
											$international_msg	= "The completion of the documentation for international activity is mandatory. Academic credit will not be granted unless all steps have been completed and approval granted prior to departure. Please go to the following link and ensure you have completed the necessary steps:\n\n";
											$international_msg .= $CLERKSHIP_INTERNATIONAL_LINK."\n\n";
											
											$message  = "Attention ".$AGENT_CONTACTS["agent-clerkship-international"]["name"].",\n\n";
											$message .= "An international clerkship elective has been approved by Queen's University please review this:\n";
											$message .= "=======================================================\n\n";
											$message .= "Approved At:\t\t".date("r", time())."\n";
											$message .= "Approved By:\t\t".$_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]."\n";
											$message .= "E-Mail Address:\t\t".$_SESSION["details"]["email"]."\n\n";
											$message .= "-------------------------------------------------------\n\n";
											$message .= "The clerk's name is: " . $student_name ." and they will begin this elective on " . date("Y-m-d", $PROCESSED["event_start"])." and will finish on " . date("Y-m-d", $PROCESSED["event_finish"])."\n\n";
											$message .= "Contact:\t\t".$AGENT_CONTACTS["agent-clerkship"]["email"]." if you have any questions.\n\n";
											$message .= "=======================================================";

											$mail->addHeader("X-Priority", "3");
											$mail->addHeader('Content-Transfer-Encoding', '8bit');
											$mail->addHeader("X-Originating-IP", $_SERVER["REMOTE_ADDR"]);
											$mail->addHeader("X-Section", "Electives Approval");
                                                                 
                                            $mail->clearRecipients();
                                            $mail->clearFrom();
                                            $mail->clearSubject();                       
											$mail->addTo($AGENT_CONTACTS["agent-clerkship-international"]["email"], $AGENT_CONTACTS["agent-clerkship-international"]["name"]);
											$mail->setFrom(($_SESSION["details"]["email"]) ? $_SESSION["details"]["email"] : "noreply@queensu.ca", $_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]);
											$mail->setSubject("International Electives Approval - ".APPLICATION_NAME);
											$mail->setReplyTo($AGENT_CONTACTS["agent-clerkship"]["email"], $AGENT_CONTACTS["agent-clerkship"]["name"]);
											$mail->setBodyText($message);
											
											try {
                                                $mail->send();
											} catch (Zend_Mail_Transport_Exception $e){
                                                $ERROR++;
                                                $ERRORSTR[] = "There was a problem sending the approval email to the international rep for this elective. The MEdTech Unit was informed of this error; please try again later.";

                                                application_log("error", "There was an error sending an approval email to the international clerkship admin for clerkship elective ID[".$EVENT_ID."]. Mail said: ".$e->getMessage());
											}
											
											$mail->clearRecipients();
											$mail->clearReplyTo();
											$mail->clearFrom();
											$mail->clearSubject();
										}
										
										$message  = "Attention ".$student_name.",\n\n";
										$message .= "A Clerkship elective has been approved by the undergraduate office:\n";
										$message .= "=======================================================\n\n";
										$message .= "Approved At:\t\t".date("r", time())."\n";
										$message .= "Approved By:\t\t".$_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]." [".$_SESSION["details"]["username"]."]\n";
										$message .= "E-Mail Address:\t\t".$_SESSION["details"]["email"]."\n\n";
										$message .= "Contact:\t\t".$AGENT_CONTACTS["agent-clerkship"]["email"]." if you have any questions.\n\n";
										$message .= "-------------------------------------------------------\n\n";
										$message .= "Go here to view this elective: " . ENTRADA_URL."/clerkship/electives?section=view&id=".$EVENT_ID."\n\n";
										$message .= $international_msg;
										$message .= "=======================================================";
										
										$mail->addHeader("X-Priority", "3");
										$mail->addHeader('Content-Transfer-Encoding', '8bit');
										$mail->addHeader("X-Originating-IP", $_SERVER["REMOTE_ADDR"]);
										$mail->addHeader("X-Section", "Electives Approval");
                                                                                        
										$mail->addTo($student_email, $student_name);
										$mail->setFrom(($_SESSION["details"]["email"]) ? $_SESSION["details"]["email"] : "noreply@queensu.ca", $_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]);
										$mail->setSubject("Electives Approval - ".APPLICATION_NAME);
										$mail->setReplyTo($AGENT_CONTACTS["agent-clerkship"]["email"], $AGENT_CONTACTS["agent-clerkship"]["name"]);
										$mail->setBodyText($message);
											
										try {
                                            $mail->send();
                                        } catch (Zend_Mail_Transport_Exception $e){
											$ERROR++;
											$ERRORSTR[] = "There was a problem sending the approval email to the student for this elective. The MEdTech Unit was informed of this error; please try again later.";
						
											application_log("error", "There was an error sending an approval email to the student for clerkship elective ID[".$EVENT_ID."]. Mail said: ".$e->getMessage());
										}
                                                                                
										$mail->clearRecipients();
										$mail->clearReplyTo();
										$mail->clearFrom();
										$mail->clearSubject();
										
										$message  = "Attention ".(isset($PROCESSED["preceptor_prefix"]) && $PROCESSED["preceptor_prefix"] != "" ? $PROCESSED["preceptor_prefix"] . " " : "")." ".(isset($PROCESSED["preceptor_first_name"]) && $PROCESSED["preceptor_first_name"] != "" ? $PROCESSED["preceptor_first_name"] . " " : "") . $PROCESSED["preceptor_last_name"].",\n\n";
										$message .= "A Clerkship elective has been approved by Queen's University please review this:\n";
										$message .= "=======================================================\n\n";
										$message .= "Approved At:\t\t".date("r", time())."\n";
										$message .= "Approved By:\t\t".$_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]."\n";
										$message .= "E-Mail Address:\t\t".$_SESSION["details"]["email"]."\n\n";
										$message .= "=======================================================\n\n";
										$message .= "Clerk's Name:\t\t" . $student_name ."\n\n";
										$message .= "Elective Start:\t\t" . date("Y-m-d", $PROCESSED["event_start"])."\n\n";
										$message .= "Elective Finish:\t" . date("Y-m-d", $PROCESSED["event_finish"])."\n\n";
										$message .= "Contact:\t\t".$AGENT_CONTACTS["agent-clerkship"]["email"]." if you have any questions.\n\n";
										$message .= "Please fill out this evaluation form upon the clerk's completion of the elective:\n\n".$CLERKSHIP_EVALUATION_FORM."\n\n";
										$message .= "=======================================================";
										
										$mail->addHeader("X-Priority", "3");
										$mail->addHeader('Content-Transfer-Encoding', '8bit');
										$mail->addHeader("X-Originating-IP", $_SERVER["REMOTE_ADDR"]);
										$mail->addHeader("X-Section", "Electives Approval");
                                                                                        
										$mail->addTo($PROCESSED["email"], (isset($PROCESSED["preceptor_prefix"]) && $PROCESSED["preceptor_prefix"] != "" ? $PROCESSED["preceptor_prefix"] . " " : "").$PROCESSED["preceptor_first_name"] . " " . $PROCESSED["preceptor_last_name"]);
										$mail->setFrom(($_SESSION["details"]["email"]) ? $_SESSION["details"]["email"] : "noreply@queensu.ca", $_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]);
										$mail->setSubject("Electives Approval - ".APPLICATION_NAME);
										$mail->setReplyTo($AGENT_CONTACTS["agent-clerkship"]["email"], $AGENT_CONTACTS["agent-clerkship"]["name"]);
										$mail->setBodyText($message);
                                                                                
										try {
                                            $mail->send();
                                        } catch (Zend_Mail_Transport_Exception $e){
											$ERROR++;
											$ERRORSTR[] = "There was a problem sending the approval email to the preceptor for this elective. The MEdTech Unit was informed of this error; please try again later.";
						
											application_log("error", "There was an error sending an approval email to the preceptor of clerkship elective ID[".$EVENT_ID."]. Mail said: ".$e->getMessage());
										}
                                                                                
										$mail->clearRecipients();
										$mail->clearReplyTo();
										$mail->clearFrom();
										$mail->clearSubject();
										
										if (count($NOTICESTR) == 1 && $msg == "") {
											$msg	= "You have approved this elective for ".$NOTICESTR[0].".  An email will be sent to this student informing them of this.<br /><br /> You will now be redirected to the clerkship index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
										} elseif ($msg == "") {
											$msg	= "You have approved this elective for ".implode(", ", $NOTICESTR).".  An email will be sent to the students informing them of this.<br /><br /> You will now be redirected to the clerkship index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
										}
									}
								} else {
									$ERROR++;
									$ERRORSTR[] = "There was a problem inserting this elective into the system. The MEdTech Unit was informed of this error; please try again later.";
				
									application_log("error", "There was an error inserting a clerkship elective event contact ID[".$value."]. Database said: ".$db->ErrorMsg());
								}
							} else {
								$ERROR++;
								$ERRORSTR[] = "There was a problem inserting this elective into the system. The MEdTech Unit was informed of this error; please try again later.";
			
								application_log("error", "There was an error inserting a clerkship elective. Database said: ".$db->ErrorMsg());
							}
							$ELECTIVE               = $PROCESSED;
							$ELECTIVE["event_id"]	= $EVENT_ID;
							
							if ($db->AutoExecute(CLERKSHIP_DATABASE.".electives", $ELECTIVE, "INSERT")) {
								continue;
							} else {
								$ERROR++;
								$ERRORSTR[] = "There was a problem inserting this elective into the system. The MEdTech Unit was informed of this error; please try again later.";
			
								application_log("error", "There was an error inserting a clerkship elective. Database said: ".$db->ErrorMsg());
							}
						} else {
							$ERROR++;
							$ERRORSTR[] = "There was a problem inserting this elective into the system. The MEdTech Unit was informed of this error; please try again later.";
		
							application_log("error", "There was an error inserting a clerkship elective. Database said: ".$db->ErrorMsg());
						}
					}
					if (!isset($SUCCESSSTR) || count($SUCCESS == 0)) {
                        add_statistic("clerkship_electives", "create", "event_id", $EVENT_ID, $ENTRADA_USER->getID());
						$SUCCESS++;
						if (isset($msg) && $msg != "") {
							$SUCCESSSTR[]  	= $msg;
						} else {
							$SUCCESSSTR[]  	= "You have successfully added this <strong>".html_encode($PROCESSED["geo_location"])."</strong> elective to the system.<br /><br />Please <a href=\"".$url."\">click here</a> to proceed to the index page or you will be automatically forwarded in 5 seconds.";
						}
						$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000)";
						
						application_log("success", "New elective [".$EVENT["event_title"]."] added to the system.");
					}
			} else {
				$STEP = 1;
			}
		break;
		case 1 :
		default :
			continue;
		break;
	}

	// Display Content
	switch ($STEP) {
		case 2 :
			if ($NOTICE) {
				echo display_notice();
			}
			if ($SUCCESS) {				
				echo display_success();
			}
			if ($ERROR) {
				echo display_error();
			}
		break;
		case 1 :
		default :
            if ($NOTICE) {
                echo display_notice();
            }
            $HEAD[] 		= "<link href=\"".ENTRADA_URL."/javascript/calendar/css/xc2_default.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
            $HEAD[]			= "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/picklist.js\"></script>\n";
            $HEAD[]			= "<script type=\"text/javascript\">
            function checkInternational (flag) 
            {
                if (flag == \"true\") {
                    $('international_notice').style.display = 'block';
                } else {
                    $('international_notice').style.display = 'none';
                }
            }

            function showOther() {
                var obj = $('schools_id');

                var value = obj.options[obj.selectedIndex].value;

                if (value == '99999') {
                    $('other_label').style.display = 'block';
                    $('other_medical_school').style.display = 'block';
                    $('other_span').style.display = 'block';
                } else {
                    $('other_label').style.display = 'none';
                    $('other_medical_school').value = '';
                    $('other_medical_school').style.display = 'none';
                    $('other_span').style.display = 'none';
                }
            }

            function changeDurationMessage() {
                $('auto_end_date').style.display = 'inline';
                var value = $('event_date').value;
                newDate = toJSDate(value);
                switch (\$F('event_finish')) {
                    case '1':
                        var days = 6;
                        var weekText = ' week';
                        break;	
                    case '2':
                        var days = 13;
                        var weekText = ' weeks';
                        break;
                    case '3':
                        var days = 20;
                        var weekText = ' weeks';
                        break;
                    case '4':
                        var days = 27;
                        var weekText = ' weeks';
                        break;
                    case '5':
                        var days = 34;
                        var weekText = ' weeks';
                        break;
                    default:
                        var days = 13;
                        var weekText = ' weeks';
                        break;
                }
                newDate.setDate(newDate.getDate()+days);
                newDate = toCalendarDate(newDate);
                $('auto_end_date').innerHTML = '&nbsp;&nbsp;&nbsp;Ending in '+\$F('event_finish')+weekText+' on ' +newDate;
            }

            function setDateValue(field, date) {
                $('auto_end_date').style.display = 'inline';
                newDate = toJSDate(date);
                switch (\$F('event_finish')) {
                    case '1':
                        var days = 6;
                        var weekText = ' weeks';
                        break;
                    case '2':
                        var days = 13;
                        var weekText = ' weeks';
                        break;
                    case '3':
                        var days = 20;
                        var weekText = ' weeks';
                        break;
                    case '4':
                        var days = 27;
                        var weekText = ' weeks';
                        break;
                    case '5':
                        var days = 34;
                        var weekText = ' weeks';
                        break;
                    default:
                        var days = 13;
                        var weekText = ' weeks';
                        break;
                }
                
                newDate.setDate(newDate.getDate()+days);
                newDate = toCalendarDate(newDate);
                $('auto_end_date').innerHTML = '&nbsp;&nbsp;&nbsp;Ending in '+\$F('event_finish')+weekText+' on ' +newDate;
                $('event_date').value = date;
            }

            var updater = null;
            function AjaxFunction(cat_id) {	
                var url='".webservice_url("clerkship_department")."';
                url=url+'?cat_id='+cat_id".(isset($PROCESSED["department_id"]) && $PROCESSED["department_id"] ? "+'&dept_id=".$PROCESSED["department_id"]."'" : "").";
                new Ajax.Updater($('department_category'), url, 
                    { 
                        method:'get'
                    });
            }

            function provStateFunction(countries_id) {	
                var url='".webservice_url("clerkship_prov")."';
                url=url+'?countries_id='+countries_id+'&prov_state=".rawurlencode((isset($_POST["prov_state"]) ? clean_input($_POST["prov_state"], array("notags", "trim")) : DEFAULT_PROVINCE_ID))."';
                new Ajax.Updater($('prov_state_div'), url, 
                    { 
                        method:'get',
                        onComplete: function () {
                            generateAutocomplete();
                            if ($('prov_state').selectedIndex || $('prov_state').selectedIndex === 0) {
                                $('prov_state_label').removeClassName('form-nrequired');
                                $('prov_state_label').addClassName('form-required');
                            } else {
                                $('prov_state_label').removeClassName('form-required');
                                $('prov_state_label').addClassName('form-nrequired');
                            }
                        }
                    });
            }

            function generateAutocomplete() {
                if (updater != null) {
                    updater.url = '".ENTRADA_URL."/api/cities-by-country.api.php?countries_id='+$('countries_id').options[$('countries_id').selectedIndex].value+'&prov_state='+($('prov_state') !== null ? ($('prov_state').selectedIndex || $('prov_state').selectedIndex === 0 ? $('prov_state').options[$('prov_state').selectedIndex].value : $('prov_state').value) : '');
                } else {
                    updater = new Ajax.Autocompleter('city', 'city_auto_complete', 
                        '".ENTRADA_URL."/api/cities-by-country.api.php?countries_id='+$('countries_id').options[$('countries_id').selectedIndex].value+'&prov_state='+($('prov_state') !== null ? ($('prov_state').selectedIndex || $('prov_state').selectedIndex === 0 ? $('prov_state').options[$('prov_state').selectedIndex].value : $('prov_state').value) : ''), 
                        {
                            frequency: 0.2, 
                            minChars: 2
                        });
                }
            }
            </script>\n";

            $ONLOAD[]		= "showOther()";
            $ONLOAD[]		= "AjaxFunction(\$F($('addElectiveForm')['category_id']))";
            $ONLOAD[]		= "provStateFunction(\$F($('addElectiveForm')['countries_id']))";
            $ONLOAD[]		= "setMaxLength()";
            $ONLOAD[]		= "changeDurationMessage()";

            if ($ERROR) {
                echo display_error();
            }
            ?>
            <form id="addElectiveForm" action="<?php echo ENTRADA_URL; ?>/admin/clerkship/electives?<?php echo replace_query(array("step" => 2)); ?>" method="post" onsubmit="selIt()">
            <table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Adding Elective">
            <colgroup>
                <col style="width: 3%" />
                <col style="width: 20%" />
                <col style="width: 77%" />
            </colgroup>
            <tr>
                <td colspan="3"><h2>Elective Details</h2></td>
            </tr>
            <tr>
                <td></td>	
                <td style="vertical-align: top"><label for="student_name" class="form-required">Student Name<?= ((@count($_SESSION["ids"]) != 1) ? "s" : "") ?>:</label></td>
                <td>
                    <?php
                    foreach ($_SESSION["ids"] as $user_id) {
                        $query	= "SELECT CONCAT_WS(', ', `".AUTH_DATABASE."`.`user_data`.`lastname`, `".AUTH_DATABASE."`.`user_data`.`firstname`) AS `fullname` FROM `".AUTH_DATABASE."`.`user_data` LEFT JOIN `".AUTH_DATABASE."`.`user_access` ON `".AUTH_DATABASE."`.`user_access`.`user_id`=`".AUTH_DATABASE."`.`user_data`.`id` WHERE `".AUTH_DATABASE."`.`user_data`.`id`='".$user_id."' AND `group`='student'";
                        $result	= $db->GetRow($query);
                        if ($result) {
                            echo "<a href=\"".ENTRADA_URL."/admin/clerkship/clerk?ids=".$user_id."\" style=\"font-weight: bold\">".$result["fullname"]."</a><br />";
                        }
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td colspan="3">&nbsp;</td>
            </tr>
            <tr>
                <td></td>
                <td valign="top"><label for="geo_location" class="form-required">Geographic Location</label></td>
                <td style="vertical-align: top">
                <input type="radio" name="geo_location" id="geo_location_national" onclick="checkInternational('false');" value="National"<?php echo (((!isset($PROCESSED["geo_location"])) || ((isset($PROCESSED["geo_location"])) && ($PROCESSED["geo_location"]) == "National")) ? " checked=\"checked\"" : ""); ?> /> <label for="geo_location_national">National</label><br />
                <input type="radio" name="geo_location" id="geo_location_international" onclick="checkInternational('true');" value="International"<?php echo (((isset($PROCESSED["geo_location"])) && $PROCESSED["geo_location"] == "International") ? " checked=\"checked\"" : ""); ?> /> <label for="geo_location_international">International</label>
                <div id="international_notice" name="international_notice" class="content-small" style="padding-top: 2px; display: none">
                    <strong>&nbsp;&nbsp;&nbsp;Note:</strong> The Student will be emailed the necessary forms.
                </div>
                </td>
            </tr>
            <tr>
                <td colspan="3">&nbsp;</td>
            </tr>
            <tr>
                <td></td>
                <td><label for="category_id" class="form-required">Elective Period</label></td>
                <td>
                    <select id="category_id" name="category_id_name" onchange="AjaxFunction(this.value);" style="width: 90%">
                    <?php
                    // Get grad year of the last student.

                    $gradQuery 	= "SELECT `role` FROM `".AUTH_DATABASE."`.`user_access` WHERE `user_id` = ".$db->qstr($value)." AND `app_id` = ".$db->qstr(AUTH_APP_ID);
                    $gradResult	= $db->GetRow($gradQuery);
                    $grad_year 	= $gradResult["role"];

                    $query	= "SELECT * FROM `".CLERKSHIP_DATABASE."`.`categories` WHERE `category_type` = ".$db->qstr($CLERKSHIP_CATEGORY_TYPE_ID)." AND `category_name` = ".$db->qstr("Class of ".$grad_year);

                    $result	= $db->GetRow($query);

                    if ($result) {
                        echo "<option value=\"0\"".((!isset($PROCESSED["category_id"])) ? " selected=\"selected\"" : "").">-- Elective Period --</option>\n";
                        $query		= "SELECT * FROM `".CLERKSHIP_DATABASE."`.`categories` WHERE `category_parent` = ".$db->qstr($result["category_id"])." AND `category_type` = '22'";
                        $results	= $db->GetAll($query);
                        if ($results) {
                            foreach ($results as $result) {
                                echo "<option value=\"".(int)$result["category_id"]."\"".(isset($PROCESSED["category_id"]) && $PROCESSED["category_id"] == (int)$result["category_id"] ? " selected=\"selected\"" : "").">".clerkship_categories_title($result["category_id"])." (".date("Y-m-d", $result["category_start"])." &gt; ".date("Y-m-d", $result["category_finish"]).")</option>\n";	
                            }
                        }
                    } else {
                        echo "<option value=\"0\"".((!isset($PROCESSED["category_id"])) ? " selected=\"selected\"" : "").">-- No Elective Periods to Choose From --</option>\n";
                    }
                    ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td colspan="3">&nbsp;</td>
            </tr>
            <tr>
                <td></td>
                <td><label for="department_id" class="form-required">Elective Department</label></td>
                <td>
                    <div id="department_category" style="display: inline">Select an Elective Period above</div>
                </td>
            </tr>
            <tr>
                <td></td>
                <td><label for="discipline_id" class="form-required">Elective Discipline</label></td>
                <td>
                    <?php
                        if (@count($discipline = clerkship_fetch_disciplines()) > 0) {
                            echo "<select id=\"discipline_id\" name=\"discipline_id\" style=\"width: 90%\">\n";
                            echo "<option value=\"0\"".((!isset($PROCESSED["discipline_id"])) ? " selected=\"selected\"" : "").">-- Discipline --</option>\n";
                            foreach ($discipline as $value) {
                                echo "<option value=\"".(int) $value["discipline_id"]."\"".((isset($PROCESSED["discipline_id"]) && $PROCESSED["discipline_id"] == $value["discipline_id"]) ? " selected=\"selected\"" : "").">".html_encode($value["discipline"])."</option>\n";
                            }
                            echo "</select>\n";
                            } else {
                                echo "<input type=\"hidden\" id=\"discipline_id\" name=\"discipline_id\" value=\"0\" />\n";
                                echo "Discipline Information Not Available\n";
                            }
                    ?>
                </td>
            </tr>
            <tr>
                <td></td>
                <td><label for="sub_discipline" class="form-nrequired">Sub-Discipline</label></td>
                <td>
                <input type="text" id="sub_discipline" name="sub_discipline" value="<?php echo html_encode((isset($PROCESSED["sub_discipline"]) && $PROCESSED["sub_discipline"] ? $PROCESSED["sub_discipline"] : "")); ?>" maxlength="64" style="width: 250px"" />
                <!--<span class="content-small">(<strong>Example:</strong> What should this say?)</span> -->
                </td>
            </tr>
            <tr>
                <td colspan="3">&nbsp;</td>
            </tr>
            <tr>
                <td></td>
                <td><label for="schools_id" class="form-required">Host School</label></td>
                <td>
                    <?php
                        if (@count($clerkship_medical_schools = clerkship_fetch_schools()) > 0) {
                            echo "<select id=\"schools_id\" name=\"schools_id\" style=\"width: 90%\" onchange=\"showOther();\">\n";
                            echo "<option value=\"0\"".((!isset($PROCESSED["schools_id"])) ? " selected=\"selected\"" : "").">-- Select Host School --</option>\n";
                            foreach ($clerkship_medical_schools as $value) {
                                echo "<option value=\"".(int) $value["schools_id"]."\"".((isset($PROCESSED["schools_id"]) && $PROCESSED["schools_id"] == $value["schools_id"]) ? " selected=\"selected\"" : "").">".html_encode($value["school_title"])."</option>\n";
                            }
                            echo "<option value=\"99999\"".(isset($PROCESSED["schools_id"]) && $PROCESSED["schools_id"] == "99999" ? " selected=\"selected\"" : "").">-- Other (Specify) --</option>\n";
                            echo "</select>\n";
                            } else {
                                echo "<input type=\"hidden\" id=\"schools_id\" name=\"schools_id\" value=\"0\" />\n";
                                echo "Host School Information Not Available\n";
                            }
                    ?>
                </td>
            </tr>
            <tr>
                <td></td>
                <td><label id="other_label" for="other_medical_school" class="form-required" style="display: none">Other</label></td>
                <td>
                <input type="text" id="other_medical_school" name="other_medical_school" value="<?php echo html_encode((isset($PROCESSED["other_medical_school"]) && $PROCESSED["other_medical_school"] ? $PROCESSED["other_medical_school"] : "")); ?>" maxlength="64" style="width: 250px; display: none" />
                <span id="other_span" class="content-small" style="display: none">(<strong>Example:</strong> Stanford University School of Medicine)</span>
                </td>
            </tr>
            <tr>
                <td colspan="3">&nbsp;</td>
            </tr>
                <?php
                echo generate_calendar("event", "Start Date", true, ((isset($PROCESSED["event_start"])) ? $PROCESSED["event_start"] : 0), false, false, true);
                ?>
            <tr>
                <td></td>
                <td><label for="event_finish" class="form-required">Elective Weeks</label></td>
                <td>
                <?php
                    $duration = ceil((isset($PROCESSED["event_finish"]) && $PROCESSED["event_finish"] && isset($PROCESSED["event_start"]) && $PROCESSED["event_start"] ? (($PROCESSED["event_finish"] - $PROCESSED["event_start"]) / 604800) : 0));
                    echo "<select id=\"event_finish\" name=\"event_finish_name\" style=\"width: 10%\" onchange=\"changeDurationMessage();\">\n";
                    for($i=1; $i<=5; $i++)  {
                        echo "<option value=\"".$i."\"".(($i == $duration) ? " selected=\"selected\"" : "").">".$i."</option>\n";
                    }
                    echo "</select>\n<div id=\"auto_end_date\" class=\"content-small\" style=\"display: none\"></div>";

                ?>
                </td>
            </tr>
            <tr>
                <td colspan="3">&nbsp;</td>
            </tr>
            <tr>
                <td></td>
                <td style="vertical-align: top"><label for="objective" class="form-required">Planned Experience</label></td>
                <td>
                    <textarea id="objective" name="objective" style="width: 95%; height: 60px" cols="50" rows="5" maxlength="300"><?php echo ((isset($PROCESSED["objective"])) ? html_encode($PROCESSED["objective"]) : ""); ?></textarea>
                    <div id="planned_note" class="content-small" style="display: block">
                        <strong><br>Note:</strong> Please provide a narrative of your educational objectives (what you hope to achieve while on the elective).
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="3"><h2>Site Details</h2></td>
            </tr>
            <tr>
                <td></td>
                <td><label for="preceptor_prefix" class="form-nrequired">Preceptor Prefix</label></td>
                <td>
                    <select id="preceptor_prefix" name="preceptor_prefix" style="width: 55px; vertical-align: middle; margin-right: 5px">
                        <option value=""<?php echo ((!isset($PROCESSED["preceptor_prefix"]) || !$PROCESSED["preceptor_prefix"]) ? " selected=\"selected\"" : ""); ?>></option>
                        <?php
                        if ((@is_array($PROFILE_NAME_PREFIX)) && (@count($PROFILE_NAME_PREFIX))) {
                            foreach ($PROFILE_NAME_PREFIX as $key => $prefix) {
                                echo "<option value=\"".html_encode($prefix)."\"".((isset($PROCESSED["preceptor_prefix"]) && $PROCESSED["preceptor_prefix"] == $prefix) ? " selected=\"selected\"" : "").">".html_encode($prefix)."</option>\n";
                            }
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td></td>
                <td><label for="preceptor_first_name" class="form-nrequired">Preceptor First Name</label></td>
                <td>
                <input type="text" id="preceptor_first_name" name="preceptor_first_name" value="<?php echo html_encode((isset($PROCESSED["preceptor_first_name"]) && $PROCESSED["preceptor_first_name"] ? $PROCESSED["preceptor_first_name"] : "")); ?>" maxlength="50" style="width: 250px"" />
                </td>
            </tr>
            <tr>
                <td></td>
                <td><label for="preceptor_last_name" class="form-required">Preceptor Last Name</label></td>
                <td>
                <input type="text" id="preceptor_last_name" name="preceptor_last_name" value="<?php echo html_encode((isset($PROCESSED["preceptor_last_name"]) && $PROCESSED["preceptor_last_name"] ? $PROCESSED["preceptor_last_name"] : "")); ?>" maxlength="50" style="width: 250px"" />
                </td>
            </tr>
            <tr>
                <td colspan="3">&nbsp;</td>
            </tr>
            <tr>
                <td></td>
                <td><label for="address" class="form-required">Address</label></td>
                <td>
                <input type="text" id="address" name="address" value="<?php echo html_encode((isset($PROCESSED["address"]) && $PROCESSED["address"] ? $PROCESSED["address"] : "")); ?>" maxlength="250" style="width: 250px"" />
                </td>
            </tr>
            <tr>
                <td></td>
                <td><label for="countries_id" class="form-required">Country</label></td>
                <td>
                    <?php
                    $countries = fetch_countries();
                    if (@count($countries) > 0) {
                        echo "<select id=\"countries_id\" name=\"countries_id\" style=\"width: 90%\" onchange=\"provStateFunction(this.value);\">\n";
                        echo "<option value=\"0\"".((!isset($PROCESSED["countries_id"])) ? " selected=\"selected\"" : "").">-- Country --</option>\n";
                        foreach ($countries as $value) {
                            echo "<option value=\"".(int) $value["countries_id"]."\"".((isset($PROCESSED["countries_id"]) && $PROCESSED["countries_id"] == $value["countries_id"]) ? " selected=\"selected\"" : (!isset($PROCESSED["countries_id"]) && $value["countries_id"] == DEFAULT_COUNTRY_ID) ? " selected=\"selected\"" : "").">".html_encode($value["country"])."</option>\n";
                        }
                        echo "</select>\n";
                    } else {
                        echo "<input type=\"hidden\" id=\"countries_id\" name=\"countries_id\" value=\"0\" />\n";
                        echo "Country Information Not Available\n";
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td></td>
                <td><label id="prov_state_label" for="prov_state_div" class="form-required">Prov / State</label></td>
                <td>
                    <div id="prov_state_div" style="display: inline">Select a Country above</div>
                </td>
            </tr>
            <tr>
                <td></td>
                <td><label for="city" class="form-required">City</label></td>
                <td>
                    <input type="text" id="city" name="city" size="100" autocomplete="off" style="width: 250px; vertical-align: middle" value="<?php echo (isset($PROCESSED["city"]) && $PROCESSED["city"] ? $PROCESSED["city"] : ""); ?>"/>
                    <script type="text/javascript">
                        $('city').observe('keypress', function(event){
                            if(event.keyCode == Event.KEY_RETURN) {
                                Event.stop(event);
                            }
                        });
                    </script>
                    <div class="autocomplete" id="city_auto_complete"></div>
                </td>
            </tr>
            <tr>
                <td></td>
                <td><label for="postal_zip_code" class="form-nrequired">Postal / Zip Code</label></td>
                <td>
                <input type="text" id="postal_zip_code" name="postal_zip_code" value="<?php echo html_encode((isset($PROCESSED["postal_zip_code"]) && $PROCESSED["postal_zip_code"] ? $PROCESSED["postal_zip_code"] : "")); ?>" maxlength="20" style="width: 250px"" />
                </td>
            </tr>
            <tr>
                <td colspan="3">&nbsp;</td>
            </tr>
            <tr>
                <td></td>
                <td><label for="phone" class="form-nrequired">Phone</label></td>
                <td>
                <input type="text" id="phone" name="phone" value="<?php echo html_encode((isset($PROCESSED["phone"]) && $PROCESSED["phone"] ? $PROCESSED["phone"] : "")); ?>" maxlength="25" style="width: 250px"" />
                </td>
            </tr>
            <tr>
                <td></td>
                <td><label for="fax" class="form-nrequired">Fax</label></td>
                <td>
                <input type="text" id="fax" name="fax" value="<?php echo html_encode((isset($PROCESSED["fax"]) && $PROCESSED["fax"] ? $PROCESSED["fax"] : "")); ?>" maxlength="25" style="width: 250px"" />
                </td>
            </tr>
            <tr>
                <td></td>
                <td><label for="email" class="form-required">Email</label></td>
                <td>
                <input type="text" id="email" name="email" value="<?php echo html_encode((isset($PROCESSED["email"]) && $PROCESSED["email"] ? $PROCESSED["email"] : "")); ?>" maxlength="150" style="width: 250px"" />
                </td>
            </tr>
            <tr>
                <td colspan="3"><h2>Elective Status</h2></td>
            </tr>
            <tr>
                <td></td>
                <td style="vertical-align: top"><label for="event_status" class="form-required">Status</label></td>
                <td>
                <input type="radio" name="event_status" id="status_approval" value="approval"<?php echo (((isset($PROCESSED["event_status"]) && ($PROCESSED["event_status"]) == "approval")) ? " checked=\"checked\"" : ""); ?> /> <label for="status_approval">Awaiting Approval</label><br />
                <input type="radio" name="event_status" id="status_published" value="published"<?php echo (((isset($PROCESSED["event_status"])) && $PROCESSED["event_status"] == "published") ? " checked=\"checked\"" : ""); ?> /> <label for="status_published">Approved</label><br />
                <input type="radio" name="event_status" id="status_trash" value="trash"<?php echo (((isset($PROCESSED["event_status"])) && $PROCESSED["event_status"] == "trash") ? " checked=\"checked\"" : ""); ?> /> <label for="status_trash">Rejected</label><br /><br />
                <div id="status_notice" name="status_notice" class="content-small" style="padding-top: 2px; display: inline">
                    <strong>Note:</strong> Student will be notified upon approval / rejection of this elective.
                </div>
                </td>
            </tr>
            <tr>
                <td colspan="3" style="padding-top: 25px">
                    <table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td style="width: 25%; text-align: left">
                            <input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/clerkship/electives'" />
                        </td>
                        <td style="width: 75%; text-align: right; vertical-align: middle">
                            <input type="submit" class="btn btn-primary" value="Save" />
                        </td>
                    </tr>
                    </table>
                </td>
            </tr>
            </table>
            </form>
            <br /><br />
            <?php
		break;
	}
}
?>
