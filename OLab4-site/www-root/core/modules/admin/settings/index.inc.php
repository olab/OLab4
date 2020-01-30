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
 * @author Unit: MEdTech Unit
 * @author Developer: Brandon Thorn <brandon.thorn@queensu.ca>
 * @copyright Copyright 2011 Queen's University. All Rights Reserved.
 *
*/

if (!defined("PARENT_INCLUDED") || !defined("IN_CONFIGURATION")) {
	exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "read", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	?>
	<h1><?php echo $translate->_("Manage Organisations"); ?></h1>
	<?php
    if($ENTRADA_ACL->amIAllowed("configuration", "create")) {
        ?>
        <div class="row-fluid space-below medium">
        	<div class="pull-right">
                <a href="<?php echo ENTRADA_URL; ?>/admin/settings?section=add" class="btn btn-success"><i class="icon-plus-sign icon-white"></i> <?php echo $translate->_("Add New Organisation"); ?></a>
            </div>
        </div>
        <?php
    }

    $query = "	SELECT a.*
                FROM `" . AUTH_DATABASE . "`.`organisations` AS a
                WHERE a.`organisation_active` = '1'
                ORDER BY a.`organisation_title` ASC";
	$results = $db->GetAll($query);
	if ($results) {
		$organisations = array();
		foreach ($results as $result) {
			if ($ENTRADA_ACL->amIAllowed(new ConfigurationResource($result["organisation_id"]), "update")) {
				$organisations[] = $result;
			}
		}

		if (!$ENTRADA_ACL->amIAllowed("configuration", "create")) {
			if (count($results) == 1) {
				$url = ENTRADA_URL."/admin/settings/manage?org=".(int) $results[0]["organisation_id"];
				header("Location: ".$url);
				exit;
			}		
		}

		if (!empty($organisations)) {
				?>
				<div id="organisations-section">
					<form action="<?php echo ENTRADA_URL; ?>/admin/settings?section=delete" method="post">
						<table class="tableList" cellspacing="0" cellpadding="1" border="0" summary="List of Organisations">
							<colgroup>
								<col class="modified" />
								<col class="title" />
							</colgroup>
							<thead>
								<tr>
									<td class="modified">&nbsp;</td>
									<td class="title">Title</td>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach ($organisations as $result) {
									$url = ENTRADA_URL."/admin/settings/manage?org=".(int) $result["organisation_id"];

									echo "<tr>\n";
									echo "	<td><input type=\"checkbox\" name = \"remove_ids[]\" value = \"".html_encode($result["organisation_id"])."\"".($ENTRADA_USER->GetActiveOrganisation() == $result["organisation_id"] ? " disabled=\"disabled\"" : "")." /></td>\n";
									echo "	<td><a href=\"".$url."\">".html_encode($result["organisation_title"])."</a></td>\n";
									echo "</tr>\n";
								}
								?>
							</tbody>
						</table><br />
						<input type="submit" class="btn btn-danger" value="<?php echo $translate->_("Delete Selected"); ?>" />

					</form>
				</div>
		<?php
		} else {
			add_notice("You don't appear to have access to change any organisations. If you feel you are seeing this in error, please contact your system administrator.");
			echo display_notice();
		}
	} else {
		add_notice("You don't appear to have access to change any organisations. If you feel you are seeing this in error, please contact your system administrator.");
		echo display_notice();
	}
}