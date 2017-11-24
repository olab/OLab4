
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
 * @author Unit: Health Sciences Education Technology Unit
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

if (!defined("PARENT_INCLUDED") || !defined("IN_CONFIGURATION")) {
	exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "delete", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/settings/manage/characteristics?section=delete&amp;org=".$ORGANISATION["organisation_id"], "title" => "Delete " . $translate->_("Characteristic"));
    ?>

    <h1>Delete <?php echo $translate->_("Characteristics"); ?></h1>

    <?php
    $PROCESSED["remove_ids"] = array();

	if (isset($_POST["remove_ids"]) && is_array($_POST["remove_ids"]) && !empty($_POST["remove_ids"])) {
		foreach ($_POST["remove_ids"] as $id) {
            /**
             * @todo We need to make sure this user has permission to remove this record. What if it's part of a separate organisation? Line 30 will pass because there is no assertion.
             */
            $PROCESSED["remove_ids"][] = (int) $id;
		}
	}
	
	if ($PROCESSED["remove_ids"]) {
		switch($STEP) {
			case 2:

                $deactivated = array();

                foreach ($PROCESSED["remove_ids"] as $id) {
                    $type = Models_Assessment_Characteristic::get($id);
                    if ($type) {
                        if ($type->delete()) {
                            $deactivated[] = $type->getTitle();
                        }
                    }
                }

                $total_deactivated = count($deactivated);

                $url = ENTRADA_URL."/admin/settings/manage/characteristics?org=".$ORGANISATION_ID;

                if ($total_deactivated) {
                    add_success("You have successfully deactivated ".$total_deactivated." ".$translate->_("Characteristic").($total_deactivated != 1 ? "s" : "").".<br /><br />You will now be redirected to the ".$translate->_("Assessment Types")." index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.");
                } else {
                    add_notice("We were unable to deactivate any of the ".$translate->_("Assessment Types")." you selected. Please try your request again.");
                }

                if (has_success()) {
					echo display_success();
                }

				if (has_notice()) {
					echo display_notice();
                }

				$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
            break;
			case 1:
			default:
				echo display_notice("Please review the following " . $translate->_("Assessment Types") . " to ensure that you wish to <strong>deactivate</strong> them.");
    			?>
    			<form action ="<?php echo ENTRADA_URL."/admin/settings/manage/characteristics?section=delete&org=".$ORGANISATION_ID."&step=2"; ?>" method="post">
                    <table class="tableList" cellspacing="0" summary="List of <?php echo $translate->_("Assessment Types"); ?> To Be Deleted">
                        <colgroup>
                            <col class="modified" />
                            <col class="title" />
                            <col class="title" />
                        </colgroup>
                        <thead>
                        <tr>
                            <td class="modified">&nbsp;</td>
                            <td class="type">Type</td>
                            <td class="title"><?php echo $translate->_("Characteristic"); ?></td>
                        </tr>
                        </thead>
                        <tbody>
							<?php 
                            foreach ($PROCESSED["remove_ids"] as $id) {
                                $type = Models_Assessment_Characteristic::get($id);
                                if ($type) {
                                    ?>
                                    <tr>
                                        <td><input type="checkbox" value="<?php echo (int) $type->getID(); ?>" name ="remove_ids[]" checked="checked" /></td>
                                        <td><?php echo ucwords(strtolower($type->getType())); ?></td>
                                        <td><?php echo html_encode($type->getTitle()); ?></td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
						</tbody>
					</table>
					<br />
					<input type="submit" value="Confirm Delete" class="btn btn-danger"/>
				</form>
				<?php
            break;
		}
	} else {
        $url = ENTRADA_URL."/admin/settings/manage/characteristics?org=".$ORGANISATION_ID;

        $ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";

		add_error("There were no " . $translate->_("Assessment Types") . " selected to be deleted. You will now be redirected to the " . $translate->_("Assessment Types") . " index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.");

		echo display_error();
	}
}
