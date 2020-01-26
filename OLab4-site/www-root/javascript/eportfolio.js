jQuery(function($) {
	
	var pfolder_id = $("#folder-list").children(":first").children("a").data("id");
	
	if (location.hash.length > 0) {
		pfolder_id = parseInt(location.hash.substring(1, location.hash.length));
	}
	
	getFolder(pfolder_id);

	$("body").on("click", "#create-artifact", function () {
		$(".modal-header h3").html(eportfolio_index_localization.create_artifact_in + " " + $("#current-folder").html());
		$("#save-button").html("Save Artifact").attr("data-type", "artifact");
		artifactForm();
	});
	
	$("#portfolio-form").on("submit", function(e) {		
		if ($(".isie").length > 0) {
			$("#method").attr("value", "create-entry").attr("name", "method");
		} else {
			var xhr = new XMLHttpRequest();
			var fd = new FormData();
			var file = $("#file-upload").prop("files");
			var pfartifact_id = jQuery("#save-button").attr("data-artifact");
			
			fd.append("method", "create-entry");
			fd.append("type", "file");
			fd.append("title", jQuery("#entry-title").val());
			fd.append("filename", jQuery("#file-upload").val());
			fd.append("description", jQuery("#entry-description").val());
			fd.append("pfartifact_id", pfartifact_id);
			
			fd.append("file", file[0]);

			xhr.open('POST', ENTRADA_URL + "/api/eportfolio.api.php", true);
			xhr.send(fd);

			xhr.onreadystatechange = function() {
				if (xhr.readyState == 4 && xhr.status == 200) {
					var jsonResponse = JSON.parse(xhr.responseText);
					if (jsonResponse.status == "success") {
						if (!jQuery("#artifact-" + pfartifact_id).length) {
							// Elements for artifact-entries-list
							var artifact_div = document.createElement("div");
							var artifact_title_h2 = document.createElement("h2");
							var artifact_list = document.createElement("ul");

							jQuery(artifact_list).attr({"id": "artifact-" + pfartifact_id}).addClass("unstyled");
							jQuery(artifact_title_h2).html(jQuery("a[data-id=" + pfartifact_id + "] span").html());
							jQuery(artifact_div).attr({"data-id": pfartifact_id}).append(artifact_title_h2).append(artifact_list).addClass("artifact-group");
							jQuery("#artifact-container").append(artifact_div);
						}
				
						var content = "";
						var entry_li = document.createElement("li");
						var entry_li_a = document.createElement("a");
						var entry_delete_a = document.createElement("a");
						var entry_div = document.createElement("div");
						var date = new Date(jsonResponse.data.submitted_date * 1000);

						if (typeof jsonResponse.data.edata.title != "undefined") {
							content = jsonResponse.data.edata.title;
						} else if (typeof jsonResponse.data.edata.filename != "undefined") {
							content = jsonResponse.data.edata.filename;
						} else if (typeof jsonResponse.data.edata.description != "undefined") {
							content = jsonResponse.data.edata.description.replace(/(<([^>]+)>)/ig,"").substr(0, 80) + "...";
						} else {
							content = "N/A";
						}

						jQuery(entry_li_a).attr({"href": "#", "data-toggle": "modal", "data-target": "#portfolio-modal", "data-artifact": pfartifact_id, "data-entry": jsonResponse.data.pentry_id, "data-type": jsonResponse.data.type}).html(content).addClass("edit-entry");
						jQuery(entry_div).html(eportfolio_index_localization.submitted + ": " + date.getFullYear() + "-" + (date.getMonth() <= 9 ? "0" : "") + (date.getMonth() + 1) + "-" +  (date.getDate() <= 9 ? "0" : "") + date.getDate() + ", Entry Type: " + jsonResponse.data.type).addClass("muted");
						jQuery(entry_delete_a).attr({"href": "#", "data-toggle": "modal", "data-target": "#portfolio-modal", "data-id": jsonResponse.data.pentry_id}).addClass("delete-entry").html("<i class=\"icon-trash\"></i>");
						jQuery(entry_li).append(entry_li_a).append(entry_delete_a).append(entry_div);
						jQuery("#artifact-" + pfartifact_id).append(entry_li);
						jQuery("#portfolio-modal").modal("hide");
						if (jQuery("#artifact-" + pfartifact_id + " .no-entries").length) {
							jQuery(".no-entries").remove();
						}
						//updateArtifactList(pfolder_id);
						getFolderArtifacts(pfolder_id);
					} else {
						display_error(jsonResponse.data, "#modal-msg", "append");
					}
				}
			}
		}
		e.preventDefault();
	});

	$(".folder-item").on("click", function (e) {
		if (jQuery("#entries-list").length) {
			jQuery("#entries-list").remove();
		}
		$(".artifact-container").empty().addClass("loading");
		pfolder_id = $(this).data("id");
		getFolderArtifacts(pfolder_id);
		location.hash = $(this).attr("data-id");
		jQuery("#current-folder").html(jQuery(this).children("span").html());

		if (jQuery("#msgs .alert-success").length) {
			jQuery("#msgs .alert-success").remove();
		}
		
		if (jQuery("#msgs .alert-info").length) {
			jQuery("#msgs .alert-info").remove();
		}
		
		e.preventDefault();
	});

	$("#save-button").on("click", function(e) {
		var button = $(this);
		var type = $(this).attr("data-type");
		var pfartifact_id =  jQuery("#save-button").attr("data-artifact");
		var method;
		
		switch (type) {
			case "file" :
				method = "media-entry";
				if (!window.FileReader) {
					$("#portfolio-form").append("<input type=\"hidden\" name=\"isie\" value=\"isie\" class=\"isie\" />");
				}
			break;
			case "reflection" :
				method = "create-entry&pfartifact_id=" + pfartifact_id;
			break;
			case "file-edit" :
				type = "file";
				method = "media-entry&filename=" + jQuery("#entry-filename").val() + "&pfartifact_id=" + pfartifact_id;
			break;
			case "artifact" :
				method = "create-artifact&pfolder_id=" + pfolder_id;
			break;
			case "artifact-edit" :
				method = "create-artifact&pfolder_id=" + pfolder_id + "&pfartifact_id=" + pfartifact_id;
			break;
			case "url" :
				method = "create-entry&pfartifact_id=" + pfartifact_id;
			break;
			case "delete-entry" :
				method = "delete-entry";
			break;
			case "delete-artifact" :
				method = "delete-artifact";
			break;
		}
		
		if (jQuery("#save-button").attr("data-entry")) {
			var pentry_id = jQuery("#save-button").attr("data-entry");
			method =  method + "&pentry_id=" + pentry_id;
		}

		if (method != "media-entry") {
			$.ajax({
				url: ENTRADA_URL + "/api/eportfolio.api.php",
				type: "POST",
				data: "method=" + method + "&type=" + type +  "&" + $("#portfolio-form").serialize(),
				success: function (data) {
					var jsonResponse = JSON.parse(data);
					if (jsonResponse.status == "success") {
						appendContent(type, jsonResponse.data, pfartifact_id, pfolder_id);
						$("#portfolio-modal").modal("hide");
					} else {
						var msgs = new Array();
						display_error(jsonResponse.data, "#modal-msg");
					}
					if (method == "delete-artifact") {
						jQuery("#artifact-"+jsonResponse.data.pfartifact_id).parent().remove();
					}
				},
				error: function (data) {
					display_error([eportfolio_index_localization.error_saving_entry + ". " + eportfolio_index_localization.please_try_again + "."], "#modal-msg");
				}
			});
		} else {
			$("#portfolio-form").attr("enctype", "multipart/form-data").attr("action", ENTRADA_URL + "/api/eportfolio.api.php").submit();
		}

		e.preventDefault();
	});

	jQuery("#portfolio-modal").on("hide", function () {
		if (jQuery("#save-button").attr("data-type") !== undefined) {
			jQuery("#save-button").removeAttr("data-type");
		}
		
		if (jQuery("#save-button").attr("data-artifact") !== undefined) {
			jQuery("#save-button").removeAttr("data-artifact");
		}
		
		if (jQuery("#save-button").attr("data-entry") !== undefined) {
			jQuery("#save-button").removeAttr("data-entry");
		}
		
		if (jQuery("#reflection-body").length) {
			jQuery("#reflection-body").ckeditorGet().destroy();
		}
		
		if ($("#portfolio-form .control-group").length) {
			$("#portfolio-form .control-group").remove();
		}

		if ($("#display-error-box-modal")) {
			$("#modal-msg").empty();
		}
		
		if (jQuery("#portfolio-form .table").length) {
			jQuery("#portfolio-form .table").remove();
		}

		if (jQuery("#portfolio-form .alert-notice").length) {
			jQuery("#portfolio-form .alert-notice").remove();
		}
		
		if (jQuery("#portfolio-form .alert-danger").length) {
			jQuery("#portfolio-form .alert-danger").remove();
		}
		
		if (jQuery("#display-error-box-modal").length) {
			jQuery("#display-error-box-modal").remove();
		}
		
		if (jQuery("#pfartifact_id").length) {
			jQuery("#pfartifact_id").remove();
		}
		
		if (jQuery("#save-button").hasClass("btn-danger")) {
			jQuery("#save-button").removeClass("btn-danger").addClass("btn-primary").html(eportfolio_index_localization.save_entry);
		}
	});
	
	jQuery(".artifact-container").on("click", ".edit-entry", function (e) {
		var data_type = jQuery(this).data("type");
		var pfartifact_id = jQuery(this).data("artifact");
		var pentry_id = jQuery(this).data("entry");
		
		jQuery("#save-button").attr({"data-entry": pentry_id});
		jQuery("#save-button").attr("data-artifact", pfartifact_id);
		
		switch (data_type) {
			case "reflection":
				jQuery("#save-button").attr("data-type", "reflection");
			break;
			case "file":
				jQuery("#save-button").attr("data-type", "file-edit");
			break;
			case "url":
				jQuery("#save-button").attr("data-type", "url");
			break;
		}
		
		buildEntryForm(data_type, pentry_id, true);
		populateEntryForm(pentry_id);
		e.preventDefault();
	});
	
	jQuery(".artifact-container").on("click", ".entry", function (e) {
		var pfartifact_id = jQuery(this).parent().parent().data("artifact");
		
		if (jQuery("#save-button").attr("data-action") || jQuery("#save-button").attr("data-entry")) {
			jQuery("#save-button").removeAttr("data-action");
			jQuery("#save-button").removeAttr("data-entry");
		}

		jQuery(".modal-header h3").html(eportfolio_index_localization.add_entry);
		
		if (jQuery(this).data("type") == "reflection") {
			jQuery("#save-button").html(eportfolio_index_localization.save_entry).attr("data-type", "reflection");
			jQuery("#method").attr("value", "reflection-entry");
		}
		
		if (jQuery(this).data("type") == "file") {
			jQuery("#save-button").html(eportfolio_index_localization.save_entry).attr("data-type", "file");
			jQuery("#method").attr("value", "file-entry");
		}
		
		if (jQuery(this).data("type") == "url") {
			jQuery("#save-button").html(eportfolio_index_localization.save_entry).attr("data-type", "url");
			jQuery("#method").attr("value", "url-entry");
		}
		
		jQuery("#save-button").attr("data-artifact", pfartifact_id);
		entryForm(pfartifact_id);
		e.preventDefault();
	});
	
	jQuery(".artifact-container").on("click", ".edit-artifact", function () {
		var pfartifact_id = jQuery(this).data("artifact");
		jQuery("#save-button").attr("data-type", "artifact-edit");
		jQuery("#save-button").attr("data-artifact", pfartifact_id);
		populateArtifactForm(pfartifact_id);
	});
	
	jQuery(".artifact-container").on("click", ".delete-entry", function () {
		var pentry_id = jQuery(this).attr("data-id");
		var entry_title = jQuery("a[data-entry="+ pentry_id +"]").html();
		jQuery("#save-button").attr("data-entry", pentry_id);
		jQuery("#save-button").html(eportfolio_index_localization.delete_entry);
		jQuery("#save-button").attr("data-type", "delete-entry");
		populateDeleteForm(pentry_id, entry_title);
	});

	jQuery("#artifact-container").on("click", ".edit-entry-assessable", function (e) {
		var pentry_id = jQuery(this).data("id");
		var pfartifact_id = jQuery(this).data("artifact-id");
		var is_assessable = jQuery(this).data("assessable");

		var action = "assessable";
		if ( 1 == is_assessable ) {
			action = "unassessable";
		}

		// calls an api to flop is_assessable flag in database
		$.ajax({
			url : ENTRADA_URL + "/api/eportfolio.api.php",
			type : "POST",
			data : "method=pentry-assessable&action="+action+"&pentry_id=" + pentry_id,
			success: function(data) {
				// redraw the artifact entries
				getEntries(pfartifact_id);
			}
		});

		e.preventDefault();
	});

	jQuery("body").on("click", ".artifact", function () {
		var pfartifact_id = jQuery(this).data("id");
		jQuery("#save-button").attr({"data-artifact": pfartifact_id}).html(eportfolio_index_localization.save_entry);
		entryForm(pfartifact_id);
	});
	
	jQuery("#artifact-list").on("click", ".remove-artifact", function(e) {
		jQuery("#portfolio-modal .modal-header h3").html("Remove Artifact");
		display_error(["<strong>" + eportfolio_index_localization.warning + "</strong> " + eportfolio_index_localization.chosen_to_remove_artifact + ".<br /><br />" + eportfolio_index_localization.use_button_to_remove_artifact + "."], "#portfolio-form", "prepend");
		jQuery("#save-button").addClass("btn-danger").removeClass("btn-primary").html(eportfolio_index_localization.remove).attr("data-type", "delete-artifact");
		jQuery("#portfolio-form").append("<input id=\"pfartifact_id\" type=\"hidden\" name=\"pfartifact_id\" value=\""+jQuery(this).data("id")+"\" />")
		e.preventDefault();
	});
	
	jQuery("#portfolio-form").on("change", "#entry-type-select", function () {
		var entry_type = jQuery(this).val();
		jQuery("#save-button").attr({"data-type": entry_type});
		if (jQuery("#portfolio-form fieldset").length) {
			jQuery("#portfolio-form fieldset").remove();
		}
		buildEntryForm(entry_type, false);
	});
});

