/**
 * @author Alfreda Morrissey <amorriss@uottawa.ca>
 * version: 1.0.0
 * https://git.med.uottawa.ca/amorriss/bootstrap-accordion-filter
 */

if (window.Prototype) {
    delete Array.prototype.toJSON;
}

var $j = jQuery.noConflict();

var AccordionFilter = function(element, options) {
    this.accordion_div = element;
    this.options = options;
    this.json_data = {};
    this.alertObj = null;
    this.filter_data = {};
    
    this.init();
};

AccordionFilter.DEFAULTS = {
        url: undefined,
        table_id: "table",
        defaults: {}
};

AccordionFilter.prototype.init = function() {
    this.initAlertDiv();
    this.initData();
    //this.loadBSTable();
};

AccordionFilter.prototype.initAlertDiv = function() {
    //If they have not set up an alert div then don't post the messages
    if ("undefined" === typeof this.options.msgdiv) {
        return false;
    }
    
    this.alertObj = new AccordionFilterAlerts($j('#'+this.options.msgdiv), this);
};

AccordionFilter.prototype.loadBSTable = function() {
    //If the url is not set, you cannot load the filter items
    if ("undefined" === typeof this.options.tableurl || "undefined" === typeof this.options.table) {
        return false;
    }
    
    filter_pointer = this;
    
    data = $j.ajax({
        url: this.options.tableurl,
        data: "data="+encodeURI(JSON.stringify(filter_pointer.filter_data)),
        type: 'GET',
        dataType: 'json',
        error: function(jqXHR, textStatus, errorThrown) {},
        success: function(data) {
            $j('#'+filter_pointer.options.table).bootstrapTable('load', data);
            $j("#"+filter_pointer.options.table).bootstrapTable({ data: data });
        }
    });
};

AccordionFilter.prototype.initContainer = function() {
    //Add the [Open All] and [Close All] buttons
    openAll_a = document.createElement("a");
    closeAll_a = document.createElement("a");
    $j(openAll_a).addClass("btn btn-default openall space-right").attr({"href":"#"}).text('Open All');
    $j(closeAll_a).addClass("btn btn-default closeall").attr({"href":"#"}).text("Close All");
    $j(this.accordion_div).append(openAll_a).append(closeAll_a).append("<hr>");
    this.initFilterItems();
    this.bindEvents();
    
    this.initDefaultValues();
    $j(this.accordion_div).trigger("af.fd.loaded");
    $j(this.accordion_div).trigger("af.fd.changed");
} ;

AccordionFilter.prototype.initFilterItems = function() {
    panelGrp_div = document.createElement("div");
    $j(panelGrp_div).addClass("panel-group").attr({"id":"accordion"});
    
    filter_pointer = this;
    alert_pointer = this.alertObj;
    
    //Loop through each filter item in the json_data and add them to the panelGrp_div
    $j.each(this.json_data, function(key, element) {
        if (element.sortable) {
            element.items.sort(function (a,b) {
                if ($j.isNumeric(a.sort_field) && $j.isNumeric(b.sort_field)) {
                    return a-b;
                }
                var nameA = a.sort_field.toUpperCase();
                var nameB = b.sort_field.toUpperCase();
                
                if (nameA < nameB) {
                    return -1;
                }
                if (nameA > nameB) {
                    return 1;
                }
        
                // names must be equal
                return 0;
            });
        }
        
        filter_pointer.initFilterItem(panelGrp_div,key,  element);
        alert_pointer.addContentFilter(key+'_cf');
        filter_pointer.filter_data[key] = [];
    });
    
    $j(this.accordion_div).append(panelGrp_div);
};

AccordionFilter.prototype.initDefaultValues = function() {
  if (!this.options.defaults) {
      return ;
  }
  $j.each(this.options.defaults, function(key, value) {
     $j("#collapse_"+key).collapse("show"); 
     $j('#'+key + '_' + value).attr('checked', true).trigger("change");
  });  
}

AccordionFilter.prototype.initFilterItem = function(panelGrp_div, key, item) {
    panel_div = document.createElement("div");
    panelHd_div = document.createElement("div");
    toggle_a = document.createElement("a");
    
    $j(panel_div).addClass("panel panel-default");
    $j(panelHd_div).addClass("panel-heading");
    $j(toggle_a).addClass("accordion-toggle").attr({"data-toggle":"collapse", "href":"#collapse_"+key});
    
    $j(panel_div).append(panelHd_div);
    $j(panelHd_div).append(toggle_a);
    $j(toggle_a).append("<h4 class=\"panel-title\">"+item.label+"<span class=\"fa fa-plus pull-right collapse_"+key+"\" aria-hidden=\"true\"></span></h4>");
    
    panelCollapse_div = document.createElement("div");
    panelBody_div = document.createElement("div");
    
    $j(panelCollapse_div).addClass("panel-collapse collapse").attr({"id":"collapse_"+key});
    $j(panelBody_div).addClass("panel-body").attr({"id":key});
    
    $j(panelCollapse_div).append(panelBody_div);
    $j(panel_div).append(panelHd_div).append(panelCollapse_div);
    
    filter_pointer = this;
    $j.each(item.items, function(row_id, element) {
        if ("undefined" === typeof element.items) {
            filter_pointer.initCheckbox(panelBody_div,key,  element, "");
        } else {
            filter_pointer.initSubfilter(panelBody_div,key,  element);
        }
        
    });
    
    //Append the newly created filter div, to the filter container div
    $j(panelGrp_div).append(panel_div);
    
    
};

