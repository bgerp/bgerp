<?php


/**
 * Кои сч. сметки ще се използват за синхронизиране със склада
 */
defIfNot('STORE_ACC_ACCOUNTS', '');


/**
 * class store_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани със складовете
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
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
    var $version = '0.1';
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'acc=0.1';
    
    
    /**
     * Стартов контролер за връзката в системното меню
     */
    var $startCtr = 'store_Stores';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Управление на складове и складови документи";
        
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var  $managers = array(
            'store_Stores',
            'store_Products',
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
    		'store_ReserveStocks',
    		'store_ReserveStockDetails',
    		'migrate::updateConfig',
    		'migrate::updateTransfers',
    		'migrate::inventoryNotes'
        );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = array(
    		array('storeWorker'),
            array('inventory'),
    		array('store', 'storeWorker'),
    		array('storeMaster', 'store'),
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.2, 'Логистика', 'Склад', 'store_Stores', 'default', "storeWorker,ceo"),
        );
    
    
    /**
	 * Описание на конфигурационните константи 
	 */
	var $configDescription = array(
			'STORE_ACC_ACCOUNTS' => array("acc_type_Accounts(regInterfaces=store_AccRegIntf|cat_ProductAccRegIntf)", 'caption=Складова синхронизация със счетоводството->Сметки'),
	);
	
	
	/**
	 * Дефинирани класове, които имат интерфейси
	 */
	var $defClasses = 'store_reports_Documents,store_reports_ChangeQuantity';
	
	
	/**
     * Настройки за Cron
     */
    var $cronSettings = array(
        array(
            'systemId' => "Update Reserved Stocks",
            'description' => "Обновяване на резервираните наличности",
            'controller' => "store_Products",
            'action' => "CalcReservedQuantity",
            'period' => 5,
        	'offset' => 1,
            'timeLimit' => 100
        ),
    );
    
	
    /**
     * Инсталиране на пакета
     */
    function install()
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
    function loadSetupData($itr = '')
    {
        $res = parent::loadSetupData($itr);
    	// Ако няма посочени от потребителя сметки за синхронизация
    	$config = core_Packs::getConfig('store');
    	if(strlen($config->STORE_ACC_ACCOUNTS) === 0){
    		$accArray = array();
    		foreach (static::$accAccount as $accSysId){
    			$accId = acc_Accounts::getRecBySystemId($accSysId)->id;
    			$accArray[$accId] = $accSysId;
    		}
    		
    		// Записват се ид-та на дефолт сметките за синхронизация
    		core_Packs::setConfig('store', array('STORE_ACC_ACCOUNTS' => keylist::fromArray($accArray)));
    		$res .= "<li style='color:green'>Дефолт счетодовни сметки за синхронизация на продуктите<b>" . implode(',', $accArray) . "</b></li>";
    	}
        
        return $res;
    }

    
    /**
     * Миграция ъпдейтваща кешираната информация
     */
    function updateConfig()
    {
    	if(core_Packs::fetch("#name = 'acc'")){
    		$config = core_Packs::getConfig('store');
    		
    		if(strlen($config->STORE_ACC_ACCOUNTS) !== 0){
    			$accArray = array();
    			foreach (static::$accAccount as $accSysId){
    				$accId = acc_Accounts::getRecBySystemId($accSysId)->id;
    				$accArray[$accId] = $accSysId;
    			}
    			
    			core_Packs::setConfig('store', array('STORE_ACC_ACCOUNTS' => keylist::fromArray($accArray)));
    		}
    	}
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
    
    
    /**
     * Изтриване на кеш
     */
    public function truncateCacheProducts1()
    {
    	try{
    		if(cls::load('store_Products', TRUE)){
    			$Products = cls::get('store_Products');
    			
    			if($Products->db->tableExists($Products->dbTableName)) {
    				store_Products::truncate();
    			}
    		}
    	} catch(core_exception_Expect $e){
    		reportException($e);
    	}
    }
    
    
    /**
     * Ъпдейт на междускладовите трансфери
     */
    public function updateTransfers()
    {
    	$Transfers = cls::get('store_TransfersDetails');
    	$Transfers->setupMvc();
    	
    	$query = $Transfers->getQuery();
    	$query->where("#newProductId IS NULL || #newProductId = 0");
    	while($rec = $query->fetch()){
    		try{
    			$productId = store_Products::fetchField($rec->productId, 'productId');
    			$rec->newProductId = $productId;
    			$Transfers->save_($rec, 'newProductId');
    		} catch(core_exception_Expect $e){
    			reportException($e);
    		}
    	}
    }
    
    
    /**
     * Миграция на инвентаризацията
     */
    public function inventoryNotes()
    {
    	$Note = cls::get('store_InventoryNotes');
    	$Note->setupMvc();
    	
    	$Sum = cls::get('store_InventoryNoteSummary');
    	$Sum->setupMvc();
    	
    	$Details = cls::get('store_InventoryNoteDetails');
    	$Details->setupMvc();
    	
    	try{
    		$query = $Note->getQuery();
    		while($rec = $query->fetch()){
    			$dQuery = $Details->getQuery();
    			$dQuery->where("#noteId = {$rec->id}");
    		
    			$save = array();
    			while($dRec = $dQuery->fetch()){
    				$clone = new stdClass();
    				$clone->id = $dRec->id;
    				$clone->createdOn  = $rec->createdOn;
    				$clone->createdBy  = $rec->createdBy;
    				$clone->modifiedOn = $rec->createdOn;
    				$clone->modifiedBy = $rec->createdBy;
    				 
    				$save[] = $clone;
    			}
    		
    			if(count($save)){
    				$Details->saveArray($save, 'id,createdOn,createdBy,modifiedOn,modifiedBy');
    			}
    		}
    	} catch(core_exception_Expect $e){
    		reportException($e);
    	}
    }
}