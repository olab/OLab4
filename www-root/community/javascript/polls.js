// PlotKit stuff for graphing
var layout = new Array();
var renderer = new Array();
var options = new Array();

function initDynamicTable(question_id) {
	options[question_id]	= PlotKit.Base.officeBlue();
	layout[question_id]		= new PlotKit.Layout('line', options[question_id]);
	renderer[question_id]	= new PlotKit.SweetCanvasRenderer($('graph-'+question_id), layout[question_id], options[question_id]);
}

function addItem() {
	var currentItemCount	= parseInt($('itemCount').value);
	var newCount			= currentItemCount + 1;
	$('itemCount').value	= newCount;
	
	var row = document.createElement('li');
	var input = document.createElement('input');
     
	if ($('rowText').value != '')  {
     	// Append the button and div using newCount for list item removal.
		row.innerHTML	= '<div class="response_' + newCount + '" onmouseover="this.morph(\'background: #FFFFDD;\');" onmouseout="this.morph(\'background: #FFFFFF;\');" style="float:left; text-align: left; width: 90%" onclick="showEditor(this)">' + $('rowText').value + '</div><div style="float:right; text-align: right; width: 10%"><a class="btn btn-danger" style="height: 20px;" onclick="removeItem(' + newCount + ');"><i class="icon-trash icon-white" style="margin-top: 3px;"></i></a></div>';
     	row.id			= 'poll_responses_'+newCount;
     	row.style.clear	= 'both';
     	input.value		= $('rowText').value;
     	input.type		= 'hidden';
     	input.id		= 'response_'+newCount;
     	input.name 		= 'response['+newCount+']';
     	
       	$('poll_responses').appendChild(row);
       	
       	$('pollResponses').appendChild(input);
		
		// Clear the text box once the item is added
		$('rowText').value = '';
		$('rowText').focus();

		$('note').style.display = 'block';
		Sortable.destroy($('poll_responses'));
		Sortable.create('poll_responses', {onUpdate: updateDatabase});
		$('itemListOrder').value = Sortable.sequence('poll_responses');
		
	}
}

function removeItem(countItem) {
	$('poll_responses_'+countItem).remove();		
	$('response_'+countItem).remove();
		
	Sortable.destroy($('poll_responses'));
	Sortable.create('poll_responses', {onUpdate: updateDatabase});
	updateDatabase();		
			
	return;
}

var dontShowEditor = false;

function updateDatabase() {
	// Update the list order field that contains the sequenced order
	newItemListOrder			= Sortable.sequence('poll_responses');
	$('itemListOrder').value	= newItemListOrder;
	$('rowText').value			= '';
	dontShowEditor = true;
	return true;
}

function showHide(checkedValue) {
	if (checkedValue == 0) { 
		$('number_of_votes').fade({ duration: 0.3 });
		$('multiple_note').fade({ duration: 0.3 });
	} else if (checkedValue == 1) { 
		$('number_of_votes').appear({ duration: 0.3 });
		$('multiple_note').appear({ duration: 0.3 });
	}
}

function updateColorIcon(question_id, color_number) {
	if ($('color-icon-'+question_id)) {
		if ((!color_number) || (color_number < 1)  || (color_number > 6) || (color_number == '')) {
			color_number = 1;
		}

		if ($('color-icon-list-'+question_id+' color-icon-' + color_number)) {
			$('color-icon-list-'+question_id+' color-icon-1').style.borderColor = '#FFFFFF';
			$('color-icon-list-'+question_id+' color-icon-2').style.borderColor = '#FFFFFF';
			$('color-icon-list-'+question_id+' color-icon-3').style.borderColor = '#FFFFFF';
			$('color-icon-list-'+question_id+' color-icon-4').style.borderColor = '#FFFFFF';
			$('color-icon-list-'+question_id+' color-icon-5').style.borderColor = '#FFFFFF';
			$('color-icon-list-'+question_id+' color-icon-6').style.borderColor = '#FFFFFF';
			$('color-icon-list-'+question_id+' color-icon-' + color_number).style.borderColor = '#999999';
			
		}

		$('color-icon-'+question_id).value = color_number;
	}
	var chartReload = "chartReload"+question_id;
	eval(chartReload+'('+question_id+')');
	
	return;
}

function updatePollTypeIcon(question_id, polling_number) {
	if ($('polling-type-'+question_id)) {
		if ((!polling_number) || (polling_number < 1)  || (polling_number > 4) || (polling_number == '')) {
			polling_number = 1;
		}

		if ($('polling-type-list-'+question_id+' polling-type-' + polling_number)) {
			$('polling-type-list-'+question_id+' polling-type-1').style.borderColor = '#FFFFFF';
			$('polling-type-list-'+question_id+' polling-type-2').style.borderColor = '#FFFFFF';
			$('polling-type-list-'+question_id+' polling-type-3').style.borderColor = '#FFFFFF';
			$('polling-type-list-'+question_id+' polling-type-4').style.borderColor = '#FFFFFF';
			$('polling-type-list-'+question_id+' polling-type-' + polling_number).style.borderColor = '#999999';
			
		}

		$('polling-type-'+question_id).value = polling_number;
	}

	var chartReload = "chartReload"+question_id;
	eval(chartReload+'('+question_id+')');

	return;
}

