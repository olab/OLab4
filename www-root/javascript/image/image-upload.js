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

    $(".org-image").hover(function(){
        $(this).find("#edit-button").animate({"opacity" : 100}, {queue: false}, 150).css("display", "block");
    }, function() {
        $(this).find("#edit-button").animate({"opacity" : 0}, {queue: false}, 150);
    });

    /* file upload stuff starts here */

    if (window.FileReader) {
        var reader = new FileReader();

        reader.onload = function (e) {

            var image = $(new Image());
            image.attr('src', e.target.result);
            image.load(function(){

                if (this.width < 1200 || this.height < 250) {
                        $(".description").show();
                        $("#upload-image-mod .description").html("Please upload an image with height not less then 250px and width not less then 1200px");
                        $(".preview-image").removeAttr("src");
                        return;
                } else {
                    $(".preview-image").attr('src', e.target.result);
                    $("#image-container img.img-polaroid").attr("src", e.target.result);

                    $(".description").hide();

                    $(".image-preview").children("img").show();

                    $("#coordinates").attr("value", "0,0,0,0");
                    $("#dimensions").attr("value", this.width + "," + this.height);

                }
            });
        };
    } else {
        $(".preview-img").hide();
        $("#upload-image-mod .description").css("height", "auto");
    }

    // Required for drag and drop file access
    $.event.props.push('dataTransfer');

    $("#upload-image-mod").on('drop', function(event) {

        $(".modal-body").css("background-color", "#FFF");

        event.preventDefault();

        var file = event.dataTransfer.files[0];

        if (file.type.match('image.*')) {
            $("#image").html(file);
            if (window.FileReader) {
                reader.readAsDataURL(file);
            }
        } else {
            // However you want to handle error that dropped file wasn't an image
        }
    });

    $("#upload-image-mod").on("dragover", function(event) {
        $(".modal-body").css("background-color", "#f3f3f3");
        return false;
    });

    $("#upload-image-mod").on("dragleave", function(event) {
        $(".modal-body").css("background-color", "#FFF");
    });

    $('#upload-image-mod').on('hidden', function () {
        if ($(".image-preview").length > 0) {
            $(".image-preview").remove();
            $(".imgareaselect-selection").parent().remove();
            $(".imgareaselect-outer").remove();
            if (typeof RESOURCE_ID != "undefined" && RESOURCE_ID > 0) {
                $("#image").val("");
            }
            $(".description").html("To upload a new course image you can drag and drop it on this area, or use the Browse button to select an image from your computer.");
            $(".description").show();
        }
    });

    $('#upload-image-mod').on('show.bs.modal', function() {
        if (!window.FileReader) {
            $("#upload-image-mod .description").html("Your browser does not support image cropping, your image will be center cropped.")
        }
        if ($(".image-preview").length <= 0) {
            var preview = $("<div />").addClass("image-preview");
            preview.append("<img />");
            preview.children("img").addClass("preview-image").hide();
            $(".preview-img").append(preview);
        }
    });

    $("#upload-image-button").on("click", function(){
        if (window.FileReader && typeof RESOURCE_ID != "undefined" && RESOURCE_ID > 0) {
            if (typeof $(".preview-image").attr("src") != "undefined") {
                // $("#upload_image_form").submit();
                imageUpload();
                $('#upload-image-mod').modal("hide");
            } else {
                $('#upload-image-mod').modal("hide");
            }
        } else {
            $("#image-container").append("<input type=\"hidden\" name=\"imageaction\" value=\"uploadimage\" />");
            // $("#upload_image_form").submit();
        }
    });


    function imageUpload() {
        if (window.FileReader && typeof RESOURCE_ID != "undefined" && RESOURCE_ID > 0) {
            var imageFile = dataURItoBlob($(".preview-image").attr("src"));

            var xhr = new XMLHttpRequest();
            var fd = new FormData();
            fd.append('method', 'upload-image');
            fd.append('image', imageFile);
            fd.append('coordinates', $("#coordinates").val());
            fd.append('resource_id', RESOURCE_ID);
            fd.append('resource_type', $("#resource_type").val());
            fd.append('dimensions', $("#dimensions").val());

            xhr.open('POST', ENTRADA_URL+"/admin/courses?section=api-image", true);
            xhr.send(fd);

            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    var jsonResponse = JSON.parse(xhr.responseText);
                    if (jsonResponse.status == "success") {
                        var date = new Date();
                        var time = date.getTime();
                        $("#image-container img.img-polaroid").attr("src", jsonResponse.data + "&time="+time);
                        if ($("#image-nav-right").length <= 0) {
                            $("#btn-toggle").append("<a href=\"#\" class=\"btn active\" id=\"image-nav-right\" style=\"display:none;\">Uploaded</a>");
                            $("#image-nav-right").removeClass("active");
                        }
                    } else {
                        // Some kind of failure notification.
                    };
                } else {
                    // another failure notification.
                }
            }

            if ($(".image-preview").length > 0) {
                $(".image-preview").remove();
                $(".imgareaselect-selection").parent().remove();
                $(".imgareaselect-outer").remove();
                $("#image").val("");
                $(".description").show();
            }

            return false;
        } else {
            $("#courseForm").append("<input type=\"hidden\" name=\"imageaction\" value=\"uploadimage\" />");
        }
    }

    $("#image").on("change", function(){
        var files = $(this).prop("files");

        if (files && files[0]) {
            if (window.FileReader) {
                reader.readAsDataURL(files[0]);
            }
        }
    });

    $("#image-container").hover(function(){
            $("#image-container .btn, #btn-toggle").fadeIn("fast");
        },
        function() {
            $("#image-container .btn").fadeOut("fast");
        });
});