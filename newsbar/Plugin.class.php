<?php



/**
 * Клас 'newsbar_Plugin'
 *
 * Прихваща събитията на plg_ProtoWrapper и добавя, ако е има помощна информация в newsbar_Nesw, като бар лента
 *
 *
 * @category  bgerp
 * @package   newsbar
 * @author    Gabriela Petrova <gpetrova@experta.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class newsbar_Plugin extends core_Plugin
{
	
	
	
    static function on_Output(&$invoker)
    {
       // взимаме всички нови новини
       $str = newsbar_News::getTopNews();
       
       $rgb = newsbar_News::hex2rgb($str->color);
       $hexTransparency = dechex($str->transparency * 255);
       $forIE = "#". $hexTransparency. str_replace("#", "", $str->color);

       if($str->news !== NULL && $str->color !== NULL && $str->transparency !== NULL) { 
           $convertText = cls::get('type_Richtext');
           $barNews = $convertText->toVerbal($str->news);
           
           $html = new ET("<div class='newsbar' style=\"background-color: rgb([#r#], [#g#], [#b#]); 
            										   background-color: rgba([#r#], [#g#], [#b#], [#transparency#]);
           											   background:transparent \0; 
                          filter:progid:DXImageTransform.Microsoft.gradient(startColorstr=[#ie#], endColorstr=[#ie#]);
                          -ms-filter: 'progid:DXImageTransform.Microsoft.gradient(startColorstr=[#ie#], endColorstr=[#ie#]) ';
                          zoom: 1;\">
            <marquee scrollamount='4'><b>$barNews</b></marquee>
            </div><div class='clearfix21'></div>");
           
           $html->replace($rgb[0], 'r');
           $html->replace($rgb[1], 'g');
           $html->replace($rgb[2], 'b');
           $html->replace($str->transparency, 'transparency');
           $html->replace($forIE, 'ie');
     
           $invoker->appendOnce($html, 'PAGE_HEADER');
       }
    }

}