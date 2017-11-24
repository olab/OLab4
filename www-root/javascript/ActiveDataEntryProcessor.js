	function ActiveDataEntryProcessor(options) {
		var url = options.url;
		var data_destination = options.data_destination;
		var remove_forms_selector = options.remove_forms_selector;
		var new_button = options.new_button;
		var messages = options.messages;
		var section = options.section;
		var new_modal = options.new_modal;
		
		function process_entry(form_values) {
			if (submitting == false) {
				submitting = true;
				new Ajax.Updater(data_destination, url,
					{
						method:'post',
						parameters: form_values,
						evalScripts:true,
						onComplete: function () {
							if (messages) {
								messages.update(data_destination.down('.status_messages'));
							}
							document.fire(section+':onAfterUpdate');
							submitting = false;
						}
					});
				document.fire(section+':onBeforeUpdate');
			}
		}
		
		function new_entry_process_ajax(event) {
			Event.stop(event);
			
			if (new_modal && new_modal.container){
				var modal_confirm = new_modal.container.down(".modal-confirm");
				var modal_close = new_modal.container.down(".modal-close");
				
				function afterClose(event) {
					new_modal.container.down("form").reset();
					modal_close.stopObserving("click", close_modal);
					modal_confirm.stopObserving("click", confirm_modal);
					new_modal.container.down("form").stopObserving("submit", confirm_modal );
				}
				
				function close_modal(event) {
					new_modal.close();
				} 
				
				function confirm_modal(event) {
					Event.stop(event);
					form_values = new_modal.container.down("form").serialize(true);
					new_modal.close();
					process_entry(form_values);
				}
				
				new_modal.container.down("form").observe("submit", confirm_modal );
				
				modal_confirm.observe("click", confirm_modal);
				modal_close.observe("click", close_modal);

				new_modal.options.afterClose = afterClose;
				new_modal.open();
			}
		}
		
		function remove_entry(form) {
			if (confirm("Are you sure you want to delete this item?")) {
				var form_values = form.serialize(true);
				process_entry(form_values);
			}
		}

		function entry_remove_ajax(event) {
			Event.stop(event);
			var form = Event.findElement(event, 'form');
			remove_entry(form);
			
		}

		function add_entry_remove_listeners() {
			$$(remove_forms_selector).each(function (element) { element.observe('submit',entry_remove_ajax); });
		}
		
		function remove_entry_remove_listeners() {
			$$(remove_forms_selector).each(function (element) { element.stopObserving('submit',entry_remove_ajax); });
		}
		
		new_button.observe('click', new_entry_process_ajax);
		new_button.observe('keydown', new_entry_process_ajax);
		
		function onAfterUpdate() {
			if(options.onAfterUpdate) {
				options.onAfterUpdate();
			}
			add_entry_remove_listeners();
		}
		
		function onBeforeUpdate() {
			if(options.onBeforeUpdate) {
				options.onBeforeUpdate();
			}
			remove_entry_remove_listeners();
		}
		
		document.observe(section+':onAfterUpdate', onAfterUpdate);
		document.observe(section+':onBeforeUpdate', onBeforeUpdate);
		
		add_entry_remove_listeners();
	}