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

    if ((isset($_POST["curriculum_order"])) && ($curriculum_order = clean_input($_POST["curriculum_order"], array("notags", "trim")))) {
        Models_Curriculum_Type::setCurriculumTypeOrderByCurriculumIDArray(explode(',', $curriculum_order));
    }

    ?>
	<h1>Curriculum Layout</h1>

    <div class="row-fluid">
        <span class="pull-right">
            <a class="btn btn-success" href="<?php echo ENTRADA_RELATIVE; ?>/admin/settings/manage/curriculumtypes?section=add&amp;org=<?php echo $ORGANISATION_ID;?>"><i class="icon-plus-sign icon-white"></i> Add Layout</a>
        </span>
    </div>
    <br />
    <?php
	$query = "SELECT a.*
	            FROM `curriculum_lu_types` AS a
				JOIN `curriculum_type_organisation` AS b
				ON a.`curriculum_type_id` = b.`curriculum_type_id` 
				WHERE b.`organisation_id` = ".$db->qstr($ORGANISATION_ID)."
				AND a.`curriculum_type_active` = '1'
				ORDER BY a.`curriculum_type_order` ASC, a.`curriculum_type_name` ASC";
	$results = $db->GetAll($query);
	if ($results) {
        ?>
        <form action ="<?php echo ENTRADA_URL;?>/admin/settings/manage/curriculumtypes?section=delete&amp;org=<?php echo $ORGANISATION_ID;?>" method="post">
            <ul id="selected_curriculumtypes_options">
                <?php
                foreach ($results as $result) {
                    echo "<li id=\"selected_curriculumtypes_options_".$result["curriculum_type_id"]."\">\n";
                    echo "  <input type=\"checkbox\" name = \"remove_ids[]\" value=\"".$result["curriculum_type_id"]."\">\n";
                    echo "  <a href=\"".ENTRADA_RELATIVE."/admin/settings/manage/curriculumtypes?section=edit&amp;org=".$ORGANISATION_ID."&amp;type_id=".$result["curriculum_type_id"]."\">".$result["curriculum_type_name"]."</a>\n";
                    echo "</li>\n";
                }
                ?>
            </ul>
            <input type="submit" class="btn btn-danger" value="Delete Selected" />
            <input type="button" id="save_curriculum_order_button" class="btn btn-primary" onclick="$('curriculum_order_form').submit()" value="Save Ordering" />
        </form>

        <form id="curriculum_order_form" action ="<?php echo ENTRADA_URL;?>/admin/settings/manage/curriculumtypes?org=<?php echo $ORGANISATION_ID;?>" method="post">
            <div id="reorder-info">
                <input id="curriculum_order" name="curriculum_order" style="display: none;" />
                <p class="content-small">Rearrange the curriculum layouts in the table above by dragging them, and then press the <strong>Save Ordering</strong> button.</p>
            </div>
        </form>

        <script>
            jQuery(document).ready(function($){
                Sortable.destroy($('selected_curriculumtypes_options'));
                Sortable.create('selected_curriculumtypes_options', {onUpdate: updateOrder});
            });
            function updateOrder() {
                $('curriculum_order').value = Sortable.sequence('selected_curriculumtypes_options');
            }
        </script> 

        <?php
	} else {
		add_notice("There are currently no Curriculum Layout.");
		echo display_notice();
	}
}

