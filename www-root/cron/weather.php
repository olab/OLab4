<?php
/**
 * Online Course Resources [Pre-Clerkship]
 * @author Unit: Medical Education Technology Unit
 * @author Director: Dr. Benjamin Chen <bhc@post.queensu.ca>
 * @author Developer: Matt Simpson <simpson@post.queensu.ca>
 * @version 3.0
 * @copyright Copyright 2006 Queen's University, MEdTech Unit
 *
 * $Id: weather.php 1103 2010-04-05 15:20:37Z simpson $
*/

@set_time_limit(0);
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

if((defined("DEFAULT_WEATHER_FETCH")) && (isset($WEATHER_LOCATION_CODES)) && (is_array($WEATHER_LOCATION_CODES))) {
	if((@is_dir(CACHE_DIRECTORY)) && (@is_writable(CACHE_DIRECTORY))) {
		foreach($WEATHER_LOCATION_CODES as $weather_code => $location_name) {
			$xml_feed_url = str_replace("%LOCATIONCODE%", $weather_code, DEFAULT_WEATHER_FETCH);

			if(($feed_contents = @file_get_contents($xml_feed_url)) && (trim($feed_contents))) {
				if(!@file_put_contents(CACHE_DIRECTORY."/weather-".$weather_code.".xml", trim($feed_contents))) {
					application_log("error", "Unable to save the fetched weather feed: ".$xml_feed_url);
				}
			} else {
				application_log("error", "Unable to fetch the requested weather feed or the feed was empty: ".$xml_feed_url);
			}
		}
	} else {
		application_log("error", "The cache directory [".CACHE_DIRECTORY."] is not writable by PHP.");
	}
}
?>