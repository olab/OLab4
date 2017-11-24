<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to list any available folders under the specific page_id.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_SHARES"))) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

// Gets the folder/document tree for the index
$shares_index = Models_Community_Share::getSharesIndex($COMMUNITY_ID, $PAGE_ID, $ENTRADA_USER->getActiveGroup() === "student");

// Section for feedback and error messages
if (isset($_POST["success"])) {
    if ($_POST["success"] == "yes") {
        add_success("You have successfully moved files, links and folders.");
        echo display_success();
    } else {
        $errors_lvl_1 = json_decode($_POST["data_json"], true);
        if (array_key_exists("errors", $errors_lvl_1)) {
            if ($errors_lvl_1["errors"] == "Not Authorized") {
               add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

               echo display_error();
            } else {
                if (array_key_exists("errors", $errors_lvl_1)) {
                    foreach ($errors_lvl_1 as $errors_lvl_2) {
                        $errors_lvl_3 = json_decode($errors_lvl_2, true);
                        foreach ($errors_lvl_3 as $errors_lvl_4) {
                            switch ($errors_lvl_4["type"]) {
                                case "folder":
                                    $sql = "SELECT `folder_title`
                                            FROM `community_shares`
                                            WHERE `cshare_id` = ?";
                                    $folder = $db->GetAll($sql, array($errors_lvl_4["id"]));
                                    
                                    add_error("Error moving Folder ID: <strong>" . html_encode($errors_lvl_4["id"]) . "</strong> Title: <strong>" . html_encode($folder[0]["folder_title"]) ."</strong>");
                                break;
                                case "file":
                                    $sql = "SELECT `file_title`
                                            FROM `community_share_files`
                                            WHERE `csfile_id` = ?";
                                    $file = $db->GetAll($sql, array($errors_lvl_4["id"])); 

                                    add_error("Error moving File ID: <strong>" . html_encode($errors_lvl_4["id"]) . "</strong> Title: <strong>" . html_encode($file[0]["file_title"]) ."</strong>");
                                break;
                                case "link":
                                    $sql = "SELECT `link_title`
                                            FROM `community_share_links`
                                            WHERE `cslink_id` = ?";
                                    $link = $db->GetAll($sql, array($errors_lvl_4["id"]));

                                    add_error("Error moving Link ID: <strong>" . html_encode($errors_lvl_4["id"]) . "</strong> Title: <strong>" . html_encode($html[0]["link_title"]) ."</strong>");
                                break;
                                case "html":
                                    $sql = "SELECT `html_title`
                                            FROM `community_share_html`
                                            WHERE `cshtml_id` = ?";
                                    $html = $db->GetAll($sql, array($errors_lvl_4["id"]));

                                    add_error("Error moving HTML ID: <strong>" . html_encode($errors_lvl_4["id"]). "</strong> Title: <strong>" . html_encode($html[0]["html_title"]) ."</strong>");
                                break;
                            }
                        }
                    }

                    echo display_error();
                }
            }
        }
    }
}

if (isset($_POST["success_reorder"])) {
    if ($_POST["success_reorder"] == "yes") {
        add_success("You have successfully updated the folder order.");

        echo display_success();
    } else {
        $errors_lvl_1 = json_decode($_POST["data_json_reorder"], true);

        if (array_key_exists("errors", $errors_lvl_1)) {
            if ($errors_lvl_1["errors"] == "Not Authorized") {
               add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

               echo display_error();
            } else {
                 //errors on each folder reorder
                foreach ($errors_lvl_1 as $errors_lvl_2) {
                    $errors_lvl_3 = json_decode($errors_lvl_2, true);
                    foreach ($errors_lvl_3 as $errors_lvl_4) {
                        $sql = "SELECT `folder_title`
                                FROM `community_shares`
                                WHERE `cshare_id` = ?";
                        $folder = $db->GetAll($sql, array($errors_lvl_4["id"]));

                        add_error("Error updating order for Folder ID: <strong>" . html_encode($errors_lvl_4["id"]) . "</strong> Title: <strong>" . html_encode($folder[0]["folder_title"]) ."</strong>");
                    }
                }

                echo display_error();
            }
        }
    }
}

