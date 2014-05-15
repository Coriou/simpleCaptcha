<?php
	
	# Released under MIT license
	# https://github.com/coriou

	/** 
		ToDo:
			- Ability to use random fonts from a fonts folder
			- Ability to make each letter of a random colour / font
			- Exception handling
			- Better readability when using transparent background (PNG layers - how ?) 
			- Better random noise (faster generation of random coordinates + random colours)
			- Do we need to destroy the image in case we don't print it ?
			- Support for other font types (only supports TrueType fonts right now)
	**/

	error_reporting(E_ALL ^ E_NOTICE);

	class captcha
	{
		private $image = null;
		private $textColour = null;
		private $backgroundColour = null;
		private $noiseColour = null;
		private $transparentColour = null;
		private $transparentBackground = null;
		private $str = null;
		private $noise = null;
		private $noiseType = null;
		private $randomAngles = null;
		private $randomNoise = null;

		public function __construct($settings)
		{
			$this->processSettings($settings);
		}

		public function generateCaptcha()
		{
			# We generate the image
			$this->image = imagecreate($this->width, $this->height);

			# We create colours
			$this->textColour = imagecolorallocate($this->image, $this->textColour[0], $this->textColour[1], $this->textColour[2]);
			$this->backgroundColour = imagecolorallocate($this->image, $this->backgroundColour[0], $this->backgroundColour[1], $this->backgroundColour[2]);
			$this->noiseColour = imagecolorallocate($this->image, $this->noiseColour[0], $this->noiseColour[1], $this->noiseColour[2]);
			if ($this->transparentBackground)
				$this->transparentColour = imagecolorallocatealpha($this->image, $this->transparentColour[0], $this->transparentColour[1], $this->transparentColour[2], $this->transparentColour[3]);

			# Sort out the background
			if ($this->transparentBackground)
				$this->transparentBackground();
			else
				imagefilledrectangle($this->image, 0, 0, $this->width, $this->height, $this->backgroundColour);

			# We make some noise if needed
			if ($this->noise)
				$this->noise();

			# We finally write the code
			$this->writeCode();

			# We return the string used
			return $this->str;
		}

		public function printCaptcha()
		{
		    header("Content-type: image/png");
		    imagepng($this->image);
		    imagedestroy($this->image);
		}

		private function processSettings($settings)
		{
			# String generation
			$this->str = $settings['length'] ? $this->generateString($settings['length']) : $this->generateString(rand(5,10));

			# Do we have noise ?
			$this->noise = $settings['noise'] ? true : false;
			$this->noiseType = $settings['noiseType'] ? $settings['noiseType'] : 'all';

			# Setting the font & it's size
			$this->font = $settings['font'];
			$this->fontSize = $settings['fontSize'] ? $settings['fontSize'] : 30;
			$this->letterSpacing = $settings['letterSpacing'] ? $settings['letterSpacing'] : 5;
			$this->horizontalMargin = $settings['horizontalMargin'] ? $settings['horizontalMargin'] : 10;

			# The difficulty
			$difficulty = $settings['difficulty'] ? $settings['difficulty'] : 5;
			$this->difficulty = $this->difficulty($difficulty);

			# Generate random angles for our letters (needed to get the width)
			$this->randomAngle();

			# The size of the image
			$this->height = $settings['height'] ? $settings['height'] : 60;
			if (is_numeric($settings['width']))
				$this->width = $settings['width'];
			elseif ($settings['width'] == "auto")
				$this->width = $this->autoWidth();
			else
				$this->width = 170;

			# Generate some random noise coordinates based on difficulty
			if ($this->noise)
				$this->randomNoise();

			# Do we use a transparent background (BETA)
			$this->transparentBackground = $settings['transparentBackground'] ? true : false;

			# Process the colours
			$this->textColour = $settings['textColour'] ? $settings['textColour'] : $this->randomColour();
			$this->backgroundColour = $settings['backgroundColour'] ? $settings['backgroundColour'] : $this->randomColour();
			$this->noiseColour = $settings['noiseColour'] ? $settings['noiseColour'] : $this->randomColour();
			$this->processColours();
		}

	    private function writeCode()
	    {  
	        $temp_x = $this->horizontalMargin;
	        for ($i = 0; $i < strlen($this->str); $i++)
	        {
	            $bbox = imagettftext($this->image, $this->fontSize, $this->randomAngles[$i], $temp_x, ceil($this->height / 1.5), $this->textColour, $this->font, $this->str[$i]);
	            $temp_x += $this->letterSpacing + ($bbox[4] - $bbox[0]);
	        }      
	    } 

		private function transparentBackground()
		{
		    imagesavealpha($this->image, true);
		    imagealphablending($this->image, false);
		    imagefill($this->image, 0, 0, $this->transparentColour);
		    imagealphablending($this->image, true); 
		    imageantialias($this->image, true);
		}

	    private function autoWidth()
	    {
	        $size = 0;
	        for ($i = 0; $i < strlen($this->str); $i++)
	        {
	            $box = imageftbbox($this->fontSize, $this->randomAngles[$i], $this->font, $this->str[$i]);
	            $size += $this->letterSpacing + (abs($box[4] - $box[0]));
	        }
	        return ceil($size) + ($this->horizontalMargin * 2);
	    }

		private function randomColour($seed = false)
		{
			# If we use a "seed" we'll always have the same colour returned, could be useful
			$seed = !$seed ? uniqid(rand(), true) : $seed;
			$hash = md5($seed);
			return array(
				hexdec(substr($hash, 0, 2)),
				hexdec(substr($hash, 2, 2)),
				hexdec(substr($hash, 4, 2))
				);
		}

	    private function processColours()
	    {
	        if (!is_array($this->textColour))
	            $this->textColour = $this->hex2rgb($this->textColour);

	        if (!is_array($this->backgroundColour))
	            $this->backgroundColour = $this->hex2rgb($this->backgroundColour);

	        if (!is_array($this->noiseColour))
	            $this->noiseColour = $this->hex2rgb($this->noiseColour);

	        if ($this->transparentBackground)
	        	$this->transparentColour = array($this->textColour[0], $this->textColour[1], $this->textColour[2], 127);
	    }

		private function generateString($length, $c = "abcdefghijklmnopqrstuvwxyz1234567890")
		{
		    for ($s = '', $cl = strlen($c)-1, $i = 0; $i < $length; $s .= $c[mt_rand(0, $cl)], ++$i);

		    return $s;
		}

	    private function hex2rgb($hex) 
	    {
	        $hex = str_replace("#", "", $hex);

	        if(strlen($hex) == 3) 
	        {
	            $r = hexdec(substr($hex,0,1).substr($hex,0,1));
	            $g = hexdec(substr($hex,1,1).substr($hex,1,1));
	            $b = hexdec(substr($hex,2,1).substr($hex,2,1));
	        }else{
	            $r = hexdec(substr($hex,0,2));
	            $g = hexdec(substr($hex,2,2));
	            $b = hexdec(substr($hex,4,2));
	        }
	        $rgb = array($r, $g, $b);

	        return $rgb;
	    }

		private function difficulty($difficulty)
		{
			$difficulty = $difficulty > 10 ? 10 : $difficulty;
			$difficulty = $difficulty < 1 ? 5 : $difficulty;
			$difficulty = ceil($difficulty);

			return $difficulty;
		}

	    private function randomAngle()
	    {
	    	$difficulty = $this->difficulty * 5;
	    	$min = -1 * abs($difficulty);
	        for ($i = 0; $i < strlen($this->str); $i++)
	            $this->randomAngles[$i] = rand($min, $difficulty);
	    }

	    private function randomNoise()
	    {
	    	$difficulty = 10 - $this->difficulty;
	    	$difficulty = $difficulty < 1 ? 1 : $difficulty;
	    	for($i = 0; $i < ($this->height / $difficulty); $i++)
	    	{
	    		# We need 10 times more dots than lines (SLOW)
	    		for($j = 0; $j < ($this->height / $difficulty) * 10; $j++)
	    		{
		    		$this->randomNoise['dot'][$j]['x'] = mt_rand(0, $this->width);
		    		$this->randomNoise['dot'][$j]['y'] = mt_rand(0, $this->height);
	    		}

	    		# Generating some lines
	    		$this->randomNoise['line'][$i]['x1'] = mt_rand(0, $this->width);
	    		$this->randomNoise['line'][$i]['y1'] = mt_rand(0, $this->height);
	    		$this->randomNoise['line'][$i]['x2'] = mt_rand(0, $this->width);
	    		$this->randomNoise['line'][$i]['y2'] = mt_rand(0, $this->height);

	    		# And some "circles"
	    		$this->randomNoise['circle'][$i]['x'] = mt_rand(0, $this->width);
	    		$this->randomNoise['circle'][$i]['y'] = mt_rand(0, $this->height);
	    		$this->randomNoise['circle'][$i]['width'] = mt_rand(0, $this->width / 4);
	    		$this->randomNoise['circle'][$i]['height'] = mt_rand(0, $this->height / 4);
	    		$this->randomNoise['circle'][$i]['start'] = mt_rand(0, 360);
	    		$this->randomNoise['circle'][$i]['end'] = mt_rand(0, 360);

	    		# We don't need as many ellipses
	    		if ($i % 2 == 0)
	    		{
		    		$this->randomNoise['ellipse'][$i-1]['x'] = mt_rand(0, $this->width);
		    		$this->randomNoise['ellipse'][$i-1]['y'] = mt_rand(0, $this->height);
		    		$this->randomNoise['ellipse'][$i-1]['width'] = mt_rand(0, $this->width / 4);
		    		$this->randomNoise['ellipse'][$i-1]['height'] = mt_rand(0, $this->height / 4);
	    		}
	    	}
	    }

	    private function noise()
	    {
	    	if ($this->noiseType == 'dots' || $this->noiseType == 'all')
	    	{
	            for($i=0; $i < count($this->randomNoise['dot']); $i++) 
	                imagesetpixel($this->image, $this->randomNoise['dot'][$i]['x'], $this->randomNoise['dot'][$i]['y'], $this->noiseColour);
	    	}
	    	if ($this->noiseType == 'lines' || $this->noiseType == 'all')
	    	{
	            for($i=0; $i < count($this->randomNoise['line']); $i++) 
	            	imageline($this->image, $this->randomNoise['line'][$i]['x1'], $this->randomNoise['line'][$i]['y1'], $this->randomNoise['line'][$i]['x2'], $this->randomNoise['line'][$i]['y2'], $this->noiseColour);
	    	}
	    	if ($this->noiseType == 'ellipses' || $this->noiseType == 'all')
	    	{
	    		for($i=0; $i < count($this->randomNoise['ellipse']); $i++) 
	    			imagefilledellipse($this->image, $this->randomNoise['ellipse'][$i]['x'], $this->randomNoise['ellipse'][$i]['y'], $this->randomNoise['ellipse'][$i]['width'], $this->randomNoise['ellipse'][$i]['height'], $this->noiseColour);
	    	}
	    	if ($this->noiseType == 'circles' || $this->noiseType == 'all')
	    	{
	    		for($i=0; $i < count($this->randomNoise['circle']); $i++) 
	    			imagearc($this->image, $this->randomNoise['circle'][$i]['x'], $this->randomNoise['circle'][$i]['y'], $this->randomNoise['circle'][$i]['width'], $this->randomNoise['circle'][$i]['height'], $this->randomNoise['circle'][$i]['start'], $this->randomNoise['circle'][$i]['end'], $this->noiseColour);
	    	}
	    }
	}