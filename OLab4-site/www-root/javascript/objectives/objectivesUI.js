var UISpace = (function() {

    var constructor = function() {
        getActiveTab();
        bindEvent();
    };

    // Get Active tab & save href in local storage
    getActiveTab = function () {
        jQuery('.nav-tabs > li > a').click( function() {
            var a = jQuery(this).attr('href');
            localStorage.setItem("href", a);
        });
    }

    // If one child is checked, then check parent else uncheck it.
    handleCheckBox = function() {
        var chk = $j(this);
        var parent_chk = $j('[name='+chk.data("parentid")+']');
        var parent_li = $j('#'+chk.data("parentid"));
        var siblings = parent_li.find('ul > li > input:checked');
        if (siblings && siblings.length > 0) {
            parent_chk.prop('checked', true);
        } else {
            parent_chk.prop('checked', false);
        }
    }

    mappedObjectives = function(code) {
        if ($j("#mapped" + code).is(':checked')) {
            $j("." + code + "Hidden").hide();
        } else {
            $j("." + code + "Hidden").show();
        }
    };

    filterData = function (e) {
        
        // First filter Show only mapped objectives
        // Get the list of li's (parents)
        var all_lis = [];
        var id = "#mappedObjectives";
        var keyword = "";
        switch(localStorage.getItem("href")) {
            case "#" + PROGRAM_COMPETENCY_OBJECTIVE_CODE:
                id += PROGRAM_COMPETENCY_OBJECTIVE_CODE;
                all_lis = $j("#" + PROGRAM_COMPETENCY_OBJECTIVE_CODE + " > ul > li");
                keyword = $j("#search" + PROGRAM_COMPETENCY_OBJECTIVE_CODE).val();
            break;

            case "#" + EPA_OBJECTIVE_CODE:
                id += EPA_OBJECTIVE_CODE;
                all_lis = $j("#" + EPA_OBJECTIVE_CODE + " > ul > li");
            break;

            case "#" + MCC_OBJECTIVE_CODE:
                id += MCC_OBJECTIVE_CODE;
                all_lis = $j("#" + MCC_OBJECTIVE_CODE + " > ul > li");
                keyword = $j("#search" + MCC_OBJECTIVE_CODE).val();
            break;
        }

        var input;
        var checkedList = [];

        //Handle show only mapped objectives
        all_lis.each( function(i, element) {
            // Get the parents inputs inside the <li>
            input = $j(element).find("input")[0];
            
            // If the CheckBox is not checked
            if ( $j(input).is(":not(:checked)") ) {
                // Show only mapped objectives
                if ($j(id).is(':checked')) {
                    // Hide the list
                    $j(element).hide();
                // Show all objectives
                } else {
                    $j(element).show();
                    checkedList.push(element);
                }
            } else {
                // Prents li which they are checked
                $j(element).show();
                checkedList.push(element);
            }
        });
        
        //If there is no search criteria then show all lis that have been
        //selected in the previous loop, close all collapsable divs so only the first level is open
        //Show all children li's that may have been stripped from a previous search.
        //Remove all highlighting
        if (!keyword) {
            $j(checkedList).show();
            $j('.collapse.in').collapse("hide");
            $j(checkedList).find("ul > li").show();
            //$j(all_lis).each(function(i, li) {clearHighlight(li);});
            
        } else {
            // Second filter by keyword search
            // If the filter keyword search matched, then show the parent and matching children
            checkedList.each(function(li) {
                div_id = '#collapse'+$j(li).attr('id');
                // Compare the text including parent and children
                if ($j(li).text().search(new RegExp(keyword, "i")) < 0 ) {
                    $j(li).hide();
                    $j(div_id+'.collapse.in').collapse("hide");
                    //clearHighlight(li);
                } else {
                    // Show the list item if the phrase matches
                    $j(li).show();
                    $j(div_id).not('.in').collapse("show");
                    children_lis = $j(li).find("ul > li");
                    children_lis.each(function() {
                        if ($j(this).text().search(new RegExp(keyword, "i")) < 0 ) {
                            $j(this).hide();
                        } else {
                            $j(this).show();
                            //highlight(keyword, this);
                        }
                    });
                }
            });
        }
        
        visible_lis = all_lis.filter(':visible');
        if (visible_lis.length === 0) {
            $j(localStorage.getItem("href")+'msg').show();
        } else {
            $j(localStorage.getItem("href")+'msg').hide();
        }
        
    };
    
    highlight = function(keyword, li) {
        //var chbx = $j(li).find("input");
        var textArray = $j(li).html().split(keyword);
        var txt="";
        $j.each(textArray, function(i,v) {
            txt += v;
            if (i < textArray.length-1) {
                txt += "<span style=\"background:yellow\">"+ keyword +"</span>";
            }
        });
        $j(li).html(txt);
    };
    
    clearHighlight = function(li) {
        var text = $j(li).html();
        //text = text.replace(/\<span\sstyle\=\"background\:yellow\"\>/i,"");
        //text = text.replace(/\<\/span\>/i,"");
        text = text.split("\<span style=\"background:yellow\"\>").join("");
        text = text.split("\<\/span\>").join("");
        $j(li).html(text);
    }

    handleNoneCheckBox = function() {
        // Get the list of li's
        var li = $j("#" + EPA_OBJECTIVE_CODE + " > ul > li");
        var input;

        // Get the list of li's
        li.each( function(i, element) {
            // Get all inputs (parent and children) inside the <li>
            input = $j(element).find("input");
            input.each( function() {
                // If None is checked
                if ($j("#none" + EPA_OBJECTIVE_CODE).is(':checked')) {
                    // Disable all checkboxes in this list
                    $j(this).attr("disabled", true);
                } else {
                    // Enable all checkboxes in this list
                    $j(this).attr("disabled", false);
                }
            });
        });
    };

    handleClearSearch = function(e) {
        switch(localStorage.getItem("href")) {
            case "#" + PROGRAM_COMPETENCY_OBJECTIVE_CODE:
                $j("#search" + PROGRAM_COMPETENCY_OBJECTIVE_CODE).val('');
            break;

            case "#" + MCC_OBJECTIVE_CODE:
                $j("#search" + MCC_OBJECTIVE_CODE).val('');
            break;
        }
        filterData(e);
    };

    searchObjective = function (e) {
         if (e.which == '13') {
            e.preventDefault();
            $j(".alert").remove();
            var id = $j("#searchByEnterObjective").val();
            if ( $j.isNumeric(id) ) {
                getFilterData(id);
            }
        }
    }
    
    searchByEnter = function (e) {
        if (e.which == '13') {
           e.preventDefault();
           filterData(e);
       }
    }
    
    

    // Ajax Call
    var getFilterData = function(id) {
        var filtersToSend = {};
        filtersToSend.id = id;
        var url = ENTRADA_URL + '/api/api-objectives.inc.php?method=get-objective';
        url += '&data=' + encodeURI(JSON.stringify(filtersToSend));
        $j.ajax({
            type: "GET",
            url: url,
            dataType: "json",
            error: function(jqXHR, textStatus, errorThrown) {
                var error = {"statusCode":jqXHR.status,"textStatus":textStatus, "error":errorThrown};
                console.log(error);
            },
            success: function(data) {
                if (data == 1) {
                    window.location.href = window.location.protocol+'//'+window.location.host + window.location.pathname + "?section=edit&id=" + id;
                } else {
                    display_error(["The provided Objective ID does not exist in the system."], "#errorMessage");
                }
            }
        });
    }

    clearSearchObjective = function () {
        $j("#searchByEnterObjective").val("");
    }
    
    delay = function (fn, ms) {
        var id, scope, args;
        return function () {
            scope = this;
            args = arguments;
            id && clearTimeout(id);
            id = setTimeout(function () { 
                fn.apply(scope, args); 
            }, ms);
        };
    };

    bindEvent = function() {
        // handle checkboxes for parents and children
        $j("li :checkbox").on("click", handleCheckBox);

        // Search Bar in the Top
        $j("#searchByEnterObjective").on("keypress", searchObjective);
        $j("#searchClearObjective").on("click", clearSearchObjective);

        // Checkbox None in EPA tab
        $j("#none" + EPA_OBJECTIVE_CODE).on("click", handleNoneCheckBox);
        if ($j("#none" + EPA_OBJECTIVE_CODE).is(':checked')) {
            handleNoneCheckBox();
        }

        /*
         * For Admin view
        */
        // Show only mapped objectives
        $j("#mappedObjectives" + PROGRAM_COMPETENCY_OBJECTIVE_CODE).on("click", filterData);
        $j("#mappedObjectives" + EPA_OBJECTIVE_CODE).on("click", filterData);
        $j("#mappedObjectives" + MCC_OBJECTIVE_CODE).on("click", filterData);
        // Search Bar
        $j("#search" + PROGRAM_COMPETENCY_OBJECTIVE_CODE).on("keypress", searchByEnter);
        $j("#search" + EPA_OBJECTIVE_CODE).on("keyup", delay(filterData,500));
        $j("#search" + MCC_OBJECTIVE_CODE).on("keyup", delay(filterData,500));
        // Clear search
        $j("#searchClear" + PROGRAM_COMPETENCY_OBJECTIVE_CODE).on("click", handleClearSearch);
        $j("#searchClear" + MCC_OBJECTIVE_CODE).on("click", handleClearSearch);

        /*
         * For Leadership & Non admin views
        */
        // Show only mapped objectives 
        $j("#mapped" + PROGRAM_COMPETENCY_OBJECTIVE_CODE).on("click", function (e) {
            mappedObjectives(PROGRAM_COMPETENCY_OBJECTIVE_CODE)
        });
        $j("#mapped" + EPA_OBJECTIVE_CODE).on("click", function (e) {
            mappedObjectives(EPA_OBJECTIVE_CODE)
        });
        $j("#mapped" + MCC_OBJECTIVE_CODE).on("click", function (e) {
            mappedObjectives(MCC_OBJECTIVE_CODE);
        });

        if ($j("#mapped" + PROGRAM_COMPETENCY_OBJECTIVE_CODE).is(':checked')) {
            mappedObjectives(PROGRAM_COMPETENCY_OBJECTIVE_CODE);
        }
        if ($j("#mapped" + EPA_OBJECTIVE_CODE).is(':checked')) {
            mappedObjectives(EPA_OBJECTIVE_CODE);
        }
        if ($j("#mapped" + MCC_OBJECTIVE_CODE).is(':checked')) {
            mappedObjectives(MCC_OBJECTIVE_CODE);
        }
    };

    return {
        init : function() {
            constructor();
        }
    }
})();
