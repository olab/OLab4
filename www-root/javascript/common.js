/**
 * Safely parse JSON and return a default error message (single string) if failure.
 *
 * @param data JSON
 * @param default_message string
 * @returns {Array}
 */

function safeParseJson(data, default_message) {
	try {
		var jsonResponse = JSON.parse(data);
	} catch (e) {
		var jsonResponse = [];
		jsonResponse.status = "error";
		jsonResponse.data = [default_message];
	}
	return jsonResponse;
}

// Converted to use jQuery
function toggle_list(element) {
    var element_id = "#" + element;
	if(jQuery(element_id).css('display') == 'none') {
		jQuery(element_id).slideDown(300);

        jQuery(element_id+'_state_btn').addClass('button-red');
        jQuery(element_id+'_state_btn').val('Hide List');

        jQuery(element_id+'_add_btn').fadeIn(300);
	} else {
		jQuery(element_id).slideUp(300);

        jQuery(element_id+'_state_btn').removeClass('button-red');
        jQuery(element_id+'_state_btn').val('Show List');

        jQuery(element_id+'_add_btn').fadeOut(300);
	}
}

// Converted to use jQuery
function toggle_visibility_checkbox(obj_id, element_id, effect) {
	if((!effect) || (effect != 'blind')) {
		effect = 'fade';
	}
	if(jQuery(element_id) != null) {
		if(jQuery(obj_id).prop('checked') == true) {
			switch(effect) {
				case 'fade' :
					jQuery(element_id).fadeIn();
				break;
				case 'blind' :
					jQuery(element_id).slideDown();
				break;
				default :
					jQuery(element_id).show();
				break;
			}
		} else {
			switch(effect) {
				case 'fade' :
					jQuery(element_id).fadeOut();
				break;
				case 'blind' :
					jQuery(element_id).slideUp();
				break;
				default :
					jQuery(element_id).hide();
				break;
			}
		}
	}
	return;
}

/**
 * This function does not appear to be used anywhere in Entrada.
 * EAH 2016/04/06
 */
/*
function toggle_visibility(element_id, effect) {
	if($(element_id) != null) {
		if($(element_id).style.display == 'none') {
			switch(effect) {
				case 'fade' :
					Effect.Appear(element_id);
				break;
				case 'blind' :
					Effect.BlindDown(element_id);
				break;
				default :
					$(element_id).style.display	= '';
				break;
			}
		} else {
			switch(effect) {
				case 'fade' :
					Effect.Fade(element_id);
				break;
				case 'blind' :
					Effect.BlindUp(element_id);
				break;
				default :
					$(element_id).style.display	= 'none';
				break;
			}
		}
	}
	return;
}
*/

// Converted to use jQuery
function updateTime(type) {
    type = '#' + type;
	var hour	= jQuery(type+'_hour').val();
	var minute	= jQuery(type+'_min').val();
	var suffix	= '';

	// If it's not past 12 don't bother.
	if(hour >= 12) {
		hour	= hour % 12;
		suffix	= 'PM';
	} else {
		suffix	= 'AM';
	}

	// Crude adjustments for silly 12 hour format.
	if(parseInt(hour) == 0) {
		hour = '12';
	}
	// Crude adjustments for the zeros.
	if(parseInt(minute) == 0) {
		minute = '00';
	}

	jQuery(type+'_display').html(hour+':'+minute+' '+suffix);

	return;
}

