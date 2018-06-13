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
    	
    	$this->FLD('termId', 'key(mvc=cond_DeliveryTerms,select=codeName,allowEmpty)', 'caption=Доставка->Начин,removeAndRefreshForm=deliveryCountry|deliveryPCode|deliveryPlace|deliveryAddress|deliveryData,silent,mandatory');
    	$this->FLD('deliveryCountry', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Доставка->Държава,hint=Държава на доставка');
    	$this->FLD('deliveryPCode', 'varchar(16)', 'caption=Доставка->П. код,hint=Пощенски код за доставка');
    	$this->FLD('deliveryPlace', 'varchar(64)', 'caption=Доставка->Място,hint=Населено място: град или село и община');
    	$this->FLD('deliveryAddress', 'varchar(255)', 'caption=Доставка->Адрес,hint=Вашият адрес');
    	$this->FLD('deliveryData', 'blob(serialize, compress)', 'input=none');
    	$this->FLD('instruction', 'richtext(rows=2)', 'caption=Доставка->Инструкции');
    	
    	$this->FLD('paymentId', 'key(mvc=cond_PaymentMethods,select=title,allowEmpty)', 'caption=Плащане->Начин,mandatory');
    	$this->FLD('makeInvoice', 'enum(none=Без фактуриране,person=Фактура на лице, company=Фактура на фирма)', 'caption=Плащане->Фактуриране,silent,removeAndRefreshForm=deliveryData|deliveryCountry|deliveryPCode|deliveryPlace|deliveryAddress|locationId');
    	
    	$this->FLD('saleFolderId', 'key(mvc=doc_Folders)', 'caption=Данни за фактура->Папка,input=none,silent,removeAndRefreshForm=invoiceNames|invoiceVatNo|invoiceAddress|invoicePCode|invoicePlace|invoiceCountry');
    	$this->FLD('invoiceNames', 'varchar(128)', 'caption=Данни за фактура->Наименование,invoiceData,hint=Име,input=none,mandatory');
    	$this->FLD('invoiceVatNo', 'drdata_VatType', 'caption=Данни за фактура->VAT/EIC,input=hidden,mandatory,invoiceData');
    	$this->FLD('invoiceCountry', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Данни за фактура->Държава,hint=Фирма на държавата,input=none,mandatory,invoiceData');
    	$this->FLD('invoicePCode', 'varchar(16)', 'caption=Данни за фактура->П. код,invoiceData,hint=Пощенски код на фирмата,input=none,mandatory');
    	$this->FLD('invoicePlace', 'varchar(64)', 'caption=Данни за фактура->Град,invoiceData,hint=Населено място: град или село и община,input=none,mandatory');
    	$this->FLD('invoiceAddress', 'varchar(255)', 'caption=Данни за фактура->Адрес,invoiceData,hint=Адрес на регистрация на фирмата,input=none,mandatory');
    	
    	$this->FLD('info', 'richtext(rows=2)', 'caption=Общи данни->Забележка,input=none');
    	$this->FLD('state', 'enum(draft=Чернова,active=Активно,closed=Приключено,rejected=Оттеглен)', 'caption=Състояние,input=none,notNull,value=active');
    	$this->FLD('saleId', 'key(mvc=sales_Sales)', 'caption=Продажба,input=none');
    	$this->FLD('locationId', 'key(mvc=crm_Locations,select=title)', 'caption=Локация,input=none,silent,removeAndRefreshForm=deliveryData|deliveryCountry|deliveryPCode|deliveryPlace|deliveryAddress,after=instruction');
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
    
    
    /**
     * Последната активна поръчка
     * 
     * @param int|NULL $domainId
     * @param int|NULL $userId
     * @return stdClass|FALSE
     */
    private static function getLastActivatedCart($domainId = NULL, $userId = NULL)
    {
    	$userId = isset($userId) ? $userId : core_Users::getCurrent('id', FALSE);
    	$domainId = isset($domainId) ? $domainId : cms_Domains::getPublicDomain()->id;
    	$brid = log_Browsers::getBrid();
    	 
    	// Ако има потребител се търси имали чернова кошница за този потребител, ако не е логнат се търси по Брид-а
    	$where = (isset($userId)) ? "#userId = '{$userId}'" : "#userId IS NULL AND #brid = '{$brid}'";
    	$query = self::getQuery();
    	$query->where("{$where} AND #state = 'active' AND #domainId = {$domainId}");
    	$query->orderBy('activatedOn', "DESC");
    	
    	return $query->fetch();
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
    			if($delivery['amount'] >= 0){
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
    			$rec->totalNoVat += round($sum / (1 + $dRec->vat), 4);
    			$rec->total += round($sum, 4);
    		} else {
    			$rec->totalNoVat += round($sum, 4);
    			$rec->total += round($sum * (1 + $dRec->vat), 4);
    		}
    	}
    	
    	$rec->totalNoVat = round($rec->totalNoVat, 4);
    	$rec->total = round($rec->total, 4);
    	
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
    		if($settings->enableCart == 'no' && !$cartRec->productCount) return new core_ET(' ');
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
    	Mode::push('eshopFinalize', TRUE);
    	$cu = core_Users::getCurrent('id', FALSE);
    	
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
    	
    	$templateId = cls::get('sales_Sales')->getDefaultTemplate((object)array('folderId' => $folderId));
    	$templateLang = doc_TplManager::fetchField($templateId, 'lang');
    	
    	core_Lg::push($templateLang);
    	
    	// Дефолтни данни на продажбата
    	$fields = array('valior'             => dt::today(), 
    			        'deliveryTermId'     => $rec->termId, 
    			        'deliveryTermTime'   => $rec->deliveryTime, 
    			        'paymentMethodId'    => $rec->paymentId, 
    			        'makeInvoice'        => ($rec->makeInvoice == 'none') ? 'no' : 'yes',
    					'chargeVat'          => $settings->chargeVat,
    					'currencyId'         => $settings->currencyId,
    					'shipmentStoreId'    => $settings->storeId,
    					'note'               => tr('Онлайн поръчка') . " №{$rec->id}",
    					'deliveryLocationId' => $rec->locationId,
    	);
    	
    	if($dealerId = sales_Sales::getDefaultDealerId($folderId, $fields['deliveryLocationId'])){
    		$fields['dealerId'] = $dealerId;
    	}
    	
    	// Създаване на продажба по количката
   		$saleId = sales_Sales::createNewDraft($Cover->getClassId(), $Cover->that, $fields);
   		core_Lg::pop();
   		sales_Sales::logWrite('Създаване от онлайн поръчка', $saleId);
   		
   		// Добавяне на артикулите от количката в продажбата
   		$dQuery = eshop_CartDetails::getQuery();
   		$dQuery->where("#cartId = {$id}");
   		while($dRec = $dQuery->fetch()){ 
   			$price = ($dRec->amount  / $dRec->quantity);
   			$price = isset($dRec->discount) ? ($price / (1 - $dRec->discount)) : $price;
   			
   			if($dRec->haveVat == 'yes'){
   				$price /= 1 + $dRec->vat;
   			}
   			
   			$price = currency_CurrencyRates::convertAmount($price, NULL, $dRec->currencyId);
   			sales_Sales::addRow($saleId, $dRec->productId, $dRec->packQuantity, $price, $dRec->packagingId, $dRec->discount);
   		}
   		
   		// Добавяне на транспорта, ако има
   		if(isset($rec->deliveryNoVat) && $rec->deliveryNoVat >= 0){
   			$transportId = cat_Products::fetchField("#code = 'transport'", 'id');
   			sales_Sales::addRow($saleId, $transportId, 1, $rec->deliveryNoVat);
   		}
   		
   		// Продажбата става на заявка, кошницата се активира
   		$saleRec = self::makeSalePending($saleId);
   		self::activate($rec, $saleId);
   		doc_Threads::doUpdateThread($saleRec->threadId);
   		
   		// Ако е партньор и има достъп до нишката, директно се реидректва към нея
   		if(core_Packs::isInstalled('colab') && isset($cu) && core_Users::isContractor($cu)){
   			$threadRec = doc_Threads::fetch($saleRec->threadId);
   			if(colab_Threads::haveRightFor('single', $threadRec)){
   				return new Redirect(array('colab_Threads', 'single', 'threadId' => $saleRec->threadId), 'Успешно създадена заявка за продажба');
   			}
   		} else {
   			self::sendEmail($rec, $saleRec);
   		}
   		
   		Mode::pop('eshopFinalize');
   		
   		return new Redirect(cls::get('eshop_Groups')->getUrlByMenuId(NULL), 'Поръчката е направена|*!');
    }
    
    
    /**
     * Продажба да се обърне в състояние заявка
     *
     * @param int $saleId
     * @return stdClass $saleRec
     */
    private static function makeSalePending($saleId)
    {
    	$saleRec = sales_Sales::fetch($saleId);
    	$saleRec->state = 'pending';
    	$saleRec->brState = 'draft';
    	$saleRec->pendingSaved = TRUE;
    	sales_Sales::save($saleRec, 'state');
    	
    	return $saleRec;
    }
    
    
    /**
     * Активиране на количката
     * 
     * @param stdClass $rec
     * @param int $saleId
     */
    private static function activate($rec, $saleId)
    {
    	$rec->saleId = $saleId;
    	$rec->state = 'active';
    	$rec->activatedOn = dt::now();
    	self::save($rec, 'state,saleId,activatedOn');
    }
    
    
    /**
     * Изпраща имейл
     * 
     * @param stdClass $rec
     * @param stdClass $saleRec
     */
    private static function sendEmail($rec, $saleRec)
    {
    	$settings = cms_Domains::getSettings($rec->domainId);
    	if(empty($settings->inboxId)) return;
    	
    	$lang = cms_Domains::fetchField($rec->domainId, 'lang');
    	core_Lg::push($lang);
    	
    	// Подготовка на тялото на имейла
    	$body = new core_ET($settings->emailBody);
    	$body->replace($rec->personNames, "NAME");
    	$body->replace("#Sal{$saleRec->id}", "SALE_HANDLER");
    	
    	// Линка за регистрация
    	$Cover = doc_Folders::getCover($saleRec->folderId);
    	$url = core_Forwards::getUrl('colab_FolderToPartners', 'Createnewcontractor', array('companyId' => $Cover->that, 'email' => $rec->email, 'rand' => str::getRand()), 604800);
    	$url = "[link={$url}]" . tr('връзка||link') . "[/link]";
    	$body->replace($url, "link");
    	$body = core_Type::getByName('richtext')->fromVerbal($body->getContent());
    	
    	// Подготовка на имейла
    	$emailRec = (object)array('subject'  => tr("Онлайн поръчка") . " №{$rec->id}",
    			                  'body'     => $body,
    			                  'folderId' => $saleRec->folderId,
    			                  'originId' => $saleRec->containerId,
    			                  'threadId' => $saleRec->threadId,
    			                  'state'    => 'active',
    	                          'email'    => $rec->email, 'tel' => $rec->tel, 'recipient' => $rec->personNames);
    	
    	// Активиране на изходящия имейл
    	email_Outgoings::save($emailRec);
    	email_Outgoings::logWrite('Създаване от онлайн поръчка', $emailRec->id);
    	cls::get('email_Outgoings')->invoke('AfterActivation', array(&$emailRec));
    	
    	// Изпращане на имейла
    	$options = (object)array('encoding' => 'utf-8', 'boxFrom' => $settings->inboxId, 'emailsTo' => $emailRec->email);
    	email_Outgoings::send($emailRec, $options, $lang);
    	
    	core_Lg::pop($lang);
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
    	
    	// Ако има последно активирана кошница да се показва като съобщение
    	if($lastCart = self::getLastActivatedCart()){
    		if($lastCart->activatedOn >= dt::addSecs(-1 * 60 * 60 * 2, dt::now())){
    			$tpl->replace($lastCart->email, 'CHECK_EMAIL');
    		}
    	}
    	
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
    	
    	// Редирект към ешопа ако количката е активна
    	if($rec->state == 'active'){
    		$shopUrl = cls::get('eshop_Groups')->getUrlByMenuId(NULL);
    		redirect($shopUrl);
    	}
    	
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
    	
    	$countryVerbal = core_Type::getByName('key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg)')->toVerbal($rec->deliveryCountry);
    	$tpl->replace($countryVerbal, 'deliveryCountry');
    	 
    	foreach (array('deliveryPCode', 'deliveryPlace', 'deliveryAddress') as $name){
    		$tpl->replace(core_Type::getByName('varchar')->toVerbal($rec->{$name}), $name);
    	}
    	
    	if($companyFolderId = core_Mode::get('lastActiveContragentFolder')){
    		if(colab_Threads::haveRightFor('list', (object)array('folderId' => $companyFolderId))){
    			$folderTitle = doc_Folders::getVerbal($companyFolderId, 'title');
				$activeFolderId = ht::createLink($folderTitle, array('colab_Threads', 'list', 'folderId' => $companyFolderId), FALSE, 'ef_icon=img/16/folder-icon.png');
				$tpl->append($activeFolderId, 'activeFolderId');
    		}
    	}
    	
    	if(self::haveRightFor('checkout', $rec)){
    		$editSaleBtn = ht::createLink('', array($this, 'order', $rec->id, 'ret_url' => TRUE), FALSE, 'ef_icon=img/16/edit.png,title=Редактиране на данните на поръчката');
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
    	$Double = core_Type::getByName('double(decimals=2)');
    	
    	$row->total = $Double->toVerbal($total);
    	$row->currencyId = $settings->currencyId;
    	
    	if($settings->chargeVat != 'yes'){
    		$totalNoVat = currency_CurrencyRates::convertAmount($rec->totalNoVat, NULL, NULL, $settings->currencyId);
    		$vatAmount = $total - $totalNoVat;
    		
    		$row->totalNoVat = $Double->toVerbal($totalNoVat);
    		$row->totalVat = $Double->toVerbal($vatAmount);
    		$row->totalNoVatCurrencyId = $row->vatCurrencyId = $row->currencyId;
    	} else {
    		unset($row->totalNoVat);
    	}
    	
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
    		$btn = ht::createLink(tr('Изчисти'), $emptyUrl, 'Сигурни ли сте, че искате да изчистите артикулите', 'title=Изчистване на всички артикули,class=eshop-link,ef_icon=img/16/deletered.png');
    		$tpl->append($btn, 'EMPTY_CART');
    	}
    	
    	if(eshop_CartDetails::haveRightFor('add', (object)array('cartId' => $rec->id))){
    		$addUrl = array('eshop_CartDetails', 'add', 'cartId' => $rec->id, 'external' => TRUE, 'ret_url' => TRUE);
    		$btn = ht::createLink(tr('Добавяне на артикул'), $addUrl, NULL, 'title=Добавяне на нов артикул,class=eshop-link,ef_icon=img/16/add1-16.png');
    		$tpl->append($btn, 'CART_TOOLBAR_LEFT');
    	}
    	
    	$btn = ht::createLink(tr('Към магазина'), $shopUrl, NULL, 'title=Връщане в онлайн магазина,class=eshop-link,ef_icon=img/16/cart_go.png');
    	$tpl->append($btn, 'CART_TOOLBAR_LEFT');
    	
    	$checkoutUrl = (eshop_Carts::haveRightFor('checkout', $rec)) ? array(eshop_Carts, 'order', $rec->id, 'ret_url' => TRUE) : array();
    	if(empty($rec->personNames) && count($checkoutUrl)){
    		$btn = ht::createBtn(tr('Данни за поръчката'), $checkoutUrl, NULL, NULL, "title=Поръчване на артикулите,class=order-btn eshop-btn {$disabledClass}");
    		$tpl->append($btn, 'CART_TOOLBAR_RIGHT');
    	}
    	
    	// Ако се изисква онлайн плащане добавя се бутон към него
    	if(isset($rec->paymentId) && cond_PaymentMethods::doRequireOnlinePayment($rec->paymentId)){
    		$paymentUrl = cond_PaymentMethods::getOnlinePaymentUrl($rec->paymentId);
    		$btn = ht::createBtn('Плащане', $paymentUrl, NULL, NULL, "title=Плащане на поръчката,class=order-btn eshop-btn {$disabledClass}");
    		$tpl->append($btn, 'CART_TOOLBAR_RIGHT');
    	}
    	
    	if(eshop_Carts::haveRightFor('finalize', $rec)){
    		$btn = ht::createBtn('Завършване', array('eshop_Carts', 'finalize', $rec->id), 'Сигурни ли сте, че искате да направите поръчката|*!', NULL, "title=Завършване на поръчката,class=order-btn eshop-btn {$disabledClass}");
    		$tpl->append($btn, 'CART_TOOLBAR_RIGHT');
    	}
    }
    
    
    /**
     * Рендиране на изгледа на кошницата във външната част
     *
     * @param mixed $rec
     * @return core_ET $tpl - шаблон на съмарито
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
    	
    	// Ако има доставка се показва и тя
    	if(isset($data->rec->deliveryNoVat) && $data->rec->deliveryNoVat >= 0){
    		$transportId = cat_Products::fetchField("#code = 'transport'", 'id');
    		$deliveryAmount = $data->rec->deliveryNoVat * (1 + cat_Products::getVat($transportId));
    		$deliveryAmount = currency_CurrencyRates::convertAmount($deliveryAmount, NULL, NULL, $settings->currencyId);
    		$deliveryAmount = core_Type::getByName('double(decimals=2)')->toVerbal($deliveryAmount);
    			
    		$data->rows['-1'] = (object)array('productId' => "<b>" . tr('Доставка||Shipping') . "</b>", 'amount' => "<b>" . $deliveryAmount. "</b>");
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
    	
    	$data = new stdClass();
    	$data->action = 'order';
    	$this->prepareEditForm($data);
    	$data->form->setAction($this, 'order');
    	
    	$form = &$data->form;
    	$form->title = 'Данни за поръчката';
    	
    	self::prepareOrderForm($form);
    	
    	$form->input(NULL, 'silent');
    	self::setDefaultsFromFolder($form, $form->rec->saleFolderId);
    	$cu = core_Users::getCurrent('id', FALSE);
    	if(isset($cu)){
    		$profileRec = crm_Profiles::getProfile($cu);
    		if($form->rec->saleFolderId == $profileRec->folderId){
    			$form->rec->makeInvoice = 'person';
    		} else {
    			$form->rec->makeInvoice = 'company';
    		}
    	}
    	
    	// Ако има условие на доставка то драйвера му може да добави допълнителни полета
    	if(isset($form->rec->termId)){
    		
    		// Държавата за доставка да е тази от ип-то по дефолт
    		if($countryCode2 = drdata_IpToCountry::get()) {
    			$form->setDefault('deliveryCountry', drdata_Countries::fetchField("#letterCode2 = '{$countryCode2}'", 'id'));
    		}
    		
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
    	
    	if($rec->makeInvoice != 'none'){
    		$form->setDefault('invoiceCountry', $rec->deliveryCountry);
    		$form->setDefault('invoicePCode', $rec->deliveryPCode);
    		$form->setDefault('invoicePlace', $rec->deliveryPlace);
    		$form->setDefault('invoiceAddress', $rec->deliveryAddress);
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
    		
    		// Ако има избрана папка обновява се
    		if(!empty($rec->saleFolderId)){
    			$Cover = doc_Folders::getCover($rec->saleFolderId);
    			$Cover->getInstance()->updateContactDataByFolderId($rec->saleFolderId, $rec->invoiceNames, $rec->invoiceVatNo, $rec->invoiceCountry, $rec->invoicePCode, $rec->invoicePlace, $rec->invoiceAddress);
    		}
    		
    		$cu = core_Users::getCurrent('id', FALSE);
    		
    		if(isset($cu) && core_Users::isContractor($cu)){
    			if(isset($rec->saleFolderId)){
    				$Cover = doc_Folders::getCover($rec->saleFolderId);
    				$contragentClassId = $Cover->getClassId();
    				$contragentId = $Cover->that;
    			} else {
    				$contragentClassId = crm_Persons::getClassId();
    				$contragentId = crm_Profiles::getProfile($cu)->id;
    			}
    			
    			// Ако има въведени адресни данни
    			if(!empty($rec->deliveryCountry) || !empty($rec->deliveryPCode) || !empty($rec->deliveryPlace) || !empty($rec->deliveryAddress)){
    				$rec->locationId = crm_Locations::update($contragentClassId, $contragentId, $rec->deliveryCountry, 'За получаване на пратки', $rec->deliveryPCode, $rec->deliveryPlace, $rec->deliveryAddress, $rec->locationId);
    			}
    		}
    		
    		$this->save($rec);
    		$this->updateMaster($rec);
    		core_Lg::pop();
    		
    		return followRetUrl();
    	}
    	
    	Mode::set('wrapper', 'cms_page_External');
    	 
    	// Добавяне на бутони
    	$form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png, title = Запис на данните за поръчката');
    	$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
    	
    	$tpl = $form->renderHtml();
    	core_Form::preventDoubleSubmission($tpl, $form);
    	core_Lg::pop();
    	
    	return $tpl;
    }
    
    
    /**
     * Подготвя формата за поръчка
     * 
     * @param core_Form $form
     * @return void
     */
    private static function prepareOrderForm(&$form)
    {
    	$cu = core_Users::getCurrent('id', FALSE);
    	$defaultTermId = $defaultPaymentId = NULL;
    	 
    	$deliveryTerms = eshop_Settings::getDeliveryTermOptions('cms_Domains', cms_Domains::getPublicDomain()->id);
    	$paymentMethods = eshop_Settings::getPaymentMethodOptions('cms_Domains', cms_Domains::getPublicDomain()->id);
    	
    	if($cu){
    		$options = colab_Folders::getSharedFolders($cu, TRUE, 'crm_ContragentAccRegIntf', FALSE);
    		$profileRec = crm_Profiles::getProfile($cu);
    		$form->setDefault('personNames', $profileRec->name);
    		$form->setDefault('email', $profileRec->email);
    		$form->setDefault('tel', $profileRec->tel);
    	
    		// Задаване като опции
    		if(count($options)){
    			$form->setDefault('makeInvoice', 'company');
    			$form->setField('makeInvoice', 'input=hidden');
    			$form->setField('saleFolderId', 'input');
    			$form->setOptions('saleFolderId', $options);
    			 
    			// Коя папка е избрана по дефолт
    			$companyFolderId = core_Mode::get('lastActiveContragentFolder');
    			$defaultFolder = ($companyFolderId) ? $companyFolderId : key($options);
    			$form->setDefault('saleFolderId', $defaultFolder);
    		}
    	
    		// Добавяне на партньорското условие на доставка
    		$defaultTermId = cond_Parameters::getParameter('crm_Persons', $profileRec->id, 'deliveryTermSale');
    		$form->setDefault('termId', $defaultTermId);
    		if($defaultTermId && !array_key_exists($defaultTermId, $deliveryTerms)){
    			$deliveryTerms[$defaultTermId] = cond_DeliveryTerms::getVerbal($defaultTermId, 'codeName');
    		}
    		
    		// Добавяне на партньорския метод за плащане
    		$defaultPaymentId = cond_Parameters::getParameter('crm_Persons', $profileRec->id, 'paymentMethodSale');
    		$form->setDefault('paymentId', $defaultPaymentId);
    		if($defaultPaymentId && !array_key_exists($defaultPaymentId, $paymentMethods)){
    			$paymentMethods[$defaultPaymentId] = tr(cond_PaymentMethods::getVerbal($paymentId, 'name'));
    		}
    	}
    	 
    	if(count($deliveryTerms) == 1){
    		$form->setDefault('termId', key($deliveryTerms));
    	} else {
    		$deliveryTerms = array('' => '') + $deliveryTerms;
    	}
    	$form->setOptions('termId', $deliveryTerms);
    	 
    	if(count($paymentMethods) == 1){
    		$form->setDefault('paymentId', key($paymentMethods));
    	} else {
    		$paymentMethods = array('' => '') + $paymentMethods;
    	}
    	$form->setOptions('paymentId', $paymentMethods);
    	 
    	$makeInvoice = bgerp_Setup::get('MANDATORY_CONTACT_FIELDS');
    	if(in_array($makeInvoice, array('company', 'both'))){
    		$form->setDefault('makeInvoice', 'company');
    		$form->setField('makeInvoice', 'input=hidden');
    	}
    }
    
    
    /**
     * Дефолти от избраната папка
     * 
     * @param core_Form $form
     * @param int|NULL $folderId
     */
    private static function setDefaultsFromFolder(&$form, $folderId)
    {
    	$rec = &$form->rec;
    	
    	// Ако има избрана папка се записват контрагент данните
    	if(isset($folderId)){
    		if($contragentData = doc_Folders::getContragentData($folderId)){
    			$form->setDefault('invoiceNames', $contragentData->company);
    			$form->setDefault('invoiceVatNo', $contragentData->vatNo);
    			$form->setDefault('invoiceCountry', $contragentData->countryId);
    			$form->setDefault('invoicePCode', $contragentData->pCode);
    			$form->setDefault('invoicePlace', $contragentData->place);
    			$form->setDefault('invoiceAddress', $contragentData->address);
    		}
    		$locations = crm_Locations::getContragentOptions('crm_Companies', $contragentData->companyId);
    	} else {
    		$cu = core_Users::getCurrent('id', FALSE);
    		if(isset($cu) && core_Users::isContractor($cu)){
    			$locations = crm_Locations::getContragentOptions('crm_Persons', crm_Profiles::getProfile($cu)->id);
    		}
    	}
    	
    	// Ако има локации задават се
    	if(count($locations)){
    		$form->setOptions('locationId', array('' => '') + $locations);
    		$form->setField('locationId', 'input');
    		$form->input('locationId', 'silent');
    	}
    	
    	// Ако е избрана локация допълват се адресните данни за доставка
    	if(isset($rec->locationId)){
    		$locationRec = crm_Locations::fetch($rec->locationId);
    		foreach (array('deliveryCountry' => 'countryId', 'deliveryPCode' => 'pCode', 'deliveryPlace' => 'place', 'deliveryAddress' => 'address') as $delField => $locField){
    			if(!empty($locationRec->{$locField})){
    				$form->setDefault($delField, $locationRec->{$locField});
    			}
    		}
    	}
    }
}