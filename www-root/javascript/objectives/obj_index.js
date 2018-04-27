/*   Handle Events from any control on */
var current_page;
var rows_per_page;
var total_pages;
var total_rows;
var current_results;
var data_results;

resetResults();

var UIEventSpace = (function() {
    
    parent_module = {
        name : "",
        base_url : ""
    };
    
    setParentModule = function(module_in) {
        parent_module = module_in;
    };
    
    getParentModule = function() {
        return parent_module;
    };
    
    var constructor = function(module_in) {
        ApiSpace.getTableColumns();
        setParentModule(module_in);
        bindEvents();
        ApiSpace.getFilterData();
    };

    handleTableLink = function() {
        if (!$j(this).data("is-max-level")) {
            var id = $j(this).data('id');
            var parent = $j(this).data('parent');
            var title = $j(this).text();
            var back_level_btn = $j('.level-back-btn');
            var parent_id = $j("#table-parent-id").val();
            if (parent_id == 0) {
                parent_id = $j(this).data('parent');
            }
            resetResults();
            if (id != parent_id) {
                back_level_btn.removeClass('hide');
            } else {
                back_level_btn.addClass('hide');
            }
            if($j(this).hasClass("back-link")) {
                ApiSpace.getFilterDataByParentBack(id);
            } else {
                ApiSpace.getFilterDataByParent(id);
            }
        }
    };
    
    handleSearchByKeyPress = function(e) {
        if (e.which == '13') {
            e.preventDefault();
            resetResults();
            ApiSpace.getFilterData();
        }
    };
    
    handleClearSearch = function() {
        $j('.search-query').val('');
        resetResults();
        ApiSpace.getFilterData();
    };
    
    handleDeleteClick = function(e) {
        var values = [];
        var lft = "<li>";
        var rght = "</li>";
        var shtml = "";

        $j("#deleteConfirmationModal").on("show.bs.modal", function(e) {
            values = $j('#TableManageObj').bootstrapTable('getSelections');
            var modal = $j(this);

            $j.each(values, function(index, value) {
                title = value.objective_name;
                if (!title) {
                    title = ' - ';
                }
                ihtml = lft.concat(value.objective_id + " : " + title, rght);
                shtml = shtml + ihtml;
            });
            modal.find('#delete-courses-list').html(shtml);
        });

        $j("#deleteConfirmationModal").modal('show');
        values = [];
    };

    handleDeleteConfirmation = function(event) {
        event.preventDefault();
        var checkedRows = $j('#TableManageObj').bootstrapTable('getSelections');
        ids = $j.map(checkedRows, function(row) {
            return row.objective_id;
        });
        
        var filterToSend = {};
        filterToSend.delete_ids = ids;
        var mydata = JSON.stringify(filterToSend);
        ApiSpace.deleteSelected(mydata);
    };
    
    handleChecksAllRows = function(e, row) {
        $j('#btn-delete').prop("disabled", false);
        $j('#btn-add').prop('disabled', true);
    };

    handleUncheckAllRows = function(e, row, $element) {
        e.preventDefault();
        $j('#btn-delete').prop("disabled", true);
        $j('#btn-add').prop('disabled', false);
        $j($element).removeClass('.default');
        setDeleteButton();
    };
    
    handleCheckRow = function(e, row, $element) {
        $j($element).addClass('warning');
        setDeleteButton();
    };
    
    handleUnCheckRow = function(e, row, $element) {
        setDeleteButton();
        $j($element).addClass('default');
    };

    handleColumnSwitch = function() {
        resetResults();
      if (ApiSpace.getBackBtnHtml() != "") {
          $j('.level-back-btn').html(ApiSpace.getBackBtnHtml());
          $j('.level-back-btn').removeClass('hide');
      }

      var table_columns = {};

      $j.each($j('#TableManageObj').bootstrapTable('getVisibleColumns'), function(index, column) {
         table_columns[index] = column.field;
      });
        var url = ENTRADA_URL + '/api/curriculum-tags.api.php?method=save-table-columns-options';
        url += '&data=' + encodeURI(JSON.stringify(table_columns));
        $j.ajax({
            type: "POST",
            url: url,
            dataType: "json",
            success: function (data) {
                if (data) {
                    ApiSpace.getFilterData();
                }
            }
        });
    };
    
    var setDeleteButton = function() {
        length = -1;
        length = $j('#TableManageObj').find('[type="checkbox"]:checked').length;
    
        $j('#btn-delete').prop('disabled',!length);
    };
    
    bindEvents = function() {
        $j("#btn-delete").bind("click", handleDeleteClick);
        $j('#submitdeleteconfirmation').bind("click", handleDeleteConfirmation);
        $j('#searchbyenter').bind("keypress", handleSearchByKeyPress);
        $j("#searchClear").bind("click", handleClearSearch);
        /*$j('#sidebarGroupAccordion').bind("af.fd.changed", function () {
            resetResults();
            ApiSpace.getFilterData();
        });*/
        $j('#TableManageObj').bind("check-all.bs.table", handleChecksAllRows);
        $j('#TableManageObj').bind("uncheck-all.bs.table", handleUncheckAllRows);
        $j('#TableManageObj').bind("check.bs.table", handleCheckRow);
        $j('#TableManageObj').bind("uncheck.bs.table", handleUnCheckRow);
        $j('#TableManageObj').bind("column-switch.bs.table", handleColumnSwitch);
        $j('#TableManageObj').on("click", ".table-link", handleTableLink);
        $j('.level-back-btn').on("click", ".table-link", handleTableLink);
        $j('#TableManageObj').on("click", ".btn-delete-tag", function (e) {
            $j('#delete-tag-modal').modal('show');
            var id = $j(this).data('id');
            $j('#delete-tag-modal #curriculum_tag_id').val(id);
            $j('#delete-tag-modal .modal-body p').html('Please confirm you would like to delete this curriculum tag');
            e.preventDefault();
        });
        $j("#delete-tag-modal-btn").bind("click", function() {
            var mydata = JSON.stringify({
                delete_ids: [$j('#delete-tag-modal #curriculum_tag_id').val()]
            });
            $j('#delete-tag-modal').modal('hide');
            ApiSpace.deleteSelected(mydata);
        });
        $j(".load_more_results").bind("click", function () {
                var parent_id = $j(this).data("parent-id");
                $j("#current_parent_id").val(parent_id);
                if (current_page['parent_' + parent_id] < total_pages) {
                    current_page['parent_' + parent_id] ++;
                    ApiSpace.getFilterDataByParent((parent_id && typeof parent_id !== "undefined" ? parent_id : 0));
                }
        });
    };
    
    return {
        init : function(module_in) {
            constructor(module_in);
        }
    }
})();

