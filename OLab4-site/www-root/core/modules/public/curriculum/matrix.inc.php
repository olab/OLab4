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
 * This file displays the list of objectives pulled
 * from the entrada.global_lu_objectives table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_CURRICULUM"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("search", "read")) {
	add_error("Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]]["group"]."] and role [".$_SESSION["permissions"][$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]]["role"]."] do not have access to this module [".$MODULE."]");
} else {
        
    if (!isset($_SESSION[APPLICATION_IDENTIFIER][$ENTRADA_USER->getActiveID()]["matrix"]["objective_id"])) {
        $objective_name = $translate->_("events_filter_controls");
        $clinical_presentations_name = $objective_name["co"]["global_lu_objectives_name"];
        $objective = Models_Objective::fetchRowByName($ENTRADA_USER->getActiveOrganisation(), $clinical_presentations_name);
        if ($objective) {
            $_SESSION[APPLICATION_IDENTIFIER][$ENTRADA_USER->getActiveID()]["matrix"]["objective_id"] = $objective->getID();
        }
    }
    
    $JQUERY[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/jquery.qtip.min.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
    
    search_subnavigation("matrix");
     
	echo "<h1>Curriculum Matrix</h1>";
    
    switch ($STEP) {
        case "2" :
            if (isset($_GET["method"]) && $tmp_input = clean_input($_GET["method"], array("trim", "striptags"))) {
                $PROCESSED["method"] = $tmp_input;
            }
            
            switch ($PROCESSED["method"]) {
                case "get-matrix-csv" :
                    ob_clear_open_buffers();
                    
                    if (isset($_GET["objective_id"]) && $tmp_input = clean_input($_GET["objective_id"], "int")) {
                        $PROCESSED["objective_id"] = $tmp_input;
                        
                        $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["matrix"]["objective_id"] = $PROCESSED["objective_id"];
                    }
                    
                    if (isset($_GET["depth"]) && $tmp_input = clean_input($_GET["depth"], "int")) {
                        $PROCESSED["depth"] = $tmp_input;
                    }
                    
                    if ($PROCESSED["objective_id"]) {
                        $curriculum_matrix = Entrada_Curriculum_Matrix::getCurriculumMatrixData($PROCESSED["objective_id"], $PROCESSED["depth"]);
                    }

                    if ($curriculum_matrix) {
                        
                        $rows = array();
                        
                        if ($curriculum_matrix["objectives"]) {
                            $row = array();
                            $row[] = "Courses";
                            foreach ($curriculum_matrix["objectives"] as $objective) {
                                $row[] = $objective["objective_name"];
                            }
                            $rows[] = $row;
                        }
                        
                        $term = "";
                        if ($curriculum_matrix["courses"]) {
                            foreach ($curriculum_matrix["courses"] as $course) {
                                $row = array();
                                if ($course["term_name"] != $term) {
                                    $row[] = $course["term_name"];
                                    
                                    for ($i = 1; $i <= count($course["objectives"]); $i++) {
                                        $row[] = " ";
                                    }
                                    
                                    $rows[] = $row;
                                    $row = array();
                                }
                                $row[] = $course["course_code"] . " - " . $course["course_name"];
                                foreach ($course["objectives"] as $objective) {
                                    $row[] = $objective ? $objective["importance"] : " ";
                                }
                                $rows[] = $row;
                                $term = $course["term_name"];
                            }
                            
                            header("Pragma: public");
                            header("Expires: 0");
                            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                            header("Content-Type: application/force-download");
                            header("Content-Type: application/octet-stream");
                            header("Content-Type: text/csv");
                            header("Content-Disposition: attachment; filename=\"curriculum-matrix-".date("Y-m-d").".csv\"");
                            header("Content-Transfer-Encoding: binary");
                            
                            $fp = fopen("php://output", "w");
                            
                            foreach ($rows as $row) {
                                fputcsv($fp, $row);
                            }
                            
                            fclose($fp);
                            
                        }
                        
                    } else {
                        header("Location: ".ENTRADA_URL."/".$MODULE."/".$SUBMODULE);
                    }
                    
                    exit;
                break;
                default:
                    $STEP = 1;
                break;
            }
        break;
    }
    
    switch ($STEP) {
        case "1" :
        default :
            ?>
            <style type="text/css">
                .fixed {
                    position:fixed;
                    top:0px;
                    z-index:10;
                    background-color:#fff;
                }
            </style>
            <style type="text/css" media="print">
                #curriculum-matrix {
                    position:initial;
                    overflow:visible;
                    width:auto;
                }
                #curriculum-matrix .inner {
                    margin:0px;
                }
                #curriculum-matrix-parent {
                    display:none;
                }
                #curriculum-matrix-appendix {
                    display:block !important;
                }
                #curriculum-matrix td.course-cell {
                    white-space: normal;
                    position:initial;
                }
                #curriculum-matrix th.courses-title-cell {
                    position:initial;
                    border-top:0px;
                }
                #curriculum-matrix table {
                    width:100%;
                    table-layout: auto;
                    margin-bottom:0px;
                }
                #csv-btn {
                    display:none;
                }
            </style>
            <script type="text/javascript" src="<?php echo ENTRADA_URL; ?>/javascript/curriculummatrix.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
            <form action="<?php echo ENTRADA_URL; ?>/api/curriculum-matrix.api.php" method="GET" class="form-horizontal" id="curriculum-matrix-parent">
                <?php 
                $objective_sets = Entrada_Curriculum::getObjectiveSets();
                if ($objective_sets) {
                    ?>
                    <div class="control-group">
                        <label class="control-label" for="objective-set">Curriculum Tag Set</label>
                        <div class="controls">
                            <select name="objective_id" id="objective-set">
                                <option value="0">-- Please select a curriculum tag set --</option>
                                <?php 
                                foreach ($objective_sets as $objective_id => $objective_set) {
                                    $depth = Models_Objective::getObjectiveSetDepth($objective_id);
                                    ?>
                                    <option value="<?php echo $objective_id; ?>" <?php echo isset($_SESSION[APPLICATION_IDENTIFIER][$ENTRADA_USER->getActiveID()]["matrix"]["objective_id"]) && $_SESSION[APPLICATION_IDENTIFIER][$ENTRADA_USER->getActiveID()]["matrix"]["objective_id"] == $objective_id ? "selected=\"selected\"" : ""; ?> data-objective-set-depth="<?php echo $depth; ?>"><?php echo $objective_set["objective_name"]; ?> (<?php echo $depth; ?> Levels)</option>
                                    <?php
                                } 
                                ?>
                            </select> 
                            
                            <div id="objective-depth" data-depth="<?php echo isset($_SESSION[APPLICATION_IDENTIFIER][$ENTRADA_USER->getActiveID()]["matrix"]["depth"]) ? $_SESSION[APPLICATION_IDENTIFIER][$ENTRADA_USER->getActiveID()]["matrix"]["depth"] : "1"; ?>"></div>
                            <a href="<?php echo ENTRADA_URL; ?>/<?php echo $MODULE; ?>/<?php echo $SUBMODULE; ?>" class="btn btn-success pull-right hide" id="download-csv"><i class="icon-white icon-file"></i> Download</a>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </form>
            <div id="curriculum-matrix-breadcrumb"></div>
            <div id="curriculum-matrix" class="space-above"></div>
            <div id="curriculum-matrix-appendix"></div>
            <div id="objective-modal" class="modal hide fade">
                <div class="modal-body">
                </div>
                <div class="modal-footer">
                    <a href="#" data-dismiss="modal" class="btn btn-primary pull-right"><?php echo $translate->_("Close"); ?></a>
                </div>
            </div>
            <?php
        break;
    }
}
