<?php



/**
 *  id на системата
 */
defIfNot('NEEDHELP_TYPEID', 28);


/**
 *  След колко време трябва да се покаже прозореца за помощ
*/
defIfNot('NEEDHELP_INACTIVE_SECS', 15);



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
	 * Описание на конфигурационните константи
	 */
	var $configDescription = array(
			'NEEDHELP_TYPEID'  => array('int', 'caption=Номер на системата'),
			'NEEDHELP_INACTIVE_SECS'    => array('int', 'caption=След колко време трябва да се покаже прозореца за помощ->Секунди'),
	);
	
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

