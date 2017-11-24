<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 *
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
*/

require_once("Template.class.php");

/**
 * Class for simplifying the emailing of templates 
 * @author Jonathan Fingland
 *
 */
class TemplateMailer {
	
	/**
	 * Mail handler, currently only Zend_Mail is supported
	 * @var Zend_Mail
	 */
	private $_mail_handler;
	
	function __construct($mail_handler) {
		$this->_mail_handler = $mail_handler;
		$this->_mail_handler->addHeader("X-Originating-IP", $_SERVER["REMOTE_ADDR"]);
	}
	
	/**
	 * passes on unknown methods to the mail handler dependency
	 * @param string $method
	 * @param array $options
	 */
	public function __call($method, array $options) {
        if (method_exists($this->_mail_handler, $method)) {
            return call_user_func_array(array($this->_mail_handler, $method), $options);
        }
        throw new BadMethodCallException($method . " is not a valid method identifier");
    }
	
 	/**
 	 * 
 	 * @param Template $template
 	 * @param array $to Array containing elements "email", "firstname", and "lastname"
 	 * @param array $from Array containing elements "email", "firstname", and "lastname"
 	 * @param string $language
 	 * @param array $bind_array Array of template variables to be bound to the template
 	 * @throws RuntimeException
 	 * @return boolean
 	 */
 	public function send(Template $template, array $to, array $from, $language, array $bind_array = array()) {
 		
 		$result = $template->getResult($bind_array, array("lang" => $language ));
	
		$mail = $this->_mail_handler;
		
		$mail->clearFrom();
		$mail->setFrom($from["email"], implode(" ",array($from["firstname"], $from["lastname"])));
		
		
		$mail->clearSubject();
		$mail->setSubject($result->subject);
		
		$mail->setBodyText(clean_input($result->body, "emailcontent"));

		$mail->clearRecipients();
		$mail->addTo($to["email"], implode(" ",array($to["firstname"], $to["lastname"])));

		if ($mail->send()) {
			return true;
		}
		throw new RuntimeException("Failed to send email");
	}    
    
}