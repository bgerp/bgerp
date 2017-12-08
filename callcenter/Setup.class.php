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
    var $info = "Център за телефонни обаждания. Връзка с тел. централа Asterisk";
    
    
    /**
     * Описание на системните действия
     */
    var $systemActions = array(
        array('title' => 'Актуализиране', 'url' => array('callcenter_Numbers', 'update', 'ret_url' => TRUE), 'params' => array('title' => 'Актуализиране на номерата'))
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
       'CALLCENTER_PROTECT_KEY' => array('varchar', 'caption=Защитен ключ за регистриране на обаждания->Ключ, width=100%'),
       'CALLCENTER_DRAFT_TO_NOANSWER' => array('time(suggestions=30 мин.|1 час|2 часа)', 'caption=След колко време да се промени от празно състояние в без отговор->Време, width=100px'),
       'CALLCENTER_MAX_CALL_DURATION' => array('time(suggestions=30 мин.|1 час|2 часа)', 'caption=Максимално време на продължителност на разговорите->Време, width=100px'),
       'CALLCENTER_DEVIATION_BETWEEN_TIMES' => array('time(suggestions=30 мин.|1 час|2 часа)', 'caption=Допустимото отклонение при регистриране на обажданията->Време, width=100px'),
       'CALLCENTER_ALLOWED_IP_ADDRESS' => array('varchar', 'caption=Разрешени IP адреси от които да се регистрира обаждане->IP адрес'),
       'CALLCENTER_SMS_SERVICE' => array('class(interface=callcenter_SentSMSIntf, select=title, allowEmpty)', 'caption=Изпращане на SMS->Услуга'),
       'CALLCENTER_SMS_SENDER' => array('varchar', 'caption=Изпращане на SMS->Изпращач'),
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(2.04, 'Обслужване', 'Централа', 'callcenter_Talks', 'default', "user"),
        );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'callcenter_Talks',
            'callcenter_Fax',
            'callcenter_SMS',
            'callcenter_Numbers',
            'callcenter_Hosts',
            'migrate::nullWrongAnswerAndEndTime',
            'migrate::fixDurationField',
            'migrate::clearBrokenNotificaions'
        );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
      	$html = parent::install();
      	
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
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
    

    /**
     * Проверява дали услугата позволява съответния изпращач
     * 
     * @return string
     */
    function checkConfig()
    {
        $conf = core_Packs::getConfig('callcenter');
        
        // Ако не е зададена услуга или изпращач
        if ((!$conf->CALLCENTER_SMS_SERVICE) || (!$conf->CALLCENTER_SMS_SENDER)) return ;
        
        if (!cls::load($conf->CALLCENTER_SMS_SERVICE, TRUE)) return ;
        
        // Инстанция на услугата
        $inst = cls::get($conf->CALLCENTER_SMS_SERVICE);
        
        // Параметри на услугата
        $paramsArr = $inst->getParams();
        
        // Ако не са зададени позволение имена за изпращач
        if (!$paramsArr['allowedUserNames']) return ;
        
        // Ако изпращача не е в допустимите
        if (!$paramsArr['allowedUserNames'][$conf->CALLCENTER_SMS_SENDER]) {
            
            // Стринг с позволените
            $allowedUsers = implode(', ', $paramsArr['allowedUserNames']);
            
            return "Невалиден изпращач. Позволените са: {$allowedUsers}";
        }
    }
    
    
    /**
     * Миграция за премахване на записите с `0000-00-00 00:00:00`
     */
    static function nullWrongAnswerAndEndTime()
    {
        $cnt = 0;
        $cQuery = callcenter_Talks::getQuery();
        $zeroTime = '0000-00-00 00:00:00';
        $cQuery->where("#answerTime = '{$zeroTime}'");
        $cQuery->orWhere("#endTime = '{$zeroTime}'");
        while ($rec = $cQuery->fetch()) {
            if ($rec->answerTime == $zeroTime) {
                $rec->answerTime = NULL;
            }
            
            if ($rec->endTime == $zeroTime) {
                $rec->endTime = NULL;
            }
            
            callcenter_Talks::save($rec);
            $cnt++;
        }
        
        if ($cnt) {
            return "<li class='green'>Оправени записи за времена на разговорите на {$cnt} записа</li>";
        }
    }
    
    
    /**
     * Миграция за добавяне на продължителност на разговорите
     */
    static function fixDurationField()
    {
        $cQuery = callcenter_Talks::getQuery();
        $cQuery->where("#duration IS NULL");
        while ($rec = $cQuery->fetch()) {
            $rec->duration = callcenter_Talks::getDuration($rec->answerTime, $rec->endTime);
            if (!$rec->duration) continue;
            callcenter_Talks::save($rec);
        }
    }
    
    
    /**
     * Изчиства старите (счупените) нотификация за пропуснато повикване
     */
    public static function clearBrokenNotificaions()
    {
        $urlArr = array('callcenter_Talks', 'list');
        
        bgerp_Notifications::clear($urlArr, '*');
    }
}
