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
 * This file displays a list of encounter tracking entries.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer:	James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_ENCOUNTER_TRACKING"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
		header("Location: ".ENTRADA_URL);
		exit;
} elseif (!$ENTRADA_ACL->amIAllowed('encounter_tracking', 'read') && !$ENTRADA_ACL->amIAllowed("academicadvisor", "read", false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/assessments\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

	$display_page = false;
    $student_viewing = true;
    $user_proxy_id = 0;

	if ($ENTRADA_ACL->amIAllowed("academicadvisor", "read", false)) {
        if (isset($_GET["proxy_id"]) && $tmp_input = clean_input($_GET["proxy_id"], array("trim", "int"))) {
			$user_proxy_id = $tmp_input;
		    $assessment_learner = Models_User::fetchRowByID($user_proxy_id);
			if ($assessment_learner) {
			    if ($ENTRADA_ACL->amIAllowed(new AcademicAdvisorResource($user_proxy_id), "read", true)) {
			        $display_page = true;
                    $student_viewing = false;
				} else {
					$ERROR++;
				}
			} else {
				$ERROR++;
			}
		} else {
			$ERROR++;
		}

		if ($ERROR) {
			$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/assessments\\'', 15000)";
			$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";
			echo display_error();
		}
	} else {
		$display_page = true;
	}

	if ($display_page) {
		echo "<h1>Encounter Tracking Entries</h1>";

		//try and get the current learner passed user id
		$entries = Models_Logbook_Entry::fetchAll($student_viewing ? $ENTRADA_USER->GetID() : $user_proxy_id);

		$HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.dataTables.min.js\"></script>";
		$HEAD[] = "<script type=\"text/javascript\">

        jQuery(function($) {
            jQuery('#entries').dataTable(
                {
                    'sPaginationType': 'full_numbers',
                    'bInfo': false,
                    'bAutoWidth': false,
                    'sAjaxSource': '?proxy_id=" . $user_proxy_id . "&section=api-list',
                    'bServerSide': true,
                    'bProcessing': true,
                    'aoColumns': [
                        { 'mDataProp': 'checkbox', 'bSortable': false },
                        { 'mDataProp': 'course' },
                        { 'mDataProp': 'date' },
                        { 'mDataProp': 'institution' },
                        { 'mDataProp': 'location' }
                    ],
                    'oLanguage': {
                        'sEmptyTable': 'There are currently no entries in the system. Use the Log Entry button to create a new encounter tracking entry.',
                        'sZeroRecords': 'No encounter tracking entries found to display.'
                    }
                }
            );
        });
        
    	</script>";

		add_statistic("encounter_tracking", "view", "lentry_id", NULL, ($student_viewing ? $ENTRADA_USER->GetID() : $user_proxy_id));

		?>
		<?php if ($student_viewing) { ?>
			<div class="row-fluid">
				<a href="<?php echo html_encode(ENTRADA_URL); ?>/logbook?section=add" class="btn btn-primary pull-right">Log Entry</a>
			</div>
			<br/>
		<?php } ?>
		<form action="<?php echo html_encode(ENTRADA_URL); ?>/logbook?section=delete" method="POST" id="encounter-tracking-entries-list">
			<table id="entries" class="table table-striped table-bordered" width="100%" cellpadding="0" cellspacing="0" border="0">
				<thead>
				<tr>
					<th class="modified"></th>
					<th class="course">Course</th>
					<th class="date">Encounter Date</th>
					<th class="institution">Institution</th>
					<th class="location">Setting</th>
				</tr>
				</thead>
				<tbody>
				</tbody>
			</table>
			<?php if ($student_viewing) { ?>
				<input class="btn" type="submit" value="Delete Selected"/>
			<?php } else {?>
				<a href="<?php echo html_encode(ENTRADA_URL); ?>/assessments" class="btn btn-default pull-left">Return</a>
			<?php } ?>
		</form>
		<?php
	}
}