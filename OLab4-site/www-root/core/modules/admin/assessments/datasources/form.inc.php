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
 * The form that allows users to add and edit formbank forms.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("ADD_DATASOURCE") && !defined("EDIT_DATASOURCE"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

    switch ($STEP) {
        case 2 :
            if (!$ERROR) {
                $datasource = new Models_Assessments_Data_Source($PROCESSED);

                if ($datasource->{$METHOD}()) {


                } else {
                    add_error("An error ocurred while attempting to update the datasource.");
                    $STEP = 1;
                }

            } else {
                $STEP = 1;
            }
        break;
    }

    switch ($STEP) {
        case 2 :
            if ($SUCCESS) {
                echo display_success();
            }
            if ($ERROR) {
                echo display_error();
            }
        break;
        case 1 :
        default :
            if ($SUCCESS) {
                echo display_success();
            }
            if ($ERROR) {
                echo display_error();
            }
            ?>
            <script>
            var ENTRADA_URL = "<?php echo ENTRADA_URL; ?>";
            var submodule_text = JSON.parse('<?php echo json_encode($SUBMODULE_TEXT); ?>');
            </script>
            <form action="<?php echo ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?step=2&section=" . $SECTION . ($METHOD == "update" ? "&id=" . $PROCESSED["dsource_id"] : ""); ?>" class="form-horizontal" method="POST">
                <div class="row-fluid">
                    <a href="<?php echo ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE; ?>" class="btn btn-default"><?php echo $translate->_("Back"); ?></a>
                    <input type="submit" class="btn btn-primary pull-right" value="<?php echo $translate->_("Submit"); ?>" />
                </div>
            </form>
            <?php
        break;
    }
}