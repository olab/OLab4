jQuery(document).ready(function($){
	if ($('#show_right_nav').val() == 1) {
		$('#change_next_nav_button').show();
	} else {
		$('#change_next_nav_button').hide();
	}
	
	$('#show_right_nav').live("click", function() {
		if ($(this).is(':checked')) {
			$('#change_next_nav_button').show();
		} else {
			$('#change_next_nav_button').hide();
		}
	});
	
	if ($('#show_left_nav').val() == 1) {
		$('#change_previous_nav_button').show();
	} else {
		$('#change_previous_nav_button').hide();
	}
	
	$('#show_left_nav').live("click", function() {
		if ($(this).is(':checked')) {
			$('#change_previous_nav_button').show();
		} else {
			$('#change_previous_nav_button').hide();
		}
	});
	
	$('#next_page_list input:radio[value=\'' + $('#selected_nav_next_page_id').val() + '\']').attr('checked', 'checked');
	
	$("#change_next_nav_button").live("click", function() {
		var modal_container = $("#modal_page_navigation");
		
		modal_container.dialog({
			title: "Change Page Navigation",
			modal: true,
			draggable: false,
			resizable: false,
			width: 500,
			minHeight: 550,
			maxHeight: 700,
			buttons: {
				Cancel : function() {

					$(this).dialog( "close" );
				},
				OK : function() {
					var selected_page = $('input[name=\'nav_next_page_id\']:checked').val();
					$('#selected_nav_next_page_id').attr("value", selected_page);
					$(this).dialog( "close" );
				}
			},
			close: function(event, ui){
				modal_container.dialog("destroy");
			}
		});
		return false;
	});
	
	$('#previous_page_list input:radio[value=\'' + $('#selected_nav_previous_page_id').val() + '\']').attr('checked', 'checked');
	
	$("#change_previous_nav_button").live("click", function() {
		var modal_container = $("#modal_previous_page_navigation");
		
		modal_container.dialog({
			title: "Change Page Navigation",
			modal: true,
			draggable: false,
			resizable: false,
			width: 500,
			minHeight: 550,
			maxHeight: 700,
			buttons: {
				Cancel : function() {

					$(this).dialog( "close" );
				},
				OK : function() {
					var selected_page = $('input[name=\'nav_previous_page_id\']:checked').val();
					$('#selected_nav_previous_page_id').attr("value", selected_page);
					$(this).dialog( "close" );
				}
			},
			close: function(event, ui){
				modal_container.dialog("destroy");
			}
		});
		return false;
	});
});