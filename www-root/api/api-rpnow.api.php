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
 * API to handle interaction with form components
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonatan Caraballo <jch9@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");
require_once("Classes/utility/Template.class.php");
require_once("Classes/utility/TemplateMailer.class.php");

if((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
} else {

    $request_method = strtoupper(clean_input($_SERVER["REQUEST_METHOD"], "alpha"));
	
	$request = ${"_" . $request_method};

    if (isset($request["post_id"]) && $tmp_input = clean_input($request["post_id"], array("trim", "int"))) {
        $post_id = $tmp_input;
    } else {
        add_error($translate->_("The Exam Post id was not provided or is not valid"));
    }

    if (isset($request["email_students"]) && (int) $request["email_students"] == 1) {
        $email_students = true;
        if (isset($request["message"]) && $tmp_input = clean_input($request["message"], array("trim"))) {
            $message = $tmp_input;
        } else {
            add_error($translate->_("The Message was not provided."));
        }

        if (isset($request["subject"]) && $tmp_input = clean_input($request["subject"], array("trim"))) {
            $subject = $tmp_input;
        } else {
            add_error($translate->_("The subject was not provided."));
        }
    } else {
        $email_students = false;
    }

    if (isset($request["email_directors"]) && (int) $request["email_directors"] == 1) {
        $email_directors = true;
    } else {
        $email_directors = false;
    }

    if (!$email_students && !$email_directors) {
        add_error($translate->_("Please select one email option."));
    }

	switch ($request_method) {
		case "POST" :
            switch ($request["method"]) {
                case "email-rpnow-code" :
                    if (!has_error()) {
                        $rp_now = Models_Secure_RpNow::fetchRowByPostID($post_id);
                        if ($rp_now) {
                            $fp = fopen('php://temp', 'w+');
                            fputcsv($fp, array("student_name" => "Student Name", "student_email" => "Student Email", "exam_code" => "Exam Code"));

                            $rp_now_users = Models_Secure_RpNowUsers::fetchAllByRpNowConfigID($rp_now->getID());
                            if ($rp_now_users) {
                                foreach ($rp_now_users as $rp_now_user) {
                                    if ($email_students) {
                                        $mail = new Zend_Mail();
                                        $mail->setFrom($AGENT_CONTACTS["administrator"]["email"], $AGENT_CONTACTS["administrator"]["name"]);
                                        $mail->setSubject($subject);

                                        $message_search = array(
                                            "%firstname%",
                                            "%lastname%",
                                            "%rp_now_url%",
                                            "%exam_code%",
                                            "%creator_firstname%",
                                            "%creator_lastname%",
                                            "%creator_email%"
                                        );

                                        $message_replace = array(
                                            $rp_now_user->getUser()->getFirstname(),
                                            $rp_now_user->getUser()->getLastname(),
                                            RP_NOW_DOWNLOAD_URL,
                                            $rp_now_user->getExamCode(),
                                            $ENTRADA_USER->getFirstname(),
                                            $ENTRADA_USER->getLastname(),
                                            $ENTRADA_USER->getEmail()
                                        );

                                        $final_message = str_ireplace($message_search, $message_replace, $message);
                                        $mail->setBodyText($final_message);
                                        $mail->addTo($rp_now_user->getUser()->getEmail(), $rp_now_user->getUser()->getFullname());

                                        if (!$mail->send()) {
                                            add_error(sprintf($translate->_("We could not send e-mail to %s. The MEdTech Unit has been informed of this problem", $rp_now_user->getUser()->getEmail())));
                                            application_log("error", $translate->_("Rp-Now E-mail failed to send."));
                                        }
                                    }
                                    if ($email_directors) {
                                        $row = array();
                                        $row["student_name"] = $rp_now_user->getUser()->getFullname(false);
                                        $row["student_email"] = $rp_now_user->getUser()->getEmail();
                                        $row["exam_code"] = $rp_now_user->getExamCode();
                                        fputcsv($fp, $row);
                                    }
                                }
                            }

                            if ($email_directors) {
                                $template = simplexml_load_file($ENTRADA_TEMPLATE->absolute() . "/email/notification-rpnow-directors.xml");

                                $mail = new Zend_Mail();
                                $mail->setType(Zend_Mime::MULTIPART_RELATED);
                                $mail->setFrom($AGENT_CONTACTS["administrator"]["email"], $AGENT_CONTACTS["administrator"]["name"]);
                                $mail->setSubject($template->template->subject);

                                $message_search = array("%creator_firstname%", "%creator_lastname%", "%creator_email%");
                                $message_replace = array($ENTRADA_USER->getFirstname(), $ENTRADA_USER->getLastname(), $ENTRADA_USER->getEmail());

                                $final_message = str_ireplace($message_search, $message_replace, $template->template->body);

                                $mail->setBodyText($final_message);

                                rewind($fp);

                                $csvData = stream_get_contents($fp);

                                $file = $mail->createAttachment($csvData);
                                $file->filename = $rp_now->getPost()->getExam()->getTitle() . "-" . date("Y-m-d") . ".csv";

                                $mail->addTo($ENTRADA_USER->getEmail(), $ENTRADA_USER->getFullname());

                                $target_type =  $rp_now->getPost()->getTargetType();
                                if ($target_type == "event") {
                                    $course_id = Models_Event::fetchRowByID($rp_now->getPost()->getTargetID())->getCourseID();
                                } else if ($target_type == "assessment") {
                                    $course_id = Models_Gradebook_Assessment::fetchRowByID($rp_now->getPost()->getTargetID())->getCourseID();
                                } else {
                                    $course_id = 0;
                                }
                                $directors = Models_Course_Contact::fetchAllByCourseIDContactType($course_id, "director");
                                foreach ($directors as $director) {
                                    $mail->addTo(Models_User::fetchRowByID($director->getProxyID())->getEmail(), Models_User::fetchRowByID($director->getProxyID())->getFullname());
                                }

                                if (!$mail->send()) {
                                    add_error($translate->_("We could not send e-mail notice to the directors. The MEdTech Unit has been informed of this problem"));

                                    application_log("error", $translate->_("E-mail notice failed to send."));
                                }
                            }
                        } else {
                            add_error($translate->_("Couldn't find RP-Now for this exam."));
                        }
                    }
                    if (!$ERROR) {
                        echo json_encode(array("status" => "success", "data" => $translate->_("Your email was sent successfully.")));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }

                    break;
            }
    }

    exit;

}
