<?php
/**
 * Entrada Authenticator - Client
 *
 * This client portion of the Entrada Authenticatior should be distributed with
 * any PHP application that you wish to authenticate users through.
 * 
 * @todo
 *
 * LICENSE: TBD
 *
 * @copyright  2008 Queen's University, Medical Education Technology
 * @author     Matt Simpson <matt.simpson@queensu.ca>
 * @license    http://entrada-project.org/legal/licence
 * @version    $Id: authentication.class.php 668 2009-08-20 19:23:08Z simpson $
 * @link       http://entrada-project.org/package/Authentication
 * @since      Available since Entrada 0.6.0
 * 
 * Changes:
 * =============================================================================
 * 1.4.0 - August 10th, 2011
 * [+]	Added enc_method variable to specify encryption method (default = low security, no requirements | blowfish = medium security, requires mCrypt | rijndael 256 = highest security, requires mcrypt).
 * 
 * 1.3.0 - August 25th, 2010
 * [*]	Due to chained auth_method, passwords must be sent to server.
 *
 * 1.2.0 - October 24th, 2008
 * [*]  Ported to PHP5 code.
 * [*]  Removed setURL() function as this is now handled in the contstructor.
 * [+]  Automatically sets the protocol and port if it detects SSL.
 * [+]  Added 
 * 
 * 1.1.1 - October 15th, 2004
 * [*]  Updated header comments and added SVN Id string.
 * 
 * 1.1.0 - September 16th, 2004
 * [*]	Updated documentation and server functions.
 * 
 * 1.0.0 - April 1st, 2004
 * [+]	First release of MEdAS
 * 
 * Credits:
 * =============================================================================
 * - Some HTTP Posting code was originally written by Daniel Kushner.
 * - Encryption algorithm originally designed by Abdullah Khaidar.
 * 
*/

class AuthSystem {
	private $version = "1.4.0";
	private $connection = array("url" => false, "uri" => false, "protocol" => "http", "port" => 80, "timeout" => 10);	// default connection url, uri, protocol, port & connection timeout.
	private $xml_array = array();
	private $data = array();
	private $httpAuthInfo = array();
	private $encryptionMethod = "default";

	public function __construct($url = "", $protocol = false, $port = false, $timeout = false) {
		
		if (($url != "") && ($url_parsed = @parse_url($url))) {
			$this->connection["url"] = $url_parsed["host"];
			$this->connection["uri"] = $url_parsed["path"];
			if (isset($url_parsed["port"]) && $url_parsed["port"]) {
				$this->connection["port"] = $url_parsed["port"];
			}

			if ((isset($url_parsed["scheme"])) && ($url_parsed["scheme"] == "https")) {
				$this->connection["protocol"] = "https";
				$this->connection["port"] = 443;
			}

			if ($protocol) {
				$this->connection["protocol"] = $protocol;
			}
			
			if ($port) {
				$this->connection["port"] = $port;
			}
			
			if ($timeout) {
				$this->connection["timeout"] = $timeout;
			}
		} else {
			return false;	
		}
	}

	public function setEncryption($method = "") {
		if (!trim($method)) {
			$method = "default";
		}
		
		if ($method != "default" && !function_exists("mcrypt_encrypt")) {
			$method = "default";
		}
		
		$this->encryptionMethod = $method;
	}
	
	/**
	 * This function sets the application authentication information.
	 * @return 
	 * @param object $auth_app_id
	 * @param object $auth_username
	 * @param object $auth_password
	 */
	public function setAppAuthentication($auth_app_id = "", $auth_username = "", $auth_password = "") {
		$this->data["auth_app_id"] = $auth_app_id;
		$this->data["auth_username"] = $auth_username;
		$this->data["auth_password"] = md5($auth_password);

		$this->httpAuthInfo = array($this->data["auth_username"], $this->data["auth_password"]);
	}

	/**
	 * This function sets the end user information that you're attempting to authenticate.
	 * @return 
	 * @param object $username[optional]
	 * @param object $password[optional]
	 */
	public function setUserAuthentication($username = "", $password = "", $auth_method = "local") {
		$this->data["username"] = $username;
		$this->data["password"] = $password;
		$this->data["auth_method"] = $auth_method;
	}

	/**
	 * This function executes the required tasks.
	 * @return 
	 * @param object $req_info
	 */
	public function Authenticate($requested_info = array()) {
		$this->data["action"] = "Authenticate";

		if ((is_array($requested_info)) && (count($requested_info))) {
			$this->data["requested_info"] = base64_encode(serialize($requested_info));
		}

		return $this->processRequest();
	}

	/**
	 * This function updates the last time the user was logged into your application.
	 * @return 
	 */
	public function updateLastLogin() {
		$this->data["action"] = "updateLastLogin";

		$this->data["last_login"] = time();
		$this->data["last_ip"] = $_SERVER["REMOTE_ADDR"];

		unset($this->data["requested_info"]);

		return $this->processRequest();
	}
	
	/**
	 * This function prepares the HTTP Post for the Process function.
	 * @return 
	 * @param object $array
	 * @param object $index[optional]
	 */
	private function prepareRequest(&$array, $index = "") {
		foreach ($array as $key => $val) {
			if (is_array($val)) {
				if ($index) {
					$body[] = $this->prepareRequest($val, $index."[".$key."]");
				} else {
					$body[] = $this->prepareRequest($val, $key);
				}
			} else {
				if ($index) {
					$body[] = $index."[".$key."]=".urlencode($val);
				} else {
					$body[] = $key."=".urlencode($val);
				}
			}
		}
		return implode("&", $body);
	}

