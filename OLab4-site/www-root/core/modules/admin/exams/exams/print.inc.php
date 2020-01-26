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
 * This file shows a print-friendly view of an exam.
 *
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Robert Fotino <robert.fotino@gmail.com>
 * @copyright Copyright 2016 UC Regents. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EXAMS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("exam", "create", false)) {
	add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    $SECTION_TEXT = $SUBMODULE_TEXT[$SECTION];
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/exams/exams/print.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/exams/print.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";

    if (isset($_GET["id"])) {
        $exam_id = (int)$_GET["id"];
    }
    $exam = Models_Exam_Exam::fetchRowByID($exam_id);
    if (!$exam) {
        add_error($SECTION_TEXT["errors"]["01"]);
        echo display_error();
    } else {
        $exam_view = new Views_Exam_Exam($exam);
        echo "<h1>" . $exam->getTitle() . "</h1>";
        echo $exam_view->examNavigationTabs($SECTION);
        $exam_elements = Models_Exam_Exam_Element::fetchAllByExamID($exam->getID());
        echo "<h2 class=\"hidden-print\">" .  $SECTION_TEXT["print_view"] . "</h2>";
        // Add up total exam points
        $total_exam_points = 0;
        if ($exam_elements && is_array($exam_elements)) {
            foreach ($exam_elements as $elem) {
                $total_exam_points += $elem->getAdjustedPoints();
            }
        }

        $exam->setTotalExamPoints($total_exam_points);

        // Create sidebar item
        $font_options = implode("\n", array_map(function($i) {
                            return "<option value=\"$i\">" . (int)($i * 100) . "%</option>";
                        }, range(1, 2, 0.25)));

        $sidebar_html = "<div>
                            <select id=\"update_fonts\">
                                " . $font_options . "
                            </select>
                             " . $SECTION_TEXT["font_size"] . "
                        </div>";

        if ($SECTION_TEXT["options"] && is_array($SECTION_TEXT["options"])) {
            foreach ($SECTION_TEXT["options"] as $hide_class => $option) {
                $sidebar_html .= "<div>\n";
                $sidebar_html .= "    <label>\n";
                $sidebar_html .= "        <input id=\"" . $hide_class. "\" type=\"checkbox\" class=\"hide_sections\" checked data-type=\"" . $hide_class ."\" />\n";
                $sidebar_html .= $option;
                $sidebar_html .= "    </label>\n";
                $sidebar_html .= "</div>\n";
            }
        }
        $sidebar_html .= "<div>\n";
        $sidebar_html .= "    <label>\n";
        $sidebar_html .= "        <input id=\"one_per_page\" type=\"checkbox\" class=\"\" data-type=\"one_per_page\" />\n";
        $sidebar_html .= "One Per Page";
        $sidebar_html .= "    </label>\n";
        $sidebar_html .= "</div>\n";

        $sidebar_html .= "<div>\n";
        $sidebar_html .= "    <label>\n";
        $sidebar_html .= "        <input id=\"repeat_question_stem\" type=\"checkbox\" class=\"\" data-type=\"repeat_question_stem\" />\n";
        $sidebar_html .= "Repeat Question Stem";
        $sidebar_html .= "    </label>\n";
        $sidebar_html .= "</div>\n";

        $print_button .= "<a class=\"no-printing btn btn-primary print_button pull-right\" href=\"#\">\n";
        $print_button .= $SECTION_TEXT["print"];
        $print_button .= "</a>\n";

        $sidebar_html .= $print_button;
        new_sidebar_item($SECTION_TEXT["print_options"], $sidebar_html, "", "open", 2);
        // Output printer friendly view
        $content = $exam_view->renderPrintView($exam_elements, $SECTION_TEXT);
        echo $content;
        echo $print_button;
    }
}