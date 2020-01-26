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
*
* @author Organisation: Queen's University
* @author Unit: School of Medicine
* @author Developer: Don Zuiker <don.zuiker@queensu.ca>
* @copyright Copyright 2015 Queen's University. All Rights Reserved.
*
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_ASSESSMENTS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed('assessments', 'read', false)) {
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
    exit;
} else {
    ob_clear_open_buffers();

    $output = array("aaData" => array());
    $count = 0;

    //Display a list of Assessments tha this user is the assessor for.
    $assessors = Models_Assessments_Distribution_Assessor::fetchAllByProxyID($ENTRADA_USER->getActiveID());
    if ($assessors) {
        if (isset($_GET["iDisplayStart"]) && isset($_GET["iDisplayLength"]) && $_GET["iDisplayLength"] != "-1" ) {
            $start = (int)$_GET["iDisplayStart"];
            $limit = (int)$_GET["iDisplayLength"];
        } else {
            $start = 0;
            $limit = count($assessors) - 1;
        }
        if ($_GET["sSearch"] != "") {
            $search_value = $_GET["sSearch"];
        }

        $target_text = "N/A";
        /* @var $assessor Models_Assessments_Distribution_Assessor */
        foreach ($assessors as $assessor) {
            $distribution = Models_Assessments_Distribution::fetchRowByID($assessor->getAdistributionID());
            $form = Models_Assessments_Form::fetchRowByID($distribution->getFormID());
            $target = Models_Assessments_Distribution_Target::fetchRowByDistributionID($distribution->getID());
            $targets = fetchAssessmentTargets($assessor->getAdistributionID());

            $form_type = fetchFormTypeTitle($assessor->getAdistributionID());
            $target_text = $form_type["title"];

            $schedule_distribution = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($distribution->getID());
            $schedule = Models_Schedule::fetchRowByID($schedule_distribution->getScheduleID());
            $schedule_children = $schedule->getChildren();

            $progress_value = "Awaiting Completion";

            if ($schedule_children) {
                foreach($schedule_children as $schedule_child) {
                    $progress_value = fetchTargetStatus($targets, $assessor, $schedule_child);

                    if ($targets && count($targets) > 1) {
                        $progress = false;
                    } else {
                        $progress = Models_Assessments_Progress::fetchRowByAdistributionIDAssessorTypeAssessorValue($assessor->getAdistributionID(), "internal", $ENTRADA_USER->getActiveId());
                    }

                    $url = ENTRADA_URL . "/assessments/assessment?adistribution_id=".$distribution->getID()."&schedule_id=".$schedule_child->getID()."&form_id=".$form->getID().($progress ? "&aprogress_id=".$progress->getID() : "");
                    if ($count >= $start && $count < ($start + $limit)) {
                        $row = array();
                        $row["modified"] = "<input type=\"checkbox\" name=\"delete[]\" value=\"".$assessor->getID()."\" />";
                        $row["distribution_title"] = "<a href=\"".$url."\">".html_encode($distribution->getTitle())."</a>";
                        $row["form_title"] = "<a href=\"".$url."\">".html_encode($form->getTitle())."</a>";
                        $row["target"] = "<a href=\"".$url."\">".html_encode($target_text)."</a>";
                        $row["due_date"] = "<a href=\"".$url."\">".date("Y-m-d", $schedule_child->getEndDate())."</a>";
                        $row["progress_value"] =  "<a href=\"".$url."\">".$progress_value."</a>";
                        $output["aaData"][] = $row;
                    }
                    $count++;
                }
            } else {
                $progress = false;
                if ($targets && count($targets) == 1) {
                    $progress_value = "Awaiting Completion";
                    $progress = Models_Assessments_Progress::fetchRowByAdistributionIDAssessorTypeAssessorValue($assessor->getAdistributionID(), "internal", $ENTRADA_USER->getActiveId());
                    if ($progress) {
                        switch($progress->getProgressValue()) {
                            case "inprogress":
                                $progress_value = "In Progress";
                                break;
                            case "complete":
                                $progress_value = "Complete";
                                break;
                            case "cancelled":
                                $progress_value = "Cancelled";
                                break;
                            default:
                                $progress_value = "Awaiting Completion";
                                break;
                        }
                    }
                } else {
                    $progress_value = fetchTargetStatus($targets, $assessor, $schedule);
                }
                $url = ENTRADA_URL . "/assessments/assessment?adistribution_id=".$distribution->getID()."&schedule_id=".$schedule->getID()."&form_id=".$form->getID().($progress ? "&aprogress_id=".$progress->getID() : "");
                if ($count >= $start && $count < ($start + $limit)) {
                    $row = array();
                    $row["modified"] = "<input type=\"checkbox\" name=\"delete[]\" value=\"".$assessor->getID()."\" />";
                    $row["distribution_title"] = "<a href=\"".$url."\">".html_encode($distribution->getTitle())."</a>";
                    $row["form_title"] = "<a href=\"".$url."\">".html_encode($form->getTitle())."</a>";
                    $row["target"] = "<a href=\"".$url."\">".html_encode($target_text)."</a>";
                    $row["due_date"] = "<a href=\"".$url."\">".date("Y-m-d", $schedule->getEndDate())."</a>";
                    $row["progress_value"] =  "<a href=\"".$url."\">".$progress_value."</a>";
                    $output["aaData"][] = $row;
                }
                $count++;
            }
        }
    }
    $output["iTotalRecords"] = (is_array($output) ? @count($output) : 0);
    $output["iTotalDisplayRecords"] = $count;
    $output["sEcho"] = clean_input($_GET["sEcho"], "int");
    if ($output && count($output)) {
        echo json_encode($output);
    }
    exit;
}