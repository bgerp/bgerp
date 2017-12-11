<?php



/**
 * class sens2_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани със сензорите
 *
 *
 * @category  bgerp
 * @package   sens
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sens2_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * От кои други пакети зависи
     */
    var $depends = '';
    
    
    /**
     * Начален контролер на пакета за връзката в core_Packs
     */
    var $startCtr = 'sens2_Indicators';
    
    
    /**
     * Начален екшън на пакета за връзката в core_Packs
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Мониторинг на сензори и оборудване";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'sens2_Indicators',
            'sens2_DataLogs',
            'sens2_Controllers',
            'sens2_Scripts',
            'sens2_ScriptActions',
            'sens2_ScriptDefinedVars'
        );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'sens';
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    var $defClasses = "sens2_reports_DataLog";
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.4, 'Мониторинг', 'Сензори', 'sens2_Indicators', 'default', "sens, ceo,admin"),
        );
    
        
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $html = parent::install();
                                 
        // Добавяме наличните драйвери
        $drivers = array(
            'sens2_MockupDrv',
            'sens2_ServMon',
            'sens2_ScriptActionAssign',
            'sens2_ScriptActionSignal',
            'sens2_ScriptActionSMS',
            'sens2_ScriptActionNotify',
        );
        
        foreach ($drivers as $drvClass) {
            $html .= core_Classes::add($drvClass);
        }
         
        $rec = new stdClass();
        $rec->systemId = "sens2_UpdateIndications";
        $rec->description = "Взима данни от активни сензори";
        $rec->controller = "sens2_Controllers";
        $rec->action = "Update";
        $rec->period = 1;
        $rec->offset = 0;
        $rec->timeLimit = 55;
        $html .= core_Cron::addOnce($rec);
        
        $rec = new stdClass();
        $rec->systemId = "sens2_RunScripts";
        $rec->description = "Изпълнява всички скриптове";
        $rec->controller = "sens2_Scripts";
        $rec->action = "RunAll";
        $rec->period = 1;
        $rec->delay = 15;
        $rec->timeLimit = 55;
        $html .= core_Cron::addOnce($rec);
         
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
}
