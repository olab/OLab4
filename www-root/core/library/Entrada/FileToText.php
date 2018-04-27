<?php

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *
 * FileDump provides a set of helper methods for managing file extraction and
 * convertion to retrieve the contents of a file as an unformated plain text
 * string. It is an ideal tool for making files searchable. The current most
 * common file types are supported: pdf, doc, docx, ppt, and pptx.
 *
 * This code is a conglomeration of serveral works brought together into a
 * single source for convenience. Original sources can be found at:
 * http://www.phpclasses.org/package/6155-PHP-Extract-text-from-PDF-files.html
 * http://www.webcheatsheet.com/php/reading_clean_text_from_pdf.php
 * http://www.phpclasses.org/browse/file/44010.html
 * http://project.ksigma.com/node/43
 *
 * @author Webcheatsheet.com
 * 		   - Original PDF conversion code
 * @author Joeri Stegeman <joeri210@yahoo.com>
 * 		   - PDF class conversion and fixes/adjustments
 * @author ksigma.com
 * 		   - Original PPT conversion code
 * @author Gourav Mehta   <gouravmehta@gmail.com>
 * 		   - Original DOC/DOCX conversion code
 * @author Scott Steil    <sasteil@ucalgary.ca>
 * 		   - Integration, clean up, and bug fixes
 *
 */
class Entrada_FileToText
{
	public static $multibyte = 2; 			 	// 2 (UTF8) or 4 (ISO)
	public static $convertquotes = ENT_QUOTES; 	// ENT_COMPAT (double-quotes), ENT_QUOTES (Both), ENT_NOQUOTES (None)


	/**
	 * Takes a file and translates it to plain text. Returns an empty string if
	 * an error occured or the file type was not supported.
	 *
	 * @param 	filename	The full path to a file to convert to text
	 * @return	The file's contents converted to plain text
	 *
	 * @author	Scott Steil <sasteil@ucalgary.ca>
	 */
	public static function decode($filename = null, $extension = null)
	{
		if (!$extension)
		{
			$parts = pathinfo($filename);
			$extension = $parts['extension'];
		}

		switch(strtolower($extension))
		{
			case 'doc':
				return Entrada_FileToText::decodeDOC($filename);
            break;
			case 'docx':
				return Entrada_FileToText::decodeDOCX($filename);
            break;
			case 'ppt':
				return Entrada_FileToText::decodePPT($filename);
            break;
			case 'pptx':
				return Entrada_FileToText::decodePPTX($filename);
            break;
			case 'pdf':
                $parser = new \Smalot\PdfParser\Parser();
                $pdf    = $parser->parseFile($filename);

                return $pdf->getText();
				//return Entrada_FileToText::decodePDF($filename);
            break;
			case 'htm':
			case 'html':
				return Entrada_FileToText::decodeHTML($filename);
            break;
			case 'txt':
				return Entrada_FileToText::decodeTXT($filename);
            break;
			default:
				// file type not supported
                continue;
            break;
		}

		return '';
	}


	/**
	 * Extracts data from a Microsoft Word 97-2004 Document file and manages its
	 * translation to plain text. It's really not very good and leaves a lot of garbage
	 * in the plain text.
	 *
	 * @param 	filename	The full path to the target DOC file
	 * @return	The data converted to plain text
	 *
	 * @author 	Gourav Mehta <gouravmehta@gmail.com>
	 * @author	Scott Steil  <sasteil@ucalgary.ca>
	 */
	public static function decodeDOC($filename)
	{
		$fileHandle = fopen($filename, "r");
		$line = @fread($fileHandle, filesize($filename));
		$lines = explode(chr(0x0D), $line);
		$outtext = "";

		foreach($lines as $thisline)
		{
			if (strlen($thisline) == 0)
				continue;

			$pos = strpos($thisline, chr(0x00));

			if ($pos == FALSE)
			{
				$outtext .= $thisline." ";
			}
		}

		return preg_replace("/[^a-zA-Z0-9\s\,\.\-\n\r\t@\/\_\(\)]/", "", $outtext);
	}


	/**
	 * Extracts data from a Microsoft Word Document file and manages its
	 * translation to plain text.
	 *
	 * @param 	filename	The full path to the target DOCX file
	 * @return	The data converted to plain text
	 *
	 * @author 	Gourav Mehta <gouravmehta@gmail.com>
	 * @author	Scott Steil  <sasteil@ucalgary.ca>
	 */
	public static function decodeDOCX($filename)
	{
		$content = '';
		$zip = zip_open($filename);

		if (!$zip || is_numeric($zip))
			return '';

		while ($zip_entry = zip_read($zip))
		{
			if (zip_entry_open($zip, $zip_entry) == FALSE)
				continue;

			if (zip_entry_name($zip_entry) != "word/document.xml")
				continue;

			$content .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

			zip_entry_close($zip_entry);
		}

		zip_close($zip);

		$content = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $content);
		$content = str_replace('</w:r></w:p>', "\r\n", $content);

