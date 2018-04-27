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
 * @author Organisation: University of British Columbia
 * @author Unit: Faculty of Medicine
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_WEEKS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("weekcontent", "update", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    $curriculum_types = Models_Curriculum_Type::fetchAllByOrg($ENTRADA_USER->getActiveOrganisation());
    $weeks_by_type = array();
    foreach ($curriculum_types as $curriculum_type) {
        $curriculum_type_id = $curriculum_type->getID();
        $curriculum_type_name = $curriculum_type->getCurriculumTypeName();
        $weeks = Models_Week::fetchAllByCurriculumType($curriculum_type_id);
        if ($weeks) {
            $weeks_by_type[$curriculum_type_name] = $weeks;
        }
    }
    $view = function() use ($translate, $weeks_by_type) {
        ?>
        <h1><?php echo $translate->_("Weeks"); ?></h1>
        <div class="row-fluid">
            <span class="pull-right">
                <a class="btn btn-success" href="<?php echo ENTRADA_RELATIVE; ?>/admin/weeks?section=add"><i class="icon-plus-sign icon-white"></i> <?php echo $translate->_("Add Week"); ?></a>
            </span>
        </div>
        <br />
        <?php if ($weeks_by_type): ?>
            <form action ="<?php echo ENTRADA_URL;?>/admin/weeks?section=delete" method="post">
                <?php foreach ($weeks_by_type as $curriculum_type_name => $weeks): ?>
                    <h2><?php echo $curriculum_type_name; ?></h2>
                    <table class="table table-striped" summary="<?php echo $translate->_("Weeks"); ?>">
                        <colgroup>
                            <col style="width: 3%" />
                            <col style="width: 97%" />
                        </colgroup>
                        <tbody>
                            <?php foreach ($weeks as $week): ?>
                                <tr>
                                    <td><input type="checkbox" name = "remove_ids[]" value="<?php echo $week->getID(); ?>"/></td>
                                    <td><a href="<?php echo ENTRADA_RELATIVE; ?>/admin/weeks?section=edit&amp;id=<?php echo $week->getID(); ?>"><?php echo $week->getWeekTitle(); ?></a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
                <input type="submit" class="btn btn-danger" value="Delete Selected" />
            </form>
        <?php else: ?>
            <?php echo display_notice($translate->_("There are currently no Weeks.")); ?>
        <?php endif; ?>
        <?php
    };
    $view();
}
/* vim: set expandtab: */
