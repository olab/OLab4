var form;
var drag = false;

jQuery(document).ready(function ($) {

    $.event.props.push('dataTransfer');

    $("#upload-file-mod").on('drop', function(event) {
        event.stopPropagation();
        event.preventDefault();

        $(".description").css("background-color", "#f4f4f4");

        var file = event.dataTransfer.files[0];

        form = new FormData();
        form.append('assignment_file', file);
        drag = true;

        $("#file_title").val(file.name);
        $(".decription-large").hide();
        $(".decription-medium").hide();
        $(".decription-small").hide();
        $(".decription-large.ready").html(file.name);
        $(".decription-medium.ready").html(Math.round(file.size/1024 * 100) / 100 +" KB");
        $(".ready").show();

    });

    $(document).on('dragenter', function (e)
    {
        e.stopPropagation();
        e.preventDefault();
    });
    $(document).on('dragover', function (e)
    {
        e.stopPropagation();
        e.preventDefault();
    });
    $(document).on('drop', function (e)
    {
        e.stopPropagation();
        e.preventDefault();
    });

    $("#upload-file-mod").on("dragover", function(event) {
        event.stopPropagation();
        event.preventDefault();
        $(".description").css("background-color", "#ababab");
        return false;
    });

    $("#upload-file-mod").on("dragleave", function(event) {
        event.stopPropagation();
        event.preventDefault();
        $(".description").css("background-color", "#f4f4f4");
        return false;
    });

    $('#upload-file-mod').on('hidden', function () {
        $(".decription-large").show();
        $(".decription-medium").show();
        $(".decription-small").show();
        $("#file_title").val("");
        $("#file_description").val("");
        $("#file_afile_id").val("");
        $("#file_parent_id").val("");
        $("#assignment_file").val("");
        $("#upload-file-result").html("");
        $("#method").val("file-upload");
        $(".ready").hide();
        $(".file-comment").show();
    });

    $("#upload-file-button").on("click", function(e){
        e.preventDefault();
        e.stopPropagation();
        if (drag) {
            form.append('file_title', $("#file_title").val());
            form.append('file_description', $("#file_description").val());
            form.append('file_afile_id', $("#file_afile_id").val());
            form.append('file_assignment_id', $("#file_assignment_id").val());
            form.append('file_proxy_id', $("#file_proxy_id").val());
            form.append('file_type', $("#file_type").val());
            form.append('file_parent_id', $("#file_parent_id").val());
            form.append('method', $("#method").val());
            fileUpload(form);
            drag = false;
        } else {
            $("#upload_file_form").submit();
        }
    });

    $("form#upload_file_form").on("submit", function(e) {
        e.preventDefault();
        e.stopPropagation();
        var new_form = new FormData(this);
        fileUpload(new_form);
    });


    function fileUpload(formData) {
        $(".form-values").hide();
        $(".upload-progress-bar").show();
        var jqXHR=$.ajax({
            xhr: function() {
                var xhrobj = $.ajaxSettings.xhr();
                if (xhrobj.upload) {
                    xhrobj.upload.addEventListener('progress', function(event) {
                        var percent = 0;
                        var position = event.loaded || event.position;
                        var total = event.total;
                        if (event.lengthComputable) {
                            percent = Math.ceil(position / total * 100);
                        }

                        if (percent > 5) {
                            $(".upload-progress-bar").html(percent+" %");
                        }

                        $(".upload-progress-bar").css("width", percent+"%");
                        
                    }, false);
                }
                return xhrobj;
            },
            url: "?section=api-assignments",
            type: "POST",
            contentType:false,
            processData: false,
            cache: false,
            data: formData
        }).done(function( data ) {
            var jsonResponse = JSON.parse(data);
            if (jsonResponse.status === "success") {
                $("#upload-file-result").html(jsonResponse.msg).addClass("display-success");
            } else {
                $("#upload-file-result").html(jsonResponse.msg).addClass("display-error");
            }
            $(".form-values").show();
            $(".upload-progress-bar").hide();

             setTimeout(location.reload(), 5000);
        });

        return false;
    }

    $("#assignment_file").on("change", function(){
        var files = $(this).prop("files");

        $("#file_title").val(files[0].name);
        $(".decription-large").hide();
        $(".decription-medium").hide();
        $(".decription-small").hide();
        $(".decription-large.ready").html(files[0].name);
        $(".decription-medium.ready").html(Math.round(files[0].size/1024 * 100) / 100 +" KB");
        $(".ready").show();
    });

    $(".upload-revised-file").on("click", function() {
        $("#file_afile_id").val($(this).attr("data-fid"));
        $("#method").val("version-upload");
        $(".file-comment").hide();
        $("#upload-file-mod").modal('show');
    });

    $(".upload-teacher-file").on("click", function() {
        $("#file_parent_id").val($(this).attr("data-parentid"));
        $("#upload-file-mod").modal('show');
    })
});

