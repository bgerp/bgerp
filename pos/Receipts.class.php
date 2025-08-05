<?php


/**
 * Мениджър за "Бележки за продажби"
 *
 *
 * @category  bgerp
 * @package   pos
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.11
 */
class pos_Receipts extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Бележки за продажба';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_Rejected, plg_Printing, acc_plg_DocumentSummary, plg_Printing, plg_State, pos_Wrapper, cat_plg_AddSearchKeywords, plg_Search, plg_Sorting, plg_Modified,plg_RowTools2,store_plg_StockPlanning,plg_Select';


    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Бележка за продажба';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'createdOn, modifiedOn, valior, title=Бележка, pointId=Точка, contragentId=Контрагент, productCount, total, paid, change, state, returnedTotal, createdOn, createdBy, waitingOn, waitingBy';


    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'sales_RatingsSourceIntf';


    /**
     * Детайли на бележката
     */
    public $details = 'pos_ReceiptDetails';


    /**
     * Главен детайл на модела
     */
    public $mainDetail = 'pos_ReceiptDetails';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, pos';


    /**
     * Кой може да приключи бележка?
     */
    public $canClose = 'ceo, pos';


    /**
     * Кой може да прехвърли бележка?
     */
    public $canTransfer = 'ceo, pos';


    /**
     * Кой може да променя?
     */
    public $canAdd = 'pos, ceo';


    /**
     * Кой може да сторнира?
     */
    public $canRevert = 'pos, ceo';


    /**
     * Кой може ръчно да прави чакаща?
     */
    public $canManualpending = 'posMaster, ceo';


    /**
     * Кой може да плати?
     */
    public $canPay = 'pos, ceo';


    /**
     * Кой може да променя?
     */
    public $canTerminal = 'pos, ceo';


    /**
     * Кой може да оттегля
     */
    public $canReject = 'pos, ceo';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,pos';


    /**
     * Кой може да задава клиент?
     */
    public $canSetcontragent = 'ceo,pos';


    /**
     * Кой може да задава ваучер?
     */
    public $canSetvoucher = 'ceo,pos';


    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,pos';


    /**
     * Кой може да променя?
     */
    public $canEdit = 'pos,ceo';


    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'pos/tpl/SingleLayoutReceipt.shtml';


    /**
     * Кои полета да се извлекат преди изтриване
     */
    public $fetchFieldsBeforeDelete = 'id';


    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn, valior, waitingOn';


    /**
     *  Полета по които ще се търси
     */
    public $searchFields = 'pointId, contragentName, valior';


    /**
     *  Служебно ид на дефолтна рецепта за сторниране
     */
    const DEFAULT_REVERT_RECEIPT = -1;


    /**
     *  При преминаването в кои състояния ще се обновяват планираните складови наличностти
     */
    public $updatePlannedStockOnChangeStates = array('waiting');


    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'revertId,returnedTotal,waitingOn,waitingBy';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('valior', 'date', 'caption=Вальор,input=none');
        $this->FLD('pointId', 'key(mvc=pos_Points, select=name)', 'caption=Точка на продажба');
        $this->FLD('contragentName', 'varchar(255)', 'caption=Контрагент,input=none');
        $this->FLD('contragentObjectId', 'int', 'input=none');
        $this->FLD('contragentLocationId', 'key(mvc=crm_Locations)', 'caption=Локация,input=none');
        $this->FLD('contragentClass', 'key(mvc=core_Classes,select=name)', 'input=none');
        $this->FLD('total', 'double(decimals=2)', 'caption=Общо, input=none, value=0');
        $this->FLD('paid', 'double(decimals=2)', 'caption=Платено, input=none, value=0');
        $this->FLD('change', 'double(decimals=2)', 'caption=Ресто, input=none, value=0');
        $this->FLD('tax', 'double(decimals=2)', 'caption=Такса, input=none, value=0');
        $this->FLD('state', 'enum(draft=Чернова, active=Контиран, rejected=Оттеглен, closed=Затворен,waiting=Чакащ)', 'caption=Статус, input=none');
        $this->FLD('transferredIn', 'key(mvc=sales_Sales)', 'input=none,oldFieldName=transferedIn');
        $this->FLD('revertId', 'int', 'input=none,caption=Сторнира');
        $this->FLD('returnedTotal', 'double(decimals=2)', 'caption=Сторнирано, input=none');
        $this->FNC('productCount', 'int', 'caption=Артикули');
        $this->FLD('waitingOn', 'datetime(format=smartTime)', 'caption=Чакаща->На,input=none');
        $this->FLD('waitingBy', 'key(mvc=core_Users,select=nick)', 'caption=Чакаща->От,input=none');
        $this->FLD('policyId', 'key(mvc=price_Lists,select=id)', 'caption=Ваучер,input=none');

        if (core_Packs::isInstalled('voucher')) {
            $this->FLD('voucherId', 'key(mvc=voucher_Cards,select=id,allowEmpty)', 'caption=Ваучер,input=none');
            $this->fetchFieldsBeforeDelete .= ",voucherId";
            $this->setDbIndex('voucherId');
        }

        $this->setDbIndex('valior');
        $this->setDbIndex('revertId');
    }


    /**
     *  Екшън създаващ нова бележка, и редиректващ към Единичния и изглед
     *  Добавянето на нова бележка става само през този екшън
     */
    public function act_New()
    {
        $pointId = Request::get('pointId', 'int');

        if (!isset($pointId)) {
            $pointId = pos_Points::getCurrent();
        } else {
            pos_Points::selectCurrent($pointId);
        }

        pos_Points::requireRightFor('select', $pointId);
        $forced = Request::get('forced', 'int');

        // Ако форсираме, винаги създаваме нова бележка
        if ($forced) {
            $contragentClass = Request::get('contragentClass', 'int');
            $contragentId = Request::get('contragentObjectId', 'int');

            $id = $this->createNew(null, $contragentClass, $contragentId);
            $this->logWrite('Създаване на нова бележка', $id);
        } else {
            // Коя е последната чернова бележка от ПОС-а
            $cu = core_Users::getCurrent();
            $query = $this->getQuery();
            $query->where("#pointId = {$pointId} AND #createdBy = {$cu} AND #state = 'draft' AND #revertId IS NULL");
            $query->show('valior,contragentClass,contragentObjectId,total');
            $query->orderBy('id', 'DESC');
            $lastDraft = $query->fetch();

            $id = null;
            if (is_object($lastDraft)) {
                // Ако има такава и тя е без контрагент и е празна
                if (empty($lastDraft->total) && pos_Receipts::isForDefaultContragent($lastDraft)) {
                    $today = dt::today();
                    if ($lastDraft->valior != $today) {
                        $lastDraft->valior = $today;
                        $this->save_($lastDraft, 'valior');
                    }
                    $id = $lastDraft->id;
                }
            }

            if (empty($id)) {
                $id = $this->createNew();
                $this->logWrite('Създаване на нова бележка', $id);
            }
        }

        // Записваме, че потребителя е разглеждал този списък
        $foundRec = self::fetch($id);
        $operation = (empty($foundRec->paid)) ? 'add' : 'payment';
        Mode::setPermanent("currentOperation{$id}", $operation);
        Mode::setPermanent("currentSearchString{$id}", null);

        return new Redirect(array('pos_Terminal', 'open', 'receiptId' => $id));
    }


    /**
     * Създава нова чернова бележка
     *
     * @param int|null $revertId - ид на бележка, която да се сторнира
     * @param int|null $contragentClass - клас на контрагент
     * @param int|null $contragentId - ид на контрагент
     * @return int
     */
    private function createNew($revertId = null, $contragentClass = null, $contragentId = null)
    {
        $rec = new stdClass();
        $posId = pos_Points::getCurrent();

        $rec->pointId = $posId;
        $rec->valior = dt::now();
        $this->requireRightFor('add', $rec);

        // Ако ще е сторнираща бележка - да е към същия котрагент
        $setDefaultContragent = true;
        if (!empty($revertId)) {
            if ($revertId != pos_Receipts::DEFAULT_REVERT_RECEIPT) {
                $recToRevert = static::fetch($revertId);
                $rec->contragentName = $recToRevert->contragentName;
                $rec->contragentClass = $recToRevert->contragentClass;
                $rec->contragentObjectId = $recToRevert->contragentObjectId;
                $setDefaultContragent = false;
            }
            $rec->revertId = $revertId;
        } else {

            // Ако е нова да е или към подадения, или към анонимния
            if (isset($contragentClass) && isset($contragentId)) {
                $rec->contragentClass = $contragentClass;
                $rec->contragentObjectId = $contragentId;
                $rec->contragentName = cls::get($contragentClass)->getVerbal($contragentId, 'name');
                $setDefaultContragent = false;
            }
        }

        if ($setDefaultContragent) {
            $rec->contragentName = 'Анонимен Клиент';
            $rec->contragentClass = core_Classes::getId('crm_Persons');
            $rec->contragentObjectId = pos_Points::defaultContragent($posId);
        }

        return $this->save($rec);
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->currency = acc_Periods::getBaseCurrencyCode($rec->createdOn);
        if (!empty($rec->returnedTotal) && empty($rec->revertId)) {
            $row->returnedTotal = ht::styleIfNegative("-{$row->returnedTotal}", -1 * $rec->returnedTotal);
            $row->returnedCurrency = $row->currency;
        }
        $row->contragentId = static::getMaskedContragent($rec->contragentClass, $rec->contragentObjectId, $rec->pointId, array('link' => true, 'icon' => true, 'policyId' => $rec->policyId));
        if (isset($rec->voucherId) && core_Packs::isInstalled('voucher')) {
            $row->voucherId = voucher_Cards::getVerbal($rec->voucherId, 'number');
        }

        if ($fields['-list']) {
            $row->title = $mvc->getHyperlink($rec->id, true);
        } elseif ($fields['-single']) {
            $row->title = self::getRecTitle($rec);
            $row->iconStyle = 'background-image:url("' . sbf('img/16/view.png', '') . '");';
            $row->caseId = cash_Cases::getHyperLink(pos_Points::fetchField($rec->pointId, 'caseId'), true);
            $row->storeId = store_Stores::getHyperLink(pos_Points::fetchField($rec->pointId, 'storeId'), true);
            $row->baseCurrency = acc_Periods::getBaseCurrencyCode($rec->createdOn);
            if ($rec->transferredIn) {
                $row->transferredIn = sales_Sales::getHyperlink($rec->transferredIn, true);
            }

            if ($rec->state == 'closed' || $rec->state == 'rejected') {
                $isIn = pos_Reports::getReportReceiptIsIn($rec->id);
                if (isset($isIn)) {
                    $row->inReport = pos_Reports::getLink($isIn, 0);
                }
            }
        }

        if (isset($fields['-terminal'])) {
            $row->id = ht::createLink($row->id, pos_Receipts::getSingleUrlArray($rec->id), false, array('class' => 'specialLink'));
            if (!empty($rec->voucherId)) {
                $endVoucher = substr($row->voucherId, 12, 4);
                $row->voucherId = "*{$endVoucher}";
                if (pos_Receipts::haveRightFor('setvoucher', $rec)) {
                    $row->voucherId .= " " . ht::createLink('', array('pos_Receipts', 'setvoucher', 'id' => $rec->id, 'voucherId' => null, 'ret_url' => true), false, array('ef_icon' => 'img/16/delete.png', 'title' => 'Премахване на избрания ваучер'));
                }
                $row->voucherCaption = tr('Ваучер');
            }
        } else {
            if (!empty($rec->voucherId) && core_Packs::isInstalled('voucher')) {
                $voucherRec = voucher_Cards::fetch($rec->voucherId);
                if(isset($voucherRec->referrer)){
                    $row->voucherId = ht::createHint($row->voucherId, "Препоръчител|*: " . crm_Persons::getTitleById($voucherRec->referrer));
                }
            }
        }

        $rec->total = abs($rec->total);
        $row->total = $mvc->getFieldType('change')->toVerbal($rec->total);
        if (!empty($rec->paid)) {
            $row->PAID_CAPTION = (isset($rec->revertId)) ? tr('Върнато') : tr('Платено');
            $rec->paid = abs($rec->paid);
            $row->paid = $mvc->getFieldType('paid')->toVerbal($rec->paid);
            $row->paidCurrency = $row->currency;
            if (!empty($rec->change)) {
                $row->CHANGE_CLASS = ($rec->change < 0 || isset($rec->revertId)) ? 'changeNegative' : 'changePositive';
                $row->CHANGE_CAPTION = ($rec->change < 0 || isset($rec->revertId)) ? tr("За плащане") : tr("Ресто");
                $row->change = $mvc->getFieldType('change')->toVerbal(abs($rec->change));
                $row->changeCurrency = $row->currency;
            } else {
                unset($row->change);
            }
        } else {
            unset($row->paid);
            unset($row->change);
        }

        if (isset($rec->revertId)) {
            if (!isset($fields['-terminal'])) {
                $row->revertId = ($rec->revertId != self::DEFAULT_REVERT_RECEIPT) ? pos_Receipts::getHyperlink($rec->revertId, true) : (!Mode::is('printing') ? ht::createHint(' ', 'Произволна сторнираща бележка', 'warning') : null);
            } else {
                $row->revertId = "<span class='red'>" . tr("Сторно") . "</span>";
            }
        } elseif ($rec->state != 'draft') {
            if (isset($rec->transferredIn)) {
                $row->revertId = tr('Прехвърлена');
            } else {
                $row->revertId = $row->state;
            }
        }

        if ($rec->state == 'rejected') {
            $row->TERMINAL_STATE_CLASS = "rejected-receipt";
            $row->revertId = $row->state;
        }

        // показваме датата на последната модификация на документа, ако е активиран
        if ($rec->state != 'draft') {
            $row->valior = dt::mysql2verbal($rec->modifiedOn, 'd.m.Y H:i:s');
        }

        $row->pointId = pos_Points::getHyperLink($rec->pointId, true);
        $row->time = dt::mysql2verbal(dt::now(), 'H:i');
        $row->productCount = $mvc->getProducts($rec->id, true);

        if (isset($rec->contragentLocationId)) {
            $row->contragentLocationId = crm_Locations::getHyperlink($rec->contragentLocationId);
        }
    }


    /**
     * След подготовка на тулбара на единичен изглед.
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        if ($mvc->haveRightFor('terminal', $data->rec)) {
            $data->toolbar->addBtn('Терминал', array('pos_Terminal', 'open', 'receiptId' => $data->rec->id, 'force' => true, 'ret_url' => true), 'ef_icon=img/16/forward16.png, order=18,target=_blank');
        }

        if ($mvc->haveRightFor('manualpending', $data->rec)) {
            $data->toolbar->addBtn('Чакащо (Ръчно)', array($mvc, 'manualpending', 'id' => $data->rec->id, 'ret_url' => true), 'ef_icon=img/16/tick-circle-frame.png,warning=Наистина ли желаете ръчно да направите бележката чакаща|*?');
        }

        if(cash_NonCashPaymentDetails::haveRightFor('list')){
            if(cash_NonCashPaymentDetails::count("#classId = {$mvc->getClassId()} AND #objectId = {$data->rec->id}")){
                $data->toolbar->addBtn('Безналични', array('cash_NonCashPaymentDetails', 'list', 'classId' => $mvc->getClassId(), 'objectId' => $data->rec->id), "ef_icon=img/16/bug.png,title=Безналичните плащания към документа,row=2");
            }
        }
    }


    /**
     * След подготовката на туулбара на списъчния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        // Подменяме бутона за добавяне с такъв сочещ към терминала
        if (!empty($data->toolbar->buttons['btnAdd'])) {
            $data->toolbar->removeBtn('btnAdd');
            $data->toolbar->addBtn('Терминал', array($mvc, 'new'), 'id=btnAdd', 'ef_icon = img/16/forward16.png,title=Създаване на нова бележка');
        }
    }


    /**
     * Извлича информацията за всички продукти които са продадени чрез тази бележки, във вид подходящ за контирането
     *
     * @param int $id        - ид на бележката
     * @param boolean $count - дали да е само броя
     * @return mixed $products - Масив от продукти
     */
    public static function getProducts($id, $count = false)
    {
        expect(static::fetch($id), 'Несъществуваща бележка');

        $query = pos_ReceiptDetails::getQuery();
        $query->where("#receiptId = {$id}");
        $query->where('#quantity != 0');
        $query->where("#action LIKE '%sale%'");
        $query->orderBy('id', 'ASC');

        if ($count) return $query->count();

        $products = array();
        while ($rec = $query->fetch()) {
            $packRec = cat_products_Packagings::getPack($rec->productId, $rec->value);
            $quantityInPack = is_object($packRec) ? $packRec->quantity : 1;

            $products[$rec->id] = (object)array('productId' => $rec->productId,
                'price' => $rec->price / $quantityInPack,
                'packagingId' => $rec->value,
                'text' => $rec->text,
                'vatPrice' => $rec->price * $rec->param,
                'discount' => $rec->discountPercent,
                'autoDiscount' => $rec->autoDiscount,
                'batch' => $rec->batch,
                'id' => $rec->id,
                'quantity' => $rec->quantity);
        }

        return $products;
    }


    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     *
     * @return int $id ид-то на обновения запис
     */
    public function updateMaster_($id)
    {
        $rec = $this->fetchRec($id);
        if (empty($rec)) return;
        core_Debug::startTimer('UPDATE_RECEIPT');

        $rec->change = $rec->total = $rec->paid = 0;
        $dQuery = $this->pos_ReceiptDetails->getQuery();
        $dQuery->where("#receiptId = {$rec->id}");

        while ($dRec = $dQuery->fetch()) {
            $action = explode('|', $dRec->action);
            switch ($action[0]) {
                case 'sale':
                    $discount = $dRec->discountPercent;
                    if ($rec->state == 'draft') {
                        if (isset($dRec->autoDiscount)) {
                            $discount = round((1 - (1 - $dRec->discountPercent) * (1 - $dRec->autoDiscount)), 4);
                        }
                    }
                    $price = $this->getDisplayPrice($dRec->price, $dRec->param, $discount, $rec->pointId, $dRec->quantity);
                    $rec->total += round($dRec->quantity * $price, 2);
                    break;
                case 'payment':
                    $paidAmount = $dRec->amount;
                    if ($action[1] != '-1') {
                        $paidAmount = cond_Payments::toBaseCurrency($action[1], $paidAmount, $rec->valior);
                    }

                    $rec->paid += $paidAmount;
                    $rec->change += $dRec->value;
                    break;
            }
        }

        $diff = round($rec->paid - $rec->total, 2);
        $rec->change = $diff;
        $rec->total = $rec->total;
        $this->save($rec);

        core_Debug::stopTimer('UPDATE_RECEIPT');
        core_Debug::log("END UPDATE_RECEIPT " . round(core_Debug::$timers["UPDATE_RECEIPT"]->workingTime, 6));
    }


    /**
     * Обновява мастъра
     *
     * @param mixed $id - ид/запис на мастъра
     */
    public static function on_AfterUpdateMaster($mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);

        // Ако не е чернова или е сторнираща - няма да се преизчислява нищо
        if ($rec->state != 'draft' || !empty($rec->revertId)) return;

        // Преизчисляване на общите отстъпки
        core_Debug::startTimer('CALC_AUTO_DISCOUNT');
        static::recalcAutoDiscount($rec);
        core_Debug::stopTimer('CALC_AUTO_DISCOUNT');
        $uTime = round(core_Debug::$timers["CALC_AUTO_DISCOUNT"]->workingTime, 6);
        core_Debug::log("END CALC_AUTO_DISCOUNT: '{$uTime}'");
        $mvc->logDebug("POS AUTO_CALC_DISC: '{$uTime}'", $rec->id);

        $mvc->updateMaster_($rec);
    }


    /**
     *  След подготовка на лист филтъра
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        pos_Points::addPointFilter($data->listFilter, $data->query);
        $filterDateFld = $data->listFilter->rec->filterDateField;
        $data->listFilter->FLD('revertState', 'enum(,no=Без сторниране,revertId=Сторниращи,isReverted=Сторнирани)', 'caption=Сторно');

        // Добавяне на филтър по начините на плащане
        $paymentOptions = array();
        $pQuery = cond_Payments::getQuery();
        $pQuery->where("#state = 'active'");
        $cardPaymentId = cond_Setup::get('CARD_PAYMENT_METHOD_ID');

        $devices = peripheral_Devices::getDevices('bank_interface_POS', false);
        while ($pRec = $pQuery->fetch()) {
            $paymentName = cond_Payments::getTitleById($pRec->id, false);
            $paymentOptions[$pRec->id] = $paymentName;
            if ($pRec->id == $cardPaymentId) {
                $paymentOptions["{$pRec->id}|card|"] = "Карта [Потв.]";
                $paymentOptions["{$pRec->id}|manual|"] = "Карта [Ръчно потв.]";
                foreach ($devices as $deviceRec) {
                    $deviceName = cash_NonCashPaymentDetails::getCardPaymentBtnName($deviceRec);
                    $paymentOptions["{$pRec->id}||{$deviceRec->id}"] = "{$deviceName}";
                }
            }
        }
        $data->listFilter->FLD('payment', 'varchar', 'caption=Плащане');
        $data->listFilter->setOptions('payment', array('all' => tr('Всички'), '-1' => tr('В брой')) + $paymentOptions);
        $data->listFilter->showFields .= ',payment,revertState';
        $data->listFilter->setDefault('payment', 'all');
        $data->listFilter->input('payment,revertState');
        $data->query->orderBy($filterDateFld, 'DESC');

        // Скриване на полето за дата, ако се филтрира по конкретно поле
        foreach (array('valior', 'createdOn', 'waitingOn') as $fld) {
            if ($fld != $data->listFilter->rec->filterDateField) {
                unset($data->listFields[$fld]);
            }
        }

        if ($filter = $data->listFilter->rec) {
            if ($filter->payment != 'all') {

                // Ако се филтрира по начини на плащане
                $cloneQuery = clone $data->query;
                $cloneQuery->show('id');
                $foundIds = arr::extractValuesFromArray($cloneQuery->fetchAll(), 'id');
                if (countR($foundIds)) {
                    $dQuery = pos_ReceiptDetails::getQuery();
                    if (is_numeric($filter->payment)) {
                        $dQuery->where("#action = 'payment|{$filter->payment}'");
                    } else {
                        list($paymentId, $paymentParam, $deviceId) = explode('|', $filter->payment);
                        $dQuery->where("#action = 'payment|{$paymentId}'");
                        if(!empty($paymentParam)){
                            $dQuery->where("#param = '{$paymentParam}'");
                        }

                        if(!empty($deviceId)){
                            $dQuery->where("#deviceId = '{$deviceId}'");
                        }
                    }
                    $dQuery->in("receiptId", $foundIds);
                    $dQuery->show('receiptId');
                    $receiptIdWithPayments = arr::extractValuesFromArray($dQuery->fetchAll(), 'receiptId');
                    if (countR($receiptIdWithPayments)) {
                        $data->query->in('id', $receiptIdWithPayments);
                    } else {
                        $data->query->where("1=2");
                    }
                }
            }

            // Филтър по сторно състояния
            if (!empty($filter->revertState)) {
                if($filter->revertState == 'no'){
                    $data->query->where("#returnedTotal IS NULL");
                } elseif($filter->revertState == 'isReverted'){
                    $data->query->where("#returnedTotal IS NOT NULL");
                } elseif($filter->revertState == 'revertId'){
                    $data->query->where("#revertId IS NOT NULL");
                }
            }
        }
    }


    /**
     * Модификация на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        // Само черновите бележки могат да се редактират в терминала
        if ($action == 'terminal' && isset($rec)) {
            if (!pos_Points::haveRightFor('select', $rec->pointId)) {
                $res = 'no_one';
            }
        }

        // Никой не може да оттегли затворена бележка
        if ($action == 'reject' && isset($rec)) {
            if (in_array($rec->state, array('closed', 'pending', 'rejected'))) {
                $res = 'no_one';
            } elseif (empty($rec->total)) {
                if (empty($rec->revertId)) {
                    $res = 'no_one';
                }
            }
        }

        // Ако бележката е започната, може да се изтрие
        if ($action == 'delete' && isset($rec)) {
            if ($rec->state != 'draft') {
                $res = 'no_one';
            }
        }

        if (in_array($action, array('delete', 'reject')) && isset($rec)) {
            if (!haveRole('posMaster')) {
                $deviceRec = peripheral_Devices::getDevice('bank_interface_POS');
                if (is_object($deviceRec)) {
                    $paidWithCards = pos_ReceiptDetails::count("#action LIKE '%payment%' AND #receiptId = '{$rec->id}' AND #param = 'card'");
                    if ($paidWithCards) {
                        $res = 'no_one';
                    }
                }
            }
        }

        // Можем да контираме бележки само когато те са чернови и платената
        // сума е по-голяма или равна на общата или общата сума е <= 0
        if ($action == 'close' && isset($rec->id)) {
            $countProducts = pos_ReceiptDetails::count("#receiptId = {$rec->id} AND #action LIKE '%sale%'");
            if (($rec->total == 0 && !$countProducts) || abs(round($rec->paid, 2)) < abs(round($rec->total, 2)) || $rec->state != 'draft') {
                $res = 'no_one';
            }
        }

        // Може ли да бъде направено плащане по бележката
        if ($action == 'pay' && isset($rec)) {
            $countProducts = pos_ReceiptDetails::count("#receiptId = {$rec->id} AND #action LIKE '%sale%'");

            if ($rec->state != 'draft') {
                $res = 'no_one';
            } elseif (!$countProducts) {
                $res = 'no_one';
            }
        }

        // Не може да се прехвърля бележката, ако общото и е нула, има платено или не е чернова
        if ($action == 'transfer' && isset($rec)) {
            if (empty($rec->id) || isset($rec->transferredIn) || ($rec->state == 'draft' && round($rec->paid, 2) > 0) || !in_array($rec->state, array('draft', 'closed', 'waiting'))) {
                $res = 'no_one';
            }
        }

        if (in_array($action, array('setcontragent', 'setvoucher')) && isset($rec)) {
            if (!$mvc->haveRightFor('terminal', $rec) || isset($rec->transferredIn)) {
                $res = 'no_one';
            }

            if(isset($action) == 'setcontragent'){
                if($rec->state == 'closed' && !pos_Receipts::isForDefaultContragent($rec)){
                    $res = 'no_one';
                }
            }
        }

        if ($action == 'setvoucher') {
            if (!core_Packs::isInstalled('voucher') || (isset($rec) && $rec->state != 'draft')) {
                $res = 'no_one';
            }
        }
        if ($action == 'edit' && isset($rec) && $rec->state == 'waiting') {
            $res = 'no_one';
        }

        if ($action == 'revert' && isset($rec) && ($rec != pos_Receipts::DEFAULT_REVERT_RECEIPT)) {
            if (isset($rec->revertId) || (!in_array($rec->state, array('waiting', 'closed'))) || (!empty($rec->returnedTotal) && round($rec->total - $rec->returnedTotal, 2) <= 0) || ($rec->state == 'closed' && isset($rec->transferredIn))) {
                $res = 'no_one';
            }
        }

        if ($action == 'manualpending' && isset($rec)) {
            if ($rec->state != 'draft') {
                $res = 'no_one';
            } else {
                if (empty($rec->total) || round($rec->paid, 2) < round($rec->total, 2)) {
                    $res = 'no_one';
                }
            }
        }
    }


    /**
     * Активира документа и ако е зададено пренасочва към създаването на нова фактура
     */
    public function act_Transfer()
    {
        $this->requireRightFor('transfer');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));

        // Извличаме нужните ни параметри от рекуеста
        expect($contragentClassId = Request::get('contragentClassId', 'int'));
        expect($contragentId = Request::get('contragentId', 'int'));
        expect($contragentClass = cls::get($contragentClassId));
        expect($contragentClass->fetch($contragentId));
        $this->requireRightFor('transfer', $rec);

        // Ако попирнцип бележката не може да се приключи - да не може да се и прехвърля
        if(core_Packs::isInstalled('voucher')){
            $productArr = arr::extractValuesFromArray(pos_Receipts::getProducts($rec->id), 'productId');
            if($error = voucher_Cards::getContoErrors($rec->voucherId, $productArr, $this->getClassId(), $rec->id)){

                return new redirect(array('pos_Terminal', 'open', 'receiptId' => $rec->id), $error, 'error');
            }
        }

        // Подготвяме масива с данните на новата продажба, подаваме склада и касата на точката
        $posRec = pos_Points::fetch($rec->pointId);
        $settings = pos_Points::getSettings($rec->pointId);
        $fields = array('shipmentStoreId' => $posRec->storeId, 'caseId' => $posRec->caseId, 'receiptId' => $rec->id, 'deliveryLocationId' => $rec->contragentLocationId);
        $fields['vatExceptionId'] = $settings->vatExceptionId;
        $hasVoucher = isset($rec->voucherId) && core_Packs::isInstalled('voucher');

        if($hasVoucher){
            $fields['voucherId'] = $rec->voucherId;
            $endVoucher = substr(voucher_Cards::getVerbal($rec->voucherId, 'number'), 12, 4);
            $fields['note'] = tr("Ваучер") . ": *{$endVoucher}";
        }

        // Намираме продуктите на бележката (трябва да има поне един)
        $products = $this->getProducts($rec->id);

        // Опитваме се да създадем чернова на нова продажба породена от бележката
        if ($sId = sales_Sales::createNewDraft($contragentClassId, $contragentId, $fields)) {
            if($hasVoucher){
                voucher_Cards::mark($rec->voucherId, true, sales_Sales::getClassId(), $sId);
            }

            sales_Sales::logWrite('Прехвърлена от POS продажба', $sId);

            // Всеки продукт се прехвърля едно към 1
            foreach ($products as &$product) {
                if ($product->discount < 0) {
                    $product->price *= (1 + abs($product->discount));
                    $product->discount = null;
                }

                $product->transferedIn = sales_Sales::addRow($sId, $product->productId, $product->quantity, $product->price, $product->packagingId, $product->discount, null, null, $product->text, $product->batch, $product->autoDiscount);
            }
        }

        // Отбелязване къде е прехвърлена бележката
        $rec->transferredIn = $sId;
        $exState = $rec->state;
        $rec->state = 'closed';

        $this->save($rec);
        $this->logInAct('Прехвърляне на бележка', $rec->id);
        if(countR($products)){
            cls::get('pos_ReceiptDetails')->saveArray($products, 'id,transferedIn');
        }

        if ($exState == 'draft') {
            Mode::push('calcAutoDiscounts', false);
            cls::get('sales_Sales')->flushUpdateQueue($sId);
            Mode::pop('calcAutoDiscounts');
        } else {
            // Продажбата се активира, ако ПОС бележката е приключена
            $saleRec = sales_Sales::fetchRec($sId);
            if($exState == 'waiting'){
                $saleRec->contoActions = 'activate,pay,ship';
            } else {
                $saleRec->contoActions = 'activate';
            }

            $saleRec->isContable = 'activate';
            sales_Sales::save($saleRec);
            sales_Sales::conto($saleRec->id);
            if($exState == 'closed'){
                $saleRec->contoActions = 'activate,pay,ship';
                cls::get('sales_Sales')->save($saleRec, 'contoActions');
            }

            Mode::push('calcAutoDiscounts', false);
            cls::get('sales_Sales')->updateMaster($saleRec->id);
            Mode::pop('calcAutoDiscounts');
            sales_Sales::logWrite('Активиране на прехвърлена от POS продажба', $sId);
        }

        // Споделяме потребителя към нишката на създадената продажба
        $cu = core_Users::getCurrent();
        $sRec = sales_Sales::fetch($sId);
        doc_ThreadUsers::addShared($sRec->threadId, $sRec->containerId, $cu);
        Mode::setPermanent("currentOperation{$rec->id}", 'receipts');
        Mode::setPermanent("currentSearchString{$rec->id}", null);

        // Редирект към новата продажба
        return new Redirect(array('sales_Sales', 'single', $sId), 'Успешно прехвърляне на бележката|*!');
    }


    /**
     * Проверка на количеството
     *
     * @param stdClass $rec
     * @param string $error
     * @param string|null $warning
     *
     * @return bool
     */
    public static function checkQuantity($rec, &$error, &$warning = null)
    {
        // Ако е забранено продаването на неналични артикули да се проверява
        if (store_Setup::canDoShippingWhenStockIsNegative()) return true;

        $instantBomRec = cat_Products::getLastActiveBom($rec->productId, 'instant');
        if(is_object($instantBomRec)) return true;

        $today = dt::today();
        $pRec = cat_products_Packagings::getPack($rec->productId, $rec->value);
        $stRec = store_Products::getQuantities($rec->productId, $rec->storeId, $today);
        $freeQuantityNow = $stRec->free;
        $quantityInStock = $stRec->quantity;
        $freeQuantity = store_Products::getQuantities($rec->productId, $rec->storeId)->free;

        // Ако има положителна наличност
        if (core_Packs::isInstalled('batch') && $quantityInStock > 0) {
            if ($BatchDef = batch_Defs::getBatchDef($rec->productId)) {
                if (!empty($rec->batch)) {

                    // И е подадена конкретна партида, взима се нейното количество
                    $quantityInStock = batch_Items::getQuantity($rec->productId, $rec->batch, $rec->storeId);
                } else {

                    // Ако е без партида но има партидност, гледа се колко има в склада, които са без партида
                    $batchesIn = batch_Items::getBatchQuantitiesInStore($rec->productId, $rec->storeId);
                    $quantityOnBatches = 0;
                    array_walk($batchesIn, function ($a) use (&$quantityOnBatches) {
                        if ($a > 0) {
                            $quantityOnBatches += $a;
                        }
                    });
                    $quantityInStock = round($quantityInStock - $quantityOnBatches, 4);
                }
            }
        }

        $originalFreeQuantityNow = $freeQuantityNow;
        $originalFreeQuantity = $freeQuantity;

        $quantityInPack = ($pRec) ? $pRec->quantity : 1;
        $freeQuantity -= round($rec->quantity * $quantityInPack, 3);
        $freeQuantityNow -= round($rec->quantity * $quantityInPack, 3);

        $freeQuantityNow = round($freeQuantityNow, 3);
        $freeQuantity = round($freeQuantity, 3);
        $Double = core_Type::getByName('double(smartRound)');
        $pName = cat_Products::getTitleById($rec->productId);

        if ($freeQuantity < 0) {
            $originalFreeQuantityVerbal = $Double->toVerbal($originalFreeQuantity / $quantityInPack);
            $error = "|* {$pName}: Количеството e над минималното разполагаемото|* <b>{$originalFreeQuantityVerbal}</b> |в склад|*: " . store_Stores::getTitleById($rec->storeId);

            return false;
        }

        if ($freeQuantityNow < 0) {
            $originalFreeQuantityNowVerbal = $Double->toVerbal($originalFreeQuantityNow);
            $warning = "|* {$pName}: Количеството e над разполагаемото|* <b>{$originalFreeQuantityNowVerbal}</b> |днес в склад|*: " . store_Stores::getTitleById($rec->storeId);

            return true;
        }

        return true;
    }


    /**
     * Активира документа и ако е зададено пренасочва към създаването на нова фактура
     */
    public function act_Close()
    {
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        if ($rec->state != 'draft') {

            // Създаване на нова чернова бележка
            return new Redirect(array($this, 'new'));
        }

        $this->requireRightFor('close', $rec);

        // Ако е инсталиран пакета за ваучери, но не е инсталиран bgfisc да се провери за референт
        if(core_Packs::isInstalled('voucher') && !core_Packs::isInstalled('bgfisc')){
            $productArr = arr::extractValuesFromArray(pos_Receipts::getProducts($rec->id), 'productId');
            if($error = voucher_Cards::getContoErrors($rec->voucherId, $productArr, $this->getClassId(), $rec->id)){

                followRetUrl(null, $error, 'error');
            }
        }

        // Ако е сторно бележка, проверява се може ли да се контира
        if (isset($rec->revertId)) {
            $error = null;
            if (!static::canCloseRevertReceipt($rec, $error)) {

                followRetUrl(null, $error, 'error');
            }
            $revertedRec = $this->fetchRec($rec->revertId);
            if(isset($revertedRec->voucherId) && core_Packs::isInstalled('voucher')){
                voucher_Cards::mark($revertedRec->voucherId, false);
                core_Statuses::newStatus('Ваучерът е освободен|*!');
            }
        }

        $this->markAsWaiting($rec);

        $autoDiscCacheKey = core_Permanent::get("autoDiscCache|{$this->className}|{$rec->id}");
        if(!empty($autoDiscCacheKey)){
            core_Cache::remove($this->className, $autoDiscCacheKey);
        }
        core_Permanent::remove("autoDiscCache|{$this->className}|{$rec->id}");

        // Създаване на нова чернова бележка
        return new Redirect(array($this, 'new'));
    }


    /**
     * Ръчно маркира бележката като чакаща
     *
     * @param stdClass $rec
     * @return void
     */
    private function markAsWaiting($rec)
    {
        $rec->state = 'waiting';
        $rec->waitingOn = dt::now();
        $rec->waitingBy = core_Users::getCurrent();
        $rec->__closed = true;

        if ($this->save($rec)) {
            if (isset($rec->revertId) && $rec->revertId != static::DEFAULT_REVERT_RECEIPT) {
                $this->calcRevertedTotal($rec->revertId);
            }

            // Кеширане на отстъпките
            $dRecs = array();
            $Details = cls::get('pos_ReceiptDetails');
            $dQuery = $Details->getQuery();
            $dQuery->where("#receiptId = {$rec->id} AND #action LIKE '%sale%'");
            while ($dRec = $dQuery->fetch()) {
                $dRec->inputDiscount = $dRec->discountPercent;
                if (isset($dRec->autoDiscount)) {
                    if (isset($dRec->discountPercent)) {
                        $dRec->discountPercent = round((1 - (1 - $dRec->discountPercent) * (1 - $dRec->autoDiscount)), 4);
                    } else {
                        $dRec->discountPercent = $dRec->autoDiscount;
                    }
                }
                $dRecs[] = $dRec;
            }

            cls::get('pos_ReceiptDetails')->saveArray($dRecs, 'id,discountPercent,inputDiscount');
            $this->logInAct('Приключване на бележка', $rec->id);

            // Записване и на безналичните плащания в другия модел регистър
            $nonCashPayments = array();
            $dQuery = $Details->getQuery();
            $dQuery->where("#receiptId = {$rec->id} AND #action  LIKE '%payment%'");
            while ($dRec = $dQuery->fetch()) {
                list(, $paymentId) = explode('|', $dRec->action);
                if($paymentId > 0){
                    $key = "{$paymentId}|{$dRec->deviceId}|{$dRec->param}";
                    if(!array_key_exists($key, $nonCashPayments)){
                        $nonCashPayments[$key] = (object)array('classId' => $this->getClassId(), 'objectId' => $rec->id, 'paymentId' => $paymentId, 'amount' => 0, 'deviceId' => $dRec->deviceId, 'param' => $dRec->param);
                    }
                    $nonCashPayments[$key]->amount += $dRec->amount;
                }
            }

            cls::get('cash_NonCashPaymentDetails')->saveArray($nonCashPayments);

            // Нотифициране на драйвера на артикулите, че той е включен в чакаща бележка
            $Products = cls::get('cat_Products');
            foreach ($dRecs as $dRec1) {
                $Driver = cat_Products::getDriver($dRec1->productId);
                $Driver->invoke('AfterDocumentInWhichIsUsedHasChangedState', array($Products, $dRec1->productId, $this, $rec->id, $Details, $dRec1->id, 'waiting'));
            }

            if(isset($rec->voucherId) && core_Packs::isInstalled('voucher')){

                // Ако е сторно ваучерът се освобождава, ако не е се маркира като използван
                if(isset($rec->revertId)){
                    voucher_Cards::mark($rec->voucherId, false);
                } else {
                    voucher_Cards::mark($rec->voucherId, true, $this->getClassId(), $rec->id, true);
                }
            }
        }
    }


    /**
     * Показва краткия номер на бележката, съгласно настройките на пакета
     *
     * @param int $id
     *
     * @return string $num
     */
    public static function getReceiptShortNum($id)
    {
        $conf = core_Packs::getConfig('pos');
        $num = substr($id, -1 * $conf->POS_SHOW_RECEIPT_DIGITS);
        if (strlen($id) > strlen($num)) {
            $num = "*{$num}";
        }

        return $num;
    }


    /**
     * Подготвя чакащите бележки в сингъла на точката на продажба
     *
     * @param stdClass $data
     *
     * @return void
     */
    public function prepareReceipts(&$data)
    {
        $data->rows = array();
        $data->Pager = cls::get('core_Pager', array('itemsPerPage' => 20));
        $data->count = 0;

        $query = $this->getQuery();
        $query->where("#pointId = {$data->masterId}");
        $query->where("#state = 'waiting' OR #state = 'draft'");
        $query->orderBy('#state=ASC,id=DESC');
        if ($count = $query->count()) {
            $data->count = core_Type::getByName('int')->toVerbal($count);
        }

        $baseCurrencyCode = acc_Periods::getBaseCurrencyCode();

        $fields = $this->selectFields();
        $fields['-list'] = true;
        $data->listFields = arr::make("num=Бележка,productCount=Артикули,contragentId=Клиент,total=Сума");

        $data->Pager->setLimit($query);
        while ($rec = $query->fetch()) {
            $data->rows[$rec->id] = $this->recToVerbal($rec, $fields);
            $num = self::getRecTitle($rec);
            if (!Mode::isReadOnly()) {
                if ($this->haveRightFor('terminal', $rec)) {
                    $num = ht::createLink($num, array('pos_Terminal', 'open', 'receiptId' => $rec->id, 'force' => true), false, 'title=Довършване на бележката,ef_icon=img/16/cash-register.png');
                } elseif ($this->haveRightFor('single', $rec)) {
                    $num = ht::createLink($num, array($this, 'single', $rec->id), false, "title=Отваряне на бележка №{$rec->id},ef_icon=img/16/view.png");
                }
            }

            $data->rows[$rec->id]->total = ht::styleNumber($data->rows[$rec->id]->total, $rec->total);
            $data->rows[$rec->id]->total .= " <span class='cCode'>{$baseCurrencyCode}</span>";
            $data->rows[$rec->id]->num = $num;
        }
    }


    /**
     * Рендиране на чакащите бележки в сингъла на точката на продажба
     *
     * @param stdClass $data
     *
     * @return core_ET $tpl
     */
    public function renderReceipts($data)
    {
        $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        $tpl->append(tr('Чакащи бележки') . " ({$data->count})", 'title');
        $fieldset = new core_FieldSet();

        $fieldset->FLD('num', 'varchar', 'tdClass=leftCol');
        $fieldset->FLD('contragentId', 'varchar', 'tdClass=leftCol');
        $fieldset->FLD('total', 'double', 'smartcenter');

        $table = cls::get('core_TableView', array('mvc' => $fieldset));
        $this->invoke('BeforeRenderListTable', array($tpl, &$data));
        $table->tableClass = 'listTable receiptsInSingle';
        $details = $table->get($data->rows, $data->listFields);

        $tpl->append($details, 'content');
        if (isset($data->Pager)) {
            $tpl->append($data->Pager->getHtml(), 'content');
        }

        return $tpl;
    }


    /**
     * Преди изтриване
     */
    protected static function on_AfterDelete($mvc, &$numRows, $query, $cond)
    {
        foreach ($query->getDeletedRecs() as $rec) {
            self::logDebug("Изтриване на бележка: {$rec->id}");
            wp('Изтриване на бележка', $rec);
            pos_ReceiptDetails::delete("#receiptId = {$rec->id}");

            if(isset($rec->voucherId) && core_Packs::isInstalled('voucher')){
                voucher_Cards::mark($rec->voucherId, false);
                core_Statuses::newStatus('Ваучерът е освободен|*!');
            }
        }
    }


    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        $valiorVerbal = self::getVerbal($rec, 'valior');
        $pointIdVerbal = self::getVerbal($rec, 'pointId');
        $title = "{$pointIdVerbal}/{$rec->id}/{$valiorVerbal}";

        if (isset($rec->revertId)) {
            $title = ht::createHint($title, 'Сторно бележка');
            $title->prepend("<span class='red'>");
            $title->append("</span>");
        }

        return $title;
    }


    /**
     * Екшън за започване на действие за сторниране на бележка
     */
    public function act_Revert()
    {
        $this->requireRightFor('revert');
        expect($id = Request::get('id', 'int'));
        $this->requireRightFor('revert', $id);

        $newReceiptId = $this->createNew($id);
        $this->logWrite('Създаване на сторнираща бележка', $id);

        Mode::setPermanent("currentOperation{$newReceiptId}", 'add');
        Mode::setPermanent("currentSearchString{$newReceiptId}", null);

        return new Redirect(array('pos_Terminal', 'open', "receiptId" => $newReceiptId));
    }


    /**
     * Опит за намиране на ПОС бележка по даден стринг
     */
    protected function on_AfterFindReceiptByNumber($mvc, &$res, $string, $forRevert = false)
    {
        if (!isset($res['rec']) && empty($res['notFoundError'])) {
            if (type_Int::isInt($string)) {
                $res['rec'] = self::fetch($string);
                if (!is_object($res['rec'])) {
                    $res['notFoundError'] = "|Не е намерена бележка от номер|* '<b>{$string}</b>'!";
                    $res['rec'] = false;
                }
            }
        }

        if (is_object($res['rec'])) {
            if ($forRevert === true) {
                if (self::fetchField("#id = {$res['rec']->id} AND #revertId IS NOT NULL")) {
                    $res['notFoundError'] = '|Не може да сторнирате сторнираща бележка|*!';
                    $res['rec'] = false;
                }
            }
        }
    }


    /**
     * Обработване на цената
     */
    protected function on_AfterGetDisplayPrice($mvc, &$res, $priceWithoutVat, $vat, $discountPercent, $pointId, $quantity)
    {
        $quantity = !empty($quantity) ? $quantity : 1;

        if (empty($res)) {
            $res = $priceWithoutVat * $quantity * (1 + $vat);
            if (!empty($discountPercent)) {
                $res *= (1 - $discountPercent);
            }
            $res /= $quantity;

            $res = round($res, 2);
        }
    }


    /**
     * Сменя контрагента на бележката и преизчислява цените
     *
     * @param stdClass $rec
     * @param int $contragentClassId
     * @param int $contragentId
     * @param int|null $locationId
     * @return void
     */
    public static function setContragent(&$rec, $contragentClassId, $contragentId, $locationId = null)
    {
        core_Debug::startTimer("SET_RECEIPT_CONTRAGENT");
        $rec->contragentClass = $contragentClassId;
        $rec->contragentObjectId = $contragentId;
        $rec->contragentName = cls::get($rec->contragentClass)->getVerbal($rec->contragentObjectId, 'name');
        $rec->contragentLocationId = $locationId;
        static::save($rec, 'contragentObjectId,contragentClass,contragentName,contragentLocationId');
        $isDefaultContragent = pos_Receipts::isForDefaultContragent($rec);
        static::recalcPricesInDetail($rec, $isDefaultContragent);

        core_Debug::stopTimer("SET_RECEIPT_CONTRAGENT");
    }


    /**
     * Преизчислява цените в бележката спрямо актуалната ЦП за нея
     *
     * @param stdClass $rec             - ид на запис
     * @param bool $isDefaultContragent - дали е сменено на дефолтния контрагент
     * @param bool $force               - форсиране на преизчисляването
     * @return void
     */
    public static function recalcPricesInDetail($rec, $isDefaultContragent = false, $force = false)
    {
        // Ако има детайли
        $Policy = cls::get('price_ListToCustomers');
        $dQuery = pos_ReceiptDetails::getQuery();
        $dQuery->where("#action = 'sale|code' AND #receiptId = {$rec->id}");
        $discountPolicyId = pos_Points::getSettings($rec->pointId, 'discountPolicyId');
        $listId = $rec->policyId;
        $pointPolicyId = pos_Points::getSettings($rec->pointId, 'policyId');
        if(!$listId){
            $listId = $isDefaultContragent ?  $pointPolicyId : null;
        }

        $now = dt::now();
        while($dRec = $dQuery->fetch()){

            // Обновява им се цената по текущата политика, ако може
            $packRec = cat_products_Packagings::getPack($dRec->productId, $dRec->value);
            $perPack = (is_object($packRec)) ? $packRec->quantity : 1;

            $price = $Policy->getPriceInfo($rec->contragentClass, $rec->contragentObjectId, $dRec->productId, $dRec->value, 1, $now, 1, 'no', $listId, false);
            if(empty($price->price)) {
                if($force && $pointPolicyId != $listId){
                    // Ако няма цена по клиентската политика при форсиране се гледа в краен случай цената от ПОС-а
                    $price = $Policy->getPriceInfo($rec->contragentClass, $rec->contragentObjectId, $dRec->productId, $dRec->value, 1, $now, 1, 'no', $pointPolicyId, false);
                    if(empty($price->price)) continue;
                } else {
                    continue;
                }
            }
            $oldPrice = (!empty($dRec->discountPercent)) ? ($dRec->price * (1 - $dRec->discountPercent)) : $dRec->price;

            $finalPrice = (!empty($price->discount)) ? ($price->price * (1 - $price->discount)) : $price->price;
            $finalPrice *= $perPack;

            if($force || $isDefaultContragent || round($oldPrice, 5) > round($finalPrice, 5)){
                $discount = $price->discount;
                $price = $price->price;

                if(!empty($discountPolicyId)){
                    $priceOnDiscountListRec = $Policy->getPriceInfo($rec->contragentClass, $rec->contragentObjectId, $dRec->productId, $dRec->value, 1, $now, 1, 'no', $discountPolicyId, false);

                    if(isset($priceOnDiscountListRec->price)){
                        $comparePrice = (!empty($priceOnDiscountListRec->discount)) ? ($priceOnDiscountListRec->price * (1 - $priceOnDiscountListRec->discount)) : $priceOnDiscountListRec->price;
                        $comparePrice *= $perPack;

                        $disc = ($finalPrice - $comparePrice) / $comparePrice;
                        $discountCalced = round(-1 * $disc, 6);
                        if ($discountCalced > 0.01) {
                            // Подменяме цената за да може като се приспадне отстъпката и, да се получи толкова колкото тя е била
                            $discount = round(-1 * $disc, 6);
                            $price = $comparePrice / $perPack;
                        }
                    }
                }

                $dRec->autoDiscount = null;
                $dRec->price = $price * $perPack;
                $dRec->amount = $dRec->price * $dRec->quantity;
                $dRec->discountPercent = $discount;
                pos_ReceiptDetails::save($dRec, 'price,amount,discountPercent,autoDiscount');
            }
        }
    }


    /**
     * Екшън задаващ контрагент на бележката
     */
    public function act_setvoucher()
    {
        $this->requireRightFor('setvoucher');
        expect($id = Request::get('id'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('setvoucher', $rec);
        $voucherId = Request::get('voucherId', 'int');

        $paidWith = pos_ReceiptDetails::count("#action LIKE '%payment%' AND #receiptId = '{$rec->id}'");
        $selectedRec = null;

        if(isset($rec->revertId)){
            core_Statuses::newStatus('Не може да се добави ваучър в сторно бележка|*!', 'error');
        } else {
            if(core_Packs::isInstalled('voucher')){
                if($voucherId){
                    $rec->voucherId = $voucherId;
                    $voucherRec = voucher_Cards::fetch($voucherId);
                    $rec->policyId = voucher_Types::fetchField($voucherRec->typeId, 'priceListId');
                    core_Statuses::newStatus("Ваучерът е добавен|*!");
                    voucher_Cards::mark($rec->voucherId, true, $this->getClassId(), $rec->id);
                } else {
                    voucher_Cards::mark($rec->voucherId, false);
                    $rec->voucherId = null;
                    $rec->policyId = null;
                    core_Statuses::newStatus("Ваучерът е премахнат|*!");
                }
            }

            $this->save($rec);
            static::recalcPricesInDetail($rec, false, true);

            $this->logWrite('Задаване на ваучер', $id);

            $operation = $paidWith ? 'payment' : 'add';
            $sign = $paidWith ? '!=' : '=';
            Mode::setPermanent("currentOperation{$rec->id}", $operation);
            Mode::setPermanent("currentSearchString{$rec->id}", null);

            $query = pos_ReceiptDetails::getQuery();
            $query->where("#receiptId = {$rec->id} AND #action {$sign} 'sale|code'");
            $query->orderBy('id', 'ASC');
            if($firstRec = $query->fetch()){
                $selectedRec = $firstRec;
            }
        }

        if (Request::get('ajax_mode')) {
            return pos_Terminal::returnAjaxResponse($id, $selectedRec, true, true, true, true, 'add', true);
        }

        followRetUrl();
    }


    /**
     * Екшън задаващ контрагент на бележката
     */
    public function act_setcontragent()
    {
        $this->requireRightFor('setcontragent');
        expect($id = Request::get('id'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('setcontragent', $rec);
        expect($contragentClassId = Request::get('contragentClassId', 'int'));
        expect($contragentId = Request::get('contragentId', 'int'));
        $locationId = Request::get('locationId', 'int');
        $autoSelect = Request::get('autoSelect');

        // Ако се прави опит за избор на същия контрагент не се прави нищо
        if($rec->contragentClass == $contragentClassId && $rec->contragentObjectId == $contragentId){
            $msg = 'Бележката е вече на този клиент';
            if($rec->contragentLocationId != $locationId){
                $msg = 'Локацията е сменена';
                $rec->contragentLocationId = $locationId;
                $this->save($rec, 'contragentLocationId');
            }

            if (!Request::get('ajax_mode')) followRetUrl(null, $msg);
            core_Statuses::newStatus($msg);

            return pos_Terminal::returnAjaxResponse($id, null, true, false, false, false, 'add', false);
        }

        $isDefaultContragent = pos_Receipts::isForDefaultContragent($rec);

        // Ако бележката е на клиент и е сканирана нова карта и тя не е на този клиент ще се върне на анонимния за да се преизчислят цените
        if(!$isDefaultContragent && $autoSelect && ($rec->contragentClass != $contragentClassId || $rec->contragentObjectId != $contragentId)){
            $defaultContragentId = pos_Points::defaultContragent($rec->pointId);
            static::setContragent($rec, crm_Persons::getClassId(), $defaultContragentId);
        }

        // Задаване на новия контрагент
        static::setContragent($rec, $contragentClassId, $contragentId, $locationId);
        $this->logWrite('Избор на контрагент в бележка', $id);

        $currentOperation = in_array($rec->state, array('waiting', 'closed')) ? 'contragent' : 'add';
        Mode::setPermanent("currentOperation{$rec->id}", $currentOperation);
        Mode::setPermanent("currentSearchString{$rec->id}", null);

        if (Request::get('ajax_mode')) {
            return pos_Terminal::returnAjaxResponse($id, null, true, true, true, true, 'add', true);
        }

        followRetUrl();
    }
    
    
    /**
     * Последните записи от потребителския лог в четим вид
     * 
     * @param int $id
     * @param string $type
     * @param int|null $limit
     * 
     * @return stdClass
     */
    public static function getLastUserActionsVerbal($id, $type = 'write', $limit = null)
    {
        $rows = array();
        $rec = static::fetchRec($id);
        $actions = log_Data::getObjectRecs(get_called_class(), $rec->id, $type, null, $limit);
        foreach ($actions as $aRec){
            $rows[] = (object)array('action' => log_Actions::getActionFromCrc($aRec->actionCrc), 
                                    'time' => dt::mysql2verbal(dt::timestamp2Mysql($aRec->time)), 
                                    'userId' => crm_Profiles::createLink($aRec->userId));
        }
        
        return $rows;
    }
    
    
    /**
     * Калкулира, колко върнато по-бележката досега
     * 
     * @param int $id
     */
    private function calcRevertedTotal($id)
    {
        $rec = $this->fetch($id);
        
        $query = pos_Receipts::getQuery();
        $query->where("#revertId = {$rec->id} AND #state NOT IN ('draft', 'rejected')");
        $query->XPR('returnedTotalCalc', 'double', 'SUM(#total)');
        $query->show('returnedTotalCalc');
        $tRec = $query->fetch();
        
        $rec->returnedTotal = ($tRec->returnedTotalCalc) ? -1 * $tRec->returnedTotalCalc : null;
        $this->save_($rec, 'returnedTotal');
    }
    
    
    /**
     * Може ли да се приключи сторниращата бележка ?
     * 
     * @param mixed $rec
     * @param null|string $error
     * @return boolean
     */
    public static function canCloseRevertReceipt($rec, &$error = null)
    {
        $rec = static::fetchRec($rec);
        if($rec->revertId != static::DEFAULT_REVERT_RECEIPT){
            expect($toRevertRec = static::fetch($rec->revertId));
            $rest = round(($toRevertRec->total - $toRevertRec->returnedTotal), 2);
            
            if(round(abs($rec->total), 2) > $rest){
                $restVerbal = core_Type::getByName('double(decimals=2)')->toVerbal($rest);
                $error = "Не може да се сторнира по-голяма сума от очакваната|* <b>{$restVerbal}</b> !";
                return false;
            }
        }
        
        return true;
    }
    
    
    /**
     * Подготовка на рейтингите за продажба на артикулите
     * @see sales_RatingsSourceIntf
     *
     * @return array $res - масив с обекти за върнатите данни
     *                 o objectClassId - ид на клас на обект
     *                 o objectId      - ид на обект
     *                 o classId       - текущия клас
     *                 o key           - ключ
     *                 o value         - стойност
     */
    public function getSaleRatingsData()
    {
        $time = pos_Setup::get('RATINGS_DATA_FOR_THE_LAST');
        $valiorFrom = dt::verbal2mysql(dt::addSecs(-1 * $time), false);
        
        // За всяка бележка, намират се най-продаваните 100 артикула
        $receiptQuery = pos_ReceiptDetails::getQuery();
        $receiptQuery->EXT('state', 'pos_Receipts', 'externalName=state,externalKey=receiptId');
        $receiptQuery->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');
        $receiptQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
        $receiptQuery->EXT('pointId', 'pos_Receipts', 'externalName=pointId,externalKey=receiptId');
        $receiptQuery->EXT('valior', 'pos_Receipts', 'externalName=valior,externalKey=receiptId');
        $receiptQuery->EXT('revertId', 'pos_Receipts', 'externalName=revertId,externalKey=receiptId');
        $receiptQuery->where("#state != 'draft' && #state != 'rejected' AND #revertId IS NULL AND #isPublic = 'yes' AND #valior >= '{$valiorFrom}' AND #productId IS NOT NULL");
        $receiptQuery->show('productId,pointId,valior');
        
        $count = $receiptQuery->count();
        core_App::setTimeLimit($count * 0.4, false, 200);
        $classId = $this->getClassId();
        $objectClassId = cat_Products::getClassId();
        
        $res = array();
        while ($receiptRec = $receiptQuery->fetch()){
            $storeId = pos_Points::fetchField($receiptRec->pointId, 'storeId');
            $index = "{$receiptRec->productId}|{$storeId}";
            
            $monthsBetween = countR(dt::getMonthsBetween($receiptRec->valior));
            $rating = round(12 / $monthsBetween);
            $rating = 100 * $rating;
            
            sales_ProductRatings::addRatingToObject($res, $index, $classId, $objectClassId, $receiptRec->productId, $storeId, $rating);
        }
        
        // Ако има артикули в бележките изчисляват се и техните рейтинги от продажбите
        $deltaQuery = sales_PrimeCostByDocument::getQuery();
        $deltaQuery->where("#sellCost IS NOT NULL AND (#state = 'active' OR #state = 'closed') AND #isPublic = 'yes'");
        $deltaQuery->where("#valior >= '{$valiorFrom}'");
        $deltaQuery->show('productId,storeId,detailClassId,valior');
        
        // Ако артикула се среща и в експедиционен документ е с по-малка тежест
        $reportClassId = pos_Reports::getClassId();
        while ($deltaRec = $deltaQuery->fetch()){
            $rating = ($deltaRec->detailClassId == $reportClassId) ? 150 : 1;
            $monthsBetween = countR(dt::getMonthsBetween($receiptRec->valior));
            $rating = $rating * round(12 / $monthsBetween);
            
            $index = "{$deltaRec->productId}|{$deltaRec->storeId}";
            sales_ProductRatings::addRatingToObject($res, $index, $classId, $objectClassId, $deltaRec->productId, $deltaRec->storeId, $rating);
        }
        
        $res = array_values($res);
        
        return $res;
    }


    /**
     * Връща планираните наличности
     *
     * @param stdClass $rec
     * @return array
     *       ['productId']        - ид на артикул
     *       ['storeId']          - ид на склад, или null, ако няма
     *       ['date']             - на коя дата
     *       ['quantityIn']       - к-во очаквано
     *       ['quantityOut']      - к-во за експедиране
     *       ['genericProductId'] - ид на генеричния артикул, ако има
     *       ['reffClassId']      - клас на обект (различен от този на източника)
     *       ['reffId']           - ид на обект (различен от този на източника)
     */
    public function getPlannedStocks($rec)
    {
        $id = is_object($rec) ? $rec->id : $rec;
        $rec = $this->fetch($id, '*', false);

        $dQuery = pos_ReceiptDetails::getQuery();
        $dQuery->EXT('generic', 'cat_Products', "externalName=generic,externalKey=productId");
        $dQuery->EXT('canConvert', 'cat_Products', "externalName=canConvert,externalKey=productId");
        $dQuery->where("#receiptId = {$rec->id} AND #action LIKE '%sale%'");

        $res = array();
        while($dRec = $dQuery->fetch()){
            $packRec = cat_products_Packagings::getPack($dRec->productId, $dRec->value);
            $quantityInPack = is_object($packRec) ? $packRec->quantity : 1;
            $quantity = $quantityInPack * $dRec->quantity;

            if(!empty($dRec->storeId)){
                $key = "{$dRec->storeId}|{$dRec->productId}";
                if(!array_key_exists($key, $res)){
                    $genericProductId = null;
                    if($dRec->generic == 'yes'){
                        $genericProductId = $dRec->productId;
                    } elseif($dRec->canConvert == 'yes'){
                        $genericProductId = planning_GenericMapper::fetchField("#productId = {$dRec->productId}", 'genericProductId');
                    }
                    $res[$key] = (object)array('storeId'          => $dRec->storeId,
                                               'productId'        => $dRec->productId,
                                               'date'             => $rec->valior,
                                               'quantityIn'       => null,
                                               'quantityOut'      => 0,
                                               'genericProductId' => $genericProductId);
                }
                $res[$key]->quantityOut += $quantity;
            }
        }

        return $res;
    }


    /**
     * ф-я връщаща най-голямото налично к-во в точката
     *
     * @param int $productId
     * @param int $pointId
     * @param bool $checkFreeQuantity
     * @return double
     */
    public static function getBiggestQuantity($productId, $pointId, $checkFreeQuantity = false)
    {
        $stores = pos_Points::getStores($pointId);
        $storeArr = store_Products::getQuantitiesByStore($productId, null, $stores, $checkFreeQuantity);
        arsort($storeArr);

        return $storeArr[key($storeArr)];
    }


    /**
     * Дали бележката е за дефолтния контрагент за ПОС-а
     *
     * @param $rec
     * @return bool
     */
    public static function isForDefaultContragent($rec)
    {
        $rec = static::fetchRec($rec);
        $defaultContragentId = pos_Points::defaultContragent($rec->pointId);
        if($rec->contragentClass == crm_Persons::getClassId() && $defaultContragentId == $rec->contragentObjectId) return true;

        return false;
    }


    /**
     * Замаскирано име на контрагента, ако е лица показва се само политиката му
     *
     * @param mixed $contragentClassId
     * @param int $contragentId
     * @param int $pointId
     * @param array $params
     * @return core_ET|string
     */
    public static function getMaskedContragent($contragentClassId, $contragentId, $pointId, $params = array())
    {
        $Class = cls::get($contragentClassId);
        $link = $params['link'] ?? false;
        $icon = $params['icon'] ?? false;

        $attr = ($params['blank']) ? array('target' => '_blank') : array();
        if($Class instanceof crm_Companies){

            return ($link) ? $Class->getHyperlink($contragentId, $icon, false, $attr) : $Class->getTitleById($contragentId);
        } else {
            $date = $params['date'] ?? dt::now();
            $defaultContragentId = pos_Points::defaultContragent($pointId);
            $contragentPriceListId = $params['policyId'] ? $params['policyId'] : (($defaultContragentId == $contragentId) ? pos_Points::getSettings($pointId, 'policyId') : price_ListToCustomers::getListForCustomer($contragentClassId, $contragentId, $date));

            $title = tr("Политика|*: ") . price_Lists::getTitleById($contragentPriceListId);
            if($link && !Mode::isReadOnly()){
                $singleUrl = $Class->getSingleUrlArray($contragentId);
                if($singleUrl){
                    $title = ht::createLinkRef($title, $singleUrl, false, array('title' => $Class->getTitleById($contragentId)));
                }
            }

            return $title;
        }
    }


    /**
     * Екшън за ръчно приключване на бележка
     */
    public function act_manualpending()
    {
        $this->requireRightFor('manualpending');
        expect($id = Request::get('id', 'int'));
        expect($rec = static::fetch($id));
        $this->requireRightFor('manualpending', $rec);
        $this->markAsWaiting($rec);
        $this->logInAct('Ръчно приключване на бележка', $rec->id);

        followRetUrl(null, '|Бележката е ръчно приключена');
    }


    /**
     * Рекалкулира автоматичните отстъпки за продажбата
     *
     * @param $rec
     * @return void
     */
    public static function recalcAutoDiscount($rec)
    {
        $rec = pos_Receipts::fetchRec($rec);

        $basicDiscountListRec = price_Lists::getListWithBasicDiscounts('pos_Receipts', $rec);
        if(!is_object($basicDiscountListRec)) return;

        $dQuery = pos_ReceiptDetails::getQuery();
        $dQuery->EXT('isPublic', 'cat_Products', "externalName=isPublic,externalKey=productId");
        $dQuery->EXT('groups', 'cat_Products', "externalName=groups,externalKey=productId");
        $dQuery->where("#receiptId = {$rec->id} AND #isPublic = 'yes' AND #action = 'sale|code'");
        $detailsAll = $dQuery->fetchAll();

        $save = array();
        $discountData = price_ListBasicDiscounts::getAutoDiscountsByGroups($basicDiscountListRec, 'pos_Receipts', $rec, 'pos_ReceiptDetails', $detailsAll);
        foreach ($detailsAll as $dRec){
            foreach ($discountData['groups'] as $groupId => $d){
                if(!keylist::isIn($groupId, $dRec->groups)) continue;
                if(empty($d['percent'])) continue;

                $dRec->autoDiscount = $d['percent'];
                $save[] = $dRec;
            }
        }

        cls::get('pos_ReceiptDetails')->saveArray($save, 'id,autoDiscount');
    }


    /**
     * Реакция в счетоводния журнал при оттегляне на счетоводен документ
     *
     * @param core_Mvc   $mvc
     * @param mixed      $res
     * @param int|object $id  първичен ключ или запис на $mvc
     */
    public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);

        // Ако има ваучер се маркира като неизползван
        if(isset($rec->voucherId) && core_Packs::isInstalled('voucher')){
            voucher_Cards::mark($rec->voucherId, false);
        }
    }


    /**
     * Изпълнява се преди възстановяването на документа
     */
    public static function on_BeforeRestore(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);

        // Проверка дали ваучерът е вече свободен
        if(isset($rec->voucherId) && core_Packs::isInstalled('voucher')){
            if($error = voucher_Cards::getRestoreError($rec->voucherId)){
                core_Statuses::newStatus($error, 'error');

                return false;
            }
        }
    }


    /**
     * Рендира заявката за създаване на резюме
     */
    public function prepareListSummary_(&$data)
    {
        if(Request::get('Rejected')) return;

        $summaryQuery = clone $data->query;
        $summaryQuery->XPR('totalNoDraft', 'int', "(CASE WHEN (#state = 'draft' OR #transferredIn IS NOT NULL) THEN 0 ELSE #total END)");
        $summaryQuery->XPR('paidNoDraft', 'int', "(CASE WHEN (#state = 'draft' OR #transferredIn IS NOT NULL) THEN 0 ELSE #paid END)");
        $summaryQuery->XPR('changeNoDraft', 'int', "(CASE WHEN (#state = 'draft' OR #transferredIn IS NOT NULL) THEN 0 ELSE #change END)");

        $data->listSummary = (object)array('mvc' => clone $this, 'query' => $summaryQuery);
        $data->listSummary->mvc->FNC('totalNoDraft', 'varchar', 'caption=Общо (без чернови),input=none,summary=amount');
        $data->listSummary->mvc->FNC('paidNoDraft', 'varchar', 'caption=Платено (без чернови),input=none,summary=amount');
        $data->listSummary->mvc->FNC('changeNoDraft', 'varchar', 'caption=Ресто (без чернови),input=none,summary=amount');
    }
}
