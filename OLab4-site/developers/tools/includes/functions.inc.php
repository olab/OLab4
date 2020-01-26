<?php
/**
 * Entrada Tools
 * @author Unit: Medical Education Technology Unit
 * @author Developer: Matt Simpson <simpson@post.queensu.ca>
 * @copyright Copyright 2010 Queen's University, MEdTech Unit
 *
 * $Id: functions.inc.php 1080 2010-03-26 17:33:23Z simpson $
 */

/**
 * Outputs an error message, and logs it.

 * @param string $message
 * @return string
 */
function output_error($message = "") {
	global $ERROR;

	if (isset($ERROR)) {
		$ERROR++;
	}

	if ($message = clean_input($message)) {
		$message = "[ERROR]   " . $message;

		log_message($message);

		echo "\n" . $message;
	}

	@flush();
}

/**
 * Outputs a notice message, and logs it.

 * @param string $message
 * @return string
 */
function output_notice($message = "") {
	global $NOTICE;

	if (isset($NOTICE)) {
		$NOTICE++;
	}

	if ($message = clean_input($message)) {
		$message = "[NOTICE]  " . $message;

		log_message($message);

		echo "\n" . $message;
	}

	@flush();
}

/**
 * Outputs a success message, and logs it.

 * @param string $message
 * @return string
 */
function output_success($message = "") {
	global $SUCCESS;

	if (isset($SUCCESS)) {
		$SUCCESS++;
	}

	if ($message = clean_input($message)) {
		$message = "[SUCCESS] " . $message;

		log_message($message);

		echo "\n" . $message;
	}

	@flush();
}

/**
 * Logs any of the messages that are set by output_error(), output_notice() or
 * output_success();

 * @param string $message
 * @return bool

 */
function log_message($message = "") {
	global $ENABLE_LOGGING, $LOG_FILENAME;

	if ((isset($ENABLE_LOGGING)) && ((bool) $ENABLE_LOGGING) && (isset($LOG_FILENAME)) && ($LOG_FILENAME != "") && ((is_writable(dirname($LOG_FILENAME))) || (is_writable($LOG_FILENAME)))) {
		if (file_put_contents($LOG_FILENAME, $message . "\n", FILE_APPEND)) {
			return true;
		}
	}

	return false;
}

/**
 * This function will generate a fairly random hash code which
 * can be used in a number of situations.
 *
 * @param int $num_chars
 * @return string
 */
function generate_hash($num_chars = 32) {
	if (!$num_chars = (int) $num_chars) {
		$num_chars = 32;
	}

	return substr(md5(uniqid(rand(), 1)), 0, $num_chars);
}

/**
 * Function will return an the new release_date / release_until dates
 * for the new event_date based on the old_event data and old release details.
 *
 * @param int $old_event_date
 * @param int $new_event_date
 * @param int $old_release_date
 *
 * return int
 */
function offset_validity($old_event_date, $new_event_date, $old_release_date) {
	if ((int) $old_event_date && (int) $new_event_date && (int) $old_release_date) {
		return ($new_event_date + ($old_release_date - $old_event_date));
	}

	return 0;
}

/**
 * Function generates a medium-strong password for the account.
 *
 * @param int $length
 *
 * @return string
 */
function generate_password($length = 8) {
	$length = (int) $length;

	if (($length < 6) || ($length > 32)) {
		$length = 8;
	}

	return substr(md5(uniqid(rand(), true)), 0, $length);
}

/**
 * This function returns the data from the events table for the provided
 * event_id.
 *
 * @param int $event_id
 *
 * @return array
 */
function get_event_data($event_id = 0) {
	global $db;

	/**
	 * If we pass this function an array of events, use the first one.
	 */
	if (is_array($event_id) && count($event_id)) {
		$event_id = $event_id[0];
	}

	if ($event_id = (int) $event_id) {
		$query = "SELECT * FROM `events` WHERE `event_id` = " . $db->qstr($event_id);

		return $db->GetRow($query);
	}

	return false;
}

