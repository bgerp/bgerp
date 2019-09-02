<?php
/**
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
 * PHP Version 5
 *
 * @category File_Formats
 * @package  File_IMC
 * @author   Paul M. Jones <pmjones@ciaweb.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version  SVN: $Id$
 * @link     http://pear.php.net/package/File_IMC
 */

/**
* This class builds a single vCard (version 3.0 or 2.1).
*
* @category File_Formats
* @package  File_IMC
* @author   Paul M. Jones <pmjones@ciaweb.net>
* @license  http://www.opensource.org/licenses/bsd-license.php The BSD License
* @version  Release: @package_version@
* @link     http://pear.php.net/package/File_IMC
*/
class File_IMC_Build_Vcard extends File_IMC_Build
{
    /**
	* Constructor
	*
	* @param string $version The vCard version to build; affects which
	* parameters are allowed and which properties are returned by
	* fetch().
	*
	* @return File_IMC_Build_Vcard
	*
	* @see  parent::fetch()
	* @uses parent::reset()
	*/
	public function __construct($version = '3.0')
	{
		$this->reset($version);
	}

	public function setVersion($val='3.0')
	{
		$this->set('VERSION',$val);
	}

	/**
	* Validates parameter names and values based on the vCard version
	* (2.1 or 3.0).
	*
	* @access public
	* @param  string $name The parameter name (e.g., TYPE or ENCODING).
	*
	* @param  string $text The parameter value (e.g., HOME or BASE64).
	*
	* @param  string $prop Optional, the propety name (e.g., ADR or PHOTO).
	*						Only used for error messaging.
	*
	* @param  int $iter Optional, the iteration of the property.
	*						Only used for error messaging.
	*
	* @return mixed	Boolean true if the parameter is valid
	* @throws File_IMC_Exception ... if not.
	*
	* @uses self::validateParam21()
	* @uses self::validateParam30()
	*/
	public function validateParam($name, $text, $prop=null, $iter=null)
	{
		$name = strtoupper($name);
		$text = strtoupper($text);
		// all param values must have only the characters A-Z 0-9 and -.
		if (preg_match('/[^a-zA-Z0-9\-]/i', $text)) {
			throw new File_IMC_Exception(
				"vCard [$prop] [$iter] [$name]: The parameter value may contain only a-z, A-Z, 0-9, and dashes (-).",
				FILE_IMC::ERROR_INVALID_PARAM);
		}
		if ( $this->value['VERSION'][0][0][0] == '2.1' ) {
			return $this->_validateParam21($name, $text, $prop, $iter);
		} elseif ( $this->value['VERSION'][0][0][0] == '3.0' ) {
			return $this->_validateParam30($name, $text, $prop, $iter);
		}
		throw new File_IMC_Exception(
			"[$prop] [$iter] Unknown vCard version number or other error.",
			FILE_IMC::ERROR);
	}

