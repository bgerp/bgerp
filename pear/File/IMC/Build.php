<?php
/**
 * +----------------------------------------------------------------------+
 * | Copyright (c) 1997-2008 The PHP Group    							  |
 * +----------------------------------------------------------------------+
 * | All rights reserved.												  |
 * |																	  |
 * | Redistribution and use in source and binary forms, with or without   |
 * | modification, are permitted provided that the following conditions   |
 * | are met:															  |
 * |																	  |
 * | - Redistributions of source code must retain the above copyright	  |
 * | notice, this list of conditions and the following disclaimer.		  |
 * | - Redistributions in binary form must reproduce the above copyright  |
 * | notice, this list of conditions and the following disclaimer in the  |
 * | documentation and/or other materials provided with the distribution. |
 * | - Neither the name of the The PEAR Group nor the names of its		  |
 * | contributors may be used to endorse or promote products derived from |
 * | this software without specific prior written permission.			  |
 * |																	  |
 * | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS  |
 * | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT	  |
 * | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS	  |
 * | FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE		  |
 * | COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,  |
 * | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, |
 * | BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;	  |
 * | LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER	  |
 * | CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT   |
 * | LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN	  |
 * | ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE	  |
 * | POSSIBILITY OF SUCH DAMAGE.										  |
 * +----------------------------------------------------------------------+
 *
 * PHP Version 5
 *
 * @category File_Formats
 * @package  File_IMC
 * @author   Paul M. Jones <pmjones@ciaweb.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version  SVN: $Id$
 * @link	 http://pear.php.net/package/File_IMC
 */

/**
* This class helps build files in the vCard and vCalendar formats.
*
* General note: we use the terms "set" "add" and "get" as function
* prefixes.
*
* "Set" means there is only one iteration of a property, and it has
* only one value repetition, so you set the whole thing at once.
*
* "Add" means either multiple iterations of a property are allowed, or
* that there is only one iteration allowed but there can be multiple
* value repetitions, so you add iterations or repetitions to the current
* stack.
*
* "Get" returns the full vCard line for a single iteration.
*
* @category File_Formats
* @package  File_IMC
* @author   Paul M. Jones <pmjones@ciaweb.net>
* @author   Till Klampaeckel <till@php.net>
* @license  http://www.opensource.org/licenses/bsd-license.php The BSD License
* @version  Release: @package_version@
* @link	 http://pear.php.net/package/File_IMC
*/
abstract class File_IMC_Build
{
	/**
	* Values for vCard properties
	*
	* @var array
	*/
	public $value = array();

	/**
	* Parameters for vCard properties
	*
	* @var array
	*/
	public $param = array();

	/**
	* Groups for vCard properties
	*
	* @var array
	*/
	public $group = array();

	/**
	* Tracks which property (N, ADR, TEL, etc) value was last set or added.
	* Used so that property need not be specified when adding parameters or groups
	*
	* @access private
	* @var string
	*/
	protected $lastProp = null;

	/**
	* Tracks which iteration was last used
	* Used so that iteration need not be specified when adding parameters or groups
	*
	* @access private
	* @var int
	*/
	protected $lastIter = null;

	/**
	* Sets the version of the specification to use.  Only one iteration.
	* Overload this function in the driver to validate and set the version
	*
	* @param string $text The text value of the verson text (e.g. '3.0' or '2.1').
	*
	* @return mixed Void on success, or a PEAR_Error object on failure.
	*/
	abstract function setVersion($text = '3.0');

	/**
	* Validates parameter names and values
	*
	* @param string $name The parameter name (e.g., TYPE or ENCODING).
	*
	* @param string $text The parameter value (e.g., HOME or BASE64).
	*
	* @param string $prop Optional, the property name (e.g., ADR or
	* PHOTO). Only used for error messaging.
	*
	* @param string $iter Optional, the iteration of the property. Only
	* used for error messaging.
	*
	* @return void
	* @throws File_IMC_Exception if not.
	*/
	abstract function validateParam($name, $text, $prop = null, $iter = null);

	/**
	* Fetches a full vCard/vCal text block based on $this->value and
	* $this->param.
	*
	* @return string A properly formatted vCard/vCalendar text block.
	*/
	abstract function fetch();

