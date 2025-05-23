<?php


/**
 * Базов клас за наследяване на детайл на ф-ри
 *
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class deals_DeliveryDocumentDetail extends doc_Detail
{
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'discount';
    
    
    /**
     * Задължителни полета за модела
     */
    public static function setDocumentFields($mvc)
    {
        $mvc->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,maxSuggestions=100,forceAjax,titleFld=name,forceOpen)', 'class=w100,caption=Артикул,notNull,mandatory', 'tdClass=productCell leftCol wrap,silent,removeAndRefreshForm=packPrice|discount|packagingId|batch|baseQuantity');
        $mvc->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Мярка,smartCenter,tdClass=small-field nowrap,after=productId,mandatory,silent,removeAndRefreshForm=packPrice|discount|baseQuantity,input=hidden');
        $mvc->FLD('quantity', 'double', 'caption=К-во в осн.мярка,input=none');
        $mvc->FLD('quantityInPack', 'double(decimals=2)', 'input=none,column=none,caption=К-во в опаковката');
        $mvc->FLD('price', 'double(decimals=2)', 'caption=Цена,input=none');
        $mvc->FNC('amount', 'double(minDecimals=2,maxDecimals=2)', 'caption=Сума,input=none');
        $mvc->FNC('packQuantity', 'double', 'caption=Количество,smartCenter,input=input');
        $mvc->FNC('packPrice', 'double(minDecimals=2)', 'caption=Цена,input,smartCenter');
        $mvc->FLD('discount', 'percent(min=0,max=1,suggestions=5 %|10 %|15 %|20 %|25 %|30 %)', 'caption=Отстъпка,smartCenter');
        $mvc->FLD('notes', 'richtext(rows=3,bucket=Notes,passage)', 'caption=Допълнително->Забележки');

        $mvc->setFieldTypeParams('discount', array('warningMax' => deals_Setup::get('MAX_WARNING_DISCOUNT')));
        $mvc->setDbIndex('productId,packagingId');
        setIfNot($mvc->allowInputPriceForQuantity, false);
    }

    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm(core_Mvc $mvc, &$data)
    {
        $rec = &$data->form->rec;
        $masterRec = $data->masterRec;
        $vatExceptionId = cond_VatExceptions::getFromThreadId($masterRec->threadId);

        $data->form->fields['packPrice']->unit = '|*' . $masterRec->currencyId . ', ';
        $data->form->fields['packPrice']->unit .= ($masterRec->chargeVat == 'yes') ? '|с ДДС|*' : '|без ДДС|*';
        // Ако ще се позволява въвеждането на цена за к-то - полето за цена става varchar
        if($mvc->allowInputPriceForQuantity){
            $data->form->setFieldType('packPrice', 'varchar(nullIfEmpty)');
            $data->form->setField('packPrice', 'class=w25');
        }

        if (isset($rec->id)) {
            $data->form->setReadOnly('productId');
        }
        
        if (!empty($rec->packPrice)) {
            $vat = cat_Products::getVat($rec->productId, $masterRec->valior, $vatExceptionId);
            $rec->packPrice = deals_Helper::getDisplayPrice($rec->packPrice, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function inputDocForm(core_Mvc $mvc, core_Form $form)
    {
        $rec = &$form->rec;
        $masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey});
        $vatExceptionId = cond_VatExceptions::getFromThreadId($masterRec->threadId);

        if ($form->rec->productId) {
            $vat = cat_Products::getVat($rec->productId, $masterRec->valior, $vatExceptionId);
            $productInfo = cat_Products::getProductInfo($rec->productId);
            
            $packs = cat_Products::getPacks($rec->productId, $rec->packagingId);
            $form->setOptions('packagingId', $packs);
            $form->setDefault('packagingId', key($packs));
            $form->setField('packagingId', 'input');

            // Показване на допълнителна мярка
            if (isset($rec->packagingId)) {
                if($mvc->getField('baseQuantity', false)){
                    $pType = cat_UoM::fetchField($rec->packagingId, 'type');
                    $derivitiveMeasures = cat_UoM::getSameTypeMeasures($productInfo->productRec->measureId);
                    if ($pType == 'uom' && !array_key_exists($rec->packagingId, $derivitiveMeasures)){
                        $form->setField('baseQuantity', 'input');
                        $measureShort = cat_UoM::getShortName($productInfo->productRec->measureId);
                        $form->setField('baseQuantity', "unit={$measureShort}");
                    } else {
                        $form->setField('baseQuantity', 'input=none');
                    }
                }
            }

            $LastPolicy = ($masterRec->isReverse == 'yes') ? 'ReverseLastPricePolicy' : 'LastPricePolicy';
            if (isset($mvc->{$LastPolicy})) {
                $policyInfoLast = $mvc->{$LastPolicy}->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->packagingId, $rec->packQuantity, $masterRec->valior, $masterRec->currencyRate, $masterRec->chargeVat);
                if ($policyInfoLast->price != 0) {
                    $form->setSuggestions('packPrice', array('' => '', "{$policyInfoLast->price}" => $policyInfoLast->price));
                }
            }

            // Ако има такъв запис, сетваме грешка
            $setWarning = deals_Setup::get('WARNING_ON_DUPLICATED_ROWS');
            if($setWarning == 'yes'){
                $countSameProduct = $mvc->count("#{$mvc->masterKey} = '{$rec->{$mvc->masterKey}}' AND #id != '{$rec->id}' AND #productId = {$rec->productId}");
                if ($countSameProduct) {
                    $form->setWarning('productId', 'Артикулът вече присъства на друг ред в документа|*');
                }
            }
        }

        if ($form->isSubmitted() && !$form->gotErrors()) {
            if (!isset($rec->packQuantity)) {
                $form->setDefault('packQuantity', $rec->_moq ? $rec->_moq : deals_Helper::getDefaultPackQuantity($rec->productId, $rec->packagingId));
                if (empty($rec->packQuantity)) {
                    $form->setError('packQuantity', 'Не е въведено количество');
                }
            }
            
            // Проверка на к-то
            $warning = null;
            if (!deals_Helper::checkQuantity($rec->packagingId, $rec->packQuantity, $warning)) {
                $form->setWarning('packQuantity', $warning);
            }
            
            // Ако артикула няма опаковка к-то в опаковка е 1, ако има и вече не е свързана към него е това каквото е било досега, ако още я има опаковката обновяваме к-то в опаковка
            $rec->quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
            
            if (!empty($rec->baseQuantity)) {
                if (empty($rec->packQuantity)) {
                    $rec->packQuantity = $rec->baseQuantity * $rec->quantityInPack;
                } else {
                    $rec->quantityInPack = $rec->baseQuantity / $rec->packQuantity;
                }
            }
            
            $rec->quantity = $rec->packQuantity * $rec->quantityInPack;

            if (!isset($rec->packPrice)) {
                $autoPrice = true;

                // Ако продукта има цена от пораждащия документ, взимаме нея, ако не я изчисляваме наново
                $origin = $mvc->Master->getOrigin($masterRec);
                if ($origin->haveInterface('bgerp_DealAggregatorIntf')) {
                    $dealInfo = $origin->getAggregateDealInfo();
                    $products = $dealInfo->get('products');
                    
                    if (countR($products)) {
                        foreach ($products as $p) {
                            if ($rec->productId == $p->productId && $rec->packagingId == $p->packagingId) {
                                $policyInfo = new stdClass();
                                $policyInfo->price = deals_Helper::getDisplayPrice($p->price, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
                                $policyInfo->discount = $p->discount;
                                break;
                            }
                        }
                    }
                }
                
                if (!$policyInfo) {
                    if(isset($rec->productId)){
                        $listId = ($dealInfo->get('priceListId')) ? $dealInfo->get('priceListId') : null;

                        // Ако има политика в документа и той не прави обратна транзакция, използваме нея, иначе продуктовия мениджър
                        $Policy = ($masterRec->isReverse == 'yes') ? (($mvc->ReversePolicy) ? $mvc->ReversePolicy : cls::get('price_ListToCustomers')) : (($mvc->Policy) ? $mvc->Policy : cls::get('price_ListToCustomers'));
                        $policyInfo = $Policy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->packagingId, $rec->quantity, $masterRec->valior, $masterRec->currencyRate, $masterRec->chargeVat, $listId);
                    }
                }
                
                // Ако няма последна покупна цена и не се обновява запис в текущата покупка
                if(isset($rec->productId)){
                    if (!isset($policyInfo->price)) {
                        $errorMsg = isset($Policy) ? $Policy->notFoundPriceErrorMsg : 'Артикулът няма цена в избраната ценова политика. Въведете цена|*!';
                        $form->setError('packPrice', $errorMsg);
                    } else {

                        // Ако се обновява запис се взима цената от него, ако не от политиката
                        $rec->price = $policyInfo->price;
                        $rec->packPrice = $policyInfo->price * $rec->quantityInPack;
                    }
                }
                
                if ($policyInfo->discount && !isset($rec->discount)) {
                    $rec->discount = $policyInfo->discount;
                }
            } else {
                $autoPrice = false;
                $error = $price4Quantity = null;
                if($mvc->allowInputPriceForQuantity){
                    $price4Quantity = deals_Helper::isPrice4Quantity($rec->packPrice, $rec->quantity, $error);
                    if(!empty($error)){
                        $form->setError('packPrice', $error);
                        return;
                    }
                }

                // Изчисляване цената за единица продукт в осн. мярка
                $rec->price = $price4Quantity ?? ($rec->packPrice / $rec->quantityInPack);
                
                if (!$form->gotErrors() || ($form->gotErrors() && Request::get('Ignore'))) {
                    $rec->packPrice = deals_Helper::getPurePrice($rec->packPrice, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
                }
            }
            
            // Проверка на цената
            $msg = null;
            if (!deals_Helper::isPriceAllowed($rec->price, $rec->quantity, $autoPrice, $msg)) {
                $form->setError('packPrice,packQuantity', $msg);
            }
            
            $rec->price = deals_Helper::getPurePrice($rec->price, $vat, $masterRec->currencyRate, $masterRec->chargeVat);

            // При редакция, ако е променена опаковката слагаме преудпреждение
            if ($rec->id) {
                $oldRec = $mvc->fetch($rec->id);
                if ($oldRec && $rec->packagingId != $oldRec->packagingId && !empty($rec->packPrice) && trim($rec->packPrice) == trim($oldRec->packPrice)) {
                    $form->setWarning('packPrice,packagingId', 'Опаковката е променена без да е променена цената|*.<br />|Сигурни ли сте, че зададената цена отговаря на новата опаковка|*?');
                }
            }
        }
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    public static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        if (countR($data->rows)) {
            foreach ($data->rows as $i => &$row) {
                if ($row instanceof core_ET) {
                    continue;
                }
                
                $rec = &$data->recs[$i];
                if (empty($rec->quantity) && !Mode::isReadOnly()) {
                    $row->ROW_ATTR['style'] = ' background-color:#f1f1f1;color:#777';
                }
                
                // Показваме подробната информация за опаковката при нужда
                deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (($action == 'edit' || $action == 'delete' || $action == 'add') && isset($rec)) {
            if ($mvc->Master->fetchField($rec->{$mvc->masterKey}, 'state') != 'draft') {
                $requiredRoles = 'no_one';
            }
        }

        if ($action == 'createproduct' && isset($rec)) {
            $isReverse = $mvc->Master->fetchField($rec->{$mvc->masterKey}, 'isReverse');
            if($isReverse == 'yes'){
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * След извличане на записите от базата данни
     */
    public static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
    {
        $recs = &$data->recs;
        $orderRec = $data->masterData->rec;
        
        deals_Helper::fillRecs($mvc->Master, $recs, $orderRec);
    }
    
    
    /**
     * След подготовка на лист тулбара
     */
    public static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if (!empty($data->toolbar->buttons['btnAdd'])) {
            unset($data->toolbar->buttons['btnAdd']);
            $data->toolbar->addBtn('Артикул', array($mvc, 'add', $mvc->masterKey => $data->masterId, 'ret_url' => true), 'id=btnAdd, order=10,title=Добавяне на артикул,ef_icon = img/16/shopping.png');
        }
    }
    
    
    /**
     * Изчисляване на цена за опаковка на реда
     */
    public static function on_CalcPackPrice(core_Mvc $mvc, $rec)
    {
        if (!isset($rec->price) || !isset($rec->quantity) || empty($rec->quantityInPack)) {
            
            return;
        }
        
        $rec->packPrice = $rec->price * $rec->quantityInPack;
    }
    
    
    /**
     * Изчисляване на количеството на реда в брой опаковки
     */
    public static function on_CalcPackQuantity(core_Mvc $mvc, $rec)
    {
        if (!isset($rec->price) || !isset($rec->quantity) || empty($rec->quantityInPack)) {
            
            return;
        }
        
        $rec->packQuantity = $rec->quantity / $rec->quantityInPack;
    }
    
    
    /**
     * Изчисляване на сумата на реда
     */
    public static function on_CalcAmount(core_Mvc $mvc, $rec)
    {
        if (empty($rec->price) || empty($rec->quantity)) {
            
            return;
        }
        
        $rec->amount = $rec->price * $rec->quantity;
    }
    
    
    /**
     * Импортиране на артикул генериран от ред на csv файл
     *
     * @param int   $masterId - ид на мастъра на детайла
     * @param array $row      - Обект представляващ артикула за импортиране
     *                        ->code - код/баркод на артикула
     *                        ->quantity - К-во на опаковката или в основна мярка
     *                        ->price - цената във валутата на мастъра, ако няма се изчислява директно
     *                        ->pack - Опаковката
     *
     * @return mixed - резултата от експорта
     */
    public function import($masterId, $row)
    {
        $Master = $this->Master;

        $pRec = cat_Products::getByCode($row->code);
        $pRec->packagingId = (isset($pRec->packagingId)) ? $pRec->packagingId : $row->pack;
        $meta = cat_Products::fetch($pRec->productId, $this->metaProducts);
        $masterThreadId = $Master::fetchField($masterId, 'threadId');

        if (!$meta->metaProducts) {
            if (doc_Threads::getFirstDocument($masterThreadId)->className == 'sales_Sales') {
                $meta = $meta->canSell;
            } elseif (doc_Threads::getFirstDocument($masterThreadId)->className == 'purchase_Purchases') {
                $meta = $meta->canBuy;
            }
        }
        
        
        if ($meta != 'yes') {

            return;
        }

        $price = null;
        
        // Ако има цена я обръщаме в основна валута без ддс, спрямо мастъра на детайла
        if ($row->price) {
            
            $packRec = cat_products_Packagings::getPack($pRec->productId,  $pRec->packagingId);
            $quantityInPack = is_object($packRec) ? $packRec->quantity : 1;
            $row->price /= $quantityInPack;

            $vatExceptionId = cond_VatExceptions::getFromThreadId($masterThreadId);
            $masterRec = $Master->fetch($masterId);
            $price = deals_Helper::getPurePrice($row->price, cat_Products::getVat($pRec->productId, null, $vatExceptionId), $masterRec->currencyRate, $masterRec->chargeVat);
        }

        return $Master::addRow($masterId, $pRec->productId, $row->quantity, $price, $pRec->packagingId, null, null, null, null, $row->batch);
    }
}
