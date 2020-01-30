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

if((!defined("PARENT_INCLUDED")) || (!defined("IN_POLLS"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->amIAllowed('poll', 'update', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000);";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	/**
	 * Update requested column to sort by.
	 * Valid: date, teacher, title, phase
	 */
	if(isset($_GET["sb"])) {
		if(@in_array(trim($_GET["sb"]), array("date" , "question", "target"))) {
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]	= trim($_GET["sb"]);
		}

		$_SERVER["QUERY_STRING"]	= replace_query(array("sb" => false));
	} else {
		if(!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"])) {
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] = "target";
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
	 * Check if preferences need to be updated on the server at this point.
	 */
	preferences_update($MODULE, $PREFERENCES);

	/**
	 * Provide the queries with the columns to order by.
	 */
	switch($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]) {
		case "date" :
			$SORT_BY	= "`poll_from` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]);
		break;
		case "question" :
			$SORT_BY	= "`poll_question` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]).", `poll_from` ASC";
		break;
		case "target" :
		default :
			$SORT_BY	= "`poll_target` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]).", `poll_from` ASC";
		break;
	}
	?>
	<h1><?php echo $MODULES[strtolower($MODULE)]["title"]; ?></h1>

	<?php if($ENTRADA_ACL->amIAllowed('poll', 'create')) : ?>
		<div class="row-fluid" style="margin-bottom:10px;">
			<div class="pull-right">
				<a href="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>?section=add" class="btn btn-primary">Add New Poll</a>
			</div>
		</div>
	<?php endif; ?>
	<?php
	$query	= "SELECT * FROM `poll_questions` ORDER BY ".$SORT_BY;
	$results	= ((USE_CACHE) ? $db->CacheGetAll(CACHE_TIMEOUT, $query) : $db->GetAll($query));
	if($results) {
		if($ENTRADA_ACL->amIAllowed('poll', 'delete')) : ?>
		<form action="<?php echo ENTRADA_URL; ?>/admin/polls?section=delete" method="post">
		<?php endif; ?>
		<table class="tableList" cellspacing="0" summary="List of Polls">
		<colgroup>
			<col class="modified" />
			<col class="general" />
			<col class="title" />
			<col class="responses" />
		</colgroup>
		<thead>
			<tr>
				<td class="modified">&nbsp;</td>
				<td class="general<?php echo (($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "target") ? " sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]) : ""); ?>"><?php echo admin_order_link("target", "Poll Targets"); ?></td>
				<td class="title<?php echo (($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "question") ? " sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]) : ""); ?>"><?php echo admin_order_link("question", "Poll Question"); ?></td>
				<td class="responses" style="font-size: 12px">Responses</td>
			</tr>
		</thead>
		<?php if($ENTRADA_ACL->amIAllowed('poll', 'delete')) : ?>
		<tfoot>
			<tr>
				<td></td>
				<td colspan="3" style="padding-top: 10px">
					<input type="submit" class="btn btn-danger" name="delete_polls" value="Delete Selected" />
					<input type="submit" class="btn btn-warning" name="expire_polls" value="Expire Selected" />
				</td>
			</tr>
		</tfoot>
		<?php endif; ?>
		<tbody>
			<?php
			foreach($results as $result) {
				$expired		= false;
				$responses	= poll_responses($result["poll_id"]);

				if(!$responses) {
					$url		= ENTRADA_URL."/admin/polls?section=edit&amp;id=".$result["poll_id"];
				} else {
					$url		= "javascript: SeeResults('".$result["poll_id"]."')";
				}

				if(($poll_until = (int) $result["poll_until"]) && ($poll_until < time())) {
					$expired	= true;
				}

				echo "<tr id=\"poll-".$result["poll_id"]."\" class=\"poll".(($expired) ? " na" : "")."\">\n";
				echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"delete[]\" value=\"".$result["poll_id"]."\" /></td>\n";
				echo "	<td class=\"general\">".(($url) ? "<a href=\"".$url."\" title=\"Edit Poll: ".((isset($POLL_TARGETS[$result["poll_target"]])) ? str_replace("&nbsp;", "", $POLL_TARGETS[$result["poll_target"]]) : $result["poll_target"])."\">" : "").((isset($POLL_TARGETS[$result["poll_target"]])) ? str_replace("&nbsp;", "", $POLL_TARGETS[$result["poll_target"]]) : $result["poll_target"]).(($url) ? "</a>" : "")."</td>\n";
				echo "	<td class=\"title\">".(($url) ? "<a href=\"".$url."\" title=\"Edit Poll: ".html_encode($result["poll_question"])."\">" : "").html_encode($result["poll_question"]).(($url) ? "</a>" : "")."</td>\n";
				echo "	<td class=\"responses\">".$responses."</td>\n";
				echo "</tr>\n";
			}
			?>
		</tbody>
		</table>
		<?php if($ENTRADA_ACL->amIAllowed('poll', 'delete')) : ?>
		</form>
		<?php
		endif;
	} else {
		$filters_applied = (((isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["filters"])) && (@count($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["filters"]))) ? true : false);
		?>
		<div class="display-notice">
			<h3>No Polls Available</h3>
			There are currently no polls in the system. To add a poll click the <strong>Add Poll</strong> link.
		</div>
		<?php
	}
}
?>