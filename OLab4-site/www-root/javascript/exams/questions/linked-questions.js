jQuery(document).ready(function ($) {
    $("#exam-questions-container").on("click", ".btn-linked-question", function (e) {
        var $this = $(this);
        var element = $this.parents(".question");
        var modal = $("#linked-question-modal");
        e.preventDefault();

        modal.data({"question-version-id": element.data("version-id"), "question-view": "detail-view"}); //set the default view
        modal.find(".modal-body").empty();
        modal.find("a.go-back").remove();

        var msg = $("<div>").addClass("alert alert-info").attr("id", "linked-question-msg").html("This question is linked to the groups below. Click a <strong>Group Title</strong> to view the other questions in that group.");
        var table = buildLinkedGroups(element.data("version-id"), edit_exam);
        modal.find(".modal-body").append(msg, table);
        modal.modal("show");
    });

    $("#exam-questions-container").on("click", "a.linked-group-title", function (e) {
        e.preventDefault();

        var $this = $(this);
        var modal = $("#linked-question-modal");
        var group_title = $("<h2>").text("Group: " + $this.text());
        var grouped_questions = buildGroupedQuestions($this.data("group-id"), edit_exam);
        var backButton = $("<a>").addClass("go-back btn btn-default pull-left").text("Back");

        modal.find(".modal-body").empty().append(group_title, grouped_questions);
        modal.find(".modal-footer > .row-fluid").prepend(backButton);
    });

    $("#linked-question-modal").on("click", "a.go-back", function (e) {
        e.preventDefault();

        var modal = $("#linked-question-modal");
        var table = buildLinkedGroups(modal.data("question-version-id"), edit_exam);
        var msg = $("<div>").addClass("alert alert-info").attr("id", "linked-question-msg").html("This question is linked to the groups below. Click a <strong>Group Title</strong> to view the other questions in that group.");

        modal.find("a.go-back").remove();
        modal.find(".modal-body").empty().append(msg, table);
    });

    $("#linked-question-modal").on("click", "a.view-toggle", function (e) {
        VIEW_PREFERENCE_MODAL = $(this).data("view");
        toggleModalView($(this));
    });

    $("#linked-question-modal").on("click", "a.attach-group", function (e) {
        e.preventDefault();

        var $this = $(this);
        var modal = $("#linked-question-modal");
        var exam_id = $("#exam_id").val();
        var group_id = $this.data("group-id");
        switch (edit_exam) {
            case "add":
                var replaceWith = null;
                break;
            case "edit":
                var replaceWith = modal.data("question-version-id");
                break;
            default:
                var replaceWith = null;
                break;
        }
        modal.find(".alert").remove();
        attachGroup(exam_id, group_id, replaceWith).done(function (json) {
            var alert = $("<div>").addClass("alert");
            var status = json.status;
            var msg = json.data;
            if (status === "success") {
                alert.addClass("alert-success");
                modal.find(".modal-body").empty().append(alert.append(msg));
                window.location.href = ENTRADA_URL + "/admin/exams/exams?section=edit-exam&id=" + exam_id;
            } else if (status === "error") {
                alert.addClass("alert-error");
                modal.find(".modal-body").prepend(alert.append(msg));
            }
        });
    });

    function toggleModalView(clicked) {
        var selected_view = clicked.data("view");
        var modal = clicked.parents(".modal");

        switch (selected_view) {
            case "detail":
                modal.find("#question-detail-container").removeClass("hide");
                modal.find("#question-table-container").addClass("hide");
                break;
            case "list":
                modal.find("#question-detail-container").addClass("hide");
                modal.find("#question-table-container").removeClass("hide");
                break;
            default:
                break;
        }

        clicked.siblings().removeClass("active");
        clicked.addClass("active");
    }

    function buildGroupedQuestions(group_id, edit_mode) {
        var questionDetailContainer = $("<div>").attr("id", "question-detail-container");
        var form = $("<form>").addClass("exam-horizontal modal-view");
        var questionTableContainer = $("<div>").attr("id", "question-table-container");
        var table = $("<table>").attr("id", "question-table-container").addClass("table table-bordered table-striped");
        var tableHeader = $("<thead>");
        var headerRow = $("<tr>");
        var headerColumnID = $("<th>").text("ID/Ver");
        var headerColumnCode = $("<th>").text("Code");
        var headerColumnType = $("<th>").text("Type");
        var headerColumnDescription = $("<th>").text("Description");
        var tableBody = $("<tbody>");
        var rowViewControls = $("<div>").addClass("row-fluid space-below");
        switch (edit_mode){
            case "add":
                var btnText = "Attach Group";
                var btnIcon = "fa-plus-circle";
                break;
            case "edit":
                var btnText = "Replace with Group";
                var btnIcon = "fa-exchange";
                break;
            default:
                var btnText = "Attach Group";
                var btnIcon = "fa-plus-circle";
                break;
        }
        var actionButtons = $("<a>").attr("href", "#").addClass("btn btn-success pull-right attach-group").data("group-id", group_id).html("<i class=\"add-icon fa " + btnIcon + "\"></i> " + btnText);
        var questionViewControls = $("<div>").attr("id", "question-view-controls").addClass("btn-group");
        var btnListView = $("<a>").attr({
            "id": "list-view",
            "attr": "#"
        }).addClass("btn view-toggle").data("view", "list").html("<i class=\"icon-align-justify\"></i>");
        var btnDetailView = $("<a>").attr({
            "id": "detail-view",
            "attr": "#"
        }).addClass("btn view-toggle").data("view", "detail").html("<i class=\"icon-th-large\"></i>");

        tableHeader.append(headerRow.append(headerColumnID, headerColumnCode, headerColumnType, headerColumnDescription));
        getGroupedQuestions(group_id).done(function (json) {
            var questions = json.data.questions;

            //Build the Details view
            for (var i = 0; i < questions.length; i++) {
                var question_details = questions[i].html_details;
                var question_row = $("<tr>");
                var columnGroupID = $("<td>").text("ID: " + questions[i].id + " / Ver: " + questions[i].version);
                var columnGroupCode = $("<td>").text(questions[i].question_code);
                var columnGroupType = $("<td>").text(questions[i].question_type);
                var columnDescription = $("<td>").text(questions[i].question_description);

                question_row.append(columnGroupID, columnGroupCode, columnGroupType, columnDescription);
                tableBody.append(question_row);
                table.append(tableHeader, tableBody);

                questionDetailContainer.append(question_details);
            }

            switch (VIEW_PREFERENCE_MODAL) {
                case "detail":
                    btnDetailView.addClass("active");
                    questionDetailContainer.removeClass("hide");
                    questionTableContainer.addClass("hide");
                    break;
                case "list":
                    btnListView.addClass("active");
                    questionDetailContainer.addClass("hide");
                    questionTableContainer.removeClass("hide");
                    break;
                default:
                    break;
            }

            questionTableContainer.append(table);
            rowViewControls.append(questionViewControls.append(btnListView, btnDetailView));
            if (edit_mode !== false) {
                rowViewControls.append(actionButtons);
            }
            form.append(rowViewControls, questionDetailContainer, questionTableContainer);
        });

        return form;
    }

    function buildLinkedGroups(question_version_id, edit_mode) {
        var table = $("<table>").addClass("table table-bordered table-striped");
        var tableHeader = $("<thead>");
        var headerRow = $("<tr>");
        var headerColumnGroupTitle = $("<th>").text("Group Title");
        var headerColumnGroupDescription = $("<th>").text("Group Description");
        var headerColumnGroupUpdated = $("<th>").text("Updated Date");
        var headerColumnActions = $("<th>");

        //Build the question group table
        headerRow.append(headerColumnGroupTitle, headerColumnGroupDescription, headerColumnGroupUpdated);
        if (edit_mode !== false) {
            headerRow.append(headerColumnActions);
        }
        table.append(tableHeader.append(headerRow));

        getLinkedGroups(question_version_id).done(function (json) {
            var groups = json.data
            switch (edit_mode){
                case "add":
                    var btnText = "Attach Group";
                    var btnIcon = "fa-plus-circle";
                    break;
                case "edit":
                    var btnText = "Replace with Group";
                    var btnIcon = "fa-exchange";
                    break;
                default:
                    var btnText = "Attach Group";
                    var btnIcon = "fa-plus-circle";
                    break;
            }

            for (var i = 0; i < groups.length; i++) {
                var row = $("<tr>");
                var columnGroupTitle = $("<td>").append($("<a>").addClass("linked-group-title").attr("href", "#").data("group-id", groups[i].group_id).text(groups[i].group_title));
                var columnGroupDescription = $("<td>").text(groups[i].group_description);
                var columnGroupUpdated = $("<td>").text(groups[i].updated_date);
                var columnGroupActions = $("<td>").append($("<a>").attr("href", "#").data("group-id", groups[i].group_id).addClass("btn btn-success attach-group").html("<i class=\"add-icon fa " + btnIcon + "\"></i> "+btnText));

                row.append(columnGroupTitle, columnGroupDescription, columnGroupUpdated);
                if (edit_mode !== false) {
                    row.append(columnGroupActions);
                }
                table.append(row);
            }
        });

        return table;
    }

    function getLinkedGroups(question_version_id) {
        return $.getJSON(ENTRADA_URL + "/admin/exams/questions?section=api-questions", {
            "method": "get-linked-questions",
            "question_version_id": question_version_id
        });
    }

    function getGroupedQuestions(group_id) {
        return $.getJSON(ENTRADA_URL + "/admin/exams/groups?section=api-group", {
            "method": "get-group",
            "group_id": group_id
        });
    }

    function attachGroup(exam_id, group_id, replaceWith) {
        return $.post(ENTRADA_URL + "/admin/exams/exams?section=api-exams", {
            "method": "attach-grouped-questions",
            "exam_id": exam_id,
            "group_id": group_id,
            "replace": replaceWith
        }, null, "json");
    }
});