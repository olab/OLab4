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
 * Rotation checklist - student ticks off evaluation milestones for clerkship rotation.
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
    $BREADCRUMB[]	= array("url" => ENTRADA_URL."/public/clerkship/logbook?section=checklist", "title" => "Rotation Evaluation Checklist");

    if (isset($_POST["rotation_id"])) {
	$rotation_id = clean_input($_POST["rotation_id"], "int");
    } else if ($_GET["id"]) {
	$event_id = clean_input($_GET["id"], "int");

	$query  = " SELECT a.`rotation_id` FROM `".CLERKSHIP_DATABASE."`.`categories` a,
		    `".CLERKSHIP_DATABASE."`.`events` b
		    WHERE a.`category_id` = b.`category_id`
		    AND b.`event_id` = ".$db->qstr($event_id);
	$rotation_id = $db->GetOne($query);

    } else if ($_GET["core"]) {
	$rotation_id = clean_input($_GET["core"], "int");
    } else {  // Select Overview / Elective if not a mandatory rotation
	$rotation_id = MAX_ROTATION;
    }

    $title  = $db->GetOne("SELECT `rotation_title` FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations`
			    WHERE `rotation_id` = ".$db->qstr($rotation_id));
    if ($title) {
	if ($ERROR) {
	    echo display_error();
	}

	$PROCESSED = $db->GetRow("SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_checklist`
				    WHERE `proxy_id` = ".$db->qstr($ENTRADA_USER->getID())." AND `rotation_id` = ".$db->qstr($rotation_id));

	if (!$PROCESSED) {
	    $PROCESSED["rotation_id"] = $rotation_id;
	    $PROCESSED["checklist"] = 0;
	}
    }

    switch ($STEP) {
	case 2 :

	$checklist = 0;
	if (isset($_POST["checklist"])) {
	    foreach ($_POST['checklist'] as $key => $value) $checklist |= (1 << $value-1);
	}
	$PROCESSED["checklist"] = $checklist;
		
	$PROCESSED["proxy_id"] = $ENTRADA_USER->getID();
	$PROCESSED["updated_date"] = time();

	$query	= "SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_checklist`
				    WHERE `proxy_id` = ".$db->qstr($ENTRADA_USER->getID())." AND `rotation_id` = ".$db->qstr($rotation_id);
	$result	= $db->GetRow($query);

	if($result) {
	    if ($db->AutoExecute("`".CLERKSHIP_DATABASE."`.`logbook_entry_checklist`", $PROCESSED, "UPDATE",
		    "`proxy_id` = ".$db->qstr($ENTRADA_USER->getID())." AND `rotation_id` = ".$db->qstr($rotation_id) )) {
		$url = ENTRADA_URL."/".$MODULE;
		$SUCCESS++;
		$SUCCESSSTR[]  	= "You have successfully updated the <strong>Evaluation checklist</strong> for logbook.<br /><br />Please <a href=\"".$url."\">click here</a> to proceed to the index page or you will be automatically forwarded in 3 seconds.";
		$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 3000)";	    application_log("success", "Evaluation checklist  updated to the system.");
	    } else {
		$ERROR++;
		$ERRORSTR[] = "There was a problem inserting this evaluation checklist into the system. The ".$AGENT_CONTACTS["administrator"]["name"]." was informed of this error; please try again later.";
		application_log("error", "There was an error updating the clerkship logbook checklist entry. Database said: ".$db->ErrorMsg());
	    }
	} else {
	    if ($db->AutoExecute("`".CLERKSHIP_DATABASE."`.`logbook_entry_checklist`", $PROCESSED, "INSERT")) {
		$url = ENTRADA_URL."/".$MODULE;
		$SUCCESS++;
		$SUCCESSSTR[]  	= "You have successfully added the <strong>Evaluation checklist</strong> to the logbook.<br /><br />Please <a href=\"".$url."\">click here</a> to proceed to the index page or you will be automatically forwarded in 3 seconds.";
		$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 3000)";
	    } else {
		$ERROR++;
		$ERRORSTR[] = "There was a problem inserting this evaluation checklist into the system. The ".$AGENT_CONTACTS["administrator"]["name"]." was informed of this error; please try again later.";
		application_log("error", "There was an error inserting a clerkship logbook checklist entry. Database said: ".$db->ErrorMsg());
	    }
	}

	if ($ERROR) {
	    $STEP = 1;
	}

	break;
	case 1 :
	    default :
		$query = "  SELECT  `type`, `indent`, `item` FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_checklist`
				WHERE `rotation_id` = ".$db->qstr($rotation_id)." ORDER BY `line` ";

		$results = $db->GetAll($query);

		if (!$results) {
		    $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 10000)";
		    $NOTICE++;
		    $NOTICESTR[]	= "There is no Rotation Checklist associated with the  $title rotation.";

		    $STEP = 2;
		}

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
		if ($ERROR) {
		    echo display_error();
		}
		$indentLevel = 0;
	        $ivalue = 0;
		$cl = $PROCESSED["checklist"];
		$l = 0;
	    ?>
		<div class="content-heading">Evaluation Checklist for <?php echo $title;?></div>
		<form id="checklistForm" action="<?php echo ENTRADA_URL; ?>/clerkship/logbook?<?php echo replace_query(array("step" => 2, "core" => '')); ?>" method="post">
		<table style="width: 100%"  cellspacing="3" cellpadding="3" border="0" >
		<tr><td width="15px">&nbsp;</td><td width="25px">&nbsp;</td><td width="25px">&nbsp;</td><td width="25px">&nbsp;</td><td>&nbsp;</td></tr>
	    <?php
		    foreach ($results as $result) {
			if ($indentLevel > $result["indent"]) {
			    echo '<tr><td colspan="5">&nbsp;</td></tr>';
			}
			$indentLevel = $result["indent"];
			if ($result["type"] == 2) {
			    $ivalue++;
			    echo "<tr><td colspan='3'>&nbsp;</td><td><input type='checkbox' name='checklist[]' value='$ivalue' ". ($cl&(1<<$l++)?'checked':'') ." /></td><td>$result[item]</td></tr>\n";
			} else {
			    echo '<tr><td colspan="' . (1+$result["indent"]) . '">&nbsp;</td><td colspan="' .(4-$result["indent"]) . "\"><b>$result[item]</b></td></tr>\n";
			}
		    }
		    echo "<input type=\"hidden\" name=\"rotation_id\" value=\"$rotation_id\">";
	    ?>
		    <tr><td colspan="5">&nbsp;</td></tr><tr><td colspan="4">&nbsp;</td><td><input class="btn btn-primary" type="submit" name="hidinp" value="Save"></td></tr>
		    </table></form>
	    <?php
		break;
	}
}
?>