function noPublic(obj) {
	obj.checked = false;
	alert('Non-Authenticated / Public Users cannot access this function at this time.');

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

			$('file_title').value = filename;
		$('file_title').focus();
	}
}