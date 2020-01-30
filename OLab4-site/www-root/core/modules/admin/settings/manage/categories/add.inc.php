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
 * This file is used to add categories in the entrada_clerkship.categories table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer:James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if (!defined("PARENT_INCLUDED") || !defined("IN_CATEGORIES")) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("categories", "create", false)) {
    $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/settings/manage/".$MODULE."\\'', 15000)";

    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    if (isset($_GET["mode"]) && $_GET["mode"] == "ajax") {
        $MODE = "ajax";
    }

    if (isset($_GET["parent_id"]) && ($id = clean_input($_GET["parent_id"], array("notags", "trim")))) {
        $PARENT_ID = $id;
    }
    
    /**
     * Fetch a list of available evaluation targets that can be used as Form Types.
     */
    $query = "SELECT * FROM `".CLERKSHIP_DATABASE."`.`category_type` ORDER BY `ctype_parent`, `ctype_id`";
    $results = $db->GetAll($query);
    if ($results) {
        foreach ($results as $result) {
            $CATEGORY_TYPES[$result["ctype_id"]] = $result;
        }
    }

    if (isset($MODE) && $MODE == "ajax" && isset($PARENT_ID) && $PARENT_ID) {
        ob_clear_open_buffers();
        $time = time();

        switch ($STEP) {
            case "2" :
                /**
                 * Required field "category_name" / Category Name
                 */
                if (isset($_POST["category_name"]) && ($category_name = clean_input($_POST["category_name"], array("notags", "trim")))) {
                    $PROCESSED["category_name"] = $category_name;
                } else {
                    $ERROR++;
                    $ERRORSTR[] = "The <strong>Category Name</strong> is a required field.";
                }

                /**
                 * Non-required field "category_code" / Category Code
                 */
                if (isset($_POST["category_code"]) && ($category_code = clean_input($_POST["category_code"], array("notags", "trim")))) {
                    $PROCESSED["category_code"] = $category_code;
                } else {
                    $PROCESSED["category_code"] = "";
                }

                $category_dates = Entrada_Utilities::validate_calendars("sub_category", true, false, false);
                if ((isset($category_dates["start"])) && ((int) $category_dates["start"])) {
                    $PROCESSED["category_start"]	= (int) $category_dates["start"];
                } else {
                    $ERROR++;
                    $ERRORSTR[] = "The <strong>Category Start</strong> field is required.";
                }

                if ((isset($category_dates["finish"])) && ((int) $category_dates["finish"])) {
                    $PROCESSED["category_finish"]	= (int) $category_dates["finish"];
                } else {
                    $ERROR++;
                    $ERRORSTR[] = "The <strong>Category Finish</strong> field is required.";
                }

                /**
                 * Required field "category_type" / Category Type.
                 */
                if (isset($_POST["category_type"]) && ($tmp_input = clean_input($_POST["category_type"], "int")) && array_key_exists($tmp_input, $CATEGORY_TYPES)) {
                    $PROCESSED["category_type"] = $tmp_input;
                } else {
                    $ERROR++;
                    $ERRORSTR[] = "The <strong>Category Type</strong> field is a required field.";
                }

                /**
                 * Non-required field "category_desc" / Category Description
                 */
                if (isset($_POST["category_desc"]) && ($category_desc = clean_input($_POST["category_desc"], array("notags", "trim")))) {
                    $PROCESSED["category_desc"] = $category_desc;
                } else {
                    $PROCESSED["category_desc"] = "";
                }

                /**
                 * Required field "category_order" / Category Order
                 */
                if (isset($_POST["category_order"]) && ($category_order = clean_input($_POST["category_order"], array("int"))) && $category_order != "-1") {
                    $PROCESSED["category_order"] = clean_input($_POST["category_order"], array("int")) - 1;
                } else if($category_order == "-1") {
                    $PROCESSED["category_order"] = $category_details["category_order"];
                } else {
                    $PROCESSED["category_order"] = 0;
                }

                if (!$ERROR) {
                    $query = "SELECT MAX(`category_order`) FROM `".CLERKSHIP_DATABASE."`.`categories`
                                WHERE `category_parent` = ".$db->qstr($PARENT_ID)."
                                AND `category_status` != 'trash'
                                AND (`organisation_id` = ".$db->qstr($ORGANISATION_ID)." OR `organisation_id` IS NULL)";
                    $count = $db->GetOne($query);
                    if (($count + 1) != $PROCESSED["category_order"]) {
                        $query = "SELECT `category_id` FROM `".CLERKSHIP_DATABASE."`.`categories`
                                    WHERE `category_parent` = ".$db->qstr($PARENT_ID)."
                                    AND (`organisation_id` = ".$db->qstr($ORGANISATION_ID)." OR `organisation_id` IS NULL)
                                    AND `category_status` != 'trash'
                                    ORDER BY `category_order` ASC";
                        $categories = $db->GetAll($query);
                        if ($categories) {
                            $count = 0;
                            foreach ($categories as $category) {
                                if($count === $PROCESSED["category_order"]) {
                                    $count++;
                                }
                                if (!$db->AutoExecute("`".CLERKSHIP_DATABASE."`.`categories`", array("category_order" => $count), "UPDATE", "`category_id` = ".$db->qstr($category["category_id"]))) {
                                    $ERROR++;
                                    $ERRORSTR[] = "There was a problem updating this category in the system. The system administrator was informed of this error; please try again later.";

                                    application_log("error", "There was an error updating an category. Database said: ".$db->ErrorMsg());
                                }
                                $count++;
                            }
                        }
                    }
                }

                if (!$ERROR) {
                    $PROCESSED["category_parent"] = $PARENT_ID;
                    $PROCESSED["organisation_id"] = $ORGANISATION_ID;
                    $PROCESSED["updated_date"] = time();
                    $PROCESSED["updated_by"] = $ENTRADA_USER->getID();

                    if (!$db->AutoExecute("`".CLERKSHIP_DATABASE."`.`categories`", $PROCESSED, "INSERT") || !($category_id = $db->Insert_Id())) {

                        echo json_encode(array("status" => "error", "msg" => "There was a problem updating this category in the system. The system administrator was informed of this error; please try again later."));

                        application_log("error", "There was an error updating an category. Database said: ".$db->ErrorMsg());
                    } else {
                        $PROCESSED["category_id"] = $category_id;
                        echo json_encode(array("status" => "success", "updates" => $PROCESSED));
                    }
                } else {
                    echo json_encode(array("status" => "error", "msg" => implode("<br />", $ERRORSTR)));
                }
            break;
            case "1" :
            default :
                ?>
                <script type="text/javascript">
                    jQuery(function(){
                        selectCategory('#m_selectCategoryField_<?php echo $time; ?>', <?php echo (isset($PARENT_ID) && $PARENT_ID ? $PARENT_ID : "0"); ?>, 0, <?php echo $ORGANISATION_ID; ?>);
                        selectOrder('#m_selectOrderField_<?php echo $time; ?>', 0, <?php echo (isset($PARENT_ID) && $PARENT_ID ? $PARENT_ID : "0"); ?>, <?php echo $ORGANISATION_ID; ?>);
                    });
                </script>
                <div class="row-fluid">
                    <form id="sub-category-form" action="<?php echo ENTRADA_URL."/admin/settings/manage/categories"."?".replace_query(array("action" => "add", "step" => 2, "mode" => "ajax")); ?>" method="post" class="form-horizontal">
                        <div class="display-error hide"></div>

                        <div class="control-group">
                            <label for="sub_category_type" class="form-required control-label">Category Type</label>
                            <div class="controls">
                                <select id="sub_category_type" name="category_type" value="<?php echo ((isset($PROCESSED["category_type"])) ? html_encode($PROCESSED["category_type"]) : ""); ?>" class="span5">
                                    <?php
                                    foreach ($CATEGORY_TYPES as $type) {
                                        echo "<option value=\"".$type["ctype_id"]."\"".($PROCESSED["category_type"] == $type["ctype_id"] ? " selected=\"selected\"" : "").">".html_encode($type["ctype_name"])."</option>\n";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="control-group">
                            <label for="sub_category_code" class="form-nrequired control-label">Category Code</label>
                            <div class="controls">
                                <input type="text" id="sub_category_code" name="category_code" value="<?php echo ((isset($PROCESSED["category_code"])) ? html_encode($PROCESSED["category_code"]) : ""); ?>" class="span5" />
                            </div>
                        </div>

                        <div class="control-group">
                            <label for="sub_category_name" class="form-required control-label">Category Name</label>
                            <div class="controls">
                                <input type="text" id="sub_category_name" name="category_name" value="<?php echo ((isset($PROCESSED["category_name"])) ? html_encode($PROCESSED["category_name"]) : ""); ?>" class="span11" />
                            </div>
                        </div>

                        <div class="control-group">
                            <label for="sub_category_desc" class="form-nrequired control-label">Category Description</label>
                            <div class="controls">
                                <textarea id="sub_category_desc" name="category_desc" class="span11 expandable"><?php echo ((isset($PROCESSED["category_desc"])) ? html_encode($PROCESSED["category_desc"]) : ""); ?></textarea>
                            </div>
                        </div>
                        <!-- Add specific styling for the date input fields. Required because JQueryUI modal dialog styles override Bootstrap -->
                        <style>#sub_category_start_date, #sub_category_finish_date {font-size: 12px;}</style>
                        <?php echo Entrada_Utilities::generate_calendars("sub_category", "Category", true, true, ((isset($PROCESSED["category_start"])) ? $PROCESSED["category_start"] : 0), true, true, ((isset($PROCESSED["category_finish"])) ? $PROCESSED["category_finish"] : 0), false); ?>

                        <div class="control-group">
                            <label for="category_id" class="form-required control-label">Category Order</label>
                            <div class="controls">
                                <div id="m_selectOrderField_<?php echo $time; ?>"></div>
                            </div>
                        </div>
                    </form>
                </div>
                <?php
            break;
        }
        exit;
    } else {
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/settings/manage/categories?".replace_query(array("section" => "add")), "title" => "Add Category");

        // Error Checking
        if ($STEP == 2) {
            /**
             * Required field "category_name" / Category Name
             */
            if (isset($_POST["category_name"]) && ($category_name = clean_input($_POST["category_name"], array("notags", "trim")))) {
                $PROCESSED["category_name"] = $category_name;
            } else {
                add_error("The <strong>Category".(isset($category_details["ctype_name"]) && $category_details["ctype_name"] ? " ".$category_details["ctype_name"] : "")." Name</strong> is a required field.");
            }

            /**
             * Non-required field "category_code" / Category Code
             */
            if (isset($_POST["category_code"]) && ($category_code = clean_input($_POST["category_code"], array("notags", "trim")))) {
                $PROCESSED["category_code"] = $category_code;
            } else {
                $PROCESSED["category_code"] = "";
            }

            /**
             * Required field "category_type" / Category Type.
             */
            if (isset($_POST["category_type"]) && ($tmp_input = clean_input($_POST["category_type"], "int")) && array_key_exists($tmp_input, $CATEGORY_TYPES)) {
                $PROCESSED["category_type"] = $tmp_input;
            } else {
                add_error("The <strong>Category Type</strong> field is required.");
            }

            /**
             * Required field "category_order" / Category Order
             */
            if (isset($_POST["category_order"]) && ($category_order = clean_input($_POST["category_order"], array("int"))) && $category_order != "-1") {
                $PROCESSED["category_order"] = clean_input($_POST["category_order"], array("int")) - 1;
            } else if($category_order == "-1") {
                $PROCESSED["category_order"] = $category_details["category_order"];
            } else {
                $PROCESSED["category_order"] = 0;
            }

            /**
             * Non-required field "category_desc" / Category Description
             */
            if (isset($_POST["category_desc"]) && ($category_desc = clean_input($_POST["category_desc"], array("notags", "trim")))) {
                $PROCESSED["category_desc"] = $category_desc;
            } else {
                $PROCESSED["category_desc"] = "";
            }

            if (!has_error()) {
                if ($category_details["category_order"] != $PROCESSED["category_order"]) {
                    $query = "SELECT `category_id` FROM `".CLERKSHIP_DATABASE."`.`categories`
                                WHERE `category_parent` = ".$db->qstr($PARENT_ID)."
                                AND (`organisation_id` = ".$db->qstr($ORGANISATION_ID)." OR `organisation_id` IS NULL)
                                AND `category_status` != 'trash'
                                ORDER BY `category_order` ASC";
                    $categories = $db->GetAll($query);
                    if ($categories) {
                        $count = 0;
                        foreach ($categories as $category) {
                            if ($count === $PROCESSED["category_order"]) {
                                $count++;
                            }
                            if (!$db->AutoExecute("`".CLERKSHIP_DATABASE."`.`categories`", array("category_order" => $count), "UPDATE", "`category_id` = ".$db->qstr($category["category_id"]))) {
                                add_error("There was a problem updating this category in the system. The system administrator was informed of this error; please try again later.");

                                application_log("error", "There was an error updating a category. Database said: ".$db->ErrorMsg());
                            }
                            $count++;
                        }
                    }
                }
            }

            if (!$ERROR) {
                $PROCESSED["category_parent"] = 0;
                $PROCESSED["organisation_id"] = $ORGANISATION_ID;
                $PROCESSED["updated_date"] = time();
                $PROCESSED["updated_by"] = $ENTRADA_USER->getID();

                if ($db->AutoExecute("`".CLERKSHIP_DATABASE."`.`categories`", $PROCESSED, "INSERT") || !($category_id = $db->Insert_Id())) {
                    if (!$ERROR) {
                        $url = ENTRADA_URL . "/admin/settings/manage/categories?org=".$ORGANISATION_ID;

                        add_success("You have successfully added <strong>".html_encode($PROCESSED["category_name"])."</strong> to the system.<br /><br />You will now be redirected to the categories index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.");

                        $ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";

                        application_log("success", "New Category [".$category_id."] added to the system.");		
                    }
                } else {
                    add_error("There was a problem updating this category in the system. The system administrator was informed of this error; please try again later.");

                    application_log("error", "There was an error updating an category. Database said: ".$db->ErrorMsg());
                }
            }

            if (has_error()) {
                $STEP = 1;
            }
        }

        //Display Content
        switch ($STEP) {
            case 2:
                if (has_success()) {
                    echo display_success();
                }

                if (has_notice()) {
                    echo display_notice();
                }

                if (has_error()) {
                    echo display_error();
                }
            break;
            case 1:
                if (has_error()) {
                    echo display_error();
                }
                $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/clerkship_categories.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
                $ONLOAD[] = "selectOrder('#selectOrderField', 0, ".(isset($PARENT_ID) && $PARENT_ID ? $PARENT_ID : "0").", ".$ORGANISATION_ID.")";
                ?>
                <script type="text/javascript">
                    var SITE_URL = "<?php echo ENTRADA_URL;?>";
                </script>
                <h1>Add Clinical Rotation Category</h1>

                <form id="category-form" action="<?php echo ENTRADA_URL."/admin/settings/manage/categories"."?".replace_query(array("action" => "add", "step" => 2)); ?>" method="post" class="form-horizontal">
                    <div class="control-group">
                        <label for="category_code" class="form-nrequired control-label">Category Code</label>
                        <div class="controls">
                            <input type="text" id="category_code" name="category_code" value="<?php echo ((isset($PROCESSED["category_code"])) ? html_encode($PROCESSED["category_code"]) : ""); ?>" class="span5" />
                        </div>
                    </div>

                    <div class="control-group">
                        <label for="category_name" class="form-required control-label">Category Name</label>
                        <div class="controls">
                            <input type="text" id="category_name" name="category_name" value="<?php echo ((isset($PROCESSED["category_name"])) ? html_encode($PROCESSED["category_name"]) : ""); ?>" class="span11" />
                        </div>
                    </div>

                    <div class="control-group">
                        <label for="category_desc" class="form-nrequired control-label">Category Description</label>
                        <div class="controls">
                            <textarea id="category_desc" name="category_desc" class="span11 expandable"><?php echo ((isset($PROCESSED["category_desc"])) ? html_encode($PROCESSED["category_desc"]) : ""); ?></textarea>
                        </div>
                    </div>

                    <input type="hidden" value="1" name="category_type" />

                    <div class="control-group">
                        <label for="category_id" class="form-required control-label">Category Order</label>
                        <div class="controls">
                            <div id="selectOrderField"></div>
                        </div>
                    </div>

                    <div class="control-group">
                        <a href="<?php echo ENTRADA_URL; ?>/admin/settings/manage/categories?org=<?php echo $ORGANISATION_ID; ?>" class="btn"><?php echo $translate->_("global_button_cancel"); ?></a>
                        <input type="submit" class="btn btn-primary pull-right" value="<?php echo $translate->_("global_button_save"); ?>" />
                    </div>
                </form>
                <?php
            default:
                continue;
            break;
        }
    }
}
