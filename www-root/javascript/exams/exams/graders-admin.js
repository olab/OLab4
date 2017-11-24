function selectItem(id, fullname, type) {
    if ((id != null) && (fullname != null) && (jQuery('#'+type+'_id').length) && (jQuery('#'+type+'_name').length)) {
        jQuery('#'+type+'_id').val(id);
        jQuery('#'+type+'_name').val(fullname);
    }

    return true;
}

function copyItem(type) {
    if ((jQuery('#'+type+'_name').length) && (jQuery('#'+type+'_ref').length)) {
        jQuery('#'+type+'_ref').val(jQuery('#'+type+'_name').val());
    }

    return true;
}

function checkItem(type) {
    if ((jQuery('#'+type+'_name').length) && (jQuery('#'+type+'_ref').length) && (jQuery('#'+type+'_id'.length))) {
        if (jQuery('#'+type+'_name').val() != jQuery('#'+type+'_ref').val()) {
            jQuery('#'+type+'_id').val('');
        }
    }

    return true;
}

function addGrader(type) {
    if ((jQuery('#'+type+'_id').length) && (jQuery('#'+type+'_id').val() != '') && (jQuery('#'+type+'_'+jQuery('#'+type+'_id').val()).length == 0)) {
        var tr = jQuery(document.createElement("tr"));
        var td_grader = jQuery(document.createElement("td"));
        var td_empty = jQuery(document.createElement("td")).attr({
            id: "td-graders-to-group-"+jQuery('#'+type+'_id').val()
        });

        td_empty.append('<i>No groups assigned</i>');

        var ckbox = jQuery(document.createElement('input')).attr({
            type: "checkbox",
            id: 'grader_'+jQuery('#'+type+'_id').val(),
            name: 'chk_graders[]',
            value: jQuery('#'+type+'_id').val(),
            'data-name': jQuery('#'+type+'_name').val()
        });

        var hiddn = jQuery(document.createElement("input")).attr({
            type: "hidden",
            name: "graders[]",
            value: jQuery('#'+type+'_id').val()
        });

        var lbl_grader = jQuery(document.createElement("label")).attr({
            for: "grader_"+jQuery('#'+type+'_id').val()
        });

        lbl_grader.append(ckbox).append(' ' + jQuery('#'+type+'_name').val());
        td_grader.append(lbl_grader).append(hiddn);

        tr.append(td_grader).append(td_empty);

        jQuery("#table-graders-to-groups").append(tr);

        jQuery('#'+type+'_id').val('');
        jQuery('#'+type+'_name').val('');
    } else if (jQuery('#'+type+'_'+jQuery('#'+type+'_id').val()) != null) {
        alert('Important: Each user may only be added once.');
        jQuery('#'+type+'_id').val('');
        jQuery('#'+type+'_name').val('');
        return false;
    } else if (jQuery('#'+type+'_name').val() != '' && jQuery('#'+type+'_name').val() != null) {
        alert('Important: When you see the correct name pop-up in the list as you type, make sure you select the name with your mouse, do not press the Enter button.');
        return false;
    } else {
        return false;
    }
}

jQuery(document).on("click", ".a-assign-single-grader", function(e) {
    e.preventDefault();

    var group_id = jQuery(this).data("id");

    // set checkbox to check for this student
    jQuery("#group_" + group_id).prop("checked", true);

    if (populateAssignToGraderModal()) {
        jQuery(jQuery(this).attr("href")).modal('show');
    }
});

jQuery(document).on("click", "#btn-assign-group", function(e) {

    e.preventDefault();

    if (populateAssignToGraderModal()) {
        jQuery(jQuery(this).attr("href")).modal('show');
    }
});

function populateAssignToGraderModal() {
    var error = false;

    if (jQuery('input[name^="group"]:checked').length < 1) {
        alert('Please select at least one group to assign.');
        error = true;
        return false;
    }

    if (jQuery('input[name^="chk_graders"]').length < 1) {
        alert("You must add at least one grader before assigning a group.")
        error = true;
        return false;
    }

    if (!error) {
        // Build the grader list
        jQuery("#table-assign-grader-modal tbody").html("");

        jQuery('input[name^="chk_graders"]').each(function() {
            var tr = jQuery(document.createElement("tr"));
            var td = jQuery(document.createElement("td"));
            var input = jQuery(document.createElement("input")).attr({
                type: "radio",
                name: "assign-grader",
                id: "assign-grader-"+jQuery(this).val(),
                value: jQuery(this).val()
            });

            var lbl = jQuery(document.createElement("label")).attr({
                for: "assign-grader-"+jQuery(this).val(),
                style: "width: 100%"
            });

            tr.append(td.append(lbl.append(input).append(' '+jQuery(this).attr("data-name"))));
            jQuery("#table-assign-grader-modal tbody").append(tr);
        });

        return true;
    }
}