function getFolder (pfolder_id) {
	jQuery.ajax({
		url: ENTRADA_URL + "/api/eportfolio.api.php",
		data: "method=get-folder&pfolder_id=" + pfolder_id,
		type: 'GET',
		success: function (data) {
			var jsonResponse = JSON.parse(data);
			if (jsonResponse.status === "success") {
                jQuery("#current-folder").html(jsonResponse.data.title);
				getFolderArtifacts(pfolder_id);
			} else {
				display_error(jsonResponse.data, "#msgs", "append");
			}
		},
		error: function (data) {
			jQuery(".artifact-container").removeClass("loading");
			display_error([eportfolio_index_localization.error_fetching_folder + ". " + eportfolio_index_localization.please_try_again + "."], "#msgs", "append");
		}
	});
}

function getFolderArtifacts (pfolder_id) {
	var proxy_id = PROXY_ID;
	var portfolio_id = PORTFOLIO_ID;

	if (jQuery("#artifact-list .artifact-list-item").length) {
		jQuery("#artifact-list .artifact-list-item").remove();
	}

	// load folder details
	jQuery("#portfolio-folder-pulse").load(ENTRADA_URL + "/api/eportfolio.api.php" + "?method=get-portfolio-pulse&pfolder_id=" + pfolder_id + "&proxy_id=" + proxy_id);
	// find out whether folders have required entries
	jQuery.ajax({
		url: ENTRADA_URL + "/api/eportfolio.api.php",
		data: "method=get-portfolio-pulse&portfolio_id=" + portfolio_id + "&proxy_id=" + proxy_id,
		type: 'GET',
		dataType: 'json',
		success: function (obj) {
			if (obj.data.pfa_required) {
				jQuery(".portfolio-required-artifacts").html(' <span class="text-error"><i class="fa fa-bell" title="Some folders require entries"></i></span>');
				var $anchor;
				jQuery("#folder-list li").each(function() {
					$anchor = jQuery(this).find("a.folder-item");
					if ('undefined' != typeof($anchor.data('id'))) {
						jQuery.each(obj.data.pf_data, function(i, pf_data) {
							if (pf_data && pf_data.data_folder && pf_data.data_folder.pfolder_id == $anchor.data("id")) {
								if (pf_data.fa_required_incomplete && pf_data.fa_required_incomplete > 0) {
									$anchor.find("span").addClass("text-error");
									$anchor.find("i").addClass("text-error");
								}							}
						});
					}
				});
			}
		},
		error: function () {}
	});

	jQuery.ajax({
		url: ENTRADA_URL + "/api/eportfolio.api.php",
		data: "method=get-folder-artifacts&pfolder_id=" + pfolder_id + "&proxy_id=" + proxy_id,
		type: 'GET',
		success: function (data) {
			var jsonResponse = JSON.parse(data);

			// Only show the learner-created artifact controls if allowed for this folder
			if ( 1 == jsonResponse.folder.allow_learner_artifacts ) {
				jQuery("#artifact-learner-create").html("<a href=\"#\" data-id=\"2\" id=\"create-artifact\" data-toggle=\"modal\" data-target=\"#portfolio-modal\"><i class=\"fa fa-plus\"></i> " + eportfolio_index_localization.create_my_own_artifact + "</a>");
				jQuery("#entries-user").show();
			} else {
				jQuery("#artifact-learner-create").html("");
				jQuery("#entries-user").hide();
			}

			jQuery(".artifact-container").removeClass("loading");
			jQuery(".artifact-container").empty();

			if (jsonResponse.status == "success") {
				if (jQuery("#msgs > .alert-notice").length) {
					jQuery("#msgs .alert-notice").remove();
				}
				jQuery(".artifact-container").append("<h1 class='text-center'>Artifacts with attached entries</h1>");

				jQuery.each(jsonResponse.data, function (key, artifact) {
					var pfartifact_id = artifact.pfartifact_id;
					var artifact_title = artifact.title;
					var artifact_description = artifact.description;
					var artifact_due;
					var proxy_id = artifact.proxy_id;
					
					if (artifact.finish_date > 0) {
						artifact_due = artifact.finish_date;
					} else if (artifact.start_date) {
						artifact_due = artifact.start_date;
					} else {
						artifact_due = 0;
					}
					appendArtifact(pfartifact_id, artifact_title, artifact_description, artifact_due, artifact.total_entries, artifact.has_entry, proxy_id);
					getEntries(pfartifact_id);
				});
				
				if (!jQuery(".artifact-group").length) {
					display_generic([eportfolio_index_localization.no_artifacts_attached_entries + ". " + eportfolio_index_localization.to_add_entry_select_artifact + "."], "#msgs", "append");
				}
			} else {
				display_notice([jsonResponse.data], "#msgs");
			}
			// this draws the "My Artifacts" menu
			updateArtifactList(pfolder_id);
		},
		error: function () {
			jQuery(".artifact-container").removeClass("loading");
			display_error([eportfolio_index_localization.error_fetching_folder_artifacts + ". " + eportfolio_index_localization.please_try_again + "."], "#msgs", "append");
		}
	});
}

