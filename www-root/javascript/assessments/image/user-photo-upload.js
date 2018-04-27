function dataURItoBlob(dataURI, type) {
    type = typeof a !== 'undefined' ? type : 'image/jpeg';
    var binary = atob(dataURI.split(',')[1]);
    var array = [];
    for (var i = 0; i < binary.length; i++) {
        array.push(binary.charCodeAt(i));
    }
    return new Blob([new Uint8Array(array)], {type: type});
}

jQuery(function(){

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

        jQuery("#coordinates").attr("value", w_offset + "," + h_offset + "," + (w_offset + 153) + "," + (h_offset + 200));
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

    var reader = new FileReader();

    reader.onload = function (e) {
        jQuery(".preview-image").attr('src', e.target.result)
        jQuery(".preview-image").load(function(){
            selectImage(jQuery(".preview-image"));
        });
    };

    // Required for drag and drop file access
    jQuery.event.props.push('dataTransfer');

    jQuery("#upload-image").on('drop', function(event) {

        jQuery(".modal-body").css("background-color", "#FFF");

        event.preventDefault();

        var file = event.dataTransfer.files[0];

        if (file.type.match('image.*')) {
            jQuery("#image").html(file);
            reader.readAsDataURL(file);
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
        if (jQuery(".profile-image-preview").length <= 0) {
            var preview = jQuery("<div />").addClass("profile-image-preview");
            preview.append("<img />");
            preview.children("img").addClass("preview-image");
            jQuery(".preview-img").append(preview);
        }
    });

    jQuery('#upload-image').on("click", '#upload-image-button', function(){
        if (typeof jQuery(".preview-image").attr("src") != "undefined") {
            jQuery("#upload_profile_image_form").submit();
            jQuery('#upload-image').modal("hide");
        } else {
            jQuery('#upload-image').modal("hide");
        }
    });

    jQuery('#upload_profile_image_form').submit(function(){
        var imageFile = dataURItoBlob(jQuery(".preview-image").attr("src"));
        var proxy_id = jQuery("#proxy_id").val();

        var xhr = new XMLHttpRequest();
        var fd = new FormData();
        fd.append('ajax_action', 'uploadimage');
        fd.append('image', imageFile);
        fd.append('coordinates', jQuery("#coordinates").val());
        fd.append('dimensions', jQuery("#dimensions").val());
        fd.append('proxy_id', proxy_id);

        xhr.open('POST', ENTRADA_URL + "/admin/assessments?section=api-user-photo-upload", true);

        xhr.send(fd);

        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                var jsonResponse = JSON.parse(xhr.responseText);
                if (jsonResponse.status == "success") {
                    jQuery("#user-photo-" + proxy_id).attr("src", jsonResponse.data);
                    if (jQuery("#image-nav-right").length <= 0) {
                        jQuery("#upload-user-photo-" + proxy_id).remove();
                        jQuery("#btn-toggle").append("<a href=\"#\" class=\"btn btn-small active\" id=\"image-nav-right\" style=\"display:none;\">Uploaded</a>");
                        jQuery("#image-nav-right").removeClass("active");
                    }
                } else {
                    // Some kind of failure notification.
                }
            } else {
                // Another failure notification.
            }
        };

        if (jQuery(".profile-image-preview").length > 0) {
            jQuery(".profile-image-preview").remove();
            jQuery(".imgareaselect-selection").parent().remove();
            jQuery(".imgareaselect-outer").remove();
            jQuery("#image").val("");
            jQuery(".description").show();
        }

        return false;
    });

    jQuery('#upload_profile_image_form').on("change", "#image", function(){
        var files = jQuery(this).prop("files");

        if (files && files[0]) {
            reader.readAsDataURL(files[0]);
        }
    });

    jQuery(".upload-image-modal-btn").on("click", function (e) {
        jQuery("#proxy_id").val(jQuery(this).data("proxy-id"));
    });

    jQuery(".upload-image-modal-btn").on("click", function() {
        jQuery("#upload-image").removeClass("hide");
    });

    jQuery(".user-photo-upload-container").on("mouseover", function () {
        jQuery(this).find("a.upload-image-modal-btn").removeClass("hide");
    });

    jQuery(".user-photo-upload-container").on("mouseout", function () {
        jQuery(this).find("a.upload-image-modal-btn").addClass("hide");
    });

    jQuery(".user-photo-upload-container").tooltip({
        selector: '[data-toggle="tooltip"]',
        placement: "top"
    });
});