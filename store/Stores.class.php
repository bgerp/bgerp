<?php


/**
 * Складове
 *
 * Мениджър на складове
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_Stores extends core_Master
{
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'store_AccRegIntf, acc_RegisterIntf, store_iface_TransferFolderCoverIntf';
    
    
    /**
     * Заглавие
     */
    var $title = 'Складове';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Склад";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created, acc_plg_Registry, store_Wrapper, plg_Current, plg_Rejected, doc_FolderPlg, plg_State';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,storeWorker';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,storeMaster';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,storeMaster';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,storeWorker';

	
	/**
	 * Кой може да пише
	 */
	var $canReject = 'ceo, storeMaster';
	
	
	/**
	 * Кой може да пише
	 */
	var $canRestore = 'ceo, storeMaster';
	
	
	/**
     * Детайла, на модела
     */
    var $details = 'AccReports=acc_ReportDetails';
    
    
    /**
     * Клас за елемента на обграждащия <div>
     */
    var $cssClass = 'folder-cover';
    
    
    /**
     * Да се показват ли в репортите нулевите редове
     */
    public $balanceRefShowZeroRows = TRUE;
    
    
    /**
     * По кои сметки ще се правят справки
     */
    public $balanceRefAccounts = '302, 304, 305, 306, 309, 321';
    
    
    /**
     * По кой итнерфейс ще се групират сметките 
     */
    public $balanceRefGroupBy = 'store_AccRegIntf';
    
    
    /**
     * Кой  може да вижда счетоводните справки?
     */
    public $canReports = 'ceo,store,acc';
    
    
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,storeWorker';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,storeMaster';
    
    
    /**
     * Кой може да пише
     */
    var $canWrite = 'ceo,store';
    
    
    /**
	 * Кой може да селектира всички записи
	 */
	var $canSelectAll = 'ceo,storeMaster';
	
	
   /**
	* Кой може да селектира?
	*/
	var $canSelect = 'ceo,storeWorker';
    
    
    /**
	 * Кое поле отговаря на кой работи с даден склад
	 */
	var $inChargeField = 'chiefs';
	
	
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, name, chiefs';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'name';
    
    
    /**
     * В коя номенкалтура, автоматично да влизат записите
     */
    var $autoList = 'stores';
    
    
    /**
     * Икона за единичен изглед
     */
    var $singleIcon = 'img/16/home-icon.png';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    var $singleLayoutFile = 'store/tpl/SingleLayoutStore.shtml';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'varchar(128)', 'caption=Име,mandatory,remember=info');
        $this->FLD('comment', 'varchar(256)', 'caption=Коментар');
        $this->FLD('chiefs', 'userList(roles=store|ceo)', 'caption=Отговорници,mandatory');
        $this->FLD('workersIds', 'userList(roles=storeWorker)', 'caption=Товарачи');
        $this->FLD('locationId', 'key(mvc=crm_Locations,select=title,allowEmpty)', 'caption=Локация');
        $this->FLD('strategy', 'class(interface=store_iface_ArrangeStrategyIntf)', 'caption=Стратегия');
    	$this->FLD('lastUsedOn', 'datetime', 'caption=Последено използване,input=none');
    	$this->FLD('state', 'enum(active=Активирано,rejected=Оттеглено)', 'caption=Състояние,notNull,default=active,input=none');
    	$this->FLD('autoShare', 'enum(yes=Да,no=Не)', 'caption=Споделяне на сделките с другите отговорници->Избор,notNull,default=yes,maxRadio=2');
    }
    
    
    /**
     * Имплементация на @see intf_Register::getAccItemRec()
     */
    public static function getAccItemRec($rec)
    {
        return (object)array(
            'title' => $rec->name
        );
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
     * КРАЙ НА интерфейса @see acc_RegisterIntf
     */
	
    
	/**
	 * Преди подготовка на резултатите
	 */
	protected static function on_AfterPrepareListFilter($mvc, &$data)
	{
		if(!haveRole($mvc->canSelectAll)){
			
			// Показват се само записите за които отговаря потребителя
			$cu = core_Users::getCurrent();
			$data->query->like('chiefs', "|$cu|");
			$data->query->orLike('workersIds', "|$cu|");
		}
	}


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
     * След подготовка на записите в счетоводните справки
     */
    public static function on_AfterPrepareAccReportRecs($mvc, &$data)
    {
    	$recs = &$data->recs;
    	if(empty($recs) || !count($recs)) return;
    	
    	foreach ($recs as &$dRec){
    		$productPlace = acc_Lists::getPosition($dRec->accountNum, 'cat_ProductAccRegIntf');
    		$itemRec = acc_Items::fetch($dRec->{"ent{$productPlace}Id"});
    		if(empty($data->cache[$itemRec->classId])){
    			$data->cache[$itemRec->classId] = cls::get($itemRec->classId);
    		}
    		
    		$ProductMan = $data->cache[$itemRec->classId];
    		
    		$packInfo = $ProductMan->getBasePackInfo($itemRec->objectId);
    		$data->uomNames[$dRec->id] = $packInfo->name;
    		
    		$dRec->blQuantity /= $packInfo->quantity;
    	}
    }
    
    
	/**
     * След подготовка на вербалнтие записи на счетоводните справки
     */
    public static function on_AfterPrepareAccReportRows($mvc, &$data)
    {
    	$rows = &$data->balanceRows;
    	$data->listFields = arr::make("tools=Пулт,ent1Id=Перо1,ent2Id=Перо2,ent3Id=Перо3,packId=Мярка,blQuantity=К-во,blAmount=Сума");
    	
    	foreach ($rows as &$arrs){
    		if(count($arrs['rows'])){
    			foreach ($arrs['rows'] as &$row){
    				$row['packId'] = $data->uomNames[$row['id']];
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
    			$row->locationId = crm_Locations::getHyperLink($rec->locationId);
    		}
    	}
    }
}