AccordionFilter.prototype.initSubfilter = function(panelBody_div, set_id, element) {
    toggle_a = document.createElement("a");
    collapse_div = document.createElement("div");
    
    $j(toggle_a).addClass("accordion-toggle").attr({"data-toggle": "collapse", "href":"#collapse_"+set_id+element.value});
    $j(toggle_a).append("<label><span class=\"fa fa-plus collapse_"+set_id+element.value+"\"></span>  "+element.label+"</label>");
    $j(collapse_div).addClass("panel-collapse collapse offset1").attr({"id":"collapse_"+set_id+element.value});
    
    filter_pointer = this;
    $j.each(element.items, function(row_id, subelement) {
        filter_pointer.initCheckbox(collapse_div,set_id,  subelement, element.label+": ");
    });
    
    $j(panelBody_div).append(toggle_a).append(collapse_div);
};

AccordionFilter.prototype.initCheckbox = function(panelBody_div, set_id, element, prefix) {
    label_chbx = document.createElement("label");
    input_chbx = document.createElement("input");
    
    $j(label_chbx).addClass("checkbox").attr({"for":set_id+"_"+element.value});
    $j(label_chbx).text(element.label);
    $j(input_chbx).addClass('filter_chbx').attr({"id":set_id+"_"+element.value, "type":"checkbox","name":set_id,"value":element.value, "data-alert":prefix+element.label});
    $j(label_chbx).append(input_chbx);
    $j(panelBody_div).append(label_chbx);
};

AccordionFilter.prototype.initData = function() {
    //If the url is not set, you cannot load the filter items
    if ("undefined" === typeof this.options.url) {
        return false;
    }
    
    filter_pointer = this;
    
    data = $j.ajax({
        url: this.options.url,
        type: 'GET',
        dataType: 'json',
        error: function(jqXHR, textStatus, errorThrown) {},
        success: function(data) {
            filter_pointer.json_data = data;
            filter_pointer.initContainer();
        }
    });
    
};

AccordionFilter.prototype.handleOpenAllAccordion = function() {
    $j('.panel-collapse:not(".in")').collapse('show');
    $j('#sidebarGroupAccordion').find('span').removeClass('fa fa-plus').addClass('fa fa-minus');
};

AccordionFilter.prototype.handleCloseAllAccordion = function() {
    $j('.panel-collapse.in').collapse('hide');  
    $j('#sidebarGroupAccordion').find('span').removeClass('fa fa-minus').addClass('fa fa-plus');
};

AccordionFilter.prototype.handleOpenAccordion = function() {
    id = $j(this).attr("id");
    if ($j(this).hasClass('in')) {
        $j('.'+id).removeClass('fa fa-plus').addClass('fa fa-minus');
    }
};

AccordionFilter.prototype.handleCloseAccordion = function() {
    id = $j(this).attr("id");
    if (!$j(this).hasClass('in')) {
        $j('.'+id).removeClass('fa fa-minus').addClass('fa fa-plus');
    }
};

AccordionFilter.prototype.bindEvents = function() {
    filter_point = this;
    
    accordion_id = $j(this.accordion_div).attr('id');
    $j('#'+accordion_id+' input[type="checkbox"]').on("change",function() {filter_pointer.handleChangeFilterCheck(this,filter_pointer.alertObj);});
    $j('.closeall').on("click",filter_pointer.handleCloseAllAccordion);
    $j('.openall').on("click",filter_pointer.handleOpenAllAccordion);
    $j('.collapse').on("hidden.bs.collapse", this.handleCloseAccordion);
    $j('.collapse').on("shown.bs.collapse", this.handleOpenAccordion);
    
} ;

AccordionFilter.prototype.addFilterData = function(name, value) {
    if (this.filter_data[name].indexOf(value) == -1) {
        this.filter_data[name].push(value);
        $j(this.accordion_div).trigger("af.fd.changed");
    }
};

AccordionFilter.prototype.removeFilterData = function(name, value) {
    index = this.filter_data[name].indexOf(value);
    if (index != -1) {
        this.filter_data[name].splice(index, 1);
        $j(this.accordion_div).trigger("af.fd.changed");
    }
};

AccordionFilter.prototype.uncheckItem = function(chbx_id) {
    $j('#'+chbx_id).removeAttr('checked');
    this.removeFilterData($j('#'+chbx_id).attr("name"), $j('#'+chbx_id).attr("value"));
};

AccordionFilter.prototype.countFilterData = function() {
    the_count = 0;
    $j.each(this.filter_data, function(index, item) {
        the_count = the_count + item.length;
    });
    return the_count;
};

