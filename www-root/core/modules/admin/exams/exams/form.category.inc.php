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
 * The add / edit category form
 *
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Samuel Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2016 UC Regents. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("ADD_CATEGORY") && !defined("EDIT_CATEGORY"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("exam", "create", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.dataTables.min-1.10.1.js?release=".html_encode(APPLICATION_VERSION).""."\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.timepicker.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.inputselector.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
    $HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/exams/reports/category-admin.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";

    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css?release=".html_encode(APPLICATION_VERSION)."\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.timepicker.css?release=".html_encode(APPLICATION_VERSION)."\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.inputselector.css?release=".html_encode(APPLICATION_VERSION)."\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/exams/reports/category-admin.css?release=".html_encode(APPLICATION_VERSION)."\" />";
    load_rte('examadvanced', array('autogrow' => true, 'divarea' => true));
    switch ($STEP) {
        case 2 :
            // clean variables to insert
            if (isset($_POST["exam_id"]) && $tmp_input = clean_input($_POST["exam_id"], array("trim", "int"))) {
                $PROCESSED["exam_id"] = $tmp_input;
            } else {
                add_error("Exam ID is required");
            }

            if (isset($_POST["post_id"]) && $tmp_input = clean_input($_POST["post_id"], array("trim", "int"))) {
                $PROCESSED["post_id"] = $tmp_input;
            } else {
                add_error("Post ID is required");
            }

            if (isset($_POST["category_id"])) {
                $tmp_input = clean_input($_POST["category_id"], array("trim", "int"));
                if ($tmp_input != 0) {
                    $PROCESSED["category_id"] = $tmp_input;
                }
            }

            if (isset($_POST["use_release_start_date"]) && $tmp_input = clean_input($_POST["use_release_start_date"], array("trim"))) {
                $PROCESSED["use_release_start_date"] = $tmp_input;
            } else {
                $PROCESSED["use_release_start_date"] = 0;
            }

            if (isset($_POST["use_release_end_date"]) && $tmp_input = clean_input($_POST["use_release_end_date"], array("trim"))) {
                $PROCESSED["use_release_end_date"] = $tmp_input;
            } else {
                $PROCESSED["use_release_end_date"] = 0;
            }

            if (isset($_POST["release_start_date"]) && $tmp_input = clean_input($_POST["release_start_date"], array("trim", "strtotime"))) {
                $PROCESSED["release_start_date"] = $tmp_input;
            }

            if (isset($_POST["release_start_time"]) && $tmp_input = clean_input($_POST["release_start_time"], array("trim"))) {
                $PROCESSED["release_start_time"] = $tmp_input;
            }

            if (isset($_POST["release_end_date"]) && $tmp_input = clean_input($_POST["release_end_date"], array("trim", "strtotime"))) {
                $PROCESSED["release_end_date"] = $tmp_input;
            }

            if (isset($_POST["release_end_time"]) && $tmp_input = clean_input($_POST["release_end_time"], array("trim"))) {
                $PROCESSED["release_end_time"] = $tmp_input;
            }

            if (isset($PROCESSED["release_start_date"]) && $PROCESSED["release_start_date"] && isset($PROCESSED["release_start_time"]) && $PROCESSED["release_start_time"]) {
                $time = explode(":", $PROCESSED["release_start_time"]);
                $hours = $time[0] * 60 * 60;
                $minutes = $time[1] * 60;
                $PROCESSED["release_start_date"] += $hours + $minutes;
            } else {
                $PROCESSED["release_start_date"] = 0;
            }

            if (isset($PROCESSED["release_end_date"]) && $PROCESSED["release_end_date"] && isset($PROCESSED["release_end_time"]) && $PROCESSED["release_end_time"]) {
                $time = explode(":", $PROCESSED["release_end_time"]);
                $hours = $time[0] * 60 * 60;
                $minutes = $time[1] * 60;
                $PROCESSED["release_end_date"] += $hours + $minutes;
            } else {
                $PROCESSED["release_end_date"] = 0;
            }

            if ($_POST["tags"] && is_array($_POST["tags"]) && !empty($_POST["tags"])) {
                foreach ($_POST["tags"] as $tag) {
                    if ($tag != 0 ) {
                        $PROCESSED["curriculum_tags"][] = $tag;
                    }
                }
            } else {
                $PROCESSED["curriculum_tags"] = array();
            }

            if ($_POST["learners"] && is_array($_POST["learners"]) && !empty($_POST["learners"])) {
                foreach ($_POST["learners"] as $learner) {
                    if ($learner != 0 ) {
                        $PROCESSED["audience"][] = $learner;
                    }
                }
            } else {
                $PROCESSED["audience"] = array();
            }

            $PROCESSED["updated_date"]  = time();
            $PROCESSED["updated_by"]    = $ENTRADA_USER->getID();

            if (!has_error()) {
                if ($method == "insert") {
                    $exam_category = new Models_Exam_Category($PROCESSED);
                    if ($exam_category->insert()) {

                        if ($PROCESSED["curriculum_tags"] && is_array($PROCESSED["curriculum_tags"]) && !empty($PROCESSED["curriculum_tags"])) {
                            foreach ($PROCESSED["curriculum_tags"] as $tag) {
                                $tag_set = new Models_Exam_Category_Set(array(
                                    "category_id"       => $exam_category->getID(),
                                    "objective_set_id"  => $tag,
                                    "updated_date"      => $PROCESSED["updated_date"],
                                    "updated_by"        => $PROCESSED["updated_by"]
                                ));

                                if (!$tag_set->insert()) {
                                    add_error("Error inserting category audience");
                                    echo display_error();
                                }
                            }
                        }

                        if ($PROCESSED["audience"] && is_array($PROCESSED["audience"]) && !empty($PROCESSED["audience"])) {
                            foreach ($PROCESSED["audience"] as $audience) {
                                $exam_audience = new Models_Exam_Category_Audience(array(
                                    "category_id"   => $exam_category->getID(),
                                    "proxy_id"      => $audience,
                                    "updated_date"  => $PROCESSED["updated_date"],
                                    "updated_by"    => $PROCESSED["updated_by"]
                                ));

                                if (!$exam_audience->insert()) {
                                    add_error("Error inserting category audience");
                                    echo display_error();
                                }
                            }
                        }

                        if (!has_error()) {
                            $url = "/admin/exams/exams?section=reports&id=" . $exam->getID();
                            add_success("Successfully Added a Category Report. In 5 seconds your browser will auto forwarded or you can click <a href=\"" . $url . "\">here</a> ");
                        }

                    } else {
                        add_error("Error Creating the Exam Category Report.");
                        echo display_error();
                    }
                } else {
                    // update
                    $exam_category = Models_Exam_Category::fetchRowByID($PROCESSED["category_id"]);
                    if ($exam_category) {
                        if ($PROCESSED["use_release_start_date"]) {
                            $exam_category->setUseReleaseStartDate(1);
                        } else {
                            $exam_category->setUseReleaseStartDate(0);
                        }

                        if ($PROCESSED["use_release_end_date"]) {
                            $exam_category->setUseReleaseEndDate(1);
                        } else {
                            $exam_category->setUseReleaseEndDate(0);
                        }

                        if ($PROCESSED["release_start_date"]) {
                            $exam_category->setReleaseStartDate($PROCESSED["release_start_date"]);
                        } else {
                            $exam_category->setReleaseStartDate("");
                        }

                        if ($PROCESSED["release_end_date"]) {
                            $exam_category->setReleaseEndDate($PROCESSED["release_end_date"]);
                        } else {
                            $exam_category->setReleaseEndDate("");
                        }

                        $exam_category->setUpdatedBy($ENTRADA_USER->getID());
                        $exam_category->setUpdatedDate(time());

                        if ($exam_category->update()) {
                            $curriculum_array   = array();
                            $curriculum_sets    = Models_Exam_Category_Set::fetchAllByCategoryID($PROCESSED["category_id"]);

                            if ($curriculum_sets && is_array($curriculum_sets) && !empty($curriculum_sets)) {
                                foreach ($curriculum_sets as $set) {
                                    if (!in_array($set->getObjectiveSetId(), $curriculum_array)) {
                                        $curriculum_array[] = $set->getObjectiveSetId();
                                    }
                                }
                            }

                            if ($curriculum_array && is_array($curriculum_array) && !empty($curriculum_array)) {
                                $old_set = true;
                            }

                            if ($PROCESSED["curriculum_tags"] && is_array($PROCESSED["curriculum_tags"]) && !empty($PROCESSED["curriculum_tags"])) {
                                $new_set = true;
                            }

                            if ($old_set && $new_set) {
                                $remove_set         = array_diff($curriculum_array, $PROCESSED["curriculum_tags"]);
                                $add_set            = array_diff($PROCESSED["curriculum_tags"], $curriculum_array);
                            } elseif ($old_set && !$new_set){
                                $remove_set = $curriculum_array;
                            } else {
                                $add_set            = $PROCESSED["curriculum_tags"];
                            }

                            if ($remove_set && is_array($remove_set) && !empty($remove_set)) {
                                foreach ($remove_set as $set) {
                                    $curriculum_set = Models_Exam_Category_Set::fetchRowByCategoryIdObjectiveId($PROCESSED["category_id"], $set);
                                    if ($curriculum_set && is_object($curriculum_set)) {
                                        $curriculum_set->setDeletedDate($PROCESSED["updated_date"]);
                                        $curriculum_set->setUpdatedDate($PROCESSED["updated_date"]);
                                        $curriculum_set->setUpdatedBy($ENTRADA_USER->getID());
                                        if (!$curriculum_set->update()) {
                                            add_error("Error removing curriculum set from category report.");
                                        }
                                    }
                                }
                            }

                            if ($add_set && is_array($add_set) && !empty($add_set)) {
                                foreach ($add_set as $set) {
                                    $curriculum_set =  new Models_Exam_Category_Set(array(
                                        "objective_set_id"  => $set,
                                        "category_id"       => $PROCESSED["category_id"],
                                        "updated_date"      => $PROCESSED["updated_date"],
                                        "updated_by"        => $PROCESSED["updated_by"]
                                    ));

                                    if (!$curriculum_set->insert()) {
                                        add_error("Error adding category set for category report.");
                                    }
                                }
                            }

                            $audience_array     = array();
                            $category_audience  = Models_Exam_Category_Audience::fetchAllByCategoryID($PROCESSED["category_id"]);

                            if ($category_audience && is_array($category_audience) && !empty($category_audience)) {
                                foreach ($category_audience as $audience) {
                                    if (!in_array($audience->getProxyID(), $audience_array)) {
                                        $audience_array[] = $audience->getProxyID();
                                    }
                                }
                            }

                            if ($audience_array && is_array($audience_array) && !empty($audience_array)) {
                                $old_audience = true;
                            }

                            if ($PROCESSED["audience"] && is_array($PROCESSED["audience"]) && !empty($PROCESSED["audience"])) {
                                $new_audience = true;
                            }

                            if ($old_audience && $new_audience) {
                                $remove_audience    = array_diff($audience_array, $PROCESSED["audience"]);
                                $add_audience       = array_diff($PROCESSED["audience"], $audience_array);
                            } elseif ($old_audience && !$new_audience){
                                $remove_audience = $audience_array;

                            } else {
                                $add_audience = $PROCESSED["audience"];
                            }

                            if ($remove_audience && is_array($remove_audience) && !empty($remove_audience)) {
                                foreach ($remove_audience as $proxy_id) {
                                    $audience = Models_Exam_Category_Audience::fetchRowByCategoryIdProxyId($PROCESSED["category_id"], $proxy_id);
                                    if ($audience && is_object($audience)) {
                                        $audience->setDeletedDate($PROCESSED["updated_date"]);
                                        $audience->setUpdatedDate($PROCESSED["updated_date"]);
                                        $audience->setUpdatedBy($ENTRADA_USER->getID());
                                        if (!$audience->update()) {
                                            add_error("Error removing access for an audience member.");
                                        }
                                    }
                                }
                            }

                            if ($add_audience && is_array($add_audience) && !empty($add_audience)) {
                                foreach ($add_audience as $proxy_id) {
                                    $audience = new Models_Exam_Category_Audience(array(
                                        "category_id"   => $PROCESSED["category_id"],
                                        "proxy_id"      => $proxy_id,
                                        "updated_date"  => $PROCESSED["updated_date"],
                                        "updated_by"    => $PROCESSED["updated_by"]
                                    ));

                                    if (!$audience->insert()) {
                                        add_error("Error adding access for an audience member.");
                                    }
                                }
                            }

                            if (!has_error()) {
                                $url = ENTRADA_URL . "/admin/exams/exams?section=reports&id=" . $exam->getID();
                                add_success("Successfully Updated a Category Report. In 5 seconds your browser will auto forwarded or you can click <a href=\"" . $url . "\">here</a> ");
                            }
                        }
                    }
                }

            } else {
                echo display_error();
                $STEP = 1;
            }

            break;
    }

    switch ($STEP) {
    case 2 :
        if (has_success()) {
            echo display_success();
            $url = ENTRADA_URL . "/admin/exams/exams?section=category&id=" . $exam->getID();
            $ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
        }
        if (has_error()) {
            echo display_error();
        }
        break;
    case 1 :

        $flash_messages = Entrada_Utilities_Flashmessenger::getMessages($MODULE);
        if ($flash_messages) {
            foreach ($flash_messages as $message_type => $messages) {
                switch ($message_type) {
                    case "error" :
                        echo display_error($messages);
                        break;
                    case "success" :
                        echo display_success($messages);
                        break;
                    case "notice" :
                    default :
                        echo display_notice($messages);
                        break;
                }
            }
        }

        if ($PROCESSED["category_id"]) {
            $curriculum_set    = Models_Exam_Category_Set::fetchAllByCategoryID($PROCESSED["category_id"]);
            $category_audience = Models_Exam_Category_Audience::fetchAllByCategoryID($PROCESSED["category_id"]);
        }

        if (has_success()) {
            echo display_success();
        }
        if (has_error()) {
            echo display_error();
            echo display_success();
        }
        ?>
        <script type="text/javascript">
            var ENTRADA_URL = "<?php echo ENTRADA_URL; ?>";
            var API_URL = "<?php echo ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-exams"; ?>";
            var submodule_text = JSON.parse('<?php echo json_encode($SUBMODULE_TEXT); ?>');
            var default_text_labels = JSON.parse('<?php echo json_encode($DEFAULT_TEXT_LABELS); ?>');
            var post_id = "<?php echo $PROCESSED["post_id"]?>";
        </script>
        <div id="category-report-admin">
            <h1 id="exam_title"><?php echo $exam->getTitle(); ?></h1>
            <?php
            $exam_view = new Views_Exam_Exam($exam);
            echo $exam_view->examNavigationTabs($SECTION);
            ?>
            <h2><?php echo $SECTION_TEXT["title"]; ?></h2>

            <div id="msgs"></div>
            <form id="category-form"
                  action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=" . $SECTION . "&id=" . ($method == "update" ? $PROCESSED["category_id"] : $PROCESSED["post_id"]); ?>"
                  data-post-id="<?php echo $PROCESSED["post_id"]; ?>" class="form-horizontal" method="POST">
                <input type="hidden" name="step" value="2"/>
                <input type="hidden" name="exam_id" value="<?php echo $PROCESSED["exam_id"]; ?>" id="exam_id"/>
                <input type="hidden" name="post_id" value="<?php echo $PROCESSED["post_id"]; ?>" id="post_id"/>
                <input type="hidden" name="category_id" value="<?php echo $PROCESSED["category_id"]; ?>" id="category_id"/>

                <div class="control-group">
                    <label for="use_release_start_date" class="control-label form-nrequired">
                        <?php echo $SECTION_TEXT["release_start_date"]; ?>
                    </label>

                    <div class="controls">
                        <div class="input-append space-right">
                            <input id="use_release_start_date" class="use_date" type="checkbox"
                                   name="use_release_start_date" data-date-name="release_start_date"
                                   data-time-name="release_start_time"
                                   value="1"<?php echo ($category && $category->getUseReleaseStartDate() == "1") ? " checked=\"checked\"" : ""; ?>/>
                        </div>
                        <div class="input-append space-right">
                            <input id="release_start_date" type="text" class="input-small datepicker"
                                   value="<?php echo ($category && ($category->getReleaseStartDate() || $category->getReleaseStartDate() != 0) != "" && $category->getUseReleaseStartDate() != "") ? date("Y-m-d", $category->getReleaseStartDate()) : ""; ?>"
                                   name="release_start_date"<?php echo (!$category || $category->getUseReleaseStartDate() != "1") ? " disabled=\"disabled\"" : ""; ?> />
                                <span class="add-on pointer">
                                    <i class="icon-calendar"></i>
                                </span>
                        </div>
                        <div class="input-append">
                            <input id="release_start_time" type="text" class="input-mini timepicker"
                                   value="<?php echo ($category && ($category->getReleaseStartDate() || $category->getReleaseStartDate() != 0) != "" && $category->getUseReleaseStartDate() != "") ? date("H:i", $category->getReleaseStartDate()) : ""; ?>"
                                   name="release_start_time"<?php echo (!$category || $category->getUseReleaseStartDate() != "1") ? " disabled=\"disabled\"" : ""; ?>/>
                                <span class="add-on pointer">
                                    <i class="icon-time"></i>
                                </span>
                        </div>
                    </div>
                </div>
                <div class="control-group">
                    <label for="use_release_end_date" class="control-label form-nrequired">
                        <?php echo $SECTION_TEXT["release_end_date"]; ?>
                    </label>

                    <div class="controls">
                        <div class="input-append space-right">
                            <input id="use_release_end_date" class="use_date" type="checkbox"
                                   name="use_release_end_date" data-date-name="release_end_date"
                                   data-time-name="release_end_time"
                                   value="1" <?php echo ($category && $category->getUseReleaseEndDate() == "1") ? " checked=\"checked\"" : ""; ?>/>
                        </div>
                        <div class="input-append space-right">
                            <input id="release_end_date" type="text" class="input-small datepicker"
                                   value="<?php echo ($category && ($category->getReleaseEndDate() != "" && $category->getReleaseEndDate() != 0) != "" && $category->getUseReleaseEndDate() != "") ? date("Y-m-d", $category->getReleaseEndDate()) : ""; ?>"
                                   name="release_end_date"<?php echo (!$category || $category->getUseReleaseEndDate() != "1") ? " disabled=\"disabled\"" : ""; ?> />
                                <span class="add-on pointer">
                                    <i class="icon-calendar"></i>
                                </span>
                        </div>
                        <div class="input-append">
                            <input id="release_end_time" type="text" class="input-mini timepicker"
                                   value="<?php echo ($category && ($category->getReleaseEndDate() != "" && $category->getReleaseEndDate() != 0) && $category->getUseReleaseEndDate() != "") ? date("H:i", $category->getReleaseEndDate()) : ""; ?>"
                                   name="release_end_time"<?php echo (!$category || $category->getUseReleaseEndDate() != "1") ? " disabled=\"disabled\"" : ""; ?> />
                                <span class="add-on pointer">
                                    <i class="icon-time"></i>
                                </span>
                        </div>
                    </div>
                </div>

                <h2><?php echo $SECTION_TEXT["curriculum_tag_name"]; ?></h2>

                <div class="control-group">
                    <label for="sets-btn" class="control-label form-required">
                        <?php echo $SECTION_TEXT["browse_sets"] ?>
                    </label>

                    <div class="controls entrada-search-widget" id="sets-btn-advancedsearch">
                        <button id="sets-btn" class="btn btn-search-filter" type="button">
                            <?php echo $SECTION_TEXT["browse_sets"] ?>
                            <i class="icon-chevron-down btn-icon pull-right"></i>
                        </button>
                    </div>
                </div>
                <div id="curriculum-tag-container" class="entrada-search-list">
                    <?php
                    if ($curriculum_set && is_array($curriculum_set) && !empty($curriculum_set)) {
                        foreach ($curriculum_set as $set) {
                            $tag = Models_Objective::fetchRow($set->getObjectiveSetId());
                            ?>
                            <div id="tags-list-container">
                                <ul id="tags-list">
                                    <li id="tags-list-<?php echo $tag->getID();?>" class="selected-list-item" data-id="<?php echo $tag->getID();?>" data-parent="" data-filter="tags">
                                        <?php echo $tag->getName();?>
                                        <span class="pull-right selected-item-container">
                                        <span class="selected-item-label">Curriculum Tag Set</span>
                                        <span class="remove-list-item">×</span>
                                    </span>
                                    </li>
                                </ul>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>

                <h2><?php echo $SECTION_TEXT["audience_list"]; ?></h2>

                <div class="control-group">
                    <label for="audience-btn" class="control-label form-required">
                        <?php echo $SECTION_TEXT["browse_audience"] ?>
                    </label>

                    <div class="controls entrada-search-widget" id="audience-btn-advancedsearch">
                        <button id="audience-btn" class="btn btn-search-filter" type="button">
                            <?php echo $SECTION_TEXT["browse_audience"] ?>
                            <i class="icon-chevron-down btn-icon pull-right"></i>
                        </button>
                    </div>
                </div>

                <div id="audience-container" class="entrada-search-list">
                    <?php
                    if ($category_audience && is_array($category_audience) && !empty($category_audience)) {
                        ?>
                        <div id="learners-list-container">
                            <ul id="learners-list">
                        <?php
                        foreach ($category_audience as $audience) {
                            $learner = Models_User::fetchRowByID($audience->getProxyID());
                            ?>
                                <li id="learners-list-<?php echo $audience->getProxyID();?>" class="selected-list-item" data-id="<?php echo $audience->getProxyID();?>" data-parent="" data-filter="learners">
                                    <?php echo $learner->getName("%l %f");?>
                                    <span class="pull-right selected-item-container">
                                        <span class="selected-item-label">Learners</span>
                                        <span class="remove-list-item">×</span>
                                    </span>
                                </li>
                            <?php
                            }
                            ?>
                            </ul>
                        </div>
                        <?php
                    }
                    ?>
                </div>

                <?php
                if ($curriculum_set && is_array($curriculum_set) && !empty($curriculum_set)) {
                    foreach ($curriculum_set as $set) {
                        $tag = Models_Objective::fetchRow($set->getObjectiveSetId());
                        ?>
                        <input
                            id="tags_<?php echo $tag->getID();?>"
                            class="search-target-control tags_search_target_control"
                            name="tags[]"
                            value="<?php echo $tag->getID();?>"
                            data-id="<?php echo $tag->getID();?>"
                            data-label="<?php echo $tag->getName();?>"
                            data-filter="tags"
                            type="hidden">
                        <?php
                    }
                }

                if ($category_audience && is_array($category_audience) && !empty($category_audience)) {
                    foreach ($category_audience as $audience) {
                        $learner = Models_User::fetchRowByID($audience->getProxyID());
                        ?>
                        <input
                            id="learners_<?php echo $audience->getProxyID();?>"
                            class="search-target-control learners_search_target_control"
                            name="learners[]"
                            value="<?php echo $audience->getProxyID();?>"
                            data-id="<?php echo $audience->getProxyID();?>"
                            data-label="<?php echo $learner->getName("%l %f");?>"
                            data-filter="learners"
                            type="hidden">
                        <?php
                    }
                }
                ?>
                <div class="row-fluid">
                    <button id="cancel-dropdown-exception" class="btn btn-default pull-left">
                        <?php echo $DEFAULT_LABELS["btn_cancel"]; ?>
                    </button>
                    <button id="update-dropdown-exception" class="btn btn-primary pull-right">
                        <?php echo $DEFAULT_LABELS["btn_save"]; ?>
                    </button>
                </div>
            </form>
        </div>
        <script>
            jQuery(function ($) {
                $("#audience-btn").advancedSearch({
                    api_url: API_URL,
                    resource_url: ENTRADA_URL,
                    filter_component_label: "Learners",
                    filters: {
                        learners: {
                            label: "<?php echo $SECTION_TEXT["audience"];?>",
                            data_source: "get-category-report-audience",
                            mode: "checkbox",
                            select_all_enabled: true,
                            api_params: {
                                post_ids: function () {
                                    var post_id = $("input[name=\"post_id\"]");
                                    return post_id.val();
                                }
                            }
                        }
                    },
                    control_class: "audience-selector",
                    no_results_text: "<?php echo $SECTION_TEXT["no_learners"];?>",
                    parent_form: $("#category-form"),
                    list_data: {
                        selector: "#audience-container",
                        background_value: "url(../../images/list-community.gif) no-repeat scroll 0 4px transparent"
                    },
                    width: 500,
                    modal: false
                });

                $("#sets-btn").advancedSearch({
                    api_url: API_URL,
                    resource_url: ENTRADA_URL,
                    filter_component_label: "Curriculum Tags",
                    filters: {
                        tags: {
                            label: "<?php echo $SECTION_TEXT["tag"];?>",
                            data_source: "get-category-report-sets",
                            mode: "checkbox",
                            select_all_enabled: true,
                            api_params: {
                                exam_id: function () {
                                    var exam_id = $("input[name=\"exam_id\"]");
                                    return exam_id.val();
                                }
                            }
                        }
                    },
                    control_class: "curriculum-selector",
                    no_results_text: "<?php echo $SECTION_TEXT["no_tags"];?>",
                    parent_form: $("#category-form"),
                    list_data: {
                        selector: "#curriculum-tag-container",
                        background_value: "url(../../images/list-community.gif) no-repeat scroll 0 4px transparent"
                    },
                    width: 500,
                    modal: false
                });
            });
        </script>
        <?php
        break;
        default:
    }
}