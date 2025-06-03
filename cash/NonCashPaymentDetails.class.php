<?php 

/**
 * Детайл за безналични методи на плащане към ПКО
 *
 *
 * @category  bgerp
 * @package   cash
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cash_NonCashPaymentDetails extends core_Manager
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
     * Кой може да изтрива?
     */
    public $canModify = 'cash, ceo, purchase, sales';


    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'cash_Wrapper,plg_Sorting';


    /**
     * Заглавие
     */
    public $title = 'Безналични начини на плащане';


    /**
     * Полета в листовия изглед
     */
    public $listFields = 'id,objectId=Обект,paymentId,amount,param,deviceId,transferredContainerId';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('classId', 'key(mvc=core_Classes)', 'input=none,caption=Клас');
        $this->FLD('objectId', 'int', 'input=hidden,mandatory,silent,oldFieldName=documentId,tdClass=leftCol,caption=Обект');
        $this->FLD('paymentId', 'key(mvc=cond_Payments, select=title,allowEmpty)', 'caption=Метод');
        $this->FLD('amount', 'double(decimals=2)', 'caption=Сума,mandatory');
        $this->FLD('param', 'varchar', 'caption=Параметър,input=none');
        $this->FLD('deviceId', 'key(mvc=peripheral_Devices,select=name)', 'caption=Периферия,input=none');
        $this->FLD('transferredContainerId', 'key(mvc=doc_Containers,select=id)', 'caption=Инкасиране,input=none');

        $this->setDbIndex('classId,objectId');
        $this->setDbUnique('classId,objectId,paymentId');
    }


    /**
     * Подготовка на детайла
     *
     * @param stdClass $data
     */
    public function prepareDetail_($data)
    {
        $masterClassId = $data->masterMvc->getClassId();
        $query = $this->getQuery();
        $query->where("#classId = {$masterClassId} AND #objectId = {$data->masterId}");
        $restAmount = $data->masterData->rec->amount;
        $toCurrencyCode = currency_Currencies::getCodeById($data->masterData->rec->currencyId);
        $canSeePrices = doc_plg_HidePrices::canSeePriceFields($data->masterMvc, $data->masterData->rec);
        $fields = $this->selectFields('');
        $fields['-detail'] = true;

        // Извличане на записите
        $data->recs = $data->rows = array();
        while ($rec = $query->fetch()) {
            $data->recs[$rec->id] = $rec;
            $data->rows[$rec->id] = $this->recToVerbal($rec, $fields);



            if (!$canSeePrices) {
                $data->rows[$rec->id]->amount = doc_plg_HidePrices::getBuriedElement();
            }

            $amount = cond_Payments::toBaseCurrency($rec->paymentId, $rec->amount, $data->masterData->rec->valior, $toCurrencyCode);
            $restAmount -= $amount;
        }

        if ($restAmount > 0 && countR($data->recs)) {
            $r = (object)array('classId' => $masterClassId, 'objectId' => $data->masterId, 'amount' => $restAmount, 'paymentId' => -1);
            $data->recs[] = $r;
            $row = $this->recToVerbal($r);
            $row->paymentId .= ", {$toCurrencyCode}";
            if (!$canSeePrices) {
                $row->amount = doc_plg_HidePrices::getBuriedElement();
            }
            $data->rows[] = $row;
        }

        $data->masterMvc->invoke('AfterPrepareNonCashPayments', array(&$data));

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
        if ($rec->paymentId == -1) {
            $row->paymentId = tr('В брой');
        }

        // Ако е избрано безналично плащане към активно ПКО
        $docRec = cls::get($rec->classId)->fetch($rec->objectId);
        if ($docRec->state == 'active' && $rec->paymentId != -1) {
            $cashFolderId = cash_Cases::fetchField($docRec->peroCase, "folderId");

            // И потребителя може да прави вътрешнокасов трансфер
            if (cash_InternalMoneyTransfer::haveRightFor("add", (object)array('folderId' => $cashFolderId))) {
                $currencyCode = cond_Payments::fetchField($rec->paymentId, 'currencyCode');
                $currencyId = !empty($currencyCode) ? currency_Currencies::getIdByCode($currencyCode) : acc_Periods::getBaseCurrencyId();

                $url = array('cash_InternalMoneyTransfer', 'add', 'folderId' => $cashFolderId, 'operationSysId' => 'nonecash2case', 'amount' => $rec->amount, 'creditCase' => $docRec->peroCase, 'paymentId' => $rec->paymentId, 'currencyId' => $currencyId, 'sourceId' => $docRec->containerId, 'foreignId' => $docRec->containerId, 'ret_url' => true);
                $toolbar = new core_RowToolbar();
                $toolbar->addLink('Инкасиране(Каса)', $url, "ef_icon = img/16/safe-icon.png,title=Създаване на вътрешно касов трансфер  за инкасиране на безналично плащане по каса");

                // Ако има периферия с избрана б.сметка да се подава тя
                if(isset($rec->deviceId)){
                    $deviceRec = peripheral_Devices::fetch($rec->deviceId);
                    if(isset($deviceRec->accountId)){
                        $url['debitBank'] = $deviceRec->accountId;
                    }
                }

                $url['operationSysId'] = 'nonecash2bank';
                $toolbar->addLink('Инкасиране(Банка)', $url, "ef_icon = img/16/own-bank.png,title=Създаване на вътрешно касов трансфер  за инкасиране на безналично плащане по банка");
                $row->buttons = $toolbar->renderHtml(2);
            }
        }

        $cardPaymentId = cond_Setup::get('CARD_PAYMENT_METHOD_ID');
        if ($rec->paymentId == $cardPaymentId) {

            // Показване на БПТ с който е платено
            $deviceId = $rec->deviceId;
            if($rec->classId == cash_Pko::getClassId()) {
                $deviceId = cash_Pko::fetchField($rec->objectId, 'bankPeripheralDeviceId');
            }

            if(isset($deviceId)){
                $row->deviceId = self::getCardPaymentBtnName($deviceId);
            }

            if($fields['-detail'] && !empty($row->deviceId)) {
                $row->paymentId = $row->deviceId;
            }

            // Показване как е платено
            if (!empty($rec->param) && !Mode::isReadOnly()) {
                $paramString = ($rec->param == 'card') ? "<span style='color:blue;'>" . tr('потв.') . "</span>" : "<span style='color:red;'>" . tr('ръчно') . "</span>";
                $row->paymentId .= " ({$paramString})";
            }
        }

        $Class = cls::get($rec->classId);
        $objectRec = $Class->fetch($rec->objectId);
        $link = cls::get($rec->classId)->getHyperlink($rec->objectId, true);
        $row->objectId = "<span class= 'state-{$objectRec->state} document-handler'>{$link}</span>";

        if(isset($rec->transferredContainerId)){
            $Document = doc_Containers::getDocument($rec->transferredContainerId);
            $row->transferredContainerId = $Document->getLink(0);
            $row->transferredContainerId = "<span class= 'state-{$Document->fetchField('state')} document-handler'>{$row->transferredContainerId}</span>";
        }
    }


    /**
     * Показва кепшъна на бутона за картово плащане
     *
     * @param int|stdClass $deviceId
     * @return string
     */
    public static function getCardPaymentBtnName($deviceId)
    {
        $deviceRec = peripheral_Devices::fetchRec($deviceId);
        try{
            $Int = cls::getInterface('bank_interface_POS', $deviceRec->driverClass);

            return $Int->getBtnName($deviceRec);
        } catch(core_exception_Expect $e){
            return tr('Карта');
        }
    }


    /**
     * Рендиране на детайла
     *
     * @param stdClass $data
     *
     * @return core_ET $tpl
     */
    public function renderDetail_($data)
    {
        $tpl = new core_ET('');
        $block = getTplFromFile('cash/tpl/NonCashPayments.shtml');

        if (countR($data->rows)) {
            foreach ($data->rows as $row) {
                $clone = clone $block;
                $clone->placeObject($row);
                $tpl->append($clone);
            }
        }

        return $tpl;
    }


    /**
     * Връща разрешените методи за плащане
     *
     * @param core_ObjectReference $document
     *
     * @return array $res
     */
    public static function getPaymentsTableArr($documentId, $documentClassId)
    {
        $res = array();

        // Взимане на методите за плащане към самия документ
        $query = self::getQuery();
        if (isset($documentId)) {
            $query->where("#classId = {$documentClassId} AND #objectId = {$documentId}");
            while ($rec = $query->fetch()) {
                $res['paymentId'][] = $rec->paymentId;
                $res['amount'][] = $rec->amount;
                $res['id'][] = $rec->id;
            }
        }

        return $res;
    }


    /**
     * Валидира таблицата с плащания
     *
     * @param mixed $tableData
     * @param core_Type $Type
     * @return void|string|array
     */
    public static function validatePayments($tableData, $Type)
    {
        $tableData = (array)$tableData;
        if (empty($tableData)) {

            return;
        }

        $res = $payments = $error = $errorFields = array();

        foreach ($tableData['paymentId'] as $key => $paymentId) {
            if (!empty($paymentId) && empty($tableData['amount'][$key])) {
                $error[] = 'Липсва сума при избран метод';
                $errorFields['amount'][$key] = 'Липсва сума при избран метод';
            }

            if (array_key_exists($paymentId, $payments)) {
                $error[] = 'Повтарящ се метод';
                $errorFields['zone'][$key] = 'Повтаряща се метод';
            } else {
                $payments[$paymentId] = $paymentId;
            }
        }

        foreach ($tableData['amount'] as $key => $quantity) {
            if (!empty($quantity) && empty($tableData['paymentId'][$key])) {
                $error[] = 'Зададено количество без зона';
                $errorFields['amount'][$key] = 'Зададено количество без зона';
            }

            if (empty($quantity)) {
                $error[] = 'Количеството не може да е 0';
                $errorFields['amount'][$key] = 'Количеството не може да е 0';
            }

            $Double = core_Type::getByName('double');
            $q2 = $Double->fromVerbal($quantity);
            if (!$q2) {
                $error[] = 'Невалидно количество';
                $errorFields['amount'][$key] = 'Невалидно количество';
            }
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
     * Връща записа за картотово плащане, ако има
     *
     * @param int $pkoId  - ид на пко
     * @return mixed|null
     */
    public static function getCardPaymentRec($pkoId)
    {
        $cardPaymentId = cond_Setup::get('CARD_PAYMENT_METHOD_ID');
        if(empty($cardPaymentId)) return;

        $cashClassId = cash_Pko::getClassId();

        return cash_NonCashPaymentDetails::fetch("#classId = {$cashClassId} AND #objectId = {$pkoId} AND #paymentId = {$cardPaymentId}");
    }


    /**
     * Подготовка на филтър формата
     *
     * @param core_Mvc $mvc
     * @param StdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->setField('objectId', 'input,silent');
        $data->listFilter->setField('classId', 'input,silent');
        $data->listFilter->setFieldTypeParams('classId', 'allowEmpty');

        $data->listFilter->showFields = 'classId,objectId,paymentId';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input('classId,objectId,paymentId', 'silent');

        // Сортиране на записите по num
        $data->query->orderBy('id', 'DESC');

        if($filter = $data->listFilter->rec) {
            if(isset($filter->paymentId)){
                $data->query->where("#paymentId = {$filter->paymentId}");
            }

            if(isset($filter->objectId)){
                $data->query->where("#objectId = {$filter->objectId}");
            }

            if(isset($filter->classId)){
                $data->query->where("#classId = {$filter->classId}");
            }
        }
    }


    /**
     * Кои са неинкасираните плащания за този безналичен метод
     *
     * @param int $paymentId     - ид на безналично плащане
     * @param int $caseId        - ид на каса
     * @param int $bankAccountId - ид на наша б.сметка
     * @return array
     */
    public static function getNotCollectedRecs($paymentId, $caseId, $bankAccountId)
    {
        $peripheralsWithBankId = array();
        $devices = peripheral_Devices::getDevices('bank_interface_POS', false);
        foreach ($devices as $deviceRec) {
            if($deviceRec->accountId == $bankAccountId) {
                $peripheralsWithBankId[$deviceRec->id] = $deviceRec->id;
            }
        }
        if(!countR($peripheralsWithBankId)) return array();

        // Извличат се всички безналични плащания отговарящи на условията, които не са прехвърлени
        $query = self::getQuery();
        $query->where("#transferredContainerId IS NULL AND #paymentId = {$paymentId}");
        $query->in("deviceId", $peripheralsWithBankId);
        $query->orderBy('id', 'ASC');

        $res = array();
        while($rec = $query->fetch()){
            $Class = cls::get($rec->classId);
            $objectRec = $Class->fetch($rec->objectId);
            if($Class instanceof cash_Pko){
                $state = $objectRec->state;
                $objectCaseId = $objectRec->peroCase;
            } else {
                $state = $objectRec->state;
                $objectCaseId = pos_Points::fetchField($objectRec->pointId, 'caseId');
            }

            if(in_array($state, array('draft', 'rejected'))) continue;
            if($objectCaseId != $caseId) continue;

            $res[$rec->id] = $rec;
        }

        return $res;
    }
}