/**
 * This function takes an event_id and checks to see if it exists in the events
 * table.
 *
 * @param int $event_id
 *
 * @return bool
 */
function validate_event_id($event_id = 0) {
	global $db;

	if ($event_id = (int) $event_id) {
		$query = "SELECT `event_id` FROM `events` WHERE `event_id` = " . $db->qstr($event_id);
		$result = $db->GetRow($query);

		if ($result) {
			return true;
		}
	}

	return false;
}

/**
 * This function takes a proxy_id and returns basic information about this user.
 *
 * @param int $proxy_id
 *
 * @return array
 */
function get_user_info($proxy_id = 0) {
	global $db;

	if ($proxy_id = (int) $proxy_id) {
		$query = "SELECT `number`, `firstname`, `lastname` FROM `" . AUTH_DATABASE . "`.`user_data` WHERE `id` = " . $db->qstr($proxy_id);
		$result = $db->GetRow($query);
		if ($result) {
			return $result;
		}
	}

	return false;
}

/**
 * This function attempts to get the course_id of a course based on the title.
 *
 * @param string $course_name
 *
 * @return int
 */
function get_course_id($course_name = "") {
	global $db;

	if (trim($course_name) != "") {
		$query = "SELECT `course_id` FROM `courses` WHERE `course_name` LIKE " . $db->qstr($course_name);
		$result = $db->GetRow($query);
		if ($result) {
			return $result["course_id"];
		}
	}

	return 0;
}

/**
 * This function attempts to get the course_id of a course based on the course code.
 * 
 * @param string $course_name
 * 
 * @return int
 */
function get_course_id_by_code($course_code = "") {
	global $db;

	if (trim($course_code) != "") {
		$query = "SELECT `course_id` FROM `courses` WHERE `course_code` LIKE " . $db->qstr($course_code);
		$result = $db->GetRow($query);
		if ($result) {
			return $result["course_id"];
		}
	}

	return 0;
}

/**
 * This function attempts to get course information based on the provided course code.
 *
 * @param string $course_code
 *
 * @return array
 */
function fetch_course($course_code = "") {
	global $db;

	if ($course_code) {
		$query = "SELECT * FROM `courses` WHERE `course_code` LIKE " . $db->qstr($course_code) . " AND `course_active` = '1'";
		$result = $db->GetRow($query);
		if ($result) {
			return $result;
		}
	}

	return false;
}

function fetch_course_group_id($course_id, $group_name = "") {
	global $db;

	if ($group_name) {
	 	$query = "	SELECT `cgroup_id`
					FROM `course_groups`
					WHERE `group_name` LIKE ".$db->qstr($group_name)."
					AND `course_id` = ".$db->qstr($course_id)."
					AND `active` = '1'";
		$result = $db->GetRow($query);
		if ($result) {
			return $result["cgroup_id"];
		}
	}

	return 0;
}

function fetch_cohort_group_id($organisation_id = 0, $cohort_name = "") {
	global $db;

	if ($organisation_id && $cohort_name) {
	 	$query = "	SELECT a.`group_id`
					FROM `groups` AS a
					JOIN `group_organisations` AS b
					ON b.`group_id` = a.`group_id`
					AND b.`organisation_id` = ".$db->qstr($organisation_id)."
					WHERE a.`group_name` LIKE ".$db->qstr($cohort_name)."
					AND a.`group_type` = 'cohort'
					AND a.`group_active` = '1'";
		$result = $db->GetRow($query);
		if ($result) {
			return $result["group_id"];
		}
	}

	return 0;
}

/**
 * This function attempts to get the eventtype_id of a event based on the event type title provided.
 *
 * @param string $eventtype_title
 *
 * @return int
 */
