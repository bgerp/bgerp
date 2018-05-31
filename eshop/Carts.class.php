<?php



/**
 * Мениджър за кошница на онлайн магазина
 *
 *
 * @category  bgerp
 * @package   eshop
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class eshop_Carts extends core_Master
{
    
    
    /**
     * Заглавие
     */
    public $title = "Кошници на онлайн магазина";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, eshop_Wrapper, plg_Rejected, plg_Modified';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'ip,brid,domainId,userId,saleId,activatedOn,state';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Кошница на онлайн магазина";
    
    
    /**
     * Кои полета ще извличаме, преди изтриване на заявката
     */
    public $fetchFieldsBeforeDelete = 'id';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'eshop,ceo,admin';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'eshop,ceo,admin';
    
    
	/**
	 * Кой може да го разглежда?
	 */
	public $canViewexternal = 'every_one';
	
	
	/**
	 * Кой може да финализира поръчката?
	 */
	public $canFinalize = 'every_one';
	
	
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'eshop,ceo,admin';
    
    
    /**
     * Кой може да добавя в кошницата
     */
    public $canAddtocart = 'every_one';
    
    
    /**
     * Кой може да чекаутва
     */
    public $canCheckout = 'every_one';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'eshop_CartDetails';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('ip', 'varchar', 'caption=Ип,input=none');
    	$this->FLD('brid', 'varchar(8)', 'caption=Браузър,input=none');
    	$this->FLD('domainId', 'key(mvc=cms_Domains, select=domain)', 'caption=Брид,input=none');
    	$this->FLD('userId', 'key(mvc=core_Users, select=nick)', 'caption=Потребител,input=none');
    	$this->FLD('deliveryNoVat', 'double(decimals=2)', 'caption=Общи данни->Стойност,input=none');
    	$this->FLD('deliveryTime', 'time', 'caption=Общи данни->Срок на доставка,input=none');
    	$this->FLD('total', 'double(decimals=2)', 'caption=Общи данни->Стойност,input=none');
    	$this->FLD('totalNoVat', 'double(decimals=2)', 'caption=Общи данни->Стойност без ДДС,input=none');
    	$this->FLD('productCount', 'int', 'caption=Общи данни->Брой,input=none');
    	
    	$this->FLD('personNames', 'varchar(255)', 'caption=Контактни данни->Имена,class=contactData,hint=Вашето име||Your name,mandatory');
    	$this->FLD('email', 'email(valid=drdata_Emails->validate)', 'caption=Контактни данни->Имейл,hint=Вашият имейл||Your email,mandatory');
    	$this->FLD('tel', 'drdata_PhoneType(type=tel)', 'caption=Контактни данни->Тел,hint=Вашият телефон,mandatory');
    	
    	$this->FLD('termId', 'key(mvc=cond_DeliveryTerms,select=codeName,allowEmpty)', 'caption=Доставка->Начин,mandatory,removeAndRefreshForm,silent');
    	$this->FLD('deliveryData', 'blob(serialize, compress)', 'input=none');
    	$this->FLD('instruction', 'richtext(rows=2)', 'caption=Доставка->Инструкции');
    	
    	$this->FLD('paymentId', 'key(mvc=cond_PaymentMethods,select=title,allowEmpty)', 'caption=Плащане->Начин,mandatory');
    	$this->FLD('makeInvoice', 'enum(none=Без фактуриране,person=Фактура на лице, company=Фактура на фирма)', 'caption=Плащане->Фактуриране,silent,removeAndRefreshForm=saleFolderId|invoiceNames|invoiceVatNo|invoiceAddress|invoicePCode|invoicePlace|invoiceCountry');
    	
    	$this->FLD('saleFolderId', 'key(mvc=doc_Folders)', 'caption=Данни за фактура->Папка,input=hidden,silent,removeAndRefreshForm=invoiceNames|invoiceVatNo|invoiceAddress|invoicePCode|invoicePlace|invoiceCountry');
    	$this->FLD('invoiceNames', 'varchar(128)', 'caption=Данни за фактура->Наименование,invoiceData,hint=Име,input=none,mandatory');
    	$this->FLD('invoiceVatNo', 'drdata_VatType', 'caption=Данни за фактура->VAT/EIC,input=hidden,mandatory,invoiceData');
    	$this->FLD('invoiceCountry', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Данни за фактура->Държава,hint=Фирма на държавата,input=none,mandatory,invoiceData');
    	$this->FLD('invoicePCode', 'varchar(16)', 'caption=Данни за фактура->П. код,invoiceData,hint=Пощенски код на фирмата,input=none,mandatory');
    	$this->FLD('invoicePlace', 'varchar(64)', 'caption=Данни за фактура->Град,invoiceData,hint=Населено място: град или село и община,input=none,mandatory');
    	$this->FLD('invoiceAddress', 'varchar(255)', 'caption=Данни за фактура->Адрес,invoiceData,hint=Адрес на регистрация на фирмата,input=none,mandatory');
    	
    	$this->FLD('info', 'richtext(rows=2)', 'caption=Общи данни->Забележка,input=none');
    	$this->FLD('state', 'enum(draft=Чернова,active=Активно,closed=Приключено,rejected=Оттеглен)', 'caption=Състояние,input=none,notNull,value=active');
    	$this->FLD('saleId', 'key(mvc=sales_Sales)', 'caption=Продажба,input=none');
    	$this->FLD('activatedOn', 'datetime(format=smartTime)', 'caption=Активиране||Activated->На,input=none');
    	
    	$this->setDbIndex('brid');
    	$this->setDbIndex('userId');
    	$this->setDbIndex('domainId');
    }
    
    
    /**
     * Екшън за добавяне на артикул в кошницата
     * @return multitype:
     */
    public function act_addToCart()
    {
    	// Взимане на данните от заявката
    	$this->requireRightFor('addtocart');
    	$eshopProductId = Request::get('eshopProductId', 'int');
    	$productId = Request::get('productId', 'int');
    	$packagingId = Request::get('packagingId', 'int');
    	$packQuantity = Request::get('packQuantity', 'double');
    	
    	// Данните от опаковката
    	if(isset($productId)){
    		$packRec = cat_products_Packagings::getPack($productId, $packagingId);
    		$quantityInPack = (is_object($packRec)) ? $packRec->quantity : 1;
    		$canStore = cat_Products::fetchField($productId, 'canStore');
    		
    		// Проверка на к-то
    		if(!deals_Helper::checkQuantity($packagingId, $packQuantity, $warning)){
    			$msg = $warning;
    			$success = FALSE;
    			$skip = TRUE;
    		}
    	}
    	
    	// Ако има избран склад, проверка дали к-то е допустимо
    	$msg = '|Проблем при добавянето на артикулът|*!';
    	$settings = cms_Domains::getSettings();
    	if(isset($settings->storeId) &&  $canStore == 'yes'){
    		$quantity = store_Products::getQuantity($productId, $settings->storeId, TRUE);
    		if($quantity < $quantityInPack * $packQuantity){
    			$msg = '|Избраното количество не е налично|* ' . $quantity . " > $quantityInPack > $packQuantity" ;
    			$success = FALSE;
    			$skip = TRUE;
    		}
    	}
    	
    	$success = FALSE;
    	if(!empty($eshopProductId) && !empty($productId) && !empty($packQuantity) && $skip !== TRUE){
    		try{
    			// Форсиране на кошница и добавяне на артикула в нея
    			$cartId = self::force();
    			$this->requireRightFor('addtocart', $cartId);
    			eshop_CartDetails::addToCart($cartId, $eshopProductId, $productId, $packagingId, $packQuantity, $quantityInPack);
    			$this->updateMaster($cartId);
    			$msg = '|Артикулът е добавен|*!';
    			$success = TRUE;
    		} catch(core_exception_Expect $e){
    			reportException($e);
    			$msg = '|Артикулът не е добавен|*!';
    		}
    	}
    	
    	// Ако режимът е за AJAX
    	if (Request::get('ajax_mode')) {
    		
    		// Пушване на езика от публичната част
    		$lang = cms_Domains::getPublicDomain('lang');
    		core_Lg::push($lang);
    		
    		core_Statuses::newStatus($msg, ($success === TRUE) ? 'notice' : 'error');
    		
    		// Ще се реплейсне статуса на кошницата
    		$resObj = new stdClass();
    		$resObj->func = "html";
    		$resObj->arg = array('id' => 'cart-external-status', 'html' => self::getStatus($cartId)->getContent(), 'replace' => TRUE);
    	
    		$hitTime = Request::get('hitTime', 'int');
    		$idleTime = Request::get('idleTime', 'int');
    		$statusData = status_Messages::getStatusesData($hitTime, $idleTime);
    		 
    		$res = array_merge(array($resObj), (array)$statusData);
    		core_Lg::pop();
    		
    		return $res;
    	}
    	
    	return followRetUrl();
    }
    
    
    /**
     * Форсира чернова на нова кошница
     * 
     * @param int|NULL $userId    - потребител (ако има)
     * @param int|NULL $domainId  - домейн, ако не е подаден се взима от менюто в което е групата
     * @param boolean  $bForce    - да форсира ли нова кошница, ако няма
     * @return int|NULL           - ид на кошницата
     */
    public static function force($domainId = NULL, $userId = NULL, $bForce = TRUE)
    {
    	// Дефолтни данни
    	$userId = isset($userId) ? $userId : core_Users::getCurrent('id', FALSE);
    	$domainId = isset($domainId) ? $domainId : cms_Domains::getPublicDomain()->id;
    	$brid = log_Browsers::getBrid();
    	
    	// Ако има потребител се търси имали чернова кошница за този потребител, ако не е логнат се търси по Брид-а
    	$where = (isset($userId)) ? "#userId = '{$userId}'" : "#userId IS NULL AND #brid = '{$brid}'";
    	$rec = self::fetch("{$where} AND #state = 'draft' AND #domainId = {$domainId}");
    	
    	if(empty($rec) && $bForce === TRUE){
    		$ip = core_Users::getRealIpAddr();
    		$rec = (object)array('ip' => $ip,'brid' => $brid, 'domainId' => $domainId, 'userId' => $userId, 'state' => 'draft', 'productCount' => 0);
    		self::save($rec);
    	}
    	
    	return $rec->id;
    }
    
    function act_Test()
    {
    	$this->updateMaster(24);
    }
    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     * @return int $id ид-то на обновения запис
     */
    public function updateMaster_($id)
    {
    	$rec = $this->fetchRec($id);
    	if(!$rec) return;
    	
    	$rec->productCount = $rec->total = $rec->totalNoVat = 0;
    	$rec->deliveryNoVat = $rec->deliveryTime = NULL;
    	
    	$dQuery = eshop_CartDetails::getQuery();
    	$dQuery->where("#cartId = {$rec->id}");
    	
    	// Ако има цена за доставка добавя се и тя
    	if($dQuery->count()){
    		if($delivery = eshop_CartDetails::getDeliveryInfo($rec)){
    			if($delivery['amount'] > 0){
    				$rec->deliveryTime = $delivery['deliveryTime'];
    				$settings = cms_Domains::getSettings();
    				$delivery = currency_CurrencyRates::convertAmount($delivery['amount'], NULL, NULL, $settings->currencyId);
    				$rec->deliveryNoVat = $delivery;
    				$rec->totalNoVat += $rec->deliveryNoVat;
    				 
    				$transportId = cat_Products::fetchField("#code = 'transport'", 'id');
    				$rec->total += $delivery * (1 + cat_Products::getVat($transportId));
    			} else {
    				$rec->deliveryNoVat = -1;
    			}
    		}
    	}
    	
    	
    	while($dRec = $dQuery->fetch()){
    		$rec->productCount++;
    		$finalPrice = currency_CurrencyRates::convertAmount($dRec->finalPrice, NULL, $dRec->currencyId);
    		if(!$dRec->discount){
    			$finalPrice -= $finalPrice * $dRec->discount;
    		}
    		$sum = $finalPrice * ($dRec->quantity / $dRec->quantityInPack);
    		
    		if($dRec->haveVat == 'yes'){
    			$rec->totalNoVat += round($sum / (1 + $dRec->vat), 2);
    			$rec->total += round($sum, 2);
    		} else {
    			$rec->totalNoVat += round($sum, 2);
    			$rec->total += round($sum * (1 + $dRec->vat), 2);
    		}
    	}
    	
    	$rec->totalNoVat = round($rec->totalNoVat, 2);
    	$rec->total = round($rec->total, 2);
    	
    	$id = $this->save_($rec, 'productCount,total,totalNoVat,deliveryNoVat,deliveryTime');
    	
    	return $id;
    }
    
    
    /**
     * Име на кошницата във външната част
     * 
     * @return varchar
     */
    public static function getCartDisplayName()
    {
    	$settings = cms_Domains::getSettings();
    	$cartName = !empty($settings->cartName) ? $settings->cartName : tr(eshop_Setup::get('CART_EXTERNAL_NAME'));
    	
    	return $cartName;
    }
    
    
    /**
     * Какъв е статуса на кошницата
     */
    public static function getStatus($cartId = NULL)
    {
    	$tpl = new core_ET("[#text#]");
    	
    	$settings = cms_Domains::getSettings();
    	if(empty($settings)) return new core_ET(' ');
    	
    	$cartId = ($cartId) ? $cartId : self::force(NULL, NULL, FALSE);
		$url = array('eshop_Carts', 'force');
    	
    	if(isset($cartId)){
    		$cartRec = self::fetch($cartId);
    		if($settings->enableCart == 'no'){
    			if(!$cartRec->productCount) return new core_ET(' ');
    		}
    		
    		$amount = core_Type::getByName('double(decimals=2)')->toVerbal($cartRec->total);
    		$amount = str_replace('&nbsp;', ' ', $amount);
    		$count = core_Type::getByName('int')->toVerbal($cartRec->productCount);
    		$url = array('eshop_Carts', 'view', $cartId);
    		$str = ($count == 1) ? 'артикул' : 'артикула';
    		$hint = "|*{$count} |{$str} за|* {$amount} " . $settings->currencyId;
    	
    		if($count){
    			$tpl->append(new core_ET("<span class='count'>[#count#]</span>"));
    		}
    	} else {
    		if($settings->enableCart == 'no') return new core_ET(' ');
    	}
    	
    	// Пушване на езика от публичната част
    	$lang = cms_Domains::getPublicDomain('lang');
    	core_Lg::push($lang);
    	
    	$cartName = self::getCartDisplayName();
    	$tpl->replace($cartName, 'text');
    	$tpl->replace($count, 'count');
    	$tpl = ht::createLink($tpl, $url, FALSE, "title={$hint}, ef_icon=img/16/cart-black.png");

		$tpl->removeBlocks();
    	$tpl->removePlaces();

    	core_Lg::pop();
    	
    	return $tpl;
    }
    
    
    /**
     * Екшън за форсиране на количка
     */
    public function act_Force()
    {
    	$cartId = self::force();
    	 
    	redirect(array($this, 'view', $cartId, 'ret_url' => TRUE));
    }
    
    
    /**
     * Финализиране на поръчката
     */
    public function act_Finalize()
    {
    	$this->requireRightFor('finalize');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = self::fetch($id));
    	$this->requireRightFor('finalize', $rec);
    	
    	$company = NULL;
    	$personNames = $rec->personNames;
    	if($rec->makeInvoice == 'company'){
    		$company = $rec->invoiceNames;
    	} elseif($rec->makeInvoice == 'person'){
    		$personNames = $rec->invoiceNames;
    	}
    	
    	// Рутиране в папка
    	$folderId = isset($rec->saleFolderId) ? $rec->saleFolderId : marketing_InquiryRouter::route($company, $personNames, $rec->email, $rec->tel, $rec->invoiceCountry, $rec->invoicePCode, $rec->invoicePlace, $rec->invoiceAddress, $rec->brid);
    	$Cover = doc_Folders::getCover($folderId);
    	$settings = cms_Domains::getSettings();
    	
    	// Дефолтни данни на продажбата
    	$fields = array('valior'           => dt::today(), 
    			        'deliveryTermId'   => $rec->termId, 
    			        'deliveryTermTime' => $rec->deliveryTime, 
    			        'paymentMethodId'  => $rec->paymentId, 
    			        'makeInvoice'      => ($rec->makeInvoice == 'none') ? 'no' : 'yes',
    					'chargeVat'        => $settings->chargeVat,
    					'currencyId'       => $settings->currencyId,
    					'shipmentStoreId'  => $settings->storeId,
    	);
    	
    	// Създаване на продажба по количката
   		$saleId = sales_Sales::createNewDraft($Cover->getClassId(), $Cover->that, $fields);
   		sales_SalesDetails::delete("#saleId = {$saleId}");
   		
   		// Добавяне на артикулите от количката в продажбата
   		$dQuery = eshop_CartDetails::getQuery();
   		$dQuery->where("#cartId = {$id}");
   		while($dRec = $dQuery->fetch()){
   			$price = isset($dRec->discount) ? ($dRec->finalPrice / (1 - $dRec->discount)) : $dRec->finalPrice;
   			$price = $price / $dRec->quantityInPack;
   			if($dRec->haveVat == 'yes'){
   				$price /= 1 + $dRec->vat;
   			}
   			
   			$price = currency_CurrencyRates::convertAmount($price, NULL, $dRec->currencyId);
   			$price = round($price, 2);
   			
   			sales_Sales::addRow($saleId, $dRec->productId, $dRec->packQuantity, $price, $dRec->packagingId, $dRec->discount);
   		}
   		
   		// Добавяне на транспорта, ако има
   		if(isset($rec->deliveryNoVat) && $rec->deliveryNoVat > 0){
   			$transportId = cat_Products::fetchField("#code = 'transport'", 'id');
   			sales_Sales::addRow($saleId, $transportId, 1, $rec->deliveryNoVat);
   		}
   		
   		// Продажбата става на заявка
   		$saleRec = sales_Sales::fetch($saleId);
   		$saleRec->state = 'pending';
   		$saleRec->brState = 'draft';
   		$saleRec->pendingSaved = TRUE;
   		sales_Sales::save($saleRec, 'state');
   		
   		// Активиране на количката
   		$rec->saleId = $saleId;
   		$rec->state = 'active';
   		$rec->activatedOn = dt::now();
   		self::save($rec, 'state,saleId,activatedOn');
   		
   		// Ако е партньор и има достъп до нишката, директно се реидректва към нея
   		if(core_Packs::isInstalled('colab') && core_Users::isContractor()){
   			doc_Threads::doUpdateThread($saleRec->threadId);
   			$threadRec = doc_Threads::fetch($saleRec->threadId);
   			
   			if(colab_Threads::haveRightFor('single', $threadRec)){
   				return new Redirect(array('colab_Threads', 'single', 'threadId' => $saleRec->threadId), 'Успешно създадена заявка за продажба');
   			}
   		}
    }
    
    
    /**
     * След изтриване
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param core_Query $query
     */
    protected static function on_AfterDelete($mvc, &$res, $query)
    {
        // Ако се изтрие кошницата изтруват се и детайлите
    	foreach ($query->getDeletedRecs() as $rec) {
        	eshop_CartDetails::delete("#cartId = {$rec->id}");
        }
    }
    
    
    /**
     * Рендира изгледа
     * 
     * @param stdClass $rec
     * @return core_ET $tpl
     */
    public static function renderView($rec)
    {
    	$rec = self::fetchRec($rec);
    	$lang = cms_Domains::getPublicDomain('lang');
    	core_Lg::push($lang);
    	
    	$tpl = getTplFromFile("eshop/tpl/SingleLayoutCartExternal.shtml");
    	$tpl->replace(self::renderViewCart($rec), 'CART_TABLE');
    	
    	self::renderCartToolbar($rec, $tpl);
    	self::renderCartSummary($rec, $tpl);
    	self::renderCartOrderInfo($rec, $tpl);
    	$tpl->replace(self::getCartDisplayName(), 'CART_NAME');
    	$settings = cms_Domains::getSettings();
    	 
    	if(!empty($settings->info)){
    		$tpl->replace(core_Type::getByName('richtext')->toVerbal($settings->info), 'COMMON_TEXT');
    	}
    	
    	$cartInfo = tr('Всички цени са в') . " {$settings->currencyId}, " . (($settings->chargeVat == 'yes') ? tr('с ДДС') : tr('без ДДС'));
    	$tpl->replace($cartInfo, 'VAT_STATUS');
    	
    	core_Lg::pop();
    	
    	return $tpl;
    }
    
    
    /**
     * Екшън за показване на външния изглед на кошницата
     */
    public function act_View()
    {
    	$this->requireRightFor('viewexternal');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = self::fetch($id));
    	$this->requireRightFor('viewexternal', $rec);
    	
    	$tpl = self::renderView($rec);
    	$tpl->prepend("<div id = 'cart-view-single'>");
    	$tpl->append('</div>');
    	
    	Mode::set('wrapper', 'cms_page_External');
    	
    	return $tpl;
    }
    
    
    /**
     * Рендира информация за поръчката
     * 
     * @param mixed $rec
     * @param core_ET $tpl
     * @return void
     */
    private static function renderCartOrderInfo($rec, core_ET &$tpl)
    {
    	$rec = self::fetchRec($rec);
    	
    	$row = self::recToVerbal($rec);
    	$tpl->replace($row->termId, 'termId');
    	$tpl->replace($row->paymentId, 'paymentId');
    	if($Driver = cond_DeliveryTerms::getTransportCalculator($rec->termId)){
    		$tpl->replace($Driver->renderDeliveryInfo($rec), 'DELIVERY_BLOCK');
    	}
    	
    	if($companyFolderId = core_Mode::get('lastActiveCompanyFolder')){
    		if(colab_Threads::haveRightFor('list', (object)array('folderId' => $companyFolderId))){
    			$folderTitle = doc_Folders::getVerbal($companyFolderId, 'title');
				$activeFolderId = ht::createLink($folderTitle, array('colab_Threads', 'list', 'folderId' => $companyFolderId), FALSE, 'ef_icon=img/16/folder-icon.png');
				$tpl->append($activeFolderId, 'activeFolderId');
    		}
    	}
    	
    	if(self::haveRightFor('checkout', $rec)){
    		$editSaleBtn = ht::createLink('', array($this, 'order', $rec->id, 'ret_url' => TRUE), FALSE, 'ef_icon=img/16/edit.png,title=Редактиране на информацията за поръчката');
    		$tpl->append($editSaleBtn, 'saleEditBtn');
    	}
    	
    	if($rec->makeInvoice != 'none'){
    		$countryVerbal = core_Type::getByName('key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg)')->toVerbal($rec->invoiceCountry);
    		$tpl->replace($countryVerbal, 'invoiceCountry');
    		foreach (array('invoiceNames', 'invoiceVatNo', 'invoicePCode', 'invoicePlace', 'invoiceAddress') as $name){
    			$tpl->replace(core_Type::getByName('varchar')->toVerbal($rec->{$name}), $name);
    		}
    		 
    		$nameCaption = ($rec->makeInvoice == 'person') ? 'Лице' : 'Фирма';
    		$tpl->replace(tr($nameCaption), 'INV_CAPTION');
    		if(!empty($rec->invoiceVatNo)){
    			$vatCaption = ($rec->makeInvoice == 'person') ? 'ЕГН' : 'VAT/EIC';
    			$tpl->replace(tr($vatCaption), 'VAT_CAPTION');
    		}
    	} else {
    		$tpl->replace(tr('Без фактура'), 'NO_INVOICE');
    	}
    	
    	if($rec->deliveryNoVat < 0){
    		$tpl->replace(tr('Има проблем при изчислението на доставката. Моля, обърнете се към нас!'), 'deliveryError');
    	}
    	
    	if(!empty($rec->instruction)){
    		$tpl->replace($row->instruction, 'instruction');
    	}
    	
    	if(isset($rec->deliveryTime)){
    		$deliveryTime = dt::mysql2verbal(dt::addSecs($rec->deliveryTime, NULL, FALSE), 'd.m.Y');
    		$tpl->replace($deliveryTime, 'deliveryTime');
    	}
    	
    	if(eshop_Carts::haveRightFor('checkout', $rec) && $rec->personNames) {
    		$editBtn = ht::createLink('', array(eshop_Carts, 'order', $rec->id, 'ret_url' => TRUE), FALSE, 'ef_icon=img/16/edit.png,title=Редактиране на данните за поръчка');
    		$tpl->append($editBtn, 'editBtn');
    	}
    	
    	return $tpl;
    }
    
    
    /**
     * Рендира съмарито на поръчката
     * 
     * @param mixed $rec
     * @param core_ET $tpl
     * @return void
     */
    private static function renderCartSummary($id, core_ET $tpl)
    {
    	$rec = self::fetchRec($id, '*', FALSE);
    	$row = self::recToVerbal($rec);
    	$settings = cms_Domains::getSettings();
    	
    	$total = currency_CurrencyRates::convertAmount($rec->total, NULL, NULL, $settings->currencyId);
    	$totalNoVat = currency_CurrencyRates::convertAmount($rec->totalNoVat, NULL, NULL, $settings->currencyId);
    	$vatAmount = $total - $totalNoVat;
    	
    	$Double = core_Type::getByName('double(decimals=2)');
    	$row->totalNoVat = $Double->toVerbal($totalNoVat);
    	$row->total = $Double->toVerbal($total);
    	$row->totalVat = $Double->toVerbal($vatAmount);
    	
    	$row->currencyId = $settings->currencyId;
    	$row->totalNoVatCurrencyId = $row->vatCurrencyId = $row->currencyId;
    	$row->productCount .= "&nbsp;" . (($rec->productCount == 1) ? tr('артикул') : tr('артикула'));
    	
    	$tpl->placeObject($row);
    	
    	return $tpl;
    }
    
    
    /**
     * Рендиране на тулбара към кошницата
     *
     * @param mixed $id    - ид или запис
     * @param core_ET $tpl - шаблон
     * @return void        
     */
    private static function renderCartToolbar($id, core_ET &$tpl)
    {
    	$rec = self::fetchRec($id);
    	$shopUrl = cls::get('eshop_Groups')->getUrlByMenuId(NULL);
    	
    	if(!empty($rec->productCount) && eshop_CartDetails::haveRightFor('removeexternal', (object)array('cartId' => $rec->id))){
    		$emptyUrl = array('eshop_CartDetails', 'removeexternal', 'cartId' => $rec->id, 'ret_url' => $shopUrl);
    		$btn = ht::createLink('Изчисти', $emptyUrl, NULL, 'title=Премахване на артикулите,class=eshop-link,ef_icon=img/16/deletered.png');
    		$tpl->append($btn, 'EMPTY_CART');
    	}
    	
    	if(eshop_CartDetails::haveRightFor('add', (object)array('cartId' => $rec->id))){
    		$addUrl = array('eshop_CartDetails', 'add', 'cartId' => $rec->id, 'external' => TRUE, 'ret_url' => TRUE);
    		$btn = ht::createLink('Добавяне на артикул', $addUrl, NULL, 'title=Добавяне на нов артикул,class=eshop-link,ef_icon=img/16/add1-16.png');
    		$tpl->append($btn, 'CART_TOOLBAR_LEFT');
    	}
    	
    	$btn = ht::createLink('Продължи пазаруването', $shopUrl, NULL, 'title=Към онлайн магазина,class=eshop-link,ef_icon=img/16/cart_go.png');
    	$tpl->append($btn, 'CART_TOOLBAR_LEFT');
    	
    	$checkoutUrl = (eshop_Carts::haveRightFor('checkout', $rec)) ? array(eshop_Carts, 'order', $rec->id, 'ret_url' => TRUE) : array();
    	if(empty($rec->personNames)){
    		$btn = ht::createBtn('Данни за поръчка', $checkoutUrl, NULL, NULL, "title=Поръчване на артикулите,class=order-btn eshop-btn {$disabledClass}");
    		$tpl->append($btn, 'CART_TOOLBAR_RIGHT');
    	}
    	
    	if(isset($rec->paymentId)){
    		//$paymentUrl = cond_PaymentMethods::getPaymentUrl($rec->paymentId);
    		//$btn = ht::createBtn('Плащане', $paymentUrl, NULL, NULL, "title=Плащане на поръчката,class=order-btn eshop-btn {$disabledClass}");
    		//$tpl->append($btn, 'CART_TOOLBAR_RIGHT');
    	}
    	
    	if(eshop_Carts::haveRightFor('finalize', $rec)){
    		$btn = ht::createBtn('Финализиране', array('eshop_Carts', 'finalize', $rec->id), 'Сигурни ли сте че искате да завършите поръчката|*!', NULL, "title=Финализиране на поръчката,class=order-btn eshop-btn {$disabledClass}");
    		$tpl->append($btn, 'CART_TOOLBAR_RIGHT');
    	}
    }
    
    
    /**
     * Рендиране на изгледа на кошницата във външната част
     *
     * @param mixed $rec
     * @return core_ET $tpl      - шаблон на съмарито
     */
    private static function renderViewCart($rec)
    {
    	$rec = self::fetchRec($rec);
    	
    	$tpl = new core_ET("");
    	$row = self::recToVerbal($rec);
    	$data = (object)array('rec' => $rec, 'row' => $row);
    	self::prepareExternalCart($data);
    	$tpl = self::renderExternalCart($data);
    	
    	return $tpl;
    }
    
    
    /**
     * Подготовка на данните на кошницата
     *
     * @param stdClass $data
     * @return core_ET $tpl  - шаблон на съмарито
     */
    private static function prepareExternalCart($data)
    {
    	$fields = cls::get('eshop_CartDetails')->selectFields();
    	$fields['-external'] = TRUE;
    	$data->listFields = arr::make("code=Код,productId=Артикул,quantity=К-во,finalPrice=Цена,amount=Сума");
    	$settings = cms_Domains::getSettings();
    	
    	$data->productRecs = $data->productRows = array();
    	$dQuery = eshop_CartDetails::getQuery();
    	$dQuery->where("#cartId = {$data->rec->id}");
    	while($dRec = $dQuery->fetch()){
    		$data->recs[$dRec->id] = $dRec;
    		$row = eshop_CartDetails::recToVerbal($dRec, $fields);
    		
    		if(!empty($dRec->discount)){
    			$discountType = type_Set::toArray($settings->discountType);
				$row->finalPrice .= "<div class='external-discount'>";
    			
    			if(isset($discountType['amount'])){
    				$amountWithoutDiscount = $dRec->finalPrice / (1 - $dRec->discount);
    				$discountAmount = core_Type::getByName('double(decimals=2)')->toVerbal($amountWithoutDiscount);
    				$row->finalPrice .= "<div class='external-discount-amount'> {$discountAmount}</div>";
    			}
				
				if(isset($discountType['amount']) && isset($discountType['percent'])) {
					$row->finalPrice .= " / ";
				}
    			
    			if(isset($discountType['percent'])){
    				$discountPercent = core_Type::getByName('percent(smartRound)')->toVerbal($dRec->discount);
    				$discountPercent = str_replace('&nbsp;', '', $discountPercent);
    				$row->finalPrice .= "<div class='external-discount-percent'> -{$discountPercent}</div>";
    			}
				
    			$row->finalPrice .= "</div>";
    		}
    		
    		$fullCode = cat_products::getVerbal($dRec->productId, 'code');
    		$row->code = substr($fullCode, 0, 10);
    		$row->code = "<span title={$fullCode}>{$row->code}</span>";
    		
    		$data->rows[$dRec->id] = $row;
    	}
    	
    	// Ако има доставка
    	if(isset($data->rec->deliveryNoVat) && $data->rec->deliveryNoVat >= 0){
    		
    		// Показва се сумата на доставка
    		$transportId = cat_Products::fetchField("#code = 'transport'", 'id');
    		$deliveryAmount = $data->rec->deliveryNoVat * (1 + cat_Products::getVat($transportId));
    		$deliveryAmount = currency_CurrencyRates::convertAmount($deliveryAmount, NULL, NULL, $settings->currencyId);
    		$deliveryAmount = core_Type::getByName('double(decimals=2)')->toVerbal($deliveryAmount);
    			
    		$data->rows['-1'] = (object)array('productId' => "<b>" . tr('Доставка') . "</b>", 'amount' => "<b>" . $deliveryAmount. "</b>");
    	}
    }
    
    
    /**
     * Рендиране на данните на кошницата
     *
     * @param stdClass $data
     * @return core_ET $tpl  - шаблон на съмарито
     */
    private static function renderExternalCart($data)
    {
    	$tpl = new core_ET('');
    	
    	$data->listTableMvc = cls::get('eshop_CartDetails');
    	$data->listTableMvc->FNC('code', 'varchar', 'smartCenter');
    	$data->listTableMvc->setFieldType('quantity', core_Type::getByName('varchar'));
    	$data->listTableMvc->setField('quantity', 'tdClass=quantity-input-column');
    	$table = cls::get('core_TableView', array('mvc' => $data->listTableMvc, 'tableClass' => 'optionsTable', 'tableId' => 'cart-view-table'));
    	plg_RowTools2::on_BeforeRenderListTable($data->listTableMvc, $tpl, $data);
    	$tpl->replace($table->get($data->rows, $data->listFields));
    	
    	return $tpl;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'viewexternal' && isset($rec)){
    		if($rec->state != 'draft'){
    			$requiredRoles = 'no_one';
    		} elseif(isset($userId) && $rec->userId != $userId){
    			$requiredRoles = 'no_one';
    		} elseif(!isset($userId)) {
    			$brid = log_Browsers::getBrid();
    			if(!(empty($rec->userId) && $rec->brid == $brid)){
    				$requiredRoles = 'no_one';
    			}
    		}
    		
    		if($requiredRoles != 'no_one'){
    			$settings = cms_Domains::getSettings();
    			if(empty($settings)){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    	
    	if(in_array($action, array('addtocart', 'checkout', 'finalize'))){
    		if(!$mvc->haveRightFor('viewexternal', $rec)){
    			$requiredRoles = 'no_one';
    		}
    	}
    	
    	if($action == 'checkout' && isset($rec)){
    		if(empty($rec->productCount)){
    			$requiredRoles = 'no_one';
    		}
    	}
    	
    	if($action == 'finalize' && isset($rec)){
    		if(empty($rec->personNames)){
    			$requiredRoles = 'no_one';
    		} elseif($rec->deliveryNoVat < 0){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    protected static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
    	$row->ROW_ATTR['class'] = "state-{$rec->state}";
    	
    	if(isset($rec->saleId)){
    		$row->saleId = sales_Sales::getLink($rec->saleId, 0);
    	}
    }
    
    
    /**
     * Екшън за показване на външния изглед на кошницата
     */
    public function act_Order()
    {
    	$lang = cms_Domains::getPublicDomain('lang');
    	core_Lg::push($lang);
    	
    	$this->requireRightFor('checkout');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = self::fetch($id));
    	$this->requireRightFor('checkout', $rec);
    	
    	$form = $this->getForm();
    	$form->rec = $rec;
    	$form->title = 'Данни за поръчка';
    	
    	if($cu = core_Users::getCurrent('id', FALSE)){
    		
    		// Има ли споделени папки контрактора
    		$options = colab_Folders::getSharedFolders($cu, TRUE, 'crm_CompanyAccRegIntf');
    		
    		// Задаване като опции
    		if(count($options)){
    			$form->setDefault('makeInvoice', 'company');
    			$form->setField('makeInvoice', 'input=hidden');
    			$form->setField('saleFolderId', 'input,mandatory');
    			$form->setOptions('saleFolderId', $options);
    			
    			// Коя папка е избрана по дефолт
    			$companyFolderId = core_Mode::get('lastActiveCompanyFolder');
    			$defaultFolder = ($companyFolderId) ? $companyFolderId : key($options);
    			$form->setDefault('saleFolderId', $defaultFolder);
    		}
    	}
    	
    	$deliveryTerms = eshop_Settings::getDeliveryTermOptions('cms_Domains', cms_Domains::getPublicDomain()->id);
    	if(count($deliveryTerms) == 1){
    		$form->setDefault('termId', key($deliveryTerms));
    	} else {
    		$deliveryTerms = array('' => '') + $deliveryTerms;
    	}
    	$form->setOptions('termId', $deliveryTerms);
    	
    	$makeInvoice = bgerp_Setup::get('MANDATORY_CONTACT_FIELDS');
    	if(in_array($makeInvoice, array('company', 'both'))){
    		$form->setDefault('makeInvoice', 'company');
    		$form->setField('makeInvoice', 'input=hidden');
    	}
    	
    	$form->input(NULL, 'silent');
    	if(isset($form->rec->saleFolderId)){
    		
    		// Ако има избрана папка записване на контрагент данните
    		if($contragentData = doc_Folders::getContragentData($form->rec->saleFolderId)){
    			$form->setDefault('invoiceNames', $contragentData->company);
    			$form->setDefault('invoiceVatNo', $contragentData->vatNo);
    			$form->setDefault('invoiceCountry', $contragentData->countryId);
    			$form->setDefault('invoicePCode', $contragentData->pCode);
    			$form->setDefault('invoicePlace', $contragentData->place);
    			$form->setDefault('invoiceAddress', $contragentData->address);
    		}
    	}
    	
    	// Ако има условие на доставка то драйвера му може да добави допълнителни полета
    	if(isset($form->rec->termId)){
    		if($Driver = cond_DeliveryTerms::getTransportCalculator($form->rec->termId)){
    			$Driver->addFields($form);
    			$fields = $Driver->getFields();
    			foreach ($fields as $fld){
    				$form->setDefault($fld, $form->rec->deliveryData[$fld]);
    			}
    		}
    	}
    	
    	$invoiceFields = $form->selectFields("#invoiceData");
    	if($form->rec->makeInvoice != 'none'){
    			
    		// Ако има ф-ра полетата за ф-ра се показват
    		foreach ($invoiceFields as $name => $fld){
    			$form->setField($name, 'input');
    		}
    		if($form->rec->makeInvoice == 'person'){
    			$form->setField('invoiceNames', "caption=Данни за фактура->Име");
    			$form->setField('invoiceVatNo', "caption=Данни за фактура->ЕГН");
    			$form->setFieldType('invoiceVatNo', 'bglocal_EgnType');
    		} else {
    			$form->setField('invoiceNames', "caption=Данни за фактура->Фирма");
    			$form->setField('invoiceVatNo', "caption=Данни за фактура->VAT/EIC");
    		}
    		$vatCaption = ($form->rec->makeInvoice == 'person') ? 'ЕГН' : 'VAT/EIC';
    	} else {
    		foreach ($invoiceFields as $name => $fld){
    			$form->setField($name, 'input=none');
    		}
    	}
    	
    	$form->input();
    	if($Driver){
    		$Driver->checkForm($form);
    	}
    	
    	if($form->isSubmitted()){
    		$rec = $form->rec;
    		
    		// Компресиране на данните за доставка от драйвера
    		$rec->deliveryData = array();
    		if($Driver){
    			if(!$form->gotErrors()){
    				$fields = $Driver->getFields();
    				foreach ($fields as $name){
    					$rec->deliveryData[$name] = $rec->{$name};
    				}
    			}
    		}
    		
    		$this->save($rec);
    		$this->updateMaster($rec);
    		core_Lg::pop();
    		
    		return followRetUrl();
    	}
    	
    	Mode::set('wrapper', 'cms_page_External');
    	 
    	// Добавяне на бутони
    	$form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png, title = Запис на адресните данни');
    	$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
    	
    	$tpl = $form->renderHtml();
    	core_Form::preventDoubleSubmission($tpl, $form);
    	core_Lg::pop();
    	
    	return $tpl;
    }
}