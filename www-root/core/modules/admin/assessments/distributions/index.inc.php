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
 * The default file that is loaded when /admin/assessments/distributions is accessed.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
*/
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_DISTRIBUTIONS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
	add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    $distribution = new Models_Assessments_Distribution();

    // Save current path in the session to return to later (from the editor).
    $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["distributions"]["distribution_editor_referrer"] = array(
        "url" => html_encode(ENTRADA_URL . "/admin/assessments/distributions"),
        "from_index" => true,
        "adistribution_id" => 0);
    
    $HEAD[] = "<script type=\"text/javascript\" >var ENTRADA_URL = '". ENTRADA_URL ."';</script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.timepicker.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";
    $HEAD[] = "<script src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.audienceselector.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.audienceselector.css?release=". html_encode(APPLICATION_VERSION) ."\" />";
    $HEAD[] = "<script type=\"text/javascript\" >var internal_assessor_label = '". $translate->_("Internal assessor"). "';</script>";
    $HEAD[] = "<script type=\"text/javascript\" >var external_assessor_label = '". $translate->_("External assessor") ."';</script>";
    $HEAD[] = "<script type=\"text/javascript\" >var individual_author_label = '". $translate->_("Individual") ."';</script>";
    $HEAD[] = "<script type=\"text/javascript\" >var course_author_label = '". $translate->_("Course") ."';</script>";
    $HEAD[] = "<script type=\"text/javascript\" >var organisation_author_label = '". $translate->_("Organisation") ."';</script>";

    $assessments_base = new Entrada_Utilities_Assessments_Base();
    $PREFERENCES = $assessments_base->getAssessmentPreferences($MODULE);

    $PROCESSED["filters"] = array();
    if (isset($PREFERENCES["distributions"]["selected_filters"])) {
        $PROCESSED["filters"] = $PREFERENCES["distributions"]["selected_filters"];
    }

    $assessments_base->updateAssessmentPreferences($MODULE);

    if ($STEP == 2) {

        // Format the posted filters so that we can use them with the advanced search widget.
        foreach (array("author", "course", "organisation", "cperiod") as $filter_type) {
            if (isset($_POST[$filter_type])) {
                $PROCESSED["filters"][$filter_type] = array_filter($_POST[$filter_type], function ($related_id) {
                    return (int)$related_id;
                });
            }
        }

        if (isset($PROCESSED["filters"])) {
            Models_Assessments_Distribution::saveFilterPreferences($PROCESSED["filters"]);
        } else {
            unset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["distributions"]["selected_filters"]);
        }

        $assessments_base->updateAssessmentPreferences($MODULE);

        foreach (array("author", "course", "organisation", "cperiod") as $filter_type) {
            // Cleanup the filters (remove old ones not included in the last post)
            Entrada_Utilities_AdvancedSearchHelper::cleanupSessionFilters($_POST, $MODULE, $SUBMODULE, $filter_type);
        }

        $url = ENTRADA_URL."/admin/assessments/distributions";
        header("Location: " . $url);
    }

    if (isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["distributions"]["selected_filters"]) && !empty($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["distributions"]["selected_filters"])) {
        $sidebar_html = "";
        foreach ($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["distributions"]["selected_filters"] as $key => $filter_type) {
            $sidebar_html .= "<span>". ($key == "cperiod" ? $translate->_("Curriculum Period") : ucwords(str_replace("_", " ", $key))) ." Filters</span>";
            $sidebar_html .= "<ul class=\"menu none\">";
            foreach ($filter_type as $target_id => $target_label) {
                $sidebar_html .= "<li class='remove-single-filter' data-id='$target_id' data-filter='$key'>";
                $sidebar_html .= "<img src='" . ENTRADA_URL . "/images/checkbox-on.gif'/>";
                $sidebar_html .= "<span>". html_encode($target_label) ."</span>";
                $sidebar_html .= "</li>";
            }
            $sidebar_html .= "</ul>";
        }
        $sidebar_html .= "<input type=\"button\" id=\"clear-all-filters\" class=\"btn\" style=\"width: 100%\" value=\"Clear All Filters\"/>";
        new_sidebar_item("Selected Distribution Filters", $sidebar_html, "assessment-filters", "open");
    }

    $distribution_search_term = "";
    $filtered_distributions = $distribution->fetchFilteredDistributions($distribution_search_term, $PROCESSED["filters"]);

    $distribution_methods = array();
    $distribution_methods[] = array("target_id" => "date_range", "target_label" => $translate->_("Date Range"));
    $distribution_methods[] = array("target_id" => "rotation_schedule", "target_label" => $translate->_("Rotation Schedule"));
    $distribution_methods[] = array("target_id" => "delegation", "target_label" => $translate->_("Delegation"));

    $assessment_evaluation_tabs = new Views_Assessments_Dashboard_NavigationTabs();
    $assessment_evaluation_tabs->render(array("active" => "distributions"));
    ?>

    <h1><?php echo $translate->_("Distributions"); ?></h1>

    <script type="text/javascript" src="<?php echo ENTRADA_URL; ?>/javascript/<?php echo $MODULE; ?>/<?php echo $SUBMODULE; ?>/<?php echo $SECTION; ?>.js?release=<?php echo APPLICATION_VERSION; ?>"></script>
    <script type="text/javascript">
        jQuery(function($) {

            $("#advanced-search").advancedSearch({
                api_url : "<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-distributions" ; ?>",
                resource_url: ENTRADA_URL,
                filters : {
                    author : {
                        label : "<?php echo $translate->_("Distribution Authors"); ?>",
                        data_source : "get-distribution-authors"
                    },
                    course : {
                        label : "<?php echo $translate->_("Courses"); ?>",
                        data_source : "get-user-courses"
                    },
                    cperiod : {
                        label : "<?php echo $translate->_("Curriculum Period"); ?>",
                        data_source : "get-user-cperiod"
                    }
                },
                no_results_text: "<?php echo $translate->_("No Distributions found matching the search criteria"); ?>",
                results_parent: $(".assessment-distributions-container"),
                list_selections: false,
                search_target_form_action: "<?php echo ENTRADA_URL . "/admin/assessments/distributions?step=2" ?>",
                width: 400
            });
        });
    </script>
    
    <div class="assessment-distributions-container">
        <form id="distribution-search-form" method="post" action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=delete" ?>">
            <div class="row-fluid space-below">
                <div class="input-append">
                    <input type="text" id="distribution-search" placeholder="<?php echo $translate->_("Search Distributions..."); ?>" <?php if ($distribution_search_term) echo "value=\"$distribution_search_term\""; ?> class="input-large search-icon">
                    <a href="#" id="advanced-search" class="btn" type="button"><i class="icon-chevron-down"></i></a>
                </div>
                <div class="pull-right">
                    <input type="submit" value="<?php echo $translate->_("Delete Distributions"); ?>" class="btn btn-danger space-right">
                    <a id="add-distribution" href="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=form&mode=create" ?>" class="btn btn-success"><i class="icon-plus-sign icon-white"></i> <?php echo $translate->_("Add New Distribution"); ?></a>
                </div>
            </div>

            <div id="distribution-load-error" class="alert alert-block alert-danger hide">
                <button type="button" class="close distribution-load-error-msg">&times;</button>
                <p><?php echo $translate->_("Failed to load distribution data. Please try again."); ?></p>
            </div>
            <div id="assessment-msgs">
                <div id="assessment-distributions-loading" class="hide">
                    <p><?php echo $translate->_("Loading Assessment Distributions..."); ?></p>
                    <img src="<?php echo ENTRADA_URL ."/images/loading.gif" ?>" />
                </div>
            </div>
            <table id="distributions-table" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th width="5%"></th>
                        <th width="25%"><?php echo $translate->_("Title"); ?></th>
                        <th width="20%"><?php echo $translate->_("Course"); ?></th>
                        <th width="25%"><?php echo $translate->_("Curriculum Period"); ?></th>
                        <th width="20%"><?php echo $translate->_("Updated Date"); ?></th>
                        <th width="5%"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($filtered_distributions): ?>
                        <?php foreach ($filtered_distributions as $distribution) { ?>
                            <tr class="data-row">
                                <td><input type="checkbox" name="distributions[]" value="<?php echo $distribution["adistribution_id"]; ?>" /></td>
                                <td><a href="<?php echo ENTRADA_URL . "/admin/assessments/distributions?section=progress&adistribution_id=" .  html_encode($distribution["adistribution_id"]); ?>"><?php echo html_encode($distribution["title"]); ?></a></td>
                                <td><a href="<?php echo ENTRADA_URL . "/admin/assessments/distributions?section=progress&adistribution_id=" .  html_encode($distribution["adistribution_id"]); ?>"><?php echo html_encode($distribution["course_name"]); ?></a></td>
                                <td><a href="<?php echo ENTRADA_URL . "/admin/assessments/distributions?section=progress&adistribution_id=" .  html_encode($distribution["adistribution_id"]); ?>"><?php echo ($distribution["curriculum_period_title"] == null) ? html_encode(date("Y-m-d", $distribution["start_date"]) . " to " . date("Y-m-d", $distribution["finish_date"])) : html_encode($distribution["curriculum_period_title"]); ?></a></td>
                                <td><a href="<?php echo ENTRADA_URL . "/admin/assessments/distributions?section=progress&adistribution_id=" .  html_encode($distribution["adistribution_id"]); ?>"><?php echo (is_null($distribution["updated_date"]) ? "N/A" : date("Y-m-d", $distribution["updated_date"])); ?></a></td>
                                <!--<td><?php echo html_encode($distribution["title"]); ?></td>
                                <td><?php echo html_encode($distribution["course_id"]); ?></td>
                                <td><?php echo (is_null($distribution["updated_date"]) ? "N/A" : date("Y-m-d", $distribution["updated_date"])); ?></td>-->
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-mini dropdown-toggle" title="Distribution Options" data-toggle="dropdown">
                                            <i class="fa fa-cog" aria-hidden="true"></i>
                                        </button>
                                        <ul class="dropdown-menu toggle-left">
                                            <li><a href="<?php echo ENTRADA_URL . "/admin/assessments/distributions?section=form&adistribution_id=" .  html_encode($distribution["adistribution_id"]); ?>" alt="Edit Distribution" class="edit-distribution" data-adistribution-id="<?php echo $distribution["adistribution_id"]; ?>"><?php echo $translate->_("Edit Distribution"); ?></a></li>
                                            <li><a href="<?php echo ENTRADA_URL . "/admin/assessments/distributions?section=form&mode=copy&adistribution_id=" .  html_encode($distribution["adistribution_id"]); ?>" alt="Copy Distribution" class="copy-distribution" data-adistribution-id="<?php echo $distribution["adistribution_id"]; ?>"><?php echo $translate->_("Copy Distribution"); ?></a></li>
                                            <li><a href="<?php echo ENTRADA_URL . "/admin/assessments/distributions?section=progress&adistribution_id=" .  $distribution["adistribution_id"]; ?>" alt="View Distribution Details"><?php echo $translate->_("View Distribution Report"); ?></a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <?php } ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6"><?php echo $translate->_("You currently do not have any distributions in the system. To begin adding distributions, click the Add New Distribution button above."); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php $total_distributions = (int) Models_Assessments_Distribution::countAllDistributions($distribution_search_term, $PROCESSED["filters"]); ?>
            <?php if ($total_distributions <= 50): ?>
                <a href="#" id="load-distributions" class="btn btn-block load-distributions-disabled"><?php echo sprintf($translate->_("Showing %s of %s distributions"), ($filtered_distributions ? count($filtered_distributions) : "0"), ($filtered_distributions ? count($filtered_distributions) : "0")); ?></a>
            <?php else: ?>
                <a href="#" id="load-distributions" class="btn btn-block"><?php echo sprintf($translate->_("Showing %s of %s distributions"), count($filtered_distributions), $total_distributions); ?></a>
            <?php endif; ?>
        </form>
    </div> 
    <?php
    if (isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["distributions"]["selected_filters"]) && !empty($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["distributions"]["selected_filters"])) {
        echo "<form id=\"search-targets-form\" method=\"post\" action=\"". ENTRADA_URL . "/admin/assessments/distributions?step=2\">";
        foreach ($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["distributions"]["selected_filters"] as $key => $filter_type) {
            foreach ($filter_type as $target_id => $target_label) {
                echo "<input id=\"" . html_encode($key) . "_" . html_encode($target_id) . "\" class=\"search-target-control " . html_encode($key) . "_search_target_control\" type=\"hidden\" name=\"" . html_encode($key) . "[]\" value=\"" . html_encode($target_id) . "\" data-id=\"" . html_encode($target_id) . "\" data-filter=\"" . html_encode($key) . "\" data-label=\"" . html_encode($target_label) . "\"/>";
            }
        }
        echo "</form>";
    }
}