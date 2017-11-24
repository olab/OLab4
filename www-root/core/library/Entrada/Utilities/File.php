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
 * File utility functions for granting temporary public access to files
 * 
 * @author Organization: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 * 
 */

class Entrada_Utilities_File {

	/**
	 * Encodes a string to b64 while being URL-safe
	 * @param  string $string
	 * @return string         b64encoded string
	 */
	public static function urlSafeB64Encode($string) {
	    $data = base64_encode($string);
	    $data = str_replace(array("+","/","="),array("-","_",""),$data);
	    return $data;
	}

	/**
	 * Decodes a b64-encoded string while being URL-safe
	 * @param  string $string b64encoded string
	 * @return string         Decoded string
	 */
	public static function urlSafeB64Decode($string) {
	    $data = str_replace(array("-","_"),array("+","/"),$string);
	    $mod4 = strlen($data) % 4;
	    if ($mod4) {
	        $data .= substr("====", $mod4);
	    }
	    return base64_decode($data);
	}

	/**
	 * Get the HMAC-SHA256 hash of a given string, then b64encode it
	 * @param  string $data
	 * @param  string $key  String used to sign the data
	 * @return string 		B64-encoded HMAC-SHA256 hash
	 */
	public static function getSignature($data, $key) {
		$hmac = hash_hmac("sha256", $data, $key, true);
		return self::urlSafeB64Encode($hmac);
	}

	/**
	 * Get the maximum timeout window in which a file can be 
	 * temporarily accessed
	 * @return int
	 */
	public static function getTimestamp() {
		return time() + FILE_PUBLIC_ACCESS_TIMEOUT;
	}

	/**
	 * Get the filename for a given afversion_id
	 * @return string filename
	 */
	public static function getStoredFileName($afversion_id) {
		return "A".$afversion_id;
	}

	/**
	 * Get the full file path including the filename
	 * @param  int $afversion_id
	 * @return string
	 */
	public static function getFilePath($afversion_id) {
		return FILE_STORAGE_PATH."/".self::getStoredFileName($afversion_id);
	}

	/**
	 * Download/serve a file directly via headers
	 * @param  int 		$afversion_id	
	 * @param  string 	$filename 
	 * @param  array  	$content      	array("disposition", "type")
	 * @param  array  	$statistic    	Used to capture statistics upon file download. Ex: array("module_name" => "assignment: 123", "action" => "file_download", "action_field" => "assignment_id", "action_value" => 123)
	 * @return void              		File is served directly to user so nothing is returned
	 */
	public static function serveFile($afversion_id, $filename, $content = array("disposition" => "attachment", "type" => "binary"), $statistic = array()) {
		$file = self::getFilePath($afversion_id);

		if (file_exists($file) && is_readable($file)) {
            ob_clear_open_buffers();
            
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Content-Type: ".$content["type"]);
            header("Content-Disposition: ".$content["disposition"]."; filename=\"".$filename."\"");
            header("Content-Length: ".filesize($file));
            header("Content-Transfer-Encoding: binary\n");

            readfile($file);

            if (is_array($statistic)) {
            	add_statistic($statistic["module_name"], $statistic["action"], $statistic["action_field"], $statistic["action_value"]);
            }

            exit;
        }
	}
}