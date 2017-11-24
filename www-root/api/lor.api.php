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
 * API to handle interaction with learning object repository.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
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

if((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("profile", "read", true)) {
	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {

	$request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));
	
	$request = ${"_" . $request_method};
	
    if ($ENTRADA_USER->getActiveRole() == "admin") {
        if (isset($request["proxy_id"]) && $tmp_input = clean_input($request["proxy_id"], "int")) {
            $PROCESSED["proxy_id"] = $tmp_input;
        } else {
            $PROCESSED["proxy_id"] = $ENTRADA_USER->getActiveID();
        }
    } else {
        $PROCESSED["proxy_id"] = $ENTRADA_USER->getActiveID();
    }
    
	switch ($request_method) {
		case "POST" :
            if (isset($_GET["method"]) && $tmp_input = clean_input($_GET["method"], array("trim", "striptags"))) {
                $request["method"] = $tmp_input;
            }

			switch ($request["method"]) {
                case "upload-files" :
                    if (isset($_FILES["upload"]) && is_array($_FILES["upload"])) {
                        $i = 0;
                        foreach($_FILES["upload"]["name"] as $file) {
                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                            $mime_type = finfo_file($finfo, $_FILES["upload"]["tmp_name"][$i]);
                            finfo_close($finfo);

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

                                if (move_uploaded_file($_FILES["upload"]["tmp_name"][$i], LOR_STORAGE_PATH . "/" . $ENTRADA_USER->getActiveID() . "/" . $lo_file->getLoFileID())) {
                                    add_success("Successfully uploaded <strong>" . $_FILES["upload"]["name"][$i] ."</strong>!");
                                } else {
                                    add_error("Failed to upload <strong>" . $_FILES["upload"]["name"][$i] ."</strong>!");
                                }
                            }
                                
                            $i++;
                        }
                    }

                    if (!$ERROR) {
                        echo json_encode(array("status" => "success", "data" => $SUCCESSSTR));
                    } else {
                        if (isset($invalid_files) && !empty($invalid_files)) {
                            echo json_encode(array("status" => "error", "data" => array("invalid_files" => $invalid_files)));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                    }
                    
                break;
            }
        break;
        case "GET" :
            switch ($request["method"]) {
                case "get-lo-details" :
                    // This is for future development.
                    if (isset($request["lo_file_id"]) && $tmp_input = clean_input($request["lo_file_id"], "int")) {
                        $lo_file_id = $tmp_input;
                    }
                    
                    if ($lo_file_id) {
                        $lo_file = Models_LearningObject::fetchRowByID($tmp_input);
                        if ($lo_file) {
                            
                            $tags = Models_LearningObject_Tag::fetchAllRecordsByFileID($lo_file_id);
                            $t = "";
                            if ($tags) {
                                $t = array();
                                foreach ($tags as $tag) {
                                    $t[] = $tag->getTag();
                                }
                            }
                            
                            $permissions = Models_LearningObject_Permission::fetchAllRecordsByFileID($lo_file_id);
                            $p = "";
                            if ($permissions) {
                                $p = array();
                                foreach ($permissions as $permissions) {
                                    $p[] = array("proxy_id" => $permissions->getProxyID(), "permission" => $permissions->getPermission());
                                }
                            }
                            
                            $lo_file_data = array(
                                "description" => $lo_file->getDescription(),
                                "filesize" => readable_size($lo_file->getFilesize()),
                                "filename" => $lo_file->getFilename(),
                                "tags" => $t,
                                "permissions" => ""
                            );
                            echo json_encode(array("status" => "success", "data" => $lo_file_data));
                        }
                    }
                    
                break;
                case "get-learning-objects" :

                    $output = array();
                    $output["aaData"] = array();
                    $count = 0;

                    if (isset($request["type"]) && in_array(strtolower(clean_input($request["type"], array("trim", "striptags"))), array("images", "files"))) {
                        $PROCESSED["type"] = strtolower(clean_input($request["type"], array("trim", "striptags")));
                    }
                    
                    if ($PROCESSED["type"] == "images") {
                        $image_types = array("image/jpeg", "image/png", "image/gif");
                        $lo_files = array();
                        foreach ($image_types as $image_type) {
                            $files = Models_LearningObject::fetchAllRecordsByProxyID($PROCESSED["proxy_id"], $image_type);
                            if ($files) {
                                foreach($files as $file) {
                                    $lo_files[] = $file;
                                }
                            }
                        }
                    } else {
                        $lo_files = Models_LearningObject::fetchAllRecordsByProxyID($PROCESSED["proxy_id"]);
                    }
                    
                    if ($lo_files) {
                        if (isset($_GET["iSortCol_0"]) && in_array($_GET["iSortCol_0"], array(1, 2, 3))) {
                            $aColumns = array("filename", "filesize", "updated_date");
                            $sort_array = array();
                            foreach ($lo_files as $lo_file) {
                                $files_array = $lo_file->toArray();
                                $sort_array[] = $files_array[$aColumns[clean_input($_GET["iSortCol_0"], "int")]];
                            }
                            array_multisort($sort_array, (isset($_GET["sSortDir_0"]) && $_GET["sSortDir_0"] == "desc" ? SORT_DESC : SORT_ASC), SORT_STRING, $lo_files);
                        }
                        if (isset($_GET["iDisplayStart"]) && isset($_GET["iDisplayLength"]) && $_GET["iDisplayLength"] != "-1" ) {
                            $start = (int)$_GET["iDisplayStart"];
                            $limit = (int)$_GET["iDisplayLength"];
                        } else {
                            $start = 0;
                            $limit = count($lo_files) - 1;
                        }
                        if ($_GET["sSearch"] != "") {
                            $search_value = $_GET["sSearch"];
                        }

                        foreach ($lo_files as $lo_file) {
                            if (!isset($search_value) || stripos($lo_file->getFilename(), $search_value) !== false || stripos($lo_file->getFilesize(), $search_value) !== false || stripos(date("Y-m-d", $lo_file->getUpdatedDate()), $search_value) !== false) {
                                $url = ENTRADA_URL . "/api/serve-learning-object.api.php?id=" . $lo_file->getLoFileID() . "&filename=" . urlencode($lo_file->getFilename());
                                if ($count >= $start && $count < ($start + $limit)) {
                                    $output["aaData"][] = array(
                                        "id"            => $lo_file->getLoFileID(),
                                        "name"          => urlencode($lo_file->getFilename()),
                                        "filename"      => "<a href=\"" . $url . "\" data-lo-file-id=\"" . $lo_file->getLoFileID() . "\" data-lo-filename=\"" . urlencode($lo_file->getFilename()) . "\" class=\"serve-file-link\">" . $lo_file->getFilename() . "</a>",
                                        "filesize"      => readable_size($lo_file->getFilesize()),
                                        "updated_date"  => date("Y-m-d H:i", $lo_file->getUpdatedDate()),
                                    );
                                }
                                $count++;
                            }
                        }
                    }

                    $output["iTotalRecords"] = (is_array($lo_files) ? @count($lo_files) : 0);
                    $output["iTotalDisplayRecords"] = $count;
                    $output["sEcho"] = clean_input($_GET["sEcho"], "int");
                    echo json_encode($output);
                    
                break;
                default :
                break;
            }
        break;
    }
    
}
