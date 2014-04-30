<?php



/**
 * Мениджър за "Бележки за продажби" 
 *
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class pos_Receipts extends core_Master {
    
    
	/**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'bgerp_DealAggregatorIntf, acc_TransactionSourceIntf=pos_TransactionSourceImpl';
    
    
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
    var $listFields = 'id, title=Заглавие, contragentName, total, paid, change, productCount, state , createdOn, createdBy';
    
    
    /**
	 * Детайли на бележката
	 */
	var $details = 'pos_ReceiptDetails';
	
	
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'ceo, pos';
    
    
    /**
     * Кой може да променя?
     */
    var $canAdd = 'pos, ceo';
    
    
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
        $this->FLD('productCount', 'int', 'caption=Продукти, input=none, value=0,summary=quantity');
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
	    	$query->where("#createdBy = {$cu}");
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
    	}elseif($fields['-single']){
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
    	$policyId = price_ListToCustomers::getClassId();
    	
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
	    		'classId' => cat_Products::getClassId(),
	    		'productId' => $rec->productId,
		    	'price' => $rec->price,
	    	    'packagingId' => $packagingId,
	    	    'quantityInPack' => $quantityInPack,
	    		'vatPrice' => $rec->price * $rec->param,
	    	    'uomId' => $info->productRec->measureId,
		    	'quantity' => $rec->quantity);
	    }
	    
    	if($count){
    		return $totalQuantity;
    	}
	    
    	return $products;
    }
    
    
    /**
     * Ъпдейтване на бележката
     * @param int $id - на бележката
     */
    function updateReceipt($id)
    {
    	expect($rec = $this->fetch($id));
    	$rec->change = $rec->total = $rec->paid =  $rec->productCount = 0;
    	
    	$hasClient = FALSE;
    	$dQuery = $this->pos_ReceiptDetails->getQuery();
    	$dQuery->where("#receiptId = {$id}");
    	while($dRec = $dQuery->fetch()){
    		$action = explode("|", $dRec->action);
    		switch($action[0]) {
    			case 'sale':
    				$vat = cat_Products::getVat($dRec->productId, $rec->createdOn);
    				$rec->total += $dRec->quantity * $dRec->price * (1 - $dRec->discountPercent) * (1 + $vat);
    				$rec->productCount += $dRec->quantity;
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
    	
    	$rec->change = ($rec->change < 0 || $rec->paid < $rec->total) ? 0 :  $rec->change;
    	
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
			if($rec->total == 0 || $rec->paid < $rec->total) {
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
	}
    
    
    /**
     * Екшън за създаване на бележка
     */
    function act_Terminal()
    {   
    	$this->requireRightFor('single');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	
    	// Имамели достъп до сингъла на бележката
    	$this->requireRightFor('single', $rec);
    	
    	// Лейаут на терминала
    	$tpl = getTplFromFile("pos/tpl/terminal/Layout.shtml");
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
	    		$tab = "<li class='active'><a href='#tools-choose'>Избор</a></li><li><a href='#tools-search'>Търсене</a></li>";
	    		$tpl->replace($this->getSelectFavourites(), 'CHOOSE_DIV_WIDE');
	    		$tpl->append($this->renderChooseTab($id), 'SEARCH_DIV_WIDE');
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
    	jquery_Jquery::enable($tpl);
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
     */
	public function getTools($id)
    {
    	$tpl = new ET("");
    	expect($rec = $this->fetchRec($id));
    	
    	// Рендиране на пулта
    	$tab = "<li class='active'><a href='#tools-form'>Пулт</a></li>";
    	$tpl->append($this->renderToolsTab($id), 'TAB_TOOLS');
    	
    	// Ако сме в тесен режим
    	if(Mode::is('screenMode', 'narrow')){
    		
    		// Добавяне на таба с бързите бутони
    		$tpl->append($this->getSelectFavourites(), 'CHOOSE_DIV');
    		
    		// Добавяне на таба с избор
    		$tpl->append($this->renderChooseTab($id), 'SEARCH_DIV');
    		$tab .= "<li><a href='#tools-choose'>Избор</a></li><li><a href='#tools-search'>Търсене</a></li>";
    	}
    	
    	// Добавяне на таба за плащане
    	$tpl->append($this->renderPaymentTab($id), 'PAYMENTS');
    	
    	// Добавяне на заглавията на табовете
    	$tab .= "<li><a href='#tools-payment'>Плащане</a></li>";
    	$tpl->append($tab, 'TABS');
    	
   		return $tpl;
    }
    
    
    /**
     * Рендира бързите бутони
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
     */
	public function renderToolsTab($id)
    {
    	expect($rec = $this->fetchRec($id));
    	$block = getTplFromFile('pos/tpl/terminal/ToolsForm.shtml')->getBlock('TAB_TOOLS');
    	
    	$block->replace(toUrl(array('pos_ReceiptDetails', 'addProduct'), 'local'), 'ACT1');
    	$block->append(ht::createElement('input', array('name' => 'ean', 'type' => 'text', 'style' => 'text-align:right')), 'INPUT_FLD');
    	$block->append(ht::createElement('input', array('name' => 'receiptId', 'type' => 'hidden', 'value' => $rec->id)), 'INPUT_FLD');
    	$block->append(ht::createElement('input', array('name' => 'rowId', 'type' => 'hidden', 'size' => '4em')), 'INPUT_FLD');
    	
    	$modQUrl = toUrl(array('pos_ReceiptDetails', 'setQuantity'), 'local');
    	$discUrl = toUrl(array('pos_ReceiptDetails', 'setDiscount'), 'local');
    	$addClient = toUrl(array('pos_ReceiptDetails', 'addClientByCard'), 'local');
    	$block->append(ht::createSbBtn('Код', 'default', NULL, NULL, array('class' => 'buttonForm')), 'FIRST_TOOLS_ROW');
    	$block->append("<br />" . ht::createFnBtn('К-во', NULL, NULL, array('class' => 'buttonForm tools-modify', 'data-url' => $modQUrl, 'title' => 'Промени количество')), 'FIRST_TOOLS_ROW');
    	$block->append("<br />" . ht::createFnBtn('Отстъпка %', NULL, NULL, array('class' => 'buttonForm tools-modify', 'data-url' => $discUrl, 'title' => 'Задай отстъпка')), 'FIRST_TOOLS_ROW');
    	$block->append("<br />" . ht::createFnBtn('Кл. карта', NULL, NULL, array('class' => 'buttonForm', 'id' => 'tools-addclient', 'data-url' => $addClient, 'title' => 'Въведи клиентска карта')), 'FIRST_TOOLS_ROW');
    	
    	return $block;
    }
    
    
    /**
     * Рендиране на таба за търсене на продукт
     * 
     * @param int $id -ид на бележка
     */
    public function renderChooseTab($id)
    {
    	expect($rec = $this->fetchRec($id));
    	$block = getTplFromFile('pos/tpl/terminal/ToolsForm.shtml')->getBlock('SEARCH_DIV');
    	
    	$formChoose = cls::get('core_Form');
    	$formChoose->view = 'horizontal';
    	$formChoose->formAttr['id'] = 'searchForm';
    	$formChoose->method = 'POST';
    	$formChoose->action = toUrl(array('pos_ReceiptDetails', 'addProduct'), 'local');
    	$formChoose->FLD('productId', 'key(mvc=cat_Products,select=name)', 'input,placeholder=Продукт');
    	$formChoose->setOptions('productId', cat_Products::getByProperty('canSell'));
    	$formChoose->FLD('receiptId', 'key(mvc=pos_Receipts)', 'input=hidden');
	    $formChoose->rec->receiptId = $rec->id;
    	$formChoose->toolbar->addSbBtn('Търси', 'save', 'ef_icon = img/16/funnel.png');
    	$block->replace($formChoose->renderHtml(), 'SEARCH_DIV');
    	
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

    	$payUrl = toUrl(array('pos_ReceiptDetails', 'makePayment'), 'local');
    	$block->append(ht::createElement('input', array('name' => 'paysum', 'type' => 'text', 'style' => 'text-align:right;float:left;')) . "<br />", 'INPUT_PAYMENT');
    	$payments = pos_Payments::fetchSelected();
	    foreach($payments as $payment) {
	    	$attr = array('class' => 'actionBtn paymentBtn', 'data-type' => "$payment->id", 'data-url' => $payUrl);
	    	$block->append(ht::createFnBtn($payment->title, '', '', $attr), 'PAYMENT_TYPE');
	    }
	    
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
	    $block->append(ht::createBtn('Приключи', $contoUrl, '', '', array('class' => "{$disClass}", 'id' => 'btn-close','title' => $hint)), 'CLOSE_BTNS');
	    $disClass = ($confInvUrl) ? '' : 'disabledBtn';
	    $block->append(ht::createBtn('Фактурирай', $confInvUrl, '', '', array('class' => "{$disClass}", 'id' => 'btn-inv', 'title' => $hintInv)), 'CLOSE_BTNS');
    	
	    return $block;
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
        
        $result = new bgerp_iface_DealResponse();
        $result->dealType = bgerp_iface_DealResponse::TYPE_SALE;
        
        $result->agreed->amount                 = $rec->total;
        $result->agreed->currency               = $currencyId;
        $result->agreed->vatType 				= 'yes';
        $result->agreed->payment->method        = cond_PaymentMethods::fetchField("#name = 'COD'", 'id');
        $result->agreed->payment->currencyId    = $currencyId;
        $result->agreed->payment->caseId        = $posRec->caseId;
       
        $result->shipped->amount                 = $rec->total;
        $result->shipped->currency               = $currencyId;
        $result->shipped->vatType 				 = 'yes';
        $result->shipped->payment->currencyId    = $currencyId;
        $result->shipped->payment->caseId        = $posRec->caseId;
        $result->shipped->delivery->storeId      = $posRec->storeId;
        $result->shipped->delivery->time         = $rec->valior;
         
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
            
            $result->agreed->products[] = $p;
            $result->shipped->products[] = clone $p;
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
    	$this->save($rec);
    	
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
}
