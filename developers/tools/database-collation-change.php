#!/usr/bin/php
<?php
/**
 * Entrada Tools [ http://www.entrada-project.org ]
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
 * Change Collation Of MySQL Database
 * 
 * Run this script to change the collation of a specific MySQL database, as well
 * as all of the tables and fields within that database.
 * 
 * Caution: Making a backup of your existing database prior to running this tool
 * is _highly_ recommended.
 * 
 * Instructions:
 * 
 * 1. Simply run "./database-collation-change.php -to NEW-COLLATION" to make the
 *    required collation change.
 *    
 *    To get all supported collations on your MySQL server run the following SQL
 *    query:  SHOW CHARACTER SET;
 *
 * @author Unit: Medical Education Technology Unit
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../../www-root/core",
    realpath(dirname(__FILE__) . "/includes"),
    dirname(__FILE__) . "/../../www-root/core/library",
    dirname(__FILE__) . "/../../www-root/core/library/vendor",
    get_include_path(),
)));

@ini_set("auto_detect_line_endings", 1);
@ini_set("magic_quotes_runtime", 0);
set_time_limit(0);

if((!isset($_SERVER["argv"])) || (@count($_SERVER["argv"]) < 1)) {
	echo "<html>\n";
	echo "<head>\n";
	echo "	<title>Processing Error</title>\n";
	echo "</head>\n";
	echo "<body>\n";
	echo "This file should be run by command line only.";
	echo "</body>\n";
	echo "</html>\n";
	exit;
}

require_once("autoload.php");

require_once("config.inc.php");
require_once("dbconnection.inc.php");
require_once("functions.inc.php");

$ACTION				= ((isset($_SERVER["argv"][1])) ? trim($_SERVER["argv"][1]) : "-usage");
$COLLATION			= ((isset($_SERVER["argv"][2])) ? clean_input($_SERVER["argv"][2], array("nows", "lowercase")) : "");
$CHARSET			= "";

$CHANGE_FIELD_TYPES = array("varchar", "char", "tinytext", "mediumtext", "longtext", "text", "enum", "set");

switch($ACTION) {
	case "-to" :
		if($COLLATION) {
			$pieces = explode("_", $COLLATION);
			if((is_array($pieces)) && (isset($pieces[0])) && ($tmp_input = $pieces[0])) {
				$CHARSET = $tmp_input;
				
				if($db->Execute("ALTER DATABASE `".DATABASE_NAME."` DEFAULT CHARACTER SET ".$CHARSET." COLLATE ".$COLLATION)) {
					// Success, changed database collation.
					echo "\n[SUCCESS] Changed ".DATABASE_NAME." to ".$COLLATION;
					
					$db_tables = $db->MetaTables("TABLES");
					if((is_array($db_tables)) && (count($db_tables))) {
						foreach($db_tables as $db_table) {
							if($db->Execute("ALTER TABLE `".$db_table."` DEFAULT CHARACTER SET ".$CHARSET." COLLATE ".$COLLATION)) {
								// Success, changed table collation.
								echo "\n[SUCCESS] Changed ".DATABASE_NAME.".".$db_table." to ".$COLLATION;

								$db_fields = $db->MetaColumns($db_table, false);
								if((is_array($db_fields)) && (count($db_fields))) {
									
									$field_names	= array();
									$field_changes	= array();
									foreach($db_fields as $db_field) {
										if(in_array(strtolower($db_field->type), $CHANGE_FIELD_TYPES)) {
											$field_names[]		= $db_field->name;
											$field_changes[]	= "CHANGE `".$db_field->name."` `".$db_field->name."` ".$db_field->type.(((isset($db_field->max_length)) && ((int) $db_field->max_length > 0)) ? "( ".(((isset($db_field->enums)) && (is_array($db_field->enums))) ? implode(",", $db_field->enums) : $db_field->max_length)." )" : "")." CHARACTER SET ".$CHARSET." COLLATE ".$COLLATION.(((isset($db_field->not_null)) && ((int) $db_field->not_null)) ? " NOT NULL" : "").(((isset($db_field->has_default)) && ((int) $db_field->has_default)) ? " DEFAULT '".$db_field->default_value."'" : "");
										} else {
											// Skipping, not a changable field type.
										}
									}
									
									if((is_array($field_changes)) && (count($field_changes))) {
									
										$query = "ALTER TABLE ".$db_table." ".implode(", ", $field_changes);
										if($db->Execute($query)) {
											// Success, changed field collation.
											echo "\n[SUCCESS] Changed ".DATABASE_NAME.".".$db_table." (".implode(", ", $field_names).") to ".$COLLATION;
										} else {
											// Error, Unable to change field collation.
											echo "\n[ERROR  ] Unable to change ".DATABASE_NAME.".".$db_table." (".implode(", ", $field_names).") to ".$COLLATION;
										}
									} else {
										// Skipping, there are no fields.
									}
								} else {
									// Skipping, there are no fields.
								}
							} else {
								// Error, Unable to change table collation.	
								echo "\n[ERROR  ] Unable to change ".DATABASE_NAME.".".$db_table." to ".$COLLATION;
							}
						}	
					} else {
						// Skipping, there are no tables.
					}
				} else {
					// Error, Unable to change database collation.
					echo "\n[ERROR  ] Unable to change ".DATABASE_NAME." to ".$COLLATION;
				}
			} else {
				// Error, Unable to figure out the character set.
				echo "\n[ERROR  ] Unable to figure out the character set of ".$COLLATION;
			}
		} else {
			// Error, You did not provide a collation to change to (i.e. latin1_swedish_ci).
			echo "\n[ERROR  ] You did not provide a collation to change your database to (i.e. latin1_swedish_ci).";
		}
	break;
	case "-usage" :
	default :
		echo "\nUsage: ".basename(__FILE__)." [options]";
		echo "\n   -usage               Brings up this help screen.";
		echo "\n   -to [collation]      Specify the collation you want to everything change to.";
	break;
}
echo "\n\n";
?>