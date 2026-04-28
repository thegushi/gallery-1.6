<?php
/**
  * $Id: hn_captcha.class.php 15936 2007-03-07 13:54:05Z jenst $
  *
  * PHP-Class hn_captcha Version 1.3, released 11-Apr-2006
  * Author: Horst Nogajski, horst@nogajski.de
  *
  * License: GNU GPL (http://www.opensource.org/licenses/gpl-license.html)
  * Download: http://hn273.users.phpclasses.org/browse/package/1569.html
  *
  * If you find it useful, you might rate it on http://www.phpclasses.org/rate.html?package=1569
  * If you use this class in a productional environment, you may drop me a note, so I can add a link to the page.
  *
  **/

/**
  * changes in version 1.1:
  *  - added a new configuration-variable: maxrotation
  *  - added a new configuration-variable: secretstring
  *  - modified function get_try(): now ever returns a string of 16 chars
  *
  * changes in version 1.2:
  *  - added a new configuration-variable: secretposition
  *  - once more modified the function get_try(): generate a string of 32 chars length,
  *	where at secretposition is the number of current-try.
  *	Hopefully this is enough for hackprevention.
  *
  * changes in version 1.3:
  *  - fixed a security-hole, what was discovered by Daniel Jagszent. Many thank's for
  *	testing, fixing and sharing it, Daniel!
  *	He has tested the class in a modified way, like it is described here:
  *	http://www.puremango.co.uk/cm_breaking_captcha_115.php
  *	It was possible to manually do the captcha-test, notice the public and private keys.
  *	In automated way this keys could send as long as the image-file exists!
  *	(with different other datas and independent from the new captcha-string!)
  *
  **/

/**
  * License: GNU GPL (http://www.opensource.org/licenses/gpl-license.html)
  *
  * This program is free software;
  *
  * you can redistribute it and/or modify it under the terms of the GNU General Public License
  * as published by the Free Software Foundation; either version 2 of the License,
  * or (at your option) any later version.
  *
  * This program is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
  * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License along with this program;
  * if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
  *
  **/

/**
  * This class generates a picture to use in forms that perform CAPTCHA test
  * (Completely Automated Public Turing to tell Computers from Humans Apart).
  * After the test form is submitted a key entered by the user in a text field
  * is compared by the class to determine whether it matches the text in the picture.
  *
  * The class is a fork of the original released at www.phpclasses.org
  * by Julien Pachet with the name ocr_captcha.
  *
  * The following enhancements were added:
  *
  * - Support to make it work with GD library before version 2
  * - Hacking prevention
  * - Optional use of Web safe colors
  * - Limit the number of users attempts
  * - Display an optional refresh link to generate a new picture with a different key
  *   without counting to the user attempts limit verification
  * - Support the use of multiple random TrueType fonts
  * - Control the output image by only three parameters: number of text characters
  *   and minimum and maximum size preserving the size proportion
  * - Preserve all request parameters passed to the page via the GET method,
  *   so the CAPTCHA test can be added to existing scripts with minimal changes
  * - Added a debug option for testing the current configuration
  *
  * All the configuration settings are passed to the class in an array when the object instance is initialized.
  *
  * The class only needs two function calls to be used: display_form() and validate_submit().
  *
  * The class comes with an examplefile.
  * If you don't have it: http://hn273.users.phpclasses.org/browse/package/1569.html
  *
  * @shortdesc Class that generate a captcha-image with text and a form to fill in this text
  * @public
  * @author Horst Nogajski, (mail: horst@nogajski.de)
  * @version 1.3
  * @date 2006-April-11
  *
  **/
class hn_captcha{

	////////////////////////////////
	//
	//	PUBLIC PARAMS
	//

		/**
		  * @shortdesc Relative path to a Tempfolder (with trailing slash!) inside the Galley albums folder.
		  * 		   This must be writeable for PHP and also accessible via HTTP, because the image will be stored there.
		  * @Note: This is different to the original hn_captcha
		  * @type string
		  * @public
		  *
		  **/
		var $tempfolder;

		/**
		  * @shortdesc Absolute path to folder with TrueTypeFonts (with trailing slash!). This must be readable by PHP.
		  * @type string
		  * @public
		  *
		  **/
		var $TTF_folder;

		/**
		  * @shortdesc A List with available TrueTypeFonts for random char-creation.
		  * @type mixed[array|string]
		  * @public
		  *
		  **/
		var $TTF_RANGE  = array('COM430.ttf');

		/**
		  * @shortdesc How many chars the generated text should have
		  * @type integer
		  * @public
		  *
		  **/
		var $chars		= 6;

