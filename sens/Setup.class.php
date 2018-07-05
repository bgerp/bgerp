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
class sens_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * От кои други пакети зависи
     */
    public $depends = 'permanent=0.1';
    
    
    /**
     * Начален контролер на пакета за връзката в core_Packs
     */
    public $startCtr = 'sens_Sensors';
    
    
    /**
     * Начален екшън на пакета за връзката в core_Packs
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Мониторинг на сензори и оборудване';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
            'sens_Sensors',
            'sens_IndicationsLog',
            'sens_MsgLog',
            'sens_Params',
            'sens_Overviews',
            'sens_OverviewDetails'
        );
    

    /**
     * Роли за достъп до модула
     */
    public $roles = 'sens';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
            array(3.4, 'Мониторинг', 'MOM', 'sens_Sensors', 'default', 'sens, ceo,admin'),
        );
    
        
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
                                 
        // Добавяме наличните драйвери
        $drivers = array(
            'sens_driver_Mockup',
            'sens_driver_HWgSTE',
            'sens_driver_TSM',
            'sens_driver_SATEC',
            'sens_driver_TCW121',
            'sens_driver_TCW122B'
        );
        
        foreach ($drivers as $drvClass) {
            $html .= core_Classes::add($drvClass);
            $drvObject = cls::get($drvClass);
            $drvObject->setParams();
            unset($drvObject);
        }
         
        $rec = new stdClass();
        $rec->systemId = 'sens_GetIndications';
        $rec->description = 'Вземат се данни от активни сензори';
        $rec->controller = 'sens_Sensors';
        $rec->action = 'Process';
        $rec->period = 1;
        $rec->offset = 0;
        $rec->timeLimit = 30;
        $html .= core_Cron::addOnce($rec);
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
}
