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
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "/" . $SECTION, "title" => $translate->_("Delete Distributions"));
    echo "<h1>".$translate->_("Delete Distributions")."</h1>";

    if (isset($_POST["distributions"]) && is_array($_POST["distributions"])) {
        $distributions = array();
        foreach ($_POST["distributions"] as $distribution_id) {
            $tmp_input = clean_input($distribution_id, "int");
            if ($tmp_input) {
                $distribution = Models_Assessments_Distribution::fetchRowByID($tmp_input);
                if ($distribution) {
                    $distributions[] = $distribution;
                }
            }
        }
    }

    $url = ENTRADA_URL."/admin/".$MODULE."/".$SUBMODULE;

    switch ($STEP) {
        case 2 :
            if (isset($distributions) && is_array($distributions)) {
                $success = false;
                foreach ($distributions as $distribution) {
                    if (!$distribution->fromArray(array("deleted_date" => time(), "updated_date" => time(), "updated_by" => $ENTRADA_USER->getActiveID()))->update()) {
                        $ERROR++;
                    } else {
                        $success = true;
                        echo add_success(sprintf($translate->_("Successfully deleted <strong>" . html_encode($distribution->getTitle()) . "</strong>."), $url));
                    }
                }
                
                if ($success) {
                    add_success(sprintf($translate->_("<a href=\"%s\">Click here</a> to return to the Distribution index."), $url));
                }
            } else {
                add_error(sprintf($translate->_("No distributions were selected for deletion. You will be redirected to the Distributions index in 5 seconds or <a href=\"%s\">click here</a> if you do not wish to wait."), $url));
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
            //$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
            break;
        case 1 :
            if (!isset($distributions)) {
                $url =  ENTRADA_URL."/admin/".$MODULE."/".$SUBMODULE;
                add_error(sprintf($translate->_("No distributions were selected for deletion. You will be redirected to the Forms index in 5 seconds or <a href=\"%s\">click here</a> if you do not wish to wait."), $url));
                $ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
            }
        default :
            if ($ERROR) {
                echo display_error();
            }
            if ($SUCCESS) {
                echo display_success();
            }
            if (isset($distributions) && !empty($distributions)) {
                echo display_notice($translate->_("Please confirm below that these are the Distributions you wish to delete.")); 
                $url = ENTRADA_URL . "/admin/assessments/distributions"; ?>

                <form action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?step=2&section=delete"; ?>" method="POST">
                    <table class="table table-bordered table-striped" id="forms-table">
                        <thead>
                            <tr>
                                <th width="5%"></th>
                                <th width="45%"><?php echo $translate->_("Title"); ?></th>
                                <th width="30%"><?php echo $translate->_("Course"); ?></th>
                                <th width="20%"><?php echo $translate->_("Updated Date"); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        foreach ($distributions as $distribution) { ?>
                            <tr>
                                <td><input type="checkbox" name="distributions[]" value="<?php echo $distribution->getID(); ?>" checked="checked" /></td>
                                <td><a href="<?php echo $url; ?>"><?php echo html_encode($distribution->getTitle()); ?></a></td>
                                <td><a href="<?php echo $url; ?>"><?php echo html_encode($distribution->getCourseName()); ?></a></td>
                                <td><a href="<?php echo $url; ?>"><?php echo (is_null($distribution->getUpdatedDate()) ? "N/A" : date("Y-m-d", $distribution->getUpdatedDate())); ?></a></td>
                            </tr>
                        <?php
                        }
                        ?>
                        </tbody>
                    </table>
                    <div class="row-fluid space-below">
                        <a href="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE; ?>" class="btn btn-default"><?php echo $translate->_("Back"); ?></a>
                        <?php if ($distributions) { ?><input type="submit" class="btn btn-danger pull-right" value="<?php echo $translate->_("Delete"); ?>"/><?php } ?>
                    </div>
                </form>
            <?php
            }
    }

}