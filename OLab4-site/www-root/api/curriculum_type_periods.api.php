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

	if ((isset($_POST["ctype_id"])) && $type_id = (int) $_POST["ctype_id"]) {
		$query = "SELECT * FROM `curriculum_periods` WHERE `curriculum_type_id` = ".$db->qstr($type_id)." AND `active` = 1";
		$periods = $db->GetAll($query);
		if ($periods) {
			echo "<select name=\"curriculum_period\" id = \"period_select\">";
			echo "<option value=\"0\">-- Select a Period --</option>";
			foreach ($periods as $period) {
				echo "<option value = \"".$period["cperiod_id"]."\">".(($period["curriculum_period_title"]) ? $period["curriculum_period_title"] . " - " : "").date("F jS, Y",$period["start_date"])." to ".date("F jS, Y",$period["finish_date"])."</option>";
			}
			echo "</select>";
		} else {
			echo "<div class=\"display-notice\"><ul><li>No periods have been found for the selected <strong>Curriculum Category</strong>.</li></ul></div>";
		}
	} else {
		echo "<div class=\"display-notice\"><ul><li>No <strong>Curriculum Category</strong> has been selected.</li></ul></div>";
	}
}
?>
