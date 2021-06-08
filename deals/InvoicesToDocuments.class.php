<?php

/**
 * Детайл за фактури към документи
 *
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class deals_InvoicesToDocuments extends core_Manager
{
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';


    /**
     * Кой може да създава?
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да редактира?
     */
    public $canEdit = 'no_one';


    /**
     * Кой може да изтрива?
     */
    public $canDelete = 'no_one';


    /**
     * Заглавие
     */
    public $title = 'Фактури към документи';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('documentContainerId', 'key(mvc=doc_Containers, select=id)', 'input=hidden,mandatory,silent,caption=Документ');
        $this->FLD('containerId', 'key(mvc=doc_Containers, select=id)', 'caption=Фактура');
        $this->FLD('amount', 'double(decimals=2)', 'caption=Сума,mandatory');

        $this->setDbIndex('documentContainerId');
        $this->setDbUnique('documentContainerId,containerId');
    }

    function act_SelectInvoice()
    {
        expect($documentId = Request::get('documentId', 'int'));
        expect($documentClassId = Request::get('documentClassId', 'int'));
        $Document = cls::get($documentClassId);
        expect($rec = $Document->fetch($documentId));
        $Document->requireRightFor('selectinvoice');
        $Document->requireRightFor('selectinvoice', $rec);
        $paymentData = $Document->getPaymentData($rec);

        $form = cls::get('core_Form');
        $form->title = "Избор на фактури към|* " . cls::get($Document)->getFormTitleLink($documentId);

        $tVerbal = core_Type::getByName('double(decimals=2)')->toVerbal($paymentData->amount);
        $currencyCode = currency_Currencies::getCodeById($paymentData->currencyId);
        $form->info = tr("За разпределяне:") . " <b>{$tVerbal} {$currencyCode}</b>";
        $form->FLD('invoices', "table(columns=containerId|amount,captions=Документ|Сума ({$currencyCode}),validate=deals_InvoicesToDocuments::validateTable)", "caption=Избор");

        $invoices = $Document->getReasonContainerOptions($rec);
        $form->setFieldTypeParams('invoices', array('amount_sgt' => array('' => '', "{$paymentData->amount}" => $paymentData->amount), 'containerId_opt' => array('' => '') + $invoices, 'totalAmount' => $paymentData->amount, 'currencyId' => $paymentData->currencyId));
        $curInvoiceArr = static::getInvoicesTableArr($rec->containerId);
        $form->setDefault('invoices', $curInvoiceArr);

        $form->input();
        if($form->isSubmitted()){
            $invArr = type_Table::toArray($form->rec->invoices);
            $newArr = array();
            foreach ($invArr as $obj){
                $newArr[] = (object)array('documentContainerId' => $rec->containerId, 'containerId' => $obj->containerId, 'amount' => $obj->amount);
            }

            $exRecs = static::getInvoiceArr($rec->containerId);
            $syncedArr = arr::syncArrays($newArr, $exRecs, 'containerId,amount', 'containerId,amount');

            if(countR($syncedArr['insert'])){
                $this->saveArray($syncedArr['insert']);
            }

            if(countR($syncedArr['update'])){
                $this->saveArray($syncedArr['update'], 'id,containerId,amount');
            }

            if(countR($syncedArr['delete'])){
                $inStr = implode(',', $syncedArr['delete']);
                $this->delete("#id IN ({$inStr})");
            }
            plg_Search::forceUpdateKeywords($Document, $rec);
            $Document->touchRec($rec);
            $Document->logWrite("Промяна към кои фактури е документа", $rec->id);
            followRetUrl();
        }

        // Добавяне на тулбар
        $form->toolbar->addSbBtn('Промяна', 'save', 'ef_icon = img/16/disk.png, title = Импорт');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');

        // Рендиране на опаковката
        $tpl = $Document->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($tpl, $form);

        return $tpl;
    }


    /**
     * Валидира таблицата с плащания
     *
     * @param mixed $tableData
     * @param core_Type $Type
     * @return void|string|array
     */
    public static function validateTable($tableData, $Type)
    {
        $tableData = (array) $tableData;
        if (empty($tableData)) {

            return;
        }

        $res = $containers = $error = $errorFields = array();

        foreach ($tableData['containerId'] as $key => $containerId) {
            if (!empty($containerId) && empty($tableData['amount'][$key])) {
                $error[] = 'Липсва сума при избран документ';
                $errorFields['amount'][$key] = 'Липсва сума при избран документ';
            }

            if (array_key_exists($containerId, $containers)) {
                $error[] = 'Повтарящ се документ';
                $errorFields['containerId'][$key] = 'Повтарящ се документ';
            } else {
                $containers[$containerId] = $containerId;
            }
        }

        $totalAmount = 0;
        foreach ($tableData['amount'] as $key => $amount) {
            if (!empty($amount) && empty($tableData['containerId'][$key])) {
                $error[] = 'Зададенa сума без посочен документ';
                $errorFields['amount'][$key] = 'Зададенa сума без посочен документ';
            }

            if (empty($amount)) {
                $error[] = 'Сумата не може да е 0';
                $errorFields['amount'][$key] = 'Невалидна сума не може да е 0';
            }

            $Double = core_Type::getByName('double');
            $q2 = $Double->fromVerbal($amount);
            if (!$q2) {
                $error[] = 'Невалидна сума';
                $errorFields['amount'][$key] = 'Невалидна сума';
            }

            if(!isset($errorFields['amount'][$key])){
                $totalAmount += $amount;
            }
        }

        if(round($totalAmount, 2) != round($Type->params['totalAmount'], 2)){
            $tVerbal = core_Type::getByName('double(decimals=2)')->toVerbal($Type->params['totalAmount']);
            $currencyCode = currency_Currencies::getCodeById($Type->params['currencyId']);
            $error[] = "Общата сума трябва да прави точно:|* <b>{$tVerbal}</b> {$currencyCode}";
        }

        if (countR($error)) {
            $error = implode('|*<li>|', $error);
            $res['error'] = $error;
        }

        if (countR($errorFields)) {
            $res['errorFields'] = $errorFields;
        }

        return $res;
    }


    /**
     * Подготовка на детайла
     *
     * @param stdClass $data
     */
    public function prepareInvoices($data)
    {
        $masterRec = $data->masterData->rec;
        $paymentData = $data->masterMvc->getPaymentData($data->masterId);
        $data->recs = static::getInvoiceArr($masterRec->containerId);

        $data->rows = array();
        foreach ($data->recs as $key => $rec) {
            $data->rows[$key] = $this->recToVerbal($rec);
            $data->rows[$key]->currencyId = currency_Currencies::getCodeById($paymentData->currencyId);
        }

        return $data;
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $Document = doc_Containers::getDocument($rec->containerId);
        $row->documentName = mb_strtolower($Document->singleTitle);

        if ($Document->getInstance()->getField('number', false)) {
            $row->containerId = $Document->getInstance()->getVerbal($Document->fetch(), 'number');
            if (!Mode::isReadOnly()) {
                $row->containerId = ht::createLink($row->containerId, $Document->getSingleurlArray());
            }
        } else {
            $row->containerId = $Document->getLink(0);
        }

        $row->documentContainerId = doc_Containers::getDocument($rec->documentContainerId)->getLink(0);
    }


    /**
     * Рендиране на детайла
     *
     * @param stdClass $data
     *
     * @return core_ET $tpl
     */
    public function renderInvoices($data)
    {
        $tpl = new core_ET("");
        $block = getTplFromFile('deals/tpl/InvoicesToDocuments.shtml');

        if (countR($data->rows)) {
            foreach ($data->rows as $row) {
                $clone = clone $block;
                $clone->placeObject($row);
                $tpl->append($clone);
            }
        }
        $tpl->removeBlocksAndPlaces();

        return $tpl;
    }


    /**
     * Връща масив с ф-те към документа
     *
     * @param int $documentContainerId
     * @return array
     */
    public static function getInvoiceArr($documentContainerId, $skipClasses = array())
    {
        $query = static::getQuery();
        $query->where("#documentContainerId = {$documentContainerId}");
        $skipClasses = arr::make($skipClasses, true);
        if(countR($skipClasses)){
            $classIds = array();
            array_walk($skipClasses, function ($a) use (&$classIds) {$classIds[] = cls::get($a)->getClassId();});
            $classIds = implode(',', $classIds);
            $query->EXT('docClass', 'doc_Containers', 'externalKey=containerId');
            $query->where("#docClass NOT IN ({$classIds})");
        }

        return $query->fetchAll();
    }


    /**
     * Връща разрешените методи за плащане
     *
     * @param int
     *
     * @return array $res
     */
    private static function getInvoicesTableArr($documentContainerId)
    {
        $res = array();
        $exRecs = static::getInvoiceArr($documentContainerId, "sales_Proformas");

        foreach ($exRecs as $rec) {
            $res['containerId'][] = $rec->containerId;
            $res['amount'][] = $rec->amount;
        }

        return $res;
    }


    /**
     * Мограционна функция за същесъвуващите документи
     * @todo да се махне след рилийза
     *
     * @param mixed $mvc
     */
    public static function migrateContainerIds($mvc)
    {
        $mvc = cls::get($mvc);
        $me = cls::get(get_called_class());
        $me->setupMvc();

        $res = array();
        $query = $mvc->getQuery();
        $query->where("#fromContainerId IS NOT NULL");
        $recs = $query->fetchAll();

        $count = countR($recs);

        if(empty($count)) return;

        core_App::setTimeLimit($count * 0.4, false, 200);

        foreach($recs as $rec){
            static::delete("#documentContainerId = {$rec->containerId}");
            $paymentData = $mvc->getPaymentData($rec->id);
            $rec = (object)array('documentContainerId' => $rec->containerId, 'containerId' => $rec->fromContainerId, 'amount' => $paymentData->amount);
            $res[] = $rec;
        }

        $me->saveArray($res);
    }
}