function getEntries (pfartifact_id) {
	var proxy_id = PROXY_ID;
	jQuery.ajax({
		url: ENTRADA_URL + "/api/eportfolio.api.php",
		data: "method=get-artifact-entries&pfartifact_id=" + pfartifact_id + "&proxy_id=" + proxy_id,
		type: 'GET',
		success: function (data) {
			// clear current entries (to allow for ajax updates)
			jQuery("#artifact-" + pfartifact_id).html("");
			var jsonResponse = JSON.parse(data);
			if (jsonResponse.status == "success") {
				jQuery.each(jsonResponse.data, function(key, entry) {
					var entry_li = document.createElement("li");
					var entry_well = document.createElement("div");
					var entry_div = document.createElement("div");
					var entry_controls = document.createElement("div");

					var entry_li_h3 = document.createElement("h3");
					var entry_assessable = document.createElement("a");
					var entry_li_a = document.createElement("a");
					var entry_link_a = document.createElement("a");
					var entry_delete_a = document.createElement("a");

					var entry_metadata = document.createElement("div");
					var entry_date = document.createElement("p");

					var entry_hr = document.createElement("hr");

					var date = new Date(entry.entry.submitted_date * 1000);

					var title = "";
					var content = "";
					if (typeof entry.entry._edata.title != "undefined") {
                        title = entry.entry._edata.title;
						content = entry.entry._edata.title;
					} else if (typeof entry.entry._edata.filename != "undefined") {
                        title = entry.entry._edata.filename;
						content = entry.entry._edata.filename;
					} else if (typeof entry.entry._edata.description != "undefined") {
						content = entry.entry._edata.description.replace(/(<([^>]+)>)/ig,"").substr(0, 80) + "...";
					} else {
						content = "N/A";
					}

					var assessable = 0;
					if (typeof entry.entry.is_assessable != "undefined") {
						if ("1" == entry.entry.is_assessable) {
							assessable = 1;
						}
					}

					var commentBlock;
                    if (typeof entry.comments != "undefined" && entry.comments.length > 0) {
						commentBlock = jQuery(document.createElement("div"));
						jQuery.each(entry.comments, function (k, comment) {
							commentBlock.append("<blockquote>" + comment.comment + "<small>" + "<b>" + comment.commentor + "</b>" + " " + comment.submitted_date + "</small>" + "</blockquote>");
						});
					}

					jQuery(entry_li_h3).addClass("card-title clearfix muted");
					jQuery(entry_li_h3).append("<i class=\"fa fa-edit\"></i> ").append(entry_li_a);
					jQuery(entry_li_a).attr({"href": "#", "data-toggle": "modal", "data-target": "#portfolio-modal", "data-artifact": pfartifact_id, "data-entry": entry.entry.pentry_id, "data-type": entry.entry.type})
										.addClass("edit-entry")
										.html(content);
					jQuery(entry_delete_a).attr({"href": "#", "data-toggle": "modal", "data-target": "#portfolio-modal", "data-id": entry.entry.pentry_id}).addClass("delete-entry").addClass("btn btn-danger pull-right").html("<i class=\"icon-trash icon-white\"></i>");
					jQuery(entry_li_h3).append(entry_delete_a);

					jQuery(entry_link_a).addClass("btn btn-success");
					switch (entry.entry.type) {
						case "reflection":
							jQuery(entry_link_a).attr({"href" : ENTRADA_URL + "/profile/eportfolio?section=reflection&entry_id=" + entry.entry.pentry_id}).html("<i class=\"fa fa-file-text\"></i> " + eportfolio_index_localization.read_reflection);
							break;
						case "file":
							jQuery(entry_link_a).attr({"href" : ENTRADA_URL + "/serve-eportfolio-entry.php?entry_id=" + entry.entry.pentry_id}).html("<i class=\"fa fa-download\"></i> " + eportfolio_index_localization.download_file);
							break;
						case "url":
							if (entry.entry._edata.description) {
								jQuery(entry_link_a).attr({
									"href": entry.entry._edata.description,
									"target": "_blank"
								}).html("<i class=\"fa fa-share\"></i> " + eportfolio_index_localization.visit_url);
							}
							break;
					}

					var assessable_text = (assessable) ? eportfolio_index_localization.used_for_assessment : eportfolio_index_localization.not_used_for_assessment;
					var assessable_class = (assessable) ? "btn-info" : "btn-warning";
					jQuery(entry_assessable).attr({"href" : ENTRADA_URL, "data-id" : entry.entry.pentry_id, "data-artifact-id" : pfartifact_id, "data-assessable" : assessable})
											.addClass("btn " + assessable_class + " pull-right edit-entry-assessable")
											.html("<i class=\"fa fa-edit\"></i> " + assessable_text);


					jQuery(entry_date).addClass("pull-right").html("<small>" + eportfolio_index_localization.submitted + ": <b>" + date.getFullYear() + "-" + (date.getMonth() <= 8 ? "0" : "") + (date.getMonth() + 1) + "-" +  (date.getDate() <= 9 ? "0" : "") + date.getDate() + "</b></small>");

					jQuery(entry_metadata).addClass("clearfix");
					jQuery(entry_metadata).append(entry_date);

					if (0 < entry.entry.reviewed_date) {
						jQuery(entry_metadata).append("<span class=\"text-info\"><i class=\"fa fa-check-square-o\"></i> <small>" + eportfolio_index_localization.reviewed_by_my_advisor + "</small></span>");
						//console.log(entry);
					}
					if (1 == entry.entry.flag) {
						jQuery(entry_metadata).append(" &nbsp; <span class=\"text-error\"><i class=\"fa fa-flag\"></i> <small>" + eportfolio_index_localization.flagged_by_my_advisor + "</small></span>");
					}

					jQuery(entry_controls).append(entry_link_a);

					if (eportfolio_index_settings.eportfolio_can_attach_to_gradebook_assessment
						&& eportfolio_index_settings.eportfolio_entry_is_assessable_set_by_learner) {
						jQuery(entry_controls).append(entry_assessable);
					}
					jQuery(entry_div).addClass("card-block").attr("style", "padding-top:0;");


					jQuery(entry_div).append(entry_metadata);
					jQuery(entry_div).append(entry_controls);
					if (commentBlock) {
						jQuery(entry_div).append(entry_hr).append(commentBlock);
					}

					jQuery(entry_well).addClass("card artifact-entry");
					jQuery(entry_well).append(entry_li_h3);
					jQuery(entry_well).append(entry_div);

					jQuery(entry_li).append(entry_well);
					jQuery("#artifact-" + pfartifact_id).append(entry_li);
				});
			} else {
				// Create error row and cell
			}
		},
		error: function(data) {
			jQuery(".artifact-container").removeClass("loading");
			jQuery(".artifact .row-fluid, .artifact .btn-group").remove();
			display_error([eportfolio_index_localization.error_fetching_artifact_entries + ". " + eportfolio_index_localization.please_try_again + "."], ".artifact", "append");
		}
	});
}