jQuery(document).on("click", "#assign-grader-modal .btn-modal-assign-groups", function(e) {
    e.preventDefault();

    // In case somehow this can happen
    if (jQuery('input[name^="group"]:checked').length < 1) {
        jQuery("#assign-grader-modal").modal("hide");
        alert('Please select at least one group to assign.');
    }

    var grader = jQuery("input[name='assign-grader']:checked").val();
    if (! grader) {
        jQuery("#assign-grader-modal .alert").html(jQuery(document.createElement("ul")).append(jQuery(document.createElement("li"))).append("Please select a grader from the list."));
        jQuery("#assign-grader-modal .alert").show();
        setTimeout(function () {
            jQuery("#assign-grader-modal .alert").fadeOut("slow");
        }, 3000);

        return false;
    }

    jQuery("input[name^='group']:checked").each(function() {
        var div = jQuery(document.createElement("div")).attr({
            style: "margin-bottom: 10px;",
            "data-id": jQuery(this).val(),
            "data-name": jQuery(this).data("name")
        });
        var img = jQuery(document.createElement("img")).attr({
            id: "remove-group-" + jQuery(this).val(),
            src: "/images/action-delete.gif",
            class: "remove-group pull-right " + (jQuery(this).hasClass("in-group") ? "in-group in-group-" + jQuery(this).attr("data-group-id") : "" ),
            style: "cursor: pointer;",
            "data-id": jQuery(this).val(),
            "data-grader": grader
        });

        div.append(jQuery(this).attr('data-name')).append(img);

        if (jQuery("#td-graders-to-group-" + grader).html() == "<i>No groups assigned</i>") {
            jQuery("#td-graders-to-group-" + grader).html(div);
        } else {
            jQuery("#td-graders-to-group-" + grader).append(div);
        }

        jQuery("#graders-assignments-container").append(
            jQuery(document.createElement("input")).attr({
                type: "hidden",
                name: "g_assignment_"+grader+"[]",
                value: jQuery(this).val()
            })
        );

        jQuery(this).closest("tr").remove();
    })

    // refreshGroupList();
    jQuery("#assign-grader-modal").modal("hide");
});

function buildUnassignedAudience(group, target) {

    if (jQuery("#remove-group-"+group.cgroup_id).length > 0) {
        return true;
    }
    
    var tr = jQuery(document.createElement("tr"));
    var td_lft = jQuery(document.createElement("td"));
    var td_rgt = jQuery(document.createElement("td"));
    var lbl = jQuery(document.createElement("label")).attr({
        for: 'group_' + group.cgroup_id
    });
    var ckbox = jQuery(document.createElement("input")).attr({
        type: 'checkbox',
        id: 'group_' + group.cgroup_id,
        name: 'group[]',
        "data-name": group.group_name,
        value: group.cgroup_id
    });

    var lnk = jQuery(document.createElement("a")).attr({
        class: 'a-assign-single-grader',
        href: '#assign-grader-modal',
        'data-id': group.cgroup_id
    }).append(ASSIGN_TO_GRADER_TEXT);

    td_rgt.append(lnk);
    td_lft.append(lbl.append(ckbox).append(' ' + group.group_name));
    tr.append(td_lft).append(td_rgt);

    jQuery(target).append(tr);
}

function refreshAudienceList() {

    jQuery.ajax ({
        url : ENTRADA_URL + "/admin/exams/exams?section=api-exams",
        type : "GET",
        data : "method=get-course-groups&course_id=" + COURSE_ID,
        success: function(data) {
            var jsonResponse = JSON.parse(data);
            if (jsonResponse.status == "success") {

                var target = "#table-groups-no-grader tbody";
                jQuery(target).empty();

                jQuery.each(jsonResponse.data, function (i, group) {
                    buildUnassignedAudience(group, target);
                });
            }
        }
    });
}

