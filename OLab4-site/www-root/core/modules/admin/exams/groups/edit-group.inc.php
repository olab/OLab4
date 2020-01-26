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
 * The file that loads the add / edit group question form for exams.
 *
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Daniel Noji <dnoji@mednet.ucla.edu>
 * @copyright Copyright 2015 UC Regents. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_GROUPS"))) {
    
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("examquestiongroup", "create", false)) {
    $link = sprintf("<a href=\"mailto:%s\">%s</a>", html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"]));
    $message = $SECTION_TEXT["error"]["03"] . "<br /><br />" . $SECTION_TEXT["error"]["01b"] .$link . $SECTION_TEXT["error"]["01c"];
    add_error($message);

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    define("EDIT_GROUP", true);

    $SECTION_TEXT = $SUBMODULE_TEXT[$SECTION];
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "/" . $SECTION, "title" => $SECTION_TEXT["title_q"]);

    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.audienceselector.css?release=". html_encode(APPLICATION_VERSION) ."\" />";
    $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = \"". ENTRADA_URL ."\";</script>";
    $HEAD[] = "<script type=\"text/javascript\">var VIEW_PREFERENCE = \"". (isset($PREFERENCES["groups"]["selected_view"]) ? $PREFERENCES["groups"]["selected_view"] : "list") ."\";</script>";
    $HEAD[] = "<script type=\"text/javascript\">var API_URL = \"". ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-group" ."\";</script>";
    $HEAD[] = "<script type=\"text/javascript\">var SECTION_TEXT = ". json_encode($SECTION_TEXT["js"]) . "</script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.audienceselector.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.dataTables.min.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.editable.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/exams/groups/groups-admin.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
    
    $PROCESSED["group_id"] = "";
    if (isset($_GET["group_id"]) && $tmp_input = clean_input($_GET["group_id"], "int")) {
        $PROCESSED["group_id"] = $tmp_input;
    } elseif (isset($_POST["group_id"]) && $tmp_input = clean_input($_POST["group_id"], "int")) {
        $PROCESSED["group_id"] = $tmp_input;
    }
    
    if (isset($PROCESSED["group_id"])) {
        if ($ENTRADA_ACL->amIAllowed(new ExamQuestionGroupResource($PROCESSED["group_id"], true), "update")) {
            $group = Models_Exam_Group::fetchRowByID($PROCESSED["group_id"]);
            $PROCESSED["group_items"] = array();
            $PROCESSED["group_descriptors"] = array();
            $group_item_string = "";
            $group_descriptors_string = "";
            if ($group && is_object($group)) {
                $PROCESSED = $group->toArray();

                if (!empty($PROCESSED["group_descriptors"])) {
                    $group_descriptors_string = "&amp;group_descriptors[]=".implode('&amp;group_descriptors[]=', array_map('urlencode', $PROCESSED["group_descriptors"]));
                }

                if (isset($_GET["exam_id"]) && $tmp_input = clean_input($_GET["exam_id"], "int")) {
                    $PROCESSED["exam_id"] = $tmp_input;
                } elseif (isset($_POST["exam_id"]) && $tmp_input = clean_input($_POST["exam_id"], "int")) {
                    $PROCESSED["exam_id"] = $tmp_input;
                }
                ?>
                <h1><?php echo $SECTION_TEXT["title_q"]?></h1>
                <?php
                require_once("form.inc.php");
            }
            
            if (!empty($PROCESSED["group_descriptors"])) {
                $group_descriptors_string = implode('&amp;group_descriptors[]=', array_map('urlencode', $PROCESSED["group_descriptors"]));
                $group_descriptors_string = "&group_descriptors[]=".$group_descriptors_string;
            }
        } else {
            $link = sprintf("<a href=\"mailto:%s\">%s</a>", html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"]));
            $message = $SECTION_TEXT["error"]["01a"] . "<br /><br />" . $SECTION_TEXT["error"]["01b"] . $link . $SECTION_TEXT["error"]["01c"];

            add_error($message);

            echo display_error();

            application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this item [".$PROCESSED["group_id"]."]");
        }
    } else {
        echo display_error($SECTION_TEXT["error"]["02"]);
    }
}