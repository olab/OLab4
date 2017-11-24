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
 * This file is used to add events to the entrada.events table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2008 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_COMMUNITIES"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('community', 'update', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	$query				= "	SELECT * FROM `communities`
							WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)."
							AND `community_active` = '1'";

	$community_details	= $db->GetRow($query);
	if($community_details) {
		$community_resource = new CommunityResource($COMMUNITY_ID);
		if($ENTRADA_ACL->amIAllowed($community_resource, 'update')) {
			echo "<h1>".html_encode($community_details["community_title"])."</h1>\n";

			// Error Checking
			switch($STEP) {
				case 1 :
				default :
				$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/livepipe/progressbar.js?release=".APPLICATION_VERSION."\"></script>";
				$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/windows/window.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
				$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/config/xc2_default.js\"></script>\n";
				$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/script/xc2_inpage.js\"></script>\n";
				$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/script/xc2_timestamp.js\"></script>\n";
				$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/elementresizer.js\"></script>\n";
				if ($NOTICE) {
					echo display_notice();
				}
				if ($ERROR) {
					echo display_error();
				}
				?>

		<h2 style="margin-top: 0px"><?php echo $translate->_("Community Reports"); ?></h2>
			
<?php

		tracking_process_filters($ACTION);

		tracking_output_filter_controls();
		/**
		* Output the calendar controls and pagination.
		*/
		//events_output_calendar_controls();
		
		
		if (isset($_GET["pv"]) && ($page = clean_input($_GET["pv"], array("int","trim")))) {
			$PROCESSED["page"] = $page;
		} else {
			$PROCESSED["page"] = 1;
		}

		list($statistics,$dates,$num_pages, $num_results, $results_per_page) = tracking_fetch_filtered_events($COMMUNITY_ID,$_SESSION[APPLICATION_IDENTIFIER]["tracking"]["filters"],true,$PROCESSED["page"]);
		
		if ($PROCESSED["page"] > $num_pages) {
			$PROCESSED["page"] = 1;
		}

		if($num_pages){
            $pagination = new Entrada_Pagination($PROCESSED["page"], $results_per_page, $num_results, ENTRADA_URL."/communities/reports?community=".$COMMUNITY_ID, replace_query());
            echo $pagination->GetPageBar();
            echo $pagination->GetResultsLabel();
		}
		?>
		<?php
			if ($statistics) {
				?><table class="table table-bordered table-striped"><?php
				foreach($statistics as $key=>$statistic){
					$module = explode(':',$statistic['module']);
					$action = explode('_',$statistic['action']); 
					$user_action = ((count($action)>1)?$action[1]:$action[0]);
					$user_action = ($user_action == "delete"?$user_action."d":$user_action."ed");
					$activity_message = "<a href=\"".ENTRADA_URL."/communities/reports?section=user&community=".$COMMUNITY_ID."&user=".$statistic["user_id"]."\">".$statistic['fullname']."</a> ";
					$activity_message .= $user_action." the <a href=\"".ENTRADA_URL."/communities/reports?section=type&community=".$COMMUNITY_ID."&type=".$module[2]."\">".ucwords($module[2])."</a>";
					$activity_message .= " titled <a href=\"".ENTRADA_URL."/communities/reports?section=page&community=".$COMMUNITY_ID."&page=".$statistic["action_field"]."-".$statistic["action_value"]."\">".(isset($statistic["page"])?$statistic["page"]:"-")."</a>";
					$activity_message .= " at ".date('D M j/y h:i a', $statistic['timestamp']);


					echo "<tr><td>".$activity_message."</td></tr>";
				}
				?></table><?php
			}else{
				add_notice('No statistics available for '.$community_details["community_title"].'.');
				echo display_notice();
			}
			?>
	<!--/TRACKING EDITS-->	
					<?php
					break;
			}
		} else {
			application_log("error", "User tried to modify a community, but they aren't an administrator of this community.");

			$ERROR++;
			$ERRORSTR[] = "You do not appear to be an administrator of the community that you are trying to modify.<br /><br />If you feel you are getting this message in error, please contact the MEdTech Unit (page feedback on left) and we will investigate. The MEdTech Unit has automatically been informed that this error has taken place.";

			echo display_error();
		}
	} else {
		application_log("error", "User tried to modify a community id [".$COMMUNITY_ID."] that does not exist or is not active in the system.");

		$ERROR++;
		$ERRORSTR[] = "The community you are trying to modify either does not exist in the system or has been deactived by an administrator.<br /><br />If you feel you are receiving this message in error, please contact the MEdTech Unit (page feedback on left) and we will investigate. The MEdTech Unit has automatically been informed that this error has taken place.";

		echo display_error();
	}

}


?>
