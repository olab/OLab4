<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Javascript used by the clerkship logbook module.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2009 Queen's University. All Rights Reserved.
 *
*/
?>
<script type="text/javascript">
    document.observe('dom:loaded',function(){
			var relative = new Control.Window($('tooltip'),{  
				position: 'relative',  
				hover: true,  
				offsetLeft: 25,  
				width: 653,  
				className: 'default-tooltip'  
			});
    });

	function addObjective (objective_id, level) {
		if (!$('objective_'+objective_id+'_row')) {
			new Ajax.Updater('objective-list', '<?php echo ENTRADA_URL."/api/logbook-objective.api.php"; ?>', {
				parameters: 'id='+objective_id+'&level='+level,
				method: 'post',
				insertion: 'top',
				onComplete: function () {
                    if (!$('objective-list').visible()) {
                        $('objective-list').show();
                    }
					if (!$('objective-container').visible()) {
						$('objective-container').show();
					}
				}
			});
			
			$('all_objective_id').selectedIndex = 0;
			if ($('rotation_objective_id')) {
				$('rotation_objective_id').selectedIndex = 0;
			}
			if ($('deficient_objective_id')) {
				$('deficient_objective_id').selectedIndex = 0;
			}
			if ($('rotation-obj-item-'+objective_id)) {
				$('rotation-obj-item-'+objective_id).hide();
			}
			if ($('deficient-obj-item-'+objective_id)) {
				$('deficient-obj-item-'+objective_id).hide();
			}
			$('all-obj-item-'+objective_id).hide();
		}
	}
	
	function addProcedure (procedure_id, level) {
		if (!$('procedure_'+procedure_id+'_row')) {
			new Ajax.Updater('procedure-list', '<?php echo ENTRADA_URL."/api/logbook-procedure.api.php"; ?>', {
				parameters: 'id='+procedure_id+'&level='+level,
				method: 'post',
				insertion: 'top',
				onComplete: function () {
					if (!$('procedure-container').visible()) {
						$('procedure-container').show();
					}

					loadProcedureInvolvement($('proc_'+procedure_id+'_participation_level'));
				}
			});
			$('all_procedure_id').selectedIndex = 0;
			if ($('rotation_procedure_id')) {
				$('rotation_procedure_id').selectedIndex = 0;
			}
			if ($('deficient_procedure_id')) {
				$('deficient_procedure_id').selectedIndex = 0;
			}
			if ($('rotation-proc-item-'+procedure_id)) {
				$('rotation-proc-item-'+procedure_id).hide();
			}
			if ($('deficient-proc-item-'+procedure_id)) {
				$('deficient-proc-item-'+procedure_id).hide();
			}
			$('all-proc-item-'+procedure_id).hide();
		}
	}
	
	function removeObjectives () {
		var ids = new Array();
		$$('.objective_delete').each(
			function (element) { 
				if (element.checked) {
					ids[element.value] = element.value;
				}
			}
		);
		ids.each(
			function (id) {
				if (id != null) {
					$('objective_'+id+'_row').remove(); 
					$('all-obj-item-'+id).show();
					if ($('rotation-obj-item-'+id)) {
						$('rotation-obj-item-'+id).show();
					}
					if ($('deficient-obj-item-'+id)) {
						$('deficient-obj-item-'+id).show();
					}
				}
			}
		);
		var count = 0;
		$$('.objective_delete').each(
			function () { 
				count++;
			}
		);
		if (!count && $('objective-container').visible()) {
			$('objective-container').hide();
		}
	}
	
	function removeProcedures () {
		var ids = new Array();
		$$('.procedure_delete').each(
			function (element) { 
				if (element != null) {
					if (element.checked) {
						ids[element.value] = element.value;
					}
				}
			}
		);

		ids.each(
			function (id) { 
				if (id != null) {
					$('procedure_'+id+'_row').remove(); 

                    $('all-proc-item-'+id).show();
                    if ($('rotation-proc-item-'+id)) {
                        $('rotation-proc-item-'+id).show();
                    }
                    if ($('deficient-proc-item-'+id)) {
                        $('deficient-proc-item-'+id).show();
                    }
				}
			}
		);
		var count = 0;
		$$('.procedure_delete').each(
			function () { 
				count++;
			}
		);
		if (!count && $('procedure-container').visible()) {
			$('procedure-container').hide();
		}
	}
	
	function showRotationObjectives() {
        if ($('all_objective_id')) {
            $('all_objective_id').hide();
        }
        if ($('deficient_objective_id')) {
		    $('deficient_objective_id').hide();
        }
        if ($('rotation_objective_id')) {
	    	$('rotation_objective_id').show();
        }
	}
	
	function showDeficientObjectives() {
		if ($('all_objective_id')) {
           $('all_objective_id').hide();
        }
		if ($('rotation_objective_id')) {
           $('rotation_objective_id').hide();
        }
        if ($('deficient_objective_id')) {
           $('deficient_objective_id').show();
        }
	}
	
	function showAllObjectives() {
        if ($('rotation_objective_id')) {
	    	$('rotation_objective_id').hide();
        }
        if ($('deficient_objective_id')) {
	    	$('deficient_objective_id').hide();
        }
        if ($('all_objective_id')) {
	    	$('all_objective_id').show();
        }
	}
	
	function showRotationProcedures() {
        if ($('all_procedure_id')) {
           $('all_procedure_id').hide();
        }
        if ($('deficient_procedure_id')) {
           $('deficient_procedure_id').hide();
        }
        if ($('rotation_procedure_id')) {
           $('rotation_procedure_id').show();
        }
	}
	
	function showDeficientProcedures() {
        if ($('all_procedure_id')) {
            $('all_procedure_id').hide();
        }
        if ($('rotation_procedure_id')) {
            $('rotation_procedure_id').hide();
        }
        if ($('deficient_procedure_id')) {
            $('deficient_procedure_id').show();
        }
	}
	
	function showAllProcedures() {
        if ($('deficient_procedure_id')) {
            $('deficient_procedure_id').hide();
        }
        if ($('rotation_procedure_id')) {
            $('rotation_procedure_id').hide();
        }
        if ($('all_procedure_id')) {
            $('all_procedure_id').show();
        }
	}

	function loadProcedureInvolvement(selectBox) {
		selectBox.options[$$('#procedure-list tr:first-child td select')[0].selectedIndex].selected = true;
	}
</script>