jQuery(document).ready(function ($) {

    var edit_mode = false;
    var resource_step_control = $("#quick-add-resource-form").find("input[name='resource_step']");
    var resource_substep_control = $("#quick-add-resource-form").find("input[name='resource_substep']");
    var resource_type_value_control = $("#quick-add-resource-form").find("input[name='event_resource_type_value']");
    var resource_required_value_control = $("#quick-add-resource-form").find("input[name='event_resource_required_value']");
    var resource_release_value_control = $("#quick-add-resource-form").find("input[name='event_resource_release_value']");
    var resource_timeframe_value_control = $("#quick-add-resource-form").find("input[name='event_resource_timeframe_value']");
    var resource_release_start_control = $("#quick-add-resource-form").find("input[name='event_resource_release_start_value']");
    var resource_release_start_time_control = $("#quick-add-resource-form").find("input[name='event_resource_release_start_time_value']");
    var resource_release_finish_control = $("#quick-add-resource-form").find("input[name='event_resource_release_finish_value']");
    var resource_release_finish_time_control = $("#quick-add-resource-form").find("input[name='event_resource_release_finish_time_value']");
    var resource_file_view_value = $("#quick-add-resource-form").find("input[name='event_resource_file_view_value']");
    var resource_file_title_controls = {};
    var resource_entity_id = $("#quick-add-resource-form").find("input[name='event_resource_entity_id']");
    var resource_event_id = $("#quick-add-resource-form").find("input[name='event_id']");
    var resource_is_recurring = $("#quick-add-resource-form").find("input[name='is_recurring_event']").val();
    var resource_recurring_event_ids = JSON.parse($("#quick-add-resource-form").find("input[name='recurring_event_ids']").val());
    var event_resource_id_value = $("#quick-add-resource-form").find("input[name='resource_id']");
    var resource_attach_file = $("#quick-add-resource-form").find("input[name='event_resource_attach_file']");
    var quick_add_control_group_container = $("#quick-add-resource-control-groups");
    var quick_add_resource_drop_overlay = $("#quick-add-resource-drop-overlay");
    var quick_add_drop_container = $("#quick-add-resource-drop-container");
    var quick_add_drop = $("#quick-add-resource-drop");

    /**
     * @var data transfer files that get dragged and dropped onto the page
     */
    var selected_files = [];

    /**
     * @var boolean dragdrop whether the user can drag and drop files onto the page
     */
    var dragdrop;

    if (window.File && window.FileReader && window.FileList && window.Blob) {
        dragdrop = true;
    } else {
        dragdrop = false;
    }

    /**
     *
     * Builds drag and drop interface
     *
     */
    function build_quick_add_drag_drop() {

        var upload_input = document.createElement("input");
        var upload_input_div = document.createElement("div");
        var drag_drop_p = document.createElement("p");
        var upload_label = document.createElement("label");
        var upload_span = document.createElement("span");

        $(upload_span).html("No file selected").addClass("event-resource-upload-span");

        $(upload_input).attr({type: "file", id: "quick_add_resource_upload", name: "file", multiple: "multiple"}).addClass("hide");
        $(upload_label).addClass("btn btn-default").append("Browse").append(upload_input);
        $(upload_input_div).append(upload_label).append(upload_span).addClass("event-resource-upload-input-div");
        $(drag_drop_p).html("Please select a file to upload.").addClass("event-resource-upload-text");

        if (dragdrop) {
            var drag_drop_img_div = document.createElement("div");
            var drag_drop_img = document.createElement("span");

            $(drag_drop_p).html("You can drag and drop files into this area to upload.").addClass("event-resource-upload-text");
            $(drag_drop_img).addClass("fa fa-file-text");
            $(drag_drop_img_div).append(drag_drop_img).addClass("event-resource-upload-div");
            quick_add_drop.append(drag_drop_img_div);
        }

        quick_add_drop.append(drag_drop_p);
        quick_add_drop.append(upload_input_div);
    }

    /**
     * Show modal dialog for quickly adding a resource
     */
    function show_quick_add_resource() {

        if (selected_files.length) {

            $("#quick-add-resource-modal").modal("show");
            $("#quick-add-resource-loading").addClass("hide");
            $("#quick-add-resource-form").removeClass("hide");
            $("#quick-add-resource-loading-msg").html("");

            $("#quick-add-resource-close").addClass("hide");
            $("#quick-add-resource-cancel").removeClass("hide");
            $("#quick-add-resource-submit").removeClass("hide");

            quick_add_control_group_container.empty();

            build_quick_add_file_controls();
            build_quick_add_copyright();
            build_quick_add_timed_release();
        }
    };

    /**
     *
     * Builds file view controls
     *
     */
    function build_quick_add_file_controls() {

        var resource_file_view_option_heading = document.createElement("h3");
        var resource_file_view_option_control_group = document.createElement("div");
        var resource_file_option_download_label = document.createElement("label");
        var resource_file_option_download_radio = document.createElement("input");
        var resource_file_option_view_label = document.createElement("label");
        var resource_file_option_view_radio = document.createElement("input");


        $(resource_file_view_option_heading).html("How do you want people to view the file(s)?");
        $(resource_file_option_download_radio).val("download").attr({
            type: "radio",
            id: "quick_add_resource_file_download",
            name: "event_resource_file_view_option"
        });
        $(resource_file_option_download_label).attr({"for": "quick_add_resource_file_download"}).addClass("radio");
        $(resource_file_option_view_radio).val("view").attr({
            type: "radio",
            id: "quick_add_resource_file_view",
            name: "event_resource_file_view_option"
        });
        $(resource_file_option_view_label).attr({"for": "quick_add_resource_file_view"}).addClass("radio");
        $(resource_file_option_download_label).append(resource_file_option_download_radio).append("Download it to their computer first, then open it.");
        $(resource_file_option_view_label).append(resource_file_option_view_radio).append("Attempt to view it directly in the web-browser.");
        $(resource_file_view_option_control_group)
            .append(resource_file_view_option_heading)
            .append(resource_file_option_download_label)
            .append(resource_file_option_view_label)
            .addClass("control-group");

        quick_add_control_group_container.prepend(resource_file_view_option_control_group);

        var selected_file_view_option = resource_file_view_value.val();

        switch (selected_file_view_option) {
            case "view":
                $("#quick_add_resource_file_view").prop("checked", true);
                break;
            case "download":
                $("#quick_add_resource_file_download").prop("checked", true);
                break;
        }

        /**
         *
         * Builds file title controls for each file
         *
         */
        for (var i = 0; i <= selected_files.length - 1; i++) {

            var filename = selected_files[i].name;

            /**
             * Build hidden input that gets used to send the data to the server.
             */
            var resource_file_title_control = $(document.createElement("input"));

            resource_file_title_control.attr({type: "hidden", name: "event_resource_file_title_value[" + i + "]"});
            resource_file_title_controls[i] = resource_file_title_control;
            $("#quick-add-resource-form").append(resource_file_title_control);

            /**
             * Set the file title to a pretty version of the first filename.
             *
             * 1. Convert underscores to spaces
             * 2. Remove the file extension
             * 3. Convert the first letter to an uppercase letter.
             */
            var pretty_filename = filename;

            pretty_filename = pretty_filename.replace(/_/g, " ");
            pretty_filename = pretty_filename.replace(/\.[^\.]+$/, "");
            pretty_filename = pretty_filename.replace(/^\w/, function(c) { return c.toUpperCase(); });

            resource_file_title_control.val(pretty_filename);

            /**
             * Provide text inputs for the user to change any file title(s)
             */
            var resource_file_title_heading = document.createElement("h3");
            var resource_file_title_control_group = document.createElement("div");
            var resource_file_title_input_container = document.createElement("div");
            var resource_file_title_input = document.createElement("input");

            $(resource_file_title_heading).html("You can optionally provide a different title for " + filename + ".");
            $(resource_file_title_input).attr({
                type: "text",
                id: "quick_add_resource_file_title",
                name: "event_resource_file_title[" + i + "]"
            });
            $(resource_file_title_input).addClass("input-xlarge");
            $(resource_file_title_input).val(resource_file_title_control.val());
            $(resource_file_title_input_container).append(resource_file_title_input);
            $(resource_file_title_control_group).append(resource_file_title_heading).append(resource_file_title_input_container).addClass("control-group");

            quick_add_control_group_container.append(resource_file_title_control_group);
        }
    }

    /**
     *
     * Builds time release options
     *
     */
    function build_quick_add_timed_release() {

        var start_date_value = resource_release_start_control.val();
        var finish_date_value = resource_release_finish_control.val();
        var start_time_value = resource_release_start_time_control.val();
        var finish_time_value = resource_release_finish_time_control.val();

        var resource_release_heading = document.createElement("h3");
        var resource_release_control_group = document.createElement("div");
        var resource_release_no_label = document.createElement("label");
        var resource_release_no_radio = document.createElement("input");
        var resource_release_yes_label = document.createElement("label");
        var resource_release_yes_radio = document.createElement("input");

        $(resource_release_heading).html("Would you like to add timed release dates to the resource(s)?");

        $(resource_release_no_label).attr({"for": "quick_add_resource_release_no"}).addClass("radio");
        $(resource_release_no_radio).attr({type: "radio", id: "quick_add_resource_release_no", value: "no", name: "resource_release"});
        $(resource_release_no_label).append(resource_release_no_radio).append("No, the resource(s) is accessible any time");

        $(resource_release_yes_label).attr({"for": "quick_add_resource_release_yes"}).addClass("radio");
        $(resource_release_yes_radio).attr({type: "radio", id: "quick_add_resource_release_yes", value: "yes", name: "resource_release"});
        $(resource_release_yes_label).append(resource_release_yes_radio).append("Yes, the resource(s) should only be available for a certain time period");

        $(resource_release_control_group)
            .append(resource_release_no_label)
            .append(resource_release_yes_label)
            .addClass("control-group");

        quick_add_control_group_container.append(resource_release_heading).append(resource_release_control_group);

        var selected_resource_release_option = resource_release_value_control.val();

        switch (selected_resource_release_option) {
            case "no":
                $("#quick_add_resource_release_no").prop("checked", true);
                break;
            case "yes":
                $("#quick_add_resource_release_yes").prop("checked", true);
                break;
        }

        var release_options_container = document.createElement("div");
        var resource_release_options_heading = document.createElement("h3");

        /**
         *
         *  Builds release start controls
         *
         */

        var resource_release_start_control_group = document.createElement("div");
        var resource_release_start_controls = document.createElement("div");
        var resource_release_start_date_append = document.createElement("div");
        var resource_release_start_time_append = document.createElement("div");
        var resource_release_start_date_span = document.createElement("span");
        var resource_release_start_date_icon = document.createElement("i");
        var resource_release_start_time_span = document.createElement("span");
        var resource_release_start_time_icon = document.createElement("i");
        var resource_release_start_label = document.createElement("label");
        var resource_release_start_date_input = document.createElement("input");
        var resource_release_start_time_input = document.createElement("input");

        $(resource_release_start_label).attr({"for": "quick_add_resource_release_start"}).html("Release Start: ").addClass("control-label");
        $(resource_release_start_date_input).attr({type: "text", id: "quick_add_resource_release_start", name: "event_resource_release_start"}).addClass("input-small datepicker start-date").val(start_date_value);
        $(resource_release_start_date_append).addClass("input-append space-right");
        $(resource_release_start_date_span).addClass("add-on pointer");
        $(resource_release_start_date_icon).addClass("icon-calendar");

        $(resource_release_start_time_input).attr({type: "text", id: "quick_add_resource_release_start_time", name: "event_resource_release_start_time"}).addClass("input-mini timepicker start-time").val(start_time_value);
        $(resource_release_start_time_append).addClass("input-append");
        $(resource_release_start_time_span).addClass("add-on pointer");
        $(resource_release_start_time_icon).addClass("icon-time");
        $(resource_release_start_date_span).append(resource_release_start_date_icon);
        $(resource_release_start_date_append).append(resource_release_start_date_input).append(resource_release_start_date_span);
        $(resource_release_start_controls).append(resource_release_start_date_append).addClass("controls");

        $(resource_release_start_time_span).append(resource_release_start_time_icon);
        $(resource_release_start_time_append).append(resource_release_start_time_input).append(resource_release_start_time_span);
        $(resource_release_start_controls).append(resource_release_start_time_append);
        $(resource_release_start_control_group)
            .append(resource_release_start_label)
            .append(resource_release_start_controls)
            .addClass("control-group");

        /**
         *
         *  Builds release finish controls
         *
         */

        var resource_release_finish_control_group = document.createElement("div");
        var resource_release_finish_controls = document.createElement("div");
        var resource_release_finish_date_append = document.createElement("div");
        var resource_release_finish_time_append = document.createElement("div");
        var resource_release_finish_date_span = document.createElement("span");
        var resource_release_finish_date_icon = document.createElement("i");
        var resource_release_finish_time_span = document.createElement("span");
        var resource_release_finish_time_icon = document.createElement("i");
        var resource_release_finish_label = document.createElement("label");
        var resource_release_finish_date_input = document.createElement("input");
        var resource_release_finish_time_input = document.createElement("input");

        $(resource_release_finish_label).attr({"for": "quick_add_resource_release_finish"}).html("Release Finish: ").addClass("control-label");
        $(resource_release_finish_date_input).attr({
            type: "text",
            id: "quick_add_resource_release_finish",
            name: "event_resource_release_finish"
        });
        $(resource_release_finish_date_input).addClass("input-small datepicker finish-date").val(finish_date_value);
        $(resource_release_finish_date_append).addClass("input-append space-right");
        $(resource_release_finish_date_span).addClass("add-on pointer");
        $(resource_release_finish_date_icon).addClass("icon-calendar");

        $(resource_release_finish_time_input).attr({type: "text", id: "quick_add_resource_release_finish_time", name: "event_resource_release_finish_time"}).addClass("input-mini timepicker finish-time").val(finish_time_value);
        $(resource_release_finish_time_append).addClass("input-append");
        $(resource_release_finish_time_span).addClass("add-on pointer");
        $(resource_release_finish_time_icon).addClass("icon-time");
        $(resource_release_finish_date_span).append(resource_release_finish_date_icon);
        $(resource_release_finish_date_append).append(resource_release_finish_date_input).append(resource_release_finish_date_span);
        $(resource_release_finish_controls).append(resource_release_finish_date_append).addClass("controls");

        $(resource_release_finish_time_span).append(resource_release_finish_time_icon);
        $(resource_release_finish_time_append).append(resource_release_finish_time_input).append(resource_release_finish_time_span);
        $(resource_release_finish_controls).append(resource_release_finish_time_append);
        $(resource_release_finish_control_group)
            .append(resource_release_finish_label)
            .append(resource_release_finish_controls)
            .addClass("control-group");

        $(release_options_container).attr({id: "quick_add_resource_release_container"})
            .append(resource_release_start_control_group)
            .append(resource_release_finish_control_group);

        if (selected_resource_release_option == "no") {
            $(release_options_container).addClass("hide");
        }

        $(quick_add_control_group_container).append(resource_release_options_heading).append(release_options_container);
    }

    /**
     *
     * Gets and builds UI for the file upload copyright statement
     *
     */
    function build_quick_add_copyright() {

        $.ajax({
            url: SITE_URL + "/admin/events?section=api-resource-wizard",
            data: "method=copyright",
            type: 'GET',
            success: function (data) {

                var jsonResponse = JSON.parse(data);

                if (jsonResponse.status === "success") {
                    var copyright_heading = document.createElement("h3");
                    var copyright_div = document.createElement("div");

                    $(copyright_heading).html(jsonResponse.data.app_name + " File Upload Copyright Statement");
                    $(copyright_div).html(jsonResponse.data.copyright_statement);
                    quick_add_control_group_container.append(copyright_heading).append(copyright_div);
                } else {
                    display_error(jsonResponse.data, "#quick-add-resource-msgs", "append");
                }
            },
            beforeSend: function () {
                lock_modal("Loading Copyright Statement");
            },
            complete: function () {
                unlock_modal();
            }
        });
    }

    /**
     * Uploads the files that have been provided be the user.
     * Displays a success to the user of all the filenames, and adds any error messages
     * to the modal dialog.
     */
    function upload_files() {

        /* Initial promise that will instantly succeed. We'll use this to chain subsequent calls to
         * upload_file() that returns promises that may fail if the upload fails.
         */
        var all_promise = $.Deferred().resolve().promise();

        /**
         * Function to ensure that if a file upload fails, we do not continue uploading files.
         * Each time we asynchronously upload a file, we wait until that one is done before proceeding
         * to wait for the next file. We do this using promises as that is the only way to wait for
         * an asynchronous operation to finish before proceeding to subsequent uploads. If an upload
         * fails, we do not recursively descend to the subsequent uploads.
         *
         * @param int file index to start uploading at
         *
         * @return promise of successfully uploaded filenames
         */
        function promise_upload(file_index) {

            var file = selected_files[file_index];

            var deferred = $.Deferred();

            upload_file(file_index, file).then(function (success_filename) {

                if (file_index < selected_files.length - 1) {
                    /*
                     * Recursively call this function to resolve the list of
                     * successfully uploaded filenames. This will means we
                     * promise the upload of the next file once this file has
                     * successfully been uploaded, and so on.
                     */
                    promise_upload(file_index + 1).done(function (success_filenames) {

                        var new_success_filenames = [success_filename].concat(success_filenames);

                        deferred.resolve(new_success_filenames);
                    });
                } else {
                    deferred.resolve([success_filename]);
                }

            }, function (error_message) {

                unlock_modal();
                display_error([error_message], "#quick-add-resource-msgs", "append");

                deferred.reject();
            });

            return deferred.promise();
        }

        promise_upload(0).done(function(success_filenames) {
            show_success(success_filenames);
        });
    }

    /**
     * Uploads one file that was provided by the user
     *
     * @param data transfer file
     *
     * @return jquery promise that resolves empty on success of upload, or fails with an error message string
     */
    function upload_file(file_index, file) {

        var deferred = $.Deferred();

        var xhr = new XMLHttpRequest();
        var fd = new FormData();

        if (file.size <= 300000000) {

            var resource_file_title_control = resource_file_title_controls[file_index];

            $("#quick-add-resource-msgs").empty();
            $("#quick-add-resource-loading-msg").html("Uploading " + file.name + ", this may take a few moments.");
            $("#quick-add-resource-form").addClass("hide");
            $("#quick-add-resource-loading").removeClass("hide");

            fd.append("file", file);
            fd.append("method", "add");
            fd.append("event_id", resource_event_id.val());
            fd.append("resource_recurring_bool", resource_is_recurring);
            fd.append("resource_recurring_event_ids", resource_recurring_event_ids);
            fd.append("event_resource_required_value", resource_required_value_control.val());
            fd.append("event_resource_timeframe_value", resource_timeframe_value_control.val());
            fd.append("event_resource_release_value", resource_release_value_control.val());
            fd.append("event_resource_release_start_value", resource_release_start_control.val());
            fd.append("event_resource_release_start_time_value", resource_release_start_time_control.val());
            fd.append("event_resource_release_finish_value", resource_release_finish_control.val());
            fd.append("event_resource_release_finish_time_value", resource_release_finish_time_control.val());
            fd.append("event_resource_file_view_value", resource_file_view_value.val());
            fd.append("event_resource_file_title_value", resource_file_title_control.val());
            fd.append("event_resource_file_description_value", resource_file_title_control.val());
            fd.append("event_resource_attach_file", resource_attach_file.val());
            fd.append("step", resource_step_control.val());
            fd.append("resource_substep", resource_substep_control.val());
            fd.append("event_resource_type_value", resource_type_value_control.val());
            fd.append("resource_id", event_resource_id_value.val());
            fd.append("event_resource_entity_id", resource_entity_id.val());

            xhr.open('POST', SITE_URL + "/admin/events?section=api-resource-wizard", true);
            xhr.send(fd);

            xhr.onreadystatechange = function() {

                if (xhr.readyState == 4) {

                    if (xhr.status == 200) {

                        $("#quick-add-resource-loading").addClass("hide");
                        $("#quick-add-resource-form").removeClass("hide");
                        $("#quick-add-resource-loading-msg").html("");

                        var jsonResponse = JSON.parse(xhr.responseText);

                        if (jsonResponse.status == "success") {

                            deferred.resolve(file.name);

                        } else {

                            var error_message = jsonResponse.data;

                            deferred.reject("Error uploading \"" + file.name + "\": " + error_message);
                        }

                    } else {

                        deferred.reject("Error uploading \"" + file.name + "\". Contact your administrator.");
                    }
                }
            }

        } else {

            deferred.reject("Error uploading \"" + file.name + "\": file exceeds 300MB.");
        }

        return deferred.promise();
    }

    /**
     * Show success message
     *
     * @param filename that got successfully uploaded
     */
    function show_success(filenames) {

        quick_add_control_group_container.empty();

        for (var i = 0; i <= filenames.length - 1; i++) {

            var filename = filenames[i];

            var success_p = document.createElement("p");

            $(success_p).html("Successfully attached " + filename + " to this event.").attr({id: "quick-add-resource-success-msg"});
            quick_add_control_group_container.append(success_p);
        }

        var success_text_p = document.createElement("p");

        $(success_text_p).html("You may close this dialog by clicking the <strong>Close</strong> button.");
        $(success_text_p).attr({id: "quick-add-resource-success-text"});
        quick_add_control_group_container.append(success_text_p);

        $("#quick-add-resource-close").removeClass("hide");
        $("#quick-add-resource-cancel").addClass("hide");
        $("#quick-add-resource-submit").addClass("hide");

        /* Reload the list of resources on the event content page */
        event_resources_load();
    }

    /**
     * Lock the modal dialog from user tampering when performing an operation.
     *
     * @param string loading_msg Message to show in loading div
     */
    function lock_modal(loading_msg) {
        $("#quick-add-resource-submit").attr("disabled", "disabled");

        if (loading_msg) {
            $("#quick-add-resource-loading-msg").html(loading_msg);
        }

        $("#quick-add-resource-form").addClass("hide");
        $("#quick-add-resource-loading").removeClass("hide");
    }

    /**
     * Unlock modal dialog after loading is complete.
     */
    function unlock_modal() {

        if ($("#quick-add-resource-submit").is(":disabled")) {
            $("#quick-add-resource-submit").removeAttr("disabled");
        }

        $("#quick-add-resource-loading").addClass("hide");
        $("#quick-add-resource-form").removeClass("hide");
        $("#quick-add-resource-loading-msg").html("");
    }

    /**
     * Handle the user clicking "Browse" to select a file
     */
    quick_add_drop_container.on("change", "#quick_add_resource_upload", function (event) {

        event.target = event.originalEvent.target;
        selected_files = event.target.files;

        show_quick_add_resource();
    });

    /**
     * Define drag and drop handlers for dragging and dropping a files onto the page
     */
    if (dragdrop) {

        var timer;

        /**
         * When user drags a file over, show the overlay indicating the
         * rectangular "box" that the user must drop the file into.
         */
        quick_add_drop_container.on("dragover", function (event) {
            clearTimeout(timer);
            event.preventDefault();
            event.stopPropagation();

            quick_add_resource_drop_overlay.removeClass("hide");
            quick_add_drop.addClass("hide");
        });

        /**
         * When user drags a file out of the drop area, hide the overlay.
         */
        quick_add_drop_container.on("dragleave", function (event) {
            event.preventDefault();
            event.stopPropagation();

            timer = setTimeout(function() {
                quick_add_resource_drop_overlay.addClass("hide");
                quick_add_drop.removeClass("hide");
            }, 200);

            return false;
        });

        /**
         * When user drops the file into the drop area, hide the overlay
         * and show the upload screen.
         */
        quick_add_drop_container.on("drop", function (event) {
            event.preventDefault();
            event.stopPropagation();

            event.dataTransfer = event.originalEvent.dataTransfer;
            selected_files = event.dataTransfer.files;

            quick_add_resource_drop_overlay.addClass("hide");
            quick_add_drop.removeClass("hide");

            $("#quick-add-resource-msgs").empty();

            show_quick_add_resource();
        });
    }

    /**
     * Define input field change handlers for updating hidden inputs.
     *
     * When user updates a field in the UI, we automatically update hidden inputs to
     * have the corresponding values. These in turn are used to send data to the API.
     */

    /**
     * When user updates view option, update view option hidden input.
     */
    quick_add_control_group_container.on("change", "input[name=event_resource_file_view_option]", function () {
        var view_option = $(this).val();
        resource_file_view_value.val(view_option);
    });

    /**
     * When user updates one of the file titles, update the file title hidden
     * input of the corresponding file index.
     */
    quick_add_control_group_container.on("keyup", "input[name^=event_resource_file_title]", function () {

        /**
         * Parse file title text field's input name for the file index.
         */
        var file_title_input_name = $(this).attr("name");
        var match = /event_resource_file_title\[(.*)\]/.exec(file_title_input_name);
        var file_index = parseInt(match[1]);    // File index is the first group (.*) matched by the regex

        if (!resource_file_title_controls[file_index]) {
            throw new Error("Expected hidden input to exist for file title " + file_index);
        }

        var file_title = $(this).val();
        resource_file_title_controls[file_index].val(file_title);
    });

    /**
     * When user updates start date picker, update the hidden input to contain the start date.
     */
    quick_add_control_group_container.on("change", "input[name=event_resource_release_start]", function () {
        var release_start_date = $(this).val();
        resource_release_start_control.val(release_start_date);
    });

    /**
     * When user updates start time picker, update the hidden input to contain the start time.
     */
    quick_add_control_group_container.on("change", "input[name=event_resource_release_start_time]", function () {
        var release_start_time = $(this).val();
        resource_release_start_time_control.val(release_start_time);
    });

    /**
     * When user updates finish date picker, update the hidden input to contain the finish date.
     */
    quick_add_control_group_container.on("change", "input[name=event_resource_release_finish]", function () {
        var release_finish_date = $(this).val();
        resource_release_finish_control.val(release_finish_date);
    });

    /**
     * When user updates finish time picker, update the hidden input to contain the finish time.
     */
    quick_add_control_group_container.on("change", "input[name=event_resource_release_finish_time]", function () {
        var release_finish_time = $(this).val();
        resource_release_finish_time_control.val(release_finish_time);
    });

    /**
     * When user says yes to setting a release start and finish time, show the
     * start and finish time control group. When they say no, hide it.
     */
    quick_add_control_group_container.on("change", "input[name=resource_release]", function () {
        var resource_release = $(this).val();
        resource_release_value_control.val(resource_release);
        if (resource_release == "yes") {
            $("#quick_add_resource_release_container").removeClass("hide");
        } else {
            $("#quick_add_resource_release_container").addClass("hide");
        }
    });

    /**
     * When user clicks the date field, show a date picker.
     * When they pick a date, populate the date field with that text.
     */
    quick_add_control_group_container.on("click", ".datepicker", function () {
        $(this).datepicker({
            dateFormat: "yy-mm-dd",
            onSelect: function (date) {
                if ($(this).hasClass("start-date")) {
                    resource_release_start_control.val(date);
                } else {
                    resource_release_finish_control.val(date);
                }
            }
        });

        $(this).datepicker("show");
    });

    /**
     * When user clicks the time field, show a time picker.
     * When they pick a time, populate the time field with that text.
     */
    quick_add_control_group_container.on("click", ".timepicker", function () {
        $(this).timepicker({
            onSelect: function (time) {
                if ($(this).hasClass("start-time")) {
                    resource_release_start_time_control.val(time);
                } else {
                    resource_release_finish_time_control.val(time);
                }
            }
        });

        $(this).timepicker("show");

        /*
         * Move timepicker as top value is wonky
         */
        $(".ui-timepicker").position({of: $(this), at: "bottom"});
        // Refresh the timepicker
        $(this).timepicker("setTime", $(this).timepicker("getTime"));
    });

    $("#quick-add-resource-submit").on("click", function () {
        upload_files();
    });

    build_quick_add_drag_drop();
});
