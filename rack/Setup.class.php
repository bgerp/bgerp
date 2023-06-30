<?php


/**
 * След колко време да се изтриват старите движение
 */
defIfNot('RACK_DELETE_OLD_MOVEMENTS', 5184000);


/**
 * След колко време да се изтриват архивораните движения
 */
defIfNot('RACK_DELETE_ARCHIVED_MOVEMENTS', dt::SECONDS_IN_MONTH * 12);


/**
 * Да се допуска ли колизия на палети
 */
defIfNot('RACK_DIFF_PALLETS_IN_SAME_POS', 'no');


/**
 * Дефолтен цвят за зоните
 */
defIfNot('RACK_DEFAULT_ZONE_COLORS', '#eeeeee');


/**
 * Коя стратегия за движенията да се използва
 */
defIfNot('RACK_PICKUP_STRATEGY', 'ver1');


/**
 * Средни времена по операции->Вземане на палет
 */
defIfNot('RACK_TIME_GET', 20);


/**
 * Средни времена по операции->Вземане на палет от ред А
 */
defIfNot('RACK_TIME_GET_A', 19);


/**
 * Средни времена по операции->Оставяне в зона
 */
defIfNot('RACK_TIME_ZONE', 5);


/**
 * Средни времена по операции->Връщане
 */
defIfNot('RACK_TIME_RETURN', 10);


/**
 * Средни времена по операции->Броене
 */
defIfNot('RACK_TIME_COUNT', 4);


/**
 * Приключване на движения в терминала
 */
defIfNot('RACK_CLOSE_COMBINED_MOVEMENTS_AT_ONCE', 'no');


/**
 * Използване на приоритетни стелажи
 */
defIfNot('RACK_ENABLE_PRIORITY_RACKS', 'no');


/**
 * class rack_Setup
 *
 * Инсталиране/Деинсталиране на пакета за палетния склад
 *
 *
 * @category  bgerp
 * @package   rack
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
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
    public $depends = 'batch=0.1,store=0.1';
    
    
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
        'rack_ZoneGroups',
        'rack_Zones',
        'rack_ZoneDetails',
        'rack_OccupancyOfRacks',
        'rack_OldMovements',
        'rack_Logs',
        'rack_ProductsByBatches',
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'rack_reports_DurationPallets';
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = array('rackZoneSelect', 'rackSee',
                    array('rack', 'rackSee,rackZoneSelect'),
                    array('rackMaster', 'rack'));
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.2, 'Логистика', 'Стелажи', 'rack_Movements', 'default', 'rackSee,ceo'),
    );


    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'Delete movements and pallets',
            'description' => 'Изтриване на остарели движения и палети',
            'controller' => 'rack_Movements',
            'action' => 'DeleteOldMovementsAndPallets',
            'period' => 1440,
            'offset' => 90,
            'timeLimit' => 100
        ),
        
        array(
            'systemId' => 'Update Racks',
            'description' => 'Обновяване на информацията за стелажите',
            'controller' => 'rack_Racks',
            'action' => 'update',
            'period' => 60,
            'offset' => 55,
            'timeLimit' => 20,
            'delay' => 0,
        ),
        
        array(
            'systemId' => 'Get occupancy of racks',
            'description' => 'Запис на текущото състояние на стелажите',
            'controller' => 'rack_OccupancyOfRacks',
            'action' => 'GetOccupancyOfRacks',
            'period' => 1440,
            'timeLimit' => 20,
            'offset' => 5,
        )
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->installPlugin('Връзка между ЕН-то и палетния склад', 'rack_plg_Shipments', 'store_ShipmentOrders', 'private');
        $html .= $Plugins->installPlugin('Връзка между МСТ-то и палетния склад', 'rack_plg_Shipments', 'store_Transfers', 'private');
        $html .= $Plugins->installPlugin('Връзка между протокола за влагане в производството и палетния склад', 'rack_plg_Shipments', 'planning_ConsumptionNotes', 'private');
        $html .= $Plugins->installPlugin('Връзка между протокола за отговорно пазене и палетния склад', 'rack_plg_Shipments', 'store_ConsignmentProtocols', 'private');
        
        $html .= $Plugins->installPlugin('Връзка между СР-то и входящия палетен склад', 'rack_plg_IncomingShipmentDetails', 'store_ReceiptDetails', 'private');
        $html .= $Plugins->installPlugin('Връзка между МСТ-то и входящия палетен склад', 'rack_plg_IncomingShipmentDetails', 'store_TransfersDetails', 'private');
        $html .= $Plugins->installPlugin('Връзка между Протокола за влагане и и входящия палетен склад', 'rack_plg_IncomingShipmentDetails', 'planning_ReturnNoteDetails', 'private');
        
        return $html;
    }


    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'RACK_DELETE_OLD_MOVEMENTS' => array('time','caption=Изтриване на стари движения->Период'),
        'RACK_DELETE_ARCHIVED_MOVEMENTS' => array('time','caption=Изтриване на архивирани движения->Период'),
        'RACK_DIFF_PALLETS_IN_SAME_POS' => array('enum(no=Не,yes=Да)', 'caption=Различни палети на една позиция->Разрешаване'),
        'RACK_DEFAULT_ZONE_COLORS' => array('color_Type','caption=Козметични настройки на зоните->Цвят'),
        'RACK_CLOSE_COMBINED_MOVEMENTS_AT_ONCE' => array('enum(yes=Еднократно за цялото движение,no=Зона по зона)', 'caption=Приключване на комбинирани движения в терминала->Приключване'),
        'RACK_PICKUP_STRATEGY' => array('enum(ver1,ver2)', 'caption=Стратегия за генериране на движенията->Избор'),
        'RACK_TIME_GET' => array('int', 'caption=Средни времена по операции->Вземане'),
        'RACK_TIME_GET_A' => array('int', 'caption=Средни времена по операции->Вземане ot A'),
        'RACK_TIME_ZONE' => array('int', 'caption=Средни времена по операции->Оставяне'),
        'RACK_TIME_RETURN' => array('int', 'caption=Средни времена по операции->Връщане'),
        'RACK_TIME_COUNT' => array('int', 'caption=Средни времена по операции->Броене'),
        'RACK_ENABLE_PRIORITY_RACKS' => array('enum(yes=Да,no=Не)', 'caption=Използване на приоритетни стелажи->Разрешаване'),
    );


    /**
     * Изпълнява се след setup-а
     */
    public function checkConfig()
    {
        $sMvc = cls::get('store_Stores');
        $sMvc->setupMVC();
    }
}
