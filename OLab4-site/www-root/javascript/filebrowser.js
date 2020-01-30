$(function() {
    var selected_lo_file_id = "";
    var offset = 0;
    var loading = false;
    var dragdrop = false;
    
    if (window.File && window.FileReader && window.FileList && window.Blob) {
        dragdrop = true;
    }

    function fetchLearningObjects(start, length) {
        if (loading == false) {
            loading = true;
            $.ajax({
                type: "GET",
                url: ENTRADA_URL + "/api/lor.api.php",
                data: {method: "get-learning-objects", type: getUrlParam("type"), iDisplayStart: start, iDisplayLength: length},
                success: function(data) {
                    if (data.length > 0) {
                        var jsonResponse = JSON.parse(data);
                        if (jsonResponse.aaData.length > 0) {
                            $(jsonResponse.aaData).each(function(i, v) {
                                var li      = $(document.createElement("li"));
                                var img     = $(document.createElement("img"));
                                var span    = $(document.createElement("span"));
                                img.attr({"src": ENTRADA_URL + "/api/serve-learning-object.api.php?id=" + v.id + "&thumbnail=1&filename=" + v.name, "width": 150, "height": 150 })
                                span.addClass("name").html(v.name);
                                li.data({"lo-file-id": v.id, "lo-filename": v.name}).append(img, span);
                                $("#tile-view").append(li);
                            });
                            offset += length;
                            var bottom_limit = $(".file-browser-panel").outerHeight(true) - $(window).innerHeight();
                            if (bottom_limit < 0 && !$("#tile-view").hasClass("hide")) {
                                loading = false;
                                fetchLearningObjects(offset, 4);
                            }
                        }
                    }
                    loading = false;
                }
            });
        }
    }
    
    function checkBottomLimit () {
        if (!$("#tile-view").hasClass("hide")) {
            var bottom_limit = $(".file-browser-panel").outerHeight(true) - $(window).innerHeight();
            var curr_pos = $(window).scrollTop();
            if (curr_pos >= bottom_limit - 5 || bottom_limit < 0) {
                fetchLearningObjects(offset, 4);
            } 
        }
    }
    
    function insertFile(filename) {
        var funcNum = getUrlParam( 'CKEditorFuncNum' );
        var fileUrl = ENTRADA_URL + "/api/serve-learning-object.api.php?id=" + selected_lo_file_id + "&filename=" + filename;
        window.opener.CKEDITOR.tools.callFunction(funcNum, fileUrl, function() {
            var element, dialog = this.getDialog();
            
            if (dialog.getName() == 'image') {
                element = dialog.getContentElement( 'info', 'txtAlt' );
              
                if (element) {
                    element.setValue(filename);
                }
            }
            
        });
        window.close();
    }
    
    fetchLearningObjects(offset, 4)
    
    $(window).scroll(function(e) {
        checkBottomLimit();
    });

    $(window).resize(function(e) {
        checkBottomLimit();
    });

    $("#upload-file-btn").on("click", function() {
        $("#fileupload").submit();
    });

    $(".navbar .brand").on("click", function(e) {
        e.preventDefault();
    });

    var datatable = $("#file-list table").dataTable({
        'sPaginationType': 'full_numbers',
        'bInfo': false,
        'bAutoWidth': false,
        'sAjaxSource' : ENTRADA_URL + "/api/lor.api.php?method=get-learning-objects&type=" + getUrlParam("type"),
        'bServerSide': true,
        'bProcessing': true,
        'aoColumns': [
            { 'mDataProp': 'filename' },
            { 'mDataProp': 'filesize' },
            { 'mDataProp': 'updated_date' },
        ],
        'iDisplayLength': 25,
        'iDisplayStart': 0,
        'aaSorting' : [
            [2, 'desc']
        ],
        'oLanguage': {
            'sEmptyTable': 'You have not yet uploaded any learning objects. Use the Upload button below to add some!',
            'sZeroRecords': 'No credits found to display.'
        }
    })

    $(".toggle-view").on("click", function(e) {

        $(this).siblings(".active").removeClass("active");
        $(this).addClass("active");

        var target = $(this).attr("href");

        if (target == "#tile-view") {
            $(target).removeClass("hide");
            $("#table-view").parent().addClass("hide");
            checkBottomLimit();
        } else {
            $(target).parent().removeClass("hide");
            $("#tile-view").addClass("hide");
        }

        e.preventDefault();
    });

    $("#tile-view, #table-view").on("click", "li, a", function(e) {
        var filename = $(this).data("lo-filename");
        selected_lo_file_id = $(this).data("lo-file-id");
        insertFile(filename);
    });
    
    if (dragdrop == true) {
        $(window).on("dragover", function(e) {

            e.preventDefault();
            e.stopPropagation();
        });

        $(window).on("dragleave", function(e) {

            e.preventDefault();
            e.stopPropagation();
        });

        $(window).on("drop", function(e) {
            $("#uploading-modal").modal("show");
            e.preventDefault();
            e.stopPropagation();
            e.dataTransfer = e.originalEvent.dataTransfer;
            var files = e.dataTransfer.files;
            var xhr = new XMLHttpRequest();
            var fd = new FormData();
            if (files.length > 0) {
                $(files).each(function(i, v) {
                    fd.append("upload[]", v);
                });
                
                xhr.open('POST', ENTRADA_URL + "/api/lor.api.php?method=upload-files", true);
                xhr.send(fd);

                xhr.onreadystatechange = function() {
                    if (xhr.readyState == 4 && xhr.status == 200) {
                        var jsonResponse = JSON.parse(xhr.responseText);
                        if (jsonResponse.status == "success") {
                            if (!$("#tile-view").hasClass("hide")) {
                                $("#tile-view").empty();
                                offset = 0;
                                checkBottomLimit();
                            } else {
                                datatable.fnDraw();
                            }

                            if (typeof jsonResponse.data !== "undefined" && jsonResponse.data.length >= 1) {
                                var success_messages = "";
                                $.each(jsonResponse.data, function(index, value){
                                    success_messages = success_messages + "<li>" + value + "</li>";
                                });
                                $("#success-msg-holder").append("<div id=\"display-success-box\" class=\"alert alert-block alert-success\"><button type=\"button\" class=\"close\" data-dismiss=\"alert\">&times;</button><ul>" + success_messages + "</ul></div>");
                            }
                            $("#uploading-modal").modal("hide");
                        } else {
                            if (jsonResponse.data.invalid_files.length > 0) {
                                var msg = "<div class=\"alert alert-block alert-error\">";
                                    msg += "<p>The following files are not supported file types:</p>";
                                    msg += "<ul>";
                                $(jsonResponse.data.invalid_files).each(function(i, v) {
                                    msg += "<li><strong>" + v + "</strong></li>";
                                });
                                msg += "<ul>";
                                msg += "</div><div class=\"row-fluid\"><a href=\"#\" id=\"close-upload-modal\" data-dismiss=\"modal\" class=\"btn btn-primary pull-right\">Close</a></div>";
                                $("#uploading-modal .modal-body .modal-loading").hide();
                                $("#uploading-modal .modal-body .modal-messages").empty().append(msg).show();
                            }
                        }
                    } else if (xhr.readyState == 4 && xhr.status != 200) {
                        $("#uploading-modal .modal-body .modal-loading").hide();
                        $("#uploading-modal .modal-body .modal-messages").empty().append("<div class=\"alert alert-block alert-error\">An error ocurred while attempting to upload your files.</div>").show();
                    }
                }
                
            }
            
        });
    }
    
    $("#uploading-modal").on("hidden", function(e) {
        $("#uploading-modal .modal-body .modal-messages").hide();
        $("#uploading-modal .modal-body .modal-loading").show();
    });

    $("#fileinput").on("change", function(e) {
        $("#fileupload").submit();
    });
    
});

function getUrlParam( paramName ) {
    var reParam = new RegExp( '(?:[\?&]|&)' + paramName + '=([^&]+)', 'i' ) ;
    var match = window.location.search.match(reParam) ;

    return ( match && match.length > 1 ) ? match[ 1 ] : null ;
}