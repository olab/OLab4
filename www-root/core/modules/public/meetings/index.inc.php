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
 * This is a read-only view of the Academic Advisor meetings.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
if((!defined("PARENT_INCLUDED")) || (!defined("IN_MEETINGS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed('cbmemeeting', 'read')) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/cbme/meetings.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
    if ($ENTRADA_USER->getActiveGroup() !== "student") {
        add_error(sprintf($translate->_("Your account does not have the permissions required to view this module. Click <a href='%s/dashboard'>here</a> to return to the dashboard"),ENTRADA_URL));
    }

    if (!$ERROR) {
        $BREADCRUMB[] = array("url" => ENTRADA_URL . "/dashboard", "title" => $translate->_("Student Dashboard"));
        $BREADCRUMB[] = array("url" => ENTRADA_URL . "/meetings", "title" => $translate->_("My Meetings"));
        $meetings = array();
        $meeting_model = new Models_AcademicAdvisor_Meeting();
        $meetings = $meeting_model->fetchAllByMemberID($ENTRADA_USER->getActiveID()); ?>
        
        <h1><?php echo $translate->_("Advisor Meeting Log"); ?></h1>
        <?php Entrada_Utilities_Flashmessenger::displayMessages($MODULE); ?>
        <table class="table table-bordered space-above">
            <thead>
                <tr>
                    <th><?php echo $translate->_("Date"); ?></th>
                    <th><?php echo $translate->_("Comments"); ?></th>
                    <th><?php echo $translate->_("Files"); ?></th>
                    <th><?php echo $translate->_("Logged By"); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if ($meetings) : ?>
                <?php foreach ($meetings as $meeting) : ?>
                    <tr>
                        <td><?php echo date("Y-m-d", $meeting["meeting_date"]); ?></td>
                        <td class="meeting-comment"><?php echo html_encode($meeting["comment"]); ?></td>
                        <td class="file-download-column">
                            <?php
                            $files = Models_AcademicAdvisor_File::fetchAllByMeetingID($meeting["meeting_id"]);
                            $advisor = Models_User::fetchRowByID($meeting["created_by"]);
                            foreach ($files as $file) : ?>
                                <div>
                                    <form class="no-margin-bottom" method="POST"
                                          action="<?php echo html_encode(ENTRADA_URL . "/api/api-meetings.inc.php"); ?>">
                                        <input type="hidden" name="method" value="serve-meeting-files"/>
                                        <input type="hidden" name="meeting_id"
                                               value="<?php echo html_encode($meeting["meeting_id"]); ?>"/>
                                        <input type="hidden" name="meeting_file_id"
                                               value="<?php echo html_encode($file["meeting_file_id"]); ?>"/>
                                        <input type="hidden" name="file_name"
                                               value="<?php echo html_encode($file["name"]); ?>"/>
                                        <input type="hidden" name="file_type"
                                               value="<?php echo html_encode($file["type"]); ?>"/>
                                        <input type="hidden" name="file_size" value="<?php echo html_encode($file["size"]); ?>"/>
                                        <label title="" class="btn btn-default space-below"
                                               for="download-label-<?php echo html_encode($file["meeting_file_id"]); ?>">
                                            <i class="fa fa-download meeting-download-icon" data-toggle="tooltip"
                                               data-placement="bottom"
                                               title="<?php echo html_encode($file["name"]); ?>"></i>
                                        </label>
                                        <input id="download-label-<?php echo html_encode($file["meeting_file_id"]); ?>"
                                               name="<?php echo html_encode($file["meeting_file_id"]); ?>" type="submit" value=""
                                               class="hide"/>
                                        <div class="public-file-label"><?php echo html_encode($file["title"]); ?></div>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </td>
                        <td><?php echo html_encode($advisor->getFirstname() . " " . $advisor->getLastname()); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="4"><?php echo $translate->_("You do not have any meetings logged"); ?></td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
        <?php
    } else {
        echo display_error();
    }
}