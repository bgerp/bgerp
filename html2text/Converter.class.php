<?php

/*************************************************************************
 *                                                                       *
 * class.html2text.inc                                                   *
 *                                                                       *
 *************************************************************************
 *                                                                       *
 * Converts HTML to formatted plain text                                 *
 *                                                                       *
 * Copyright (c) 2005-2007 Jon Abernathy <jon@chuggnutt.com>             *
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
 * Last modified: 08/08/07                                               *
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
 * Thanks to Darius Kasperavicius (http://www.dar.dar.lt/) for
 * suggesting the addition of $allowed_tags and its supporting function
 * (which I slightly modified). Updated 3/12/04.
 * Thanks to Justin Dearing for pointing out that a replacement for the
 * <TH> tag was missing, and suggesting an appropriate fix.
 * Updated 8/25/04.
 * Thanks to Mathieu Collas (http://www.myefarm.com/) for finding a
 * display/formatting bug in the _build_link_list() function: email
 * readers would show the left bracket and number ("[1") as part of the
 * rendered email address.
 * Updated 12/16/04.
 * Thanks to Wojciech Bajon (http://histeria.pl/) for submitting code
 * to handle relative links, which I hadn't considered. I modified his
 * code a bit to handle normal HTTP links and MAILTO links. Also for
 * suggesting three additional HTML entity codes to search for.
 * Updated 03/02/05.
 * Thanks to Jacob Chandler for pointing out another link condition
 * for the _build_link_list() function: "https".
 * Updated 04/06/05.
 * Thanks to Marc Bertrand (http://www.dresdensky.com/) for
 * suggesting a revision to the word wrapping functionality; if you
 * specify a $width of 0 or less, word wrapping will be ignored.
 * Updated 11/02/06.
 * Big housecleaning updates below:
 * Thanks to Colin Brown (http://www.sparkdriver.co.uk/) for
 * suggesting the fix to handle </li> and blank lines (whitespace).
 * Christian Basedau (http://www.movetheweb.de/) also suggested the
 * blank lines fix.
 * Special thanks to Marcus Bointon (http://www.synchromedia.co.uk/),
 * Christian Basedau, Norbert Laposa (http://ln5.co.uk/),
 * Bas van de Weijer, and Marijn van Butselaar
 * for pointing out my glaring error in the <th> handling. Marcus also
 * supplied a host of fixes.
 * Thanks to Jeffrey Silverman (http://www.newtnotes.com/) for pointing
 * out that extra spaces should be compressed--a problem addressed with
 * Marcus Bointon's fixes but that I had not yet incorporated.
 * Thanks to Daniel Schledermann (http://www.typoconsult.dk/) for
 * suggesting a valuable fix with <a> tag handling.
 * Thanks to Wojciech Bajon (again!) for suggesting fixes and additions,
 * including the <a> tag handling that Daniel Schledermann pointed
 * out but that I had not yet incorporated. I haven't (yet)
 * incorporated all of Wojciech's changes, though I may at some
 * future time.
 * End of the housecleaning updates. Updated 08/08/07.
 *
 *
 * @category  vendors
 * @package   html2text
 * @author    Jon Abernathy <jon@chuggnutt.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class html2text_Converter
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
     * Set this value to 0 (or less) to ignore word wrapping
     * and not constrain text to a fixed-width column.
     *
     * @var integer $width
     * @access public
     */
    var $width = 7000;
    
    
    /**
     * List of preg* regular expression patterns to search for,
     * used in conjunction with $replace.
     *
     * @var array $search
     * @access public
     * @see $replace
     */
    /*
	var $search = array(
        //'/<pre[^>]*>(.*?)<\/pre>/sei',           // <pre>
        "/\r/",                                  // Non-legal carriage return
        "/[\n\t]+/",                             // Newlines and tabs
        '/[ ]{2,}/',                             // Runs of spaces, pre-handling
        '/<base [^>]*href="([^"]+)"[^>]*>/ie',   // Base URL
        '/<script[^>]*>.*?<\/script[^>]*>/i',         // <script>s -- which strip_tags supposedly has problems with
        '/<style[^>]*>.*?<\/style[^>]*>/i',           // <style>s -- which strip_tags supposedly has problems with
        //'/<!-- .* -->/',                       // Comments -- which strip_tags might have problem a with
        '/<h([123456])[^>]*>(.*?)<\/h([123456])>/ie',      // H1 - H6
        '/<p[^>]*>/i',                           // <P>
        '/<div[^>]*>/i',                         // <div>
        '/<br[^>]*>/i',                          // <br>
        '/<b[^>]*>(.*?)<\/b[^>]*>/ie',                // <b>
        '/<strong[^>]*>(.*?)<\/strong[^>]*>/ie',      // <strong>
        '/<i[^>]*>(.*?)<\/i[^>]*>/i',                 // <i>
        '/<em[^>]*>(.*?)<\/em[^>]*>/i',               // <em>
        '/(<ul[^>]*>|<\/ul[^>]*>)/i',                 // <ul> and </ul>
        '/(<ol[^>]*>|<\/ol[^>]*>)/i',                 // <ol> and </ol>
        '/<li[^>]*>(.*?)<\/li[^>]*>/i',               // <li> and </li>
        '/<li[^>]*>/i',                          // <li>
        '/<a [^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/ie',
        // <a href="">
        '/<hr[^>]*>/i',                          // <hr>
        '/(<table[^>]*>|<\/table[^>]*>)/i',           // <table> and </table>
        '/(<tr[^>]*>|<\/tr>)/i',                 // <tr> and </tr>
        '/<td[^>]*>(.*?)<\/td>/i',               // <td> and </td>
        '/<th[^>]*>(.*?)<\/th>/ie',              // <th> and </th>
        '/&(nbsp|#160);/i',                      // Non-breaking space
        '/&(quot|rdquo|ldquo|#8220|#8221|#147|#148);/i',
        // Double quotes
        '/&(apos|rsquo|lsquo|#8216|#8217);/i',   // Single quotes
        '/&(copy|#169);/i',                      // Copyright
        '/&(trade|#8482|#153);/i',               // Trademark
        '/&(reg|#174);/i',                       // Registered
        '/&(mdash|#151|#8212);/i',               // mdash
        '/&(ndash|minus|#8211|#8722);/i',        // ndash
        '/&(bull|#149|#8226);/i',                // Bullet
        '/&(pound|#163);/i',                     // Pound sign
        '/&(euro|#8364);/i',                     // Euro sign
        //'/&[^&;]+;/i',                           // Unknown/unhandled entities
        '/[ ]{2,}/'                              // Runs of spaces, post-handling
    ); 
	*/
    
    
    /**
     * List of pattern replacements corresponding to patterns searched.
     *
     * @var array $replace
     * @access public
     * @see $search
     */
    /*
    var $replace = array(
        // '$this->pre("\\1")',                     // <pre>
        '',                                     // Non-legal carriage return
        ' ',                                    // Newlines and tabs
        ' ',                                    // Runs of spaces, pre-handling
        '$this->_set_base_url("\\1")',          // Set Base Url
        '',                                     // <script>s -- which strip_tags supposedly has problems with
        '',                                     // <style>s -- which strip_tags supposedly has problems with
        //'',                                   // Comments -- which strip_tags might have problem a with
        '$this->h("\\1", "\\2")',              // H1 - H6
        "\n\n",                               // <P>
        "\n",                                   // <DIV>
        "\n",                                   // <br>
        '$this->bold("\\1")',                  // <b>
        '$this->bold("\\1")',                  // <strong>
        '[i]\\1[/i]',                           // <i>
        '[b]\\1[/b]',                           // <em>
        "\n\n",                                 // <ul> and </ul>
        "\n\n",                                 // <ol> and </ol>
        "\t* \\1\n",                            // <li> and </li>
        "\n\t* ",                               // <li>
        '$this->_build_link_list("\\1", "\\2")',
        // <a href="">
        "\n-------------------------\n",        // <hr>
        "\n\n",                                 // <table> and </table>
        "\n",                                   // <tr> and </tr>
        "\\1\t",                            // <td> and </td>
        '$this->bold("\\1\t")',       // <th> and </th>
        ' ',                                    // Non-breaking space
        '"',                                    // Double quotes
        "'",                                    // Single quotes
        '(c)',
        '(tm)',
        '(R)',
        '--',
        '-',
        '*',
        '�',
        'EUR',                                  // Euro sign. � ?
        // '',                                     // Unknown/unhandled entities
        ' '                                     // Runs of spaces, post-handling
    );
    */
    
    
    /**
     * Contains a list of HTML tags to allow in the resulting text.
     *
     * @var string $allowed_tags
     * @access public
     * @see set_allowed_tags()
     */
    var $allowed_tags = '';
    
    
    /**
     * Contains the base URL that relative links should resolve to.
     *
     * @var string $url
     * @access public
     */
    var $url;
    
    
    /**
     * Indicates whether content in the $html variable has been converted yet.
     *
     * @var boolean $_converted
     * @access private
     * @see $html, $text
     */
    var $_converted = false;
    
    
    /**
     * Contains URL addresses from links to be rendered in plain text.
     *
     * @var string $_link_list
     * @access private
     * @see _build_link_list()
     */
    var $_link_list = '';
    
    
    /**
     * Number of valid links detected in the text, used for plain text
     * display (rendered similar to footnotes).
     *
     * @var integer $_link_count
     * @access private
     * @see _build_link_list()
     */
    var $_link_count = 0;
    
    
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
    function __construct($source = '', $from_file = false)
    {
        if (!empty($source)) {
            $this->set_html($source, $from_file);
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function toRichText($text)
    {
        $html2Text = new html2text_Converter($text);
        
        return trim($html2Text->get_text());
    }
    
    
    /**
     * Loads source HTML into memory, either from $source string or a file.
     *
     * @param string $source HTML content
     * @param boolean $from_file Indicates $source is a file to pull content from
     * @access public
     * @return void
     */
    function set_html($source, $from_file = false)
    {
        $this->html = $source;
        
        if ($from_file && file_exists($source)) {
            $fp = fopen($source, 'r');
            $this->html = fread($fp, filesize($source));
            fclose($fp);
        }
        
        $this->_converted = false;
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
     * Sets the allowed HTML tags to pass through to the resulting text.
     *
     * Tags should be in the form "<p>", with no corresponding closing tag.
     *
     * @access public
     * @return void
     */
    function set_allowed_tags($allowed_tags = '')
    {
        if (!empty($allowed_tags)) {
            $this->allowed_tags = $allowed_tags;
        }
    }
    
    
    /**
     * Sets a base URL to handle relative links.
     *
     * @access public
     * @return void
     */
    function _set_base_url($matches)
    {
        $url = $matches[2];
        if (empty($url)) {
            if (!empty($_SERVER['HTTP_HOST'])) {
                $this->url = 'http://' . $_SERVER['HTTP_HOST'];
            } else {
                $this->url = '';
            }
        } else {
            // Strip any trailing slashes for consistency (relative
            // URLs may already start with a slash like "/file.html")
            if (substr($url, -1) == '/') {
                $url = substr($url, 0, -1);
            }
            $this->url = $url;
        }
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
        $this->_link_count = 0;
        $this->_link_list = '';
        
        $text = trim(stripslashes($this->html));

        // Run our defined search-and-replace
        
        // <pre>
        $text = preg_replace_callback("/<pre[^>]*>(.*?)<\/pre>/si", array($this, 'pre'), $text); 
        
        // Non-legal carriage return
        $text = preg_replace("/\r/", '', $text); 
        
        // Newlines and tabs
        $text = preg_replace("/[\n\t]+/", ' ', $text);
        
        // Runs of spaces, pre-handling
        $text = preg_replace("/[ ]{2,}/", ' ', $text);
        
        // Base URL
        $text = preg_replace_callback('/<base [^>]*href=("|\')([^"|\']+)("|\')[^>]*>/i', array($this, '_set_base_url'), $text);
        
        // <script>s -- which strip_tags supposedly has problems with
        $text = preg_replace("/<script[^>]*>.*?<\/script[^>]*>/i", '', $text);
        
        // <style>s -- which strip_tags supposedly has problems with
        $text = preg_replace("/<style[^>]*>.*?<\/style[^>]*>/i", '', $text);
        
        // Comments -- which strip_tags might have problem a with
//        $text = preg_replace('/<!-- .* -->/', '', $text);
        
        // H1 - H6
        $text = preg_replace_callback('/<h([123456])[^>]*>(.*?)<\/h([123456])>/i', array($this, 'h'), $text);
        
        // <P>
        $text = preg_replace("/<p[^>]*>/i", "\n\n", $text);
        
        // <div>
        $text = preg_replace("/<div[^>]*>/i", "\n", $text);
        
        // <br>
        $text = preg_replace("/<br[^>]*>/i", "\n", $text);
        
        // blockquote 
        $text = preg_replace("/<blockquote[^>]*>/i", "[bQuote]", $text);
        $text = preg_replace("/<\/blockquote[^>]*>/i", "[/bQuote]", $text);

        // <b>
        $text = preg_replace_callback('/<b[^>]*>(.*?)<\/b[^>]*>/i', array($this, 'bold'), $text);
        
        // <strong>
        $text = preg_replace_callback('/<strong[^>]*>(.*?)<\/strong[^>]*>/i', array($this, 'bold'), $text);
        
        // <i>
        $text = preg_replace("/<i[^>]*>(.*?)<\/i[^>]*>/i", "[i]\\1[/i]", $text);
        
        // <em>
        $text = preg_replace("/<em[^>]*>(.*?)<\/em[^>]*>/i", "[b]\\1[/b]", $text);
        

       // $text = preg_replace("/<table[^>]*>(.*?)<\/table[^>]*>/i", "[table]\\1[/table]", $text);
       // $text = preg_replace("/<tr[^>]*>(.*?)<\/tr[^>]*>/i", "[tr]\\1[/tr]", $text);
       // $text = preg_replace("/<td[^>]*>(.*?)<\/td[^>]*>/i", "[td]\\1[/td]", $text);
       // $text = preg_replace("/<th[^>]*>(.*?)<\/th[^>]*>/i", "[th]\\1[/th]", $text);

        // <ul> and </ul>
        $text = preg_replace("/(<ul[^>]*>|<\/ul[^>]*>)/i", "\n\n", $text);
        
        // <ol> and </ol>
        $text = preg_replace("/(<ol[^>]*>|<\/ol[^>]*>)/i", "\n\n", $text);
        
        // <li> and </li>
        $text = preg_replace("/<li[^>]*>(.*?)<\/li[^>]*>/i", "\t* \\1\n", $text);
        
        // <li>
        $text = preg_replace("/<li[^>]*>/i", "\n\t* ", $text);
        
        // <a href="">
        $text = preg_replace_callback('/<a [^>]*href=("|\')([^"|\']+)("|\')[^>]*>(.*?)<\/a>/is', array($this, '_build_link_list'), $text);
        
        // <hr>
        $text = preg_replace("/<hr[^>]*>/i", "\n-------------------------\n", $text);
        
        // <table> and </table>
        $text = preg_replace("/(<table[^>]*>|<\/table[^>]*>)/i", "\n\n", $text);
        
        // <tr> and </tr>
        $text = preg_replace("/(<tr[^>]*>|<\/tr>)/i", "\n", $text);
        
        // <td> and </td>
        $text = preg_replace("/<td[^>]*>(.*?)<\/td>/i", "\\1\t", $text);
        
        // <th> and </th>
        $text = preg_replace_callback('/<th[^>]*>(.*?)<\/th>/i', array($this, 'boldt'), $text);
        
        // Non-breaking space
        $text = preg_replace("/&(nbsp|#160);/i", " ", $text);
        
        // Double quotes
        $text = preg_replace("/&(quot|rdquo|ldquo|#8220|#8221|#147|#148);/i", '"', $text);
        
        // Single quotes
        $text = preg_replace("/&(apos|rsquo|lsquo|#8216|#8217);/i", "'", $text);
        
        // Copyright
        $text = preg_replace("/&(copy|#169);/i", "(c)", $text);
        
        // Trademark
        $text = preg_replace("/&(trade|#8482|#153);/i", "(tm)", $text);
        
        // Registered
        $text = preg_replace("/&(reg|#174);/i", "(R)", $text);
        
        // mdash
        $text = preg_replace("/&(mdash|#151|#8212);/i", "--", $text);
        
        // ndash
        $text = preg_replace("/&(ndash|minus|#8211|#8722);/i", "-", $text);
        
        // Bullet
        $text = preg_replace("/&(bull|#149|#8226);/i", "*", $text);
        
        // Pound sign
        $text = preg_replace("/&(pound|#163);/i", "£", $text);
        
        // Euro sign
        $text = preg_replace("/&(euro|#8364);/i", "€", $text);
        
        // Unknown/unhandled entities
//        $text = preg_replace("'/&[^&;]+;/i'", "", $text);
        
        // Runs of spaces, post-handling
        $text = preg_replace("/[ ]{2,}/", " ", $text);
        
        $text = preg_replace_callback("/<[0-9]+/i", array($this, '_allowTags'), $text);

        // Strip any other HTML tags
        $text = strip_tags($text, $this->allowed_tags);
        
        // ������������ ���������� � ����������� HTML �����
        // $text = preg_replace(array('/&gt;/i', '/&lt;/i', '/&(amp|#38);/i'), array('>', '<', '&'), $text);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        
        // Bring down number of empty lines to 2 max
        $text = preg_replace("/\n\s+\n/", "\n\n", $text);
        $text = preg_replace("/[\n]{3,}/", "\n\n", $text);
        
        // Add link list
        if (!empty($this->_link_list)) {
            $text .= "\n\n[hide=Links:]" . $this->_link_list . '[/hide]';
        }
        
        // Wrap the text to a readable format
        // for PHP versions >= 4.0.2. Default width is 75
        // If width is 0 or less, don't wrap the text.
        if ($this->width > 0) {
            $text = wordwrap($text, $this->width);
        }
        
        $this->text = $text;

        $this->_converted = true;
    }
    

    /**
     * Позволява определни тагове да се показват
     */
    function _allowTags($matches)
    {
        $res =  '&lt;' . substr($matches[0], 1);

        return $res;
    }
    
   
    /**
     * Helper function called by preg_replace() on link replacement.
     *
     * Maintains an internal list of links to be displayed at the end of the
     * text, with numeric indices to the original point in the text they
     * appeared. Also makes an effort at identifying and handling absolute
     * and relative links.
     *
     * @param string $link URL of the link
     * @param string $display Part of the text to associate number with
     * @access private
     * @return string
     */
    function _build_link_list($matches)
    {
        $link = $matches[2];
        $display = $matches[4];

        $linkArr = explode(':', $link, 2);
        $schema  = strtolower(trim($linkArr[0]));
        $path    = strtolower(trim($linkArr[1], "\t\n\r/"));
        
        preg_match(type_Richtext::URL_PATTERN, strip_tags($display), $dUrls);
 
        if(is_array($dUrls) && $dU = $dUrls[0]) {  
            if(stripos($dU, 'www.') === 0) {
                $dU = 'http://' . $dU;
            }
 
            if(core_Url::getDomain($dU) != core_Url::getDomain($link)) {
                $alert = ' [em=alert]';
            }
        }

        switch($schema) {
            case 'http' :
            case 'https' :
            case 'ftp' :
            case 'ftps' :
                if(stripos($display, trim($path)) === FALSE) {
                    $this->_link_count++;
                    $this->_link_list .= "[" . $this->_link_count . "] $link {$alert}\n";
                    $additional = " [link={$link}][" . $this->_link_count . "][/link]{$alert}";
                } else {
                    $additional = '';
                }
                break;
            case 'mailto' :
                if(stripos($display, $path) === FALSE) {
                    $this->_link_count++;
                    $this->_link_list .= "[" . $this->_link_count . "]$path\n";
                    $additional = " [" . $this->_link_count . "]";
                } else {
                    $additional = '';
                }
            default :
            $additional = '';
            break;
        }

        return $display . $additional;
    }
    
    
    /**
     * ���������� ��������� ������ ��� ������ �����
     */
    function bold($matches)
    {

        return "[b]{$matches[1]}[/b]";
    }
    
    
    /**
     * 
     */
    function boldt($matches)
    {

        return $this->bold($matches) . "\t";
    }
    
    /**
     * ����� ������ �����, ����� ������� ����� �� ����
     */
    function ucwords($stri)
    {
        
        return mb_convert_case($text, MB_CASE_TITLE);
    }
    
    
    /**
     * ������� ������� �� ��������������� �����
     */
    static function pre($matches)
    {
        $text = str_replace(array("\r\n", "\n\r", "\n", "\r"  ), 
                array("<br>", "<br>\n", "<br>\n", "<br>\n" ), $matches[1]);
         
        return '[code=text]' . $text . '[/code]';
    }
    
    
    /**
     * �������� �������� <h*>
     */
    function h($matches)
    {
        return "[h{$matches[1]}]{$matches[2]}[/h{$matches[1]}]";
    }
}
