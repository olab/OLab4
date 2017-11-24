var comment_id = 0;
var file_version_id = 0;

jQuery(document).ready(function ($) {

    $(".assignment_comment_edit").on("click", function (e) {
        comment_id = $(this).attr("data-commentid");

        $("#edit-comment").modal('show');

    });

    $(".assignment_comment_delete").on("click", function (e) {
        comment_id = $(this).attr("data-commentid");

        $("#delete-comment-modal").modal('show');

    });

    $(".assignment_file_delete").on("click", function (e) {
        file_version_id = $(this).attr("data-fileVersionId");

        $("#delete-file-modal").modal('show');

    });

    $("#add_assignment_comment").on("click", function (e) {
        $("#edit-comment").modal('show');
    });

    
    $('#edit-comment').on('show.bs.modal', function (e) {
        $("#comment_title").val("");
        if (typeof CKEDITOR != "undefined") {
            CKEDITOR.instances.comment_description.setData("");
        } else {
            $("#comment_description").val("");
        }

        if (comment_id) {
            var target_request = $.ajax({
                url: "?section=api-assignments",
                data: "method=get-comment&acomment_id=" + comment_id,
                type: 'GET'
            });

            $.when(target_request).done(function (data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status === "success") {

                    $("#comment_title").val(jsonResponse.data.comment_title);
                    if (typeof CKEDITOR != "undefined") {
                        CKEDITOR.instances.comment_description.setData(jsonResponse.data.comment_description);
                    } else {
                        $("#comment_description").val(jsonResponse.data.comment_title);
                    }

                }
            });
        }
        $("#modal_comment_id").val(comment_id);
        comment_id = 0;
    });

    $('#delete-comment-modal').on('show.bs.modal', function (e) {
        $("#delete-comment-container").html($("#comment-"+comment_id).html());
        $("#delete_modal_comment_id").val(comment_id);
        comment_id = 0;
    });

    $('#delete-file-modal').on('show.bs.modal', function (e) {
        $("#delete-file-container").html($("#file-version-"+file_version_id).html());
        $("#delete_file_version_id").val(file_version_id);
        file_version_id = 0;
    });

    $('#save-assignment-comment-button').on('click', function (e) {
        var modal_comment_id = $("#modal_comment_id").val();
        var modal_assignment_id = $("#modal_assignment_id").val();
        var comment_title = $("#comment_title").val();
        var assignment_proxy_id = $("#assignment_proxy_id").val();

        if (typeof CKEDITOR != "undefined") {
            var comment_description = CKEDITOR.instances.comment_description.getData();
        } else {
            var comment_description = $("#comment_description").val();
        }
        var target_request = $.ajax({
            url: "?section=api-assignments",
            data: {
                'method' : 'update-comment',
                'acomment_id': modal_comment_id,
                'assignment_id': modal_assignment_id,
                'comment_title': comment_title,
                'comment_description': comment_description,
                'assignment_proxy_id': assignment_proxy_id
            },
            type: "POST"
        });

        $.when(target_request).done(function (data) {
            var jsonResponse = JSON.parse(data);
            if (jsonResponse.status === "success") {
                $("#update-comment-result").html(jsonResponse.msg).addClass("display-success");
            } else {
                $("#update-comment-result").html(jsonResponse.msg).addClass("display-error");
            }
            setTimeout(location.reload(), 3000);
        });

    });

    $('#delete-assignment-comment-button').on('click', function (e) {
        var modal_comment_id = $("#delete_modal_comment_id").val();

        var target_request = $.ajax({
            url: "?section=api-assignments",
            data: {
                'method' : 'delete-comment',
                'acomment_id': modal_comment_id
            },
            type: "POST"
        });

        $.when(target_request).done(function (data) {
            var jsonResponse = JSON.parse(data);
            if (jsonResponse.status === "success") {
                $("#delete-comment-container").html("");
                $("#delete-comment-result").html(jsonResponse.msg).addClass("display-success");
            } else {
                $("#delete-comment-container").html("");
                $("#delete-comment-result").html(jsonResponse.msg).addClass("display-error");
            }
            setTimeout(location.reload(), 3000);
        });

    });

    $('#delete-assignment-file-button').on('click', function (e) {
        var modal_file_version_id = $("#delete_file_version_id").val();

        var target_request = $.ajax({
            url: "?section=api-assignments",
            data: {
                'method' : 'delete-file',
                'afversion_id': modal_file_version_id
            },
            type: "POST"
        });

        $.when(target_request).done(function (data) {
            var jsonResponse = JSON.parse(data);
            if (jsonResponse.status === "success") {
                $("#delete-file-container").html("");
                $("#delete-file-result").html(jsonResponse.msg).addClass("display-success");
            } else {
                $("#delete-file-container").html("");
                $("#delete-file-result").html(jsonResponse.msg).addClass("display-error");
            }
            setTimeout(location.reload(), 3000);
        });

    });
});