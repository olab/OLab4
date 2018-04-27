<?php
/**
 * Online Course Resources [Pre-Clerkship]
 * @author Unit: Medical Education Technology Unit
 * @author Director: Dr. Benjamin Chen <bhc@post.queensu.ca>
 * @author Developer: Matt Simpson <simpson@post.queensu.ca>
 * @version 3.0
 * @copyright Copyright 2006 Queen's University, MEdTech Unit
 *
 * $Id: personnel.api.php 1140 2010-04-27 18:59:15Z simpson $
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
    if (isset($_POST["method"])) {
        
        $PROCESSED["uri"] = array_key_exists("pageUri", $_POST) ? clean_input($_POST["pageUri"], array("trim","notags")) : null;
        $jsonReturn = array();
        
        switch($_POST["method"]) {
            case "add-bookmark":

                if (isset($_POST["pageUri"])) {
                    
                    $PROCESSED["bookmark_title"] = clean_input($_POST["bookmarkTitle"],array("trim","notags"));
                    $PROCESSED["proxy_id"] = $ENTRADA_USER->getID();
                    $PROCESSED["organisation_id"] = $ENTRADA_USER->getActiveOrganisation();
                    $PROCESSED["updated_date"] = time();
                    $PROCESSED["order"] = 0; //Add it to the top of the bookmark list
                    
                    if (strlen($PROCESSED["bookmark_title"]) > 0) {

                        $AddBookmark = new Models_Bookmarks($PROCESSED);

                        if ($AddBookmark->insert($PROCESSED)) {											

                            add_statistic("Bookmarks", "add", "bookmarks.bookmark_id", $AddBookmark->getId(), $ENTRADA_USER->getID());

                            $jsonReturn["type"] = "add";
                            $jsonReturn["feedback"] = "Success";
                            $jsonReturn["bookmark_id"] = $AddBookmark->getId();

                        } else {
                            $errorMsg = "An error occurred while adding a bookmark for this page.  The system administrator was informed of this error; please try again later.";

                            $jsonReturn["error"][] = $errorMsg;
                            add_error($errorMsg);
                            application_log("error", "Error inserting bookmark for page URL: " . $PROCESSED["uri"] . " by Proxy ID: ". $PROCESSED["proxy_id"]);
                        }
                    } else {
                        $jsonReturn["error"][] = "Bookmark title cannot be blank!";
                    }


                } else {
                    $jsonReturn["error"][] = "No page URL was submitted";
                } 
                
                break;
            case "remove-bookmark":
                if (isset($_POST["bookmarkId"]) && $_POST["bookmarkId"] != "") {
                    
                    $PROCESSED["id"] = clean_input($_POST["bookmarkId"],array("trim","notags"));
                    $PROCESSED["proxy_id"] = $ENTRADA_USER->getID();
                    
                    $RemoveBookmark = new Models_Bookmarks($PROCESSED);
                    
                    if ($RemoveBookmark->delete()) {
                        $jsonReturn["type"] = "remove";
                        $jsonReturn["bookmark_id"] = $PROCESSED["id"];
                        $jsonReturn["proxy_id"] = $ENTRADA_USER->getID();
                        $jsonReturn["feedback"] = "Success";
                        
                    } else {
                        $errorMsg = "An error occurred while removing your bookmark for this page.  The system administrator was informed of this error; please try again later.";

                        $jsonReturn["error"][] = $errorMsg;
                        add_error($errorMsg);
                        application_log("error", "Error removing bookmark for Bookmark ID: ".$PROCESSED["id"].", page URL: " . $PROCESSED["uri"] . " by Proxy ID: ". $PROCESSED["proxy_id"]);
                    }
                }
                        
                break;
            case "get-bookmarks":

                if (isset($_POST["search_value"]) && $tmp_input = clean_input(strtolower($_POST["search_value"]), array("trim", "striptags"))) {
                    $PROCESSED["search_value"] = $tmp_input;
                } else {
                    $PROCESSED["search_value"] = "";
                }

                $Bookmarks = new Models_Bookmarks();
                
                $Bookmarks = $Bookmarks->fetchAllByProxyIdOrganisationId($ENTRADA_USER->getID(), $ENTRADA_USER->getActiveOrganisation(), $PROCESSED["search_value"]);
                
                if ($Bookmarks) {
                    foreach($Bookmarks as $Bookmark) {
                        $bookmarkProperties = array("url"=>$Bookmark->getUri(), "title"=>$Bookmark->getBookmarkTitle(), "bookmark_id"=>$Bookmark->getId());
                        
                        if ($PROCESSED["uri"] == $Bookmark->getUri()) {
                            $bookmarkProperties["current"] = 1;
                        }
                        $jsonReturn[] = $bookmarkProperties;       
                    }
                }
                break;
            case "sort-bookmarks":
                $Bookmarks = new Models_Bookmarks();
                
                if (isset($_POST["bookmark"])) {
                    foreach($_POST["bookmark"] as $order => $bookmark_id) {
                        $PROCESSED["id"] = clean_input($bookmark_id,array("trim","notags"));
                        $PROCESSED["order"] = clean_input($order,array("trim","notags"));
                        $PROCESSED["updated_date"] = time();
                        
                        
                        //Update Model
                        $UpdateBookmark = $Bookmarks->fetchBookmarkById ($ENTRADA_USER->getID(), $PROCESSED["id"]);
                        $UpdateBookmark->setOrder($PROCESSED["order"]);
                        $UpdateBookmark->setUpdatedDate($PROCESSED["updated_date"]);
                        
                        //Persist to the DB
                        if ($UpdateBookmark->update()) {
                            $jsonReturn[$order] = $bookmark_id;
                        } else {
                            $jsonReturn["error"][] = "An error occurred while updating the sort order for Bookmark ID: ".$PROCESSED["id"];
                        }
                                
                    }
                    $jsonReturn["type"] = "update-sort";
                    $jsonReturn["feedback"] = "Success";

                } else {
                    $jsonReturn["error"][] = "An error occurred while updating the bookmark list sort order";
                    application_log("error", "Error updating bookmark order for Proxy ID: ".$ENTRADA_USER->getID());

                }
                break;
                
            case "edit-bookmark":
                $Bookmarks = new Models_Bookmarks();
                
                if (isset($_POST["bookmarkId"]) && $_POST["bookmarkId"] != "") {
                    $PROCESSED["bookmark_title"] = clean_input($_POST["updatedTitle"],array("trim","notags"));
                    $PROCESSED["id"] = clean_input($_POST["bookmarkId"],array("trim","notags"));
                    $PROCESSED["updated_date"] = time();
                    
                    $UpdateBookmark = $Bookmarks->fetchBookmarkById ($ENTRADA_USER->getID(), $PROCESSED["id"]);
                    $UpdateBookmark->setBookmarkTitle($PROCESSED["bookmark_title"]);
                    $UpdateBookmark->setUpdatedDate($PROCESSED["updated_date"]);
                        
                    if ($UpdateBookmark->update()) {
                        $jsonReturn["success"] = true;
                        $jsonReturn["feedback"] = "Success";
                        $jsonReturn["type"] = "update-title";
                    } else {
                        $jsonReturn["success"] = false;
                        $jsonReturn["feedback"] = "Fail";
                        $jsonReturn["error"] = "An error occurred while updating the bookmark title Bookmark ID: ".$PROCESSED["id"];
                        application_log("error", "Error updating bookmark order for Proxy ID: ".$ENTRADA_USER->getID());
                    }
                                
                } else {
                    $jsonReturn["error"][] = "An error occurred while updating the bookmark list sort order";
                    application_log("error", "Error updating bookmark order for Proxy ID: ".$ENTRADA_USER->getID());

                }
                break;
                
        }
    }
    
    header("Content-type: application/json");
    echo json_encode($jsonReturn);
	
	
} else {
	application_log("error", "Bookmark API accessed without valid session_id.");
	echo htmlspecialchars(json_encode(array("error"=>"Bookmark API accessed without valid session_id.")), ENT_NOQUOTES);
	exit;
}