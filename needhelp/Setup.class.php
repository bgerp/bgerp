<?php


/**
 * id на системата
 */
defIfNot('NEEDHELP_TYPEID', 28);


/**
 * След колко време трябва да се покаже прозореца за помощ
 */
defIfNot('NEEDHELP_INACTIVE_SECS', 15);


/**
 * Брой показвания за един потребител
 */
defIfNot('NEEDHELP_SHOW_LIMIT', 3);


/**
 * След колко време да не се вземат предвид показванията
 * 6 месеца
 */
defIfNot('NEEDHELP_SHOW_LIMIT_DATE', 15778476);



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
	public $configDescription = array(
		'NEEDHELP_TYPEID' => array('int', 'caption=Номер на системата'),
		'NEEDHELP_INACTIVE_SECS' => array('int', 'caption=След колко време трябва да се покаже прозореца за помощ->Секунди'),
		'NEEDHELP_SHOW_LIMIT' => array('int', 'caption=Брой показвания на един потребител->Брой'),
		'NEEDHELP_SHOW_LIMIT_DATE' => array('time(suggestions=3 месецa|6 месеца|9 месеца|1 година|2 години)', 'caption=След колко време да не се вземат предвид показванията->Време'),
	);
	
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'needhelp_Log',
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