// Converted to use jQuery
function dateLock(inputField) {

	var field = '#' + inputField;
	if(jQuery(field)) {
		jQuery(field + '_text').removeClass('form-nrequired');
		jQuery(field + '_text').removeClass('form-required');

		if (jQuery(field).prop('checked') == true) {
			jQuery(field + '_text').addClass('form-required');
			jQuery(field + '_date').prop('disabled', false);

			if (jQuery(field + '_hour') != null) {
				jQuery(field + '_hour').prop('disabled', false);
			}
			if (jQuery(field + '_min') != null) {
				jQuery(field + '_min').prop('disabled', false);
			}
		} else {
			jQuery(field + '_text').addClass('form-nrequired');
			jQuery(field + '_date').prop('disabled', true);
			if (jQuery(field + '_hour') != null) {
				jQuery(field + '_hour').prop('disabled', true);
			}
			if (jQuery(field + '_min') != null) {
				jQuery(field + '_min').prop('disabled', true);
			}
		}
	}

	return;
}

/**
 * This function does not appear to be used anywhere in Entrada.
 * EAH 2016/04/13
 */
/*
function upload() {
	$('addbutton').disabled		= true;
	$('addbutton').style.color	= '#666666';
	$('status').innerHTML		= 'Please wait. Uploading data to server ...';

	document.forms[0].submit();
}
*/

function customConfig(config) {
	config.toolbar = [
		[ "bold", "italic", "underline", "separator",
		  "orderedlist", "unorderedlist", "outdent", "indent", "separator",
		  "htmlmode", "popupeditor"
		]
	];
	config.pageStyle	= 'body { font-family: Verdana, Arial, sans-serif; font-size: 12px; margin: 5px }';
	config.statusBar	= false;
}

/**
 * This function does not appear to be used anywhere in Entrada.
 * EAH 2016/04/13
 */
/*
function getSelectedButton(buttonGroup) {
	for (var i = 0; i < buttonGroup.length; i++) {
		if (buttonGroup[i].checked) {
			return i;
		}
	}
	return -1; //no button selected
}
*/

function sendFeedback(url) {
	if(url) {
		var windowW = 485;
		var windowH = 585;

		var windowX = (screen.width / 2) - (windowW / 2);
		var windowY = (screen.height / 2) - (windowH / 2);

		feedbackWindow = window.open(url, 'feedbackWindow', 'width='+windowW+', height='+windowH+', scrollbars=yes');
		feedbackWindow.blur();
		window.focus();

		feedbackWindow.resizeTo(windowW, windowH);
		feedbackWindow.moveTo(windowX, windowY);

		feedbackWindow.focus();
	}
	return;
}

function sendClerkship(url) {
	if(url) {
		var windowW = 485;
		var windowH = 585;

		var windowX = (screen.width / 2) - (windowW / 2);
		var windowY = (screen.height / 2) - (windowH / 2);

		clerkshipWindow = window.open(url, 'clerkshipWindow', 'width='+windowW+', height='+windowH+', scrollbars=yes');
		clerkshipWindow.blur();
		window.focus();

		clerkshipWindow.resizeTo(windowW, windowH);
		clerkshipWindow.moveTo(windowX, windowY);

		clerkshipWindow.focus();
	}
	return;
}

/**
 * This function does not appear to be used anywhere in Entrada.
 * EAH 2016/04/13
 */
/*
function sendAnonymousFeedback(url) {
	if(url) {
		var windowW = 505;
		var windowH = 525;

		var windowX = (screen.width / 2) - (windowW / 2);
		var windowY = (screen.height / 2) - (windowH / 2);

		feedbackWindow = window.open(url, 'feedbackWindow', 'width='+windowW+', height='+windowH+', scrollbars=yes');
		feedbackWindow.blur();
		window.focus();

		feedbackWindow.resizeTo(windowW, windowH);
		feedbackWindow.moveTo(windowX, windowY);

		feedbackWindow.focus();
	}
	return;
}
*/

function sendAccommodation(url) {
	if(url) {
		var windowW = 485;
		var windowH = 585;

		var windowX = (screen.width / 2) - (windowW / 2);
		var windowY = (screen.height / 2) - (windowH / 2);

		accommodationWindow = window.open(url, 'accommodationWindow', 'width='+windowW+', height='+windowH+', scrollbars=yes');
		accommodationWindow.blur();
		window.focus();

		accommodationWindow.resizeTo(windowW, windowH);
		accommodationWindow.moveTo(windowX, windowY);

		accommodationWindow.focus();
	}
	return;
}