	/**
	* @access private
	* @param string property
	* @param in iteration
	*/
	protected function _setLast($prop,$iter) {
		$this->lastProp = $prop;
		$this->lastIter = $iter;
	}

	/**
	*
	* Resets the vCard values and params to be blank.
	*
	* @param string $version The vCard version to reset to ('2.1' or
	* '3.0' -- default is the same version as previously set).
	*
	* @return void
	*/
	public function reset($version = null)
	{
		if ( $version === null && isset($this->value['VERSION']) )
			$version = $this->value['VERSION'][0][0][0];
		$this->value = array();
		$this->param = array();
		$this->group = array();
		$this->lastProp = null;
		$this->lastIter = null;
		$this->setVersion($version);	// setVersion will set to default ver if null
	}

	/**
	*
	* Gets back the version of the the vCard.  Only one iteration.
	*
	* @return string The data-source of the vCard.
	*
	* @access public
	*/
	public function getVersion()
	{
		return $this->getMeta('VERSION', 0) . $this->getValue('VERSION', 0);
	}

	/**
	* Check if encoding parameter has been set for this property/iteration
	*   If so... it is assumed that the value has already been encoded as such
	*   Otherwide, encode the value if necessary and sets the encoding parameter
	*
	* @param string $prop
	* @param int $iter
	* @return void
	*/
	public function encode($prop,$iter)
	{
		$ver = $this->value['VERSION'][0][0][0];
		if ( $ver == '2.1' )
		{
			if ( empty($this->param[$prop][$iter]['ENCODING']) )
			{
				foreach ( $this->value[$prop][$iter] as $part => $a )
				{
					foreach ( $a as $rept => $val )
					{
						$val_new = quoted_printable_encode($val);
						$val_new = str_replace("=\r\n",'',$val_new);	// quoted_printable_encode wrapped/folded the text... undo
																		//  lines will get folded via fetch()
						$val_new = str_replace(array("\r","\n"),array('=0D','=0A'),$val_new);
						if ( $val_new != $val )
						{
							$this->addParam('ENCODING', 'QUOTED-PRINTABLE', $prop, $iter);
							$this->value[$prop][$iter][$part][$rept] = $val_new;
						}
					}
				}
			}
		}
		return;
	}

	/**
	* Prepares a string so it may be safely used as vCard values.  DO
	* NOT use this with binary encodings.  Operates on text in-place;
	* does not return a value.  Recursively descends into arrays.
	*
	* Escapes a string so that...
	*	 ; => \;
	*	 , => \,
	*	 newline => literal \n
	*
	* @param mixed $text The string or array or strings to escape.
	*
	* @return void
	* @throws File_IMC_Exception on failure.
	*/
	public function escape(&$text)
	{
		if (is_object($text)) {
			throw new File_IMC_Exception(
				'The escape() method works only with string literals and arrays.',
				FILE_IMC::ERROR_INVALID_PARAM_TYPE);
		}
		if (is_array($text)) {
			// the "text" is really an array; recursively descend into
			// the array and escape text as we go, then set the value
			// of the current "text" (which is really an array).
			foreach ($text as $key => $val) {
				$this->escape($val);
				$text[$key] = $val;
			}
			return;
		}
		//$regex = '(?<!\\\\)([:;,\n\r])';
		$ver = $this->value['VERSION'][0][0][0];
		$regex = $ver == '3.0'
			? '(?<!\\\\)([:;,])'
			: '(?<!\\\\)([:;])';
		$text = preg_replace("/$regex/", '\\\$1', $text);
		if ( $ver == '3.0' )
		{
			$text = str_replace(array("\r","\n"),array('\r','\n'),$text);
		}
	}