jQuery(document).on("change", "#all-groups", function() {

    jQuery('input[name^="group"]').each(function() {
        jQuery(this).prop("checked", jQuery("#all-groups").is(":checked")).change();
    });
});

jQuery(document).on("click", ".remove-group", function() {
    var id = jQuery(this).data("id");
    var grader = jQuery(this).data("grader");

    jQuery('input[name^="g_assignment_'+grader+'"]').each(function() {
        if (jQuery(this).val() == id) {
            jQuery(this).remove();
        }
    });

    jQuery(this).closest("div").remove();
    if (jQuery("#td-graders-to-group-"+grader).children().length == 0) {
        jQuery("#td-graders-to-group-"+grader).html('<i>No groups assigned</i>');
    }

    refreshAudienceList();
});

jQuery(document).on("click", "#btn-remove-graders", function(e) {
    e.preventDefault();

    jQuery("#table-modal-remove-grader tbody").html('');

    if (jQuery('input[name^="chk_graders"]:checked').length < 1) {
        alert('Please select one or more grader(s) to remove');
        return false;
    }

    jQuery('input[name^="chk_graders"]:checked').each(function() {
        var tr = jQuery(document.createElement("tr"));
        var td_grad = jQuery(document.createElement("td")).append(jQuery(this).data("name"));
        var td_learn = jQuery(document.createElement("td"));

        jQuery("#td-graders-to-group-"+jQuery(this).val()+" div").each(function() {
            td_learn.append(jQuery(this).data("name") + "<br />");
        });

        if (td_learn.children().length == 0) {
            td_learn.html('<i>No groups assigned</i>');
        }

        tr.append(td_grad).append(td_learn);

        jQuery("#table-modal-remove-grader tbody").append(tr);
    });
    jQuery(jQuery(this).attr("href")).modal('show');
});

jQuery(document).on("click", ".btn-modal-remove-grader", function(e) {

    e.preventDefault();

    jQuery('input[name^="chk_graders"]:checked').each(function() {
        jQuery('input[name^="g_assignment_'+jQuery(this).val()+'"]').each(function() {
            jQuery(this).remove();
        });

        jQuery(this).closest("tr").remove();
    });

    refreshAudienceList();

    jQuery("#modal-remove-grader").modal("hide");
});

jQuery(document).on("click", "#btn-save-grader-settings", function(e) { 

    var graders = [];
    var found_group = false;

    data = {"method" : "save-grader-settings", "post_id" : POST_ID}; 

    jQuery("#table-graders-to-groups tbody tr").each(function () {
        var grader_id = jQuery(this).find("td:first input").val();
        var groups = [];
        graders.push(grader_id);

        jQuery(this).find("td:nth-of-type(2) div").each(function () {
            groups.push(jQuery(this).data("id"));
            found_group = true;
        });           
        data["grader_"+grader_id] = groups;
    });
    data["graders"] = graders;

    if (!found_group) {
        var select_data = {"method" : "get-assigned-groups", "post_id" : POST_ID}; 
        jQuery.ajax({
            url : ENTRADA_URL + "/admin/exams/exams?section=api-exams",
            type : "GET",
            data : select_data,
            success : function(data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status == "success") {
                    jQuery("#modal_remove_all_graders_from_post").find(".modal-body").html("<p>Confirm removing all assigned graders from this post.</p>");
                    jQuery("#modal_remove_all_graders_from_post").find(".btn-modal-remove-all-graders-from-post").show();
                } else {
                    jQuery("#modal_remove_all_graders_from_post").find(".modal-body").html("<p>No grader has been assigned to any course group.</p>");
                    jQuery("#modal_remove_all_graders_from_post").find(".btn-modal-remove-all-graders-from-post").hide();
                }
                jQuery("#modal_remove_all_graders_from_post").modal('show');
            }
        });
    } else {
        jQuery.ajax({
            url : ENTRADA_URL + "/admin/exams/exams?section=api-exams",
            type : "POST",
            data : data,
            success : function(data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status == "success") {
                    window.location.href = ENTRADA_URL + "/admin/exams/exams?section=graders&post_id="+POST_ID+"&step=2";
                }
            }
        });
    }
});

