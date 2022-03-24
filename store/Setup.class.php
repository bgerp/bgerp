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
 * Изписване на отрицателни наличности от склада
 */
defIfNot('STORE_ALLOW_NEGATIVE_SHIPMENT', 'yes');


/**
 * Подготовка преди експедиция
 */
defIfNot('STORE_PREPARATION_BEFORE_SHIPMENT', '');


/**
 * Иьчисляване на най-ранната наличност на ЕН-та в рамките на
 */
defIfNot('STORE_EARLIEST_SHIPMENT_READY_IN', 14);



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
        'migrate::reconto3231v1',
        'migrate::updateProductsLastUsedOn'
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = array(
        array('storeWorker'),
        array('inventory'),
        array('store', 'storeWorker'),
        array('storeMaster', 'store'),
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.2, 'Логистика', 'Склад', 'store_Products', 'default', 'storeWorker,ceo'),
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'STORE_ACC_ACCOUNTS' => array('acc_type_Accounts(regInterfaces=store_AccRegIntf|cat_ProductAccRegIntf)', 'caption=Складова синхронизация със счетоводството->Сметки'),
        'STORE_TARIFF_NUMBER_LENGTH' => array('int', 'caption=Групиране на тарифните номера по част от него->Първите,unit=цифри'),
        'STORE_ALLOW_NEGATIVE_SHIPMENT' => array('enum(no=Забранено, yes=Разрешено)', 'caption=Изписване на минус от склад->Избор'),
        'STORE_PREPARATION_BEFORE_SHIPMENT' => array('time(suggestions=1 ден|2 дена|3 дена|1 седмица)', 'caption=Подготовка преди експедиция->Време'),
        'STORE_EARLIEST_SHIPMENT_READY_IN' => array('int(min=0)', 'caption=Изчисляване на най-ранната наличност на артикулите в ЕН-та за следващите->Дни'),
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'store_reports_Documents,store_reports_ChangeQuantity,store_reports_ProductAvailableQuantity,
                          store_iface_ImportShippedProducts,store_reports_DeficitInStores,store_reports_UnfulfilledQuantities,
                          store_reports_ArticlesDepended,store_reports_ProductsInStock,store_reports_UnrealisticPricesAndWeights,
                          store_reports_ProductAvailableQuantity1,store_reports_JobsHorizons';
    
    
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
        
        return $res;
    }
    
    
    /**
     * Изтриване на кеш
     */
    public function truncateCacheProducts1()
    {
        try {
            if (cls::load('store_Products', true)) {
                $Products = cls::get('store_Products');
                
                if ($Products->db->tableExists($Products->dbTableName)) {
                    store_Products::truncate();
                }
            }
        } catch (core_exception_Expect $e) {
            reportException($e);
        }
    }


    /**
     * Реконтира документите засягащи сметка 323
     */
    public function reconto3231v1()
    {
        $Consignemts = cls::get('store_ConsignmentProtocols');
        $Consignemts->setupMvc();

        // Коя е първата дата след последния затворен период
        $lastClosed = acc_Periods::getLastClosed();
        $nextDay = is_object($lastClosed) ? $lastClosed->end : '0000-00-00';

        // Взимат се активните протоколи за отговорно пазене
        $documents = array();
        $query = $Consignemts::getQuery();
        $query->where("#state = 'active' AND #valior > '{$nextDay}'");
        $query->show('id');
        while($rec = $query->fetch()){
            $documents[] = (object)array('docType' => $Consignemts->getClassId(), 'docId' => $rec->id);
        }

        // Взимат се и документите с амбалаж
        $packQuery = store_DocumentPackagingDetail::getQuery();
        while($packRec = $packQuery->fetch()){
            $Document = cls::get($packRec->documentClassId);
            $docRec = $Document->fetch($packRec->documentId, "state,{$Document->valiorFld}");
            if($docRec->state == 'active' && $docRec->{$Document->valiorFld} > $nextDay){
                $documents[] = (object)array('docType' => $Document->getClassId(), 'docId' => $packRec->documentId);
            }
        }

        // Ако няма такива не се прави нищо
        $count = countR($documents);
        if(!$count)  return;

        $accSetup = cls::get('acc_Setup');
        $accSetup->loadSetupData();

        core_App::setTimeLimit($count * 0.6, false, 250);

        // Всеки документ от тях се реконтира
        foreach ($documents as $doc){

            // Изтриваме му транзакцията
            acc_Journal::deleteTransaction($doc->docType, $doc->docId);

            // Записване на новата транзакция на документа
            try{
                $startReconto = true;
                Mode::push('recontoTransaction', true);
                $success = acc_Journal::saveTransaction($doc->docType, $doc->docId, false);
                Mode::pop('recontoTransaction');
                $startReconto = false;
            } catch(core_exception_Expect  $e){
                reportException($e);
                if($startReconto){
                    Mode::pop('recontoTransaction');
                }
            }
        }
    }


    /**
     * Първоначално обновяване на датата на последна промяна
     */
    function updateProductsLastUsedOn()
    {
        $Products = cls::get('store_Products');
        $Products->setupMvc();

        $lastUpdatedColName = str::phpToMysqlName('lastUpdated');
        $query = "UPDATE {$Products->dbTableName} SET {$lastUpdatedColName} = NOW() WHERE {$lastUpdatedColName} IS NULL";
        $Products->db->query($query);
    }
}
