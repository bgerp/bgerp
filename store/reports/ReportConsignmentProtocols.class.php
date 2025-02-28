<?php


/**
 * Мениджър на отчети протоколи за отговорно пазене
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Склад » Oтговорно пазене
 */
class store_reports_ReportConsignmentProtocols extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, cat, store, acc';


    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     *
     * @var string
     */
    protected $hashField;


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
    protected $summaryListFields;


    /**
     * Как да се казва обобщаващия ред. За да се покаже трябва да е зададено $summaryListFields
     *
     * @var int
     */
    protected $summaryRowCaption = 'ОБЩО';


    /**
     * Коя комбинация от полета от $data->recs да се следи, ако има промяна в последната версия
     *
     * @var string
     */
    protected $newFieldsToCheck;


    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField = 'contragent';


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
        $fieldset->FLD('from', 'date', 'caption=От,after=compare,single=none,mandatory');
        $fieldset->FLD('to', 'date', 'caption=До,after=from,single=none,mandatory');

        $fieldset->FLD('contragent', 'keylist(mvc=doc_Folders,select=title,allowEmpty)', 'caption=Контрагенти->Контрагент,single=none,after=orderBy');

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
        if ($form->isSubmitted()) {

            if (isset($form->rec->workingPdogresOn) && $form->rec->workingPdogresOn == 'included' && ($form->rec->type == 'long')) {
                $form->setError('type', 'Незавършено производство може да се включи само при избран вариант "Кратка".');
            }

        }
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager $Embedder
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;

        $consignmentQuery = store_ConsignmentProtocols::getQuery();

        $consignmentQuery->EXT('folderTitle', 'doc_Folders', 'externalName=title,externalKey=folderId');

        $consignmentQuery->groupBy('folderId');

        $consignmentQuery->show('folderId, contragentId, folderTitle');

        while ($contragent = $consignmentQuery->fetch()) {
            if (!is_null($contragent->contragentId)) {
                $suggestions[$contragent->folderId] = $contragent->folderTitle;
            }
        }

        asort($suggestions);

        $form->setSuggestions('contragent', $suggestions);
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

        //  $date = (is_null($rec->date)) ? dt::today() : $rec->date;

        $recs = array();

        $Balance = new acc_ActiveShortBalance(array('from' => $rec->from, 'to' => $rec->to, 'accs' => '3231', 'cacheBalance' => false, 'keepUnique' => true));
        //$bRecs = $Balance->getBalance('3231');
        $balHistory = $Balance->getBalanceHystory('3231', $from = $rec->from, $to = $rec->to, $item1 = null, $item2 = null, $item3 = null, $groupByDocument = false, $strict = true);

        $documentsDebitQuantity1 = $documentsCreditQuantity1 = array();

        foreach ($balHistory['history'] as $jRec) {

            $debitQuantity = $creditQuantity = 0;

            $pRec = cls::get($jRec['docType'])->fetch($jRec['docId']);

            $contragentFolder = cls::get($pRec->contragentClassId)::fetch($pRec->contragentId)->folderId;

            if ($rec->contragent) {
                if (!in_array($contragentFolder, keylist::toArray($rec->contragent))) continue;
            }

            $item = acc_Items::fetch($jRec['debitItem2']);

            $prodRec = cls::get($item->classId)->fetch($item->objectId);

            if ($jRec['debitQuantity']) {
                $debitQuantity = $jRec['debitQuantity'];
                $documentsDebitQuantity1[$jRec['docId'] . '|' . $prodRec->id] = (object)array('docType' => $jRec['docType'], 'docId' => $jRec['docId'], 'productId' => $prodRec->id, 'contragent' => $pRec->folderId);
            }
            if ($jRec['creditQuantity']) {
                $creditQuantity = $jRec['creditQuantity'];
                $documentsCreditQuantity1[$jRec['docId'] . '|' . $prodRec->id] = (object)array('docType' => $jRec['docType'], 'docId' => $jRec['docId'], 'productId' => $prodRec->id, 'contragent' => $pRec->folderId);

            }

            $id = $prodRec->id . '|' . $pRec->folderId;
            // добавяме в масива
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object)array(

                    'contragent' => $pRec->folderId,
                    'docClsId' => $jRec['docType'],
                    'docId' => $jRec['docId'],
                    'productId' => $prodRec->id,
                    'debitQuantity' => $debitQuantity,
                    'creditQuantity' => $creditQuantity,
                    'documentsDebitQuantity' => array(),
                    'documentsCreditQuantity' => array(),
                    'date' => $pRec->valior,
                    'storeId' => $pRec->storeId,

                );
            } else {
                $obj = &$recs[$id];
                $obj->debitQuantity += $debitQuantity;
                $obj->creditQuantity += $creditQuantity;
            }

        }
        foreach ($recs as $rec) {

            $rec->documentsDebitQuantity = $documentsDebitQuantity1;
            $rec->documentsCreditQuantity = $documentsCreditQuantity1;

        }

        return $recs;
    }


    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec - записа
     * @param bool $export - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');

        if ($export === false) {
            $fld->FLD('contragent', 'key(mvc=doc_Folders,select=name)', 'caption=Контрагент');
            $fld->FLD('productId', 'varchar', 'caption=Артикул');
            //$fld->FLD('date', 'date', 'caption=Дата');
            $fld->FLD('quantity', 'double(decimals=2)', 'caption=К-во,smartCenter,tdClass=boldText');
            $fld->FLD('debitQuantity', 'double(decimals=2)', 'caption=Дадено->К-во,smartCenter');
            $fld->FLD('debitDocuments', 'varchar', 'caption=Дадено->Документи,tdClass=leftCol');
            $fld->FLD('creditQuantity', 'double(decimals=2)', 'caption=Прието->К-во,smartCenter');
            $fld->FLD('creditDocuments', 'varchar', 'caption=Прието->Документи');

            //    $fld->FLD('protocol', 'varchar', 'caption=Протокол');
            //   $fld->FLD('inOut', 'varchar', 'caption=Тип');
            // $fld->FLD('storeId', 'varchar', 'caption=Склад');
            // $fld->FLD('newProtocol', 'varchar', 'caption=Нов ПОП');


        } else {

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
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        $Date = cls::get('type_Date');

        $row = new stdClass();

        $row->contragent = "<span style='line-height: 140%'>" . doc_Folders::getTitleById($dRec->contragent) . "</span>";

        $cUrl = array('store_reports_ReportConsignmentProtocols', 'newProtocol', 'contragentFolder' => $dRec->contragent, 'storeId' => $dRec->storeId, 'ret_url' => true);

        $row->contragent .= "<span class='fright smallBtnHolder'>" . ht::createBtn('Нов ПОП', $cUrl, false, false, "ef_icon = img/16/add.png") . "</span>";

        $row->productId = cat_Products::getHyperlink($dRec->productId);

        $row->quantity = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->debitQuantity - $dRec->creditQuantity);

        $row->debitQuantity = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->debitQuantity);

        $row->creditQuantity = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->creditQuantity);

        $row->debitDocuments = '';
        if (!empty($dRec->documentsDebitQuantity)) {

            foreach ($dRec->documentsDebitQuantity as $v) {

                if (($v->contragent == $dRec->contragent) && ($v->productId == $dRec->productId)) {

                    $Doc = cls::get($v->docType);
                    $rDoc = $Doc->fetch($v->docId);
                    $handle = $Doc->className::getHandle($v->docId);
                    $state = $rDoc->state;

                    $singleUrl = toUrl(array($Doc->className, 'single', $v->docId));
                    $row->debitDocuments .= "<span class= 'state-{$state} document-handler' style='margin: 0 3px;'>" .
                        ht::createLink("#{$handle}", $singleUrl, false, "ef_icon={$Doc->singleIcon}") . '</span>' ;

                }
            }
        }

        $row->creditDocuments = '';
        if (!empty($dRec->documentsCreditQuantity)) {

            foreach ($dRec->documentsCreditQuantity as $v) {

                if (($v->contragent == $dRec->contragent) && ($v->productId == $dRec->productId)) {

                    $Doc = cls::get($v->docType);
                    $rDoc = $Doc->fetch($v->docId);
                    $handle = $Doc->className::getHandle($v->docId);
                    $state = $rDoc->state;

                    $singleUrl = toUrl(array($Doc->className, 'single', $v->docId));
                    $row->creditDocuments .= "<div style='margin-top: 2px;'><span class= 'state-{$state} document-handler' >" .
                        ht::createLink("#{$handle}", $singleUrl, false, "ef_icon={$Doc->singleIcon}") . '</span>' . '</div>';

                }
            }
        }

        $row->protocol = store_ConsignmentProtocols::getHyperlink($dRec->protocol);
        if ($dRec->inOut) {
            $row->inOut = 'Входящ';
        } else {
            $row->inOut = 'Изходящ';
        }

        return $row;
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager $Embedder
     * @param core_ET $tpl
     * @param stdClass $data
     */
    protected static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
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
        $Date = cls::get('type_Date');
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        $Enum = cls::get('type_Enum', array('options' => array('included' => 'Включено', 'off' => 'Изключено', 'only' => 'Само')));


        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN date--><div>|Към дата|*: [#date#]</div><!--ET_END date-->
                                        <!--ET_BEGIN storeId--><div>|Склад|*: [#storeId#]</div><!--ET_END storeId-->
                                        <!--ET_BEGIN group--><div>|Групи|*: [#group#]</div><!--ET_END group-->
                                        <!--ET_BEGIN products--><div>|Артикули|*: [#products#]</div><!--ET_END products-->
                                        <!--ET_BEGIN availability--><div>|Наличност|*: [#availability#]</div><!--ET_END availability-->
                                        <!--ET_BEGIN totalProducts--><div>|Брой артикули|*: [#totalProducts#]</div><!--ET_END totalProducts-->
                                        <!--ET_BEGIN workingPdogresOn--><div>|Незавършено производство|*: [#workingPdogresOn#]</div><!--ET_END workingPdogresOn-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));

        if (isset($data->rec->from)) {
            $fieldTpl->append('<b>' . $data->row->from . '</b>', 'from');
        }

        if (isset($data->rec->to)) {
            $fieldTpl->append('<b>' . $data->row->to . '</b>', 'to');
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

    /**
     * Създаване на нов ПОП
     */
    public static function act_NewProtocol()
    {

        expect($contragentFolder = Request::get('contragentFolder', 'int'));
        expect($storeId = Request::get('storeId', 'int'));

        $fRec = doc_Folders::fetch($contragentFolder);
        $contragentClassId = $fRec->coverClass;
        $contragentId = $fRec->coverId;

        // Прави запис в модела на движенията
        $pRec = (object)array(

            'folderId' => $contragentFolder,
            'storeId' => $storeId,
            'protocolType' => 'protocol',
            'productType' => 'ours',
            'contragentClassId' => $contragentClassId,
            'contragentId' => $contragentId,
            'state' => 'draft',
        );

        store_ConsignmentProtocols::save($pRec);

        $cu = core_Users::getCurrent();
        doc_ThreadUsers::addShared($pRec->threadId, $pRec->containerId, $cu);

        return new Redirect(array('store_ConsignmentProtocols', 'single', $pRec->id));

    }


}
