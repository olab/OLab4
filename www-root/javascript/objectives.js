var EDITABLE = false;
var loaded = [];
var loading_objectives = false;
var linked_objective_id = 0;
jQuery(document).ready(function(){
	jQuery("#curriculum-tags-section").on("click", ".objective-link-control", function(e) {
		linked_objective_id = jQuery(this).attr("data-id");
		var objective_id = jQuery(this).attr("data-id");
		var linked_objective_list = jQuery(document.createElement("ul"));
		var modal_data = jQuery(document.createElement("div"));
		jQuery.ajax({
			url: SITE_URL + "/admin/settings/manage/objectives?org=" + org_id + "&section=edit&id=" + objective_id + "&mode=ajax",
			type: "POST",
			data: "method=fetch-linked-objectives&objective_set_id=" + objective_set_id,
			async: false,
			success: function(data) {
				modal_data.append(data);
			}
		});
		
		var modal = jQuery("#objective-link-modal");
		modal.append(modal_data);
		modal.dialog({
			title: "Link Curriculum Tag",
			modal: true,
			draggable: false,
			resizable: false,
			width: 700,
			minHeight: 550,
			maxHeight: 700,
			dialogClass: "fixed",
			buttons: {
				Close : function() {
					jQuery(this).dialog( "close" );
				}
			},
			close: function(event, ui) {
				modal.html("");
				jQuery(this).dialog( "destroy" );
			}
		});
		e.preventDefault();
	});
	
	jQuery("#objective-link-modal").on("click", "#objective-link-modal .objective", function(e) {
		
		var objective_id = jQuery(this).attr("data-id");
		var clicked = jQuery(this);
		if (clicked.hasClass("expanded")) {
			clicked.siblings(".children").html("");
			clicked.removeClass("expanded");
		} else {
			clicked.addClass("expanded");
			jQuery.ajax({
				url: SITE_URL + "/api/fetchobjectives.api.php?objective_id=" + objective_id + "&org_id=" + (typeof org_id !== 'undefined' && org_id ? org_id : default_org_id),
				type: "POST",
				data: "method=fetch-linked-objectives",
				async: false,
				success: function(data) {
					var objective_list = jQuery(document.createElement("ul"));
					var jsonData = JSON.parse(data);
					for (var i = 0; i < jsonData.length; i++) {
						var objective_list_item = jQuery(document.createElement("li"));
						var map_input = "";

						var checked = "";
						if (jQuery("#currently-linked-objectives li[data-id="+jsonData[i].objective_id+"]").length > 0) {
							checked = "checked=\"checked\"";
						}	
						map_input = "<input class=\"objective-check\" type=\"checkbox\" name=\"\" data-id=\""+jsonData[i].objective_id+"\" " + checked + " />";

						if (jsonData[i].has_child == true) {
							var objective_link = jQuery(document.createElement("a"));
						} else {
							var objective_link = jQuery(document.createElement("span"));
						}
						objective_link.addClass("objective").attr("href", "#").attr("data-id", jsonData[i].objective_id).html(jsonData[i].objective_name + (jsonData[i].has_child == true ? " <i class=\"icon-chevron-down\"></i>" : ""));
						objective_list_item.html(map_input + " ").append(objective_link).append("<div class=\"children\"></div>");
						objective_list.append(objective_list_item);
					}
					clicked.siblings(".children").append(objective_list);
				}
			});
		}
		e.preventDefault();
	});
	
	jQuery("#objective-link-modal").on("click", "#objective-link-modal .objective-check", function(e) {
		
		var checkbox = jQuery(this);
		var target_objective_id = jQuery(this).attr("data-id");
		var action = "unlink";
		if (checkbox.is(":checked")) {
			action = "link";
		}
		
		jQuery.ajax({
			url: SITE_URL + "/admin/settings/manage/objectives?org=" + org_id + "&section=edit&id=" + linked_objective_id + "&target_objective_id=" + target_objective_id + "&mode=ajax",
			type: "POST",
			data: "method=link-objective&action="+action,
			async: false,
			success: function(data) {
				var jsonResponse = JSON.parse(data);
				if (jsonResponse.status == "success") {
					if (jsonResponse.data.action == "link") {
						var objectiveListItem = jQuery(document.createElement("li"));
						objectiveListItem.attr("data-id", jsonResponse.data.target_objective_id);
						objectiveListItem.html("<strong>" + jsonResponse.data.objective_name + "</strong><a href=\"#\" class=\"unlink\"><i class=\"icon-trash\"></i></a>" + (jsonResponse.data.parent_objective != null ? "<br /><small class=\"content-small\">From " + jsonResponse.data.parent_objective + "</small>" : "") + (jsonResponse.data.objective_description != null ? "<br />" + jsonResponse.data.objective_description : ""));
						jQuery("#currently-linked-objectives").append(objectiveListItem);
						if (jQuery("#currently-linked-objectives .no-objectives").length > 0) {
							jQuery("#currently-linked-objectives .no-objectives").remove();
						}
					} else {
						jQuery("#currently-linked-objectives li[data-id='" + jsonResponse.data.target_objective_id + "']").remove();
						if (jQuery("#currently-linked-objectives li").length <= 0) {
							jQuery("#currently-linked-objectives").append("<li class=\"no-objectives\">This objective is not currently linked to any other objectives.</li>");
						}
					}
				}
			}
		});
		
	});
	
	jQuery("#objective-link-modal").on("click", "#objective-link-modal .unlink", function(e) {
		
		var target_objective_id = jQuery(this).parent().attr("data-id");
		var action = "unlink";
		
		jQuery.ajax({
			url: SITE_URL + "/admin/settings/manage/objectives?org=" + org_id + "&section=edit&id=" + linked_objective_id + "&target_objective_id=" + target_objective_id + "&mode=ajax",
			type: "POST",
			data: "method=link-objective&action="+action,
			async: false,
			success: function(data) {
				var jsonResponse = JSON.parse(data);
				if (jsonResponse.status == "success") {
					jQuery("#currently-linked-objectives li[data-id='" + jsonResponse.data.target_objective_id + "']").remove();
					if (jQuery("#currently-linked-objectives li").length <= 0) {
						jQuery("#currently-linked-objectives").append("<li class=\"no-objectives\">This objective is not currently linked to any other objectives.</li>");
					}
					if (jQuery(".objective-check[data-id='" + jsonResponse.data.target_objective_id + "']").is(":checked")) {
						jQuery(".objective-check[data-id='" + jsonResponse.data.target_objective_id + "']").attr("checked", false);
					}
				}
			}
		});

		e.preventDefault();
	});
	
	jQuery(document).on('click', '.objective-collapse-control', function(){
		var id = jQuery(this).attr('data-id');
		if(jQuery('#children_'+id).is(':visible')){
			jQuery('#children_'+id).slideUp();
		}else if(loaded[id] === undefined || !loaded[id]){
			jQuery('#objective_title_'+id).trigger('click');
		}else{
			jQuery('#children_'+id).slideDown();
		}
	});

	jQuery('#curriculum-tags-section, #assessment-objectives-section, #course-objectives-section, #event-objectives-section, #exam-objectives-section').on('click', '.objective-title', function() {
		var id = jQuery(this).attr('data-id');
		if (loaded[id] === undefined || !loaded[id]) {
			var query = {'objective_id' : id, 'org_id' : (typeof org_id !== 'undefined' && org_id ? org_id : default_org_id)};

            if (typeof by_course_id !== 'undefined' && by_course_id) {
                query['course_id'] = by_course_id;
            } else if (typeof by_assessment_id !== 'undefined' && by_assessment_id) {
                query['assessment_id'] = by_assessment_id;
            }

            if (jQuery("#event-objectives-section").length > 0) {
                if(jQuery('#mapped_objectives').length>0){
                    var type = jQuery('#mapped_objectives').attr('data-resource-type');
                    var value = jQuery('#mapped_objectives').attr('data-resource-id');
                    if(type && value){
                        if (type != 'evaluation_question') {
                            query[type+'_id'] = value;
                        } else if (jQuery('#objective_ids_string_'+value).val()) {
                            query['objective_ids'] = jQuery('#objective_ids_string_'+value).val();
                        }
                    }
                }
            }
			
			if(!loading_objectives){
				var loading = jQuery(document.createElement('img'))
									.attr('src',SITE_URL+'/images/loading.gif')
									.attr('width','15')
									.attr('title','Loading...')
									.attr('alt','Loading...')
									.attr('class','loading')
									.attr('id','loading_'+id);
				jQuery('#objective_controls_'+id).append(loading);
				loading_objectives = true;				
				jQuery.ajax({
						url:SITE_URL+'/api/fetchobjectives.api.php',
						data:query,
						success:function(data,status,xhr){								
							jQuery('#loading_'+id).remove();
							loaded[id] = jQuery.parseJSON(data);
							buildDOM(loaded[id],id);
							loading_objectives = false;
						}
					});
			}
		} else if (jQuery('#children_'+id).is(':visible')) {
			jQuery('#children_'+id).slideUp(600);	
		} else {
			if (jQuery("#objective_list_"+id).children('li').length == 0) {
				if(!EDITABLE){
                    buildDOM(loaded[id],id);
				}
			}	else {
				jQuery('#children_'+id).slideDown(600);	
			}
		}
		return false;
	});

	jQuery('#expand-all').click(function(){
		jQuery('.objective_title').each(function(){
			jQuery(this).trigger('click');
		});
	});
	
	jQuery("#curriculum-tags-section").on("click", ".objective-edit-control", function(){
		if (jQuery(".edit-objectiv-modal").length > 0) {
			jQuery(".edit-objectiv-modal").remove();
		}

		var objective_id = jQuery(this).attr("data-id");
		var modal_container = jQuery(document.createElement("div"));

		jQuery(modal_container).addClass("edit-objectiv-modal");
		modal_container.load(SITE_URL + "/admin/settings/manage/objectives?org="+org_id+"&section=edit&id=" + objective_id + "&mode=ajax");
		
		modal_container.dialog({
			title: "Edit Tag",
			modal: true,
			draggable: false,
			resizable: false,
			width: 700,
			minHeight: 550,
			maxHeight: 700,
			buttons: {
				Cancel : function() {
					jQuery(this).dialog( "close" );
				},
				Save : function() {
					var url = modal_container.find("form").attr("action");
					var closeable = true;
					jQuery.ajax({
						url: url,
						type: "POST",
						async: false,
						data: modal_container.find("form").serialize(),
						success: function(data) {
							var jsonData = JSON.parse(data);
							
							if (jsonData.status == "success") {
								
								var order = jsonData.updates.objective_order;
								var objective_parent = jsonData.updates.objective_parent;
								var data_title = ((jsonData.updates.objective_code && jsonData.updates.objective_code.length > 0) ? jsonData.updates.objective_code + ': ' : '') + jsonData.updates.objective_name;
								var list_item = jQuery("#objective_"+objective_id);
								
								jQuery("#objective_title_"+jsonData.updates.objective_id).html(data_title);
								jQuery("#description_"+jsonData.updates.objective_id).html(jsonData.updates.objective_description);
								
								jQuery("#objective_"+objective_id).remove();
							
								if (jQuery("#children_" + objective_parent + " #objective_list_" + objective_parent).children().length != order) {
									jQuery("#children_" + objective_parent + " #objective_list_" + objective_parent + " li").eq(order).before(list_item)
								} else {
									jQuery("#children_" + objective_parent + " #objective_list_" + objective_parent).append(list_item);
								}															
							} else if(jsonData.status == "error"){
								jQuery('#objective_error').html(jsonData.msg);
								jQuery('#objective_error').show();
								closeable = false;
							}							
						}																		
					});	
					if(closeable){
						jQuery(this).dialog( "close" );	
					}
				}
			},
			close: function(event, ui){
				modal_container.dialog("destroy");
				jQuery(".edit-objectiv-modal").remove();

			}
		});
		return false;
	});
	
	jQuery("#curriculum-tags-section").on("click", ".objective-add-control", function(){
		if (jQuery(".add-objectiv-modal").length > 0) {
			jQuery(".add-objectiv-modal").remove();
		}
		var parent_id = jQuery(this).attr("data-id");
		var modal_container = jQuery(document.createElement("div"));
		var url = SITE_URL + "/admin/settings/manage/objectives?org="+org_id+"&section=add&mode=ajax&parent_id="+parent_id;

		jQuery(modal_container).addClass("add-objectiv-modal");
		modal_container.load(url);
		
		modal_container.dialog({
			title: "Add Curriculum Tag",
			modal: true,
			draggable: false,
			resizable: false,
			width: 700,
			minHeight: 550,
			maxHeight: 700,
			buttons: {
				Cancel : function() {
					jQuery(this).dialog( "close" );
				},
				Add : function() {
					var url = modal_container.find("form").attr("action");
					var closeable = true;
					jQuery.ajax({
						url: url,
						type: "POST",
						async: false,
						data: modal_container.find("form").serialize(),
						success: function(data) {

							var jsonData = JSON.parse(data);
							
							if (jsonData.status == "success") {
							
                                var order = jsonData.updates.objective_order;
								var objective_parent = jsonData.updates.objective_parent;
								var data_title = ((jsonData.updates.objective_code && jsonData.updates.objective_code.length > 0) ? jsonData.updates.objective_code + ': ' : '') + jsonData.updates.objective_name;
								var list_item = jQuery(document.createElement("li"));
								list_item.addClass("objective-container")
                                    .attr("id", "objective_"+jsonData.updates.objective_id)
                                    .attr("data-id", jsonData.updates.objective_id)
                                    .attr("data-code", jsonData.updates.objective_code)
                                    .attr("data-name", jsonData.updates.objective_name)
                                    .attr("data-title", data_title)
                                    .attr("data-desc", jsonData.updates.objective_description)
                                    .append(jQuery(document.createElement("div")).attr("id", "objective_title_"+jsonData.updates.objective_id).attr("data-title", jsonData.updates.objective_name).attr("data-id", jsonData.updates.objective_id).addClass("objective-title").html(data_title))
                                    .append(jQuery(document.createElement("div")).addClass("objective-controls"))
                                    .append(jQuery(document.createElement("div")).attr("id", "description_"+jsonData.updates.objective_id).addClass("objective-description").addClass("content-small").html(jsonData.updates.objective_description))
                                    .append(
                                       jQuery(document.createElement("div")).attr("id", "children_"+jsonData.updates.objective_id).addClass("objective-children").append(
                                           jQuery(document.createElement("ul")).attr("id", "objective_list_"+jsonData.updates.objective_id).addClass("objective-list")
                                       )
                                    );
                                list_item.children(".objective-controls").append(jQuery(document.createElement("i")).addClass("objective-edit-control").addClass("icon-edit").attr("data-id", jsonData.updates.objective_id))
                                     .append(jQuery(document.createElement("i")).addClass("objective-add-control").addClass("icon-plus-sign").attr("data-id", jsonData.updates.objective_id))
                                     .append(jQuery(document.createElement("i")).addClass("objective-delete-control").addClass("icon-minus-sign").attr("data-id", jsonData.updates.objective_id))
                                     .append(jQuery(document.createElement("i")).addClass("objective-link-control").addClass("icon-link").attr("data-id", jsonData.updates.objective_id));

                                if (jQuery("#children_" + objective_parent + " #objective_list_" + objective_parent).children().length != order) {
                                    jQuery("#children_" + objective_parent + " #objective_list_" + objective_parent + " li").eq(order).before(list_item)
                                } else {
                                    jQuery("#children_" + objective_parent + " #objective_list_" + objective_parent).append(list_item);
                                }
							}  else if(jsonData.status == "error"){
								console.log("aaaaa");
								console.log(jQuery('#objective_error'));
								jQuery('#objective_error').html(jsonData.msg);
								jQuery('#objective_error').show();
								closeable = false;
							}

						}
					});
					if(closeable){
						jQuery(this).dialog( "close" );
						jQuery(modal_container).destroy();
					}
				}
			},
			close: function(event, ui){
				modal_container.dialog("destroy");
				jQuery(".add-objectiv-modal").remove();
			}
		});
		return false;
	});
	
	jQuery("#curriculum-tags-section").on("click", ".objective-delete-control", function(){
		var objective_id = jQuery(this).attr("data-id");
		var modal_container = jQuery(document.createElement("div"));
		var url = SITE_URL + "/admin/settings/manage/objectives?org="+org_id+"&section=delete&mode=ajax&objective_id="+objective_id;
		modal_container.load(url);
		
		modal_container.dialog({
			title: "Delete Curriculum Tag",
			modal: true,
			draggable: false,
			resizable: false,
			width: 700,
			minHeight: 550,
			maxHeight: 700,
			buttons: {
				Cancel : function() {
					jQuery(this).dialog( "close" );
				},
				Delete : function() {
					jQuery.ajax({
						url: modal_container.find("form").attr("action"),
						type: "POST",
						async: false,
						data: modal_container.find("form").serialize(),
						success: function(data) {
							var jsonData = JSON.parse(data);
							if (jsonData.status != "error") {
								jQuery("#objective_"+objective_id).remove();
								modal_container.dialog( "close" );
							} else {
								if (jQuery(".ui-dialog .display-generic .check-err").length <= 0) {
									jQuery(".ui-dialog .display-generic").append("<p class=\"check-err\"><strong>Please note:</strong> The checkbox below must be checked off to delete this objective and its children.</p>");
								}
							}
						}
					});
				}
			},
			close: function(event, ui){
				modal_container.dialog("destroy");
			}
		});
		return false;
	});
});