var back_level_btn_html = "", current_level = 1, maximum_levels = 1;
var ApiSpace = (function() {
    var table_columns = [];
    var getFilterData = function(parent_id, back) {
        $j('.loading-curriculum-tags').removeClass('hide');
        var filtersToSend = {};
        //filtersToSend = $j('#sidebarGroupAccordion').accordionFilter('filterData');
        var this_parent_id = (parent_id && typeof parent_id !== 'undefined' ? parent_id : 0);
        if (typeof current_page['parent_' + this_parent_id] === 'undefined') {
            current_page['parent_' + this_parent_id] = 1;
        }

        filtersToSend.KeywordSearch = $j('#searchbyenter').val();
        filtersToSend.objective_set_id = $j("#objective_set_id").val();
        filtersToSend.rows_per_page = rows_per_page;
        filtersToSend.current_page = current_page['parent_' + this_parent_id];
        //filtersToSend.attributes = getAttributes();
        if (back && typeof back !== 'undefined') {
            filtersToSend.back = true;
        } else {
            filtersToSend.back = null;
        }
        filtersToSend.parent_id = (parent_id && typeof parent_id !== 'undefined' ? parent_id : $j("#current_parent_id").val());
        var url = ENTRADA_URL + '/api/curriculum-tags.api.php?method=get-curriculum-tags';
        url += '&data=' + encodeURI(JSON.stringify(filtersToSend));

        $j.ajax({
            type: "GET",
            url: url,
            dataType: "json",
            error: function(jqXHR, textStatus, errorThrown) {
                var error = {"statusCode":jqXHR.status,"textStatus":textStatus, "error":errorThrown};
                try {
                    jsonResponse = JSON.parse(jqXHR.responseText);
                    if (!parent_id) {
                        display_error([jsonResponse.data], "#msgs");
                    }
                } catch (e) {
                    display_error(["There was an error fetching data for this request"], "#msgs");
                }
            },
            success: function(data) {
                $j("#TableManageObj").removeClass("hide");
                if (data.status == 'success') {
                    total_rows = data.total_rows;
                    current_results = data.data.length;
                    $j("#current_parent_id").val((parent_id && typeof parent_id !== 'undefined' ? parent_id : 0));
                    $j('#current_level').val(data.current_level);
                    $j('#linked_tags').val(data.linked_tags);
                    $j('#maximum_levels').val(data.maximum_levels);
                    if (data_results.length > 0) {
                        data_results = data_results.concat(data.data);
                    } else {
                        data_results = data.data;
                    }
                    $j('#TableManageObj').bootstrapTable('load', data_results);
                    handleLoadMoreResults(this_parent_id);
                    if (data.data.length > 0) {
                        if (data.parent_id) {
                            $j('.level-back-btn').removeClass('hide');
                            $j("#table-parent-id").val(data.parent_id);
                            if (back) {
                                $j('.level-back-btn a[data-id="' + parent_id + '"]').nextAll().remove();
                                $j('.level-back-btn a:last-child').remove();
                            } else {
                                if ($j('.level-back-btn .table-link.back-link[data-id="'+data.level_id+'"]').length <= 0) {
                                    back_level_btn_html = '<a class="table-link back-link" href="#" data-id="'+data.level_id+'" data-parent="'+data.parent_id+'"><i class="fa fa-angle-right"></i> '+data.parent_title+'</a>';
                                    $j('.level-back-btn').append(back_level_btn_html);
                                }
                            }
                        } else {
                            back_level_btn_html = '';
                            $j('.level-back-btn').html(back_level_btn_html);
                            $j('.level-back-btn').addClass('hide');
                        }
                    }
                } else {
                    if(!parent_id) {
                        display_notice([data.data], "#msgs");
                    }
                }
            }
        });
        $j('.loading-curriculum-tags').addClass('hide');
    };
    
    var deleteSelected = function(mydata) {
        var url = ENTRADA_URL
                + '/api/curriculum-tags.api.php?method=delete-tag&data='
                + encodeURI(mydata);
        var data = $j.ajax({
            type : "DELETE",
            url : url,
            dataType : "json",

            error : function(jqXHR, textStatus, errorThrown) {
                try {
                    jsonResponse = JSON.parse(jqXHR.responseText);
                    display_error([jsonResponse.data], "#msgs");
                } catch (e) {
                    display_error(["There was an error fetching data for this request"], "#msgs");
                }
            },
            success : function deleteRows() {
                var ids = JSON.parse(mydata).delete_ids;

                $j("#TableManageObj").bootstrapTable('remove', {
                    field : 'objective_id',
                    values : ids
                });
                $j('#btn-delete').prop("disabled", true);
            }
        });
    };

    var getBackBtnHtml = function() {
        return back_level_btn_html;
    };

    var getTableColumns = function () {
        var url = ENTRADA_URL + '/api/curriculum-tags.api.php?method=get-table-columns-options';
        $j.ajax({
            type: "GET",
            url: url,
            dataType: "json",
            success: function (data) {
                $j.each(data, function(index, item) {
                    table_columns.push(index);
                    $j("#TableManageObj").bootstrapTable('showColumn', index);
                });
            }
        });
    };

    var getAttributes = function () {
        attributes = [];
        table_columns = $j("#TableManageObj").bootstrapTable("getOptions").columns;
        jQuery.each(table_columns[0], function(index, column) {
            if (column.class == "attribute-column")
                attributes.push(column.field.split("attribute_")[1]);
        });
        return attributes;
    };

    return {
        getFilterData: function() {
            getFilterData();
        },
        getFilterDataByParent: function (id) {
            getFilterData(id);
        },
        getFilterDataByParentBack: function (id) {
            getFilterData(id, true);
        },
        deleteSelected : function(mydata) {
            deleteSelected(mydata);
        },
        getBackBtnHtml: function() {
            getBackBtnHtml();
        },
        getTableColumns: function() {
            getTableColumns();
        }
    };
})();

