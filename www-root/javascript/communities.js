function validateShortname(shortname) {
	if((shortname) && (shortname != '')) {
		shortname = shortname.replace(/\W/g, '');
		shortname = shortname.toLowerCase();
	} else {
		shortname	= '';
	}

	if(shortname.length > 20) {
		shortname	= shortname.truncate(20, '');	
	}
	
	document.getElementById('community_shortname').value		= shortname;
	document.getElementById('displayed_shortname').innerHTML	= shortname;

	return;
}

function selectRegistrationOption(selectedName) {
	if(selectedName == '2') {
		document.getElementById('community_registration_show_groups').style.display		= 'block';
		document.getElementById('community_registration_show_communities').style.display	= 'none';
	} else if(selectedName == '3') { 
		document.getElementById('community_registration_show_groups').style.display		= 'none';
		document.getElementById('community_registration_show_communities').style.display	= 'block';
	} else {
		document.getElementById('community_registration_show_groups').style.display		= 'none';
		document.getElementById('community_registration_show_communities').style.display	= 'none';
	}
	
	return;
}