function buildDOM(children,id){
	var container,title,title_text,controls,check,d_control,e_control,a_control,m_control,description,child_container;
	jQuery('#children_'+id).hide();
	if(children.error !== undefined){
		if(!EDITABLE){
			jQuery('#check_objective_'+id).trigger('click');
            jQuery('#check_objective_'+id).trigger('change');
		}
		return false;
	}
	for(i = 0;i<children.length;i++){
		//Javascript to create DOM elements from JSON response
		var data_title = ((children[i].objective_code && children[i].objective_code.length > 0) ? children[i].objective_code + ': ' : '') + children[i].objective_name;

		container = jQuery(document.createElement('li'))
					.attr('class','objective-container draggable')
					.attr('data-id',children[i].objective_id)
					.attr('data-code',children[i].objective_code)
					.attr('data-name',children[i].objective_name)
					.attr("data-title", data_title)
					.attr('data-description',children[i].objective_description)					
					.attr('id','objective_'+children[i].objective_id);

		title = 	jQuery(document.createElement('div'))
					.attr('class','objective-title')
					.attr('id','objective_title_'+children[i].objective_id)
					.attr('data-id',children[i].objective_id)
					.attr('data-title',data_title)
					.html(data_title);

		controls = 	jQuery(document.createElement('div'))
					.attr('class','objective-controls');
						
		if(EDITABLE == true){
			e_control = jQuery(document.createElement('i'))
						.attr('class','objective-edit-control icon-edit')
						.attr('data-id',children[i].objective_id);
			a_control = jQuery(document.createElement('i'))
						.attr('class','objective-add-control icon-plus-sign')
						.attr('data-id',children[i].objective_id);	
			d_control = jQuery(document.createElement('i'))
						.attr('class','objective-delete-control icon-minus-sign')
						.attr('data-id',children[i].objective_id);
			m_control = jQuery(document.createElement('i'))
						.attr('class','objective-link-control icon-link')
						.attr('data-id',children[i].objective_id);
		} else {
			check = 	jQuery(document.createElement('input'))
						.attr('type','checkbox')
						.attr('class','checked-objective')
						.attr('id','check_objective_'+children[i].objective_id)
						.val(children[i].objective_id);
			if(children[i].mapped && children[i].mapped != 0){
				jQuery(check).prop('checked',true);
			}else if(children[i].child_mapped && children[i].child_mapped != 0){
				jQuery(check).prop('checked',true);
				jQuery(check).prop('disabled',true);
			}	
		}
		description = 	jQuery(document.createElement('div'))
						.attr('class','objective-description content-small')
						.attr('id','description_'+children[i].objective_id)
						.html(children[i].objective_description);
		child_container = 	jQuery(document.createElement('div'))
							.attr('class','objective-children')
							.attr('id','children_'+children[i].objective_id);
		child_list = 	jQuery(document.createElement('ul'))
							.attr('class','objective-list')
							.attr('id','objective_list_'+children[i].objective_id)
							.attr('data-id',children[i].objective_id);																				
		jQuery(child_container).append(child_list);			
		var type = jQuery('#mapped_objectives').attr('data-resource-type');								
		if((type != 'event' && type != 'assessment' ) || !children[i].has_child){
			jQuery(controls).append(check);
		}		
		if(EDITABLE == true){
		jQuery(controls).append(e_control)
						.append(a_control)
						.append(d_control)
						.append(m_control);
		}
		jQuery(container).append(title)
							.append(controls)
							.append(description)
							.append(child_container);
		jQuery('#objective_list_'+id).append(container);
	}	

	jQuery('#children_'+id).slideDown();

}

function selectObjective(element, parent_id, objective_id) {
    jQuery.ajax({
        url: SITE_URL + "/api/objectives-list.api.php",
        type : 'get',
        data : {'pid': parent_id, 'id': objective_id, 'organisation_id': (typeof org_id !== 'undefined' && org_id ? org_id : default_org_id)}
    }).done(function(result) {
        jQuery(element).html(result)
    });

    return;
}
function selectOrder(element, objective_id, parent_id) {
    jQuery.ajax({
        url: SITE_URL + "/api/objectives-list.api.php",
        type: 'get',
        data : {'id': objective_id, 'type': 'order', 'pid': parent_id, 'organisation_id': (typeof org_id !== 'undefined' && org_id ? org_id : default_org_id)}
    }).done(function(result) {
        jQuery(element).html(result)
    });

    return;
}
