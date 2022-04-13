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
    public $priceFields = 'amountDelivered';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'storeId, locationId, deliveryTime, lineId, contragentClassId, contragentId, weight, volume, folderId, id';
    
    
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
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn, valior,deliveryTime,modifiedOn';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'valior, amountDelivered, amountDeliveredVat, amountDiscount, deliveryTime,weight,volume,weightInput,volumeInput,lineId,additionalConditions,reverseContainerId';
    
    
    /**
     * Поле за забележките
     */
    public $notesFld = 'note';
    
    
    /**
     * След описанието на полетата
     */
    protected static function setDocFields(core_Master &$mvc)
    {
        $mvc->FLD('valior', 'date', 'caption=Дата');
        $mvc->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code,allowEmpty)', 'input=none,caption=Вал.,smartCenter');
        $mvc->FLD('currencyRate', 'double(decimals=5)', 'caption=Валута->Курс,input=hidden');
        $mvc->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=От склад, mandatory,silent,removeAndRefreshForm=deliveryTime');
        $mvc->FLD('chargeVat', 'enum(yes=Включено ДДС в цените, separate=Отделен ред за ДДС, exempt=Освободено от ДДС, no=Без начисляване на ДДС)', 'caption=ДДС,input=hidden');
        
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
        
        $mvc->FLD('note', 'richtext(bucket=Notes,passage,rows=6)', 'caption=Допълнително->Бележки');
        $mvc->FLD('state', 'enum(draft=Чернова, active=Контиран, rejected=Оттеглен,stopped=Спряно, pending=Заявка)', 'caption=Статус, input=none');
        $mvc->FLD('isReverse', 'enum(no,yes)', 'input=none,notNull,value=no');
        $mvc->FLD('accountId', 'customKey(mvc=acc_Accounts,key=systemId,select=id)', 'input=none,notNull,value=411');

        $mvc->FLD('prevShipment', 'key(mvc=store_ShipmentOrders)', 'caption=Адрес за доставка->Избор,silent,removeAndRefreshForm=company|person|tel|country|pCode|place|address|features|addressInfo,placeholder=От предишна доставка,autohide');
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
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (!deals_Helper::canSelectObjectInDocument($action, $rec, 'store_Stores', 'storeId')) {
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
        
        $form->setDefault('storeId', store_Stores::getCurrent('id', false));
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
        $form->setDefault('locationId', $dealInfo->get('deliveryLocation'));

        $form->setDefault('deliveryOn', $dealInfo->get('deliveryTime'));
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
                if (!$mvc->haveRightFor('asClient', $sRec)) continue;
                if (!$mvc->haveRightFor('single', $sRec)) continue;

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
            if ($rec->importProducts != 'all') {
                $agreedProducts = $aggregatedDealInfo->get('products');
                $shippedProducts = $aggregatedDealInfo->get('shippedProducts');
                
                if (countR($shippedProducts)) {
                    $normalizedProducts = deals_Helper::normalizeProducts(array($agreedProducts), array($shippedProducts));
                } else {
                    $copyBatches = true;
                    $agreedProducts = $aggregatedDealInfo->get('dealProducts');
                }
                
                if ($rec->importProducts == 'stocked') {
                    foreach ($agreedProducts as $i1 => $p1) {
                        $inStock = store_Products::fetchField("#storeId = {$rec->storeId} AND #productId = {$p1->productId}", 'quantity');
                        if ($p1->quantity > $inStock) {
                            unset($agreedProducts[$i1]);
                        }
                    }
                }
            } else {
                $agreedProducts = $aggregatedDealInfo->get('dealProducts');
                $normalizedProducts = $aggregatedDealInfo->get('dealProducts');
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
                    } else {
                        $toShip = $product->quantity;
                    }
                    
                    $price = (isset($agreedProducts[$index]->price)) ? $agreedProducts[$index]->price : $normalizedProducts[$index]->price;
                    $discount = ($agreedProducts[$index]->discount) ? $agreedProducts[$index]->discount : $normalizedProducts[$index]->discount;
                    
                    // Пропускат се експедираните продукти
                    if ($toShip <= 0) {
                        continue;
                    }
                    
                    $shipProduct = new stdClass();
                    $shipProduct->{$mvc->{$Detail}->masterKey} = $rec->id;
                    $shipProduct->productId = $product->productId;
                    $shipProduct->packagingId = $product->packagingId;
                    $shipProduct->quantity = $toShip;
                    $shipProduct->price = $price;
                    $shipProduct->discount = $discount;
                    $shipProduct->notes = $product->notes;
                    $shipProduct->quantityInPack = $product->quantityInPack;
                    
                    if (core_Packs::isInstalled('batch') && $copyBatches === true) {
                        $shipProduct->isEdited = false;
                        $shipProduct->_clonedWithBatches = true;
                    }
                    
                    $Detail::save($shipProduct);
                    
                    // Копира партидата ако артикулите идат 1 към 1 от договора
                    if (core_Packs::isInstalled('batch') && $copyBatches === true) {
                        if (is_array($product->batches)) {
                            foreach ($product->batches as $bRec) {
                                unset($bRec->id);
                                $bRec->detailClassId = $mvc->{$Detail}->getClassId();
                                $bRec->detailRecId = $shipProduct->id;
                                $bRec->containerId = $rec->containerId;
                                $bRec->date = $rec->valior;
                                $bRec->storeId = $rec->storeId;
                                
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
        }  elseif(!doc_plg_HidePrices::canSeePriceFields($rec)) {
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

            $headerInfo = deals_Helper::getDocumentHeaderInfo($rec->contragentClassId, $rec->contragentId);
            $row = (object) ((array) $row + (array) $headerInfo);

            $row->deliveryTo = '';
            if ($row->country) {
                $row->deliveryTo .= $row->country;
            }

            if ($row->pCode) {
                $row->deliveryTo .= (($row->deliveryTo) ? ', ' : '') . $row->pCode;
            }

            if ($row->pCode) {
                $row->deliveryTo .= ' ' . core_Lg::transliterate($row->place);
            }

            foreach (array('address', 'company', 'person', 'tel', 'features', 'addressInfo') as $fld) {
                if (!empty($rec->{$fld})) {
                    if ($fld == 'address') {
                        $row->{$fld} = core_Lg::transliterate($row->{$fld});
                    } elseif ($fld == 'tel') {
                        if (callcenter_Talks::haveRightFor('list')) {
                            $row->{$fld} = ht::createLink($rec->{$fld}, array('callcenter_Talks', 'list', 'number' => $rec->{$fld}));
                        }
                    }

                    $row->deliveryTo .= ", {$row->{$fld}}";
                }
            }

            if ($rec->locationId) {
                $row->locationId = crm_Locations::getHyperlink($rec->locationId);
                if ($ourLocation = store_Stores::fetchField($rec->storeId, 'locationId')) {
                    $row->ourLocation = crm_Locations::getTitleById($ourLocation);
                    $ourLocationAddress = crm_Locations::getAddress($ourLocation);
                    if ($ourLocationAddress != '') {
                        $row->ourLocationAddress = $ourLocationAddress;
                    }
                }
                
                $contLocationAddress = crm_Locations::getAddress($rec->locationId);
                if ($contLocationAddress != '') {
                    $row->deliveryLocationAddress = core_Lg::transliterate($contLocationAddress);
                }

                $locationRec = crm_Locations::fetch($rec->locationId, 'gln,tel,mol');
                if ($locationRec->gln) {
                    $row->deliveryLocationAddress = $locationRec->gln . ', ' . $row->deliveryLocationAddress;
                    $row->deliveryLocationAddress = trim($row->deliveryLocationAddress, ', ');
                }
                
                if ($locationRec->tel) {
                    $locTel = core_Type::getByName('varchar')->toVerbal($locationRec->tel);
                    $row->deliveryLocationAddress .= ", {$locTel}";
                }
                
                if ($locationRec->mol) {
                    $locMol = core_Type::getByName('varchar')->toVerbal($locationRec->mol);
                    $row->deliveryLocationAddress .= ", {$locMol}";
                }
            }

            $row->storeId = store_Stores::getHyperlink($rec->storeId);
            core_Lg::pop();
            
            if ($rec->isReverse == 'yes') {
                $row->operationSysId = tr('Връщане на стока');
                if(isset($rec->reverseContainerId)){
                    $row->operationSysId .= tr("|* |от|* ") . doc_Containers::getDocument($rec->reverseContainerId)->getLink(0, array('ef_icon' => false));
                }
            }
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
     * Документа не може да бъде начало на нишка; може да се създава само в съществуващи нишки
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
     * @return bgerp_iface_DealAggregator
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
            
            $vat = cat_Products::getVat($dRec->productId);
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
            $fields = ($Doc->isInstanceOf('deals_DealMaster')) ? 'deliveryTime,valior' : "{$Doc->lineFieldName},deliveryTime,valior";
            $docRec = $Doc->fetch($fields);

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
        $ownCompany = crm_Companies::fetchOurCompany();
        $ownCountryId = $ownCompany->country;
        
        if ($locationId = store_Stores::fetchField($rec->storeId, 'locationId')) {
            $storeLocation = crm_Locations::fetch($locationId);
            $ownCountryId = $storeLocation->countryId;
        }
        
        $contragentData = doc_Folders::getContragentData($rec->folderId);
        $contragentCountryId = $contragentData->countryId;
        
        if (isset($rec->locationId)) {
            $contragentLocation = crm_Locations::fetch($rec->locationId);
            $contragentCountryId = $contragentLocation->countryId;
        }
        
        $ownPart = ($this instanceof store_ShipmentOrders) ? 'from' : 'to';
        $contrPart = ($this instanceof store_ShipmentOrders) ? 'to' : 'from';
        
        // Подготвяне на данните за разтоварване
        $res = array();
        $res["{$ownPart}Country"] = drdata_Countries::fetchField($ownCountryId, 'commonName');
        
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
        
        $res["{$ownPart}Company"] = $ownCompany->name;
        $toPersonId = ($rec->activatedBy) ? $rec->activatedBy : $rec->createdBy;
        $res["{$ownPart}Person"] = ($res["{$ownPart}Person"]) ? $res["{$ownPart}Person"] : core_Users::fetchField($toPersonId, 'names');
        
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
        $res['ourReff'] = '#' . $this->getHandle($rec);

        $totalInfo = $this->getTotalTransportInfo($rec);
        $res['totalWeight'] = isset($rec->weightInput) ? $rec->weightInput : $totalInfo->weight;
        $res['totalVolume'] = isset($rec->volumeInput) ? $rec->volumeInput : $totalInfo->volume;

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
     */
    public function getTransportLineInfo_($rec, $lineId)
    {
        $rec = static::fetchRec($rec);
        $res = array('baseAmount' => null, 'amount' => null, 'currencyId' => null, 'notes' => $rec->lineNotes);
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
            $res['amountVerbal'] = currency_Currencies::decorate($amountVerbal, $rec->currencyId);
        }

        if(!empty($logisticData["{$part}AddressInfo"])){
            $res['addressInfo'] = $logisticData["{$part}AddressInfo"];
        }

        return $res;
    }
    
    
    /*
     * API за генериране на сделка
     */
    
    
    /**
     * Метод за бързо създаване на чернова сделка към контрагент
     *
     * @param mixed $contragentClass - ид/инстанция/име на класа на котрагента
     * @param int   $contragentId    - ид на контрагента
     * @param array $fields          - стойности на полетата на сделката
     *
     * 		o $fields['valior']             -  вальор (ако няма е текущата дата)
     * 		o $fields['reff']               -  вашия реф на продажбата
     * 		o $fields['currencyId']         -  код на валута (ако няма е основната за периода)
     * 		o $fields['currencyRate']       -  курс към валутата (ако няма е този към основната валута)
     * 		o $fields['paymentMethodId']    -  ид на платежен метод (Ако няма е плащане в брой, @see cond_PaymentMethods)
     * 		o $fields['chargeVat']          -  да се начислява ли ДДС - yes=Да, separate=Отделен ред за ДДС, exempt=Освободено,no=Без начисляване(ако няма, се определя според контрагента)
     * 		o $fields['shipmentStoreId']    -  ид на склад (@see store_Stores)
     * 		o $fields['deliveryTermId']     -  ид на метод на доставка (@see cond_DeliveryTerms)
     * 		o $fields['deliveryLocationId'] -  ид на локация за доставка (@see crm_Locations)
     * 		o $fields['deliveryTime']       -  дата на доставка
     * 		o $fields['dealerId']           -  ид на потребител търговец
     * 		o $fields['initiatorId']        -  ид на потребител инициатора (ако няма е отговорника на контрагента)
     * 		o $fields['caseId']             -  ид на каса (@see cash_Cases)
     * 		o $fields['note'] 				-  бележки за сделката
     * 		o $fields['originId'] 			-  източник на документа
     *		o $fields['makeInvoice'] 		-  изисквали се фактура или не (yes = Да, no = Не), По дефолт 'yes'
     *		o $fields['template'] 		    -  бележки за сделката
     *      o $fields['receiptId']          -  информативно от коя бележка е
     *      o $fields['onlineSale']         -  дали е онлайн продажба
     *
     * @return mixed $id/FALSE - ид на запис или FALSE
     */
    public static function createNewDraft($contragentClass, $contragentId, $fields = array())
    {
        $contragentClass = cls::get($contragentClass);
        expect($cRec = $contragentClass->fetch($contragentId));
        expect($cRec->state != 'rejected');
        
        // Намираме всички полета, които не са скрити или не се инпутват, те са ни позволените полета
        $me = cls::get(get_called_class());
        $fields = arr::make($fields);
        $allowedFields = $me->selectFields("#input != 'none' AND #input != 'hidden'");
        $allowedFields['originId'] = true;
        $allowedFields['currencyRate'] = true;
        $allowedFields['deliveryTermId'] = true;
        $allowedFields['receiptId'] = true;
        $allowedFields['onlineSale'] = true;
        
        // Проверяваме подадените полета дали са позволени
        if (countR($fields)) {
            foreach ($fields as $fld => $value) {
                expect(array_key_exists($fld, $allowedFields), $fld);
            }
        }
        
        // Ако има склад, съществува ли?
        if (isset($fields['shipmentStoreId'])) {
            expect(store_Stores::fetch($fields['shipmentStoreId']));
        }
        
        // Ако има каса, съществува ли?
        if (isset($fields['caseId'])) {
            expect(cash_Cases::fetch($fields['caseId']));
        }
        
        // Ако има условие на доставка, съществува ли?
        if (isset($fields['deliveryTermId'])) {
            expect(cond_DeliveryTerms::fetch($fields['deliveryTermId']));
        }
        
        // Ако има платежен метод, съществува ли?
        if (isset($fields['paymentMethodId'])) {
            expect(cond_PaymentMethods::fetch($fields['paymentMethodId']));
        }
        
        // Форсираме папката на клиента
        $fields['folderId'] = $contragentClass::forceCoverAndFolder($contragentId);
        
        // Ако е зададен шаблон, съществува ли?
        if (isset($fields['template'])) {
            expect(doc_TplManager::fetch($fields['template']));
        } elseif ($me instanceof sales_Sales) {
            $fields['template'] = $me->getDefaultTemplate((object) array('folderId' => $fields['folderId']));
        }
        
        // Ако не е подадена дата, това е сегашната
        $fields['valior'] = (empty($fields['valior'])) ? dt::today() : $fields['valior'];
        
        // Записваме данните на контрагента
        $fields['contragentClassId'] = $contragentClass->getClassId();
        $fields['contragentId'] = $contragentId;
        
        // Ако няма валута, това е основната за периода
        $fields['currencyId'] = (empty($fields['currencyId'])) ? acc_Periods::getBaseCurrencyCode($fields['valior']) : $fields['currencyId'];
        
        // Ако няма курс, това е този за основната валута
        
        if (empty($fields['currencyRate'])) {
            $fields['currencyRate'] = currency_CurrencyRates::getRate($fields['currencyRate'], $fields['currencyId'], null);
            expect($fields['currencyRate']);
        }
        
        // Ако няма платежен план, това е плащане в брой
        $paymentSysId = (get_called_class() == 'sales_Sales') ? 'paymentMethodSale' : 'paymentMethodPurchase';
        $fields['paymentMethodId'] = (empty($fields['paymentMethodId'])) ? cond_Parameters::getParameter($contragentClass, $contragentId, $paymentSysId) : $fields['paymentMethodId'];
        
        $termSysId = (get_called_class() == 'sales_Sales') ? 'deliveryTermSale' : 'deliveryTermPurchase';
        $fields['deliveryTermId'] = (empty($fields['deliveryTermId'])) ? cond_Parameters::getParameter($contragentClass, $contragentId, $termSysId) : $fields['deliveryTermId'];
        
        // Ако не е подадено да се начислявали ддс, определяме от контрагента
        if (empty($fields['chargeVat'])) {
            $fields['chargeVat'] = ($contragentClass::shouldChargeVat($contragentId)) ? 'yes' : 'no';
        }
        
        // Ако не е подадено да се начислявали ддс, определяме от контрагента
        if (empty($fields['makeInvoice'])) {
            $fields['makeInvoice'] = 'yes';
        }
        
        // Състояние на плащането, чакащо
        $fields['paymentState'] = 'pending';
        
        // Опиваме се да запишем мастъра на сделката
        $rec = (object) $fields;
        if ($fields['onlineSale'] === true) {
            $rec->_onlineSale = true;
        }
        
        if (isset($fields['receiptId'])) {
            $rec->_receiptId = $fields['receiptId'];
        }
        
        if ($id = $me->save($rec)) {
            doc_ThreadUsers::addShared($rec->threadId, $rec->containerId, core_Users::getCurrent());
            
            return $id;
        }
        
        return false;
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
        
        // Проверяваме дали въвдения детайл е уникален
        $exRec = deals_Helper::fetchExistingDetail($Detail, $id, null, $productId, $packagingId, $price, $discount, $tolerance, $term, null, null, $notes);
        
        if (is_object($exRec)) {
            
            // Смятаме средно притеглената цена и отстъпка
            $nPrice = ($exRec->quantity * $exRec->price + $dRec->quantity * $dRec->price) / ($dRec->quantity + $exRec->quantity);
            $nDiscount = ($exRec->quantity * $exRec->discount + $dRec->quantity * $dRec->discount) / ($dRec->quantity + $exRec->quantity);
            $nTolerance = ($exRec->quantity * $exRec->tolerance + $dRec->quantity * $dRec->tolerance) / ($dRec->quantity + $exRec->quantity);
            
            // Ъпдейтваме к-то, цената и отстъпката на записа с новите
            if ($term) {
                $exRec->term = max($exRec->term, $dRec->term);
            }
            
            $exRec->quantity += $dRec->quantity;
            $exRec->price = $nPrice;
            $exRec->discount = (empty($nDiscount)) ? null : round($nDiscount, 2);
            $exRec->tolerance = (!isset($nTolerance)) ? null : round($nTolerance, 2);
            
            // Ъпдейтваме съществуващия запис
            $id = $Detail->save($exRec);
        } else {
            
            // Ако е уникален, добавяме го
            $id = $Detail->save($dRec);
            if(!empty($batch) && core_Packs::isInstalled('batch')){
                batch_BatchesInDocuments::saveBatches($Detail, $id, array($batch => $dRec->quantity), true);
            }
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
        if ($rec->isReverse == 'no') {
            if($rec->state == 'active'){
                if(isset($mvc->reverseClassName)){
                    $ReverseClass = cls::get($mvc->reverseClassName);
                    if ($ReverseClass->haveRightFor('add', (object) array('threadId' => $rec->threadId, 'reverseContainerId' => $rec->containerId))) {
                        $data->toolbar->addBtn('Връщане', array($ReverseClass, 'add', 'threadId' => $rec->threadId, 'reverseContainerId' => $rec->containerId, 'ret_url' => true), "title=Създаване на документ за връщане,ef_icon={$ReverseClass->singleIcon},row=2");
                    }
                }
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
     * @param mixed    $Detail
     *
     * @return array
     */
    public function getDetailsToCloneAndChange($rec, &$Detail)
    {
        $Detail = cls::get($this->mainDetail);
        $id = $rec->clonedFromId;

        // Ако е създаден като обратен документ взима детайлите от него
        if (isset($rec->reverseContainerId) && empty($rec->id)) {
            $Source = doc_Containers::getDocument($rec->reverseContainerId);
            $Detail = cls::get($Source->mainDetail);
            $id = $Source->that;
        }

        $dQuery = $Detail->getQuery();
        $dQuery->where("#{$Detail->masterKey} = {$id}");

        return $dQuery->fetchAll();
    }
}
