<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Serves up the poll-js.php javascript with the Entrada URL included.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 * @version $Id: poll-js.php 1141 2010-04-27 20:17:41Z jellis $
 *
 * This code is originally from Democracy, a Word Press plug-in. They did a great job of
 * utilitizing AJAX, and I appreciate their CC, GPL licence.
 * http://blog.jalenack.com/archives/democracy/
 *
*/

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Register the Composer autoloader.
 */
require_once("autoload.php");

require_once("config/settings.inc.php");

date_default_timezone_set(DEFAULT_TIMEZONE);

header("Cache-Control: must-revalidate");
header("Expires: ".gmdate("D, d M Y H:i:s",time() + (86400 * 7))." GMT");
header("Content-Type: application/x-javascript");
?>
var SendPoll	= '<?php echo ENTRADA_URL; ?>/serve-polls.php?pollSend=true';
var GetPoll	= '<?php echo ENTRADA_URL; ?>/serve-polls.php?pollGet=true';

function initPoll() {
	// initiates the two objects for sending and receiving data
	httpReceiveVotes	= poll_getHTTPObject();
	httpSendVotes		= poll_getHTTPObject();
	view_results		= document.getElementById('view-results');

	if (view_results) {
		view_results.href = "javascript: SeeResults();";
	}

	addAnswer = document.getElementById('jalAddAnswer');

	if (addAnswer) {
		addAnswer.onclick = function () {
			this.style.display = "none";
			document.getElementById('jalAddAnswerRadio').style.display = "inline";
			document.getElementById('jalAddAnswerRadio').checked = true;
			document.getElementById('jalAddAnswerInput').style.display = "inline";

			all_inputs = document.getElementsByTagName('input');

			for (var i = 0; i < all_inputs.length; i++) {
				if (all_inputs[i].getAttribute('name') == "poll_answer_id" && all_inputs[i].getAttribute('id') != "jalAddAnswerRadio") {
					all_inputs[i].onclick = function () {
						document.getElementById('jalAddAnswerRadio').style.display = "none";
						document.getElementById('jalAddAnswerInput').style.display = "none";
						document.getElementById('jalAddAnswerInput').value = "";
						document.getElementById('jalAddAnswer').style.display = "inline";
					}
				}
			}

			return false;
		}
	}
}

function SeeResults(poll_id) {
	if (httpReceiveVotes.readyState == 4 || httpReceiveVotes.readyState == 0) {
		httpReceiveVotes.open("GET",GetPoll + '&poll_id='+poll_id+'&rand='+Math.floor(Math.random() * 1000000), true);
		httpReceiveVotes.onreadystatechange = function () {
			if (httpReceiveVotes.readyState == 4) {
				results	= httpReceiveVotes.responseText;
				the_poll	= document.getElementById("poll");
				height	= the_poll.offsetHeight;
				the_poll.style.minHeight = height + "px";
				the_poll.innerHTML = results;
			}
		}

		httpReceiveVotes.send(null);
	} else {
		setTimeout('SeeResults('+poll_id+')', 500);
	}
}

function ReadVote () {
	var the_vote;
	the_poll = document.getElementById("pollForm");
	for (x = 0; x < the_poll.poll_answer_id.length; x++) {
		if (the_poll.poll_answer_id[x].checked) {
			the_vote = the_poll.poll_answer_id[x].value;
		}
	}
	if (!the_vote) {
		alert ("Please vote on the poll prior to clicking the Vote button.");
	} else {
		SendVote(the_vote);
	}
	return false;
}

function SendVote (the_vote) {
	poll_id	= document.getElementById("poll_id").value;
	param	= 'poll_answer_id='+the_vote+'&poll_id='+poll_id;
	if (httpSendVotes.readyState == 4 || httpSendVotes.readyState == 0) {
		httpSendVotes.open("POST", SendPoll, true);
		httpSendVotes.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
		httpSendVotes.send(param);
	} else {
		setTimeout('SendVote('+the_vote+')', 400)
	}

	setTimeout('SeeResults('+poll_id+')', 400);
}


// brothercake's generic onload
// http://www.brothercake.com/site/resources/scripts/onload/
if(typeof window.addEventListener != 'undefined') {
	//.. gecko, safari, konqueror and standard
	window.addEventListener('load', initPoll, false);
} else if(typeof document.addEventListener != 'undefined') {
	//.. opera 7
	document.addEventListener('load', initPoll, false);
} else if(typeof window.attachEvent != 'undefined') {
	//.. win/ie
	window.attachEvent('onload', initPoll);
}

// initiates the XMLHttpRequest object
// as found here: http://www.webpasties.com/xmlHttpRequest
function poll_getHTTPObject() {
	var xmlhttp;
	/*@cc_on
	@if (@_jscript_version >= 5)
		try {
			xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
		} catch (e) {
			try {
				xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
			} catch (E) {
				xmlhttp = false;
			}
		}
	@else
		xmlhttp = false;
	@end @*/

	if (!xmlhttp && typeof XMLHttpRequest != 'undefined') {
		try {
			xmlhttp = new XMLHttpRequest();
		} catch (e) {
			xmlhttp = false;
		}
	}

	return xmlhttp;
}