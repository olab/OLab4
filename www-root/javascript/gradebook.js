var ENTRADA_URL;
var marking_scheme_id = 1;

jQuery(document).ready(function($) {

	var loading_html = '<div class="spreadsheet-loading"></div>';

	var tableSelector;

	var table;

	$('.gradebook_edit').jqm({
		ajax: '@data-href',
		ajaxText: loading_html,
		trigger: $("#fullscreen-edit"),
		modal: true,
		toTop: true,
		overlay: 100,
		onShow: function(hash) {
			hash.w.css("z-index","500");
			hash.o.css("z-index","500");
			hash.w.show();
		},
		onLoad: function(hash) {
			initializeModalfunctions();
		},
		onHide: function(hash) {
			hash.o.hide();
			hash.w.hide();
		}
	});

	$("#fullscreen-edit").click(function(e) {
		e.preventDefault();
	});

	$('.gradebook_modal_close').live('click', function(e) {
		e.preventDefault();
		$('.gradebook_edit').jqmHide();
	});

	$('.gradebook_edit_add').live('click', function(e) {
		window.location = $("#gradebook_assessment_add").attr('href');
	});
	$('.gradebook_export').live('click', function(e) {
		var ids = [];
		$$('#assessment_ids').each(function(input) {
			ids.push($F(input));
		});
		if(ids.length > 0) {
			window.location = $("#gradebook_export_url").attr('value')+ids.join(',');
		} else {
			alert("There are no assessments to export for this cohort.");
		}
		return false;
	});
	$(".resubmit-button").on("click", function(e) {
		$(this).siblings(".resubmit").click();
		e.preventDefault();
	});
	$('.resubmit').on("click", function(e) {
		$(this).hide();
		var input = $(document.createElement("input"));
		input.attr("type", "text")
			.attr("data-id", $(this).attr("data-id"))
			.attr("data-proxy-id", $(this).attr("data-proxy-id"))
			.attr("data-aovalue-id", $(this).attr("data-aovalue-id"))
			.addClass("input-mini")
			.addClass("resubmit-input")
			.appendTo($(this).parent()).focus();
		e.preventDefault();
	});
	$('.resubmissions').on('blur', '.resubmit-input', function(e) {
		var input = $(this);
		$.ajax({
			url: ENTRADA_URL + "/admin/gradebook/assessments?section=grade",
			data: "ajax=ajax&method=store-resubmit&value=" + input.val() + "&aoption_id=" + input.attr("data-id") + "&proxy_id=" + input.attr("data-proxy-id") + "&aovalue_id=" + input.attr("data-aovalue-id"),
			type: "POST",
			success: function(data) {
				var jsonResponse = JSON.parse(data);
				if (jsonResponse.status == "success") {
					if (jsonResponse.data.value > 0) {
						input.siblings(".resubmit")
							.html(jsonResponse.data.value)
							.attr("data-aovalue-id", jsonResponse.data.aovalue_id)
							.attr("data-proxy-id", jsonResponse.data.proxy_id);
					} else {
						input.siblings(".resubmit").html("-");
					}
				} else {
					input.siblings(".resubmit").html("-");
				}
				input.hide();
				input.siblings(".resubmit").show();
				input.remove();
			}
		})
		e.preventDefault();
	});
	$(".late-submissions input").on("change", function(e) {
		var input = $(this);
		var value = "0";
		if ($(this).is(":checked")) {
			value = "1";
		}
		$.ajax({
			url: ENTRADA_URL + "/admin/gradebook/assessments?section=grade",
			data: "ajax=ajax&method=store-late&value=" + value + "&aoption_id=" + $(this).attr("data-id") + "&proxy_id=" + $(this).attr("data-proxy-id") + "&aovalue_id=" + $(this).attr("data-aovalue-id"),
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


	// Filter results based on search input
	$("#gradebook_spreadsheet_mark").on("keyup", "#input-search-learners",function () {
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
		$("#gradebook_spreadsheet_mark").on("click", ".no-form", function() {
			$(this).children(".grade-no-form").trigger("edit");
		});




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
			$(".modal-body", $(this)).load(ENTRADA_URL + "/admin/assessments/forms?section=api-forms&method=get-rendered-form&form_id=" + FORM_ID + "&assessment_id=" + ASSESSMENT_ID + "&edit_comments=false")
		})
		.on("hide", function() {
			$(".modal-body", $(this)).empty();
	});


	function initializeModalfunctions () {
		tableSelector = $("#gradebook_spreadsheet_mark").children("#datatable-student-list");

		// Init DataTable
		table = tableSelector.DataTable({
			// remove search box
			dom: "lrtip",

			// remove ability to set number of results per page
			lengthChange: false,

			// remove "1-N of N Entries"
			info: false,

			// disable pagination
			paging: false,

			autoWidth: false,

			columnDefs: [
				{ width: "200px", targets: "_all" }
			]




		});

		// Edit grade manually (when no form is attached)
		$("table.gradebook_editable .grade-no-form").editable(ENTRADA_URL+"/api/gradebook.api.php", {
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

				ajaxGradeUpdateCallback(value, this);

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


});

