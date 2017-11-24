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

	if ($MAILING_LISTS["active"]) {
		require_once("Entrada/mail-list/mail-list.class.php");
	}

	$USER_ID			= 0;

	/**
	 * Check for a community category to proceed.
	 */
	if ((isset($_GET["user"])) && ((int) trim($_GET["user"]))) {
		$USER_ID	= (int) trim($_GET["user"]);
	} elseif ((isset($_POST["user_id"])) && ((int) trim($_POST["user_id"]))) {
		$USER_ID	= (int) trim($_POST["user_id"]);
	}	

	if ($USER_ID) {
		/**
		 * Add PlotKit to the beginning of the $HEAD array.
		 */
		array_unshift($HEAD,
			"<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/MochiKit/MochiKit.js\"></script>",
			"<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/PlotKit/excanvas.js\"></script>",
			"<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/PlotKit/Base.js\"></script>",
			"<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/PlotKit/Layout.js\"></script>",
			"<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/PlotKit/Canvas.js\"></script>",
			"<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/PlotKit/SweetCanvas.js\"></script>"
			);

		$query = "	SELECT a.*, CONCAT_WS(' ',a.`firstname`,a.`lastname`) AS `fullname` FROM `".AUTH_DATABASE."`.`user_data` AS a WHERE `id` = ".$db->qstr($USER_ID);
		$result = $db->GetRow($query);
		if ($result) {
			$fullname = $result["fullname"];
			$BREADCRUMB[] = array("url" => ENTRADA_URL."/".$MODULE, "title" => "Report for ".$fullname);
			echo "<h2>Community Usage Report for ".$fullname."</h2>";


			$query = "	SELECT COUNT(*) AS `count`, `proxy_id`, `module`, `action_field`, `action_value`, `action` 
						FROM `statistics` 
						WHERE `module` LIKE 'community:".$COMMUNITY_ID.":%' 
						AND `proxy_id` = ".$db->qstr($USER_ID)."
						GROUP BY `proxy_id`, `module`, `action_field`, `action_value`, `action`
						ORDER BY `count`,`module`,`action`";
			$results = $db->GetAll($query);
			if($results){
				
			$query = "	SELECT COUNT(*) 
						FROM `statistics` 
						WHERE `module` LIKE 'community:".$COMMUNITY_ID.":%'";

			$total_interactions = $db->GetOne($query);				
			$query = "	SELECT COUNT(*) FROM `community_members` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID);
			$num_members = $db->GetOne($query);
			?>
				

			<canvas id="bargraph" width="750" height="450"></canvas>

			<table class="tableList" cellspacing="0" cellpadding="1" summary="List of Events">
				<colgroup>
					<col class="modified" />
					<col class="module" />
					<col class="phase" />
					<col class="teacher" />
					<col class="title" />
					<col class="attachment" />
				</colgroup>
				<thead>
					<tr>
						<td class="modified">&nbsp;</td>
						<td class="module">Module Type</td>
						<td class="field">Page</td>
						<td class="action">Action</td>
						<td class="count">Count</td>
						<td class="attachment">&nbsp;</td>
					</tr>
				</thead>
				<tbody>
			<?php	

				$total = 0;
				foreach ($results as $result) {
					$page = get_page_for_statistic($result["action_field"], $result["action_value"]);
					$module = explode(":",$result["module"]);
					$action_info = explode("_",$result["action"]);
					if (count($action_info) == 1) {
						$action = $result["action"];
					} else {
						$action = $action_info[1];
					}
					
					if ($page) {
					?>
						<tr><td>&nbsp;</td>
							<td><?php echo "<a href=\"".ENTRADA_URL."/communities/reports?section=type&community=".$COMMUNITY_ID."&type=".$module[2]."\">".ucwords($module[2])."</a>";?></td>
							<td><?php echo "<a href=\"".ENTRADA_URL."/communities/reports?section=page&community=".$COMMUNITY_ID."&page=".$result["action_field"]."-".$result["action_value"]."\">".ucwords($page)."</a>";?></td>
							<td><?php echo ucwords($action);?></td>
							<td><?php echo $result["count"];?></td>
							<td>&nbsp;</td></tr>
					<?php
					$total += $result["count"];
					}
				}


			?>
					<tr class="na"><td>&nbsp;</td><td colspan="3"><span class="bold">Total</span></td><td><?php echo $total;?></td><td>&nbsp;</td></tr>
				</tbody>
			</table>				
	<?php } else {
		 add_notice($result["fullname"]." has no activity in this community.");
		 echo display_notice();
	}

			$STATISTICS["labels"][1] = "Average Interactions Per User:".($total_interactions/$num_members);
			$STATISTICS["legend"][1] = "Average Interactions Per User:".($total_interactions/$num_members);
			$STATISTICS["display"][1] = ($total_interactions/$num_members);

			$STATISTICS["labels"][2] = "Interactions by ".$fullname.":".$total;
			$STATISTICS["legend"][2] = "Interactions by ".$fullname.":".$total;
			$STATISTICS["display"][2] = $total;	
	

	?>
			<script type="text/javascript">
			var options = {
			   'IECanvasHTC':		'<?php echo ENTRADA_RELATIVE; ?>/javascript/plotkit/iecanvas.htc',
			   "drawYAxis": false,
			   'xTicks':			[<?php echo plotkit_statistics_lables($STATISTICS["legend"]); ?>]
			};

			var layout	= new PlotKit.Layout('bar', options);
			layout.addDataset('results', [<?php echo plotkit_statistics_values($STATISTICS["display"]); ?>]);
			layout.evaluate();

			var canvas	= MochiKit.DOM.getElement('bargraph');
			var plotter	= new PlotKit.SweetCanvasRenderer(canvas, layout, options);
			plotter.render();
			</script>
			<?php

		}

	} else {
		application_log("error", "User tried to access a user report without providing a user_id.");

		header("Location: ".ENTRADA_URL."/communities");
		exit;	
	}

}
?>
	