CurriculumTagsList = function(options) {
    var self = this;
    var url = ENTRADA_URL + '/api/curriculum-tags.api.php';
    var settings = $j.extend({
        breadcrumbs: false
    }, options);

    $j('#delete-tag-modal-btn').bind('click', function() {
       var id = $j('#delete-tag-modal #curriculum_tag_id').val();
       self.delete(id);
    });

    self.get = function(parent_id, is_load_more) {
        var html = "";
        var items_container = (parent_id && parent_id != 0 ? '.curriculum-tag-item[data-id="' + parent_id + '"]' : '#curriculum-tags-list');
        if (!is_load_more && $j('.curriculum-tag-item[data-id="' + parent_id + '"]').hasClass("loaded")) {
            self.toggleCollapse('.curriculum-tag-item[data-id="' + parent_id + '"]');
        } else {
            $j('.loading-curriculum-tags').removeClass('hide');
            $j('.curriculum-tag-item[data-id="' + parent_id + '"] .item-arrow').removeClass("fa-chevron-down").addClass("fa-spinner fa-spin");
            var dataToSend = {};
            //dataToSend = $j('#sidebarGroupAccordion').accordionFilter('filterData');
            if ($j('#searchbyenter').val() != "" && !parent_id) {
                dataToSend.KeywordSearch = $j('#searchbyenter').val();
            } else {
                dataToSend.KeywordSearch = null;
            }

            var this_parent_id = (parent_id && typeof parent_id !== 'undefined' ? parent_id : 0);
            if (typeof current_page['parent_' + this_parent_id] === 'undefined') {
                current_page['parent_' + this_parent_id] = 1;
                if (this_parent_id == 0) {
                    $j(items_container).empty();
                }
            }

            dataToSend.rows_per_page = rows_per_page;
            dataToSend.current_page = current_page['parent_' + this_parent_id];
            dataToSend.objective_set_id = $j("#objective_set_id").val();
            dataToSend.parent_id = (parent_id && typeof parent_id !== 'undefined' ? parent_id : $j("#current_parent_id").val());
            dataToSend.list = true;
            $j.ajax({
                type: "GET",
                url: url,
                data: {
                    method: 'get-curriculum-tags',
                    data: JSON.stringify(dataToSend)
                },
                dataType: "json",
                error: function (jqXHR, textStatus, errorThrown) {
                    $j('.loading-curriculum-tags').addClass('hide');
                    var error = {"statusCode": jqXHR.status, "textStatus": textStatus, "error": errorThrown};
                    try {
                        jsonResponse = JSON.parse(jqXHR.responseText);
                        if (!parent_id) {
                            display_error([jsonResponse.data], "#msgs");
                        }
                    } catch (e) {
                        display_error(["There was an error fetching data for this request"], "#msgs");
                    }
                },
                success: function (data) {
                    $j('.loading-curriculum-tags').addClass('hide');
                    if (data.status == "success") {
                        $j("#current_parent_id").val((parent_id && typeof parent_id !== 'undefined' ? parent_id : 0));
                        var breadcrumb = "";
                        // managing multiple results for each child
                        var tags = [];
                        total_rows['parent_' + this_parent_id] = data.total_rows;
                        current_results['parent_' + this_parent_id] = data.data.length;
                        if (data_results['parent_' + this_parent_id]) {
                            data_results['parent_' + this_parent_id] = data_results['parent_' + this_parent_id].concat(data.data);
                        } else {
                            data_results['parent_' + this_parent_id] = data.data;
                        }
                        tags = (this_parent_id == 0 ? data.data : data_results['parent_' + this_parent_id]);
                        // rendering the items
                        $j(items_container).next('.curriculum-children').remove();
                        $j.each(tags, function (index, item) {
                                if (settings.breadcrumbs) {
                                    $j.each(item.breadcrumbs, function(index, ele) {
                                        item.breadcrumbs[index] = ele.trunc(35, true);
                                    });
                                    breadcrumb = '<span class="tag-item-breadcrumb">' + item.breadcrumbs.join(" / ") + ' / </span>';
                                }

                                let buttons = '<div class="btn-group pull-right">';

                                if ((typeof display_button_add !== 'undefined') && display_button_add && (data.current_level < data.maximum_levels)) {
                                    buttons += '<a href="#tag-modal" data-toggle="modal" class="btn btn-sm add-tag" data-parent-id=' + item.objective_id + '><i class="fa fa-plus"></i></a>';
                                }

                                if ((typeof display_button_edit !== 'undefined') && display_button_edit) {
                                    buttons += '<a href="#edit-modal" data-toggle="modal" class="btn btn-sm edit-tag" data-id=' + item.objective_id + '><i class="fa fa-edit"></i></a>';

                                    if (data.linked_tags) {
                                        buttons += '<a href="#" class="btn btn-sm link-tag" data-id="' + item.objective_id + '"><i class="fa fa-link"></i></a>';
                                    }
                                }

                                if ((typeof display_button_delete !== 'undefined') && display_button_delete) {
                                    buttons += '<a href="#" class="btn btn-sm btn-delete-tag" data-id="' + item.objective_id + '"><i class="fa fa-trash"></i></a>';
                                }

                                buttons += '</div>';

                                html += '' +
                                    '<div class="curriculum-tag-item clearfix" data-id="' + item.objective_id + '" data-parent-id="' + this_parent_id + '" data-is-max-level="' + (data.current_level < data.maximum_levels ? false : true) + '">' +
                                    '<div class="span12">' +
                                    (data.current_level < data.maximum_levels ? '<i class="fa fa-chevron-down item-arrow"></i>' : '') +
                                    '<div class="tag-details">' + breadcrumb + item.long_method + '</div>' +
                                    '</div>' +
                                    '<div class="curriculum-btns">' + buttons + '</div>' +
                                    '</div>';
                        });
                        if (!parent_id || parent_id == 0) {
                            $j(items_container).append(html);
                        } else {
                            $j(items_container).after('<div class="curriculum-children" data-parent-id="' + parent_id + '">' + html + '<button class="load_more_results btn btn-default btn-block space-below" disabled data-parent-id="' + parent_id + '">Show more results</button></div>');
                        }
                        // initialize the load more results functions
                        handleLoadMoreResults(this_parent_id);
                        $j(".load_more_results").off();
                        $j(".load_more_results").bind("click", function () {
                            var parent_id = $j(this).data("parent-id");
                            $j("#current_parent_id").val(parent_id);
                            if (current_page['parent_' + parent_id] < total_pages) {
                                current_page['parent_' + parent_id] ++;
                                self.get((parent_id && typeof parent_id !== "undefined" ? parent_id : 0), true);
                            }
                        });
                        $j('.curriculum-tag-item[data-id="' + parent_id + '"]').addClass("loaded active");
                        $j(".curriculum-tag-item .tag-details, .curriculum-tag-item .item-arrow").off();
                        $j(".curriculum-tag-item .tag-details, .curriculum-tag-item .item-arrow").bind("click", function (e) {
                            if (!$j(this).closest(".curriculum-tag-item").data("is-max-level")) {
                                self.get($j(this).closest(".curriculum-tag-item").data("id"));
                            }
                            e.preventDefault();
                        });

                        $j('#sidebarGroupAccordion').bind("af.fd.changed", function () {
                            self.get();
                        });

                        $j(".btn-delete-tag").bind("click", function (e) {
                            $j('#delete-tag-modal').modal('show');
                            var id = $j(this).data('id');
                            $j('#delete-tag-modal #curriculum_tag_id').val(id);
                            var title = $j('.curriculum-tag-item[data-id="' + id + '"] .tag-item-breadcrumb').text().split(' / ');
                            title = title[title.length - 1];
                            $j('#delete-tag-modal .modal-body p').html('Please confirm you would like to delete <strong>' + title + '</strong>');
                            e.preventDefault();
                        });
                    } else {
                        if (!parent_id) {
                            display_notice([data.data], "#msgs");
                        }
                    }
                }
            });

            $j('.curriculum-tag-item[data-id="' + parent_id + '"] .item-arrow').addClass("fa-chevron-down").removeClass("fa-spinner fa-spin");
        }
    };

    self.delete = function(id) {

        var url = ENTRADA_URL
            + '/api/curriculum-tags.api.php?method=delete-tag&data='
            + encodeURI(JSON.stringify({delete_ids: [id]}));
        var data = $j.ajax({
            type : "DELETE",
            url : url,
            dataType : "json",

            error : function(jqXHR, textStatus, errorThrown) {
                try {
                    jsonResponse = JSON.parse(jqXHR.responseText);
                    display_error([jsonResponse.data], "#msgs");
                } catch (e) {
                    display_error(["There was an error fetching data for this request"], "#msgs");
                }
            },
            success : function (data) {
                $j('#delete-tag-modal').modal('hide');
                try {
                    if(data.status == 'success') {
                        $j('.curriculum-children[data-parent-id="' + id + '"]').remove();
                        $j('.curriculum-tag-item[data-id="' + id + '"]').remove();

                        $j("#TableManageObj").bootstrapTable('remove', {
                            field : 'objective_id',
                            values : [id]
                        });
                    } else {
                        display_error([data.data], "#msgs");
                    }
                } catch (e) {
                    display_error(["There was an error fetching data for this request"], "#msgs");
                }
            }
        });
    };

    self.toggleCollapse = function(container) {
        if ($j(container).hasClass("active")) {
            $j(container).removeClass("active");
        } else {
            $j(container).addClass("active");
        }
    };


    self.handleSearchByKeyPress = function(e) {
        if (e.which == '13') {
            e.preventDefault();
            resetResults();
            self.get();
        }
    };

    self.handleClearSearch = function() {
        $j('.search-query').val('');
        resetResults();
        self.get();
    };

    $j('#searchbyenter').bind("keypress", self.handleSearchByKeyPress);
    $j("#searchClear").bind("click", self.handleClearSearch);
};