function artifactForm(folders, selected_folder_id) {
	selected_folder_id = selected_folder_id || null;
	folders = folders || [];

    // Create the divs that will hold the form controls for the create artifact form
	var title_control_group = document.createElement("div");
	var title_controls = document.createElement("div");
	var folder_control_group = document.createElement('div');
	var folder_controls = document.createElement('div');
	var description_control_group = document.createElement("div");
	var description_controls = document.createElement("div");
	
	// Create the form elements
	var title_input = document.createElement("input");
	var folder_select = document.createElement('select');
	var description_textarea = document.createElement("textarea");
	
	// Create element labels
	var title_label = document.createElement("label");
	var folder_label = document.createElement('label');
	var description_label = document.createElement("label");
	
	jQuery(title_control_group).addClass("control-group");
	jQuery(title_controls).addClass("controls");
	jQuery(folder_control_group).addClass('control-group');
	jQuery(folder_controls).addClass('controls');
	jQuery(description_control_group).addClass("control-group");
	jQuery(description_controls).addClass("controls");

	jQuery(title_input).attr({type: "text", name: "title", id: "artifact-title"}).addClass("input-large");
	jQuery(folder_select).attr({name: 'pfolder_id', id: 'artifact-folder'}).addClass('input-large');
	jQuery(description_textarea).attr({name: "description", id: "artifact-description"}).addClass("input-large");

	jQuery(title_label).html("Title<br /><em class=\"content-small muted\">required</em>").attr("for", "artifact-title").addClass("control-label form-required");
	jQuery(folder_label).html('Folder<br /><em class=\"content-small muted\">required</em>').attr('for', 'artifact-folder').addClass("control-label form-required");
	jQuery(description_label).html("Description:<br /><em class=\"content-small muted\">required</em>").attr("for", "artifact-description").addClass("control-label form-required");

	// Populate the folder select
	for(var i in folders) {
		if(folders.hasOwnProperty(i)) {
            var $option = jQuery('<option />');

            $option.val(folders[i].pfolder_id);
            $option.html(folders[i].title);

            if(folders[i].pfolder_id == selected_folder_id) {
            	$option.attr('selected', true);
			}

            jQuery(folder_select).append($option);
		}
	}

	// Put it all together
	jQuery(title_controls).append(title_input);
	jQuery(title_control_group).append(title_label).append(title_controls);
	jQuery(folder_controls).append(folder_select);
	jQuery(folder_control_group).append([folder_label, folder_controls]);
	jQuery(description_controls).append(description_textarea);
	jQuery(description_control_group).append(description_label).append(description_controls);
	jQuery("#portfolio-form").append(title_control_group).append(folder_control_group).append(description_control_group);
}

