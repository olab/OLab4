<?php
/**
 * TextImage 1.0
 * @author Unit: Medical Education Technology Unit
 * @author Director: Dr. Benjamin Chen <bhc@post.queensu.ca>
 * @author Developer: Matt Simpson <simpson@post.queensu.ca>
 * @version 3.0
 * @copyright Copyright 2006 Queen's University, MEdTech Unit
 *
 * $Id: textimage.class.php 1 2008-07-11 20:11:41Z simpson $
*/

class Entrada_TextImage {
	var $message		= "na";				// Default text to display if no message is present.
	var $font			= "fonts/Vera.ttf";	// Default font. directory relative to script directory.
	var $size			= 12;				// Default font size to use if not specified.
	var $rotation		= 0;				// Rotation in degrees.
	var $padding		= 0;				// Padding surrounding the type.

	var $transparent	= false;			// Transparency set to on (true | false).

	var $red			= 255;				// White text (RGB value).
	var $green			= 255;
	var $blue			= 255;

	var $bg_red			= 0;				// On blue background (RGB value).
	var $bg_green		= 51;
	var $bg_blue		= 102;

	var $width			= 0;				// 0 = auto-set image width, otherwise, specify.
	var $height			= 0;				// 0 = auto-set image height, otherwise, specify.

	var $image_format	= "png";			// Type of image to output (gif, jpg, wbmp, png).

	/**
	 * Constructor function.
	 *
	 * @param string $message
	 * @param string $format (gif, jpg, wbmp, png)
	 * @param int $width
	 * @param int $height
	 * @return TextImage
	 */
	public function __construct($message = "", $format = "", $width = 0, $height = 0, $font = "", $rotation = 0, $padding = 0) {
		if($message != "") {
			$this->message = $message;
		}
        
		if($format = strtolower(trim($format)) != "") {
			if(@in_array($format, $this->_getSupportedImageTypes())) {
				$this->image_format = $format;
			}
		}

		if($width = (int) trim($width)) {
			$this->width = $width;
		}

		if($height = (int) trim($height)) {
			$this->height = $height;
		}

		if($rotation = (int) trim($rotation)) {
			$this->rotation = $rotation;
		}

		if($padding = (int) trim($padding)) {
			$this->padding = $padding;
		}
        
        if ($font) {
            $this->font = $font;
        }
	}

	/**
	 * Draw function will actually draw the image.
	 * @return Draw's image.
	 */
	public function draw() {
		$offset_x	= 0;
		$offset_y	= 0;

		$bounds	= array();
		$image	= "";

		// Determine font height.
		$bounds = ImageTTFBBox($this->size, $this->rotation, $this->font, "W");
		if ($this->rotation < 0) {
			$font_height = abs($bounds[7] - $bounds[1]);
		} else if ($this->rotation > 0) {
			$font_height = abs($bounds[1] - $bounds[7]);
		} else {
			$font_height = abs($bounds[7] - $bounds[1]);
		}

		// Determine bounding box.
		$bounds = ImageTTFBBox($this->size, $this->rotation, $this->font, $this->message);
		if ($this->rotation < 0) {
			if((!(int) $this->width) || (!(int) $this->height)) {
				$this->width	= abs($bounds[4] - $bounds[0]);
				$this->height	= abs($bounds[3] - $bounds[7]);
			}
			$offset_y = $font_height;
			$offset_x = 0;
		} else if ($this->rotation > 0) {
			if((!(int) $this->width) || (!(int) $this->height)) {
				$this->width	= abs($bounds[2] - $bounds[6]);
				$this->height	= abs($bounds[1] - $bounds[5]);
			}
			$offset_y = abs($bounds[7] - $bounds[5]) + $font_height;
			$offset_x = abs($bounds[0] - $bounds[6]);
		} else {
			if((!(int) $this->width) || (!(int) $this->height)) {
				$this->width	= abs($bounds[4] - $bounds[6]);
				$this->height	= abs($bounds[7] - $bounds[1]);
			}
			$offset_y = $font_height;
			$offset_x = 0;
		}

		$image		= imagecreate(($this->width + ($this->padding * 2) + 1), ($this->height + ($this->padding * 2) + 1));

		$background	= imagecolorallocate($image, $this->bg_red, $this->bg_green, $this->bg_blue);
		$foreground	= imagecolorallocate($image, $this->red, $this->green, $this->blue);

		if($this->transparent) {
			imagecolortransparent($image, $background);
		}

		imageinterlace($image, false);

		// Render it text.
		imagettftext($image, $this->size, $this->rotation, ($offset_x + $this->padding), ($offset_y + $this->padding), $foreground, $this->font, $this->message);

		switch($this->image_format) {
			case "jpg" :
				if(imagetypes() & IMG_JPG) {
					header("Content-type: image/jpg");
					imagejpeg($image, NULL, 60);
				} else {
					echo "Failure: JPEG format not supported by your PHP installation.";
					exit;
				}
			break;
			case "gif" :
				if(imagetypes() & IMG_GIF) {
					header("Content-type: image/gif");
					imagegif($image);
				} else {
					echo "Failure: GIF format not supported by your PHP installation.";
					exit;
				}
			break;
			case "wbmp" :
				if(imagetypes() & IMG_WBMP) {
					header("Content-type: image/vnd.wap.wbmp");
					imagewbmp($image);
				} else {
					echo "Failure: WBMP format not supported by your PHP installation.";
					exit;
				}
			break;
			case "png" :
			default :
				if(imagetypes() & IMG_PNG) {
					header("Content-type: image/png");
					imagepng($image);
				} else {
					echo "Failure: PNG format not supported by your PHP installation.";
					exit;
				}
			break;
		}
	}

	function _getSupportedImageTypes() {
		$aSupportedTypes		= array();
		$aPossibleImageTypeBits	= array(IMG_GIF => "gif", IMG_JPG => "jpg", IMG_PNG => "png", IMG_WBMP => "wbmp");

		foreach ($aPossibleImageTypeBits as $iImageTypeBits => $sImageTypeString) {
			if (imagetypes() & $iImageTypeBits) {
				$aSupportedTypes[] = $sImageTypeString;
			}
		}

		return $aSupportedTypes;
	}
}
?>