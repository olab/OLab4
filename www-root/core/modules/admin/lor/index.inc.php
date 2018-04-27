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
 * The default file that is loaded when /admin/lor is accessed.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Travis Obregon <travismobregon@gmail.com>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 *
 */

if (!defined("PARENT_INCLUDED") || !defined("IN_ADMIN_LOR")) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !$_SESSION["isAuthorized"]) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("lor", "update", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] does not have access to this module [" . $MODULE . "]");
} else {
    $HEAD[] = "<script type=\"text/javascript\" >var ENTRADA_URL = '" . ENTRADA_URL . "';</script>";
    $HEAD[] = "<script type=\"text/javascript\" >var IN_ADMIN = true;</script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/" . $MODULE . ".js\"></script>\n";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\"  href=\"" . ENTRADA_RELATIVE . "/css/" . $MODULE . ".css\">\n";

    $learning_object = new Models_LearningObject();
    $active_learning_objects = $learning_object->fetchActiveResources();
    ?>
    <h1><?php echo $translate->_("Learning Objects"); ?></h1>
    <div id="learning-objects-container">
        <form id="learning-object-search-form" method="post" action="<?php echo ENTRADA_URL . "/admin/" . $MODULE; ?>">
            <div class="row-fluid space-below">
                <div class="pull-left">
                    <div class="space-right">
                        <input type="text" id="learning-object-search" class="input-large search-icon"
                               name="learning-object-search"
                               placeholder="Search <?php echo $translate->_("Learning Objects"); ?>..." autofocus/>
                    </div>
                </div>
                <div class="pull-right">
                    <a href="#delete-learning-object-modal" data-toggle="modal" class="btn btn-danger space-right"><i
                                class="icon-minus-sign icon-white"></i> <?php echo "Delete " . $translate->_("Learning Objects"); ?>
                    </a>
                    <a href="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=add" ?>"
                       class="btn btn-success"><i
                                class="icon-plus-sign icon-white"></i> <?php echo "Add New " . $translate->_("Learning Object"); ?>
                    </a>
                </div>
            </div>
            <div id="learning-object-msgs">
                <div id="learning-objects-loading" class="hide">
                    <p><?php echo "Loading " . $translate->_("Learning Objects") . "..."; ?></p>
                    <img src="<?php echo ENTRADA_URL . "/images/loading.gif" ?>"/>
                </div>
            </div>
        </form>
        <div id="learning-object-table-container">
            <table id="learning-objects-table" class="table table-striped table-bordered">
                <thead>
                <tr>
                    <th width="5%"></th>
                    <th width="45%"><?php echo $translate->_("Title"); ?></th>
                    <th width="30%"><?php echo $translate->_("Author(s)"); ?></th>
                    <th width="15%"><?php echo $translate->_("Updated Date"); ?></th>
                    <th width="5%"></th>
                </tr>
                </thead>
                <tbody>
                <?php
                if ($active_learning_objects) {
                    foreach ($active_learning_objects as $learning_object) {
                        $url = ENTRADA_URL . "/admin/" . $MODULE . "?section=edit&learning_object_id=" . $learning_object->getID();
                        $authors = $learning_object->getAuthors();
                        ?>
                        <tr class="data-total">
                            <td>
                                <input type="checkbox" name="learning_objects[]"
                                       value="<?php echo $learning_object->getID(); ?>"/>
                            </td>
                            <td>
                                <a id="learning_object_title_link_<?php echo $learning_object->getID(); ?>"
                                   href="<?php echo $url; ?>"><?php echo html_encode($learning_object->getTitle()); ?></a>
                            </td>
                            <td>
                                <a href="<?php echo $url; ?>"><?php echo($authors ? $authors : "N/A"); ?></a>
                            </td>
                            <td>
                                <a href="<?php echo $url; ?>">
                                    <?php echo html_encode(is_null($learning_object->getUpdatedDate()) ? "N/A" : date(DEFAULT_DATE_FORMAT, $learning_object->getUpdatedDate())); ?>
                                </a>
                            </td>
                            <td>
                                <a href="<?php echo ENTRADA_RELATIVE; ?>/object?id=<?php echo $learning_object->getID(); ?>"
                                   target="_blank"><i class="fa fa-play"></i></a>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    ?>
                    <tr>
                        <td colspan="5">
                            <?php echo $translate->_("There are no Learning Objects to display at this time."); ?>
                        </td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
        </div>

        <?php
        $total_learning_objects = (int)$learning_object->countAllResources();
        if ($total_learning_objects <= 50) { ?>
            <a id="load-learning-objects"
               class="btn btn-block load-learning-objects-disabled"><?php echo sprintf("Showing %s of %s " . $translate->_("Learning Objects"), ($active_learning_objects ? count($active_learning_objects) : "0"), ($active_learning_objects ? count($active_learning_objects) : "0")); ?></a>
        <?php
        if ($total_learning_objects == 0) { ?>
            <script type="text/javascript">
                jQuery("#load-learning-objects").addClass("hide");
            </script>
        <?php
        }
        } else { ?>
            <a id="load-learning-objects"
               class="btn btn-block"><?php echo sprintf("Showing %s of %s " . $translate->_("Learning Objects"), count($active_learning_objects), $total_learning_objects); ?></a>
            <?php
        }
        ?>
        <div id="delete-learning-object-modal" class="modal hide fade">
            <form id="delete-learning-object-modal-form" class="form-horizontal"
                  action="<?php echo ENTRADA_URL . "/admin/" . $MODULE; ?> ?section=api-lor" method="POST"
                  style="margin:0px;">
                <input type="hidden" name="step" value="2"/>
                <div class="modal-header">
                    <h1><?php echo "Delete " . $translate->_("Learning Objects"); ?></h1>
                </div>
                <div class="modal-body">
                    <div id="no-learning-objects-selected" class="hide">
                        <p><?php echo "No " . $translate->_("Learning Objects") . " selected to delete." ?></p>
                    </div>
                    <div id="learning-objects-selected" class="hide">
                        <p><?php echo "Please confirm you would like to delete the selected Learning Object(s)?" ?></p>
                        <div id="delete-learning-objects-container"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo "Cancel"; ?></a>
                        <input id="delete-learning-objects-modal-delete" type="submit" class="btn btn-danger"
                               value="<?php echo "Delete"; ?>"/>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php
}