function get_eventtype_id($eventtype_title = "") {
	global $db;

	if (trim($eventtype_title) != "") {
		$query = "SELECT `eventtype_id` FROM `events_lu_eventtypes` WHERE `eventtype_title` LIKE " . $db->qstr($eventtype_title);
		$result = $db->GetRow($query);
		if ($result) {
			return $result["eventtype_id"];
		}
	}

	return 0;
}

/**
 * This function attempts to get the eventtype_id of a event based on the event type title provided.
 *
 * @param string $eventtype_title
 *
 * @return int
 */
function get_eventtype_id_for_org($eventtype_title = "",$org = 1) {
	global $db;

	if (trim($eventtype_title) != "") {
		$query = "SELECT a.`eventtype_id` FROM `events_lu_eventtypes` a JOIN `eventtype_organisation` b ON a.`eventtype_id` = b.`eventtype_id` AND b.`organisation_id` = ".$db->qstr($org)." WHERE a.`eventtype_title` LIKE ".$db->qstr($eventtype_title);
		$result = $db->GetRow($query);
		if ($result) {
			return $result["eventtype_id"];
		}
	}

	return 0;
}

/**
 * This function takes the given staff number and returns the users
 * proxy_id (entrada_auth.user_data.id).
 *
 * @param int $number
 *
 * @return int
 */
function get_proxy_id($number = 0) {
	global $db;

	$number = (int) $number;

	if ($number) {
		$query = "	SELECT `id` AS `proxy_id`
					FROM `".AUTH_DATABASE."`.`user_data`
					WHERE `number` = ".$db->qstr($number);
		$result = $db->GetRow($query);
		if ($result) {
			return $result["proxy_id"];
		}
	}

	return 0;
}

/**
 * Wrapper function to clean_input.
 *
 * @param string $string
 * @param mixed $rules
 * @return string
 */
