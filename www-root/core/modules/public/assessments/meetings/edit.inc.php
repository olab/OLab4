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
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
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
    $HEAD[] = "<script type=\"text/javascript\">var CBME_UPLOAD_STORAGE_PATH  = '" . CBME_UPLOAD_STORAGE_PATH . "'; </script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/meetings/meeting-file-upload.js?release=" . html_encode(APPLICATION_VERSION) ."\"></script>";

    if (isset($_GET["meeting_id"]) && ($tmp_input = clean_input($_GET["meeting_id"], array("trim", "int")))) {
        $PROCESSED["meeting_id"] = $tmp_input;
    } else {
        add_error($translate->_('You must provide a meeting ID in order to see this page'));
    }

    if (isset($_GET["proxy_id"]) && ($tmp_input = clean_input($_GET["proxy_id"], array("trim", "int")))) {
        $PROCESSED["proxy_id"] = $tmp_input;
    } else {
        add_error($translate->_('You must provide a user ID in order to see this page'));
    }

    if (isset($PROCESSED["meeting_id"])) {
        $meeting_model = new Models_AcademicAdvisor_Meeting();
        $meeting = $meeting_model->fetchRowByMeetingIDCreatedBy($PROCESSED["meeting_id"], $ENTRADA_USER->getActiveId());
        if (!$meeting) {
            add_error(
                sprintf($translate->_("Your account does not have the permissions required to edit this meeting. Click <a href='%s/dashboard'>here</a> to return to your dashboard"),ENTRADA_URL)
            );
        }
    }

    if (!$ERROR) {
        Entrada_Utilities_Flashmessenger::displayMessages($MODULE);
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/assessments/meetings?proxy_id=".$PROCESSED["proxy_id"], "title" => "My Meetings");
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/assessments/meetings/add?proxy_id=".$PROCESSED["proxy_id"], "title" => "Add New Meeting");
        $user = Models_User::fetchRowByID($PROCESSED["proxy_id"]);
        switch ($STEP) {
            case 1 : ?>
                <h1>
                    <?php echo html_encode(
                        sprintf(
                            $translate->_("Edit Meeting with %s"),
                            "{$user->getFirstname()} {$user->getLastname()}"
                        )
                    ); ?>
                </h1>
                <div class="alert alert-info">
                    <?php echo html_encode($translate->_("Please note that you will be able to upload files to the meeting after you have saved it.")); ?>
                </div>
                <form class="form-horizontal" id="meetings-form" method="POST" action="<?php echo html_encode(ENTRADA_URL . "/assessments/meetings/edit?step=2&proxy_id=". $PROCESSED["proxy_id"] . "&meeting_id=" . $PROCESSED["meeting_id"]);?>">
                    <div class="control-group">
                        <div class="controls">
                            <input class="space-right" name="proxy_id" type="hidden" value="<?php echo $user->getID(); ?>">
                            <input class="space-right" name="meeting_id" type="hidden" value="<?php echo $meeting["meeting_id"]; ?>">
                        </div>
                        <div id="cbme-filters">
                            <?php if ($user) : ?>
                                <input type="hidden" value="<?php echo $user->getID(); ?>"
                                       id="selected_users_<?php echo $user->getID(); ?>"
                                       data-label="<?php echo html_encode($user->getFirstname() . " " . $user->getLastname()); ?>"
                                       class="search-target-control selected_users_search_target_control"
                                       name="selected_users[]">
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label form-required" for="meeting_date">
                            <?php echo $translate->_("Date of Meeting"); ?>
                        </label>
                        <div class="input-append space-left release-calendar">
                            <input type="text" id="meeting-date" name="meeting-date" class="input-small datepicker" value="<?php echo date("Y-m-d", $meeting["meeting_date"]); ?>"/>
                            <span class="add-on pointer"><i class="icon-calendar"></i></span>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="attending-input">
                            <?php echo $translate->_("Comments"); ?>
                        </label>
                        <div class="controls">
                            <textarea name="comments" rows="5" type="text" form="meetings-form" class="span11"><?php echo html_encode($meeting["comment"]); ?></textarea>
                        </div>
                    </div>
                </form>

                <div class="control-group">
                    <input type="submit" form="meetings-form" class="btn btn-primary pull-right" value="<?php echo $translate->_("Save Meeting"); ?>"/>
                </div>
                <?php
            break;
            case 2 :
                if (isset($_POST["proxy_id"]) && ($tmp_input = clean_input($_POST["proxy_id"], array("trim", "int")))) {
                    $PROCESSED["meeting_member_id"] = $tmp_input;
                } else {
                    $ERROR = true;
                    Entrada_Utilities_Flashmessenger::addMessage($translate->_("Please specify a learner"), "error", $MODULE);
                }

                if (isset($_POST["meeting_id"]) && ($tmp_input = clean_input($_POST["meeting_id"], array("trim", "int")))) {
                    $PROCESSED["meeting_id"] = $tmp_input;
                } else {
                    $ERROR = true;
                    Entrada_Utilities_Flashmessenger::addMessage($translate->_("Please specify a meeting"), "error", $MODULE);
                }

                if (isset($_POST["meeting-date"]) && ($tmp_input = clean_input($_POST["meeting-date"], array("trim", "striptags")))) {
                    $PROCESSED["meeting_date"] = $tmp_input;
                    $PROCESSED["meeting_date"] = strtotime($PROCESSED["meeting_date"]);
                } else {
                    $ERROR = true;
                    Entrada_Utilities_Flashmessenger::addMessage($translate->_("Please provide a meeting date"),"error", $MODULE);
                }

                if (isset($_POST["comments"]) && ($tmp_input = clean_input($_POST["comments"], array("trim", "striptags")))) {
                    $PROCESSED["comment"] = $tmp_input;
                } else {
                    $PROCESSED["comment"] = "";
                }

                if (!$ERROR) {
                    $meeting_model = Models_AcademicAdvisor_Meeting::fetchRowByID($PROCESSED["meeting_id"]);
                    $meeting_model->fromArray($PROCESSED);

                    $meeting_model->setUpdatedBy($ENTRADA_USER->getActiveID());
                    $meeting_model->setUpdatedDate(time());

                    $meeting_model->update();
                    header("Location: " . ENTRADA_URL . "/assessments/meetings?proxy_id=" . $PROCESSED["meeting_member_id"]);
                } else {
                    header("Location: " . ENTRADA_URL . "/assessments/meetings/edit?meeting_id=" . $PROCESSED["meeting_id"] . "&proxy_id=" . $PROCESSED["meeting_member_id"]);
                }
            break;
        }
    } else {
        echo display_error();
    }
}