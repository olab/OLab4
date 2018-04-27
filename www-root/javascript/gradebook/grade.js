/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Gradebook / Assessment / Grade
 * 
 * @author Organization: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 * 
 */

jQuery("document").ready(function($) {

	var tableSelector = "#datatable-student-list";

	// Init DataTable
	var table = $(tableSelector).DataTable({
		// remove search box
		dom: "lrtip",

		// remove ability to set number of results per page
		lengthChange: false,

		// remove "1-N of N Entries"
		info: false,

		// disable pagination
		paging: false,
	});

	// Filter results based on search input
	$("#input-search-learners").on("keyup", function () {
	    table.search(this.value).draw();
	});

	// Image used to display loading status
	var loading_html = '<img width="16" height="16" src="'+ENTRADA_URL+'/images/loading.gif">';

	// Callback function run when a user wants to edit the next cell
	function editNextCell(e){
		var dest;

		switch(e.which) {
			case 38: // Up
			case 40: // Down
			case 13: // Enter

				// Go up or down a line
				$('input', this).trigger('blur');
				var pos = $(this).parent().parent().prevAll().length;
				var row = $(this).parents("tr");
				var groupId = $(this).attr("data-group-id");

				if(e.which == 38) { //going up!
					if ($(this).hasClass("in-group")) {
						dest = row.prevAll("tr").find(".grade").not(".in-group-" + groupId).last().parents("tr");
					}
					else {
						dest = row.prev();
					}
				} else {
					if ($(this).hasClass("in-group")) {
						dest = row.nextAll("tr").find(".grade").not(".in-group-" + groupId).first().parents("tr");
					}
					else {
						dest = row.next();
					}
					
				}

				if(dest) {
					$(dest).find("."+$(this).attr("data-type")).trigger("edit");
				}

			break;
			default:
			break;
		}
	}

	// Updates the grade entry upon ajax return
	function ajaxGradeUpdateCallback(data, item) {

		var suffix = $(item).next(".gradesuffix").html().split("|");

		// If grade came back deleted remove the grade ID data
		if (data == "-") {
			var percent = 0;
			$(item).attr("data-grade-id", "");
			$(item).next(".gradesuffix").hide();
			$(item).attr("data-formatted-grade", "");
			$(item).attr("data-grade-value", "");
			$(item).html(data);
		} 
		else {
			grade = $.parseJSON(data);
			$(item).html(grade.grade_value);

			if (suffix[1]) {
				var percent = (grade.grade_value/suffix[1]*100).toFixed(2);
			}

			$(item).attr("data-grade-id", $(item).attr("data-grade-id"));
			$(item).attr("data-formatted-grade", grade.grade_value);
			$(item).next(".gradesuffix").show();
		}

		if (suffix[1]) {
			var id_suffix = $(item).attr("id").substring(5);
			$("#percentage"+id_suffix).html('<div style="width: 45px; ">'+percent+'%</div>');
        }

        if ($("#grades"+$(item).attr("data-proxy-id")).hasClass("highlight")) {
        	$("#grades"+$(item).attr("data-proxy-id")).removeClass("highlight");
        }
	}

	if (!$(tableSelector).hasClass("has-form")) {
		$(".editable").on("click", function() {
			$(this).children(".grade").trigger("edit");
		});

		// Edit grade manually (when no form is attached)
		$("table.gradebook_editable .grade").editable(ENTRADA_URL+"/api/gradebook.api.php", {
			placeholder: "-",
			indicator: loading_html,
			event: "edit",
			onblur: "submit",
			width: 40,
			cssclass: "editing",
			submitdata: function(value, settings) {
				return {
					grade_id: $(this).attr("data-grade-id"),
					assessment_id: $(this).attr("data-assessment-id"),
					proxy_id: $(this).attr("data-proxy-id"),
					return_type: "json"
				};
			},
			callback: function(value, settings) {
				
				ajaxGradeUpdateCallback(value, this)

	            if ($(this).hasClass("in-group")) {

	            	var groupItemCount = $(".in-group-"+$(this).attr("data-group-id")).length - 1;

	            	var _this = this;

	            	$(".in-group-"+$(this).attr("data-group-id")).each(function(i, item) {

	            		if ($(_this).attr("id") != $(item).attr("id")) {

	            			var grade_value = null;

	            			if (value !== "-") {
	            				grade = $.parseJSON(value);

	            				if (typeof grade == 'object') {
	            					grade_value = grade.grade_value;
	            				}
	            				else {
	            					grade_value = value;
	            				}
	            			}
	            			else {
	            				grade_value = "";
	            			}

	            			$.ajax({
	            				type: "POST",
	            				url: ENTRADA_URL+"/api/gradebook.api.php",
	            				data: {
	            					value: grade_value,
	            					grade_id: $(item).attr("data-grade-id"),
									assessment_id: $(item).attr("data-assessment-id"),
									proxy_id: $(item).attr("data-proxy-id"),
									return_type: "json"
	            				}
	            			})
	            			.done(function(value) {
	            				ajaxGradeUpdateCallback(value, item)
	            			})
	            		}
	            		
	            	})
	            }
			}
		})
		.keyup(editNextCell);
	}

	$("table.gradebook_editable .resubmission").editable(ENTRADA_URL + "/admin/gradebook/assessments?section=grade", {
		placeholder: "-",
		indicator: loading_html,
		onblur: "submit",
		width: 40,
		cssclass: "editing",
		submitdata: function(value, settings) {
			return {
				ajax: "ajax",
				method: "store-resubmit",
				aoption_id: $(this).attr("data-aoption-id"),
				aovalue_id: $(this).attr("data-aovalue-id"),
				assessment_id: $(this).attr("data-assessment-id"),
				proxy_id: $(this).attr("data-proxy-id")
			};
		},
		callback: function(data, settings) {
			var jsonResponse = JSON.parse(data);
			if (jsonResponse.status == "success") {
				if (jsonResponse.data.value > 0) {
					$(this).text(jsonResponse.data.value)
					.attr("data-aoption-id", jsonResponse.data.aoption_id)
					.attr("data-aovalue-id", jsonResponse.data.aovalue_id)
					.attr("data-proxy-id", jsonResponse.data.proxy_id)
				} else {
					$(this).text("-");
				}
			} else {
				$(this).text("-");
			}
		}
	})
	.keyup(editNextCell);

	$(".resubmissions.editable").on("click", function() {
		$(this).children(".resubmission").trigger("click");
	});

	$(".late-submission input").on("change", function(e) {
		var input = $(this);
		var value = "0";
		if ($(this).is(":checked")) {
			value = "1";
		}
		$.ajax({
			url: ENTRADA_URL + "/admin/gradebook/assessments?section=grade",
			data: "ajax=ajax&method=store-late&value=" + value + "&aoption_id=" + $(this).attr("data-aoption-id") + "&proxy_id=" + $(this).attr("data-proxy-id") + "&aovalue_id=" + $(this).attr("data-aovalue-id"),
			type: "POST",
			success: function(data) {
				var jsonResponse = JSON.parse(data);
				if (jsonResponse.status == "success") {
					input.attr("data-aovalue-id", jsonResponse.data.aovalue_id)
						 .attr("data-proxy-id", jsonResponse.data.proxy_id);
				}
			}
		})
		e.preventDefault();
	});

	$("#modal-preview-assessment-form").on("show", function() {
		$(".modal-body", $(this)).load(ENTRADA_URL + "/admin/gradebook/assessments?section=api-forms&method=get-rendered-form&form_id=" + FORM_ID + "&assessment_id=" + ASSESSMENT_ID + "&edit_comments=false")
	})
	.on("hide", function() {
		$(".modal-body", $(this)).empty();
	})

	// Import CSV Modal
	$(".btn-submit-import-grades").on("click", function(e) {
		e.preventDefault();
		$("form", $(this).parents(".modal")).submit();
	})

	// Export CSV
	$("#export-csv-button").on("click", function(e) {
		e.preventDefault();
		window.location = ENTRADA_URL + "/admin/gradebook?section=csv-download&id=" + COURSE_ID + "&cperiod_id=" + CPERIOD_ID + "&assessment_id=" + ASSESSMENT_ID + "&title=" + TITLE;
	})

	$.get(ENTRADA_URL + '/admin/gradebook/assessments?section=api-forms&method=get-grade-exceptions&assessment_id=' + ASSESSMENT_ID, function(data) {
		var json = $.parseJSON(data);

		if (json.status == "success") {

			if (json.results.length) {
				for (var i = 0; i < json.results.length; i++) {
					$("#exception_container").append(createGradeExceptionDOMElement(json.results[i]["proxy_id"], json.results[i]["fullname"], json.results[i]["aexception_id"], json.results[i]["grade_weighting"]))

					// hide any from the dropdown selector
					$("#exception_student_"+json.results[i]["proxy_id"]).hide()
				}
			}			
		}
		else {
			$("#exception-notice").show()
		}
	})

	$("#selector-dropdown-learner-grade-exceptions").on("change", function() {
		if ($(this).val() > 0) {
			var $this = $(this);
			var proxy_id = $this.val();
			var fullname = $("#exception_student_"+proxy_id).text();

			$.post(ENTRADA_URL + "/admin/gradebook/assessments", {
				section: "api-forms",
				method: "add_grade_exception",
				assessment_id: ASSESSMENT_ID,
				proxy_id: proxy_id
			}, function(data) {
				var json = $.parseJSON(data);

				if (json.status == "success") {
					// Hide empty message
					$("#exception-notice").hide()

					$('#exception_container').append(createGradeExceptionDOMElement(json.result["proxy_id"], fullname, json.result["aexception_id"], json.result["grade_weighting"]))

					// hide from dropdown
					$('#exception_student_'+proxy_id).hide();

					// Set select back to zero
					$this.val(0)
				}
			})
		}
	})

	$("#exception_container").on("click", ".remove", function(e) {
		e.preventDefault();
		var proxy_id = $(this).attr("data-proxy-id");

		$.post(ENTRADA_URL + "/admin/gradebook/assessments", {
			section: "api-forms",
			method: "remove_grade_exception",
			aexception_id: $(this).attr("data-aexception-id"),
			assessment_id: $(this).attr("data-assessment-id"),
			grade_weighting: $(this).val()
		}, function(data) {
			var json = $.parseJSON(data);

			if (json.status == "success") {
				// remove from exception list
				$("#proxy_"+proxy_id).remove();

				// show in dropdown selector
				$("#exception_student_"+proxy_id).show();

				// Show empty message if there are no more exceptions
				if ($("#exception_container").children().length == 0) {
					$("#exception-notice").show();
				}
			}
		})
	})
	.on("input", ".grade-weighting", debounce(function(e) {

		$.post(ENTRADA_URL + "/admin/gradebook/assessments", {
			section: "api-forms",
			method: "save_grade_exception",
			aexception_id: $(this).attr("data-aexception-id"),
			assessment_id: $(this).attr("data-assessment-id"),
			proxy_id: $(this).attr("data-proxy-id"),
			grade_weighting: $(this).val(),
		}, function(data) {
			var json = $.parseJSON(data);
		})
	}, 250))

	function createGradeExceptionDOMElement(proxy_id, fullname, aexception_id, grade_weighting) {
		var li = document.createElement("li");
		li.id = "proxy_" + proxy_id;

		var span = document.createElement("span");
		span.id = "name_" + proxy_id;

		var name = document.createTextNode(fullname);
		span.appendChild(name);

		var deleteLink = document.createElement("a");
		deleteLink.className = "remove";
		deleteLink.title = DELETE_LINK_TITLE_TEXT + " " +fullname
		deleteLink.setAttribute("data-aexception-id", aexception_id);
		deleteLink.setAttribute("data-proxy-id", proxy_id);
		deleteLink.setAttribute("data-assessment-id", ASSESSMENT_ID);

		var deleteIcon = document.createElement("img");
		deleteIcon.src = ENTRADA_URL + "/images/action-delete.gif";
		deleteIcon.alt = DELETE_EXCEPTION_TEXT;

		deleteLink.appendChild(deleteIcon);

		var inputContainer = document.createElement("span");
		inputContainer.className = "duration_segment_container";

		var weightingText = document.createTextNode(WEIGHTING_TEXT);

		var input = document.createElement("input");
		input.id = "input_student_exception_"+proxy_id;
		input.className = "duration_segment grade-weighting";
		input.name = "student_exception[]";
		input.value = grade_weighting;
		input.setAttribute("data-aexception-id", aexception_id);
		input.setAttribute("data-proxy-id", proxy_id);
		input.setAttribute("data-assessment-id", ASSESSMENT_ID);

		inputContainer.appendChild(weightingText);
		inputContainer.appendChild(input);

		li.appendChild(span);
		li.appendChild(deleteLink);
		li.appendChild(inputContainer);

		return li;
	}

	function debounce(func, wait, immediate) {
		var timeout;
		return function() {
			var context = this, args = arguments;
			var later = function() {
				timeout = null;
				if (!immediate) func.apply(context, args);
			};
			var callNow = immediate && !timeout;
			clearTimeout(timeout);
			timeout = setTimeout(later, wait);
			if (callNow) func.apply(context, args);
		};
	};

	$(".modal").on("show", function() {
		$(this).removeClass("hide");
	});
});