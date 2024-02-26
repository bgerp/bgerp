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

        $fieldset->FLD('from', 'date', 'caption=От,refreshForm,after=title,single=none');
        $fieldset->FLD('to', 'date', 'caption=До,refreshForm,after=from,single=none');

        $fieldset->FLD('period', 'time(suggestions=1 ден|1 седмица|1 месец|6 месеца|1 година)', 'caption=Цени->Изменени цени,after=vat,single=none');


        $fieldset->FLD('crmGroup', 'key2(mvc=crm_Groups,select=name,allowEmpty)', 'placeholder=Група,caption=Група Клиенти,mandatory,input,silent,after=to,remember,autoFilter,single=none');

        $fieldset->FLD('catGroup', 'key2(mvc=cat_Groups,select=name,allowEmpty)', 'placeholder=Всички групи,caption=Група Артикули,input,silent,after=crmGroup,remember,autoFilter,single=none');

        //Показване на резултатите
        $fieldset->FLD('seeBy', 'enum(contragentName=Клиент,date=Дата, kross=Клиент по дати)', 'caption=Покажи по,after=groupId,single=none,refreshForm,silent');

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
        if ($rec->seeBy == 'kross') {
            $this->groupByField = 'contragentName';
            $this->summaryListFields = '';

        }

        $receiptQuery = pos_ReceiptDetails::getQuery();
        $receiptQuery->EXT('waitingOn', 'pos_Receipts', 'externalName=waitingOn,externalKey=receiptId');
        $receiptQuery->where("#waitingOn IS NOT NULL");
        $receiptQuery->where("#autoDiscount IS NOT NULL");

        if($rec->to < substr(($rec->to), 0, 10) . ' 00:00:01'){
            $end = substr(($rec->to), 0, 10) . ' 23:59:59';
        }else{
            $end = $rec->to;
        }

        $receiptQuery->where(array("#waitingOn>= '[#1#]' AND #waitingOn <= '[#2#]'", $rec->from, $end));

        $allCompanyDiscount = 0;

        while ($receiptDetailRec = $receiptQuery->fetch()) {

            $autoDiscount = $amount =0;

            $receiptRec = pos_Receipts::fetch($receiptDetailRec->receiptId);
            $contragentRec = cls::get($receiptRec->contragentClass)->fetch($receiptRec->contragentObjectId);
            $folderId = $contragentRec->folderId;

            //Филтър по група клиенти
            if (isset($rec->crmGroup)){
                if(!in_array($rec->crmGroup,keylist::toArray($contragentRec->groupList)))continue;
            }

            //Филтър по група артикули
            if(isset($rec->catGroup)){
                if(!in_array($rec->catGroup,keylist::toArray(cat_Products::fetchField($receiptDetailRec->productId,'groups'))))continue;
            }

            //ДДС на артикула
            $prodVat = cat_Products::getVat($receiptDetailRec->productId);

            // Стойността намалена с отстъпките по политика $amount
            $amount = isset($receiptDetailRec->inputDiscount) ? ($receiptDetailRec->amount * (1 - $receiptDetailRec->inputDiscount)) : $receiptDetailRec->amount;

            // Автоматично начислената отстъпка за този артикул в тази бележка $autoDiscount
            $autoDiscount = isset($receiptDetailRec->autoDiscount) ? $amount * $receiptDetailRec->autoDiscount : 0;

            $autoDiscount = $autoDiscount * (1 + $prodVat);

            //Обща стойност на отстъпките на фирмата
            $allCompanyDiscount += $autoDiscount;

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

                    'receiptId' => $receiptDetailRec->receiptId,                       // id на бележката
                    'allAutoDiscountContragent' => $autoDiscount,                      // обща отстъпка по този ключ
                    'waitingOn' => $receiptDetailRec->waitingOn,
                    'allCompanyDiscount' => 0,                                         // обща стойност на отстъпките на тази фирма
                    'contragentName' => $contragentRec->name,
                    'contragentObjectId' => $receiptDetailRec->contragentObjectId,
                    'contragentClass' => $receiptDetailRec->contragentClass,
                    'folderId' => $folderId,
                );
            } else {

                $obj = &$recs[$id];
                $obj->allAutoDiscountContragent += $autoDiscount;

            }

        }
        //Добавям общата стойност на отстъпките
        foreach ($recs as $v) {

            $v->allCompanyDiscount = $allCompanyDiscount;
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
                }
            } else {
                $fld->FLD('date', 'varchar', 'caption=Дата');
            }


            //$fld->FLD('datetime', 'datetime', 'caption=Време');
            $fld->FLD('allAutoDiscountContragent', 'double(decimals=2)', 'caption=Отстъпка,smartCenter');

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


        $d = substr(dt::mysql2verbal($dRec->waitingOn), 0, 8);

        $row->date = $d;
        if ($rec->seeBy == 'kross') {
            $row->date = '<span class="fright">' . $d . '</span>';
        }

        $row->contragentName = $dRec->contragentName;

        $row->allAutoDiscountContragent = $Double->toVerbal($dRec->allAutoDiscountContragent);

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
            $fieldTpl->append($Double->toVerbal($data->rec->allCompanyDiscount), 'allCompanyDiscount');
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

