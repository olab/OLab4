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
 * Used in observerships.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <jonathan.fingland@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 * @version $Id: index.inc.php 1187 2010-05-06 13:44:57Z finglanj $
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_OBSERVERSHIPS_ADMIN"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("observerships", "update", false)) {
	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	
	require_once("Classes/mspr/Observership.class.php");
	require_once("Classes/mspr/Observerships.class.php");

	$observership_id = clean_input($_GET["id"], array("int"));
	
	$observership = Observership::get($observership_id);
	
	if ($observership) {
			
		$student = User::fetchRowByID($observership->getStudentID());

		$BREADCRUMB = array();
		$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/users", "title" => "Manage Users");
		$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/users/manage?id=".$student->getID(), "title" => $student->getFullname(false));
		$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/users/manage/students?section=observerships&id=".$student->getID(), "title" => "Observerships");
		$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/observerships?section=review&id=".$observership_id, "title" => "Review ".$observership->getTitle());
		
		echo "<h1>" . $observership->getTitle() . "</h1>";
		
		switch ($STEP) {
			case 2 :
				switch (strtolower($_POST["status"])) {
					case "approve" :
						$status = "approved";
					break;
					case "reject" :
						$status = "rejected";
					break;
					default;
					break;
				}
				
				if ($status && $observership->getStatus() == "pending") {
					$query = "UPDATE `student_observerships` SET `status` = ".$db->qstr($status)." WHERE `id` = ".$db->qstr($observership->getID());
					if ($db->Execute($query)) {
						add_success("The observership has been ".$status.". You will be redireted to the Manage Observerships page in 5 seconds. If you do not wish to wait please <a href=\"".ENTRADA_URL."/admin/observerships\">click here</a> if you do not wish to wait.");
						$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/observerships\\'', 5000)";
					} else {
						add_error("Sorry, an error ocurred while attempting to update the status of this observership. An administrator has been informed, please try again later.");
						application_log("error", "Error occurred while attempting to update `student_observerships`, DB Said: ".$db->ErrorMsg());
					}
				} else {
					add_error("The observership status was invalid, please try again.");
				}
			break;
		}
		
		switch ($STEP) {
			case 2 :
				if ($ERROR) {
					echo display_error();
				}
				if ($SUCCESS) {
					echo display_success();
				}
			break;
			case 1 :
			default :
			?>
			<form action="<?php echo ENTRADA_URL; ?>/admin/observerships?section=review&id=<?php echo $observership_id; ?>" method="post">
				<input type="hidden" value="2" name="step" />
				<div class="row-fluid">
					<div class="span3"><strong for="title" class="form-nrequired">Status:</strong></div>
					<div class="span9"><?php echo ucwords($observership->getStatus());?></div>
				</div>

				<div class="row-fluid">
					<div class="span3"><strong for="activity_type" class="form-nrequired">Activity Type:</strong></div>
					<div class="span9"><?php echo ucwords($observership->getActivityType()); ?></div>
				</div>
				
				<?php if ($observership->getObservershipDetails() != NULL) { ?>
				<div class="row-fluid">
					<div class="span3"><strong for="activity_type" class="form-nrequired"><?php echo $observership->getActivityType() == "ip-observership" ? "IP " : "" ; ?>Observership Details:</strong></div>
					<div class="span9"><?php echo $observership->getObservershipDetails(); ?></div>
				</div>
				<?php } ?>

				<div class="row-fluid">
					<div class="span3"><strong for="clinical_discipline" class="form-nrequired">Clinical Discipline:</strong></div>
					<div class="span9"><?php echo $observership->getClinicalDiscipline(); ?></div>
				</div>

				<div class="row-fluid">
					<div class="span3"><strong for="organisation" class="form-nrequired">Organisation:</strong></div>
					<div class="span9"><?php echo $observership->getOrganisation();?></div>
				</div>
				
				<div class="row-fluid">
					<div class="span3"><strong for="address_l1" class="form-nrequired">Address Line 1:</strong></div>
					<div class="span2"><?php echo $observership->getAddressLine1();?></div>
				
					<div class="span2"><strong for="phone">Phone:</strong></div>
					<div class="span2"><?php echo $observership->getPhone();?></div>
				</div>

				<div class="row-fluid">
					<div class="span3"><strong for="address_l2" class="form-nrequired">Address Line 2:</strong></div>
					<div class="span2"><?php echo $observership->getAddressLine2(); ?></div>

					<div class="span2"><strong for="fax">Fax:</strong></div>
					<div class="span2"><?php echo $observership->getFax(); ?></div>
				</div>
				<div class="row-fluid">
					<div class="span3"><strong for="countries_id" class="form-nrequired">Country:</strong></div>
					<div class="span2"><?php echo $observership->getCountry(); ?></div>

					<div class="span2"><strong for="city" class="form-nrequired">City:</strong></div>
					<div class="span2"><?php echo $observership->getCity();?></div>
				</div>

				<div class="row-fluid">
					<div class="span3"><strong id="prov_state_strong" for="prov_state_div" class="form-nrequired">Prov / State:</strong></div>
					<div class="span2"><?php echo $observership->getProv(); ?></div>

					<div class="span2"><strong for="postal_code">Postal Code:</strong></div>
					<div class="span2"><?php echo $observership->getPostalCode();?></div>
				</div>
				
				<div class="row-fluid">
					<div class="span3"><strong for="supervisor" class="form-nrequired">Preceptor:</strong></div>
					<div class="span9"><?php echo $observership->getPreceptorFirstname() . " " . $observership->getPreceptorLastname() ;?></div>
				</div>

				<div class="row-fluid">
					<div class="span3"><strong for="supervisor" class="form-nrequired">Period:</strong></div>
					<div class="span9"><?php echo date("l, F jS, Y", $observership->getStart()) . ($observership->getStart() < $observership->getEnd() ? " to " . date("l, F jS, Y", $observership->getEnd()) : "") ;?></div>
				</div>

				<div class="row-fluid">
					<div class="span1">
						<input type="button" class="btn" value="Back" onclick="window.location = '<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=observerships&id=<?php echo $observership->getStudentID(); ?>'" />
					</div>
					<?php if ($ENTRADA_USER->getActiveRole() == "admin") { ?>
					<div class="span1">
						<input type="button" class="btn" value="Edit" onclick="window.location = '<?php echo ENTRADA_URL; ?>/admin/observerships?section=edit&id=<?php echo $observership->getID(); ?>'" />
					</div>
					<?php } ?>
					<?php if ($observership->getStatus() == "pending") { ?>
					<div class="span3 pull-right">
					<input type="submit" class="btn btn-danger" name="status" value="Reject" />
					<input type="submit" class="btn btn-primary" name="status" value="Approve" />&nbsp;
					</div>
					<?php } ?>
				</div>
				
			</form>
			<?php
		break;
		}
	}
}