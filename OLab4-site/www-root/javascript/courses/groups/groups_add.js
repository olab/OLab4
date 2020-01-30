var timeout;

jQuery(document).ready(function ($) {

    get_enrolment();

    $("#cperiod_select").on("change", function(e) {
        if ($('#group_type_populated').is(':checked')) {
            $(".v-divider").slideUp();
        }
        $(".enrolment_groups").html("");
        $("#total_students").html("");
        $("#enrolment_name").html("");
        get_enrolment();
    });
    
    $("input:radio[name=group_type]").on("click", function(e) {
        if ($(this).val() == "populated") {
            $(".v-divider").slideDown();
        } else {
            $(".v-divider").slideUp();
        }
    });

    $("input:radio[name=enrolment]").on("click", function(e) {
        if ($(this).val() == "part_enrolment") {
            $(".enrolment_groups").slideDown();
        } else {
            $(".enrolment_groups").slideUp();
        }
    });

    $("input:checkbox[name=gender]").on("click", function(e) {
        if (this.checked) {
            $(".gender-radio").slideDown();
        } else {
            $(".gender-radio").slideUp();
        }
    });

});

function get_enrolment () {
    var cperiod_id = jQuery("#cperiod_select").val();
    var course_id = jQuery("#course_id").val();

    //set the item type if we are building a rubric/grouped item
    var items = jQuery.ajax({
        url: "?section=api-enrolment",
        data: "method=get-enrolment&cperiod_id=" + cperiod_id + "&course_id=" + course_id,
        type: 'GET',
    });

    jQuery.when(items).done(function (data) {

        var jsonResponse = JSON.parse(data);
        if (jsonResponse.results > 0) {
            jQuery.each(jsonResponse.data.enrolment, function (key, item) {
                build_enrolment_row(item);
            });
            jQuery("#total_students").html("<b>"+jsonResponse.data.total_students+"</b>");
            jQuery("#enrolment_name").html("<b>"+jQuery("#cperiod_select option:selected").html()+"</b>");
            if (jQuery('#group_type_populated').is(':checked')) {
                jQuery(".v-divider").slideDown();
            }
        }
    });
}

function build_enrolment_row (enrolment) {

    var enrolment_input   = document.createElement("input");
    var enrolment_input_hidden   = document.createElement("input");
    var enrolment_span   = document.createElement("span");
    var enrolment_label   = document.createElement("label");


    jQuery(enrolment_input).attr({type: "checkbox", "class": "space-right", name: "part_enrolment_val[]", value: enrolment.type_value});
    jQuery(enrolment_input_hidden).attr({type: "hidden", name: "enrolment_type_"+enrolment.type_value, value: enrolment.type});
    jQuery(enrolment_label).addClass("checkbox");
    if (enrolment.total > 1) {
        jQuery(enrolment_span).html(enrolment.name+" - <b>"+enrolment.total+"</b> learners");
    } else {
        jQuery(enrolment_span).html(enrolment.name);
    }


    jQuery(enrolment_label).append(enrolment_input).append(enrolment_input_hidden).append(enrolment_span);
    jQuery(".enrolment_groups").append(enrolment_label);

}

function toggleGroupTextbox () {
    if ($('group_populate_group_size').checked) {
        $('group_size').show();
        $('group_size').focus();
        $('group_number').hide();
    } else {
        $('group_number').show();
        $('group_number').focus();
        $('group_size').hide();
    }
}