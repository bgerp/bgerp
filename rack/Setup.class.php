<?php


/**
 * Колко време след като е направено движението да се изтрие
 */
defIfNot('RACK_DELETE_MOVEMENTS_OLDER_THAN', '');


/**
 * class rack_Setup
 *
 * Инсталиране/Деинсталиране на пакета за палетния склад
 *
 *
 * @category  bgerp
 * @package   rack
 *
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class rack_Setup extends core_ProtoSetup
{
    /**
     * Версия на компонента
     */
    public $version = '0.1';
    
    
    /**
     * Необходими пакети
     */
    public $depends = 'acc=0.1';
    
    
    /**
     * Стартов контролер за връзката в системното меню
     */
    public $startCtr = 'rack_Movements';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Палетно складово стопанство';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'rack_Products',
        'rack_Movements',
        'rack_Pallets',
        'rack_Racks',
        'rack_RackDetails',
        'rack_Zones',
        'rack_ZoneDetails',
        'migrate::truncateOldRecs',
        'migrate::updateFloor'
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'rack,rackMaster';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.2, 'Логистика', 'Стелажи', 'rack_Movements', 'default', 'rack,ceo,store,storeWorker'),
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'RACK_DELETE_MOVEMENTS_OLDER_THAN' => array('time(suggestions=1 месец|2 месеца|3 месеца|6 месеца)', 'caption=Изтриване на минали движения->От преди'),
    );
    
    
    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'Delete movements',
            'description' => 'Изтриване на остарели движения',
            'controller' => 'rack_Movements',
            'action' => 'DeleteOldMovements',
            'period' => 1440,
            'offset' => 90,
            'timeLimit' => 100
        ));
        

    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->installPlugin('Връзка между ЕН-то и палетния склад', 'rack_plg_Shipments', 'store_ShipmentOrders', 'private');
        $html .= $Plugins->installPlugin('Връзка между МСТ-то и палетния склад', 'rack_plg_Shipments', 'store_Transfers', 'private');
        
        // Залагаме в cron
        $rec = new stdClass();
        $rec->systemId = 'Update Racks';
        $rec->description = 'Обновяване на информацията за стелажите';
        $rec->controller = 'rack_Racks';
        $rec->action = 'update';
        $rec->period = 60;
        $rec->offset = rand(5, 55);
        $rec->delay = 0;
        $rec->timeLimit = 20;
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
    
    
    /**
     * Изпълнява се след setup-а
     */
    public function checkConfig()
    {
        $sMvc = cls::get('store_Stores');
        $sMvc->setupMVC();
    }
    
    
    /**
     * Изпълнява се след setup-а
     */
    public function truncateOldRecs()
    {
        foreach (array('rack_Pallets', 'rack_RackDetails', 'rack_Zones', 'rack_ZoneDetails', 'rack_Movements') as $class) {
            $Class = cls::get($class);
            $Class->setupMvc();
            $Class->truncate();
        }
    }
    
    
    /**
     * Обновяване на пода
     */
    public function updateFloor()
    {
        core_App::setTimeLimit(300);
        $Movements = cls::get('rack_Movements');
        $Movements->setupMvc();
        
        $query = $Movements->getQuery();
        $query->where("#palletId IS NULL OR #palletToId IS NULL");
        
        while($rec = $query->fetch()){
            $saveFields = array();
            if(empty($rec->position)){
                $rec->position = rack_PositionType::FLOOR;
                $saveFields['position'] = 'position';
            }
            
            if(empty($rec->positionTo)){
                $rec->positionTo = rack_PositionType::FLOOR;
                $saveFields['positionTo'] = 'positionTo';
            }
            
            if(count($saveFields)){
                $Movements->save_($rec, $saveFields);
            }
        }
    }
}
