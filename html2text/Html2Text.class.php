<?php

/*************************************************************************
 *                                                                       *
 * class.html2text.inc                                                   *
 *                                                                       *
 *************************************************************************
 *                                                                       *
 * Converts HTML to formatted plain text                                 *
 *                                                                       *
 * Copyright (c) 2003 Jon Abernathy <jon@chuggnutt.com>                  *
 * All rights reserved.                                                  *
 *                                                                       *
 * This script is free software; you can redistribute it and/or modify   *
 * it under the terms of the GNU General Public License as published by  *
 * the Free Software Foundation; either version 2 of the License, or     *
 * (at your option) any later version.                                   *
 *                                                                       *
 * The GNU General Public License can be found at                        *
 * http://www.gnu.org/copyleft/gpl.html.                                 *
 *                                                                       *
 * This script is distributed in the hope that it will be useful,        *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of        *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the          *
 * GNU General Public License for more details.                          *
 *                                                                       *
 * Author(s): Jon Abernathy <jon@chuggnutt.com>                          *
 *                                                                       *
 * Last modified: 11/07/03                                               *
 *                                                                       *
 *************************************************************************/


/**
 * Takes HTML and converts it to formatted, plain text.
 *
 * Thanks to Alexander Krug (http://www.krugar.de/) to pointing out and
 * correcting an error in the regexp search array. Fixed 7/30/03.
 * Updated set_html() function's file reading mechanism, 9/25/03.
 * Thanks to Joss Sanglier (http://www.dancingbear.co.uk/) for adding
 * several more HTML entity codes to the $search and $replace arrays.
 * Updated 11/7/03.
 *
 *
 * @category  vendors
 * @package   html2text
 * @author    Jon Abernathy <jon@chuggnutt.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class html2text_Html2Text
{
    
    
    /**
     * Contains the HTML content to convert.
     *
     * @var string $html
     * @access public
     */
    var $html;
    
    
    /**
     * Contains the converted, formatted text.
     *
     * @var string $text
     * @access public
     */
    var $text;
    
    
    /**
     * Maximum width of the formatted text, in columns.
     *
     * @var integer $width
     * @access public
     */
    var $width = 70;
    
    
    /**
     * List of preg* regular expression patterns to search for,
     * used in conjunction with $replace.
     *
     * @var array $search
     * @access public
     * @see $replace
     */
    var $search = array(
        "/\r/", // Non-legal carriage return
        "/[\n\t]+/", // Newlines and tabs
        '/<script[^>]*>.*?<\/script>/si', // <script>s -- which strip_tags supposedly has problems with
        '/<style[^>]*>.*?<\/style>/si', // <script>s -- which strip_tags supposedly has problems with
        '/<!--.*-->/U', // Comments -- which strip_tags might have problem a with
        '/<h[123][^>]*>(.+?)<\/h[123]>/i', // H1 - H3
        '/<h[456][^>]*>(.+?)<\/h[456]>/i', // H4 - H6
        '/<p[^>]*>/i', // <P>
        '/<br[^>]*>/i', // <br>
        '/<b[^>]*>(.+?)<\/b>/i', // <b>
        '/<i[^>]*>(.+?)<\/i>/i', // <i>
        '/(<ul[^>]*>|<\/ul>)/i', // <ul> and </ul>
        '/<li[^>]*>/i', // <li>
        '/<a.+href="(http\:[^"]+)"[^>]*>(.+?)<\/a>/i', // <a href="">
        "/<a.+href='(http\:[^']+)'[^>]*>(.+?)<\/a>/i", // <a href=''>
        '/<hr[^>]*>/i', // <hr>
        /* New
         '/<table[^>]*>/i',                          // <hr>
         '/<\/table[^>]*>/i',                          // <hr>
         '/<tr[^>]*>/i',                          // <hr>
         '/<\/tr[^>]*>/i',                          // <hr>
         '/<td[^>]*>/i',                          // <hr>
         '/<\/td[^>]*>/i',                          // <hr>
         '/<th[^>]*>/i',                          // <hr>
         '/<\/th[^>]*>/i',                          // <hr> */
        
        '/(<table[^>]*>|<\/table>)/i', // <table> and </table>
        '/(<tr[^>]*>|<\/tr>)/i', // <tr> and </tr>
        '/<td[^>]*>(.+?)<\/td>/i', // <td> and </td>
        '/&nbsp;/i',
        '/&quot;/i',
        '/&gt;/i',
        '/&lt;/i',
        '/&amp;/i',
        '/&copy;/i',
        '/&trade;/i',
        '/&#8220;/',
        '/&#8221;/',
        '/&#8211;/',
        '/&#8217;/',
        '/&#38;/',
        '/&#169;/',
        '/&#8482;/',
        '/&#151;/',
        '/&#147;/',
        '/&#148;/',
        '/&#149;/',
        '/&reg;/i'
    );
    
    
    /**
     * List of pattern replacements corresponding to patterns searched.
     *
     * @var array $replace
     * @access public
     * @see $search
     */
    var $replace = array(
        '', // Non-legal carriage return
        ' ', // Newlines and tabs
        '', // <script>s -- which strip_tags supposedly has problems with
        '', // Comments -- which strip_tags might have problem a with
        '', // Comments -- which strip_tags might have problem a with
        "strtoupper(\"\n\n\\1\n\n\")", // H1 - H3
        "ucwords(\"\n\n\\1\n\n\")", // H4 - H6
        "\n\n\t", // <P>
        "\n", // <br>
        '[b]\\1[/b]', // <b>
        '[i]\\1[/i]', // <i>
        "\n\n", // <ul> and </ul>
        "\t*", // <li>
        '$this->_build_link_list($link_count++, "\\1", "\\2")', // <a href="">
        
        '$this->_build_link_list($link_count++, "\\1", "\\2")', // <a href=''>
        
        "\n[hr]", // <hr>
        /*New
         '[table]',                          // <hr>
         '[/table]',                          // <hr>
         '[tr]',                          // <hr>
         '[/tr]',                          // <hr>
         '[td]',                          // <hr>
         '[/td]',                          // <hr>
         '[td]',                          // <hr>
         '[/td]',                          // <hr> */
        
        "\n\n", // <table> and </table>
        "\n", // <tr> and </tr>
        "\t\\1\t", // <td> and </td>
        ' ',
        '"',
        '>',
        '<',
        '&',
        '(c)',
        '(tm)',
        '"',
        '"',
        '-',
        "'",
        '&',
        '(c)',
        '(tm)',
        '--',
        '"',
        '"',
        '*',
        '(R)'
    );
    
    
    /**
     * @todo Чака за документация...
     */
    var $replaceSimple = array(
        '', // Non-legal carriage return
        ' ', // Newlines and tabs
        '', // <script>s -- which strip_tags supposedly has problems with
        '', // Comments -- which strip_tags might have problem a with
        '', // Comments -- which strip_tags might have problem a with
        "strtoupper(\"\n\n\\1\n\n\")", // H1 - H3
        "ucwords(\"\n\n\\1\n\n\")", // H4 - H6
        "\n\n\t", // <P>
        "\n", // <br>
        '\\1', // <b>
        '\\1', // <i>
        "\n\n", // <ul> and </ul>
        "\t*", // <li>
        '$this->_build_link_list($link_count++, "\\1", "\\2")', // <a href="">
        
        '$this->_build_link_list($link_count++, "\\1", "\\2")', // <a href=''>
        
        "\n_________________________________________________________________", // <hr>
        /*New
         '[table]',                          // <hr>
         '[/table]',                          // <hr>
         '[tr]',                          // <hr>
         '[/tr]',                          // <hr>
         '[td]',                          // <hr>
         '[/td]',                          // <hr>
         '[td]',                          // <hr>
         '[/td]',                          // <hr> */
        
        "\n\n", // <table> and </table>
        "\n", // <tr> and </tr>
        "\t\\1\t", // <td> and </td>
        ' ',
        '"',
        '>',
        '<',
        '&',
        '(c)',
        '(tm)',
        '"',
        '"',
        '-',
        "'",
        '&',
        '(c)',
        '(tm)',
        '--',
        '"',
        '"',
        '*',
        '(R)'
    );
    
    
    /**
     * Indicates whether content in the $html variable has been converted yet.
     *
     * @var boolean $converted
     * @access private
     * @see $html, $text
     */
    var $_converted = FALSE;
    
    
    /**
     * Contains URL addresses from links to be rendered in plain text.
     *
     * @var string $link_list
     * @access private
     * @see _build_link_list()
     */
    var $_link_list;
    
    
    /**
     * Просто конвертира
     */
    function convert2text($html, $simple = FALSE) {
        $this->set_html($html);
        $this->simple = $simple;
        
        return $this->get_text();
    }
    
    
    /**
     * Constructor.
     *
     * If the HTML source string (or file) is supplied, the class
     * will instantiate with that source propagated, all that has
     * to be done it to call get_text().
     *
     * @param string $source HTML content
     * @param boolean $from_file Indicates $source is a file to pull content from
     * @access public
     * @return void
     */
    function set($source = '', $from_file = FALSE)
    {
        if (!empty($source)) {
            $this->set_html($source, $from_file);
        }
    }
    
    
    /**
     * Loads source HTML into memory, either from $source string or a file.
     *
     * @param string $source HTML content
     * @param boolean $from_file Indicates $source is a file to pull content from
     * @access public
     * @return void
     */
    function set_html($source, $from_file = FALSE)
    {
        $this->html = $source;
        
        if ($from_file && file_exists($source)) {
            $fp = fopen($source, 'r');
            $this->html = fread($fp, filesize($source));
            fclose($fp);
        }
        
        $this->_converted = FALSE;
    }
    
    
    /**
     * Returns the text, converted from HTML.
     *
     * @access public
     * @return string
     */
    function get_text()
    {
        if (!$this->_converted) {
            $this->_convert();
        }
        
        return $this->text;
    }
    
    
    /**
     * Prints the text, converted from HTML.
     *
     * @access public
     * @return void
     */
    function print_text()
    {
        print $this->get_text();
    }
    
    
    /**
     * Alias to print_text(), operates identically.
     *
     * @access public
     * @return void
     * @see print_text()
     */
    function p()
    {
        print $this->get_text();
    }
    
    
    /**
     * Workhorse function that does actual conversion.
     *
     * First performs custom tag replacement specified by $search and
     * $replace arrays. Then strips any remaining HTML tags, reduces whitespace
     * and newlines to a readable format, and word wraps the text to
     * $width characters.
     *
     * @access private
     * @return void
     */
    function _convert()
    {
        // Variables used for building the link list
        $link_count = 1;
        $this->_link_list = '';
        
        $text = trim(stripslashes($this->html));
        
        // Run our defined search-and-replace
        if($this->simple) {
            $text = preg_replace($this->search, $this->replaceSimple, $text);
        } else {

            $text = preg_replace($this->search, $this->replace, $text);
        }
        
        // Strip any other HTML tags
        $text = strip_tags($text);
        
        // Bring down number of empty lines to 2 max
        $text = preg_replace("/\n[[:space:]]+\n/", "\n", $text);
        $text = preg_replace("/[\n]{3,}/", "\n\n", $text);
        
        // Add link list
        //if ( !empty($this->_link_list) ) {
        //    $text .= "\n\nLinks:\n------\n" . $this->_link_list;
        //}
        
        // Wrap the text to a readable format
        // for PHP versions >= 4.0.2. Default width is 75
        $text = wordwrap($text, $this->width);
        
        $this->text = $text;
        
        $this->_converted = TRUE;
    }
    
    
    /**
     * Helper function called by preg_replace() on link replacement.
     *
     * Maintains an internal list of links to be displayed at the end of the
     * text, with numeric indices to the original point in the text they
     * appeared.
     *
     * @param integer $link_count Counter tracking current link number
     * @param string $link URL of the link
     * @param string $display Part of the text to associate number with
     * @access private
     * @return string
     */
    function _build_link_list($link_count, $link, $display)
    {
        $link = trim($link);
        $display = trim($display);
        
        if($this->wapSafe) {
            if (trim($link) == trim($display)) {
                return "{[{a href='$display'}]}$display{[{/a}]}";
            } else {
                return "{[{a href='$link'}]}$display{[{/a}]}" ;
            }
        } else {
            if (trim($link) == trim($display)) {
                return $display;
            } else {
                return $display . " " . $link . " ";
            }
        }
    }
}