function clean_data($string = "", $rules = array()) {
	return clean_input($string, $rules);
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
				case "page_url" :  // Acceptable characters for community page urls.
				case "module" :
					$string = preg_replace("/[^a-z0-9_\-]/i", "", $string);
					break;
				case "url" :   // Allows only a minimal number of characters
					$string = preg_replace(array("/[^a-z0-9_\-\.\/\~\?\&\:\#\=\+]/i", "/(\.)\.+/", "/(\/)\/+/"), "$1", $string);
					break;
				case "file" :
				case "dir" :   // Allows only a minimal number of characters
					$string = preg_replace(array("/[^a-z0-9_\-\.\/]/i", "/(\.)\.+/", "/(\/)\/+/"), "$1", $string);
					break;
				case "int" :   // Change string to an integer.
					$string = (int) $string;
					break;
				case "float" :   // Change string to a float.
					$string = (float) $string;
					break;
				case "bool" :   // Change string to a boolean.
					$string = (bool) $string;
					break;
				case "nows" :   // Trim all whitespace anywhere in the string.
					$string = str_replace(array(" ", "\t", "\n", "\r", "\0", "\x0B", "&nbsp;"), "", $string);
					break;
				case "trim" :   // Trim whitespace from ends of string.
					$string = trim($string);
					break;
				case "trimds" :   // Removes double spaces.
					$string = str_replace(array(" ", "\t", "\n", "\r", "\0", "\x0B", "&nbsp;", "\x7f", "\xff", "\x0", "\x1f"), " ", $string);
					$string = html_decode(str_replace("&nbsp;", "", html_encode($string)));
					break;
				case "nl2br" :
					$string = nl2br($string);
					break;
				case "underscores" : // Trim all whitespace anywhere in the string.
					$string = str_replace(array(" ", "\t", "\n", "\r", "\0", "\x0B", "&nbsp;"), "_", $string);
					break;
				case "lower" :   // Change string to all lower case.
				case "lowercase" :
					$string = strtolower($string);
					break;
				case "upper" :   // Change string to all upper case.
				case "uppercase" :
					$string = strtoupper($string);
					break;
				case "ucwords" :  // Change string to correct word case.
					$string = ucwords(strtolower($string));
					break;
				case "boolops" :  // Removed recognized boolean operators.
					$string = str_replace(array("\"", "+", "-", "AND", "OR", "NOT", "(", ")", ",", "-"), "", $string);
					break;
				case "quotemeta" :  // Quote's meta characters
					$string = quotemeta($string);
					break;
				case "credentials" : // Acceptable characters for login credentials.
					$string = preg_replace("/[^a-z0-9_\-\.]/i", "", $string);
					break;
				case "alphanumeric" : // Remove anything that is not alphanumeric.
					$string = preg_replace("/[^a-z0-9]+/i", "", $string);
					break;
				case "alpha" :   // Remove anything that is not an alpha.
					$string = preg_replace("/[^a-z]+/i", "", $string);
					break;
				case "numeric" :  // Remove everything but numbers 0 - 9 for when int won't do.
					$string = preg_replace("/[^0-9]+/i", "", $string);
					break;
				case "name" :   // @todo jellis ?
					$string = preg_replace("/^([a-z]+(\'|-|\.\s|\s)?[a-z]*){1,2}$/i", "", $string);
					break;
				case "emailcontent" : // Check for evil tags that could be used to spam.
					$string = str_ireplace(array("content-type:", "bcc:", "to:", "cc:"), "", $string);
					break;
				case "postclean" :  // @todo jellis ?
					$string = preg_replace('/\<br\s*\/?\>/i', "\n", $string);
					$string = str_replace("&nbsp;", " ", $string);
					break;
				case "decode" :   // Returns the output of the html_decode() function.
					$string = html_decode($string);
					break;
				case "encode" :   // Returns the output of the html_encode() function.
					$string = html_encode($string);
					break;
				case "htmlspecialchars" : // Returns the output of the htmlspecialchars() function.
				case "specialchars" :
					$string = htmlspecialchars($string, ENT_QUOTES, DEFAULT_CHARSET);
					break;
				case "htmlbrackets" : // Converts only brackets into entities.
					$string = str_replace(array("<", ">"), array("&lt;", "&gt;"), $string);
					break;
				case "notags" :   // Strips tags from the string.
				case "nohtml" :
				case "striptags" :
					$string = strip_tags($string);
					break;
                case "msword" :
                    $string = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $string);
                    break;
				default :	// Unknown rule, log notice.
					application_log("notice", "Unknown clean_input function rule [" . $rule . "]");
					break;
			}
		}

		return $string;
	} else {
		return trim($string);
	}
}

/**
 * Activates speficied module for the specified community
 *
 * @param int $community_id
 * @param int $module_id
 * @return bool
 */
function communities_module_activate($community_id = 0, $module_id = 0) {
	global $db;

	if (($community_id = (int) $community_id) && ($module_id = (int) $module_id)) {
		/**
		 * Check that the requested module is present and active.
		 */
		$query = "SELECT * FROM `communities_modules` WHERE `module_id` = " . $db->qstr($module_id) . " AND `module_active` = '1'";
		$module_info = $db->GetRow($query);
		if ($module_info) {
			$query = "SELECT * FROM `community_modules` WHERE `community_id` = " . $db->qstr($community_id) . " AND `module_id` = " . $db->qstr($module_id);
			$result = $db->GetRow($query);
			if ($result) {
				/**
				 * If it is not already active, active it.
				 */
				if (!(int) $result["module_active"]) {
					if (!$db->AutoExecute("community_modules", array("module_active" => 1), "UPDATE", "`community_id` = " . $db->qstr($community_id) . " AND `module_id` = " . $db->qstr($module_id))) {
						echo("Unable to active module ".(int) $module_id." (updating existing record) for updated community id ".(int) $COMMUNITY_ID.". Database said: ".$db->ErrorMsg());
					}
				}
			} else {
				if (!$db->AutoExecute("community_modules", array("community_id" => $community_id, "module_id" => $module_id, "module_active" => 1), "INSERT")) {
					echo("Unable to active module ".(int) $module_id." (inserting new record) for updated community id ".(int) $COMMUNITY_ID.". Database said: ".$db->ErrorMsg());
				}
			}
		} else {
			echo("Module_id [".$module_id."] requested activation in community_id [".$community_id."] but the module is either missing or inactive.");
		}
	} else {
		echo("There was no community_id [".$community_id."] or module_id [".$module_id."] provided to active a module.");
	}

	return true;
}

