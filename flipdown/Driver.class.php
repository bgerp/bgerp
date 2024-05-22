<?php


/**
 * Клас 'flipdown_Driver'
 *
 * Вграждане на намаляващи броячи в bgERP
 *
 * @category  bgerp
 * @package   flipdown
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 */
class flipdown_Driver extends core_BaseClass
{
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'cms_LibraryIntf';
    
    
    /**
     * Заглавие на класа
     */
    public $title = 'Намаляващ таймер';
    
    
    /**
     * Допълва дадената форма с параметрите на фигурата
     * Връща масив от имената на параметрите
     */
    public static function addFields(&$form)
    {
        $form->FLD('start', 'datetime', 'caption=Начало,mandatory');
        $form->FLD('theme', 'enum(dark=Тъмна,light=Светла)', 'caption=Тема');

        $form->FLD('beforeText', 'richtext(rows=4)', 'caption=Текстове->Преди');
        $form->FLD('afterText', 'richtext(rows=4)', 'caption=Текстове->След');
    }
    
    
    /**
     * Връща HTML представянето на обекта
     *
     * @param stdClass $rec Записа за елемента от модела-библиотека
     * @param $maxWidth int Максимална широчина на елемента
     * @param $isAbsolute bool Дали URL-тата да са абсолютни
     *
     * @return core_ET|string Представяне на обекта в HTML шабло
     */
    public static function render($rec, $maxwidth = 1200, $absolute = false)
    {
        // Ако е текстов режим, да не сработва
        if (Mode::is('text', 'plain')) {
            
            return '';
        }
        
        $tpl = '';

        if(dt::now() < $rec->start) {
            $rt = cls::get('type_RichText');
            $bt = $rec->beforeText ? $rt->toHtml($rec->beforeText, '', '') : null;
            $at = $rec->afterText ? $rt->toHtml($rec->afterText, '', '') : null;
          
            $tpl = new ET("<div style='display: flex;align-items: center;justify-content: center;flex-direction: column;'>
                <!--ET_BEGIN BEFORE_CDT--><div>[#BEFORE_CDT#]</div><!--ET_END BEFORE_CDT-->
                <div id='flipdown{$rec->id}' class='flipdown'></div>
                <!--ET_BEGIN AFTER_CDT--><div>[#AFTER_CDT#]</div><!--ET_END AFTER_CDT-->
                </div>");
            $tpl->replace($bt, 'BEFORE_CDT');
            $tpl->replace($at, 'AFTER_CDT');

            $timestamp = dt::mysql2timestamp($rec->start);
            $tpl->push("flipdown/css/flipdown.min.css", 'CSS');
            $tpl->push("flipdown/js/flipdown.min.js", 'JS');
            $headers = core_Lg::getCurrent() == 'bg' ? "['Дни', 'Часове', 'Минути', 'Секунди']" : "['Days', 'Hours', 'Minutes', 'Seconds']";
            jquery_Jquery::run($tpl, "new FlipDown({$timestamp}, 'flipdown{$rec->id}', {headings: {$headers}, theme: '{$rec->theme}', }).start();", true);

        }  
 
        return $tpl;
    }
}
