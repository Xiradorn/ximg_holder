<?php
/**
 * XImg-Holder
 * A project for my image placeholder. API for external generation
 * @package XImg-Holder
 * @author Sir Xiradorn <http://xiradorn.it>
 * @version 1.0.0
 * @license CC-BY-NC-ND-4.0
 */

namespace XImg;

interface XImgInterface {
	const GRAY = "aaaaaa";
	const WHITE = "ffffff";
	const COLOR_BORDER_RANGE = 20;
	public function rendering();
}

/**
 * class XImgClassBase
 * protected function for image gen
 * @author Sir Xiradorn
 */
abstract class XImgClassBase implements XImgInterface {
	public $width = 0;
	public $height = 0;
	public $font_size = 25;
	public $txt_opacity = 50;

	const WIDTH_HOLD = 300;
	const HEIGHT_HOLD = 100; 

	public $bgcolor = false;
	public $fgcolor = false;
	
	/**
	 * Function for validate image dimension
	 * @param int $w
	 * @param int $h
	 */
	protected function _XImgDimCalc($w = null, $h = null) {
		if (is_null($w) && is_null($h)) {
			$this->width = self::WIDTH_HOLD;
			$this->height = self::HEIGHT_HOLD;
		} else {
			if (!filter_var($w, FILTER_VALIDATE_INT) === false && !filter_var($h, FILTER_VALIDATE_INT) === false) {
				$this->width = filter_var($w, FILTER_SANITIZE_NUMBER_INT);
				$this->height = filter_var($h, FILTER_SANITIZE_NUMBER_INT);
			} else {
				$this->width = self::WIDTH_HOLD;
				$this->height = self::HEIGHT_HOLD;
			}
		}
	}
	
	/**
	 * Function for detect color
	 * @param string $fcolor
	 * @param string $bcolor
	 */
	protected function _XImgColorDef($fcolor = null, $bcolor = null) {
		if (is_null($fcolor) && is_null($bcolor)) {
			$this->bgcolor = self::GRAY;
			$this->fgcolor = self::WHITE;
		} else {
			if (is_null($fcolor) && !is_null($bcolor)) {
				$this->bgcolor = filter_var($bcolor, FILTER_SANITIZE_STRING);
				$this->fgcolor = self::WHITE;
			} elseif (!is_null($fcolor) && is_null($bcolor)) {
				$this->bgcolor = self::GRAY;
				$this->fgcolor = filter_var($fcolor, FILTER_SANITIZE_STRING);
			} else {
				$this->bgcolor = filter_var($bcolor, FILTER_SANITIZE_STRING);
				$this->fgcolor = filter_var($fcolor, FILTER_SANITIZE_STRING);
			}
		}
	}
	
	/**
	 * Convert hex color in rgb color
	 * @param string $hex_color
	 */
	protected function __colorHexToRGB($hex_color = null) {
		// check if is a real color string
		if (!is_null($hex_color)) {
			$hex_color = filter_var($hex_color, FILTER_SANITIZE_STRING);
		} else {
			$hex_color = self::GRAY;
		}
				
		if (strlen($hex_color) === 3) {
			$r = hexdec(substr($hex_color, 0, 1));
			$g = hexdec(substr($hex_color, 1, 1));
			$b = hexdec(substr($hex_color, 2, 1));
				
			// color driving
			$red = $r.$r;
			$green = $g.$g;
			$blue = $b.$b;
		} elseif (strlen($hex_color) === 6) {
			$red 	= hexdec(substr($hex_color, 0, 2));
			$green 	= hexdec(substr($hex_color, 2, 2));
			$blue 	= hexdec(substr($hex_color, 4, 2));
		}
		
		$rgb_ary = array("red", "green", "blue");
		$rgb = compact($rgb_ary);	
	
		return $rgb;
	}
	
