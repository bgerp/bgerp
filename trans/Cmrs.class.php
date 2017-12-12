<?php



/**
 * Клас 'trans_Cmrs'
 *
 * Документ за ЧМР товарителници
 *
 *
 * @category  bgerp
 * @package   trans
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class trans_Cmrs extends core_Master
{
	
	
    /**
     * Заглавие
     */
    public $title = 'Товарителници';


    /**
     * Абревиатура
     */
    public $abbr = 'CMR';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, trans_Wrapper,plg_Clone,doc_DocumentPlg, plg_Printing, plg_Search, doc_ActivatePlg, doc_EmailCreatePlg';

    
    /**
     * Кой може да го клонира?
     */
    public $canClonerec = 'ceo, trans';
    
    
    /**
     * Кой може да го вижда?
     */
    public $canSingle = 'ceo, trans';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, trans';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, trans';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, trans';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id=№,cmrNumber=ЧМР №,title=Товарителница, originId=Експедиция, folderId, state,createdOn, createdBy';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Товарителница';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'trans/tpl/SingleLayoutCMR.shtml';
    		
    		
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/lorry_go.png';
    
   
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.7|Логистика";
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'cmrNumber,consigneeData,deliveryPlace,loadingDate,cariersData,vehicleReg';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = FALSE;
    
    
    /**
     * Кои редове да са компресирани
     */
    const NUMBER_GOODS_ROWS = 4;
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'cmrNumber,loadingDate';
    
    
    /**
     * Може ли да се редактират активирани документи
     */
    public $canEditActivated = TRUE;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('cmrNumber', 'varchar(12)', 'caption=ЧМР №,mandatory');
    	$this->FLD('senderData', 'text(rows=2)', 'caption=1. Изпращач,mandatory');
    	$this->FLD('consigneeData', 'text(rows=2)', 'caption=2. Получател,mandatory');
    	$this->FLD('deliveryPlace', 'text(rows=2)', 'caption=3. Разтоварен пункт,mandatory');
    	$this->FLD('loadingPlace', 'text(rows=2)', 'caption=4. Товарен пункт,mandatory');
    	$this->FLD('loadingDate', 'date', 'caption=4. Дата на товарене,mandatory');
    	$this->FLD('documentsAttached', 'varchar', 'caption=5. Приложени документи');
    	$this->FLD('goodsData', "blob(1000000, serialize, compress)", "input=none,column=none,single=none");
    	
    	$this->FLD('class', 'varchar(12)', 'caption=ADR->Клас,autohide');
    	$this->FLD('number', 'varchar(12)', 'caption=ADR->Цифра,autohide');
    	$this->FLD('letter', 'varchar(12)', 'caption=ADR->Буква,autohide');
    	$this->FLD('natureofGoods', 'varchar(12)', 'caption=ADR->Вид на стоката,autohide');
    	
    	$this->FLD('senderInstructions', 'text(rows=2)', 'caption=Допълнително->13. Указания на изпращача');
    	$this->FLD('instructionsPayment', 'text(rows=2)', 'caption=Допълнително->14. Предп. плащане навло');
    	$this->FLD('carragePaid', 'varchar(12)', 'caption=Допълнително->Предплатено');
    	$this->FLD('sumPaid', 'varchar(12)', 'caption=Допълнително->Дължимо');
    	
    	$this->FLD('cashOnDelivery', 'varchar', 'caption=Допълнително->15. Наложен платеж');
    	$this->FLD('cariersData', 'text(rows=2)', 'caption=Допълнително->16. Превозвач,mandatory');
    	$this->FLD('vehicleReg', 'varchar', 'caption=МПС регистрационен №,mandatory');
    	$this->FLD('successiveCarriers', 'text(rows=2)', 'caption=Допълнително->17. Посл. превозвачи');
    	$this->FLD('specialagreements', 'text(rows=2)', 'caption=Допълнително->19. Спец. споразумения');
    	$this->FLD('establishedPlace', 'text(rows=2)', 'caption=21. Изготвена в');
    	$this->FLD('establishedDate', 'date', 'caption=21. Изготвена на');
    	
    	$this->setDbUnique('cmrNumber');
    }
    
    
    /**
     * Изпълнява се след извличане на запис чрез ->fetch()
     */
    public static function on_AfterRead($mvc, $rec)
    {
    	// Разпъване на компресираните полета
    	if(is_array($rec->goodsData)) {
    		foreach($rec->goodsData as $field => $value) {
    			$rec->{$field} = $value;
    		}
    	}
    }
    
    
    /**
     * Преди запис в модела, компактираме полетата
     */
    public function save_(&$rec, $fields = NULL, $mode = NULL)
    {
    	$saveGoodsData = FALSE;
    	$goodsData = array();
    	
    	$arr = (array)$rec;
    	$compressFields = $this->getCompressFields();
    	
    	// Компресиране на нужните полета
    	foreach ($arr as $fld => $value){
    		if(in_array($fld, $compressFields)){
    			$goodsData[$fld] = ($value !== '') ? $value : NULL;
    			$saveGoodsData = TRUE;
    		}
    	}
    	
    	if($saveGoodsData === TRUE){
    		$rec->goodsData = $goodsData;
    		
    		if(is_array($fields)){
    			$fields['goodsData'] = 'goodsData';
    		}
    	}
    	
    	$res = parent::save_($rec, $fields, $mode);
    	
    	if(isset($rec->originId)){
    		doc_DocumentCache::invalidateByOriginId($rec->originId);
    	}
    	
    	return $res;
    }
    
    
    /**
     * Кои полета ще се компресират
     * 
     * @return array
     */
    private function getCompressFields()
    {
    	$res = array();
    	foreach (range(1, self::NUMBER_GOODS_ROWS) as $i){
    		foreach (array('mark', 'numOfPacks', 'methodOfPacking', 'natureOfGoods', 'statNum', 'grossWeight', 'volume') as $fld){
    			$res[] = "{$fld}{$i}";
    		}
    	}
    	
    	return $res;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public function prepareEditForm_($data)
    {
    	$data = parent::prepareEditForm_($data);
    	$form = &$data->form;
    	
    	// Разпъване на компресираните полета
    	foreach (range(1, self::NUMBER_GOODS_ROWS) as $i){
    		$autohide = ($i === 1) ? '' : 'autohide';
    		$after = ($i === 1) ? 'documentsAttached' : ("volume" . ($i-1));
    		$mandatory = ($i === 1) ? 'mandatory' : '';
    		
    		$form->FLD("mark{$i}", 'varchar', "after={$after},caption={$i}. Информация за стоката->6. Знаци и Номера,{$autohide}");
    	    $form->FLD("numOfPacks{$i}", 'varchar', "after=mark{$i},caption={$i}. Информация за стоката->7. Брой колети,{$autohide}");
    	    $form->FLD("methodOfPacking{$i}", 'varchar', "after=methodOfPacking{$i},caption={$i}. Информация за стоката->8. Вид опаковка,{$autohide}");
    	    $form->FLD("natureOfGoods{$i}", 'varchar', "{$mandatory},after=natureOfGoods{$i},caption={$i}. Информация за стоката->9. Вид стока,{$autohide}");
    	    $form->FLD("statNum{$i}", 'varchar', "after=statNum{$i},caption={$i}. Информация за стоката->10. Статистически №,{$autohide}");
    	    $form->FLD("grossWeight{$i}", 'varchar', "after=grossWeight{$i},caption={$i}. Информация за стоката->11. Тегло Бруто,{$autohide}");
    	    $form->FLD("volume{$i}", 'varchar', "after=volume{$i},caption={$i}. Информация за стоката->12. Обем,{$autohide}");
    	}
    	
    	return $data;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec  = &$form->rec;
    	
    	// Зареждане на дефолти от ориджина
    	if(isset($rec->originId) && !isset($rec->id)){
    		$mvc->setDefaultsFromShipmentOrder($rec->originId, $form);
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
    	if($form->isSubmitted()){
    		
    		// Подсигуряване че винаги след редакция ще е в чернова
    		if($form->cmd == 'save'){
    			$form->rec->state = 'draft';
    		}
    	}
    }
    
    
    /**
     * Зарежда дефолтни данни от формата
     * 
     * @param int $originId   - ориджин
     * @param core_Form $form - форма
     * @return void
     */
    private function setDefaultsFromShipmentOrder($originId, &$form)
    {
    	expect($origin = doc_Containers::getDocument($originId));
    	$sRec = $origin->fetch();
    	$form->setDefault('cmrNumber', $sRec->id);
    	$lData = $origin->getLogisticData();
    	
    	// Всичките дефолтни данни трябва да са на английски
    	core_Lg::push('en');
    	 
    	// Информация за изпращача
    	$ownCompanyId = crm_Setup::get('BGERP_OWN_COMPANY_ID', TRUE);
    	$senderData = $this->getDefaultContragentData('crm_Companies', $ownCompanyId);
    	 
    	// Информация за получателя
    	$consigneeData = $this->getDefaultContragentData($sRec->contragentClassId, $sRec->contragentId, FALSE);
    	 
    	// Място на товарене / Разтоварване
    	$loadingPlace = $lData['fromPCode'] . " " .  transliterate($lData['fromPlace']) . ", " . $lData['fromCountry'];
    	$deliveryPlace = $lData['toPCode'] . " " .  transliterate($lData['toPlace']) . ", " . $lData['toCountry'];
    	 
    	// Има ли общо тегло в ЕН-то
    	$weight = ($sRec->weightInput) ? $sRec->weightInput : $sRec->weight;
    	if(!empty($weight)){
    		$weight = core_Type::getByName('cat_type_Weight')->toVerbal($weight);
    		$form->setDefault('grossWeight1', $weight);
    	}
    	 
    	// Има ли общ обем в ЕН-то
    	$volume = ($sRec->volumeInput) ? $sRec->volumeInput : $sRec->volume;
    	if(!empty($weight)){
    		$volume = core_Type::getByName('cat_type_Volume')->toVerbal($volume);
    		$form->setDefault('volume1', $volume);
    	}
    	 
    	core_Lg::pop();
    	 
    	// Задаване на дефолтните полета
    	$form->setDefault('senderData', $senderData);
    	$form->setDefault('consigneeData', $consigneeData);
    	$form->setDefault('deliveryPlace', $deliveryPlace);
    	$form->setDefault('loadingPlace', $loadingPlace);
    	$form->setDefault('loadingDate', $lData['loadingTime']);
    	 
    	// Информация за превозвача
    	if(isset($sRec->lineId)){
    		$lineRec = trans_Lines::fetch($sRec->lineId);
    		if(isset($lineRec->forwarderId)){
    			$carrierData = $this->getDefaultContragentData('crm_Companies', $lineRec->forwarderId);
    			$form->setDefault('cariersData', $carrierData);
    		}
    	
    		if(isset($lineRec->vehicleId)){
    			$vehicleReg = trans_Vehicles::fetchField($lineRec->vehicleId, 'number');
    			$form->setDefault('vehicleReg', $vehicleReg);
    		}
    	}
    	 
    	// Има ли общ брой палети
    	if(!empty($sRec->palletCountInput)){
    		$collets = core_Type::getByName('int')->toVerbal($sRec->palletCountInput);
    		$collets .= " PALLETS";
    		$form->setDefault('numOfPacks1', $collets);
    	}
    }
    
    
    /**
     * Информацията за контрагента
     * 
     * @param mixed $contragentClassId - клас на контрагента
     * @param int $contragentId        - контрагент ид
     * @param boolean $translate       - превод на името на контрагента
     * @return string                  - информация за контрагента
     */
    private function getDefaultContragentData($contragentClassId, $contragentId, $translate = TRUE)
    {
    	$Contragent = cls::get($contragentClassId);
    	$contragentAddress = $Contragent->getFullAdress($contragentId, TRUE, FALSE)->getContent();
    	$contragentAddress = str_replace('<br>', ', ', $contragentAddress);
    	$contragentCountry = $Contragent->getVerbal($contragentId, 'country');
    	$contragentName = ($translate === TRUE) ? transliterate(tr($Contragent->fetchField($contragentId, 'name'))) : $Contragent->getVerbal($contragentId, 'name');
    	$contragenData = "{$contragentName},{$contragentAddress}, {$contragentCountry}";
    	
    	return $contragenData;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->title = $mvc->getLink($rec->id, 0);
    	
    	if(isset($row->originId)){
    		if(!Mode::isReadOnly()){
    			$origin = doc_Containers::getDocument($rec->originId);
    			$row->originId = $origin->getInstance()->getLink($origin->that, 0);
    		} else {
    			unset($row->originId);
    		}
    	}
    	
    	if(isset($fields['-single'])){
    		
    		// Вербализиранре на компресираните полета
    		if(is_array($rec->goodsData)) {
    			foreach($rec->goodsData as $field => $value) {
    				if(isset($value)){
    					$row->{$field} = core_Type::getByName('varchar')->toVerbal($value);
    				}
    			}
    		}
    		
    		$row->basicColor = "#000";
    		
    		if(!empty($rec->establishedDate)){
    			$row->establishedDate = dt::mysql2verbal($rec->loadingDate, 'd.m.Y');
    		}
    		
    		if(!empty($rec->loadingDate)){
    			$row->loadingDate = dt::mysql2verbal($rec->loadingDate, 'd.m.y');
    		}
    	}
    }
    
    
    /**
     * Изпълнява се след подготвянето на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
    	$data->listFilter->view = 'horizontal';
    	$data->listFilter->showFields = 'search';
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
    }
    
    
    /**
     * Документа не може да бъде начало на нишка; може да се създава само в съществуващи нишки
     */
    public static function canAddToFolder($folderId)
    {
    	return FALSE;
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     */
    public static function canAddToThread($threadId)
    {
    	$firstDoc = doc_Threads::getFirstDocument($threadId);
    	if($firstDoc && $firstDoc->isInstanceOf('deals_DealMaster')){
    		$state = $firstDoc->fetchField('state');
    		if(in_array($state, array('active', 'closed', 'pending'))) return TRUE;
    	}
    	
    	return FALSE;
    }
    
    
    /**
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
    	expect($rec = $this->fetch($id));
    	$title = $this->getRecTitle($rec);
    
    	$row = (object)array(
    			'title'    => $title,
    			'authorId' => $rec->createdBy,
    			'author'   => $this->getVerbal($rec, 'createdBy'),
    			'state'    => $rec->state,
    			'recTitle' => $title
    	);
    
    	return $row;
    }
    
    
    /**
     * Връща тялото на имейла генериран от документа
     *
     * @see email_DocumentIntf
     * @param int $id - ид на документа
     * @param boolean $forward
     * @return string - тялото на имейла
     */
    public function getDefaultEmailBody($id, $forward = FALSE)
    {
    	$handle = $this->getHandle($id);
    	$tpl = new ET(tr("Моля запознайте се с нашето|* |ЧМР|*") . ': #[#handle#]');
    	$tpl->append($handle, 'handle');
    
    	return $tpl->getContent();
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'add' && isset($rec->originId)){
    		$origin = doc_Containers::getDocument($rec->originId);
    		if(!$origin->isInstanceOf('store_ShipmentOrders')){
    			$requiredRoles = 'no_one';
    		} else {
    			$state = $origin->fetchField('state');
    			if(!in_array($state, array('active', 'pending'))){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    protected static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
    	$fields = $mvc->getCompressFields();
    	
    	// Допълване на ключовите думите
    	foreach ($fields as $fld){
    		if(strpos($fld, 'natureOfGoods') !== FALSE || strpos($fld, 'statNum') !== FALSE){
    			if(!empty($rec->{$fld})){
    				$res .= " " . plg_Search::normalizeText($rec->{$fld});
    			}
    		}
    	}
    }
    
    
    /**
     * Метод по подразбиране, за връщане на състоянието на документа в зависимот от класа/записа
     *
     * @param core_Master $mvc
     * @param NULL|string $res
     * @param NULL|integer $id
     * @param NULL|boolean $hStatus
     * @see doc_HiddenContainers
     */
    public function getDocHiddenStatus($id, $hStatus)
    {
    	$cid = $this->fetchField($id, 'containerId');
    	if(doclog_Documents::fetchByCid($cid, doclog_Documents::ACTION_PRINT)) return TRUE;
    	
    	return NULL;
    }
}