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
 * @author   Marshall Roch <mroch@php.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version  SVN: $Id$
 * @link     http://pear.php.net/package/File_IMC
 */

/**
* This class handles vCard and vCalendar files, formats designed by the
* Internet Mail Consortium (IMC).
*
* vCard automates the exchange of personal information typically found
* on a traditional business card. vCard is used in applications such as
* Internet mail, voice mail, Web browsers, telephony applications, call
* centers, video conferencing, PIMs (Personal Information Managers),
* PDAs (Personal Data Assistants), pagers, fax, office equipment, and
* smart cards.
*
* vCalendar defines a transport and platform-independent format for
* exchanging calendaring and scheduling information in an easy, automated,
* and consistent manner. It captures information about event and "to-do"
* items that are normally used by applications such as a personal
* information managers (PIMs) and group schedulers. Programs that use
* vCalendar can exchange important data about events so that you can
* schedule meetings with anyone who has a vCalendar-aware program.
*
* This class is capable of building and parsing vCard 2.1, vCard 3.0, and
* vCalendar files.  The vCard code was moved from Contact_Vcard_Build
* and Contact_Vcard_Parse, and the API remains basically the same.
* The only difference is that this package uses a factory pattern:
*
* <code>
*     $parse =& File_IMC::parse('vCard');
*     $build =& File_IMC::build('vCard', '3.0');
* </code>
* instead of
* <code>
*     $parse = new Contact_Vcard_Parse();
*     $build = new Contact_Vcard_Build('3.0');
* </code>
*
* @category File_Formats
* @package  File_IMC
* @author   Paul M. Jones <pmjones@ciaweb.net>
* @author   Marshall Roch <mroch@php.net>
* @license  http://www.opensource.org/licenses/bsd-license.php The BSD License
* @version  Release: @package_version@
* @link     http://pear.php.net/package/File_IMC
* @link     http://www.imc.org/pdi/ IMC's vCard and vCalendar info page
*/
class File_IMC
{
    /**
     * Constants for File_IMC errors.
     * @global
     */
    const ERROR                       = 100;
    const ERROR_INVALID_DRIVER        = 101;
    const ERROR_INVALID_PARAM         = 102;
    const ERROR_INVALID_VCARD_VERSION = 103;
    const ERROR_PARAM_NOT_SET         = 104;
    const ERROR_INVALID_ITERATION     = 105;

    /**
     * Constants for File_IMC vCard "N" component positions.
     * @global
     */
    const VCARD_N_FAMILY = 0;
    const VCARD_N_GIVEN  = 1;
    const VCARD_N_ADDL   = 2;
    const VCARD_N_PREFIX = 3;
    const VCARD_N_SUFFIX = 4;

    /**
     * Constants for File_IMC vCard "ADR" component positions.
     * @global
     */
    const VCARD_ADR_POB      = 0;
    const VCARD_ADR_EXTEND   = 1;
    const VCARD_ADR_STREET   = 2;
    const VCARD_ADR_LOCALITY = 3;
    const VCARD_ADR_REGION   = 4;
    const VCARD_ADR_POSTCODE = 5;
    const VCARD_ADR_COUNTRY  = 6;

    /**
     * Constants for File_IMC vCard "GEO" component positions.
     * @global
     */
    const VCARD_GEO_LAT = 0;
    const VCARD_GEO_LON = 1;

    /**
	 * SPL-compatible autoloader.
	 *
     * @param string $className Name of the class to load.
     *
     * @return boolean
     */
    public static function autoload($className)
    {
        if (strpos($className, 'File_IMC') === false) {
            return false;
		}
        return include str_replace('_', '/', $className) . '.php';
    }

    /**
    * Builder factory
    *
    * Creates an instance of the correct parser class, based on the
    * parameter passed. For example, File_IMC::parse('vCard') creates
    * a new object to parse a vCard file.
    *
    * @param string $format  Type of file to parse, vCard or vCalendar
    * @param mixed  $version Optionally, the version.
    *
    * @return mixed
    * @throws File_IMC_Exception In case the driver is not found/available.
    */
    public static function build($format, $version = null)
    {
        $format = trim($format);
        if (empty($format)) {
            throw new File_IMC_Exception('No driver.', self::ERROR_INVALID_DRIVER);
        }

        // fix^H^H^H^force case
        $format = ucfirst(strtolower($format));

        $fileName  = 'File/IMC/Build/'. $format . '.php';
        $className = 'File_IMC_Build_'. $format;

        if (!class_exists($className, false)) {
            @include_once $fileName;
        }

        if (!class_exists($className, false)) {
            throw new File_IMC_Exception(
                'No builder driver exists for format: ' . $format,
                self::ERROR_INVALID_DRIVER);
        }

        if ($version !== null) {
            $class = new $className($version);
        } else {
            $class = new $className;
        }
        return $class;
    }

    /**
    * Parser factory
    *
    * Creates an instance of the correct parser class, based on the
    * parameter passed. For example, File_IMC::parse('vCard') creates
    * a new object to parse a vCard file.
    *
    * @param string $format Type of file to parse, vCard or vCalendar
    *
    * @return mixed
    * @throws File_IMC_Exception If no parse is found/available.
    */
    public static function parse($format)
    {
        $format = trim($format);
        if (empty($format)) {
            throw new File_IMC_Exception('No driver.', self::ERROR_INVALID_DRIVER);
        }

        // fix^H^H^H^force case
        $format = ucfirst(strtolower($format));

        $fileName  = 'File/IMC/Parse/'. $format . '.php';
        $className = 'File_IMC_Parse_'. $format;

        if (!class_exists($className, false)) {
            @include_once $fileName;
        }

        if (!class_exists($className, false)) {
            throw new File_IMC_Exception(
                'No parser driver exists for format: ' . $format,
                self::ERROR_INVALID_DRIVER);
        }
        return new $className;
    }
}

spl_autoload_register(array('File_IMC', 'autoload'));
