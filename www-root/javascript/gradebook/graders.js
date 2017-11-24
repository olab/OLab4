var ENTRADA_URL;
var COURSE_ID;
var CPERIOD_ID;
var ASSESSMENT_ID;
var ASSIGN_TO_GRADER_TEXT;

function addItemNoError(type) {
    if ((jQuery('#' +type+'_id').length) && (jQuery('#' +type+'_id').val() != '') && (jQuery('#'+type+'_'+jQuery('#'+type+'_id').val()).length == 0)) {
        addGrader(type);
    }
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

function removeItem(id, type) {
    if (jQuery('#'+type+'_'+id)) {
        jQuery('#'+type+'_'+id).remove();
    }
}

function selectItem(id, fullname, type) {
    if ((id != null) && (fullname != null) && (jQuery('#'+type+'_id').length) && (jQuery('#'+type+'_name').length)) {
        jQuery('#'+type+'_id').val(id);
        jQuery('#'+type+'_name').val(fullname);
    }
}

function buildUnassignedAudience(audience, assigned_learners) {
    jQuery.each(audience, function(audience, member) {
        if (assigned_learners.indexOf(member.proxy_id) > -1) {
            return true;
        }

        if (jQuery('img[data-id="' + member.proxy_id + '" ]').length < 1) {
            var tr = jQuery(document.createElement("tr"));
            var td_lft = jQuery(document.createElement("td"));
            var td_rgt = jQuery(document.createElement("td"));
            var lbl = jQuery(document.createElement("label")).attr({
                for: 'learner_' + member.proxy_id
            });
            var ckbox = jQuery(document.createElement("input")).attr({
                type: 'checkbox',
                id: 'learner_' + member.proxy_id,
                name: 'learner[]',
                "data-name": member.lastname + ', ' + member.firstname,
                value: member.proxy_id
            });

            var lnk = jQuery(document.createElement("a")).attr({
                class: 'a-assign-single-grader',
                href: '#assign-grader-modal',
                'data-toggle': 'modal',
                'data-id': member.proxy_id
            }).append(ASSIGN_TO_GRADER_TEXT);

            td_rgt.append(lnk);
            td_lft.append(lbl.append(ckbox).append(' ' + member.lastname + ', ' + member.firstname));
            tr.append(td_lft).append(td_rgt);

            jQuery("#table-learners-no-grader tbody").append(tr);
        }
    });
}

function updateUnassignedAudience() {
    var assigned_learners = [];

    jQuery.when(
        jQuery.ajax ({
            url : ENTRADA_URL + "/api/gradebook.graders.api.php",
            type : "GET",
            data : "method=get_assigned_learners&assessment_id=" + ASSESSMENT_ID,
            success: function(data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status == "success") {
                    assigned_learners = jsonResponse.data;
                }
            }
        })
    ).done ( function() {
        jQuery.ajax({
            url: ENTRADA_URL + "/api/course-enrolment.api.php",
            type: "GET",
            data: "method=list&course_id=" + COURSE_ID + "&cperiod_id=" + CPERIOD_ID,
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status == "success") {
                    jQuery("#table-learners-no-grader tbody").html('');

                    jQuery.each(jsonResponse.data, function (audience_type, audience_type_members) {
                        if (audience_type == "groups") {
                            jQuery.each(audience_type_members, function (group_name, audience) {
                                buildUnassignedAudience(audience, assigned_learners);
                            });
                        } else {
                            buildUnassignedAudience(audience_type_members, assigned_learners);
                        }
                    });

                    refreshGroupList();
                }
            }
        })
    });
}

