/*
 *	elementresizer.js
 *	
 *	Prototype port of the jQuery TextAreaResizer
 *	Created on 14th November 2008 by Sam Burney <sburney@sifnt.net.au>
 *	Reworked in June 2009 by Harry Brundage <hbrundage@qmed.ca>
 *	Version 0.2
 *
 *	Original More info: http://samburney.com/blog/text-area-resizer-prototype-port
 *	Original Demo: http://stmarys.sifnt.net.au/~sam/textarearesizer/
 *	Original version: http://plugins.jquery.com/project/ElementResizer
 */

function ElementResizer(id, options){
	this.element = $(id);
	this.staticOffset;
	this.iLastMousePos = 0;
	this.iMin = 80;
	this.grip;
	this.options = options;
	if(options.min) {
		this.iMin = options.min;
	}
	this.init();
}

ElementResizer.prototype.init = function(){
	this.element.addClassName('resize-processed')
	this.staticOffset = null;

	var grippie = new Element('div', {
		'class': 'grippie'
	});
	Element.extend(grippie);
	if(this.options.handleElement) {
		this.options.handleElement = $(this.options.handleElement);
		this.options.handleElement.insert(grippie);
	} else {
		this.element.insert(grippie);
		this.element.setStyle({
			'height':(this.element.getHeight() + 10)+'px'
		});

	}

	Event.observe(grippie, 'mousedown', this.startDrag.bindAsEventListener(this, this.element));
}

ElementResizer.prototype.startDrag = function(event){
	var data = $A(arguments);
	data.shift();
	this.element = data[0];
	this.iLastMousePos = event.pointerY();
	this.staticOffset = this.element.getHeight() - this.iLastMousePos;
	this.element.setStyle({
		'opacity': 0.7
	});

	Event.observe(document, 'mousemove', this.performDrag.bindAsEventListener(this));
	Event.observe(document, 'mouseup', this.endDrag.bindAsEventListener(this));

	return false;
}

ElementResizer.prototype.performDrag = function(event){
	var data = $A(arguments);
	data.shift();

	var iThisMousePos = event.pointerY();
	var iMousePos = this.staticOffset + iThisMousePos;
	if(this.iLastMousePos >= (iThisMousePos)){
		iMousePos -= 5;
	}
	this.iLastMousePos = iThisMousePos;
	iMousePos = Math.max(this.iMin, iMousePos);
	this.element.setStyle({
		'height': iMousePos + 'px'
	});
	if(iMousePos < this.iMin){
		this.endDrag(event);
	}

	return false;
}

ElementResizer.prototype.endDrag = function(event){
	var data = $A(arguments);
	data.shift();

	Event.stopObserving(document, 'mousemove');
	Event.stopObserving(document, 'mouseup');

	this.element.setStyle({
		'opacity': 1
	});
	this.element.focus();
	this.staticOffset = null;
	this.element = null;
	this.iLastMousePos = 0;

	if(this.options){
		if(this.options.afterDrag){
			this.options.afterDrag();
		}
	}
}
