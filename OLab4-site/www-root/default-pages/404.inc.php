<?php
/**
 * Online Course Resources [Pre-Clerkship]
 * @author Unit: Medical Education Technology Unit
 * @author Director: Dr. Benjamin Chen <bhc@post.queensu.ca>
 * @author Developer: Matt Simpson <simpson@post.queensu.ca>
 * @version 3.0
 * @copyright Copyright 2006 Queen's University, MEdTech Unit
 *
*/

if(!defined("PARENT_INCLUDED")) exit;

/**
 * 404 Not Found
 */
header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");


$PAGE_META["title"]			= "404 Error: Document Not Found";
$PAGE_META["description"]	= "";
$PAGE_META["keywords"]		= "";
?>
<strong>404 Error</strong>: The document you have requested cannot be found.