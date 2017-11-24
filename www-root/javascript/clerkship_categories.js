var EDITABLE = false;
var loaded = [];
var loading_categories = false;
jQuery(document).ready(function(){	
    jQuery('.category-collapse-control').live('click',function(){
        var id = jQuery(this).attr('data-id');
        if(jQuery('#children_'+id).is(':visible')){
            jQuery('#children_'+id).slideUp();
        }else if(loaded[id] === undefined || !loaded[id]){
            jQuery('#category_title_'+id).trigger('click');
        }else{
            jQuery('#children_'+id).slideDown();
        }
    });


    jQuery('.category-title').live('click',function(){
        var id = jQuery(this).attr('data-id');
        var children = [];
        if (loaded[id] === undefined || !loaded[id]) {
            var query = {'category_id':id};
            if(!loading_categories){
                var loading = jQuery(document.createElement('img'))
                                    .attr('src',SITE_URL+'/images/loading.gif')
                                    .attr('width','15')
                                    .attr('title','Loading...')
                                    .attr('alt','Loading...')
                                    .attr('class','loading')
                                    .attr('id','loading_'+id);
                jQuery('#category_controls_'+id).append(loading);
                loading_categories = true;
                jQuery.ajax({
                        url:SITE_URL+'/api/fetchcategories.api.php',
                        data:query,
                        success:function(data,status,xhr){
                            jQuery('#loading_'+id).remove();
                            loaded[id] = jQuery.parseJSON(data);
                            buildDOM(loaded[id],id);
                            loading_categories = false;
                        }
                    });
            }
        } else if (jQuery('#children_'+id).is(':visible')) {
            jQuery('#children_'+id).slideUp(600);
        } else {
            // children = loaded[id];
            // buildDOM(children,id);
            if (jQuery("#category_list_"+id).children('li').length == 0) {
                if(!EDITABLE){
                    jQuery('#check_category_'+id).trigger('click');
                    jQuery('#check_category_'+id).trigger('change');
                }
            }	else {
                jQuery('#children_'+id).slideDown(600);
            }
        }
    });

    jQuery('#expand-all').click(function(){
        jQuery('.category_title').each(function(){
            jQuery(this).trigger('click');
        });
    });

    jQuery(".category-edit-control").live("click", function(){
        var category_id = jQuery(this).attr("data-id");
        var organisation_id = jQuery('#organisation_id').val();
        var modal_container = jQuery(document.createElement("div"));

        modal_container.load(SITE_URL + "/admin/settings/manage/categories?section=edit&org="+ organisation_id +"&id=" + category_id + "&mode=ajax");

        modal_container.dialog({
            title: "Edit Category",
            modal: true,
            draggable: false,
            resizable: false,
            width: 700,
            minHeight: 550,
            maxHeight: 700,
            buttons: {
                Cancel : {
                    click: function() {
                        modal_container.dialog("destroy");
                        modal_container.remove();
                    },
                    class: 'btn',
                    text: 'Cancel'
                },
                Save : {
                    click: function() {
                        var url = modal_container.find("form").attr("action");
                        jQuery.ajax({
                            url: url,
                            type: "POST",
                            async: false,
                            data: modal_container.find("form").serialize(),
                            success: function(data) {
                                var jsonData = JSON.parse(data);

                                if (jsonData.status == "success") {

                                    var order = jsonData.updates.category_order;
                                    var category_parent = jsonData.updates.category_parent;

                                    var list_item = jQuery("#category_"+category_id);

                                    jQuery("#category_title_"+jsonData.updates.category_id).html(jsonData.updates.category_name);
                                    jQuery("#description_"+jsonData.updates.category_id).html(jsonData.updates.category_description);

                                    jQuery("#category_"+category_id).remove();

                                    if (jQuery("#children_" + category_parent + " #category_list_" + category_parent).children().length != order) {
                                        jQuery("#children_" + category_parent + " #category_list_" + category_parent + " li").eq(order).before(list_item)
                                    } else {
                                        jQuery("#children_" + category_parent + " #category_list_" + category_parent).append(list_item);
                                    }	
                                    modal_container.dialog("destroy");
                                    modal_container.remove();
                                } else if (jsonData.status == "error") {
                                    jQuery(".ui-dialog .display-error").html("<p class=\"check-err\">"+ jsonData.msg +"</p>");
                                    if (!jQuery(".ui-dialog .display-error").is(":visible")) {
                                        jQuery(".ui-dialog .display-error").show();
                                    }
                                }
                            }
                        });
                    },
                    class: 'pull-right btn btn-primary',
                    text: 'Save'
                }
            },
            close: function(event, ui){
                modal_container.dialog("destroy");
                modal_container.remove();
            }
        });
        return false;
    });

    jQuery(".category-add-control").live("click", function(){
        var parent_id = jQuery(this).attr("data-id");
        var modal_container = jQuery(document.createElement("div"));
        var organisation_id = jQuery('#organisation_id').val();
        
        var url = SITE_URL + "/admin/settings/manage/categories?section=add&org="+ organisation_id +"&mode=ajax&parent_id="+parent_id;
        modal_container.load(url);

        modal_container.dialog({
            title: "Add New Category",
            modal: true,
            draggable: false,
            resizable: false,
            width: 700,
            minHeight: 550,
            maxHeight: 700,
            buttons: {
                Cancel : function() {
                    modal_container.dialog("destroy");
                    modal_container.remove();
                },
                Add : function() {
                    var url = modal_container.find("form").attr("action");
                    jQuery.ajax({
                        url: url,
                        type: "POST",
                        async: false,
                        data: modal_container.find("form").serialize(),
                        success: function(data) {

                            var jsonData = JSON.parse(data);
                            if (jsonData.status == "success") {
                                var order = jsonData.updates.category_order;
                                var category_parent = jsonData.updates.category_parent;
                                var list_item = jQuery(document.createElement("li"));
                                list_item.addClass("category-container")
                                         .attr("id", "category_"+jsonData.updates.category_id)
                                         .attr("data-id", jsonData.updates.category_id)
                                         .attr("data-code", jsonData.updates.category_code)
                                         .attr("data-name", jsonData.updates.category_name)
                                         .attr("data-desc", jsonData.updates.category_description)
                                         .append(jQuery(document.createElement("div")).attr("id", "category_title_"+jsonData.updates.category_id).attr("data-title", jsonData.updates.category_name).attr("data-id", jsonData.updates.category_id).addClass("category-title").html(jsonData.updates.category_name))
                                         .append(jQuery(document.createElement("div")).addClass("category-controls"))
                                         .append(jQuery(document.createElement("div")).attr("id", "description_"+jsonData.updates.category_id).addClass("category-description").addClass("content-small").html(jsonData.updates.category_description))
                                         .append(
                                            jQuery(document.createElement("div")).attr("id", "children_"+jsonData.updates.category_id).addClass("category-children").append(
                                                jQuery(document.createElement("ul")).attr("id", "category_list_"+jsonData.updates.category_id).addClass("category-list")
                                            )
                                         );
                                list_item.children(".category-controls").append(jQuery(document.createElement("i")).addClass("category-edit-control").attr("data-id", jsonData.updates.category_id))
                                         .append(jQuery(document.createElement("i")).addClass("category-add-control").attr("data-id", jsonData.updates.category_id))
                                         .append(jQuery(document.createElement("i")).addClass("category-delete-control").attr("data-id", jsonData.updates.category_id));

                                if (jQuery("#children_" + parent_id + " #category_list_" + category_parent).children().length != order) {
                                    jQuery("#children_" + parent_id + " #category_list_" + category_parent + " li").eq(order).before(list_item)
                                } else {
                                    jQuery("#children_" + parent_id + " #category_list_" + category_parent).append(list_item);
                                }
                                modal_container.dialog("destroy");
                                modal_container.remove();
                            } else if (jsonData.status == "error") {
                                jQuery(".ui-dialog .display-error").html("<p class=\"check-err\">"+ jsonData.msg +"</p>");
                                if (!jQuery(".ui-dialog .display-error").is(":visible")) {
                                    jQuery(".ui-dialog .display-error").show();
                                }
                            }
                        }
                    });
                }
            },
            close: function(event, ui){
                modal_container.dialog("destroy");
                modal_container.remove();
            }
        });
        return false;
    });

    jQuery(".category-delete-control").live("click", function(){
        var category_id = jQuery(this).attr("data-id");
        var modal_container = jQuery(document.createElement("div"));
        var organisation_id = jQuery('#organisation_id').val();
        var url = SITE_URL + "/admin/settings/manage/categories?section=delete&org="+ organisation_id +"&mode=ajax&category_id="+category_id;
        modal_container.load(url);

        modal_container.dialog({
            title: "Delete Category",
            modal: true,
            draggable: false,
            resizable: false,
            width: 700,
            minHeight: 550,
            maxHeight: 700,
            buttons: {
                Cancel : function() {
                    modal_container.dialog("destroy");
                    modal_container.remove();
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
                                jQuery("#category_"+category_id).remove();
                                modal_container.dialog( "close" );
                                modal_container.remove();
                            } else {
                                if (jQuery(".ui-dialog .display-error .check-err").length <= 0) {
                                    jQuery(".ui-dialog .display-error").append("<p class=\"check-err\"><strong>Please note:</strong> The checkbox below must be checked off to delete this category and its children.</p>");
                                    if (!jQuery(".ui-dialog .display-error").is(":visible")) {
                                        jQuery(".ui-dialog .display-error").show();
                                    }
                                }
                            }
                        }
                    });
                }
            },
            close: function(event, ui){
                modal_container.dialog("destroy");
                modal_container.remove();
            }
        });
        return false;
    });
});

