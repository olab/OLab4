// PlotKit stuff for graphing
var layout = null;
var renderer = null;
var options = null;

function initDynamicTable() {
	options = PlotKit.Base.officeBlue();
	layout = new PlotKit.Layout("line", options);
	renderer = new PlotKit.SweetCanvasRenderer($('graph'), layout, options);
}	
function updateColorIcon(color_number) {
	if($('color-icon')) {
		if((!color_number) || (color_number < 1)  || (color_number > 6) || (color_number == '')) {
			color_number = 1;
		}

		if($('color-icon-' + color_number)) {
			$('color-icon-1').style.borderColor = '#FFFFFF';
			$('color-icon-2').style.borderColor = '#FFFFFF';
			$('color-icon-3').style.borderColor = '#FFFFFF';
			$('color-icon-4').style.borderColor = '#FFFFFF';
			$('color-icon-5').style.borderColor = '#FFFFFF';
			$('color-icon-6').style.borderColor = '#FFFFFF';
			$('color-icon-' + color_number).style.borderColor = '#999999';
			
		}

		$('color-icon').value = color_number;
	}
	
	chartReload();
	return;
}

function updatePollTypeIcon(polling_number) {
	if($('polling-type')) {
		if((!polling_number) || (polling_number < 1)  || (polling_number > 4) || (polling_number == '')) {
			polling_number = 1;
		}

		if($('polling-type-' + polling_number)) {
			$('polling-type-1').style.borderColor = '#FFFFFF';
			$('polling-type-2').style.borderColor = '#FFFFFF';
			$('polling-type-3').style.borderColor = '#FFFFFF';
			$('polling-type-4').style.borderColor = '#FFFFFF';
			$('polling-type-' + polling_number).style.borderColor = '#999999';
			
		}

		$('polling-type').value = polling_number;
	}
	chartReload();
	return;
}