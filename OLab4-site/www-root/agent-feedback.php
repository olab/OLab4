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
 * This file is loaded when someone opens the Anonymous Feedback Agent.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
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

ob_start("on_checkout");

if((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"DTD/xhtml1-transitional.dtd\">\n";
	echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n";
	echo "<body>\n";
	echo "<script type=\"text/javascript\">\n";
	echo "alert('It appears as though your session has expired; you will now be taken back to the login page.');\n";
	echo "if(window.opener) {\n";
	echo "	window.opener.location = '".ENTRADA_URL.((isset($_SERVER["REQUEST_URI"])) ? "?url=".rawurlencode(clean_input($_SERVER["REQUEST_URI"], array("nows", "url"))) : "")."';\n";
	echo "	top.window.close();\n";
	echo "} else {\n";
	echo "	window.location = '".ENTRADA_URL.((isset($_SERVER["REQUEST_URI"])) ? "?url=".rawurlencode(clean_input($_SERVER["REQUEST_URI"], array("nows", "url"))) : "")."';\n";
	echo "}\n";
	echo "</script>\n";
	echo "</body>\n";
	echo "</html>\n";
	exit;
} else {

	$ENCODED_INFORMATION = "";

	if((isset($_GET["step"])) && ((int) trim($_GET["step"]))) {
		$STEP = (int) trim($_GET["step"]);
	}

	if(isset($_POST["enc"])) {
		$ENCODED_INFORMATION = trim($_POST["enc"]);
	} elseif(isset($_POST["action"])) {
		$ENCODED_INFORMATION = trim($_POST["enc"]);
	}

	if (isset($_POST["who"])) {
		$WHO = clean_input($_POST["who"], array("trim", "striptags"));
	} else {
		/*
		 * If $_POST["who"] is not set the file was opened in a window from a legacy call.
		 */
		$WHO = "system";
		?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head lang="en-US" dir="ltr">
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo DEFAULT_CHARSET; ?>" />
		<title>Feedback for MEdTech Central</title>
		<meta name="description" content="%DESCRIPTION%" />
		<meta name="keywords" content="%KEYWORDS%" />
		<meta name="author" content="Medical Education Technology Unit, Queen's University" />
		<meta name="copyright" content="Copyright (c) 2010 Queen's University. All Rights Reserved." />
		<meta name="robots" content="index,follow" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<link rel="shortcut icon" href="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/images/favicon.ico" />
		<link rel="icon" href="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/images/favicon.ico" type="image/x-icon" />
		<link href="<?php echo ENTRADA_RELATIVE; ?>/css/common.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" type="text/css" media="all" />
		<link href="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/css/bootstrap.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" type="text/css" media="all" />
        <script type="text/javascript" src="<?php echo ENTRADA_RELATIVE; ?>/javascript/jquery/jquery.min.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script type="text/javascript">
        jQuery(function(){
            jQuery(document).on("click", "input[value=Close]", function() {
                window.close();
            });
            jQuery("#feedback-form").on("click", "input[value=Submit]", function() {
                jQuery("#feedback-form").submit();
            });
        });
        </script>
	</head>
	<body>
		<?php
	}

	$feedback_form = $translate->_("global_feedback_widget");

	if (isset($feedback_form["global"][$WHO]["form"])) {
		$form_content = $feedback_form["global"][$WHO]["form"];
	} else if (isset($feedback_form[$ENTRADA_USER->getGroup()][$WHO]["form"])) {
		$form_content = $feedback_form[$ENTRADA_USER->getGroup()][$WHO]["form"];
	} else if (isset($feedback_form["clerkship"][$WHO]["form"])) {
		$form_content = $feedback_form["clerkship"][$WHO]["form"];
	} else {
		add_error("There was a problem loading the feedback form for the contact you selected. A system administrator has been informed, please try again later.");
	}

	if (!$ERROR) {

		switch($STEP) {
			case "2" :

				if (!empty($form_content["recipients"])) {
					if (isset($_POST["hide_identity"]) && $_POST["hide_identity"]) {
						$email_address = $AGENT_CONTACTS["administrator"]["email"];
						$fullname = "Anonymous Student";
					} else {
						$email_address = $_SESSION["details"]["email"];
						$fullname = $_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"];
					}

					$extracted_information	= false;
					$tmp_information		= @unserialize(@base64_decode($ENCODED_INFORMATION));

					if((@is_array($tmp_information)) && (@count($tmp_information))) {
						$extracted_information = $tmp_information;
						unset($tmp_information);
					}

                    $recipient_name = (isset($AGENT_CONTACTS["agent-anonymous-feedback"]["name"]) ? $AGENT_CONTACTS["agent-anonymous-feedback"]["name"] : APPLICATION_NAME." Administrator");
                    if (isset($form_content["recipients"]) && @count($form_content["recipients"]) == 1) {
                        foreach ($form_content["recipients"] as $recipient) {
                            $recipient_name = $recipient;
                        }
                    }

					$message  = "Attention ".$recipient_name."\n";
					$message .= "The following student feedback information has been submitted:\n";
					$message .= "=======================================================\n\n";
					$message .= "Submitted At:\t\t".date("r", time())."\n";
					$message .= "Student Feedback / Comments:\n";
					$message .= "-------------------------------------------------------\n";
					$message .= clean_input($_POST["feedback"], array("trim", "emailcontent"))."\n\n";
					$message .= "=======================================================";

                    $group = $ENTRADA_USER->getActiveGroup();
                    if (empty($group)) {
                        $group = "Student";
                    }
                    
					$mail = new Zend_Mail("iso-8859-1");

					$mail->addHeader("X-Priority", "3");
					$mail->setFrom($email_address, $fullname);
					$mail->addHeader("X-Originating-IP", $_SERVER["REMOTE_ADDR"]);
					$mail->addHeader("X-Section", "Student Feedback System");

					$mail->setSubject("New " . ucwords($group) . " Feedback Submission - ".APPLICATION_NAME);

					foreach ($form_content["recipients"] as $email => $name) {
						$mail->addTo($email, $name);
					}

					$message = "The following ".($WHO == "clerkship_professionalism" ? "Clerkship Professionalism Narrative" : "feedback information")." has been submitted:\n";
					$message .= "=======================================================\n\n";
					$message .= "Submitted At:\t\t".date("r", time())."\n";
					$message .= "Submitted By:\t\t".$fullname." [".((isset($_POST["hide_identity"])) ? "withheld" : $_SESSION["details"]["username"])."]\n";
					$message .= "E-Mail Address:\t\t".$email_address."\n\n";
					$message .= "Comments / Feedback:\n";
					$message .= "-------------------------------------------------------\n";
					$message .= clean_input($_POST["feedback"], array("trim", "emailcontent"))."\n\n";
                    if ($WHO == "system") {
					$message .= "Web-Browser / OS:\n";
					$message .= "-------------------------------------------------------\n";
					$message .= clean_input($_SERVER["HTTP_USER_AGENT"], array("trim", "emailcontent"))."\n\n";
					$message .= "URL Sent From:\n";
					$message .= "-------------------------------------------------------\n";
					$message .= ((isset($_SERVER["HTTPS"])) ? "https" : "http")."://".$_SERVER["HTTP_HOST"].clean_input($extracted_information["url"], array("trim", "emailcontent"))."\n\n";
                    }
					$message .= "=======================================================";

					$mail->setBodyText($message);
					if($mail->Send()) {
						echo "<h4>Feedback Submission Successful</h4>";
						add_success("Thank-you for providing us with your valuable feedback.<br /><br />Once again, thank-you for using our automated anonymous feedback system and feel free to submit comments any time.");
						echo display_success();
						echo "<div style=\"text-align:right;\"><input type=\"button\" class=\"btn\" value=\"Close\" /></a>";
					} else {
						add_error("We apologize however, we are unable to submit your feedback at this time due to a problem with the mail server.<br /><br />The system administrator has been informed of this error, please try again later.");
						echo display_error();
						application_log("error", "Unable to send anonymous feedback with the anonymous feedback agent.");
					}
				} else {
					add_error("We apologize however, we are unable to submit your feedback at this time due to a problem with the mail server.<br /><br />The system administrator has been informed of this error, please try again later.");
					echo display_error();
					application_log("error", "An error ocurred when trying to send feedback to agent [".$WHO."], no recipients found in language file.");
				}
			break;
			case "1" :
			default :
				?> 
		<style type="text/css">
		#feedback-form {padding:20px;margin:0px;}	
		#feedback-form h4 {margin-top:0px;}
		.feedback-title {margin:-5px;border-radius:0px;}
		#feedback {width:434px;}
		</style>

				<div class="panel-head feedback-title"><h3 class=""><?php echo $form_content["title"]; ?></h3></div>

				<form id="feedback-form" clsas="form form-horizontal" action="<?php echo ENTRADA_URL; ?>/agent-feedback.php?step=2" method="post">
				<?php if (isset($_POST["who"])) { ?><input type="hidden" name="who" value="<?php echo $WHO; ?>" /><?php } ?>
				<?php if (isset($_POST["enc"])) { ?><input type="hidden" name="enc" value="<?php echo $ENCODED_INFORMATION; ?>" /><?php } ?>
					
					<h4>Your Feedback is Important</h4>

					<?php echo $form_content["description"]; ?>

					<?php if ($form_content["anon"]) { ?>
					<div class="alert alert-info space-below space-above">
						<div class="row-fluid">
							<div class="span1"><input type="checkbox" value="1" id="hide_identity" name="hide_identity" checked="checked" /></div>
							<div class="span11"><label for="hide_identity"><?php echo $form_content["anon-text"]; ?></label></div>
						</div>
					</div>
					<?php } else { ?>
					<br />
					<?php } ?>
					<div class="control-group">
						<label for="feedback" class="form-required control-label space-above">Feedback or Comments:</label>
						<div class="controls"><textarea id="feedback" class="resize-vertical" name="feedback"></textarea></div>
					</div>
					<div class="row-fluid">
						<input type="button" class="btn" value="Close" />
						<input type="submit" class="btn btn-primary pull-right" value="Submit" />
					</div>
				</form>
				<?php
			break;
		}
	} else {
		echo display_error();
		echo "<input type=\"button\" class=\"btn btn-primary\" value=\"Submit\" />";
	}

	if (!isset($_POST["who"])) {
		?>
	</body>
</html>
		<?php
	}

}