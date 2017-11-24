var grow;
function growPic(picture, unzoom) {
	if (picture.width == 72 && !grow) {
		$$('.zoom').each(function (e) { e.innerHTML = ''; });
		picture.style.zIndex = 7; 
		new Effect.Scale(picture, 300, 
		{
				scaleMode: 
				{ 
					originalHeight: 100, 
					originalWidth: 72 
				},
				afterFinish: function() {
					grow = true;
					unzoom.innerHTML = '-';
				}
		}); 
		return false;
	}
}

function shrinkPic(picture, unzoom) {
	if (picture.width > 72 && picture.style.zIndex > 5) {
		unzoom.innerHTML = '';
		new Effect.Scale(picture, 100, 
		{
			scaleFrom: (picture.width / 72 * 100), 
			scaleMode: 
			{ 
				originalHeight: 100, 
				originalWidth: 72 
			},
			afterFinish: function() {
				picture.style.zIndex = 5;
				$$('.zoom').each(function (e) { e.innerHTML = '+'; });
				grow = false;
			}
		});
		
		return false;
	}
}

function dataURItoBlob(dataURI) {
	var byteString = atob(dataURI.split(',')[1]);
	var ab = new ArrayBuffer(byteString.length);
	var ia = new Uint8Array(ab);
	for (var i = 0; i < byteString.length; i++) {
		ia[i] = byteString.charCodeAt(i);
	}
	return new Blob([ab], { type: 'image/jpeg' });
}

jQuery(document).ready(function ($) {

	$("#country_id").on("change", function() {
		getProvinces($(this).val());
	});
	
	
	function getProvinces(country_id) {
		var target_request = $.ajax({
			url: ENTRADA_URL+'/api/province.api.php',
			data: 'countries_id=' + country_id + '&prov_state=' + PROV_STATE,
			type: "GET",
			dataType: "html"
		});

		$.when(target_request).done(function (data) {
			$("#prov_state_div").html(data);
			if ($('#prov_state').prop('type') == 'select-one') {
				$('#prov_state_label').removeClass('form-nrequired');
				$('#prov_state_label').addClass('form-required');
				if (!data)
					$("#prov_state").selectedIndex = 0;

			} else {

				$('#prov_state_label').removeClass('form-required');
				$('#prov_state_label').addClass('form-nrequired');
				if (!data)
					$("#prov_state").html("");

			}
		});

	}
	if ($("#country_id").length) {
		getProvinces($("#country_id").val());
	}
});

