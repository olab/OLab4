<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * This file gives Entrada users the ability to update their user profile.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/
if (!defined("IN_MANAGE_USER_STUDENTS")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("mspr", "create", true)) {
	$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	add_error("Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");
	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
}  else {
	require_once(dirname(__FILE__)."/includes/functions.inc.php");
	
	$PROXY_ID = $user_record["id"];
	$user = User::fetchRowByID($user_record["id"]);
	
	$PAGE_META["title"]			= "MSPR";
	$PAGE_META["description"]	= "";
	$PAGE_META["keywords"]		= "";


	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/admin/users/manage/students?section=mspr&id=".$PROXY_ID, "title" => "MSPR");

	$PROCESSED		= array();
	$HEAD[] = "<script language='javascript' src='".ENTRADA_URL."/javascript/ActiveDataEntryProcessor.js'></script>";
	$HEAD[] = "<script language='javascript' src='".ENTRADA_URL."/javascript/ActiveEditor.js'></script>";
	$HEAD[] = "<script language='javascript' src='".ENTRADA_URL."/javascript/ActiveApprovalProcessor.js'></script>";
	$HEAD[] = "<script language='javascript' src='".ENTRADA_URL."/javascript/PriorityList.js'></script>";
	
	if ((is_array($_SESSION["permissions"])) && ($total_permissions = count($_SESSION["permissions"]) > 1)) {
		$sidebar_html  = "The following individual".((($total_permissions - 1) != 1) ? "s have" : " has")." given you access to their ".APPLICATION_NAME." permission levels:";
		$sidebar_html .= "<ul class=\"menu\">\n";
		foreach ($_SESSION["permissions"] as $access_id => $result) {
			if ($access_id != $ENTRADA_USER->getDefaultAccessId()) {
				$sidebar_html .= "<li class=\"checkmark\"><strong>".html_encode($result["fullname"])."</strong><br /><span class=\"content-small\">Exp: ".(($result["expires"]) ? date("D M d/y", $result["expires"]) : "Unknown")."</span></li>\n";
			}
		}
		$sidebar_html .= "</ul>\n";

		new_sidebar_item("Delegated Permissions", $sidebar_html, "delegated-permissions", "open");
	}

	
	$mspr = MSPR::get($user);
		
	if (!$mspr) { //no mspr yet. create one
		MSPR::create($user);
		$mspr = MSPR::get($user);
	}

	if (!$mspr) {
		add_notice("MSPR not yet available. Please try again later.");
		application_log("error", "Error creating MSPR for user " .$PROXY_ID. ": " . $name . "(".$number.")");
		display_status_messages();
	} else {
		
		$is_closed = $mspr->isClosed();
		
		$generated = $mspr->isGenerated();
		$revision = $mspr->getGeneratedTimestamp();
		$number = $user->getNumber();
		
		$name = $user->getFirstname() . " " . $user->getLastname();
		if (isset($_GET['get']) && ($type = $_GET['get'])) {
			$name = $user->getFirstname() . " " . $user->getLastname();
			switch($type) {
				case 'html':
					header('Content-type: text/html');
					header('Content-Disposition: filename="MSPR - '.$name.'('.$number.').html"');
					
					break;
				case 'pdf':
					header('Content-type: application/pdf');
					header('Content-Disposition: attachment; filename="MSPR - '.$name.'('.$number.').pdf"');
					break;
				default:
					add_error("Unknown file type: " . $type);
			}
			if (!has_error()) {
				ob_clear_open_buffers();
				flush();
				echo $mspr->getMSPRFile($type);
				exit();	
			}
			
		}
		
		$clerkship_core_completed = $mspr["Clerkship Core Completed"];
		$clerkship_core_pending = $mspr["Clerkship Core Pending"];
		$clerkship_elective_completed = $mspr["Clerkship Electives Completed"];
		$clinical_evaluation_comments = $mspr["Clinical Performance Evaluation Comments"];
		$critical_enquiry = $mspr["Critical Enquiry"];
		$student_run_electives = $mspr["Student-Run Electives"];
		$observerships = $mspr["Observerships"];
		$international_activities = $mspr["International Activities"];
		$internal_awards = $mspr["Internal Awards"];
		$external_awards = $mspr["External Awards"];
		$studentships = $mspr["Studentships"];
		$contributions = $mspr["Contributions to Medical School"];
		$leaves_of_absence = $mspr["Leaves of Absence"];
		$formal_remediations = $mspr["Formal Remediation Received"];
		$disciplinary_actions = $mspr["Disciplinary Actions"];
		$community_based_project = $mspr["Community Based Project"];
		$research_citations = $mspr["Research"];
					
		$year = $user->getGradYear();
		$class_data = MSPRClassData::get($year);
		
		$mspr_close = $mspr->getClosedTimestamp();
		
		if (!$mspr_close && $class_data) { //no custom time.. use the class default
			$mspr_close = $class_data->getClosedTimestamp();	
		}
		
		$faculty = ClinicalFacultyMembers::get();
			
		display_status_messages();
		add_mspr_management_sidebar();
	
?>
<script type="text/javascript">
	var submitting = false;
</script>
<h1><?php echo $translate->_("Medical School Performance Report"); ?><?php echo ($mspr->isAttentionRequired()) ? ": " . $translate->_("Attention Required") : ""; ?></h1>

<?php 
	if ($is_closed) {
		?>
<div class="display-notice"><p><strong>Note: </strong>This MSPR is now <strong>closed</strong> to student submissions. (Deadline was <?php echo date("F j, Y \a\\t g:i a",$mspr_close); ?>.) You may continue to approve, unapprove, or reject submissions, however students are unable to submit new data.</p>
	<?php if ($generated) {	?>
	<p>The latest revision of this MSPR is available in HTML and PDF below: </p>
	<span class="file-block"><a href="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=mspr&id=<?php echo $PROXY_ID; ?>&get=html"><img src="<?php echo ENTRADA_URL; ?>/serve-icon.php?ext=html" /> HTML</a>&nbsp;&nbsp;&nbsp;
	<a href="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=mspr&id=<?php echo $PROXY_ID; ?>&get=pdf"><img src="<?php echo ENTRADA_URL; ?>/serve-icon.php?ext=pdf" /> PDF</a>
	</span>
	<span class="edit-block"><a href="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=mspr-edit&id=<?php echo $PROXY_ID; ?>&from=user"><img src="<?php echo ENTRADA_URL; ?>/images/btn-edit.gif" /> Edit</a></span>
	<div class="clearfix">&nbsp;</div>
	<span class="last-update">Last Updated: <?php echo date("F j, Y \a\\t g:i a",$revision); ?></span>
	<?php }?>
	<hr />
	<a href="<?php echo ENTRADA_URL; ?>/admin/mspr?section=generate&id=<?php echo $PROXY_ID; ?>">Generate Report</a>
	</div>
		<?php
	} elseif ($mspr_close) {
		?>
<div class="display-notice"><strong>Note: </strong>The student submission deadline is <?php echo date("F j, Y \a\\t g:i a",$mspr_close); ?>. You may continue to approve, unapprove, or reject submissions after this date, however students will be unable to submit new data.</div>
		<?php
	}
?>

<div class="mspr-tree">

	<a href="#" onclick="CollapseSections(true)">Expand All</a> / <a href="#" onclick="CollapseSections(false)">Collapse All</a>

	<h2 title="Information Requiring Approval">Information Requiring Approval</h2>
	<div id="information-requiring-approval">
		<div class="instructions" style="margin-left:2em;margin-top:2ex;">
			<strong>Instructions</strong>
			<p>The sections below consist of student-submitted information. The submissions require approval or rejection.</p>
			<ul>
				<li>
					If an entry is verifiably accurate and meets criteria, it should be approved.
				</li>
				<li>
					If an entry is verifiably innacurate or contains errors in spelling or formatting, it should be rejected.
				</li>
				<li>
					If previously approved information comes into question, it's status can be reverted to unapproved, and rejected if deemed appropriate.
				</li>
				<li>
					All entries have a background color corresponding to their status: 
					<ul>
						<li>Gray - Approved</li>
						<li>Yellow - Pending Approval</li>
						<li>Red - Rejected</li>
					</ul>
				</li>
			</ul>
		</div>	
		<div class="section">
			<h3 title="Contributions to Medical School" class="collapsable<?php echo ($contributions->isAttentionRequired()) ? "" : " collapsed"; ?>"><?php echo $translate->_("Contributions to Medical School/Student Life") ?></h3>
			<div id="contributions-to-medical-school">
				<div id="add_contribution_link" style="float: right;">
					<a id="add_contribution" href="<?php echo ENTRADA_URL; ?>/profile?section=mspr&show=contributions_form&id=<?php echo $PROXY_ID; ?>" class="btn btn-small btn-success"><i class="icon-plus-sign icon-white"></i> Add Contribution</a>
				</div>
				<div class="instructions">
					<ul>
						<li>Examples of contributions to medical school/student life include:
							<ul>
								<li>Participation in School of Medicine student government</li>
								<li>Committees (such as admissions)</li>
								<li>Organizing extra-curricular learning activities and seminars</li>					
							</ul>
						</li>
						<li>Examples of submissions that do <em>not</em> qualify:
							<ul>
								<li>Captain of intramural soccer team.</li>
								<li>Member of Oprah's book of the month club.</li>
							</ul>
						</li>
					</ul>
				</div>
				<div id="update-contribution-box" class="modal hide">
					<div class="modal-header">
						<h3><?php echo $translate->_("Edit Contribution to Medical School/Student Life"); ?></h3>
					</div>
					<div class="modal-body">
						<form method="post">
							<table class="mspr_form">
								<colgroup>
									<col width="25%" />
									<col width="72%" />
								</colgroup>
								<tbody>
									<tr>
										<td><label class="form-required" for="role"><?php echo $translate->_("Role:"); ?></label></td>
										<td><input name="role" type="text" style="width:40%;" /><span class="content-small"><strong>Example</strong>: Interviewer</span></td>
									</tr>
									<tr>
										<td><label class="form-required" for="org_event"><?php echo $translate->_("Organization/Event:"); ?></label></td>
										<td><input name="org_event" type="text" style="width:40%;" /><span class="content-small"><strong>Example</strong>: Medical School Interview Weekend</span></td>
									</tr>
									<tr>
										<td><label class="form-required" for="start"><?php echo $translate->_("Start:"); ?></label></td>
										<td>
											<select name="start_month">
												<?php
												echo build_option("","Month",true);

												for($month_num = 1; $month_num <= 12; $month_num++) {
													echo build_option($month_num, getMonthName($month_num));
												}
												?>
											</select>
											<select name="start_year">
												<?php
												$cur_year = (int) date("Y");
												$start_year = $cur_year - 6;
												$end_year = $cur_year + 4;

												for ($opt_year = $start_year; $opt_year <= $end_year; ++$opt_year) {
														echo build_option($opt_year, $opt_year, $opt_year == $cur_year);
												}
												?>
											</select>
										</td>
									</tr>
									<tr>
										<td><label class="form-required" for="end"><?php echo $translate->_("End:"); ?></label></td>
										<td>
											<select tabindex="1" name="end_month">
												<?php
												echo build_option("","Month",true);

												for($month_num = 1; $month_num <= 12; $month_num++) {
													echo build_option($month_num, getMonthName($month_num));
												}
												?>
											</select>
											<select name="end_year">
												<?php
												echo build_option("","Year",true);
												$cur_year = (int) date("Y");
												$start_year = $cur_year - 6;
												$end_year = $cur_year + 4;

												for ($opt_year = $start_year; $opt_year <= $end_year; ++$opt_year) {
														echo build_option($opt_year, $opt_year, false);
												}
												?>
											</select>
										</td>
									</tr>
								</tbody>
							</table>
						</form>
					</div>
					<div class="modal-footer">
						<button class="btn modal-close">Close</button>
						<button class="btn btn-primary pull-right modal-confirm">Update</button>
					</div>
				</div>
				
				<div id="add-contribution-box" class="modal hide">
					<div class="modal-header">
						<h3><?php echo $translate->_("Add Contribution to Medical School/Student Life"); ?></h3>
					</div>
					<div class="modal-body">
						<form method="post">
							<input type="hidden" name="user_id" value="<?php echo $user->getID(); ?>" />
							<input type="hidden" name="action" value="Add" />
							<table class="mspr_form">
								<colgroup>
									<col width="25%" />
									<col width="72%" />
								</colgroup>
								<tbody>
									<tr>
										<td><label class="form-required" for="role"><?php echo $translate->_("Role:"); ?></label></td>
										<td><input name="role" type="text" style="width:40%;" /> <span class="content-small"><strong>Example</strong>: Interviewer</span></td>
									</tr>
									<tr>
										<td><label class="form-required" for="org_event"><?php echo $translate->_("Organization/Event:"); ?></label></td>
										<td><input name="org_event" type="text" style="width:40%;" /> <span class="content-small"><strong>Example</strong>: Medical School Interview Weekend</span></td>
									</tr>
									<tr>
										<td><label class="form-required" for="start"><?php echo $translate->_("Start:"); ?></label></td>
										<td>
											<select name="start_month">
												<?php
												echo build_option("","Month",true);

												for($month_num = 1; $month_num <= 12; $month_num++) {
													echo build_option($month_num, getMonthName($month_num));
												}
												?>
											</select>
											<select name="start_year">
												<?php
												$cur_year = (int) date("Y");
												$start_year = $cur_year - 6;
												$end_year = $cur_year + 4;

												for ($opt_year = $start_year; $opt_year <= $end_year; ++$opt_year) {
														echo build_option($opt_year, $opt_year, $opt_year == $cur_year);
												}
												?>
											</select>
										</td>
									</tr>
									<tr>
										<td><label class="form-required" for="end"><?php echo $translate->_("End:"); ?></label></td>
										<td>
											<select tabindex="1" name="end_month">
												<?php
												echo build_option("","Month",true);

												for($month_num = 1; $month_num <= 12; $month_num++) {
													echo build_option($month_num, getMonthName($month_num));
												}
												?>
											</select>
											<select name="end_year">
												<?php
												echo build_option("","Year",true);
												$cur_year = (int) date("Y");
												$start_year = $cur_year - 6;
												$end_year = $cur_year + 4;

												for ($opt_year = $start_year; $opt_year <= $end_year; ++$opt_year) {
														echo build_option($opt_year, $opt_year, false);
												}
												?>
											</select>
										</td>
									</tr>
								</tbody>
							</table>
						</form>
					</div>
					<div class="modal-footer">
						<button class="btn modal-close">Close</button>
						<button class="btn btn-primary pull-right modal-confirm">Submit</button>
					</div>
				</div>

				<div class="clear">&nbsp;</div>
				<div id="contributions"><?php echo display_contributions($contributions, "admin"); ?></div>
				<div class="clear">&nbsp;</div>
			</div>
		</div>
		
		<div class="section">
			<h3 title="Critical Enquiry" class="collapsable<?php echo ($critical_enquiry && $critical_enquiry->isAttentionRequired()) ? "" : " collapsed"; ?>"><?php echo $translate->_("Critical Enquiry"); ?></h3>
			<div id="critical-enquiry">
				<div id="add_critical_enquiry_link" style="float: right;">
					<a id="add_critical_enquiry" href="<?php echo ENTRADA_URL; ?>/profile?section=mspr&id=<?php echo $PROXY_ID; ?>"  class="btn btn-small btn-success"><i class="icon-plus-sign icon-white"></i> Add Critical Enquiry</a>
				</div>
				<div class="clear">&nbsp;</div>
				
				<div id="add-critical-enquiry-box" class="modal hide">
					<div class="modal-header">
						<h3><?php echo $translate->_("Add Critical Enquiry"); ?></h3>
					</div>
					<div class="modal-body">
						<form method="post">
							<input type="hidden" name="user_id" value="<?php echo $user->getID(); ?>" />
							<input type="hidden" name="action" value="Add"/>

							<table class="mspr_form">
								<colgroup>
									<col width="3%" />
									<col width="25%" />
									<col width="72%" />
								</colgroup>
								<tbody>
									<tr>
										<td>&nbsp;</td>
										<td><label class="form-required" for="title"><?php echo $translate->_("Title:"); ?></label></td>
										<td><input name="title" type="text" style="width:40%;" value="" /></td>
									</tr>
									<tr>
										<td>&nbsp;</td>
										<td><label class="form-required" for="organization"><?php echo $translate->_("Organization:"); ?></label></td>
										<td><input name="organization" type="text" style="width:40%;" value="" /> <span class="content-small"><strong>Example</strong>: Queen's University</span></td>
									</tr>
									<tr>
										<td>&nbsp;</td>
										<td><label class="form-required" for="location"><?php echo $translate->_("Location:"); ?></label></td>
										<td><input name="location" type="text" style="width:40%;" value="" /> <span class="content-small"><strong>Example</strong>: Kingston, Ontario</span></td>
									</tr>
									<tr>
										<td>&nbsp;</td>
										<td><label class="form-required" for="supervisor"><?php echo $translate->_("Supervisor:"); ?></label></td>
										<td><input name="supervisor" type="text" style="width:40%;" value="" /> <span class="content-small"><strong>Example</strong>: Dr. Nick Riviera</span></td>
									</tr>
								</tbody>
							</table>
						</form>
					</div>
					<div class="modal-footer">
						<button class="btn modal-close">Close</button>
						<button class="btn btn-primary pull-right modal-confirm">Submit</button>
					</div>
				</div>
				
				<div id="update-critical-enquiry-box" class="modal hide">
					<div class="modal-header">
						<h3><?php echo $translate->_("Edit Critical Enquiry"); ?></h3>
					</div>
					<div class="modal-body">
						<form method="post">
							<table class="mspr_form">
								<colgroup>
									<col width="3%" />
									<col width="25%" />
									<col width="72%" />
								</colgroup>
								<tbody>
									<tr>
										<td>&nbsp;</td>
										<td><label class="form-required" for="title"><?php echo $translate->_("Title:"); ?></label></td>
										<td><input name="title" type="text" style="width:40%;" value="" /></td>
									</tr>
									<tr>
										<td>&nbsp;</td>
										<td><label class="form-required" for="organization"><?php echo $translate->_("Organization:"); ?></label></td>
										<td><input name="organization" type="text" style="width:40%;" value="" /> <span class="content-small"><strong>Example</strong>: Queen's University</span></td>
									</tr>
									<tr>
										<td>&nbsp;</td>
										<td><label class="form-required" for="location"><?php echo $translate->_("Location:"); ?></label></td>
										<td><input name="location" type="text" style="width:40%;" value="" /> <span class="content-small"><strong>Example</strong>: Kingston, Ontario</span></td>
									</tr>
									<tr>
										<td>&nbsp;</td>
										<td><label class="form-required" for="supervisor"><?php echo $translate->_("Supervisor:"); ?></label></td>
										<td><input name="supervisor" type="text" style="width:40%;" value="" /> <span class="content-small"><strong>Example</strong>: Dr. Nick Riviera</span></td>
									</tr>
								</tbody>
							</table>
						</form>
					</div>
					<div class="modal-footer">
						<button class="btn modal-close">Close</button>
						<button class="btn btn-primary pull-right modal-confirm">Update</button>
					</div>
				</div>

				<div id="critical_enquiry"><?php echo display_supervised_project($critical_enquiry,"admin"); ?></div>
			</div>
		</div>
		
		<div class="section">
			<h3 title="Community-Based Project" class="collapsable<?php echo ($community_based_project && $community_based_project->isAttentionRequired()) ? "" : " collapsed"; ?>">Community-Based Project</h3>
			<div id="community-based-project">
				<div id="add_community_based_project_link" style="float: right;">
					<a id="add_community_based_project" href="<?php echo ENTRADA_URL; ?>/profile?section=mspr&show=community_based_project_form&id=<?php echo $PROXY_ID; ?>" class="btn btn-small btn-success"><i class="icon-plus-sign icon-white"></i> Add Community-Based Project</a>
				</div>
				<div class="clear">&nbsp;</div>

				<div id="add-community-based-project-box" class="modal hide">
					<div class="modal-header">
						<h3><?php echo $translate->_("Add Community Based Project"); ?></h3>
					</div>
					<div class="modal-body">
						<form method="post">
							<input type="hidden" name="user_id" value="<?php echo $user->getID(); ?>" />
							<input type="hidden" name="action" value="Add" />

							<table class="mspr_form">
								<colgroup>
									<col width="3%" />
									<col width="25%" />
									<col width="72%" />
								</colgroup>
								<tbody>
									<tr>
										<td>&nbsp;</td>
										<td><label class="form-required" for="title"><?php echo $translate->_("Title:"); ?></label></td>
										<td><input name="title" type="text" style="width:40%;" value="" /></td>
									</tr>
									<tr>
										<td>&nbsp;</td>
										<td><label class="form-required" for="organization"><?php echo $translate->_("Organization:"); ?></label></td>
										<td><input name="organization" type="text" style="width:40%;" value="" /> <span class="content-small"><strong>Example</strong>: Queen's University</span></td>
									</tr>
									<tr>
										<td>&nbsp;</td>
										<td><label class="form-required" for="location"><?php echo $translate->_("Location:"); ?></label></td>
										<td><input name="location" type="text" style="width:40%;" value="" /> <span class="content-small"><strong>Example</strong>: Kingston, Ontario</span></td>
									</tr>
									<tr>
										<td>&nbsp;</td>
										<td><label class="form-required" for="supervisor"><?php echo $translate->_("Supervisor:"); ?></label></td>
										<td><input name="supervisor" type="text" style="width:40%;" value="" /> <span class="content-small"><strong>Example</strong>: Dr. Nick Riviera</span></td>
									</tr>
								</tbody>
							</table>
						</form>
					</div>
					<div class="modal-footer">
						<button class="btn modal-close">Close</button>
						<button class="btn btn-primary pull-right modal-confirm">Submit</button>
					</div>
				</div>
				
				<div id="update-community-based-project-box" class="modal hide">
					<div class="modal-header">
						<h3><?php echo $translate->_("Edit Community Based Project"); ?></h3>
					</div>
					<div class="modal-body">
						<form method="post">
							<table class="mspr_form">
								<colgroup>
									<col width="3%" />
									<col width="25%" />
									<col width="72%" />
								</colgroup>
								<tbody>
									<tr>
										<td>&nbsp;</td>
										<td><label class="form-required" for="title"><?php echo $translate->_("Title:"); ?></label></td>
										<td><input name="title" type="text" style="width:40%;" value="" /></td>
									</tr>
									<tr>
										<td>&nbsp;</td>
										<td><label class="form-required" for="organization"><?php echo $translate->_("Organization:"); ?></label></td>
										<td><input name="organization" type="text" style="width:40%;" value="" /> <span class="content-small"><strong>Example</strong>: Queen's University</span></td>
									</tr>
									<tr>
										<td>&nbsp;</td>
										<td><label class="form-required" for="location"><?php echo $translate->_("Location:"); ?></label></td>
										<td><input name="location" type="text" style="width:40%;" value="" /> <span class="content-small"><strong>Example</strong>: Kingston, Ontario</span></td>
									</tr>
									<tr>
										<td>&nbsp;</td>
										<td><label class="form-required" for="supervisor"><?php echo $translate->_("Supervisor:"); ?></label></td>
										<td><input name="supervisor" type="text" style="width:40%;" value="" /> <span class="content-small"><strong>Example</strong>: Dr. Nick Riviera</span></td>
									</tr>
								</tbody>
							</table>
						</form>
					</div>
					<div class="modal-footer">
						<button class="btn modal-close">Close</button>
						<button class="btn btn-primary pull-right modal-confirm">Update</button>
					</div>
				</div>

				<div id="community_based_project"><?php echo display_supervised_project($community_based_project,"admin"); ?></div>
			</div>
		</div>
		
		<div class="section">
			<h3 title="Research" class="collapsable<?php echo ($research_citations->isAttentionRequired()) ? "" : " collapsed"; ?>">Research</h3>
			<div id="research">
				<div id="add_research_citation_link" style="float: right;">
					<a id="add_research_citation" href="<?php echo ENTRADA_URL; ?>/profile?section=mspr&id=<?php echo $PROXY_ID; ?>" class="btn btn-small btn-success"><i class="icon-plus-sign icon-white"></i> Add Research Citation</a>
				</div>
				<div class="instructions">
					<ul>
						<li>Only approve citations of published research in which <?php echo $name; ?> was a named author</li>
						<li>Approve a maximum of <em>six</em> research citations</li>
						<li>Approved research citations should be in a format following <a href="http://owl.english.purdue.edu/owl/resource/747/01/">MLA guidelines</a></li>
					</ul>
				</div>
				<div class="clear">&nbsp;</div>
				
				<div id="update-research-box" class="modal hide">
					<div class="modal-header">
						<h3><?php echo $translate->_("Edit Research Citation"); ?></h3>
					</div>
					<div class="modal-body">
						<form method="post">
							<table class="mspr_form">
								<tbody>
									<tr>
										<td><label class="form-required" for="details"><?php echo $translate->_("Citation:"); ?></label></td>
									</tr>
									<tr>
										<td><textarea name="details" style="width:96%;height:25ex;"></textarea><br /></td>
									</tr>
								</tbody>
							</table>
						</form>
					</div>
					<div class="modal-footer">
						<button class="btn modal-close">Close</button>
						<button class="btn btn-primary pull-right modal-confirm">Update</button>
					</div>
				</div>
				
				<div id="add-research-box" class="modal hide">
					<div class="modal-header">
						<h3><?php echo $translate->_("Add Research Citation"); ?></h3>
					</div>
					<div class="modal-body">
						<form method="post">
							<input type="hidden" name="user_id" value="<?php echo $user->getID(); ?>" />
							<input type="hidden" name="action" value="Add" />
							<table class="mspr_form">
								<tbody>
									<tr>
										<td><label class="form-required" for="details"><?php echo $translate->_("Citation:"); ?></label></td>
									</tr>
									<tr>
										<td><textarea name="details" style="width:96%;height:25ex;"></textarea><br /></td>
									</tr>
								</tbody>
							</table>
						</form>
					</div>
					<div class="modal-footer">
						<button class="btn modal-close">Close</button>
						<button class="btn btn-primary pull-right modal-confirm">Submit</button>
					</div>
				</div>

				<div id="research_citations">
					<?php echo display_research_citations($research_citations,"admin"); ?>
				</div>
				<div class="clear">&nbsp;</div>
			</div>
		</div>
		
		<div class="section">
			<h3 title="External Awards" class="collapsable<?php echo ($external_awards->isAttentionRequired()) ? "" : " collapsed"; ?>">External Awards</h3>
			<div id="external-awards">
				<div id="add_external_award_link" style="float: right;">
					<a id="add_external_award" href="#external-awards-section" class="btn btn-small btn-success"><i class="icon-plus-sign icon-white"></i> Add External Award</a>
				</div>
				<div class="instructions">
					<ul>
						<li>Only awards of academic significance should be considered.</li>
						<li>Award terms must be provided to be approved. Awards not accompanied by terms should be rejected.</li>
					</ul>
				</div>
				<div id="update-external-award-box" class="modal hide">
					<div class="modal-header">
						<h3><?php echo $translate->_("Edit External Award"); ?></h3>
					</div>
					<div class="modal-body">
						<form method="post">
							<table class="mspr_form">
								<colgroup>
									<col width="25%" />
									<col width="75%" />
								</colgroup>
								<tbody>
									<tr>
										<td><label class="form-required" for="title"><?php echo $translate->_("Title:"); ?></label></td>
										<td><input name="title" type="text" style="width:60%;" /></td>
									</tr>
									<tr>
										<td><label class="form-required" for="body"><?php echo $translate->_("Awarding Body:"); ?></label></td>
										<td><input name="body" type="text" style="width:60%;" /></td>
									</tr>
									<tr>
										<td valign="top"><label class="form-required" for="terms"><?php echo $translate->_("Award Terms:"); ?></label></td>
										<td><textarea name="terms" style="width: 80%; height: 12ex;" cols="65" rows="20"></textarea></td>
									</tr>
									<tr>
										<td><label class="form-required" for="year"><?php echo $translate->_("Year Awarded:"); ?></label></td>
										<td>
											<select name="year">
												<?php

												$cur_year = (int) date("Y");
												$start_year = $cur_year - 10;
												$end_year = $cur_year + 4;

												for ($opt_year = $start_year; $opt_year <= $end_year; ++$opt_year) {
														echo build_option($opt_year, $opt_year, $opt_year == $cur_year);
												}

												?>
											</select>
										</td>
									</tr>
								</tbody>
							</table>
						</form>
					</div>
					<div class="modal-footer">
						<button class="btn modal-close">Close</button>
						<button class="btn btn-primary pull-right modal-confirm">Update</button>
					</div>
				</div>
				
				<div id="add-external-award-box" class="modal hide">
					<div class="modal-header">
						<h3><?php echo $translate->_("Add External Award"); ?></h3>
					</div>
					<div class="modal-body">
						<form method="post">
							<input type="hidden" name="user_id" value="<?php echo $user->getID(); ?>" />
							<input type="hidden" name="action" value="Add" />
							<table class="mspr_form">
								<colgroup>
									<col width="25%" />
									<col width="75%" />
								</colgroup>
								<tbody>
									<tr>
										<td><label class="form-required" for="title"><?php echo $translate->_("Title:"); ?></label></td>
										<td><input name="title" type="text" style="width:60%;" /></td>
									</tr>
									<tr>
										<td><label class="form-required" for="body"><?php echo $translate->_("Awarding Body:"); ?></label></td>
										<td><input name="body" type="text" style="width:60%;" /></td>
									</tr>
									<tr>
										<td valign="top"><label class="form-required" for="terms"><?php echo $translate->_("Award Terms:"); ?></label></td>
										<td><textarea name="terms" style="width: 80%; height: 12ex;" cols="65" rows="20"></textarea></td>
									</tr>
									<tr>
										<td><label class="form-required" for="year"><?php echo $translate->_("Year Awarded:"); ?></label></td>
										<td>
											<select name="year">
												<?php

												$cur_year = (int) date("Y");
												$start_year = $cur_year - 10;
												$end_year = $cur_year + 4;

												for ($opt_year = $start_year; $opt_year <= $end_year; ++$opt_year) {
														echo build_option($opt_year, $opt_year, $opt_year == $cur_year);
												}

												?>
											</select>
										</td>
									</tr>
								</tbody>
							</table>
						</form>
					</div>
					<div class="modal-footer">
						<button class="btn modal-close">Close</button>
						<button class="btn btn-primary pull-right modal-confirm">Submit</button>
					</div>
				</div>

				<div id="external_awards"><?php echo display_external_awards($external_awards,"admin"); ?></div>
			</div>
		</div>
		
		
		<div class="section">
			<h3 title="Observerships Section" class="collapsable collapsed">Observerships</h3>
			<div id="observerships-section">
				<div id="observerships"><?php echo display_observerships($observerships,"admin", true); ?></div>
			</div>
		</div>		
	</div>

	<h2 title="Required Information Section">Information Requiring Entry</h2>
	<div id="required-information-section">
	
		<div class="section">
			<h3 title="Clinical Performance Evaluation Comments Section" class="collapsable collapsed">Clinical Performance Evaluation Comments</h3>
			<div id="clinical-performance-evaluation-comments-section">
				<div id="add_clineval_link" style="float: right;">
					<a id="add_clineval" href="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=mspr&id=<?php echo $PROXY_ID; ?>" class="btn btn-small btn-success"><i class="icon-plus-sign icon-white"></i> Add Clinical Performance Evaluation Comment</a>
				</div>
				<div class="instructions">
					<p>Comments should be copied in whole or in part from Clinical Performance Evaluations from the student's clerkship rotations and electives.</p>
					<p>There should be one comment for each core rotation and one per received elective.</p>
				</div>
				<div id="update-clineval-box" class="modal hide">
					<div class="modal-header">
						<h3><?php echo $translate->_("Edit Clinical Performance Evaluation Comment"); ?></h3>
					</div>
					<div class="modal-body">
						<form method="post" name="edit_clineval_form">
							<table class="mspr_form">
								<colgroup>
									<col width="25%" />
									<col width="75%" />
								</colgroup>
								<tbody>
									<tr>
										<td><label class="form-required" for="source">Source:</label></td>
										<td><input type="text" name="source" /><span class="content-small"> <strong>Example</strong>: Pediatrics Rotation</span></td>
									</tr>
									<tr>
										<td colspan="2"><label class="form-required" for="text">Comment:</label></td>
									</tr>
									<tr>
										<td colspan="2"><textarea name="text" style="width:96%;height:30ex;"></textarea><br /></td>
									</tr>
								</tbody>
							</table>
						</form>
					</div>
					<div class="modal-footer">
						<button class="btn modal-close">Close</button>
						<button class="btn btn-primary pull-right modal-confirm" id="edit-submission-confirm">Update</button>
					</div>
				</div>
				
				<div id="add-clineval-box" class="modal hide">
					<div class="modal-header">
						<h3><?php echo $translate->_("Add Clinical Performance Evaluation Comment"); ?></h3>
					</div>
					<div class="modal-body">
						<form method="post" name="add_int_act_form">
							<input type="hidden" name="user_id" value="<?php echo $user->getID(); ?>" />
							<input type="hidden" name="action" value="Add" />
							<table class="mspr_form">
								<colgroup>
									<col width="25%" />
									<col width="75%" />
								</colgroup>
								<tbody>
									<tr>
										<td><label class="form-required" for="source">Source:</label></td>
										<td><input type="text" name="source" /><span class="content-small"> <strong>Example</strong>: Pediatrics Rotation</span></td>
									</tr>
									<tr>
										<td colspan="2"><label class="form-required" for="text">Comment:</label></td>
									</tr>
									<tr>
										<td colspan="2"><textarea name="text" style="width:96%;height:30ex;"></textarea><br /></td>
									</tr>
								</tbody>
							</table>
						</form>
					</div>
					<div class="modal-footer">
						<button class="btn modal-close">Close</button>
						<button class="btn btn-primary pull-right modal-confirm">Submit</button>
					</div>
				</div>
				
				<div class="clear">&nbsp;</div>

				<div id="add_clineval" class="modal hide">
					<div class="modal-header">
						<h3><?php echo $translate->_("Add Clinical"); ?></h3>
					</div>
					<div class="modal-body">
						<form id="add_clineval_form" name="add_clineval_form" action="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=mspr&id=<?php echo $PROXY_ID; ?>" method="post" style="display:none;" >
							<input type="hidden" name="user_id" value="<?php echo $PROXY_ID; ?>" />
							<table class="mspr_form">
								<colgroup>
									<col width="3%" />
									<col width="25%" />
									<col width="72%" />
								</colgroup>
								<tfoot>
									<tr>
										<td colspan="3">&nbsp;</td>
									</tr>
									<tr>
										<td colspan="3" style="border-top: 2px #CCCCCC solid; padding-top: 5px; text-align: right">
											<input type="submit" class="btn btn-primary" name="action" value="Add" />
											<div id="hide_clineval_link" style="display:inline-block;">
												<a id="hide_clineval" href="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=mspr&id=<?php echo $PROXY_ID; ?>" class="btn btn-small btn-success"><i class="icon-plus-sign icon-white"></i> Cancel Adding Comment</a>
											</div>
										</td>
									</tr>
								</tfoot>
								<tbody>
									<tr>
										<td>&nbsp;</td>
										<td><label class="form-required" for="source">Source:</label></td>
										<td><input type="text" name="source" /><span class="content-small"> <strong>Example</strong>: Pediatrics Rotation</span></td>
									</tr>
									<tr>
										<td>&nbsp;</td>
										<td valign="top"><label class="form-required" for="text">Comment:</label></td>
										<td><textarea name="text" style="width:80%;height:12ex;"></textarea><br /></td>
									</tr>
								</tbody>
							</table>
							<div class="clear">&nbsp;</div>
						</form>
					</div>
					<div class="modal-footer">
						<button class="btn modal-close">Close</button>
						<button class="btn btn-primary pull-right modal-confirm">Update</button>
					</div>
				</div>

				<div id="clinical_performance_eval_comments"><?php echo display_clineval($clinical_evaluation_comments,"admin"); ?></div>
			</div>
		</div>
		
		<div class="section">
			<h3 title="Summer Studentships" class="collapsable collapsed">Summer Studentships</h3>
			<div id="summer-studentships">
				<div id="add_studentship_link" style="float: right;">
					<a id="add_studentship" href="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=mspr&show=studentship_form&id=<?php echo $PROXY_ID; ?>" class="btn btn-small btn-success"><i class="icon-plus-sign icon-white"></i> Add Studentship</a>
				</div>
				<div class="clear">&nbsp;</div>
			
				<div id="update-studentship-box" class="modal hide">
					<div class="modal-header">
						<h3><?php echo $translate->_("Edit Studentship"); ?></h3>
					</div>
					<div class="modal-body">
						<form method="post" name="edit_studentship_form">
							<table class="mspr_form">
								<colgroup>
									<col width="25%" />
									<col width="75%" />
								</colgroup>
								<tbody>
									<tr>
										<td><label class="form-required" for="title"><?php echo $translate->_("Title:"); ?></label></td>
										<td><input type="text" name="title" /> <span class="content-small"><strong>Example</strong>: The Canadian Institute of Health Studentship</span></td>
									</tr>
									<tr>
										<td><label class="form-required" for="year"><?php echo $translate->_("Year Awarded:"); ?></label></td>
										<td>
											<select name="year">
												<?php

												$cur_year = (int) date("Y");
												$start_year = $cur_year - 4;
												$end_year = $cur_year + 4;

												for ($opt_year = $start_year; $opt_year <= $end_year; ++$opt_year) {
														echo build_option($opt_year, $opt_year, $opt_year == $cur_year);
												}

												?>
											</select>
										</td>
									</tr>
								</tbody>
							</table>
						</form>
					</div>
					<div class="modal-footer">
						<button class="btn modal-close">Close</button>
						<button class="btn btn-primary pull-right modal-confirm" id="edit-submission-confirm">Update</button>
					</div>
				</div>

				<div id="add-studentship-box" class="modal hide">
					<div class="modal-header">
						<h3><?php echo $translate->_("Add Studentship"); ?></h3>
					</div>
					<div class="modal-body">
						<form method="post" name="add_studentship_form">
							<input type="hidden" name="user_id" value="<?php echo $user->getID(); ?>" />
							<input type="hidden" name="action" value="Add" />
							<table class="mspr_form">
								<colgroup>
									<col width="25%" />
									<col width="75%" />
								</colgroup>
								<tbody>
									<tr>
										<td><label class="form-required" for="title"><?php echo $translate->_("Title:"); ?></label></td>
										<td><input type="text" name="title" /> <span class="content-small"><strong>Example</strong>: The Canadian Institute of Health Studentship</span></td>
									</tr>
									<tr>
										<td><label class="form-required" for="year"><?php echo $translate->_("Year Awarded:"); ?></label></td>
										<td>
											<select name="year">
												<?php

												$cur_year = (int) date("Y");
												$start_year = $cur_year - 4;
												$end_year = $cur_year + 4;

												for ($opt_year = $start_year; $opt_year <= $end_year; ++$opt_year) {
														echo build_option($opt_year, $opt_year, $opt_year == $cur_year);
												}

												?>
											</select>
										</td>
									</tr>
								</tbody>
							</table>
						</form>
					</div>
					<div class="modal-footer">
						<button class="btn modal-close">Close</button>
						<button class="btn btn-primary pull-right modal-confirm">Submit</button>
					</div>
				</div>
				
				<div id="studentships"><?php echo display_studentships($studentships,"admin"); ?></div>
			</div>
		</div>

		<div class="section">
			<h3 title="International Activities" class="collapsable collapsed"><?php echo $translate->_("International Activities:"); ?></h3>
			<div id="international-activities">
				<div id="add_int_act_link" style="float: right;">
					<a id="add_int_act" href="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=mspr&show=int_act_form&id=<?php echo $PROXY_ID; ?>" class="btn btn-small btn-success"><i class="icon-plus-sign icon-white"></i> Add Activity</a>
				</div>
				<div class="clear">&nbsp;</div>
				
				<div id="update-int-act-box" class="modal hide">
					<div class="modal-header">
						<h3><?php echo $translate->_("Edit International Activity"); ?></h3>
					</div>
					<div class="modal-body">
						<form method="post" name="edit_int_act_form">
							<table class="mspr_form">
								<colgroup>
									<col width="25%" />
									<col width="25%" />
									<col width="50%" />
								</colgroup>
								<tbody>
									<tr>
										<td><label class="form-required" for="title"><?php echo $translate->_("Title:"); ?></label></td>
										<td><input name="title" type="text" /></td><td><span class="content-small"><strong>Example:</strong> Geriatrics Observership</span></td>
									</tr>
									<tr>
										<td><label class="form-required" for="site"><?php echo $translate->_("Site:"); ?></label></td>
										<td><input name="site" type="text" /></td><td><span class="content-small"><strong>Example:</strong> Tokyo Metropolitan Hospital</span></td>
									</tr>
									<tr>
										<td><label class="form-required" for="location"><?php echo $translate->_("Location:"); ?></label></td>
										<td><input name="location" type="text" /></td><td><span class="content-small"><strong>Example:</strong> Tokyo, Japan</span></td>
									</tr>
									<tr>
										<td><label class="form-required" for="start"><?php echo $translate->_("Start Date:"); ?></label></td>
										<td>
											<input type="text" name="start" id="int_act_start_edit" type="text" /></td><td><span class="content-small"><strong>Format:</strong> yyyy-mm-dd</span>
										</td>
									</tr>
									<tr>
										<td><label class="form-required" for="end"><?php echo $translate->_("End Date:"); ?></label></td>
										<td>
											<input type="text" name="end" id="int_act_end_edit" type="text" /></td><td>
										</td>
									</tr>
								</tbody>
							</table>
						</form>
					</div>
					<div class="modal-footer">
						<button class="btn modal-close">Close</button>
						<button class="btn btn-primary pull-right modal-confirm" id="edit-submission-confirm">Update</button>
					</div>
				</div>
				
				<div id="add-int-act-box" class="modal hide">
					<div class="modal-header">
						<h3><?php echo $translate->_("Add International Activity"); ?></h3>
					</div>
					<div class="modal-body">
						<form method="post" name="add_int_act_form">
							<input type="hidden" name="user_id" value="<?php echo $user->getID(); ?>" />
							<input type="hidden" name="action" value="Add" />
							<table class="mspr_form">
								<colgroup>
									<col width="25%" />
									<col width="25%" />
									<col width="50%" />
								</colgroup>
								<tbody>
									<tr>
										<td><label class="form-required" for="title"><?php echo $translate->_("Title:"); ?></label></td>
										<td><input name="title" type="text" /></td><td><span class="content-small"><strong>Example:</strong> Geriatrics Observership</span></td>
									</tr>
									<tr>
										<td><label class="form-required" for="site"><?php echo $translate->_("Site:"); ?></label></td>
										<td><input name="site" type="text" /></td><td><span class="content-small"><strong>Example:</strong> Tokyo Metropolitan Hospital</span></td>
									</tr>
									<tr>
										<td><label class="form-required" for="location"><?php echo $translate->_("Location:"); ?></label></td>
										<td><input name="location" type="text" /></td><td><span class="content-small"><strong>Example:</strong> Tokyo, Japan</span></td>
									</tr>
									<tr>
										<td><label class="form-required" for="start"><?php echo $translate->_("Start Date:"); ?></label></td>
										<td>
											<input type="text" name="start" id="int_act_start" /></td><td><span class="content-small"><strong>Format:</strong> yyyy-mm-dd</span>
										</td>
									</tr>
									<tr>
										<td><label class="form-required" for="end"><?php echo $translate->_("End Date:"); ?></label></td>
										<td>
											<input type="text" type="text" name="end" id="int_act_end" /></td><td>
										</td>
									</tr>
								</tbody>

							</table>
						</form>
					</div>
					<div class="modal-footer">
						<button class="btn modal-close">Close</button>
						<button class="btn btn-primary pull-right modal-confirm">Submit</button>
					</div>
				</div>

				<div id="int_acts"><?php echo display_international_activities($international_activities,"admin"); ?></div>
			</div>
		</div>
		
		<div class="section">
			<h3 title="Student-Run Electives" class="collapsable collapsed"><?php echo $translate->_("Student-Run Electives"); ?></h3>
			<div id="student-run-electives">
				<div id="add_student_run_elective_link" style="float: right;">
					<a id="add_student_run_elective" href="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=mspr&id=<?php echo $PROXY_ID; ?>" class="btn btn-small btn-success"><i class="icon-plus-sign icon-white"></i> Add Student Run Elective</a>
				</div>
				
				<div class="clear">&nbsp;</div>
				
				<div id="add-sre-box" class="modal hide">
					<div class="modal-header">
						<h3><?php echo $translate->_("Add Student-Run Elective/Interest Group"); ?></h3>
					</div>
					<div class="modal-body">
						<form method="post" name="add_sre_form">
							<input type="hidden" name="user_id" value="<?php echo $user->getID(); ?>" />
							<input type="hidden" name="action" value="Add" />

							<table class="mspr_form">
								<colgroup>
									<col width="25%" />
									<col width="75%" />
								</colgroup>
								<tbody>
									<tr>
										<td><label class="form-required" for="group_name"><?php echo $translate->_("Group Name:"); ?></label></td>
										<td><input name="group_name" type="text" /> <span class="content-small"><strong>Example</strong>: Emergency Medicine Elective</span></td>
									</tr>
									<tr>
										<td><label class="form-required" for="university"><?php echo $translate->_("University:"); ?></label></td>
										<td><input name="university" value="Queen's University" type="text" /> <span class="content-small"><strong>Example</strong>: Queen's University</span></td>
									</tr>
									<tr>
										<td><label class="form-required" for="location"><?php echo $translate->_("Location:"); ?></label></td>
										<td><input name="location" value="Kingston, ON" type="text" /> <span class="content-small"><strong>Example</strong>: Kingston, Ontario</span></td>
									</tr>
									<tr>
										<td><label class="form-required" for="start"><?php echo $translate->_("Start:"); ?></label></td>
										<td>
											<select name="start_month">
												<?php
												echo build_option("","Month",true);

												for($month_num = 1; $month_num <= 12; $month_num++) {
													echo build_option($month_num, getMonthName($month_num));
												}
												?>
											</select>
											<select name="start_year">
												<?php
												$cur_year = (int) date("Y");
												$start_year = $cur_year - 6;
												$end_year = $cur_year + 4;

												for ($opt_year = $start_year; $opt_year <= $end_year; ++$opt_year) {
														echo build_option($opt_year, $opt_year, $opt_year == $cur_year);
												}
												?>
											</select>
										</td>
									</tr>
									<tr>
										<td><label class="form-required" for="end">End:</label></td>
										<td>
											<select name="end_month">
												<?php
												echo build_option("","Month",true);

												for($month_num = 1; $month_num <= 12; $month_num++) {
													echo build_option($month_num, getMonthName($month_num));
												}
												?>
											</select>
											<select name="end_year">
												<?php
												echo build_option("","Year",true);
												$cur_year = (int) date("Y");
												$start_year = $cur_year - 6;
												$end_year = $cur_year + 4;

												for ($opt_year = $start_year; $opt_year <= $end_year; ++$opt_year) {
														echo build_option($opt_year, $opt_year, false);
												}
												?>
											</select>
										</td>
									</tr>
								</tbody>
							</table>
						</form>
					</div>
					<div class="modal-footer">
						<button class="btn modal-close">Close</button>
						<button class="btn btn-primary pull-right modal-confirm">Submit</button>
					</div>
				</div>
				
				<div id="update-sre-box" class="modal hide">
					<div class="modal-header">
						<h3><?php echo $translate->_("Edit Student-Run Elective/Interest Group"); ?></h3>
					</div>
					<div class="modal-body">
						<form method="post" name="edit_sre_form">
							<input type="hidden" name="user_id" value="<?php echo $user->getID(); ?>" />
							<input type="hidden" name="action" value="Edit" />

							<table class="mspr_form">
								<colgroup>
									<col width="25%" />
									<col width="75%" />
								</colgroup>
								<tbody>
									<tr>
										<td><label class="form-required" for="group_name"><?php echo $translate->_("Group Name:"); ?></label></td>
										<td><input name="group_name" /> <span class="content-small"><strong>Example</strong>: Emergency Medicine Elective</span></td>
									</tr>
									<tr>
										<td><label class="form-required" for="university"><?php echo $translate->_("University:"); ?></label></td>
										<td><input name="university" value="Queen's University" /> <span class="content-small"><strong>Example</strong>: Queen's University</span></td>
									</tr>
									<tr>
										<td><label class="form-required" for="location"><?php echo $translate->_("Location:"); ?></label></td>
										<td><input name="location" value="Kingston, ON" /> <span class="content-small"><strong>Example</strong>: Kingston, Ontario</span></td>
									</tr>
									<tr>
										<td><label class="form-required" for="start"><?php echo $translate->_("Start:"); ?></label></td>
										<td>
											<select name="start_month">
												<?php
												echo build_option("","Month",true);

												for($month_num = 1; $month_num <= 12; $month_num++) {
													echo build_option($month_num, getMonthName($month_num));
												}
												?>
											</select>
											<select name="start_year">
												<?php
												$cur_year = (int) date("Y");
												$start_year = $cur_year - 6;
												$end_year = $cur_year + 4;

												for ($opt_year = $start_year; $opt_year <= $end_year; ++$opt_year) {
														echo build_option($opt_year, $opt_year, $opt_year == $cur_year);
												}
												?>
											</select>
										</td>
									</tr>
									<tr>
										<td><label class="form-required" for="end"><?php echo $translate->_("End:"); ?></label></td>
										<td>
											<select name="end_month">
												<?php
												echo build_option("","Month",true);

												for($month_num = 1; $month_num <= 12; $month_num++) {
													echo build_option($month_num, getMonthName($month_num));
												}
												?>
											</select>
											<select name="end_year">
												<?php
												echo build_option("","Year",true);
												$cur_year = (int) date("Y");
												$start_year = $cur_year - 6;
												$end_year = $cur_year + 4;

												for ($opt_year = $start_year; $opt_year <= $end_year; ++$opt_year) {
														echo build_option($opt_year, $opt_year, false);
												}
												?>
											</select>
										</td>
									</tr>
								</tbody>
							</table>
						</form>
					</div>
					<div class="modal-footer">
						<button class="btn modal-close">Close</button>
						<button class="btn btn-primary pull-right modal-confirm">Update</button>
					</div>
				</div>
				
				<div class="clear">&nbsp;</div>
				<div id="student_run_electives"><?php echo display_student_run_electives($student_run_electives,"admin"); ?></div>
			</div>
		</div>
		
		<div class="section">
			<h3 title="Internal Awards" class="collapsable collapsed"><?php echo $translate->_("Internal Awards"); ?></h3>
			<div id="internal-awards">
				<div id="add_internal_award_link" style="float: right;">
					<a id="add_internal_award" href="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=mspr&id=<?php echo $PROXY_ID; ?>" class="btn btn-small btn-success"><i class="icon-plus-sign icon-white"></i> Add Internal Award</a>
				</div>
			
				<div class="clear">&nbsp;</div>
				
				<div id="add-internal-award-box" class="modal hide">
					<div class="modal-header">
						<h3><?php echo $translate->_("Add Internal Award"); ?></h3>
					</div>
					<div class="modal-body">
						<form method="post" name="add_internal_award_form">
							<input type="hidden" name="user_id" value="<?php echo $user->getID(); ?>" />
							<input type="hidden" name="action" value="Add" />

							<table class="mspr_form">
								<colgroup>
									<col width="25%" />
									<col width="75%" />
								</colgroup>
								<tbody>
									<tr>
										<td><label class="form-required" for="title"><?php echo $translate->_("Title:"); ?></label></td>
										<td>
											<select name="award_id">
												<?php
													$query		= "SELECT * FROM `student_awards_internal_types` where `disabled` = 0 order by `title` asc";
													$results	= $db->GetAll($query);
													if ($results) {
														foreach ($results as $result) {
															echo build_option($result['id'], clean_input($result["title"], array("notags", "specialchars")));
														}
													}
												?>
											</select>
										</td>
									</tr>
									<tr>
										<td><label class="form-required" for="year"><?php echo $translate->_("Year Awarded:"); ?></label></td>
										<td>
											<select name="year">
											<?php

											$cur_year = (int) date("Y");
											$start_year = $cur_year - 4;
											$end_year = $cur_year + 4;

											for ($opt_year = $start_year; $opt_year <= $end_year; ++$opt_year) {
													echo build_option($opt_year, $opt_year, $opt_year == $cur_year);
											}

											?>
											</select>
										</td>
									</tr>
								</tbody>
							</table>
						</form>
					</div>
					<div class="modal-footer">
						<button class="btn modal-close">Close</button>
						<button class="btn btn-primary pull-right modal-confirm">Submit</button>
					</div>
				</div>
				
				<div id="update-internal-award-box" class="modal hide">
					<div class="modal-header">
						<h3><?php echo $translate->_("Edit Internal Award"); ?></h3>
					</div>
					<div class="modal-body">
						<form method="post" name="edit_internal_award_form">
							<input type="hidden" name="user_id" value="<?php echo $user->getID(); ?>" />
							<input type="hidden" name="action" value="Edit" />

							<table class="mspr_form">
								<colgroup>
									<col width="25%" />
									<col width="75%" />
								</colgroup>
								<tbody>
									<tr>
										<td><label class="form-required" for="title"><?php echo $translate->_("Title:"); ?></label></td>
										<td>
											<select name="award_id">
												<?php
													$query		= "SELECT * FROM `student_awards_internal_types` where `disabled` = 0 order by `title` asc";
													$results	= $db->GetAll($query);
													if ($results) {
														foreach ($results as $result) {
															echo build_option($result['id'], clean_input($result["title"], array("notags", "specialchars")));
														}
													}
												?>
											</select>
										</td>
									</tr>
									<tr>
									<td>
										<label class="form-required" for="year"><?php echo $translate->_("Year Awarded:"); ?></label></td>
										<td>
											<select name="year">
												<?php
												$cur_year = (int) date("Y");
												$start_year = $cur_year - 4;
												$end_year = $cur_year + 4;

												for ($opt_year = $start_year; $opt_year <= $end_year; ++$opt_year) {
													echo build_option($opt_year, $opt_year, $opt_year == $cur_year);
												}
												?>
											</select>
										</td>
									</tr>
								</tbody>
							</table>
						</form>
					</div>
					<div class="modal-footer">
						<button class="btn modal-close">Close</button>
						<button class="btn btn-primary pull-right modal-confirm">Update</button>
					</div>
				</div>
				
				<form id="add_internal_award_form" name="add_internal_award_form" action="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=mspr&id=<?php echo $PROXY_ID; ?>" method="post" style="display:none;" >
					<input type="hidden" name="user_id" value="<?php echo $user->getID(); ?>" />
					<div class="clear">&nbsp;</div>
				</form>

				<div id="internal_awards"><?php echo display_internal_awards($internal_awards,"admin"); ?></div>
			</div>
		</div>
	</div>
	
	<h2 title="Extracted Information Section" class="collapsed"><?php echo $translate->_("Information Extracted from Other Sources"); ?></h2>
	<div id="extracted-information-section">
	
		<div class="section">
			<h3 title="Clerkship Core Rotations Completed Satisfactorily to Date Section"  class="collapsable collapsed">Clerkship Core Rotations Completed Satisfactorily to Date</h3>
			<div id="clerkship-core-rotations-completed-satisfactorily-to-date-section"><?php echo display_clerkship_core_completed($clerkship_core_completed); ?></div>
		</div>
		
		<div class="section">
			<h3 title="Clerkship Core Rotations Pending Section"  class="collapsable collapsed">Clerkship Core Rotations Pending</h3>
			<div id="clerkship-core-rotations-pending-section"><?php echo display_clerkship_core_pending($clerkship_core_pending); ?></div>
		</div>
		
		<div class="section">
			<h3 title="Clerkship Electives Completed Satisfactorily to Date Section"  class="collapsable collapsed">Clerkship Electives Completed Satisfactorily to Date</h3>
			<div id="clerkship-electives-completed-satisfactorily-to-date-section"><?php echo display_clerkship_elective_completed($clerkship_elective_completed); ?></div>
		</div>
		<div class="section">
			<h3 title="Leaves of Absence" class="collapsable collapsed">Leaves of Absence</h3>
			<div id="leaves-of-absence">
			<?php 
			echo display_mspr_details($leaves_of_absence);
			?>
			</div>
		</div>
		<div class="section">
			<h3 title="Formal Remediation Received" class="collapsable collapsed">Formal Remediation Received</h3>
			<div id="formal-remediation-received">
			<?php 
			echo display_mspr_details($formal_remediations);
			?>
			</div>
		</div>
		<div class="section">
			<h3 title="Disciplinary Actions" class="collapsable collapsed">Disciplinary Actions</h3>
			<div id="disciplinary-actions"> 
			<?php 
			echo display_mspr_details($disciplinary_actions);
			?>
			</div>
		</div>
	</div>
	
</div>

<div id="reject-submission-box" class="modal hide" style="height: 300px">
	<div class="modal-header">
		<h3><?php echo $translate->_("Reject Submission"); ?></h3>
	</div>
	<div class="modal-body">
		<div class="display-notice">
			Please confirm that you wish to <strong>reject</strong> this submission.
		</div>
		<p>
			<label for="reject-submission-details" class="form-required">Please provide an explanation for this decision:</label><br />
			<textarea id="reject-submission-details" name="reject_verify_details" style="width: 99%; height: 75px" cols="45" rows="5"></textarea>
		</p>
	</div>
	<div class="modal-footer">
		<button class="btn modal-close">Close</button>
		<button class="btn btn-primary pull-right modal-confirm" id="reject-submission-confirm">Reject</button>
	</div>
</div>

<script type="text/javascript">

	function CollapseSections(event) {
		if (event) {
			jQuery('#information-requiring-approval .collapse, #required-information-section .collapse, #extracted-information-section .collapse').collapse('show');
			jQuery('#information-requiring-approval .collapsable, #required-information-section .collapsable, #extracted-information-section .collapsable').removeClass('collapsed');
		} else {
			jQuery('#information-requiring-approval .collapse, #required-information-section .collapse, #extracted-information-section .collapse').collapse('hide');
			jQuery('#information-requiring-approval .collapsable, #required-information-section .collapsable, #extracted-information-section .collapsable').addClass('collapsed');
		}
	}

document.observe('dom:loaded', function() {
	try {
		function get_modal_options() {
			return {
				overlayOpacity:	0.75,
				closeOnClick:	'overlay',
				className:		'modal',
				fade:			true,
				fadeDuration:	0.30,
				position: 'fixed'
			};
		}

	var api_url = '<?php echo webservice_url("mspr-admin"); ?>&id=<?php echo $PROXY_ID; ?>&mspr-section=';
		
	var reject_modal = new Control.Modal('reject-submission-box', get_modal_options());

	var add_clineval_modal = new Control.Modal('add-clineval-box', get_modal_options());
	var edit_clineval_modal = new Control.Modal('update-clineval-box', get_modal_options());

	var edit_studentship_modal = new Control.Modal('update-studentship-box', get_modal_options());
	var add_studentship_modal = new Control.Modal('add-studentship-box', get_modal_options());
	
	var edit_int_act_modal = new Control.Modal('update-int-act-box', get_modal_options());
	var add_int_act_modal = new Control.Modal('add-int-act-box', get_modal_options());

	var edit_sre_modal = new Control.Modal('update-sre-box', get_modal_options());
	var add_sre_modal = new Control.Modal('add-sre-box', get_modal_options());

	var edit_internal_award_modal = new Control.Modal('update-internal-award-box', get_modal_options());
	var add_internal_award_modal = new Control.Modal('add-internal-award-box', get_modal_options());
	
	var add_critical_enquiry_modal = new Control.Modal('add-critical-enquiry-box', get_modal_options());
	var edit_critical_enquiry_modal = new Control.Modal('update-critical-enquiry-box', get_modal_options());

	var add_community_based_project_modal = new Control.Modal('add-community-based-project-box', get_modal_options());
	var edit_community_based_project_modal = new Control.Modal('update-community-based-project-box', get_modal_options());

	var add_research_modal = new Control.Modal('add-research-box', get_modal_options());
	var edit_research_modal = new Control.Modal('update-research-box',get_modal_options());

	var add_contribution_modal = new Control.Modal('add-contribution-box', get_modal_options());
	var edit_contribution_modal = new Control.Modal('update-contribution-box', get_modal_options());

	var add_external_award_modal = new Control.Modal('add-external-award-box', get_modal_options());
	var edit_external_award_modal = new Control.Modal('update-external-award-box', get_modal_options());
	
	var research_citations = new ActiveDataEntryProcessor({
		url : api_url + 'research_citations',
		data_destination: $('research_citations'),
		remove_forms_selector: '#research .entry form.remove_form',
		new_button: $('add_research_citation_link'),
		section:'research_citations',
		new_modal: add_research_modal
	});

	var research_citation_priority_list = new PriorityList({
		url : api_url + 'research_citations',
		data_destination: $('research_citations'),
		format: /research_citation_([0-9]*)$/,
		tag: "li",
		handle:'.handle',
		section:'research_citations',
		element: 'citations_list',
		params : { user_id: <?php echo $user->getID(); ?> }
	});

	var research_edit = new ActiveEditor({
		url : api_url + 'research_citations',
		data_destination: $('research_citations'),
		edit_forms_selector: '#research_citations .entry form.edit_form',
		edit_modal: edit_research_modal,
		section: 'research_citations'
	});

	var research_citations_approval = new ActiveApprovalProcessor({
		url : api_url + 'research_citations',
		data_destination: $('research_citations'),
		action_form_selector: '#research_citations .entry form.reject_form, #research_citations .entry form.approve_form, #research_citations .entry form.unapprove_form',
		section: "research_citations",
		reject_modal: reject_modal
	});
	
	
	var critical_enquiry = new ActiveDataEntryProcessor({
		url : api_url + 'critical_enquiry',
		data_destination: $('critical_enquiry'),
		remove_forms_selector: '#critical_enquiry .entry form.remove_form',
		new_button: $('add_critical_enquiry_link'),
		section:'critical_enquiry',
		new_modal: add_critical_enquiry_modal
	});

	var critical_enquiry_edit = new ActiveEditor({
		url : api_url + 'critical_enquiry',
		data_destination: $('critical_enquiry'),
		edit_forms_selector: '#critical_enquiry .entry form.edit_form',
		edit_modal: edit_critical_enquiry_modal,
		section: 'critical_enquiry'
	});

	var critical_enquiry_approval = new ActiveApprovalProcessor({
		url : api_url + 'critical_enquiry',
		data_destination: $('critical_enquiry'),
		action_form_selector: '#critical_enquiry .entry form.reject_form, #critical_enquiry .entry form.approve_form, #critical_enquiry .entry form.unapprove_form',
		section: "critical_enquiry",
		reject_modal: reject_modal
	});

	var community_based_project = new ActiveDataEntryProcessor({
		url : api_url + 'community_based_project',
		data_destination: $('community_based_project'),
		remove_forms_selector: '#community_based_project .entry form.remove_form',
		new_button: $('add_community_based_project_link'),
		section:'community_based_project',
		new_modal: add_community_based_project_modal
	});

	var community_based_project_edit = new ActiveEditor({
		url : api_url + 'community_based_project',
		data_destination: $('community_based_project'),
		edit_forms_selector: '#community_based_project .entry form.edit_form',
		edit_modal: edit_community_based_project_modal,
		section: 'community_based_project'
	});

	var community_based_project_approval = new ActiveApprovalProcessor({
		url : api_url + 'community_based_project',
		data_destination: $('community_based_project'),
		action_form_selector: '#community_based_project .entry form.reject_form, #community_based_project .entry form.approve_form, #community_based_project .entry form.unapprove_form',
		section: "community_based_project",
		reject_modal: reject_modal
	});
	

	var external_awards = new ActiveDataEntryProcessor({
		url : api_url + 'external_awards',
		data_destination: $('external_awards'),
		remove_forms_selector: '#external_awards .entry form.remove_form',
		new_button: $('add_external_award_link'),
		section:'external_awards',
		new_modal: add_external_award_modal
	});

	var external_awards_edit = new ActiveEditor({
		url : api_url + 'external_awards',
		data_destination: $('external_awards'),
		edit_forms_selector: '#external_awards .entry form.edit_form',
		edit_modal: edit_external_award_modal,
		section: 'external_awards'
	});

	var external_awards_approval = new ActiveApprovalProcessor({
		url : api_url + 'external_awards',
		data_destination: $('external_awards'),
		action_form_selector: '#external_awards .entry form.reject_form, #external_awards .entry form.approve_form, #external_awards .entry form.unapprove_form',
		section: "external_awards",
		reject_modal: reject_modal
	});

	var contributions = new ActiveDataEntryProcessor({
		url : api_url + 'contributions',
		data_destination: $('contributions'),
		remove_forms_selector: '#contributions .entry form.remove_form',
		new_button: $('add_contribution_link'),
		section:'contributions',
		new_modal: add_contribution_modal
	});

	var contributions_edit = new ActiveEditor({
		url : api_url + 'contributions',
		data_destination: $('contributions'),
		edit_forms_selector: '#contributions .entry form.edit_form',
		edit_modal: edit_contribution_modal,
		section: 'contributions'
	});
	
	var contributions_approval = new ActiveApprovalProcessor({
		url : api_url + 'contributions',
		data_destination: $('contributions'),
		action_form_selector: '#contributions .entry form.reject_form, #contributions .entry form.approve_form, #contributions .entry form.unapprove_form',
		section: "contributions",
		reject_modal: reject_modal
	});

	var clineval_comments = new ActiveDataEntryProcessor({
		url : api_url + 'clineval',
		data_destination: $('clinical_performance_eval_comments'),
		remove_forms_selector: '#clinical_performance_eval_comments .entry form.remove_form',
		new_button: $('add_clineval_link'),
		section: 'clineval',
		new_modal: add_clineval_modal
	});

	var clineval_edit = new ActiveEditor({
		url : api_url + 'clineval',
		data_destination: $('clinical_performance_eval_comments'),
		edit_forms_selector: '#clinical_performance_eval_comments .entry form.edit_form',
		edit_modal: edit_clineval_modal,
		section: 'clineval'
	});
	
	var studentships = new ActiveDataEntryProcessor({
		url : api_url + 'studentships',
		data_destination: $('studentships'),
		remove_forms_selector: '#studentships .entry form.remove_form',
		new_button: $('add_studentship_link'),
		section: 'studentships',
		new_modal: add_studentship_modal
	});

	var studentships_edit = new ActiveEditor({
		url : api_url + 'studentships',
		data_destination: $('studentships'),
		edit_forms_selector: '#studentships .entry form.edit_form',
		edit_modal: edit_studentship_modal,
		section: 'studentships'
	});
	
	$('int_act_start').observe('focus',function(e) {
		showCalendar('',this,this,null,null,0,30,1);
	}.bind($('int_act_start')));
	$('int_act_end').observe('focus',function(e) {
		showCalendar('',this,this,null,null,0,30,1);
	}.bind($('int_act_end')));

	var int_acts = new ActiveDataEntryProcessor({
		url : api_url + 'int_acts',
		data_destination: $('int_acts'),
		remove_forms_selector: '#int_acts .entry form.remove_form',
		new_button: $('add_int_act_link'),
		section: 'int_acts',
		new_modal: add_int_act_modal
	});

	$('int_act_start_edit').observe('focus',function(e) {
		showCalendar('',this,this,null,null,0,30,1);
	}.bind($('int_act_start_edit')));
	$('int_act_end_edit').observe('focus',function(e) {
		showCalendar('',this,this,null,null,0,30,1);
	}.bind($('int_act_end_edit')));

	var int_acts_edit = new ActiveEditor({
		url : api_url + 'int_acts',
		data_destination: $('int_acts'),
		edit_forms_selector: '#int_acts .entry form.edit_form',
		edit_modal: edit_int_act_modal,
		section: 'int_acts'
	});
	
	var student_run_electives = new ActiveDataEntryProcessor({
		url : api_url + 'student_run_electives',
		data_destination: $('student_run_electives'),
		remove_forms_selector: '#student_run_electives .entry form.remove_form',
		new_button: $('add_student_run_elective_link'),
		section: 'student_run_electives',
		new_modal: add_sre_modal
	});

	var student_run_electives_edit = new ActiveEditor({
		url : api_url + 'student_run_electives',
		data_destination: $('student_run_electives'),
		edit_forms_selector: '#student_run_electives .entry form.edit_form',
		edit_modal: edit_sre_modal,
		section: 'student_run_electives'
	});
	
	var internal_awards = new ActiveDataEntryProcessor({
		url : api_url + 'internal_awards',
		data_destination: $('internal_awards'),
		remove_forms_selector: '#internal_awards .entry form.remove_form',
		new_button: $('add_internal_award_link'),
		section: 'internal_awards',
		new_modal: add_internal_award_modal
	});

	var internal_awards_edit = new ActiveEditor({
		url : api_url + 'internal_awards',
		data_destination: $('internal_awards'),
		edit_forms_selector: '#internal_awards .entry form.edit_form',
		edit_modal: edit_internal_award_modal,
		section: 'internal_awards'
	});
	
	}catch(e) {alert(e);
		clog(e);
	}
});
</script>
<?php 
	}
}