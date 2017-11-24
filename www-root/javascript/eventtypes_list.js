var events_sortable;
var initial_total_duration;
function cleanupList() {
	ol = $('duration_container');
	if (ol != undefined) {
		if(ol.immediateDescendants().length > 0) {
			ol.show();
			$('duration_notice').hide();
		} else {
			ol.hide();
			$('duration_notice').show();
		}
	}
	var some_too_low = false;
	total = $$('input.duration_segment').inject(0, function(acc, e) {
		seg = parseInt($F(e), 10);
		if(seg < 60) {
			some_too_low = true;
		}
		if (Object.isNumber(seg)) {
			acc += seg;
		}
		return acc;
	});
	// if(some_too_low) {
	// 	alert("Error. No event types can have durations of less than 60 minutes.");
	// }
	if(typeof initial_total_duration == "undefined") {
		initial_total_duration = total;
	}
	str = 'Total time: '+total+' minutes';
	if(EVENT_LIST_STATIC_TOTAL_DURATION && total != initial_total_duration) {
		str += ', original total time: '+initial_total_duration+" minutes";
	}
	str += '.';
	if ($('total_duration') != undefined) {
		$('total_duration').update(str);
	}
	events_sortable = Sortable.create('duration_container', {
		onUpdate: writeOrder
	});
	writeOrder(null);
}

function writeOrder(container) {
	$('eventtype_duration_order').value = Sortable.sequence('duration_container').join(',');
}

document.observe('click', function(e, el) {
	if (el = e.findElement('.remove')) {
		$(el).up().remove();
		cleanupList();
	}
});


document.observe("dom:loaded", function() {
	if(typeof EVENT_LIST_STATIC_TOTAL_DURATION == "undefined") {
		EVENT_LIST_STATIC_TOTAL_DURATION = false;
	}
	if(typeof INITIAL_EVENT_DURATION != "undefined") {
		initial_total_duration = INITIAL_EVENT_DURATION;
	}

	//if ($('eventtype_ids') != undefined) {
	//	$('eventtype_ids').observe('change', function(event) {
	//		var select = $('eventtype_ids');
	//		var option = select.options[select.selectedIndex];
	//		var li = document.createElement('li');
	//		jQuery(li).attr('id','type_'+option.value).attr('class','');
	//
	//		var link = document.createElement('a');
	//		jQuery(link).attr('href','javascript:void(0)').attr('class','remove');
	//		var img = document.createElement('img');
	//		jQuery(img).attr('src',DELETE_IMAGE_URL);
	//		jQuery(link).html(img);
	//
	//		var span = document.createElement('span');
	//		jQuery(span).attr('class','duration_segment_container');
	//		var input = document.createElement('input');
	//		jQuery(input).attr('type','text').attr('class','input-mini duration_segment').attr('name', 'duration_segment[]').attr('onchange', 'cleanupList();').attr('value', '60');
	//		jQuery(span).append('Duration: ', input, ' minutes');
	//		jQuery(li).append(option.text+"  ", link, span);
	//		jQuery('#duration_container').append(li);
	//		cleanupList();
	//		select.selectedIndex = 0;
	//
	//	});
	//}
	cleanupList();
});