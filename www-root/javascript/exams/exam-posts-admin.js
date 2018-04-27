var dates_times = {};
var exam_post_ids = [];
var exceptions = {};
var course_id;
jQuery(function($) {
    dates_times["exam_start_date"]      = $("#exam_start_date").val();
    dates_times["exam_start_time"]      = $("#exam_start_time").val();
    dates_times["exam_end_date"]        = $("#exam_end_date").val();
    dates_times["exam_end_time"]        = $("#exam_end_time").val();
    dates_times["exam_submission_date"] = $("#exam_submission_date").val();
    dates_times["exam_submission_time"] = $("#exam_submission_time").val();

    dates_times["release_start_date"]     = $("#release_start_date").val();
    dates_times["release_start_time"]     = $("#release_start_time").val();
    dates_times["release_end_date"]       = $("#release_end_date").val();
    dates_times["release_end_time"]       = $("#release_end_time").val();

    dates_times["initial_release_start_date"]     = $("#release_start_date").val();
    dates_times["initial_release_start_time"]     = $("#release_start_time").val();
    dates_times["initial_release_end_date"]       = $("#release_end_date").val();
    dates_times["initial_release_end_time"]       = $("#release_end_time").val();

    dates_times["initial_exam_start_date"]      = $("#exam_start_date").val();
    dates_times["initial_exam_start_time"]      = $("#exam_start_time").val();
    dates_times["initial_exam_end_date"]        = $("#exam_end_date").val();
    dates_times["initial_exam_end_time"]        = $("#exam_end_time").val();
    dates_times["initial_exam_submission_date"] = $("#exam_submission_date").val();
    dates_times["initial_exam_submission_time"] = $("#exam_submission_time").val();

    dates_times["initial_exam_start_date"]      = $("#exam_start_date").val();
    dates_times["initial_exam_start_time"]      = $("#exam_start_time").val();
    dates_times["initial_exam_end_date"]        = $("#exam_end_date").val();
    dates_times["initial_exam_end_time"]        = $("#exam_end_time").val();

    // Set up some vars...
    var referrer = $("#referrer").val();
    var exam_id = $("input#exam-id").val();
    var event_id = $(".event-id").val();
    var redirect_section = $("#wizard-step-input").val();
    var post_id = jQuery("#post_id").val() == "" ? null : parseInt(jQuery("#post_id").val());
    var is_redirect = false;
    var is_editing = false;

    if (redirect_section > 1) {
        is_redirect = true;
    }

    if (post_id != null && post_id > 0) {
        is_editing = true;
    }

    $(".panel .remove-target-toggle").on("click", function (e) {
        e.preventDefault();
        var filter_type = $(this).attr("data-filter");
        var filter_target = $(this).attr("data-id");
        remove_filter(filter_type, filter_target);
    });

    $(".panel .clear-filters").on("click", function (e) {
        e.preventDefault();
        remove_all_filters();
    });

    $(".datepicker").datepicker({
        dateFormat: "yy-mm-dd"
    });

    $(".timepicker").timepicker({
        defaultTime: $(this).val(),
        minutes: {
            starts: 0,
            ends: 59,
            interval: 5
        }
    });

    /**
     *
     * Delete Exam event listener.
     */
    $("#delete_exam_posts").on("click", function (e) {
        getCheckedExamsPosts(e);
    });

    $("#delete-selected-exam-posts").on("click", function (e) {
        deleteSelectedExams(e);
    });

    /**
     *
     * Event listeners associated with creating distributions
     *
     */
    $("#choose-exam-btn").change(function() {
        var title = $("#choose-exam-btn").text();
        $("#exam-title").val(title);

        var course_id = $("#search-targets-form").find("input[name=\"course_id\"]").val();
    });

    $("#release_score").change(function() {
        var release_score_value = $(this).is(":checked") ;
        if (release_score_value == true) {
            $("#release_score_group").removeClass("hide").addClass("show");
        } else {
            $("#release_score_group").removeClass("show").addClass("hide");
            $("#release_feedback").prop("checked", false);
        }
    });

    $("#release_feedback").change(function() {
        var release_feedback_value = $(this).is(":checked") ;
        if (release_feedback_value == true) {
            $("#release_feedback_group").removeClass("hide").addClass("show");
        } else {
            $("#release_feedback_group").removeClass("show").addClass("hide");
        }
    });

    $("#secure_false").change(function() {
        if ($(this).is(":checked")) {
            security_options = false;
            $("#wizard-nav-item-6").removeClass("complete");
            $(".honor-code-group").hide();
            $("#wizard-nav-item-6").fadeOut(500);
        } else {
            $(".honor-code-group").show();
        }
    });

    $("#secure_true").change(function() {
        if ($(this).is(":checked")) {
            security_options = true;
            $(".honor-code-group").show();
            $("#wizard-nav-item-6").fadeIn(500);
        } else {
            $(".honor-code-group").hide();
        }
    });

    $("input:radio[name=secure_mode]").on("click", function(e) {
        var id_select = $(this).val();
        $(".resume_password").attr("disabled", "disabled");
        if (id_select != "rp_now") {
            $("#exam_url").attr("disabled", "disabled");
            $("input[name=\"exam_sponsor\"]").attr("disabled", "disabled");
            $("#rpnow_reviewed_exam").attr("disabled", "disabled");
            $("#rpnow_reviewer_notes").attr("disabled", "disabled");

        }
        $(".item-body").each(function() {
            var id_others = $(this).attr("id");
            if (id_others == id_select) {
                $("#"+id_select).toggle();
                $("#resume_password_"+ id_select + ($("#use_resume_password").is(":checked") ? "" : ":not(#resume_password_seb)")).removeAttr("disabled");
                if (id_select == "rp_now") {
                    $("#exam_url").removeAttr("disabled");
                    $("input[name=\"exam_sponsor\"]").removeAttr("disabled");
                    $("#rpnow_reviewed_exam").removeAttr("disabled");
                    $("#rpnow_reviewer_notes").removeAttr("disabled");
                }
            }else{
                $("#"+id_others).hide();
            }
        });
    });

    $("input:radio[name=secure_mode]:checked").trigger("click");

    $("input:checkbox[id=rpnow_reviewed_exam]").on("change", function(e) {
        var reviewed_exam = $(this).is(":checked");
        if (reviewed_exam == true) {
            $("#reviewer_notes").show();
            $("#rpnow_reviewer_notes").removeAttr("disabled");
        } else {
            $("#reviewer_notes").hide();
            $("#rpnow_reviewer_notes").attr("disabled", "disabled");
        }
    });

    $("input:checkbox[id=rpnow_reviewed_exam]:checked").trigger("change");

    post_id = $("#search-targets-form").find("input[name=\"post_id\"]").val();
    $("#method").val("update");

    /*
    object Date date_object
     */
    function format_date(date_object) {
        var date_collection = {};
        var month = (date_object.getMonth() + 1) < 10 ? "0" + (date_object.getMonth() + 1) : (date_object.getMonth() + 1);
        var day = date_object.getDate() < 10 ? "0" + date_object.getDate() : date_object.getDate();

        date_collection.date_string     = date_object.getFullYear() + "-" + month + "-" + day;
        date_collection.date_string_us  = (date_object.getMonth() + 1) + "/" + date_object.getDate() + "/" + date_object.getFullYear();
        date_collection.time_string     = (date_object.getHours() < 10 ? "0" + date_object.getHours() : date_object.getHours()) + ":" + (date_object.getMinutes() < 10 ? "0" + date_object.getMinutes() : date_object.getMinutes());

        return date_collection;
    }

    function manageDate(date, time) {
        if (typeof date && date != "undefined") {
            var date_array  = date.split("-");
            var time_array  = time.split(":");

            var year        = date_array[0];
            var month       = date_array[1];
            var day         = date_array[2];
            var hours       = time_array[0];
            var mins        = time_array[1];

            var date_obj    = new Date(year, month - 1, day, hours, mins, 0);

            var unix_date   = date_obj.getTime() / 1000;
            return unix_date;
        } else {
            return null;
        }
    }

    function moveTimePicker(input) {
        var modal = $(input).parents(".modal");
        var left;
        var top;
        if (modal.length > 0) {
            var position            = $(input).position();
            var exception           = $(input).parents("#edit-user-exception");
            var exception_position  = exception.position();
            var scroll              = $(document).scrollTop();

            var timepicker          = $("#ui-timepicker-div");
            var table_height        = timepicker.outerHeight();

            if (exception.length > 0) {
                top     = position.top + exception_position.top + input.outerHeight() - scroll ;
            } else {
                top     = position.top + table_height - 70;
            }

            timepicker.css({top: top})
        }
    }

    $(".timepicker").focus(function() {
        var input = $(this);
        moveTimePicker(input);
    });

    /**
     * Secure File functions
     */
    var resource_drop_overlay = $("#resource_drop_overlay");
    var resource_id_value = $("#resource_id");
    var resource_attach_file = $("#resource_attach_file");
    var resource_form = $("#resource_form");
    var modal = $("#modal-secure-file");

    var secure_file_section;
    var secure_file_list;
    var secure_key_section;
    var secure_key_list;
    var secure_key_badge;
    var selected_exam;
    var dragdrop = false;
    var $secure_key_exists = false;
    var post_id = $("#search-targets-form").find("input[name=\"post_id\"]").val();

    $("#wizard-step-6").on("click", ".delete-item", function(event) {
        var $section = $(this).parents(".item-section");
        deleteButtonState($section);
    });

    /**
     * Delete Secure Access Files on click
     */
    $("#wizard-step-6").on("click", "#delete-secure-file", function(event) {
        event.preventDefault();
        event.stopPropagation();
        /**
         * Delete Secure Keys
         * @type {*|jQuery}
         */
        var $delete_file = $(".secure-file-list").find("input[name=\"delete[]\"]:checked").filter(function() {
            return this.value;
        });
        var $local_file = $(".secure-file-list").find("input[name=\"delete[]\"]:checked").filter(function() {
            return !this.value;
        });
        if ($delete_file.length) {
            $.each($delete_file, function() {
                $(".secure-file-list").append("<input type=\"hidden\" name=\"secure_file_delete[]\" value=\"" + $(this).val() + "\" />");
                $(this).parents("tr").remove();
            });
        }
        //Always remove local keys
        if ($local_file.length) {
            $.each($local_file, function () {
                $(this).parents("tr").remove();
            });
        }

        var secure_listing = $(".secure-file-list-content");
        var secure_table = $(secure_listing).find("#seb_file_table > tbody > tr");
        var container = $(secure_listing).find("#secure-access-file-empty");
        if (!$(secure_table).length && !$(container).length) {
            var resource_list_html = "<div class=\"alert alert-info text-center\" id=\"secure-access-file-empty\">";
            resource_list_html += "<i class=\"fa fa-file-o fa-4x\"></i>";
            resource_list_html += "<h3>Please upload the SEB file for this exam post by dropping the file in this box or use the Browse button below</h3>";
            resource_list_html += "<p>Please use the following for the <strong>Start URL</strong> when creating the SEB file in Safe Exam Browser:<br />";
            resource_list_html += "<strong>" + ENTRADA_URL + "/secure/exams?section=attempt&id=" + post_id + "</strong>";
            resource_list_html += "</p>";
            resource_list_html += "<span class=\"btn btn-default btn-file\">";
            resource_list_html += "Browse <input name=\"secure-file\" type=\"file\">";
            resource_list_html += "</span>";
            resource_list_html += "</div>";
            $(".secure-file-list-content").find("table#seb_file_table").remove();
            $(".secure-file-list-content").prepend(resource_list_html);
        }
    });

    /**
     * Delete Secure Access Keys on click
     */
    $("#wizard-step-6").on("click", "#delete-secure-keys", function(event) {
        event.preventDefault();
        event.stopPropagation();
        /**
         * Delete Secure Keys
         * @type {*|jQuery}
         */
        var $delete_keys = $(".secure-key-list").find("input[name=\"delete[]\"]:checked").filter(function() {
            return this.value;
        });

        var $local_keys = $(".secure-key-list").find("input[name=\"delete[]\"]:checked").filter(function() {
            return !this.value;
        });

        if ($delete_keys.length) {
            $.each($delete_keys, function() {
                $(".secure-key-list").append("<input type=\"hidden\" name=\"secure_key_delete[]\" value=\"" + $(this).val() + "\" />");
                $(this).parents("tr").remove();
            });
        }

        //Always remove local keys
        if ($local_keys.length) {
            $.each($local_keys, function () {
                $(this).parents("tr").remove();
            });
        }

        if (!$(".secure-key-list").find("tbody > tr").length) {
            $(".secure-key-list").html("<div class=\"alert alert-info text-center\" id=\"secure-access-file-empty\"><h3>Please add a secure key for this exam.</h3> <p>You must attach a secure key for each supported version of Safe Exam Browser</p></div>");
        }
    });

    $(".add-item, .delete-item").click(function() {
        selected_exam = $(this).parents(".item-container");
        secure_file_section = selected_exam.find(".item-section.secure-file");
        secure_file_list = secure_file_section.find(".secure-file-list");
        secure_key_section = selected_exam.find(".item-section.secure-key");
        secure_key_list = secure_key_section.find(".secure-key-list");
        secure_key_badge = secure_key_section.find(".secure-key-badge");
    });

    $("#add-secure-key").click(function() {
        resource_id_value.val($(this).data("post"));
        var index = $(".secure-key-list").find("table > tbody > tr").length;
        var nextIndex = index+1;

        var new_keys = [];
        var new_key = {
            id: "",
            key: "<input type=\"text\" class=\"input-secure-key\" name=\"secure_key[" + nextIndex + "][key]\" />",
            version: "<input type=\"text\" class=\"input-browser-version\" name=\"secure_key[" + nextIndex + "][version]\" />"
        };

        if (!$(".secure-key-list").find("table").length) {
            new_keys.push(new_key);
            $(".secure-key-list").html(build_key_list(new_keys));
        } else {
            $(".secure-key-list").find("tbody").append(build_key_item(new_key));
        }
    });

    if (window.File && window.FileReader && window.FileList && window.Blob) {
        dragdrop = true;
    }

    if (dragdrop) {

        /**
         * Event listeners for drag and drop file uploading
         */
        $(".secure-file-list-content").on("dragover", function (event) {
            event.preventDefault();
            event.stopPropagation();

            $(this).find("#seb_file_table").hide();
            $(this).find("#secure-access-file-empty").hide();
            if (!$(".secure-file-list-content").find("#secure-file-upload-drop").length) {
                var secure_html = "<div class=\"alert alert-success text-center\" id=\"secure-file-upload-drop\">";
                    secure_html += "<i class=\"fa fa-file-o fa-4x\"></i>";
                    secure_html += "<h3 class=\"text-center\" id=\"secure-file-upload-drop-header\">";
                    secure_html += "<i class=\"fa fa-upload\"></i>";
                    secure_html += "Drop secure access file here to upload</h3>";
                    secure_html += "</div>";

                $(".secure-file-list-content").prepend(secure_html);
            }
        });

        $(".secure-file-list-content").on("dragleave", function (event) {
            event.preventDefault();
            event.stopPropagation();

            $(this).find("#seb_file_table").show();
            if ($(this).find("#secure-access-file-empty").length) {
                $(this).find("#secure-access-file-empty").show();
            }
            $("#secure-file-upload-drop").remove();
        });

        $(".secure-file-list-content").on("drop", function (event) {
            event.preventDefault();
            event.stopPropagation();

            event.dataTransfer = event.originalEvent.dataTransfer;
            var file = event.dataTransfer.files[0];

            $(this).find("#secure-access-file-empty").remove();

            upload_file(file);
        });

        $(".secure-file-list-content").on("change", "input[name=\"secure-file\"]", function (event) {
            event.preventDefault();
            event.stopPropagation();
            var file = $(this)[0].files[0];

            upload_file(file);
        });
    }

    function upload_file (file) {
        var selected_resource = 1;
        var xhr = new XMLHttpRequest();
        var fd = new FormData();
        var valid_file_type = true;
        var post_id = $("#search-targets-form").find("input[name=\"post_id\"]").val();

        switch (selected_resource) {
            case 1 :
                switch (file.type) {
                    case "application/seb" :
                        valid_file_type = true;
                        break;
                }
                break;
        }

        if (valid_file_type) {
            $("#secure-file-upload-drop-header").html("<i class=\"fa fa-upload\"></i> Uploading file. Please wait...");

            fd.append("file", file);
            fd.append("method", "add-secure-file");
            fd.append("resource_attach_file", $("#resource_attach_file").val());
            fd.append("post_id", post_id);

            xhr.open("POST", ENTRADA_URL + "/admin/events?section=api-exams&method=add-secure-file", true);
            xhr.send(fd);
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    var jsonResponse = JSON.parse(xhr.responseText);

                    if (jsonResponse.status === "success") {

                        $(".secure-file-list-content").html(build_file_list(jsonResponse.data["secure_access_file"]));
                        if ($("#secure-file-upload-drop")) {
                            $("#secure-file-upload-drop").remove();
                        }
                        $("#secure-file-header-messages").html("<span class=\"text-success\">Successfully updated secure access file!</span>");

                    } else {
                        display_error(jsonResponse.data, "#resource-msgs", "append");
                    }
                }
            }
        } else {
            $("#secure-file-header-messages").html("<span class=\"text-error\">The file you selected is an invalid file type. Please try again...</span>");
        }
    }

    function build_key_list (resources, target, viewMode) {
        var viewModeOption = (typeof viewMode !== "undefined") ? viewMode : false;

        if (resources != null && resources.length) {
            var resource_list_html = "<table id=\"seb_key_table\" class=\"tableList\"><thead><tr>";
            if (viewModeOption === false) {
                resource_list_html += "<th class=\"modified\"></th>";
            }
            resource_list_html += "<th>Browser Key</th><th>SEB Version</th></tr></thead><tbody>";

            jQuery.each(resources, function (i, resource) {
                resource_list_html += build_key_item(resource);
            });
            resource_list_html += "</tbody></table>";

            $(".secure-key-badge").text(resources.length);

        } else {
            resource_list_html = "<div class=\"alert alert-info text-center\" id=\"secure-access-file-empty\"><h3>Please add a secure key for this exam.</h3> <p>You must attach a secure key for each supported version of Safe Exam Browser</p></div>";
        }

        return resource_list_html;
    }

    function build_key_item (resource) {
        if (resource.id !== null) {
            return "<tr><td class=\"delete-column\"><input type=\"checkbox\" name=\"delete[]\" value=\"" + resource.id + "\" class=\"delete-item delete-secure-key\" /></td><td>" + resource.key + "</td><td>" + resource.version + "</td></tr>";
        } else {
            return false;
        }
    }

    function build_file_item (resource) {
        if (resource.id !== null) {
            return "<tr><td class=\"delete-column\"><input type=\"checkbox\" name=\"delete[]\" value=\"" + resource.id + "\" class=\"delete-item delete-secure-file\" /></td><td>" + resource.file_name + "</td><td>" + resource.updated_date + "</td></tr>";
        } else {
            return false;
        }
    }

    function build_file_list (resource, viewMode) {
        var viewModeOption = (typeof viewMode !== "undefined") ? viewMode : false;
        var resource_list_html = "";

        if (typeof viewModeOption !== "undefined" && typeof resource !== "undefined" && resource !== null && resource.id !== null) {
            resource_list_html = "<table id=\"seb_file_table\" class=\"tableList\"><thead><tr>";

            if (viewModeOption === false) {
                resource_list_html += "<td class=\"modified\"></td>";
            }
            resource_list_html += "<td>File Name</td><td>Last Updated</td></tr></thead><tbody>";
            resource_list_html += build_file_item(resource);
            resource_list_html += "</tbody></table>";

        } else {
            var resource_list_html = "<div class=\"alert alert-info text-center\" id=\"secure-access-file-empty\"><i class=\"fa fa-file-o fa-4x\"></i><h3>Please upload the SEB file for this exam post by dropping the file in this box or use the Browse button below</h3><p>Please use the following for the <strong>Start URL</strong> when creating the SEB file in Safe Exam Browser:<br /><strong>" + ENTRADA_URL + "/secure/exams?section=attempt&id=" + post_id + "</strong></p><span class=\"btn btn-default btn-file\">Browse <input name=\"secure-file\" type=\"file\"></span></div>";
        }

        return resource_list_html;
    }

    function deleteButtonState (target) {
        var itemSection = target;
        var deleteButton = itemSection.children(".item-section-header").find(".delete-item");

        if (itemSection.find("input[type=checkbox]:checked").length) {
            if (deleteButton.hasClass("disabled")) {
                deleteButton.removeClass("disabled");
            }
        } else {
            if (!deleteButton.hasClass("disabled")) {
                deleteButton.addClass("disabled");
            }
        }
    }

    function randomPassword(length) {
        var chars = "abcdefghijklmnopqrstuvwxyz!@#$%&*ABCDEFGHIJKLMNOP1234567890";
        var pass = "";
        for (var x = 0; x < length; x++) {
            var i = Math.floor(Math.random() * chars.length);
            pass += chars.charAt(i);
        }
        return pass;
    }

    /**
     * Audience.
     */
    // Vars:
    var audience_list = {};
    var current_proxy_id;

    // HTML Components:
    var audience_table_body = $("#audience-list-body");
    var user_exception_modal = $("#edit-user-exception");
    var clear_exception_btn = $("#clear-dropdown-exception");

    // Modal inputs:
    var ex_student_name = $("#exception-student-name");
    var cb_excluded = $("#excluded");

    var use_exception_start_date = $("#use_exception_start_date");
    var exception_start_date = $("#exception_start_date");
    var exception_start_time = $("#exception_start_time");

    var use_exception_end_date = $("#use_exception_end_date");
    var exception_end_date = $("#exception_end_date");
    var exception_end_time = $("#exception_end_time");

    var use_exception_submission_date = $("#use_exception_submission_date");
    var exception_submission_date = $("#exception_submission_date");
    var exception_submission_time = $("#exception_submission_time");

    var use_exception_time_factor = $("#use_exception_time_factor");
    var exception_time_factor = $("#exception_time_factor");

    var use_exception_max_attempts = $("#use_exception_max_attempts");
    var exception_max_attempts = $("#exception_max_attempts");

    // Event listeners:
    $("#wizard-step-3").on("click", ".btn-edit-exception", function(e) {
        e.preventDefault();
        edit_exception($(this).data("proxy-id"));
        user_exception_modal.modal("show");
    });

    // Event listeners:
    $("#cancel-dropdown-exception").on("click", function(e) {
        e.preventDefault();
        user_exception_modal.modal("hide");
    });

    $("#update-dropdown-exception").on("click", function(e) {
        e.preventDefault();
        update_exception();
    });

    $("#clear-dropdown-exception").on("click", function(e) {
        e.preventDefault();
        var msg = javascript_translations.clear_exception_message;

        if (confirm(msg)) {
            remove_exception(current_proxy_id);
        }
    });

    cb_excluded.on("change", function() {
        if($(this).is(":checked")) {
            set_excluded_status(true);
        } else {
            set_excluded_status(false);
        }
    });

    /**
     * Removes an exception from the list, and flags it to be deleted in the back end.
     *
     * @param proxy_id
     */
    function remove_exception(proxy_id) {
        audience_list[proxy_id].excluded = 0;
        audience_list[proxy_id].selected = 0;
        audience_list[proxy_id].use_exception_max_attempts = 0;
        audience_list[proxy_id].use_exception_start_date = 0;
        audience_list[proxy_id].use_exception_end_date = 0;
        audience_list[proxy_id].use_exception_time_factor = 0;
        audience_list[proxy_id].use_exception_submission_date = 0;
        audience_list[proxy_id].exception_start_date = null;
        audience_list[proxy_id].exception_end_date = null;
        audience_list[proxy_id].exception_submission_date = null;
        audience_list[proxy_id].exception_time_factor = 0;
        audience_list[proxy_id].max_attempts = 1;

        draw_audience_table(audience_list);
        user_exception_modal.modal("hide");
    }

    $("body").on("change", "#choose-event-btn", function () {
        var event_id = $("input[name=\"target_id\"]").val();
        populate_audience_table(event_id, "event");
    });

    /**
     * Updates an existing exception.
     *
     */
    function update_exception() {
        // The selected flag is used to let the API know to proccess changes for this record.
        // If selected is set to zero, this record will no be saved, or deleted if the user is editing a post.
        audience_list[current_proxy_id].selected = 1;

        // Excluded:
        audience_list[current_proxy_id].excluded = get_int_value(cb_excluded.is(":checked"));

        // Time factor:
        audience_list[current_proxy_id].use_exception_time_factor = get_int_value(use_exception_time_factor.is(":checked"));
        audience_list[current_proxy_id].exception_time_factor = use_exception_time_factor.is(":checked") ? parseInt(exception_time_factor.val()) : 0;

        // Attempts:
        audience_list[current_proxy_id].use_exception_max_attempts = get_int_value(use_exception_max_attempts.is(":checked"));
        audience_list[current_proxy_id].max_attempts = use_exception_max_attempts.is(":checked") ? parseInt(exception_max_attempts.val()) : parseInt($("#max_attempts").val());

        // Start Date:
        audience_list[current_proxy_id].use_exception_start_date = get_int_value(use_exception_start_date.is(":checked"));
        if (audience_list[current_proxy_id].use_exception_start_date) {
            audience_list[current_proxy_id].exception_start_date = manageDate(exception_start_date.val(), exception_start_time.val());
        } else {
            audience_list[current_proxy_id].exception_start_date = null;
        }

        // End Date:
        audience_list[current_proxy_id].use_exception_end_date = get_int_value(use_exception_end_date.is(":checked"));
        if (audience_list[current_proxy_id].use_exception_end_date) {
            audience_list[current_proxy_id].exception_end_date = manageDate(exception_end_date.val(), exception_end_time.val());
        } else {
            audience_list[current_proxy_id].exception_end_date = null;
        }

        // Submission Deadline:
        audience_list[current_proxy_id].use_exception_submission_date = get_int_value(use_exception_submission_date.is(":checked"));
        if (audience_list[current_proxy_id].use_exception_submission_date) {
            audience_list[current_proxy_id].exception_submission_date = manageDate(exception_submission_date.val(), exception_submission_time.val());
        } else {
            audience_list[current_proxy_id].exception_submission_date = null;
        }

        // If there are no exceptions, mark this row for exclusion.
        if (audience_list[current_proxy_id].excluded == 0 &&
            audience_list[current_proxy_id].use_exception_start_date == 0 &&
            audience_list[current_proxy_id].use_exception_end_date == 0 &&
            audience_list[current_proxy_id].use_exception_submission_date == 0 &&
            audience_list[current_proxy_id].use_exception_time_factor == 0 &&
            audience_list[current_proxy_id].use_exception_max_attempts == 0) {

            remove_exception(current_proxy_id);
        } else {
            draw_audience_table(audience_list);
            user_exception_modal.modal("hide");
        }
    }

    /**
     * Populates the fields of a modal based on the state of a current audience member.
     *
     * @param proxy_id
     */
    function edit_exception (proxy_id) {
        current_proxy_id = proxy_id;

        var audience_member = audience_list[proxy_id];

        ex_student_name.html(audience_member.label);
        clear_exception_btn.prop("disabled", ! get_bool_value(audience_member.selected));

        // For the date and time fields we will check if the use_date param is true, and if so, we will select
        // the checkbox, enable the text inputs and set the defaut value to the user's exception date.
        // If the user doesn't have exception dates set, we will use the exam dates.

        // Start Date:
        use_exception_start_date.prop("checked", get_bool_value(audience_member.use_exception_start_date));
        exception_start_date.prop("disabled", ! get_bool_value(audience_member.use_exception_start_date));
        exception_start_time.prop("disabled", ! get_bool_value(audience_member.use_exception_start_date));

        if (audience_member.exception_start_date != null && audience_member.exception_start_date > 0) {
            var start_date_obj = format_date(new Date(audience_member.exception_start_date * 1000));
            exception_start_date.val(start_date_obj.date_string);
            exception_start_time.val(start_date_obj.time_string);
        } else {
            exception_start_date.val($("#exam_start_date").val());
            exception_start_time.val($("#exam_start_time").val());
        }

        // End Date:
        use_exception_end_date.prop("checked", get_bool_value(audience_member.use_exception_end_date));
        exception_end_date.prop("disabled", ! get_bool_value(audience_member.use_exception_end_date));
        exception_end_time.prop("disabled", ! get_bool_value(audience_member.use_exception_end_date));

        if (audience_member.exception_end_date != null && audience_member.exception_end_date > 0) {
            var start_date_obj = format_date(new Date(audience_member.exception_end_date * 1000));
            exception_end_date.val(start_date_obj.date_string);
            exception_end_time.val(start_date_obj.time_string);
        } else {
            exception_end_date.val($("#exam_end_date").val());
            exception_end_time.val($("#exam_end_time").val());
        }

        // Submission Deadline:
        use_exception_submission_date.prop("checked", get_bool_value(audience_member.use_exception_submission_date));
        exception_submission_date.prop("disabled", ! get_bool_value(audience_member.use_exception_submission_date));
        exception_submission_time.prop("disabled", ! get_bool_value(audience_member.use_exception_submission_date));

        if (audience_member.exception_submission_date != null && audience_member.exception_submission_date > 0) {
            var start_date_obj = format_date(new Date(audience_member.exception_submission_date * 1000));
            exception_submission_date.val(start_date_obj.date_string);
            exception_submission_time.val(start_date_obj.time_string);
        } else {
            exception_submission_date.val($("#exam_submission_date").val());
            exception_submission_time.val($("#exam_submission_time").val());
        }

        // Extra Time:
        use_exception_time_factor.prop("checked", get_bool_value(audience_member.use_exception_time_factor));
        exception_time_factor.prop("disabled", ! get_bool_value(audience_member.use_exception_time_factor));
        exception_time_factor.val(audience_member.exception_time_factor);

        // Attempts:
        use_exception_max_attempts.prop("checked", get_bool_value(audience_member.use_exception_max_attempts));
        exception_max_attempts.prop("disabled", ! get_bool_value(audience_member.use_exception_max_attempts));
        if (audience_member.selected) {
            exception_max_attempts.val(audience_member.max_attempts);
        } else {
            exception_max_attempts.val($("#max_attempts").val());
        }

        // Exclusion:
        cb_excluded.prop("checked", get_bool_value(audience_member.excluded));
        set_excluded_status(get_bool_value(audience_member.excluded));
    }

    /**
     * Converts an int to true/false. Useful for handling data from the API.
     *
     * @param int_val
     * @returns {boolean}
     */
    function get_bool_value(int_val) {
        return int_val == 1 ? true : false;
    }

    /**
     * Converts a bool to int (0 or 1). Useful for sending data to the API.
     *
     * @param bool_val
     * @returns {number}
     */
    function get_int_value(bool_val) {
        return bool_val === true ? 1 : 0;
    }

    /**
     * Enables/disables editing of exceptions based on the value of the "excluded" checkbox.
     *
     * @param status
     */
    function set_excluded_status(status) {
        use_exception_start_date.prop("disabled", status);
        use_exception_end_date.prop("disabled", status);
        use_exception_submission_date.prop("disabled", status);
        use_exception_time_factor.prop("disabled", status);
        use_exception_max_attempts.prop("disabled", status);

        // Enabling...
        if (status == false) {
            // We'll only enable the input boxes that have the checkbox checked.
            if (use_exception_start_date.is(":checked")) {
                exception_start_date.prop("disabled", false);
                exception_start_time.prop("disabled", false);
            }

            if (use_exception_end_date.is(":checked")) {
                exception_end_date.prop("disabled", false);
                exception_end_time.prop("disabled", false);
            }

            if (use_exception_submission_date.is(":checked")) {
                exception_submission_date.prop("disabled", false);
                exception_submission_time.prop("disabled", false);
            }

            if (use_exception_time_factor.is(":checked")) {
                exception_time_factor.prop("disabled", false);
            }

            if (use_exception_max_attempts.is(":checked")) {
                exception_max_attempts.prop("disabled", false);
            }
        } else {
            exception_start_date.prop("disabled", true);
            exception_start_time.prop("disabled", true);

            exception_end_date.prop("disabled", true);
            exception_end_time.prop("disabled", true);

            exception_submission_date.prop("disabled", true);
            exception_submission_time.prop("disabled", true);

            exception_time_factor.prop("disabled", true);

            exception_max_attempts.prop("disabled", true);
        }
    }

    // Editing a post?
    if (post_id != null && post_id > 0) {
        populate_audience_table(post_id, "post");
    } else if (event_id != null && event_id > 0) {
        // Creating a post as an exam resource. The event_id is passed.
        populate_audience_table(event_id, "event");
    }

    /**
     * Calls the API and populates the audience table with the current's audience state.
     *
     * @param id
     * @param type
     */
    function populate_audience_table(id, type) {
        var request_data = "method=get-post-exceptions";

        // Use the right id depending on the type passed.
        if (type == "post") {
            request_data += "&post_id=" + id;
        } else if (type == "event") {
            request_data += "&event_id=" + id;
        }

        // Send the request.
        var get_post_exceptions = jQuery.ajax({
            url: "?section=api-exams",
            data: request_data,
            type: "GET"
        });

        // Process response...
        $.when(get_post_exceptions).done(function (data) {
            var jsonResponse = JSON.parse(data);

            if (jsonResponse.status == "success") {
                audience_list = jsonResponse.data.exam_exceptions;
                draw_audience_table(audience_list);
            } else {
                alert(javascript_translations.recovering_audience_error_message);
            }
        });

    }

    /**
     * (Re)draws the audience table.
     *
     * @param audience_members
     */
    function draw_audience_table(audience_members) {
        var audience_html = "";

        // We need to display the audience members in alphabetical order, but since audience_members
        // is an object, and uses proxy_id as "keys", we cannot sort it based on the label property.
        // The implemented solution was to pass everything to an array, sort it based on
        // the label (last name, first name) and use the sorted array to assemble the table.
        var audience_members_sortable = [];

        // Pass everything to the objects array.
        for (var key in audience_members) {
            audience_members[key].proxy_id = key; // Well add the proxy_id as a property.
            audience_members_sortable.push(audience_members[key]);
        }

        // Sorted based on the "label" (last name, first name).
        audience_members_sortable.sort(function(a, b) {
            return a.label.localeCompare(b.label);
        });

        // Render the ordered array.
        for (var i = 0; i < audience_members_sortable.length; i++) {
            var element = audience_members_sortable[i];
            audience_html += render_audience_row(element.proxy_id, element);
        }

        audience_table_body.empty();
        audience_table_body.append(audience_html);

        // Save JSON string to the hidden input.
        $("#exam_exceptions").val(JSON.stringify(audience_list));
    }

    /**
     * Renders a row for the audience table.
     *
     * @param proxy_id
     * @param data
     * @returns {string}
     */
    function render_audience_row(proxy_id, data) {
        var row = "";
        var classes = data.selected == 1 ? "has-exception" : "";
        row += "<tr id=\"audience-row-" + proxy_id + "\" class=\"" + classes + "\">";
        row += "<td>" + data.label + "</td>";

        // Excluded:
        var excluded_label  = data.excluded == 1 ? "<label class=\"label label-success\">" + javascript_translations.yes + "</label>" : "<label class=\"label\">" + javascript_translations.no + "</label>";
        row += "<td>" + excluded_label + "</td>";

        // Start Date Row:
        if (data.exception_start_date > 0) {
            var start_date_obj = format_date(new Date(data.exception_start_date * 1000));
            var label_start_date = start_date_obj.date_string + " " + start_date_obj.time_string;
        } else {
            var label_start_date = "";
        }
        row += "<td>" + label_start_date + "</td>";

        // End Date Row:
        if (data.exception_end_date > 0) {
            var end_date_obj = format_date(new Date(data.exception_end_date * 1000));
            var label_end_date = end_date_obj.date_string + " " + end_date_obj.time_string;
        } else {
            var label_end_date = "";
        }
        row += "<td>" + label_end_date + "</td>";

        // Submission Date Row:
        if (data.exception_submission_date > 0) {
            var submission_date_obj = format_date(new Date(data.exception_submission_date * 1000));
            var label_submission_date = submission_date_obj.date_string + " " + submission_date_obj.time_string;
        } else {
            var label_submission_date = "";
        }
        row += "<td>" + label_submission_date + "</td>";

        // Time factor:
        var label_time_factor = "";
        if (data.exception_time_factor > 0) {
            label_time_factor = data.exception_time_factor + "%";
        }
        row += "<td>" + label_time_factor + "</td>";

        // Max attempts:
        var label_max_attempts = "";
        if (data.selected) {
            if (data.max_attempts != parseInt($("#max_attempts").val())) {
                label_max_attempts = data.max_attempts + "x";
            }
        }

        row += "<td>" + label_max_attempts + "</td>";

        // Edit btn and closing of the row.
        row += "<td><button class=\"btn btn-default btn-edit-exception\" data-proxy-id=\"" + proxy_id + "\"><span class=\"fa fa-edit\"></span></button></td>";
        row += "</tr>";
        return row;
    }

    $(".add-on").on("click", function () {
        var prev_input = $(this).prev("input");
        if ($(prev_input).prop("disabled") == false) {
            $(prev_input).focus();
        }
    });

    $("#use_time_limit").click(function() {
        var clicked = $(this);
        var auto_submit = $("#auto_submit");
        var time_limit_mins = $("#time_limit_mins");
        var time_limit_hours = $("#time_limit_hours");

        if (clicked.prop("checked") == true ) {
            $(auto_submit).prop("disabled", false);
            $(time_limit_mins).prop("disabled", false);
            $(time_limit_hours).prop("disabled", false);
        } else {
            $(auto_submit).prop("disabled", true);
            $(auto_submit).prop("checked", false);
            $(time_limit_mins).prop("disabled", true);
            $(time_limit_hours).prop("disabled", true);
        }
    });

    use_exception_time_factor.on("click", function() {
        exception_time_factor.prop("disabled", ! $(this).is(":checked"));
    });

    use_exception_max_attempts.on("click", function() {
        exception_max_attempts.prop("disabled", ! $(this).is(":checked"));
    });

    $(".use_date").click(function() {
        var date_id = $(this).data("date-name");
        var time_id = $(this).data("time-name");

        $("#" + date_id).attr("disabled", ! $(this).is(":checked"));
        $("#" + time_id).attr("disabled", ! $(this).is(":checked"));

        // Adds pointer to the calendar icon when hovering the mouse.
        $($("#" + date_id).next(".add-on")).toggleClass("pointer");
    });

    $("#use_re_attempt_threshold").click(function() {
        var clicked = $(this);
        var retake_threshold = $("#re_attempt_threshold");
        var retake_threshold_attempts = $("#re_attempt_threshold_attempts");

        if (clicked.prop("checked") == true ) {
            $(retake_threshold).prop("disabled", false);
            $(retake_threshold_attempts).prop("disabled", false);
            $("#max_attempts").val(1);
        } else {
            $(retake_threshold).prop("disabled", true);
            $(retake_threshold_attempts).prop("disabled", true);
        }
    });

    $("#use_resume_password").on("change", function(e) {
        var clicked = $(this);

        if (clicked.prop("checked") == true ) {
            $("#resume_password_seb").attr("disabled", false);
            $("#seb .generate-resume-password-btn").attr("disabled", false);
        } else {
            $("#resume_password_seb").val("").attr("disabled", true);
            $("#seb .generate-resume-password-btn").attr("disabled", true);
        }
    });

    $(".generate-resume-password-btn").click(function() {
        var resume_password = randomPassword(12);
        $(".resume_password").val(resume_password);

    });

    $(".resume_password").on("change",function() {
        var value = $(this).val();
        $(".resume_password").val(value);
    });

    $("input#secure_true").on("click", function() {
        $("#use_resume_password").prop("checked", false);
        $("#resume_password_seb").prop("disabled", true);
        $("#seb .generate-resume-password-btn").prop("disabled", false);
    });

    $("input:checkbox[id=use_resume_password]").trigger("change");

    function get_secure_keys (target, post_id) {
        var targetOutput = (typeof target !== "undefined") ? target : secure_key_list;
        var post_id = (typeof post_id !== "undefined") ? post_id : parseInt(jQuery("input[name=\"post_id\"]").val());

        return jQuery.ajax({
            url: ENTRADA_URL + "/admin/events?section=api-exams",
            data: "method=get-secure-keys&post_id=" + post_id,
            type: "GET",
            success: function (data) {
                var jsonResponse = JSON.parse(data);

                if (jsonResponse.status === "success") {
                    jQuery(".secure-key-list").html(build_key_list(jsonResponse.data["secure_access_keys"], jQuery(".secure-key-list")));
                    $secure_key_exists = true;
                    var secure_key_count = jsonResponse.data.length;
                    if (secure_key_count > 0) {
                        secure_key_badge.text(jsonResponse.data.length);
                    }

                } else if (jsonResponse.status === "empty") {
                    $secure_key_exists = false;
                    targetOutput.empty();
                    targetOutput.html("<div class=\"alert alert-info\">" + jsonResponse.data + "</div>");
                    if (!jQuery("#delete-secure-keys").hasClass("hide")) {
                        jQuery("#delete-secure-keys").addClass("hide");
                    }

                } else {
                    $("#resources-msgs").append(jsonResponse.data);
                    $secure_key_exists = false;
                }

            },
            beforeSend: function () {
                jQuery("#resources-container-loading").removeClass("hide");
                jQuery("#resources-container").addClass("hide");
            },
            complete: function () {
                jQuery("#resources-container-loading").addClass("hide");
                jQuery("#resources-container").removeClass("hide");
            }
        });
    }

    function get_secure_file (target, post_id) {
        var targetOutput = (typeof target !== "undefined") ? target : secure_file_list;
        var post_id = (typeof post_id !== "undefined") ? post_id : parseInt(jQuery("input[name=\"post_id\"]").val());

        return jQuery.ajax({
            url: ENTRADA_URL + "/admin/events?section=api-exams",
            data: "method=get-secure-file&post_id=" + post_id,
            type: "GET",
            success: function (data) {
                var jsonResponse = JSON.parse(data);

                if (jsonResponse.status === "success") {
                    targetOutput.html(build_file_list(jsonResponse.data["secure_access_file"]));

                } else if (jsonResponse.status === "empty") {
                    var resource_list_html = "<div class=\"alert alert-info text-center\" id=\"secure-access-file-empty\">";
                        resource_list_html += "<i class=\"fa fa-file-o fa-4x\"></i>";
                        resource_list_html += "<h3>Please upload the SEB file for this exam post by dropping the file in this box or use the Browse button below</h3>";
                        resource_list_html += "<p>Please use the following for the <strong>Start URL</strong> when creating the SEB file in Safe Exam Browser:<br />";
                        resource_list_html += "<strong>" + ENTRADA_URL + "/secure/exams?section=attempt&id=" + post_id + "</strong>";
                        resource_list_html += "</p>";
                        resource_list_html += "<span class=\"btn btn-default btn-file\">";
                        resource_list_html += "Browse <input name=\"secure-file\" type=\"file\"></span>";
                        resource_list_html += "</div>";
                    targetOutput.empty().html(resource_list_html);

                    if (!jQuery("#delete-secure-file").hasClass("hide")) {
                        jQuery("#delete-secure-file").addClass("hide");
                    }

                } else {
                    $("#resources-msgs").append(jsonResponse.data);
                }


            },
            beforeSend: function () {
                jQuery("#resources-container-loading").removeClass("hide");
                jQuery("#resources-container").addClass("hide");
            },
            complete: function () {
                jQuery("#resources-container-loading").addClass("hide");
                jQuery("#resources-container").removeClass("hide");
            }
        });
    }

    /**
     * WIZARD CONTROLS
     */
    enable_wizard_controls();

    // Shows the right wizard step if the post is being edited and there is a redirect.
    if (post_id.length > 0 && is_redirect) {
        update_step(1);
        wizard_goto_step(redirect_section);
    } else {
        show_step(1);
    }

    /**
     * Advances to the next step of the wizard.
     */
    $(".wizard-next-step").on("click", function (e) {
        e.preventDefault();
        var next_step = get_step() + 1;
        wizard_goto_step(next_step);
    });

    /**
     * Goes back to the previous step.
     */
    $(".wizard-previous-step").on("click", function (e) {
        e.preventDefault();
        var step = get_step();
        // Step should never be smaller than one.
        var previous_step = step <= 1 ? 1 : step - 1;
        wizard_goto_step(previous_step);
    });

    /**
     * Goes straight to a completed step of the wizard.
     */
    $(".wizard-nav-item").on("click", function (e) {
        e.preventDefault();
        var wizard_nav_item = $(this);

        // The user can only move between steps that have been completed.
        if (wizard_nav_item.hasClass("complete")) {
            var next_step = wizard_nav_item.data("step");
            wizard_goto_step(next_step);
        }
    });

    /**
     * Goes to a specified wizard step, validates and saves data using the API.
     *
     * @param next_step
     */
    function wizard_goto_step (next_step) {
        for (instance in CKEDITOR.instances) {
            CKEDITOR.instances[instance].updateElement();
        }

        step = get_step();
        remove_errors();

        if (next_step <= step) {
            // Going back to a previous step.
            show_step(next_step);
        } else {
            // Advancing to one of the following steps.

            // Serialize form data.
            var step_data = "method=exam-wizard&" + jQuery("#search-targets-form").serialize() + "&step=" + step;
            // Appends the next step to the serialized data.
            step_data += (typeof next_step !== "undefined" ? "&next_step=" + next_step : "");

            // Sends the ajax request for validation/saving depending on the backend rules.
            var wizard_step_request = jQuery.ajax({
                url: ENTRADA_URL + "/admin/events?section=api-exams",
                data: step_data,
                type: "POST",
                beforeSend: function () {
                    show_loading_msg();
                }
            });

            // This promise is executed when the request is completed.
            jQuery.when(wizard_step_request).done(function (data) {
                hide_loading_msg();
                var jsonResponse = JSON.parse(data);

                if (jsonResponse.status === "success") {
                    post_id  = jsonResponse.data.post_id > 0 ? jsonResponse.data.post_id : null;

                    jQuery("li[data-step=\""+ step +"\"]").addClass("complete");
                    var next_step = parseInt(jsonResponse.data.step);
                    if(next_step == 5 || next_step == 6) {
                        // Creates a table with post info for review.
                        jQuery("#review-exam-details").empty();
                        var $exam_detail = "<table class=\"table table-striped table-bordered\">";
                        jQuery.each(jsonResponse.data.post, function(i, v) {
                            var display = v.display;
                            $exam_detail += "<tr>";
                            $exam_detail += "<td>" + v.label + "</td>";
                            $exam_detail += "<td>" + display + "</td>";
                            $exam_detail += "</tr>";
                        });
                        $exam_detail += "</table>";
                        jQuery("#review-exam-details").append($exam_detail);

                    }

                    // Draws the audience table again.
                    if (next_step == 3) {
                        draw_audience_table(audience_list);
                    }

                    // Default redirect URL.
                    if (referrer == "event") {
                        var redirect_url = ENTRADA_URL + "/admin/events?section=content&id=" + event_id;
                    } else {
                        var redirect_url = ENTRADA_URL + "/admin/exams/exams?section=post&id=" + exam_id;
                    }

                    // Display the correct success message and redirect if needed.
                    if (next_step == 6 && ! is_editing && security_options) {
                        // Creating a post with security options. Redirect immediately to the next step.
                        disable_wizard_controls();
                        redirect_url = ENTRADA_URL + "/admin/exams/exams?section=form-post&id=" + exam_id + "&post_id=" + post_id + "&target_type=event&redirect_section=6";
                        if (referrer == "event") {
                            redirect_url += "&target_id=" + event_id;
                        }
                        window.location.replace(redirect_url);
                    } else if (next_step == 6 && ! is_editing && ! security_options) {
                        // Creating a post without security options.
                        var msg = javascript_translations.exam_post_created_message;
                        next_step = false;
                    } else if (next_step == 6 && is_editing && security_options) {
                        // Editing a post with security options.
                        // Just go to the next step.
                    } else if (next_step == 6 && is_editing && ! security_options) {
                        // Editing a post without security options.
                        var msg = javascript_translations.exam_post_saved_message;
                        next_step = false;
                    } else if (next_step == 7 && is_editing && security_options) {
                        // Saving/updating security options.
                        var msg = javascript_translations.exam_post_saved_message;
                        next_step = false;
                    }

                    // All steps completed, display success message and redirect in 5 secs.
                    if (next_step === false) {
                        disable_wizard_controls();
                        msg += " You will now be redirected in 5 seconds or <a href=\""+ redirect_url + "\"><strong>click here</strong></a> to continue.";
                        display_success([msg], "#msgs");

                        setTimeout(function () {
                            window.location.replace(redirect_url);
                        }, 5000);
                    }

                    if (security_options && next_step == 6) {
                        get_secure_file(jQuery(".secure-file-list-content"), post_id);
                        get_secure_keys(jQuery(".secure-key-list-content"), post_id);
                    }

                    if (security_options && (next_step == 7 || next_step == false)) {
                        get_secure_keys(jQuery(".secure-key-list"), post_id);
                    }

                    mark_step_as_completed(step);
                    show_step(next_step);
                } else {
                    enable_wizard_controls();
                    display_error(jsonResponse.data, "#msgs", "prepend");
                }
            });
        }

    }

    /**
     * Remove error messages from the page.
     */
    function remove_errors () {
        jQuery("#msgs").empty();
    }

    /**
     * Mark a step as completed in the wizard navigation tabs.
     * @param step
     */
    function mark_step_as_completed(step) {
        jQuery("li[data-step=\""+ step +"\"]").addClass("complete");
    }

    /**
     * Shows the loading message.
     */
    function show_loading_msg () {
        disable_wizard_controls();
        jQuery("#wizard-loading-msg").html("Loading Exam Options...");
        jQuery("#search-targets-form").addClass("hide");
        jQuery("#wizard-loading").removeClass("hide");
    }

    /**
     * Hides the loading message.
     */
    function hide_loading_msg () {
        enable_wizard_controls();
        jQuery("#wizard-loading").addClass("hide");
        jQuery("#wizard-loading-msg").html("");
        jQuery("#search-targets-form").removeClass("hide");
    }

    /**
     * Enables the wizzard control buttons (prev/next).
     */
    function enable_wizard_controls () {
        if (jQuery(".wizard-next-step").is(":disabled")) {
            jQuery(".wizard-next-step").removeAttr("disabled");
        }

        if (jQuery(".wizard-previous-step").is(":disabled")) {
            jQuery(".wizard-previous-step").removeAttr("disabled");
        }
    }

    /**
     * Disables the wizzard control buttons (prev/next).
     */
    function disable_wizard_controls () {
        if (!jQuery(".wizard-next-step").is(":disabled")) {
            jQuery(".wizard-next-step").attr("disabled", "disabled");
        }

        if (!jQuery(".wizard-previous-step").is(":disabled")) {
            jQuery(".wizard-previous-step").attr("disabled", "disabled");
        }
    }

    /**
     * Updates the next step message.
     * @param message
     */
    function set_next_step_message(message) {
        jQuery(".wizard-next-step").html(message);
    }

    /**
     * Shows the next navigation step.
     * If false is passed, will hide all steps and show a blank screen that can be used to display messages.
     *
     * @param next_step
     */
    function show_step (next_step) {

        if (next_step != false) {
            update_step(next_step);

            // Toggles the active navigation item for a specified step.
            jQuery(".wizard-nav-item").removeClass("active");
            jQuery("#wizard-nav-item-" + next_step).addClass("active");

            // Show or hide the "previous" button.
            if (next_step <= 1) {
                jQuery("button.wizard-previous-step").hide();
            } else {
                jQuery("button.wizard-previous-step").show();
            }

            // Show appropriate "next" message according to the user page.
            if (next_step == 5 && security_options) {
                set_next_step_message("Save Post and Continue");
            } else if (next_step == 5 && ! security_options) {
                set_next_step_message("Save Post")
            } else if (next_step == 6 && security_options) {
                set_next_step_message("Save Security Options");
            } else {
                set_next_step_message("Next");
            }

            jQuery(".wizard-step").addClass("hide");
            jQuery("#wizard-step-" + next_step).removeClass("hide");
        } else {
            jQuery(".wizard-step").addClass("hide");
            jQuery(".wizard-nav-item").removeClass("complete");
        }

    }

    /**
     * Updates the current step number.
     * @param {number} step
     */
    function update_step (step) {
        jQuery("#wizard-step-input").val(step);
    }

    /**
     * Returns the current step number.
     * @returns {number}
     */
    function get_step() {
        return parseInt(jQuery("#wizard-step-input").val());
    }

    function remove_filter (filter_type, filter_target) {
        var remove_filter_request = jQuery.ajax({
            url: ENTRADA_URL + "/admin/events?section=api-exams",
            data: "method=remove-filter&filter_type=" + filter_type + "&filter_target=" + filter_target,
            type: "POST"
        });

        jQuery.when(remove_filter_request).done(function (data) {
            var jsonResponse = JSON.parse(data);
            if (jsonResponse.status === "success") {
                var id_string = "#" + filter_type + "_" + filter_target
                jQuery(id_string).remove();
                jQuery("#search-targets-form").submit();
            }
        });
    }

    function remove_all_filters () {
        var remove_filter_request = jQuery.ajax({
            url: ENTRADA_URL + "/admin/events?section=api-exams",
            data: "method=remove-all-filters",
            type: "POST"
        });

        jQuery.when(remove_filter_request).done(function (data) {
            var jsonResponse = JSON.parse(data);
            if (jsonResponse.status === "success") {
                jQuery("#search-targets-form").empty().submit();
            }
        });
    }

    function getCheckedExamsPosts(e) {
        e.preventDefault();

        jQuery("#exam-post-delete-dialog #start_delete_exam").show();
        jQuery("#exam-post-delete-dialog #post-titles").html("");
        var inputs = jQuery(".delete_exam_post:checkbox:checked");
        var post_ids = [];
        jQuery.each(inputs, function(key, value) {
            var post_id = jQuery(value).data("post-id");
            var post_title = "<li>" + jQuery(value).data("post-title") + "</li>";
            post_ids.push(post_id);
            jQuery("#exam-post-delete-dialog #post-titles").append(post_title);
        });

        exam_post_ids = post_ids;
        jQuery("#exam-post-delete-dialog").modal("show");
    }

    function deleteSelectedExams(e) {
        var dataObject = {
            method : "delete-exam-posts",
            post_ids: exam_post_ids
        };

        jQuery.ajax({
            url: API_URL,
            data: dataObject,
            type: "POST",
            success: function (data) {
                var jsonResponse = JSON.parse(data);
                jQuery("#exam-post-delete-dialog #start_delete_exam").hide();
                var message = jsonResponse.msg;
                if (jsonResponse.status == "success") {
                    jQuery("#exam-post-delete-dialog #delete_exam_message").addClass("alert-success").html(message);
                    setTimeout(function() {
                        location.reload();
                    }, 5000);
                } else if (jsonResponse.status == "warning") {
                    jQuery("#exam-post-delete-dialog #delete_exam_message").addClass("alert-warning").html(message);
                } else if (jsonResponse.status == "error") {
                    jQuery("#exam-post-delete-dialog #delete_exam_message").addClass("alert-error").html(message);
                }

                jQuery("#exam-post-delete-dialog #delete_exam_message").show();
            }
        });
}});
