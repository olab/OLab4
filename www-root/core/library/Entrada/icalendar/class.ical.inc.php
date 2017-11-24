<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
//+----------------------------------------------------------------------+
//| WAMP (XP-SP1/1.3.24/4.0.12/4.3.0)                                    |
//+----------------------------------------------------------------------+
//| Copyright (c) 1992-2003 Michael Wimmer                               |
//+----------------------------------------------------------------------+
//| I don't have the time to read through all the licences to find out   |
//| what the exactly say. But it's simple. It's free for non commercial  |
//| projects, but as soon as you make money with it, i want my share :-) |
//| (License : Free for non-commercial use)                              |
//+----------------------------------------------------------------------+
//| Authors: Michael Wimmer <flaimo@gmx.net>                             |
//+----------------------------------------------------------------------+
//
// $Id: class.ical.inc.php 1 2008-07-11 20:11:41Z simpson $

/**
* @package iCalendar Everything to generate simple iCal files
*/

/**#@+
* We need the child class
*/
include_once 'class.icalevent.inc.php';
include_once 'class.icaltodo.inc.php';
include_once 'class.icalfreebusy.inc.php';
include_once 'class.icaljournal.inc.php';
/**#@-*/

/**
* Create a iCalendar file for download
*
* $iCal = new iCal('', 0, '');
* $iCal->addEvent(…);
* $iCal->addToDo(…);
* …
* $iCal->outputFile('ics'); // output file as isc (xcs and rdf possible)
*
* Date/Time is stored with an absolute “z” value, which means that the
* calendar programm should import the time 1:1 not regarding timezones and
* Daylight Saving Time. MS Outlook imports “z” dates wrong, so you have to
* “correct” the dates BEFORE you add a new event.
* Also if you have an event series and not a single event, you have to use
* “File >> Import” in Outlook to import the whole series and not just the
* first date.
*
* Last Change: 2003-03-29
* Tested with WAMP (XP-SP1/1.3.24/4.0.4/4.3.0)
*
* @desc  Create a iCalendar file for download
* @access public
* @author Michael Wimmer <flaimo 'at' gmx 'dot' net>
* @copyright Michael Wimmer
* @link http://www.flaimo.com/
* @package iCalendar
* @abstract
* @example sample_ical.php Sample script
*/
class iCal {

	/*-------------------*/
	/* V A R I A B L E S */
	/*-------------------*/

	/**#@+
	* @var array
	* @access private
	*/
	/**
	* Array with all the iCalEvent objects
	*
	* @desc Array with all the iCalEvent objects
	*/
	var $icalevents = array();

	/**
	* Array with all the iCalToDo objects
	*
	* @desc Array with all the iCalToDo objects
	*/
	var $icaltodos = array();

	/**
	* Array with all the freebusy objects
	*
	* @desc Array with all the freebusy objects
	*/
	var $icalfbs = array();

	/**
	* Array with all the journal objects
	*
	* @desc Array with all the journal objects
	*/
	var $icaljournals = array();
	/**#@-*/

	/**#@+
	* @var string
	* @access private
	*/
	/**
	* Programm ID for the File
	*
	* @desc Programm ID for the File
	*/
	var $prodid = '-//flaimo.com//iCal Class MIMEDIR//EN';

	/**
	* Output string to be written in the iCal file
	*
	* @desc Output string to be written in the iCal file
	*/
	var $output;

	/**
	* Format of the output (ics, xcs, rdf)
	*
	* @desc Format of the output (ics, xcs, rdf)
	*/
	var $output_format;

	/**
	* Download directory where iCal file will be saved
	*
	* @desc Download directory where iCal file will be saved
	*/
	var $download_dir = 'icaldownload';

	/**
	* Filename for the iCal file to be saved
	*
	* @desc Filename for the iCal file to be saved
	*/
	var $events_filename;

	/**
	* Time the entry was created (iCal format)
	*
	* @desc Time the entry was created (iCal format)
	*/
	var $ical_timestamp;
	/**#@-*/

	/**#@+
	* @var int
	* @access private
	*/
	/**
	* ID number for the event array
	*
	* @desc ID number for the event array
	*/
	var $eventid = 0;

	/**
	* ID number for the todo array
	*
	* @desc ID number for the todo array
	*/
	var $todoid = 0;

	/**
	* ID number for the freebusy array
	*
	* @desc ID number for the freebusy array
	*/
	var $fbid = 0;

	/**
	* ID number for the journal array
	*
	* @desc ID number for the journal array
	*/
	var $journalid = 0;

	/**
	* Method: PUBLISH (1) or REQUEST (0)
	*
	* @desc Method: PUBLISH (1) or REQUEST (0)
	*/
	var $method = 1;
	/**#@-*/


	/*-----------------------*/
	/* C O N S T R U C T O R */
	/*-----------------------*/

	/**
	* Constructor
	*
	* Only job is to set all the variablesnames
	*
	* @desc Constructor
	* @param string $prodid  ID code for the iCal file (see setProdID)
	* @param int $method  PUBLISH (1) or REQUEST (0)
	* @param string $downloaddir
	* @return void
	* @access private
	* @uses setiCalTimestamp()
	* @uses setProdID()
	* @uses setMethod()
	* @uses checkClass()
	*/
	function iCal($prodid = '', $method = 1, $downloaddir = '', $filename = '') {
		if(trim($filename) == "") {
			$filename = "";	
		}
		
		$this->setiCalTimestamp();
		$this->setProdID($prodid);
        	$this->setMethod($method);
		if (strlen(trim($downloaddir)) > 0) {
			$this->download_dir = (string) $downloaddir;
		} // end if
		$this->events_filename  = (string) $filename . '.ics';
	} // end constructor

	/*-------------------*/
	/* F U N C T I O N S */
	/*-------------------*/

	/**
	* Encodes a string for QUOTE-PRINTABLE
	*
	* @desc Encodes a string for QUOTE-PRINTABLE
	* @param string $quotprint  String to be encoded
	* @return string  Encodes string
	* @access private
	* @since 1.001 - 2002-10-19
	* @author Harald Huemer <harald.huemer@liwest.at>
	*/
	function quotedPrintableEncode($quotprint = '') {
		/*
		//beim Mac Umlaute nicht kodieren !!!! sonst Fehler beim Import
		if ($progid == 3)
		  {
		  $quotprintenc = preg_replace("~([\x01-\x1F\x3D\x7F-\xBF])~e", "sprintf('=%02X', ord('\\1'))", $quotprint);
		  return($quotprintenc);
		  }
		//bei Windows und Linux alle Sonderzeichen kodieren
		else
		  {*/
		//if (!extension_loaded('mbstring')) {
			$quotprint = (string) str_replace('\r\n',chr(13) . chr(10),$quotprint);
			$quotprint = (string) str_replace('\n',chr(13) . chr(10),$quotprint);
			//$quotprint = (string) preg_replace("~([\x01-\x1F\x3D\x7F-\xFF])~e", "sprintf('=%02X', ord('\\1'))", $quotprint);
			$quotprint = (string) str_replace('\=0D=0A','=0D=0A',$quotprint);
			return (string) $quotprint;
		//} else {
		//	return (string) mb_encode_mimeheader($quotprint, 'iso-8859-1', 'Q');
		//} // end if
	} // end function


	/**
	* Checks if the download directory exists, else trys to create it
	*
	* @desc Checks if the download directory exists, else trys to create it
	* @return boolean
	* @access private
	*/
	function checkDownloadDir() {
		if (!is_dir($this->download_dir)) {
			return (boolean) ((!mkdir($this->download_dir, 0700)) ? FALSE : TRUE);
		} else {
			return (boolean) TRUE;
		} // end if
	} // end function


	/**
	* Returns string with the status of an attendee
	*
	* @desc Returns string with the status of an attendee
	* @param int $role
	* @return string $roles Status
	* @access private
	* @since 1.001 - 2002-10-10
	*/
	function getAttendeeRole($role = 2) {
		$roles = (array) array('CHAIR','REQ-PARTICIPANT','OPT-PARTICIPANT','NON-PARTICIPANT');
		return (string) ((array_key_exists($role, $roles)) ? $roles[$role] : $roles[2]);
	} // end function

	/**#@+
	* @access private
	* @return void
	*/
	/**
	* Set $prodid variable
	*
	* @desc Set $prodid variable
	* @param string $prodid
	* @see getProdID()
	* @see $prodid
	*/
	function setProdID($prodid = '') {
		if (strlen(trim($prodid)) > 0) {
			$this->prodid = (string) $prodid;
		} // end if
	} // end function

	/**
	* Set $method variable
	*
	* @desc Set $method variable
	* @param int $method
	* @see getMethod()
	* @see $method
	* @since 1.001 - 2002-10-10
	*/
	function setMethod($method = 1) {
		if (is_int($method) && preg_match('(^([0-1]{1})$)', $method)) {
			$this->method = (int) $method;
		} // end if
	} // end function

