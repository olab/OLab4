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
 * This module allows authenticated users to search the user database for
 * specific people, or browse faculty by department / students by year, etc.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
 * @version $Id: people.inc.php 1171 2010-05-01 14:39:27Z ad29 $
*/
if (!defined("PARENT_INCLUDED")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->amIAllowed('people', 'read')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	/**
	 * Meta information for this page if they are able to use this module.
	 */
	$PAGE_META["title"]			= "People Search";
	$PAGE_META["description"]	= "Allowing you to search the School of Medicine for a specific person or people.";
	$PAGE_META["keywords"]		= "";

	$is_administrator = $ENTRADA_ACL->amIallowed('user', 'update');
	
	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/people", "title" => "People Search");
	
	$PROCESSED		= array();
	$PREFERENCES	= preferences_load($MODULE);

	$ORGANISATION_ID = $ENTRADA_USER->getActiveOrganisation();
	$organisation_query = "SELECT * FROM `".AUTH_DATABASE."`.`organisations`";
	$ORGANISATIONS = $db->GetAll($organisation_query);
	$ORGANISATION_BY_ID = array();
	foreach($ORGANISATIONS as $o) {
		$ORGANISATIONS_BY_ID[$o["organisation_id"]] = $o;
	}
	$search_query	= "";
	$plaintext_query = "";
	$year_offset = (strtotime("July 15th, ".date("Y", time())) < time() ? 1 : 0);
	
	$active_tab = "";
	if (isset($_GET["active_tab"]) && $active_tab = clean_input($_GET["active_tab"], array("trim", "url"))) {		
		$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["active_tab"] = $active_tab;
	} else {
		if (isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["active_tab"]) && $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["active_tab"]) {
			$active_tab = $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["active_tab"];
		}
	}
	
	/**
	 * Update requsted number of profiles per page.
	 * Valid: any integer really.
	 */
	if (((isset($_POST["pp"])) && ($integer = (int) trim($_POST["pp"]))) || ((isset($_GET["pp"])) && ($integer = (int) trim($_GET["pp"])))) {
		if (($integer > 0) && ($integer <= 250)) {
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"] = $integer;
		}
	
		$_SERVER["QUERY_STRING"] = replace_query(array("pp" => false));
	} else {
		if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"])) {
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"] = 5;
		}
	}

	/**
	 * The query that is actually be searched for.
	 */

	if ((isset($_POST["id"])) && (trim($_POST["id"]))) {
		$load_profile = clean_input($_POST["id"], "int");
	} elseif ((isset($_GET["id"])) && (trim($_GET["id"]))) {
		$load_profile = clean_input($_GET["id"], "int");
	}

	if ((isset($_POST["profile"])) && (trim($_POST["profile"]))) {
		$load_profile = clean_input($_POST["profile"], array("credentials"));
	} elseif ((isset($_GET["profile"])) && (trim($_GET["profile"]))) {
		$load_profile = clean_input($_GET["profile"], array("credentials"));
	}

	if (isset($load_profile) && $load_profile) {

		$query_profile	= "
						SELECT a.*, b.`group`, b.`role`, b.`organisation_id`
						FROM `".AUTH_DATABASE."`.`user_data` AS a
						LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
						ON b.`user_id` = a.`id`
						WHERE  b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
						AND b.`account_active` = 'true'
						AND (b.`access_starts` = '0' OR b.`access_starts` < ".$db->qstr(time()).")
						AND (b.`access_expires` = '0' OR b.`access_expires` >= ".$db->qstr(time()).")
						AND ".((is_numeric($load_profile)) ? "a.`id` = ".$db->qstr((int) $load_profile) : "a.`username` = ".$db->qstr($load_profile));
	}
	
	/**
	 * Determine the type of search that is requested.
	 */
	if ((isset($_POST["type"])) && (in_array(trim($_POST["type"]), array("search", "browse-group", "browse-dept")))) {
		$search_type = clean_input($_POST["type"], "trim");	
	} elseif ((isset($_GET["type"])) && (in_array(trim($_GET["type"]), array("search", "browse-group", "browse-dept")))) {
		$search_type = clean_input($_GET["type"], "trim");	
	}
	
	if (isset($search_type) && $search_type) {
		switch ($search_type) {
			case "browse-group" :
				$PROCESSED["organisation"]	= false;
				$PROCESSED["group"]			= false;
				$PROCESSED["role"]			= false;
				
				if ((isset($_POST["g"])) && (isset($SYSTEM_GROUPS[$group = clean_input($_POST["g"], "credentials")]))) {
					$PROCESSED["group"]	= $group;
					$search_query_text	= html_encode(ucwords($group));

					if (($PROCESSED["group"] == "student") && (isset($_POST["r"])) && ($role = clean_input($_POST["r"], "alphanumeric"))) {
						$PROCESSED["role"] = $role;
						
						$search_query_text	.= " &rArr; ".html_encode(ucwords($role));
					}
					
					$search_query = $search_query_text;
					
				} elseif ((isset($_GET["g"])) && (isset($SYSTEM_GROUPS[$group = clean_input($_GET["g"], "credentials")]))) {
					$PROCESSED["group"]	= $group;
					$search_query_text	= html_encode(ucwords($group));
					
					if (($PROCESSED["group"] == "student") && (isset($_GET["r"])) && ($role = clean_input($_GET["r"], "alphanumeric"))) {
						$PROCESSED["role"] = $role;
						
						$search_query_text	.= " &rArr; ".html_encode(ucwords($role));
					}
					
					$search_query = $search_query_text;
					
				} else {
					$ERROR++;
					$ERRORSTR[] = "To browse a group, you must select a group from the group select list.";	
				}
				
				if(($organisation = $ENTRADA_USER->getActiveOrganisation()) && isset($ORGANISATIONS_BY_ID[$organisation])) {
					$PROCESSED["organisation"] = $organisation;
					$search_query .= " in ".$ORGANISATIONS_BY_ID[$organisation]["organisation_title"];
				} else {
					$ERROR++;
					$ERRORSTR[] = "To browse a group, you must select a organisation from the organisation select list.";
				}
				
				if (!$ERROR) {
					if ($PROCESSED["group"] != "student") {
						$query_search	= "SELECT a.*, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, b.`account_active`, b.`access_starts`, b.`access_expires`, b.`last_login`, b.`role`, b.`group`
											FROM `".AUTH_DATABASE."`.`user_data` AS a
											LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
											ON b.`user_id` = a.`id`
											AND b.`app_id` IN (".AUTH_APP_IDS_STRING.")
											WHERE b.`app_id` IN (".AUTH_APP_IDS_STRING.")
											AND b.`organisation_id` = ".$db->qstr($PROCESSED["organisation"])."
											AND b.`group` ".($PROCESSED["group"] == "staff" ? "IN ('staff', 'medtech')" : "= ".$db->qstr($PROCESSED["group"]))."
											".(($PROCESSED["role"]) ? "AND b.`role` = ".$db->qstr($PROCESSED["role"]) : "")."
											GROUP BY a.`id`
											ORDER BY `fullname` ASC
											LIMIT %s, %s";
						$query_count	= "SELECT a.*, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, b.`account_active`, b.`access_starts`, b.`access_expires`, b.`last_login`, b.`role`, b.`group`
											FROM `".AUTH_DATABASE."`.`user_data` AS a
											LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
											ON b.`user_id` = a.`id`
											AND b.`app_id` IN (".AUTH_APP_IDS_STRING.")
											WHERE b.`app_id` IN (".AUTH_APP_IDS_STRING.")
											AND b.`organisation_id` = ".$db->qstr($PROCESSED["organisation"])."
											AND b.`group` ".($PROCESSED["group"] == "staff" ? "IN ('staff', 'medtech')" : "= ".$db->qstr($PROCESSED["group"]))."
											".(($PROCESSED["role"]) ? "AND b.`role` = ".$db->qstr($PROCESSED["role"]) : "")."
											GROUP BY a.`id`
											ORDER BY `fullname` ASC";
					} else {
						$search_query = groups_get_name($PROCESSED["role"])." in ".$ORGANISATIONS_BY_ID[$organisation]["organisation_title"];
						$query_search	= "SELECT a.*, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, b.`account_active`, b.`access_starts`, b.`access_expires`, b.`last_login`, b.`role`, b.`group`
											FROM `".AUTH_DATABASE."`.`user_data` AS a
											LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
											ON b.`user_id` = a.`id`
											AND b.`app_id` IN (".AUTH_APP_IDS_STRING.")
											INNER JOIN `group_members` AS c
											ON a.`id` = c.`proxy_id`
											AND c.`member_active` = 1
											JOIN `groups` AS d
											ON c.`group_id` = d.`group_id`
											AND d.`group_active` = 1
											WHERE b.`app_id` IN (".AUTH_APP_IDS_STRING.")
											AND b.`organisation_id` = ".$db->qstr($PROCESSED["organisation"])."
											AND c.`group_id` = ".$db->qstr($PROCESSED["role"])."
											GROUP BY a.`id`
											ORDER BY `fullname` ASC
											LIMIT %s, %s";
						$query_count	= "SELECT a.*, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, b.`account_active`, b.`access_starts`, b.`access_expires`, b.`last_login`, b.`role`, b.`group`
											FROM `".AUTH_DATABASE."`.`user_data` AS a
											LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
											ON b.`user_id` = a.`id`
											AND b.`app_id` IN (".AUTH_APP_IDS_STRING.")
											JOIN `group_members` AS c
											ON a.`id` = c.`proxy_id`
											AND c.`member_active` = 1
											JOIN `groups` AS d
											ON c.`group_id` = d.`group_id`
											AND d.`group_active` = 1
											WHERE b.`app_id` IN (".AUTH_APP_IDS_STRING.")
											AND b.`organisation_id` = ".$db->qstr($PROCESSED["organisation"])."
											AND c.`group_id` = ".$db->qstr($PROCESSED["role"])."
											GROUP BY a.`id`
											ORDER BY `fullname` ASC";
					}
				}
			break;
			case "browse-dept" :
				$browse_dept = 0;
				
				if ((isset($_POST["d"])) && ($department = clean_input($_POST["d"], array("trim", "int")))) {
					$query	= "	SELECT a.`department_id`, a.`department_title`, a.`organisation_id`, b.`entity_title`, c.`organisation_title`
								FROM `".AUTH_DATABASE."`.`departments` AS a
								LEFT JOIN `".AUTH_DATABASE."`.`entity_type` AS b
								ON a.`entity_id` = b.`entity_id`
								LEFT JOIN `".AUTH_DATABASE."`.`organisations` AS c
								ON a.`organisation_id` = c.`organisation_id`
								WHERE a.`department_id` = ".$db->qstr($department)."
								ORDER BY c.`organisation_title` ASC, a.`department_title`";
					$result = $db->GetRow($query);
					if ($result) {
						$browse_department	= $department;
						$search_query_text	= html_encode($result["department_title"]);
						$search_query		= $search_query_text;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The department you have provided does not exist. Please ensure that you select a valid department from the department list.";
					}
				} elseif ((isset($_GET["d"])) && ($department = clean_input($_GET["d"], array("trim", "int")))) {
					$query	= "	SELECT a.`department_id`, a.`department_title`, a.`organisation_id`, b.`entity_title`, c.`organisation_title`
								FROM `".AUTH_DATABASE."`.`departments` AS a
								LEFT JOIN `".AUTH_DATABASE."`.`entity_type` AS b
								ON a.`entity_id` = b.`entity_id`
								LEFT JOIN `".AUTH_DATABASE."`.`organisations` AS c
								ON a.`organisation_id` = c.`organisation_id`
								WHERE a.`department_id` = ".$db->qstr($department)."
								ORDER BY c.`organisation_title` ASC, a.`department_title`";
					$result = $db->GetRow($query);
					if ($result) {
						$browse_department	= $department;
						$search_query_text	= html_encode($result["department_title"]);
						$search_query		= $search_query_text;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The department you have provided does not exist. Please ensure that you select a valid department from the department list.";
					}
				} else {
					$ERROR++;
					$ERRORSTR[] = "To browse a department, you must select a department from the department selection list.";	
				}
				
				if (!$ERROR) {
					
					$query_search	= "	SELECT a.*, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, b.`account_active`, b.`access_starts`, b.`access_expires`, b.`last_login`, b.`role`, b.`group`
										FROM `".AUTH_DATABASE."`.`user_data` AS a
										LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
										ON b.`user_id` = a.`id`
										AND b.`app_id` IN (".AUTH_APP_IDS_STRING.")
										LEFT JOIN `".AUTH_DATABASE."`.`user_departments` AS c
										ON c.`user_id` = a.`id`
										WHERE b.`app_id` IN (".AUTH_APP_IDS_STRING.")
										AND c.`dep_id` = ".$db->qstr($browse_department)."
										GROUP BY a.`id`
										ORDER BY `fullname` ASC
										LIMIT %s, %s";
					$query_count	= "	SELECT a.*, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, b.`account_active`, b.`access_starts`, b.`access_expires`, b.`last_login`, b.`role`, b.`group`
										FROM `".AUTH_DATABASE."`.`user_data` AS a
										LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
										ON b.`user_id` = a.`id`
										AND b.`app_id` IN (".AUTH_APP_IDS_STRING.")
										LEFT JOIN `".AUTH_DATABASE."`.`user_departments` AS c
										ON c.`user_id` = a.`id`
										WHERE b.`app_id` IN (".AUTH_APP_IDS_STRING.")
										AND c.`dep_id` = ".$db->qstr($browse_department)."
										GROUP BY a.`id`
										ORDER BY `fullname` ASC";
				}
			break;
			case "search" :
			default :
				$group_string			= "";
				$role_string			= "";
				if ((isset($_REQUEST["q"])) && ($query = clean_input($_REQUEST["q"], array("trim", "notags")))) {
					$search_query		= $query;
					$plaintext_query	= $search_query;
					$search_query_text	= html_encode($query);
				}
				
				if (isset($_REQUEST["search_groups"]) && ($search_groups = explode(",", $_REQUEST["search_groups"]))) {
					foreach ($search_groups as $group) {
						if ($group_string && ($group = clean_input($group, "credentials"))) {
							$group_string .= ", ".$db->qstr($group);
							if ($group == "staff") {
								$group_string .= ", 'medtech'";
							}
						} elseif (($group = clean_input($group, "credentials"))) {
							$group_string = $db->qstr($group);
							if ($group == "staff") {
								$group_string .= ", 'medtech'";
							}
						}
					}
				} else {
					$group_string = "'staff', 'medtech', 'faculty', 'resident'";
				}
				
				if (isset($_REQUEST["search_classes"]) && ($search_classes = explode(",", $_REQUEST["search_classes"]))) {
					foreach ($search_classes as $class) {
						if ($role_string && ($role = clean_input($class, "credentials"))) {
							$role_string .= ", ".$db->qstr($role);
						} elseif (($role = clean_input($class, "credentials"))) {
							$role_string = $db->qstr($role);
						}
					}
				} else {
					$role_string = "'".(date("Y", time()) + $year_offset)."', '".(date("Y", time()) + $year_offset + 1)."', '".(date("Y", time()) + $year_offset + 2)."', '".(date("Y", time()) + $year_offset + 3)."'";
				}
				
				if (isset($_REQUEST["search_alumni"]) && $_REQUEST["search_alumni"]) {
					$query = "	SELECT UNIQUE(`role`) FROM `".AUTH_DATABASE."`.`user_access`
								WHERE `group` = 'student'
								AND `role` < ".$db->qstr((date("Y", time()) + $year_offset));
					$roles = $db->GetAll($query);
					if ($roles) {
						foreach ($roles as $role) {
							if ($role_string) {
								$role_string .= ", ".$db->qstr($role["role"]);
							} else {
								$role_string = $db->qstr($role["role"]);
							}
						}
					}
				}

				$query_search	= "	SELECT a.*, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, b.`account_active`, b.`access_starts`, b.`access_expires`, b.`last_login`, b.`role`, b.`group`
									FROM `".AUTH_DATABASE."`.`user_data` AS a
									LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
									ON b.`user_id` = a.`id`
									AND b.`app_id` IN (".AUTH_APP_IDS_STRING.")
									WHERE b.`organisation_id` IN (" . $ENTRADA_USER->getActiveOrganisation() . ")
									AND (a.`number` LIKE ".$db->qstr("%%".str_replace("%", "", $search_query)."%%")."
									OR a.`username` LIKE ".$db->qstr("%%".str_replace("%", "", $search_query)."%%")."
									OR a.`email` LIKE ".$db->qstr("%%".str_replace("%", "", $search_query)."%%")."
									OR CONCAT_WS(' ', a.`firstname`, a.`lastname`) LIKE ".$db->qstr("%%".str_replace("%", "", $search_query)."%%").")
									GROUP BY a.`id`
									ORDER BY `fullname` ASC
									LIMIT %s, %s";
				
				$query_count	= "	SELECT a.*, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, b.`account_active`, b.`access_starts`, b.`access_expires`, b.`last_login`, b.`role`, b.`group`
									FROM `".AUTH_DATABASE."`.`user_data` AS a
									LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
									ON b.`user_id` = a.`id`
									AND b.`app_id` IN (".AUTH_APP_IDS_STRING.")
									WHERE b.`app_id` IN (".AUTH_APP_IDS_STRING.")
									AND a.`organisation_id` IN (" . $ORGANISATION_ID . ")
									AND (a.`number` LIKE ".$db->qstr("%%".str_replace("%", "", $search_query)."%%")."
									OR a.`username` LIKE ".$db->qstr("%%".str_replace("%", "", $search_query)."%%")."
									OR a.`email` LIKE ".$db->qstr("%%".str_replace("%", "", $search_query)."%%")."
									OR CONCAT_WS(' ', a.`firstname`, a.`lastname`) LIKE ".$db->qstr("%%".str_replace("%", "", $search_query)."%%").")
									GROUP BY a.`id`
									ORDER BY `fullname` ASC, FIELD(b.`app_id`, ".AUTH_APP_IDS_STRING.")";
			break;
		}

		$results	= $db->GetAll($query_count);
		/**
		 * Get the total number of results using the generated queries above and calculate the total number
		 * of pages that are available based on the results per page preferences.
		 */
		$result 	= count($results);
		if ($result) {
			$total_rows	= $result;

			if ($total_rows <= $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"]) {
				$total_pages = 1;
			} elseif (($total_rows % $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"]) == 0) {
				$total_pages = (int) ($total_rows / $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"]);
			} else {
				$total_pages = (int) ($total_rows / $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"]) + 1;
			}
		} else {
			$total_rows		= 0;
			$total_pages	= 1;
		}

		/**
		 * Check if pv variable is set and see if it's a valid page, other wise page 1 it is.
		 */
		if (isset($_POST["pv"])) {
			$page_current = (int) trim($_POST["pv"]);
	
			if (($page_current < 1) || ($page_current > $total_pages)) {
				$page_current = 1;
			}
		} elseif (isset($_GET["pv"])) {
			$page_current = (int) trim($_GET["pv"]);
	
			if (($page_current < 1) || ($page_current > $total_pages)) {
				$page_current = 1;
			}
		} else {
			$page_current = 1;
		}	

		$page_previous	= (($page_current > 1) ? ($page_current - 1) : false);
		$page_next		= (($page_current < $total_pages) ? ($page_current + 1) : false);
	}
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/selectchained.js\"></script>\n";
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/picklist.js\"></script>\n";

	/**
	 * Check if preferences need to be updated on the server at this point.
	 */
	preferences_update($MODULE, $PREFERENCES);
	$existcohorts = true;
	$student_classes = array();
	$active_cohorts = groups_get_active_cohorts($ENTRADA_USER->getActiveOrganisation());
	if (isset($active_cohorts) && !empty($active_cohorts)) {
		$existcohorts = true;
		foreach ($active_cohorts as $cohort) {
			$student_classes[$cohort["group_id"]] = $cohort["group_name"];
		}
	}else{
		$existcohorts = false;
	}
	
	$browse_people		= array();
	$browse_people[]	= array(
							"value"		=> "student",
							"title"		=> "Browse Students",
							"options"	=> (sizeof($student_classes) > 0) ? $student_classes : array("student" => "Show All Students")
							);
	$browse_people[]	= array(
							"value"		=> "resident",
							"title"		=> "Browse Residents",
							"options"	=> array("resident" => "Show All Residents")
							);
	$browse_people[]	= array(
							"value"		=> "faculty",
							"title"		=> "Browse Faculty",
							"options"	=> array("faculty" => "Show All Faculty")
							);
	$browse_people[]	= array(
							"value"		=> "staff",
							"title"		=> "Browse Staff",
							"options"	=> array("staff" => "Show All Staff")
							);

	$i = count($HEAD);
	$HEAD[$i]  = "<script type=\"text/javascript\">\n";
	$HEAD[$i] .= "addListGroup('account_type', 'cs-top');\n";

	if (is_array($browse_people)) {
		foreach ($browse_people as $key => $result) {
				$HEAD[$i] .= "addList('cs-top', '".$result["title"]."', '".$result["value"]."', 'cs-sub-".$key."', ".(((isset($PROCESSED["group"])) && ($PROCESSED["group"] == $result["value"])) ? "1" : "0").");\n";
				if (is_array($result["options"]) && sizeof($result["options"])>0) {
					foreach ($result["options"] as $option => $value) {
						$HEAD[$i] .= "addOption('cs-sub-".$key."', '".$value."', '".$option."', ".(((isset($PROCESSED["role"])) && ($PROCESSED["role"] == $option)) ? "1" : "0").");\n";
					}
				}
		}
	}
	$HEAD[$i] .= "</script>\n";

	$ONLOAD[] = "initListGroup('account_type', $('group'), $('role'))";
	
	if ($ERROR) {
		echo display_error();
	}
	
	if ($NOTICE) {
		echo display_notice();	
	}
	?>
	<div class="tabbable" id="people-search-tabs">
		<ul class="nav nav-tabs">
			<li class="active"><a href="#people_search_tab" data-toggle="tab">People Search</a></li>
			<li><a href="#browse_group_tab" data-toggle="tab">Browse People</a></li>
			<li><a href="#browse_dept_tab" data-toggle="tab">Browse Departments</a></li>
		</ul>
		<div class="tab-content ps-tab-style">
		<div class="tab-pane active" id="people_search_tab">
			<?php
			if ((isset($_REQUEST["search_groups"]) && $_REQUEST["search_groups"] != "faculty,resident,staff") || (isset($_REQUEST["search_classes"]) && $_REQUEST["search_classes"] != "2010,2011,2012,2013") || (isset($_REQUEST["search_alumni"]) && $_REQUEST["search_alumni"])) {
				$ONLOAD[] = "toggle_search('advanced')";
			} else {
				$ONLOAD[] = "toggle_search('basic')";
			}
			?>
			<script type="text/javascript">
			function toggle_search(searchType) {
				$('target-basic-mode').hide();
				$('target-advanced-mode').hide();
				$('advanced_search').hide();

				if (searchType == 'advanced') {
					$('target-advanced-mode').show();
					$('advanced_search').show();

				} else {
					$('target-basic-mode').show();
				}
			}
				jQuery(document).ready(function($) {
					$('a[data-toggle="tab"]').on('shown', function (e) {
						//save the active tab.
						$('input[name="active_tab"]').val($(e.target).attr('href'));
					});

					//show the active tab if it exists.  Defaults to People Search.
					var active_tab = <?php echo "'" . $active_tab . "';\n" ; ?>
					if (active_tab) {
						$('a[href="' + active_tab + '"]').tab('show');
					} 
				});
			</script>
			<form id="search_form" class="form-search form-horizontal" action="<?php echo ENTRADA_URL; ?>/people" method="get">
				<input type="hidden" name="pv" id="search_pv" value="<?php echo ($page_current ? $page_current : 1);?>" />
				<input type="hidden" name="pp" id="search_pp" value="<?php echo $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"]; ?>" />
				<input type="hidden" name="type" value="search" />
				<input type="hidden" name="active_tab" value="" />
				<div class="input-append space-right">
					<input type="text" class="input-xxlarge search-query" placeholder="<?php echo $translate->_("Search for name, username, email address or staff / student number."); ?>" id="q" name="q" value="<?php echo html_encode($plaintext_query); ?>" />
					<button class="btn btn-default" type="submit"><i class="icon-search"></i> Search</button>
				</div>

				<a id="target-advanced-mode" onclick="toggle_search('basic')" style="display: none" href="#">
					<i class="fa fa-caret-up"></i> Basic Search
				</a>
				<a id="target-basic-mode" onclick="toggle_search('advanced')" href="#">
					<i class="fa fa-caret-down"></i> Advanced Search
				</a>

				<div id="advanced_search">
					<input type="hidden" name="search_groups" id="search_groups" value="<?php echo (isset($_GET["search_groups"]) ? $_GET["search_groups"] : "faculty,resident,staff"); ?>" />
					<input type="hidden" name="search_organisations" id="search_organisations" value="<?php echo (isset($_GET["search_organisations"]) ? $_GET["search_organisations"] : $ORGANISATION_ID); ?>" />
					<input type="hidden" name="search_classes" id="search_classes" value="<?php echo (isset($_GET["search_classes"]) ? $_GET["search_classes"] : (date("Y", time()) + $year_offset).",".(date("Y", time()) + $year_offset + 1).",".(date("Y", time()) + $year_offset + 2).",".(date("Y", time()) + $year_offset + 3)); ?>" />

					<div class="row-fluid space-above">
						<div class="span2">
							<label class="form-required pull-right"><?php echo $translate->_("Search within:"); ?></label>
							<script type="text/javascript">
								function addSomething(which) {
									$('search_'+which).value = "0";
									$$('.search_'+which).each( function (e) {
										if (e.checked) {
											if ($('search_'+which).value != '0') {
												$('search_'+which).value += ","+e.value;
											} else {
												$('search_'+which).value = e.value;
											}
										}
									});
								}
								function addClass() {
									addSomething('classes');
								}
								function addGroup() {
									addSomething('groups');
								}
								function addOrganisation() {
									addSomething('organisations');
								}
							</script>
						</div>

						<div class="span10">
							<div class="row-fluid">
								<label class="span3 checkbox content-small" for="alumni"><input id="alumni" type="checkbox" <?php echo (isset($_REQUEST["search_alumni"]) && $_REQUEST["search_alumni"] ? "checked=\"checked\" " : ""); ?>value="1" name="search_alumni" /> Alumni</label>

								<label class="span3 checkbox content-small" for="faculty"><input class="search_groups" id="faculty" type="checkbox" <?php echo ((isset($_REQUEST["search_groups"]) && is_array(explode(',', $_REQUEST["search_groups"])) && array_search("faculty", (explode(',', $_REQUEST["search_groups"]))) !== false) || (isset($_REQUEST["search_groups"]) && $_REQUEST["search_groups"] == "faculty") || (!isset($_REQUEST["search_groups"]) && !isset($_REQUEST["search_classes"]) && !isset($_REQUEST["search_alumni"])) ? "checked=\"checked\" " : ""); ?>value="faculty" onclick="addGroup()" /> Faculty</label>

								<label class="span3 checkbox content-small" for="resident"><input class="search_groups" id="resident" type="checkbox" <?php echo ((isset($_REQUEST["search_groups"]) && is_array(explode(',', $_REQUEST["search_groups"])) && array_search("resident", (explode(',', $_REQUEST["search_groups"]))) !== false) || (isset($_REQUEST["search_groups"]) && $_REQUEST["search_groups"] == "resident") || (!isset($_REQUEST["search_groups"]) && !isset($_REQUEST["search_classes"]) && !isset($_REQUEST["search_alumni"])) ? "checked=\"checked\" " : ""); ?>value="resident" onclick="addGroup()" /> Residents</label>

								<label class="span3 checkbox content-small" for="staff"><input class="search_groups" id="staff" type="checkbox" <?php echo ((isset($_REQUEST["search_groups"]) && is_array(explode(',', $_REQUEST["search_groups"])) && array_search("staff", (explode(',', $_REQUEST["search_groups"]))) !== false) || (isset($_REQUEST["search_groups"]) && $_REQUEST["search_groups"] == "staff") || (!isset($_REQUEST["search_groups"]) && !isset($_REQUEST["search_classes"]) && !isset($_REQUEST["search_alumni"])) ? "checked=\"checked\" " : ""); ?>value="staff" onclick="addGroup()" /> Staff</label>
							</div>

							<div class="row-fluid">
								<?php
									if ($existcohorts) {
										foreach ($active_cohorts as $cohort) {
											echo "<label class=\"span3 checkbox content-small\" for=class_\"" . strtolower(str_replace(' ', '_', $cohort["group_id"])) . "\"><input class=\"search_classes\" id=\"class_" . strtolower(str_replace(' ', '_', $cohort["group_id"])) . "\" type=\"checkbox\" " . ((isset($_REQUEST["search_classes"]) && is_array(explode(',', $_REQUEST["search_classes"])) && array_search($cohort["group_id"], (explode(',', $_REQUEST["search_classes"]))) !== false) || (isset($_REQUEST["search_classes"]) && $_REQUEST["search_classes"] == $cohort["group_id"]) || (!isset($_REQUEST["search_groups"]) && !isset($_REQUEST["search_classes"]) && !isset($_REQUEST["search_alumni"])) ? "checked=\"checked\" " : "") . "value=\"" . $cohort["group_id"] . "\" onclick=\"addClass()\" />" . $cohort["group_name"] . "</label>";
										}
									}
								?>
							</div>
						</div>

					</div>
				</div>
			</form>
		</div>
		<div class="tab-pane" id="browse_group_tab">
			<form id="browse-group_form" action="<?php echo ENTRADA_URL; ?>/people" method="get" class="form-horizontal">
				<input name="type" value="browse-group" type="hidden">
				<input name="pv" id="browse-group_pv" value="1" type="hidden">
				<input name="pp" id="browse-group_pp" value="50" type="hidden">
				<input name="active_tab" value="#browse_group_tab" type="hidden">
				<div class="control-group">
					<label for="group" class="form-required control-label">Browse Group:</label>
					<div class="controls">
						<select id="group" name="g" class="ps-group-select span5"></select>
					</div>
				</div>
				<div class="control-group">
					<label for="role" class="control-label form-required">Browse Role:</label>
					<div class="controls">
						<select id="role" name="r" class="ps-role-select span5"></select>
					</div>
				</div>
				<div class="control-group">
					<div class="controls">
						<button class="btn btn-primary" type="submit"><i class="icon-search icon-white"></i> Browse People</button>
					</div>
				</div>
			</form>
		</div>
		<div class="tab-pane" id="browse_dept_tab">
			<form id="browse-dept_form" action="<?php echo ENTRADA_URL; ?>/people" method="get" class="form-horizontal">
			<input type="hidden" name="type" value="browse-dept" />
			<input type="hidden" name="pv" id="browse-dept_pv" value="<?php echo ($page_current ? $page_current : 1);?>" />
			<input type="hidden" name="pp" id="browse-dept_pp" value="<?php echo $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"]; ?>" />
			<input type="hidden" name="active_tab" value="" />
			
			<div class="row-fluid">
				<div class="span9">
					<div class="control-group">
						<label for="department" class="control-label form-required">Browse Department:</label>
						<div class="controls">
							<select id="department" name="d" class="ps-department-select">
								<?php
								$query = "	SELECT a.`department_id`, a.`department_title`, a.`organisation_id`, b.`entity_title`, c.`organisation_title`
								FROM `".AUTH_DATABASE."`.`departments` AS a
								LEFT JOIN `".AUTH_DATABASE."`.`entity_type` AS b
								ON a.`entity_id` = b.`entity_id`
								LEFT JOIN `".AUTH_DATABASE."`.`organisations` AS c
								ON a.`organisation_id` = c.`organisation_id`
								WHERE a.`department_active` = '1' AND a.`organisation_id` = ".$ORGANISATION_ID."
								ORDER BY c.`organisation_title` ASC, a.`department_title`";
								$results	= $db->GetAll($query);
								if ($results) {
									$organisation_title = "";

									foreach ($results as $key => $result) {
										if ($organisation_title != $result["organisation_title"]) {
											if ($key) {
												echo "</optgroup>";
											}
											echo "<optgroup label=\"".html_encode($result["organisation_title"])."\">";

											$organisation_title = $result["organisation_title"];
										}

										echo "<option value=\"".(int) $result["department_id"]."\"".(((isset($browse_department)) && ((int) $browse_department) && ($browse_department == $result["department_id"])) ? " selected=\"selected\"" : "").">".html_encode($result["department_title"])."</option>\n";
									}
									echo "</optgroup>";
								}
								?>
							</select>
						</div>
					</div>
				</div>
				<div class="span3">
					<button class="btn btn-primary pull-right" type="submit"><i class="icon-search icon-white"></i> Browse Departments</button>
				</div>
			</div>

			</form>
		</div>
	</div>
	</div>
	<?php
	if (($search_query) || (isset($load_profile) && $load_profile)) {
		if ($search_query) {
			if ($total_pages > 1) {
                $pagination = new Entrada_Pagination($page_current, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"], $total_rows, ENTRADA_URL."/".$MODULE, replace_query());
                echo $pagination->GetPageBar();
			}
			/**
			 * Provides the first parameter of MySQLs LIMIT statement by calculating which row to start results from.
			 */
			$limit_parameter 	= (int) (($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"] * $page_current) - $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"]);

			$query_search		= sprintf($query_search, $limit_parameter, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"]);
			$results			= $db->GetAll($query_search);
		} elseif ($load_profile) {
			$results			= $db->GetAll($query_profile);
			if (!$results) {
				$query_profile	= "
								SELECT a.*, c.`country`, d.`province`, b.`group`, b.`role`, b.`organisation_id`
								FROM `".AUTH_DATABASE."`.`user_data` AS a
								LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
								LEFT JOIN `global_lu_countries` AS c
								ON c.`countries_id` = a.`country_id`
								LEFT JOIN `global_lu_provinces` AS d
								ON d.`province_id` = a.`province_id`
								ON b.`user_id` = a.`id`
								WHERE  b.`app_id` IN (".AUTH_APP_IDS_STRING.")
								AND b.`account_active` = 'true'
								AND (b.`access_starts` = '0' OR b.`access_starts` < ".$db->qstr(time()).")
								AND (b.`access_expires` = '0' OR b.`access_expires` >= ".$db->qstr(time()).")
								AND ".((is_numeric($load_profile)) ? "a.`id` = ".$db->qstr((int) $load_profile) : "a.`username` = ".$db->qstr($load_profile))."
								GROUP BY a.`id`";
				echo $query_profile;
				$results		= $db->GetAll($query_profile);
			}
			$search_query		= $load_profile;
			$total_rows 		= 1;
			$limit_parameter	= 5;
			$total_pages		= 1;
		}
		
		if ($results) {
			echo "<div class=\"row-fluid ps-search-summary-bar\">\n";
			echo "	<div class=\"span3 ps-search-result-title ps-vertical-margins\">" . $translate->_("Search Results:") . "</div>\n";
			echo "	<div class=\"span9 ps-search-result-summary ps-vertical-margins\"><span class=\"pull-right\">".$total_rows." Result".(($total_rows != 1) ? "s" : "")." Found. Results ".($limit_parameter + 1)." - ".((($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"] + $limit_parameter) <= $total_rows) ? ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"] + $limit_parameter) : $total_rows)." for &quot;<strong>".$search_query."</strong>&quot; shown below.</span></div>\n";
			echo "</div>";
			echo "<div class=\"row-fluid\">";
			$flag = 1;
			foreach ($results as $key => $result) {
				echo "<div id=\"result-".$result["id"]."\" class=\"media ps-media-padding span6\" style=\"border:none; overflow: visible;".($key % 2 == 1 ? " background-color: rgb(238, 238, 238);" : "")."\">\n";
			
				$offical_file_active	= false;
				$uploaded_file_active	= false;

				/**
				 * If the photo file actually exists, and either
				 * 	If the user is in an administration group, or
				 *  If the user is trying to view their own photo, or
				 *  If the proxy_id has their privacy set to "Any Information"
				 */
				if ((@file_exists(STORAGE_USER_PHOTOS."/".$result["id"]."-official")) && ($ENTRADA_ACL->amIAllowed(new PhotoResource($result["id"], (int) $result["privacy_level"], "official"), "read"))) {
					$offical_file_active	= true;
				}

				/**
				 * If the photo file actually exists, and
				 * If the uploaded file is active in the user_photos table, and
				 * If the proxy_id has their privacy set to "Basic Information" or higher.
				 */
				$query			= "SELECT `photo_active` FROM `".AUTH_DATABASE."`.`user_photos` WHERE `photo_type` = '1' AND `photo_active` = '1' AND `proxy_id` = ".$db->qstr($result["id"]);
				$photo_active	= $db->GetOne($query);
				if ((@file_exists(STORAGE_USER_PHOTOS."/".$result["id"]."-upload")) && ($photo_active) && ($ENTRADA_ACL->amIAllowed(new PhotoResource($result["id"], (int) $result["privacy_level"], "upload"), "read"))) {
					$uploaded_file_active = true;
				}
				echo "<div id=\"img-holder-".$result["id"]."\" class=\"img-holder pull-left\">";
				if ($offical_file_active) {
					echo "		<img id=\"official_photo_".$result["id"]."\" class=\"official people-search-thumb img-circle img-polaroid\" src=\"".webservice_url("photo", array($result["id"], "official"))."\" width=\"72\" height=\"100\" alt=\"".html_encode($result["prefix"]." ".$result["firstname"]." ".$result["lastname"])."\" title=\"".html_encode($result["prefix"]." ".$result["firstname"]." ".$result["lastname"])."\" />\n";
				}
 
				if ($uploaded_file_active) {
					echo "		<img id=\"uploaded_photo_".$result["id"]."\" class=\"uploaded people-search-thumb img-circle img-polaroid\" src=\"".webservice_url("photo", array($result["id"], "upload"))."\" width=\"72\" height=\"100\" alt=\"".html_encode($result["prefix"]." ".$result["firstname"]." ".$result["lastname"])."\" title=\"".html_encode($result["prefix"]." ".$result["firstname"]." ".$result["lastname"])."\" />\n";
				}

				if (($offical_file_active) || ($uploaded_file_active)) {
					echo "		<a id=\"zoomin_photo_".$result["id"]."\" class=\"zoomin\" onclick=\"growPic($('official_photo_".$result["id"]."'), $('uploaded_photo_".$result["id"]."'), $('official_link_".$result["id"]."'), $('uploaded_link_".$result["id"]."'), $('zoomout_photo_".$result["id"]."'));\"><i class=\"fa fa-search-plus\" aria-hidden=\"true\"></i></a>";
					echo "		<a id=\"zoomout_photo_".$result["id"]."\" class=\"zoomout\" onclick=\"shrinkPic($('official_photo_".$result["id"]."'), $('uploaded_photo_".$result["id"]."'), $('official_link_".$result["id"]."'), $('uploaded_link_".$result["id"]."'), $('zoomout_photo_".$result["id"]."'));\"></a>";
				} else {
					echo "		<img class=\"media-object people-search-thumb img-circle img-polaroid\" src=\"".ENTRADA_URL."/images/headshot-male.gif\" width=\"72\" height=\"100\" alt=\"No Photo Available\" title=\"No Photo Available\" />\n";
				}
				
				if (($offical_file_active) && ($uploaded_file_active)) {
					echo "		<a id=\"official_link_".$result["id"]."\" class=\"img-selector one\" onclick=\"showOfficial($('official_photo_".$result["id"]."'), $('official_link_".$result["id"]."'), $('uploaded_link_".$result["id"]."'));\" href=\"javascript: void(0);\">1</a>";
					echo "		<a id=\"uploaded_link_".$result["id"]."\" class=\"img-selector two\" onclick=\"hideOfficial($('official_photo_".$result["id"]."'), $('official_link_".$result["id"]."'), $('uploaded_link_".$result["id"]."'));\" href=\"javascript: void(0);\">2</a>";
				}
				echo "</div>";
				echo "<div class=\"media-body\">";
				echo "	<div class=\"pull-left ps-media-body-margin muted\">";
				echo "		<h4 class=\"media-heading ps-media-heading\">" . html_encode((($result["prefix"]) ? $result["prefix"]." " : "").$result["firstname"]." ".$result["lastname"]) . "</h4>";

				$departmentResults = get_user_departments($result["id"]);
				if ($departmentResults) {
					foreach ($departmentResults as $key => $departmentValue) {
						echo (($key > 0) ? "<br />" : "") . $departmentValue["department_title"];
					}
				} else {
					if ($result["group"] == "student") {
						$cohort = groups_get_cohort($result["id"]);
					}

					echo ucwords($result["group"])." <i class=\"fa fa-caret-right\" aria-hidden=\"true\"></i> ".($result["group"] == "student" && isset($cohort["group_name"]) ? $cohort["group_name"] : ucwords($result["role"]));
				}

				echo (isset($ORGANISATIONS_BY_ID[$result["organisation_id"]]) ? "<br />".$ORGANISATIONS_BY_ID[$result["organisation_id"]]["organisation_title"] : "")."\n";

				echo "<br /><br />";

				if ($result["privacy_level"] > 1 || $is_administrator) {
					echo "<a href=\"mailto:".html_encode($result["email"])."\">".html_encode($result["email"])."</a><br />\n";
					
					if ($result["email_alt"]) {
						echo "<a href=\"mailto:".html_encode($result["email_alt"])."\">".html_encode($result["email_alt"])."</a>\n";
					}
				}

				if (($result["privacy_level"] > 2 || $is_administrator)) {
					if ($result["telephone"]) {
						echo "Telephone: " . html_encode($result["telephone"]) . "<br />";
					}

					if ($result["fax"]) {
						echo "Fax: " . html_encode($result["fax"]) . "<br />";
					}

					if ($result["address"] && $result["city"]) {
						echo "<br />Address:";
						echo "<address>";
                        echo    html_encode($result["address"])."<br />\n";
						echo    html_encode($result["city"].($result["city"] && $result["province"] ? ", ".$result["province"] : ""))."<br />\n";
						echo    html_encode($result["country"].($result["country"] && $result["postcode"] ? ", ".$result["postcode"] : ""))."\n";
                        echo "</address>";
					}
					if ($result["office_hours"]) {
						echo "Office Hours: " . nl2br(html_encode($result["office_hours"])) . "<br />";
					}
				}
				
				$query		= "	SELECT CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, b.`email`
								FROM `permissions` AS a
								LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
								ON b.`id` = a.`assigned_to`
								WHERE a.`assigned_by`=".$db->qstr($result["id"])."
								AND (a.`valid_from` = '0' OR a.`valid_from` <= ".$db->qstr(time()).") AND (a.`valid_until` = '0' OR a.`valid_until` > ".$db->qstr(time()).")
								ORDER BY `valid_until` ASC";
				$assistants	= $db->GetAll($query);
				if ($assistants) {
					echo "<br />Administrative Assistants";
					foreach ($assistants as $assistant) {
						echo "<br /> <i class=\"fa fa-user\" aria-hidden=\"true\"></i> <a href=\"mailto:".html_encode($assistant["email"])."\">".html_encode($assistant["fullname"])."</a>";
					}
				}
				?>
					</div>
				</div>
				<div class="clearfix"> </div>
			<?php
				echo "</div>\n";
				if ($flag == 2) {
					echo "</div><div class=\"row-fluid\">";
					$flag = 1;
				} else {
					$flag = $flag + 1;
				}
			}
			echo "</div>";
			
		} else {
			echo "<div class=\"display-notice\">\n";
			echo "	<h3>No Matching People</h3>\n";
			echo "	There are no people in the system found which contain matches to &quot;<strong>".$search_query."</strong>&quot;.";
			echo "</div>\n";
		}

		if ($total_pages > 1) {
            echo $pagination->GetPageBar();
		}
	}
	
	/**
	 * Sidebar item that will provide another method for sorting, ordering, etc.
	 */
	$sidebar_html  = "<ul class=\"menu\">\n";
	$sidebar_html .= "	<li class=\"".((strtolower($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"]) == "5") ? "on" : "off")."\"><a href=\"".ENTRADA_URL."/people?".replace_query(array("pp" => "5"))."\" title=\"Display 5 Profiles Per Page\">5 profiles per page</a></li>\n";
	$sidebar_html .= "	<li class=\"".((strtolower($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"]) == "15") ? "on" : "off")."\"><a href=\"".ENTRADA_URL."/people?".replace_query(array("pp" => "15"))."\" title=\"Display 15 Profiles Per Page\">15 profiles per page</a></li>\n";
	$sidebar_html .= "	<li class=\"".((strtolower($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"]) == "25") ? "on" : "off")."\"><a href=\"".ENTRADA_URL."/people?".replace_query(array("pp" => "25"))."\" title=\"Display 25 Profiles Per Page\">25 profiles per page</a></li>\n";
	$sidebar_html .= "	<li class=\"".((strtolower($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"]) == "50") ? "on" : "off")."\"><a href=\"".ENTRADA_URL."/people?".replace_query(array("pp" => "50"))."\" title=\"Display 50 Profiles Per Page\">50 profiles per page</a></li>\n";
	$sidebar_html .= "</ul>\n";

	new_sidebar_item("Profiles Per Page", $sidebar_html, "sort-results", "open");	
}
?>