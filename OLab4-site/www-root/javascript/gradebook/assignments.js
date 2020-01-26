var ENTRADA_URL;

function loadCurriculumPeriods(ctype_id) {
    var updater = new Ajax.Updater('curriculum_type_periods', ENTRADA_URL + '/api/curriculum_type_periods.api.php',{
        method:'post',
        parameters: {
            'ctype_id': ctype_id
        },
        onFailure: function(transport){
            $('curriculum_type_periods').update(new Element('div', {'class':'display-error'}).update('No Periods were found for this Curriculum Category.'));
        }
    });
}

jQuery(document).ready(function($) {
    $('#allow_multiple_files').change(function () {
        jQuery('#num_files_allowed_wrapper').toggle(this.checked);
    });

    $('#notice_enabled').change(function () {
        jQuery('#notice-dates').toggle(this.checked);
    });

    $('.datepicker').datepicker({
        dateFormat: 'yy-mm-dd'
    });

    $(".timepicker").timepicker({
        showPeriodLabels: false
    });

    $('.add-on').on('click', function() {
        if ($(this).siblings('input').is(':enabled')) {
            $(this).siblings('input').focus();
        }
    });

    $('#allow_multiple_files').change(function() {
        jQuery('#num_files_allowed_wrapper').toggle(this.checked);
    });

    $('#notice_enabled').change(function() {
        jQuery('#notice-dates').toggle(this.checked);
    });

    $("#due_finish").closest("td").hide();
});