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
// $Id: class.icalfreebusy.inc.php 1 2008-07-11 20:11:41Z simpson $

/**
* @package iCalendar Everything to generate simple iCal files
*/
/**
* We need the base class
*/
include_once 'class.icalbase.inc.php';
/**
* Container for a single freebusy
*
* Tested with WAMP (XP-SP1/1.3.24/4.0.4/4.3.0)
* Last Change: 2003-03-29
*
* @desc Container for a single freebusy
* @access private
* @author Michael Wimmer <flaimo 'at' gmx 'dot' net>
* @copyright Michael Wimmer
* @link http://www.flaimo.com/
* @package iCalendar
*/
class iCalFreeBusy extends iCalBase {

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
	* Timestamp of the end date
	*
	* @desc Timestamp of the end date
	* @var int
	*/
	var $enddate_ts;

	/**
	* start date in iCal format
	*
	* @desc start date in iCal format
	* @var string
	*/
	var $startdate;

	/**
	* end date in iCal format
	*
	* @desc end date in iCal format
	* @var string
	*/
	var $enddate;

	/**
	* Automaticaly created: md5 value of the start date + end date
	*
	* @desc Automaticaly created: md5 value of the start date + end date
	* @var string
	*/
	var $uid;

	/**
	* Duration of the freebusy in minutes
	*
	* @desc Duration of the freebusy in minutes
	* @var int
	*/
	var $duration;

	/**
	* Array with all the free busy times
	*
	* @desc Array with all the free busy times
	* @var array
	*/
	var $freebusy_times;

	/**
	* 0 = FREE, 1 = BUSY, 2 = BUSY-UNAVAILABLE, 3 = BUSY-TENTATIVE
	*
	* @desc 0 = FREE, 1 = BUSY, 2 = BUSY-UNAVAILABLE, 3 = BUSY-TENTATIVE
	* @var array
	*/
	var $fb_status;
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
	* @param int $start  Start time for fb (timestamp)
	* @param int $end  Start time for fb (timestamp)
	* @param int $duration  Duration of the fb in minutes
	* @param array $organizer  The organizer - use array('Name', 'name@domain.com')
	* @param array $attendees  key = attendee name, value = e-mail, second value = role of the attendee [0 = CHAIR | 1 = REQ | 2 = OPT | 3 =NON] (example: array('Michi' => 'flaimo@gmx.net,1'); )
	* @param array $fb_times  key = timestamp (starting point), value = minutes, secound value = status (0 = FREE, 1 = BUSY, 2 = BUSY-UNAVAILABLE, 3 = BUSY-TENTATIVE)
	* @param string $url optional URL for that event
	* @param string $uid  Optional UID for the FreeBusy
	* @uses iCalBase::setOrganizer()
	* @uses setStartDate()
	* @uses setDuration()
	* @uses setEndDate()
	* @uses setUID()
	* @uses iCalBase::setAttendees()
	* @uses setFBTimes()
	* @uses iCalBase::setURL()
	*/
	function iCalFreeBusy($start, $end, $duration, $organizer, $attendees, $fb_times, $url, $uid) {
		parent::iCalBase();
		$this->fb_status = (array) array('FREE','BUSY','BUSY-UNAVAILABLE','BUSY-TENTATIVE');
		parent::setOrganizer($organizer);
		$this->setStartDate($start);
		$this->setDuration($duration);
		$this->setEndDate($end);
		parent::setAttendees($attendees);
		$this->setFBTimes($fb_times);
		parent::setURL($url);
        $this->setUID($uid);
	} // end constructor

	/*-------------------*/
	/* F U N C T I O N S */
	/*-------------------*/

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
	* Set $enddate_ts variable
	*
	* @desc Set $enddate_ts variable
	* @param int $timestamp
	* @see getEndDateTS()
	* @see $enddate_ts
	*/
	function setEndDateTS($timestamp = 0) {
		if (is_int($timestamp) && $timestamp > 0) {
			$this->enddate_ts = (int) $timestamp;
		} else {
			$this->enddate_ts = (int) ((isset($this->startdate_ts) && is_numeric($this->startdate_ts) && $this->startdate_ts > 0) ? ($this->startdate_ts + 3600) : (time() + 3600));
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
	* Set $enddate variable
	*
	* @desc Set $enddate variable
	* @param int $timestamp
	* @see getEndDate()
	* @see $enddate
	* @uses setEndDateTS()
	*/
	function setEndDate($timestamp = 0) {
		$this->setEndDateTS($timestamp);
		$this->enddate = (string) gmdate('Ymd\THi00\Z',$this->enddate_ts);
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
            $rawid      = (string) $this->startdate . 'plus' . $this->enddate;
            $this->uid = (string) md5($rawid);
        }
	} // end function

	/**
	* Set $duration variable
	*
	* @desc Set $duration variable
	* @param int $int
	* @see getDuration()
	* @see $duration
	* @since 1.020 - 2002-12-24
	*/
	function setDuration($int) {
		$this->duration = (int) $int;
	} // end function

	/**
	* Set $freebusy_times variable
	*
	* @desc Set $freebusy_times variable
	* @param (array) $times
	* @see getFBTimes()
	* @see $freebusy_times
	* @uses getFBStatus()
	* @since 1.020 - 2002-12-24
	*/
	function setFBTimes($times = '') {
		if (is_array($times)) {
			foreach ($times as $timestamp => $data) {
				$values     = (array) explode(',',$data);
				$minutes    = (int) $values[0];
				$status     = (string) $this->getFBStatus($values[1]);
				unset($values);
				$this->freebusy_times[gmdate('Ymd\THi00\Z',$timestamp)] = gmdate('Ymd\THi00\Z',$minutes) . ',' . $status;
			} // end foreach
		} // end if
	} // end function
	/**#@-*/

	/**#@+
	* @access public
	*/
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
	* Get $enddate_ts variable
	*
	* @desc Set $startdate variable
	* @return (int) $enddate_ts
	* @see setEndDateTS()
	* @see $enddate_ts
	*/
	function &getEndDateTS() {
		return (int) $this->enddate_ts;
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
	* Get $enddate variable
	*
	* @desc Get $enddate variable
	* @return string $enddate
	* @see setEndDate()
	* @see $enddate
	*/
	function &getEndDate() {
		return (string) $this->enddate;
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

	/**
	* Get $duration variable
	*
	* @desc Get $duration variable
	* @return (int) $duration
	* @see setDuration()
	* @see $duration
	* @access private
	* @since 1.020 - 2002-12-24
	*/
	function &getDuration() {
		return (int) $this->duration;
	} // end function

	/**
	* Get $freebusy_times variable
	*
	* @desc Get $freebusy_times variable
	* @return (int) $freebusy_times
	* @see setFBTimes()
	* @see $freebusy_times
	* @access private
	* @since 1.020 - 2002-12-24
	*/
	function &getFBTimes() {
		return (array) $this->freebusy_times;
	} // end function

	/**
	* Get $Status for a FreeBusy statuscode
	*
	* @desc  Get $Status for a  FreeBusy statuscode
	* @return string $fb_status
	* @see setFreeBusy()
	* @see $freebusy_times
	* @access private
	* @since 1.020 - 2002-12-24
	*/
	function &getFBStatus($int = 0) {
		return (string) ((array_key_exists($int, $this->fb_status)) ? $this->fb_status[$int] : $this->fb_status[0]);
	} // end function
} // end class iCalFreeBusy
?>
