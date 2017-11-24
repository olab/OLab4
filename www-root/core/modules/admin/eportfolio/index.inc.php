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
 * ePortfolio public index
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @author Developer: Josh Dillon <josh.dillon@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
 */

if (!defined("PARENT_INCLUDED")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("eportfolio", "read")) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/AutoCompleteList.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	$HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = '".ENTRADA_URL."'; var PROXY_ID = '".$ENTRADA_USER->getProxyId()."'; var FLAGGED = false; var ADVISOR = false; var STUDENT_PROXY_ID;</script>";
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/eportfolio.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	load_rte("minimal");
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/ckeditor/adapters/jquery.js\"></script>\n";
	?>
	<h1>Entrada ePortfolio</h1>
	<?php
	
	$is_advisor = false;
	if ($ENTRADA_USER->getRole() != "admin" && $ENTRADA_USER->getRole() != "director") {
		$is_advisor = true;
	}
	$eportfolios = Models_Eportfolio::fetchAll($ENTRADA_USER->getActiveOrganisation(), $is_advisor ? $ENTRADA_USER->GetID() : NULL);
	?>
    <style rel="stylesheet">
        #artifacts .well:not(.no-folders){
            padding: 0px;
        }
        .artifacts li strong {
            margin-top: 5px;
            display: block;
        }
        .artifacts li {
            padding: 20px;
            border-bottom: 1px solid #ccc;
        }
        .artifacts li:last-child {
            border-bottom: none;
        }
    </style>
	<script type="text/javascript">
		<?php if ($is_advisor == true) { ?>
		ADVISOR = true;
		<?php } ?>
		var api_url = ENTRADA_URL + "/api/eportfolio.api.php";
		function getPortfolio(portfolio_id) {
			jQuery.ajax({
				url: api_url,
				data: "method=get-portfolio-members&portfolio_id=" + portfolio_id + (FLAGGED === true ? "&flagged=true" : "") + (ADVISOR == true ? "&proxy_id=" + PROXY_ID : ""),
				type: 'GET',
				success:function (data) {
					var jsonResponse = JSON.parse(data);
					if (jsonResponse.status == "success") {
						jQuery("#user-list").html("");
						var user_list = document.createElement("ul");
						var back_row = document.createElement("li");
						var back_btn = document.createElement("a");
						jQuery(back_btn).addClass("back");
						jQuery(back_row).append(back_btn);
						jQuery(user_list).append(back_row);
						jQuery(back_btn).html("<i class=\"icon-chevron-left\"></i> Back")
						for (var i=0; i < jsonResponse.data.length; i++) {
							var user_row = document.createElement("li");
							var user_link = document.createElement("a");
							jQuery(user_link).addClass("portfolio-user");
							jQuery(user_link).attr("data-proxy-id", jsonResponse.data[i].proxy_id).attr("data-portfolio-id", portfolio_id).attr("href", "#").html(jsonResponse.data[i].lastname + ", " + jsonResponse.data[i].firstname);
							jQuery(user_row).append(user_link);
							jQuery(user_list).append(user_row);
						}
						jQuery("#user-list").append(user_list);
					}
				}
			});
		}
		
		function getFolders(portfolio_id) {
			jQuery.ajax({
				url: api_url,
				data: "method=get-folders&portfolio_id=" + portfolio_id + (FLAGGED === true ? "&flagged=true&proxy_id="+STUDENT_PROXY_ID : ""),
				type: 'GET',
				success:function (data) {
					var jsonResponse = JSON.parse(data);
					if (jsonResponse.status == "success") {
						var folder_list = document.createElement("ul");
						jQuery(folder_list).attr("class", "folder-list");
						jQuery.each(jsonResponse.data, function(i, v) {
							var folder_row = document.createElement("li");
							var folder_link = document.createElement("a");
							jQuery(folder_link).addClass("portfolio-folder");
							jQuery(folder_link).attr("data-pfolder-id", v.pfolder_id).attr("href", "#").html(v.title);
							jQuery(folder_row).append(folder_link);
							jQuery(folder_list).append(folder_row);
						});
						jQuery("#user-portfolio").append(folder_list);
					} else {
                        $("#artifacts").html("<div class=\"no-folders well\"><strong>No folders in this portfolio</strong></div>");
                    }

				}
			});
		}
		
		function adminArtifactForm(btn) {
			artifactForm();
			
			/*
			var reviewers_control_group = document.createElement("div");
			jQuery(reviewers_control_group).addClass("control-group");
			var reviewers_label = document.createElement("label");
			jQuery(reviewers_label).addClass("control-label").html("Reviewers:").attr("for", "reviewers");
			jQuery(reviewers_control_group).append(reviewers_label);
			var reviewers_controls = document.createElement("div");
			jQuery(reviewers_controls).addClass("controls");
			var reviewers_input = document.createElement("input");
			jQuery(reviewers_input).attr("type", "text").attr("name", "reviewers[]").attr("id", "reviewers");
			jQuery(reviewers_controls).append(reviewers_input);
			jQuery(reviewers_control_group).append(reviewers_controls);
			*/

			var start_date_control_group = document.createElement("div");
			jQuery(start_date_control_group).addClass("control-group");
			var start_date_label = document.createElement("label");
			jQuery(start_date_label).addClass("control-label").html("Start:").attr("for", "start_date");
			jQuery(start_date_control_group).append(start_date_label);
			var start_date_controls = document.createElement("div");
			jQuery(start_date_controls).addClass("controls");
			var start_date_input_container = document.createElement("div");
			jQuery(start_date_input_container).addClass("input-prepend").html("<span class=\"add-on\"><i class=\"icon-calendar\"></i></span>");
			var start_date_input = document.createElement("input");
			jQuery(start_date_input).attr("type", "text").attr("name", "start_date").attr("id", "start_date").addClass("input-small");
			jQuery(start_date_input_container).append(start_date_input);
			jQuery(start_date_controls).append(start_date_input_container);
			jQuery(start_date_control_group).append(start_date_controls);

			var finish_date_control_group = document.createElement("div");
			jQuery(finish_date_control_group).addClass("control-group");
			var finish_date_label = document.createElement("label");
			jQuery(finish_date_label).addClass("control-label").html("Finish:").attr("for", "finish_date");
			jQuery(finish_date_control_group).append(finish_date_label);
			var finish_date_controls = document.createElement("div");
			jQuery(finish_date_controls).addClass("controls");
			var finish_date_input_container = document.createElement("div");
			jQuery(finish_date_input_container).addClass("input-prepend").html("<span class=\"add-on\"><i class=\"icon-calendar\"></i></span>");
			var finish_date_input = document.createElement("input");
			jQuery(finish_date_input).attr("type", "text").attr("name", "finish_date").attr("id", "finish_date").addClass("input-small");
			jQuery(finish_date_input_container).append(finish_date_input);
			jQuery(finish_date_controls).append(finish_date_input_container);
			jQuery(finish_date_control_group).append(finish_date_controls);

			var enable_commenting_control_group = document.createElement("div");
			jQuery(enable_commenting_control_group).addClass("control-group");
			var enable_commenting_label = document.createElement("label");
			jQuery(enable_commenting_label).addClass("control-label").html("Allow commenting:").attr("for", "allow_commenting");
			jQuery(enable_commenting_control_group).append(enable_commenting_label);
			var enable_commenting_controls = document.createElement("div");
			jQuery(enable_commenting_controls).addClass("controls");
			var enable_commenting_input = document.createElement("input");
			jQuery(enable_commenting_input).attr("type", "checkbox").attr({"name": "allow_commenting", "id": "allow_commenting"});
			jQuery(enable_commenting_controls).append(enable_commenting_input);
			jQuery(enable_commenting_control_group).append(enable_commenting_controls);

			var pfolder_id_input = document.createElement("input");
			jQuery(pfolder_id_input).attr({"type" : "hidden", "name" : "pfolder_id", "value" : btn.data("pfolder-id")});

			jQuery("#portfolio-form").append(pfolder_id_input).append("<input type=\"hidden\" name=\"method\" value=\"create-artifact\" />").append(start_date_control_group).append(finish_date_control_group).append(enable_commenting_control_group).attr("action", api_url);
			jQuery("#start_date").datepicker({ dateFormat: "yy-mm-dd" });
			jQuery("#finish_date").datepicker({ dateFormat: "yy-mm-dd" });
//			jQuery("#artifact-description").ckeditor()
		}
		
		jQuery(function($) {
			
			$("#portfolio-list, #breadcrumb").on("click", ".portfolio-item", function (e) {
				portfolio_id = $(this).data("id");
				getPortfolio(portfolio_id);
				location.hash = $(this).attr("data-id");
				$("#breadcrumb").html("");
				$("#user-portfolio").html("");
				var span = document.createElement("span");
				var breadcrumb_link = $(this).clone();
				$(span).append(breadcrumb_link);
				$("#breadcrumb").append(span);
				
				jQuery("#user-portfolio").html("<div class=\"title-container row-fluid\"><h1 class=\"pull-left\">"+$(breadcrumb_link).html()+"</h1></div>");
				display_notice(["Select a learner from the menu on the left to review their portfolio."], $("#user-portfolio"), "append");

				e.preventDefault();
			});
			$("#portfolio-container, #breadcrumb").on("click", ".portfolio-user", function(e) {
				$(".portfolio-user").removeClass("active");
				$(this).addClass("active");
				STUDENT_PROXY_ID = $(this).data("proxy-id");
				portfolio_id = $(this).data("portfolio-id");
				
				$("#breadcrumb .portfolio-user").parent().remove();
				$("#breadcrumb .portfolio-folder").parent().remove();
				var span = document.createElement("span");
				var breadcrumb_link = $(this).clone();
				$(span).append(" / ").append(breadcrumb_link);
				$("#breadcrumb").append(span);

				jQuery("#user-portfolio").html("<div class=\"title-container row-fluid\"><h1 class=\"pull-left\">"+$(breadcrumb_link).html()+"</h1></div>");

				getFolders(portfolio_id);
				
				e.preventDefault();
			});
			$("#portfolio-container").on("click", ".back", function(e) {
				if ($("#breadcrumb span").length > 1) {
					$("#breadcrumb span:eq("+ ($("#breadcrumb span").length - 2) + ")").children("a").click();
				}
			});
			$("#portfolio-container, #breadcrumb").on("click", ".portfolio-folder", function(e) {
				
				var group_container = $(document.createElement("div"));
				group_container.addClass("btn-group space-above pull-right");
				group_container.append("<a class=\"btn dropdown-toggle\" data-toggle=\"dropdown\" href=\"#\">Folder<span class=\"caret\"></span></a>");
				var folder_list = $(".folder-list").clone();
				folder_list.addClass("dropdown-menu");
				group_container.append(folder_list);
				
				$("#user-portfolio").html("");
				
				$("#breadcrumb .portfolio-folder").parent().remove();
				var span = document.createElement("span");
				var breadcrumb_link = $(this).clone();
				$(span).append(" / ").append(breadcrumb_link);
				$("#breadcrumb").append(span);
				$("#user-portfolio").append("<div class=\"title-container row-fluid\"><h1 class=\"pull-left\">" + $(breadcrumb_link).html() + "</h1></div>");
				
				$(".title-container").append(group_container);
				
				var pfolder_id = $(this).data("pfolder-id");
				var proxy_id = STUDENT_PROXY_ID;
				$.ajax({
					url: api_url,
					data: "method=get-folder-artifacts&pfolder_id=" + pfolder_id + "&proxy_id=" + proxy_id,
					type: 'GET',
					success:function (data) {
						var jsonResponse = JSON.parse(data);
						var artifact_list = document.createElement("ul");
						
						if (typeof jsonResponse.data != "string") {
							$.each(jsonResponse.data, function(i, v) {
								var artifact_row = document.createElement("li");
								var artifact_title = document.createElement("h2");
								var pfartifact_id = v.pfartifact_id;
								$(artifact_title).html(v.title);
								var entries = document.createElement("ul");
								$.ajax({
									url : api_url,
									data : "method=get-artifact-entries&pfartifact_id=" + pfartifact_id + "&proxy_id=" + proxy_id,
									type : 'GET',
									success : function (data) {
										var entryJsonResponse = JSON.parse(data);
										if (typeof entryJsonResponse.data != "string") {
											$.each(entryJsonResponse.data, function(i, v) {
												var entry_row = document.createElement("li");
												$(entry_row).addClass("well entry-row entry-"+v.entry.pentry_id);
												if (typeof v.entry._edata != 'undefined') {
													if (typeof v.entry._edata.title != 'undefined' && v.entry._edata.description.title > 0) {
														$(entry_row).append("<h3>" + v.entry._edata.title + "</h3>");
													}
													if (typeof v.entry._edata.filename != 'undefined' && v.entry._edata.filename.length > 0) {
														var entry_link = document.createElement("a");
														$(entry_link).attr("href", ENTRADA_URL + "/serve-eportfolio-entry.php?entry_id=" + v.entry.pentry_id).html(v.entry._edata.filename);
														$(entry_row).append(entry_link);
													}
													if (typeof v.entry._edata.description != 'undefined' && v.entry._edata.description.length > 0) {
														$(entry_row).append("<div>" + v.entry._edata.description + "</div>");
													}
													
													if (typeof v.comments != 'undefined') {
														var comment_container = document.createElement("div");
														$(comment_container).addClass("comments well space-above").html("<strong>Comments:</strong><hr />").attr("id", "comments-"+v.entry.pentry_id);
														$.each(v.comments, function(c_i, comment) {
															$(comment_container).append("<div class=\"comment\">&ldquo;"+comment.comment+"&rdquo;<br /><span class=\"muted content-small\">"+comment.commentor+" - "+comment.submitted_date + "</span> " + ((ADVISOR == true && comment.proxy_id == PROXY_ID) || ADVISOR == false ? " - <i class=\"icon-trash comment-delete\" style=\"cursor:pointer;\" data-pecomment-id=\""+comment.pecomment_id+"\"></i>" : "") + "<hr /></div>");
														});
														$(entry_row).append(comment_container);
													}
													
													var entry_controls = document.createElement("div");
													$(entry_controls).addClass("row-fluid space-above controls");
													
													var flag_btn = document.createElement("button");
													$(flag_btn).addClass("btn btn-danger btn-mini pull-right add-flag space-right" + (v.entry.flag == 1 ? " flagged" : "")).attr("data-pentry-id", v.entry.pentry_id).html("<i class=\"icon-flag icon-white\"></i> " + (v.entry.flag == 1 ? "Flagged" : "Flag"))
													
													var review_btn = document.createElement("button");
													$(review_btn).addClass("btn btn-mini pull-right add-review space-right" + (v.entry.reviewed_date > 0 ? " reviewed" : "")).attr("data-pentry-id", v.entry.pentry_id).html("<i class=\"icon-check\"></i> " + (v.entry.reviewed_date > 0 ? "Reviewed" : "Review"))
													
													var comment_btn = document.createElement("button");
													$(comment_btn).addClass("btn btn-success btn-mini pull-right add-comment").attr("data-pentry-id", v.entry.pentry_id).html("<i class=\"icon-plus-sign icon-white\"></i> Add Comment")
													
													$(entry_controls).append(comment_btn);
													$(entry_controls).append(flag_btn);
													$(entry_controls).append(review_btn);
													
													$(entry_row).append(entry_controls);
                                                    
                                                    if (v.entry.updated_date.length > 0) {
                                                        $(entry_row).append("<div class=\"content-small space-above\" style=\"text-align:right;\"><strong>Submitted: </strong>" + v.entry.updated_date + "</div>");
                                                    }
                                                    
													$(entries).append(entry_row);
												}
											});
										} else {
											$(entries).append("<div class=\"alert alert-block alert-notice\"><ul><li>" + entryJsonResponse.data + "</li></ul></div>");
										}
									}
								});

								$(artifact_row).append(artifact_title).append(entries);
								
								$(artifact_list).append(artifact_row);
							});
							$("#user-portfolio").append(artifact_list);
						} else {
							display_notice(["The folder you are attempting to view does not have any associated artifacts. Use the manage tab to add artifacts to the folder."], $("#user-portfolio"), "append");
						}
					}
				});
				e.preventDefault();
			});
			$("#flag-toggle button").on("click", function(e) {
				$("#flag-toggle button").removeClass("active");
				$(this).addClass("active");
				if ($(this).hasClass("flagged")) {
					FLAGGED = true;
				} else {
					FLAGGED = false;
				}
				
				if ($("#breadcrumb .portfolio-item").length > 0) {
					$("#breadcrumb .portfolio-item").click();
				}
				
				e.preventDefault();
			})
			$("#user-portfolio").on("click", ".add-comment", function(e) {

				$("#entry-modal .modal-body #modal-form").empty();

				$("#entry-modal .modal-header h3").html("Add Comment");
				$("#entry-modal .modal-footer .btn-primary").html("Save Comment");

				var comment_row = document.createElement("div");
				$(comment_row).addClass("control-group");
				
				var comment_label = document.createElement("label");
				$(comment_label).addClass("control-label form-required").attr("for", "entry-comment").html("Comment");
				
				$(comment_row).append(comment_label);
				
				var comment_box_container = document.createElement("div");
				$(comment_box_container).addClass("controls");
				var comment_box = document.createElement("textarea");
				$(comment_box).attr("id", "entry-comment").attr("name", "entry-comment");
				$(comment_box).ckeditor();
				$(comment_box_container).append(comment_box);
				$(comment_row).append(comment_box_container);
				
				$("#entry-modal .modal-body #modal-form").append(comment_row).append("<input type=\"hidden\" name=\"pentry_id\" value=\""+$(this).data("pentry-id")+"\" />");
				$("#entry-modal").modal("show");
				
				e.preventDefault();
			});
			$("#modal-form").on("submit", function(e) {
				var form = $(this);
				
				$.ajax({
				url : api_url,
					type : "POST",
					data : "method=add-pentry-comment&" + form.serialize(),
					success: function(data) {
						var jsonResponse = JSON.parse(data);
						if (jsonResponse.status == "success") {
							var comment = "&ldquo;"+jsonResponse.data.comment+"&rdquo;<br /><span class=\"muted content-small\">" + jsonResponse.data.commentor + " - "+jsonResponse.data.submitted_date+" - <i class=\"icon-trash comment-delete\" style=\"cursor:pointer;\" data-pecomment-id=\""+jsonResponse.data.pecomment_id+"\"></i></span><hr />";
							if ($("#comments-"+jsonResponse.data.pentry_id).length > 0) {
								$("#comments-"+jsonResponse.data.pentry_id).append(comment);
							} else {
								var comment_container = document.createElement("div");
								$(comment_container).addClass("comments well space-above").html("<strong>Comments:</strong><hr />").attr("id", "comments-"+jsonResponse.data.pentry_id);
								$(".entry-"+jsonResponse.data.pentry_id+" .controls").prepend(comment_container);
								$("#comments-"+jsonResponse.data.pentry_id).append(comment);
							}
						}
					}
				});

				$("#entry-modal").modal("hide");

				e.preventDefault();
			});
			$("#entry-modal .modal-footer .btn-primary").on("click", function(e) {
				$("#modal-form").submit();
				e.preventDefault();
			});
			$("#user-portfolio").on("click", ".add-flag", function(e) {
				var btn = $(this);
				var action = "flag";
				if (btn.hasClass("flagged")) {
					action = "unflag";
				}
				$.ajax({
				url : api_url,
					type : "POST",
					data : "method=pentry-flag&action="+action+"&pentry_id=" + btn.data("pentry-id"),
					success: function(data) {
						var jsonResponse = JSON.parse(data);
						if (jsonResponse.data.flag == 1) {
							btn.addClass("flagged").html("<i class=\"icon-flag icon-white\"></i> Flagged");
						} else {
							btn.removeClass("flagged").html("<i class=\"icon-flag icon-white\"></i> Flag");
						}
					}
				});
				e.preventDefault();
			});
			$("#user-portfolio").on("click", ".add-review", function(e) {
				var btn = $(this);
				var action = "review";
				if (btn.hasClass("reviewed")) {
					action = "unreview";
				}
				$.ajax({
				url : api_url,
					type : "POST",
					data : "method=pentry-review&action="+action+"&pentry_id=" + btn.data("pentry-id"),
					success: function(data) {
						var jsonResponse = JSON.parse(data);
						if (jsonResponse.data.reviewed_date > 0) {
							btn.addClass("reviewed").html("<i class=\"icon-check\"></i> Reviewed");
						} else {
							btn.removeClass("reviewed").html("<i class=\"icon-check\"></i> Review");
						}
					}
				});
				e.preventDefault();
			});
			$("#user-portfolio").on("click", ".comment-delete", function(e) {
				var btn = $(this);
				$.ajax({
				url : api_url,
					type : "POST",
					data : "method=delete-pentry-comment&pecomment_id=" + btn.data("pecomment-id"),
					success: function(data) {
						var jsonResponse = JSON.parse(data);
						if (jsonResponse.status == "success") {
							btn.closest(".comment").remove();
						}
					}
				});
				e.preventDefault();
			});
			
			$("#manage").on("click", ".portfolio-item", function(e) {

                var btn = $(this);
				$("#portfolio-actions").show().data("portfolio-id", btn.data("portfolio-id"));
				$("#manage .add-folder").data("id", btn.data("portfolio-id"));
				$("#manage-eportfolio-title").html(btn.html())
				$("#artifacts").empty();
				$.ajax({
					url : api_url,
					type : "GET",
					data : "method=get-folders&portfolio_id=" + btn.data("portfolio-id"),
					success: function(data) {
						var jsonResponse = JSON.parse(data);
						if (jsonResponse.status == "success") {
							$.each(jsonResponse.data, function(i, v) {
								
								var folder_container = document.createElement("div");
								var folder_title = document.createElement("h3");
								var folder_desc = document.createElement("p");
								$(folder_title).html(v.title);
								$(folder_title).append(" <a href=\"#manage-modal\" data-toggle=\"modal\" class=\"add-artifact\" data-pfolder-id=\""+v.pfolder_id+"\"><i class=\"icon-plus-sign\"></i></a> <a href=\"#manage-modal\" data-toggle=\"modal\" class=\"edit-folder\" data-pfolder-id=\""+v.pfolder_id+"\"><i class=\"icon-edit\"></i></a> <a href=\"#manage-modal\" data-toggle=\"modal\" class=\"delete-folder\" data-pfolder-id=\""+v.pfolder_id+"\"><i class=\"icon-trash\"></i></a>");
								$(folder_desc).html(v.description);
								
								var artifacts_container = document.createElement("div");
								$(artifacts_container).addClass("well").attr("data-pfolder-id", v.pfolder_id);
								var artifacts = document.createElement("ul");
								$(artifacts).addClass("artifacts");
								$.ajax({
									url : api_url,
										type : "GET",
										data : "method=get-folder-artifacts&pfolder_id=" + v.pfolder_id + "&proxy_id=0",
										async: false,
										success: function(data) {
											var artifactJsonResponse = JSON.parse(data);
											if (artifactJsonResponse.status == "success") {
												$.each(artifactJsonResponse.data, function(a_i, a_v) {
													$(artifacts).append("<li data-id=\""+a_v.pfartifact_id+"\"><div class=\"row-fluid space-below\"><div class=\"span9\"><strong>" + a_v.title + "</strong></div><div class=\"span3\"><div class=\"btn-group pull-right\"><a href=\"#manage-modal\" data-toggle=\"modal\" class=\"btn btn-small edit-artifact\" data-id=\""+a_v.pfartifact_id+"\"><i class=\"icon-edit\"></i></a> <a href=\"#manage-modal\" data-toggle=\"modal\" class=\"btn btn-small delete-artifact\" data-id=\""+a_v.pfartifact_id+"\"><i class=\"icon-trash\"></i></a></div></div></div><div class=\"row-fluid\">" + a_v.description + "</div></li>")
												});
											} else {
												$(artifacts).append("<li class=\"no-artifacts\"><strong>No artifacts in this folder</strong></li>");
											}
										}
								});
								$(artifacts_container).append(artifacts);
								$(folder_container).addClass("folder-container").attr("data-pfolder-id", v.pfolder_id).append(folder_title).append(folder_desc).append(artifacts_container);
								$("#artifacts").append(folder_container);
							});
						} else {
                            $("#artifacts").html("<div class=\"no-folders well\"><strong>No folders in this portfolio</strong></div>");
                        }
					}
				});
				e.preventDefault();
			});
			$("#manage").on("click", ".add-folder, .edit-folder", function(e) {

				var cloned_form = $("#portfolio-form").clone();
				$("#manage-modal .modal-body").empty().append(cloned_form);
				
				$("#manage-modal .modal-footer .save-btn").removeAttr("data-pfartifact-id").removeAttr("data-pfolder-id").addClass("btn-primary add-folder-modal").removeClass("btn-danger add-new-portfolio").html("Save");
				var btn = $(this);
				var folder_title = document.createElement("input");
				var folder_desc = document.createElement("textarea");
				var folder_allow_artifact = document.createElement("input");
				
				$(folder_title).attr({"name" : "title", "type" : "text"});
				$(folder_desc).attr({"name" : "description", "id" : "folderdesc"});
				$(folder_allow_artifact).attr({"name" : "allow_learner_artifacts", "type" : "checkbox", "value" : "1"});
				
				var title_row = document.createElement("div");
				$(title_row).addClass("control-group");
				var title_label = document.createElement("label");
				$(title_label).addClass("control-label form-required").attr("for", "entry-comment").html("Title");
				$(title_row).append(title_label);
				var title_input_container = document.createElement("div");
				$(title_input_container).addClass("controls");
				$(title_input_container).append(folder_title);
				$(title_row).append(title_input_container);
				$("#portfolio-form").append(title_row);
		
				var desc_row = document.createElement("div");
				$(desc_row).addClass("control-group");
				var desc_label = document.createElement("label");
				$(desc_label).addClass("control-label form-required").attr("for", "entry-comment").html("Description");
				$(desc_row).append(desc_label);
				var desc_input_container = document.createElement("div");
				$(desc_input_container).addClass("controls");
				$(desc_input_container).append(folder_desc);
				$(desc_row).append(desc_input_container);
				$("#portfolio-form").append(desc_row);
				
				var allow_artifact_row = document.createElement("div");
				$(allow_artifact_row).addClass("control-group");
				var allow_artifact_label = document.createElement("label");
				$(allow_artifact_label).addClass("control-label").attr("for", "entry-artifact").html("Allow Learner Artifacts");
				$(allow_artifact_row).append(allow_artifact_label);
				var allow_artifact_input_container = document.createElement("div");
				$(allow_artifact_input_container).addClass("controls");
				$(allow_artifact_input_container).append(folder_allow_artifact);
				$(allow_artifact_row).append(allow_artifact_input_container);
				$("#portfolio-form").append(allow_artifact_row);
				$("#portfolio-form").append("<input type=\"hidden\" name=\"" + (btn.hasClass("add-folder") ? "portfolio_id" : "pfolder_id") + "\" value=\""+(btn.hasClass("add-folder") ? btn.data("id") : btn.data("pfolder-id"))+"\" />");
				
				if (btn.hasClass("edit-folder")) {
                    $("#manage-modal .modal-header h3").html("Edit Folder");
					$("#portfolio-form").append("<input type=\"hidden\" name=\"method\" value=\"edit-folder\" />");
					$.ajax({
						url : api_url,
						type : "GET",
						data : "method=get-folder&pfolder_id=" + btn.data("pfolder-id"),
						success: function(data) {
							var jsonResponse = JSON.parse(data);
							if (jsonResponse.status == "success") {
								$("#manage input[name='title']").val(jsonResponse.data.title);
								$("#folderdesc").val(jsonResponse.data.description);
								if (jsonResponse.data.allow_learner_artifacts == 1) {
									$("#manage input[name='allow_learner_artifacts']").attr("checked", "checked");
								}
							}
						}
					});
				} else {
                    $("#manage-modal .modal-header h3").removeData("pfolder-id").html("Add Folder");
					$("#portfolio-form").append("<input type=\"hidden\" name=\"method\" value=\"create-folder\" />");
				}
			});
			$("#manage").on("click", ".add-artifact", function(e) {
                $("#manage-modal .modal-header h3").html("Add Artifact");
                $("#manage-modal .modal-footer .save-btn").removeClass("add-new-portfolio");
				$("#manage-modal .modal-footer .save-btn").addClass("btn-primary add-artifact-modal").removeClass("btn-danger").html("Save");
				var btn = $(this);
				$("#portfolio-form").empty()
				$("#display-error-box-modal").remove();
				adminArtifactForm(btn);
				$("#artifact-description").addClass("artifact-description-field");
				$(".artifact-description-field").ckeditor();
				e.preventDefault();
			});
			$("#manage").on("click", ".edit-artifact", function(e) {
				$("#manage-modal .modal-footer .save-btn").addClass("btn-primary edit-artifact-modal").removeClass("btn-danger add-artifact-modal").html("Save");
				var btn = $(this);
				$("#portfolio-form").empty()
				$("#display-error-box-modal").remove();
				adminArtifactForm(btn);
				
				$.ajax({
					url : api_url,
					type : "GET",
					data : "method=get-folder-artifact&pfartifact_id="+btn.data("id"),
					success: function(data) {
						var jsonResponse = JSON.parse(data);
						if (jsonResponse.status == "success") {
							$("#portfolio-form input[name='pfolder_id']").attr("value", jsonResponse.data.pfolder_id);
							$("#portfolio-form").append("<input type=\"hidden\" name=\"pfartifact_id\" value=\""+jsonResponse.data.pfartifact_id+ "\" />")
							$("#artifact-title").attr("value", jsonResponse.data.title);
							$("#artifact-description").addClass("artifact-description-"+jsonResponse.data.pfartifact_id).attr("value", jsonResponse.data.description);
							var start_date = new Date(jsonResponse.data.start_date * 1000);
							$("#start_date").attr("value", start_date.getFullYear() + "-" + (start_date.getMonth() <= 9 ? "0" : "") + (start_date.getMonth() + 1) + "-" +  (start_date.getDate() <= 9 ? "0" : "") + start_date.getDate());
							var finish_date = new Date(jsonResponse.data.finish_date * 1000);
							$("#finish_date").attr("value", finish_date.getFullYear() + "-" + (finish_date.getMonth() <= 9 ? "0" : "") + (finish_date.getMonth() + 1) + "-" +  (finish_date.getDate() <= 9 ? "0" : "") + finish_date.getDate());
							if (jsonResponse.data.allow_commenting == 1) {
								$("#allow_commenting").attr("checked", "checked");
							}
							$(".artifact-description-"+jsonResponse.data.pfartifact_id).ckeditor();
						}
					}
				})
				
				e.preventDefault();
			});
			$("#manage").on("click", ".delete-artifact, .delete-folder", function(e) {
				var btn = $(this);
				$("#portfolio-form").empty();
				$("#display-error-box-modal").remove();
				if (btn.hasClass("delete-artifact")) {
					$("#manage-modal .modal-header h3").html("Delete Artifact");
					var modal_btn = $("#manage-modal .modal-footer .save-btn");
                    modal_btn.removeAttr("data-pfartifact-id").removeAttr("data-pfolder-id");
					modal_btn.removeClass("btn-primary delete-folder").addClass("btn-danger delete-artifact-modal").html("Delete").attr("data-pfartifact-id", btn.data("id"));
					display_error(["<strong>Warning</strong>, you have clicked the delete artifact button. <br/><br /> Please confirm you wish to delete the artifact by clicking on the button below."], "#manage-modal .modal-body", "append");
				} else if (btn.hasClass("delete-folder")) {
					$("#manage-modal .modal-header h3").html("Delete Folder");
					var modal_btn = $("#manage-modal .modal-footer .save-btn");
					modal_btn.removeClass("btn-primary delete-portfolio-modal").addClass("btn-danger delete-folder").html("Delete").attr("data-pfolder-id", btn.data("pfolder-id"));
					display_error(["<strong>Warning</strong>, you have clicked the delete folder button. <br/><br /> Please confirm you wish to delete the folder by clicking on the button below. All artifacts will also be deleted."], "#manage-modal .modal-body", "append");
				}
			});
			$("#manage-modal .modal-footer").on("click", ".delete-artifact-modal, .delete-folder", function(e) {
				var btn = $(this);
				var method = "delete-artifact";
				var datatype = "pfartifact_id";
				var data = btn.data("pfartifact-id");
				if (typeof btn.data("pfolder-id") != "undefined") {
					method = "delete-folder";
					datatype = "pfolder_id";
					data = btn.data("pfolder-id");
					$(".folder-container[data-pfolder-id='"+data+"']").remove();
				}

				$.ajax({
					url : api_url,
					type : "POST",
					data : "method=" + method + "&" + datatype + "=" + data,
					success: function(data) {
						var jsonResponse = JSON.parse(data);
						if (jsonResponse.status == "success") {
							$("ul.artifacts li[data-id='" + btn.data("pfartifact-id") + "']").remove();
							if($("div[data-pfolder-id='"+jsonResponse.data.pfolder_id+"'] ul.artifacts").html() == ""){
                                $("div[data-pfolder-id='"+jsonResponse.data.pfolder_id+"'] ul.artifacts").append("<li class=\"no-artifacts\"><strong>No artifacts in this folder</strong></li>");
                            }
                            if($("#artifacts").html() == ""){
                                $("#artifacts").append("<div class=\"no-folders well\"><strong>No folders in this portfolio</strong></div>");
                            }
							$("#manage-modal").modal("hide");
						}
					}
				});
                e.preventDefault();
			});
			
			$("#manage-modal .modal-footer").on("click", ".add-folder-modal", function(e) {
				var btn = $(this);
				$.ajax({
					url : api_url,
					type : "POST",
					data : $(".admin-portfolio-form").serialize(),
					success: function(data) {
						var jsonResponse = JSON.parse(data);
						if (jsonResponse.status == "success") {
                            if($(".admin-portfolio-form input[name='pfolder_id']").val() == jsonResponse.data.pfolder_id) {
                                $(".folder-container[data-pfolder-id=\""+jsonResponse.data.pfolder_id+"\"] h3").html(jsonResponse.data.title).append(" <a class=\"add-artifact\" data-pfolder-id=\""+jsonResponse.data.pfolder_id+"\" data-toggle=\"modal\" href=\"#manage-modal\"><i class=\"icon-plus-sign\"></i></a> <a class=\"edit-folder\" data-pfolder-id=\""+jsonResponse.data.pfolder_id+"\" data-toggle=\"modal\" href=\"#manage-modal\"><i class=\"icon-edit\"></i></a> <a class=\"delete-folder\" data-pfolder-id=\""+jsonResponse.data.pfolder_id+"\" data-toggle=\"modal\" href=\"#manage-modal\"><i class=\"icon-trash\"></i></a>");;
                                $(".folder-container[data-pfolder-id=\""+jsonResponse.data.pfolder_id+"\"] p").html(jsonResponse.data.description);
                            } else {
                                var folder_row = document.createElement("div");
                                $(folder_row).data("pfolder-id",jsonResponse.data.pfolder_id);
                                var folder_title = document.createElement("h3");
                                $(folder_title).append(jsonResponse.data.title + " <a class=\"add-artifact\" data-pfolder-id=\""+jsonResponse.data.pfolder_id+"\" data-toggle=\"modal\" href=\"#manage-modal\"><i class=\"icon-plus-sign\"></i></a> <a class=\"edit-folder\" data-pfolder-id=\""+jsonResponse.data.pfolder_id+"\" data-toggle=\"modal\" href=\"#manage-modal\"><i class=\"icon-edit\"></i></a> <a class=\"delete-folder\" data-pfolder-id=\""+jsonResponse.data.pfolder_id+"\" data-toggle=\"modal\" href=\"#manage-modal\"><i class=\"icon-trash\"></i></a>");
                                var folder_desc = document.createElement("p");
                                $(folder_desc).html(jsonResponse.data.description);
                                var artifact_container = document.createElement("div");
                                $(artifact_container).attr("data-pfolder-id",jsonResponse.data.pfolder_id);
                                $(artifact_container).addClass("well").append("<ul class=\"artifacts\"><li class=\"no-artifacts\"><strong>No artifacts in this folder</strong></li></ul>");
                                $(folder_row).addClass("folder-container").attr("data-pfolder-id", jsonResponse.data.pfolder_id).append(folder_title).append(folder_desc).append(artifact_container);
                                $("#artifacts").append(folder_row);
                            }
						}

                        if($("#artifacts").html() == ""){
                            $("#artifacts").append("<div class=\"no-folders well\"><strong>No folders in this portfolio</strong></div>");
                        } else {
                            $("#artifacts .no-folders").remove();
                        }

						$("#manage-modal").modal("hide");
					}
				});
				e.preventDefault();
			});
			$("#manage-modal .modal-footer").on("click", ".add-artifact-modal", function(e) {
				$.ajax({
					url : api_url,
					type : "POST",
					data : $(".admin-portfolio-form").serialize(),
					success: function(data) {
						var jsonResponse = JSON.parse(data);
						if (jsonResponse.status == "success") {
                            $("div[data-pfolder-id='" + jsonResponse.data.pfolder_id + "'] ul li.no-artifacts").remove();
							if ($("div[data-pfolder-id='" + jsonResponse.data.pfolder_id + "'] ul li[data-id='"+jsonResponse.data.pfartifact_id+"']").length > 0) {
								$("div[data-pfolder-id='" + jsonResponse.data.pfolder_id + "'] ul li[data-id='"+jsonResponse.data.pfartifact_id+"']").html("<div class=\"row-fluid space-below\"><div class=\"span9\"><strong>" + jsonResponse.data.title + "</strong></div><div class=\"span3\"><div class=\"btn-group pull-right\"><a href=\"#manage-modal\" data-toggle=\"modal\" class=\"btn btn-small edit-artifact\" data-id=\""+jsonResponse.data.pfartifact_id+"\"><i class=\"icon-edit\"></i></a> <a href=\"#manage-modal\" data-toggle=\"modal\" class=\"btn btn-small delete-artifact\" data-id=\""+jsonResponse.data.pfartifact_id+"\"><i class=\"icon-trash\"></i></a></div></div></div><div class=\"row-fluid\">" + jsonResponse.data.description + "</div>");
							} else {
								$("div[data-pfolder-id='" + jsonResponse.data.pfolder_id + "'] ul").append("<li data-id=\""+jsonResponse.data.pfartifact_id+"\"><div class=\"row-fluid space-below\"><div class=\"span9\"><strong>" + jsonResponse.data.title + "</strong></div><div class=\"span3\"><div class=\"btn-group pull-right\"><a href=\"#manage-modal\" data-toggle=\"modal\" class=\"btn btn-small edit-artifact\" data-id=\""+jsonResponse.data.pfartifact_id+"\"><i class=\"icon-edit\"></i></a> <a href=\"#manage-modal\" data-toggle=\"modal\" class=\"btn btn-small delete-artifact\" data-id=\""+jsonResponse.data.pfartifact_id+"\"><i class=\"icon-trash\"></i></a></div></div></div><div class=\"row-fluid\">" + jsonResponse.data.description + "</div></li>");
							}
							$("#manage-modal").modal("hide");
						}
					}
				});
			});
			$("#manage-modal .modal-footer").on("click", ".edit-artifact-modal", function(e) {
				$.ajax({
					url : api_url,
					type: "POST",
					data : $(".admin-portfolio-form").serialize(),
					success: function(data) {
						var jsonResponse = JSON.parse(data);
						if (jsonResponse.status == "success") {
                            $("div[data-pfolder-id='" + jsonResponse.data.pfolder_id + "'] ul li[data-id='"+jsonResponse.data.pfartifact_id+"']").html("<div class=\"row-fluid space-below\"><div class=\"span9\"><strong>" + jsonResponse.data.title + "</strong></div><div class=\"span3\"><div class=\"btn-group pull-right\"><a href=\"#manage-modal\" data-toggle=\"modal\" class=\"btn btn-small edit-artifact\" data-id=\""+jsonResponse.data.pfartifact_id+"\"><i class=\"icon-edit\"></i></a> <a href=\"#manage-modal\" data-toggle=\"modal\" class=\"btn btn-small delete-artifact\" data-id=\""+jsonResponse.data.pfartifact_id+"\"><i class=\"icon-trash\"></i></a></div></div></div><div class=\"row-fluid\">" + jsonResponse.data.description + "</div>");
							$("#manage-modal").modal("hide");
						}
					}
				});
			});
			$("#manage-modal").on("hide", function(e) {
				$("#portfolio-form").empty();
				var cloned_form = $("#portfolio-form").clone();
				$("#manage-modal .modal-body").empty().append(cloned_form);
				$(".save-btn").removeClass("edit-artifact-modal add-artifact-modal add-folder-modal delete-artifact-modal delete-folder delete-portfolio-modal add-new-portfolio update-portfolio copy-new-portfolio btn-danger").addClass("btn-primary");
				$(".save-btn").removeData("pfartifact-id").removeData("pfolder-id");
			});
			$("#advisors").on("click", ".advisor", function(e) {
				var btn = $(this);
				$("#advisors .right-pane").empty()
				
				var title = document.createElement("h1");
				$(title).html("Students assigned to " + btn.html());
				$("#advisors .right-pane").append(title).append("<div class=\"row-fluid space-below\"><a href=\"#advisor-modal\" data-advisor-id=\""+btn.data("id")+"\" data-toggle=\"modal\" class=\"btn btn-success pull-right add-student-btn\"><i class=\"icon-plus-sign icon-white\"></i> Add Student</a></div>");
				var user_list = document.createElement("table");
				$(user_list).attr({"class" : "padvisor-students table table-bordered table-striped", "data-padvisor-id" : btn.data("id")});
				$("#advisors .right-pane").append(user_list);
				
				$.ajax({
					url : api_url,
					type : "GET",
					data : "method=get-advisor-students&padvisor_proxy_id=" + btn.data("id"),
					success: function(data) {
						var jsonResponse = JSON.parse(data);
						if (jsonResponse.status == "success") {
							$.each(jsonResponse.data, function(i, v) {
								var user_line = document.createElement("tr");
								var user_link = document.createElement("a");
								var user_link_td = document.createElement("td");
								$(user_link).attr({"href" : "#", "data-student-id" : v.proxy_id, "data-advisor-id" : btn.data("id")}).html("<i class=\"icon-trash\"></i>").addClass("remove-relation btn btn-small pull-right");
								$(user_link_td).addClass("span1").append(user_link);
								$(user_line).html("<td class=\"span11\">" + v.fullname + "</td>").append(user_link_td);
								$("table.padvisor-students").append(user_line);
							});
						} else {
							$("table.padvisor-students").append("<tr class=\"no-students\"><td colspan=\"2\"> There are no students assigned to this advisor.</td></tr>");
						}
					}
				});
				e.preventDefault();
			});
			$("#advisors").on("click", ".remove-relation", function(e) {
				var btn = $(this);
				$.ajax({
					url : api_url,
					type : "POST",
					data : "method=delete-advisor-student&student_id=" + btn.data("student-id") + "&advisor_id=" + btn.data("advisor-id"),
					success: function(data) {
						var jsonResponse = JSON.parse(data);
						if (jsonResponse.status == "success") {
							btn.closest("tr").remove();
							if ($(".padvisor-students tr").length <= 0) {
                                $("table.padvisor-students").append("<tr class=\"no-students\"><td colspan=\"2\"> There are no students assigned to this advisor.</td></tr>");
							}
						}
					}
				});
			});
			$("#advisors").on("click", ".add-student-btn", function(e) {
				$("#advisor_id").attr("value", $(this).data("advisor-id"));
				$("#student_list").empty();
				$("#associated_student").attr("value", "");
				$("#student_ref").attr("value", "");
			});
			$("#advisors").on("click", ".add-students", function(e) {
				$.ajax({
					url : api_url,
					type : "POST",
					data : "method=add-advisor-students&student_ids=" + $("#associated_student").attr("value") + "&advisor_id=" + $("#advisor_id").attr("value"),
					success: function(data) {
						var jsonResponse = JSON.parse(data);
						if (jsonResponse.status == "success") {
							if ($(".no-students").length > 0) {
								$(".no-students").remove();
							}
							$.each(jsonResponse.data, function(i, v) {
                                var user_line = document.createElement("tr");
                                var user_link = document.createElement("a");
                                var user_link_td = document.createElement("td");
                                $(user_link).attr({"href" : "#", "data-student-id" : v.proxy_id, "data-advisor-id" : v.advisor}).html("<i class=\"icon-trash\"></i>").addClass("remove-relation btn btn-small pull-right");
                                $(user_link_td).addClass("span1").append(user_link);
                                $(user_line).html("<td class=\"span11\">" + v.firstname + " " + v.lastname + "</td>").append(user_link_td);
                                $("table.padvisor-students[data-padvisor-id='" + v.advisor + "']").append(user_line);
							});
							$("#advisor-modal").modal("hide");
						}
					}
				});
			});
			$("a.add-advisors").on("click", function(e) {
				$("#add-advisor-form").submit();
				e.preventDefault();
			});
			$("#add-advisor-form").on("submit", function(e) {
				$.ajax({
					url : api_url,
					type : "POST",
					data : "method=add-advisors&advisor_ids=" + $("#associated_advisor").attr("value"),
					success: function(data) {
						var jsonResponse = JSON.parse(data);
						if (jsonResponse.status == "success") {
							$.each(jsonResponse.data, function(i, v) {
								var advisor_line = document.createElement("li");
								var advisor_link = document.createElement("a");
								$(advisor_link).html(v.firstname + " " + v.lastname).addClass("advisor").attr({"data-id" : v.proxy_id, "href" : "#"});
								$(advisor_line).append(advisor_link);
								$("#advisors .left-pane ul").append(advisor_line);
								$("#add-advisor-modal").modal("hide");
							});
						}
					}
				});
				e.preventDefault();
			});
			$("#manage").on("click", ".delete-portfolio", function(e) {
				display_error(["<strong>WARNING</strong> You are about to delete a portfolio. Please use the button below to confirm you wish to delete it."], "#manage-modal .modal-body", "append");
                $("#manage-modal .modal-header h3").html("Delete Portfolio");
				$("#manage-modal .modal-footer .save-btn").addClass("btn-danger").addClass("delete-portfolio-modal").removeClass("btn-primary").html("Delete");
			});
			$("#manage-modal .modal-footer").on("click", ".delete-portfolio-modal", function(e) {
				$.ajax({
					url : api_url,
					type : "POST",
					data : "method=delete-portfolio&portfolio_id=" + $("#portfolio-actions").data("portfolio-id"),
					success: function(data) {
						var jsonResponse = JSON.parse(data);
						if (jsonResponse.status == "success") {
							$(".portfolio-item[data-portfolio-id='" + $("#portfolio-actions").data("portfolio-id") + "']").parent().remove();
							$("#manage .left-pane ul li:eq(1) a").click();
						}
					}
				});
				$("#manage-modal").modal("hide");
				e.preventDefault();
			});
			$("#manage").on("change", "#group-id", function(e) {
				$("#group-name").attr("value", $(this).children("option[value='"+$(this).val()+"']").html());
				e.preventDefault();
			});
			$("#manage").on("click", ".add-portfolio", function(e) {
				if ($("#display-error-box-modal").length > 0) {
					$("#display-error-box-modal").remove();
				}
				$("#manage-modal .modal-header h3").html("New Portfolio");
				$("#manage-modal .modal-footer .save-btn").removeAttr("data-pfartifact-id").removeAttr("data-pfolder-id").html("Add").addClass("add-new-portfolio").removeClass("btn-danger");
				portfolioForm("add");
				e.preventDefault();
			});
			$("#manage").on("click", ".edit-portfolio", function(e) {
				if ($("#display-error-box-modal").length > 0) {
					$("#display-error-box-modal").remove();
				}
				$("#manage-modal .modal-header h3").html("Edit Portfolio");
				$("#manage-modal .modal-footer .save-btn").html("Update").addClass("update-portfolio");
				portfolioForm("edit");

				$.ajax({
					url : api_url,
					type : "GET",
					data : "method=get-portfolio&portfolio_id=" + $(".add-folder").data("id"),
					success : function(data) {
						var jsonResponse = JSON.parse(data);
						if (jsonResponse.status == "success") {
							var start_date = new Date(jsonResponse.data.start_date * 1000);
							var finish_date = new Date(jsonResponse.data.finish_date * 1000);
							$("#portfolio-form input[name='start_date']").attr("value", start_date.getFullYear() + "-" + (start_date.getMonth() <= 9 ? "0" : "") + (start_date.getMonth() + 1) + "-" +  (start_date.getDate() <= 9 ? "0" : "") + start_date.getDate())
							$("#portfolio-form input[name='finish_date']").attr("value", finish_date.getFullYear() + "-" + (finish_date.getMonth() <= 9 ? "0" : "") + (finish_date.getMonth() + 1) + "-" +  (finish_date.getDate() <= 9 ? "0" : "") + finish_date.getDate())
							if (jsonResponse.data.active != 1) {
								$("#portfolio-form input[name='active']").removeAttr("checked");
							}
							if (jsonResponse.data.allow_student_export != 1) {
								$("#portfolio-form input[name='export']").removeAttr("checked");
							}
						}
					}
				});
				
				e.preventDefault();
			});
			$("#manage").on("click", ".copy-portfolio", function(e) {
				if ($("#display-error-box-modal").length > 0) {
					$("#display-error-box-modal").remove();
				}
				$("#manage-modal .modal-header h3").html("Copy Portfolio");
				$("#manage-modal .modal-footer .btn-primary").html("Copy").addClass("copy-new-portfolio");
				portfolioForm("add");
				display_notice(["Please select the group, start, and finish dates the copied portfolio will apply to."], "#portfolio-form", "prepend");
				e.preventDefault();
			});
			$("#manage").on("click", ".add-new-portfolio", function(e) {
				$.ajax({
					url : api_url,
					type : "POST",
					data : "method=create-portfolio&" + $("#portfolio-form").serialize(),
					success : function(data) {
						var jsonResponse = JSON.parse(data);
						if (jsonResponse.status == "success") {
							$("#manage .left-pane ul").append("<li><a href=\"#\" data-portfolio-id=\""+jsonResponse.data.portfolio_id+"\" class=\"portfolio-item\">"+jsonResponse.data.portfolio_name+"</a></li>");
							$("#manage-modal").modal("hide");
						}
					}
				});
				e.preventDefault();
			});
			$("#manage").on("click", ".update-portfolio", function(e) {
				$.ajax({
					url : api_url,
					type : "POST",
					data : "method=create-portfolio&" + $("#portfolio-form").serialize() + "&portfolio_id=" + $(".add-folder").data("id"),
					success : function(data) {
						var jsonResponse = JSON.parse(data);
						if (jsonResponse.status == "success") {
							display_success(["Successfully updated portfolio."], "#portfolio-msg", "append");
							$("#manage-modal").modal("hide");
						}
					}
				});
				e.preventDefault();
			});
			$("#manage-modal .modal-footer").on("click", ".copy-new-portfolio", function(e) {
				$.ajax({
					url : api_url,
					type : "POST",
					data : "method=copy-portfolio&" + $("#portfolio-form").serialize() + "&portfolio_id=" + $(".add-folder").data("id"),
					success : function(data) {
						var jsonResponse = JSON.parse(data);
						if (jsonResponse.status == "success") {
							$("#manage .left-pane ul").append("<li><a href=\"#\" data-portfolio-id=\""+jsonResponse.data.portfolio_id+"\" class=\"portfolio-item\">"+jsonResponse.data.portfolio_name+"</a></li>");
							$("#manage-modal").modal("hide");
						}
					}
				});
				e.preventDefault();
			});
		});
		
		function portfolioForm(mode) {
			
			if (mode == "add") {
				var cohort_row = document.createElement("div");
				jQuery(cohort_row).addClass("control-group");
				var cohort_label = document.createElement("label");
				jQuery(cohort_label).addClass("control-label form-required").html("Group");
				jQuery(cohort_row).append(cohort_label);
				var cohort_container = document.createElement("div");
				jQuery(cohort_container).addClass("controls");
				var cohorts = document.createElement("select");
				jQuery(cohorts).attr({"name" : "group_id", "id" : "group-id"});
				jQuery(cohorts).append("<option>Please select a group</option>");

				jQuery.ajax({
					url : api_url,
					type : "GET",
					data : "method=get-cohorts",
					success : function(data) {
						var jsonResponse = JSON.parse(data);
						if (jsonResponse.status == "success") {
							jQuery.each(jsonResponse.data, function(i, v) {
								var option = document.createElement("option");
								jQuery(option).attr({"value" : v.group_id}).html(v.group_name);
								jQuery(cohorts).append(option);
							});
						}
					}
				});
			}
			jQuery(cohort_container).append(cohorts);
			jQuery(cohort_row).append(cohort_container);
			var cohort_name = document.createElement("input");
			jQuery(cohort_name).attr({"name" : "portfolio_name", "type" : "hidden", "id" : "group-name"})

			jQuery("#portfolio-form").append(cohort_row).append(cohort_name);

			var start_row = document.createElement("div");
			jQuery(start_row).addClass("control-group");
			var start_label = document.createElement("label");
			jQuery(start_label).addClass("control-label form-required").html("Start");
			jQuery(start_row).append(start_label);
			var start_container = document.createElement("div");
			jQuery(start_container).addClass("controls");
			var start_input = document.createElement("input");
			jQuery(start_input).attr({"type":"text", "name" : "start_date"}).addClass("input-small").datepicker({dateFormat: "yy-mm-dd"});
			jQuery(start_container).append(start_input);
			jQuery(start_row).append(start_container);

			var finish_row = document.createElement("div");
			jQuery(finish_row).addClass("control-group");
			var finish_label = document.createElement("label");
			jQuery(finish_label).addClass("control-label form-required").html("Finish");
			jQuery(finish_row).append(finish_label);
			var finish_container = document.createElement("div");
			jQuery(finish_container).addClass("controls");
			var finish_input = document.createElement("input");
			jQuery(finish_input).attr({"type":"text", "name" : "finish_date"}).addClass("input-small").datepicker({dateFormat: "yy-mm-dd"});
			jQuery(finish_container).append(finish_input);
			jQuery(finish_row).append(finish_container);

			var export_row = document.createElement("div");
			jQuery(export_row).addClass("control-group");
			var export_label = document.createElement("label");
			jQuery(export_label).addClass("control-label form-required").html("Allow exporting");
			jQuery(export_row).append(export_label);
			var export_container = document.createElement("div");
			jQuery(export_container).addClass("controls");
			var export_input = document.createElement("input");
			jQuery(export_input).attr({"type":"checkbox", "name" : "export", "checked" : "checked", "value" : "1"});
			var export_input_wrapper = document.createElement("label");
			jQuery(export_input_wrapper).addClass("checkbox").append(export_input).append("<span class=\"muted\">Allow learners to export their ePortfolio.</span>");
			jQuery(export_container).append(export_input_wrapper);
			jQuery(export_row).append(export_container);

			jQuery("#portfolio-form").append(start_row).append(finish_row).append(export_row);
		}
		
	</script>
	<style type="text/css">
		.tab-content.visible {
			overflow: visible;
		}
		.pane-container {
			border:1px solid #DDDDDD;
			height: 500px;
			border-radius:5px;
		}
		
		.left-pane, .right-pane {
			overflow-y:scroll;
			overflow-x:hidden;
			height: 500px;
		}
		
		.left-pane ul {
			list-style: none;
			margin:0;
			padding:0;
		}
		
		.left-pane ul li {
			margin:0px;
			padding:0px;
		}
		
		.left-pane ul li a {
			display:block;
			padding:12px 10px;
		}
		
		.left-pane ul li a.active {
			background: #ecf0f3; /* Old browsers */
			border-bottom: none;
		}
		
		.right-pane {
			padding-right:2.12766%;
		}
		
		.right-pane ul {
			list-style:none;
			margin:0px;
			padding:0px;
		}
		
		.right-pane .portfolio-folder {
			display:block;
			padding:12px 10px;
		}
		
		.well.comments {
			background:#fff;
		}
		#ui-datepicker-div {
			z-index:1050!important;
		}
		#advisor-modal .modal-body, #add-advisor-modal .modal-body {
			overflow-y:visible;
		}
	</style>
	<ul class="nav nav-tabs">
		<li <?php echo $is_advisor ? "class=\"active\"" : ""; ?>><a href="#review" data-toggle="tab">Review</a></li>
		<?php if (!$is_advisor) { ?>
		<li class="active"><a href="#manage" data-toggle="tab">Manage</a></li>
		<li class=""><a href="#advisors" data-toggle="tab">Advisors</a></li>
		<?php } ?>
    </ul>
	
	<div class="tab-content visible">
		<div class="tab-pane <?php echo $is_advisor ? "active" : ""; ?>" id="review">
			<div class="row-fluid space-below">
				<div class="btn-group">
					<a class="btn btn-primary">Portfolio</a>
					<a class="btn btn-primary dropdown-toggle" data-toggle="dropdown" href="#"><span class="caret"></span></a>
					<ul class="dropdown-menu" id="portfolio-list">
					<?php 
                    if ($eportfolios) { 
                        foreach ($eportfolios as $eportfolio) { ?>
						<li>
							<a href="#" data-id="<?php echo $eportfolio->getID(); ?>" class="portfolio-item"><?php echo $eportfolio->getPortfolioName(); ?></a>
						</li>
                    <?php } 
                    } else {
                        echo "<li>None assigned.</li>";
                    } ?>
					</ul>
				</div>
				<div class="btn-group" id="flag-toggle">
					<button type="button" class="btn active">All</button>
					<button type="button" class="btn flagged">Flagged</button>
				</div>
			</div>
			<div id="breadcrumb" class="row-fluid space-below"></div>
			<div id="portfolio-container" class="pane-container row-fluid">
				<div id="user-list" class="left-pane span3"></div>
				<div id="user-portfolio" class="right-pane span9">
					<h1>Portfolio</h1>
					<?php echo display_generic("Please select a student from the menu on the left to get started."); ?>
				</div>
			</div>
			<div id="entry-modal" class="modal hide">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
					<h3>View Entry</h3>
				</div>
				<div class="modal-body">
					<form action="" method="POST" class="form-horizontal" id="modal-form"></form>
				</div>
				<div class="modal-footer">
					<a href="#" class="btn" data-dismiss="modal" aria-hidden="true">Close</a>
					<a href="#" class="btn btn-primary">Save</a>
				</div>
			</div>
		</div>
		<?php if (!$is_advisor) { ?>
		<div class="tab-pane active" id="manage">
			<div class="pane-container row-fluid">
				<div class="left-pane span3">
					<ul>
						<li><a href="#manage-modal" data-toggle="modal" class="add-portfolio"><i class="icon-plus-sign"></i> New Portfolio</a></li>
					<?php 
					if ($eportfolios) {
						foreach ($eportfolios as $eportfolio) { ?>
							<li><a href="#" class="portfolio-item" data-portfolio-id="<?php echo $eportfolio->getID(); ?>"><?php echo $eportfolio->getPortfolioName(); ?></a></li>
						<?php } 
					}?>
					</ul>
				</div>
				<div class="right-pane span9">
					<h1 id="manage-eportfolio-title">Manage ePortfolio</h1>
					<div id="portfolio-msg"></div>
					<div id="portfolio-actions" class="btn-group hide space-below">
						<a href="#manage-modal" data-toggle="modal" class="btn add-folder"><i class="icon-folder-open" title="Edit"></i> Add Folder</a>
						<button class="btn dropdown-toggle" data-toggle="dropdown">
							<span class="caret"></span>
						</button>
						<ul class="dropdown-menu">
							<li><a href="#manage-modal" data-toggle="modal" class="edit-portfolio"><i class="icon-edit" title="Edit"></i> Edit Portfolio</a></li>
							<li><a href="#manage-modal" data-toggle="modal" class="copy-portfolio"><i class="icon-refresh" title="Copy"></i> Copy Portfolio</a></li>
							<li><a href="#manage-modal" data-toggle="modal" class="delete-portfolio"><i class="icon-trash" title="Delete"></i> Delete Portfolio</a></li> 
						</ul>
					</div>
					<div id="artifacts">
						<?php echo display_notice("Please select an eportfolio from the left to get started, or use the New Portfolio item to create one."); ?>
					</div>
				</div>
				<div id="manage-modal" class="modal hide">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<h3>View Entry</h3>
					</div>
					<div class="modal-body">
						<form action="" method="POST" class="form-horizontal admin-portfolio-form" id="portfolio-form"></form>
					</div>
					<div class="modal-footer">
						<a href="#" class="btn" data-dismiss="modal" aria-hidden="true">Close</a>
						<a href="#" class="btn btn-primary save-btn">Save changes</a>
					</div>
				</div>
			</div>
		</div>
		<div class="tab-pane" id="advisors">
			<div class="row-fluid space-below"><a href="#add-advisor-modal" class="btn btn-success pull-right" data-toggle="modal"><i class="icon-plus-sign icon-white"></i> Add Advisor</a></div>
			<div class="pane-container row-fluid">
				<div class="left-pane span3">
					<ul>
						<?php
						$eportfolio_advisors = Models_Eportfolio_Advisor::fetchAll($ENTRADA_USER->getActiveOrganisation());
						if ($eportfolio_advisors) {
							foreach ($eportfolio_advisors as $advisor) {
								?><li><a href="#" data-id="<?php echo $advisor->getProxyID(); ?>" class="advisor"><?php echo $advisor->getLastName() . ", " . $advisor->getFirstName(); ?></a></li><?php
							}
						}
						?>
					</ul>
				</div>
				<div class="right-pane span9">
					<h1>Manage Advisors</h1>
					<?php echo display_notice("Please select an advisor from the list on the left, or please add an advisor with the button above."); ?>
				</div>
			</div>
			<div id="add-advisor-modal" class="modal hide">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
					<h3>Add Advisors</h3>
				</div>
				<div class="modal-body">
					<form action="<?php echo ENTRADA_URL; ?>/api/eportfolio.api.php" method="POST" class="form-horizontal admin-advisor-form" id="add-advisor-form">
						<div class="control-group">
							<label class="control-label" for="advisor-name">advisor Name</label>
							<div class="controls">
								<input type="text" id="advisor_name" name="fullname" autocomplete="off" placeholder="Example: <?php echo html_encode($ENTRADA_USER->getLastname().", ".$ENTRADA_USER->getFirstname()); ?>" />
                                <?php
                                $ONLOAD[] = "advisor_list = new AutoCompleteList({ type: 'advisor', url: '". ENTRADA_RELATIVE ."/api/personnel.api.php?type=facultyorstaff', remove_image: '". ENTRADA_RELATIVE ."/images/action-delete.gif'})";
                                ?>
                                <div class="autocomplete" id="advisor_name_auto_complete"></div>
                                <input type="hidden" id="associated_advisor" name="associated_advisor" />
                                <input type="button" class="btn" id="add_associated_advisor" value="Add" />
								<ul id="advisor_list" class="menu" style="margin-top: 15px"></ul>
								<input type="hidden" id="advisor_ref" name="advisor_ref" value="" />
                                <input type="hidden" id="advisor_id" name="advisor_id" value="" />
								<input type="hidden" id="advisor_id" name="advisor_id" value="" />
							</div>
						</div>
					</form>
				</div>
				<div class="modal-footer">
					<a href="#" class="btn" data-dismiss="modal" aria-hidden="true">Close</a>
					<a href="#" class="btn btn-primary add-advisors">Add Advisors</a>
				</div>
			</div>
			<div id="advisor-modal" class="modal hide">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
					<h3>Add Students</h3>
				</div>
				<div class="modal-body">
					<form action="<?php echo ENTRADA_URL; ?>/api/eportfolio.api.php" method="POST" class="form-horizontal admin-advisor-form" id="advisor-form">
						<div class="control-group">
							<label class="control-label" for="student-name">Student Name</label>
							<div class="controls">
								<input type="text" id="student_name" name="fullname" autocomplete="off" placeholder="Example: <?php echo html_encode($ENTRADA_USER->getLastname().", ".$ENTRADA_USER->getFirstname()); ?>" />
                                <?php
                                $ONLOAD[] = "student_list = new AutoCompleteList({ type: 'student', url: '". ENTRADA_RELATIVE ."/api/personnel.api.php?type=student', remove_image: '". ENTRADA_RELATIVE ."/images/action-delete.gif'})";
                                ?>
                                <div class="autocomplete" id="student_name_auto_complete"></div>
                                <input type="hidden" id="associated_student" name="associated_student" />
                                <input type="button" class="btn" id="add_associated_student" value="Add" />
								<ul id="student_list" class="menu" style="margin-top: 15px"></ul>
								<input type="hidden" id="student_ref" name="student_ref" value="" />
                                <input type="hidden" id="student_id" name="student_id" value="" />
								<input type="hidden" id="advisor_id" name="advisor_id" value="" />
							</div>
						</div>
					</form>
				</div>
				<div class="modal-footer">
					<a href="#" class="btn" data-dismiss="modal" aria-hidden="true">Close</a>
					<a href="#" class="btn btn-primary add-students">Add students</a>
				</div>
			</div>
		</div>
		<?php } ?>
	</div>
	<?php
}