	/**
	* Set $ical_timestamp variable
	*
	* @desc Set $ical_timestamp variable
	* @see getiCalTimestamp()
	* @see $ical_timestamp
	*/
	function setiCalTimestamp() {
		$this->ical_timestamp = (string) gmdate('Ymd\THi00\Z',time());
	} // end function
	/**#@-*/

	/**#@+
	* @access public
	*/
	/**
	* Get $prodid variable
	*
	* @desc Get $prodid variable
	* @return string $prodid
	* @see setProdID()
	* @see $prodid
	*/
	function getProdID() {
		return (string) $this->prodid;
	} // end function

	/**
	* Get $method variable
	*
	* @desc Get $method variable
	* @return string $methods
	* @see setMethod()
	* @see $methods
	* @since 1.001 - 2002-10-10
	*/
	function getMethod() {
		$methods = (array) array('REQUEST','PUBLISH');
		return (string) ((array_key_exists($this->method, $methods)) ? $methods[$this->method] : $methods[1]);
	} // end function

	/**
	* Get $ical_timestamp variable
	*
	* @desc Get $ical_timestamp variable
	* @return string $ical_timestamp
	* @see setiCalTimestamp()
	* @see $ical_timestamp
	*/
	function getiCalTimestamp() {
		return (string) $this->ical_timestamp;
	} // end function

	/**
	* Get class name
	*
	* @desc Get class name
	* @param int $int
	* @return string $classes
	*/
	function getClassName($int = 0) {
		$classes = (array) array('PRIVATE','PUBLIC','CONFIDENTIAL');
		return (string) ((array_key_exists($int, $classes)) ? $classes[$int] : $classes[0]);
	} // end function

	/**
	* Get status name
	*
	* @desc Get status name
	* @param int $int
	* @return string $statuscode
	* @since 1.011 - 2002-12-22
	*/
	function getStatusName($int = 0) {
		$statuscode = (array) array('TENTATIVE','CONFIRMED','CANCELLED');
		return (string) ((array_key_exists($int, $statuscode)) ? $statuscode[$int] : $statuscode[0]);
	} // end function

	/**
	* Get frequency name
	*
	* @desc Get frequency name
	* @return string $frequencies
	* @see setFrequency(), $frequencies
	* @since 1.010 - 2002-10-26
	*/
	function getFrequencyName($int = 0) {
		$frequencies = (array) array('ONCE','SECONDLY','MINUTELY','HOURLY','DAILY','WEEKLY','MONTHLY','YEARLY');
		return (string) ((array_key_exists($int, $frequencies)) ? $frequencies[$int] : $frequencies[0]);
	} // end function
	/**#@-*/

	/**#@+
	* @access public
	* @return void
	*/
	/**
	* Adds a new Event Object to the Events Array
	*
	* @desc Adds a new Event Object to the Events Array
	* @param array $organizer  The organizer - use array('Name', 'name@domain.com')
	* @param int $start  Start time for the event (timestamp; if you want an allday event the startdate has to start at 00:00:00)
	* @param int $end  Start time for the event (timestamp or write 'allday' for an allday event)
	* @param string $location  Location
	* @param int $transp  Transparancy (0 = OPAQUE | 1 = TRANSPARENT)
	* @param array $categories  Array with Strings (example: array('Freetime','Party'))
	* @param string $description  Description
	* @param string $summary  Title for the event
	* @param int $class  (0 = PRIVATE | 1 = PUBLIC | 2 = CONFIDENTIAL)
	* @param array $attendees  key = attendee name, value = e-mail, second value = role of the attendee [0 = CHAIR | 1 = REQ | 2 = OPT | 3 =NON] (example: array('Michi' => 'flaimo@gmx.net,1'); )
	* @param int $prio  riority = 0-9
	* @param int $frequency  frequency: 0 = once, secoundly – yearly = 1–7
	* @param mixed $rec_end  recurrency end: ('' = forever | integer = number of times | timestring = explicit date)
	* @param int $interval  Interval for frequency (every 2,3,4 weeks…)
	* @param string $days  Array with the number of the days the event accures (example: array(0,1,5) = Sunday, Monday, Friday
	* @param string $weekstart  Startday of the Week ( 0 = Sunday – 6 = Saturday)
	* @param string $exept_dates  exeption dates: Array with timestamps of dates that should not be includes in the recurring event
	* @param int $alarm  Array with all the alarm information, “''” for no alarm
	* @param int $status  Status of the event (0 = TENTATIVE, 1 = CONFIRMED, 2 = CANCELLED)
	* @param string $url  optional URL for that event
	* @param string $language  Language of the strings used in the event (iso code)
	* @param string $uid  Optional UID for the event
	* @see getEvent()
	* @uses iCalEvent
	*/
	function addEvent($organizer, $start, $end, $location, $transp, $categories,
					  $description, $summary, $class, $attendees, $prio,
					  $frequency, $rec_end, $interval, $days, $weekstart,
					  $exept_dates, $alarm, $status, $url, $language, $uid) {

		$event = (object) new iCalEvent($organizer, $start, $end, $location,
										$transp, $categories, $description,
										$summary, $class, $attendees, $prio,
										$frequency, $rec_end, $interval, $days,
										$weekstart, $exept_dates, $alarm,
										$status, $url, $language, $uid);

		$this->icalevents[$this->eventid++] = $event;
		unset($event);
	} // end function

	/**
	* Adds a new ToDo Object to the ToDo Array
	*
	* @desc Adds a new ToDo Object to the ToDo Array
	* @param string $summary  Title for the event
	* @param string $description  Description
	* @param string $location  Location
	* @param int $start  Start time for the event (timestamp)
	* @param int $duration  Duration of the todo in minutes
	* @param int $end  Start time for the event (timestamp)
	* @param int $percent  The percent completion of the ToDo
	* @param int $prio  riority = 0–9
	* @param int $status  Status of the event (0 = TENTATIVE, 1 = CONFIRMED, 2 = CANCELLED)
	* @param int $class  (0 = PRIVATE | 1 = PUBLIC | 2 = CONFIDENTIAL)
	* @param array $organizer  The organizer – use array('Name', 'name@domain.com')
	* @param array $attendees  key = attendee name, value = e-mail, second value = role of the attendee [0 = CHAIR | 1 = REQ | 2 = OPT | 3 =NON] (example: array('Michi' => 'flaimo@gmx.net,1'); )
	* @param array $categories  Array with Strings (example: array('Freetime','Party'))
	* @param int $last_mod  Last modification of the to-to (timestamp)
	* @param array $alarm  Array with all the alarm information, “''” for no alarm
	* @param int $frequency  frequency: 0 = once, secoundly – yearly = 1–7
	* @param mixed $rec_end  recurrency end: ('' = forever | integer = number of times | timestring = explicit date)
	* @param int $interval  Interval for frequency (every 2,3,4 weeks…)
	* @param string $days  Array with the number of the days the event accures (example: array(0,1,5) = Sunday, Monday, Friday
	* @param string $weekstart  Startday of the Week ( 0 = Sunday – 6 = Saturday)
	* @param string $exept_dates  exeption dates: Array with timestamps of dates that should not be includes in the recurring event
	* @param string $url  optional URL for that event
	* @param string $lang  Language of the strings used in the event (iso code)
	* @param string $uid  Optional UID for the ToDo
	* @uses iCalToDo
	* @since 1.020 - 2002-12-24
	*/
	function addToDo($summary, $description, $location, $start, $duration, $end,
					 $percent, $prio, $status, $class, $organizer, $attendees,
					 $categories, $last_mod, $alarm, $frequency, $rec_end,
					 $interval, $days, $weekstart, $exept_dates, $url, $lang, $uid) {

		$todo = (object) new iCalToDo($summary, $description, $location, $start,
									  $duration, $end, $percent, $prio, $status,
									  $class, $organizer, $attendees, $categories,
									  $last_mod, $alarm, $frequency, $rec_end,
									  $interval, $days, $weekstart, $exept_dates,
									  $url, $lang, $uid);

		$this->icaltodos[$this->todoid++] = $todo;
		unset($todo);
	} // end function

