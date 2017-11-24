function getMSFromDate(date) {
	var jsDate	= toJSDate(date);
	var yy		= jsDate.getFullYear();
	var mm		= jsDate.getMonth();
	var dd		= jsDate.getDate();

	var hrIdx		= xcDateFormat.search(/hr/i);
	var miIdx		= xcDateFormat.search(/mi/i);
	var amIdx		= xcDateFormat.search(/am/i);

	var hr = ((hrIdx < 0) ? 0 : parseInt(date.substring(hrIdx, hrIdx+2), 10));
	var mi = ((miIdx < 0) ? 0 : parseInt(date.substring(miIdx, miIdx+2), 10));
	var am = ((amIdx < 0) ? 0 : date.substring(amIdx, amIdx+2) == ((xcAMPM[0]) ? 0 : 12));

	return (new Date(yy, mm, dd, hr+am, mi, 0)).getTime() / 1000;
}

function getD2(n) {
	return ((n < 10) ? '0' : '' ) + n;
}

function getDateValue(field) {
	if (field.value == '') {
		return '';
	}

	var jsDate	= new Date();
	jsDate.setTime(parseInt(field.value, 10) * 1000);

	var date		= toCalendarDate(jsDate)

	var hrIdx		= xcDateFormat.search(/hr/i);
	var miIdx		= xcDateFormat.search(/mi/i);
	var amIdx		= xcDateFormat.search(/am/i);

	var hr		= jsDate.getHours();
	var mi		= getD2(jsDate.getMinutes());
	var am;
	
	if(amIdx >= 0) {
		if(hr < 12) {
			am = xcAMPM[0];
		} else {
			am = xcAMPM[1];
			hr -= 12;
		}
	}
	
	hr = getD2(hr);
	
	if (hrIdx >= 0) {
		date = date.substring(0, hrIdx) + hr + date.substring(hrIdx + 2);
	}
	
	if (miIdx >= 0) {
		date = date.substring(0, miIdx) + mi + date.substring(miIdx + 2);
	}
	
	if (amIdx >= 0) {
		date = date.substring(0, amIdx) + am + date.substring(amIdx + 2);
	}

	return date;
}