jQuery(function(){

	jQuery("#update-pw").on("click", function() {
		jQuery.ajax({
			url : ENTRADA_URL + "/profile",
			data : "ajax_action=resetpw&" + jQuery("#update-pw-form").serialize(),
			type : "post",
			async : true,
			success : function(data) {
				var jsonResponse = JSON.parse(data);
				if (jsonResponse.status == "success") {
					jQuery("#password-change-modal").modal("hide");
					display_success(jsonResponse.data, "#msgs");
				} else {
					display_error(jsonResponse.data, "#pw-change-msg");
				}
			}
		});
	});

	jQuery("#password-change-modal").on("hide", function() {
		jQuery("#msgs").html("");
		jQuery("#pw-change-msg").html("");
		jQuery("#current_password, #new_password, #new_password_confirm").attr("value", "");
	});

	jQuery("#reset-hash").live("click", function() {
		jQuery.ajax({
			url : ENTRADA_URL + "/profile",
			data : "ajax_action=generatehash",
			type : "post",
			async : true,
			success : function(data) {
				var jsonResponse = JSON.parse(data);
				jQuery("#hash-value").html(jsonResponse.data);
			}
		});
		jQuery("#reset-hash-modal").modal("hide");
	});

	jQuery("#btn-toggle .btn").live("click", function() {
		var clicked = jQuery(this);
		if (!clicked.parent().hasClass(clicked.html().toLowerCase())) {
			jQuery.ajax({
				url : ENTRADA_URL + "/profile",
				data : "ajax_action=togglephoto",
				type : "post",
				async : true,
				success : function(data) {
					var jsonResponse = JSON.parse(data);
					jQuery("#profile-image-container span img").attr("src", jsonResponse.data.imgurl);
					jQuery("#btn-toggle .btn.active").removeClass("active");
					clicked.addClass("active");
					clicked.parent().removeClass((jsonResponse.data.imgtype == "uploaded" ? "official" : "uploaded")).addClass(jsonResponse.data.imgtype);
				}
			});
		}
		return false;
	});

	function selectImage(image){
		jQuery(".description").hide();
		var image_width;
		var image_height;
		var w_offset;
		var h_offset

		image_width = image.width();
		image_height = image.height();
		w_offset = parseInt((image_width - 150) / 2);
		h_offset = parseInt((image_height - 150) / 2);

		jQuery("#coordinates").attr("value", w_offset + "," + h_offset + "," + (w_offset + 150) + "," + (h_offset + 150));
		jQuery("#dimensions").attr("value", image_width + "," + image_height)

		image.imgAreaSelect({
			aspectRatio: '98:98',
			handles: true,
			x1: w_offset, y1: h_offset, x2: w_offset + 150, y2: h_offset + 150,
			instance: true,
			persistent: true,
			onSelectEnd: function (img, selection) {
				jQuery("#coordinates").attr("value", selection.x1 + "," + selection.y1 + "," + selection.x2 + "," + selection.y2);
			}
		});
	};

	jQuery(".org-profile-image").hover(function(){
		jQuery(this).find("#edit-button").animate({"opacity" : 100}, {queue: false}, 150).css("display", "block");
	}, function() {
		jQuery(this).find("#edit-button").animate({"opacity" : 0}, {queue: false}, 150);
	});

	/* file upload stuff starts here */
	if (window.FileReader) {
		var reader = new FileReader();

		reader.onload = function (e) {
			jQuery(".preview-image").attr('src', e.target.result)
			jQuery(".preview-image").load(function(){
				selectImage(jQuery(".preview-image"));
			});
		};
	} else {
		jQuery(".preview-img").hide();
		jQuery("#upload-image .description").css("height", "auto");
	}

	// Required for drag and drop file access
	jQuery.event.props.push('dataTransfer');

	jQuery("#upload-image").on('drop', function(event) {

		jQuery(".modal-body").css("background-color", "#FFF");

		event.preventDefault();

		var file = event.dataTransfer.files[0];

		if (file.type.match('image.*')) {
			jQuery("#image").html(file);
			if (window.FileReader) {
				reader.readAsDataURL(file);
			}
		} else {
			// However you want to handle error that dropped file wasn't an image
		}
	});

	jQuery("#upload-image").on("dragover", function(event) {
		jQuery(".modal-body").css("background-color", "#f3f3f3");
		return false;
	});

	jQuery("#upload-image").on("dragleave", function(event) {
		jQuery(".modal-body").css("background-color", "#FFF");
	});

	jQuery('#upload-image').on('hidden', function () {
		if (jQuery(".profile-image-preview").length > 0) {
			jQuery(".profile-image-preview").remove();
			jQuery(".imgareaselect-selection").parent().remove();
			jQuery(".imgareaselect-outer").remove();
			jQuery("#image").val("");
			jQuery(".description").show();
		}
	});

	jQuery('#upload-image').on('shown', function() {
		if (!window.FileReader) {
			jQuery("#upload-image .description").html("Your browser does not support image cropping, your image will be center cropped.")
		}
		if (jQuery(".profile-image-preview").length <= 0) {
			var preview = jQuery("<div />").addClass("profile-image-preview");
			preview.append("<img />");
			preview.children("img").addClass("preview-image");
			jQuery(".preview-img").append(preview);
		}
	});

	jQuery("#upload-image-button").live("click", function(){
		if (window.FileReader) {
			if (typeof jQuery(".preview-image").attr("src") != "undefined") {
				jQuery("#upload_profile_image_form").submit();
				jQuery('#upload-image').modal("hide");
			} else {
				jQuery('#upload-image').modal("hide");
			}
		} else {
			jQuery("#upload_profile_image_form").submit();
		}
	});

	jQuery("#upload_profile_image_form").submit(function(){
		if (window.FileReader) {
			var imageFile = dataURItoBlob(jQuery(".preview-image").attr("src"));

			var xhr = new XMLHttpRequest();
			var fd = new FormData();
			fd.append('ajax_action', 'uploadimage');
			fd.append('image', imageFile);
			fd.append('coordinates', jQuery("#coordinates").val());
			fd.append('dimensions', jQuery("#dimensions").val());

			xhr.open('POST', ENTRADA_URL + "/profile", true);
			xhr.send(fd);

			xhr.onreadystatechange = function() {
				if (xhr.readyState == 4 && xhr.status == 200) {
					var jsonResponse = JSON.parse(xhr.responseText);
					if (jsonResponse.status == "success") {
						jQuery("#profile-image-container img.img-polaroid").attr("src", jsonResponse.data);
						if (jQuery("#image-nav-right").length <= 0) {
							jQuery("#btn-toggle").append("<a href=\"#\" class=\"btn active\" id=\"image-nav-right\" style=\"display:none;\">Uploaded</a>");
							jQuery("#image-nav-right").removeClass("active");
						}
					} else {
						// Some kind of failure notification.
					};
				} else {
					// another failure notification.
				}
			}

			if (jQuery(".profile-image-preview").length > 0) {
				jQuery(".profile-image-preview").remove();
				jQuery(".imgareaselect-selection").parent().remove();
				jQuery(".imgareaselect-outer").remove();
				jQuery("#image").val("");
				jQuery(".description").show();
			}

			return false;
		} else {
			jQuery("#upload_profile_image_form").append("<input type=\"hidden\" name=\"ajax_action\" value=\"uploadimageie\" />");
		}
	});

	jQuery("#image").live("change", function(){
		var files = jQuery(this).prop("files");

		if (files && files[0]) {
			if (window.FileReader) {
				reader.readAsDataURL(files[0]);
			}
		}
	});

	jQuery("#profile-image-container").hover(function(){
			jQuery("#profile-image-container .btn, #btn-toggle").fadeIn("fast");
		},
		function() {
			jQuery("#profile-image-container .btn").fadeOut("fast");
		});
});

