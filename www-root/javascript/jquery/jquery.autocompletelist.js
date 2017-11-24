// jQuery function to replace Prototype based AutoCompleteList.js
(function( $ ) {

    $.fn.autocompletelist = function( options ) {

        var self = this;
        var settings = $.extend({
            type: "",
            url: "",
            remove_image: "",
            min_search_length: 2,
        }, options );

        // construct some other jQuery selectors based on the type
        settings.type_head = '#' + settings.type;
        settings.type_id   = settings.type_head + '_id';
        settings.type_ref  = settings.type_head + '_ref';
        settings.type_name = settings.type_head + '_name';
        settings.type_list = settings.type_head + '_list';

        $(this).data("settings", settings);

        // id='associated_<type>' holds the sorted list. Update if the list changes
        function updateOrder() {
            var associated = '#associated_' + settings.type;
            var proxy_list = $(settings.type_list).sortable("toArray", {attribute: "data-proxy-id"});
            $(associated).val(proxy_list);
        }

        // adds a selected name to the element with id='<type>_list'
        function addItem () {
            if (($(settings.type_id).length) && ($(settings.type_id).val() != '') && ($(settings.type_head+'_'+$(settings.type_id).val()).length == 0)) {
                var id = $(settings.type_id).val();
                var li = $(document.createElement("li"))
                    .addClass('user')
                    .attr({'id': settings.type+'_'+id, 'data-proxy-id': id})
                    .css({'cursor': 'move'})
                    .html($(settings.type_name).val());
                if(settings.type=='faculty'){
                    li.css({'margin-bottom':'10px', 'width': '350px'});
                    var select = $(document.createElement("select"))
                        .addClass('input-medium')
                        .attr({'name': 'faculty_role[]'})
                        .css({'float': 'right', 'margin-right': '30px', 'margin-top': '-5px'})
                        .append($(document.createElement("option")).val('teacher').html('Teacher'))
                        .append($(document.createElement("option")).val('tutor').html('Tutor'))
                        .append($(document.createElement("option")).val('ta').html('Teacher\'s Assistant'))
                        .append($(document.createElement("option")).val('auditor').html('Auditor'));
                    li.append(select);
                }
                var img = $(document.createElement("img"))
                    .addClass('list-cancel-image')
                    .attr({'src': settings.remove_image});
                li.append(img);
                $(settings.type_name).val('');
                $(settings.type_id).val('');
                $(settings.type_list).append(li);
                if ($(settings.type_list).hasClass('ui-sortable')) {
                    $(settings.type_list).sortable("refresh");
                } else {
                    $(settings.type_list).sortable({ update: function () { updateOrder() }});
                }
                updateOrder();

                

            } else if ($(settings.type_head+'_'+$(settings.type_id).val()).length) {
                alert('Important: Each user may only be added once.');
                $(settings.type_id).val('');
                $(settings.type_name).val('');
            } else if ($(settings.type_name).val() != '' && $(settings.type_name).length) {
                alert('Important: When you see the correct name pop-up in the list as you type, make sure you select the name with your mouse, do not press the Enter button.');
            }
            
            $(settings.type_list).trigger("change");
        }

        function removeItem (id) {
            if ($(settings.type_head+'_'+id).length) {
                $(settings.type_head+'_'+id).remove();
                $(settings.type_list).sortable("refresh");
                updateOrder();
            }
        }

        // when an item is selected, put the proxy_id into element with id='<type>_id'
        function selectInput(id) {
            if ((id != null) && (jQuery(settings.type_id).length)) {
                jQuery(settings.type_id).val(id);
            }
        }

        // copies the selection's name to two elements
        // id = '<type>_name'
        // id = '<type>_ref'
        function copyInput(fullname) {
            if ($(settings.type_name).length) {
                $(settings.type_name).val(fullname);
            }
            if (($(settings.type_name).length) && ($(settings.type_ref).length)) {
                $(settings.type_ref).val(fullname);
            }
            return true;
        }

        // set up the jQuery autocomplete functionality on the input selector, based on options provided
        var autocompleteObject = $(this).autocomplete({
            source: settings.url + '&out=json',
            minLength: settings.min_search_length,
            appendTo: $(settings.type_head+'_name_auto_complete'),
            open: function () {
                $(settings.type_name).removeClass("search");
                $(settings.type_name).addClass("searching");
            },
            close: function () {
                $(settings.type_name).removeClass("searching");
                $(settings.type_name).addClass("search");
            },
            select: function (e, ui) {
                selectInput(ui.item.proxy_id);
                copyInput(ui.item.fullname);
                addItem();
                e.preventDefault();
            },
            search: function () {
                $(settings.type_name).removeClass("search");
                $(settings.type_name).addClass("searching");
            }
        }).data("autocomplete");

        // this is a custom render function for the autocomplete
        autocompleteObject._renderItem = function(ul, item) {

            if (typeof(item.response) == "undefined" || item.response.length == 0) {

                var user_li                = $(document.createElement("li")).data("item.autocomplete", item);
                var template_a             = $(document.createElement("a"));
                var details_div            = $(document.createElement("div")).addClass("course-auto-details");
                var secondary_details_span = $(document.createElement("span")).addClass("course-auto-secondary-details");
                var name_span              = $(document.createElement("span")).addClass("course-auto-name");
                var email_span             = $(document.createElement("span")).addClass("course-auto-email");
                var group_role_span        = $(document.createElement("span"));

                name_span.html("<strong>"+item.fullname+"</strong><br/>");
                email_span.html(item.email + " <br/>");
                group_role_span.html(item.organisation_title);

                $(secondary_details_span).append(group_role_span);
                $(details_div).append(name_span).append(email_span).append(secondary_details_span);
                $(template_a).append(details_div);
                $(user_li).append(template_a);

                return(user_li.appendTo(ul));
            } else {
                var user_li = $(document.createElement("li")).data("item.autocomplete", item);
                $(user_li).html(item.response);
                return(user_li.appendTo(ul));
            }
        };

        // If there is an existing results list, make sure it has the data-proxy-id attribute (required for sorting)
        // Then make the results list sortable
        if ($(settings.type_list).children().length > 0) {
            $(settings.type_list).children().each(function() {
               if (!($(this).attr('data-proxy-id'))) {
                   var temp = $(this).attr("id").split("_");
                   var id = temp[2];
                   $(this).attr('data-proxy-id', id);
               }
            });
            $(settings.type_list).sortable({ update: function () { updateOrder() }});
            updateOrder();
        };

        // create a handler for the delete buttons
        $(settings.type_list).on("click", 'img.list-cancel-image', function(e) {
            id = $(this).parent().attr("data-proxy-id");
            removeItem(id);
            e.preventDefault();
        });

        // if there is an associated 'add_associated_<type>' button, remove it. This method adds directly on selection
        if ($('input#add_associated_'+settings.type).length) {
            $('input#add_associated_'+settings.type).remove();
        }
        return this;
    };
}( jQuery ));
