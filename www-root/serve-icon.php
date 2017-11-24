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
 * Outputs the requested file type icon to the users web-browser if they
 * request it like <img src="serve-icon.php?ext=pdf" />
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

$na		= "R0lGODlhEAAQAMQfANPn7by8vG+5zlOvyszMzIzI2JnO3dTU1Orq6u7u7sbGxvHx8bPZ5Pn5+f/599vb28DAwObm5v79/Oz1+D6mw3+/0LS0tPv8/PX29ReUtjKgvrzZ3/f399bW1v///////yH5BAEAAB8ALAAAAAAQABAAAAWL4PdBhAIFqJWIrBh42JIgSKes7ffKdPQgERxrh5ghHpaTUAdbOBcRRYBgab0amIaWM1lgAlaP+DJ5MBicC6QFEUs2lYrAIFawxZ6KZjAo1O9iFRkUGn4edixtgRkDFHQeBIAOAhoCAgAckJIFFAUAF2KRLAp4AAwTeJosBKmtHg8tNQcHHbS0tREiIQA7";
$ext	= false;

if((isset($_GET["ext"])) && (trim($_GET["ext"]) != "")) {
	$ext = clean_input($_GET["ext"], array("lower", "alphanumeric"));
    $hidden = clean_input($_GET["hidden"], array("lower", "alphanumeric"));
}

if($ext) {
	$query	= "SELECT `image` FROM `filetypes` WHERE `ext` = ".$db->qstr($ext);
    ($hidden ? $query.= "AND `hidden` = " . $hidden : "");
	$result	= $db->CacheGetRow(CACHE_TIMEOUT, $query);
	if($result) {
		header("Cache-Control: max-age=2592000\n");
		header("Content-type: image/gif\n");
		header("Content-Disposition: filename=".$ext.".gif\n");
		header("Content-Transfer-Encoding: binary\n");
		echo $result["image"];
		exit;
	} else {
		header("Cache-Control: max-age=2592000\n");
		header("Content-type: image/gif\n");
		header("Content-Disposition: filename=not-available.gif\n");
		header("Content-Transfer-Encoding: binary\n");
		echo base64_decode($na);
		exit;
	}
} else {
	header("Cache-Control: max-age=2592000\n");
	header("Content-type: image/gif\n");
	header("Content-Disposition: filename=not-available.gif\n");
	header("Content-Transfer-Encoding: binary\n");
	echo base64_decode($na);
	exit;
}