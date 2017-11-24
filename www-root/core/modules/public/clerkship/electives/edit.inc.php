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
 * Allows students to edit elective in the system if they have not yet been approved.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Andrew Dos-Santos <andrew.dos-santos@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CLERKSHIP"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('clerkship', 'read')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	if ($EVENT_ID) {
		$nameArray 	= clerkship_student_name($EVENT_ID);
		
		$query		= "SELECT *
						FROM `".CLERKSHIP_DATABASE."`.`events`, `".CLERKSHIP_DATABASE."`.`electives`, `".CLERKSHIP_DATABASE."`.`event_contacts`
						WHERE `".CLERKSHIP_DATABASE."`.`events`.`event_id` = ".$db->qstr($EVENT_ID)."
						AND `".CLERKSHIP_DATABASE."`.`events`.`event_id` = `".CLERKSHIP_DATABASE."`.`electives`.`event_id`
						AND `".CLERKSHIP_DATABASE."`.`events`.`event_id` = `".CLERKSHIP_DATABASE."`.`event_contacts`.`event_id`
						AND `".CLERKSHIP_DATABASE."`.`event_contacts`.`etype_id` = ".$db->qstr($ENTRADA_USER->getID());
		$event_info	= $db->GetRow($query);
		if ($event_info && $event_info["event_status"] != "published") {
			$query = "	SELECT `countries_id`, `prov_state`, `region_name`
						FROM `".CLERKSHIP_DATABASE."`.`regions`
						WHERE `region_id` = ".$db->qstr($event_info["region_id"]);
			$event_info["prov_state"] 	= "";
			$event_info["countries_id"] = 1;
			$event_info["city"] 		= "";
			if ($region_info = $db->GetRow($query)) {
				$event_info["prov_state"] = $region_info["prov_state"];
				$event_info["countries_id"] = $region_info["countries_id"];
				$event_info["city"] = $region_info["region_name"];
			}
			switch ($event_info["event_status"]) {
				case "approval" :
					$cancel			= "Cancel";
					$BREADCRUMB[]	= array("url" => ENTRADA_URL."/public/clerkship/electives?".replace_query(array("section" => "edit")), "title" => "Editing Elective");
					$header_output	= "<h1>Editing Elective</h1>\n";
				break;
				case "trash" :
					if ($STEP != 2) {
						$NOTICE++;
						$NOTICESTR[]	= "This elective has been rejected. <br /><br /> Modify it accordingly and resubmit it if you wish to have it approved.";
					}

					$resubmit		= true;
					$cancel			= "Cancel";
					$BREADCRUMB[]	= array("url" => ENTRADA_URL."/public/clerkship/electives?".replace_query(array("section" => "edit")), "title" => "Editing Elective");
					$header_output	= "<h1>Editing Elective</h1>\n";
				break;
				default:
					$cancel			= "Cancel";
					$BREADCRUMB[]	= array("url" => ENTRADA_URL."/public/clerkship/electives?".replace_query(array("section" => "edit")), "title" => "Editing Elective");
					$header_output	= "<h1>Editing Elective</h1>\n";
				break;
			}
			$student_name = get_account_data("firstlast", $ENTRADA_USER->getID());
			
			echo $header_output;
				
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
					 * Required field "department_id" / Department.
					 */
					if ((isset($_POST["department_id"])) && ($department_id = clean_input($_POST["department_id"], "int"))) {
						$PROCESSED["department_id"] = $department_id;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Department</strong> field is required.";
					}
		
					/**
					 * Required field "discipline_id" / Discipline.
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
					
					/**
					 * Required field "event_start" / Start Date .
					 */
                    $event_date = validate_calendar("Elective", "event", false);
                    if ((isset($event_date)) && ((int) $event_date)) {
                        $PROCESSED["event_start"]   = (int) $event_date;
                        $PROCESSED["event_finish"]  = $PROCESSED["event_start"] + (clean_input($_POST["event_finish_name"], array("int")) * ONE_WEEK) - 10800;
                        $start_stamp                = $PROCESSED["event_start"];
                        $end_stamp                  = $PROCESSED["event_finish"];
						$dateCheckQuery = "SELECT `event_title`, `event_start`, `event_finish`
											FROM `".CLERKSHIP_DATABASE."`.`events`, `".CLERKSHIP_DATABASE."`.`electives`, `".CLERKSHIP_DATABASE."`.`event_contacts`
											WHERE `".CLERKSHIP_DATABASE."`.`events`.`event_id` != ".$db->qstr($EVENT_ID)."
											AND `".CLERKSHIP_DATABASE."`.`events`.`event_id` = `".CLERKSHIP_DATABASE."`.`electives`.`event_id`
											AND `".CLERKSHIP_DATABASE."`.`events`.`event_id` = `".CLERKSHIP_DATABASE."`.`event_contacts`.`event_id`
											AND `".CLERKSHIP_DATABASE."`.`event_contacts`.`etype_id` = ".$db->qstr($student_id)."
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
						} else {
							$weekTotals = clerkship_get_elective_weeks($ENTRADA_USER->getID(), $EVENT_ID);
							$totalWeeks = $weekTotals["approval"] + $weekTotals["approved"];
							
							if ($totalWeeks + clean_input($_POST["event_finish_name"], array("int")) > $CLERKSHIP_REQUIRED_WEEKS) {
								$ERROR++;
								$ERRORSTR[] = "The <strong>Weeks</strong> field contains too large a number as this combined with the other electives you have in the system
								(both approved and awaiting approval) exceeds the maximum number of weeks allowed (".$CLERKSHIP_REQUIRED_WEEKS."). Please use the Page Feedback link to 
								contact the Undergraduate office if you need help resolving this issue.";
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
						$EVENT["event_start"]				= $PROCESSED["event_start"];
						$EVENT["event_finish"]				= $PROCESSED["event_finish"];
						$EVENT["event_type"]				= "elective";
						$EVENT["event_status"]				= "approval";
						$EVENT["modified_last"]				= $PROCESSED["updated_date"];
						$EVENT["modified_by"]				= $PROCESSED["updated_by"];
						
						if ($db->AutoExecute(CLERKSHIP_DATABASE.".events", $EVENT, "UPDATE", "`event_id` = ".$db->qstr($EVENT_ID))) {
							$url	= ENTRADA_URL."/clerkship";
							$msg	= "You will now be redirected to the clerkship index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
							
							$ELECTIVE = $PROCESSED;
							
							if ($db->AutoExecute(CLERKSHIP_DATABASE.".electives", $ELECTIVE, "UPDATE", "`event_id` = ".$db->qstr($EVENT_ID))) {
                                add_statistic("clerkship_electives", "edit", "event_id", $EVENT_ID, $ENTRADA_USER->getID());
								$SUCCESS++;
								$SUCCESSSTR[]  	= "You have successfully edited this <strong>".html_encode($PROCESSED["geo_location"])."</strong> elective in the system.<br /><br />".$msg;
								$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000)";
								
								application_log("success", "New elective [".$EVENT["event_title"]."] edited in the system.");
							} else {
								$ERROR++;
								$ERRORSTR[] = "There was a problem editing this elective in the system. The MEdTech Unit was informed of this error; please try again later.";
			
								application_log("error", "There was an error editing a clerkship elective. Database said: ".$db->ErrorMsg());
							}
						} else {
							$ERROR++;
							$ERRORSTR[] = "There was a problem editing this elective into the system. The MEdTech Unit was informed of this error; please try again later.";
		
							application_log("error", "There was an error editing a clerkship elective. Database said: ".$db->ErrorMsg());
						}
					} else {
						$STEP = 1;
					}
				break;
				case 1 :
				default :
					$PROCESSED = $event_info;
					continue;
				break;
			}
		
			// Display Content
			switch ($STEP) {
				case 2 :
					if ($SUCCESS) {
						echo display_success();
					}
					
					if ($NOTICE) {
						echo display_notice();
					}
					
					if ($ERROR) {
						echo display_error();
					}
				break;
				case 1 :
				default :
					
					$HEAD[] 		= "<link href=\"".ENTRADA_URL."/javascript/calendar/css/xc2_default.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
					$HEAD[] 		= "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/config/xc2_default.js\"></script>\n";
					$HEAD[] 		= "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/script/xc2_inpage.js\"></script>\n";
					$HEAD[]			= "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/picklist.js\"></script>\n";
					$HEAD[]			= "<script type=\"text/javascript\">
					function checkInternational (flag) {
						if (flag == \"true\") {
							$('international_notice').style.display = 'block';
						} else {
							$('international_notice').style.display = 'none';
						}
					}
					
					function showOther() {
						if (\$F('schools_id') == '99999') {
							$('other_host_school').show();
						} else {
							$('other_host_school').hide();
						}
					}
					
					function changeDurationMessage() {
						var value = $('event_start').value;
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
							default:
								var days = 13;
								var weekText = ' weeks';
								break;
						}
						newDate.setDate(newDate.getDate()+days);
						newDate = toCalendarDate(newDate);
						$('auto_end_date').innerHTML = '&nbsp;&nbsp;&nbsp;Ending in '+\$F('event_finish')+weekText+' on ' +newDate;
						$('event_start').value = date;
					}
					
					function AjaxFunction(cat_id) {	
						url='".webservice_url("clerkship_department")."?cat_id=' + cat_id + '&dept_id=".(isset($_POST["department_id"]) ? clean_input($_POST["department_id"], array("int")) : $PROCESSED["department_id"])."&disabled=';
				    	new Ajax.Updater($('department_category'), url, 
							{ 
								method:'get'
							});
					}
					
					var updater = null;
					function provStateFunction(countries_id) {
						var url='".webservice_url("clerkship_prov")."';
						url=url+'?countries_id='+countries_id+'&prov_state=".rawurlencode((isset($_POST["prov_state"]) ? clean_input($_POST["prov_state"], array("notags", "trim")) : $PROCESSED["prov_state"]))."';
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
		
					$ONLOAD[] = "showOther()";
					$ONLOAD[] = "AjaxFunction(\$F($('editElectiveForm')['category_id']))";
					$ONLOAD[] = "provStateFunction(\$F($('editElectiveForm')['countries_id']))";
					$ONLOAD[] = "setMaxLength()";
					$ONLOAD[] = "changeDurationMessage()";
					
					$LASTUPDATED = $event_info["updated_date"];
					
					if ($ERROR) {
						echo display_error();
					}
					
					if ($NOTICE) {
						echo display_notice();
					}
					if (!isset($resubmit) || !$resubmit) {
					?>
					<div class="display-notice" style="vertical-align: middle; padding: 15px;">
						<strong>Please Note:</strong> This elective has not yet been reviewed, as such you can cancel this request. <input type="button" class="btn btn-danger" value="Cancel Request" style="margin-left: 15px" onclick="window.location='<?php echo ENTRADA_URL; ?>/clerkship/electives?section=delete&id=<?php echo $EVENT_ID; ?>'" />
					</div>
					<?php
					}
					?>
					<form id="editElectiveForm" action="<?php echo ENTRADA_URL; ?>/clerkship/electives?<?php echo replace_query(array("step" => 2)); ?>" method="post">
					<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Editing Elective">
					<colgroup>
						<col style="width: 3%" />
						<col style="width: 20%" />
						<col style="width: 77%" />
					</colgroup>
					<tfoot>
						<tr>
							<td colspan="3" style="padding-top: 25px">
								<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
								<tr>
									<td style="width: 25%; text-align: left">
										<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/clerkship'" />
									</td>
									<td style="width: 75%; text-align: right">
									<?php
									if (!isset($resubmit) || !$resubmit) {
										echo '<input type="submit" class="btn btn-primary" value="Save" />';
									} else if ($resubmit) {
										echo '<input type="submit" class="btn btn-primary" value="Resubmit" />';
									} else {
										echo '&nbsp;';
									}
									?>
								</tr>
								</table>
							</td>
						</tr>
					</tfoot>
					<tbody>
					<tr>
						<td colspan="3"><h2>Elective Details</h2></td>
					</tr>
					<tr>
						<td></td>
						<td valign="top"><label for="geo_location" class="form-required">Geographic Location</label></td>
						<td style="vertical-align: top">
						<input type="radio" name="geo_location" id="geo_location_national" onclick="checkInternational('false');" value="National"<?php echo (((!isset($PROCESSED["geo_location"])) || ((isset($PROCESSED["geo_location"])) && ($PROCESSED["geo_location"]) == "National")) ? " checked=\"checked\"" : ""); ?> /> <label for="geo_location_national">National</label><br />
						<input type="radio" name="geo_location" id="geo_location_international" onclick="checkInternational('true');" value="International"<?php echo (((isset($PROCESSED["geo_location"])) && $PROCESSED["geo_location"] == "International") ? " checked=\"checked\"" : ""); ?> /> <label for="geo_location_international">International</label>
						<div id="international_notice" class="display-notice" style="display: none">
							<strong>Important Note:</strong> You must allow 12 weeks for processing of international electives.
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
							$query	= "SELECT * FROM `".CLERKSHIP_DATABASE."`.`categories` WHERE `category_type` = ".$db->qstr($CLERKSHIP_CATEGORY_TYPE_ID)." AND `category_name` = ".$db->qstr("Class of ".$nameArray["role"]);
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
							<div id="department_category">Please select an <strong>Elective Period</strong> from above first.</div>
						</td>
					</tr>
					<tr>
						<td></td>
						<td><label for="discipline_id" class="form-required">Elective Discipline</label></td>
						<td>
							<?php
							$discipline = clerkship_fetch_disciplines();
							if ((is_array($discipline)) && (count($discipline) > 0)) {
								echo "<select id=\"discipline_id\" name=\"discipline_id\"  style=\"width: 256px\">\n";
								echo "<option value=\"0\"".((!isset($PROCESSED["discipline_id"])) ? " selected=\"selected\"" : "").">-- Select Discipline --</option>\n";
								foreach ($discipline as $value) {
									echo "<option value=\"".(int) $value["discipline_id"]."\"".(($PROCESSED["discipline_id"] == $value["discipline_id"]) ? " selected=\"selected\"" : "").">".html_encode($value["discipline"])."</option>\n";
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
						<input type="text" id="sub_discipline"  name="sub_discipline" value="<?php echo html_encode($PROCESSED["sub_discipline"]); ?>" maxlength="64" style="width: 250px" />
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
							$clerkship_medical_schools = clerkship_fetch_schools();
							if ((is_array($clerkship_medical_schools)) && (count($clerkship_medical_schools) > 0)) {
								echo "<select id=\"schools_id\"  name=\"schools_id\" style=\"width: 256px\" onchange=\"showOther();\">\n";
								echo "<option value=\"0\"".((!isset($PROCESSED["schools_id"])) ? " selected=\"selected\"" : "").">-- Select Host School --</option>\n";
								foreach ($clerkship_medical_schools as $value) {
									echo "<option value=\"".(int) $value["schools_id"]."\"".(($PROCESSED["schools_id"] == $value["schools_id"]) ? " selected=\"selected\"" : "").">".html_encode($value["school_title"])."</option>\n";
								}
								echo "<option value=\"99999\"".($PROCESSED["schools_id"] == "99999" ? " selected=\"selected\"" : "").">-- Other (Specify) --</option>\n";
								echo "</select>\n";
							} else {
								echo "<input type=\"hidden\" id=\"schools_id\" name=\"schools_id\" value=\"0\" />\n";
								echo "Host school information is not currently available.\n";
							}
							?>
						</td>
					</tr>
				</tbody>
				<tbody id="other_host_school" style="display: none">
					<tr>
						<td></td>
						<td style="vertical-align: top"><label id="other_label" for="other_medical_school" class="form-required">Other</label></td>
						<td style="vertical-align: top">
							<input type="text" id="other_medical_school" name="other_medical_school" value="<?php echo html_encode($PROCESSED["other_medical_school"]); ?>" maxlength="64" style="width: 250px;" />
							<span class="content-small">(<strong>Example:</strong> Stanford University)</span>
						</td>
					</tr>
				</tbody>
				<tbody>
					<tr>
						<td colspan="3">&nbsp;</td>
					</tr>
					<?php 
						echo generate_calendar("event", "Start Date", true, ((isset($PROCESSED["event_start"])) ? $PROCESSED["event_start"] : 0), false, false, false);
					?>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="event_finish" class="form-required">Elective Weeks</label></td>
						<td style="vertical-align: top">
						<?php
							$duration = ceil((isset($PROCESSED["event_finish"]) && $PROCESSED["event_finish"] && isset($PROCESSED["event_start"]) && $PROCESSED["event_start"] ? (($PROCESSED["event_finish"] - $PROCESSED["event_start"]) / 604800) : 0));
							echo "<select id=\"event_finish\" name=\"event_finish_name\" style=\"width: 10%\" onchange=\"changeDurationMessage();\">\n";
							if ($event_info["event_status"] == "published") {
								$start = 1;
							} else {
								$start = 2;
							}
							for($i=$start; $i<=4; $i++)  {
								echo "<option value=\"".$i."\"".(($i == $duration) ? " selected=\"selected\"" : "").">".$i."</option>\n";
							}
							echo "</select>";
							echo "<span id=\"auto_end_date\" class=\"content-small\"></span>";
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
							<textarea id="objective" name="objective" class="expandable" style="width: 95%; height: 60px" cols="50" rows="5" maxlength="300"><?php echo ((isset($PROCESSED["objective"])) ? html_encode($PROCESSED["objective"]) : ""); ?></textarea>
							<div id="planned_note" class="content-small" style="display: block">
								<strong><br>Note:</strong> Please provide a narrative of your educational objectives (what you hope to achieve while on the elective).
							</div>
						</td>
					</tr>
					<tr>
					<td colspan="3" style="padding-top: 15px">
						<h2>Site Details</h2>
					</td>
					</tr>
					<tr>
						<td></td>
						<td><label for="preceptor_prefix" class="form-nrequired">Preceptor Prefix</label></td>
						<td>
							<select id="preceptor_prefix" name="preceptor_prefix" style="width: 55px; vertical-align: middle; margin-right: 5px">
								<option value=""<?php echo ((!$PROCESSED["preceptor_prefix"]) ? " selected=\"selected\"" : ""); ?>></option>
								<?php
								if ((@is_array($PROFILE_NAME_PREFIX)) && (@count($PROFILE_NAME_PREFIX))) {
									foreach ($PROFILE_NAME_PREFIX as $key => $prefix) {
										echo "<option value=\"".html_encode($prefix)."\"".(($PROCESSED["preceptor_prefix"] == $prefix) ? " selected=\"selected\"" : "").">".html_encode($prefix)."</option>\n";
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
						<input type="text" id="preceptor_first_name" name="preceptor_first_name" value="<?php echo html_encode($PROCESSED["preceptor_first_name"]); ?>" maxlength="50" style="width: 250px"" />
						</td>
					</tr>
					<tr>
						<td></td>
						<td><label for="preceptor_last_name" class="form-required">Preceptor Last Name</label></td>
						<td>
						<input type="text" id="preceptor_last_name" name="preceptor_last_name" value="<?php echo html_encode($PROCESSED["preceptor_last_name"]); ?>" maxlength="50" style="width: 250px"" />
						</td>
					</tr>
					<tr>
						<td colspan="3">&nbsp;</td>
					</tr>
					<tr>
						<td></td>
						<td><label for="countries_id" class="form-required">Country</label></td>
						<td>
							<?php
                            $countries = fetch_countries();
                            if ((is_array($countries)) && (count($countries) > 0)) {
                                echo "<select id=\"countries_id\" name=\"countries_id\" style=\"width: 256px\" onchange=\"provStateFunction(this.value);\">\n";
                                echo "<option value=\"0\"".((!isset($PROCESSED["countries_id"])) ? " selected=\"selected\"" : "").">-- Select Country --</option>\n";
                                foreach ($countries as $value) {
                                    echo "<option value=\"".(int) $value["countries_id"]."\"".(($PROCESSED["countries_id"] == $value["countries_id"]) ? " selected=\"selected\"" : "").">".html_encode($value["country"])."</option>\n";
                                }
                                echo "</select>\n";
                            } else {
                                echo "<input type=\"hidden\" id=\"countries_id\" name=\"countries_id\" value=\"0\" />\n";
                                echo "Country information not currently available.\n";
                            }
							?>
						</td>
					</tr>
					<tr>
						<td></td>
						<td><label id="prov_state_label" for="prov_state_div" class="form-required">Province / State</label></td>
						<td>
							<div id="prov_state_div">Please select a <strong>Country</strong> from above first.</div>
						</td>
					</tr>
					<tr>
						<td></td>
						<td><label for="city" class="form-required">City</label></td>
						<td>
							<input type="text" id="city" name="city" size="100" autocomplete="off" style="width: 250px; vertical-align: middle" value="<?php echo $PROCESSED["city"]; ?>"/>
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
						<td><label for="address" class="form-required">Address</label></td>
						<td>
						<input type="text" id="address" name="address" value="<?php echo html_encode($PROCESSED["address"]); ?>" maxlength="250" style="width: 250px" />
						</td>
					</tr>
					<tr>
						<td></td>
						<td><label for="postal_zip_code" class="form-nrequired">Postal / Zip Code</label></td>
						<td>
						<input type="text" id="postal_zip_code" name="postal_zip_code" value="<?php echo html_encode($PROCESSED["postal_zip_code"]); ?>" maxlength="20" style="width: 250px"" />
						</td>
					</tr>
					<tr>
					<td colspan="3">&nbsp;</td>
				</tr>
				<tr>
						<td></td>
						<td><label for="phone" class="form-nrequired">Phone</label></td>
						<td>
						<input type="text" id="phone" name="phone" value="<?php echo html_encode($PROCESSED["phone"]); ?>" maxlength="25" style="width: 250px"" />
						</td>
					</tr>
					<tr>
						<td></td>
						<td><label for="fax" class="form-nrequired">Fax</label></td>
						<td>
						<input type="text" id="fax" name="fax" value="<?php echo html_encode($PROCESSED["fax"]); ?>" maxlength="25" style="width: 250px"" />
						</td>
					</tr>
					<tr>
						<td></td>
						<td><label for="email" class="form-required">Email</label></td>
						<td>
					<input type="text" id="email" name="email" value="<?php echo html_encode($PROCESSED["email"]); ?>" maxlength="150" style="width: 250px"" />
					</td>
				</tr>
				<tr>
					<td></td>
					<td colspan="2">
						<div id="disclosure" name="disclosure" class="content-small" style="padding-top: 15px">
							<strong>DISCLOSURE:</strong> By clicking the Submit button below, I hereby certify that there is no conflict of interest which may result in the submission of a biased evaluation ( i.e. family member, close personal friend, etc.). I also confirm that this elective has already been approved by my preceptor.
						</div>
						</td>
					</tr>
			</tbody>
					</table>
					</form>
					<?php
				break;
			}
		} else {
			if($event_info["event_status"] == "published") {
				$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

				$ERROR++;
				$ERRORSTR[]	= "This elective has already been approved, you cannot edit it. <br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";
			
				echo display_error();
			
				application_log("error", "Error, invalid Event ID [".$EVENT_ID."] supplied for editing a clerkship elective in module [".$MODULE."].");
			} else {
				$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";
	
				$ERROR++;
				$ERRORSTR[]	= "This Event ID is not valid<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";
			
				echo display_error();
			
				application_log("error", "Error, invalid Event ID [".$EVENT_ID."] supplied for editing a clerkship elective in module [".$MODULE."].");
			}
		}
	} else {
		$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

		$ERROR++;
		$ERRORSTR[]	= "You must provide a valid Event ID<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";
	
		echo display_error();
	
		application_log("error", "Error, invalid Event ID [".$EVENT_ID."] supplied for clerkship elective in module [".$MODULE."].");
	}
}