function linkFormatterObjective(value,row,index) {
    var current_level = $j("#current_level").val();
    var maximum_levels = $j("#maximum_levels").val();
    return '<a class="table-link" href="#" data-id="'+row.objective_id+'" data-parent="'+row.objective_parent+'" data-is-max-level="' + (current_level < maximum_levels ? false : true) + '">' + (value ? value : "-") + '</a>';
}

function buttonsFormatter(value,row,index) {
    var current_level = $j("#current_level").val();
    var maximum_levels = $j("#maximum_levels").val();
    var linked_tags = $j("#linked_tags").val();

    let buttons = '<div class="btn-group pull-right">';

    if ((typeof display_button_add !== 'undefined') && display_button_add && (current_level < maximum_levels)) {
        buttons += '<a id="add_' + row.objective_name + '" href="#tag-modal" data-toggle="modal" class="btn btn-sm add-tag" data-parent-id=' + row.objective_id + '><i class="fa fa-plus"></i></a>';
    }

    if ((typeof display_button_edit !== 'undefined') && display_button_edit) {
        buttons += '<a id="edit_' + row.objective_name + '" href="#tag-modal" data-toggle="modal" class="btn btn-sm edit-tag" data-id=' + row.objective_id + '><i  class="fa fa-edit"></i></a><i class="fa fa-edit"></i></a>';

        if (linked_tags == 'true') {
            buttons += '<a href="#tag-modal" class="btn btn-sm link-tag" data-id="' + row.objective_id + '"><i class="fa fa-link"></i></a>';
        }
    }

    if ((typeof display_button_delete !== 'undefined') && display_button_delete) {
        buttons += '<a href="#" class="btn btn-sm btn-delete-tag" data-id="' + row.objective_id + '"><i class="fa fa-trash"></i></a>';
    }
    buttons += '</div>';

    return buttons;
}

