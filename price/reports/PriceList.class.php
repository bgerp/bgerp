<?php


/**
 * Драйвер за спарвка от тип 'Ценоразпис'
 *
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Продажби » Ценоразпис
 */
class price_reports_PriceList extends frame2_driver_TableData
{
    /**
     * Закръгляне на цените по подразбиране
     */
    const DEFAULT_ROUND = 5;
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'frame2_ReportIntf,label_SequenceIntf=price_interface_LabelImpl';
    
    
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'sales, priceDealer, ceo';
    
    
    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     *
     * @var string
     */
    protected $hashField = 'productId';
    
    
    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField = 'groupName';
    
    
    /**
     * Какъв да е класа на групирания ред
     */
    protected $groupByFieldClass = 'pricelist-group-label';


    /**
     * Кои полета от листовия изглед да може да се сортират
     */
    protected $sortableListFields = 'code,productId,price';


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('date', 'datetime(smartTime)', 'caption=Към дата,after=title');
        $fieldset->FLD('policyId', 'key(mvc=price_Lists, select=title)', 'caption=Цени->Политика, silent, mandatory,after=date,removeAndRefreshForm=listingId,single=none');
        $fieldset->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Цени->Валута,input,after=policyId,single=none');
        $fieldset->FLD('vat', 'enum(yes=с включено ДДС,no=без ДДС)', 'caption=Цени->ДДС,after=currencyId,single=none');
        $fieldset->FLD('vatExceptionId', 'key(mvc=cond_VatExceptions,select=title,allowEmpty)', 'caption=Цени->ДДС изключение,after=currencyId');
        $fieldset->FLD('period', 'time(suggestions=1 ден|1 седмица|1 месец|6 месеца|1 година)', 'caption=Цени->Изменени цени,after=vat,single=none');
        $fieldset->FLD('round', 'int(Min=0,max=6)', 'caption=Цени->Точност,autohide,after=period');
        $fieldset->FLD('packType', 'enum(yes=Да,no=Не,base=Основна)', 'caption=Филтър->Опаковки,columns=3,after=round,single=none,silent,removeAndRefreshForm=packagings');
        $fieldset->FLD('packagings', 'keylist(mvc=cat_UoM,select=name)', 'caption=Филтър->Избор,columns=3,placeholder=Всички опаковки,after=packType,single=none');
        $fieldset->FLD('productGroups', 'keylist(mvc=cat_Groups,select=name,makeLinks,allowEmpty)', 'caption=Филтър->Групи,columns=2,placeholder=Всички,after=packagings,single=none');
        $fieldset->FLD('listingId', 'keylist(mvc=cat_Listings,select=id,allowEmpty)', 'caption=Филтър->Листвани артикули,columns=2,after=productGroups,input=hidden,maxRadio=1');
        $fieldset->FLD('expandGroups', 'enum(yes=Да,no=Не)', 'caption=Филтър->Подгрупи,columns=2,after=listingId,single=none');
        $fieldset->FLD('notInGroups', 'keylist(mvc=cat_Groups,select=name,makeLinks,allowEmpty)', 'caption=Филтър->Без групи,after=expandGroups,single=none');
        $fieldset->FLD('displayDetailed', 'enum(no=Съкратен изглед,yes=Разширен изглед)', 'caption=Допълнително->Артикули,after=expandGroups,single=none');
        $fieldset->FLD('showMeasureId', 'enum(yes=Показване,no=Скриване)', 'caption=Допълнително->Основна мярка,after=displayDetailed,single=internal');
        $fieldset->FLD('showEan', 'enum(yes=Да,no=Не)', 'caption=Допълнително->Показване ЕАН,after=showMeasureId,single=internal');
        $fieldset->FLD('lang', 'enum(auto=Текущ,bg=Български,en=Английски)', 'caption=Допълнително->Език,after=showEan,single=internal');
        $fieldset->FLD('showUiextLabels', 'enum(yes=Включено,no=Изключено)', 'caption=Допълнително->Тагове на редовете,after=showEan,single=internal');
        $fieldset->FLD('templateType', 'enum(default=Стандартен изглед,foods=Храни)', 'caption=Допълнително->Изглед,after=lang,single=internal');
    }


    /**
     * Връща заглавието на отчета
     *
     * @param stdClass $rec - запис
     * @param bool $isPlain - дали заглавието да е чисто (без окрасяване)
     *
     * @return string|NULL - заглавието или NULL, ако няма
     */
    public function getTitle($rec, $isPlain = false)
    {
        $title = $rec->title;
        if(empty($title)) {
            $policyName = price_Lists::getTitleById($rec->policyId);
            $title = "Ценоразпис \"{$policyName}\"";
        }
        
        return $title;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver   $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        $form->setField('round', 'placeholder=' . self::DEFAULT_ROUND);
        $form->setSuggestions('round', array('' => '', 2 => 2, 4 => 4));
        $form->setDefault('lang', 'auto');
        $form->setDefault('showEan', 'yes');
        $form->setDefault('showMeasureId', 'yes');
        $form->setDefault('displayDetailed', 'no');
        $form->setDefault('packType', 'yes');
        $form->setDefault('showUiextLabels', 'no');
        if(!core_Packs::isInstalled('uiext')){
            $form->setFiedl('showUiextLabels', 'input=none');
        }

        $suggestions = cat_UoM::getPackagingOptions();
        $form->setSuggestions('packagings', $suggestions);
        
        // Ако е в папка на контрагент
        $defaultListId = price_ListRules::PRICE_LIST_CATALOG;
        $Cover = doc_Folders::getCover($form->rec->folderId);
        if ($Cover->haveInterface('crm_ContragentAccRegIntf')) {
            $defaultListId = price_ListToCustomers::getListForCustomer($Cover->getClassId(), $Cover->that);
            $form->setDefault('vat', deals_Helper::getDefaultChargeVat($Embedder, $form->rec));
            $form->setDefault('currencyId', $Cover->getDefaultCurrencyId());
            
            $listOptions = price_Lists::getAccessibleOptions($Cover->className, $Cover->that);
        } else {
            $listOptions = price_Lists::getAccessibleOptions(null, null, false);
        }
        
        $form->setOptions('policyId', $listOptions);
        $form->setDefault('policyId', $defaultListId);

        // Ако е в папка с контрагентски данни
        if ($Cover->haveInterface('crm_ContragentAccRegIntf')) {
            $cData = doc_Folders::getContragentData($form->rec->folderId);
            $bgId = drdata_Countries::fetchField("#commonName = 'Bulgaria'", 'id');
            $lang = (!empty($cData->countryId) && $cData->countryId != $bgId) ? 'en' : 'bg';
            $form->setDefault('lang', $lang);
        }
        
        if ($form->rec->packType != 'yes') {
            $form->setField('packagings', 'input=none');
        }

        // Ако има посочена ценова политика
        if(isset($rec->policyId)){
            $listingId = null;

            if ($Cover->haveInterface('crm_ContragentAccRegIntf')) {

                // Ако справката е в папка на клиент взима се списъка му листвани артикули
                $listingId = cond_Parameters::getParameter($Cover->className, $Cover->that, 'salesList');
            } else {

                // Ако справката не е в папка на клиент, проверява се дали политиката е частна
                // ако е частна, взима се списъка от листваните артикули на клиента
                $listRec = price_Lists::fetch($rec->policyId);
                if($listRec->isPublic = 'no'){
                    if($foundRec = price_ListToCustomers::fetch("#listId = {$rec->policyId}")){
                        $listingId = cond_Parameters::getParameter($foundRec->cClass, $foundRec->cId, 'salesList');
                    }
                }
            }

            // Ако има намерен списък с листвани артикули, показва се като възможност за избор
            if(isset($listingId)){
                $form->setField('listingId', 'input');
                $form->setOptions('listingId', array('' => '') + array("{$listingId}" => cat_Listings::getTitleById($listingId, false)));
             }
        }
    }
    
    
    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec
     * @param stdClass $data
     *
     * @return array
     */
    protected function prepareRecs($rec, &$data = null)
    {
        $date = !empty($rec->date) ? $rec->date : dt::now();
        $date = strlen($date) == 10 ? "{$date} 23:59:59" : $date;

        $dateBefore = (!empty($rec->period)) ? (dt::addSecs(-1 * $rec->period, $date, false) . ' 23:59:59') : null;
        $round = !empty($rec->round) ? $rec->round : self::DEFAULT_ROUND;
        $sellableProducts = cat_Products::getProducts(null, null, null, 'canSell', null, null, false, $rec->productGroups, $rec->notInGroups, 'yes');

        $sellableProducts = array_keys($sellableProducts);
        unset($sellableProducts[0]);

        // Ако има избран списък с листвани артикули оставят се само тези от тях, които отговарят на филтъра
        if(isset($rec->listingId)){
            $listingProductIds = arr::extractValuesFromArray(cat_Listings::getAll($rec->listingId), 'productId');
            $sellableProducts = array_values(array_intersect($sellableProducts, $listingProductIds));
        }

        // Вдигане на времето за изпълнение, според броя записи
        $timeLimit = countR($sellableProducts) * 0.7;
        core_App::setTimeLimit($timeLimit, false, 600);

        // Ако няма опаковки, това са всички
        $recs = $packArr = array();
        $currencyRate = currency_CurrencyRates::getRate($rec->date, $rec->currencyId, acc_Periods::getBaseCurrencyCode($rec->date));

        if ($rec->packType == 'yes') {
            $packArr = (!empty($rec->packagings)) ? keylist::toArray($rec->packagings) : arr::make(array_keys(cat_UoM::getPackagingOptions(), true));
        }

        $data->variationId = price_ListVariations::getActiveVariationId($rec->policyId, $date);

        // За всеки продаваем стандартен артикул
        foreach ($sellableProducts as $id) {
            $productRec = cat_Products::fetch($id, 'groups,code,measureId,name,isPublic,nameEn');

            $quantity = 1;
            $obj = (object) array('productId' => $productRec->id,
                'code' => (!empty($productRec->code)) ? $productRec->code : "Art{$productRec->id}",
                'measureId' => $productRec->measureId,
                'vat' => cat_Products::getVat($productRec->id, $date, $rec->vatExceptionId),
                'packs' => array(),
                'groups' => $productRec->groups);

            if ($rec->packType == 'base') {
                $basePack = cat_products_Packagings::fetch("#productId = {$productRec->id} AND #isBase = 'yes'");
                if (is_object($basePack)) {
                    $obj->measureId = $basePack->packagingId;
                    $quantity = $basePack->quantity;
                }
            }

            // Изчислява се цената по избраната политика
            $priceByPolicy = price_ListRules::getPrice($rec->policyId, $productRec->id, null, $date);
            $obj->name = cat_Products::getVerbal($productRec, 'name');
            $obj->price = deals_Helper::getDisplayPrice($priceByPolicy, $obj->vat, $currencyRate, $rec->vat);

            // Ако има избран период в който да се гледа променена ли е цената
            if (isset($dateBefore)) {
                $oldPrice = price_ListRules::getPrice($rec->policyId, $productRec->id, null, $dateBefore);
                $oldPrice = round($oldPrice, $round);
                $priceByPolicy = round($priceByPolicy, $round);
                $differenceHint = null;

                // Колко процента е промяната спрямо старата цена
                if (empty($oldPrice)) {
                    $obj->type = 'new';
                    $difference = 1;
                } elseif (!empty($oldPrice) && empty($priceByPolicy)) {
                    $obj->type = 'removed';
                    $difference = -1;
                } else {
                    $difference = (trim($priceByPolicy) - trim($oldPrice)) / $oldPrice;
                    $difference = round($difference, 4);
                    $differenceHint = "Стара цена|*: {$oldPrice}; |Нова цена|*: {$priceByPolicy}";
                }

                // Ако няма промяна, артикулът не се показва
                if ($difference == 0) {
                    continue;
                }

                $obj->differenceHint = $differenceHint;
                $obj->difference = $difference;
            }
            price_ListRules::$alreadyReplaced = array();

            $obj->price *= $quantity;

            // Ако има цена, показват се и избраните опаковки с техните цени
            if (!empty($priceByPolicy) && countR($packArr)) {
                $packQuery = cat_products_Packagings::getQuery();
                $packQuery->where("#productId = {$productRec->id} AND #state != 'closed'");
                $packQuery->in('packagingId', $packArr);
                $packQuery->show('eanCode,quantity,packagingId');
                while ($packRec = $packQuery->fetch()) {
                    $packRec->price = $packRec->quantity * $priceByPolicy;
                    $packRec->price = deals_Helper::getDisplayPrice($packRec->price, $obj->vat, $currencyRate, $rec->vat);
                    $obj->packs[$packRec->packagingId] = $packRec;
                }

                // Ако ще се скрива мярката и няма опаковки, няма какво да се показва, освен ако артикула не е бил премахнат
                if ($rec->showMeasureId != 'yes' && !countR($obj->packs)) {
                    continue;
                }
            }

            if ($obj->type != 'removed' && empty($priceByPolicy)) {
                continue;
            }

            if($rec->showEan == 'yes'){
                if($ean = cat_products_Packagings::getPack($obj->productId, $obj->measureId, 'eanCode')){
                    $obj->eanCode = $ean;
                }
            }

            $recs[$id] = $obj;
        }
        
        // Ако има подговени записи
        if (countR($recs)) {
           
           // Ако няма избрани групи, търсят се всички
            $productGroups = $rec->productGroups;
            if (empty($productGroups)) {
                $productGroups = arr::extractValuesFromArray(cat_Groups::getQuery()->fetchAll(), 'id');
                $productGroups = keylist::fromArray($productGroups);
            }
            
            // Филтриране на артикулите според избраните групи
            if ($rec->lang != 'auto') {
                core_Lg::push($rec->lang);
            }
            
            $expand = $rec->expandGroups === 'yes';
            store_InventoryNoteSummary::filterRecs($productGroups, $recs, 'code', 'name', 'groups', $expand);
            
            if ($rec->lang != 'auto') {
                core_Lg::pop();
            }
        }

        return $recs;
    }
    
    
    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec  - записа
     * @param stdClass $dRec - чистия запис
     *
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $row = new stdClass();
        $display = ($rec->displayDetailed == 'yes') ? 'detailed' : 'short';

        if($rec->templateType == 'foods'){
            $row->productId = cat_Products::getAutoProductDesc($dRec->productId, null, 'short', 'public', $rec->lang, null, false);
            $previewHandler = cat_Products::getParams($dRec->productId, 'preview');
            if($previewHandler){
                $thumb = new thumb_Img($previewHandler, 200, 200, 'fileman', null, null, 3, 'small-no-change', null, true);
                $row->photo = $thumb->createImg(array('class' => 'priceListFoodImage', 'title' => cat_Products::getTitleById($dRec->productId)))->getContent();
                if(!Mode::isReadOnly()){
                    $row->photo = ht::createLink($row->photo, cat_Products::getSingleUrlArray($dRec->productId));
                }
            }

            $row->weightVerbal = cat_Products::getParams($dRec->productId, 'weight');
        } else {
            Mode::push('noIconImg', true);
            $row->productId = cat_Products::getAutoProductDesc($dRec->productId, null, $display, 'public', $rec->lang, null, false);
            Mode::pop('noIconImg');
        }

        $row->groupName = core_Type::getByName('varchar')->toVerbal($dRec->groupName);
        $row->code = core_Type::getByName('varchar')->toVerbal($dRec->code);
        $row->measureId = cat_UoM::getShortName($dRec->measureId);

        $decimals = $rec->round ?? self::DEFAULT_ROUND;
        $row->price = core_Type::getByName("double(decimals={$decimals})")->toVerbal($dRec->price);
        if($rec->templateType == 'foods'){
            $row->price = currency_Currencies::decorate($row->price, $rec->currencyId);
            if($rec->currencyId == 'BGN'){
                $euroRate = currency_CurrencyRates::getRate($rec->date, 'EUR', 'BGN');
                $priceEuro = round($dRec->price, $decimals) / $euroRate;
                $priceEuroVerbal = core_Type::getByName("double(decimals={$decimals})")->toVerbal($priceEuro);
                $row->price .= " / " . currency_Currencies::decorate($priceEuroVerbal, 'EUR', true);
            }


        }

        // Рендиране на опаковките в таблица
        if (countR($dRec->packs)) {
            $row->packs = $this->getPackTable($rec, $dRec);
        }
        
        if (!Mode::isReadOnly()) {
            $row->ROW_ATTR['class'] = 'state-active';
        }
        
        // Показване на процента промяна
        if (!empty($rec->period)) {
            if ($dRec->type == 'new') {
                $row->difference = "<span class='price-list-new-item'>" . tr('Нов') . '</span>';
            } elseif ($dRec->type == 'removed') {
                $row->difference = "<span class='price-list-removed-item'>" . tr('Премахнат') . '</span>';
            } else {
                $row->difference = core_Type::getByName('percent(decimals=2)')->toVerbal($dRec->difference);
                if ($dRec->difference > 0) {
                    $row->difference = "<span class='green'>+{$row->difference}</span>";
                } else {
                    $row->difference = "<span class='red'>{$row->difference}</span>";
                }

                if(!empty($dRec->differenceHint) && !Mode::isReadOnly()){
                    $row->difference = ht::createHint($row->difference, $dRec->differenceHint);
                }
            }
        }
        
        // Ако има баркод на основната мярка да се показва и той
        if(!empty($dRec->eanCode)){
            $eanCode = core_Type::getByName('varchar')->toVerbal($dRec->eanCode);
            if(!Mode::isReadOnly() && barcode_Search::haveRightFor('list')){
                $eanCode = ht::createLink($eanCode, array('barcode_Search', 'search' => $eanCode));
            }
            $row->measureId = "{$eanCode} {$row->measureId}";
        }
        
        return $row;
    }
    
    
    /**
     * Рендиране на таблицата с опаковките
     *
     * @param stdClass $rec
     * @param stdClass $dRec
     *
     * @return core_ET $tpl
     */
    private function getPackTable($rec, $dRec)
    {
        $rows = array();
        
        // Вербализиране на опаковките ако има
        foreach ($dRec->packs as $packRec) {
            $packName = cat_UoM::getVerbal($packRec->packagingId, 'name');
            deals_Helper::getPackInfo($packName, $dRec->productId, $packRec->packagingId, $packRec->quantity);
            $decimals = isset($rec->round) ? $rec->round : self::DEFAULT_ROUND;
            $rows[$packRec->packagingId] = (object) array('packagingId' => $packName, 'price' => core_Type::getByName("double(decimals={$decimals})")->toVerbal($packRec->price));
            if (!empty($packRec->eanCode)) {
                $eanCode = core_Type::getByName('varchar')->toVerbal($packRec->eanCode);
                if (!Mode::isReadOnly() && barcode_Search::haveRightFor('list')) {
                    $eanCode = ht::createLink($eanCode, array('barcode_Search', 'search' => $eanCode));
                }
                $rows[$packRec->packagingId]->eanCode = $eanCode;
            }
        }
        
        $fieldset = new core_FieldSet();
        $fieldset->FLD('eanCode', 'varchar', 'tdClass=small');
        $fieldset->FLD('price', 'varchar', 'smartCenter');
        
        // Рендиране на таблицата, в която ще се показват опаковките
        $table = cls::get('core_TableView', array('mvc' => $fieldset));
        $table->tableClass = 'pricelist-report-pack-table';
        $table->thHide = true;
        $listFields = arr::make('eanCode=ЕАН,packagingId=Опаковка,price=Цена', true);
        if ($rec->showEan != 'yes') {
            unset($listFields['eanCode']);
        }
        
        $tpl = $table->get($rows, $listFields);
        $tpl->removeBlocksAndPlaces();
        
        return $tpl;
    }
    
    
    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec    - записа
     * @param bool     $export - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');
        $decimals = isset($rec->round) ? $rec->round : self::DEFAULT_ROUND;
        if ($export === true) {
            $fld->FLD('groupName', 'varchar', 'caption=Група');
        }
        $fld->FLD('code', 'varchar', 'caption=Код,tdClass=centered');
        $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
        if ($export === true) {
            $fld->FLD('eanCode', 'varchar', 'caption=ЕАН');
        }
        if ($rec->showMeasureId == 'yes' || $export === true) {
            $fld->FLD('measureId', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered nowrap small quiet');
            $fld->FLD('price', "double(decimals={$decimals})", 'caption=Цена');
        }
        if ($export === true) {
            $fld->FLD('currencyId', 'varchar', 'caption=Валута');
        } elseif ($rec->packType == 'yes') {
            $fld->FLD('packs', 'html', 'caption=Опаковка');
        }
        if (!empty($rec->period)) {
            if ($export === false) {
                $fld->FLD('difference', 'varchar', 'caption=Промяна,smartCenter');
            } else {
                $fld->FLD('difference', 'percent', 'caption=Промяна');
            }
        }
        
        return $fld;
    }
    
    
    /**
     * Какъв ще е езика с който ще се рендират данните на шаблона
     *
     * @param stdClass $rec
     *
     * @return string|null езика с който да се рендират данните
     */
    public function getRenderLang($rec)
    {
        return ($rec->lang == 'auto') ? null : $rec->lang;
    }
    
    
    /**
     * рендиране на таблицата
     *
     * @param stdClass $rec
     * @param stdClass $data
     *
     * @return core_ET $tpl
     */
    protected function renderTable($rec, &$data)
    {
        $tpl = parent::renderTable($rec, $data);

        $vatRow = core_Type::getByName('enum(yes=с включено ДДС,no=без ДДС)')->toVerbal($rec->vat);
        $beforeRow = tr("Всички цени са в|* {$rec->currencyId}, |{$vatRow}|*");
        $vatPlaceholder = ($rec->templateType == 'foods') ? 'VAT_STATE' : 'TABLE_BEFORE';
        $tpl->prepend($beforeRow, $vatPlaceholder);

        if($rec->displayDetailed == 'yes'){
            Mode::set('saveJS', true);
        }

        return $tpl;
    }
    
    
    /**
     * След вербализирането на данните
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $row
     * @param stdClass            $rec
     * @param array               $fields
     */
    protected static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {
        if(isset($rec->listingId)) {
            $row->listingId = cat_Listings::getHyperlink($rec->listingId, true);
        }

        $row->policyId = price_Lists::getHyperlink($rec->policyId, true);
        $row->productGroups = (!empty($rec->productGroups)) ? implode(', ', cat_Groups::getLinks($rec->productGroups)) : tr('Всички');
        if(!empty($rec->notInGroups)){
            $row->notInGroups = implode(', ', cat_Groups::getLinks($rec->notInGroups));
        }
        
        if ($rec->packType == 'yes') {
            $row->packagings = (!empty($rec->packagings)) ? core_Type::getByName('keylist(mvc=cat_UoM,select=name)')->toVerbal($rec->packagings): tr('Всички');
        } elseif ($rec->packType == 'no') {
            $row->packagings = tr('Без опаковки');
        } else {
            $row->packagings = tr('Само основна опаковка');
        }
        
        if (!empty($rec->period)) {
            $row->period = core_Type::getByName('time')->toVerbal($rec->period);
            $row->periodDate = dt::mysql2verbal(dt::addSecs(-1 * $rec->period, $rec->date, false), 'd.m.Y');
        }

        $dateHint = false;
        $date = $rec->date;
        if (empty($date)) {
            $date = dt::verbal2mysql($rec->lastRefreshed);
            $dateHint = true;
        }
        $row->date = core_Type::getByName('datetime(format=smartTime)')->toVerbal($date);
        if($dateHint){
            $row->date = ht::createHint($row->date, 'Датата ще се опресни при следващата актуализация', 'notice', false);
        }
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager     $Embedder
     * @param core_ET           $tpl
     * @param stdClass          $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        if(isset($data->rec->data->variationId)){
            $data->row->variationId = price_Lists::getHyperlink($data->rec->data->variationId, true);
        }

        if (Mode::is('printing') || Mode::is('text', 'xhtml')){
            if($data->rec->showLetterHeadWhenSending == 'no') return;
            unset($data->row->variationId);
        }

        $customTpl = $Driver->getReportLayoutTpl($data->rec);
        if($customTpl instanceof core_ET){
            $fieldTpl = new core_ET("");
            if(!Mode::isReadOnly()){
                $fieldTpl = $customTpl->getBlock('MASTER_BLOCK');
            }
        } else {
            $fieldTpl = new core_ET(tr("|*<fieldset class='detail-info'>
                                            <legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                            <div class='small'>
                                                <div>|Политика|*</span>: <b>[#policyId#]</b></div>
                                                <!--ET_BEGIN variationId--><div>|Вариация|*</span>: <b>[#variationId#]</b></div><!--ET_END variationId-->
                                                <div>|Цени към|*</span>: <b>[#date#]</b></div>
                                                <!--ET_BEGIN period--><div>|Изменени за|*: [#period#] (|от|* [#periodDate#])</div><!--ET_END period-->
                                                <div>|Групи|*: [#productGroups#]</div>
                                                <!--ET_BEGIN notInGroups--><div>|С изключение на|*: [#notInGroups#]</div><!--ET_END notInGroups-->
                                                <div>|Опаковки|*: [#packagings#]</div>
                                            </div>
                                        </fieldset>"));
        }

        foreach (array('periodDate', 'date', 'period', 'productGroups', 'notInGroups', 'packagings', 'policyId', 'variationId') as $field) {
            $fieldTpl->replace($data->row->{$field}, $field);
        }

        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }


    /**
     * При събмитване на формата
     *
     * @param frame2_driver_Proto $Driver   $Driver
     * @param embed_Manager       $Embedder
     * @param core_Form           $form
     */
    protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
    {
        if ($form->isSubmitted()) {
            if (cat_Groups::checkForNestedGroups($form->rec->productGroups)) {
                $form->setError('productGroups', 'Избрани са вложени групи');
            }
            
            if ($form->rec->packType != 'yes') {
                $form->rec->packagings = null;
            }
        }
    }
    
    
    /**
     * Връща редовете, които ще се експортират от справката
     *
     * @param stdClass       $rec         - запис
     * @param core_BaseClass $ExportClass - клас за експорт (@see export_ExportTypeIntf)
     *
     * @return array - записите за експорт
     */
    protected function getRecsForExport($rec, $ExportClass)
    {
        $exportRecs = array();
        foreach ($rec->data->recs as $dRec) {
            $clone = clone $dRec;
            $clone->currencyId = $rec->currencyId;
            
            $exportRecs[] = $clone;
            if (countR($dRec->packs)) {
                foreach ($dRec->packs as $packRec) {
                    $clone1 = clone $clone;
                    $clone1->packs = array();
                    $clone1->price = $packRec->price;
                    $clone1->eanCode = $packRec->eanCode;
                    $clone1->measureId = $packRec->packagingId;
                    
                    $exportRecs[] = $clone1;
                }
            }
        }
        
        return $exportRecs;
    }
    
    
    /**
     * Заглавие от източника на етикета
     *
     * @param mixed $id
     *
     * @return void
     */
    public function getLabelSourceLink($id)
    {
        return frame2_Reports::getLabelSourceLink($id);
    }
    
    
    /**
     * Може ли справката да бъде изпращана по имейл
     *
     * @param mixed $rec
     *
     * @return bool
     */
    public function canBeSendAsEmail($rec)
    {
        return true;
    }
    
    
    /**
     * Да се изпраща ли нова нотификация на споделените потребители, при опресняване на отчета
     *
     * @param stdClass $rec
     *
     * @return bool $res
     */
    public function canSendNotificationOnRefresh($rec)
    {
        return true;
    }
    
    
    /**
     * Изборът на потребители които да бъдат нотифицирани при обновяване дали са задължителни
     *
     * @param mixed $rec
     *
     * @return boolean
     */
    public function requireUserForNotification($rec)
    {
        return false;
    }


    /**
     * Да се показват ли разширените тагове на редовете на справката
     *
     * @param stdClass $rec
     * @return bool
     */
    protected function showUiextRowLabelsIfExist($rec)
    {
        return $rec->showUiextLabels == 'yes';
    }


    /**
     * Връща частния шаблон за отчета
     *
     * @param stdClass $rec
     * @return core_ET|null
     */
    protected function getReportLayoutTpl($rec)
    {
        if($rec->templateType == 'foods') return getTplFromFile("price/tpl/templates/FoodPriceList.shtml");

        return null;
    }


    /**
     * Рендиране на частен изглед на таблицата
     *
     * @param stdClass $rec
     * @param stdClass$data
     * @return core_ET|null
     */
    protected function renderCustomLayout($rec, $data)
    {
        if($tpl = parent::renderCustomLayout($rec, $data)){

            // Коя дата да показваме ако специалния изглед
            $date = dt::now();
            $cH = dt::mysql2verbal($date, 'H');
            if($cH >= '18'){
                $date = dt::addDays(1, $date);
            }
            $cDay = dt::mysql2verbal($date, 'd');
            $lg = ($rec->lang == 'auto') ? null : $rec->lang;
            $cDayWithSuffix = dt::getDayWithSuffix($cDay, $lg);
            $cMonth = mb_strtolower(dt::mysql2verbal($date, 'F'));
            $tpl->append("{$cDayWithSuffix} {$cMonth}", 'currentDate');

            $counter = 0;
            foreach ($data->rows as $row){
                if(!empty($row->photo) && !($row instanceof core_ET) && $counter < 16){
                    $counter++;
                    $block = clone $tpl->getBlock('DETAIL_ROW_WITH_PHOTO');
                    $photo = ht::createElement("div", array('class' => 'gridPhoto'), $row->photo, true);
                    $block->append($photo);
                    $block->removeBlocksAndPlaces();
                    $tpl->append($block, 'GRID');
                }
            }
        }

        return $tpl;
    }
}
