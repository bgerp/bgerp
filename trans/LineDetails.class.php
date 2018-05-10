<?php



/**
 * Детайли на транспортните линии
 *
 *
 * @category  bgerp
 * @package   trans
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class trans_LineDetails extends doc_Detail
{
    
	
	/**
	 * Константа за товар
	 */
	const BASE_TRANS_UNIT = 'Товар';
	
	
    /**
     * Заглавие
     */
    public $title = "Детайли на транспортните линии";
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Логистичен документ';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'lineId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, trans_Wrapper';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    //public $listFields = 'debitAccId, debitQuantity=Дебит->К-во, debitPrice=Дебит->Цена, creditAccId, creditQuantity=Кредит->К-во, creditPrice=Кредит->Цена, amount=Сума, reason=Информация';

    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, trans';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('lineId', 'key(mvc=trans_Lines)', 'column=none,notNull,silent,hidden,mandatory');
    	$this->FLD('containerId', 'key(mvc=doc_Containers)', 'column=none,notNull,silent,hidden,mandatory');
    	$this->FLD('readyInfo', 'blob(serialize, compress)', 'input=none');
    	$this->FLD('classId', 'class', 'input=none');
    	$this->FLD('status', 'enum(waiting=Чакащо,ready=Подготвено)', 'input=none');
    	
    	$this->setDbIndex('containerId');
    }
    
    
    public static function setTransUnitField(&$form, $rec, $transUnits)
    {
    	$form->setDefault('transUnitInput', $rec->transUnits);
    	
    	$units = trans_TransportUnits::getAll();
    	$form->FLD('transUnitInput', "table(columns=unitId|quantity,captions=ЛЕ|Брой,validate=trans_LineDetails::validateTransTable)", "caption=Логистични единици,after=lineNotes");
    	$form->setFieldTypeParams('transUnitInput', array('unitId_opt' => array('' => '') + $units));
    	
    	if(count($transUnits)){
    		$unitOptions = array();
    		foreach ($transUnits as $tId => $q1){
    			$unitOptions['unitId'][] = $tId;
    			$unitOptions['quantity'][] = $q1;
    		}
    		$form->setDefault('transUnitInput', $unitOptions);
    	}
    }
    
    
    public static function validateTransTable($tableData, $Type)
    {
    	$res = array();
    	$units = $tableData['unitId'];
    	$quantities = $tableData['quantity'];
    	$error = $errorFields = array();
    
    	if(count($units) != count(array_unique($units))){
    		$error[] = "Логистичните единици трябва да са уникални|*";
    	}
    	
    	foreach ($units as $k => $unitId){
    		if(empty($quantities[$k])){
    			$error[] = "Попълнена ЛЕ без да има количество|*";
    			$errorFields['quantity'][$k] = "Попълнена ЛЕ без да има количество";
    			$errorFields['unitId'][$k] = "Попълнена ЛЕ без да има количество";
    		}
    	}
    	
    	foreach ($quantities as $k1 => $q1){
    		if(empty($units[$k1])){
    			$error[] = "Попълнено количество без да има ЛЕ|*";
    			$errorFields['quantity'][$k1] = "Попълнено количество без да има ЛЕ";
    			$errorFields['unitId'][$k1] = "Попълнено количество без да има ЛЕ";
    		}
    		
    		if(empty($errorFields['quantity'][$k1])){
    			if(!type_Int::isInt($q1) || $q1 <= 0){
    				$error[] = "Не е въведено цяло положително число|*";
    				$errorFields['quantity'][$k1] = "Не е въведено цяло положително число";
    				$errorFields['unitId'][$k1] = "Не е въведено цяло положително число";
    			}
    		}
    	}
    	
    	if(count($error)){
    		$error = implode("<li>", $error);
    		$res['error'] = $error;
    	}
    	
    	if(count($errorFields)){
    		$res['errorFields'] = $errorFields;
    	}
    	
    	return $res;
    }
}