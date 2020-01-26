
var TextTypeList = function() {
	var sortables = new Array();

	return function (list_type,select_id) {
		var idx = 0;
		var text_sortable;
		var type = list_type;
		var s_id; 
		if(select_id){
			s_id = select_id;
		}
		else{
			s_id = type+'_value';
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
			text_sortable = Sortable.create(type+'_container', {
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


		
			$(type+'_add').observe('click', function(event){
				value = $F(s_id);
				idx++
				li = new Element('li', {id: 'type_'+type+'_'+idx, 'class': type});
				li.insert(value+"  ");
				li.insert(new Element('span', {style: 'cursor:pointer;float:right;','class': 'remove'}).insert(new Element('img', {src: DELETE_IMAGE_URL})));
				$(type+'_container').insert(li);
				cleanupList();
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