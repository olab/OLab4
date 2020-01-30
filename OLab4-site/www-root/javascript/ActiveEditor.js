	function ActiveEditor(options) {
		var url = options.url;
		var data_destination = options.data_destination;
		var action_form_selector = options.edit_forms_selector;
		var edit_modal = options.edit_modal;
		var messages = options.messages;
		var section = options.section;
		
		function process_entry(form_values) {
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
					}
				});
			document.fire(section+':onBeforeUpdate');
		}

		function entry_process_ajax(event) {
			Event.stop(event);
			var form = Event.findElement(event, 'form');
			var form_values = form.serialize(true);
			
			if ((form_values.action == "Edit") && edit_modal && edit_modal.container){

				var modal_confirm = edit_modal.container.down(".modal-confirm");
				var modal_close = edit_modal.container.down(".modal-close");
				
				function afterClose(event) {
					edit_modal.container.down("form").reset();
					modal_close.stopObserving("click", close_modal);
					modal_confirm.stopObserving("click", confirm_modal);
					edit_modal.container.down("form").stopObserving("submit", confirm_modal );
				}
				
				function close_modal(event) {
					//clear all of the fields.
					edit_modal.close();
				} 
				function confirm_modal(event) {
					Event.stop(event);
					edited_fields = edit_modal.container.down("form").serialize(true);
					edit_modal.close();
					Object.extend(form_values, edited_fields);
					process_entry(form_values);
				}
				
				edit_modal.container.down("form").observe("submit", confirm_modal );
				
				modal_confirm.observe("click", confirm_modal);
				modal_close.observe("click", close_modal);

				edit_modal.options.afterClose = afterClose;
				edit_modal.open();
								
				var owner = form.up(".entry");
				
				var edit_fields = owner.down("form.edit_data").serialize(true);
				
				//beginning edit. transfer data to modal form
				for(fieldname in edit_fields) {
					edit_modal.container.down("*[name="+fieldname+"]").setValue(edit_fields[fieldname]); //uses * selector as it might be an input, or a textarea... or a button, etc
				}
			}
		}

		function addListener (element) { element.observe('submit',entry_process_ajax) }
		function removeListener (element) { element.stopObserving('submit',entry_process_ajax) }
		
		var add_entry_listeners, remove_entry_listeners;
					
		
		add_entry_listeners =  function() { $$(action_form_selector).each(addListener); }
		remove_entry_listeners = function() { $$(action_form_selector).each(removeListener); }
		
		
		function onBeforeUpdate() {
			if(options.onBeforeUpdate) {
				options.onBeforeUpdate();
			}
			remove_entry_listeners();
		}

		function onAfterUpdate() {
			if(options.onAfterUpdate) {
				options.onAfterUpdate();
			}
			add_entry_listeners();
		}
		
		document.observe(section+':onBeforeUpdate', onBeforeUpdate);
		document.observe(section+':onAfterUpdate', onAfterUpdate);
		
		add_entry_listeners();
	}