
var SelectTypeList = function() {
	var sortables = new Array();

	return function (list_type,select_id) {
		var select_sortable;	
		var idx = 0;
		var type = list_type;
		var s_id;
		if(select_id){
			s_id = select_id;
		}
		else{
			s_id = type+'_ids';
		}		
		
		function cleanupList() {
			ol = $(type+'_container');
			if(ol.immediateDescendants().length > 0) {
				ol.show();
				$(type+'_notice').hide();
			} else {
				ol.hide();
				$(type+'_notice').show();
			}
			select_sortable = Sortable.create(type+'_container', {
				onUpdate: writeOrder
			});
			writeOrder(null);
		}

		function writeOrder(container) {
			$(type+'_order').value = Sortable.sequence(type+'_container').join(',');	
		}

		document.observe('click', function(e, el) {
		  if (el = e.findElement('.remove')) {
			$(el).up().remove();
			cleanupList();
		  }
		});


		$(s_id).observe('change', function(event){
			select = $(s_id);
			option = select.options[select.selectedIndex];
			li = new Element('li', {id: 'type_'+type+'_'+option.value, 'class': type});
			li.insert(option.text+"  ");
			li.insert(new Element('span', {style: 'cursor:pointer;float:right;','class': 'remove'}).insert(new Element('img', {src: DELETE_IMAGE_URL})));
			$(type+'_container').insert(li);
			cleanupList();
			select.selectedIndex = 0;
			//fires the change event for the list holding the added elements
			if ("fireEvent" in $(type+'_order'))
				$(type+'_order').fireEvent("onchange");
			else
			{
				var evt = document.createEvent("HTMLEvents");
				evt.initEvent("change", false, true);
				$(type+'_order').dispatchEvent(evt);
			}
		});
		cleanupList();	

	};
}();