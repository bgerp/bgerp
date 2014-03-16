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
class vislog_Setup extends core_ProtoSetup
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
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'vislog_HistoryResources',
            'vislog_History',
            'vislog_Referer',
            'vislog_IpNames',
        );

        
    /**
     * Роли за достъп до модула
     */
    //var $roles = 'vislog';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.5, 'Сайт', 'Лог', 'vislog_History', 'default', "admin, ceo, cms"),
        );

        
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        return "";
    }
}