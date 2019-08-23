<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4: */
/**+----------------------------------------------------------------------+
 * | PHP version 5                                                        |
 * +----------------------------------------------------------------------+
 * | Copyright (c) 1997-2008 The PHP Group                                |
 * +----------------------------------------------------------------------+
 * | All rights reserved.                                                 |
 * |                                                                      |
 * | Redistribution and use in source and binary forms, with or without   |
 * | modification, are permitted provided that the following conditions   |
 * | are met:                                                             |
 * |                                                                      |
 * | - Redistributions of source code must retain the above copyright     |
 * | notice, this list of conditions and the following disclaimer.        |
 * | - Redistributions in binary form must reproduce the above copyright  |
 * | notice, this list of conditions and the following disclaimer in the  |
 * | documentation and/or other materials provided with the distribution. |
 * | - Neither the name of the The PEAR Group nor the names of its        |
 * | contributors may be used to endorse or promote products derived from |
 * | this software without specific prior written permission.             |
 * |                                                                      |
 * | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS  |
 * | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT    |
 * | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS    |
 * | FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE       |
 * | COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,  |
 * | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, |
 * | BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;     |
 * | LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER     |
 * | CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT   |
 * | LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN    |
 * | ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE      |
 * | POSSIBILITY OF SUCH DAMAGE.                                          |
 * +----------------------------------------------------------------------+
 *
 * @category File_Formats
 * @package  File_IMC
 * @author   Paul M. Jones <pmjones@ciaweb.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version  CVS: $Id$
 * @link     http://pear.php.net/package/File_IMC
 */

/**
* Common parser for IMC files (vCard, vCalendar, iCalendar)
*
* This class provides the methods to parse a file into an array.
* By extending the class, you are able to define functions to handle
* specific elements that need special decoding.  For an example, see
* File_IMC_Parse_vCard.
*
* @author Paul M. Jones <pmjones@ciaweb.net>
*
* @category File_Formats
* @package  File_IMC
* @author   Paul M. Jones <pmjones@ciaweb.net>
* @author   Till Klampaeckel <till@php.net>
* @license  http://www.opensource.org/licenses/bsd-license.php The BSD License
* @version  Release: @package_version@
* @link     http://pear.php.net/package/File_IMC
*/
abstract class File_IMC_Parse
{
    /**
	* Keeps track of the current line being parsed
	*
	* Starts at -1 so that the first line parsed is 0, since
	* _parseBlock() advances the counter by 1 at the beginning
	*
	* @see self::_parseBlock()
	*
	* @var int
	*/
	protected $count = -1;

	/**
	 * @var array
	 */
	protected $data;

	abstract function getVersion();

	/**
	* Reads a file for parsing, then sends it to $this->fromText()
	* and returns the results.
	*
	* @param string $filename The name of the file to read
	* @param string $charset what charset the text should be.  Default = UTF-8
	*
	* @return array An array of information extracted from the file.
	* @throws File_IMC_Exception If the file does not exist.
	* @throws File_IMC_Exception If the file is not readable.
	*
	* @see self::fromText()
	* @see self::_fromArray()
	*/
	public function fromFile($filename, $charset='UTF-8')
	{
		if (!file_exists($filename)) {
			throw new File_IMC_Exception("File {$filename} does not exist.");
		}
		if (!is_readable($filename)) {
			throw new File_IMC_Exception("Could not open {$filename}.");
		}
		// get the file data
		$text = implode('', file($filename));

		// dump to, and get return from, the fromText() method.
		return $this->fromText($text,$charset);
	}

	/**
	* Prepares a block of text for parsing, then sends it through and
	* returns the results from $this->_fromArray().
	*
	* @param string $text A block of text to read for information.
	* @param string $charset what charset the text should be.  Default = UTF-8
	* @return array An array of information extracted from the source text.
	*
	* @see self::_fromArray()
	*/
	public function fromText($text, $charset='UTF-8')
	{
		// convert all kinds of line endings to Unix-standard and get
		// rid of double blank lines.
		$text = $this->_convertLineEndings($text);

		// unfold lines.  concat two lines where line 1 ends in \n and
		// line 2 starts with any amount of whitespace.  only removes
		// the first whitespace character, leaves others in place.
		//$fold_regex = '(\n)([ |\t])';
		$fold_regex = '(\n)([ |\t|=])';
		$text       = preg_replace("/$fold_regex/i", "", $text);

		// convert the resulting text to an array of lines
		$lines = explode("\n", $text);

		// parse the array of lines and return info
		$this->data = $this->_fromArray($lines,$charset);
		return $this->data;
	}