	/**
	 * Validate parameters with 2.1 vcards.
	 *
	 * @access private
	 * @param string $name The parameter name (e.g., TYPE or ENCODING).
	 * @param string $text The parameter value (e.g., HOME or BASE64).
	 * @param string $prop the property name (e.g., ADR or PHOTO).
	 *						Only used for error messaging.
	 * @param int $iter Optional, the iteration of the property.
	 *						Only used for error messaging.
	 * @return boolean
	 */
	protected function _validateParam21($name, $text, $prop, $iter)
	{
		// Validate against version 2.1 (pretty strict)
		$x_val = strpos($text,'X-') === 0;
		switch ($name) {
		case 'TYPE':
			static $types = array (
				// ADR
				'DOM', 'INTL', 'POSTAL', 'PARCEL','HOME', 'WORK',
				// TEL
				'PREF','VOICE', 'FAX', 'MSG', 'CELL', 'PAGER', 'BBS', 'MODEM', 'CAR', 'ISDN', 'VIDEO',
				//EMAIL
				'AOL', 'APPLELINK', 'ATTMAIL', 'CIS', 'EWORLD','INTERNET',
					'IBMMAIL', 'MCIMAIL','POWERSHARE', 'PRODIGY', 'TLX', 'X400',
				//PHOTO & LOGO
				'GIF', 'CGM', 'WMF', 'BMP', 'MET', 'PMB', 'DIB', 'PICT', 'TIFF',
					'PDF', 'PS', 'JPEG', 'MPEG', 'MPEG2', 'AVI', 'QTIME',
				//SOUND
				'WAVE', 'AIFF', 'PCM',
				// KEY
				'X509', 'PGP'
			);
			$result = ( in_array($text, $types) || $x_val );
			break;
		case 'ENCODING':
			$vals = array('7BIT','8BIT','BASE64','QUOTED-PRINTABLE');
			$result = ( in_array($text, $vals) || $x_val );
			break;
		case 'CHARSET':  // all charsets are OK
		case 'LANGUAGE': // all languages are OK
			$result = true;
			break;
		case 'VALUE':
			$vals = array('INLINE','CONTENT-ID','CID','URL','VCARD');
			$result = ( in_array($text, $vals) || $x_val );
			break;
		default:
			$result = ( strpos($name,'X-') === 0 );
			/*
			if ( !$result )
				throw new File_IMC_Exception(
					'vCard 2.1 ['.$prop.']['.$iter.']: "'.$name.'" is an unknown or invalid parameter name.',
					FILE_IMC::ERROR_INVALID_PARAM);
			*/
			break;
		}
		/*
		if ( !$result )
			throw new File_IMC_Exception(
				'vCard 2.1 ['.$prop.']['.$iter.']: "'.$text.'" is not a recognized '.$name.' value.',
				FILE_IMC::ERROR_INVALID_PARAM);
		*/
		return $result;
	}

	/**
	 * Validate parameters with 3.0 vcards.
	 *
	 * @access private
	 * @param string $name The parameter name (e.g., TYPE or ENCODING).
	 * @param string $text The parameter value (e.g., HOME or BASE64).
	 * @param string $prop the property name (e.g., ADR or PHOTO).
	 *						Only used for error messaging.
	 * @param int $iter the iteration of the property.
	 *						Only used for error messaging.
	 * @return boolean
	 */
	protected function _validateParam30($name, $text, $prop, $iter)
	{
		// Validate against version 3.0 (pretty lenient)
		$x_val = strpos($text,'X-') === 0;
		switch ($name) {
		case 'TYPE':     // all types are OK
		case 'LANGUAGE': // all languages are OK
			$result = true;
			break;
		case 'ENCODING':
			$vals = array('8BIT','B');
			$result = ( in_array($text, $vals) || $x_val );
			break;
		case 'VALUE':
			$vals = array('BINARY','PHONE-NUMBER','TEXT','URI','UTC-OFFSET','VCARD');
			$result = ( in_array($text, $vals) || $x_val );
			break;
		default:
			$result = ( strpos($name,'X-') === 0 );
			/*
			if ( !$result )
				throw new File_IMC_Exception(
					'vCard 3.0 ['.$prop.']['.$iter.']: "'.$name.'" is an unknown or invalid parameter name.',
					FILE_IMC::ERROR_INVALID_PARAM);
			*/
			break;
		}
		/*
		if ( !$result )
			throw new File_IMC_Exception(
				'vCard 3.0 ['.$prop.']['.$iter.']: "'.$text.'" is not a recognized '.$name.' value.',
				FILE_IMC::ERROR_INVALID_PARAM);
		*/
		return $result;
	}

	/**
	* Sets the value of one entire ADR iteration.
	*
	* @access private
	* @param array address components
	*   post-office-box
	*   extended-address
	*   street-address
	*   locality		: (e.g., city)
	*   region			: (e.g., state, province, or governorate)
	*   postal-code		: (e.g., ZIP code)
	*	country-name
	*  value may be passed as a numeric or key/value array
	*  (keys coming from hCard microformat specification)
	*  each component may be a String (one repetition) or array (multiple reptitions)
	* @param int iteration
	* @return $this
	*/
	protected function _setADR($value, $iter)
	{
		$keys = array(
			'post-office-box',
			'extended-address',
			'street-address',
			'locality',
			'region',
			'postal-code',
			'country-name',
		);
		foreach ( $keys as $i => $k )
		{
			if ( isset($value[$k]) )
				$value[$i] = $value[$k];
			if ( !isset($value[$i]) )
				$value[$i] = '';
		}
		$this->setValue('ADR', $iter, FILE_IMC::VCARD_ADR_POB,       $value[0]);
		$this->setValue('ADR', $iter, FILE_IMC::VCARD_ADR_EXTEND,    $value[1]);
		$this->setValue('ADR', $iter, FILE_IMC::VCARD_ADR_STREET,    $value[2]);
		$this->setValue('ADR', $iter, FILE_IMC::VCARD_ADR_LOCALITY,  $value[3]);
		$this->setValue('ADR', $iter, FILE_IMC::VCARD_ADR_REGION,    $value[4]);
		$this->setValue('ADR', $iter, FILE_IMC::VCARD_ADR_POSTCODE,  $value[5]);
		$this->setValue('ADR', $iter, FILE_IMC::VCARD_ADR_COUNTRY,   $value[6]);
	}

