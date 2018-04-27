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
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Harry Brundage <hbrundage@qmed.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_GRADEBOOK"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->amIAllowed("gradebook", "update", false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	/**
	 * Update requested column to sort by.
	 * Valid: director, name
	 */
	if (isset($_GET["sb"])) {
		if (@in_array(trim($_GET["sb"]), array("type", "code", "name"))) {
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]	= trim($_GET["sb"]);
		}

		$_SERVER["QUERY_STRING"]	= replace_query(array("sb" => false));
	} else {
		if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"])) {
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] = "code"; // sort by course code by default
		}
	}

	/**
	 * Update requested order to sort by.
	 * Valid: asc, desc
	 */
	if (isset($_GET["so"])) {
		$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"] = ((strtolower($_GET["so"]) == "desc") ? "desc" : "asc");

		$_SERVER["QUERY_STRING"] = replace_query(array("so" => false));
	} else {
		if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"])) {
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"] = "asc"; // sort in ascending order by default
		}
	}

	/**
	 * Update requested number of rows per page.
	 * Valid: any integer really.
	 */
	if ((isset($_GET["pp"])) && ((int) trim($_GET["pp"]))) {
		$integer = (int) trim($_GET["pp"]);

		if (($integer > 0) && ($integer <= 250)) {
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"] = $integer;
		}

		$_SERVER["QUERY_STRING"] = replace_query(array("pp" => false));
	} else {
		if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"])) {
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"] = DEFAULT_ROWS_PER_PAGE;
		}
	}

	/**
	 * Update requested organisation filter
	 * Valid: any integer really.
	 */

	/**
	 * Check if preferences need to be updated on the server at this point.
	 */
	preferences_update($MODULE, $PREFERENCES);

	$JQUERY[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/" . $MODULE . "/" . $MODULE . ".js\"></script>\n";
    $HEAD[] = "<link rel=\"stylesheet\" href=\"".  ENTRADA_URL ."/css/courses/courses.css\" />";

	echo "<h1>Manage Gradebooks</h1>";
	?>

	<div class="search-bar" id="search-bar">
		<div class="row-fluid space-below medium">
			<div class="pull-left">
				<input type="text" class="input-large search-icon" placeholder="Search Gradebooks..." id="gradebook-search">
			</div>
		</div>
	</div>

	<div id="courses-msgs">
		<div id="gradebook-loading" class="hide">
			<p><?php echo $translate->_("Loading Gradebooks..."); ?></p>
			<img src="<?php echo ENTRADA_URL ."/images/loading.gif" ?>" />
		</div>
		<div id="gradebook-no-results" class="hide">
			<p><?php echo $translate->_("No Gradebook Found"); ?></p>
		</div>
	</div>
	
	<table id="gradebook-table" class="table table-bordered table-striped" summary="List of Gradebooks">
		<thead>
            <tr>
                <th width="25%" class="general">
                    Category
                    <i class="fa fa-sort<?php echo (($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "type") ?
                        "-".$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"] : ""); ?>  gradebook-sort" aria-hidden="true" data-name="type" data-order=
                                                            "<?php echo (($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "type") ? $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"] : ""); ?>">
                    </i>
                </th>
                <th width="25%" class="general">
                    Code
                    <i class="fa fa-sort<?php echo (($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "code") ?
                        "-".$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"] : ""); ?>  gradebook-sort" aria-hidden="true" data-name="code" data-order=
                                                        "<?php echo (($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "code") ? $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"] : ""); ?>">
                    </i>
                </th>
                <th width="50%" class="title">
                    Name
                    <i class="fa fa-sort<?php echo (($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "name") ?
                        "-".$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"] : ""); ?>  gradebook-sort" aria-hidden="true" data-name="name" data-order=
                                                      "<?php echo (($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "name") ? $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"] : ""); ?>">
                    </i>
                </th>
            </tr>
		</thead>
		<tbody>
		</tbody>
	</table>

	<div class="row-fluid">
		<a id="load-more-gradebook" class="btn btn-block">
            Showing <span id="total_loaded">0</span> of <span id="total_available"></span> total gradebooks
        </a>
	</div>
<?php
}
