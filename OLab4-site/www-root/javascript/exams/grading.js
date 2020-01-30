// Does an HTTP POST to save the question associated with the given gradingBtn,
// showing an error or success Growl message.
function gradingSaveScore(gradingBtn) {
    if (gradingBtn.prop('disabled')) {
        return;
    }
    var responseId = gradingBtn.data('id');
    gradingBtn.prop('disabled', true);
    jQuery('#spinner_' + responseId).show();
    var comments = jQuery('#comments_' + responseId).val();
    var score = jQuery('#scores_' + responseId).val();
    var correct = [];
    jQuery('.correct-' + responseId).each(function() { correct.push(JSON.parse($(this).value)); });
    var url = ENTRADA_URL + '/admin/exams/grade?section=api-grade&post_id=' + EXAM_POST_ID;
    var submission = {
        'exam_progress_response_id': responseId,
        'comments': comments,
        'score': score,
        'correct': correct
    };
    jQuery.post(url, submission, function(jsonData) {
        gradingBtn.prop('disabled', false);
        jQuery('#spinner_' + responseId).hide();
        var data = JSON.parse(jsonData);
        if (data.messageType && data.message) {
            if ('success' === data.messageType) {
                jQuery.growl({'title': 'Success', 'message': data.message});
                jQuery('#scores_' + responseId).val(data.questionScore);
                jQuery('#student_overall_score').text(data.examScore);
                gradingBtn.parents('.grading-wrapper').addClass('graded');
                if (data.correct) {
                    for (var i = 0; i < data.correct.length; i++) {
                        var correct = data.correct[i];
                        var html;
                        if (0 === correct.answers.length) {
                            html = '<em>none</em>';
                        } else {
                            html = correct.answers.join(' <em>or</em> ');
                        }
                        jQuery('#correct_answers_' + correct.qanswer_id).html(html);
                    }
                }
            } else if ('error' === data.messageType) {
                jQuery.growl.error({'title': 'Error', 'message': data.message});
            }
        }
    });
}

// Attach event listeners
jQuery(document).ready(function($) {
    $('.grading-mark-correct').on('click', function(e) {
        e.preventDefault();
        var answerId = $(this).data('id');
        var hiddenField = $('#correct-' + answerId);
        var hiddenVal = JSON.parse(hiddenField.val());
        hiddenVal.correct = true;
        hiddenField.val(JSON.stringify(hiddenVal));
        var toggleField = $('#grading-mark-toggle-' + answerId);
        toggleField.html('<i style="color: #50bc00" class="fa fa-check"></i>')
    });
    $('.grading-mark-incorrect').on('click', function(e) {
        e.preventDefault();
        var answerId = $(this).data('id');
        var hiddenField = $('#correct-' + answerId);
        var hiddenVal = JSON.parse(hiddenField.val());
        hiddenVal.correct = false;
        hiddenField.val(JSON.stringify(hiddenVal));
        var toggleField = $('#grading-mark-toggle-' + answerId);
        toggleField.html('<i style="color: #d9534f" class="fa fa-close"></i>')
    });

    $('#grading-save-all-btn').on('click', function() {
        $('.grading-save-btn').each(function() {
            gradingSaveScore($(this));
        });
    });
    $('.grading-save-btn').on('click', function() {
        gradingSaveScore($(this));
    });
    
    var sidebarDropdownLink = $('#grading-sidebar-dropdown-link');
    var sidebarDropdownContent = $('#grading-sidebar-dropdown-content');
    sidebarDropdownLink.on('click', function() {
        sidebarDropdownContent.toggle();
        if (sidebarDropdownContent.is(':visible')) {
            sidebarDropdownLink.children('i').removeClass('fa-chevron-down');
            sidebarDropdownLink.children('i').addClass('fa-chevron-up');
        } else {
            sidebarDropdownLink.children('i').removeClass('fa-chevron-up');
            sidebarDropdownLink.children('i').addClass('fa-chevron-down');
        }
        return false;
    });
});
