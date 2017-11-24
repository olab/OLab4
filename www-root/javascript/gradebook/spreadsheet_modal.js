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
 * Gradebook / Assignment Marking In-Browser
 * 
 * @author Organization: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 Queen"s University. All Rights Reserved.
 * 
 */

jQuery(document).ready(function($) {
	var $modal = $("#modal-mark-assignment");

	$("#gradebook_spreadsheet_mark").on("click", ".has-form",function(e) {

		e.preventDefault();
		var $this = $(this).children(".grade");

		var assignment = {
			id: $this.attr("data-assignment-id"),
			title: $this.attr("data-assignment-title"),
		};

		var studentId = $this.attr("data-proxy-id");
		var studentName = $this.attr("data-student-name");
		var courseId = $this.attr("data-course-id");
		var organisationId = $this.attr("data-organisation-id");
		var assessmentId = $this.attr("data-assessment-id");
		var formId = $this.attr("data-form-id");
		var gradeId = $this.attr("data-grade-id");
		var gradeValue = $this.attr("data-grade-value");
		var formattedGrade = $this.attr("data-formatted-grade");
		var inGroup = $this.hasClass("in-group");
		var groupName = $this.attr("data-group-name");
		var groupId = $this.attr("data-group-id");

		// Show modal
		$modal.modal("show");

		if (inGroup) {

			var viewGroupMembersLink = document.createElement("a");
			viewGroupMembersLink.href = "#";
			viewGroupMembersLink.className = "pull-right view-group-members";
			viewGroupMembersLink.appendChild(document.createTextNode(VIEW_GROUP_MEMBERS_TEXT));

			$(".modal-header", $modal).append(viewGroupMembersLink);

			// Display popover with list of group members
			$(".view-group-members", $modal).popover({
				placement: 'bottom',
				trigger: 'click',
				html: true,
				content: function() {
					var ul = document.createElement("ul");
					ul.className = "unstyled";
					$(".in-group-" + groupId).each(function(i, grade) {
						var li = document.createElement("li");
						var studentName = document.createTextNode($(grade).attr("data-student-name"));
						li.appendChild(studentName);
						ul.appendChild(li);
					})

					return ul;
				}
			})
			.on('show', function(e) {
			    e.stopPropagation();
			})
			.on('hidden', function(e) {
			    e.stopPropagation();
			});

			$modal.on('click', function(e) {
			  if (typeof $(e.target).data('original-title') == 'undefined') {
			    $('[data-original-title]').popover('hide');
			  }
			});
		}

		// This is already being done in css, but this is for browsers who can"t support css calc()
		$(".modal-body", $modal).height($modal.height() - 142);

		// Body gets overflow:hidden to remove body scrolling while modal is open
		$("body").addClass("modal-open");

		$("h3", $modal).text((assignment.title ? assignment.title + ": " : "") + studentName);

		if (inGroup) {
			$("h3", $modal).append(" - " + groupName);
		}

		var hasFiles = false;
		var proxyQuery = "";

		if (inGroup) {
			$(".in-group-" + groupId).each(function(i, grade) {
				proxyQuery += "&proxy_id[]=" + $(grade).attr("data-proxy-id");
			})
		}
		else {
			proxyQuery = "&proxy_id=" + studentId;
		}

		$(".selector-documents", $modal).load(ENTRADA_URL + "/admin/assessments/forms?section=api-forms&method=get-rendered-file-selector" + proxyQuery + "&assessment_id=" + assessmentId + "&assignment_id=" + assignment.id + "&course_id=" + courseId, function(e) {
			var afversionId = $(".selector-documents option:first", $modal).val();

			if (afversionId) {
				$(".file", $modal).load(ENTRADA_URL + "/admin/assessments/forms?section=api-forms&method=get-rendered-assignment-file&assessment_id=" + assessmentId + "&afversion_id=" + afversionId + "&organisation_id=" + organisationId + "&proxy_id=" + studentId);
				$(".file, .marking-scheme", $modal).removeAttr("style");
			}
			else {
				$(".file", $modal).css("width", "0").css("border", "none");
				$(".marking-scheme", $modal).css("width", "98%");
			}

			$("#selector-student-files").on("change", function(e) {
				var afversionId = $(this).val();
				$(".file", $modal).load(ENTRADA_URL + "/admin/assessments/forms?section=api-forms&method=get-rendered-assignment-file&assessment_id=" + assessmentId + "&afversion_id=" + afversionId + "&organisation_id=" + organisationId + "&proxy_id=" + studentId);
			})
		});

		$(".marking-scheme", $modal).load(ENTRADA_URL + "/admin/assessments/forms?section=api-forms&method=get-rendered-form&form_id=" + formId + "&assessment_id=" + assessmentId + "&proxy_id=" + studentId + "&edit_comments=true", function(e) {
			$(".loading", $modal).hide();

			function updateInputScores() {
				$(".input-score", $(this).parents(".item-response-view")).prop("type", "hidden");
				$("#input-score-" + $(this).data("gairesponse-id")).prop("type", "text");

				$(".text-score", $(this).parents(".item-response-view")).show();
				$("#text-score-" + $(this).data("gairesponse-id")).hide();
			}

			function updateSelectScores() {

				// Hide all inputs
				$(".input-score", $(this).parents(".item-response-view")).prop("type", "hidden");

				if ($(this).attr("multiple") == "multiple") {
					$('option', $(this)).each(function() {
						if ($(this).is(":selected")) {
							// Show matching input
							$("#input-score-" + $(this).val()).prop("type", "text");

							// Hide matching text
							$("#text-score-" + $(this).val()).hide();
						}
						else {
							// Hide matching input and show matching text
							$("#input-score-" + $(this).val()).prop("type", "hidden");
							$("#text-score-" + $(this).val()).show();
						}				
					})
				}
				else {
					// Show matching input
					$("#input-score-" + $(this).val()).prop("type", "text");

					// Show all text
					$(".text-score", $(this).parents(".item-response-view")).show();

					// Hide matching text
					$("#text-score-" + $(this).val()).hide();
				}
			}

			function updateCheckboxScores() {
				if ($(this).is(":checked")) {
					$("#input-score-" + $(this).val()).prop("type", "text");
					$("#text-score-" + $(this).val()).hide();
				}
				else {
					$("#input-score-" + $(this).val()).prop("type", "hidden");
					$("#text-score-" + $(this).val()).show();
				}
			}

			// Calculates the highest score for a given Item ID. 
			// In the case of checkboxes, calculate the total scores possible
			function getHighestScore(itemId) {
				var $scores = $('.input-score', $('#item-response-view-' + itemId));
				var $input = $('.proxy-scores', $('#item-response-view-' + itemId)).first();
				var highestScore = 0;

				if ($input.attr("type") == "checkbox" || $input.attr("multiple") == "multiple") {
					$scores.each(function() {
						if ($(this).val() > 0) {
							highestScore += parseFloat($(this).val() || 0);
						}
					})
				}
				else {
					$scores.each(function() {
						if ($(this).val() > highestScore) {
							highestScore = $(this).val();
						}
					})
				}

				return parseFloat(highestScore || 0);
			}

			// Get the calculated grade, out of 100, for the whole form
			function getCalculatedGrade() {
				var $selections = $(".proxy-scores:checked, select.proxy-scores option:selected");

				var weightedScore = 0;

				$selections.each(function() {
					var itemId = $(this).data("item-id");
					var weight = parseInt($('#weight-item-' + itemId).text() || 0);
					var gairesponseId = $(this).val();
					var score = parseFloat($("#input-score-" + gairesponseId).val() || 0);
					var highestScore = getHighestScore(itemId);

					var weightedItemScore = parseFloat(((score || 0) / highestScore) * weight || 0);

					weightedScore += weightedItemScore;
				});

				// Round to 2 decimal places
				return Math.round(weightedScore * 100) / 100;
			}

			// Get formatted grade via ajax
			function getAjaxFormattedGrade(onSuccess, grade) {
				var grade = grade ? grade : getCalculatedGrade();

				return $.ajax({
					url: ENTRADA_URL + "/admin/assessments/forms?section=api-forms&method=get-student-grade&assessment_id=" + assessmentId + "&grade=" + grade + "&proxy_id=" + studentId,
					success: onSuccess
				});
			}

			var firstLoad = true;

			// Update the calculated grade in the modal footer
			function updateCalculatedGrade() {
				getAjaxFormattedGrade(function(data) {
					var json = $.parseJSON(data);
					$(".calculated-grade").text(json.complete_grade)

					if (firstLoad) {

						// Select custom grade if it differs, only on first load
						if (json.formatted_grade != formattedGrade) {
							$('#custom-grade').prop("checked", true);
							$("#custom-grade-value").val(formattedGrade).show();
						}
						else {
							$("#custom-grade-value").hide();
							$('.custom-grade .assessment-suffix').hide();
						}

						firstLoad = false;
					}					
				})
			}

			// Run the function upon opening the modal
			updateCalculatedGrade();

			// Update which inputs display
			$("input.item-control[type='radio']", $modal).on("click", updateInputScores);
			$("input.item-control[type='checkbox']", $modal).on("click", updateCheckboxScores);
			$("select.item-control", $modal).on("change", updateSelectScores);

			$(".item-control, .input-score", $modal).on("change click input", updateCalculatedGrade);

			$("input.item-control[type='radio']:checked", $modal).each(updateInputScores);
			$("input.item-control[type='checkbox']:checked", $modal).each(updateCheckboxScores);
			$("select.item-control", $modal).each(updateSelectScores);

			$("#custom-grade").on("click", function() {
				if ($(this).is(":checked")) {
					$("#custom-grade-value").show();
					$('.custom-grade .assessment-suffix').show();
				}
				else {
					$("#custom-grade-value").hide();
					$('.custom-grade .assessment-suffix').hide();
				}
			});

			// Trigger ajax call upon "save"
			$(".btn-save-assignment").on("click", function(e) {
				var $selections = $(".proxy-scores:checked, select.proxy-scores :selected");
                var $comments = $(".rubric-comment textarea");
				var $link = $(this);
				var scores = {};
                var comments = {};

				$selections.each(function() {
					var gairesponse_id = $(this).val();
					scores[gairesponse_id] = $("#input-score-" + gairesponse_id).val();
				});

                $comments.each(function() {
                    var item_id = $(this).attr('data-gafelement-id')
                    comments[item_id] = $(this).val();
                });

				if ($("#custom-grade").is(":checked")) {
					if ($("#custom-grade-value").val().length > 0) {
						var customGrade = $("#custom-grade-value").val();
					}
				}

				function updateDisplayedGrade(studentId) {
					if (customGrade) {
						$("#grade_" + assessmentId + "_" + studentId).text(customGrade)
						$("#grade_" + assessmentId + "_" + studentId).attr("data-formatted-grade", customGrade)

						$.get(ENTRADA_URL + "/admin/assessments/forms?section=api-forms&method=get-storage-grade&grade=" + customGrade + "&assessment_id=" + assessmentId, function(data) {
							var json = $.parseJSON(data);
							$("#grade_" + assessmentId + "_" + studentId).attr("data-grade-value", json.storage_grade)
						})
					}
					else {
						getAjaxFormattedGrade(function(data) {
							var json = $.parseJSON(data);
							$("#grade_" + assessmentId + "_" + studentId).text(json.formatted_grade)
							$("#grade_" + assessmentId + "_" + studentId).attr("data-formatted-grade", json.formatted_grade)
							$("#grade_" + assessmentId + "_" + studentId).attr("data-grade-value", getCalculatedGrade())
						})
					}

					$("#grade_" + assessmentId + "_" + studentId).next(".gradesuffix").show();
				}

				var data = {
					assessment_id: assessmentId,
					proxy_id: studentId,
					scores: scores,
                    comments: comments,
					method: "save-assessment-proxy-scores",
					calculated_grade: getCalculatedGrade()
				}

				customGrade ? data.custom_grade = customGrade : '';

				if (inGroup) {
					$(".in-group-" + groupId).each(function(i, grade) {
						data.proxy_id = $(grade).attr("data-proxy-id");
						$.post(ENTRADA_URL + "/admin/assessments/forms?section=api-forms&assessment_id=" + assessmentId, data);
						updateDisplayedGrade(data.proxy_id);
					});
				}
				else {
					$.post(ENTRADA_URL + "/admin/assessments/forms?section=api-forms&assessment_id=" + assessmentId, data);
					updateDisplayedGrade(studentId);
				}

				$modal.modal("hide")

				if ($link.hasClass("btn-save-go-to-next")) {
					$this.parents("tr").next().find(".grade-editable").trigger("click");
				}
			});
		});
	});

	$modal.on("hidden", function(e) {
		$("body").removeClass("modal-open");
		$(".file, .marking-scheme", $modal).empty();
		$(".btn-save-assignment").off("click");
		$(".view-group-members", $modal).remove();
		$(".view-group-members", $modal).popover("destroy");
		$(".loading", $modal).show();
		$("#custom-grade").prop("checked", false);
		$("#custom-grade-value").val("");
	});

	// Upon resizing window, reset .modal-body height
	var resizeTimer;
	$(window).on("resize", function(e) {
	  clearTimeout(resizeTimer);
	  resizeTimer = setTimeout(function() {

	    $(".modal-body", $modal).height($modal.height() - 142);

	  }, 250);
	});


});