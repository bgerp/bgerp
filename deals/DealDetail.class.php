<?php


/**
 * Клас 'deals_DealDetail'
 *
 * Клас за наследяване от детайли на бизнес документи(@see deals_DealDetail)
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class deals_DealDetail extends doc_Detail
{
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'discount,reff';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'tolerance,term,autoDiscount,inputDiscount';
    

    /**
     * Изчисляване на сумата на реда
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public static function on_CalcAmount(core_Mvc $mvc, $rec)
    {
        if (empty($rec->price) || empty($rec->quantity)) {
            
            return;
        }
        
        $rec->amount = $rec->price * $rec->quantity;
    }
    
    
    /**
     * Изчисляване на цена за опаковка на реда
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public static function on_CalcPackPrice(core_Mvc $mvc, $rec)
    {
        if (!isset($rec->price) || empty($rec->quantity) || empty($rec->quantityInPack)) {
            
            return;
        }
        
        $rec->packPrice = $rec->price * $rec->quantityInPack;
    }
    
    
    /**
     * Изчисляване на количеството на реда в брой опаковки
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public static function on_CalcPackQuantity(core_Mvc $mvc, $rec)
    {
        if (empty($rec->quantity) || empty($rec->quantityInPack)) {
            
            return;
        }
        
        $rec->packQuantity = $rec->quantity / $rec->quantityInPack;
    }
    
    
    /**
     * След описанието на полетата
     */
    public static function getDealDetailFields(&$mvc)
    {
        $mvc->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,maxSuggestions=100,forceAjax,titleFld=name,forceOpen)', 'class=w100,caption=Артикул,notNull,mandatory', 'tdClass=productCell leftCol wrap,silent,removeAndRefreshForm=packPrice|discount|packagingId|tolerance|batch');
        $mvc->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Мярка', 'smartCenter,tdClass=small-field nowrap,silent,removeAndRefreshForm=packPrice|discount,mandatory,input=hidden');
        
        // Количество в основна мярка
        $mvc->FLD('quantity', 'double', 'caption=Количество (Основна),input=none');
        
        // Количество (в осн. мярка) в опаковката, зададена от 'packagingId'; Ако 'packagingId'
        // няма стойност, приема се за единица.
        $mvc->FLD('quantityInPack', 'double', 'input=none');
        
        // Цена за единица продукт в основна мярка
        $mvc->FLD('price', 'double', 'caption=Цена,input=none');
        
        // Брой опаковки (ако има packagingId) или к-во в основна мярка (ако няма packagingId)
        $mvc->FNC('packQuantity', 'double(Min=0)', 'caption=Количество,input,smartCenter');
        $mvc->FNC('amount', 'double(minDecimals=2,maxDecimals=2)', 'caption=Сума');
        
        // Цена за опаковка (ако има packagingId) или за единица в основна мярка (ако няма packagingId)
        $mvc->FNC('packPrice', 'double(minDecimals=2)', 'caption=Цена,input,smartCenter');
        $mvc->FLD('discount', 'percent(min=0,max=1,suggestions=5 %|10 %|15 %|20 %|25 %|30 %)', 'caption=Отстъпка,smartCenter,tdClass=small-field');
        $mvc->setFieldTypeParams('discount', array('warningMax' => deals_Setup::get('MAX_WARNING_DISCOUNT')));
        
        $mvc->FLD('tolerance', 'percent(min=0,max=1,decimals=0,warningMax=0.1)', 'caption=Толеранс,input=none');
        $mvc->FLD('term', 'time(uom=days,suggestions=1 ден|5 дни|7 дни|10 дни|15 дни|20 дни|30 дни)', 'caption=Срок,after=tolerance,before=showMode,input=none');
        
        $mvc->FLD('showMode', 'enum(auto=По подразбиране,detailed=Разширен,short=Съкратен)', 'caption=Допълнително->Изглед,notNull,default=auto');
        $mvc->FLD('notes', 'richtext(rows=3,bucket=Notes)', 'caption=Допълнително->Забележки');

        $mvc->FLD('autoDiscount', 'percent(min=0,max=1)', 'caption=Авт. отстъпка,input=none');
        $mvc->FLD('inputDiscount', 'percent(min=0,max=1)', 'caption=Ръчна отстъпка,input=none');

        // За по-бързо преброяване на Usage
        $mvc->setDbIndex('productId');
        setIfNot($mvc->quantityFld, 'quantity');
        setIfNot($mvc->allowInputPriceForQuantity, false);
    }
    
    
    /**
     * След описанието
     */
    public static function on_AfterDescription(&$mvc)
    {
        // Скриване на полетата за създаване
        $mvc->setField('createdOn', 'column=none');
        $mvc->setField('createdBy', 'column=none');
        setIfNot($mvc->combineSameRecsWhenImport, false);
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (($action == 'delete' || $action == 'add' || $action == 'edit' || $action == 'import' || $action == 'createproduct' || $action == 'importlisted') && isset($rec)) {
            $state = $mvc->Master->fetchField($rec->{$mvc->masterKey}, 'state');
            if ($state != 'draft') {
                $requiredRoles = 'no_one';
            }
        }

        if ($action == 'importlisted' && isset($rec)) {
            if ($requiredRoles != 'no_one') {
                if (isset($rec)) {
                    $masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey}, 'contragentClassId,contragentId');
                    
                    if ($masterRec->contragentClassId && $masterRec->contragentId) {
                        $param = ($mvc->Master instanceof sales_Sales) ? 'salesList' : 'purchaseList';
                        $param = cond_Parameters::getParameter($masterRec->contragentClassId, $masterRec->contragentId, $param);
                        if (!isset($param)) {
                            $requiredRoles = 'no_one';
                        }
                    } else {
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }
        
        if ($action == 'add' && $requiredRoles != 'no_one' && haveRole('partner', $userId)) {
            $listSysId = ($mvc instanceof sales_SalesDetails) ? 'salesList' : 'purchaseList';
            $masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey}, 'contragentClassId,contragentId');
            if (!cond_Parameters::getParameter($masterRec->contragentClassId, $masterRec->contragentId, $listSysId)) {
                $requiredRoles = 'no_one';
            }
        }

        // Да се показва ли бутона за клониране от оригинала
        if($action == 'copydetailsfromcloned' && isset($rec)){
            $masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey}, 'clonedFromId,state');
            if(empty($masterRec->clonedFromId) || $masterRec->state != 'draft'){
                $requiredRoles = 'no_one';
            } elseif(!$mvc->areTheDetailsDifferent($rec->{$mvc->masterKey}, $masterRec->clonedFromId)){
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * След извличане на записите от базата данни
     */
    public static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
    {
        if (empty($data->recs)) return;
        $recs = &$data->recs;
        deals_Helper::fillRecs($mvc->Master, $recs, $data->masterData->rec);
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        $masterRec = $data->masterRec;

        $form->fields['packPrice']->unit = '|*' . $masterRec->currencyId . ', ';
        $form->fields['packPrice']->unit .= ($masterRec->chargeVat == 'yes') ? '|с ДДС|*' : '|без ДДС|*';

        // Ако ще се позволява въвеждането на цена за к-то - полето за цена става varchar
        if($mvc->allowInputPriceForQuantity){
            $form->setFieldType('packPrice', 'varchar(nullIfEmpty)');
            $form->setField('packPrice', 'class=w25');
        }

        // Добавяне да се показва ценовата информация за стандартните артикули
        $priceData = array('valior' => $masterRec->valior, 'rate' => $masterRec->currencyRate, 'chargeVat' => $masterRec->chargeVat, 'listId' => $masterRec->priceListId, 'currencyId' => $masterRec->currencyId, 'threadId' => $masterRec->threadId);
        $form->setFieldTypeParams('productId', array('customerClass' => $masterRec->contragentClassId, 'customerId' => $masterRec->contragentId, 'hasProperties' => $mvc->metaProducts, 'hasnotProperties' => 'generic', 'priceData' => $priceData));

        if (empty($rec->id)) {
            $listSysId = ($mvc instanceof sales_SalesDetails) ? 'salesList' : 'purchaseList';
            $listId = cond_Parameters::getParameter($masterRec->contragentClassId, $masterRec->contragentId, $listSysId);

            // Ако потребителя е партньор и има листвани артикули за контрагента
            if (haveRole('partner')) {
                $form->setFieldTypeParams('productId', array('listId' => $listId, 'selectSourceArr' => 'cat_Listings::getProductOptions'));
            } else {
                $form->setFieldTypeParams('productId', array('listId' => $listId));
            }
        } else {
            $form->setReadOnly('productId');
        }

        $vatExceptionId = cond_VatExceptions::getFromThreadId($masterRec->threadId);
        if (!empty($rec->packPrice)) {
            if (strtolower(Request::get('Act')) != 'createproduct') {
                $vat = cat_Products::getVat($rec->productId, $masterRec->valior, $vatExceptionId);
            } else {
                $vat = acc_Periods::fetchByDate($masterRec->valior)->vatRate;
            }
            
            $rec->packPrice = deals_Helper::getDisplayPrice($rec->packPrice, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
        }

        // Показване на толеранс аи срока на доставка, ако има
        if (isset($rec->productId) && !core_Users::haveRole('partner')) {
            if (cat_Products::getTolerance($rec->productId, 1)) {
                $form->setField('tolerance', 'input');
            }
            
            if (cat_Products::getDeliveryTime($rec->productId, 1)) {
                $form->setField('term', 'input');
            }
        }
        
        if (core_Users::haveRole('partner')) {
            $form->setField('packPrice', 'input=none');
            $form->setField('tolerance', 'input=none');
            $form->setField('discount', 'input=none');
            
            $mvc->currentTab = 'Нишка';
            plg_ProtoWrapper::changeWrapper($mvc, 'cms_ExternalWrapper');
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
        
        if ($rec->productInfo) {
            $productInfo = $rec->productInfo;
        } elseif ($rec->productId) {
            $productInfo = cat_Products::getProductInfo($rec->productId);
        }
        
        if ($rec->productId) {
            $vatExceptionId = cond_VatExceptions::getFromThreadId($masterRec->threadId);
            $vat = cat_Products::getVat($rec->productId, $masterRec->valior, $vatExceptionId);
            $packs = cat_Products::getPacks($rec->productId, $rec->packagingId);
            $form->setOptions('packagingId', $packs);
            $form->setDefault('packagingId', key($packs));
            $form->setField('packagingId', 'input');

            if (isset($mvc->LastPricePolicy)) {
                $policyInfoLast = $mvc->LastPricePolicy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->packagingId, $rec->packQuantity, $masterRec->valior, $masterRec->currencyRate, $masterRec->chargeVat);
                if ($policyInfoLast->price != 0) {
                    $form->setSuggestions('packPrice', array('' => '', "{$policyInfoLast->price}" => $policyInfoLast->price));
                }
            }

            if (Request::get('Act') != 'CreateProduct') {

                // Сетване на предупреждение ако реда се дублира
                if(empty($rec->id)){
                    $setWarning = deals_Setup::get('WARNING_ON_DUPLICATED_ROWS');
                    if($setWarning == 'yes'){
                        $countSameProduct = $mvc->count("#{$mvc->masterKey} = '{$rec->{$mvc->masterKey}}' AND #id != '{$rec->id}' AND #productId = {$rec->productId}");
                        if ($countSameProduct) {
                            $form->setWarning('productId', 'Артикулът вече присъства на друг ред в документа|*!');
                        }
                    }
                }
            }
        }
        
        if ($form->isSubmitted() && !$form->gotErrors()) {

            // Ако е партньор се маха вече изчислената цена за да се изчисли наново
            if (core_Users::haveRole('partner')) {
                unset($form->rec->packPrice);
                unset($form->rec->price);
            }
            
            // Извличане на информация за продукта - количество в опаковка, единична цена
            if (!isset($rec->packQuantity)) {
                $defaultPackQuantity = (isset($rec->_moq)) ? $rec->_moq : deals_Helper::getDefaultPackQuantity($rec->productId, $rec->packagingId);
                $form->setDefault('packQuantity', $defaultPackQuantity);
                if(empty($defaultPackQuantity)){
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
            $rec->quantity = $rec->packQuantity * $rec->quantityInPack;
            
            // Проверка дали к-то е под МКП
            $action = ($mvc instanceof sales_SalesDetails) ? 'sell' : 'buy';
            deals_Helper::isQuantityBellowMoq($form, $rec->productId, $rec->quantity, $rec->quantityInPack, 'packQuantity', $action);

            if (!isset($rec->packPrice)) {
                $Policy = (isset($mvc->Policy)) ? $mvc->Policy : cls::get('price_ListToCustomers');
                
                if ($rec->productId) {
                    $listId = ($masterRec->priceListId) ? $masterRec->priceListId : null;
                    $policyInfo = $Policy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->packagingId, $rec->quantity, $masterRec->valior, $masterRec->currencyRate, $masterRec->chargeVat, $listId);

                    if (!isset($policyInfo->price)) {
                        $form->setError('packPrice', $Policy->notFoundPriceErrorMsg);
                    } else {
                        
                        // Ако се обновява запис се взима цената от него, ако не от политиката
                        $price = $policyInfo->price;
                        if ($policyInfo->discount && !isset($rec->discount)) {
                            $rec->discount = $policyInfo->discount;
                        }
                        $rec->autoPrice = true;
                    }
                }
            } else {
                $error = $price4Quantity = null;
                if($mvc->allowInputPriceForQuantity){
                    $price4Quantity = deals_Helper::isPrice4Quantity($rec->packPrice, $rec->quantity, $error);
                    if(!empty($error)){
                        $form->setError('packPrice', $error);
                        return;
                    }
                }

                $price = $price4Quantity ?? ($rec->packPrice / $rec->quantityInPack);

                if (!$form->gotErrors() || ($form->gotErrors() && Request::get('Ignore'))) {
                    $rec->packPrice = deals_Helper::getPurePrice($rec->packPrice, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
                }
            }
            
            // Проверка на цената
            $msg = null;
            if (!deals_Helper::isPriceAllowed($price, $rec->quantity, $rec->autoPrice, $msg)) {
                $form->setError('packPrice,packQuantity', $msg);
            }

            if(!$form->gotErrors()){
                $price = deals_Helper::getPurePrice($price, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
                $rec->price = $price;
            }

            // При редакция, ако е променена опаковката слагаме преудпреждение
            if ($rec->id) {
                $oldRec = $mvc->fetch($rec->id);
                if ($oldRec && $rec->packagingId != $oldRec->packagingId && !empty($rec->packPrice) && round($rec->packPrice, 4) == round($oldRec->packPrice, 4)) {
                    $form->setWarning('packPrice,packagingId', 'Опаковката е променена без да е променена цената|*.<br />|Сигурни ли сте, че зададената цена отговаря на новата опаковка|*?');
                }
            }
        }
    }
    
    
    /**
     * Преди подготовка на полетата за показване в списъчния изглед
     */
    public static function on_AfterPrepareListRows($mvc, $data)
    {
        if (!countR($data->recs)) {
            
            return;
        }
        
        $recs = &$data->recs;
        $rows = &$data->rows;
        $masterRec = $data->masterData->rec;
        
        core_Lg::push($masterRec->tplLang);
        $date = ($masterRec->state == 'draft') ? null : $masterRec->modifiedOn;
        
        foreach ($rows as $id => &$row) {
            $rec = $recs[$id];
            core_RowToolbar::createIfNotExists($row->_rowTools);
            cat_Products::addButtonsToDocToolbar($rec->productId, $row->_rowTools, $mvc->className, $id);
            $row->productId = cat_Products::getAutoProductDesc($rec->productId, $date, $rec->showMode, 'public', $masterRec->tplLang);
            
            deals_Helper::addNotesToProductRow($row->productId, $rec->notes);
        }
        
        core_Lg::pop();
    }
    
    
    /**
     * След подготовка на лист тулбара
     */
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
        if (!empty($data->toolbar->buttons['btnAdd'])) {
            $masterRec = $data->masterData->rec;
            
            if (!countR(cat_Products::getProducts($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->valior, $mvc->metaProducts, null, 1))) {
                $error = 'error=Няма продаваеми артикули, ';
            }
            
            $data->toolbar->addBtn(
                
                'Артикул',
                
                array($mvc, 'add', "{$mvc->masterKey}" => $masterRec->id, 'ret_url' => true),
            "id=btnAdd-{$masterRec->id},{$error} order=10,title=Добавяне на артикул",
                
                'ef_icon = img/16/shopping.png'
            
            );
            
            unset($data->toolbar->buttons['btnAdd']);
        }
        
        if ($mvc->haveRightFor('importlisted', (object) array("{$mvc->masterKey}" => $data->masterId))) {
            $data->toolbar->addBtn('Списък', array($mvc, 'importlisted', "{$mvc->masterKey}" => $data->masterId, 'ret_url' => true), "id=btnAddImp-{$data->masterId},order=14,title=Добавяне на артикули от списък", 'ef_icon = img/16/shopping.png');
        }

        if ($mvc->haveRightFor('copydetailsfromcloned', (object) array("{$mvc->masterKey}" => $data->masterId))) {
            $clonedFromHandle = $data->masterMvc->getHandle($data->masterData->rec->clonedFromId);
            $data->toolbar->addBtn("От|* #{$clonedFromHandle}", array($mvc, 'copydetailsfromcloned', "{$mvc->masterKey}" => $data->masterId, 'ret_url' => true), "id=btnCloneImp-{$data->masterId},order=22,warning222222222222=Наистина ли желаете да копирате 1:1 артикулите и техните цени от клонирания документ?,title=Налични са разлики (актуализирани цени или други) спрямо клонирания договор! Копиране 1:1 от оригинал?", 'ef_icon = img/16/shopping.png');
        }
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    public static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $recs = &$data->recs;
        
        if (!countR($recs)) {
            
            return;
        }
        
        if (countR($data->rows)) {
            foreach ($data->rows as $i => &$row) {
                $rec = $data->recs[$i];
                
                $toleranceRow = deals_Helper::getToleranceRow($rec->tolerance, $rec->productId, $rec->quantity);
                if ($toleranceRow) {
                    $row->packQuantity .= "<small style='font-size:0.8em;display:block;' class='quiet'>±{$toleranceRow}</small>";
                }
                
                // Показваме подробната информация за опаковката при нужда
                deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
            }
        }
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
        $meta = cat_Products::fetchField($pRec->productId, $this->metaProducts);
        if ($meta != 'yes') {
            
            return;
        }
        
        $price = null;
        
        // Ако има цена я обръщаме в основна валута без ддс, спрямо мастъра на детайла
        if ($row->price) {
            $packRec = cat_products_Packagings::getPack($pRec->productId, $pRec->packagingId);
            $quantityInPack = is_object($packRec) ? $packRec->quantity : 1;
            $row->price /= $quantityInPack;
            
            $masterRec = $Master->fetch($masterId);
            $vatExceptionId = cond_VatExceptions::getFromThreadId($masterRec->threadId);
            $price = deals_Helper::getPurePrice($row->price, cat_Products::getVat($pRec->productId, null, $vatExceptionId), $masterRec->currencyRate, $masterRec->chargeVat);
        }

        return $Master::addRow($masterId, $pRec->productId, $row->quantity, $price, $pRec->packagingId, null, null, null, null, $row->batch);
    }
    
    
    /**
     * Импорт на списък от артикули
     */
    public function act_Importlisted()
    {
        // Проверка на права
        $this->requireRightFor('importlisted');
        expect($saleId = Request::get($this->masterKey, 'int'));
        expect($saleRec = $this->Master->fetch($saleId));
        $this->requireRightFor('importlisted', (object) array("{$this->masterKey}" => $saleId));
        
        // Инстанциране на формата за добавяне
        $form = cls::get('core_Form');
        $form->title = 'Импорт на списък към|* ' . $this->Master->getHyperlink($saleId, true);
        $form->method = 'POST';
        
        // Намират се всички листвани артикули
        $param = ($this->Master instanceof sales_Sales) ? 'salesList' : 'purchaseList';
        expect($listId = cond_Parameters::getParameter($saleRec->contragentClassId, $saleRec->contragentId, $param));
        $form->info = tr('|Списък за листване|*:') . cat_Listings::getLink($listId, 0);
        
        $listed = cat_Listings::getAll($listId, $saleRec->shipmentStoreId, 50, true);
        if(!countR($listed)) followRetUrl(null, "|В избрания списък няма активни артикули|*: " . cat_Listings::getLink($listId, 0, array('ef_icon' => false)), 'warning');

        $form->info .= tr('|* ( |Показване на първите|* <b>50</b> |артикула|* )');

        // И всички редове от продажбата
        $query = $this->getQuery();
        $query->where("#{$this->masterKey} = {$saleId}");
        $recs = $query->fetchAll();

        foreach ($listed as &$list) {
            $list->code = cat_Products::getVerbal($list->productId, 'code');
        }
        
        arr::sortObjects($listed, 'code', 'asc', 'stri');
        
        // Подготовка на полетата на формата
        $this->prepareImportListForm($form, $listed, $recs, $saleRec);
        $form->input();
        
        // Ако формата е събмитната
        if ($form->isSubmitted()) {
            $rec = $form->rec;
            $Policy = ($this->Master instanceof sales_Sales) ? 'price_ListToCustomers' : 'purchase_PurchaseLastPricePolicy';
            $Policy = cls::get($Policy);
            
            // Подготовка на записите
            $error = $error2 = $warnings = $toSave = $toUpdate = $multiError = array();
            foreach ($listed as $lId => $lRec) {

                $packQuantity = $rec->{"quantity{$lId}"};
                $quantityInPack = $rec->{"quantityInPack{$lId}"};
                $recId = $rec->{"rec{$lId}"};
                $quantity = $packQuantity * $quantityInPack;
                $productId = $rec->{"productId{$lId}"};
                $packagingId = $rec->{"packagingId{$lId}"};
                $packPrice = $discount = null;
                
                // Ако няма к-во пропускане на реда
                if (empty($packQuantity)) {
                    continue;
                }


                if (!isset($rec->id)) {
                    $listId = ($saleRec->priceListId) ? $saleRec->priceListId : null;
                    
                    $policyInfo = (isset($lRec->price)) ? (object) array('price' => $lRec->price) : $Policy->getPriceInfo($saleRec->contragentClassId, $saleRec->contragentId, $productId, $packagingId, $quantity, $saleRec->valior, $saleRec->currencyRate, $saleRec->chargeVat, $listId);
                    
                    if (!isset($policyInfo->price)) {
                        $error[$lId] = "quantity{$lId}";
                    } else {
                        $vatExceptionId = cond_VatExceptions::getFromThreadId($saleRec->threadId);
                        $vat = cat_Products::getVat($productId, $saleRec->valior, $vatExceptionId);
                        if (isset($lRec->price)) {
                            $price = $lRec->price / $quantityInPack;
                        } else {
                            $price = deals_Helper::getPurePrice($policyInfo->price, $vat, $saleRec->currencyRate, $saleRec->chargeVat);
                        }
                        
                        $packPrice = $price * $quantityInPack;
                        $discount = $policyInfo->discount;
                    }
                }
                
                $warning = null;
                if (!deals_Helper::checkQuantity($packagingId, $packQuantity, $warning)) {
                    $warnings[$warning][] = "quantity{$lId}";
                }
                
                if (isset($lRec->moq) && $packQuantity < $lRec->moq) {
                    $error2[$lId] = "quantity{$lId}";
                }
                
                if (isset($lRec->multiplicity)) {
                    if (core_Math::fmod($packQuantity, $lRec->multiplicity) != 0) {
                        $multiError[$lId] = "quantity{$lId}";
                    }
                }
                
                // Ако няма грешка със записа
                if (!array_key_exists($lId, $error)) {
                    $obj = (object) array('quantity' => $packQuantity * $quantityInPack,
                        'quantityInPack' => $quantityInPack,
                        'price' => $packPrice / $quantityInPack,
                        'discount' => $discount,
                        'productId' => $productId,
                        'packagingId' => $packagingId,
                        'id' => $recId,
                        "{$this->masterKey}" => $saleRec->id,
                    );
                    
                    // Определяне дали ще се добавя или обновява
                    if (isset($obj->id)) {
                        $toUpdate[] = $obj;
                    } else {
                        $toSave[] = $obj;
                    }
                }
            }
            
            if (countR($error2)) {
                if (haveRole('powerUser')) {
                    $form->setWarning(implode(',', $error2), 'Количеството е под МКП');
                } else {
                    $form->setError(implode(',', $error2), 'Количеството е под МКП');
                }
            }
            
            // Ако има грешка сетва се ерор
            if (countR($error)) {
                $form->setError(implode(',', $error), 'Артикулът няма цена');
            }
            
            if (countR($warnings)) {
                foreach ($warnings as $msg => $fields) {
                    $form->setWarning(implode(',', $fields), $msg);
                }
            }
            
            if (countR($multiError)) {
                if (haveRole('salesMaster,ceo')) {
                    $form->setWarning(implode(',', $multiError), 'Количеството не е кратно на очакваното');
                } else {
                    $form->setError(implode(',', $multiError), 'Количеството не е кратно на очакваното');
                }
            }
            
            if (!countR($error) && (!countR($error2) || (countR($error2) && Request::get('Ignore'))) && (!countR($multiError) || (countR($multiError) && Request::get('Ignore')))) {

                $msg = null;
                $logText = "Импортиране на списък без промяна";

                // Запис на обновените записи
                if (countR($toUpdate)) {
                    $hasChangedQuantity = false;
                    foreach ($toUpdate as $uRec) {
                        if($hasChangedQuantity === false){
                            $oldQuantity = $this->fetchField($uRec->id, 'quantity');
                            if(trim($oldQuantity) != trim($uRec->quantity)){
                                $hasChangedQuantity = true;
                            }
                        }

                        $uRec->isEdited = true;
                        $this->save($uRec, 'id,quantity');
                    }

                    if($hasChangedQuantity){
                        $msg = "Списъкът е импортиран успешно";
                        $logText = "Импортиране на артикули от списък";
                    }
                }
                
                if (countR($toSave)) {
                    foreach ($toSave as $saveRec) {
                        $this->save($saveRec);
                    }
                    $msg = "Списъкът е импортиран успешно";
                    $logText = "Импортиране на артикули от списък";
                }
                
                $this->Master->invoke('AfterUpdateDetail', array($saleId, $this));
                $this->Master->logWrite($logText, $saleId);

                // Редирект към продажбата
                followRetUrl(null, $msg);
            }
        }
        
        // Добавяне на тулбар
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png, title = Импорт');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        // Рендиране на опаковката
        $tpl = $this->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($tpl, $form);
        $this->logInAct('Разглеждане на импортиране на артикули от списък', $saleId);

        return $tpl;
    }
    
    
    /**
     * Подготовка на полетата към формата за листвани артикули
     *
     * @param core_Form $form
     * @param array     $listed
     * @param array     $recs
     * @param stdClass  $saleRec
     *
     * @return bool void
     */
    private function prepareImportListForm(&$form, $listed, $recs, $saleRec)
    {
        // За всеки листван артикул
        foreach ($listed as $lId => $lRec) {
            $pRec = cat_Products::fetch($lRec->productId, "{$this->metaProducts},isPublic,folderId");
            if ($pRec->{$this->metaProducts} != 'yes') continue;

            // Към кои папки е споделен артикула
            $sharedQuery = cat_products_SharedInFolders::getQuery();
            $sharedQuery->where("#productId = {$pRec->id}");
            $sharedFolders = arr::extractValuesFromArray($sharedQuery->fetchAll(), 'folderId');
            if(countR($sharedFolders)){

                // Ако не е споделен в конкретната папка или е в папка различна от тази на сделката не се показва
                if(!array_key_exists($saleRec->folderId, $sharedFolders) && $pRec->folderId != $saleRec->folderId) continue;
            } else {

                // Ако няма споделени папки и е нестандартен и е в друга папка от тази на сделката не се показва
                if($pRec->isPublic == 'no' && $pRec->folderId != $saleRec->folderId) continue;
            }

            $title = cat_Products::getTitleById($lRec->productId);
            $title = str_replace(',', ' ', $title);
            $title = str_replace('=', ' ', $title);

            if($lRec->reff != $lRec->code){
                $title = "[{$lRec->reff}] {$title}";
            }

            $caption = '|' . $title . '|*';
            $caption .= ': | ' . cat_UoM::getShortName($lRec->packagingId);
            
            // Проверка дали вече не просъства в продажбата
            $res = array_filter($recs, function (&$e) use ($lRec) {
                if ($e->productId == $lRec->productId && $e->packagingId == $lRec->packagingId && !isset($e->batch) && !isset($e->tolerance) && !isset($e->term)) {
                    
                    return true;
                }
                
                return false;
            });
            
            $key = key($res);
            $exRec = $res[$key];
            
            // Подготовка на полета за всеки артикул
            $form->FLD("productId{$lId}", 'int', 'К-во,input=hidden');
            $form->FLD("packagingId{$lId}", 'int', 'К-во,input=hidden');
            $form->FLD("rec{$lId}", 'int', 'input=hidden');
            $form->FLD("quantityInPack{$lId}", 'double', 'input=hidden');
            $form->FLD("quantity{$lId}", 'double(Min=0)', "caption={$caption}->Количество");
            $form->setDefault("productId{$lId}", $lRec->productId);
            $form->setDefault("packagingId{$lId}", $lRec->packagingId);
            
            $unit = '';
            if (isset($lRec->moq)) {
                $moq = cls::get('type_Double', array('params' => array('smartRound' => true)))->toVerbal($lRec->moq);
                $unit = "<i>|МКП||MOQ|* <b>{$moq}</b></i>";
            }
            
            if (isset($lRec->multiplicity)) {
                $multiplicity = cls::get('type_Double', array('params' => array('smartRound' => true)))->toVerbal($lRec->multiplicity);
                $unit .= (($unit) ? ', ' : ' ') . "|кратно на|* <b>{$multiplicity}</b>";
            }
            
            if ($unit != '') {
                $form->setField("quantity{$lId}", array('unit' => "|*{$unit}"));
            }
            
            // Ако иам съшествуващ запис, попълват му се стойностите
            if (isset($exRec)) {
                $form->setDefault("rec{$lId}", $exRec->id);
                $form->setDefault("quantity{$lId}", $exRec->packQuantity);
                $form->setDefault("quantityInPack{$lId}", $exRec->quantityInPack);
            }
            
            // Задаване на к-то в опаковката
            $packRec = cat_products_Packagings::getPack($lRec->productId, $lRec->packagingId);
            $quantityInPack = is_object($packRec) ? $packRec->quantity : 1;
            $form->setDefault("quantityInPack{$lId}", $quantityInPack);
        }
    }


    /**
     * Помощна ф-я сравняваща има ли разлика между детайлите на две сделки
     *
     * @param int $master1Id
     * @param int $master2Id
     * @return bool
     */
    private function areTheDetailsDifferent($master1Id, $master2Id)
    {
        $arr1 = $arr2 = array();
        $unsetFields = arr::make($this->fieldsNotToClone, true);

        // Обикаля детайлите
        foreach (range(1, 2) as $i){
            $masterVal = ${"master{$i}Id"};
            $arr = &${"arr{$i}"};
            $dQuery = $this->getQuery();
            $dQuery->where("#{$this->masterKey} = {$masterVal}");
            $dQuery->orderBy('id', 'ASC');

            // Нормализира ги за сравнение
            while($dRec = $dQuery->fetch()){
                foreach ($unsetFields as $fld){
                    unset($dRec->{$fld});
                }
                $dRec->_batches = array();
                if(core_Packs::isInstalled('batch')) {
                    if ($bRec = batch_BatchesInDocuments::fetch("#detailClassId = {$this->getClassId()} AND #detailRecId = {$dRec->id}")) {
                        unset($bRec->containerId, $bRec->id, $bRec->detailRecId);
                        $dRec->_batches[] = $bRec;
                    }
                }
                unset($dRec->id, $dRec->{$this->masterKey}, $dRec->createdOn, $dRec->createdBy);
                $arr[] = $dRec;
            }
        }

        return md5(serialize($arr1)) != md5(serialize($arr2));
    }


    /**
     * Екшън клониращ детайлите от клонирания документ в клонинга
     */
    public function act_copydetailsfromcloned()
    {
        // Проверка на права
        $this->requireRightFor('copydetailsfromcloned');
        expect($masterId = Request::get($this->masterKey, 'int'));
        expect($masterRec = $this->Master->fetch($masterId));
        $this->requireRightFor('copydetailsfromcloned', (object)array($this->masterKey => $masterId));

        // Изтриване на текущите записи
        $this->delete("#{$this->masterKey} = {$masterId}");
        $now = dt::now();
        $cu = core_Users::getCurrent();
        $classId = $this->getClassId();
        $unsetFields = arr::make($this->fieldsNotToClone, true);

        // Копиране на детайлите
        $oQuery = $this->getQuery();
        $oQuery->where("#{$this->masterKey} = {$masterRec->clonedFromId}");
        $oQuery->orderBy('id', 'ASC');
        while($oRec = $oQuery->fetch()){
            $cloneRec = clone $oRec;
            unset($cloneRec->id);
            $cloneRec->{$this->masterKey} = $masterId;
            $cloneRec->createdOn = $now;
            $cloneRec->createdBy = $cu;
            foreach ($unsetFields as $fld){
                unset($cloneRec->{$fld});
            }
            $this->save($cloneRec);

            // Ако има партиди към редовете, клонират се и те
            if(core_Packs::isInstalled('batch')){
                if($bRec = batch_BatchesInDocuments::fetch("#detailClassId = {$classId} AND #detailRecId = {$oRec->id}")){
                    unset($bRec->id);
                    $bRec->detailRecId = $cloneRec->id;
                    $bRec->containerId = $masterRec->containerId;
                    batch_BatchesInDocuments::save($bRec);
                }
            }
        }

        followRetUrl(null, '|Оригиналните редове са прехвърлени успешно|*!');
    }


    /**
     * След извличане на експорт на полетата за csv
     *
     * @param $mvc
     * @param $fieldset
     * @return void
     */
    protected static function on_AfterGetCsvExportDetailFieldset($mvc, &$fieldset)
    {
        deals_Helper::getExportCsvProductFieldset($mvc, $fieldset);
    }


    /**
     * Взимане на детайлите за експорт в csv
     *
     * @param $mvc
     * @param $masterRec
     * @param $expandedRecs
     * @param $detailFields
     * @param $fieldset
     * @return void
     */
    protected static function on_AfterGetCsvExportDetailRecs($mvc, $masterRec, &$expandedRecs, &$fieldset)
    {
        deals_Helper::addCsvExportProductRecs4Master($mvc, $masterRec, $expandedRecs);
    }
}
