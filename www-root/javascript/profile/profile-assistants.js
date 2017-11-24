function addAssistant() {
    if ((jQuery('#assistant_id').length > 0) && (jQuery('#assistant_id').val() > 0) && (jQuery('#assistant_name').val() != "")) {
        jQuery('#assistant_add_form').submit();
    } else {
        alert('You can only add people as assistants to your profile if they already exist in the system.\n\nIf you are typing in their name properly (Lastname, Firstname) and their name does not show up in the list then chances are that they do not exist in our system.\n\nPlease Note: When you see the correct name pop-up in the list as you type, make sure you select the name with your mouse, do not press the Enter button.');

        return false;
    }
}

function confirmRemoval() {
    ask_user = confirm("Press OK to confirm that you would like to remove the ability for the selected individuals to access your permission levels, otherwise press Cancel.");

    if (ask_user == true) {
        jQuery('#assistant_remove_form').submit();
    } else {
        return false;
    }
}

jQuery(document).ready(function ($) {

    function copyItem(type, name) {

        var type_head = '#' + type;
        var type_ref  = type_head + '_ref';
        var type_name = type_head + '_name';

        if ($(type_name).length) {
            $(type_name).val(name);
        }
        if (($(type_name).length) && ($(type_ref).length)) {
            $(type_ref).val(name);
        }

        return true;
    }

    function selectItem(id, type) {

        var type_head = '#' + type;
        var type_id   = type_head + '_id';

        if ((id != null) && ($(type_id).length)) {
            $(type_id).val(id);
        }
    }

    function deleteItem(type) {
        var type_head = '#' + type;
        var type_ref  = type_head + '_ref';
        var type_id   = type_head + '_id';

        if ($(type_ref).length) {
            $(type_ref).val('');
        }
        if ($(type_id).length) {
            $(type_id).val('');
        }
    }

    createAuto("assistant_name", "assistant");

    function createAuto (element_id, type) {
        var auto_item = $("#"+element_id).autocomplete({
            source: "/api/personnel.api.php?out=json",
            minLength: 2,
            appendTo: $("#autocomplete-list-container"),
            open: function () {
                $("#"+element_id).removeClass("searching");
                $("#"+element_id).addClass("search");
            },
            close: function(e) {
                $("#"+element_id).removeClass("searching");
                $("#"+element_id).addClass("search");
            },

            select: function(e, ui) {
                selectItem(ui.item.proxy_id, type);
                copyItem(type, ui.item.fullname);
                e.preventDefault();
            },
            // when searching, remove any previously selected item
            search: function () {
                $("#"+element_id).removeClass("search");
                $("#"+element_id).addClass("searching");
                deleteItem(type);
            }
        }).data("autocomplete");

        auto_item._renderItem = function(ul, item) {

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
                var user_li                = $(document.createElement("li")).data("item.autocomplete", item);
                $(user_li).html(item.response);
                return(user_li.appendTo(ul));
            }
        };
    }
});