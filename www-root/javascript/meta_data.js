/**
 * contains metadata functions for type/group/role view as well as user view
 */


/**
 * Adds a row to the table in user view
 * @param user_id
 * @param event
 */
function addUserRow(user_id, event) {
	if (event) {
		Event.stop(event);
	}
	
	/**
	 * the category id is on the page in a hidden input. this comes in with the table from the ajax call to show table
	 */
	var category_id = $('cat_id').getValue();
	new Ajax.Request(api_url,
		{
			method:'post',
			parameters: { proxy_id: user_id, type: category_id, request: 'new_value' },
			evalScripts:false,
			onSuccess: function (response) {
				var head = $('user_head_' + user_id);
				//a successful response will be valid xhtml which is easy to work with for extracting information. we use the response text for actual insertion later, however 
				var xml = response.responseXML;
				var value_id = xml.firstChild.getAttribute("id");
				if (value_id) {
					var value_parts = /value_edit_(\d+)/.exec(value_id);
					if (value_parts && value_parts[1]) {
						head.insert({after: response.responseText});
						document.fire('MetaData:onAfterRowInsert', value_parts[1]);
					}
				}
			},
			onComplete: function(response) {
				//anything other than 200 OK is considered an error. Obviously this isn't the case in fact, but in this case we can get by with this. other options include checking if status is >= 400, or handling specific error codes 
				if (response.status != 200) {
					display_error(response.responseText);
				}
			}
		});
	document.fire('MetaData:onBeforeRowInsert', user_id);
}

/**
 * Adds a row to the table in category view
 * @param category_id
 * @param event
 */
function addCategoryRow(category_id, event) {
	if (event) {
		Event.stop(event);
	}
	
	new Ajax.Request(api_url,
		{
			method:'post',
			parameters: { type: category_id, request: 'new_value' },
			evalScripts:true,
			onSuccess: function (response) {
				var head = $('cat_head_' + category_id);
				var xml = response.responseXML;
				var value_id = xml.firstChild.getAttribute("id");
				if (value_id) {
					var value_parts = /value_edit_(\d+)/.exec(value_id);
					if (value_parts && value_parts[1]) {
						head.insert({after: response.responseText});
						document.fire('MetaData:onAfterRowInsert', value_parts[1]);
					}
				}
			},
			onComplete: function(response) {
				//anything other than 200 OK is considered an error. Obviously this isn't the case in fact, but in this case we can get by with this. other options include checking if status is >= 400, or handling specific error codes 
				if (response.status != 200) {
					display_error(response.responseText);
				}
			}
		});
	document.fire('MetaData:onBeforeRowInsert', category_id);
}

/**
 * Marks the row associated with the specified value_id for deletion. actual deletion occurs when updating the table  
 * @param value_id
 */
function deleteRow(value_id) {
	var tr = $('value_edit_'+value_id);
	tr.setAttribute("class", "value_delete");
	var checkbox = $('delete_'+value_id);
	var opts = [ "enable", "disable" ];
	tr.select('input:not([type=checkbox]), select').invoke(opts[Number(checkbox.checked)]);
}

/**
 * utility function used for addRowReq and deleteRowReq. the supplied regex is used to to extract an id to be passed on to the supplied function 
 * @param regex
 * @param func
 * @returns {Function}
 */
function mkEvtReq(regex, func) {
	return function(event) {
		var element = Event.findElement(event);
		var tr = element.up('tr');
		var id = tr.getAttribute('id');
		var res = regex.exec(id);
		if (res && res[1]) {
			var target_id = res[1];
			func(target_id, event);
		}
		return false;
	};
}

/**
 * Event listener for delete requests. can be invoked directly if needed. not dependent on event calls
 * @param value_id
 */
function addDeleteListener(value_id) {
	var btn = $('delete_'+value_id);
	btn.observe('click', deleteRowReq);
}

/**
 * adds listeners for showing/hiding calendars in the specified value row 
 * @param value_id
 */
function addCalendarListener(value_id) {
	$$("#value_edit_"+value_id+ " input.date").each(function(e){
		e.observe('focus', focusHandler);
		e.observe('blur',blurHandler);
	});
}

/**
 * adds listeners for showing/hiding calendars in the data table 
 */
function addCalendarListeners() {
	$$(".DataTable input.date").each(function(e){
		e.observe('focus', focusHandler);
		e.observe('blur',blurHandler);
	});
}

/**
 * adds event listeners for the add buttons on all listed users 
 */
function addAddListeners() {
	$$('.DataTable .add_btn').invoke("observe", "click", addRowReq);
}

/**
 * adds event listeners for delete requests on all rows in the data table
 */
function addDeleteListeners() {
	$$('.DataTable .delete_btn').invoke("observe", "click", deleteRowReq);
}

/**
 * removes all event listeners on the data table
 */
function removeListeners() {
	$$('.DataTable .add_btn, .DataTable .delete_btn, #save_btn, .DataTable input.date').invoke("stopObserving");
}

/**
 * adds a listener for the save button in the data table
 */
function addSaveListener() {
	$('save_btn').observe("click", updateValues);
}

/**
 * save the current table. makes an ajax request. if the request is successful, the table is replaced with the new data. 
 * @param event
 */
