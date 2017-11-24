var is_groups;
var group_ids = new Array();
var group_names = new Array();
var curriculum_tracks = new Array();

jQuery(document).ready(function ($) {

    function addItem(type) {

        var type_head = '#' + type;
        var type_id   = type_head + '_id';
        var type_ref  = type_head + '_ref';
        var type_name = type_head + '_name';
        var type_list = type_head + '_list';

        if (($(type_id).length) && ($(type_id).val() != '') && ($(type_head+'_'+$(type_id).val()).length == 0)) {
            var li = $(document.createElement("li"))
                .addClass('community')
                .attr({'id': type+'_'+$(type_id).val(), 'data-proxy-id': $(type_id).val() })
                .css('cursor', 'move').html($(type_ref).val());
            var img = $(document.createElement("img"))
                .addClass('list-cancel-image')
                .attr({src: '/images/action-delete.gif', onclick: 'removeItem(\''+$(type_id).val()+'\', \''+type+'\')'});
            $(type_name).val('');
            $(li).append(img);
            $(type_id).val('');
            $(type_list).append(li);
            $(type_list).sortable("refresh");
            updateOrder(type);
        } else if ($(type_head+'_'+$(type_id).val()).length) {
            alert('Important: Each user may only be added once.');
            $(type_id).val('');
            $(type_name).val('');
            return false;
        } else if ($(type_name).val() != '' && $(type_name).val().length) {
            alert('Important: When you see the correct name pop-up in the list as you type, make sure you select the name with your mouse, do not press the Enter button.');
            return false;
        } else {
            return false;
        }
    }

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

    // Set up course contacts section - autoloading and sortable
    if ($("#director_name").length > 0) {
        createAuto("director_name", "director");
        $('#director_list').sortable({ update: function () { updateOrder('director') }});
        updateOrder('director');
    }

    if ($("#coordinator_name").length > 0) {
        createAuto("coordinator_name", "coordinator");
        $('#coordinator_list').sortable({ update: function () { updateOrder('coordinator') }});
        updateOrder('coordinator');
    }

    if ($("#faculty_name").length > 0) {
        createAuto("faculty_name", "faculty");
        $('#faculty_list').sortable({ update: function () { updateOrder('faculty') }});
        updateOrder('faculty');
    }

    if ($("#pcoordinator_name").length > 0) {
        createAuto("pcoordinator_name", "pcoordinator");
        $('#pcoordinator_list').sortable({ update: function () { updateOrder('pcoordinator') }});
        updateOrder('pcoordinator');
    }

    if ($("#evaluationrep_name").length > 0) {
        createAuto("evaluationrep_name", "evaluationrep");
        $('#evaluationrep_list').sortable({ update: function () { updateOrder('evaluationrep') }});
        updateOrder('evaluationrep');
    }

    if ($("#studentrep_name").length > 0) {
        createAuto("studentrep_name", "studentrep");
        $('#studentrep_list').sortable({ update: function () { updateOrder('studentrep') }});
        updateOrder('studentrep');
    }

    if ($("#ta_name").length > 0) {
        createAuto("ta_name", "ta");
        $('#ta_list').sortable({ update: function () { updateOrder('ta') }});
        updateOrder('ta');
    }


    function createAuto (element_id, type) {
        var auto_item = $("#"+element_id).autocomplete({
            source: ENTRADA_URL + "/api/personnel.api.php?type="+type+"&organisation_id="+ORGANISATION+"&out=json",
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
                addItem(type);
                e.preventDefault();
            },

            search: function () {
                $("#"+element_id).removeClass("search");
                $("#"+element_id).addClass("searching");
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

    var popover_options = {
        animation: false,
        container: "body",
        selector: "[rel=\"popover\"]",
        html: true,
        trigger: "hover",
        placement: "left",
        content: function () {
            var target_id = $(this).attr("data-id");
            var index;

            for (index = 0; index < curriculum_tracks.length; index++) {
                if (curriculum_tracks[index]["target_id"] == target_id) {
                    break;
                }
            }

            return curriculum_tracks[index]["curriculum_track_name"];
        }
    };

    $("#curriculum_track_ids").click(function (e) {
        $.each($(".search-filter-item"), function (index, value) {
            $(this).attr("rel", "popover");
        });

        $("#courseForm").on("mouseenter", ".search-filter-item", function (e) {
            e.stopPropagation();

            $(".popover").remove();
            $("[rel=\"popover\"]").popover(popover_options);
            $(this).popover("show");
        });

        $("#courseForm").on("mouseleave", ".search-filter-item", function (e) {
            e.stopPropagation();

            if (!$(".search-filter-item:hover").length) {
                setTimeout(function () {
                    if (!$(".popover:hover").length) {
                        $(".popover").remove();
                    }
                }, 300);
            }
        });

        $("#courseForm").on("click", ".search-filter-item", function (e) {
            $(".popover").remove();
        });
    });

    $("input[name=course_mandatory]").on("change", function() {
        if (this.value == 1) {
            $.each($(".track"), function(i, object) {
                $(object).addClass("hide");
                if ($(object).val() == 1)
                {
                    $(object).attr("checked", true);
                }
            })
        } else {
            $.each($(".track"), function(i, object) {
                $(object).removeClass("hide");
            })
        }

    });

    $("#courseForm").on("click", ".search-target-input-control", function () {

        if ($(this).is(":checked")) {
            // Item has been checked - add to the page.
            if ($("input[name=track_mandatory_" + this.value + "]").length == 0) {
                var course_mandatory = $("input[name=course_mandatory]:checked").val();

                var hide = "";
                var checked = false;
                if (course_mandatory == 1) {
                    hide = "hide";
                    checked = true;
                }

                var tr        = $(document.createElement("tr")).attr({id: "track_" + this.value});
                var td_name   = $(document.createElement("td")).addClass("track-name").addClass("span6").html($(this).attr("data-label"));
                var td_option = $(document.createElement("td")).addClass("track-options").addClass("span4");
                var td_remove = $(document.createElement("td")).addClass("span2");
                var control_group = $(document.createElement("div")).addClass("pull-right");
                var br = $(document.createElement("br"));
                var a = $(document.createElement("a")).attr({
                    href: "#",
                    onclick: "$(this).up().up().remove(); jQuery('input#event_types_" + this.value + "').remove(); return false;"
                }).addClass("remove");
                var img = $(document.createElement("img")).attr({src: ENTRADA_URL + "/images/action-delete.gif"});
                var label1 = $(document.createElement("label")).html("Mandatory").addClass("track").addClass(hide);
                var radio1 = $(document.createElement("input")).attr({
                    type: "radio",
                    name: "track_mandatory_" + this.value,
                    value: 1,
                    checked: checked
                }).addClass("track").addClass(hide);
                var hidden1 = $(document.createElement("input")).attr({
                    type: "hidden",
                    name: "course_track[]",
                    value: this.value
                });
                var label2 = $(document.createElement("label")).html("Additional").addClass("track").addClass(hide);
                var radio2 = $(document.createElement("input")).attr({
                    type: "radio",
                    name: "track_mandatory_" + this.value,
                    value: 0,
                    checked: !checked
                }).addClass("track").addClass(hide);

                a.append(img);
                td_remove.append(a);
                label1.prepend(radio1);
                label2.prepend(radio2);
                td_option.append(label1).append(label2);
                td_option.html(control_group.html(td_option.html()));
                tr.append(td_name).append(td_option).append(td_remove).append(hidden1);

                $("#tracks_container").append(tr);
            }
        } else {
            // Item has been unchecked - remove from the page
            var track_li = "#tracks_container #track_" + this.value;
            if ($(track_li).length > 0) {
                $(track_li).remove();
            }
        }
    });

    $("#courseForm").on("click", ".remove-target-toggle", function () {
        var target_id = $(this).attr("data-id");
        var track_li = "#tracks_container #track_" + target_id;

        if ($(track_li).length > 0) {
            $(track_li).remove();
        }
    });

    $("#curriculum_track_ids").advancedSearch({
        resource_url: ENTRADA_URL,
        filters: {
            event_types: {
                label: "Curriculum Tracks",
                data_source: curriculum_tracks
            }
        },
        list_selections :false,
        no_results_text: "No Curriculum Tracks found matching the search criteria",
        parent_form: $("#courseForm"),
        width: 400
    });

    $('#period_list').on('click', '.remove_audience', function(e) {
        //check if the event target is <i> or <img>.  This depends upon
        //the element being added by the AutoComplete.js which uses the img tag still
        //or built on page load with the bootstrap icon.

        if ($(e.target).is("i")) {
            var period_info = String($(e.target).parent().parent().attr("id")).split('_');
            var id_info = String($(e.target).parent().attr("id")).split('_');
        } else {
            var period_info = String($(e.target).parent().parent().parent().attr("id")).split('_');
            var id_info = String($(e.target).parent().parent().attr("id")).split('_');
        }

        var period_id = period_info[2];
        var type = id_info[1];
        var id = id_info[2];

        if ($(e.target).hasClass("cohort")) {
            period_id = period_info[3];
        }

        if ($(e.target).hasClass("course_list")) {
            period_id = period_info[4];
            type = "course_list";
            id = id_info[3];
        }

        if (type==='cohort') {
            $('#cohort_container_' + period_id).show();
            var members_array = $('#cohort_audience_members_'+period_id).val().split(',');
            var idx = $.inArray(id, members_array);
            if (idx != -1) {
                members_array.splice(idx,1);
            }
            $('#cohort_audience_members_'+period_id).val(members_array.join(','));
            $("#cohort_select_"+period_id+" option[value='"+id+"']").removeAttr('disabled');
        }

        if (type==='course_list') {
            $('#course_list_container_' + period_id).show();
            var members_array = $('#course_list_audience_members_'+period_id).val().split(',');
            var idx = $.inArray(id, members_array);
            if (idx != -1) {
                members_array.splice(idx,1);
            }
            $('#course_list_audience_members_'+period_id).val(members_array.join(','));
            $("#course_list_select_"+period_id+" option[value='"+id+"']").removeAttr('disabled');
        }

        if ($(e.target).is("i")) {
            $(e.target).parent().remove();
            if ($('#cohort_audience_container_' + period_id).children().length == 0) {
                $('#cohort_container_' + period_id).hide();
            }
        } else {
            $(e.target).parent().parent().remove();
            if ($(e.target).parent().parent().parent().children().length == 0) {
                $(e.target).parent().parent().parent().parent().hide();
            }
        }
    });

    function getGroups(organisation) {
        var target_request = $.ajax({
            url: ENTRADA_URL + "/api/course-groups.api.php",
            data: "organisation_id=" + organisation,
            type: "POST"
        });

        $.when(target_request).done(function (data) {
            var jsonResponse = JSON.parse(data);
            if (jsonResponse.status === "success") {
                if (jsonResponse.data.length > 0) {
                    $.each(jsonResponse.data, function(i, group) {
                        group_ids[i] = group.group_id
                        group_names[i] = group.group_name
                    });
                    is_groups = true;
                } else {
                    is_groups = false;
                }


            } else {
                is_groups = false;
            }
        });
    }

    function getTracks(organisation) {
        var target_request = $.ajax({
            url: ENTRADA_URL + "/api/curriculum-tracks.api.php",
            data: "organisation_id=" + organisation,
            type: "POST"
        });

        $.when(target_request).done(function (data) {
            var jsonResponse = JSON.parse(data);
            if (jsonResponse.status === "success") {
                if (jsonResponse.data.length > 0) {
                    $.each(jsonResponse.data, function(i, track) {
                        var curriculum_track = [];
                        curriculum_track["target_id"] = track.curriculum_track_id;
                        curriculum_track["target_label"] = track.curriculum_track_name;
                        curriculum_track["curriculum_track_name"] = track.curriculum_track_name;
                        curriculum_tracks.push(curriculum_track);
                    });
                    is_groups = true;
                } else {
                    is_groups = false;
                }


            } else {
                is_groups = false;
            }
        });
    }

    $('div#course-enrolment-section').on('click', '.remove_period',function(e) {
        var id_info = e.target.id.split('_');
        var id = id_info[2];
        $('#period_item_'+id).remove();
        $("#period_select option[value='"+id+"']").removeAttr('disabled');
    });
    
    getGroups(ORGANISATION);
    getTracks(ORGANISATION);

    $("input[name='sync_ldap']").on("change", function() {
        if ($(this).val() == "1") {
            $(".ldap-course-sync-list").slideDown("fast");
            if ($("textarea[name='sync_ldap_courses']").val().length <= 0) {
                $("textarea[name='sync_ldap_courses']").attr("value", $("#course_code").val());
            }
        } else {
            $(".ldap-course-sync-list").slideUp("fast");
        }
    });


    $("input[name=syllabus_enabled]").on("click", function() {
        if ($(this).val() == "enabled") {
            $("#syllabus-settings").show();
        } else {
            $("#syllabus-settings").hide();
        }
    });

    $('#course_report_ids.chosen-select').chosen({no_results_text: 'No Reports Available.'});

    $('div#period_list').on("click", 'a.enrollment-toggle', function(event) {

        var section_id = $(this).attr("id").split("add_audience_")[1];
        $('#audience_section_' + section_id).show();
        $('#no_audience_msg_' + section_id).remove();
        $('#audience_type_select_' + section_id).fadeToggle("slow", "swing",
            function() {
                if ($('#audience_type_select_' + section_id).is(':visible')) {
                    $('#add_audience_' + section_id).html("Done");
                } else {
                    $('#add_audience_' + section_id).html("Add Audience");
                }
            }
        );
    });

    $('#curriculum_type_periods').on("change", '#period_select', function(event) {
        var option = $("option:selected", this);
        var course_id = 0;
        if (typeof RESOURCE_ID != "undefined") {
            course_id = RESOURCE_ID;
        }
        $.ajax({
            url: ENTRADA_URL + "/api/curriculum_periods.api.php",
            data: "key=" + $(option).val() + "&course_id=" + course_id,
            type: "GET",
            dataType: "html",
            success: function(data) {
                $('#period_list').append(data);
                var period_id = $(option).val();

                $('#period_select option[value='+period_id+']').attr('disabled', 'disabled');
                $("#period_select").val('0');
                $('#period_list').show();
                $('#student_'+period_id+'_name').autocompletelist({ type: 'student_'+period_id, url: ENTRADA_URL + '/api/personnel.api.php?type=student', remove_image: DELETE_IMAGE_URL});

                if ($("#enrollment-required").length <= 1) {
                    $("#enrollment-required").fadeOut();
                    $("input[name=syllabus_enabled]").removeAttr("disabled");
                }
            }
        });
    });

    $('div#period_list').on("click keyup", ".individual_add_btn", function(event) {
        var period_id = $(this).attr("id").split("add_associated_student_")[1];
        var student_list_container = $('#student_' + period_id + "_list_container");
        if (event.type == "keyup") {
            var code = event.keyCode || event.which;
            if (code == 13) { //Enter keycode
                //show student list if not visible
                if (!$(student_list_container).is(':visible')) {
                    $(student_list_container).show();
                }
            }
        } else {
            if (!$(student_list_container).is(':visible')) {
                $(student_list_container).show();
            }
        }
    });

    $('div.audience_list .sortableList').each(function() {

        if ($(this) !== 'undefined') {
            if ($(this).children().length > 0) {
                $(this).parent().parent().show();
            } else {
                $(this).parent().parent().hide();
            }
        }
    });

    $('div#period_list').on("click", 'img.list-cancel-image', function(e) {

        $('div.audience_list .sortableList').each(function() {
            if ($(this) !== 'undefined') {
                if ($(this).children().length > 0) {
                    $(this).parent().parent().show();
                } else {
                    $(this).parent().parent().hide();
                }
            }
        });
    });

    $('div#period_list').on("change", '.audience_list .listContainer ol', function(e) {

        if ($(this).children().length > 0) {
            $(this).parent().parent().show();
        } else {
            $(this).parent().parent().hide();
        }
    });

    if (typeof COURSE_COLOR_PALETTE != 'undefined' && Array.isArray(COURSE_COLOR_PALETTE)) {
        color_picker('#course_color', COURSE_COLOR_PALETTE);
    } else {
        color_picker('#course_color');
    }
});

// remove an item from the list, refresh the sortable, and update the hidden list of proxy id's
function removeItem(id, type) {
    if (jQuery('#'+type+'_'+id)) {
        jQuery('#'+type+'_'+id).remove();
        jQuery('#'+type+'_list').sortable("refresh");
        updateOrder(type);
    }
}

// update the hidden associated_<type> field with a list of the proxy id's (in data-proxy-id attribute), in the correct order
function updateOrder(type) {
    var proxy_list = jQuery('#' + type+'_list').sortable("toArray", {attribute: "data-proxy-id"});
    jQuery('#associated_'+type).val(proxy_list);
}

function loadCurriculumPeriods(ctype_id) {
    jQuery.ajax({
        url: ENTRADA_URL + "/api/curriculum_type_periods.api.php",
        data: {
            'ctype_id': ctype_id
        },
        type: "POST",
        success: function(data) {
            jQuery('#curriculum_type_periods').html(data);
        },
        error: function(data) {
            jQuery('#curriculum_type_periods').html(jQuery(document.createElement('div').addClass('display-error').html('No Periods were found for this Curriculum Category.')));
        }
    });
}


function showSelect(period_id,type) {
    jQuery('.type_select').each(function() {
        $(this).hide();
    });

    if (type=='cohort') {
        jQuery('#'+type+'_select_'+period_id).show();
        jQuery('#course_list_select_'+period_id).hide();
        jQuery("#student_example_"+period_id).hide();
    }

    if (type=='course_list') {
        jQuery('#'+type+'_select_'+period_id).show();
        jQuery('#cohort_select_'+period_id).hide();
        jQuery("#student_example_"+period_id).hide();
    }

    if (type=='individual') {
        jQuery('#student_'+period_id+'_name').show();
        jQuery('#add_associated_student_'+period_id).show();
        jQuery("#student_example_"+period_id).show();
    }
    jQuery('.audience_type_select').each(function() {
        jQuery(this).val('0');
    });
    jQuery("#audience_type_select_"+period_id).show();
    jQuery("#audience_type_select_"+period_id+" option[value='"+type+"']").attr('selected','selected');
}

function addAudience(period_id,audience_value,type,select_value) {
    if (type=='individual') {
        audience_value = jQuery('#individual_select_'+period_id).val();
    }
    var li = jQuery(document.createElement('li')).attr({id: 'audience_'+type+'_'+select_value}).addClass('audience_'+type);
    li.append(audience_value + "&nbsp;");
    li.append(jQuery(document.createElement('i')).css({cursor:'pointer', float:'right'}).addClass('remove_audience ' + type + ' icon-minus-sign'));
    jQuery('#' + type + '_audience_container_'+period_id).append(li);

    jQuery('#'+type+'_select_'+period_id).val('');
    jQuery('#audience_section_' + period_id).show();

    if (type=='cohort') {
        if (!jQuery('#cohort_container_' + period_id).is(':visible')) {
            jQuery('#cohort_container_' + period_id).show();
        }
        jQuery('#'+type+'_select_'+period_id).val('0');
        jQuery("#cohort_select_"+period_id+" option[value='"+select_value+"']").attr('disabled','disabled');
        var ids = jQuery('#cohort_audience_members_'+period_id).val().split(',');
        if (jQuery('#cohort_audience_members_'+period_id).val().length == 0) {
            idx = 0;
        } else {
            idx = ids.length;
        }
        ids[idx] = select_value;
        jQuery('#cohort_audience_members_'+period_id).val(ids.join(','));
    }   else if (type=='course_list') {
        if (!jQuery('#course_list_container_' + period_id).is(':visible')) {
            jQuery('#course_list_container_' + period_id).show();
        }
        jQuery('#'+type+'_select_'+period_id).val('0');
        jQuery("#course_list_select_"+period_id+" option[value='"+select_value+"']").attr('disabled','disabled');
        var ids = jQuery('#course_list_audience_members_'+period_id).val().split(',');
        if (jQuery('#course_list_audience_members_'+period_id).val().length == 0) {
            idx = 0;
        } else {
            idx = ids.length;
        }
        ids[idx] = select_value;
        jQuery('#course_list_audience_members_'+period_id).val(ids.join(','));
    } else {
        if (!jQuery('#student_' + period_id + '_list_container').is(':visible')) {
            jQuery('#student_' + period_id + '_list_container').show();
        }
    }
}