/**
 * Activates speficied module for the specified community
 *
 * @param int $community_id
 * @param int $module_id
 * @return bool
 */
function communities_module_activate_and_page_create($community_id = 0, $module_id = 0) {
	global $db;

	if(($community_id = (int) $community_id) && ($module_id = (int) $module_id)) {
	/**
	 * Check that the requested module is present and active.
	 */
		$query			= "SELECT * FROM `communities_modules` WHERE `module_id` = ".$db->qstr($module_id)." AND `module_active` = '1'";
		$module_info	= $db->GetRow($query);
		if($module_info) {
			$query	= "SELECT * FROM `community_modules` WHERE `community_id` = ".$db->qstr($community_id)." AND `module_id` = ".$db->qstr($module_id);
			$result	= $db->GetRow($query);
			if($result) {
			/**
			 * If it is not already active, active it.
			 */
				if(!(int) $result["module_active"]) {
					if(!$db->AutoExecute("community_modules", array("module_active" => 1), "UPDATE", "`community_id` = ".$db->qstr($community_id)." AND `module_id` = ".$db->qstr($module_id))) {
						output_error("Unable to active module ".(int) $module_id." (updating existing record) for updated community id ".(int) $COMMUNITY_ID.". Database said: ".$db->ErrorMsg());
						return false;
					}
				}
			} else {
				if(!$db->AutoExecute("community_modules", array("community_id" => $community_id, "module_id" => $module_id, "module_active" => 1), "INSERT")) {
					output_error("Unable to active module ".(int) $module_id." (inserting new record) for updated community id ".(int) $COMMUNITY_ID.". Database said: ".$db->ErrorMsg());
					return false;
				}
			}

			$query	= "SELECT * FROM `community_pages` WHERE `community_id` = ".$db->qstr($community_id)." AND `page_active` = '1' AND `page_type` = ".$db->qstr($module_info["module_shortname"]);
			$result	= $db->GetRow($query);
			if(!$result) {
				$query		= "SELECT (MAX(`page_order`) + 1) as `order` FROM `community_pages` WHERE `community_id` = ".$db->qstr($community_id)." AND `page_active` = '1' AND `parent_id` = '0' AND `page_url` != ''";
				$result		= $db->GetRow($query);
				if($result) {
					$page_order = (int) $result["order"];
				} else {
					$page_order = 0;
				}

				if((!$db->AutoExecute("community_pages", array("community_id" => $community_id, "page_order" => $page_order, "page_type" => $module_info["module_shortname"], "menu_title" => $module_info["module_title"], "page_title" => $module_info["module_title"], "page_url" => $module_info["module_shortname"], "page_content" => "", "updated_date" => time(), "updated_by" => "MedTech"), "INSERT")) && ($cpage_id = $db->Insert_Id())) {
					output_error("Unable to create page for module ".(int) $module_id." for new community id ".(int) $community_id.". Database said: ".$db->ErrorMsg());
					return false;
				}
			}
		} else {
			output_error("Module_id [".$module_id."] requested activation in community_id [".$community_id."] but the module is either missing or inactive. Connected to a DB: " . $db->isConnected());
			return false;
		}
	} else {
		output_error("There was no community_id [".$community_id."] or module_id [".$module_id."] provided to active a module.");
		return false;
	}

	return true;
}

