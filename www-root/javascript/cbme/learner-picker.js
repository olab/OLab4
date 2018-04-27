jQuery(document).ready(function ($) {

   $("#cbme-learner-picker").advancedSearch({
       api_url: ENTRADA_URL + "/assessments?section=api-assessments",
       resource_url: ENTRADA_URL,
       filter_component_label: cbme_translations.filter_component_label,
       filters: {
           learners: {
               label: cbme_translations.curriculum_period_filter_label,
               data_source: "get-learner-picker-data",
               secondary_data_source: "my-learners",
               mode: "radio"
           }
       },
       control_class: "learner-selector",
       parent_form: $("#learner-form"),
       no_results_text: cbme_translations.no_learners_found
   });

    $("#cbme-learner-picker").on("change", function () {
        var selected_learner_id = $(".learner-selector").val();
        set_learner_preference(selected_learner_id);
    });

    function set_learner_preference(proxy_id) {
        var learner_preference_request = $.ajax({
            url: ENTRADA_URL + "/assessments?section=api-assessments",
            type: "POST",
            data: {"method": "set-learner-preference", "proxy_id": proxy_id}
        });

        $.when(learner_preference_request).done(function () {
            var url = window.location.href.replace(/proxy_id=\d+/, "proxy_id="+proxy_id);
            window.location = url;
        });
    }

    $("#learner-form").on("click", ".search-target-children-toggle", function() {
        $.ajax({
            url: ENTRADA_URL + "/admin/rotationschedule?section=api-schedule",
            data: {
                method: "set-curriculum-period",
                cperiod_id: $(this).attr("data-id")
            },
            type: "POST"
        });
    });

});
