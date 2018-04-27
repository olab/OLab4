<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Used to list all available polls within this page of a community.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Andrew Dos-Santos <andrew.dos-santos@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_EXAMS"))) {
    exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}
$HEAD[] = "<script type=\"text/javascript\">var org_id = '".$ENTRADA_USER->getActiveOrganisation()."';</script>";
$HEAD[] = "<script type=\"text/javascript\">var SITE_URL = '".ENTRADA_URL."';</script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/ckeditor/ckeditor.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/elementresizer.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/wizard.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<link href=\"".ENTRADA_URL."/css/wizard.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
$HEAD[] = "<link href=\"".ENTRADA_RELATIVE."/javascript/calendar/css/xc2_default.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/livepipe/livepipe.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/livepipe/window.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/calendar/config/xc2_default.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/calendar/script/xc2_inpage.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.timepicker.js\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/wizard.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/picklist.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/exams/exam-posts-admin.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<link href=\"".ENTRADA_URL."/css/wizard.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
$HEAD[] = "<link href=\"".ENTRADA_URL."/css/exams/attach.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";
?>
<iframe id="upload-frame" name="upload-frame" onload="frameLoad()" style="display: none;"></iframe>
<a id="false-link" href="#placeholder"></a>
<div id="placeholder" style="display: none"></div>
<div id="module-header">
</div>
<script type="text/javascript">
	var ajax_url = '';
	var modalDialog;
	document.observe('dom:loaded', function() {
		modalDialog = new Control.Modal($('false-link'), {
			position:		'center',
			overlayOpacity:	0.75,
			closeOnClick:	'overlay',
			className:		'modal',
			fade:			true,
			fadeDuration:	0.30,
			beforeOpen: function(request) {
				eval($('scripts-on-open').innerHTML);
			},
			afterClose: function() {
				if (uploaded == true) {
                    location.reload();
				}
			}
		});
	});
    
    var browser_key_prototype = '<div class="row browser-key-container"><div class="span1"><button type="button" class="btn btn-link delete-browser-key"><i class="icon-minus-sign"></i></button></div><div class="span8"><label class="control-label">SEB Browser Key</label><br /><input type="text" id="browser-key" name="browser_key[]" placeholder="Please enter the SEB Browser Key" /></div><div class="span2 offset1"><label class="control-label">Version</label><br /><input type="text" id="version" name="version[]" maxlength="8" placeholder="SEB Version" /></div></div>';
    jQuery('body').on('click', '#add-more-keys', function(event) {
        event.preventDefault();
        event.stopPropagation();

        jQuery(this).prev('#browser-key-collection-container').append(browser_key_prototype);
    });
    

	function openDialog (url) {
		if (url) {
			ajax_url = url;
			new Ajax.Request(ajax_url, {
				method: 'get',
				onComplete: function(transport) {
					modalDialog.container.update(transport.responseText);
					modalDialog.open();
				}
			});
		} else {
			$('scripts-on-open').update();
			modalDialog.open();
		}
	}
    
    
