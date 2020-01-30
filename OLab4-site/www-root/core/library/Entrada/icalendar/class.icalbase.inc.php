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
// $Id: class.icalbase.inc.php 1 2008-07-11 20:11:41Z simpson $

/**
* @package iCalendar Everything to generate simple iCal files
*/
/**
* Base Class for the different Modules
*
* Last Change: 2003-03-29
* Tested with WAMP (XP-SP1/1.3.24/4.0.4/4.3.0)
*
* @desc Container for a single event
* @access private
* @author Michael Wimmer <flaimo 'at' gmx 'dot' net>
* @copyright Michael Wimmer
* @link http://www.flaimo.com/
* @package iCalendar
*/
class iCalBase {

	/*-------------------*/
	/* V A R I A B L E S */
	/*-------------------*/

	/**#@+
	* @access private
	* @var string
	*/
	/**
	* Detailed information for the module
	*
	* @desc Detailed information for the module
	*/
	var $description;

	/**
	* iso code language (en, de,…)
	*
	* @desc iso code language (en, de,…)
	*/
	var $lang;

	/**
	* If not empty, contains a Link for that module
	*
	* @desc If not empty, contains a Link for that module
	*/
	var $url;

	/**
	* Headline for the module (mostly displayed in your cal programm)
	*
	* @desc Headline for the module
	*/
	var $summary;

	/**
	* String of days for the recurring module (example: “SU,MO”)
	*
	* @desc String of days for the recurring module
	*/
	var $rec_days;

	/**
	* Short string symbolizing the startday of the week
	*
	* @desc Short string symbolizing the startday of the week
	*/
	var $week_start = 1;

	/**
	* Location of the module
	*
	* @desc Location of the module
	*/
	var $location;

	/**
	* String with the categories asigned to the module
	*
	* @desc String with the categories asigned to the module
	*/
	var $categories;

	/**
	* last modification date in iCal format
	*
	* @desc last modification date in iCal format
	*/
	var $last_mod;
	/**#@-*/

	/**#@+
	* @access private
	* @var array
	*/
	/**
	* Organizer of the module; $organizer[0] = Name, $organizer[1] = e-mail
	*
	* @desc Organizer of the module; $organizer[0] = Name, $organizer[1] = e-mail
	*/
	var $organizer = array('vCalEvent class', 'http://www.flaimo.com');

	/**
	* List of short strings symbolizing the weekdays
	*
	* @desc List of short strings symbolizing the weekdays
	*/
	var $shortDaynames = array('SU','MO','TU','WE','TH','FR','SA');

	/**
	* If the method is REQUEST, all attendees are listet in the file
	*
	* key = attendee name, value = e-mail, second value = role of the attendee
	* [0 = CHAIR | 1 = REQ | 2 = OPT | 3 =NON] (example: array('Michi' => 'flaimo@gmx.net,1'); )
	*
	* @desc If the method is REQUEST, all attendees are listet in the file
	*/
	var $attendees = array();

	/**
	* Array with the categories asigned to the module (example:
	* array('Freetime','Party'))
	*
	* @desc Array with the categories asigned to the module
	*/
	var $categories_array;

	/**
	* Exeptions dates for the recurring module (Array of timestamps)
	*
	* @desc Exeptions dates for the recurring module
	*/
	var $exept_dates;
	/**#@-*/

	/**#@+
	* @access private
	* @var int
	*/
	/**
	* set to 0
	*
	* @desc set to 0
	*/
	var $sequence;

	/**
	* 0 = once, 1-7 = secoundly - yearly
	*
	* @desc 0 = once, 1-7 = secoundly - yearly
	*/
	var $frequency;

	/**
	* If not empty, contains the status of the module
	* (0 = TENTATIVE, 1 = CONFIRMED, 2 = CANCELLED)
	*
	* @desc If not empty, contains the status of the module
	*/
	var $status;

	/**
	* interval of the recurring date (example: every 2,3,4 weeks)
	*
	* @desc
	*/
	var $interval = 1;

	/**
	* PRIVATE (0) or PUBLIC (1) or CONFIDENTIAL (2)
	*
	* @desc PRIVATE (0) or PUBLIC (1) or CONFIDENTIAL (2)
	*/
	var $class;

	/**
	* set to 5 (value between 0 and 9)
	*
	* @desc set to 5 (value between 0 and 9)
	*/
	var $priority;

	/**
	* Timestamp of the last modification
	*
	* @desc Timestamp of the last modification
	*/
	var $last_mod_ts;
	/**#@-*/

	/*-----------------------*/
	/* C O N S T R U C T O R */
	/*-----------------------*/

	/**#@+
	* @access private
	* @return void
	*/

	function iCalBase() {

	} // end constructor

