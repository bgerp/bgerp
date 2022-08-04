<?php


/**
 * Абстрактен клас за наследяване на протоколи за доствка и приемане на услуги
 *
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class deals_ServiceMaster extends core_Master
{
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'amountDelivered';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn, valior,deliveryTime,modifiedOn';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'valior,amountDelivered,amountDiscount,amountDeliveredVat,deliveryTime';
    
    
    /**
     * Кои са задължителните полета за модела
     */
    protected static function setServiceFields($mvc)
    {
        $mvc->FLD('valior', 'date', 'caption=Дата,oldFieldName=date');
        $mvc->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code,allowEmpty)', 'input=none,caption=Плащане->Валута');
        $mvc->FLD('currencyRate', 'double(decimals=5)', 'caption=Валута->Курс,input=hidden');
        $mvc->FLD('chargeVat', 'enum(yes=Включено ДДС в цените, separate=Отделен ред за ДДС, exempt=Освободено от ДДС, no=Без начисляване на ДДС)', 'caption=ДДС,input=hidden');
        
        $mvc->FLD('amountDelivered', 'double(decimals=2)', 'caption=Доставено,input=none,summary=amount'); // Сумата на доставената стока
        $mvc->FLD('amountDeliveredVat', 'double(decimals=2)', 'caption=Доставено,summary=amount,input=none');
        $mvc->FLD('amountDiscount', 'double(decimals=2)', 'input=none');
        
        // Контрагент
        $mvc->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
        $mvc->FLD('contragentId', 'int', 'input=hidden');
        
        // Доставка
        $mvc->FLD('locationId', 'key(mvc=crm_Locations, select=title)', 'caption=Обект до,silent');
        $mvc->FLD('deliveryTime', 'datetime', 'caption=Срок до');
        $mvc->FLD('received', 'varchar', 'caption=Получил');
        $mvc->FLD('delivered', 'varchar', 'caption=Доставил');
        
        // Допълнително
        $mvc->FLD('note', 'richtext(bucket=Notes,rows=6)', 'caption=Допълнително->Бележки');
        $mvc->FLD(
            'state',
                'enum(draft=Чернова, active=Контиран, rejected=Оттеглен,stopped=Спряно, pending=Заявка)',
                'caption=Статус, input=none'
        );
        $mvc->FLD('isReverse', 'enum(no,yes)', 'input=none,notNull,value=no');
        $mvc->FLD('accountId', 'customKey(mvc=acc_Accounts,key=systemId,select=id)', 'input=none,notNull,value=411');
        
        $mvc->setDbIndex('valior');
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
        
        $Detail = $this->mainDetail;
        $query = $this->{$Detail}->getQuery();
        $query->where("#{$this->{$Detail}->masterKey} = '{$id}'");
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
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int      $id  първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        if ($rec->_isCreated !== true) {
            
            return;
        }
        if ($rec->_isClone === true) {
            
            return;
        }
        $origin = $mvc->getOrigin($rec);
        
        // Ако новосъздадения документ има origin, който поддържа bgerp_AggregateDealIntf,
        // използваме го за автоматично попълване на детайлите на протокола
        expect($origin->haveInterface('bgerp_DealAggregatorIntf'));
        
        $aggregatedDealInfo = $origin->getAggregateDealInfo();
        $agreedProducts = $aggregatedDealInfo->get('products');
        $shippedProducts = $aggregatedDealInfo->get('shippedProducts');
        
        if (countR($shippedProducts)) {
            $normalizedProducts = deals_Helper::normalizeProducts(array($agreedProducts), array($shippedProducts));
        } else {
            $agreedProducts = $aggregatedDealInfo->get('dealProducts');
        }
        
        if (countR($agreedProducts)) {
            foreach ($agreedProducts as $index => $product) {
                $info = cat_Products::getProductInfo($product->productId);
                
                if (isset($normalizedProducts[$index])) {
                    $toShip = $normalizedProducts[$index]->quantity;
                } else {
                    $toShip = $product->quantity;
                }
                
                $price = ($agreedProducts[$index]->price) ? $agreedProducts[$index]->price : $normalizedProducts[$index]->price;
                $discount = ($agreedProducts[$index]->discount) ? $agreedProducts[$index]->discount : $normalizedProducts[$index]->discount;
                
                // Пропускат се експедираните и складируемите артикули
                if (isset($info->meta['canStore']) || ($toShip <= 0)) {
                    continue;
                }
                
                $shipProduct = new stdClass();
                $shipProduct->shipmentId = $rec->id;
                $shipProduct->productId = $product->productId;
                $shipProduct->packagingId = $product->packagingId;
                $shipProduct->quantity = $toShip;
                $shipProduct->price = $price;
                $shipProduct->discount = $discount;
                $shipProduct->notes = $product->notes;
                $shipProduct->quantityInPack = $product->quantityInPack;
                
                if (isset($product->expenseItemId)) {
                    $shipProduct->expenseItemId = $product->expenseItemId;
                }
                
                $Detail = $mvc->mainDetail;
                $dId = $mvc->{$Detail}->save($shipProduct);
                
                // Копиране на разпределените разходи
                if (!empty($product->expenseRecId)) {
                    $aRec = acc_CostAllocations::fetch($product->expenseRecId);
                    unset($aRec->id);
                    $aRec->detailRecId = $dId;
                    $aRec->detailClassId = $Detail::getClassId();
                    $aRec->containerId = $rec->containerId;
                    
                    acc_CostAllocations::save($aRec);
                }
            }
        }
    }
    
    
    /**
     * След създаване на запис в модела
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        $rec->_isCreated = true;
    }
    
    
    /**
     * След рендиране на сингъла
     */
    protected static function on_AfterRenderSingle($mvc, $tpl, $data)
    {
        if (Mode::is('printing') || Mode::is('text', 'xhtml')) {
            $tpl->removeBlock('header');
        }
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
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Задаване на стойности на полетата на формата по подразбиране
        $form = &$data->form;
        $rec = &$form->rec;
        
        $rec->contragentClassId = doc_Folders::fetchCoverClassId($rec->folderId);
        $rec->contragentId = doc_Folders::fetchCoverId($rec->folderId);
        
        // Поле за избор на локация - само локациите на контрагента по покупката
        $form->getField('locationId')->type->options =
        array('' => '') + crm_Locations::getContragentOptions($rec->contragentClassId, $rec->contragentId);
        
        // Ако създаваме нов запис и то базиран на предхождащ документ ...
        if (empty($form->rec->id)) {
            
            // ... проверяваме предхождащия за bgerp_DealIntf
            $origin = ($form->rec->originId) ? doc_Containers::getDocument($form->rec->originId) : doc_Threads::getFirstDocument($form->rec->threadId);
            expect($origin->haveInterface('bgerp_DealAggregatorIntf'));
            
            $dealInfo = $origin->getAggregateDealInfo();
            
            $form->setDefault('currencyId', $dealInfo->get('currency'));
            $form->setDefault('currencyRate', $dealInfo->get('rate'));
            $form->setDefault('locationId', $dealInfo->get('deliveryLocation'));
            $form->setDefault('deliveryTime', $dealInfo->get('deliveryTime'));
            $form->setDefault('chargeVat', $dealInfo->get('vatType'));
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (isset($fields['-list'])) {
            if ($rec->amountDeliveredVat || $rec->amountDelivered) {
                $row->amountDeliveredVat = "<span class='cCode' style='float:left'>{$rec->currencyId}</span> &nbsp;{$row->amountDeliveredVat}";
                $row->amountDelivered = "<span class='cCode' style='float:left'>{$rec->currencyId}</span> &nbsp;{$row->amountDelivered}";
            } else {
                $row->amountDeliveredVat = "<span class='quiet'>0.00</span>";
            }
            
            $row->title = $mvc->getLink($rec->id, 0);
        }
        
        if (isset($fields['-single'])) {
            core_Lg::push($rec->tplLang);

            $row->reff = deals_Helper::getYourReffInThread($rec->threadId);
            $headerInfo = deals_Helper::getDocumentHeaderInfo($rec->contragentClassId, $rec->contragentId);
            $row = (object) ((array) $row + $headerInfo);
            
            if ($rec->locationId) {
                $row->locationId = crm_Locations::getHyperlink($rec->locationId);
                
                $contLocationAddress = crm_Locations::getAddress($rec->locationId);
                if ($contLocationAddress != '') {
                    $row->deliveryLocationAddress = core_Lg::transliterate($contLocationAddress);
                }
                
                if ($gln = crm_Locations::fetchField($rec->locationId, 'gln')) {
                    $row->deliveryLocationAddress = $gln . ', ' . $row->deliveryLocationAddress;
                    $row->deliveryLocationAddress = trim($row->deliveryLocationAddress, ', ');
                }
            }
            
            if (!empty($rec->delivered)) {
                $row->delivered = core_Lg::transliterate($row->delivered);
            }
            
            core_Lg::pop();
            
            if ($rec->isReverse == 'yes') {
                if (!Mode::is('text', 'xhtml') && !Mode::is('printing') && !Mode::is('pdf')) {
                    $row->operationSysId = tr('Отказ от услуга');
                }
            }
            
            // Имената в Получил и Доставил да се пренасят, ако са по-дълги
            if (strlen($rec->received) > 60) {
                $row->receivedClass = 'wrapText';
            }
            
            if (strlen($rec->delivered) > 60) {
                $row->deliveredClass = 'wrapText';
            }
        }
    }
    
    
    /**
     * Протокола не може да бъде начало на нишка; може да се създава само в съществуващи нишки
     *
     * @param $folderId int ид на папката
     *
     * @return bool
     */
    public static function canAddToFolder($folderId)
    {
        return false;
    }
    
    
    /**
     * @param int $id key(mvc=purchase_Purchases)
     *
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow_($id)
    {
        expect($rec = $this->fetch($id));
        $title = "{$this->singleTitle} №{$rec->id} / " . $this->getVerbal($rec, 'valior');
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
     * Връща масив от използваните нестандартни артикули в протокола
     *
     * @param int $id - ид на протокола
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
        
        $aggregator->setIfNot('shippedValior', $rec->valior);
        $aggregator->setIfNot('deliveryLocation', $rec->locationId);
        $aggregator->setIfNot('deliveryTime', $rec->deliveryTime);
        
        $Detail = $this->mainDetail;
        $dQuery = $this->{$Detail}->getQuery();
        $dQuery->where("#{$this->{$Detail}->masterKey} = {$rec->id}");
        
        while ($dRec = $dQuery->fetch()) {
            $p = new stdClass();
            $p->productId = $dRec->productId;
            $p->packagingId = $dRec->packagingId;
            $p->inPack = $dRec->quantityInPack;
            $index = $dRec->productId;
            
            $aggregator->push('shippedPacks', $p, $index);
        }
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
                
                return (isset($operations[static::$defOperationSysId])) ? true : false;
            }
        }
        
        return false;
    }
    
    
    /**
     * Връща тялото на имейла генериран от документа
     *
     * @see email_DocumentIntf
     *
     * @param int  $id      - ид на документа
     * @param bool $forward
     *
     * @return string - тялото на имейла
     */
    public function getDefaultEmailBody($id, $forward = false)
    {
        $handle = $this->getHandle($id);
        $title = tr(mb_strtolower($this->singleTitle));
        
        $tpl = new ET(tr('Моля запознайте се с нашия') . " {$title}: #[#handle#]");
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
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
}