function entryForm (pfartifact_id) {
	// Create the divs that will hold the form controls for the create artifact form
	var title_control_group = document.createElement("div");
	var title_controls = document.createElement("div");
	var title_input = document.createElement("input");
	var title_label = document.createElement("label");
	
	jQuery(title_control_group).addClass("control-group");
	jQuery(title_controls).addClass("controls");
	jQuery(title_input).attr({type: "text", name: "title", id: "media-entry-title"}).addClass("input-large");
	jQuery(title_label).html("Title").attr("for", "media-entry-title").addClass("control-label");
	
	// Create the elements for the enrty type selectbox
	var type_control_group = document.createElement("div");
	var type_controls = document.createElement("div");
	var type_label = document.createElement("label");
	var type_select = document.createElement("select");
	var type_option = document.createElement("option");
	var type_reflection = document.createElement("option");
	var type_file = document.createElement("option");
	var type_url = document.createElement("option");
	
	// Put the elements together and then append them to the #portfolio-form
	jQuery(".modal-header h3").html("Add Entry");
	jQuery(type_control_group).addClass("control-group");
	jQuery(type_controls).addClass("controls");
	jQuery(type_label).html("Entry Type").addClass("control-label");
	jQuery(type_option).html("-- Select an Entry Type --");
	jQuery(type_reflection).attr({"value": "reflection"}).html("Reflection");
	jQuery(type_file).attr({"value": "file"}).html("File");
	jQuery(type_url).attr({"value": "url"}).html("Url");
	jQuery(type_select).attr({"id": "entry-type-select"}).append(type_option).append(type_reflection).append(type_file).append(type_url);
	jQuery(type_controls).append(type_select);
	jQuery(type_control_group).append(type_label).append(type_controls);
	jQuery("#portfolio-form").append(type_control_group);
}

function appendArtifact (pfartifact_id, artifact_title, artifact_description, artifact_due, total_entries, has_entry, proxy_id) {
	if (has_entry) {
		// Elements for artifact-entries-list
		var artifact_div = document.createElement("div");
		var artifact_title_h2 = document.createElement("h2");
		var artifact_description_element = document.createElement("div");
		var artifact_due_element = document.createElement("small");
		var artifact_list = document.createElement("ul");
		var artifact_add_entry_link = document.createElement("a");

		if (artifact_due > 0) {
			var artifact_due_date  = new Date(artifact_due  * 1000);
		}
		
		jQuery(artifact_list).attr({"id": "artifact-" + pfartifact_id}).addClass("unstyled");

		jQuery(artifact_title_h2).addClass("card-title").html(artifact_title);

		jQuery(artifact_add_entry_link).html("<i class=\"fa fa-plus\"></i> Add Entry").addClass("btn btn-outline-primary artifact").attr({"data-id": pfartifact_id, "href": "#", "data-toggle": "modal", "data-target": "#portfolio-modal"});
		jQuery(artifact_add_entry_link).wrap("<p></p>");

		jQuery(artifact_description_element).html(artifact_description).addClass("artifact-description");
		if (artifact_due_date) {
			jQuery(artifact_due_element).addClass("pull-right text-warning").html(eportfolio_index_localization.due + ": <b>" + artifact_due_date.getFullYear() + "-" + (artifact_due_date.getMonth() <= 8 ? "0" : "") + (artifact_due_date.getMonth() + 1) + "-" +  (artifact_due_date.getDate() <= 9 ? "0" : "") + artifact_due_date.getDate() + "</b>");
			jQuery(artifact_title_h2).append(artifact_due_element);
		}
		jQuery(artifact_div).attr({"data-id": pfartifact_id}).append(artifact_title_h2).append(artifact_description_element).append(artifact_add_entry_link).append(artifact_list).addClass("artifact-group card card-block");
		jQuery("#artifact-container").append(artifact_div);
	}
}