	/*-------------------*/
	/* F U N C T I O N S */
	/*-------------------*/


	/**
	* Set $startdate variable
	*
	* @desc Set $startdate variable
	* @param string $isocode
	* @see getStartDate()
	* @see $startdate
	* @uses isValidLanguageCode()
	*/
	function setLanguage($isocode = '') {
		$this->lang = (string) (($this->isValidLanguageCode($isocode) == TRUE) ? ';LANGUAGE=' . $isocode : '');
	} // end function

	/**
	* Set $description variable
	*
	* @desc Set $description variable
	* @param string $description
	* @see getDescription()
	* @see $description
	*/
	function setDescription($description) {
		$this->description = (string) $description;
	} // end function

	/**
	* Set $organizer variable
	*
	* @desc Set $organizer variable
	* @param (array) $organizer
	* @see getOrganizerName()
	* @see getOrganizerMail()
	* @see $organizer
	*/
	function setOrganizer($organizer = '') {
		if (is_array($organizer)) {
			$this->organizer = (array) $organizer;
		} // end if
	} // end function

	/**
	* Set $url variable
	*
	* @desc Set $url variable
	* @param string $url
	* @see getURL()
	* @see $url
	* @since 1.011 - 2002-12-22
	*/
	function setURL($url = '') {
		$this->url = (string) $url;
	} // end function

	/**
	* Set $summary variable
	*
	* @desc Set $summary variable
	* @param string $summary
	* @see getSummary()
	* @see $summary
	*/
	function setSummary($summary = '') {
		$this->summary = (string) $summary;
	} // end function

	/**
	* Set $sequence variable
	*
	* @desc Set $sequence variable
	* @param int $int
	* @see getSequence()
	* @see $sequence
	*/
	function setSequence($int = 0) {
		$this->sequence = (int) $int;
	} // end function

	/**
	* Sets a string with weekdays of the recurring module
	*
	* @desc Sets a string with weekdays of the recurring event
	* @param (array) $recdays integers
	* @see getDays()
	* @see $rec_days
	* @since 1.010 - 2002-10-26
	*/
	function setDays($recdays = '') {
		$this->rec_days = (string) '';
		if (!is_array($recdays) || count($recdays) == 0) {
			$this->rec_days = (string) $this->shortDaynames[1];
		} else {
			if (count($recdays) > 1) {
				$recdays = array_values(array_unique($recdays));
			} // end if
			foreach ($recdays as $day) {
				if (array_key_exists($day, $this->shortDaynames)) {
					$this->rec_days .= (string) $this->shortDaynames[$day] . ',';
				} // end if
			} // end foreach
			$this->rec_days = (string) substr($this->rec_days,0,strlen($this->rec_days)-1);
		} // end if
	} // end function

	/**
	* Sets the starting day for the week (0 = Sunday)
	*
	* @desc Sets the starting day for the week (0 = Sunday)
	* @param int $weekstart  0–6
	* @see getWeekStart()
	* @see $week_start
	* @since 1.010 - 2002-10-26
	*/
	function setWeekStart($weekstart = 1) {
		if (is_int($weekstart) && preg_match('(^([0-6]{1})$)', $weekstart)) {
			$this->week_start = (int) $weekstart;
		} // end if
	} // end function

	/**
	* Set $attendees variable
	*
	* @desc Set $attendees variable
	* @param (array) $attendees
	* @see getAttendees()
	* @see $attendees
	* @since 1.001 - 2002-10-10
	*/
	function setAttendees($attendees = '') {
		if (is_array($attendees)) {
			$this->attendees = (array) $attendees;
		} // end if
	} // end function

	/**
	* Set $location variable
	*
	* @desc Set $location variable
	* @param string $location
	* @see getLocation()
	* @see $location
	*/
	function setLocation($location = '') {
		if (strlen(trim($location)) > 0) {
			$this->location = (string) $location;
		} // end if
	} // end function

	/**
	* Set $categories_array variable
	*
	* @desc Set $categories_array variable
	* @param string $categories
	* @see getCategoriesArray()
	* @see $categories_array
	*/
	function setCategoriesArray($categories = '') {
		$this->categories_array = (array) $categories;
	} // end function

	/**
	* Set $categories variable
	*
	* @desc Set $categories variable
	* @param string $categories
	* @see getCategories()
	* @see $categories
	*/
	function setCategories($categories = '') {
		$this->setCategoriesArray($categories);
		$this->categories = (string) implode(',',$categories);
	} // end function

	/**
	* Sets the frequency of a recurring event
	*
	* @desc Sets the frequency of a recurring event
	* @param int $int  Integer 0–7
	* @see getFrequency()
	* @see $frequencies
	* @since 1.010 - 2002-10-26
	*/
	function setFrequency($int = 0) {
		$this->frequency = (int) $int;
	} // end function

