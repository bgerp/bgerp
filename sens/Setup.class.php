<?php



/**
 * class acc_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани със сензорите
 *
 *
 * @category  bgerp
 * @package   sens
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sens_Setup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * От кои други пакети зависи
     */
    var $depends = 'permanent=0.1';
    
    
    /**
     * Начален контролер на пакета за връзката в core_Packs
     */
    var $startCtr = 'sens_Sensors';
    
    
    /**
     * Начален екшън на пакета за връзката в core_Packs
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Мониторинг на сензори и оборудване";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'sens_Sensors',
            'sens_IndicationsLog',
            'sens_MsgLog',
            'sens_Params',
            'sens_Overviews',
            'sens_OverviewDetails'
        );
        
        // Роля за power-user на този модул
        $role = 'sens';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        // Добавяме наличните драйвери
        $drivers = array(
            'sens_driver_Mockup',
            'sens_driver_HWgSTE',
            'sens_driver_TSM',
            'sens_driver_SATEC',
            'sens_driver_TCW121'
        );
        
        foreach ($drivers as $drvClass) {
            $html .= core_Classes::add($drvClass);
            $drvObject = cls::get($drvClass);
            $drvObject->setParams();
            unset($drvObject);
        }
        
        $Menu = cls::get('bgerp_Menu');
        $Menu->addItem(3, 'Мониторинг', 'MOM', 'sens_Sensors', 'default', "{$role}, admin");
        
        $Cron = cls::get('core_Cron');
        
        $rec = new stdClass();
        $rec->systemId = "sens_GetIndications";
        $rec->description = "Взима данни от активни сензори";
        $rec->controller = "sens_Sensors";
        $rec->action = "Process";
        $rec->period = 1;
        $rec->offset = 0;
        $rec->timeLimit = 30;
        $Cron->addOnce($rec);
        
        $html .= "<li style='color:#660000'>На Cron e зададенo да следи сензорите</li>";
        
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
