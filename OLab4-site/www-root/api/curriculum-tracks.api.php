<?php

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

if ((isset($_SESSION["isAuthorized"])) && ((bool) $_SESSION["isAuthorized"])) {
	if (!isset($_POST)) {
		die();
	}
	
	if (!isset($_POST["organisation_id"]) || ($organisation_id = clean_input($_POST["associated_student"], "int") && $organisation_id == 0)) {
		die();
	}

	$results = Models_Curriculum_Track::fetchAllByOrg($ENTRADA_USER->getActiveOrganisation());



	if ($results) {
		foreach ($results as $result) {
			$tracks[] = $result->toArray();
		}
		echo json_encode(array("status" => "success", "data" => $tracks));
	} else {
		echo json_encode(array("status" => "failure"));
	}

}

?>