	/**
	* Sets the FN property of the card.  If no text is passed as the
	* FN value, constructs an FN automatically from N property.
	*
	* @access private
	* @param string $text Override the automatic generation of FN from N
	*    elements with the specified text.
	* @return mixed Void on success
	* @throws File_IMC_Exception ... on failure.
	*/
	protected function _setFN($text=null, $iter)
	{
		if ( $text === null ) {
			// no text was specified for the FN, so build it
			// from the current N property if an N exists
			if ( is_array($this->value['N']) ) {
				// build from N.
				// first (given) name, first iteration, first repetition
				$text .= $this->getValue('N', 0, FILE_IMC::VCARD_N_GIVEN, 0);
				// add a space after, if there was text
				if ($text != '') {
					$text .= ' ';
				}
				// last (family) name, first iteration, first repetition
				$text .= $this->getValue('N', 0, FILE_IMC::VCARD_N_FAMILY, 0);
				// add a space after, if there was text
				if ($text != '') {
					$text .= ' ';
				}
				// last-name suffix, first iteration, first repetition
				$text .= $this->getValue('N', 0, FILE_IMC::VCARD_N_SUFFIX, 0);
			} else {
				// no N exists, and no FN was set, so return.
				throw new File_IMC_Exception('FN not specified and N not set; cannot set FN.',FILE_IMC::ERROR_PARAM_NOT_SET);
			}
		}
		$this->setValue('FN', $iter, 0, $text);
	}

	/**
	* Sets the GEO property (both latitude and longitude)
	*
	* @access private
	* @param array coords lat and lon
	*     value may be passed as a numeric or key/value array
	*     (keys coming from geo microformat specification)
	* @param int iteration
	* @return $this
	*/
	protected function _setGEO($value, $iter)
	{
		$keys = array(
			'latitude',
			'longitude',
		);
		foreach ( $keys as $i => $k )
		{
			if ( isset($value[$k]) )
				$value[$i] = $value[$k];
			if ( !isset($value[$i]) )
				$value[$i] = '';
		}
		$this->setValue('GEO', $iter, FILE_IMC::VCARD_GEO_LAT, $value[0]);
		$this->setValue('GEO', $iter, FILE_IMC::VCARD_GEO_LON, $value[1]);
	}

	/**
	* Sets the full N property of the vCard.
	*
	* @access private
	* @param array $value name comonents
	*	family-name		: family/last name.
	*	given-name		: given/first name.
	*	additional-name	: additional/middle name.
	*	honorific-prefix: prefix such as Mr., Miss, etc.
	*	honorific-suffix: suffix such as III, Jr., Ph.D., etc.
	* value may be passed as a numeric or key/value array
	*   (keys coming from hCard microformat specification)
	* each component may be a string or array
	*/
	protected function _setN($value,$iter)
	{
		$keys = array(
			'family-name',
			'given-name',
			'additional-name',
			'honorific-prefix',
			'honorific-suffix',
		);
		foreach ( $keys as $i => $k )
		{
			if ( isset($value[$k]) )
				$value[$i] = $value[$k];
			if ( !isset($value[$i]) )
				$value[$i] = '';
		}
		$this->setValue('N', $iter, FILE_IMC::VCARD_N_FAMILY,	$value[0]);
		$this->setValue('N', $iter, FILE_IMC::VCARD_N_GIVEN,	$value[1]);
		$this->setValue('N', $iter, FILE_IMC::VCARD_N_ADDL,		$value[2]);
		$this->setValue('N', $iter, FILE_IMC::VCARD_N_PREFIX,	$value[3]);
		$this->setValue('N', $iter, FILE_IMC::VCARD_N_SUFFIX,	$value[4]);
	}

