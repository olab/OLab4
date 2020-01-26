<?php

require_once("functions.inc.php");
require_once("Entrada/metadata/functions.inc.php"); //include the general metadata functions as well


require_once("Classes/users/UserPhoto.class.php");
require_once("Classes/users/UserPhotos.class.php");
require_once("Classes/users/Cohort.class.php");
require_once("Classes/organisations/Organisation.class.php");
require_once("Classes/organisations/Organisations.class.php");
require_once("Classes/users/Department.class.php");
require_once("Classes/users/Departments.class.php");


function getExpandedFeatures() {
	$features = array(
	/* biographical features */
		"name" => 1,
		"group" => 1,
		"role" => 1,
		"photo" => 1,
		"department" => 1,
		"organisation" => 1,
		"email" => 1,
		"email_alt" => 0,
		"address" => 1,
		"phone" => 1,
		"fax" => 1
	);
	return $features;
}

function getExpandedProfile(User $user, array $features, $include_empty = true) {
	//outline
	//display basic biographical information
	//get all MetaData categories applicable to this user.
	//get the value sets for each type
	//display substitute row in empty sets/hide category if specified
	var_dump($features);
	$types = getTypes_User($user);
	$categories = getCategories($types);

	$str = getBiographicalSketch($user, $features);

	//if categories are not included in $features, assume they are to be included
	return $str;
}

function getBiographicalSketch(User $user, array $features) {
	ob_start();
	?>
	<h2>Expanded Biographical Sketch</h2>
	<table>
	<?php
	if ($features['name'] && ($name = getBiographicalFeature($user, "name"))) {
		echo formatFeature("Name:", $name);
	}
	if ($features["department"]) {
	$departments = $user->getDepartments();
	} elseif ($features["group"]) {
		
	}
	?>
	</table>
	<?php
	return ob_get_clean(); 
}

function errNoCats_Expanded() {
	return display_notice("There are currently no Meta Data Categories applicable to this user.");
}

function formatFeature($label, $value) {
	return "<tr><td>".$label."</td><td>".$value."</td></tr>";
	
}

function getBiographicalFeature(User $user, $feature) {
	switch ($feature) {
		case "name":
			$feat_str = $user->getName("%f %l");
			break;
		case "group";
			$feat_str = $user->getGroup();
			break;
		case "role":
			$feat_str = $user->getRole();
			break;
		case "photo":
			$official_photo = UserPhoto::get($user->getID(), UserPhoto::OFFICIAL);
			$feat_str = $official_photo->getFilename();
			break;
		case "organisation":
			$organisation = $user->getOrganisation();
			$feat_str = $organisation->getTitle();
			break;
		case "email":
			$feat_str = $user->getEmail();
			break;
		case "email_alt":
			$feat_str = $user->getEmailAlternate();
			break;
		case "address":
			$address = $user->getAddress();
			$postcode = $user->getPostalCode();
			$city = $user->getCity();
			$province = $user->getProvince();
			$prov_name = $province->getName();
			$country = $user->getCountry();
			$country_name = $country->getName();
			
			$feat_str = html_encode($address)."<br />".html_encode($city);
			if ($prov_name) $feat_str .= ", ".html_encode($prov_name);
			$feat_str .= "<br />";
			$feat_str .= html_encode($country_name);
			if ($postcode) $feat_str .= ", ".html_encode($postcode);
			break;
		case "phone":
			$feat_str = $user->getTelephone();
			break;
		case "fax":
			$feat_str = $user->getFax();
			break;
		default:
			Zend_Debug::dump($feature);
			return;
	}
	return $feat_str;
}