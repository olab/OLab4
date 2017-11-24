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
 * Core Rotation select - student selects clerkship rotation.
 *
 * @author Organisation: University of Calgary
 * @author Unit: Undergraduate Medical Education
 * @author Developer: Doug Hall <hall@ucalgary.ca>
 * @copyright Copyright 2009 University of Calgary. All Rights Reserved.
 *
*/


if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CLERKSHIP"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('logbook', 'read')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/public/clerkship/logbook?section=select", "title" => "Select Rotation");

	$query	    = "	SELECT * FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations` ORDER BY `rotation_id` ASC";

	$results    = $db->GetAll($query);

	if ($results) {
	    if ($ERROR) {
		echo display_error();
	    }
	    ?>
	    <div class="content-heading">Select a Clerkship Rotation</div>
	    <div style="float: right; margin-bottom: 5px">
		<div id="module-content">
		    <ul class="page-action">
			<li>
			    <a href="<?php echo ENTRADA_URL."/clerkship/logbook?section=add";?>" class="strong-green">Log Encounter</a>
			</li>
		    </ul>
		</div>
	    </div>
	    <div style="clear: both"></div>
	    <table class="tableList" cellspacing="0" summary="List of Clerkship Rotations">
		<colgroup>
		    <col class="region" />
		    <col class="completed" />
		    <col class="completed" />
		    <col class="completed" />
		    <col class="completed" />
		</colgroup>
		<thead>
		    <tr>
			<td class="region">Rotation</td>
			<td class="completed">Entries</td>
			<td class="completed">CPs</td>
			<td class="completed">Mandatory CPs</td>
			<td class="completed">Procedures</td>
		    </tr>
		</thead>
		<tbody>
		<tr><td colspan="5"></td></tr>
		<?php
		$other = false;
		foreach ($results as $result) {
		    $click_url	= ENTRADA_URL."/clerkship?core=".$result["rotation_id"];

		    $clinical_encounters = clerkship_get_rotation_overview($result["rotation_id"]);
		    if ($clinical_encounters["entries"]) {
				$click_url = ENTRADA_URL."/clerkship/logbook?section=view&type=entries&core=".$result["rotation_id"];
		    } else {
				$click_url = ENTRADA_URL."/clerkship?core=".$result["rotation_id"];
		    }
		    echo "<tr><td class=\"region\"><a href=\"".$click_url."\" style=\"font-size: 11px\">".limit_chars(html_decode($result["rotation_title"]), 55, true, false)."</a></td>\n";
		    echo "<td class=\"completed\">".blank_zero($clinical_encounters["entries"])."</td>\n";
		    echo "<td class=\"completed\">".blank_zero($clinical_encounters["objectives"])."</td>\n";
		    echo "<td class=\"completed\">".blank_zero($clinical_encounters["mandatories"])." ". ($clinical_encounters["other_mandatories"]?"&nbsp;(".$clinical_encounters["other_mandatories"].")":'')."</td>\n";
		    echo "<td class=\"completed\">".blank_zero($clinical_encounters["procedures"])."</td></tr>\n";
		}
		?>
		</tbody>
		</table>
	    <br />
	<?php
	    if ($other) {
		echo "<div style=\"color:#666; text-align:right;\">";
		echo "Parenthisized (value) indicate mandatory objectives seen in other rotations.";
		echo "</div>\n";
	    }
	}
}
?>