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
 * Gradebook / View
 * 
 * @author Organization: bitHeads, Inc.
 * @author Developer: Steve Yang <sy49@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 * 
 */

jQuery("document").ready(function($) {

	var tableSelector = "#datatable-assessments";

	// init tooltips
	$(".btn-tooltip").tooltip()
	// this is to counteract a known namespace conflict bug with Prototype https://github.com/twbs/bootstrap/issues/6921
	.on("hidden.bs.popover", function() {
		$(this).show();
	});

	// generate table data
	/*
	var table = $(tableSelector).DataTable({
		// remove search box
		dom: "lrtip",

		// remove ability to set number of results per page
		lengthChange: false,

		// get data from api
		ajax: {
			url: ENTRADA_URL + '/admin/gradebook',
			type: 'GET',
			data: function(d) {
				d.section = 'api-assessments',
				d.method = 'list',
				d.id = COURSE_ID,
				d.cperiod_id = $("#selector-select-period").val()
			},
		},

		// callback function run after initial ajax call
		initComplete: function(settings, json) {
			dataTableAjaxCallback(json)
		},

		// set no results message
		language: {
			emptyTable: NO_RESULTS_MESSAGE
		},

		// remove "1-N of N Entries"
		info: false,

		// disable pagination
		paging: false,

		// allow row reordering
		rowReorder: {
			selector: '.fa-grip-row',
			dataSrc: 'order',
		},

		// set which columns appear in table. orderable means the user ability to sort that column asc or desc
		columns: [
			{ 
				data: 'order',
				visible: false,
			},
			{ 
				data: 'name',
				render: function(data, type, row) {
					var grabIcon = '<i class="fa fa-grip fa-grip-row"></i>';
					var checkbox = '<input class="checkbox-assessment" type="checkbox" name="assessments[]" value="'+row.assessment_id+'">';
					return '<ul class="inline"><li>' + grabIcon + '</li><li>' + checkbox + '</li><li>' + getAssessmentLink(data, row) + '</li></ul>'
				},
				orderable: false
			},
			{ 
				data: 'grade_weighting',
				render: function(data, type, row) {
					return getAssessmentLink(data + '%', row)
				},
				orderable: false
			},
			{ 
				data: 'assignment_title',
				render: function(data, type, row) {
					if (row.assignment_id) {
						return getAssignmentLink(row)
					}

					return getNewAssignmentLink(row)
				},
				orderable: false
			},
			{ 
				data: 'views',
				orderable: false
			},
			{
				data: 'edit',
				class: 'td-edit',
				render: function(data, type, row) {
					var content = '<i class="fa fa-pencil center-block"></i>'

					return getEditAssessmentLink(content, row)
				},
				orderable: false
			},
		]
	})
	

	// row reorder event
	table.on('row-reorder', function(e, diff, edit) {
		var _this = this;

		var assessmentsOrder = new Array();

		for ( var i = 0; i < diff.length; i++ ) {

			var assessment = {
				assessment_id: table.row(diff[i].node).data().assessment_id,
				order: diff[i].newPosition
			};

        	assessmentsOrder.push(assessment);
        }

		$.post('/admin/gradebook?section=api-assessments&method=set-order', { assessments: assessmentsOrder });
	})
	*/

	// on selector change, reload table via ajax
	$("#selector-select-period").on("change", function(e) {
		// table.ajax.reload(dataTableAjaxCallback)
		reloadTable();

		// get download CSV url
		$(".btn-download-csv").attr("href", csvDownloadUrl + "&cperiod_id=" + $("#selector-select-period").val());

		$("#gradebook_assessment_add").attr("href", gradebook_assessment_add + "&cperiod_id=" + $("#selector-select-period").val());
	});

	// undisable checkbox buttons if any are checked
	$(this).on("change", tableSelector + " :checkbox", function(e) {
		// handleCheckboxChange(this);
		if ($(":checkbox:checked", tableSelector).length) {
			// row_type keeps track of selected inner or outer row, excluding header row
			var row_type = {};

			$(":checkbox:checked", tableSelector).each(function () {
				var parent = $(this).closest("div.ui-sortable");
				
				if (parent.hasClass("outer")) {
					if (!$(this).hasClass("group-checkbox-assessment")) {
						row_type["outer"] = 1;
					} else {
						row_type["group"] = 1;
					}
				} else {
					row_type["inner"] = 1;
				}
			});

			if (Object.keys(row_type).length > 1) { // checking mixed-typed rows disable the buttons
				// $(".btn-add-assessments-to-collection").addClass("disabled");
				$(".btn-toolbar .btn-checkbox").addClass("disabled");
			} else if (row_type["inner"]) {
				$(".btn-toolbar .btn-checkbox").removeClass("disabled");
				$(".btn-add-assessments-to-collection").removeClass("btn-default").addClass("btn-danger").text("Remove From Collection").data("type", "remove").removeClass("disabled");
				$(".btn-delete-assessments").data("type", "row");
			} else if (row_type["outer"]) {
				$(".btn-toolbar .btn-checkbox").removeClass("disabled");
				$(".btn-add-assessments-to-collection").removeClass("btn-danger").addClass("btn-default").text("Add To Collection").data("type", "add").removeClass("disabled");
				$(".btn-delete-assessments").data("type", "row");
			} else if (row_type["group"]) {
				$(".btn-toolbar .btn-checkbox").removeClass("disabled");
				$(".btn-add-assessments-to-collection").removeClass("btn-default").addClass("btn-danger").text("Empty Collection").data("type", "empty").removeClass("disabled");
				$(".btn-copy-assessments").addClass("disabled");
				$(".btn-delete-assessments").data("type", "group");
			}
		} else {
			$(".btn-toolbar .btn-checkbox").addClass("disabled");
		}
	});

	var delay = (function(){
	    var timer = 0;
	    return function(callback, ms){
	        clearTimeout (timer);
	        timer = setTimeout(callback, ms);
	    };
	})();

	// filter results based on search input
	$("#input-search-assessments").on("keyup", function () {
	    var search_terms = this;

	    delay(function(){
	        reloadTable($(search_terms).val());
	    }, 500);
	});

	// prevent normal form submission for search box
	$("#search-assessments").on("submit", function(e) {
		e.preventDefault();
	});
	/*
	// generate links for dataTable cells
	function getAssessmentLink(data, row) {
		return '<a href="'+ENTRADA_URL+'/admin/gradebook/assessments?section=grade&id='+COURSE_ID+'&assessment_id='+row.assessment_id+'">'+data+'</a>'
	}

    function getEditAssessmentLink(data, row) {
        return '<a href="'+ENTRADA_URL+'/admin/gradebook/assessments?section=edit&id='+COURSE_ID+'&assessment_id='+row.assessment_id+'">'+data+'</a>'
    }

	function getAssignmentLink(row) {
		return '<a href="'+ENTRADA_URL+'/admin/gradebook/assignments?section=grade&id='+COURSE_ID+'&assignment_id='+row.assignment_id+'">'+VIEW_ASSIGNMENT_TEXT+'</a>'
	}

	function getNewAssignmentLink(row) {
		return '<a href="'+ENTRADA_URL+'/admin/gradebook/assignments?section=add&id='+COURSE_ID+'&assessment_id='+row.assessment_id+'">'+NEW_ASSIGNMENT_TEXT+'</a>'
	}

	// frontend changes made when an ajax call is made through dataTable
	function dataTableAjaxCallback(json) {
		var totalWeight = 0;

		for (var i = 0; i < json.data.length; i++) {
			totalWeight = totalWeight + parseInt(json.data[i].grade_weighting)
		}

		$('#grade-weighting').text(totalWeight);

		// display total weight if there are weight amounts from the ajax call
		if (totalWeight > 0) {
			$('.total-grade-weighting').removeClass('hide')
		}
		else {
			$('.total-grade-weighting').addClass('hide')
		}

		// if assessments found, display the number of assessments and btn-toolbar
		if (json.data.length) {
			$('.number-of-assessments').text(json.data.length)
			$('.assessments-found').removeClass('hide')

			$('.btn-toolbar').removeClass('hide');
		}
		else {
			$('.assessments-found').addClass('hide')
			$('.btn-toolbar').addClass('hide');
		}

		// get download CSV url
		var csvDownloadUrl = $('.btn-download-csv').attr('href');

		$('.btn-download-csv').attr('href', addParameterToUrl('cperiod_id=' + $("#selector-select-period").val(), csvDownloadUrl));

		// Set checkbox buttons to disabled
		$('.btn-checkbox').addClass('disabled')
	}
	*/
	// Add param to a url
    function addParameterToUrl(param, url){
        url += (url.split("?")[1] ? "&" : "?") + param;
        return url;
    }

    function reloadTable(search) {
        if (typeof(search) === "undefined") {
            search = null;
        }

        $.get(ENTRADA_URL + "/admin/gradebook?section=api-assessments&method=load-table&id=" + COURSE_ID + "&cperiod_id=" + $("#selector-select-period").val() + (search ? "&search="+search : "" ))
        .done(function(json) {
            // if cperiod is the same as current, perform new ajax call
            // var jsonResponse = JSON.parse(res);
            if (json.status == "success") {
                $("#form-assessments").empty().append(""+json.data);
                setGroupEdit();
                // if we have entered some search terms, then disable sorting
                if (!search) {
                    setSortable();
                }
                updateTotalGradeWeighting();

                $("#modal-add-to-collection").modal("hide");
                $("#modal-copy-assessments").modal("hide");
                $("#modal-delete-assessments").modal("hide");
                $(".btn-toolbar .btn-checkbox").addClass("disabled");
            } else {
                console.log("unable to fetch the data table");
            }
        })
        .fail(function(e) {
            console.log(e);
        });
    }

	// Open "copy assessments" modal
	$(".btn-copy-assessments").on("click", function(e) {
		e.preventDefault();

		if (!$(this).hasClass("disabled")) {
			$("#modal-copy-assessments").modal("show");
		}
	});

	// Copy assessments button
	$(".btn-submit-copy-assessments").on("click", function(e) {
		e.preventDefault();

		var $this = $(this);

		// Get original button text for later
		var originalText = $this.text();

		// Disable this button and change text before ajax call occurs
		$this.prop("disabled", true).text(COPY_ASSESSMENTS_TEXT);

		// Get cperiod_id
		var new_cperiod_id = $("#selector-copy-assessments").val();

		// Get serialized data
		var assessmentsToCopy = $("#form-assessments").serialize();

		// Make ajax call
		$.post(ENTRADA_URL +"/admin/gradebook?section=api-assessments&method=copy&id="+COURSE_ID+"&cperiod_id="+new_cperiod_id, {
			assessments: assessmentsToCopy
		})
		.done(function(res) {
			$(".btn-submit-copy-assessments").text("Copy Assessments");
			reloadTable();
		})
		.fail(function(e) {
			console.log(e);
		})
		.always(function(e) {
            $this.prop("disabled", false).text(originalText);
            $("#modal-copy-assessments").modal("hide");
        });
	});

	// Open "delete assessments" modal
    $(".btn-delete-assessments").on("click", function(e) {
        e.preventDefault();

        if (!$(this).hasClass("disabled")) {

            if ($(this).data("type") == "group") {
                // no assessment will be deleted, but rather, the collection group is emptied and then deleted.
                var collections = $("#form-assessments").serialize();

                $.post(ENTRADA_URL + "/admin/gradebook?section=api-assessments&method=empty-collection", {
                    collections: collections
                })
                .done(function(res) {
                    // Reload table when it"s done to reflect server-side changes
                    $.post(ENTRADA_URL + "/admin/gradebook?section=api-assessments&method=delete-collection", {
                        collections: collections
                    })
                    .done(function(res) {
                        reOrderAssessments();
                    })
                    .fail(function(e) {
                        console.log(e);
                    });
                })
                .fail(function(e) {
                    console.log(e);
                });
            } else { // $(this).data("type") == "row"
                $("#modal-delete-assessments").modal("show");
            }
        }
    });

		// Delete assessments button
	$(".btn-submit-delete-assessments").on("click", function(e) {
		e.preventDefault();

		var $this = $(this);

		// Get original button text for later
		var originalText = $this.text();

		// Disable this button and change text before ajax call occurs
		$this.prop("disabled", true).text(COPY_ASSESSMENTS_TEXT);

		// Get serialized data
		var assessmentsToDelete = $("#form-assessments").serialize();

		// Make ajax call
		$.post(ENTRADA_URL + "/admin/gradebook?section=api-assessments&method=delete&id=" + COURSE_ID, {
			assessments: assessmentsToDelete
		})
		.done(function(res) {
			$(".btn-submit-delete-assessments").text("Delete Assessments");
			reloadTable();
			// Reload table when it"s done to reflect server-side changes
			// table.ajax.reload(dataTableAjaxCallback);
		})
		.fail(function(e) {
			console.log(e);
		})
		.always(function(e) {
			$this.prop("disabled", false).text(originalText);
			$("#modal-delete-assessments").modal("hide");
		});
	});

    $(".btn-add-assessments-to-collection").on("click", function(e) {
        e.preventDefault();

        if (!$(this).hasClass("disabled")) {
             // remove assessment from collection

             if ($(this).data("type") == "empty") {
                var collections = $("#form-assessments").serialize();

                $.post(ENTRADA_URL + "/admin/gradebook?section=api-assessments&method=empty-collection", {
                    collections: collections
                })
                .done(function(res) {
                    // Reload table when it's done to reflect server-side changes
                    reOrderAssessments();
                    // reloadTable();
                })
                .fail(function(e) {
                    console.log(e);
                })
            } else if ($(this).data("type") == "remove") {
                var assessments = $("#form-assessments").serialize();

                $.post(ENTRADA_URL + "/admin/gradebook?section=api-assessments&method=remove-from-collection", {
                    assessments: assessments
                })
                .done(function(res) {
                    // Reload table when it's done to reflect server-side changes
                    reOrderAssessments();
                    // reloadTable();
                })
                .fail(function(e) {
                    console.log(e);
                })
            } else { // $(this).data("type") == "add"
                $("#assessment-collections-select").empty();
                $("#assessment-collections-title").val("");
                $("#assessment-collections-desc").val("");
                $("#assessment-collections-id").val("");

                // fetch Collection ID, Title and Description through ajax call
                $.get(ENTRADA_URL + "/admin/gradebook?section=api-assessments&method=fetch-collection-list&id=" + COURSE_ID)
                .done(function(json) {
                    
                    $("#assessment-collections-select").empty().append("<option value=\"new-collection\">- Create New Collection -</option>");
                    
                    if (json.status == "success") {
                        $("#assessment-collections-select").append(json.data);
                    } else {
                        console.log("unable to fetch assessment collections");
                    }
                    $("#assessment-collections-title").val("").removeAttr("disabled");
                    $("#assessment-collections-desc").val("").removeAttr("disabled");
                    $(".btn-submit-add-to-collection").data("type","new").empty().append("<span class=\"icon-plus\"></span>Add to Collection").show();
                    $(".btn-cancel-add-to-collection").text("Cancel"); 
                    $("#assessment-collections-select").show().find("option:first").attr("selected", true);
                    $("#add-to-collection-message").empty();
                    $("#modal-add-to-collection .modal-header h3").text("Add Assessments to Collection");
                    $("#modal-add-to-collection").modal("show");
                })
                .fail(function(e) {
                    console.log(e);
                });
            }
        }
    });

    $(".btn-submit-add-to-collection").on("click", function(e) {
        e.preventDefault();
        var type = $(this).data("type");
        // var selected_collection_id = null;

        if (type == "new") {
            var title = $("#assessment-collections-title").val();
            var description = $("#assessment-collections-desc").val();

            if (title) {

                $.post(ENTRADA_URL + "/admin/gradebook?section=api-assessments&method=add-collection", {title: title, description: description, id: COURSE_ID})
                .done(function(json) {
                    
                    if (json.status == "success") {
                        var selected_val = $("#assessment-collections-select").append(json.data).find("option:last").attr("selected", true).val();
                        handleSelect(selected_val);
                        handleAddToSelection(selected_val);
                       
                    } else if (json.status == "abort") {
                        $("#assessment-collections-select").val(json.collection_id);
                        handleSelect(json.collection_id);
                        $("#add-to-collection-message").html("<strong>Assessment collection " + title + " already exists.</strong>");
                    } else {
                        console.log("unable to create a new collections");
                        return false;
                    }
                })
                .fail(function(e) {
                    console.log(e);
                    return false;
                });
            } else {
                alert("Please fill in the title field.");
                return false;
            }
        } else if (type == "old") {
            handleAddToSelection($("#assessment-collections-select").val());
        } else { //type = "edit" up update an existing collection
            var collection_id = $("#assessment-collections-id").val();
            var title = $("#assessment-collections-title").val();
            var description = $("#assessment-collections-desc").val();

            if (title) {

                $.post(ENTRADA_URL + "/admin/gradebook?section=api-assessments&method=update-collection", {collection_id: collection_id, title: title, description: description})
                .done(function(json) {
                    
                    if (json.status == "success") {
                            reloadTable();
                        } else {
                            console.log("unable to update a new collections");
                            return false;
                        }
                    })
                .fail(function(e) {
                    console.log(e);
                    return false;
                })
                .always(function(e) {
                    $("#modal-add-to-collection").modal("hide");
                });
            } else {
                alert("Pleaes fill in the title field.");
                return false;
            }
        }
    });

    function getAssessmentIds(checked_only) {
      
        var parent = $("div.ui-sortable.outer");
        var rtrn = [];

        $(parent).find("div.row").filter(function() { 
            if (checked_only) {
                return $(this).find("input.checkbox-assessment").is(":checked") && $(this).attr("data-assessment-id") != null;
            } else {
                return $(this).attr("data-assessment-id") != null; 
            }
        }).each(function (i, v) {
            rtrn[i] = $(v).attr("data-assessment-id");
        });

        return rtrn;
    }

    function handleSelect(value) {

        if (!isNaN(value) && value) {
             var selected = $("#assessment-collections-select").find("option[value=\"" + value + "\"]");
             $("#assessment-collections-title").val($(selected).text()).attr("disabled","disabled");
             $("#assessment-collections-desc").val($(selected).attr("desc")).attr("disabled","disabled");
             $(".btn-submit-add-to-collection").data("type","old"); 
        } else {
             $("#assessment-collections-title").val("").removeAttr("disabled");
             $("#assessment-collections-desc").val("").removeAttr("disabled");
             $(".btn-submit-add-to-collection").data("type","new"); 
        }
        $("#add-to-collection-message").empty();
    }

    $("#assessment-collections-select").on("change", function () {
        handleSelect($(this).val());
    });
    
    function handleAddToSelection(collection_id) {
        var assessments = $("#form-assessments").serialize();
 
        $.post(ENTRADA_URL + "/admin/gradebook?section=api-assessments&method=add-to-collection", {collection_id: collection_id, assessments: assessments})
        .done(function(json) {
            
            if (json.status == "success") {
                $(".btn-cancel-add-to-collection").text("Close");
                $(".btn-submit-add-to-collection").hide();
                var collection_text = $("#assessment-collections-select option[value=\"" + collection_id + "\"]").text();
                var message = "<strong>assessment(s) added to '" + collection_text + "' successfully.</strong>";
                $("#add-to-collection-message").html(message);
                reOrderAssessments(collection_id);
            } else {
                console.log("unable to update assessments");
                return false;
            }
        })
        .fail(function(e) {
            console.log(e);
            return false;
        })
        .always(function(e) {
            $("#modal-add-to-collection").modal("hide");
        });
    }

    function reOrderAssessments(collection_id) {
        var in_group = false;
        var checked_ids = getAssessmentIds(true);
        var new_order_ids = [];
        var skipped_grouped_ids = [];
        var iterated_ids = [];

        $(".ui-sortable.outer > div.row").each(function() {
            if ($(this).hasClass("group")) { // group header row

                if (collection_id && $(this).attr("data-collection-id") == collection_id) {
                    
                    if (iterated_ids.length == 0) {
                        iterated_ids = new_order_ids.slice(0);
                        new_order_ids = []; 
                    }
                    in_group = true;
                } else {
                    in_group = false
                }
                
                $(this).find(".ui-sortable > div.row").each(function () {

                    var curr_assessment_id = $(this).attr("data-assessment-id");

                    if (collection_id || checked_ids.indexOf(curr_assessment_id) < 0) {
 
                        if (in_group) {
                            iterated_ids.push(curr_assessment_id);
                        } else {
                            new_order_ids.push(curr_assessment_id);
                        }
                    } else {
                        skipped_grouped_ids.push(curr_assessment_id);
                    }
                });

                if (!collection_id && skipped_grouped_ids.length) {
                    
                    for (var i = 0; i < skipped_grouped_ids.length; i++) {
                        new_order_ids.push(skipped_grouped_ids[i]);
                    }
                    skipped_grouped_ids = [];
                }
            } else {

                var curr_assessment_id = $(this).attr("data-assessment-id");

                if (collection_id && checked_ids.indexOf(curr_assessment_id) >= 0) {
                    
                    if (!in_group && iterated_ids.length == 0) {
                        in_group = true;
                        iterated_ids = new_order_ids.slice(0);
                        iterated_ids.push(curr_assessment_id);
                        new_order_ids = [];
                        add_to_group = true;
                    } else { 
                        iterated_ids.push(curr_assessment_id);
                    }
                }

                if (!collection_id || checked_ids.indexOf(curr_assessment_id) < 0) {
                    new_order_ids.push(curr_assessment_id);
                } 
            }
        });

        if (collection_id && iterated_ids.length > 0) {
            new_order_ids = iterated_ids.concat(new_order_ids);
        }

        if (new_order_ids.length > 0) {
            $.post(ENTRADA_URL + "/admin/gradebook?section=api-assessments&method=update-assessments-order", {new_order: new_order_ids})
            .done(function(json) {
                
                if (json.status == "success") {
                    reloadTable();
                    return true;
                } else {
                    console.log("unable to update assessments order");
                    return false;
                }
            })
            .fail(function(e) {
                console.log(e);
                return false;
            });
        }
    }

    function setGroupEdit() {
        $(".group-edit-cell").on("click", function(e) {
            e.preventDefault();
            
            var title       =  $(this).closest(".row.group").attr("data-collection-title");
            var description =  $(this).closest(".row.group").attr("data-collection-description");
            var id          =  $(this).closest(".row.group").attr("data-collection-id");

            $("#assessment-collections-title").val(title).removeAttr("disabled");
            $("#assessment-collections-desc").val(description).removeAttr("disabled");
            $("#assessment-collections-id").val(id);
            $(".btn-submit-add-to-collection").data("type","edit").empty().text("Update Collection").show(); 
            $(".btn-cancel-add-to-collection").text("Cancel"); 
            $("#assessment-collections-select").hide();
            $("#add-to-collection-message").empty();
            $("#modal-add-to-collection .modal-header h3").text("Edit "+title);
            $("#modal-add-to-collection").modal("show");
        })
    }

    function setSortable() {
        $(".ui-sortable").sortable({
            handle: ".fa-arrows",
            stop : function(event, ui) {
                var new_order = getAssessmentIds(false);
                
                if (new_order.length > 0) {
                    $.post(ENTRADA_URL + "/admin/gradebook?section=api-assessments&method=update-assessments-order", {new_order: new_order})
                    .done(function(json) {
                        
                        if (json.status == "success") {
                            return true;
                        } else {
                            console.log("unable to update assessments order");
                            return false;
                        }
                    })
                    .fail(function(e) {
                        console.log(e);
                        return false;
                    });
                }
            }
            // connectWith: ".ui-sortable",
        });
    }

	$("#form-assessments").on("mouseenter", ".fa-grip", function () {
		$(this).attr("class", "fa fa-arrows fa-arrows-alt");
	});

	$("#form-assessments").on("mouseleave", ".fa-arrows", function () {
		$(this).attr("class", "fa fa-grip fa-grip-row");
	});

    function updateTotalGradeWeighting() {
        var total_grade_weighting = [];
        
        $(".assessment-row .weight-cell").each(function () {
            var row = $(this).closest(".outer > .row");
            var collection_id = ($(row).hasClass("group") ? $(row).attr("data-collection-id") : 0);
            
            total_grade_weighting[collection_id] = (total_grade_weighting[collection_id] ? total_grade_weighting[collection_id] : 0.0) + parseFloat($(this).text());
        });

        $(".outer > .row.group").each(function () {
            $(this).find(".grouped-weight").eq(0).text(total_grade_weighting[$(this).attr("data-collection-id")]+"%");
        });

        var total = 0.0;
        for(var i = 0; i < total_grade_weighting.length; i++) {
            total += (total_grade_weighting[i] ? total_grade_weighting[i] : 0);
        }

        $("#grade-weighting").removeClass("hide").text((Math.round(total)).toFixed(0));
    }

    var csvDownloadUrl = $(".btn-download-csv").attr("href");
    $(".btn-download-csv").attr("href", addParameterToUrl("cperiod_id=" + $("#selector-select-period").val(), csvDownloadUrl));

	var gradebook_assessment_add = $("#gradebook_assessment_add").attr("href");
	$("#gradebook_assessment_add").attr("href", addParameterToUrl("cperiod_id=" + $("#selector-select-period").val(), gradebook_assessment_add));

	setGroupEdit();
    setSortable();
    updateTotalGradeWeighting();
});