	/**
	* Adds a new FreeBusy Object to the ToDo Array
	*
	* @desc Adds a new FreeBusy Object to the ToDo Array
	* @param int $start  Start time for fb (timestamp)
	* @param int $end  Start time for fb (timestamp)
	* @param int $duration  Duration of the fb in minutes
	* @param array $organizer  The organizer - use array('Name', 'name@domain.com')
	* @param array $attendees  key = attendee name, value = e-mail, second value = role of the attendee [0 = CHAIR | 1 = REQ | 2 = OPT | 3 =NON] (example: array('Michi' => 'flaimo@gmx.net,1'); )
	* @param array $fb_times  key = timestamp (starting point), value = minutes, secound value = status (0 = FREE, 1 = BUSY, 2 = BUSY-UNAVAILABLE, 3 = BUSY-TENTATIVE)
	* @param string $url  optional URL for that FreeBusy
	* @param string $uid  Optional UID for the FreeBusy
	* @uses iCalFreeBusy
	*/
	function addFreeBusy($start, $end, $duration, $organizer, $attendees,
						 $fb_times, $url, $uid) {

		$fb = (object) new iCalFreeBusy($start, $end, $duration, $organizer,
										$attendees, $fb_times, $url, $uid);

		$this->icalfbs[$this->fbid++] = $fb;
		unset($fb);
	} // end function

	/**
	* Adds a new Journal Object to the ToDo Array
	*
	* @desc Adds a new Journal Object to the ToDo Array
	* @param string $summary  Title for the event
	* @param string $description  Description
	* @param int $start  Start time for the event (timestamp)
	* @param int $created  Creation date for the event (timestamp)
	* @param int $last_mod  Last modification date for the event (timestamp)
	* @param int $status  Status of the event (0 = TENTATIVE, 1 = CONFIRMED, 2 = CANCELLED)
	* @param int $class  (0 = PRIVATE | 1 = PUBLIC | 2 = CONFIDENTIAL)
	* @param array $organizer  The organizer – use array('Name', 'name@domain.com')
	* @param array $attendees  key = attendee name, value = e-mail, second value = role of the attendee [0 = CHAIR | 1 = REQ | 2 = OPT | 3 =NON] (example: array('Michi' => 'flaimo@gmx.net,1'); )
	* @param array $categories  Array with Strings (example: array('Freetime','Party'))
	* @param int $frequency  frequency: 0 = once, secoundly – yearly = 1–7
	* @param mixed $rec_end  recurrency end: ('' = forever | integer = number of times | timestring = explicit date)
	* @param int $interval  Interval for frequency (every 2,3,4 weeks…)
	* @param string $days  Array with the number of the days the event accures (example: array(0,1,5) = Sunday, Monday, Friday
	* @param string $weekstart  Startday of the Week ( 0 = Sunday – 6 = Saturday)
	* @param string $exept_dates  exeption dates: Array with timestamps of dates that should not be includes in the recurring event
	* @param string $url  optional URL for that event
	* @param string $lang  Language of the strings used in the event (iso code)
	* @param string $uid  Optional UID for the Journal
	* @uses iCalJournal
	*/
	function addJournal($summary, $description, $start, $created, $last_mod,
						$status, $class, $organizer, $attendees, $categories,
						$frequency, $rec_end, $interval, $days, $weekstart,
						$exept_dates, $url, $lang, $uid) {

		$journal = (object) new iCalJournal($summary, $description, $start,
											$created, $last_mod, $status, $class,
											$organizer, $attendees, $categories,
											$frequency, $rec_end, $interval,
											$days, $weekstart, $exept_dates,
											$url, $lang, $uid);

		$this->icaljournals[$this->journalid++] = $journal;
		unset($journal);
	} // end function
	/**#@-*/

	/**#@+
	* @access public
	* @return mixed
	* @param int $id
	*/
	/**
	* Fetches an event from the array by the ID number
	*
	* @desc Fetches an event from the array by the ID number
	* @see addEvent()
	* @see iCalEvent::iCalEvent()
	*/
	function getEvent($id = 0) {
		if (count($this->icalevents) < 1) {
			return (string) 'No Dates found';
		} elseif (is_int($id) && array_key_exists($id, $this->icalevents)) {
			return (object) $this->icalevents[$id];
		} else {
			return (object) $this->icalevents[0];
		} // end if
	} // end function

	/**
	* Fetches an event from the array by the ID number
	*
	* @desc Fetches an event from the array by the ID number
	* @see addToDo()
	* @see iCalToDo::iCalToDo()
	* @since 1.020 - 2002-12-24
	*/
	function getToDo($id = 0) {
		if (count($this->icaltodos) < 1) {
			return (string) 'No ToDos found';
		} elseif (is_int($id) && array_key_exists($id, $this->icaltodos)) {
			return (object) $this->icaltodos[$id];
		} else {
			return (object) $this->icaltodos[0];
		} // end if
	} // end function

	/**
	* Fetches an freebusy from the array by the ID number
	*
	* @desc Fetches an freebusy from the array by the ID number
	* @see addFreeBusy()
	* @see iCalFreeBusy::iCalFreeBusy()
	* @since 1.020 - 2002-12-24
	*/
	function getFreeBusy($id = 0) {
		if (count($this->icalfbs) < 1) {
			return (string) 'No FreeBusys found';
		} elseif (is_int($id) && array_key_exists($id, $this->icalfbs)) {
			return (object) $this->icalfbs[$id];
		} else {
			return (object) $this->icalfbs[0];
		} // end if
	} // end function

	/**
	* Fetches an journal from the array by the ID number
	*
	* @desc Fetches an journal from the array by the ID number
	* @see addJournal()
	* @see iCalJournal::iCalJournal()
	* @since 1.020 - 2002-12-24
	*/
	function getJournal($id = 0) {
		if (count($this->icaljournals) < 1) {
			return (string) 'No Journals found';
		} elseif (is_int($id) && array_key_exists($id, $this->icaljournals)) {
			return (object) $this->icaljournals[$id];
		} else {
			return (object) $this->icaljournals[0];
		} // end if
	} // end function
	/**#@-*/

	/**#@+
	* @access public
	*/
	/**
	* Returns the array with the iCal Event Objects
	*
	* @desc Returns the array with the iCal Event Objects
	* @return (array) $icalevents
	* @see addEvent()
	* @see getEvent()
	*/
	function getEvents() {
		return (array) $this->icalevents;
	} // end function

	/**
	* Returns the array with the iCal ToDo Objects
	*
	* @desc Returns the array with the iCal ToDo Objects
	* @return array $icaltodos
	* @see addToDo()
	* @see getToDo()
	* @since 1.020 - 2002-12-24
	*/
	function getToDos() {
		return (array) $this->icaltodos;
	} // end function

	/**
	* Returns the array with the iCal ToDo Objects
	*
	* @desc Returns the array with the iCal ToDo Objects
	* @return array $icaltodos
	* @see addFreeBusy()
	* @see getFreeBusy()
	* @since 1.020 - 2002-12-24
	*/
	function getFreeBusys() {
		return (array) $this->icalfbs;
	} // end function

	/**
	* Returns the array with the iCal journal objects
	*
	* @desc Returns the array with the iCal journal objects
	* @return array $icaljournals
	* @see addJournal()
	* @see getJournal()
	* @since 1.020 - 2002-12-24
	*/
	function getJournals() {
		return (array) $this->icaljournals;
	} // end function
	/**#@-*/

	/**#@+
	* @access public
	* @since 1.031 - 2002-02-08
	*/
	/**
	* Returns the number of created events
	*
	* @desc Returns the number of created events
	* @return (int) $icalevents
	* @uses $icalevents
	*/
	function countEvents() {
		return (int) count($this->icalevents);
	} // end function

	/**
	* Returns the number of created ToDos
	*
	* @desc Returns the number of created ToDos
	* @return int $icaltodos
	* @uses $icaltodos
	*/
	function countToDos() {
		return (int) count($this->icaltodos);
	} // end function

	/**
	* Returns the number of created FreeBusys
	*
	* @desc Returns the number of created FreeBusys
	* @return int $icalfbs
	* @uses $icalfbs
	*/
	function countFreeBusys() {
		return (int) count($this->icalfbs);
	} // end function

	/**
	* Returns the number of created Journals
	*
	* @desc Returns the number of created Journals
	* @return int $icaljournals
	* @uses $icaljournals
	*/
	function countJournals() {
		return (int) count($this->icaljournals);
	} // end function
	/**#@-*/

	/**#@+
	* @access public
	* @return boolean $success
	* @since 1.020 - 2002-12-24
	*/
	/**
	* Deletes an event-object from the event-array
	*
	* @desc Deletes an event-object from the event-array
	* @see addEvent()
	* @since 1.011 - 2002-12-21
	*/
	function deleteEvent($id = 0) {
		if (array_key_exists($id, $this->icalevents)) {
			$this->icalevents[$id] = '';
			$this->icalevents = (array) array_filter($this->icalevents, 'strlen');
			/*
			$event_keys = (array) array_keys($this->getEvents());

			foreach ($event_keys as $key) {
				if (strlen(trim($this->icalevents[$key])) > 0) {
					$temp_array[$key] = $this->icalevents[$key];
				} // end if
			} // end foreach

			$this->icalevents = (array) $temp_array;
			unset($temp_array);
			unset($event_keys);
			*/
			return (boolean) TRUE;
		} else {
			return (boolean) FALSE;
		} // end if
	} // end function

