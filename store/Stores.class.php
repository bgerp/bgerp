<?php



/**
 * Мениджър на складове
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_Stores extends core_Master
{
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'store_AccRegIntf, acc_RegisterIntf, store_iface_TransferFolderCoverIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Складове';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Склад";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, acc_plg_Registry, bgerp_plg_FLB, store_Wrapper, plg_Current, plg_Rejected, doc_FolderPlg, plg_State, plg_Modified';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,admin';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,admin';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,storeWorker';

	
	/**
	 * Кой може да пише
	 */
	public $canReject = 'ceo, admin';
	
	
	/**
	 * Кой може да пише
	 */
	public $canRestore = 'ceo, admin';
	
	
	/**
     * Детайла, на модела
     */
    public $details = 'AccReports=acc_ReportDetails,store_Products';
    
    
    /**
     * Клас за елемента на обграждащия <div>
     */
    public $cssClass = 'folder-cover';
    
    
    /**
     * Да се показват ли в репортите нулевите редове
     */
    public $balanceRefShowZeroRows = TRUE;
    
    
    /**
     * По кои сметки ще се правят справки
     */
    public $balanceRefAccounts = '302, 304, 305, 306, 309';
    
    
    /**
     * По кой итнерфейс ще се групират сметките 
     */
    public $balanceRefGroupBy = 'store_AccRegIntf';
    
    
    /**
     * Кой  може да вижда счетоводните справки?
     */
    public $canReports = 'ceo,store,acc';
    
    
    /**
     * Кой  може да вижда счетоводните справки?
     */
    public $canAddacclimits = 'ceo,storeMaster,accMaster,accLimits';
    
    
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,storeWorker';
    
    
    /**
     * Да се създаде папка при създаване на нов запис
     */
    public $autoCreateFolder = 'instant';
    
    
    /**
     * Кой може да пише
     */
    public $canWrite = 'ceo, admin';
	
	
   /**
	* Кой може да активира?
	*/
	public $canActivate = 'ceo, store';
    
    
    /**
     * Поле за избор на потребителите, които могат да активират обекта
     *
     * @see bgerp_plg_FLB
     */
    public $canActivateUserFld = 'chiefs';
	
	
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name, chiefs,activateRoles,selectUsers,selectRoles';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * В коя номенкалтура, автоматично да влизат записите
     */
    public $autoList = 'stores';
    
    
    /**
     * Икона за единичен изглед
     */
    public $singleIcon = 'img/16/home-icon.png';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'store/tpl/SingleLayoutStore.shtml';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'varchar(128)', 'caption=Наименование,mandatory,remember=info');
        $this->FLD('comment', 'varchar(256)', 'caption=Коментар');
        $this->FLD('chiefs', 'userList(roles=store|ceo)', 'caption=Контиране на документи->Потребители,mandatory');
        $this->FLD('workersIds', 'userList(roles=storeWorker)', 'caption=Допълнително->Товарачи');
        $this->FLD('locationId', 'key(mvc=crm_Locations,select=title,allowEmpty)', 'caption=Допълнително->Локация');
    	$this->FLD('lastUsedOn', 'datetime', 'caption=Последено използване,input=none');
    	$this->FLD('state', 'enum(active=Активирано,rejected=Оттеглено)', 'caption=Състояние,notNull,default=active,input=none');
    	$this->FLD('autoShare', 'enum(yes=Да,no=Не)', 'caption=Споделяне на сделките с другите отговорници->Избор,notNull,default=yes,maxRadio=2');

        if(core_Packs::isInstalled('pallet')) {
            $this->FLD('strategy', 'class(interface=pallet_ArrangeStrategyIntf,allowEmpty)', 'caption=Управление на стелажите->Стратегия');
        }

    	$this->setDbUnique('name');
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = $data->rec;
    	
    	if($rec->state != 'rejected'){
    		if(store_InventoryNotes::haveRightFor('add', (object)array('folderId' => $rec->folderId))){
    			$data->toolbar->addBtn('Инвентаризация', array('store_InventoryNotes', 'add', 'folderId' => $rec->folderId, 'ret_url' => TRUE), 'ef_icon=img/16/invertory.png,title = Създаване на протокол за инвентаризация');
    		}
    	}
    }
    
    
    /**
     * Имплементация на @see intf_Register::getAccItemRec()
     */
    public static function getAccItemRec($rec)
    {
        return (object)array('title' => $rec->name);
    }
    
    
    /*******************************************************************************************
     * 
     * ИМПЛЕМЕНТАЦИЯ на интерфейса @see crm_ContragentAccRegIntf
     * 
     ******************************************************************************************/
    
    
    /**
     * @see crm_ContragentAccRegIntf::getItemRec
     * @param int $objectId
     */
    public static function getItemRec($objectId)
    {
        $self = cls::get(__CLASS__);
        $result = NULL;
        
        if ($rec = $self->fetch($objectId)) {
            $result = (object)array(
                'num' => $rec->id . " st",
                'title' => $rec->name,
                'features' => 'foobar' // @todo!
            );
        }
        
        return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     * @param int $objectId
     */
    static function itemInUse($objectId)
    {
        // @todo!
    }


    /**
     * След показване на едит формата
     */
	protected static function on_AfterPrepareEditForm($mvc, &$res, $data)
	{
		$company = crm_Companies::fetchOwnCompany();
		$locations = crm_Locations::getContragentOptions(crm_Companies::getClassId(), $company->companyId);
		$data->form->setOptions('locationId', $locations);
		
		// Ако сме в тесен режим
		if (Mode::is('screenMode', 'narrow')) {
	
			// Да има само 2 колони
			$data->form->setField('workersIds', array('maxColumns' => 2));
		}
	}
	
	
	/**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'select' && $rec){
    		
    		// Ако не може да избира склада, проверяваме дали е складов работник, и имали още тази роля
    		if($res == 'no_one'){
    			$cu = core_Users::getCurrent();
    			if(keylist::isIn($cu, $rec->workersIds) && haveRole($mvc->getFieldType('workersIds')->getRoles())){
    				$res = 'ceo,storeWorker';
    			}
    		}
    	}
    }
    
    
    /**
     * Изпълнява се преди преобразуването към вербални стойности на полетата на записа
     */
    protected static function on_BeforeRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(is_object($rec)){
    		if(isset($fields['-list'])){
    			$rec->name =  $mvc->singleTitle . " \"{$rec->name}\"";
    		}
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if($fields['-single']){
    		if($rec->locationId){
    			$row->locationId = crm_Locations::getHyperLink($rec->locationId, TRUE);
    		}
    	}
    }
    
    
    /**
     * Кои документи да се показват като бързи бутони в папката на корицата
     *
     * @param int $id - ид на корицата
     * @return array $res - възможните класове
     */
    public function getDocButtonsInFolder($id)
    {
    	$res = array();
    	$res[] = planning_ConsumptionNotes::getClassId();
    	$res[] = store_Transfers::getClassId();
    	$res[] = store_InventoryNotes::getClassId();
    	
    	return $res;
    }
}