/**
 * This function does not appear to be used anywhere in Entrada.
 * EAH 2016/04/13
 */
/*
function closeWindow() {
	window.close();

	if (window.opener && !window.opener.closed) {
		window.opener.focus();
	}
}
*/

function fieldCopy(copy_from, copy_to, copy_only_empty) {
	if((!copy_only_empty) || (copy_only_empty == null)) {
		copy_only_empty = 0;
	} else {
		copy_only_empty = 1;
	}

	if(((copy_only_empty) && (document.getElementById(copy_from) != null)) || (!copy_only_empty)) {
		if((!copy_only_empty) || ((copy_only_empty) && (document.getElementById(copy_to).value != ""))) {
		} else {
			document.getElementById(copy_to).value = document.getElementById(copy_from).value;
		}
	}

	return true;
}

function noPublic(obj) {
	obj.checked = false;
	alert('Non-Authenticated / Public Users cannot access this function at this time.');

	return;
}

function uploadPhoto() {
	if($('display-upload-button')) {
		if($('display-upload-status')) {
			if(($('photo_file')) && ($('photo_file').value != '')) {
				$('display-upload-button').innerHTML = $('display-upload-status').innerHTML;
			}
		}
	}

	if($('upload-photo-form')) {
		$('upload-photo-form').submit();
	}

	return;
}

function photoShow(url, width, height) {
	img = new Image(width, height);
	img.src = url;
	var win = new UI.Window(
	{
		shadow:	true,
		shadowTheme: "drop_shadow",
		theme: "alphacube",
		title: "User Photo",
		width: img.width + 4,
		height: img.height + 38,
		resizable: false
	}).center().setContent("<img src=\'"+url+"\' />").show();
}

function setMaxLength() {
	var x = document.getElementsByTagName('textarea');
	var counter = document.createElement('div');
	counter.className = 'content-small';
	for (var i=0;i<x.length;i++) {
		if (x[i].getAttribute('maxlength')) {
			var counterClone = counter.cloneNode(true);
			counterClone.relatedElement = x[i];
			counterClone.innerHTML = 'Character Count: <span>0</span>/'+x[i].getAttribute('maxlength');
			x[i].parentNode.insertBefore(counterClone,x[i].nextSibling);
			x[i].relatedElement = counterClone.getElementsByTagName('span')[0];

			x[i].onkeyup = x[i].onchange = checkMaxLength;
			x[i].onkeyup();
		}
	}
}

function checkMaxLength() {
	var maxLength = this.getAttribute('maxlength');
	var currentLength = this.value.length;
	if (currentLength > maxLength)
		this.relatedElement.className = 'content-red';
	else
		this.relatedElement.className = 'content-small';
	this.relatedElement.firstChild.nodeValue = currentLength;
}

var checkflag = 'false';
function selection(field) {
	if(checkflag == 'false') {
		if(!field.length) {
			field.checked = true;
		} else {
			for (i = 0; i < field.length; i++) {
				field[i].checked = true;
			}
		}
		checkflag = 'true';
		return;
	} else {
		if(!field.length) {
			field.checked = false;
		} else {
			for (i = 0; i < field.length; i++) {
				field[i].checked = false;
			}
		}
		checkflag = 'false';
		return;
	}
}

// Used on the Adding / Editing Calendar Events page.
function checkForNewRegion() {
	if(document.getElementById('region_id').options[document.getElementById('region_id').selectedIndex].value == 'new') {
		document.getElementById('new_region_layer').style.display = '';
		document.getElementById('new_region').focus();
	} else {
		document.getElementById('new_region_layer').style.display = 'none';
		document.getElementById('region_id').focus();
	}
}

/**
 * the following photo related functions are used in modules/public/people.inc.php
 */
