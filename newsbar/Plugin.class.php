<?php


/**
 * Клас 'newsbar_Plugin'
 *
 * Прихваща събитията на plg_ProtoWrapper и добавя, ако е има помощна информация в newsbar_Nesw, като бар лента
 *
 *
 * @category  bgerp
 * @package   newsbar
 *
 * @author    Gabriela Petrova <gpetrova@experta.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class newsbar_Plugin extends core_Plugin
{
    public static function on_Output(&$invoker)
    {
        // взимаме всички нови новини
        $str = newsbar_News::getTopNews();
        
        if ($str->news !== null && $str->color !== null && $str->transparency !== null) {
            $convertText = cls::get('type_Richtext');
            $barNews = $convertText->toVerbal($str->news);
            
            $html = newsbar_News::generateHTML($str);
            $html->replace('newsbar', 'class');
            $html->replace("<marquee scrollamount='4'>", 'marquee');
            $html->replace('</marquee>', 'marquee2');
            
            
            $invoker->appendOnce($html, 'PAGE_HEADER');
        }
    }
}
