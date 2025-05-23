<?php

/**
 * Колко време трябва да се пазат записите от индикаторите? 
 * По подразбиране 1000 дни
 */
defIfNot('SENS2_TIME_TO_KEEP_INDICATORS', '86400000');

/**
 * Колко време трябва да се пазат записите в логовете? 
 * По подразбиране 10 дни
 */
defIfNot('SENS2_TIME_TO_KEEP_LOGS', '864000');

/**
 * class sens2_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани със сензорите
 *
 *
 * @category  bgerp
 * @package   sens
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sens2_Setup extends core_ProtoSetup
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
    public $startCtr = 'sens2_Indicators';
    
    
    /**
     * Начален екшън на пакета за връзката в core_Packs
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Мониторинг на сензори и оборудване';
    

    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
                'SENS2_TIME_TO_KEEP_INDICATORS' => array(
                    'time(uom=days)',
                    'caption=Времена за запазване на записите->От индикаторите'
                ),
                'SENS2_TIME_TO_KEEP_LOGS' => array(
                    'time(uom=days)',
                    'caption=Времена за запазване на записите->В логовете'
                ),
            );
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'sens2_Indicators',
        'sens2_DataLogs',
        'sens2_Controllers',
        'sens2_Scripts',
        'sens2_script_Actions',
        'sens2_Semaphores',
        'sens2_script_DefinedVars',
        'sens2_IOPorts',
        'sens2_script_Logs',
        'migrate::changeToDot',
        'migrate::updateScriptKeywords2443',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'sens,sensMaster';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.4, 'Мониторинг', 'Сензори', 'sens2_Indicators', 'default', 'sens, ceo,admin'),
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = array(
        'sens2_script_ActionAssign',
        'sens2_MockupDrv',
        'sens2_ServMon',
        'sens2_DomainMon',
        'sens2_script_ActionSignal',
        'sens2_script_ActionSMS',
        'sens2_script_ActionNotify',
        'sens2_ioport_AI',
        'sens2_ioport_DI',
        'sens2_ioport_DO',
        'sens2_ioport_AO',
        'sens2_ioport_W1',
        'sens2_RemoteDriver',
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        $rec = new stdClass();
        $rec->systemId = 'sens2_UpdateIndications';
        $rec->description = 'Взима данни от активни сензори';
        $rec->controller = 'sens2_Controllers';
        $rec->action = 'Update';
        $rec->period = 1;
        $rec->offset = 0;
        $rec->timeLimit = 55;
        $html .= core_Cron::addOnce($rec);
        
        $rec = new stdClass();
        $rec->systemId = 'sens2_RunScripts';
        $rec->description = 'Изпълнява всички скриптове';
        $rec->controller = 'sens2_Scripts';
        $rec->action = 'RunAll';
        $rec->period = 1;
        $rec->delay = 15;
        $rec->timeLimit = 70;
        $html .= core_Cron::addOnce($rec);
        
        $rec = new stdClass();
        $rec->systemId = 'sens2_RunScripts2';
        $rec->description = 'Изпълнява всички скриптове';
        $rec->controller = 'sens2_Scripts';
        $rec->action = 'RunAll';
        $rec->period = 1;
        $rec->delay = 45;
        $rec->timeLimit = 70;
        $html .= core_Cron::addOnce($rec);
        
        $rec = new stdClass();
        $rec->systemId = 'sens2_DeleteExpiredData';
        $rec->description = 'Изтрива остарелите данни';
        $rec->controller = 'sens2_Controllers';
        $rec->action = 'RemoveExpiredRecords';
        $rec->period = 60 * 24;
        $rec->offset = (int) (60 * 2.5);
        $rec->timeLimit = 450;
        $html .= core_Cron::addOnce($rec);

        return $html;
    }
    
    
    /**
     * Смяна към точка
     */
    public function changeToDot()
    {
        $query = sens2_script_Actions::getQuery();
        
        while ($rec = $query->fetch()) {
            foreach (array('cond', 'expr', 'output') as $w) {
                if ($rec->data->{$w}) {
                    $rec->data->{$w} = str_replace('->', '.', $rec->data->{$w});
                    $query->mvc->save($rec);
                }
            }
        }
    }


    /**
     * Обновява ключовите думи на скриптовете
     *
     * @return void
     */
    public function updateScriptKeywords2443()
    {
        $Scripts = cls::get('sens2_Scripts');
        $sQuery = $Scripts->getQuery();
        while ($sRec = $sQuery->fetch()) {
            plg_Search::forceUpdateKeywords($Scripts, $sRec);
        }
    }
}
