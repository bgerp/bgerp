<?php


/**
 * Абстрактен клас за наследяване от вътрешни складови документи
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class store_InternalDocumentDetail extends doc_Detail
{
    /**
     * Поле за артикула
     */
    public $productFieldName = 'productId';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'transUnitId';
    
    
    /**
     * Описание на модела (таблицата)
     */
    protected function setFields($mvc)
    {
        $mvc->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,maxSuggestions=100,forceAjax,titleFld=name)', 'class=w100,silent,caption=Продукт,notNull,mandatory,removeAndRefreshForm=packPrice|packagingId,tdClass=productCell leftCol wrap');
        $mvc->FLD('packagingId', 'key(mvc=cat_UoM, select=name)', 'caption=Мярка,after=productId,mandatory,tdClass=small-field nowrap,smartCenter,input=hidden');
        $mvc->FLD('quantityInPack', 'double(decimals=2)', 'input=none,column=none');
        $mvc->FLD('packQuantity', 'double(Min=0)', 'caption=Количество,input=input,mandatory,smartCenter');
        $mvc->FLD('packPrice', 'double(minDecimals=2)', 'caption=Цена,input,smartCenter');
        $mvc->FNC('amount', 'double(minDecimals=2,maxDecimals=2)', 'caption=Сума,input=none');
        $mvc->FNC('quantity', 'double(minDecimals=2,maxDecimals=2)', 'caption=К-во,input=none');
    }
    
    
    /**
     * Изчисляване на сумата на реда
     */
    public static function on_CalcQuantity(core_Mvc $mvc, $rec)
    {
        if (empty($rec->quantityInPack) || empty($rec->packQuantity)) {
            
            return;
        }
        
        $rec->quantity = $rec->packQuantity * $rec->quantityInPack;
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
        
        if(isset($rec->id)){
            $data->form->setReadOnly('productId');
        }
        
        $rec->chargeVat = (cls::get($masterRec->contragentClassId)->shouldChargeVat($masterRec->contragentId)) ? 'yes' : 'no';
        $chargeVat = ($rec->chargeVat == 'yes') ? 'с ДДС' : 'без ДДС';
        
        $data->form->setField('packPrice', "unit={$masterRec->currencyId} {$chargeVat}");
        $data->form->setFieldTypeParams('productId', array('customerClass' => $masterRec->contragentClassId, 'customerId' => $masterRec->contragentId, 'hasProperties' => $mvc->metaProducts, 'hasnotProperties4' => 'generic'));
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form &$form)
    {
        $rec = &$form->rec;
        
        $masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey});
        $currencyRate = $rec->currencyRate = currency_CurrencyRates::getRate($masterRec->valior, $masterRec->currencyId, acc_Periods::getBaseCurrencyCode($masterRec->valior));
        
        if (!$currencyRate) {
            $form->setError('currencyRate', 'Не може да се изчисли курс');
        }
        
        if ($form->rec->productId) {
            $packs = cat_Products::getPacks($rec->productId);
            $form->setField('packagingId', 'input');
            $form->setOptions('packagingId', $packs);
            $form->setDefault('packagingId', key($packs));
            
            // Слагаме цената от политиката за последна цена
            if (isset($mvc->LastPricePolicy)) {
                $policyInfoLast = $mvc->LastPricePolicy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->packagingId, $rec->packQuantity, $masterRec->valior, $currencyRate, $rec->chargeVat);
                if ($policyInfoLast->price != 0) {
                    $form->setSuggestions('packPrice', array('' => '', "{$policyInfoLast->price}" => $policyInfoLast->price));
                }
            }
        }
        
        if ($form->isSubmitted()) {
            if(isset($rec->productId)){
                $productInfo = cat_Products::getProductInfo($rec->productId);
                
                // Ако артикула няма опаковка к-то в опаковка е 1, ако има и вече не е свързана към него е това каквото е било досега, ако още я има опаковката обновяваме к-то в опаковка
                $rec->quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
                
                $autoPrice = false;
                
                if (!isset($rec->packPrice)) {
                    $autoPrice = true;
                    $Policy = cls::get('price_ListToCustomers');
                    
                    $packPrice = $Policy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->packagingId, $rec->packQuantity * $rec->quantityInPack, $masterRec->valior, $currencyRate, $rec->chargeVat)->price;
                    if (isset($packPrice)) {
                        $rec->packPrice = $packPrice * $rec->quantityInPack;
                    }
                }
            }
            
            if (!isset($rec->packPrice) && (Request::get('Act') != 'CreateProduct')) {
                $form->setError('packPrice', 'Продуктът няма цена в избраната ценова политика (3)');
            }
            
            // Проверка на цената
            $quantity = $rec->packQuantity * $rec->quantityInPack;
            $msg = null;
            if (!deals_Helper::isPriceAllowed($rec->packPrice, $quantity, $autoPrice, $msg)) {
                $form->setError('packPrice,packQuantity', $msg);
            }
            
            if ($form->gotErrors()) {
                if ($autoPrice === true) {
                    unset($rec->packPrice);
                }
            }
        }
    }
    
    
    /**
     * Изчисляване на сумата на реда
     */
    public static function on_CalcAmount(core_Mvc $mvc, $rec)
    {
        if (empty($rec->packPrice) || empty($rec->packQuantity)) {
            
            return;
        }
        
        $rec->amount = $rec->packPrice * $rec->packQuantity;
    }
    
    
    /**
     * След обработка на записите от базата данни
     */
    public static function on_AfterPrepareListRows(core_Mvc $mvc, $data)
    {
        if (!countR($data->rows)) {
            
            return;
        }
        $unsetAmounts = true;
        
        foreach ($data->rows as $i => &$row) {
            $rec = &$data->recs[$i];
            $row->productId = cat_Products::getAutoProductDesc($rec->productId, null, 'short', 'internal');
            deals_Helper::addNotesToProductRow($row->productId, $rec->notes);
            
            // Показваме подробната информация за опаковката при нужда
            deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
            if (!empty($rec->packPrice)) {
                $unsetAmounts = false;
            }
        }
        
        if ($unsetAmounts === true) {
            unset($data->listFields['packPrice']);
            unset($data->listFields['amount']);
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (($action == 'edit' || $action == 'delete' || $action == 'add' || $action == 'createproduct') && isset($rec)) {
            if (!($mvc instanceof store_DocumentPackagingDetail)) {
                if ($mvc->Master->fetchField($rec->{$mvc->masterKey}, 'state') != 'draft') {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * След рендиране на детайла
     */
    public static function on_AfterRenderDetail($mvc, &$tpl, $data)
    {
        // Ако документа е активиран и няма записи съответния детайл не го рендираме
        if ($data->masterData->rec->state != 'draft' && $data->masterData->rec->state != 'pending' && !$data->rows) {
            $tpl = new ET('');
        }
    }
    
    
    /**
     * Метод по пдоразбиране на getRowInfo за извличане на информацията от реда
     */
    public static function on_AfterGetRowInfo($mvc, &$res, $rec)
    {
        $rec = $mvc->fetchRec($rec);
        
        $res->quantity = $rec->packQuantity * $rec->quantityInPack;
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
        $pacRec = cat_products_Packagings::getPack($pRec->productId, $pRec->packagingId);
        $quantityInPack = (is_object($pacRec)) ? $pacRec->quantity : 1;
        
        $masterRec = $Master->fetch($masterId);
        $chargeVat = (cls::get($masterRec->contragentClassId)->shouldChargeVat($masterRec->contragentId)) ? 'yes' : 'no';
        $currencyRate = currency_CurrencyRates::getRate($masterRec->valior, $masterRec->currencyId, acc_Periods::getBaseCurrencyCode($masterRec->valior));
        
        // Ако има цена я обръщаме в основна валута без ддс, спрямо мастъра на детайла
        if ($row->price) {
            $price = deals_Helper::getPurePrice($row->price, cat_Products::getVat($pRec->productId), $currencyRate, $chargeVat);
        } else {
            $Policy = cls::get('price_ListToCustomers');
            $policyInfo = $Policy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $pRec->productId, $pRec->packagingId, ($row->quantity * $quantityInPack), $masterRec->valior, $currencyRate, $chargeVat);
            $price = $policyInfo->price;
        }
        
        $price *= $quantityInPack;
        $dRec = (object)array('protocolId' => $masterId, 'productId' => $pRec->productId, 'packagingId' => $pRec->packagingId, 'packPrice' => $price, 'packQuantity' => $row->quantity, 'quantityInPack' => $quantityInPack);
        
        return self::save($dRec);
    }
}
