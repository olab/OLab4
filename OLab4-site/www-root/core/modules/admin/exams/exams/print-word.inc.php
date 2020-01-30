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
 * This file creates a word doc version of the exam
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2017 Regents of The University of California. All Rights Reserved.
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
    $SECTION_TEXT = $SUBMODULE_TEXT["print"];

    if (isset($_GET["id"])) {
        $exam_id = (int)$_GET["id"];
    }
    $exam = Models_Exam_Exam::fetchRowByID($exam_id);
    if (!$exam) {
        add_error($SECTION_TEXT["errors"]["01"]);
        echo display_error();
    } else {
        $exam_view = new Views_Exam_Exam($exam);
        $exam_elements = Models_Exam_Exam_Element::fetchAllByExamID($exam->getID());
        // Add up total exam points
        $total_exam_points = 0;
        if ($exam_elements && is_array($exam_elements)) {
            foreach ($exam_elements as $elem) {
                $total_exam_points += $elem->getAdjustedPoints();
            }
        }

        $exam->setTotalExamPoints($total_exam_points);

        $file_name = $exam->getTitle() . ".docx";
        $temp_file = tempnam("", "htd");

        $images_removed = array();

        $render = $exam_view->renderWordExport($exam_elements, $SECTION_TEXT, $images_removed);

        if ($render && is_array($render)) {
            $phpWord = $render["phpWord"];
            $images_removed = $render["images_removed"];
        }

        $phpWord->setDefaultFontSize(12);

        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($temp_file);

        if ($images_removed && is_array($images_removed) && !empty($images_removed)) {
            foreach ($images_removed as $image) {
                unlink($image);
            }
        }

        $download = true;
        // Download the file:
        if ($download) {
            header('Content-Description: File Transfer');
            header("Content-Type: application/force-download");
            header("Content-Type: application/octet-stream");
            header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document'");
            header('Content-Disposition: attachment; filename="' . $file_name . '"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($temp_file));
            ob_clean();
            flush();
            $status = readfile($temp_file);
            unlink($temp_file);
            exit();
        }
    }
}