	/**
	* Converts line endings in text.
	*
	* Takes any text block and converts all line endings to UNIX
	* standard. DOS line endings are \r\n, Mac are \r, and UNIX is \n.
	* As a side-effect, all double-newlines (\n\n) are converted to a
	* single-newline.
	*
	* NOTE: Acts on the text block in-place; does not return a value.
	*
	* @param string $text The string on which to convert line endings.
	*
	* @return void
	*/
	protected function _convertLineEndings($text)
	{
		// first, replace \r\n with \n to fix up from DOS and Mac
		$text = str_replace("\r\n", "\n", $text);
		$text = str_replace("\r", "\n", $text);

		return $text;
	}

	/**
	* Splits a string into an array.  Honors backslash-escaped
	* delimiters, (i.e., splits at ';' not '\;') and double-quotes
	* (will not break inside double-quotes ("")).
	*
	* @param string $text The string to split into an array.
	*
	* @param string $delim Character to split string at.
	*
	* @param bool $recurse If true, recursively parse the entire text
	* for all occurrences of the delimiter; if false, only parse for
	* the first occurrence.  Defaults to true.
	*
	* @return string|array An array of values, or a single string.
	*/
	public function _splitByDelim($text, $delim, $recurse = true)
	{
		// where in the string is the delimiter?
		$pos = false;

		// was the previously-read character a backslash?
		// (used for tracking escaped characters)
		$prevIsBackslash = false;

		// are we currently inside a quoted passage?
		$inQuotes = false;

		// the length of the text to be parsed
		$len = strlen($text);

		// go through the text character by character, find the
		// first occurrence of the delimiter, save it, and
		// recursively parse the rest of the text
		for ($i = 0; $i < $len; $i++) {

			// if the current char is a double-quote, and the
			// previous char was _not_ an escaping backslash,
			// then note that we are now inside a quoted passage.
			if ($text{$i} == '"' && $prevIsBackslash == false) {
				($inQuotes == true) ? $inQuotes = false : $inQuotes = true;
			}

			// if the current char is the delimiter, and we are _not_
			// inside quotes, and the delimiter has not been backslash-
			// escaped, then note the position of the delimiter and
			// break out of the loop.
			if ($text{$i} == $delim &&
				$inQuotes == false &&
				$prevIsBackslash == false) {

				$pos = $i;
				break;
			}

			// we have not found quotes, or the delimiter.
			// is the current char an escaping backslash?
			if ($text{$i} == "\\") {
				$prevIsBackslash = true;
			} else {
				$prevIsBackslash = false;
			}
		}

		// have we found the delimiter in the text?
		if ($pos === false) {
			// we have not found the delimiter anywhere in the
			// text.  return the text as it is.
			return array($text);
		}

		// find the portions of the text to the left and the
		// right of the delimiter
		$left = trim(substr($text, 0, $pos));
		$right = trim(substr($text, $pos+1, strlen($text)));

		// should we recursively parse the rest of the text?
		if ($recurse) {
			// parse the right portion for the same delimiter, and
			// merge the results with the left-portion.
			return array_merge(
				array($left),
				$this->_splitByDelim($right, $delim, $recurse)
			);
		}

		// no recursion
		return array($left, $right);
	}

	/**
	* Splits a string into an array at semicolons.
	*
	* @param string $text The string to split into an array.
	*
	* @param bool $convertSingle If splitting the string results in a
	* single array element, return a string instead of a one-element
	* array.
	*
	* @param bool $recurse If true, recursively parse the entire text
	* for all occurrences of the delimiter; if false, only parse for
	* the first occurrence.  Defaults to true.
	*
	* @return string|array An array of values, or a single string.
	*
	* @see self::_splitByDelim()
	*/
	protected function _splitBySemi($text, $recurse = true)
	{
		return $this->_splitByDelim($text, ";", $recurse);
	}

