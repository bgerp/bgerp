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
 *
 * @author    Jon Abernathy <jon@chuggnutt.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
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
    public $html;
    
    
    /**
     * Contains the converted, formatted text.
     *
     * @var string $text
     * @access public
     */
    public $text;
    
    
    /**
     * Maximum width of the formatted text, in columns.
     *
     * @var int $width
     * @access public
     */
    public $width = 70;
    
    
    public $search = array(
        "/\r/",
        "/[\n\t]+/",
        '/<script[^>]*>.*?<\/script>/is',
        '/<style[^>]*>.*?<\/style>/is',
        '/<!--.*?-->/s',
        '/<p[^>]*>/i',
        '/<br[^>]*>/i',
        '/<b[^>]*>(.+?)<\/b>/i',
        '/<i[^>]*>(.+?)<\/i>/i',
        '/(<ul[^>]*>|<\/ul>)/i',
        '/<li[^>]*>/i',
        '/<hr[^>]*>/i',
        '/(<table[^>]*>|<\/table>)/i',
        '/(<tr[^>]*>|<\/tr>)/i',
        '/<td[^>]*>(.+?)<\/td>/i',
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

    public $replace = array(
        '',
        ' ',
        '',
        '',
        '',
        "\n\n\t",
        "\n",
        '[b]\\1[/b]',
        '[i]\\1[/i]',
        "\n\n",
        "\t*",
        "\n[hr]",
        "\n\n",
        "\n",
        "\t\\1\t",
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

    public $replaceSimple = array(
        '',
        ' ',
        '',
        '',
        '',
        "\n\n\t",
        "\n",
        '\\1',
        '\\1',
        "\n\n",
        "\t*",
        "\n_________________________________________________________________",
        "\n\n",
        "\n",
        "\t\\1\\t",
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
     * @var bool $converted
     * @access private
     *
     * @see $html, $text
     */
    public $_converted = false;
    
    
    /**
     * Contains URL addresses from links to be rendered in plain text.
     *
     * @var string $link_list
     * @access private
     *
     * @see _build_link_list()
     */
    public $_link_list;
    
    public $simple = false;

    public $wapSafe = false;
 

    /**
     * Просто конвертира
     */
    public function convert2text($html, $simple = false)
    {
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
     * @param string $source    HTML content
     * @param bool   $from_file Indicates $source is a file to pull content from
     * @access public
     *
     * @return void
     */
    public function set($source = '', $from_file = false)
    {
        if (!empty($source)) {
            $this->set_html($source, $from_file);
        }
    }
    
    
    /**
     * Loads source HTML into memory, either from $source string or a file.
     *
     * @param string $source    HTML content
     * @param bool   $from_file Indicates $source is a file to pull content from
     * @access public
     *
     * @return void
     */
    public function set_html($source, $from_file = false)
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
     *
     * @return string
     */
    public function get_text()
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
     *
     * @return void
     */
    public function print_text()
    {
        echo $this->get_text();
    }
    
    
    /**
     * Alias to print_text(), operates identically.
     *
     * @access public
     *
     * @return void
     *
     * @see print_text()
     */
    public function p()
    {
        echo $this->get_text();
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
     *
     * @return void
     */
    public function _convert()
    {
        $link_count = 1;
        $this->_link_list = '';

        $text = trim($this->html);

        // 1) Пусни „статичните“ замествания (без H1–H6 и <a ...>)
        $search  = $this->search;
        $replace = $this->simple ? $this->replaceSimple : $this->replace;

        // махаме правилата, които преди "изпълняваха" PHP
        foreach ([5, 6, 13, 14] as $i) {
            unset($search[$i], $replace[$i]);
        }

        $text = preg_replace(array_values($search), array_values($replace), $text);

        // 2) H1–H3: ГОЛЕМИ букви
        $text = preg_replace_callback(
            '/<h[123][^>]*>(.+?)<\/h[123]>/is',
            function ($m) {
                $s = trim($m[1]);
                return "\n\n" . (function_exists('mb_strtoupper') ? mb_strtoupper($s, 'UTF-8') : strtoupper($s)) . "\n\n";
            },
            $text
        );

        // 3) H4–H6: Title Case
        $text = preg_replace_callback(
            '/<h[456][^>]*>(.+?)<\/h[456]>/is',
            function ($m) {
                $s = trim($m[1]);
                if (function_exists('mb_convert_case')) {
                    $s = mb_convert_case($s, MB_CASE_TITLE, 'UTF-8');
                } else {
                    $s = ucwords(strtolower($s));
                }
                return "\n\n" . $s . "\n\n";
            },
            $text
        );

        // 4) Линкове (<a ...>) – поддържа http/https и " или '
        $text = preg_replace_callback(
            '/<a[^>]+href=["\'](https?:[^"\']+)["\'][^>]*>(.*?)<\/a>/is',
            function ($m) use (&$link_count) {
                return $this->_build_link_list($link_count++, $m[1], $m[2]);
            },
            $text
        );

        // 5) Махни останалите HTML тагове
        $text = strip_tags($text);

        // 6) Нормализирай празните редове
        $text = preg_replace("/\n[[:space:]]+\n/", "\n", $text);
        $text = preg_replace("/[\n]{3,}/", "\n\n", $text);

        // 7) Wrap
        $text = wordwrap($text, $this->width);

        $this->text = $text;
        $this->_converted = true;
    }
    
    
    /**
     * Helper function called by preg_replace() on link replacement.
     *
     * Maintains an internal list of links to be displayed at the end of the
     * text, with numeric indices to the original point in the text they
     * appeared.
     *
     * @param int    $link_count Counter tracking current link number
     * @param string $link       URL of the link
     * @param string $display    Part of the text to associate number with
     * @access private
     *
     * @return string
     */
    public function _build_link_list($link_count, $link, $display)
    {
        $link = trim($link);
        $display = trim($display);
        
        if ($this->wapSafe) {
            if (trim($link) == trim($display)) {
                
                return "{[{a href='${display}'}]}${display}{[{/a}]}";
            }
            
            return "{[{a href='${link}'}]}${display}{[{/a}]}" ;
        }
        if (trim($link) == trim($display)) {
            
            return $display;
        }
        
        return $display . ' ' . $link . ' ';
    }
}
