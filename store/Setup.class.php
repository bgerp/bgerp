<?php


/**
 * Кои сч. сметки ще се използват за синхронизиране със склада
 */
defIfNot('STORE_ACC_ACCOUNTS', '');


/**
 * Групиране на тарифните номера по част от него
 */
defIfNot('STORE_TARIFF_NUMBER_LENGTH', '8');


/**
 * Подготовка преди експедиция
 */
defIfNot('STORE_PREPARATION_BEFORE_SHIPMENT', '');


/**
 * Иьчисляване на най-ранната наличност на ЕН-та в рамките на
 */
defIfNot('STORE_EARLIEST_SHIPMENT_READY_IN', 14);


/**
 * Изписване на минус -> Роли
 */
defIfNot('STORE_ALLOW_NEGATIVE_SHIPMENT_ROLES', '');


/**
 * ЕН за митница - вид на опаковката
 */
defIfNot('STORE_SO_TYPE_OF_PACKING_DEFAULT', '');


/**
 * class store_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани със складовете
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_Setup extends core_ProtoSetup
{
    /**
     * Систем ид-та на счетоводните сметки за синхронизация
     */
    protected static $accAccount = array('321', '302');
    
    
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
    public $startCtr = 'store_Products';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Управление на складове и складови документи';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'store_Stores',
        'store_Products',
        'store_DocumentPackagingDetail',
        'store_ShipmentOrders',
        'store_ShipmentOrderDetails',
        'store_Receipts',
        'store_ReceiptDetails',
        'store_Transfers',
        'store_TransfersDetails',
        'store_ConsignmentProtocols',
        'store_ConsignmentProtocolDetailsSend',
        'store_ConsignmentProtocolDetailsReceived',
        'store_InventoryNotes',
        'store_InventoryNoteSummary',
        'store_InventoryNoteDetails',
        'store_StockPlanning',
        'store_ShipmentOrderTariffCodeSummary',
        'migrate::repairSearchKeywords2505',
        'migrate::repairSearchKeywordsNotes2505',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = array(
        array('contoNegativeQuantities'),
        array('storeWorker'),
        array('inventory'),
        array('store', 'storeWorker'),
        array('storeMaster', 'store'),
        array('revertShipmentDocs'),
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.2, 'Логистика', 'Склад', 'store_Products', 'default', 'storeWorker,ceo,storeAll'),
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'STORE_ACC_ACCOUNTS' => array('acc_type_Accounts(regInterfaces=store_AccRegIntf|cat_ProductAccRegIntf)', 'caption=Складова синхронизация със счетоводството->Сметки'),
        'STORE_TARIFF_NUMBER_LENGTH' => array('int', 'caption=Групиране на тарифните номера по част от него->Първите,unit=цифри'),
        'STORE_PREPARATION_BEFORE_SHIPMENT' => array('time(suggestions=1 ден|2 дена|3 дена|1 седмица)', 'caption=Подготовка преди експедиция->Време'),
        'STORE_EARLIEST_SHIPMENT_READY_IN' => array('int(min=0)', 'caption=Изчисляване на най-ранната наличност на артикулите в ЕН-та за следващите->Дни'),
        'STORE_ALLOW_NEGATIVE_SHIPMENT_ROLES' => array('keylist(mvc=core_Roles,select=role)', 'caption=Изписване на минус->Да се използва с повишено внимание и на собствен риск! Формираните отрицателни количества ТРЯБВА да бъдат коригирани в рамките на счетоводния период (месец) в който са възникнали! Основни причини: невъведени/грешновъведени доставки/изписване от грешен склад/детайлно изписване от незавършено производство без такова влагане и т.н... Отрицателни наличности в края на периода водят до нереални счетоводни себестойности!->Роли'),
        'STORE_SO_TYPE_OF_PACKING_DEFAULT' => array('varchar', 'caption=Обобщен ред на "Packing list за митница"->Вид на опаковката'),
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'store_reports_Documents,store_reports_ChangeQuantity,store_reports_ProductAvailableQuantity,
                          store_iface_ImportShippedProducts,store_reports_DeficitInStores,store_reports_UnfulfilledQuantities,
                          store_reports_ArticlesDepended,store_reports_ProductsInStock,store_reports_UnrealisticPricesAndWeights,
                          store_reports_ProductAvailableQuantity1,store_reports_JobsHorizons,store_tpl_SingleLayoutPackagingListGrouped,
                          store_tpl_SingleLayoutShipmentOrderEuro,store_iface_ShipmentWithBomPriceTplHandler,store_iface_OpeningBalanceImportImpl,
                          store_reports_NonPublicItems';
    
    
    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'Update Reserved Stocks',
            'description' => 'Обновяване на резервираните наличности',
            'controller' => 'store_Products',
            'action' => 'CalcReservedQuantity',
            'period' => 5,
            'offset' => 0,
            'timeLimit' => 100
        ),
        array(
            'systemId' => 'Update Shipment Document Readiness',
            'description' => 'Обновяване на готовността на складовите документи на заявка',
            'controller' => 'store_Products',
            'action' => 'UpdateShipmentDocumentReadiness',
            'period' => 3,
            'offset' => 0,
            'timeLimit' => 100
        ),
        array(
            'systemId' => 'Recalc Shipment Document Dates',
            'description' => 'Кеширане на изчисленията на датите в складовите документи',
            'controller' => 'store_Setup',
            'action' => 'RecalcShipmentDates',
            'period' => 60,
            'offset' => 10,
            'timeLimit' => 300,
        ),

    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Закачане на плъгина за прехвърляне на собственотст на системни папки към core_Users
        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->installPlugin('Синхронизиране на складовите наличности', 'store_plg_BalanceSync', 'acc_Balances', 'private');

        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->installPlugin('Връзка на ЕН-та с куриерско API', 'store_plg_CourierApiShipment', 'store_ShipmentOrders', 'private');
        $html .= cls::get('store_ShipmentOrders')->setupMvc();

        return $html;
    }
    
    
    /**
     * Зареждане на данните
     */
    public function loadSetupData($itr = '')
    {
        $res = parent::loadSetupData($itr);
        
        // Ако няма посочени от потребителя сметки за синхронизация
        $config = core_Packs::getConfig('store');

        if (strlen($config->STORE_ACC_ACCOUNTS) === 0) {
            $accArray = array();
            foreach (static::$accAccount as $accSysId) {
                $accId = acc_Accounts::getRecBySystemId($accSysId)->id;
                $accArray[$accId] = $accSysId;
            }
            
            // Записват се ид-та на дефолт сметките за синхронизация
            core_Packs::setConfig('store', array('STORE_ACC_ACCOUNTS' => keylist::fromArray($accArray)));
            $res .= "<li style='color:green'>Дефолт счетодовни сметки за синхронизация на продуктите<b>" . implode(',', $accArray) . '</b></li>';
        }

        $repairAccountsDefault = core_Packs::getConfigValue('store', 'STORE_ALLOW_NEGATIVE_SHIPMENT_ROLES');
        if (strlen($repairAccountsDefault) === 0) {
            core_Packs::setConfig('store', array('STORE_ALLOW_NEGATIVE_SHIPMENT_ROLES' => core_Roles::getRolesAsKeylist('contoNegativeQuantities')));
        }

        return $res;
    }


    /**
     * Може ли да се експедират отрицателни к-ва
     *
     * @param int|null $userId
     * @return bool
     */
    public static function canDoShippingWhenStockIsNegative($userId = null)
    {
        $allowedRoles = store_Setup::get('ALLOW_NEGATIVE_SHIPMENT_ROLES');
        if(Mode::is('closedDealCall')) return true;

        if(empty($allowedRoles)) return false;

        return haveRole($allowedRoles, $userId);
    }


    /**
     * Обновяване по разписание
     */
    function cron_RecalcShipmentDates()
    {
        // Кои са складовите документи в системата
        $storableDocuments = core_Classes::getOptionsByInterface('store_iface_DocumentIntf');
        $transportableIntf = core_Classes::getOptionsByInterface('trans_TransportableIntf');
        $classesToRecalc = array_intersect_key($storableDocuments, $transportableIntf);

        foreach ($classesToRecalc as $class){
            $toSave = array();
            $Class = cls::get($class);

            // Ако има полета за дати в тях
            $dateData = $Class->getShipmentDateFields();
            if(!countR($dateData)) return;

            $updateFields = array('id');
            array_walk($dateData, function($a) use(&$updateFields) {if(isset($a['autoCalcFieldName'])) {$updateFields[$a['autoCalcFieldName']] = $a['autoCalcFieldName'];}});

            // Обикалят се всички заявки и чакащи и се преизчислява това им поле
            $query = $Class->getQuery();
            $query->where("#state = 'pending' || #state = 'draft'");
            while($rec = $query->fetch()){
                if($Class->recalcShipmentDateFields($rec, false)){
                    $toSave[$rec->id] = $rec;
                }
            }

            // Обновяване на всички записи
            if(countR($toSave)){
                $Class->saveArray($toSave, $updateFields);
            }
        }
    }


    /**
     * Миграция за регенериране на ключовите думи на ПОП
     */
    public static function repairSearchKeywords2505()
    {
        $callOn = dt::addSecs(120);
        core_CallOnTime::setCall('plg_Search', 'repairSearchKeywords', 'store_ConsignmentProtocols', $callOn);
    }


    /**
     * Миграция за регенериране на ключовите думи на инвентаризацията
     */
    public static function repairSearchKeywordsNotes2505()
    {
        $callOn = dt::addSecs(120);
        core_CallOnTime::setCall('plg_Search', 'repairSearchKeywords', 'store_InventoryNotes', $callOn);
    }
}
