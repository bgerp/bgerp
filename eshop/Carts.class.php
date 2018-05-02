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
    public $loadList = 'plg_Created, plg_RowTools2, eshop_Wrapper, plg_Rejected, doc_ActivatePlg, plg_Modified';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'ip,brid,domainId,userId,state';
    
    
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
    public $canAdd = 'eshop,ceo,admin';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'eshop,ceo,admin';
    
    
	/**
	 * Кой може да го разглежда?
	 */
	public $canViewexternal = 'every_one';
	
	
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'eshop,ceo,admin';
    
    
    /**
     * Кой може да добавя в кошницата
     */
    public $canAddtocart = 'every_one';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'eshop_CartDetails';
    
    
    /**
     * Кой може да я прави на заявка
     */
    public $canPending = 'no_one';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('ip', 'varchar', 'caption=Ип,input=none1');
    	$this->FLD('brid', 'varchar(8)', 'caption=Браузър,input=none1');
    	$this->FLD('domainId', 'key(mvc=cms_Domains, select=domain)', 'caption=Брид,silent');
    	$this->FLD('userId', 'key(mvc=core_Users, select=nick)', 'caption=Потребител,silent');
    	$this->FLD('total', 'double(decimals=2)', 'caption=Общи данни->Стойност,silent');
    	$this->FLD('totalNoVat', 'double(decimals=2)', 'caption=Общи данни->Стойност без ДДС,silent');
    	$this->FLD('productCount', 'int', 'caption=Общи данни->Брой,silent');
    	$this->FLD('paymentId', 'key(mvc=cond_PaymentMethods,select=title,allowEmpty)', 'caption=Общи данни->Плащане');
    	$this->FLD('termId', 'key(mvc=cond_DeliveryTerms,select=codeName,allowEmpty)', 'caption=Общи данни->Доставка');
    	$this->FLD('info', 'richtext(rows=2)', 'caption=Общи данни->Забележка');
    	$this->FLD('invoiceNames', 'varchar(255)', 'caption=Данни на фирма за фактура->Наименование,class=contactData,hint=Имате на фирмата');
    	$this->FLD('invoiceVatNo', 'drdata_VatType', 'caption=Данни на фирма за фактура->VAT/EIC');
    	$this->FLD('invoiceAddress', 'varchar(255)', 'caption=Данни на фирма за фактура->Адрес,class=contactData,hint=Адрес на регистрация на фирмата');
    	$this->FLD('invoicePCode', 'varchar(16)', 'caption=Данни на фирма за фактура->П. код,class=contactData,hint=Пощенски код на фирмата');
    	$this->FLD('invoicePlace', 'varchar(64)', 'caption=Данни на фирма за фактура->Град,class=contactData,hint=Населено място: град или село и община');
    	$this->FLD('invoiceCountry', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Данни на фирма за фактура->Държава,hint=Фирма на държавата');
    	$this->FLD('deliveryAddress', 'varchar(255)', 'caption=Данни за доставка->Адрес,class=contactData,hint=Вашият адрес');
    	$this->FLD('deliveryPCode', 'varchar(16)', 'caption=Данни за доставка->П. код,class=contactData,hint=Пощенски код за доставка');
    	$this->FLD('deliveryPlace', 'varchar(64)', 'caption=Данни за доставка->Град,class=contactData,hint=Населено място: град или село и община');
    	$this->FLD('deliveryCountry', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Данни за доставка->Държава,hint=Държава на доставка');
    	$this->FLD('instruction', 'richtext(rows=2)', 'caption=Данни за доставка->Инструкции');
    	$this->FLD('personNames', 'varchar(255)', 'caption=Данни на лице->Имена,class=contactData,hint=Вашето име||Your name,mandatory');
    	$this->FLD('salutation', 'varchar(255)', 'caption=Данни на лице->Обръщение,class=contactData,hint=Обръщение||Salutation');
    	$this->FLD('email', 'email(valid=drdata_Emails->validate)', 'caption=Данни на лице->Имейл,hint=Вашият имейл||Your email,mandatory');
    	$this->FLD('tel', 'drdata_PhoneType', 'caption=Данни на лице->Телефони,hint=Вашият телефон,mandatory');
    	$this->setDbIndex('brid');
    	$this->setDbIndex('userId');
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
    	
    	$packRec = cat_products_Packagings::getPack($productId, $packagingId);
    	$quantityInPack = (is_object($packRec)) ? $packRec->quantity : 1;
    	$canStore = cat_Products::fetchField($productId, 'canStore');
    	
    	$msg = 'Проблем при добавянето на артикулът|*!';
    	$settings = eshop_Settings::getSettings('cms_Domains', cms_Domains::getPublicDomain()->id);
    	if(isset($settings->storeId) &&  $canStore == 'yes'){
    		$quantity = store_Products::getQuantity($productId, $settings->storeId, TRUE);
    		if($quantity < $quantityInPack * $packQuantity){
    			$msg = 'Избраното количество не е налично ' . $quantity . " > $quantityInPack > $packQuantity" ;
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
    			$msg = 'Артикулът е успешно добавен|*!';
    			$success = TRUE;
    		} catch(core_exception_Expect $e){
    			reportException($e);
    			$msg = 'Проблем при добавянето на артикула|*!';
    		}
    	}
    	
    	// Ако режимът е за AJAX
    	if (Request::get('ajax_mode')) {
    		core_Statuses::newStatus($msg, ($success === TRUE) ? 'notice' : 'error');
    		
    		// Ще се реплейсне статуса на кошницата
    		$resObj = new stdClass();
    		$resObj->func = "html";
    		$resObj->arg = array('id' => 'cart-external-status', 'html' => self::getStatus($cartId)->getContent(), 'replace' => TRUE);
    	
    		$hitTime = Request::get('hitTime', 'int');
    		$idleTime = Request::get('idleTime', 'int');
    		$statusData = status_Messages::getStatusesData($hitTime, $idleTime);
    		 
    		$res = array_merge(array($resObj), (array)$statusData);
    		
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
    		$rec = (object)array('ip' => $ip,'brid' => $brid, 'domainId' => $domainId, 'userId' => $userId, 'state' => 'draft');
    		self::save($rec);
    	}
    	
    	return $rec->id;
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
    	$dQuery = eshop_CartDetails::getQuery();
    	$dQuery->where("#cartId = {$rec->id}");
    	
    	while($dRec = $dQuery->fetch()){
    		$rec->productCount++;
    		$finalPrice = $dRec->finalPrice;
    		if(!$dRec->discount){
    			$finalPrice -= $finalPrice * $dRec->discount;
    		}
    		$sum = $finalPrice * ($dRec->quantity / $dRec->quantityInPack);
    		
    		if($dRec->haveVat == 'yes'){
    			$rec->totalNoVat += $sum / (1 + $dRec->vat);
    			$rec->total += $sum;
    		} else {
    			$rec->totalNoVat += $sum;
    			$rec->total += $sum * (1 + $dRec->vat);
    		}
    	}
    	
    	$rec->totalNoVat = round($rec->totalNoVat, 2);
    	$rec->total = round($rec->total, 2);
    	
    	$id = $this->save_($rec, 'productCount,total,totalNoVat');
    	
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
		$url = array();
    	
		$cartName = self::getCartDisplayName();
    	if(isset($cartId)){
    		$cartRec = self::fetch($cartId);
    		if($settings->enableCart == 'no'){
    			if(!$cartRec->productCount) return new core_ET(' ');
    		}
    		
    		$amount = core_Type::getByName('double(smartRound)')->toVerbal($cartRec->total);
    		$amount = str_replace('&nbsp;', ' ', $amount);
    		$count = core_Type::getByName('int')->toVerbal($cartRec->productCount);
    		$url = array('eshop_Carts', 'view', $cartId, 'ret_url' => TRUE);
    		$str = ($count == 1) ? 'артикул' : 'артикула';
    		$hint = "|*{$count} |{$str} за|* {$amount} " . $settings->currencyId;
    	
    		if($count){
    			$tpl->append(new core_ET("<sup>[#count#]</sup>"));
    		}
    	} else {
    		if($settings->enableCart == 'no') return new core_ET(' ');
    	}
    	
    	$tpl->replace($cartName, 'text');
    	$tpl->replace($count, 'count');
    	$tpl = ht::createLink($tpl, $url, FALSE, "title={$hint}, ef_icon=img/16/cart-black.png");

		$tpl->removeBlocks();
    	$tpl->removePlaces();

    	return $tpl;
    }
    
    
    /**
     * Прекъсва връзките на изтритите визитки с всички техни имейл адреси.
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param core_Query $query
     */
    protected static function on_AfterDelete($mvc, &$res, $query)
    {
        foreach ($query->getDeletedRecs() as $rec) {
        	eshop_CartDetails::delete("#cartId = {$rec->id}");
        }
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
    	
    	$tpl = getTplFromFile("eshop/tpl/SingleLayoutCartExternal.shtml");
    	$tpl->replace(self::renderViewCart($rec), 'CART_TABLE');
    	$tpl->replace(self::renderCartSummary($rec), 'CART_TOTAL');
    	$tpl->replace(self::renderCartSummary($rec, TRUE), 'CART_COUNT');
    	$tpl->replace(self::renderCartToolbar($rec, TRUE), 'CART_TOOLBAR');
    	$tpl->replace(self::getCartDisplayName(), 'CART_NAME');
    	
    	$settings = cms_Domains::getSettings();
    	
    	$cartInfo = tr('Всички цени са в') . " {$settings->currencyId}, " . (($settings->chargeVat == 'yes') ? tr('с включено ДДС') : tr('без ДДС'));
    	$tpl->replace($cartInfo, 'VAT_STATUS');
    	
    	if(!empty($settings->info)){
    		$tpl->replace(core_Type::getByName('richtext')->toVerbal($settings->info), 'COMMON_TEXT');
    	}
    	
    	Mode::set('wrapper', 'cms_page_External');
    	
    	return $tpl;
    }
    
    
    /**
     * Рендиране на съмарито на кошницата
     * 
     * @param mixed $id
     * @param boolean $onlyCount - само общата бройка или общата сума
     * @return core_ET $tpl      - шаблон на съмарито
     */
    public static function renderCartSummary($id, $onlyCount = FALSE)
    {
    	$rec = self::fetchRec($id, '*', FALSE);
    	$row = self::recToVerbal($rec);
    	$row->totalNoVatCurrencyId = $row->currencyId;
    	$row->productCount .= "&nbsp;" . (($rec->productCount == 1) ? tr('артикул') : tr('артикула'));
    	$block = ($onlyCount === TRUE) ? 'CART_COUNT' : 'CART_SUMMARY';
    	$tpl = clone getTplFromFile('eshop/tpl/SingleLayoutCartExternalBlocks.shtml')->getBlock($block);
    	$tpl->placeObject($row);
    	$tpl->removeBlocks();
    	$tpl->removePlaces();
    	
    	return $tpl;
    }
    
    
    /**
     * Рендиране на тулбара към кошницата
     *
     * @param mixed $id
     * @param boolean $onlyCount - само общата бройка или общата сума
     * @return core_ET $tpl      - шаблон на съмарито
     */
    public static function renderCartToolbar($id)
    {
    	$rec = self::fetchRec($id);
    	$tpl = clone getTplFromFile('eshop/tpl/SingleLayoutCartExternalBlocks.shtml')->getBlock('CART_TOOLBAR');
    	
    	if(eshop_CartDetails::haveRightFor('removeexternal', (object)array('cartId' => $rec->id))){
    		$emptyUrl = ($rec->productCount) ? array('eshop_CartDetails', 'removeexternal', 'cartId' => $rec->id, 'ret_url' => getRetUrl()) : array();
    		$btn = ht::createBtn('Изчистване', $emptyUrl, NULL, NULL, 'title=Изчистване на кошницата,class=eshop-btn,ef_icon=img/16/bin_closed.png');
    		$tpl->append($btn, 'CART_TOOLBAR');
    	}
    	
    	if(eshop_CartDetails::haveRightFor('add', (object)array('cartId' => $rec->id))){
    		$addUrl = array('eshop_CartDetails', 'add', 'cartId' => $rec->id, 'external' => TRUE, 'ret_url' => TRUE);
    		$btn = ht::createBtn('Добавяне', $addUrl, NULL, NULL, 'title=Добавяне на артикули,class=eshop-btn,ef_icon=img/16/add.png');
    		$tpl->append($btn, 'CART_TOOLBAR');
    	}
    	
    	if(eshop_CartDetails::haveRightFor('checkout', (object)array('cartId' => $rec->id))){
    		$checkoutUrl = array();
    		$disabledClass = !count($checkoutUrl) ? 'disabled': '';
    		$btn = ht::createBtn('Поръчване', $checkoutUrl, NULL, NULL, "title=Поръчване на артикулите,class=eshop-btn {$disabledClass},ef_icon=img/16/cart_go.png");
    		$tpl->append($btn, 'CART_TOOLBAR');
    	}
    	
    	$tpl->removeBlocks();
    	$tpl->removePlaces();
    	
    	return $tpl;
    }
    
    
    /**
     * Рендиране на изгледа на кошницата във външната част
     *
     * @param mixed $rec
     * @return core_ET $tpl      - шаблон на съмарито
     */
    public static function renderViewCart($rec)
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
    	$data->listFields = arr::make("code=Код,productId=Артикул,packagingId=Опаковка,quantity=К-во,finalPrice=Цена,amount=Сума");
    	
    	$data->productRecs = $data->productRows = array();
    	$dQuery = eshop_CartDetails::getQuery();
    	$dQuery->where("#cartId = {$data->rec->id}");
    	while($dRec = $dQuery->fetch()){
    		$data->recs[$dRec->id] = $dRec;
    		$row = eshop_CartDetails::recToVerbal($dRec, $fields);
    		if(!empty($dRec->discount)){
    			$settings = eshop_Settings::getSettings('cms_Domains', cms_Domains::getPublicDomain()->id);
    			$discount = ($settings->discountType == 'amount') ? core_Type::getByName('double(decimals=2)')->toVerbal($dRec->finalPrice/ (1 - $dRec->discount)) : "-" . core_Type::getByName('percent(decimals=2)')->toVerbal($dRec->discount);
    			$class = ($settings->discountType == 'amount') ? 'external-discount-amount' : 'external-discount-percent';
    			
    			$row->finalPrice .= "<span class='{$class}'> {$discount}</span>";
    		}
    		
    		$fullCode = cat_products::getVerbal($dRec->productId, 'code');
    		$row->code = substr($fullCode, 0, 10);
    		$row->code = "<span title={$fullCode}>{$row->code}</span>";
    		
    		$data->rows[$dRec->id] = $row;
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
    	
    	if($action == 'addtocart'){
    		if(!$mvc->haveRightFor('viewexternal', $rec)){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
}