function appendContent (type, jsonResponse, pfartifact_id, pfolder_id) {
	if (jQuery("#display-notice-box-modal").length) {
		jQuery("#msgs").empty();
	}
	// ToDo: pretty sure this block can go
	/*
	switch (type) {
		case "artifact" :
			display_success(["Successfully created artifact titled <strong>" + jsonResponse.title + "</strong>"], "#msgs", "append");
		break;
		case "delete-artifact" :
			display_success(["Successfully removed artifact titled <strong>" + jsonResponse.title + "</strong>"], "#msgs", "append");
		break;
		case "artifact-edit" :
			jQuery("span[data-artifact="+ jsonResponse.pentry_id + "]").html(jsonResponse.title);
		break;
		case "reflection" :
		case "url" :
			if (jQuery("#msgs .alert-info").length) {
				jQuery("#msgs .alert-info").remove();
			} 
			
			if (jQuery(".edit-entry[data-entry="+ jsonResponse.pentry_id +"]").length) {
				jQuery(".edit-entry[data-entry="+ jsonResponse.pentry_id +"]").html(jQuery("#entry-title").val());
			} else {
				if (!jQuery("#artifact-" + pfartifact_id).length) {
					// Elements for artifact-entries-list
					var artifact_div = document.createElement("div");
					var artifact_title_h2 = document.createElement("h2");
					var artifact_list = document.createElement("ul");

					jQuery(artifact_list).attr({"id": "artifact-" + pfartifact_id}).addClass("unstyled");
					jQuery(artifact_title_h2).html(jQuery("a[data-id=" + pfartifact_id + "] span").html());
					jQuery(artifact_div).attr({"data-id": pfartifact_id}).append(artifact_title_h2).append(artifact_list).addClass("artifact-group");
					jQuery("#artifact-container").append(artifact_div);
				}
				
				var content = "";
				var entry_li = document.createElement("li");
				var entry_li_a = document.createElement("a");
				var entry_delete_a = document.createElement("a");
                var entry_link = document.createElement("a");
                var entry_link_icon = document.createElement("i");
				var entry_div = document.createElement("div");
				var date = new Date(jsonResponse.submitted_date * 1000);

				if (typeof jsonResponse.edata.title != "undefined") {
					content = jsonResponse.edata.title;
				} else if (typeof jsonResponse.edata.filename != "undefined") {
					content = jsonResponse.edata.filename;
				} else if (typeof jsonResponse.edata.description != "undefined") {
					content = jsonResponse.edata.description.replace(/(<([^>]+)>)/ig,"").substr(0, 80) + "...";
				} else {
					content = "N/A";
				}

				jQuery(entry_li_a).attr({"href": "#", "data-toggle": "modal", "data-target": "#portfolio-modal", "data-artifact": pfartifact_id, "data-entry": jsonResponse.pentry_id, "data-type": jsonResponse.type}).html(content).addClass("edit-entry");
				jQuery(entry_div).html("Submitted: " + date.getFullYear() + "-" + (date.getMonth() <= 8 ? "0" : "") + (date.getMonth() + 1) + "-" +  (date.getDate() <= 8 ? "0" : "") + date.getDate() + ", Entry Type: " + jsonResponse.type).addClass("muted");
                jQuery(entry_link_icon).addClass("icon-link");
                jQuery(entry_link).attr({"href" : ENTRADA_URL + "/profile/eportfolio?section=reflection&entry_id=" + jsonResponse.pentry_id}).append(entry_link_icon);
				jQuery(entry_delete_a).attr({"href": "#", "data-toggle": "modal", "data-target": "#portfolio-modal", "data-id": jsonResponse.pentry_id}).addClass("delete-entry").html("<i class=\"icon-trash\"></i>");
				jQuery(entry_li).append(entry_li_a).append(entry_delete_a).append(entry_link).append(entry_div);
				jQuery("#artifact-" + pfartifact_id).append(entry_li);
				display_success(["Successfully added an entry titled <strong>" + jsonResponse.edata.title + "</strong>"], "#msgs", "append");
			}
		break;
		case "file" :
			if (jQuery(".edit-entry[data-entry="+ jsonResponse.pentry_id +"]").length) {
				jQuery(".edit-entry[data-entry="+ jsonResponse.pentry_id +"]").html(jQuery("#entry-title").val());
			}
		break;
		case "delete-entry" :
			jQuery("a.edit-entry[data-entry="+ jsonResponse.pentry_id + "]").parent().remove();
			
			if (jQuery("#artifact-" +jsonResponse.pfartifact_id).is(":empty")) {	
				jQuery(".artifact-group[data-id="+ jsonResponse.pfartifact_id +"]").remove();
			}
			
			if (!jQuery(".artifact-group").length) {
				display_generic(["There are currently no portfolio artifacts with entries attached to them. To add an entry to an artifact, select an artifact from the <strong>My Artifacts</strong> list."], "#msgs", "append");
			}
			
			if (jQuery("#msgs .alert-success").length) {
				jQuery("#msgs .alert-success").remove();
			}

			display_success(["Successfully removed entry titled: <strong>" + jsonResponse._edata.title + "</strong>"], "#msgs", "append");
		break;
	}
	*/
	//updateArtifactList(pfolder_id);
	getFolderArtifacts(pfolder_id);
}

function populateEntryForm(pentry_id) {
	jQuery.ajax({
		url: ENTRADA_URL + "/api/eportfolio.api.php",
		data: "method=get-entry&pentry_id=" + pentry_id,
		type: 'GET',
		async: false,
		success: function (data) {
			var jsonResponse = JSON.parse(data);
			if (jsonResponse.status === "success") {
				jQuery(".modal-header h3").html("Edit Entry");
				switch (jsonResponse.data.type) {
					case "reflection" :
						jQuery("#entry-title").val(jsonResponse.data._edata.title);
						jQuery("#reflection-body").val(jsonResponse.data._edata.description);
					break;
					case "file" :
						jQuery("#entry-title").val(jsonResponse.data._edata.title);
						jQuery("#entry-description").val(jsonResponse.data._edata.description);
						jQuery("#entry-filename").val(jsonResponse.data._edata.filename);
					break;
					case "url" :
						jQuery("#entry-title").val(jsonResponse.data._edata.title);
						jQuery("#entry-description").val(jsonResponse.data._edata.description);
						jQuery("#url-text").html("<i class=\"icon-bookmark\"></i>" + jsonResponse.data._edata.description).attr({"href": jsonResponse.data._edata.description});
					break;
				}
			} else {
				display_error(jsonResponse.data, "#modal-msg", "append");
			}
		},
		error: function (data) {
			jQuery(".artifact-container").removeClass("loading");
			display_error([eportfolio_index_localization.error_fetching_entry + ". " + eportfolio_index_localization.please_try_again + "."], "#modal-msg", "append");
		}
	});
}

function populateArtifactForm (pfartifact_id) {
	artifactForm();
	jQuery.ajax({
		url: ENTRADA_URL + "/api/eportfolio.api.php",
		data: "method=get-folder-artifact&pfartifact_id=" + pfartifact_id,
		type: 'GET',
		async: false,
		success: function (data) {
			var jsonResponse = JSON.parse(data);
			if (jsonResponse.status == "success") {
				jQuery("#artifact-title").val(jsonResponse.data.title);
				jQuery("#artifact-description").val(jsonResponse.data.description);
			} else {
				display_error(jsonResponse.data, "#modal-msg", "append");
			}
		},
		error: function (data) {
			jQuery(".artifact-container").removeClass("loading");
			display_error([eportfolio_index_localization.error_fetching_artifact + ". " + eportfolio_index_localization.please_try_again + "."], "#modal-msg", "append");
		}
	});	
}

