<?php


/**
 * SALT ключа за генериране на уникален sid (status id)
 */
defIfNot('STATUS_SALT', md5(EF_SALT . 'status'));


/**
 * Колко време преди създаването да се показват статус съобщеният
 */
defIfNot('STATUS_TIME_BEFORE', 2);


/**
 * Време на бездействие на таба, преди което съобщението ще се маркира, като прочетено
 */
defIfNot('STATUS_IDLE_TIME', 3);


/**
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с status
 *
 * @category  vendors
 * @package   status
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class status_Setup extends core_ProtoSetup
{
    /**
     * Версията на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'status_Messages';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Извличане и показване на статус съобщенията';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
        'STATUS_TIME_BEFORE' => array('time', 'caption=Колко време преди създаването'),
        'STATUS_IDLE_TIME' => array('time', 'caption=Време на бездействие на таба за премахване на статус'),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        // Инсталиране на мениджърите
        $managers = array(
            'status_Messages',
            'status_Retrieving',
        );
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме
        $html .= $Plugins->forcePlugin('Статус съобщения', 'status_Plugin', 'core_Statuses', 'private');
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        // Изтриване на пакета от менюто
        $html = bgerp_Menu::remove($this);
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        $Plugins->deinstallPlugin('status_Plugin');
        $html .= "<li>Премахнати са всички инсталации на 'status_Plugin'";
        
        return $html;
    }
}
