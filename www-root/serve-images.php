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
 * Outputs a dynamically generated image of the users choice containing the
 * requested text to the users web-browser.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 * @version $Id: serve-images.php 1171 2010-05-01 14:39:27Z ad29 $
 *
 * @example /images/dynamic/20/235/5/90/Class%20of%202009/jpg
 * [0] => Always Blank, ignore it.
 * [1] => Width
 * [2] => Height
 * [3] => Text Padding
 * [4] => Image Rotation
 * [5] => Text to make into an image
 * [6] => Type of image
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

if ((!isset($_SESSION["isAuthorized"])) || (!(bool) $_SESSION["isAuthorized"])) {
	application_log("error", "Someone attempted to access the serve-images.php file without being authenticated.");

	header("HTTP/1.0 404 Not Found");
	exit;
}

/**
 * Get all of the parameters passed in the URL.
 */
$parameters		= explode("/", $_SERVER["PATH_INFO"]);

$width			= clean_input($parameters[1], array("trim", "int"));
$height			= clean_input($parameters[2], array("trim", "int"));
$padding		= clean_input($parameters[3], array("trim", "int"));
$rotation		= clean_input($parameters[4], array("trim", "int"));
$message		= rawurldecode(clean_input($parameters[5], array("trim", "notags")));
$file_type		= clean_input($parameters[6], array("nows", "lower"));

$text			= new Entrada_TextImage($message, $file_type, $width, $height, ENTRADA_ABSOLUTE."/core/fonts/Vera.ttf", $rotation, $padding);
$text->draw();