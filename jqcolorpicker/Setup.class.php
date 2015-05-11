<?php


/**
 * Клас 'jqdatepick_Setup' - избор на дата
 *
 *
 * @category  bgerp
 * @package   jqcolorpicker
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
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
	public $info = "Палитра за избор на цяват";
	
	
	/**
	 * Път до js файла
	 */
	var $commonJS = 'jqcolorpicker/2.0/jquery.colourPicker.js';
	
	
	/**
	 * Път до css файла
	 */
	var $commonCSS = 'jqcolorpicker/2.0/jquery.colourPicker.css';

    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
    	
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        $html .= $Plugins->installPlugin('Избор на цвят', 'jqcolorpicker_Plugin', 'color_Type', 'private');
        
        return $html;
    }
}
