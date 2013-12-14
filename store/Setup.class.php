<?php
// Кои сч. сметки ще се използват за синхронизиране със склада
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
     * Стартов контролер за връзката в системното меню
     */
    var $startCtr = 'store_Movements';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Палетно складово стопанство";
        
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var  $managers = array(
            'store_Stores',
            'store_Movements',
            'store_Pallets',
            'store_PalletTypes',
            'store_Racks',
            'store_RackDetails',
            'store_Products',
            'store_Zones',
            'store_ShipmentOrders',
            'store_ShipmentOrderDetails',
    		'store_Receipts',
    		'store_ReceiptDetails',
    		'store_Transfers',
    		'store_TransfersDetails',
        );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'storeWorker';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.3, 'Логистика', 'Складове', 'store_Movements', 'default', "storeWorker,ceo"),
        );
    
    
    /**
	 * Описание на конфигурационните константи
	 */
	var $configDescription = array(
			'STORE_ACC_ACCOUNTS' => array("acc_type_Accounts", 'caption=Продукти->Сч. сметки за синхронизиране'),
	);
	
	
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $html = parent::install();      
        
        core_Classes::add('store_ArrangeStrategyTop');
        core_Classes::add('store_ArrangeStrategyBottom');
        core_Classes::add('store_ArrangeStrategyMain');
        
    	if($roleRec = core_Roles::fetch("#role = 'masterStore'")){
    		core_Roles::delete("#role = 'masterStore'");
    	}
    	
        // Добавяне на роля за старши складажия
        $html .= core_Roles::addRole('store', 'storeWorker') ? "<li style='color:green'>Добавена е роля <b>store</b> наследяваща <b>storeWorker</b></li>" : '';
    	$html .= core_Roles::addRole('storeMaster', 'store') ? "<li style='color:green'>Добавена е роля <b>storeMaster</b> наследяваща <b>store</b></li>" : '';
		
    	
    	// Ако няма посочени от потребителя сметки а синхронизация
    	$config = core_Packs::getConfig('store');
    	if(strlen($config->STORE_ACC_ACCOUNTS) === 0){
    		$accArray = array();
    		foreach (static::$accAccount as $accSysId){
    			$accId = acc_Accounts::getRecBySystemId($accSysId)->id;
    			$accArray[$accId] = $accSysId;
    		}
    		
    		// Записват се ид-та на дефолт сметките за синхронизация
    		core_Packs::setConfig('store', array('STORE_ACC_ACCOUNTS' => keylist::fromArray($accArray)));
    		$html .= "<li style='color:green'>Дефолт счетодовни сметки за синхронизация на продуктите<b>" . implode(',', $accArray) . "</b></li>";
    	}
    	
    	return $html;
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
}