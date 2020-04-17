<?php


/**
 * Клас 'mejs_Driver'
 *
 * Вграждане на видео и аудио обекти в bgERP
 *
 * @category  bgerp
 * @package   mejs
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 */
class mejs_VideoDriver extends core_BaseClass
{
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'cms_LibraryIntf';
    
    
    /**
     * Заглавие на класа
     */
    public $title = 'Видео';
    
    
    /**
     * Допълва дадената форма с параметрите на фигурата
     * Връща масив от имената на параметрите
     */
    public static function addFields(&$form)
    {
        $form->FLD('source1', 'fileman_FileType(bucket=cms_Video,align=vertical)', 'caption=Файл 1');
        $form->FLD('source2', 'fileman_FileType(bucket=cms_Video,align=vertical)', 'caption=Файл 2');
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
        
        // Определяме широчината на видеото в зависимост от мода
        $width = '100%';
        $height = '100%';
                
        $source = array();
        if($rec->source1) {
            $source[$rec->source1] = $rec->source1;
        }
        if($rec->source2) {
            $source[$rec->source2] = $rec->source2;
        }
 
        // Шаблона за видеото
        $tpl = mejs_Adapter::createVideo($source, array('width' => $width, 'height' => $height));

        
        return $tpl;
    }
}