	/**
	* Deletes an todo-object from the todo-array
	*
	* @desc Deletes an todo-object from the todo-array
	* @see addToDo()
	*/
	function deleteToDo($id = 0) {
		if (array_key_exists($id, $this->icaltodos)) {
			$this->icaltodos[$id] = '';
			$this->icaltodos = (array) array_filter($this->icaltodos, 'strlen');
			/*
			$todo_keys = (array) array_keys($this->getToDos());

			foreach ($todo_keys as $key) {
				if (strlen(trim($this->icaltodos[$key])) > 0) {
					$temp_array[$key] = $this->icaltodos[$key];
				} // end if
			} // end foreach

			$this->icaltodos = (array) $temp_array;
			unset($temp_array);
			unset($todo_keys);
			*/
			return (boolean) TRUE;
		} else {
			return (boolean) FALSE;
		} // end if
	} // end function

	/**
	* Deletes an todo-object from the todo-array
	*
	* @desc Deletes an todo-object from the todo-array
	* @see addFreeBusy()
	*/
	function deleteFreeBusy($id = 0) {
		if (array_key_exists($id, $this->icalfbs)) {
			$this->icalfbs[$id] = '';
			$this->icalfbs = (array) array_filter($this->icalfbs, 'strlen');
			/*
			$fb_keys = (array) array_keys($this->getFreeBusys());

			foreach ($fb_keys as $key) {
				if (strlen(trim($this->icalfbs[$key])) > 0) {
					$temp_array[$key] = $this->icalfbs[$key];
				} // end if
			} // end foreach

			$this->icalfbs = (array) $temp_array;
			unset($temp_array);
			unset($fb_keys);
			*/
			return (boolean) TRUE;
		} else {
			return (boolean) FALSE;
		} // end if
	} // end function

	/**
	* Deletes an journal object from the journal-array
	*
	* @desc Deletes an journal object from the journal-array
	* @see addJournal()
	*/
	function deleteJournal($id = 0) {
		if (array_key_exists($id, $this->icaljournals)) {
			$this->icaljournals[$id] = '';
			$this->icaljournals = (array) array_filter($this->icaljournals, 'strlen');
			/*
			$journal_keys = (array) array_keys($this->getJournals());

			foreach ($journal_keys as $key) {
				if (strlen(trim($this->icaljournals[$key])) > 0) {
					$temp_array[$key] = $this->icaljournals[$key];
				} // end if
			} // end foreach

			$this->icaljournals = (array) $temp_array;
			unset($temp_array);
			unset($journal_keys);
			*/
			return (boolean) TRUE;
		} else {
			return (boolean) FALSE;
		} // end if
	} // end function
	/**#@-*/

	/**
	* Returns the number of iCal-Objects which would be returned when generating the iCal file
	*
	* @desc Returns the number of iCal-Objects which would be returned when generating the iCal file
	* @return int
	* @access public
	* @uses countEvents
	* @uses countToDos()
	* @uses countFreeBusys()
	* @uses countJournals()
	* @since 1.031 - 2002-02-08
	*/
	function countiCalObjects() {
		return (int) ($this->countEvents() + $this->countToDos() + $this->countFreeBusys() + $this->countJournals());
	} // end function

	/**#@+
	* @return void
	* @access private
	*/
	/**
	* Generates the string for the alarm
	*
	* @desc Generates the string for the alarm
	* @param object $alarm
	* @param string $format  ics | xcs
	* @see generateOutput()
	* @uses generateAttendeesOutput()
	* @uses iCalAlarm::getTrigger()
	* @uses iCalAlarm::getAction()
	* @uses iCalAlarm::getLanguage()
	* @uses iCalAlarm::getDescription()
	* @uses iCalAlarm::getSummary()
	* @uses iCalAlarm::getRepeat()
	* @uses iCalAlarm::getDuration()
	* @uses iCalAlarm::getAttendees()
	* @since 1.021 - 2002-12-24
	*/
	function generateAlarmOutput($alarm, $format = 'ics') {
		$output = (string) '';
		if (is_object($alarm)) {
			if ($format === 'ics') {
				if ($alarm->getTrigger() > 0) {
					$output .= (string) "BEGIN:VALARM\r\n";
					$output .= (string) "ACTION:" . $alarm->getAction() . "\r\n";
					$output .= (string) "TRIGGER:-PT" . $alarm->getTrigger() . "M\r\n";

					if ($alarm->getAction() == 'DISPLAY' || $alarm->getAction() == 'EMAIL') {
						$output .= (string) "DESCRIPTION" . $alarm->getLanguage() . ":" . $alarm->getDescription() . "\r\n";
					} // end if

					if ($alarm->getAction() == 'DISPLAY' || $alarm->getAction() == 'EMAIL') {
						$output .= (string) "SUMMARY" . $alarm->getLanguage() . ":" . $alarm->getSummary() . "\r\n";
					} // end if

					if ($alarm->getDuration() != 0 && $alarm->getRepeat() != 0) {
						$output .= (string) "DURATION:" . $alarm->getDuration() . "\r\n";
						$output .= (string) "REPEAT:" . $alarm->getRepeat() . "\r\n";
					} // end if

					$output .= (string) $this->generateAttendeesOutput($alarm->getAttendees(), $format);
					$output .= (string) "END:VALARM\r\n";
				}
			} elseif ($format === 'xcs') {
				if ($alarm->getTrigger() > 0) {
					$output .= (string) '<valarm>';
					$output .= (string) '<action>' . $alarm->getAction() . '</action>';
					$output .= (string) '<trigger>-PT' . $alarm->getTrigger() . '</trigger>';

					if ($alarm->getAction() == 'DISPLAY' || $alarm->getAction() == 'EMAIL') {
						$output .= (string) '<description>' . $alarm->getDescription() . '</description>';
					} // end if

					if ($alarm->getAction() == 'DISPLAY' || $alarm->getAction() == 'EMAIL') {
						$output .= (string) '<summary>' . $alarm->getSummary() . '</summary>';
					} // end if

					if ($alarm->getDuration() != 0 && $alarm->getRepeat() != 0) {
						$output .= (string) '<duration>' . $alarm->getDuration() . '</duration>';
						$output .= (string) '<repeat>' . $alarm->getRepeat() . '</repeat>';
					} // end if

					$output .= (string) $this->generateAttendeesOutput($alarm->getAttendees(), $format);
					$output .= (string) '</valarm>';
				} // end if
			} // end if
		} // end if
		return (string) $output;
	} // end function

	/**
	* Generates the string for the attendees
	*
	* @desc Generates the string for the attendees
	* @param array $attendees
	* @param string $format  ics | xcs
	* @see generateOutput()
	* @uses getAttendeeRole()
	* @since 1.021 - 2002-12-24
	*/
	function generateAttendeesOutput($attendees, $format = 'ics') {
		$output = (string) '';
		if ($this->method == 0 && count($attendees) > 0) {
			if ($format === 'ics') {
				if (count($attendees) > 0) {
					foreach ($attendees as $name => $data) {
						$values = (array) explode(',',$data);
						$email = (string) $values[0];
						if (strlen(trim($email)) > 5) {
							$role = (int) $values[1];
							$output .= (string) "ATTENDEE;ROLE=" . $this->getAttendeeRole($role) . ";CN=" . $name . ":MAILTO:" . $email . "\r\n";
						} // end if
					} // end foreach
				} // end if
			} elseif ($format === 'xcs') {
				if (count($attendees) > 0) {
					foreach ($attendees as $name => $data) {
						$values = (array) explode(',',$data);
						$email = (string) $values[0];
						if (strlen(trim($email)) > 5) {
							$role = (int) $values[1];
							$output .= (string) '<attendee cn="' . $name . '" role="' . $this->getAttendeeRole($role) . '">MAILTO:' . $email . '</attendee>';
						} // end if
					} // end foreach
				} // end if
			} // end if
		} // end if
		return (string) $output;
	} // end function

	function isEmpty(&$variable) {
		return (boolean) ((strlen(trim($variable)) > 0) ? FALSE : TRUE);
    }
	
