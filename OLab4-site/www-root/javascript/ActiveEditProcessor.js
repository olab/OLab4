	function ActiveEditProcessor(options) {
		var url = options.url;
		var data_destination = options.data_destination;
		var edit_form = options.edit_form;
		var edit_button = options.edit_button;
		var hide_button = options.hide_button;
		var messages = options.messages;
		var section = options.section;
		
		function edit_entry() {
			if (!(data_destination.empty())) {
				data_destination.childElements().invoke('remove');
			}
			
			new Ajax.Updater(data_destination, url,
				{
					method:'post',
					parameters: edit_form.serialize(),
					evalScripts:true,
					onComplete: function () {
						if (messages) {
							messages.update(data_destination.down('.status_messages'));
						}
						document.fire(section+':onAfterUpdate');
					}
				});
			hide_edit_entry_handle();
			document.fire(section+':onBeforeUpdate');
		}

		function submit_entry_ajax(event) {
			event.stop();
			edit_entry();
		}
		
		function show_edit_entry_handle(event) {
			edit_form.show();
			edit_button.hide();
			data_destination.hide();
			if (event) event.stop();
			return false;
		}
		
		function hide_edit_entry_handle(event) {
			edit_form.hide();
			edit_button.show();
			data_destination.show();
			if (event) event.stop();
			return false;
		}

		edit_button.observe('click', show_edit_entry_handle);
		edit_button.observe('keydown', show_edit_entry_handle);
		hide_button.observe('click', hide_edit_entry_handle);
		hide_button.observe('keydown', hide_edit_entry_handle);
		edit_form.observe('submit',submit_entry_ajax);
		
		function onBeforeUpdate() {
			if(options.onBeforeUpdate) {
				options.onBeforeUpdate();
			}
			document.stopObserving(section+':onBeforeUpdate', onBeforeUpdate);
		}

		document.observe(section+':onBeforeUpdate', onBeforeUpdate);
	}