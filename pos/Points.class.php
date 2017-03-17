<?php



/**
 * Мениджър за "Точки на продажба" 
 *
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class pos_Points extends core_Master {
    
    
    /**
     * Заглавие
     */
    public $title = "Точки на продажба";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, plg_Rejected, doc_FolderPlg,
                     pos_Wrapper, plg_Sorting, plg_Printing, plg_Current,plg_State, plg_Modified';

    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "POS";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name, caseId, storeId';
    
    
   /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Кой може да го прочете?
     */
    public $canRead = 'ceo, pos';
    
    
    /**
     * Кой може да променя?
     */
    public $canWrite = 'ceo, posMaster';
    
    
    /**
     * Кой може да пише
     */
    public $canCreatenewfolder = 'ceo, pos';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, pos';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,pos';
    
    
    /**
     * Икона за единичен изглед
     */
    public $singleIcon = 'img/16/cash-register.png';
    
    
    /**
     * Кой може да го отхвърли?
     */
    public $canReject = 'admin, pos';
    

    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'pos/tpl/SinglePointLayout.shtml';
	
	
    /**
	* Кой може да селектира?
	*/
	public $canSelect = 'ceo, pos';
	
	
    /**
	 * Кой може да селектира всички записи
	 */
	public $canSelectAll = 'ceo, posMaster';
	
	
	/**
	 * Детайли на бележката
	 */
	public $details = 'Receipts=pos_Receipts';
	
	
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('name', 'varchar(255)', 'caption=Наименование, mandatory,oldFieldName=title');
    	$this->FLD('caseId', 'key(mvc=cash_Cases, select=name)', 'caption=Каса, mandatory');
        $this->FLD('storeId', 'key(mvc=store_Stores, select=name)', 'caption=Склад, mandatory');
        $this->FLD('policyId', 'key(mvc=price_Lists, select=title)', 'caption=Политика, silent, mandotory');
        $this->FLD('driver', 'class(interface=sales_FiscPrinterIntf,allowEmpty,select=title)', 'caption=Фискален принтер->Драйвър');
    }
    
	
    /**
     * Създава дефолт контрагент за обекта, ако той вече няма създаден
     */
    protected static function on_AfterSave($mvc, &$id, $rec)
    {
    	if(!static::defaultContragent($id)) {
	    	$defaultContragent = new stdClass();
	    	$defaultContragent->name = "POS:" . $rec->id . "-Анонимен Клиент";
	    	
	    	$countryName = core_Packs::getConfigValue('crm', 'BGERP_OWN_COMPANY_COUNTRY');
	    	$defaultContragent->country = drdata_Countries::fetchField("#commonName = '{$countryName}'", 'id');
	    	
	    	crm_Persons::save($defaultContragent);
    	}
    }
    
    
    /**
     * Подготовка на формата за добавяне
     */
    protected static function on_AfterPrepareEditForm($mvc, $res, $data)
    { 
    	$data->form->setDefault('policyId', cat_Setup::get('DEFAULT_PRICELIST'));
    }
    
    
    /**
     * Намира кой е дефолт контрагента на Точката на продажба
     * @param int $id - ид на точкта
     * @return mixed $id/FALSE - ид на контрагента или FALSE ако няма
     */
    public static function defaultContragent($id = NULL)
    {
    	($id) ? $pos = $id : $pos = pos_Points::getCurrent();
    	$query = crm_Persons::getQuery();
    	$query->where("#name LIKE '%POS:{$pos}%'");
    	if($rec = $query->fetch()) {
    		
    		return $rec->id;
    	}
    	
    	return FALSE;
    }
    
    
	/**
     * След подготовка на тулбара на единичен изглед.
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = $data->rec;
    	
    	if($mvc->haveRightFor('select', $rec->id) && pos_Receipts::haveRightFor('terminal')){
    		$urlArr = array('pos_Points', 'OpenTerminal', $rec->id);
    		$data->toolbar->addBtn("Отвори", $urlArr, NULL, 'title=Отваряне на терминала за POS продажби,class=pos-open-btn,ef_icon=img/16/forward16.png,target=_blank');
    	}
    	
    	$reportUrl = array();
    	if(pos_Reports::haveRightFor('add', (object)array('pointId' => $rec->id)) && pos_Reports::canMakeReport($rec->id)){
    		$reportUrl = array('pos_Reports', 'add', 'pointId' => $rec->id, 'ret_url' => TRUE);
    	}
    	
    	$title = (count($reportUrl)) ? 'Направи отчет' : 'Не може да се генерира отчет. Възможна причина - неприключени бележки.';
    	
    	$data->toolbar->addBtn("Отчет", $reportUrl, NULL, "title={$title},ef_icon=img/16/report.png");
    }
    
    
    /**
     * Екшън форсиращ избирането на точката и отваряне на терминала
     */
    function act_OpenTerminal()
    {
    	expect($pointId = Request::get('id', 'int'));
    	expect($rec = $this->fetch($pointId));
    	$this->requireRightFor('select', $pointId);
    	$this->selectCurrent($pointId);
    	
    	return new Redirect(array('pos_Receipts', 'new'));
    }
    
    
    /**
     * Обработка по вербалното представяне на данните
     */
    protected static function on_AfterRecToVerbal(core_Mvc $mvc, &$row, $rec, $fields = array())
    {
    	unset($row->currentPlg);
    	
    	if(!Mode::is('text', 'xhtml') && !Mode::is('printing') && !Mode::is('pdf')){
    		if($mvc->haveRightFor('select', $rec->id) && pos_Receipts::haveRightFor('terminal')){
    			$urlArr = array('pos_Points', 'OpenTerminal', $rec->id);
    			$row->currentPlg = ht::createBtn('Отвори', $urlArr, NULL, TRUE, 'title=Отваряне на терминала за POS продажби,class=pos-open-btn,ef_icon=img/16/forward16.png');
    		}
    	}
    	
    	$row->caseId = cash_Cases::getHyperlink($rec->caseId, TRUE);
    	$row->storeId = store_Stores::getHyperlink($rec->storeId, TRUE);
    	
    	if($fields['-single']){
    		$row->policyId = price_Lists::getHyperlink($rec->policyId, TRUE);
    	}
    }
	
	
	/**
	 * След връщане на избраната точка
	 */
	protected static function on_AfterGetCurrent($mvc, &$res, $part = 'id', $bForce = TRUE)
	{
		// Ако сме се логнали в точка
		if($res && $part == 'id'){
			$rec = $mvc->fetchRec($res);
			
			// .. и имаме право да изберем склада и, логваме се в него
			if(store_Stores::haveRightFor('select', $rec->storeId)){
				store_Stores::selectCurrent($rec->storeId);
			}
			
			// .. и имаме право да изберем касата и, логваме се в нея
			if(cash_Cases::haveRightFor('select', $rec->caseId)){
				cash_Cases::selectCurrent($rec->caseId);
			}
		}
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'select' && isset($rec)){
			if(!self::canSelectPos($rec, $userId)){
				$requiredRoles = 'no_one';
			}
		}
	}
	
	
	/**
	 * Може ли потребителя да избере точката на продажба.
	 * Може само ако има права да избира касата и склада в точката
	 * 
	 * @param mixed $rec          - ид или запис
	 * @param string|NULL $userId - потребител, NULL за текущия
	 * @return boolean $res       - може ли да избира точката на продажба    
	 */
	public static function canSelectPos($rec, $userId = NULL)
	{
		$userId = (isset($userId)) ? $userId : core_Users::getCurrent();
		
		$rec = static::fetchRec($rec);
		$canActivateStore = bgerp_plg_FLB::canUse('store_Stores', $rec->storeId, $userId);
		$canActivateCase = bgerp_plg_FLB::canUse('cash_Cases', $rec->caseId, $userId);
		$res = ($canActivateStore === TRUE && $canActivateCase === TRUE);
		
		return $res;
	}
}