	/**
	* Sets the full value of the ORG property.
	*
	* The ORG property can have one or more parts (as opposed to
	* repetitions of values within those parts).  The first part is the
	* highest-level organization, the second part is the next-highest,
	* the third part is the third-highest, and so on.  There can by any
	* number of parts in one ORG iteration.  (This is different from
	* other properties, such as NICKNAME, where an iteration has only
	* one part but may have many repetitions within that part.)
	*
	* @access private
	* @param mixed $value String (one ORG part) or array (of ORG parts)
	*     to use as the value for the property iteration.
	* @param int iteration
	*/
	protected function _setORG($value,$iter)
	{
		$keys = array(
			'organization-name',
			'organization-unit',	// may pass an array
		);
		settype($value, 'array');
		foreach ( $keys as $i => $k )
		{
			if ( isset($value[$k]) )
			{
				$value[] = $value[$k];
				unset($value[$k]);
			}
		}
		if ( isset($this->value['ORG'][$iter]) ) {
			// clear existing value
			unset($this->value['ORG'][$iter]);
		}
		foreach ( $value as $k => $v) {
			settype($v, 'array');
			foreach ( $v as $v2 ) {
				if ( !empty($v2) )
					$this->setValue('ORG', $iter, $k, $v2);
			}
		}
	}

	/**
	* Gets back the value of one ADR property iteration.
	*
	* @access private
	* @param int $iter The property iteration-number to get the value for.
	* @return mixed The value of this property iteration, or ...
	* @throws File_IMC_Exception ... if the iteration is not valid.
	*/
	protected function _getADR($iter)
	{
		if (! is_integer($iter) || $iter < 0) {
			throw new File_IMC_Exception(
				'ADR iteration number not valid.',
				FILE_IMC::ERROR_INVALID_ITERATION);
		}
		return $this->getMeta('ADR', $iter)
			.$this->getValue('ADR', $iter, FILE_IMC::VCARD_ADR_POB) . ';'
			.$this->getValue('ADR', $iter, FILE_IMC::VCARD_ADR_EXTEND) . ';'
			.$this->getValue('ADR', $iter, FILE_IMC::VCARD_ADR_STREET) . ';'
			.$this->getValue('ADR', $iter, FILE_IMC::VCARD_ADR_LOCALITY) . ';'
			.$this->getValue('ADR', $iter, FILE_IMC::VCARD_ADR_REGION) . ';'
			.$this->getValue('ADR', $iter, FILE_IMC::VCARD_ADR_POSTCODE) . ';'
			.$this->getValue('ADR', $iter, FILE_IMC::VCARD_ADR_COUNTRY);
	}

	/**
	* Gets back the value of the GEO property.
	*
	* @access private
	* @param int $iter The property iteration-number to get
	* @return string The value of this property.
	*/
	protected function _getGEO($iter)
	{
		return $this->getMeta('GEO', $iter)
			.$this->getValue('GEO', $iter, FILE_IMC::VCARD_GEO_LAT, 0) . ';'
			.$this->getValue('GEO', $iter, FILE_IMC::VCARD_GEO_LON, 0);
	}

	/**
	* Gets back the full N property
	*
	* @access private
	* @param int $iter The property iteration-number to get the value for.
	* @return string
	*/
	protected function _getN($iter)
	{
		return $this->getMeta('N', $iter)
			.$this->getValue('N', $iter, FILE_IMC::VCARD_N_FAMILY) . ';'
			.$this->getValue('N', $iter, FILE_IMC::VCARD_N_GIVEN) . ';'
			.$this->getValue('N', $iter, FILE_IMC::VCARD_N_ADDL) . ';'
			.$this->getValue('N', $iter, FILE_IMC::VCARD_N_PREFIX) . ';'
			.$this->getValue('N', $iter, FILE_IMC::VCARD_N_SUFFIX);
	}

	/**
	* Gets back the value of the ORG property.
	*
	* @access private
	* @param int $iter The property iteration-number to get the value for.
	* @return string The value of this property.
	*/
	protected function _getORG($iter)
	{
		$text	= $this->getMeta('ORG', $iter);
		$parts	= count($this->value['ORG'][$iter]);
		$last = $parts - 1;
		for ( $part = 0; $part < $parts; $part++ ) {
			$text .= $this->getValue('ORG', $iter, $part);
			if ( $part != $last ) {
				$text .= ';';
			}
		}
		return $text;
	}

