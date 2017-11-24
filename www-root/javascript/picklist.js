/*
	Online Course Resources System [Pre-Clerkship]
	Developed By:	Medical Education Technology Unit
	Director:		Dr. Benjamin Chen <bhc@post.queensu.ca>
	Developers:	Matt Simpson <simpson@post.queensu.ca>

	$Id: picklist.js 700 2009-08-21 18:27:41Z jellis $

	Original Copyright Heading:	
	===============================================
	This is based on PickList II script - By Phil Webb (http://www.philwebb.com)
	Visit JavaScript Kit (http://www.javascriptkit.com) for this JavaScript and
	100s more. Please keep this notice intact.
*/

function addIt() {
	moveIt(document.getElementById('SelectList'), document.getElementById('PickList'));
}

function delIt() {
	moveIt(document.getElementById('PickList'), document.getElementById('SelectList'));
}

function moveIt(fbox, tbox) {
     var arrFbox	= new Array();
     var arrTbox	= new Array();
     var arrLookup	= new Array();
     var i;
     for(i = 0; i < tbox.options.length; i++) {
          arrLookup[tbox.options[i].text] = tbox.options[i].value;
          arrTbox[i] = tbox.options[i].text;
     }

     var fLength	= 0;
     var tLength	= arrTbox.length
     for(i = 0; i < fbox.options.length; i++) {
          arrLookup[fbox.options[i].text] = fbox.options[i].value;
          if(fbox.options[i].selected && fbox.options[i].value != "") {
               arrTbox[tLength] = fbox.options[i].text;
               tLength++;
          } else {
               arrFbox[fLength] = fbox.options[i].text;
               fLength++;
          }
     }

     arrFbox.sort();
     arrTbox.sort();
     fbox.length	= 0;
     tbox.length	= 0;
     var c;
     
     for(c = 0; c < arrFbox.length; c++) {
          var no	= new Option();
          no.value	= arrLookup[arrFbox[c]];
          no.text	= arrFbox[c];
          fbox[c]	= no;
     }
     
     for(c = 0; c < arrTbox.length; c++) {
     	var no	= new Option();
     	no.value	= arrLookup[arrTbox[c]];
     	no.text	= arrTbox[c];
     	tbox[c]	= no;
     }
}

function selIt() {
	var pickList	 = document.getElementById("PickList");
	var pickOptions = pickList.options;
	var pickOLength = pickOptions.length;

	for (var i = 0; i < pickOLength; i++) {
		pickOptions[i].selected = true;
	}
	
	return true;
}


function picklist_move(element, direction) {
	if (direction != "up") {
		direction = "down";
	}
	if (direction == "up") {
		if (element.previous("option") && !element.previous("option").selected) {
			element.previous("option").insert({before: element});
		}
	} else {
		if (element.next("option") && !element.next("option").selected) {
			element.next("option").insert({after: element});
		}
	}
}

function toggle_faculty_reorder_element(index, list) {
	if(((index) || (index === 0)) && (index != undefined) && (index != '-1')) {
		if(list.options.length > 1) {
			$('faculty_list_options_reorder').appear({ duration: 0.3 });
		}
	} else {
		close_faculty_reorder_element();
	}
} 

function close_faculty_reorder_element(list) {
	$('faculty_list_options_reorder').fade({ duration: 0.3 });
	for(i = 0; i < list.options.length; i++) {
		list.options[i].selected = false;
	}
}