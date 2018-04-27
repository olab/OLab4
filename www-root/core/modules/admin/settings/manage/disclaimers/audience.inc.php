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
 * This file is part of the User Disclaimers management feature and lists the users that have approve or decline
 * the User Disclaimer
 *
 * @author Organisation: Queens University
 * @author Unit: Medtech Unit
 * @author Developer: Jonatan Caraballo <jch9@queensu.ca>
 * @copyright Copyright 2017. All Rights Reserved.
 *
 */

if (!defined("PARENT_INCLUDED") || !defined("IN_CONFIGURATION")) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "read", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    $BREADCRUMB[]  = array("url" => ENTRADA_URL . "/admin/settings/manage/disclaimers?org=" . $ORGANISATION_ID, "title" => "User Disclaimer Audience");

    if (isset($_GET["disclaimer_id"]) && $tmp_input = clean_input($_GET["disclaimer_id"], "int")) {
        $disclaimer_id = $tmp_input;

        $disclaimer = Models_Disclaimers::fetchRowByID($disclaimer_id);
        if ($disclaimer) {
            $disclaimers_audience_users = Models_Disclaimer_Audience_Users::fetchAllByDisclaimerID($disclaimer_id);
            ?>
            <h1><?php echo html_encode($disclaimer->getDisclaimerTitle()); ?></h1>
            <h2><?php echo $translate->_("User Disclaimer Audience"); ?></h2>

            <?php
            if ($disclaimers_audience_users) {

                foreach ($disclaimers_audience_users as $disclaimers_audience_user) {
                    if ($disclaimers_audience_user && is_object($disclaimers_audience_user)) {
                        if ($user = Models_User::fetchRowByID($disclaimers_audience_user->getProxyID())) {
                            $audience_row = "";
                            $audience_row .= "<tr class=\"" . ($disclaimers_audience_user->getApproved() == 1 ? "success" : "error") . "\">";
                            $audience_row .= "<td>" . $user->getFullname(false) . " <span class=\"muted\">&lt;" . $user->getEmail() . "&gt;</span></td>";
                            $audience_row .= "<td>" . date("Y-m-d", $disclaimers_audience_user->getUpdatedDate()) . "</td>";
                            $audience_row .= "</tr>";
                            ($disclaimers_audience_user->getApproved() == 1 ? $approved_audience .= $audience_row : $declined_audience .= $audience_row);
                        }
                    }
                }
                ?>
                <ul class="nav nav-tabs">
                    <li class="active"><a href="#approved" data-toggle="tab"><?php echo $translate->_("Approved"); ?></a></li>
                    <li><a href="#declined" data-toggle="tab"><?php echo $translate->_("Declined"); ?></a></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="approved">
                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th class="fullname span8">
                                    <?php echo $translate->_("Full Name"); ?>
                                </th>
                                <th class="date span3">
                                    <?php echo $translate->_("Date"); ?>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php echo ($approved_audience != "" ? $approved_audience : "<tr><td colspan=\"2\">" . $translate->_("None has declined this user disclaimer yet.") . "</td></tr>"); ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="tab-pane" id="declined">
                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th class="fullname span8">
                                    <?php echo $translate->_("Full Name"); ?>
                                </th>
                                <th class="date span3">
                                    <?php echo $translate->_("Date"); ?>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php echo ($declined_audience != "" ? $declined_audience : "<tr><td colspan=\"2\">" . $translate->_("None has declined this user disclaimer yet.") . "</td></tr>"); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php
            } else {
                add_notice($translate->_("None has approved or declined this User Disclaimer."));
                echo display_notice();
            }
        } else {
            add_error($translate->_("Has ocurred an error attempting to get data from this User Disclaimer"));

            echo display_error();

            application_log("error", $ERRORSTR . "User Disclaimer ID: " . $disclaimer_id);
        }
    } else {
        add_error($translate->_("The User Disclaimer ID has not been provided or it is not valid."));

        echo display_error();

        application_log("error", $ERRORSTR);
    }
}