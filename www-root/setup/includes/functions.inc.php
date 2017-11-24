<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * This file contains all of the functions used within Entrada.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

/**
 * Handy function that takes the QUERY_STRING and adds / modifies / removes elements from it
 * based on the $modify array that is provided.
 *
 * @param array $modify
 * @return string
 * @example echo "index.php?".replace_query(array("action" => "add", "step" => 2));
 */
function replace_query($modify = array(), $html_encode_output = false) {
	$process	= array();
	$tmp_string	= array();
	$new_query	= "";

	// Checks to make sure there is something to modify, else just returns the string.
	if(count($modify) > 0) {
		$original	= explode("&", $_SERVER["QUERY_STRING"]);
		if(count($original) > 0) {
			foreach ($original as $value) {
				$pieces = explode("=", $value);
				// Gets rid of any unset variables for the URL.
				if(isset($pieces[0]) && isset($pieces[1])) {
					$process[$pieces[0]] = $pieces[1];
				}
			}
		}

		foreach ($modify as $key => $value) {
		// If the variable already exists, replace it, else add it.
			if(array_key_exists($key, $process)) {
				if(($value === 0) || (($value) && ($value !=""))) {
					$process[$key] = $value;
				} else {
					unset($process[$key]);
				}
			} else {
				if(($value === 0) || (($value) && ($value !=""))) {
					$process[$key] = $value;
				}
			}
		}
		if(count($process) > 0) {
			foreach ($process as $var => $value) {
				$tmp_string[] = $var."=".$value;
			}
			$new_query = implode("&", $tmp_string);
		} else {
			$new_query = "";
		}
	} else {
		$new_query = $_SERVER["QUERY_STRING"];
	}

	return (((bool) $html_encode_output) ? html_encode($new_query) : $new_query);
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
					$string = preg_replace(array("/[^a-z0-9_\-\.\/\~\?\&\:\#\=\+\~]/i", "/(\.)\.+/", "/(\/)\/+/"), "$1", $string);
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
					$string = preg_replace("/[^a-z0-9_\-\.]/i", "", $string);
				break;
				case "alphanumeric" :	// Remove anything that is not alphanumeric.
					$string = preg_replace("/[^a-z0-9]+/i", "", $string);
				break;
				case "alpha" :			// Remove anything that is not an alpha.
					$string = preg_replace("/[^a-z]+/i", "", $string);
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
				case "allowedtags" :	// Cleans and validates HTML, requires HTMLPurifier: http://htmlpurifier.org
				case "nicehtml" :
				case "html" :
					//require_once("Entrada/htmlpurifier/HTMLPurifier.auto.php");

					$html = new HTMLPurifier();

					$config = HTMLPurifier_Config::createDefault();
					$config->set("Cache.SerializerPath", CACHE_DIRECTORY);
					$config->set("Core.Encoding", DEFAULT_CHARSET);
					$config->set("Core.EscapeNonASCIICharacters", true);
					$config->set("HTML.SafeObject", true);
					$config->set("Output.FlashCompat", true);
					$config->set("HTML.TidyLevel", "medium");
					$config->set("Test.ForceNoIconv", true);
					$config->set("Attr.AllowedFrameTargets", array("_blank", "_self", "_parent", "_top"));

					$string = $html->purify($string, $config);
				break;
				default :				// Unknown rule, log notice.
					application_log("notice", "Unknown clean_input function rule [".$rule."]");
				break;
			}
		}

		return $string;
	} else {
		return trim($string);
	}
}

/**
 * Function to properly format the success messages for consistency.
 *
 * @param array $success_messages
 * @return string containing the HTML of the message or false if there is no HTML.
 */
function display_success($success_messages = array()) {
	global $SUCCESS, $SUCCESSSTR;

	$output_html = "";

	if (is_scalar($success_messages)) {
		if (trim($success_messages) != "") {
			$success_messages = array($success_messages);
		} else {
			$success_messages = array();
		}
	}

	if (!$num_success = (int) @count($success_messages)) {
		if ($num_success = (int) @count($SUCCESSSTR)) {
			$success_messages = $SUCCESSSTR;
		}
	}

	if ($num_success) {
		$output_html .= "<div id=\"display-success-box\" class=\"display-success\">\n";
		$output_html .= "	<ul>\n";
		foreach ($success_messages as $success_message) {
			$output_html .= "	<li>".$success_message."</li>\n";
		}
		$output_html .= "	</ul>\n";
		$output_html .= "</div>\n";
	}

	return (($output_html) ? $output_html : false);
}

/**
 * Function to properly format the error messages for consistency.
 *
 * @param array $notice_messages
 * @return string containing the HTML of the message or false if there is no HTML.
 */
function display_notice($notice_messages = array()) {
	global $NOTICE, $NOTICESTR;

	$output_html = "";

	if (is_scalar($notice_messages)) {
		if (trim($notice_messages) != "") {
			$notice_messages = array($notice_messages);
		} else {
			$notice_messages = array();
		}
	}

	if (!$num_notices = (int) @count($notice_messages)) {
		if ($num_notices = (int) @count($NOTICESTR)) {
			$notice_messages = $NOTICESTR;
		}
	}

	if ($num_notices) {
		$output_html .= "<div id=\"display-notice-box\" class=\"display-notice\">\n";
		$output_html .= "	<ul>\n";
		foreach ($notice_messages as $notice_message) {
			$output_html .= "	<li>".$notice_message."</li>\n";
		}
		$output_html .= "	</ul>\n";
		$output_html .= "</div>\n";
	}

	return (($output_html) ? $output_html : false);
}

/**
 * Function to properly format the error messages for consistency.
 *
 * @param array $error_messages
 * @return string containing the HTML of the message or false if there is no HTML.
 */
function display_error($error_messages = array()) {
	global $ERROR, $ERRORSTR;

	$output_html = "";

	if (is_scalar($error_messages)) {
		if (trim($error_messages) != "") {
			$error_messages = array($error_messages);
		} else {
			$error_messages = array();
		}
	}

	if (!$num_errors = (int) @count($error_messages)) {
		if ($num_errors = (int) @count($ERRORSTR)) {
			$error_messages = $ERRORSTR;
		}
	}

	if($num_errors) {
		$output_html .= "<div id=\"display-error-box\" class=\"display-error\">\n";
		$output_html .= "	<ul>\n";
		foreach ($error_messages as $error_message) {
			$output_html .= "	<li>".$error_message."</li>\n";
		}
		$output_html .= "	</ul>\n";
		$output_html .= "</div>\n";
	}

	return (($output_html) ? $output_html : false);
}

/**
 * Function checks to ensure the e-mail address is valid.
 *
 * @param string $address
 * @return bool
 */
function valid_address($address = "", $mode = 0) {
	switch((int) $mode) {
		case 2 :	// Strict
			$regex = "/^([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})$/i";
		break;
		case 1 :	// Promiscuous
			$regex = "/^([*+!.&#$|\'\\%\/0-9a-z^_`{}=?~:-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})$/i";
		break;
		default :	// Recommended
			$regex = "/^([*+!.&#$|0-9a-z^_=?~:-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})$/i";
		break;
	}

	if(preg_match($regex, trim($address))) {
		return true;
	} else {
		return false;
	}
}
?>