	/**
	* Generates the string to be written in the file later on
	*
	* you can choose between ics, xcs or rdf format.
	* only ics has been tested; the other two are not, or are not
	* finished coded yet
	*
	* @desc Generates the string to be written in the file later on
	* @param string $format  ics | xcs | rdf
	* @see getOutput()
	* @see writeFile()
	* @uses iCalEvent
	* @uses iCalToDo
	* @uses iCalFreeBusy
	* @uses iCalJournal
	* @uses quotedPrintableEncode()
	* @uses getClassName()
	* @uses getStatusName()
	* @uses getFrequencyName()
	* @since 1.001 - 2002-10-10
	*/
	function generateOutput($format = 'ics') {
        $this->output_format = (string) $format;
		if ($this->output_format == 'ics') {
			$this->output  = (string) "BEGIN:VCALENDAR\r\n";
			$this->output .= (string) "PRODID:" . $this->prodid . "\r\n";
			$this->output .= (string) "VERSION:2.0\r\n";
			$this->output .= (string) "METHOD:" . $this->getMethod() . "\r\n";
			$eventkeys = (array) array_keys($this->icalevents);
			foreach ($eventkeys as $id) {
				$this->output .= (string) "BEGIN:VEVENT\r\n";
				$event =& $this->icalevents[$id];
				$this->output .= (string) $this->generateAttendeesOutput($event->getAttendees(), $format);
				if (!$this->isEmpty($event->getOrganizerMail())) {
					$name = '';
					if (!$this->isEmpty($event->getOrganizerName())) {
						$name = (string) ";CN=" . $event->getOrganizerName();
					} // end if
					$this->output .= (string) "ORGANIZER" . $name . ":MAILTO:" . $event->getOrganizerMail() . "\r\n";
				} // end if
				$this->output .= (string) "DTSTART:" . $event->getStartDate() . "\r\n";
				if (strlen(trim($event->getEndDate())) > 0) {
					$this->output .= (string) "DTEND:" . $event->getEndDate() . "\r\n";
				}

				if ($event->getFrequency() > 0) {
					$this->output .= (string) "RRULE:FREQ=" . $this->getFrequencyName($event->getFrequency());
					if (is_string($event->getRecEnd())) {
						$this->output .= (string) ";UNTIL=" . $event->getRecEnd();
					} elseif (is_int($event->getRecEnd())) {
						$this->output .= (string) ";COUNT=" . $event->getRecEnd();
					} // end if
					$this->output .= (string) ";INTERVAL=" . $event->getInterval() . ";BYDAY=" . $event->getDays() . ";WKST=" . $event->getWeekStart() . "\r\n";
				} // end if
				if (!$this->isEmpty($event->getExeptDates())) {
					$this->output .= (string) "EXDATE:" . $event->getExeptDates() . "\r\n";
				} // end if
				if (!$this->isEmpty($event->getLocation())) {
					$this->output .= (string) "LOCATION" . $event->getLanguage() . ";ENCODING=QUOTED-PRINTABLE:" . $this->quotedPrintableEncode($event->getLocation()) . "\r\n";
				} // end if
				$this->output .= (string) "TRANSP:" . $event->getTransp() . "\r\n";
				$this->output .= (string) "SEQUENCE:" . $event->getSequence() . "\r\n";
				$this->output .= (string) "UID:" . $event->getUID() . "\r\n";
				$this->output .= (string) "DTSTAMP:" . $this->ical_timestamp . "\r\n";
				if (!$this->isEmpty($event->getCategories())) {
					$this->output .= (string) "CATEGORIES" . $event->getLanguage() . ";ENCODING=QUOTED-PRINTABLE:" . $this->quotedPrintableEncode($event->getCategories()) . "\r\n";
				} // end if
				if (!$this->isEmpty($event->getDescription())) {
					$this->output .= (string) "DESCRIPTION" . $event->getLanguage() . ";ENCODING=QUOTED-PRINTABLE:" . str_replace('\n', '=0D=0A=',str_replace('\r', '=0D=0A=', $this->quotedPrintableEncode($event->getDescription()))) . "\r\n";
				} // end if
				$this->output .= (string) "SUMMARY" . $event->getLanguage() . ";ENCODING=QUOTED-PRINTABLE:" . $this->quotedPrintableEncode($event->getSummary()) . "\r\n";
				$this->output .= (string) "PRIORITY:" . $event->getPriority() . "\r\n";
				$this->output .= (string) "CLASS:" . $this->getClassName($event->getClass()) . "\r\n";
				if (!$this->isEmpty($event->getURL())) {
					$this->output .= (string) "URL:" . $event->getURL() . "\r\n";
				} // end if
				if (!$this->isEmpty($event->getStatus())) {
					$this->output .= (string) "STATUS:" . $this->getStatusName($event->getStatus()) . "\r\n";
				} // end if
				$this->output .= (string) $this->generateAlarmOutput($event->getAlarm(), $format);
				$this->output .= (string) "END:VEVENT\r\n";
			} // end foreach
			$todokeys = (array) array_keys($this->icaltodos);
			foreach ($todokeys as $id) {
				$this->output .= (string) "BEGIN:VTODO\r\n";
				$todo =& $this->icaltodos[$id];
				$this->output .= (string) $this->generateAttendeesOutput($todo->getAttendees(), $format);
				if (!$this->isEmpty($todo->getOrganizerMail())) {
					$name = '';
					if (!$this->isEmpty($todo->getOrganizerName())) {
						$name = (string) ";CN=" . $todo->getOrganizerName();
					} // end if
					$this->output .= (string) "ORGANIZER" . $name . ":MAILTO:" . $todo->getOrganizerMail() . "\r\n";
				} // end if
				$this->output .= (string) "SEQUENCE:" . $todo->getSequence() . "\r\n";
				$this->output .= (string) "UID:" . $todo->getUID() . "\r\n";
				$this->output .= (string) "DTSTAMP:" . $this->ical_timestamp . "\r\n";
				if (!$this->isEmpty($todo->getCategories())) {
					$this->output .= (string) "CATEGORIES" . $todo->getLanguage() . ";ENCODING=QUOTED-PRINTABLE:" . $this->quotedPrintableEncode($todo->getCategories()) . "\r\n";
				} // end if
				if (!$this->isEmpty($todo->getDescription())) {
					$this->output .= (string) "DESCRIPTION" . $todo->getLanguage() . ";ENCODING=QUOTED-PRINTABLE:" . str_replace('\n', '=0D=0A=',str_replace('\r', '=0D=0A=', $this->quotedPrintableEncode($todo->getDescription()))) . "\r\n";
				} // end if
				$this->output .= (string) "SUMMARY" . $todo->getLanguage() . ";ENCODING=QUOTED-PRINTABLE:" . $this->quotedPrintableEncode($todo->getSummary()) . "\r\n";
				$this->output .= (string) "PRIORITY:" . $todo->getPriority() . "\r\n";
				$this->output .= (string) "CLASS:" . $this->getClassName($todo->getClass()) . "\r\n";
				if (!$this->isEmpty($todo->getLocation())) {
					$this->output .= (string) "LOCATION" . $todo->getLanguage() . ";ENCODING=QUOTED-PRINTABLE:" . $this->quotedPrintableEncode($todo->getLocation()) . "\r\n";
				} // end if
				if (!$this->isEmpty($todo->getURL())) {
					$this->output .= (string) "URL:" . $todo->getURL() . "\r\n";
				} // end if
				if (!$this->isEmpty($todo->getStatus())) {
					$this->output .= (string) "STATUS:" . $this->getStatusName($todo->getStatus()) . "\r\n";
				} // end if
				if (!$this->isEmpty($todo->getPercent()) && $todo->getPercent() > 0) {
					$this->output .= (string) "PERCENT-COMPLETE:" . $todo->getPercent() . "\r\n";
				} // end if
				if (!$this->isEmpty($todo->getDuration()) && $todo->getDuration() > 0) {
					$this->output .= (string) "DURATION:PT" . $todo->getDuration() . "M\r\n";
				} // end if
				if (!$this->isEmpty($todo->getLastMod())) {
					$this->output .= (string) "LAST-MODIFIED:" . $todo->getLastMod() . "\r\n";
				} // end if
				if (!$this->isEmpty($todo->getStartDate())) {
					$this->output .= (string) "DTSTART:" . $todo->getStartDate() . "\r\n";
				} // end if
				if (!$this->isEmpty($todo->getCompleted())) {
					$this->output .= (string) "COMPLETED:" . $todo->getCompleted() . "\r\n";
				} // end if
				if ($todo->getFrequency() != 'ONCE') {
					$this->output .= (string) "RRULE:FREQ=" . $todo->getFrequency();
					if (is_string($todo->getRecEnd())) {
						$this->output .= (string) ";UNTIL=" . $todo->getRecEnd();
					} elseif (is_int($todo->getRecEnd())) {
						$this->output .= (string) ";COUNT=" . $todo->getRecEnd();
					} // end if
					$this->output .= (string) ";INTERVAL=" . $todo->getInterval() . ";BYDAY=" . $todo->getDays() . ";WKST=" . $todo->getWeekStart() . "\r\n";
				} // end if
				if (!$this->isEmpty($todo->getExeptDates())) {
					$this->output .= (string) "EXDATE:" . $todo->getExeptDates() . "\r\n";
				} // end if
				$this->output .= (string) $this->generateAlarmOutput($todo->getAlarm(), $format);
				$this->output .= (string) "END:VTODO\r\n";
			} // end foreach
			$journalkeys = (array) array_keys($this->icaljournals);
			foreach ($journalkeys as $id) {
				$this->output .= (string) "BEGIN:VJOURNAL\r\n";
				$journal =& $this->icaljournals[$id];
				$this->output .= (string) $this->generateAttendeesOutput($journal->getAttendees(), $format);
				if (!$this->isEmpty($journal->getOrganizerMail())) {
					$name = '';
					if (!$this->isEmpty($journal->getOrganizerName())) {
						$name = (string) ";CN=" . $journal->getOrganizerName();
					} // end if
					$this->output .= (string) "ORGANIZER" . $name . ":MAILTO:" . $journal->getOrganizerMail() . "\r\n";
				} // end if
				$this->output .= (string) "SEQUENCE:" . $journal->getSequence() . "\r\n";
				$this->output .= (string) "UID:" . $journal->getUID() . "\r\n";
				$this->output .= (string) "DTSTAMP:" . $this->ical_timestamp . "\r\n";
				if (!$this->isEmpty($journal->getCategories())) {
					$this->output .= (string) "CATEGORIES" . $journal->getLanguage() . ";ENCODING=QUOTED-PRINTABLE:" . $this->quotedPrintableEncode($journal->getCategories()) . "\r\n";
				} // end if
				if (!$this->isEmpty($journal->getDescription())) {
					$this->output .= (string) "DESCRIPTION" . $journal->getLanguage() . ";ENCODING=QUOTED-PRINTABLE:" . str_replace('\n', '=0D=0A=',str_replace('\r', '=0D=0A=', $this->quotedPrintableEncode($journal->getDescription()))) . "\r\n";
				} // end if
				$this->output .= (string) "SUMMARY" . $journal->getLanguage() . ";ENCODING=QUOTED-PRINTABLE:" . $this->quotedPrintableEncode($journal->getSummary()) . "\r\n";
				$this->output .= (string) "CLASS:" . $this->getClassName($journal->getClass()) . "\r\n";
				if (!$this->isEmpty($journal->getURL())) {
					$this->output .= (string) "URL:" . $journal->getURL() . "\r\n";
				} // end if
				if (!$this->isEmpty($journal->getStatus())) {
					$this->output .= (string) "STATUS:" . $this->getStatusName($journal->getStatus()) . "\r\n";
				} // end if
				if (!$this->isEmpty($journal->getLastMod())) {
					$this->output .= (string) "LAST-MODIFIED:" . $journal->getLastMod() . "\r\n";
				} // end if
				if (!$this->isEmpty($journal->getStartDate())) {
					$this->output .= (string) "DTSTART:" . $journal->getStartDate() . "\r\n";
				} // end if
				if (!$this->isEmpty($journal->getCreated())) {
					$this->output .= (string) "CREATED:" . $journal->getCreated() . "\r\n";
				} // end if
				if ($journal->getFrequency() > 0) {
					$this->output .= (string) "RRULE:FREQ=" . $this->getFrequencyName($journal->getFrequency());
					if (is_string($journal->getRecEnd())) {
						$this->output .= (string) ";UNTIL=" . $journal->getRecEnd();
					} elseif (is_int($journal->getRecEnd())) {
						$this->output .= (string) ";COUNT=" . $journal->getRecEnd();
					} // end if
					$this->output .= (string) ";INTERVAL=" . $journal->getInterval() . ";BYDAY=" . $journal->getDays() . ";WKST=" . $journal->getWeekStart() . "\r\n";
				} // end if
				if (!$this->isEmpty($journal->getExeptDates())) {
					$this->output .= (string) "EXDATE:" . $journal->getExeptDates() . "\r\n";
				} // end if
				$this->output .= (string) "END:VJOURNAL\r\n";
			} // end foreach
			$fbkeys = (array) array_keys($this->icalfbs);
			foreach ($fbkeys as $id) {
				$this->output .= (string) "BEGIN:VFREEBUSY\r\n";
				$fb =& $this->icalfbs[$id];
				$this->output .= (string) $this->generateAttendeesOutput($fb->getAttendees(), $format);
				if (!$this->isEmpty($fb->getOrganizerMail())) {
					$name = '';
					if (!$this->isEmpty($fb->getOrganizerName())) {
						$name = (string) ";CN=" . $fb->getOrganizerName();
					} // end if
					$this->output .= (string) "ORGANIZER" . $name . ":MAILTO:" . $fb->getOrganizerMail() . "\r\n";
				} // end if
				$this->output .= (string) "UID:" . $fb->getUID() . "\r\n";
				$this->output .= (string) "DTSTAMP:" . $this->ical_timestamp . "\r\n";
				if (!$this->isEmpty($fb->getURL())) {
					$this->output .= (string) "URL:" . $fb->getURL() . "\r\n";
				} // end if
				if (!$this->isEmpty($fb->getDuration()) && $fb->getDuration() > 0) {
					$this->output .= (string) "DURATION:PT" . $fb->getDuration() . "M\r\n";
				} // end if
				if (!$this->isEmpty($fb->getStartDate())) {
					$this->output .= (string) "DTSTART:" . $fb->getStartDate() . "\r\n";
				} // end if
				if (!$this->isEmpty($fb->getEndDate())) {
					$this->output .= (string) "DTEND:" . $fb->getEndDate() . "\r\n";
				} // end if
				if (count($fb->getFBTimes()) > 0) {
					foreach ($fb->getFBTimes() as $timestamp => $data) {
						$values = (array) explode(',',$data);
						$this->output .= (string) "FREEBUSY;FBTYPE=" . $values[1] . ":" . $timestamp . "/" . $values[0] . "\r\n";
					} // end foreach
					unset($values);
				} // end if
				$this->output .= (string) "END:VFREEBUSY\r\n";
			} // end foreach
			$this->output .= (string) "END:VCALENDAR\r\n";
		} // end if ics
		elseif ($this->output_format == 'xcs') {
			$this->output  = (string) '<?xml version="1.0" encoding="UTF-8"?>';
			//$this->output  = (string) '<!DOCTYPE iCalendar PUBLIC "-//IETF//DTD iCalendar//EN" "http://www.ietf.org/internet-drafts/draft-dawson-ical-xml-dtd-02.txt">';
			$this->output .= (string) '<iCalendar>';
			if (count($this->icalevents) > 0) {
				$this->output .= (string) '<vCalendar version="2.0" prodid="' . $this->prodid . '" method="' . $this->getMethod() . '">';
				$eventkeys = (array) array_keys($this->icalevents);
				foreach ($eventkeys as $id) {
					$this->output .= (string) '<vevent>';
					$event =& $this->icalevents[$id];
					$this->output .= (string) $this->generateAttendeesOutput($event->getAttendees(), $format);
					if (!$this->isEmpty($event->getOrganizerMail())) {
						$name = '';
						if (!$this->isEmpty($event->getOrganizerName())) {
							$name = (string) ' cn="' . $event->getOrganizerName() . '"';
						} // end if
						$this->output .= (string) '<organizer' . $name . '>MAILTO:' . $event->getOrganizerMail() . '</organizer>';
					} // end if
					$this->output .= (string) '<dtstart>' . $event->getStartDate() . '</dtstart>';

					if (strlen(trim($event->getEndDate())) > 0) {
						$this->output .= (string) '<dtend>' . $event->getEndDate() . '</dtend>';
					} // end if
					if ($event->getFrequency() > 0) {
						$this->output .= (string) '<rrule>FREQ=' . $this->getFrequencyName($event->getFrequency());
						if (is_string($event->getRecEnd())) {
							$this->output .= (string) ";UNTIL=" . $event->getRecEnd();
						} elseif (is_int($event->getRecEnd())) {
							$this->output .= (string) ";COUNT=" . $event->getRecEnd();
						} // end if
						$this->output .= (string) ";INTERVAL=" . $event->getInterval() . ";BYDAY=" . $event->getDays() . ";WKST=" . $event->getWeekStart() . '</rrule>';
					} // end if
					if (!$this->isEmpty($event->getExeptDates())) {
						$this->output .= (string) '<exdate>' . $event->getExeptDates() . '</exdate>';
					} // end if
					$this->output .= (string) '<location>' . $event->getLocation() . '</location>';
					$this->output .= (string) '<transp>' . $event->getTransp() . '</transp>';
					$this->output .= (string) '<sequence>' . $event->getSequence() . '</sequence>';
					$this->output .= (string) '<uid>' . $event->getUID() . '</uid>';
					$this->output .= (string) '<dtstamp>' . $this->ical_timestamp . '</dtstamp>';
					if (!$this->isEmpty($event->getCategories())) {
						$this->output .= (string) '<categories>';
						foreach ($event->getCategoriesArray() as $item) {
							$this->output .= (string) '<item>' . $item . '</item>';
						} // end foreach
						$this->output .= (string) '</categories>';
					} // end if
					if (!$this->isEmpty($event->getDescription())) {
						$this->output .= (string) '<description>' . $event->getDescription() . '</description>';
					} // end if
					$this->output .= (string) '<summary>' . $event->getSummary() . '</summary>';
					$this->output .= (string) '<priority>' . $event->getPriority() . '</priority>';
					$this->output .= (string) '<class>' . $this->getClassName($event->getClass()) . '</class>';
					if (!$this->isEmpty($event->getURL())) {
						$this->output .= (string) '<url>' . $event->getURL() . '</url>';
					} // end if
					if (!$this->isEmpty($event->getStatus())) {
						$this->output .= (string) '<status>' . $this->getStatusName($event->getStatus()) . '</status>';
					} // end if
					$this->output .= (string) $this->generateAlarmOutput($event->getAlarm(), $format);
					$this->output .= (string) '</vevent>';
				} // end foreach event
				$this->output .= (string) '</vCalendar>';
			} // end if count($this->icalevents) > 0
			if (count($this->icaltodos) > 0) {
				$this->output .= (string) '<vCalendar version="2.0" prodid="' . $this->prodid . '" method="' . $this->getMethod() . '">';
				$todokeys = (array) array_keys($this->icaltodos);
				foreach ($todokeys as $id) {
					$this->output .= (string) '<vtodo>';
					$todo =& $this->icaltodos[$id];
					$this->output .= (string) $this->generateAttendeesOutput($todo->getAttendees(), $format);
					if (!$this->isEmpty($todo->getOrganizerMail())) {
						$name = '';
						if (!$this->isEmpty($todo->getOrganizerName())) {
							$name = (string) ' cn="' . $todo->getOrganizerName() . '"';
						} // end if
						$this->output .= (string) '<organizer' . $name . '>MAILTO:' . $todo->getOrganizerMail() . '</organizer>';
					} // end if
					if (!$this->isEmpty($todo->getStartDate())) {
						$this->output .= (string) '<dtstart>' . $todo->getStartDate() . '</dtstart>';
					} // end if
					if (!$this->isEmpty($todo->getCompleted())) {
						$this->output .= (string) '<completed>' . $todo->getCompleted() . '</completed>';
					} // end if
					if (!$this->isEmpty($todo->getDuration()) && $todo->getDuration() > 0) {
						$this->output .= (string) '<duration>PT' . $todo->getDuration() . 'M</duration>';
					} // end if
					if (!$this->isEmpty($todo->getLocation())) {
						$this->output .= (string) '<location>' . $todo->getLocation() . '</location>';
					} // end if
					$this->output .= (string) '<sequence>' . $todo->getSequence() . '</sequence>';
					$this->output .= (string) '<uid>' . $todo->getUID() . '</uid>';
					$this->output .= (string) '<dtstamp>' . $this->ical_timestamp . '</dtstamp>';
					if (!$this->isEmpty($todo->getCategories())) {
						$this->output .= (string) '<categories>';
						foreach ($todo->getCategoriesArray() as $item) {
							$this->output .= (string) '<item>' . $item . '</item>';
						} // end foreach
						$this->output .= (string) '</categories>';
					} // end if
					if (!$this->isEmpty($todo->getDescription())) {
						$this->output .= (string) '<description>' . $todo->getDescription() . '</description>';
					} // end if
					$this->output .= (string) '<summary>' . $todo->getSummary() . '</summary>';
					$this->output .= (string) '<priority>' . $todo->getPriority() . '</priority>';
					$this->output .= (string) '<class>' . $this->getClassName($todo->getClass()) . '</class>';
					if (!$this->isEmpty($todo->getURL())) {
						$this->output .= (string) '<url>' . $todo->getURL() . '</url>';
					} // end if
					if (!$this->isEmpty($todo->getStatus())) {
						$this->output .= (string) '<status>' . $this->getStatusName($todo->getStatus()) . '</status>';
					} // end if
					if (!$this->isEmpty($todo->getPercent()) && $todo->getPercent() > 0) {
						$this->output .= (string) '<percent>' . $todo->getPercent() . '</percent>';
					} // end if
					if (!$this->isEmpty($todo->getLastMod())) {
						$this->output .= (string) '<last-modified>' . $todo->getLastMod() . '</last-modified>';
					} // end if
					if ($todo->getFrequency() > 0) {
						$this->output .= (string) '<rrule>FREQ=' . $this->getFrequencyName($todo->getFrequency());
						if (is_string($todo->getRecEnd())) {
							$this->output .= (string) ";UNTIL=" . $todo->getRecEnd();
						} elseif (is_int($todo->getRecEnd())) {
							$this->output .= (string) ";COUNT=" . $todo->getRecEnd();
						} // end if
						$this->output .= (string) ";INTERVAL=" . $todo->getInterval() . ";BYDAY=" . $todo->getDays() . ";WKST=" . $todo->getWeekStart() . '</rrule>';
					} // end if
					if (!$this->isEmpty($todo->getExeptDates())) {
						$this->output .= (string) '<exdate>' . $todo->getExeptDates() . '</exdate>';
					} // end if
					$this->output .= (string) $this->generateAlarmOutput($todo->getAlarm(), $format);
					$this->output .= (string) '</vtodo>';
				} // end foreach event
				$this->output .= (string) '</vCalendar>';
			} // end if count($this->icaljournals) > 0
			if (count($this->icaljournals) > 0) {
				$this->output .= (string) '<vCalendar version="2.0" prodid="' . $this->prodid . '" method="' . $this->getMethod() . '">';
				$journalkeys = (array) array_keys($this->icaljournals);
				foreach ($journalkeys as $id) {
					$this->output .= (string) '<vjournal>';
					$journal =& $this->icaljournals[$id];
					$this->output .= (string) $this->generateAttendeesOutput($journal->getAttendees(), $format);
					if (!$this->isEmpty($journal->getOrganizerMail())) {
						$name = '';
						if (!$this->isEmpty($journal->getOrganizerName())) {
							$name = (string) ' cn="' . $journal->getOrganizerName() . '"';
						} // end if
						$this->output .= (string) '<organizer' . $name . '>MAILTO:' . $journal->getOrganizerMail() . '</organizer>';
					} // end if
					if (!$this->isEmpty($journal->getStartDate())) {
						$this->output .= (string) '<dtstart>' . $journal->getStartDate() . '</dtstart>';
					} // end if
					if (!$this->isEmpty($journal->getCreated()) && $journal->getCreated() > 0) {
						$this->output .= (string) '<created>' . $journal->getCreated() . '</created>';
					} // end if
					if (!$this->isEmpty($journal->getLastMod()) && $journal->getLastMod() > 0) {
						$this->output .= (string) '<last-modified>' . $journal->getLastMod() . '</last-modified>';
					} // end if
					$this->output .= (string) '<sequence>' . $journal->getSequence() . '</sequence>';
					$this->output .= (string) '<uid>' . $journal->getUID() . '</uid>';
					$this->output .= (string) '<dtstamp>' . $this->ical_timestamp . '</dtstamp>';
					if (!$this->isEmpty($journal->getCategories())) {
						$this->output .= (string) '<categories>';
						foreach ($journal->getCategoriesArray() as $item) {
							$this->output .= (string) '<item>' . $item . '</item>';
						} // end foreach
						$this->output .= (string) '</categories>';
					} // end if
					if (!$this->isEmpty($journal->getDescription())) {
						$this->output .= (string) '<description>' . $journal->getDescription() . '</description>';
					} // end if
					$this->output .= (string) '<summary>' . $journal->getSummary() . '</summary>';
					$this->output .= (string) '<class>' . $this->getClassName($journal->getClass()) . '</class>';
					if (!$this->isEmpty($journal->getURL())) {
						$this->output .= (string) '<url>' . $journal->getURL() . '</url>';
					} // end if
					if (!$this->isEmpty($journal->getStatus())) {
						$this->output .= (string) '<status>' . $this->getStatusName($journal->getStatus()) . '</status>';
					} // end if
					if ($journal->getFrequency() != 'ONCE') {
						$this->output .= (string) '<rrule>FREQ=' . $journal->getFrequency();
						if (is_string($journal->getRecEnd())) {
							$this->output .= (string) ";UNTIL=" . $journal->getRecEnd();
						} elseif (is_int($journal->getRecEnd())) {
							$this->output .= (string) ";COUNT=" . $journal->getRecEnd();
						} // end if
						$this->output .= (string) ";INTERVAL=" . $journal->getInterval() . ";BYDAY=" . $journal->getDays() . ";WKST=" . $journal->getWeekStart() . '</rrule>';
					} // end if
					if (!$this->isEmpty($journal->getExeptDates())) {
						$this->output .= (string) '<exdate>' . $journal->getExeptDates() . '</exdate>';
					} // end if
					$this->output .= (string) '</vjournal>';
				} // end foreach event
				$this->output .= (string) '</vCalendar>';
			} // end if count($this->icaltodos) > 0
			if (count($this->icalfbs) > 0) {
				$this->output .= (string) '<vCalendar version="2.0" prodid="' . $this->prodid . '" method="' . $this->getMethod() . '">';
				$fbkeys = (array) array_keys($this->icalfbs);
				foreach ($fbkeys as $id) {
					$this->output .= (string) '<vfreebusy>';
					$fb =& $this->icalfbs[$id];
					$this->output .= (string) $this->generateAttendeesOutput($fb->getAttendees(), $format);
					if (!$this->isEmpty($fb->getOrganizerMail())) {
						$name = '';
						if (!$this->isEmpty($fb->getOrganizerName())) {
							$name = (string) ' cn="' . $fb->getOrganizerName() . '"';
						} // end if
						$this->output .= (string) '<organizer' . $name . '>MAILTO:' . $fb->getOrganizerMail() . '</organizer>';
					} // end if
					if (!$this->isEmpty($fb->getStartDate())) {
						$this->output .= (string) '<dtstart>' . $fb->getStartDate() . '</dtstart>';
					} // end if
					if (!$this->isEmpty($fb->getEndDate())) {
						$this->output .= (string) '<dtend>' . $fb->getEndDate() . '</dtend>';
					} // end if
					if (!$this->isEmpty($fb->getDuration()) && $fb->getDuration() > 0) {
						$this->output .= (string) '<duration>PT' . $fb->getDuration() . 'M</duration>';
					} // end if
					$this->output .= (string) '<uid>' . $fb->getUID() . '</uid>';
					$this->output .= (string) '<dtstamp>' . $this->ical_timestamp . '</dtstamp>';
					if (!$this->isEmpty($fb->getURL())) {
						$this->output .= (string) '<url>' . $fb->getURL() . '</url>';
					} // end if
					if (count($fb->getFBTimes()) > 0) {
						foreach ($fb->getFBTimes() as $timestamp => $data) {
							$values = (array) explode(',',$data);
							$this->output .= (string) '<freebusy fbtype="' . $values[1] . '">' . $timestamp . '/' . $values[0] . '</freebusy>';
						} // end foreach
						unset($values);
					} // end if
					$this->output .= (string) '</vfreebusy>';
				} // end foreach event
				$this->output .= (string) '</vCalendar>';
			} // end if count($this->icaltodos) > 0
			$this->output .= (string) '</iCalendar>';
		} // end if xcs
		elseif ($this->output_format == 'rdf') {
			$this->output  = (string) '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
			$this->output .= (string) '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns="http://www.w3.org/2000/10/swap/pim/ical#" xmlns:i="http://www.w3.org/2000/10/swap/pim/ical#">';
			$this->output .= (string) '<Vcalendar rdf:about="">';
			$this->output .= (string) '<version>2.0</version>';
			$this->output .= (string) '<prodid>' . $this->prodid . '</prodid>';
			$this->output .= (string) '</Vcalendar>';
			$eventkeys = (array) array_keys($this->icalevents);
			foreach ($eventkeys as $id) {
				$event =& $this->icalevents[$id];
				$this->output .= (string) '<Vevent>';
				$this->output .= (string) '<uid>' . $event->getUID() . '</uid>';
				$this->output .= (string) '<summary>' . $event->getSummary() . '</summary>';
				if (!$this->isEmpty($event->getDescription())) {
					$this->output .= (string) '<description>' . $event->getDescription() . '</description>';
				} // end if
				if (!$this->isEmpty($event->getCategories())) {
					$this->output .= (string) '<categories>' . $event->getCategories() . '</categories>';
				} // end if
				$this->output .= (string) '<status/>';
				$this->output .= (string) '<class resource="http://www.w3.org/2000/10/swap/pim/ical#private"/>';
				$this->output .= (string) '<dtstart parseType="Resource">';
				$this->output .= (string) '<value>' . $event->getStartDate() . '</value>';
				$this->output .= (string) '</dtstart>';
				$this->output .= (string) '<dtstamp>' . $this->ical_timestamp . '</dtstamp>';
				$this->output .= (string) '<due/>';
				$this->output .= (string) '</Vevent>';
			} // end foreach event
			$this->output .= (string) '</rdf:RDF>';
		} // end if rdf
		if (isset($event)) {
			unset($event);
		}
	} // end function
	/**#@-*/

