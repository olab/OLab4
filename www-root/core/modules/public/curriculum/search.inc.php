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

	application_log("error", "Group [".$_SESSION["permissions"][$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]]["group"]."] and role [".$_SESSION["permissions"][$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    /**
     * Meta information for this page.
     */
	$PAGE_META["title"] = "Curriculum Search";
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

	if ($SEARCH_QUERY) {
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

		if ($SEARCH_MODE == "standard") {

            $queries = Entrada_Curriculum_Search::prepare($SEARCH_QUERY, $SEARCH_ORGANISATION, $SEARCH_CLASS, $SEARCH_YEAR);

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

	<h1>Curriculum Search</h1>
	<form action="<?php echo ENTRADA_RELATIVE; ?>/curriculum/search" method="get" class="form-horizontal">
		<?php
		if ($SEARCH_MODE == "timeline") {
			echo "<input type=\"hidden\" name=\"m\" value=\"timeline\" />\n";
		}
		?>
		<div class="control-group" style="margin-bottom:5px">
			<label class="control-label">Boolean Search Term:</label>
			<div class="controls">
				<input type="text" style="width:300px" id="q" name="q" value="<?php echo html_encode($SEARCH_QUERY); ?>" /> <input type="submit" class="btn btn-primary" value="Search" />
			</div>
		</div>
		<div class="control-group">
			<div class="controls content-small search-operators">
				<a data-toggle="collapse" href="#search-operator-instructions">
					Refine your search using: -, "quotes", OR
				</a>
				<style type="text/css">
					#search-operator-instructions.in {
						border: 1px solid #ccc;
					}
					#search-operator-instructions dt {
						width: 70px;
						margin-left: 12px;
						text-align: left;
					}
					.stripe {
						background: #f4f7fa;
					}
					#search-operator-instructions dd {
						margin-left:90px;
					}
					/*shrink label so the year fits on screen when loaded*/
					.timeline-band-layer-inner td {
						font-size: 30px;
					}
				</style>
				<div id="search-operator-instructions" class="collapse">
					<dl>
						<dt>-</dt>
						<dd>Use the '-' character to remove a word from search results</dd>
						<dd>E.g. asthma -paediatric
						<br>Returns all results related to asthma excluding the word paediatric.</dd>
						<div class="stripe">
							<dt>"quotes"</dt>
							<dd>Use quotes to search for the exact text</dd>
							<dd>E.g. asthma "paediatric"
							<br>Returns results that contain exactly paediatric. Pediatric results will not be listed.</dd>
						</div>
						<dt>OR</dt>
						<dd>Use this to search for multiple queries at a time</dd>
						<dd>E.g. asthma OR paediatric
						<br>Returns all results for asthma or paediatric, the results do not need to be related.</dd>
					</dl>
				</div>
			</div>
		</div>
		<div class="control-group">
			<label class="control-label">Cohort:</label>
			<div class="controls">
				<select id="c" name="c">
                    <option value="0"<?php echo ((!$SEARCH_CLASS) ? " selected=\"selected\"" : ""); ?>>-- All Cohorts --</option>
                    <?php
                    $cohorts = groups_get_all_cohorts($ENTRADA_USER->getActiveOrganisation());
                    foreach ($cohorts as $cohort) {
                        echo "<option value=\"".$cohort["group_id"]."\"".(($SEARCH_CLASS == $cohort["group_id"]) ? " selected=\"selected\"" : "").">".html_encode($cohort["group_name"])."</option>\n";
                    }
                    ?>
				</select>
			</div>
		</div> <!--/control-group-->
		<div class="control-group">
			<label class="control-label">Academic Year:</label>
			<div class="controls">
					<select id="y" name="y" <?php echo (($SEARCH_MODE == "timeline") ? " disabled=\"disabled\"" : ""); ?>>
                        <option value="0"<?php echo ((!$SEARCH_YEAR)? " selected=\"selected\"" : ""); ?>>-- All Years --</option>
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
			<div class="controls">
				<div class="btn-group" data-toggle="buttons-radio">
					<a href="<?php echo ENTRADA_RELATIVE; ?>/curriculum/search?<?php echo replace_query(array("m" =>  "text")); ?>" class="btn <?php echo (($SEARCH_MODE != "timeline") ? "active" : ""); ?>">Text Results</a>
					<a href="<?php echo ENTRADA_RELATIVE; ?>/curriculum/search?<?php echo replace_query(array("m" =>  "timeline")); ?>" class="btn <?php echo (($SEARCH_MODE == "timeline") ? "active" : ""); ?>">Timeline</a>
				</div>
			</div>
		</div>
	</form>
	<?php
	if ($SEARCH_QUERY) {
		switch ($SEARCH_MODE) {
			case "timeline" :
				$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/timeline/timeline-api.js\"></script>\n";
				$ONLOAD[] = "loadTimeline()";
				?>
				<script type="text/javascript">
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
					theme.event.bubble.width = 220;
					theme.event.bubble.height = 120;
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
					tl[gradClass].loadXML('<?php echo ENTRADA_RELATIVE; ?>/api/timeline.api.php?q=<?php echo rawurlencode($SEARCH_QUERY); ?>&c=' + gradYearIds[gradClass], function(xml, url) {
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
			default :
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

					foreach ($results as $result) {
						$description = search_description($SEARCH_QUERY, $result["event_description"]);

						echo "<div id=\"result-".$result["event_id"]."\" class=\"space-below\">\n";
						echo "	<a href=\"".ENTRADA_URL."/events?id=".$result["event_id"]."\" style=\"font-weight: bold\">".html_encode($result["event_title"])."</a> <span class=\"muted\"> on ".date(DEFAULT_DATE_FORMAT, $result["event_start"])."</span><br />\n";
						echo 	(($description) ? clean_input($description, array("decode", "notags")) : "")."\n";
						echo "	<div style=\"white-space: nowrap; overflow: hidden\"><a href=\"".ENTRADA_URL."/events?id=".$result["event_id"]."\" style=\"color: green; font-size: 11px\" target=\"_blank\">".ENTRADA_URL."/events?id=".$result["event_id"]."</a></div>\n";
						echo "</div>\n";
					}
				} else {
					if (strlen($SEARCH_QUERY) > 3) {
						echo "<div class=\"display-notice\" style=\"margin-top: 20px; padding: 15px\">\n";
						echo "	<div style=\"font-side: 13px; font-weight: bold\">No Matching Teaching Events</div>\n";
						echo "	There are no teaching events found which contain matches to &quot;<strong>".html_encode($SEARCH_QUERY)."</strong>&quot;.";
						if (($SEARCH_CLASS) || ($SEARCH_YEAR) || ($SEARCH_ORGANISATION)) {
							echo "<br /><br />\n";
							echo "You may wish to try modifying or removing the Cohort or Academic Year limiters.\n";
						}
						echo "</div>\n";
					} else {
						echo "<div class=\"display-error\" style=\"margin-top: 20px; padding: 15px\">\n";
						echo "	<div style=\"font-side: 13px; font-weight: bold\">Invalid Search Term</div>\n";
						echo "	The search term which you have provided &quot;<strong>".html_encode($SEARCH_QUERY)."</strong>&quot; must be at least 4 characters long in order to perform an accurate search.";
						echo "</div>\n";
					}
				}
			break;
		}
	}
}