	/**
	* Sets the value of the specified property
	*   for PHOTO, LOGO, SOUND, & KEY properties:
	*		if a filepath is passed:, automatically base64-encodes
	*			and sets ENCODING parameter
	*		if a URL is passed, automatically sets the VALUE=URL|URI parameter
	*
	* _setPROPERTY($value,$iter) method will be used if exists  ( ie _setADR() )
	*
	* @access public
	*
	* @param string property
	* @param mixed value
	*       when property is ADR, GEO, or N:  value is an array
	*			additionaly, the array may be an associateive array
	*			ADR: 	post-office-box, extended-address, street-address, locality, region, postal-code, country-name
	*			GEO:	latitude, longitude
	*			N:		family-name, given-name, additional-name, honorific-prefix, honorific-suffix
	*       when property is ORG, value may be an string or array
	*			ORG		'organization-name','organization-unit'
	*       for all other properties, value is a string
	* @param mixed iteration default = 0; pass 'new' to add an iteration
	* @return $this
	*/
	public function set($prop,$value,$iter=0)
	{
		$prop = strtoupper(trim($prop));
		if ( $iter === 'new' )
			$iter = isset($this->value[$prop])
				? count($this->value[$prop])
				: 0;
		elseif ( !is_integer($iter) || $iter < 0) {
			throw new File_IMC_Exception(
				$prop.' iteration number not valid.', FILE_IMC::ERROR_INVALID_ITERATION
			);
		}
		$method = '_set'.$prop;
		if ( method_exists($this, $method) ) {
			call_user_func(array($this,$method), $value, $iter);
		}
		else {
			if ( $prop == 'VERSION' && !in_array($value,array('2.1','3.0')) )
				throw new File_IMC_Exception('Version must be 3.0 or 2.1 to be valid.', FILE_IMC::ERROR_INVALID_VCARD_VERSION);
			elseif ( in_array($prop,array('PHOTO','LOGO','SOUND','KEY')) ) {
				if ( file_exists($value) )
					$value = base64_encode(file_get_contents($value));
			}
			$this->setValue($prop, $iter, 0, $value);
			if ( in_array($prop,array('PHOTO','LOGO','SOUND','KEY')) ) {
				$ver = $this->getValue('VERSION');
				if ( preg_match('#^(https?|ftp)://#',$value) ) {
					$this->addParam('VALUE', $ver == '2.1' ? 'URL' : 'URI' );
				}
				else {
					$this->addParam('ENCODING', $ver == '2.1' ? 'BASE64' : 'B' );
				}
			}
		}
		return $this;
	}

	/**
	* Gets back the vcard line of the specified property (property name, params, & value)
	*    this func removes the need for all the public getXxx functions...
	*      uses the protected methods: _getADR, _getGEO, _getN, & _getORG
	* If an encoding parameter has been specified, then it is assumed that the value has already
	* If no encoding specified, the value will be encoded automatically as necessary
	*
	* _getPROPERTY($iter) method will be used if exists  ( ie _getADR() )
	*
	* @access public
	*
	* @param string property
	* @param int iteration default = 0
	* @return string The value of the property
	*/
	public function get($prop,$iter=0)
	{
		$return = '';
		$prop = strtoupper(trim($prop));
		if ( !is_integer($iter) || $iter < 0) {
			throw new File_IMC_Exception(
				$prop.' iteration number not valid.', FILE_IMC::ERROR_INVALID_ITERATION
			);
		}
		$this->encode($prop,$iter);
		$method = '_get'.$prop;
		if ( method_exists($this, $method) )
			$return = call_user_func(array($this,$method),$iter);
		else
			$return = $this->getMeta($prop, $iter) . $this->getValue($prop, $iter, 0);
		return $return;
	}

