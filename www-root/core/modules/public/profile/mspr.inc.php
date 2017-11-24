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
if (!defined("IN_PROFILE")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->isLoggedInAllowed('mspr', 'update',true) || $_SESSION["details"]["group"] != "student") {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

	add_error("Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
}  else {
	require_once(dirname(__FILE__)."/includes/functions.inc.php");

	$PROXY_ID                   = $ENTRADA_USER->getID();
	$user                       = User::fetchRowByID($PROXY_ID);
	$PAGE_META["title"]			= "MSPR";
	$PAGE_META["description"]	= "";
	$PAGE_META["keywords"]		= "";

	$BREADCRUMB[] = array("url" => ENTRADA_URL."/profile?section=mspr", "title" => "My MSPR");

	$HEAD[] = "<script language='javascript' src='".ENTRADA_URL."/javascript/ActiveDataEntryProcessor.js'></script>";
	$HEAD[] = "<script language='javascript' src='".ENTRADA_URL."/javascript/ActiveEditProcessor.js'></script>";
	$HEAD[] = "<script language='javascript' src='".ENTRADA_URL."/javascript/PriorityList.js'></script>";
	$HEAD[] = "<script language='javascript' src='".ENTRADA_URL."/javascript/ActiveEditor.js'></script>";
	$HEAD[] = "<link href=\"".ENTRADA_URL."/javascript/calendar/css/xc2_default.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
	$HEAD[] = "<script language=\"javascript\" type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/config/xc2_default.js\"></script>\n";
	$HEAD[] = "<script language=\"javascript\" type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/script/xc2_inpage.js\"></script>\n";

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

	$name = $user->getFirstname() . " " . $user->getLastname();
	$number = $user->getNumber();
	$year = $user->getGradYear();

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
		$revisions = $mspr->getMSPRRevisions();
		$closed = $mspr->isClosed();
		$generated = $mspr->isGenerated();
		$revision = $mspr->getGeneratedTimestamp();
		if (!$revision && $revisions) {
			$revision = array_shift($revisions);
		}

		$class_data = MSPRClassData::get($year);

		$mspr_close = $mspr->getClosedTimestamp();

		if (!$mspr_close) { // no custom time.. use the class default
            if (is_object($class_data)) {
                $mspr_close = $class_data->getClosedTimestamp();
            } else { // This is the default closing date if the MSPR doesn't exist for this cohort.
                $mspr_close = mktime(0, 0, 0, 11, 1, ($year - 1));
            }
		}

		if ($type = $_GET['get']) {
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
				echo $mspr->getMSPRFile($type,$revision);
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

		$faculty = ClinicalFacultyMembers::get();

		display_status_messages();
        ?>
        <script type="text/javascript">
            var submitting = false;
        </script>
        <h1><?php echo $translate->_("Medical School Performance Report"); ?></h1>
        <?php
        if ($closed) {
            ?>
            <div class="display-notice">
                <p>MSPR submission closed on <?php echo date("F j, Y \a\\t g:i a",$mspr_close); ?></p>
                <?php if ($revision) {	?>
                <p>Your MSPR is available in HTML and PDF, below:</p>
                <span class="file-block"><a href="<?php echo ENTRADA_URL; ?>/profile?section=mspr&get=html"><img src="<?php echo ENTRADA_URL; ?>/serve-icon.php?ext=html" /> HTML</a>&nbsp;&nbsp;&nbsp;<a href="<?php echo ENTRADA_URL; ?>/profile?section=mspr&get=pdf"><img src="<?php echo ENTRADA_URL; ?>/serve-icon.php?ext=pdf" /> PDF</a></span>
                <div class="clearfix">&nbsp;</div>
                <span class="last-update">Last Updated: <?php echo date("F j, Y \a\\t g:i a",$revision); ?></span>
                <?php } else { ?>
                <p>Finalized documents are not yet available.</p>
                <?php } ?>
            </div>
            <?php
        } elseif ($mspr_close) {
            ?>
            <div class="display-notice">
            The deadline for student submissions to this MSPR is <?php echo date("F j, Y \a\\t g:i a",$mspr_close); ?>. Please note that submissions may be approved, unapproved, or rejected after this date.
            </div>
            <?php
        }
        ?>

        <div class="mspr-tree">
            <a href="#" onclick="CollapseSections(true)">Expand All</a> / <a href="#" onclick="CollapseSections(false)">Collapse All</a>
            <?php
            if (!$closed) {
                ?>
                <h2 title="Required Information Section">Information Required From You</h2>
                <div id="required-information-section">
                    <div class="instructions" style="margin-left:2em;margin-top:2ex;">
                        <strong>Instructions</strong>
                        <p>The sections below require your input. The information you provide will appear on your Medical School Performance Report. All submisions are subject to dean approval.</p>
                        <ul>
                            <li>
                                Each section below provides a link to add new entires or edit in the case of single entires (Critical Enquiry, and Community Based Project).
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
                    <div class="section" >
                        <h3 title="Critical Enquiry" class="collapsable collapsed">Critical Enquiry Project</h3>
                        <div id="critical-enquiry">
                            <div id="add_critical_enquiry_link" style="float: right;">
                                <a id="add_critical_enquiry" href="<?php echo ENTRADA_URL; ?>/profile?section=mspr&id=<?php echo $PROXY_ID; ?>" class="btn btn-small btn-success"><i class="icon-plus-sign icon-white"></i> Add Critical Enquiry Project</a>
                            </div>
                            <div class="clear">&nbsp;</div>

                            <div id="add-critical-enquiry-box" class="modal hide">
                                <div class="modal-header">
                                    <h3><?php echo $translate->_("Add Critical Enquiry Project"); ?></h3>
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
                                                <td><input name="supervisor" type="text" style="width:40%;" value=""> /><span class="content-small"><strong>Example</strong>: Dr. Nick Riviera</span></td>
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
                                    <h3><?php echo $translate->_("Edit Critical Enquiry Project"); ?></h3>
                                </div>
                                <div class="modal-body">
                                    <form method="post">
                                        <table class="mspr_form">
                                            <colgroup>
                                                <col width="3%"> />
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
                                                <td><input name="location" type="text" style="width:40%;" value=""> /> <span class="content-small"><strong>Example</strong>: Kingston, Ontario</span></td>
                                            </tr>
                                            <tr>
                                                <td>&nbsp;</td>
                                                <td><label class="form-required" for="supervisor"><?php echo $translate->_("Supervisor:"); ?></label></td>
                                                <td><input name="supervisor" type="text" style="width:40%;" value=""> /> <span class="content-small"><strong>Example</strong>: Dr. Nick Riviera</span></td>
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

                            <div id="critical_enquiry"><?php echo display_supervised_project_profile($critical_enquiry); ?></div>
                        </div>
                    </div>

                    <div class="section" >
                        <h3 title="Community-Based Project" class="collapsable collapsed">Community Based Project</h3>
                        <div id="community-based-project">
                            <div id="add_community_based_project_link" style="float: right;">
                                <a id="add_community_based_project" href="<?php echo ENTRADA_URL; ?>/profile?section=mspr&show=community_based_project_form&id=<?php echo $PROXY_ID; ?>" class="btn btn-small btn-success"><i class="icon-plus-sign icon-white"></i> Add Community Based Project</a>
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

                            <div id="community_based_project"><?php echo display_supervised_project_profile($community_based_project); ?></div>
                        </div>
                    </div>

                    <div class="section" >
                        <h3 title="Research" class="collapsable collapsed">Publications</h3>
                        <div id="research">
                            <div id="add_research_citation_link" style="float: right;">
                                <a id="add_research_citation" href="<?php echo ENTRADA_URL; ?>/profile?section=mspr&id=<?php echo $PROXY_ID; ?>" class="btn btn-small btn-success"><i class="icon-plus-sign icon-white"></i> Add Publication Citation</a>
                            </div>
                            <div class="clear"></div>
                            <div class="instructions">
                                <ul>
                                    <li>Only add citations of published research in which you were a named author</li>
                                    <li>Citations below may be re-ordered. The top-six <em>approved</em> citations will appear on your MSPR.</li>
                                    <li>Research citations should be provided in a format following <a href="http://owl.english.purdue.edu/owl/resource/747/01/">MLA guidelines</a></li>
                                </ul>
                            </div>
                            <div class="clear">&nbsp;</div>

                            <div id="update-research-box" class="modal hide">
                                <div class="modal-header">
                                    <h3><?php echo $translate->_("Edit Publication Citation"); ?></h3>
                                </div>
                                <div class="modal-body">
                                    <form method="post">
                                        <table class="mspr_form">
                                            <tbody>
                                                <tr>
                                                    <td><label class="form-required" for="details"><?php echo $translate->_("Citation:"); ?></label></td>
                                                </tr>
                                                <tr>
                                                    <td><textarea name="details" style="width:100%;height:25ex;"></textarea><br /></td>
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
                                    <h3><?php echo $translate->_("Add Publication Citation"); ?></h3>
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
                                                    <td><textarea name="details" style="width:100%;height:25ex;"></textarea><br /></td>
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

                            <div id="research_citations"><?php echo display_research_citations_profile($research_citations); ?></div>
                            <div class="clear">&nbsp;</div>
                        </div>
                    </div>

                    <div class="section">

                        <h3 class="collapsable collapsed" title="External Awards Section">External Awards</h3>
                        <div id="external-awards-section">
                            <div id="add_external_award_link" style="float: right;">
                                <a id="add_external_award" href="#external-awards-section" class="btn btn-small btn-success"><i class="icon-plus-sign icon-white"></i> Add External Award</a>
                            </div>
                            <div class="clear"></div>
                            <div class="instructions">
                                <ul>
                                    <li>Only awards of academic significance will be considered.</li>
                                    <li>Award terms must be provided to be considered. Awards not accompanied by terms will be rejected.</li>
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

                            <div id="external_awards"><?php echo display_external_awards_profile($external_awards); ?></div>
                        </div>
                    </div>

                    <div class="section" >
                        <h3 title="Contributions to Medical School" class="collapsable collapsed"><?php echo $translate->_("Contributions to Medical School/Student Life") ?></h3>
                        <div id="contributions-to-medical-school">
                            <div class="instructions">
                                <ul>
                                    <li>Extra-curricular accomplishments are only approved if verified</li>
                                    <li>Examples of contributions to medical school/student life include:
                                        <ul>
                                            <li>Participation in School of Medicine student government</li>
                                            <li>Committees (such as admissions)</li>
                                            <li>Organizing extra-curricular learning activities and seminars</li>
                                        </ul>
                                    </li>
                                </ul>
                            </div>
                            <div id="add_contribution_link" style="float: right;">
                                <a id="add_contribution" href="<?php echo ENTRADA_URL; ?>/profile?section=mspr&show=contributions_form&id=<?php echo $PROXY_ID; ?>" class="btn btn-small btn-success"><i class="icon-plus-sign icon-white"></i> Add Contribution</a>
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
                                                    <td><label class="form-required" for="role">Role:</label></td>
                                                    <td><input name="role" type="text" style="width:40%;" /> <span class="content-small"><strong>Example</strong>: Interviewer</span></td>
                                                </tr>
                                                <tr>
                                                    <td><label class="form-required" for="org_event">Organization/Event:</label></td>
                                                    <td><input name="org_event" type="text" style="width:40%;" /> <span class="content-small"><strong>Example</strong>: Medical School Interview Weekend</span></td>
                                                </tr>
                                                <tr>
                                                    <td><label class="form-required" for="start">Start:</label></td>
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
                                        <input type="hidden" name="user_id" value="<?php echo $user->getID(); ?>">
                                        <input type="hidden" name="action" value="Add">
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
                            <div id="contributions"><?php echo display_contributions_profile($contributions); ?></div>
                            <div class="clear">&nbsp;</div>
                        </div>
                    </div>
                    <div class="section">
                        <h3 title="Observerships Section" class="collapsable collapsed">Observerships</h3>
                        <div id="observerships-section">
                            <div id="observerships"><?php echo display_observerships($observerships,"public"); ?></div>
                        </div>
                    </div>
                </div>
                <script type="text/javascript">
                    document.observe('dom:loaded', function() {
                    try {
                        function get_modal_options() {
                            return {
                                overlayOpacity:	0.75,
                                closeOnClick:	'overlay',
                                className:		'modal',
                                fade:			true,
                                fadeDuration:	0.30
                            };
                        }

                        var api_url = '<?php echo webservice_url("mspr-profile"); ?>&id=<?php echo $PROXY_ID; ?>&mspr-section=';

                        }catch(e) {alert(e);
                            clog(e);
                        }
                    });

                    function CollapseSections(event) {
                        if (event) {
                            jQuery('#required-information-section .collapse, #supplied-information-section .collapse').collapse('show');
                            jQuery('#required-information-section .collapsable, #supplied-information-section .collapsable').removeClass('collapsed');
                        } else {
                            jQuery('#required-information-section .collapse, #supplied-information-section .collapse').collapse('hide');
                            jQuery('#required-information-section .collapsable, #supplied-information-section .collapsable').addClass('collapsed');
                        }
                    }
                </script>
                <h2 title="Supplied Information Section" class="collapsed">Information Supplied by Staff and Faculty</h2>
                <div id="supplied-information-section">
                    <div class="instructions">
                        <p>This section consists of information entered by staff or extracted from other sources (for example, clerkship schedules).</p>
                        <p>Please periodically read over the information in the following sections to verify its accuracy. If any errors are found, please contact the undergraduate office.</p>
                    </div>

                    <div class="section">
                        <h3 title="Clerkship Core Rotations Completed Satisfactorily to Date" class="collapsable collapsed">Clerkship Core Rotations Completed Satisfactorily to Date</h3>
                        <div id="clerkship-core-rotations-completed-satisfactorily-to-date">
                            <div id="clerkships_core_completed"><?php echo display_clerkship_details($clerkship_core_completed); ?></div>
                        </div>
                    </div>

                    <div class="section">
                        <h3 title="Clerkship Core Rotations Pending" class="collapsable collapsed">Clerkship Core Rotations Pending</h3>
                        <div id="clerkship-core-rotations-pending">
                            <div id="clerkships_core_pending"><?php echo display_clerkship_details($clerkship_core_pending); ?></div>
                        </div>
                    </div>

                    <div class="section">
                        <h3 title="Clerkship Electives Completed Satisfactorily to Date" class="collapsable collapsed">Clerkship Electives Completed Satisfactorily to Date</h3>
                        <div id="clerkship-electives-completed-satisfactorily-to-date">
                            <div id="clerkships_electves_completed"><?php echo display_clerkship_details($clerkship_elective_completed); ?></div>
                        </div>
                    </div>

                    <div class="section">
                        <h3 title="Clinical Performance Evaluation Comments" class="collapsable collapsed">Clinical Performance Evaluation Comments</h3>
                        <div id="clinical-performance-evaluation-comments">
                            <div id="clinical_performance_eval_comments"><?php echo display_clineval_profile($clinical_evaluation_comments); ?></div>
                        </div>
                    </div>

                    <div class="section">
                        <h3 title="Extra Curricular Accomplishments" class="collapsable collapsed">Extra-Curricular Accomplishments</h3>
                        <div id="extra-curricular-accomplishments">
                            <div class="subsection">
                                <h4 title="International Activities">International Activities</h4>
                                <div id="international-activities"><?php echo display_international_activities_profile($international_activities); ?></div>
                            </div>
                            <div class="subsection" >
                                <h4>Student-Run Electives</h4>
                                <div id="student_run_electives"><?php echo display_student_run_electives_profile($student_run_electives); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="section">
                        <h3 title="Internal Awards" class="collapsable collapsed">Internal Awards</h3>
                        <div id="internal-awards"><?php echo display_internal_awards_profile($internal_awards); ?></div>
                    </div>

                    <div class="section">
                        <h3 title="Summer Studentships" class="collapsable collapsed">Summer Studentships</h3>
                        <div id="summer-studentships"><?php echo display_studentships_profile($studentships); ?></div>
                    </div>

                    <div class="section">
                        <h3 title="Leaves of Absence" class="collapsable collapsed">Leaves of Absence</h3>
                        <div id="leaves-of-absence">
                            <?php echo display_mspr_details($leaves_of_absence); ?>
                        </div>
                    </div>

                    <div class="section">
                        <h3 title="Formal Remediation Received" class="collapsable collapsed">Formal Remediation Received</h3>
                        <div id="formal-remediation-received">
                            <?php echo display_mspr_details($formal_remediations); ?>
                        </div>
                    </div>

                    <div class="section">
                        <h3 title="Disciplinary Actions" class="collapsable collapsed">Disciplinary Actions</h3>
                        <div id="disciplinary-actions">
                            <?php echo display_mspr_details($disciplinary_actions); ?>
                        </div>
                    </div>
                </div>

                <script type="text/javascript">
                document.observe("dom:loaded", function () {
                    //If you're wondering why we don't simply re-use the object, it's because the options are not cloned by Control.Modal and are used for other purposes as well
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

                        var api_url = '<?php echo webservice_url("mspr-profile"); ?>&id=<?php echo $PROXY_ID; ?>&mspr-section=';

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
                    }catch(e) {
                        clog(e);
                    }
                });
                </script>
                <?php
            } else {
                ?>
                <div class="section" >
                    <h3 title="Critical Enquiry" class="collapsable collapsed">Critical Enquiry Project</h3>
                    <div id="critical-enquiry">
                        <div id="critical_enquiry"><?php echo display_supervised_project_profile($critical_enquiry, true); ?></div>
                    </div>
                </div>
                <div class="section" >
                    <h3 title="Community-Based Project" class="collapsable collapsed">Community Based Project</h3>
                    <div id="community-based-project">
                        <div id="community_based_project"><?php echo display_supervised_project_profile($community_based_project, true); ?></div>
                    </div>
                </div>
                <div class="section" >
                    <h3 title="Research" class="collapsable collapsed">Publications</h3>
                    <div id="research">
                        <div id="research_citations"><?php echo display_research_citations_profile($research_citations, true); ?></div>
                    </div>
                </div>
                <div class="section">

                    <h3 class="collapsable collapsed" title="External Awards Section">External Awards</h3>
                    <div id="external-awards-section">
                        <div id="external_awards"><?php echo display_external_awards_profile($external_awards,true); ?></div>
                    </div>
                </div>
                <div class="section" >
                    <h3 title="Contributions to Medical School" class="collapsable collapsed">Contributions to Medical School</h3>
                    <div id="contributions-to-medical-school">
                        <div id="contributions"><?php echo display_contributions_profile($contributions,true); ?></div>
                    </div>
                </div>
                <div class="section">
                    <h3 title="Clerkship Core Rotations Completed Satisfactorily to Date" class="collapsable collapsed">Clerkship Core Rotations Completed Satisfactorily to Date</h3>
                    <div id="clerkship-core-rotations-completed-satisfactorily-to-date">
                        <div id="clerkships_core_completed"><?php echo display_clerkship_details($clerkship_core_completed); ?></div>
                    </div>
                </div>
                <div class="section">
                    <h3 title="Clerkship Core Rotations Pending" class="collapsable collapsed">Clerkship Core Rotations Pending</h3>
                    <div id="clerkship-core-rotations-pending">
                        <div id="clerkships_core_pending"><?php echo display_clerkship_details($clerkship_core_pending); ?></div>
                    </div>
                </div>
                <div class="section">
                    <h3 title="Clerkship Electives Completed Satisfactorily to Date" class="collapsable collapsed">Clerkship Electives Completed Satisfactorily to Date</h3>
                    <div id="clerkship-electives-completed-satisfactorily-to-date">
                        <div id="clerkships_electves_completed"><?php echo display_clerkship_details($clerkship_elective_completed); ?></div>
                    </div>
                </div>
                <div class="section">
                    <h3 title="Clinical Performance Evaluation Comments" class="collapsable collapsed">Clinical Performance Evaluation Comments</h3>
                    <div id="clinical-performance-evaluation-comments">
                        <div id="clinical_performance_eval_comments"><?php echo display_clineval_profile($clinical_evaluation_comments); ?></div>
                    </div>
                </div>

                <div class="section">
                    <h3 title="Extra Curricular Accomplishments" class="collapsable collapsed">Extra-Curricular Accomplishments</h3>
                    <div id="extra-curricular-accomplishments">
                        <div class="subsection">
                            <h4 title="International Activities">International Activities</h4>
                            <div id="international-activities"><?php echo display_international_activities_profile($international_activities); ?></div>
                        </div>
                        <div class="subsection" >
                            <h4>Learning Activities - Observerships</h4>
                            <div id="observerships"><?php echo display_observerships_profile($observerships); ?></div>
                        </div>
                        <div class="subsection" >
                            <h4>Student-Run Electives</h4>
                            <div id="student_run_electives"><?php echo display_student_run_electives_profile($student_run_electives); ?></div>
                        </div>
                    </div>
                </div>
                <div class="section">
                    <h3 title="Internal Awards" class="collapsable collapsed">Internal Awards</h3>
                    <div id="internal-awards"><?php echo display_internal_awards_profile($internal_awards); ?></div>
                </div>
                <div class="section">
                    <h3 title="Summer Studentships" class="collapsable collapsed">Summer Studentships</h3>
                    <div id="summer-studentships"><?php echo display_studentships_profile($studentships); ?></div>
                </div>
                <div class="section">
                    <h3 title="Leaves of Absence" class="collapsable collapsed">Leaves of Absence</h3>
                    <div id="leaves-of-absence"><?php echo display_mspr_details($leaves_of_absence); ?></div>
                </div>
                <div class="section">
                    <h3 title="Formal Remediation Received" class="collapsable collapsed">Formal Remediation Received</h3>
                    <div id="formal-remediation-received"><?php echo display_mspr_details($formal_remediations); ?></div>
                </div>
                <div class="section">
                    <h3 title="Disciplinary Actions" class="collapsable collapsed">Disciplinary Actions</h3>
                    <div id="disciplinary-actions"><?php echo display_mspr_details($disciplinary_actions); ?></div>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
	}
}