jQuery(document).on("click", ".btn-modal-remove-all-graders-from-post", function(e) {

    data = {"method" : "delete-all-grader-settings", "post_id" : POST_ID};

    jQuery.ajax({
        url : ENTRADA_URL + "/admin/exams/exams?section=api-exams",
        type : "POST",
        data : data,
        success : function(data) {
            var jsonResponse = JSON.parse(data);
            if (jsonResponse.status == "success") {
                window.location.href = ENTRADA_URL + "/admin/exams/exams?section=graders&post_id="+POST_ID+"&step=2";
            }
        }
    });
});

jQuery(document).on("click", "#randomly-distribute-groups", function(e) {
    e.preventDefault();

    if (jQuery('input[name^="chk_graders"]').length < 1) {
        alert("You must add at least one grader");
        return false;
    }

    if (jQuery("input[name^='group']").length < 1) {
        alert("There is no group to assign.");
        return false;
    }

    var grader = new Array();

    jQuery('input[name^="chk_graders"]').each(function() {
        grader.push(jQuery(this).val());
    });

    var i = 0;
    jQuery("input[name^='group']").shuffle().each(function() {
        var div = jQuery(document.createElement("div")).attr({
            style: "margin-bottom: 10px;",
            "data-id": jQuery(this).val(),
            "data-name": jQuery(this).data("name")
        });
        var img = jQuery(document.createElement("img")).attr({
            id: "remove-group-" + jQuery(this).val(),
            src: "/images/action-delete.gif",
            class: "remove-group pull-right",
            style: "cursor: pointer;",
            "data-id": jQuery(this).val(),
            "data-grader": grader[i]
        });

        div.append(jQuery(this).attr('data-name')).append(img);

        if (jQuery("#td-graders-to-group-" + grader[i]).html() == "<i>No groups assigned</i>") {
            jQuery("#td-graders-to-group-" + grader[i]).html(div);
        } else {
            jQuery("#td-graders-to-group-" + grader[i]).append(div);
        }

        jQuery("#graders-assignments-container").append(
            jQuery(document.createElement("input")).attr({
                type: "hidden",
                name: "g_assignment_"+grader[i]+"[]",
                value: jQuery(this).val()
            })
        );

        jQuery(this).closest("tr").remove();

        if (++i == grader.length) {
            i=0;
        }
    });

    refreshAudienceList();
});

// Set up listeners for course groups
jQuery(document).ready(function($) {
    refreshAudienceList();

    if ($('#grader_name').length) {
        $('#grader_name').autocomplete({
            source: ENTRADA_URL + '/admin/exams/exams?section=api-graders&course_id=' + COURSE_ID,
            minLength: 2,
            appendTo: $('#grader_name_auto_complete'),
            select: function (e, ui) {
                selectItem(ui.item.proxy_id, ui.item.fullname, 'grader');
                copyItem('grader');
                e.preventDefault();
            }
        }).data("autocomplete")._renderItem = function (ul, item) {

            if (typeof(item.response) == "undefined" || item.response.length == 0) {

                var user_li = $(document.createElement("li")).data("item.autocomplete", item);
                var template_a = $(document.createElement("a"));
                var details_div = $(document.createElement("div")).addClass("course-auto-details");
                var secondary_details_span = $(document.createElement("span")).addClass("course-auto-secondary-details");
                var name_span = $(document.createElement("span")).addClass("course-auto-name");
                var email_span = $(document.createElement("span")).addClass("course-auto-email");
                var group_role_span = $(document.createElement("span"));

                name_span.html("<strong>" + item.fullname + "</strong><br/>");
                email_span.html(item.email + " <br/>");
                group_role_span.html(item.organisation_title);

                $(secondary_details_span).append(group_role_span);
                $(details_div).append(name_span).append(email_span).append(secondary_details_span);
                $(template_a).append(details_div);
                $(user_li).append(template_a);

                return (user_li.appendTo(ul));
            } else {
                var user_li = $(document.createElement("li")).data("item.autocomplete", item);
                $(user_li).html(item.response);
                return (user_li.appendTo(ul));
            }
        };

        $('#grader_name').on('keypress', function (e) {
            if (e.which == 13) {
                addGrader('grader');
                e.preventDefault();
            }
        });
    }
});
