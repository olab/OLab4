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
 * This is the default section that is loaded when the quizzes module is
 * accessed without a defined section.
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

echo "<h1>" . $translate->_("My Gradebooks") . "</h1>";

/**
 * Update requested column to sort by.
 * Valid: date, teacher, title, phase
 */
if (isset($_GET["sb"])) {
	if (in_array(trim($_GET["sb"]), array("code", "title", "assessments"))) {
		$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["gradebook"]["sb"] = trim($_GET["sb"]);
	}

	$_SERVER["QUERY_STRING"] = replace_query(array("sb" => false));
} else {
	if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["gradebook"]["sb"])) {
		$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["gradebook"]["sb"] = "code";
	}
}

/**
 * Update requested order to sort by.
 * Valid: asc, desc
 */
if (isset($_GET["so"])) {
	$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["gradebook"]["so"] = ((strtolower($_GET["so"]) == "desc") ? "desc" : "asc");

	$_SERVER["QUERY_STRING"] = replace_query(array("so" => false));
} else {
	if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["gradebook"]["so"])) {
		$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["gradebook"]["so"] = "desc";
	}
}

/**
 * Provide the queries with the columns to order by.
 */
switch ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["gradebook"]["sb"]) {
	case "title" :
		$sort_by = "a.`course_name` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["gradebook"]["so"]);
	break;
	case "assessments" :
		$sort_by = "`assessments` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["gradebook"]["so"]);
	break;
	case "code" :
	default :
		$sort_by = "a.`course_code` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["gradebook"]["so"]);
	break;
}

$group_ids = groups_get_enrolled_group_ids($ENTRADA_USER->getId());
if ($group_ids) {
    $group_ids_string = implode(', ', $group_ids);
} else {
    $group_ids_string = "";
}

if ($group_ids_string) {
    $query = "SELECT a.*, COUNT(b.`assessment_id`) AS `assessments`
                FROM `courses` AS a
                JOIN `assessments` AS b
                ON a.`course_id` = b.`course_id`
                AND b.`active` = 1
                AND b.`cohort` IN (" . $group_ids_string . ")
                AND (b.`release_date` = '0' OR b.`release_date` <= " . $db->qstr(time()) . ")
                AND (b.`release_until` = '0' OR b.`release_until` > " . $db->qstr(time()) . ")
                AND b.`show_learner` = '1'
                JOIN `assessment_grades` AS c
                ON b.`assessment_id` = c.`assessment_id`
                AND c.`proxy_id` = " . $db->qstr($ENTRADA_USER->getID()) . "
                GROUP BY a.`course_id`
                ORDER BY " . $sort_by;
    $results = $db->GetAll($query);
    if ($results) {
        ?>
        <table class="table table-bordered table-striped" cellspacing="0" summary="List of Gradebooks">
            <thead>
                <tr>
                    <th width="14%" class="date-small borderl<?php echo(($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["gradebook"]["sb"] == "code") ? " sorted" . strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["gradebook"]["so"]) : ""); ?>"><?php echo public_order_link("code", "Course Code", ENTRADA_RELATIVE . "/profile/gradebook"); ?></th>
                    <th class="title<?php echo(($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["gradebook"]["sb"] == "title") ? " sorted" . strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["gradebook"]["so"]) : ""); ?>"><?php echo public_order_link("title", "Course Title", ENTRADA_RELATIVE . "/profile/gradebook"); ?></th>
                    <th width="14%" class="general<?php echo(($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["gradebook"]["sb"] == "assessments") ? " sorted" . strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["gradebook"]["so"]) : ""); ?>"><?php echo public_order_link("assessments", "Assessments", ENTRADA_RELATIVE . "/profile/gradebook"); ?></th>
                    <?php
                    if (defined("GRADEBOOK_DISPLAY_WEIGHTED_TOTAL") && GRADEBOOK_DISPLAY_WEIGHTED_TOTAL) {
                        ?>
                        <th width="13%" class="general">Wgt. Total</th>
                        <?php
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
            <?php
            foreach ($results as $result) {
                echo "<tr id=\"gradebook-" . $result["course_id"] . "\"" . (!$result["course_active"] ? " class=\"disabled\"" : "") . ">\n";
                echo "  <td" . ((!$result["course_active"]) ? " class=\"disabled\"" : "") . "><a href=\"" . ENTRADA_URL . "/" . $MODULE . "/gradebook?section=view&amp;id=" . $result["course_id"] . "\">" . html_encode($result["course_code"]) . "</a></td>\n";
                echo "  <td" . ((!$result["course_active"]) ? " class=\"disabled\"" : "") . "><a href=\"" . ENTRADA_URL . "/" . $MODULE . "/gradebook?section=view&amp;id=" . $result["course_id"] . "\">" . html_encode($result["course_name"]) . "</a></td>\n";
                echo "  <td" . ((!$result["course_active"]) ? " class=\"disabled\"" : "") . "><a href=\"" . ENTRADA_URL . "/" . $MODULE . "/gradebook?section=view&amp;id=" . $result["course_id"] . "\">" . ($result["assessments"]) . "</a></td>\n";
                if (defined("GRADEBOOK_DISPLAY_WEIGHTED_TOTAL") && GRADEBOOK_DISPLAY_WEIGHTED_TOTAL) {
                    echo "<td>";
                    $gradebook = gradebook_get_weighted_grades($result["course_id"], $ENTRADA_USER->getCohort(), $ENTRADA_USER->getID());
                    if ($gradebook) {
                        echo round(trim($gradebook["grade"]), 2) . " / " . trim($gradebook["total"]) . "</td>\n";
                    } else {
                        echo "&nbsp;";
                    }
                    echo "</td>";
                }
                echo "</tr>";
            }
            ?>
            </tbody>
        </table>
        <?php
    } else {
        ?>
        <div class="display-generic">
            <?php echo $translate->_("There are no gradebook assessments to display in any of the courses you are enroled in at this time."); ?>
        </div>
        <?php
    }
} else {
    ?>
    <div class="display-generic">
        <?php echo $translate->_("You are not presently enroled in any courses, so there are no gradebook assessments to display at this time."); ?>
    </div>
    <?php
}
