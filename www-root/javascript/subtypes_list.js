var subtype_sortable;
var idx = 0;
function cleanupList() {
	ol = $('subtype_container');
	if(ol.immediateDescendants().length > 0) {
		ol.show();
		$('subtype_notice').hide();
	} else {
		ol.hide();
		$('subtype_notice').show();
	}
	subtype_sortable = Sortable.create('subtype_container', {
		onUpdate: writeOrder
	});
	writeOrder(null);
}

function writeOrder(container) {
	$('subtype_order').value = Sortable.sequence('subtype_container').join(',');	
}

document.observe('click', function(e, el) {
  if (el = e.findElement('.remove')) {
    $(el).up().remove();
    cleanupList();
  }
});


document.observe("dom:loaded", function() {        
	
	$('subtype_add').observe('click', function(event){
		value = $F('subtype_value');
		idx++;
		li = new Element('li', {id: 'type_'+type+'_'+idx, 'class': 'subtype'});
		li.insert(value+"  ");
		li.insert(new Element('span', {style: 'cursor:pointer;float:right;','class': 'remove'}).insert(new Element('img', {src: DELETE_IMAGE_URL})));
		$('subtype_container').insert(li);
		cleanupList();
		//fires the change event for the list holding the added elements
		if ("fireEvent" in $('subtype_order'))
			$('subtype_order').fireEvent("onchange");
		else
		{
			var evt = document.createEvent("HTMLEvents");
			evt.initEvent("change", false, true);
			$('subtype_order').dispatchEvent(evt);
		}
	});
	cleanupList();
});