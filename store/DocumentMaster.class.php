<?php


/**
 * Абстрактен клас за наследяване на складови документи
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class store_DocumentMaster extends core_Master
{
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'amountDelivered,amountDeliveredVat';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'storeId, locationId, deliveryTime, lineId, contragentClassId, contragentId, weight, volume, folderId, note, addressInfo';
    
    
    /**
     * Поле в което се замества шаблона от doc_TplManager
     */
    public $templateFld = 'SINGLE_CONTENT';
    
    
    /**
     * Флаг, който указва, че документа е партньорски
     */
    public $visibleForPartners = true;
    
    
    /**
     * На кой ред в тулбара да се показва бутона за принтиране
     */
    public $printBtnToolbarRow = 1;
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array('template' => 'lastDocUser|lastDoc|lastDocSameCountry');
    
    
    /**
     * Флаг, който указва дали документа да се кешира в треда
     */
    public $cacheInThread = true;
    
    
    /**
     * Дата на очакване
     */
    public $termDateFld = 'deliveryTime';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Нужно ли е да има детайл, за да стане на 'Заявка'
     */
    public $requireDetailForPending = false;


    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'valior, amountDelivered, amountDeliveredVat, amountDiscount, deliveryTime,weight,volume,weightInput,volumeInput,lineId,additionalConditions,reverseContainerId,username';
    
    
    /**
     * Поле за забележките
     */
    public $notesFld = 'note';


    /**
     * Кое поле ще се оказва за подредбата на детайла
     */
    public $detailOrderByField = 'detailOrderBy';


    /**
     * Да се проверява ли избраната валута преди активиране
     */
    public $checkCurrencyWhenConto = true;


    /**
     * Работен кеш
     */
    protected static $logisticDataCache = array('cData' => array(), 'locationId' => array(), 'countryId' => array());


    /**
     * Поле за валутен курс
     */
    public $rateFldName = 'currencyRate';


    /**
     * След описанието на полетата
     */
    protected static function setDocFields(core_Master &$mvc)
    {
        $mvc->FLD('valior', 'date', 'caption=Дата');
        $mvc->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code,allowEmpty)', 'input=none,caption=Вал.,smartCenter');
        $mvc->FLD('currencyRate', 'double(decimals=5)', 'caption=Валута->Курс,input=hidden');
        $mvc->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=От склад, mandatory,silent,removeAndRefreshForm=deliveryTime');
        $mvc->FLD('chargeVat', 'enum(separate=Отделен ред за ДДС, yes=Включено ДДС в цените, exempt=Освободено от ДДС, no=Без начисляване на ДДС)', 'caption=ДДС,input=hidden');
        
        $mvc->FLD('amountDelivered', 'double(decimals=2)', 'caption=Общо,input=none,summary=amount,smartCenter'); // Сумата на доставената стока
        $mvc->FLD('amountDeliveredVat', 'double(decimals=2)', 'caption=ДДС,input=none,summary=amount,smartCenter');
        $mvc->FLD('amountDiscount', 'double(decimals=2)', 'input=none');
        $mvc->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
        $mvc->FLD('contragentId', 'int', 'input=hidden');
        $mvc->FLD('locationId', 'key(mvc=crm_Locations, select=title,allowEmpty)', 'caption=Обект до,silent');
        $mvc->FLD('deliveryTime', 'datetime');
        $mvc->FLD('lineId', 'key(mvc=trans_Lines,select=title,allowEmpty)', 'caption=Транспорт');
        $mvc->FLD('weight', 'cat_type_Weight', 'input=none,caption=Тегло');
        $mvc->FLD('volume', 'cat_type_Volume', 'input=none,caption=Обем');
        
        $mvc->FLD('detailOrderBy', 'enum(auto=Автоматично,creation=Ред на създаване,code=Код,reff=Ваш №)', 'caption=Артикули->Подреждане по,notNull,value=auto');
		$mvc->FLD('note', 'richtext(bucket=Notes,passage,rows=6)', 'caption=Допълнително->Бележки');
        $mvc->FLD('state', 'enum(draft=Чернова, active=Контиран, rejected=Оттеглен,stopped=Спряно, pending=Заявка)', 'caption=Статус, input=none');
        $mvc->FLD('isReverse', 'enum(no,yes)', 'input=none,notNull,value=no');
        $mvc->FLD('accountId', 'customKey(mvc=acc_Accounts,key=systemId,select=id)', 'input=none,notNull,value=411');

        $mvc->FLD('prevShipment', 'int', 'caption=Адрес за доставка->Избор,silent,removeAndRefreshForm=company|person|tel|country|pCode|place|address|features|addressInfo,placeholder=От предишна доставка,autohide');
        $mvc->FLD('company', 'varchar', 'caption=Адрес за доставка->Фирма,autohide');
        $mvc->FLD('person', 'varchar', 'caption=Адрес за доставка->Име, class=contactData,autohide');
        $mvc->FLD('tel', 'varchar', 'caption=Адрес за доставка->Тел., class=contactData,autohide');
        $mvc->FLD('country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Адрес за доставка->Държава, class=contactData,autohide');
        $mvc->FLD('pCode', 'varchar', 'caption=Адрес за доставка->П. код, class=contactData,autohide');
        $mvc->FLD('place', 'varchar', 'caption=Адрес за доставка->Град/с, class=contactData,autohide');
        $mvc->FLD('address', 'varchar', 'caption=Адрес за доставка->Адрес, class=contactData,autohide');
        $mvc->FLD('features', 'keylist(mvc=trans_Features,select=name)', 'caption=Адрес за доставка->Особености');
        $mvc->FLD('addressInfo', 'richtext(bucket=Notes, rows=2)', 'caption=Адрес за доставка->Други,autohide');
        $mvc->FLD('reverseContainerId', 'key(mvc=doc_Containers,select=id)', 'caption=Връщане от,input=hidden,silent');

        $mvc->setDbIndex('valior');
        $mvc->setDbIndex('contragentId,contragentClassId');
        $mvc->setDbIndex('modifiedOn');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (!deals_Helper::canSelectObjectInDocument($action, $rec, 'store_Stores', 'storeId')) {
            if(($action == 'reject' && $rec->state == 'pending') || ($action == 'restore' && $rec->brState == 'pending')) return;
            $requiredRoles = 'no_one';
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;

        $defaultStoreId = store_Stores::getCurrent('id', false);
        if(core_Packs::isInstalled('holding')){
            if(!holding_Companies::isAllowedValueInThread($rec->threadId, $defaultStoreId, 'stores')){
                $defaultStoreId = null;
            }
        }

        $form->setDefault('storeId', $defaultStoreId);
        $rec->contragentClassId = doc_Folders::fetchCoverClassId($rec->folderId);
        $rec->contragentId = doc_Folders::fetchCoverId($rec->folderId);
        if (!trans_Lines::count("#state = 'active'")) {
            $form->setField('lineId', 'input=none');
        }
        
        // Поле за избор на локация - само локациите на контрагента по продажбата
        $form->setOptions('locationId', array('' => '') + crm_Locations::getContragentOptions($rec->contragentClassId, $rec->contragentId));
        expect($origin = ($form->rec->originId) ? doc_Containers::getDocument($form->rec->originId) : doc_Threads::getFirstDocument($form->rec->threadId));
        expect($origin->haveInterface('bgerp_DealAggregatorIntf'));
        $dealInfo = $origin->getAggregateDealInfo();
        $form->dealInfo = $dealInfo;

        $form->setDefault('currencyId', $dealInfo->get('currency'));
        $form->setDefault('currencyRate', $dealInfo->get('rate'));
        if(empty($rec->id)){
            $form->setDefault('locationId', $dealInfo->get('deliveryLocation'));
        }
        $form->setDefault('chargeVat', $dealInfo->get('vatType'));
        $form->setDefault('storeId', $dealInfo->get('storeId'));

        // Вземаме другите ЕН, от същата папак
        $prevShipmentArr = array('' => '');
        if (isset($rec->folderId)) {
            $sQuery = $mvc->getQuery();
            $sQuery->where("#id != '{$rec->id}' AND #folderId = {$rec->folderId} AND #state != 'rejected' AND #state != 'draft'");
            $sQuery->orderBy('modifiedOn', 'DESC');
            $sQuery->limit(100);

            while ($sRec = $sQuery->fetch()) {
                if (!store_ShipmentOrders::haveRightFor('asClient', $sRec)) continue;
                if (!store_ShipmentOrders::haveRightFor('single', $sRec)) continue;

                $name = '#' . $mvc->getHandle($sRec->id);
                if ($sRec->company) {
                    $name .= ' - ' . $sRec->company;
                }
                if ($sRec->place) {
                    $name .= ' (' . $sRec->place . ')';
                }
                $prevShipmentArr[$sRec->id] = $name;
            }
        }

        if (countR($prevShipmentArr)) {
            $data->form->setOptions('prevShipment', $prevShipmentArr);
        } else {
            $data->form->setField('prevShipment', 'input=none');
        }
    }
    
    
    /**
     * След изпращане на формата
     */
    protected static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        if ($form->rec->prevShipment) {
            $prevRec = $mvc->fetch($form->rec->prevShipment);
            foreach (explode('|', $form->fields['prevShipment']->removeAndRefreshForm) as $fName) {
                $form->setDefault($fName, $prevRec->{$fName});
            }
        }

        if ($form->isSubmitted()) {
            $rec = &$form->rec;

            $valiorError = null;
            if(!deals_Helper::isValiorAllowed($rec->valior, $rec->threadId, $valiorError)) {
                $form->setError('valior', $valiorError);
            }

            // Ако валутата на документа не е разрешена ще се подмени след запис
            if($form->dealInfo->get('currency') == 'BGN'){
                $valiorBaseCurrencyId = acc_Periods::getBaseCurrencyCode($rec->valior);

                if($rec->currencyId != $valiorBaseCurrencyId){
                    if(isset($rec->id)){
                        $oldRec = $mvc->fetch($rec->id, 'valior,currencyRate', false);
                        $rec->_oldValior = $oldRec->valior ?? dt::verbal2mysql($rec->createdOn, false);
                        $rec->_oldRate = $oldRec->currencyRate;
                    }
                    $rec->currencyId = $valiorBaseCurrencyId;
                }
            } elseif($form->dealInfo->get('currency') == "EUR"){
                if(isset($rec->id)) {
                    $oldRec = $mvc->fetch($rec->id, 'valior,currencyRate', false);
                    if(acc_Periods::getBaseCurrencyCode($oldRec->valior) != acc_Periods::getBaseCurrencyCode($rec->valior)){
                        $rec->_oldValior = $oldRec->valior ?? dt::verbal2mysql($rec->createdOn, false);
                        $rec->_oldRate = $oldRec->currencyRate;
                    }
                }
            }

            $rec->_dealCurrencyId = $form->dealInfo->get('currency');
            if(in_array($form->dealInfo->get('currency'), array('EUR', 'BGN')) || (acc_Periods::getBaseCurrencyCode($rec->valior) != acc_Periods::getBaseCurrencyCode($form->dealInfo->get('agreedValior')))) {
                $rec->currencyRate = currency_CurrencyRates::getRate($rec->valior, $rec->currencyId, null);
            }

            // Ако има локация и тя е различна от договорената, слагаме предупреждение
            if (!empty($rec->locationId) && $form->dealInfo->get('deliveryLocation') && $rec->locationId != $form->dealInfo->get('deliveryLocation')) {
                $agreedLocation = crm_Locations::getTitleById($form->dealInfo->get('deliveryLocation'));
                $form->setWarning('locationId', "Избраната локация е различна от договорената \"{$agreedLocation}\"");
            }

            if (isset($rec->locationId)) {
                foreach (array('company','person','tel','country','pCode','place','address', 'features', 'addressInfo') as $del) {
                    if ($rec->{$del}) {
                        $form->setError("locationId,{$del}", 'Не може да има избрана локация и въведени адресни данни');
                        break;
                    }
                }
            }

            if ((!empty($rec->tel) || !empty($rec->country) || !empty($rec->pCode) || !empty($rec->place) || !empty($rec->address)) && (empty($rec->tel) || empty($rec->country) || empty($rec->pCode) || empty($rec->place) || empty($rec->address))) {
                $form->setError('tel,country,pCode,place,address', 'Трябва или да са попълнени всички полета за адрес или нито едно');
            }
        }
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
        
        $Detail = $this->mainDetail;
        $query = $this->{$Detail}->getQuery();
        $query->where("#{$this->{$Detail}->masterKey} = '{$rec->id}'");
        
        $recs = $query->fetchAll();
        
        deals_Helper::fillRecs($this, $recs, $rec);
        
        // ДДС-т е отделно amountDeal  е сумата без ддс + ддс-то, иначе самата сума си е с включено ддс
        $amount = ($rec->chargeVat == 'separate') ? $this->_total->amount + $this->_total->vat : $this->_total->amount;
        $amount -= $this->_total->discount;
        $rec->amountDelivered = $amount * $rec->currencyRate;
        $rec->amountDeliveredVat = $this->_total->vat * $rec->currencyRate;
        $rec->amountDiscount = $this->_total->discount * $rec->currencyRate;
        
        return $this->save($rec);
    }


    /**
     * След създаване на запис в модела
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        $origin = $mvc::getOrigin($rec);

        // Ако документа е клониран пропуска се
        if ($rec->_isClone === true) {
            
            return;
        }

        // Ако е към ориджин на КИ/ДИ да се налеят променените к-ва от него
        if(isset($rec->fromContainerId) && empty($rec->importProducts)){
            $fromDocument = doc_Containers::getDocument($rec->fromContainerId);

            if($fromDocument->isInstanceOf('deals_InvoiceMaster')){
                $invRec = $fromDocument->fetch();

                if($invRec->type == 'dc_note'){
                    $invDetail = cls::get($fromDocument->mainDetail);
                    $dQuery = $invDetail->getQuery();
                    $dQuery->where("#invoiceId = {$invRec->id}");
                    $details = $dQuery->fetchAll();
                    $invDetail::modifyDcDetails($details, $invRec, $invDetail);
                    $withChangedQuantityDetails = array_filter($details, function($a) {return $a->changedQuantity === true;});

                    $Detail = cls::get($mvc->mainDetail);
                    foreach ($withChangedQuantityDetails as $invDetailRec){
                        $shipProduct = new stdClass();
                        $shipProduct->{$Detail->masterKey} = $rec->id;
                        $shipProduct->productId = $invDetailRec->productId;
                        $shipProduct->packagingId = $invDetailRec->packagingId;
                        $shipProduct->quantity = abs($invDetailRec->quantity);
                        $shipProduct->price = $invDetailRec->price;
                        $shipProduct->discount = $invDetailRec->discount;
                        $shipProduct->quantityInPack = $invDetailRec->quantityInPack;
                        $Detail->save($shipProduct);
                    }

                    return;
                }
            }
        }

        // Ако новосъздадения документ има origin, който поддържа bgerp_AggregateDealIntf,
        // използваме го за автоматично попълване на детайлите на документа
        if ($origin->haveInterface('bgerp_DealAggregatorIntf')) {

            // Ако документа е обратен не слагаме продукти по дефолт
            if ($rec->isReverse == 'yes') {
                
                return;
            }
            
            $copyBatches = false;
            $Detail = $mvc->mainDetail;
            $aggregatedDealInfo = $origin->getAggregateDealInfo();
            $normalizedProducts = array();
            if($rec->importProducts == 'none'){
                $agreedProducts = array();
            } elseif($rec->importProducts != 'all') {
                $agreedProducts = $aggregatedDealInfo->get('products');
                $shippedProducts = $aggregatedDealInfo->get('shippedProducts');
                $copyBatches = true;
                if (countR($shippedProducts)) {

                    // Извличане на експедираните партиди от документи в нишката
                    if(in_array($rec->importProducts, array('notshipped', 'notshippedstorable'))){
                        $byNowShippedBatches = array();
                        if(core_Packs::isInstalled('batch')){
                            $mWhere = "";
                            $cQuery = doc_Containers::getQuery();
                            $cQuery->where("#threadId = {$rec->threadId} AND #state = 'active'");
                            $cQuery->show('docClass,docId');
                            while($cRec = $cQuery->fetch()){
                                $mWhere .= (!empty($mWhere) ? " OR " : '') . "(#docType = {$cRec->docClass} AND #docId = {$cRec->docId})";
                            }
                            $bQuery = batch_Movements::getQuery();
                            $bQuery->EXT('productId', 'batch_Items', 'externalName=productId,externalKey=itemId');
                            $bQuery->EXT('storeId', 'batch_Items', 'externalName=storeId,externalKey=itemId');
                            $bQuery->EXT('batch', 'batch_Items', 'externalName=batch,externalKey=itemId');
                            $bQuery->where("#storeId ={$rec->storeId}");
                            $bQuery->where($mWhere);
                            $bQuery->show('productId,quantity,batch');
                            while($bRec = $bQuery->fetch()){
                                $byNowShippedBatches[$bRec->productId][$bRec->batch] += $bRec->quantity;
                            }
                        }

                        foreach ($shippedProducts as $k1 => &$shipped){
                            // Ако има експедирани партиди досега и се искат неекспедираните приспадат се
                            if(isset($byNowShippedBatches[$shipped->productId])){
                                $shipped->batches = $byNowShippedBatches[$shipped->productId];
                            }
                        }
                    }


                    $normalizedProducts = deals_Helper::normalizeProducts(array($agreedProducts), array($shippedProducts));
                } else {
                    $agreedProducts = $aggregatedDealInfo->get('dealProducts');
                }

                if ($rec->importProducts == 'stocked') {
                    $originRec = $origin->fetch();
                    foreach ($agreedProducts as $i1 => $p1) {
                        $inStock = store_Products::fetchField("#storeId = {$rec->storeId} AND #productId = {$p1->productId}", 'quantity');
                        $inStock = is_numeric($inStock) ? $inStock : 0;
                        $agreedQuantity = $p1->quantity;
                        $isPublic = cat_Products::fetchField($p1->productId, 'isPublic');

                        // Ако договора е за многократна експедиция
                        if($originRec->oneTimeDelivery == 'no'){
                            if($isPublic == 'no'){

                                // и артикула е нестандартен с поне едно приключено задание, поръчаното к-во е това с толеранса
                                if(planning_Jobs::count("#productId = {$p1->productId} AND #state = 'closed' AND #dueDate >= '{$originRec->valior}'")){
                                    if(isset($p1->tolerance)){
                                        $agreedQuantity *= (1 + $p1->tolerance);
                                    }
                                }
                            }
                        } else {
                            if($isPublic == 'no'){
                                // Ако има активно/събудено/спряно задание няма да се води за налично
                                if(planning_Jobs::count("#productId = {$p1->productId} AND #state IN ('wakeup', 'active', 'stopped')")){
                                    $agreedQuantity = 0;
                                } else {
                                    // ако няма такова се взима до толеранса
                                    if(isset($p1->tolerance)){
                                        $agreedQuantity *= (1 + $p1->tolerance);
                                    }
                                }
                            }
                        }

                        // При всички положения ще се вземе по-малкото от наличното в склада и договореното
                        $p1->quantity = min($agreedQuantity, $inStock);

                        // Оставяне само на наличните партиди
                        if(is_array($p1->batches) && core_Packs::isInstalled('batch')){
                            $productBatchQuantitiesInStore = batch_Items::getBatchQuantitiesInStore($p1->productId,$rec->storeId, $rec->valior);
                            foreach ($p1->batches as $b => $q){
                                $batchQuantityInStore = !empty($productBatchQuantitiesInStore[$b]) ? $productBatchQuantitiesInStore[$b] : 0;
                                if ($q > $batchQuantityInStore) {
                                    unset($p1->batches[$b]);
                                }
                            }
                        }
                    }
                }
            } else {
                $agreedProducts = $aggregatedDealInfo->get('dealProducts');
                $normalizedProducts = $aggregatedDealInfo->get('dealProducts');
                $copyBatches = true;
            }

            if (countR($agreedProducts)) {
                foreach ($agreedProducts as $index => $product) {
                    
                    // Игнориране на услуги или складируемите ако е избрано друго
                    if (isset($rec->importProducts) && in_array($rec->importProducts, array('notshippedstorable', 'notshippedservices', 'services'))) {
                        $canStore = cat_Products::fetchField($product->productId, 'canStore');
                        $skipIfNot = ($rec->importProducts == 'notshippedstorable') ? 'yes' : 'no';
                        if ($canStore != $skipIfNot) {
                            continue;
                        }
                    }
                    
                    if (isset($normalizedProducts[$index])) {
                        $toShip = $normalizedProducts[$index]->quantity;
                        $batches = $normalizedProducts[$index]->batches;
                    } else {
                        $toShip = $product->quantity;
                        $batches = $product->batches;
                    }
                    
                    $price = (isset($product->price)) ? $product->price : $normalizedProducts[$index]->price;
                    $discount = ($product->discount) ? $product->discount : $normalizedProducts[$index]->discount;

                    // Пропускат се експедираните продукти
                    if ($toShip <= 0) continue;
                    $shipProduct = new stdClass();
                    $shipProduct->{$mvc->{$Detail}->masterKey} = $rec->id;
                    $shipProduct->productId = $product->productId;
                    $shipProduct->packagingId = $product->packagingId;
                    $shipProduct->quantity = $toShip;
                    $shipProduct->price = $price;
                    if($aggregatedDealInfo->get('currency') == 'BGN') {
                        $shipProduct->price = deals_Helper::getSmartBaseCurrency($shipProduct->price, $aggregatedDealInfo->get('agreedValior'), $rec->valior);
                    } else {
                        $shipProduct->price = $shipProduct->price / $aggregatedDealInfo->get('rate') * $rec->currencyRate;
                    }
                    $shipProduct->discount = $discount;
                    $shipProduct->notes = $product->notes;
                    $shipProduct->quantityInPack = $product->quantityInPack;

                    if (core_Packs::isInstalled('batch') && $copyBatches === true) {
                        $shipProduct->batches = $batches;
                        $shipProduct->isEdited = FALSE;
                        $shipProduct->_clonedWithBatches = TRUE;
                    }

                    $Detail::save($shipProduct);

                    // Копира партидата ако артикулите идат 1 към 1 от договора
                    if (core_Packs::isInstalled('batch') && $copyBatches === true) {
                        if (is_array($shipProduct->batches)) {

                            // Ако има генерирана нова партида и има без партида оставено от договора - ще се зададе за остатъка
                            if(!empty($shipProduct->batch)){
                                if(!countR($shipProduct->batches)){
                                    $shipProduct->batches =  array($shipProduct->batch => $shipProduct->quantity);
                                } else {
                                    $quantityInBatches = array_sum($shipProduct->batches);
                                    $diff = round($shipProduct->quantity - $quantityInBatches, 5);
                                    if($diff){
                                        $shipProduct->batches += array($shipProduct->batch => $diff);
                                    }
                                }
                            }

                            foreach ($shipProduct->batches as $b => $q) {
                                $bRec = new stdClass();
                                $bRec->batch = $b;
                                $bRec->quantity = $q;
                                $bRec->productId = $product->productId;
                                $bRec->detailClassId = $mvc->{$Detail}->getClassId();
                                $bRec->detailRecId = $shipProduct->id;
                                $bRec->containerId = $rec->containerId;
                                $bRec->date = $rec->valior;
                                $bRec->storeId = $rec->storeId;
                                $bRec->operation = $mvc->{$Detail}->getBatchMovementDocument($shipProduct);
                                $bRec->packagingId = $shipProduct->packagingId;
                                $bRec->quantityInPack = $shipProduct->quantityInPack;
                                batch_BatchesInDocuments::save($bRec);
                            }
                        }
                    }
                }
            }
        }
    }
    
    
    /**
     * След рендиране на сингъла
     */
    protected static function on_AfterRenderSingle($mvc, $tpl, $data)
    {
        if (Mode::is('printing') || Mode::is('text', 'xhtml')) {
            $tpl->removeBlock('header');
        }

        if ($data->rec->country) {
            $deliveryAddress = "{$data->row->country} <br/> {$data->row->pCode} {$data->row->place} <br /> {$data->row->address}";
            $inlineDeliveryAddress = "{$data->row->country},  {$data->row->pCode} {$data->row->place}, {$data->row->address}";
            $tpl->replace($inlineDeliveryAddress, 'inlineDeliveryAddress');
        } else {
            $deliveryAddress = $data->row->contragentAddress;
        }

        core_Lg::push($data->rec->tplLang);
        $deliveryAddress = core_Lg::transliterate($deliveryAddress);
        $tpl->replace($deliveryAddress, 'deliveryAddress');
        core_Lg::pop();
    }
    
    
    /**
     * Подготвя данните (в обекта $data) необходими за единичния изглед
     */
    public function prepareSingle_($data)
    {
        parent::prepareSingle_($data);
        
        $rec = &$data->rec;
        if (empty($data->noTotal)) {
            $data->summary = deals_Helper::prepareSummary($this->_total, $rec->valior, $rec->currencyRate, $rec->currencyId, $rec->chargeVat, false, $rec->tplLang);
            $data->row = (object) ((array) $data->row + (array) $data->summary);
        }  elseif(!doc_plg_HidePrices::canSeePriceFields($this, $rec)) {
            $data->row->value = doc_plg_HidePrices::getBuriedElement();
            $data->row->total = doc_plg_HidePrices::getBuriedElement();
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (!empty($rec->currencyRate)) {
            $amountDelivered = $rec->amountDelivered / $rec->currencyRate;
        } else {
            $amountDelivered = $rec->amountDelivered;
        }
        
        $row->amountDelivered = $mvc->getFieldType('amountDelivered')->toVerbal($amountDelivered);
        
        if (isset($fields['-list'])) {
            if ($rec->amountDelivered) {
                $row->amountDelivered = "{$row->amountDelivered}";
            } else {
                $row->amountDelivered = "<span class='quiet'>0.00</span>";
            }
            
            $row->title = $mvc->getLink($rec->id, 0);
        }
        
        if (isset($fields['-single'])) {
            core_Lg::push($rec->tplLang);

            $row->reff = deals_Helper::getYourReffInThread($rec->threadId);
            $headerInfo = deals_Helper::getDocumentHeaderInfo($rec->containerId, $rec->contragentClassId, $rec->contragentId);
            $row = (object) ((array) $row + $headerInfo);

            $addressData = array();
            if ($rec->locationId) {
                $row->locationId = crm_Locations::getHyperlink($rec->locationId);
                if ($ourLocation = store_Stores::fetchField($rec->storeId, 'locationId')) {
                    $row->ourLocation = crm_Locations::getTitleById($ourLocation);
                    $ourLocationAddress = crm_Locations::getAddress($ourLocation);
                    if ($ourLocationAddress != '') {
                        $row->ourLocationAddress = $ourLocationAddress;
                    }
                }

                $Location = cls::get('crm_Locations');
                $locationRec = $Location->fetch($rec->locationId);
                $locationRow = $Location->recToVerbal($locationRec, $Location->selectFields());
                foreach (array('countryId' => 'deliveryCountry', 'pCode' => 'deliverypCode', 'place' => 'deliveryPlace', 'address' => 'deliveryAddress', 'gln' => 'deliveryGln', 'countryId' => 'deliveryCountry', 'tel' => 'deliveryTel', 'mol' => 'deliveryPerson', 'features' => 'features', 'specifics' => 'addressInfo') as $recFld => $recPlaceholder){
                    if (!empty($locationRec->{$recFld})) {
                        if($recFld == 'features'){
                            $addressData[$recPlaceholder] = $locationRec->{$recFld};
                        } else {
                            $addressData[$recPlaceholder] = $locationRow->{$recFld};
                        }
                    }
                }
            } else {
                foreach (array('address' => 'deliveryAddress', 'company' => 'deliveryCompany', 'country' => 'deliveryCountry', 'person' => 'deliveryPerson', 'tel' => 'deliveryTel', 'pCode' => 'deliverypCode', 'place' => 'deliveryPlace', 'features' => 'features', 'addressInfo' => 'addressInfo') as $recFld => $recPlaceholder){
                    if (!empty($rec->{$recFld})) {
                        if($recFld == 'features'){
                            $addressData[$recPlaceholder] = $rec->{$recFld};
                        } else {
                            $addressData[$recPlaceholder] = $row->{$recFld};
                        }
                    }
                }
            }

            // Скриваме "Особености" на локацията ПРИ ПЕЧАТ И ИЗПРАЩАНЕ
            // ВИНАГИ когато ЕН е включено в Транспортна линия и в нея "Изпълнител" НЕ Е "Моята фирма"
            if (Mode::is('text', 'xhtml') || Mode::is('printing')) {
                if($rec->{$mvc->lineFieldName}){
                    $forwarderId = trans_Lines::fetchField($rec->{$mvc->lineFieldName}, 'forwarderId');
                    if(isset($forwarderId) && $forwarderId != crm_Setup::BGERP_OWN_COMPANY_ID){
                        unset($addressData['features']);
                    }
                }
            }

            if(countR($addressData)){
                foreach ($addressData as $delKey => $delVal){
                    if (in_array($delKey, array('deliveryAddress', 'deliveryPlace', 'deliveryPerson'))) {
                        $addressData[$delKey] = core_Lg::transliterate($delVal);
                    } elseif($delKey == 'features'){
                        $addressData[$delKey] = trans_Features::getVerbalFeatures($delVal);
                    } elseif($delKey == 'tel') {
                        if (callcenter_Talks::haveRightFor('list')) {
                            $addressData[$delKey] = ht::createLink($delVal, array('callcenter_Talks', 'list', 'number' => $rec->{$fld}));
                        }
                    }
                }

                $addressBlock = getTplFromFile('store/tpl/DeliveryAddressTpl.shtml');
                $addressBlock->placeArray($addressData);
                $row->deliveryTo = $addressBlock->getContent();
            }

            $row->storeId = store_Stores::getHyperlink($rec->storeId);
            core_Lg::pop();

        } elseif (isset($fields['-list'])) {
            if (doc_Setup::get('LIST_FIELDS_EXTRA_LINE') != 'no') {
                $row->title = '<b>' . $row->title . '</b>';
                $row->title .= '  «  ' . $row->folderId;
                $row->createdBy = crm_Profiles::createLink($rec->createdBy);
                $row->title .= "<span class='fright'>" . $row->createdOn . ' ' . tr('от') . ' ' .   $row->createdBy . '</span>';
            }
        }
    }
    
    
    /**
     * Документът не може да бъде начало на нишка; може да се създава само в съществуващи нишки
     */
    public static function canAddToFolder($folderId)
    {
        return false;
    }
    
    
    /**
     * Може ли документа да се добави в посочената нишка?
     *
     * @param int $threadId key(mvc=doc_Threads)
     *
     * @return bool
     */
    public static function canAddToThread($threadId)
    {
        $firstDoc = doc_Threads::getFirstDocument($threadId);
        $docState = $firstDoc->fetchField('state');
        
        // Може да се добавя само към активиран документ
        if ($docState == 'active') {
            if ($firstDoc->haveInterface('bgerp_DealAggregatorIntf')) {
                $operations = $firstDoc->getShipmentOperations();
                
                return isset($operations[static::$defOperationSysId]);
            }
        }
        
        return false;
    }
    
    
    /**
     * Връща масив от използваните нестандартни артикули в документа
     *
     * @param int $id - ид на документа
     *
     * @return array $res - масив с използваните документи
     *               ['class'] - инстанция на документа
     *               ['id'] - ид на документа
     */
    public function getUsedDocs_($id)
    {
        return deals_Helper::getUsedDocs($this, $id);
    }
    
    
    /**
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow_($id)
    {
        expect($rec = $this->fetch($id));
        $title = $this->getRecTitle($rec);
        
        $row = (object) array(
            'title' => $title,
            'authorId' => $rec->createdBy,
            'author' => $this->getVerbal($rec, 'createdBy'),
            'state' => $rec->state,
            'recTitle' => $title
        );
        
        return $row;
    }
    
    
    /**
     * Променяме шаблона в зависимост от мода
     */
    protected static function on_BeforeRenderSingleLayout($mvc, &$tpl, $data)
    {
        if (Mode::is('renderHtmlInLine') && isset($mvc->layoutFileInLine)) {
            $data->singleLayout = getTplFromFile($mvc->layoutFileInLine);
            unset($data->_selectTplForm);
        }
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        $res = '';
        $this->setTemplates($res);
        
        return $res;
    }
    
    
    /**
     * Имплементация на @link bgerp_DealIntf::getDealInfo()
     *
     * @param int|object $id
     *
     * @return void
     *
     * @see bgerp_DealIntf::getDealInfo()
     */
    public function pushDealInfo($id, &$aggregator)
    {
        $rec = $this->fetchRec($id);

        // Конвертираме данъчната основа към валутата идваща от продажбата
        if(isset($rec->locationId)){
            $aggregator->setIfNot('deliveryLocation', $rec->locationId);
        }

        $aggregator->setIfNot('storeId', $rec->storeId);
        $aggregator->setIfNot('shippedValior', $rec->valior);
        
        $Detail = $this->mainDetail;
        $dQuery = $this->{$Detail}->getQuery();
        $dQuery->where("#{$this->{$Detail}->masterKey} = {$rec->id}");
        
        // Подаваме на интерфейса най-малката опаковка с която е експедиран продукта
        while ($dRec = $dQuery->fetch()) {
            
            // Подаваме най-малката опаковка в която е експедиран продукта
            $push = true;
            $index = $dRec->productId;
            $shipped = $aggregator->get('shippedPacks');
            if ($shipped && isset($shipped[$index])) {
                if ($shipped[$index]->inPack < $dRec->quantityInPack) {
                    $push = false;
                }
            }
            
            // Ако ще обновяваме информацията за опаковката
            if ($push) {
                $arr = (object) array('packagingId' => $dRec->packagingId, 'inPack' => $dRec->quantityInPack);
                $aggregator->push('shippedPacks', $arr, $index);
            }

            $vatExceptionId = cond_VatExceptions::getFromThreadId($rec->threadId);
            $vat = cat_Products::getVat($dRec->productId, $rec->valior, $vatExceptionId);
            if ($rec->chargeVat == 'yes' || $rec->chargeVat == 'separate') {
                $dRec->packPrice += $dRec->packPrice * $vat;
            }
            
            $aggregator->pushToArray('productVatPrices', $dRec->packPrice, $index);
        }
    }
    
    
    /**
     * Преди запис на документ
     */
    protected static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
        if (empty($rec->originId)) {
            $rec->originId = doc_Threads::getFirstContainerId($rec->threadId);
        }

        // Ако оригиналния документ е закачен към ТЛ, закача се и този
        if((empty($rec->id) || $rec->_replaceReverseContainerId) && isset($rec->reverseContainerId)){
            $Doc = doc_Containers::getDocument($rec->reverseContainerId);

            if(isset($Doc->lineFieldName)){
                $docRec = $Doc->fetch("{$Doc->lineFieldName},deliveryTime,valior");

                if($docRec->{$Doc->lineFieldName}){
                    $lineStart = trans_Lines::fetchField($docRec->{$Doc->lineFieldName}, 'start');
                    $deliveryTime = !empty($rec->deliveryTime) ? $rec->deliveryTime : (($rec->valior) ? $rec->valior : dt::today());
                    $deliveryTime = (strlen($deliveryTime) == 10) ? "{$deliveryTime} 23:59:59" : $deliveryTime;
                    $docDeliveryTime = !empty($docRec->deliveryTime) ? $docRec->deliveryTime : (($docRec->valior) ? $docRec->valior : dt::today());
                    $docDeliveryTime = (strlen($docDeliveryTime) == 10) ? "{$docDeliveryTime} 23:59:59" : $docDeliveryTime;

                    // ако датата на документа за връщане е по-голяма или равна от тази на оригиналния документ
                    // и по-малка или равна от тази на ТЛ
                    if($deliveryTime >= $docDeliveryTime && $deliveryTime <= $lineStart){
                        $rec->{$mvc->lineFieldName} = $docRec->{$Doc->lineFieldName};
                        $rec->_changeLine = true;
                    }
                }
            }
        }
    }


    /**
     * Информация за логистичните данни
     *
     * @param mixed $rec - ид или запис на документ
     * @return array      - логистичните данни
     *
     *		string(2)     ['fromCountry']         - международното име на английски на държавата за натоварване
     * 		string|NULL   ['fromPCode']           - пощенски код на мястото за натоварване
     * 		string|NULL   ['fromPlace']           - град за натоварване
     * 		string|NULL   ['fromAddress']         - адрес за натоварване
     *  	string|NULL   ['fromCompany']         - фирма
     *   	string|NULL   ['fromPerson']          - лице
     *      string|NULL   ['fromPersonPhones']    - телефон на лицето
     *      string|NULL   ['fromLocationId']      - лице
     *      string|NULL   ['fromAddressInfo']     - особености
     *      string|NULL   ['fromAddressFeatures'] - особености на транспорта
     * 		datetime|NULL ['loadingTime']         - дата на натоварване
     * 		string(2)     ['toCountry']           - международното име на английски на държавата за разтоварване
     * 		string|NULL   ['toPCode']             - пощенски код на мястото за разтоварване
     * 		string|NULL   ['toPlace']             - град за разтоварване
     *  	string|NULL   ['toAddress']           - адрес за разтоварване
     *   	string|NULL   ['toCompany']           - фирма
     *   	string|NULL   ['toPerson']            - лице
     *      string|NULL   ['toLocationId']        - лице
     *      string|NULL   ['toPersonPhones']      - телефон на лицето
     *      string|NULL   ['toAddressInfo']       - особености
     *      string|NULL   ['toAddressFeatures']   - особености на транспорта
     *      string|NULL   ['instructions']        - инструкции
     * 		datetime|NULL ['deliveryTime']        - дата на разтоварване
     * 		text|NULL 	  ['conditions']          - други условия
     *		varchar|NULL  ['ourReff']             - наш реф
     * 		double|NULL   ['totalWeight']         - общо тегло
     * 		double|NULL   ['totalVolume']         - общ обем
     */
    public function getLogisticData($rec)
    {
        $rec = $this->fetchRec($rec);
        $ownCompanyId = core_Packs::isInstalled('holding') ? holding_plg_DealDocument::getOwnCompanyIdFromThread($rec) : crm_Setup::BGERP_OWN_COMPANY_ID;
        $ownCompany = crm_Companies::fetch($ownCompanyId);
        $ownCountryId = $ownCompany->country;

        if ($locationId = store_Stores::fetchField($rec->storeId, 'locationId')) {
            if(!array_key_exists($locationId, static::$logisticDataCache['locationId'])){
                static::$logisticDataCache['locationId'][$locationId] = crm_Locations::fetch($locationId);
            }
            $storeLocation = static::$logisticDataCache['locationId'][$locationId];
            $ownCountryId = $storeLocation->countryId;
        }

        if(!array_key_exists($rec->folderId, static::$logisticDataCache['cData'])){
            static::$logisticDataCache['cData'][$rec->folderId] = doc_Folders::getContragentData($rec->folderId);
        }
        $contragentData = static::$logisticDataCache['cData'][$rec->folderId];
        $contragentCountryId = $contragentData->countryId;
        
        if (isset($rec->locationId)) {
            $contragentLocation = crm_Locations::fetch($rec->locationId);
            $contragentCountryId = $contragentLocation->countryId;
        }
        
        $ownPart = ($this instanceof store_ShipmentOrders) ? 'from' : 'to';
        $contrPart = ($this instanceof store_ShipmentOrders) ? 'to' : 'from';
        
        // Подготвяне на данните за разтоварване
        $res = array();
        if(!array_key_exists($ownCountryId, static::$logisticDataCache['countryId'])){
            static::$logisticDataCache['countryId'][$ownCountryId] = drdata_Countries::fetchField($ownCountryId, 'commonName');
        }

        $res["{$ownPart}Country"] = static::$logisticDataCache['countryId'][$ownCountryId];
        
        if (isset($storeLocation)) {
            $res["{$ownPart}PCode"] = !empty($storeLocation->pCode) ? $storeLocation->pCode : null;
            $res["{$ownPart}Place"] = !empty($storeLocation->place) ? $storeLocation->place : null;
            $res["{$ownPart}Address"] = !empty($storeLocation->address) ? $storeLocation->address : null;
            $res["{$ownPart}Person"] = !empty($storeLocation->mol) ? $storeLocation->mol : null;
            $res["{$ownPart}LocationId"] = $storeLocation->id;
            $res["{$ownPart}AddressInfo"] = $storeLocation->specifics;
            $res["{$ownPart}AddressFeatures"] = $storeLocation->features;
        } else {
            $res["{$ownPart}PCode"] = !empty($ownCompany->pCode) ? $ownCompany->pCode : null;
            $res["{$ownPart}Place"] = !empty($ownCompany->place) ? $ownCompany->place : null;
            $res["{$ownPart}Address"] = !empty($ownCompany->address) ? $ownCompany->address : null;
        }

        if(!Mode::is('calcOnlyDeliveryPart')){
            $res["{$ownPart}Company"] = $ownCompany->name;
            $toPersonId = ($rec->activatedBy) ? $rec->activatedBy : $rec->createdBy;
            $res["{$ownPart}Person"] = ($res["{$ownPart}Person"]) ? $res["{$ownPart}Person"] : core_Users::fetchField($toPersonId, 'names');

            if($res["{$ownPart}Person"]){
                $personId = crm_Profiles::getPersonByUser($toPersonId);
                if(isset($personId)){
                    $buzPhones = crm_Persons::fetchField($personId, 'buzTel');
                    if(!empty($buzPhones)){
                        $res["{$ownPart}PersonPhones"] = $buzPhones;
                    }
                }
            }
        }

        // Подготвяне на данните за натоварване
        $res["{$contrPart}Country"] = drdata_Countries::fetchField($contragentCountryId, 'commonName');
        $res["{$contrPart}Company"] = $contragentData->company;
        $res["{$contrPart}PCode"] = !empty($contragentData->pCode) ? $contragentData->pCode : null;
        $res["{$contrPart}Place"] = !empty($contragentData->place) ? $contragentData->place : null;
        $res["{$contrPart}Address"] = !empty($contragentData->address) ? $contragentData->address : null;

        // Данните за разтоварване от ЕН-то са с приоритет
        if (!empty($rec->country) || !empty($rec->pCode) || !empty($rec->place) || !empty($rec->address)) {

            $res["{$contrPart}Country"] = !empty($rec->country) ? drdata_Countries::fetchField($rec->country, 'commonName') : null;
            $res["{$contrPart}PCode"] = !empty($rec->pCode) ? $rec->pCode : null;
            $res["{$contrPart}Place"] = !empty($rec->place) ? $rec->place : null;
            $res["{$contrPart}Address"] = !empty($rec->address) ? $rec->address : null;
            $res["{$contrPart}Company"] = !empty($rec->company) ? $rec->company : $contragentData->company;
            $res["{$contrPart}Person"] = !empty($rec->person) ? $rec->person : $contragentData->person;
            $res["{$contrPart}AddressInfo"] = !empty($rec->addressInfo) ? $rec->addressInfo : null;
            $res["{$contrPart}PersonPhones"] = !empty($rec->tel) ? $rec->tel : null;
            $res["{$contrPart}AddressFeatures"] = !empty($rec->features) ? $rec->features : null;
        } elseif (isset($rec->locationId)) {
            $res["{$contrPart}Country"] = !empty($contragentLocation->countryId) ? drdata_Countries::fetchField($contragentLocation->countryId, 'commonName') : null;
            $res["{$contrPart}PCode"] = !empty($contragentLocation->pCode) ? $contragentLocation->pCode : null;
            $res["{$contrPart}Place"] = !empty($contragentLocation->place) ? $contragentLocation->place : null;
            $res["{$contrPart}Address"] = !empty($contragentLocation->address) ? $contragentLocation->address : null;
            $res["{$contrPart}Person"] = !empty($contragentLocation->mol) ? $contragentLocation->mol : null;
            $res["{$contrPart}PersonPhones"] = !empty($contragentLocation->tel) ? $contragentLocation->tel : null;
            $res["{$contrPart}LocationId"] = $contragentLocation->id;
            $res["{$contrPart}AddressInfo"] = $contragentLocation->specifics;
            $res["{$contrPart}AddressFeatures"] = $contragentLocation->features;

        } elseif($rec->isReverse == 'no') {
            if ($firstDocument = doc_Threads::getFirstDocument($rec->threadId)) {
                if($firstDocument->haveInterface('trans_LogisticDataIntf')){
                    if(!$firstDocument->fetchField('deliveryLocationId')){
                        $firstDocumentLogisticData = $firstDocument->getLogisticData();
                        $res["{$contrPart}Country"] = $firstDocumentLogisticData["{$contrPart}Country"];
                        $res["{$contrPart}PCode"] = $firstDocumentLogisticData["{$contrPart}PCode"];
                        $res["{$contrPart}Place"] = $firstDocumentLogisticData["{$contrPart}Place"];
                        $res["{$contrPart}Address"] = $firstDocumentLogisticData["{$contrPart}Address"];
                        $res['instructions'] = $firstDocumentLogisticData['instructions'];
                        $res["{$contrPart}Company"] = $firstDocumentLogisticData["{$contrPart}Company"];
                        $res["{$contrPart}Person"] = $firstDocumentLogisticData["{$contrPart}Person"];
                        $res["{$contrPart}PersonPhones"] = $firstDocumentLogisticData["{$contrPart}PersonPhones"];
                        $res["{$contrPart}LocationId"] = $firstDocumentLogisticData["{$contrPart}LocationId"];
                        $res["{$contrPart}AddressInfo"] = $firstDocumentLogisticData["{$contrPart}AddressInfo"];
                        $res["{$contrPart}AddressFeatures"] = $firstDocumentLogisticData["{$contrPart}AddressFeatures"];
                    }
                }
            }
        }
        
        $res['deliveryTime'] = (!empty($rec->deliveryTime)) ? $rec->deliveryTime : ($rec->valior . ' ' . bgerp_Setup::get('START_OF_WORKING_DAY'));

        if(!Mode::is('calcOnlyDeliveryPart')){
            $res['ourReff'] = '#' . $this->getHandle($rec);
            $totalInfo = $this->getTotalTransportInfo($rec);
            $res['totalWeight'] = isset($rec->weightInput) ? $rec->weightInput : $totalInfo->weight;
            $res['totalVolume'] = isset($rec->volumeInput) ? $rec->volumeInput : $totalInfo->volume;
        }

        if(!empty($rec->addressInfo)){
            $res["{$contrPart}AddressInfo"] = $rec->addressInfo;
        }

        return $res;
    }


    /**
     * Артикули които да се заредят във фактурата/проформата, когато е създадена от
     * определен документ
     *
     * @param mixed               $id     - ид или запис на документа
     * @param deals_InvoiceMaster $forMvc - клас наследник на deals_InvoiceMaster в който ще наливаме детайлите
     * @param string $strategy - стратегия за намиране
     *
     * @return array $details - масив с артикули готови за запис
     *               o productId      - ид на артикул
     *               o packagingId    - ид на опаковка/основна мярка
     *               o quantity       - количество опаковка
     *               o quantityInPack - количество в опаковката
     *               o discount       - отстъпка
     *               o price          - цена за единица от основната мярка
     *               o rate           - курса на документа
     */
    public function getDetailsFromSource($id, deals_InvoiceMaster $forMvc, $strategy)
    {
        $details = array();
        $rec = static::fetchRec($id);
        
        $Detail = cls::get($this->mainDetail);
        $query = $Detail->getQuery();
        $query->where("#{$Detail->masterKey} = {$rec->id}");
        
        while ($dRec = $query->fetch()) {
            if(empty($dRec->quantity)) continue;
            
            $dRec->quantity /= $dRec->quantityInPack;
            if (!($forMvc instanceof sales_Proformas)) {
                $dRec->price -= $dRec->price * $dRec->discount;
                unset($dRec->discount);
            }
            unset($dRec->id);
            unset($dRec->shipmentId);
            unset($dRec->createdOn);
            unset($dRec->createdBy);
            $dRec->rate = $rec->currencyRate;
            $details[] = $dRec;
        }
        
        return $details;
    }


    /**
     * Информацията на документа, за показване в транспортната линия
     *
     * @param mixed $id
     * @param int $lineId
     *
     * @return array
     *               ['baseAmount']     double|NULL - сумата за инкасиране във базова валута
     *               ['amount']         double|NULL - сумата за инкасиране във валутата на документа
     *               ['amountVerbal']   double|NULL - сумата за инкасиране във валутата на документа
     *               ['currencyId']     string|NULL - валутата на документа
     *               ['notes']          string|NULL - забележки за транспортната линия
     *               ['stores']         array       - склад(ове) в документа
     *               ['cases']          array       - каси в документа
     *               ['zoneId']         array       - ид на зона, в която е нагласен документа
     *               ['zoneReadiness']  int         - готовност в зоната в която е нагласен документа
     *               ['weight']         double|NULL - общо тегло на стоките в документа
     *               ['volume']         double|NULL - общ обем на стоките в документа
     *               ['transportUnits'] array       - използваните ЛЕ в документа, в формата ле -> к-во
     *               ['contragentName'] double|NULL - име на контрагента
     *               ['address']        double|NULL - адрес ба диставка
     *               ['storeMovement']  string|NULL - посока на движението на склада
     *               ['locationId']     string|NULL - ид на локация на доставка (ако има)
     *               ['addressInfo']    string|NULL - информация за адреса
     *               ['countryId']      string|NULL - ид на държава
     *               ['place']          string|NULL - населено място
     *               ['features']       array       - свойства на адреса
     *               ['deliveryOn']     date        - Доставка на
     */
    public function getTransportLineInfo_($rec, $lineId)
    {
        $rec = static::fetchRec($rec);
        $res = array('baseAmount' => null, 'amount' => null, 'currencyId' => null, 'notes' => $rec->lineNotes, 'deliveryOn' => $rec->deliveryOn);
        $res['stores'] = array($rec->storeId);
        $res['storeMovement'] = ($this instanceof store_Receipts) ? (($rec->isReverse == 'yes') ? 'out' : 'in') : (($rec->isReverse == 'yes') ? 'in' : 'out');
        $res['cases'] = array();

        $contragentClass = cls::get($rec->contragentClassId);
        $contragentRec = $contragentClass->fetch($rec->contragentId);
        $contragentTitle = $contragentClass->getVerbal($contragentRec, 'name');
        $res['contragentName'] = $contragentTitle;

        $address = '';
        $part = ($this instanceof store_ShipmentOrders) ? 'to' : 'from';
        $logisticData = $this->getLogisticData($rec);

        $countryId = drdata_Countries::getIdByName($logisticData["{$part}Country"]);
        $res['countryId'] .= $countryId;

        if($logisticData['fromCountry'] != $logisticData['toCountry']){
            $this->pushTemplateLg($rec->template);
            $address .= drdata_Countries::getTitleById($countryId) . " ";
            core_Lg::pop();
        }

        $res['address'] = "{$address}{$logisticData["{$part}PCode"]} {$logisticData["{$part}Place"]}, {$logisticData["{$part}Address"]}";
        if(!empty($logisticData["{$part}AddressFeatures"])){
            $res['features'] = keylist::toArray($logisticData["{$part}AddressFeatures"]);
        }

        if(!empty($logisticData["{$part}Person"])){
            $res['address'] .= ", {$logisticData["{$part}Person"]}";
        }
        if(!empty($logisticData["{$part}PersonPhones"])){
            $res['address'] .= " {$logisticData["{$part}PersonPhones"]}";
        }

        if(!empty($logisticData["{$part}LocationId"])){
            $res['locationId'] .= $logisticData["{$part}LocationId"];
        }

        if(!empty($logisticData["{$part}Place"])){
            $res['place'] = $logisticData["{$part}Place"];
        }

        $amount = null;
        $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
        if ($firstDoc->getInstance()->getField('#paymentMethodId', false)) {
            $paymentMethodId = $firstDoc->fetchField('paymentMethodId');
            if (cond_PaymentMethods::isCOD($paymentMethodId)) {
                $amount = currency_Currencies::round($rec->amountDelivered / $rec->currencyRate, $rec->currencyId);
            }
        }
       
        if ($amount) {
            $res['baseAmount'] = currency_Currencies::round($rec->amountDelivered, $rec->currencyId);
            $res['amount'] = $amount;
            $res['currencyId'] = $rec->currencyId;

            $sign = ($rec->isReverse != 'yes') ? 1 : -1;
            $amount = $sign * $res['amount'];
            $amountVerbal = core_type::getByName('double(decimals=2)')->toVerbal($amount);
            $amountVerbal = ht::styleNumber($amountVerbal, $res['amount']);
            Mode::push('text', 'plain');
            $res['amountVerbal'] = currency_Currencies::decorate($amountVerbal, $rec->currencyId);
            Mode::pop('text');
        }

        if(!empty($logisticData["{$part}AddressInfo"])){
            $res['addressInfo'] = $logisticData["{$part}AddressInfo"];
        }

        return $res;
    }
    
    
    /**
     * Добавя нов ред в главния детайл на чернова сделка.
     * Ако има вече такъв артикул добавен към сделката, наслагва к-то, цената и отстъпката
     * на новия запис към съществуващия (цените и отстъпките стават по средно притеглени)
     *
     * @param int    $id           - ид на сделка
     * @param int    $productId    - ид на артикул
     * @param float  $packQuantity - количество продадени опаковки (ако няма опаковки е цялото количество)
     * @param float  $price        - цена на единична бройка (ако не е подадена, определя се от политиката)
     * @param int    $packagingId  - ид на опаковка (не е задължителна)
     * @param float  $discount     - отстъпка между 0(0%) и 1(100%) (не е задължителна)
     * @param float  $tolerance    - толеранс между 0(0%) и 1(100%) (не е задължителен)
     * @param string $term         - срок (не е задължителен)
     * @param string $notes        - забележки
     * @param string $batch        - партида
     *
     * @return mixed $id/FALSE     - ид на запис или FALSE
     */
    public static function addRow($id, $productId, $packQuantity, $price = null, $packagingId = null, $discount = null, $tolerance = null, $term = null, $notes = null, $batch)
    {
        $me = cls::get(get_called_class());
        $Detail = cls::get($me->mainDetail);
        
        expect($rec = $me->fetch($id));
        expect($rec->state == 'draft');
        
        // Дали отстъпката е между 0 и 1
        if (isset($discount)) {
            expect($discount >= 0 && $discount <= 1);
        }
        
        // Дали толеранса е между 0 и 1
        if (isset($tolerance)) {
            expect($tolerance >= 0 && $tolerance <= 1);
        }
        
        if (!empty($term)) {
            expect($term = cls::get('type_Time')->fromVerbal($term));
        }
        
        // Трябва да има такъв продукт и опаковка
        expect(cat_Products::fetchField($productId, 'id'));
        if (isset($packagingId)) {
            expect(cat_UoM::fetchField($packagingId, 'id'));
        }
        
        if (isset($notes)) {
            $notes = cls::get('type_Richtext')->fromVerbal($notes);
        }
        
        // Броя единици в опаковка се определя от информацията за продукта
        $productInfo = cat_Products::getProductInfo($productId);
        if (empty($packagingId)) {
            $packagingId = $productInfo->productRec->measureId;
        }
        
        $quantityInPack = ($productInfo->packagings[$packagingId]) ? $productInfo->packagings[$packagingId]->quantity : 1;
        
        // Ако няма цена, опитваме се да я намерим от съответната ценова политика
        if (empty($price)) {
            $firstDocumentInThread = doc_Threads::getFirstDocument($rec->threadId);
            $listId = null;
            if($firstDocumentInThread->isInstanceOf('deals_DealMaster')){
                $listId = $firstDocumentInThread->fetchField('priceListId');
                $listId = empty($listId) ? null : $listId;
            }
            $Policy = (isset($Detail->Policy)) ? $Detail->Policy : cls::get('price_ListToCustomers');
            $policyInfo = $Policy->getPriceInfo($rec->contragentClassId, $rec->contragentId, $productId, $packagingId, $quantityInPack * $packQuantity, null, 1, 'no', $listId, true);

            $price = $policyInfo->price;
            if (!isset($discount) && isset($policyInfo->discount)) {
                $discount = $policyInfo->discount;
            }
            
            $price = ($price) ? $price : cat_Products::getPrimeCost($productId, null, null, null);
        }
        
        $packQuantity = cls::get('type_Double')->fromVerbal($packQuantity);
        
        // Подготвяме детайла
        $dRec = (object) array($Detail->masterKey => $id,
            'productId' => $productId,
            'packagingId' => $packagingId,
            'quantity' => $quantityInPack * $packQuantity,
            'discount' => $discount,
            'tolerance' => $tolerance,
            'term' => $term,
            'price' => $price,
            'quantityInPack' => $quantityInPack,
            'notes' => $notes,
        );

        if(!empty($batch) && core_Packs::isInstalled('batch')){
            $dRec->autoAllocate = false;
            $dRec->_clonedWithBatches = true;
        }

        // Ако е уникален, добавяме го
        $id = $Detail->save($dRec);
        if(!empty($batch) && core_Packs::isInstalled('batch')){
            batch_BatchesInDocuments::saveBatches($Detail, $id, array($batch => $dRec->quantity), true);
        }
        
        // Връщаме резултата от записа
        return $id;
    }
    
    
    /**
     * Връща дефолтен коментар при връзка на документи
     *
     * @param integer $id
     * @param string $comment
     *
     * @return string
     */
    public function getDefaultLinkedComment($id, $comment)
    {
        $rec = $this->fetchRec($id);
        
        $storeName = $rec->storeId ? store_Stores::getTitleById($rec->storeId) : '';
        $pattern = preg_quote($storeName, '/');
       
        if ($storeName && (!$comment || (!preg_match("/(^|\s)*{$pattern}(\$|\s){1}/iu", $comment)))) {
            if (trim($comment)) {
                $comment .= '<br>';
            }
            
            $comment .= tr("Склад|*: ") . $storeName;
        }
        
        return $comment;
    }


    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;

        if($rec->state == 'active'){
            if ($rec->isReverse == 'no') {
                if($ReverseClass = $mvc->getDocumentReverseClass($data->rec)){
                    if ($ReverseClass->haveRightFor('add', (object) array('threadId' => $rec->threadId, 'reverseContainerId' => $rec->containerId))) {
                        $data->toolbar->addBtn('Връщане', array($ReverseClass, 'add', 'threadId' => $rec->threadId, 'reverseContainerId' => $rec->containerId, 'ret_url' => true), "title=Създаване на документ за връщане,ef_icon={$ReverseClass->singleIcon},row=2");
                    }
                }
            }

            if(store_ConsignmentProtocols::canBeAddedFromDocument($rec->containerId)){
                $data->toolbar->addBtn('ПОП', array('store_ConsignmentProtocols', 'add', 'threadId' => $rec->threadId, 'ret_url' => true), "ef_icon=img/16/consignment.png,title=Създаване на нов протокол за отговорно пазене,row=1");
            }
        }
    }


    /**
     * Подготвя данните (в обекта $data) необходими за единичния изглед
     */
    public function prepareEditForm_($data)
    {
        parent::prepareEditForm_($data);
        $rec = &$data->form->rec;
        if (isset($rec->reverseContainerId) && empty($rec->id)) {
            $data->action = 'clone';
        }

        return $data;
    }


    /**
     * Кои детайли да се клонират с промяна
     *
     * @param stdClass $rec
     * @return array $res
     *          ['recs'] - записи за промяна
     *          ['detailMvc] - модел от който са
     */
    public function getDetailsToCloneAndChange_($rec)
    {
        $Detail = cls::get($this->mainDetail);
        $id = $rec->clonedFromId;

        // Ако е създаден като обратен документ взима детайлите от него
        if (isset($rec->reverseContainerId) && empty($rec->id)) {
            $Source = doc_Containers::getDocument($rec->reverseContainerId);
            $Detail = cls::get($Source->mainDetail);
            $id = $Source->that;
        }

        $masterRec = $Detail->Master->fetch($id, 'currencyRate,currencyId,valior');
        $res = array();
        $dQuery = $Detail->getQuery();
        $dQuery->where("#{$Detail->masterKey} = {$id}");
        while($dRec = $dQuery->fetch()){
            if($genericProductId = planning_GenericProductPerDocuments::getRec($Detail, $dRec->id)){
                $dRec->_genericProductId = $genericProductId;
            }

            // Какъв е курса на документа от който е извлечен детайла
            if(isset($masterRec->currencyRate)){
                $dRec->_rate = $masterRec->currencyRate;
            }
            if(isset($masterRec->valior)){
                $dRec->_valior = $masterRec->valior;
            }

            $res[$dRec->id] = $dRec;
        }
        $res = array('recs' => $res, 'detailMvc' => $Detail);

        return $res;
    }


    /**
     * Връща информация за сумите по платежния документ
     *
     * @param mixed $id
     * @return object
     */
    public function getPaymentData($id)
    {
        if (is_object($id)) {
            $rec = $id;
        } else {
            $rec = $this->fetchRec($id, '*', false);
        }

        $amount = round($rec->amountDelivered / $rec->currencyRate, 2);

        return (object)array('amount' => $amount, 'currencyId' => currency_Currencies::getIdByCode($rec->currencyId), 'operationSysId' => $rec->operationSysId, 'isReverse' => ($rec->isReverse == 'yes'));
    }


    /**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        if(isset($rec->_oldValior)){

            // Ако вальора е сменен и основната валута към стария вальор е различна от тази към новия
            if(acc_Periods::getBaseCurrencyCode($rec->_oldValior) != acc_Periods::getBaseCurrencyCode($rec->valior)){
                deals_Helper::recalcDetailPriceInBaseCurrency($mvc, $rec, $rec->_oldValior, $rec->valior, $rec->_oldRate);
                $valiorVerbal = dt::mysql2verbal($rec->valior, 'd.m.Y');
                core_Statuses::newStatus("Цените на артикулите са преизчислени към основната валута за|*: <b>{$valiorVerbal}</b>");
            }
        }
    }
}