	/**#@+
	* @access public
	*/
	/**
	* Loads the string into the variable if it hasn't been set before
	*
	* @desc Loads the string into the variable
	* @param string $format  ics | xcs | rdf
	* @return string $output
	* @see generateOutput()
	* @see writeFile()
	*/
	function getOutput($format = 'ics') {
		if (!isset($this->output) || $this->output_format != $format) {
			$this->generateOutput($format);
		} // end if
		return (string) $this->output;
	} // end function

	/**
	* Sends the right header information and outputs the generated content to
	* the browser
	*
	* @desc Sends the right header information
	* @param string $format  ics | xcs | rdf (only Events)
	* @return void
	* @uses getOutput()
	* @since 1.011 - 2002-12-22
	*/
	function outputFile($format = 'ics') {
		if ($format == 'ics') {
			header('Content-Type: text/Calendar');
			header('Content-Disposition: attachment; filename=iCalendar_dates_' . date('Y-m-d_H-m-s') . '.ics');
			echo $this->getOutput('ics');
		} elseif ($format == 'xcs') {
			header('Content-Type: text/Calendar');
			header('Content-Disposition: attachment; filename=iCalendar_dates_' . date('Y-m-d_H-m-s') . '.xcs');
			echo $this->getOutput('xcs');
		} elseif ($format == 'rdf') {
			header('Content-Type: text/xml');
			header('Content-Disposition: attachment; filename=iCalendar_dates_' . date('Y-m-d_H-m-s') . '.rdf');
			echo $this->getOutput('rdf');
		} // end if
	} // end function


