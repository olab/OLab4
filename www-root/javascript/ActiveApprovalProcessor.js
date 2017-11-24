	function ActiveApprovalProcessor(options) {
		try {
		var url = options.url;
		var data_destination = options.data_destination;
		var action_form_selector = options.action_form_selector;
		var section = options.section;
		var messages = options.messages;
		var reject_modal = options.reject_modal;

		
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
			//having a reject modal defined changes the workflow a little.
			//if there is no reject_modal, and we're rejecting, then process_entry can go right ahead and work,
			//if not, then we have to pop up the modal and listen for the confirmation.
			var form_values = form.serialize(true);
			
			if ((form_values.action != "Reject") || !reject_modal){
				process_entry(form_values);
			}
			if ((form_values.action == "Reject") && reject_modal && reject_modal.container){
				//now we have to listen for the confirmation in the the modal, 
				//transfer the comment from the modal
				//and submit the source form above.
				
				var modal_confirm = reject_modal.container.down(".modal-confirm");
				var modal_close = reject_modal.container.down(".modal-close");
				var modal_comment = reject_modal.container.down("textarea"); 
				
				function afterClose() {
					modal_comment.clear();
					modal_close.stopObserving("click", close_modal);
					modal_confirm.stopObserving("click", confirm_modal);
				}
				
				function close_modal() {
					//clear all of the fields.
					reject_modal.close();
				} 
				function confirm_modal() {
					form_values.comment = modal_comment.getValue();
					reject_modal.close();
					process_entry(form_values);
				}

				modal_confirm.observe("click", confirm_modal);
				modal_close.observe("click", close_modal);
				
				reject_modal.options.afterClose = afterClose; 
				reject_modal.open();
				modal_comment.focus();
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
		
		} catch (e) {clog(e);}
	}