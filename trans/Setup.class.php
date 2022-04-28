<?php


/**
 * Дефолтен текст за инструкции на изпращача
 */
defIfNot('TRANS_CMR_SENDER_INSTRUCTIONS', '');


/**
 * Дали да се показва бутона за ЧМР, ако не е избрано условие на доставка
 */
defIfNot('TRANS_CMR_SHOW_BTN', 'no');


/**
 * От коя дата да започнат да се изчисляват индикаторите за транспортните линии
 */
defIfNot('TRANS_DATE_FOR_TRANS_INDICATORS', '');


/**
 * Автоматично затваряне на транспортни линии активни от
 */
defIfNot('TRANS_LINES_ACTIVATED_AFTER', '5184000');


/**
 * Автоматично затваряне на транспортни линии чакащи с минала дата
 */
defIfNot('TRANS_LINES_PENDING_AFTER', '604800');


/**
 * Показване на бутон за ВОД към ЕН
 */
defIfNot('TRANS_SHOW_VOD_BTN', 'auto');


/**
 * Логистична информация в транспортните документи при печат/изпращане
 */
defIfNot('TRANS_SHOW_LOG_INFO_IN_DOCUMENTS', 'show');


/**
 * Начало на работния ден за доставка
 */
defIfNot('TRANS_START_WORK_TIME', '08:08');


/**
 * Край на работния ден за доставка
 */
defIfNot('TRANS_END_WORK_TIME', '17:17');



/**
 * Транспорт
 *
 *
 * @category  bgerp
 * @package   trans
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class trans_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'trans_Lines';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Организация на вътрешния транспорт';
    
    
    /**
     * Необходими пакети
     */
    public $depends = 'store=0.1,sales=0.1,cash=0.1,deals=0.1,crm=0.1';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'trans_Vehicles',
        'trans_Lines',
        'trans_Cmrs',
        'trans_TransportModes',
        'trans_TransportUnits',
        'trans_Features',
        'trans_LineDetails',
        'trans_IntraCommunitySupplyConfirmations',
    );


    /**
     * Роли за достъп до модула
     */
    public $roles = array('trans',
                    array('transMaster', 'trans'));


    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'trans_Indicators,trans_reports_LinesByForwarder';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.3, 'Логистика', 'Транспорт', 'trans_Lines', 'default', 'trans, ceo'),
    );


    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'Close Trans Lines',
            'description' => 'Затваряне на транспортни линии',
            'controller' => 'trans_Lines',
            'action' => 'CloseTransLines',
            'period' => 1440,
            'timeLimit' => 360
        ),
    );


    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'TRANS_LINES_ACTIVATED_AFTER' => array('time','caption=Автоматично затваряне на транспортни линии->Активни от'),
        'TRANS_LINES_PENDING_AFTER' => array('time','caption=Автоматично затваряне на транспортни линии->Чакащи с минала дата'),
        'TRANS_CMR_SENDER_INSTRUCTIONS' => array('text(rows=2)','caption=ЧМР->13. Инструкции на изпращача'),
        'TRANS_CMR_SHOW_BTN' => array('enum(yes=Включено,no=Изключено)','caption=При липса на условие на доставка. Да се показва ли бутона за ЧМР->Избор'),
        'TRANS_DATE_FOR_TRANS_INDICATORS' => array('date', 'caption=От коя дата да се изчисляват индикатори транспортни линии->Дата'),
        'TRANS_SHOW_VOD_BTN' => array('enum(always=Винаги,auto=Условието на доставка + Чужбина ЕС,never=Никога)','caption=Показване на бутон за ВОД към ЕН->Избор'),
        'TRANS_SHOW_LOG_INFO_IN_DOCUMENTS' => array('enum(show=Показване, hide=Скриване)','caption=Логистична информация в транспортните документи при печат/изпращане->Избор,customizeBy=trans|store|sales|ceo'),

        'TRANS_START_WORK_TIME' => array('hour','caption=Начало и край на работния ден за датите в складовите документи->Начало'),
        'TRANS_END_WORK_TIME' => array('hour','caption=Начало и край на работния ден за датите в складовите документи->Край'),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Добавяне на системна група за водачи на МПС
        $groupRec = (object)array('name' => 'Водачи МПС', 'sysId' => 'vehicleDrivers', 'allow' => 'persons');
        crm_Groups::forceGroup($groupRec);
        
        return $html;
    }
}
