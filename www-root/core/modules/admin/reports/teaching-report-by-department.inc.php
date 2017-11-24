<?php
/**
 * Online Course Resources [Pre-Clerkship]
 * Module:	Reports
 * Area:		Admin
 * @author Unit: Medical Education Technology Unit
 * @author Director: Dr. Benjamin Chen <bhc@post.queensu.ca>
 * @author Developer: Matt Simpson <simpson@post.queensu.ca>
 * @version 3.0
 * @copyright Copyright 2007 Queen's University, MEdTech Unit
 *
 * $Id: teaching-report-by-department-workforce.inc.php 957 2009-12-18 14:14:32Z simpson $
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_REPORTS"))) {
	exit;
} else if ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
		header("Location: ".ENTRADA_URL);
		exit;
	} else if (!$ENTRADA_ACL->amIAllowed('report', 'read', false)) {
			$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

			$ERROR++;
			$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

			echo display_error();

			application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]." and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
		} else {
			
			ini_set("max_execution_time", 120);
			$BREADCRUMB[]	= array("url" => "", "title" => "Faculty Teaching Report By Department" );

			$PROCESSED = array();
			$PROCESSED["show_all_teachers"]	= true;

			if ((isset($_POST["update"])) && ((!isset($_POST["show_all_teachers"])) || ($_POST["show_all_teachers"] != "1"))) {
				$PROCESSED["show_all_teachers"] = false;
			}

			function display_half_days($convert = 0, $type = "lecture") {
				if ($convert = (int) $convert) {
					switch($type) {
						case "lecture" :
						case "lab" :
						case "exam" :
						case "interview" :
							// 2 HD's per session.
							$number = round(($convert * 2), 2);
						break;
						case "small_group" :
						case "review" :
						case "patient_contact" :
						case "symposium" :
						case "clerkship_seminar" :
						case "directed_learning" :
						case "observership" :
							// 1 HD's per session.
							$number = $convert;
						break;
						case "events" :
						default :
							// 2 HD's per hour.
//							$number =  round((round(($convert / 60), 2) * 2), 2);
							// 2 HD's per session.
							$number =  round(($convert * 2), 2);

						break;
					}

					return $number.(($number != 1) ? " HDs" : " HD");
				}

				return "";
			}
			?>
			<a name="top"></a>
			<div class="no-printing">
				<form action="<?php echo ENTRADA_URL; ?>/admin/reports?section=<?php echo $SECTION; ?>&step=2" method="post">
					<input type="hidden" name="update" value="1" />
					<table style="width: 100%" cellspacing="0" cellpadding="2" border="0">
						<colgroup>
							<col style="width: 3%" />
							<col style="width: 20%" />
							<col style="width: 77%" />
						</colgroup>
						<tbody>
							<tr>
								<td colspan="3"><h2>Report Options</h2></td>
							</tr>
								<?php echo generate_calendars("reporting", "Reporting Date", true, true, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"], true, true, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"]);?>
							<tr>
								<td colspan="3" style="padding-top: 15px">
									<input type="checkbox" id="show_all_teachers" name="show_all_teachers" value="1" style="vertical-align: middle"<?php echo (($PROCESSED["show_all_teachers"]) ? " checked=\"checked\"" : ""); ?> /> <label for="show_all_teachers" class="form-nrequired" style="vertical-align: middle">Display teachers in departments who are not currently teaching.</label>
								</td>
							</tr>
							<tr>
								<td colspan="3" style="text-align: right; padding-top: 10px"><input type="submit" class="btn btn-primary" value="Update Report" /></td>
							</tr>
						</tbody>
					</table>
				</form>
			</div>
			<?php
			if ($STEP == 2) {
				$int_use_cache		= true;
				$event_ids			= array();
				$report_results		= array();
				$no_staff_number	= array();
				$department_sidebar	= array();

				$default_na_name	= "Department";

				$query	= "	SELECT a.`id` AS `proxy_id`, a.`number` AS `staff_number`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, a.`email`
							FROM `".AUTH_DATABASE."`.`user_data` AS a
							JOIN `".AUTH_DATABASE."`.`user_access` AS b
							ON b.`user_id` = a.`id`
							AND b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
							AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
							AND b.`group` = 'faculty'
							ORDER BY `fullname`";
				if ($int_use_cache) {
					$results = $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
				} else {
					$results = $db->GetAll($query);
				}

				if ($results) {
					/*
					1	Lecture	Faculty member speaks to a whole group of students for the session. Ideally, the lecture is interactive, with brief student activities to apply learning within the talk or presentation. The focus, however, is on the faculty member speaking or presenting to a group of students.	1	0	NULL	NULL	1250877835	1
					6	Lab	In this session, practical learning, activity and demonstration take place, usually with specialized equipment, materials or methods and related to a class, or unit of teaching.	1	1	NULL	NULL	1250877835	1
					8	Small Group	In the session, students in small groups work on specific questions, problems, or tasks related to a topic or a case, using discussion and investigation. Faculty member facilitates. May occur in:
					11	Patient Contact Session	The focus of the session is on the patient(s) who will be present to answer students' and/or professor's questions and/or to offer a narrative about their life with a condition, or as a member of a specific population. Medical Science Rounds are one example.	1	4	NULL	NULL	1219434863	1
					13	Symposium / Student Presentation	For one or more hours, a variety of speakers, including students, present on topics to teach about current issues, research, etc.	1	6	NULL	NULL	1219434863	1
					15	Directed Independent Learning	Students work independently (in groups or on their own) outside of class sessions on specific tasks to acquire knowledge, and develop enquiry and critical evaluation skills, with time allocated into the timetable. Directed Independent Student Learning may include learning through interactive online modules, online quizzes, working on larger independent projects (such as Community Based Projects or Critical Enquiry), or completing reflective, research or other types of papers and reports. While much student independent learning is done on the students? own time, for homework, in this case, directed student time is built into the timetable as a specific session and linked directly to other learning in the course.	1	3	NULL	NULL	1219434863	1
					18	Review / Feedback Session	In this session faculty help students to prepare for future learning and assessment through de-briefing about previous learning in a quiz or assignment, through reviewing a week or more of learning, or through reviewing at the end of a course to prepare for summative examination.	1	5	NULL	NULL	1219434863	1
					20	Examination	Scheduled course examination time, including mid-term as well as final examinations. <strong>Please Note:</strong> These will be identified only by the Curricular Coordinators in the timetable.	1	7	NULL	NULL	1219434863	1
					23	Clerkship Seminars	Case-based, small-group sessions emphasizing more advanced and integrative topics. Students draw upon their clerkship experience with patients and healthcare teams to participate and interact with the faculty whose role is to facilitate the discussion.	1	8	NULL	NULL	1250878869	1
					24	Other	These are sessions that are not a part of the UGME curriculum but are recorded in MEdTech Central. Examples may be: Course Evaluation sessions, MD Management. NOTE: these will be identified only by the Curricular Coordinators in the timetable.	1	9	NULL	NULL	1250878869	1
					*/
					$report_results["courses"]["lecture"]			= array("total_events" => 0, "total_minutes" => 0, "events_calculated" => 0, "events_minutes" => 0);
					$report_results["courses"]["lab"]				= array("total_events" => 0, "total_minutes" => 0, "events_calculated" => 0, "events_minutes" => 0);
					$report_results["courses"]["small_group"]		= array("total_events" => 0, "total_minutes" => 0, "events_calculated" => 0, "events_minutes" => 0);
					$report_results["courses"]["patient_contact"]	= array("total_events" => 0, "total_minutes" => 0, "events_calculated" => 0, "events_minutes" => 0);
					$report_results["courses"]["symposium"]			= array("total_events" => 0, "total_minutes" => 0, "events_calculated" => 0, "events_minutes" => 0);
					$report_results["courses"]["directed_learning"]	= array("total_events" => 0, "total_minutes" => 0, "events_calculated" => 0, "events_minutes" => 0);
					$report_results["courses"]["review"]			= array("total_events" => 0, "total_minutes" => 0, "events_calculated" => 0, "events_minutes" => 0);
					$report_results["courses"]["exam"]				= array("total_events" => 0, "total_minutes" => 0, "events_calculated" => 0, "events_minutes" => 0);
					$report_results["courses"]["clerkship_seminar"]	= array("total_events" => 0, "total_minutes" => 0, "events_calculated" => 0, "events_minutes" => 0);
					$report_results["courses"]["events"]			= array("total_events" => 0, "total_minutes" => 0, "events_calculated" => 0, "events_minutes" => 0);

					foreach ($results as $result) {
						$department_id	= $default_na_name;
						$division_id	= "Division";

                        $query = "SELECT a.`department_title`
                                    FROM `".AUTH_DATABASE."`.`departments` AS a
                                    JOIN `".AUTH_DATABASE."`.`user_departments` AS b
                                    ON b.`dep_id` = a.`department_id`
                                    AND b.`user_id` = ".$db->qstr($result["proxy_id"]);
                        $dresult	= $db->GetRow($query);
                        if ($dresult) {
                            $department_id = $dresult["department_title"];
                            $division_id = $dresult["department_title"];
                        }

						$i = @count($report_results["departments"][$department_id][$division_id]["people"]);
						$report_results["departments"][$department_id][$division_id]["people"][$i]["fullname"]				= $result["fullname"];
						$report_results["departments"][$department_id][$division_id]["people"][$i]["number"]				= $result["staff_number"];
						$report_results["departments"][$department_id][$division_id]["people"][$i]["contributor"]			= false;
						$report_results["departments"][$department_id][$division_id]["people"][$i]["lecture"]				= array("total_events" => 0, "total_minutes" => 0);
						$report_results["departments"][$department_id][$division_id]["people"][$i]["lab"]					= array("total_events" => 0, "total_minutes" => 0);
						$report_results["departments"][$department_id][$division_id]["people"][$i]["small_group"]			= array("total_events" => 0, "total_minutes" => 0);
						$report_results["departments"][$department_id][$division_id]["people"][$i]["patient_contact"]		= array("total_events" => 0, "total_minutes" => 0);
						$report_results["departments"][$department_id][$division_id]["people"][$i]["symposium"]				= array("total_events" => 0, "total_minutes" => 0);
						$report_results["departments"][$department_id][$division_id]["people"][$i]["directed_learning"]		= array("total_events" => 0, "total_minutes" => 0);
						$report_results["departments"][$department_id][$division_id]["people"][$i]["review"]				= array("total_events" => 0, "total_minutes" => 0);
						$report_results["departments"][$department_id][$division_id]["people"][$i]["exam"]					= array("total_events" => 0, "total_minutes" => 0);
						$report_results["departments"][$department_id][$division_id]["people"][$i]["clerkship_seminar"]		= array("total_events" => 0, "total_minutes" => 0);
						$report_results["departments"][$department_id][$division_id]["people"][$i]["events"]				= array("total_events" => 0, "total_minutes" => 0);

						$query = "	SELECT a.`event_id`, a.`event_title`, a.`course_id`, c.`eventtype_id`, c.`duration` AS `segment_duration`
									FROM `events` AS a
									JOIN `event_contacts` AS b
									ON b.`event_id` = a.`event_id`
									JOIN `event_eventtypes` AS c
									ON c.`event_id` = a.`event_id`
									JOIN `courses` AS d
									ON d.`course_id` = a.`course_id`
									WHERE b.`proxy_id` = ".$db->qstr($result["proxy_id"])."
									AND (a.`event_start` BETWEEN ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"])." AND ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"]).")
									AND d.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation());
						$sresults	= $db->GetAll($query);
						if ($sresults) {
							$report_results["departments"][$department_id][$division_id]["people"][$i]["contributor"]	= true;

							foreach($sresults as $sresult) {
								if (!in_array($sresult["event_id"], $event_ids)) {
									$event_ids[]		= $sresult["event_id"];
									$increment_total	= true;
								} else {
									$increment_total	= false;
								}

								switch($sresult["eventtype_id"]) {
									case "1" :
										// Lecture
										$report_results["departments"][$department_id][$division_id]["people"][$i]["lecture"]["total_events"]	+= 1;
										$report_results["departments"][$department_id][$division_id]["people"][$i]["lecture"]["total_minutes"]		+= (int) $sresult["segment_duration"];

										if ($increment_total) {
											$report_results["courses"]["lecture"]["total_minutes"]	+= (int) $sresult["segment_duration"];
											$report_results["departments"][$department_id][$division_id]["courses"]["lecture"]["total_events"]	+= 1;
											$report_results["departments"][$department_id][$division_id]["courses"]["lecture"]["total_minutes"]	+= (int) $sresult["segment_duration"];
										}

										$report_results["courses"]["lecture"]["total_events"]		+= 1;
										$report_results["courses"]["lecture"]["events_calculated"]	+= 1;
										$report_results["courses"]["lecture"]["events_minutes"]		+= (int) $sresult["segment_duration"];
										$report_results["departments"][$department_id][$division_id]["courses"]["lecture"]["events_calculated"]	+= 1;
										$report_results["departments"][$department_id][$division_id]["courses"]["lecture"]["events_minutes"]		+= (int) $sresult["segment_duration"];
									break;
									case "6" :
										// Lab
										$report_results["departments"][$department_id][$division_id]["people"][$i]["lab"]["total_events"]	+= 1;
										$report_results["departments"][$department_id][$division_id]["people"][$i]["lab"]["total_minutes"]		+= (int) $sresult["segment_duration"];

										if ($increment_total) {
											$report_results["courses"]["lab"]["total_minutes"]	+= (int) $sresult["segment_duration"];
											$report_results["departments"][$department_id][$division_id]["courses"]["lab"]["total_events"]	+= 1;
											$report_results["departments"][$department_id][$division_id]["courses"]["lab"]["total_minutes"]	+= (int) $sresult["segment_duration"];
										}

										$report_results["courses"]["lab"]["total_events"]		+= 1;
										$report_results["courses"]["lab"]["events_calculated"]		+= 1;
										$report_results["courses"]["lab"]["events_minutes"]		+= (int) $sresult["segment_duration"];
										$report_results["departments"][$department_id][$division_id]["courses"]["lab"]["events_calculated"]		+= 1;
										$report_results["departments"][$department_id][$division_id]["courses"]["lab"]["events_minutes"]		+= (int) $sresult["segment_duration"];
									break;
									case "8" :
										// Small Group
										$report_results["departments"][$department_id][$division_id]["people"][$i]["small_group"]["total_events"]		+= 1;
										$report_results["departments"][$department_id][$division_id]["people"][$i]["small_group"]["total_minutes"]		+= (int) $sresult["segment_duration"];

										if ($increment_total) {
											$report_results["courses"]["small_group"]["total_minutes"]	+= (int) $sresult["segment_duration"];
											$report_results["departments"][$department_id][$division_id]["courses"]["small_group"]["total_events"]		+= 1;
											$report_results["departments"][$department_id][$division_id]["courses"]["small_group"]["total_minutes"]	+= (int) $sresult["segment_duration"];
										}

										$report_results["courses"]["small_group"]["total_events"]		+= 1;
										$report_results["courses"]["small_group"]["events_calculated"]		+= 1;
										$report_results["courses"]["small_group"]["events_minutes"]		+= (int) $sresult["segment_duration"];
										$report_results["departments"][$department_id][$division_id]["courses"]["small_group"]["events_calculated"]		+= 1;
										$report_results["departments"][$department_id][$division_id]["courses"]["small_group"]["events_minutes"]		+= (int) $sresult["segment_duration"];
									break;
									case "11" :
										// Patient Contact Session
										$report_results["departments"][$department_id][$division_id]["people"][$i]["patient_contact"]["total_events"]		+= 1;
										$report_results["departments"][$department_id][$division_id]["people"][$i]["patient_contact"]["total_minutes"]	+= (int) $sresult["segment_duration"];

										if ($increment_total) {
											$report_results["courses"]["patient_contact"]["total_minutes"]	+= (int) $sresult["segment_duration"];
											$report_results["departments"][$department_id][$division_id]["courses"]["patient_contact"]["total_events"]	+= 1;
											$report_results["departments"][$department_id][$division_id]["courses"]["patient_contact"]["total_minutes"]	+= (int) $sresult["segment_duration"];
										}

										$report_results["courses"]["patient_contact"]["total_events"]		+= 1;
										$report_results["courses"]["patient_contact"]["events_calculated"]	+= 1;
										$report_results["courses"]["patient_contact"]["events_minutes"]		+= (int) $sresult["segment_duration"];
										$report_results["departments"][$department_id][$division_id]["courses"]["patient_contact"]["events_calculated"]	+= 1;
										$report_results["departments"][$department_id][$division_id]["courses"]["patient_contact"]["events_minutes"]		+= (int) $sresult["segment_duration"];
									break;
									case "13" :
										// Symposium / Student Presentation
										$report_results["departments"][$department_id][$division_id]["people"][$i]["symposium"]["total_events"]		+= 1;
										$report_results["departments"][$department_id][$division_id]["people"][$i]["symposium"]["total_minutes"]	+= (int) $sresult["segment_duration"];

										if ($increment_total) {
											$report_results["courses"]["symposium"]["total_minutes"]	+= (int) $sresult["segment_duration"];
											$report_results["departments"][$department_id][$division_id]["courses"]["symposium"]["total_events"]	+= 1;
											$report_results["departments"][$department_id][$division_id]["courses"]["symposium"]["total_minutes"]	+= (int) $sresult["segment_duration"];
										}

										$report_results["courses"]["symposium"]["total_events"]		+= 1;
										$report_results["courses"]["symposium"]["events_calculated"]	+= 1;
										$report_results["courses"]["symposium"]["events_minutes"]		+= (int) $sresult["segment_duration"];
										$report_results["departments"][$department_id][$division_id]["courses"]["symposium"]["events_calculated"]	+= 1;
										$report_results["departments"][$department_id][$division_id]["courses"]["symposium"]["events_minutes"]		+= (int) $sresult["segment_duration"];
									break;
									case "15" :
										// Directed Independent Learning
										$report_results["departments"][$department_id][$division_id]["people"][$i]["directed_learning"]["total_events"]		+= 1;
										$report_results["departments"][$department_id][$division_id]["people"][$i]["directed_learning"]["total_minutes"]	+= (int) $sresult["segment_duration"];

										if ($increment_total) {
											$report_results["courses"]["directed_learning"]["total_minutes"]	+= (int) $sresult["segment_duration"];
											$report_results["departments"][$department_id][$division_id]["courses"]["directed_learning"]["total_events"]	+= 1;
											$report_results["departments"][$department_id][$division_id]["courses"]["directed_learning"]["total_minutes"]	+= (int) $sresult["segment_duration"];
										}

										$report_results["courses"]["directed_learning"]["total_events"]		+= 1;
										$report_results["courses"]["directed_learning"]["events_calculated"]	+= 1;
										$report_results["courses"]["directed_learning"]["events_minutes"]		+= (int) $sresult["segment_duration"];
										$report_results["departments"][$department_id][$division_id]["courses"]["directed_learning"]["events_calculated"]	+= 1;
										$report_results["departments"][$department_id][$division_id]["courses"]["directed_learning"]["events_minutes"]		+= (int) $sresult["segment_duration"];
									break;
									case "18" :
										// Review / Feedback Session
										$report_results["departments"][$department_id][$division_id]["people"][$i]["review"]["total_events"]		+= 1;
										$report_results["departments"][$department_id][$division_id]["people"][$i]["review"]["total_minutes"]	+= (int) $sresult["segment_duration"];

										if ($increment_total) {
											$report_results["courses"]["review"]["total_minutes"]	+= (int) $sresult["segment_duration"];
											$report_results["departments"][$department_id][$division_id]["courses"]["review"]["total_events"]	+= 1;
											$report_results["departments"][$department_id][$division_id]["courses"]["review"]["total_minutes"]	+= (int) $sresult["segment_duration"];
										}

										$report_results["courses"]["review"]["total_events"]		+= 1;
										$report_results["courses"]["review"]["events_calculated"]	+= 1;
										$report_results["courses"]["review"]["events_minutes"]		+= (int) $sresult["segment_duration"];
										$report_results["departments"][$department_id][$division_id]["courses"]["review"]["events_calculated"]	+= 1;
										$report_results["departments"][$department_id][$division_id]["courses"]["review"]["events_minutes"]		+= (int) $sresult["segment_duration"];
									break;
									case "20" :
										// Examination
										$report_results["departments"][$department_id][$division_id]["people"][$i]["exam"]["total_events"]		+= 1;
										$report_results["departments"][$department_id][$division_id]["people"][$i]["exam"]["total_minutes"]	+= (int) $sresult["segment_duration"];

										if ($increment_total) {
											$report_results["courses"]["exam"]["total_minutes"]	+= (int) $sresult["segment_duration"];
											$report_results["departments"][$department_id][$division_id]["courses"]["exam"]["total_events"]	+= 1;
											$report_results["departments"][$department_id][$division_id]["courses"]["exam"]["total_minutes"]	+= (int) $sresult["segment_duration"];
										}

										$report_results["courses"]["exam"]["total_events"]		+= 1;
										$report_results["courses"]["exam"]["events_calculated"]	+= 1;
										$report_results["courses"]["exam"]["events_minutes"]		+= (int) $sresult["segment_duration"];
										$report_results["departments"][$department_id][$division_id]["courses"]["exam"]["events_calculated"]	+= 1;
										$report_results["departments"][$department_id][$division_id]["courses"]["exam"]["events_minutes"]		+= (int) $sresult["segment_duration"];
									break;
									case "23" :
										// Clerkship Seminars
										$report_results["departments"][$department_id][$division_id]["people"][$i]["clerkship_seminar"]["total_events"]		+= 1;
										$report_results["departments"][$department_id][$division_id]["people"][$i]["clerkship_seminar"]["total_minutes"]	+= (int) $sresult["segment_duration"];

										if ($increment_total) {
											$report_results["courses"]["clerkship_seminar"]["total_minutes"]	+= (int) $sresult["segment_duration"];
											$report_results["departments"][$department_id][$division_id]["courses"]["clerkship_seminar"]["total_events"]	+= 1;
											$report_results["departments"][$department_id][$division_id]["courses"]["clerkship_seminar"]["total_minutes"]	+= (int) $sresult["segment_duration"];
										}

										$report_results["courses"]["clerkship_seminar"]["total_events"]		+= 1;
										$report_results["courses"]["clerkship_seminar"]["events_calculated"]	+= 1;
										$report_results["courses"]["clerkship_seminar"]["events_minutes"]		+= (int) $sresult["segment_duration"];
										$report_results["departments"][$department_id][$division_id]["courses"]["clerkship_seminar"]["events_calculated"]	+= 1;
										$report_results["departments"][$department_id][$division_id]["courses"]["clerkship_seminar"]["events_minutes"]		+= (int) $sresult["segment_duration"];
									break;
									case "24" :
									default :
										$report_results["departments"][$department_id][$division_id]["people"][$i]["events"]["total_events"]	+= 1;
										$report_results["departments"][$department_id][$division_id]["people"][$i]["events"]["total_minutes"]	+= (int) $sresult["segment_duration"];

										if ($increment_total) {
											$report_results["courses"]["events"]["total_minutes"]+= (int) $sresult["segment_duration"];
											$report_results["departments"][$department_id][$division_id]["courses"]["events"]["total_events"]	+= 1;
											$report_results["departments"][$department_id][$division_id]["courses"]["events"]["total_minutes"]+= (int) $sresult["segment_duration"];
										}

										$report_results["courses"]["events"]["total_events"]	+= 1;
										$report_results["courses"]["events"]["events_calculated"]	+= 1;
										$report_results["courses"]["events"]["events_minutes"]	+= (int) $sresult["segment_duration"];
										$report_results["departments"][$department_id][$division_id]["courses"]["events"]["events_calculated"]	+= 1;
										$report_results["departments"][$department_id][$division_id]["courses"]["events"]["events_minutes"]	+= (int) $sresult["segment_duration"];
									break;
								}
							}
						}

						$query = "SELECT * FROM `ar_internal_contributions`
									WHERE `proxy_id` = ".$db->qstr($result["proxy_id"])."
									AND `year_reported` BETWEEN 
										".date("Y", $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"])." 
											AND 
										".date("Y", $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"])."
									AND `role_description` IN ('Interviewer', 'Reader')";
						if ($int_use_cache) {
							$iresults	= $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
						} else {
							$iresults	= $db->GetAll($query);
						}
						if ($iresults) {
							foreach ($iresults as $iresult) {
								if ($iresult["role_description"] == "Interviewer") {
									$sessions = ceil((($iresult["time_commitment"] / 9) * 2));
								} else {
									$sessions = round(($iresult["time_commitment"] / 4));
								}
								if (!$sessions) {
									$sessions = 1;
								}
								$report_results["departments"][$department_id][$division_id]["people"][$i]["interview"]["total_events"]		+= $sessions;

								if ($increment_total) {
									$report_results["courses"]["interview"]["total_events"]													+= $sessions;
									$report_results["departments"][$department_id][$division_id]["courses"]["interview"]["total_events"]	+= $sessions;
								}

								$report_results["courses"]["interview"]["events_calculated"]												+= $sessions;
								$report_results["departments"][$department_id][$division_id]["courses"]["interview"]["events_calculated"]	+= $sessions;
							}
						}
						
						$query = "SELECT * FROM `student_observerships`
									WHERE `preceptor_proxy_id` = ".$db->qstr($result["proxy_id"])."
									AND `start` BETWEEN 
										".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"])." 
											AND 
										".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"]);
						if ($int_use_cache) {
							$oresults	= $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
						} else {
							$oresults	= $db->GetAll($query);
						}
						if ($oresults) {
							foreach ($oresults as $oresult) {
								if ($oresult["end"]) {
									$time_period = $oresult["end"] - $oresult["start"];
									$days = round(($time_period / 86400));
									if (!$days) {
										$days = 1;
									}
								} else {
									$days = 1;
								}
								$report_results["departments"][$department_id][$division_id]["people"][$i]["observership"]["total_events"]		+= $days;

								if ($increment_total) {
									$report_results["courses"]["observership"]["total_events"]													+= $days;
									$report_results["departments"][$department_id][$division_id]["courses"]["observership"]["total_events"]		+= $days;
								}

								$report_results["courses"]["observership"]["events_calculated"]													+= $days;
								$report_results["departments"][$department_id][$division_id]["courses"]["observership"]["events_calculated"]	+= $days;
							}
						}
					}
				}
				if (isset($report_results) && !empty($report_results)) {
					ksort($report_results["departments"]);

				$department_list = array_keys($report_results["departments"]);
				foreach($department_list as $department) {
					ksort($report_results["departments"][$department]);

					if (is_array($report_results["departments"][$department][$default_na_name])) {
						$tmp_array = $report_results["departments"][$department][$default_na_name];
						unset($report_results["departments"][$department][$default_na_name]);
						$report_results["departments"][$department][$default_na_name] = $tmp_array;
					}
				}

				if (is_array($report_results["departments"][$default_na_name])) {
					$tmp_array = $report_results["departments"][$default_na_name];
					unset($report_results["departments"][$default_na_name]);
					$report_results["departments"][$default_na_name] = $tmp_array;
				}

				echo "<h1>Faculty Teaching Report By Department (Workforce)</h1>";
				echo "<div class=\"content-small\" style=\"margin-bottom: 10px\">\n";
				echo "	<strong>Date Range:</strong> ".date(DEFAULT_DATE_FORMAT, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"])." <strong>to</strong> ".date(DEFAULT_DATE_FORMAT, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"]);
				echo "</div>";

				if ((is_array($report_results["departments"])) && (count($report_results["departments"]))) {
					$absolute_duration_total_lecture			= 0;
					$absolute_duration_total_lab				= 0;
					$absolute_duration_total_small_group		= 0;
					$absolute_duration_total_patient_contact	= 0;
					$absolute_duration_total_symposium			= 0;
					$absolute_duration_total_directed_learning	= 0;
					$absolute_duration_total_review				= 0;
					$absolute_duration_total_exam				= 0;
					$absolute_duration_total_clerkship_seminar	= 0;
					$absolute_duration_total_events				= 0;
					$absolute_duration_final_total				= 0;

					foreach($report_results["departments"] as $department_name => $department_entries) {
						$department_duration_total_lecture				= 0;
						$department_duration_total_lab					= 0;
						$department_duration_total_small_group			= 0;
						$department_duration_total_patient_contact		= 0;
						$department_duration_total_symposium			= 0;
						$department_duration_total_directed_learning	= 0;
						$department_duration_total_review				= 0;
						$department_duration_total_exam					= 0;
						$department_duration_total_clerkship_seminar	= 0;
						$department_duration_total_events				= 0;
						$department_duration_final_total				= 0;

						$department_session_total_lecture				= 0;
						$department_session_total_lab					= 0;
						$department_session_total_small_group			= 0;
						$department_session_total_patient_contact		= 0;
						$department_session_total_symposium				= 0;
						$department_session_total_directed_learning		= 0;
						$department_session_total_review				= 0;
						$department_session_total_exam					= 0;
						$department_session_total_interview				= 0;
						$department_session_total_observership			= 0;
						$department_session_total_clerkship_seminar		= 0;
						$department_session_total_events				= 0;
						$department_session_final_total					= 0;

						$department_link		= clean_input($department_name, "credentials");

						$department_sidebar[]	= array("department_name" => $department_name, "department_link" => "#".$department_link);

						echo "<div style=\"float: right\">\n";
						echo "	<a href=\"#top\">(top)</a>\n";
						echo "</div>\n";
						echo "<a name=\"".$department_link."\"></a>\n";
						echo "<h2>".html_encode($department_name)."</h2>";
						?>
						<table class="tableList" cellspacing="0" summary="Summary Report For <?php echo html_encode($department_name); ?>">
							<colgroup>
								<col class="modified" />
								<col class="general" />
								<col class="report-hours-lg" style="background-color: #F3F3F3" />
								<col class="report-hours-lg" />
								<col class="report-hours-lg" style="background-color: #F3F3F3" />
								<col class="report-hours-lg" />
								<col class="report-hours-lg" style="background-color: #F3F3F3" />
								<col class="report-hours-lg" />
								<col class="report-hours-lg" style="background-color: #F3F3F3" />
								<col class="report-hours-lg" />
								<col class="report-hours-lg" style="background-color: #F3F3F3" />
								<col class="report-hours-lg" />
								<col class="report-hours-lg" style="background-color: #F3F3F3" />
								<col class="report-hours-lg" />
								<col class="report-hours-lg" style="background-color: #F3F3F3" />
								<col class="report-hours-lg" />
							</colgroup>
							<thead>
								<tr>
									<td class="modified">&nbsp;</td>
									<td class="general">&nbsp;</td>
									<td class="report-hours-lg">Lecture</td>
									<td class="report-hours-lg">Lab</td>
									<td class="report-hours-lg">Small Group</td>
									<td class="report-hours-lg">Patient Contact</td>
									<td class="report-hours-lg">Symposium</td>
									<td class="report-hours-lg">Ind. Learning</td>
									<td class="report-hours-lg">Review Session</td>
									<td class="report-hours-lg">Examination</td>
									<td class="report-hours-lg">Interview</td>
									<td class="report-hours-lg">Observership</td>
									<td class="report-hours-lg">Clerk Seminars</td>
									<td class="report-hours-lg">Other Events</td>
									<td class="report-hours-lg">Total Hours</td>
									<td class="report-hours-lg">Total Sessions</td>
								</tr>
							</thead>
							<tbody>
								<?php
								if ((is_array($department_entries)) && (count($department_entries))) {
									foreach($department_entries as $division_name => $division_entries) {
										$division_duration_total_lecture			= 0;
										$division_duration_total_lab				= 0;
										$division_duration_total_small_group		= 0;
										$division_duration_total_patient_contact	= 0;
										$division_duration_total_symposium			= 0;
										$division_duration_total_directed_learning	= 0;
										$division_duration_total_review				= 0;
										$division_duration_total_exam				= 0;
										$division_duration_total_clerkship_seminar	= 0;
										$division_duration_total_events				= 0;
										$division_duration_final_total				= 0;
				
										$division_session_total_lecture				= 0;
										$division_session_total_lab					= 0;
										$division_session_total_small_group			= 0;
										$division_session_total_patient_contact		= 0;
										$division_session_total_symposium			= 0;
										$division_session_total_directed_learning	= 0;
										$division_session_total_review				= 0;
										$division_session_total_exam				= 0;
										$division_session_total_interview			= 0;
										$division_session_total_observership		= 0;
										$division_session_total_clerkship_seminar	= 0;
										$division_session_total_events				= 0;
										$division_session_final_total				= 0;

										echo "<tr>\n";
										echo "	<td colspan=\"15\" style=\"padding-left: 2%\"><strong>".html_encode($division_name)."</strong></td>\n";
										echo "</tr>\n";

										if ((is_array($division_entries["people"])) && (count($division_entries["people"]))) {
											$i = 0;
											foreach($division_entries["people"] as $result) {
												if (!$result["number"]) {
													$no_staff_number[] = array("fullname" => $result["fullname"], "email" => $result["email"]);
												}
												$duration_total					= 0;
												$duration_lecture				= ((isset($result["lecture"]["total_minutes"])) ? $result["lecture"]["total_minutes"] : 0);
												$duration_lab					= ((isset($result["lab"]["total_minutes"])) ? $result["lab"]["total_minutes"] : 0);
												$duration_small_group			= ((isset($result["small_group"]["total_minutes"])) ? $result["small_group"]["total_minutes"] : 0);
												$duration_patient_contact		= ((isset($result["patient_contact"]["total_minutes"])) ? $result["patient_contact"]["total_minutes"] : 0);
												$duration_symposium				= ((isset($result["symposium"]["total_minutes"])) ? $result["symposium"]["total_minutes"] : 0);
												$duration_directed_learning		= ((isset($result["directed_learning"]["total_minutes"])) ? $result["directed_learning"]["total_minutes"] : 0);
												$duration_review				= ((isset($result["review"]["total_minutes"])) ? $result["review"]["total_minutes"] : 0);
												$duration_exam					= ((isset($result["exam"]["total_minutes"])) ? $result["exam"]["total_minutes"] : 0);
												$duration_clerkship_seminar		= ((isset($result["clerkship_seminar"]["total_minutes"])) ? $result["clerkship_seminar"]["total_minutes"] : 0);
												$duration_events				= ((isset($result["events"]["total_minutes"])) ? $result["events"]["total_minutes"] : 0);

												$duration_total		= ($duration_lecture + $duration_lab + $duration_small_group + $duration_patient_contact + $duration_symposium + $duration_directed_learning + $duration_review + $duration_exam + $duration_clerkship_seminar + $duration_events);

												$session_total		= 0;
												$session_lecture				= ((isset($result["lecture"]["total_events"])) ? $result["lecture"]["total_events"] : 0);
												$session_lab					= ((isset($result["lab"]["total_events"])) ? $result["lab"]["total_events"] : 0);
												$session_small_group			= ((isset($result["small_group"]["total_events"])) ? $result["small_group"]["total_events"] : 0);
												$session_patient_contact		= ((isset($result["patient_contact"]["total_events"])) ? $result["patient_contact"]["total_events"] : 0);
												$session_symposium				= ((isset($result["symposium"]["total_events"])) ? $result["symposium"]["total_events"] : 0);
												$session_directed_learning		= ((isset($result["directed_learning"]["total_events"])) ? $result["directed_learning"]["total_events"] : 0);
												$session_review					= ((isset($result["review"]["total_events"])) ? $result["review"]["total_events"] : 0);
												$session_exam					= ((isset($result["exam"]["total_events"])) ? $result["exam"]["total_events"] : 0);
												$session_interview				= ((isset($result["interview"]["total_events"])) ? $result["interview"]["total_events"] : 0);
												$session_observership			= ((isset($result["observership"]["total_events"])) ? $result["observership"]["total_events"] : 0);
												$session_clerkship_seminar		= ((isset($result["clerkship_seminar"]["total_events"])) ? $result["clerkship_seminar"]["total_events"] : 0);
												$session_events					= ((isset($result["events"]["total_events"])) ? $result["events"]["total_events"] : 0);

												$session_total		= ($session_lecture + $session_lab + $session_small_group + $session_patient_contact + $session_symposium + $session_directed_learning + $session_review + $session_exam + $session_clerkship_seminar + $session_events);

												if (($PROCESSED["show_all_teachers"]) || ((bool) $result["contributor"])) {
													?>
													<tr <?php echo ((!$result["number"]) ? " class=\"np\"" : ""); ?>>
														<td class="modified<?php echo ((!(bool) $result["contributor"]) ? " np" : ""); ?>"><?php echo ((!$result["number"]) ? "<img src=\"".ENTRADA_URL."/images/checkbox-no-number.gif\" width=\"14\" height=\"14\" alt=\"No Number\" title=\"No Number\" />" : "&nbsp;"); ?></td>
														<td class="general<?php echo ((!(bool) $result["contributor"]) ? " np" : ""); ?>"><?php echo html_encode($result["fullname"]); ?></td>
														<td class="report-hours-lg<?php echo ((!(bool) $result["contributor"]) ? " np" : ""); ?>"><?php echo (($session_lecture) ? display_half_days($session_lecture, "lecture") : "&nbsp;"); ?></td>
														<td class="report-hours-lg<?php echo ((!(bool) $result["contributor"]) ? " np" : ""); ?>"><?php echo (($session_lab) ? display_half_days($session_lab, "lab") : "&nbsp;"); ?></td>
														<td class="report-hours-lg<?php echo ((!(bool) $result["contributor"]) ? " np" : ""); ?>"><?php echo (($session_small_group) ? display_half_days($session_small_group, "small_group") : "&nbsp;"); ?></td>
														<td class="report-hours-lg<?php echo ((!(bool) $result["contributor"]) ? " np" : ""); ?>"><?php echo (($session_patient_contact) ? display_half_days($session_patient_contact, "patient_contact") : "&nbsp;"); ?></td>
														<td class="report-hours-lg<?php echo ((!(bool) $result["contributor"]) ? " np" : ""); ?>"><?php echo (($session_symposium) ? display_half_days($session_symposium, "symposium") : "&nbsp;"); ?></td>
														<td class="report-hours-lg<?php echo ((!(bool) $result["contributor"]) ? " np" : ""); ?>"><?php echo (($session_directed_learning) ? display_half_days($session_directed_learning, "directed_learning") : "&nbsp;"); ?></td>
														<td class="report-hours-lg<?php echo ((!(bool) $result["contributor"]) ? " np" : ""); ?>"><?php echo (($session_review) ? display_half_days($session_review, "review") : "&nbsp;"); ?></td>
														<td class="report-hours-lg<?php echo ((!(bool) $result["contributor"]) ? " np" : ""); ?>"><?php echo (($session_exam) ? display_half_days($session_exam, "exam") : "&nbsp;"); ?></td>
														<td class="report-hours-lg<?php echo ((!(bool) $result["contributor"]) ? " np" : ""); ?>"><?php echo (($session_interview) ? display_half_days($session_interview, "interview") : "&nbsp;"); ?></td>
														<td class="report-hours-lg<?php echo ((!(bool) $result["contributor"]) ? " np" : ""); ?>"><?php echo (($session_observership) ? display_half_days($session_observership, "observership") : "&nbsp;"); ?></td>
														<td class="report-hours-lg<?php echo ((!(bool) $result["contributor"]) ? " np" : ""); ?>"><?php echo (($session_clerkship_seminar) ? display_half_days($session_clerkship_seminar, "clerkship_seminar") : "&nbsp;"); ?></td>
														<td class="report-hours-lg<?php echo ((!(bool) $result["contributor"]) ? " np" : ""); ?>"><?php echo (($session_events) ? display_half_days($session_events, "events") : "&nbsp;"); ?></td>
														<td class="report-hours-lg<?php echo ((!(bool) $result["contributor"]) ? " np" : ""); ?>"><?php echo (($duration_total) ? display_hours($duration_total) : "&nbsp;"); ?></td>
														<td class="report-hours-lg<?php echo ((!(bool) $result["contributor"]) ? " np" : ""); ?>"><?php echo (($session_total) ? $session_total : "&nbsp;"); ?></td>
													</tr>
													<?php
													$i++;
												}
											}
										}
										if ((is_array($division_entries["courses"])) && (count($division_entries["courses"]))) {
											$division_duration_total_lecture			= ((isset($division_entries["courses"]["lecture"]["events_minutes"])) ? $division_entries["courses"]["lecture"]["events_minutes"] : 0);
											$division_duration_total_lab				= ((isset($division_entries["courses"]["lab"]["events_minutes"])) ? $division_entries["courses"]["lab"]["events_minutes"] : 0);
											$division_duration_total_small_group		= ((isset($division_entries["courses"]["small_group"]["events_minutes"])) ? $division_entries["courses"]["small_group"]["events_minutes"] : 0);
											$division_duration_total_patient_contact	= ((isset($division_entries["courses"]["patient_contact"]["events_minutes"])) ? $division_entries["courses"]["patient_contact"]["events_minutes"] : 0);
											$division_duration_total_symposium			= ((isset($division_entries["courses"]["symposium"]["events_minutes"])) ? $division_entries["courses"]["symposium"]["events_minutes"] : 0);
											$division_duration_total_directed_learning	= ((isset($division_entries["courses"]["directed_learning"]["events_minutes"])) ? $division_entries["courses"]["directed_learning"]["events_minutes"] : 0);
											$division_duration_total_review				= ((isset($division_entries["courses"]["review"]["events_minutes"])) ? $division_entries["courses"]["review"]["events_minutes"] : 0);
											$division_duration_total_exam				= ((isset($division_entries["courses"]["exam"]["events_minutes"])) ? $division_entries["courses"]["exam"]["events_minutes"] : 0);
											$division_duration_total_clerkship_seminar	= ((isset($division_entries["courses"]["clerkship_seminar"]["events_minutes"])) ? $division_entries["courses"]["clerkship_seminar"]["events_minutes"] : 0);
											$division_duration_total_events				= ((isset($division_entries["courses"]["events"]["events_minutes"])) ? $division_entries["courses"]["events"]["events_minutes"] : 0);

											$division_session_total_lecture				= ((isset($division_entries["courses"]["lecture"]["events_calculated"])) ? $division_entries["courses"]["lecture"]["events_calculated"] : 0);
											$division_session_total_lab					= ((isset($division_entries["courses"]["lab"]["events_calculated"])) ? $division_entries["courses"]["lab"]["events_calculated"] : 0);
											$division_session_total_small_group			= ((isset($division_entries["courses"]["small_group"]["events_calculated"])) ? $division_entries["courses"]["small_group"]["events_calculated"] : 0);
											$division_session_total_patient_contact		= ((isset($division_entries["courses"]["patient_contact"]["events_calculated"])) ? $division_entries["courses"]["patient_contact"]["events_calculated"] : 0);
											$division_session_total_symposium			= ((isset($division_entries["courses"]["symposium"]["events_calculated"])) ? $division_entries["courses"]["symposium"]["events_calculated"] : 0);
											$division_session_total_directed_learning	= ((isset($division_entries["courses"]["directed_learning"]["events_calculated"])) ? $division_entries["courses"]["directed_learning"]["events_calculated"] : 0);
											$division_session_total_review				= ((isset($division_entries["courses"]["review"]["events_calculated"])) ? $division_entries["courses"]["review"]["events_calculated"] : 0);
											$division_session_total_exam				= ((isset($division_entries["courses"]["exam"]["events_calculated"])) ? $division_entries["courses"]["exam"]["events_calculated"] : 0);
											$division_session_total_interview			= ((isset($division_entries["courses"]["interview"]["events_calculated"])) ? $division_entries["courses"]["interview"]["events_calculated"] : 0);
											$division_session_total_observership		= ((isset($division_entries["courses"]["observership"]["events_calculated"])) ? $division_entries["courses"]["observership"]["events_calculated"] : 0);
											$division_session_total_clerkship_seminar	= ((isset($division_entries["courses"]["clerkship_seminar"]["events_calculated"])) ? $division_entries["courses"]["clerkship_seminar"]["events_calculated"] : 0);
											$division_session_total_events				= ((isset($division_entries["courses"]["events"]["events_calculated"])) ? $division_entries["courses"]["events"]["events_calculated"] : 0);
											
											$department_duration_total_lecture				+= $department_duration_total_lecture;
											$department_duration_total_lab					+= $department_duration_total_lab;
											$department_duration_total_small_group			+= $department_duration_total_small_group;
											$department_duration_total_patient_contact		+= $department_duration_total_patient_contact;
											$department_duration_total_symposium			+= $department_duration_total_symposium;
											$department_duration_total_directed_learning	+= $department_duration_total_directed_learning;
											$department_duration_total_review				+= $department_duration_total_review;
											$department_duration_total_exam					+= $department_duration_total_exam;
											$department_duration_total_clerkship_seminar	+= $department_duration_total_clerkship_seminar;
											$department_duration_total_events				+= $department_duration_total_events;
											
											$department_session_total_lecture				+= $division_session_total_lecture;
											$department_session_total_lab					+= $division_session_total_lab;
											$department_session_total_small_group			+= $division_session_total_small_group;
											$department_session_total_patient_contact		+= $division_session_total_patient_contact;
											$department_session_total_symposium				+= $division_session_total_symposium;
											$department_session_total_directed_learning		+= $division_session_total_directed_learning;
											$department_session_total_review				+= $division_session_total_review;
											$department_session_total_exam					+= $division_session_total_exam;
											$department_session_total_interview				+= $division_session_total_interview;
											$department_session_total_observership			+= $division_session_total_observership;
											$department_session_total_clerkship_seminar		+= $division_session_total_clerkship_seminar;
											$department_session_total_events				+= $division_session_total_events;
											
											$division_duration_final_total		= ($division_duration_total_lecture + $division_duration_total_lab + $division_duration_total_small_group + $division_duration_total_patient_contact + $division_duration_total_symposium + $division_duration_total_directed_learning + $division_duration_total_review + $division_duration_total_exam + $division_duration_total_clerkship_seminar + $division_duration_total_events);
											$division_session_final_total		= ($division_session_total_lecture + $division_session_total_lab + $division_session_total_small_group + $division_session_total_patient_contact + $division_session_total_symposium + $division_session_total_directed_learning + $division_session_total_review + $division_session_total_exam + $division_session_total_clerkship_seminar + $division_session_total_events);

											if ($division_duration_final_total && $division_session_final_total) {
												$department_duration_final_total	+= $division_duration_final_total;
												$department_session_final_total		+= $division_session_final_total;
												?>
												<tr>
													<td colspan="14"">&nbsp;</td>
												</tr>
												<tr class="modified" style="font-weight: normal">
													<td class="modified">&nbsp;</td>
													<td class="general"><?php echo html_encode($division_name); ?> Totals:</td>
													<td class="report-hours-lg"><?php echo (($division_session_total_lecture) ? display_half_days($division_session_total_lecture, "lecture") : "&nbsp;"); ?></td>
													<td class="report-hours-lg"><?php echo (($division_session_total_lab) ? display_half_days($division_session_total_lab, "lab") : "&nbsp;"); ?></td>
													<td class="report-hours-lg"><?php echo (($division_session_total_small_group) ? display_half_days($division_session_total_small_group, "small_group") : "&nbsp;"); ?></td>
													<td class="report-hours-lg"><?php echo (($division_session_total_patient_contact) ? display_half_days($division_session_total_patient_contact, "patient_contact") : "&nbsp;"); ?></td>
													<td class="report-hours-lg"><?php echo (($division_session_total_symposium) ? display_half_days($division_session_total_symposium, "symposium") : "&nbsp;"); ?></td>
													<td class="report-hours-lg"><?php echo (($division_session_total_directed_learning) ? display_half_days($division_session_total_directed_learning, "directed_learning") : "&nbsp;"); ?></td>
													<td class="report-hours-lg"><?php echo (($division_session_total_review) ? display_half_days($division_session_total_review, "review") : "&nbsp;"); ?></td>
													<td class="report-hours-lg"><?php echo (($division_session_total_exam) ? display_half_days($division_session_total_exam, "exam") : "&nbsp;"); ?></td>
													<td class="report-hours-lg"><?php echo (($division_session_total_interview) ? display_half_days($division_session_total_interview, "interview") : "&nbsp;"); ?></td>
													<td class="report-hours-lg"><?php echo (($division_session_total_observership) ? display_half_days($division_session_total_observership, "observership") : "&nbsp;"); ?></td>
													<td class="report-hours-lg"><?php echo (($division_session_total_clerkship_seminar) ? display_half_days($division_session_total_clerkship_seminar, "clerkship_seminar") : "&nbsp;"); ?></td>
													<td class="report-hours-lg"><?php echo (($division_session_total_events) ? display_half_days($division_session_total_events, "events") : "&nbsp;"); ?></td>
													<td class="report-hours-lg"><?php echo (($division_duration_final_total) ? display_hours($division_duration_final_total) : "&nbsp;"); ?></td>
													<td class="report-hours-lg"><?php echo (($division_session_final_total) ? $division_session_final_total : "&nbsp;"); ?></td>
												</tr>
											<?php
											}
										}
										echo "<tr>\n";
										echo "	<td colspan=\"14\">&nbsp;</td>\n";
										echo "</tr>\n";
									}
									?>
									<tr class="na" style="font-weight: bold">
										<td class="modified">&nbsp;</td>
										<td class="general"><?php echo html_encode($department_name); ?> Totals:</td>
										<td class="report-hours-lg"><?php echo (($department_session_total_lecture) ? display_half_days($department_session_total_lecture, "lecture") : "&nbsp;"); ?></td>
										<td class="report-hours-lg"><?php echo (($department_session_total_lab) ? display_half_days($department_session_total_lab, "lab") : "&nbsp;"); ?></td>
										<td class="report-hours-lg"><?php echo (($department_session_total_small_group) ? display_half_days($department_session_total_small_group, "small_group") : "&nbsp;"); ?></td>
										<td class="report-hours-lg"><?php echo (($department_session_total_patient_contact) ? display_half_days($department_session_total_patient_contact, "patient_contact") : "&nbsp;"); ?></td>
										<td class="report-hours-lg"><?php echo (($department_session_total_symposium) ? display_half_days($department_session_total_symposium, "symposium") : "&nbsp;"); ?></td>
										<td class="report-hours-lg"><?php echo (($department_session_total_directed_learning) ? display_half_days($department_session_total_directed_learning, "directed_learning") : "&nbsp;"); ?></td>
										<td class="report-hours-lg"><?php echo (($department_session_total_review) ? display_half_days($department_session_total_review, "review") : "&nbsp;"); ?></td>
										<td class="report-hours-lg"><?php echo (($department_session_total_exam) ? display_half_days($department_session_total_exam, "exam") : "&nbsp;"); ?></td>
										<td class="report-hours-lg"><?php echo (($department_session_total_interview) ? display_half_days($department_session_total_interview, "interview") : "&nbsp;"); ?></td>
										<td class="report-hours-lg"><?php echo (($department_session_total_observership) ? display_half_days($department_session_total_observership, "observership") : "&nbsp;"); ?></td>
										<td class="report-hours-lg"><?php echo (($department_session_total_clerkship_seminar) ? display_half_days($department_session_total_clerkship_seminar, "clerkship_seminar") : "&nbsp;"); ?></td>
										<td class="report-hours-lg"><?php echo (($department_session_total_events) ? display_half_days($department_session_total_events, "events") : "&nbsp;"); ?></td>
										<td class="report-hours-lg"><?php echo (($department_duration_final_total) ? display_hours($department_duration_final_total) : "&nbsp;"); ?></td>
										<td class="report-hours-lg"><?php echo (($department_session_final_total) ? $department_session_final_total : "&nbsp;"); ?></td>
									</tr>
									<?php
									$absolute_duration_total_lecture			+= $department_duration_total_lecture;
									$absolute_duration_total_lab				+= $department_duration_total_lab;
									$absolute_duration_total_small_group		+= $department_duration_total_small_group;
									$absolute_duration_total_patient_contact	+= $department_duration_total_patient_contact;
									$absolute_duration_total_symposium			+= $department_duration_total_symposium;
									$absolute_duration_total_directed_learning	+= $department_duration_total_directed_learning;
									$absolute_duration_total_review				+= $department_duration_total_review;
									$absolute_duration_total_exam				+= $department_duration_total_exam;
									$absolute_duration_total_clerkship_seminar	+= $department_duration_total_clerkship_seminar;
									$absolute_duration_total_events				+= $department_duration_total_events;
									$absolute_duration_final_total				+= $department_duration_final_total;
									
									$absolute_session_total_lecture				+= $department_session_total_lecture;
									$absolute_session_total_lab					+= $department_session_total_lab;
									$absolute_session_total_small_group			+= $department_session_total_small_group;
									$absolute_session_total_patient_contact		+= $department_session_total_patient_contact;
									$absolute_session_total_symposium			+= $department_session_total_symposium;
									$absolute_session_total_directed_learning	+= $department_session_total_directed_learning;
									$absolute_session_total_review				+= $department_session_total_review;
									$absolute_session_total_exam				+= $department_session_total_exam;
									$absolute_session_total_interview			+= $department_session_total_interview;
									$absolute_session_total_observership		+= $department_session_total_observership;
									$absolute_session_total_clerkship_seminar	+= $department_session_total_clerkship_seminar;
									$absolute_session_total_events				+= $department_session_total_events;
									$absolute_session_final_total				+= $department_session_final_total;
								}
								?>
						</tbody>
					</table>
					<br />
					<?php
					}
				}
				?>
				<table class="tableList" cellspacing="0" summary="Total Report Summary">
					<colgroup>
						<col class="modified" />
						<col class="general" />
						<col class="report-hours-lg" style="background-color: #F3F3F3" />
						<col class="report-hours-lg" />
						<col class="report-hours-lg" style="background-color: #F3F3F3" />
						<col class="report-hours-lg" />
						<col class="report-hours-lg" style="background-color: #F3F3F3" />
						<col class="report-hours-lg" />
						<col class="report-hours-lg" style="background-color: #F3F3F3" />
						<col class="report-hours-lg" />
						<col class="report-hours-lg" style="background-color: #F3F3F3" />
						<col class="report-hours-lg" />
						<col class="report-hours-lg" style="background-color: #F3F3F3" />
						<col class="report-hours-lg" />
						<col class="report-hours-lg" style="background-color: #F3F3F3" />
						<col class="report-hours-lg" />
					</colgroup>
					<thead>
						<tr>
							<td class="modified">&nbsp;</td>
							<td class="general">&nbsp;</td>
							<td class="report-hours-lg">Lecture</td>
							<td class="report-hours-lg">Lab</td>
							<td class="report-hours-lg">Small Group</td>
							<td class="report-hours-lg">Patient Contact</td>
							<td class="report-hours-lg">Symposium</td>
							<td class="report-hours-lg">Ind. Learning</td>
							<td class="report-hours-lg">Review Session</td>
							<td class="report-hours-lg">Examination</td>
							<td class="report-hours-lg">Interview</td>
							<td class="report-hours-lg">Observership</td>
							<td class="report-hours-lg">Clerk Seminars</td>
							<td class="report-hours-lg">Other Events</td>
							<td class="report-hours-lg">Total Hours</td>
							<td class="report-hours-lg">Total Sessions</td>
						</tr>
					</thead>
					<tbody>
						<?php
						if ((is_array($report_results["courses"])) && (count($report_results["courses"]))) {
							$duration_total_lecture				= ((isset($report_results["courses"]["lecture"]["events_minutes"])) ? $report_results["courses"]["lecture"]["events_minutes"] : 0);
							$duration_total_lab					= ((isset($report_results["courses"]["lab"]["events_minutes"])) ? $report_results["courses"]["lab"]["events_minutes"] : 0);
							$duration_total_small_group			= ((isset($report_results["courses"]["small_group"]["events_minutes"])) ? $report_results["courses"]["small_group"]["events_minutes"] : 0);
							$duration_total_patient_contact		= ((isset($report_results["courses"]["patient_contact"]["events_minutes"])) ? $report_results["courses"]["patient_contact"]["events_minutes"] : 0);
							$duration_total_symposium			= ((isset($report_results["courses"]["symposium"]["events_minutes"])) ? $report_results["courses"]["symposium"]["events_minutes"] : 0);
							$duration_total_directed_learning	= ((isset($report_results["courses"]["directed_learning"]["events_minutes"])) ? $report_results["courses"]["directed_learning"]["events_minutes"] : 0);
							$duration_total_review				= ((isset($report_results["courses"]["review"]["events_minutes"])) ? $report_results["courses"]["review"]["events_minutes"] : 0);
							$duration_total_exam				= ((isset($report_results["courses"]["exam"]["events_minutes"])) ? $report_results["courses"]["exam"]["events_minutes"] : 0);
							$duration_total_clerkship_seminar	= ((isset($report_results["courses"]["clerkship_seminar"]["events_minutes"])) ? $report_results["courses"]["clerkship_seminar"]["events_minutes"] : 0);
							$duration_total_events				= ((isset($report_results["courses"]["events"]["events_minutes"])) ? $report_results["courses"]["events"]["events_minutes"] : 0);

							$session_total_lecture				= ((isset($report_results["courses"]["lecture"]["events_calculated"])) ? $report_results["courses"]["lecture"]["events_calculated"] : 0);
							$session_total_lab					= ((isset($report_results["courses"]["lab"]["events_calculated"])) ? $report_results["courses"]["lab"]["events_calculated"] : 0);
							$session_total_small_group			= ((isset($report_results["courses"]["small_group"]["events_calculated"])) ? $report_results["courses"]["small_group"]["events_calculated"] : 0);
							$session_total_patient_contact		= ((isset($report_results["courses"]["patient_contact"]["events_calculated"])) ? $report_results["courses"]["patient_contact"]["events_calculated"] : 0);
							$session_total_symposium			= ((isset($report_results["courses"]["symposium"]["events_calculated"])) ? $report_results["courses"]["symposium"]["events_calculated"] : 0);
							$session_total_directed_learning	= ((isset($report_results["courses"]["directed_learning"]["events_calculated"])) ? $report_results["courses"]["directed_learning"]["events_calculated"] : 0);
							$session_total_review				= ((isset($report_results["courses"]["review"]["events_calculated"])) ? $report_results["courses"]["review"]["events_calculated"] : 0);
							$session_total_exam					= ((isset($report_results["courses"]["exam"]["events_calculated"])) ? $report_results["courses"]["exam"]["events_calculated"] : 0);
							$session_total_interview			= ((isset($report_results["courses"]["interview"]["events_calculated"])) ? $report_results["courses"]["interview"]["events_calculated"] : 0);
							$session_total_observership			= ((isset($report_results["courses"]["observership"]["events_calculated"])) ? $report_results["courses"]["observership"]["events_calculated"] : 0);
							$session_total_clerkship_seminar	= ((isset($report_results["courses"]["clerkship_seminar"]["events_calculated"])) ? $report_results["courses"]["clerkship_seminar"]["events_calculated"] : 0);
							$session_total_events				= ((isset($report_results["courses"]["events"]["events_calculated"])) ? $report_results["courses"]["events"]["events_calculated"] : 0);

							$duration_final_total	= ($duration_total_lecture + $duration_total_lab + $duration_total_pbl + $duration_total_small_group + $duration_total_patient_contact + $duration_total_symposium + $duration_total_directed_learning + $duration_total_review + $duration_total_exam + $duration_total_clerkship_seminar + $duration_total_events);
							$session_final_total	= ($session_total_lecture + $session_total_lab + $session_total_pbl + $session_total_small_group + $session_total_patient_contact + $session_total_symposium + $session_total_directed_learning + $session_total_review + $session_total_exam + $session_total_clerkship_seminar + $session_total_events);
							
							if ($duration_final_total && $session_final_total) {
								?>
								<tr>
									<td colspan="14"">&nbsp;</td>
								</tr>
								<tr style="background-color: #DEE6E3; font-weight: bold">
									<td class="modified">&nbsp;</td>
									<td class="general">Final Totals:</td>
									<td class="report-hours-lg"><?php echo (($session_total_lecture) ? display_half_days($session_total_lecture, "lecture") : "&nbsp;"); ?></td>
									<td class="report-hours-lg"><?php echo (($session_total_lab) ? display_half_days($session_total_lab, "lab") : "&nbsp;"); ?></td>
									<td class="report-hours-lg"><?php echo (($session_total_small_group) ? display_half_days($session_total_small_group, "small_group") : "&nbsp;"); ?></td>
									<td class="report-hours-lg"><?php echo (($session_total_patient_contact) ? display_half_days($session_total_patient_contact, "patient_contact") : "&nbsp;"); ?></td>
									<td class="report-hours-lg"><?php echo (($session_total_symposium) ? display_half_days($session_total_symposium, "symposium") : "&nbsp;"); ?></td>
									<td class="report-hours-lg"><?php echo (($session_total_directed_learning) ? display_half_days($session_total_directed_learning, "directed_learning") : "&nbsp;"); ?></td>
									<td class="report-hours-lg"><?php echo (($session_total_review) ? display_half_days($session_total_review, "review") : "&nbsp;"); ?></td>
									<td class="report-hours-lg"><?php echo (($session_total_exam) ? display_half_days($session_total_exam, "exam") : "&nbsp;"); ?></td>
									<td class="report-hours-lg"><?php echo (($session_total_interview) ? display_half_days($session_total_interview, "interview") : "&nbsp;"); ?></td>
									<td class="report-hours-lg"><?php echo (($session_total_observership) ? display_half_days($session_total_observership, "observership") : "&nbsp;"); ?></td>
									<td class="report-hours-lg"><?php echo (($session_total_clerkship_seminar) ? display_half_days($session_total_clerkship_seminar, "clerkship_seminar") : "&nbsp;"); ?></td>
									<td class="report-hours-lg"><?php echo (($session_total_events) ? display_half_days($session_total_events, "events") : "&nbsp;"); ?></td>
									<td class="report-hours-lg"><?php echo (($duration_final_total) ? display_hours($duration_final_total) : "&nbsp;"); ?></td>
									<td class="report-hours-lg"><?php echo (($session_final_total) ? $session_final_total : "&nbsp;"); ?></td>
								</tr>
							<?php
							}
						}
						?>
					</tbody>
				</table>
				<?php
				if ((is_array($no_staff_number)) && ($total_no_staff_number = count($no_staff_number))) {
					?>
					<div class="no-printing">
						<h2>Numberless Faculty</h2>
						In order to increase the accuracy of our reporting we need to ensure that all faculty members have their staff number attached to their MEdTech profile. There currently <?php echo $total_no_staff_number; ?> faculty member<?php echo (($total_no_staff_number != 1) ? "s" : ""); ?> in the system that have no staff numbers associated with them; they are therefore put into an &quot;Unknown or N/A&quot; department.
						<br /><br />
						<table style="width: 100%" cellspacing="0" summary="Faculty Members Without Staff Numbers">
							<tbody>
								<tr>
									<?php
									$i				= 0;
									$columns		= 0;
									$max_columns	= 4;
									foreach($no_staff_number as $result) {
										$i++;
										$columns++;
										echo "\t<td".((($i == $total_no_staff_number) && ($columns < $max_columns)) ? " colspan=\"".(($max_columns - $columns) + 1)."\"" :"").">".html_encode($result["fullname"])."</td>\n";

										if (($columns == $max_columns) || ($i == $total_no_staff_number)) {
											$columns = 0;
											echo "</tr>\n";

											if ($i < $total_no_staff_number) {
												echo "<tr>\n";
											}
										}
									}
									?>
							</tbody>
						</table>
					</div>
					<?php
				}

				$sidebar_html  = "<ul class=\"menu\">\n";
				foreach($department_sidebar as $result) {
					$sidebar_html .= "	<li class=\"link\"><a href=\"".$result["department_link"]."\" title=\"".html_encode($result["department_name"])."\">".html_encode($result["department_name"])."</a></li>\n";
				}
				$sidebar_html .= "</ul>";
				new_sidebar_item("Department List", $sidebar_html, "department-list", "open");
			} else {
				echo '<div class="display-notice">There were no faculty found using the specified parameters. Try selecting a different organisation or date range.</div>';

			}
		}
	}
?>