/*
 * Feedback sidebar widget
 */

var shown = false;
var submitted = false;

function submitFeedback() {
	return true;
}

function removeFeedbackForm() {
	jQuery("#feedback-form-container").slideUp(function(){
		jQuery(this).remove()
	});
	shown = false;
}

jQuery(function(){
	jQuery("#feedback-widget").parent().css("overflow","visible");

	jQuery("#feedback-widget .menu li a").on("click", function(){
		if (shown == false) {
			shown = true;
			
			var who = jQuery(this).parent().attr("class");
			var container = jQuery("<div id=\"feedback-form-container\" />");
			var url = jQuery(this).attr("href");
			jQuery.ajax({
				url: url,
				type: 'POST',
				data: "who="+who+"&enc="+jQuery("#feedback-widget ul.feedback").attr("data-enc"),
				async: false,
				success: function (data) {
					container.html("<div class='cornerarrow'></div>"+data);
				}
			});

			container.appendTo(jQuery("#feedback-widget"));
			jQuery("#feedback-form-container").animate({
				left: jQuery(this).parent().width() + 22
			}, 200);
			
		} else {
			removeFeedbackForm();
		}
		return false;
	});
	jQuery("#feedback-widget").on("click", "#feedback-form input[value=Close]", function() {
		removeFeedbackForm()
		return false;
	});
	jQuery("#feedback-widget").on("click", "#feedback-form input[value=Submit]", function() {
		jQuery("#feedback-form").hide();
		jQuery("#feedback-form-container").append("<div class=\"loading\">Loading</div>");
		if (submitted == false) {
			var action		= jQuery("#feedback-form").attr("action");
			var method		= jQuery("#feedback-form").attr("method");
			var form_data	= jQuery("#feedback-form").serialize();

			jQuery.ajax({
				url: action,
				type: method,
				data: form_data,
				async: false,
				success: function (data) {
					jQuery("#feedback-form-container .loading").remove();
					jQuery("#feedback-form").fadeOut(500, function(){
						jQuery("#feedback-form").html(data);
						jQuery("#feedback-form").fadeIn();
						setTimeout("removeFeedbackForm()", 5000);
					});
				}
			});
			submitted = true;
		}
		return false;
	});
});