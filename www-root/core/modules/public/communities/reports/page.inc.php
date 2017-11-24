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


	if (isset($_GET["page"]) && $tmp = clean_input($_GET["page"], array("notags","trim"))) {
		$page_info = explode("-",$tmp);
	}

	if ($page_info) {				
		$page = get_page_for_statistic($page_info[0], $page_info[1]);

		$BREADCRUMB[] = array("url" => ENTRADA_URL."/".$MODULE, "title" => "Report for page `".$page."'");


		echo "<h1>Community Usage Report for Page '".$page."'</h1>";

		$query = "	SELECT DISTINCT `proxy_id` 
					FROM `statistics` WHERE `module` 
					LIKE 'community:".$COMMUNITY_ID.":%' 
					AND `action_field` = ".$db->qstr($page_info[0])." 
					AND `action_value` = ".$db->qstr($page_info[1]);
		$active_users = $db->GetAll($query);
		if ($active_users) {
			foreach($active_users as $user){
				$userlist[] = $user["proxy_id"];
			}
		}
		$query = "	SELECT `proxy_id` 
					FROM `community_members` 
					WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." 
					AND `proxy_id` NOT IN(".implode(",",$userlist).")";
		$inactive_users = $db->GetAll($query);				

		$STATISTICS["labels"][1] = "Active Users";
		$STATISTICS["legend"][1] = "Active Users";
		$STATISTICS["display"][1] = count($active_users);

		$STATISTICS["labels"][2] = "Inactive Users";
		$STATISTICS["legend"][2] = "Inactive Users";
		$STATISTICS["display"][2] = count($inactive_users);


?>
		<div style="text-align: center">
			<canvas id="graph_<?php echo $tmp; ?>" width="750" height="450"></canvas>
		</div>

		<h2>Active Users</h2>
		<table id="data_table_<?php echo $course_id; ?>" class="tableList" style="width: 750px" cellspacing="0" summary="<?php echo $translate->_("Event Types"); ?> of <?php echo html_encode($courses_included[$course_id]); ?>">
		<colgroup>
			<col class="modified" />
			<col class="user" />
			<col class="views" />
			<col class="adss" />					
		</colgroup>
		<thead>
			<tr>
				<td class="modified">&nbsp;</td>
				<td class="user">Community Member</td>
				<td class="views">Views</td>
				<td class="views">Submissions</td>						
			</tr>
		</thead>
		<tbody>
			<?php
			$total_views = 0;
			$total_adds = 0;
			foreach ($active_users as $user) {
				$query = "	SELECT CONCAT_WS(' ',`firstname`,`lastname`) AS `fullname`
							FROM `".AUTH_DATABASE."`.`user_data` WHERE `id` = ".$db->qstr($user["proxy_id"]);
				$fullname = $db->GetOne($query);
				$query = "	SELECT COUNT(`proxy_id`) 
							FROM `statistics` 
							WHERE `module` LIKE 'community:".$COMMUNITY_ID.":%' 
							AND `action_field` = ".$db->qstr($page_info[0])." 
							AND `action_value` = ".$db->qstr($page_info[1])."
							AND `action` LIKE '%view%' 
							AND `proxy_id` = ".$db->qstr($user["proxy_id"]);
				$num_views = $db->GetOne($query);
				$total_views += $num_views;
				$query = "	SELECT COUNT(`proxy_id`) 
							FROM `statistics` 
							WHERE `module` LIKE 'community:".$COMMUNITY_ID.":%' 
							AND `action_field` = ".$db->qstr($page_info[0])." 
							AND `action_value` = ".$db->qstr($page_info[1])."
							AND `action` LIKE '%add%' 
							AND `proxy_id` = ".$db->qstr($user["proxy_id"]);
				$num_adds = $db->GetOne($query);
				$total_adds += $num_adds;
				echo "<tr><td>&nbsp;</td><td><a href=\"".ENTRADA_URL."/communities/reports?section=user&community=".$COMMUNITY_ID."&user=".$user["proxy_id"]."\">".$fullname."</a></td><td>".$num_views."</td><td>".$num_adds."</td></tr>";
			}
			?>
			<tr class="na"><td>&nbsp;</td><td><span class="bold">Total</span></td><td><?php echo $total_views;?></td><td><?php echo $total_adds;?></td></tr>
		</tbody>
		</table>

		<h2>Inactive Users</h2>
		<table id="data_table_<?php echo $course_id; ?>" class="tableList" style="width: 750px" cellspacing="0" summary="<?php echo $translate->_("Event Types"); ?> of <?php echo html_encode($courses_included[$course_id]); ?>">
		<colgroup>
			<col class="modified" />
			<col class="user" />
		</colgroup>
		<thead>
			<tr>
				<td class="modified">&nbsp;</td>
				<td class="user">Community Member</td>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach ($inactive_users as $user) {
				$query = "	SELECT CONCAT_WS(' ',`firstname`,`lastname`) AS `fullname`
							FROM `".AUTH_DATABASE."`.`user_data` WHERE `id` = ".$db->qstr($user["proxy_id"]);
				$fullname = $db->GetOne($query);
				echo "<tr><td>&nbsp;</td><td><a href=\"".ENTRADA_URL."/communities/reports?section=user&community=".$COMMUNITY_ID."&user=".$user["proxy_id"]."\">".$fullname."</a></td></tr>";
			}
			?>
		</tbody>
		</table>				


		<script type="text/javascript">
		var options = {
		   'IECanvasHTC':		'<?php echo ENTRADA_RELATIVE; ?>/javascript/plotkit/iecanvas.htc',
		   'yTickPrecision':	1,
		   'xTicks':			[<?php echo plotkit_statistics_lables($STATISTICS["legend"]); ?>]
		};

		var layout	= new PlotKit.Layout('pie', options);
		layout.addDataset('results', [<?php echo plotkit_statistics_values($STATISTICS["display"]); ?>]);
		layout.evaluate();

		var canvas	= MochiKit.DOM.getElement('graph_<?php echo $tmp; ?>');

		var plotter	= new PlotKit.SweetCanvasRenderer(canvas, layout, options);
		plotter.render();

		</script>
		<?php
	}


}