	/**
	* Splits a string into an array at commas.
	*
	* @param string $text The string to split into an array.
	*
	* @param bool $recurse If true, recursively parse the entire text
	* for all occurrences of the delimiter; if false, only parse for
	* the first occurrence.  Defaults to true.
	*
	* @return string|array An array of values, or a single string.
	*
	* @see self::_splitByDelim()
	*/
	protected function _splitByComma($text, $recurse = true)
	{
		return $this->_splitByDelim($text, ",", $recurse);
	}

	/**
	*
	* Splits the line into types/parameters and values.
	*
	* @todo A parameter w/ 1 quote will break everything. Try to
	*       come up with a good way to fix this.
	*
	* @param string $text The string to split into an array.
	*
	* @param bool $recurse If true, recursively parse the entire text
	* for all occurrences of the delimiter; if false, only parse for
	* the first occurrence.  Defaults to false (this is different from
	* {@link self::_splitByCommon()} and {@link self::_splitBySemi()}).
	*
	* @return array The first element contains types and parameters
	* (before the colon). The second element contains the line's value
	* (after the colon).
	*/
	protected function _splitByColon($text, $recurse = false)
	{
		return $this->_splitByDelim($text, ":", $recurse);
	}

	/**
	* Used to make string human-readable after being a vCard value.
	*
	* Converts...
	*     \; => ;
	*     \, => ,
	*     literal \n => newline
	*
	* @param string|array $text The text to unescape.
	*
	* @return mixed
	*/
	protected function _unescape($text)
	{
		if (is_array($text)) {
			foreach ($text as $key => $val) {
				$text[$key] = $this->_unescape($val);
			}
		} else {
			// \r added per #16637
			$find    = array('\:', '\;', '\,', '\n', '\r');
			$replace = array(':',  ';',  ',',  "\n", "\r");
			$text    = str_replace($find, $replace, $text);
		}
		return $text;
	}

	/**
	* Parses an array of source lines and returns an array of vCards.
	* Each element of the array is itself an array expressing the types,
	* parameters, and values of each part of the vCard. Processes both
	* 2.1 and 3.0 vCard sources.
	*
	* @param array $source An array of lines to be read for vCard information.
	* @param string $charset what charset the text should be.  Default = UTF-8
	* @return array An array of of vCard information extracted from the source array.
	*
	* @todo fix missing colon = skip line
	*/
	protected function _fromArray($source,$charset='UTF-8')
	{
		$parsed = $this->_parseBlock($source,$charset);
		$parsed = $this->_unescape($parsed);
		return $parsed;
	}

	/**
	* Goes through the IMC file, recursively processing BEGIN-END blocks
	*
	* Handles nested blocks, such as vEvents (BEGIN:VEVENT) and vTodos
	* (BEGIN:VTODO) inside vCalendars (BEGIN:VCALENDAR).
	*
	* @param array Array of lines in the IMC file
	* @param string $charset
	*
	* @return array
	*/
	protected function _parseBlock(array $source, $charset='UTF-8')
	{
		$max = count($source);

		for ($this->count++; $this->count < $max; $this->count++) {

			$line = $source[$this->count];

			// if the line is blank, skip it.
			if (trim($line) == '') {
				continue;
			}

			// get the left and right portions. The part
			// to the left of the colon is the type and parameters;
			// the part to the right of the colon is the value data.
			$lr = $this->_splitByColon($line);
			if ( count($lr) < 2 ) {
				// colon not found, skip whole line
				continue;
			}
			list($left,$right) = $lr;

			if (strtoupper($left) == "BEGIN") {

				$block[$right][] = $this->_parseBlock($source);

			} elseif (strtoupper($left) == "END") {

				return $block;

			} else {

				// we're not on an ending line, so collect info from
				// this line into the current card. split the
				// left-portion of the line into a type-definition
				// (the kind of information) and parameters for the
				// type.
				$tmp     = $this->_splitBySemi($left);
				$group   = $this->_getGroup($tmp);
				$typedef = $this->_getTypeDef($tmp);
				$params  = $this->_getParams($tmp);

				$resp = $this->_decode($params, $right, $typedef);
				$params = $resp[0];
				$right  = $resp[1];

				$resp = $this->_charset_conv($params, $right, $charset);
				$params = $resp[0];
				$right  = $resp[1];

				// now get the value-data from the line, based on the typedef
				$func = '_parse' . strtoupper($typedef);

				if (method_exists($this, $func)) {
					$value = $this->$func($right);
				} else {
					// by default, just grab the plain value. keep
					// as an array to make sure *all* values are
					// arrays.  for consistency. ;-)
					$value = array(array($right));
				}

				// add the type, parameters, and value to the
				// current card array.  note that we allow multiple
				// instances of the same type, which might be dumb
				// in some cases (e.g., N).
				$block[$typedef][] = array(
					'group' => $group,
					'param' => $params,
					'value' => $value
				);
			}
		}
		return $block;
	}