AccordionFilter.prototype.clearAll = function() {
    filter_pointer = this;
    $j.each(this.filter_data, function(index, item) {
        filter_pointer.filter_data[index] = [];
        $j('.filter_chbx').removeAttr('checked');
    });
    $j(this.accordion_div).trigger("af.fd.changed");
};

AccordionFilter.prototype.handleChangeFilterCheck = function(element, alertObj) {
    name = $j(element).prop("name");
    value = $j(element).prop("value");
    cf_div = name+'_cf';
    message = $j(element).data("alert");
    if (($j('#'+name+'_'+value).is(':checked'))){
        alertObj.addAlert(cf_div, name, value, message);
        this.addFilterData(name, value);
    } else {
        this.removeFilterData(name, value);
        alertObj.removeAlert(name+value+'_alert');
    }
};

AccordionFilter.prototype.filterData  = function () {
    return this.filter_data;
};

var AccordionFilterAlerts = function(sd, filter_pointer) {
    this.selected_div = sd;
    this.options = $j.extend({}, AccordionFilterAlerts.DEFAULTS, $j(this.selected_div).data());
    this.filter_pointer = filter_pointer;
    this.init();
};

AccordionFilterAlerts.DEFAULTS = {
        msg: "Items"
};

AccordionFilterAlerts.prototype.init = function() {
    $j(this.selected_div).hide();
    this.initContainerDiv();
};

AccordionFilterAlerts.prototype.initContainerDiv = function() {
    row_div = document.createElement("div");
    filteredElements_span = document.createElement("span");
    contentFilters_div = document.createElement("div");
    clear_span = document.createElement("span");
    
    $j(row_div).addClass("row-fluid");
    $j(contentFilters_div).addClass("span12").attr({"id":"contentFilters"});
    $j(clear_span).attr({"id":"clearbutton"}).append("<a href=\"#\"	class=\"btn btn-info\" role=\"button\">Clear All</a>");
    
    $j(row_div).append('<h2 aria-level="2" role="heading">Displaying '+this.options.msg+' for:</h2>').append(contentFilters_div);
    $j(this.selected_div).append(row_div).append("<br/>").append(clear_span).append("<br/><br/><br/>");
    
    filter_pointer = this.filter_pointer;
    alert_pointer = this;
    $j('#clearbutton').on("click", function() {
        filter_pointer.clearAll();
        alert_pointer.removeAllAlerts();
    });
} ;

AccordionFilterAlerts.prototype.addContentFilter = function(div_id) {
    column_div = document.createElement("div");
    $j(column_div).attr({"id":div_id});
    //TODO: should qualify this so it only takes the content filters of the current AccordionFilterAlerts objects
    $j("#contentFilters").append(column_div);
} ;

AccordionFilterAlerts.prototype.addAlert = function(div_id, name, value, message) {
    $j('#'+div_id).append('<div class="span3 alert filter_alert" id="'+ name+value +'_alert" data-chbxID="'+name+'_'+value+'"><a href="#" class="close">x</a> '+message+' </div>');
    $j(this.selected_div).show();
    alert_pointer = this;
    $j('#'+name+value +'_alert').on("click", function() {alert_pointer.handleAlertClose(this, alert_pointer); return true;});
} ;

AccordionFilterAlerts.prototype.removeAlert = function(alert_div_id) {
    $j('#'+alert_div_id).remove();
    //If we do not have any more alerts, hide the div.
    if (this.filter_pointer.countFilterData() == 0) {
        $j(this.selected_div).hide();
    }
} ;

AccordionFilterAlerts.prototype.removeAllAlerts = function() {
    $j('.filter_alert').remove();
    $j(this.selected_div).hide();
} ;

AccordionFilterAlerts.prototype.handleAlertClose = function(element, alertObj) {
    chbx_id = $j(element).attr("data-chbxID");
    alertObj.filter_pointer.uncheckItem(chbx_id);
    alertObj.removeAlert($j(element).attr("id"));
};

var allowedMethods = [
                      'filterData'
                      ];

$j.fn.accordionFilter = function(option) {
    var value,
    args = Array.prototype.slice.call(arguments, 1);
    
    this.each(function () {
        var $this = $j(this),
        data = $this.data('accordion.filter'),
        options = $j.extend({}, AccordionFilter.DEFAULTS, $this.data(),
                typeof option === 'object' && option);
        
        if (typeof option === 'string') {
            if ($j.inArray(option, allowedMethods) < 0) {
                throw new Error("Unknown method: " + option);
            }
            
            if (!data) {
                return;
            }
            
            value = data[option].apply(data, args);
            
            if (option === 'destroy') {
                $this.removeData('accordion.filter');
            }
        }
        
        if (!data) {
            $this.data('accordion.filter', (data = new AccordionFilter(this, options)));
        }
    });
    return typeof value === 'undefined' ? this : value;
};

$j.fn.accordionFilter.Constructor = AccordionFilter;
$j.fn.accordionFilter.defaults = AccordionFilter.DEFAULTS;
$j.fn.accordionFilter.methods = allowedMethods;

$j(document).ready(function(event) {
    //For all items with the data-toggle set to "filter-accordion" initialize the div
    $j('[data-toggle="filter-accordion"]').accordionFilter();
});

