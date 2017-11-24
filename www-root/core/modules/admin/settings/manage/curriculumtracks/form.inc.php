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

if((!defined("PARENT_INCLUDED")) || (!defined("IN_TRACKS"))) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "update", false)) {
    $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."\\'', 15000)";

    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    if (isset($RECORD_ID) && $RECORD_ID) {
        $curriculum_track = Models_Curriculum_Track::fetchRowByID($RECORD_ID);
    } else {
        $curriculum_track = new Models_Curriculum_Track();
    }
    switch ($STEP) {
        case 2 :
            if (isset($_POST["curriculum_track_name"]) && $tmp_input = clean_input($_POST["curriculum_track_name"], array("trim", "striptags"))) {
                if (strlen($tmp_input) <= 256) {
                    $PROCESSED["curriculum_track_name"] = $tmp_input;
                } else {
                    add_error("The curriculum track too long.  Please specify a curriculum track that is 256 characters or less.");
                }
            } else {
                add_error("The Track Name is required.");
            }

            if (isset($_POST["curriculum_track_description"]) && ($track_description = clean_input($_POST["curriculum_track_description"], array("striptags", "trim")))) {
                $PROCESSED["curriculum_track_description"] = $track_description;
            } else {
                $PROCESSED["curriculum_track_description"] = "";
            }

            if (isset($_POST["curriculum_track_url"]) && ($track_url = clean_input($_POST["curriculum_track_url"], array("url", "trim")))) {
                $PROCESSED["curriculum_track_url"] = $track_url;
            } else {
                $PROCESSED["curriculum_track_url"] = "";
            }
            /**
             * Required field "order" / Descriptor Order
             */
            if (isset($_POST["curriculum_track_order"]) && ($order = clean_input($_POST["curriculum_track_order"], array("trim", "int")))) {
                $PROCESSED["curriculum_track_order"] = $order;
            } else {
                add_error("The order is required. Please select the order which this Track should appear in.");
            }

            $PROCESSED["organisation_id"] = $ORGANISATION_ID;

            $curriculum_track->fromArray($PROCESSED);

            if (!has_error()) {
                $existing_tracks = Models_Curriculum_Track::fetchAllByOrg($ORGANISATION_ID);
                if ($existing_tracks) {
                    foreach ($existing_tracks as $existing_track) {
                        if ($existing_track->getCurriculumTrackOrder() >= $PROCESSED["curriculum_track_order"]) {
                            $curriculum_track_array = $existing_track->toArray();
                            $curriculum_track_array["curriculum_track_order"]++;
                            $existing_track->fromArray($curriculum_track_array)->update();
                        }
                    }
                }

                if (defined("ADD_TRACK")) {
                    $PROCESSED["created_date"] = time();
                    $PROCESSED["created_by"] = $ENTRADA_USER->getID();
                    if ($insert_track = $curriculum_track->fromArray($PROCESSED)->insert()) {
                        if ($result_connection = Models_Curriculum_Track::insertTrackOrgRelationship($insert_track->getID(),$ORGANISATION_ID)) {
                            add_success("Successfully added the Curriculum Track [<strong>". $curriculum_track->getCurriculumTrackName()."</strong>]. You will now be redirected to the Curriculum Tracks index, please <a href=\"".ENTRADA_URL."/admin/settings/manage/curriculumtracks?org=".$ORGANISATION_ID."\">click here</a> if you do not wish to wait.");
                        } else {
                            //$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/settings/manage/curriculumtracks?org=".$ORGANISATION_ID."\\'', 5000)";
                            add_error("An error occurred while attempting to add the Curriculum Track [<strong>".  $curriculum_track->getCurriculumTrackName()."</strong>]. A system administrator has been informed, please try again later.<br /><br />You will now be redirected to the Curriculum Tracks index, please <a href=\"".ENTRADA_URL."/admin/settings/manage/curriculumtracks?org=".$ORGANISATION_ID."\">click here</a> if you do not wish to wait.");
                        }

                        /**
                         *  insert track image
                         */

                        if (isset($_POST["imageaction"]) && $_POST["imageaction"] == "uploadimage") {

                            $file_data = getimagesize($_FILES["image"]["tmp_name"]);
                            $file_dimensions = $file_data[0] . "," . $file_data[1];

                            if ($file_dimensions) {
                                $dimensions = explode(",", $file_dimensions);
                                foreach($dimensions as $dimension) {
                                    $tmp_dimensions[] = clean_input($dimension, "int");
                                }
                                $PROCESSED["dimensions"] = implode(",", $tmp_dimensions);
                            }

                            $sizes = array (1200, 600, 100);

                            $percentage = array();
                            foreach ($sizes as $size) {
                                $percentage[] = round($size/$dimensions[0],2);
                            }

                            $sizes = array("large" => array("width" => 1200,
                                "height" => round($dimensions[1]*$percentage[0])),
                                "medium" => array("width" => 600,
                                    "height" => round($dimensions[1]*$percentage[1])),
                                "small" => array("width" => 100,
                                    "height" => round($dimensions[1]*$percentage[2])),
                            );


                            $filesize = Entrada_Utilities_Image::uploadImage($_FILES["image"]["tmp_name"], $PROCESSED["dimensions"], $insert_track->getID(), "track", $sizes);

                            if ($filesize) {
                                $PROCESSED_PHOTO["resource_id"]			= $insert_track->getID();
                                $PROCESSED_PHOTO["resource_type"]		= "track";
                                $PROCESSED_PHOTO["image_active"]		= 1;
                                $PROCESSED_PHOTO["image_filesize"]		= $filesize;
                                $PROCESSED_PHOTO["updated_date"]		= time();

                                $resource_image = new Models_Resource_Image();

                                if (!$resource_image->fromArray($PROCESSED_PHOTO)->insert()) {
                                    add_error("An error ocurred while inserting image data in DB, please try again later.");
                                }

                            } else {
                                add_error("An error ocurred while moving your image in the system, please try again later.");
                            }
                        }


                    }
                } else {
                    $PROCESSED["updated_date"] = time();
                    $PROCESSED["updated_by"] = $ENTRADA_USER->getID();
                    if (isset($RECORD_ID)) {
                        if ($curriculum_track->update()) {
                            add_success("Successfully updated the Curriculum Track [<strong>". $curriculum_track->getCurriculumTrackName()."</strong>]. You will now be redirected to the Curriculum Track index, please <a href=\"".ENTRADA_URL."/admin/settings/manage/curriculumtracks?org=".$ORGANISATION_ID."\">click here</a> if you do not wish to wait.");
                        } else {
                          //  $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/settings/manage/curriculumtracks?org=".$ORGANISATION_ID."\\'', 5000)";
                            add_error("An error occurred while attempting to update the Curriculum Track [<strong>". $curriculum_track->getCurriculumTrackName()."</strong>]. A system administrator has been informed, please try again later.<br /><br />You will now be redirected to the Curriculum Tracks index, please <a href=\"".ENTRADA_URL."/admin/settings/manage/curriculumtracks?org=".$ORGANISATION_ID."\">click here</a> if you do not wish to wait.");
                        }
                    }
                }

            } else {
                $STEP = 1;
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
                $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/settings/manage/curriculumtracks?org=".$ORGANISATION_ID."\\'', 5000)";
            }
            if ($NOTICE) {
                echo display_notice();
            }
            break;
        case 1 :
        default :
            if ($ERROR) {
                echo display_error();
            }
            if ($SUCCESS) {
                echo display_success();
            }
            if ($NOTICE) {
                echo display_notice();
            }
            ?>
            <form action="<?php echo ENTRADA_URL . "/admin/settings/manage/curriculumtracks?org=".$ORGANISATION_ID; ?>&section=<?php echo defined("EDIT_TRACK") ? "edit&id=".$RECORD_ID : "add"; ?>&step=2" method="POST" class="form-horizontal"  enctype="multipart/form-data">
                <h2 title="Track Details Section"><?php echo $translate->_("Track Details"); ?></h2>
                <div id="track-details-section">
                    <?php if (defined("EDIT_TRACK")) { ?>
                        <div id="upload-image-mod" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="label" aria-hidden="true">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                                <h3 id="label">Upload Image</h3>
                            </div>
                            <div class="modal-body">
                                <div class="preview-img"></div>
                                <div class="description alert" style="height:264px;width:483px;padding:20px;">
                                    To upload a new curriculum track image you can drag and drop it on this area or use the Browse button to select an image from your computer.
                                </div>
                            </div>
                            <div class="modal-footer">
                                <input type="hidden" name="coordinates" id="coordinates" value="" />
                                <input type="hidden" name="resource_type" id="resource_type" value="track" />
                                <input type="hidden" name="dimensions" id="dimensions" value="" />
                                <input type="file" name="image" id="image" />
                                <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
                                <button class="btn"  data-dismiss="modal" class="btn btn-primary" id="upload-image-button">Upload</button>
                            </div>
                        </div>
                        <div id="image-container" class="pull-right">
                            <a href="#upload-image-mod" id="upload-image-modal-btn" data-toggle="modal" class="btn btn-primary" id="upload-image">Upload Image</a>
                            <span>
                                <img src="<?php echo ENTRADA_URL; ?>/admin/courses?section=api-image&method=get-image&resource_type=track&resource_id=<?php echo $RECORD_ID;?>" width="150" height="250" class="img-polaroid" />
                            </span>
                        </div>
                    <?php } ?>
                    <div class="control-group">
                        <label class="control-label form-required" for="curriculum_track_name">Track Name</label>
                        <div class="controls">
                            <input name="curriculum_track_name" id="curriculum_track_name" type="text" class="span6" value="<?php echo (isset($curriculum_track) && $curriculum_track && $curriculum_track->getCurriculumTrackName() ? $curriculum_track->getCurriculumTrackName() : ""); ?>" />
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="curriculum_track_description">Track Description</label>
                        <div class="controls">
                            <textarea id="curriculum_track_description" name="curriculum_track_description" class="span6 expandable"><?php echo (isset($curriculum_track) && $curriculum_track && $curriculum_track->getCurriculumTrackDescription() ? $curriculum_track->getCurriculumTrackDescription() : ""); ?></textarea>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="curriculum_track_url">Track Public URL</label>
                        <div class="controls">
                            <input name="curriculum_track_url" id="curriculum_track_url" type="text" class="span6" value="<?php echo (isset($curriculum_track) && $curriculum_track && $curriculum_track->getCurriculumTrackURL() ? $curriculum_track->getCurriculumTrackURL() : ""); ?>" />
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label form-required" for="curriculum_track_order">Order</label>
                        <div class="controls">
                            <select name="curriculum_track_order" id="curriculum_track_order" class="span6">
                                <option value="">-- Please select the display order --</option>
                                <?php
                                $curriculum_tracks = Models_Curriculum_Track::fetchAllByOrg($ORGANISATION_ID);
                                if ($curriculum_tracks && @count($curriculum_tracks)) {
                                    $max_count = count($curriculum_tracks);
                                    $count = 0;
                                    foreach ($curriculum_tracks as $curriculum_track) {
                                        $count++;
                                        if (!$RECORD_ID || $RECORD_ID != $curriculum_track->getID()) {
                                            if ($count < $max_count) {
                                                echo "<option value=\"".$curriculum_track->getCurriculumTrackOrder()."\">Before ".$curriculum_track->getCurriculumTrackName()."</option>";
                                            } else {
                                                echo "<option value=\"".$curriculum_track->getCurriculumTrackOrder()."\">Before ".$curriculum_track->getCurriculumTrackName()."</option>";
                                                echo "<option value=\"".($curriculum_track->getCurriculumTrackOrder() + 1)."\">After ".$curriculum_track->getCurriculumTrackName()."</option>";
                                            }
                                        } else {
                                            echo "<option value=\"".$curriculum_track->getCurriculumTrackOrder()."\" selected=\"selected\">Do not change</option>";
                                        }
                                    }
                                } else {
                                    echo "<option value=\"1\">First</option>\n";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="row-fluid">
                        <a href="<?php echo ENTRADA_URL."/admin/settings/manage/curriculumtracks?org=".$ORGANISATION_ID; ?>" class="btn">Cancel</a>
                        <input type="submit" class="btn btn-primary pull-right" value="Save" />
                    </div>
                </div>
            </form>
            <?php
        break;
    }
}
