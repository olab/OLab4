<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Serves the categories list up in a select box.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <jellis@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
*/
@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

if ((isset($_SESSION["isAuthorized"])) && ((bool) $_SESSION["isAuthorized"])) {
    if (isset($_POST["community_type_id"]) && ((int) $_POST["community_type_id"])) {
        $community_type_id = ((int) $_POST["community_type_id"]);
    }
	if (isset($_POST["category_id"]) && ((int) $_POST["category_id"])) {
        $CATEGORY_ID = ((int) $_POST["category_id"]);
    }
    if (isset($_POST["group"]) && (clean_input($_POST["group"], "module"))) {
        $GROUP = clean_input($_POST["group"], "module");
    }
    if (isset($_POST["page_ids"]) && is_array($_POST["page_ids"])) {
        $page_ids = $_POST["page_ids"];
    } else {
		$page_ids = array();
	}
    if (isset($_POST["course_ids"]) && is_array($_POST["course_ids"])) {
        $course_ids = $_POST["course_ids"];
    } else {
		$course_ids = array();
	}
	?>
    <div class="control-group">
        <label class="control-label form-required">Community Template</label>
        <div class="controls">
            <?php
            $query = "SELECT a.* FROM `community_templates` AS a
                        JOIN `community_type_templates` AS b
                        ON a.`template_id` = b.`template_id`
                        WHERE b.`type_id` = ".$db->qstr($community_type_id)."
                        AND b.`type_scope` = 'organisation'";
            $results = $db->GetAll($query);
            if ($results) {
                ?>
                <ul class="community-themes">
                    <?php
                    $default_templates = array();
                    $groups = array();
                    $category = array();
                    $default_categories = array();
                    $default_groups = array();
                    $large_template_images = "";
                    foreach($results as $key => $community_template) {
                        ?>
                        <li id="<?php echo $community_template["template_name"]."-template"; ?>" style="background: url('images/<?php echo $community_template["template_name"]; ?>-thumb.jpg')">
                            <div class="template-rdo">
                                <input type="radio" id="<?php echo "template_option_".$community_template["template_id"] ?>" name="template_selection" value="<?php echo $community_template["template_id"]; ?>"<?php echo (((!isset($template_selection) || $template_selection == 0) && ($key == 0) || (isset($template_selection) && $template_selection == $community_template["template_id"])) ? " checked=\"checked\"" : ""); ?> />
                            </div>
                            <div class="large-view">
                                <a href="#" onclick="show_<?php echo $community_template["template_name"]; ?>_large()" class="<?php echo "large-view-".$community_template["template_id"]; ?>"><img src="<?php echo ENTRADA_URL. "/images/icon-magnify.gif"  ?>" /></a>
                            </div>
                            <label for="<?php echo "template_option_".$community_template["template_id"]; ?>"><?php echo ucfirst($community_template["template_name"]. " Template"); ?></label>
                        </li>
                        <?php
                        $large_template_images .= " <div class=\"".$community_template["template_name"]."-large\" style=\"display:none;\">\n";
                        $large_template_images .= "     <img src=\"".ENTRADA_URL."/images/template-".$community_template["template_name"]."-large.gif\" alt=\"".ucfirst($community_template["template_name"])." Template Screen shot\" />\n";
                        $large_template_images .= " </div>\n";
                    }
                    ?>
                </ul>
                <?php
                echo (isset($large_template_images) && $large_template_images ? $large_template_images : "");
            }
            ?>
        </div>
    </div>
    <div class="clearfix"></div>

    <h3>Community Pages</h3>
    <div class="control-group">
        <label class="control-label form-required">Default Pages</label>
        <div class="controls">
			<?php
                $pages_output = community_type_pages_inlists($community_type_id, 0, 0, array(), $page_ids);
                if ($pages_output != "<ul class=\"community-page-list empty\"></ul>") {
                    echo $pages_output;
                } else {
                    add_notice("No default pages found for this community type.");
                    echo display_notice();
                }
			?>
		</div>
	</div>
	<?php
    $query = "SELECT `community_type_options` FROM `org_community_types`
                WHERE `octype_id` = ".$db->qstr($community_type_id);
    $type_options_serialized = $db->GetOne($query);
    if ($type_options_serialized && ($type_options = json_decode($type_options_serialized)) && @count($type_options)) {
        foreach ($type_options as $type_option => $active) {
            if ($type_option == "course_website" && $active && $ENTRADA_ACL->amIAllowed("course", "create", false)) {
                ?>
                <h3>Community Courses</h3>
                <div class="control-group">
                    <label class="control-label form-required">Select course(s)</label>
                    <div class="controls">
                        <?php
                            $query = "SELECT `course_id`, `course_code`, `course_name` FROM `courses` 
                                        WHERE `course_active` = 1
                                        AND `organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
                                        AND `course_id` NOT IN (
                                            SELECT `course_id` FROM `community_courses`
                                        )";
                            $courses = $db->GetAll($query);
                            if ($courses) {
                                echo "<select multiple=\"multiple\" name=\"course_ids[]\" id=\"course_ids\" class=\"chosen-select\">";
                                foreach ($courses as $course) {
                                    if ($ENTRADA_ACL->amIAllowed(new CourseContentResource($course["course_id"], $ENTRADA_USER->getActiveOrganisation()), 'update')) {
                                        echo "<option value=\"".((int)$course["course_id"])."\"".(in_array($course["course_id"], $course_ids) ? " selected=\"selected\"" : "").">".html_encode(($course["course_code"] ? $course["course_code"]." - " : "").$course["course_name"])."</option>";
                                    }
                                }
                                echo "</select>";
                            }
                        ?>
                    </div>
                </div>
                <?php
            }
        }
    }
}
