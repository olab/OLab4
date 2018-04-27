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
 * Outputs the requested event file id to the users web browser.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Thaisa Almeida <trda@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 * 
*/

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/core",
    dirname(__FILE__) . "/core/includes",
    dirname(__FILE__) . "/core/library",
    dirname(__FILE__) . "/core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

if((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL.((isset($_SERVER["REQUEST_URI"])) ? "?url=".rawurlencode(clean_input($_SERVER["REQUEST_URI"], array("nows", "url"))) : ""));
    exit;
} else {
    $FILE_ID = 0;

    if ((isset($_GET["id"])) && ((int)trim($_GET["id"]))) {
        $FILE_ID = (int)trim($_GET["id"]);
    }

    if ($FILE_ID) {
        $result = Models_Exam_Exam_File::fetchRowByID($FILE_ID);
        if ($result) {
            if ($ENTRADA_ACL->amIAllowed(new ExamResource($result->getExamID(), true), "update")) {
                $filename = $result->getFileName();
                $filetype = $result->getFileType();
                $filesize = $result->getFileSize();

                if ((@file_exists(EXAM_STORAGE_PATH . "/" . $FILE_ID)) && (@is_readable(EXAM_STORAGE_PATH . "/" . $FILE_ID))) {
                    header("Pragma: public");
                    header("Expires: 0");
                    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                    header("Content-Type: application/force-download");
                    header("Content-Type: application/octet-stream");
                    header("Content-Type: " . $result->getFileType() . "");
                    header("Content-Disposition: attachment; filename=\"" . $result->getFileName() . "\"");
                    header("Content-Length: " . @filesize(EXAM_STORAGE_PATH . "/" . $FILE_ID));
                    header("Content-Transfer-Encoding: binary");

                    echo file_get_contents(EXAM_STORAGE_PATH . "/" . $FILE_ID, FILE_BINARY);
                    exit;
                } else {
                    $TITLE = sprintf($translate->_("Not Found: %s"), html_encode($result->getFileName()));
                    $BODY = display_notice(array(sprintf($translate->_("The file that you are trying to download (<strong>%s</strong>) does not exist in the filesystem."), html_encode($result->getFileName()))));

                    $template_html = fetch_template("global/external");
                    if ($template_html) {
                        echo str_replace(array("%DEFAULT_CHARSET%", "%ENTRADA_URL%", "%TITLE%", "%BODY%"), array(DEFAULT_CHARSET, ENTRADA_URL, $TITLE, $BODY), $template_html);
                    }
                    exit;
                }
            } else {
                $TITLE = $translate->_("Not Authorized");
                $BODY = display_notice(array($translate->_("The file that you are trying to access is only accessible by authorized users.")));

                $template_html = fetch_template("global/external");
                if ($template_html) {
                    echo str_replace(array("%DEFAULT_CHARSET%", "%ENTRADA_URL%", "%TITLE%", "%BODY%"), array(DEFAULT_CHARSET, ENTRADA_URL, $TITLE, $BODY), $template_html);
                }
                exit;
            }
        } else {
            $TITLE = $translate->_("Not Found");
            $BODY = display_notice(array($translate->_("The file you are trying to download does not exist in our system. This file may have been removed by the course director or system administrator or the file identifier may have been mistyped in the URL.")));

            $template_html = fetch_template("global/external");
            if ($template_html) {
                echo str_replace(array("%DEFAULT_CHARSET%", "%ENTRADA_URL%", "%TITLE%", "%BODY%"), array(DEFAULT_CHARSET, ENTRADA_URL, $TITLE, $BODY), $template_html);
            }
            exit;
        }
    } else {
        header("Location: ".ENTRADA_URL);
        exit;
    }
}