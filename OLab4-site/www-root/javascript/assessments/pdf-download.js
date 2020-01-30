
jQuery(document).ready(function ($) {

    /**
     * Open the download PDF(s) modal.
     */
    $(".generate-pdf-btn").on("click", function (e) {
        e.preventDefault();
        if ($("#generate-pdf-download-wait").hasClass("hide")) {
            $("#no-generate-selected").addClass("hide");
            $("#generate-error").addClass("hide");
            $("#generate-success").addClass("hide");
            $("#generate-pdf-details-section").addClass("hide");
            $("#generate-pdf-details-section-loading").addClass("hide");
            $("#generate-pdf-modal-confirm").addClass("hide");
        }
        var button = $(this);
        var parent_ref = button.parent().parent();
        if (typeof $(parent_ref).attr("id") === "undefined") {
            $(parent_ref).attr("id", "pdf-download-placeholder-id");
        }

        var parent_id_container = $("input[name='parent_id_container']").attr("id");
        if (parent_id_container){
            var parent_id = parent_id_container;
        } else {
            var parent_id = button.parent().parent().attr("id");
        }
        var checkbox_selector = "#" + parent_id + " input[type='checkbox']:checked.generate-pdf";
        var none_selected = true;
        var assessments = [];

        $("#generate-pdf-modal").removeClass("hide");
        $("#generate-pdf-modal").modal("show");

        // Populate the table, and set download as one file checkbox
        $(checkbox_selector).each(function (i, v) {
            none_selected = false;

            // Build a list of assessments to fetch information for
            assessments.push({
                "dassessment_id": $(v).data("dassessment-id"),
                "aprogress_id": $(v).data("aprogress-id")
            });
        });

        if (none_selected) {
            $("#no-generate-selected").removeClass("hide");
        } else {
            if ($("#generate-pdf-download-wait").hasClass("hide")) {
                $("#generate-pdf-details-section-loading").removeClass("hide");
                $("#generate-pdf-modal-confirm").removeClass("hide");

                // AJAX fetch the target information for the selected assessments
                $.ajax({
                    url: ENTRADA_URL + "/assessments?section=api-tasks",
                    data: {
                        "method": "get-assessments-metadata",
                        "assessments": assessments
                    },
                    type: "POST",
                    success: function (data) {
                        $("#generate-pdf-details-section-loading").addClass("hide");
                        var jsonResponse = safeParseJson(data, assessments_index.default_error_message);
                        if (jsonResponse.status === "success") {
                            $("#generate-pdf-modal-targets-list").html("");
                            $("#generate-pdf-details-section").removeClass("hide");
                            // iterate through the usable assessments metadata and populate the delivery/target/assessor information
                            $.each(jsonResponse.data, function (key, value) {
                                for (var proxy_id in value.assessor) {
                                    var assessor_name = value.assessor[proxy_id];
                                }
                                for (var atarget_id in value.targets) {
                                    $("#generate-pdf-modal-targets-list").append("<tr></tr>");
                                    var target_tr = $("#generate-pdf-modal-targets-list tr:last");
                                    target_tr.append("<td>" + assessor_name + "</td>"); // should only be one assessor
                                    target_tr.append("<td>" + value.targets[atarget_id] + "</td>");
                                    target_tr.append("<td>" + value.delivery_date_formatted + "</td>");
                                }
                            });
                            // Append the json directly the the form that we will be submitting.
                            var encoded_task_data = encodeURIComponent(JSON.stringify(jsonResponse.data));

                            $("#generate-pdf-modal-form").append("<input type='hidden' name='method' class='hide' value='generate-pdf' />");
                            $("#generate-pdf-modal-form").append("<input type='hidden' name='task_data' class='hide' value=\"" + encoded_task_data + "\"/>");

                        } else {
                            $("#generate-error").removeClass("hide");
                            display_error(jsonResponse.data, "#generate-error");
                        }
                    }
                });
            }
        }
    });

    /**
     * Download PDF confirmation.
     */
    $("#generate-pdf-modal-confirm").on("click", function (e) {
        e.preventDefault();
        var prop_value = $("#pdf_individual_option").is(":checked") ? 1 : 0;
        // Add the option of whether to download multiple files as one via ZIP or aggregated PDF
        $("#generate-pdf-modal-form").append("<input type='hidden' name='pdf_individual_option' class='hide' value='" + prop_value + "'/>");

        // Create a token to use to track the download.
        var pdf_download_token = Math.random().toString(36) + createUnixTimestamp();
        pdf_download_token = encodeURIComponent(pdf_download_token);

        // Set a cookie with the download token to determine when to close the DL window.
        //createCookie("assessment-pdf-download", pdf_download_token, 1);
        $("#generate-pdf-modal-form").append("<input type='hidden' name='pdf_download_token' class='hide' value='" + pdf_download_token + "'/>");

        // Check local cookie to see if the server has finished.
        check_download_status(pdf_download_token);

        $("#generate-pdf-modal-confirm").addClass("disabled");
        $("#generate-pdf-modal-confirm").prop("disabled", true);
        $("#generate-pdf-download-wait").removeClass("hide");

        // Scroll to the bottom of modal after click
        $("#generate-pdf-modal .modal-body").animate({scrollTop: $("#generate-pdf-modal .modal-body").height()}, 0);

        $("#generate-pdf-modal-form").submit();
    });

    /**
     * Periodically check if the PDF download has finished yet.
     *
     * @param pdf_download_token
     */
    function check_download_status(pdf_download_token) {

        var cookie_data = readCookie("assessment-pdf-download");
        if (cookie_data !== pdf_download_token) {
            setTimeout(function () {
                check_download_status(pdf_download_token)
            }, 500);

        } else {
            // Dismiss the modal
            $("#generate-pdf-modal").modal("hide");
            $("#generate-pdf-download-wait").addClass("hide");
            $("#generate-pdf-modal-confirm").removeClass("disabled");
            $("#generate-pdf-modal-confirm").removeProp("disabled");
        }
    }
});