	/**
	* Adds a parameter value for a given property and parameter name.
	*
	* Note that although vCard 2.1 and vCalendar allow you to specify a
	* parameter value without a name (e.g., "HOME" instead of
	* "TYPE=HOME") this class is not so lenient.  ;-)	You must
	* specify a parameter name (TYPE, ENCODING, etc) when adding a
	* parameter.  Call multiple times if you want to add multiple values
	* to the same parameter. E.g.:
	*
	* $vcard = File_IMC::build('vCard');
	*
	* // set "TYPE=HOME,PREF" for the first TEL property
	* $vcard->addParam('TYPE', 'HOME', 'TEL', 0);
	* $vcard->addParam('TYPE', 'PREF', 'TEL', 0);
	*
	* @param string $param_name The parameter name, such as TYPE, VALUE,
	* or ENCODING.
	*
	* @param string $param_value The parameter value.
	*
	* @param string $prop The vCard property for which this is a
	* paramter (ADR, TEL, etc).  If null, will be the property that was
	* last set or added-to.
	*
	* @param mixed $iter An integer vCard property iteration that this
	* is a param for.  E.g., if you have more than one ADR property, 0
	* refers to the first ADR, 1 to the second ADR, and so on.  If null,
	* the parameter will be added to the last property iteration
	* available.
	*
	* @return void
	* @throws File_IMC_Excpetion on failure.
	*/
	public function addParam($param_name, $param_value, $prop=null, $iter=null)
	{
		// if property is not specified, default to the last property that was set or added.
		if ($prop === null) {
			$prop = $this->lastProp;
		}
		// if property is not specified, default to the last iteration that was set or added.
		if ($iter === null) {
			//$iter = count($this->value[$prop]) - 1;
			$iter = $this->lastIter;
		}

		// massage the text arguments
		$prop			= strtoupper(trim($prop));
		$param_name		= strtoupper(trim($param_name));
		$param_value	= trim($param_value);

		if ( !is_integer($iter) || $iter < 0) {
			throw new File_IMC_Exception($iter.' is not a valid iteration number for '.$prop.'; must be a positive integer.',
				FILE_IMC::ERROR_INVALID_ITERATION);
		}

		try {
			$result = $this->validateParam($param_name, $param_value, $prop, $iter);
			if ( $result )
				$this->param[$prop][$iter][$param_name][] = $param_value;
		} catch (File_IMC_Exception $e) {
			throw $e; // FIXME: check later
		}

	}

	/**
	* Gets back the group for a given property.
	*
	* @param string $prop The property to get parameters for (ADR, TEL, etc).
	*
	* @param int $iter The vCard property iteration to get the param
	* list for. E.g., if you have more than one ADR property, 0 refers
	* to the first ADR, 1 to the second ADR, and so on.
	*
	* @return string
	*/
	public function getGroup($prop=null, $iter=null)
	{
		$prop = $prop == null
			? $this->lastProp
			: trim(strtoupper($prop));
		$iter = $iter == null
			? $this->lastIter
			: $iter;
		$text = isset($this->group[$prop][$iter])
			? $this->group[$prop][$iter]
			: '';
		return $text;
	}

	/**
	* Sets the group for a given property.
	*
	* @param string $groupNAme The group to assign to the property
	*
	* @param string $prop The property (ADR, TEL, etc).
	*  If null, will be the property that was last set or added-to.
	*
	* @param int $iter An integer vCard property iteration that this is a param for
	*  If null, will be the iteration that was last set or added-to.
	*
	* @return void
	*/
	public function setGroup($groupName, $prop=null, $iter=null)
	{
		$prop = $prop === null
			? $this->lastProp
			: trim(strtoupper($prop));
		$iter = $iter === null
			? $this->lastIter
			: $iter;
		$this->group[$prop][$iter] = $groupName;
		$this->_setLast($prop,$iter);
	}

	/**
	* Gets the left-side/prefix/before-the-colon (metadata) part of a
	* vCard line, including the property identifier, the parameter
	* list, and a colon.
	*
	* @param string $prop The property to get metadata for (ADR, TEL, etc).
	*
	* @param int $iter The vCard property iteration to get the metadata
	* for. E.g., if you have more than one ADR property, 0 refers to
	* the first ADR, 1 to the second ADR, and so on.
	*
	* @return string The line prefix metadata.
	*/
	public function getMeta($prop, $iter = 0)
	{
		$text = '';
		$group	= $this->getGroup($prop, $iter);
		$params	= $this->getParam($prop, $iter);
		if ( !empty($group) )
			$text .= $group.'.';
		$text .= $prop;
		if ( trim($params) != '' )
			$text .= ';'.$params;
		$text .= ':';
		return $text;
	}