function formatObjectiveList(value,row,index) {
    if (value == null || !$j.isArray(value)) {
        return "";
    }

    output = "<ul>";
    $j.each(value, function(id,val) {output = output + "<li>" +val+"</li>";});
    output = output + "</ul>";
    return output;
}

function formatTagAttributes(value,row,index) {
    var attributes = "";
    if (value != null) {
        try {
            jQuery.each(value, function () {
                attributes += "<div class=\"atribute-item\">" + this + "</div>";
            });
        } catch (e) {
            attributes = " - ";
        }
    } else {
        attributes = " - ";
    }
    return attributes;
}

function displayEditModal(id, tag_links) {
    jQuery("#manage-modal").modal("show");
    jQuery("#manage-modal .modal-header h3").html("Edit Tag");
    var url = ENTRADA_URL + "/admin/curriculum/tags/objectives?section=edit";
    jQuery("#manage-modal .modal-body .container").load(url, {"id" : id}, function () {
        jQuery("#manage-modal .modal-body h1, #manage-modal .modal-body .buttons").remove();
        jQuery("#manage-modal .modal-body form").attr("action", ENTRADA_URL + "/admin/curriculum/tags/objectives?section=edit&step=2");
        jQuery("#admin_notes").ckeditor();
        jQuery("#manage-modal .modal-body .objective_id_buttons").hide();
        jQuery("#manage-modal #side_buttons").html(jQuery("#manage-modal .modal-body .objective_id_buttons .controls").html());
        if (tag_links) {
            jQuery("a[aria-controls='tagAttributes']").click();
        }
    });
}

