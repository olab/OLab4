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
 * ePortfolio public index
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @author Developer: Josh Dillon <josh.dillon@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
 */

if (!defined("PARENT_INCLUDED")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("eportfolio", "read")) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    
    echo "<h1>Portfolio Reflection</h1>";
    
    if (isset($_GET["entry_id"]) && $tmp_input = clean_input($_GET["entry_id"], "int")) {
        $reflection = Models_Eportfolio_Entry::fetchRow($tmp_input);
    }
    
    if (isset($reflection) && ($reflection->getProxyID() == $ENTRADA_USER->getProxyID() || $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] != "student") && $reflection->getType() == "reflection") {
        
        $reflection_data = $reflection->getEdataDecoded();
        
        ?>
        <h2><?php echo $reflection_data["title"]; ?></h2>
        <div class="well">
            <?php echo $reflection_data["description"]; ?>
        </div>
        <a href="<?php echo ENTRADA_URL; ?>/profile/eportfolio" class="btn">Back</a>
        <?php
        
    } else {
        add_error("Reflection not found.");
        echo display_error();
    }
    
}