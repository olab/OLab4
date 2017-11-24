jQuery(document).ready(function($){
	function makeDraggable () {
		$(".draggable").draggable({
			revert:"invalid"
		});
	}

	function makeSortable () {
		$("#selected_export_options > li").sortable();
	}

	makeDraggable();

	$("#selected_export_options_container").droppable({
		accept: "ul#available_export_options > li",
		activeClass: "ui-state-hover",
		drop: function( event, ui ) {
			var item = $( "<li></li>" ).text( ui.draggable.text() ).addClass("ui-widget-content ui-state-default draggable");
			var span = $("<span></span>").addClass("ui-icon ui-icon-arrowthick-2-n-s");
			item.prepend(span);
			item.attr("data-field", ui.draggable.attr("data-field"));
			item.appendTo( $(this).find("ul") );
			ui.draggable.remove();
			//append the new option to the list of selected options.
			temp_option = {};
			temp_option[ui.draggable.attr("data-field")] = ui.draggable.text();
			var my_export_options = JSON.parse($('input[name=my_export_options]').val());
			$.extend(my_export_options, temp_option);
			$('input[name=my_export_options]').val(JSON.stringify(my_export_options));
		}
	}).sortable({
		items: "ul li",
		sort: function (event, ui) {
			var my_export_options = {};
			$("#selected_export_options > li").each(function(index) {
				my_export_options[$(this).attr("data-field")] = $(this).text();
			});
			$('input[name=my_export_options]').val(JSON.stringify(my_export_options));
		}
	});

	makeSortable();

	$("#available_export_options_container").droppable({
		accept: "ul#selected_export_options > li",
		activeClass: "ui-state-hover",
		drop: function( event, ui ) {
			var item = $( "<li></li>" ).text( ui.draggable.text() ).addClass("ui-widget-content ui-state-default draggable");
			ui.draggable.remove();
			item.appendTo( $(this).find("ul") );
			makeDraggable();
		}
	});

	$("#export-results-button").live("click", function() {
		var modal_container = $("#modal_export_container");

		modal_container.dialog({
			title: "Export Events",
			modal: true,
			draggable: false,
			resizable: false,
			width: 700,
			minHeight: 550,
			maxHeight: 700,
			buttons: [
				{
					text: "Cancel",
					"class": 'btn pull-left',
					click: function() {
						$(this).dialog( "close" );
					}
				},
				{
					text: "Export",
					"class": 'btn btn-primary pull-right',
					click: function() {
						var my_export_options = {};
						$("#selected_export_options > li").each(function(index) {
							my_export_options[$(this).attr("data-field")] = $(this).text();
						});
						$('input[name=my_export_options]').val(JSON.stringify(my_export_options));

						if ($('#display-error-box').length > 0) {
							$('#display-error-box').remove();
						}
						var url = modal_container.find("#my_export_options_form").attr("action") + "?" + modal_container.find("#my_export_options_form").serialize();
						window.location=url;
						$(this).dialog( "close" );
					}				
				}
			],
			close: function(event, ui){
				modal_container.dialog("destroy");
			}
		});
		return false;
	});
});
