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
// $Id: class.icalalarm.inc.php 1 2008-07-11 20:11:41Z simpson $

/**
* @package iCalendar Everything to generate simple iCal files
*/
/**
* We need the base class
*/
include_once 'class.icalbase.inc.php';
/**
* Container for an alarm (used in event and todo)
*
* Tested with WAMP (XP-SP1/1.3.24/4.0.4/4.3.0)
* Last Change: 2003-03-29
*
* @desc Container for an alarm (used in event and todo)
* @access private
* @author Michael Wimmer <flaimo 'at' gmx 'dot' net>
* @copyright Michael Wimmer
* @link http://www.flaimo.com/
* @package iCalendar
*/
class iCalAlarm extends iCalBase {

	/*-------------------*/
	/* V A R I A B L E S */
	/*-------------------*/

	/**#@+
	* @access private
	* @var int
	*/
	/**
	* Kind of alarm (0 = DISPLAY, 1 = EMAIL, (not supported: 2 = AUDIO, 3 = PROCEDURE))
	*
	* @desc Kind of alarm (0 = DISPLAY, 1 = EMAIL, (not supported: 2 = AUDIO, 3 = PROCEDURE))
	*/
	var $action;

	/**
	* Minutes the alarm goes off before the event/todo
	*
	* @desc Minutes the alarm goes off before the event/todo
	*/
	var $trigger = 0;

	/**
	* Headline for the alarm (if action = Display)
	*
	* @desc Headline for the alarm (if action = Display)
	* @var string
	*/
	var $summary;

	/**
	* Duration between the alarms in minutes
	*
	* @desc Duration between the alarms in minutes
	*/
	var $duration;

	/**
	* How often should the alarm be repeated
	*
	* @desc How often should the alarm be repeated
	*/
	var $repeat;
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
	* @param int $action  0 = DISPLAY, 1 = EMAIL, (not supported: 2 = AUDIO, 3 = PROCEDURE)
	* @param int $trigger  Minutes the alarm goes off before the event/todo
	* @param string $summary  Title for the alarm
	* @param string $description  Description
	* @param (array) $attendees  key = attendee name, value = e-mail, second value = role of the attendee
	* [0 = CHAIR | 1 = REQ | 2 = OPT | 3 =NON] (example: array('Michi' => 'flaimo@gmx.net,1'); )
	* @param int $duration  Duration between the alarms in minutes
	* @param int $repeat  How often should the alarm be repeated
	* @param string $lang  Language of the strings used in the event (iso code)
	* @uses setAction()
	* @uses setTrigger()
	* @uses setSummary()
	* @uses iCalBase::setDescription()
	* @uses setAttendees()
	* @uses setDuration()
	* @uses setRepeat()
	* @uses iCalBase::setLanguage()
	*/
	function iCalAlarm($action, $trigger, $summary, $description, $attendees,
					   $duration, $repeat, $lang) {
        parent::iCalBase();
        $this->setAction($action);
		$this->setTrigger($trigger);
		parent::setSummary($summary);
		parent::setDescription($description);
		parent::setAttendees($attendees);
		$this->setDuration($duration);
		$this->setRepeat($repeat);
		parent::setLanguage($lang);
	} // end constructor

	/*-------------------*/
	/* F U N C T I O N S */
	/*-------------------*/

	/**
	* Set $action variable
	*
	* @desc Set $action variable
	* @param int $action 0 = DISPLAY, 1 = EMAIL, (not supported: 2 = AUDIO, 3 = PROCEDURE)
	* @see getAction()
	* @see $action
	* @since 1.021 - 2002-12-24
	*/
	function setAction($action = 0) {
		$this->action = (int) $action;
	} // end function

	/**
	* Set $trigger variable
	*
	* @desc Set $trigger variable
	* @param int $minutes
	* @see getTrigger()
	* @see  $minutes
	* @since 1.021 - 2002-12-24
	*/
	function setTrigger($minutes = 0) {
		$this->trigger = (int) $minutes;
	} // end function

	/**
	* Set $duration variable
	*
	* @desc Set $duration variable
	* @param int $int
	* @see getDuration()
	* @see  $duration
	* @since 1.020 - 2002-12-24
	*/
	function setDuration($int = 0) {
		$this->duration = (int) $int;
	} // end function

	/**
	* Set $repeat variable
	*
	* @desc Set $repeat variable
	* @param int $int  in minutes
	* @see getRepeat()
	* @see  $repeat
	* @since 1.020 - 2002-12-24
	*/
	function setRepeat($int = 0) {
		$this->duration = (int) $int;
	} // end function
	/**#@-*/

	/**#@+
	* @access public
	* @since 1.021 - 2002-12-24
	*/
	/**
	* Get $action variable
	*
	* @desc Get $action variable
	* @return string $action
	* @see setAction()
	* @see $action
	*/
	function &getAction() {
		$action_status = (array) array('DISPLAY', 'EMAIL', 'AUDIO', 'PROCEDURE');
		return (string) ((array_key_exists($this->action, $action_status)) ? $action_status[$this->action] : $action_status[0]);
	} // end function

	/**
	* Get $trigger variable
	*
	* @desc Get $trigger variable
	* @return int $trigger
	* @see setTrigger()
	* @see $trigger
	*/
	function &getTrigger() {
		return (int) $this->trigger;
	} // end function
	/**#@-*/

	/**
	* Get $duration variable
	*
	* @desc Get $duration variable
	* @return int $duration
	* @see setDuration()
	* @see $duration
	* @access private
	*/
	function &getDuration() {
		return (int) $this->duration;
	} // end function

	/**
	* Get $repeat variable
	*
	* @desc Get $repeat variable
	* @return int $repeat
	* @see setRepeat()
	* @see $repeat
	* @access private
	*/
	function &getRepeat() {
		return (int) $this->duration;
	} // end function
} // end class iCalAlarm
?>