</script>
<?php
if ($COMMUNITY_ADMIN) {
		?>
		<div style="float: right; margin-bottom: 5px">
			<ul class="page-action">
				<li><a href="<?php echo ENTRADA_URL; ?>/admin/exams?section=add" class="btn btn-success"><i class="icon-plus-sign icon-white"></i> Create New Exam</a></li>
				<li><a href="#distributions-modal" data-toggle="modal" class="btn btn-success"><i class="icon-plus-sign icon-white"></i> Attach Existing Exam</a></li>
			</ul>
		</div>
		<div class="clear"></div>
    <?php
}
?>
<div style="padding-top: 10px; clear: both">
    <div class="section-holder">
		<?php
		/**
		 * Get all attached exams
		 */
		$attached_exams = Models_Exam_Post::fetchAllByCommunityID($PAGE_ID);
		echo "<table class=\"tableList\" cellspacing=\"0\" summary=\"List of Attached Exams\">\n";
		echo "<colgroup>\n";
		echo "	<col class=\"modified\" style=\"width: 50px\"  />\n";
		echo "	<col class=\"file-category\" />\n";
		echo "	<col class=\"title\" />\n";
		echo "	<col class=\"date\" />\n";
		echo "	<col class=\"date\" />\n";
		echo "	<col class=\"accesses\" />\n";
		echo "</colgroup>\n";
		echo "<thead>\n";
		echo "	<tr>\n";
		echo "		<td class=\"modified\">&nbsp;</td>\n";
		echo "		<td class=\"file-category sortedASC\"><div class=\"noLink\">Mandatory</div></td>\n";
		echo "		<td class=\"title\">Exam Title</td>\n";
		echo "		<td class=\"date-small\">Accessible Start</td>\n";
		echo "		<td class=\"date-small\">Accessible Finish</td>\n";
		echo "		<td class=\"accesses\">Done</td>\n";
		echo "	</tr>\n";
		echo "</thead>\n";
		echo "<tfoot>\n";
		echo "	<tr>\n";
		echo "		<td>&nbsp;</td>\n";
		echo "		<td colspan=\"5\" style=\"padding-top: 10px\">\n";
		echo "			".(($results) ? "<input type=\"button\" class=\"btn btn-danger\" value=\"Detach Selected\" onclick=\"confirmExamDelete()\" />" : "&nbsp;");
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "</tfoot>\n";
		echo "<tbody>\n";
		if ($attached_exams) {
			foreach ($attached_exams as $attached_exam) {
				echo "<tr>\n";
				echo "<td class=\"modified\" style=\"width: 50px; white-space: nowrap\">\n";
				echo "<input type=\"checkbox\" name=\"delete[]\" value=\"" . $attached_exam->getID() . "\" style=\"vertical-align: middle\" />\n";
				if ($completed_attempts > 0) {
					echo "<a href=\"".ENTRADA_URL."/admin/exam?section=results&amp;id=" . $result["post_id"] . "\">";
					echo "<img src=\"".ENTRADA_URL."/images/view-stats.gif\" width=\"16\" height=\"16\" alt=\"View results of ".html_encode($attached_exam->getTitle())."\" title=\"View results of ".html_encode($attached_exam->getTitle())."\" style=\"vertical-align: middle\" border=\"0\" /></a>\n";
				} else {
					echo "<img src=\"".ENTRADA_URL."/images/view-stats-disabled.gif\" width=\"16\" height=\"16\" alt=\"No completed quizzes at this time.\" title=\"No completed quizzes at this time.\" style=\"vertical-align: middle\" border=\"0\" />\n";
				}
				echo "</td>\n";
				echo "<td class=\"file-category\">";
				echo ($attached_exam->getMandatory() ? "Yes" : "No");
				echo "</td>\n";
				echo "<td class=\"title\" style=\"white-space: normal; overflow: visible\">\n";
				echo "<a href=\"#distributions-modal\" data-toggle=\"modal\" title=\"Click to edit ".html_encode($attached_exam->getTitle())."\">";
				echo "<strong>" . html_encode($attached_exam->getTitle()) . "</strong>";
				echo ($result["secure"] == 1) ? " <i class=\"icon-lock\"></i>" : "";
				echo "</a>\n";
				echo "</td>\n";
				echo "<td class=\"date-small\"><span class=\"content-date\">".(((int) $attached_exam->getStartDate()) ? date(DEFAULT_DATETIME_FORMAT, $attached_exam->getStartDate()) : "No Restrictions")."</span></td>\n";
				echo "<td class=\"date-small\"><span class=\"content-date\">".(((int) $attached_exam->getEndDate()) ? date(DEFAULT_DATETIME_FORMAT, $attached_exam->getEndDate()) : "No Restrictions")."</span></td>\n";
				echo "<td class=\"accesses\" style=\"text-align: center\">" . $completed_attempts . "</td>\n";
				echo "</tr>\n";
			}
		} else {
			echo "<tr>\n";
			echo "	<td colspan=\"6\">\n";
			echo "		<div class=\"display-generic\" style=\"white-space: normal\">\n";
			echo "			There have been no exams attached to this event. To <strong>create a new exam</strong> click the <a href=\"".ENTRADA_URL."/admin/exams\" style=\"font-weight: bold\">Manage Exams</a> tab, and then to attach the exam to this event click the <strong>Attach Exam</strong> button below.\n";
			echo "		</div>\n";
			echo "	</td>\n";
			echo "</tr>\n";
		}
		echo "</tbody>\n";
		echo "</table>\n";
		?>
		<div id="distributions-modal" class="modal hide fade">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times</button>
				<h3><?php echo $translate->_("Attach an Exam"); ?></h3>
			</div>
			<div class="modal-body">
				<div class="distribution-wizard-step-container">
					<ul class="distribution-wizard-steps">
						<li id="wizard-nav-item-1" class="active wizard-nav-item" data-step="1"><a href="#"><?php echo $translate->_("<span>1</span> Exam"); ?></a></li>
						<li id="wizard-nav-item-2" class="wizard-nav-item" data-step="2"><a href="#"><?php echo $translate->_("<span>2</span> Settings"); ?></a></li>
						<li id="wizard-nav-item-3" class="wizard-nav-item" data-step="3"><a href="#"><?php echo $translate->_("<span>3</span> Review"); ?></a></li>
						<!--                                            <li id="wizard-nav-item-4" class="wizard-nav-item" data-step="4"><a href="#">--><?php //echo $translate->_("<span>4</span> Results"); ?><!--</a></li>-->
					</ul>
				</div>
				<div id="msgs"></div>
				<div id="distribution-loading" class="hide">
					<img src="<?php echo ENTRADA_URL ."/images/loading.gif" ?>" />
					<p id="distribution-loading-msg"></p>
				</div>
				<form id="distribution-wizard-form" class="form-horizontal">
					<input id="wizard-step-input" type="hidden" name="wizard_step" value="1" />
					<input id="wizard-previous-step-input" type="hidden" name="previous_wizard_step" value="0" />
					<div id="wizard-step-1" class="wizard-step">
						<div class="distribution-instruction">
							<!--<h2><?php echo $translate->_("Choose a form for this distribution"); ?></h2>-->
							<!--<p><?php echo $translate->_("Click on the button below to browse and attach one of your forms."); ?></p>-->
						</div>
						<div class="control-group">
							<label for="choose-exam-btn" class="control-label form-required"><?php echo $translate->_("Select Exam"); ?></label>
							<div class="controls entrada-search-widget">
								<button id="choose-exam-btn" class="btn btn-search-filter" type="button"><?php echo $translate->_("Browse Exams"); ?> <i class="icon-chevron-down btn-icon pull-right"></i></button>
							</div>
						</div>
						<div class="control-group">
							<label for="exam-title" class="control-label form-required"><?php echo $translate->_("Exam Title"); ?></label>
							<div class="controls">
								<input id="exam-title" type="text" name="exam_title" />
							</div>
						</div>
						<div class="control-group">
							<label for="exam-description" class="control-label"><?php echo $translate->_("Exam Description"); ?></label>
							<div class="controls">
								<textarea id="exam-description" name="exam_description"></textarea>
							</div>
						</div>
						<div class="control-group">
							<label for="mandatory" class="control-label"><?php echo $translate->_("Required"); ?></label>
							<div class="controls">
								<label class="checkbox" for="required">
									<input id="mandatory" type="checkbox" name="mandatory" value="1" />
									<?php echo $translate->_("Require this exam to be completed by all audience members"); ?>
								</label>
							</div>
						</div>

						<input type="hidden" name="target_type" value="community" />
						<input type="hidden" name="target_id" value="<?php echo $PAGE_ID; ?>" />

						<div id="distribution-rotation-schedule-options" class="hide distribution-options">
							<div class="control-group">
								<label for="choose-rs-course-btn" class="control-label form-required"><?php echo $translate->_("Select a Course"); ?></label>
								<div class="controls">
									<button id="choose-rs-course-btn" class="btn btn-search-filter"><?php echo $translate->_("Browse Courses"); ?> <i class="icon-chevron-down btn-icon pull-right"></i></button>
								</div>
							</div>
							<div class="control-group hide" id="rs-rotation-schedule-options">
								<label for="rs-choose-rotation-btn" class="control-label form-required"><?php echo $translate->_("Rotation Schedule"); ?></label>
								<div class="controls">
									<button id="rs-choose-rotation-btn" class="btn"><?php echo $translate->_("Browse Rotation Schedules"); ?> <i class="icon-chevron-down btn-icon pull-right"></i></button>
								</div>
							</div>
						</div>
						<div id="distribution-rotation-delivery-options" class="hide">
							<div class="control-group">
								<label for="" class="control-label form-required">Delivery Period</label>
								<div class="controls">
									<label class="radio">
										<input type="radio" name="schedule_delivery_type" value="repeat" class="schedule_delivery_type" data-timeline-options="repeat" autocomplete="off" /> <?php echo $translate->_("Deliver repeatedly"); ?>
									</label>
									<label class="radio">
										<input type="radio" name="schedule_delivery_type" value="block" class="schedule_delivery_type block" data-timeline-options="once-per" autocomplete="off" /> <?php echo $translate->_("Deliver once per block"); ?>
									</label>
									<label class="radio">
										<input type="radio" name="schedule_delivery_type" value="rotation" class="schedule_delivery_type rotation" data-timeline-options="once-per" autocomplete="off" /> <?php echo $translate->_("Deliver once per rotation"); ?>
									</label>
								</div>
							</div>
							<div id="rotation-schedule-delivery-offset" class="hide">
								<label for="rotation-schedule_delivery_type[1]" class="control-label form-nrequired">Delivery Timeline Options</label>
								<div class="controls">
									<div id="timeline-option-repeat" class="hide">
										<label for="frequency"><?php echo $translate->_("Deliver every "); ?></label>
										<input type="text" style="width: 30px" name="frequency" id="frequency" />
										<label for="frequency"><?php echo $translate->_(" days during the Rotation."); ?></label>
									</div>
									<div id="timeline-option-once-per">

										<?php echo $translate->_("Deliver "); ?>
										<input type="text" style="width: 30px" name="period_offset_days" id="period_offset_days" />
										<?php echo $translate->_(" days "); ?>
										<select name="delivery_period" style="width: 130px">
											<option value="after-start"><?php echo $translate->_("after the start"); ?></option>
											<option value="before-middle"><?php echo $translate->_("before the middle"); ?></option>
											<option value="after-middle"><?php echo $translate->_("after the middle"); ?></option>
											<option value="before-end"><?php echo $translate->_("before the end"); ?></option>
											<option value="after-end"><?php echo $translate->_("after the end"); ?></option>
										</select>
										<span class="once-per-rotation hide"><?php echo $translate->_(" of the Rotation."); ?></span>
										<span class="once-per-block hide"><?php echo $translate->_(" of each Block."); ?></span>

									</div>
								</div>
							</div>

						</div>
					</div>
					<div id="wizard-step-2" class="hide wizard-step">
						<div class="distribution-instruction">
							<!--<h2><?php echo $translate->_("Choose Assessors"); ?></h2>-->
							<!--<p><?php echo sprintf($translate->_("Please select an option to indicate if the assessor for this distribution are %s users, or users external to %s."), APPLICATION_NAME, APPLICATION_NAME); ?></p>-->
						</div>
						<div id="distribution-specific-date-options" class="distribution-options">
							<div class="control-group">
								<label for="exam_start_date" class="control-label form-required"><?php echo $translate->_("Start Date"); ?></label>
								<div class="controls">
									<div class="input-append space-right">
										<input id="exam_start_date" type="text" class="input-small datepicker" value="<?php echo date("Y-m-d", strtotime("today")) ?>" name="exam_start_date" />
										<span class="add-on pointer">
											<i class="icon-calendar"></i>
										</span>
									</div>
									<div class="input-append">
										<input id="exam_start_time" type="text" class="input-mini timepicker" value="00:00" name="exam_start_time" />
										<span class="add-on pointer">
											<i class="icon-time"></i>
										</span>
									</div>
								</div>
							</div>
							<div class="control-group">
								<label for="exam_end_date" class="control-label form-required"><?php echo $translate->_("End Date"); ?></label>
								<div class="controls">
									<div class="input-append space-right">
										<input id="exam_end_date" type="text" class="input-small datepicker" value="<?php echo date("Y-m-d", strtotime("today + 1 week")) ?>" name="exam_end_date" />
										<span class="add-on pointer">
											<i class="icon-calendar"></i>
										</span>
									</div>
									<div class="input-append">
										<input id="exam_end_time" type="text" class="input-mini timepicker" value="23:59" name="exam_end_time" />
										<span class="add-on pointer">
											<i class="icon-time"></i>
										</span>
									</div>
								</div>
							</div>
						</div>
						<div class="control-group">
							<label for="required" class="control-label">Minimum number of attempts required</label>
							<div class="controls">
								<label class="control-label form-required" for="min_attempts">
									<input id="min_attempts" type="text" name="min_attempts" value="1" />
									<?php echo $translate->_("Minimum attempts required"); ?>
								</label>
							</div>
						</div>
						<div class="control-group">
							<label for="required" class="control-label">Number of attempts allowed</label>
							<div class="controls">
								<label class="control-label form-required" for="max_attempts">
									<input id="max_attempts" type="text" name="max_attempts" value="1" />
									<?php echo $translate->_("Enter \"0\" for unlimited attempts"); ?>
								</label>
							</div>
						</div>
						<div class="control-group">
							<label for="required" class="control-label form-required">Add to Gradebook</label>
							<div class="controls">
								<label class="checkbox" for="add_to_gradebook">
									<input id="add_to_gradebook" type="checkbox" name="add_to_gradebook" value="1" />
									<?php echo $translate->_("Create a Gradebook assessment for this exam"); ?>
								</label>
							</div>
						</div>
					</div>
					<div id="wizard-step-3" class="wizard-step hide">
						<div class="distribution-instruction">
							<!--<h2><?php echo $translate->_("Choose Targets"); ?></h2>-->
							<!--<p><?php echo $translate->_("Browse and select the targets for this distribution using the button below. Possible target choices include <strong>Cohort</strong>, <strong>Course Audience</strong>, <strong>Organisations</strong> or <strong>Individuals</strong>"); ?></p>-->
						</div>
						<h4>Please review your Exam Post details below</h4>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<div class="row-fluid">
					<button id="distribution-close-btn" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Close"); ?></button>
					<button id="distribution-previous-step"class="btn btn-default hide"><?php echo $translate->_("Previous Step"); ?></button>
					<button id="distribution-next-step" class="btn btn-primary"><?php echo $translate->_("Next Step"); ?></button>
				</div>
			</div>
		</div>
    </div>
</div>
<script>
	jQuery(function($) {
		$("#choose-exam-btn").advancedSearch({
			api_url: "<?php echo ENTRADA_URL . "/admin/events?section=api-exams" ; ?>",
			resource_url: ENTRADA_URL,
			filters: {
				exam: {
					label: "<?php echo $translate->_("Exam"); ?>",
					data_source: "get-user-exams",
					mode: "radio",
					selector_control_name: "exam"
				}
			},
			control_class: "exam-selector",
			no_results_text: "<?php echo $translate->_("No Exams found matching the search criteria"); ?>",
			parent_form: $("#distribution-wizard-form"),
			width: 300,
			modal: false
		});
	});
</script>