	/**
	* Fetches a full vCard text block based on $this->value and
	* $this->param. The order of the returned properties is similar to
	* their order in RFC 2426.  Honors the value of
	* $this->value['VERSION'] to determine which vCard properties are
	* returned (2.1- or 3.0-compliant).
	*
	* @access public
	* @uses self::get()
	*
	* @return string A properly formatted vCard text block.
	*/
	public function fetch()
	{
		$prop_dfn_default = array(
			'vers'	=> array('2.1','3.0'),
			'req'	=> array(),				// versions required in
			'limit'	=> false,				// just one value allowed
		);
		$prop_dfns = array(
			'VERSION'	=> array( 'req' => array('2.1','3.0'),	'limit' => true ),
			'FN'		=> array( 'req'	=> array('3.0') ),
			'N'			=> array( 'req'	=> array('2.1','3.0') ),
			'PROFILE'	=> array( 'vers'=> array('3.0'),		'limit' => true ),
			'NAME'		=> array( 'vers'=> array('3.0'),		'limit' => true ),
			'SOURCE'	=> array( 'vers'=> array('3.0'),		'limit' => true ),
			'NICKNAME'	=> array( 'vers'=> array('3.0') ),
			'PHOTO' 	=> array( ),
			'BDAY'		=> array( ),
			'ADR'		=> array( ),	// 'limit' => false
			'LABEL'		=> array( ),	// 'limit' => false
			'TEL'		=> array( ),	// 'limit' => false
			'EMAIL'		=> array( ),	// 'limit' => false
			'MAILER'	=> array( ),
			'TZ'		=> array( ),
			'GEO'		=> array( ),
			'TITLE'		=> array( ),
			'ROLE'		=> array( ),
			'LOGO'		=> array( ),
			'AGENT'		=> array( ),
			'ORG'		=> array( ),
			'CATEGORIES'=> array( 'vers' => array('3.0') ),
			'NOTE'		=> array( ),
			'PRODID'	=> array( 'vers' => array('3.0') ),
			'CLASS'		=> array( 'vers' => array('3.0') ),
			'REV'		=> array( ),
			'SORT-STRING'=>array( 'vers' => array('3.0') ),
			'SOUND'		=> array( ),
			'UID'		=> array( ),
			'URL'		=> array( ),
			'CLASS'		=> array( 'vers' => array('3.0') ),
			'KEY'		=> array( ),
		);
		$ver = $this->getValue('VERSION');
		$newline = $ver == '2.1'
			? "\r\n"	// version 2.1 uses \r\n for new lines
			: "\n";		// version 3.0 uses \n

		// initialize the vCard lines
		$lines = array();

		$lines[] = 'BEGIN:VCARD';

		$prop_keys = array_keys($this->value);

		foreach ( $prop_dfns as $prop => $prop_dfn ) {
			if ( !is_array($prop_dfn) )
				$prop_dfn = array( 'func' => $prop_dfn );
			$prop_dfn = array_merge($prop_dfn_default,$prop_dfn);
			if ( false !== $key = array_search($prop,$prop_keys) );
				unset($prop_keys[$key]);
			$prop_exists = isset($this->value[$prop]) && is_array($this->value[$prop]);
			if ( $prop == 'PROFILE' && in_array($ver,$prop_dfn['vers']) )
				$lines[] = 'PROFILE:VCARD';	// special case... don't really care what current val is
			elseif ( $prop_exists ) {
				if ( in_array($ver,$prop_dfn['vers']) ) {
					foreach ( $this->value[$prop] as $iter => $val ) {
						$lines[] = $this->get($prop,$iter);
						if ( $prop_dfn['limit'] )
							break;
					}
				}
			}
			elseif ( in_array($ver,$prop_dfn['req']) ) {
				throw new File_IMC_Exception($prop.' not set (required).',FILE_IMC::ERROR_PARAM_NOT_SET);
			}
		}
		// now build the extension properties
		foreach ( $prop_keys as $prop ) {
			if ( strpos($prop,'X-') === 0 ) {
				foreach ($this->value[$prop] as $key => $val) {
					$lines[] = $this->get($prop,$key);
				}
			}
		}

		$lines[] = 'END:VCARD';

		// fold lines at 75 characters
		$regex = '/(.{1,75})/i';
		foreach ( $lines as $key => $val ) {
			if (strlen($val) > 75) {
				// we trim to drop the last newline, which will be added
				// again by the implode function at the end of fetch()
				$lines[$key] = trim(preg_replace($regex, "\\1$newline ", $val));
			}
		}

		// compile the array of lines into a single text block and return
		return implode($newline, $lines);
	}

