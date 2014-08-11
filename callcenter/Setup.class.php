<?php


/**
 * Защитен ключ за регистриране на обаждания
 */
defIfNot('CALLCENTER_PROTECT_KEY', md5(EF_SALT . 'callCenter'));


/**
 * Разрешени IP адреси, от които да се генерира обаждане
 */
defIfNot('CALLCENTER_ALLOWED_IP_ADDRESS', '');


/**
 * След колко секунди да се промени от празно състояние в без отговор
 */
defIfNot('CALLCENTER_DRAFT_TO_NOANSWER', '3600');


/**
 * Максимална продължителност на разговорите
 */
defIfNot('CALLCENTER_MAX_CALL_DURATION', '3600');


/**
 * Допустимото отклонение в секуди при регистриране на обажданията
 */
defIfNot('CALLCENTER_DEVIATION_BETWEEN_TIMES', '3600');


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
class callcenter_Setup extends core_ProtoSetup
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
     * Път до css файла
     */
//    var $commonCSS = 'callcenter/css/callSummary.css';
   
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
       'CALLCENTER_PROTECT_KEY' => array('varchar', 'caption=Защитен ключ за регистриране на обаждания->Ключ, width=100%'),
       'CALLCENTER_DRAFT_TO_NOANSWER' => array('time(suggestions=30 мин.|1 час|2 часа)', 'caption=След колко време да се промени от празно състояние в без отговор->Време, width=100px'),
       'CALLCENTER_MAX_CALL_DURATION' => array('time(suggestions=30 мин.|1 час|2 часа)', 'caption=Максимално време на продължителност на разговорите->Време, width=100px'),
       'CALLCENTER_DEVIATION_BETWEEN_TIMES' => array('time(suggestions=30 мин.|1 час|2 часа)', 'caption=Допустимото отклонение при регистриране на обажданията->Време, width=100px'),
       'CALLCENTER_ALLOWED_IP_ADDRESS' => array('varchar', 'caption=Разрешени IP адреси от които да се регистрира обаждане->IP адрес'),
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(2.04, 'Обслужване', 'Централа', 'callcenter_Talks', 'default', "user"),
        );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
      	$html = parent::install();
      	
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
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Прикачаме плъгина
        $html .= $Plugins->forcePlugin('Линкове към централа', 'callcenter_LinkPlg', 'drdata_PhoneType', 'private');
        
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