function addGrader(type) {
    if ((jQuery('#'+type+'_id').length) && (jQuery('#'+type+'_id').val() != '') && (jQuery('#'+type+'_'+jQuery('#'+type+'_id').val()).length == 0)) {
        var tr = jQuery(document.createElement("tr"));
        var td_grader = jQuery(document.createElement("td"));
        var td_empty = jQuery(document.createElement("td")).attr({
            id: "td-graders-to-learner-"+jQuery('#'+type+'_id').val()
        });

        td_empty.append('<i>No learners assigned</i>');

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

        jQuery("#table-graders-to-learners").append(tr);

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

jQuery(document).ready(function($) {
    updateUnassignedAudience();

    $("#all-learners").on("change", function() {
        $('input[name^="learner"]').each(function() {
            $(this).prop("checked", $("#all-learners").is(":checked"));
        });
    });

    $("#all-groups").on("change", function() {
        $('input[name^="groups"]').each(function() {
            $(this).prop("checked", $("#all-groups").is(":checked")).change();
        });
    });

    $(document).on("change", ".checkbox_group", function() {
        var _this = this;
        var groupId = $(this).attr("data-group-id");
        $(".in-group-" + groupId).each(function(i, learner) {
            $(this).prop("checked", $(_this).is(":checked"));
        });
    })

    $(document).on("click", ".btn-assign-group", function(e) {
        e.preventDefault();

        // get group id
        var groupId = $(this).parents("tr").find(".checkbox_group").attr("data-group-id");

        // set all students in group to checked
        $(".in-group-" + groupId).prop("checked", true);

        if (populateAssignToGraderModal()) {
            $($(this).attr("href")).modal('show')
        }
    });

    $("#btn-assign-learner").on("click", function(e) {

        e.preventDefault()

        if (populateAssignToGraderModal()) {
            $($(this).attr("href")).modal('show')
        }
    });

    function populateAssignToGraderModal() {
        var error = false;

        if ($('input[name^="learner"]:checked').length < 1) {
            alert('Please select at least one learner to assign.');
            error = true;
            return false;
        }

        if ($('input[name^="chk_graders"]').length < 1) {
            alert("You must add at least one grader before assigning a learner.")
            error = true;
            return false;
        }

        if (!error) {
            // Build the grader list
            $("#table-assign-grader-modal tbody").html("");

            $('input[name^="chk_graders"]').each(function() {
                var tr = jQuery(document.createElement("tr"));
                var td = jQuery(document.createElement("td"));
                var input = jQuery(document.createElement("input")).attr({
                    type: "radio",
                    name: "assign-grader",
                    id: "assign-grader-"+$(this).val(),
                    value: $(this).val()
                });

                var lbl = jQuery(document.createElement("label")).attr({
                    for: "assign-grader-"+$(this).val(),
                    style: "width: 100%"
                });

                tr.append(td.append(lbl.append(input).append(' '+$(this).attr("data-name"))));
                $("#table-assign-grader-modal tbody").append(tr);
            });

            return true;
        }
    }

    $("#assign-grader-modal .close-assign-grader-modal").on("click", function(e) {
        e.preventDefault();
        $("#assign-grader-modal").modal("hide");
    });

    $(document).on("click", ".a-assign-single-grader", function(e) {
        if ($('input[name^="chk_graders"]').length < 1) {
            $("#assign-grader-modal").modal("hide");
            alert("You must add at least one grader before assigning a learner.");
            return false;
        }

        // Build the grader list
        $("#table-assign-grader-modal tbody").html("");

        $('input[name^="chk_graders"]').each(function() {
            var tr = jQuery(document.createElement("tr"));
            var td = jQuery(document.createElement("td"));
            var input = jQuery(document.createElement("input")).attr({
                type: "radio",
                name: "assign-grader",
                id: "assign-grader-"+$(this).val(),
                value: $(this).val()
            });

            var lbl = jQuery(document.createElement("label")).attr({
                for: "assign-grader-"+$(this).val(),
                style: "width: 100%"
            });

            tr.append(td.append(lbl.append(input).append(' '+$(this).attr("data-name"))));
            $("#table-assign-grader-modal tbody").append(tr);
        });
    });

    $(document).on("click", "#assign-grader-modal .btn-modal-assign-learner", function(e) {
        e.preventDefault();

        // In case somehow this can happen
        if ($('input[name^="learner"]:checked').length < 1) {
            $("#assign-grader-modal").modal("hide");
            alert('Please select at least one learner to assign.');
        }

        var grader = $("input[name='assign-grader']:checked").val();
        if (! grader) {
            $("#assign-grader-modal .alert").html($(document.createElement("ul")).append($(document.createElement("li"))).append("Please select a grader from the list."));
            $("#assign-grader-modal .alert").show();
            setTimeout(function () {
                $("#assign-grader-modal .alert").fadeOut("slow");
            }, 3000);

            return false;
        }

        $("input[name^='learner']:checked").each(function() {
            var div = jQuery(document.createElement("div")).attr({
                style: "margin-bottom: 10px;",
                "data-id": $(this).val(),
                "data-name": $(this).data("name")
            });
            var img = jQuery(document.createElement("img")).attr({
                id: "remove-learner-" + $(this).val(),
                src: "/images/action-delete.gif",
                class: "remove-learner pull-right " + ($(this).hasClass("in-group") ? "in-group in-group-" + $(this).attr("data-group-id") : "" ),
                style: "cursor: pointer;",
                "data-id": $(this).val(),
                "data-grader": grader
            });

            div.append($(this).attr('data-name')).append(img);

            if (jQuery("#td-graders-to-learner-" + grader).html() == "<i>No learners assigned</i>") {
                jQuery("#td-graders-to-learner-" + grader).html(div);
            } else {
                jQuery("#td-graders-to-learner-" + grader).append(div);
            }

            $("#graders-assignments-container").append(
                jQuery(document.createElement("input")).attr({
                    type: "hidden",
                    name: "g_assignment_"+grader+"[]",
                    value: $(this).val()
                })
            );

            $(this).closest("tr").remove();
        })

        refreshGroupList();

        $("#assign-grader-modal").modal("hide");
    });

    $(document).on("click", ".remove-learner", function() {
        var id = $(this).data("id");
        var grader = $(this).data("grader");

        $('input[name^="g_assignment_'+grader+'"]').each(function() {
            if ($(this).val() == id) {
                $(this).remove();
            }
        });

        $(this).closest("div").remove();
        if ($("#td-graders-to-learner-"+grader).html()=='') {
            $("#td-graders-to-learner-"+grader).html('<i>No learners assigned</i>');
        }

        updateUnassignedAudience();
    });

    $("#randomly-distribute-learners").on("click", function(e) {
        e.preventDefault();

        if ($('input[name^="chk_graders"]').length < 1) {
            alert("You must add at least one grader");
            return false;
        }

        if ($("input[name^='learner']").length < 1) {
            alert("There is no learner to assign.");
            return false;
        }

        var grader = new Array();

        $('input[name^="chk_graders"]').each(function() {
            grader.push($(this).val());
        });

        var i = 0;
        $("input[name^='learner']").each(function() {
            var div = jQuery(document.createElement("div")).attr({
                style: "margin-bottom: 10px;",
                "data-id": $(this).val(),
                "data-name": $(this).data("name")
            });
            var img = jQuery(document.createElement("img")).attr({
                id: "remove-learner-" + $(this).val(),
                src: "/images/action-delete.gif",
                class: "remove-learner pull-right",
                style: "cursor: pointer;",
                "data-id": $(this).val(),
                "data-grader": grader[i]
            });

            div.append($(this).attr('data-name')).append(img);

            if (jQuery("#td-graders-to-learner-" + grader[i]).html() == "<i>No learners assigned</i>") {
                jQuery("#td-graders-to-learner-" + grader[i]).html(div);
            } else {
                jQuery("#td-graders-to-learner-" + grader[i]).append(div);
            }

            $("#graders-assignments-container").append(
                jQuery(document.createElement("input")).attr({
                    type: "hidden",
                    name: "g_assignment_"+grader[i]+"[]",
                    value: $(this).val()
                })
            );

            $(this).closest("tr").remove();

            if (++i == grader.length) {
                i=0;
            }
        });

        refreshGroupList()
    });

    $("#btn-remove-graders").on("click", function() {
        $("#table-modal-remove-grader tbody").html('');

        if ($('input[name^="chk_graders"]:checked').length < 1) {
            alert('Please select one or more grader(s) to remove');
            return false;
        }

        $('input[name^="chk_graders"]:checked').each(function() {
            var tr = jQuery(document.createElement("tr"));
            var td_grad = jQuery(document.createElement("td")).append($(this).data("name"));
            var td_learn = jQuery(document.createElement("td"));

            jQuery("#td-graders-to-learner-"+$(this).val()+" div").each(function() {
                td_learn.append($(this).data("name") + "<br />");
            });

            if (td_learn.html() == '') {
                td_learn.html('<i>No learners assigned</i>');
            }

            tr.append(td_grad).append(td_learn);

            $("#table-modal-remove-grader tbody").append(tr);
        });
    });

    $("#modal-remove-grader .btn-modal-remove-grader").on("click", function(e) {
        e.preventDefault();

        $('input[name^="chk_graders"]:checked').each(function() {
            $('input[name^="g_assignment_'+$(this).val()+'"]').each(function() {
                $(this).remove();
            });

            $(this).closest("tr").remove();
        });

        updateUnassignedAudience();

        $("#modal-remove-grader").modal("hide");
    });
});

/**
 * Gradebook Assessment List
 */
function refreshViewingGraders() {
    jQuery.ajax ({
        url : ENTRADA_URL + "/api/gradebook.graders.api.php",
        type : "GET",
        data : "method=grader_list&assessment_id=" + ASSESSMENT_ID + "&cperiod_id=" + CPERIOD_ID + "&course_id=" + COURSE_ID,
        success: function(data) {
            jQuery("#viewing-graders").html(data);
        }
    });
}

function refreshGroupList() {
    jQuery.ajax ({
        url : ENTRADA_URL + "/api/gradebook.graders.api.php",
        type : "GET",
        data : "method=get_groups&cperiod_id=" + CPERIOD_ID + "&course_id=" + COURSE_ID,
        success: function(data) {
            var jsonResponse = JSON.parse(data);
            if (jsonResponse.status == "success") {

                jQuery("#table-groups-no-grader tbody").empty()

                jQuery.each(jsonResponse.data, function (i, group) {

                    // Add group class to each student
                    if (jQuery("#learner_" + group.proxy_id).length) {

                        jQuery("#learner_" + group.proxy_id).addClass("in-group in-group-" + group.cgroup_id).attr("data-group-id", group.cgroup_id);

                        if (jQuery("#group_" + group.cgroup_id).length == false) {
                            var id = "group_" + group.cgroup_id;

                            var tr = document.createElement("tr")
                            var td_group = document.createElement("td")
                            var label = document.createElement("label")
                            label.for = id;

                            var input = document.createElement("input");
                            input.type = "checkbox";
                            input.name = "groups[]";
                            input.id = id;
                            input.className = "checkbox_group";
                            input.setAttribute("data-group-id", group.cgroup_id);

                            label.appendChild(input)
                            var group_name = document.createTextNode(" " + group.group_name)
                            label.appendChild(group_name)

                            td_group.appendChild(label)

                            tr.appendChild(td_group)

                            var td = document.createElement("td");
                            var a = document.createElement("a");
                            a.href = "#assign-grader-modal";
                            a.className = "btn-assign-group";

                            var aText = document.createTextNode(ASSIGN_TO_GRADER_TEXT);
                            a.appendChild(aText);
                            td.appendChild(a);

                            tr.appendChild(td);

                            jQuery("#table-groups-no-grader tbody").append(tr)
                        }
                    }
                });
            }
        }
    });
}

jQuery(document).ready(function ($) {
    refreshViewingGraders();
    refreshGroupList();

    $(document).on("change", ".checkbox_group", function() {

    })

    $(document).on("click", ".remove_grader", function () {
        var grader_id = $(this).data("id");
        var grader_name = $(this).data("name");

        /**
         * Get the list for the learners attached to the selected Grader
         */
        $.ajax ({
            url : ENTRADA_URL + "/api/gradebook.graders.api.php",
            type : "GET",
            data : "method=get_learners&assessment_id=" + ASSESSMENT_ID + "&cperiod_id=" + CPERIOD_ID + "&grader_id=" + grader_id,
            success: function(data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status == "success") {
                    $("#table-modal-remove-grader tbody").html('');

                    var tr = jQuery(document.createElement("tr"));
                    var td_grad = jQuery(document.createElement("td")).append(grader_name);
                    var hiddn = jQuery(document.createElement("input")).attr({
                        type: "hidden",
                        name: "delete-grader-id",
                        id: "delete-grader-id",
                        value: grader_id
                    });
                    td_grad.append(hiddn);
                    var td_learn = jQuery(document.createElement("td"));

                    jQuery.each(jsonResponse.data, function(no, learner) {
                        td_learn.append(learner + "<br />");
                    });

                    if (td_learn.html() == '') {
                        td_learn.html('<i>No learners assigned</i>');
                    }

                    tr.append(td_grad).append(td_learn);

                    $("#modal-remove-grader-from-list tbody").append(tr);
                }
            }
        });
    });

    $("#modal-remove-grader-from-list .btn-modal-remove-grader-from-list").on("click", function() {
        var grader_id = $("#delete-grader-id").val();

        $("#modal-remove-grader-from-list").modal("hide");

        $.ajax ({
            url : ENTRADA_URL + "/api/gradebook.graders.api.php",
            type : "POST",
            data : "method=delete_grader&assessment_id=" + ASSESSMENT_ID + "&cperiod_id=" + CPERIOD_ID + "&grader_id=" + grader_id,
            success: function(data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status == "success") {
                    $("#selector-select-grader-filter").val("");
                    $("#select-grader-filter").submit();
                } else {
                    alert(data);
                }
            } 
        });
    });
    
    $("#selector-select-grader-filter").on("change", function() {
        $("#select-grader-filter").submit();
    });
});
jQuery(document).ready(function($) {
    if ($('#grader_name').length) {
        $('#grader_name').autocomplete({
            source: ENTRADA_URL + '/api/personnel.api.php?type=people&out=json',
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