	/**
	* Generic, all-purpose method to store a string or array in
	* $this->value, in a way suitable for later output as a vCard
	* element.  This forces the value to be the passed text or array
	* value, overriding any prior values.
	*
	* @param string $prop The property to set the value for ('N','ADR', etc).
	*
	* @param int $iter The property-iteration to set the value for.
	*
	* @param int $part The part number of the property-iteration to set
	* the value for.
	*
	* @param mixed $value A string or array; the set of repeated values
	* for this property-iteration part.
	*
	* @return void
	*/
	public function setValue($prop, $iter, $part, $value)
	{
		$prop = strtoupper($prop);
		settype($value, 'array');
		if ( !isset($this->value[$prop]) ) {
			$this->value[$prop] = array();
		}
		if ( !isset($this->value[$prop][$iter]) ) {
			$this->value[$prop][$iter] = array();
		}
		$this->value[$prop][$iter][$part] = $value;
		$this->_setLast($prop,$iter);
	}

	/**
	* Generic, all-purpose method to add a repetition of a string or
	* array in $this->value, in a way suitable for later output as a
	* vCard element.  This appends the value to be the passed text or
	* array value, leaving any prior values in place.
	*
	* @param string $prop The property to set the value for ('N',
	* 'ADR', etc).
	*
	* @param int $iter The property-iteration to set the value for.
	*
	* @param int $part The part number of the property-iteration to set
	* the value for.
	*
	* @param mixed $value A string or array; the set of repeated values
	* for this property-iteration part.
	*
	* @return void
	*/
	public function addValue($prop, $iter, $part, $vals)
	{
		$prop = strtoupper($prop);
		settype($vals, 'array');
		foreach ($vals as $val) {
			$this->value[$prop][$iter][$part][] = $val;
		}
		$this->_setLast($prop,$iter);
	}

	/**
	* Generic, all-purpose method to get back the data stored in $this->value.
	*
	* @param string $prop The property to set the value for ('N','ADR', etc).
	*
	* @param int $iter The property-iteration to set the value for.
	*
	* @param int $part The part number of the property-iteration to get
	* the value for.
	*
	* @param mixed $rept The repetition number within the part to get;
	* if null, get all repetitions of the part within the iteration.
	*
	* @return string The value, escaped and delimited, of all
	* repetitions in the property-iteration part (or specific
	* repetition within the part).
	*/
	public function getValue($prop, $iter=0, $part=0, $rept=null)
	{
		if ( $rept === null && is_array($this->value[$prop][$iter][$part]) ) {
			// get all repetitions of a part
			$list = array();
			foreach ($this->value[$prop][$iter][$part] as $key => $val) {
				$list[] = trim($val);
			}
			$this->escape($list);
			return implode(',', $list);
		}
		else {
			// get a specific repetition of a part
			$value = trim($this->value[$prop][$iter][$part][$rept]);
			$this->escape($value);
			return $value;
		}
	}

	/**
	* Gets back the parameter string for a given property.
	*
	* @param string $prop The property to get parameters for (ADR, TEL,
	* etc).
	*
	* @param int $iter The vCard property iteration to get the param
	* list for. E.g., if you have more than one ADR property, 0 refers
	* to the first ADR, 1 to the second ADR, and so on.
	*
	* @return string
	*/
	public function getParam($prop, $iter = 0)
	{
		$prop = trim(strtoupper($prop));
		$text = '';

		if (!isset($this->param[$prop])) {
			$this->param[$prop] = array();
		}
		if (!isset($this->param[$prop][$iter])
			|| !is_array($this->param[$prop][$iter])) {
			// if there were no parameters, this will be blank.
			return $text;
		}

		// loop through the array of parameters for
		// the property

		foreach ($this->param[$prop][$iter] as $param_name => $param_val) {

			// if there were previous parameter names, separate with
			// a semicolon
			if ($text != '') {
				$text .= ';';
			}

			if ($param_val === null) {

				// no parameter value was specified, which is typical
				// for vCard version 2.1 -- the name is the value.
				$this->escape($param_name);
				$text .= $param_name;

			} else {
				// set the parameter name...
				$text .= strtoupper($param_name) . '=';

				// ...then escape and comma-separate the parameter
				// values.
				$this->escape($param_val);
				$text .= implode(',', $param_val);
			}
		}
		// if there were no parameters, this will be blank.
		return $text;
	}