var grow;

function growPic(official_photo, uploaded_photo, official_link, uploaded_link, zoomout) {
	if (!grow) {

		$$('.zoomin').each(function (e) { e.innerHTML = ''; });

		if (official_photo) {
			new Effect.Scale(official_photo, 300,
			{
				scaleMode:
				{
					originalHeight:	72,
					originalWidth:	72
				},
				beforeStart: function() {
					official_photo.style.zIndex = 8;
				},
				afterFinish: function() {
					zoomout.innerHTML = '<i class="fa fa-search-minus" aria-hidden="true"></i>';

					grow = true;
				}
			});

			if (official_link) {
				official_link.style.zIndex = 10;
				new Effect.Morph(official_link,
				{
					style: 'left: 15px; bottom: -164px; font-size: 24px; line-height: 26px; padding: 0px 5px 0px 5px;',
					duration: 1.0
				});
			}
		}

		if (uploaded_photo) {
			new Effect.Scale(uploaded_photo, 300,
			{
				scaleMode:
				{
					originalHeight:	72,
					originalWidth:	72
				},
				beforeStart: function() {
					uploaded_photo.style.zIndex	= 7;
				},
				afterFinish: function() {
					zoomout.innerHTML = '<i class="fa fa-search-minus" aria-hidden="true"></i>';

					grow = true;
				}
			});

			if (uploaded_link) {
				uploaded_link.style.zIndex = 10;
				new Effect.Morph(uploaded_link,
				{
					style: 'left: 47px; bottom: -164px; font-size: 24px; line-height: 26px; padding: 0px 5px 0px 5px;',
					duration: 1.0
				});
			}
		}
	}

	return false;
}

function shrinkPic(official_photo, uploaded_photo, official_link, uploaded_link, zoomout) {
	if ((official_photo && official_photo.width > 72) || (uploaded_photo && uploaded_photo.width > 72)) {

		zoomout.innerHTML = '';

		if (official_photo) {
			new Effect.Scale(official_photo, 100,
			{
				scaleFrom: (official_photo.width / 72 * 100),
				scaleMode:
				{
					originalHeight:	72,
					originalWidth:	72
				},
				afterFinish: function() {
					$$('.zoomin').each(function (e) { e.innerHTML = '<i class="fa fa-search-plus" aria-hidden="true"></i>'; });

					official_photo.style.zIndex = 6;

					grow = false;
				}
			});

			if (official_link) {
				new Effect.Morph(official_link,
				{
					style: 'left: 5px; bottom: 5px; font-size: 9px; line-height: 10px;  padding: 0px 2px 0px 2px;',
					duration: 1.0
				});
				official_link.style.zIndex = 6;
			}
		}

		if (uploaded_photo) {
			new Effect.Scale(uploaded_photo, 100,
			{
				scaleFrom: (uploaded_photo.width / 72 * 100),
				scaleMode:
				{
					originalHeight: 72,
					originalWidth: 72
				},
				afterFinish: function() {
					$$('.zoomin').each(function (e) { e.innerHTML = '<i class="fa fa-search-plus" aria-hidden="true"></i>'; });

					uploaded_photo.style.zIndex = 5;

					grow = false;
				}
			});

			if (uploaded_link) {
				new Effect.Morph(uploaded_link,
				{
					style: 'left: 19px; bottom: 5px; font-size: 9px; line-height: 10px; padding: 0px 2px 0px 2px;',
					duration: 1.0
				});
				uploaded_link.style.zIndex = 6;
			}
		}
	}

	return false;
}

var transitionRunning = false;

function hideOfficial(official_photo, active, inactive) {
	if (!transitionRunning) {
		transitionRunning = true;
		new Effect.Fade(official_photo,
		{
			duration: 0.3,
			to: 0.0,
			afterFinish: function() {
				transitionRunning	= false;
			}
		});
	}
}

