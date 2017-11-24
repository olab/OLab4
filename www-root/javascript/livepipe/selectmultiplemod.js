/**
 * @author Ryan Johnson <http://syntacticx.com/>
 * @copyright 2008 PersonalGrid Corporation <http://personalgrid.com/>
 * @package LivePipe UI
 * @license MIT
 * @url http://livepipe.net/control/rating
 * @require prototype.js, livepipe.js
 */

if(typeof(Prototype) == "undefined")
	throw "Control.SelectMultiple requires Prototype to be loaded.";
if(typeof(Object.Event) == "undefined")
	throw "Control.SelectMultiple requires Object.Event to be loaded.";

Control.SelectMultiple = Class.create({
	select: false,
	selecttype: true, //True if this.select is actually a select, false if it is an input
	container: false,
	numberOfCheckedBoxes: 0,
	checkboxes: [],
	selectedCheckboxes: '',
	categoryCheckboxes: [],
	hasExtraOption: false,
	
	initialize: function(select,container,options){
		this.options = {
			checkboxSelector: 'input[type=checkbox]',
			nameSelector: 'span.name',
			labelSeparator: ', ',
			valueSeparator: ',',
			afterChange: Prototype.emptyFunction,
			filter: false,
			resize: false,
			overflowString: function(str, length){
				return str.truncate(length-3);
			},
			overflowLength: 30,

			check: function(element) {
				element.checked = true;
				element.afterCheck();
			},
			unCheck: function(element) {
				element.checked = false;
				element.afterCheck();
			},
			afterCheck: function(element) {
				return true;
			}
		};

		Object.extend(this.options,options || {});
		Element.addMethods('INPUT', {
			check: this.options.check,
			unCheck: this.options.unCheck,
			afterCheck: this.options.afterCheck
		});

		this.select = $(select);

		//Check to see if the supplied select element is a select or an input.
		if(this.select.nodeName.toLowerCase() != 'select') {
			if(this.select.nodeName.toLowerCase() == 'input') {
				this.selecttype = false;
			} else {
				return false;
			}
		}
		this.container = $(container);
		this.checkboxes = (typeof(this.options.checkboxSelector) == 'function')
		? this.options.checkboxSelector.bind(this)()
		: this.container.getElementsBySelector(this.options.checkboxSelector)
		;
		
		this.categoryCheckboxes = (typeof(this.options.categoryCheckboxSelector) == 'function')
		? this.options.categoryCheckboxSelector.bind(this)()
		: this.container.getElementsBySelector(this.options.categoryCheckboxSelector)
		;

		var value_was_set = false;
		if(this.options.value){
			value_was_set = true;
			this.setValue(this.options.value);
			delete this.options.value;
		}

		this.hasExtraOption = false;
		
		//Observe checkboxes
		this.checkboxes.each(function(checkbox){
			checkbox.observe('click',this.checkboxOnClick.bind(this,checkbox));
		}.bind(this));
		
		this.categoryCheckboxes.each(function(checkbox){
			checkbox.observe('click',this.categoryCheckboxOnClick.bind(this,checkbox));
		}.bind(this));
		//Initialization of checkboxes and scanning (this is here to prevent erroneous loops later.)

		this.countAndCheckInit();
		
		if(!value_was_set)
			this.scanCheckBoxes();

		if(this.selecttype) {
			this.select.observe('change',this.selectOnChange.bind(this));
		}
		if(this.options.filter) {
			$(this.options.filter).observe('focus', function(event) {
				if($F(this.options.filter) == "Search...") {
					$(this.options.filter).value = '';
				}
			}.bindAsEventListener(this));

			$(this.options.filter).observe('blur', function(event) {
				if($F(this.options.filter) == "") {
					$(this.options.filter).value = 'Search...';
				}
			}.bindAsEventListener(this));

			$(this.options.filter).observe('keyup', function(event) {
				if($F(this.options.filter) !== "Filter...") {
					query = $F(this.options.filter);
					$$("#"+container+" .select_multiple_name").each(function(element) {
						element = $(element);
						parentelement = element.up();
						if(!parentelement.hasClassName('category') &&
							element.innerHTML.toLowerCase().indexOf(query.toLowerCase()) == -1) {
							parentelement.hide();
							parentelement.addClassName('filter-hidden');
						} else {
							parentelement.show();
						}
					}.bind(this))
				}
			}.bindAsEventListener(this));
		}

		if(this.options.resize) {
			new ElementResizer($(this.options.resize), {
				handleElement: $(this.container)
			});
		}

		this.notify('afterChange',((this.selecttype) ? this.select.options[this.select.options.selectedIndex].value : this.select.value));
	},
	destroy: function () {
		if(this.options.filter) {
			$(this.options.filter).stopObserving();
		}
		if(this.options.resize) {
			$(this.options.resize).remove();
		}
		this.checkboxes.each(function(checkbox){
			checkbox.stopObserving();
		});
	},
	
	//CountAndCheckInit: called on initialization ONLY
	countAndCheckInit: function(){
		this.numberOfCheckedBoxes = this.checkboxes.inject(0,function(number,checkbox){
			if(checkbox.checked) {
				checkbox.check();
				++number;
			}

			if(this.selecttype && this.select.options[this.select.options.selectedIndex].value == checkbox.value) {
				checkbox.check();
				++number;
			}

			return number;
		}.bind(this));
	},

	//Set Value: called to set a particular value of the select and multi select on start
	setValue: function(value_string){
		this.numberOfCheckedBoxes = 0;
		var value_collection = $A(value_string.split ? value_string.split(this.options.valueSeparator) : value_string)
		this.checkboxes.each(function(checkbox){
			checkbox.checked = false;
			value_collection.each(function(value){
				if(checkbox.value == value){
					++this.numberOfCheckedBoxes;
					checkbox.checked = true;
				}
			}.bind(this));
		}.bind(this));
		this.scanCheckBoxes();
	},

	//OnChange event handler for the select object
	selectOnChange: function(){
		this.removeExtraOption();
		this.checkboxes.each(function(checkbox) {
			if(this.select.options[this.select.options.selectedIndex].value == checkbox.value) {
				checkbox.check();
			} else {
				checkbox.unCheck();
			}
		}.bind(this));
		this.notify('afterChange',((this.selecttype) ? this.select.options[this.select.options.selectedIndex].value : this.select.value));
	},

	//Onclick event handleer for the checkbox
	checkboxOnClick: function(checkbox){
		this.numberOfCheckedBoxes += (checkbox.checked) ? 1 : -1;
		checkbox.afterCheck();
		this.scanCheckBoxes();
		this.notify('afterChange',((this.selecttype) ? this.select.options[this.select.options.selectedIndex].value : this.select.value));
	},
	
	checkboxOnClickFromCategory: function(checkbox) {
		this.numberOfCheckedBoxes += (checkbox.checked) ? 1 : -1;
		checkbox.afterCheck();
	}, 
	
	//Onclick event handler for category checkboxes
	categoryCheckboxOnClick: function(checkbox){
		category = checkbox.up().up();
		indent = this.getRowIndent(category);
		tr = category.next();
		i = 0;
		found = false;
		while(this.getRowIndent(tr) > indent || i > 150) {
			i++;
			rowcheckbox = tr.down().next().down();
			if(rowcheckbox) {
				found = true;
				rowcheckbox.checked = checkbox.checked;
				this.checkboxOnClickFromCategory(rowcheckbox);
			}
			tr = tr.next();
		}
		updatediv = this.options.updateDiv;
		this.options.updateDiv = null;
		this.scanCheckBoxes();
		if(checkbox.checked && found) {
			updatediv(['Everyone in the <strong>'+category.down().innerHTML+'</strong> list.']);
		} else {
			updatediv([]);
		}
		this.options.updateDiv = updatediv;
		return found;
	},
	
	getRowIndent: function (tr) {
		if(tr == null || tr.nodeName.toLowerCase() != 'tr') {
			return false;
		}
		td = tr.down();
		for(i = 0; i<6; i++) {
			if(td.hasClassName('indent_'+i)) {
				return i;
			}
		}
		return false;
	},
	//ScanCheckBoxes checks for single selections and gracefully selects them in the select element, otherwise handles addition and removal of elements
	//Called by initializer, setValue, checkboxOnClick
	scanCheckBoxes: function(){
		switch(this.numberOfCheckedBoxes){
			case 1:
				if(this.selecttype) {
					var found = false;
					this.checkboxes.each(function(checkbox){
						if(checkbox.checked){
							$A(this.select.options).each(function(option,i){
								if(option.value == checkbox.value){
									this.select.options.selectedIndex = i;
									found = true;
									throw $break;
								}
							}.bind(this));
							throw $break;
						}
						if(!found) {
							this.addExtraOption();
						}
					}.bind(this));
				} else {
					this.addExtraOption();
				}
				break;
			case 0:
				this.removeExtraOption();
				break;
			default:
				this.addExtraOption();
				break;
		};
	},


	getLabelForExtraOption: function(){
		var label = (typeof(this.options.nameSelector) == 'function'
			? this.options.nameSelector.bind(this)()
			: this.container.getElementsBySelector(this.options.nameSelector).inject([],function(labels,name_element,i){
				if(this.checkboxes[i].checked)
					labels.push(name_element.innerHTML);
				return labels;
			}.bind(this))
			).join(this.options.labelSeparator);
		return (label.length >= this.options.overflowLength && this.options.overflowLength > 0)
		? (typeof(this.options.overflowString) == 'function' ? this.options.overflowString(label, this.options.overflowLength) : this.options.overflowString)
		: label
	;
	},

	getValueForExtraOption: function(){
		return this.checkboxes.inject([],function(values,checkbox){
			if(checkbox.checked)
				values.push(checkbox.value);
			return values;
		}).join(this.options.valueSeparator);
	},

	addExtraOption: function(){
		this.removeExtraOption();
		this.hasExtraOption = true;
		if(this.selecttype) {
			this.select.options[this.select.options.length] = new Option(this.getLabelForExtraOption(),this.getValueForExtraOption());
			this.select.options.selectedIndex = this.select.options.length - 1;
		} else {
			this.select.value = this.getValueForExtraOption();
		}
		
		if(this.options.updateDiv != null) {
			var isnew = true;
			var label = (typeof(this.options.nameSelector) == 'function'
				? this.options.nameSelector.bind(this)()
				: this.container.getElementsBySelector(this.options.nameSelector).inject([],function(labels,name_element,i){
					if(this.checkboxes[i].checked) {
						labels.push(name_element.innerHTML);
						if ($(this.options.selectedCheckboxes)) {
							isnew = ($(this.options.selectedCheckboxes).value.search(this.checkboxes[i].value+',') == -1 && $(this.options.selectedCheckboxes).value.search(','+this.checkboxes[i].value) == -1 && $(this.options.selectedCheckboxes).value != this.checkboxes[i].value ? true : false);
						}
					}
					return labels;
				}.bind(this))
				);
			this.options.updateDiv(label, isnew);
		}
	},

	removeExtraOption: function(){
		if(this.hasExtraOption){
			if(this.selecttype) {
				this.select.remove(this.select.options.length - 1);
			} else {
				this.select.value = '';
			}
			this.hasExtraOption = false;
		}
		if(this.options.updateDiv != null) {
			this.options.updateDiv([]);
		}
	}
});
Object.Event.extend(Control.SelectMultiple);