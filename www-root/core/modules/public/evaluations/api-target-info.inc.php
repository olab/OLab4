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
 * This file looks a bit different because it is called only by AJAX requests
 * and returns status codes based on it's ability to complete the requested
 * action. In this case, the requested action is to submit a response to an
 * answered quiz questions.
 *
 * 0	Unable to start processing request.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 * @version $Id: save-response.inc.php 1170 2010-05-01 14:35:01Z simpson $
 *
*/
ob_clear_open_buffers();

if((!defined("PARENT_INCLUDED")) || (!defined("IN_PUBLIC_EVALUATIONS"))) {
	/**
	 * @exception 0: Unable to start processing request.
	 */
	echo 0;
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	/**
	 * @exception 0: Unable to start processing request.
	 */
	echo 0;
	exit;
}

if ($RECORD_ID) {
    if (isset($_POST["etarget_id"]) && ($tmp_input = clean_input($_POST["etarget_id"], "int"))) {
        $etarget_id = $tmp_input;
    }
    if (isset($_POST["proxy_id"]) && ($tmp_input = clean_input($_POST["proxy_id"], "int"))) {
        $proxy_id = $tmp_input;
    }
    if (isset($_POST["eevaluator_id"]) && ($tmp_input = clean_input($_POST["eevaluator_id"], "int"))) {
        $eevaluator_id = $tmp_input;
    }
    if (isset($_POST["erequest_id"]) && ($tmp_input = clean_input($_POST["erequest_id"], "int"))) {
        $erequest_id = $tmp_input;
    } else {
        $erequest_id = false;
    }
    if (isset($_POST["preceptor_evaluation"]) && ($tmp_input = clean_input($_POST["preceptor_evaluation"], "int"))) {
        $preceptor_evaluation = $tmp_input;
    } else {
        $preceptor_evaluation = false;
    }

	if (((isset($etarget_id) && $etarget_id) || (isset($proxy_id) && $proxy_id)) && isset($eevaluator_id) && $eevaluator_id) {
        $evaluation_targets = Classes_Evaluation::getTargetsArray($RECORD_ID, $eevaluator_id, $ENTRADA_USER->getID(), false, true, false, $erequest_id);
        foreach ($evaluation_targets as $evaluation_target) {
            if (!isset($preceptor_evaluation) || !$preceptor_evaluation) {
                if (isset($proxy_id) && $proxy_id && isset($evaluation_target["proxy_id"]) && $evaluation_target["proxy_id"] == $proxy_id) {
                    $found = true;
                    break;
                } elseif (isset($etarget_id) && $etarget_id && isset($evaluation_target["etarget_id"]) && $evaluation_target["etarget_id"] == $etarget_id) {
                    $proxy_id = $evaluation_target["proxy_id"];
                    $found = true;
                    break;
                }
            } elseif ($preceptor_evaluation == $evaluation_target["event_id"]) {
                $preceptors = Classes_Evaluation::getPreceptorArray($RECORD_ID, $preceptor_evaluation, $ENTRADA_USER->getID());
                foreach ($preceptors as $preceptor) {
                    if ($preceptor["proxy_id"] == $proxy_id) {
                        $found = true;
                        break;
                    }
                }
                break;
            }
        }
		if (isset($found) && $found) {
            $query_profile	= "
								SELECT a.*, b.`group`, b.`role`, b.`organisation_id`
								FROM `".AUTH_DATABASE."`.`user_data` AS a
								LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
								ON b.`user_id` = a.`id`
								WHERE  b.`app_id` IN (".AUTH_APP_IDS_STRING.")
								AND b.`account_active` = 'true'
								AND (b.`access_starts` = '0' OR b.`access_starts` < ".$db->qstr(time()).")
								AND (b.`access_expires` = '0' OR b.`access_expires` >= ".$db->qstr(time()).")
								AND a.`id` = ".$db->qstr((int) $proxy_id)."
								AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
								GROUP BY a.`id`";
            $user = $db->GetRow($query_profile);
            if ($user) {
                echo "<div id=\"result-".$proxy_id."\" class=\"media ps-media-padding\" style=\"overflow: visible;".($key % 2 == 1 ? " background-color: rgb(238, 238, 238);" : "")."\">\n";

                $offical_file_active	= false;
                $uploaded_file_active	= false;

                /**
                 * If the photo file actually exists, and either
                 * 	If the user is in an administration group, or
                 *  If the user is trying to view their own photo, or
                 *  If the proxy_id has their privacy set to "Any Information"
                 */
                if ((@file_exists(STORAGE_USER_PHOTOS."/".$proxy_id."-official")) && ($ENTRADA_ACL->amIAllowed(new PhotoResource($proxy_id, (int) $user["privacy_level"], "official"), "read"))) {
                    $offical_file_active	= true;
                }

                /**
                 * If the photo file actually exists, and
                 * If the uploaded file is active in the user_photos table, and
                 * If the proxy_id has their privacy set to "Basic Information" or higher.
                 */
                $query			= "SELECT `photo_active` FROM `".AUTH_DATABASE."`.`user_photos` WHERE `photo_type` = '1' AND `photo_active` = '1' AND `proxy_id` = ".$db->qstr($proxy_id);
                $photo_active	= $db->GetOne($query);
                if ((@file_exists(STORAGE_USER_PHOTOS."/".$proxy_id."-upload")) && ($photo_active) && ($ENTRADA_ACL->amIAllowed(new PhotoResource($proxy_id, (int) $user["privacy_level"], "upload"), "read"))) {
                    $uploaded_file_active = true;
                }
                echo "<div id=\"img-holder-".$proxy_id."\" class=\"img-holder pull-left\">";
                if ($offical_file_active) {
                    echo "		<img id=\"official_photo_".$proxy_id."\" class=\"official people-search-thumb\" src=\"".webservice_url("photo", array($proxy_id, "official"))."\" width=\"72\" height=\"100\" alt=\"".html_encode($user["prefix"]." ".$user["firstname"]." ".$user["lastname"])."\" title=\"".html_encode($user["prefix"]." ".$user["firstname"]." ".$user["lastname"])."\" />\n";
                }

                if ($uploaded_file_active) {
                    echo "		<img id=\"uploaded_photo_".$proxy_id."\" class=\"uploaded people-search-thumb\" src=\"".webservice_url("photo", array($proxy_id, "upload"))."\" width=\"72\" height=\"100\" alt=\"".html_encode($user["prefix"]." ".$user["firstname"]." ".$user["lastname"])."\" title=\"".html_encode($user["prefix"]." ".$user["firstname"]." ".$user["lastname"])."\" />\n";
                }

                if (($offical_file_active) || ($uploaded_file_active)) {
                    echo "		<a id=\"zoomin_photo_".$proxy_id."\" class=\"zoomin\" onclick=\"growPic($('official_photo_".$proxy_id."'), $('uploaded_photo_".$proxy_id."'), $('official_link_".$proxy_id."'), $('uploaded_link_".$proxy_id."'), $('zoomout_photo_".$proxy_id."'));\">+</a>";
                    echo "		<a id=\"zoomout_photo_".$proxy_id."\" class=\"zoomout\" onclick=\"shrinkPic($('official_photo_".$proxy_id."'), $('uploaded_photo_".$proxy_id."'), $('official_link_".$proxy_id."'), $('uploaded_link_".$proxy_id."'), $('zoomout_photo_".$proxy_id."'));\"></a>";
                } else {
                    echo "		<img class=\"media-object people-search-thumb\" src=\"".ENTRADA_URL."/images/headshot-male.gif\" width=\"72\" height=\"100\" alt=\"No Photo Available\" title=\"No Photo Available\" />\n";
                }

                if (($offical_file_active) && ($uploaded_file_active)) {
                    echo "		<a id=\"official_link_".$proxy_id."\" class=\"img-selector one\" onclick=\"showOfficial($('official_photo_".$proxy_id."'), $('official_link_".$proxy_id."'), $('uploaded_link_".$proxy_id."'));\" href=\"javascript: void(0);\">1</a>";
                    echo "		<a id=\"uploaded_link_".$proxy_id."\" class=\"img-selector two\" onclick=\"hideOfficial($('official_photo_".$proxy_id."'), $('official_link_".$proxy_id."'), $('uploaded_link_".$proxy_id."'));\" href=\"javascript: void(0);\">2</a>";
                }
                echo "</div>";
                echo "<div class=\"media-body\">";
                echo "<div class=\"pull-left ps-media-body-margin\">";
                echo "<h5 class\"media-heading ps-media-heading\">" . html_encode((($user["prefix"]) ? $user["prefix"]." " : "").$user["firstname"]." ".$user["lastname"]) . "</h5>";
                echo "<span class=\"content-small\">";
                if($departmentResults = get_user_departments($proxy_id)) {
                    $deptCtr = 0;
                    foreach($departmentResults as $key => $departmentValue) {
                        if ($deptCtr == 0) {
                            $deptCtr++;
                            echo ucwords($departmentValue["department_title"]);
                        } else {
                            $deptCtr++;
                            echo "<br />".ucwords($departmentValue["department_title"]);
                        }
                    }
                } else {
                    if ($user["group"] == "student") {
                        $cohort = groups_get_cohort($proxy_id);
                    }
                    echo ucwords($user["group"])." > ".($user["group"] == "student" && isset($cohort["group_name"]) ? $cohort["group_name"] : ucwords($user["role"]));
                }
                echo (isset($ORGANISATIONS_BY_ID[$user["organisation_id"]]) ? "<br />".$ORGANISATIONS_BY_ID[$user["organisation_id"]]["organisation_title"] : "")."\n";
                echo "<br />";
                if ($user["privacy_level"] > 1) {
                    echo "			<a href=\"mailto:".html_encode($user["email"])."\" class=\"ps-email\">".html_encode($user["email"])."</a><br />\n";

                    if ($user["email_alt"]) {
                        echo "		<a href=\"mailto:".html_encode($user["email_alt"])."\" class=\"ps-email\">".html_encode($user["email_alt"])."</a>\n";
                    }
                }
                echo "</span></div>";
                echo "<div class=\"content-small ps-address-margin pull-left\"\">";
                if ($user["privacy_level"] > 2) {
                    if ($user["telephone"]) {
                        echo "Telephone: \n";
                        echo html_encode($user["telephone"]). "\n";
                    }
                    if ($user["fax"]) {
                        echo "Fax:\n";
                        echo html_encode($user["fax"])."\n";
                    }
                    if ($user["address"] && $user["city"]) {
                        echo "<br />Address:\n";
                        echo "<br />".html_encode($user["address"])."\n";
                        echo "<br />".html_encode($user["city"].($user["city"] && $user["province"] ? ", ".$user["province"] : ""))."\n";
                        echo "<br />".html_encode($user["country"].($user["country"] && $user["postcode"] ? ", ".$user["postcode"] : ""))."\n";
                    }
                    if ($user["office_hours"]) {
                        echo "<br />Office Hours:\n";
                        echo nl2br(html_encode($user["office_hours"]))."\n";
                    }
                }

                $query		= "	SELECT CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, b.`email`
                                    FROM `permissions` AS a
                                    LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
                                    ON b.`id` = a.`assigned_to`
                                    WHERE a.`assigned_by`=".$db->qstr($proxy_id)."
                                    AND (a.`valid_from` = '0' OR a.`valid_from` <= ".$db->qstr(time()).") AND (a.`valid_until` = '0' OR a.`valid_until` > ".$db->qstr(time()).")
                                    ORDER BY `valid_until` ASC";
                $assistants	= $db->GetAll($query);
                if ($assistants) {
                    echo "		<span class=\"content-small\">Administrative Assistants:</span>\n";
                    echo "		<ul class=\"assistant-list\">";
                    foreach ($assistants as $assistant) {
                        echo "		<li><a href=\"mailto:".html_encode($assistant["email"])."\">".html_encode($assistant["fullname"])."</a></li>";
                    }
                    echo "		</ul>";
                }
                echo "</div>\n";
                echo "</div>\n"; ?>
                <div class="clearfix"> </div>
                <?php
                echo "</div>\n";
            }
		}
	}
}
exit;
