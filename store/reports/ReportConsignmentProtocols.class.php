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
 * @title     Склад » Отговорно пазене
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
    protected $changeableFields = 'from,to,contragent';


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('from', 'date', 'caption=От,after=title,single=none,mandatory');
        $fieldset->FLD('to', 'date', 'caption=До,after=from,single=none,mandatory');
	    $fieldset->FLD('typeOfReport', 'enum(standard=Само с ПОП, zeroRows=Всички от избраните групи)', 'caption=Контрагенти->Избор,after=to,removeAndRefreshForm,single=none,silent');
        $fieldset->FLD('crmGroup', 'keylist(mvc=crm_Groups,select=name)', 'caption=Контрагенти->Група контрагенти,placeholder=Избери,mandatory,input=none,after=typeOfReport,single=none');

        $fieldset->FLD('contragent', 'keylist(mvc=doc_Folders,select=title,allowEmpty)', 'caption=Контрагенти->Контрагент,placeholder=Всички които имат издавани ПОП,single=none,after=typeOfReport');
        $fieldset->FLD('seeZeroRows', 'set(yes = )', 'caption=Контрагенти->Без текуща наличност,after=contragent,single=none,silent');
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

        $form->setDefault('typeOfReport', 'zeroRows');
        $form->setDefault('seeZeroRows', null);

        if ($rec->typeOfReport == 'zeroRows') {
            $form->setField('crmGroup', 'input');
            $form->setField('contragent', 'input=hidden');
            $form->setField('seeZeroRows', 'input=hidden');
        }

        if ($rec->typeOfReport == 'standard') {

            $consignmentQuery = store_ConsignmentProtocols::getQuery();

            $consignmentQuery->EXT('folderTitle', 'doc_Folders', 'externalName=title,externalKey=folderId');
            $consignmentQuery->limit(20);

            $consignmentQuery->groupBy('folderId');

            $consignmentQuery->show('folderId, contragentId, folderTitle');

            while ($contragent = $consignmentQuery->fetch()) {
                if (!is_null($contragent->contragentId)) {
                    $suggestions[$contragent->folderId] = $contragent->folderTitle;
                }
            }

            asort($suggestions);

            $form->setSuggestions('contragent', $suggestions);
        }else{
            $form->setSuggestions('contragent', array());
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
        $stateArr = array('rejected');
        // фирми, които са включени в избраните групи
        $crmComp = crm_Companies::getQuery();
        $crmComp -> in('state', $stateArr,true);
        $crmComp->likeKeylist('groupList', $rec->crmGroup);
        $crmComp -> where("#folderId IS NOT NULL");

        $contragentsInGroups = arr::extractValuesFromArray($crmComp->fetchAll(), 'folderId');

        //лица, които са включени в избраните групи
        $crmPers = crm_Persons::getQuery();
        $crmPers -> in('state', $stateArr,true);
        $crmPers->likeKeylist('groupList', $rec->crmGroup);
        $crmPers -> where("#folderId IS NOT NULL");

        //общо контрагенти в избраните групи
        $contragentsInGroups = $contragentsInGroups + arr::extractValuesFromArray($crmPers->fetchAll(), 'folderId');

        $Balance = new acc_ActiveShortBalance(array('from' => $rec->from, 'to' => $rec->to, 'accs' => '3231', 'cacheBalance' => false, 'keepUnique' => true));

        $balHistory = $Balance->getBalanceHystory('3231', $from = $rec->from, $to = $rec->to, $item1 = null, $item2 = null, $item3 = null, $groupByDocument = false, $strict = true);

        $documentsDebitQuantity1 = $documentsCreditQuantity1 = array();

        foreach ($balHistory['history'] as $jRec) {

            $pRec = cls::get($jRec['docType'])->fetch($jRec['docId']);

            $debitQuantity = $creditQuantity = 0;

            if($rec->typeOfReport == 'zeroRows'){
                if(!in_array($pRec->folderId, $contragentsInGroups)) continue;
            }

            $contragentName = doc_Folders::getTitleById($pRec->folderId);

            //филтър по контрагент когато е избран режим на справката стандартен
            if($rec->typeOfReport == 'standard' && $rec->contragent  && !in_array($pRec->folderId, keylist::toArray($rec->contragent))) continue;

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
                    'contragentName' => $contragentName,
                    'docClsId' => $jRec['docType'],
                    'docId' => $jRec['docId'],
                    'productId' => $prodRec->id,
                    'measureId' => $prodRec->measureId,
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

        //Добавяме масивите с документите
        foreach ($recs as $key => $r) {
            $r->documentsDebitQuantity = $documentsDebitQuantity1;
            $r->documentsCreditQuantity = $documentsCreditQuantity1;
        }

        //Добавяне на празните редове с бутон за създаване на ПОП
        if($rec->typeOfReport == 'zeroRows') {
            $contragentsInRecs = arr::extractValuesFromArray($recs, 'contragent');

            foreach ($contragentsInGroups as $contragent) {
                $id = $contragent;

                if (in_array($contragent, $contragentsInRecs)) continue;

                $contragentName = doc_Folders::getTitleById($contragent);

                $recs[$id] = (object)array(
                    'contragent' => $contragent,
                    'contragentName' => $contragentName,
                    'docClsId' => null,
                    'docId' => null,
                    'productId' => null,
                    'measureId' => null,
                    'debitQuantity' => null,
                    'creditQuantity' => null,
                    'documentsDebitQuantity' => array(),
                    'documentsCreditQuantity' => array(),
                    'date' => null,
                    'storeId' => null,
                );

            }
        }
        if (countR($recs)) {
            arr::sortObjects($recs, 'contragentName', 'asc', 'stri');
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
            $fld->FLD('measureId', 'key(mvc=cat_Uom,select=shortName)', 'smartCenter,caption=Мярка');
            //$fld->FLD('date', 'date', 'caption=Дата');
            $fld->FLD('quantity', 'double(decimals=2)', 'caption=К-во,smartCenter,tdClass=boldText');
            $fld->FLD('debitQuantity', 'double(decimals=2)', 'caption=Дадено->К-во,smartCenter');
            $fld->FLD('debitDocuments', 'varchar', 'caption=Дадено->Документи,tdClass=midCell');
            $fld->FLD('creditQuantity', 'double(decimals=2)', 'caption=Прието->К-во,smartCenter');
            $fld->FLD('creditDocuments', 'varchar', 'caption=Прието->Документи,tdClass=midCell');

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

        if($rec->seeZeroRows == null && (($dRec->debitQuantity - $dRec->creditQuantity) == 0) && $rec->typeOfReport == 'standard') {

            return ;
        }

        $row = new stdClass();

        $row->contragent = "<span style='line-height: 140%'>" . doc_Folders::getTitleById($dRec->contragent) . "</span>";

        $userId = core_Users::getCurrent();
        if (store_ConsignmentProtocols::haveRightFor('add')) {
            $cUrl = array('store_reports_ReportConsignmentProtocols', 'newProtocol', 'contragentFolder' => $dRec->contragent, 'ret_url' => true);

            $row->contragent .= "<span class='fright smallBtnHolder'>" . ht::createBtn('Нов ПОП', $cUrl, false, true, "ef_icon = img/16/add.png") . "</span>";
        }

        if (isset($dRec->measureId)) {
            $row->measureId = cat_UoM::fetchField($dRec->measureId, 'shortName');
        } else {
            $row->measureId = '';
        }

        if (isset($dRec->productId)) {
            $row->productId = cat_Products::getHyperlink($dRec->productId);
        } else {
            $row->productId = '';
        }

        if (isset($dRec->debitQuantity) || isset($dRec->creditQuantity)) {
            $row->quantity = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->debitQuantity - $dRec->creditQuantity);
        }

        if (isset($dRec->debitQuantity)) {
            $row->debitQuantity = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->debitQuantity);
        }
        if (isset($dRec->creditQuantity)) {
            $row->creditQuantity = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->creditQuantity);
        }

        $row->debitDocuments = '';
        if (!empty($dRec->documentsDebitQuantity)) {

            foreach ($dRec->documentsDebitQuantity as $v) {

                if (($v->contragent == $dRec->contragent) && ($v->productId == $dRec->productId)) {

                    $Doc = cls::get($v->docType);
                    $rDoc = $Doc->fetch($v->docId);
                    $handle = $Doc->className::getHandle($v->docId);
                    $state = $rDoc->state;

                    $singleUrl = toUrl(array($Doc->className, 'single', $v->docId));
                    $row->debitDocuments .= "<span class= 'state-{$state} document-handler' style='margin: 1px 3px;'>" .
                        ht::createLink("#{$handle}", $singleUrl, false, array('target' => '_blank','ef_icon' => "{$Doc->singleIcon}")) . '</span>';
//ht::createLink($str, $str, false, array('target' => '_blank')),
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
                    $row->creditDocuments .= "<span class= 'state-{$state} document-handler' style='margin: 1px 3px;'>" .
                        ht::createLink("#{$handle}", $singleUrl, false, array('target' => '_blank','ef_icon' => "{$Doc->singleIcon}")) . '</span>';

                }
            }
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
        requireRole('ceo,store');

        expect($contragentFolder = Request::get('contragentFolder', 'int'));

        $form = cls::get('core_Form');

        $form->title = "Избор на полета";
        $fRec = doc_Folders::fetch($contragentFolder);
        $contragentClassId = $fRec->coverClass;
        $contragentId = $fRec->coverId;

        $form->FLD('storeId', 'key(mvc=store_Stores, select=name)', 'caption=Склад,silent');
        $form->FLD('folderId', 'int', 'caption=Контрагент,silent,input=hidden');
        $form->FLD('productType', 'enum(ours=Наши артикули,other=Чужди артикули)', 'caption=Артикули наши/ външни,silent');
        $form->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf,select=title)', 'input=hidden,caption=caption=Контрагент->Вид,silent');
        $form->FLD('contragentId', 'int', 'input=hidden,caption=caption=Контрагент->Име,silent');
        $form->FLD('state', 'enum(draft=Чернова, active=Контиран, rejected=Оттеглен,stopped=Спряно,pending=Заявка)', 'caption=Статус, input=none');;
        $form->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code,allowEmpty)', 'mandatory,caption=Валута, input=none');;

        $pRec = $form->input();
        $pRec->folderId = $contragentFolder;
        $pRec->contragentClassId = $contragentClassId;
        $pRec->contragentId = $contragentId;
        $pRec->state = 'draft';
        $pRec->currencyId = acc_Periods::getBaseCurrencyCode();

        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');

        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png');

        if ($form->isSubmitted()) {

            store_ConsignmentProtocols::save($pRec);

            $cu = core_Users::getCurrent();
            doc_ThreadUsers::addShared($pRec->threadId, $pRec->containerId, $cu);

            return new Redirect(array('store_ConsignmentProtocols', 'single', $pRec->id));
        }

        return $form->renderHtml();

    }
}
