<?php


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
    public $depends = 'batch=0.1';
    
    
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
        'migrate::truncateOldRecs',
        'migrate::deleteOldPlugins',
        'migrate::updateNoBatchRackDetails2',
    );
    
    
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
     * Описание на конфигурационните константи
     */
    public $configDescription = array();
    
    
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
     * Деинсталиране на стари плъгини
     */
    public function deleteOldPlugins()
    {
        cls::get('core_Plugins')->deinstallPlugin('rack_plg_Document');
    }
    
    
    /**
     * Бъгфикс с без партида
     */
    public function updateNoBatchRackDetails2()
    {
        $Zones = cls::get('rack_ZoneDetails');
        $Zones->setupMvc();
        
        if (!$Zones->count()) {
            
            return;
        }
        
        $toSave = array();
        $zQuery = rack_ZoneDetails::getQuery();
        $zQuery->where('#batch IS NULL');
        while ($zRec = $zQuery->fetch()) {
            $zRec->batch = '';
            $toSave[$zRec->id] = $zRec;
        }
        
        if (count($toSave)) {
            $Zones->saveArray($toSave, 'id,batch');
        }
        
        $deleteArr = array();
        $query2 = $Zones->getQuery();
        $query2->where("#batch = ''");
        $query2->orderBy('id', 'ASC');
        while ($rec2 = $query2->fetch()) {
            $exRec = rack_ZoneDetails::fetch("#id != '{$rec2->id}' AND #zoneId = {$rec2->zoneId} AND #productId = {$rec2->productId} AND #packagingId = {$rec2->packagingId} AND #batch = ''");
            if (!$exRec) {
                continue;
            }
            
            $rec2->movementQuantity = !empty($rec2->movementQuantity) ? $rec2->movementQuantity : $exRec->movementQuantity;
            $rec2->documentQuantity = !empty($rec2->documentQuantity) ? $rec2->documentQuantity : $exRec->documentQuantity;
            
            $Zones->save($rec2, 'movementQuantity,documentQuantity');
            $deleteArr[$exRec->id] = $exRec->id;
        }
        
        foreach ($deleteArr as $delId) {
            rack_ZoneDetails::delete($delId);
        }
    }
}
