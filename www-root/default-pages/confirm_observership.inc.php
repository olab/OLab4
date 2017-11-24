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
 * Observership confirmation page.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 *
 */

if(!defined("PARENT_INCLUDED")) exit;

$unique_id = isset($_GET["unique_id"]) ? clean_input($_GET["unique_id"], "alphanumeric") : NULL;

echo "<h1>Observership Confirmation</h1>";

if ($unique_id) {
	
	require_once(ENTRADA_CORE."/library/Classes/mspr/Observership.class.php");

	$step = (isset($_POST["step"]) ? (int) $_POST["step"] : '1');
	
	$observership = Observership::getByUniqueID($unique_id);

	if ($observership && $observership->getStatus() == "approved") {

		switch ($step) {
			case 2 :
				if ($_POST["action"] == "Confirm" || $_POST["action"] == "Deny") {
					$PROCESSED["status"] = ($_POST["action"] == "Confirm" ? "confirmed" : "denied");
				}
				
				if ($PROCESSED["status"]) {
					$query = "UPDATE `student_observerships` SET `status` = ".$db->qstr($PROCESSED["status"])." WHERE `id` = ".$db->qstr($observership->getID());
					if ($db->Execute($query)) {
						add_success("Thank you for updating this observership.");
					} else {
						application_log("error", "Error occurred when attempting to update `student_observershisp` [".$observership->getID()."], DB said: ".$db->ErrorMsg());
						add_error("An error ocurred, we were unable to update the observership. A system administrator has been informed, please try again later.");
					}
				} else {
					add_error("A problem occurred, an invalid action was provided. A system administrator has been informed, please try again later.");
				}
			break;
			default :
			continue;
		}

		switch ($step) {
			case 2 :
				if ($ERROR) {
					echo display_error();
				}
				if ($SUCCESS) {
					echo display_success();
				}
			break;
			case 1 :
		?>

	<p><?php echo (($observership->getPreceptorPrefix() ? $observership->getPreceptorPrefix()." " : "").$observership->getPreceptorFirstname()." ".$observership->getPreceptorLastname()); ?>,</p>
	<p>The learner <?php echo $observership->getUser()->getFullname(false); ?> has indicated you were the preceptor for the following observership:</p>
	<blockquote>
	<div class="row-fluid">
		<div class="span2"><strong>Title:</strong></div>
		<div class="span10"><?php echo $observership->getTitle(); ?></div>
	</div>
	<div class="row-fluid">
		<div class="span2"><strong>Clinical Discipline:</strong></div>
		<div class="span10"><?php echo $observership->getClinicalDiscipline(); ?></div>
	</div>
	<div class="row-fluid">
		<div class="span2"><strong>Organisation:</strong></div>
		<div class="span10"><?php echo $observership->getOrganisation(); ?></div>
	</div>
	<div class="row-fluid">
		<div class="span2"><strong>Location:</strong></div>
		<div class="span10"><?php echo $observership->getCity() . ", " . $observership->getProv() . ", " . $observership->getCountry(); ?></div>
	</div>
	<div class="row-fluid">
		<div class="span2"><strong>Period:</strong></div>
		<div class="span10"><?php echo $observership->getPeriod(); ?></div>
	</div>
	</blockquote>

	
	<p>Please confirm or deny the observership with the buttons below.</p>
	
	<form action="<?php echo ENTRADA_URL."/confirm_observership?unique_id=".$unique_id; ?>" method="POST">
		<div class="row-fluid">
			<input type="hidden" value="2" name="step" />
			<input class="btn btn-danger" type="submit" value="Deny" name="action" />
			<input class="btn btn-primary pull-right" type="submit" value="Confirm" name="action" />
		</div>
	</form>

	<?php 
			break;
			default :
			continue;
		}

	} else if ($observership && ($observership->getStatus() == "confirmed" || $observership->getStatus() == "denied")) { 
		add_success("Thank you, your response to this observership has been recorded.");
		echo display_success();
	} else {
		application_log("error", "Unable to find observership by unique id [".$unique_id."].");
		add_error("Sorry, we were unable to find the observership associated with this id. An administrator has been informed, please try again later.");
		echo display_error();
	}
} else {
	add_notice("An error has ocurred, a valid unique identification number has not been supplied. Please contact support for further assistance.");
	echo display_notice();
}