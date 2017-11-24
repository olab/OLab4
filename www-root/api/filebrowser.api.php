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
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
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

ob_start("on_checkout");

if((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    exit;
} else {
    switch ($STEP) {
        case 2 :
            if (isset($_FILES["upload"]) && is_array($_FILES["upload"])) {
                $i = 0;
                foreach($_FILES["upload"]["name"] as $file) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime_type = finfo_file($finfo, $_FILES["upload"]["tmp_name"][$i]);
                    finfo_close($finfo);
                    
                    if (in_array($mime_type, get_allowed_mime_types("lor"))) {
                        $lo_file_data = array(
                            "filename"  => clean_input($_FILES["upload"]["name"][$i], array("trim", "striptags")),
                            "filesize"  => clean_input($_FILES["upload"]["size"][$i], array("trim", "striptags")),
                            "mime_type" => $mime_type,
                            "proxy_id"  => clean_input($ENTRADA_USER->getActiveID(), "int"),
                            "public"    => "0",
                            "updated_date" => time(),
                            "updated_by"   => clean_input($ENTRADA_USER->getActiveID(), "int"),
                            "active"    => "1"
                        );

                        $lo_file = new Models_LearningObject($lo_file_data);
                        if ($lo_file->insert()) {
                            if (!is_dir(LOR_STORAGE_PATH . "/" . $ENTRADA_USER->getActiveID())) {
                                mkdir(LOR_STORAGE_PATH . "/" . $ENTRADA_USER->getActiveID());
                            }

                            // Use the move_uploaded_file() function in PHP.
                            if (move_uploaded_file($_FILES["upload"]["tmp_name"][$i], LOR_STORAGE_PATH . "/" . $ENTRADA_USER->getActiveID() . "/" . $lo_file->getLoFileID())) {
                                add_success("Successfully uploaded <strong>" . $_FILES["upload"]["name"][$i] ."</strong>!");
                            } else {
                                add_error("Failed to upload <strong>" . $_FILES["upload"]["name"][$i] ."</strong>!");
                            }
                        }
                    } else {
                        $ERROR++;
                        $invalid_files[] = $_FILES["upload"]["name"][$i];
                    }
                    $i++;
                }
            }
            
            $STEP = 1;
        break;
    }
    
    ob_clear_open_buffers();
    
    ?>
<!DOCTYPE html>
<html>
    <head>
        <title>File Manager</title>
        <link href="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/css/bootstrap.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" type="text/css" media="all" />
        <link href="<?php echo ENTRADA_RELATIVE; ?>/css/common.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" type="text/css" media="all" />
        <link href="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/css/common.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" type="text/css" media="all" />
        <link href="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/css/style.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" type="text/css" media="all" />
        <link href="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/images/favicon.ico" rel="shortcut icon" type="image/x-icon" />
        <link href="<?php echo ENTRADA_URL; ?>/css/filebrowser.css" rel="stylesheet" type="text/css" media="all" />
        <script type="text/javascript" src="<?php echo ENTRADA_RELATIVE; ?>/javascript/jquery/jquery.min.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script type="text/javascript" src="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/js/libs/bootstrap.min.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script type="text/javascript" src="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/js/libs/modernizr-2.5.3.min.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script type="text/javascript" src="<?php echo ENTRADA_URL; ?>/javascript/jquery/jquery.dataTables.min.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <?php 
            load_rte();
        ?>
        <script type="text/javascript">
            var ENTRADA_URL = "<?php echo ENTRADA_URL; ?>";
        </script>
        <script type="text/javascript" src="<?php echo ENTRADA_URL; ?>/javascript/filebrowser.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
    </head>
    <body>
    
    <?php
    
    $PROCESSED["type"]               = strtolower(clean_input($_GET["type"],            array("trim", "striptags")));
    $PROCESSED["CKEditor"]           = strtolower(clean_input($_GET["CKEditor"],        array("trim", "striptags")));
    $PROCESSED["CKEditorFuncNum"]    = strtolower(clean_input($_GET["CKEditorFuncNum"], array("trim", "striptags")));
    $PROCESSED["langCode"]           = strtolower(clean_input($_GET["langCode"],        array("trim", "striptags")));
    
    switch ($STEP) {
        case 1 :
        default:
            $allowed_types = array("images", "files");

            if (in_array($PROCESSED["type"], $allowed_types)) {

                if (!is_dir(LOR_STORAGE_PATH . "/" . $ENTRADA_USER->getActiveID())) {
                    mkdir(LOR_STORAGE_PATH . "/" . $ENTRADA_USER->getActiveID());
                }

                ?>
                <div class="navbar navbar-inverse navbar-fixed-top">
                    <div class="navbar-inner">
                        <div class="container">
                            <a class="brand" href="#">My Files</a>
                        </div>
                    </div>
                </div>
                <div class="row file-browser-panel">
                    <div class="row-fluid toolbar">
                        <div id="dropzone" class="span12 space-below">
                            <h3>Upload Files</h3>
                            <p class="event-resource-upload-text">You can drag and drop files into this window to upload. If you wish you can also use the browse button below to upload files.</p>
                            <?php
                            if (has_error()) {
                                if (isset($invalid_files) && !empty($invalid_files)) {
                                    $msg = "<p>The following files are not supported file types:</p>";
                                    $msg .= "<ul>";
                                    foreach ($invalid_files as $invalid_file) {
                                        $msg .= "<li><strong>" . $invalid_file . "</strong></li>";
                                    }
                                    $msg .= "</ul>";
                                    add_error($msg);
                                }
                                ?>
                                <div class="alert alert-block alert-error space-above"><?php echo $msg; ?></div>
                                <?php
                            }
                            ?>
                            <div id="success-msg-holder" class="space-above">
                                <?php
                                if (has_success()) {
                                    echo display_success();
                                }
                                ?>
                            </div>
                            <form id="fileupload" method="POST" enctype="multipart/form-data" action="<?php echo ENTRADA_URL; ?>/api/filebrowser.api.php?type=<?php echo ucwords($PROCESSED["type"]); ?>&CKEditor=<?php echo $PROCESSED["CKEditor"]; ?>&CKEditorFuncNum=<?php echo $PROCESSED["CKEditorFuncNum"]; ?>&langCode=<?php echo $PROCESSED["langCode"]; ?>" class="space-above form">
                                <input type="hidden" name="step" value="2" />
                                <div class="row-fluid">
                                    <div class="form-group space-below">
                                        <label class="btn btn-success span3">Choose File <input type="file" name="upload[]" class="hide" multiple="multiple" id="fileinput" /></label>
                                        <span class="filename span6">No file selected</span>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="pull-right">
                            <div class="btn-group">
                                <a href="#table-view" class="btn btn-default active toggle-view"><i class="icon-th-list"></i> List View</a>
                                <a href="#tile-view" class="btn btn-default toggle-view"><i class="icon-th"></i> Grid View</a>
                            </div>
                        </div>
                    </div>
                    <div id="file-list">
                        <table id="table-view" class="table table-bordered table-striped ">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th width="15%">Size</th>
                                    <th width="20%">Uploaded</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        <ul id="tile-view" class="hide"></ul>
                    </div>
                    <div id="uploading-modal" class="modal hide fade">
                        <div class="modal-header">
                            <h3>Please wait while your files are uploaded.</h3>
                        </div>
                        <div class="modal-body">
                            <div class="modal-messages hide"></div>
                            <p class="modal-loading center">
                                <img src="<?php echo ENTRADA_URL; ?>/images/loading_large.gif" width="256" height="256" alt="Loading" />
                            </p>
                        </div>
                    </div>
                </div>
                <?php
            }
        break;
    }
        ?>
    </body>
</html>
        <?php
    exit;
}