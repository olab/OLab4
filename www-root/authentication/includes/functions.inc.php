<?php
/**
 * Function to process the XML data.
 * @return 
 * @param object $xml_data
 */
function process_xml($xml_data) {
	$ar = array();
	$ttags = array();
	$tags = array();
	
	$parser = xml_parser_create();
	xml_parse_into_struct($parser, $xml_data, $vals, $index) or die(xml_error_string(xml_get_error_code($parser)));
	xml_parser_free($parser);
	
	for ($n = 0; $n <= count($vals)-1; $n++) {
		if (trim($vals[$n]["value"])) {
			$ar[$vals[$n]["tag"]][count($ar[$vals[$n]["tag"]])] = $vals[$n]["value"];
			$ttags[$vals[$n]["tag"]] = $vals[$n]["tag"];
		}
	}
	
	foreach ($ttags as $tagi) {
		array_push($tags, $tagi);
	}
	
	return $ar;
}

function encrypt_data($plain_data, $key) {
	global $encryption_method;
	
	if (!isset($encryption_method)) {
		$encryption_method = "default";
	}
	
	switch ($encryption_method) {
		case "rijndael" :
			return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $plain_data, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));
		break;
		case "blowfish" :
			return base64_encode(mcrypt_encrypt(MCRYPT_BLOWFISH, $key, $plain_data, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB), MCRYPT_RAND)));
		break;
		case "default" :
		default :
			/***********************************************************************
			*   Xoft Coder       
			*   modified by Armand Turpel armand@a-tu.net    
			*   - speed improvements
			* ====================================================    
			*                                       
			* Copyright (c) 2001 by M.Abdullah Khaidar (khaidarmak@yahoo.com)      
			*
			* This program is free software. You can redistribute it and/or modify 
			* it under the terms of the GNU General Public License as published by 
			* the Free Software Foundation; either version 2 of the License.       
			*
			***********************************************************************/
			$tob64 = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "+", "/", "=");

			$key_length	= 0;
			$keyl = strlen($key);
			$all_bin_chars = "";
			$cipher_data = "";

			for ($i=0; $i<strlen($plain_data); $i++) {
				$p = $plain_data[$i];
				$k = $key[$key_length];
				$key_length++;
				if ($key_length >= $keyl) {
					$key_length = 0;
				}
				$dec_chars = ord($p)^ord($k);
				$dec_chars = $dec_chars + $keyl;
				$bin_chars = decbin($dec_chars);
				while (strlen($bin_chars) < 8) {
					$bin_chars = "0".$bin_chars;
				}
				$all_bin_chars .= $bin_chars;
			}

			$m = 0;
			for ($j=0; $j<strlen($all_bin_chars); $j=$j+4) {
				$four_bit = substr($all_bin_chars, $j, 4);
				$four_bit_dec = bindec($four_bit);
				$cipher_data .= $tob64[($four_bit_dec << 2) + $m];
				if (++$m > 3) {
					$m = 0;
				}
			}

			return $cipher_data;			
		break;
	}
}

/**
 * This function handles basic logging for the application. You provide it with the entry type and message
 * it will log it to the appropriate log file. You also have the option of notifying the application
 * administrator of error log entries.
 *
 * @param string $type
 * @param string $message
 * @return bool
 */
function application_log($type, $message) {
	global $AGENT_CONTACTS, $ENTRADA_USER;
	
	$search		= array("\t", "\r", "\n");
	$log_entry	= date("r", time())."\t".str_replace($search, " ", $message)."\t".((isset($ENTRADA_USER)) ? str_replace($search, " ", $ENTRADA_USER->getID()) : 0)."\t".((isset($_SERVER["REMOTE_ADDR"])) ? str_replace($search, " ", $_SERVER["REMOTE_ADDR"]) : 0)."\t".((isset($_SERVER["HTTP_USER_AGENT"])) ? str_replace($search, " ", $_SERVER["HTTP_USER_AGENT"]) : false)."\n";

	switch($type) {
		case "auth_success" :
			$log_file = "auth_success_log.txt";
		break;
		case "auth_notice" :
			$log_file = "auth_notice_log.txt";
		break;
		case "auth_error" :
			$log_file = "auth_error_log.txt";

			if ((defined("NOTIFY_ADMIN_ON_ERROR")) && (NOTIFY_ADMIN_ON_ERROR)) {
				@error_log($log_entry, 1, $AGENT_CONTACTS["administrator"]["email"], "Subject: Authentication System: Errorlog Entry\nFrom: \"".$AGENT_CONTACTS["administrator"]["name"]."\" <".$AGENT_CONTACTS["administrator"]["email"].">\n");
			}
		break;
		default :
			$log_file = "auth_default_log.txt";
		break;
	}

	if (@error_log($log_entry, 3, LOG_DIRECTORY.DIRECTORY_SEPARATOR.$log_file)) {
		return true;
	} else {
		return false;
	}
}

