// Used for checking if the student selected a teacher not in the list.
function displayOtherTeacher() {
	if($('instructor_id').options[$('instructor_id').selectedIndex].value == 'other') {
		$('other_teacher_layer').style.display = 'block';
		$('other_teacher_fname').focus();
	} else {
		$('other_teacher_layer').style.display = 'none';
	}
	
	return;
}