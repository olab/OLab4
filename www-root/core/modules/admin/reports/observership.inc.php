<?php
/**
 * Online Course Resources [Pre-Clerkship]
 * Module:	Reports
 * Area:		Admin
 * @author Unit: Medical Education Technology Unit
 * @author Director: Dr. Benjamin Chen <bhc@post.queensu.ca>
 * @author Developer: Matt Simpson <simpson@post.queensu.ca>
 * @version 3.0
 * @copyright Copyright 2007 Queen's University, MEdTech Unit
 *
 * $Id: report-by-event-types.inc.php 992 2009-12-22 16:26:26Z simpson $
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_REPORTS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_RELATIVE);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("report", "read", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]." and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[]	= array("url" => "", "title" => "Observership Report");
	
	$HEAD[]		= "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/jquery.dataTables.min.js\"></script>\n";
	$HEAD[]		= "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/picklist.js\"></script>\n";
	$ONLOAD[]	= "$('courses_list').style.display = 'none'";
	
    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));
    $request = "_".$request_method;
    
    if (isset(${$request}["observership_status"]) && $tmp_input = strtolower(clean_input(${$request}["observership_status"], array("trim", "striptags", "alpha")))) {
        $allowed_status = array('pending','approved','rejected','confirmed','denied');
        if (in_array($tmp_input, $allowed_status)) {
            $PROCESSED["status"] = $tmp_input;
        }
    }
    
    if (isset(${$request}["group_id"]) && $tmp_input = clean_input(${$request}["group_id"], "int")) {
        $PROCESSED["group_id"] = $tmp_input;
    }
    
    if (isset(${$request}["csv"]) && $tmp_input = clean_input(${$request}["csv"], "int")) {
        $PROCESSED["csv"] = $tmp_input;
    }
    
    if (isset(${$request}["preceptors"]) && is_array(${$request}["preceptors"])) {
        foreach (${$request}["preceptors"] as $preceptor) {
            $PROCESSED["preceptors"][] = $db->qstr(clean_input($preceptor, "int"));
        }
    }
    
	?>
	</style>	
    <script type="text/javascript">
        jQuery(function($) {
            $(".datatable").dataTable();
        })
    </script>
	<div class="no-printing">
		<h2>Observership Report</h2>
        <form action="<?php echo ENTRADA_RELATIVE; ?>/admin/reports?section=<?php echo $SECTION; ?>&step=2" method="post" class="form-horizontal">
			<div class="control-group">
				<table>
					<tr>
						<?php echo generate_calendars("reporting", "Reporting Date", true, true, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"], true, true, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"]); ?>
					</tr>
				</table>
			</div>
            <div class="control-group">
                <label for="observership-status" class="control-label">Status</label>
                <div class="controls">
                    <select id="observership-status" name="observership_status">
                        <option value="all">All</option>
                        <option value="confirmed" <?php echo $PROCESSED["status"] == "confirmed" ? "selected=\"selected\"" : ""; ?>>Confirmed</option>
                        <option value="approved" <?php echo $PROCESSED["status"] == "approved" ? "selected=\"selected\"" : ""; ?>>Approved</option>
                        <option value="pending" <?php echo $PROCESSED["status"] == "pending" ? "selected=\"selected\"" : ""; ?>>Pending</option>
                        <option value="rejected" <?php echo $PROCESSED["status"] == "rejected" ? "selected=\"selected\"" : ""; ?>>Rejected</option>
                        <option value="denied" <?php echo $PROCESSED["status"] == "denied" ? "selected=\"selected\"" : ""; ?>>Denied</option>
                    </select>
                </div>
            </div>
            <div class="control-group">
                <label for="group_id" class="control-label">Cohort</label>
                <div class="controls">
                    <select id="group-id" name="group_id">
                        <option value="0">-- Please Select a Cohort --</option>
                        <?php
                        $query = "SELECT a.*
                                    FROM `groups` AS a
                                    JOIN `group_organisations` AS b
                                    ON a.`group_id` = b.`group_id`
                                    WHERE b.`organisation_id` = ?
                                    AND a.`group_active` = '1'
                                    ORDER BY `group_name` DESC";
                        $cohorts = $db->GetAll($query, array($ENTRADA_USER->getActiveOrganisation()));
                        if ($cohorts) {
                            foreach ($cohorts as $cohort) {
                                ?>
                        <option value="<?php echo html_encode($cohort["group_id"]); ?>" <?php echo isset($PROCESSED["group_id"]) && $cohort["group_id"] == $PROCESSED["group_id"] ? "selected=\"selected\"" : ""; ?>><?php echo html_encode($cohort["group_name"]); ?></option>
                                <?php
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="control-group">
                <label for="group_id" class="control-label">Preceptors</label>
                <div class="controls">
                    <select id="preceptors" name="preceptors[]" multiple="multiple">
                        <?php
                        $query = "SELECT a.`preceptor_proxy_id`, b.`firstname`, b.`lastname`
                                    FROM `student_observerships` AS a
                                    JOIN `".AUTH_DATABASE."`.`user_data` AS b
                                    ON a.`preceptor_proxy_id` = b.`id`
                                    WHERE a.`preceptor_proxy_id` IS NOT NULL AND a.`preceptor_proxy_id` != 0 
                                    GROUP BY a.`preceptor_proxy_id`
                                    ORDER BY b.`lastname`, b.`firstname`";
                        $preceptors = $db->GetAll($query);
                        if ($preceptors) {
                            foreach ($preceptors as $preceptor) {
                                ?>
                        <option value="<?php echo $preceptor["preceptor_proxy_id"]; ?>" <?php echo isset($PROCESSED["preceptors"]) && in_array("'".$preceptor["preceptor_proxy_id"]."'", $PROCESSED["preceptors"]) ? "selected=\"selected\"" : ""; ?>><?php echo $preceptor["lastname"] . ", " . $preceptor["firstname"]; ?></option>
                                <?php
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
			<div class="row-fluid">
				<div class="pull-right">
					<input type="submit" class="btn btn-primary" value="Create Report" />
				</div>
			</div>
		</form>
	</div>
	<?php
	if ($STEP == 2) {
		
		$query = "SELECT a.`id`, a.`title`, a.`start`, a.`end`, `organisation`, `address_l1`,
						CONCAT(b.`lastname`, ', ', b.`firstname`) AS `student_name`, 
						IF (a.`preceptor_proxy_id` IS NULL OR a.`preceptor_proxy_id` = '', 
							CONCAT(a.`preceptor_lastname`, ', ', a.`preceptor_firstname`), 
							CONCAT(c.`lastname`, ', ', c.`firstname`)) AS `preceptor_name` ,
                        a.`status`, e.`id` AS `reflection_id`
					FROM `student_observerships` AS a
					LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
					ON a.`student_id` = b.`id`
					LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS d
					ON a.`student_id` = d.`user_id`
					AND d.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
					LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS c
					ON a.`preceptor_proxy_id` = c.`id`
                    LEFT JOIN `observership_reflections` AS e
                    ON a.`id` = e.`observership_id`
                    ". (isset($PROCESSED["group_id"]) && $PROCESSED["group_id"] ? " JOIN `group_members` AS f ON a.`student_id` = f.`proxy_id` AND f.`group_id` = " . $db->qstr($PROCESSED["group_id"]) : "")."
					WHERE (a.`start` BETWEEN ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"])." AND ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"]).")
                    ". (isset($PROCESSED["status"]) ? " AND a.`status` = " . $db->qstr($PROCESSED["status"]) : "")."
                    ". (isset($PROCESSED["preceptors"]) ? " AND a.`preceptor_proxy_id` IN (" . implode(",", $PROCESSED["preceptors"]) . ")" : "")."
					GROUP BY a.`id`
					ORDER BY b.`lastname`, b.`firstname`, a.`start`";
		$results = $db->GetAll($query);
		echo "<h2 style=\"page-break-before: avoid\">Observerships within date range:</h2>";
		echo "<div class=\"content-small\" style=\"margin-bottom: 10px\">\n";
		echo "	<strong>Date Range:</strong> ".date(DEFAULT_DATE_FORMAT, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"])." <strong>to</strong> ".date(DEFAULT_DATE_FORMAT, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"]).".";
		echo "</div>\n";
		
		if ($results) {
            if ($PROCESSED["csv"] == "1") {
                ob_clear_open_buffers();
                
                $rows = array();
                $rows[] = array("Observership", "Start", "End", "Organisation", "Address Line 1", "Student", "Preceptor", "Status");
                foreach ($results as $result) {
                    $rows[] = array(
                        $result["title"],
                        date("Y-m-d", $result["start"]),
                        !empty($result["end"]) ? date("Y-m-d", $result["end"]) : date("Y-m-d", $result["start"]),
                        $result["organisation"],
                        $result["address_l1"],
                        $result["student_name"],
                        $result["preceptor_name"],
                        $result["status"] . ($result["status"] == "approved" && $result["reflection_id"] ? "[reflection entered]" : "")
                    );
                }
                
                header("Pragma: public");
                header("Expires: 0");
                header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header("Content-Type: application/force-download");
                header("Content-Type: application/octet-stream");
                header("Content-Type: text/csv");
                header("Content-Disposition: attachment; filename=\"observership-report-".date("Y-m-d").".csv\"");
                header("Content-Transfer-Encoding: binary");
                
                $fp = fopen("php://output", "w");
                
                foreach ($rows as $row) {
                    fputcsv($fp, $row);
                }
                
                fclose($fp);
                
                exit;
            } else {
                $status_desc["confirmed"]   = "<strong>Confirmed:</strong> The preceptor has confirmed the observership.";
                $status_desc["approved"]    = "<strong>Approved:</strong> The observership has been approved by staff and is awaiting preceptor confirmation.";
                $status_desc["pending"]     = "<strong>Pending:</strong> The learner has submitted the observerhsip and is awaiting staff approval.";
                $status_desc["rejected"]    = "<strong>Rejected:</strong> The observership has been rejected by staff. The learner may resubmit the observership request.";
                $status_desc["denied"]      = "<strong>Denied:</strong> The observership has been denied by the preceptor.";
                echo display_generic($status_desc[$PROCESSED["status"]]);
                ?>
                <form action="<?php echo ENTRADA_RELATIVE; ?>/admin/reports?section=<?php echo $SECTION; ?>&step=2" method="post" class="form-horizontal">
                    <input type="hidden" name="csv" value="1" />
                    
                    <?php if (isset($PROCESSED["group_id"])) { ?>
                    <input type="hidden" name="group_id" value="<?php echo $PROCESSED["group_id"]; ?>" />
                    <?php } ?>
                    
                    <?php
                        if (isset($PROCESSED["preceptors"]) && is_array($PROCESSED["preceptors"])) {
                            foreach ($PROCESSED["preceptors"] as $preceptor) {
                                ?>
                    <input type="hidden" name="preceptors[]" value="<?php echo clean_input($preceptor, "numeric"); ?>" />
                                <?php
                            }
                        }
                    ?>
                    <table width="100%" cellpadding="0" cellspacing="0" class="table table-bordered table-striped datatable">
                        <thead>
                            <th>Observership</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Student</th>
                            <th>Preceptor</th>
                            <th>Status</th>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $result) { ?>
                            <tr>
                                <td><a href="<?php echo ENTRADA_URL; ?>/admin/observerships?section=review&id=<?php echo $result["id"]; ?>"><?php echo $result["title"]; ?></a></td>
                                <td><?php echo date("Y-m-d", $result["start"]); ?></td>
                                <td><?php echo !empty($result["end"]) ? date("Y-m-d", $result["end"]) : date("Y-m-d", $result["start"]); ?></td>
                                <td><?php echo $result["student_name"]; ?></td>
                                <td><?php echo $result["preceptor_name"]; ?></td>
                                <td><?php echo $result["status"] . ($result["status"] == "approved" && $result["reflection_id"] ? "<br />[<a href=\"" . ENTRADA_URL . "/admin/observerships?section=reflection&id=".$result["reflection_id"]."\">reflection</a>]" : ""); ?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                    <div class="row-fluid space-above">
                        <input type="submit" class="btn btn-success pull-right" value="Download CSV" />
                    </div>
                </form>
                <?php
            }
		} else {
			add_notice("No student observerships were found within this date range. Please review the selected date range and run the report again. If you received this message in error please contact an administrator for assistance.");
			echo display_notice();
		}
		
	}
}
?>