		/**
		  * @shortdesc The minimum size a Char should have
		  * @type integer
		  * @public
		  *
		  **/
		var $minsize	= 20;

		/**
		  * @shortdesc The maximum size a Char can have
		  * @type integer
		  * @public
		  *
		  **/
		var $maxsize	= 40;

		/**
		  * @shortdesc The maximum degrees a Char should be rotated. Set it to 30 means a random rotation between -30 and 30.
		  * @type integer
		  * @public
		  *
		  **/
		var $maxrotation = 30;

		/**
		  * @shortdesc Background noise On/Off (if is Off, a grid will be created)
		  * @type boolean
		  * @public
		  *
		  **/
		var $noise		= TRUE;

		/**
		  * @shortdesc This will only use the 216 websafe color pallette for the image.
		  * @type boolean
		  * @public
		  *
		  **/
		var $websafecolors = FALSE;

		/**
		  * @shortdesc Switches language, available are 'en' and 'de'. You can easily add more. Look in CONSTRUCTOR.
		  * @type string
		  * @public
		  *
		  **/
		var $lang		= "de";

		/**
		  * @shortdesc If a user has reached this number of try's without success, he will moved to the $badguys_url
		  * @type integer
		  * @public
		  *
		  **/
		var $maxtry		= 3;

		/**
		  * @shortdesc Gives the user the possibility to generate a new captcha-image.
		  * @type boolean
		  * @public
		  *
		  **/
		var $refreshlink = TRUE;

		/**
		  * @shortdesc If a user has reached his maximum try's, he will located to this url.
		  * @type boolean
		  * @public
		  *
		  **/
		var $badguys_url = "/";

		/**
		  * Number between 1 and 32
		  *
		  * @shortdesc Defines the position of 'current try number' in (32-char-length)-string generated by function get_try()
		  * @type integer
		  * @public
		  *
		  **/
		var $secretposition = 21;

		/**
		  * @shortdesc The string is used to generate the md5-key.
		  * @type string
		  * @public
		  *
		  **/
		var $secretstring = "This is a very secret Gallery string. Nobody should know it, :-)";

		/**
		  * @shortdesc Outputs configuration values for testing
		  * @type boolean
		  * @public
		  *
		  **/
		var $debug = FALSE;



	////////////////////////////////
	//
	//	PRIVATE PARAMS
	//

		/** @private **/
		var $lx;				// width of picture
		/** @private **/
		var $ly;				// height of picture
		/** @private **/
		var $jpegquality = 80;	// image quality
		/** @private **/
		var $noisefactor = 9;	// this will multiplyed with number of chars
		/** @private **/
		var $nb_noise;			// number of background-noise-characters
		/** @private **/
		var $TTF_file;			// holds the current selected TrueTypeFont
		/** @private **/
		var $msg1;
		/** @private **/
		var $msg2;
		/** @private **/
		var $buttontext;
		/** @private **/
		var $refreshbuttontext;
		/** @private **/
		var $public_K;
		/** @private **/
		var $private_K;
		/** @private **/
		var $key;				// md5-key
		/** @private **/
		var $public_key;		// public key
		/** @private **/
		var $filename;			// filename of captcha picture
		/** @private **/
		var $gd_version;		// holds the Version Number of GD-Library
		/** @private **/
		var $QUERY_STRING;		// keeps the ($_GET) Querystring of the original Request
		/** @private **/
		var $current_try = 0;
		/** @private **/
		var $r;
		/** @private **/
		var $g;
		/** @private **/
		var $b;


	////////////////////////////////
	//
	//	CONSTRUCTOR
	//

