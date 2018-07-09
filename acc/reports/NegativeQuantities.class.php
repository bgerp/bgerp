<?php


/**
 * Мениджър на отчети за артикули с отрицателни количества
 *
 * @category  bgerp
 * @package   acc
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Счетоводство » Артикули с отрицателни количества
 */
class acc_reports_NegativeQuantities extends frame2_driver_TableData
{

    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,acc';

    /**
     * Брой записи на страница
     *
     * @var int
     */
    protected $listItemsPerPage = 30;

    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField = 'articul';

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
        $fieldset->FLD('period', 'key(mvc=acc_Periods,title=title)', 'caption = Период,after=accountId,single=none');
        $fieldset->FLD('accountId', 'key(mvc=acc_Accounts,title=title)', 'caption = Сметка,after=title,single=none');
        $fieldset->FLD('minval', 'double(decimals=2)', 'caption = Минимален праг за отчитане,unit= (количество),
                        placeholder=Без праг,after=period');
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     *            $Driver
     * @param embed_Manager $Embedder
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;

        $form->setDefault('accountId', 81);
    }


    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec
     * @param stdClass $data
     * @return array
     */
    protected function prepareRecs($rec, &$data = NULL)
    {
        $recs = array();
        $articulsForCheck = array();
        $storesArr = array();

        $query = acc_BalanceDetails::getQuery();

        $query->EXT('periodId', 'acc_Balances', 'externalName=periodId,externalKey=balanceId');

        $query->where("#accountId = '$rec->accountId'");

        $query->where("#periodId = '$rec->period'");

        $query->where("#ent1Id IS NOT NULL AND #ent2Id IS NOT NULL");

        while ($detail = $query->fetch()) {
            $storesArr[$detail->ent1Id] = $detail->ent1Id;

            if (($detail->blQuantity < 0) && (abs($detail->blQuantity) > $rec->minval)) {

                $articulsForCheck[$detail->ent2Id] = $detail->ent2Id;
            }

            if ((in_array($detail->ent2Id, $articulsForCheck)) && abs($detail->blQuantity) > $rec->minval) {

                if (! array_key_exists($detail->ent2Id, $recs)) {

                    $recs[$detail->ent2Id] = (object) array(

                        'articulId' => $detail->ent2Id,
                        'articulName' => cat_Products::getTitleById($detail->ent2Id),
                        'uomId' => acc_Items::fetch($detail->ent2Id)->uomId,
                        'storeId' => $detail->ent1Id,
                        'quantity' => $detail->blQuantity
                    );
                } else {
                    $obj = &$recs[$detail->ent2Id];

                    $obj->storeId .= ',' . $detail->ent1Id;

                    $obj->quantity .= ',' . $detail->blQuantity;
                }
            }
        }

        return $recs;
    }


    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec
     *            - записа
     * @param boolean $export
     *            - таблицата за експорт ли е
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = FALSE)
    {
        $fld = cls::get('core_FieldSet');

        if ($export === FALSE) {

            $fld->FLD('articul', 'varchar', 'caption=Артикул');
            $fld->FLD('uomId', 'varchar', 'caption=Мярка');
            $fld->FLD('store', 'varchar', 'caption=Склад');
            $fld->FLD('quantity', 'double(decimals=2)', 'caption=Количество');
        } else {}
        return $fld;
    }


    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec
     *            - записа
     * @param stdClass $dRec
     *            - чистия запис
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $isPlain = Mode::is('text', 'plain');
        $Int = cls::get('type_Int');
        $Date = cls::get('type_Date');
        $resArr = array();

        $row = new stdClass();
        
        $productId = acc_Items::fetch($dRec->articulId)->objectId;
        
        $row->articul = cat_Products::getHyperlink($productId, 'name');
        
        $row->uomId = cat_UoM::getTitleById($dRec->uomId);
        
        $stores = explode(',', $dRec->storeId);
        $quantities = explode(',', $dRec->quantity);

        $resArr = array_combine($stores, $quantities);
        asort($resArr);
        foreach ($resArr as $key => $val) {
            
            $storeId = acc_Items::fetch($key)->objectId;

            $row->store .= store_Stores::getHyperlink($storeId) . "</br>";
            $color = 'green';
            if ($val < 0){
                
                $color = 'red';
            }

            $row->quantity .= "<span class= '{$color}'>" .core_Type::getByName('double(decimals=2)')->toVerbal($val)."</span>" . "</br>";
        }
        return $row;
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
        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
        		                <fieldset class='detail-info'><legend class=red><small><b>|СПРАВКАТА Е В ПРОЦЕС НА РАЗРАБОТКА.ВЪЗМОЖНО Е ДА ИМА НЕТОЧНИ РЕЗУЛТАТИ|*</b></small></legend>
                                <small><div><!--ET_BEGIN period-->|Период|*: [#period#]<!--ET_END period--></div></small>
                                <small><div><!--ET_BEGIN minval-->|Минимален праг за отчитане|*: [#minval#]<!--ET_END minval--></div></small>
                                </fieldset><!--ET_END BLOCK-->"));

        if (isset($data->rec->period)) {
            $fieldTpl->append("<b>" . acc_Periods::getTitleById($data->rec->period) . "</b>", 'period');
        }
        
        if (isset($data->rec->minval)) {
            $fieldTpl->append("<b>" .($data->rec->minval).' единици' . "</b>", 'minval');
        }

        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }


    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver
     * @param stdClass $res
     * @param stdClass $rec
     * @param stdClass $dRec
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {
        // $res->absencesDays = ($dRec->numberOfTripsesDays + $dRec->numberOfSickdays + $dRec->numberOfLeavesDays);

        // $employee = crm_Persons::getContragentData($dRec->personId)->person;

        // $res->employee = $employee;
    }
}



