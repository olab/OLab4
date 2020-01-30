var ENTRADA_URL;

jQuery(document).ready(function(){

    var disclaimers = [];
    var accept_btn = "Accept";
    var decline_btn = "Decline";
    var trigger_type = null;
    var trigger_value = null;
    getDisclaimers();
/*
* Get the Disclaimers available for the currently active user from the User Disclaimers API
* */
    function getDisclaimers() {
        disclaimers = [];
        if (typeof COMMUNITY_ID  !== "undefined") {
            trigger_type = "community";
            trigger_value = COMMUNITY_ID;
        } else if (typeof COURSE_ID  !== "undefined") {
            trigger_type = "course";
            trigger_value = COURSE_ID;
        } else if (typeof EVENT_ID  !== "undefined") {
            trigger_type = "event";
            trigger_value = EVENT_ID;
        }
        jQuery.getJSON(ENTRADA_URL + "/api/disclaimers.api.php", {
            method: "get-disclaimers",
            trigger_type: (trigger_type != null ? trigger_type : null),
            trigger_value: (trigger_value != null ? trigger_value : null)
        }, function(json) {
            if (json) {
                if(json.status == "success") {
                    accept_btn = json.accept_btn;
                    decline_btn = json.decline_btn;
                    if (json.data && json.data.length>0) {
                        jQuery.each(json.data, function(index, disclaimer) {
                            disclaimers.push(disclaimer);
                        });

                        loadDisclaimers();
                    }
                }
            }
        })
    }
/*
* Create a modal for each User Disclaimer loaded from the API
* */
    function loadDisclaimers() {
        if (disclaimers.length > 0) {

            counter = disclaimers.length+1;

            jQuery(".disclaimers-modal").remove();
            jQuery.each(disclaimers, function(index, disclaimer) {
                jQuery("body").append('' +
                    '<div id="disclaimers-modal_' + disclaimer.disclaimer_id + '" class="modal hide fade disclaimers-modal responsive-modal modal-lg" data-id="' + disclaimer.disclaimer_id + '">' +
                    '   <div class="modal-header">' +
                    '       <h3 id="disclaimer_title_' + disclaimer.disclaimer_id + '">' + disclaimer.disclaimer_title + '' +
                    '           <button class="btn btn-mini pull-right print-btn" title="Print this page"><i class="fa fa-print"></i></button>' +
                    '       </h3> ' +
                    '   </div>' +
                    '   <div class="modal-body" id="disclaimer_text_' + disclaimer.disclaimer_id + '">' +
                    '       <div class="modal-msg"></div>' +
                    '       ' + disclaimer.disclaimer_text +
                    '   </div>' +
                    '   <div class="modal-footer">' +
                    '       <div class="pull-left space-above">Disclaimer ' + (counter - 1) + ' of ' + disclaimers.length + '</div>' +
                    '       <button class="btn btn-danger disclaimer_action_decline" data-method="decline-disclaimer">' + decline_btn + '</button>' +
                    '       <button class="btn btn-success disclaimer_action_approve" data-method="approve-disclaimer">' + accept_btn + '</button>' +
                    '   </div>' +
                    '</div>');
                counter -= 1;
            });

            jQuery(".disclaimers-modal:last-child").modal({show: "true", backdrop: "static"});
            /*
            * Send the user approval / decline to the User Disclaimers API
            * */

            jQuery(".disclaimers-modal").on("click", ".disclaimer_action_approve, .disclaimer_action_decline", function() {
                disclaimer_id = jQuery(this).closest(".disclaimers-modal").data("id");
                method = jQuery(this).data("method");
                jQuery.post(ENTRADA_RELATIVE + "/api/disclaimers.api.php", {
                    method: method,
                    disclaimer_id: disclaimer_id
                }, function(data) {
                    if (data) {
                        json = jQuery.parseJSON(data);
                        if (json.status == "success") {
                            if (method == "decline-disclaimer" && json.data.upon_decline == "log_out") {
                                window.location = ENTRADA_RELATIVE + "/?action=logout";
                            } else if (method == "decline-disclaimer" && json.data.upon_decline == "deny_access") {
                                window.location = ENTRADA_RELATIVE + "/";
                            } else {
                                jQuery("#disclaimers-modal_" + disclaimer_id).modal("hide");
                            }
                        } else {
                            jQuery("#disclaimers-modal_" + disclaimer_id + " .modal-msg").html('<div class="alert alert-error">' + json.data + '</div>');
                        }
                    }

                });
            });
            /*
            * Create a new window with the User Disclaimer content and print it
            * */
            jQuery(".disclaimers-modal").on("click", ".print-btn", function() {
                disclaimer_id = jQuery(this).closest(".disclaimers-modal").data("id");
                var print_window = window.open('', 'PRINT', 'height=400,width=600, left=100, top=100');
                var window_html = '  <html>' +
                                    '   <head>' +
                                    '       <title>' + jQuery("#disclaimer_title_" + disclaimer_id).html()  + '</title>' +
                                    '   </head>' +
                                    '<body>' +
                                    '   <h1>' + jQuery("#disclaimer_title_" + disclaimer_id).html()  + '</h1>' +
                                    '' + jQuery("#disclaimer_text_" + disclaimer_id).html() + '' +
                                    '</body>' +
                                    '</html>';
                print_window.document.write(window_html);

                print_window.document.close(); // necessary for IE >= 10
                print_window.focus(); // necessary for IE >= 10*/

                print_window.print();
                print_window.close();

                return true;
            });
            /*
             * Show the next User Disclaimer after one is hidden
             * */
            jQuery(".disclaimers-modal").on("hidden", function() {
               if (jQuery(this).prev().hasClass("disclaimers-modal")) {
                   jQuery(this).prev().modal({show: "true", backdrop: "static"});
               }
            });
        }
    }
});

