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
	public $info = "Изтрумент за работа с цветове";
	
	
	/**
	 * Път до js файла
	 */
	var $commonJS = 'jqcolorpicker/2.0/jquery.colourPicker.js';
	
	
	/**
	 * Път до css файла
	 */
	var $commonCSS = 'jqcolorpicker/2.0/jquery.colourPicker.css';
	
	
	/**
	 * Пакет без инсталация
	 */
	public $noInstall = TRUE;
}

