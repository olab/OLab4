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
 * This file is loaded when someone opens the Feedback Agent.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Andrew Dos-Santos <ad29@queensu.ca>
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
	$PAGE_META["title"]		= "Automated Page Feedback";

	$ERROR					= 0;
	$ERRORSTR				= array();

	$STEP					= 1;
	$ENCODED_INFORMATION	= "";

	$MEDTECH_PROXYID		= ((isset($ENTRADA_USER) && (int) $ENTRADA_USER->getID()) ? (int) $ENTRADA_USER->getID() : 0); // This must be in all files once authenticated.

	if((isset($_GET["step"])) && ((int) trim($_GET["step"]))) {
		$STEP = (int) trim($_GET["step"]);
	}

	if(isset($_GET["enc"])) {
		$ENCODED_INFORMATION = trim($_GET["enc"]);
	} elseif(isset($_POST["action"])) {
		$ENCODED_INFORMATION = trim($_POST["enc"]);
	}
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo DEFAULT_CHARSET; ?>" />

		<title>%TITLE%</title>

		<meta name="description" content="%DESCRIPTION%" />
		<meta name="keywords" content="%KEYWORDS%" />

		<meta name="robots" content="noindex, nofollow" />

		<meta name="MSSmartTagsPreventParsing" content="true" />
		<meta http-equiv="imagetoolbar" content="no" />
		<script type="text/javascript" src="<?php echo ENTRADA_URL; ?>/javascript/jquery/jquery.min.js"></script>
		<link href="<?php echo $ENTRADA_TEMPLATE->url(); ?>/css/bootstrap.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" type="text/css" media="all" />
		<link href="<?php echo $ENTRADA_TEMPLATE->url(); ?>/css/style.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" type="text/css" media="all" />
		<link href="<?php echo ENTRADA_URL; ?>/css/common.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" type="text/css" media="all" />
		<link href="<?php echo ENTRADA_URL; ?>/css/print.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" type="text/css" media="print" />

		<link href="<?php echo ENTRADA_URL; ?>/images/favicon.ico" rel="shortcut icon" type="image/x-icon" />
		<link href="<?php echo ENTRADA_URL; ?>/w3c/p3p.xml" rel="P3Pv1" type="text/xml" />

		%HEAD%

		<style type="text/css">
		body {
			overflow:	hidden;
			margin:		0px;
			padding:	0px;
		}
		</style>

		<script type="text/javascript">
		function submitFeedback() {
			var formData = jQuery("#feedback-form").serialize();
			jQuery("#feedback-form").remove();
			jQuery("#form-submitting").show();
			jQuery.ajax({
				url: '<?php echo ENTRADA_URL; ?>/agent-feedback.php?step=2&amp;enc=<?php echo $ENCODED_INFORMATION; ?>',
				type: 'POST',
				dataType: 'html',
				data: formData,
				async: true,
				success: function(data) {
					jQuery("#form-submitting").parent().append(data);
					jQuery("#form-submitting").hide();
				}
			});
			return false;
		}
		
		function newFeedback() {
			jQuery("#wizard-body, #wizard-footer").remove();
			jQuery.ajax({
				url: '<?php echo ENTRADA_URL; ?>/agent-feedback.php?step=1&amp;enc=<?php echo $ENCODED_INFORMATION; ?>',
				type: 'POST',
				dataType: 'html',
				async: true,
				success: function(data) {
					jQuery("#form-submitting").parent().append(data);
				}
			});
			return false;
		}
		
		function closeWindow() {
			window.close();
		}
		</script>
	</head>
	<body>
	<?php
	switch($STEP) {
		case "2" :
			$extracted_information	= false;
			$tmp_information		= @unserialize(@base64_decode($ENCODED_INFORMATION));

			if((@is_array($tmp_information)) && (@count($tmp_information))) {
				$extracted_information = $tmp_information;
				unset($tmp_information);
			}

			$message  = "Attention ".$AGENT_CONTACTS["agent-undergrad-teaching"]["name"]."\n";
			$message .= "The following teaching needs to be adjusted:\n";
			$message .= "=======================================================\n\n";
			$message .= "Submitted At:\t\t".date("r", time())."\n";
			$message .= "Submitted By:\t\t".$_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]." [".$_SESSION["details"]["username"]."]\n";
			$message .= "E-Mail Address:\t\t".$_SESSION["details"]["email"]."\n\n";
			$message .= "Missing / Incorrect Undergraduate Teaching:\n";
			$message .= "-------------------------------------------------------\n";
			$message .= clean_input($_POST["feedback"], array("trim", "emailcontent"))."\n\n";
			$message .= "Web-Browser / OS:\n";
			$message .= "-------------------------------------------------------\n";
			$message .= clean_input($_SERVER["HTTP_USER_AGENT"], array("trim", "emailcontent"))."\n\n";
			$message .= "URL Sent From:\n";
			$message .= "-------------------------------------------------------\n";
			$message .= ((isset($_SERVER["HTTPS"])) ? "https" : "http")."://".$_SERVER["HTTP_HOST"].clean_input($extracted_information["url"], array("trim", "emailcontent"))."\n\n";
			$message .= "=======================================================";

                        $mail = new Zend_Mail("iso-8859-1");

                        $mail->addHeader("X-Priority", "3");
                        $mail->addHeader('Content-Transfer-Encoding', '8bit');
                        $mail->addHeader("X-Originating-IP", $_SERVER["REMOTE_ADDR"]);
                        $mail->addHeader("X-Section", "Feedback System");

                        $mail->addTo($AGENT_CONTACTS["annualreport-support"]["email"], $AGENT_CONTACTS["annualreport-support"]["name"]);
                        $mail->setFrom(($_SESSION["details"]["email"]) ? $_SESSION["details"]["email"] : $AGENT_CONTACTS["administrator"]["email"], $_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]);
                        $mail->setSubject("Report Missing /Incorrect Teaching Submission - ".APPLICATION_NAME);
                        $mail->setReplyTo($mail->getFrom(), $_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]);
                        $mail->setBodyText($message);

                        try{
                                $mail->send();
                                $SUCCESS++;
                                $SUCCESSSTR[] = "Thank-you for informing us of the missing / incorrect undergraduate teaching. If we have questions regarding any of the information you provided, we will get in touch with you via e-mail, otherwise the teaching will be adjusted within two business days.";
                        } catch (Zend_Mail_Transport_Exception $e) {
                                $ERROR++;
                                $ERRORSTR[] = "We apologize however, we are unable to submit your feedback at this time due to a problem with the mail server.<br /><br />The system administrator has been informed of this error, please try again later.";
                                application_log("error", "Unable to report missing / incorrect undergraduate teaching with the feedback agent. Zend_mail said: ".$e->getMessage());
                        }
			?>
			<div id="wizard-body" style="position: absolute; top: 35px; left: 0px; width: 452px; height: 440px; padding-left: 15px; overflow: auto">
				<?php
				if($ERROR) {
					echo "<h2>Feedback Submission Failure</h2>\n";

					echo display_error();
				} elseif($SUCCESS) {
					echo "<h2>Feedback Submitted Successfully</h2>\n";

					echo display_success();
				}
				?>

				To <strong>report missing / incorrect undergraduate teaching</strong> or to <strong>close this window</strong> please use the buttons below.
			</div>
			<div id="wizard-footer" style="position: absolute; top: 465px; left: 0px; width: 100%; height: 40px; border-top: 2px #CCCCCC solid; padding: 4px 4px 4px 10px">
				<table style="width: 452px" cellspacing="0" cellpadding="0" border="0">
				<tr>
					<td style="width: 180px; text-align: left">
						<input type="button" class="btn" value="Close" onclick="closeWindow()" />
					</td>
					<td style="width: 272px; text-align: right">
						<input type="button" class="btn btn-primary" value="Report More Teaching" onclick="window.location='<?php echo ENTRADA_URL; ?>/agent-undergrad-teaching.php?enc=<?php echo $ENCODED_INFORMATION; ?>'" />
					</td>
				</tr>
				</table>
			</div>
			<?php
		break;
		case "1" :
		default :
			?>
			<form id="feedback-form" action="<?php echo ENTRADA_URL; ?>/agent-undergrad-teaching.php?step=2&amp;enc=<?php echo $ENCODED_INFORMATION; ?>" method="post" style="display: inline">
				<div class="panel-head" style="border-radius:0px;">
					<h3>Your Feedback is Important</h3>
				</div>
				<div class="inner-content" style="margin:20px;">
					<img src="<?php echo ENTRADA_URL; ?>/images/feedback.gif" width="48" height="48" alt="Give Feedback" title="Give Feedback" align="right" />
					This form is provided so you can efficiently provide our undergraduate office with important feedback regarding teaching as it is recorded in the system. Use this feature if you need to report missing / incorrect undergraduate teaching.
					<div class="row space-above">
						Your Name:
						<a href="mailto:<?php echo html_encode($_SESSION["details"]["email"]); ?>"><?php echo html_encode($_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]); ?></a>
					</div>
					<div class="row space-below">
						Your E-Mail:
						<a href="mailto:<?php echo html_encode($_SESSION["details"]["email"]); ?>"><?php echo html_encode($_SESSION["details"]["email"]); ?></a></td>
					</div>
					<div class="row space-below">
						<label for="feedback" class="form-required">Description of Teaching:</label>
						<textarea id="feedback" name="feedback" style="width: 98%; height: 115px"></textarea>
					</div>
					<div class="row space-below">
						<strong>Please note:</strong> Please try to be as specific as possible when reporting missing / incorrect undergraduate teaching.
					</div>
					<div class="row space-above">
						<input type="button" class="btn" value="Close" onclick="closeWindow()" />
						<input type="button" class="btn btn-primary pull-right" value="Submit" onclick="submitFeedback()" />
					</div>
				</div>
			</form>
			<div id="form-submitting" style="display: none; position: absolute; top: 0px; left: 0px;  background-color: #FFFFFF; opacity:.90; filter: alpha(opacity=90); -moz-opacity: 0.90">
				<div style="display: table; width: 485px; height: 555px; _position: relative; overflow: hidden">
					<div style="_position: absolute; _top: 50%; display: table-cell; vertical-align: middle;">
						<div style="_position: relative; _top: -50%; width: 100%; text-align: center">
							<span style="color: #003366; font-size: 18px; font-weight: bold">
								<img src="<?php echo ENTRADA_URL; ?>/images/loading.gif" width="32" height="32" alt="Report Filing" title="Please wait while your report is being filed" style="vertical-align: middle" /> Please Wait: report is being filed.
							</span>
						</div>
					</div>
				</div>
			</div>
			<?php
		break;
	}
	?>
	</body>
	</html>
	<?php
}