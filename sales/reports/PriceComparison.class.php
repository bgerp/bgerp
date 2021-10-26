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
 * @title     Продажби » Мониторинг на доставни цени. Сравнение на доставни и продажни цени
 */
class sales_reports_PriceComparison extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'debug';


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

        $fieldset->FLD('group', 'keylist(mvc=cat_Groups,select=name)', 'caption=Артикули->Групи артикули,after=priceListHigh,removeAndRefreshForm,placeholder=Избери,mandatory,silent,single=none');

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
                $katalog = $priceListsRec->id;
            }
            $suggestions[$priceListsRec->id] = $priceListsRec->title;
        }
        $form->setSuggestions('priceListLow', $suggestions);
        $form->setSuggestions('priceListHigh', $suggestions);

        $form->setDefault('priceListLow', array());
        $form->setDefault('policyClassId', array());
        $form->setDefault('priceListHigh', $katalog);


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
        $pQuery->where("#isPublic = 'yes'");
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

            //Изчисляване на разликата в процент
            if ($hiPrice) {
                $diffPercent = $diffPrice / $hiPrice;
            }

            $id = $pRec->productId;

            //САМО ЗА ТЕСТ
            //if (!$hiPrice || !$lowPrice) continue;

            $recs[$id] = (object)array(
                'productId' => $pRec->productId,
                'lowPrice' => $lowPrice,
                'hiPrice' => $hiPrice,
                'diffPrice' => $diffPrice,
                'diffPercent' => $diffPercent,
            );
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

        $fld->FLD('productId', 'varchar', 'caption=Артикул');
        $fld->FLD('lowPrice', 'varchar', 'caption=Цена -> ниска');
        $fld->FLD('hiPrice', 'varchar', 'caption=Цена -> висока');
        $fld->FLD('diffPrice', 'varchar', 'caption=Разлика -> стойност');
        $fld->FLD('diffPercent', 'varchar', 'caption=Разлика -> процент');


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
        $Double = core_Type::getByName('double(decimals=2,smartRound)');
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
        }

        if (isset($dRec->diffPercent)) {
            $row->diffPercent = $Percent->toVerbal($dRec->diffPercent);
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
    }

}