function set_module_page_permissions($db, $community_id, $module_id, $allow_member_view, $allow_public_view, $allow_troll_view) {
	$query = "SELECT * FROM " . DATABASE_NAME . ".`communities_modules` WHERE `module_id` = " . $db->qstr($module_id) . " AND `module_active` = '1'";
	$module_info = $db->GetRow($query);
	$module_shortname = "";

	if ($module_info) {
		$module_shortname = $module_info["module_shortname"];

		if ($db->AutoExecute("" . DATABASE_NAME . ".`community_pages`",
						array("allow_member_view" => 0, "allow_public_view" => 0, "allow_troll_view" => 0,
							"updated_date" => time(), "updated_by" => 5440), "UPDATE",
						"`community_id` = " . $db->qstr($community_id) . " AND page_type = " . $db->qstr($module_shortname))) {

			output_success("Permission set to allow Admin access only.");
		} else {
			output_error("Failed to create the module page.");
		}
	} else {
		output_error("Module does not exist.");
	}
}

/**
 * Processes / resizes and creates properly sized image and thumbnail image
 * for images uploaded to the galleries module.
 *
 * @param string $original_file
 * @param int $photo_id
 * @return bool
 */
function process_user_photo_official($original_file, $proxy_id = 0) {
	global $VALID_MAX_DIMENSIONS;

	if (!@function_exists("gd_info")) {
		echo "Error: " . __LINE__;

		return false;
	}

	if ((!@file_exists($original_file)) || (!@is_readable($original_file))) {
		echo "Error: " . __LINE__;

		return false;
	}

	if (!$proxy_id = (int) $proxy_id) {
		echo "Error: " . __LINE__;

		return false;
	}

	$new_file = STORAGE_USER_PHOTOS . "/" . $proxy_id . "-official";
	$img_quality = 85;

	if ($original_file_details = @getimagesize($original_file)) {
		$original_file_width = $original_file_details[0];
		$original_file_height = $original_file_details[1];

		/**
		 * Check if the original_file needs to be resized or not.
		 */
		if (($original_file_width > $VALID_MAX_DIMENSIONS["photo-width"]) || ($original_file_height > $VALID_MAX_DIMENSIONS["photo-height"])) {
			switch ($original_file_details["mime"]) {
				case "image/pjpeg":
				case "image/jpeg":
				case "image/jpg":
					$original_img_resource = @imagecreatefromjpeg($original_file);
					break;
				case "image/png":
					$original_img_resource = @imagecreatefrompng($original_file);
					break;
				case "image/gif":
					$original_img_resource = @imagecreatefromgif($original_file);
					break;
				default :
					echo "Error: " . __LINE__;

					return false;
					break;
			}
			if ($original_img_resource) {
				/**
				 * Determine whether it's a horizontal / vertical image and calculate the new smaller size.
				 */
				if ($original_file_width > $original_file_height) {
					$new_file_width = $VALID_MAX_DIMENSIONS["photo-width"];
					$new_file_height = (int) (($VALID_MAX_DIMENSIONS["photo-width"] * $original_file_height) / $original_file_width);
				} else {
					$new_file_width = (int) (($VALID_MAX_DIMENSIONS["photo-height"] * $original_file_width) / $original_file_height);
					$new_file_height = $VALID_MAX_DIMENSIONS["photo-height"];
				}

				if ($original_file_details["mime"] == "image/gif") {
					$new_img_resource = @imagecreate($new_file_width, $new_file_height);
				} else {
					$new_img_resource = @imagecreatetruecolor($new_file_width, $new_file_height);
				}

				if ($new_img_resource) {
					if (@imagecopyresampled($new_img_resource, $original_img_resource, 0, 0, 0, 0, $new_file_width, $new_file_height, $original_file_width, $original_file_height)) {
						switch ($original_file_details["mime"]) {
							case "image/pjpeg":
							case "image/jpeg":
							case "image/jpg":
								if (!imagejpeg($new_img_resource, $new_file, $img_quality)) {
									echo "Error: " . __LINE__;

									return false;
								}
								break;
							case "image/png":
								if (!@imagepng($new_img_resource, $new_file)) {
									echo "Error: " . __LINE__;

									return false;
								}
								break;
							case "image/gif":
								if (!@imagegif($new_img_resource, $new_file)) {
									echo "Error: " . __LINE__;

									return false;
								}
								break;
							default :
								echo "Error: " . __LINE__;

								return false;
								break;
						}

						@chmod($new_file, 0644);

						/**
						 * Frees the memory this used, so it can be used again for the thumbnail.
						 */
						@imagedestroy($original_img_resource);
						@imagedestroy($new_img_resource);
					} else {
						echo "Error: " . __LINE__;

						return false;
					}
				} else {
					echo "Error: " . __LINE__;

					return false;
				}
			} else {
				echo "Error: " . __LINE__;

				return false;
			}
		} else {
			if (@move_uploaded_file($original_file, $new_file)) {
				@chmod($new_file, 0644);

				/**
				 * Create the new width / height so we can use the same variables
				 * below for thumbnail generation.
				 */
				$new_file_width = $original_file_width;
				$new_file_height = $original_file_height;
			} else {
				echo "Error: " . __LINE__;

				return false;
			}
		}

		/**
		 * Check that the new_file exists, and can be used, then proceed
		 * with Thumbnail generation ($new_file-thumbnail).
		 */
		if ((@file_exists($new_file)) && (@is_readable($new_file))) {

			switch ($original_file_details["mime"]) {
				case "image/pjpeg":
				case "image/jpeg":
				case "image/jpg":
					$original_img_resource = @imagecreatefromjpeg($new_file);
					break;
				case "image/png":
					$original_img_resource = @imagecreatefrompng($new_file);
					break;
				case "image/gif":
					$original_img_resource = @imagecreatefromgif($new_file);
					break;
				default :
					echo "Error: " . __LINE__;

					return false;
					break;
			}

			if (($new_file_width > $VALID_MAX_DIMENSIONS["thumb-width"]) || ($new_file_height > $VALID_MAX_DIMENSIONS["thumb-height"])) {
				$dest_x = 0;
				$dest_y = 0;
				$ratio_orig = ($new_file_width / $new_file_height);
				$cropped_width = $VALID_MAX_DIMENSIONS["thumb-width"];
				$cropped_height = $VALID_MAX_DIMENSIONS["thumb-height"];

				if ($ratio_orig > 1) {
					$cropped_width = ($cropped_height * $ratio_orig);
				} else {
					$cropped_height = ($cropped_width / $ratio_orig);
				}
			} else {
				$cropped_width = $new_file_width;
				$cropped_height = $new_file_height;

				$dest_x = ($VALID_MAX_DIMENSIONS["thumb-width"] / 2) - ($cropped_width / 2);
				$dest_y = ($VALID_MAX_DIMENSIONS["thumb-height"] / 2) - ($cropped_height / 2 );
			}

			if ($original_file_details["mime"] == "image/gif") {
				$new_img_resource = @imagecreate($VALID_MAX_DIMENSIONS["thumb-width"], $VALID_MAX_DIMENSIONS["thumb-height"]);
			} else {
				$new_img_resource = @imagecreatetruecolor($VALID_MAX_DIMENSIONS["thumb-width"], $VALID_MAX_DIMENSIONS["thumb-height"]);
			}

			if ($new_img_resource) {
				if (@imagecopyresampled($new_img_resource, $original_img_resource, $dest_x, $dest_y, 0, 0, $cropped_width, $cropped_height, $new_file_width, $new_file_height)) {
					switch ($original_file_details["mime"]) {
						case "image/pjpeg":
						case "image/jpeg":
						case "image/jpg":
							if (!@imagejpeg($new_img_resource, $new_file . "-thumbnail", $img_quality)) {
								echo "Error: " . __LINE__;

								return false;
							}
							break;
						case "image/png":
							if (!@imagepng($new_img_resource, $new_file . "-thumbnail")) {
								echo "Error: " . __LINE__;

								return false;
							}
							break;
						case "image/gif":
							if (!@imagegif($new_img_resource, $new_file . "-thumbnail")) {
								echo "Error: " . __LINE__;

								return false;
							}
							break;
						default :
							echo "Error: " . __LINE__;

							return false;
							break;
					}

					@chmod($new_file . "-thumbnail", 0644);

					/**
					 * Frees the memory this used, so it can be used again.
					 */
					@imagedestroy($original_img_resource);
					@imagedestroy($new_img_resource);

					/**
					 * Keep a copy of the original file, just in case it is needed.
					 */
					if (@copy($original_file, $new_file . "-original")) {
						@chmod($new_file . "-original", 0644);
					}

					return true;
				}
			} else {
				echo "Error: " . __LINE__;

				return false;
			}
		} else {
			echo "Error: " . __LINE__;

			return false;
		}
	} else {
		echo "Error: " . __LINE__;

		return false;
	}
}