if (jQuery("#reset-google-password-close").length) {
	jQuery("#reset-google-password-close").on('click', function() {
		jQuery("#reset-google-password-submit").show();
		jQuery("#reset-google-password-form").show();
		jQuery("#reset-google-password-success").hide();
		jQuery("#reset-google-password-waiting").hide();

		jQuery("#google_password_1").val('');
		jQuery("#google_password_2").val('');
		Control.Modal.close();
	});

	jQuery("#reset-google-password-submit").on('click', function() {
		$('reset-google-password-submit', 'reset-google-password-form').invoke('hide');
		$('reset-google-password-waiting').show();

		if ($('google_password_1') && $('google_password_2')) {
			var new_password = $F('google_password_1');
			var test_password = $F('google_password_2');

			if (new_password && test_password) {
				if (new_password == test_password) {

					jQuery.ajax({
						url : ENTRADA_URL + "/profile",
						data : "action=google-password-reset&password="+ new_password +"&ajax=1",
						type : "post",
						async : true,
						success : function(data) {
							jQuery("#reset-google-password-form-status").html('');
							jQuery("#reset-google-password-waiting").hide();
							jQuery("#reset-google-password-success").show();
						},
						error : function(data) {
							jQuery("#reset-google-password-form-status").update('<div class="display-error">We were unable to reset your password at this time, please try again later. If this error persists please contact the system administrator and inform them of the error.</div>');
							jQuery("#reset-google-password-waiting").hide();
							jQuery("#reset-google-password-submit").show();
							jQuery("#reset-google-password-form").show();
						}

					});
				} else {
					jQuery("#reset-google-password-form-status").html('<div class="display-error">Your passwords did not match, please try again.</div>');
					jQuery("#reset-google-password-waiting").hide();
					jQuery("#reset-google-password-submit").show();
					jQuery("#reset-google-password-form").show();
				}
			} else {
				jQuery("#reset-google-password-form-status").html('<div class="display-error" style="margin: 0">Please make sure you enter your new password, then re-enter it again in the space provided.</div>');
				jQuery("#reset-google-password-waiting").hide();
				jQuery("#reset-google-password-submit").show();
				jQuery("#reset-google-password-form").show();
			}
		} else {
			jQuery("#reset-google-password-form-status").html('<div class="display-error" style="margin: 0">Please make sure you enter your new password, then re-enter it again in the space provided.</div>');
			jQuery("#reset-google-password-waiting").hide();
			jQuery("#reset-google-password-submit").show();
			jQuery("#reset-google-password-form").show();
		}
	});
}

