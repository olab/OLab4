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
 * A model for handling course contacts.
 *
 * @author Organisation: Queen's University
 * @author Developer: Eugene Bivol <ebivol@gmail.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");
if((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("profile", "read", false)) {
    add_error(sprintf($translate->_("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    ob_clear_open_buffers();

    $VALID_MAX_FILESIZE = 100*1048576 ;  // 100MB

    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));

    $request = ${"_" . $request_method};

    if (isset($request["method"]) && $tmp_input = clean_input($request["method"], array("trim", "striptags"))) {
        $method = $tmp_input;
    } else {
        add_error($translate->_("No method supplied."));
    }

    if (!$ERROR) {
        switch ($request_method) {
            case "GET" :
                switch ($method) {
                    case "get-comment" :

                        if (isset($request["acomment_id"]) && $tmp_input = clean_input(strtolower($request["acomment_id"]), array("trim", "int"))) {
                            $PROCESSED["acomment_id"] = $tmp_input;
                        } else {
                            $PROCESSED["acomment_id"] = 0;
                        }

                        if ($PROCESSED["acomment_id"]) {
                            $comment = Models_Assignment_Comments::fetchRowByID($PROCESSED["acomment_id"]);
                        }


                        if ($comment) {

                            $comment_data = array(
                                    "comment_title"         => $comment->getCommentTitle(),
                                    "comment_description"         => $comment->getCommentDescription()
                                );

                            echo json_encode(array("status" => "success", "data" => $comment_data));
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("No Comment Found."))));
                        }
                        break;
                    default:
                        echo json_encode(array("status" => "error", "data" => $translate->_("Invalid GET method.")));
                        break;
                }
                break;
            case "POST" :
                switch ($method) {
                    case "update-comment" :

                    if (isset($request["acomment_id"]) && $tmp_input = clean_input(strtolower($request["acomment_id"]), array("trim", "int"))) {
                        $PROCESSED["acomment_id"] = $tmp_input;
                    } else {
                        $PROCESSED["acomment_id"] = 0;
                    }

                    if (isset($request["assignment_id"]) && $tmp_input = clean_input(strtolower($request["assignment_id"]), array("trim", "int"))) {
                        $PROCESSED["assignment_id"] = $tmp_input;
                    } else {
                        $PROCESSED["assignment_id"] = 0;
                    }

                    if (isset($request["assignment_proxy_id"]) && $tmp_input = clean_input(strtolower($request["assignment_proxy_id"]), array("trim", "int"))) {
                        $PROCESSED["proxy_to_id"] = $tmp_input;
                    } else {
                        $PROCESSED["proxy_to_id"] = 0;
                    }

                    if (isset($request["comment_title"]) && $tmp_input = clean_input($request["comment_title"], array("trim", "striptags"))) {
                        $PROCESSED["comment_title"] = $tmp_input;
                    } else {
                        $PROCESSED["comment_title"] = "";
                    }

                    if (isset($request["comment_description"]) && $tmp_input = clean_input($request["comment_description"], array("trim"))) {
                        $PROCESSED["comment_description"] = $tmp_input;
                    } else {
                        $PROCESSED["comment_description"] = "";
                    }

                    if ($PROCESSED["assignment_id"] && $PROCESSED["comment_description"] && $PROCESSED["proxy_to_id"]) {
                        $PROCESSED["proxy_id"]			= $ENTRADA_USER->getID();
                        $PROCESSED["updated_date"]		= time();
                        $PROCESSED["updated_by"]		= $ENTRADA_USER->getID();

                        if (!$PROCESSED["acomment_id"]) {
                            unset($PROCESSED["acomment_id"]);
                            $PROCESSED["release_date"]		= time();
                            $PROCESSED["comment_active"]	= 1;

                            $comment_object = new Models_Assignment_Comments();

                            if (!$comment_object->fromArray($PROCESSED)->insert()) {
                                add_error($translate->_("Unable to Add an Assignment Comment"));
                            }
                        } else {
                            $comment_object = Models_Assignment_Comments::fetchRowByID($PROCESSED["acomment_id"]);
                            if (!$comment_object->fromArray($PROCESSED)->update()) {
                                add_error($translate->_("Unable to Update this Assignment Comment"));
                            }
                        }

                        if (!$ERROR) {
                            echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully Added/Updates Comment.")));
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $translate->_("There was an error when attempting to add/update an assignment comment.")));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "msg" => $translate->_("There was an error when attempting to add/update an assignment comment.")));
                    }
                    break;

                    case "delete-comment" :

                        if (isset($request["acomment_id"]) && $tmp_input = clean_input(strtolower($request["acomment_id"]), array("trim", "int"))) {
                            $PROCESSED["acomment_id"] = $tmp_input;
                        } else {
                            $PROCESSED["acomment_id"] = 0;
                        }

                        if ($PROCESSED["acomment_id"]) {
                            $PROCESSED["proxy_id"]			= $ENTRADA_USER->getID();

                            $comment_object = Models_Assignment_Comments::fetchRowByID($PROCESSED["acomment_id"]);
                            if ($comment_object && $comment_object->getProxyID() == $PROCESSED["proxy_id"]) {
                                if(!$comment_object->fromArray(array("comment_active" => 0))->update()) {
                                    add_error($translate->_("Unable to Delete an Assignment Comment"));
                                }
                            } else {
                                add_error($translate->_("Current user is unable to Delete this Assignment Comment"));
                            }

                            if (!$ERROR) {
                                echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully Deleted Comment.")));
                            } else {
                                echo json_encode(array("status" => "error", "msg" => $translate->_("There was an error when attempting to delete an assignment comment.")));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $translate->_("There was an error when attempting to delete an assignment comment.")));
                        }
                    break;
                    case "delete-file" :

                        if (isset($request["afversion_id"]) && $tmp_input = clean_input(strtolower($request["afversion_id"]), array("trim", "int"))) {
                            $PROCESSED["afversion_id"] = $tmp_input;
                        } else {
                            $PROCESSED["afversion_id"] = 0;
                        }

                        if ($PROCESSED["afversion_id"]) {
                            $PROCESSED["proxy_id"]			= $ENTRADA_USER->getID();

                            $file_version_object = Models_Assignment_File_Version::fetchRowByID($PROCESSED["afversion_id"]);

                            if ($file_version_object && $file_version_object->getProxyID() == $PROCESSED["proxy_id"]) {
                                if(!$file_version_object->fromArray(array("file_active" => 0))->update()) {
                                    add_error($translate->_("Unable to Delete this Assignment File"));
                                }

                                $rest_file_version_object = Models_Assignment_File_Version::fetchMostRecentByAFileID($file_version_object->getAfileID());
                                if (!$rest_file_version_object) {
                                    $file_object = Models_Assignment_File::fetchRowByID($file_version_object->getAfileID());
                                    if ($file_object && !$file_object->fromArray(array("file_active" => 0))->update()) {
                                        add_error($translate->_("Unable to Delete this Assignment General File"));
                                    }
                                }
                            } else {
                                add_error($translate->_("Current user is unable to Delete this Assignment File"));
                            }

                            if (!$ERROR) {
                                echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully Deleted this File.")));
                            } else {
                                echo json_encode(array("status" => "error", "msg" => implode("<br />", $ERROR)));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "msg" => implode("<br />", $ERROR)));
                        }
                    break;
                    case "file-upload" :
                        /**
                         * Required field "title" / File Title.
                         */
                        if ((isset($_POST["file_title"])) && ($title = clean_input($_POST["file_title"], array("notags", "trim")))) {
                            $PROCESSED["file_title"] = $title;
                        } elseif ((isset($PROCESSED["file_filename"])) && ($PROCESSED["file_filename"])) {
                            $PROCESSED["file_title"] = $PROCESSED["file_filename"];
                        } else {
                            add_error("The <strong>File Title</strong> field is required.");
                        }

                        /**
                         * Non-Required field "description" / File Description.
                         *
                         */
                        if ((isset($_POST["file_description"])) && $description = clean_input($_POST["file_description"], array("notags", "trim"))) {
                            $PROCESSED["file_description"] = $description;
                        } else {
                            $PROCESSED["file_description"] = "";
                        }

                        if (isset($request["file_assignment_id"]) && $tmp_input = clean_input(strtolower($request["file_assignment_id"]), array("trim", "int"))) {
                            $PROCESSED["assignment_id"] = $tmp_input;
                        } else {
                            $PROCESSED["assignment_id"] = 0;
                        }

                        if ((isset($_POST["file_type"])) && $description = clean_input($_POST["file_type"], array("notags", "trim"))) {
                            $PROCESSED["file_type"] = $description;
                        } else {
                            $PROCESSED["file_type"] = "";
                        }

                        if (isset($request["file_parent_id"]) && $tmp_input = clean_input(strtolower($request["file_parent_id"]), array("trim", "int"))) {
                            $PROCESSED["parent_id"] = $tmp_input;
                        } else {
                            $PROCESSED["parent_id"] = 0;
                        }

                        $uploaded_file = false;
                        if ($_FILES["assignment_file"] && !$_FILES["assignment_file"]["error"]) {
                            if (($file_filesize = (int)trim($_FILES["assignment_file"]["size"])) <= $VALID_MAX_FILESIZE) {

                                $finfo = new finfo(FILEINFO_MIME);
                                $type = $finfo->file($_FILES["assignment_file"]["tmp_name"]);
                                $type_array = explode(";", $type);
                                $mimetype = $type_array[0];



                                $filetypes = Models_Filetypes::fetchAllRecords();

                                if ($filetypes) {
                                    foreach ($filetypes as $filetype) {
                                        if ($filetype->getMime() == strtolower(trim($mimetype))) {
                                            $uploaded_file = true;
                                            break;
                                        }
                                    }
                                }

                                if ($uploaded_file) {
                                    if (!DEMO_MODE) {
                                        $PROCESSED["file_version"] = 1;
                                        $PROCESSED["file_mimetype"] = strtolower(trim($mimetype));
                                        $PROCESSED["file_filesize"] = $file_filesize;
                                        $PROCESSED["file_filename"] = useable_filename(trim($_FILES["assignment_file"]["name"]));
                                    } else {
                                        $PROCESSED["file_version"] = 1;
                                        $PROCESSED["file_mimetype"] = filetype(DEMO_ASSIGNMENT);
                                        $PROCESSED["file_filesize"] = filesize(DEMO_ASSIGNMENT);
                                        $PROCESSED["file_filename"] = basename(DEMO_ASSIGNMENT);
                                    }
                                } else {
                                    $file_error = "File type is not supported by the system";
                                }
                            }
                        } elseif ($_FILES["assignment_file"]["error"]) {
                            $file_error = "";
                            switch($_FILES["assignment_file"]["error"]) {
                                case 1 :
                                case 2 :
                                $file_error = "The file that was uploaded is larger than ".readable_size($VALID_MAX_FILESIZE).". Please make the file smaller and try again.";
                                    break;
                                case 3 :
                                    $file_error = "The file that was uploaded did not complete the upload process or was interrupted; please try again.";
                                    break;
                                case 4 :
                                    $file_error = "You did not select a file from your computer to upload. Please select a local file and try again.";
                                    break;
                                case 6 :
                                case 7 :
                                    $file_error = "Unable to store the new file on the server; the MEdTech Unit has been informed of this error, please try again later.";
                                    break;
                                default :
                                    $file_error = "Unrecognized file upload error number [".$_FILES["assignment_file"]["error"]."].";
                                    break;
                            }
                        }


                        if ($PROCESSED["assignment_id"] && $PROCESSED["file_title"] && $PROCESSED["file_type"] && $uploaded_file) {
                            $PROCESSED["proxy_id"]		= $ENTRADA_USER->getActiveId();
                            $PROCESSED["file_active"]	= 1;
                            $PROCESSED["updated_date"]	= time();
                            $PROCESSED["updated_by"]	= $ENTRADA_USER->getActiveId();

                            $assignment_file = new Models_Assignment_File();
                            if ($assignment_file->fromArray($PROCESSED)->insert()) {
                                $PROCESSED["afile_id"] = $assignment_file->getAfileID();
                                $assignment_file_version = new Models_Assignment_File_Version();
                                if ($assignment_file_version->fromArray($PROCESSED)->insert()) {
                                    if (assignments_process_file($_FILES["assignment_file"]["tmp_name"], $assignment_file_version->getAfversionID())) {
                                        if (!DEMO_MODE) {
                                            add_success("You have successfully uploaded ".html_encode($PROCESSED["file_filename"])." (version 1).");
                                        } else {
                                            add_success("Entrada is in demo mode therefore the Entrada demo assignment file was used for this import instead of the file you attempted to upload");
                                        }
                                        echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully Uploaded new Assignment File.")));
                                    } else {
                                        echo json_encode(array("status" => "error", "msg" => $translate->_("Unable to upload this assignment file")));
                                    }
                                } else {
                                    echo json_encode(array("status" => "error", "msg" => $translate->_("Unable to create this file version in DB")));
                                }
                            } else {
                                echo json_encode(array("status" => "error", "msg" => $translate->_("Unable to create this file record in DB")));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $translate->_("Required parameters are missing or file upload error present <br/>".$file_error)));
                        }
                     break;

                    case "version-upload" :
                        if (isset($request["file_assignment_id"]) && $tmp_input = clean_input(strtolower($request["file_assignment_id"]), array("trim", "int"))) {
                            $PROCESSED["assignment_id"] = $tmp_input;
                        } else {
                            $PROCESSED["assignment_id"] = 0;
                        }

                        if (isset($request["file_afile_id"]) && $tmp_input = clean_input(strtolower($request["file_afile_id"]), array("trim", "int"))) {
                            $PROCESSED["afile_id"] = $tmp_input;
                        } else {
                            $PROCESSED["afile_id"] = 0;
                        }

                        $uploaded_file = false;
                        if ($_FILES["assignment_file"] && !$_FILES["assignment_file"]["error"]) {
                            if (($file_filesize = (int)trim($_FILES["assignment_file"]["size"])) <= $VALID_MAX_FILESIZE) {

                                $filetypes = Models_Filetypes::fetchAllRecords();

                                $finfo = new finfo(FILEINFO_MIME);
                                $type = $finfo->file($_FILES["assignment_file"]["tmp_name"]);
                                $type_array = explode(";", $type);
                                $mimetype = $type_array[0];

                                if ($filetypes) {
                                    foreach ($filetypes as $filetype) {
                                        if ($filetype->getMime() == strtolower(trim($mimetype))) {
                                            $uploaded_file = true;
                                            break;
                                        }
                                    }
                                }

                                if ($uploaded_file) {
                                    if (!DEMO_MODE) {
                                        $PROCESSED["file_version"] = 1;
                                        $PROCESSED["file_mimetype"] = strtolower(trim($mimetype));
                                        $PROCESSED["file_filesize"] = $file_filesize;
                                        $PROCESSED["file_filename"] = useable_filename(trim($_FILES["assignment_file"]["name"]));
                                    } else {
                                        $PROCESSED["file_version"] = 1;
                                        $PROCESSED["file_mimetype"] = filetype(DEMO_ASSIGNMENT);
                                        $PROCESSED["file_filesize"] = filesize(DEMO_ASSIGNMENT);
                                        $PROCESSED["file_filename"] = basename(DEMO_ASSIGNMENT);
                                    }
                                } else {
                                    $file_error = "File type is not supported by the system";
                                }
                            }
                        } elseif ($_FILES["assignment_file"]["error"]) {
                            $file_error = "";
                            switch($_FILES["assignment_file"]["error"]) {
                                case 1 :
                                case 2 :
                                    $file_error = "The file that was uploaded is larger than ".readable_size($VALID_MAX_FILESIZE).". Please make the file smaller and try again.";
                                    break;
                                case 3 :
                                    $file_error = "The file that was uploaded did not complete the upload process or was interrupted; please try again.";
                                    break;
                                case 4 :
                                    $file_error = "You did not select a file from your computer to upload. Please select a local file and try again.";
                                    break;
                                case 6 :
                                case 7 :
                                    $file_error = "Unable to store the new file on the server; the MEdTech Unit has been informed of this error, please try again later.";
                                    break;
                                default :
                                    $file_error = "Unrecognized file upload error number [".$_FILES["assignment_file"]["error"]."].";
                                    break;
                            }
                        }


                        if ($PROCESSED["assignment_id"] && $PROCESSED["afile_id"] && $uploaded_file) {

                            $file_version = Models_Assignment_File_Version::fetchMostRecentByAFileID($PROCESSED["afile_id"]);

                            $PROCESSED["proxy_id"]		= $ENTRADA_USER->getActiveId();
                            $PROCESSED["file_active"]	= 1;
                            $PROCESSED["updated_date"]	= time();
                            $PROCESSED["updated_by"]	= $ENTRADA_USER->getActiveId();
                            $PROCESSED["file_version"] = $file_version->getFileVersion()+1;

                            $assignment_file_version = new Models_Assignment_File_Version();
                            if ($assignment_file_version->fromArray($PROCESSED)->insert()) {
                                if (assignments_process_file($_FILES["assignment_file"]["tmp_name"], $assignment_file_version->getAfversionID())) {
                                    if (!DEMO_MODE) {
                                        add_success("You have successfully uploaded ".html_encode($PROCESSED["file_filename"])." (version 1).");
                                    } else {
                                        add_success("Entrada is in demo mode therefore the Entrada demo assignment file was used for this import instead of the file you attempted to upload");
                                    }
                                    echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully Uploaded new Assignment File.")));
                                } else {
                                    echo json_encode(array("status" => "error", "msg" => $translate->_("Unable to upload this assignment file")));
                                }
                            } else {
                                echo json_encode(array("status" => "error", "msg" => $translate->_("Unable to create this file version in DB")));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $translate->_("Required parameters are missing or file upload error present <br/>".$file_error)));
                        }
                        break;
                }
                break;
        }
    } else {
        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
    }
    exit;
}