/**
 * Wrapper function for html_entities.
 *
 * @param string $string
 * @return string
 */
function html_encode($string) {
	return htmlentities($string, ENT_QUOTES, DEFAULT_CHARSET);
}

/**
 * Wrapper for PHP's html_entities_decode function.
 *
 * @param string $string
 * @return string
 */
function html_decode($string) {
	return html_entity_decode($string, ENT_QUOTES, DEFAULT_CHARSET);
}

/**
 * Function to select and return all event ids which have a possible connected
 * rotation id, and return them as an array.
 */
function get_event_rotation_ids() {
	global $db;
	$events = array();
	$query = "SELECT `category_id`, `rotation_id` FROM `categories` WHERE `rotation_id` > 0";
	$categories = $db->GetAll($query);
	if ($categories) {
		foreach ($categories as $category) {
			$query = "SELECT `event_id` FROM `events` WHERE `category_id` = " . $db->qstr($category["category_id"]);
			$event_ids = $db->GetAll($query);
			if ($event_ids) {
				foreach ($event_ids as $event_id) {
					$events[] = array("event_id" => $event_id["event_id"],
						"rotation_id" => $category["rotation_id"]);
				}
			}
		}
	}
	return $events;
}

/**
 * This function loads the current progress based on an qprogress_id.
 *
 * @global object $db
 * @param int $qprogress_id
 * @return array Returns the users currently progress or returns false if there
 * is an error.
 */
