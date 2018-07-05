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
        // $fieldset->FLD('from', 'date', 'caption=От,after=title,single=none,mandatory');
        // $fieldset->FLD('to', 'date', 'caption=До,after=from,single=none,mandatory');
        // $fieldset->FLD('ent1Id', 'key(mvc=acc_Items,select=titleLink)', 'caption=Сметка->перо,after=title');
        $fieldset->FLD('accountId', 'key(mvc=acc_Accounts,title=title)', 'caption=Сметка->име,single=none');
        $fieldset->FLD('period', 'key(mvc=acc_Periods,title=title)', 'caption=Период,single=none');
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
        
        $query = acc_BalanceDetails::getQuery();
        
        $query->EXT('periodId', 'acc_Balances', 'externalName=periodId,externalKey=balanceId');
        
        $query->where("#accountId = '$rec->accountId'");
        
        $query->where("#periodId = '$rec->period'");
        
        while ($detail = $query->fetch()) {
            
            if (!is_null($detail->ent2Id)) {
                $articul = acc_Items::fetch($detail->ent2Id);
            }
            
            if (!is_null($detail->ent1Id)) {
                $store = acc_Items::fetch($detail->ent1Id);
            }
            
            if ($detail->blQuantity >= 0)continue;
            
            if (!array_key_exists($articul->id, $recs)) {
                
                $recs[$articul->id] = (object) array(
                    
                    'artikulId' => $articul->id,
                    'artikulName' => $articul->title,
                    'storelId' => $store->id,
                    'storeName' => $store->title,
                    'period' => $rec->period,
                    'quantity' => $detail->blQuantity
                
                );
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
            
            $fld->FLD('artikulName', 'varchar', 'caption=Артикул');
            $fld->FLD('storeName', 'varchar', 'caption=Склад,tdClass=centered');
            $fld->FLD('quantity', 'varchar', 'caption=Количество,tdClass=centered');
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
        $row = new stdClass();
        
        $row->artikulName = $dRec->artikulName;
        
        $row->storeName = $dRec->storeName;
        
        $row->quantity = $dRec->quantity;
        
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
                                <small><div><!--ET_BEGIN to-->|До|*: [#to#]<!--ET_END to--></div></small>
                                <small><div><!--ET_BEGIN employee-->|Служители|*: [#employee#]<!--ET_END employee--></div></small>
                                </fieldset><!--ET_END BLOCK-->"));
        
        if (isset($data->rec->period)) {
            $fieldTpl->append("<b>" . acc_Periods::getTitleById($data->rec->period) . "</b>", 'period');
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
        $res->absencesDays = ($dRec->numberOfTripsesDays + $dRec->numberOfSickdays + $dRec->numberOfLeavesDays);
        
        $employee = crm_Persons::getContragentData($dRec->personId)->person;
        
        $res->employee = $employee;
    }
}



