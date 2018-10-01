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
 * Клас 'fastscroll_Setup' -  плъгин за бързо скролиране на страниците
 *
 *
 * @category  vendors
 * @package   fastscroll
 *
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class fastscroll_Setup extends core_ProtoSetup
{
    public $info = 'Бързо скролиране в дълги страници';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'FASTSCROLL_HIDE_AFTER_SEC' => array('time(suggestions=1сек|2сек|3сек|4сек|5сек)', 'caption=Скриване на стрелките за бързо скролиране след->Време за изчакване'),
        'FASTSCROLL_ACTIVE_RATIO' => array('double', 'caption=Показване при скрита/видима част->Съотношение'),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина към страницата
        $html .= $Plugins->installPlugin('Бързо скролиране в страниците', 'fastscroll_Plugin', 'core_page_Active', 'family');
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        $html = parent::deinstall();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        $Plugins->deinstallPlugin('fastscroll_Plugin');
        $html .= "<li>Премахнати са всички инсталации на 'fastscroll_Plugin'";
        
        return $html;
    }
    
    
    /**
     * Връща JS файлове, които са подходящи за компактиране
     */
    public function getCommonJs()
    {
        return 'fastscroll/lib/fastscroll.js';
    }
    
    
    /**
     * Връща JS файлове, които са подходящи за компактиране
     */
    public function getCommonCss()
    {
        return 'fastscroll/lib/fastscroll.css';
    }
}
