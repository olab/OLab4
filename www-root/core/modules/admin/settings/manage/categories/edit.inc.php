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
 * This file is used to edit categories in the entrada_clerkship.categories table.
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
} elseif (!$ENTRADA_ACL->amIAllowed("categories", "update", false)) {
    $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/settings/manage/".$MODULE."\\'', 15000)";

    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    if (isset($_GET["id"]) && ($id = clean_input($_GET["id"], array("notags", "trim")))) {
        $CATEGORY_ID = $id;
    }

    if (isset($_GET["mode"]) && $_GET["mode"] == "ajax") {
        $MODE = "ajax";
    }

    if ($CATEGORY_ID) {
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

        $query = "	SELECT * FROM `".CLERKSHIP_DATABASE."`.`categories` AS a
                    JOIN `".CLERKSHIP_DATABASE."`.`category_type` AS b
                    ON a.`category_type` =  b.`ctype_id`
                    WHERE a.`category_id` = ".$db->qstr($CATEGORY_ID)."
                    AND (a.`organisation_id` = ".$db->qstr($ORGANISATION_ID)." OR a.`organisation_id` IS NULL)
                    AND a.`category_status` != 'trash'";
        $category_details = $db->GetRow($query);

        if (isset($MODE) && $MODE == "ajax") {
            ob_clear_open_buffers();
            $time = time();

            if ($category_details["category_parent"] != 0) {

                switch ($STEP) {
                    case "2" :
                        /**
                         * Required field "category_name" / Category Name
                         */
                        if (isset($_POST["category_name"]) && ($category_name = clean_input($_POST["category_name"], array("notags", "trim")))) {
                            $PROCESSED["category_name"] = $category_name;
                        } else {
                            add_error("The <strong>Category Name</strong> is a required field.");
                        }

                        /**
                         * Non-required field "category_code" / Category Code
                         */
                        if (isset($_POST["category_code"]) && ($category_code = clean_input($_POST["category_code"], array("notags", "trim")))) {
                            $PROCESSED["category_code"] = $category_code;
                        } else {
                            $PROCESSED["category_code"] = "";
                        }
                        
                        $category_dates = Entrada_Utilities::validate_calendars("sub_category", true, true, false);
                        if ((isset($category_dates["start"])) && ((int) $category_dates["start"])) {
                            $PROCESSED["category_start"]	= (int) $category_dates["start"];
                        } else {
                            add_error("The <strong>Category Start</strong> field is required.");
                        }
                        
                        if ((isset($category_dates["finish"])) && ((int) $category_dates["finish"])) {
                            $PROCESSED["category_finish"]	= (int) $category_dates["finish"];
                        } else {
                            add_error("The <strong>Category Finish</strong> field is required.");
                        }
                        
                        /**
                         * Required field "category_type" / Category Type.
                         */
                        if (isset($_POST["category_type"]) && ($tmp_input = clean_input($_POST["category_type"], "int")) && array_key_exists($tmp_input, $CATEGORY_TYPES)) {
                            $PROCESSED["category_type"] = $tmp_input;
                        } else {
                            add_error("The <strong>Category Type</strong> field is a required field.");
                        }

                        /**
                        * Non-required field "category_parent" / Category Parent
                        */
                        if (isset($_POST["category_id"]) && ($category_parent = clean_input($_POST["category_id"], array("int")))) {
                            $PROCESSED["category_parent"] = $category_parent;
                        } else {
                            $PROCESSED["category_parent"] = 0;
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

                        if (!has_error()) {
                            if ($category_details["category_order"] != $PROCESSED["category_order"]) {
                                $query = "SELECT `category_id` FROM `".CLERKSHIP_DATABASE."`.`categories`
                                            WHERE `category_parent` = ".$db->qstr($PROCESSED["category_parent"])."
                                            AND (`organisation_id` = ".$db->qstr($ORGANISATION_ID)." OR `organisation_id` IS NULL)
                                            AND `category_id` != ".$db->qstr($CATEGORY_ID)."
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
                                            add_error("There was a problem updating this category in the system. The system administrator was informed of this error; please try again later.");

                                            application_log("error", "There was an error updating an category. Database said: ".$db->ErrorMsg());
                                        }
                                        $count++;
                                    }
                                }
                            }
                        }

                        if (!has_error()) {
                            $PROCESSED["updated_date"] = time();
                            $PROCESSED["updated_by"] = $ENTRADA_USER->getID();

                            if (!$db->AutoExecute("`".CLERKSHIP_DATABASE."`.`categories`", $PROCESSED, "UPDATE", "`category_id` = ".$db->qstr($CATEGORY_ID))) {
                                echo json_encode(array("status" => "error", "msg" => "There was a problem updating this category in the system. The system administrator was informed of this error; please try again later."));

                                application_log("error", "There was an error updating an category. Database said: ".$db->ErrorMsg());
                            } else {
                                $PROCESSED["category_id"] = $CATEGORY_ID;

                                echo json_encode(array("status" => "success", "updates" => $PROCESSED));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "msg" => implode("<br />", $ERRORSTR)));
                        }
                    break;
                    case "1" :
                    default :
                        $PROCESSED = $category_details;
                        ?>
                        <script type="text/javascript">
                            jQuery(function() {
                                selectCategory('#m_selectCategoryField_<?php echo $time; ?>', <?php echo (isset($PROCESSED["category_parent"]) && $PROCESSED["category_parent"] ? $PROCESSED["category_parent"] : "0"); ?>, <?php echo $CATEGORY_ID; ?>, <?php echo $ORGANISATION_ID; ?>);
                                selectOrder('#m_selectOrderField_<?php echo $time; ?>', <?php echo $CATEGORY_ID; ?>, <?php echo (isset($PROCESSED["category_parent"]) && $PROCESSED["category_parent"] ? $PROCESSED["category_parent"] : "0"); ?>, <?php echo $ORGANISATION_ID; ?>);
                            });
                        </script>
                        <h2>Clerkship<?php echo (isset($PROCESSED["ctype_name"]) && $PROCESSED["ctype_name"] ? " ".$PROCESSED["ctype_name"] : ""); ?> Category Details</h2>
                        <div class="row-fluid">
                            <form id="sub-category-form" action="<?php echo ENTRADA_URL."/admin/settings/manage/categories"."?".replace_query(array("action" => "edit", "step" => 2, "mode" => "ajax")); ?>" method="post" class="form-horizontal">
                                <div class="display-error hide"></div>

                                <div class="control-group">
                                    <label for="sub_category_name" class="form-required control-label">Category Name:</label>
                                    <div class="controls">
                                        <input type="text" id="sub_category_name" name="category_name" value="<?php echo ((isset($PROCESSED["category_name"])) ? html_encode($PROCESSED["category_name"]) : ""); ?>" class="span11" />
                                    </div>
                                </div>

                                <div class="control-group">
                                    <label for="sub_category_code" class="form-nrequired control-label">Category Code:</label>
                                    <div class="controls">
                                        <input type="text" id="sub_category_code" name="category_code" value="<?php echo ((isset($PROCESSED["category_code"])) ? html_encode($PROCESSED["category_code"]) : ""); ?>" class="span5" />
                                    </div>
                                </div>

                                <div class="control-group">
                                    <label for="sub_category_type" class="form-nrequired control-label">Category Type:</label>
                                    <div class="controls">
                                        <select id="sub_category_type" name="category_type" value="<?php echo ((isset($PROCESSED["category_type"])) ? html_encode($PROCESSED["category_type"]) : ""); ?>" class="span5" >
                                            <option value="0"<?php echo (!isset($PROCESSED["category_type"]) || $PROCESSED["category_type"] == 0 ? " selected=\"selected\"" : ""); ?>>--- Select a Category Type ---</option>
                                            <?php
                                            foreach ($CATEGORY_TYPES as $type) {
                                                echo "<option value=\"".$type["ctype_id"]."\"".($PROCESSED["category_type"] == $type["ctype_id"] ? " selected=\"selected\"" : "").">".html_encode($type["ctype_name"])."</option>\n";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <!-- Add specific styling for the date input fields. Required because JQueryUI modal dialog styles override Bootstrap -->
                                <style>#sub_category_start_date, #sub_category_finish_date {font-size: 12px;}</style>
                                <?php
                                echo Entrada_Utilities::generate_calendars("sub_category", "Category", true, true, ((isset($PROCESSED["category_start"])) ? $PROCESSED["category_start"] : 0), true, true, ((isset($PROCESSED["category_finish"])) ? $PROCESSED["category_finish"] : 0), false);
                                ?>

                                <div class="control-group">
                                    <label for="sub_category_desc" class="form-nrequired control-label">Category Description: </label>
                                    <div class="controls">
                                        <textarea id="sub_category_desc" name="category_desc" class="span11 expandable"><?php echo ((isset($PROCESSED["category_desc"])) ? html_encode($PROCESSED["category_desc"]) : ""); ?></textarea>
                                    </div>
                                </div>

                                <div class="control-group">
                                    <label for="category_id" class="form-required control-label">Category Parent:</label>
                                    <div class="controls">
                                        <div id="m_selectCategoryField_<?php echo $time; ?>"></div>
                                    </div>
                                </div>

                                <div class="control-group">
                                    <label for="category_id" class="form-required control-label">Category Order:</label>
                                    <div class="controls">
                                        <div id="m_selectOrderField_<?php echo $time; ?>"></div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <?php
                    break;
                }
            }
            exit;
        } else {
            if ($category_details) {
                $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/settings/manage/categories?".replace_query(array("section" => "edit")), "title" => "Edit Category");

                // Error Checking
                switch ($STEP) {
                    case 2:
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
                         * Non-required field "category_parent" / Category Parent
                         */
                        if (isset($_POST["category_id"]) && ($category_parent = clean_input($_POST["category_id"], array("int")))) {
                            $PROCESSED["category_parent"] = $category_parent;
                        } else {
                            $PROCESSED["category_parent"] = 0;
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
                                            WHERE `category_parent` = ".$db->qstr($PROCESSED["category_parent"])."
                                            AND (`organisation_id` = ".$db->qstr($ORGANISATION_ID)." OR `organisation_id` IS NULL)
                                            AND `category_id` != ".$db->qstr($CATEGORY_ID)."
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
                                            add_error("There was a problem updating this category in the system. The system administrator was informed of this error; please try again later.");

                                            application_log("error", "There was an error updating a category. Database said: ".$db->ErrorMsg());
                                        }
                                        $count++;
                                    }
                                }
                            }
                        }

                        if (!has_error()) {
                            $PROCESSED["updated_date"] = time();
                            $PROCESSED["updated_by"] = $ENTRADA_USER->getID();

                            if ($db->AutoExecute("`".CLERKSHIP_DATABASE."`.`categories`", $PROCESSED, "UPDATE", "`category_id` = ".$db->qstr($CATEGORY_ID))) {
                                $url = ENTRADA_URL . "/admin/settings/manage/categories?org=".$ORGANISATION_ID;

                                add_success("You have successfully updated <strong>".html_encode($PROCESSED["category_name"])."</strong> in the system.<br /><br />You will now be redirected to the categories index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.");

                                $ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";

                                application_log("success", "Category [".$CATEGORY_ID."] updated in the system.");
                            } else {
                                add_error("There was a problem updating this category in the system. The system administrator was informed of this error; please try again later.");

                                application_log("error", "There was an error updating an category. Database said: ".$db->ErrorMsg());
                            }
                        }

                        if (has_error()) {
                            $STEP = 1;
                        }
                    break;
                    case 1:
                    default:
                        $PROCESSED = $category_details;
                    break;
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

                        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/elementresizer.js\"></script>\n";
                        $HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/picklist.js\"></script>\n";
                        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/clerkship_categories.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
            
                        $ONLOAD[] = "selectCategory('#selectCategoryField', ".(isset($PROCESSED["category_parent"]) && $PROCESSED["category_parent"] ? $PROCESSED["category_parent"] : "0").", ".$CATEGORY_ID.", ".$ORGANISATION_ID.")";
                        $ONLOAD[] = "selectOrder('#selectOrderField', ".$CATEGORY_ID.", ".(isset($PROCESSED["category_parent"]) && $PROCESSED["category_parent"] ? $PROCESSED["category_parent"] : "0").",".$ORGANISATION_ID.")";
                        ?>
                        <script type="text/javascript">
                            var SITE_URL = "<?php echo ENTRADA_URL;?>";
                            jQuery(function($){
                                $("#category-form").submit(function(){
                                    $("#PickList").each(function(){
                                        $("#PickList option").attr("selected", "selected");
                                    });
                                });
                            });
                        </script>

                        <h1>Edit Clinical Rotation Category</h1>

                        <form id="category-form" action="<?php echo ENTRADA_URL."/admin/settings/manage/categories"."?".replace_query(array("action" => "add", "step" => 2)); ?>" method="post" class="form-horizontal">
                            <input type="hidden" name="category_type" value="<?php echo ((isset($PROCESSED["category_type"])) ? html_encode($PROCESSED["category_type"]) : ""); ?>"/>
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

                            <div class="control-group">
                                <label for="category_id" class="form-required control-label">Category Order</label>
                                <div class="controls">
                                    <div id="selectOrderField"></div>
                                </div>
                            </div>

                            <div class="control-group">
                                <a href="<?php echo ENTRADA_URL."/admin/settings/manage/categories?org=".$ORGANISATION_ID; ?>" class="btn"><?php echo $translate->_("global_button_cancel"); ?></a>
                                <input type="submit" class="btn btn-primary pull-right" value="<?php echo $translate->_("global_button_save"); ?>" />
                            </div>
                        </form>

                        <script type="text/javascript">
                            var SITE_URL = "<?php echo ENTRADA_URL;?>";
                            var EDITABLE = true;
                        </script>

                        <div>
                            <style>
                                .category-title{
                                    cursor:pointer;
                                }
                                .category-list{
                                    padding-left:5px;
                                }
                                #category_list_0{
                                    margin-left:0px;
                                    padding-left: 0px;
                                }
                                .categories{
                                    width:48%;
                                    float:left;
                                }
                                .remove{
                                    display:block;
                                    cursor:pointer;
                                    float:right;
                                }
                                .draggable{
                                    cursor:pointer;
                                }
                                .droppable.hover{
                                    background-color:#ddd;
                                }
                                .category-title{
                                    font-weight:bold;
                                }
                                .category-children{
                                    margin-top:5px;
                                }
                                .category-container{
                                    position:relative;
                                    padding-right:0px!important;
                                    margin-right:0px!important;
                                }
                                .category-controls{
                                    position:absolute;
                                    top:5px;
                                    right:0px;
                                }
                                li.display-notice{
                                    border:1px #FC0 solid!important;
                                    padding-top:10px!important;
                                    text-align:center;
                                }
                                .hide{
                                    display:none;
                                }
                                .category-controls i {
                                    display:block;
                                    width:16px;
                                    height:16px;
                                    cursor:pointer;
                                    float:left;
                                }
                                .category-controls .category-add-control {
                                    background-image:url("<?php echo ENTRADA_URL; ?>/images/add.png");
                                }
                                .category-controls .category-edit-control {
                                    background-image:url("<?php echo ENTRADA_URL; ?>/images/edit_list.png");
                                }
                                .category-controls .category-delete-control {
                                    background-image:url("<?php echo ENTRADA_URL; ?>/images/action-delete.gif");
                                }
                                ul.category-list li {
                                    border:0;
                                    padding:5px;
                                }
                            </style>

                            <h2 title="Sub Categories Section">Available Sub-Categories</h2>

                            <div class="pull-right">
                                <a href="#" class="category-add-control btn btn-success" data-id="<?php echo $CATEGORY_ID; ?>"><i class="icon-plus-sign icon-white"></i> Add Sub-Category</a>
                            </div>

                            <div class="clearfix space-below"></div>

                            <div data-description="" data-id="<?php echo $CATEGORY_ID; ?>" data-title="" id="category_title_<?php echo $CATEGORY_ID; ?>" class="category-title draggable ui-draggable" style="display:none;"></div>

                            <div class="half left" id="children_<?php echo $CATEGORY_ID; ?>">
                                <?php
                                $query = "SELECT * FROM `".CLERKSHIP_DATABASE."`.`categories`
                                            WHERE `category_parent` = ".$db->qstr($CATEGORY_ID)."
                                            AND (`organisation_id` = ".$db->qstr($ORGANISATION_ID)." OR `organisation_id` IS NULL)
                                            AND `category_status` != 'trash'
                                            ORDER BY `category_order`";
                                $categories = $db->GetAll($query);
                                if ($categories) {
                                    ?>
                                    <ul class="category-list" id="category_list_<?php echo $CATEGORY_ID; ?>">
                                        <?php
                                        foreach ($categories as $category) {
                                            ?>
                                            <li class = "category-container" id = "category_<?php echo $category["category_id"]; ?>">
                                                <?php
                                                $title = ($category["category_code"]?$category["category_code"].': '.$category["category_name"]:$category["category_name"]);
                                                ?>
                                                <div class="category-title draggable" id="category_title_<?php echo $category["category_id"]; ?>" data-title="<?php echo $title;?>" data-id = "<?php echo $category["category_id"]; ?>" data-code = "<?php echo $category["category_code"]; ?>" data-name = "<?php echo $category["category_name"]; ?>" data-description = "<?php echo $category["category_desc"]; ?>">
                                                    <?php echo $title; ?>
                                                </div>
                                                <div class="category-controls">
                                                    <i class="category-edit-control" data-id="<?php echo $category["category_id"]; ?>"></i>
                                                    <i class="category-add-control" data-id="<?php echo $category["category_id"]; ?>"></i>
                                                    <i class="category-delete-control" data-id="<?php echo $category["category_id"]; ?>"></i>
                                                </div>
                                                <div class="category-children" id="children_<?php echo $category["category_id"]; ?>">
                                                    <ul class="category-list" id="category_list_<?php echo $category["category_id"]; ?>">
                                                    </ul>
                                                </div>
                                            </li>
                                            <?php
                                        }
                                        ?>
                                    </ul>
                                    <?php
                                } else {
                                    echo display_notice("No Child Categories found. Please click <strong>Add New Category</strong> above to create one.");
                                }
                                ?>
                            </div>
                        </div>
                        <?php
                    default:
                        continue;
                    break;
                }
            } else {
                $url = ENTRADA_URL."/admin/settings/manage/categories?org=".$ORGANISATION_ID;
                $ONLOAD[]	= "setTimeout('window.location=\\'". $url . "\\'', 5000)";

                add_error("In order to update an category, a valid category identifier must be supplied. The provided ID does not exist in the system.  You will be redirected to the System Settings page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.");

                echo display_error();

                application_log("notice", "Failed to provide category identifer when attempting to edit an category.");
            }
        }
    } else {
        $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/settings/manage/".$MODULE."\\'', 15000)";

        add_error("In order to update an category a valid category identifier must be supplied.");

        echo display_error();

        application_log("notice", "Failed to provide category identifer when attempting to edit an category.");
    }
}
