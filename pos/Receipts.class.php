<?php



/**
 * Мениджър за "Бележки за продажби" 
 *
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class pos_Receipts extends core_Master {
    
    
	/**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'bgerp_DealAggregatorIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "Бележки за продажба";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_Rejected, plg_Printing, acc_plg_DocumentSummary,
    				 plg_State, bgerp_plg_Blank, pos_Wrapper, plg_Search, plg_Sorting,
                     plg_Modified';

    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Бележка за продажба";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, title=Заглавие, contragentName, total, paid, change, state , createdOn, createdBy';
    
    
    /**
	 * Детайли на бележката
	 */
	var $details = 'pos_ReceiptDetails';
	
	
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'ceo, pos';
    
    
    /**
     * Кой може да приключи бележка?
     */
    var $canClose = 'ceo, pos';
    
    
    /**
     * Кой може да променя?
     */
    var $canAdd = 'pos, ceo';
    
    
    /**
     * Кой може да плати?
     */
    var $canPay = 'pos, ceo';
    
    
    /**
     * Кой може да променя?
     */
    var $canTerminal = 'pos, ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,pos';

	
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,pos';
    
    
    /**
     * Кой може да променя?
     */
    var $canEdit = 'pos, ceo';
    
    
    /**
	 * Полета които да са достъпни след изтриване на дъска
	 */
	var $fetchFieldsBeforeDelete = 'id';
	
    
	/** 
	 *  Полета по които ще се търси
	 */
	var $searchFields = 'contragentName';
	
	
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    public $singleLayoutFile = 'pos/tpl/SingleLayoutReceipt.shtml';
    
    
    /**
     * При търсене до колко продукта да се показват в таба
     */
    public $maxSearchProducts = 20;
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,input=none');
    	$this->FLD('pointId', 'key(mvc=pos_Points, select=name)', 'caption=Точка на продажба');
    	$this->FLD('contragentName', 'varchar(255)', 'caption=Контрагент,input=none');
    	$this->FLD('contragentObjectId', 'int', 'input=none');
    	$this->FLD('contragentClass', 'key(mvc=core_Classes,select=name)', 'input=none');
    	$this->FLD('total', 'double(decimals=2)', 'caption=Общо, input=none, value=0, summary=amount');
    	$this->FLD('paid', 'double(decimals=2)', 'caption=Платено, input=none, value=0, summary=amount');
    	$this->FLD('change', 'double(decimals=2)', 'caption=Ресто, input=none, value=0, summary=amount');
    	$this->FLD('tax', 'double(decimals=2)', 'caption=Такса, input=none, value=0');
    	$this->FLD('state', 
            'enum(draft=Чернова, active=Контиран, rejected=Сторниран, closed=Затворен)', 
            'caption=Статус, input=none'
        );
    }
    
    
	/**
     * Извиква се преди изпълняването на екшън
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
    	$id = Request::get('id', 'int');
    	if($action == 'terminal' && !$id) {
    		
    		// Ако не е зададено Ид, намираме кой е последно добавената бележка
	    	$cu = core_Users::getCurrent();
    		$query = static::getQuery();
    		$posId = pos_Points::getCurrent();
	    	$query->where("#createdBy = {$cu}");
	    	$query->where("#pointId = {$posId}");
	    	$query->where("#state = 'draft'");
	    	$query->orderBy("#createdOn", "DESC");
	    	if($rec = $query->fetch()) {
	    		
	    		return Redirect(array($mvc, 'terminal', $rec->id));
	    	}
    		
	    	// Ако няма последно добавена бележка създаваме нова
    		return Redirect(array($mvc, 'new'));
    	}
    }
    
    
    /**
     *  Екшън създаващ нова бележка, и редиректващ към Единичния и изглед
     *  Добавянето на нова бележка става само през този екшън 
     */
    function act_New()
    {
    	$id = $this->createNew();
    	
    	return Redirect(array($this, 'terminal', $id));
    }
    
    
    /**
     * Създава нова чернова бележка
     */
    private function createNew()
    {
    	$rec = new stdClass();
    	$posId = pos_Points::getCurrent();
    	
    	$rec->contragentName = tr('Анонимен Клиент');
    	$rec->contragentClass = core_Classes::getId('crm_Persons');
    	$rec->contragentObjectId = pos_Points::defaultContragent($posId);
    	$rec->pointId = $posId;
    	$rec->valior = dt::now();
    	$this->requireRightFor('add', $rec);
    	
    	// Слагане на статус за потребителя
    	status_Messages::newStatus(tr("Успешно е създадена нова чернова бележка"));
    	
    	return $this->save($rec);
    }
    
    
	/**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->currency = acc_Periods::getBaseCurrencyCode($rec->createdOn);
    	
    	if($fields['-list']){
    		$row->title = "{$mvc->singleTitle} №{$row->id}";
    		$row->title = ht::createLink($row->title, array($mvc, 'single', $rec->id), NULL, "ef_icon={$mvc->singleIcon}");
    	} elseif($fields['-single']){
    		$row->iconStyle = 'background-image:url("' . sbf('img/16/view.png', '') . '");';
    		$row->header = $mvc->singleTitle . " #<b>{$mvc->abbr}{$row->id}</b> ({$row->state})";
    		$row->pointId = pos_Points::getHyperLink($rec->pointId, TRUE);
    		$row->caseId = cash_Cases::getHyperLink(pos_Points::fetchField($rec->pointId, 'caseId'), TRUE);
    		$row->storeId = store_Stores::getHyperLink(pos_Points::fetchField($rec->pointId, 'storeId'), TRUE);
    		$row->baseCurrency = acc_Periods::getBaseCurrencyCode($rec->createdOn);
    	}
    	
    	if($rec->state != 'draft'){
    		
    		// показваме датата на последната модификация на документа, ако е активиран
    		$row->valior = dt::mysql2verbal($rec->modifiedOn, "d.m.Y H:i:s");
    	}
    	
    	$cu = core_Users::fetch($rec->createdBy);
    	$row->createdBy = core_Users::recToVerbal($cu)->names;
    }

    
	/**
     * След подготовка на тулбара на единичен изглед.
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        if($mvc->haveRightFor('list')) {
    		
    		// Добавяме бутон за достъп до 'List' изгледа
    		$data->toolbar->addBtn('Всички', array($mvc, 'list', 'ret_url' => TRUE),
    							   'ef_icon=img/16/application_view_list.png, order=18');    
    	}
    	
    	if($mvc->haveRightFor('terminal', $data->rec)){
    		$data->toolbar->addBtn('Терминал', array($mvc, 'Terminal', $data->rec->id, 'ret_url' => TRUE),
    				'ef_icon=img/16/forward16.png, order=18,target=_blank');
    	}
    }
    
    
    /**
     * След подготовката на туулбара на списъчния изглед
     */
	static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if($mvc->haveRightFor('add')){
    		$addUrl = array($mvc, 'new');
    		$data->toolbar->buttons['btnAdd']->url = $addUrl;
    	}
    }
    
    
	/**
     * Извлича информацията за всички продукти които са продадени чрез
     * тази бележки, във вид подходящ за контирането
     * @param int id - ид на бележката
     * @param boolean $count - FALSE  връща масив от продуктите
     * 						   TRUE връща само броя на продуктите
     * @return array $products - Масив от продукти
     */
    public static function getProducts($id, $count = FALSE)
    {
    	expect($rec = static::fetch($id), 'Несъществуваща бележка');
    	
    	$products = array();
    	$totalQuantity = 0;
    	$currencyId = acc_Periods::getBaseCurrencyId($rec->createdOn);
    	
    	$query = pos_ReceiptDetails::getQuery();
    	$query->where("#receiptId = {$id}");
    	$query->where("#quantity != 0");
    	$query->where("#action LIKE '%sale%'");
    	
	    while($rec = $query->fetch()) {
	    	$info = cat_Products::getProductInfo($rec->productId, $rec->value);
	    	
	    	if($info->packagingRec){
	    		$packagingId = $info->packagingRec->packagingId;
	    		$quantityInPack = $info->packagingRec->quantity;
	    	} else {
	    		$packagingId = NULL;
	    		$quantityInPack = 1;
	    	}
	    	
	    	$totalQuantity += $rec->quantity;
	    	$products[] = (object) array(
	    		'classId'        => cat_Products::getClassId(),
	    		'productId'      => $rec->productId,
		    	'price'          => $rec->price,
	    	    'packagingId'    => $packagingId,
	    	    'quantityInPack' => $quantityInPack,
	    		'vatPrice'       => $rec->price * $rec->param,
	    	    'uomId' 		 => $info->productRec->measureId,
		    	'quantity'       => $rec->quantity);
	    }
	    
    	if($count){
    		return $totalQuantity;
    	}
	    
    	return $products;
    }
    
    
    /**
     * Ъпдейтване на бележката
     * 
     * @param int $id - на бележката
     */
    function updateReceipt($id)
    {
    	expect($rec = $this->fetch($id));
    	$rec->change = $rec->total = $rec->paid = 0;
    	
    	$hasClient = FALSE;
    	$dQuery = $this->pos_ReceiptDetails->getQuery();
    	$dQuery->where("#receiptId = {$id}");
    	while($dRec = $dQuery->fetch()){
    		$action = explode("|", $dRec->action);
    		switch($action[0]) {
    			case 'sale':
    				$vat = cat_Products::getVat($dRec->productId, $rec->createdOn);
    				$rec->total += $dRec->quantity * $dRec->price * (1 - $dRec->discountPercent) * (1 + $vat);
    				break;
    			case 'payment':
    				$rec->paid += $dRec->amount;
    				$rec->change += $dRec->value;
    				break;
    			case 'client':
    				
    				// "Клиент" : записваме в бележката информацията за контрагента
	    			$contragentRec = explode("|", $dRec->param);
	    			$rec->contragentObjectId = $contragentRec[0];
	    			$Class = $contragentRec[1];
	    			$rec->contragentClass = $Class::getClassId();
	    			$rec->contragentName = $Class::getTitleById($contragentRec[0]);
	    			$hasClient = TRUE;
    				break;
    		}
    	}
    	
    	// Ако няма въведен клиент от потребителя
    	if(!$hasClient){
    		$rec->contragentName = tr('Анонимен Клиент');
	    	$rec->contragentClass = core_Classes::getId('crm_Persons');
	    	$rec->contragentObjectId = pos_Points::defaultContragent($rec->pointId);
    	}
    	
    	$diff = round($rec->paid - $rec->total, 2);
    	$rec->change = ($diff <= 0) ? 0 : $diff;
    	$rec->total = round($rec->total, 2);
    	
    	$this->save($rec);
    }
    
    
    /**
     *  Филтрираме бележката
     */
	public static function on_AfterPrepareListFilter($mvc, &$data)
    {
    	$data->query->orderBy('#createdOn', 'DESC');
    }
    
    
    /**
	 * Модификация на ролите, които могат да видят избраната тема
	 */
    static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{ 
		// Само черновите бележки могат да се редактират в терминала
		if($action == 'terminal' && isset($rec)) {
			if($rec->state != 'draft'){
				$res = 'no_one';
			}
		}
		
		// Никой неможе да редактира бележка
		if($action == 'edit') {
			$res = 'no_one';
		}
		
		// Никой неможе да изтрива активирана или затворена бележка
		if($action == 'delete' && isset($rec)) {
			$res = 'no_one';
		}
		
		// Можем да контираме бележки само когато те са чернови и платената
		// сума е по-голяма или равна на общата или общата сума е <= 0
		if($action == 'close' && isset($rec->id)) {
			if($rec->total == 0 || round($rec->paid, 2) < round($rec->total, 2)) {
				$res = 'no_one';
			}
		}
		
		// Немогат да се оттеглят бележки в затворен сч. период
		if($action == 'reject'){
			$period = acc_Periods::fetchByDate($rec->valior);
			if($period->state == 'closed') {
				$res = 'no_one';
			}
		}
		
		// Можели да бъде направено плащане по бележката
		if($action == 'pay' && isset($rec)){
			if(!$rec->total || ($rec->total && $rec->paid >= $rec->total)){
				$res = 'no_one';
			}
		}
		
		// Дали може да се принтира касова бележка
		if($action == 'printreceipt'){
			$pointRec = pos_Points::fetch($rec->pointId);
			
			// Трябва точката да има драйвър, да има инсталирани драйвъри и бележката да е чернова
			if($pointRec->driver && array_key_exists($pointRec->driver, core_Classes::getOptionsByInterface('sales_FiscPrinterIntf')) && $rec->state == 'draft'){
				$res = $mvc->getRequiredRoles('close', $rec);
			} else {
				$res = 'no_one';
			}
		}
	}
    
    
    /**
     * Екшън за създаване на бележка
     */
    function act_Terminal()
    { 
    	$this->requireRightFor('terminal');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	
    	// Имаме ли достъп до терминала
    	$this->requireRightFor('terminal', $rec);
    	
    	// Лейаут на терминала
    	$tpl = getTplFromFile("pos/tpl/terminal/Layout.shtml");
    	$tpl->replace(pos_Points::getTitleById($rec->pointId), 'PAGE_TITLE');
    	Mode::set('wrapper', 'page_Empty');
    	
    	// Добавяме бележката в изгледа
    	$receiptTpl = $this->getReceipt($rec);
    	$tpl->replace($receiptTpl, 'RECEIPT');
    	
    	// Ако сме чернова, добавяме пултовете
    	if($rec->state == 'draft'){
    		
    		// Добавяне на табовете под бележката
    		$toolsTpl = $this->getTools($rec);
    		$tpl->replace($toolsTpl, 'TOOLS');
    		
    		// Добавяне на табовете показващи се в широк изглед отстрани
	    	if(!Mode::is('screenMode', 'narrow')){
	    		$DraftsUrl = toUrl(array('pos_Receipts', 'showDrafts', $rec->id), 'absolute');
	    		$tab = "<li class='active'><a href='#tools-choose'>Избор</a></li><li><a href='#tools-search'>Търсене</a></li><li><a href='#tools-drafts' data-url='{$DraftsUrl}'>Бележки</a></li>";
	    		$tpl->replace($this->getSelectFavourites(), 'CHOOSE_DIV_WIDE');
	    		$tpl->append($this->renderChooseTab($id), 'SEARCH_DIV_WIDE');
	    		$tpl->append($this->renderDraftsTab($id), 'DRAFTS_WIDE');
	    		
	    		$tpl->replace($tab, 'TABS_WIDE');
	    	}
    	}
    	
    	// Вкарване на css и js файлове
    	$this->pushFiles($tpl);
    	
    	return $tpl;
    }
    
    
    /**
     * Вкарване на css и js файлове
     */
    private function pushFiles(&$tpl)
    {
	    $tpl->push('pos/tpl/css/styles.css', 'CSS');
	    $tpl->push('pos/js/scripts.js', 'JS');
		jquery_Jquery::run($tpl, "posActions();");
	    
	    $conf = core_Packs::getConfig('pos');
        $ThemeClass = cls::get($conf->POS_PRODUCTS_DEFAULT_THEME);
        $tpl->push($ThemeClass->getStyleFile(), 'CSS');
    }
    
    
    /**
     * Подготовка и рендиране на бележка
     * 
     * @param int $id - ид на бележка
     * @return core_ET $tpl - шаблона
     */
    public function getReceipt($id)
    {
    	expect($rec = $this->fetchRec($id));
    	
    	$data = new stdClass();
    	$data->rec = $rec;
    	$this->prepareReceipt($data);
    	$tpl = $this->renderReceipt($data);
    	
    	return $tpl;
    }
    
    
    /**
     * Подготовка на бележка
     */
    private function prepareReceipt(&$data)
    {
    	$data->row = $this->recToverbal($data->rec);
    	$data->details = $this->pos_ReceiptDetails->prepareReceiptDetails($data->rec->id);
    }
    
    
    /**
     * Подготовка и рендиране на бележка
     * 
     * @return core_ET $tpl - шаблон
     */
    private function renderReceipt($data)
    {
    	// Слагане на мастър данните
    	$tpl = getTplFromFile('pos/tpl/terminal/Receipt.shtml');
    	$tpl->placeObject($data->row);
    	
    	// Слагане на детайлите на бележката
    	$detailsTpl = $this->pos_ReceiptDetails->renderReceiptDetail($data->details);
    	$tpl->append($detailsTpl, 'DETAILS');
    	
    	return $tpl;
    }
    
    
    /**
     * Рендиране на табовете под бележката
     * 
     * @param int $id - ид на бележка
     * @return core_ET $tpl - шаблон
     */
	public function getTools($id)
    {
    	$tpl = new ET("");
    	expect($rec = $this->fetchRec($id));
    	
    	// Рендиране на пулта
    	$tab = "<li class='active'><a href='#tools-form'>Пулт</a></li><li><a href='#tools-payment'>Плащане</a></li>";
    	$tpl->append($this->renderToolsTab($id), 'TAB_TOOLS');
    	
    	// Ако сме в тесен режим
    	if(Mode::is('screenMode', 'narrow')){
    		
    		// Добавяне на таба с бързите бутони
    		$tpl->append($this->getSelectFavourites(), 'CHOOSE_DIV');
    		
    		$DraftsUrl = toUrl(array('pos_Receipts', 'showDrafts', $rec->id), 'absolute');
    		
    		// Добавяне на таба с избор
    		$tpl->append($this->renderChooseTab($id), 'SEARCH_DIV');
    		$tab .= "<li><a href='#tools-choose'>Избор</a></li><li><a href='#tools-search'>Търсене</a></li><li><a href='#tools-drafts'>Чернови</a></li>";
    	
    		// Добавяне на таба с черновите
    		$tpl->append($this->renderDraftsTab($id), 'DRAFTS');
    	}
    	
    	// Добавяне на таба за плащане
    	$tpl->append($this->renderPaymentTab($id), 'PAYMENTS');
    	$tpl->append($tab, 'TABS');
    	
   		return $tpl;
    }
    
    
    /**
     * Рендира бързите бутони
     * @return core_ET $block - шаблон
     */
    public function getSelectFavourites()
    {
    	$products = pos_Favourites::prepareProducts();
    	$tpl = getTplFromFile('pos/tpl/terminal/ToolsForm.shtml')->getBlock('CHOOSE_DIV');
    	
    	if($products->arr) {
    		$tpl->append(pos_Favourites::renderPosProducts($products), 'CHOOSE_DIV');
	    }
    	
	    return $tpl;
    }
    
	
    /**
     * Рендиране на таба с пулта
     * 
     * @param int $id - ид на бележка
     * @return core_ET $block - шаблон
     */
	public function renderToolsTab($id)
    {
    	expect($rec = $this->fetchRec($id));
    	$block = getTplFromFile('pos/tpl/terminal/ToolsForm.shtml')->getBlock('TAB_TOOLS');
    	
    	// Ако можем да добавяме към бележката
    	if($this->pos_ReceiptDetails->haveRightFor('add', (object)array('receiptId' => $rec->id))){
	    	$modQUrl = toUrl(array('pos_ReceiptDetails', 'setQuantity'), 'local');
	    	$discUrl = toUrl(array('pos_ReceiptDetails', 'setDiscount'), 'local');
	    	$addClient = toUrl(array('pos_ReceiptDetails', 'addClientByCard'), 'local');
	    	$addUrl = toUrl(array('pos_Receipts', 'addProduct', $rec->id), 'local');
	    	$absUrl = toUrl(array('pos_Receipts', 'addProduct', $rec->id), 'absolute');
	    	
    	} else {
    		$disClass = 'disabledBtn';
    		$disabled = 'disabled';
    	}
    	
    	// Ако има последно добавен продукт, записваме ид-то на записа в скрито поле
    	if($lastRow = Mode::get('lastAdded')){
    		$value = $lastRow;
    		Mode::setPermanent('lastAdded', NULL);
    	}
    	$browserInfo = Mode::get('getUserAgent');
    	if(strrpos($browserInfo, "Android") !== FALSE){
    		$htmlScan = "<input type='button' class='webScan {$disClass}' {$disabled} id='webScan' name='scan' onclick=\"document.location = 'http://zxing.appspot.com/scan?ret={$absUrl}?ean={CODE}'\" value='Scan' />";
    		$block->append($htmlScan, 'FIRST_TOOLS_ROW');
    	}
    	
    	$block->append(ht::createElement('input', array('name' => 'ean', 'type' => 'text', 'style' => 'text-align:right')), 'INPUT_FLD');
    	$block->append(ht::createElement('input', array('name' => 'receiptId', 'type' => 'hidden', 'value' => $rec->id)), 'INPUT_FLD');
    	$block->append(ht::createElement('input', array('name' => 'rowId', 'type' => 'hidden', 'value' => $value)), 'INPUT_FLD');
    	$block->append(ht::createFnBtn('Код', NULL, NULL, array('class' => "{$disClass} buttonForm", 'id' => 'addProductBtn', 'data-url' => $addUrl)), 'FIRST_TOOLS_ROW');
    	$block->append("<br />" . ht::createFnBtn('К-во', NULL, NULL, array('class' => "{$disClass} buttonForm tools-modify", 'data-url' => $modQUrl, 'title' => 'Промени количество')), 'FIRST_TOOLS_ROW');
    	$block->append("<br />" . ht::createFnBtn('Отстъпка %', NULL, NULL, array('class' => "{$disClass} buttonForm tools-modify", 'data-url' => $discUrl, 'title' => 'Задай отстъпка')), 'FIRST_TOOLS_ROW');
    	$block->append("<br />" . ht::createFnBtn('Кл. карта', NULL, NULL, array('class' => "{$disClass} buttonForm", 'id' => 'tools-addclient', 'data-url' => $addClient, 'title' => 'Въведи клиентска карта')), 'FIRST_TOOLS_ROW');
    	
    	return $block;
    }
    
    
    /**
     * Рендиране на таба за търсене на продукт
     * 
     * @param int $id -ид на бележка
     * @return core_ET $block - шаблон
     */
    public function renderChooseTab($id)
    {
    	expect($rec = $this->fetchRec($id));
    	$block = getTplFromFile('pos/tpl/terminal/ToolsForm.shtml')->getBlock('SEARCH_DIV');
    	if(!Mode::is('screenMode', 'narrow')){
    		$keyboardsTpl = getTplFromFile('pos/tpl/terminal/Keyboards.shtml');
    		$block->replace($keyboardsTpl, 'KEYBOARDS');
    	}
    	
    	$searchUrl = toUrl(array('pos_Receipts', 'getSearchResults'), 'local');
    	$inpFld = ht::createTextInput('select-input-pos', '', array('id' => 'select-input-pos', 'data-url' => $searchUrl));
    	$block->replace($inpFld, 'INPUT_SEARCH');
    	
    	return $block;
    }
    
    
    /**
     * Екшън за показване на черновите бележки
     */
 	function act_ShowDrafts()
    {
    	$this->requireRightFor('terminal');
    	expect($id = Request::get('id'));
    	expect($rec = $this->fetch($id));
    	$this->requireRightFor('terminal', $rec);
    	
    	Mode::set('wrapper', 'page_Empty');
    	
    	return $this->renderDraftsTab($id)->getContent() . '<div class="clearfix21"></div>';
    } 
    
    
    /**
     * Рендиране на таба с черновите
     * 
     * @param int $id -ид на бележка
     * @return core_ET $block - шаблон
     */
    public function renderDraftsTab($id)
    {
    	$rec = $this->fetchRec($id);
    	$block = getTplFromFile('pos/tpl/terminal/ToolsForm.shtml')->getBlock('DRAFTS');
    	$pointId = pos_Points::getCurrent('id');
    	$now = dt::today();
    	
    	// Намираме всички чернови бележки и ги добавяме като линк
    	$query = $this->getQuery();
    	$query->where("#state = 'draft' AND #pointId = '{$pointId}' AND #id != {$rec->id}");
    	while($rec = $query->fetch()){
    		$date = dt::mysql2verbal($rec->createdOn, $mask = "H:i");
    		$between = dt::daysBetween($now, $rec->valior);
    		$between = ($between != 0) ? " <span>-$between</span>" : NULL;
    		
    		$row = ht::createLink("№{$rec->id} <br> {$date}$between", array('pos_Receipts', 'Terminal', $rec->id), NULL, array('class'=>'pos-notes'));
    		$block->append($row);
    	}
    	
    	if(!$query->count()){
    		$block->append("<div class='pos-no-result'>" . tr('Няма чернови') . "</div>");
    	}
    	
    	return $block;
    }
    
    
    /**
     * Рендиране на таба за плащане
     * 
     * @param int $id -ид на бележка
     */
	public function renderPaymentTab($id)
    {
    	expect($rec = $this->fetchRec($id));
    	$block = getTplFromFile('pos/tpl/terminal/ToolsForm.shtml')->getBlock('PAYMENTS_BLOCK');

    	if($this->haveRightFor('pay', $rec)){
    		$payUrl = toUrl(array('pos_ReceiptDetails', 'makePayment'), 'local');
    	}
    	
    	$block->append(ht::createElement('input', array('name' => 'paysum', 'type' => 'text', 'style' => 'text-align:right;float:left;')) . "<br />", 'INPUT_PAYMENT');
    	
    	// Показваме всички активни методи за плащания
    	$disClass = ($payUrl) ? '' : 'disabledBtn';
    	$payments = pos_Payments::fetchSelected();
	    foreach($payments as $payment) {
	    	$attr = array('class' => "{$disClass} actionBtn paymentBtn", 'data-type' => "$payment->id", 'data-url' => $payUrl);
	    	$block->append(ht::createFnBtn($payment->title, '', '', $attr), 'PAYMENT_TYPE');
	    }
	    
	    // Ако може да се издаде касова бележка, активираме бутона
	    if($this->haveRightFor('printReceipt', $rec)){
	    	$recUrl = array($this, 'printReceipt', $rec->id);
	    }
	    $disClass = ($recUrl) ? '' : 'disabledBtn';
	    $block->append(ht::createBtn('Касов бон', $recUrl, NULL, NULL, array('class' => "{$disClass} actionBtn", 'target' => 'iframe_a', 'title' => 'Издай касова бележка')), 'CLOSE_BTNS');
	    
	    // Търсим бутон "Контиране" в тулбара на мастъра, добавен от acc_plg_Contable
	    if ($this->haveRightFor('close', $rec)) {
	    	$contoUrl = array('pos_Receipts', 'close', $rec->id);
	    	$hint = tr("Приключи продажбата");
	    	$hintInv = tr("Приключи и издай фактура");
	    	
	        if($client = $this->pos_ReceiptDetails->hasClient($rec->id)){
	        	$contragentClass = cls::get($client->class);
    			$folderId = $contragentClass->forceCoverAndFolder($client->id, FALSE);
    			if(doc_Folders::haveRightToFolder($folderId)){
    				$confInvUrl = $contoUrl;
	        		$confInvUrl['makeInvoice'] = TRUE;
    			} else {
    				$hintInv = tr("Не може да издадете фактура, защото нямате достъп до папката на клиента");
    			}
	        }
	    } else {
	    	$hint = $hintInv = tr("Не може да приключите бележката, докато не е платена");
	    }
	    
	    $disClass = ($contoUrl) ? '' : 'disabledBtn';
	    $block->append(ht::createBtn('Приключи', $contoUrl, '', '', array('class' => "{$disClass} different-btns", 'id' => 'btn-close','title' => $hint)), 'CLOSE_BTNS');
	    $disClass = ($confInvUrl) ? '' : 'disabledBtn';
	    $block->append(ht::createBtn('Фактурирай', $confInvUrl, '', '', array('class' => "{$disClass} different-btns", 'id' => 'btn-inv', 'title' => $hintInv)), 'CLOSE_BTNS');
    	
	    return $block;
    }
    
    
    /**
     * Екшън за принтиране на касова белжка
     */
    public function act_printReceipt()
    {
    	expect(haveRole('pos, ceo'));
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	$this->requireRightFor('printReceipt', $rec);
    	
    	$Driver = cls::get(pos_Points::fetchField($rec->pointId, 'driver'));
    	$driverData = $this->getFiscPrinterData($rec);
    	
    	return $Driver->createFile($id);
    }
    
    
    /**
     * Имплементиране на интерфейсен метод ( @see acc_TransactionSourceIntf )
     */
    static function getLink($id)
    {
    	return static::recToVerbal(static::fetchRec($id), 'id,title,-list')->title;
    }
    
    
	/**
     * Имплементация на @link bgerp_DealAggregatorIntf::getAggregateDealInfo()
     * 
     * @param int|object $id
     * @return bgerp_iface_DealResponse
     * @see bgerp_DealAggregatorIntf::getAggregateDealInfo()
     */
    public function getAggregateDealInfo($id)
    {
        $rec = self::fetchRec($id);
        $products = static::getProducts($id);
        $currencyId = acc_Periods::getBaseCurrencyCode($rec->valior);
        $posRec = pos_Points::fetch($rec->pointId);
        
        $result = new bgerp_iface_DealAggregator();
        
        
        $result->set('dealType', sales_Sales::AGGREGATOR_TYPE);
        $result->set('amount', $rec->total);
        $result->set('currency', $currencyId);
        $result->set('vatType', 'yes');
        $result->set('agreedValior', $rec->valior);
        $result->set('storeId', $posRec->storeId);
        $result->set('paymentMethodId', cond_PaymentMethods::fetchField("#name = 'COD'", 'id'));
        $result->set('caseId', $posRec->caseId);
        $result->set('amountPaid', $rec->total);
        $result->set('deliveryAmount', $rec->total);
         
        $productManId = cat_Products::getClassId();
        
        foreach ($products as $pr) {
            $p = new bgerp_iface_DealProduct();
            
            $p->classId     = $productManId;
            $p->productId   = $pr->productId;
            $p->packagingId = $pr->packagingId;
            $p->discount    = $dRec->discountPercent;
            $p->quantity    = $pr->quantity;
            $p->price       = $pr->price;
            $p->uomId       = $pr->uomId;
            
            $result->push('products', $p);
            $result->push('shippedProducts', $p);
        }
        
        return $result;
    }
    
    
    /**
     * Метод по подразбиране на canActivate
     */
    public static function canActivate($rec)
    {
    	if(empty($rec->id) && $rec->state != 'draft' && ($rec->total == 0 || $rec->paid < $rec->total)) {
			return FALSE;
		} else {
			return TRUE;
		}
    }
    
    
    /**
     * Екшън добавящ продукт в бележката
     */
    function act_addProduct()
    {
    	$this->pos_ReceiptDetails->requireRightFor('add');
    	
    	// Трябва да има такава бележка
    	if(!$receiptId = Request::get('id', 'int')) {
    		if(!$receiptId = Request::get('receiptId', 'int')){
    			return $this->pos_ReceiptDetails->returnError($receiptId);
    		}
    	}
    	 
    	if($this->fetchField($receiptId, 'paid')){
    		core_Statuses::newStatus(tr('|Не може да се добавя продукт, ако има направено плащане|*!'), 'error');
    		return $this->pos_ReceiptDetails->returnError($receiptId);
    	}
    	 
    	// Трябва да можем да добавяме към нея
    	$this->pos_ReceiptDetails->requireRightFor('add', (object)array('receiptId' => $receiptId));
    	 
    	// Запис на продукта
    	$rec = new stdClass();
    	$rec->receiptId = $receiptId;
    	$rec->action = 'sale|code';
    	$rec->quantity = 1;
    	 
    	// Ако е зададен код на продукта
    	if($ean = Request::get('ean')) {
    		$rec->ean = $ean;
    	}
    	 
    	// Ако е зададено ид на продукта
    	if($productId = Request::get('productId', 'int')) {
    		$rec->productId  = $productId;
    	}
    	 
    	// Трябва да е подаден код или ид на продукт
    	if(!$rec->productId && !$rec->ean){
    		core_Statuses::newStatus(tr('|Не е избран артикул|*!'), 'error');
    		return $this->pos_ReceiptDetails->returnError($receiptId);
    	}
    	 
    	// Намираме нужната информация за продукта
    	$this->pos_ReceiptDetails->getProductInfo($rec);
    	
    	// Ако не е намерен продукт
    	if(!$rec->productId) {
    		core_Statuses::newStatus(tr('|Няма такъв продукт в системата, или той не е продаваем|*!'), 'error');
    		return $this->pos_ReceiptDetails->returnError($receiptId);
    	}
    
    	// Ако няма цена
    	if(!$rec->price) {
    		core_Statuses::newStatus(tr('|Артикулът няма цена|*!'), 'error');
    		return $this->pos_ReceiptDetails->returnError($receiptId);
    	}
    
    	// Намираме дали този проект го има въведен
    	$sameProduct = $this->pos_ReceiptDetails->findSale($rec->productId, $rec->receiptId, $rec->value);
    	if($sameProduct) {
    
    		// Ако цената и опаковката му е същата като на текущия продукт,
    		// не добавяме нов запис а ъпдейтваме стария
    		$newQuantity = $rec->quantity + $sameProduct->quantity;
    		$rec->quantity = $newQuantity;
    		$rec->amount += $sameProduct->amount;
    		$rec->id = $sameProduct->id;
    	}
    	
    	// Добавяне/обновяване на продукта
    	if($this->pos_ReceiptDetails->save($rec)){
    		if(Mode::is('screenMode', 'wide')){
    			$msg = tr('Добавен/а') . " " . cat_Products::getTitleById($rec->productId);
    			core_Statuses::newStatus($msg);
    		}
    
    		return $this->pos_ReceiptDetails->returnResponse($rec->receiptId);
    	} else {
    		core_Statuses::newStatus(tr('|Проблем при добавяне на артикул|*!'), 'error');
    	}
    	
    	return $this->pos_ReceiptDetails->returnError($receiptId);
    }
    
    
    /**
     * Активира документа и ако е зададено пренасочва към създаването на нова фактура
     */
    function act_Close()
    {
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	expect($rec->state == 'draft');
    	$makeInvoice = Request::get('makeInvoice', 'int');
    	
    	$this->requireRightFor('close', $rec);
    	
    	$rec->state = 'active';
    	if($this->save($rec)){
    		
    		// Обновяваме складовите наличности
    		pos_Stocks::updateStocks($rec->id);
    	}
    	
    	// Ако не трябва да се прави фактура редирект към новата бележка
    	if(empty($makeInvoice)){
    		
    		// Създаване на нова чернова бележка
    		return redirect(array($this, 'new'));
    	}
    	
    	// Форсиране на папката на клиента
    	expect($client = $this->pos_ReceiptDetails->hasClient($id));
    	$contragentClass = cls::get($client->class);
    	$folderId = $contragentClass->forceCoverAndFolder($client->id);
    	
    	// Създаване на нова чернова бележка
    	$this->createNew();
    	
    	// Редирект към създаването на нова фактура;
    	return redirect(array('sales_Invoices', 'add', 'folderId' => $folderId, 'docType' => $this->getClassId(), 'docId' => $id));
    }
    
    
	/**
     * Връща таблицата с намерените резултати за търсене
     */
	function act_getSearchResults()
    {
    	$this->requireRightFor('terminal');
    	
    	if($searchString = Request::get('searchString')){
    		if(!$id = Request::get('receiptId')) return array();
    		
	    	if(!$rec = $this->fetch($id)) return array();
	    	
	    	$this->requireRightFor('terminal', $rec);
	    	$html = $this->getResultsTable($searchString, $rec);
	    } else {
    		$html = ' ';
    	}
    	
    	if(Request::get('ajax_mode')){
    		// Ще реплесйнем и добавим таблицата с резултатите
    		$resObj = new stdClass();
    		$resObj->func = "html";
    		$resObj->arg = array('id' => 'pos-search-result-table', 'html' => $html, 'replace' => TRUE);
    		
    		return array($resObj);
    		
    	} else {
    		Redirect(array($this, 'terminal', $rec->id));
    	}
    }
    
    
    /**
     * Връща таблицата с продукти отговарящи на определен стринг
     */
    public function getResultsTable($string, $rec)
    {
    	$searchString = plg_Search::normalizeText($string);
	    $data = new stdClass();
	    $data->rec = $rec;
	    $data->searchString = $searchString;
	    $data->baseCurrency = acc_Periods::getBaseCurrencyCode();
	    
	    $this->prepareSearchData($data);
	    	
	    return $this->renderSearchResultTable($data);
    }
    
    
    /**
     * Подготвя данните от резултатите за търсене
     */
    private function prepareSearchData(&$data)
    {
    	$data->rows = array();
    	$count = 0;
    	$conf = core_Packs::getConfig('pos');
    	$data->showParams = $conf->POS_RESULT_PRODUCT_PARAMS;
    	
    	// Намираме всички продаваеми продукти
    	$sellable = cat_Products::getByProperty('canSell');
    	if(!count($sellable)) return;
    	
    	$Policy = cls::get('price_ListToCustomers');
    	$Products = cls::get('cat_Products');
    	foreach ($sellable as $id => $name){
    		
    		// Показваме само до определена бройка
    		if($count >= $this->maxSearchProducts) break;
    		
    		// Ако продукта не отговаря на търсения стринг, го пропускаме
    		if(!$pRec = $Products->fetch(array("#id = {$id} AND #searchKeywords LIKE '%[#1#]%'", $data->searchString))) continue;
    		
    		$price = $Policy->getPriceInfo($data->rec->contragentClass, $data->rec->contragentObjectId, $id, $Products->getClassId(), NULL, NULL, $data->rec->createdOn);
    		
    		// Ако няма цена също го пропускаме
    		if(empty($price->price)) continue;
    		
    		$obj = (object)array('productId' => $id, 
    							 'measureId' => $pRec->measureId,
    							 'price'     => $price->price, 
    							 'photo'     => $pRec->photo,
    							 'vat'	     => $Products->getVat($id));
    		
    		$pInfo = cat_Products:: getProductInfo($id);
    		if(isset($pInfo->meta['canStore'])){
    			$obj->stock = pos_Stocks::getQuantity($id, $data->rec->pointId);
    		}
    		
    		// Обръщаме реда във вербален вид
    		$data->rows[$id] = $this->getVerbalSearchresult($obj, $data);
    		
    		$count++;
    	}
    }
    
    
    /**
     * Връща вербалното представяне на един ред от резултатите за търсене
     */
    private function getVerbalSearchResult($obj, &$data)
    {
    	$Double = cls::get('type_Double');
    	$Double->params['decimals'] = 2;
    	$row = new stdClass();
    	
    	$row->price = $Double->toVerbal($obj->price * (1 + $obj->vat));
    	$row->price .= "&nbsp;<span class='cCode'>{$data->baseCurrency}</span>";
    	$row->stock = $Double->toVerbal($obj->stock);
    	
    	$obj->receiptId = $data->rec->id;
    	if($this->pos_ReceiptDetails->haveRightFor('add', $obj)){
    		$addUrl = toUrl(array('pos_Receipts', 'addProduct', $data->rec->id), 'local');
    	}
    	$row->productId = "<span class='pos-add-res-btn' data-url='{$addUrl}' data-productId='{$obj->productId}'>" . cat_Products::getTitleById($obj->productId) . "</span>";
    	if($data->showParams){
    		$params = keylist::toArray($data->showParams);
    		$values = NULL;
    		foreach ($params as $pId){
    			if($vRec = cat_products_Params::fetch("#productId = {$obj->productId} AND #paramId = {$pId}")){
    				$row->productId .= " &nbsp;" . cat_products_Params::recToVerbal($vRec, 'paramValue')->paramValue;
    			}
    		}
    	}
    	
    	$row->productId = ht::createLinkRef($row->productId, array('cat_Products', 'single', $obj->productId), NULL, array('target'=>'_blank', 'class'=>'singleProd'));
    	
    	if($obj->stock < 0){
    		$row->stock = "<span style='color:red'>$row->stock</span>";	
    	}
    	
    	if($rec->stock){
    		$row->stock .= "&nbsp;" . cat_UoM::getShortName($obj->measureId);
    	}
    	
    	if($obj->photo && !Mode::is('screenMode', 'narrow')) {
    		$thumb = new img_Thumb($obj->photo, 64, 64);
    		$arr = array();
    		$row->photo = "<div class='pos-search-pic'>" . $thumb->createImg($arr) . "</div>";
    		$data->showImg = TRUE;
    	}
    	
    	return $row;
    }
    
    
    /**
     * Рендира таблицата с резултатите от търсенето
     */
    private function renderSearchResultTable(&$data)
    {
    	$fSet = cls::get('core_FieldSet');
    	$fSet->FNC('photo', 'varchar', 'tdClass=pos-photo-field');
    	$fSet->FNC('productId', 'varchar', 'tdClass=pos-product-field');
    	$fSet->FNC('price', 'double', 'tdClass=pos-price-field');
    	$fSet->FNC('stock', 'double', 'tdClass=pos-stock-field');
    	
    	$table = cls::get('core_TableView', array('mvc' => $fSet));
    	$fields = arr::make('photo=Снимка,productId=Продукт,price=Цена,stock=Наличност,singleBtn=Добави');
    	if(!$data->showImg){
    		unset($fields['photo']);
    	}
    	
    	return $table->get($data->rows, $fields)->getContent();
    }
    
    
    private function getFiscPrinterData($id)
    {
    	$rec = $this->fetchRec($id);
    	
    	$payments = $products = array();
    	$query = pos_ReceiptDetails::getQuery();
    	$query->where("#receiptId = '{$rec->id}'");
    	
    	// Разделяме детайлите на плащания и продажби
    	while($rec = $query->fetch()){
    		if(strpos($rec->action, 'sale') !== false){
    			$nRec['id'] = $rec->productId;
    			$nRec['managerId'] = cat_Products::getClassId();
    			$nRec['quantity'] = $rec->quantity;
    			if($rec->discountPercent){
    				$nRec['discount'] = (round($rec->discountPercent, 2) * 100) . "%";
    			}
    			$pInfo = cls::get('cat_Products')->getProductInfo($rec->productId);
    			
    			$nRec['measure'] = ($rec->value) ? cat_Packagings::getTitleById($rec->value) : cat_UoM::getShortName($pInfo->productRec->measureId);
    			$nRec['vat'] = $rec->param;
    			$nRec['price'] = $rec->price;
    			
    			$products[] = (object)$nRec;
    		} elseif(strpos($rec->action, 'payment') !== false) {
    			
    			bp($rec);
    			$payments[] = $rec;
    		}
    	}
    	
    	return (object)array('products' => $products, 'payments' => $payments);
    }
}
