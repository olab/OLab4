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
 * This file is the add/edit restricted days form.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_CONFIGURATION"))) {
	exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
		header("Location: ".ENTRADA_URL);
		exit;
} elseif (!$ENTRADA_ACL->amIAllowed('configuration', 'read')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	if ((defined("IN_CONFIGURATION") && IN_CONFIGURATION) && ((defined("EDIT_RESTRICTED_DAY") && EDIT_RESTRICTED_DAY) || (defined("ADD_RESTRICTED_DAY") && ADD_RESTRICTED_DAY))) {
		        
		if (isset($day_id)) {
			$day = Models_RestrictedDays::fetchRow($day_id);
		} else {
			$day = new Models_RestrictedDays();
		}
		switch ($STEP) {
			case 2 :
				
				if (isset($day_id)) {
					$PROCESSED["orday_id"] = $day_id;
				}
                
                if ((isset($_POST["date_type"])) && in_array($_POST["date_type"], array("specific", "weekly", "monthly", "yearly"))) {
                    $PROCESSED["date_type"]	= $_POST["date_type"];
                } else {
                    add_error("The <strong>Date Type</strong> field is required.");
                }
                
                switch ($PROCESSED["date_type"]) {
                    case "specific" :
                        if ((isset($_POST["specific_date"])) && ($_POST["specific_date"])) {
                            $PROCESSED["specific_date"]	= strtotime($_POST["specific_date"]);
                            $PROCESSED["month"] = date("n", $PROCESSED["specific_date"]);
                            $PROCESSED["day"] = date("j", $PROCESSED["specific_date"]);
                            if ((isset($_POST["repeat_toggle"])) && ((int) $_POST["repeat_toggle"])) {
                                $PROCESSED["year"] = NULL;
                            } else {
                                $PROCESSED["year"] = date("Y", $PROCESSED["specific_date"]);
                            }
                        } else {
                            add_error("The <strong>Restricted Date</strong> field is required.");
                        }
                    break;
                    case "weekly" :
                        if ((isset($_POST["weekly_weekdays"])) && ($weekday = ((int) $_POST["weekly_weekdays"])) && $weekday >= 1 && $weekday <= 7) {
                            $PROCESSED["day"] = $weekday;
                        } else {
                            add_error("The <strong>Weekday</strong> field is required.");
                        }
                    break;
                    case "monthly" :
                        if ((isset($_POST["offset"])) && ($offset = ((int) $_POST["offset"])) && $offset >= 1 && $offset <= 5) {
                            $PROCESSED["offset"] = $offset;
                        } else {
                            add_error("The <strong>Week Offset</strong> field is required.");
                        }
                        
                        if ((isset($_POST["weekday"])) && ($weekday = ((int) $_POST["weekday"])) && $weekday >= 1 && $weekday <= 7) {
                            $PROCESSED["day"] = $weekday;
                        } else {
                            add_error("The <strong>Weekday</strong> field is required.");
                        }
                    break;
                    case "yearly" :
                        $PROCESSED["date_type"] = "computed";
                        if ((isset($_POST["week_offset"])) && ($offset = ((int) $_POST["week_offset"])) && $offset >= 1 && $offset <= 5) {
                            $PROCESSED["offset"] = $offset;
                        } else {
                            add_error("The <strong>Week Offset</strong> field is required.");
                        }
                        
                        if ((isset($_POST["monthly_weekday"])) && ($weekday = ((int) $_POST["monthly_weekday"])) && $weekday >= 1 && $weekday <= 7) {
                            $PROCESSED["day"] = $weekday;
                        } else {
                            add_error("The <strong>Weekday</strong> field is required.");
                        }
                        
                        if ((isset($_POST["month"])) && ($month = ((int) $_POST["month"])) && $month >= 1 && $month <= 12) {
                            $PROCESSED["month"] = $month;
                        } else {
                            add_error("The <strong>Month</strong> field is required.");
                        }
                    break;
                }
                
				if (!$ERROR) {
                    $PROCESSED["proxy_id"] = $ENTRADA_USER->getID();
					$PROCESSED["updated_by"] = $ENTRADA_USER->getID();
					$PROCESSED["updated_date"] = time();
					
					if (defined("EDIT_RESTRICTED_DAY") && EDIT_RESTRICTED_DAY) {
						if($day->fromArray($PROCESSED)->update()) {
							add_success("The restricted day has successfully been updated. You will be redirected to the restricted days index in 5 seconds, or you can <a href=\"".ENTRADA_URL ."/admin/settings/manage/restricteddays?org=".$ORGANISATION_ID."\">click here</a> if you do not wish to wait.");
							add_statistic("restricted_day", "update", "orday_id", $PROCESSED["orday_id"], $ENTRADA_USER->getID());
						} else {
							add_error("An error occurred when attempting to update a restricted day [".$PROCESSED["orday_id"]."], an administrator has been informed, please try again later.");
							application_log("error", "Error occurred when updating restricted day, DB said: ".$db->ErrorMsg());
						}
					} else {
                        $PROCESSED["organisation_id"] = $ORGANISATION_ID;
                        if($day->fromArray($PROCESSED)->insert()) {
                            add_success("The restricted day has successfully been updated. You will be redirected to the restricted days index in 5 seconds, or you can <a href=\"".ENTRADA_URL ."/admin/settings/manage/restricteddays?org=".$ORGANISATION_ID."\">click here</a> if you do not wish to wait.");
                            add_statistic("restricted_day", "insert", "orday_id", $db->Insert_ID(), $ENTRADA_USER->getID());
                        } else {
                            add_error("An error occurred when attempting to create a new restricted day, an administrator has been informed, please try again later.");
                            application_log("error", "Error occurred when updating a restricted day, DB said: ".$db->ErrorMsg());
                        }
					}
					
				} else {
					$day = new Models_RestrictedDays();
					$day->fromArray($PROCESSED);
					$STEP = 1;
				}
				
			break;
			default:
			break;
		}
		
		switch ($STEP) {
			case 2 :
				if ($ERROR) {
					echo display_error();
					$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/settings/manage/restricteddays?org=".$ORGANISATION_ID."\\'', 5000)";
				}
				if ($SUCCESS) {
					echo display_success();
					$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/settings/manage/restricteddays?org=".$ORGANISATION_ID."\\'', 5000)";
				}
			break;
			case 1 :
			default:
				if ($ERROR) {
					echo display_error();
				}
                $ONLOAD[] = "jQuery('.datepicker').datepicker({
                                dateFormat: 'yy-mm-dd'
                            })";
                $ONLOAD[] = "jQuery('.add-on').on('click', function() {
                                if (jQuery(this).siblings('input').is(':enabled')) {
                                    jQuery(this).siblings('input').focus();
                                }
                            })";
				?>
                <script type="text/javascript">
                    jQuery(document).ready(function($){
                        var _old_toggle = jQuery.fn.button.prototype.constructor.Constructor.prototype.toggle;

                        jQuery.fn.button.prototype.constructor.Constructor.prototype.toggle = function () {
                            _old_toggle.apply(this);
                            this.$element.trigger('active');
                        }
                        jQuery('.toggle-days').live('active', function(event) {
                            event.preventDefault();
                            if (jQuery(this).hasClass('active') && jQuery('#weekday_'+ jQuery(this).data("value")).length < 1) {
                                jQuery('#days-container').html('<input type="hidden" value="'+ jQuery(this).data("value") +'" name="weekday" id="weekday_'+ jQuery(this).data("value") +'" />');
                            } else if (!jQuery(this).hasClass('active') && jQuery('#weekday_'+ jQuery(this).data("value")).length > 0) {
                                jQuery('#weekday_'+ jQuery(this).data("value")).remove();
                            }
                        });
                        $(".weekly a").on("click", function(e) {
                            $("#weekly_weekdays").val($(this).attr("data-value"));
                        });
                    });
                    
                    function loadDateType() {
                        var date_type = jQuery('#date_type').val();
                        if (date_type) {
                            jQuery('.date_controls').hide();
                            jQuery('#' + date_type + '_controls').show();
                        }
                    }
                </script>
				<form action="<?php echo html_encode(ENTRADA_URL); ?>/admin/settings/manage/restricteddays?org=<?php echo $ORGANISATION_ID; ?>&section=<?php echo isset($day_id) ? "edit&day_id=".html_encode($day_id) : "add" ; ?>&step=2" method="POST" id="entry-form">
					<div class="row-fluid">
                        <h2>Restricted Day</h2>
                    </div>
                    <div class="control-group row-fluid">
                        <label for="date_type" class="form-required span3">Date Type</label>
                        <span class="controls span8">
                            <select id="date_type" name="date_type" style="width: 100%" onchange="loadDateType()">
                            <option value="">-- Select A Date Type --</option>
                            <option value="specific"<?php echo ($day->getDateType() == "specific" || ($day->getDateType() == "computed" && !$day->getOffset()) ? " selected=\"selected\"" : ""); ?>>Specific</option>
                            <option value="weekly"<?php echo ($day->getDateType() == "weekly" ? " selected=\"selected\"" : "" ); ?>>Weekly</option>
                            <option value="monthly"<?php echo ($day->getDateType() == "monthly" ? " selected=\"selected\"" : ""); ?>>Monthly</option>
                            <option value="yearly"<?php echo ($day->getDateType() == "computed" && $day->getOffset() ? " selected=\"selected\"" : ""); ?>>Yearly</option>
                            </select>
                        </span>
                    </div>
                    <br />
                    <div class="date_controls" id="specific_controls"<?php echo ($day->getDateType() == "specific" || ($day->getDateType() == "computed" && !$day->getOffset()) ? "" : " style=\"display: none;\""); ?>>
                        <div class="row-fluid space-above">
                            <label class="control-label span3 form-required" for="specific_date">Restricted Date: </label>
                            <span class="span8">
                                <div class="input-append">
                                    <input type="text" class="input-small datepicker" value="<?php echo ($day->getDateType() == "specific" ? date("Y-m-d", mktime(0, 0, 0, $day->getMonth(), $day->getDay(), $day->getYear())) : ""); ?>" name="specific_date" id="specific_date" />
                                    <span class="add-on pointer"><i class="icon-calendar"></i></span>
                                </div>
                            </span>
                        </div>
                        <div class="control-group row-fluid">
                            <label for="repeat_toggle" class="form-required span3">Repeat Yearly</label>
                            <span class="controls span8">
                                <label>No <input type="radio" name="repeat_toggle" id="no_repeat" value="0"<?php echo ($day->getDateType() == "specific" ? " checked=\"checked\"" : ""); ?> /></label>
                                <label>Yes <input type="radio" name="repeat_toggle" id="repeat" value="1"<?php echo ($day->getDateType() == "computed" ? " checked=\"checked\"" : ""); ?> /></label>
                            </span>
                        </div>
                    </div>
                    <div class="date_controls" id="weekly_controls"<?php echo ($day->getDateType() == "weekly" ? "" : " style=\"display: none;\""); ?>>
                        <div id="days-container">
                            <?php 
                            if ($day->getDateType() == "weekly" && $day->getDay() && $day->getDay() <= 7) {
                                echo "<input type=\"hidden\" value=\"".$day->getDay()."\" name=\"weekday\" id=\"weekday_".$day->getDay()."\" />";
                            }
                            ?>
                        </div>
                        <div class="control-group row-fluid">
                            <label for="days-container" class="form-required span3">Weekday</label>
                            <span class="controls span8">
                                <div class="weekly btn-group" id="days-container" data-toggle="buttons-radio">
                                    <a class="btn toggle-days<?php echo ($day->getDateType() == "weekly" && $day->getDay() == 1 ? " active" : ""); ?>" data-value="1">Monday</a>
                                    <a class="btn toggle-days<?php echo ($day->getDateType() == "weekly" && $day->getDay() == 2 ? " active" : ""); ?>" data-value="2">Tuesday</a>
                                    <a class="btn toggle-days<?php echo ($day->getDateType() == "weekly" && $day->getDay() == 3 ? " active" : ""); ?>" data-value="3">Wednesday</a>
                                    <a class="btn toggle-days<?php echo ($day->getDateType() == "weekly" && $day->getDay() == 4 ? " active" : ""); ?>" data-value="4">Thursday</a>
                                    <a class="btn toggle-days<?php echo ($day->getDateType() == "weekly" && $day->getDay() == 5 ? " active" : ""); ?>" data-value="5">Friday</a>
                                    <a class="btn toggle-days<?php echo ($day->getDateType() == "weekly" && $day->getDay() == 6 ? " active" : ""); ?>" data-value="6">Saturday</a>
                                    <a class="btn toggle-days<?php echo ($day->getDateType() == "weekly" && $day->getDay() == 7 ? " active" : ""); ?>" data-value="7">Sunday</a>
                                </div>
                                <input type="hidden" id="weekly_weekdays" name="weekly_weekdays"/>
                            </span>
                        </div>
                    </div>
                    <div class="date_controls" id="monthly_controls"<?php echo ($day->getDateType() == "monthly" ? "" : " style=\"display: none;\""); ?>>
                        <div class="control-group row-fluid">
                            <span class="span3">&nbsp;</span>
                            <span class="controls span8">
                                <div class="btn-group" id="days-container" data-toggle="buttons-checkbox">
                                    <span class="controls span8">
                                        <label for="week_offset" class="span11">Repeat every 
                                        <select class="input-small" id="offset" name="offset">
                                            <option value="1"<?php echo ($day->getDateType() == "monthly" && $day->getOffset() == 1 ? " selected=\"selected\"" : ""); ?>>First</option>
                                            <option value="2"<?php echo ($day->getDateType() == "monthly" && $day->getOffset() == 2 ? " selected=\"selected\"" : ""); ?>>Second</option>
                                            <option value="3"<?php echo ($day->getDateType() == "monthly" && $day->getOffset() == 3 ? " selected=\"selected\"" : ""); ?>>Third</option>
                                            <option value="4"<?php echo ($day->getDateType() == "monthly" && $day->getOffset() == 4 ? " selected=\"selected\"" : ""); ?>>Fourth</option>
                                            <option value="5"<?php echo ($day->getDateType() == "monthly" && $day->getOffset() == 5 ? " selected=\"selected\"" : ""); ?>>Last</option>
                                        </select>
                                        <select class="input-small" id="weekday" name="weekday">
                                            <option value="1"<?php echo ($day->getDateType() == "monthly" && $day->getOffset() && $day->getDay() == 1 ? " selected=\"selected\"" : ""); ?>>Monday</option>
                                            <option value="2"<?php echo ($day->getDateType() == "monthly" && $day->getOffset() && $day->getDay() == 2 ? " selected=\"selected\"" : ""); ?>>Tuesday</option>
                                            <option value="3"<?php echo ($day->getDateType() == "monthly" && $day->getOffset() && $day->getDay() == 3 ? " selected=\"selected\"" : ""); ?>>Wednesday</option>
                                            <option value="4"<?php echo ($day->getDateType() == "monthly" && $day->getOffset() && $day->getDay() == 4 ? " selected=\"selected\"" : ""); ?>>Thursday</option>
                                            <option value="5"<?php echo ($day->getDateType() == "monthly" && $day->getOffset() && $day->getDay() == 5 ? " selected=\"selected\"" : ""); ?>>Friday</option>
                                            <option value="6"<?php echo ($day->getDateType() == "monthly" && $day->getOffset() && $day->getDay() == 6 ? " selected=\"selected\"" : ""); ?>>Saturday</option>
                                            <option value="7"<?php echo ($day->getDateType() == "monthly" && $day->getOffset() && $day->getDay() == 7 ? " selected=\"selected\"" : ""); ?>>Sunday</option>
                                        </select>
                                        of the month.</label>
                                    </span>
                                </div>
                            </span>
                        </div>
                    </div>
                    <div class="date_controls" id="yearly_controls"<?php echo ($day->getDateType() == "computed" && $day->getOffset() ? "" : " style=\"display: none;\""); ?>>
                        <div class="control-group row-fluid">
                            <span class="span3">&nbsp;</span>
                            <span class="controls span8">
                                <div class="btn-group" id="days-container" data-toggle="buttons-checkbox">
                                    <label for="week_offset" class="span11">Repeat every 
                                    <select class="input-small" id="week_offset" name="week_offset">
                                        <option value="1"<?php echo ($day->getDateType() == "computed" && $day->getOffset() == 1 ? " selected=\"selected\"" : ""); ?>>First</option>
                                        <option value="2"<?php echo ($day->getDateType() == "computed" && $day->getOffset() == 2 ? " selected=\"selected\"" : ""); ?>>Second</option>
                                        <option value="3"<?php echo ($day->getDateType() == "computed" && $day->getOffset() == 3 ? " selected=\"selected\"" : ""); ?>>Third</option>
                                        <option value="4"<?php echo ($day->getDateType() == "computed" && $day->getOffset() == 4 ? " selected=\"selected\"" : ""); ?>>Fourth</option>
                                        <option value="5"<?php echo ($day->getDateType() == "computed" && $day->getOffset() == 5 ? " selected=\"selected\"" : ""); ?>>Last</option>
                                    </select>
                                    <select class="input-small" id="monthly_weekday" name="monthly_weekday">
                                        <option value="1"<?php echo ($day->getDateType() == "computed" && $day->getOffset() && $day->getDay() == 1 ? " selected=\"selected\"" : ""); ?>>Monday</option>
                                        <option value="2"<?php echo ($day->getDateType() == "computed" && $day->getOffset() && $day->getDay() == 2 ? " selected=\"selected\"" : ""); ?>>Tuesday</option>
                                        <option value="3"<?php echo ($day->getDateType() == "computed" && $day->getOffset() && $day->getDay() == 3 ? " selected=\"selected\"" : ""); ?>>Wednesday</option>
                                        <option value="4"<?php echo ($day->getDateType() == "computed" && $day->getOffset() && $day->getDay() == 4 ? " selected=\"selected\"" : ""); ?>>Thursday</option>
                                        <option value="5"<?php echo ($day->getDateType() == "computed" && $day->getOffset() && $day->getDay() == 5 ? " selected=\"selected\"" : ""); ?>>Friday</option>
                                        <option value="6"<?php echo ($day->getDateType() == "computed" && $day->getOffset() && $day->getDay() == 6 ? " selected=\"selected\"" : ""); ?>>Saturday</option>
                                        <option value="7"<?php echo ($day->getDateType() == "computed" && $day->getOffset() && $day->getDay() == 7 ? " selected=\"selected\"" : ""); ?>>Sunday</option>
                                    </select>
                                    of 
                                    <select class="input-small" id="month" name="month">
                                        <option value="1"<?php echo ($day->getDateType() == "computed" && $day->getOffset() && $day->getMonth() == 1 ? " selected=\"selected\"" : ""); ?>>January</option>
                                        <option value="2"<?php echo ($day->getDateType() == "computed" && $day->getOffset() && $day->getMonth() == 2 ? " selected=\"selected\"" : ""); ?>>February</option>
                                        <option value="3"<?php echo ($day->getDateType() == "computed" && $day->getOffset() && $day->getMonth() == 3 ? " selected=\"selected\"" : ""); ?>>March</option>
                                        <option value="4"<?php echo ($day->getDateType() == "computed" && $day->getOffset() && $day->getMonth() == 4 ? " selected=\"selected\"" : ""); ?>>April</option>
                                        <option value="5"<?php echo ($day->getDateType() == "computed" && $day->getOffset() && $day->getMonth() == 5 ? " selected=\"selected\"" : ""); ?>>May</option>
                                        <option value="6"<?php echo ($day->getDateType() == "computed" && $day->getOffset() && $day->getMonth() == 6 ? " selected=\"selected\"" : ""); ?>>June</option>
                                        <option value="7"<?php echo ($day->getDateType() == "computed" && $day->getOffset() && $day->getMonth() == 7 ? " selected=\"selected\"" : ""); ?>>July</option>
                                        <option value="8"<?php echo ($day->getDateType() == "computed" && $day->getOffset() && $day->getMonth() == 8 ? " selected=\"selected\"" : ""); ?>>August</option>
                                        <option value="9"<?php echo ($day->getDateType() == "computed" && $day->getOffset() && $day->getMonth() == 9 ? " selected=\"selected\"" : ""); ?>>September</option>
                                        <option value="10"<?php echo ($day->getDateType() == "computed" && $day->getOffset() && $day->getMonth() == 10 ? " selected=\"selected\"" : ""); ?>>October</option>
                                        <option value="11"<?php echo ($day->getDateType() == "computed" && $day->getOffset() && $day->getMonth() == 11 ? " selected=\"selected\"" : ""); ?>>November</option>
                                        <option value="12"<?php echo ($day->getDateType() == "computed" && $day->getOffset() && $day->getMonth() == 12 ? " selected=\"selected\"" : ""); ?>>December</option>
                                    </select>
                                    .</label>
                                </div>
                            </span>
                        </div>
                    </div>
                    <br />
                    <div class="row-fluid">
                        <span class="span3">
                            <input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/settings/manage/restricteddays?org=<?php echo $ORGANISATION_ID; ?>'" />
                        </span>
                        <span class="span9">
                            <input type="submit" class="btn btn-primary pull-right" value="Submit" />
                        </span>
                    </div>
				</form>
				<?php
			break;
		}
	}
}