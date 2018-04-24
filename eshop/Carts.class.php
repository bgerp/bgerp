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
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'payments';
    
    
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
	public $canView = 'every_one';
	
	
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
    	$this->FLD('productCount', 'int', 'caption=Общи данни->Брой,silent');
    	$this->FLD('paymentId', 'key(mvc=cond_PaymentMethods,select=title,allowEmpty)', 'caption=Общи данни->Плащане');
    	$this->FLD('termId', 'key(mvc=cond_DeliveryTerms,select=codeName,allowEmpty)', 'caption=Общи данни->Доставка');
    	$this->FLD('timeId', 'key(mvc=eshop_DeliveryTimes,select=title,allowEmpty)', 'caption=Общи данни->Време');
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
    	$this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Плащания->Валута');
    	$this->FLD('chargeVat', 'enum(yes=Включено ДДС в цените, separate=Отделен ред за ДДС, exempt=Oсвободено от ДДС, no=Без начисляване на ДДС)', 'caption=Плащания->ДДС режим');
    	$this->setDbIndex('brid');
    	$this->setDbIndex('userId');
    }
    
    
    public function act_addToCart()
    {
    	$this->requireRightFor('addtocart');
    	expect($eshopProductId = Request::get('eshopProductId', 'int'));
    	expect($productId = Request::get('productId', 'int'));
    	expect($packQuantity = Request::get('packQuantity', 'double'));
    	
    	$cartId = self::force();
    	eshop_CartDetails::addToCart($cartId, $eshopProductId, $productId, $packQuantity);
    	
    	return followRetUrl(NULL, 'Артикулът е успешно добавен в кошницата');
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
    		$settings = eshop_Settings::getSettings('cms_Domains', $domainId);
    		$chargeVat = isset($settings->chargeVat) ? $settings->chargeVat : 'yes';
    		$currencyId = isset($settings->chargeVat) ? $settings->currencyId : acc_Periods::getBaseCurrencyCode();
    		$ip = core_Users::getRealIpAddr();
    		$rec = (object)array('ip' => $ip,'brid' => $brid, 'domainId' => $domainId, 'userId' => $userId, 'currencyId' => $currencyId, 'chargeVat' => $chargeVat, 'state' => 'draft');
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
    	
    	$dQuery = eshop_CartDetails::getQuery();
    	$dQuery->where("#cartId = {$rec->id}");
    	$dQuery->XPR("count", 'int', 'COUNT(#id)');
    	$dQuery->XPR("sum", 'double', 'SUM(#finalPrice * #finalQuantity)');
    	$dRec = $dQuery->fetch();
    	
    	$rec->productCount = (!empty($dRec->count)) ? $dRec->count : 0;
    	$rec->total = (!empty($dRec->sum)) ? $dRec->sum : 0;
    	$id = $this->save_($rec, 'productCount,total');
    	
    	return $id;
    }
    
    
    /**
     * Какъв е статуса на кошницата
     */
    public static function getStatus()
    {
    	$tpl = new core_ET("");
    	$cartId = self::force(1, NULL, FALSE);
    	if(empty($cartId)) return $tpl;
    	
    	$cartRec = self::fetch($cartId);
    	$img = ht::createImg(array('height' => '16px', 'width' => '16px', 'src' => sbf("img/16/shopping_carts_blue.png", '')));
    	
    	$amount = core_Type::getByName('double(smartRound)')->toVerbal($cartRec->total);
    	$count = core_Type::getByName('int')->toVerbal($cartRec->productCount);
    	if(empty($count)) return NULL;
    	
    	$hint = tr("В кошницата има|* {$count} |продукта за|* 300 лв.");
    	$text = tr('Кошница');
    	$tpl = new core_ET("<div>[#img#]&nbsp;([#count#])&nbsp;[#text#]</div>");
    	$tpl->replace($img, 'img');
    	$tpl->replace($text, 'text');
    	$tpl->replace($count, 'count');
    	$tpl = ht::createLink($tpl, array('eshop_Carts', 'view', $cartId), FALSE, "title={$hint}");
    	
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
    
    
    public function act_View()
    {
    	expect($id = Request::get('id', 'int'));
    	expect($rec = self::fetch($id));
    	$this->requireRightFor('view', $rec);
    	
    	$tpl = new core_ET("");
    	$row = self::recToVerbal($rec);
    	$data = (object)array('rec' => $rec, 'row' => $row);
    	$this->prepareExternalCart($data);
    	if(!count($data->recs)) return followRetUrl(NULL, 'Кошницата е празна');
    	
    	$tpl = $this->renderExternalCart($data);
    	Mode::set('wrapper', 'cms_page_External');
    	
    	return $tpl;
    }
    
    
    private function prepareExternalCart($data)
    {
    	$fields = cls::get('eshop_CartDetails')->selectFields();
    	$fields['-external'] = TRUE;
    	
    	$settings = eshop_Settings::getSettings('cms_Domains', cms_Domains::getPublicDomain()->id);
    	$priceHead = tr('Цена') . " " . $settings->currencyId;
    	$data->listFields = arr::make("code=Код,productId=Артикул,packagingId=Опаковка,quantity=К-во,finalPrice={$priceHead},btn=|*&nbsp;");
    	
    	$data->productRecs = $data->productRows = array();
    	$dQuery = eshop_CartDetails::getQuery();
    	$dQuery->where("#cartId = {$data->rec->id}");
    	while($dRec = $dQuery->fetch()){
    		$data->recs[$dRec->id] = $dRec;
    		$data->rows[$dRec->id] = eshop_CartDetails::recToVerbal($dRec, $fields);
    	}
    	
    	if(eshop_CartDetails::haveRightFor('removeexternal', (object)array('cartId' => $data->rec->id))){
    		$data->emptyCartUrl = toUrl(array('eshop_CartDetails', 'removeexternal', 'cartId' => $data->rec->id), 'absolute');
    	}
    }
    
    
    private function renderExternalCart($data)
    {
    	$tpl = getTplFromFile("eshop/tpl/SingleLayoutCartExternal.shtml");
    	$tpl->placeObject($data->row);
    	
    	$data->listTableMvc = cls::get('eshop_CartDetails');
    	$table = cls::get('core_TableView', array('mvc' => $data->listTableMvc, 'tableClass' => 'optionsTable'));
    	plg_RowTools2::on_BeforeRenderListTable($data->listTableMvc, $tpl, $data);
    	$tpl->replace($table->get($data->rows, $data->listFields), 'CART_TABLE');
    	
    	if(isset($data->emptyCartUrl)){
    		$emptyBtn = ht::createBtn('Изпразни', $data->emptyCartUrl, NULL, NULL, 'title=Изпразване на кошницата,ef_icon=img/16/bin_closed.png');
    		$tpl->replace($emptyBtn, 'EMPTY_BTN');
    	}
    	
    	return $tpl;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'view' && isset($rec)){
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
    	}
    	
    	if($action == 'addtocart' && isset($rec)){
    		
    	}
    }
}