/*
* Build Audience items list for the Advanced Search plugin
* */

function buildAdvancedSearchList(search_btn) {
    jQuery("input[name=\"" + search_btn.data("settings").target_name + "[]\"]").each(function() {
        var input = jQuery(this);
        var filter = input.attr("id").split("_")[0];
        var list_item = "<li class=\"" + filter + "_target_item " + filter + "_" + input.data("id") + "\" data-id=\"" + input.data("id") + '"><span class="selected-list-container"><span class="selected-list-item">' + filter.replace(/\b\w/g, function(l){ return l.toUpperCase() }) + "</span><span class=\"remove-selected-list-item remove-target-toggle\" data-id=\"" + input.data("id") + "\" data-filter=\"" + filter + "\">×</span></span>" + input.data("label") + "</li>";
        if (jQuery("#" + filter + "_list_container").length > 0) {
            jQuery("#" + filter + "_list_container").append(list_item);
        } else {
            search_btn.after("<ul id=\"" + filter + "_list_container\" class=\"selected-items-list\">" + list_item + "</ul>")
        }
    });
}

function OnChangeUponDecline() {
}

function OnChangeTriggerType() {
    var trigger_type = jQuery("input[name*='trigger_type']:checked").val();

    switch (trigger_type) {
        case "course":
            jQuery("#choose-course-btn").removeClass("hide");
            jQuery("#choose-community-btn").addClass("hide");
            jQuery(".community_search_target_control").remove();
            jQuery("#community_list_container").remove();
            jQuery("label[for=upon_decline3]").removeClass("hide");
            break;
        case "community":
            jQuery("#choose-community-btn").removeClass("hide");
            jQuery("#choose-course-btn").addClass("hide");
            jQuery(".course_search_target_control").remove();
            jQuery("#course_list_container").remove();
            jQuery("label[for=upon_decline3]").removeClass("hide");
            break;
        default:
            jQuery("#choose-course-btn").addClass("hide");
            jQuery(".course_search_target_control").remove();
            jQuery("#course_list_container").remove();
            jQuery("#choose-community-btn").addClass("hide");
            jQuery(".community_search_target_control").remove();
            jQuery("#community_list_container").remove();
            jQuery("label[for=upon_decline3]").addClass("hide");
            jQuery("input[name*='upon_decline'][value='continue']").attr("checked", "checked");
            break;
    }
}