function handleLoadMoreResults(parent_id) {
    var tags = [];
    var rows = 0;
    var load_more_btn;
    if (view_type == "table-view") {
        load_more_btn = "#table-view .load_more_results";
        if (parent_id != 0) {
            jQuery(load_more_btn).attr("data-parent-id", parent_id);
            jQuery(load_more_btn).data("parent-id", parent_id);
        }
    } else {
        load_more_btn = "#list-view .load_more_results[data-parent-id=\"" + parent_id + "\"]"
    }
    tags = (view_type != "table-view" ? data_results['parent_' + parent_id] : data_results);
    rows = (view_type != "table-view" ? total_rows['parent_' + parent_id] : total_rows);
    total_pages = parseInt(rows / rows_per_page).toFixed(0);
    if (rows % rows_per_page > 0) {
        total_pages ++;
    }
    jQuery(load_more_btn).html("Showing " + tags.length + " of " + rows + " results");
    if (tags.length < rows) {
        jQuery(load_more_btn).removeAttr("disabled");
    } else {
        jQuery(load_more_btn).attr("disabled", "disabled");
    }
}

function resetResults() {
    current_page = [];
    total_rows = [];
    current_results = [];
    data_results = [];
    rows_per_page = 25;
    total_pages = 0;
}

function handleExportClick() {
    $j('#export-form').submit();
    return false;
};

