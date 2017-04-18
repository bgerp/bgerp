<?php


/**
 * Модел "Взаимодействие на Зони и Навла"
 *
 *
 * @category  bgerp
 * @package   tcost
 * @author    Kristiyan Serafimov <kristian.plamenov@gmail.com> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class tcost_FeeZones extends core_Master
{


	/**
	 * Поддържани интерфейси
	 */
	public $interfaces = 'tcost_CostCalcIntf';
	
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'trans_FeeZones';
	
	
    /**
     * Полета, които се виждат
     */
    public $listFields = "name, deliveryTermId=Доставка->Условие, deliveryTime=Доставка->Време,createdOn, createdBy";


    /**
     * Заглавие
     */
    public $title = "Навла";


    /**
     * Плъгини за зареждане
     */
    public $loadList = "plg_Created, plg_RowTools2, plg_Printing, tcost_Wrapper";


    /**
     * Време за опресняване информацията при лист на събитията
     */
    public $refreshRowsTime = 5000;


    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,tcost';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,tcost';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,tcost';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,tcost';


    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,tcost';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,tcost';


    /**
     * Детайли за зареждане
     */
    public $details = "tcost_Fees, tcost_Zones";


    /**
     * Единично поле за RowTools
     */
    public $rowToolsSingleField = 'name';


    /**
     * Константа, специфична за дадения режим на транспорт
     * 
     * @var double
     */
    const V2C = 1;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'varchar(16)', 'caption=Зона, mandatory');
        $this->FLD('deliveryTermId', 'key(mvc=cond_DeliveryTerms, select = codeName)', 'caption=Условие на доставка, mandatory');
        $this->FLD('deliveryTime', 'time', 'caption=Доставка,recently,smartCenter');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'delete' && isset($rec)){
    		if(tcost_Fees::fetch("#feeId = {$rec->id}") || tcost_Zones::fetch("#zoneId = {$rec->id}")){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Определяне на обемното тегло, на база на обема на товара
     *
     * @param double $weight  - Тегло на товара
     * @param double $volume  - Обем  на товара
     *
     * @return double         - Обемно тегло на товара
     */
    public function getVolumicWeight($weight, $volume)
    {
    	$volumicWeight = NULL;
    	if(!empty($weight) || !empty($volume)){
    		$volumicWeight = max($weight, $volume * self::V2C);
    	}
    	
    	return $volumicWeight;
    }
    
    
    /**
     * Определяне цената за транспорт при посочените параметри
     *
     * @param int $deliveryTermId    -условие на доставка
     * @param int $productId         - ид на артикул
     * @param int $packagingId       - ид на опаковка/мярка
     * @param int $quantity          - количество
     * @param int $totalWeight       - Общо тегло на товара
     * @param int $toCountry         - id на страната на мястото за получаване
     * @param string $toPostalCode   - пощенски код на мястото за получаване
     * @param int $fromCountry       - id на страната на мястото за изпращане
     * @param string $fromPostalCode - пощенски код на мястото за изпращане
     *
     * @return array
     * 			['fee']              - цена, която ще бъде платена за теглото на артикул, ако не може да се изчисли се връща tcost_CostCalcIntf::CALC_ERROR
     * 			['deliveryTime']     - срока на доставка в секунди ако го има
     */
    public function getTransportFee($deliveryTermId, $productId, $packagingId, $quantity, $totalWeight, $toCountry, $toPostalCode, $fromCountry, $fromPostalCode)
    {
    	// Колко е еденичното транспортно тегло на артикула
    	$weightRow = cat_Products::getWeight($productId, $packagingId, $quantity);
    	$volumeRow = cat_Products::getVolume($productId, $packagingId, $quantity);
    	
    	// Ако теглото е 0 и няма обем, да не се изчислява транспорт
    	if(empty($weightRow) && isset($weightRow) && empty($volumeRow)) return;
    	
    	$weightRow = $this->getVolumicWeight($weightRow, $volumeRow);
    	
    	// Ако няма, цената няма да може да се изчисли
    	if(empty($weightRow)) return array('fee' => tcost_CostCalcIntf::CALC_ERROR);
    	
    	// Опит за калкулиране на цена по посочените данни
    	$fee = tcost_Fees::calcFee($deliveryTermId, $toCountry, $toPostalCode, $totalWeight, $weightRow);
    	
    	$deliveryTime = ($fee[3]) ? $fee[3] : NULL;
    	
    	// Ако цената може да бъде изчислена се връща
    	if($fee != tcost_CostCalcIntf::CALC_ERROR){
    		$fee = (isset($fee[1])) ? $fee[1] : 0;
    	} 
    	
    	// Връщане на изчислената цена
    	return array('fee' => $fee, 'deliveryTime' => $deliveryTime);
    }
    
    
    /**
     * Добавяне на бутон за изчисление
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
    	if (haveRole('admin, ceo, tcost')) {
    		$data->toolbar->addBtn("Изчисление", array($mvc, "calcFee", 'ret_url' => TRUE), "ef_icon=img/16/arrow_out.png, title=Изчисляване на разходи по транспортна зона");
    	}
    }
    
    
    /**
     * Изчисление на транспортни разходи
     */
    public function act_calcFee()
    {
    	//Дос на потребителите
    	requireRole('admin, ceo, tcost');
    
    	// Вземаме съответстващата форма на този модел
    	$form = cls::get('core_Form');
    	$form->FLD('deliveryTermId', 'key(mvc=cond_DeliveryTerms, select = codeName,allowEmpty)', 'caption=Условие на доставка, mandatory');
    	$form->FLD('countryId', 'key(mvc = drdata_Countries, select = letterCode2,allowEmpty)', 'caption=Държава, mandatory,smartCenter');
    	$form->FLD('pCode', 'varchar(16)', 'caption=П. код,recently,class=pCode,smartCenter, notNull');
    	$form->FLD('singleWeight', 'double(Min=0)', 'caption=Единично тегло,mandatory');
    	$form->FLD('totalWeight', 'double(Min=0)', 'caption=Тегло за изчисление,recently, unit = kg.,mandatory');
    	
    	// Въвеждаме формата от Request (тази важна стъпка я бяхме пропуснали)
    	$form->input();
    	$form->setDefault('singleWeight', 1);
    	
    	if ($form->isSubmitted()) {
    		$rec = $form->rec;
    		try {
    			$result = tcost_Fees::calcFee($rec->deliveryTermId, $rec->countryId, $rec->pCode, $rec->totalWeight, $rec->singleWeight);
    			if($result === tcost_CostCalcIntf::CALC_ERROR){
    				$form->setError("deliveryTermId,countryId,pCode", 'Не може да се изчисли сума за транспорт');
    			} else {
    				$zoneName = tcost_FeeZones::getVerbal($result[2], 'name');
    				$form->info = "Цената за|* <b>" . $rec->singleWeight . "</b> |на|* <b>" . $rec->totalWeight . "</b> |кг. от този пакет ще струва|* <b>". round($result[1], 4).
    				"</b>, |a всички|* <b>".  $rec->totalWeight . "</b> |ще струват|* <b>" . round($result[0], 4) . "</b>. |Пратката попада в|* <b>" . $zoneName . "</b>";
    				$form->info = tr($form->info);
    			}
    		} catch(core_exception_Expect $e) {
    			$form->setError("zoneId, countryId", "Не може да се изчисли по зададените данни, вашата пратка не попада в никоя зона");
    		}
    	}
    
    	$form->title = 'Пресмятане на навла';
    	$form->toolbar->addSbBtn('Изчисли', 'save', 'ef_icon=img/16/arrow_refresh.png');
    	$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
    	
    	return $this->renderWrapping($form->renderHTML());
    }
}