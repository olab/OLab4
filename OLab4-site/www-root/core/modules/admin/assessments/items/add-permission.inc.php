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
 * The file that loads the form permissions add page when /admin/assessments/forms?section=add-permission is accessed.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
*/
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_ITEMS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
	add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    
    $SECTION_TEXT = $SUBMODULE_TEXT[$SECTION];
    
    if (isset($_GET["item_id"]) && $tmp_input = clean_input($_GET["item_id"], "int")) {
        $PROCESSED["item_id"] = $tmp_input;
    }
    
    $item = Models_Assessments_Item::fetchRowByID($PROCESSED["item_id"]);
    
    ?>
    <h1><?php echo $SECTION_TEXT["title"] ?></h1>
    <?php
    
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=edit-item&item_id=" . $PROCESSED["item_id"], "title" => $translate->_("Edit Item"));
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=" . $SECTION . "&item_id=" . $PROCESSED["item_id"], "title" => $SECTION_TEXT["breadcrumb"]["title"]);
    
    switch ($STEP) {
        case 2:
            $audience_types = array("organisation_id", "course_id", "proxy_id");
            
            if (isset($_POST["audience"]) && !empty($_POST["audience"])) {
                foreach ($_POST["audience"] as $audience_type => $members) {
                    $tmp_type = clean_input($audience_type, array("trim", "striptags"));
                    if ($tmp_type && in_array($tmp_type, $audience_types)) {
                        $PROCESSED["authors"][$tmp_type] = array();
                        foreach ($members as $member) {
                            $tmp_input = clean_input($member, "int");
                            if ($tmp_input) {
                                $PROCESSED["authors"][$tmp_type][] = $tmp_input;
                            }
                        }
                    }
                }
            }
            
            if (isset($PROCESSED) && !empty($PROCESSED["authors"])) {
                $added = 0;
                foreach ($PROCESSED["authors"] as $author_type => $authors) {
                    foreach ($authors as $author_id) {
                        $a = Models_Assessments_Form_Author::fetchRowByFormIDAuthorIDAuthorType($PROCESSED["item_id"], $author_id, $author_type);
                        if ($a) {
                            if ($a->getDeletedDate()) {
                                if ($a->fromArray(array("deleted_date" => NULL))->update()) {
                                    $added++;
                                } else {
                                    $ERROR++;
                                }
                            } else {
                                application_log("notice", "Item author [".$a->getID()."] is already an active author. API should not have returned this author as an option.");
                            }
                        } else {
                            $a = new Models_Assessments_Item_Author(
                                array(
                                    "item_id"       => $PROCESSED["item_id"],
                                    "author_type"   => $author_type,
                                    "author_id"     => $author_id,
                                    "updated_date"  => time(),
                                    "updated_by"    => $ENTRADA_USER->getActiveID()
                                )
                            );
                            if (!$a->insert()) {
                                $ERROR++;
                            } else {
                                $added++;
                            }
                        }
                    }
                }
            }
            
            if ($added <= 0) {
                add_error($translate->_("A problem occurred while attempting to add authors to the item."));
                $STEP = 1;
            } else {
                add_success(sprintf($translate->_("Successfully added <strong>%d</strong> item authors."), $added));
                $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."/".$SUBMODULE."?section=edit-item&id=".$PROCESSED["item_id"]."\\'', 5000)";
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
        break;
        case 1 :
            if ($ERROR) {
                echo display_error();
            }
            if ($SUCCESS) {
                echo display_success();
            }
        
            if ($PROCESSED["item_id"]) {

                $item = Models_Assessments_Item::fetchRowByID($PROCESSED["item_id"]);
                if ($item) {
                    ?>
                    <script type="text/javascript">
                        jQuery(function($) {

                            var ENTRADA_URL = "<?php echo ENTRADA_URL; ?>";

                            $.fn.audienceSelector = function(options) {
                                var self = $(this);
                                var interval;

                                var settings = $.extend({
                                    "filter"        : "",
                                    "filter_type"   : "proxy_id",
                                    "interval"      : 500,
                                    "api_url"       : ENTRADA_URL + "/api/item_bank.api.php",
                                    "min_chars"     : 3,
                                    "no_results_text" : "<?php echo $translate->_("No results found"); ?>"
                                }, options);

                                self.parent().css("position", "relative");

                                self.on("keypress", function(e) {
                                    clearInterval(interval);
                                    interval = window.setInterval(fetchFilteredAudience, settings.interval);
                                });

                                self.on("focus", function(e) {
                                    if (self.val().length >= settings.min_chars) {
                                        clearInterval(interval);
                                        interval = window.setInterval(fetchFilteredAudience, settings.interval);
                                    }
                                });

                                self.on("blur", function(e) {
                                    clearInterval(interval);
                                    $(".audience-selector-container").remove();
                                });

                                if (settings.filter.length > 0) {
                                    $(settings.filter).on("change", function(e) {
                                        settings.filter_type = $(this).val();
                                    });
                                }

                                function fetchFilteredAudience() {
                                    var search_value = self.val();
                                    if (search_value.length >= settings.min_chars) {
                                        $.ajax({
                                            url: settings.api_url,
                                            type: "GET",
                                            data: {
                                                "method"        : "get-filtered-audience",
                                                "search_value"  : search_value,
                                                "filter_type"   : settings.filter_type
                                            }, 
                                            success : function(data) {
                                                var jsonResponse = JSON.parse(data);
                                                if (jsonResponse.data.length) {
                                                    buildDom(jsonResponse);
                                                }
                                            }
                                        });
                                    }
                                    clearInterval(interval);
                                }

                                function createListItem(v) {
                                    var item = $(document.createElement("li"))
                                        .addClass("audience-selector-item")
                                        .data("author-id", v.id).html(v.fullname + "<br /><span class=\"content-small\">" + v.email + "</span>")
                                        .on("mousedown", function(e) {
                                            addAudienceMember(v);
                                            self.val("");
                                        });
                                    return item;
                                }

                                function buildDom(jsonResponse) {
                                    if ($(".audience-selector-container").length <= 0) {
                                        var selector_container = $(document.createElement("div")).addClass("audience-selector-container");
                                    } else {
                                        var selector_container = $(".audience-selector-container");
                                        selector_container.empty();
                                    }

                                    var selector_list = $(document.createElement("ul")).addClass("audience-selector-list");
                                    if (jsonResponse.results > 0) {
                                        $(jsonResponse.data).each(function(i, v) {
                                            if ($("input[data-member-id="+v.id+"]").length <= 0) {
                                                selector_list.append(
                                                    createListItem(v)
                                                );
                                            }
                                        });
                                    }
                                    
                                    if (selector_list.children().length <= 0) {
                                        var dummy = new Object({
                                            "id" : "",
                                            "fullname" : settings.no_results_text,
                                            "email" : ""
                                        });
                                        selector_list.append(
                                            createListItem(dummy)
                                        );
                                    }

                                    selector_container.append(selector_list);

                                    if ($(".audience-selector-container").length <= 0) {
                                        selector_container.insertAfter(self);
                                    }
                                }

                                function addAudienceMember(member) {
                                    $(".audience-selector-container").remove();
                                    var member_hidden_input = $(document.createElement("input")).attr({"type" : "hidden", "name" : "audience[" + settings.filter_type + "][]", "data-member-id" : member.id}).val(member.id);
                                    member_hidden_input.insertAfter(self);
                                    if ($("#added-member-list").length <= 0) {
                                        var member_list = $(document.createElement("ul")).addClass("unstyled").attr({"id" : "added-member-list"});
                                    } else {
                                        var member_list = $("#added-member-list");
                                    }
                                    var member_trash = $(document.createElement("a")).attr({"href" : "#"}).on("click", function() {
                                        $(this).parent().remove();
                                        $("input[data-member-id="+member.id+"]").remove();
                                    }).html("<i class=\"icon-trash\"></i>");
                                    var member_list_line = $(document.createElement("li")).append(member_trash, member.fullname);
                                    member_list.append(member_list_line);
                                    if ($("#added-member-list").length <= 0) {
                                        member_list.insertAfter(self);
                                    }
                                }

                            };

                            $("#contact-selector").audienceSelector({"filter" : "#contact-type"});
                        });
                    </script>
                    <style type="text/css">
                        .audience-selector-container {
                            position:absolute;
                            z-index:10;
                            background:#fff;
                            width:218px;
                            height:200px;
                            border:1px solid #ccc;
                            border-top-width: 0px;
                            border-radius: 0px 0px 5px 5px;
                            box-shadow: 0 1px 1px rgba(0, 0, 0, 0.075) inset;
                            overflow-x:hidden;
                            overflow-y:scroll;
                        }
                        .audience-selector-list {
                            list-style:none;
                            margin:0px;
                            padding:0px;
                        }
                        .audience-selector-list .audience-selector-item {
                            padding:4px 6px;
                        }
                        .audience-selector-list .audience-selector-item:hover {
                            cursor:pointer;
                            background:#eee;
                        }
                    </style>
                    <form action="<?php echo ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=" . $SECTION; ?>&id=<?php echo $PROCESSED["item_id"]; ?>" class="form-horizontal" method="POST">
                        <input type="hidden" name="item_id" value="<?php echo $PROCESSED["item_id"]; ?>" />
                        <input type="hidden" name="step" value="2" />
                        <div class="control-group">
                            <label class="control-label" for="form-type"><?php echo $SECTION_TEXT["labels"]["label_contact_type"]; ?></label>
                            <div class="controls">
                                <select name="contact_type" id="contact-type">
                                    <?php foreach ($SECTION_TEXT["contact_types"] as $contact_type => $contact_type_name) { ?>
                                    <option value="<?php echo $contact_type; ?>"><?php echo $contact_type_name; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label" for="contacts"><?php echo $SECTION_TEXT["labels"]["label_contact_name"]; ?></label>
                            <div class="controls">
                                <input type="text" name="contact_select" id="contact-selector" />
                            </div>
                        </div>
                        <div class="row-fluid">
                            <a href="<?php echo ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=" . $SECTION . "&id=" . $PROCESSED["item_id"]; ?>" class="btn btn-default"><?php echo $translate->_("Back"); ?></a>
                            <input type="submit" class="btn btn-primary pull-right" value="<?php echo $translate->_("Submit"); ?>" />
                        </div>
                    </form>
                    <?php
                } else {
                    echo display_error($translate->_("The item could not be found. It may have been deleted from the system."));
                }
            } else {
                echo display_error($translate->_("To add permissions a valid item identifier is required."));
            }
            
        break;
    }
}