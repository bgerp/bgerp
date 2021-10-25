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
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'rack_reports_DurationPallets';
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = array('rack', array('rackMaster', 'rack'));
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.2, 'Логистика', 'Стелажи', 'rack_Movements', 'default', 'rack,ceo'),
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