function showOfficial(official_photo, active, inactive) {
	if (!transitionRunning) {
		transitionRunning = true;
		new Effect.Appear(official_photo, {
			duration: 0.3,
			to: 1.0,
			afterFinish: function() {
				transitionRunning = false;
			}
		});
	}
}

function createCookie(name,value,days) {
	if (days) {
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else var expires = "";
	document.cookie = name+"="+value+expires+"; path=/";
}

function readCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	}
	return null;
}

function eraseCookie(name) {
	createCookie(name,"",-1);
}

/**
 * Allows specification of a select all check box and a defined group of slaves to it. clicking the master (select all) will select/de-select all slaves. clicking one of the slaves may check/uncheck the master depending on the state of th other checkboxes (all checked -> master checked, one or more unchecked -> master unchecked)
 * @param master element which acts as the "selecct all" checkbox
 * @param slaves css selector pattern, or nodelist/array of elements
 */
function CheckboxCheckAll(master,slaves) {
	//if slaves is a string, then use it as a pattern and, if not, use the nodes

	function getSlaves() {
		if (typeof slaves == "string") {
			return $$(slaves);
		} else return slaves;
	}

	function getMaster() {
		return $(master);
	}

	function checkAll(event) {
		var state = getMaster().checked;
		getSlaves().reject(isDisabled).each(function (el) { el.checked=state; });
	}

	function areAllChecked() {
		return getSlaves().reject(isDisabled).pluck("checked").all();
	}

	function setCheckAll() {
		var state = areAllChecked();
		getMaster().checked=state;
	}

	this.disable = function () {
		getMaster().stopObserving('click',checkAll);
		getSlaves().invoke("stopObserving","click",setCheckAll);
	}

	this.enable = function() {
		var slaves = getSlaves();
		getMaster().observe('click',checkAll);
		slaves.invoke("observe","click",setCheckAll);
	}
	this.enable();
}

/**
 * Returns true if the passed element has a disabled property with a truthy value. false otherwise.
 * @param element
 * @return boolean
 */
function isDisabled(element) {
	return (!!(element.disabled));
}

/**
 *  Returns true if console.log is available. false otherwise.
 *  @return boolean
 */
function hasConsole() {
	return (typeof console != "undefined") && (console.log) && (typeof console.log == "function");
}

/**
 * passes arguments to console.log if it is available. otherwise does nothing.
 */
function clog() {
	return false;
}

/**
 * Capitalize the first letter of a string (i.e. word).
 */
function capitalizeFirstLetter(word)
{
   if (typeof word == "string") {
     return word.charAt(0).toUpperCase() + word.slice(1);
   } else {
     return word;
   }
}

/*
 * Displays a generic message
 */
function display_generic(err_array, target, location) {
	display_msg("info", err_array, target, location);
}

/*
 * Displays a notice
 */
function display_notice(err_array, target, location) {
	display_msg("notice", err_array, target, location);
}

/*
 * Displays a success message
 */
function display_success(err_array, target, location) {
	display_msg("success", err_array, target, location);
}

/*
 * Displays an error message
 */
function display_error(err_array, target, location) {
	display_msg("error", err_array, target, location);
}

/*
 * Called by other display_msg functions, or can be called directly with type.
 */
