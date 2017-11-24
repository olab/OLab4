function setDateValue(field, date) { 
	if (field.id == 'event_start_date') {
		field.value = date;
		$('event_finish_date').value = date;
	} else {
		field.value = date;
	}
	return;
}