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
defIfNot('CALLCENTER_DRAFT_TO_NOANSWER', '7200');


/**
 * Максимална продължителност на разговорите
 */
defIfNot('CALLCENTER_MAX_CALL_DURATION', '7200');


/**
 * Допустимото отклонение в секуди при регистриране на обажданията
 */
defIfNot('CALLCENTER_DEVIATION_BETWEEN_TIMES', '7200');


/**
 * Услуга за изпращане на SMS
 */
defIfNot('CALLCENTER_SMS_SERVICE', '');


/**
 * Данни за изпращача
 */
defIfNot('CALLCENTER_SMS_SENDER', '');


/**
 * Записване на вътрешните обаждания
 */
defIfNot('CALLCENTER_SAVE_INNTERNAL_CALLS', 'no');


/**
 * Записване на изходящите обаждания
 */
defIfNot('CALLCENTER_SAVE_OUTGOING_CALLS', 'no');


/**
 * Записване на обажданията към нерегистрирани вътрешни номера
 */
defIfNot('CALLCENTER_SAVE_CALLS_FOR_NON_EXIST_INTERNAL_NUMS', 'yes');


/**
 * Максимална дълбина на вътрешните номера
 */
defIfNot('CALLCENTER_MAX_INTERNAL_NUM_LENGTH', 6);


/**
 * Инсталиране/Деинсталиране на мениджъри свързани с callcenter модула
 *
 * @category  bgerp
 * @package   callcenter
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class callcenter_Setup extends core_ProtoSetup
{
    /**
     * Версията на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Необходими пакети
     */
    public $depends = 'ssh=0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'callcenter_Talks';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Център за телефонни обаждания. Връзка с тел. централа Asterisk';
    
    
    /**
     * Описание на системните действия
     */
    public $systemActions = array(
        array('title' => 'Актуализиране', 'url' => array('callcenter_Numbers', 'update', 'ret_url' => true), 'params' => array('title' => 'Актуализиране на номерата'))
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'CALLCENTER_PROTECT_KEY' => array('varchar', 'caption=Защитен ключ за регистриране на обаждания->Ключ, width=100%'),
        'CALLCENTER_DRAFT_TO_NOANSWER' => array('time(suggestions=30 мин.|1 час|2 часа)', 'caption=След колко време да се промени от празно състояние в без отговор->Време, width=100px'),
        'CALLCENTER_MAX_CALL_DURATION' => array('time(suggestions=30 мин.|1 час|2 часа)', 'caption=Максимално време на продължителност на разговорите->Време, width=100px'),
        'CALLCENTER_DEVIATION_BETWEEN_TIMES' => array('time(suggestions=30 мин.|1 час|2 часа)', 'caption=Допустимото отклонение при регистриране на обажданията->Време, width=100px'),
        'CALLCENTER_ALLOWED_IP_ADDRESS' => array('varchar', 'caption=Разрешени IP адреси от които да се регистрира обаждане->IP адрес'),
            
        'CALLCENTER_SAVE_INNTERNAL_CALLS' => array('enum(no=Не,yes=Да)', 'caption=Записване на вътрешните обаждания->Избор'),
        'CALLCENTER_SAVE_OUTGOING_CALLS' => array('enum(no=Не,yes=Да)', 'caption=Записване на изходящите обаждания->Избор'),
        'CALLCENTER_SAVE_CALLS_FOR_NON_EXIST_INTERNAL_NUMS' => array('enum(yes=Да,no=Не)', 'caption=Записване на обажданията към нерегистрирани вътрешни номера->Избор'),
            
        'CALLCENTER_SMS_SERVICE' => array('class(interface=callcenter_SentSMSIntf, select=title, allowEmpty)', 'caption=Изпращане на SMS->Услуга'),
        'CALLCENTER_SMS_SENDER' => array('varchar', 'caption=Изпращане на SMS->Изпращач'),
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(2.04, 'Обслужване', 'Централа', 'callcenter_Talks', 'default', 'powerUser'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'callcenter_Talks',
        'callcenter_Fax',
        'callcenter_SMS',
        'callcenter_Numbers',
        'callcenter_Hosts',
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Прикачаме плъгина
        $html .= $Plugins->forcePlugin('Линкове към централа', 'callcenter_LinkPlg', 'drdata_PhoneType', 'private');
        
        return $html;
    }
    
    
    /**
     * Проверява дали услугата позволява съответния изпращач
     *
     * @return string
     */
    public function checkConfig()
    {
        $conf = core_Packs::getConfig('callcenter');
        
        // Ако не е зададена услуга или изпращач
        if ((!$conf->CALLCENTER_SMS_SERVICE) || (!$conf->CALLCENTER_SMS_SENDER)) {
            
            return ;
        }
        
        if (!cls::load($conf->CALLCENTER_SMS_SERVICE, true)) {
            
            return ;
        }
        
        // Инстанция на услугата
        $inst = cls::get($conf->CALLCENTER_SMS_SERVICE);
        
        // Параметри на услугата
        $paramsArr = $inst->getParams();
        
        // Ако не са зададени позволение имена за изпращач
        if (!$paramsArr['allowedUserNames']) {
            
            return ;
        }
        
        // Ако изпращача не е в допустимите
        if (!$paramsArr['allowedUserNames'][$conf->CALLCENTER_SMS_SENDER]) {
            
            // Стринг с позволените
            $allowedUsers = implode(', ', $paramsArr['allowedUserNames']);
            
            return "Невалиден изпращач. Позволените са: {$allowedUsers}";
        }
    }
}
