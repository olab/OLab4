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
// $Id: class.icaljournal.inc.php 1 2008-07-11 20:11:41Z simpson $

/**
* @package iCalendar Everything to generate simple iCal files
*/
/**
* We need the base class
*/
include_once 'class.icalbase.inc.php';
/**
* Container for a single Journal
*
* Tested with WAMP (XP-SP1/1.3.24/4.0.4/4.3.0)
* Last Change: 2003-03-29
*
* @desc Container for a single Journal
* @access private
* @author Michael Wimmer <flaimo 'at' gmx 'dot' net>
* @copyright Michael Wimmer
* @link http://www.flaimo.com/
* @package iCalendar
*/
class iCalJournal extends iCalBase {

	/*-------------------*/
	/* V A R I A B L E S */
	/*-------------------*/

	/**#@+
	* @access private
	*/
	/**
	* Timestamp of the start date
	*
	* @desc Timestamp of the start date
	* @var int
	*/
	var $startdate_ts;

	/**
	* start date in iCal format
	*
	* @desc start date in iCal format
	* @var string
	*/
	var $startdate;

	/**
	* Timestamp of the creation date
	*
	* @desc Timestamp of the creation date
	* @var int
	*/
	var $created_ts;

	/**
	* creation date in iCal format
	*
	* @desc creation date in iCal format
	* @var string
	*/
	var $created;

	/**
	* Automaticaly created: md5 value of the start date + end date
	*
	* @desc Automaticaly created: md5 value of the start date + end date
	* @var string
	*/
	var $uid;

	/**
	* '' = never, integer < 4 numbers = number of times, integer >= 4 = timestamp
	*
	* @desc '' = never, integer < 4 numbers = number of times, integer >= 4 = timestamp
	* @var mixed
	*/
	var $rec_end;
	/**#@-*/


	/*-----------------------*/
	/* C O N S T R U C T O R */
	/*-----------------------*/

	/**#@+
	* @access private
	* @return void
	*/
	/**
	* Constructor
	*
	* Only job is to set all the variablesnames
	*
	* @desc Constructor
	* @param string $summary  Title for the event
	* @param string $description  Description
	* @param int $start  Start time for the event (timestamp)
	* @param int $created  Creation date for the event (timestamp)
	* @param int $last_mod  Last modification date for the event (timestamp)
	* @param int $status  Status of the event (0 = TENTATIVE, 1 = CONFIRMED, 2 = CANCELLED)
	* @param int $class  (0 = PRIVATE | 1 = PUBLIC | 2 = CONFIDENTIAL)
	* @param array $organizer  The organizer � use array('Name', 'name@domain.com')
	* @param array $attendees  key = attendee name, value = e-mail, second value = role of the attendee [0 = CHAIR | 1 = REQ | 2 = OPT | 3 =NON] (example: array('Michi' => 'flaimo@gmx.net,1'); )
	* @param array $categories  Array with Strings (example: array('Freetime','Party'))
	* @param int $frequency  frequency: 0 = once, secoundly � yearly = 1�7
	* @param mixed $rec_end  recurrency end: ('' = forever | integer = number of times | timestring = explicit date)
	* @param int $interval  Interval for frequency (every 2,3,4 weeks�)
	* @param string $days  Array with the number of the days the event accures (example: array(0,1,5) = Sunday, Monday, Friday
	* @param string $weekstart  Startday of the Week ( 0 = Sunday � 6 = Saturday)
	* @param string $exept_dates  exeption dates: Array with timestamps of dates that should not be includes in the recurring event
	* @param string $url  optional URL for that event
	* @param string $lang  Language of the strings used in the event (iso code)
	* @param string $uid  Optional UID for the Journal
	* @uses setSummary()
	* @uses iCalBase::setDescription()
	* @uses setStartDate()
	* @uses setCreated()
	* @uses iCalBase::setLastMod()
	* @uses iCalBase::setStatus()
	* @uses iCalBase::setClass()
	* @uses iCalBase::setOrganizer()
	* @uses iCalBase::setAttendees()
	* @uses iCalBase::setCategories()
	* @uses iCalBase::setURL()
	* @uses iCalBase::setLanguage()
	* @uses iCalBase::setFrequency()
	* @uses setRecEnd()
	* @uses iCalBase::setInterval()
	* @uses iCalBase::setDays()
	* @uses iCalBase::setWeekStart()
	* @uses iCalBase::setExeptDates()
	* @uses iCalBase::setSequence()
	* @uses setUID()
	*/
	function iCalJournal($summary, $description, $start, $created, $last_mod,
						 $status, $class, $organizer, $attendees, $categories,
						 $frequency, $rec_end, $interval, $days, $weekstart,
						 $exept_dates, $url, $lang, $uid) {

		parent::iCalBase();
		parent::setSummary($summary);
		parent::setDescription($description);
		$this->setStartDate($start);
		$this->setCreated($created);
		parent::setLastMod($last_mod);
		parent::setStatus($status);
		parent::setClass($class);
		parent::setOrganizer($organizer);
		parent::setAttendees($attendees);
		parent::setCategories($categories);
		parent::setURL($url);
		parent::setLanguage($lang);
		parent::setFrequency($frequency);
		$this->setRecEnd($rec_end);
		parent::setInterval($interval);
		parent::setDays($days);
		parent::setWeekStart($weekstart);
		parent::setExeptDates($exept_dates);
		parent::setSequence(0);
		$this->setUID($uid);
	} // end constructor

