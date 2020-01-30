//This is a counter for giving each file and title a unique suffix
var attachmentIndex = 0;

jQuery(document).ready(function() {
    jQuery("#uploaded_file").filestyle({
        icon: true,
        buttonText: " Find File"
    }); 

    jQuery('#file_attach_button').click(function() {
        attachClicked();
    });

//This sets the title field to be the name of the file selected for upload after choosing the file
jQuery('#uploaded_file').change(function() {
	jQuery('#uploaded_title').val(jQuery('#uploaded_file').val().replace(/^.*[\\\/]/, ''));
    jQuery('#uploaded_title').select().focus();
});
jQuery('#uploaded_title').keydown(function(event) {
    if (event.keyCode === 13) {
        event.preventDefault();
        jQuery('#file_attach_button').click();
        return false;
    }
});
});

//This checks the file input for a selected file, and if it is found, it stores the title and file inputs 
//as hidden and clears the visible inputs; it also shows the name of the file to the user with a link to
//remove it from being uploaded
function attachClicked() {
	if (jQuery('#uploaded_file').val()) {
		var fileUploadCopy = jQuery('#uploaded_file').clone();
		var titleUploadCopy = jQuery('#uploaded_title').clone().val('');
		fileUploadCopy.change(function() {
			jQuery('#uploaded_title').val(jQuery('#uploaded_file').val().replace(/^.*[\\\/]/, ''));
            jQuery('#uploaded_title').select().focus();
		});
        titleUploadCopy.keydown(function(event) {
            if (event.keyCode === 13) {
                event.preventDefault();
                jQuery('#file_attach_button').click();
                return false;
            }
        });
        
        var fileName = jQuery('#uploaded_file').val().replace(/^.*[\\\/]/, '');

        jQuery('.bootstrap-filestyle').hide();
		var fileUpload = jQuery('#uploaded_file').
				attr('id', 'uploaded_file'+attachmentIndex).attr('name', 'uploaded_file[]');
		var titleUpload = jQuery('#uploaded_title').
				attr('id', 'uploaded_title'+attachmentIndex).attr('name', 'uploaded_title[]');
        
		if (titleUpload.val() === '') {
            titleUpload.val(fileName);
		}
               
		jQuery('#attached-files').append(fileUpload);
		jQuery('#attached-files').append(titleUpload);

		jQuery('#file_attach_button').before(fileUploadCopy);
        fileUploadCopy.filestyle({ icon: true, buttonText: " Find File" });
        jQuery('#file_attach_button').before(titleUploadCopy);
        
        jQuery('#file_attach_button').css({'margin-left': '9px'});

		var div = jQuery('<div id="file'+attachmentIndex+'"></div>');
		var divText = '<span class="attached-file-wrapper">';
        divText += titleUpload.val() + ' (' + fileName + ')';
        divText += ' <a href="javascript:removeAttachment('+attachmentIndex+')"><i class="icon-remove"></i></a>'
        divText += '</span>';
		div.html(divText);
		jQuery('#attached-files').append(div);

		attachmentIndex++;
	}
}

function removeAttachment(index) {
	jQuery('#file'+index).remove();
	jQuery('#uploaded_file'+index).remove();
	jQuery('#uploaded_title'+index).remove();
}

function removeAttachedFile(file_id) {
    jQuery('#attached_file'+file_id).remove();
    jQuery('#attached-files').append(jQuery('<input type="hidden" name="files_to_remove[]" value="'+file_id+'"></input>'));
}