#!/usr/bin/php
<?php
/**
 * Entrada Tools [ http://www.entrada-project.org ]
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
 * Display Podcast Upload Permissions Array
 * 
 * This is a script that you can use to see what the extras field in
 * the entrada.user_access table should look like for a user who has permissions
 * to upload podcasts to a specific graduating class.
 * 
 * Instructions:
 * 1. Run "./podcast-extras-permission-string.php 2013" to get the extras field
 *    for a student who can upload podcasts for the class of 2013. 
 * 
 *   
 * @author Unit: Medical Education Technology Unit
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */

if((!isset($_SERVER["argv"])) || (@count($_SERVER["argv"]) < 1)) {
	echo "<html>\n";
	echo "<head>\n";
	echo "	<title>Processing Error</title>\n";
	echo "</head>\n";
	echo "<body>\n";
	echo "This file should be run by command line only.";
	echo "</body>\n";
	echo "</html>\n";
	exit;
}

$GRAD_YEARS = array();

if((isset($_SERVER["argv"])) && (is_array($_SERVER["argv"])) && (count($_SERVER["argv"]) > 1)) {
	foreach($_SERVER["argv"] as $key => $value) {
		if((int) $key) {
			$GRAD_YEARS[] = $value;
		}
	}
} else {
	$GRAD_YEARS[] = ((isset($_SERVER["argv"][1])) ? (int) trim($_SERVER["argv"][1]) : (date("Y") + 4));
}


echo "\nShowing Extras Field:";
echo "\n================================================================\n";
foreach($GRAD_YEARS as $grad_year) {
	echo "\nClass of ".$grad_year.": ".base64_encode(serialize(array("allow_podcasting" => $grad_year)));
}
echo "\n\n================================================================\n";
?>