	/**
	 * This function decrypts the XML data that's returned by the Authentication program.
	 * @return 
	 * @param object $cipher_data
	 * @param object $key
	 */
	private function decryptData($cipher_data, $key) {
		switch ($this->encryptionMethod) {
			case "rijndael" :
				return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, base64_decode($cipher_data), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));
			break;
			case "blowfish" :
				return trim(mcrypt_decrypt(MCRYPT_BLOWFISH, $key, base64_decode($cipher_data), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB), MCRYPT_RAND)));
			break;
			case "default" :
			default :
				$todec = array("A" => 0, "B" => 1, "C" => 2, "D" => 3, "E" => 4, "F" => 5, "G" => 6, "H" => 7, "I" => 8, "J" => 9, "K" => 10, "L" => 11, "M" => 12, "N" => 13, "O" => 14, "P" => 15, "Q" => 16, "R" => 17, "S" => 18, "T" => 19, "U" => 20, "V" => 21, "W" => 22, "X" => 23, "Y" => 24, "Z" => 25, "a" => 26, "b" => 27, "c" => 28, "d" => 29, "e" => 30, "f" => 31, "g" => 32, "h" => 33, "i" => 34, "j" => 35, "k" => 36, "l" => 37, "m" => 38, "n" => 39, "o" => 40, "p" => 41, "q" => 42, "r" => 43, "s" => 44, "t" => 45, "u" => 46, "v" => 47, "w" => 48, "x" => 49, "y" => 50, "z" => 51, "0" => 52, "1" => 53, "2" => 54, "3" => 55, "4" => 56, "5" => 57, "6" => 58, "7" => 59, "8" => 60, "9" => 61, "+" => 62, "/" => 63, "=" => 64);
				$keyl = strlen($key);
				$m = 0;
				$all_bin_chars = "";
				$plain_data	= "";
				
				for ($i = 0; $i < strlen($cipher_data); $i++) {
					$c = $cipher_data[$i];
					$decimal_value=($todec[$c] - $m) >> 2;
					$four_bit=decbin($decimal_value);
					while (strlen($four_bit) < 4) {
						$four_bit="0".$four_bit;
					}
					$all_bin_chars .= $four_bit;
					if (++$m > 3) {
						$m = 0;
					}
				}
				
				$key_length	= 0;

				for ($j = 0; $j < strlen($all_bin_chars); $j = $j + 8) {
					$c = substr($all_bin_chars, $j, 8);
					$k = $key[$key_length];
					$dec_chars = bindec($c);
					$dec_chars = $dec_chars - $keyl;
					$c = chr($dec_chars);
					$key_length++;
					if ($key_length >= $keyl) {
						$key_length = 0;
					}
					$dec_chars = ord($c)^ord($k);
					$p = chr($dec_chars);
					$plain_data .= $p;
				}
				
				return trim($plain_data);
			break;
		}
	}

	/**
	 * This function processes and sends the request to the auth.
	 * @return 
	 */
	private function processRequest() {
		$responseBody = "";
		$element = array();
		$ttags = array();
		$tags = array();

		$this->data["enc_method"] = $this->encryptionMethod;

		$requestBody = $this->prepareRequest($this->data);

		if ($this->httpAuthInfo) {
			$auth = base64_encode("{".$this->httpAuthInfo[0]."}:{".$this->httpAuthInfo[1]."}");
		}

		$contentLength = strlen($requestBody);

		$request = "POST ".$this->connection["uri"]." HTTP/1.0\r\nHost: ".$this->connection["url"]."\r\nUser-Agent: EntradaAuth[".$this->version."]\r\nContent-Type: application/x-www-form-urlencoded\r\n".(($this->httpAuthInfo) ? "Authorization: Basic ".$auth."\r\n" : "")."Content-Length: ".$contentLength."\r\n\r\n".$requestBody."\r\n";
		$socket = @fsockopen((($this->connection["port"] == 443) ? "ssl://" : "").$this->connection["url"], $this->connection["port"], $errno, $errstr, $this->connection["timeout"]);
		if (!$socket) {
			return array("STATUS" => "failed", "MESSAGE" => "Unable to connect to ".(($this->connection["port"] == 443) ? "ssl://" : "").$this->connection["url"]." on port ".$this->connection["port"].". The server may be down or blocked by a firewall?");
		}

		fputs($socket, $request);

		$result = "";
		while (!feof($socket)) {
			$result .= fread($socket, 1024);
		}
		$lines = explode("\r\n", $result);
		foreach ($lines as $key => $line) {
			if (trim($line) == "") {
				for ($i = $key + 1; $i <= count($lines); $i++) {
					$responseBody .= ((isset($lines[$i])) ? $lines[$i] : "");
				}
			}
		}
		fclose($socket);

		$parser = xml_parser_create();
		xml_parse_into_struct($parser, $responseBody, $vals, $index) or die(xml_error_string(xml_get_error_code($parser)));
		xml_parser_free($parser);

		for ($n = 0; $n <= count($vals)-1; $n++) {
			if (isset($vals[$n]["value"]) && trim($vals[$n]["value"])) {
				$element[$vals[$n]["tag"]][(isset($element[$vals[$n]["tag"]]) && is_array($element[$vals[$n]["tag"]]) ? count($element[$vals[$n]["tag"]]) : 0)] = $vals[$n]["value"];
			}
		}

		foreach ($element as $key => $value) {
			$this->xml_array[$key] = $this->decryptData($value[0], $this->data["auth_password"]);
		}

		return $this->xml_array;
	}
}