	/**
	* Takes a line and extracts the Group for the line (a group is
	* identified as a prefix-with-dot to the Type-Definition; e.g.,
	* Group.ADR or Group.ORG).
	*
	* @param array $text Array containing left side (before colon) split by
	*                    semi-colon from a line.
	*
	* @return string The group for the line.
	*
	* @see self::_getTypeDef()
	* @see self::_splitBySemi()
	*/
	protected function _getGroup(array $text)
	{
		// find the first element (the typedef)
		$tmp = $text[0];
		// find a dot in the typedef
		$pos = strpos($tmp, '.');
		return $pos !== false
			? substr($tmp, 0, $pos)	// there is a group, return it
			: '';					// there is no group
	}

	/**
	* Takes a line and extracts the Type-Definition for the line (not
	* including the Group portion; e.g., in Group.ADR, only ADR is
	* returned).
	*
	* @param array $text Array containing left side (before colon) split by
	*                    semi-colon from a line.
	*
	* @return string The type definition for the line.
	*
	* @see self::_getGroup()
	* @see self::_splitBySemi()
	*/
	protected function _getTypeDef(array $text)
	{
		// find the first element (the typedef)
		$tmp = strtoupper($text[0]);
		$pos = strpos($tmp,'.');
		return $pos !== false
			? substr($tmp, $pos + 1)	// there is a group... just return the property name
			: $tmp;						// no group

	}

	/**
	* Finds the Type-Definition parameters for a line.
	*
	* @param array Array containing left side (before colon) split by
	*              semi-colon from a line.
	*
	* @return array An array of parameters.
	*
	* @see self::_splitBySemi()
	*/
	protected function _getParams(array $text)
	{
		// drop the first element of the array (the type-definition)
		array_shift($text);

		// set up an array to retain the parameters, if any
		$params = array();

		// loop through each parameter.  the params may be in the format...
		// "TYPE=type1,type2,type3"
		//    ...or...
		// "TYPE=type1;TYPE=type2;TYPE=type3"
		foreach ($text as $full) {

			// split the full parameter at the equal sign so we can tell
			// the parameter name from the parameter value
			$tmp = explode("=", $full, 2);

			// the key is the left portion of the parameter (before
			// '='). if in 2.1 format, the key may in fact be the
			// parameter value, not the parameter name.
			$key = strtoupper(trim($tmp[0]));

			// get the parameter name by checking to see if it's in
			// vCard 2.1 or 3.0 format.
			$name = $this->_getParamName($key);

			// list of all parameter values
			$listall = array_key_exists(1, $tmp) ? trim($tmp[1]) : '';

			// if there is a value-list for this parameter, they are
			// separated by commas, so split them out too.
			$list = $this->_splitByComma($listall);

			// now loop through each value in the parameter and retain
			// it.  if the value is blank, that means it's a 2.1-style
			// param, and the key itself is the value.
			foreach ($list as $val) {
				if (trim($val) != '') {
					// 3.0 formatted parameter
					$params[$name][] = trim($val);
				} else {
					// 2.1 formatted parameter
					$params[$name][] = $key;
				}
			}

			// if, after all this, there are no parameter values for the
			// parameter name, retain no info about the parameter (saves
			// ram and checking-time later).
			if (count($params[$name]) == 0) {
				unset($params[$name]);
			}
		}

		// return the parameters array.
		return $params;
	}

