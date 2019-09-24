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
 * @version  SVN: $Id$
 * @link     http://pear.php.net/package/File_IMC
 */

/**
 * This class is a parser for vCards.
 *
 * Parses vCard 2.1 and 3.0 sources from file or text into a structured
 * array.
 *
 * Usage:
 * <code>
 *     // include this class file
 *     require_once 'File/IMC.php';
 *
 *     // instantiate a parser object
 *     $parse = new File_IMC::parse('vCard');
 *
 *     // parse a vCard file and store the data
 *     // in $cardinfo
 *     $cardinfo = $parse->fromFile('sample.vcf');
 *
 *     // view the card info array
 *     echo '<pre>';
 *     print_r($cardinfo);
 *     echo '</pre>';
 * </code>
 *
 * @author Paul M. Jones <pmjones@ciaweb.net>
 *
 * @package File_IMC
 *
 */
class File_IMC_Parse_Vcard extends File_IMC_Parse
{
    /**
	 * Return version.
	 *
	 * @uses parent::$data
	 */
	public function getVersion()
	{
		return $this->data['VCARD'][0]['VERSION'][0]['value'][0][0];
	}

	/**
	* Looks at a line's parameters;
	* if ENCODING parameter is set -> decode the text in-place.
	*
	* @param array $params A parameter array from a vCard line.
	* @param string $text A right-part (after-the-colon part) from a line.
	* @param string $prop The property name.. passed so may have special-cases
	* @return array
	*
	* @uses quoted_printable_decode()
	* @uses base64_decode()
	*/
	protected function _decode(array $params, $text, $prop=null)
	{
		if ( in_array($prop,array('PHOTO','LOGO','SOUND','KEY')) ) {
			// don't decode binary values
			return array($params, $text);
		}
		// loop through each parameter
		foreach ( $params as $param => $param_vals ) {
			// check to see if it's an encoding param
			if ( trim(strtoupper($param)) != 'ENCODING' ) {
				continue;
			}
			// loop through each encoding param value
			foreach ( $param_vals as $k => $param_val ) {
				$param_val == trim(strtoupper($param_val));
				if ( $param_val == 'QUOTED-PRINTABLE' ) {
					$text = quoted_printable_decode($text);
					// remove the encoding param.. as it's no longer encoded!
					unset($params[$param][$k]);
					break;
				}
				elseif ( in_array($param_val,array('BASE64','B')) ) {
					$text = base64_decode($text);
					// remove the encoding param.. as it's no longer encoded!
					unset($params[$param][$k]);
					break;
				}
			}
		}
		return array($params, $text);
	}

	/**
	*
	* Parses a vCard line value identified as being of the "N"
	* (structured name) type-defintion.
	*
	* @param string $text The right-part (after-the-colon part) of a
	* vCard line.
	*
	* @return array An array of key-value pairs where the key is the
	* portion-name and the value is the portion-value.  The value itself
	* may be an array as well if multiple comma-separated values were
	* indicated in the vCard source.
	*
	* @see parent::_splitBySemi()
	* @see parent::_splitByComma()
	*/
	protected function _parseN($text)
	{
		// array_pad makes sure there are the right number of elements
		$tmp = array_pad($this->_splitBySemi($text), 5, '');
		return array(
			$this->_splitByComma($tmp[FILE_IMC::VCARD_N_FAMILY]),
			$this->_splitByComma($tmp[FILE_IMC::VCARD_N_GIVEN]),
			$this->_splitByComma($tmp[FILE_IMC::VCARD_N_ADDL]),
			$this->_splitByComma($tmp[FILE_IMC::VCARD_N_PREFIX]),
			$this->_splitByComma($tmp[FILE_IMC::VCARD_N_SUFFIX])
		);
	}

	/**
	* Parses a vCard line value identified as being of the "ADR"
	* (structured address) type-defintion.
	*
	* @param string $text The right-part (after-the-colon part) of a
	* vCard line.
	*
	* @return array An array of key-value pairs where the key is the
	* portion-name and the value is the portion-value.  The value itself
	* may be an array as well if multiple comma-separated values were
	* indicated in the vCard source.
	*
	*/
	protected function _parseADR($text)
	{
		// array_pad makes sure there are the right number of elements
		$tmp = array_pad($this->_splitBySemi($text), 7, '');
		return array(
			$this->_splitByComma($tmp[FILE_IMC::VCARD_ADR_POB]),
			$this->_splitByComma($tmp[FILE_IMC::VCARD_ADR_EXTEND]),
			$this->_splitByComma($tmp[FILE_IMC::VCARD_ADR_STREET]),
			$this->_splitByComma($tmp[FILE_IMC::VCARD_ADR_LOCALITY]),
			$this->_splitByComma($tmp[FILE_IMC::VCARD_ADR_REGION]),
			$this->_splitByComma($tmp[FILE_IMC::VCARD_ADR_POSTCODE]),
			$this->_splitByComma($tmp[FILE_IMC::VCARD_ADR_COUNTRY])
		);
	}

	/**
	* Parses a vCard line value identified as being of the "NICKNAME"
	* (informal or descriptive name) type-defintion.
	*
	* @param string $text The right-part (after-the-colon part) of a
	* vCard line.
	*
	* @return array An array of nicknames.
	*
	*/
	protected function _parseNICKNAME($text)
	{
		return array($this->_splitByComma($text));
	}

	/**
	* Parses a vCard line value identified as being of the "ORG"
	* (organizational info) type-defintion.
	*
	* @param string $text The right-part (after-the-colon part) of a
	* vCard line.
	*
	* @return array An array of organizations; each element of the array
	* is itself an array, which indicates primary organization and
	* sub-organizations.
	*
	*/
	protected function _parseORG($text)
	{
		$tmp = $this->_splitbySemi($text);
		$list = array();
		foreach ($tmp as $val) {
			$list[] = array($val);
		}
		return $list;
	}

	/**
	* Parses a vCard line value identified as being of the "CATEGORIES"
	* (card-category) type-defintion.
	*
	* @param string $text The right-part (after-the-colon part) of a
	* vCard line.
	*
	* @return mixed An array of categories.
	*
	*/
	protected function _parseCATEGORIES($text)
	{
		return array($this->_splitByComma($text));
	}

	/**
	* Parses a vCard line value identified as being of the "GEO"
	* (geographic coordinate) type-defintion.
	*
	* @param string $text The right-part (after-the-colon part) of a
	* vCard line.
	*
	* @return mixed An array of lat-lon geocoords.
	*/
	protected function _parseGEO($text)
	{
		// array_pad makes sure there are the right number of elements
		$tmp = array_pad($this->_splitBySemi($text), 2, '');
		return array(
			array($tmp[FILE_IMC::VCARD_GEO_LAT]), // lat
			array($tmp[FILE_IMC::VCARD_GEO_LON])  // lon
		);
	}
}

?>