<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Serves a particular calendar in either JSON or ICS depending on the extension of the $_GET["request"];
 * http://www.yourschool.ca/calendars/username.json
 * http://www.yourschool.ca/calendars/username.ics
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

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

if (isset($_GET["c"]) && ($tmp_input = clean_input($_GET["c"], array("nows", "int")))) {
    $count = $tmp_input;
} else {
    $count = 4;
}

/**
 * Type of feeds we're looking for.
 */
if (isset($_GET["t"]) && ($tmp_input = clean_input($_GET["t"]))) {
    $type = $tmp_input;
} else {
    $type = "";
}

if ($type != "course" && $type != "community") {
    $type = "";
}

/**
 * If type of feed is specified, find out the ID of the course or community;
 */
if ($type) {
    if (isset($_GET["id"]) && ($tmp_input = clean_input($_GET["id"], array("nows", "int")))) {
        $id = $tmp_input;
    } else {
        $id = 0;
        $type = "";
    }
} else {
    $id = 0;
}

/**
 * Check for offset
 */
if (isset($_GET["o"]) && ($tmp_input = clean_input($_GET["o"], array("nows", "int")))) {
    $offset = $tmp_input;
} else {
    $offset = 0;
}

$twitter = new Entrada_Twitter();
$html = $twitter->render($count, $type, $id, true, $offset);

echo $html;