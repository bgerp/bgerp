<?php



/**
 * Мениджър за "Бележки за продажби" 
 *
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class pos_Receipts extends core_Master {
    
    
	/**
     * Заглавие
     */
    public $title = "Бележки за продажба";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_Rejected, doc_plg_MultiPrint, plg_Printing, acc_plg_DocumentSummary, plg_Printing,
    				 plg_State, bgerp_plg_Blank, pos_Wrapper, plg_Search, plg_Sorting,
                     plg_Modified';

    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Бележка за продажба";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, title=Заглавие, pointId=Точка, contragentName, total, paid, change, state , createdOn, createdBy';
    
    
    /**
	 * Детайли на бележката
	 */
	public $details = 'pos_ReceiptDetails';
	
	
    /**
     * Кой може да го прочете?
     */
    public $canRead = 'ceo, pos';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, pos';
    
    
    /**
     * Кой може да приключи бележка?
     */
    public $canClose = 'ceo, pos';
    
    
    /**
     * Кой може да прехвърли бележка?
     */
    public $canTransfer = 'ceo, pos';
   
    
    /**
     * Кой може да променя?
     */
    public $canAdd = 'pos, ceo';
    
    
    /**
     * Кой може да плати?
     */
    public $canPay = 'pos, ceo';
    
    
    /**
     * Кой може да променя?
     */
    public $canTerminal = 'pos, ceo';
    
    
    /**
     * Кой може да оттегля
     */
    public $canReject = 'pos, ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,pos';

	
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,pos';
    
    
    /**
     * Кой може да променя?
     */
    public $canEdit = 'pos, ceo';
	
    
	/** 
	 *  Полета по които ще се търси
	 */
	public $searchFields = 'contragentName';
	
	
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'pos/tpl/SingleLayoutReceipt.shtml';
    
    
    /**
     * При търсене до колко продукта да се показват в таба
     */
    protected $maxSearchProducts = 20;
    
    
    /**
     * Кои полета да се извлекат преди изтриване
     */
    public $fetchFieldsBeforeDelete = 'id';
    
    
    /**
     * Инстанция на детайла
     */
    public $pos_ReceiptDetails;
    
    
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
            'enum(draft=Чернова, active=Контиран, rejected=Оттеглен, closed=Затворен,waiting=Чакащ,pending)', 
            'caption=Статус, input=none'
        );
    	$this->FLD('transferedIn', 'key(mvc=sales_Sales)', 'input=none');

    	$this->setDbIndex('valior');
    }
    
    
    /**
     *  Екшън създаващ нова бележка, и редиректващ към Единичния и изглед
     *  Добавянето на нова бележка става само през този екшън 
     */
    function act_New()
    {
    	$cu = core_Users::getCurrent();
    	$posId = pos_Points::getCurrent();
    	$forced = Request::get('forced', 'int');
    	
    	// Ако форсираме, винаги създаваме нова бележка
    	if($forced){
    		$id = $this->createNew();
    	} else {
    		
    		// Ако има чернова бележка от същия ден, не създаваме нова
    		$today = dt::today();
    		if(!$id = $this->fetchField("#valior = '{$today}' AND #createdBy = {$cu} AND #pointId = {$posId} AND #state = 'draft'", 'id')){
    			$id = $this->createNew();
    		}
    	}
    	
    	// Записваме, че потребителя е разглеждал този списък
    	$this->logWrite("Отваряне на бележка в ПОС терминала", $id);
    	
    	return new Redirect(array($this, 'terminal', $id));
    }
    
    
    /**
     * Създава нова чернова бележка
     */
    private function createNew()
    {
    	$rec = new stdClass();
    	$posId = pos_Points::getCurrent();
    	
    	$rec->contragentName = 'Анонимен Клиент';
    	$rec->contragentClass = core_Classes::getId('crm_Persons');
    	$rec->contragentObjectId = pos_Points::defaultContragent($posId);
    	$rec->pointId = $posId;
    	$rec->valior = dt::now();
    	$this->requireRightFor('add', $rec);
    	
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
    		$row->title = "{$mvc->singleTitle} <b>№{$row->id}</b>";
    		$row->iconStyle = 'background-image:url("' . sbf('img/16/view.png', '') . '");';
    		$row->caseId = cash_Cases::getHyperLink(pos_Points::fetchField($rec->pointId, 'caseId'), TRUE);
    		$row->storeId = store_Stores::getHyperLink(pos_Points::fetchField($rec->pointId, 'storeId'), TRUE);
    		$row->baseCurrency = acc_Periods::getBaseCurrencyCode($rec->createdOn);
    		if($rec->transferedIn){
    			$row->transferedIn = sales_Sales::getHyperlink($rec->transferedIn, TRUE);
    		}
    		
    		if($rec->state == 'closed' || $rec->state == 'rejected'){
    			$reportQuery = pos_Reports::getQuery();
    			$reportQuery->where("#state = 'active'");
    			$reportQuery->show('details');
    			
    			// Опитваме се да намерим репорта в който е приключена бележката
    			//@TODO не е много оптимално защото търсим в блоб поле...
    			while($rRec = $reportQuery->fetch()){
    				$id = $rec->id;
    				$found = array_filter($rRec->details['receipts'], function ($e) use (&$id) {
    								return $e->id == $id;
    							});
    				
    				if($found){
    					$row->inReport = pos_Reports::getHyperlink($rRec->id, TRUE);
    					break;
    				}
    			}
    		}
    	}
    	
    	// Слагаме бутон за оттегляне ако имаме права
    	if(!Mode::is('printing')){
    		if($mvc->haveRightFor('reject', $rec)){
    			$row->rejectBtn = ht::createLink('', array($mvc, 'reject', $rec->id, 'ret_url' => toUrl(array($mvc, 'new'), 'local')), 'Наистина ли желаете да оттеглите документа?', 'ef_icon=img/16/reject.png,title=Оттегляне на бележката, class=reject-btn');
    		} elseif($mvc->haveRightFor('delete', $rec)){
    			$row->rejectBtn = ht::createLink('', array($mvc, 'delete', $rec->id, 'ret_url' => toUrl(array($mvc, 'new'), 'local')), 'Наистина ли желаете да изтриете документа?', 'ef_icon=img/16/delete.png,title=Изтриване на бележката, class=reject-btn');
    		}
    	}
    	
    	if($rec->state != 'draft'){
    		
    		// показваме датата на последната модификация на документа, ако е активиран
    		$row->valior = dt::mysql2verbal($rec->modifiedOn, "d.m.Y H:i:s");
    	}
    	
    	$cu = core_Users::fetch($rec->createdBy);
    	$row->createdBy = ht::createLink(core_Users::recToVerbal($cu)->nick, crm_Profiles::getUrl($rec->createdBy));
    	$row->pointId = pos_Points::getHyperLink($rec->pointId, TRUE);
    	
    	$row->time = dt::mysql2verbal(dt::now(), 'H:i');
    }

    
	/**
     * След подготовка на тулбара на единичен изглед.
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
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
	protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	// Подменяме бутона за добавяне с такъв сочещ към терминала
    	if(!empty($data->toolbar->buttons['btnAdd'])){
    		$data->toolbar->removeBtn('btnAdd');
    		$data->toolbar->addBtn('Нов запис', array($mvc, 'new'), 'id=btnAdd', 'ef_icon = img/16/star_2.png,title=Създаване на нов запис');
    	}
    }
    
    
	/**
     * Извлича информацията за всички продукти които са продадени чрез
     * тази бележки, във вид подходящ за контирането
     * 
     * @param int id - ид на бележката
     * @return mixed $products - Масив от продукти
     */
    public static function getProducts($id)
    {
    	expect($rec = static::fetch($id), 'Несъществуваща бележка');
    	
    	$products = array();
    	
    	$query = pos_ReceiptDetails::getQuery();
    	$query->where("#receiptId = {$id}");
    	$query->where("#quantity != 0");
    	$query->where("#action LIKE '%sale%'");
    	$query->orderBy("id", "ASC");
    	
	    while($rec = $query->fetch()) {
	    	$info = cat_Products::getProductInfo($rec->productId);
	    	$quantityInPack = ($info->packagings[$rec->value]) ? $info->packagings[$rec->value]->quantity : 1;
	    	
	    	$products[] = (object) array(
	    		'productId'   => $rec->productId,
		    	'price'       => $rec->price / $quantityInPack,
	    	    'packagingId' => $rec->value,
	    		'vatPrice'    => $rec->price * $rec->param,
	    		'discount'    => $rec->discountPercent,
		    	'quantity'    => $rec->quantity);
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
    	
    	$dQuery = $this->pos_ReceiptDetails->getQuery();
    	$dQuery->where("#receiptId = {$id}");
    	while($dRec = $dQuery->fetch()){
    		$action = explode("|", $dRec->action);
    		switch($action[0]) {
    			case 'sale':
    				$vat = cat_Products::getVat($dRec->productId, $rec->createdOn);
    				$price = $dRec->price * (1 - $dRec->discountPercent) * (1 + $vat);
    				$price = round($price, 2);
    				$rec->total += round($dRec->quantity * $price, 2);
    				break;
    			case 'payment':
    				$rec->paid += $dRec->amount;
    				$rec->change += $dRec->value;
    				break;
    		}
    	}
    	
    	// Ако няма въведен клиент от потребителя
    	$rec->contragentName = tr('Анонимен Клиент');
	    $rec->contragentClass = core_Classes::getId('crm_Persons');
	    $rec->contragentObjectId = pos_Points::defaultContragent($rec->pointId);
    	
    	$diff = round($rec->paid - $rec->total, 2);
    	$rec->change = ($diff <= 0) ? 0 : $diff;
    	$rec->total = $rec->total;
    	
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
	 * Модификация на ролите
	 */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{ 
		// Само черновите бележки могат да се редактират в терминала
		if($action == 'terminal' && isset($rec)) {
			if($rec->state != 'draft'){
				$res = 'no_one';
			}
		}
		
		// Никой не може да редактира бележка
		if($action == 'edit') {
			$res = 'no_one';
		}
		
		// Никой не може да оттегли затворена бележка
		if($action == 'reject' && isset($rec)) {
			if($rec->state == 'closed'){
				$res = 'no_one';
			}
		}
		
		
		// Ако бележката е започната, може да се изтрие
		if($action == 'delete' && isset($rec)) {
			if($rec->state != 'draft'){
				$res = 'no_one';
			}
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
		
		// Не могат да се възстановяват пранзи бележки
		if($action == 'restore' && isset($rec)){
			if($rec->total == 0){
				$res = 'no_one';
			}
		}
		
		// Може ли да бъде направено плащане по бележката
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
		
		// Не може да се прехвърля бележката, ако общото и е нула, има платено или не е чернова
		if($action == 'transfer' && isset($rec)) {
			if(empty($rec->id) || round($rec->paid, 2) > 0 || $rec->state != 'draft') {
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
    	pos_Points::requireRightFor('select', $rec->pointId);
    	
    	// Имаме ли достъп до терминала
    	if(!$this->haveRightFor('terminal', $rec)){
    		
    		return new Redirect(array($this, 'new'));
    	}
    	
    	// Лейаут на терминала
    	$tpl = getTplFromFile("pos/tpl/terminal/Layout.shtml");
    	$tpl->replace(pos_Points::getTitleById($rec->pointId), 'PAGE_TITLE');
    	$tpl->appendOnce("\n<link  rel=\"shortcut icon\" href=" . sbf("img/16/cash-register.png", '"', TRUE) . ">", "HEAD");
    	
    	// Добавяме бележката в изгледа
    	$receiptTpl = $this->getReceipt($rec);
    	$tpl->replace($receiptTpl, 'RECEIPT');
    	
    	// Ако не сме в принтиране, сменяме обвивквата и рендираме табовете
    	if(!Mode::is('printing')){
    		
    		// Задаваме празна обвивка
    		Mode::set('wrapper', 'page_Empty');
    	
    		// Ако сме чернова, добавяме пултовете
    		if($rec->state == 'draft'){
    		
    			// Добавяне на табовете под бележката
    			$toolsTpl = $this->getTools($rec);
    			$tpl->replace($toolsTpl, 'TOOLS');
    		
    			// Добавяне на табовете показващи се в широк изглед отстрани
    			if(!Mode::is('screenMode', 'narrow')){
    				$DraftsUrl = toUrl(array('pos_Receipts', 'showDrafts', $rec->id), 'absolute');
    				$tab = new ET(tr("|*<li [#active#] title='|Търсене на артикул|*'><a href='#tools-search' accesskey='o'>|Търсене|*</a></li><li title='|Всички чернови бележки|*'><a href='#tools-drafts' data-url='{$DraftsUrl}' accesskey='p'>|Бележки|*</a></li>"));
    				 
    				if($selectedFavourites = $this->getSelectFavourites()){
    					$tab->prepend(tr("|*<li class='active' title='|Избор на бърз артикул|*'><a href='#tools-choose' accesskey='i'>|Избор|*</a></li>"));
    					$tpl->replace($selectedFavourites, 'CHOOSE_DIV_WIDE');
    				} else {
    					$tab->replace("class='active'", 'active');
    				}
    				 
    				$tpl->append($this->renderChooseTab($id), 'SEARCH_DIV_WIDE');
    				$tpl->append($this->renderDraftsTab($id), 'DRAFTS_WIDE');
    				 
    				$tpl->replace($tab, 'TABS_WIDE');
    			}
    		}
    	}

        $data = (object) array('rec' => $rec);
    	
    	$this->invoke('AfterRenderSingle', array(&$tpl, $data));
    	
    	if(!Mode::is('printing')){
    		$tpl->append("<iframe name='iframe_a' style='display:none'></iframe>");
    	}
    	
    	// Вкарване на css и js файлове
    	$this->pushFiles($tpl);
    	
    	$this->renderWrapping($tpl);
    	
    	return $tpl;
    }
    
    
    /**
     * Вкарване на css и js файлове
     */
    private function pushFiles(&$tpl)
    {
    	$tpl->push('css/Application.css', 'CSS');
    	$tpl->push('css/default-theme.css', 'CSS');
    	$tpl->push('pos/tpl/css/styles.css', 'CSS');
    	if(!Mode::is('printing')){
    		$tpl->push('pos/js/scripts.js', 'JS');
    		jquery_Jquery::run($tpl, "posActions();");
    	}
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
    	unset($data->row->contragentName);
    	$data->receiptDetails = $this->pos_ReceiptDetails->prepareReceiptDetails($data->rec->id);
    }
    
    
    /**
     * Подготовка и рендиране на бележка
     * 
     * @return core_ET $tpl - шаблон
     */
    private function renderReceipt($data)
    {
    	// Слагане на мастър данните
    	if(!Mode::is('printing')){
    		$tpl = getTplFromFile('pos/tpl/terminal/Receipt.shtml');
    	} else {
    		$tpl = getTplFromFile('pos/tpl/terminal/ReceiptPrint.shtml');
    	}
    	
    	$tpl->placeObject($data->row);
    	
    	$img = ht::createElement('img',  array('src' => sbf('pos/img/bgerp.png', '')));
    	$logo = ht::createLink($img, array('bgerp_Portal', 'Show'), NULL, array('target'=>'_blank', 'class' => 'portalLink'));
    	$tpl->append($logo, 'LOGO');
    	
    	// Слагане на детайлите на бележката
    	$detailsTpl = $this->pos_ReceiptDetails->renderReceiptDetail($data->receiptDetails);
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
    	$tab = tr("|*<li class='active' title='|Пулт|*'><a href='#tools-form' accesskey='z'>|Пулт|*</a></li><li title='|Пулт за плащане|*'><a href='#tools-payment' accesskey='x'>|Плащане|*</a></li><li title='|Прехвърляне на продажбата на контрагент|*'><a href='#tools-transfer' accesskey='c'>|Прехвърляне|*</a></li>");
    	$tpl->append($this->renderToolsTab($id), 'TAB_TOOLS');
    	
    	// Ако сме в тесен режим
    	if(Mode::is('screenMode', 'narrow')){
    		
    		// Добавяне на таба с бързите бутони
    		$tpl->append($this->getSelectFavourites(), 'CHOOSE_DIV');
    		
    		// Добавяне на таба с избор
    		$tpl->append($this->renderChooseTab($id), 'SEARCH_DIV');
    		$tab .= tr("|*<li title='|Избор на бърз артикул|*'><a href='#tools-choose' accesskey='i'>|Избор|*</a></li><li title='|Търсене на артикул|*'><a href='#tools-search' accesskey='o'>|Търсене|*</a></li><li><a href='#tools-drafts' title='|Всички чернови бележки|*' accesskey='p'>|Бележки|*</a></li>");
    	
    		// Добавяне на таба с черновите
    		$tpl->append($this->renderDraftsTab($id), 'DRAFTS');
    	}
    	
    	// Добавяне на таба за плащане
    	$tpl->append($this->renderPaymentTab($id), 'PAYMENTS');
    	
    	// Добавяне на таба за прехвърлянията
    	$tpl->append($this->renderTransferTab($id), 'TRANSFERS');
    	
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
    	if(!$products->arr) return FALSE;
    	
    	$tpl = getTplFromFile('pos/tpl/terminal/ToolsForm.shtml')->getBlock('CHOOSE_DIV');
    	$tpl->append(pos_Favourites::renderPosProducts($products), 'CHOOSE_DIV');
    	
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
	    	$addUrl = toUrl(array('pos_Receipts', 'addProduct', $rec->id), 'local');
	    	$absUrl = toUrl(array('pos_Receipts', 'addProduct', $rec->id), 'absolute');
	    	
    	} else {
    		$discUrl = $addUrl = $addUrl = $modQUrl = NULL;
    		$disClass = 'disabledBtn';
    		$disabled = 'disabled';
    	}
    	
    	$value = NULL;
    	
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
    	
    	$block->append(ht::createElement('input', array('name' => 'ean', 'type' => 'text', 'style' => 'text-align:right', 'title' => tr('Въведи'))), 'INPUT_FLD');
    	$block->append(ht::createElement('input', array('name' => 'receiptId', 'type' => 'hidden', 'value' => $rec->id)), 'INPUT_FLD');
    	$block->append(ht::createElement('input', array('name' => 'rowId', 'type' => 'hidden', 'value' => $value)), 'INPUT_FLD');
    	$block->append(ht::createFnBtn('Код', NULL, NULL, array('class' => "{$disClass} buttonForm", 'id' => 'addProductBtn', 'data-url' => $addUrl, 'title' => 'Продуктов код или баркод')), 'FIRST_TOOLS_ROW');
    	$block->append(ht::createFnBtn('К-во', NULL, NULL, array('class' => "{$disClass} buttonForm tools-modify", 'data-url' => $modQUrl, 'title' => 'Промени количество')), 'FIRST_TOOLS_ROW');
    	$block->append(ht::createFnBtn('|Отстъпка|* %', NULL, NULL, array('class' => "{$disClass} buttonForm tools-modify", 'data-url' => $discUrl, 'title' => 'Задай отстъпка')), 'FIRST_TOOLS_ROW');
    	$block->append(ht::createFnBtn('*', NULL, NULL, array('class' => "buttonForm tools-sign", 'title' => 'Умножение', 'value' => '*')), 'FIRST_TOOLS_ROW');
    	
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
    	$inpFld = ht::createTextInput('select-input-pos', '', array('id' => 'select-input-pos', 'data-url' => $searchUrl, "type" => 'text'));
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
    	
    	if($this->haveRightFor('add')){
    		$addBtn = ht::createLink("Нова<br>бележка", array('pos_Receipts', 'new', 'forced' => TRUE), NULL, "class=pos-notes");
    		$block->prepend($addBtn);
    	}
    	
    	return $block;
    }
    
    
    /**
     * Активира документа и ако е зададено пренасочва към създаването на нова фактура
     */
    function act_Transfer()
    {
    	$this->requireRightFor('transfer');
    	
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	
    	// Извличаме нужните ни параметри от рекуеста
    	expect($contragentClassId = Request::get('contragentClassId', 'int'));
    	expect($contragentId = Request::get('contragentId', 'int'));
    	expect($contragentClass = cls::get($contragentClassId));
    	expect($contragentClass->fetch($contragentId));
    	
    	$this->requireRightFor('transfer', $rec);
    	
    	// Подготвяме масива с данните на новата продажба, подаваме склада и касата на точката
    	$posRec = pos_Points::fetch($rec->pointId);
    	$fields = array('shipmentStoreId' => $posRec->storeId, 'caseId' => $posRec->caseId);
    	
    	$products = $this->getProducts($rec->id);
    	
    	// Опитваме се да създадем чернова на нова продажба породена от бележката
    	if($sId = sales_Sales::createNewDraft($contragentClassId, $contragentId, $fields)){
    		
    		// Намираме продуктите на бележката (трябва да има поне един)
    		$products = $this->getProducts($rec->id);
    		
    		// За всеки продукт
    		foreach ($products as $product){
    			
    			// Намираме цената от ценовата политика
    			$Policy = cls::get('price_ListToCustomers');
    			$pInfo = $Policy->getPriceInfo($contragentClassId, $contragentId, $product->productId, $product->packagingId, $product->quantity);
    			
    			// Колко са двете цени с приспадната отстъпка
    			$rPrice1 = $product->price * (1 - $product->discount);
    			$rPrice2 = $pInfo->price * (1 - $pInfo->discount);
    			
    			// Оставяме по-малката цена
    			if($rPrice2 < $rPrice1) {
    				$product->price = $pInfo->price;
    				$product->discount = $pInfo->discount;
    			}
    			
    			// Добавяме го като детайл на продажбата;
    			sales_Sales::addRow($sId, $product->productId, $product->quantity, $product->price, $product->packagingId, $product->discount);
    		}
    	}
    	
    	// Отбелязваме къде е прехвърлена рецептата
    	$rec->transferedIn = $sId;
    	$rec->state = 'closed';
    	$this->save($rec);
    	core_Statuses::newStatus("|Бележка|* №{$rec->id} |е затворена|*");
    	
    	// Споделяме потребителя към нишката на създадената продажба
    	$cu = core_Users::getCurrent();
    	$sRec = sales_Sales::fetch($sId);
    	doc_ThreadUsers::addShared($sRec->threadId, $sRec->containerId, $cu);
    	
    	// Редирект към новата бележка
    	return new Redirect(array('sales_Sales', 'single', $sId), '|Успешно прехвърляне на бележката');
    }
    
    
    /**
     * Подготвя данните на намерените контрагенти
     * 
     * @param string $string - По кой стринг ще се търси
     * @param enum(company,person) $type - какво ще търсим Лице/Фирма
     * @return stdClass $data
     */
	private function prepareContragents($rec, $string, $type)
	{
		$data = new stdClass();
		$data->recs = $data->rows = array();
		
		$searchString = plg_Search::normalizeText($string);
		
		foreach (array('person' => 'crm_Persons', 'company' => 'crm_Companies') as $type1 => $class){
			if($type1 === $type || !$type){
				$query = $class::getQuery();
				
				if($type1 == 'company'){
					$ownId = crm_Setup::BGERP_OWN_COMPANY_ID;
					$query->where("#id != {$ownId}");
				}
				
				if($searchString){
					$query->where(array("#searchKeywords LIKE '%[#1#]%'", $searchString));
				}
				$query->where("#state != 'rejected' AND #state != 'closed'");
				$query->show('id,name');
				
				if($type){
					$query->limit(20);
				} else {
					$query->limit(10);
				}
					
				while($rec1 = $query->fetch()){
					$rec1->class = $class;
					$rec1->icon = cls::get($class)->singleIcon;
					$data->recs["$type1|{$rec1->id}"] = $rec1;
				}
				
				if($type1 == 'person'){
					if($Contragent = pos_Cards::getContragent($searchString, crm_Persons::getClassId())){
						$data->recs["{$type1}|{$Contragent->that}"] = $Contragent->rec();
					}
				}
			}
		}
		
    	// Ако има намерени записи
    	if(count($data->recs)){
    		$count = 1;
    	
    		// Обръщаме ги във вербален вид
    		foreach ($data->recs as $dRec){
    			if($this->haveRightFor('transfer', $rec)){
    				$recUrl = array($this, 'Transfer', 'id' => $rec->id, 'contragentClassId' => cls::get($dRec->class)->getClassId(), 'contragentId' => $dRec->id);
    				$newUrl = toUrl(array('pos_Receipts', 'new'), 'local');
    			}
    			$disClass = ($recUrl) ? '' : 'disabledBtn';
    			$btn = ht::createBtn('Прехвърли', $recUrl, FALSE, TRUE, array('class' => "{$disClass} different-btns transferBtn", 'data-url' => $newUrl, 'title' => 'Прехвърли продажбата към контрагента'));
    			
    			$icon = ht::createElement('img', array('src' => sbf($dRec->icon, '')));
    			
    			if(cls::get($dRec->class)->haveRightFor('single', $dRec->id)){
    				$name = " " . ht::createLinkRef($icon . " " . $dRec->name, array($dRec->class, 'single', $dRec->id));
    			} else {
    				$icon = ht::createElement('img', array('src' => sbf('img/16/lock.png', '')));
    				$name = $icon . " <span style='color:#777'>{$dRec->name}</span>";
    			}
    			
    			$data->rows[$dRec->id] = (object)array('count' => $count, 'name' => $name, 'btn' => $btn);
    			$count++;
    		}
    	}
    	
    	return $data;
	}


	/**
	 * Рендира таблицата с намерените контрагенти
	 * 
	 * @param stdClass $data
	 */
	private function renderFoundContragents($data)
	{
		$table = cls::get('core_TableView');
		$fields = arr::make('count=№,name=Име,btn=Действие');
		
		$tpl = new ET("<div class='result-string'>{$data->title}</div><div class='pos-table'>[#TABLE#]</div>");
		$tpl->append($table->get($data->rows, $fields), 'TABLE');
		
		return $tpl->getContent();
	}
	
	
    /**
     * Връща намерените фирми
     */
    function act_SearchContragents()
    {
    	$this->requireRightFor('terminal');
    	
    	if(!$receiptId = Request::get('receiptId', 'int')) return array();
    	if(!$rec = $this->fetch($receiptId)) return array();
    	$searchString = Request::get('searchString');
    	$type = Request::get('type');
    	
    	// Подготвяме информацията за контрагентите
    	$data = $this->prepareContragents($rec, $searchString, $type);
    	
    	// Рендираме я
    	$html = $this->renderFoundContragents($data);
    	
    	if(Request::get('ajax_mode')){
    		// Ще реплесйнем и добавим таблицата с резултатите
    		$resObj = new stdClass();
    		$resObj->func = "html";
    		$resObj->arg = array('id' => 'result_contragents', 'html' => $html, 'replace' => TRUE);
    		 
    		return array($resObj);
    		 
    	} else {
    		return new Redirect(array($this, 'terminal', $rec->id));
    	}
    }
    
    
    /**
     * Рендиране на таба за прехвърлянията
     *
     * @param int $id -ид на бележка
     */
    public function renderTransferTab($id)
    {
    	expect($rec = $this->fetchRec($id));
    	$block = getTplFromFile('pos/tpl/terminal/ToolsForm.shtml')->getBlock('TRANSFERS_BLOCK');
    	
    	$searchUrl1 = toUrl(array('pos_Receipts', 'searchContragents', 'type' => 'company'), 'local');
    	$searchUrl2 = toUrl(array('pos_Receipts', 'searchContragents', 'type' => 'person'), 'local');
    	$searchUrl3 = toUrl(array('pos_Receipts', 'searchContragents'), 'local');
    	
    	$inpFld = ht::createElement('input', array('name' => 'input-search-contragent', 'id' => 'input-search-contragent', 'type' => 'text', 'data-url' => $searchUrl3, 'title' => 'Търси контрагент по ключова дума,номер или код'));
    	
    	$block->append($inpFld, 'TRANSFERS_BLOCK');
    	
    	$block->append(ht::createFnBtn('Фирма', NULL, NULL, array('class' => "buttonForm pos-search-contragent-btn", 'data-url' => $searchUrl1, 'title' => 'Търси фирма')), 'BTNS');
    	$block->append(ht::createFnBtn('Лице', NULL, NULL, array('class' => "buttonForm pos-search-contragent-btn", 'data-url' => $searchUrl2, 'title' => 'Търси лице')), 'BTNS');
    	
    	
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

    	$payUrl = $recUrl = array();
    	if($this->haveRightFor('pay', $rec)){
    		$payUrl = toUrl(array('pos_ReceiptDetails', 'makePayment'), 'local');
    	}
    	
    	$value = $rec->total - $rec->paid;
    	$value = ($value > 0) ? $value : NULL;
    	$block->append(ht::createElement('input', array('name' => 'paysum', 'type' => 'text', 'style' => 'text-align:right;float:left;', 'value' => $value, 'title' => tr('Въведи платена сума'))) . "<br />", 'INPUT_PAYMENT');
    	
    	// Показваме всички активни методи за плащания
    	$disClass = ($payUrl) ? '' : 'disabledBtn';
    	
    	$payments = cond_Payments::fetchSelected();
    	$placeholder = (count($payments)) ? 'PAYMENT_TYPE' : 'CLOSE_BTNS';
    	$block->append(ht::createFnBtn('В брой', '', '', array('class' => "{$disClass} actionBtn paymentBtn", 'data-type' => "-1", 'data-url' => $payUrl)), $placeholder);
    	
    	if(count($payments)){
    		$block->append("<div class=''>", 'PAYMENT_TYPE');
    		foreach($payments as $payment) {
    			$attr = array('class' => "{$disClass} actionBtn paymentBtn", 'data-type' => "$payment->id", 'data-url' => $payUrl);
    			$block->append(ht::createFnBtn($payment->title, '', '', $attr), $placeholder);
    		}
    		$block->append("</div>", 'PAYMENT_TYPE');
    	}
	    
    	$printUrl = array($this, 'terminal', $rec->id, 'Printing' => 'yes');
    	$block->append(ht::createBtn('Печат', $printUrl, NULL, NULL, array('class' => "actionBtn", 'title' => 'Принтиране на бележката')), 'CLOSE_BTNS');
    	
	    // Ако може да се издаде касова бележка, активираме бутона
	    if($this->haveRightFor('printReceipt', $rec)){
	    	$recUrl = array($this, 'printReceipt', $rec->id);
	    }
	    
	    $disClass = ($recUrl) ? '' : 'disabledBtn';
	    $block->append(ht::createBtn('Касов бон', $recUrl, NULL, NULL, array('class' => "{$disClass} actionBtn", 'target' => 'iframe_a', 'title' => 'Издай касова бележка')), 'CLOSE_BTNS');
	    
	    if ($this->haveRightFor('close', $rec)) {
	    	$contoUrl = array('pos_Receipts', 'close', $rec->id);
	    	$hint = tr("Приключи продажбата");
	    } else {
	    	$contoUrl =  NULL;
	    	$hint = $hintInv = tr("Не може да приключите бележката, докато не е платена");
	    }
	    
	    $disClass = ($contoUrl) ? '' : 'disabledBtn';
	    $block->append(ht::createBtn('Приключи', $contoUrl, '', '', array('class' => "{$disClass} different-btns", 'id' => 'btn-close', 'title' => $hint)), 'CLOSE_BTNS');
	   
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
    	
    	return $Driver->createFile($driverData);
    }
    
    
    /**
     * Имплементиране на интерфейсен метод ( @see acc_TransactionSourceIntf )
     */
    static function getLink($id)
    {
    	return static::recToVerbal(static::fetchRec($id), 'id,title,-list')->title;
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
    		core_Statuses::newStatus('|Не може да се добавя продукт, ако има направено плащане|*!', 'error');
    		return $this->pos_ReceiptDetails->returnError($receiptId);
    	}
    	 
    	// Трябва да можем да добавяме към нея
    	$this->pos_ReceiptDetails->requireRightFor('add', (object)array('receiptId' => $receiptId));
    	 
    	// Запис на продукта
    	$rec = new stdClass();
    	$rec->receiptId = $receiptId;
    	$rec->action = 'sale|code';
    	
    	// Ако има к-во и то валидно задаваме го на записа
    	$quantity = Request::get('quantity');
    	if($quantity = cls::get('type_Double')->fromVerbal($quantity)){
    		$rec->quantity = $quantity;
    	} else {
    		$rec->quantity = 1;
    	}
    	
    	// Ако е зададено ид на продукта
    	if($productId = Request::get('productId', 'int')) {
    		$rec->productId  = $productId;
    	}
    	
    	// Ако е зададен код на продукта
    	if($ean = Request::get('ean')) {
    		
    		// Проверяваме дали въведения "код" дали е във формата '< число > * < код >', 
    		// ако да то приемаме числото преди '*' за количество а след '*' за код
    		preg_match('/([0-9+\ ?]*[\.|\,]?[0-9]*\ *)(\ ?\* ?)([0-9a-zа-я\- _]*)/iu', $ean, $matches);
    		
    		// Ако има намерени к-во и код от регулярния израз
    		if(!empty($matches[1]) && !empty($matches[3])){
    			
    			// Ако има ид на продукт
    			if(isset($rec->productId)){
    				$rec->quantity = cls::get('type_Double')->fromVerbal($matches[1] * $matches[3]);
    			} else {
    				
    				// Ако няма приемаме, че от ляво е колчиество а от дясно код
    				$rec->quantity = cls::get('type_Double')->fromVerbal($matches[1]);
    				$rec->ean = $matches[3];
    			}
    			
    			// Ако има само лява част приемаме, че е количество
    		} elseif(!empty($matches[1]) && empty($matches[3])) {
    			$rec->quantity = cls::get('type_Double')->fromVerbal($matches[1]);
    		} else {
    			if(isset($rec->productId)){
					$rec->quantity = cls::get('type_Double')->fromVerbal($ean);
    			} else {
    				$rec->ean = $ean;
    			}
    		}
    	}
    	
    	// Трябва да е подаден код или ид на продукт
    	if(!$rec->productId && !$rec->ean){
    		core_Statuses::newStatus('|Не е избран артикул|*!', 'error');
    		return $this->pos_ReceiptDetails->returnError($receiptId);
    	}
    	 
    	if($packId = Request::get('packId', 'int')){
    		if(!cat_UoM::fetch($packId)){
    			core_Statuses::newStatus('|Невалидна опаковка|*!', 'error');
    			return $this->pos_ReceiptDetails->returnError($receiptId);
    		}
    	
    		$rec->value = $packId;
    	}
    	
    	
    	// Намираме нужната информация за продукта
    	$this->pos_ReceiptDetails->getProductInfo($rec);
    	
    	// Ако не е намерен продукт
    	if(!$rec->productId) {
    		core_Statuses::newStatus('|Няма такъв продукт в системата, или той не е продаваем|*!', 'error');
    		return $this->pos_ReceiptDetails->returnError($receiptId);
    	}
    
    	// Ако няма цена
    	if(!$rec->price) {
    		$createdOn = pos_Receipts::fetchField($rec->receiptId, 'createdOn');
    		$createdOn = dt::mysql2verbal($createdOn, 'd.m.Y H:i');
    		
    		core_Statuses::newStatus("|Артикулът няма цена към|* <b>{$createdOn}</b>", 'error');
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
    			$resObj = new stdClass();
    			$resObj->func = 'Sound';
    			$resObj->arg = array('soundOgg' => sbf("sounds/scanner.ogg", ''),
    			'soundMp3' => sbf("sounds/scanner.mp3", ''),
    			);
    		}
 
    		$resArr =  $this->pos_ReceiptDetails->returnResponse($rec->receiptId);
    		$resArr[] = $resObj;
    		
    		return $resArr;
    	} else {
    		core_Statuses::newStatus('|Проблем при добавяне на артикул|*!', 'error');
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
    	if($rec->state != 'draft'){
    		
    		// Създаване на нова чернова бележка
    		return new Redirect(array($this, 'new'));
    	}
    	
    	$this->requireRightFor('close', $rec);
    	
    	$rec->state = 'waiting';
    	if($this->save($rec)){
    		
    		// Обновяваме складовите наличности
    		pos_Stocks::updateStocks($rec->id);
    	}
    	
    	// Създаване на нова чернова бележка
    	return new Redirect(array($this, 'new'));
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
    		$rec = NULL;
    	}
    	
    	if(Request::get('ajax_mode')){
    		// Ще реплесйнем и добавим таблицата с резултатите
    		$resObj = new stdClass();
    		$resObj->func = "html";
    		$resObj->arg = array('id' => 'pos-search-result-table', 'html' => $html, 'replace' => TRUE);
    		
    		return array($resObj);
    		
    	} else {
    		return new Redirect(array($this, 'terminal', $rec->id));
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
    	
    	// Намираме всички продаваеми продукти, за анонимния клиент
    	$sellable = cls::get('cat_Products')->getProducts($data->rec->contragentClass, $data->rec->contragentObjectId, $data->rec->valior, 'canSell');
    	if(!count($sellable)) return;
    	
    	$Policy = cls::get('price_ListToCustomers');
    	$Products = cls::get('cat_Products');
    	
    	foreach ($sellable as $id => $name){
    		if(is_object($name)) continue;
    		
    		// Показваме само до определена бройка
    		if($count >= $this->maxSearchProducts) break;
    		
    		// Ако продукта не отговаря на търсения стринг, го пропускаме
    		if(!$pRec = $Products->fetch(array("#id = {$id} AND #searchKeywords LIKE '%[#1#]%'", $data->searchString))) continue;
    		
    		$pInfo = cat_Products::getProductInfo($id);
    		
    		$packs = $Products->getPacks($id);
    		$packId = key($packs);
    		$perPack = (isset($pInfo->packagings[$packId])) ? $pInfo->packagings[$packId]->quantity : 1;
    		
    		$price = $Policy->getPriceInfo($data->rec->contragentClass, $data->rec->contragentObjectId, $id, $packId, 1, $data->rec->createdOn, 1, 'yes');
    		
    		// Ако няма цена също го пропускаме
    		if(empty($price->price)) continue;
    		$vat = $Products->getVat($id);
    		$obj = (object)array('productId'   => $id, 
    							 'measureId'   => $pRec->measureId,
    							 'price'       => $price->price * $perPack, 
    							 'photo'       => $pRec->photo,
    							 'packagingId' => $packId,
    							 'vat'	       => $vat);
    		
    		if(isset($pInfo->meta['canStore'])){
    			$obj->stock = pos_Stocks::getQuantity($id, $data->rec->pointId);
    			$obj->stock /= $perPack;
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
    	
    	$row->price = $Double->toVerbal($obj->price);
    	$row->price .= "&nbsp;<span class='cCode'>{$data->baseCurrency}</span>";
    	$row->stock = $Double->toVerbal($obj->stock);
    	
    	$row->packagingId = ($obj->packagingId) ? cat_UoM::getTitleById($obj->packagingId) : cat_UoM::getTitleById($obj->measureId);
    	
    	$obj->receiptId = $data->rec->id;
    	if($this->pos_ReceiptDetails->haveRightFor('add', $obj)){
    		$addUrl = toUrl(array('pos_Receipts', 'addProduct', $data->rec->id), 'local');
    	} else {
    		$addUrl = NULL;
    	}
    	
    	$row->productId = cat_Products::getTitleById($obj->productId);
    	if($data->showParams){
    		$params = keylist::toArray($data->showParams);
    		$values = NULL;
    		foreach ($params as $pId){
    			
    			//@TODO да използва нов метод getParamValue
    			if($vRec = cat_products_Params::fetch("#productId = {$obj->productId} AND #paramId = {$pId}")){
    				$row->productId .= " &nbsp;" . cat_products_Params::recToVerbal($vRec, 'paramValue')->paramValue;
    			}
    		}
    	}
    	
    	$row->productId = ht::createLinkRef($row->productId, array('cat_Products', 'single', $obj->productId), NULL, array('target'=>'_blank', 'class'=>'singleProd'));
    	
    	if($obj->stock < 0){
    		$row->stock = "<span style='color:red'>{$row->stock}</span>";	
    	}
    	
    	$row->ROW_ATTR['class'] = "search-product-row pos-add-res-btn";
    	$row->ROW_ATTR['data-url'] = $addUrl;
    	$row->ROW_ATTR['data-productId'] = $obj->productId;
    	$row->ROW_ATTR['title'] = tr('Добавете артикула към бележката');
    	
    	if(!Mode::is('screenMode', 'narrow')) {
    		$thumb = ($obj->photo) ? new thumb_Img($obj->photo, 64, 64) : new thumb_Img(getFullPath('pos/img/default-image.jpg'), 64, 64, 'path');
    		$arr = array();
    		$row->photo = $thumb->createImg($arr);
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
    	$fields = arr::make('photo=Снимка,productId=Продукт,packagingId=Опаковка,price=Цена,stock=Наличност');
    	if(Mode::is('screenMode', 'narrow')){
    		unset($fields['photo']);
    	}
    	
    	return $table->get($data->rows, $fields)->getContent();
    }
    
    
    /**
     * Подготвя данните за драйвера на фискалния принтер
     */
    private function getFiscPrinterData($id)
    {
    	$receiptRec = $this->fetchRec($id);
    	$data = new stdClass();
    	$data->totalPaid = 0;
    	
    	$payments = $products = array();
    	$query = pos_ReceiptDetails::getQuery();
    	$query->where("#receiptId = '{$receiptRec->id}'");
    	
    	// Разделяме детайлите на плащания и продажби
    	while($rec = $query->fetch()){
    		$nRec = new stdClass();
    		
    		// Всеки продукт
    		if(strpos($rec->action, 'sale') !== FALSE){
    			$nRec->id = $rec->productId;
    			$nRec->managerId = cat_Products::getClassId();
    			$nRec->quantity = $rec->quantity;
    			$pInfo = cls::get('cat_Products')->getProductInfo($rec->productId);
    			$nRec->measure = cat_UoM::getShortName($rec->value);
    			$nRec->vat = $rec->param;
    			$nRec->price = $rec->price;
    			
    			// Подаваме цената с приспадната отстъпка ако има, за да няма проблем при закръглянията
    			if($rec->discountPercent){
    				$nRec->price -= $nRec->price * $rec->discountPercent;
    			}
    			
    			$nRec->name = $pInfo->productRec->name;
    			if($pInfo->productRec){
    				$nRec->vatGroup = $pInfo->productRec->vatGroup;
    			}
    			
    			$products[] = $nRec;
    		} elseif(strpos($rec->action, 'payment') !== FALSE) {
    			
    			// Всяко плащане
    			list(, $type) = explode('|', $rec->action);
    			$nRec->type = cond_Payments::fetchField($type, 'code');
    			$nRec->amount = round($rec->amount, 2);
    			$data->totalPaid += $nRec->amount;
    			
    			$payments[] = $nRec;
    		}
    	}
    	
    	$data->short = FALSE;
    	$data->hasVat = TRUE;
    	$data->products = $products;
    	$data->payments = $payments;
    	
    	return $data;
    }
    

    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$me = cls::get(get_called_class());
    	
    	return $me->singleTitle . " №{$rec->id}";
    }
    
    
    /**
     * Подготвя чакащите бележки в сингъла на точката на продажба
     * 
     * @param stdClass $data
     * @return void
     */
    public function prepareReceipts(&$data)
    {
    	$data->rows = array();
    	
    	$query = $this->getQuery();
    	$query->where("#pointId = {$data->masterId}");
    	$query->where("#state = 'waiting' OR #state = 'draft'");
    	$query->orderBy("#state");
    	
    	$conf = core_Packs::getConfig('pos');
    	
    	while($rec = $query->fetch()){
    		$num = substr($rec->id, -1 * $conf->POS_SHOW_RECEIPT_DIGITS);
    		$stateClass = ($rec->state == 'draft') ? "state-draft" : "state-waiting";
    		
    		if(!Mode::isReadOnly()){
    			if($this->haveRightFor('terminal', $rec)){
    				$num = ht::createLink($num, array($this, 'terminal', $rec->id));
    			} elseif($this->haveRightFor('single', $rec)){
    				$num = ht::createLink($num, array($this, 'single', $rec->id));
    			}
    		}
    		
    		if($rec->state == 'draft'){
    			if($rec->total != 0){
    				$num = ht::createHint($num, 'Бележката е започната, но не е приключена', 'warning', FALSE);
    			}
    		}
    		$num = " <span class='open-note {$stateClass}' style='border:1px solid #a6a8a7'>{$num}</span>";
    		
    		$data->rows[$rec->id] = $num;
    	}
    }
    
    
    /**
     * Рендиране на чакащите бележки в сингъла на точката на продажба
     * 
     * @param stdClass $data
     * @return core_ET $tpl
     */
    public function renderReceipts($data)
    {
    	$tpl = new ET('');
    	$str = implode('', $data->rows);
        $tpl->append($str);	
         
    	return $tpl;
    }
    
    
    /**
     * Преди изтриване
     */
    public static function on_AfterDelete($mvc, &$numRows, $query, $cond)
    {
    	foreach ($query->getDeletedRecs() as $rec) {
    		pos_ReceiptDetails::delete("#receiptId = {$rec->id}");
    	}
    }
}
