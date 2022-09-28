<?php


/**
 * Мениджър на отчети за цени на нестандартни артикули
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Продажби » Цени на нестандартни артикули
 */
class sales_reports_PricesOfNonstandardProducts extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, admin';


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
    protected $newFieldsToCheck ;


    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField ;


    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields ;


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('date', 'date(smartTime)', 'caption=Отчетен период->От,after=sellPriceToleranceUp,mandatory,single=none');

    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     *                                      $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;

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
bp('dgdfgfdgfhfghf');

        return $recs;
    }


    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec
     *                         - записа
     * @param bool     $export
     *                         - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');

        $fld->FLD('saleId', 'varchar', 'caption=Сделка');
        if ($export === true) {
            $fld->FLD('folderId', 'key(mvc=doc_Folders,select=title)', 'caption=Папка');
            $fld->FLD('code', 'varchar', 'caption=Код');
        }
        $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,tdClass=productCell leftCol wrap');
        $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');
        $fld->FLD('quantity', 'double(smartRound,decimals=2)', 'caption=Количество,smartCenter');
        $fld->FLD('price', 'double', 'caption=Цени->Продажна,smartCenter');
        $fld->FLD('selfPrice', 'double', 'caption=Цени->Себест-ст,smartCenter');
        $fld->FLD('catPrice', 'double', 'caption=Цени->Политика,smartCenter');
        $fld->FLD('deviationDownSelf', 'percent', 'caption=Отклонение->Под Себест-ст,tdClass=centered');
        $fld->FLD('deviationCatPrice', 'percent', 'caption=Отклонение->Спрямо политика,tdClass=centered');

        return $fld;
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
        $Int = cls::get('type_Int');

        $row = new stdClass();

        $marker = '';

        if ($dRec->catPrice) {
            $row->deviationCatPrice = self::getDeviationCatPrice($dRec);
        }

        if ($dRec->selfPriceDown) {
            $row->deviationDownSelf = self::getDeviationDownSelf($dRec);
        }

        $Sale = doc_Containers::getDocument(sales_Sales::fetch($dRec->saleId)->containerId);
        $handle = $Sale->getHandle();
        $folder = ((sales_Sales::fetch($dRec->saleId)->folderId));
        $folderLink = doc_Folders::recToVerbal(doc_Folders::fetch($folder))->title;
        $singleUrl = $Sale->getUrlWithAccess($Sale->getInstance(), $Sale->that);

        if (isset($dRec->saleId)) {
            $row->saleId = "<div ><span class= 'state-{$Sale->fetchField('state')} document-handler' >" . ht::createLink(
                    "#{$handle}",
                    $singleUrl,
                    false,
                    "ef_icon={$Sale->singleIcon}"
                ) . '</span>' . ' »  ' . "<span class= 'quiet small'>" .
                $folderLink . '</span></div>';
        }

        $row->productId = cat_Products::getShortHyperlink($dRec->productId);

        if (isset($dRec->quantity)) {
            $row->quantity = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->quantity);
        }

        if (isset($dRec->price)) {
            $row->price = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->price);
        }

        if (isset($dRec->measure)) {
            $row->measure = cat_UoM::fetchField($dRec->measure, 'shortName');
        }

        if (isset($dRec->selfPrice)) {
            $row->selfPrice = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->selfPrice);
        }

        if (isset($dRec->catPrice)) {
            $row->catPrice = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->catPrice);
        }

        return $row;
    }


    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver      - драйвер
     * @param stdClass            $res         - резултатен запис
     * @param stdClass            $rec         - запис на справката
     * @param stdClass            $dRec        - запис на реда
     * @param core_BaseClass      $ExportClass - клас за експорт (@see export_ExportTypeIntf)
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {

    }


    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param core_ET           $tpl
     * @param stdClass          $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {


    }

    /**
     * Кои полета да са скрити във вътрешното показване
     *
     * @param core_Master $mvc
     * @param NULL|array  $res
     * @param object      $rec
     * @param object      $row
     */
    public static function on_AfterGetHideArrForLetterHead(frame2_driver_Proto $Driver, embed_Manager $Embedd, &$res, $rec, $row)
    {
        $res = arr::make($res);

        $res['external']['selfPriceTolerance'] = true;
    }
}