function display_msg(type, msg_array, target, location) {

	location = location == "append" ? "append" : "prepend";

	if (jQuery("div#display-" + type + "-box-modal").length > 0) {
		var msg_container = jQuery(target + " .alert-" + type);
		msg_container.children("ul").remove();
	} else {

		var msg_container = document.createElement("div");
		jQuery(msg_container).addClass("alert").addClass("alert-block").addClass("alert-"+type);

		var close_btn = document.createElement("button");
		jQuery(close_btn).addClass("close").attr("onclick", "jQuery('div#display-" + type + "-box-modal').hide();").attr("type", "button").html("&times;");

		jQuery(msg_container).append(close_btn);

	}

	jQuery(msg_container).attr("id", "display-" + type + "-box-modal");

	var msg_list = document.createElement("ul");
	for (var i = 0; i < msg_array.length; i++) {
		var msg_li = document.createElement("li");
		jQuery(msg_li).append(msg_array[i]);
		jQuery(msg_list).append(msg_li);
	}

	jQuery(msg_container).append(msg_list);

	if (jQuery("div#display-" + type + "-box-modal").length > 0) {
		if (jQuery("div#display-" + type + "-box-modal").not("visible")) {
			jQuery("div#display-" + type + "-box-modal").show();
		}
	} else {
		if (location == "append") {
			jQuery(target).append(msg_container);
		} else {
			jQuery(target).prepend(msg_container);
		}
	}

}

/**
 *  Appends a timer to the sidebar navigation based on a total number of seconds
 *  @param minutes - the total number of minutes, defaults to 60
 *  @param warning_threshold - the number of minutes at which the timer fades from blue to yellow, defaults to 25
 *  @param danger_threshold - the number of minutes at which the timer fades from yellow to red, defaults to 10
 */
function ui_timer (message, minutes, warning_threshold, danger_threshold, doSomething) {
    var count = typeof minutes !== 'undefined' ? (minutes * 60) : 3600;
    warning_threshold = typeof warning_threshold !== 'undefined' ? warning_threshold : 25;
    danger_threshold = typeof danger_threshold !== 'undefined' ? danger_threshold : 10;
    message = typeof message !== 'undefined' ? message : "You must <strong>submit</strong> the form before the timer expires.";
    
    add_timer(count, message, true);
    
    var counter = setInterval(function () { 
        count = count - 1;
        timer(count, warning_threshold, danger_threshold);
        if (count == 0) {
            clearInterval(counter);
            if (doSomething !== "undefined") {
                doSomething();
            }
            return;
        }
    }, 1000);
}

function timer (count, warning_threshold, danger_threshold) {
    var seconds = count % 60;
    var minutes = Math.floor(count / 60);
    
    if (minutes < 10) {
        minutes = "0" + minutes;
    }

    if (seconds < 10) {
        seconds = "0" + seconds;
    }

    if (minutes < danger_threshold) {
        jQuery(".timer").animate({
            backgroundColor: "#F2DEDE", 
            borderColor: "#EBCCD1", 
            color: "#A94442"
        }, "slow");
    } else if (minutes < warning_threshold) {
        jQuery(".timer").animate({ 
            backgroundColor: "#FCF8E3", 
            borderColor: "#FAEBCC", 
            color: "#8A6D3B"
        }, "slow");
    }
    
    jQuery(".minutes").html("<strong>"+ minutes +"</strong>");
    jQuery(".seconds").html("<strong>"+ seconds +"</strong>"); 
}

function add_timer (count, msg, affix) {
    var seconds = count % 60;
    var minutes = Math.floor(count / 60);
    
    if (minutes < 10) {
        minutes = "0" + minutes;
    }

    if (seconds < 10) {
        seconds = "0" + seconds;
    }
    
    var container = document.createElement("div");
    var icon_container = document.createElement("i");
    var minute_container = document.createElement("span");
    var colon_container = document.createElement("span");
    var second_container = document.createElement("span");
    var msg_container = document.createElement("p");
    
    jQuery(container).addClass("timer");
    jQuery(minute_container).addClass("minutes").append("<strong>" + minutes + "</strong>");
    jQuery(colon_container).text(":");
    jQuery(second_container).addClass("seconds").append("<strong>" + seconds + "</strong>");
    jQuery(msg_container).append(msg);
    
    jQuery(container).append(icon_container).append(minute_container).append(colon_container).append(second_container).append(msg_container);
    jQuery(".inner-sidebar").append(container);
    
    var sidebar_height = jQuery("#sidebar").height();
    var timer_height = jQuery(container).height() - 71;
    var offset = sidebar_height - timer_height;
    
    if (affix) {
        jQuery('.timer').affix({
            offset: {
                top: offset
            }
        });
    }
}

