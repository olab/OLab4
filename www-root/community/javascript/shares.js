function noPublic(obj) {
	obj.checked = false;
	alert('Non-Authenticated / Public Users cannot access this function at this time.');

	return;
}



function addFile() {
	if (addFileHTML) {
		var file_id	= $$('#file_list div.file-upload').length;
		var newItem		= new Template(addFileHTML);

		$('file_list').insert(newItem.evaluate({'file_id' : file_id, 'file_number' : (file_id + 1)}));
	}

	return;
}

function addLink() {
	if (addLinkHTML) {
		var link_id	= $$('#link_list div.link-upload').length;
		var newItem		= new Template(addLinkHTML);

		$('link_list').insert(newItem.evaluate({'link_id' : link_id, 'link_number' : (link_id + 1)}));

		$('link_'+link_id).select('h2').each(function (el) {
			new CollapseHeadings(el);
		});
	}

	return;
}

function uploadFile() {
	/*if($('display-upload-button')) {
		if($('display-upload-status')) {
			if(($('uploaded_file')) && ($('uploaded_file').value != '')) {
				$('display-upload-button').innerHTML = $('display-upload-status').innerHTML;
			}
		}
	}

	if($('upload-file-form')) {
		$('upload-file-form').submit();
	}*/

	$('upload-file-form').submit();
	return;
}

function uploadLink() {
	$('upload-link-form').submit();
	return;
}

function fetchFilename(file_id) {
	var fn = $('uploaded_file_'+file_id).value;
	if (fn == ''){
		$('uploaded_file_'+file_id).value = '';
	} else {
			var filename = fn.match(/[\/|\\]([^\\\/]+)$/);

			if (filename == null) {
				filename = fn; // Opera
			} else {
				filename = filename[1];
			}

			$('file_'+file_id+'_title').value = filename;
		$('file_'+file_id+'_title').focus();
	}
}

function fetchLinkname(link_id) {
	var fn = $('link_url_'+link_id).value;
	if (fn == ''){
		$('link_url_'+link_id).value = '';
	} else {
			var linkname = fn.match(/[\/|\\]([^\\\/]+)$/);

			if (linkname == null) {
				linkname = fn; // Opera
			} else {
				linkname = linkname[1];
			}

			$('link_'+link_id+'_title').value = linkname;
		$('link_'+link_id+'_title').focus();
	}
}

function updateFolderIcon(folder_number) {
	if ($('folder_icon')) {
		if((!folder_number) || (folder_number < 1)  || (folder_number > 6) || (folder_number == '')) {
			folder_number = 1;
		}

		var folder_icon_number = folder_number;

		if($('folder-icon-' + folder_number)) {
			$('folder-icon-1').style.border = '1px solid #FFF';
			$('folder-icon-2').style.border = '1px solid #FFF';
			$('folder-icon-3').style.border = '1px solid #FFF';
			$('folder-icon-4').style.border = '1px solid #FFF';
			$('folder-icon-5').style.border = '1px solid #FFF';
			$('folder-icon-6').style.border = '1px solid #FFF';
			$('folder-icon-' + folder_number).style.border = '1px solid #999999';
		}

		$('folder_icon').value = folder_number;
	}

	return;
}

function acceptButton(cb) {
    if(cb.checked)
    {
        $('upload-button').disabled = false;
    }
    else
    {
        $('upload-button').disabled = true;
    }
    return;
}

function loadLink(url) {
    jQuery.ajax({
        type: "POST",
        url: url,
        dataType: "json",
        success: function(data) {
            if (data["feedback"] == "success") {
                var messageHTML = data["html"];
                jQuery("#loadLink").html(messageHTML);
            } else {
                var messageHTML = "<div id='display-error-box' class='alert alert-block alert-error'><button class='close' data-dismiss='alert' type='button'>×</button><ul><li id='messageText'>Subject and message failed to load successfully. Error Message: " + data["errorMSG"] + "</li></ul></div>";
                jQuery("#messageContainer2").show().html(messageHTML);
            }
        }
    });
}

jQuery(document).ready(function() {                                
    jQuery(document).on({
         click: function () {
            var target_value = jQuery(this).val();

            if (target_value == 1) {
                // hide iframe
                jQuery('#iframe_resize').hide();
            } else {
                //show iframe
                jQuery('#iframe_resize').show();
            }
         }
     }, ".access_method"); //pass the element as an argument to .on

    jQuery("#resizer-notes-link").click(function() {
        if (jQuery("#iframe-dev-notes").is(':visible')) {
            jQuery("#iframe-dev-notes td.dev-notes").hide();
			jQuery("#iframe-dev-notes").hide();
        } else {
            jQuery("#iframe-dev-notes").show();
			jQuery("#iframe-dev-notes td.dev-notes").show();
        }
    });

    jQuery("#variables-notes-link").click(function() {
        if (jQuery("#variables-dev-notes").is(':visible')) {
            jQuery("#variables-dev-notes").hide();
			jQuery("#variables-dev-notes td.dev-notes").hide();
        } else {
            jQuery("#variables-dev-notes").show();
			jQuery("#variables-dev-notes td.dev-notes").show();
        }
    });
});
