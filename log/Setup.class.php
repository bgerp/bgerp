<?php


/**
 * Период за преобразуване на предупрежденията в грешки
 */
defIfNot('LOG_WARNING_TO_ERR_PERIOD', 1200);


/**
 * Брой записи над които предупрежденията ще са грешки
 */
defIfNot('LOG_WARNING_TO_ERR_CNT', 3);


/**
 * Изпращане на системни известия
 */
defIfNot('LOG_ADD_SYSTEM_NOTIFICATIONS', 'yes');


/**
 * Колко време да се съхраняват brid за браузъри, за които няма информация - 1 години
 */
defIfNot('LOG_EMPTY_BRID_KEEP_DAYS', 31556952);


/**
 *
 *
 *
 * @category  bgerp
 * @package   logs
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class log_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'log_Data';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Логове и нотификации';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'log_System',
        'log_Data',
        'log_Actions',
        'log_Browsers',
        'log_Classes',
        'log_Ips',
        'log_Debug',
        'log_Mysql',
    );
    

    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'UpdateIpInfo',
            'description' => 'Извличане на информация за IP-та',
            'controller' => 'log_Ips',
            'action' => 'UpdateIpInfo',
            'period' => 3,
            'offset' => 0,
            'timeLimit' => 100,
             ),
        );

    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
        'LOG_WARNING_TO_ERR_PERIOD' => array('time(suggestions=5 мин, 20 мин, 1 час)', 'caption=Период за преобразуване на предупрежденията в грешки->Максимално време'),
        'LOG_WARNING_TO_ERR_CNT' => array('int', 'caption=Брой записи над които предупрежденията ще са грешки->Брой'),
        'LOG_ADD_SYSTEM_NOTIFICATIONS' => array('enum(yes=Да,no=Не)', 'caption=Показване на системните известия->Избор, customizeBy=admin'),
        'LOG_EMPTY_BRID_KEEP_DAYS' => array('time(suggestions=6 месеца|1 година|2 години|3 години,unit=days)', 'caption=Време за съхранение на празните BRID->Време'),
    );


    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();

        $rec = new stdClass();
        $rec->systemId = 'DeleteOldEmptyBrid';
        $rec->description = 'Изтриване на старите и празни BRID записи';
        $rec->controller = 'log_Browsers';
        $rec->action = 'DeleteOldEmptyBrid';
        $rec->period = 24 * 60;
        $rec->timeLimit = 50;
        $rec->offset = mt_rand(0, 300);
        $rec->isRandOffset = true;
        $html .= core_Cron::addOnce($rec);

        return $html;
    }
}
