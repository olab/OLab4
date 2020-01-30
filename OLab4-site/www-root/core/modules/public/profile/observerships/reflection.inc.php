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

if((!defined("PARENT_INCLUDED")) || (!defined("IN_PUBLIC_OBSERVERSHIPS"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} else {
	
	require_once("Classes/mspr/Observership.class.php");
	require_once("Classes/mspr/ObservershipReflection.class.php");
	
	$observership = Observership::get($OBSERVERSHIP_ID);
	
	$status = $observership->getStatus();

	echo "<h1>".$observership->getTitle()." Reflection</h1>";
	
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/profile/observerships?section=review&id=".$OBSERVERSHIP_ID, "title" => $observership->getTitle() );
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/profile/observerships?section=reflection&id=".$OBSERVERSHIP_ID, "title" => $observership->getTitle() . " Reflection");
	
	if ($observership) {
		if (($observership->getEnd() != 0 ? $observership->getEnd() : $observership->getStart()) >= time()) {
			$ERROR = "";
			$ERRORSTR = "";
			add_error("Sorry, but the observership has not yet been completed.");
			echo display_error();
		} else {
			$observership_reflection = ObservershipReflection::get($observership->getReflection());
			if (!$observership_reflection && $observership->getStatus() == "confirmed") {
				add_error("This observership has been confirmed, a reflection can not be added at this time. You will be redirected to the observerships page in 5 seconds.");
				echo display_error();
				$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/profile/observerships\\'', 5000)";
			} else {
				switch ($STEP) {
					case 2 :
						if ($observership->getStatus() != "confirmed") {

							if ($observership_reflection) {
								$_POST["id"] = $observership_reflection->getID();
								$observership_reflection->mapArray($_POST,"update");
								$observership_reflection->update($observership_reflection->getID());
							} else {
								$observership_reflection = ObservershipReflection::fromArray($_POST, "create");
								$observership_reflection = $observership_reflection->create();
								$query = "UPDATE `student_observerships` SET `reflection_id` = ".$db->qstr($observership_reflection->getID())." WHERE `id` = ".$db->qstr($observership->getID());
								$db->Execute($query);

								$preceptor_name = ($observership->getPreceptorPrefix() ? $observership->getPreceptorPrefix()." " : "").$observership->getPreceptorFirstname()." ".$observership->getPreceptorLastname();
								$message	= $preceptor_name.",\n\n";
								$message   .= "You have been indicated as the preceptor on an Observership:\n".
											  "======================================================\n".

											  "Submitted at: ".date("Y-m-d H:i", time())."\n".
											  "Submitted by: ".$ENTRADA_USER->getFullname(false)."\n".
											  "E-Mail Address: ".$ENTRADA_USER->getEmail()."\n".

											  "Observership details:\n".
											  "---------------------\n".
											  "Title: ".$observership->getTitle()."\n".
											  "Activity Type: ".$observership->getActivityType()."\n".
											  ($observership->getActivityType() == "ipobservership" ? "IP Observership Details: ".$observership->getObservershipDetails()."\n" : "").
											  "Clinical Discipline: ".$observership->getClinicalDiscipline()."\n".
											  "Organisation: ".$observership->getOrganisation()."\n".
											  "Address: ".$observership->getAddressLine1()."\n".
											  "Preceptor: ".$observership->getPreceptorFirstname(). " " . $observership->getPreceptorLastname() ."\n".
											  "Start date: ".date("Y-m-d", $observership->getStart())."\n".
											  "End date: ".date("Y-m-d", $observership->getEnd())."\n\n".

											  "The observership request can be approved or rejected at the following address:\n".
											  ENTRADA_URL."/confirm_observership?unique_id=".$observership->getUniqueID();

								$mail = new Zend_Mail();
								$mail->addHeader("X-Section", "Observership Notification System", true);
								$mail->setFrom($AGENT_CONTACTS["observership"]["email"], $AGENT_CONTACTS["observership"]["name"]);
								$mail->clearSubject();
								$mail->setSubject("Observership Request Created");
								$mail->setBodyText($message);
								$mail->clearRecipients();
								$mail->addTo($observership->getPreceptorEmail(), $preceptor_name);
								
								try {
									$mail->send();
								} catch (Exception $e) { }

							}
						} else {
							add_error("This observerhip reflection can not be edited because it has already been approved by the observership preceptor.");
						}
					break;
					case 1 :
					default:
					continue;
				}

				switch ($STEP) {
					case 2 :
						if ($ERROR) {
							echo display_error();
							$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/profile/observerships\\'', 5000)";
						}
						if ($SUCCESS) {
							echo display_success();
							$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/profile/observerships\\'', 5000)";
						}
					break;
					case 1:
					default:
						$observership = Observership::get($OBSERVERSHIP_ID);
						$status = $observership->getStatus();

						$stat_action = ($status == "confirmed") ? "reflection_review" : "relflection_edit";
						add_statistic("observerships", "reflection", "observership_id", $observership->getID());
						
						echo "<h1>".$observership->getTitle()." Reflection</h1>";
						
						if ($status != "confirmed") {
							add_generic("<strong>Please note:</strong> Preserve the privacy and confidentiality of your preceptor.");
							echo display_generic();
						}
						?>
						<style type="text/css">
							.confirmed tbody td {padding:10px 5px;}
							.reflection textarea.input-xxlarge {min-height:200px;}
						</style>
						
						<form class="form-horiztonal reflection" action="<?php echo ENTRADA_URL; ?>/profile/observerships?section=reflection&id=<?php echo $OBSERVERSHIP_ID; ?>" method="POST">
							<input type="hidden" name="observership_id" value="<?php echo $OBSERVERSHIP_ID; ?>" />
							<input type="hidden" name="step" value="2" />
							
							<div class="row-fluid">
								<p><strong>What was the physician's intrinsic role (Advocate, Collaborator, Communicator, Manager, Professional, Scholar) that you observed most in this observership?</strong></p>
								<?php if ($status == "confirmed") { echo "<p>".$observership_reflection->getPhysiciansRole()."</p>"; } else { ?>
								<textarea class="input-xxlarge" name="physicians_role"><?php echo ($observership_reflection ? html_encode($observership_reflection->getPhysiciansRole()) : ""); ?></textarea>
								<?php } ?>
							</div>
							<div class="row-fluid">
								<p><strong>Give examples of how this was enacted:</strong></p>
								<?php if ($status == "confirmed") { echo "<p>".$observership_reflection->getPhysicianReflection()."</p>"; } else { ?>
								<textarea class="input-xxlarge" name="physician_reflection"><?php echo ($observership_reflection ? html_encode($observership_reflection->getPhysicianReflection()) : "") ; ?></textarea>
								<?php } ?>
							</div>
							<div class="row-fluid">
								<p><strong>What challenges do you see for yourself in your future practice of medicine around this role?  What can you start to do now to learn more about this?</strong></p>
								<?php if ($status == "confirmed") { echo "<p>".$observership_reflection->getObservershipChallenge()."</p>"; } else { ?>
								<textarea class="input-xxlarge" name="observership_challenge"><?php echo ($observership_reflection ? html_encode($observership_reflection->getObservershipChallenge()) : "") ; ?></textarea>
								<?php } ?>
							</div>
							<div class="row-fluid">
								<p><strong>What questions do you have about this experience or what would you like to learn more about?  What additional comments do you have about the observership?</strong></p>
								<?php if ($status == "confirmed") { echo "<p>".$observership_reflection->getDisciplineReflection()."</p>"; } else { ?>
								<textarea class="input-xxlarge" name="discipline_reflection"><?php echo ($observership_reflection ? html_encode($observership_reflection->getDisciplineReflection()) : "") ; ?></textarea>
								<?php } ?>
							</div>
							<div class="row-fluid">
							<p><strong>Was this experience helpful in career exploration?</strong></p>
								<?php if ($status == "confirmed") { 
									echo "<p>".($observership_reflection->getCareer() == "1" ? "Yes, this experience was helpful in career exploration." : "No, this experience was <strong>not</strong> helpful in career exploration.")."</p>";
								} else { ?>
								<input name="career" type="radio" <?php echo ($observership_reflection ? ($observership_reflection->getCareer() == "1" ? "checked=\"checked\"" : "") : ""); ?> value="1" /> Yes, this experience was helpful in career exploration.<br />
								<input name="career" type="radio" <?php echo ($observership_reflection ? ($observership_reflection->getCareer() == "0" ? "checked=\"checked\"" : "") : ""); ?> value="0" /> No, this experience was <strong>not</strong> helpful in career exploration.
								<?php } ?>
							</div>
							<br />
							<div class="row-fluid">
								<?php if ($status == "confirmed") { ?>
								<input type="button" class="btn" onclick="window.location = '<?php echo ENTRADA_URL; ?>/profile/observerships'" value="Back" />
								<?php } else { ?>
								<input type="submit" class="btn btn-primary pull-right" value="Save" />
								<?php } ?>
							</div>
						</form>
						<?php
					break;
				}
			}
		}
	} else {
		application_log("error", "An observership id was provided to the observership reflection, but could not be found.");
		add_error("Sorry, but the observership could not be found. If you believe this to be in error please use the feedback system to contact an administrator. You will be redirected to the observership index in 5 seconds, <a href=\"".ENTRADA_URL."/profile/observerships\">click here</a> if you do not wish to wait.");
		echo display_error();
		$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/profile/observerships\\'', 5000)";
	}
}