<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Allows administrators to edit users from the entrada_auth.user_data table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_USERS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("user", "update", false)) {
	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	if ($PROXY_ID && $user_record) {
		?>
				<h1>Manage Incidents</h1>
				<div class="clearfix">		
					<a href="<?php echo ENTRADA_URL."/admin/users/manage/incidents?section=add&id=".$PROXY_ID; ?>" class="btn btn-primary">Add New Incident</a>
				</div>
							

							<?php
							$query = "	SELECT a.*, CONCAT_WS(', ', b.lastname, b.firstname) as `reported_by`
										FROM `".AUTH_DATABASE."`.`user_incidents` as a
										LEFT JOIN `".AUTH_DATABASE."`.`user_data` as b
										ON a.`incident_author_id` = b.`id`
										WHERE a.`proxy_id` = ".$db->qstr($PROXY_ID)."
										ORDER BY a.`incident_date` DESC";
							$results = $db->GetAll($query);
							if ($results) {
								?>
								<div style="padding-top: 10px; clear: both">
									<form action="<?php echo ENTRADA_URL; ?>/admin/users/manage/incidents?section=close&amp;id=<?php echo $PROXY_ID; ?>" method="post">
										<table class="tableList" cellspacing="0" summary="List of Incidents">
											<colgroup>
												<col class="modified" />
												<col class="title" />
												<col class="date" />
												<col class="date" />
												<col class="general" />
											</colgroup>
											<thead>
												<tr>
													<td class="modified">&nbsp;</td>
													<td class="title" style="border-left: none">Title</td>
													<td class="date sortedASC" style="border-left: none"><a>Incident Date</a></td>
													<td class="date" style="border-left: none">Follow-up Date</td>
													<td class="general" style="border-left: none">Reported By</td>
												</tr>
											</thead>
											<tfoot>
												<tr>
													<td colspan="5">&nbsp;</td>
												</tr>
												<tr>
													<td>&nbsp;</td>
													<td colspan="4">
														<input type="submit" class="btn" value="Close Selected" />
													</td>
												</tr>
											</tfoot>
											<tbody>
											<?php
											foreach ($results as $result) {
												$url = ENTRADA_URL."/admin/users/manage/incidents?section=edit&id=".$result["proxy_id"]."&incident-id=".$result["incident_id"];
												echo "<tr ".( !$result["incident_status"] ? " class=\"closed\"" : "").">\n";
												echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"delete_id[]\" id=\"delete_id[]\" value=\"".$result["incident_id"]."\" ".( !$result["incident_status"] ? " disabled=\"true\"" : "")." /></td>\n";
												echo "	<td class=\"title\"><a href=\"".$url."\" title=\"Incident Title: ".html_encode($result["incident_title"])."\">[".html_encode($result["incident_severity"])."] ".html_encode(limit_chars($result["incident_title"], 75))."</a></td>\n";
												echo "	<td class=\"date\"><a href=\"".$url."\" title=\"Incident Date\">".date(DEFAULT_DATE_FORMAT, $result["incident_date"])."</a></td>\n";
												echo "	<td class=\"date\"><a href=\"".$url."\" title=\"Incident Follow-Up Date\">".(isset($result["follow_up_date"]) && ((int)$result["follow_up_date"]) ? date(DEFAULT_DATE_FORMAT, $result["follow_up_date"]) : "")."</a></td>\n";
												echo "	<td class=\"general\"><a href=\"".$url."\" title=\"Reported By: ".html_encode(limit_chars($result["reported_by"], 14))."\">".html_encode($result["reported_by"])."</a></td>\n";
												echo "</tr>\n";
											}
											?>
											</tbody>
										</table>
									</form>
								</div>
								<?php
							} else {
								$NOTICE++;
								$NOTICESTR[] = "<strong>There are no incidents for this user.</strong><br /><br />If you would like to add a new incident, click the <strong>Add New Incident</strong> link above.";

								echo display_notice();
							}
		} else {
		$ERROR++;
		$ERRORSTR[] = "In order to edit a user profile you must provide a user identifier.";

		echo display_error();

		application_log("notice", "Failed to provide user identifer when attempting to edit a user profile.");
	}
}
?>