/**
 * Add the javascript for deleting forums.
 */
if (communities_module_access($COMMUNITY_ID, $MODULE_ID, "delete-folder")) {
	?>
	<script>
		function folderDelete(id) {
			Dialog.confirm('Do you really wish to remove the '+ $('folder-' + id + '-title').innerHTML +' folder from this community?<br /><br />If you confirm this action, you will be deactivating the folder and all files within it.',
				{
					id:				'requestDialog',
					width:			350,
					height:			125,
					title:			'Delete Confirmation',
					className:		'medtech',
					okLabel:		'Yes',
					cancelLabel:	'No',
					closable:		'true',
					buttonClass:	'btn',
					ok:				function(win) {
										window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=delete-folder&id='+id;
										return true;
									}
				}
			);
		}
	</script>
	<?php
}
?>
<!--[if IE 7]>
<style>
.subshare{display: block;}
</style>
<![endif]-->
<?php
$shares_hierarchy = Models_Community_Share::getSelectHierarchy($shares_index);
$shares_options = implode("", array_map(function($i) { return "<option value=\"{$i["id"]}\">".html_encode($i["title"])."</option>"; }, $shares_hierarchy));
$community_shares_select_folder = "<select id=\"share_id\" name=\"share_id\" style=\"width: 300px\"><option value=\"0\">Root Level</option>".$shares_options."</select>";
$community_shares_select_documents = "<select id=\"share_id\" name=\"share_id\" style=\"width: 300px\">".$shares_options."</select>";
?>
<script>
    function fileDelete(id) {
        Dialog.confirm('Do you really wish to remove the '+ $('file-' + id + '-title').innerHTML +' file?<br /><br />If you confirm this action, you will be deactivating this file and any comments.',
            {
                id:             'requestDialog',
                width:          350,
                height:         165,
                title:          'Delete Confirmation',
                className:      'medtech',
                okLabel:        'Yes',
                cancelLabel:    'No',
                closable:       'true',
                buttonClass:    'btn',
                ok:             function(win) {
                                    window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=delete-file&id='+id;
                                    return true;
                                }
            }
        );
    }

    function linkDelete(id) {
        Dialog.confirm('Do you really wish to remove the '+ $('link-' + id + '-title').innerHTML +' link?<br /><br />If you confirm this action, you will be deactivating this link and any comments.',
            {
                id:             'requestDialog',
                width:          350,
                height:         165,
                title:          'Delete Confirmation',
                className:      'medtech',
                okLabel:        'Yes',
                cancelLabel:    'No',
                closable:       'true',
                buttonClass:    'btn',
                ok:             function(win) {
                                    window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=delete-link&id='+id;
                                    return true;
                                }
            }
        );
    }   
    
    function htmlDelete(id) {
        Dialog.confirm('Do you really wish to remove the '+ $('html-' + id + '-title').innerHTML +' HTML document?<br /><br />If you confirm this action, you will be deactivating this HTML document and any comments.',
            {
                id:             'requestDialog',
                width:          350,
                height:         165,
                title:          'Delete Confirmation',
                className:      'medtech',
                okLabel:        'Yes',
                cancelLabel:    'No',
                closable:       'true',
                buttonClass:    'btn',
                ok:             function(win) {
                                    window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=delete-html&id='+id;
                                    return true;
                                }
            }
        );
    }
    <?php if ($community_shares_select_folder || $community_shares_select_documents) { ?>
    var community_shares_select_folder = '<?php echo $community_shares_select_folder; ?>';
    var community_shares_select_documents = '<?php echo $community_shares_select_documents; ?>';
    
    function getFolderIDFromDocID(id, type) {
        if (type == "folder") {
            var folder = jQuery("#" + type + "-" + id + "-title").parent().parent().parent().parent().parent().data("parent");
        } else {
            var folder = jQuery("#" + type + "-" + id + "-title").parent().parent().parent().parent().parent().parent().data("cshare-id");
        }
        return folder;
    }
    
    function getSub_folder_ids(id) {
        var share_folder_children = jQuery("#folder_id_" + id).parent().parent().find(".folder_container");
        //console.log(share_folder_children);
        var ids = new Array();
        jQuery(share_folder_children).each(function() {
            var share_id = jQuery(this).data("cshare-id");
            ids[ids.length] = share_id;
        });
        
        return ids;
    }
    
    function fileMove(id) {
        var folder_id = getFolderIDFromDocID(id, "file");
        var community_shares_select_clean_doc = community_shares_select_documents.replace('value="' + folder_id + '"', 'value="' + folder_id + '" disabled="disabled"');
        Dialog.confirm('Do you really wish to move the '+ $('file-' + id + '-title').innerHTML +' file?<br /><br />If you confirm this action, you will be moving the file and all comments to the selected folder.<br /><br />' + community_shares_select_clean_doc,
            {
                id:				'requestDialog',
                width:			350,
                height:			205,
                title:			'Move File',
                className:		'medtech',
                okLabel:		'Yes',
                cancelLabel:	'No',
                closable:		'true',
                buttonClass:	'btn',
                ok:				function(win) {
                                    window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=move-file&id='+id+'&share_id='+$F('share_id');
                                    return true;
                                }
            }
        );
    }
    function linkMove(id) {
        var folder_id = getFolderIDFromDocID(id, "link");
        var community_shares_select_clean_doc = community_shares_select_documents.replace('value="' + folder_id + '"', 'value="' + folder_id + '" disabled="disabled"');
        Dialog.confirm('Do you really wish to move the '+ $('link-' + id + '-title').innerHTML +' link?<br /><br />If you confirm this action, you will be moving the link to the selected folder.<br /><br />' + community_shares_select_clean_doc,
            {
                id:             'requestDialog',
                width:          350,
                height:         205,
                title:          'Move Link',
                className:      'medtech',
                okLabel:        'Yes',
                cancelLabel:    'No',
                closable:       'true',
                buttonClass:    'btn',
                ok:             function(win) {
                                    window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=move-link&id='+id+'&share_id='+$F('share_id');
                                    return true;
                                }
            }
        );
    }
    function htmlMove(id) {
        var folder_id = getFolderIDFromDocID(id, "html");
        var community_shares_select_clean_doc = community_shares_select_documents.replace('value="' + folder_id + '"', 'value="' + folder_id + '" disabled="disabled"');
        Dialog.confirm('Do you really wish to move the '+ $('html-' + id + '-title').innerHTML +' html?<br /><br />If you confirm this action, you will be moving the html document to the selected folder.<br /><br />' + community_shares_select_clean_doc,
            {
                id:             'requestDialog',
                width:          350,
                height:         205,
                title:          'Move HTML',
                className:      'medtech',
                okLabel:        'Yes',
                cancelLabel:    'No',
                closable:       'true',
                buttonClass:    'btn',
                ok:             function(win) {
                                    window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=move-html&id='+id+'&share_id='+$F('share_id');
                                    return true;
                                }
            }
        );
    }
    function folderMove(id) {
        var folder_id = getFolderIDFromDocID(id, "folder");
        var community_shares_select_clean = community_shares_select_folder.replace('value="' + id + '"', 'value="' + id + '" disabled="disabled"');
        if (folder_id != 0) {
            community_shares_select_clean = community_shares_select_clean.replace('value="' + folder_id + '"', 'value="' + folder_id + '" disabled="disabled"');
        }
        //hides all subfolders of the current folder as we don't want to let users move it into it's children as it would disaper.
        var subfolder_ids = getSub_folder_ids(id);
        var loop = 0;
        if (subfolder_ids.length != 0) {
            jQuery(subfolder_ids).each(function() {
                var id_sub = subfolder_ids[loop];
                loop++;
                community_shares_select_clean = community_shares_select_clean.replace('value="' + id_sub + '"', 'value="' + id_sub + '" disabled="disabled"');
            });
        }

        Dialog.confirm('Do you really wish to move the folder: <strong>'+ $('folder-' + id + '-title').innerHTML +'</strong>?<br /><br />If you confirm this action, you will be moving the folder to the selected folder.<br /><br />' + community_shares_select_clean,
            {
                id:             'requestDialog',
                width:          350,
                height:         205,
                title:          'Move Folder',
                className:      'medtech',
                okLabel:        'Yes',
                cancelLabel:    'No',
                closable:       'true',
                buttonClass:    'btn',
                ok:             function(win) {
                                    window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=move-folder&id='+id+'&share_id='+$F('share_id');
                                    return true;
                                }
            }
        );
    }
    <?php };?>
    
    //modifies the jquery drag to work with ie8
    jQuery.extend(jQuery.ui.draggable.prototype, (function (orig) {
      return {
        _mouseCapture: function (event) {
          var result = orig.call(this, event);
          if (result && jQuery.browser.msie) event.stopPropagation();
          return result;
        }
      };
    })(jQuery.ui.draggable.prototype["_mouseCapture"]));    
    
    
    //functions to open and close the folders
    function showFolder(target) {
        var folders = jQuery(target).next().find(".folderShare:first").children(".subshare");
        var files = jQuery(target).next().find(".folderShare:first").children(".fileUL");
        files.show("fast");
        folders.show("fast");
        jQuery(target).removeClass("icon-chevron-right").addClass("icon-chevron-down");
    }

    function hideFolder(target) {
        var folders = jQuery(target).next().find(".folderShare:first").children(".subshare");
        var files = jQuery(target).next().find(".folderShare:first").children(".fileUL");
        folders.hide("fast");
        files.hide("fast");
        jQuery(target).removeClass("icon-chevron-down").addClass("icon-chevron-right");
    }
    
    function expandFolders() {
        if (jQuery("#expandAllFolders").hasClass("closed")) {
            jQuery("#expandAllFolders").val("Collapse All Folders").removeClass("closed").addClass("open");
            showFolder(".point-right");
        } else {
            jQuery("#expandAllFolders").val("Expand All Folders").removeClass("open").addClass("closed");
            hideFolder(".point-right");
        }
        getIDsOpen();        
    }

    function getIDsOpen() {
        //gets the id's of the open folders
        var foldersOpen = jQuery(".icon-chevron-down").map(function() {
            return this.id.substr(9);
        }).get().join();
        if (foldersOpen == "") {
            foldersOpen = 0;
        }

        //insert/update code
        var url = "<?php echo ENTRADA_URL . "/api/community-shares-open.api.php";?>";
        var community_id = "<?php echo $COMMUNITY_ID?>";
        var page_id =  "<?php echo $PAGE_ID?>";
        var dataString = "community_id=" + community_id + "&foldersOpen=" + foldersOpen + "&page_id=" + page_id;
        jQuery.ajax({
            type: "POST",
            url: url,
            data: dataString,
            dataType: "json",
            success: function(data) {
            }
        });
    }
    
    //creates the js array of opened folders   
    function openFoldersSaved() {
        var url = "<?php echo ENTRADA_URL . "/api/community-shares-load-open.api.php";?>";
        var community_id = "<?php echo $COMMUNITY_ID?>";
        var page_id =  "<?php echo $PAGE_ID?>";
        var dataString = "community_id=" + community_id + "&page_id=" + page_id;
        jQuery.ajax({
            type: "POST",
            url: url,
            data: dataString,
            dataType: "json",
            async: false, 
            success: function(data) {
                openArray = data;
            }
        });
        return openArray;
    }
        
    jQuery(document).ready(function() {
        //hides folders opened by ie7 code
        hideFolder(".point-right");

        var openArray = openFoldersSaved();
        //loop through the folders to open them
        for (i=0;i<openArray.length;i++) {
            showFolder(".share_id_"+openArray[i]);
        }

        jQuery(".point-right").click(function() {
            if (jQuery("#expandAllFolders").is(":visible")) {
                if (jQuery(this).hasClass("icon-chevron-right")) {
                    showFolder(this);
                } else {
                    hideFolder(this);
                }
                getIDsOpen();
            }
        });

        jQuery(".folderIcon").click(function() {
            if (jQuery("#expandAllFolders").is(":visible")) {
                var clicked= ".share_id" + this.id.substr(9);
                if (jQuery(clicked).hasClass("icon-chevron-right")) {
                    showFolder(clicked);
                } else {
                    hideFolder(clicked);
                }
                getIDsOpen();
            }
        });

        jQuery("#expandAllFolders").click(function() {
            expandFolders();
        });
        
        var moving = false;
        var reordering = false;
        var sortableChanged = false;
        var orderChangedFolders = false;
        var orderChangedFiles = false;
        var reorderFolders = false
        jQuery("#savemove").hide();
        jQuery("#saveorder").hide();
        jQuery("li[data-parent=\"0\"]:first").hide();
        var movedFolders = {};
        var movedFiles = {};
       
        jQuery("#move").click(function() {
            
            if(moving == false) {
                
                //files
                jQuery("li.fileshare").prepend("<div class=\"dropzone\"></div>");
                jQuery("li.fileshare").prepend("<span class=\"handleFile\"></span>");
                
                //folders
                jQuery("#savemove").show();
                jQuery("li[data-parent=\"0\"]:first").show();
                var count = 0;
                var filesCount = 0;
                
                jQuery(".folder_container").prepend("<div class=\"dropzone\"></div>");
                jQuery(".point-right, .iconPlaceholder").hide();
                jQuery(".folder_container").prepend("<span class=\"handle\"></span>");
                
                jQuery("#move").attr("value", "Cancel Move");
                jQuery("#reorder").hide();
            
                jQuery(".share-edit-btn").hide();
            
                //folder drag
                jQuery(".folder_container").draggable({
                    handle: ".handle",
                    opacity: .8,
                    addClasses: false,
                    helper: "clone",
                    zIndex: 100
                });
                
                //file drag
                jQuery(".fileshare").draggable({
                    handle: ".handleFile",
                    opacity: .8,
                    addClasses: false,
                    helper: "clone",
                    zIndex: 100,
                    start: function() {
                        //disbale the root level dragable
                        jQuery("li[data-parent=\"0\"]:first").children("span.handle").hide();
                        jQuery("li[data-parent=\"0\"]:first").children("span.iconPlaceholder").show();
                    },
                    stop: function() {
                        //enables the root drop 
                        jQuery("li[data-parent=\"0\"]:first").children("span.handle").show();
                        jQuery("li[data-parent=\"0\"]:first").children("span.iconPlaceholder").hide();
                    }
                });     
                
                //disable the root level dragable
                jQuery("#parent_folder_id_0:first").draggable("destroy");
                
                //folder drop action
                jQuery(".handle").droppable({
                    accept: ".folder_container, .fileshare",
                    tolerance: "pointer",
                    hoverClass: "hoverClass",
                    drop: function(e, ui) {
                        //folder drop
                        if ($(ui.draggable).hasClass("folder_container")) {
                            var li = jQuery(this).parent();
                            var folder_sub_loop = jQuery(li).children("div.folder_sub_loop");

                            if (folder_sub_loop) {
                                //gets the folders invlovled
                                var folderMoved = jQuery(ui.draggable).children("i").attr("id");
                                var destinationFolder = jQuery(li).find("div.folder_sub_loop").children(".folderIcon").attr("id");
                                var originFolder = jQuery(ui.draggable).attr("id");

                                if (destinationFolder.substring(10) == 0) {
                                    jQuery("ul#sharetop").append(ui.draggable);
                                } else {
                                    //moves the folder to the new loacation
                                    var ulPath = jQuery(li).find("div.folder_sub_loop").children(".folderUL").children("li.folderShare:first");
                                    if (ulPath.children("ul:first").length) {
                                        ulPath.children("ul:first").append(ui.draggable);
                                    } else {
                                        ulPath.append("<ul class=\"shares subshare\"></ul>");
                                        ulPath.children("ul:first").append(ui.draggable);
                                        ulPath.children("ul:first").show();
                                    }
                                }
                                //sets the new parent folder on the moved folder
                                //substring folder_id_8
                                var folderSub = destinationFolder.substring(10);
                                jQuery(ui.draggable).attr("id", "parent_folder_id_"+folderSub);

                                //logs the move in an array
                                movedFolders[count] = {};
                                movedFolders[count]["folderMoved"] = folderMoved.substring(9);
                                movedFolders[count]["destinationFolder"] = destinationFolder.substring(10);
                                movedFolders[count]["originFolder"] = originFolder.substring(17);
                            }
                            //reset our background colours.
                            li.find(".handle,.dropzone").css({ backgroundColor: "", borderColor: "" });

                            count++;

                            if (movedFolders[0]) {
                                orderChangedFolders = true;
                            } else {
                                orderChangedFolders = false;
                            }             
                        }//end folder
                        
                        //file drop
                        if ($(ui.draggable).hasClass("fileshare")) {
                            var li = jQuery(this).parent(); //folder_sub_loop
                            var folder_sub_loop = jQuery(li).children("div.folder_sub_loop");
                            
                            //ui.draggable is our reference to the item that's been dragged.
                            if (folder_sub_loop) {
                                //gets the folders invlovled
                                var id_moved = jQuery(ui.draggable).children("a:first").attr("id");
                                var destinationFolder = jQuery(li).find("div.folder_sub_loop").children(".folderIcon").attr("id");
                                
                                if (destinationFolder.substring(10) == 0) {
                                    
                                } else {
                                    //moves the folder to the new loacation
                                    var ulPath = jQuery(li).find("div.folder_sub_loop:first").children(".folderUL").children("li.folderShare:first");

                                    if (ulPath.children("ul:first").length) {
                                        ulPath.children("ul:first").append(ui.draggable);
                                    } else {
                                        ulPath.append("<ul class=\"fileUL subshare\"></ul>");
                                        ulPath.children("ul:first").append(ui.draggable);
                                        ulPath.children("ul:first").show();
                                    }
                                }
                                //logs the move in an array
                                movedFiles[filesCount] = {};
                                movedFiles[filesCount]["type"] = id_moved.substring(0, 4);
                                movedFiles[filesCount]["id_moved"] = id_moved.substring(5, (id_moved.length - 6));
                                movedFiles[filesCount]["destinationFolder"] = destinationFolder.substring(10);
                            }
                            //reset our background colours.
                            li.find(".handleFile,.handle,.dropzone").css({ backgroundColor: "", borderColor: "" });

                            filesCount++;

                            if (movedFiles[0]) {
                                orderChangedFiles = true;
                            } else {
                                orderChangedFiles = false;
                            }
                        }
                    },
                    over: function() {
                        jQuery(this).filter(".handle").css({ backgroundColor: "#ccc" });
                        jQuery(this).filter(".dropzone").css({ borderColor: "#aaa" });
                    },
                    out: function() {
                        jQuery(this).filter(".handle").css({ backgroundColor: "" });
                        jQuery(this).filter(".dropzone").css({ borderColor: "" });
                    }
                });
                moving = true;
            } else {
                jQuery("#savemove").hide();
                jQuery("#reorder").show();
                jQuery(".share-edit-btn").show();
                //$("li[data-parent="0"]");
                jQuery("li[data-parent=\"0\"]:first").hide();
                jQuery(".handle").droppable("destroy");
                jQuery(".fileshare").draggable("destroy");
                jQuery(".folder_container").draggable("destroy");
                jQuery("#move").attr("value", "Move Files and Folders");
                jQuery(".folder_container .handle, .folder_container .dropzone, .fileshare .handleFile, .fileshare .dropzone").remove();
                //show all point-rights show all iconPlaceholders nexto .i-hidden
                jQuery(".point-right").show();
                jQuery(".i-hidden").prev("span").show();
                //jQuery(".point-right, .iconPlaceholder").hide();
                moving = false;
                if (orderChangedFiles == true || orderChangedFolders == true) {
                    url = "<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>";
                    window.location.replace(url);
                } else {                   
                    
                    
                }        
            
            }
        });
        
        //this button allows you to change the order of the main folders
        jQuery("#reorder").click(function() {
            if(moving == false) {
                //saves the open folders into a variable to reload once the sorting is done
                openArray = openFoldersSaved();
                
                //sets buttons
                jQuery("#reorder").attr("value", "Cancel Reorder");
                jQuery("#expandAllFolders").hide();
                jQuery("#move").hide();
                jQuery("#saveorder").show();
                hideFolder(".point-right");
                jQuery(".share-edit-btn").hide();
                //sets moving var
                moving = true;
                
                //switches the handles
                jQuery(".folder_container").prepend("<span class=\"handle\"></span>");
                jQuery(".point-right, .iconPlaceholder").hide();
                          
                jQuery("#sharetop").sortable({
                    items: "> li",
                    axis: "y",
                    scroll: true,
                    containment: "#folderTop",
                    change: function(event, ui) {
                        sortableChanged = true;
                    }
                });
                
            } else {           
                //reloads the page if the order was changed but not saved
                if (sortableChanged == true) {
                    url = "<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>";
                    window.location.replace(url);
                } else {
                    //reset buttons
                    jQuery("#reorder").attr("value", "Reorder Root Level Folders");
                    jQuery("#expandAllFolders").show();
                    jQuery("#move").show();
                    jQuery("#saveorder").hide();
                    jQuery(".share-edit-btn").show();
                    //sets moving to false
                    moving = false;

                    //switches the handles
                    jQuery(".point-right").show();
                    jQuery(".i-hidden").prev("span").show();

                    //reopens folders that were open before
                    //loop through the folders to open them
                    for (i=0;i<openArray.length;i++) {
                        showFolder(".share_id_"+openArray[i]);
                    }

                    jQuery(".folder_container .handle").remove();
                    jQuery("#sharetop").destroy();
                }
            }
        });
        
          
        jQuery("#savemove").click(function() {
            //insert/update code
            var url = "<?php echo ENTRADA_URL . "/api/community-shares-move-folders.api.php";?>";
            var community_id = "<?php echo $COMMUNITY_ID?>";
            var user_access = "<?php echo $USER_ACCESS?>";
            //var dataString = "community_id=" + community_id + "&movedFolders=" + movedFolders;
            jQuery.ajax({
                type: "POST",
                url: url,
                data: { community_id: community_id, user_access: user_access, movedFolders: movedFolders, movedFiles: movedFiles },
                dataType: "json",
                success: function(data) {
                    if (data["errors"]) {
                        var success = "no";
                        var data_json = JSON.stringify(data);
                        jQuery("#success").val(success);
                        jQuery("#data_json").val(data_json);
                        jQuery("#submit").submit();
                    } else {
                        var success = "yes";
                        jQuery("#success").val(success);
                        jQuery("#submit").submit();
                    }
                }
            });
        });        
        
        //submits the reorder
        jQuery("#saveorder").click(function() {
            var fieldOrder = jQuery("#sharetop").sortable("serialize");
            var community_id = "<?php echo $COMMUNITY_ID?>";
            var user_access = "<?php echo $USER_ACCESS?>";
            var url = "<?php echo ENTRADA_URL . "/api/community-shares-reorder-folders.api.php";?>";
            //var community_id = "<?php echo $COMMUNITY_ID?>";
            
            jQuery.ajax({
                type: "POST",
                url: url,
                data: { community_id: community_id, user_access: user_access, fieldOrder: fieldOrder },
                //data: fieldOrder,
                dataType: "json",
                success: function(data) {
                    if (data["errors"]) {
                        var success = "no";
                        var data_json = JSON.stringify(data);
                        jQuery("#success_reorder").val(success);
                        jQuery("#data_json_reorder").val(data_json);
                        jQuery("#submitReorder").submit();
                    } else {
                        var success = "yes";
                        jQuery("#success_reorder").val(success);
                        jQuery("#submitReorder").submit();
                    }
                }
            });            
            
        });
    });
