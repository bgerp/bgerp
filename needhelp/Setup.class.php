<?php


/** Въпроси
 * 
 * @category  bgerp
 * @package   needhelp
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class  needhelp_Setup extends core_ProtoSetup
{
	/**
	 * Инсталиране на пакета
	 */
	function install()
	{
		$html = parent::install();
	
		// Зареждаме мениджъра на плъгините
		$Plugins = cls::get('core_Plugins');
	
		// Инсталираме клавиатурата към password полета
		$html .= $Plugins->installPlugin('Задаване на въпроси от потребители', 'needhelp_Plugin', 'core_Manager', 'family');
	
		return $html;
	}
	

}

