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
 * @author Developer: Matt Simpson <simpson@queensu.ca> and friends.
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CURRICULUM_MAP_VERSIONS"))) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "delete", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    $BREADCRUMB[] = array("url" => "", "title" => "Delete Versions");

    echo "<h1>".$translate->_("Delete Curriculum Map Versions")."</h1>";

    $curriculum_map_versions = array();

    if (isset($_POST["remove_ids"])) {
        foreach ($_POST["remove_ids"] as $version_id) {
            if ($tmp_input = clean_input($version_id, "int")) {
                $curriculum_map_version = Models_Curriculum_Map_Versions::fetchRowByID($tmp_input, $ORGANISATION_ID);
                if ($curriculum_map_version) {
                    $curriculum_map_versions[$tmp_input] = $curriculum_map_version;
                }
            }
        }
    }

    switch ($STEP) {
        case 2 :
            if ($curriculum_map_versions) {
                $curriculum_map_version_ids = array_keys($curriculum_map_versions);

                $version = new Models_Curriculum_Map_Versions();
                if ($version->delete($curriculum_map_version_ids)) {
                    add_success("The selected Curriculum Map Versions were deleted from the system. You will now be redirected to the Curriculum Map Version index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"" . ENTRADA_URL . "/admin/curriculum/curriculummapversions?org=" . $ORGANISATION_ID . "\"><strong>click here</strong></a> to continue.");
                } else {
                    add_error("An error occurred while deleting the selected Curriculum Map Versions. A system administrator has been notified; please try again later. You will now be redirected to the Curriculum Map Version index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"" . ENTRADA_URL . "/admin/curriculum/curriculummapversions?org=" . $ORGANISATION_ID . "\"><strong>click here</strong></a> to continue.");

                    application_log("error", "Unable to delete all of the requested Curriculum Map Versions. Database said: ".$db->ErrorMsg());
                }
            } else {
                add_error("Please select one or more Curriculum Map Versions to delete. You will now be redirected to the Curriculum Map Version index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"" . ENTRADA_URL . "/admin/curriculum/curriculummapversions?org=" . $ORGANISATION_ID . "\"><strong>click here</strong></a> to continue.");
            }
            break;
    }

    switch ($STEP) {
        case 2 :
            if ($ERROR) {
                echo display_error();
            }
            if ($SUCCESS) {
                echo display_success();
            }

            $ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/curriculum/curriculummapversions?org=" . $ORGANISATION_ID."\\'', 5000)";
            break;
        case 1 :
        default :
            if ($curriculum_map_versions) {
                ?>
                <div class="alert alert-info">You have selected the following Curriculum Map Versions to be deleted. Please confirm below that you would like to delete them.</div>
                <form action="<?php echo ENTRADA_URL."/admin/curriculum/curriculummapversions?org=" . $ORGANISATION_ID; ?>&section=delete&step=2" method="POST" id="curriculummapversions-list">
                    <table class="table table-striped" summary="<?php echo $translate->_("Curriculum Map Versions"); ?>">
                        <colgroup>
                            <col style="width: 3%" />
                            <col style="width: 82%" />
                            <col style="width: 15%" />
                        </colgroup>
                        <thead>
                            <tr>
                                <th>&nbsp;</th>
                                <th><?php echo $translate->_("Curriculum Map Versions"); ?></th>
                                <th><?php echo $translate->_("Status"); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        foreach ($curriculum_map_versions as $version_id => $curriculum_map_version) {
                            echo "<tr>\n";
                            echo "  <td><input type=\"checkbox\" name = \"remove_ids[]\" value=\"" . $curriculum_map_version->getID() . "\" checked=\"checked\" /></td>\n";
                            echo "  <td><a href=\"" . ENTRADA_RELATIVE . "/admin/curriculum/curriculummapversions?section=edit&amp;org=" . $ORGANISATION_ID . "&amp;id=" . $curriculum_map_version->getID() . "\">" . $curriculum_map_version->getTitle() . "</a></td>\n";
                            echo "  <td><a href=\"" . ENTRADA_RELATIVE . "/admin/curriculum/curriculummapversions?section=edit&amp;org=" . $ORGANISATION_ID . "&amp;id=" . $curriculum_map_version->getID() . "\">" . ucwords($curriculum_map_version->getStatus()) . "</a></td>\n";
                            echo "</tr>\n";
                        }
                        ?>
                        </tbody>
                    </table>                    

                    <div class="row-fluid">
                        <a href="<?php echo ENTRADA_RELATIVE . "/admin/curriculum/curriculummapversions?org=" . $ORGANISATION_ID; ?>" class="btn" role="button">Cancel</a>
                        <input type="submit" class="btn btn-danger pull-right" value="Delete Selected" />
                    </div>
                </form>
                <?php
            } else {
                add_error("You must select one or more Curriculum Map Versions to delete. Please return to the <a href=\"" . ENTRADA_RELATIVE . "/admin/curriculum/curriculummapversions?org=" . $ORGANISATION_ID . "\"><strong>Curriculum Map Versions</strong></a> index to continue.");

                echo display_error();
            }
            break;
    }
}
