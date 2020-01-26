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
 * @author Developer: Matt Simpson <simpson@queensu.ca>
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

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	/**
	 * Meta information for this page.
	 */
	$PAGE_META["title"] = $translate->_("Curriculum Search");
	$PAGE_META["description"] = "Allowing you to search the curriculum for specific key words and events.";
	$PAGE_META["keywords"] = "";

	$BREADCRUMB[] = array("url" => ENTRADA_URL."/curriculum/search", "title" => "Search");

	$SEARCH_QUERY = "";
	$SEARCH_MODE = "standard";
	$SEARCH_CLASS = 0;
	$SEARCH_YEAR = 0;
	$SEARCH_DURATION = array();
	$SEARCH_ORGANISATION = $ENTRADA_USER->getActiveOrganisation();
	$RESULTS_PER_PAGE = 25;

	$objective_repository = Models_Repository_Objectives::getInstance();

	/**
	 * The query that is actually be searched for.
	 */
	if (isset($_GET["q"]) && ($tmp_input = clean_input($_GET["q"]))) {
		$SEARCH_QUERY = $tmp_input;
	}

	/**
	 * The mode that results are displayed in.
	 */
	if (isset($_GET["m"]) && (trim($_GET["m"]) == "timeline")) {
		$SEARCH_MODE = "timeline";
	}

	if (isset($_GET["m"]) && (trim($_GET["m"]) == "table")) {
		$SEARCH_MODE = "table";
	}

	if (isset($_GET["m"]) && (trim($_GET["m"]) == "csv")) {
		$SEARCH_MODE = "csv";
	}

	if (isset($_GET["m"]) && (trim($_GET["m"]) == "csv_grouped")) {
		$SEARCH_MODE = "csv_grouped";
	}

	$COURSE_LIST = array();

	$results = courses_fetch_courses(true, true);
	if ($results) {
		foreach ($results as $result) {
			$COURSE_LIST[$result["course_id"]] = html_encode(($result["course_code"] ? $result["course_code"] . ": " : "") . $result["course_name"]);
		}
	}

	$UNIT_LIST = array();
	$units = Models_Course_Unit::fetchMyUnits();

	if ($units):
		foreach ($units as $curriculum_type_name => $units_by_week):
			foreach ($units_by_week as $week_title => $units_by_course):
				if (count($units_by_course) == 1):
					$course_code = key($units_by_course);
					$unit = current($units_by_course);
					$UNIT_LIST[$unit->getID()] = $week_title;
				else:
					foreach ($units_by_course as $course_code => $unit):
						$UNIT_LIST[$unit->getID()] = html_encode($unit->getUnitText()).(($unit->getUnitCode() == "") ? " (".html_encode($course_code).")" : "");
					endforeach;
				endif;
			endforeach;
		endforeach;
	endif;

	/**
	 * Determine what are the selected tag sets, if any.
	 */
	$tag_set_ids = array();
	if ((isset($_GET["tag_set_ids"])) && (is_array($_GET["tag_set_ids"]))) {
		foreach ($_GET["tag_set_ids"] as $tag_set_id) {
			if ($tag_set_id = (int) $tag_set_id) {
				$tag_set_ids[] = $tag_set_id;
			}
		}
	}

	$tag_set_results = $objective_repository->toArrays($objective_repository->fetchTagSetsByOrganisationID($ENTRADA_USER->getActiveOrganisation()));
	$tag_sets = array();
	foreach ($tag_set_results as $tag_set) {
		$tag_sets[$tag_set["objective_id"]] = $tag_set;
	}

	/**
	 * Determine filter tag ID's
	 */
	if (isset($_GET["filter_tag"]) && is_array($_GET["filter_tag"])) {
		$filter_tag_ids = array_map(function ($filter_tag_id) { return (int) $filter_tag_id; }, $_GET["filter_tag"]);
	} else {
		$filter_tag_ids = array();
	}
	$filter_tags = $objective_repository->fetchAllByIDs($filter_tag_ids);

	/**
	 * Determine search filters
	 */
	$search_filters = array();
	if ((isset($_GET["search_filters"])) && (is_array($_GET["search_filters"]))) {
		foreach ($_GET["search_filters"] as $search_filter_field => $search_filter) {
			if (is_array($search_filter)) {
				foreach ($search_filter as $search_filter_operator => $search_filter_values) {
					if (is_array($search_filter_values)) {
						foreach ($search_filter_values as $search_filter_value) {
							$tmp_input_field = clean_input($search_filter_field, array("strip", "notags"));
							$tmp_input_operator = clean_input($search_filter_operator, array("strip", "notags"));
							$tmp_input_value = clean_input($search_filter_value, array("strip", "notags"));
							$search_filters[$tmp_input_field][$tmp_input_operator][] = $tmp_input_value;
						}
					}
				}
			}
		}
	}

	/**
	 * Override replace_query() function to handle list of tag set ID's
	 * @param array
	 * @return string
	 */
	$replace_query = function ($modify) use ($tag_set_ids) {
		$query = replace_query($modify);
		$query = preg_replace("/\&tag_set_ids.*?\=\d+/", "", $query);
		foreach ($tag_set_ids as $tag_set_id) {
			$query .= "&tag_set_ids[]={$tag_set_id}";
		}
		return $query;
	};

	/**
	 * Check if course_select variable is set for Course.
	 */
	if (isset($_GET["course_select"]) && ($tmp_input = clean_input($_GET["course_select"], array("nows", "int")))) {
		$SEARCH_COURSE = $tmp_input;
	}

	/**
	 * Check if unit_select variable is set for Course.
	 */
	if (isset($_GET["unit_select"]) && ($tmp_input = clean_input($_GET["unit_select"], array("nows", "int")))) {
		$SEARCH_UNIT = $tmp_input;
	}

	if ($SEARCH_QUERY || !empty($filter_tag_ids) || !empty($search_filters) || !empty($SEARCH_COURSE) || !empty($SEARCH_UNIT)) {
		/**
		 * Check if c variable is set for Class of.
		 */
		if (isset($_GET["c"]) && ($tmp_input = clean_input($_GET["c"], array("nows", "int")))) {
			$SEARCH_CLASS = $tmp_input;
		}

		/**
		 * Check if o variable is set for Organisation
		 */
		if (isset($_GET["o"]) && ($tmp_input = clean_input($_GET["o"], array("nows", "int")))) {
			$SEARCH_ORGANISATION = $tmp_input;
		}

		/**
		 * Check if y variable is set for Academic year.
		 */
		if (isset($_GET["y"]) && ($tmp_input = clean_input($_GET["y"], array("nows", "int")))) {
			$SEARCH_YEAR = $tmp_input;
		}

		if (isset($_GET["sort"]) && ($tmp_input = clean_input($_GET["sort"]))) {
			$SEARCH_SORT_ORDER = $tmp_input;
		}

		if ($SEARCH_MODE != "timeline") {
			if ($SEARCH_MODE == "csv" || $SEARCH_MODE == "csv_grouped") {
				$add_limit_param = false;
			} else {
				$add_limit_param = true;
			}

			$queries = Entrada_Curriculum_Search::prepare($SEARCH_QUERY, $SEARCH_ORGANISATION, $SEARCH_CLASS, $SEARCH_YEAR, true, $add_limit_param, $search_filters, $filter_tag_ids, $SEARCH_COURSE, $SEARCH_UNIT, $SEARCH_SORT_ORDER);

			$query_counter = $queries["counter"];
			$query_search = $queries["search"];

			/**
			 * Get the total number of results using the generated queries above and calculate the total number
			 * of pages that are available based on the results per page preferences.
			 */
			$result = ((USE_CACHE) ? $db->CacheGetRow(CACHE_TIMEOUT, $query_counter) : $db->GetRow($query_counter));

			if ($result) {
				$TOTAL_ROWS	= $result["total_rows"];

				if ($TOTAL_ROWS <= $RESULTS_PER_PAGE) {
					$TOTAL_PAGES = 1;
				} elseif (($TOTAL_ROWS % $RESULTS_PER_PAGE) == 0) {
					$TOTAL_PAGES = (int) ($TOTAL_ROWS / $RESULTS_PER_PAGE);
				} else {
					$TOTAL_PAGES = (int) ($TOTAL_ROWS / $RESULTS_PER_PAGE) + 1;
				}
			} else {
				$TOTAL_ROWS	= 0;
				$TOTAL_PAGES = 1;
			}

			/**
			 * Check if pv variable is set and see if it's a valid page, other wise page 1 it is.
			 */
			if (isset($_GET["pv"])) {
				$PAGE_CURRENT = (int) trim($_GET["pv"]);

				if (($PAGE_CURRENT < 1) || ($PAGE_CURRENT > $TOTAL_PAGES)) {
					$PAGE_CURRENT = 1;
				}
			} else {
				$PAGE_CURRENT = 1;
			}

			$PAGE_PREVIOUS = (($PAGE_CURRENT > 1) ? ($PAGE_CURRENT - 1) : false);
			$PAGE_NEXT = (($PAGE_CURRENT < $TOTAL_PAGES) ? ($PAGE_CURRENT + 1) : false);
		}
	}
	search_subnavigation("search");
	?>

	<h1><?php echo $translate->_("Curriculum Search"); ?></h1>

	<form id="search-form" name="search-form" action="<?php echo ENTRADA_RELATIVE; ?>/curriculum/search" method="get" class="form-horizontal">
		<?php
		if ($SEARCH_MODE == "timeline") {
			echo "<input type='hidden' name='m' id='m' value='timeline' />\n";
		} else if ($SEARCH_MODE == "table") {
			echo "<input type='hidden' name='m' id='m' value='table' />\n";
		} else {
			echo "<input type='hidden' name='m' id='m' value='text' />\n";
		}

		if ($SEARCH_MODE == "table") {
			echo "<input type=\"hidden\" id=\"sort\" name=\"sort\" value=\"\" />\n";
		}
		?>
        <div class="control-group space-below">
            <label class="control-label"><?php echo $translate->_("Boolean Search Term"); ?></label>
            <div class="controls">
                <div class="input-append">
                    <input type="text" class="input-xlarge" id="q" name="q" value="<?php echo html_encode($SEARCH_QUERY); ?>" />
                    <button type="submit" class="btn">
                        <i class="icon-search"></i> <?php echo $translate->_("Search"); ?>
                    </button>
                </div>

                <button type="button" class="btn show" data-toggle="collapse" data-target="#advanced-search">
                    <i class="fa fa-cog" aria-hidden="true"></i> <?php echo $translate->_("Advanced Options"); ?>
                </button>

                <?php if ($SEARCH_QUERY) : ?>
                <a href="<?php echo ENTRADA_RELATIVE; ?>/curriculum/search" class="btn"><i class="fa fa-refresh" aria-hidden="true"></i> <?php echo $translate->_("Reset"); ?></a>
                <?php endif; ?>

                <div class="space-above">
                    <small><?php echo $translate->_("Search Tip"); ?>: </small><a data-toggle="collapse" href="#search-operator-instructions"><small><?php echo $translate->_("You can refine your search using boolean operators -, &quot;quotes&quot;, OR"); ?></small></a>

                    <style>
                        #search-form .controls button.show {
                            display: inline-block;
                        }
                        .timeline-band-layer-inner td {
                            font-size: 30px;
                        }
                    </style>
                    <div id="search-operator-instructions" class="collapse">
                        <h3>-</h3>
                        <p>
                            <?php echo $translate->_("Use the - character to remove a specific word from search results. For example (asthma -paediatric) will return all results related to asthma excluding the word paediatric."); ?>
                        </p>
                        <h3>&quot;<?php echo $translate->_("quoted term"); ?>&quot;</h3>
                        <p>
                            <?php echo $translate->_("Use quotes around your search term to search for the exact text. For example (&quot;pediatric asthma&quot;) will return results that contain exactly pediatric asthma."); ?>
                        </p>
                        <h3>OR</h3>
                        <p>
                            <?php echo $translate->_("Use this to search for multiple queries at a time. For example  (asthma OR paediatric) will return all results for either asthma or paediatric."); ?>
                        </p>
                    </div>
                </div>
			</div>
		</div>
		<!-- advanced search -->
		<div id="advanced-search" class="collapse">
            <div class="control-group">
                <div class="controls">
                    <label class="checkbox">
                        <input type="checkbox" id="search-in-files"<?php echo (isset($_GET["search-in-files"]) ? " checked=\"checked\"" : ""); ?> name="search-in-files"> Search within files attached to Learning Events
                    </label>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label"><?php echo $translate->_("Cohort"); ?></label>
                <div class="controls">
                    <select id="c" name="c">
                        <option value="0"<?php echo ((!$SEARCH_CLASS) ? " selected=\"selected\"" : ""); ?>>-- <?php echo $translate->_("All Cohorts"); ?> --</option>
                        <?php
                        $cohorts = groups_get_all_cohorts($ENTRADA_USER->getActiveOrganisation(), true);
                        foreach ($cohorts as $cohort) {
                            echo "<option value=\"".$cohort["group_id"]."\"".(($SEARCH_CLASS == $cohort["group_id"]) ? " selected=\"selected\"" : "").">".html_encode($cohort["group_name"])."</option>\n";
                        }
                        ?>
                    </select>
                </div>
            </div> <!--/control-group-->
            <div class="control-group">
                <label class="control-label"><?php echo $translate->_("Academic Year"); ?></label>
                <div class="controls">
                    <select id="y" name="y" <?php echo (($SEARCH_MODE == "timeline") ? " disabled=\"disabled\"" : ""); ?>>
                        <option value="0"<?php echo ((!$SEARCH_YEAR)? " selected=\"selected\"" : ""); ?>>-- <?php echo $translate->_("All Years"); ?> --</option>
                        <?php
                        $start_year = (fetch_first_year() - 3);
                        for ($year = $start_year; $year >= ($start_year - 3); $year--) {
                            echo "<option value=\"".$year."\"".(($SEARCH_YEAR == $year) ? " selected=\"selected\"" : "").">".$year."/".($year + 1)."</option>\n";
                        }
                        ?>
                    </select>
                </div>
            </div> <!--/control-group-->
            <div class="control-group">
                <label class="control-label"><?php echo $translate->_("course"); ?></label>
                <div class="controls">
                    <select id="course_select" name="course_select">
                        <option value="">-- <?php echo $translate->_("Select a course"); ?> --</option>
                        <?php
                        foreach ($COURSE_LIST as $key => $course_name) {
                            echo "<option value=\"".$key."\"".(($key == $SEARCH_COURSE) ? " selected=\"selected\"" : "").">".$course_name."</option>\n";
                        }
                        ?>
                    </select>
                </div>
            </div> <!--/control-group-->
            <?php if ($UNIT_LIST) : ?>
            <div class="control-group">
                <label class="control-label"><?php echo $translate->_("Weeks"); ?></label>
                <div class="controls">
					<select id="unit_select" name="unit_select"></select>
				</div>
			</div> <!--/control-group-->
            <?php endif; ?>
            <?php
            $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js\"></script>";
            $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";
            $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/picklist.js\"></script>\n";
            if ($SEARCH_MODE == "table") {
                Views_UI_PickList::render(
                        "tag_set",
                        "tag_set_ids",
                        "Tag Set(s) to Add:",
                        $tag_sets,
                        $tag_set_ids,
                        function ($tag_set) {
                            return $tag_set["objective_name"];
                        });
            }
            ?>
            <div class="control-group">
                <label for="filter_tag_button" class="control-label"><?php echo $translate->_("Filter Tags"); ?></label>
                <div class="controls">
                    <button id="filter_tag_button" type="button" class="btn btn-search-filter input-xlarge"><span id="filter_tag_button_text"><?php echo $translate->_("Select Curriculum Tags to Filter By"); ?></span> <i class="icon-chevron-down btn-icon pull-right"></i></button>
                </div>
            </div>
            <script>
                jQuery(function() {
                    jQuery("#filter_tag_button").advancedSearch({
                        resource_url: '<?php echo ENTRADA_URL; ?>',
                        api_url: '<?php echo ENTRADA_URL; ?>/api/curriculum-tags.api.php',
                        filters: {
                            filter_tag: {
                                label: '<?php echo $translate->_("Curriculum Tags"); ?>',
                                data_source: 'get-objectives',
                                secondary_data_source: 'get-objectives',
                                mode: 'checkbox',
                                set_button_text_to_selected_option: true
                            }
                        },
                        no_results_text: "<?php echo $translate->_("No Curriculum Tags found matching the search criteria"); ?>",
                        parent_form: jQuery("#search-form"),
                        width: 400
                    });
                });

                var filter_tags = [];
                <?php
                foreach ($filter_tags as $filter_tag_id => $objective) {
                    ?>
                    filter_tags.push({
                        target_id: '<?php echo html_encode($objective->getID()) ?>',
                        target_parent: '<?php echo html_encode($objective->getParent()) ?>',
                        target_name: '<?php echo html_encode($objective->getName()); ?>'
                    });
                    <?php
                }
                ?>

                jQuery(function () {
                    for (var i = 0; i < filter_tags.length; i++) {
                        var value = filter_tags[i];
                        var element_id = 'filter_tag_' + value.target_id;
                        if (!jQuery('#' + element_id).length) {
                            jQuery(document.createElement("input")).attr({
                                "type"        : 'hidden',
                                "class"       : 'search-target-control filter_tag_search_target_control',
                                "name"        : 'filter_tag[]',
                                "id"          : element_id,
                                "value"       : value.target_id,
                                "data-id"     : value.target_id,
                                "data-filter" : 'filter_tag',
                                "data-label"  : value.target_name
                            }).appendTo("#search-form");
                        }
                    }
                    jQuery("#filter_tag_button").data("settings").build_list();
                    // on cohort select change
                    jQuery('#c').on('change', function() {
                        cohort_changed(this);
                    });

                    function cohort_changed(selected_cohort) {
                        jQuery.ajax({
                            url: '<?php echo ENTRADA_URL; ?>/api/units.api.php?method=get-by-cohort&cohort_id=' + selected_cohort.value,
                            type: 'GET',
                            dataType: 'json',
                            success: function(units_results) {
                                jQuery('#unit_select').empty();
                                jQuery('#unit_select').append('<option value="">-- <?php echo $translate->_("Select a week"); ?> --</option>');
                                // api only returns an array if 0 results, otherwise returns an object.
                                if (jQuery.type(units_results) != "array" && Object.keys(units_results).length > 0) {
                                    var SEARCH_UNIT = "<?php echo $SEARCH_UNIT ?>";

                                    for (unit_id in units_results) {
                                        jQuery('#unit_select').append('<option value="' + unit_id + '">' + units_results[unit_id] + '</option>');
                                    }

                                    // select the unit searched for
                                    jQuery("#unit_select").val(<?php echo $SEARCH_UNIT ?>);
                                }
                            }
                        });
                    }

                    // populate initial units dropdown
                    cohort_changed(jQuery('#c')[0]);
                });

                function resubmit(span_button) {
                    let sort_by = jQuery(span_button).attr('data-name').replace('sort-','');
                    jQuery('#sort').val(sort_by);
                    jQuery('#search-form').submit();
                }
            </script>
            <div class="control-group">
                <label for="search_filter_select" class="control-label"><?php echo $translate->_("Search Filter Fields"); ?></label>
                <div class="controls">
                    <select id="search_filter_select">
                        <option value="">-- <?php echo $translate->_("Select Field to Search"); ?> --</option>
                        <option value="event_title"><?php echo $translate->_("Event Title"); ?></option>
                        <option value="event_description"><?php echo $translate->_("Event Description"); ?></option>
                        <option value="event_types"><?php echo $translate->_("Learning Event Types"); ?></option>
                        <option value="teachers"><?php echo $translate->_("Teachers"); ?></option>
                        <option value="course"><?php echo $translate->_("Course"); ?></option>
                        <option value="course_unit"><?php echo $translate->_("Unit"); ?></option>
                        <?php foreach ($tag_sets as $tag_set): ?>
                            <option value="tag_set-<?php echo $tag_set["objective_id"]; ?>"><?php echo $tag_set["objective_name"]; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div id="search-filter-operator-select-control-group" class="control-group" style="display: none">
                <label for="search_filter_operator_select" class="control-label"><?php echo $translate->_("Search Filter Operator"); ?></label>
                <div class="controls">
                    <select id="search_filter_operator_select">
                        <option value="">-- <?php echo $translate->_("Select Operator"); ?> --</option>
                        <option value="is"><?php echo $translate->_("is"); ?></option>
                        <option value="is_not"><?php echo $translate->_("is not"); ?></option>
                        <option value="contains"><?php echo $translate->_("contains"); ?></option>
                        <option value="not_contains"><?php echo $translate->_("does not contain"); ?></option>
                        <option value="starts_with"><?php echo $translate->_("starts with"); ?></option>
                        <option value="not_starts_with"><?php echo $translate->_("does not start with"); ?></option>
                        <option value="ends_with"><?php echo $translate->_("ends with"); ?></option>
                        <option value="not_ends_with"><?php echo $translate->_("does not end with"); ?></option>
                    </select>
                </div>
            </div>
            <div id="search-filter-text-control-group" class="control-group" style="display: none">
                <label for="search_filter_text" class="control-label"><?php echo $translate->_("Search Filter Text"); ?></label>
                <div class="controls">
                    <input type="text" id="search_filter_text" />
                    <button id="search_filter_add_button" type="button" class="btn"><?php echo $translate->_("Add Search Filter"); ?></button>
                </div>
            </div>
            <script>
                jQuery(function () {
                    function hide_text_control_group() {
                        jQuery("#search-filter-text-control-group").hide();
                        jQuery("#search_filter_text").val('');
                    }
                    function hide_control_group() {
                        jQuery("#search_filter_select").val('');
                        jQuery("#search-filter-operator-select-control-group").hide();
                        jQuery("#search_filter_operator_select").val('');
                        hide_text_control_group();
                    }
                    jQuery("#search_filter_select").change(function() {
                        if (jQuery(this).val()) {
                            jQuery("#search-filter-operator-select-control-group").show();
                        } else {
                            hide_control_group();
                        }
                    });
                    jQuery("#search_filter_operator_select").change(function() {
                        if (jQuery(this).val()) {
                            jQuery("#search-filter-text-control-group").show();
                        } else {
                            hide_text_control_group();
                        }
                    });
                    function search_filter_remove() {
                        var input_name = jQuery(this).attr('data-name');
                        var input_value = jQuery(this).attr('data-value');
                        var input_element = jQuery('input[name="' + input_name + '"][value="' + input_value + '"]');
                        if (input_element.length) {
                            input_element.remove();
                        }
                        jQuery(this).parent().remove();
                    }
                    function search_filter_add() {
                        var search_filter_value = jQuery("#search_filter_text").val();
                        if (search_filter_value) {
                            var search_filter_operator = jQuery("#search_filter_operator_select").val();
                            var search_filter_field = jQuery("#search_filter_select").val();
                            var input_name = 'search_filters[' + search_filter_field + '][' + search_filter_operator + '][]';
                            var input_value = search_filter_value;
                            var label_text = "'" + search_filter_field + '\' ' + search_filter_operator + ' "' + search_filter_value + '"';
                            if (!jQuery('input[name="' + input_name + '"][value="' + input_value + '"]').length) {
                                jQuery(document.createElement("input")).attr({
                                    'type'  : 'hidden',
                                    'name'  : input_name,
                                    'value' : input_value
                                }).appendTo("#search-filter-list");
                                var item_label = jQuery(document.createElement("span")).addClass('target-label').html(label_text);
                                var item_remove = jQuery(document.createElement("span")).attr({
                                    'data-name': input_name,
                                    'data-value': input_value
                                }).addClass('search-filter-remove').addClass('remove-target-toggle').html('×');
                                item_remove.click(search_filter_remove);
                                jQuery(document.createElement("li")).attr({
                                }).addClass('selected-target-item').append(item_label).append(item_remove).appendTo("#search-filters-targets-list");
                            }
                            hide_control_group();
                        }
                    }
                    jQuery("#search_filter_add_button").click(search_filter_add);
                    jQuery("#search_filter_text").keypress(function(e) {
                        if (e.which === 13) {
                            search_filter_add();
                            e.preventDefault();
                        }
                    });
                    jQuery(".search-filter-remove").click(search_filter_remove);
                });
            </script>
            <div class="control-group">
                <div id="search-filter-list" class="controls entrada-search-widget">
                    <div>
                        <ul id="search-filters-targets-list" class="selected-targets-list" style="padding-left: 0">
                            <?php foreach ($search_filters as $search_filter_field => $search_filter): ?>
                                <?php foreach ($search_filter as $search_filter_operator => $search_filter_values): ?>
                                    <?php foreach ($search_filter_values as $search_filter_value):
                                        $search_filter_input_name = "search_filters[" . $search_filter_field . "][" . $search_filter_operator . "][]";
                                        $search_filter_input_value = $search_filter_value;
                                        $search_filter_label_text = "'" . $search_filter_field . "' " . $search_filter_operator . " \"" . $search_filter_value . "\"";
                                        ?>
                                        <li class="filter_tag_target_item">
                                            <span class="selected-list-container">
                                                <span class="selected-list-item"><?php echo $search_filter_label_text; ?></span><span class="search-filter-remove remove-selected-list-item remove-target-toggle" data-name="<?php echo $search_filter_input_name; ?>" data-value="<?php echo $search_filter_input_value; ?>">×</span>
                                            </span>
                                            <input type="hidden" name="<?php echo $search_filter_input_name; ?>" value="<?php echo $search_filter_input_value; ?>"/>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
			<div class="control-group">
				<label class="control-label">&nbsp;</label>
				<div class="controls">
					<input type="submit" class="btn btn-primary" value="Search">
				</div>
			</div>
		</div>
		<!-- end advanced search -->
		<div class="control-group">
			<div class="controls">
				<div class="btn-group" data-toggle="buttons-radio">
					<a href="<?php echo ENTRADA_RELATIVE; ?>/curriculum/search?<?php echo $replace_query(array("m" =>  "text")); ?>" class="btn <?php echo (($SEARCH_MODE == "standard") ? "active" : ""); ?>"><?php echo $translate->_("List View"); ?></a>
					<a href="<?php echo ENTRADA_RELATIVE; ?>/curriculum/search?<?php echo $replace_query(array("m" =>  "timeline")); ?>" class="btn <?php echo (($SEARCH_MODE == "timeline") ? "active" : ""); ?>"><?php echo $translate->_("Timeline View"); ?></a>
					<a href="<?php echo ENTRADA_RELATIVE; ?>/curriculum/search?<?php echo $replace_query(array("m" =>  "table")); ?>" class="btn <?php echo (($SEARCH_MODE == "table") ? "active" : ""); ?>"><?php echo $translate->_("Table View"); ?></a>
				</div>
                <?php
                if ($TOTAL_ROWS > 0) {
                    ?>
                    <div class="btn-group">
                        <a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
                            <?php echo $translate->_("Export Results"); ?> <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="<?php echo ENTRADA_RELATIVE; ?>/curriculum/search?<?php echo $replace_query(["m" =>  "csv"]); ?>" role="button"><?php echo $translate->_("Export Including Child Events"); ?></a>
                            </li>
                            <li>
                                <a href="<?php echo ENTRADA_RELATIVE; ?>/curriculum/search?<?php echo $replace_query(["m" =>  "csv_grouped"]); ?>" role="button"><?php echo $translate->_("Export Excluding Child Events"); ?></a>
                            </li>
                        </ul>
                    </div>
                    <?php
                }
                ?>
			</div>
		</div>
	</form>
	<?php

	if ($SEARCH_QUERY) {
        $mysql_innodb_ft_min_token_size = $db->GetRow("SHOW VARIABLES WHERE `Variable_name` LIKE 'innodb_ft_min_token_size'")["Value"];
        $mysql_ft_min_word_len = $db->GetRow("SHOW VARIABLES WHERE `Variable_name` LIKE 'ft_min_word_len'")["Value"];
        $min_characters_for_search = max($mysql_innodb_ft_min_token_size, $mysql_ft_min_word_len);

        if (strlen($SEARCH_QUERY) < $min_characters_for_search) {
            echo "<div class=\"display-notice space-above\">";
            echo "    <strong>" . $translate->_("Search results may be incomplete due to system configuration issues.") . "</strong> ";
            echo sprintf($translate->_("Entering fewer than %s characters may not show all results."), $min_characters_for_search);
            echo "</div>";
        }
    }

    if ($SEARCH_QUERY || !empty($filter_tag_ids) || !empty($search_filters) || !empty($SEARCH_COURSE) || !empty($SEARCH_UNIT)) {

		if (empty($filter_tag_ids) && empty($search_filters)) {
			$mysql_innodb_ft_min_token_size = $db->GetRow("SHOW VARIABLES WHERE Variable_name LIKE 'innodb_ft_min_token_size'")['Value'];
			$mysql_ft_min_word_len = $db->GetRow("SHOW VARIABLES WHERE Variable_name LIKE 'ft_min_word_len'")['Value'];
			$min_characters_for_search = max($mysql_innodb_ft_min_token_size, $mysql_ft_min_word_len);
			if (strlen($SEARCH_QUERY) < $min_characters_for_search) {
				echo "<div class=\"display-notice\" style=\"margin-top: 20px; padding: 15px\">\n";
				echo "	<div style=\"font-side: 13px; font-weight: bold\">Results May Be Incomplete</div>\n";
				echo "  We recommend searching using ".$min_characters_for_search." characters or more. Due to system limitations, entering fewer characters may not show all the results that exist.";
				echo "</div>\n";
			}
		}

		switch ($SEARCH_MODE) {
			case "timeline" :
				$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/timeline/timeline-api.js\"></script>\n";
				$ONLOAD[] = "loadTimeline()";
				?>
				<script>
				var tl = new Array();
				var gradYears = new Array();
				var gradYearIds = new Array();
				<?php
				if ($SEARCH_CLASS) {
					echo "gradYears[0] = '".preg_replace("/[^0-9]/", "", groups_get_name($SEARCH_CLASS))."';\n\n";
					echo "gradYearIds[".preg_replace("/[^0-9]/", "", groups_get_name($SEARCH_CLASS))."] = '".$SEARCH_CLASS."';\n\n";
				} else {
					$cohorts_list = groups_get_active_cohorts($ENTRADA_USER->getActiveOrganisation());
					$i = 0;
					foreach ($cohorts_list as $cohort) {
						echo "gradYears[".$i."] = '".preg_replace("/[^0-9]/", "", $cohort["group_name"])."';\n";
						echo "gradYearIds[".preg_replace("/[^0-9]/", "", $cohort["group_name"])."] = '".$cohort["group_id"]."';\n";
						$i++;
					}
				}
				?>

				function showYear(yearNumber) {
					if ((yearNumber < 1 ) || (yearNumber > 4)) {
						yearNumber = 1;
					}

					gradYears.each(function(gradClass) {
						startYear = (gradClass.match(/[\d\.]+/g) - (4 - yearNumber));

						tl[gradClass].getBand(0).setCenterVisibleDate(Timeline.DateTime.parseGregorianDateTime('Jan 25 ' + startYear + ' 00:00:00 GMT<?php echo date("O"); ?>'));
					});

					return;
				}

				function loadTotalsSidebar() {
					var class_totals = $('class-result-totals');

					if (class_totals != null) {
						var list_menu = document.createElement('ul');
						list_menu.setAttribute('class', 'menu');

						gradYears.each(function(gradClass) {
							var list_item = document.createElement('li');
							list_item.setAttribute('class', 'item');

							var year_totals	= document.createElement('div');
							year_totals.setAttribute('id', gradClass + '-event-count');

							var class_title	= document.createTextNode('Class of ' + gradClass + ': ');

							year_totals.appendChild(class_title);
							list_item.appendChild(year_totals);
							list_menu.appendChild(list_item);
						});

						class_totals.appendChild(list_menu);
					}

					return;
				}

				function loadTimeline() {
					if (gradYears.length > 0) {
						loadTotalsSidebar();
						gradYears.each(function(gradClass) {
							loadClass(gradClass);
						});
					} else {
						alert('There are no classes specified which can be searched.');
					}

					return;
				}

				function loadClass(gradClass) {
					gradYear = gradClass.match(/[\d\.]+/g);

					var eventSource = new Timeline.DefaultEventSource(0);
					var theme = Timeline.ClassicTheme.create();
					/*
					theme.event.bubble.width = 220;
					theme.event.bubble.height = 120;
					*/
                    theme.event.bubble.width = 420;
                    theme.event.bubble.height = 220;
					theme.event.track.height = 1.1;
					var zones = [
						{	start:    'Sept 1 ' + (gradYear - 4) + ' 00:00:00 GMT<?php echo date("O"); ?>',
							end:      'Apr 30 ' + gradYear + ' 00:00:00 GMT<?php echo date("O"); ?>',
							magnify:  4,
							unit:     Timeline.DateTime.MONTH
						}
					];

					var bandInfos = [
						Timeline.createHotZoneBandInfo({
							width:          '100%',
							intervalUnit:   Timeline.DateTime.YEAR,
							intervalPixels: 175,
							zones:          zones,
							eventSource:    eventSource,
							date:           Timeline.DateTime.parseGregorianDateTime('Jan 15 ' + (gradYear - 3) + ' 00:00:00 GMT<?php echo date("O"); ?>'),
							theme:          theme
						})
					];

					bandInfos[0].decorators = [
						new Timeline.SpanHighlightDecorator({
							startDate:  'Sept 1 ' + (gradYear - 4) + ' 00:00:00 GMT<?php echo date("O"); ?>',
							endDate:    'Apr 30 ' + gradYear + ' 00:00:00 GMT<?php echo date("O"); ?>',
							color:      ((gradYear % 2) ? '#003366' : '#336699'),
							opacity:    50,
							startLabel: 'Sept 01 ' + (gradYear - 4),
							endLabel:   'Apr 30 ' + gradYear,
							theme:      theme
						})
					];

					tl[gradClass] = Timeline.create($('search-timeline-' + gradClass), bandInfos, Timeline.HORIZONTAL);
					tl[gradClass].loadXML('<?php echo ENTRADA_RELATIVE; ?>/api/timeline.api.php?<?php echo $replace_query(array("q" => rawurlencode($SEARCH_QUERY), "m" => "", "c" => "", "course_select" => $SEARCH_COURSE, "unit_select" => $SEARCH_UNIT)); ?>&c=' + gradYearIds[gradClass] + '&filter_tag=<?php echo implode(",", $filter_tag_ids) ?>', function(xml, url) {
						eventSource.loadXML(xml, url);
						if ($(gradClass + '-event-count') != null) {
							$(gradClass + '-event-count').innerHTML += eventSource.getCount();
						}
					});
				}
				</script>

				<h2>Plotted Timeline</h2>

				<div style="text-align: right">
					<a href="javascript: showYear(1)">1st Year</a> |
					<a href="javascript: showYear(2)">2nd Year</a> |
					<a href="javascript: showYear(3)">3rd Year</a> |
					<a href="javascript: showYear(4)">4th Year</a>
				</div>

				<?php
				if ($SEARCH_CLASS) {
					echo "<div style=\"border: 1px #CCCCCC solid; margin-bottom: 1px\">\n";
					echo "	<img src=\"".ENTRADA_URL."/images/dynamic/14/314/5/90/".rawurlencode(groups_get_name($SEARCH_CLASS))."/jpg\" width=\"25\" height=\"325\" align=\"left\" alt=\"".html_encode(groups_get_name($SEARCH_CLASS))."\" title=\"".html_encode(groups_get_name($SEARCH_CLASS))."\" />\n";
					echo "	<div id=\"search-timeline-".preg_replace("/[^0-9]/", "", groups_get_name($SEARCH_CLASS))."\" style=\"height: 325px\"></div>\n";
					echo "</div>\n";
				} else {
					$cohorts_list = groups_get_active_cohorts($ENTRADA_USER->getActiveOrganisation());
					foreach ($cohorts_list as $cohort) {
						echo "<div style=\"border: 1px #CCCCCC solid; margin-bottom: 1px\">\n";
						echo "	<img src=\"".ENTRADA_URL."/images/dynamic/14/314/5/90/".rawurlencode($cohort["group_name"])."/jpg\" width=\"25\" height=\"325\" align=\"left\" alt=\"".html_encode($cohort["group_name"])."\" title=\"".html_encode($cohort["group_name"])."\" />\n";
						echo "	<div id=\"search-timeline-".preg_replace("/[^0-9]/", "", $cohort["group_name"])."\" style=\"height: 325px\"></div>\n";
						echo "</div>\n";
					}
				}

				new_sidebar_item("Cohort Result Totals", "<div id=\"class-result-totals\"></div>", "result-totals", "open");
			break;
			case "standard" :
			default:
				$serve_csv = function ($rows) {
					ob_clear_open_buffers();
					header("Pragma: public");
					header("Expires: 0");
					header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
					header("Content-Type: application/force-download");
					header("Content-Type: application/octet-stream");
					header("Content-Type: text/csv");
					header("Content-Disposition: attachment; filename=\"curriculum-search-".date("Y-m-d_H:i:s").".csv\"");
					header("Content-Transfer-Encoding: binary");

					$fp = fopen("php://output", "w");
					foreach ($rows as $row) {
						fputcsv($fp, $row);
					}
					exit;
				};

                $pagination = new Entrada_Pagination($PAGE_CURRENT, $RESULTS_PER_PAGE, $TOTAL_ROWS, ENTRADA_RELATIVE."/curriculum/search", replace_query());

				if (($SEARCH_MODE != "timeline") && ($TOTAL_PAGES > 1)) {
                    echo $pagination->GetPageBar();
				}

				/**
				 * Provides the first parameter of MySQLs LIMIT statement by calculating which row to start results from.
				 */
				$limit_parameter = (int) (($RESULTS_PER_PAGE * $PAGE_CURRENT) - $RESULTS_PER_PAGE);
				$query = sprintf($query_search, $limit_parameter, $RESULTS_PER_PAGE);
				$results = $db->GetAll($query);

				if ($results) {
					echo $pagination->GetResultsLabel(html_encode(limit_chars($SEARCH_QUERY, 65)));

					switch ($SEARCH_MODE) {
						case "standard":
							foreach ($results as $result) {
								if ($SEARCH_QUERY) {
									$description = search_description($SEARCH_QUERY, $result["event_description"]);
								} else {
									$description = clean_input($result["event_description"], array("notags", "trimds", "trim"));
									$description = substr($description, 0, 275);
								}

								echo "<div id=\"result-".$result["event_id"]."\" class=\"space-below\">\n";
								echo "	<a href=\"".ENTRADA_URL."/events?id=".$result["event_id"]."\" style=\"font-weight: bold\">".html_encode($result["event_title"])."</a> <span class=\"muted\"> on ".date(DEFAULT_DATETIME_FORMAT, $result["event_start"])."</span><br />\n";
								echo 	(($description) ? clean_input($description, array("decode", "notags")) : "")."\n";
								echo "	<div style=\"white-space: nowrap; overflow: hidden\"><a href=\"".ENTRADA_URL."/events?id=".$result["event_id"]."\" style=\"color: green; font-size: 11px\" target=\"_blank\">".ENTRADA_URL."/events?id=".$result["event_id"]."</a></div>\n";
								echo "</div>\n";
							}
						break;
						case "table":
						case "csv":
						case "csv_grouped":
							$version_model = new Models_Curriculum_Map_Versions();
							$export = new Entrada_Curriculum_Export($version_model);
							$export->fetchRelatedObjectivesForEvents($results, $tag_set_ids);
							Models_Event::fetchContactsForEvents($results, "teacher");
							$show_teachers = (bool) count(array_filter($results, function ($event) { return (bool) count($event["contacts"]["teacher"]); }));
							Models_Event::fetchEventTypesForEvents($results);
							Models_Event::fetchCourseUnitsForEvents($results);

							switch ($SEARCH_MODE) {
								case "table":
									?>
									<style>
										table.grid {
											min-width: 100%;
										}

										table.grid thead tr td {
											font-weight: 700;
											border-bottom: 2px #CCC solid;
											font-size: 11px;
										}

                                        table.grid thead td div.sort-buttons {
                                            float: left;
                                            padding: 0 0 0 5px;
                                        }

                                        table.grid thead td strong {
                                            float: left;
                                        }

										table.grid tbody tr td {
											vertical-align:top;
											padding:3px 2px 10px 4px;
											font-size: 11px;
											border-bottom: 1px #EEE solid;
										}

										table.grid tbody tr td a {
											font-size: 11px;
										}

										table.grid tbody td.border-r {
											border-right: 1px #EEE solid;
										}

                                        span.sort-button {
                                            font-size: 15px;
                                        }

										span.sort-button:hover {
											cursor: pointer;
										}

										span.sort-button-active {
											font-size: larger;
										}

                                        @media (min-width: 769px) {
                                            table.grid thead tr td:nth-child(1) { width: 98px;  }
                                            table.grid thead tr td:nth-child(2) { width: 106px; }
                                            table.grid thead tr td:nth-child(3) { width: 49px;  }
                                            table.grid thead tr td:nth-child(4) { width: 71px;  }
                                            table.grid thead tr td:nth-child(5) { width: 130px; }
                                            table.grid thead tr td:nth-child(6) { width: 48px;  }

                                        }
									</style>
									<div class="grid-container">
										<table class="grid table" cellspacing="0">
											<thead>
												<tr>
													<td class="border-r">
														<strong><?php echo $translate->_("Course Name"); ?></strong>
                                                        <div class="sort-buttons">
                                                            <span
                                                                    data-name="sort-course-asc"
                                                                    onclick="resubmit(this)"
                                                                    class="sort-button <?php echo ($SEARCH_SORT_ORDER == 'course-asc' ? 'sort-button-active' : '' ) ?>"
                                                            >&uarr;</span>
                                                            <span
                                                                    data-name="sort-course-desc"
                                                                    onclick="resubmit(this)"
                                                                    class="sort-button <?php echo ($SEARCH_SORT_ORDER == 'course-desc' ? 'sort-button-active' : '' ) ?>"
                                                            >&darr;</span>
                                                        </div>
													</td>
													<td class="border-r">
														<strong><?php echo $translate->_("Learning Event"); ?></strong>
                                                        <div class="sort-buttons">
                                                            <span
                                                                    data-name="sort-event-asc"
                                                                    onclick="resubmit(this)"
                                                                    class="sort-button <?php echo ($SEARCH_SORT_ORDER == 'event-asc' ? 'sort-button-active' : '' ) ?>"
                                                            >&uarr;</span>
                                                            <span
                                                                    data-name="sort-event-desc"
                                                                    onclick="resubmit(this)"
                                                                    class="sort-button <?php echo ($SEARCH_SORT_ORDER == 'event-desc' ? 'sort-button-active' : '' ) ?>"
                                                            >&darr;</span>
                                                        </div>
													</td>
													<td class="border-r">
														<strong><?php echo $translate->_("Date"); ?></strong>
                                                        <div class="sort-buttons">
                                                            <span
                                                                    data-name="sort-date-asc"
                                                                    onclick="resubmit(this)"
                                                                    class="sort-button <?php echo ($SEARCH_SORT_ORDER == 'date-asc' ? 'sort-button-active' : '' ) ?>"
                                                            >&uarr;</span>
                                                            <span
                                                                    data-name="sort-date-desc"
                                                                    onclick="resubmit(this)"
                                                                    class="sort-button <?php echo ($SEARCH_SORT_ORDER == 'date-desc' ? 'sort-button-active' : '' ) ?>"
                                                            >&darr;</span>
                                                        </div>
													</td>
													<td class="border-r">
														<strong><?php echo $translate->_("Duration"); ?></strong>
                                                        <div class="sort-buttons">
                                                            <span
                                                                    data-name="sort-duration-asc"
                                                                    onclick="resubmit(this)"
                                                                    class="sort-button <?php echo ($SEARCH_SORT_ORDER == 'duration-asc' ? 'sort-button-active' : '' ) ?>"
                                                            >&uarr;</span>
                                                            <span
                                                                    data-name="sort-duration-desc"
                                                                    onclick="resubmit(this)"
                                                                    class="sort-button <?php echo ($SEARCH_SORT_ORDER == 'duration-desc' ? 'sort-button-active' : '' ) ?>"
                                                            >&darr;</span>
                                                        </div>
													</td>
													<td class="border-r">
														<strong><?php echo $translate->_("Description"); ?></strong>
                                                        <div class="sort-buttons">
                                                            <span
                                                                    data-name="sort-description-asc"
                                                                    onclick="resubmit(this)"
                                                                    class="sort-button <?php echo ($SEARCH_SORT_ORDER == 'description-asc' ? 'sort-button-active' : '' ) ?>"
                                                            >&uarr;</span>
                                                            <span
                                                                    data-name="sort-description-desc"
                                                                    onclick="resubmit(this)"
                                                                    class="sort-button <?php echo ($SEARCH_SORT_ORDER == 'description-desc' ? 'sort-button-active' : '' ) ?>"
                                                            >&darr;</span>
                                                        </div>
													</td>
													<td class="border-r">
														<strong><?php echo $translate->_("Unit"); ?></strong>
                                                        <div class="sort-buttons">
                                                            <span
                                                                    data-name="sort-unit-asc"
                                                                    onclick="resubmit(this)"
                                                                    class="sort-button <?php echo ($SEARCH_SORT_ORDER == 'unit-asc' ? 'sort-button-active' : '' ) ?>"
                                                            >&uarr;</span>
                                                            <span
                                                                    data-name="sort-unit-desc"
                                                                    onclick="resubmit(this)"
                                                                    class="sort-button <?php echo ($SEARCH_SORT_ORDER == 'unit-desc' ? 'sort-button-active' : '' ) ?>"
                                                            >&darr;</span>
                                                        </div>
													</td>
													<td class="border-r">
														<strong><?php echo $translate->_("Event Types"); ?></strong>
													</td>
													<?php if ($show_teachers): ?>
														<td class="border-r">
															<strong><?php echo $translate->_("Teachers"); ?></strong>
														</td>
													<?php endif; ?>
													<?php foreach ($tag_set_ids as $tag_set_id): ?>
														<td class="border-r">
															<strong><?php echo html_encode($tag_sets[$tag_set_id]["objective_name"]); ?></strong>
														</td>
													<?php endforeach; ?>
												</tr>
											</thead>
											<tbody>
												<?php foreach ($results as $result): ?>
													<tr>
														<td class="border-r"><?php echo html_encode($result["course_code"]." ".$result["course_name"]); ?></td>
														<td class="border-r"><a href="<?php echo ENTRADA_URL."/events?id=".$result["event_id"];?>"><?php echo html_encode($result["event_title"]); ?></td>
														<td class="border-r"><?php echo date(DEFAULT_DATE_FORMAT, $result["event_start"]); ?></td>
														<td class="border-r"><?php echo html_encode($result["event_duration"]); ?> minutes</td>
														<td class="border-r"><?php echo clean_input($result["event_description"], array("decode", "notags")); ?></td>
														<td class="border-r"><?php echo html_encode($result["course_unit"]); ?></td>
														<td class="border-r">
															<?php if (isset($result["event_types"])): ?>
																<?php if (count($result["event_types"]) == 1): ?>
																	<ul>
																		<?php foreach ($result["event_types"] as $event_type): ?>
																			<li>
																				<?php echo html_encode($event_type["eventtype_title"]); ?>
																			</li>
																		<?php endforeach; ?>
																	</ul>
																<?php else: ?>
																	<ul>
																		<?php foreach ($result["event_types"] as $event_type): ?>
																			<li>
																				<?php echo html_encode($event_type["eventtype_title"]); ?>
																				(<?php echo html_encode($event_type["duration"]); ?> min)
																			</li>
																		<?php endforeach; ?>
																	</ul>
																<?php endif; ?>
															<?php endif; ?>
														</td>
														<?php if ($show_teachers): ?>
															<td class="border-r">
																<?php if (isset($result["contacts"]["teacher"])): ?>
																	<ul>
																		<?php foreach ($result["contacts"]["teacher"] as $teacher): ?>
																			<li><?php echo html_encode($teacher["lastname"]) . ", " . html_encode($teacher["firstname"]); ?></li>
																		<?php endforeach; ?>
																	</ul>
																<?php endif; ?>
															</td>
														<?php endif; ?>
														<?php foreach ($tag_set_ids as $tag_set_id): ?>
															<td class="border-r">
																<?php if (isset($result["objectives"][$tag_set_id])): ?>
																	<ul>
																		<?php foreach ($result["objectives"][$tag_set_id] as $objective): ?>
																			<li><?php echo html_encode(get_objective_text($objective)); ?></li>
																		<?php endforeach; ?>
																	</ul>
																<?php endif; ?>
															</td>
														<?php endforeach; ?>
													</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
									</div>
									<?php
									break;
								case "csv":
								case "csv_grouped":
									$version_model = new Models_Curriculum_Map_Versions();
									$export = new Entrada_Curriculum_Export($version_model);
									$serve_csv($export->toRows($results, $tag_set_ids, $tag_sets, 'get_objective_text', $SEARCH_MODE == "csv_grouped"));
									break;
							}
						break;
					}
				} else {
					switch ($SEARCH_MODE) {
						case "csv":
						case "csv_grouped":
							$version_model = new Models_Curriculum_Map_Versions();
							$export = new Entrada_Curriculum_Export($version_model);
							$serve_csv($export->toRows($results, $tag_set_ids, $tag_sets, 'get_objective_text'));
							break;
						default:
                            echo "<div class=\"display-notice space-above\">\n";
                            echo "	<h4> " . $translate->_("No Matching Results") . "</h4>";
                            echo sprintf($translate->_("There are no Learning Events found containing &quot;%s&quot;."), "<strong>" . html_encode($SEARCH_QUERY) . "</strong>");

                            if ($SEARCH_CLASS || $SEARCH_YEAR) {
                                echo "<br><br>";
                                echo $translate->_("You may wish to modify or remove the Cohort or Academic Year limitations within Advanced Search.");
                            }
                            echo "</div>\n";

							break;

					}
				}
			break;
		}
	}
}
