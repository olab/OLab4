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
 * A utility that allows local users to reset their Entrada password if they
 * have forgotten it.
 *
 * 1. The user enters their e-mail address into the form.
 * 2. The system checks to ensure valid address, then sends a password reset e-mail to the address.
 * 3. The user clicks the link in the e-mail that contains their e-mail address and a hash.
 * 4. The system validates the e-mail address and hash combination, and if they match allows the user to change the password.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if (!defined("PARENT_INCLUDED")) exit;

$BREADCRUMB[] = array("url" => ENTRADA_RELATIVE . "/", "title" => APPLICATION_NAME);
$BREADCRUMB[] = array("url" => ENTRADA_RELATIVE . "/password_reset", "title" => "Password Reset");

if (!isset($_SESSION["reset_page_accesses"])) {
    $_SESSION["reset_page_accesses"] = 1;
} else {
    $_SESSION["reset_page_accesses"]++;
}

/**
 * Fetch the hash from the URL if it exists.
 */
$hash = (isset($_GET["hash"]) && ($tmp_input = clean_input($_GET["hash"], array("notags", "nows"))) ? $tmp_input : false);

/**
 * Fetch the e-mail address from the form post if it exists.
 */
if (isset($_POST["email_address"]) && valid_address($_POST["email_address"]) && ($tmp_input = clean_input($_POST["email_address"]))) {
    $email_address = $tmp_input;
} else {
    $email_address = false;
}
?>

<h1><?php echo APPLICATION_NAME; ?> Password Reset</h1>

<?php
if ($hash) {
    if (isset($_POST["npassword1"]) && isset($_POST["npassword2"])) {
        $STEP = 4;
    } else {
        $STEP = 3;
    }
} else if ($email_address) {
    $STEP = 2;
} else {
    $STEP = 1;
}

/**
 * Check to see if the user is currently logged in, has too many accesses of
 * the password reset page (which could indicate brute force).
 */
