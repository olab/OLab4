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
 * This file is used to add events to the entrada.events table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_REPORTS"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->amIAllowed('report', 'read', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[]			= array("url" => "", "title" => "Podcast Usage Report" );
	
	$TOP_PODCASTS_NUMBER	= 25;	// How many Top Podcasts do you want to see?
	$TOP_PODCAST_LISTENERS	= 25;	// How many Top Podcast Downloaders do you want to see?
	
	$podcast_stats						= array();
	$podcast_stats["total_podcasts"]	= 0;
	$podcast_stats["total_hits"]		= 0;
	$podcast_stats["min_hits"]			= array();
	$podcast_stats["max_hits"]			= array();
	$podcast_stats["mean"]				= 0;
	$podcast_stats["median"]			= 0;
	$podcast_stats["mode"]				= 0;
	$podcast_stats["hit_frequency"]		= array();		
	$podcast_stats["top_podcasts"]		= array();
	$podcast_stats["top_podcast_listeners"] = array();
	
	$podcast_tmp_hits					= array();
	$podcast_tmp_records				= array();
	$podcast_tmp_frequency				= array();
	?>
	<div class="no-printing">
		<form action="<?php echo ENTRADA_URL; ?>/admin/reports?section=<?php echo $SECTION; ?>&step=2" method="post">
		<table style="width: 100%" cellspacing="0" cellpadding="2" border="0">
		<colgroup>
			<col style="width: 3%" />
			<col style="width: 20%" />
			<col style="width: 77%" />
		</colgroup>
		<tbody>
			<tr>
				<td colspan="3"><h2>Reporting Dates</h2></td>
			</tr>
			<?php echo generate_calendars("reporting", "Reporting Date", true, true, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"], true, true, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"]); ?>
			<tr>
				<td colspan="3" style="text-align: right; padding-top: 10px"><input type="submit" class="btn btn-primary" value="Create Report" /></td>
			</tr>
		</tbody>
		</table>
		</form>
	</div>
	<?php
	if($STEP == 2) {
		
	?>
<h1>Podcast Usage Report</h1>
	<div class="content-small" style="margin-bottom: 10px">
		<strong>Date Range:</strong> <?php echo date(DEFAULT_DATE_FORMAT, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"]); ?> <strong>to</strong> <?php echo date(DEFAULT_DATE_FORMAT, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"]); ?>
	</div>

	<?php
	/**
	 * Total podcasts available in selected duration.
	 * Total number of podcasts downloaded in selected duration.
	 * Minimum times a podcast has been downloaded.
	 * Maximum times a podcast has been downloaded.
	 * Mean of downloads per podcast.
	 * Median of downloads per podcast.
	 * Mode of downloads per podcast.
	 * Top 25 Downloaded Podcasts
	 * 
	 */
	$organisation_where = " AND (e.`organisation_id` = ".$ENTRADA_USER->getActiveOrganisation().") ";

	$query		= "
				SELECT a.`accesses`, a.`efile_id` AS `podcast_id`, a.`event_id`, a.`file_name`, b.`event_title`, b.`event_start`, c.`audience_value` AS `event_cohort`
				FROM `event_files` AS a
				LEFT JOIN `events` AS b
				ON b.`event_id` = a.`event_id`
				LEFT JOIN `event_audience` AS c
				ON c.`event_id` = a.`event_id`
				LEFT JOIN `courses` as e
				ON e.`course_id` = b.`course_id`
				WHERE a.`file_category` = 'podcast'
				AND e.`course_active` = '1'
				AND (b.`event_start` BETWEEN ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"])." AND ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"]).")
				AND c.`audience_type` = 'cohort'".$organisation_where;
	
	$results	= $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
	if($results) {
		$podcast_stats["total_podcasts"] = @count($results);
		
		foreach($results as $key => $result) {
			if((!isset($podcast_stats["min_hits"]["accesses"])) || ((int) $result["accesses"] < (int) $podcast_stats["min_hits"]["accesses"])) {
				$podcast_stats["min_hits"] = $result;
			}

			if((!isset($podcast_stats["max_hits"]["accesses"])) || ((int) $result["accesses"] > (int) $podcast_stats["max_hits"]["accesses"])) {
				$podcast_stats["max_hits"] = $result;
			}

			/**
			 * This allows me to get the Top 25 podcasts a bit later on.
			 */
			$podcast_tmp_hits[$key]		= (int) $result["accesses"];
			$podcast_tmp_records[$key]	= $result;
		}

		$podcast_stats["total_hits"] = array_sum($podcast_tmp_hits);
		
		/**
		 * Since I can't use array_slice because it resets keys before PHP5,
		 * I have to do this. I know... I don't want it this way either.
		 */
		arsort($podcast_tmp_hits);
		$i = 0;
		foreach($podcast_tmp_hits as $key => $value) {
			$i++;

			if($i > $TOP_PODCASTS_NUMBER) {
				break;	
			}
			
			$podcast_stats["top_podcasts"][] = $podcast_tmp_records[$key];
		}
		
		$podcast_stats["mean"]		=  ($podcast_stats["total_hits"] / $podcast_stats["total_podcasts"]);
		$podcast_stats["median"]	= $podcast_tmp_hits[(int) ($podcast_stats["total_podcasts"] / 2)];
		
		/**
		 * Calculate mode by determining the frequency of accesses.
		 */
		foreach($podcast_tmp_hits as $key => $value) {
			$podcast_stats["hit_frequency"][$value]++;
		}
		
		arsort($podcast_stats["hit_frequency"]);
		
		$podcast_tmp_frequency	= array_keys($podcast_stats["hit_frequency"]);
		
		$podcast_stats["mode"]	= array("hits" => $podcast_tmp_frequency[0], "frequency" => $podcast_stats["hit_frequency"][$podcast_tmp_frequency[0]]);
		
		/**
		 * Free some memory perhaps.
		 */
		unset($results, $podcast_tmp_hits, $podcast_tmp_records);
		
		/**
		 * Top 25 Podcast Downloaders and their deets b.
		 */
		$query		= "
					SELECT COUNT(a.`proxy_id`) AS `podcasts_downloaded`, COUNT(DISTINCT a.`module`) AS `access_methods`, a.`module`, e.`username`, e.`firstname`, e.`lastname`, c.`role`, c.`group`
					FROM `statistics` AS a
					LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS e
					ON e.`id` = a.`proxy_id`
					LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS c
					ON c.`user_id` = a.`proxy_id`
					LEFT JOIN `event_files` AS d
					ON d.`efile_id` = a.`action_value`
					AND c.`app_id` = ".$db->qstr(AUTH_APP_ID)."
					WHERE a.`timestamp` BETWEEN ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"])." AND ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"])."
					AND (a.`module` = 'events' OR a.`module` = 'podcasts')
					AND a.`action` = 'file_download'
					AND a.`action_field` = 'file_id'
					AND c.`app_id` = ".$db->qstr(AUTH_APP_ID).$organisation_where."
					AND d.`file_category` = 'podcast'
					GROUP BY a.`proxy_id`
					ORDER BY `podcasts_downloaded` DESC
					LIMIT 0, ".(int) $TOP_PODCAST_LISTENERS;
		$podcast_stats["top_podcast_listeners"] = $db->CacheGetAll($query);
	}
	
	if((int) $podcast_stats["total_podcasts"]) {
		?>
		<table style="width: 100%" cellspacing="2" cellpadding="1" summary="Podcast Reporting">
		<colgroup>
			<col style="width: 4%" />
			<col style="width: 60%" />
			<col style="width: 36%" />
		</colgroup>
		<tbody>
			<tr>
				<td colspan="3">
					<a name="general-stats"></a><h2>General Podcast Statistics</h2>			
				</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>Total podcast files in system during selected duration:</td>
				<td><?php echo (int) $podcast_stats["total_podcasts"]; ?></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>Total podcast downloads during selected duration:</td>
				<td><?php echo (int) $podcast_stats["total_hits"]; ?></td>
			</tr>
			<tr>
				<td colspan="3">
					<a name="detailed-stats"></a><h2>Detailed Download Statistics</h2>			
				</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>Minimum times a podcast was downloaded:</td>
				<td><?php echo (int) $podcast_stats["min_hits"]["accesses"]; ?> <span class="content-small">(<?php echo $min_freq = $podcast_stats["hit_frequency"][$podcast_stats["min_hits"]["accesses"]]; ?> time<?php echo (($min_freq != 1) ? "s" : ""); ?>)</span></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>Maximum times a podcast was downloaded:</td>
				<td><?php echo (int) $podcast_stats["max_hits"]["accesses"]; ?> <span class="content-small">(<?php echo $max_freq = $podcast_stats["hit_frequency"][$podcast_stats["max_hits"]["accesses"]]; ?> time<?php echo (($max_freq != 1) ? "s" : ""); ?>)</span></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>Mean (average) a podcast is downloaded:</td>
				<td><?php echo round($podcast_stats["mean"], 2); ?></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>Median a podcast is downloaded:</td>
				<td><?php echo $podcast_stats["median"]; ?></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>Mode a podcast is downloaded:</td>
				<td><?php echo (int) $podcast_stats["mode"]["hits"]; ?> <span class="content-small">(<?php echo $podcast_stats["mode"]["frequency"]; ?> time<?php echo (($podcast_stats["mode"]["frequency"] != 1) ? "s" : ""); ?>)</span></td>
			</tr>
			<tr>
				<td colspan="3">
					<a name="top-podcasts"></a><h2>Top <?php echo $TOP_PODCASTS_NUMBER; ?> Podcast Events</h2>			
				</td>
			</tr>
			<?php
			if((is_array($podcast_stats["top_podcasts"])) && (@count($podcast_stats["top_podcasts"]))) {
				foreach($podcast_stats["top_podcasts"] as $key => $result) {
					echo "<tr>\n";
					echo "	<td>".($key + 1).".</td>\n";
					echo "	<td>\n";
					echo "		<a href=\"".ENTRADA_URL."/events?id=".$result["event_id"]."\" style=\"font-weight: bold\" target=\"_blank\">".html_encode(limit_chars($result["event_title"], 50))."</a> <span class=\"content-small\">(".(int) $result["accesses"]." time".(($result["accesses"] != 1) ? "s" : "").")</span>\n";
					echo "		<div class=\"content-small\">Event on ".date(DEFAULT_DATE_FORMAT, $result["event_start"])."; Class of ".$result["event_cohort"]."</div>\n";
					echo "	</td>\n";
					echo "	<td style=\"vertical-align: top\"><a href=\"".ENTRADA_URL."/file-event.php?id=".$result["podcast_id"]."\" style=\"font-size: 11px\">".html_encode($result["file_name"])."</a></td>\n";
					echo "</tr>\n";
				}
			} else {
				?>
				<tr>
					<td colspan="3">
						<?php echo display_notice(array("The top podcasts are not currently available. There are podcasts in the system; however, it doesn't appear anyone has downloaded them.")); ?>
					</td>
				</tr>
				<?php	
			}
			?>
			<tr>
				<td colspan="3">
					<a name="top-downloaders"></a><h2>Top <?php echo $TOP_PODCASTS_NUMBER; ?> Podcast Downloaders</h2>			
				</td>
			</tr>
			<?php
			if((is_array($podcast_stats["top_podcast_listeners"])) && (@count($podcast_stats["top_podcast_listeners"]))) {
				foreach($podcast_stats["top_podcast_listeners"] as $key => $result) {
					echo "<tr>\n";
					echo "	<td>".($key + 1).".</td>\n";
					echo "	<td>\n";
					echo "		<a href=\"".ENTRADA_URL."/people?profile=".html_encode($result["username"])."\" style=\"font-weight: bold\" target=\"_blank\">".html_encode($result["firstname"]." ".$result["lastname"])."</a> <span class=\"content-small\">(".(int) $result["podcasts_downloaded"]." download".(($result["podcasts_downloaded"] != 1) ? "s" : "").")</span>\n";
					echo "		<div class=\"content-small\">\n";
									switch($result["group"]) {
										case "student" :
											echo "Student, Class of ".$result["role"];
										break;
										case "resident" :
											echo "Resident";
										break;
										case "alumni" :
											echo "Alumni";
											if((int) $result["role"]) {
												echo ", Class of ".$result["role"];
											}
										break;
										case "faculty" :
											echo "Faculty Member";
										break;
										case "staff" :
											echo "Staff Member";
										break;
										case "medtech" :
											echo "MEdTech Staff Member";
										break;
										default :
											echo "Unspecified Role";
										break;
									}
					echo "		</div>\n";
					echo "	</td>\n";
					echo "	<td style=\"vertical-align: top\">Accesses via ".(($result["access_methods"] == 1) ? (($result["module"] == "podcasts") ? "iTunes" : "File Download") : "iTunes &amp; File Download").".</td>\n";
					echo "</tr>\n";
				}
			} else {
				?>
				<tr>
					<td colspan="3">
						<?php echo display_notice(array("The top podcast download stats are not currently available. There are podcasts in the system; however, it doesn't appear anyone has downloaded them.")); ?>
					</td>
				</tr>
				<?php	
			}
			?>
		</tbody>
		</table>
		<?php
		$sidebar_html  = "<ul class=\"menu\">\n";
		$sidebar_html .= "	<li class=\"link\"><a href=\"#general-stats\" title=\"General Podcast Statistics\">General Podcast Statistics</a></li>\n";
		$sidebar_html .= "	<li class=\"link\"><a href=\"#detailed-stats\" title=\"Detailed Download Statistics\">Detailed Download Statistics</a></li>\n";
		$sidebar_html .= "	<li class=\"link\"><a href=\"#top-podcasts\" title=\"Top Podcast Events\">Top Podcast Events</a></li>\n";
		$sidebar_html .= "	<li class=\"link\"><a href=\"#top-downloaders\" title=\"Top Podcast Downloaders\">Top Podcast Downloaders</a></li>\n";
		$sidebar_html .= "</ul>";
		new_sidebar_item("Report Sections", $sidebar_html, "report-sections", "open");
			
	} else {
		$NOTICE++;
		$NOTICESTR[] = "There are no podcasts in the system during the selected duration or belonging to the selected organisation. Please choose a new date range and/or a different organisation and try again.";
		
		echo display_notice();
	}
	}
}
?>