	/********* deprecated methods *********/

	/**
	* @deprecated
	* @see self::set()
	*/
	public function addAddress() {
		$args = func_get_args();
		return $this->set('ADR',$args);
	}

	/**
	* @deprecated
	* @see self::set()
	*/
	public function addCategories($val) {
		return $this->set('CATEGORIES',$val);
	}

	/**
	* @deprecated
	* @see self::set()
	*/
	public function addEmail($val) {
		return $this->set('EMAIL',$val);
	}

	/**
	* @deprecated
	* @see self::set()
	*/
	public function addLabel($val) {
		return $this->set('LABEL',$val);
	}

	/**
	* @deprecated
	* @see self::set()
	*/
	public function addNickname($val) {
		return $this->set('NICKNAME',$val);
	}

	/**
	* @deprecated
	* @see self::set()
	*/
	public function addOrganization($val) {
		return $this->set('ORG',$val);
	}

	/**
	* @deprecated
	* @see self::set()
	*/
	public function addTelephone($val) {
		return $this->set('TEL',$val);
	}

	/**
	* @deprecated
	* @see self::get()
	*/
	public function getAddress($iter=0) {
		return $this->get('ADR',$iter);
	}

	/**
	* @deprecated
	* @see self::get()
	*/
	public function getAgent($iter=0) {
		return $this->get('AGENT',$iter);
	}

	/**
	* @deprecated
	* @see self::get()
	*/
	public function getBirthday($iter=0) {
		return $this->get('BDAY',$iter);
	}

	/**
	* @deprecated
	* @see self::get()
	*/
	public function getCategories($iter=0) {
		return $this->get('CATEGORIES',$iter);
	}

	/**
	* @deprecated
	* @see self::get()
	*/
	public function getClass($iter=0) {
		return $this->get('CLASS',$iter);
	}

	/**
	* @deprecated
	* @see self::get()
	*/
	public function getEmail($iter=0) {
		return $this->get('EMAIL',$iter);
	}

	/**
	* @deprecated
	* @see self::get()
	*/
	public function getFormattedName($iter=0) {
		return $this->get('FN',$iter);
	}

	/**
	* @deprecated
	* @see self::get()
	*/
	public function getGeo($iter=0) {
		return $this->get('GEO',$iter);
	}

	/**
	* @deprecated
	* @see self::get()
	*/
	public function getKey($iter=0) {
		return $this->get('KEY',$iter);
	}

	/**
	* @deprecated
	* @see self::get()
	*/
	public function getLabel($iter=0) {
		return $this->get('LABEL',$iter);
	}

	/**
	* @deprecated
	* @see self::get()
	*/
	public function getLogo($iter=0) {
		return $this->get('LOGO',$iter);
	}

	/**
	* @deprecated
	* @see self::get()
	*/
	public function getMailer($iter=0) {
		return $this->get('MAILER',$iter);
	}

	/**
	* @deprecated
	* @see self::get()
	*/
	public function getName($iter=0) {
		return $this->get('N',$iter);
	}

	/**
	* @deprecated
	* @see self::get()
	*/
	public function getNickname($iter=0) {
		return $this->get('NICKNAME',$iter);
	}

	/**
	* @deprecated
	* @see self::get()
	*/
	public function getNote($iter=0) {
		return $this->get('NOTE',$iter);
	}

	/**
	* @deprecated
	* @see self::get()
	*/
	public function getOrganization($iter=0) {
		return $this->get('ORG',$iter);
	}

	/**
	* @deprecated
	* @see self::get()
	*/
	public function getPhoto($iter=0) {
		return $this->get('PHOTO',$iter);
	}

	/**
	* @deprecated
	* @see self::get()
	*/
	public function getProductID($iter=0) {
		return $this->get('PRODID',$iter);
	}

	/**
	* @deprecated
	* @see self::get()
	*/
	public function getRevision($iter=0) {
		return $this->get('REV',$iter);
	}

	/**
	* @deprecated
	* @see self::get()
	*/
	public function getRole($iter=0) {
		return $this->get('ROLE',$iter);
	}

	/**
	* @deprecated
	* @see self::get()
	*/
	public function getSortString($iter=0) {
		return $this->get('SORT-STRING',$iter);
	}

