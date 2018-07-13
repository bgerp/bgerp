<?php


/**
 * Клас 'jqdatepick_Setup' - избор на дата
 *
 *
 * @category  bgerp
 * @package   jqcolorpicker
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class jqcolorpicker_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Палитра за избор на цвят';
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        $html .= $Plugins->installPlugin('Избор на цвят', 'jqcolorpicker_Plugin', 'color_Type', 'private');
        
        return $html;
    }
    
    
    /**
     * Връща JS файлове, които са подходящи за компактиране
     */
    public function getCommonJs()
    {
        return 'jqcolorpicker/2.0/jquery.colourPicker.js';
    }
    
    
    /**
     * Връща JS файлове, които са подходящи за компактиране
     */
    public function getCommonCss()
    {
        return 'jqcolorpicker/2.0/jquery.colourPicker.css';
    }
}
