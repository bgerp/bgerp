<?php


/**
 * При какъв процент при достигането на края на срока на годност партидите да се оцветяват
 */
defIfNot('BATCH_EXPIRYDATE_PERCENT', 0.15);


/**
 * Партиди без движения колко месеца назаде да се затварят
 */
defIfNot('BATCH_CLOSE_OLD_BATCHES', core_DateTime::SECONDS_IN_MONTH);


/**
 * Брой партиди които да се показват в прозореца за промяна на партидите
 */
defIfNot('BATCH_COUNT_IN_EDIT_WINDOW', 10);


/**
 * Дали партидите да се показват във фактурите
 */
defIfNot('BATCH_SHOW_IN_INVOICES', 'yes');


/**
 * class batch_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани със следенето на партидности
 *
 *
 * @category  bgerp
 * @package   batch
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class batch_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'batch_Items';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Партиди и серийни номера към складовите документи';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'batch_Defs',
        'batch_Items',
        'batch_Movements',
        'batch_CategoryDefinitions',
        'batch_Features',
        'batch_Templates',
        'batch_BatchesInDocuments'
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = array(
        array('batch'),
        array('batchMaster', 'batch'),
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'batch_definitions_Varchar,
                          batch_definitions_Serial,
                          batch_definitions_ExpirationDate,
                          batch_definitions_Document,
                          batch_definitions_DeliveryDate,
                          batch_definitions_ProductionDate,
                          batch_definitions_Component,
                          batch_definitions_StringAndDate,
                          batch_definitions_StringAndCodeAndDate,
                          batch_definitions_Digits,
                          batch_definitions_Job,
                          batch_definitions_SaleReff';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.4, 'Логистика', 'Партиди', 'batch_Items', 'default', 'batch,ceo'),
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'BATCH_EXPIRYDATE_PERCENT' => array('percent', 'caption=Оцветяване на изтичащите партиди->Преди края'),
        'BATCH_CLOSE_OLD_BATCHES' => array('time', 'caption=Затваряне на изчерпани партиди->След'),
        'BATCH_COUNT_IN_EDIT_WINDOW' => array('int', 'caption=Колко партиди да се показват в прозореца за промяна->Брой'),
        'BATCH_SHOW_IN_INVOICES' => array('enum(yes=Да,no=Не)', 'caption=Показване на партиди във фактурите->Избор'),
    );
    
    
    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'Close Old Batches',
            'description' => 'Затваряне на старите партиди по които не е имало движение',
            'controller' => 'batch_Items',
            'action' => 'closeOldBatches',
            'period' => 1440,
            'offset' => 20,
            'timeLimit' => 100
        ),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->installPlugin('Партидни движения на експедиционите нареждания', 'batch_plg_DocumentMovement', 'store_ShipmentOrders', 'private');
        $html .= $Plugins->installPlugin('Партидни движения на детайлите на експедиционите нареждания', 'batch_plg_DocumentMovementDetail', 'store_ShipmentOrderDetails', 'private');
        
        $html .= $Plugins->installPlugin('Партидни движения на складовите разписки', 'batch_plg_DocumentMovement', 'store_Receipts', 'private');
        $html .= $Plugins->installPlugin('Партидни движения на детайлите на складовите разписки', 'batch_plg_DocumentMovementDetail', 'store_ReceiptDetails', 'private');
        
        $html .= $Plugins->installPlugin('Партидни движения на сделките', 'batch_plg_DocumentMovement', 'deals_DealMaster', 'family');
        $html .= $Plugins->installPlugin('Партидни движения на детайлите на продажбите', 'batch_plg_DocumentMovementDetail', 'deals_DealDetail', 'family');
        
        $html .= $Plugins->installPlugin('Партидни движения на междускладовите трансфери', 'batch_plg_DocumentMovement', 'store_Transfers', 'private');
        $html .= $Plugins->installPlugin('Партидни движения на детайлите на междускладовите трансфери', 'batch_plg_DocumentMovementDetail', 'store_TransfersDetails', 'private');
        
        $html .= $Plugins->installPlugin('Партидни движения на производствените документи', 'batch_plg_DocumentMovement', 'deals_ManifactureMaster', 'family');
        $html .= $Plugins->installPlugin('Партидни движения на детайлите на производствените документи', 'batch_plg_DocumentMovementDetail', 'deals_ManifactureDetail', 'family');
        
        $html .= $Plugins->installPlugin('Партиден детайл на артикулите', 'batch_plg_ProductDetail', 'cat_Products', 'private');
        $html .= $Plugins->installPlugin('Детайл за дефиниции на партиди', 'batch_plg_CategoryDetail', 'cat_Categories', 'private');
        
        $html .= $Plugins->installPlugin('Партиден детайл на детайла напротоколите за отговорно пазене', 'batch_plg_DocumentMovementDetail', 'store_InternalDocumentDetail', 'family');
        $html .= $Plugins->installPlugin('Партидни движения на протоколите за отговорно пазене', 'batch_plg_DocumentMovement', 'store_ConsignmentProtocols', 'private');
        
        $html .= $Plugins->installPlugin('Партидни движения на протокола за инвентаризация', 'batch_plg_InventoryNotes', 'store_InventoryNoteDetails', 'private');
        $html .= $Plugins->installPlugin('Партидни движения на протокола за производство', 'batch_plg_DocumentMovementDetail', 'planning_DirectProductionNote', 'private');
        $html .= $Plugins->installPlugin('Партидни движения на отчета за ПОС продажби', 'batch_plg_PosReports', 'pos_Reports', 'private');
        $html .= $Plugins->installPlugin('Партидни движения на заданията', 'batch_plg_Jobs', 'planning_Jobs', 'private');
        $html .= $Plugins->installPlugin('Партидни движения към прогреса на производствените операции', 'batch_plg_TaskDetails', 'planning_ProductionTaskDetails', 'private');

        // Обновяване на моделите към, които са закачени партиди
        $classesToSetup = array('planning_ProductionTaskDetails', 'store_InventoryNoteDetails', 'planning_DirectProductionNote', 'sales_SalesDetails', 'planning_DirectProductNoteDetails', 'purchase_PurchasesDetails', 'store_ConsignmentProtocols', 'store_TransfersDetails', 'store_ReceiptDetails', 'store_ShipmentOrderDetails', 'pos_Reports');
        foreach ($classesToSetup as $clsToSetup){
            $SetupCls = cls::get($clsToSetup);
            $html .= $SetupCls->setupMvc();
        }
        
        return $html;
    }
}
