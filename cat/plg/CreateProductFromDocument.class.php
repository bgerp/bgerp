<?php


/**
 * Добавя екшън към бизнес документ за автоматично добавяне на нов артикул
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cat_plg_CreateProductFromDocument extends core_Plugin
{
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        setIfNot($mvc->filterProtoByMeta, 'canSell');
        expect(in_array($mvc->filterProtoByMeta, array('canSell', 'canBuy', 'canStore', 'canConvert', 'fixedAsset', 'canManifacture')));
        expect($mvc instanceof deals_DealDetail || $mvc instanceof sales_QuotationsDetails || $mvc instanceof store_InternalDocumentDetail);
    }
    
    
    /**
     * След подготовка на лист тулбара
     */
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
        if ($mvc->haveRightFor('createproduct', (object) array($mvc->masterKey => $data->masterId))) {
            $data->toolbar->addBtn('Създаване||New item', array($mvc, 'CreateProduct', $mvc->masterKey => $data->masterId, 'ret_url' => true), 'id=btnNewProduct,title=Създаване на нов нестандартен артикул', 'ef_icon = img/16/bag-new.png,order=12');
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'createproduct') {
            if (isset($rec)) {
                if ($mvc instanceof sales_SalesDetails) {
                    if (core_Users::haveRole('partner', $userId)) {
                        $requiredRoles = 'no_one';
                    } else {
                        $roles = sales_Setup::get('ADD_BY_CREATE_BTN');
                        if (!haveRole($roles, $userId)) {
                            $requiredRoles = 'no_one';
                        }
                    }
                } elseif(!($mvc instanceof store_InternalDocumentDetail)){
                    $requiredRoles = $mvc->getRequiredRoles('add', $rec);
                }
                
                // Могат да се клонират само артикули от същата папка акто тези на документа
                if (isset($rec->cloneId)) {
                    $pId = $mvc->fetchField($rec->cloneId, 'productId');
                    $docFolder = $mvc->Master->fetchField($rec->{$mvc->masterKey}, 'folderId');
                    if (cat_Products::fetchField($pId, 'folderId') != $docFolder) {
                        $requiredRoles = 'no_one';
                    }
                }
            } else {
                $requiredRoles = $mvc->getRequiredRoles('add');
            }
        }
    }
    
    
    /**
     * Преди всеки екшън на мениджъра-домакин
     */
    public static function on_BeforeAction($mvc, &$tpl, $action)
    {
        if ($action == 'createproduct') {
            $mvc->requireRightFor('createproduct');
            expect($masterId = Request::get($mvc->masterKey, 'int'));
            expect($masterRec = $mvc->Master->fetch($masterId));
            $cloneId = Request::get('cloneId', 'int');
            if ($cloneId) {
                $cloneRec = $mvc->fetch($cloneId);
            }
            $action = (isset($cloneRec)) ? 'cloneRecInDocument' : 'createProductInDocument';
            
            $mvc->requireRightFor('createproduct', (object) array($mvc->masterKey => $masterId, 'cloneId' => $cloneRec->id));
            $Products = cls::get('cat_Products');
            unset($Products->doc_plg_Prototype);
            
            $detailFields = $productFields = array();
            
            // Взимаме формата на детайла
            $form = $mvc->getForm();
            
            $form->setField($mvc->masterKey, 'input=hidden');
            $fieldPack = $form->getField('packagingId');
            unset($fieldPack->removeAndRefreshForm);
            
            // Поле за прототип
            $form->FLD('innerClass', 'class(interface=cat_ProductDriverIntf, allowEmpty, select=title)', 'caption=Вид,mandatory,silent,before=proto,removeAndRefreshForm=proto|packPrice|discount|packagingId|tolerance|meta,mandatory');
            $form->setOptions('innerClass', cat_Products::getAvailableDriverOptions());
            $form->FLD('proto', 'key(mvc=cat_Products,allowEmpty,select=name)', 'caption=Шаблон,input=hidden,silent,refreshForm,placeholder=Популярни продукти,before=packagingId');
            
            $form->input(null, 'silent');
            
            if ($form->rec->innerClass) {
                $form->setDefault('innerClass', $form->rec->innerClass);
            } elseif (isset($cloneRec)) {
                $innerClass = cat_Products::fetchField($cloneRec->productId, 'innerClass');
                $form->setDefault('innerClass', $innerClass);
            }
            
            // Наличните прототипи + клонирания
            if (isset($form->rec->innerClass)) {
                $protos = cat_Categories::getProtoOptions($form->rec->innerClass, $mvc->filterProtoByMeta, null, $masterRec->folderId);
                $Driver = cls::get($form->rec->innerClass);
            } else {
                $protos = array();
            }
            
            if (isset($cloneRec)) {
                $protos[$cloneRec->productId] = cat_Products::getTitleById($cloneRec->productId, false);
            }
            
            if (countR($protos)) {
                $form->setOptions('proto', $protos);
                $form->setField('proto', 'input');
            }
            
            // Инпутваме silent полетата
            $form->input(null, 'silent');
            
            
            // Махаме системните полета от формата
            foreach (array('id', 'createdOn', 'createdBy') as $f) {
                unset($form->fields[$f]);
            }
            
            // Продукта ще се създава, няма да се избира
            $form->setField('productId', 'input=none');
 
            if (isset($cloneRec)) {
                $form->setField('packQuantity', 'mandatory');
                
                // Ако няма избран прототип - клонираният продукт е прототип
                $form->setDefault('proto', $cloneRec->productId);
                
                $detailFields['proto'] = 'proto';
                $excludeArr = array('packQuantity',  'price', 'packPrice', 'discount');
                if ($form->rec->innerClass) {
                    $excludeArr[] = 'packagingId';
                }
                foreach ($form->fields as $n => $f1) {
                    $detailFields[$n] = $n;
                    if (isset($cloneRec->{$n}) && !in_array($n, $excludeArr)) {
                        // =================================================================================================
                        $form->setDefault($n, $cloneRec->{$n});
                    }
                }
            } else {
                $form->setField('packagingId', 'input=none');
                foreach ($form->fields as $n => $f1) {
                    $detailFields[$n] = $n;
                }
            }
            
            $data1 = (object) array('form' => $form, 'masterRec' => $masterRec, 'action' => $action);
            $mvc->invoke('AfterPrepareEditForm', array($data1, $data1));
            
            if ($mvc instanceof sales_QuotationsDetails) {
                $form->setDefault('optional', 'no');
            }
            
            $d = Request::get('d');
            
            // Ако е инпутнат прототип
            if (isset($form->rec->proto) || isset($form->rec->innerClass) || isset($d)) {
                
                // Взимаме от драйвера нужните полета
                $proto = $form->rec->proto;
                
                // =================================================================================================
                cat_Products::setAutoCloneFormFields($form, $proto, $form->rec->innerClass);
                
                // $form->setDefault('productId', $form->rec->proto);
                
                $productFields = array_diff_key($form->fields, $detailFields);
                
                // Зареждаме данни от прототипа (или артикула който клонираме)
                if ($proto) {
                    $protoRec = cat_Products::fetch($proto);
                    $protoName = cat_Products::getTitleById($protoRec->id);
                    unset($productFields['measureId']);
                    foreach ($productFields as $n1 => $fld) {
                        if (isset($protoRec->{$n1})) {
                            // =================================================================================================
                            $form->setDefault($n1, $protoRec->{$n1});
                        }
                    }
                    
                    unset($form->rec->name);
                    
                    // Допустимите мерки са сред производните на тази на прототипа
                    $sameMeasures = cat_UoM::getSameTypeMeasures($protoRec->measureId);
                    $form->setOptions('measureId', $sameMeasures);
                }
                
                // Ако има в крипитаните данни записват се
                if (isset($d)) {
                    foreach ($productFields as $n1 => $fld) {
                        if (isset($d->{$n1})) {
                            $form->setDefault($n1, $d->{$n1});
                        }
                    }
                    
                    // Цената
                    if (isset($d->price)) {
                        $d->price = deals_Helper::getDisplayPrice($d->price, 0, $masterRec->currencyRate, $masterRec->chargeVat);
                        $form->setDefault('packPrice', $d->price);
                    }
                }
                
                $form->rec->folderId = $masterRec->folderId;
                $form->rec->threadId = $masterRec->threadId;
                $form->rec->_createProductForm = true;
                
                // Извикваме в класа и драйвера нужните ивенти
                if ($proto && !$form->rec->innerClass) {
                    $Driver = cat_Products::getDriver($proto);
                } else {
                    $Driver = cls::get($form->rec->innerClass);
                    $cover = doc_Folders::getCover($form->rec->folderId);
                    
                    $defMetas = $Driver->getDefaultMetas();
                    if (!count($defMetas)) {
                        $defMetas = $cover->getDefaultMeta();
                    }
                    
                    if (count($defMetas)) {
                        $form->setDefault('meta', $form->getFieldType('meta')->fromVerbal($defMetas));
                    }
                    
                    if ($Driver->getDefaultUomId()) { 
                        $driverUomId = $Driver->getDefaultUomId();
                        $form->rec->measureId = $driverUomId;
                        $form->setField('measureId', 'input=hidden');
                    } else {
                        $measureOptions = cat_UoM::getUomOptions();
                        if ($defMeasure = core_Packs::getConfigValue('cat', 'CAT_DEFAULT_MEASURE_ID')) {
                            $measureOptions[$defMeasure] = cat_UoM::getTitleById($defMeasure, false);
                            $form->setDefault('measureId', $defMeasure);
                        }
                        $form->setOptions('measureId', array('' => '') + $measureOptions);
                    }
                }
                
                $Driver->invoke('AfterPrepareEditForm', array($Products, (object) array('form' => $form, 'action' => $action)));
                $defMetas = $Driver->getDefaultMetas();
                if (isset($defMetas['canManifacture'])) {
                    if(!($mvc instanceof store_InternalDocumentDetail)){
                        $form->setField('tolerance', 'input');
                        $form->setField('term', 'input');
                    }
                }
                
                $form->input();
 
                if($driverUomId) {
                    $form->rec->measureId = $driverUomId;
                }

                if (empty($form->rec->packagingId)) {
                    $form->rec->packagingId =  $form->rec->measureId;
                }
                
                if ($Driver->canAutoCalcPrimeCost($form->rec) !== true) {
                    $form->setField('packPrice', 'mandatory');
                }
                
                $Products->invoke('AfterInputEditForm', array($form));

                $form->rec->_moq = $Driver->getMoq();
                $form->rec->productInfo = clone($form->rec);
                $mvc->invoke('AfterInputEditForm', array($form));

                if ($form->rec->packagingId) {
                    $form->setReadOnly('packagingId');
                }
                $productKeys = array_keys($productFields);
                $productKeys = implode('|', $productKeys);
                $form->setField('proto', "removeAndRefreshForm={$productKeys}");
                
                if (!isset($cloneRec)) {
                    $form->setField('packagingId', 'input=hidden');
                }
                
                // Намираме полетата от артикула
                $productFields = array_diff_key($form->fields, $detailFields);
            } else {
                // Ако не клонираме прототипа е скрит
                $fields = $form->selectFields();
                foreach ($fields as $name => $fl1) {
                    if ($name != 'proto' && $name != 'innerClass') {
                        $form->setField($name, 'input=none');
                    }
                }
            }
            
            if (isset($cloneRec)) {
                $protoType = $form->getFieldType('proto');
                
                unset($protoType->options[$cloneRec->productId], $form->rec->proto);
                
                if (!countR($protoType->options)) {
                    $form->setField('proto', 'input=none');
                }
                
                $quantityArr = array('' => '', $cloneRec->quantity => $cloneRec->quantity);
                
                $form->setSuggestions('packQuantity', $quantityArr);
            }
            
            // След събмит
            if ($form->isSubmitted()) {
                $rec = $form->rec;
                 
                if (isset($cloneRec)) {
                    $rec->proto = cat_Products::fetchField($cloneRec->productId, 'proto');
                }
                
                $arrRec = (array) $rec;
                
                // Намираме полетата на артикула
                $pRec = (object) (array('proto' => $rec->proto) + array_intersect_key($arrRec, $productFields));
                $pRec->folderId = $masterRec->folderId;
                $pRec->threadId = $masterRec->threadId;
                $pRec->isPublic = 'no';
                $pRec->innerClass = $rec->innerClass;
                $pRec->meta = $rec->meta;
                
                $productId = null;
                $hash = cat_Products::getHash($pRec);
                
                // Ако артикула има хеш търси се имали друг артикул със същия хеш ако има се добавя
                if (isset($hash)) {
                    
                    // Филтрираме id-тата само на детайла от текущия документ
                    $ids = array();
                    
                    $detDocs = array('quotationId' => 'sales_QuotationsDetails', 'requestId' => 'purchase_PurchasesDetails', 'saleId' => 'sales_SalesDetails');
                    foreach ($detDocs as $part => $detMvc) {
                        if ($rec->{$part}) {
                            $detQuery = $detMvc::getQuery();
                            while ($detRec = $detQuery->fetch("#{$part} = " . $rec->{$part})) {
                                $ids[] = $detRec->productId;
                            }
                        }
                    }
                    
                    if (countR($ids)) {
                        $pQuery = cat_Products::getQuery();
                        $pQuery->where('#id IN (' . implode(',', $ids) . ')');
                        $pQuery->where("#innerClass = {$rec->innerClass}");
                        $pQuery->where("#state = 'active'");
                        while ($eRec = $pQuery->fetch()) {
                            $hash1 = cat_Products::getHash($eRec);
                            
                            if ($hash1 == $hash) {
                                $productId = $eRec->id;
                                break;
                            }
                        }
                    }
                }
                
                if(deals_Helper::fetchExistingDetail($mvc, $rec->{$mvc->masterKey}, $rec->id, $productId, $rec->packagingId, $rec->price, $rec->discount, $rec->tolerance, $rec->term, $rec->batch, null, $rec->notes, $rec->quantity)){
                    $form->setError('productId,packagingId,packPrice,discount,notes,packQuantity', 'Има въведен ред със същите данни');
                }
                
                if (!$form->gotErrors()) {
                    // Създаване на нов артикул само при нужда
                    if (!empty($productId) && $mvc instanceof sales_QuotationsDetails) {
                        $sameProduct = $mvc->fetch("#quotationId = {$rec->quotationId} AND #productId = {$productId}  AND #quantity='{$rec->quantity}'");
                    }
                    
                    $msg = null;
                    if (!isset($productId)) {
                        if ($sameProduct && $sameProduct->productId) {
                            $pRec->id = $productId = $rec->productId;
                            $Products->save($pRec);
                            $msg = 'Променен е артикул|*:' . cat_Products::getTitleById($productId);
                            $Products->logInAct('Модифициране от документ', $pRec);
                        } else {
                            $productId = $Products->save($pRec);
                            $msg = 'Създаден е нов артикул|*:' . cat_Products::getTitleById($productId);
                            $Products->logInAct('Създаване от документ', $pRec);
                        }
                    }
                    
                    $dRec = (object) array_diff_key($arrRec, $productFields);
                    $dRec->productId = $productId;
                    
                    if (!isset($cloneRec)) {
                        $dRec->packagingId = $pRec->measureId;
                        $dRec->quantityInPack = 1;
                    }
                    
                    if (empty($rec->packQuantity) || $rec->defQuantity === true) {
                        $dRec->quantity = deals_Helper::getDefaultPackQuantity($productId, $pRec->measureId);
                    }
                    
                    $dRec->quantity = ($dRec->quantity) ? $dRec->quantity : 1;
                    
                    // Хакване на автоматично изчислена цена
                    if (!($mvc instanceof sales_QuotationsDetails)) {
                        if ($Driver->canAutoCalcPrimeCost($productId) == true && empty($dRec->packPrice)) {
                            $Policy = (isset($mvc->Master->Policy)) ? $mvc->Master->Policy : cls::get('price_ListToCustomers');
                            $listId = ($masterRec->priceListId) ? $masterRec->priceListId : null;
                            
                            if(empty($masterRec->currencyRate)){
                                $masterRec->currencyRate = currency_CurrencyRates::getRate(dt::now(), null, $masterRec->currencyId);
                            }
                            
                            if(empty($masterRec->chargeVat)){
                                $masterRec->chargeVat = (cls::get($masterRec->contragentClassId)->shouldChargeVat($masterRec->contragentId)) ? 'yes' : 'no';
                            }
                            
                            if($mvc instanceof store_InternalDocumentDetail){
                                $dRec->quantity = $dRec->packQuantity * $dRec->quantityInPack;
                            }
                            
                            $policyInfo = $Policy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $dRec->productId, $dRec->packagingId, $dRec->quantity, $masterRec->valior, $masterRec->currencyRate, $masterRec->chargeVat, $listId);
                            
                            $price = $policyInfo->price;
                            if ($policyInfo->discount && !isset($dRec->discount)) {
                                $dRec->discount = $policyInfo->discount;
                            }
                            $dRec->autoPrice = true;
                            
                            if($mvc instanceof store_InternalDocumentDetail){
                                $dRec->packPrice = $price;
                            } else {
                                $price = deals_Helper::getPurePrice($price, cat_Products::getVat($productId, $masterRec->valior), $masterRec->currencyRate, $masterRec->chargeVat);
                                $dRec->price = $price;
                            }
                        }
                    } else {
                        
                        // За офертата
                        if ($Driver->canAutoCalcPrimeCost($productId) == true && empty($dRec->packPrice)) {
                            $dRec->autoPrice = true;
                        }
                    }
                    
                    if (!$dRec->autoPrice) {
                        $vat = cat_Products::getVat($productId, $masterRec->valior);
                        if ($masterRec->chargeVat == 'yes') {
                            $dRec->price = $dRec->price / (1 + $vat);
                        }
                    }
                    
                    $fields = ($mvc instanceof sales_QuotationsDetails) ? array('masterMvc' => 'sales_Quotations', 'deliveryLocationId' => 'deliveryPlaceId') : array();
                    sales_TransportValues::prepareFee($dRec, $form, $masterRec, $fields);
                    
                    $mvc->save($dRec);
                    
                    if ($cloneId) {
                        $mvc->logInAct('Създаване с клониране', $dRec);
                    } else {
                        $mvc->logInAct('Създаване от нов нестандартен артикул', $dRec);
                    }
                    
                    // Разпределяне на разходи при нужда
                    if (isset($d->costItemId)) {
                        acc_CostAllocations::delete("#detailClassId = {$mvc->getClassId()} AND #detailRecId = {$dRec->id} AND #productId = {$productId}");
                        $saveRec = (object) array('detailClassId' => $mvc->getClassId(), 'detailRecId' => $dRec->id, 'productId' => $productId, 'expenseItemId' => $d->costItemId, 'containerId' => $masterRec->containerId, 'quantity' => $dRec->quantity, 'allocationBy' => 'no');
                        
                        acc_CostAllocations::save($saveRec);
                        $CostAllocations = cls::get('acc_CostAllocations');
                        $CostAllocations->logInAct('Създаване на артикул с клониране', $saveRec);
                    }
                    
                    // Редирект към сделката/офертата
                    return Redirect(array($mvc->Master, 'single', $dRec->{$mvc->masterKey}), false, $msg);
                }
            }
            
            // Добавяме бутони на формата
            $folderTitle = doc_Folders::getLink($masterRec->folderId);
            $form->title = "Създаване на нов нестандартен артикул в|* {$folderTitle}";
            
            if (isset($form->rec->innerClass)) {
                $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png, title = Запис');
            } else {
                $form->toolbar->addBtn('Запис', array(), 'ef_icon = img/16/disk.png, title = Запис');
            }
            
            $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
            
            // Рендиране на опаковката
            $tpl = $mvc->renderWrapping($form->renderHtml());
            core_Form::preventDoubleSubmission($tpl, $form);
            
            // Връщаме FALSE за да се прекъсне ивента
            return false;
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if ($mvc->haveRightFor('createProduct', (object) array($mvc->masterKey => $rec->{$mvc->masterKey}, 'cloneId' => $rec->id))) {
            $url = array($mvc, 'CreateProduct', $mvc->masterKey => $rec->{$mvc->masterKey}, 'cloneId' => $rec->id, 'ret_url' => true);
            
            if ($mvc->hasPlugin('plg_RowTools2')) {
                core_RowToolbar::createIfNotExists($row->_rowTools);
                $row->_rowTools->addLink('Клониране', $url, 'id=btnNewProduct,title=Създаване на нов нестандартен артикул', 'ef_icon = img/16/clone.png,order=12');
            }
        }
    }
}
