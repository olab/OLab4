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
 * The default file that is loaded when /admin/courses/reports is accessed.
 *
 * @author Organisation: Queen's University
 * @author Unit: MEdTech
 * @author Developer: Don Zuiker <don.zuiker@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
*/
if (!defined("IN_COURSE_REPORTS")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed(new CourseContentResource($COURSE_ID, $ENTRADA_USER->getActiveOrganisation(), true), "update")) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] does not have access to this module [".$MODULE."]");
} else {
    $course = Models_Course::get($COURSE_ID);

    if ($course) {

        echo "<h1 id=\"page-top\">" . $course->getFullCourseTitle() . "</h1>";

        courses_subnavigation($course->toArray(),"reports");

        $query = "	SELECT *
                    FROM `course_lu_reports` a
                    JOIN `course_reports` b
                    ON a.`course_report_id` = b.`course_report_id`
                    WHERE b.`course_id` = " . $db->qstr($COURSE_ID) . "
                    ORDER BY a.`course_report_title` ASC";
        $reports = $db->getAll($query);
        ?>
        <h1 class="muted"><?php echo $translate->_("Course Reports"); ?></h1>
        <?php
        if ($reports) {
            ?>
            <ul>
            <?php
                foreach($reports as $report) {
                    ?>
                    <li>
                        <a href="<?php echo ENTRADA_URL . '/admin/courses/reports?section=' . $report["section"] . '&id=' . $COURSE_ID; ?>">
                            <?php echo $report["course_report_title"]; ?>
                        </a>
                    </li>
                    <?php
                }
            ?>
            </ul>
            <?php
        } else {
            $NOTICE++;
            $NOTICESTR[] = $translate->_("Your course has no reports to display.  You can add reports on the course Setup page.");
            echo display_notice();
        }
    } else {
        add_error("In order to edit a course reports you must provide a valid course identifier. The provided ID does not exist in this system.");

        echo display_error();
    }
}