	/**
	* Writes the string into the file and saves it to the download directory
	*
	* @desc Writes the string into the file and saves it to the download directory
	* @return void
	* @see getOutput()
	* @uses checkDownloadDir()
	* @uses generateOutput()
	* @uses deleteOldFiles()
	*/
	function writeFile() {
		if ($this->checkDownloadDir() == FALSE) {
			die('error creating download directory');
		} // end if
		if (!isset($this->output)) {
			$this->generateOutput();
		} // end if
		$handle = fopen($this->download_dir . '/' . $this->events_filename, 'w');
		fputs($handle, $this->output);
		fclose($handle);
		//$this->deleteOldFiles(300);
		if (isset($handle)) {
			unset($handle);
		}
	} // end function

	/**
	* Returns the full path to the saved file where it can be downloaded.
	*
	* Can be used for “header(Location:…”
	*
	* @desc Returns the full path to the saved file where it can be downloaded.
	* @return string  Full http path
	*/
	function getFilePath() {
		$path_parts = pathinfo($_SERVER['SCRIPT_NAME']);
		$port = (string) (($_SERVER['SERVER_PORT'] != 80) ? ':' . $_SERVER['SERVER_PORT'] : '' );
		return (string) 'http://' . $_SERVER['SERVER_NAME'] . $port . $path_parts["dirname"] . '/' . $this->download_dir . '/' . $this->events_filename;
	} // end function
	/**#@-*/

	/**
	* Writes the string into the file and saves it to the download directory
	*
	* @desc Writes the string into the file and saves it to the download directory
	* @param int $time  Minimum age of the files (in seconds) before file get deleted
	* @return void
	* @see writeFile()
	* @access private
	*/
	function deleteOldFiles($time = 300) {
		if ($this->checkDownloadDir() == FALSE) {
			die('error creating download directory');
		} // end if
		if (!is_int($time) || $time < 1) {
			$time = (int) 300;
		} // end if
		$handle = opendir($this->download_dir);
		while ($file = readdir($handle)) {
			if (!eregi("^\.{1,2}$",$file) && !is_dir($this->download_dir . '/' . $file) && eregi("\.ics",$file) && ((time() - filemtime($this->download_dir . '/' . $file)) > $time)) {
				unlink($this->download_dir . '/' . $file);
			} // end if
		} // end while
		closedir($handle);
		if (isset($handle)) {
			unset($handle);
		}
		if (isset($file)) {
			unset($file);
		}
	} // end function
} // end class iCal
?>
