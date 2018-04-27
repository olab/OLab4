<?php
if (!defined("IN_MEETINGS_LOG")) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("academicadvisor", "read", false) && !$ENTRADA_ACL->amIAllowed("competencycommittee", "read", false)) {
    $ONLOAD[] = "setTimeout('window.location=\\'" . ENTRADA_URL . "/" . $MODULE . "\\'', 15000)";

    add_error($translate->_("Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:" . html_encode($AGENT_CONTACTS["administrator"]["email"]) . "\">" . html_encode($AGENT_CONTACTS["administrator"]["name"]) . "</a> for assistance."));

    echo display_error();

    application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] do not have access to this module [" . $MODULE . "]");
} else {
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/courses/curriculum-tags.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/cbme/meetings.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/meetings/meeting-file-upload.js?release=" . html_encode(APPLICATION_VERSION) ."\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/cbme/learner-picker.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";

    $JAVASCRIPT_TRANSLATIONS[] = "var meetings_index_localization = {};";
    $JAVASCRIPT_TRANSLATIONS[] = "meetings_index_localization.delete_message = '" . html_encode($translate->_("Are you sure you want to delete %s ?")) . "';";
    $JAVASCRIPT_TRANSLATIONS[] = "meetings_index_localization.delete_meeting_message = '" . html_encode($translate->_("Are you sure you want to delete the meeting on %s ?")) . "';";
    $JAVASCRIPT_TRANSLATIONS[] = "meetings_index_localization.default_error = '" . html_encode($translate->_("Unknown server error")) . "';";
    Entrada_Utilities::addJavascriptTranslation("No Learners Found", "no_learners_found", "cbme_translations");
    Entrada_Utilities::addJavascriptTranslation("Learners", "filter_component_label", "cbme_translations");
    Entrada_Utilities::addJavascriptTranslation("Curriculum Period", "curriculum_period_filter_label", "cbme_translations");


    if (isset($_GET["proxy_id"]) && ($tmp_input = clean_input($_GET["proxy_id"], array("trim", "int")))) {
        $PROCESSED["proxy_id"] = $tmp_input;
    } else {
        add_error($translate->_('You must provide a user ID in order to see this page'));
    }

    if ($PROCESSED["proxy_id"]) {
        $assessment_user = new Entrada_Utilities_AssessmentUser();
        $admin = $ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true);
        $learners = $assessment_user->getMyLearners($ENTRADA_USER->getActiveId(), $ENTRADA_USER->getActiveOrganisation(), $admin, null);
        $valid_learner = false;
        if ($learners) {
            foreach ($learners as $learner) {
                if ($learner["proxy_id"] == $PROCESSED["proxy_id"]) {
                    $valid_learner = true;
                }
            }
        }
        if (!$valid_learner) {
            add_error(
                    sprintf($translate->_("Your account does not have the permissions required to view this learners meetings. Click <a href='%s/dashboard'>here</a> to return to your dashboard"),ENTRADA_URL)
            );
        }
    }

    if (!$ERROR) {
        $BREADCRUMB[]	= array("url" => ENTRADA_URL."/assessments/meetings?proxy_id=" . $PROCESSED["proxy_id"], "title" => $translate->_("My Meetings"));
        $meetings = array();
        $meeting_model = new Models_AcademicAdvisor_Meeting();

        $meetings = $meeting_model->fetchAllByMemberID($PROCESSED["proxy_id"]);
        $user = Models_User::fetchRowByID($PROCESSED["proxy_id"]);

        $learner_array = array(
            "proxy_id" => $user->getID(),
            "number" => $user->getNumber(),
            "firstname" => $user->getFirstname(),
            "lastname" => $user->getLastname(),
            "email" => $user->getEmail()
        );
        ?>
        <h1><?php echo $translate->_("Advisor Meeting Log"); ?></h1>
        <?php
         Entrada_Utilities_Flashmessenger::displayMessages($MODULE);

        $learner_card = new Views_User_Card();
        $learner_card->render($learner_array);

        $learner_picker = new Views_CBME_LearnerPicker();
        $learner_picker->render(array("learner_preference" => null, "proxy_id" => $PROCESSED["proxy_id"], "learner_name" => $user->getFirstname() . " " . $user->getLastname()));
        ?>
        <a id="add-meeting"
           href="<?php echo ENTRADA_URL . "/assessments/meetings/add?proxy_id=" . html_encode($PROCESSED["proxy_id"]); ?>"
           class="btn btn-success space-right pull-right">
            <i class="icon-plus-sign icon-white space-right"></i>
            <span><?php echo $translate->_("Log New Meeting"); ?></span>
        </a>

        <table class="table table-bordered space-above">
            <thead>
            <tr>
                <th><?php echo $translate->_("Date"); ?></th>
                <th><?php echo $translate->_("Comments"); ?></th>
                <th><?php echo $translate->_("Files"); ?></th>
                <th><?php echo $translate->_("Logged By"); ?></th>
                <th><?php echo $translate->_("Actions"); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if ($meetings) : ?>
                <?php foreach ($meetings as $meeting) : ?>
                    <tr>
                        <td><?php echo date("Y-m-d", $meeting["meeting_date"]); ?></td>
                        <td class="meeting-comment"><?php echo html_encode($meeting["comment"]); ?></td>
                        <td class="files-column">
                            <?php
                            $is_author = false;
                            if ($meeting["created_by"] === $ENTRADA_USER->getActiveID()) {
                                $is_author = true;
                            }
                            $files = Models_AcademicAdvisor_File::fetchAllByMeetingID($meeting["meeting_id"]);
                            $advisor = Models_User::fetchRowByID($meeting["created_by"]);
                            foreach ($files as $file) : ?>
                                <div>
                                    <form class="space-below meeting-file-form" method="POST" action="<?php echo html_encode(ENTRADA_URL . "/api/api-meetings.inc.php"); ?>">
                                        <span></span>
                                        <input type="hidden" name="method" value="serve-meeting-files" />
                                        <input type="hidden" name="meeting_id" value="<?php echo $meeting["meeting_id"]; ?>" />
                                        <input type="hidden" name="meeting_file_id" value="<?php echo $file["meeting_file_id"]; ?>" />
                                        <input type="hidden" name="file_name" value="<?php echo html_encode($file["name"]); ?>" />
                                        <input type="hidden" name="file_type" value="<?php echo html_encode($file["type"]); ?>" />
                                        <input type="hidden" name="file_size" value="<?php echo $file["size"]; ?>" />
                                        <?php if ($is_author) : ?>
                                        <span id="meeting-delete-file-icon" data-id="<?php echo $file["meeting_file_id"]; ?>" data-html="<?php echo html_encode($file["title"]); ?>" class="btn btn-default" data-toggle="modal" data-target="#delete-file-modal">
                                            <i class="fa fa-trash meeting-action-icon" data-toggle="tooltip" data-placement="bottom" title="<?php echo $translate->_("Delete File"); ?>"></i>
                                        </span>
                                        <?php else : ?>
                                        <span class="btn btn-default" disabled>
                                            <i class="fa fa-trash meeting-delete-file-icon-disabled display-block"></i>
                                        </span>
                                        <?php endif; ?>
                                        <label title="" class="btn btn-default" for="upload-label-<?php echo $file["meeting_file_id"]; ?>" >
                                            <i class="fa fa-download meeting-download-icon" data-toggle="tooltip" data-placement="bottom" title="<?php echo $translate->_($file["name"]); ?>"></i>
                                        </label>
                                        <input id="upload-label-<?php echo $file["meeting_file_id"]; ?>" name="<?php echo $file["meeting_file_id"]; ?>" type="submit" value="" class="hide" />
                                        <div class="file-label"><?php echo html_encode($file["title"]); ?></div>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </td>
                        <td>
                            <?php echo html_encode($advisor->getFirstname() . " " . $advisor->getLastname()); ?>
                        </td>
                        <td class="text-center actions-column">
                            <a data-toggle="modal" data-id="<?php echo html_encode($meeting["meeting_id"]); ?>" class="btn btn-default upload-file-btn" <?php echo $is_author ? "href=\"#upload-file-modal\"" : "disabled"; ?>>
                                <i class="fa fa-upload meeting-action-icon" data-toggle="tooltip" data-placement="bottom" title="<?php echo $translate->_("Upload a new file"); ?>"></i></a>
                            <a class="btn btn-default upload-file-btn" <?php echo $is_author ? "href=" . ENTRADA_URL . "/assessments/meetings/edit?meeting_id=" . $meeting['meeting_id'] . "&proxy_id=" . $PROCESSED["proxy_id"] : "disabled"; ?>>
                                <i class="fa fa-pencil meeting-action-icon" data-toggle="tooltip" data-placement="bottom" title="<?php echo $translate->_("Edit this meeting"); ?>"></i></a>
                            <a data-toggle="modal" id="meeting-delete-icon" data-html="<?php echo html_encode(date("Y-m-d", $meeting["meeting_date"])); ?>" data-id="<?php echo html_encode($meeting["meeting_id"]); ?>" class="btn btn-default upload-file-btn" <?php echo $is_author ? "href=\"#delete-meeting-modal\"" : "disabled"; ?>>
                                <i class="fa fa-trash meeting-action-icon" data-toggle="tooltip" data-placement="bottom" title="<?php echo $translate->_("Delete this meeting"); ?>"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="5"><?php echo $translate->_("Click the Log New Meeting button to log a meeting"); ?></td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

        <!-- Uploading File Modal -->
        <div class="modal hide fade" id="upload-file-modal">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h3><?php echo $translate->_("Upload a new file"); ?></h3>
            </div>
            <div class="modal-body">
                <?php
                $uploader = new Views_Course_Cbme_ImportData_Uploader();
                $uploader->render(array(
                    "course_id" => 0,
                    "data_method" => "upload-meeting-files",
                    "type" => "File(s)",
                    "type_plural" => $translate->_("Files"),
                    "form_id" => "meeting-file-upload",
                    "allow_multiple" => false,
                    "hide_submit_button" => true,
                    "include_file_title" => true
                ));
                ?>
            </div>
        </div>

        <!-- Deleting File Modal -->
        <div class="modal hide fade" id="delete-file-modal">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><?php echo $translate->_("&times;"); ?></button>
                <h3><?php echo $translate->_("Delete a file"); ?></h3>
            </div>
            <div class="modal-body">
                <form method="POST" id="delete-file-form" action="<?php echo html_encode(ENTRADA_URL . "/api/api-meetings.inc.php"); ?>">
                    <div>
                        <div class="file-text space-below"></div>
                        <input type="hidden" name="method" value="delete-meeting-file" />
                        <input type="hidden" name="file_id" value="" id="hidden-file-id">
                        <input type="hidden" name="proxy_id" value="<?php echo html_encode($PROCESSED["proxy_id"]); ?>">
                    </div>
                    <div>
                        <button type="button" class="btn btn-default pull-left" data-dismiss="modal" aria-hidden="true"><?php echo $translate->_("Cancel"); ?></button>
                        <input type="submit" class="btn btn-danger pull-right" value="<?php echo $translate->_("Delete"); ?>"/>
                    </div>
                </form>
            </div>
        </div>

        <!-- Deleting Meeting Modal -->
        <div class="modal hide fade" id="delete-meeting-modal">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><?php echo $translate->_("&times;"); ?></button>
                <h3><?php echo $translate->_("Delete a meeting"); ?></h3>
            </div>
            <div class="modal-body">
                <form method="POST" id="delete-file-form" action="<?php echo html_encode(ENTRADA_URL . "/api/api-meetings.inc.php"); ?>">
                    <div>
                        <div class="file-text space-below"></div>
                        <input type="hidden" name="method" value="delete-meeting" />
                        <input type="hidden" name="meeting_id" value="" id="hidden-meeting-id">
                        <input type="hidden" name="proxy_id" value="<?php echo html_encode($PROCESSED["proxy_id"]); ?>">
                    </div>
                    <div>
                        <button type="button" class="btn btn-default pull-left" data-dismiss="modal" aria-hidden="true"><?php echo $translate->_("Cancel"); ?></button>
                        <input type="submit" class="btn btn-danger pull-right" value="<?php echo $translate->_("Delete"); ?>"/>
                    </div>
                </form>
            </div>
        </div>
        <?php
    } else {
        echo display_error();
    }
}