	/**
	* Builds a vCard/vCal from a parser result array.  Only send
	* one vCard from the parse-results.
	*
	* Usage (to build from first vCard in parsed results):
	*
	* $parse = File_IMC::parse('vCard'); // new parser
	* $info = $parse->fromFile('sample.vcf'); // parse file
	*
	* $vcard = File_IMC::build('vCard'); // new builder
	* $vcard->setFromArray($info);
	*
	* @param  array  $src One vCard entry as parsed using File_IMC::parse()
	*
	* @return void
	*
	* @see File_IMC_Parse::fromFile()
	* @see File_IMC_Parse::fromText()
	*/
	public function setFromArray(array $src)
	{
		// reset to a blank values and params
		$this->value = array();
		$this->param = array();
		$this->group = array();
		foreach ($src as $card => $card_val) {
			// loop through properties (N, ADR, TEL, etc)
			foreach ($card_val AS $prop => $prop_val) {
				$prop = strtoupper($prop);
				$this->lastProp = $prop;
				// iteration number of each property
				foreach ($prop_val AS $iter => $iter_val) {
					$this->lastIter = $iter;
					foreach ($iter_val AS $kind => $kind_val) {
						$kind = strtolower($kind);
						if ( $kind == 'group' ) {
							$this->group[$prop][$iter] = $kind_val;
						}
						elseif ( is_array($kind_val) ) {
							foreach ( $kind_val AS $part => $part_val ) {
								foreach ( $part_val AS $rept => $text ) {
									if ( $kind == 'value' ) {
										$this->value[$prop][$iter][$part][$rept] = $text;
									} elseif ( $kind == 'param' ) {
										$this->param[$prop][$iter][$part][$rept] = $text;
									} else {
										// ignore data when $kind is neither 'value' nor 'param'
									}
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Magic method to display the vCard/vCal.
	 *
	 * <code>
	 *
	 * $vcard = File_IMC::build('vCard');
	 *
	 * // set "TYPE=HOME,PREF" for the first TEL property
	 * $vcard->addParam('TYPE', 'HOME', 'TEL', 0);
	 * $vcard->addParam('TYPE', 'PREF', 'TEL', 0);
	 *
	 * echo $vcard;
	 *
	 * </code>
	 *
	 * @return string
	 * @uses   self::fetch()
	 */
	public function __toString()
	{
		return $this->fetch();
	}
}

if (!function_exists('quoted_printable_encode')) {
	/**
	 * quoted_printable_encode()
	 * PHP 5.3.0
	 * http://us.php.net/manual/en/function.quoted-printable-encode.php
	 *    ericth at NOSPAM dot pennyworth dot com 07-Oct-2011 01:46
	 * @param string $string
	 * @return string
	 */
	function quoted_printable_encode($str)
	{
		$lp = 0;
		$ret = '';
		$hex = "0123456789ABCDEF";
		$PHP_QPRINT_MAXL = 75;
		$length = strlen($str);
		$str_index = 0;
		while ($length--) {
			if ( (($c = $str[$str_index++]) == "\015") && ($str[$str_index] == "\012") && $length > 0 ) {
				$ret .= "\015";
				$ret .= $str[$str_index++];
				$length--;
				$lp = 0;
			} else {
				if ( ctype_cntrl($c)
					|| (ord($c) == 0x7f)
					|| (ord($c) & 0x80)
					|| ($c == '=')
					|| (($c == ' ') && ($str[$str_index] == "\015")) )
				{
					if (($lp += 3) >$PHP_QPRINT_MAXL)
					{
						$ret .= '=';
						$ret .= "\015";
						$ret .= "\012";
						$lp = 3;
					}
					$ret .= '=';
					$ret .= $hex[ord($c) >> 4];
					$ret .= $hex[ord($c) & 0xf];
				}
				else
				{
					if ( (++$lp) > $PHP_QPRINT_MAXL )
					{
						$ret .= '=';
						$ret .= "\015";
						$ret .= "\012";
						$lp = 1;
					}
					$ret .= $c;
				}
			}
		}
		return $ret;
	}
}