function updateValues(event) {
	if (event) {
		Event.stop(event);
	}
	new Ajax.Request(api_url,
			{
				method:'post',
				parameters: $('meta_data_form').serialize(true),
				evalScripts:true,
				onSuccess: function (response) {
					removeListeners();
					$('meta_data_form').update(response.responseText);
					table_init();
					
				},
				onComplete: function(response) {
					document.fire('MetaData:onAfterUpdate');
					if (response.status != 200) {
						display_error(response.responseText);
					}
				}
			});
	//document.fire('MetaData:onBeforeUpdate');
}

/**
 * Gets the table based on the form values for Organisation, Group, etc.. Makes an ajax request. if succesful the table is shown (and replaces existing table, if any). 
 * @param event
 */
function getTable(event) {
	if (event) {
		Event.stop(event);
	}
	params = $('table_selector').serialize(true);
	if (params.associated_cat_id) {
		params.request = 'get_table';
		new Ajax.Request(api_url,
				{
					method:'post',
					parameters: params,
					evalScripts:true,
					onSuccess: function (response) {
						removeListeners();
						$('meta_data_form').update(response.responseText);
						table_init();
					},
					onComplete: function(response) {
						document.fire('MetaData:onAfterUpdate');
						if (response.status != 200) {
							display_error(response.responseText);
						}
					}
				});
		//document.fire('MetaData:onBeforeUpdate');
	}
}

/**
 * Gets the select box of categories by ajax. submits current options to get relevant category options
 */
function getCategories() {
	new Ajax.Request(api_url,
			{
				method:'post',
				parameters: $('table_selector').serialize(true),
				evalScripts:true,
				onSuccess: function (response) {
					$('assoc_cat_holder').update(response.responseText);
				},
				onComplete: function(response) {
					if (response.status != 200) {
						display_error(response.responseText);
					}
				}
			});
	document.fire('MetaData:onBeforeCatUpdate');
}

/**
 * Wraps the calendar functions in a single object.  
 */
var Calendar = function () {
	var current_target = null;
	
	var hide_request = 0;
	var show_request = 0;
	
	return {
		getTarget: function() {
			return current_target;
		},
		
		show: function(element) {
			if (current_target != element) { //to avoid resetting if called on the same element
				current_target = element;
				showCalendar('',element,element,null,element.getAttribute("id"),0,$(element).getHeight()+1,1);
			}
			Calendar.cancelHide();
		},
		
		hide: function() {
			hide_request = (new Date()).valueOf();
			function _hide() {
				if (hide_request > show_request) {
					hideCalendars();
					current_target = null;
				}
			}
			_hide.delay(0.2);
		},
		
		cancelHide: function() {
			function _cancelHide() {
				show_request = (new Date()).valueOf();
			}
			//remove from current stream, but insert before hide activates. using .defer() did not work as expected. delay(0) and defer() are supposed to be equivalent, but unfortunately are not behaving the same
			_cancelHide.delay(0);
		}
	};
}();

/**
 * Event listener for input focus events. For date fields this will show the calendar
 * @param event
 */
function focusHandler(event) {
	var element = Event.findElement(event, 'input.date');
	
	if(element) {
		Calendar.show(element);
	} 
}

/**
 * Event listener for input blur events. hides calendars
 * @param event
 */
function blurHandler(event){
	Calendar.hide();
}

/**
 * handles clicks anywhere on document. If the element is part of the calendar, it cancels the hide process. shows the calendar if the 
 * @param event
 */
function clickHandler(event) {
	var element = Event.findElement(event);
	//bizarre bug which causes prototype to sometimes be unable to get ancestors by selector. if body is not available, then we know we're hit by the bug. of course we have to exclude the case where body is clicked
	if (element != $(document.body)) {
		var body = element.up('body');
		var cal = element.up(".panel");
		if (!body || cal) {
			Calendar.cancelHide();
		}
	}
	focusHandler(event);
}

/**
 * initializes listeners every time a table is added
 */
function table_init() {
	addAddListeners();
	addDeleteListeners();
	addCalendarListeners();
	document.observe('MetaData:onAfterRowInsert', function(event) {
		addDeleteListener(event.memo);
		addCalendarListener(event.memo);
	});
	addSaveListener();
}

/**
 * returns a function to handle error messages. returned function takes a single parameter "text", which can be an html string
 * @param modal
 * @returns {Function}
 */
function ErrorHandler(modal) {
	if (modal && modal.container) {
		var modal_close = modal.container.down(".modal-close");
		function close_modal(event) {
			modal.close();
		} 
		modal_close.observe("click", close_modal);

		return function (text) { 
			modal.container.down(".status").update(text);
			modal.open();
		};
	}
}

/**
 * Sets the appropriate role options when the associated group changes.
 */
function setRoleList() {
	var group = $('associated_group').getValue();
	var roles = user_groups[group];
	
	if (roles) {
		var role_opts = "";
		roles.each(function (role) {
			role_opts += "<option value=\""+role.toLowerCase()+"\">"+role+"</option>";
		}); 
		
		$('associated_role').update(role_opts);
		
	} //else? broken? all groups have at least one role
}
