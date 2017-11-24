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
 * This section is loaded when an individual wants to attempt a quiz.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_PUBLIC_GRADEBOOK"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
}

if ($COURSE_ID) {
    $course_title = fetch_course_title($COURSE_ID);

  	$BREADCRUMB[] = array("url" => ENTRADA_URL."/profile/gradebook", "title" => $course_title);

	$group_ids = groups_get_enrolled_group_ids($ENTRADA_USER->getId());

	$group_ids_string = implode(', ',$group_ids);
	$query = "	SELECT b.*, c.*, d.`handler`, AVG(e.`value`) as `mean`
				FROM `courses` AS a
				JOIN `assessments` AS b
				ON a.`course_id` = b.`course_id`
				AND b.`cohort` IN(".$group_ids_string.")
				JOIN `assessment_grades` AS c
				ON b.`assessment_id` = c.`assessment_id`
				AND c.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
				JOIN `assessment_marking_schemes` AS d
				ON b.`marking_scheme_id` = d.`id`
                JOIN `assessment_grades` AS e
                ON b.`assessment_id` = e.`assessment_id`
				WHERE a.`course_id` = ".$db->qstr($COURSE_ID)."
				AND b.`active` = 1
				AND (b.`release_date` = '0' OR b.`release_date` <= ".$db->qstr(time()).")
				AND (b.`release_until` = '0' OR b.`release_until` >= ".$db->qstr(time()).")
				AND b.`show_learner` = '1'
                GROUP BY e.`assessment_id`
				ORDER BY `order` ASC";
	$results = $db->GetAll($query);
	if ($results) {
		?>
		<h1><?php echo $course_title; ?> Gradebook</h1>
		<table class="tableList" cellspacing="0" summary="List of Assessments">
			<colgroup>
				<col class="title" />
				<col class="assessment-type" />
				<col class="grade" />
                <?php
				if (defined("GRADEBOOK_DISPLAY_MEAN_GRADE") && GRADEBOOK_DISPLAY_MEAN_GRADE) {
                    ?>
    				<col class="grade" />
                    <?php
                }
                if (defined("GRADEBOOK_DISPLAY_WEIGHTED_TOTAL") && GRADEBOOK_DISPLAY_WEIGHTED_TOTAL) {
                    ?>
                    <col class="grade" />
                    <?php
                }
                ?>
				<col class="grade" />
			</colgroup>
			<thead>
				<tr>
					<td class="title borderl<?php echo (isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]) && ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "title") ? " sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]) : ""); ?>"><?php echo public_order_link("title", "Assessment Title", ENTRADA_RELATIVE."/profile/gradebook"); ?></td>
					<td class="assessment-type<?php echo (isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]) && ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "type") ? " sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]) : ""); ?>"><?php echo public_order_link("type", "Assessment Type", ENTRADA_RELATIVE."/profile/gradebook"); ?></td>
					<td class="grade">Your Mark</td>
                    <?php
                    if (defined("GRADEBOOK_DISPLAY_MEAN_GRADE") && GRADEBOOK_DISPLAY_MEAN_GRADE) {
                        ?>
                        <td class="grade">Class Mean</td>
                        <?php
                    }
                    if (defined("GRADEBOOK_DISPLAY_MEDIAN_GRADE") && GRADEBOOK_DISPLAY_MEDIAN_GRADE) {
                        ?>
                        <td class="grade">Class Median</td>
                        <?php
                    }
                    if (defined("GRADEBOOK_DISPLAY_WEIGHTED_TOTAL") && GRADEBOOK_DISPLAY_WEIGHTED_TOTAL) {
						?>
						<td class="grade">Weighted Mark</td>
						<?php
					}
					?>
					<td class="grade">Percent</td>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach ($results as $result) {
                add_statistic("gradebook", "view", "assessment_id", $result["assessment_id"]);
				if (isset($result["value"])) {
					$grade_value = format_retrieved_grade(round($result["value"], 2), $result);
				} else {
					$grade_value = "-";
				}
				if (isset($result["mean"])) {
					$mean_value = format_retrieved_grade(round($result["mean"], 2), $result);
				} else {
					$mean_value = "-";
				}
				echo "<tr id=\"gradebook-".$result["course_id"]."\">\n";
				echo "	<td>".html_encode($result["name"])."</td>\n";
				echo "	<td>".($result["type"])."</td>\n";
				echo "	<td>".trim($grade_value).assessment_suffix($result)."</td>\n";
				if (defined("GRADEBOOK_DISPLAY_MEAN_GRADE") && GRADEBOOK_DISPLAY_MEAN_GRADE) {
                    echo "	<td>".trim($mean_value).assessment_suffix($result)."</td>\n";
                }
				if (defined("GRADEBOOK_DISPLAY_MEDIAN_GRADE") && GRADEBOOK_DISPLAY_MEDIAN_GRADE) {
                    $query = "SELECT c.`value`
                                FROM `courses` AS a
                                JOIN `assessments` AS b
                                ON a.`course_id` = b.`course_id`
                                AND b.`cohort` IN(".$group_ids_string.")
                                JOIN `assessment_grades` AS c
                                ON b.`assessment_id` = c.`assessment_id`
                                JOIN `assessment_marking_schemes` AS d
                                ON b.`marking_scheme_id` = d.`id`
                                JOIN `assessment_grades` AS e
                                ON b.`assessment_id` = e.`assessment_id`
                                WHERE a.`course_id` = ".$db->qstr($COURSE_ID)."
                                AND b.`active` = 1
                                AND (b.`release_date` = '0' OR b.`release_date` <= ".$db->qstr(time()).")
                                AND (b.`release_until` = '0' OR b.`release_until` >= ".$db->qstr(time()).")
                                AND b.`assessment_id` = ".$db->qstr($result["assessment_id"])."
                                ORDER BY c.`value` ASC";
                    $all_grades = $db->GetAll($query);
                    if ($all_grades) {
                        $n = count($all_grades);
                        $h = intval($n / 2);

                        if($n % 2 == 0) { 
                            $median_value = ($all_grades[$h]["value"] + $all_grades[$h-1]["value"]) / 2; 
                        } else { 
                            $median_value = $all_grades[$h]["value"]; 
                        }
                        $median_value = format_retrieved_grade(round($median_value, 2), $result);
                    }
                    echo "	<td>".trim($median_value).assessment_suffix($result)."</td>\n";
                }
				if (defined("GRADEBOOK_DISPLAY_WEIGHTED_TOTAL") && GRADEBOOK_DISPLAY_WEIGHTED_TOTAL) {
					$gradebook = gradebook_get_weighted_grades($result["course_id"], $ENTRADA_USER->getCohort(), $ENTRADA_USER->getID(), $result["assessment_id"]);
					echo "	<td>".round(trim($gradebook["grade"]), 2)." / ".trim($gradebook["total"])."</td>\n";
				}
				echo "	<td style=\"text-align: right;\">".(($grade_value === "-") ? "-" : (($result["handler"] == "Numeric" ? ($result["value"] === "0" ? "0" : trim(trim(number_format(($grade_value / $result["numeric_grade_points_total"] * 100), 2), "0"), "."))."%" : (($result["handler"] == "Percentage" ? ("N/A") : $grade_value)))))."</td>\n";
				echo "</tr>\n";
			}
			?>
			</tbody>
		</table>
		<?php
	}else{
		echo display_notice("No grades are available for any assessments in this course.");
	}
} else {
	$ERROR++;
	$ERRORSTR[] = "In order to review a gradebook, you must provide a valid course identifier.";

	echo display_error();

	application_log("error", "Failed to provide an course_id [".$COURSE_ID."] when attempting to view a gradebook.");
}