<?php


/**
 * Мениджър на отчети за начислени общи отстъпки по групи
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     POS  » Начислени автоматични отстъпки
 */
class acc_reports_GeneralDiscountsByGroups extends frame2_driver_TableData
{
    /**
     * Кои полета от листовия изглед да може да се сортират
     *
     * @var int
     */
    protected $sortableListFields;

    /**
     * Кои полета от таблицата в справката да се сумират в обобщаващия ред
     *
     * @var int
     */
    protected $summaryListFields = 'allAutoDiscountContragent';


    /**
     * Как да се казва обобщаващия ред. За да се покаже трябва да е зададено $summaryListFields
     *
     * @var int
     */
    protected $summaryRowCaption = 'ОБЩО ЗА ПЕРИОДА';


    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,manager,debug';

    /**
     * По кое поле да се групира
     */
    public $groupByField;

    /**
     * По-кое поле да се групират данните след групиране, вътре в групата
     */
    protected $subGroupFieldOrder;


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
     * Кои полета са за избор на период
     */
    protected $periodFields = 'from,to';


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {

        $fieldset->FLD('from', 'date', 'caption=От,refreshForm,after=title,single=none');
        $fieldset->FLD('to', 'date', 'caption=До,refreshForm,after=from,placeholder=До днес,single=none');

        // $fieldset->FLD('period', 'time(suggestions=1 ден|1 седмица|1 месец|6 месеца|1 година)', 'caption=Цени->Изменени цени,after=vat,single=none');


        $fieldset->FLD('crmGroup', 'key2(mvc=crm_Groups,select=name,allowEmpty)', 'placeholder=Група,caption=Група Клиенти,mandatory,input,silent,after=to,remember,autoFilter,single=none');

        $fieldset->FLD('catGroup', 'key2(mvc=cat_Groups,select=name,allowEmpty)', 'placeholder=Всички групи,caption=Група Артикули,input,silent,after=crmGroup,remember,autoFilter,single=none');

        //Показване на резултатите
        $fieldset->FLD('seeBy', 'enum(contragentName=Клиент,date=Дата, kross=Клиент по дати)', 'caption=Покажи по,after=groupId,single=none,refreshForm,silent');
        $fieldset->FLD('inDet', 'set(yes = )', 'caption=Подробно,after=seeBy,input=none,single=none');
        $fieldset->FNC('allCompanyDiscount', 'double', 'caption=Общо отстъпка,input=none,single=none');
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

        $form->setDefault('groupBy', 'contragentName');

        if ($rec->seeBy == 'contragentName') {
            $form->setField('inDet', 'input');
        }

        if (is_null($rec->to)) {
            $form->setDefault('to', dt::today());
        }

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
        $recs = $personalReceipts = $arr = array();

        //Показването да бъде ли ГРУПИРАНО
        if ($rec->seeBy == 'kross') {
            $this->groupByField = 'contragentName';
            $this->subGroupFieldOrder = 'waitingOn';
            $this->summaryListFields = '';

        }

        $receiptQuery = pos_ReceiptDetails::getQuery();

        $receiptQuery->EXT('waitingOn', 'pos_Receipts', 'externalName=waitingOn,externalKey=receiptId');
        $receiptQuery->EXT('pointId', 'pos_Receipts', 'externalName=pointId,externalKey=receiptId');
        $receiptQuery->where("#waitingOn IS NOT NULL");
        $receiptQuery->where("#autoDiscount IS NOT NULL");

        if ($rec->to < substr(($rec->to), 0, 10) . ' 00:00:01') {
            $end = substr(($rec->to), 0, 10) . ' 23:59:59';
        } else {
            $end = $rec->to;
        }

        $receiptQuery->where(array("#waitingOn>= '[#1#]' AND #waitingOn <= '[#2#]'", $rec->from, $end));

        $allCompanyDiscount = array();

        while ($receiptDetailRec = $receiptQuery->fetch()) {

            $autoDiscount = $amount = 0;

            $receiptRec = pos_Receipts::fetch($receiptDetailRec->receiptId);

            //Филтър по състояние
            if (in_array($receiptRec->state, array('rejected', 'draft', 'active'))) continue;

            $contragentRec = cls::get($receiptRec->contragentClass)->fetch($receiptRec->contragentObjectId);
            $folderId = $contragentRec->folderId;

            //Филтър по група клиенти
            if (isset($rec->crmGroup)) {
                if (!in_array($rec->crmGroup, keylist::toArray($contragentRec->groupList))) continue;
            }

            //Филтър по група артикули
            if (isset($rec->catGroup)) {
                if (!in_array($rec->catGroup, keylist::toArray(cat_Products::fetchField($receiptDetailRec->productId, 'groups')))) continue;
            }

            $vagExeptionId = pos_Points::fetch($receiptDetailRec->pointId)->vatExceptionId;

            //ДДС на артикула
            $prodVat = cat_Products::getVat($receiptDetailRec->productId, $receiptDetailRec->waitingOn, $vagExeptionId);

            // Стойността намалена с отстъпките по политика $amount
            $amount = isset($receiptDetailRec->inputDiscount) ? ($receiptDetailRec->amount * (1 - $receiptDetailRec->inputDiscount)) : $receiptDetailRec->amount;

            // Автоматично начислената отстъпка за този артикул в тази бележка $autoDiscount
            $autoDiscount = isset($receiptDetailRec->autoDiscount) ? $amount * $receiptDetailRec->autoDiscount : 0;

            $autoDiscount = $autoDiscount * (1 + $prodVat);

            //Обща стойност на отстъпките на фирмата
            $allCompanyDiscount[$folderId] += $autoDiscount;

            //Ключ
            if ($rec->seeBy == 'date') {

                $id = substr(dt::mysql2verbal($receiptDetailRec->waitingOn), 0, 8);

            } elseif ($rec->seeBy == 'contragentName') {
                $id = $folderId;
            } elseif ($rec->seeBy == 'kross') {
                $id = $folderId . '|' . substr(dt::mysql2verbal($receiptDetailRec->waitingOn), 0, 8);
            }


            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object)array(

                    // 'receiptId' => $receiptDetailRec->receiptId,                       // id на бележката
                    'allAutoDiscountContragent' => $autoDiscount,                      // обща отстъпка по този ключ
                    'waitingOn' => $receiptDetailRec->waitingOn,                       // дата
                    'allCompanyDiscount' => 0,                                         // обща стойност на отстъпките на тази фирма
                    'contragentName' => $contragentRec->name,
                    'contragentObjectId' => $receiptDetailRec->contragentObjectId,
                    'contragentClass' => $receiptDetailRec->contragentClass,
                    'folderId' => $folderId,
                    'personalReceipts' => array(0 => array('receiptId' => $receiptDetailRec->receiptId,
                        'allAutoDiscountContragent' => $autoDiscount,
                        'waitingOn' => $receiptDetailRec->waitingOn),
                    ));
            } else {

                $obj = &$recs[$id];
                $obj->allAutoDiscountContragent += $autoDiscount;
                array_push($obj->personalReceipts, array('receiptId' => $receiptDetailRec->receiptId,
                    'allAutoDiscountContragent' => $autoDiscount,
                    'waitingOn' => $receiptDetailRec->waitingOn));

            }

        }
        //Добавям общата стойност на отстъпките
        foreach ($recs as $v) {

            $v->allCompanyDiscount = $allCompanyDiscount[$v->folderId];

            $arr = array();
            foreach ($v->personalReceipts as $r) {

                if (!array_key_exists($r['receiptId'], $arr)) {

                    $arr[$r['receiptId']] = (object)array(

                        'receiptId' => $r['receiptId'],                       // id на бележката
                        'waitingOn' => $r['waitingOn'],
                        'allAutoDiscountContragent' => $r['allAutoDiscountContragent'],
                    );
                } else {
                    $obj = &$arr[$r['receiptId']];
                    $obj->allAutoDiscountContragent += $r['allAutoDiscountContragent'];

                }
            }
            $v->personalReceipts = $arr;
            unset($arr);
        }

        $rec->allCompanyDiscount = $allCompanyDiscount;

        if ((countR($recs)) && (($rec->seeBy == 'kross') || ($rec->seeBy == 'contragentName'))) {
            arr::sortObjects($recs, 'contragentName', 'asc');
        }
        if ((countR($recs) && ($rec->seeBy == 'date'))) {
            arr::sortObjects($recs, 'waitingOn', 'asc');
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

            if ($rec->seeBy != 'kross') {
                if ($rec->seeBy == 'date') {
                    $fld->FLD('date', 'varchar', 'caption=Дата');
                } elseif ($rec->seeBy == 'contragentName') {
                    $fld->FLD('contragentName', 'varchar', 'caption=Клиент');
                    if ($rec->seeBy) {
                        if ($rec->inDet == 'yes') {
                            $fld->FLD('receipts', 'varchar', 'caption=Бележки->номер >> дата >> час');
                        }

                    }

                }
            } else {
                $fld->FLD('date', 'varchar', 'caption=Дата');
            }

            $fld->FLD('allAutoDiscountContragent', 'double(decimals=2)', 'caption=Отстъпка');

        } else {

            if ($rec->seeBy != 'kross') {
                if ($rec->seeBy == 'date') {
                    $fld->FLD('date', 'varchar', 'caption=Дата');
                    $fld->FLD('allAutoDiscountContragent', 'double(decimals=2)', 'caption=Обща отстъпка');
                } elseif ($rec->seeBy == 'contragentName') {
                    $fld->FLD('contragentName', 'varchar', 'caption=Клиент');
                    if ($rec->seeBy) {
                        if ($rec->inDet == 'yes') {
                            $fld->FLD('receiptId', 'varchar', 'caption=Бележка->номер');
                            $fld->FLD('waitingOn', 'varchar', 'caption=Бележка->дата >> час');
                            $fld->FLD('autoDiscount', 'double(decimals=2)', 'caption=Бележка->сума');
                        }
                        $fld->FLD('allAutoDiscountContragent', 'double(decimals=2)', 'caption=Обща отстъпка');
                    }

                }
            } else {
                $fld->FLD('contragentName', 'varchar', 'caption=Клиент');
                $fld->FLD('date', 'varchar', 'caption=Дата');
                $fld->FLD('discountDate', 'double(decimals=2)', 'caption=Отстъпка');
                $fld->FLD('allCompanyDiscount', 'double(decimals=2)', 'caption=Обща');

            }

            // $fld->FLD('allAutoDiscountContragent', 'double(decimals=2)', 'caption=Обща отстъпка');


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


        $d = substr(dt::mysql2verbal($dRec->waitingOn), 0, 8);

        $row->date = $d;
        if ($rec->seeBy == 'kross') {
            $row->date = '<span class="fright">' . $d . '</span>';
        }

        $row->contragentName = $dRec->contragentName;
        if ($rec->seeBy == 'kross') {
            $row->contragentName .= '<span class="fright">' . $Double->toVerbal($dRec->allCompanyDiscount) . '</span>';
        }

        $row->receipts = '</br>';
        foreach ($dRec->personalReceipts as $val) {
            $counter = 3;
            foreach ($val as $v) {

                if ($counter == 3) {
                    $prv = ht::createLink($v, array('pos_Receipts', 'single', $v));

                } elseif ($counter == 2) {
                    $prv = $Datetime->toVerbal($v);
                }
                $row->receipts .= '<span class="small">' . $prv . '</span>';
                if ($counter == 3) {
                    $row->receipts .= ', ';
                }

                $counter--;
                unset($prv);
            }
            $row->receipts .= '</br>';
        }
        if ($rec->inDet == 'yes' && $rec->seeBy == 'contragentName') {
            $row->allAutoDiscountContragent = '<b>' . $Double->toVerbal($dRec->allAutoDiscountContragent) . '</b>' . '</br>';
        } else {
            $row->allAutoDiscountContragent = $Double->toVerbal($dRec->allAutoDiscountContragent) . '</br>';
        }
        if ($rec->inDet == 'yes' && $rec->seeBy == 'contragentName') {
            foreach ($dRec->personalReceipts as $val) {
                $row->allAutoDiscountContragent .= '<span class="small">' . $Double->toVerbal($val->allAutoDiscountContragent) . '</span>' . '</br>';

            }
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
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;

        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
                                <fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN from--><div>|От|*: [#from#]</div><!--ET_END from-->
                                        <!--ET_BEGIN to--><div>|До|*: [#to#]</div><!--ET_END to-->
                                        <!--ET_BEGIN crmGroup--><div>|Група|*: [#crmGroup#]</div><!--ET_END crmGroup-->
                                        <!--ET_BEGIN catGroup--><div>|Група артикули|*: [#catGroup#]</div><!--ET_END catGroup-->
                                        <!--ET_BEGIN allCompanyDiscount--><div>|Общо авт. отстъпки|*: [#allCompanyDiscount#] лв.</div><!--ET_END allCompanyDiscount-->     
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));


        if (isset($data->rec->from)) {
            $fieldTpl->append($Date->toVerbal($data->rec->from), 'from');
        }

        if (isset($data->rec->to)) {
            $fieldTpl->append($Date->toVerbal($data->rec->to), 'to');
        }


        if (isset($data->rec->catGroup)) {
            $fieldTpl->append(cat_Groups::getTitleById($data->rec->catGroup), 'catGroup');
        }

        if (isset($data->rec->crmGroup)) {
            $fieldTpl->append(crm_Groups::getTitleById($data->rec->crmGroup), 'crmGroup');
        }

        if (isset($data->rec->allCompanyDiscount)) {

            $fieldTpl->append($Double->toVerbal(array_sum($data->rec->allCompanyDiscount)), 'allCompanyDiscount');
        }


        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }

    /**
     * Връща редовете на CSV файл-а
     *
     * @param stdClass $rec - запис
     * @param core_BaseClass $ExportClass - клас за експорт (@see export_ExportTypeIntf)
     *
     * @return array $recs                - записите за експорт
     */
    public function getExportRecs($rec, $ExportClass)
    {

        expect(cls::haveInterface('export_ExportTypeIntf', $ExportClass));
        $recsToExport = $this->getRecsForExport($rec, $ExportClass);
        $recs = array();
        if (is_array($recsToExport)) {
            foreach ($recsToExport as $dRec) {
                if (!is_null($rec->inDet) && ($rec->seeBy == 'contragentName')) {
                    $mark = 0;
                    $dRec->personalReceipts[0] = (object)array(

                        'receiptId' => '',
                        'waitingOn' => '',
                        'allAutoDiscountContragent' => '',
                    );
                    ksort($dRec->personalReceipts);
                    foreach ($dRec->personalReceipts as $pKey => $pReceipt) {

                        $dCloneRec = clone $dRec;
                        if ($pKey != 0) {
                            $dCloneRec->contragentName = '';
                            $dCloneRec->allAutoDiscountContragent = '';
                        }

                        $dCloneRec->receiptId = $pReceipt->receiptId;

                        $dCloneRec->waitingOn = $pReceipt->waitingOn;

                        $dCloneRec->autoDiscount = $pReceipt->allAutoDiscountContragent;

                        unset ($dCloneRec->personalReceipts);

                        $recs[] = $this->getExportRec($rec, $dCloneRec, $ExportClass);

                    }
                } elseif ($rec->seeBy == 'kross') {

                    foreach ($recsToExport as $dRec) {

                        $dCloneRec = clone $dRec;

                        $d = substr(dt::mysql2verbal($dRec->waitingOn), 0, 8);
                        $dCloneRec->date = $d;

                        $dCloneRec->discountDate = $dCloneRec->allAutoDiscountContragent;

                        $recs[] = $this->getExportRec($rec, $dCloneRec, $ExportClass);

                    }

                    return $recs;

                } elseif ($rec->seeBy == 'date') {
                    $dCloneRec = clone $dRec;
                    $d = substr(dt::mysql2verbal($dRec->waitingOn), 0, 8);
                    $dCloneRec->date = $d;
                    $recs[] = $this->getExportRec($rec, $dCloneRec, $ExportClass);

                } else {
                    $recs = $recsToExport;
                }
            }
        }

        return $recs;
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
        $Datetime = cls::get('type_Datetime');

        $d = substr(dt::mysql2verbal($dRec->waitingOn), 0, 8);

        $res->date = $d;
        $res->contragentName = $dRec->contragentName;
        $res->receiptId = $dRec->receiptId;
        $res->waitingOn = $dRec->waitingOn;
        $res->autoDiscount = $dRec->autoDiscount;
        $res->allAutoDiscountContragent = $dRec->allAutoDiscountContragent;
        $res->discountDate = $dRec->allAutoDiscountContragent;

    }

}

