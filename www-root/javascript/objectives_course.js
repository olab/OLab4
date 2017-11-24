	var mapped = [];
	jQuery(document).ready(function($){
		jQuery('.objectives').hide();
		if(jQuery('#mapped_hierarchical_objectives').children('li').length == 0 && jQuery('#mapped_flat_objectives').children('li').length == 0){
			jQuery('#toggle_sets').trigger('click');
		}

		jQuery('#course-objectives-section').on('click', '.objective-remove', function(){
			var id = jQuery(this).attr('data-id');
			var key = jQuery.inArray(id,mapped);
			var list = jQuery('#mapped_objective_'+id).parent().attr('data-importance');
			var importance;
			if(list == "flat"){
				importance = 'clinical';
			}else{
				 var imp_select = jQuery(this).parent().find('.importance');
				 if(imp_select !== undefined && imp_select){
				 	var imp_val = jQuery(imp_select).val();
				 	switch(imp_val){
				 		case '3':
				 			importance = 'tertiary';
				 			break;
				 		case '2':
				 			importance = 'secondary';
				 			break;
				 		case '1':
				 		default:
				 			importance = 'primary';
				 			break;
				 	}
				 }else{
				 	importance = 'primary';
				 }
			}

			if(key != -1){
				mapped.splice(key,1);
			}
			jQuery('#check_objective_'+id).prop('checked',false);
			jQuery('#mapped_objective_'+id).remove();																		
			jQuery("#"+importance+"_objectives_select option[value='"+id+"']").remove();
			var children_exist = jQuery("#"+importance+"_objectives_select option").length;
			var mapped_siblings = false;		
			jQuery('#objective_'+id).siblings('li.objective-container').each(function(){
				var oid = jQuery(this).attr('data-id');
				if(jQuery('#check_objective_'+oid).prop('checked')){
					mapped_siblings = true;
				}
			});

			jQuery('#objective_'+id).parents('.objective-list').each(function(){
				var mapped_cousins = false;
				var pid = jQuery(this).attr('data-id');
				var mapped_parent = false;
				if(jQuery.inArray(pid,mapped) !== -1){
					mapped_parent = true;
				}
				if(mapped_siblings == false){
					jQuery('#objective_list_'+pid+' > li').each(function(){
						var cid = jQuery(this).attr('data-id');
						if(jQuery('#check_objective_'+cid).prop('checked')){
							mapped_cousins = true;
						}
					});
					if(mapped_cousins == false && mapped_parent == false){
						jQuery('#check_objective_'+pid).prop('checked',false);				
						jQuery('#check_objective_'+pid).prop('disabled',false);
					} else if(mapped_parent){
						jQuery('#check_objective_'+pid).prop('disabled',false);
					}
				}								
			});	

			if(list=="flat" && !children_exist){
				if(jQuery('#'+list+'-toggle').hasClass('expanded')){
					jQuery('#'+list+'-toggle').removeClass('expanded');
					jQuery('#'+list+'-toggle').addClass('collapsed');
					var d = jQuery('#'+list+'-toggle').next();
					jQuery(d).slideUp();
				}				
			}
			if(jQuery('#mapped_'+list+'_objectives').children('li').length == 0){
				var warning = jQuery(document.createElement('li'))
								.attr('class','display-notice')
								.html('No <strong>'+importance+' objectives</strong> have been mapped to this course.');
				jQuery('#mapped_'+importance+'_objectives').append(warning);				
			}							
		});

		jQuery('#course-objectives-section').on('change', '.checked-objective', function(){
			var id = jQuery(this).val();
			// parents will return all sets above that objective, which for anything other than curriculum objectives will be an array
			// this grabs all parents above the object and then fetches the list from the immediate (last) parent
			var sets_above = jQuery(this).parents('.objective-set');
			var list = jQuery(sets_above[sets_above.length-1]).attr('data-list');

			var title = jQuery('#objective_title_'+id).attr('data-title');
			var description = jQuery('#objective_'+id).attr('data-description');
			if (jQuery(this).is(':checked')) {				
				mapObjective(id,title,description,list);
			} else {
				jQuery('#objective_remove_'+id).trigger('click');
			}
		});

		jQuery('.mapping-toggle').click(function(){
			var state = $(this).attr('data-toggle');
			if(state == "show"){
				$(this).attr('data-toggle','hide');
				$(this).html('<i class="icon-minus-sign icon-white"></i> Hide Curriculum Tag Sets');
				jQuery('.mapped_objectives').animate({width:'60%'},400,'swing',function(){
					//jQuery('.objectives').animate({display:'block'},400,'swing');											
					jQuery('.objectives').css({width:'0%'});
					jQuery('.objectives').show();
					jQuery('.objectives').animate({width:'38%'},400,'linear');
				});										
			}else{
				$(this).attr('data-toggle','show');
				$(this).html('<i class="icon-plus-sign icon-white"></i> Show Curriculum Tag Sets');
				jQuery('.objectives').animate({width:'0%'},400,'linear',function(){
					jQuery('.objectives').hide();
					jQuery('.mapped_objectives').animate({width:'100%'},400,'swing');
				});																				
			}
		});



		jQuery('#course-objectives-section').on('change', '.importance', function(){
			var id = $(this).attr('data-id');
			var cur_val = $(this).attr('data-value');
			var imp = $(this).val();
			$(this).attr('data-value',imp);
			var desc = $(this).parent().parent().attr('data-description');
			var title = $(this).parent().parent().attr('data-title');
			var importance,new_importance;
			switch(cur_val){
				case '1':
					importance = 'primary';
					break;
				case '2':
					importance = 'secondary';
					break;
				case '3':
					importance = 'tertiary';
					break;
			}
			switch(imp){
				case '1':
					new_importance = 'primary';
					break;
				case '2':
					new_importance = 'secondary';
					break;
				case '3':
					new_importance = 'tertiary';
					break;
			}
			var option = jQuery('#'+importance+'_objectives_select option[value="'+id+'"]').clone(true);			
			jQuery('#'+importance+'_objectives_select option[value="'+id+'"]').remove();
			jQuery('#'+new_importance+'_objectives_select').append(option);

		});

		jQuery('#primary_objectives_select').children('option').each(function(){
			mapped.push($(this).val());
		});
		jQuery('#secondary_objectives_select').children('option').each(function(){
			mapped.push($(this).val());
		});		
		jQuery('#tertiary_objectives_select').children('option').each(function(){
			mapped.push($(this).val());
		});		
		jQuery('#clinical_objectives_select').children('option').each(function(){
			mapped.push($(this).val());
		});				

	});

	function mapObjective(id,title,description,list,importance){
		var ismapped = jQuery.inArray(id,mapped);
		if(ismapped !== -1){
			return;
		}		
		if(list === undefined || !list){
			list = 'flat';
		}								
		if(importance === undefined || !importance){
			importance = '1';
		}
		if(description === undefined || !description || description == null || description == 'null'){
			description = '';
		}
		
		var li = jQuery(document.createElement('li'))
						.attr('class','mapped-objective')
						.attr('id','mapped_objective_'+id)
						.attr('data-title',title)
						.attr('data-description',description)
						.html('<strong>'+title+'</strong>');
		var desc = jQuery(document.createElement('div'))
						.attr('class','objective-description')
						.attr('data-description',description);
		var sets_above = jQuery('#objective_'+id).parents('.objective-set');
		var set_id = jQuery(sets_above[sets_above.length-1]).attr('data-id');
		var set_name = jQuery('#objective_title_'+set_id).attr('data-title');
		if(set_name){
			jQuery(desc).html("Curriculum Tag Set: <strong>"+set_name+"</strong><br/>");
		}
		jQuery(desc).append(description);
		jQuery(li).append(desc);
		var controls = 	jQuery(document.createElement('div'))
							.attr('class','objective-controls');			
		var list_val = 'primary';						
		if(list == 'flat'){
			list_val = 'clinical';
		}else{									
			var imp = 	jQuery(document.createElement('select'))
								.attr('class','importance mini input-small')
								.attr('data-id',id)
								.attr('data-value',1);
			var pri = jQuery(document.createElement('option')).val(1).html('Primary');
			var sec = jQuery(document.createElement('option')).val(2).html('Secondary');
			var ter = jQuery(document.createElement('option')).val(3).html('Tertiary');									
			switch(importance){
				case '2':
					list_val = 'secondary';
					jQuery(sec).attr('selected','selected');
					break;
				case '3':
					list_val = 'tertiary';
					jQuery(ter).attr('selected','selected');
					break;
				default:
					jQuery(pri).attr('selected','selected');
					break;
			}
			jQuery(imp).append(pri).append(sec).append(ter);
			jQuery(controls).append(imp);												
		}

		var rm = jQuery(document.createElement('img'))
						.attr('data-id',id)
						.attr('src',SITE_URL+"/images/action-delete.gif")
						.attr('class','objective-remove list-cancel-image')
						.attr('id','objective_remove_'+id);
		
		jQuery(controls).append(rm);			
		jQuery(li).append(controls);											
													
		jQuery('#mapped_'+list+'_objectives').append(li);
		if(jQuery('#'+list+'-toggle').hasClass('collapsed')){
			jQuery('#'+list+'-toggle').removeClass('collapsed');
			jQuery('#'+list+'-toggle').addClass('expanded');
			var d = jQuery('#'+list+'-toggle').next();
			jQuery(d).slideDown();
		}
		jQuery('#mapped_'+list+'_objectives .display-notice').remove();
		if(jQuery("#"+list_val+"_objectives_select option[value='"+id+"']").length == 0){
		var option = jQuery(document.createElement('option'))
						.val(id)
						.prop('checked',true)
						.html(title);													
			jQuery('#'+list_val+'_objectives_select').append(option);
		}		
		jQuery(option).attr('selected','selected');
		jQuery('#check_objective_'+id).prop('checked',true);

		jQuery('#objective_'+id).parents('.objective-list').each(function(){
			var id = jQuery(this).attr('data-id');
			jQuery('#check_objective_'+id).prop('checked',true);
			jQuery('#check_objective_'+id).prop('disabled',true);
		});

/*
* This commented block should find any children of the objective that was checked and remove them from the selects
* before removing the DOM elements. Need to add code to objectives.js in the objective-title click handler
* to not load in children if objective is mapped, but only for courses I believe (perhaps more than courses)
* @todo: test commented out section, unable to test before leaving
*/	
		// jQuery('#objective_'+id+' li').each(function(){
		// 	var id = jQuery(this).attr('data-id');
		// 	var key = jQuery.inArray(id,mapped);
		// 	if(key != -1){
		// 		mapped.splice(key,1);
		// 		jQuery("#"+importance+"_objectives_select option[value='"+id+"']").remove();
		// 	}			
		// });

		// jQuery('#objective_'+id+'> li').slideUp(400,function(){
		// 	$(this).remove();
		// });

		mapped.push(id);								
	}