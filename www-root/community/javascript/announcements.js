function onCalendar(obj) {
	if(obj.checked) {
		$('event_start').disabled	= false;
		$('event_start').checked	= true;
		dateLock('event_start');
		$('event_finish').disabled	= false;
		$('event_finish').checked	= true;
		dateLock('event_finish');
	} else {
		$('event_start').disabled	= true;
		$('event_start').checked	= false;
		dateLock('event_start');
		$('event_finish').disabled	= true;
		$('event_finish').checked	= false;
		dateLock('event_finish');
	}
	return;
}