	/**
	* Set $status variable
	*
	* @desc Set $status variable
	* @param int $status
	* @see getStatus()
	* @see $status
	* @since 1.011 - 2002-12-22
	*/
	function setStatus($status = 1) {
		$this->status = (int) $status;
	} // end function

	/**
	* Sets the interval for a recurring event (2 = every 2 [days|weeks|years|…])
	*
	* @desc Sets the interval for a recurring event
	* @param int $interval
	* @see getInterval()
	* @see $interval
	* @since 1.010 - 2002-10-26
	*/
	function setInterval($interval = 1) {
			$this->interval = (int) $interval;
	} // end function

	/**
	* Sets an array of formated exeptions dates based on an array with timestamps
	*
	* @desc Sets an array of formated exeptions dates based on an array with timestamps
	* @param (array) $exeptdates
	* @see getExeptDates()
	* @see $exept_dates
	* @since 1.010 - 2002-10-26
	*/
	function setExeptDates($exeptdates = '') {
		if (!is_array($exeptdates)) {
			$this->exept_dates = (array) array();
		} else {
			foreach ($exeptdates as $timestamp) {
				$this->exept_dates[] = gmdate('Ymd\THi00\Z',$timestamp);
			} // end foreach
		} // end if
	} // end function

	/**
	* Set $class variable
	*
	* @desc Set $class variable
	* @param int $int
	* @see getClass()
	* @see $class
	*/
	function setClass($int = 0) {
		$this->class = (int) $int;
	} // end function

	/**
	* Set $priority variable
	*
	* @desc Set $priority variable
	* @param int $int
	* @see getPriority()
	* @see $priority
	*/
	function setPriority($int = 5) {
		$this->priority = (int) ((is_int($int) && preg_match('(^([0-9]{1})$)', $int)) ? $int : 5);
	} // end function

	/**
	* Set $last_mod_ts variable
	*
	* @desc Set $last_mod_ts variable
	* @param int $timestamp
	* @see getLastModTS()
	* @see $last_mod_ts
	* @since 1.020 - 2002-12-24
	*/
	function setLastModTS($timestamp = 0) {
		if (is_int($timestamp) && $timestamp > 0) {
			$this->last_mod_ts = (int) $timestamp;
		} // end if
	} // end function

	/**
	* Set $last_mod variable
	*
	* @desc Set $last_mod variable
	* @param int $last_mod
	* @see getLastMod()
	* @see $last_mod
	* @since 1.020 - 2002-12-24
	*/
	function setLastMod($timestamp = 0) {
		$this->setLastModTS($timestamp);
		$this->last_mod = (string) gmdate('Ymd\THi00\Z',$this->last_mod_ts);
	} // end function
	/**#@-*/

	/**
	* Checks if a given string is a valid iso-language-code
	*
	* @desc Checks if a given string is a valid iso-language-code
	* @param string $code  String that should validated
	* @return boolean isvalid  If string is valid or not
	* @access protected
	* @since 1.001 - 2002/10/19
	*/
	function isValidLanguageCode($code = '') {
		return (boolean) ((preg_match('(^([a-zA-Z]{2})((_|-)[a-zA-Z]{2})?$)',trim($code)) > 0) ? TRUE : FALSE);
	} // end function


	/**#@+
	* @access public
	*/
	/**
	* Get $startdate variable
	*
	* @desc Get $startdate variable
	* @return (int) $startdate
	* @see setStartDate()
	* @see $startdate
	*/
	function &getLanguage() {
		return (string) $this->lang;
	} // end function

	/**
	* Get $description variable
	*
	* @desc Get $description variable
	* @return string $description
	* @see setDescription()
	* @see $description
	*/
	function &getDescription() {
		return (string) $this->description;
	} // end function

	/**
	* Get name from $organizer variable
	*
	* @desc Get name from $organizer variable
	* @return (array) $organizer
	* @see setOrganizer()
	* @see getOrganizerMail()
	* @see $organizer
	* @since 1.011 - 2002-12-22
	*/
	function &getOrganizerName() {
		return (string) $this->organizer[0];
	} // end function

	/**
	* Get e-mail from $organizer variable
	*
	* @desc Get e-mail from $organizer variable
	* @return (array) $organizer
	* @see setOrganizer()
	* @see getOrganizerName()
	* @see $organizer
	* @since 1.011 - 2002-12-22
	*/
	function &getOrganizerMail() {
		return (string) $this->organizer[1];
	} // end function

	/**
	* Get $url variable
	*
	* @desc Get $url variable
	* @return string $url
	* @see setURL()
	* @see $url
	* @since 1.011 - 2002-12-22
	*/
	function &getURL() {
		return (string) $this->url;
	} // end function