function quiz_load_progress($qprogress_id = 0) {
	global $db;

	$output = array();

	if ($qprogress_id = (int) $qprogress_id) {
		/**
		 * Grab the specified progress identifier, but you better be sure this
		 * is the correct one, and the results are being returned to the proper
		 * user.
		 */
		$query = "	SELECT *
						FROM `quiz_progress`
						WHERE `qprogress_id` = " . $db->qstr($qprogress_id);
		$progress = $db->GetRow($query);
		if ($progress) {
			/**
			 * Add all of the qquestion_ids to the $output array so they're set.
			 */
			$query = "SELECT * FROM `quiz_questions` WHERE `quiz_id` = " . $db->qstr($progress["quiz_id"]) . " ORDER BY `question_order` ASC";
			$questions = $db->GetAll($query);
			if ($questions) {
				foreach ($questions as $question) {
					$output[$question["qquestion_id"]] = 0;
				}
			} else {
				return false;
			}

			/**
			 * Update the $output array with any currently selected responses.
			 */
			$query = "	SELECT *
							FROM `quiz_progress_responses`
							WHERE `qprogress_id` = " . $db->qstr($qprogress_id);
			$responses = $db->GetAll($query);
			if ($responses) {
				foreach ($responses as $response) {
					$output[$response["qquestion_id"]] = $response["qqresponse_id"];
				}
			}
		} else {
			return false;
		}
	}

	return $output;
}

?>