if (isset($_SESSION["isAuthorized"]) && (bool) $_SESSION["isAuthorized"]) {
    add_error("You cannot reset your password using the password reset tool if you are currently logged into the system.<br /><br />If you would like to change your password, please click the <a href=\"".ENTRADA_RELATIVE."/profile\">My Profile</a> link at the top of the page.");

    echo display_error();
} elseif ($_SESSION["reset_page_accesses"] > 25) {
    /**
     * Really basic way of attempting to prevent unsophisticated brute force
     * attempts.
     */
    add_error("You appear to having trouble resetting your ".APPLICATION_NAME." password. Please contact the service desk for assistance logging in.");

    echo display_error();
} else {
    // Error Checking Step
    switch ($STEP) {
        case 4 :
            $pieces = explode(":", rawurldecode($hash));
            if ($pieces && is_array($pieces) && (count($pieces) == 2)) {
                $proxy_id = clean_input($pieces[0], "int");
                $hash = clean_input($pieces[1], "alphanumeric");

                $query = "SELECT *
                            FROM `".AUTH_DATABASE."`.`password_reset`
                            WHERE `user_id` = ".$db->qstr($proxy_id)."
                            AND `hash` = ".$db->qstr($hash);
                $result = $db->GetRow($query);
                if ($result) {
                    if (!(int) $result["complete"]) {
                        $query = "SELECT `username`, `firstname`, `lastname`, `email`
                                    FROM `".AUTH_DATABASE."`.`user_data`
                                    WHERE `id` = ".$db->qstr($proxy_id);
                        $result = $db->GetRow($query);
                        if ($result) {
                            $firstname = $result["firstname"];
                            $lastname = $result["lastname"];
                            $username = $result["username"];
                            $email_address = $result["email"];

                            /**
                             * If the user is changing the password, then proceed.
                             */
                            if (isset($_POST["npassword1"]) && ($tmp_input = clean_input($_POST["npassword1"]))) {
                                $password = $tmp_input;

                                if (isset($_POST["npassword2"]) && ($tmp_input = clean_input($_POST["npassword2"]))) {
                                    $password2 = $tmp_input;

                                    if ($password == $password2) {
                                        if ((strlen($password) < 6) || (strlen($password) > 48)) {
                                            add_error("Your new password must be between 6 and 48 characters in length.");
                                        }
                                    } else {
                                        add_error("Your new passwords do not match, please re-enter your new password.");
                                    }
                                } else {
                                    add_error("Please be sure to re-enter the new password for your account.");
                                }
                            } else {
                                add_error("Please be sure to enter the new password for your account.");
                            }

                            if ($ERROR) {
                                $STEP = 3;
                            } else {
                                $salt = hash("sha256", (uniqid(rand(), 1) . time() . $result["id"]));
                                $query = "UPDATE `".AUTH_DATABASE."`.`user_data`
                                            SET `password` = ".$db->qstr(sha1($password.$salt)).", `salt` = ".$db->qstr($salt)."
                                            WHERE `id` = ".$db->qstr($proxy_id)."
                                            AND `username` = ".$db->qstr($username);
                                if ($db->Execute($query)) {
                                    $query = "UPDATE `".AUTH_DATABASE."`.`password_reset`
                                                SET `complete` = '1'
                                                WHERE `user_id` = ".$db->qstr($proxy_id)."
                                                AND `hash` = ".$db->qstr($hash);
                                    if (!$db->Execute($query)) {
                                        application_log("error", "Unable to set the password complete status to 1. Database said: ".$db->ErrorMsg());
                                    }

                                    $message  = "Hello ".$firstname." ".$lastname.",\n\n";
                                    $message .= "Your ".APPLICATION_NAME." username is: ".$username."\n\n";
                                    $message .= "This is an automated e-mail to inform you that your ".APPLICATION_NAME." password\n";
                                    $message .= "has been successfully changed. No further action is needed, this message\n";
                                    $message .= "is for your information only.\n\n";
                                    $message .= "If you did not change the password for this account and you believe there\n";
                                    $message .= "has been a mistake, please forward this message along with a description of\n";
                                    $message .= "the problem to: ".$AGENT_CONTACTS["administrator"]["email"]."\n\n";
                                    $message .= "Best Regards,\n";
                                    $message .= $AGENT_CONTACTS["administrator"]["name"]."\n";
                                    $message .= $AGENT_CONTACTS["administrator"]["email"]."\n";
                                    $message .= ENTRADA_URL."\n\n";
                                    $message .= "Requested By:\t".$_SERVER["REMOTE_ADDR"]."\n";
                                    $message .= "Requested At:\t".date("r", time())."\n";

                                    try {
                                        $mail = new Zend_Mail(DEFAULT_CHARSET);

                                        $mail->addHeader("X-Priority", "3");
                                        $mail->addHeader("Content-Transfer-Encoding", "8bit");
                                        $mail->addHeader("X-Originating-IP", $_SERVER["REMOTE_ADDR"]);
                                        $mail->addHeader("X-Section", "Password Reset Outcome");

                                        $mail->addTo($email_address, $firstname." ".$lastname);
                                        $mail->setFrom($AGENT_CONTACTS["administrator"]["email"], $AGENT_CONTACTS["administrator"]["name"]);
                                        $mail->setSubject("Password Reset Outcome - ".APPLICATION_NAME." Authentication System");
                                        $mail->setReplyTo($AGENT_CONTACTS["administrator"]["email"], $AGENT_CONTACTS["administrator"]["name"]);
                                        $mail->setBodyText($message);

                                        if (!$mail->send()) {
                                            application_log("error", "Unable to send the password reset outcome e-mail to [".$email_address."] as Zend_Mail's send function failed.");
                                        }
                                    } catch(Zend_Mail_Transport_Exception $e ) {
                                        add_error("We were unable to send you a password reset authorization e-mail at this time due to an unrecoverable error. The administrator has been notified of this error and will investigate the issue shortly.<br /><br />Please try again later, we apologize for any inconvenience this may have caused.");

                                        application_log("error", "Unable to send password reset outcome e-mail to [".$email_address."] as Zend_Mail's send function failed: ".$e->getMessage());
                                    }

                                    $_SESSION = array();
                                    @session_destroy();

                                    add_success("<strong>Your ".APPLICATION_NAME." password has been reset.</strong><br /><br />A notification e-mail with the result of this process has also been sent to <a href=\"mailto:".html_encode($email_address)."\">".html_encode($email_address)."</a>. You can now <a href=\"".ENTRADA_URL."\"><strong>return to the login page</strong></a> to log into ".APPLICATION_NAME.".");

                                    application_log("success", "Username: [".$username." / ".$proxy_id."] reset their password.");
                                } else {
                                    $STEP = 1;

                                    add_error("We were unable to complete your password reset request at this time, please try again later.<br /><br />The administrator has been informed of this error and will investigate promptly.");

                                    application_log("error", "Unable to reset the password because of an update failure. Database said: ".$db->ErrorMsg());
                                }
                            }
                        } else {
                            $STEP = 1;

                            add_error("Unfortunately we were unable to proceed with resetting this password, please submit a new <a href=\"".ENTRADA_URL."/password_reset\">password reset request</a>.");

                            application_log("error", "There was a problem with a password reset entry. Proxy ID: [".$proxy_id."], Hash: [".$hash."]");
                        }
                    } else {
                        $STEP = 1;

                        add_error("<strong>Your password has already been reset.</strong><br /><br />If you have forgotten your password again, please submit a new <a href=\"".ENTRADA_URL."/password_reset\">password reset request</a>.");

                        application_log("error", "Password has already been reset but is hitting step 4 still. Hash: ".$hash);
                    }
                } else {
                    $STEP = 1;

                    add_error("<strong>There is a problem with the provided hash code.</strong><br /><br />If you are trying to reset your ".APPLICATION_NAME." password, copy and paste the entire link from the e-mail you have received into the browsers' location bar. Sometimes if you click the link from your e-mail client it may not include the entire address.");

                    application_log("error", "A bad hash code is hitting step 4. Hash: ".$hash);
                }
            } else {
                $STEP = 1;

                add_error("<strong>There is a problem with the provided hash code.</strong><br /><br />If you are trying to reset your ".APPLICATION_NAME." password, copy and paste the entire link from the e-mail you have received into the browser location bar. Sometimes if you click the link from your e-mail client it may not include the entire address.");

                application_log("error", "A bad hash code is hitting step 4. Hash: ".$hash);
            }
        break;
        case 3 :
            $pieces = explode(":", rawurldecode($hash));
            if ($pieces && is_array($pieces) && (count($pieces) == 2)) {
                $proxy_id = clean_input($pieces[0], "int");
                $hash = clean_input($pieces[1], "alphanumeric");

                $query = "SELECT b.`username`, b.`firstname`, b.`lastname`, a.*
                            FROM `".AUTH_DATABASE."`.`password_reset` AS a
                            JOIN `".AUTH_DATABASE."`.`user_data` AS b
                            ON b.`id` = a.`user_id`
                            WHERE a.`user_id` = ".$db->qstr($proxy_id)."
                            AND a.`hash` = ".$db->qstr($hash);
                $result = $db->GetRow($query);
                if ($result) {
                    if (!(int) $result["complete"]) {
                        $username = $result["username"];
                        $firstname = $result["firstname"];
                        $lastname = $result["lastname"];
                    } else {
                        add_error("<strong>Your password has already been reset.</strong><br /><br />If you have forgotten your password again, please <a href=\"".ENTRADA_RELATIVE."/password_reset\">click here</a> to start the password reset process again.");
                    }
                } else {
                    add_error("<strong>There is a problem with the provided hash code.</strong><br /><br />If you are trying to reset your ".APPLICATION_NAME." password, copy and paste the entire link from the e-mail you have received into the browsers' location bar. Sometimes if you click the link from your e-mail client it may not include the entire address.");

                    application_log("error", "A bad hash code is hitting step 3. Hash: ".$hash);
                }
            } else {
                add_error("<strong>There is a problem with the provided hash code.</strong><br /><br />If you are trying to reset your ".APPLICATION_NAME." password, copy and paste the entire link from the e-mail you have received into the browsers' location bar. Sometimes if you click the link from your e-mail client it may not include the entire address.");

                application_log("error", "A bad hash code is hitting step 3. Hash: ".$hash);
            }

            if ($ERROR) {
                $STEP = 1;
            }
        break;
        case 2 :
            $query  = "SELECT `id`, `username`, `email`, `firstname`, `lastname`
                        FROM `".AUTH_DATABASE."`.`user_data`
                        WHERE `email` = ".$db->qstr($email_address);
            $result = $db->GetRow($query);
            if ($result) {
                $proxy_id = (int) $result["id"];
                $username = $result["username"];
                $firstname = $result["firstname"];
                $lastname = $result["lastname"];

                $hash = get_hash();

                $processed = array();
                $processed["ip"] = $_SERVER["REMOTE_ADDR"];
                $processed["date"] = time();
                $processed["user_id"] = $proxy_id;
                $processed["hash"] = $hash;
                $processed["complete"] = 0;

                if ($db->AutoExecute("`".AUTH_DATABASE."`.`password_reset`", $processed, "INSERT")) {
                    $message  = "Hello ".$firstname." ".$lastname.",\n\n";
                    $message .= "This is an automated e-mail containing instructions to help you set or reset\n";
                    $message .= "your ".APPLICATION_NAME." password.\n\n";
                    $message .= "Your ".APPLICATION_NAME." username is: ".$username."\n\n";
                    $message .= "Please visit the following link to assign a new password to your account:\n";
                    $message .= ENTRADA_URL."/password_reset?hash=".rawurlencode($proxy_id.":".$hash)."\n\n";
                    $message .= "Please Note:\n";
                    $message .= "This password link will be valid for the next 3 days. If you do not reset your\n";
                    $message .= "password within this time period, you will need to reinitate this process.\n\n";
                    $message .= "If you did not request a password reset for this account and you believe\n";
                    $message .= "there has been a mistake, DO NOT click the above link. Please forward this\n";
                    $message .= "message along with a description of the problem to: ".$AGENT_CONTACTS["administrator"]["email"]."\n\n";
                    $message .= "Best Regards,\n";
                    $message .= $AGENT_CONTACTS["administrator"]["name"]."\n";
                    $message .= $AGENT_CONTACTS["administrator"]["email"]."\n";
                    $message .= ENTRADA_URL."\n\n";
                    $message .= "Requested By:\t".$_SERVER["REMOTE_ADDR"]."\n";
                    $message .= "Requested At:\t".date("r", time())."\n";

                    try {
                        $mail = new Zend_Mail();

                        $mail->addHeader("X-Priority", "3");
                        $mail->addHeader("Content-Transfer-Encoding", "8bit");
                        $mail->addHeader("X-Originating-IP", $_SERVER["REMOTE_ADDR"]);
                        $mail->addHeader("X-Section", "Password Reset");

                        $mail->addTo($email_address, $firstname." ".$lastname);
                        $mail->setFrom($AGENT_CONTACTS["administrator"]["email"], $AGENT_CONTACTS["administrator"]["name"]);
                        $mail->setSubject("Password Reset - ".APPLICATION_NAME." Authentication System");
                        $mail->setReplyTo($AGENT_CONTACTS["administrator"]["email"], $AGENT_CONTACTS["administrator"]["name"]);
                        $mail->setBodyText($message);

                        if ($mail->send()) {
                            add_success("An e-mail has just been sent to <strong>".html_encode($email_address)."</strong> that contains further instructions on resetting your ".APPLICATION_NAME." password. Please check your e-mail in a few minutes to proceed.");

                            application_log("notice", "A password reset e-mail has just been sent for ".$username." [".$proxy_id."].");

                            $email_address = "";
                        } else {
                            add_error("We were unable to send you a password reset authorization e-mail at this time due to an unrecoverable error. The administrator has been notified of this error and will investigate the issue shortly.<br /><br />Please try again later, we apologize for any inconvenience this may have caused.");

                            application_log("error", "Unable to send password reset notice as Zend_Mail's send function failed.");
                        }
                    } catch(Zend_Mail_Transport_Exception $e ) {
                        add_error("We were unable to send you a password reset authorization e-mail at this time due to an unrecoverable error. The administrator has been notified of this error and will investigate the issue shortly.<br /><br />Please try again later, we apologize for any inconvenience this may have caused.");

                        application_log("error", "Unable to send password reset notice as Zend_Mail's send function failed: ".$e->getMessage());
                    }

                    $_SESSION = array();
                    @session_destroy();
                } else {
                    add_error("We were unable to reset your password at this time due to an unrecoverable error. The administrator has been notified of this error and will investigate the issue shortly.<br /><br />Please try again later, we apologize for any inconvenience this may have caused.");

                    application_log("error", "Unable to insert password reset query into ".AUTH_DATABASE.".password_reset table. Database said: ".$db->ErrorMsg());
                }
            } else {
                add_error("Your e-mail address (<strong>".html_encode($email_address)."</strong>) could not be found in the system. Please be sure that you have entered your official e-mail address correctly.<br /><br />If you believe there is a problem please contact us for assistance: <a href=\"mailto:".$AGENT_CONTACTS["administrator"]["email"]."\">".$AGENT_CONTACTS["administrator"]["email"]."</a>");

                application_log("notice", "Unable to locate an e-mail address [".$email_address."] in the database to reset password.");
            }

            if ($ERROR) {
                $STEP = 1;
            }
        break;
        case 1 :
        default :
            continue;
        break;
    }

    // Page Display Step
    switch ($STEP) {
        case 4 :
            if ($ERROR) {
                echo display_error();
            }
            if ($NOTICE) {
                echo display_error();
            }
            if ($SUCCESS) {
                echo display_success();
            }
        break;
        case 3 :
            if ($ERROR) {
                echo display_error();
            }
            if ($NOTICE) {
                echo display_error();
            }
            if ($SUCCESS) {
                echo display_success();
            }
            ?>
            <div class="display-generic">
                Welcome to the password reset program <strong><?php echo html_encode($firstname); ?></strong>. Using the form below enter the new password that you would like to use for <?php echo APPLICATION_NAME; ?>. Please be aware that your password must be between 6 and 48 characters in length.
            </div>

            <form class="form-horizontal" action="<?php echo ENTRADA_RELATIVE; ?>/password_reset?hash=<?php echo rawurlencode($proxy_id.":".$hash); ?>" method="POST">
                <div class="control-group">
                    <label class="control-label">Username:</label>
                    <div class="controls">
                        <span class="input-xlarge uneditable-input"><?php echo html_encode($username); ?></span>
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label" for="npassword1">Enter New Password:</label>
                    <div class="controls">
                        <input class="input-xlarge" name="npassword1" id="npassword1" type="password" value="" />
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label" for="npassword2">Re-enter Password:</label>
                    <div class="controls">
                        <input class="input-xlarge" name="npassword2" id="npassword2" type="password" value="" />
                    </div>
                </div>

                <input type="submit" class="btn btn-primary" value="Change Password" /> or <a href="<?php echo ENTRADA_URL; ?>">Cancel</a>
            </form>
            <?php
        break;
        case 2 :
        case 1 :
        default :
            if ($ERROR) {
                echo display_error();
            }
            if ($NOTICE) {
                echo display_error();
            }
            if ($SUCCESS) {
                echo display_success();
            }
            ?>
            <div class="display-generic">
                To reset the local password associated with your <?php echo APPLICATION_NAME; ?> account please provide your official e-mail address in the text box below then click <strong>Continue</strong>. Further instructions on resetting your local password will be sent to you via e-mail.
            </div>

            <form class="form-horizontal" action="<?php echo ENTRADA_RELATIVE; ?>/password_reset" method="POST">
                <div class="control-group">
                    <label class="control-label" for="email_address"><strong>Official</strong> E-Mail Address:</label>
                    <div class="controls">
                        <input class="input-xlarge" name="email_address" id="email_address" type="text" placeholder="example@email.com" value="<?php echo $email_address; ?>" />
                        <input type="submit" class="btn btn-primary" value="Continue" />
                        <strong style="margin-left: 5px">or</strong> <a href="<?php echo ENTRADA_URL; ?>">Cancel</a>
                    </div>
                </div>
            </form>
            <?php
        break;
    }
}