/**
 * This function cleans a string with any valid rules that have been provided in the $rules array.
 * Note that $rules can also be a string if you only want to apply a single rule.
 * If no rules are provided, then the string will simply be trimmed using the trim() function.
 * @param string $string
 * @param array $rules
 * @return string
 * @example $variable = clean_input(" 1235\t\t", array("nows", "int")); // $variable will equal an integer value of 1235.
 */
function clean_input($string, $rules = array()) {
	if (is_scalar($rules)) {
		if (trim($rules) != "") {
			$rules = array($rules);
		} else {
			$rules = array();
		}
	}

	if (count($rules) > 0) {
		foreach ($rules as $rule) {
			switch ($rule) {
				case "page_url" :		// Acceptable characters for community page urls.
				case "module" :
					$string = preg_replace("/[^a-z0-9_\-]/i", "", $string);
				break;
				case "url" :			// Allows only a minimal number of characters
					$string = preg_replace(array("/[^a-z0-9_\-\.\/\~\?\&\:\#\=\+]/i", "/(\.)\.+/", "/(\/)\/+/"), "$1", $string);
				break;
				case "file" :
				case "dir" :			// Allows only a minimal number of characters
					$string = preg_replace(array("/[^a-z0-9_\-\.\/]/i", "/(\.)\.+/", "/(\/)\/+/"), "$1", $string);
				break;
				case "int" :			// Change string to an integer.
					$string = (int) $string;
				break;
				case "float" :			// Change string to a float.
					$string = (float) $string;
				break;
				case "bool" :			// Change string to a boolean.
					$string = (bool) $string;
				break;
				case "nows" :			// Trim all whitespace anywhere in the string.
					$string = str_replace(array(" ", "\t", "\n", "\r", "\0", "\x0B", "&nbsp;"), "", $string);
				break;
				case "trim" :			// Trim whitespace from ends of string.
					$string = trim($string);
				break;
				case "trimds" :			// Removes double spaces.
					$string = str_replace(array(" ", "\t", "\n", "\r", "\0", "\x0B", "&nbsp;", "\x7f", "\xff", "\x0", "\x1f"), " ", $string);
					$string = html_decode(str_replace("&nbsp;", "", html_encode($string)));
				break;
				case "nl2br" :
					$string = nl2br($string);
				break;
				case "underscores" :	// Trim all whitespace anywhere in the string.
					$string = str_replace(array(" ", "\t", "\n", "\r", "\0", "\x0B", "&nbsp;"), "_", $string);
				break;
				case "lower" :			// Change string to all lower case.
				case "lowercase" :
					$string = strtolower($string);
				break;
				case "upper" :			// Change string to all upper case.
				case "uppercase" :
					$string = strtoupper($string);
				break;
				case "ucwords" :		// Change string to correct word case.
					$string = ucwords(strtolower($string));
				break;
				case "boolops" :		// Removed recognized boolean operators.
					$string = str_replace(array("\"", "+", "-", "AND", "OR", "NOT", "(", ")", ",", "-"), "", $string);
				break;
				case "quotemeta" :		// Quote's meta characters
					$string = quotemeta($string);
				break;
				case "credentials" :	// Acceptable characters for login credentials.
					$string = preg_replace("/[^a-z0-9_\-\.@]/i", "", $string);
				break;
				case "alphanumeric" :	// Remove anything that is not alphanumeric.
					$string = preg_replace("/[^a-z0-9]+/i", "", $string);
				break;
				case "alpha" :			// Remove anything that is not an alpha.
					$string = preg_replace("/[^a-z]+/i", "", $string);
				break;
				case "numeric" :		// Remove everything but numbers 0 - 9 for when int won't do.
					$string = preg_replace("/[^0-9]+/i", "", $string);
				break;
				case "name" :			// @todo jellis ?
					$string = preg_replace("/^([a-z]+(\'|-|\.\s|\s)?[a-z]*){1,2}$/i", "", $string);
				break;
				case "emailcontent" :	// Check for evil tags that could be used to spam.
					$string = str_ireplace(array("content-type:", "bcc:","to:", "cc:"), "", $string);
				break;
				case "postclean" :		// @todo jellis ?
					$string = preg_replace('/\<br\s*\/?\>/i', "\n", $string);
					$string = str_replace("&nbsp;", " ", $string);
				break;
				case "decode" :			// Returns the output of the html_decode() function.
					$string = html_decode($string);
				break;
				case "encode" :			// Returns the output of the html_encode() function.
					$string = html_encode($string);
				break;
				case "htmlspecialchars" : // Returns the output of the htmlspecialchars() function.
				case "specialchars" :
					$string = htmlspecialchars($string, ENT_QUOTES, DEFAULT_CHARSET);
				break;
				case "htmlbrackets" :	// Converts only brackets into entities.
					$string = str_replace(array("<", ">"), array("&lt;", "&gt;"), $string);
				break;
				case "notags" :			// Strips tags from the string.
				case "nohtml" :
				case "striptags" :
					$string = strip_tags($string);
				break;
				default :				// Unknown rule, log notice.
					continue;
				break;
			}
		}

		return $string;
	} else {
		return trim($string);
	}
}