		return strip_tags($content);
	}


	/**
	 * Extracts data from a Microsoft Powerpoint 97-2004 Presentation file and
	 * manages its translation to plain text. This approach uses detection of the string
	 * "chr(0f).Hex_value.chr(0x00).chr(0x00).chr(0x00)" to find text strings, which are
	 * then terminated by another NUL chr(0x00). [1] Get text between delimiters [2]
	 *
	 * @param 	filename	The full path to the target PPT file
	 * @return	The data converted to plain text
	 *
	 * @author 	ksigma.com
	 * @author	Scott Steil <sasteil@ucalgary.ca>
	 */
	public static function decodePPT($filename)
	{
		$fileHandle = fopen($filename, "r");
		$line = @fread($fileHandle, filesize($filename));
		$lines = explode(chr(0x0f),$line);
		$outtext = '';

		foreach($lines as $thisline)
		{
			if (strpos($thisline, chr(0x00).chr(0x00).chr(0x00)) == 1)
			{
				$text_line = substr($thisline, 4);
				$end_pos   = strpos($text_line, chr(0x00));
				$text_line = substr($text_line, 0, $end_pos);
				$text_line = preg_replace("/[^a-zA-Z0-9\s\,\.\-\n\r\t@\/\_\(\)]/","",$text_line);

				if (strlen($text_line) > 1)
				{
					$outtext.= substr($text_line, 0, $end_pos)."\n";
				}
			}
		}

		return $outtext;
	}


	/**
	 * Extracts data from a Microsoft Powerpoint Presentation file and manages
	 * its translation to plain text.
	 *
	 * @param 	filename	The full path to the target PPTX file
	 * @return	The data converted to plain text
	 *
	 * @author 	Gourav Mehta <gouravmehta@gmail.com>
	 * @author	Scott Steil  <sasteil@ucalgary.ca>
	 */
	public static function decodePPTX($filename)
	{
		$content = '';
		$zip = zip_open($filename);

		if (!$zip || is_numeric($zip))
			return '';

		while ($zip_entry = zip_read($zip))
		{
			if (zip_entry_open($zip, $zip_entry) == FALSE)
				continue;

			if (strpos(zip_entry_name($zip_entry), "ppt/slides/") === FALSE)
				continue;

			$content .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

			zip_entry_close($zip_entry);
		}

		zip_close($zip);

		$content = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $content);
		$content = str_replace('</w:r></w:p>', "\r\n", $content);

		return strip_tags($content);
	}


	/**
	 * Convert HTML to plain text by removing HTML tags
	 * 
	 * @param 	filename	The full path to the target HTML file
	 * @return	The data converted to plain text
	 *
	 * @author	Scott Steil <sasteil@ucalgary.ca>
	 * @author	Doug Hall   <hall@ucalgary.ca>
	 */
	public static function decodeHTML($filename)
	{
		$outtext = "";

		$fileHandle = fopen($filename, "r");

		if (!$fileHandle) {
			return '';
		}

		while (!feof($fileHandle)) {
			$outtext .= fgets($fileHandle, 4096);
		}

		fclose($fileHandle);
		
		return Entrada_FileToText::strip_tags_content($outtext);
	}

	/**
	 * Clean plain text by removing exess space
	 * 
	 * @param 	filename	The full path to the target HTML file
	 * @return	The data converted to plain text
	 *
	 * @author	Scott Steil <sasteil@ucalgary.ca>
	 * @author	Doug Hall   <hall@ucalgary.ca>
	 */
	public static function decodeTXT($filename)
	{
		$outtext = "";

		$fileHandle = fopen($filename, "r");

		if (!$fileHandle) {
			return '';
		}

		while (!feof($fileHandle)) {
			$outtext .= fgets($fileHandle, 4096);
		}

		fclose($fileHandle);

		// ----- remove control characters -----
		$outtext = str_replace("\r", '', $outtext);    // --- replace with empty space
		$outtext = str_replace("\n", ' ', $outtext);   // --- replace with space
		$outtext = str_replace("\t", ' ', $outtext);   // --- replace with space
	   
		// ----- remove multiple spaces -----
		return trim(preg_replace('/ {2,}/', ' ', $outtext));
	}

	/**
	 * Extracts data from PDF file and manages its translation to plain text.
	 *
	 * @param 	filename	The full path to the target PDF file
	 * @return	The data converted to plain text
	 *
	 * @author 	Webcheatsheet.com
 	 * @author 	Joeri Stegeman 	<joeri210@yahoo.com>
 	 * @author	Scott Steil 	<sasteil@ucalgary.ca>
	 */
	public static function decodePDF($filename)
	{
		// Read the data from pdf file
		$infile = @file_get_contents($filename, FILE_BINARY);
		if (empty($infile))
			return "";

		// Get all text data.
		$transformations = array();
		$texts = array();

		// Get the list of all objects.
		preg_match_all("#obj[\n|\r](.*)endobj[\n|\r]#ismU", $infile, $objects);
		$objects = @$objects[1];

		// Select objects with streams.
		for ($i = 0; $i < count($objects); $i++)
		{
			$currentObject = $objects[$i];

			// Check if an object includes data stream.
			if (preg_match("#stream[\n|\r](.*)endstream[\n|\r]#ismU", $currentObject, $stream))
			{
				$stream = ltrim($stream[1]);

				// Check object parameters and look for text data.
				$options = Entrada_FileToText::getObjectOptions($currentObject);

				if (!(empty($options["Length1"]) && empty($options["Type"]) && empty($options["Subtype"])))
					continue;

				// Hack, length doesnt always seem to be correct
				unset($options["Length"]);

				// So, we have text data. Decode it.
				$data = Entrada_FileToText::getDecodedStream($stream, $options);

				if (strlen($data))
				{
	                if (preg_match_all("#BT[\n|\r](.*)ET[\n|\r]#ismU", $data, $textContainers))
	                {
						$textContainers = @$textContainers[1];
						Entrada_FileToText::getDirtyTexts($texts, $textContainers);
					}
					else
					{
						Entrada_FileToText::getCharTransformations($transformations, $data);
					}
				}
			}
		}

		// Analyze text blocks taking into account character transformations
		return Entrada_FileToText::getTextUsingTransformations($texts, $transformations);
	}



	////////////////////////////////////////////////////////////////////////////
	// The following methods are a set of decoders and helpers methods for    //
	// converting PDF files to text.										  //
	////////////////////////////////////////////////////////////////////////////

	private static function decodeAsciiHex($input)
	{
		$output = "";

		$isOdd = true;
		$isComment = false;

		for($i = 0, $codeHigh = -1; $i < strlen($input) && $input[$i] != '>'; $i++)
		{
			$c = $input[$i];

			if($isComment)
			{
				if ($c == '\r' || $c == '\n')
					$isComment = false;

				continue;
			}

			switch($c)
			{
				case '\0': case '\t': case '\r': case '\f': case '\n': case ' ':
					break;

				case '%':
					$isComment = true;
					break;

				default:
					$code = hexdec($c);
					if($code === 0 && $c != '0')
						return "";

					if($isOdd)
						$codeHigh = $code;
					else
						$output .= chr($codeHigh * 16 + $code);

					$isOdd = !$isOdd;
					break;
			}
		}

		if($input[$i] != '>')
			return "";

		if($isOdd)
			$output .= chr($codeHigh * 16);

		return $output;
	}


	private static function decodeAscii85($input)
	{
		$output = "";

		$isComment = false;
		$ords = array();

		for($i = 0, $state = 0; $i < strlen($input) && $input[$i] != '~'; $i++)
		{
			$c = $input[$i];

			if($isComment)
			{
				if ($c == '\r' || $c == '\n')
					$isComment = false;

				continue;
			}

			if ($c == '\0' || $c == '\t' || $c == '\r' || $c == '\f' || $c == '\n' || $c == ' ')
				continue;

			if ($c == '%')
			{
				$isComment = true;
				continue;
			}

			if ($c == 'z' && $state === 0)
			{
				$output .= str_repeat(chr(0), 4);
				continue;
			}

			if ($c < '!' || $c > 'u')
				return "";

			$code = ord($input[$i]) & 0xff;
			$ords[$state++] = $code - ord('!');

			if ($state == 5)
			{
				$state = 0;
				for ($sum = 0, $j = 0; $j < 5; $j++)
					$sum = $sum * 85 + $ords[$j];
				for ($j = 3; $j >= 0; $j--)
					$output .= chr($sum >> ($j * 8));
			}
		}

		if ($state === 1)
		{
			return "";
		}

		elseif ($state > 1)
		{
			for ($i = 0, $sum = 0; $i < $state; $i++)
				$sum += ($ords[$i] + ($i == $state - 1)) * pow(85, 4 - $i);
			for ($i = 0; $i < $state - 1; $i++)
				$ouput .= chr($sum >> ((3 - $i) * 8));
		}

		return $output;
	}


	private static function decodeFlate($input)
	{
		return gzuncompress($input);
	}


	private static function getObjectOptions($object)
	{
		$options = array();

		if (preg_match("#<<(.*)>>#ismU", $object, $options))
		{
			$options = explode("/", $options[1]);
			@array_shift($options);

			$o = array();
			for ($j = 0; $j < @count($options); $j++)
			{
				$options[$j] = preg_replace("#\s+#", " ", trim($options[$j]));

				if (strpos($options[$j], " ") !== false)
				{
					$parts = explode(" ", $options[$j]);
					$o[$parts[0]] = $parts[1];
				}
				else
				{
					$o[$options[$j]] = true;
				}
			}

			$options = $o;
			unset($o);
		}

		return $options;
	}


	private static function getDecodedStream($stream, $options)
	{
		$data = "";

		if (empty($options["Filter"]))
		{
			$data = $stream;
		}

		else
		{
			$length = !empty($options["Length"]) ? $options["Length"] : strlen($stream);
			$_stream = substr($stream, 0, $length);

			foreach ($options as $key => $value)
			{
				if ($key == "ASCIIHexDecode")
					$_stream = Entrada_FileToText::decodeAsciiHex($_stream);
				if ($key == "ASCII85Decode")
					$_stream = Entrada_FileToText::decodeAscii85($_stream);
				if ($key == "FlateDecode")
					$_stream = Entrada_FileToText::decodeFlate($_stream);
				if ($key == "Crypt") {} // TO DO
			}

			$data = $_stream;
		}

		return $data;
	}


	private static function getDirtyTexts(&$texts, $textContainers)
	{
		for ($j = 0; $j < count($textContainers); $j++)
		{
			if (preg_match_all("#\[(.*)\]\s*TJ[\n|\r]#ismU", $textContainers[$j], $parts))
				$texts = array_merge($texts, @$parts[1]);
			elseif(preg_match_all("#T[d|w|m|f]\s*(\(.*\))\s*Tj[\n|\r]#ismU", $textContainers[$j], $parts))
				$texts = array_merge($texts, @$parts[1]);
			elseif(preg_match_all("#T[d|w|m|f]\s*(\[.*\])\s*Tj[\n|\r]#ismU", $textContainers[$j], $parts))
				$texts = array_merge($texts, @$parts[1]);
		}
	}


	private static function getCharTransformations(&$transformations, $stream)
	{
		preg_match_all("#([0-9]+)\s+beginbfchar(.*)endbfchar#ismU", $stream, $chars, PREG_SET_ORDER);
		preg_match_all("#([0-9]+)\s+beginbfrange(.*)endbfrange#ismU", $stream, $ranges, PREG_SET_ORDER);

		for ($j = 0; $j < count($chars); $j++)
		{
			$count = $chars[$j][1];
			$current = explode("\n", trim($chars[$j][2]));
			for ($k = 0; $k < $count && $k < count($current); $k++)
			{
				if (preg_match("#<([0-9a-f]{2,4})>\s+<([0-9a-f]{4,512})>#is", trim($current[$k]), $map))
					$transformations[str_pad($map[1], 4, "0")] = $map[2];
			}
		}

		for ($j = 0; $j < count($ranges); $j++)
		{
			$count = $ranges[$j][1];
			$current = explode("\n", trim($ranges[$j][2]));
			for ($k = 0; $k < $count && $k < count($current); $k++)
			{
				if (preg_match("#<([0-9a-f]{4})>\s+<([0-9a-f]{4})>\s+<([0-9a-f]{4})>#is", trim($current[$k]), $map))
				{
					$from = hexdec($map[1]);
					$to = hexdec($map[2]);
					$_from = hexdec($map[3]);

					for ($m = $from, $n = 0; $m <= $to; $m++, $n++)
						$transformations[sprintf("%04X", $m)] = sprintf("%04X", $_from + $n);
				}

				elseif (preg_match("#<([0-9a-f]{4})>\s+<([0-9a-f]{4})>\s+\[(.*)\]#ismU", trim($current[$k]), $map))
				{
					$from = hexdec($map[1]);
					$to = hexdec($map[2]);
					$parts = preg_split("#\s+#", trim($map[3]));

					for ($m = $from, $n = 0; $m <= $to && $n < count($parts); $m++, $n++)
						$transformations[sprintf("%04X", $m)] = sprintf("%04X", hexdec($parts[$n]));
				}
			}
		}
	}


	private static function getTextUsingTransformations($texts, $transformations)
	{
		$document = "";
		for ($i = 0; $i < count($texts); $i++)
		{
			$isHex = false;
			$isPlain = false;

			$hex = "";
			$plain = "";
			for ($j = 0; $j < strlen($texts[$i]); $j++)
			{
				$c = $texts[$i][$j];
				switch($c)
				{
					case "<":
						$hex = "";
						$isHex = true;
						break;

					case ">":
						$hexs = str_split($hex, Entrada_FileToText::$multibyte); // 2 or 4 (UTF8 or ISO)
						for ($k = 0; $k < count($hexs); $k++)
						{
							$chex = str_pad($hexs[$k], 4, "0"); // Add tailing zero
							if (isset($transformations[$chex]))
								$chex = $transformations[$chex];
							$document .= html_entity_decode("&#x".$chex.";");
						}
						$isHex = false;
						break;

					case "(":
						$plain = "";
						$isPlain = true;
						break;

					case ")":
						$document .= $plain;
						$isPlain = false;
						break;

					case "\\":
						$c2 = $texts[$i][$j + 1];
						if (in_array($c2, array("\\", "(", ")"))) $plain .= $c2;
						elseif ($c2 == "n") $plain .= '\n';
						elseif ($c2 == "r") $plain .= '\r';
						elseif ($c2 == "t") $plain .= '\t';
						elseif ($c2 == "b") $plain .= '\b';
						elseif ($c2 == "f") $plain .= '\f';
						elseif ($c2 >= '0' && $c2 <= '9')
						{
							$oct = preg_replace("#[^0-9]#", "", substr($texts[$i], $j + 1, 3));
							$j += strlen($oct) - 1;
							$plain .= html_entity_decode("&#".octdec($oct).";", Entrada_FileToText::$convertquotes);
						}
						$j++;
						break;

					default:
						if ($isHex)
							$hex .= $c;
						if ($isPlain)
							$plain .= $c;
				}
			}
			$document .= "\n";
		}

		return $document;
	}
	
	private static function strip_tags_content($text, $allowed_tags = '')
    {
        mb_regex_encoding('UTF-8');
        //replace MS special characters first
        $search = array('/&lsquo;/u', '/&rsquo;/u', '/&ldquo;/u', '/&rdquo;/u', '/&mdash;/u');
        $replace = array('\'', '\'', '"', '"', '-');
        $text = preg_replace($search, $replace, $text);
        //make sure _all_ html entities are converted to the plain ascii equivalents - it appears
        //in some MS headers, some html entities are encoded and some aren't
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        //try to strip out any C style comments first, since these, embedded in html comments, seem to
        //prevent strip_tags from removing html comments (MS Word introduced combination)
        if(mb_stripos($text, '/*') !== FALSE){
            $text = mb_eregi_replace('#/\*.*?\*/#s', '', $text, 'm');
        }
        //introduce a space into any arithmetic expressions that could be caught by strip_tags so that they won't be
        //'<1' becomes '< 1'(note: somewhat application specific)
        $text = preg_replace(array('/<([0-9]+)/'), array('< $1'), $text);
        $text = strip_tags($text, $allowed_tags);
        //eliminate extraneous whitespace from start and end of line, or anywhere there are two or more spaces, convert it to one
        $text = preg_replace(array('/^\s\s+/', '/\s\s+$/', '/\s\s+/u'), array('', '', ' '), $text);
        //strip out inline css and simplify style tags
        $search = array('#<(strong|b)[^>]*>(.*?)</(strong|b)>#isu', '#<(em|i)[^>]*>(.*?)</(em|i)>#isu', '#<u[^>]*>(.*?)</u>#isu');
        $replace = array('<b>$2</b>', '<i>$2</i>', '<u>$1</u>');
        $text = preg_replace($search, $replace, $text);
        //on some of the ?newer MS Word exports, where you get conditionals of the form 'if gte mso 9', etc., it appears
        //that whatever is in one of the html comments prevents strip_tags from eradicating the html comment that contains
        //some MS Style Definitions - this last bit gets rid of any leftover comments */
        $num_matches = preg_match_all("/\<!--/u", $text, $matches);
        if($num_matches){
              $text = preg_replace('/\<!--(.)*--\>/isu', '', $text);
        }

		$text = preg_replace("/[^a-zA-Z0-9\s\,\.\-\n\r\t@\/\_\(\)]/", "", $text);
		$text = trim(preg_replace('/ {2,}/', ' ', $text));
        return $text; 
	}
}
?>