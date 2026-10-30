<?php


expect(extension_loaded('mbstring'), 'Html2Text 4.3.2 изисква PHP разширението mbstring');
require_once __DIR__ . '/4.3.2/src/Html2Text.php';


/**
 * Html2Text 4.3.2 с форматирането и линковете на bgERP
 *
 * @category  vendors
 * @package   html2text
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
class html2text_Modern extends \Html2Text\Html2Text
{
    /** @var html2text_Converter Общи правила за bgERP форматиране и линкове. */
    protected $legacy;


    /** Настройва upstream конвертора за съществуващия Richtext договор. */
    public function __construct(html2text_Converter $legacy)
    {
        $this->legacy = $legacy;
        parent::__construct($legacy->html, array('width' => $legacy->width));
        $this->replace[5] = '[i]\\1[/i]';
        $this->replace[6] = '[i]\\1[/i]';
        $this->replace[7] = '[u]\\1[/u]';
        $this->replace[19] = "\\1\t";
    }


    /** Използва непроменения upstream формат за текстовия файлов индекс. */
    public static function toPlainText($html, $width = 70)
    {
        $encoding = mb_internal_encoding();
        try {
            $converter = new \Html2Text\Html2Text((string) ($html ?? ''), array('width' => $width));

            return $converter->getText();
        } finally {
            mb_internal_encoding($encoding);
        }
    }


    /** Възстановява encoding и при прекъсване от framework callback. */
    protected function convert()
    {
        $encoding = mb_internal_encoding();
        try {
            $this->legacy->_link_count = 0;
            $this->legacy->_link_list = '';
            parent::convert();
            if ($this->legacy->_link_list !== '') {
                $this->text .= "\n\n[hide=Links:]" . $this->legacy->_link_list . '[/hide]';
            }
        } finally {
            mb_internal_encoding($encoding);
        }
    }


    /** Запазва CSS форматирането чрез същите правила като стария адаптер. */
    protected function converter(&$text)
    {
        $text = $this->legacy->convertStyles($text, true);
        parent::converter($text);
    }


    /** Използва цитатите на Richtext вместо текстовия префикс на upstream. */
    protected function convertBlockquotes(&$text)
    {
        $text = preg_replace(array('/<blockquote\b[^>]*>/i', '/<\/blockquote\s*>/i'), array('[bQuote]', '[/bQuote]'), $text);
    }


    /** Запазва блока и използва upstream обработката на whitespace в PRE. */
    protected function convertPre(&$text)
    {
        $text = preg_replace(array('/(<pre\b[^>]*>)/i', '/(<\/pre\s*>)/i'), array('[bQuote]$1', '$1[/bQuote]'), $text);
        parent::convertPre($text);
    }


    /** Пренася bgERP таговете без да променя официалния изходен файл. */
    protected function pregCallback($matches)
    {
        $tag = strtolower($matches[1] ?? '');
        $content = $matches[3] ?? '';
        if ($tag === 'b' || $tag === 'strong') {
            return $this->legacy->bold(array(1 => $content));
        }
        if ($tag === 'th') {
            return $this->legacy->boldt(array(1 => $content));
        }
        if ($tag === 'h' && preg_match('/^<h([1-6])/i', $matches[0] ?? '', $heading)) {
            return $this->legacy->h(array(1 => $heading[1], 2 => $content));
        }
        if ($tag === 'del') {
            return '[s]' . $content . '[/s]';
        }

        return parent::pregCallback($matches);
    }


    /** Запазва брояча, mailto и проверката за различен видим домейн. */
    protected function buildlinkList($link, $display, $linkOverride = null)
    {
        return $this->legacy->_build_link_list(array(2 => $link, 4 => $display));
    }
}
