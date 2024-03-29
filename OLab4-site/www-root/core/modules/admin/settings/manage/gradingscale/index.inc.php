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
 * @author Developer: Eric Howarth <eric.howarth@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CONFIGURATION"))) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "read", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {

    $grading_scales = Models_Gradebook_Grading_Scale::fetchAllByOrganisationID($ORGANISATION_ID);

    ?>
    <h1>Grading Scale</h1>

    <div class="row-fluid">
        <span class="pull-right">
            <a class="btn btn-success" href="<?php echo ENTRADA_RELATIVE; ?>/admin/settings/manage/gradingscale?section=add&amp;org=<?php echo $ORGANISATION_ID; ?>"><i class="icon-plus-sign icon-white"></i> <?php echo $translate->_("Add New Grading Scale"); ?></a>
        </span>
    </div>
    <br />

    <?php
    if (is_array($grading_scales) && count($grading_scales) > 0) {
    ?>
    <form action="<?php echo ENTRADA_URL."/admin/settings/manage/gradingscale?org=".$ORGANISATION_ID; ?>&section=delete" method="POST">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th width="5%"></th>
                    <th width="75%">Title</th>
                    <th>Applicable From</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($grading_scales as $grading_scale) {
                ?>
                <tr>
                    <td>
                        <input type="checkbox" name="remove-ids[]" value="<?php echo $grading_scale->getID(); ?>"/>
                    </td>
                    <td><a href="<?php echo ENTRADA_URL."/admin/settings/manage/gradingscale?org=".$ORGANISATION_ID."&scale=".$grading_scale->getID(); ?>&section=edit"><?php echo $grading_scale->getTitle(); ?></td>
                    <td><?php date(DEFAULT_DATETIME_FORMAT, $grading_scale->getApplicableFrom()); ?></td>
                </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
        <br />
        <input type="submit" value="Delete Selected" class="btn btn-danger" />
    </form>
    <?php
    } else {
        echo display_notice("There are no Grading Scales to display");
    }
}