	/**
	* @deprecated
	* @see self::get()
	*/
	public function getSound($iter=0) {
		return $this->get('SOUND',$iter);
	}

	/**
	* @deprecated
	* @see self::get()
	*/
	public function getSource($iter=0) {
		return $this->get('SOURCE',$iter);
	}

	/**
	* @deprecated
	* @see self::get()
	*/
	public function getSourceName($iter=0) {
		return $this->get('NAME',$iter);
	}

	/**
	* @deprecated
	* @see self::get()
	*/
	public function getTZ($iter=0) {
		return $this->get('TZ',$iter);
	}

	/**
	* @deprecated
	* @see self::get()
	*/
	public function getTelephone($iter=0) {
		return $this->get('TEL',$iter);
	}

	/**
	* @deprecated
	* @see self::get()
	*/
	public function getTitle($iter=0) {
		return $this->get('TITLE',$iter);
	}

	/**
	* @deprecated
	* @see self::get()
	*/
	public function getURL($iter=0) {
		return $this->get('URL',$iter);
	}

	/**
	* @deprecated
	* @see self::get()
	*/
	public function getUniqueID($iter=0) {
		return $this->get('UID',$iter);
	}

	/**
	* @deprecated
	* @see self::set()
	*/
	public function setAgent($val) {
		return $this->set('AGENT',$val);
	}

	/**
	* @deprecated
	* @see self::set()
	*/
	public function setBirthday($val) {
		return $this->set('BDAY',$val);
	}

	/**
	* @deprecated
	* @see self::set()
	*/
	public function setClass($val) {
		return $this->set('CLASS',$val);
	}

	/**
	* @deprecated
	* @see self::set()
	*/
	public function setFormattedName($val) {
		return $this->set('FN',$val);
	}

	/**
	* @deprecated
	* @see self::set()
	*/
	public function setGeo() {
		$args = func_get_args();
		return $this->set('GEO',$args);
	}

	/**
	* @deprecated
	* @see self::set()
	*/
	public function setKey($val) {
		return $this->set('KEY',$val);
	}

	/**
	* @deprecated
	* @see self::set()
	*/
	public function setLogo($val) {
		return $this->set('LOGO',$val);
	}

	/**
	* @deprecated
	* @see self::set()
	*/
	public function setMailer($val) {
		return $this->set('MAILER',$val);
	}

	/**
	* @deprecated
	* @see self::set()
	*/
	public function setName() {
		$args = func_get_args();
		return $this->set('N',$args);
	}

	/**
	* @deprecated
	* @see self::set()
	*/
	public function setNote($val) {
		return $this->set('NOTE',$val);
	}

	/**
	* @deprecated
	* @see self::set()
	*/
	public function setPhoto($val) {
		return $this->set('PHOTO',$val);
	}

	/**
	* @deprecated
	* @see self::set()
	*/
	public function setProductID($val) {
		return $this->set('PRODID',$val);
	}

	/**
	* @deprecated
	* @see self::set()
	*/
	public function setRevision($val) {
		return $this->set('REV',$val);
	}

	/**
	* @deprecated
	* @see self::set()
	*/
	public function setRole($val) {
		return $this->set('ROLE',$val);
	}

	/**
	* @deprecated
	* @see self::set()
	*/
	public function setSortString($val) {
		return $this->set('SORT-STRING',$val);
	}

	/**
	* @deprecated
	* @see self::set()
	*/
	public function setSound($val) {
		return $this->set('SOUND',$val);
	}

	/**
	* @deprecated
	* @see self::set()
	*/
	public function setSource($val) {
		return $this->set('SOURCE',$val);
	}

	/**
	* @deprecated
	* @see self::set()
	*/
	public function setSourceName($val) {
		return $this->set('NAME',$val);
	}

	/**
	* @deprecated
	* @see self::set()
	*/
	public function setTZ($val) {
		return $this->set('TZ',$val);
	}

	/**
	* @deprecated
	* @see self::set()
	*/
	public function setTitle($val) {
		return $this->set('TITLE',$val);
	}

	/**
	* @deprecated
	* @see self::set()
	*/
	public function setURL($val) {
		return $this->set('URL',$val);
	}

	/**
	* @deprecated
	* @see self::set()
	*/
	public function setUniqueID($val) {
		return $this->set('UID',$val);
	}

}

?>