function populateDeleteForm (pentry_id, entry_title) {
	var warning_div = document.createElement("div");
	var warning_ul = document.createElement("ul");
	var warning_button = document.createElement("button");
	var warning_li = document.createElement("li");
	
	jQuery(warning_div).addClass("alert alert-block alert-danger");
	jQuery(warning_button).addClass("close").html("&times;");
	jQuery(warning_li).html(eportfolio_index_localization.confirm_remove_entry + " <strong>" + entry_title + "</strong>.");
	jQuery(warning_ul).append(warning_li);
	jQuery(warning_div).append(warning_button).append(warning_ul);
	jQuery("#portfolio-form").append(warning_div);
	jQuery(".modal-header h3").html(eportfolio_index_localization.confirm_entry_removal);
}

// ToDo: is this function called anywhere?
function appendEntry(entry) {
	var entry_li = document.createElement("li");
	var entry_li_a = document.createElement("a");
	var entry_div = document.createElement("div");
	
	if (entry.submitted_date != 0) {
		var date = new Date(entry.submitted_date * 1000);
		var date_string = eportfolio_index_localization.due + ": " + date.getFullYear() + "-" + (date.getMonth() <= 9 ? "0" : "") + (date.getMonth() + 1) + "-" +  (date.getDate() <= 9 ? "0" : "") + date.getDate()
	} else {
		var date_string = eportfolio_index_localization.due + ": " + eportfolio_index_localization.na;
	}
	
	jQuery(entry_div).html(eportfolio_index_localization.type + ": <strong>" + entry.type + "</strong>, " + eportfolio_index_localization.submitted + ": <strong>" + date_string + "</strong>").addClass("muted");
	jQuery(entry_li_a).attr({"href": "#"}).html((entry._edata.hasOwnProperty("title") ? entry._edata.title : (entry._edata.hasOwnProperty("description") && entry._edata.description.length ? entry._edata.description.replace(/(<([^>]+)>)/ig,"").substr(0, 80) : entry._edata.filename)));
	jQuery(entry_li).append(entry_li_a).append(entry_div);
	jQuery("#entries-list").append(entry_li);
}

function buildEntryForm(entry_type, pentry_id, edit_mode) {
	var title_fieldset = document.createElement("fieldset");
	var title_control_group = document.createElement("div");
	var title_controls = document.createElement("div");
	var title_input = document.createElement("input");
	var title_label = document.createElement("label");
	
	jQuery(title_controls).addClass("controls");
	jQuery(title_input).attr({type: "text", name: "title", id: "entry-title"}).addClass("input-large");
	jQuery(title_label).html(eportfolio_index_localization.title).attr("for", "entry-title").addClass("control-label");
	jQuery(title_controls).append(title_input);
	jQuery(title_control_group).addClass("control-group").append(title_label).append(title_controls);
	jQuery(title_fieldset).append(title_control_group);
	jQuery("#portfolio-form").append(title_fieldset);
	
	switch (entry_type) {
		case "reflection" :
			var reflection_fieldset = document.createElement("fieldset");
			var reflection_control_group = document.createElement("div");
			var reflection_controls = document.createElement("div");
			var reflection_textarea = document.createElement("textarea");
			var reflection_label = document.createElement("label");
			
			jQuery(reflection_control_group).addClass("control-group");
			jQuery(reflection_controls).addClass("controls");
			jQuery(reflection_label).attr({"for": "reflection-body"}).html(eportfolio_index_localization.reflection_body).addClass("control-label");
			jQuery(reflection_textarea).attr({"name": "description", "id": "reflection-body"}).ckeditor();
			jQuery(reflection_controls).append(reflection_textarea);
			jQuery(reflection_control_group).append(reflection_label).append(reflection_controls);
			jQuery(reflection_fieldset).append(reflection_control_group);
			jQuery("#portfolio-form").append(reflection_fieldset);
		break;
		case "file" :
			var file_description_fieldset = document.createElement("fieldset");
			var file_description_control_group = document.createElement("div");
			var file_description_controls = document.createElement("div");
			var file_description_label = document.createElement("label");
			var file_description = document.createElement("textarea");
			
			if (edit_mode) {
				var file_download_fieldset = document.createElement("fieldset");
				var file_download_control_group = document.createElement("div");
				var file_download_controls = document.createElement("div");
				var file_download_label = document.createElement("label");
				var file_download_a = document.createElement("a");
				var file_filename_input = document.createElement("input");
				
				jQuery(file_download_control_group).addClass("control-group");
				jQuery(file_download_controls).addClass("controls");
				jQuery(file_download_label).html("eportfolio_index_localization.download_file").addClass("control-label");
				jQuery(file_download_a).html("<i class=\"icon-download-alt icon-white\"></i> " + eportfolio_index_localization.download_file).attr("href", ENTRADA_URL + "/serve-eportfolio-entry.php?entry_id=" + pentry_id).addClass("btn btn-success");
				jQuery(file_download_controls).append(file_download_a);
				jQuery(file_filename_input).attr({"type": "hidden", "id": "entry-filename"});
				jQuery(file_download_control_group).append(file_filename_input);
				jQuery(file_download_control_group).append(file_download_label).append(file_download_controls);
				jQuery(file_download_fieldset).append(file_download_control_group);
			} else {
				var file_fieldset = document.createElement("fieldset");
				var file_control_group = document.createElement("div");
				var file_controls = document.createElement("div");
				var file_upload = document.createElement("input");
				var file_label = document.createElement("label");
				
				jQuery(file_control_group).addClass("control-group");
				jQuery(file_controls).addClass("controls");
				jQuery(file_upload).attr({"type": "file", "id": "file-upload"});
				jQuery(file_label).attr({"for": "file-upload"}).html(eportfolio_index_localization.attach_file).addClass("control-label");
				jQuery(file_controls).append(file_upload);
				jQuery(file_control_group).append(file_label).append(file_controls);
				jQuery(file_fieldset).append(file_control_group);
			}
			
			jQuery(file_description_control_group).addClass("control-group");
			jQuery(file_description_controls).addClass("controls");
			jQuery(file_description_label).attr({"for": "entry-description"}).html(eportfolio_index_localization.description).addClass("control-label");
			jQuery(file_description).attr({"name": "description", "id": "entry-description"});
			jQuery(file_description_controls).append(file_description);
			jQuery(file_description_control_group).append(file_description_label).append(file_description_controls);
			jQuery(file_description_fieldset).append(file_description_control_group);
			jQuery("#portfolio-form").append(file_description_fieldset).append(file_fieldset).append(file_download_fieldset);
		break;
		case "url" :
			var url_fieldset = document.createElement("fieldset");
			var url_control_group = document.createElement("div");
			var url_controls = document.createElement("div");
			var url_input = document.createElement("input");
			var url_label = document.createElement("label");
			
			jQuery(url_control_group).addClass("control-group");
			jQuery(url_controls).addClass("controls");
			jQuery(url_input).attr({"name": "description", "id": "entry-description", "type": "text"});
			jQuery(url_label).attr({"for": "entry-description"}).html(eportfolio_index_localization.url).addClass("control-label");
			jQuery(url_controls).append(url_input);
			jQuery(url_control_group).append(url_label).append(url_controls);
			jQuery(url_fieldset).append(url_control_group);
			jQuery("#portfolio-form").append(url_fieldset);
			
			if (edit_mode) {
				var url_text_fieldset = document.createElement("fieldset");
				var url_text_control_group = document.createElement("div");
				var url_text_controls = document.createElement("div");
				var url_text_a = document.createElement("a");
				
				jQuery(url_text_control_group).addClass("control-group");
				jQuery(url_text_controls).addClass("controls");
				jQuery(url_text_a).attr({"target": "_BLANK", "id": "url-text"});
				jQuery(url_text_controls).append(url_text_a);
				jQuery(url_text_control_group).append(url_text_controls);
				jQuery(url_text_fieldset).append(url_text_control_group);
				jQuery("#portfolio-form").append(url_text_fieldset);
			}
		break;
	}
}

