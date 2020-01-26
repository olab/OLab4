/*   Handle Events from any control on */
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
        setParentModule(module_in);
        bindEvents();
    };
    
    handleSearchByKeyPress = function(e) {
        if (e.which == '13') {
            e.preventDefault();
            ApiSpace.getFilterData();
        }
    };
    
    handleClearSearch = function() {
        $j('.search-query').val('');
        ApiSpace.getFilterData();
    };
    
    handleExportClick = function() {
        var options = $j('#TableManageObj').bootstrapTable('getOptions');
        var columns = options.columns[0];
        var data = options.data;

        var result = $j.map(data, function(row, i) {
            var item = {};
            $j.each(columns, function(i, elem) {
                if (elem.field !== "selectall" && elem.visible) {
                    item[elem.field] = row[elem.field];
                }
            })
            return item;
        });
        $j('#table-data').val(JSON.stringify(result));
        $j('#export-form').submit();
        return false;
    };
    
    handleAddClick = function(e) {
        location.href = parent_module.base_url + "?section=add";
        return false;
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
                title = value.title;
                ihtml = lft.concat(value.objective_set_id + " : " + title, rght);
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
            return row.objective_set_id;
        });
        
        var filterToSend = {};
        filterToSend.delete_ids = ids;
        var mydata = JSON.stringify(filterToSend);
        ApiSpace.deleteSelected(mydata);
    };
    
    handleChecksAllRows = function(e, row) {
        $j('#btn-delete').prop("disabled", false);
        $j('#btn-duplicate').prop("disabled", false);
        $j('#btn-add').prop('disabled', true);
        $j('#exportbtn').prop('disabled', true);
    };

    handleUncheckAllRows = function(e, row, $element) {
        e.preventDefault();
        $j('#btn-delete').prop("disabled", true);
        $j('#btn-duplicate').prop("disabled", true);
        $j('#exportbtn').prop('disabled', true);
        $j('#btn-add').prop('disabled', false);
        $j($element).removeClass('.default');
        setDeleteButton();
        setDuplicateButton();
        setExportButton();
    };
    
    handleCheckRow = function(e, row, $element) {
        $j($element).addClass('warning');
        setDeleteButton();
        setDuplicateButton();
        setExportButton();
    };
    
    handleUnCheckRow = function(e, row, $element) {
        setDeleteButton();
        setDuplicateButton();
        setExportButton();
        $j($element).addClass('default');
    };
    
    var setDeleteButton = function() {
        length = -1;
        length = $j('#TableManageObj').find('[type="checkbox"]:checked').length;
    
        $j('#btn-delete').prop('disabled',!length);
    };

    var setDuplicateButton = function() {
        length = -1;
        length = $j('#TableManageObj').find('[type="checkbox"]:checked').length;

        $j('#btn-duplicate').prop('disabled',!length);
    };

    var setExportButton = function() {
        length = -1;
        length = $j('#TableManageObj').find('[type="checkbox"]:checked').length;

        $j('#exportbtn').prop('disabled',(length > 0 && length == 1 ? false : true));
    };
    
    bindEvents = function() {
        $j("#btn-add").bind("click", handleAddClick);
        $j("#btn-delete").bind("click", handleDeleteClick);
        $j('#submitdeleteconfirmation').bind("click", handleDeleteConfirmation);
        $j('#searchbyenter').bind("keypress", handleSearchByKeyPress);
        $j("#exportbtn").bind("click", handleExportClick);
        $j("#searchClear").bind("click", handleClearSearch);
        $j('#sidebarGroupAccordion').bind("af.fd.changed", ApiSpace.getFilterData);
        $j('#TableManageObj').bind("check-all.bs.table", handleChecksAllRows);
        $j('#TableManageObj').bind("uncheck-all.bs.table", handleUncheckAllRows);
        $j('#TableManageObj').bind("check.bs.table", handleCheckRow);
        $j('#TableManageObj').bind("uncheck.bs.table", handleUnCheckRow);
    };
    
    return {
        init : function(module_in) {
            constructor(module_in);
        }
    }
})();

var ApiSpace = (function() {
    
    var getFilterData = function() {
        $j('.loading-curriculum-tags').removeClass('hide');
        var filtersToSend = {};
        filtersToSend = $j('#sidebarGroupAccordion').accordionFilter('filterData');
        filtersToSend.KeywordSearch = $j('.search-query').val();
        
        var url = ENTRADA_URL + '/api/curriculum-tags.api.php?method=get-curriculum-tag-sets';
        url += '&data=' + encodeURI(JSON.stringify(filtersToSend));
        
        $j.ajax({
            type: "GET",
            url: url,
            dataType: "json",
            error: function(jqXHR, textStatus, errorThrown) {
                var error = {"statusCode":jqXHR.status,"textStatus":textStatus, "error":errorThrown};
                try {
                    jsonResponse = JSON.parse(jqXHR.responseText);
                    display_error([jsonResponse.data], "#msgs");
                } catch (e) {
                    display_error(["There was an error fetching data for this request"], "#msgs");
                }
            },
            success: function(data) {
                $j("#TableManageObj").removeClass("hide");
                $j('#TableManageObj').bootstrapTable('load', data.data);
                $j('.loading-curriculum-tags').addClass('hide');
            }
        });
    };
    
    var deleteSelected = function(mydata) {
        var url = ENTRADA_URL
                + '/api/curriculum-tags.api.php?method=delete-tag-set&data=' + encodeURI(mydata);
        var data = $j.ajax({
            type : "DELETE",
            url : url,
            dataType: "json",
            error : function(jqXHR, textStatus, errorThrown) {
                try {
                    jsonResponse = JSON.parse(jqXHR.responseText);
                    display_error([jsonResponse.data], "#msgs");
                } catch (e) {
                    display_error(["There was an error fetching data for this request"], "#msgs");
                }
            },
            success : function(data) {
                jsonResponse = data;
                if (jsonResponse.status && jsonResponse.status == "success") {
                    deleteRows(mydata);
                } else {
                    display_error([jsonResponse.data], "#msgs");
                }
            }
        });
    };

    var deleteRows = function(mydata) {
        var ids = JSON.parse(mydata).delete_ids;

        $j("#TableManageObj").bootstrapTable('remove', {
            field : 'objective_set_id',
            values : ids
        });
        $j('#btn-delete').prop("disabled", true);
    };
    
    return {
        getFilterData: function() {
            getFilterData()
        },
        deleteSelected : function(mydata) {
            deleteSelected(mydata)
        }
    };
})();
