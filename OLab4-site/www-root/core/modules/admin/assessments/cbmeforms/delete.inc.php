<?php
/**
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
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "/" . $SECTION, "title" => "Delete Items");
    echo "<h1>".$translate->_("Delete Forms")."</h1>";

    if (isset($_POST["forms"]) && is_array($_POST["forms"])) {
        $forms = array();
        foreach ($_POST["forms"] as $form_id) {
            $tmp_input = clean_input($form_id, "int");
            if ($tmp_input) {
                $form = Models_Assessments_Form::fetchRowByID($form_id);
                if ($form) {
                    $forms[] = $form;
                }
            }
        }
    }

    $url = ENTRADA_URL."/admin/".$MODULE."/".$SUBMODULE;

    switch ($STEP) {
        case 2 :
            if (isset($forms) && is_array($forms)) {
                foreach ($forms as $form) {
                    if (!$form->fromArray(array("deleted_date" => time(), "updated_date" => time(), "updated_by" => $ENTRADA_USER->getActiveID()))->update()) {
                        $ERROR++;
                    }
                }

                if (!$ERROR) {
                    echo add_success($SUBMODULE_TEXT["index"]["delete_success"] . sprintf($translate->_(" You will be redirected to the Forms index in 5 seconds or <a href=\"%s\">click here</a> if you do not wish to wait."), $url));
                } else {
                    $STEP = 1;
                    echo add_error($SUBMODULE_TEXT["index"]["delete_error"]);
                    unset($forms);
                }
            } else {
                add_error(sprintf($translate->_("No forms selected for deletion. You will be redirected to the Forms index in 5 seconds or <a href=\"%s\">click here</a> if you do not wish to wait."), $url));
            }
            break;
    }

    switch ($STEP) {
        case 2 :
            if ($ERROR) {
                echo display_error();
            }
            if ($SUCCESS) {
                echo display_success();
            }
            $ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
            break;
        case 1 :
            if (!$forms) {
                $url =  ENTRADA_URL."/admin/".$MODULE."/".$SUBMODULE;;
                add_error(sprintf($translate->_("No Forms were selected for deletion. You will be redirected to the Forms index in 5 seconds or <a href=\"%s\">click here</a> if you do not wish to wait."), $url));
                $ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
            }
        default :
            if ($ERROR) {
                echo display_error();
            }
            if ($SUCCESS) {
                echo display_success();
            }
            if ($forms && !empty($forms)) {
                echo display_notice($translate->_("Please confirm below that these are the Items you wish to delete."));
            ?>
                <form action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?step=2&section=delete"; ?>" method="POST">
                    <table class="table table-bordered table-striped" id="forms-table">
                        <thead>
                        <tr>
                            <th width="4%"></th>
                            <th width="71%"><?php echo $SUBMODULE_TEXT["index"]["title_heading"]; ?></th>
                            <th width="15%"><?php echo $SUBMODULE_TEXT["index"]["created_heading"]; ?></th>
                            <th width="10%"><?php echo $SUBMODULE_TEXT["index"]["items_heading"]; ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                            foreach ($forms as $form) {
                        ?>
                                <tr>
                                    <td><input type="checkbox" name="forms[]" checked="checked" value="<?php echo $form->getFormID(); ?>" /></td>
                                    <td><?php echo $form->getTitle(); ?></td>
                                    <td><?php echo ($form->getCreatedDate() && !is_null($form->getCreatedDate()) ? date("Y-m-d", $form->getCreatedDate()) : $translate->_("N/A")); ?></td>
                                    <td><?php echo $form->fetchFormElementCount(); ?></td>
                                </tr>
                        <?php
                            }
                        ?>
                        </tbody>
                    </table>
                    <div class="row-fluid space-below">
                        <a href="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE; ?>" class="btn btn-default"><?php echo $translate->_("Back"); ?></a>
                        <?php if ($forms) { ?><input type="submit" class="btn btn-danger pull-right" value="<?php echo $translate->_("Delete"); ?>"/><?php } ?>
                    </div>
                </form>
            <?php
            }
    }

}