function sidebarBegone() {
    jQuery(function($) {
        $("#sidebar").hide();
        $("#content").removeClass("span9").addClass("span12").css("margin-left", "0");
    });
}

/**
 * jQuery Textarea AutoSize plugin
 * Author: Javier Julio
 * Licensed under the MIT license
 */
;(function ($, window, document, undefined) {

    var pluginName = "textareaAutoSize";
    var pluginDataName = "plugin_" + pluginName;

    var containsText = function (value) {
        return (value.replace(/\s/g, '').length > 0);
    };

    function Plugin(element, options) {
        this.element = element;
        this.$element = $(element);
        this.init();
    }

    Plugin.prototype = {
        init: function() {
            var height = this.$element.outerHeight();
            var diff = parseInt(this.$element.css('paddingBottom')) +
                parseInt(this.$element.css('paddingTop')) || 0;

            if (containsText(this.element.value)) {
                this.$element.height(this.element.scrollHeight - diff);
            }

            // keyup is required for IE to properly reset height when deleting text
            this.$element.on('input keyup', function(event) {
                var $window = $(window);
                var currentScrollPosition = $window.scrollTop();

                $(this)
                    .height(0)
                    .height(this.scrollHeight - diff);

                $window.scrollTop(currentScrollPosition);
            });
        }
    };

    $.fn[pluginName] = function (options) {
        this.each(function() {
            if (!$.data(this, pluginDataName)) {
                $.data(this, pluginDataName, new Plugin(this, options));
            }
        });
        return this;
    };

})(jQuery, window, document);

/**
 * To allow Bootstrap/jQuery plugins to work in a Prototype environment
 * disable Prototype's show() and hide() DOM element extensions for the affected plugins
 */
if (typeof Prototype !== 'undefined' && Prototype.BrowserFeatures.ElementExtensions) {
    var disablePrototypeJS = function (method, pluginsToDisable) {
            var handler = function (event) {
                event.target[method] = undefined;
                setTimeout(function () {
                    delete event.target[method];
                }, 0);
            };
            pluginsToDisable.each(function (plugin) {
                jQuery(window).on(method + '.bs.' + plugin, handler);
            });
        },
        //pluginsToDisable = ['collapse', 'dropdown', 'modal', 'tooltip', 'popover'];
        pluginsToDisable = ['collapse'];
    disablePrototypeJS('show', pluginsToDisable);
    disablePrototypeJS('hide', pluginsToDisable);
}

jQuery(document).ready(function($) {

    /**
     * if there are expandable text areas on the screen, enable them to auto-grow with content
     */
    $('textarea.expandable').textareaAutoSize();

    /**
     * check for the prototype based collapsable headings, and enable these with standard bootstrap collapse instead
     * This depends on the above disablePrototypeJS function above to disable Prototype show() and hide()
     */
    $('h2, .collapsable').each(function(){
        /**
         * Ignore if already using bootstrap collapse, or if specifically excluded with 'nocollapse' class
         */
        if (!($(this).attr('data-toggle') == 'collapse') && !$(this).hasClass('nocollapse')) {
            var child = "#" + $(this).prop('title').split(' ').join('-').toLowerCase();
            if (child.length > 1 && $(child).length) {
                $(this).attr('data-toggle', 'collapse').attr('data-target', child).addClass('collapsable');
                $(child).addClass('collapse');
                if (!$(this).hasClass('collapsed')) {
                    $(child).addClass('in');
                }
            }
        }
    });

    /**
     * Converted from Prototype to jQuery
     */
    $('ul.page-action > li:last-child').each(function () {
        if (!$(this).hasClass('last')) {
            $(this).addClass('last');
        }
    });

    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    });
});
