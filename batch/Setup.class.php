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
 * class batch_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани със следенето на партидности
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
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
    public $info = "Партиди и серийни номера към складовите документи";
            
        
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
    		'migrate::migrateBatches',
    		'migrate::migrateProdBatches',
    		'migrate::migrateDefs',
    		'migrate::migrateProdDetBatches',
    		'migrate::updateFeatures2',
        );
    

    /**
     * Роли за достъп до модула
     */
    public $roles = 'batch';
    

    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = "batch_definitions_Varchar,batch_definitions_Serial,batch_definitions_ExpirationDate,batch_definitions_Document,batch_definitions_DeliveryDate,batch_definitions_ProductionDate,batch_definitions_Component";
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
            array(3.4, 'Логистика', 'Партиди', 'batch_Items', 'default', "batch,ceo"),
        );
    
        
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
    		'BATCH_EXPIRYDATE_PERCENT' => array("percent", 'caption=Оцветяване на изтичащите партиди->Преди края'),
    		'BATCH_CLOSE_OLD_BATCHES'  => array('time', 'caption=Затваряне на изчерпани партиди->След'),
    		'BATCH_COUNT_IN_EDIT_WINDOW' => array('int', 'caption=Колко партиди да се показват в прозореца за промяна->Брой'),
    );
    
    
    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
    		array(
    				'systemId' => "Close Old Batches",
    				'description' => "Затваряне на старите партиди по които не е имало движение",
    				'controller' => "batch_Items",
    				'action' => "closeOldBatches",
    				'period' => 1440,
    				'offset' => 20,
    				'timeLimit' => 100
    		),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
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
        
        // Обновяване на протокола за инвентаризация да мус е сетъпне модела
        $Notes = cls::get('store_InventoryNotes');
        $html .= $Notes->setupMvc();
        
        return $html;
    }
    
    
    /**
     * Миграция на партидите
     */
    function migrateBatches()
    {
    	core_Plugins::delete("#plugin = 'batch_plg_DirectProductionNoteMovement'");
    	
    	$Batches = cls::get('batch_BatchesInDocuments');
    	$Batches->setupMvc();
    
    	$documents = array('sales_SalesDetails', 
    			           'purchase_PurchasesDetails', 
    			           'store_ShipmentOrderDetails', 
    			           'store_ReceiptDetails', 
    			           'planning_ConsumptionNoteDetails', 
    			           'store_ConsignmentProtocolDetailsReceived', 
    			           'store_ConsignmentProtocolDetailsSend', 
    			           'store_TransfersDetails');
    	
    	$arr = array();
    	foreach ($documents as $doc){
    		$D = cls::get($doc);
    		
    		// Ако няма такова поле не се прави нищо
    		if(!$D->db->isFieldExists($D->dbTableName, 'batch')) continue;
    		
    		$query = $D->getQuery();
    		$query->FLD('batch', 'text', 'input=hidden,caption=Партиден №,after=productId,forceField');
    		$query->EXT('containerId', cls::getClassName($D->Master), "externalName=containerId,externalKey={$D->masterKey}");
    		$query->EXT('valior', cls::getClassName($D->Master), "externalName={$D->Master->valiorFld},externalKey={$D->masterKey}");
    		$query->EXT('storeId', cls::getClassName($D->Master), "externalName={$D->Master->storeFieldName},externalKey={$D->masterKey}");
    		$query->where("#batch IS NOT NULL");
    		
    		
    		while($dRec = $query->fetch()){
    			if(in_array($doc, array('store_ConsignmentProtocolDetailsReceived', 'store_ConsignmentProtocolDetailsSend'))){
    				$quantity = $dRec->packQuantity / $dRec->quantityInPack;
    			} else {
    				$quantity = $dRec->quantity;
    			}
    			
    			$obj = (object)array('detailClassId'  => $D->getClassId(), 
    					             'containerId'    => $dRec->containerId,
    								 'detailRecId'    => $dRec->id,
    								 'productId'      => $dRec->productId,
    								 'packagingId'    => $dRec->packagingId,
    					             'quantityInPack' => $dRec->quantityInPack,
    					             'quantity'       => $quantity,
    					             'batch'          => $dRec->batch,
    								 'date'           => $dRec->valior,
    								 'storeId'        => $dRec->storeId,
    								 'operation'      => ($D->getBatchMovementDocument($dRec) == 'out') ? 'out' : 'in',
    			
    			);
    			
    			$arr[] = $obj;
    		}
    	}
    	
    	$Batches->saveArray($arr);
    }
    
    
    /**
     * Миграция на дефинициите
     */
    public function migrateDefs()
    {
    	$Defs = cls::get('batch_Defs');
    	$Defs->setupMvc();
    	$Templates = cls::get('batch_Templates');
    	$Templates->setupMvc();
    	$Templates->loadSetupData();
    	
    	if(!$Defs->db->isFieldExists($Defs->dbTableName, str::phpToMysqlName('driverClass'))) return;
    	
    	$templates = array();
    	$tQuery = $Templates->getQuery();
    	while($tRec = $tQuery->fetch()){
    		$t = array('driverClass' => $tRec->driverClass) + (array)$tRec->driverRec;
    		$templates[$tRec->id] = $t;
    	}
    	
    	$os = array();
    	$query = $Defs->getQuery();
    	$query->FLD('driverClass', "class(interface=batch_BatchTypeIntf, allowEmpty, select=title)");
    	$query->FLD('driverRec', "blob(1000000, serialize, compress)");
    	$query->where("#driverClass IS NOT NULL");
    	$query->where("#templateId IS NULL");
    	
    	while($rec = $query->fetch()){
    		$o = array('driverClass' => $rec->driverClass) + (array)$rec->driverRec;
    		if($rec->driverClass == batch_definitions_Varchar::getClassId()){
    			$o['length'] = NULL;
    		}
    		
    		$rec->templateId = batch_Templates::force($o);
    		$Defs->save($rec, 'id,templateId');
    	}
    }
    
    
    /**
     * Миграция на протоколите за производство
     */
    function migrateProdBatches()
    {
    	$Batches = cls::get('batch_BatchesInDocuments');
    	$Batches->setupMvc();
    	
    	$arr = array();
    	
    	$D = cls::get('planning_DirectProductionNote');
    	if(!$D->db->isFieldExists($D->dbTableName, 'batch')) return;
    	
    	$query = planning_DirectProductionNote::getQuery();
    	$query->FLD('batch', 'text', 'input=hidden,caption=Партиден №,after=productId,forceField');
    	$query->where("#batch IS NOT NULL");
    	
    	while($dRec = $query->fetch()){
    		$obj = (object)array('detailClassId'  => planning_DirectProductionNote::getClassId(),
    				             'containerId'    => $dRec->containerId,
    				'detailRecId'    => $dRec->id,
    				'productId'      => $dRec->productId,
    				'packagingId'    => cat_Products::fetchField($dRec->productId, 'measureId'),
    				'quantityInPack' => 1,
    				'quantity'       => $dRec->quantity,
    				'batch'          => $dRec->batch,
    				'date'           => $dRec->valior,
    				'storeId'        => $dRec->storeId,
    				'operation'      => 'in',
    				 
    		);
    		 
    		$arr[] = $obj;
    	}
    	
    	$Batches->saveArray($arr);
    }
    
    
    /**
     * Миграция на протоколите за производство
     */
    function migrateProdDetBatches()
    {
    	$Batches = cls::get('batch_BatchesInDocuments');
    	$Batches->setupMvc();
    	
    	$D = cls::get('planning_DirectProductNoteDetails');
    	if(!$D->db->isFieldExists($D->dbTableName, 'batch')) return;
    	
    	$arr = array();
    	$query = planning_DirectProductNoteDetails::getQuery();
    	$query->FLD('batch', 'text', 'input=hidden,caption=Партиден №,after=productId,forceField');
    	$query->EXT('containerId', 'planning_DirectProductionNote', "externalName=containerId,externalKey=noteId");
    	$query->EXT('valior', 'planning_DirectProductionNote', "externalName=valior,externalKey=noteId");
    	$query->where("#batch IS NOT NULL");
    	
    	while($dRec = $query->fetch()){
    		$obj = (object)array('detailClassId'  => planning_DirectProductNoteDetails::getClassId(),
    				'containerId'    => $dRec->containerId,
    				'detailRecId'    => $dRec->id,
    				'productId'      => $dRec->productId,
    				'packagingId'    => $dRec->packagingId,
    				'quantityInPack' => $dRec->quantityInPack,
    				'quantity'       => $dRec->quantity,
    				'batch'          => $dRec->batch,
    				'date'           => $dRec->valior,
    				'storeId'        => $dRec->storeId,
    				'operation'      => 'out',
    					
    		);
    		
    		$arr[] = $obj;
    	}
    	 
    	$Batches->saveArray($arr);
    }
    
    
    /**
     * Ъпдейт на свойствата на партидите
     */
    public static function updateFeatures2()
    {
    	$Features = cls::get('batch_Features');
    	$Features->setupMvc();
    	$Features->truncate();
    	
    	$iQuery = batch_Items::getQuery();
    	
    	while($iRec = $iQuery->fetch()){
			try{
    			batch_Features::sync($iRec);
    		} catch(core_exception_Expect $e){
    			reportException($e);
    		}
    	}
    }
}
