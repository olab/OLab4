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

	$(".has-form .grade-editable").on("click", function(e) {
		e.preventDefault();
		var $this = $(this).children(".grade");
		var row_is = ($(this).is("td") ? "tr" : ($(this).is("a") ? "a" : ""));
		var parent_is = (row_is == "tr" ? "tbody" : (row_is == "a" ? "div" : ""));
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

			function setCheckboxCommentLabel(parent, context) {
				var label = $(".rubric-comment", parent).find("td label");
			
				if (label && label.hasClass("form-flagged")) {
					var closestTBody = $(context).closest("tbody");
					var allCheckBoxes= $(closestTBody).find("input[type='checkbox']");
					var flaggedChecked = false;

					$(allCheckBoxes).each(function() {
						var inputID = "#input-score-" + $(this).data("gairesponse-id");
						if ($(this).is(":checked") && $(inputID).hasClass("flagged")) {
							flaggedChecked = true;
						}
					});

					if (flaggedChecked) {
						$(label).addClass("form-required");
					} else {
						$(label).removeClass("form-required");
					}
				}
			}

			function setCommentLabel(parent, context) {

				var label = $(".rubric-comment", parent).find("td label");

				if (label && label.hasClass("form-flagged")) {
					
					if ($("#input-score-" + $(context).data("gairesponse-id")).hasClass("flagged")) {
						$(label).addClass("form-required");
					} else {
						$(label).removeClass("form-required");
					}
				}
			}

			function updateInputScores() {
				/*
				$(".input-score", $(this).parents(".item-response-view")).prop("type", "hidden");
				$("#input-score-" + $(this).data("gairesponse-id")).prop("type", "text");

				$(".text-score", $(this).parents(".item-response-view")).show();
				$("#text-score-" + $(this).data("gairesponse-id")).hide();
				*/
				$(".input-score", $(this).parents(".item-response-view")).prop("type", "hidden");
				$(".vertical-multiple-row", $(this).parents(".item-response-view")).removeClass("selected-mark-item");
				$("#vertical-multiple-row-" + $(this).data("gairesponse-id")).addClass("selected-mark-item");
				$(".text-score", $(this).parents(".item-response-view")).show();

				var is_disabled = $("#input-score-" + $(this).data("gairesponse-id")).attr("disabled");
				
				if (typeof is_disabled === 'undefined' || is_disabled === false) {
					$("#text-score-" + $(this).data("gairesponse-id")).hide();
					$("#input-score-" + $(this).data("gairesponse-id")).prop("type", "text");
				}
				setCommentLabel($(this).parents(".item-response-view"), this);
			}

			function updateSelectScores() {

				// Hide all inputs
				$(".input-score", $(this).parents(".item-response-view")).prop("type", "hidden");
				var is_disabled = $("#input-score-" + $(this).val()).attr("disabled");

				if ($(this).attr("multiple") == "multiple") {
					$('option', $(this)).each(function() {
						if ($(this).is(":selected") && (typeof is_disabled === 'undefined' || is_disabled === false)) {
							// Show matching input
							$("#input-score-" + $(this).val()).prop("type", "text");
							// Hide matching text
							$("#text-score-" + $(this).val()).hide();
						} else {
							// Hide matching input and show matching text
							$("#input-score-" + $(this).val()).prop("type", "hidden");
							$("#text-score-" + $(this).val()).show();
						}
					})
				}
				else {
					$(".text-score", $(this).parents(".item-response-view")).show();
					
					if (typeof is_disabled === 'undefined' || is_disabled === false) {
						$("#text-score-" + $(this).val()).hide();
						$("#input-score-" + $(this).val()).prop("type", "text");
					}
				}

				setCommentLabel($(this).parents(".item-response-view").closest("tbody"), $(this).find("option:selected"));
			}

			function updateCheckboxScores() {

				var is_disabled = $("#input-score-" + $(this).attr("value")).attr("disabled");

				if ($(this).is(":checked") &&  (typeof is_disabled === 'undefined' || is_disabled === false)) {
					$("#input-score-" + $(this).val()).prop("type", "text");
					$("#text-score-" + $(this).val()).hide();
				} else {
					$("#input-score-" + $(this).val()).prop("type", "hidden");
					$("#text-score-" + $(this).val()).show();
				}

				setCheckboxCommentLabel($(this).parents(".item-response-view"), this);
			}
			// Calculates the highest score for a given Item ID. 
			// In the case of checkboxes, calculate the total scores possible
            // Note this uses the hidden input 'item-response-score' which is the original score assigned to the form
			function getHighestScore(itemId) {
				var $scores = $('.item-response-score', $('#item-response-view-' + itemId));
				var $input = $('.proxy-scores', $('#item-response-view-' + itemId)).first();
				var highestScore = 0;

				if ($input.attr("type") == "checkbox" || $input.attr("multiple") == "multiple") {
					$scores.each(function() {
						if (parseFloat($(this).val()) > 0) {
							highestScore += parseFloat($(this).val() || 0);
						}
					})
				}
				else {
					$scores.each(function() {
						if (parseFloat($(this).val()) > highestScore) {
							highestScore = parseFloat($(this).val());
						}
					})
				}

				return parseFloat(highestScore || 0);
			}

            // Custom rounding function
            function roundDecimal(value, decimals) {
                return Number(Math.round(value + 'e' + decimals) + 'e-' + decimals);
            }

			// Get the calculated grade, out of 100, for the whole form
			function getCalculatedGrade() {
				var $selections = $(".proxy-scores:checked, select.proxy-scores option:selected");
				var weightedScore = 0;

				$selections.each(function() {
					var itemId = $(this).data("item-id");
					var weight = parseFloat($('#weight-item-' + itemId).text() || 0);
					var gairesponseId = $(this).val();
					var score = parseFloat($("#input-score-" + gairesponseId).val() || 0);
					var highestScore = getHighestScore(itemId);
					var weightedItemScore = parseFloat(((score || 0) / highestScore) * weight || 0);

					weightedScore += weightedItemScore;
				});

				// Round to 2 decimal places
				return roundDecimal(weightedScore, 4);
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
						} else {
							$("#custom-grade-value").hide();
							$('.custom-grade .assessment-suffix').hide();
						}

						firstLoad = false;
					}
				})

				/* hack to temporarily allow TA to change custom grade and item score; for testing adn debug purpose 
				$("#custom-grade").removeAttr("disabled").show();
				$("#custom-grade-value").removeAttr("disabled").show();
				$("input.input-score").removeAttr("disabled").prop("type", "text");
				$("span.text-score").attr("disabled","disabled").hide(); 
				/* ene hack code */
			}

			// Run the function upon opening the modal
			updateCalculatedGrade();

			function updateBadge() {
				// keep track of ungraded students
				var ungraded_count = 0;
				$(".has-form .grade-editable .grade").each(function() {
					if (!$(this).attr("data-grade-value")) {
						ungraded_count++;
					}
				});

				
				if ((ungraded_count == 0) || ((ungraded_count == 1) && !$this.attr("data-grade-value"))) {
					$(".btn-save-assignment.btn-save-go-to-next").hide();
				} else {
					$(".btn-save-assignment.btn-save-go-to-next").text("Save and Go to Next ("+ ungraded_count +")").show();
				}
			}
			// Run the function upon opening the modal
			updateBadge();

			// Update which inputs display
			$("input.item-control[type='radio']", $modal).on("click", updateInputScores);
			$("input.item-control[type='checkbox']", $modal).on("click", updateCheckboxScores);
			$("select.item-control", $modal).on("change", updateSelectScores);
			$(".item-control, .input-score", $modal).on("change", updateCalculatedGrade);
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
                var flagged_comment_items = {}; 
                var unfilled_flagged_comment = false;

				$selections.each(function() {
					var gairesponse_id = $(this).val();
					scores[gairesponse_id] = $("#input-score-" + gairesponse_id).val();

					// check for checked selection this flagged, per item-id.
					var data_item_id = $(this).attr("data-item-id");
					if ($("#input-score-" + gairesponse_id).hasClass("flagged")) {
						flagged_comment_items[data_item_id] = 1;
					} 
				});

                $comments.each(function() {
                    var item_id = $(this).attr('data-gafelement-id');
                    var data_item_id = $(this).attr('data-item-id');
                    var label = $(this).prev("label[for='"+$(this).attr("id")+"']");
                    comments[item_id] = $(this).val();

                    if (!$(this).val() && $(label).hasClass("form-required")) {
                        unfilled_flagged_comment = true;
                        return false;
                    } else if (!$(this).val() && $(label).hasClass("form-flagged") && flagged_comment_items[data_item_id]) {
                        unfilled_flagged_comment = true;
                        return false;
                    }
                });

                if (unfilled_flagged_comment) {
                    alert("Pleaes fill in all comments with asterisk.");
                    return;
                }

                var customGrade = null;
                if ($("#custom-grade").is(":checked")) {
                    if ($("#custom-grade-value").val().length > 0) {
                        customGrade = Math.round(parseFloat($("#custom-grade-value").val()) * 100) / 100;
                    }
                } else {
                    if ($("#enter-grade").length > 0 && $("#enter-grade").val().length > 0) {
                        customGrade = Math.round(parseFloat($("#enter-grade").val()) * 100) / 100;
                    }
                }

                function clickNextUngraded(curr_obj) {

                    if ($(curr_obj).is(":last-child")) {
                        var next_obj = $(curr_obj).closest(parent_is).find(row_is).eq(0);
                    } else {
                        var next_obj = $(curr_obj).next();
                    }

                    if ($(next_obj).find(".grade").eq(0).attr("id") == $this.attr("id")) {
                        return false;
                    }

                    var grade_obj = $(next_obj).find(".grade[data-grade-value='']").eq(0);

                    if (grade_obj.length) {
                        $(grade_obj).trigger("click");
                        return true;
                    } else {
                        clickNextUngraded(next_obj);
                    }
                }

				function updateDisplayedGrade(studentId) {

					var grade_item_parent = $("#grade_" + assessmentId + "_" + studentId).closest(".grade-editable");

					if (customGrade) {
						$("#grade_" + assessmentId + "_" + studentId).text(customGrade)
						$("#grade_" + assessmentId + "_" + studentId).attr("data-formatted-grade", customGrade)
						/*
						$.get(ENTRADA_URL + "/admin/assessments/forms?section=api-forms&method=get-storage-grade&grade=" + customGrade + "&assessment_id=" + assessmentId, function(data) {
							var json = $.parseJSON(data);
							$("#grade_" + assessmentId + "_" + studentId).attr("data-grade-value", json.storage_grade)
						})
						*/
						$.get(ENTRADA_URL + "/admin/assessments/forms?section=api-forms&method=get-storage-grade&grade=" + customGrade + "&assessment_id=" + assessmentId + "&proxy_id=" + studentId, function(data) {
							var json = $.parseJSON(data);
							$("#grade_" + assessmentId + "_" + studentId).attr("data-grade-value", json.storage_grade);

							getAjaxFormattedGrade(function(data) { // (isNaN(json.formatted_grade) ? json.formatted_grade: json.complete_grade)
								var json = $.parseJSON(data);
								$("#grade_" + assessmentId + "_" + studentId).parent().find("h4.label").eq(0).text("Grade");
								$("#grade_" + assessmentId + "_" + studentId).parent().find("p.student-grade").eq(0).text(json.formatted_grade) ;
							}, json.storage_grade);
						});
					}
					else {
						getAjaxFormattedGrade(function(data) {
							var json = $.parseJSON(data);
							$("#grade_" + assessmentId + "_" + studentId).text(json.formatted_grade);
							$("#grade_" + assessmentId + "_" + studentId).attr("data-formatted-grade", json.formatted_grade);
							$("#grade_" + assessmentId + "_" + studentId).parent().find("h4.label").eq(0).text("Grade");
							$("#grade_" + assessmentId + "_" + studentId).parent().find("p.student-grade").eq(0).text(json.formatted_grade);
							$("#grade_" + assessmentId + "_" + studentId).attr("data-grade-value", getCalculatedGrade());
						});
					}

					$(grade_item_parent).find('.gradesuffix').removeClass("hide");

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

                // check for unfilled item/rubric
                var unfilled_rubric = false;
                $(".item-response-view").each(function () {

                    var data_item_id = $(this).prop("id").split("-").pop();

                    if (data_item_id) {
                        var fill_count = $(this).find("[data-item-id='"+data_item_id+"']").filter(function() {
                                
                            if ($(this).is("select")) {
                                return ($(this).find("option:selected").val() !== null && !isNaN(parseInt($(this).find("option:selected").val())));
                            } else if (($(this).is(":radio") || $(this).is(":checkbox")) &&  $(this).is(':checked')) { 
                                return $(this).val() !== null;
                            } else {
                                return false;
                            }
                        }).length;

                        if (fill_count == 0) {
                            unfilled_rubric = true;
                        }
                    }
                });

                if (unfilled_rubric) {
                    alert("Pleaes make a selection for each rubric item.");
                    return;
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

				$modal.modal("hide");

				if ($link.hasClass("btn-save-go-to-next")) {
					clickNextUngraded($this.parents(row_is));
				}
			});

			$(".datepicker").datepicker({
		        "dateFormat": "yy-mm-dd"
		    });

		    $(".datepicker-icon").on("click", function () {

		        if (!$(this).prev("input").is(":disabled")) {
		            $(this).prev("input").focus();
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