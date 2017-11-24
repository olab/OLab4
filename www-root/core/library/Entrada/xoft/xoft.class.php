<?php

/************************************************************************
 +----------------------------------------------------------------------+
 |   Xoft.class.php -> Xoft Encoding Class     		                |
 +----------------------------------------------------------------------+  						        |	
 |								        |
 | (c) 2003 by M.Abdullah Khaidar (khaidarmak@yahoo.com)                |
 |								        |	
 | This program is free software. You can redistribute it and/or modify |
 | it under the terms of the GNU General Public License as published by |
 | the Free Software Foundation; either version 2 of the License.       |
 |                                                                      |
 +----------------------------------------------------------------------+
 ************************************************************************/ 

/**
 * A Xoft class represents xoft encoding
 * 
 * @author	M. Abdullah Khaidar
 * @author	Armand Turpel armand@a-tu.net (speed improvements)
 */
class Xoft
{

	/**
	 * @var	string
	 */
	var $_input;

	/**
	 * @var	string
	 */
	var $_output;

	/**
	 * @var	string
	 */
	var $_key;
	
	/**
	 * @var	string
	 */
	//array to convert decimal value into base64 value 
	var $_tob64 = array(
	    "A",
	    "B",
	    "C",
	    "D",
	    "E",
	    "F",
	    "G",
	    "H",
	    "I",
	    "J",
	    "K",
	    "L",
	    "M",
	    "N",
	    "O",
	    "P",
	    "Q",
	    "R",
	    "S",
	    "T",
	    "U",
	    "V",
	    "W",
	    "X",
	    "Y",
	    "Z",
	    "a",
	    "b",
	    "c",
	    "d",
	    "e",
	    "f",
	    "g",
	    "h",
	    "i",
	    "j",
	    "k",
	    "l",
	    "m",
	    "n",
	    "o",
	    "p",
	    "q",
	    "r",
	    "s",
	    "t",
	    "u",
	    "v",
	    "w",
	    "x",
	    "y",
	    "z",
	    "0",
	    "1",
	    "2",
	    "3",
	    "4",
	    "5",
	    "6",
	    "7",
	    "8",
	    "9",
	    "+",
	    "/",
	    "="
	    );
	   
	/**
	 * @var	string
	 */	
        // array to convert base64 value into decimal value
	var $_todec = array(
	    "A" => 0,
	    "B" => 1,
	    "C" => 2,
	    "D" => 3,
	    "E" => 4,
	    "F" => 5,
	    "G" => 6,
	    "H" => 7,
	    "I" => 8,
	    "J" => 9,
	    "K" => 10,
	    "L" => 11,
	    "M" => 12,
	    "N" => 13,
	    "O" => 14,
	    "P" => 15,
	    "Q" => 16,
	    "R" => 17,
	    "S" => 18,
	    "T" => 19,
	    "U" => 20,
	    "V" => 21,
	    "W" => 22,
	    "X" => 23,
	    "Y" => 24,
	    "Z" => 25,
	    "a" => 26,
	    "b" => 27,
	    "c" => 28,
	    "d" => 29,
	    "e" => 30,
	    "f" => 31,
	    "g" => 32,
	    "h" => 33,
	    "i" => 34,
	    "j" => 35,
	    "k" => 36,
	    "l" => 37,
	    "m" => 38,
	    "n" => 39,
	    "o" => 40,
	    "p" => 41,
	    "q" => 42,
	    "r" => 43,
	    "s" => 44,
	    "t" => 45,
	    "u" => 46,
	    "v" => 47,
	    "w" => 48,
	    "x" => 49,
	    "y" => 50,
	    "z" => 51,
	    "0" => 52,
	    "1" => 53,
	    "2" => 54,
	    "3" => 55,
	    "4" => 56,
	    "5" => 57,
	    "6" => 58,
	    "7" => 59,
	    "8" => 60,
	    "9" => 61,
	    "+" => 62,
	    "/" => 63,
	    "=" => 64
	    );

	/**
	 * Xoft encoding method
	 *
	 * @access	public
	 * @param	string	$plain_data
	 * @param	string	$key
	 */	
	function encode($plain_data,$key){
	    
	    $key_length = 0;
	    $keyl = strlen($key);
	    $all_bin_chars = "";
	    $cipher_data = "";
	    
	    for($i = 0; $i < strlen($plain_data); $i++){
	        $p = $plain_data[$i];
	        $k = $key[$key_length];
	        $key_length++;
	        if($key_length >= $keyl)
	            $key_length = 0;
	        $dec_chars = ord($p) ^ ord($k);
	        $dec_chars = $dec_chars + $keyl;
	        $bin_chars = decbin($dec_chars);
	        while(strlen($bin_chars) < 8)
	            $bin_chars = "0" . $bin_chars;
	            
	        $all_bin_chars .= $bin_chars;
	    }
	   
	    $m = 0;
	    for($j = 0; $j < strlen($all_bin_chars); $j = $j + 4){
	        $four_bit = substr($all_bin_chars,$j,4);
	        $four_bit_dec = bindec($four_bit);
	        $cipher_data .= $this->_tob64[($four_bit_dec << 2) + $m];
	        if(++$m > 3)
	            $m = 0;
	    }
	    
	    $this->_output = $cipher_data;
	} 	
	
	/**
	 * Xoft decoding method
	 *
	 * @access	public
	 * @param	string	$cipher_data
	 * @param	string	$key
	 */	
	function decode($cipher_data,$key){
	    
	    $keyl = strlen($key);
	    $m = 0;
	    $all_bin_chars = "";
	   
	    for($i = 0; $i < strlen($cipher_data); $i++){
	        $c = $cipher_data[$i];
	        $decimal_value = ($this->_todec[$c] - $m) >> 2;
	        $four_bit = decbin($decimal_value);
	        while(strlen($four_bit) < 4)
	            $four_bit = "0" . $four_bit;
	        $all_bin_chars .= $four_bit;
	        if(++$m > 3)
	            $m = 0;
	    }
	   
	    $key_length = 0;
	    $plain_data = "";
	   
	    for($j = 0; $j < strlen($all_bin_chars); $j = $j + 8){
	        $c = substr($all_bin_chars,$j,8);
	        $k = $key[$key_length];
	        $dec_chars = bindec($c);
	        $dec_chars = $dec_chars - $keyl;
	        $c = chr($dec_chars);
	        $key_length++;
	        if($key_length >= $keyl)
	            $key_length = 0;
	        $dec_chars = ord($c) ^ ord($k);
	        $p = chr($dec_chars);
	        $plain_data .= $p;
	    }
	    
	    $this->_output = $plain_data;
	}

	/**
	 * Get input text
	 *
	 * @access	public
	 * @return	string
	 */
	function getInput()
	{
		return $this->_input;
	}

	/**
	 * Get output text
	 *
	 * @access	public
	 * @return	string
	 */
	function getOutput()
	{
		return $this->_output;
	}

	/**
	 * Get the key
	 *
	 * @access	public
	 * @return	string
	 */
	function getKey()
	{
		return $this->_key;
	}

	/**
	 * Set input text
	 *
	 * @access	public
	 * @param	string	$plain
	 */
	function setInput($plain)
	{
		$this->_input = $plain;
	}

	/**
	 * Set output text
	 *
	 * @access	public
	 * @param	string	$cipher
	 */
	function setOutput($cipher)
	{
		$this->_output = $cipher;
	}

	/**
	 * Set the key
	 *
	 * @access	public
	 * @param	string	$key
	 */
	function setKey($key)
	{
		$this->_key = $key;
	}	

}		

?>