		/**
		  * @shortdesc Extracts the config array and generate needed params.
		  * @private
		  * @type void
		  * @return nothing
		  *
		  **/
		function hn_captcha($config, $secure = TRUE) {

			global $save;
			global $gallery;

			// Test for GD-Library(-Version)
			$this->gd_version = $this->get_gd_version();
			if($this->gd_version == 0) die("There is no GD-Library-Support enabled. The Captcha-Class cannot be used!");
			if($this->debug) echo "\n<br>-Captcha-Debug: The available GD-Library has major version ".$this->gd_version;

			// Hackprevention
			if(
				(isset($_GET['maxtry']) || isset($_POST['maxtry']) || isset($_COOKIE['maxtry']))
				||
				(isset($_GET['debug']) || isset($_POST['debug']) || isset($_COOKIE['debug']))
				||
				(isset($_GET['captcharefresh']) || isset($_COOKIE['captcharefresh']))
				||
				(isset($_POST['captcharefresh']) && isset($_POST['private_key']) && !empty($save))
				)
			{
				if($this->debug) echo "\n<br>-Captcha-Debug: Buuh. You are a bad guy!";
				echo "\n<br>-Captcha-Debug: Buuh. You are a bad guy!";
				//if(isset($this->badguys_url) && !headers_sent()) header('location: '.$this->badguys_url);
				//else die('Sorry.');
			}


			// extracts config array
			if(is_array($config))
			{
				if($secure && strcmp('4.2.0', phpversion()) < 0)
				{
					if($this->debug) echo "\n<br>-Captcha-Debug: Extracts Config-Array in secure-mode!";
					$valid = get_class_vars(get_class($this));
					foreach($config as $k=>$v)
					{
						if(array_key_exists($k,$valid)) $this->$k = $v;
					}
				}
				else
				{
					if($this->debug) echo "\n<br>-Captcha-Debug: Extracts Config-Array in unsecure-mode!";
					foreach($config as $k=>$v) $this->$k = $v;
				}
			}

			$absoluteTempFolder = $gallery->app->albumDir . '/' . $this->tempfolder;
			if (empty($this->tempfolder)) {
				printInfoBox(array(array(
					'type' => 'error',
					'text' => Translate('core', "Please enter a temporary folder for the catcha images in the captcha init file.")
				)));
				exit;
			}
			elseif (!fs_is_dir($absoluteTempFolder)) {
				if(! fs_mkdir($absoluteTempFolder, 0775)) {
					printInfoBox(array(array(
						'type' => 'error',
						'text' => sprintf(gTranslate('core', "The specified folder '%s' (Fullpath: '%s') does not exist inside the albums folder and Gallery is not able to create it."), $this->tempfolder, $absoluteTempFolder)
					)));
					exit;
				}
			}
			elseif (!fs_is_writable($absoluteTempFolder)) {
				printInfoBox(array(array(
					'type' => 'error',
					'text' => sprintf(gTranslate('core', "The specified folder '%s' (Fullpath: '%s') for captcha images is not writable for the webserver."), $this->tempfolder, $absoluteTempFolder)
				)));

				exit;
			}

			// check vars for maxtry, secretposition and min-max-size
			$this->maxtry = ($this->maxtry > 9 || $this->maxtry < 1) ? 3 : $this->maxtry;
			$this->secretposition = ($this->secretposition > 32 || $this->secretposition < 1) ? $this->maxtry : $this->secretposition;
			if($this->minsize > $this->maxsize)
			{
				$temp = $this->minsize;
				$this->minsize = $this->maxsize;
				$this->maxsize = $temp;
				if($this->debug) echo "<br>-Captcha-Debug: Arrghh! What do you think I mean with min and max? Switch minsize with maxsize.";
			}


			// check TrueTypeFonts
			if(is_array($this->TTF_RANGE))
			{
				if($this->debug) echo "\n<br>-Captcha-Debug: Check given TrueType-Array! (".count($this->TTF_RANGE).")";
				$temp = array();
				foreach($this->TTF_RANGE as $k=>$v)
				{
					if(is_readable($this->TTF_folder.$v)) $temp[] = $v;
				}
				$this->TTF_RANGE = $temp;
				if($this->debug) echo "\n<br>-Captcha-Debug: Valid TrueType-files: (".count($this->TTF_RANGE).")";
				if(count($this->TTF_RANGE) < 1) die('No Truetypefont available for the CaptchaClass.');
			}
			else
			{
				if($this->debug) echo "\n<br>-Captcha-Debug: Check given TrueType-File! (".$this->TTF_RANGE.")";
				if(!is_readable($this->TTF_folder.$this->TTF_RANGE)) die('No Truetypefont available for the CaptchaClass.');
			}

			// select first TrueTypeFont
			$this->change_TTF();
			if($this->debug) echo "\n<br>-Captcha-Debug: Set current TrueType-File: (".$this->TTF_file.")";


			// get number of noise-chars for background if is enabled
			$this->nb_noise = $this->noise ? ($this->chars * $this->noisefactor) : 0;
			if($this->debug) echo "\n<br>-Captcha-Debug: Set number of noise characters to: (".$this->nb_noise.")";


			// set dimension of image
			$this->lx = ($this->chars + 1) * (int)(($this->maxsize + $this->minsize) / 1.5);
			$this->ly = (int)(2.4 * $this->maxsize);
			if($this->debug) echo "\n<br>-Captcha-Debug: Set image dimension to: (".$this->lx." x ".$this->ly.")";


			// set all messages
			// (if you add a new language, you also want to add a line to the function "notvalid_msg()" at the end of the class!)
			$this->messages = array(
				'de'=>array(