	/**
	* Get $summary variable
	*
	* @desc Get $summary variable
	* @return string $summary
	* @see setSummary()
	* @see $summary
	*/
	function &getSummary() {
		return (string) $this->summary;
	} // end function

	/**
	* Get $sequence variable
	*
	* @desc Get $sequence variable
	* @return (int) $sequence
	* @see setSequence()
	* @see $sequence
	*/
	function &getSequence() {
		return (int) $this->sequence;
	} // end function

	/**
	* Returns a string with recurring days
	*
	* @desc Returns a string with recurring days
	* @return string $rec_days
	* @see setDays()
	* @see $rec_days
	* @since 1.010 - 2002-10-26
	*/
	function &getDays() {
		return (string) $this->rec_days;
	} // end function

	/**
	* Get the string from the $week_start variable
	*
	* @desc Get the string from the $week_start variable
	* @return string $shortDaynames
	* @see setWeekStart()
	* @see $week_start
	* @since 1.010 - 2002-10-26
	*/
	function &getWeekStart() {
		return (string) ((array_key_exists($this->week_start, $this->shortDaynames)) ? $this->shortDaynames[$this->week_start] : $this->shortDaynames[1]);
	} // end function

	/**
	* Get $attendees variable
	*
	* @desc Get $attendees variable
	* @return string $attendees
	* @see setAttendees()
	* @see $attendees
	* @since 1.001 - 2002-10-10
	*/
	function &getAttendees() {
		return (array) $this->attendees;
	} // end function

	/**
	* Get $location variable
	*
	* @desc Get $location variable
	* @return string $location
	* @see setLocation()
	* @see $location
	*/
	function &getLocation() {
		return (string) $this->location;
	} // end function

	/**
	* Get $categories_array variable
	*
	* @desc Get $categories_array variable
	* @return (array) $categories_array
	* @see setCategoriesArray()
	* @see $categories_array
	*/
	function &getCategoriesArray() {
		return (array) $this->categories_array;
	} // end function

	/**
	* Get $categories variable
	*
	* @desc Get $categories variable
	* @return string $categories
	* @see setCategories()
	* @see $categories
	*/
	function &getCategories() {
		return (string) $this->categories;
	} // end function

	/**
	* Get $frequency variable
	*
	* @desc Get $frequency variable
	* @return string $frequencies
	* @see setFrequency()
	* @see $frequencies
	* @since 1.010 - 2002-10-26
	*/
	function &getFrequency() {
		return (int) $this->frequency;
	} // end function

	/**
	* Get $status variable
	*
	* @desc Get $status variable
	* @return string $statuscode
	* @see setStatus()
	* @see $status
	* @since 1.011 - 2002-12-22
	*/
	function &getStatus() {
		return (int) $this->status;
	} // end function

	/**
	* Get $interval variable
	*
	* @desc Get $interval variable
	* @return (int) $interval
	* @see setInterval()
	* @see $interval
	* @since 1.010 - 2002-10-26
	*/
	function &getInterval() {
		return (int) $this->interval;
	} // end function

	/**
	* Returns a string with exeptiondates
	*
	* @desc Returns a string with exeptiondates
	* @return string $return
	* @see setExeptDates()
	* @see $exept_dates
	* @since 1.010 - 2002-10-26
	*/
	function &getExeptDates() {
		$return = (string) '';
		foreach ($this->exept_dates as $date) {
			$return .= (string) $date . ',';
		} // end foreach
		$return = (string) substr($return,0,strlen($return)-1);
		return (string) $return;
	} // end function

	/**
	* Get $class variable
	*
	* @desc Get $class variable
	* @return string $class
	* @see setClass()
	* @see $class
	*/
	function &getClass() {
		return (int) $this->class;
	} // end function

	/**
	* Get $priority variable
	*
	* @desc Get $priority variable
	* @return string $priority
	* @see setPriority()
	* @see $priority
	*/
	function &getPriority() {
		return (int) $this->priority;
	} // end function

	/**
	* Get $last_mod_ts variable
	*
	* @desc Get $last_mod_ts variable
	* @return (int) $last_mod_ts
	* @see setLastModTS()
	* @see $last_mod_ts
	* @since 1.020 - 2002-12-24
	*/
	function &getLastModTS() {
		return (int) $this->last_mod_ts;
	} // end function

	/**
	* Get $last_mod variable
	*
	* @desc Get $last_mod variable
	* @return (int) $last_mod
	* @see setLastMod()
	* @see $last_mod
	* @since 1.020 - 2002-12-24
	*/
	function &getLastMod() {
		return (string) $this->last_mod;
	} // end function
	/**#@-*/
} // end class iCalBase
?>