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
 * This file looks a bit different because it is called only by AJAX requests
 * and returns the members relevant to the requested group, role, and community.
 *
 * @author Organisation: University of Calgary
 * @author Unit: School of Medicine
 * @author Developer:  Howard Lu <yhlu@ucalgary.ca>
 * @copyright Copyright 2010 University of Calgary. All Rights Reserved.
 *
 * @version $Id: add.inc.php 317 2009-01-19 19:26:35Z simpson $
 *
 */
if((!defined("PARENT_INCLUDED")) || (!defined("IN_EVALUATIONS"))) {
/**
 * @exception 0: Unable to start processing request.
 */
	echo "Authentication error!";
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
/**
 * @exception 0: Unable to start processing request.
 */
	echo "Authentication error!";
	exit;
}

ob_clear_open_buffers();
$EVALUATION_ID		= 0;

if((isset($_GET["evaluation"])) && ((int) trim($_GET["evaluation"]))) {
	$EVALUATION_ID	= (int) trim($_GET["evaluation"]);
} elseif((isset($_POST["evaluation_id"])) && ((int) trim($_POST["evaluation_id"]))) {
	$EVALUATION_ID	= (int) trim($_POST["evaluation_id"]);
}

if((isset($_GET["action"])) && ($tmp_action_type = clean_input(trim($_GET["action"]), "alphanumeric"))) {
	$ACTION	= $tmp_action_type;
} elseif((isset($_POST["action"])) && ($tmp_action_type = clean_input(trim($_POST["action"]), "alphanumeric"))) {
	$ACTION	= $tmp_action_type;
}

if ((isset($_GET["type"])) && ($tmp_type = clean_input(trim($_GET["type"]), "alphanumeric"))) {
	$TYPE	= $tmp_type;
} elseif((isset($_POST["type"])) && ($tmp_action_type = clean_input(trim($_POST["type"]), "alphanumeric"))) {
	$TYPE	= $tmp_type;
} else {
	$TYPE	= "default";
}


unset($tmp_action_type);
unset($tmp_type);
unset($tmp_poll);
if(isset($EVALUATION_ID) && isset($ACTION)) {
	ob_clear_open_buffers();

	switch($ACTION) {
		case "memberlist":
			//Figure out the organisation, group, and role requested
			if(isset($_POST["ogr"])) {
				$pieces = explode('|', $_POST["ogr"]);
				if(isset($pieces[0])) {
					$ORGANISATION_ID = clean_input($pieces[0], array("trim", "int"));
				}
				if(isset($pieces[1])) {
					$GROUP = clean_input($pieces[1], array("trim", "alphanumeric"));
				}
				if(isset($pieces[2])) {
					$ROLE = clean_input($pieces[2], array("trim", "alphanumeric"));
				}
			}

						$nmembers_results		= false;
                                                if($GROUP == 'course'){
                                                $nmembers_query = " SELECT `course_id` AS `proxy_id`, CONCAT_WS(', ', `course_name`, `course_code`) AS `fullname`, `course_name` as username, `organisation_id`, 'course' as `group`, 'course' as `role`
                                                                            FROM `courses`";
                                                }else{
						$nmembers_query	= "	SELECT a.`id` AS `proxy_id`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, a.`username`, a.`organisation_id`, b.`group`, b.`role`
											FROM `".AUTH_DATABASE."`.`user_data` AS a
											LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
											ON a.`id` = b.`user_id`
											WHERE
											a.`organisation_id` = ".$db->qstr($ORGANISATION_ID)."
											AND b.`group` = ".$db->qstr($GROUP)."
											".($ROLE != 'all' ? "AND b.`role` = ".$db->qstr($ROLE) : "")."
											AND b.`app_id` IN (".AUTH_APP_IDS_STRING.")
											AND b.`account_active` = 'true'
											AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
											AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")
											GROUP BY a.`id`
											ORDER BY a.`lastname` ASC, a.`firstname` ASC";

                                                }
							

						//Fetch list of current members
						$current_member_list	= array();
                                                switch ($GROUP) {
                                                        case "course" :
                                                            $query = "SELECT `target_value`, `target_id` FROM `evaluation_targets` WHERE `evaluation_id` = ".$db->qstr($EVALUATION_ID)." AND `target_id` = 1";
                                                        break;
                                                        default :
                                                            $query = "SELECT `target_value`, `target_id` FROM `evaluation_targets` WHERE `evaluation_id` = ".$db->qstr($EVALUATION_ID)." AND `target_id` != 1";
                                                        break;
                                                }
                                                
                                                $results	= $db->GetAll($query);
						if($results) {
							foreach($results as $result) {
								if($proxy_id = (int) $result["target_value"]) {
									$current_member_list[] = $proxy_id;
								}
							}
						}
						

						//
						if($nmembers_query != "") {
							$nmembers_results = $db->GetAll($nmembers_query);
							if($nmembers_results) {
								$members = array(array('text' => "$GROUP > $ROLE", 'value'=>$GROUP.$ROLE, 'options'=>array(), 'disabled'=>false, 'category'=>'true'));
								foreach($nmembers_results as $member) {
									if(in_array($member['proxy_id'], $current_member_list)) {
										$registered = true;
									} else {
										$registered = false;
									}
                                                                        if ($registered) {
                                                                            $members[0]['options'][] = array('text' => $member['fullname'].($registered ? ' (already a member)' : ''), 'value' => $member['proxy_id'], 'disabled' => $registered);
                                                                        } else {
                                                                            $members[0]['options'][] = array('text' => $member['fullname'], 'value' => $member['proxy_id'], 'disabled' => false, 'checked' => ($in_poll ? "checked=\"checked\"" : ""));
                                                                        }
								}

								foreach($members[0]['options'] as $key => $member) {
									if(isset($member['options']) && is_array($member['options']) && !empty($member['options'])) {
										//Alphabetize members
										sort($members[0]['options'][$key]['options']);
									}
								}

								echo '<table cellspacing="0" cellpadding="0" class="select_multiple_table" width="100%">';
								echo lp_multiple_select_table($members, 0, 0, true);
								echo '</table>';
							} else {
								echo "No One Available [1]";
							}
						} else {
							echo "No One Available [2]";
						}
			break;
	}
}
exit();