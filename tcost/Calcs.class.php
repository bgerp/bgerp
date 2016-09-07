<?php



/**
 * Модел за кеширани изчислени транспортни цени
 *
 *
 * @category  bgerp
 * @package   tcost
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class tcost_Calcs extends core_Manager
{


	/**
     * Заглавие
     */
    public $title = "Изчислен транспорт";


    /**
     * Плъгини за зареждане
     */
    public $loadList = "tcost_Wrapper";


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';


    /**
     * Полета, които се виждат
     */
    public $listFields  = "docId,recId,fee";
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('docClassId', 'class(interface=doc_DocumentIntf)', 'mandatory,caption=Вид на документа');
    	$this->FLD('docId', 'int', 'mandatory,caption=Ид на документа');
    	$this->FLD('recId', 'int', 'mandatory,caption=Ид на реда');
    	$this->FLD('fee', 'double', 'mandatory,caption=Сума на транспорта');
    	
    	$this->setDbUnique('docClasId,docId,recId');
    	$this->setDbIndex('docClasId,docId');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->docId = cls::get($rec->docClassId)->getLink($rec->docId, 0);
    }
    
    
    /**
     * Връща информация за цената на транспорта, към клиент
     * 
     * @param int $productId            - ид на артикул
     * @param double $quantity          - к-во
     * @param int|NULL $deliveryTermId  - условие на доставка, NULL ако няма
     * @param mixed $contragentClassId  - клас на контрагента
     * @param int $contragentId         - ид на контрагента
     * @return FALSE|array $res         - информация за цената на транспорта или NULL, ако няма
     * 					['totalFee']  - обща сума на целия транспорт, в основна валута без ДДС
     * 					['singleFee'] - цената от транспорта за 1-ца от артикула, в основна валута без ДДС
     */
    public static function getTransportCost($deliveryTermId, $productId, $quantity, $totalWeight, $toCountryId, $toPcodeId)
    {
    	// Имали в условието на доставка, драйвер за изчисляване на цени?
    	$TransportCostDriver = cond_DeliveryTerms::getCostDriver($deliveryTermId);
    	if(!is_object($TransportCostDriver)) return FALSE;
    	
    	$ourCompany = crm_Companies::fetchOurCompany();	 
    	$totalFee = $TransportCostDriver->getTransportFee($deliveryTermId, $productId, $quantity, $totalWeight, $toCountryId, $toPcodeId, $ourCompany->country, $ourCompany->pCode);
    	if($totalFee == tcost_CostCalcIntf::CALC_ERROR) return FALSE; 	
    	
    	$res = array('totalFee' => $totalFee, 
    			     'singleFee' => round($totalFee / $quantity, 2));
    	
    	return $res;
    }
    
    
    /**
     * Връща начисления транспорт към даден документ
     * 
     * @param mixed $docClassId - ид на клас на документ
     * @param int $docId        - ид на документ
     * @param int $recId        - ид на ред на документ
     * @return stdClass|NULL    - записа, или NULL ако няма
     */
    public static function get($docClassId, $docId, $recId)
    {
    	$docClassId = cls::get($docClassId)->getClassId();
    	$rec = self::fetch("#docClassId = {$docClassId} AND #docId = {$docId} AND #recId = '{$recId}'");
    	
    	return (is_object($rec)) ? $rec : NULL;
    }
    
    
    /**
     * Синхронизира сумата на скрития транспорт на един ред на документ
     * 
     * @param mixed $docClassId - ид на клас на документ
     * @param int $docId        - ид на документ
     * @param int $recId        - ид на ред на документ
     * @param double $fee       - начисления скрит транспорт
     * @return void
     */
    public static function sync($docClass, $docId, $recId, $fee)
    {
    	// Клас ид
    	$classId = cls::get($docClass)->getClassId();
    	
    	// Проверка имали запис за ъпдейт
    	$exRec = self::get($classId, $docId, $recId);
    	
    	// Ако подадената сума е NULL, и има съществуващ запис - трие се
    	if(is_null($fee) && is_object($exRec)){
    		self::delete($exRec->id);
    	}
    	
    	// Ако има сума
    	if(isset($fee)){
    		$fields = NULL;
    		
    		// И няма съществуващ запис, ще се добавя нов
    		if(!$exRec){
    			$exRec = (object)array('docClassId' => $classId, 'docId' => $docId, 'recId' => $recId);
    		} else {
    			$fields = 'fee';
    		}
    		 
    		// Ъпдейт / Добавяне на записа
    		$exRec->fee = $fee;
    		self::save($exRec);
    	}
    }
    
    
    /**
     * След подготовка на туклбара на списъчния изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(haveRole('debug')){
    		$data->toolbar->addBtn('Изчистване', array($mvc, 'truncate'), 'warning=Искатели да изчистите таблицата,ef_icon=img/16/sport_shuttlecock.png');
    	}
    }
    
    
    /**
     * Изчиства записите в балансите
     */
    public function act_Truncate()
    {
    	requireRole('debug');
    		
    	// Изчистваne записите от моделите
    	self::truncate();
    		
    	$this->logWrite("Изтриване на кеша на транспортните суми");
    
    	return new Redirect(array($this, 'list'), '|Записите са изчистени успешно');
    }
    
    
    /**
     * Помощна ф-я връщаща п. кода и държава от подадени данни
     * 
     * @param mixed $contragentClassId - клас на контрагента
     * @param int $contragentId        - ид на контрагента
     * @param string|NULL $pCode       - пощенски код
     * @param int|NULL $countryId      - ид на държава
     * @param int|NULL $locationId     - ид на локация
     * @return array $res
     * 				['pCode']     - пощенски код
     * 				['countryId'] - ид на държава
     */
    public static function getCodeAndCountryId($contragentClassId, $contragentId, $pCode = NULL, $countryId = NULL, $locationId = NULL)
    {
    	// Адреса и ид-то на държавата са с приоритет тези, които се подават
    	$res = array('pCode' => $pCode, 'countryId' => $countryId);
    	
    	// Ако няма
    	if(empty($res['pCode']) || empty($res['pCode'])){
    		
    		// И има локация, попълва се липсващото поле от локацията
    		if(isset($locationId)){
    			$locationRec = crm_Locations::fetch($locationId);
    			$res['pCode'] = isset($res['pCode']) ? $res['pCode'] : $locationRec->pCode;
    			$res['countryId'] = isset($res['countryId']) ? $res['countryId'] : $locationRec->countryId;
    		}
    		
    		// Ако отново липсва поле, взимат се от визитката на контрагента
    		if(empty($res['pCode']) || empty($res['pCode'])){
    			$cData = cls::get($contragentClassId)->getContragentData($contragentId);
    			$res['pCode'] = (!empty($res['pCode'])) ? $res['pCode'] : $cData->pCode;
    			$res['countryId'] = (!empty($res['countryId'])) ? $res['countryId'] : $cData->countryId;
    		}
    	}
    	
    	// Връщане на резултата
    	return $res;
    }
    
    
    /**
     * Колко е начисления скрит танспорт за документа
     * 
     * @param mixed $docClass - клас на документа
     * @param int $docId      - ид на документа
     * @return double $count  - общо начислени разходи
     */
    public static function calcInDocument($docClass, $docId)
    {
    	$count = 0;
    	$classId = cls::get($docClass)->getClassId();
    	$feeErr = tcost_CostCalcIntf::CALC_ERROR;
    	
    	$query = self::getQuery();
    	$query->where("#docClassId = {$classId} AND #docId = {$docId}");
    	$query->XPR('sum', 'double', 'sum(#fee)');
    	$query->where("#fee != {$feeErr}");
    	
    	if($rec = $query->fetch()){
    		$count = $rec->sum;
    	}
    	
    	return $count;
    }
    
    
    /**
     * Показване на хинт при изчисление на цена
     * 
     * @param varchar $amountRow   - вербалната сума на реда
     * @param double $amountFee    - вербалната транспортна такса
     * @param double $vat          - процент ДДС
     * @param double $currencyRate - валутен курс
     * @param varchar $chargeVat   - режим на ДДС
     * @return core_ET             - сумата на реда с хинт
     */
    public static function getAmountHint($amountRow, $amountFee, $vat, $currencyRate, $chargeVat)
    {
    	if($amountFee == tcost_CostCalcIntf::CALC_ERROR){
    		
    		return ht::createHint($amountRow, 'Скритият транспорт, не може да бъде изчислен', 'warning', FALSE);
    	} elseif(isset($amountFee)){
    		$amountFee = deals_Helper::getDisplayPrice($amountFee, $vat, $currencyRate, $chargeVat);
    		$amountFee = cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($amountFee);
    		$hint = tr("Транспорт|*: {$amountFee}");
    		
    		return ht::createHint($amountRow, $hint, 'notice', FALSE, 'width=14px,height=14px');
    	}
    	
    	return $amountRow;
    }
    
    
    /**
     * Сумата на видимия транспорт в документа
     * 
     * @param core_Query $query - заявка
     * @param string $productFld - име на полето на артикула
     * @param string $amountFld - име на полето на сумата
     * @return double $amount - сума на видимия транспорт в основна валута без ДДС
     */
    public static function getVisibleTransportCost(core_Query $query, $productFld = 'productId', $amountFld = 'amount')
    {
    	$amount = 0;
    	
    	$transportArr = keylist::toArray(tcost_Setup::get('TRANSPORT_PRODUCTS_ID'));
    	$query->in($productFld, $transportArr);
    	
    	while($dRec = $query->fetch()){
    		$amount += $dRec->{$amountFld};
    	}
    	
    	return $amount;
    }
    
    
    /**
     * Връща общото тегло на масив с артикули
     * 
     * @param array $products - масив с артикули
     * @param tcost_CostCalcIntf $TransportCalc - интерфейс за изчисляване на транспортна цена
     * @param string $productFld - поле съдържащо ид-то на артикул
     * @param string $quantityFld - поле съдържащо количеството на артикул
     * @return double$totalWeight - общото тегло
     */
    public static function getTotalWeight($products,tcost_CostCalcIntf $TransportCalc, $productFld = 'productId', $quantityFld = 'quantity')
    {
    	$totalWeight = 0;
    	if(!is_array($products)) return $totalWeight;
    	
    	// За всеки артикул в масива
    	foreach ($products as $p1){
    		
    		// Намира се обемното му тегло и се съжура
    		$singleWeight = cat_Products::getParams($p1->{$productFld}, 'transportWeight');
    		$singleVolume = cat_Products::getParams($p1->{$productFld}, 'transportVolume');
    		$singleWeight = $TransportCalc->getVolumicWeight($singleWeight, $singleVolume);
    		$totalWeight += $singleWeight * $p1->{$quantityFld};
    	}
    	
    	// Връщане на общото тегло
    	return $totalWeight;
    }
}