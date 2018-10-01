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
    public $title = 'Кошници на онлайн магазина';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'eshop_InitiatorPaymentIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, eshop_Wrapper, plg_Rejected, plg_Modified,plg_Sorting';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productCount=Артикули,total=Сума,ip,brid,domainId,userId,saleId,createdOn,activatedOn,state';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Онлайн поръчка';
    
    
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
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('ip', 'varchar', 'caption=Ип,input=none');
        $this->FLD('brid', 'varchar(8)', 'caption=Браузър,input=none');
        $this->FLD('domainId', 'key(mvc=cms_Domains, select=titleExt)', 'caption=Магазин,input=none');
        $this->FLD('userId', 'key(mvc=core_Users, select=nick)', 'caption=Потребител,input=none');
        $this->FLD('freeDelivery', 'enum(yes=Да,no=Не)', 'caption=Безплатна доставка,input=none,notNull,value=no');
        $this->FLD('deliveryNoVat', 'double(decimals=2)', 'caption=Общи данни->Доставка без ДДС,input=none');
        $this->FLD('deliveryTime', 'time', 'caption=Общи данни->Срок на доставка,input=none');
        $this->FLD('total', 'double(decimals=2)', 'caption=Общи данни->Стойност,input=none');
        $this->FLD('totalNoVat', 'double(decimals=2)', 'caption=Общи данни->Стойност без ДДС,input=none');
        $this->FLD('paidOnline', 'enum(no=Не,yes=Да)', 'caption=Общи данни->Платено,input=none,notNull,value=no');
        $this->FLD('productCount', 'int', 'caption=Общи данни->Брой,input=none');
        
        $this->FLD('personNames', 'varchar(255)', 'caption=Контактни данни->Имена,class=contactData,hint=Вашето име||Your name,mandatory');
        $this->FLD('email', 'email(valid=drdata_Emails->validate)', 'caption=Контактни данни->Имейл,hint=Вашият имейл||Your email,mandatory');
        $this->FLD('tel', 'drdata_PhoneType(type=tel)', 'caption=Контактни данни->Телефон,hint=Вашият телефон,mandatory');
        $this->FLD('country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Контактни данни->Държава');
        
        $this->FLD('termId', 'key(mvc=cond_DeliveryTerms,select=codeName)', 'caption=Доставка->Начин,removeAndRefreshForm=deliveryCountry|deliveryPCode|deliveryPlace|deliveryAddress|deliveryData,silent,mandatory');
        $this->FLD('deliveryCountry', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Доставка->Държава,hint=Страна за доставка');
        $this->FLD('deliveryPCode', 'varchar(16)', 'caption=Доставка->П. код,hint=Пощенски код за доставка');
        $this->FLD('deliveryPlace', 'varchar(64)', 'caption=Доставка->Място,hint=Населено място: град или село и община');
        $this->FLD('deliveryAddress', 'varchar(255)', 'caption=Доставка->Адрес,hint=Вашият адрес');
        $this->FLD('deliveryData', 'blob(serialize, compress)', 'input=none');
        $this->FLD('instruction', 'richtext(rows=2)', 'caption=Доставка->Инструкции');
        
        $this->FLD('paymentId', 'key(mvc=cond_PaymentMethods,select=title,allowEmpty)', 'caption=Плащане->Начин,mandatory');
        $this->FLD('makeInvoice', 'enum(none=Без фактуриране,person=Фактура на лице, company=Фактура на фирма)', 'caption=Плащане->Фактуриране,silent,removeAndRefreshForm=locationIdinvoiceNames|invoiceVatNo|invoiceAddress|invoicePCode|invoicePlace|invoiceCountry|invoiceNames');
        
        $this->FLD('saleFolderId', 'key(mvc=doc_Folders)', 'caption=Данни за фактура->Папка,input=none,silent,removeAndRefreshForm=invoiceNames|invoiceVatNo|invoiceAddress|invoicePCode|invoicePlace|invoiceCountry');
        $this->FLD('invoiceNames', 'varchar(128)', 'caption=Данни за фактура->Наименование,invoiceData,hint=Име,input=none,mandatory');
        $this->FLD('invoiceVatNo', 'drdata_VatType', 'caption=Данни за фактура->VAT/EIC,input=hidden,mandatory,invoiceData');
        $this->FLD('invoiceCountry', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Данни за фактура->Държава,hint=Държава по регистрация,input=none,invoiceData');
        $this->FLD('invoicePCode', 'varchar(16)', 'caption=Данни за фактура->П. код,invoiceData,hint=Пощенски код на фирмата,input=none');
        $this->FLD('invoicePlace', 'varchar(64)', 'caption=Данни за фактура->Град,invoiceData,hint=Населено място: град или село и община,input=none');
        $this->FLD('invoiceAddress', 'varchar(255)', 'caption=Данни за фактура->Адрес,invoiceData,hint=Адрес на регистрация на фирмата,input=none');
        
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
     * Сортира записите по време на създаване
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('createdOn', 'DESC');
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
            if (!deals_Helper::checkQuantity($packagingId, $packQuantity, $warning)) {
                $msg = $warning;
                $success = false;
                $skip = true;
            }
        }
        
        // Ако има избран склад, проверка дали к-то е допустимо
        $msg = '|Проблем при добавянето на артикула|*!';
        
        $maxQuantity = eshop_CartDetails::getMaxQuantity($productId, $quantityInPack);
        if (isset($maxQuantity) && $maxQuantity < $packQuantity) {
            $msg = '|Избраното количество не е налично|*';
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
                
                $packagingName = tr(cat_UoM::getShortName($packagingId));
                $packType = cat_UoM::fetchField($packagingId, 'type');
                if ($packType == 'packaging') {
                    $packagingName = str::getPlural($exRec->packQuantity, $packagingName, true);
                }
                
                $packQuantity = core_Type::getByName('double(smartRound)')->toVerbal($exRec->packQuantity);
                $productName = cat_Products::getVerbal($productId, 'name');
                
                $settings = cms_Domains::getSettings();
                $addText = new core_ET($settings->addProductText);
                $addText->append($packagingName, 'packagingId');
                $addText->append($productName, 'productName');
                $addText->append($packQuantity, 'packQuantity');

                $msg = $addText->getContent();
                $success = true;
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
            if($success === true) {
                $resObj2 = new stdClass();
                $resObj2->func = 'Sound';
                $resObj2->arg = array('soundOgg' => sbf('sounds/bell.ogg', ''),
                    'soundMp3' => sbf('sounds/bell.mp3', ''),
                );
            } else {
                $resObj2 = new stdClass();
            }


            $hitTime = Request::get('hitTime', 'int');
            $idleTime = Request::get('idleTime', 'int');
            $statusData = status_Messages::getStatusesData($hitTime, $idleTime);
            
            $res = array_merge(array($resObj, $resObj2), (array) $statusData);
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
     * @return int $id ид-то на обновения запис
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
        $count = $dQuery->count();
        
        while ($dRec = $dQuery->fetch()) {
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
        }
        
        // Ако има цена за доставка добавя се и тя
        if ($count) {
            if ($delivery = eshop_CartDetails::getDeliveryInfo($rec)) {
                
                if ($delivery['amount'] >= 0) {
                    $rec->deliveryTime = $delivery['deliveryTime'];
                    $settings = cms_Domains::getSettings();
                    
                    $rec->deliveryNoVat = $delivery['amount'];
                    $freeDelivery = currency_CurrencyRates::convertAmount($settings->freeDelivery, null, $settings->currencyId);
                    $deliveryNoVat = $rec->deliveryNoVat;
                    
                    // Ако има сума за безплатна доставка и доставката е над нея, тя не се начислява
                    if (!empty($settings->freeDelivery) && round($rec->total, 2) >= round($freeDelivery, 2)){
                        $delivery = $deliveryNoVat = 0;
                        $rec->freeDelivery = 'yes';
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
        
        $id = $this->save_($rec, 'productCount,total,totalNoVat,deliveryNoVat,deliveryTime,freeDelivery');
        
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
        $url = array('eshop_Carts', 'force');
        
        if (isset($cartId)) {
            $cartRec = self::fetch($cartId);
            if ($settings->enableCart == 'no' && !$cartRec->productCount) {
                
                return new core_ET(' ');
            }
            $amount = core_Type::getByName('double(decimals=2)')->toVerbal($cartRec->total);
            $amount = str_replace('&nbsp;', ' ', $amount);
            $count = core_Type::getByName('int')->toVerbal($cartRec->productCount);
            $url = array('eshop_Carts', 'view', $cartId);
            $str = ($count == 1) ? 'артикул' : 'артикула';
            $hint = "|*{$count} |{$str} за|* {$amount} " . $settings->currencyId;
            
            if ($count) {
                $tpl->append(new core_ET("<span class='count'>[#count#]</span>"));
            }
        } else {
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
        $selectedClass = ($currentTab == 'eshop_Carts') ? 'class=selected-external-tab' : '';
        $tpl = ht::createLink($tpl, $url, false, "title={$hint}, ef_icon=img/16/cart-black.png,{$selectedClass}");
        
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
        if(!core_Packs::isInstalled('eshop')) return;
        
        $cartId = self::force();
        
        redirect(array($this, 'view', $cartId, 'ret_url' => true));
    }
    
    
    /**
     * Финализиране на поръчката
     */
    public function act_Finalize()
    {
        Request::setProtected('description,accountId');
        $this->requireRightFor('finalize');
        expect($id = Request::get('id', 'int'));
        expect($rec = self::fetch($id));
        $description = Request::get('description', 'varchar');
        $accountId = Request::get('accountId', 'key(mvc=bank_OwnAccounts)');
        
        $this->requireRightFor('finalize', $rec);
        $cu = core_Users::getCurrent('id', false);
        
        Mode::set('currentExternalTab', 'eshop_Carts');
        
        $saleRec = self::forceSale($rec);
        
        // Ако е партньор и има достъп до нишката, директно се реидректва към нея
        $colabUrl = null;
        if (core_Packs::isInstalled('colab') && isset($cu) && core_Users::isContractor($cu)) {
            $threadRec = doc_Threads::fetch($saleRec->threadId);
            if (colab_Threads::haveRightFor('single', $threadRec)) {
                $colabUrl = array('colab_Threads', 'single', 'threadId' => $saleRec->threadId);
            }
        }
        
        // Ако е платено онлайн се създава нов ПБД в нишката на продажбата
        if($rec->paidOnline == 'yes'){
            try{
                $incomeFields = array('reason' => $description, 'termDate' => dt::today(), 'operation' => 'customer2bank', 'ownAccountId' => $accountId);
               
                bank_IncomeDocuments::create($threadRec->id, $incomeFields, true);
            } catch(core_exception_Expect $e){
                reportException($e);
            }
        }
        
        if (is_array($colabUrl) && count($colabUrl)) {
            
            return new Redirect($colabUrl, 'Успешно създадена заявка за продажба|*!');
        }
        
        return new Redirect(cls::get('eshop_Groups')->getUrlByMenuId(null), 'Поръчката е направена|*!');
    }
    
    
    /**
     * Форсира продажба към количката
     * 
     * @param mixed $id
     * 
     * @return stdClass $saleRec
     */
    public function forceSale($id)
    {
        $rec = $this->fetchRec($id);
        if(isset($rec->saleId)) return $rec->saleId;
        expect($rec->state == 'draft');
        
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
        if (isset($rec->saleFolderId)) {
            $Cover = doc_Folders::getCover($rec->saleFolderId);
            $folderId = $rec->saleFolderId;
        } else {
            $country = isset($rec->invoiceCountry) ? $rec->invoiceCountry : $rec->country;
            $folderId = marketing_InquiryRouter::route($company, $personNames, $rec->email, $rec->tel, $country, $rec->invoicePCode, $rec->invoicePlace, $rec->invoiceAddress, $rec->brid);
            
            // Ако папката е на фирма, добавя се нейния ват номер
            $Cover = doc_Folders::getCover($folderId);
            if ($Cover->isInstanceOf('crm_Companies')) {
                $companyRec = crm_Companies::fetch($Cover->that);
                $companyRec->vatId = $rec->invoiceVatNo;
                crm_Companies::save($companyRec, 'vatId');
            }
        }
        
        $settings = cms_Domains::getSettings();
        $templateId = cls::get('sales_Sales')->getDefaultTemplate((object) array('folderId' => $folderId));
        $templateLang = doc_TplManager::fetchField($templateId, 'lang');
        
        core_Lg::push($templateLang);
        
        // Форсиране на потребителя, ако има или системния потребител за създател на документа
        if ($cu && $cu != core_Users::SYSTEM_USER) {
            core_Users::sudo($cu);
        } else {
            core_Users::forceSystemUser();
        }
        
        // Дефолтни данни на продажбата
        $fields = array('valior' => dt::today(),
            'deliveryTermId' => $rec->termId,
            'deliveryTermTime' => $rec->deliveryTime,
            'paymentMethodId' => $rec->paymentId,
            'makeInvoice' => ($rec->makeInvoice == 'none') ? 'no' : 'yes',
            'chargeVat' => $settings->chargeVat,
            'currencyId' => $settings->currencyId,
            'shipmentStoreId' => $settings->storeId,
            'deliveryLocationId' => $rec->locationId,
        );
        
        $fields['dealerId'] = sales_Sales::getDefaultDealerId($folderId, $fields['deliveryLocationId']);
        
        // Създаване на продажба по количката
        $saleId = sales_Sales::createNewDraft($Cover->getClassId(), $Cover->that, $fields);
        
        if ($cu && $cu != core_Users::SYSTEM_USER) {
            core_Users::exitSudo($cu);
        } else {
            core_Users::cancelSystemUser();
        }
        
        core_Lg::pop();
        sales_Sales::logWrite('Създаване от онлайн поръчка', $saleId);
        
        // Добавяне на артикулите от количката в продажбата
        $dQuery = eshop_CartDetails::getQuery();
        $dQuery->where("#cartId = {$rec->id}");
        while ($dRec = $dQuery->fetch()) {
            $price = ($dRec->amount / $dRec->quantity);
            $price = isset($dRec->discount) ? ($price / (1 - $dRec->discount)) : $price;
            
            if ($dRec->haveVat == 'yes') {
                $price /= 1 + $dRec->vat;
            }
            
            $paramsText = eshop_CartDetails::getUniqueParamsAsText($dRec);
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
        if($rec->paidOnline == 'yes'){
            
            // Продажбата се активира
            $saleRec = sales_Sales::fetchRec($saleId);
            $saleRec->contoActions = 'activate';
            $saleRec->isContable = 'activate';
            sales_Sales::save($saleRec);
            sales_Sales::conto($saleRec->id);
            cls::get('sales_Sales')->updateMaster($saleRec->id);
        } else {
            
            // Ако не е става на заявка
            $saleRec = self::makeSalePending($saleId);
        }
        
        self::activate($rec, $saleId);
        
        doc_Threads::doUpdateThread($saleRec->threadId);
        
        if (!(core_Packs::isInstalled('colab') && isset($cu) && core_Users::isContractor($cu))) {
            self::sendEmail($rec, $saleRec);
            doc_Threads::doUpdateThread($saleRec->threadId);
        }
        
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
        if (empty($settings->inboxId)) {
            
            return;
        }
        
        $lang = cms_Domains::fetchField($rec->domainId, 'lang');
        core_Lg::push($lang);
        
        // Подготовка на тялото на имейла
        $threadCount = doc_Threads::count("#folderId = {$saleRec->folderId}");
        $body = ($threadCount == 1) ? $settings->emailBodyWithReg : $settings->emailBodyWithoutReg;
        $body = new core_ET($body);
        $body->replace($rec->personNames, 'NAME');
        
        if ($hnd = sales_Sales::getHandle($saleRec->id)) {
            $body->replace("#{$hnd}", 'SALE_HANDLER');
        }
        
        // Линка за регистрация
        $Cover = doc_Folders::getCover($saleRec->folderId);
        $url = core_Forwards::getUrl('colab_FolderToPartners', 'Createnewcontractor', array('companyId' => (int) $Cover->that, 'email' => $rec->email, 'rand' => str::getRand(), 'userNames' => $rec->personNames), 604800);
        
        $url = "[link={$url}]" . tr('връзка||link') . '[/link]';
        $body->replace($url, 'link');
        
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
        Mode::set('isSystemCanSingle', true);
        
        email_Outgoings::save($emailRec);
        
        email_Outgoings::logWrite('Създаване от онлайн поръчка', $emailRec->id);
        cls::get('email_Outgoings')->invoke('AfterActivation', array(&$emailRec));
        
        // Изпращане на имейла
        $options = (object) array('encoding' => 'utf-8', 'boxFrom' => $settings->inboxId, 'emailsTo' => $emailRec->email);
        email_Outgoings::send($emailRec, $options, $lang);
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
        $tpl->replace($cartInfo, 'VAT_STATUS');
        
        // Ако има последно активирана кошница да се показва като съобщение
        if ($lastCart = self::getLastActivatedCart()) {
            if ($lastCart->activatedOn >= dt::addSecs(-1 * 60 * 60 * 2, dt::now())) {
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
        $id = Request::get('id', 'int');
        if (empty($id)) {
            redirect(array($this, 'force'));
        }
        
        $rec = self::fetch($id);
        if (empty($rec)) {
            redirect(array($this, 'force'));
        }
        
        // Редирект към ешопа ако количката е активна
        if ($rec->state == 'active') {
            $shopUrl = cls::get('eshop_Groups')->getUrlByMenuId(null);
            redirect($shopUrl);
        }
        
        $this->requireRightFor('viewexternal', $rec);
        Mode::set('currentExternalTab', 'eshop_Carts');
        
        $tpl = self::renderView($rec);
        $tpl->prepend("<div id = 'cart-view-single'>");
        $tpl->append('</div>');
        
        Mode::set('wrapper', 'cms_page_External');
        
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
        
        $row = self::recToVerbal($rec);
        $tpl->replace($row->termId, 'termId');
        $tpl->replace($row->paymentId, 'paymentId');
        if ($Driver = cond_DeliveryTerms::getTransportCalculator($rec->termId)) {
            $tpl->replace($Driver->renderDeliveryInfo($rec), 'DELIVERY_BLOCK');
        }
        
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
            foreach (array('invoiceNames', 'invoiceVatNo', 'invoicePCode', 'invoicePlace', 'invoiceAddress') as $name) {
                $tpl->replace(core_Type::getByName('varchar')->toVerbal($rec->{$name}), $name);
            }
            
            $nameCaption = ($rec->makeInvoice == 'person') ? 'Лице' : 'Фирма';
            $tpl->replace(tr($nameCaption), 'INV_CAPTION');
            if (!empty($rec->invoiceVatNo)) {
                $vatCaption = ($rec->makeInvoice == 'person') ? 'ЕГН' : 'VAT/EIC';
                $tpl->replace(tr($vatCaption), 'VAT_CAPTION');
            }
        } else {
            $tpl->replace(tr('Без фактура'), 'NO_INVOICE');
        }
        
        if ($rec->deliveryNoVat < 0) {
            $tpl->replace(tr('Има проблем при изчислението на доставката. Моля, обърнете се към нас!'), 'deliveryError');
        }
        
        if (!empty($rec->instruction)) {
            $tpl->replace($row->instruction, 'instruction');
        }
        
        if (isset($rec->deliveryTime)) {
            $deliveryTime = dt::mysql2verbal(dt::addSecs($rec->deliveryTime, null, false), 'd.m.Y');
            $tpl->replace($deliveryTime, 'deliveryTime');
        }
        
        if (eshop_Carts::haveRightFor('checkout', $rec) && $rec->personNames) {
            $editBtn = ht::createLink('', array(eshop_Carts, 'order', $rec->id, 'ret_url' => true), false, 'ef_icon=img/16/edit.png,title=Редактиране на данните за поръчка');
            $tpl->append($editBtn, 'editBtn');
        }
        
        if(!empty($rec->personNames)){
            $tpl->append('borderTop', 'BORDER_CLASS');
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
        $row = self::recToVerbal($rec);
        $settings = cms_Domains::getSettings();
        if(!empty($settings->freeDelivery)){
            $row->freeDelivery = $Double->toVerbal($settings->freeDelivery);
            $row->freeDeliveryCurrencyId = $settings->currencyId;
        }
        
        $total = currency_CurrencyRates::convertAmount($rec->total, null, null, $settings->currencyId);
        $row->total = $Double->toVerbal($total);
        $row->currencyId = $settings->currencyId;
        
        if ($settings->chargeVat != 'yes') {
            $totalNoVat = currency_CurrencyRates::convertAmount($rec->totalNoVat, null, null, $settings->currencyId);
            $vatAmount = $total - $totalNoVat;
            
            $row->totalNoVat = $Double->toVerbal($totalNoVat);
            $row->totalVat = $Double->toVerbal($vatAmount);
            $row->totalNoVatCurrencyId = $row->vatCurrencyId = $row->currencyId;
        } else {
            unset($row->totalNoVat);
        }
        
        // Ако има доставка се показва и нея
        if (isset($rec->deliveryNoVat) && $rec->deliveryNoVat >= 0) {
            $row->deliveryCaption = tr('Доставка||Shipping');
            $row->deliveryCurrencyId = $row->currencyId;
            
            if($rec->freeDelivery != 'no'){
                $row->deliveryAmount = "<span style='text-transform: uppercase;color:green';>" . tr('Безплатна') . "</span>";
                unset($row->deliveryCurrencyId);
                $row->deliveryColspan = "colspan=2";
            } else {
                $transportId = cat_Products::fetchField("#code = 'transport'", 'id');
                $deliveryAmount = $rec->deliveryNoVat * (1 + cat_Products::getVat($transportId));
                $deliveryAmount = currency_CurrencyRates::convertAmount($deliveryAmount, null, null, $settings->currencyId);
                $deliveryAmountV = core_Type::getByName('double(decimals=2)')->toVerbal($deliveryAmount);
                $row->deliveryAmount = $deliveryAmountV;
            }
        }
        
        $row->productCount .= '&nbsp;' . (($rec->productCount == 1) ? tr('артикул') : tr('артикула'));
        unset($row->invoiceVatNo);
        $tpl->placeObject($row);
        
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
        
        $btn = ht::createLink(tr('Магазин'), $shopUrl, null, 'title=Назад към магазина,class=eshop-link,ef_icon=img/16/cart_go_back.png');
        $tpl->append($btn, 'CART_TOOLBAR_TOP');
        
        $wideSpan = '<span>|</span>';
        
        if (eshop_CartDetails::haveRightFor('add', (object) array('cartId' => $rec->id))) {
            $addUrl = array('eshop_CartDetails', 'add', 'cartId' => $rec->id, 'external' => true, 'ret_url' => true);
            $btn = ht::createLink(tr('Добавяне'), $addUrl, null, 'title=Добавяне на нов артикул,class=eshop-link,ef_icon=img/16/add1-16.png');
            $tpl->append($wideSpan . $btn, 'CART_TOOLBAR_TOP');
        }
        
        if (!empty($rec->productCount) && eshop_CartDetails::haveRightFor('removeexternal', (object) array('cartId' => $rec->id))) {
            $emptyUrl = array('eshop_CartDetails', 'removeexternal', 'cartId' => $rec->id, 'ret_url' => $shopUrl);
            $btn = ht::createLink(tr('Изчистване'), $emptyUrl, 'Сигурни ли сте, че искате да изчистите артикулите?', 'title=Изчистване на всички артикули,class=eshop-link,ef_icon=img/16/deletered.png');
            $tpl->append($wideSpan . $btn, 'CART_TOOLBAR_TOP');
        }
        
        $checkoutUrl = (eshop_Carts::haveRightFor('checkout', $rec)) ? array('eshop_Carts', 'order', $rec->id, 'ret_url' => true) : array();
        if (empty($rec->personNames) && count($checkoutUrl)) {
            $btn = ht::createBtn(tr('Данни за поръчката') . ' »', $checkoutUrl, null, null, "title=Поръчване на артикулите,class=order-btn eshop-btn");
            $tpl->append($btn, 'CART_TOOLBAR_RIGHT');
        }
        
        // Ако се изисква онлайн плащане добавя се бутон към него
        if (isset($rec->paymentId)) {
            if($PaymentDriver = cond_PaymentMethods::getOnlinePaymentDriver($rec->paymentId)){
                if(!empty($rec->productCount)){
                    $cancelUrl = array('eshop_Carts', 'abort', $rec->id);
                    $okUrl = array('eshop_Carts', 'confirm', $rec->id);
                    $settings = cms_Domains::getSettings();
                    $btn = $PaymentDriver->getPaymentBtn($rec->paymentId, $rec->total, $settings->currencyId, $okUrl, $cancelUrl, 'eshop_Carts', $rec->id);
                    $tpl->append($btn, 'CART_TOOLBAR_RIGHT');
                }
            }
        }
        
        if (eshop_Carts::haveRightFor('finalize', $rec)) {
            $btn = ht::createBtn('Завършване', array('eshop_Carts', 'finalize', $rec->id), 'Сигурни ли сте, че искате да направите поръчката|*!', null, "title=Завършване на поръчката,class=order-btn eshop-btn");
            $tpl->append($btn, 'CART_TOOLBAR_RIGHT');
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
        $row = self::recToVerbal($rec);
        $data = (object) array('rec' => $rec, 'row' => $row);
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
        while ($dRec = $dQuery->fetch()) {
            $data->recs[$dRec->id] = $dRec;
            $row = eshop_CartDetails::recToVerbal($dRec, $fields);
            
            if (!empty($dRec->discount)) {
                $discountType = type_Set::toArray($settings->discountType);
                $row->finalPrice .= "<div class='external-discount'>";
                
                if (isset($discountType['amount'])) {
                    $amountWithoutDiscount = $dRec->finalPrice / (1 - $dRec->discount);
                    $discountAmount = core_Type::getByName('double(decimals=2)')->toVerbal($amountWithoutDiscount);
                    $row->finalPrice .= "<div class='external-discount-amount'> {$discountAmount}</div>";
                }
                
                if (isset($discountType['amount'], $discountType['percent'])) {
                    $row->finalPrice .= ' / ';
                }
                
                if (isset($discountType['percent'])) {
                    $discountPercent = core_Type::getByName('percent(smartRound)')->toVerbal($dRec->discount);
                    $discountPercent = str_replace('&nbsp;', '', $discountPercent);
                    $row->finalPrice .= "<div class='external-discount-percent'> -{$discountPercent}</div>";
                }
                
                $row->finalPrice .= '</div>';
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
            $data->listTableMvc->commonFirst = true;
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
        
        if (in_array($action, array('addtocart', 'checkout', 'finalize'))) {
            if (!$mvc->haveRightFor('viewexternal', $rec)) {
                $requiredRoles = 'no_one';
            }
        }
        
        if ($action == 'finalize' && isset($rec)) {
            if (empty($rec->personNames) || empty($rec->productCount)) {
                $requiredRoles = 'no_one';
            } elseif ($rec->deliveryNoVat < 0) {
                $requiredRoles = 'no_one';
            }
        }
        
        if ($action == 'delete' && isset($rec)) {
            if($rec->state != 'draft'){
                $requiredRoles = 'no_one';
            } else {
                $compareDate = dt::addSecs($rec->createdOn, 60 * 60 * 24 * 2);
                if($compareDate >= dt::now()){
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
        $row->ip = type_Ip::decorateIp($rec->ip, $rec->createdOn);
        
        if(isset($fields['-list'])){
            $row->ROW_ATTR['class'] = "state-{$rec->state}";
            if (isset($rec->saleId)) {
                $row->saleId = sales_Sales::getLink($rec->saleId, 0);
            }
            $row->domainId = cms_Domains::getHyperlink($rec->domainId);
            
            $currencyCode = cms_Domains::getSettings($rec->domainId)->currencyId;
            $total = currency_CurrencyRates::convertAmount($rec->total, null, null, $currencyCode);
            $row->total = $mvc->getFieldType('total')->toVerbal($total) . " <span class='cCode'>{$currencyCode}</span>";
        }
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
        
        $settings = cms_Domains::getSettings();
        $countries = keylist::toArray($settings->countries);
        
        $data = new stdClass();
        $data->action = 'order';
        $this->prepareEditForm($data);
        $data->form->setAction($this, 'order');
        
        $form = &$data->form;
        $form->title = 'Данни за поръчка';
        $form->countries = $countries;
        
        self::prepareOrderForm($form);
        
        // Добавяне на линк за логване, ако от преди се е логвам потребителя
        cms_Helper::setLoginInfoIfNeeded($form);
        
        $form->input(null, 'silent');
        self::setDefaultsFromFolder($form, $form->rec->saleFolderId);
        
        $form->setOptions('country', drdata_Countries::getOptionsArr($form->countries));
        if(count($form->countries) == 1){
            $form->setDefault('country', key($form->countries));
            $form->setField('country', 'input=hidden');
        } else {
            $form->setDefault('country', cls::get('drdata_Countries')->getByIp());
        }
        
        $cu = core_Users::getCurrent('id', false);
        if (isset($cu) && $form->rec->makeInvoice != 'none') {
            $profileRec = crm_Profiles::getProfile($cu);
            if (isset($form->rec->saleFolderId)){
                $form->rec->makeInvoice = ($form->rec->saleFolderId == $profileRec->folderId) ? 'person' : 'company';
            }
        }
        
        // Ако има условие на доставка то драйвера му може да добави допълнителни полета
        if (isset($form->rec->termId)) {
            
            // Държавата за доставка да е тази от ип-то по дефолт
            if ($countryCode2 = drdata_IpToCountry::get()) {
                $form->setDefault('deliveryCountry', drdata_Countries::fetchField("#letterCode2 = '{$countryCode2}'", 'id'));
            }
            
            if ($Driver = cond_DeliveryTerms::getTransportCalculator($form->rec->termId)) {
                $Driver->addFields($form);
                $fields = $Driver->getFields();
                foreach ($fields as $fld) {
                    $form->setDefault($fld, $form->rec->deliveryData[$fld]);
                }
            }
        }
        
        if(!empty($form->rec->deliveryCountry)){
            $form->countries[$form->rec->deliveryCountry] = $form->rec->deliveryCountry;
        }
        
        $form->setOptions('deliveryCountry', drdata_Countries::getOptionsArr($form->countries));
        if(count($form->countries) == 1){
            $form->setDefault('deliveryCountry', key($form->countries));
            $form->setReadOnly('deliveryCountry');
        }
        
        $invoiceFields = $form->selectFields('#invoiceData');
        if (isset($form->rec->makeInvoice) && $form->rec->makeInvoice != 'none') {
            
            // Ако има ф-ра полетата за ф-ра се показват
            foreach ($invoiceFields as $name => $fld) {
                $form->setField($name, 'input');
            }
            
            if ($form->rec->makeInvoice == 'person') {
                $form->setField('invoiceNames', 'caption=Данни за фактура->Име');
                $form->setField('invoiceVatNo', 'caption=Данни за фактура->ЕГН');
                $form->setFieldType('invoiceVatNo', 'bglocal_EgnType');
            } else {
                $form->setField('invoiceNames', 'caption=Данни за фактура->Фирма');
                $form->setField('invoiceVatNo', 'caption=Данни за фактура->VAT/EIC');
            }
            
            $form->setFieldAttr('deliveryCountry', 'data-updateonchange=invoiceCountry,class=updateselectonchange');
            $form->setFieldAttr('deliveryPCode', 'data-updateonchange=invoicePCode,class=updateonchange');
            $form->setFieldAttr('deliveryPlace', 'data-updateonchange=invoicePlace,class=updateonchange');
            $form->setFieldAttr('deliveryAddress', 'data-updateonchange=invoiceAddress,class=updateonchange');
            
            if(!empty($form->rec->invoiceCountry)){
                $form->countries[$form->rec->invoiceCountry] = $form->rec->invoiceCountry;
            }
            $form->setOptions('invoiceCountry', drdata_Countries::getOptionsArr($form->countries));
            if(count($form->countries) == 1){
                $form->setDefault('invoiceCountry', key($form->countries));
                $form->setReadOnly('invoiceCountry');
            }
        } else {
            foreach ($invoiceFields as $name => $fld) {
                $form->setField($name, 'input=none');
            }
        }
        
        $form->input();
        if ($Driver) {
            $Driver->checkForm($form);
        }
        
        if ($rec->makeInvoice != 'none') {
            $form->setDefault('invoiceCountry', $rec->deliveryCountry);
            $form->setDefault('invoicePCode', $rec->deliveryPCode);
            $form->setDefault('invoicePlace', $rec->deliveryPlace);
            $form->setDefault('invoiceAddress', $rec->deliveryAddress);
        }
        
        if ($form->isSubmitted()) {
            $rec = $form->rec;
            
            if(!empty($rec->invoiceNames)){
                if(!preg_match("/[a-zа-я0-9]+.*[a-zа-я0-9]+.*[a-zа-я0-9]+/iu", $rec->invoiceNames)){
                    $form->setError('invoiceNames', 'Неправилен формат');
                }
            }
            
            // Ако има регистриран потребител с този имейл. Изисква се да се логне
            if ($error = cms_Helper::getErrorIfThereIsUserWithEmail($rec->email)) {
                $form->setError('email', $error);
            }
            
            $arr = array('invoiceCountry' => 'deliveryCountry', 'invoicePCode' => 'deliveryPCode', 'invoicePlace' => 'deliveryPlace', 'invoiceAddress' => 'deliveryAddress');
            $emptyFields = array();
            
            if ($rec->makeInvoice != 'none'){
                foreach ($arr as $invField => $delField) {
                    $rec->{$invField} = !empty($rec->{$invField}) ? $rec->{$invField} : $rec->{$delField};
                    if (empty($rec->{$invField})) {
                        $emptyFields[] = $invField;
                    }
                }
            } else {
                $rec->invoiceCountry = $rec->invoicePCode = $rec->invoicePlace = $rec->invoiceAddress = $rec->invoiceNames = $rec->invoiceVatNo = NULL;
            }
           
            if (count($emptyFields)) {
                $form->setError($emptyFields, 'Липсват данни за фактура');
            }
            
            if (!$form->gotErrors()) {
                // Компресиране на данните за доставка от драйвера
                $rec->deliveryData = array();
                if ($Driver) {
                    if (!$form->gotErrors()) {
                        $fields = $Driver->getFields();
                        foreach ($fields as $name) {
                            $rec->deliveryData[$name] = $rec->{$name};
                        }
                    }
                }
                
                // Ако има избрана папка обновява се
                if (!empty($rec->saleFolderId)) {
                    $Cover = doc_Folders::getCover($rec->saleFolderId);
                    $Cover->getInstance()->updateContactDataByFolderId($rec->saleFolderId, $rec->invoiceNames, $rec->invoiceVatNo, $rec->invoiceCountry, $rec->invoicePCode, $rec->invoicePlace, $rec->invoiceAddress);
                }
                
                $cu = core_Users::getCurrent('id', false);
                
                if (isset($cu) && core_Users::isContractor($cu)) {
                    if (isset($rec->saleFolderId)) {
                        $Cover = doc_Folders::getCover($rec->saleFolderId);
                        $contragentClassId = $Cover->getClassId();
                        $contragentId = $Cover->that;
                    } else {
                        $contragentClassId = crm_Persons::getClassId();
                        $contragentId = crm_Profiles::getProfile($cu)->id;
                    }
                   
                    // Ако има въведени адресни данни
                    if (!empty($rec->deliveryCountry) || !empty($rec->deliveryPCode) || !empty($rec->deliveryPlace) || !empty($rec->deliveryAddress)) {
                        $rec->locationId = crm_Locations::update($contragentClassId, $contragentId, $rec->deliveryCountry, 'За получаване на пратки', $rec->deliveryPCode, $rec->deliveryPlace, $rec->deliveryAddress, $rec->locationId);
                    }
                }
                
                if (!$cu) {
                    $userData = array('email' => $rec->email, 'personNames' => $rec->personNames, 'tel' => $rec->tel);
                    log_Browsers::setVars($userData);
                }
                
                $this->save($rec);
                $this->updateMaster($rec);
                core_Lg::pop();
                
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
      
        $deliveryTerms = eshop_Settings::getDeliveryTermOptions('cms_Domains', cms_Domains::getPublicDomain()->id);
        $paymentMethods = eshop_Settings::getPaymentMethodOptions('cms_Domains', cms_Domains::getPublicDomain()->id);
        
        if ($cu) {
            $options = colab_Folders::getSharedFolders($cu, true, 'crm_ContragentAccRegIntf', false);
            $profileRec = crm_Profiles::getProfile($cu);
            $form->setDefault('personNames', $profileRec->name);
            $emails = type_Emails::toArray($profileRec->email);
            $form->setDefault('email', $emails[0]);
            $form->setDefault('tel', $profileRec->tel);
            
            // Задаване като опции
            if (count($options)) {
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
            if ($defaultTermId && !array_key_exists($defaultTermId, $deliveryTerms)) {
                $deliveryTerms[$defaultTermId] = cond_DeliveryTerms::getVerbal($defaultTermId, 'codeName');
            }
            
            // Добавяне на партньорския метод за плащане
            $defaultPaymentId = cond_Parameters::getParameter('crm_Persons', $profileRec->id, 'paymentMethodSale');
            $form->setDefault('paymentId', $defaultPaymentId);
            if ($defaultPaymentId && !array_key_exists($defaultPaymentId, $paymentMethods)) {
                $paymentMethods[$defaultPaymentId] = tr(cond_PaymentMethods::getVerbal($defaultPaymentId, 'name'));
            }
        }
        
        if (count($deliveryTerms) == 1) {
            $form->setDefault('termId', key($deliveryTerms));
        } else {
            $deliveryTerms = array('' => '') + $deliveryTerms;
        }
        $form->setOptions('termId', $deliveryTerms);
        
        if (count($paymentMethods) == 1) {
            $form->setDefault('paymentId', key($paymentMethods));
        } else {
            $paymentMethods = array('' => '') + $paymentMethods;
        }
        $form->setOptions('paymentId', $paymentMethods);
        
        $makeInvoice = bgerp_Setup::get('MANDATORY_CONTACT_FIELDS');
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
        $isColab = isset($cu);
        
        // Ако има избрана папка се записват контрагент данните
        if (isset($folderId)) {
            if ($contragentData = doc_Folders::getContragentData($folderId)) {
                $form->setDefault('invoiceNames', $contragentData->company);
                $form->setDefault('invoiceVatNo', $contragentData->vatNo);
                $form->setDefault('invoiceCountry', $contragentData->countryId);
                $form->setDefault('invoicePCode', $contragentData->pCode);
                $form->setDefault('invoicePlace', $contragentData->place);
                $form->setDefault('invoiceAddress', $contragentData->address);
                $form->countries[$contragentData->countryId] = $contragentData->countryId;
                
                $contragentCover = doc_Folders::getCover($folderId);
                $locations = crm_Locations::getContragentOptions($contragentCover->className, $contragentCover->that, true, $form->countries);
            }
        } else {
            if ($isColab === true) {
                $locations = crm_Locations::getContragentOptions('crm_Persons', crm_Profiles::getProfile($cu)->id, true, $form->countries);
            }
        }
        
        // Ако има локации задават се
        if (count($locations)) {
            $form->setOptions('locationId', array('' => '') + $locations);
            $form->setField('locationId', 'input');
            $form->input('locationId', 'silent');
        }
        
        // Ако е избрана локация допълват се адресните данни за доставка
        if (isset($rec->locationId)) {
            $locationRec = crm_Locations::fetch($rec->locationId);
            foreach (array('deliveryCountry' => 'countryId', 'deliveryPCode' => 'pCode', 'deliveryPlace' => 'place', 'deliveryAddress' => 'address') as $delField => $locField) {
                if (!empty($locationRec->{$locField})) {
                    $form->setDefault($delField, $locationRec->{$locField});
                }
            }
        }
        
        // Ако е колаборатор
        if ($isColab === true) {
           
            // Адреса за доставка е този от последната количка
            $cQuery = eshop_Carts::getQuery();
            $cQuery->where("#userId = {$cu} AND #state = 'active'");
            $cQuery->in('deliveryCountry', $form->countries);
            $cQuery->show('termId,deliveryCountry,deliveryPCode,deliveryPlace,deliveryAddress,locationId');
            $cQuery->orderBy('activatedOn', 'DESC');
            $cQuery->limit(1);
            
            if ($lastCart = $cQuery->fetch()) {
                foreach (array('termId', 'deliveryCountry', 'deliveryPCode', 'deliveryPlace', 'deliveryAddress', 'locationId') as $field) {
                    $form->setDefault($field, $lastCart->{$field});
                }
            } else {
                
                // Ако няма е като този на избраната папка
                if (isset($folderId)) {
                    if ($contragentData = doc_Folders::getContragentData($folderId)) {
                        $form->setDefault('deliveryCountry', $contragentData->countryId);
                        $form->setDefault('deliveryPCode', $contragentData->pCode);
                        $form->setDefault('deliveryPlace', $contragentData->place);
                        $form->setDefault('deliveryAddress', $contragentData->address);
                    }
                }
            }
        }
    }
    
    
    /**
     * Изтриване на забравните колички
     */
    public function cron_DeleteDraftCarts()
    {
        // Всички чернови колички
        $now = dt::now();
        $query = self::getQuery();
        $query->where("#state = 'draft'");
        
        // За всяка
        while ($rec = $query->fetch()) {
            
            // Колко е очаквания и 'живот'
            $settings = cms_Domains::getSettings($rec->domainId);
            $lifetime = isset($rec->userId) ? $settings->lifetimeForUserDraftCarts : $settings->lifetimeForUserDraftCarts;
            
            // Ако и е изтекла продължителността и е чернова се изтрива
            $endOfLife = dt::addSecs($lifetime, $rec->createdOn);
            if ($endOfLife <= $now) {
                self::delete($rec->id);
            }
        }
    }
    
    
    /**
     * Екшън към който се редиректва при отказване на онлайн плащане
     */
    function act_Abort()
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
    function act_Confirm()
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
}
