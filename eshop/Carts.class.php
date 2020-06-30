<?php


/**
 * Мениджър за кошница на онлайн магазина
 *
 *
 * @category  bgerp
 * @package   eshop
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class eshop_Carts extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Кошници в онлайн магазина';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'eshop_InitiatorPaymentIntf';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'orderDate,activatedOn,createdOn';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, eshop_Wrapper, plg_Rejected, plg_Modified,acc_plg_DocumentSummary,plg_Sorting';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,productCount=Артикули,total=Сума,saleId,userId,createdOn=Създаване,activatedOn=Активиране,domainId';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Кошница';
    
    
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
    public $canList = 'eshop,ceo';
    
    
    /**
     * Кой може да създава нова продажба?
     */
    public $canMakenewsale = 'eshop,sales,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canViewexternal = 'every_one';
    
    
    /**
     * Кой може да откаже плащане?
     */
    public $canAbortpayment = 'every_one';
    
    
    /**
     * Кой може да потвърди плащане?
     */
    public $canConfirmpayment = 'every_one';
    
    
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
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'eshop/tpl/SingleLayoutCart.shtml';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'sales,eshop,ceo';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'productCount';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/trolley.png';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('ip', 'varchar', 'caption=Ип,input=none');
        $this->FLD('brid', 'varchar(8)', 'caption=Браузър,input=none');
        $this->FLD('domainId', 'key(mvc=cms_Domains, select=titleExt)', 'caption=Домейн,input=hidden,silent');
        $this->FLD('userId', 'key(mvc=core_Users, select=nick)', 'caption=Потребител,input=none');
        $this->FLD('freeDelivery', 'enum(yes=Да,no=Не)', 'caption=Безплатна доставка,input=none,notNull,value=no');
        $this->FLD('totalNoVat', 'double(decimals=2)', 'caption=Общи данни->Стойност без ДДС,input=none,summaryCaption= Сума (без ДДС), summary=amount');
        $this->FLD('deliveryNoVat', 'double(decimals=2)', 'caption=Общи данни->Доставка без ДДС,input=none,summaryCaption= Транспорт (без ДДС) , summary=amount');
        $this->FLD('deliveryTime', 'time', 'caption=Общи данни->Срок на доставка,input=none');
        $this->FLD('total', 'double(decimals=2)', 'caption=Общи данни->Стойност,input=none');
        $this->FLD('paidOnline', 'enum(no=Не,yes=Да)', 'caption=Общи данни->Платено,input=none,notNull,value=no');
        $this->FLD('productCount', 'int', 'caption=Общи данни->Брой,input=none, summary=quantity,summaryCaption=  Брой артикули');
        
        $this->FLD('personNames', 'varchar(255,autocomplete=off)', 'caption=Имена,class=contactData,hint=Вашето име||Your name,mandatory,silent');
        $this->FLD('email', 'email(valid=drdata_Emails->validate,autocomplete=off)', 'caption=Имейл,hint=Вашият имейл||Your email,mandatory');
        $this->FLD('tel', 'drdata_PhoneType(type=tel,nullIfEmpty,unrecognized=warning,autocomplete=off)', 'caption=Телефон,hint=Вашият телефон,mandatory');
        $this->FLD('country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Държава,mandatory');
        
        $this->FLD('termId', 'key(mvc=cond_DeliveryTerms,select=codeName)', 'caption=Доставка->Начин,autocomplete=off,removeAndRefreshForm=deliveryCountry|deliveryPCode|deliveryPlace|deliveryAddress|deliveryData|locationId,silent,mandatory');
        $this->FLD('locationId', 'key(mvc=crm_Locations,select=title)', 'caption=Доставка->Локация,input=none,silent,removeAndRefreshForm=deliveryData|deliveryCountry|deliveryPCode|deliveryPlace|deliveryAddress,after=termId');
        $this->FLD('deliveryCountry', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Доставка->Държава,hint=Страна за доставка');
        $this->FLD('deliveryPCode', 'varchar(16)', 'caption=Доставка->П. код,hint=Пощенски код за доставка');
        $this->FLD('deliveryPlace', 'varchar(64)', 'caption=Доставка->Град,hint=Населено място: град или село и община');
        $this->FLD('deliveryAddress', 'varchar(255)', 'caption=Доставка->Адрес,hint=Вашият адрес');
        $this->FLD('deliveryData', 'blob(serialize, compress)', 'input=none');
        $this->FLD('instruction', 'richtext(rows=2)', 'caption=Доставка->Инструкции');
        
        $this->FLD('paymentId', 'key(mvc=cond_PaymentMethods,select=title,allowEmpty)', 'caption=Плащане->Начин,mandatory');
        $this->FLD('makeInvoice', 'enum(none=Без фактуриране,person=Фактура на лице, company=Фактура на фирма)', 'caption=Плащане->Фактуриране,silent,removeAndRefreshForm=locationId|invoiceNames|invoiceUicNo|invoiceVatNo|invoiceAddress|invoicePCode|invoicePlace|invoiceCountry|invoiceNames');
        
        $this->FLD('saleFolderId', 'key(mvc=doc_Folders)', 'caption=Данни за фактуриране->Папка,input=none,silent,removeAndRefreshForm=locationId|invoiceNames|invoiceVatNo|invoiceUicNo|invoiceAddress|invoicePCode|invoicePlace|invoiceCountry|deliveryData|deliveryCountry|deliveryPCode|deliveryPlace|deliveryAddress|makeInvoice');
        $this->FLD('invoiceNames', 'varchar(128)', 'caption=Данни за фактуриране->Наименование,invoiceData,hint=Име,input=none,mandatory');
        
        $this->FLD('invoiceVatNo', 'drdata_VatType', 'caption=Данни за фактуриране->ДДС №||VAT ID,input=hidden,invoiceData');
        $this->FLD('invoiceUicNo', 'varchar(26)', 'caption=Данни за фактуриране->ЕИК №,input=hidden,invoiceData');
        
        $this->FLD('invoiceCountry', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Данни за фактуриране->Държава,hint=Държава по регистрация,input=none,invoiceData');
        $this->FLD('invoicePCode', 'varchar(16)', 'caption=Данни за фактуриране->П. код,invoiceData,hint=Пощенски код на фирмата,input=none');
        $this->FLD('invoicePlace', 'varchar(64)', 'caption=Данни за фактуриране->Град,invoiceData,hint=Населено място: град или село и община,input=none');
        $this->FLD('invoiceAddress', 'varchar(255)', 'caption=Данни за фактуриране->Адрес,invoiceData,hint=Адрес на регистрация на фирмата,input=none');
        
        $this->FLD('info', 'richtext(rows=2)', 'caption=Общи данни->Забележка,input=none');
        $this->FLD('state', 'enum(draft=Чернова,active=Активно,closed=Приключено,rejected=Оттеглен)', 'caption=Състояние,input=none,notNull,value=active');
        $this->FLD('saleId', 'key(mvc=sales_Sales)', 'caption=Продажба,input=none');
        $this->FLD('activatedOn', 'datetime(format=smartTime)', 'caption=Активиране||Activated->На,input=none');
        $this->FLD('haveOnlyServices', 'enum(no=Не,yes=Да)', 'caption=Само услуги,input=none,notNull,value=no');
        $this->FLD('haveProductsWithExpectedDelivery', 'enum(no=Не,yes=Да)', 'caption=Очаквана доставка,input=none,notNull,value=no');
        $this->XPR('orderDate', 'datetime', 'COALESCE(#activatedOn, #createdOn)', 'caption=Дата');
        
        $this->setDbIndex('brid');
        $this->setDbIndex('userId');
        $this->setDbIndex('domainId');
    }
    
    
    /**
     * Сортира записите по време на създаване
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->listFilter->FNC('domain', 'key(mvc=cms_Domains,select=titleExt,allowEmpty)', 'caption=Домейн,input,refreshForm,placeholder=Всички');
        $data->listFilter->FNC('type', 'enum(all=Всички,draft=Чернови,active=Активни,empty=Празни,users=От потребител,anonymous=Без потребител,pendingSales=С чакащи продажби)', 'caption=Вид,input,silent,refreshForm');
        
        $data->listFilter->setDefault('type', 'all');
        $data->listFilter->setDefault('domain', cms_Domains::getCurrent('id', false));
        
        $data->listFilter->showFields .= ',domain,type';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input();
        
        if ($filter = $data->listFilter->rec) {
            if (!empty($filter->domain)) {
                unset($data->listFields['domainId']);
                $data->query->where("#domainId = {$filter->domain}");
            }
            
            if (!empty($filter->type)) {
                switch ($filter->type) {
                    case 'active':
                    case 'draft':
                        $data->query->where("#state = '{$filter->type}'");
                        break;
                    case 'empty':
                        $data->query->where('#productCount = 0 OR #productCount IS NULL');
                        break;
                    case 'users':
                        $data->query->where("#userId IS NOT NULL OR (#email IS NOT NULL OR #email != '')");
                        break;
                    case 'anonymous':
                        $data->query->where('#userId IS NULL AND #email IS NULL');
                        break;
                    case 'pendingSales':
                        $data->query->EXT('saleState', 'sales_Sales', 'externalName=state,externalKey=saleId');
                        $data->query->where("#saleId IS NOT NULL AND #saleState = 'pending'");
                        break;
                }
            }
        }
    }
    
    
    /**
     * Екшън за добавяне на артикул в кошницата
     *
     * @return mixed
     */
    public function act_addToCart()
    {
        // Взимане на данните от заявката
        $this->requireRightFor('addtocart');
        $eshopProductId = Request::get('eshopProductId', 'int');
        $productId = Request::get('productId', 'int');
        $packagingId = Request::get('packagingId', 'int');
        $packQuantity = Request::get('packQuantity', 'double');
        
        // Пушване на езика от публичната част
        $lang = cms_Domains::getPublicDomain('lang');
        core_Lg::push($lang);
        
        // Данните от опаковката
        if (isset($productId)) {
            $packRec = cat_products_Packagings::getPack($productId, $packagingId);
            $quantityInPack = (is_object($packRec)) ? $packRec->quantity : 1;
            
            // Проверка на к-то
            $warning = '';
            if (!deals_Helper::checkQuantity($packagingId, $packQuantity, $warning)) {
                $msg = $warning;
                $success = false;
                $skip = true;
            }
        }
        
        // Ако има избран склад, проверка дали к-то е допустимо
        $msg = '|Проблем при добавянето на артикула|*!';
        
        $maxQuantity = eshop_CartDetails::getMaxQuantity($productId, $quantityInPack, $eshopProductId);
        if (isset($maxQuantity) && $maxQuantity < $packQuantity) {
            $msg = '|Избраното количество не е налично|*';
            $success = false;
            $skip = true;
        }
        
        if (!eshop_ProductDetails::getPublicDisplayPrice($productId, $packagingId)) {
            $msg = '|Артикулът няма цена|*';
            $success = false;
            $skip = true;
        }
        
        $success = false;
        if (!empty($eshopProductId) && !empty($productId) && !empty($packQuantity) && $skip !== true) {
            try {
                // Форсиране на кошница и добавяне на артикула в нея
                $cartId = self::force();
                $this->requireRightFor('addtocart', $cartId);
                eshop_CartDetails::addToCart($cartId, $eshopProductId, $productId, $packagingId, $packQuantity, $quantityInPack);
                $this->updateMaster($cartId);
                
                $exRec = eshop_CartDetails::fetch("#cartId = {$cartId} AND #eshopProductId = {$eshopProductId} AND #productId = {$productId} AND #packagingId = {$packagingId}");
                
                $packagingName = cat_UoM::getShortName($packagingId);
                $packType = cat_UoM::fetchField($packagingId, 'type');
                if ($packType == 'packaging') {
                    $packagingName = str::getPlural($exRec->packQuantity, $packagingName, true);
                }
                
                $packQuantity = core_Type::getByName('double(smartRound)')->toVerbal($exRec->packQuantity);
                $productName = eshop_ProductDetails::getPublicProductName($eshopProductId, $productId);
                
                $settings = cms_Domains::getSettings();
                $addText = new core_ET($settings->addProductText);
                
                $cartName = self::getCartDisplayName();
                $cartName = ht::createLink($cartName, array('eshop_Carts', 'view', $cartId), false, 'class=eshop-card-add-item-status');
                
                $addText->append($cartName, 'cartName');
                $addText->append($packagingName, 'packagingId');
                $addText->append($productName, 'productName');
                $addText->append($packQuantity, 'packQuantity');
                
                $msg = $addText->getContent();
                $success = true;
                
                vislog_History::add("Добавяне на артикул «{$productName}» в количка");
            } catch (core_exception_Expect $e) {
                reportException($e);
                $msg = '|Артикулът не е добавен|*!';
            }
        }
        
        // Ако режимът е за AJAX
        if (Request::get('ajax_mode')) {
            core_Statuses::newStatus($msg, ($success === true) ? 'notice' : 'error');
            
            // Ще се реплейсне статуса на кошницата
            $resObj = new stdClass();
            $resObj->func = 'html';
            $resObj->arg = array('id' => 'cart-external-status', 'html' => self::getStatus($cartId)->getContent(), 'replace' => true);
            if ($success === true) {
                $resObj2 = new stdClass();
                $resObj2->func = 'Sound';
                $resObj2->arg = array('soundOgg' => sbf('sounds/bell.ogg', ''), 'soundMp3' => sbf('sounds/bell.mp3', ''),);
            } else {
                $resObj2 = new stdClass();
            }
            
            // Форсираме рефреша след връщане назад
            $resObjReload = new stdClass();
            $resObjReload->func = 'forceReloadAfterBack';
            
            // Махане на другите статуси от екрана
            $resObj3 = new stdClass();
            $resObj3->func = 'clearStatuses';
            $resObj3->arg = array('type' => 'notice');
            
            $resObj4 = new stdClass();
            $resObj4->func = 'addClass';
            $resObj4->arg = array('id' => 'maincontent', 'class' => 'hasLogoutBlock');
            
            $hitTime = Request::get('hitTime', 'int');
            $idleTime = Request::get('idleTime', 'int');
            $statusData = status_Messages::getStatusesData($hitTime, $idleTime);
            
            $res = array_merge(array($resObj, $resObj2, $resObjReload, $resObj3, $resObj4), (array) $statusData);
            core_Lg::pop();
            
            return $res;
        }
        core_Lg::pop();
        
        return followRetUrl();
    }
    
    
    /**
     * Форсира чернова на нова кошница
     *
     * @param int|NULL $userId   - потребител (ако има)
     * @param int|NULL $domainId - домейн, ако не е подаден се взима от менюто в което е групата
     * @param bool     $bForce   - да форсира ли нова кошница, ако няма
     *
     * @return int|NULL - ид на кошницата
     */
    public static function force($domainId = null, $userId = null, $bForce = true)
    {
        // Дефолтни данни
        $userId = isset($userId) ? $userId : core_Users::getCurrent('id', false);
        $domainId = isset($domainId) ? $domainId : cms_Domains::getPublicDomain()->id;
        $brid = log_Browsers::getBrid();
        
        // Ако има потребител се търси има ли чернова кошница за този потребител, ако не е логнат се търси по Брид-а
        $where = (isset($userId)) ? "#userId = '{$userId}'" : "#userId IS NULL AND #brid = '{$brid}'";
        $rec = self::fetch("{$where} AND #state = 'draft' AND #domainId = {$domainId}");
        
        if (empty($rec) && $bForce === true) {
            $ip = core_Users::getRealIpAddr();
            $rec = (object) array('ip' => $ip,'brid' => $brid, 'domainId' => $domainId, 'userId' => $userId, 'state' => 'draft', 'productCount' => 0);
            self::save($rec);
            
            vislog_History::add('Създаване на количка');
        }
        
        return $rec->id;
    }
    
    
    /**
     * Последната активна поръчка
     *
     * @param int|NULL $domainId
     * @param int|NULL $userId
     *
     * @return stdClass|FALSE
     */
    private static function getLastActivatedCart($domainId = null, $userId = null)
    {
        $userId = isset($userId) ? $userId : core_Users::getCurrent('id', false);
        $domainId = isset($domainId) ? $domainId : cms_Domains::getPublicDomain()->id;
        $brid = log_Browsers::getBrid();
        
        // Ако има потребител се търси има ли чернова кошница за този потребител, ако не е логнат се търси по Брид-а
        $where = (isset($userId)) ? "#userId = '{$userId}'" : "#userId IS NULL AND #brid = '{$brid}'";
        $query = self::getQuery();
        $query->where("{$where} AND #state = 'active' AND #domainId = {$domainId}");
        $query->orderBy('activatedOn', 'DESC');
        
        return $query->fetch();
    }
    
    
    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     *
     * @return int|false $id ид-то на обновения запис
     */
    public function updateMaster_($id)
    {
        $rec = $this->fetchRec($id);
        if (!$rec) {
            
            return;
        }
        
        $rec->freeDelivery = 'no';
        $rec->productCount = $rec->total = $rec->totalNoVat = 0;
        $rec->deliveryNoVat = $rec->deliveryTime = null;
        
        $dQuery = eshop_CartDetails::getQuery();
        $dQuery->where("#cartId = {$rec->id}");
        $dQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
        $count = $dQuery->count();
        $dRecs = $dQuery->fetchAll();
        
        $haveOnlyServices = 'yes';
        array_walk($dRecs, function ($a) use (&$haveOnlyServices) {
            if ($a->canStore == 'yes') {
                $haveOnlyServices = 'no';
            }
        });
        
        $rec->haveOnlyServices = $haveOnlyServices;
        $rec->haveProductsWithExpectedDelivery = 'no';
        $settings = cms_Domains::getSettings($rec->domainId);
        
        foreach ($dRecs as $dRec) {
            $rec->productCount++;
            $finalPrice = currency_CurrencyRates::convertAmount($dRec->finalPrice, null, $dRec->currencyId);
            
            if (!$dRec->discount) {
                $finalPrice -= $finalPrice * $dRec->discount;
            }
            $sum = $finalPrice * ($dRec->quantity / $dRec->quantityInPack);
            
            if ($dRec->haveVat == 'yes') {
                $rec->totalNoVat += round($sum / (1 + $dRec->vat), 4);
                $rec->total += round($sum, 4);
            } else {
                $rec->totalNoVat += round($sum, 4);
                $rec->total += round($sum * (1 + $dRec->vat), 4);
            }
            
            // Дигане на флаг ако има артикули очакващи доставка
            if($rec->haveProductsWithExpectedDelivery != 'yes' && isset($settings->storeId) && $dRec->canStore == 'yes'){
                $quantityInStore = store_Products::getQuantity($dRec->productId, $settings->storeId, true);
                if($quantityInStore < $dRec->quantity){
                    $eshopProductRec = eshop_ProductDetails::fetch("#eshopProductId = {$dRec->eshopProductId} AND #productId = {$dRec->productId}", 'deliveryTime');
                    if(!empty($eshopProductRec->deliveryTime)){
                        $rec->haveProductsWithExpectedDelivery = 'yes';
                    }
                }
            }
        }
        
        // Ако има цена за доставка добавя се и тя
        if ($count) {
            $TransCalc = null;
            if ($delivery = eshop_CartDetails::getDeliveryInfo($rec, $TransCalc)) {
                if (is_object($TransCalc)) {
                    $TransCalc->onUpdateCartMaster($rec);
                }
                
                if ($delivery['amount'] >= 0) {
                    $rec->deliveryTime = $delivery['deliveryTime'];
                    $rec->deliveryNoVat = $delivery['amount'];
                    
                    // Ако има сума за безплатна доставка и доставката е над нея, тя не се начислява
                    $deliveryNoVat = $rec->deliveryNoVat;
                    if ($rec->freeDelivery == 'yes') {
                        $delivery = $deliveryNoVat = 0;
                    }
                    
                    $transportId = cat_Products::fetchField("#code = 'transport'", 'id');
                    $rec->totalNoVat += $deliveryNoVat;
                    $rec->total += $deliveryNoVat * (1 + cat_Products::getVat($transportId));
                } else {
                    $rec->deliveryNoVat = -1;
                }
            }
        }
        
        $rec->totalNoVat = round($rec->totalNoVat, 4);
        $rec->total = round($rec->total, 4);
        
        $id = $this->save_($rec, 'productCount,total,totalNoVat,deliveryNoVat,deliveryTime,freeDelivery,haveOnlyServices,haveProductsWithExpectedDelivery');
        
        return $id;
    }
    
    
    /**
     * Име на кошницата във външната част
     *
     * @return string $cartName
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
    public static function getStatus($cartId = null)
    {
        $tpl = new core_ET('[#text#]');
        
        $settings = cms_Domains::getSettings();
        if (empty($settings)) {
            
            return new core_ET(' ');
        }
        
        $cartId = ($cartId) ? $cartId : self::force(null, null, false);
        $url = array();
        
        if (isset($cartId)) {
            $cartRec = self::fetch($cartId);
            if ($settings->enableCart == 'no' && !$cartRec->productCount) {
                
                return new core_ET(' ');
            }
            
            $amount = currency_CurrencyRates::convertAmount($cartRec->total, null, null, $settings->currencyId);
            $amountVerbal = str_replace('&nbsp;', ' ', core_Type::getByName('double(decimals=2)')->toVerbal($amount));
            $count = core_Type::getByName('int')->toVerbal($cartRec->productCount);
            $url = array('eshop_Carts', 'view', $cartId);
            $str = ($count == 1) ? 'артикул' : 'артикула';
            $hint = "|Поръчайте|* {$count} |{$str} за|* {$amountVerbal} " . $settings->currencyId;
            
            if ($count) {
                $tpl->append(new core_ET("<span class='count'>[#count#]</span>"));
            }
        } else {
            $hint = 'Нямате избрани артикули|*!';
            if ($settings->enableCart == 'no') {
                
                return new core_ET(' ');
            }
        }
        
        // Пушване на езика от публичната част
        $lang = cms_Domains::getPublicDomain('lang');
        core_Lg::push($lang);
        
        $cartName = self::getCartDisplayName();
        $tpl->replace($cartName, 'text');
        $tpl->replace($count, 'count');
        
        $currentTab = Mode::get('currentExternalTab');
        $className = ($currentTab == 'eshop_Carts') ? 'selected-external-tab' : ' ';
        $className .= $count ? ' cardLink' : '';
        $url = ($currentTab != 'eshop_Carts') ? $url : array();
        
        $tpl = ht::createLink($tpl, $url, false, "title={$hint}, ef_icon=img/16/cart-black.png,class={$className},rel=nofollow");
        
        $tpl->removeBlocks();
        $tpl->removePlaces();
        
        core_Lg::pop();
        
        return $tpl;
    }
    
    
    /**
     * Финализиране на поръчката
     */
    public function act_Finalize()
    {
        $retUrl = getRetUrl();
        
        Request::setProtected('description,accountId');
        $this->requireRightFor('finalize');
        expect($id = Request::get('id', 'int'));
        expect($rec = self::fetch($id));
        $msg = '|Благодарим за поръчката|*!';
        if ($rec->state == 'active') {
            if(countR($retUrl)){
                
                return new Redirect($retUrl, 'Има вече такава поръчка');
            }
            
            return new Redirect(cls::get('eshop_Groups')->getUrlByMenuId(null), $msg);
        }
        
        $description = Request::get('description', 'varchar');
        $accountId = Request::get('accountId', 'key(mvc=bank_OwnAccounts)');
        
        $this->requireRightFor('finalize', $rec);
        $cu = core_Users::getCurrent('id', false);
        
        Mode::set('currentExternalTab', 'eshop_Carts');
        
        try{
            $saleRec = self::forceSale($rec);
        } catch(core_exception_Expect $e){
            reportException($e);
            $saleRec = null;
        }
        
        if (empty($saleRec)) {
            $this->logErr('Проблем при генериране на онлайн продажба', $rec->id);
            $errorMs = 'Опитайте пак! Имаше проблем при завършването на поръчката! Ако все още имате проблем, свържете се с нас.';
            
            if(countR($retUrl)){
                
                return new Redirect($retUrl, $errorMs, 'error');
            }
            
            return new Redirect(array('eshop_Carts', 'view', $rec->id), $errorMs, 'error');
        }
        
        
        // Ако е партньор и има достъп до нишката, директно се редиректва към нея
        $colabUrl = null;
        if (core_Packs::isInstalled('colab') && isset($cu) && core_Users::isContractor($cu)) {
            $threadRec = doc_Threads::fetch($saleRec->threadId);
            if (colab_Threads::haveRightFor('single', $threadRec)) {
                $colabUrl = array('colab_Threads', 'single', 'threadId' => $saleRec->threadId);
            }
        }
        
        // Ако има основание, се форсира ново ПБД
        if (!empty($description)) {
            self::forceBankIncomeDocument($description, $saleRec, $accountId);
        }
        
        vislog_History::add('Финализиране на количка');
        if ($saleRec->_paymentInstructionsSend === true) {
            $msg .= ' |Изпратихме Ви имейл с инструкции за плащането|*.';
        }
        
        if (is_array($colabUrl) && countR($colabUrl)) {
            
            return new Redirect($colabUrl, $msg);
        }
        
        // Ако ще се показва страница за след плащането
        if ($Driver = cond_PaymentMethods::getDriver($rec->paymentId)) {
            $afterPaymentDisplay = $Driver->displayHtmlAfterPayment($rec->paymentId, $rec);
            if (isset($afterPaymentDisplay) && $afterPaymentDisplay instanceof core_ET) {
                Mode::set('wrapper', 'cms_page_External');
                core_Statuses::newStatus($msg);
                vislog_History::add('Показване на информация за плащането');
                
                return $afterPaymentDisplay;
            }
        }
        
        if(countR($retUrl)){
            
            return new Redirect($retUrl, 'Поръчката е активирана успешно');
        }
        
        return new Redirect(cls::get('eshop_Groups')->getUrlByMenuId(null), $msg);
    }
    
    
    /**
     * Форсира нов ПБД към продажбата на количката с подаденото основание.
     * Ако има ПБД с това основание, нищо не се прави. Ако няма се създава
     * нов ПБД и се контира ако е подадена сума
     *
     * @param string     $reason    - основание за ПБД
     * @param stdClass   $saleRec   - към коя продажба
     * @param int        $accountId - ид на наша сметка
     * @param float|null $amount    - сума на ПБД-то, ако няма е тази от продажбата
     *
     * @return stdClass $bRec     - запис на форсираното ПБД
     */
    public static function forceBankIncomeDocument($reason, $saleRec, $accountId, $amount = null)
    {
        $reason = trim($reason);
        
        // Ако има контиран ПБД с това основания, не се създава нов
        $bQuery = bank_IncomeDocuments::getQuery();
        $bQuery->where("#threadId = {$saleRec->threadId} AND #state IN ('active')");
        while ($bRec = $bQuery->fetch()) {
            if ($bRec->reason == $reason) {
                
                return $bRec;
            }
        }
        
        // Проверка има ли чернова на ПБД със същото основания
        $bankRec = null;
        $bQuery2 = bank_IncomeDocuments::getQuery();
        $bQuery2->where("#threadId = {$saleRec->threadId} AND #state IN ('draft', 'pending')");
        while ($bRec = $bQuery2->fetch()) {
            if ($bRec->reason == $reason) {
                $bankRec = $bRec;
                break;
            }
        }
        
        // Ако няма чернова се създава нов ПБД
        core_Users::forceSystemUser();
        $cu = core_Users::getCurrent('id', false);
        if (empty($bankRec)) {
            $incomeFields = array('reason' => $reason, 'termDate' => dt::today(), 'operation' => 'customer2bank', 'ownAccountId' => $accountId);
            $bankRec = bank_IncomeDocuments::create($saleRec->threadId, $incomeFields, true);
            
            bank_IncomeDocuments::logWrite('Създаване към онлайн продажба', $bankRec->id, 360, $cu);
        }
        
        // Ако има сума, то сумата на ПБД-то се подменя с тази от пристигналото плащане
        if (isset($amount)) {
            $fromCurrencyCode = (isset($accountId)) ? bank_OwnAccounts::getOwnAccountInfo($accountId)->currencyCode : $saleRec->currencyId;
            $amountDeal = currency_CurrencyRates::convertAmount($amount, null, $fromCurrencyCode, $saleRec->currencyId);
            $bankRec->amount = $amount;
            $bankRec->amountDeal = $amountDeal;
            bank_IncomeDocuments::save($bankRec);
        }
        
        core_Users::cancelSystemUser();
        
        return $bankRec;
    }
    
    
    /**
     * Форсира продажба към количката
     *
     * @param mixed $id
     * @param bool  $force
     * @param bool  $sendEmailIfNecessary
     *
     * @return stdClass $saleRec
     */
    public static function forceSale($id, $force = false, $sendEmailIfNecessary = true)
    {
        $rec = static::fetchRec($id);
        
        if ($force === false) {
            if (isset($rec->saleId)) {
                
                return sales_Sales::fetch($rec->saleId);
            }
        }
        
        Mode::push('eshopFinalize', true);
        $cu = core_Users::getCurrent('id', false);
        
        $company = null;
        $personNames = $rec->personNames;
        if ($rec->makeInvoice == 'company') {
            $company = $rec->invoiceNames;
        } elseif ($rec->makeInvoice == 'person') {
            $personNames = $rec->invoiceNames;
        }
        
        // Рутиране в папка
        $routerExplanation = null;
        if (isset($rec->saleFolderId)) {
            $Cover = doc_Folders::getCover($rec->saleFolderId);
            $folderId = $rec->saleFolderId;
        } else {
            $country = isset($rec->invoiceCountry) ? $rec->invoiceCountry : (isset($rec->deliveryCountry) ? $rec->deliveryCountry : $rec->country);
            if (!empty($rec->invoicePCode) || !empty($rec->invoicePlace) || !empty($rec->invoiceAddress)) {
                $pCode = $rec->invoicePCode;
                $place = $rec->invoicePlace;
                $address = $rec->invoiceAddress;
            } else {
                $pCode = $rec->deliveryPCode;
                $place = $rec->deliveryPlace;
                $address = $rec->deliveryAddress;
            }
            $folderId = marketing_InquiryRouter::route($company, $personNames, $rec->email, $rec->tel, $country, $pCode, $place, $address, $rec->brid, $rec->invoiceVatNo, $rec->invoiceUicNo, $routerExplanation, $rec->domainId);
            $Cover = doc_Folders::getCover($folderId);
        }
        
        $settings = cms_Domains::getSettings($rec->domainId);
        $templateId = ($settings->lg == 'bg') ? eshop_Setup::get('SALE_DEFAULT_TPL_BG') : eshop_Setup::get('SALE_DEFAULT_TPL_EN');
        $templateLang = doc_TplManager::fetchField($templateId, 'lang');
        
        core_Lg::push($templateLang);
        
        // Форсиране на потребителя, ако има или системния потребител за създател на документа
        if ($cu && $cu != core_Users::SYSTEM_USER) {
            core_Users::sudo($cu);
        } else {
            core_Users::forceSystemUser();
        }
        $cu = core_Users::getCurrent('id', false);
        
        // Дефолтни данни на продажбата
        $fields = array('valior' => dt::today(),
            'template' => $templateId,
            'deliveryTermId' => $rec->termId,
            'deliveryTermTime' => $rec->deliveryTime,
            'paymentMethodId' => $rec->paymentId,
            'makeInvoice' => ($rec->makeInvoice == 'none') ? 'no' : 'yes',
            'chargeVat' => $settings->chargeVat,
            'currencyId' => $settings->currencyId,
            'shipmentStoreId' => $settings->storeId,
            'deliveryLocationId' => $rec->locationId,
            'deliveryData' => $rec->deliveryData,
            'onlineSale' => true,
        );
        
        $folderIncharge = doc_Folders::fetchField($folderId, 'inCharge');
        if (haveRole('sales', $folderIncharge)) {
            $fields['dealerId'] = $folderIncharge;
        } elseif (!empty($settings->dealerId)) {
            $fields['dealerId'] = $settings->dealerId;
        }
        
        // Създаване на продажба по количката
        try{
            $saleId = sales_Sales::createNewDraft($Cover->getClassId(), $Cover->that, $fields);
        } catch(core_exception_Expect $e){
            reportException($e);
            eshop_Carts::logErr("Грешка при създаване на онлайн продажба: '{$e->getMessage()}'", $rec->id);
        }
        
        if (empty($saleId)) {
            
            return false;
        }
        
        sales_Sales::logWrite('Създаване от онлайн поръчка', $saleId, 360, $cu);
        if (!empty($routerExplanation)) {
            sales_Sales::logDebug($routerExplanation, $saleId, 7);
        }
        eshop_Carts::logDebug("Създаване на продажба #Sal{$saleId} към онлайн поръчка", $rec->id);
        
        // Добавяне на артикулите от количката в продажбата
        $dQuery = eshop_CartDetails::getQuery();
        $dQuery->where("#cartId = {$rec->id}");
        while ($dRec = $dQuery->fetch()) {
            $price = ($dRec->amount / $dRec->quantity);
            $price = isset($dRec->discount) ? ($price / (1 - $dRec->discount)) : $price;
            
            if ($dRec->haveVat == 'yes') {
                $price /= 1 + $dRec->vat;
            }
            
            $paramsText = eshop_CartDetails::getUniqueParamsAsText($dRec->eshopProductId, $dRec->productId, true);
            $notes = (!empty($paramsText)) ? $paramsText : null;
            
            $price = currency_CurrencyRates::convertAmount($price, null, $dRec->currencyId);
            sales_Sales::addRow($saleId, $dRec->productId, $dRec->packQuantity, $price, $dRec->packagingId, $dRec->discount, null, null, $notes);
        }
        
        // Добавяне на транспорта, ако има
        if (isset($rec->deliveryNoVat) && $rec->deliveryNoVat >= 0) {
            $transportId = cat_Products::fetchField("#code = 'transport'", 'id');
            $deliveryNoVat = (!empty($settings->freeDelivery) && $rec->freeDelivery == 'yes') ? 0 : $rec->deliveryNoVat;
            sales_Sales::addRow($saleId, $transportId, 1, $deliveryNoVat);
        }
        
        // Ако е платено онлайн
        if ($rec->paidOnline == 'yes') {
            
            // Продажбата се активира
            $saleRec = sales_Sales::fetchRec($saleId);
            $saleRec->contoActions = 'activate';
            $saleRec->isContable = 'activate';
            sales_Sales::save($saleRec);
            sales_Sales::conto($saleRec->id);
            cls::get('sales_Sales')->updateMaster($saleRec->id);
            eshop_Carts::logDebug("Активиране на продажба #Sal{$saleId} към онлайн поръчка", $rec->id);
        } else {
            
            // Ако не е става на заявка
            $saleRec = self::makeSalePending($saleId);
            eshop_Carts::logDebug("Продажбата #Sal{$saleId} към онлайн поръчка, става на заявка", $rec->id);
        }
        
        self::activate($rec, $saleRec->id);
        
        doc_Threads::doUpdateThread($saleRec->threadId);
        if (!(core_Packs::isInstalled('colab') && isset($cu) && core_Users::isContractor($cu))) {
            if ($sendEmailIfNecessary === true) {
                self::sendEmail($rec, $saleRec);
                doc_Threads::doUpdateThread($saleRec->threadId);
                eshop_Carts::logDebug('Изпращане на имейл за продажба от онлайн поръчка', $rec->id);
            }
        }
        
        // Форсиране на контрагента в група, онлайн клинети
        $groupRec = (object)array('name' => 'Онлайн клиенти', 'sysId' => 'onlineClients', 'parentId' => crm_Groups::getIdFromSysId('customers'));
        $groupId = crm_Groups::forceGroup($groupRec);
        cls::get($saleRec->contragentClassId)->forceGroup($saleRec->contragentId, $groupId, false);
        
        if ($cu && $cu != core_Users::SYSTEM_USER) {
            core_Users::exitSudo($cu);
        } else {
            core_Users::cancelSystemUser();
        }
        
        core_Lg::pop();
        Mode::pop('eshopFinalize');
        
        // Нишката да остане отворена накрая
        $threadRec = doc_Threads::fetch($saleRec->threadId);
        $threadRec->state = 'opened';
        doc_Threads::save($threadRec, 'state');
        doc_Threads::updateThread($threadRec->id);
        
        return $saleRec;
    }
    
    
    /**
     * Продажба да се обърне в състояние заявка
     *
     * @param int $saleId
     *
     * @return stdClass $saleRec
     */
    private static function makeSalePending($saleId)
    {
        $saleRec = sales_Sales::fetch($saleId);
        $saleRec->state = 'pending';
        $saleRec->brState = 'draft';
        $saleRec->pendingSaved = true;
        
        sales_Sales::save($saleRec, 'state');
        
        return $saleRec;
    }
    
    
    /**
     * Активиране на количката
     *
     * @param stdClass $rec
     * @param int      $saleId
     */
    private static function activate($rec, $saleId)
    {
        $rec->saleId = $saleId;
        
        $isActivatedNow = false;
        if ($rec->state != 'active') {
            $rec->state = 'active';
            $rec->activatedOn = dt::now();
            $isActivatedNow = true;
        }
        
        self::save($rec, 'state,saleId,activatedOn');
        if ($isActivatedNow === true) {
            eshop_Carts::logDebug('Активиране на количката', $rec->id);
        }
    }
    
    
    /**
     * Изпраща имейл
     *
     * @param stdClass $rec
     * @param stdClass $saleRec
     */
    private static function sendEmail($rec, &$saleRec)
    {
        $settings = cms_Domains::getSettings($rec->domainId);
        if (empty($settings->inboxId)) {
            
            return;
        }
        
        $lang = cms_Domains::fetchField($rec->domainId, 'lang');
        core_Lg::push($lang);
        
        // Подготовка на тялото на имейла
        $file = ($lang == 'bg') ? 'eshop/tpl/email/PlacedOrderBg.shtml' : 'eshop/tpl/email/PlacedOrderEn.shtml';
        $body = getTplFromFile($file);
        $body->replace(new core_ET($settings->emailBodyIntroduction), 'INTRODUCTION');
        $body->replace(new core_ET($settings->emailBodyFooter), 'FOOTER');
        
        $threadCount = doc_Threads::count("#folderId = {$saleRec->folderId}");
        $makeInvoice = tr(self::getVerbal($rec, 'makeInvoice'));
        $body->replace($makeInvoice, 'MAKE_INVOICE');
        
        // Показване на информацията за доставка
        if (isset($rec->termId)) {
            $termName = cond_DeliveryTerms::getVerbal($rec->termId, 'term');
            if (empty($termName)) {
                $termName = cond_DeliveryTerms::getVerbal($rec->termId, 'codeName');
            }
            
            $termName = strip_tags(str_replace('<br>', ' ', $termName));
            $body->replace($termName, 'TERM_ID');
            $countryName = core_Type::getByName('key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg)')->toVerbal($rec->deliveryCountry);
            $pCode = core_Type::getByName('varchar')->toVerbal($rec->deliveryPCode);
            $place = core_Type::getByName('varchar')->toVerbal($rec->deliveryPlace);
            $place = (!empty($pCode)) ? "{$pCode} {$place}" : $place;
            $deliveryAddress = core_Type::getByName('varchar')->toVerbal($rec->deliveryAddress);
            $body->replace($countryName, 'DELIVERY_COUNTRY');
            if (!empty($place)) {
                $body->replace($place, 'PLACE');
            }
            if (!empty($rec->deliveryAddress)) {
                $body->replace($deliveryAddress, 'ADDRESS');
            }
        }
        
        $amount = currency_CurrencyRates::convertAmount($rec->total, null, null, $settings->currencyId);
        $amount = core_Type::getByName('double(decimals=2)')->toVerbal($amount);
        $amount = str_replace('&nbsp;', ' ', $amount);
        $body->replace("{$amount} {$settings->currencyId}", 'AMOUNT');
        
        // Ако има избран метод на плащане, добавя се и текста, който трябва да се добави до имейла
        if (isset($rec->paymentId)) {
            $paymentMethod = tr(cond_PaymentMethods::getTitleById($rec->paymentId));
            $body->append($paymentMethod, 'PAYMENT_NAME');
            
            if ($PaymentDriver = cond_PaymentMethods::getOnlinePaymentDriver($rec->paymentId)) {
                Mode::push('text', 'plain');
                $paymentText = $PaymentDriver->getText4Email($rec->paymentId, $rec);
                Mode::pop('text');
                if (!empty($paymentText)) {
                    $body->replace($paymentText, 'PAYMENT_TEXT');
                    $saleRec->_paymentInstructionsSend = true;
                }
            }
        }
        
        $body->replace($rec->personNames, 'NAME');
        if ($hnd = sales_Sales::getHandle($saleRec->id)) {
            $body->replace("#{$hnd}", 'SALE_HANDLER');
        }
        
        $Cover = doc_Folders::getCover($saleRec->folderId);
        if ($threadCount == 1) {
            $url = core_Forwards::getUrl('colab_FolderToPartners', 'Createnewcontractor', array('companyId' => (int) $Cover->that, 'email' => $rec->email, 'rand' => str::getRand(), 'className' => $Cover->className, 'userNames' => $rec->personNames, 'onlyPartner' => 'yes'), 604800);
            $url = "[link={$url}]" . tr('връзка||link') . '[/link]';
            $body->replace($url, 'REGISTER_LINK');
        }
        
        if($expectedDeliveryText = self::getExpectedDeliveryText($rec, $settings)){
            $body->replace($expectedDeliveryText, 'EXPECTED_DELIVERY');
        }
        
        // Името на 'Моята фирма' във футъра
        $companyName = tr(crm_Companies::fetchOwnCompany()->company);
        $body->replace($companyName, 'COMPANY_NAME');
        $body = core_Type::getByName('richtext')->fromVerbal($body->getContent());
        
        // Подготовка на имейла
        $emailRec = (object) array('subject' => tr('Онлайн поръчка') . " №{$rec->id}",
            'body' => $body,
            'folderId' => $saleRec->folderId,
            'originId' => $saleRec->containerId,
            'threadId' => $saleRec->threadId,
            'state' => 'active',
            'email' => $rec->email, 'tel' => $rec->tel, 'recipient' => $rec->personNames);
        
        // Активиране на изходящия имейл
        core_Users::forceSystemUser();
        $cu = core_Users::getCurrent('id', false);
        Mode::set('isSystemCanSingle', true);
        
        email_Outgoings::save($emailRec);
        
        $files = cls::get('email_Outgoings')->getAttachments($emailRec);
        $documents = doc_RichTextPlg::getAttachedDocs($emailRec->body);
        
        email_Outgoings::logWrite('Създаване от онлайн поръчка', $emailRec->id, 360, $cu);
        cls::get('email_Outgoings')->invoke('AfterActivation', array(&$emailRec));
        email_Outgoings::logWrite('Активиране', $emailRec->id, 360, $cu);
        
        // Изпращане на имейла
        $options = (object) array('encoding' => 'utf-8', 'boxFrom' => $settings->inboxId, 'emailsTo' => $emailRec->email);
        
        // Прикачване на прикачените файлове
        $files = cls::get('email_Outgoings')->getAttachments($emailRec);
        if (is_array($files) && countR($files)) {
            $options->attachmentsSet = implode(',', array_keys($files));
        }
        
        // Прикачване на прикачените документи
        if (is_array($documents)) {
            $attachedDocs = array();
            foreach ($documents as $name => $doc) {
                $attachedDocs[$name] = "{$name}.pdf";
            }
            $options->documentsSet = implode(',', $attachedDocs);
        }
        
        email_Outgoings::send($emailRec, $options, $lang);
        email_Outgoings::logWrite('Send', $emailRec->id, 360, $cu);
        Mode::set('isSystemCanSingle', false);
        core_Users::cancelSystemUser();
        
        core_Lg::pop($lang);
    }
    
    
    /**
     * След изтриване
     *
     * @param core_Mvc   $mvc
     * @param stdClass   $res
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
     *
     * @return core_ET $tpl
     */
    public static function renderView($rec)
    {
        $rec = self::fetchRec($rec);
        $lang = cms_Domains::getPublicDomain('lang');
        core_Lg::push($lang);
        
        $tpl = getTplFromFile('eshop/tpl/SingleLayoutCartExternal.shtml');
        $tpl->replace(self::renderViewCart($rec), 'CART_TABLE');
        
        self::renderCartToolbar($rec, $tpl);
        self::renderCartSummary($rec, $tpl);
        self::renderCartOrderInfo($rec, $tpl);
        $tpl->replace(self::getCartDisplayName(), 'CART_NAME');
        $settings = cms_Domains::getSettings();
        
        if (!empty($settings->info)) {
            $tpl->replace(core_Type::getByName('richtext')->toVerbal($settings->info), 'COMMON_TEXT');
        }
        
        $cartInfo = tr('Всички цени са')  . ' ' . (($settings->chargeVat == 'yes') ? tr('с ДДС') : tr('без ДДС'));
        $tpl->replace($cartInfo . '.', 'VAT_STATUS');
        
        // Ако има последно активирана кошница да се показва като съобщение
        if ($lastCart = self::getLastActivatedCart()) {
            if ($lastCart->activatedOn >= dt::addSecs(-1 * 60 * 60 * 2, dt::now())) {
                $tpl->replace($lastCart->email, 'CHECK_EMAIL');
            }
        }
        core_Lg::pop();
        
        // Да се рефрешва по Ajax, трябвали да се рефрешне количката
        core_Ajax::subscribe($tpl, array('eshop_Carts', 'refreshCartIfNecessary', $rec->id, 'state' => $rec->state, 'total' => $rec->total, 'haveProductsWithExpectedDelivery' => $rec->haveProductsWithExpectedDelivery), 'eshop_Carts_Redirect', 1000);
        
        return $tpl;
    }
    
    
    /**
     * Рефрешва количката, ако има промяна
     *
     * @return array
     */
    public function act_refreshCartIfNecessary()
    {
        $id = Request::get('id', 'int');
        if (Request::get('ajax_mode')) {
            if (!empty($id)) {
                $exState = Request::get('state', 'varchar');
                $exTotal = Request::get('total', 'double');
                $exHaveProductsWithExpectedDelivery = Request::get('haveProductsWithExpectedDelivery', 'enum(yes,no)');
                
                $url = array();
                $currentRec = self::fetch($id, 'total,state,haveProductsWithExpectedDelivery');
                if($currentRec->state != $exState) {
                    $url = cls::get('eshop_Groups')->getUrlByMenuId(null);
                } elseif(trim($exTotal) != trim($currentRec->total) || $exHaveProductsWithExpectedDelivery != $currentRec->haveProductsWithExpectedDelivery){
                    $url = array($this, 'view', $id);
                }
                
                // Ако състоянието на количката не е чернова, се редиректва
                if (countR($url)) {
                    $resObj = new stdClass();
                    $resObj->func = 'redirect';
                    $resObj->arg = array('url' => toUrl($url));
                }
            }
            
            $hitTime = Request::get('hitTime', 'int');
            $idleTime = Request::get('idleTime', 'int');
            $statusData = status_Messages::getStatusesData($hitTime, $idleTime);
            $res = (isset($resObj->func)) ? array_merge(array($resObj), (array) $statusData) : (array) $statusData;
            
            return $res;
        }
        
        expect($id);
        redirect(cls::get('eshop_Groups')->getUrlByMenuId(null));
    }
    
    
    /**
     * Екшън за показване на външния изглед на кошницата
     */
    public function act_View()
    {
        Request::setProtected('accessToken');
        $this->requireRightFor('viewexternal');
        $id = Request::get('id', 'int');
        
        if (empty($id)) {
            redirect(cls::get('eshop_Groups')->getUrlByMenuId(null));
        }
        
        $rec = self::fetch($id);
        if (empty($rec)) {
            redirect(cls::get('eshop_Groups')->getUrlByMenuId(null));
        }
        
        // Ако има нова текуща количка, редирект към нея
        if ($newCartId = self::force($rec->domainId, null, false)) {
            if ($rec->id != $newCartId) {
                redirect(array($this, 'view', $newCartId));
            }
        }
        
        // Редирект към ешопа ако количката е активна
        if ($rec->state == 'active') {
            $shopUrl = cls::get('eshop_Groups')->getUrlByMenuId(null);
            redirect($shopUrl);
        }
        
        // Ако има токен за достъп
        $accessToken = Request::get('accessToken');
        if (!empty($accessToken)) {
            
            // И токена е валиден
            expect($token = str::checkHash($accessToken, 6, eshop_Setup::get('CART_ACCESS_SALT')), 'Невалиден токен за достъп');
            expect($token == "cart{$id}");
            
            // Ако количката няма потребител
            if (empty($rec->userId)) {
                $oldBrid = $rec->brid;
                
                // Ако новия брид е различен от стария обновява се
                $newBrid = log_Browsers::getBrid();
                $updateFields = array();
                if ($rec->brid != $newBrid) {
                    $rec->brid = $newBrid;
                    $updateFields[] = 'brid';
                }
                
                // Kоличката се присвоява на текущия потребител, само ако не е powerUser
                if ($cu = core_Users::getCurrent('id', false)) {
                    if (!core_Users::isPowerUser($cu)) {
                        $rec->userId = $cu;
                        $updateFields[] = 'userId';
                    }
                }
                
                // Така потребителя вече има достъп до количката
                if (countR($updateFields)) {
                    $this->save($rec, $updateFields);
                    log_System::add('eshop_Carts', "Присвоена количка:BRID {$oldBrid} -> {$rec->brid}/ #userId = '{$rec->userId}'", $rec->id);
                }
            }
        }
        
        $this->requireRightFor('viewexternal', $rec);
        cms_Domains::setPublicDomain($rec->domainId);
        Mode::set('currentExternalTab', 'eshop_Carts');
        
        $tpl = self::renderView($rec);
        
        // Редирект ако количката се е ъпдейтнала
        if($rec->_updatedPrice === true){
            
            return new Redirect(array($this, 'view', $rec->id));
        }
        
        $tpl->prepend("<div id = 'cart-view-single'>");
        $tpl->append('</div>');
        Mode::set('wrapper', 'cms_page_External');
        $tpl->prepend("\n<meta name=\"robots\" content=\"nofollow\">", 'HEAD');
        
        vislog_History::add('Разглеждане на количка');
        
        return $tpl;
    }
    
    
    /**
     * Рендира информация за поръчката
     *
     * @param mixed   $rec
     * @param core_ET $tpl
     *
     * @return void
     */
    private static function renderCartOrderInfo($rec, core_ET &$tpl)
    {
        $rec = self::fetchRec($rec);
        
        $row = self::recToVerbal($rec, cls::get('eshop_Carts')->selectFields());
        $tpl->replace($row->termId, 'termId');
        $tpl->replace($row->paymentId, 'paymentId');
        
        $countryVerbal = core_Type::getByName('key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg)')->toVerbal($rec->deliveryCountry);
        $tpl->replace($countryVerbal, 'deliveryCountry');
        
        foreach (array('deliveryPCode', 'deliveryPlace', 'deliveryAddress') as $name) {
            $tpl->replace(core_Type::getByName('varchar')->toVerbal($rec->{$name}), $name);
        }
        
        if ($companyFolderId = core_Mode::get('lastActiveContragentFolder')) {
            if (colab_Threads::haveRightFor('list', (object) array('folderId' => $companyFolderId))) {
                $folderTitle = doc_Folders::getVerbal($companyFolderId, 'title');
                $activeFolderId = ht::createLink($folderTitle, array('colab_Threads', 'list', 'folderId' => $companyFolderId), false, 'ef_icon=img/16/folder-icon.png');
                $tpl->append($activeFolderId, 'activeFolderId');
            }
        }
        
        if (self::haveRightFor('checkout', $rec)) {
            $editSaleBtn = ht::createLink('', array('eshop_Carts', 'order', $rec->id, 'ret_url' => true), false, 'ef_icon=img/16/edit.png,title=Редактиране на данните на поръчката');
            $tpl->append($editSaleBtn, 'saleEditBtn');
        }
        
        if ($rec->makeInvoice != 'none') {
            $countryVerbal = core_Type::getByName('key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg)')->toVerbal($rec->invoiceCountry);
            $tpl->replace($countryVerbal, 'invoiceCountry');
            foreach (array('invoiceNames', 'invoiceVatNo', 'invoiceUicNo', 'invoicePCode', 'invoicePlace', 'invoiceAddress') as $name) {
                $tpl->replace(core_Type::getByName('varchar')->toVerbal($rec->{$name}), $name);
            }
            
            $nameCaption = ($rec->makeInvoice == 'person') ? 'Лице' : 'Фирма';
            $tpl->replace(tr($nameCaption), 'INV_CAPTION');
            if (!empty($rec->invoiceUicNo)) {
                $vatCaption = ($rec->makeInvoice == 'person') ? 'ЕГН' : 'ЕИК №';
                $tpl->replace(tr($vatCaption), 'VAT_CAPTION');
            }
        } else {
            $tpl->replace(tr('Без фактура'), 'NO_INVOICE');
        }
        
        // Ако няма потребител и има клиентски карти, ще се показва бутон за въвеждане на карта
        if (crm_ext_Cards::haveRightFor('checkcard', (object) array('domainId' => $rec->domainId))) {
            $tpl->replace(ht::createLink(tr('тук'), array('crm_ext_Cards', 'CheckCard', 'ret_url' => true), false, 'ef_icon=img/16/client-card.png '), 'CARD_LINK');
        }
        
        if ($rec->deliveryNoVat < 0) {
            $tpl->replace(tr('Има проблем при изчислението на доставката. Моля, обърнете се към нас|*!'), 'deliveryError');
        }
        
        if (!empty($rec->instruction)) {
            $tpl->replace($row->instruction, 'instruction');
        }
        
        if (isset($rec->deliveryTime)) {
            $deliveryTime = dt::mysql2verbal(dt::addSecs($rec->deliveryTime, null, false), 'd.m.Y');
            $tpl->replace($deliveryTime, 'deliveryTime');
        }
        
        if (eshop_Carts::haveRightFor('checkout', $rec) && $rec->personNames) {
            $editBtn = ht::createLink('', array('eshop_Carts', 'order', $rec->id, 'ret_url' => true), false, 'ef_icon=img/16/edit.png,title=Редактиране на данните за поръчка');
            $tpl->append($editBtn, 'editBtn');
        }
        
        if (!empty($rec->personNames)) {
            $tpl->append('borderTop', 'BORDER_CLASS');
        }
        
        if ($Driver = cond_DeliveryTerms::getTransportCalculator($rec->termId)) {
            $deliveryDataArr = $Driver->getVerbalDeliveryData($rec->termId, $rec->deliveryData, get_called_class());
            foreach ($deliveryDataArr as $delObj){
                $block = $tpl->getBlock('DELIVERY_DATA_VALUE');
                $block->append($delObj->caption, 'DELIVERY_DATA_CAPTION');
                $block->append($delObj->value, 'DELIVERY_DATA_VALUE');
                $block->removeBlocksAndPlaces();
                $tpl->append($block, 'DELIVERY_BLOCK');
            }
        }
        
        return $tpl;
    }
    
    
    /**
     * Рендира съмарито на поръчката
     *
     * @param mixed   $rec
     * @param core_ET $tpl
     *
     * @return void
     */
    private static function renderCartSummary($id, core_ET $tpl)
    {
        $Double = core_Type::getByName('double(decimals=2)');
        $rec = self::fetchRec($id, '*', false);
        if (empty($rec->productCount) && empty($rec->personNames)) {
            
            return;
        }
        $fields = cls::get('eshop_Carts')->selectFields();
        $fields['-external'] = true;
        
        $row = self::recToVerbal($rec, $fields);
        $settings = cms_Domains::getSettings();
        
        $total = currency_CurrencyRates::convertAmount($rec->total, null, null, $settings->currencyId);
        $totalNoVat = currency_CurrencyRates::convertAmount($rec->totalNoVat, null, null, $settings->currencyId);
        $deliveryNoVat = ($rec->freeDelivery != 'no') ? 0 : currency_CurrencyRates::convertAmount($rec->deliveryNoVat, null, null, $settings->currencyId);
        $vatAmount = $total - $totalNoVat - $deliveryNoVat;
        
        $amountWithoutDelivery = ($settings->chargeVat == 'yes') ? $total : $totalNoVat;
        $row->total = $Double->toVerbal($total);
        $row->total = currency_Currencies::decorate($row->total, $settings->currencyId);
        
        // Ако има доставка се показва и нея
        if (isset($rec->deliveryNoVat) && $rec->deliveryNoVat >= 0) {
            $row->deliveryCaption = tr('Доставка||Shipping');
            $row->deliveryCurrencyId = $row->currencyId;
            
            if ($rec->freeDelivery != 'no') {
                $row->deliveryAmount = "<span style='text-transform: uppercase;color:green';>" . tr('Безплатна') . '</span>';
                unset($row->deliveryCurrencyId);
                $row->deliveryColspan = 'colspan=2';
            } else {
                if ($settings->chargeVat == 'yes') {
                    $transportId = cat_Products::fetchField("#code = 'transport'", 'id');
                    $transportVat = cat_Products::getVat($transportId);
                    
                    $deliveryAmount = $rec->deliveryNoVat * (1 + $transportVat);
                    $amountWithoutDelivery -= $deliveryNoVat * (1 + $transportVat);
                } else {
                    $deliveryAmount = $rec->deliveryNoVat;
                }
                
                $deliveryAmount = currency_CurrencyRates::convertAmount($deliveryAmount, null, null, $settings->currencyId);
                $totalNoVat -= $deliveryAmount;
                
                $deliveryAmountV = core_Type::getByName('double(decimals=2)')->toVerbal($deliveryAmount);
                $deliveryAmountV = currency_Currencies::decorate($deliveryAmountV, $settings->currencyId);
                $row->deliveryAmount = $deliveryAmountV;
            }
        }
        
        $row->amount = $Double->toVerbal($amountWithoutDelivery);
        $row->amount = currency_Currencies::decorate($row->amount, $settings->currencyId);
        $row->amountCurrencyId = $row->currencyId;
        
        if ($settings->chargeVat != 'yes') {
            $row->totalVat = $Double->toVerbal($vatAmount);
        }
        
        $row->productCount .= '&nbsp;' . (($rec->productCount == 1) ? tr('артикул') : tr('артикула'));
        unset($row->invoiceVatNo);
        $tpl->placeObject($row);
        
        if (isset($rec->paymentId)) {
            cond_PaymentMethods::addToCartView($rec->paymentId, $rec, $row, $tpl);
        }
        
        if (isset($rec->termId)) {
            cond_DeliveryTerms::addToCartView($rec->termId, $rec, $row, $tpl);
        }
        
        return $tpl;
    }
    
    
    /**
     * Рендиране на тулбара към кошницата
     *
     * @param mixed   $id  - ид или запис
     * @param core_ET $tpl - шаблон
     *
     * @return void
     */
    private static function renderCartToolbar($id, core_ET &$tpl)
    {
        $rec = self::fetchRec($id);
        $shopUrl = cls::get('eshop_Groups')->getUrlByMenuId(null);
        
        $btn = ht::createLink(tr('Магазин'), $shopUrl, null, 'title=Назад към магазина,class=eshop-link,ef_icon=img/16/cart_go_back.png,rel=nofollow');
        $tpl->append($btn, 'CART_TOOLBAR_TOP');
        $wideSpan = '<span>|</span>';
        
        if (eshop_CartDetails::haveRightFor('add', (object) array('cartId' => $rec->id))) {
            $addUrl = array('eshop_CartDetails', 'add', 'cartId' => $rec->id, 'external' => true, 'ret_url' => true);
            $btn = ht::createLink(tr('Добавяне'), $addUrl, null, 'title=Добавяне на нов артикул,class=eshop-link,ef_icon=img/16/add1-16.png,rel=nofollow');
            $tpl->append($wideSpan . $btn, 'CART_TOOLBAR_TOP');
        }
        
        if (!empty($rec->productCount) && eshop_CartDetails::haveRightFor('removeexternal', (object) array('cartId' => $rec->id))) {
            $emptyUrl = array('eshop_CartDetails', 'removeexternal', 'cartId' => $rec->id, 'ret_url' => $shopUrl);
            $btn = ht::createLink(tr('Изчистване'), $emptyUrl, 'Сигурни ли сте, че искате да изтриете артикулите?', 'title=Премахване на всички артикули,class=eshop-link,ef_icon=img/16/deletered.png,rel=nofollow');
            $tpl->append($wideSpan . $btn, 'CART_TOOLBAR_TOP');
        }
        
        $checkoutUrl = (eshop_Carts::haveRightFor('checkout', $rec)) ? array('eshop_Carts', 'order', $rec->id, 'ret_url' => true) : array();
        if (empty($rec->personNames) && countR($checkoutUrl)) {
            $btn = ht::createBtn(tr('Направете поръчка') . ' »', $checkoutUrl, null, null, 'title=Поръчване на артикулите,class=order-btn eshop-btn,rel=nofollow');
            $tpl->append($btn, 'CART_TOOLBAR_RIGHT');
        }
        
        // Ако се изисква онлайн плащане добавя се бутон към него
        if (isset($rec->paymentId)) {
            
            // Ако самия метод на плащане, добавяв текст показва се
            $paymentRec = cond_PaymentMethods::fetch($rec->paymentId);
            if (!empty($paymentRec->onlinePaymentText)) {
                $onlinePaymentText = core_Type::getByName('text')->toVerbal($paymentRec->onlinePaymentText);
                $tpl->append($onlinePaymentText . '<br>', 'PAYMENT_TEXT_RIGHT');
            }
            
            if ($PaymentDriver = cond_PaymentMethods::getOnlinePaymentDriver($rec->paymentId)) {
                
                // Ако драйвера на метода на плащане добавя текст, показва се и той
                $paymentDriverText = $PaymentDriver->getDisplayHtml($paymentRec);
                if (!empty($paymentDriverText)) {
                    $tpl->append($paymentDriverText, 'PAYMENT_TEXT_RIGHT');
                }
                
                // Ако има поне един артикул, показва се бутона за онлайн плащане
                if (!empty($rec->productCount)) {
                    $cancelUrl = array('eshop_Carts', 'abort', $rec->id);
                    $okUrl = array('eshop_Carts', 'confirm', $rec->id);
                    $settings = cms_Domains::getSettings();
                    $btn = $PaymentDriver->getPaymentBtn($rec->paymentId, $rec->total, $settings->currencyId, $okUrl, $cancelUrl, 'eshop_Carts', $rec->id);
                    $tpl->append($btn, 'CART_TOOLBAR_RIGHT');
                }
            }
        }
        
        if (eshop_Carts::haveRightFor('finalize', $rec)) {
            $btn = ht::createBtn('Завършване', array('eshop_Carts', 'finalize', $rec->id), 'Сигурни ли сте, че искате да направите поръчката|*!', null, 'title=Завършване на поръчката,class=order-btn eshop-btn,rel=nofollow');
            
            $tpl->append($btn, 'CART_TOOLBAR_RIGHT');
            if ($rec->productCount > 3) {
                $tpl->append($btn, 'CART_TOOLBAR_TOP_RIGHT');
            }
        }
    }
    
    
    /**
     * Рендиране на изгледа на кошницата във външната част
     *
     * @param mixed $rec
     *
     * @return core_ET $tpl - шаблон на съмарито
     */
    private static function renderViewCart($rec)
    {
        $rec = self::fetchRec($rec);
        
        $tpl = new core_ET('');
        $fields = cls::get('eshop_Carts')->selectFields();
        $fields['-external'] = true;
        
        $row = self::recToVerbal($rec, $fields);
        $data = (object) array('rec' => &$rec, 'row' => $row);
        self::prepareExternalCart($data);
        $tpl = self::renderExternalCart($data);
        
        return $tpl;
    }
    
    
    /**
     * Подготовка на данните на кошницата
     *
     * @param stdClass $data
     *
     * @return core_ET $tpl  - шаблон на съмарито
     */
    private static function prepareExternalCart($data)
    {
        $fields = cls::get('eshop_CartDetails')->selectFields();
        $fields['-external'] = true;
        $data->listFields = arr::make('code=Код,productId=Артикул,quantity=Количество,finalPrice=Цена,amount=Сума');
        $settings = cms_Domains::getSettings();
        
        $data->productRecs = $data->productRows = array();
        $dQuery = eshop_CartDetails::getQuery();
        $dQuery->where("#cartId = {$data->rec->id}");
        $dQuery->orderBy('id', 'ASC');
        
        while ($dRec = $dQuery->fetch()) {
            $data->recs[$dRec->id] = $dRec;
            $row = eshop_CartDetails::recToVerbal($dRec, $fields);
            if($dRec->_updatedPrice === true){
                $data->rec->_updatedPrice = true;
            }
            if (!empty($dRec->discount)) {
                $discountType = type_Set::toArray($settings->discountType);
                $row->finalPrice = "<span class='end-price'>{$row->finalPrice}</span>";
                $row->finalPrice .= "<div class='external-discount'>";
                
                if (isset($discountType['amount'])) {
                    $amountWithoutDiscount = $dRec->finalPrice / (1 - $dRec->discount);
                    $discountAmount = core_Type::getByName('double(decimals=2)')->toVerbal($amountWithoutDiscount);
                    $discountAmount = currency_Currencies::decorate($discountAmount, $settings->currencyId);
                    $row->finalPrice .= "<div class='external-discount-amount'> {$discountAmount}</div>";
                }
                
                if (isset($discountType['amount'], $discountType['percent'])) {
                    $row->finalPrice .= ' / ';
                }
                
                if (isset($discountType['percent'])) {
                    $discountPercent = core_Type::getByName('percent(decimals=2)')->toVerbal($dRec->discount);
                    $discountPercent = str_replace('&nbsp;', '', $discountPercent);
                    $row->finalPrice .= "<div class='external-discount-percent'> -{$discountPercent}</div>";
                }
                
                $row->finalPrice .= '</div>';
            }
            
            $fullCode = cat_products::getVerbal($dRec->productId, 'code');
            $row->code = substr($fullCode, 0, 10);
            $row->code = "<span title={$fullCode}>{$row->code}</span>";
            $row->amount = "<span class='end-price'>{$row->amount}</span>";
            
            $data->rows[$dRec->id] = $row;
        }
    }
    
    
    /**
     * Рендиране на данните на кошницата
     *
     * @param stdClass $data
     *
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
        
        if (Mode::is('screenMode', 'narrow')) {
            $data->listTableMvc->commonRowClass = 'ecartCommonRow';
            $data->listTableMvc->tableRowTpl = "<tbody>[#ADD_ROWS#][#ROW#]</tbody>\n";
            $data->listFields['productId'] = '@Артикул';
        }
        
        plg_RowTools2::on_BeforeRenderListTable($data->listTableMvc, $tpl, $data);
        $tpl->replace($table->get($data->rows, $data->listFields));
        
        return $tpl;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'viewexternal' && isset($rec)) {
            if ($rec->state != 'draft') {
                $requiredRoles = 'no_one';
            } elseif (isset($userId) && $rec->userId != $userId) {
                $requiredRoles = 'no_one';
            } elseif (!isset($userId)) {
                $brid = log_Browsers::getBrid();
                if (!(empty($rec->userId) && $rec->brid == $brid)) {
                    $requiredRoles = 'no_one';
                }
            }
            
            if ($requiredRoles != 'no_one') {
                $settings = cms_Domains::getSettings();
                if (empty($settings)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        if (in_array($action, array('addtocart', 'checkout', 'finalize')) && isset($rec)) {
            $singleRoles = $mvc->getRequiredRoles('single', $rec, $userId);
            if(!haveRole($singleRoles, $userId)){
                $requiredRoles = $mvc->getRequiredRoles('viewexternal', $rec, $userId);
            }
        }
        
        if ($action == 'checkout' && isset($rec)) {
            if (empty($rec->productCount) && empty($rec->personNames)) {
                $requiredRoles = 'no_one';
            }
        }
        
        if ($action == 'finalize' && isset($rec)) {
            if($rec->state != 'draft'){
                $requiredRoles = 'no_one';
            } elseif (empty($rec->personNames) || empty($rec->productCount)) {
                $requiredRoles = 'no_one';
            } elseif ($rec->deliveryNoVat < 0) {
                $requiredRoles = 'no_one';
            } elseif ($rec->paidOnline != 'yes') {
                if ($PaymentDriver = cond_PaymentMethods::getOnlinePaymentDriver($rec->paymentId)) {
                    if ($PaymentDriver->isPaymentMandatory($rec->paymentId, $mvc, $rec->id)) {
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }
        
        if ($action == 'delete' && isset($rec)) {
            if ($rec->state != 'draft') {
                $requiredRoles = 'no_one';
            } else {
                $compareDate = dt::addSecs($rec->createdOn, 60 * 60 * 24 * 2);
                if ($compareDate >= dt::now()) {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        if ($action == 'makenewsale' && isset($rec)) {
            if ($rec->state != 'active') {
                $requiredRoles = 'no_one';
            } elseif (isset($rec->saleId)) {
                if (sales_Sales::fetchField($rec->saleId, 'state') == 'active') {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        // Ако няма начин за плащане, и няма драйвер за онлайн плащане, екшъна за отказано плащане е недостъпен
        if (in_array($action, array('abortpayment', 'confirmpayment')) && isset($rec)) {
            if ($rec->state != 'draft' || !isset($rec->paymentId) || !cond_PaymentMethods::getOnlinePaymentDriver($rec->paymentId) || empty($rec->productCount)) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if ($rec->userId){
            $row->userId = core_Users::getNick($rec->userId)."</br>";
        }
        
        $settings = cms_Domains::getSettings($rec->domainId);
        $row->userId .=type_Ip::decorateIp($rec->ip, $rec->createdOn)."</br>" .log_Browsers::getLink($rec->brid);
        $row->ROW_ATTR['class'] = "state-{$rec->state}";
        $row->STATE_CLASS = $row->ROW_ATTR['class'];
        $row->domainId = cms_Domains::getHyperlink($rec->domainId);
        
        if ($fields['-single']) {
            if ($rec->state == 'draft') {
                $delitionTime = self::getDeletionTime($rec);
                $row->delitionTime = core_Type::getByName('datetime(format=smartTime)')->toVerbal($delitionTime);
                
                // Кога ще се изпраща имейл за нотифициране
                if (!empty($rec->email)) {
                    $timeToNotifyBeforeDeletion = dt::addSecs(-1 * $settings->timeBeforeDelete, $delitionTime);
                    $row->timeToNotifyBeforeDeletion = core_Type::getByName('datetime(format=smartTime)')->toVerbal($timeToNotifyBeforeDeletion);
                    $isNotified = core_Permanent::get("eshopCartsNotify{$rec->id}");
                    
                    $row->isNotified = ($isNotified === 'y') ? tr('Имейлът е изпратен') : tr('Имейлът не е изпратен');
                }
            }
        }
        
        $currencyCode = $settings->currencyId;
        $rec->vatAmount = $rec->total - $rec->totalNoVat;
        
        if ($rec->freeDelivery != 'yes') {
            $rec->totalNoVat = $rec->totalNoVat - $rec->deliveryNoVat;
        }
        
        foreach (array('total', 'totalNoVat', 'deliveryNoVat', 'vatAmount') as $fld) {
            if (isset($rec->{$fld})) {
                ${$fld} = currency_CurrencyRates::convertAmount($rec->{$fld}, null, null, $currencyCode);
                $row->{$fld} = $mvc->getFieldType('total')->toVerbal(${$fld}) . " <span class='cCode'>{$currencyCode}</span>";
            }
        }
        
        if ($rec->freeDelivery == 'yes') {
            $row->deliveryNoVat = "<span style='text-transform: uppercase;color:green;font-weight:bold';>" . tr('Безплатна') . '</span>';
        }
        
        if (isset($fields['-list'])) {
            if (!empty($rec->email) && $rec->state == 'draft') {
                $row->productCount = ht::createHint($row->productCount, 'Има попълнени данни за поръчка|*!', 'notice', false);
            }
        }
        
        if (isset($rec->saleId)) {
            $saleState = sales_Sales::fetchField($rec->saleId, 'state');
            $row->saleId = sales_Sales::getHyperlink($rec->saleId, true);
            $row->saleId = "<span class='state-{$saleState} document-handler'>{$row->saleId}</span>";
        }
        
        if (isset($rec->termId) && !isset($fields['-external'])) {
            $row->termId = cond_DeliveryTerms::getHyperlink($rec->termId, true);
        }
        
        if (isset($rec->paymentId) && !isset($fields['-external'])) {
            $row->paymentId = cond_PaymentMethods::getHyperlink($rec->paymentId, true);
        }
        
        if(isset($fields['-external'])){
            
            // Показване на текст за очаквана доставка
            if($expectedDeliveryText = self::getExpectedDeliveryText($rec, $settings)){
                $row->EXPECTED_DELIVERY = $expectedDeliveryText;
            }
        }
    }
    
    
    /**
     * Връща текста на очакваната доставка
     */
    private static function getExpectedDeliveryText($rec, $settings)
    {
        if($rec->haveProductsWithExpectedDelivery == 'yes'){
            $expectedDeliveryText = new core_ET($settings->expectedDeliveryText);
            $cartName = self::getCartDisplayName();
            $expectedDeliveryText->replace(mb_strtolower($cartName), 'cartName');
            
            return $expectedDeliveryText;
        }
        
        return null;
    }
        
    
    /**
     * Екшън за показване на външния изглед на кошницата
     */
    public function act_Order()
    {
        Mode::set('currentExternalTab', 'eshop_Carts');
        
        $lang = cms_Domains::getPublicDomain('lang');
        core_Lg::push($lang);
        
        $this->requireRightFor('checkout');
        expect($id = Request::get('id', 'int'));
        expect($rec = self::fetch($id));
        $this->requireRightFor('checkout', $rec);
        vislog_History::add('Въвеждане на данни за количка');
        
        $settings = cms_Domains::getSettings();
        $countries = keylist::toArray($settings->countries);
        
        $data = new stdClass();
        $data->action = 'order';
        $this->prepareEditForm($data);
        $data->form->setAction($this, 'order');
        
        $form = &$data->form;
        $form->title = 'Данни за поръчка';
        $form->countries = $countries;
        cms_Domains::addMandatoryText2Form($form);
        
        self::prepareOrderForm($form);
        
        // Добавяне на линк за логване, ако от преди се е логвам потребителя
        cms_Helper::setLoginInfoIfNeeded($form);
        
        $form->input(null, 'silent');
        
        if(empty($form->rec->termId)){
            $form->setField('deliveryCountry', 'input=hidden');
            $form->setField('deliveryPCode', 'input=hidden');
            $form->setField('deliveryPlace', 'input=hidden');
            $form->setField('deliveryAddress', 'input=hidden');
        }
        
        $cu = core_Users::getCurrent('id', false);
        if (isset($cu) && $form->rec->makeInvoice != 'none') {
            $profileRec = crm_Profiles::getProfile($cu);
            if (isset($form->rec->saleFolderId)) {
                $form->rec->makeInvoice = ($form->rec->saleFolderId == $profileRec->folderId) ? 'person' : 'company';
            }
        }
        
        self::setDefaultsFromFolder($form, $form->rec->saleFolderId);
        
        $form->setOptions('country', drdata_Countries::getOptionsArr($form->countries));
        if (countR($form->countries) == 1) {
            $form->setDefault('country', key($form->countries));
            $form->setField('country', 'input=hidden');
        } else {
            $form->setDefault('country', cls::get('drdata_Countries')->getByIp());
        }
        
        if (!empty($form->rec->deliveryCountry)) {
            $form->countries[$form->rec->deliveryCountry] = $form->rec->deliveryCountry;
        }
        
        $isDeliveryCountryReadOnly = $form->getFieldTypeParam('deliveryCountry', 'isReadOnly');
        if ($isDeliveryCountryReadOnly !== true) {
            $form->setOptions('deliveryCountry', drdata_Countries::getOptionsArr($form->countries));
        }
        
        if (countR($form->countries) == 1) {
            $form->setDefault('deliveryCountry', key($form->countries));
            $form->setReadOnly('deliveryCountry');
        }
        
        // Ако има условие на доставка то драйвера му може да добави допълнителни полета
        if (isset($form->rec->termId)) {
            
            // Държавата за доставка да е тази от ип-то по дефолт
            if ($countryCode2 = drdata_IpToCountry::get()) {
                $form->setDefault('deliveryCountry', drdata_Countries::fetchField("#letterCode2 = '{$countryCode2}'", 'id'));
            }
            
            cond_DeliveryTerms::prepareDocumentForm($form->rec->termId, $form, $this);
        }
        
        $invoiceFields = $form->selectFields('#invoiceData');
        if (isset($form->rec->makeInvoice) && $form->rec->makeInvoice != 'none') {
            
            // Ако има ф-ра полетата за ф-ра се показват
            foreach ($invoiceFields as $name => $fld) {
                $form->setField($name, 'input');
            }
            
            if ($form->rec->makeInvoice == 'person') {
                $form->setField('invoiceNames', 'caption=Данни за фактуриране->Име');
                $form->setField('invoiceUicNo', 'caption=Данни за фактуриране->ЕГН');
                $form->setFieldType('invoiceUicNo', 'bglocal_EgnType');
                $form->setDefault('invoiceNames', $form->rec->personNames);
            } else {
                $form->setField('invoiceNames', 'caption=Данни за фактуриране->Фирма');
                $form->setField('invoiceVatNo', 'caption=Данни за фактуриране->ДДС №||VAT ID');
            }
            
            $form->setFieldAttr('deliveryCountry', 'data-updateonchange=invoiceCountry,class=updateselectonchange');
            $form->setFieldAttr('deliveryPCode', 'data-updateonchange=invoicePCode,class=updateonchange');
            $form->setFieldAttr('deliveryPlace', 'data-updateonchange=invoicePlace,class=updateonchange');
            $form->setFieldAttr('deliveryAddress', 'data-updateonchange=invoiceAddress,class=updateonchange');
            
            if (!empty($form->rec->invoiceCountry)) {
                $form->countries[$form->rec->invoiceCountry] = $form->rec->invoiceCountry;
            }
            $form->setOptions('invoiceCountry', drdata_Countries::getOptionsArr($form->countries));
            if (countR($form->countries) == 1) {
                $form->setDefault('invoiceCountry', key($form->countries));
                $form->setReadOnly('invoiceCountry');
            }
        } else {
            foreach ($invoiceFields as $name => $fld) {
                $form->setField($name, 'input=none');
            }
        }
        
        $form->input();
        
        if ($rec->makeInvoice != 'none') {
            $form->setDefault('invoiceCountry', $rec->deliveryCountry);
            $form->setDefault('invoicePCode', $rec->deliveryPCode);
            $form->setDefault('invoicePlace', $rec->deliveryPlace);
            $form->setDefault('invoiceAddress', $rec->deliveryAddress);
        }
        
        if ($form->isSubmitted()) {
            $rec = $form->rec;
            
            // Проверка на имената да са поне две с поне 2 букви
            if (!core_Users::checkNames($rec->personNames)) {
                $form->setError('personNames', 'Невалидни имена');
            }
            
            // Проверка на имената на лицето на фактурата, ако тя е за лице да са поне две с поне 2 букви
            if ($rec->makeInvoice == 'person') {
                if (!core_Users::checkNames($rec->invoiceNames)) {
                    $form->setError('invoiceNames', 'Невалидни имена');
                }
            }
            
            if ($rec->makeInvoice != 'none' && empty($rec->invoiceVatNo) && empty($rec->invoiceUicNo)) {
                $form->setError('invoiceVatNo,invoiceUicNo', 'Поне едно от полетата трябва да бъде въведено');
            }
            
            if (!empty($rec->invoiceNames) && $rec->makeInvoice != 'none') {
                if (!preg_match('/[a-zа-я0-9]+.*[a-zа-я0-9]+.*[a-zа-я0-9]+/iu', $rec->invoiceNames)) {
                    $form->setError('invoiceNames', 'Неправилен формат');
                }
            }
            
            // Ако има регистриран потребител с този имейл. Изисква се да се логне
            if ($error = cms_Helper::getErrorIfThereIsUserWithEmail($rec->email)) {
                $form->setError('email', $error);
            }
            
            $arr = array('invoiceCountry' => 'deliveryCountry', 'invoicePCode' => 'deliveryPCode', 'invoicePlace' => 'deliveryPlace', 'invoiceAddress' => 'deliveryAddress');
            $emptyFields = array();
            
            if ($rec->makeInvoice != 'none') {
                foreach ($arr as $invField => $delField) {
                    $rec->{$invField} = !empty($rec->{$invField}) ? $rec->{$invField} : $rec->{$delField};
                    if (empty($rec->{$invField})) {
                        $emptyFields[] = $invField;
                    }
                }
            } else {
                $rec->invoiceCountry = $rec->invoicePCode = $rec->invoicePlace = $rec->invoiceAddress = $rec->invoiceUicNo = $rec->invoiceNames = $rec->invoiceVatNo = null;
            }
            
            if (countR($emptyFields)) {
                $form->setError($emptyFields, 'Липсват данните за фактуриране');
            }
            
            if (!$form->gotErrors()) {
                
                if(isset($rec->termId)){
                    cond_DeliveryTerms::inputDocumentForm($rec->termId, $form, $this);
                }
                
                // Ако има избрана папка обновява се
                if (!empty($rec->saleFolderId)) {
                    $Cover = doc_Folders::getCover($rec->saleFolderId);
                    $Cover->getInstance()->updateContactDataByFolderId($rec->saleFolderId, $rec->invoiceNames, $rec->invoiceVatNo, $rec->invoiceUicNo, $rec->invoiceCountry, $rec->invoicePCode, $rec->invoicePlace, $rec->invoiceAddress);
                }
                
                $cu = core_Users::getCurrent('id', false);
                if (!$cu) {
                    $userData = array('email' => $rec->email, 'personNames' => $rec->personNames, 'tel' => $rec->tel);
                    log_Browsers::setVars($userData);
                }
                
                $this->save($rec);
                $this->updateMaster($rec);
                core_Lg::pop();
                eshop_Carts::logWrite("Попълване на данни за поръчката от външната част", $rec->id);
                
                return followRetUrl();
            }
        }
        
        Mode::set('wrapper', 'cms_page_External');
        
        // Добавяне на бутони
        $form->toolbar->addSbBtn('Продължи', 'save', 'ef_icon = img/16/disk.png, title = Запис на данните за поръчката');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        if ($form->cmd == 'refresh') {
            $form->renderLayout();
            
            if ($form->layout) {
                jquery_Jquery::run($form->layout, 'copyValToPlaceholder();');
            }
        }
        
        $tpl = $form->renderHtml();
        core_Form::preventDoubleSubmission($tpl, $form);
        core_Lg::pop();
        
        // Рефрешване на формата ако потребителя се логне докато е в нея
        cms_Helper::setRefreshFormIfNeeded($tpl);
        jquery_Jquery::run($tpl, 'runOnLoad(copyValToPlaceholder);');
        $tpl->prepend("\n<meta name=\"robots\" content=\"nofollow\">", 'HEAD');
        
        return $tpl;
    }
    
    
    /**
     * Подготвя формата за поръчка
     *
     * @param core_Form $form
     *
     * @return void
     */
    private static function prepareOrderForm(&$form)
    {
        $cu = core_Users::getCurrent('id', false);
        $defaultTermId = $defaultPaymentId = null;
        $settings = cms_Domains::getSettings();
        
        $deliveryTerms = eshop_Settings::getDeliveryTermOptions('cms_Domains', cms_Domains::getPublicDomain()->id);
        $paymentMethods = eshop_Settings::getPaymentMethodOptions('cms_Domains', cms_Domains::getPublicDomain()->id);
        
        if ($cu) {
            $options = array();
            if (core_Packs::isInstalled('colab')) {
                $options = colab_Folders::getSharedFolders($cu, true, 'crm_ContragentAccRegIntf', false);
            }
            
            $profileRec = crm_Profiles::getProfile($cu);
            $email = !empty($profileRec->email) ? $profileRec->email : core_Users::fetchField($cu, 'email');
            
            $form->setDefault('personNames', $profileRec->name);
            $emails = type_Emails::toArray($email);
            $form->setDefault('email', $emails[0]);
            $form->setDefault('tel', $profileRec->tel);
            
            // Задаване като опции
            if (countR($options)) {
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
            
            if ($defaultTermId && !array_key_exists($defaultTermId, $deliveryTerms)) {
                $deliveryTerms[$defaultTermId] = cond_DeliveryTerms::getVerbal($defaultTermId, 'codeName');
            }
            
            // Ако локацията е задължителна, остават само условията с локация на получателя
            if($settings->locationIsMandatory == 'yes'){
                $receiverTerms = cond_DeliveryTerms::getTermOptions('receiver');
                $deliveryTerms = array_intersect_key($deliveryTerms, $receiverTerms);
            }
            
            if(array_key_exists($defaultTermId, $deliveryTerms)){
                $form->setDefault('termId', $defaultTermId);
            }
            
            // Добавяне на партньорския метод за плащане
            $defaultPaymentId = cond_Parameters::getParameter('crm_Persons', $profileRec->id, 'paymentMethodSale');
            $form->setDefault('paymentId', $defaultPaymentId);
            if ($defaultPaymentId && !array_key_exists($defaultPaymentId, $paymentMethods)) {
                $paymentMethods[$defaultPaymentId] = tr(cond_PaymentMethods::getVerbal($defaultPaymentId, 'name'));
            }
        }
        
        $originalRec = static::fetch($form->rec->id, '*', false);
        if (countR($deliveryTerms) == 1) {
            $form->setDefault('termId', key($deliveryTerms));
        } elseif(empty($originalRec->personNames)) {
            $deliveryTerms = array('' => '') + $deliveryTerms;
        }
        $form->setOptions('termId', $deliveryTerms);
        
        if (countR($paymentMethods) == 1) {
            $form->setDefault('paymentId', key($paymentMethods));
        } else {
            $paymentMethods = array('' => '') + $paymentMethods;
        }
        $form->setOptions('paymentId', $paymentMethods);
        
        $makeInvoice = eshop_Setup::get('MANDATORY_CONTACT_FIELDS');
        if (in_array($makeInvoice, array('company', 'both'))) {
            $form->setDefault('makeInvoice', 'company');
            $form->setField('makeInvoice', 'input=hidden');
        }
    }
    
    
    /**
     * Дефолти от избраната папка
     *
     * @param core_Form $form
     * @param int|NULL  $folderId
     */
    private static function setDefaultsFromFolder(&$form, $folderId)
    {
        $rec = &$form->rec;
        $cu = core_Users::getCurrent('id', false);
        $isColab = isset($cu) && core_Users::isContractor($cu);
        $settings = cms_Domains::getSettings();
        
        $onlyLocationsWithRoutes = null;
        if($isColab && isset($form->rec->termId)){
            if($Calculator = cond_DeliveryTerms::getTransportCalculator($form->rec->termId)){
                if($Calculator->class instanceof sales_interface_FreeRegularDelivery){
                    $onlyLocationsWithRoutes = 7;
                }
            }
        }
        
        // Ако има избрана папка се записват контрагент данните
        if (isset($folderId)) {
            if ($contragentData = doc_Folders::getContragentData($folderId)) {
                $invName = ($rec->makeInvoice == 'person') ? $contragentData->person : $contragentData->company;
                $form->rec->invoiceNames = $invName;
                $form->rec->invoiceVatNo = $contragentData->vatNo;
                $form->rec->invoiceUicNo = $contragentData->uicId;
                $form->rec->invoiceCountry = $contragentData->countryId;
                $form->rec->invoicePCode = $contragentData->pCode;
                $form->rec->invoicePlace = $contragentData->place;
                $form->rec->invoiceAddress = $contragentData->address;
                
                $form->countries[$contragentData->countryId] = $contragentData->countryId;
                $contragentCover = doc_Folders::getCover($folderId);
                $locations = crm_Locations::getContragentOptions($contragentCover->className, $contragentCover->that, true, true, $form->countries, $onlyLocationsWithRoutes);
            }
        } else {
            if ($isColab === true) {
                $locations = crm_Locations::getContragentOptions('crm_Persons', crm_Profiles::getProfile($cu)->id, true, true, $form->countries, $onlyLocationsWithRoutes);
            }
        }
        
        if($isColab){
            $form->setField('locationId', 'input');
            $form->input('locationId', 'silent');
            
            // Ако е партньор и локацията е задължителна
            if($settings->locationIsMandatory == 'yes'){
                $form->setField('locationId', 'input,mandatory');
                if (!countR($locations)) {
                    $infoText = tr('За да продължите трябва да имате регистриран обект за доставка. Моля свържете се с нас');
                    $form->info = new core_ET("<div id='editStatus'><div class='warningMsg'>{$infoText}</div></div>");
                }
            }
            
            if(countR($locations) == 1){
                $form->setDefault('locationId', key($locations));
            } elseif(countR($locations) > 1) {
                if($settings->locationIsMandatory == 'yes'){
                    $form->setDefault('locationId', key($locations));
                } else {
                    $locations = array('' => '') + $locations;
                }
            }
            $form->setOptions('locationId', $locations);
        }
        
        // Ако е избрана локация допълват се адресните данни за доставка
        if (isset($rec->locationId)) {
            $locationRec = crm_Locations::fetch($rec->locationId);
            foreach (array('deliveryCountry' => 'countryId', 'deliveryPCode' => 'pCode', 'deliveryPlace' => 'place', 'deliveryAddress' => 'address') as $delField => $locField) {
                
                // Ако има избрана локация, твърдо подменяме адресните данни
                if (!empty($locationRec->{$locField})) {
                    $form->rec->{$delField} = $locationRec->{$locField};
                }
            }
        }
        
        if (isset($cu)) {
            $cQuery = eshop_Carts::getQuery();
            $cQuery->where("#userId = {$cu} AND #state = 'active' AND #domainId = {$rec->domainId}");
            $cQuery->orderBy('activatedOn', 'DESC');
            $cQuery->limit(1);
            $cQuery2 = clone $cQuery;
            $cQuery3 = clone $cQuery;
            
            // Адреса за доставка е този от последната количка, освен ако локацията не е задължителна
            if($settings->locationIsMandatory != 'yes'){
                $cQuery->in('deliveryCountry', $form->countries);
                if(isset($folderId)){
                    $cQuery->where("#saleFolderId = {$folderId}");
                }
                if ($lastCart = $cQuery->fetch()) {
                    foreach (array('termId', 'deliveryCountry', 'deliveryPCode', 'deliveryPlace', 'deliveryAddress', 'locationId') as $field) {
                        $form->setDefault($field, $lastCart->{$field});
                    }
                }
            }
            
            $cQuery2->in('invoiceCountry', $form->countries);
            if(isset($folderId)){
                $cQuery2->where("#saleFolderId = {$folderId}");
            }
            
            if ($lastCart2 = $cQuery2->fetch()) {
                foreach (array('invoiceNames', 'invoiceVatNo', 'invoiceUicNo', 'invoiceCountry', 'invoicePlace', 'invoiceAddress') as $field) {
                    $form->setDefault($field, $lastCart2->{$field});
                }
            }
            
            if ($lastCart3 = $cQuery3->fetch()) {
                foreach (array('termId', 'paymentId') as $field) {
                    $form->setDefault($field, $lastCart3->{$field});
                }
                $form->rec->tel = $lastCart3->tel;
            }
            
            if (isset($folderId) && $settings->locationIsMandatory != 'yes') {
                if ($contragentData = doc_Folders::getContragentData($folderId)) {
                    $form->setDefault('deliveryCountry', $contragentData->countryId);
                    $form->setDefault('deliveryPCode', $contragentData->pCode);
                    $form->setDefault('deliveryPlace', $contragentData->place);
                    $form->setDefault('deliveryAddress', $contragentData->address);
                }
            }
        }
    }
    
    
    /**
     * Кога количката ще бъде изтрита
     *
     * @param stdClass $rec
     *
     * @return string
     */
    private static function getDeletionTime($rec)
    {
        // Колко е очаквания и 'живот'
        $settings = cms_Domains::getSettings($rec->domainId);
        if (empty($rec->productCount)) {
            $lifetime = $settings->lifetimeForEmptyDraftCarts;
        } else {
            
            // Потребителските колички са тези създадени от потребител или тези с въведен имейл за връзка
            $lifetime = (isset($rec->userId) || !empty($rec->email)) ? $settings->lifetimeForUserDraftCarts : $settings->lifetimeForNoUserDraftCarts;
        }
        
        // Ако и е изтекла продължителността и е чернова се изтрива
        $endOfLife = dt::addSecs($lifetime, $rec->createdOn);
        
        return $endOfLife;
    }
    
    
    /**
     * Изтриване на забравните колички
     */
    public function cron_CheckDraftCarts()
    {
        // Всички чернови колички
        $now = dt::now();
        $query = self::getQuery();
        $query->where("#state = 'draft' OR #state = '' OR #state IS NULL");
        $query->where("#productCount != 0");
        
        // За всяка
        while ($rec = $query->fetch()) {
            $settings = cms_Domains::getSettings($rec->domainId);
            $endOfLife = self::getDeletionTime($rec);
            $timeToNotifyBeforeDeletion = dt::addSecs(-1 * $settings->timeBeforeDelete, $endOfLife);
            
            $isDeleted = false;
            if ($endOfLife <= $now) {
                self::delete($rec->id);
                $isDeleted = true;
            } elseif (!empty($rec->email) && $timeToNotifyBeforeDeletion <= $now) {
                
                // Ако не е изпращан нотифициращ имейл за забравена поръчка, изпраща се
                $isNotified = core_Permanent::get("eshopCartsNotify{$rec->id}");
                if ($isNotified !== 'y') {
                    self::sendNotificationEmail($rec);
                    core_Permanent::set("eshopCartsNotify{$rec->id}", 'y', 10080);
                }
            }
            
            // Ако количката не е изтрита се обновява
            if(!$isDeleted){
                $this->updateMaster($rec);
            }
        }
    }
    
    
    /**
     * Екшън към който се редиректва при отказване на онлайн плащане
     */
    public function act_Abort()
    {
        // Проверка дали наистина е отказано плащане
        expect($id = Request::get('id', 'int'));
        $this->requireRightFor('abortpayment', $id);
        
        // Редирект към количката с подходящо съобщение
        core_Statuses::newStatus('Плащането е отказано|*!', 'error');
        redirect(array($this, 'view', $id));
    }
    
    
    /**
     * Екшън към който се редиректва при отказване на онлайн плащане
     */
    public function act_Confirm()
    {
        Request::setProtected('description,accountId');
        
        // Проверка дали наистина е отказано плащане
        expect($id = Request::get('id', 'int'));
        expect($rec = self::fetch($id));
        expect($description = Request::get('description', 'varchar'));
        $accountId = Request::get('accountId', 'key(mvc=bank_OwnAccounts)');
        $this->requireRightFor('confirmpayment', $rec);
        
        // Маркира се като платена онлайн
        $rec->paidOnline = 'yes';
        self::save($rec, 'paidOnline');
        
        // Финализиране на сделката
        redirect(array($this, 'finalize', $id, 'description' => $description, 'accountId' => $accountId));
    }
    
    
    /**
     * Приемане на плащане към инциаторът (@see eshop_InitiatorPaymentIntf)
     *
     * @param int         $objId             - ид на обекта
     * @param string      $reason            - основания за плащане
     * @param string|null $payer             - име на наредителя
     * @param float|null  $amount            - сума за нареждане
     * @param string      $currencyCode      - валута за нареждане
     * @param int|NULL    $accountId         - ид на наша сметка, или NULL ако няма
     * @param int         $linkedContainerId - свързан контейнер
     *
     * @return void
     */
    public function receivePayment($objId, $reason, $payer, $amount, $currencyCode, $accountId = null, $linkedContainerId = null)
    {
        $rec = self::fetch($objId);
        $rec->paidOnline = 'yes';
        self::save($rec, 'paidOnline');
        
        try {
            $saleRec = self::forceSale($rec);
        } catch (core_exception_Expect $e) {
            reportException($e);
        }
        
        if (!is_object($saleRec)) {
            
            return;
        }
        
        try {
            $bankRec = self::forceBankIncomeDocument($reason, $saleRec, $accountId, $amount);
            bank_IncomeDocuments::conto($bankRec->id);
            bank_IncomeDocuments::logWrite('Автоматично контиране на пристигнало плащане', $bankRec->id, 360, core_Users::SYSTEM_USER);
            
            if (isset($linkedContainerId)) {
                doc_Linked::add($linkedContainerId, $bankRec->containerId, 'doc', 'doc', 'Получено плащане от ePay.bg');
            }
        } catch (core_exception_Expect $e) {
            reportException($e);
        }
    }
    
    
    /**
     * Изпращане на напомнящ имейл за забравена поръчка
     *
     * @param stdClass $rec
     */
    private static function sendNotificationEmail($rec)
    {
        // Има ли настройки за изпращане на имейл
        $settings = cms_Domains::getSettings($rec->domainId);
        if (empty($rec->email) || empty($settings->inboxId)) {
            
            return;
        }
        
        $domainName = '';
        cms_Domains::getAbsoluteUrl($rec->domainId, $domainName);
        $cartUrl = self::getGrantAccessUrl($rec->id);
        $createdOn = dt::mysql2verbal($rec->createdOn, 'd.m.Y');
        
        $lang = cms_Domains::fetchField($rec->domainId, 'lang');
        core_Lg::push($lang);
        $deleteTime = core_Type::getByName('time(uom=hours,noSmart)')->toVerbal($settings->timeBeforeDelete);
        
        $file = ($lang == 'bg') ? 'eshop/tpl/email/NotifyEmailBg.shtml' : 'eshop/tpl/email/NotifyEmailEn.shtml';
        $fileTxt = ($lang == 'bg') ? 'eshop/tpl/email/NotifyEmailBg.txt' : 'eshop/tpl/email/NotifyEmailEn.txt';
        
        $companyName = tr(crm_Companies::fetchOwnCompany()->company);
        $body = new stdClass();
        
        foreach (array('html' => 'xhtml', 'text' => 'plain') as $var => $mode) {
            Mode::push('text', $mode);
            
            $file = ($var == 'html') ? $file : $fileTxt;
            $tpl = getTplFromFile($file);
            $tpl->replace(new core_ET($settings->emailBodyIntroduction), 'INTRODUCTION');
            $settings->emailBodyFooter = str_replace("\n", '<br>', $settings->emailBodyFooter);
            
            $tpl->replace(new core_ET($settings->emailBodyFooter), 'FOOTER');
            $body->{$var} = $tpl;
            
            $link = ht::createLink($domainName, $cartUrl)->getContent();
            if ($mode == 'plain') {
                $link = html2text_Converter::toRichText($link);
                $link = cls::get('type_Richtext')->toVerbal($link);
            }
            
            $body->{$var}->replace($deleteTime, 'DELETE_TIME');
            $body->{$var}->replace($createdOn, 'DATE');
            $body->{$var}->replace(core_Type::getByName('varchar')->toVerbal($rec->personNames), 'NAME');
            $body->{$var}->replace($link, 'LINK');
            $body->{$var}->replace($companyName, 'COMPANY_NAME');
            Mode::pop('text');
            
            $body->{$var} = $body->{$var}->getContent();
        }
        
        $options = array('encoding' => 'utf-8', 'no_thread_hnd' => true, 'no_return_path' => 'no_return_path', 'no_return_receipt' => 'no_return_receipt');
        $subject = tr('Незавършена поръчка в') . " {$domainName}";
        
        // Опит за изпращане на имейл-а
        $error = null;
        $isSended = email_Sent::sendOne($settings->inboxId, $rec->email, $subject, $body, $options, null, $error);
        
        if ($isSended) {
            eshop_Carts::logWrite('АВТОМАТИЧНО изпращане на имейл за забравена поръчка', $rec->id);
        } else {
            eshop_Carts::logErr("Грешка при изпращане на имейл за забравена поръчка: '{$error}'", $rec->id);
        }
        core_Lg::pop();
        
        return $isSended;
    }
    
    
    /**
     * След подготовка на тулбара за единичния изглед
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;
        
        if (self::haveRightFor('makenewsale', $rec)) {
            $data->toolbar->addBtn('Нова продажба', array($mvc, 'makenewsale', 'id' => $rec->id, 'ret_url' => true, ''), null, 'ef_icon=img/16/cart_go.png,title=Създаване на нова продажба към количката');
        }
        
        if (log_System::haveRightFor('list')) {
            $data->toolbar->addBtn('Системен лог', array('log_System', 'list', 'search' => $mvc->className, 'objectId' => $data->rec->id), 'ef_icon=img/16/bug.png, title=Разглеждане на логовете на документа, order=20, row=3');
        }
        
        if ($mvc->haveRightFor('finalize', $rec)) {
            $data->toolbar->addBtn('Финализиране', array($mvc, 'finalize', $rec->id, 'ret_url' => true), 'ef_icon=img/16/bug.png, title=Ръчно финализиране на количката');
        }
    }
    
    
    /**
     * Създаване на нова продажба към съществуваща количка
     */
    public function act_Makenewsale()
    {
        $this->requireRightFor('makenewsale');
        expect($id = Request::get('id', 'int'));
        expect($rec = self::fetch($id));
        $this->requireRightFor('makenewsale', $rec);
        
        // Подготовка на формата
        $form = cls::get('core_Form');
        $form->title = 'Създаване на нова продажба към|* ' . $this->getFormTitleLink($rec->id);
        $form->FLD('folderId', 'key2(mvc=doc_Folders,select=title,allowEmpty, restrictViewAccess=yes,coverInterface=crm_ContragentAccRegIntf)', 'caption=Контрагент,mandatory,single=none,after=title,mandatory');
        $form->input();
        
        if ($form->isSubmitted()) {
            if (isset($rec->saleId)) {
                $form->setWarning('folderId', 'Към количката има създадена вече продажба|*. |Наистина ли искате да продължите|*');
            }
            
            // Ако няма грешки
            if (!$form->gotErrors()) {
                
                // Създава се нова продажба и количката се свързва към нея
                $fRec = $form->rec;
                $rec->saleFolderId = $fRec->folderId;
                $newSaleRec = self::forceSale($rec, true, false);
                sales_Sales::logWrite('Форсирана нова продажба към количка', $newSaleRec->id);
                
                if (isset($rec->saleId)) {
                    $oldContainerId = sales_Sales::fetchField($rec->saleId, 'containerId');
                    doc_Linked::add($oldContainerId, $newSaleRec->containerId);
                    sales_Sales::logWrite('Продажбата е откачена от количката', $rec->saleId);
                }
                
                return new Redirect(array('sales_Sales', 'single', $newSaleRec->id), 'Успешно създадена нова продажба към количката');
            }
        }
        
        $form->toolbar->addSbBtn('Напред', 'save', 'ef_icon = img/16/move.png, title = Създаване на нова продажба');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        $tpl = $this->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($tpl, $form);
        
        // Рендиране на формата
        return $tpl;
    }
    
    
    /**
     * Връща защите урл присвояващо количка без потребител към текущия брид
     * или ако има нова количка към този брид редирект към нея
     *
     * @param int $id
     *
     * @return string $url
     */
    public static function getGrantAccessUrl($id)
    {
        $token = str::addHash("cart{$id}", 6, eshop_Setup::get('CART_ACCESS_SALT'));
        Request::setProtected('accessToken');
        $url = array('eshop_Carts', 'view', $id, 'accessToken' => $token);
        $url = toUrl($url, 'absolute');
        Request::removeProtected('accessToken');
        
        return $url;
    }
}
