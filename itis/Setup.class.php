<?php

/**
 * Колко време трябва да се пазат записите в логовете? 
 * По подразбиране 100 дни
 */
defIfNot('ITIS_TIME_TO_KEEP_LOGS', '8640000');

/**
 * class itis_Setup
 *
 * Наблюдение на IT инфраструктура
 *
 *
 * @category  bgerp
 * @package   itis
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class itis_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * От кои други пакети зависи
     */
    public $depends = '';
    
    
    /**
     * Начален контролер на пакета за връзката в core_Packs
     */
    public $startCtr = 'itis_Devices';
    
    
    /**
     * Начален екшън на пакета за връзката в core_Packs
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Мониторинг на IT оборудване';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
                'ITIS_TIME_TO_KEEP_LOGS' => array(
                    'time(uom=days)',
                    'caption=Времена за запазване на записите->На ИТ устройствате'
                ),
            );
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'itis_Devices',
        'itis_Ports',
        'itis_Groups',
        'itis_Changelog',
        'itis_Values',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'itis, itisMaster';
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = array(
         
    );
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html .= parent::install();

        $rec = new stdClass();
        $rec->systemId = 'itis_DeleteOldRecords';
        $rec->description = 'Изтрива остарелите записи за IT устройства';
        $rec->controller = 'itis_Changelog';
        $rec->action = 'RemoveExpiredRecords';
        $rec->period = 3600 * 24;
        $rec->offset = (int) (3600 * 3.5);
        $rec->timeLimit = 170;
        $html .= core_Cron::addOnce($rec);

        return $html;
    }
 
}
