function noPublic(obj) {
	obj.checked = false;
	alert('Non-Authenticated / Public Users cannot access this function at this time.');

	return;
}

function addPhoto() {
	if (addPhotoHTML) {
		var photo_id	= $$('#photo_list div.photo-upload').length;
		var newItem		= new Template(addPhotoHTML);

		$('photo_list').insert(newItem.evaluate({'photo_id' : photo_id, 'photo_number' : (photo_id + 1)}));
	}

	return;
}

function uploadPhoto() {
	if($('display-upload-button')) {
		if($('display-upload-status')) {
			if(($('photo_file')) && ($('photo_file').value != '')) {
				$('display-upload-button').innerHTML = $('display-upload-status').innerHTML;
			}
		}
	}

	if($('upload-photo-form')) {
		$('upload-photo-form').submit();
	}

	return;
}

function uploadPhotos() {
	if ($('display-upload-button')) {
		if ($('display-upload-status')) {
			$('display-upload-button').innerHTML = $('display-upload-status').innerHTML;
		}
	}

	if ($('upload-photo-form')) {
		$('upload-photo-form').submit();
	}

	return;
}

function fetchPhotoFilename(photoNumber) {
	var fn = $('photo_file_'+photoNumber).value;
	if (fn == ''){
		$('photo_title_'+photoNumber).value = '';
	} else {
		if($('photo_title_'+photoNumber).value == '') {
			var filename = fn.match(/[\/|\\]([^\\\/]+)$/);

			if (filename == null) {
				filename = fn; // Opera
			} else {
				filename = filename[1];
			}

			$('photo_title_'+photoNumber).value = filename;
		}

		$('photo_title_'+photoNumber).focus();
	}
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
