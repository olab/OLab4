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
 * View a file stored on disk. Only allowed if the proxy_id, afversion_id,
 * and correct HMAC-SHA256 signature are included as params.
 * 
 * @author Organisation: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
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

$PROCESSED = Entrada_Utilities::getCleanUrlParams(array(
	"afversion_id" => "int",
	"proxy_id" => "int",
	"organisation_id" => "int",
	"timestamp" => "int",
	"signature" => array("trim","striptags"),
	"download" => "bool",
));

if ($PROCESSED["afversion_id"] && $PROCESSED["proxy_id"] && $PROCESSED["organisation_id"] && $PROCESSED["timestamp"] && $PROCESSED["signature"]) {
	
	$models_user_access = new Models_User_Access(array(
		"user_id" => $PROCESSED["proxy_id"],
		"organisation_id" => $PROCESSED["organisation_id"],
		"group" => "student"
	));
	$user = $models_user_access->fetchRowByUserIDOrganisationIDGroup();

	if (is_object($user)) {

		// Use user's private hash as secret key
		$secret_key = $user->getPrivateHash();

		// Generate signature based on concatenate of file version id and timestamp, signed with user's private hash
		$new_signature = Entrada_Utilities_File::getSignature($PROCESSED["afversion_id"].$PROCESSED["proxy_id"].$PROCESSED["organisation_id"].$PROCESSED["timestamp"], $secret_key);

		// Compare new signature to included one, and that timestamp is within time limit
		if ($new_signature === $PROCESSED["signature"] && $PROCESSED["timestamp"] > time()) {

			$file = Models_Assignment_File_Version::fetchRowByID($PROCESSED["afversion_id"]);

			if (is_object($file)) {

				// Disposition should be equal to "inline" or "attachment"
				$disposition = $PROCESSED["download"] ? "attachment" : "inline";

				// Set Content headers
				$content = array(
					"disposition" => $disposition, 
					"type" => $file->getFileMimetype()
				);

				// Set statistics
				$statistic = array(
					"module_name" => "assignment_file",
					"action" => $PROCESSED["download"] ? "file_download" : "file_view",
					"action_field" => "afversion_id",
					"action_value" => $file->getAfversionID()
				);

				// All checks out, serve the file
				Entrada_Utilities_File::serveFile($file->getAfversionID(), $file->getFileFilename(), $content, $statistic);
			}
			
		}
	}
}