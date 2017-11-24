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
 * @author Developer: Brandon Thorn <brandon.thorn@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_PUBLIC_GRADEBOOK"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
}

echo "<h1>" . $translate->_("My Assignments") . "</h1>";

/**
 * Update requested column to sort by.
 * Valid: date, teacher, title, phase
 */
if (isset($_GET["sb"])) {
	if (in_array(trim($_GET["sb"]), array("title", "code", "date"))) {
		$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] = trim($_GET["sb"]);
	}

	$_SERVER["QUERY_STRING"] = replace_query(array("sb" => false));
} else {
	if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"])) {
		$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] = "date";
	}
}

/**
 * Update requested order to sort by.
 * Valid: asc, desc
 */
if (isset($_GET["so"])) {
	$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"] = ((strtolower($_GET["so"]) == "desc") ? "desc" : "asc");

	$_SERVER["QUERY_STRING"] = replace_query(array("so" => false));
} else {
	if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"])) {
		$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"] = "desc";
	}
}

/**
 * Provide the queries with the columns to order by.
 */
switch ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]) {
	case "title" :
		$sort_by = "a.`assignment_title` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]);
	break;
	case "code" :
		$sort_by = "b.`course_code` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]);
	break;
	case "date" :
	default :
		$sort_by = "a.`due_date` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]);
	break;
}

$group_ids = groups_get_enrolled_group_ids($ENTRADA_USER->getId());
if ($group_ids) {
    $group_ids_string = implode(", ", $group_ids);
} else {
    $group_ids_string = "";
}

$cperiods = Models_Course_Audience::getAllByGroupIDProxyID($group_ids_string, $ENTRADA_USER->getID());
if ($cperiods) {
    $cperiod_ids = array();

    foreach ($cperiods as $cperiod) {
        $cperiod_ids[] = (int) $cperiod["cperiod_id"];
    }

    $cperiod_ids_string = implode(", ", $cperiod_ids);
} else {
    $cperiod_ids_string = "";
}

$courses = groups_get_enrolled_course_ids($ENTRADA_USER->getID());
if ($courses) {
    $courses_ids_string = implode(", ", $courses);
} else {
    $courses_ids_string = "";
}

if ($cperiod_ids_string && $courses_ids_string) {
    $assignments = Models_Assignment::getAllByCourseIDUserID($cperiod_ids_string, $courses_ids_string, $ENTRADA_USER->getID(), $ENTRADA_USER->getActiveOrganisation(), $sort_by);
    if ($assignments) {
        ?>
        <table class="table table-bordered table-striped" cellspacing="0" summary="List of Assignments">
            <colgroup>
                <col class="title" />
                <col class="general" />
                <col class="general" />
                <col class="date" />
            </colgroup>
            <thead>
                <tr>
                    <th class="title<?php echo(($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "title") ? " sorted" . strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]) : ""); ?>"><?php echo public_order_link("title", "Assignment Title", ENTRADA_RELATIVE . "/profile/gradebook/assignments"); ?></th>
                    <th class="general<?php echo(($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "code") ? " sorted" . strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]) : ""); ?>"><?php echo public_order_link("code", "Course", ENTRADA_RELATIVE . "/profile/gradebook/assignments"); ?></th>
                    <th class="general">Grade</th>
                    <th class="date<?php echo(($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "date") ? " sorted" . strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]) : ""); ?>"><?php echo public_order_link("date", "Due Date", ENTRADA_RELATIVE . "/profile/gradebook/assignments"); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php
            foreach ($assignments as $key => $result) {
                ?>
                <tr>
                    <td>
                        <a href="<?php echo ENTRADA_RELATIVE . "/" . $MODULE; ?>/gradebook/assignments?section=view&amp;assignment_id=<?php echo $result["assignment_id"]; ?>"><?php echo html_encode($result["name"]); ?></a>
                    </td>
                    <td><?php echo html_encode($result["course_code"]); ?></td>
                    <td><?php echo (isset($result["grade_value"]) && $result["show_learner"] ? $result["grade_value"] : $translate->_("N/A")); ?></td>
                    <td><?php echo ($result["due_date"] == 0 ? '-' : date(DEFAULT_DATE_FORMAT, $result["due_date"])); ?></td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
        <?php
    } else {
        ?>
        <div class="display-generic">
            <?php echo $translate->_("There are no assignments due for you at this time, please check back later."); ?>
        </div>
        <?php
    }
} else {
    ?>
    <div class="display-generic">
        <?php echo $translate->_("You are not presently enroled in any courses, so there are no assignments to display at this time."); ?>
    </div>
    <?php
}