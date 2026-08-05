<?php


/**
 * Драйвер за спарвка от тип 'Меню' - продаваеми артикули (обичайно храни) със снимка, тегло
 * и цена, подредени в изглед подходящ за отпечатване/споделяне като меню.
 *
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Продажби » Меню
 */
class price_reports_Menu extends price_reports_PriceListProto
{
    /**
     * Максимален брой артикули със снимка, показвани в решетката най-отгоре на менюто
     */
    const MAX_GRID_PHOTOS = 16;


    /**
     * До коя дата да се показва цената едновременно в лева и евро
     */
    const DUAL_PRICE_END_DATE = '2026-08-08';


    /**
     * Плейсхолдър, в който се извежда текста с валутата/ДДС в MASTER_BLOCK-а на шаблона
     */
    protected $vatBeforeRowPlaceholder = 'VAT_STATE';


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
            $title = "Меню \"{$policyName}\"";
        }

        return $title;
    }


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        parent::addFields($fieldset);

        $fieldset->FLD('templateCssClass', 'varchar(16)', 'caption=Допълнително->CSS клас,after=lang,single=internal');
        $fieldset->FLD('showTextIfNoVariation', 'enum(yes=Да,no=Не)', 'caption=Показване на съобщение|*&#44; |ако няма активна вариация->Избор,after=templateCssClass,single=none,silent,removeAndRefreshForm=noVariationText|noVariationTextEn');
        $fieldset->FLD('noVariationText', 'text(rows=3)', 'caption=Показване на съобщение|*&#44; |ако няма активна вариация->Текст (БГ),after=showTextIfNoVariation,single=none');
        $fieldset->FLD('noVariationTextEn', 'text(rows=3)', 'caption=Показване на съобщение|*&#44; |ако няма активна вариация->Текст (EN),after=noVariationText,single=none');
    }


    /**
     * Кои записи ще се показват в менюто
     *
     * @param stdClass $rec
     * @param stdClass $data
     *
     * @return array
     */
    protected function prepareRecs($rec, &$data = null)
    {
        $common = $this->prepareCommonData($rec, $data);

        // Ако е избрано вместо артикулите да се показва текст, когато няма активна вариация
        if ($rec->showTextIfNoVariation == 'yes' && empty($data->variationId)) {
            $data->noVariationTextMode = true;

            return array();
        }

        $recs = array();
        foreach ($common->pRecs as $productRec) {
            $obj = $this->preparePriceRow($rec, $productRec, $common->date);

            // Изчислява се цената по избраната политика
            $priceByPolicy = price_ListRules::getPrice($rec->policyId, $productRec->id, null, $common->date);
            $obj->name = cat_Products::getDisplayName($productRec);
            $obj->price = deals_Helper::getDisplayPrice($priceByPolicy, $obj->vat, $common->currencyRate, $rec->vat);
            price_ListRules::$alreadyReplaced = array();

            if (empty($priceByPolicy)) continue;

            $recs[$productRec->id] = $obj;
        }

        $this->orderRecsByGroups($rec, $recs);

        return $recs;
    }


    /**
     * рендиране на менюто, или текста, ако няма активна вариация
     *
     * @param stdClass $rec
     * @param stdClass $data
     *
     * @return core_ET $tpl
     */
    protected function renderTable($rec, &$data)
    {
        if (!empty($data->noVariationTextMode)) {
            $lang = $this->getRenderLang($rec) ?? core_Lg::getCurrent();
            $text = ($lang == 'en' && !empty($rec->noVariationTextEn)) ? $rec->noVariationTextEn : $rec->noVariationText;

            $tpl = new core_ET("<div class='priceListNoVariationText' style='padding: 24px 20px; font-size: 1.15em; line-height: 1.5; text-align: center;'>[#TEXT#]</div>");
            $tpl->replace(core_Type::getByName('text')->toVerbal($text), 'TEXT');

            return $tpl;
        }

        return parent::renderTable($rec, $data);
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
        $form->setDefault('showTextIfNoVariation', 'no');
        if ($form->rec->showTextIfNoVariation != 'yes') {
            $form->setField('noVariationText', 'input=none');
            $form->setField('noVariationTextEn', 'input=none');
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
        if ($form->isSubmitted() && $form->rec->showTextIfNoVariation == 'yes' && trim($form->rec->noVariationText) === '') {
            $form->setError('noVariationText', 'Въведете текст, който да се показва при липса на активна вариация|*!');
        }
    }


    /**
     * Вербализиране на редовете, които ще се показват в менюто
     *
     * @param stdClass $rec  - записа
     * @param stdClass $dRec - чистия запис
     *
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $row = new stdClass();
        $date = $rec->date ?? dt::today();

        $row->productId = cat_Products::getAutoProductDesc($dRec->productId, null, 'short', 'public', $rec->lang, null, false);
        $previewHandler = cat_Products::getParams($dRec->productId, 'preview');
        if ($previewHandler) {
            $thumb = new thumb_Img($previewHandler, 200, 200, 'fileman', null, null, 3, 'small-no-change', null, true);
            $row->photo = $thumb->createImg(array('class' => 'priceListFoodImage', 'title' => cat_Products::getTitleById($dRec->productId)))->getContent();
            if (!Mode::isReadOnly()) {
                $row->photo = ht::createLink($row->photo, cat_Products::getSingleUrlArray($dRec->productId));
            }
        }

        $weightVerbal = cat_Products::getParams($dRec->productId, 'weight', true);
        if (!empty($weightVerbal)) {
            $suffix = cat_Params::fetch("#sysId = 'weight'")->suffix;
            $row->weight = "{$weightVerbal} {$suffix}";
        }

        $row->groupName = core_Type::getByName('varchar')->toVerbal($dRec->groupName);
        $row->code = core_Type::getByName('varchar')->toVerbal($dRec->code);

        $decimals = $rec->round ?? self::DEFAULT_ROUND;
        $Double = core_Type::getByName("double(decimals={$decimals})");
        $row->price = $Double->toVerbal($dRec->price);
        $row->price = currency_Currencies::decorate($row->price, $rec->currencyId, true);

        $isPublic = $dRec->isPublic ?? cat_Products::fetchField($dRec->productId, 'isPublic');
        if ($isPublic != 'yes') {
            $row->productId = ht::createHint($row->productId, "Артикулът е нестандартен|*!", 'warning', false);
        }

        // Показване на цената едновременно в лева и евро в преходния период
        $showDualPrice = $date <= self::DUAL_PRICE_END_DATE && in_array($rec->currencyId, array('BGN', 'EUR'));
        if ($showDualPrice) {
            if ($rec->currencyId == 'BGN') {
                $priceInBg = $row->price;
                $priceInEuro = currency_CurrencyRates::convertAmount($dRec->price, $date, 'BGN', 'EUR');
                $priceInEuro = currency_Currencies::decorate($Double->toVerbal($priceInEuro), 'EUR', true);

                $row->price = "{$priceInEuro}&nbsp;/&nbsp;{$priceInBg}";
            } else {
                $priceInEuro = $row->price;
                $priceInBg = currency_CurrencyRates::convertAmount($dRec->price, $date, 'EUR', 'BGN');
                $priceInBg = currency_Currencies::decorate($Double->toVerbal($priceInBg), 'BGN', true);

                $row->price = "{$priceInEuro}&nbsp;/&nbsp;{$priceInBg}";
            }
        }

        if (!Mode::isReadOnly()) {
            $row->ROW_ATTR['class'] = 'state-active';
        }

        return $row;
    }


    /**
     * Връща фийлдсета на таблицата (ползва се само за CSV експорт и тагове на редовете)
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
        $fld->FLD('price', "double(decimals={$decimals})", 'caption=Цена');
        if ($export === true) {
            $fld->FLD('currencyId', 'varchar', 'caption=Валута');
        }

        return $fld;
    }


    /**
     * Връща частния шаблон за менюто
     *
     * @param stdClass $rec
     * @return core_ET|null
     */
    protected function getReportLayoutTpl($rec)
    {
        return getTplFromFile("price/tpl/templates/Menu.shtml");
    }


    /**
     * Рендиране на менюто
     *
     * @param stdClass $rec
     * @param stdClass $data
     * @return core_ET|null
     */
    protected function renderCustomLayout($rec, $data)
    {
        $tpl = parent::renderCustomLayout($rec, $data);
        if (!($tpl instanceof core_ET)) return $tpl;

        // Коя дата да показваме в менюто - след 18ч се показва менюто за следващия ден
        $date = dt::now();
        $cH = dt::mysql2verbal($date, 'H');
        if ($cH >= '18') {
            $date = dt::addDays(1, $date);
        }
        $cDay = dt::mysql2verbal($date, 'd');
        $cMonth = mb_strtolower(dt::mysql2verbal($date, 'm'));
        $tpl->append("{$cDay}/{$cMonth}", 'currentDate');

        $counter = 0;
        foreach ($data->rows as $row) {
            if (!empty($row->photo) && !($row instanceof core_ET) && $counter < self::MAX_GRID_PHOTOS) {
                $counter++;
                $block = clone $tpl->getBlock('DETAIL_ROW_WITH_PHOTO');
                $photo = ht::createElement("div", array('class' => 'gridPhoto'), $row->photo, true);
                $block->append($photo);
                $block->removeBlocksAndPlaces();
                $tpl->append($block, 'GRID');
            }
        }

        if (!empty($rec->templateCssClass)) {
            $tpl->append($rec->templateCssClass, 'templateCssClass');
        }

        return $tpl;
    }


    /**
     * Шаблонът за филтърната информация, показвана в единичния изглед на менюто
     *
     * @param stdClass $data
     *
     * @return core_ET
     */
    protected function getSingleFieldTpl($data)
    {
        $fieldTpl = new core_ET("");
        if (!Mode::isReadOnly()) {
            $customTpl = $this->getReportLayoutTpl($data->rec);
            $fieldTpl = $customTpl->getBlock('MASTER_BLOCK');
        }

        return $fieldTpl;
    }


    /**
     * Да се показват ли разширените тагове на редовете на справката
     *
     * @param stdClass $rec
     * @return bool
     */
    protected function showUiextRowLabelsIfExist($rec)
    {
        return false;
    }


    /**
     * Връща редовете, които ще се експортират от менюто
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
        }

        return $exportRecs;
    }
}