function buildDOM(children,id){
    var container,title,title_text,controls,check,d_control,e_control,a_control,description,child_container;
    jQuery('#children_'+id).hide();
    if(children.error !== undefined){
        if(!EDITABLE){
            jQuery('#check_category_'+id).trigger('click');
            jQuery('#check_category_'+id).trigger('change');
        }
        return;
    }
    for(i = 0;i<children.length;i++){
        //Javascript to create DOM elements from JSON response
        container = jQuery(document.createElement('li'))
                    .attr('class','category-container draggable')
                    .attr('data-id',children[i].category_id)
                    .attr('data-code',children[i].category_code)
                    .attr('data-name',children[i].category_name)
                    .attr('data-description',children[i].category_description)
                    .attr('id','category_'+children[i].category_id);
        if(children[i].category_code){
            title_text = children[i].category_code+': '+children[i].category_name
        }else{
            title_text = children[i].category_name;
        }
        title = 	jQuery(document.createElement('div'))
                    .attr('class','category-title')
                    .attr('id','category_title_'+children[i].category_id)
                    .attr('data-id',children[i].category_id)
                    .attr('data-title',title_text)
                    .html(title_text);

        controls = 	jQuery(document.createElement('div'))
                    .attr('class','category-controls');

        if(EDITABLE == true){
            e_control = jQuery(document.createElement('i'))
                        .attr('class','category-edit-control')
                        .attr('data-id',children[i].category_id);
            a_control = jQuery(document.createElement('i'))
                        .attr('class','category-add-control')
                        .attr('data-id',children[i].category_id);
            d_control = jQuery(document.createElement('i'))
                        .attr('class','category-delete-control')
                        .attr('data-id',children[i].category_id);
        } else {
            check = 	jQuery(document.createElement('input'))
                        .attr('type','checkbox')
                        .attr('class','checked-category')
                        .attr('id','check_category_'+children[i].category_id)
                        .val(children[i].category_id);
        }
        description = 	jQuery(document.createElement('div'))
                        .attr('class','category-description content-small')
                        .attr('id','description_'+children[i].category_id)
                        .html(children[i].category_description);
        child_container = 	jQuery(document.createElement('div'))
                            .attr('class','category-children')
                            .attr('id','children_'+children[i].category_id);
        child_list = 	jQuery(document.createElement('ul'))
                            .attr('class','category-list')
                            .attr('id','category_list_'+children[i].category_id)
                            .attr('data-id',children[i].category_id);
        jQuery(child_container).append(child_list);
        if(EDITABLE == true){
        jQuery(controls).append(e_control)
                        .append(a_control)
                        .append(d_control);
        }
        jQuery(container).append(title)
                            .append(controls)
                            .append(description)
                            .append(child_container);
        jQuery('#category_list_'+id).append(container);
    }

    jQuery('#children_'+id).slideDown();
}

function selectCategory(element, parent_id, category_id, organisation_id) {
    jQuery.ajax({
        url: SITE_URL + "/api/categories-list.api.php",
        type : 'get',
        data : {'pid': parent_id, 'id': category_id, 'organisation_id': organisation_id}
    }).done(function(result) {
        jQuery(element).html(result)
    });
    return;
}

function selectOrder(element, category_id, parent_id, organisation_id) {
    jQuery.ajax({
        url: SITE_URL + "/api/categories-list.api.php",
        type: 'get',
        data : {'type': 'order', 'id': category_id, 'pid': parent_id, 'organisation_id': organisation_id}
    }).done(function(result) {
        jQuery(element).html(result)
    });
    return;
}


