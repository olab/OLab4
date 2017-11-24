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

if((!defined("PARENT_INCLUDED")) || (!defined("IN_CONFIGURATION"))) {
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
    $JQUERY[] = '<script src="'.ENTRADA_URL.'/javascript/jquery/jquery.dataTables.min.js?release='.html_encode(APPLICATION_VERSION).'"></script>';
    $JQUERY[] = '<script src="'.ENTRADA_URL.'/javascript/settings/gradingscale/form.js?release='.html_encode(APPLICATION_VERSION).'"></script>';
    $JQUERY[] = '<script>var ENTRADA_URL = "'.ENTRADA_URL.'";</script>';

    if (isset($SCALE_ID) && (int) $SCALE_ID) {
        $grading_scale = Models_Gradebook_Grading_Scale::fetchRowByID($SCALE_ID);
        $grading_ranges = Models_Gradebook_Grading_Range::fetchAllByScale($grading_scale->getID());
    } else {
        $grading_scale = new Models_Gradebook_Grading_Scale();
        $grading_ranges = array();
    }

    /**
     * See if there is a default scale for this organisation (where Applicable Date is null)
     * There can only be one - so Applicable Date becomes required in that case
     */
    $org_default_scale = Models_Gradebook_Grading_Scale::fetchDefaultScaleForOrganisation($ORGANISATION_ID);
    if ($org_default_scale && ((defined("ADD_GRADING_SCALE") || $org_default_scale->getID() <> $grading_scale->getID()))) {
        $applicable_from_required = true;
    } else {
        $applicable_from_required = false;
    }

    switch ($STEP) {
        case 2 :
            if (isset($_POST["grading_scale_title"]) && $tmp_input = clean_input($_POST["grading_scale_title"], array("trim", "striptags"))) {
                if (strlen($tmp_input) <= 256) {
                    $PROCESSED["title"] = $tmp_input;
                } else {
                    add_error("The grading scale Title is too long.  Please specify a title that is 256 characters or less.");
                }
            } else {
                add_error("The Title is required.");
            }

            /**
             * Applicable From is conditionally required - if there is already a default scale, then it is required, unless the current one is the null one
             */
            if (isset($_POST["grading_scale_applicable_date"]) && ($grading_scale_applicable_date = clean_input($_POST["grading_scale_applicable_date"], array("striptags", "trim")))) {
                $PROCESSED["applicable_from"] = strtotime($grading_scale_applicable_date);
            } else {
                $PROCESSED["applicable_from"] = null;
                if ($applicable_from_required) {
                    add_error("The Applicable From date is required.");
                }
            }

            $PROCESSED["organisation_id"] = $ORGANISATION_ID;

            $grading_scale->fromArray($PROCESSED);

            /**
             * extract ranges from the POST variables
             */
            if (isset($_POST["letter_grade"]) && is_array($_POST["letter_grade"]) && 
                isset($_POST["numeric_grade_min"]) && is_array($_POST["numeric_grade_min"]) &&
                isset($_POST["gpa"]) && is_array($_POST["gpa"]) &&
                isset($_POST["notes"]) && is_array($_POST["notes"])) {

                $grading_ranges = array();
                for ($i=0; $i< count($_POST["letter_grade"]); $i++) {
                    /**
                     * letter grade is required
                     */
                    $letter_grade = null;
                    if (!(isset($_POST["letter_grade"][$i]) && $letter_grade = clean_input($_POST["letter_grade"][$i], array("striptags", "trim")))) {
                        add_error("The Letter Grade is required.");
                    }

                    /**
                     * Numeric grade is required and must be between 0 and 100
                     */
                    $numeric_grade_min = null;
                    if (isset($_POST["numeric_grade_min"][$i]) && strlen($_POST["numeric_grade_min"][$i]) > 0) {
                        $numeric_grade_min = (int) clean_input($_POST["numeric_grade_min"][$i], array("trim", "int"));
                        if ($numeric_grade_min < 0 || $numeric_grade_min > 100) {
                            add_error("The Start Percentage must be between 0 and 100.");
                        }
                    } else {
                        add_error("The Start Percentage is required.");
                    }

                    /**
                     * GPA must be between 0 and 4.3, if provided
                     */
                    $gpa = null;
                    if (isset($_POST["gpa"][$i]) && strlen($_POST["gpa"][$i]) > 0) {
                        $gpa = floatval(clean_input($_POST["gpa"][$i], array("trim")));
                        if ($gpa < 0 || $gpa > 4.3) {
                            add_error("The GPA must be between 0 and 4.30.");
                        }
                    }

                    $notes = null;
                    if (isset($_POST["notes"][$i]) && strlen($_POST["notes"][$i]) > 0) {
                        $notes = clean_input($_POST["notes"][$i], array("striptags", "trim"));
                    }
                    
                    $range = array(
                        "letter_grade" => $letter_grade,
                        "numeric_grade_min" => $numeric_grade_min,
                        "gpa" => $gpa,
                        "notes" => $notes
                    );
                    $grading_ranges[] = new Models_Gradebook_Grading_Range($range);
                }
            }
            
            if (!has_error()) {

                /**
                 * insert or update the scale
                 */
                $grading_scale->setupdatedDate(time());
                $grading_scale->setUpdatedBy($ENTRADA_USER->getID());
                if (defined("ADD_GRADING_SCALE")) {
                    $operation = "add";
                    $scale_result = $grading_scale->insert();
                } else {
                    $operation = "update";
                    $scale_result = $grading_scale->update();
                }

                /**
                 * Insert the ranges
                 */
                $range_error = false;
                if ($scale_result) {
                    Models_Gradebook_Grading_Range::deleteByScale($grading_scale->getID());
                    if (is_array($grading_ranges) && count($grading_ranges) > 0) {
                        foreach ($grading_ranges as $range) {
                            $range->setupdatedDate(time());
                            $range->setUpdatedBy($ENTRADA_USER->getID());
                            $range->setAgscaleID($grading_scale->getID());
                            if (!$range->insert()) {
                                $range_error = true;
                            }
                        }
                    }
                }

                if (!$scale_result || $range_error) {
                    add_error("An error occurred while attempting to ".$operation." Grading Scale [<strong>". $grading_scale->getTitle()."</strong>]. A system administrator has been informed, please try again later.<br /><br />You will now be redirected to the Grading Scale page, please <a href=\"".ENTRADA_URL."/admin/settings/manage/gradingscale?org=".$ORGANISATION_ID."\">click here</a> if you do not wish to wait.");
                    $ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/settings/manage/gradingscale?org=".$ORGANISATION_ID."\\'', 5000)";
                } else {
                    add_success("Successful ".$operation." of Grading Scale [<strong>". $grading_scale->getTitle()."</strong>]. You will now be redirected to the Grading Scale page, please <a href=\"".ENTRADA_URL."/admin/settings/manage/gradingscale?org=".$ORGANISATION_ID."\">click here</a> if you do not wish to wait.");
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
                $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/settings/manage/gradingscale?org=".$ORGANISATION_ID."\\'', 5000)";
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
            <form id="grading_scale_form" class="form-horizontal" action="<?php echo ENTRADA_URL . "/admin/settings/manage/gradingscale?org=".$ORGANISATION_ID; ?>&section=<?php echo defined("EDIT_GRADING_SCALE") ? "edit&scale=".$SCALE_ID : "add"; ?>&step=2" method="POST">
                <div class="control-group">
                    <label class="control-label form-required" for="grading_scale_title">Title</label>
                    <div class="controls">
                        <input type="text" id="grading_scale_title" name="grading_scale_title" class="span8" value="<?php echo (!empty($grading_scale) ? $grading_scale->getTitle() : ""); ?>" required/>
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label<?php echo ($applicable_from_required ? " form-required" : ""); ?>" for="grading_scale_applicable_date">Applicable Date</label>
                    <div class="controls">
                        <div class="input-append">
                            <input type="text" id="grading_scale_applicable_date" name="grading_scale_applicable_date" class="span6 datepicker" value="<?php echo (!empty($grading_scale) && $grading_scale->getApplicableFrom() != "") ? date("Y-m-d", $grading_scale->getApplicableFrom()) : ""; ?>" <?php echo ($applicable_from_required ? "required" : ""); ?> />
                            <span class="add-on pointer datepicker-icon"><i class="icon-calendar"></i></span>
                        </div>
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label form-required" for="grading_scale_range">Ranges</label>
                    <div class="controls">
                        <button type="button" class="btn btn-success pull-right" id="add-range">
                            <i class="icon-plus-sign icon-white"></i> <?php echo $translate->_("Add Range"); ?>
                        </button>
                        <input type="hidden" id="grading_ranges" name="grading_ranges" />
                        <table id="grading_scale_range" class="table table-striped" summary="This grid displays each range for a grade scheme.  The symbol, starting percentage, GPA and Notes be defined.  You can also remove a grade range." style="width:80%;">
                            <thead>
                                <tr>
                                    <th scope="col" style="width:25%">Letter Grade<span style="color:red">*</span>&nbsp;</th>
                                    <th scope="col">Start %<span style="color:red">*</span>&nbsp;</th>
                                    <th scope="col">GPA</th>
                                    <th scope="col" style="width:30%">Notes</th>
                                    <th scope="col">Remove</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            if (is_array($grading_ranges) && count($grading_ranges) > 0) {
                                foreach ($grading_ranges as $range) {
                                ?>
                                <tr>
                                    <td><input type="text" name="letter_grade[]" class="input-small" value="<?php echo $range->getLetterGrade(); ?>" required /></td>
                                    <td><input type="text" name="numeric_grade_min[]" class="input-mini numeric_grade_min" value="<?php echo $range->getNumericGradeMin(); ?>" <?php echo ($range->getNumericGradeMin() == 0 ? "readonly" : ""); ?> required /></td>
                                    <td><input type="text" name="gpa[]" class="input-mini" value="<?php echo $range->getGpa(); ?>" /></td>
                                    <td><input type="text" name="notes[]" class="input-small" value="<?php echo $range->getNotes(); ?>" /></td>
                                    <td><span class="input-mini"><?php echo ($range->getNumericGradeMin() <> 0 ? '<img src="'.ENTRADA_URL.'/images/action-delete.gif" class="list-cancel-image">' : ''); ?></span></td>
                                </tr>
                                <?php
                                }
                            } else {
                            ?>
                                <tr>
                                    <td><input class="input-small" type="text"  name="letter_grade[]" required /></td>
                                    <td><input class="input-mini numeric_grade_min" type="text" name="numeric_grade_min[]" value="0" readonly required /></td>
                                    <td><input class="input-mini" type="text" name="gpa[]" /></td>
                                    <td><input class="input-small" type="text" name="notes[]" /></td>
                                    <td><span class="input-mini"></span></td>
                                </tr>
                            <?php
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="row-fluid">
                    <a href="<?php echo ENTRADA_URL."/admin/settings/manage/gradingscale?org=".$ORGANISATION_ID; ?>" class="btn" type="button"><?php echo $translate->_("global_button_cancel"); ?></a>
                    <input type="submit" class="btn btn-primary pull-right" value="<?php echo $translate->_("global_button_save"); ?>" />
                </div>
            </form>
            <?php
        break;
    }
}
