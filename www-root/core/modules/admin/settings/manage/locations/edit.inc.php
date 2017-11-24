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
 * This file edits a building in the `global_lu_buildings` table.
 *
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Robert Fotino <robert.fotino@gmail.com>
 * @copyright Copyright 2014 David Geffen School of Medicine at UCLA. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CONFIGURATION"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "create", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:" . html_encode($AGENT_CONTACTS["administrator"]["email"]) . "\">" . html_encode($AGENT_CONTACTS["administrator"]["name"]) . "</a> for assistance.");

    echo display_error();

    application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] do not have access to this module [" . $MODULE . "]");
} else {

    $BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/settings/manage/locations?" . replace_query(array("section" => "edit", "building_id" => $_GET["building_id"])) . "&amp;org=" . $ORGANISATION_ID, "title" => "Edit Building");

    if ((isset($_GET["building_id"])) && ($building_id = clean_input($_GET["building_id"], array("notags", "trim")))) {
        $PROCESSED["building_id"] = $building_id;
    }

    $MODULE_TEXT   = $translate->_($MODULE);
    $BUILDING_TEXT = $MODULE_TEXT["building"]["building"];
    $ROOM_TEXT     = $MODULE_TEXT["building"]["room"]["manage"];


    // Error Checking
    switch ($STEP) {
        case 2 :
            /**
             * Required field "building_name" / Building Name
             */
            if (isset($_POST["building_name"]) && ($building_name = clean_input($_POST["building_name"], array("notags", "trim")))) {
                $PROCESSED["building_name"] = $building_name;
				$building = Models_Location_Building::fetchRowByName($building_name, $ENTRADA_USER->getOrganisationId());
				if ($building  && is_object($building) && $PROCESSED["building_id"] != $building->getID()) {
					add_error("The Building Name you selected already exists, please choose another.");
				}
            } else {
                $ERROR++;
                $ERRORSTR[] = "The <strong>Building Name</strong> is a required field.";
                add_error($BUILDING_TEXT["error_msg"]["name"]);
            }

            if (isset($_POST["building_code"]) && ($building_code = clean_input($_POST["building_code"], array("notags", "trim")))) {
                $PROCESSED["building_code"] = $building_code;
				$building = Models_Location_Building::fetchRowByCode($building_code, $ENTRADA_USER->getOrganisationId());
				if ($building  && is_object($building) && $PROCESSED["building_id"] != $building->getID()) {
					add_error("The Building Code you selected already exists, please choose another.");
				}
            } else {
                add_error($BUILDING_TEXT["error_msg"]["code"]);
            }

            /**
             * Required field "building_address1" / Address Line 1.
             */
            if ((isset($_POST["building_address1"])) && ($address = clean_input($_POST["building_address1"], array("trim", "ucwords"))) && (strlen($address) >= 6) && (strlen($address) <= 255)) {
                $PROCESSED["building_address1"] = $address;
            } else {
                add_error($BUILDING_TEXT["error_msg"]["address_1"]);
            }

            /**
             * Non-required field "building_address2" / Address Line 2.
             */
            if ((isset($_POST["building_address2"])) && ($address = clean_input($_POST["building_address2"], array("trim", "ucwords"))) && (strlen($address) >= 6) && (strlen($address) <= 255)) {
                $PROCESSED["building_address2"] = $address;
            } else {
                $PROCESSED["building_address2"] = "";
            }

            /**
             * required field "building_city" / City.
             */
            if ((isset($_POST["building_city"])) && ($city = clean_input($_POST["building_city"], array("trim", "ucwords"))) && (strlen($city) >= 3) && (strlen($city) <= 35)) {
                $PROCESSED["building_city"] = $city;
            } else {
                add_error($BUILDING_TEXT["error_msg"]["city"]);
            }

            /**
             * required field "postcode" / Postal Code.
             */
            if ((isset($_POST["postcode"])) && ($postcode = clean_input($_POST["postcode"], array("trim", "uppercase"))) && (strlen($postcode) >= 5) && (strlen($postcode) <= 12)) {
                $PROCESSED["building_postcode"] = $postcode;
            } else {
                add_error($BUILDING_TEXT["error_msg"]["postal"]);
            }

            /**
             * Required filed "country_id" / Country
             */
            if ((isset($_POST["country_id"])) && ($tmp_input = clean_input($_POST["country_id"], "int"))) {
                $countries = Models_Country::fetchRowByID($tmp_input);
                if ($countries && is_object($countries)) {
                    $PROCESSED["country_id"]        = $tmp_input;
                    $PROCESSED["building_country"]  = $countries->getCountry();
                } else {
                    add_error($BUILDING_TEXT["error_msg"]["country"]);
                    application_log("error", "Unknown countries_id [" . $tmp_input . "] was selected. Database said: " . $db->ErrorMsg());
                }
            } else {
                add_error($BUILDING_TEXT["error_msg"]["country2"]);
            }

            /**
             * Required field "prov_state" / Province or State
             */
            if ((isset($_POST["prov_state"])) && ($tmp_input = clean_input($_POST["prov_state"], array("trim", "notags")))) {
                $PROCESSED["province_id"] = 0;
                if (ctype_digit($tmp_input) && ($tmp_input = (int) $tmp_input)) {
                    if ($PROCESSED["country_id"]) {
                        $provinces = Models_Province::fetchRowByID($tmp_input);
                        if ($provinces && is_object($provinces)) {
                            $PROCESSED["province_id"]       = $tmp_input;
                            $PROCESSED["building_province"] = $provinces->getProvince();
                        } else {
                            add_error($BUILDING_TEXT["error_msg"]["state1"]);
                        }
                    } else {
                        add_error($BUILDING_TEXT["error_msg"]["state2"]);
                    }
                } else {
                    add_error($BUILDING_TEXT["error_msg"]["state3"]);
                }                
            } else {
                $PROCESSED["province_id"]       = "";
                $PROCESSED["building_province"] = "";
            }

            if (!$ERROR) {
                $building = Models_Location_Building::fetchRowByID($PROCESSED["building_id"]);
                if ($building && is_object($building)) {
                    $building->fromArray($PROCESSED);
                    if ($building->update()) {
                        $url = ENTRADA_URL . "/admin/settings/manage/locations?org=" . $ORGANISATION_ID;

                        $success_message = $BUILDING_TEXT["success_msg"]["success_update_1"] . "<strong>" . html_encode($PROCESSED["building_name"]) . "</strong>" . $BUILDING_TEXT["success_msg"]["success_update_2"];
                        $success_message .= "<br /><br />" . $BUILDING_TEXT["success_msg"]["success_add_3"] . "<strong>" . $BUILDING_TEXT["success_msg"]["success_update_4"] . "</strong>" . $BUILDING_TEXT["success_msg"]["success_update_5"];
                        $success_message .= "<a href=\"" . $url . "\" style = \"font-weight: bold\" >" . $BUILDING_TEXT["success_msg"]["success_update_6"] . "</a>" . $BUILDING_TEXT["success_msg"]["success_update_7"];
                        add_success($success_message);

                        $ONLOAD[] = "setTimeout('window.location=\\'" . $url . "\\'', 5000)";
                        application_log("success", "Building [" . $PROCESSED["building_name"] . "] was changed in the system.");
                    } else {
                        add_error($BUILDING_TEXT["error_msg"]["update"]);
                        application_log("error", "There was an error inserting a Building. Database said: " . $db->ErrorMsg());
                    }
                }
            }

            if ($ERROR) {
                $STEP = 1;
            }
            break;
        case 1 :
        default :

            $building = Models_Location_Building::fetchRowByID($PROCESSED["building_id"]);
            if ($building && is_object($building)) {
                $PROCESSED["building_name"]     = $building->getBuildingName();
                $PROCESSED["building_code"]     = $building->getBuildingCode();
                $PROCESSED["building_address1"] = $building->getBuildingAddress1();
                $PROCESSED["building_address2"] = $building->getBuildingAddress2();
                $PROCESSED["building_city"]     = $building->getBuildingCity();
                $PROCESSED["building_country"]  = $building->getBuildingCountry();
                $PROCESSED["building_province"] = $building->getBuildingProvince();
                $PROCESSED["building_postcode"] = $building->getBuildingPostcode();

                $country = Models_Country::fetchRowByCountry($PROCESSED["building_country"]);
                if ($country && is_object($country)) {
                    $PROCESSED["country_id"] = $country->getID();
                }

                $province = Models_Province::fetchRowByProvinceName($PROCESSED["building_province"]);
                if ($province && is_object($province)) {
                    $PROCESSED["province_id"] = $province->getID();
                }
            }

            break;
    }

    // Display Content
    switch ($STEP) {
        case 2 :
            if ($SUCCESS) {
                echo display_success();
            }

            if ($NOTICE) {
                echo display_notice();
            }

            if ($ERROR) {
                echo display_error();
            }
            break;
        case 1 :
        default:
            if ($ERROR) {
                echo display_error();
            }

        $url_country_id  = ((!isset($PROCESSED["country_id"]) && defined("DEFAULT_COUNTRY_ID") && DEFAULT_COUNTRY_ID) ? DEFAULT_COUNTRY_ID : 0);
        $url_province_id = ((!isset($PROCESSED["province_id"]) && defined("DEFAULT_PROVINCE_ID") && DEFAULT_PROVINCE_ID) ? DEFAULT_PROVINCE_ID : 0);

        /**
         * Determine whether the Google Maps can be shown.
         */
        if ((defined("GOOGLE_MAPS_API")) && (GOOGLE_MAPS_API != "")) {
            $HEAD[] = "<script type=\"text/javascript\" src=\"".GOOGLE_MAPS_API."\"></script>";

        }
        $HEAD[] = "<script>var country_id = \"" . $PROCESSED["country_id"] . "\";</script>";
        $HEAD[] = "<script>var province_id = \"" . $PROCESSED["province_id"] . "\";</script>";
        $HEAD[] = "<script>var url_province_id = \"" . $url_province_id . "\";</script>";
        $HEAD[] = "<script>var url_country_id = \"" . $url_country_id . "\";</script>";
        $HEAD[] = "<script>var province_web_url = \"" . webservice_url("province") . "\";</script>";

        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/locations/locations.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
        require_once(ENTRADA_ABSOLUTE."/javascript/locations/locations_googlemaps.js.php");

    ?>

    <h1><?php echo $BUILDING_TEXT["edit_building"];?></h1>

    <h2><?php echo $BUILDING_TEXT["building_information"];?></h2>

    <div class="row-fluid">
        <div class="span5">
            <form class="form-horizontal" action="<?php echo ENTRADA_URL . "/admin/settings/manage/locations" . "?" . replace_query(array("step" => 2)) . "&org=" . $ORGANISATION_ID; ?>" id ="building_edit_form" method="post">

                <div class="control-group">
                    <label for="building_name" class="control-label form-required">
                        <?php echo $BUILDING_TEXT["label_name"];?>
                    </label>
                    <div class="controls">
                        <input class="input-large"  type="text" id="building_name" name="building_name" value="<?php echo ((isset($PROCESSED["building_name"])) ? html_encode($PROCESSED["building_name"]) : ""); ?>" maxlength="60" />
                    </div>
                </div>

                <div class="control-group">
                    <label for="building_code" class="control-label form-required">
                        <?php echo $BUILDING_TEXT["label_code"];?>
                    </label>
                    <div class="controls">
                        <input class="input-large"  type="text" id="building_code" name="building_code" value="<?php echo ((isset($PROCESSED["building_code"])) ? html_encode($PROCESSED["building_code"]) : ""); ?>" maxlength="10" />
                    </div>
                </div>

                <div class="control-group">
                    <label for="building_address1" class="control-label form-required">
                        <?php echo $BUILDING_TEXT["label_address1"];?>
                    </label>
                    <div class="controls">
                        <input class="input-large"  type="text" onblur="updateBuildingLocation();" id="building_address1" name="building_address1" value="<?php echo ((isset($PROCESSED["building_address1"])) ? html_encode($PROCESSED["building_address1"]) : ""); ?>" maxlength="255" />
                    </div>
                </div>

                <div class="control-group">
                    <label for="building_address2" class="control-label form-nrequired">
                        <?php echo $BUILDING_TEXT["label_address2"];?>
                    </label>
                    <div class="controls">
                        <input class="input-large"  type="text" id="building_address2" name="building_address2" value="<?php echo ((isset($PROCESSED["building_address2"])) ? html_encode($PROCESSED["building_address2"]) : ""); ?>" maxlength="255" />
                    </div>
                </div>

                <div class="control-group">
                    <label for="building_city" class="control-label form-required">
                        <?php echo $BUILDING_TEXT["label_city"];?>
                    </label>
                    <div class="controls">
                        <input class="input-large"  type="text" id="building_city" name="building_city" value="<?php echo ((isset($PROCESSED["building_city"])) ? html_encode($PROCESSED["building_city"]) : "Los Angeles"); ?>" maxlength="35" />
                    </div>
                </div>

                <div class="control-group">
                    <label for="country_id" class="control-label form-required">
                        <?php echo $BUILDING_TEXT["label_country"];?>
                    </label>
                    <div class="controls">
                        <?php
                        $countries = Models_Country::fetchAllRecords();
                        if ($countries && is_array($countries) && !empty($countries)) {
                            echo "<select class=\"input-large\"  id=\"country_id\" name=\"country_id\">\n";
                            echo "<option value=\"0\">" . $BUILDING_TEXT["label_country3"] . "</option>\n";
                            foreach ($countries as $country) {
                                if ($country && is_object($country)) {
                                    echo "<option value=\"" . (int)$country->getID() . "\"" . (((!isset($PROCESSED["country_id"]) && ($country->getID() == DEFAULT_COUNTRY_ID)) || ($PROCESSED["country_id"] == $country->getID())) ? " selected=\"selected\"" : "") . ">" . html_encode($country->getCountry()) . "</option>\n";
                                }
                            }
                            echo "</select>\n";
                        } else {
                            echo "<input type=\"hidden\" id=\"countries_id\" name=\"countries_id\" value=\"0\" />\n";
                            echo $BUILDING_TEXT["label_country2"];
                        }
                        ?>
                    </div>
                </div>

                <div class="control-group">
                    <label id="prov_state_label" for="prov_state_div" class="control-label">
                        <?php echo $BUILDING_TEXT["label_state"];?>
                    </label>
                    <div class="controls" id="prov_state_div">
                        <?php echo $BUILDING_TEXT["label_state_country"];?>
                    </div>
                </div>

                <div class="control-group">
                    <label for="postcode" class="control-label form-required">
                        <?php echo $BUILDING_TEXT["label_postal_code"];?>
                    </label>
                    <div class="controls">
                        <input class="input-large" placeholder="(<?php echo $BUILDING_TEXT["label_example"];?> <?php echo $BUILDING_TEXT["label_example_postal_code"];?>)" type="text" id="postcode" name="postcode" value="<?php echo ((isset($PROCESSED["building_postcode"])) ? html_encode($PROCESSED["building_postcode"]) : $BUILDING_TEXT["label_example_postal_code"]); ?>" maxlength="7" />
                    </div>
                </div>

                <div class="control-group">
                    <div class="controls">
                        <input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/settings/manage/locations?org=<?php echo $ORGANISATION_ID; ?>'" />
                        <input type="submit" class="btn btn-primary" value="<?php echo $translate->_("global_button_save"); ?>" />
                    </div>
                </div>

            </form>
        </div>

        <div class="span7">

            <!-- Google Map Integration -->
            <div id="mapContainer">
                <div id="mapData" class="img-rounded">
                    <?php echo "<script>updateBuildingLocation('" . $PROCESSED["building_address1"] . "','" . $PROCESSED["building_country"] . "','" . $PROCESSED["building_city"] . "','" . $PROCESSED["building_province"] . "');</script>"; ?>
                </div>
            </div>

        </div>
    </div>

    <!-- Rooms for the buildings -->

    <h2><?php echo $BUILDING_TEXT["building_rooms"];?></h2>

    <?php
    $rooms = Models_Location_Room::fetchAllByBuildingId($PROCESSED["building_id"]);
    ?>

    <div class="row-fluid">
        <form action ="<?php echo ENTRADA_URL;?>/admin/settings/manage/locations?section=delete-room&amp;org=<?php echo $ORGANISATION_ID;?>&amp;building_id=<?php echo $BUILDING_ID;?>" method="post">
            <div class="row-fluid space-below medium">
                <div class="pull-right">
                    <?php
                    if ($rooms) {
                        ?>
                        <button type="submit" class="btn btn-danger space-right"><i class="icon-minus-sign icon-white"></i> <?php echo $ROOM_TEXT["delete_selected"]; ?></button>
                        <?php
                    }
                    ?>
                    <a id="add_new_room" href="<?php echo ENTRADA_URL; ?>/admin/settings/manage/locations?section=add-room&amp;org=<?php echo $ORGANISATION_ID;?>&amp;building_id=<?php echo $PROCESSED["building_id"];?>" class="btn btn-success pull-right"><i class="icon-plus-sign icon-white"></i>  <?php echo $ROOM_TEXT["add_room"];?></a>
                </div>
            </div>
            <?php
            if ($rooms) {
                ?>
                <table class="table table-bordered table-striped" cellspacing="0" cellpadding="1" border="0" summary="<?php echo $ROOM_TEXT["label_list"];?>">
                    <colgroup>
                        <col class="modified"/>
                        <col class="title" />
                        <col class="active" />
                    </colgroup>
                    <thead>
                    <tr>
                        <th class="modified" width="5%">&nbsp;</th>
                        <th class="title" width="10%">
                            <?php echo $ROOM_TEXT["label_room_number"];?>
                        </th>
                        <th class="title" width="70%">
                            <?php echo $ROOM_TEXT["label_room_name"];?>
                        </th>
                        <th class="title" width="15%">
                            <?php echo $ROOM_TEXT["label_room_max_occupancy"];?>
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($rooms as $room) {
                        if ($room && is_object($room)) {
                            $building = $room->getBuilding();
                            $link_url = ENTRADA_URL."/admin/settings/manage/locations?section=edit-room&amp;org=".$ORGANISATION_ID."&amp;room_id=" . $room->getID()."&amp;building_id=" . $room->getBuildingId();
                            echo "<tr>";
                            echo "<td><input type=\"checkbox\" name = \"remove_ids[]\" value=\"" . $room->getID() . "\"/></td>";
                            echo "<td><a href=\"" . $link_url . "\">" . $room->getRoomNumber() . "</a></td>\n";
                            echo "<td><a href=\"" . $link_url . "\">" . $room->getRoomName() . "</a></td>\n";
                            echo "<td>" . $room->getRoomMaxOccupancy() . "</td>\n";
                            echo "</tr>";
                        }
                    }
                    ?>
                    </tbody>
                </table>
                <?php
            } else {
                add_notice($ROOM_TEXT["no_rooms"] . $translate->_("organisation"));
                echo display_notice();
            }  ?>
        </form>
    </div>
    <?php
        break;
    }
}