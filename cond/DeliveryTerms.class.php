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
 * @copyright 2006 - 2016 Experta OOD
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
    public $listFields = 'codeName, term, state';
    
    
    /**
     * Поле в което ще се показва тулбара
     */
    public $rowToolsSingleField = 'codeName';
    
    
    /**
     * Кой може да пише
     */
    public $canWrite = 'ceo,cond';
    
    
    /**
     * Кой може да добавя
     */
    public $canAdd = 'ceo,cond';
    
    
    /**
     * Кой може да променя
     */
    public $canEdit = 'ceo,cond';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,cond';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,cond';
    
    
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
        
        $this->setDbUnique('codeName');
    }
    
    
    /**
     * Връща имплементация на драйвера за изчисляване на транспортната себестойност
     * 
     * @param mixed $id - ид, запис или NULL
     * @return tcost_CostCalcIntf|NULL
     */
    public static function getCostDriver($id)
    {
    	if($id){
    		$rec = self::fetchRec($id);
    		if(cls::load($rec->costCalc, TRUE)){
    			return cls::getInterface('tcost_CostCalcIntf', $rec->costCalc);
    		}
    	}
    	
    	return NULL;
    }
    
    
    /**
     * Дали да се изчислява скрития транспорт
     * 
     * @param mixed $id - ид или запис
     * @return boolean $res - да се начислява ли скрит транспорт или не
     */
    public static function canCalcHiddenCost($id)
    {
    	expect($rec = self::fetchRec($id));
    	$res = ($rec->calcCost == 'yes') ? TRUE :FALSE;
    	
    	return $res;
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
	    	4 => "transport");
    	
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
    	$params = explode(' ', $code);
    	
    	// Кода трябва да е в първите символи
    	$foundCode = $params[0];
    	
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
    	
    	if(($deliveryCode == 'EXW' && $isSale === TRUE) || ($deliveryCode == 'DDP' && $isSale === FALSE)){
    		if(isset($storeId)){
    			if($locationId = store_Stores::fetchField($storeId, 'locationId')){
    				$adress = crm_Locations::getAddress($locationId);
    			}
    		} 
    		
    		if(empty($adress)){
    			$ownCompany = crm_Companies::fetchOurCompany();
    			$adress = cls::get('crm_Companies')->getFullAdress($ownCompany->id)->getContent();
    		}
    	} elseif(($deliveryCode == 'DDP' && $isSale === TRUE) || ($deliveryCode == 'EXW' && $isSale === FALSE)){
    		if(isset($locationId)){
    			$adress = crm_Locations::getAddress($locationId);
    		} else {
    			$adress = cls::get($contragentClassId)->getFullAdress($contragentId)->getContent();
    		}
    	}
    	
    	$adress = trim(strip_tags($adress));
    	if(!empty($adress)){
    		if($deliveryCode == 'DDP'){
    			$deliveryCode .= " {$adress}";
    		} else {
    			$deliveryCode .= " ({$adress})";
    		}
    	}
    	
    	return $deliveryCode;
    }
}