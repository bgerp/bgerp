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
       
       $convertText = cls::get('type_Richtext');
       $barNews = $convertText->toVerbal($str);
       
       $html = "<div class='newsbar'>
		<marquee scrollamount='4'><b style='opacity:1;'>$barNews</b></marquee>
		</div><div class='clearfix21'></div>";
       
       $invoker->appendOnce($html, 'TOP_NEWS');
    }

}