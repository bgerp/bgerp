<?php


/**
 * Мениджър за "Детайли на офертите"
 *
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.11
 */
class deals_QuotationDetails extends doc_Detail
{
    /**
     * Заглавие в единствено число
     *
     * @var string
     */
    public $singleTitle = 'Артикул';


    /**
     * Мастър ключ към дъските
     */
    public $masterKey = 'quotationId';


    /**
     * При колко линка в тулбара на реда да не се показва дропдауна
     *
     * @param int
     */
    public $rowToolsMinLinksToShow = 2;


    /**
     * Кои полета да се извличат при изтриване
     */
    public $fetchFieldsBeforeDelete = 'quotationId';


    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'price,packPrice,tolerance,term,weight,quantityInPack';


    /**
     * Кой може да променя?
     */
    public $canList = 'no_one';


    /**
     * Полета свързани с цени
     */
    public $priceFields = 'packPrice,discount,amount,vatPackPrice';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId, quantityInPack, packQuantity, packPrice, discount, tolerance, term, weight,optional, amount, discAmount,quantity';


    /**
     * Добавяне на нужните полета
     */
    protected function addDetailFields($mvc)
    {
        $mvc->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,maxSuggestions=100,forceAjax,forceOpen)', 'class=w100,caption=Артикул,notNull,mandatory,silent,removeAndRefreshForm=packPrice|discount|packagingId');

