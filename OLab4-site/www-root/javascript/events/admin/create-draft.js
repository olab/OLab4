jQuery(document).ready(function($) {
    var courses = [];
    var courseLength = 0;
    var coursesAdded = 0;
    var courseLink = {};
    var coursesLinkedArray = [];
    var selected_course = $("#selectedCourses");

    //creates JS array of courses
    $.each(courses_php, function (key, value) {
        selected = {
            course_id: key,
            code: value["code"],
            name: value["name"]
        };
        if (courses.length) {
            courseLength = courses.length;
        }
        courses[courseLength] = selected;
    });

    $("#destinationCourses").chosen().change(function() {
        selected_course.show();
        var chosenCourse = $(this).val();

        //tracks how many courses have been added - used in the id of each new select element
        var arrayPosition = coursesAdded;
        coursesAdded++;

        var selected_options = get_selected_option(chosenCourse);
        var option = selected_options["option"];
        var courseCode = selected_options["courseCode"];

        var select = "<select id=\"chosen-course-id-" + coursesAdded + "\" class=\"chosen-select\" data-id=\"" + chosenCourse + "\" data-array-pos=\"" + arrayPosition + "\">";
        select += option;
        select += "</select>";

        var HTML = "<tr class=\"copyCourseRow\">\n";
        HTML += "<td class=\"selectedCourse\">\n";
        HTML += courseCode;
        HTML += "</td>\n";
        HTML += "<td class=\"courseCopyIcon\">\n";
        HTML += "   <i class=\"icon-arrow-right\"></i>\n";
        HTML += "</td>\n";
        HTML += "<td class=\"destinationCourse\">\n";
        HTML += select;
        HTML += "</td>\n";
        HTML += "<td class=\"removeCourseBtn\">\n";
        HTML += "<button type=\"button\" class=\"btn btn-remove-course-copy\" data-array-pos=\"" + arrayPosition + "\" data-chosen-course-id=\"" + chosenCourse + "\">\n";
        HTML += "Remove";
        HTML += "</button>\n";
        HTML += "</td>\n";
        HTML += "</tr>\n";

        //adds the selected course to the page
        selected_course.append(HTML);

        //removes the selected course from the original list
        $("option[data-id=\"course-id-" + chosenCourse + "\"]").attr("disabled", "disabled");
        $("#destinationCourses").trigger("liszt:updated");

        //adds the default course to the course linked array
        var chosen_course_input = $("#chosen-course-id-" + coursesAdded);
        var destinationCourse = $(chosen_course_input).val();
        courseLink = {
            source_course_id: chosenCourse,
            destination_course_id: destinationCourse
        };

        coursesLinkedArray[arrayPosition] = courseLink;
        update_hidden_course_array();

        //activates the new select element and updates the object in the correct position in the array
        $(chosen_course_input).chosen().change(function() {
            var arrayPos = $(this).data("array-pos");
            var sourceCourse = chosenCourse;
            var destinationCourse = $(this).val();
            courseLink = {
                source_course_id: sourceCourse,
                destination_course_id: destinationCourse
            };
            coursesLinkedArray[arrayPos] = courseLink;
            update_hidden_course_array();
        });

    });

    //removes the course from the screen and javascript array
    selected_course.on("click", ".btn-remove-course-copy", function() {
        removeCourseCopy($(this));
    });

    function get_selected_option(chosenCourse) {
        //loops through the list of courses to find the course that matches the one selected
        var option = "";
        var courseCode = "";
        for (var i = 0; i < courses.length; i++) {
            var selected = "";
            if (courses[i]["course_id"] == chosenCourse) {
                selected = "selected=\"selected\"";
                courseCode = courses[i]["code"];
            }
            option += "<option value=\"" + courses[i]["course_id"] + "\" " + selected + ">";
            option += courses[i]["code"] + " - " + courses[i]["name"];
            option += "</option>";
        }
        return return_obj = {
            option: option,
            courseCode: courseCode
        };
    }

    function update_hidden_course_array() {
        $("#course_array").val(JSON.stringify(coursesLinkedArray));
    }

    function removeCourseCopy(clicked) {
        var chosenCourseId = $(clicked).data("chosen-course-id");
        var arrayPosition  = $(clicked).data("array-pos");
        var removeCourse   = $(clicked).parent().parent();
        $(removeCourse).remove();

        //deletes the course from the array
        delete coursesLinkedArray[arrayPosition];

        //adds the course to the select
        $("option[data-id=\"course-id-" + chosenCourseId + "\"]").attr("disabled", false);
        $("#destinationCourses").trigger("liszt:updated");

        update_hidden_course_array();
    }
});