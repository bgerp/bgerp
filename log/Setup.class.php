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
 *
 *
 *
 * @category  bgerp
 * @package   logs
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
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
            'log_Referer',
            'migrate::removeMaxCrc',
            'migrate::repairType'
        );
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
             
            'LOG_WARNING_TO_ERR_PERIOD' => array('time(suggestions=5 мин, 20 мин, 1 час)', 'caption=Период за преобразуване на предупрежденията в грешки->Максимално време'),
            'LOG_WARNING_TO_ERR_CNT' => array('int', 'caption=Брой записи над които предупрежденията ще са грешки->Брой'),
    );
    
    
    
    public static function removeMaxCrc()
    {
        $max = 2147483647;
        log_Actions::delete("#crc = '{$max}'");
        log_Classes::delete("#crc = '{$max}'");
        log_Data::delete("#actionCrc = '{$max}' OR #classCrc = '{$max}'");
    }

    
    /**
     * Поправя типовете след промяната
     */
    public static function repairType()
    {
        $query = log_Data::getQuery();
        $query->where("#type IS NULL OR #type = ''");
        
        while ($rec = $query->fetch()) {
            $rec->type = 'write';
            
            log_Data::save($rec, 'type');
        }
    }
}
