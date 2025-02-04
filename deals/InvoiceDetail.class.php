<?php


/**
 * Базов клас за наследяване на детайл на ф-ри
 *
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class deals_InvoiceDetail extends doc_Detail
{
    /**
     * Помощен масив за мапиране на полета изпозлвани в deals_Helper
     */
    public $map = array('rateFld' => 'rate',
        'chargeVat' => 'vatRate',
        'quantityFld' => 'quantity',
        'valior' => 'date',
        'alwaysHideVat' => true,);
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'discount,reff';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'amount,discount,packPrice';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId, quantity=К-во, packPrice, discount=Отст., amount';
    
    
    /**
     * Да се показва ли вашия номер
     */
    public $showReffCode = true;
    
    
    /**
     * Да се показва ли кода като в отделна колона
     */
    public $showCodeColumn = true;


    /**
     * Извиква се след описанието на модела
     *
     * @param core_Mvc $mvc
     */
    public static function setInvoiceDetailFields(&$mvc)
    {
        $mvc->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,maxSuggestions=100,forceAjax,titleFld=name)', 'class=w100,caption=Артикул,mandatory', 'tdClass=productCell leftCol wrap,silent,removeAndRefreshForm=packPrice|discount|packagingId');
        $mvc->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Мярка', 'tdClass=small-field nowrap,silent,removeAndRefreshForm=packPrice|discount,mandatory,smartCenter,input=hidden');
        $mvc->FLD('quantity', 'double', 'caption=Количество', 'tdClass=small-field,smartCenter');
        $mvc->FLD('quantityInPack', 'double(smartRound)', 'input=none');
        $mvc->FLD('price', 'double', 'caption=Цена, input=none');
        $mvc->FLD('amount', 'double(minDecimals=2,maxDecimals=2)', 'caption=Сума,input=none');
        $mvc->FNC('packPrice', 'double(minDecimals=2)', 'caption=Цена,input,smartCenter');
        $mvc->FLD('discount', 'percent(min=0,max=1,suggestions=5 %|10 %|15 %|20 %|25 %|30 %)', 'caption=Отстъпка,smartCenter');
        $mvc->FLD('notes', 'richtext(rows=3,bucket=Notes)', 'caption=Допълнително->Забележки,formOrder=110001');
        $mvc->FLD('clonedFromDetailId', "int", 'caption=От кое поле е клонирано,input=none');
        $mvc->FLD('autoDiscount', 'percent(min=0,max=1)', 'caption=Авт. отстъпка,input=none');
        $mvc->FLD('inputDiscount', 'percent(min=0,max=1)', 'caption=Ръчна отстъпка,input=none');
        $mvc->setFieldTypeParams('discount', array('warningMax' => deals_Setup::get('MAX_WARNING_DISCOUNT')));

        $mvc->setDbIndex('productId,packagingId');
    }
    
    
    /**
     * Извиква се след подготовката на формата
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $rec = &$data->form->rec;
        $masterRec = $data->masterRec;
        
        $data->form->fields['packPrice']->unit = '|*' . $masterRec->currencyId . ', ';
        $data->form->fields['packPrice']->unit .= ($masterRec->chargeVat == 'yes') ? '|с ДДС|*' : '|без ДДС|*';
        $data->form->setFieldTypeParams('productId', array('customerClass' => $masterRec->contragentClassId, 'customerId' => $masterRec->contragentId, 'hasProperties' => $mvc->metaProducts, 'hasnotProperties' => 'generic'));
        
        if (isset($rec->id)) {
            $data->form->setReadOnly('productId');
        }
        
        if ($masterRec->type === 'dc_note') {
            $data->form->info = tr('|*<div style="color:#333;margin-top:3px;margin-bottom:12px">|Моля, въведете крайното количество|* <b>|или|*</b> |цена след промяната|* <br><small>( |системата автоматично ще изчисли и попълни разликата в известието|* )</small></div>');
            $data->form->setField('quantity', 'caption=|Крайни|* (|след известието|*)->К-во');
            $data->form->setField('packPrice', 'caption=|Крайни|* (|след известието|*)->Цена');
            
            foreach (array('packagingId', 'notes', 'discount') as $fld) {
                $data->form->setField($fld, 'input=none');
            }
            if ($masterRec->state != 'draft'){
                $data->form->setField('notes', 'input');
            }
        }
        $data->form->setFieldTypeParams('quantity', array('min' => 0));
        $data->form->setFieldTypeParams('packPrice', array('min' => 0));
        
        if (!empty($rec->packPrice)) {
            $rec->packPrice = deals_Helper::getDisplayPrice($rec->packPrice, 0, $masterRec->rate, 'no');
        }
        
        if ($masterRec->state != 'draft' and !haveRole('no_one')) {

            $fields = $data->form->selectFields("#name != 'notes' AND #name != 'productId' AND #name != 'id' AND #name != 'invoiceId'");
            $data->singleTitle = 'забележка';
            $data->form->editActive = true;
            foreach ($fields as $name => $fld) {
                $data->form->setField($name, 'input=none');
            }
        }
    }
    
    
    /**
     * След подготовка на лист тулбара
     */
    public static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if (!empty($data->toolbar->buttons['btnAdd'])) {
            unset($data->toolbar->buttons['btnAdd']);
            $masterRec = $data->masterData->rec;
            
            $error = '';
            if (!countR(cat_Products::getProducts($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->valior, $mvc->metaProducts, 'generic', 1))) {
                $text = ($mvc->metaProducts == 'canSell') ? 'продаваеми' : 'купуваеми';
                $error = "error=Няма {$text} артикули,";
            }
            
            $data->toolbar->addBtn('Артикул', array($mvc, 'add', "{$mvc->masterKey}" => $data->masterId, 'ret_url' => true), "id=btnAdd,{$error} order=10,title=Добавяне на артикул", 'ef_icon = img/16/shopping.png');
        }
        
        // Добавяне на бутон за импортиране на артикулите директно от договора
        if ($mvc->haveRightFor('importfromdeal', (object) array("{$mvc->masterKey}" => $data->masterId))) {
            $data->toolbar->addBtn(
                'От договора',
                array($mvc, 'importfromdeal', "{$mvc->masterKey}" => $data->masterId, 'ret_url' => true),
            "id=btnimportfromdeal-{$masterRec->id},{$error} order=10,title=Импортиране на артикулите от договора",
                array('warning' => 'Редовете на фактурата, ще копират точно тези от договора|*!', 'ef_icon' => 'img/16/shopping.png')
            );
        }
    }
    
    
    /**
     * Импортиране на артикулите от договора във фактурата
     */
    public function act_Importfromdeal()
    {
        // Проверки
        $this->requireRightFor('importfromdeal');
        
        expect($id = Request::get("{$this->masterKey}", 'int'));
        expect($invoiceRec = $this->Master->fetch($id));
        $this->requireRightFor('importfromdeal', (object) array("{$this->masterKey}" => $id));
        
        // Извличане на дийл интерфейса от договора-начало на нишка
        $this->delete("#{$this->masterKey} = {$id}");
        $firstDoc = doc_Threads::getFirstDocument($invoiceRec->threadId);

        // Изтриване на ръчните общи отстъпки
        if(cls::haveInterface('price_TotalDiscountDocumentIntf', $this->Master)){
            price_DiscountsPerDocuments::delete("#documentClassId={$this->Master->getClassId()} AND #documentId = {$invoiceRec->id}");
        }

        $dealInfo = $firstDoc->getAggregateDealInfo();
        $importBatches = batch_Setup::get('SHOW_IN_INVOICES');
        $chargeVat = $dealInfo->get('vatType');

        // За всеки артикул от договора, копира се 1:1
        $autoDiscountPercent = null;
        $iAmount = 0;
        if (is_array($dealInfo->dealProducts)) {
            foreach ($dealInfo->dealProducts as $det) {
                if(!empty($det->discount) && empty($rec->autoDiscount) && empty($rec->inputDiscount)){
                    $det->inputDiscount = $det->discount;
                }

                $autoDiscountPercent = $det->autoDiscount;
                $det->discount = $det->inputDiscount;
                $det->autoDiscount = null;
                $det->{$this->masterKey} = $id;
                $det->amount = $det->price * $det->quantity;
                $det->quantity /= $det->quantityInPack;
                if(is_array($det->batches) && countR($det->batches)){
                    $det->_batches = array_keys($det->batches);
                }
                unset($det->batches);
                $det->_importBatches = $importBatches;
                $this->save($det);

                if($chargeVat == 'yes'){
                    $iAmount += isset($det->discount) ? ($det->amount * (1 - $det->discount)) : $det->amount;
                }
            }
        }

        // Зареждане и на общите отстъпки от договора, ако има
        if(cls::haveInterface('price_TotalDiscountDocumentIntf', $this->Master)){

            // Гледа се сумата на общите отстъпки и се прехвърлят в един общ ред
            $discQuery = price_DiscountsPerDocuments::getQuery();
            $discQuery->where("#documentClassId = {$firstDoc->getClassId()} AND #documentId = {$firstDoc->that}");
            $discQuery->orderBy('id', 'ASC');
            $totalDiscountRecs = $discQuery->fetchAll();
            $totalDiscountSum = arr::sumValuesArray($totalDiscountRecs, 'amount');

            $expectedDiscount = $iAmount * $autoDiscountPercent;
            foreach ($totalDiscountRecs as $tRec){
                $tRec->documentClassId = $this->Master->getClassId();
                $tRec->documentId = $invoiceRec->id;
                unset($tRec->id);
                if($chargeVat == 'yes'){
                    $tRec->amount = $expectedDiscount * ($tRec->amount / $totalDiscountSum);
                }
                price_DiscountsPerDocuments::save($tRec);
            }
        }

        // Редирект обратно към фактурата
        return followRetUrl(null, '|Артикулите от сделката са копирани успешно');
    }
    
    
    /**
     * Изчисляване на цена за опаковка на реда
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public static function on_CalcPackPrice(core_Mvc $mvc, $rec)
    {
        if (!isset($rec->price) || empty($rec->quantityInPack)) {
            
            return;
        }
        
        $rec->packPrice = $rec->price * $rec->quantityInPack;
    }
    
    
    /**
     * След калкулиране на общата сума
     */
    public function calculateAmount_(&$recs, &$rec)
    {
        // Ако документа е известие
        if ($rec->type === 'dc_note') {
            self::modifyDcDetails($recs, $rec, $this);
        }
        
        deals_Helper::fillRecs($this->Master, $recs, $rec, $this->map);
    }
    
    
    /**
     * Помощна ф-я за обработката на записите на КИ и ДИ
     *
     * @param array    $recs
     * @param stdClass $rec
     */
    public static function modifyDcDetails(&$recs, $rec, $mvc)
    {
        expect($rec->type != 'invoice');
        arr::sortObjects($recs, 'id', 'ASC');

        if (countR($recs)) {
            $hasDiscount = false;
            array_walk($recs, function($a) use (&$hasDiscount) {if(!empty($a->discount)) {$hasDiscount = true;}});
            $applyDiscount = !($hasDiscount);

            // Намираме оригиналните к-ва и цени
            $cached = $mvc->Master->getInvoiceDetailedInfo($rec->originId, $applyDiscount);

            // За всеки запис ако е променен от оригиналния показваме промяната
            foreach ($recs as &$dRec) {
                if(array_key_exists($dRec->clonedFromDetailId, $cached->recWithIds)){
                    $quantityArr = $cached->recWithIds[$dRec->clonedFromDetailId];
                    $originPrice = deals_Helper::getDisplayPrice($quantityArr['price'], 0, 1, 'no', 5);
                    $diffPrice = $dRec->packPrice - $originPrice;

                    $priceIsChanged = false;
                    $diffPrice = round($diffPrice, 5);
                    if(abs($diffPrice) > 0.0001){
                        $priceIsChanged = true;
                    }

                    if ($priceIsChanged) {
                        $dRec->packPrice = $diffPrice;
                        $dRec->changedPrice = true;
                    }
                }

                if(array_key_exists($dRec->clonedFromDetailId, $cached->recWithIds)){
                    $priceArr = $cached->recWithIds[$dRec->clonedFromDetailId];
                    $diffQuantity = $dRec->quantity - $priceArr['quantity'];
                    if (round($diffQuantity, 5) != 0) {
                        $dRec->quantity = $diffQuantity;
                        $dRec->changedQuantity = true;
                    }
                }
            }
        }
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    public static function on_BeforeRenderListTable($mvc, &$res, $data)
    {
        if (!countR($data->rows)) {
            
            return;
        }
        
        $masterRec = $data->masterData->rec;
        
        $batchesInstalled = core_Packs::isInstalled('batch');
        foreach ($data->rows as $id => &$row1) {
            $rec = $data->recs[$id];

            // Ако под артикула ще се показва текста за ф-ра добавя се
            if(isset($mvc->productInvoiceInfoParamName)) {
                if(isset($rec->productId)){
                    if($masterRec->state != 'active') {

                        // Показване на параметъра за информация за фактура лайв
                        $invoiceInfoVerbal = cat_Products::getParams($rec->productId, $mvc->productInvoiceInfoParamName, true);
                        if(!empty($invoiceInfoVerbal)){
                            if(!Mode::isReadOnly()){
                                $invoiceInfoVerbal = "<span style='color:blue'>{$invoiceInfoVerbal}</span>";
                                $invoiceInfoVerbal = ht::createHint($invoiceInfoVerbal, 'Стойността ще се добави в забележката при контиране|*!');
                            }
                            if ($row1->productId instanceof core_ET) {
                                $row1->productId->append("<div class='classInvoiceParam small'>{$invoiceInfoVerbal}</div>");
                            } else {
                                $row1->productId .= "<div class='classInvoiceParam small'>{$invoiceInfoVerbal}</div>";
                            }

                        }
                    }
                }
            }

            if ($batchesInstalled && !empty($rec->batches)) {
                $b = batch_BatchesInDocuments::displayBatchesForInvoice($rec->productId, $rec->batches);
                if (!empty($b)) {
                    if (is_string($row1->productId)) {
                        $row1->productId .= "<div class='small'>{$b}</div>";
                    } else {
                        $row1->productId->append(new core_ET("<div class='small'>[#BATCH#]</div>"));
                        $row1->productId->replace($b, 'BATCH');
                    }
                }
            }
            
            deals_Helper::addNotesToProductRow($row1->productId, $rec->notes);

            if ($masterRec->type != 'dc_note' || !isset($masterRec->type)) {
                $row1->discount = deals_Helper::getDiscountRow($rec->discount, $rec->inputDiscount, $rec->autoDiscount, $masterRec->state);
            }
        }
        
        if ($masterRec->type != 'dc_note') {
            
            return;
        }
        
        foreach ($data->rows as $id => &$row) {
            $rec = $data->recs[$id];
            
            $changed = false;
            
            foreach (array('Quantity' => 'quantity', 'Price' => 'packPrice', 'Amount' => 'amount') as $key => $fld) {
                if ($rec->{"changed{$key}"} === true) {
                    $changed = true;
                    if ($rec->{$fld} < 0) {
                        $row->{$fld} = "<span style='color:red'>{$row->{$fld}}</span>";
                    } elseif ($rec->{$fld} > 0) {
                        $row->{$fld} = "<span style='color:green'>+{$row->{$fld}}</span>";
                    }
                }
            }
            
            // Ако няма промяна реда
            if ($changed === false) {
                
                // При активна ф-ра не го показваме
                if ($masterRec->state == 'active') {
                    unset($data->rows[$id]);
                } else {
                    
                    // Иначе го показваме в сив ред
                    $row->ROW_ATTR['style'] = ' background-color:#f1f1f1;color:#777';
                }
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListRows($mvc, &$data)
    {
        $masterRec = $data->masterData->rec;
        
        if (isset($masterRec->type)) {
            if ($masterRec->type == 'debit_note' || $masterRec->type == 'credit_note' || ($masterRec->type == 'dc_note' && isset($masterRec->changeAmount) && !countR($data->rows))) {
                // При дебитни и кредитни известия показваме основанието
                $data->listFields = array();
                $data->listFields['RowNumb'] = '№';
                $data->listFields['reason'] = 'Основание';
                $data->listFields['amount'] = 'Сума';
                $data->rows = array();
                
                // Показване на сумата за промяна на известието
                $Type = core_Type::getByName('double(decimals=2)');
                $rate = !empty($masterRec->displayRate) ? $masterRec->displayRate : $masterRec->rate;
                $amount = $Type->toVerbal($masterRec->dealValue / $rate);
                $originRec = doc_Containers::getDocument($masterRec->originId)->rec();
                
                if ($originRec->dpOperation == 'accrued') {
                    $reason = ($amount > 0) ? 'Увеличаване на авансово плащане' : 'Намаляване на авансово плащане';
                } else {
                    $reason = ($amount > 0) ? 'Увеличаване на стойност' : 'Намаляване на стойност';
                }
                
                if(!empty($masterRec->dcReason)){
                    $dcReason = core_Type::getByName('richtext')->toVerbal($masterRec->dcReason);
                    $reason .= "|* {$dcReason}";
                }
                
                $data->recs['advance'] = (object) array('amount' => $masterRec->dealValue / $masterRec->rate, 'changedAmount' => true);
                
                core_Lg::push($masterRec->tplLang);
                $data->rows['advance'] = (object) array('RowNumb' => 1, 'reason' => tr($reason), 'amount' => $amount);
                core_Lg::pop();
            }
        }
    }
    
    
    /**
     * След извличане на записите от базата данни
     */
    public static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
    {
        $recs = &$data->recs;
        $invRec = &$data->masterData->rec;
        
        $mvc->calculateAmount($recs, $invRec);
    }
    
    
    /**
     * Конвертира един запис в разбираем за човека вид
     * Входният параметър $rec е оригиналният запис от модела
     * резултата е вербалният еквивалент, получен до тук
     */
    public static function recToVerbal_($rec, &$fields = '*')
    {
        $row = parent::recToVerbal_($rec, $fields);
        
        $mvc = cls::get(get_called_class());
        $masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey});
        
        $date = ($masterRec->state == 'draft') ? null : $masterRec->modifiedOn;
        $modeLg = Mode::get('tplManagerLg');
        $lang = isset($modeLg) ? $modeLg : doc_TplManager::fetchField($masterRec->template, 'lang');

        core_Lg::push($lang);
        core_RowToolbar::createIfNotExists($row->_rowTools);
        cat_Products::addButtonsToDocToolbar($rec->productId, $row->_rowTools, $mvc->className, $rec->id);
        $row->productId = cat_Products::getAutoProductDesc($rec->productId, $date, 'short', 'invoice', $lang, 1, false);
        core_Lg::pop();

        // Показваме подробната информация за опаковката при нужда
        deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
        
        if ($masterRec->type == 'invoice') {
            if (empty($rec->quantity) && !Mode::isReadOnly()) {
                $row->ROW_ATTR['style'] = ' background-color:#f1f1f1;color:#777';
            }
        }
        
        return $row;
    }
    
    
    /**
     * След проверка на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if (($action == 'add' || $action == 'edit' || $action == 'delete' || $action == 'import') && isset($rec->{$mvc->masterKey})) {
            $masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey});
            $invoiceType = $mvc->Master->getField('type', false) ? $mvc->Master->fetchField($rec->{$mvc->masterKey}, 'type') : null;

            if (empty($invoiceType) || $invoiceType == 'invoice') {

                if ($masterRec->state != 'draft') {
                    $res = 'no_one';
                    
                    if ($action == 'edit') {
                        if ($masterRec->state == 'active') {
                            if ($masterRec->createdBy == $userId || haveRole('ceo,manager', $userId) || keylist::isIn($userId, core_Users::getTeammates($masterRec->createdBy))) {
                                $res = 'powerUser';
                            }
                        }
                    }
                } else {
                    
                    // При начисляване на авансово плащане не може да се добавят други продукти
                    if ($masterRec->dpOperation == 'accrued') {
                        $res = 'no_one';
                    }
                }
            } elseif ($invoiceType == 'dc_note') {
                // На ДИ и КИ не можем да изтриваме и добавяме
                if (in_array($action, array('add', 'delete'))) {
                    $res = 'no_one';
                }
            }
        }
        
        if ($action == 'importfromdeal') {
            $res = $mvc->getRequiredRoles('add', $rec, $userId);
        }
    }
    
    
    /**
     * Преди извличане на записите филтър по number
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('#id', 'ASC');
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        $rec = &$form->rec;
        $masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey});
        $vatExceptionId = cond_VatExceptions::getFromThreadId($masterRec->threadId);

        if ($form->rec->productId && $masterRec->type != 'dc_note' && $form->editActive !== true) {
            $vat = cat_Products::getVat($rec->productId, $masterRec->date, $vatExceptionId);
            
            $packs = cat_Products::getPacks($rec->productId, $rec->packagingId);
            $form->setOptions('packagingId', $packs);
            $form->setDefault('packagingId', key($packs));
            $form->setField('packagingId', 'input');
            
            if (isset($mvc->LastPricePolicy)) {
                $policyInfoLast = $mvc->LastPricePolicy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->packagingId, $masterRec->rate);
                
                if ($policyInfoLast->price != 0) {
                    $form->setSuggestions('packPrice', array('' => '', "{$policyInfoLast->price}" => $policyInfoLast->price));
                }
            }
        } else {
            $form->setReadOnly('packagingId');
        }
        
        if ($form->isSubmitted() && !$form->gotErrors()) {
            if (!isset($rec->quantity) && $masterRec->type != 'dc_note') {
                $defaultQuantity = $rec->_moq ? $rec->_moq : deals_Helper::getDefaultPackQuantity($rec->productId, $rec->packagingId);
                $form->setDefault('quantity', $defaultQuantity);
                if (empty($rec->quantity)) {
                    $form->setError('quantity', 'Не е въведено количество');
                    return;
                }
            }
            
            if ($masterRec->type == 'dc_note') {
                if (!isset($rec->packPrice) || !isset($rec->quantity)) {
                    $form->setError('packPrice,packQuantity', 'Количеството и сумата трябва да са попълнени');
                    
                    return;
                }
            }
            
            // Проверка на к-то, само ако не е КИ или ДИ
            $warning = null;
            if (!deals_Helper::checkQuantity($rec->packagingId, $rec->quantity, $warning) && $masterRec->type != 'dc_note') {
                $form->setWarning('quantity', $warning);
            }

            $productInfo = cat_Products::getProductInfo($rec->productId);
            if ($masterRec->type != 'dc_note') {
                $rec->quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
            }

            // Ако няма въведена цена
            if (!isset($rec->packPrice) && $masterRec->type != 'dc_note') {
                $autoPrice = true;
                
                // Ако продукта има цена от пораждащия документ, взимаме нея, ако не я изчисляваме наново
                $origin = $mvc->Master->getOrigin($masterRec);
                $dealInfo = $origin->getAggregateDealInfo();
                $products = $dealInfo->get('products');
                
                if (countR($products)) {
                    foreach ($products as $p) {
                        if ($rec->productId == $p->productId && $rec->packagingId == $p->packagingId) {
                            $policyInfo = new stdClass();
                            $policyInfo->price = deals_Helper::getDisplayPrice($p->price, $vat, $masterRec->rate, 'no');
                            $policyInfo->discount = $p->discount;
                            break;
                        }
                    }
                }
                
                if (!$policyInfo) {
                    $Policy = (isset($mvc->Policy)) ? $mvc->Policy : cls::get('price_ListToCustomers');
                    $listId = ($dealInfo->get('priceListId')) ? $dealInfo->get('priceListId') : null;
                    $policyInfo = $Policy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->packagingId, $rec->quantity, dt::today(), $masterRec->rate, 'no', $listId);
                }
                
                // Ако няма последна покупна цена и не се обновява запис в текущата покупка
                if (!isset($policyInfo->price)) {
                    $errorMsg = isset($Policy) ? $Policy->notFoundPriceErrorMsg : 'Артикулът няма цена в избраната ценова политика. Въведете цена|*!';
                    $form->setError('packPrice', $errorMsg);
                } else {
                    
                    // Ако се обновява запис се взима цената от него, ако не от политиката
                    $rec->price = $policyInfo->price;
                    $rec->packPrice = $policyInfo->price * $rec->quantityInPack;
                    
                    if ($policyInfo->discount && !isset($rec->discount)) {
                        $rec->discount = $policyInfo->discount;
                    }
                }
            } else {
                $autoPrice = false;
                
                // Изчисляване цената за единица продукт в осн. мярка
                $rec->price = $rec->packPrice / $rec->quantityInPack;
                $packPrice = null;
                if (!$form->gotErrors() || ($form->gotErrors() && Request::get('Ignore'))) {
                    $rec->packPrice = deals_Helper::getPurePrice($rec->packPrice, 0, $masterRec->rate, $masterRec->vatRate);
                } else {
                    $packPrice = deals_Helper::getPurePrice($rec->packPrice, 0, $masterRec->rate, $masterRec->vatRate);
                }
            }
            
            // Проверка на цената
            $msg = null;
            $quantityInBaseMeasureId = $rec->quantity * $rec->quantityInPack;
            if (!deals_Helper::isPriceAllowed($rec->price, $quantityInBaseMeasureId, $autoPrice, $msg)) {
                $form->setError('packPrice,quantity', $msg);
            }
            
            $rec->price = deals_Helper::getPurePrice($rec->price, 0, $masterRec->rate, $masterRec->chargeVat);

            // Ако има такъв запис, сетваме грешка
            $setWarning = deals_Setup::get('WARNING_ON_DUPLICATED_ROWS');
            if($setWarning == 'yes'){
                $countSameProduct = $mvc->count("#{$mvc->masterKey} = '{$rec->{$mvc->masterKey}}' AND #id != '{$rec->id}' AND #productId = {$rec->productId}");
                if ($countSameProduct) {
                    if($masterRec->type != 'dc_note'){
                        $form->setWarning('productId', 'Артикулът вече присъства на друг ред в документа');
                        unset($rec->packPrice, $rec->price, $rec->quantityInPack);
                    }
                }
            }

            if(!$form->gotErrors()){
                // Записваме основната мярка на продукта
                $rec->amount = $rec->packPrice * $rec->quantity;

                // При редакция, ако е променена опаковката слагаме преудпреждение
                if ($rec->id) {
                    $oldRec = $mvc->fetch($rec->id);
                    if ($oldRec && $rec->packagingId != $oldRec->packagingId && !empty($rec->packPrice) && trim($rec->packPrice) == trim($oldRec->packPrice)) {
                        $form->setWarning('packPrice,packagingId', 'Опаковката е променена без да е променена цената|*.<br />|Сигурни ли сте, че зададената цена отговаря на новата опаковка|*?');
                    }
                }

                if ($masterRec->type === 'dc_note') {

                    // Проверка дали са променени и цената и количеството
                    $cache = $mvc->Master->getInvoiceDetailedInfo($masterRec->originId, true);
                    $originRec = $cache->recWithIds[$rec->clonedFromDetailId];
                    $diffPrice = round($rec->packPrice - $originRec['price'], 5);
                    if(round($rec->quantity, 5) != round($originRec['quantity'], 5) && abs($diffPrice) > 0.0001){
                        $form->setError('quantity,packPrice', 'Не може да е променена и цената и количеството');
                    }
                }
            }
        }
    }


    /**
     * Кои полета да се преизичслят при активиране
     *
     * @param stdClass $invoiceRec;
     */
    public function getFieldsToCalcOnActivation_($invoiceRec)
    {
        return array();
    }


    /**
     * Дали да се обнови записа при активиране
     *
     * @param stdClass $dRec      - ид на запис
     * @param stdClass $masterRec - ид на мастъра на записа
     * @param array $params       - продуктовите параметри
     * @return bool               - ще се обновява ли реда или не
     */
    public function calcFieldsOnActivation_(&$dRec, $masterRec, $params)
    {
        return false;
    }


    /**
     * Изпълнява се преди клониране на детайла
     */
    protected static function on_BeforeSaveClonedDetail($mvc, &$rec, $oldRec)
    {
        $rec->discount = $oldRec->inputDiscount;
    }
}
