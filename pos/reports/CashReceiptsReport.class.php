<?php


/**
 * Мениджър на отчети за издадени касови бележки
 *
 * @category  bgerp
 * @package   pos
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     POS  » Издадени касови бележки
 */
class pos_reports_CashReceiptsReport extends frame2_driver_TableData
{
    /**
     * Кои полета от листовия изглед да може да се сортират
     *
     * @var int
     */
    protected $sortableListFields;


    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,debug';

    /**
     * По кое поле да се групира
     */
    public $groupByField ;


    /**
     * Брой записи на страница
     *
     * @var int
     */
    protected $listItemsPerPage = 30;

    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     *
     * @var string
     */
    protected $hashField;


    /**
     * Коя комбинация от полета от $data->recs да се следи, ако има промяна в последната версия
     *
     * @var string
     */
    protected $newFieldsToCheck;


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

        $fieldset->FLD('start', 'datetime(smartTime)', 'caption=От,after=title');
        $fieldset->FLD('end', 'datetime(smartTime)', 'caption=До,after=start');

        $fieldset->FLD('pos', 'keylist(mvc=pos_Points,select=name,allowEmpty)', 'caption=ПОС,placeholder=Всички,after=end,single=none');


        //Групиране на резултатите
        $fieldset->FLD('groupBy', 'enum(no=Без групиране,contragentName=Клиент,pointId=ПОС)', 'caption=Групиране по,after=pos,single=none,refreshForm,silent');

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

        $form->setDefault('groupBy', 'no');

    }


    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager $Embedder
     * @param core_Form $form
     * @param stdClass $data
     */
    protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
    {
        $rec = $form->rec;

        if ($form->isSubmitted()) {

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
        $recs = array();

        //Показването да бъде ли ГРУПИРАНО
        if ($rec->groupBy != 'no') {
            $this->groupByField = $rec->groupBy;
        }


        $receiptQuery = pos_Receipts::getQuery();
        $receiptQuery->where("#waitingOn >= '$rec->from'");

        //Филтър по ПОС
        if(!is_null($rec->pos)){

            $receiptQuery->in('pointId', keylist::toArray($rec->pos));

        }


        while ($receiptRec = $receiptQuery->fetch()) {

            $id = $receiptRec->id;

            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object)array(

                    'receiptId' => $receiptRec->id,
                    'pointId' => $receiptRec->pointId,                  //POS
                    'total' => $receiptRec->total,
                    'waitingOn' => $receiptRec->waitingOn,
                    'contragentName' => $receiptRec->contragentName,
                    'contragentObjectId' => $receiptRec->contragentObjectId,
                    'contragentClass' => $receiptRec->contragentClass,
                );
            } else {
                $obj = &$recs[$id];

            }
        }
        if(countR($recs)){
            arr::sortObjects($recs, 'contragentName', 'asc');
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

            $fld->FLD('contragentName', 'varchar', 'caption=Клиент');
            $fld->FLD('pointId', 'datetime', 'caption=ПОС');
            $fld->FLD('datetime', 'datetime', 'caption=Време');
            $fld->FLD('total', 'double(decimals=2)', 'caption=Сума');

        } else {
            $fld->FLD('datetime', 'datetime', 'caption=Време');


        }
        return $fld;
    }


    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec
     *                       - записа
     * @param stdClass $dRec
     *                       - чистия запис
     *
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $Int = cls::get('type_Int');
        $Datetime = cls::get('type_Datetime');
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;

        $row = new stdClass();


        $row->datetime = $Datetime->toVerbal($dRec->waitingOn);


        $row->pointId = pos_Points::getHyperlink($dRec->pointId, true);

        $row->contragentName = $dRec->contragentName;

        $row->total = ht::createLink($dRec->total, array('pos_Receipts', 'single', $dRec->receiptId));

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
        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
                                <fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN users--><div>|Потребители|*: [#users#]</div><!--ET_END users-->
                                       
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));

        if (isset($data->rec->users)) {

            $fieldTpl->append(core_Type::getByName('userList')->toVerbal($data->rec->users), 'users');

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
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;


    }

}
