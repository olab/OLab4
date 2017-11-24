var dates_times = {};
var exam_post_ids = [];
var exceptions = {};
var current_user;
var event_id;
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

    dates_times["initial_exam_start_date"]      = $("#exam_start_date").val();
    dates_times["initial_exam_start_time"]      = $("#exam_start_time").val();
    dates_times["initial_exam_end_date"]        = $("#exam_end_date").val();
    dates_times["initial_exam_end_time"]        = $("#exam_end_time").val();
    dates_times["initial_exam_submission_date"] = $("#exam_submission_date").val();
    dates_times["initial_exam_submission_time"] = $("#exam_submission_time").val();

    dates_times["initial_release_start_date"]     = $("#release_start_date").val();
    dates_times["initial_release_start_time"]     = $("#release_start_time").val();
    dates_times["initial_release_end_date"]       = $("#release_end_date").val();
    dates_times["initial_release_end_time"]       = $("#release_end_time").val();
    dates_times["initial_exam_start_date"]      = $("#exam_start_date").val();
    dates_times["initial_exam_start_time"]      = $("#exam_start_time").val();
    dates_times["initial_exam_end_date"]        = $("#exam_end_date").val();
    dates_times["initial_exam_end_time"]        = $("#exam_end_time").val();

    var redirect_section = $("#wizard-step-input").val();

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

    var exception_data_table = $("#exceptions-table table").DataTable();

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
        var secure_false_value = $(this).is(":checked") ;
        if (secure_false_value == true) {
            $("#wizard-nav-item-3").removeClass("complete");
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
            if (id_others == id_select){
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
        date_collection.date_string     = date_object.getFullYear() + "-" + (date_object.getMonth() + 1) + "-" + date_object.getDate();
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

    $("#wizard-step-3").on("click", ".delete-item", function(event) {
        var $section = $(this).parents(".item-section");
        deleteButtonState($section);
    });

    /**
     * Delete Secure Access Files on click
     */
    $("#wizard-step-3").on("click", "#delete-secure-file", function(event) {
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
    $("#wizard-step-3").on("click", "#delete-secure-keys", function(event){
        event.preventDefault();
        event.stopPropagation();
        /**
         * Delete Secure Keys
         * @type {*|jQuery}
         */
        var $delete_keys = $(".secure-key-list").find("input[name=\"delete[]\"]:checked").filter(function(){
            return this.value;
        });
        var $local_keys = $(".secure-key-list").find("input[name=\"delete[]\"]:checked").filter(function(){
            return !this.value;
        });
        if ($delete_keys.length){
            $.each($delete_keys, function(){
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

        if (!$(".secure-key-list").find("tbody > tr").length){
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
                        if ($("#secure-file-upload-drop")){
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

    /*
    Exceptions code
     */

    // Exception Event Listeners

    $("#wizard-step-4").on("click", ".search-target-input-control[data-filter=\"exception_audience\"]", function() {
        var selected    = 0;
        var label       = jQuery(this).data("label");
        var proxy_id    = jQuery(this).val();
        var search_filter_item = jQuery(this).closest(".search-filter-item");

        setTimeout(function() {
            if (search_filter_item.hasClass("search-target-selected")) {
                selected = 1;
            }
            manageExceptionAudience(proxy_id, selected, label);
        }, 100);
    });

    $("#wizard-step-4").on("click", ".remove-target-toggle[data-filter=\"exception_audience\"]", function() {
        var selected    = 0;
        var proxy_id    = jQuery(this).data("id");

        manageExceptionAudience(proxy_id, selected, "");
    });

    $("#wizard-step-4").on("click", ".edit-exception-data", function(e) {
        e.preventDefault();
        load_exception_user_data($(this));
    });

    $("#wizard-step-4").on("click", ".delete-exception-data", function(e) {
        e.preventDefault();
        var selected    = 0;
        var proxy_id = $(this).data("proxy_id");
        manageExceptionAudience(proxy_id, selected, "");
    });

    $("#cancel-dropdown-exception").on("click", function(e) {
        e.preventDefault();
        var edit_menu = $("#edit-user-exception");

        edit_menu.modal("hide");
    });

    $("#update-dropdown-exception").on("click", function(e) {
        e.preventDefault();

        var proxy_id = current_user;
        update_exception_user_data();
        manageExceptionAudience(proxy_id, 1, "");

        var edit_menu = $("#edit-user-exception");

        edit_menu.modal("hide");
    });

    // Exception Functions

    function manageExceptionAudience(proxy_id, selected, label) {
        var edit_btn            = "<button class=\"edit-exception-data btn btn-default\" data-proxy_id=\"" + proxy_id + "\" data-label=\"" + label + "\"><i class=\"fa fa-gear\"></i></button>";
        var delete_btn          = "<button class=\"delete-exception-data btn btn-default\" data-proxy_id=\"" + proxy_id + "\" data-label=\"" + label + "\"><i class=\"fa fa-remove\"></i></button>";
        var btns                = "<div class=\"btn-group\">" + edit_btn + delete_btn + "</div>";
        var exception_user      = {};
        var exam_start_date     = null;
        var exam_end_date       = null;
        var exam_sub_date       = null;
        var exception_time_factor = 0;
        var display_max_attempts = "";
        var display_start       = "";
        var display_end         = "";
        var display_sub         = "";

        if (!exceptions[proxy_id]) {
            var start_date  = $("#exam_start_date").val();
            var start_time  = $("#exam_start_time").val();
            var end_date    = $("#exam_end_date").val();
            var end_time    = $("#exam_end_time").val();
            var sub_date    = $("#exam_submission_date").val();
            var sub_time    = $("#exam_submission_time").val();

            var use_start_date_checked = 0;
            var use_end_date_checked = 0;
            var use_sub_date_checked = 0;
            var use_exception_time_factor_checked = 0;
            var use_exception_max_attempts = 0;

            if (start_date && start_time) {
                exam_start_date = manageDate(start_date, start_time);
            }
            if (end_date && end_time) {
                exam_end_date = manageDate(end_date, end_time);
            }
            if (sub_date && sub_time) {
                exam_sub_date = manageDate(sub_date, sub_time);
            }

            exception_user = {
                "selected":                     selected,
                "excluded":                     0,
                "use_exception_max_attempts":   use_exception_max_attempts,
                "max_attempts":                 null,
                "exception_start_date":         exam_start_date,
                "exception_end_date":           exam_end_date,
                "exception_submission_date":    exam_sub_date,
                "use_exception_start_date":     use_start_date_checked,
                "use_exception_end_date":       use_end_date_checked,
                "use_exception_submission_date": use_sub_date_checked,
                "use_exception_time_factor":    use_exception_time_factor_checked,
                "exception_time_factor":        null,
                "label":                        label
            };

            exceptions[proxy_id] = exception_user;

            var rowNode = exception_data_table.row.add([
                label,
                exception_user.excluded,
                null,
                null,
                null,
                null,
                null,
                btns
            ]).draw().node();
            $( rowNode ).attr("id", "exception_row_" + proxy_id);
        } else {
            exceptions[proxy_id].selected = selected;

            if (selected == 0) {
                var row = exception_data_table.row( "#exception_row_" + proxy_id ).remove().draw();
                var hidden_input = $("#exception_audience_" + proxy_id);
                $(hidden_input).remove();
            } else if (selected == 1) {
                exception_user = exceptions[proxy_id];
                exception_data_table.row( "#exception_row_" + proxy_id ).remove();

                var exam_start_date             = exception_user.exception_start_date;
                var exam_end_date               = exception_user.exception_end_date;
                var exam_submission_date        = exception_user.exception_submission_date;
                var exception_time_factor       = exception_user.exception_time_factor;
                var use_exception_time_factor   = exception_user.use_exception_time_factor;
                var use_exception_max_attempts  = exception_user.use_exception_max_attempts;
                var max_attempts                = exception_user.max_attempts;

                var use_exception_start_date            = exception_user.use_exception_start_date;
                var use_exception_end_date              = exception_user.use_exception_end_date;
                var use_exception_submission_date       = exception_user.use_exception_submission_date;
                var use_exception_time_factor_checked   = exception_user.use_exception_time_factor;

                if (typeof use_exception_start_date !== "undefined" && use_exception_start_date == 1) {
                    if (typeof exam_start_date !== "undefined" && exam_start_date) {
                        var exception_start_date = format_date(new Date(exception_user.exception_start_date * 1000));
                        display_start = exception_start_date.date_string_us + " " + exception_start_date.time_string;
                    }
                }

                if (typeof use_exception_end_date !== "undefined" && use_exception_end_date == 1) {
                    if (typeof exam_end_date !== "undefined" && exam_end_date) {
                        var exception_end_date      = format_date(new Date(exception_user.exception_end_date * 1000));
                        display_end = exception_end_date.date_string_us + " " + exception_end_date.time_string;
                    }
                }

                if (typeof use_exception_submission_date !== "undefined" && use_exception_submission_date == 1) {
                    if (typeof exam_submission_date !== "undefined" && exam_submission_date) {
                        var exception_submission_date = format_date(new Date(exception_user.exception_submission_date * 1000));
                        display_sub = exception_submission_date.date_string_us + " " + exception_submission_date.time_string;
                    }
                }

                if (typeof use_exception_max_attempts !== "undefined" && use_exception_max_attempts == 1) {
                    if (typeof max_attempts !== "undefined" && max_attempts !== null) {
                        display_max_attempts = max_attempts;
                    } else {
                        display_max_attempts = "";
                    }
                }

                if (typeof exception_time_factor == "undefined") {
                    exception_time_factor = "";
                }

                var add_array = [exception_user.label,
                    exception_user.excluded,
                    display_max_attempts,
                    display_start,
                    display_end,
                    display_sub,
                    exception_time_factor,
                    btns];

                var rowNode = exception_data_table.row.add(add_array).draw().node();

                $( rowNode ).attr("id", "exception_row_" + proxy_id);
            }
        }
        var exam_exceptions_serialized = JSON.stringify(exceptions);
        $("#exam_exceptions").val(exam_exceptions_serialized);
    }

    function load_exception_user_data(element_clicked) {
        var edit_menu = $("#edit-user-exception");
        var proxy_id = element_clicked.data("proxy_id");
        current_user = proxy_id;

        var use_exception_max_attempts      = exceptions[proxy_id].use_exception_max_attempts;
        var use_exception_start_date        = exceptions[proxy_id].use_exception_start_date;
        var use_exception_end_date          = exceptions[proxy_id].use_exception_end_date;
        var use_exception_submission_date   = exceptions[proxy_id].use_exception_submission_date;
        var use_exception_time_factor       = exceptions[proxy_id].use_exception_time_factor;
        var exception_time_factor           = exceptions[proxy_id].exception_time_factor;
        var start_date                      = exceptions[proxy_id].exception_start_date;
        var end_date                        = exceptions[proxy_id].exception_end_date;
        var sub_date                        = exceptions[proxy_id].exception_submission_date;

        $("#edit-user-exception h3").text(exceptions[proxy_id].label);

        if (typeof use_exception_max_attempts != "undefined") {
            if (use_exception_max_attempts == 1) {
                $("#edit-user-exception input#use_exception_max_attempts").prop("checked", true);
                if (typeof max_attempts != "undefined" && max_attempts ) {
                    $("#edit-user-exception input#max_attempts").val(exceptions[proxy_id].max_attempts);
                } else {
                    $("#edit-user-exception input#max_attempts").val("");
                }
            } else {
                $("#edit-user-exception input#use_exception_max_attempts").prop("checked", false);
                $("#edit-user-exception input#max_attempts").val("");
            }
        } else {
            $("#edit-user-exception input#use_exception_max_attempts").prop("checked", false);
            $("#edit-user-exception input#max_attempts").val("");
        }

        if (typeof use_exception_start_date != "undefined") {
            if (typeof start_date != "undefined" && start_date > 0) {
                var exception_start_date = format_date(new Date(start_date * 1000));
                $("#exception_start_date").prop("disabled", false).datepicker( "setDate", exception_start_date.date_string ).css("z-index", 9999);
                $("#exception_start_time").prop("disabled", false).val(exception_start_date.time_string);
                dates_times.exception_start_date = jQuery("#exception_start_date").val();
                dates_times.exception_start_time = jQuery("#exception_start_time").val();
            } else {
                $("#exception_start_date").val("");
                $("#exception_start_time").val("");
            }

            if (use_exception_start_date == 1) {
                $("#edit-user-exception input#use_exception_start_date").prop("checked", true);
            } else {
                $("#edit-user-exception input#use_exception_start_date").prop("checked", false);
                $("#edit-user-exception input#exception_start_date").prop("disabled", true).val("");
                $("#edit-user-exception input#exception_start_time").prop("disabled", true).val("");
            }
        } else {
            $("#exception_start_date").val("");
            $("#exception_start_time").val("");
        }

        if (typeof use_exception_end_date != "undefined") {
            if (typeof end_date != "undefined" && end_date > 0) {
                var exception_end_date = format_date(new Date(end_date * 1000));
                $("#exception_end_date").prop("disabled", false).datepicker( "setDate", exception_end_date.date_string );
                $("#exception_end_time").prop("disabled", false).val(exception_end_date.time_string);
                dates_times.exception_end_date = jQuery("#exception_end_date").val();
                dates_times.exception_end_time = jQuery("#exception_end_time").val();
            } else {
                $("#exception_end_date").val("");
                $("#exception_end_time").val("");
            }

            if (use_exception_end_date == 1) {
                $("#edit-user-exception input#use_exception_end_date").prop("checked", true);
            } else {
                $("#edit-user-exception input#use_exception_end_date").prop("checked", false);
                $("#edit-user-exception input#exception_end_date").prop("disabled", true).val("");
                $("#edit-user-exception input#exception_end_time").prop("disabled", true).val("");
            }
        } else {
            $("#exception_end_date").val("");
            $("#exception_end_time").val("");
        }

        if (typeof use_exception_submission_date != "undefined") {
            if (typeof sub_date != "undefined" && sub_date > 0) {
                var exception_submission_date = format_date(new Date(sub_date * 1000));
                $("#exception_submission_date").prop("disabled", false).datepicker( "setDate", exception_submission_date.date_string );
                $("#exception_submission_time").prop("disabled", false).val(exception_submission_date.time_string);
                dates_times.exception_submission_date = jQuery("#exception_submission_date").val();
                dates_times.exception_submission_time = jQuery("#exception_submission_time").val();
            } else {
                $("#exception_submission_date").val("");
                $("#exception_submission_time").val("");
            }

            if (use_exception_submission_date == 1) {
                $("#edit-user-exception input#use_exception_submission_date").prop("checked", true);
            } else {
                $("#edit-user-exception input#use_exception_submission_date").prop("checked", false);
                $("#edit-user-exception input#exception_submission_date").prop("disabled", true).val("");
                $("#edit-user-exception input#exception_submission_time").prop("disabled", true).val("");
            }
        } else {
            $("#exception_submission_date").val("");
            $("#exception_submission_time").val("");
        }

        if (exceptions[proxy_id].excluded == 1) {
            $("#edit-user-exception input#excluded").prop("checked", "checked");
        }

        if (typeof use_exception_time_factor != "undefined" && use_exception_time_factor == 1) {
            $("#edit-user-exception input#use_exception_time_factor").prop("checked", "checked");
            $("#edit-user-exception input#exception_time_factor").val(exception_time_factor).prop("disabled", false);
        } else {
            $("#edit-user-exception input#exception_time_factor").val("").prop("disabled", true);
        }

        edit_menu.modal("show");
    }

    function update_exception_user_data() {
        var proxy_id                    = current_user;
        var exception_start_date        = $("#edit-user-exception input#exception_start_date");
        var exception_start_time        = $("#edit-user-exception input#exception_start_time");
        var exception_end_date          = $("#edit-user-exception input#exception_end_date");
        var exception_end_time          = $("#edit-user-exception input#exception_end_time");
        var exception_submission_date   = $("#edit-user-exception input#exception_submission_date");
        var exception_submission_time   = $("#edit-user-exception input#exception_submission_time");
        var exception_time_factor       = "";
        var max_attempts                = $("#edit-user-exception input#max_attempts").val();

        var use_exception_max_attempt_checked = $("#edit-user-exception input#use_exception_max_attempts").prop("checked");
        var use_exception_max_attempts = 0;
        if (use_exception_max_attempt_checked) {
            use_exception_max_attempts = 1;
        }

        var use_exception_start_date_checked = $("#edit-user-exception input#use_exception_start_date").prop("checked");
        var use_exception_start_date = 0;
        if (use_exception_start_date_checked) {
            use_exception_start_date = 1;
        }

        var use_exception_end_date_checked = $("#edit-user-exception input#use_exception_end_date").prop("checked");
        var use_exception_end_date = 0;
        if (use_exception_end_date_checked) {
            use_exception_end_date = 1;
        }

        var use_exception_submission_date_checked = $("#edit-user-exception input#use_exception_submission_date").prop("checked");
        var use_exception_submission_date = 0;
        if (use_exception_submission_date_checked) {
            use_exception_submission_date = 1;
        }

        var use_exception_time_factor_checked = $("#edit-user-exception input#use_exception_time_factor").prop("checked");
        var use_exception_time_factor = 0;
        if (use_exception_time_factor_checked) {
            use_exception_time_factor = 1;
            exception_time_factor                 = $("#edit-user-exception input#exception_time_factor").val();
        }

        var exception_start_unix        = manageDate(exception_start_date.val(), exception_start_time.val());
        var exception_end_unix          = manageDate(exception_end_date.val(), exception_end_time.val());
        var exception_submission_unix   = manageDate(exception_submission_date.val(), exception_submission_time.val());

        var excluded = $("#edit-user-exception input#excluded").prop("checked");
        var excluded_value = 0;
        if (excluded) {
            excluded_value = 1;
        }

        exceptions[proxy_id].excluded                       = excluded_value;
        exceptions[proxy_id].use_exception_max_attempts     = use_exception_max_attempts;
        exceptions[proxy_id].max_attempts                   = max_attempts;
        exceptions[proxy_id].exception_start_date           = exception_start_unix;
        exceptions[proxy_id].exception_end_date             = exception_end_unix;
        exceptions[proxy_id].exception_submission_date      = exception_submission_unix;
        exceptions[proxy_id].use_exception_start_date       = use_exception_start_date;
        exceptions[proxy_id].use_exception_end_date         = use_exception_end_date;
        exceptions[proxy_id].use_exception_submission_date  = use_exception_submission_date;
        exceptions[proxy_id].use_exception_time_factor      = use_exception_time_factor;
        exceptions[proxy_id].exception_time_factor          = exception_time_factor;
        exceptions[proxy_id].delete                         = 0;

        var exam_exceptions_serialized = JSON.stringify(exceptions);
        $("#exam_exceptions").val(exam_exceptions_serialized);
    }

    //function hides edit menu for exceptions when clicking outside it
    $(document).mouseup(function (e) {
        var edit_menu = $("#edit-user-exception");
        var time_picker = $("#ui-timepicker-div");
        var date_picker = $("#ui-datepicker-div");

        if (!edit_menu.is(e.target) && edit_menu.has(e.target).length === 0) {
            if (!time_picker.is(e.target) && time_picker.has(e.target).length === 0) {
                if (!date_picker.is(e.target) && date_picker.has(e.target).length === 0) {
                    edit_menu.modal("hide");
                }
            }
        }
    });

    /*
    end exceptions
     */

    var post_id = $("input#post_id").val();
    if (post_id.length > 0) {
        var get_post_exceptions = jQuery.ajax({
            url: "?section=api-exams",
            data: "method=get-post-exceptions&post_id=" + post_id,
            type: "GET"
        });

        $.when(get_post_exceptions).done(function (data) {
            var jsonResponse = JSON.parse(data);

            if (jsonResponse.status == "success") {
                /*
                 Start Exception loading section
                 */

                //removes any inputs selected previously for other posts
                $(".exception_audience_search_target_control").remove();

                if (jsonResponse.data.exam_exceptions) {
                    var exam_exceptions = jsonResponse.data.exam_exceptions;
                    $.each(exam_exceptions, function (proxy_id, data) {
                        //build data entry for each user
                        if (!exceptions[proxy_id]) {
                            var exception_user = {
                                "selected":                         1,
                                "excluded":                         data.excluded,
                                "use_exception_max_attempts":       data.use_exception_max_attempts,
                                "max_attempts":                     data.max_attempts,
                                "exception_start_date":             data.exception_start_date,
                                "exception_end_date":               data.exception_end_date,
                                "exception_submission_date":        data.exception_submission_date,
                                "use_exception_start_date":         data.use_exception_start_date,
                                "use_exception_end_date":           data.use_exception_end_date,
                                "use_exception_submission_date":    data.use_exception_submission_date,
                                "use_exception_time_factor":        data.use_exception_time_factor,
                                "exception_time_factor":            data.exception_time_factor,
                                "label":                            data.label
                            };
                            exceptions[proxy_id] = exception_user;
                        } else {
                            exceptions[proxy_id].excluded                       = data.excluded;
                            exceptions[proxy_id].use_exception_max_attempts     = data.use_exception_max_attempts;
                            exceptions[proxy_id].max_attempts                   = data.max_attempts;
                            exceptions[proxy_id].exception_start_date           = data.exception_start_date;
                            exceptions[proxy_id].exception_end_date             = data.exception_end_date;
                            exceptions[proxy_id].exception_submission_date      = data.exception_submission_date;
                            exceptions[proxy_id].use_exception_start_date       = data.use_exception_start_date;
                            exceptions[proxy_id].use_exception_end_date         = data.use_exception_end_date;
                            exceptions[proxy_id].use_exception_submission_date  = data.use_exception_submission_date;
                            exceptions[proxy_id].use_exception_time_factor      = data.use_exception_time_factor;
                            exceptions[proxy_id].exception_time_factor          = data.exception_time_factor;
                            exceptions[proxy_id].label                          = data.label;
                        }

                        manageExceptionAudience(proxy_id, 1, data.label);

                         $(document.createElement("input")).attr({
                             "type"  : "hidden",
                             "class" : "search-target-control exception_audience_search_target_control form-selector",
                             "name"  : "exception_audience[]",
                             "id"    : "exception_audience_" + proxy_id,
                             "value" : proxy_id,
                             "data-label" : data.label
                        }).appendTo("#search-targets-form");
                    });
                } else {
                    //clear list from datatable and object
                    $.each(exceptions, function (proxy_id, data) {
                        var row = exception_data_table.row("#exception_row_" + proxy_id).remove().draw();
                    });
                    exceptions = {};
                }

                /*
                 End Exception loading section
                 */
            }
        });
    }

    function navItemClick(element_clicked) {
        if ($(element_clicked).hasClass("complete")) {
            var next_step = $(element_clicked).data("step");
            var current_step = $("#wizard-step-input").val();

            var post_id = jQuery("#post_id").val();
            if (typeof post_id !== "undefined" || post_id > 0) {
                if (next_step == 1) {
                    show_step(next_step);
                    jQuery(".wizard-previous-step").addClass("hide");
                } else {
                    wizard_next_step(next_step);
                }
            } else if (next_step > current_step) {
                wizard_next_step(next_step);
            } else {
                show_step(next_step);
            }
        }
    }

    $(".add-on").on("click", function () {
        var prev_input = $(this).prev("input");
        if ($(prev_input).prop("disabled") == false) {
            $(prev_input).focus();
        }
    });

    enable_wizard_controls();

    $(".wizard-next-step").on("click", function (e) {
        e.preventDefault();
        wizard_next_step();
    });

    $(".wizard-previous-step").on("click", function (e) {
        e.preventDefault();
        wizard_previous_step();
    });

    $(".wizard-nav-item").on("click", function (e) {
        e.preventDefault();
        navItemClick($(this));
    });

    var post_id = jQuery("#post_id").val();
    if (post_id.length > 0) {
        $("#wizard-nav-item-" + redirect_section).trigger("click");
    } else {
        show_step(redirect_section);
    }

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

    $("#use_exception_time_factor").click(function() {
        var clicked = $(this);
        var exception_time_factor = $("#exception_time_factor");

        if (clicked.prop("checked") == true ) {
            $(exception_time_factor).prop("checked", true);
            $(exception_time_factor).prop("disabled", false);
        } else {
            $(exception_time_factor).prop("checked", false);
            $(exception_time_factor).prop("disabled", true);
        }
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

    $(".use_date").click(function() {
        var clicked = $(this);
        var id = clicked.attr("id");
        var date_id = clicked.data("date-name");
        var time_id = clicked.data("time-name");

        var calendar_icon = $("#" + date_id).next(".add-on");

        if (clicked.prop("checked") == true ) {
            $("#" + date_id).val($("#" + date_id).data("default-date")).attr("disabled", false);
            $("#" + time_id).val($("#" + time_id).data("default-time")).attr("disabled", false);
            $(calendar_icon).addClass("pointer");
        } else {
            $("#" + date_id).data("default-date", $("#" + date_id).val());
            $("#" + time_id).data("default-time", $("#" + time_id).val());
            $("#" + date_id).val("").attr("disabled", true);
            $("#" + time_id).val("").attr("disabled", true);
            $(calendar_icon).removeClass("pointer");
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
                    if (secure_key_count > 0){
                        secure_key_badge.text(jsonResponse.data.length);
                    }

                } else if (jsonResponse.status === "empty"){
                    $secure_key_exists = false;
                    targetOutput.empty();
                    targetOutput.html("<div class=\"alert alert-info\">" + jsonResponse.data + "</div>");
                    if (!jQuery("#delete-secure-keys").hasClass("hide")){
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

                } else if (jsonResponse.status === "empty"){
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

                    if (!jQuery("#delete-secure-file").hasClass("hide")){
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

function wizard_next_step (next_step) {
    for (instance in CKEDITOR.instances) {
        CKEDITOR.instances[instance].updateElement();
    }

    var step;
    if (typeof next_step !== "undefined" ) {
        step = next_step - 1;
        if (step <= 0) {
            step = 1;
        }
    } else {
        step = parseInt(jQuery("#wizard-step-input").val());
    }

    var wizard_step_request = jQuery.ajax({
        url: ENTRADA_URL + "/admin/events?section=api-exams",
        data: "method=exam-wizard&" + jQuery("#search-targets-form").serialize() + "&step=" + step  + (typeof next_step !== "undefined" ? "&next_step=" + next_step : ""),
        type: "POST",
        beforeSend: function () {
            show_loading_msg();
        },
        complete: function () {
            hide_loading_msg();
            if (step == 6) {
                disable_wizard_controls();
            } else {
                enable_wizard_controls();
                if (step == 5) {
                    jQuery(".wizard-next-step").html("Save Exam Post");
                } else {
                    jQuery(".wizard-next-step").html("Next Step");
                }
            }
        }
    });

    jQuery.when(wizard_step_request).done(function (data) {
        var jsonResponse = JSON.parse(data);
        var current_step = jQuery("#wizard-step-input").val();

        remove_errors();
        if (jsonResponse.status === "success") {
            jQuery("li[data-step=\""+ current_step +"\"]").addClass("complete");
            var next_step = parseInt(jsonResponse.data.step);

            switch (next_step) {
                case 2 :
                    break;
                case 3 :
                    var post_id  = jsonResponse.data.post_id;
                    get_secure_file(jQuery(".secure-file-list-content"), post_id);
                    get_secure_keys(jQuery(".secure-key-list-content"), post_id);
                    break;
                case 6 :
                    jQuery("#review-exam-details").empty();
                    var $exam_detail = "<table class=\"table table-striped table-bordered\">";
                    jQuery.each(jsonResponse.data.post, function(i, v){
                        var display = v.display;
                        $exam_detail += "<tr>";
                        $exam_detail += "<td>" + v.label + "</td>";
                        $exam_detail += "<td>" + display + "</td>";
                        $exam_detail += "</tr>";
                    });
                    $exam_detail += "</table>";
                    jQuery("#review-exam-details").append($exam_detail);
                    break;
                case 7 :
                    var post_id = jsonResponse.data.post_id;
                    var update_post_row = jQuery("tr.exam-posting[data-post-id=" + post_id + "]");
                    var form_body = jQuery("form#exam-listing tbody");
                    var generic_msg = jQuery("form#exam-listing tbody div.display-generic");
                    var post_row = false;
                    var dataObject = {
                        method : "get-exam-post-row",
                        post_id: post_id
                    };
                    get_secure_keys(jQuery(".secure-key-list"), post_id);

                    var redirect_secure = false;
                    var string_mode = "";

                    if (jsonResponse.data.post.mode.value == "create") {
                        string_mode = "created";
                        if (jsonResponse.data.post.secure.value == "1") {
                            redirect_secure = true;
                            var post_id_redirect = post_id;
                        }
                    } else {
                        string_mode = "edited"
                    }

                    jQuery.ajax({
                        url: API_URL,
                        data: dataObject,
                        type: "GET",
                        success: function (data) {
                            var jsonResponse = JSON.parse(data);
                            if (jsonResponse["success"] = "success") {
                                post_row = jsonResponse["post_view"];
                            }

                            if (update_post_row.length > 0) {
                                if (jQuery.isEmptyObject(update_post_row)) {
                                    jQuery(form_body).append(post_row);
                                } else {
                                    jQuery(update_post_row).before(post_row).remove();
                                }
                            } else {
                                jQuery(form_body).append(post_row);
                            }

                            if (generic_msg.length > 0) {
                                jQuery(generic_msg).parents("tr").remove();
                                generic_msg.remove();
                            }

                            jQuery(".wizard-step-container").addClass("hide");

                            var referrer = $("#referrer").val();
                            if (referrer == "event") {
                                var eventID = $("input.event-id").val();
                                var redirectUrl = ENTRADA_URL + "/admin/events?section=content&id=" + eventID;
                            } else if (referrer == "exam") {
                                var examID = $("input#exam-id").val();
                                var redirectUrl = (!redirect_secure ? ENTRADA_URL + "/admin/exams/exams?section=post&id=" + examID : ENTRADA_URL + "/admin/exams/exams?section=form-post&id=" + examID + "&post_id=" + post_id_redirect + "&target_type=event&redirect_section=3");
                            }

                            if (!redirect_secure) {
                                display_success(["The Exam Post has been successfully " + string_mode + ". You will now be redirected in 5 seconds or <a href=" + redirectUrl + "><strong>click here</strong></a> to continue."], "#msgs");
                            } else {
                                display_success(["The Exam Post has been successfully created. You will now be redirected the Security Setup step in 5 seconds or <a href=" + redirectUrl + "><strong>click here</strong></a> to continue."], "#msgs");
                            }

                            setTimeout(function() {
                                window.location.replace(redirectUrl);
                            }, 5000);
                        }
                    });
                    break;
                default :
                    jQuery(".wizard-next-step").html("Next");
                    break;
            }
            post_id = jQuery("#post_id").val();

            if (typeof post_id !== "undefined" || post_id > 0) {
                jQuery(".wizard-nav-item.active").removeClass("active");
                jQuery("#wizard-nav-item-" + next_step).addClass("active");
                jQuery("li[data-step=\""+ current_step +"\"]").addClass("complete");
            }

            if (next_step > 1) {
                jQuery(".wizard-previous-step").removeClass("hide");
            }

            if (jsonResponse.data.hasOwnProperty("previous_step")) {
                jQuery("#wizard-previous-step-input").val(jsonResponse.data.previous_step);
            } else {
                jQuery("#wizard-previous-step-input").val("0");
            }

            jQuery("#wizard-step-input").val(next_step);
            jQuery(".wizard-step").addClass("hide");
            jQuery("#wizard-step-" + next_step).removeClass("hide");
            toggle_active_nav_item(next_step);
        } else {
            enable_wizard_controls();
            display_error(jsonResponse.data, "#msgs", "prepend");
        }
    });
}

function wizard_previous_step () {
    var step = parseInt(jQuery("#wizard-step-input").val());
    var previous_step = parseInt(jQuery("#wizard-previous-step-input").val());
    //jQuery(".wizard-next-step").html("Next");
    remove_errors();

    if (previous_step !== 0) {
        var updated_step = previous_step;
        jQuery("#wizard-step-input").val(updated_step);
        jQuery(".wizard-step").addClass("hide");
        jQuery("#wizard-step-" + updated_step).removeClass("hide");
        jQuery("#wizard-previous-step-input").val("0");
        toggle_active_nav_item(updated_step);
    } else {
        if (step > 1) {
            if (step == 4) {
                var secure_false_value = $("#secure_false").is(":checked");
                if (secure_false_value === true) {
                    //skip step
                    var updated_step = step - 2;
                } else {
                    var updated_step = step - 1;
                }
            } else {
                var updated_step = step - 1;
            }

            jQuery("#wizard-step-input").val(updated_step);
            jQuery(".wizard-step").addClass("hide");
            jQuery("#wizard-step-" + updated_step).removeClass("hide");
            toggle_active_nav_item(updated_step);
        }
    }

    if (step == 7) {
        jQuery(".wizard-next-step").html("Save Exam Post");
    } else {
        jQuery(".wizard-next-step").html("Next Step");
    }

    var updated_step = parseInt(jQuery("#wizard-step-input").val());
    if (updated_step === 1) {
        jQuery(".wizard-previous-step").addClass("hide");
    }
}

function remove_errors () {
    jQuery("#msgs").empty();
}

function show_loading_msg () {
    disable_wizard_controls();
    jQuery("#wizard-loading-msg").html("Loading Exam Options...");
    jQuery("#search-targets-form").addClass("hide");
    jQuery("#wizard-loading").removeClass("hide");
}

function hide_loading_msg () {
    enable_wizard_controls();
    jQuery("#wizard-loading").addClass("hide");
    jQuery("#wizard-loading-msg").html("");
    jQuery("#search-targets-form").removeClass("hide");
}

function enable_wizard_controls () {
    if (jQuery(".wizard-next-step").is(":disabled")) {
        jQuery(".wizard-next-step").removeAttr("disabled");
    }

    if (jQuery(".wizard-previous-step").is(":disabled")) {
        jQuery(".wizard-previous-step").removeAttr("disabled");
    }
}

function disable_wizard_controls () {
    if (!jQuery(".wizard-next-step").is(":disabled")) {
        jQuery(".wizard-next-step").attr("disabled", "disabled");
    }

    if (!jQuery(".wizard-previous-step").is(":disabled")) {
        jQuery(".wizard-previous-step").attr("disabled", "disabled");
    }
}

function toggle_active_nav_item (step) {
    jQuery(".wizard-nav-item").removeClass("active");
    jQuery("#wizard-nav-item-" + step).addClass("active");
}

function show_step (next_step) {
    update_step(next_step);
    remove_errors();
    toggle_active_nav_item (next_step);

    jQuery(".wizard-step").addClass("hide");
    jQuery("#wizard-step-" + next_step).removeClass("hide");
}

function update_step (step) {
    jQuery("#wizard-step-input").val(step);
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
