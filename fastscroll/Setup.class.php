<?php


/**
 * Колко време след посленото скролиране да се скрият бутоните
 */
defIfNot('FASTSCROLL_HIDE_AFTER_SEC', '3');

/**
 * Съотнощение между височината на страницата и височината на прозореца, при която да работи плъгина
 */
defIfNot('FASTSCROLL_ACTIVE_RATIO', '2');


/**
 * Клас 'fastscroll_Setup' -  плъгин за бързо скрoлиране на страниците
 *
 *
 * @category  vendors
 * @package   fastscroll
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fastscroll_Setup extends core_ProtoSetup 
{
	var $info = "Бързо скрoлиране на страниците";
    
	
	/**
	 * Път до js файла
	 */
	var $commonJS = 'fastscroll/lib/fastscroll.js';
	
	
	/**
	 * Път до css файла
	 */
	var $commonCSS = 'fastscroll/lib/fastscroll.css';
	
	
	/**
	 * Описание на конфигурационните константи
	 */
	var $configDescription = array(
			'FASTSCROLL_HIDE_AFTER_SEC' => array ('time(suggestions=1сек|2сек|3сек|4сек|5сек)', 'caption=Скриване на стрелките за бързо скролиране след->Време за изчакване'),
			'FASTSCROLL_ACTIVE_RATIO' => array ('double', 'caption=Съотношение между височините на страницата и прозореца->Съотношение'),
	);
	
    /**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
    	
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина към страницата
        $html .= $Plugins->installPlugin('Бързо скролиране', 'fastscroll_Plugin', 'core_page_Internal', 'private');
        $html .= $Plugins->installPlugin('Бързо скролиране за модерната версия', 'fastscroll_Plugin', 'core_page_InternalModern', 'private');
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
    	$html = parent::deinstall();
    	
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        $Plugins->deinstallPlugin('fastscroll_Plugin');
        $html .= "<li>Премахнати са всички инсталации на 'fastscroll_Plugin'";
        
        return $html;
    }
}
