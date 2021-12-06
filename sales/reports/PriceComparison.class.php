<?php


/**
 * Мениджър на отчети за сравнение на цените
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Продажби » Сравнение на цени (политики/себест-сти)
 */
class sales_reports_PriceComparison extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,debug,priceMaster';

    /**
     * Кои полета от листовия изглед да може да се сортират
     *
     * @var int
     */
    protected $sortableListFields = 'diffPercent,diffPrice';


    /**
     * Кои полета от таблицата в справката да се сумират в обобщаващия ред
     *
     * @var int
     */
    protected $summaryListFields;


    /**
     * Как да се казва обобщаващия ред. За да се покаже трябва да е зададено $summaryListFields
     *
     * @var int
     */
    protected $summaryRowCaption = 'ОБЩО';


    /**
     * Брой записи на страница
     *
     * @var int
     */
    protected $listItemsPerPage = 30;


    /**
     * Коя комбинация от полета от $data->recs да се следи, ако има промяна в последната версия
     *
     * @var string
     */
    protected $newFieldsToCheck;


    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField;


    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields;


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {

        $fieldset->FLD('priceListLow', 'key(mvc=price_Lists,allowEmpty,select=title)', 'caption=Ниска->Ценова политика,after=title,removeAndRefreshForm,placeholder=Избери,silent,single=none');
        $fieldset->FLD('policyClassId', 'class(interface=price_CostPolicyIntf,allowEmpty,select=title)', 'caption=Ниска->Себестойност,placeholder=Избери,removeAndRefreshForm,silent,after=priceListLow');


        $fieldset->FLD('priceListHigh', 'key(mvc=price_Lists,select=title)', 'caption=Висока->Ценова политика,after=priceListLow,removeAndRefreshForm,mandatory,silent,single=none');

        $fieldset->FLD('groups', 'keylist(mvc=cat_Groups,select=name)', 'caption=Артикули->Групи артикули,after=priceListHigh,placeholder=Избери,silent,single=none');

        $fieldset->FLD('orderBy', 'enum(name=Име,code=Код,diffPrice=Разлика ст.,diffPercent=Разлика %)', 'caption=Сортиране по,maxRadio=4,columns=4,after=groups');

        $fieldset->FLD('typePercent', 'enum(none=Без,up=Надценка,down=Отстъпка)', 'caption=Тип отчитане в %,maxRadio=3,columns=3,after=orderBy');
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     *                                      $Driver
     * @param embed_Manager $Embedder
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;

        $form->setDefault('orderBy', 'diffPrice');
        $form->setDefault('typePercent', 'none');

        if ($rec->priceListLow) {
            $form->setReadOnly('policyClassId');
        }
        if ($rec->policyClassId) {
            $form->setReadOnly('priceListLow');
        }

        //Да се заредят само публични политики
        $priceListsQuery = price_Lists::getQuery();
        $priceListsQuery->where("#public = 'yes' AND #state = 'active'");
        $suggestions = array();
        while ($priceListsRec = $priceListsQuery->fetch()) {

            if ($priceListsRec->title == 'Каталог') {
                $katalogId = $priceListsRec->id;
            }
            $suggestions[$priceListsRec->id] = $priceListsRec->title;
        }
        $form->setSuggestions('priceListLow', $suggestions);
        $form->setSuggestions('priceListHigh', $suggestions);

        $form->setDefault('priceListLow', array());
        $form->setDefault('policyClassId', array());
        $form->setDefault('priceListHigh', $katalogId);


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

        $recs = array();

        $pQuery = store_Products::getQuery();
        $pQuery->where("#isPublic = 'yes'");

        $pQuery->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');
        $pQuery->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');
        $pQuery->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');
        $pQuery->EXT('name', 'cat_Products', 'externalName=name,externalKey=productId');

        $pQuery->where("#isPublic = 'yes'");

        //Филтър по групи артикули
        if ($rec->groups) {
            $pQuery->likeKeylist('groups', $rec->groups);
        }


        while ($pRec = $pQuery->fetch()) {
            $diffPrice = $lowPrice = $hiPrice = $diffPercent = 0;

            //Намиране на ниската цена
            //Ако е избрана някаква себестойност
            if ($rec->policyClassId) {
                $lowPrice = price_ProductCosts::getPrice($pRec->productId, core_Classes::fetch($rec->policyClassId)->name);
            }
            if ($rec->priceListLow) {
                $lowPrice = price_ListRules::getPrice($rec->priceListLow, $pRec->productId, null, dt::today());

            }

            //Намиране на високата цена
            $hiPrice = price_ListRules::getPrice($rec->priceListHigh, $pRec->productId, null, dt::today());

            //Изчисляване на разликата в стойност
            $diffPrice = $hiPrice - $lowPrice;
            if (!$hiPrice && !$lowPrice) {
                $diffPrice = '';
            }

            //Изчисляване на разликата в процент

            $d = ($rec->typePercent == 'up') ? $lowPrice : $hiPrice;

            if ($hiPrice && $lowPrice) {
                $diffPercent = $diffPrice / $d;
            } else {
                $diffPercent = '';
            }

            $id = $pRec->productId;

            //САМО ЗА ТЕСТ
            //if (!$hiPrice || !$lowPrice) continue;

            $recs[$id] = (object)array(
                'productId' => $pRec->productId,
                'code' => $pRec->code,
                'name' => $pRec->name,
                'lowPrice' => $lowPrice,
                'hiPrice' => $hiPrice,
                'diffPrice' => $diffPrice,
                'diffPercent' => $diffPercent,
            );
        }

        //Подредба на резултатите
        if (!is_null($recs)) {
            $typeOrder = ($rec->orderBy == 'name' || $rec->orderBy == 'code') ? 'stri' : 'native';

            $order = in_array($rec->orderBy, array('name', 'code')) ? 'ASC' : 'DESC';

            $orderBy = $rec->orderBy;

            arr::sortObjects($recs, $orderBy, $order, $typeOrder);
        }

        return $recs;
    }


    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec
     *                         - записа
     * @param bool $export
     *                         - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');

        if ($export === false) {

            $fld->FLD('productId', 'varchar', 'caption=Артикул');
            $fld->FLD('lowPrice', 'double(decimals=2,smartRound)', 'caption=Цена -> Ниска');
            $fld->FLD('hiPrice', 'double(decimals=2,smartRound)', 'caption=Цена -> Висока');
            $fld->FLD('diffPrice', 'double(decimals=2,smartRound)', 'caption=Разлика -> Стойност');
            if ($rec->typePercent != 'none') {
                $fld->FLD('diffPercent', 'double(decimals=2,smartRound)', 'caption=Разлика -> Процент');
            }
        }else{

            $fld->FLD('code', 'varchar', 'caption=Код');
            $fld->FLD('productId', 'varchar', 'caption=Артикул');
            $fld->FLD('lowPrice', 'double(decimals=2,smartRound)', 'caption=Цена -> Ниска');
            $fld->FLD('hiPrice', 'double(decimals=2,smartRound)', 'caption=Цена -> Висока');
            $fld->FLD('diffPrice', 'double(decimals=2,smartRound)', 'caption=Разлика -> Стойност');
            if ($rec->typePercent != 'none') {
                $fld->FLD('diffPercent', 'double(decimals=2,smartRound)', 'caption=Разлика -> Процент');
            }

        }

        return $fld;
    }


    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec - записа
     * @param stdClass $dRec - чистия запис
     *
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $Int = cls::get('type_Int');

        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;

        $Date = cls::get('type_Date');
        $Percent = cls::get('type_Percent');

        $row = new stdClass();
        if (isset($dRec->productId)) {
            $row->productId = cat_Products::getHyperlink($dRec->productId);
        }
        if (isset($dRec->lowPrice)) {
            $row->lowPrice = $Double->toVerbal($dRec->lowPrice);
        }

        if (isset($dRec->hiPrice)) {
            $row->hiPrice = $Double->toVerbal($dRec->hiPrice);
        }

        if (isset($dRec->diffPrice)) {
            $row->diffPrice = $Double->toVerbal($dRec->diffPrice);
            $row->diffPrice = ht::styleIfNegative($row->diffPrice, $dRec->diffPrice);

        }

        if (isset($dRec->diffPercent)) {

            $row->diffPercent = $Percent->toVerbal($dRec->diffPercent);
            $row->diffPercent = ht::styleIfNegative($row->diffPercent, $dRec->diffPercent);
        }

        return $row;
    }


    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver - драйвер
     * @param stdClass $res - резултатен запис
     * @param stdClass $rec - запис на справката
     * @param stdClass $dRec - запис на реда
     * @param core_BaseClass $ExportClass - клас за експорт (@see export_ExportTypeIntf)
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {

        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;



        $res->productId = cat_Products::fetch($dRec->productId)->name;
        $res->code = $dRec->code;

    }


    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager $Embedder
     * @param core_ET $tpl
     * @param stdClass $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        $Date = cls::get('type_Date');
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;

        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN priceListLow--><div>|Ниска цена по|*: [#priceListLow#]</div><!--ET_END priceListLow-->
                                        <!--ET_BEGIN policyClassId--><div>|Ниска цена по|*: [#policyClassId#]</div><!--ET_END policyClassId-->
                                        <!--ET_BEGIN priceListHigh--><div>|Висока цена по|*: [#priceListHigh#]</div><!--ET_END priceListHigh-->
                                        <!--ET_BEGIN groups--><div>|Групи продукти|*: [#groups#]</div><!--ET_END groups-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));
        if (isset($data->rec->priceListLow)) {
            $priceListLowName = price_Lists::fetch($data->rec->priceListLow)->title;
            $fieldTpl->append('<b>' . $priceListLowName . '</b>', 'priceListLow');
        }

        if (isset($data->rec->policyClassId)) {
            $fieldTpl->append('<b>' . core_Classes::fetch($data->rec->policyClassId)->title . '</b>', 'policyClassId');
        }

        if ((isset($data->rec->priceListHigh))) {
            $priceListHighName = price_Lists::fetch($data->rec->priceListHigh)->title;
            $fieldTpl->append('<b>' . $priceListHighName . '</b>', 'priceListHigh');
        }

        $marker = 0;
        if (isset($data->rec->groups)) {
            foreach (type_Keylist::toArray($data->rec->groups) as $group) {
                $marker++;

                $groupVerb .= (cat_Groups::getTitleById($group));

                if ((countR((type_Keylist::toArray($data->rec->groups))) - $marker) != 0) {
                    $groupVerb .= ', ';
                }
            }

            $fieldTpl->append('<b>' . $groupVerb . '</b>', 'groups');
        } else {
            $fieldTpl->append('<b>' . 'Всички' . '</b>', 'groups');
        }

        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }

}