	/**
	* Returns the parameter name for parameters given without names.
	*
	* The vCard 2.1 specification allows parameter values without a
	* name. The parameter name is then determined from the unique
	* parameter value.
	*
	* Shamelessly lifted from Frank Hellwig <frank@hellwig.org> and his
	* vCard PHP project <http://vcardphp.sourceforge.net>.
	*
	* @param string $value The first element in a parameter name-value
	* pair.
	*
	* @return string The proper parameter name (TYPE, ENCODING, or
	* VALUE).
	*/
	protected function _getParamName($value)
	{
		static $types = array (
			'DOM', 'INTL', 'POSTAL', 'PARCEL','HOME', 'WORK',
			'PREF', 'VOICE', 'FAX', 'MSG', 'CELL', 'PAGER',
			'BBS', 'MODEM', 'CAR', 'ISDN', 'VIDEO',
			'AOL', 'APPLELINK', 'ATTMAIL', 'CIS', 'EWORLD',
			'INTERNET', 'IBMMAIL', 'MCIMAIL',
			'POWERSHARE', 'PRODIGY', 'TLX', 'X400',
			'GIF', 'CGM', 'WMF', 'BMP', 'MET', 'PMB', 'DIB',
			'PICT', 'TIFF', 'PDF', 'PS', 'JPEG', 'QTIME',
			'MPEG', 'MPEG2', 'AVI',
			'WAVE', 'AIFF', 'PCM',
			'X509', 'PGP'
		);

		// CONTENT-ID added by pmj
		static $values = array (
			'INLINE', 'URL', 'CID', 'CONTENT-ID'
		);

		// 8BIT added by pmj
		static $encodings = array (
			'7BIT', '8BIT', 'QUOTED-PRINTABLE', 'BASE64'
		);

		// changed by pmj to the following so that the name defaults to
		// whatever the original value was.  Frank Hellwig's original
		// code was "$name = 'UNKNOWN'".
		$name = $value;

		if (in_array($value, $types)) {
			$name = 'TYPE';
		} elseif (in_array($value, $values)) {
			$name = 'VALUE';
		} elseif (in_array($value, $encodings)) {
			$name = 'ENCODING';
		}

		return $name;
	}

	/**
	* Looks at a line's parameters;
	* if ENCODING parameter is set and is QUOTED-PRINTABLE
	*     then decode the text in-place.
	* This method is overwritten in Parse/Vcard.php
	*
	* @access private
	*
	* @param array $params A parameter array from a vCard line.
	* @param string $text A right-part (after-the-colon part) from a line.
	* @param string $prop The property name.. passed so may have special-cases
	* @return array
	*
	* @uses quoted_printable_decode()
	*/
	protected function _decode(array $params, $text,$prop=null)
	{
		// loop through each parameter
		foreach ( $params as $param => $param_vals ) {
			// check to see if it's an encoding param
			if ( trim(strtoupper($param)) != 'ENCODING' ) {
				continue;
			}
			// loop through each ENCODING param value
			foreach ( $param_vals as $k => $param_val ) {
				// if any of the values are QP, decode the text in-place and return
				if ( trim(strtoupper($param_val)) == 'QUOTED-PRINTABLE' ) {
					$text = quoted_printable_decode($text);
					// remove the encoding param.. as it's no longer encoded!
					unset($params[$param][$k]);
					break;
				}
			}
		}
		return array($params, $text);
	}

	/**
	* Convert text to the specified charaster set
	*
	* @access private
	*
	* @param array $params A parameter array from a vCard line.
	* @param string $text A right-part (after-the-colon part) from a line.
	* @param string $charset The charset to convert to
	* @return array
	*/
	protected function _charset_conv(array $params, $text, $charset='UTF-8')
	{
		/*
		vcard 2.1 default charset = ASCII.
		vcard 3.0 default charset = UTF-8 // there is no CHARSET parameter
		*/
		$charset_is = 'UTF-8';
		foreach ( $params as $param => $param_vala ) {
			if ( trim(strtoupper($param)) == 'CHARSET' ) {
				$charset_is = $param_vala[0];
				break;
			}
		}
		if ( $charset_is == 'ASCII' && $charset == 'UTF-8' ) {
			$charset_is = 'UTF-8';
			// no conversion necessary
			// unset($params['CHARSET']);
		}
		elseif ( $charset_is != $charset ) {
			if ( extension_loaded('mbstring') ) {
				$text = mb_convert_encoding($text, $charset, $charset_is);
				$params['CHARSET'] = array( $charset );
			}
			else {
				$text_new = htmlentities($text,ENT_NOQUOTES,$charset_is);
				$text_new = html_entity_decode($text_new,ENT_QUOTES,$charset);
				if ( $text_new )
				{
					$text = $text_new;
					$params['CHARSET'] = array( $charset );
				}
			}
		}
		return array($params, $text);
	}

}

?>