function handleImportClick(demo) {
    var url = ENTRADA_URL + "/api/curriculum-tags.api.php?method=import-csv" + (demo ? "&demo=true" : "");
    if (demo) {
        $j("#import-tags-modal form").attr("action", url);
        $j("#import-tags-modal form").submit();
    } else{
        $j("#import-tags-modal-btn").attr("disabled", "disabled");
        if ($j("input[name=\"parent_tag\"]").length > 0) {
            url += "&parent_tag=" + $j("input[name=\"parent_tag\"]").val();
        }
        var formData = new FormData($j("#import-tags-modal form")[0]);
        $j.ajax({
            url: url,
            type: "POST",
            async: false,
            data: formData,
            processData: false,
            contentType: false,
            success: function(data) {
                var jsonResponse = JSON.parse(data);
                $j("#import-tags-modal-btn").removeAttr("disabled");
                if (jsonResponse.status === "success") {
                    display_success(jsonResponse.data, "#msgs", "append");
                    $j("#import-tags-modal").modal("hide");
                    setTimeout(function () {
                        window.location.reload();
                    }, 5000);
                } else {
                    display_error(jsonResponse.data, "#import-errors");
                }
            }
        });
    }
    return false;
};

var $j = jQuery.noConflict();

$j(document).ready(function(event) {

    $j(".tab-content").on("click", ".add-tag", function(e) {
        $j("#manage-modal").modal("show");
        $j("#manage-modal .modal-header h3").html("Add Tag");
        var url = ENTRADA_URL + "/admin/curriculum/tags/objectives?section=add&ajax=1";
        $j("#manage-modal .modal-body .container").load(url, {"parent_id" : $j(this).data("parent-id")}, function () {
            $j("#manage-modal .modal-body h1, #manage-modal .modal-body .buttons").remove();
            $j("#manage-modal .modal-body form").attr("action", ENTRADA_URL + "/admin/curriculum/tags/objectives?section=add&ajax=1&step=2");
            $j("#admin_notes").ckeditor();
        });
    });

    $j("#tag-btn").on("click", function (event) {
        var url = $j("#manage-modal").find("form").attr("action");
        jQuery.ajax({
            url: url,
            type: "POST",
            async: false,
            data: $j("#manage-modal").find("form").serialize(),
            success: function(data) {
                var jsonResponse = JSON.parse(data);
                if (jsonResponse.status === "success") {
                    $j("#manage-modal").modal("hide");
                    display_success(jsonResponse.data, "#msgs", "append");
                    $j(".curriculum-tag-item[data-id='" + jsonResponse.parent_id + "']").removeClass("loaded");
                    var this_parent_id = 0;
                    if ($j("#manage-modal").find("form").find("input#id").length > 0) {
                        this_parent_id = $j(".curriculum-tag-item[data-id=" + $j("#manage-modal").find("form").find("input#id").val() + "]").data("parent-id");
                    } else {
                        this_parent_id = $j("#manage-modal").find("form").find("input#parent_id").val();
                    }
                    if (view_type == "list-view") {
                        delete current_page["parent_" + this_parent_id];
                        delete total_rows["parent_" + this_parent_id];
                        delete current_results["parent_" + this_parent_id];
                        delete data_results["parent_" + this_parent_id];
                        var tagslist = new CurriculumTagsList({breadcrumbs: true});
                        tagslist.get(this_parent_id);
                    } else {
                        resetResults();
                        ApiSpace.getFilterDataByParent(jsonResponse.parent_id);
                    }
                }
                if (jsonResponse.status === "error") {
                    display_error(jsonResponse.data, "#error-msgs");
                }
            }
        });
    });

    $j(".tab-content").on("click", ".edit-tag", function(e) {
        displayEditModal($j(this).data("id"));
    });

    $j(".tab-content").on("click", ".link-tag", function(e) {
        displayEditModal($j(this).data("id"), true);
    });

    $j("#manage-modal.fullscreen-modal").on("shown", function () {
        $j("body").addClass("modal-open");
    });

    $j("#manage-modal.fullscreen-modal").on("hidden", function () {
        $j("body").removeClass("modal-open");
    });

    $j("#exportbtn").on("click", function(e) {
        handleExportClick();
    });

    $j("#import-tags-modal-btn").on("click", function(e) {
        handleImportClick();
    });

    $j("#example-file-btn").on("click", function(e) {
        handleImportClick(true);
    });

    $j("#import-tags-modal").on("hidden", function(){
       $j("#import-tags-modal form")[0].reset();
       $j("#import-errors").empty();
       $j("input[name=\"parent_tag\"]").remove();
       $j("#choose-parent-tag-btn").html("Browse All Tags <i class=\"icon-chevron-down btn-icon pull-right\"></i>");

    });

   /* $j(window).scroll(function(e) {
        if($j(window).scrollTop() + $j(window).height() > $j(document).height() - 100) {
            if ($j(".loading-curriculum-tags").hasClass("hide")) {
                setTimeout(function() {
                    jQuery(".load_more_results").click()
                }, 500);
            }
        }
        e.preventDefault();
    });*/

    String.prototype.trunc =
        function( n, useWordBoundary ){
            if (this.length <= n) { return this; }
            var subString = this.substr(0, n-1);
            return (useWordBoundary
                    ? subString.substr(0, subString.lastIndexOf(' '))
                    : subString) + " ...";
        };
});