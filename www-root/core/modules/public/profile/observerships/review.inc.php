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
 * The default file that is loaded when /admin/users is accessed.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <jonathan.fingland@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 * @version $Id: index.inc.php 1187 2010-05-06 13:44:57Z finglanj $
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_PUBLIC_OBSERVERSHIPS"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} else {
	
	require_once("Classes/mspr/Observership.class.php");

	$observership_id = clean_input($_GET["id"], array("int"));
	
	$observership = Observership::get($observership_id);

	$BREADCRUMB[] = array("url" => ENTRADA_URL."/profile/observerships?section=review&id=".$observership_id, "title" => $observership->getTitle());
	
	if ($observership) {
		add_statistic("observerships", "review", "observership_id", $observership_id);
	
		echo "<h1>" . $observership->getTitle() . "</h1>";
		
		?>
		<div class="row-fluid">
			<strong for="title" class="form-nrequired">Status:</strong>
			<?php echo ucwords($observership->getStatus());?>
		</div>
		<div class="row-fluid">
			&nbsp;
			<strong for="activity_type" class="form-nrequired">Activity Type:</strong>
			<?php echo $observership->getActivityType();?>
		</div>
		<?php if ($observership->getActivityType() == "ipobservership") { ?>
			<div class="row-fluid">
			&nbsp;
			<strong for="activity_type" class="form-nrequired">IP Observership Details:</strong>
			<?php echo $observership->getObservershipDetails(); ?>
		</div>	
		<?php } ?>
		<div class="row-fluid">
			&nbsp;
			<strong for="clinical_discipline" class="form-nrequired">Clinical Discipline:</strong>
			<?php echo $observership->getClinicalDiscipline(); ?>
		</div>
		<div class="row-fluid">
			&nbsp;
			<strong for="organisation" class="form-nrequired">Organisation:</strong>
			<?php echo $observership->getOrganisation();?>
		</div>



		<div class="row-fluid">
			&nbsp;
			<strong for="address_l1" class="form-nrequired">Address Line 1:</strong>
			<?php echo $observership->getAddressLine1();?>
			&nbsp;
			<strong for="phone">Phone:</strong>
			<?php echo $observership->getPhone();?>
		</div>
		<div class="row-fluid">
			&nbsp;
			<strong for="address_l2" class="form-nrequired">Address Line 2:</strong>
			<?php echo $observership->getAddressLine2(); ?>
			&nbsp;
			<strong for="fax">Fax:</strong>
			<?php echo $observership->getFax(); ?>
		</div>
		<div class="row-fluid">
			&nbsp;
			<strong for="countries_id" class="form-nrequired">Country:</strong>
			<?php echo $observership->getCountry(); ?>
			&nbsp;
			<strong for="city" class="form-nrequired">City:</strong>
			<?php echo $observership->getCity();?>
		</div>
		<div class="row-fluid">
			&nbsp;
			<strong id="prov_state_strong" for="prov_state_div" class="form-nrequired">Prov / State:</strong>
			<?php echo $observership->getProv(); ?>
			&nbsp;
			<strong for="postal_code">Postal Code:</strong>
			<?php echo $observership->getPostalCode();?>
		</div>




		<div class="row-fluid">
			&nbsp;
			<strong for="supervisor" class="form-nrequired">Preceptor:</strong>
			<?php echo $observership->getPreceptorFirstname() . " " . $observership->getPreceptorLastname() ;?>
		</div>
		<div class="row-fluid">
			&nbsp;
			<strong for="supervisor" class="form-nrequired">Period:</strong>
			<?php echo date("l, F jS, Y", $observership->getStart()) . ($observership->getStart() < $observership->getEnd() ? " to " . date("l, F jS, Y", $observership->getEnd()) : "") ;?>
		</div>
		<div class="row-fluid">
			<input class="btn btn-primary pull-right" type="button" value="Back" onclick="window.location = '<?php echo ENTRADA_URL; ?>/profile/observerships/'" />
		</div>
		
		<?php
	}
}