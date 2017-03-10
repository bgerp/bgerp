<?php



/**
 * Клас 'cond_DeliveryTerms' - Условия на доставка
 *
 * Набор от стандартните условия на доставка (FOB, DAP, ...)
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cond_DeliveryTerms extends core_Master
{
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, cond_Wrapper, plg_State2';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'codeName, term, costCalc=Транспорт->Калкулатор, calcCost=Транспорт->Скрито, lastUsedOn=Последно, state, createdBy,createdOn';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'costCalc, calcCost, lastUsedOn';
    
    
    /**
     * Поле в което ще се показва тулбара
     */
    public $rowToolsSingleField = 'codeName';
    
    
    /**
     * Кой може да пише
     */
    public $canWrite = 'ceo,admin';
    
    
    /**
     * Кой може да добавя
     */
    public $canAdd = 'ceo,admin';
    
    
    /**
     * Кой може да променя
     */
    public $canEdit = 'ceo,admin';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,admin';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,admin';
    

    /**
     * Кой може да променя състоянието на Условията на доставка
     */
    public $canChangestate = 'ceo,admin';


    /**
     * Заглавие
     */
    public $title = 'Условия на доставка';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Условие на доставка";
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    public $singleIcon = 'img/16/delivery.png';
    
    
    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'cond/tpl/SingleDeliveryTerms.shtml';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'ceo,admin';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('codeName', 'varchar', 'caption=Код');
        $this->FLD('term', 'text', 'caption=Обяснение');
        $this->FLD('forSeller', 'text', 'caption=За продавача');
        $this->FLD('forBuyer', 'text', 'caption=За купувача');
        $this->FLD('transport', 'text', 'caption=Транспорт');
        $this->FLD('costCalc', 'class(interface=tcost_CostCalcIntf,allowEmpty,select=title)', 'caption=Изчисляване на транспортна себестойност->Калкулатор');
        $this->FLD('calcCost', 'enum(yes=Включено,no=Изключено)', 'caption=Изчисляване на транспортна себестойност->Скрито,notNull,value=no');
        $this->FLD('address', 'enum(none=Без,receiver=Локацията на получателя,supplier=Локацията на доставчика)', 'caption=Показване на мястото на доставка->Избор,notNull,value=none,default=none');
        $this->FLD('lastUsedOn', 'datetime(format=smartTime)', 'caption=Последна употреба,input=none,column=none');
        
        $this->setDbUnique('codeName');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	
    	if($form->rec->createdBy == core_Users::SYSTEM_USER){
    		$form->setReadOnly('codeName');
    		foreach (array('term', 'forSeller', 'forBuyer', 'transport', 'address') as $fld){
    			$form->setField($fld, 'input=none');
    		}
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = &$form->rec;
    	
    	if($form->isSubmitted()){
    		if(strpos($rec->codeName, ':') !== FALSE){
    			$form->setError('codeName', 'Кода не може да съдържа|* "<b>:</b>"');
    		}
    	}
    }
    
    
    /**
     * Връща имплементация на драйвера за изчисляване на транспортната себестойност
     * 
     * @param mixed $id - ид, запис или NULL
     * @return tcost_CostCalcIntf|NULL
     */
    public static function getCostDriver($id)
    {
    	if(!empty($id)){
    		$rec = self::fetchRec($id);
    		if(cls::load($rec->costCalc, TRUE)){
    			return cls::getInterface('tcost_CostCalcIntf', $rec->costCalc);
    		}
    	}
    	
    	return NULL;
    }
    
    
    /**
     * Дали да се изчислява скрития транспорт, за дадения артикул
     * 
     * @param mixed $id      - ид или запис
     * @param int $productId - ид на артикул
     * @return boolean $res  - да се начислява ли скрит транспорт или не
     */
    public static function canCalcHiddenCost($id, $productId)
    {
    	if(!$id) return FALSE;
    	
    	expect($rec = self::fetchRec($id));
    	if($rec->calcCost == 'yes'){
    		
    		// Може да се начислява скрит транспорт само за складируем артикул, ако в условието на доставка е разрешено
    		if(empty($productId)) return FALSE;
    		
    		if(cat_Products::fetchField($productId, 'canStore') == 'yes'){
    			return TRUE;
    		}
    	}
    	
    	return FALSE;
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public static function on_AfterSetupMvc($mvc, &$res)
    {
    	$file = "cond/csv/DeliveryTerms.csv";
    	$fields = array( 
	    	0 => "term", 
	    	1 => "codeName", 
	    	2 => "forSeller", 
	    	3 => "forBuyer", 
	    	4 => "transport",
    		5 => "address",
    	);
    	
    	$cntObj = csv_Lib::importOnce($mvc, $file, $fields);
    	$res .= $cntObj->html;
    	
    	return $res;
    }
    
    
    /**
     * Проверява даден стринг дали съдържа валиден код CASE SENSITIVE
     * 
     * @param string $code - код
     * @return int|NULL - ид на кода или NULL - ако не е открит
     */
    public static function getTermCodeId($code)
    {
    	// Разделяме въведения стринг на интервали
    	$params = explode(':', $code);
    	
    	// Кода трябва да е в първите символи
    	$foundCode = trim($params[0]);
    	
    	// Ако няма запис с намерения код, връщаме FALSE
    	$rec = static::fetch(array("#codeName = '[#1#]'", $foundCode));
    	
    	// Ако е намерено нещо връщаме го
    	if(isset($rec)) return $rec->id;
    	
    	// Ако стигнем до тук, значи кода е валиден
    	return NULL;
    }
    
    
    /**
     * Помощен метод допълващ условието на доставка с адреса
     * 
     * @param string $deliveryCode   - текста на търговското условие
     * @param int $contragentClassId - класа на контрагента
     * @param int $contragentId      - ид на котнрагента
     * @param int $storeId           - ид на склада
     * @param int $locationId        - ид на локация
     * @param core_Mvc $document     - за кой документ се отнася
     * @return string                - условието за доставка допълнено с адреса, ако може да се определи
     */
    public static function addDeliveryTermLocation($deliveryCode, $contragentClassId, $contragentId, $storeId, $locationId, $document)
    {
    	$adress = '';
    	$isSale = ($document instanceof sales_Sales);
    	expect($rec = self::fetch(array("#codeName = '[#1#]'", $deliveryCode)));
    	
    	if(($rec->address == 'supplier' && $isSale === TRUE) || ($rec->address == 'receiver' && $isSale === FALSE)){
    		
    		if(isset($storeId)){
    			if($locationId = store_Stores::fetchField($storeId, 'locationId')){
    				$adress = crm_Locations::getAddress($locationId);
    			}
    		}
    		
    		if(empty($adress)){
    			$ownCompany = crm_Companies::fetchOurCompany();
    			$adress = cls::get('crm_Companies')->getFullAdress($ownCompany->id, TRUE)->getContent();
    		}
    	} elseif(($rec->address == 'receiver' && $isSale === TRUE) || ($rec->address == 'supplier' && $isSale === FALSE)){
    		if(isset($locationId)){
    			$adress = crm_Locations::getAddress($locationId);
    		} else {
    			$adress = cls::get($contragentClassId)->getFullAdress($contragentId, TRUE)->getContent();
    		}
    	}
    	
    	$adress = trim(strip_tags($adress));
    	if(!empty($adress)){
    		$deliveryCode .= ": {$adress}";
    	}
    	
    	return $deliveryCode;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'delete' && isset($rec->lastUsedOn)){
    		$res = 'no_one';
    	}
    }
}
