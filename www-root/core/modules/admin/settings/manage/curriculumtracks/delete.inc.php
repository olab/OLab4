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
 * @author Developer: Eugene Bivol <ebivol@gmail.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_TRACKS"))) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "delete", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {

    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/settings/manage/curriculumtracks?org=".$ORGANISATION_ID."&section=delete&id=".$RECORD_ID, "title" => "Delete Evaluation Response Descriptors");

    echo "<h1>Delete Curriculum Tracks</h1>";

    if (isset($_POST["delete"])) {
        foreach ($_POST["delete"] as $curriculum_track_id) {
            if ($tmp_input = clean_input($curriculum_track_id, "numeric")) {
                $PROCESSED["delete"][] = $tmp_input;
                $curriculum_tracks[] = Models_Curriculum_Track::fetchRowByID($tmp_input);
            }
        }
    }

    switch ($STEP) {
        case 2 :
            if (is_array($curriculum_tracks)) {
                foreach ($curriculum_tracks as $curriculum_track) {
                    $curriculum_track_data = $curriculum_track->toArray();
                    $curriculum_track_data["deleted_date"] = time();

                    $org_uses = Models_Curriculum_Track::countOrgByTrackID($curriculum_track_data["curriculum_track_id"]);

                    if ($delete_connection = Models_Curriculum_Track::deleteTrackOrgByIdAndOrg($curriculum_track->getID(),$ORGANISATION_ID)) {
                        add_success("Successfully removed Curriculum Track [".$curriculum_track->getID()."] from your organisation.<br />");
                    }

                    if ($org_uses['org_num'] == 1) {
                        if ($curriculum_track->fromArray($curriculum_track_data)->update()) {
                            add_statistic("curriculum_tracks", "delete", "curriculum_track_id", $curriculum_track->getID(), $ENTRADA_USER->getID());

                            if (!$ERROR) {
                                add_success("Successfully deleted a Curriculum Track [<strong>".$curriculum_track->getCurriculumTrackName()."</strong>]. You will now be redirected to the Curriculum Tracks index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".ENTRADA_URL."/admin/settings/manage/curriculumtracks?org=".$ORGANISATION_ID."\"><strong>click here</strong></a> to continue.");
                            }
                        } else {
                            add_error("Failed to delete a Curriculum Track [<strong>".$curriculum_track->getCurriculumTrackName()."</strong>], an Administrator has been informed, please try again later. You will now be redirected to the Curriculum Tracks index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".ENTRADA_URL."/admin/settings/manage/curriculumtracks?org=".$ORGANISATION_ID."\"><strong>click here</strong></a> to continue.");
                            application_log("error", "Failed to delete Curriculum Track, DB said: ".$db->ErrorMsg());
                        }
                    }
                }
            } else {
                add_success("No Curriculum Tracks were selected, so no Curriculum Tracks were deleted. You will now be redirected to the Curriculum Tracks index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".ENTRADA_URL."/admin/settings/manage/curriculumtracks?org=".$ORGANISATION_ID."\"><strong>click here</strong></a> to continue.");
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
            $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/settings/manage/curriculumtracks?org=".$ORGANISATION_ID."\\'', 5000)";
            break;
        case 1 :
        default :

            if (isset($curriculum_tracks) && is_array($curriculum_tracks)) { ?>
                <div class="alert alert-info">You have selected the following Curriculum Tracks to be deleted. Please confirm below that you would like to delete them.</div>
                <form action="<?php echo ENTRADA_URL."/admin/settings/manage/curriculumtracks?org=".$ORGANISATION_ID; ?>&section=delete&step=2" method="POST" id="curriculumtracks-list">
                    <table class="table table-striped table-bordered" width="100%" cellpadding="0" cellspacing="0" border="0">
                        <thead>
                        <tr>
                            <th width="5%"></th>
                            <th>Name</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($curriculum_tracks as $curriculum_track) { ?>
                            <tr class="descriptor" data-id="<?php echo html_encode($curriculum_track->getID()); ?>">
                                <td><input class="delete" type="checkbox" name="delete[<?php echo html_encode($curriculum_track->getID()); ?>]" value="<?php echo html_encode($curriculum_track->getID()); ?>" <?php echo html_encode((in_array($curriculum_track->getID(), $PROCESSED["delete"]) ? "checked=\"checked\"" : "")); ?> /></td>
                                <td class="name"><a href="<?php echo ENTRADA_URL."/admin/settings/manage/curriculumtracks?org=".$ORGANISATION_ID; ?>&section=edit&id=<?php echo html_encode($curriculum_track->getID()); ?>"><?php echo html_encode($curriculum_track->getCurriculumTrackName()); ?></a></td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                    <div class="row-fluid">
                        <a href="<?php echo ENTRADA_URL."/admin/settings/manage/curriculumtracks?org=".$ORGANISATION_ID; ?>" class="btn" role="button">Cancel</a>
                        <input type="submit" class="btn btn-primary pull-right" value="Delete" />
                    </div>
                </form>
            <?php } else { ?>
                <div class="alert alert-info">No Curriculum Tracks have been selected to be deleted. Please <a href="<?php echo ENTRADA_URL."/admin/settings/manage/curriculumtracks?org=".$ORGANISATION_ID; ?>">click here</a> to return to the Curriculum Track index.</div>
            <?php }

            break;
    }
}