// ToDo: is this function called anywhere?
function appendArtifactItem(artifact) {
	var artifact_item = document.createElement("li");
	var artifact_item_a = document.createElement("a");
	var artifact_due = document.createElement("div");
	var artifact_title_span = document.createElement("span");

	jQuery(artifact_title_span).html(artifact.title);
	jQuery(artifact_due).html("<span class=\"badge\">0</span> " + eportfolio_index_localization.due + ": " +eportfolio_index_localization.na).addClass("muted");
	jQuery(artifact_item_a).attr({"href": "#", "data-id": artifact.pentry_id, "data-toggle": "modal", "data-target": "#portfolio-modal"}).append(artifact_title_span).append(artifact_due).css("padding-bottom", "8px").addClass("artifact");
	jQuery(artifact_item).append(artifact_item_a).addClass("artifact-list-item");
	jQuery("#entries-user").after(artifact_item);
	
	if (jQuery(".entries-user-error").length) {
		jQuery(".entries-user-error").remove();
	}
}

function updateArtifactList (pfolder_id) {
	var proxy_id = PROXY_ID;

	if (jQuery(".artifact-list-item").length) {
		jQuery(".artifact-list-item").remove();
	}
	
	jQuery.ajax({
		url: ENTRADA_URL + "/api/eportfolio.api.php",
		data: "method=get-folder-artifacts&pfolder_id=" + pfolder_id + "&proxy_id=" + proxy_id,
		type: 'GET',
		success: function (data) {
			var jsonResponse = JSON.parse(data);
			if (jsonResponse.status == "success") {
				jQuery.each(jsonResponse.data, function (key, artifact) {
					// Elements for My Artifacts list
					var artifact_li = document.createElement("li");
					var artifact_li_a = document.createElement("a");
					var artifact_li_a_span = document.createElement("span");
					var artifact_li_div = document.createElement("div");
					var date_string = "";
					var artifact_due = artifact.finish_date;

					if (artifact_due != 0) {
						var date = new Date(artifact_due * 1000);
						date_string = eportfolio_index_localization.due + ": " + date.getFullYear() + "-" + (date.getMonth() <= 9 ? "0" : "") + (date.getMonth() + 1) + "-" +  (date.getDate() <= 9 ? "0" : "") + date.getDate();
					} else {
						date_string = eportfolio_index_localization.due + ": " + eportfolio_index_localization.na;
					}

					if (artifact.has_entry || date_string == eportfolio_index_localization.due + ": " + eportfolio_index_localization.na) {
						jQuery(artifact_li_div).html((artifact.total_entries > 0 ? "<span class=\"badge badge-info\">" + artifact.total_entries + "</span> " + date_string : "<span class=\"badge\">" + artifact.total_entries + "</span> " + date_string )).addClass("muted");
					} else {
						var warning_span = document.createElement("span");

						jQuery(artifact_li_a_span).addClass("artifact-meta-warning").addClass("text-error");
						jQuery(warning_span).html(date_string).addClass("label label-important");
						jQuery(artifact_li).addClass("artifact-due-warning");
						jQuery(artifact_li_div).append(warning_span);
					}

					jQuery(artifact_li_a_span).html(artifact.title);
					jQuery(artifact_li_a).attr({"data-id": artifact.pfartifact_id, "href": "#", "data-toggle": "modal", "data-target": "#portfolio-modal"}).append(artifact_li_a_span).append(artifact_li_div).addClass("artifact").css("padding-bottom", "8px");
					jQuery(artifact_li).append(artifact_li_a).addClass("artifact-list-item");

					if (artifact.proxy_id != PROXY_ID) {
						if (!artifact.has_entry) {
							jQuery(artifact_li).addClass("entries-required");
							jQuery("#entries-required").after(artifact_li);
							if (jQuery(".entries-required-error").length) {
								jQuery(".entries-required-error").remove();
							}
						} else {
							jQuery(artifact_li).addClass("entries-attached");
							jQuery("#entries-attached").after(artifact_li);

							if (jQuery(".entries-attached-error").length) {
								jQuery(".entries-attached-error").remove();
							}
						}
					} else {
						jQuery(artifact_li).addClass("entries-user").append("<span class=\"label label-important remove-artifact\" data-toggle=\"modal\" data-target=\"#portfolio-modal\" data-id=\""+artifact.pfartifact_id+"\"><i class=\"icon-trash icon-white\"></i></span>");
						jQuery("#entries-user").after(artifact_li);
					}
				});

			} else {
				jQuery("#msgs").empty();
				display_notice([jsonResponse.data], "#msgs");
			}

			if (!jQuery(".entries-required").length) {
				var error_required_li = document.createElement("li");
				jQuery(error_required_li).html(eportfolio_index_localization.no_artifacts_require_entries).addClass("artifact-list-item muted entries-required-error").css("padding", "8px 10px");
				jQuery("#entries-required").after(error_required_li);
			}

			if (!jQuery(".entries-attached").length) {
				var error_attached_li = document.createElement("li");
				jQuery(error_attached_li).html(eportfolio_index_localization.no_artifacts_attached_entries + ".").addClass("artifact-list-item muted entries-attached-error").css("padding", "8px 10px");
				jQuery("#entries-attached").after(error_attached_li);
			}

			if (!jQuery(".entries-user").length) {
				// Only show learner-created artifact error message if allowed for this folder
				if ( 1 == jsonResponse.folder.allow_learner_artifacts ) {
					var error_user_li = document.createElement("li");
					jQuery(error_user_li).html(eportfolio_index_localization.no_artifacts_for_folder + ".").addClass("artifact-list-item muted entries-user-error").css("padding", "8px 10px");
					jQuery("#entries-user").after(error_user_li);
					jQuery("#entries-user").show();
				} else {
					jQuery("#entries-user").hide();
				}
			}
		},
		error: function () {
			jQuery(".artifact-container").removeClass("loading");
			display_error([eportfolio_index_localization.error_fetching_folder_artifacts + ". " + eportfolio_index_localization.please_try_again + "."], "#msgs", "append");
		}
	});
}