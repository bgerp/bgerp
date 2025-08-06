<?php


/**
 * Клас 'flip_Driver'
 *
 * Вграждане на намаляващи броячи в bgERP
 *
 * @category  bgerp
 * * @package   flip
 * *
 * * @author    Nevena Vitkinova <nevena@experta.bg>
 * * @copyright 2006 - 2025 Experta OOD
 * * @license   GPL 3
 *
 */
class flip_Driver extends core_BaseClass
{
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'cms_LibraryIntf';
    
    
    /**
     * Заглавие на класа
     */
    public $title = 'Намаляващ таймер flip';
    
    
    /**
     * Допълва дадената форма с параметрите на фигурата
     * Връща масив от имената на параметрите
     */
    public static function addFields(&$form)
    {
        $form->FLD('start', 'datetime', 'caption=Начало,mandatory');

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

            $timestamp = dt::mysql2timestamp($rec->start);
            $date = new DateTime();
            $date->setTimestamp($timestamp);
            $startDate = $date->format(DateTime::ATOM);

            $tpl = new ET("   <script>
              function handleTickInit(tick) {
                  var locale = {
                      YEAR_PLURAL: 'години',
                      YEAR_SINGULAR: 'година',
                      MONTH_PLURAL: 'месеца',
                      MONTH_SINGULAR: 'месец',
                      WEEK_PLURAL: 'седмици',
                      WEEK_SINGULAR: 'седмица',
                      DAY_PLURAL: 'дни',
                      DAY_SINGULAR: 'ден',
                      HOUR_PLURAL: 'часа',
                      HOUR_SINGULAR: 'час',
                      MINUTE_PLURAL: 'минути',
                      MINUTE_SINGULAR: 'минута',
                      SECOND_PLURAL: 'секунди',
                      SECOND_SINGULAR: 'секунда'
                  };
        
                  for (var key in locale) {
                      if (!locale.hasOwnProperty(key)) { continue; }
                      tick.setConstant(key, locale[key]);
                  }
                  var date = new Date('{$startDate}');
                  
                  var counter = Tick.count.down(date);
        
                  counter.onupdate = function (value) {
                      tick.value = value;
                  };
                  
                  counter.onended = function () {
                       tick.root.style.display = 'none';
                  };
        }
        </script>
            <div class='tick' data-did-init='handleTickInit'>
              <!--ET_BEGIN BEFORE_CDT--><div>[#BEFORE_CDT#]</div><!--ET_END BEFORE_CDT-->
              <div data-repeat='true' data-layout='horizontal fit' data-transform='preset(d, h, m, s) -&gt; delay'>
                <div class='tick-group'>
                  <div data-key='value' data-repeat='true' data-transform='pad(00) -&gt; split -&gt; delay'><span data-view='flip'></span></div>
                  <span data-key='label' data-view='text' class='tick-label'></span>
                </div>
              </div>
                <!--ET_BEGIN AFTER_CDT--><div>[#AFTER_CDT#]</div><!--ET_END AFTER_CDT-->
            </div>");
            $tpl->replace($bt, 'BEFORE_CDT');
            $tpl->replace($at, 'AFTER_CDT');


            $tpl->push("flip/css/flip.css", 'CSS');
            $tpl->push("flip/js/flip.js", 'JS');
        }
 
        return $tpl;
    }
}