        $mvc->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName)', 'caption=Мярка,mandatory', 'tdClass=small-field nowrap,smartCenter,input=hidden');
        $mvc->FNC('packQuantity', 'double(Min=0)', 'caption=Количество,input=input,smartCenter');
        $mvc->FLD('quantityInPack', 'double(smartRound)', 'input=none');
        $mvc->FNC('packPrice', 'double(minDecimals=2)', 'caption=Цена,input,smartCenter');
        $mvc->FNC('vatPackPrice', 'double(minDecimals=2)', 'caption=Цена с ддс,smartCenter');
        $mvc->FLD('quantity', 'double(Min=0)', 'caption=Количество,input=none');
        $mvc->FLD('price', 'double(minDecimals=2,maxDecimals=4)', 'caption=Ед. цена, input=none');
        $mvc->FLD('discount', 'percent(smartRound,min=0,max=1,suggestions=5 %|10 %|15 %|20 %|25 %|30 %,warningMax=0.3)', 'caption=Отстъпка,smartCenter');
        $mvc->FLD('tolerance', 'percent(min=0,max=1,decimals=0,warningMax=0.1)', 'caption=Толеранс,input=none');
        $mvc->FLD('term', 'time(uom=days,suggestions=1 ден|5 дни|7 дни|10 дни|15 дни|20 дни|30 дни)', 'caption=Срок,input=none');
        $mvc->FLD('weight', 'cat_type_Weight', 'input=none,caption=Тегло');
        $mvc->FLD('vatPercent', 'percent(min=0,max=1,decimals=2)', 'caption=ДДС,input=none');
        $mvc->FLD('optional', 'enum(no=Не,yes=Да)', 'caption=Опционален,maxRadio=2,columns=2,input=hidden,silent,notNull,value=no');
        $mvc->FLD('showMode', 'enum(auto=По подразбиране,detailed=Разширен,short=Съкратен)', 'caption=Изглед,notNull,default=auto');
        $mvc->FLD('notes', 'richtext(rows=3,bucket=Notes)', 'caption=Забележки,formOrder=110001');
        $mvc->setField('packPrice', 'silent');

        $mvc->setDbIndex('productId,quantity');
    }


    /**
     * @TODO описание
     *
     * След потготовка на формата за добавяне / редактиране.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     *
     * @return bool|null
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $mvc->prepareDetailForm($data);
    }


    /**
     * Допълнителна подготовка на едит формата
     */
    protected function prepareDetailForm(&$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        $masterRec = $data->masterRec;
        $form->setDefault('showMode', 'detailed');

        $form->setFieldTypeParams('productId', array('customerClass' => $masterRec->contragentClassId, 'customerId' => $masterRec->contragentId, 'hasProperties' => $this->metaProducts, 'hasnotProperties' => 'generic'));
        if (isset($rec->id)) {
            $data->form->setReadOnly('productId');
        }

        if (!empty($rec->packPrice)) {
            if (strtolower(Request::get('Act')) != 'createproduct') {
                $valior = !empty($masterRec->valior) ? $masterRec->valior : dt::today();
                $vat = cat_Products::getVat($rec->productId, $valior);
            } else {
                $vat = acc_Periods::fetchByDate($masterRec->valior)->vatRate;
            }

            $rec->packPrice = deals_Helper::getDisplayPrice($rec->packPrice, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
        }

        $form->fields['packPrice']->unit = '|*' . $masterRec->currencyId . ', ' .(($masterRec->chargeVat == 'yes') ? '|с ДДС|*' : '|без ДДС|*');

        if ($form->rec->price && $masterRec->currencyRate) {
            if ($masterRec->chargeVat == 'yes') {
                ($rec->vatPercent) ? $vat = $rec->vatPercent : $vat = cat_Products::getVat($rec->productId, $masterRec->date);
                $rec->price = $rec->price * (1 + $vat);
            }

            $rec->price = $rec->price / $masterRec->currencyRate;
        }

        if (empty($rec->id)) {
            $form->setDefault('discount', $this->fetchField("#quotationId = {$masterRec->id} AND #discount IS NOT NULL", 'discount'));
        }

        if (isset($rec->productId)) {
            if (cat_Products::getTolerance($rec->productId, 1)) {
                $form->setField('tolerance', 'input');
            }

            if (cat_Products::getDeliveryTime($rec->productId, 1)) {
                $form->setField('term', 'input');
            }
        }

        // Показваме документа, който е бил източник на мастъра
        if ($masterRec->originId || $rec->originId) {
            $oDocId = $rec->originId;

            if (!$oDocId) {
                $oDocId = $masterRec->originId;
            }

            if ($oDocId && !Mode::is('stopRenderOrigin')) {
                $document = doc_Containers::getDocument($oDocId);
                if ($document && cls::haveInterface('doc_DocumentIntf', $document->instance)) {
                    // Добавяме клас, за да може формата да застане до привюто на документа/файла
                    if (Mode::is('screenMode', 'wide')) {
                        $className = ' floatedElement ';
                        $form->class .= $className;
                    }

                    if ($document->haveRightFor('single')) {
                        $form->layout = $form->renderLayout();
                        $tpl = new ET("<div class='preview-holder {$className}'><div style='margin-top:20px; margin-bottom:-10px; padding:5px;'><b>" . tr('Източник') . "</b></div><div class='scrolling-holder'>[#DOCUMENT#]</div></div><div class='clearfix21'></div>");

                        $docHtml = $document->getInlineDocumentBody();
                        $tpl->append($docHtml, 'DOCUMENT');
                        $form->layout->append($tpl);
                    }
                }
            }
        }
    }


    /**
     * При инпут на формата
     */
    protected static function inputQuoteDetailsForm($mvc, $form)
    {
        $rec = &$form->rec;
        $masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey});

        if(isset($rec->productId)) {
            $productInfo = cat_Products::getProductInfo($rec->productId);

            $vat = cat_Products::getVat($rec->productId, $masterRec->valior);
            $rec->vatPercent = $vat;
            $packs = cat_Products::getPacks($rec->productId);
            $form->setOptions('packagingId', $packs);
            $form->setDefault('packagingId', key($packs));

            // Ако артикула не е складируем, скриваме полето за мярка
            if (!isset($productInfo->meta['canStore'])) {
                $measureShort = cat_UoM::getShortName($rec->packagingId);
                $form->setField('packQuantity', "unit={$measureShort}");
            } else {
                $form->setField('packagingId', 'input');
            }
        }

        if ($form->isSubmitted()) {
            if (!isset($form->rec->packQuantity)) {
                $form->rec->defQuantity = true;
                $form->setDefault('packQuantity', $rec->_moq ? $rec->_moq : deals_Helper::getDefaultPackQuantity($rec->productId, $rec->packagingId));
                if (empty($rec->packQuantity)) {
                    if($rec->optional == 'yes'){
                        $form->setDefault('packQuantity', 1);
                    } else {
                        $form->setError('packQuantity', 'Не е въведено количество');
                    }
                }
            }

            // Ако артикула няма опаковка к-то в опаковка е 1, ако има и вече не е свързана към него е това каквото е било досега, ако още я има опаковката обновяваме к-то в опаковка
            $rec->quantityInPack = 1;
            if(isset($rec->productId)){
                $productInfo = cat_Products::getProductInfo($rec->productId);
                $rec->quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
            }
            $rec->quantity = $rec->packQuantity * $rec->quantityInPack;

            // Проверка дали к-то е под МКП
            deals_Helper::isQuantityBellowMoq($form, $rec->productId, $rec->quantity, $rec->quantityInPack);
            $price = null;
            if (!isset($rec->packPrice)) {
                $rec->price = null;
            } else {
                if (!$form->gotErrors()) {
                    $price = $rec->packPrice / $rec->quantityInPack;
                    $rec->packPrice = deals_Helper::getPurePrice($rec->packPrice, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
                }
            }

            // Проверка на цената
            $msg = '';
            if (!deals_Helper::isPriceAllowed($price, $rec->quantity, false, $msg)) {
                $form->setError('packPrice,packQuantity', $msg);
            }

            if (!$form->gotErrors()) {
                if (isset($price)) {
                    $price = deals_Helper::getPurePrice($price, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
                    $rec->price = $price;
                }
            }

            // При редакция, ако е променена опаковката слагаме предупреждение
            if ($rec->id) {
                $oldRec = $mvc->fetch($rec->id);
                if ($oldRec && $rec->packagingId != $oldRec->packagingId && !empty($rec->packPrice) && round($rec->packPrice, 4) == round($oldRec->packPrice, 4)) {
                    $form->setWarning('packPrice,packagingId', 'Опаковката е променена без да е променена цената|*.<br />|Сигурни ли сте, че зададената цена отговаря на  новата опаковка|*?');
                }
            }

            if (strtolower(Request::get('Act')) != 'createproduct') {
                if ($sameProduct = $mvc->fetch("#quotationId = {$rec->quotationId} AND #productId = {$rec->productId}")) {
                    if ($rec->optional == 'no' && $sameProduct->optional == 'yes' && $rec->id != $sameProduct->id) {
                        $form->setError('productId', 'Не може да добавите продукта като задължителен, защото фигурира вече като опционален!');
                        return;
                    }
                }
            }

            if (!$form->gotErrors()) {
                $idToCheck = ($form->cmd == 'save_new_row') ? null : $rec->id;

                if($rec->_createProductForm != true && deals_Helper::fetchExistingDetail($mvc, $rec->quotationId, $idToCheck, $rec->productId, $rec->packagingId, $rec->price, $rec->discount, $rec->tolerance, $rec->term, $rec->batch, null, $rec->notes, $rec->quantity)){
                    $form->setError('productId,packagingId,packPrice,discount,notes,packQuantity', 'Има въведен ред със същите данни');
                }

                if (isset($masterRec->deliveryPlaceId)) {
                    if ($locationId = crm_Locations::fetchField("#title = '{$masterRec->deliveryPlaceId}' AND #contragentCls = {$masterRec->contragentClassId} AND #contragentId = {$masterRec->contragentId}", 'id')) {
                        $masterRec->deliveryPlaceId = $locationId;
                    }
                }

                if (!$form->gotErrors()&& $form->cmd == 'save_new_row') {
                    unset($rec->id);
                }
            }
        }
    }


    /**
     * Изчисляване на цена за опаковка на реда
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    protected static function on_CalcPackPrice(core_Mvc $mvc, $rec)
    {
        if (!isset($rec->price) || empty($rec->quantityInPack)) {

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
    protected static function on_CalcPackQuantity(core_Mvc $mvc, $rec)
    {
        if (empty($rec->quantity) || empty($rec->quantityInPack)) {

            return;
        }

        $rec->packQuantity = $rec->quantity / $rec->quantityInPack;
    }


    /**
     * Проверява дали има вариация на продукт
     */
    protected function checkUnique($recs, $productId, $id, $isOptional = 'no', $notes)
    {
        $other = array_values(array_filter($recs, function ($val) use ($productId, $id, $isOptional, $notes) {
            if ($val->optional == $isOptional && $val->productId == $productId && $val->id != $id && md5($notes) == md5($val->notes)) {

                return $val;
            }
        }));

        return countR($other);
    }


    /**
     * Преди подготвяне на едит формата
     */
    protected static function on_BeforePrepareEditForm($mvc, &$res, $data)
    {
        if ($optional = Request::get('optional')) {
            $prepend = ($optional == 'no') ? 'задължителен' : 'опционален';
            $mvc->singleTitle = "|{$prepend}|* |{$mvc->singleTitle}|*";
        }
    }


    /**
     * Групираме резултатите спрямо продукта
     *
     * @var stdClass $data
     */
    protected function groupResultData(&$data)
    {
        $newRows = array();
        $error = '';

        // Подготвяме бутоните за добавяне на нов артикул
        if ($this->haveRightFor('add', (object) array('quotationId' => $data->masterId))) {
            $data->addNotOptionalBtn = ht::createBtn('Артикул', array($this, 'add', 'quotationId' => $data->masterId, 'optional' => 'no', 'ret_url' => true), false, false, "{$error} ef_icon = img/16/shopping.png, title=Добавяне на артикул към офертата");
            $data->addOptionalBtn = ht::createBtn('Опционален артикул', array($this, 'add', 'quotationId' => $data->masterId, 'optional' => 'yes', 'ret_url' => true), false, false, "{$error} ef_icon = img/16/shopping.png, title=Добавяне на опционален артикул към офертата");

            if ($this->haveRightFor('createProduct', (object) array('quotationId' => $data->masterId))) {
                $data->addNewProductBtn = ht::createBtn('Създаване', array($this, 'CreateProduct', 'quotationId' => $data->masterId, 'optional' => 'no', 'ret_url' => true), false, false, 'id=btnNewProduct,title=Създаване на нов нестандартен артикул,ef_icon = img/16/bag-new.png,order=12');
                $data->addNewProductOptionalBtn = ht::createBtn('Създаване', array($this, 'CreateProduct', 'quotationId' => $data->masterId, 'optional' => 'yes', 'ret_url' => true), false, false, 'id=btnNewProduct,title=Създаване на нов нестандартен артикул,ef_icon = img/16/bag-new.png,order=12');
            }
        }

        if ($this->haveRightFor('import', (object) array("quotationId" => $data->masterId))) {
            $data->addImportProductBtn = ht::createBtn('Импортиране', array($this, 'import', 'quotationId' => $data->masterId, 'optional' => 'no', 'ret_url' => true), false, false, 'id=btnAdd-import,title=Импортиране на артикули,ef_icon=img/16/import.png');
            $data->addImportProductOptionalBtn = ht::createBtn('Импортиране', array($this, 'import', 'quotationId' => $data->masterId, 'optional' => 'yes', 'ret_url' => true), false, false, 'id=btnAdd-import,title=Импортиране на артикули,ef_icon = img/16/import.png');
        }

        // Ако няма записи не правим нищо
        if (!$data->rows) {

            return;
        }

        // Заределяме рековете и роуовете на опционални и неопционални
        $optionalRows = $notOptionalRows = $optionalRecs = $notOptionalRecs = array();
        foreach ($data->recs as $ind => $r) {
            if ($r->optional == 'no') {
                $notOptionalRecs[$ind] = $r;
                $notOptionalRows[$ind] = $data->rows[$ind];
            } else {
                $optionalRecs[$ind] = $r;
                $optionalRows[$ind] = $data->rows[$ind];
            }
        }

        // Подравняваме ги спрямо едни други
        plg_AlignDecimals2::alignDecimals($this, $optionalRecs, $optionalRows);
        plg_AlignDecimals2::alignDecimals($this, $notOptionalRecs, $notOptionalRows);

        // Подменяме записите за показване с подравнените
        $data->rows = $notOptionalRows + $optionalRows;

        // Групираме записите за по-лесно показване
        foreach ($data->rows as $i => $row) {
            $rec = $data->recs[$i];
            if ($rec->livePrice === true) {
                $row->packPrice = "<span style='color:blue'>{$row->packPrice}</span>";
                $row->packPrice = ht::createHint($row->packPrice, 'Цената е динамично изчислена. Ще бъде записана при активиране', 'notice', false);
            }

            if (!isset($data->recs[$i]->price) && haveRole('seePrice,ceo')) {
                $row->packPrice = '???';
                $row->amount = '???';
            }

            $pId = $data->recs[$i]->productId;
            $optional = $data->recs[$i]->optional;

            // Създава се специален индекс на записа productId|optional, така
            // резултатите са разделени по продукти и дали са опционални или не
            $pId = $pId . "|{$optional}|" . md5($rec->notes);

            if($rec->optional == 'yes'){
                if($rec->quantity == 1 || empty($rec->quantity)){
                    unset($row->packQuantity);
                    unset($row->packagingId);
                    $row->packPrice .= " / " . tr(cat_UoM::getShortName($rec->packagingId));
                    $row->amount .= " / " . tr(cat_UoM::getShortName($rec->packagingId));
                }
            }

            $newRows[$pId][] = $row;
        }

        // Подреждане на груприаните записи по к-ва
        $zebra = 'zebra1';
        foreach ($newRows as &$group) {

            // Сортиране по к-во
            usort($group, function ($a, $b) {

                return (str_replace('&nbsp;', '', $a->quantity) > str_replace('&nbsp;', '', $b->quantity)) ? 1 : -1;
            });

            $group = array_values($group);
            $group[0]->rowspan = countR($group);

            foreach ($group as $index => $row) {
                if ($index != 0) {
                    unset($row->productId);
                    $row->rowspanId = $group[0]->rowspanId;
                    $row->TR_CLASS = $group[0]->TR_CLASS;
                } else {
                    $prot = md5($pId.$data->masterData->rec->id);
                    $row->rowspanId = $row->rowspanpId = "product-row{$prot}";
                    $zebra = $row->TR_CLASS = ($zebra == 'zebra0') ? 'zebra1' :'zebra0';
                }
            }
        }

        // Така имаме масив в който резултатите са групирани
        // по продукти, и това дали са опционални или не,
        $data->rows = $newRows;
    }


    /**
     * След подготовка на детайлите, изчислява се общата цена
     * и данните се групират
     */
    protected static function on_AfterPrepareDetail($mvc, $res, $data)
    {
        // Групираме резултатите по продукти и дали са опционални или не
        $mvc->groupResultData($data);
    }


    /**
     * Връща последната цена за посочения продукт направена от оферта към контрагента
     *
     * @return object $rec->price  - цена
     *                $rec->discount - отстъпка
     */
    public static function getPriceInfo($customerClass, $customerId, $date, $productId, $packagingId = null, $quantity = 1)
    {
        $me = cls::get(get_called_class());
        $Master = $me->Master->className;
        $date = empty($date) ? '0000-00-00' : $date;

        $query = $me->getQuery();
        $query->EXT('contragentClassId', $Master, 'externalName=contragentClassId,externalKey=quotationId');
        $query->EXT('contragentId', $Master, 'externalName=contragentId,externalKey=quotationId');
        $query->EXT('state', $Master, 'externalName=state,externalKey=quotationId');
        $query->EXT('date', $Master, 'externalName=date,externalKey=quotationId');
        $query->EXT('validFor', $Master, 'externalName=validFor,externalKey=quotationId');
        $query->XPR('expireOn', 'datetime', 'CAST(DATE_ADD(#date, INTERVAL #validFor SECOND) AS DATE)');

        // Филтрираме офертите за да намерим на каква цена последно сме оферирали артикула за посоченото количество
        $query->where("#contragentClassId = {$customerClass} AND #contragentId = {$customerId}");
        $query->where("#state = 'active'");
        $query->where("(#expireOn IS NULL AND #date >= '{$date}') OR (#expireOn IS NOT NULL AND #expireOn >= '{$date}')");
        $query->limit(1);

        $cloneQuery = clone $query;
        $query->where("#productId = {$productId} AND #quantity = {$quantity}");
        $query->orderBy('date=DESC,quotationId=DESC,quantity=ASC');

        $cloneQuery->where("#productId = {$productId} AND #quantity < {$quantity}");
        $cloneQuery->orderBy('date,quotationId,quantity', 'DESC');

        $rec1 = $query->fetch();
        $rec = is_object($rec1) ? $rec1 : $cloneQuery->fetch();

        $res = (object)array('price' => null);
        if ($rec) {
            $res->price = $rec->price;
            if($me instanceof sales_QuotationsDetails){
                $fee = sales_TransportValues::get('sales_Quotations', $rec->quotationId, $rec->id);

                if ($fee && $fee->fee > 0) {
                    $res->price -= round($fee->fee / $rec->quantity, 4);
                }
            }

            if ($rec->discount) {
                $res->discount = $rec->discount;
            }
        }

        return $res;
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
        $meta = cat_Products::fetchField($pRec->productId, 'canSell');
        if($meta != 'yes') return;

        $price = null;

        // Ако има цена я обръщаме в основна валута без ддс, спрямо мастъра на детайла
        if ($row->price) {
            $packRec = cat_products_Packagings::getPack($pRec->productId, $pRec->packagingId);
            $quantityInPack = is_object($packRec) ? $packRec->quantity : 1;
            $row->price /= $quantityInPack;

            $masterRec = $Master->fetch($masterId);
            $price = deals_Helper::getPurePrice($row->price, cat_Products::getVat($pRec->productId), $masterRec->currencyRate, $masterRec->chargeVat);
        }

        $optionalReq = Request::get('optional');
        $optional = ($optionalReq == 'yes');

        return $Master::addRow($masterId, $pRec->productId, $row->quantity, $row->pack, $price, $optional);
    }


    /**
     * След проверка на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (($action == 'add' || $action == 'delete' || $action == 'edit') && isset($rec)) {
            $quoteState = $mvc->Master->fetchField($rec->quotationId, 'state');
            if (!in_array($quoteState, array('draft'))) {
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * Подготовка на бутоните за добавяне на нови редове на фактурата
     */
    protected static function on_AfterPrepareListToolbar($mvc, $data)
    {
        unset($data->toolbar->buttons['btnAdd']);
        unset($data->toolbar->buttons['btnNewProduct']);
        unset($data->toolbar->buttons['btnAdd-import']);
    }


    /**
     * Конвертира един запис в разбираем за човека вид
     * Входният параметър $rec е оригиналният запис от модела
     * резултата е вербалният еквивалент, получен до тук
     */
    public static function recToVerbal_($rec, &$fields = array())
    {
        $row = parent::recToVerbal_($rec, $fields);

        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;

        if ($rec->quantityInPack != 1) {
            $measureId = cat_Products::fetchField($rec->productId, 'measureId');
            $totalQuantity = cat_UoM::round($measureId, $rec->quantity);

            // Показване на к-то в основна мярка, само ако тя е различна от мярката/опаковката на показване
            if($measureId != $rec->packagingId){
                $row->totalQuantity = core_Type::getByName('double(smartRound)')->toVerbal($totalQuantity);
                $shortUom = cat_Uom::getShortName($measureId);
                $row->totalQuantity .= ' ' . tr($shortUom);
            }
        }

        // Показваме подробната информация за опаковката при нужда
        deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
        $row->amount = $Double->toVerbal($rec->amount);

        return $row;
    }


    /**
     * Преди подготовка на полетата за показване в списъчния изглед
     */
    protected static function on_AfterPrepareListRows($mvc, $data)
    {
        if (!countR($data->recs)) return;

        $recs = &$data->recs;
        $rows = &$data->rows;
        $data->discountsOptional = $data->discounts = array();
        $data->hasDiscounts = false;
        $masterRec = $data->masterData->rec;

        core_Lg::push($masterRec->tplLang);
        $date = ($masterRec->state == 'draft') ? null : $masterRec->modifiedOn;

        foreach ($rows as $id => &$row) {
            $rec = $recs[$id];
            core_RowToolbar::createIfNotExists($row->_rowTools);
            cat_Products::addButtonsToDocToolbar($rec->productId, $row->_rowTools, $mvc->className, $id);

            if ($rec->discount) {
                $data->hasDiscounts = true;
            }

            if ($rec->optional == 'no') {
                $data->discounts[$rec->discount] = $row->discount;
            } else {
                $data->discountsOptional[$rec->discount] = $row->discount;
            }

            $row->productId = cat_Products::getAutoProductDesc($rec->productId, $date, $rec->showMode, 'public', $masterRec->tplLang);
            deals_Helper::addNotesToProductRow($row->productId, $rec->notes);

            // Ако е имало проблем при изчисляването на скрития транспорт, показва се хинт
            $fee = sales_TransportValues::get($mvc->Master, $rec->quotationId, $rec->id);

            $vat = cat_Products::getVat($rec->productId, $masterRec->date);
            $row->amount = sales_TransportValues::getAmountHint($row->amount, $fee->fee, $vat, $masterRec->currencyRate, $masterRec->chargeVat, $masterRec->currencyId, $fee->explain);

            if(isset($rec->vatPackPrice) && $data->renderVatPriceInRec){
                $row->vatPackPrice = $mvc->getFieldType('vatPackPrice')->toVerbal($rec->vatPackPrice);
            }
        }

        core_Lg::pop();
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterPrepareListRecs($mvc, $data)
    {
        $recs = &$data->recs;
        ksort($recs);

        $masterRec = $data->masterData->rec;
        $notOptional = $optional = array();
        $total = new stdClass();
        $total->discAmount = 0;
        $data->notOptionalHaveOneQuantity = true;
        $data->optionalHaveOneQuantity = true;
        $pcsUom = cat_UoM::fetchBySinonim('pcs')->id;

        if (countR($recs)) {
            foreach ($recs as $id => $rec) {
                if (!isset($rec->price)) {
                    $mvc::calcLivePrice($rec, $masterRec);
                    if (isset($rec->price)) {
                        $rec->packPrice = $rec->price * $rec->quantityInPack;
                        $rec->amount = $rec->packPrice * $rec->packQuantity;
                        $rec->livePrice = true;
                    } else {
                        $data->noTotal = true;
                    }
                }

                if(isset($rec->price)) {
                    $vat = cat_Products::getVat($rec->productId, $masterRec->date);
                    $rec->vatPackPrice = ($rec->packPrice * (1 + $vat) / $masterRec->currencyRate);
                }

                if ($rec->optional == 'no') {
                    if ($rec->packQuantity != 1 || $rec->packagingId != $pcsUom) {
                        $data->notOptionalHaveOneQuantity = false;
                    }

                    $notOptional[$id] = $rec;
                } else {
                    if ($rec->packQuantity != 1 || $rec->packagingId != $pcsUom) {
                        $data->optionalHaveOneQuantity = false;
                    }

                    $optional[$id] = $rec;
                }
            }
        }

        $data->countNotOptional = countR($notOptional);
        $data->countOptional = countR($optional);

        // Подготовка за показване на задължителнтие продукти
        deals_Helper::fillRecs($mvc, $notOptional, $masterRec);

        $notDefinedAmount = false;
        $onlyNotOptionalRec = null;

        if ($data->countNotOptional == 1 && $data->notOptionalHaveOneQuantity) {
            unset($data->noTotal);
            list($firstKey) = array_keys($notOptional);
            $onlyNotOptionalRec = $notOptional[$firstKey];
            if (!isset($onlyNotOptionalRec->price)) {
                $notDefinedAmount = true;
            }
        }

        if (!haveRole('seePrice,ceo')) {
            $data->noTotal = true;
        }

        if (empty($data->noTotal) && countR($notOptional)) {

            // Запомня се стойноста и ддс-то само на опционалните продукти
            $data->summary = deals_Helper::prepareSummary($mvc->_total, $masterRec->date, $masterRec->currencyRate, $masterRec->currencyId, $masterRec->chargeVat, false, $masterRec->tplLang);

            if (isset($data->summary->vat009) && !isset($data->summary->vat0) && !isset($data->summary->vat02)) {
                $data->summary->onlyVat = $data->summary->vat009;
                unset($data->summary->vat009);
            } elseif (isset($data->summary->vat0) && !isset($data->summary->vat009) && !isset($data->summary->vat02)) {
                $data->summary->onlyVat = $data->summary->vat0;
                unset($data->summary->vat0);
            } elseif (isset($data->summary->vat02) && !isset($data->summary->vat009) && !isset($data->summary->vat0)) {
                $data->summary->onlyVat = $data->summary->vat02;
                unset($data->summary->vat02);
            }

            // Обработваме сумарните данни
            if ($data->masterData->rec->chargeVat != 'separate') {
                $data->summary->chargeVat = $data->masterData->row->chargeVat;
            }

            if (!$data->summary->discountValue) {
                $data->summary->discountValue = '-';
                $data->summary->discountTitle = '-';
            } else {
                $data->summary->discountTitle = 'Отстъпка';
                $data->summary->discountValue = "- {$data->summary->discountValue}";
            }

            if (!$data->summary->neto) {
                $data->summary->neto = '-';
                $data->summary->netTitle = '-';
            } else {
                $data->summary->netTitle = 'Нето';
            }

            if ($notDefinedAmount === true) {
                $data->summary->value = '???';
                $data->summary->total = "<span class='quiet'>???</span>";
            }

            // Ако има само 1 артикул и той е в 1 бройка и няма опционални и цената му е динамично изчислена
            if (is_object($onlyNotOptionalRec)) {
                if ($onlyNotOptionalRec->livePrice === true) {

                    $rowAmount = cls::get('type_Double', array('params' => array('decimals' => 2)))->toVerbal($onlyNotOptionalRec->amount);
                    $data->summary->value = "<span style='color:blue'>{$rowAmount}</span>";
                    $data->summary->value = ht::createHint($data->summary->value, 'Сумата е динамично изчислена. Ще бъде записана при активиране', 'notice', false, 'width=14px,height=14px');

                    $data->summary->total = "<span style='color:blue'>{$data->summary->total}</span>";
                    $data->summary->total = ht::createHint($data->summary->total, 'Сумата е динамично изчислена. Ще бъде записана при активиране', 'notice', false, 'width=14px,height=14px');
                }
            }
        }

        // Подготовка за показване на опционалните продукти
        deals_Helper::fillRecs($mvc, $optional, $masterRec);
        $recs = $notOptional + $optional;

        // Изчисляване на цената с отстъпка
        foreach ($recs as $id => $rec) {
            if ($rec->optional == 'no') {
                $other = $mvc->checkUnique($recs, $rec->productId, $rec->id, 'no', $rec->notes);
                if ($other) {
                    unset($data->summary);
                }
            }
        }

        $data->renderVatPriceInRec = ($masterRec->chargeVat == 'separate' && (empty($data->summary) || countR($recs) == 1));
    }

    /**
     * Помощна ф-я за лайв изчисляване на цената
     *
     * @param stdClass $rec
     * @param stdClass $masterRec
     *
     * @return void;
     */
    public static function calcLivePrice($rec, $masterRec, $force = false)
    {

    }


    /**
     * Променяме рендирането на детайлите
     */
    public function renderDetail_($data)
    {
        $tpl = new ET('');
        $masterRec = $data->masterData->rec;

        // Шаблон за задължителните продукти
        $shortest = false;
        $templateFile = ($data->countNotOptional && $data->notOptionalHaveOneQuantity) ? $this->shortDetailFile : $this->normalDetailFile;
        if ($data->countNotOptional == 1 && $data->notOptionalHaveOneQuantity) {
            $templateFile = $this->shortestDetailFile;
            $shortest = true;
        }

        $dTpl = getTplFromFile($templateFile);
        if ($data->countNotOptional) {
            $dTpl->replace(1, 'DATA_COL_ATTR');
            $dTpl->replace(2, 'DATA_COL_ATTR_AMOUNT');
        }

        if ($shortest === true) {
            if ($masterRec->state != 'draft') {
                $dTpl->replace('display:none;', 'none');
            }
        }

        // Шаблон за опционалните продукти
        $optionalTemplateFile = ($data->countOptional && $data->optionalHaveOneQuantity) ? 'sales/tpl/LayoutQuoteDetailsShort.shtml' : 'sales/tpl/LayoutQuoteDetails.shtml';

        $oTpl = getTplFromFile($optionalTemplateFile);
        if ($data->countOptional) {
            $oTpl->replace(3, 'DATA_COL_ATTR');
            $oTpl->replace(4, 'DATA_COL_ATTR_AMOUNT');
        }

        $oTpl->removeBlock('totalPlace');

        $oCount = $dCount = 1;

        // Променливи за определяне да се скриват ли някои колони
        $hasQuantityColOpt = false;
        if ($data->rows) {
            foreach ($data->rows as $index => $arr) {
                list(, $optional) = explode('|', $index);
                foreach ($arr as $row) {
                    core_RowToolbar::createIfNotExists($row->_rowTools);
                    $row->tools = $row->_rowTools->renderHtml($this->rowToolsMinLinksToShow);

                    // Взависимост дали е опционален продукта го добавяме към определения шаблон
                    if ($optional == 'no') {
                        $rowTpl = $dTpl->getBlock('ROW');
                        $id = &$dCount;
                    } else {
                        $rowTpl = $oTpl->getBlock('ROW');

                        // Слага се 'opt' в класа на колоната да се отличава
                        $rowTpl->replace("-opt{$masterRec->id}", 'OPT');
                        if ($row->productId) {
                            $rowTpl->replace('-opt-product', 'OPTP');
                        }
                        $oTpl->replace("-opt{$masterRec->id}", 'OPT');
                        $id = &$oCount;
                        if ($hasQuantityColOpt !== true && ($row->quantity)) {
                            $hasQuantityColOpt = true;
                        }
                    }

                    $row->index = $id++;
                    $rowTpl->placeObject($row);
                    $rowTpl->removeBlocksAndPlaces();
                    $rowTpl->append2master();
                }
            }
        }

        if ($dCount <= 1) {
            $dTpl->replace('<tr><td colspan="6">' . tr('Няма записи') . '</td></tr>', 'ROWS');
        }

        if ($oCount <= 1) {
            $oTpl->replace('<tr><td colspan="6">' . tr('Няма записи') . '</td></tr>', 'ROWS');
        }

        if ($summary = $data->summary) {
            if ($summary->discountTitle != '-') {
                $summary->discountTitle = tr($summary->discountTitle);
            }

            if ($summary->netTitle != '-') {
                $summary->netTitle = tr($summary->netTitle);
            }

            if ($masterRec->chargeVat != 'separate') {
                $summary->vatAmount = tr($summary->vatAmount);
            }

            $dTpl->placeObject($summary, 'SUMMARY');
            $dTpl->replace($summary->sayWords, 'sayWords');

            // Ако всички артикули имат валидна отстъпка показваме я в обобщената информация
            if (isset($summary) && countR($data->discounts) == 1) {
                if (key($data->discounts)) {
                    $dTpl->replace($data->discounts[key($data->discounts)], 'discountPercent');
                }
            }
        } else {
            $dTpl->removeBlock('totalPlace');
        }

        $vatRow = " " . (($masterRec->chargeVat == 'yes') ? tr('с ДДС') : (($masterRec->chargeVat == 'separate') ? "<b>" . tr('без ДДС') . "</b>" : tr('без ДДС')));


        $miscMandatory = $masterRec->currencyId . $vatRow;
        $miscOptional = $masterRec->currencyId . $vatRow;
        if (countR($data->discounts) && $data->hasDiscounts === true) {
            $miscMandatory .= ', ' . tr('без извадени отстъпки');
        }

        if (countR($data->discountsOptional) && $data->hasDiscounts === true) {
            $miscOptional .= ', ' . tr('без извадени отстъпки');
        }

        // Ако сме чернова или има поне един задължителен артикул, рендираме таблицата му
        if ($masterRec->state == 'draft' || $dCount > 1) {
            $tpl->append($this->renderListToolbar($data), 'ListToolbar');
            $dTpl->append(tr('Оферирани'), 'TITLE');

            if ($shortest !== true) {
                $dTpl->append($miscMandatory, 'MISC');
            }

            if (isset($data->addNotOptionalBtn)) {
                $dTpl->append($data->addNotOptionalBtn, 'ADD_BTN');
            }

            if (isset($data->addNewProductBtn)) {
                $dTpl->append($data->addNewProductBtn, 'ADD_BTN');
            }

            if (isset($data->addImportProductBtn)) {
                $dTpl->append($data->addImportProductBtn, 'ADD_BTN');
            }

            $dTpl->removeBlocks();
            $tpl->append($dTpl, 'MANDATORY');
        }

        // Ако сме чернова и има поне един опционален артикул, рендираме таблицата за артикули
        if ($masterRec->state == 'draft' || $oCount > 1) {
            $oTpl->append(tr('Опционални'), 'TITLE');
            $oTpl->append($miscOptional, 'MISC');
            if (isset($data->addOptionalBtn)) {
                $oTpl->append($data->addOptionalBtn, 'ADD_BTN');
            }

            if (isset($data->addNewProductOptionalBtn)) {
                $oTpl->append($data->addNewProductOptionalBtn, 'ADD_BTN');
            }

            if (isset($data->addImportProductOptionalBtn)) {
                $oTpl->append($data->addImportProductOptionalBtn, 'ADD_BTN');
            }

            $oTpl->removePlaces();
            $oTpl->removeBlocks();
            $tpl->append($oTpl, 'OPTIONAL');
        }

        if (!$hasQuantityColOpt) {
            $tpl->append(".quote-col-opt{$masterRec->id} {display:none;} .product-id-opt-product {width:65%;}", 'STYLES');
        }

        // Закачане на JS
        $tpl->push('sales/js/ResizeQuoteTable.js', 'JS');
        jquery_Jquery::run($tpl, 'resizeQuoteTable();');
        jquery_Jquery::runAfterAjax($tpl, 'resizeQuoteTable');

        return $tpl;
    }
}