	/*-------------------*/
	/* F U N C T I O N S */
	/*-------------------*/

	/**
	* Sets the end for a recurring event (0 = never ending,
	* integer < 4 numbers = number of times, integer >= 4 enddate)
	*
	* @desc Sets the end for a recurring event
	* @param int $freq
	* @see getRecEnd()
	* @see $rec_end
	* @since 1.010 - 2002-10-26
	*/
	function setRecEnd($freq = '') {
		if (strlen(trim($freq)) < 1) {
			$this->rec_end = 0;
		} elseif (is_int($freq) && strlen(trim($freq)) < 4) {
			$this->rec_end = $freq;
		} else {
			$this->rec_end = (string) gmdate('Ymd\THi00\Z',$freq);
		} // end if
	} // end function

	/**
	* Set $startdate_ts variable
	*
	* @desc Set $startdate_ts variable
	* @param int $timestamp
	* @see getStartDateTS()
	* @see $startdate_ts
	*/
	function setStartDateTS($timestamp = 0) {
		if (is_int($timestamp) && $timestamp > 0) {
			$this->startdate_ts = (int) $timestamp;
		} else {
			$this->startdate_ts = (int) ((isset($this->enddate_ts) && is_numeric($this->enddate_ts) && $this->enddate_ts > 0) ? ($this->enddate_ts - 3600) : time());
		} // end if
	} // end function

	/**
	* Set $created_ts variable
	*
	* @desc Set $created_ts variable
	* @param int $timestamp
	* @see getCreatedTS()
	* @see $created_ts
	*/
	function setCreatedTS($timestamp = 0) {
		if (is_int($timestamp) && $timestamp > 0) {
			$this->created_ts = (int) $timestamp;
		} // end if
	} // end function

	/**
	* Set $startdate variable
	*
	* @desc Set $startdate variable
	* @param int $timestamp
	* @see getStartDate()
	* @see $startdate
	* @uses setStartDateTS()
	*/
	function setStartDate($timestamp = 0) {
		$this->setStartDateTS($timestamp);
		$this->startdate = (string) gmdate('Ymd\THi00\Z',$this->startdate_ts);
	} // end function

	/**
	* Set $created variable
	*
	* @desc Set $created variable
	* @param int $timestamp
	* @see getCreated()
	* @see $created
	* @uses setCreatedTS()
	*/
	function setCreated($timestamp = 0) {
		$this->setCreatedTS($timestamp);
		$this->created = (string) gmdate('Ymd\THi00\Z',$this->created_ts);
	} // end function

	/**
	* Set $uid variable
	*
	* @desc Set $uid variable
    * @param int $uid
	* @see getUID()
	* @see $uid
	*/
	function setUID($uid = 0) {
		if (strlen(trim($uid)) > 0) {
            $this->uid = (string) $uid;
        } else {
            $rawid = (string) $this->startdate . 'plus' .  $this->summary;
            $this->uid = (string) md5($rawid);
        } // end if
	} // end function
	/**#@-*/

	/**#@+
	* @access public
	*/
	/**
	* Get $rec_end variable
	*
	* @desc Get $rec_end variable
	* @return (mixed) $rec_end
	* @see setRecEnd()
	* @see $rec_end
	* @since 1.010 - 2002-10-26
	*/
	function &getRecEnd() {
		return $this->rec_end;
	} // end function

	/**
	* Get $startdate_ts variable
	*
	* @desc Get $startdate_ts variable
	* @return (int) $startdate_ts
	* @see setStartDateTS()
	* @see $startdate_ts
	*/
	function &getStartDateTS() {
		return (int) $this->startdate_ts;
	} // end function

	/**
	* Get $created_ts variable
	*
	* @desc Get $created_ts variable
	* @return (int) $created_ts
	* @see setCreatedTS()
	* @see $created_ts
	*/
	function &getCreatedTS() {
		return (int) $this->created_ts;
	} // end function

	/**
	* Get $startdate variable
	*
	* @desc Get $startdate variable
	* @return (int) $startdate
	* @see setStartDate()
	* @see $startdate
	*/
	function &getStartDate() {
		return (string) $this->startdate;
	} // end function

	/**
	* Get $created variable
	*
	* @desc Get $created variable
	* @return string $created
	* @see setCreated()
	* @see $created
	*/
	function &getCreated() {
		return (string) $this->created;
	} // end function

	/**
	* Get $uid variable
	*
	* @desc Get $uid variable
	* @return string $uid
	* @see setUID()
	* @see $uid
	*/
	function &getUID() {
		return (string) $this->uid;
	} // end function
	/**#@-*/
} // end class iCalJournal
?>
