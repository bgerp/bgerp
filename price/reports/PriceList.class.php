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
class price_reports_PriceList extends price_reports_PriceListProto
{
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
        parent::addFields($fieldset);

        $fieldset->FLD('period', 'time(suggestions=1 ден|1 седмица|1 месец|6 месеца|1 година)', 'caption=Цени->Изменени цени,after=vat,single=none');
        $fieldset->setField('round', 'after=period');
        $fieldset->FLD('packType', 'enum(yes=Да,no=Не,base=Основна)', 'caption=Филтър->Опаковки,columns=3,after=round,single=none,silent,removeAndRefreshForm=packagings');
        $fieldset->FLD('packagings', 'keylist(mvc=cat_UoM,select=name)', 'caption=Филтър->Избор,columns=3,placeholder=Всички опаковки,after=packType,single=none');
        $fieldset->setField('productGroups', 'after=packagings');
        $fieldset->FLD('displayDetailed', 'enum(no=Съкратен изглед,yes=Разширен изглед)', 'caption=Допълнително->Артикули,after=expandGroups,single=none');
        $fieldset->FLD('showMeasureId', 'enum(yes=Показване,no=Скриване)', 'caption=Допълнително->Основна мярка,after=displayDetailed,single=internal');
        $fieldset->FLD('showEan', 'enum(yes=Да,no=Не)', 'caption=Допълнително->Показване ЕАН,after=showMeasureId,single=internal');
        $fieldset->setField('lang', 'after=showEan');
        $fieldset->FLD('showUiextLabels', 'enum(yes=Включено,no=Изключено)', 'caption=Допълнително->Тагове на редовете,after=showEan,single=internal');
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
        if (empty($title)) {
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
        $form->setDefault('showEan', 'yes');
        $form->setDefault('showMeasureId', 'yes');
        $form->setDefault('displayDetailed', 'no');
        $form->setDefault('packType', 'yes');
        $form->setDefault('showUiextLabels', 'no');
        if (!core_Packs::isInstalled('uiext')) {
            $form->setField('showUiextLabels', 'input=none');
        }

        $suggestions = cat_UoM::getPackagingOptions();
        $form->setSuggestions('packagings', $suggestions);

        if ($form->rec->packType != 'yes') {
            $form->setField('packagings', 'input=none');
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
        $common = $this->prepareCommonData($rec, $data);

        $dateBefore = (!empty($rec->period)) ? (dt::addSecs(-1 * $rec->period, $common->date, false) . ' 23:59:59') : null;
        $round = !empty($rec->round) ? $rec->round : self::DEFAULT_ROUND;

        // Ако няма опаковки, това са всички
        $recs = $packArr = array();

        if ($rec->packType == 'yes') {
            $packArr = (!empty($rec->packagings)) ? keylist::toArray($rec->packagings) : arr::make(array_keys(cat_UoM::getPackagingOptions(), true));
        }

        // Нормализиран lookup за избраните опаковки (за филтриране в PHP)
        $packLookup = array();
        foreach ($packArr as $pId) {
            $packLookup[$pId] = $pId;
        }

        $basePackagings = array();
        if ($rec->packType == 'base' && countR($common->sellableProducts)) {
            $packQuery = cat_products_Packagings::getQuery();
            $packQuery->in('productId', $common->sellableProducts);
            $packQuery->where("#isBase = 'yes'");
            while ($packRec = $packQuery->fetch()) {
                $basePackagings[$packRec->productId] = $packRec;
            }
        }

        // Batch зареждане на опаковките и ЕАН-кодовете за всички артикули с една заявка (вместо N+1)
        // Зарежда се при нужда от опаковки (packType=yes) и/или показване на ЕАН на основната мярка
        $loadPacks = countR($packArr) > 0;
        $loadEan = ($rec->showEan == 'yes');
        $packsByProduct = array();
        if (($loadPacks || $loadEan) && countR($common->sellableProducts)) {
            $allPackQuery = cat_products_Packagings::getQuery();
            $allPackQuery->in('productId', $common->sellableProducts);
            $allPackQuery->where("#state != 'closed'");
            $allPackQuery->show('eanCode,quantity,packagingId,productId');
            while ($allPackRec = $allPackQuery->fetch()) {
                $packsByProduct[$allPackRec->productId][$allPackRec->packagingId] = $allPackRec;
            }
        }

        // За всеки продаваем стандартен артикул
        foreach ($common->pRecs as $productRec) {
            $quantity = 1;
            $obj = $this->preparePriceRow($rec, $productRec, $common->date);

            if ($rec->packType == 'base') {
                if (array_key_exists($productRec->id, $basePackagings)) {
                    $basePack = $basePackagings[$productRec->id];
                    $obj->measureId = $basePack->packagingId;
                    $quantity = $basePack->quantity;
                }
            }

            // Изчислява се цената по избраната политика
            $priceByPolicy = price_ListRules::getPrice($rec->policyId, $productRec->id, null, $common->date);
            $obj->name = cat_Products::getDisplayName($productRec);
            $obj->price = deals_Helper::getDisplayPrice($priceByPolicy, $obj->vat, $common->currencyRate, $rec->vat);

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
                } elseif (empty($priceByPolicy)) {
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

            // Ако има цена, показват се и избраните опаковки с техните цени (от batch-а, без отделна заявка)
            if (!empty($priceByPolicy) && countR($packArr)) {
                if (isset($packsByProduct[$productRec->id])) {
                    foreach ($packsByProduct[$productRec->id] as $packagingId => $srcPackRec) {
                        if (!isset($packLookup[$packagingId])) {
                            continue;
                        }
                        $packRec = clone $srcPackRec;
                        $packRec->price = $packRec->quantity * $priceByPolicy;
                        $packRec->price = deals_Helper::getDisplayPrice($packRec->price, $obj->vat, $common->currencyRate, $rec->vat);
                        $obj->packs[$packRec->packagingId] = $packRec;
                    }
                }

                // Ако ще се скрива мярката и няма опаковки, няма какво да се показва, освен ако артикула не е бил премахнат
                if ($rec->showMeasureId != 'yes' && !countR($obj->packs)) {
                    continue;
                }
            }

            if ($obj->type != 'removed' && empty($priceByPolicy)) {
                continue;
            }

            if ($rec->showEan == 'yes') {
                if (isset($packsByProduct[$obj->productId][$obj->measureId]) && !empty($packsByProduct[$obj->productId][$obj->measureId]->eanCode)) {
                    $obj->eanCode = $packsByProduct[$obj->productId][$obj->measureId]->eanCode;
                }
            }

            $recs[$productRec->id] = $obj;
        }

        $this->orderRecsByGroups($rec, $recs);

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

        Mode::push('noIconImg', true);
        $row->productId = cat_Products::getAutoProductDesc($dRec->productId, null, $display, 'public', $rec->lang, null, false);
        Mode::pop('noIconImg');

        $row->groupName = core_Type::getByName('varchar')->toVerbal($dRec->groupName ?? null);
        $row->code = core_Type::getByName('varchar')->toVerbal($dRec->code);
        $row->measureId = cat_UoM::getShortName($dRec->measureId);

        $decimals = $rec->round ?? self::DEFAULT_ROUND;
        $Double = core_Type::getByName("double(decimals={$decimals})");
        $row->price = $Double->toVerbal($dRec->price);
        $row->price = currency_Currencies::decorate($row->price, $rec->currencyId, true);

        $isPublic = $dRec->isPublic ?? cat_Products::fetchField($dRec->productId, 'isPublic');
        if ($isPublic != 'yes') {
            $row->productId = ht::createHint($row->productId, "Артикулът е нестандартен|*!", 'warning', false);
        }

        // Рендиране на опаковките в таблица
        if (countR($dRec->packs ?? null)) {
            $row->packs = $this->getPackTable($rec, $dRec);
        }

        if (!Mode::isReadOnly()) {
            $row->ROW_ATTR['class'] = 'state-active';
        }

        // Показване на процента промяна
        if (!empty($rec->period)) {
            // В по-старите изчислени версии може да няма 'type'
            $type = $dRec->type ?? null;
            if ($type == 'new') {
                $row->difference = "<span class='price-list-new-item'>" . tr('Нов') . '</span>';
            } elseif ($type == 'removed') {
                $row->difference = "<span class='price-list-removed-item'>" . tr('Премахнат') . '</span>';
            } else {
                $row->difference = core_Type::getByName('percent(decimals=2)')->toVerbal($dRec->difference ?? null);
                if (($dRec->difference ?? 0) > 0) {
                    $row->difference = "<span class='green'>+{$row->difference}</span>";
                } else {
                    $row->difference = "<span class='red'>{$row->difference}</span>";
                }

                if (!empty($dRec->differenceHint) && !Mode::isReadOnly()) {
                    $row->difference = ht::createHint($row->difference, $dRec->differenceHint);
                }
            }
        }

        // Ако има баркод на основната мярка да се показва и той
        if (!empty($dRec->eanCode)) {
            $eanCode = core_Type::getByName('varchar')->toVerbal($dRec->eanCode);
            if (!Mode::isReadOnly() && barcode_Search::haveRightFor('list')) {
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

        if ($rec->displayDetailed == 'yes') {
            Mode::set('saveJS', true);
        }

        return $tpl;
    }


    /**
     * Шаблонът за филтърната информация, показвана в единичния изглед на справката
     *
     * @param stdClass $data
     *
     * @return core_ET
     */
    protected function getSingleFieldTpl($data)
    {
        return new core_ET(tr("|*<fieldset class='detail-info'>
                                        <legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                        <div class='small'>
                                            <div>|Политика|*</span>: <b>[#policyId#]</b></div>
                                            <!--ET_BEGIN variationId--><div>|Вариация|*</span>: <b>[#variationId#]</b></div><!--ET_END variationId-->
                                            <div>|Цени към|*</span>: <b>[#date#]</b></div>
                                            <!--ET_BEGIN period--><div>|Изменени за|*: [#period#] (|от|* [#periodDate#])</div><!--ET_END period-->
                                            <div>|Групи|*: [#productGroups#]</div>
                                            <!--ET_BEGIN notInGroups--><div>|С изключение на|*: [#notInGroups#]</div><!--ET_END notInGroups-->
                                            <div>|Опаковки|*: [#packagings#]</div>
                                            <div>|Валута|*: [#currencyId#]</div>
                                        </div>
                                    </fieldset>"));
    }


    /**
     * Кои полета от вербализирания ред да се заместят в шаблона от getSingleFieldTpl()
     *
     * @return array
     */
    protected function getSingleFieldsToReplace()
    {
        return array_merge(array('periodDate', 'period', 'packagings'), parent::getSingleFieldsToReplace());
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
        if ($rec->packType == 'yes') {
            $row->packagings = (!empty($rec->packagings)) ? core_Type::getByName('keylist(mvc=cat_UoM,select=name)')->toVerbal($rec->packagings) : tr('Всички');
        } elseif ($rec->packType == 'no') {
            $row->packagings = tr('Без опаковки');
        } else {
            $row->packagings = tr('Само основна опаковка');
        }

        if (!empty($rec->period)) {
            $row->period = core_Type::getByName('time')->toVerbal($rec->period);
            $row->periodDate = dt::mysql2verbal(dt::addSecs(-1 * $rec->period, $rec->date, false), 'd.m.Y');
        }
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
        if ($form->isSubmitted() && $form->rec->packType != 'yes') {
            $form->rec->packagings = null;
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
            if (countR($dRec->packs ?? null)) {
                foreach ($dRec->packs as $packRec) {
                    $clone1 = clone $clone;
                    $clone1->packs = array();
                    $clone1->price = $packRec->price;
                    $clone1->eanCode = $packRec->eanCode ?? null;
                    $clone1->measureId = $packRec->packagingId;

                    $exportRecs[] = $clone1;
                }
            }
        }

        return $exportRecs;
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
}
