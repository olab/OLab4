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
 * @author Unit: EdTech Unit
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
*/

if (!defined("PARENT_INCLUDED")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
	add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "/" . $SECTION, "title" => $translate->_("Delete Items"));
    echo "<h1>".$translate->_("Delete Items")."</h1>";
    
    $items = array();
    if (isset($_POST["items"])) {
        foreach ($_POST["items"] as $tmp_item_id) {
            if ($tmp_input = clean_input($tmp_item_id, "int")) {
                $items[] = Models_Assessments_Item::fetchRowByID($tmp_input);
            }
        }
    }
    
    switch ($STEP) {
        case 2 :
            $url = ENTRADA_URL."/admin/".$MODULE."/".$SUBMODULE;
            if ($items) {
                $count = 0;
                foreach ($items as $item) {
                    if (!$item->fromArray(array("deleted_date" => time()))->update()) {
                        $ERROR++;
                    } else {
                        $count++;
                    }
                }
                if ($ERROR) {
                    add_error($translate->_("Unable to delete Item."));
                } else {
                    add_success(sprintf($translate->_("Successfully deleted <strong>%d</strong> Item(s), you will be redirected to the Item Bank index in 5 seconds or <a href=\"%s\">click here</a> if you do not wish to wait."), $count, $url));
                }
            } else {
                add_error($translate->_("No Items were selected for deletion."));
            }
        break;
        case 1 :
        break;
    }
    
    switch ($STEP) {
        case 2 : 
            $url = ENTRADA_URL."/admin/".$MODULE."/".$SUBMODULE;
            if ($SUCCESS) {
                echo display_success();
            }
            if ($ERROR) {
                echo display_error();
            }
            $ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
        break;
        default :
        case 1 :
            if (!$items) {
                $url =  ENTRADA_URL."/admin/".$MODULE."/".$SUBMODULE;;
                add_error(sprintf($translate->_("No Items were selected for deletion. You will be redirected to the Items index in 5 seconds or <a href=\"%s\">click here</a> if you do not wish to wait."), $url));
                $ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
            }

            if ($SUCCESS) {
                echo display_success();
            }
            if ($ERROR) {
                echo display_error();
            }
            
            if ($items) {
                echo display_notice($translate->_("Please confirm below that these are the Items you wish to delete.")); ?>
                <form action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?step=2&section=delete"; ?>" method="POST">
                    <table class="table table-bordered table-striped">
                            <thead>
                                <th width="5%"></th>
                                <th width="40%"><?php echo $translate->_("Item Code"); ?></th>
                                <th width="48%"><?php echo $translate->_("Item Type"); ?></th>
                                <th width="7%"><i class="icon-th-list"></i></th>
                            </thead>
                            <tbody>
                            <?php 
                            foreach ($items as $item) {
                                $item_type = $item->getItemType(); 
                                $item_responses =  $item->getItemResponses(); ?>
                                <tr>
                                    <td><input type="checkbox" name="items[]" checked="checked" value="<?php echo $item->getID(); ?>" /></td>
                                    <td><?php echo ($item->getItemCode() ? $item->getItemCode() : $translate->_("N/A")); ?></td>
                                    <td><?php echo ($item_type ? $item_type->getName() : $translate->_("N/A")); ?></td>
                                    <td><?php echo ($item_responses ? count($item_responses) : "0"); ?></td>
                                </tr>
                            <?php 
                            }
                            ?>
                            </tbody>
                            </tbody>
                        </table>
                    <div class="row-fluid space-below">
                        <a href="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE; ?>" class="btn btn-default"><?php echo $translate->_("Back"); ?></a>
                        <?php if ($items) { ?><input type="submit" class="btn btn-danger pull-right" value="<?php echo $translate->_("Delete"); ?>"/><?php } ?>
                    </div>
                </form>
            <?php
            }
        break;
    }
    ?>
<?php
    
}
