	function PassiveDataEntryProcessor(options) {
		var new_form = options.new_form;
		var new_button = options.new_button;
		var hide_button = options.hide_button;
		
		function show_new_entry_handle(event) {
			new_form.show();
			new_button.hide();
			if (event) event.stop();
			return false;
		}
		
		function hide_new_entry_handle(event) {
			new_form.hide();
			new_button.show();
			if (event) event.stop();
			return false;
		}

		new_button.observe('click', show_new_entry_handle);
		new_button.observe('keydown', show_new_entry_handle);
		hide_button.observe('click', hide_new_entry_handle);
		hide_button.observe('keydown', hide_new_entry_handle);	
	}