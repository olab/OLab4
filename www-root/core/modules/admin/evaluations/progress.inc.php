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
 * This file is used to review the progress of evaluations.
 *
 * @author Organisation: University of Calgary
 * @author Unit: School of Medicine
 * @author Developer:  Howard Lu <yhlu@ucalgary.ca>
 * @copyright Copyright 2010 University of Calgary. All Rights Reserved.
 *
*/
if((!defined("PARENT_INCLUDED")) || (!defined("IN_EVALUATIONS"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
}

if ($MAILING_LISTS["active"]) {
	require_once("Entrada/mail-list/mail-list.class.php");
}

$EVALUATION_ID		= 0;
$PREFERENCES		= preferences_load($MODULE);
$PROXY_IDS			= array();

/**
 * 
 */
if((isset($_GET["evaluation"])) && ((int) trim($_GET["evaluation"]))) {
	$EVALUATION_ID	= (int) trim($_GET["evaluation"]);
} elseif((isset($_POST["evaluation_id"])) && ((int) trim($_POST["evaluation_id"]))) {
	$EVALUATION_ID	= (int) trim($_POST["evaluation_id"]);
} elseif((isset($_POST["evaluation"])) && ((int) trim($_POST["evaluation"]))) {
	$EVALUATION_ID	= (int) trim($_POST["evaluation"]);
}


if((isset($_GET["type"])) && ($tmp_action_type = clean_input(trim($_GET["type"]), "alphanumeric"))) {
	$ACTION_TYPE	= $tmp_action_type;
} elseif((isset($_POST["type"])) && ($tmp_action_type = clean_input(trim($_POST["type"]), "alphanumeric"))) {
	$ACTION_TYPE	= $tmp_action_type;
}
unset($tmp_action_type);


/**
 * Ensure that the selected evaluation is in the system.
 */
if($EVALUATION_ID) {
	$query				= "SELECT a.*, c.`target_shortname` FROM `evaluations` AS a
							JOIN `evaluation_forms` AS b
							ON a.`eform_id` = b.`eform_id`
							JOIN `evaluations_lu_targets` AS c
							ON b.`target_id` = c.`target_id`
							WHERE a.`evaluation_id` = ".$db->qstr($EVALUATION_ID);
	$evaluation_details	= $db->GetRow($query);
	if($evaluation_details) {
		$BREADCRUMB[]	= array("url" => ENTRADA_URL."/admin/evaluations?".replace_query(array("section" => "edit", "id" => $EVALUATION_ID)), "title" => "Show Progress");
		
        /**
         * Update whether individual attempts are displayed or not.
         * Valid: true, false
         */
        if((isset($_GET["display"]) && $_GET["display"] == "complete_list") || !isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["display"])) {
           $view_individual_attempts = true;
           $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["display"] = true;
        } elseif ((isset($_GET["display"]) && $_GET["display"] == "summary")) {
           $view_individual_attempts = false;
           $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["display"] = false;
        }
                
        /**
         * Update requested sort column.
         * Valid: date, title
         */
        if(isset($_GET["sb"])) {
                if(@in_array(trim($_GET["sb"]), array("date", "name", "type"))) {
                        $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] = trim($_GET["sb"]);
                }

                $_SERVER["QUERY_STRING"]	= replace_query(array("sb" => false));
        } else {
                if(!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"])) {
                        $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] = "date";
                }
        }

        /**
         * Update requested order to sort by.
         * Valid: asc, desc
         */
        if(isset($_GET["so"])) {
                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"] = ((strtolower($_GET["so"]) == "desc") ? "desc" : "asc");

                $_SERVER["QUERY_STRING"]	= replace_query(array("so" => false));
        } else {
                if(!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"])) {
                        $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"] = "asc";
                }
        }

        /**
         * Update requsted number of rows per page.
         * Valid: any integer really.
         */
        if((isset($_GET["pp"])) && ((int) trim($_GET["pp"]))) {
                $integer = (int) trim($_GET["pp"]);

                if(($integer > 0) && ($integer <= 250)) {
                        $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"] = $integer;
                }

                $_SERVER["QUERY_STRING"] = replace_query(array("pp" => false));
        } else {
                if(!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"])) {
                        $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"] = 15;
                }
        }

        if($NOTICE) {
                echo display_notice();
        }
        if($ERROR) {
                echo display_error();
        }
		
		
		$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.dataTables.min.js\"></script>";
		$HEAD[] = "<script type=\"text/javascript\">
		jQuery(document).ready(function() {
			jQuery('#attempts').dataTable(
				{    
					'bPaginate': false,
					'bInfo': false,
                    'bAutoWidth': false
				}
			);
		});
		</script>";
		
        if ($ENTRADA_ACL->amIAllowed(new EvaluationResource($evaluation_details["evaluation_id"], $evaluation_details["organisation_id"], true), 'update')) {
            echo "<div class=\"no-printing\">\n";
            echo "	<div style=\"float: right; margin-top: 8px\">\n";
            echo "		<a href=\"".ENTRADA_URL."/admin/evaluations?".replace_query(array("section" => "edit", "id" => $evaluation_details["evaluation_id"]))."\"><img src=\"".ENTRADA_URL."/images/event-details.gif\" width=\"16\" height=\"16\" alt=\"Edit details\" title=\"Edit evaluation details\" border=\"0\" style=\"vertical-align: middle\" /></a> <a href=\"".ENTRADA_URL."/admin/evaluations?".replace_query(array("section" => "edit", "id" => $evaluation_details["evaluation_id"]))."\" style=\"font-size: 10px; margin-right: 8px\">Edit details</a>\n";
            echo "	</div>\n";
            echo "</div>\n";
        }
        echo "<h1 class=\"evaluation-title\">".html_encode($evaluation_details["evaluation_title"])." Progress</h1>\n";
        ?>
        <div class="tab-pane" id="progress_div">
            <?php
            if (!$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["display"]) {
				$query = "	SELECT a.*, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, b.`id` AS `proxy_id`
	            			FROM `evaluation_evaluators` AS a
                			JOIN `".AUTH_DATABASE."`.`user_access` AS ba
                			ON ba.`app_id` = ".$db->qstr(AUTH_APP_ID)."
                			LEFT JOIN `groups` AS g
                			ON g.`group_type` = 'cohort'
                			AND a.`evaluator_type` = 'cohort'
                			AND a.`evaluator_value` = g.`group_id`
                			LEFT JOIN `group_members` AS gm
                			ON g.`group_id` = gm.`group_id`
							LEFT JOIN `course_groups` AS cg
							ON a.`evaluator_type` = 'cgroup_id'
							AND a.`evaluator_value` = cg.`cgroup_id`
							LEFT JOIN `course_group_audience` AS cga
							ON cg.`cgroup_id` = cga.`cgroup_id`
	            			JOIN `".AUTH_DATABASE."`.`user_data` AS b
	            			ON ((
		            			a.`evaluator_type` = 'proxy_id'
		            			AND a.`evaluator_value` = b.`id`
							) OR (
								gm.`proxy_id` = b.`id`
							) OR (
								cga.`proxy_id` = b.`id`
							))
							AND ba.`user_id` = b.`id`
	            			WHERE a.`evaluation_id` = ".$db->qstr($EVALUATION_ID);
	            $evaluation_evaluators = $db->GetAll($query);
	        	if ($evaluation_evaluators) {
	                ?>
	                <table id="attempts" class="tableList" style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Evaluation Members">
	                <colgroup>
                        <col class="modified" />
                        <col class="target" />
                        <col class="date-small" />
                        <col class="date-small" />
                        <col class="date" />
	                </colgroup>
	                <thead>
                        <tr>
                            <td class="modified">&nbsp;</td>
                            <td class="target">Evaluator</td>
                            <td class="date-small">Current Attempt</td>
                            <td class="date-small">Attempts Completed</td>
                            <td class="date">Last Attempted</td>
                        </tr>
	                </thead>
	                <tbody>
	                <?php
	                foreach($evaluation_evaluators as $evaluation_evaluator) {
                		$query = "	SELECT * FROM `evaluation_progress`
                					WHERE `evaluation_id` = ".$evaluation_evaluator["evaluation_id"]."
		                			AND `proxy_id` = ".$evaluation_evaluator["proxy_id"]."
		                			AND `progress_value` <> 'cancelled'
                					ORDER BY `progress_value` ASC";
                		$evaluator_progress_records = $db->GetAll($query);
                		$count = 0;
                		$inprogress = true;
                		$last_completed = 0;
                		$last_updated = 0;
              			if ($evaluator_progress_records) {
	                		foreach ($evaluator_progress_records as &$evaluator_progress) {
	                			if ($evaluator_progress["progress_value"] == "complete") {
	                				$count++;
	                				$inprogress = false;
		            				if ($last_completed < $evaluator_progress["updated_date"]) {
		            					$last_completed = $evaluator_progress["updated_date"];
		            				}
		                		}
	            				if ($last_updated < $evaluator_progress["updated_date"]) {
	            					$last_updated = $evaluator_progress["updated_date"];
	            				}
            				}
	            			if ($inprogress == true && $last_completed < $last_updated) {
	            				$last_completed = $last_updated;
	            			}
              			} else  {
              				$inprogress = false;
              			}
						$evaluation_targets_list = Classes_Evaluation::getTargetsArray($EVALUATION_ID, $evaluation_evaluator["eevaluator_id"], $evaluation_evaluator["proxy_id"]);
						$max_submittable = $evaluation_details["max_submittable"];
						if ($evaluation_targets_list) {
							$evaluation_targets_count = count($evaluation_targets_list);
							if (array_search($evaluation_details["target_shortname"], array("preceptor", "rotation_core", "rotation_elective")) !== false && $evaluation_details["max_submittable"]) {
								$max_submittable = ($evaluation_targets_count * (int) $evaluation_details["max_submittable"]);
							} elseif ($evaluation_details["target_shortname"] == "peer" && $evaluation_details["max_submittable"] == 0) {
								$max_submittable = $evaluation_targets_count;
							} elseif ($evaluation_details["max_submittable"] == 0 && $evaluation_details["allow_repeat_targets"]) {
                                $max_submittable = "-";
                            }
						} else {
							$target_name = "";
						}
                        echo "<tr>\n";
                        echo "	<td>&nbsp;</td>\n";
                        echo "	<td>".$evaluation_evaluator["fullname"]."</td>\n";
                        echo "	<td>".($inprogress ? "In Progress" : ($last_completed ? "Completed" : "Not Started"))."</td>\n";
                        echo "	<td>".$count." / ".$max_submittable."</td>\n";
                        echo "	<td>".(isset($last_completed) && $last_completed ? date(DEFAULT_DATE_FORMAT, $last_completed) : "Not Started")."</td>\n";
                        echo "</tr>\n";
	                }
	                ?>
	                </tbody>
	                <tfoot>
	                    <tr><td>&nbsp;</td></tr>
	                </tfoot>
	                </table>
	                <?php
	            } else {
	                    echo display_notice(array("No evaluators have completed this evaluation at this time."));
	            }
            } else {
            	
            	?>
            	<br />
            	<h2 style="margin-top: 0px">Complete Attempts</h2>
            	<?php
	            /**
	             * Get the total number of results using the generated queries above and calculate the total number
	             * of pages that are available based on the results per page preferences.
	             */	           
	           $query = "SELECT a.*, c.`target_record_id`, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, b.`lastname`, b.`firstname`, a.`updated_date` AS `ordered_date`, c.`progress_value`, c.`updated_date`, d.`target_value`, e.`target_shortname`, b.`id` AS `proxy_id`, c.`etarget_id`
	            			FROM `evaluation_progress` AS c
                			JOIN `evaluation_evaluators` AS a
                			ON a.`evaluation_id` = c.`evaluation_id`
	            			JOIN `".AUTH_DATABASE."`.`user_data` AS b
	            			ON a.`evaluator_type` = 'proxy_id'
	            			AND a.`evaluator_value` = b.`id`
                			AND b.`id` = a.`evaluator_value`
                			AND b.`id` = c.`proxy_id`
                			JOIN `".AUTH_DATABASE."`.`user_access` AS ba
                			ON ba.`app_id` = ".$db->qstr(AUTH_APP_ID)."
							AND ba.`user_id` = b.`id`
                			LEFT JOIN `evaluation_targets` AS d
                			ON d.`etarget_id` = c.`etarget_id`
                			LEFT JOIN `evaluations_lu_targets` AS e
                			ON d.`target_id` = e.`target_id`
	            			WHERE c.`evaluation_id` = ".$db->qstr($EVALUATION_ID)."
                			AND c.`progress_value` = 'complete'
	            			GROUP BY c.`eprogress_id`
	            			
	            			UNION
	            			
	            			SELECT a.*, c.`target_record_id`, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, b.`lastname`, b.`firstname`, a.`updated_date` AS `ordered_date`, c.`progress_value`, c.`updated_date`, d.`target_value`, e.`target_shortname`, b.`id` AS `proxy_id`, c.`etarget_id`
	            			FROM `evaluation_progress` AS c
                			JOIN `evaluation_evaluators` AS a
                			ON a.`evaluation_id` = c.`evaluation_id`
                			JOIN `groups` AS g
                			ON a.`evaluator_value` = g.`group_id`
                			JOIN `group_members` AS gm
                			ON g.`group_id` = gm.`group_id`
                			AND gm.`member_active` = 1
                			AND gm.`proxy_id` = c.`proxy_id`
	            			JOIN `".AUTH_DATABASE."`.`user_data` AS b
	            			ON a.`evaluator_value` = g.`group_id`
                			AND b.`id` = c.`proxy_id`
                			JOIN `".AUTH_DATABASE."`.`user_access` AS ba
                			ON ba.`app_id` = ".$db->qstr(AUTH_APP_ID)."
							AND ba.`user_id` = b.`id`
                			LEFT JOIN `evaluation_targets` AS d
                			ON d.`etarget_id` = c.`etarget_id`
                			LEFT JOIN `evaluations_lu_targets` AS e
                			ON d.`target_id` = e.`target_id`
	            			WHERE c.`evaluation_id` = ".$db->qstr($EVALUATION_ID)."
	            			AND a.`evaluator_type` = 'cohort'
	            			AND g.`group_type` = 'cohort'
                			AND c.`progress_value` = 'complete'
	            			GROUP BY c.`eprogress_id`
							
							UNION
							
	            			SELECT a.*, c.`target_record_id`, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, b.`lastname`, b.`firstname`, a.`updated_date` AS `ordered_date`, c.`progress_value`, c.`updated_date`, d.`target_value`, e.`target_shortname`, b.`id` AS `proxy_id`, c.`etarget_id`
	            			FROM `evaluation_progress` AS c
                			JOIN `evaluation_evaluators` AS a
                			ON a.`evaluation_id` = c.`evaluation_id`
                			JOIN `course_groups` AS cg
                			ON a.`evaluator_value` = cg.`cgroup_id`
                			JOIN `course_group_audience` AS cga
                			ON cg.`cgroup_id` = cga.`cgroup_id`
                			AND cga.`active` = 1
                			AND cga.`proxy_id` = c.`proxy_id`
	            			JOIN `".AUTH_DATABASE."`.`user_data` AS b
	            			ON a.`evaluator_value` = cg.`cgroup_id`
                			AND b.`id` = c.`proxy_id`
                			JOIN `".AUTH_DATABASE."`.`user_access` AS ba
                			ON ba.`app_id` = ".$db->qstr(AUTH_APP_ID)."
							AND ba.`user_id` = b.`id`
                			LEFT JOIN `evaluation_targets` AS d
                			ON d.`etarget_id` = c.`etarget_id`
                			LEFT JOIN `evaluations_lu_targets` AS e
                			ON d.`target_id` = e.`target_id`
	            			WHERE c.`evaluation_id` = ".$db->qstr($EVALUATION_ID)."
	            			AND a.`evaluator_type` = 'cgroup_id'
                			AND c.`progress_value` = 'complete'
	            			GROUP BY c.`eprogress_id`
	            			ORDER BY `etarget_id`, `lastname`, `firstname`, `ordered_date`";
	            $evaluation_evaluators = $db->GetAll($query);
	            
	        	if ($evaluation_evaluators) {
	                ?>
	                <table class="tableList" style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Evaluation Members">
	                <colgroup>
                        <col class="modified" />
                        <col class="title" />
                        <col class="title" />
                        <col class="date-small" />
                        <col class="date" />
	                </colgroup>
	                <thead>
                        <tr>
                            <td class="modified">&nbsp;</td>
                            <td class="title">Evaluator</td>
                            <td class="title">Evaluation Target</td>
                            <td class="date-small">Attempt Progress</td>
                            <td class="date">Last Updated</td>
                        </tr>
	                </thead>
	                <tbody>
	                <?php
	                foreach($evaluation_evaluators as $evaluation_evaluator) {
						if (in_array($evaluation_evaluator["target_shortname"], array("rotation_core", "rotation_elective", "preceptor"))) {
							$query = "SELECT `event_id` FROM `evaluation_progress_clerkship_events`
										WHERE `eprogress_id` = ".$db->qstr($evaluation_evaluator["eprogress_id"]);
							$event_id = $db->GetOne($query);
							if ($event_id) {
								$evaluation_evaluator["target_record_id"] = $event_id;
							}
						}
	                	$target_name = fetch_evaluation_target_title($evaluation_evaluator["target_record_id"], 1, $evaluation_evaluator["target_shortname"]);
                        echo "<tr>\n";
                        echo "	<td>&nbsp;</td>\n";
                        echo "	<td>".$evaluation_evaluator["fullname"]."</td>\n";
                        echo "	<td>".$target_name."</td>\n";
                        echo "	<td>".($evaluation_evaluator["progress_value"] == "inprogress" ? "In Progress" : "Completed")."</td>\n";
                        echo "	<td>".date(DEFAULT_DATE_FORMAT, $evaluation_evaluator["updated_date"])."</td>\n";
                        echo "</tr>\n";
	                }
	                ?>
	                </tbody>
	                <tfoot>
	                    <tr><td>&nbsp;</td></tr>
	                </tfoot>
	                </table>
	                <?php
	            } else {
	                    echo display_notice(array("No evaluators have completed this evaluation at this time."));
	            }
            	?>
            	<br />
            	<h2 style="margin-top: 0px">Incomplete Attempts</h2>
            	<?php
	            /**
	             * Get the total number of results using the generated queries above and calculate the total number
	             * of pages that are available based on the results per page preferences.
	             */
	           $query = "SELECT a.*, c.`target_record_id`, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, b.`lastname`, b.`firstname`, a.`updated_date` AS `ordered_date`, c.`progress_value`, c.`updated_date`, d.`target_value`, e.`target_shortname`, b.`id` AS `proxy_id`
	            			FROM `evaluation_progress` AS c
                			JOIN `evaluation_evaluators` AS a
                			ON a.`evaluation_id` = c.`evaluation_id`
	            			JOIN `".AUTH_DATABASE."`.`user_data` AS b
	            			ON a.`evaluator_type` = 'proxy_id'
	            			AND a.`evaluator_value` = b.`id`
                			AND b.`id` = a.`evaluator_value`
                			AND c.`proxy_id` = b.`id`
                			JOIN `".AUTH_DATABASE."`.`user_access` AS ba
                			ON ba.`app_id` = ".$db->qstr(AUTH_APP_ID)."
							AND ba.`user_id` = b.`id`
                			LEFT JOIN `evaluation_targets` AS d
                			ON d.`etarget_id` = c.`etarget_id`
                			LEFT JOIN `evaluations_lu_targets` AS e
                			ON d.`target_id` = e.`target_id`
	            			WHERE c.`evaluation_id` = ".$db->qstr($EVALUATION_ID)."
                			AND c.`progress_value` = 'inprogress'
	            			GROUP BY b.`id`
	            			
	            			UNION
	            			
	            			SELECT a.*, c.`target_record_id`, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, b.`lastname`, b.`firstname`, a.`updated_date` AS `ordered_date`, c.`progress_value`, c.`updated_date`, d.`target_value`, e.`target_shortname`, b.`id` AS `proxy_id`
	            			FROM `evaluation_progress` AS c
                			JOIN `evaluation_evaluators` AS a
                			ON a.`evaluation_id` = c.`evaluation_id`
                			JOIN `groups` AS g
                			ON a.`evaluator_value` = g.`group_id`
                			JOIN `group_members` AS gm
                			ON g.`group_id` = gm.`group_id`
                			AND gm.`member_active` = 1
	            			JOIN `".AUTH_DATABASE."`.`user_data` AS b
	            			ON a.`evaluator_value` = g.`group_id`
                			AND b.`id` = gm.`proxy_id`
                			AND c.`proxy_id` = b.`id`
                			JOIN `".AUTH_DATABASE."`.`user_access` AS ba
                			ON ba.`app_id` = ".$db->qstr(AUTH_APP_ID)."
							AND ba.`user_id` = b.`id`
                			LEFT JOIN `evaluation_targets` AS d
                			ON d.`etarget_id` = c.`etarget_id`
                			LEFT JOIN `evaluations_lu_targets` AS e
                			ON d.`target_id` = e.`target_id`
	            			WHERE c.`evaluation_id` = ".$db->qstr($EVALUATION_ID)."
	            			AND a.`evaluator_type` = 'cohort'
	            			AND g.`group_type` = 'cohort'
                			AND c.`progress_value` = 'inprogress'
	            			GROUP BY gm.`proxy_id`
	            			
							UNION

	            			SELECT a.*, c.`target_record_id`, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, b.`lastname`, b.`firstname`, a.`updated_date` AS `ordered_date`, c.`progress_value`, c.`updated_date`, d.`target_value`, e.`target_shortname`, b.`id` AS `proxy_id`
	            			FROM `evaluation_progress` AS c
                			JOIN `evaluation_evaluators` AS a
                			ON a.`evaluation_id` = c.`evaluation_id`
                			JOIN `course_groups` AS cg
                			ON a.`evaluator_value` = cg.`cgroup_id`
                			JOIN `course_group_audience` AS cga
                			ON cg.`cgroup_id` = cga.`cgroup_id`
                			AND cga.`active` = 1
	            			JOIN `".AUTH_DATABASE."`.`user_data` AS b
	            			ON a.`evaluator_value` = cg.`cgroup_id`
                			AND b.`id` = cga.`proxy_id`
                			AND c.`proxy_id` = b.`id`
                			JOIN `".AUTH_DATABASE."`.`user_access` AS ba
                			ON ba.`app_id` = ".$db->qstr(AUTH_APP_ID)."
							AND ba.`user_id` = b.`id`
                			LEFT JOIN `evaluation_targets` AS d
                			ON d.`etarget_id` = c.`etarget_id`
                			LEFT JOIN `evaluations_lu_targets` AS e
                			ON d.`target_id` = e.`target_id`
	            			WHERE c.`evaluation_id` = ".$db->qstr($EVALUATION_ID)."
	            			AND a.`evaluator_type` = 'cgroup_id'
                			AND c.`progress_value` = 'inprogress'
	            			GROUP BY cga.`proxy_id`
	            			ORDER BY `lastname`, `firstname`, `ordered_date`";
	            $evaluation_evaluators = $db->GetAll($query);
	            
	        	if ($evaluation_evaluators) {
	                ?>
	                <table class="tableList" style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Evaluation Members">
	                <colgroup>
                        <col class="modified" />
                        <col class="title" />
                        <col class="title" />
                        <col class="date-small" />
                        <col class="date" />
	                </colgroup>
	                <thead>
                        <tr>
                            <td class="modified">&nbsp;</td>
                            <td class="title">Evaluator</td>
                            <td class="title">Evaluation Target</td>
                            <td class="date-small">Attempt Progress</td>
                            <td class="date">Last Updated</td>
                        </tr>
	                </thead>
	                <tbody>
	                <?php
	                foreach($evaluation_evaluators as $evaluation_evaluator) {
						if (in_array($evaluation_evaluator["target_shortname"], array("rotation_core", "rotation_elective", "preceptor"))) {
							$query = "SELECT `event_id` FROM `evaluation_progress_clerkship_events`
										WHERE `eprogress_id` = ".$db->qstr($evaluation_evaluator["eprogress_id"]);
							$event_id = $db->GetOne($query);
							if ($event_id) {
								$evaluation_evaluator["target_record_id"] = $event_id;
							}
						}
	                	$target_name = fetch_evaluation_target_title($evaluation_evaluator["target_record_id"], 1, $evaluation_evaluator["target_shortname"]);
                        echo "<tr>\n";
                        echo "	<td>&nbsp;</td>\n";
                        echo "	<td>".$evaluation_evaluator["fullname"]."</td>\n";
                        echo "	<td>".$target_name."</td>\n";
                        echo "	<td>".($evaluation_evaluator["progress_value"] == "inprogress" ? "In Progress" : "Completed")."</td>\n";
                        echo "	<td>".date(DEFAULT_DATE_FORMAT, $evaluation_evaluator["updated_date"])."</td>\n";
                        echo "</tr>\n";
	                }
	                ?>
	                </tbody>
	                <tfoot>
	                    <tr><td>&nbsp;</td></tr>
	                </tfoot>
	                </table>
	                <?php
	            } else {
	                    echo display_notice(array("No evaluators have a current attempt in progress for this evaluation."));
	            }
	            ?>
            	<br />
            	<h2 style="margin-top: 0px">Evaluators With No Attempts</h2>
            	<?php
	            /**
	             * Get the total number of results using the generated queries above and calculate the total number
	             * of pages that are available based on the results per page preferences.
	             */
	          $query = "	SELECT a.*, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, b.`id` AS `proxy_id`
	            			FROM `evaluation_evaluators` AS a
                			LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS ba
                			ON ba.`app_id` = ".$db->qstr(AUTH_APP_ID)."
                			LEFT JOIN `groups` AS g
                			ON a.`evaluator_type` = 'cohort'
                			AND a.`evaluator_value` = g.`group_id`
                			LEFT JOIN `group_members` AS gm
                			ON g.`group_id` = gm.`group_id`
                			AND gm.`member_active`
                			LEFT JOIN `course_groups` AS cg
                			ON a.`evaluator_type` = 'cgroup_id'
                			AND a.`evaluator_value` = cg.`cgroup_id`
                			LEFT JOIN `course_group_audience` AS cga
                			ON cg.`cgroup_id` = cga.`cgroup_id`
                			AND cga.`active`
	            			JOIN `".AUTH_DATABASE."`.`user_data` AS b
	            			ON ba.`user_id` = b.`id`
	            			AND (
	            					gm.`proxy_id` = b.`id`
									OR cga.`proxy_id` = b.`id`
	            					OR (
	            							b.`id` = a.`evaluator_value` 
	            							AND a.`evaluator_type` = 'proxy_id'
	            						)
	            				)
	            			WHERE a.`evaluation_id` = ".$db->qstr($EVALUATION_ID)."
	            			ORDER BY b.`lastname`, b.`firstname`";
	            $evaluation_evaluators = $db->GetAll($query);
	            
	        	if ($evaluation_evaluators) {
	        		$query = "SELECT `proxy_id` FROM `evaluation_progress` WHERE `evaluation_id` = ".$db->qstr($EVALUATION_ID);
	        		$ignorable_evaluators = $db->GetAll($query);
	        		$ignore_list = array();
	        		foreach ($ignorable_evaluators as $ignorable_evaluator) {
	        			$ignore_list[] = $ignorable_evaluator["proxy_id"];
	        		}
	                ?>
	                <table class="tableList" style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Evaluation Members">
	                <colgroup>
                        <col class="modified" />
                        <col class="title" />
                        <col class="title" />
                        <col class="date-small" />
                        <col class="date" />
	                </colgroup>
	                <thead>
                        <tr>
                            <td class="modified">&nbsp;</td>
                            <td class="title">Evaluator</td>
                            <td class="title">Evaluation Target</td>
                            <td class="date-small">Attempt Progress</td>
                            <td class="date">Last Updated</td>
                        </tr>
	                </thead>
	                <tbody>
	                <?php
	                foreach($evaluation_evaluators as $evaluation_evaluator) {
	                	if (array_search($evaluation_evaluator["proxy_id"], $ignore_list) === false) {
							$evaluation_targets_list = Classes_Evaluation::getTargetsArray($EVALUATION_ID, $evaluation_evaluator["eevaluator_id"], $evaluation_evaluator["proxy_id"]);
							if ($evaluation_targets_list) {
								$evaluation_targets_count = count($evaluation_targets_list);
								if (array_search($evaluation_details["target_shortname"], array("preceptor", "rotation_core", "rotation_elective")) !== false && $evaluation_details["max_submittable"]) {
									$evaluation_details["max_submittable"] = ($evaluation_targets_count * (int) $evaluation_details["max_submittable"]);
								}
								$target_name = fetch_evaluation_target_title($evaluation_targets_list[0], $evaluation_targets_count, $evaluation_details["target_shortname"]);
								if ($evaluation_details["target_shortname"] == "peer" && $evaluation_details["max_submittable"] == 0) {
									$evaluation_details["max_submittable"] = $evaluation_targets_count;
								}
							} else {
								$target_name = "";
							}
	                        echo "<tr>\n";
	                        echo "	<td>&nbsp;</td>\n";
	                        echo "	<td>".$evaluation_evaluator["fullname"]."</td>\n";
	                        echo "	<td>".$target_name."</td>\n";
	                        echo "	<td>Not yet begun</td>\n";
	                        echo "	<td>".date(DEFAULT_DATE_FORMAT, $evaluation_evaluator["updated_date"])."</td>\n";
	                        echo "</tr>\n";
	                	}
	                }
	                ?>
	                </tbody>
	                <tfoot>
	                    <tr><td>&nbsp;</td></tr>
	                </tfoot>
	                </table>
	                <?php
	            } else {
	                    echo display_notice(array("There are no evaluators who have not started this evaluation."));
	            }
            }
            ?>
		</div>
		<br /><br />
		<?php
		/**
		 * Sidebar item that will provide a method for choosing which results to display.
		 */
		$sidebar_html  = "Display progress as a:\n";
		$sidebar_html .= "<ul class=\"menu\">\n";
		$sidebar_html .= "	<li class=\"".(($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["display"] == false) ? "on" : "off")."\"><a href=\"".ENTRADA_URL."/admin/".$MODULE."?".replace_query(array("display" => "summary"))."\" title=\"Summary\">summary</a></li>\n";
		$sidebar_html .= "	<li class=\"".(($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["display"] == true) ? "on" : "off")."\"><a href=\"".ENTRADA_URL."/admin/".$MODULE."?".replace_query(array("display" => "complete_list"))."\" title=\"Complete list\">complete list of attempts</a></li>\n";
		$sidebar_html .= "</ul>\n";

		new_sidebar_item("Progress Display", $sidebar_html, "sort-results", "open");
	} else {
		application_log("error", "User tried to manage progress of a evaluation id [".$EVALUATION_ID."] that does not exist or is not active in the system.");

		$ERROR++;
		$ERRORSTR[] = "The evaluation you are trying to manage either does not exist in the system or has been deactived by an administrator.<br /><br />If you feel you are receiving this message in error, please contact the MEdTech Unit (page feedback on left) and we will investigate. The MEdTech Unit has automatically been informed that this error has taken place.";

		echo display_error();
	}
} else {
	application_log("error", "User tried to manage members a evaluation without providing a evaluation_id.");

	header("Location: ".ENTRADA_URL."/admin/evaluations");
	exit;
}
?>