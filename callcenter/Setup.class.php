<?php


/**
 * След колко секунди да се промени от празно състояние в без отговор
 */
defIfNot(CALLCENTER_DRAFT_TO_NOANSWER, '3600');


/**
 * Инсталиране/Деинсталиране на мениджъри свързани с callcenter модула
 *
 * @category  bgerp
 * @package   callcenter
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class callcenter_Setup
{
    
    
    /**
     * Версията на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'callcenter_Talks';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Център за телефонни обаждания";
    
    
    /**
     * Описание на системните действия
     */
    var $systemActions = array(
           
       'Актуализиране' => array ('callcenter_Numbers', 'update', 'ret_url' => TRUE),
    
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
           
       'CALLCENTER_DRAFT_TO_NOANSWER' => array('int'),

    );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        // Инсталиране на мениджърите
        $managers = array(
            'callcenter_Talks',
            'callcenter_Fax',
            'callcenter_SMS',
            'callcenter_Numbers',
        );
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }

        // Добавяме менюто
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(2.04, 'Обслужване', 'Централа', 'callcenter_Talks', 'default', "user");
        
        //инсталиране на кофата
//        $Bucket = cls::get('fileman_Buckets');
//        $html .= $Bucket->createBucket('callcenter', 'Прикачени файлове в КЦ', NULL, '300 MB', 'user', 'user');
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}