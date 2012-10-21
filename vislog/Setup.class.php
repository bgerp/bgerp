<?php



/**
 * Клас 'vislog_Setup' -
 *
 *
 * @category  vendors
 * @package   vislog
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class vislog_Setup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'vislog_History';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Какво правят не-регистрираните потребители на сайта";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'vislog_HistoryResources',
            'vislog_History',
            'vislog_Referer',
        );
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        // Меню
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(3, 'Сайт', 'Лог', 'vislog_History', 'default', "admin, ceo, cms");
        
        return $html;

        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        return "";
    }
}