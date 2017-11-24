jQuery(document).ready(function(){

	var sortables = new Array();

	jQuery('.user_name').each(function(){
		var that = this;
		var type = getType(this);
		var prfx = getPref(this);	
		console.log('Initializing user autocomplete');
		console.log('Type: '+type+ ' Prefix: '+prfx);

		var ac = jQuery(document.createElement('div'))
					.attr('class','autocomplete')
					.attr('id',prfx+type+'_name_auto_complete');		

		var assoc = jQuery(document.createElement('select'))
						.attr('style','display:none;')						
						.attr('id',prfx+'associated_'+type)
						.attr('name',prfx+'associated_'+type);
		if(jQuery(this).attr('data-multiple').length == 0 || jQuery(this).attr('data-multiple') != 'off'){
			jQuery(assoc).attr('multiple','multiple');
		}

		var btn = jQuery(document.createElement('button'))
					.attr('type','button')					
					.attr('id',prfx+type+'_btn')	
					.attr('class','button-sm user_add_btn')					
					.attr('data-type',type)
					.attr('data-prefix',prfx)
					.html('Add');

		var ref = jQuery(document.createElement('input'))
					.attr('type','hidden')
					.attr('id',prfx+type+'_ref')
					.attr('name',prfx+type+'_ref');

		var id = jQuery(document.createElement('input'))
					.attr('type','hidden')
					.attr('id',prfx+type+'_id')
					.attr('name',prfx+type+'_id');					

		jQuery(that).after(ac)
				.after(assoc)
				.after(btn)
				.after(ref)
				.after(id).each(function(){


				jQuery('#preceptor_director_list li').each(function(){
					var option = jQuery(document.createElement('option'))
									.attr('selected','selected')
									.val(jQuery('#'+prfx+type+'_id').val());
					jQuery('#'+prfx+'associated_'+type).append(option);
				});
					//var sq = Sortable.sequence(prfx+type+'_list');
					//jQuery('#'+prfx+'associated_'+type).val(sq);		
					
				new Ajax.Autocompleter(
										prfx+type+'_name', 
										prfx+type+'_name_auto_complete', 
										SITE_URL+'/api/personnel.api.php?type=faculty', 
										{
											frequency: 0.2, 
											minChars: 2, 
											afterUpdateElement: function (text, li) {
												//select
												if ((li.id != null) && (jQuery('#'+prfx+type+'_id').length)) {
													jQuery('#'+prfx+type+'_id').val(li.id);
												}
												//copy
												if ((jQuery('#'+prfx+type+'_name').length) && (jQuery('#'+prfx+type+'_ref').length)) {
													jQuery('#'+prfx+type+'_ref').val(jQuery('#'+prfx+type+'_name').val());
												}
											}
										});

					// Sortable.create(prfx+type+'_list', {
					// 			onUpdate : function() {
					// 				updateOrder(type,prfx);
					// 			}
					// 		});
				});		
	});	

	jQuery('.user_autocomplete').bind('keypress', function(event){
	    if (event.keyCode == Event.KEY_RETURN) {
			var type = getType(this);
			var prfx = getPref(this);
	        addItem(type,prfx);

	        Event.stop(event);
	    }
	});

	jQuery('.user_autocomplete').bind('keyup',function(){
		var type = getType(this);
		var prfx = getPref(this);
		if ((jQuery('#'+prfx+type+'_name').length) && (jQuery('#'+prfx+type+'_ref').length) && (jQuery('#'+prfx+type+'_id').length)) {
			if (jQuery('#'+prfx+type+'_name').val() != jQuery('#'+prfx+type+'_ref').val()) {
				jQuery('#'+prfx+type+'_id').val('');
			}
		}

		return true;		
	});

	jQuery('.user_autocomplete').bind('blur',function(){
		var type = getType(this);
		var prfx = getPref(this);	
		if ((jQuery('#'+prfx+type+'_id').length) && (jQuery('#'+prfx+type+'_id').val() != '') && (jQuery('#'+prfx+type+'_'+jQuery('#'+prfx+type+'_id').val()) == null)) {
			addItem(type,prfx);
		}
	});

	jQuery('.user_remove').live('click',function(){
		var type = getType(this);
		var prfx = getPref(this);
		var id = getId(this);
		if (jQuery('#'+prfx+type+'_'+id)) {
			jQuery('#'+prfx+type+'_'+id).remove();

			if(jQuery('#'+prfx+type+'_name').attr('data-multiple') == 'off'){
				jQuery('#'+prfx+type+'_btn').show();
				jQuery('#'+prfx+type+'_name').show();
			}	
			jQuery(prfx+'associated_'+type+' option[value="'+id+'"]').remove();		
			// Sortable.destroy($(prfx+type+'_list'));
			// Sortable.create(prfx+type+'_list', {
			// 	onUpdate : function (type) {
			// 		updateOrder(type,prfx);
			// 	}
			// });
			// updateOrder(type,prfx);
		}
	});

	jQuery('.user_add_btn').live('click',function(){
		var type = getType(this);
		var prfx = getPref(this);
		addItem(type,prfx);
	});

	function addItem(type,prfx) {
		console.log(type,prfx);
		if ((jQuery('#'+prfx+type+'_id').length) && (jQuery('#'+prfx+type+'_id').val() != '') && (jQuery('#'+prfx+type+'_'+jQuery('#'+prfx+type+'_id').val()).length == 0)) {
			var li = jQuery(document.createElement('li'))
						.attr('class','community')
						.attr('id',prfx+type+'_'+jQuery('#'+prfx+type+'_id').val())
						.attr('style','cursor: move;')
						.html(jQuery('#'+prfx+type+'_name').val());
			jQuery('#'+prfx+type+'_name').val('');

			var rm = jQuery(document.createElement('img'))
						.attr('src',SITE_URL+"/images/action-delete.gif")
						.attr('class','list-cancel-image user_remove')
						.attr('data-id',jQuery('#'+prfx+type+'_id').val())
						.attr('data-prefix',prfx)
						.attr('data-type',type);
			jQuery(li).append(rm);
			var option = jQuery(document.createElement('option'))
							.attr('selected','selected')
							.val(jQuery('#'+prfx+type+'_id').val());
			jQuery('#'+prfx+'associated_'+type).append(option);
			jQuery('#'+prfx+type+'_id').val('');
			jQuery('#'+prfx+type+'_list').append(li);

			if(jQuery('#'+prfx+type+'_name').attr('data-multiple') == 'off'){
				jQuery('#'+prfx+type+'_btn').hide();
				jQuery('#'+prfx+type+'_name').hide();
			}

			// sortables[type] = Sortable.destroy($(prfx+type+'_list'));
			// Sortable.create(prfx+type+'_list', {
			// 	onUpdate : function(){
			// 		updateOrder(type,prfx);
			// 	}
			// });
			// updateOrder(type,prfx);
		} else if (jQuery('#'+prfx+type+'_'+jQuery('#'+prfx+type+'_id').val()).length > 0) {
			alert('Important: Each user may only be added once.');
			jQuery('#'+prfx+type+'_id').val('');
			jQuery('#'+prfx+type+'_name').val('');

			return false;
		} else if (jQuery('#'+type+'_name').val() != '' && jQuery('#'+prfx+type+'_name').val() != null) {
			alert('Important: When you see the correct name pop-up in the list as you type, make sure you select the name with your mouse, do not press the Enter button.');

			return false;
		} else {
			return false;
		}
	}		

	function updateOrder(type,prfx) {
		//jQuery('#'+prfx+'associated_'+type).val(Sortable.sequence(prfx+type+'_list'));
	}	

	function getId(el){
		return jQuery(el).attr('data-id');
	}

	function getType(el){
		return jQuery(el).attr('data-type');
	}

	function getPref(el){
		var prfx = jQuery(el).attr('data-prefix');
		if(!prfx){
			prfx  = '';
		}
		return prfx;
	}
	
});