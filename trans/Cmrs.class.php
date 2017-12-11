<?php



/**
 * Клас 'trans_Cmrs'
 *
 * Документ за Транспортни линии
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
    public $listFields = 'id=№,title=Товарителница, originId=Експедиция, folderId, state,createdOn, createdBy';
    
    
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
    public $searchFields = 'consigneeData,deliveryPlace,loadingDate,natureOfGoods1,statNum1,natureOfGoods2,statNum2,natureOfGoods3,statNum3,natureOfGoods4,statNum4,cariersData,vehicleReg';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = FALSE;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('senderData', 'text(rows=2)', 'caption=1. Изпращач,mandatory');
    	$this->FLD('consigneeData', 'text(rows=2)', 'caption=2. Получател,mandatory');
    	$this->FLD('deliveryPlace', 'text(rows=2)', 'caption=3. Разтоварен пункт,mandatory');
    	$this->FLD('loadingPlace', 'text(rows=2)', 'caption=4. Товарен пункт,mandatory');
    	$this->FLD('loadingDate', 'date', 'caption=4. Дата на товарене,mandatory');
    	$this->FLD('documentsAttached', 'varchar', 'caption=5. Приложени документи');
    	
    	$this->FLD('mark1', 'varchar', 'caption=1. Информация за стоката->6. Знаци и Номера');
    	$this->FLD('numOfPacks1', 'varchar', 'caption=1. Информация за стоката->7. Брой колети');
    	$this->FLD('methodOfPacking1', 'varchar', 'caption=1. Информация за стоката->8. Вид опаковка');
    	$this->FLD('natureOfGoods1', 'varchar', 'caption=1. Информация за стоката->9. Вид стока,mandatory');
    	$this->FLD('statNum1', 'varchar', 'caption=1. Информация за стоката->10. Статистически №');
    	$this->FLD('grossWeight1', 'varchar', 'caption=1. Информация за стоката->11. Тегло Бруто');
    	$this->FLD('volume1', 'varchar', 'caption=1. Информация за стоката->12. Обем');
    	$this->FLD('mark2', 'varchar', 'caption=2. Информация за стоката->6. Знаци и Номера,autohide');
    	$this->FLD('numOfPacks2', 'varchar', 'caption=2. Информация за стоката->7. Брой колети,autohide');
    	$this->FLD('methodOfPacking2', 'varchar', 'caption=2. Информация за стоката->8. Вид опаковка,autohide');
    	$this->FLD('natureOfGoods2', 'varchar', 'caption=2. Информация за стоката->9. Вид стока,autohide');
    	$this->FLD('statNum2', 'varchar', 'caption=2. Информация за стоката->10. Статистически №,autohide');
    	$this->FLD('grossWeight2', 'varchar', 'caption=2. Информация за стоката->11. Тегло Бруто,autohide');
    	$this->FLD('volume2', 'varchar', 'caption=2. Информация за стоката->12. Обем,autohide');
    	$this->FLD('mark3', 'varchar', 'caption=3. Информация за стоката->6. Знаци и Номера,autohide');
    	$this->FLD('numOfPacks3', 'varchar', 'caption=3. Информация за стоката->7. Брой колети,autohide');
    	$this->FLD('methodOfPacking3', 'varchar', 'caption=3. Информация за стоката->8. Вид опаковка,autohide');
    	$this->FLD('natureOfGoods3', 'varchar', 'caption=3. Информация за стоката->9. Вид стока,autohide');
    	$this->FLD('statNum3', 'varchar', 'caption=3. Информация за стоката->10. Статистически №,autohide');
    	$this->FLD('grossWeight3', 'varchar', 'caption=3. Информация за стоката->11. Тегло Бруто,autohide');
    	$this->FLD('volume3', 'varchar', 'caption=3. Информация за стоката->12. Обем,autohide');
    	$this->FLD('mark4', 'varchar', 'caption=4. Информация за стоката->6. Знаци и Номера,autohide');
    	$this->FLD('numOfPacks4', 'varchar', 'caption=4. Информация за стоката->7. Брой колети,autohide');
    	$this->FLD('methodOfPacking4', 'varchar', 'caption=4. Информация за стоката->8. Вид опаковка,autohide');
    	$this->FLD('natureOfGoods4', 'varchar', 'caption=4. Информация за стоката->9. Вид стока,autohide');
    	$this->FLD('statNum4', 'varchar', 'caption=4. Информация за стоката->10. Стат. №,autohide');
    	$this->FLD('grossWeight4', 'varchar', 'caption=4. Информация за стоката->11. Тегло Бруто,autohide');
    	$this->FLD('volume4', 'varchar', 'caption=4. Информация за стоката->12. Обем,autohide');
    	
    	$this->FLD('class', 'varchar(12)', 'caption=ADR->Клас');
    	$this->FLD('number', 'int', 'caption=ADR->Цифра');
    	$this->FLD('letter', 'varchar(12)', 'caption=ADR->Буква');
    	$this->FLD('senderInstructions', 'text(rows=4)', 'caption=Допълнително->13. Указания на изпращача');
    	$this->FLD('instructionsPayment', 'text(rows=2)', 'caption=Допълнително->14. Предп. плащане навло');
    	$this->FLD('cashOnDelivery', 'varchar', 'caption=Допълнително->15. Наложен платеж');
    	$this->FLD('cariersData', 'text(rows=2)', 'caption=Допълнително->16. Превозвач,mandatory');
    	$this->FLD('vehicleReg', 'varchar', 'caption=МПС регистрационен №,mandatory');
    	$this->FLD('successiveCarriers', 'text(rows=2)', 'caption=Допълнително->17. Посл. превозвачи');
    	$this->FLD('specialagreements', 'text(rows=4)', 'caption=Допълнително->19. Спец. споразумения');
    	$this->FLD('establishedPlace', 'text(rows=2)', 'caption=21. Изготвена в');
    	$this->FLD('establishedDate', 'date', 'caption=21. Изготвена на');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec  = &$form->rec;
    	
    	// Към кой документ е
    	expect($origin = doc_Containers::getDocument($rec->originId));
    	$sRec = $origin->fetch();
    	$lData = $origin->getLogisticData();
    	
    	// Всичките дефолтни данни трябва да са на английски
    	core_Lg::push('en');
    	
    	// Информация за изпращача
    	$ownCompanyId = crm_Setup::get('BGERP_OWN_COMPANY_ID', TRUE);
    	$senderData = $mvc->getDefaultContragentData('crm_Companies', $ownCompanyId);
    	
    	// Информация за получателя
    	$consigneeData = $mvc->getDefaultContragentData($sRec->contragentClassId, $sRec->contragentId, FALSE);
    	
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
    			$carrierData = $mvc->getDefaultContragentData('crm_Companies', $lineRec->forwarderId);
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
    	
    	if(!Mode::isReadOnly()){
    		$origin = doc_Containers::getDocument($rec->originId);
    		$row->originId = $origin->getInstance()->getLink($origin->that, 0);
    	} else {
    		unset($row->originId);
    	}
    	
    	if(!empty($rec->loadingDate)){
    		$row->loadingDate = dt::mysql2verbal($rec->loadingDate, 'd.m.y');
    	}
    	
    	if(!empty($rec->establishedDate)){
    		$row->establishedDate = dt::mysql2verbal($rec->loadingDate, 'd.m.Y');
    	}
    	
    	$row->basicColor = "#000";
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
    	$originId = Request::get('originId', 'int');
    	if(empty($originId)) return FALSE;
    	
    	$origin = doc_Containers::getDocument($originId);
    	$state = $origin->rec()->state;
    	
    	if(in_array($state, array('draft','active', 'pending')) && $origin->isInstanceOf('store_ShipmentOrders')) return TRUE;
    	
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
    	// ХАК за да се клонират ЧМР-та (@see trans_Cmrs::canAddToThread)
    	if($action == 'clonerec' && isset($rec)){
    		if($mvc->haveRightFor('single', $rec, $userId)){
    			$requiredRoles = $mvc->getRequiredRoles('clonerec', NULL, $userId);
    		}
    	}
    }
}