</script>
<style>
.page-action li a {
    color:#FFF;
    font-weight:700;
}
.page-action li {
    display:inline;
    background: none;
    padding:0;
}
form {
    margin: 0;
}
div.content {
    overflow: visible;
}
</style>
<form id="submit" method="post" action="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL?>">
    <input type="hidden" name="success" id="success"/>
    <input type="hidden" name="data_json" id="data_json"/>
</form>
<form id="submitReorder" method="post" action="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL?>">
    <input type="hidden" name="success_reorder" id="success_reorder"/>
    <input type="hidden" name="data_json_reorder" id="data_json_reorder"/>
</form>
<div style="padding-top: 10px; clear: both" id="folderTop">
	<?php
	if (communities_module_access($COMMUNITY_ID, $MODULE_ID, "add-folder")) {
		?>
		<div style="float: right">
			<ul class="page-action">
				<li><a href="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=add-folder" class="btn btn-success">Add Shared Folder</a></li>
			</ul>
		</div>
		<div style="clear: both"></div>
		<?php
	}
	
    //checks the role of the user and sets hidden to true if they're not a faculty, staff, or medtech memeber
    //used to control access to files if they're marked hidden from students
    $group = $ENTRADA_USER->getActiveGroup();
    if ($group == "faculty" || $group == "staff"  || $group == "medtech") {
        $hidden = false;
    } else {
        $hidden = true;
    }
	if ($shares_index) {
		?>
        <ul id="sharetop" class="shares">
            <li class="folder_container" id="share_id_0" data-parent="0">
                <span class="iconPlaceholder"></span>
                <div class="folder_sub_loop">
                    <span id="folder_id_0" class="folderIcon folder-1"></span>
                    <ul class="folderUL">
                        <li class="folderShare">
                            <div>Root Level</div>
                        </li>
                    </ul>
                </div>
            </li>
            <div class="clear"></div>
            <?php
            foreach ($shares_index as $share) {
                echo Models_Community_Share::getIndexFolderHtml($share);
            }
            ?>
		</ul>
        <br/>
        <?php
        if ($COMMUNITY_ADMIN) {
            ?>
            <input type="button" class="btn" id="reorder" value="Reorder Root Level Folders" />
            <input type="button" class="btn" id="move" value="Move Files and Folders" />
            <input type="button" class="btn btn-primary" id="savemove" value="Save Move" />
            <input type="button" class="btn btn-primary" id="saveorder" value="Save Order" />
            <?php
        }
        ?>
        <input type="button" id="expandAllFolders" class="closed btn" value="Expand All Folders" /> 
        
		<?php
	} else {
		add_notice("There are currently no shared folders available in this community.<br /><br />".((communities_module_access($COMMUNITY_ID, $MODULE_ID, "add-folder")) ? "As a community adminstrator you can add shared folders by clicking <a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=add-folder\">Add Shared Folder</a>." : "Please check back later."));

		echo display_notice();
	}
	?>
</div>