function showHideMembers() {
	if (($('allow_member_read').checked == false) && ($('allow_member_vote').checked == false) && ($('allow_member_results').checked == false) && ($('allow_member_results_after').checked == false)) {
		$('members-list').hide();
		$('all_members').hide();
		$('specific_members').hide();
	} else if ($('all_members_vote').checked == true && $('specific_members').style.display != 'none') {
		$('members-list').hide();
	} else {
		if ($('members-list').style.display == 'none') {
			$('all_members').appear({ duration: 0.3 });
			$('specific_members').appear({ duration: 0.3 });
			if ($('specific_members_vote').checked) {
				$('members-list').appear({ duration: 0.3 });
			}
		}
	}				   	    
}

function setUnsetResults() {
	if ($('allow_member_vote').checked == false) {
		$('allow_member_results_after').checked = false;
		$('allow_member_results_after').disabled = true;
	} else if ($('allow_member_results').checked == true) {
		$('allow_member_results_after').checked = false;
		$('allow_member_results_after').disabled = true;
	} else {
		$('allow_member_results_after').disabled = false;
	}	   	    
}

function displayChart(old_question_id, new_question_id) {
	$('question-'+old_question_id).toggle();
//	updatePollTypeIcon(new_question_id, $('polling-type-'+old_question_id).value);
//	updateColorIcon(new_question_id, $('color-icon-'+old_question_id).value);
	$('question-'+new_question_id).toggle();
	$('pagination-links').innerHTML = "<ul>"
												+(parseInt(new_question_id) > 1 ? " <li><a href=\"javascript:displayChart('"+new_question_id+"','"+(new_question_id-1)+"');\"><i class=\"icon-chevron-left\" style=\"margin-top: 3px;\"></i></a></li>" : "")
												+(parseInt(new_question_id) > 3 ? " <li><a href=\"javascript:displayChart('"+new_question_id+"','1');\">1...</a></li>" : "")
												+(parseInt(new_question_id) > 2 ? " <li><a href=\"javascript:displayChart('"+new_question_id+"','"+(new_question_id-2)+"');\">"+(new_question_id-2)+"</a></li>" : "")
												+(parseInt(new_question_id) > 1 ? " <li><a href=\"javascript:displayChart('"+new_question_id+"','"+(new_question_id-1)+"');\" >"+(new_question_id-1)+"</a></li>" : "")
												+" <li class=\"active\" ><a>"+new_question_id+"</a></li>"
												+(parseInt(new_question_id) < (parseInt($('no-questions').innerHTML)) ? " <li><a href=\"javascript:displayChart('"+new_question_id+"','"+(parseInt(new_question_id)+1)+"');\" >"+(parseInt(new_question_id)+1)+"</a></li>" : "")
												+((parseInt(new_question_id)+1) < (parseInt($('no-questions').innerHTML)) ? " <li><a href=\"javascript:displayChart('"+new_question_id+"','"+(parseInt(new_question_id)+2)+"');\">"+(parseInt(new_question_id)+2)+"</a></li>" : "")
												+(((parseInt(new_question_id)+2) < (parseInt($('no-questions').innerHTML)) && (parseInt(new_question_id) < 3)) ? " <li><a href=\"javascript:displayChart('"+new_question_id+"','"+(parseInt(new_question_id)+3)+"');\" >"+(parseInt(new_question_id)+3)+"</a></li>" : "")
												+(((parseInt(new_question_id)+3) < (parseInt($('no-questions').innerHTML)) && (parseInt(new_question_id) < 2)) ? " <li><a href=\"javascript:displayChart('"+new_question_id+"','"+(parseInt(new_question_id)+4)+"');\">"+(parseInt(new_question_id)+4)+"</a></li>" : "")
												+((parseInt(new_question_id)+2) < (parseInt($('no-questions').innerHTML)) ? " <li><a href=\"javascript:displayChart('"+new_question_id+"','"+(parseInt($('no-questions').innerHTML))+"');\">..."+(parseInt($('no-questions').innerHTML))+"</a></li>" : "")
												+((parseInt(new_question_id)) < (parseInt($('no-questions').innerHTML)) ? " <li><a href=\"javascript:displayChart('"+new_question_id+"','"+(parseInt(new_question_id)+1)+"');\"><i class=\"icon-chevron-right\" style=\"margin-top: 3px;\"></i></a></li>" : "")
												+"</ul>";

//	var chartReload = "chartReload"+new_question_id;
//	eval(chartReload+'('+new_question_id+')');
}

function showEditor(element) {
	if (!dontShowEditor) {
		if (!element.hasClassName('editor') && $('edit-textbox') == null) {
			element.addClassName('editor');
			element.innerHTML = '<input id="edit-textbox" type="textbox" style="width: 100%; position: absolute;" value="'+element.innerHTML+'" onblur="hideEditor(this)" />';
			$('edit-textbox').focus();
			$('edit-textbox').observe('keypress', function(event){
			    if(event.keyCode == Event.KEY_RETURN) {
			        hideEditor($('edit-textbox'));
			        Event.stop(event);
			    }
			});
		}
	} else {
		dontShowEditor = false;
	}
}

function hideEditor(element) {
	if (element.parentNode.hasClassName('editor')) {
		element.parentNode.removeClassName('editor');
		$(element.parentNode.className).value = element.value;
		element.parentNode.innerHTML = element.value;
	}
}