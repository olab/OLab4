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
 * Display Community Modules Permissions Array
 * 
 * This is a script that you can use to see what the module_permissions field in
 * the entrada.communities_modules table should look like. This field is just a
 * serialized array, but it is handy to see it like this sometimes.
 * 
 * Instructions:
 * 1. Add an entry to the $modules array with the key being the modules name and
 *    the value being an array containing the module's actions and associated
 *    permission level.
 * 
 * 2. Permision level explaination:
 *    0 - non-administrative action
 *    1 - administrative action
 *   
 * @author Unit: Medical Education Technology Unit
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */

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

$modules					= array();

$modules["announcements"]	= array(
								"add"				=> 1,
								"delete"			=> 1,
								"edit"				=> 1,
								"index"				=> 0
								);

$modules["discussions"]		= array(
								"add-forum"			=> 1,
								"add-post"			=> 0,
								"delete-forum"		=> 1,
								"delete-post"		=> 0,
								"edit-forum"		=> 1,
								"edit-post"			=> 0,
								"index"				=> 0,
								"reply-post"		=> 0,
								"view-forum"		=> 0,
								"view-post"			=> 0
								);

$modules["galleries"]		= array(
								"add-comment"		=> 0,
								"add-gallery"		=> 1,
								"add-photo"			=> 0,
								"delete-comment"	=> 0,
								"delete-gallery"	=> 1,
								"delete-photo"		=> 0,
								"edit-comment"		=> 0,
								"edit-gallery"		=> 1,
								"edit-photo"		=> 0,
								"index"				=> 0,
								"view-gallery"		=> 0,
								"view-photo"		=> 0
								);

$modules["sharing"]		= array(
								"add-comment"		=> 0,
								"add-folder"		=> 1,
								"add-file"			=> 0,
								"add-revision"		=> 0,
								"delete-comment"	=> 0,
								"delete-folder"		=> 1,
								"delete-file"		=> 0,
								"delete-revision"	=> 0,
								"edit-comment"		=> 0,
								"edit-folder"		=> 1,
								"edit-file"			=> 0,
								"index"				=> 0,
								"view-folder"		=> 0,
								"view-file"			=> 0
								);

$modules["polls"]		= array(
								"add-poll"			=> 1,
								"delete-poll"		=> 1,
								"edit-poll"			=> 1,
								"view-poll"			=> 0,
								"vote-poll"			=> 0,
								"index"				=> 0,
								"my-votes"			=> 0
								);

echo "\nShowing Module Permissions:";
echo "\n================================================================\n";
foreach($modules as $module => $permissions) {
	echo "\n".str_pad($module.":", 17, " ", STR_PAD_RIGHT).serialize($permissions)."\n";
}
echo "\n================================================================\n";
?>