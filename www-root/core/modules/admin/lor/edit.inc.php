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
 * Loads the file to add/edit a learning object.
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
    define("EDIT_LOR", true);

    if (isset($_GET["learning_object_id"]) && $tmp_input = clean_input($_GET["learning_object_id"], "int")) {
        $PROCESSED["learning_object_id"] = $tmp_input;
    }

    $learning_object = Models_LearningObject::fetchRowByID($PROCESSED["learning_object_id"]);

    if ($learning_object) {
        $BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/" . $MODULE . "/" . $SECTION, "title" => $learning_object->getTitle());

        $PROCESSED = $learning_object->toArray();

        $METHOD = "update";
        ?>
        <h1><?php echo "Editing " . $translate->_("Learning Object") . ":"; ?></h1>
        <?php

        $authors = Models_LearningObject_Author::fetchAllByLearningResourceID($PROCESSED["learning_object_id"]);
        if ($authors) {
            foreach ($authors as $author) {
                $PROCESSED["authors"][] = array("author_type" => $author->getAuthorType(), "author_id" => $author->getAuthorID());
            }
        } else { ?>
            <div class="alert alert-warning">
                <ul>
                    <li><?php echo $translate->_("There are currently no <strong>Authors</strong> for this Learning Object. Please make sure to add one.") ?></li>
                </ul>
            </div>
            <?php
        }

        require_once("lor.inc.php");
    } else {
        // todo check this. is h1 necessary etc
        $BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/" . $MODULE . "/" . $SECTION, "title" => "Editing " . $translate->_("Learning Object") . ":");
        ?>
        <h1><?php echo "Editing " . $translate->_("Learning Object") . ":"; ?></h1>
        <?php
        echo display_error($translate->_("Learning Object") . " not found");
    }
}