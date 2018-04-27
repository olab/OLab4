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
 * This file is part of the User Disclaimers feature and lists the User Disclaimers
 * in the system for the currently active organisation.
 *
 * @author Organisation: Queens University
 * @author Unit: Medtech Unit
 * @author Developer: Jonatan Caraballo <jch9@queensu.ca>
 * @copyright Copyright 2017 Queens University. All Rights Reserved.
 *
 */

if (!defined("PARENT_INCLUDED") || !defined("IN_CONFIGURATION")) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !$_SESSION["isAuthorized"]) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "read",false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {

    $disclaimers = Models_Disclaimers::fetchAllByOrganisationID($ORGANISATION_ID);
    ?>
    <h1><?php echo $translate->_("User Disclaimers"); ?></h1>
    <h2><?php echo $translate->_("Manage User Disclaimers"); ?></h2>

    <div class="row-fluid space-below medium">
        <div class="pull-right">

            <a id="add_new_disclaimer" href="<?php echo ENTRADA_URL; ?>/admin/settings/manage/disclaimers?section=add&amp;org=<?php echo $ORGANISATION_ID;?>" class="pull-right btn btn-success"><i class="icon-plus-sign icon-white"></i>  <?php echo $translate->_("Add User Disclaimer"); ?></a>
        </div>
    </div>
    <?php

    if ($disclaimers) {
        ?>
        <form action ="<?php echo ENTRADA_URL;?>/admin/settings/manage/disclaimers?section=delete&amp;org=<?php echo $ORGANISATION_ID;?>" method="post">
            <table class="table table-bordered table-striped" cellspacing="0" cellpadding="1" border="0">
                <colgroup>
                    <col class="modified"/>
                    <col class="title" />
                    <col class="active" />
                </colgroup>
                <thead>
                    <tr>
                        <th class="title span7">
                            <?php echo $translate->_("Name");?>
                        </th>
                        <th class="date span2">
                            <?php echo $translate->_("Effective From");?>
                        </th>
                        <th class="date span2">
                            <?php echo $translate->_("Effective Until");?>
                        </th>
                        <th class="options span1"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        foreach ($disclaimers as $disclaimer) {
                            if ($disclaimer && is_object($disclaimer)) {
                                echo "<tr id=\"disclaimer_" . $disclaimer->getID() . "\" " . ($disclaimer->getDisclaimerExpireDate() && $disclaimer->getDisclaimerExpireDate() < time() ? "class=\"warning\"" : "") . ">";
                                echo "<td><a class=\"title\" href=\"" . ENTRADA_URL . "/admin/settings/manage/disclaimers?section=edit&amp;org=" . $ORGANISATION_ID . "&amp;disclaimer_id=" . $disclaimer->getID() . "\">" . $disclaimer->getDisclaimerTitle() . "</a></td>";
                                echo "<td class=\"text-center\">" . ($disclaimer->getDisclaimerIssueDate() ? date("Y-m-d", $disclaimer->getDisclaimerIssueDate()) : "-") . "</td>";
                                echo "<td class=\"text-center\">" . ($disclaimer->getDisclaimerExpireDate() ? date("Y-m-d", $disclaimer->getDisclaimerExpireDate()) : "-") . "</td>";
                                echo " <td class=\"text-center\">
                                        <div class=\"btn-group\">
                                            <a id=\"disclaimer_options_" . $disclaimer->getID() . "\" class=\"btn\" href=\"" . ENTRADA_URL . "/admin/settings/manage/disclaimers?section=audience&amp;org=" . $ORGANISATION_ID . "&amp;disclaimer_id=" . $disclaimer->getID() . "\"><i class=\"fa fa-eye\"></i></a> 
                                            <a data-id=\"" . $disclaimer->getID() . "\" id=\"delete_disclaimer_" . $disclaimer->getID() . "\" class=\"btn delete_disclaimer\" href=\"#delete_disclaimer_modal\" role=\"button\" data-toggle=\"modal\"><i class=\"fa fa-trash\"></i></a>
                                        </div>
                                        </td>";
                                echo "</tr>";
                            }
                        }
                    ?>
                </tbody>
            </table>
        </form>
        <div id="delete_disclaimer_modal" class="modal hide fade responsive-modal">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h3 id="myModalLabel"><?php echo $translate->_("Delete User Disclaimer"); ?></h3>
            </div>
            <div class="modal-body">
                <div id="delete_modal_msg"></div>
                <p><?php echo $translate->_("Please confirm you wish to delete the "); ?> <b id="disclaimer_title"></b> <?php echo $translate->_("disclaimer"); ?></p>
            </div>
            <div class="modal-footer">
                <button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
                <button class="btn btn-danger delete_modal_btn">Delete</button>
            </div>
        </div>
        <script>
            jQuery(document).ready(function () {
                jQuery(".delete_disclaimer").on("click", function() {
                    disclaimer_id = jQuery(this).data("id");
                    disclaimer_title = jQuery("#disclaimer_" + disclaimer_id).find(".title").html();
                    jQuery("#delete_disclaimer_modal #disclaimer_title").html(disclaimer_title);
                    jQuery("#delete_disclaimer_modal .delete_modal_btn").attr("data-id", disclaimer_id);
                });
                jQuery(".delete_modal_btn").on("click", function() {
                    disclaimer_id = jQuery(this).attr("data-id");

                    jQuery.post(ENTRADA_RELATIVE + "/api/disclaimers.api.php", {
                        method: "delete-disclaimer",
                        disclaimer_id: disclaimer_id
                    }, function(response) {
                        if (response) {
                            json = jQuery.parseJSON(response);
                            if (json.status == "success") {
                                jQuery("#delete_disclaimer_modal").modal("hide");
                                jQuery("#delete_disclaimer_modal #delete_modal_btn").removeAttr("data-id");
                                jQuery("#disclaimer_" + json.data.disclaimer_id).remove();
                            } else {
                                jQuery("#delete_modal_msg").empty();
                                jQuery("#delete_modal_msg").html('<div class="alert alert-error">' + json.data + '</div>');
                            }
                        }
                    })
                });
            });
        </script>
        <?php
    } else {
        add_notice($translate->_("No user disclaimers for this organisation"));
        echo display_notice();
    }
}