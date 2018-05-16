<?php



/**
 * Детайли на Транспортните линии
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
    public $loadList = 'plg_RowTools2, trans_Wrapper, plg_GroupByField';
    
    
    /**
     * Поле за групиране
     */
    public $groupByField = 'classId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'containerId=Документ,storeId=Складове,documentLu=Логистични единици->От документа,readyLu=Логистични единици->Подготвени,measures=Тегло / Обем,collection=Инкасиране,status,notes=@,documentHtml=@';

    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     *
     *  @var string
     */
    public $hideListFieldsIfEmpty = 'weight,collection,volume,notes,address,documentHtml';
    
    
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
    public $canList = 'no_one';
    
    
    /**
     * Кой има право да подготвя?
     */
    public $canPrepare = 'trans,ceo';
    
    
    /**
     * Може ръчно да подготвя реда
     */
    public $canTogglestatus = 'trans,ceo';
    
    
    /**
     * Работен кеш
     */
    private static $cache = array();
    
    
    /**
     * Вербалните имена на класовете
     */
    private static $classGroups = array('store_ShipmentOrders'       => 'Експедиции', 
    		                            'store_Receipts'             => 'Доставки', 
    		                            'store_ConsignmentProtocols' => 'Отговорно пазене', 
    		                            'store_Transfers'            => 'Трансфери');
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('lineId', 'key(mvc=trans_Lines)', 'column=none,notNull,silent,hidden,mandatory');
    	$this->FLD('containerId', 'key(mvc=doc_Containers)', 'column=none,notNull,silent,hidden,mandatory');
    	$this->FLD('documentLu', 'blob(serialize, compress)', 'input=none');
    	$this->FLD('readyLu', 'blob(serialize, compress)', 'input=none');
    	$this->FLD('classId', 'class', 'input=none');
    	$this->FLD('status', 'enum(waiting=Чакащо,ready=Готово)', 'input=none,notNull,value=waiting,caption=Статус,smartCenter,tdClass=status-cell');
    	
    	$this->setDbIndex('containerId');
    	$this->setDbIndex('classId');
    	$this->setDbIndex('status');
    }
    
    
    /**
     * Синхронизиране детайла на линията с документа
     * 
     * @param int $lineId      - линия
     * @param int $containerId - контейнер на документ
     * @return int             - синхронизирания запис
     */
    public static function sync($lineId, $containerId)
    {
    	$Document = doc_Containers::getDocument($containerId);
    	$transportInfo = $Document->getTransportLineInfo();
    	
    	// Има ли запис за тази линия
    	$rec = self::fetch("#lineId = {$lineId} AND #containerId = {$containerId}");
    	
    	// Ако няма се проверява за запис на друга линия и се пренасочва към тази
    	if(empty($rec)){
    		if($rec = self::fetch("#lineId != {$lineId} AND #containerId = {$containerId}")){
    			$rec->lineId = $lineId;
    		}
    	}
    	
    	// Ако няма се създава нов запис
    	if(empty($rec)){
    		$rec = (object)array('lineId' => $lineId, 'containerId' => $containerId, 'classId' => $Document->getClassId());
    	}
    	
    	// Запис на ЛЕ от документа, ако позволява
    	if($r = $Document->requireManualCheckInTransportLine()){
    		$rec->documentLu = $transportInfo['transportUnits'];
    	}
    	
    	self::save($rec);
    	cls::get('trans_Lines')->updateMaster($rec->lineId);
    	
    	return $rec->id;
    }
    
    
    /**
     * Преди запис на документ
     */
    protected static function on_BeforeSave(core_Manager $mvc, $res, $rec, $fields = NULL)
    {
    	if($rec->_forceStatus !== TRUE){
    		$rec->status = (trans_Helper::checkTransUnits($rec->documentLu, $rec->readyLu)) ? 'ready' : 'waiting';
    	}
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
    	$Document = doc_Containers::getDocument($rec->containerId);
    	
    	$transportInfo = $Document->getTransportLineInfo();
    	if(!core_Mode::isReadOnly()){
    		$row->containerId = $Document->getLink(0);
    		$row->containerId = "<span class='state-{$transportInfo['state']} document-handler'>{$row->containerId}</span>";
    	} else {
    		$row->containerId = "#" . $Document->getHandle();
    	}
    	
    	if(Mode::is('renderHtmlInLine') && isset($Document->layoutFileInLine)){
    		$row->documentHtml = $Document->getInlineDocumentBody();
    	}
    	
    	$row->ROW_ATTR['class'] = ($rec->status == 'waiting') ? 'soft-rejected' : 'state-active';
    	
    	if(!empty($transportInfo['notes'])){
    		$row->notes = core_Type::getByName('richtext')->toVerbal($transportInfo['notes']);
    	}
    	
    	if(!empty($transportInfo['stores'])){
    		if(count($transportInfo['stores']) == 1){
    			$row->storeId = store_Stores::getHyperlink($transportInfo['stores'][0], TRUE);
    		} else {
    			$row->storeId = store_Stores::getHyperlink($transportInfo['stores'][0], TRUE) . " » " . store_Stores::getHyperlink($transportInfo['stores'][1], TRUE);
    		}
    	}
    	
    	if(!empty($transportInfo['address'])){
    		$address = core_Type::getByName('varchar')->toVerbal($transportInfo['address']);
    		$row->storeId .= "<br><span class='line-detail-address'>{$address}</span>";
    	}
    	
    	if(!empty($transportInfo['weight'])){
    		$weight = core_Type::getByName('cat_type_Weight')->toVerbal($transportInfo['weight']);
    	} else {
    		$weight = "<span class='quiet'>N/A</span>";
    	}
    	
    	if(!empty($transportInfo['volume'])){
    		$volume = core_Type::getByName('cat_type_Volume')->toVerbal($transportInfo['volume']);
    	} else {
    		$volume = "<span class='quiet'>N/A</span>";
    	}
    	
    	$row->measures = "{$weight} <b>/</b> {$volume}";
    	
    	if(!empty($transportInfo['amount'])){
    		$row->collection = "<span class='cCode'>{$transportInfo['currencyId']}</span> " . core_type::getByName('double(decimals=2)')->toVerbal($transportInfo['amount']);
    	}
    	
    	$luObject = self::colorTransUnits($rec->documentLu, $rec->readyLu);
		$row->documentLu = $luObject->documentLu;
		$row->readyLu = $luObject->readyLu;
    	
    	if($mvc->haveRightFor('togglestatus', $rec) && !Mode::isReadOnly()){
    		$btnImg = ($rec->status != 'waiting') ? 'img/16/checked.png' : 'img/16/checkbox_no.png';
    		$linkTitle = ($rec->status == 'waiting') ? 'Маркиране на документа като готов' : 'Отмаркиране на документа като готов';
    		$row->status .= ht::createLink('', array($mvc, 'togglestatus', $rec->id, 'ret_url' => TRUE), FALSE, "ef_icon={$btnImg},title={$linkTitle}");
    	}
    	
    	core_RowToolbar::createIfNotExists($row->_rowTools);
    	
    	// Бутон за подготовка
    	if($mvc->haveRightFor('prepare', $rec)){
    		$url = array($mvc, 'prepare', 'id' => $rec->id, 'ret_url' => TRUE);
    		$row->_rowTools->addLink('Подготвяне', $url, array('ef_icon' => "img/16/tick-circle-frame.png", 'title' => "Ръчна подготовка на документа"));
    	}
    	
    	// Бутон за създаване на коментар
    	$commentUrl = array('doc_Comments', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE);
    	$row->_rowTools->addLink('Известяване', $commentUrl, array('ef_icon' => "img/16/comment_add.png", 'title' => "Известяване на отговорниците на документа"));
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
    	$data->listTableMvc->FNC('weight', 'cat_type_Weight');
    	$data->listTableMvc->FNC('volume', 'cat_type_Volume');
    	$data->listTableMvc->FNC('collection', 'double');
    	$data->listTableMvc->FNC('notes', 'varchar', 'tdClass=row-notes');
    }
    
    
    /**
     * Подготвя формата за добавяне на ЛЕ
     * 
     * @param core_Form $form - форма
     * @param mixed $value    - дефолтна стойност
     */
    public static function setTransUnitField(&$form, $value)
    {
    	$form->setDefault('transUnitsInput', $value);
    	
    	$units = trans_TransportUnits::getAll();
    	$form->FLD('transUnitsInput', "table(columns=unitId|quantity,captions=ЛЕ|Брой,validate=trans_LineDetails::validateTransTable)", "caption=Лог. ед.,after=lineNotes");
    	$form->setFieldTypeParams('transUnitsInput', array('unitId_opt' => array('' => '') + $units));
    }
    
    
    /**
     * Валидиране на таблица с транспортни линии
     * 
     * @param array $tableData
     * @param core_Type $Type
     * @return array
     */
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
    		if(!isset($quantities[$k])){
    			$error[] = "Попълнена ЛЕ без да има количество|*";
    			$errorFields['quantity'][$k] = "Попълнена ЛЕ без да има количество|*";
    			$errorFields['unitId'][$k] = "Попълнена ЛЕ без да има количество|*";
    		}
    	}
    	
    	foreach ($quantities as $k1 => $q1){
    		if(empty($units[$k1])){
    			$error[] = "Попълнено количество без да има ЛЕ|*";
    			$errorFields['quantity'][$k1] = "Попълнено количество без да има ЛЕ|*";
    			$errorFields['unitId'][$k1] = "Попълнено количество без да има ЛЕ|*";
    		}
    		
    		if(empty($errorFields['quantity'][$k1])){
    			if(!type_Int::isInt($q1) || $q1 < 0){
    				$error[] = "Не е въведено цяло положително число|*";
    				$errorFields['quantity'][$k1] = "Не е въведено цяло положително число|*";
    				$errorFields['unitId'][$k1] = "Не е въведено цяло положително число|*";
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
    
    
    /**
     * Смяна на състоянието на документа
     */
    public function act_ToggleStatus()
    {
    	$this->requireRightFor('togglestatus');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	$this->requireRightFor('togglestatus', $rec);
    	
    	// Смяна на състоянието
    	$newStatus = ($rec->status == 'ready') ? 'waiting' : 'ready';
    	$rec->status = $newStatus;
    	$rec->_forceStatus = TRUE;
    	$this->save($rec, 'status');
    	
    	trans_Lines::logWrite('Смяна на състояние на ред', $rec->lineId);
    	
    	return followRetUrl();
    }
    
    
    /**
     * Екшън за подготовка на документа
     */
    public function act_Prepare()
    {
    	// Проверка на права
    	$this->requireRightFor('prepare');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	$this->requireRightFor('prepare', $rec);
    	$Document = doc_Containers::getDocument($rec->containerId);
    	$transInfo = $Document->getTransportLineInfo();
    	
    	// Подготовка на формата
    	$form = cls::get('core_Form');
    	$form->title = 'Подготовка на ЛЕ на|* ' . cls::get('trans_Lines')->getFormTitleLink($rec->lineId);
    	
    	// Задаване на полетата за ЛЕ
    	if($rec->readyLu){
    		$rec->readyLu = trans_Helper::convertToUnitTableArr($rec->readyLu);
    	} else {
    		$rec->readyLu = NULL;
    	}
    	
    	$rec->readyLu = empty($rec->readyLu) ? NULL : $rec->readyLu;
    	$rec->documentLu = empty($rec->documentLu) ? NULL : $rec->documentLu;
    	self::setTransUnitField($form, $rec->readyLu);
    	if(isset($rec->documentLu)){
    		$defValue = trans_Helper::convertToUnitTableArr($rec->documentLu);
    		$form->setDefault('transUnitsInput', $defValue);
    	}
    	$form->input();
    	
    	if($form->isSubmitted()){
    		$formRec = $form->rec;
    		$rec->readyLu = trans_Helper::convertTableToNormalArr($formRec->transUnitsInput);
    		$this->save($rec, 'readyLu,status');
    		trans_Lines::logWrite('Ръчно подготвяне на ред', $rec->lineId);
    		
    		return followRetUrl();
    	}
    	
    	// Подготовка на тулбара
    	$form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
    	$form->toolbar->addBtn('Отказ', getRetUrl(),  'ef_icon = img/16/close-red.png');
    	$form->layout = $form->renderLayout();
    	
    	// Показване на оригиналния документ под формата
    	$originTpl = new ET("<div class='preview-holder {$className}'><div style='margin-top:20px; margin-bottom:-10px; padding:5px;'><b>" . tr("Оригинален документ") . "</b></div><div class='scrolling-holder'>[#DOCUMENT#]</div></div><div class='clearfix21'></div>");
    	if ($Document->haveRightFor('single')) {
    		$docHtml = $Document->getInlineDocumentBody();
    		$originTpl->append($docHtml, 'DOCUMENT');
    		$form->layout->append($originTpl);
    	}
    	
    	// Рендиране на формата
    	$tpl = $form->renderHtml();
    	$tpl = $this->renderWrapping($tpl);
    	core_Form::preventDoubleSubmission($tpl, $form);
    	
    	return $tpl;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	// Ако линията не е активна или чернова да не може да се променят редовете по нея
    	if(in_array($action, array('togglestatus', 'prepare')) && isset($rec)){
    		$state = trans_Lines::fetchField($rec->lineId, 'state');
    		
    		if(in_array($state, array('rejected', 'closed'))){
    			$requiredRoles = 'no_one';
    		}
    	}
    	
    	// Ако документа не изисква ръчно потвърждаване не може да се подготвя
    	if($action == 'prepare' && isset($rec->containerId)){
    		$Document = doc_Containers::getDocument($rec->containerId);
    		if(!$Document->requireManualCheckInTransportLine()){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    /**
     * С какво име ще се показва групата
     */
    public function renderGroupName($data, $groupId, $groupVerbal)
    {
    	if(!array_key_exists($groupId, self::$cache)){
    		
    		// Към коя група спада документа
    		$className = cls::getClassName($groupId);
    		$className = tr(self::$classGroups[$className]);
    		 
    		// Общо записи от същия вид документ
    		$total = self::count("#lineId = {$data->masterId} AND #classId = {$groupId}");
    		$totalVerbal = core_Type::getByName('int')->toVerbal($total);
    		 
    		// Общо готови записи от същия вид документ
    		$ready = self::count("#lineId = {$data->masterId} AND #status = 'ready' AND #classId = {$groupId}");
    		$readyVerbal = core_Type::getByName('int')->toVerbal($ready);
    		
    		// На всяка група се показва колко са готови от общата им бройка
    		$className .= " ({$readyVerbal}/{$totalVerbal})";
    		
    		self::$cache[$groupId] = $className;
    	}
    	
    	return self::$cache[$groupId];
    }
    
    
    /**
     * Преди извличане на записите от БД
     */
    protected static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
    	$shipClassId = store_ShipmentOrders::getClassId();
    	$receiptClassId = store_Receipts::getClassId();
    	$transferClassId = store_Transfers::getClassId();
    	$consClassId =  store_ConsignmentProtocols::getClassId();
    	
    	$data->query->XPR('orderByClassId', 'int', "(CASE #classId WHEN {$shipClassId} THEN 1 WHEN {$receiptClassId} THEN 2 WHEN {$transferClassId} THEN 3 WHEN {$consClassId} THEN 4 ELSE 5 END)");
    	$data->query->orderBy('#orderByClassId=ASC,#containerId');
    }
    
    
    /**
     * Подготовка на детайла
     */
    function prepareDetail_($data)
    {
    	// Ако ще се печата разширено се пушва в определен мод
    	if(Mode::is('printing') && Request::get('Width')){
    		Mode::push('renderHtmlInLine', TRUE);
    		$data->renderDocumentInLine = TRUE;
    	}
    	
    	parent::prepareDetail_($data);
    }
    
    
    /**
     * Рендиране на детайла
     */
    public function renderDetail_($data)
    {
    	$tpl = parent::renderDetail_($data);
    	
    	if($data->renderDocumentInLine === TRUE){
    		Mode::pop('renderHtmlInLine');
    	}
    	
    	return $tpl;
    }
    
    
    /**
     * Удобно показване на използваните логистични единици.
     * Тези които се срещат и в двата масива с еднакво количество се показват маркирани
     * 
     * @param array $documentLu - ЛЕ в документа
     * @param array $readyLu    - Подготвените ЛЕ
     * @return array $res
     * 			['documentLu'] - ЛЕ в документа
     * 			['readyLu']    - Готовите ЛЕ
     */
    public static function colorTransUnits($documentLu, $readyLu)
    {
		// Само ненулевите ЛЕ
    	$documentLu = empty($documentLu) ? array() : $documentLu;
    	$readyLu = empty($readyLu) ? array() : $readyLu;
    	$documentLu = array_filter($documentLu, function (&$d1){return !empty($d1);});
    	$readyLu = array_filter($readyLu, function (&$d2){return !empty($d2);});
    	
    	$res = (object)array('documentLu' => '', 'readyLu' => '');
    	
    	// Всички ЛЕ от документа
    	foreach ($documentLu as $unit1 => $quantity1){
    		
    		// Подготвят се за показване
    		$strPart = trans_TransportUnits::display($unit1, $quantity1);
    		
    		// Ако са налични и подготвени със същото к-во маркират се
			$className= '';
    		if(array_key_exists($unit1, $readyLu)){
				if($readyLu[$unit1] == $quantity1){
					$className= 'lu-light';
				}
    		}
			$strPart = "<div class='lu {$className}'>{$strPart}</div>";
    		$res->documentLu .= $strPart;
    	}
    	
    	foreach ($readyLu as $unit2 => $quantity2){
    		
    		// Подготвят се за показване
    		$strPart1 = trans_TransportUnits::display($unit2, $quantity2);
    		
    		// Ако са налични и подготвени със същото к-во маркират се
			$className= '';
			if(array_key_exists($unit2, $documentLu) && in_array($quantity2, $documentLu)){
    			if($documentLu[$unit2] == $quantity2){
					$className= 'lu-light';
    			}
    		}

			$strPart1 = "<div class='lu {$className}'>{$strPart1}</div>";
    		$res->readyLu .= $strPart1;
    	}
    	
    	return $res;
    }
}