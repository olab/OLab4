jQuery(document).ready(function ($) {
    var timeout;
    var table_list = [
        "stage-container"
    ];
    var spinner_div = jQuery(document.createElement("div")).addClass("spinner-container");
    var loading_tasks = jQuery(document.createElement("h3")).html("Loading EPAs...");
    var spinner = jQuery(document.createElement("img")).attr({
        "class": "loading_spinner",
        "src": ENTRADA_URL + "/images/loading.gif"
    });
    $(document).tooltip({
        selector: ".assessment-scale"
    });

    /**
     * Event listener for the show more buttons
     */
    $(".show-more-btn").on("click", function (e) {
        if ($(this).hasClass("active")) {
            $(this).removeClass("active").parent().siblings().not(".visible").addClass("hide");
            $(this).find("a").html(cbme_progress_dashboard.show_more);
        } else {
            $(this).addClass("active").parent().siblings().removeClass("hide");
            $(this).find("a").html(cbme_progress_dashboard.show_less);
        }
        e.preventDefault();
    });

    /**
     * Event listener for the hide/show EPAs buttons
     */
    $(".stage-toggle, .stage-container h2, .epa-progress-toggle").on("click", function (e) {
        setTimeout(function() {
            stage_epa_load(139);
        }, 50);

        var preference = "expanded";
        var stage = $(this).attr("data-stage");
        var show_more_btn = $(".show-more-btn");

        if (show_more_btn.hasClass("active")) {
            show_more_btn.removeClass("active").siblings().not(".visible").addClass("hide");
            show_more_btn.find("a").html(cbme_progress_dashboard.show_more);
        }

        if ($("#" + stage).hasClass("collapsed")) {
            $("#" + stage).show();
            $("#" + stage + "-show-hide").removeClass("fa-angle-down").addClass("fa-angle-up");
            $("#" + stage).removeClass("collapsed");
            $("#" + stage).slideDown(200);
        } else {
            $("#" + stage + "-show-hide").removeClass("fa-angle-up").addClass("fa-angle-down");
            $("#" + stage).addClass("collapsed");
            $("#" + stage).slideUp(200);
        }

        if ($(this).parent().find(".stage-toggle").hasClass("collapsed")) {
            $(this).parent().find(".stage-toggle").removeClass("collapsed");
            $(this).parent().find(".stage-toggle-label").html(cbme_progress_dashboard.hide);
            $(this).parent().find(".epa-container").slideDown(200);
        } else {
            preference = "collapsed";
            $(this).parent().find(".stage-toggle").addClass("collapsed");
            $(this).parent().find(".stage-toggle-label").html(cbme_progress_dashboard.show);
            $(this).parent().find(".epa-container").slideUp(200);
        }

        set_view_preference(preference, stage);
        e.preventDefault();
    });

    /**
     * Handle resizing the EPA list items in the Dashboard
     */
    stage_epa_load(139);
    $(window).on("resize", function () {
        stage_epa_load(139);
    });

    $(".show-more-btn").on("click", function() {
        stage_epa_load(139);
    });

    function set_view_preference(preference, stage) {
        $.ajax({
            url: ENTRADA_URL + "/assessments?section=api-assessments",
            type: "POST",
            data: {"method": "set-epa-view-preference", "preference": preference, "stage": stage}
        });
    }

    function stage_epa_load(padding_width) {
        $(".list-set-item").each(function() {
            var epa_cell_width = $(this).find(".list-set-item-epa span").outerWidth(),
                count_cell_width = $(this).find(".list-set-item-assessment-count span").outerWidth(),
                status_cell_width = $(this).find(".list-set-item-status span").outerWidth();

            var list_set = $(this).closest(".list-set").outerWidth(),
                epa_list_item_width_total = epa_cell_width + count_cell_width + status_cell_width + padding_width - 50;

            if ($(".epa-description-block").length == 0) {
                $(this).find(".list-set-item-epa-description-cell").css("width", list_set - epa_list_item_width_total + "px");
                $(this).find(".list-set-item-epa-description").css("width", list_set - epa_list_item_width_total + "px");
            }
        });
    }

    $(document).on('click', function (e) {
        $('.rotation-schedule').each(function () {
            if (!$(this).is(e.target) && $(this).has(e.target).length === 0 && $('.popover').has(e.target).length === 0) {
                (($(this).popover('hide').data('bs.popover')||{}).inState||{}).click = false;
                $('.rotation-schedule').css("display", "block");
                $('.fc-event').css("display", "block");
            }
        });
    });

    $(document).on("click", ".fc-event", function (e) {
        if($(this).hasClass("rotation-schedule")) {
            $(".popover-content").empty();
            $(".popover").remove();
            $("[rel=\"popover\"]").popover({
                animation: true,
                container: "body",
                selector: "[rel=\"popover\"]",
                html: true,
                trigger: "focus",
                placement: "top",
                title: function () {
                    return $(this).find("fc-title").context.innerText;
                },
                content: function () {
                    var response;
                    $.ajax({
                        url: ENTRADA_URL + "/api/api-schedule.api.php?method=get-schedule-objectives&schedule_parent_id=" + $(this).attr("data-schedule-id"),
                        type: "GET"
                    }).done(function (data) {
                        var jsonResponse = safeParseJson(data, "");
                        if (typeof jsonResponse.data !== 'undefined') {
                            if (jsonResponse.data) {
                                if (jsonResponse.status === 'success') {
                                    response = jsonResponse.data;
                                    var epa_list = "<span>" + schedule_localization.title + "</span>";
                                    epa_list += "<div class='scroll-y'>";
                                    epa_list += "<table class='table table-bordered full-width'>";
                                    epa_list += "<thead>";
                                    epa_list += "<tr>";
                                    epa_list += "<th>" + schedule_localization.objective_heading + "</th>";
                                    epa_list += "<th>" + schedule_localization.likelihood_heading + "</th>";
                                    epa_list += "<th>" + schedule_localization.priority_heading + "</th>";
                                    epa_list += "</tr>";
                                    epa_list += "</thead>";
                                    for (var i = 0; i < response.length; i++) {
                                        epa_list += "<tr>";
                                        epa_list += "<td class='epa-column epa-objective-name' data-toggle='tooltip' title='" + response[i].objective_name + "'>" + response[i].objective_code + "</td>";
                                        if (response[i].likelihood_id === '1') {
                                            epa_list += "<td class='epa-column'>";
                                            epa_list += "<span>" + schedule_localization.unlikely + "</span>";
                                            epa_list += "</td>";
                                        }
                                        if (response[i].likelihood_id === '2') {
                                            epa_list += "<td class='epa-column'>";
                                            epa_list += "<span>" + schedule_localization.likely + "</span>";
                                            epa_list += "</td>";
                                        }
                                        if (response[i].likelihood_id === '3') {
                                            epa_list += "<td class='epa-column'>";
                                            epa_list += "<span>" + schedule_localization.very_likely + "</span>";
                                            epa_list += "</td>";
                                        }
                                        if (response[i].likelihood_id !== '3' && response[i].likelihood_id !== '2' && response[i].likelihood_id !== '1') {
                                            epa_list += "<td></td>";
                                        }
                                        epa_list += "<td class='epa-column text-center priority-column' style='padding: 3px'>";
                                        if (response[i].priority === '1') {
                                            epa_list += "<i class='fa fa-exclamation-circle blue-cbme-icon' data-toggle='tooltip' title='" + schedule_localization.priority_tooltip + "'></i>";
                                        } else {
                                            epa_list += "<i class='fa fa-exclamation-circle grey-cbme-icon' data-toggle='tooltip' title='" + schedule_localization.not_priority_tooltip + "'></i>";
                                        }
                                        epa_list += "</td>";
                                        epa_list += "</tr>";
                                    }
                                    epa_list += "</table>";
                                    epa_list += "</div>";
                                    $(".popover-content").html(epa_list);
                                } else {
                                    $(".popover-content").html("<div class='full-width text-center'>" + jsonResponse.data + "</div>");
                                }
                            } else {
                                $(".popover-content").html("<span>" + schedule_localization.no_results + "</span>");
                            }
                        }
                    });
                }
            });
            $(this).popover("toggle");
            $(".popover-content").html(
                "<div class='full-width text-center'>" +
                "<img class='loading_spinner space-below' src='" + ENTRADA_URL + "/images/loading.gif'/><br/>" +
                "<span>" + schedule_localization.loading_message + "</span>" +
                "</div>");
            $('.rotation-schedule').css("display", "block");
        }
    });


    $(document).on("click", ".list-set-item-status", function(e) {
        e.preventDefault();
        var objective_id = parseInt($(this).data("id"));
        var objective_set = $("#span-toggle-objective-" + objective_id).data("objective-set");
        var course_id;
        if (typeof($('#cbme-course-picker').find(':selected').val()) === 'undefined') {
            course_id = $('#course-id').val();
        } else {
            course_id = $('#cbme-course-picker').find(':selected').val();
        }

        if (objective_id < 1) {
            return;
        }

        var proxy_id = $("[name=\"proxy_id\"]").val();
        $("#objective-status-toggle-objective-id").val(objective_id);
        $("#objective-status-toggle-objective-set").val(objective_set);
        $("#epa-status-history-link").attr("href", ENTRADA_URL + "/assessments/learner/cbme/epastatushistory?proxy_id=" + proxy_id + "&objective_id=" + objective_id + "&course_id=" + course_id);

        if ($(this).hasClass("list-item-status-complete")) {
            $("#reason-label").addClass("form-required");
            $("#reason-label-optional").addClass("hide");
            $("#objective-status-toggle-action").val("incomplete");
            $("#objective-status-toggle-h3-incomplete").removeClass("hide");
            $("#objective-status-toggle-h3-complete").addClass("hide");
        } else {
            $("#reason-label").removeClass("form-required");
            $("#reason-label-optional").removeClass("hide");
            $("#objective-status-toggle-action").val("complete");
            $("#objective-status-toggle-h3-incomplete").addClass("hide");
            $("#objective-status-toggle-h3-complete").removeClass("hide");
        }

        $("#modal-objective-status-toggle").modal("show");
    });

    function clear_objective_status_toggle_fields() {
        $("#objective-status-toggle-objective-id").val('');
        $("#objective-status-toggle-reason").val('');
    }

    $(".clear-objective-status-toggle-fields").on("click", function() {
        clear_objective_status_toggle_fields();
    });

    /**
     * Handle the completion status update
     */
    $("#objective-status-toggle-confirm").on("click", function(e) {
        e.preventDefault();
        var objective_id = $("#objective-status-toggle-objective-id").val();
        var proxy_id = $("#objective-status-toggle-proxy-id").val();
        var action = $("#objective-status-toggle-action").val();
        var reason = $("#objective-status-toggle-reason").val();
        var objective_set = $("#objective-status-toggle-objective-set").val();
        var course_id;
        if (typeof($('#cbme-course-picker').find(':selected').val()) === 'undefined') {
            course_id = $('#course-id').val();
        } else {
            course_id = $('#cbme-course-picker').find(':selected').val();
        }

        var update_request = $.ajax({
            url: ENTRADA_URL + "/assessments?section=api-assessments",
            type: "POST",
            data: {
                "method": "update-objective-completion-status",
                "action": action,
                "proxy_id": proxy_id,
                "course_id": course_id,
                "objective_id": objective_id,
                "reason": reason,
                "objective_set": objective_set
            }
        });

        $.when(update_request).done(function (data) {
            $("#modal-objective-status-toggle").modal("hide");
            var jsonResponse = safeParseJson(data, "Invalid Json");
            if (jsonResponse.status == "success") {
                if (jsonResponse.action == "complete") {
                    $("#span-toggle-objective-" + objective_id).addClass("fa-check-circle-o");
                    $("#span-toggle-objective-" + objective_id).removeClass("fa-circle-o");
                    $("#span-toggle-objective-" + objective_id).closest("a").addClass("list-item-status-complete");
                    $("#span-toggle-objective-" + objective_id).closest("a").removeClass("list-item-status-incomplete");
                } else {
                    $("#span-toggle-objective-" + objective_id).removeClass("fa-check-circle-o");
                    $("#span-toggle-objective-" + objective_id).removeClass("item-complete");
                    $("#span-toggle-objective-" + objective_id).addClass("fa-circle-o");
                    $("#span-toggle-objective-" + objective_id).closest("a").removeClass("list-item-status-complete");
                    $("#span-toggle-objective-" + objective_id).closest("a").addClass("list-item-status-incomplete");

                }
                var text = (action == "complete" ? cbme_learner_progress_dashboard.completed : cbme_learner_progress_dashboard.in_progress);
                $("#span-toggle-objective-" + objective_id).attr("data-original-title", text);
            }

            $.animatedNotice(jsonResponse.data, jsonResponse.status, {"resourceUrl": ENTRADA_URL});
        });

        clear_objective_status_toggle_fields();
    });

  $(document).on("click", ".list-set-item-assessment-count", function() {
      /**
       * Use our localized strings
       * @type {string}
       */
      var replacement_string = "%s";
      var localized_modal_title = cbme_progress_dashboard.breakdown_title.replace(
          replacement_string,
          $(this).attr("data-html")
          );
      var localized_assessment_total = cbme_progress_dashboard.assessment_total.replace(
          replacement_string,
          $(this).attr("data-id")
      );
      $(".assessment-count").html(localized_assessment_total);
      $(".breakdown-title").html(localized_modal_title);
      $(".rating-scales").empty();
      var course_id;
      if (typeof($('#cbme-course-picker').find(':selected').val()) === 'undefined') {
          course_id = $('#course-id').val();
      } else {
          course_id = $('#cbme-course-picker').find(':selected').val();
      }
      $.ajax({
          url: ENTRADA_URL + "/assessments?section=api-assessments&method=get-assessment-stage-data&objective_id=" + $(this).attr("objective-id") + "&course_id=" + course_id + "&proxy_id=" + $("#proxy-id").val(),
          type: "GET",
          beforeSend: function () {
              $(".modal-body-contents").empty();
              $("#loading-spinner").removeClass("hide");

          },
          complete: function () {
              $("#loading-spinner").addClass("hide");
          }
      }).done(function (data) {
          var jsonResponse = safeParseJson(data, cbme_progress_dashboard.error_message);
          if (typeof jsonResponse.data !== 'undefined') {
              if (jsonResponse.data) {
                  if (jsonResponse.status === 'success') {
                      var response = jsonResponse.data;
                      for (var i=0; i < response.length; i++) {
                          var response_array = [];
                          var assessment_total = 0;
                          var response_1_count = 0;
                          var response_2_count = 0;
                          var response_3_count = 0;
                          var response_4_count = 0;
                          var response_5_count = 0;
                          var response_6_count = 0;
                          var unique_scales = 1;
                          $(".modal-body-contents").append("<div class='assessment-container-" + i + " bottom-border small-padding space-below'>");
                          $(".assessment-container-" + i).append("<h3 class='no-margin form-title'>" + response[i][0].form_type + " (" + response[i].length + ")" + "</h3>");
                          //Look for all of the unique rating scales in the data set.
                          for (var a = 0; a < response[i].length; a++) {
                               if (response[i][a + 1]) {
                                  if (response[i][a + 1].rating_scale_title !== response[i][a].rating_scale_title) {
                                    // We have another scale
                                    unique_scales ++;
                                  }
                              }
                          }
                          // Tally the responses into response count buckets for displaying
                          for (var k = 0; k < response[i].length; k++) {
                              if (response[i][k].rating_scale_title !== "N/A") {
                                  switch (response[i][k].selected_iresponse_order) {
                                      case "1" :
                                          response_1_count++;
                                          break;
                                      case "2" :
                                          response_2_count++;
                                          break;
                                      case "3" :
                                          response_3_count++;
                                          break;
                                      case "4" :
                                          response_4_count++;
                                          break;
                                      case "5" :
                                          response_5_count++;
                                          break;
                                      case "6" :
                                          response_6_count++;
                                          break;
                                  }
                                  if (response[i][k + 1]) {
                                      if (response[i][k + 1].rating_scale_title !== response[i][k].rating_scale_title) {
                                          response_array.push([response_1_count, response_2_count, response_3_count, response_4_count, response_5_count, response_6_count]);
                                      }
                                  } else {
                                      response_array.push([response_1_count, response_2_count, response_3_count, response_4_count, response_5_count, response_6_count]);
                                  }
                              }
                          }
                          // Go through all of the unique rating scales and output the tally of each response.
                          for (var b = 0; b < unique_scales ; b++) {
                              assessment_total = 0;
                              $(".assessment-container-" + i).append("<span>" + response[i][b].rating_scale_title + "</span>");
                              if (response[i][b].rating_scale_title !== "N/A") {
                                  $(".assessment-container-" + i).append("<span class='assessment-total-" + i +"-" + b + "'></span>");
                                  $(".assessment-container-" + i).append("<br>");
                              }
                              for (var p = 0; p < response[i][b].rating_scale_responses.length; p++) {
                                  assessment_total += response_array[b][p];
                                  var icon_class = response_array[b][p] === 0 ? "grey-icon" : "rating-scale-count-active";
                                  $(".assessment-total-" + i + "-" + b).html(" (" + assessment_total + ")");
                                  $(".assessment-container-" + i).append("<span class='scale-wrap assessment-scale-wrap'><span style='display: inline-block !important;' data-toggle='tooltip' class='" + icon_class + " space-right assessment-scale' title='" + response[i][b].rating_scale_responses[p].text + "'>" + response_array[b][p] + "</span></span>");
                              }
                              $(".assessment-container-" + i).append("<br>");
                              $(".assessment-container-" + i).append("</div>");
                          }
                      }
                  } else {
                      $(".form-title").empty();
                      $(".modal-body-contents").html("<h3 class='no-margin form-title small-padding'>" + jsonResponse.data[0] + "</h3>");
                  }
              }
          }
      });
    });

    $(".submit-progress-change").on("click", function(e) {
        e.preventDefault();
        var objective_id = $("[name=\"objective_id\"]").val();
        var proxy_id = $("[name=\"proxy_id\"]").val();
        var action = $("[name=\"action\"]").val();
        var reason = $("[name=\"reason\"]").val();
        var objective_set = $("[name=\"objective_set\"]").val();
        var course_id;
        if (typeof($('#cbme-course-picker').find(':selected').val()) === 'undefined') {
            course_id = $('#course-id').val();
        } else {
            course_id = $('#cbme-course-picker').find(':selected').val();
        }

        $.ajax({
            url: ENTRADA_URL + "/assessments?section=api-assessments",
            type: "POST",
            data: {
                "method": "update-objective-completion-status",
                "action": action,
                "course_id": course_id,
                "proxy_id": proxy_id,
                "objective_id": objective_id,
                "reason": reason,
                "objective_set": objective_set
            }
        });

    });

    /**
     * Toggles EPA card details when details icon is clicked
     */
  $(".assessment-breakdown-drawer").on("click", function (e) {
      e.preventDefault();
      $(this).toggleClass("active");
      var drawer_toggle = $(this);
      var proxy_id = $(this).attr("data-proxy-id");
      var course_id = $(this).attr("data-course-id");
      var objective_id = $(this).attr("data-objective-id");

      if ($(this).hasClass("active")) {
          if ($("#assessment-data-container-"+objective_id + " li").length == 0) {
              var update_request = $.ajax({
                  url: ENTRADA_URL + "/assessments?section=api-assessments",
                  type: "GET",
                  data: {
                      "method": "get-assessment-breakdown-data",
                      "proxy_id": proxy_id,
                      "course_id": course_id,
                      "objective_id": objective_id,
                      "filters": null,
                      "limit": 100,
                      "pinned_only": false
                  },
                  complete: function () {
                      drawer_toggle.parent().find(".assessment-breakdown-spinner").addClass("hide");
                  },
                  beforeSend: function () {
                      drawer_toggle.parent().find(".assessment-breakdown-spinner").removeClass("hide");
                  }
              });
              $.when(update_request).done(function (data) {
                  var jsonResponse = safeParseJson(data, cbme_progress_dashboard.error_message);
                      if (jsonResponse.status === 'success') {
                          if (jsonResponse.data) {
                          var response = jsonResponse.data;
                          var accumulated_height = 0;
                          for (var i = 0; i < response.length; i++) {
                              if (response[i].rating_scale_title !== "N/A") {
                                  $("<li/>").loadTemplate(
                                      "#list-assessment-template", {
                                          title: response[i].title,
                                          assessment_total: response[i].number_of_assessments,
                                          scale_wrap_class: "scale-wrap-" + i,
                                          assessment_progress: JSON.stringify(response[i].progress),
                                          assessment_form_ids: JSON.stringify(response[i].form_ids),
                                          rating_scale_ids: JSON.stringify(response[i].rating_scale_ids),
                                      }
                                  ).appendTo("#assessment-data-container-" + objective_id).addClass("assessment-list-item padding-top padding-bottom");

                                  for (var j = 0; j < response[i].rating_scale_length; j++) {
                                      var temp_span = $("<span>").loadTemplate(
                                          "#assessment-count-template", {
                                              assessment_class: response[i].tallied_responses[j].value === 0 ? "grey-icon" : "rating-scale-count-active",
                                              assessment_count: response[i].tallied_responses[j].value,
                                              assessment_tooltip: response[i].tallied_responses[j].text,
                                              assessment_iresponses: JSON.stringify(response[i].tallied_responses[j].iresponse_ids)
                                          }
                                      );
                                      $("#assessment-data-container-" + objective_id + " .scale-wrap-" + i).append(temp_span.html());
                                      temp_span.remove();
                                  }
                              } else {
                                  $("<li/>").loadTemplate(
                                      "#no-scale-template", {
                                          title: response[i].title,
                                          assessment_total: response[i].number_of_assessments,
                                          assessment_progress: JSON.stringify(response[i].progress),
                                          assessment_form_ids: JSON.stringify(response[i].form_ids),
                                          rating_scale_ids: JSON.stringify(response[i].rating_scale_ids),
                                      }
                                  ).appendTo("#assessment-data-container-" + objective_id).addClass("assessment-list-item padding-top padding-bottom");
                              }
                              accumulated_height += 40;
                          }
                          $("#assessment-data-container-" + objective_id).css("height", accumulated_height);
                      }
                  } else {
                      $("#assessment-data-container-" + objective_id).html("<li><div class='text-center padding-top padding-bottom'>" + cbme_progress_dashboard.no_assessments + "</div></li>")
                  }
                  drawer_toggle.parent().parent().parent().find(".assessment-details-container").slideDown("fast");
              });
          } else {
              drawer_toggle.parent().parent().parent().find(".assessment-details-container").slideDown("fast");
          }
      } else {
          drawer_toggle.parent().parent().parent().find(".assessment-details-container").slideUp("fast");
      }
  });

  $(document).on("click", ".list-header", function () {
      var form_ids = $(this).closest("div.list-assessment-wrapper").data("form-ids");
      var progress = $(this).closest("div.list-assessment-wrapper").data("progress");
      var rating_scale_ids = $(this).closest("div.list-assessment-wrapper").data("rating-scale-ids");
      var proxy_id = $("#proxy-id").val();

      var parameters_string = "&target_type=proxy_id&target_id=" + proxy_id;

      $.each(form_ids, function (i, v) {
          parameters_string += "&form_ids[]=" + v;
      });

      $.each(rating_scale_ids, function (i, v) {
          parameters_string += "&rating_scale_ids[]=" + v;
      });

      $.each(progress, function (i, v) {
          parameters_string += "&progress_ids[]=" + v;
      });

      window.open(ENTRADA_URL + "/cbme/report?" + parameters_string, "_blank");
  });

  $(document).on("click", "span.assessment-scale.rating-scale-count-active", function () {
      var form_ids = $(this).closest("div.list-assessment-wrapper").data("form-ids");
      var progress = $(this).closest("div.list-assessment-wrapper").data("progress");
      var rating_scale_ids = $(this).closest("div.list-assessment-wrapper").data("rating-scale-ids");
      var iresponses = $(this).data("iresponses");
      var proxy_id = $("#proxy-id").val();

      var parameters_string = "&target_type=proxy_id&target_id=" + proxy_id;

      $.each(form_ids, function (i, v) {
          parameters_string += "&form_ids[]=" + v;
      });

      $.each(rating_scale_ids, function (i, v) {
          parameters_string += "&rating_scale_ids[]=" + v;
      });

      $.each(progress, function (i, v) {
          parameters_string += "&progress_ids[]=" + v;
      });

      $.each(iresponses, function (i, v) {
          parameters_string += "&iresponse_ids[]=" + v;
      });

      window.open(ENTRADA_URL + "/cbme/report?" + parameters_string, "_blank");
  });

});
