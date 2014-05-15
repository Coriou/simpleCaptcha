simpleCaptcha
==============

A minimalist captcha image generator in PHP
--------------

There's a lot of libraries for generating captchas out there but I wanted something very simple, that would let me do whatever I want with the generated code (hash it myself, store it where I want, ...) yet easily customisable.

Settings
--------------

	'font'                  => "{$fontsFolder}/Arial.ttf", 	# This has to be set or it will crash - only TrueType supported
	'length'                => '5', 						# The length of the code [default: rand(5,10)]
	'fontSize'              => '30', 						# [default: 30]
	'height'                => '60', 						# [default: 60]
	'width'                 => 'auto', 						# Use "auto" for automatically adjusting the width of the image depending on code's length or set a size. [default: 170]
	'backgroundColour'      => '#FDD02C', 					# The colour of the background. If not set, it will pick a random colour. Can be an hex value (#FFF or #FFFFFF) or an array containing RGB values
	'textColour'            => '#000', 						# The colour of the text. If not set, it will pick a random colour. Can be an hex value (#FFF or #FFFFFF) or an array containing RGB values
	'noiseColour'           => '', 							# The colour of the noise. If not set, it will pick a random colour. Can be an hex value (#FFF or #FFFFFF) or an array containing RGB values
	'letterSpacing'         => 5, 							# Spacing between letters [default: 5]
	'transparentBackground' => false, 						# Using a transparent background [default: false]
	'noise'                 => false, 						# If it generates some noise in the background [default: false]
	'difficulty'            => 5, 							# The overall difficulty of the captcha between 1-10 [default: 5]
	'noiseType'             => 'all', 						# The kind of noise to generate [dots | lines | all] [default: all]
	'horizontalMargin'      => '10' 						# The margin on the right and left side [default: 10]

To Do
--------------

- Ability to use random fonts from a fonts folder
- Ability to make each letter of a random colour / font
- Exception handling
- Better readability when using transparent background (PNG layers - how ?) 
- Better random noise (faster generation of random coordinates + random colours)
- Do we need to destroy the image in case we don't print it ?
- Support for other font types (only supports TrueType fonts right now)

Basic usage
--------------

	# The real path to your fonts' folder
	$fontsFolder = realpath(dirname(__FILE__))."/fonts";

	$settings = array(
            'font'                  => "{$fontsFolder}/Arial.ttf", 	# This has to be set or it will crash - only TrueType supported
            'length'                => '5', 						# The length of the code [default: rand(5,10)]
            'fontSize'              => '30', 						# [default: 30]
            'height'                => '60', 						# [default: 60]
            'width'                 => 'auto', 						# Use "auto" for automatically adjusting the width of the image depending on code's length or set a size. [default: 170]
            'backgroundColour'      => '#FDD02C', 					# The colour of the background. If not set, it will pick a random colour. Can be an hex value (#FFF or #FFFFFF) or an array containing RGB values
            'textColour'            => '#000', 						# The colour of the text. If not set, it will pick a random colour. Can be an hex value (#FFF or #FFFFFF) or an array containing RGB values
            'noiseColour'           => '', 							# The colour of the noise. If not set, it will pick a random colour. Can be an hex value (#FFF or #FFFFFF) or an array containing RGB values
            'letterSpacing'         => 5, 							# Spacing between letters [default: 5]
            'transparentBackground' => false, 						# Using a transparent background [default: false]
            'noise'                 => false, 						# If it generates some noise in the background [default: false]
            'difficulty'            => 5, 							# The overall difficulty of the captcha between 1-10 [default: 5]
            'noiseType'             => 'all', 						# The kind of noise to generate [dots | lines | all] [default: all]
            'horizontalMargin'      => '10' 						# The margin on the right and left side [default: 10]

    );

	$c = new captcha($settings);
	$captchaString = $c->generateCaptcha(); 						# The code on the image, unhashed, do whatever you want with it
	$c->printCaptcha(); 											# Outputs the image