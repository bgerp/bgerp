<?php


/**
 * Клас 'slick_Adapter'
 *
 * Адаптер за slick към bgERP
 *
 * @category  bgerp
 * @package   slick
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @link      http://idangero.us/slick/
 */
class slick_Driver extends core_BaseClass
{
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'cms_LibraryIntf';
    
    
    /**
     * Заглавие на класа
     */
    public $title = 'Slick слайдер';
    
    
    /**
     * Допълва дадената форма с параметрите на фигурата
     * Връща масив от имената на параметрите
     */
    public static function addFields(&$form)
    {
        $form->FLD('images', 'fileman_type_Files(bucket=gallery_Pictures,align=vertical)', 'caption=Картинки');
        $form->FLD('dots', 'enum(no=Няма,out=Отвън,inner=Отвътре)', 'caption=Точки');
        $form->FLD('arrows', 'enum(no=Няма,out=Отвън,inner=Отвътре)', 'caption=Стрелки');
        $form->FLD('autoplay', 'time(suggestions=1 сек.|2 сек.|3 сек.|5 сек.|10 сек.,uom=secunds)', 'caption=Смяна през,placeholder=Няма');
        $form->FLD('maxSize', 'int(min=0)', 'caption=Макс. разм.,placeholder=0,unit=px');
    }
    
    
    /**
     * Връща HTML представянето на обекта
     *
     * @param stdClass $rec Записа за елемента от модела-библиотека
     * @param $maxWidth int Максимална широчина на елемента
     * @param $isAbsolute bool Дали URL-тата да са абсолютни
     *
     * @return core_ET Представяне на обекта в HTML шабло
     */
    public static function render($rec, $maxwidth = 1200, $absolute = false)
    {
        // Ако е текстов режим, да не сработва
        if (Mode::is('text', 'plain')) {
            
            return '';
        }
        
        $images = keylist::toArray($rec->images, false);

        // Ако няма картинки - да не сработва
        if (!is_array($images) || !count($images)) {
            
            return ;
        }

        $tpl = new ET("
            <div>
                <div id='slick{$rec->id}' class='[#OUTER_ARROWS#] [#OUTER_DOTS#]'>
                [#SLICK_SLIDES#]
                </div>
            </div>
        ");
        
        if ($rec->maxSize > 0) {
            $maxwidth = $rec->maxSize;
        }
        if ($rec->arrows == 'out') {
            $tpl->replace('outerArrows', 'OUTER_ARROWS');
        }
        
        if ($rec->dots == 'out') {
            $tpl->replace('outerDots', 'OUTER_DOTS');
        }
        
        $style = '';
        foreach ($images as $fileId) {
            $img = new thumb_Img(array(fileman::idToFh($fileId), $maxwidth, $maxwidth, 'fileman', 'mode' => 'small-no-change', 'isAbsolute' => $absolute));
            $imageURL = $img->getUrl('forced');
            $slide = "\n    <div{$style}><img style='width:100%;height:auto;' src='{$imageURL}'></div>";
            $tpl->append($slide, 'SLICK_SLIDES');
            $style = ' style="display:none;"';
        }
        
        // Вземаме актуалната версия
        $ver = slick_Setup::get('VERSION');
        
        // Включваме необходимия JS
        $tpl->push("slick/{$ver}/js/slick.js", 'JS');
        
        // Включваме необходимия CSS
        $tpl->push("slick/{$ver}/css/slick.css", 'CSS');
        $tpl->push("slick/{$ver}/css/slick-theme.css", 'CSS');
        
        $options = array(
            'slidesToShow' => 1,
            'adaptiveHeight' => true,
            'slidesToScroll' => 1,
            'dots' => $rec->dots != 'no',
            'arrows' => $rec->arrows != 'no',
            'autoplay' => $rec->autoplay > 0,
            'autoplaySpeed' => 1000 * $rec->autoplay,
        );
        
        $options = json_encode($options);
        
        // Стартираме slick
        jquery_Jquery::run($tpl, "$('#slick{$rec->id}').slick(${options});");
        
        $tpl->removeBlocks();
        
        return $tpl;
    }
}