	/**
	 * Function for image generation
	 * @param array $settings
	 */
	protected function _XImgGenerator(array $settings = array()) {
		try {
			/* Image generation start */
			// Grab parameters from GET query string
			if (isset($settings['width']) && !isset($settings['height'])) {
				$this->_XImgDimCalc($settings['width'], $settings['width']);
			} elseif (!isset($settings['width']) && isset($settings['height'])) {
				$this->_XImgDimCalc($settings['height'], $settings['height']);
			} elseif (isset($settings['width']) && isset($settings['height'])){
				$this->_XImgDimCalc($settings['width'], $settings['height']);
			} else {
				$this->_XImgDimCalc();
			}
			
			if (isset($settings['color'])) {
				$colorImg = $this->__colorHexToRGB($settings['color']);
			} else {
				$colorImg = $this->__colorHexToRGB();
			}
			
			// Image true color created
			$im = @imagecreatetruecolor($this->width, $this->height);
			
			// Image colore allocation for background
			$backgroundColor = @imagecolorallocate($im, $colorImg['red'], $colorImg['green'], $colorImg['blue']);
			
			/* Creation & Fix for Border */
			foreach ($colorImg as $clr => $clr_val ) {
				if (($clr_val - self::COLOR_BORDER_RANGE) < 0) {
					$borderCol[$clr] = 0;
				} else {
					$borderCol[$clr] = $clr_val - self::COLOR_BORDER_RANGE;
				}
			}
			$borderColor = @imagecolorallocate($im, $borderCol['red'], $borderCol['green'], $borderCol['blue']);
			
			// first fill for image
			imagefill($im, 0, 0, $borderColor);
			
			// creation and fill for border. 
			// Really is an indirect way to do this
			if (isset($settings['border'])) {
				$b = $settings['border'];
				imagefilledrectangle($im, $b, $b, $this->width-$b-1, $this->height-$b-1, $backgroundColor);
			} else {
				imagefilledrectangle($im, 0, 0, $this->width, $this->height, $backgroundColor);
			}
			
			// Text Generation in image if exist
			if (isset($settings['text']) && $settings != "") {
				$text = str_replace("-", " ", str_replace("_", "", filter_var($settings['text'], FILTER_SANITIZE_STRING)));
				
				$textColor = $this->__colorHexToRGB(self::WHITE);

				if (isset($settings['opacity'])) {
					$opacity = filter_var($settings['opacity'], FILTER_SANITIZE_NUMBER_INT);

					if ($opacity < 0) {
						$opacity = 0;
					} elseif ($opacity > 127) {
						$opacity = 127;
					}
					$this->opacity = $opacity;
				}
				$textColor = @imagecolorallocatealpha($im, $textColor['red'], $textColor['green'], $textColor['blue'], $this->opacity);
				
				// better font charge
				$font = "font/Roboto-Light.ttf";
				
				/* Create new image text */

				// create a bounding box for twxt width autocalc dimension
				if (isset($settings['font_size'])) {
					$this->font_size = filter_var($settings['font_size'], FILTER_SANITIZE_NUMBER_INT);
				}
				$textBbox = @imagettfbbox($this->font_size, 0, $font, $text);
				$bbox_width = abs($textBbox[4]);
				$bbox_height = abs($textBbox[5]);

				// image resource for text
				// $bbox_width+$this->font_size, $bbox_height+$this->font_size used for text fix for g y e similar letter
				$txt_im = @imagecreatetruecolor($bbox_width + $this->font_size, $bbox_height + $this->font_size);
				@imagefilledrectangle($txt_im, 0, 0, $bbox_width + $this->font_size, $bbox_height + $this->font_size, $backgroundColor);
				// tramsparentize bg
				@imagecolortransparent($txt_im, $backgroundColor);
				@imagettftext($txt_im, $this->font_size, 0, 0, $bbox_height, $textColor, $font, $text);

				// merging two image
				@imagealphablending($txt_im, false);
				@imagesavealpha($txt_im, true);
				
				// build final image
				$x_center = imagesx($im) / 2 - $bbox_width / 2;
				$y_center = imagesy($im) / 2 - $bbox_height / 2;
				imagecopymerge($im, $txt_im, $x_center, $y_center, 0, 0, imagesx($txt_im), imagesy($txt_im), 50);
			}

			// build image resources and destroy
			header("Content-Type: image/png");
			@imagepng($im);
			@imagedestroy($im);
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}
}

final class XImgClass extends XImgClassBase {
	/**
	 * Function for external purpose
	 * @param array $param
	 * @return array $value
	 */
	private function __query_string_splitter(array $param = array()) {
		/* image Gen Recall and destroy */
		//$this->_XImgGenerator($param);
		$query_string_ary = explode("/", $param['query_string']);
		
		foreach ($query_string_ary as $q) {
			// extractiong key and convert to effective key string
			$key = strtolower(substr($q, 0, 1));
			
			switch ($key) {
				case "c":
					$key = "color";
				break;
				case "h":
					$key = "height";
				break;
				case "w":
					$key = "width";
				break;
				case "t":
					$key = "text";
				break;
				case "b":
					$key = "border";
				break;
				case "f":
					$key = "font_size";
				break;
				case "o":
					$key = "opacity";
				break;
				default:
					return;
				break;
			}
			
			$value[$key] = substr($q, 1);
		}
		
		return $value;
	}
	
	public function rendering(array $param = array()) {
		if (!isset($param) || empty($param)) {
			$param['query_string'] = "w" . self::WIDTH_HOLD . "/h" . self::HEIGHT_HOLD;
		}
			
		$param_new = $this->__query_string_splitter($param);
		
		$this->_XImgGenerator($param_new);
	}
}