<?php


/**
 * Модел Отчети за POS продажби
 *
 *
 * @category  bgerp
 * @package   pos
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pos_Reports extends core_Master
{
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=pos_transaction_Report, deals_DealsAccRegIntf, acc_RegisterIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Отчети за POS продажби';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = true;
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'pos_Wrapper, plg_Printing, sales_plg_CalcPriceDelta, acc_plg_Contable, cond_plg_DefaultValues, doc_DocumentPlg, bgerp_plg_Blank, doc_plg_Close, acc_plg_Registry, acc_plg_DocumentSummary, plg_Search, plg_Sorting';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Отчет за POS продажби';
    
    
    /**
     * Икона на единичния обект
     */
    public $singleIcon = 'img/16/report.png';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Otc';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'pos, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, pos';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo, pos';
    
    
    /**
     * Кой има право да контира?
     */
    public $canConto = 'pos, ceo';
    
    
    /**
     * Файл за единичен изглед
     */
    public $singleLayoutFile = 'pos/tpl/SingleReport.shtml';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, valior, title=Документ, pointId, total, paid, state, createdOn, createdBy';
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
        'operators' => 'lastDocUser|lastDoc',
        'chargeVat' => 'lastDocUser|lastDoc',
    );
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'pointId';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '3.5|Търговия';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'valior, createdOn';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;


    /**
     * Детайли
     */
    public $details = 'shipped=pos_ReportDetails,payments=pos_ReportDetails,receipts=pos_ReportDetails';


    /**
     * Дали автоматично да се разпределят партиди при моментно производство
     */
    public $allowInstantProductionBatches = true;


    /**
     * Дали артикула ще произвежда при експедиране артикулите с моментна рецепта
     */
    public $manifactureProductsOnShipment = true;


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('pointId', 'key(mvc=pos_Points, select=name)', 'caption=Точка, width=9em, mandatory,silent');
        $this->FLD('valior', 'date', 'caption=Вальор');
        $this->FLD('paid', 'double(decimals=2)', 'caption=Сума->Платено, input=none, value=0, summary=amount');
        $this->FLD('total', 'double(decimals=2)', 'caption=Сума->Продадено, input=none, value=0, summary=amount');
        $this->FLD('state', 'enum(draft=Чернова,active=Активиран,rejected=Оттеглена,closed=Приключен,stopped=Спряно)', 'caption=Състояние,input=none,width=8em');
        $this->FLD('details', 'blob(serialize,compress)', 'caption=Данни,input=none');
        $this->FLD('closedOn', 'datetime', 'input=none');
        $this->FLD('operators', "keylist(mvc=core_Users,select=nick,allowEmpty)", 'caption=Оператори');
        $this->FLD('chargeVat', 'enum(yes=Начисляване,no=Без начисляване)', 'caption=Допълнително->ДДС,notNull,value=yes');
    }
    
    
    /**
     * Подготовка на формата за добавяне
     */
    protected static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $form = &$data->form;
        $form->setReadOnly('pointId');
        $form->setField('valior', "placeholder=" . dt::mysql2verbal(dt::today(), 'd.m.Y'));
        $settings = pos_Points::getSettings($form->rec->pointId);
        $form->setDefault('chargeVat', $settings->chargeVat);
        $form->setReadOnly('chargeVat');

        if(haveRole('pos,sales')){
            $form->setDefault('dealerId', core_Users::getCurrent());
        }

        $operatorOptions = core_Users::getUsersByRoles('powerUser');
        $form->setSuggestions('operators', $operatorOptions);
    }
    
    
    /**
     *  Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->query->orderBy('valior', 'DESC');
        pos_Points::addPointFilter($data->listFilter, $data->query);
    }
    
    
    /**
     * Изпълнява се преди вербалното представяне
     */
    protected static function on_BeforeRecToVerbal($mvc, &$row, $rec, $fields)
    {
        // Ако няма записани детайли извличаме актуалните
        if (!$rec->details) {
            $mvc->extractData($rec);
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->title = $mvc->getLink($rec->id, 0);
        $row->pointId = pos_Points::getHyperLink($rec->pointId, true);

        $dates = arr::extractValuesFromArray($rec->details['receipts'], 'createdOn');
        if(countR($dates)){
            $fromDate = min($dates);
            $toDate = max($dates);

            $row->from = dt::mysql2verbal($fromDate, 'd.m.Y H:i');
            $row->to = dt::mysql2verbal($toDate, 'd.m.Y H:i');
        }
        
        if ($fields['-single']) {
            $pointRec = pos_Points::fetch($rec->pointId);
            $row->caseId = cash_Cases::getHyperLink($pointRec->caseId, true);
            $row->baseCurrency = acc_Periods::getBaseCurrencyCode($rec->createdOn);
            setIfNot($row->dealerId, $row->createdBy);

            if(empty($rec->operators)){
                $row->operators = "<i>" . tr("Всички") . "</i>";
            }
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните
     */
    protected static function on_AfterInputEditForm($mvc, core_Form &$form)
    {
        if ($form->isSubmitted()) {
            $rec = $form->rec;

            // Можем ли да създадем отчет за този касиер или точка
            $errorMsg = null;
            if (!self::canMakeReport($rec->pointId, $rec->operators, $errorMsg)) {
                $form->setError('pointId', $errorMsg);
            }

            if(!empty($rec->valior) && $rec->valior < dt::today()){
                $form->setError('valior', 'Вальорът не може да е в миналото');
            }

            // Ако няма грешки, форсираме отчета да се създаде в папката на точката
            if (!$form->gotErrors()) {
                $rec->folderId = pos_Points::forceCoverAndFolder($rec->pointId);
            }
        }
    }
    
    
    /**
     * Функция която обновява информацията на репорта
     * извиква се след изпращането на формата и при
     * активация на документа
     *
     * @param stdClass $rec - запис от модела
     */
    public function extractData(&$rec)
    {
        // Извличаме информацията от бележките
        $reportData = $this->fetchData($rec);
        
        $rec->details = $reportData;
        $rec->total = $rec->paid = 0;
        if (countR($reportData['receiptDetails'])) {
            foreach ($reportData['receiptDetails'] as $index => $detail) {
                list($action) = explode('|', $index);
                if ($action == 'sale') {
                    $rec->total += $detail->amount * (1 + $detail->param);
                } else {
                    $paidAmount = $detail->amount;
                    if($detail->value != '-1'){
                        $paidAmount = cond_Payments::toBaseCurrency($detail->value, $paidAmount, $rec->valior);
                    }
                    
                    $rec->paid += $paidAmount;
                }
            }
        }
    }
    
    
    /**
     * Пушваме css и рендираме "детайлите"
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        // Рендиране на обобщената информация за касиерите
        if (countR($data->statisticArr)) {
            foreach ($data->statisticArr as $k => $statRow) {
                $operatorBlock = clone $tpl->getBlock('OPERATOR');
                $operatorBlock->append($statRow->receiptBy, 'operatorId');
                if(isset($statRow->receiptTotal)){
                    $statRow->receiptTotalVerbal = core_Type::getByName('double(decimals=2)')->toVerbal($statRow->receiptTotal);
                    $statRow->receiptTotalVerbal = ht::styleNumber($statRow->receiptTotalVerbal, $statRow->receiptTotal);
                    $statRow->receiptTotalVerbal = currency_Currencies::decorate($statRow->receiptTotalVerbal, $data->row->baseCurrency);
                    $operatorBlock->append($statRow->receiptTotalVerbal, 'operatorTotal');
                }

                ksort($statRow->payments);

                $cardPaymentId = cond_Setup::get('CARD_PAYMENT_METHOD_ID');
                foreach ($statRow->payments as $paymentRec){
                    $paymentBlocks = clone $operatorBlock->getBlock('PAYMENT_ROW');

                    $paymentName = ($paymentRec->value == '-1') ? 'В брой' : cond_Payments::getTitleById($paymentRec->value);
                    $paymentName = tr($paymentName);
                    if($paymentRec->value == $cardPaymentId){
                        if(isset($paymentRec->deviceId)){
                            $deviceRec = peripheral_Devices::fetch($paymentRec->deviceId);
                            $deviceName = cls::get($deviceRec->driverClass)->getBtnName($deviceRec);
                            $paymentName = "<div style='text-indent:30px'>- {$deviceName}</div>";
                        } else {
                            $paymentName .= " (" . tr('Всички') . ")";
                        }
                    }

                    $paymentBlocks->append($paymentName, 'paymentId');
                    $paymentAmountRow = core_Type::getByName('double(decimals=2)')->toVerbal($paymentRec->amount);
                    $paymentAmountRow = ht::styleNumber($paymentAmountRow, $paymentRec->amount);
                    $paymentAmountRow = currency_Currencies::decorate($paymentAmountRow, $data->row->baseCurrency);
                    $paymentBlocks->append($paymentAmountRow, 'paymentAmount');
                    if($k == -1){
                        $paymentBlocks->append('reportTotal', 'PAYMENT_CLASS');
                    }
                    $paymentBlocks->removeBlocksAndPlaces();
                    $operatorBlock->append($paymentBlocks, 'PAYMENT');
                }

                $operatorBlock->removeBlocksAndPlaces();
                $tpl->append($operatorBlock, 'OPERATOR_DATA');
            }
        }
        
        // Пушваме стиловете
        $tpl->push('pos/tpl/css/styles.css', 'CSS');
    }
    
    
    /**
     * Обработка детайлите на репорта
     */
    protected static function on_AfterPrepareSingle($mvc, &$data)
    {
        $detail = (object) $data->rec->details;

        // Сумиране по плащания на клиентите
        $data->statisticArr = array();
        $receiptIds = arr::extractValuesFromArray($detail->receipts, 'id');
        $rQuery = pos_ReceiptDetails::getQuery();
        $rQuery->EXT('createdReceiptBy', 'pos_Receipts', 'externalName=createdBy,externalKey=receiptId');
        $rQuery->EXT('waitingReceiptBy', 'pos_Receipts', 'externalName=waitingBy,externalKey=receiptId');
        $rQuery->EXT('change', 'pos_Receipts', 'externalName=change,externalKey=receiptId');
        $rQuery->XPR('calcedUser', 'int', "COALESCE(#waitingReceiptBy, #createdReceiptBy)");
        $rQuery->where("#action LIKE '%payment%'");
        if(countR($receiptIds)){
            $rQuery->in('receiptId', $receiptIds);
        } else {
            $rQuery->where("1=2");
        }

        $cardPaymentId = cond_Setup::get('CARD_PAYMENT_METHOD_ID');
        $totalArr = (object)array('receiptBy' => tr('Общо'), 'payments' => array());
        while($rRec = $rQuery->fetch()){
            $action = explode('|', $rRec->action);
            if($action[1] == -1){
                $rRec->amount -= $rRec->change;
            }
            if (!array_key_exists($rRec->calcedUser, $data->statisticArr)) {
                $data->statisticArr[$rRec->calcedUser] = (object) array('receiptBy' => crm_Profiles::createLink($rRec->calcedUser), 'receiptTotal' => 0, 'payments' => array());
            }

            if (!array_key_exists($action[1], $data->statisticArr[$rRec->calcedUser]->payments)) {
                $data->statisticArr[$rRec->calcedUser]->payments[$action[1]] = (object)array('value' => $action[1], 'amount' => 0);
            }

            if (!array_key_exists($action[1], $totalArr->payments)) {
                $totalArr->payments[$action[1]] = (object)array('value' => $action[1], 'amount' => 0, 'deviceId' => null);
            }

            if($action[1] == $cardPaymentId){
                if($rRec->deviceId){
                    if (!array_key_exists("{$action[1]}|{$rRec->deviceId}", $data->statisticArr[$rRec->calcedUser]->payments)) {
                        $data->statisticArr[$rRec->calcedUser]->payments["{$action[1]}|{$rRec->deviceId}"] = (object)array('value' => $action[1], 'amount' => 0, 'deviceId' => $rRec->deviceId);
                    }
                    $data->statisticArr[$rRec->calcedUser]->payments["{$action[1]}|{$rRec->deviceId}"]->amount += $rRec->amount;
                }
            }
            $data->statisticArr[$rRec->calcedUser]->receiptTotal += $rRec->amount;
            $data->statisticArr[$rRec->calcedUser]->payments[$action[1]]->amount += $rRec->amount;
            $totalArr->payments[$action[1]]->amount += $rRec->amount;
        }

        if(countR($data->statisticArr) > 1){
            $data->statisticArr += array('-1' => $totalArr);
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а на формата за редактиране/добавяне
     */
    protected static function on_AfterPrepareEditToolbar($mvc, $data)
    {
        if (!empty($data->form->toolbar->buttons['save'])) {
            $data->form->toolbar->removeBtn('save');
            $data->form->toolbar->addSbBtn('Контиране', 'save', 'warning=Наистина ли желаете да контирате отчета|*?,ef_icon = img/16/disk.png,order=9.99985, title = Контиране на документа');
        }
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow_($id)
    {
        $rec = $this->fetch($id);
        $title = "Отчет за POS продажба №{$rec->id}";
        $row = new stdClass();
        $row->title = $title;
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        $row->recTitle = $title;
        
        return $row;
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public static function getHandle($id)
    {
        $rec = static::fetchRec($id);
        $self = cls::get(get_called_class());
        
        return $self->abbr . $rec->id;
    }
    
    
    /**
     * Подготвя информацията за направените продажби и плащания
     * от всички бележки за даден период от време на даден потребител
     * на дадена точка
     *
     * @param stdClass $rec - Ид на точката на продажба
     *
     * @return array $result - масив с резултати
     * */
    private function fetchData($rec)
    {
        $details = $receipts = array();
        $query = pos_Receipts::getQuery();
        $query->where("#pointId = {$rec->pointId}");
        $query->where("#state = 'waiting'");
        if(!empty($rec->operators)){
            $operatorStr = implode(',', keylist::toArray($rec->operators));
            $query->where("#waitingBy IN ($operatorStr) OR (#waitingBy IS NULL AND #createdBy IN ($operatorStr))");
        }

        // Извличане на нужната информация за продажбите и плащанията
        $this->fetchReceiptData($query, $details, $receipts);
        
        return array('receipts' => $receipts, 'receiptDetails' => $details);
    }
    
    
    /**
     * Връща продажбите и плащанията направени в търсените бележки групирани
     *
     * @param core_Query $query    - Заявка към модела
     * @param array      $results  - Масив в който ще връщаме резултатите
     * @param array      $receipts - Масив от бележките които сме обходили
     */
    private function fetchReceiptData($query, &$results, &$receipts)
    {
        while ($rec = $query->fetch()) {
            
            // Запомняне на бележките
            $receipts[] = (object) array('id' => $rec->id, 'createdOn' => $rec->createdOn, 'createdBy' => $rec->createdBy, 'waitingOn' => $rec->waitingOn, 'waitingBy' => $rec->waitingBy, 'total' => $rec->total);
            
            // Добавяне на детайлите на бележките
            $data = pos_ReceiptDetails::fetchReportData($rec->id);

            foreach ($data as $obj) {
                $indexArr = array($obj->action, $obj->pack, $obj->contragentClassId, $obj->contragentId, $obj->value, $obj->param, $obj->storeId, $obj->userId);
                if(core_Packs::isInstalled('batch')){
                    $indexArr[] = str_replace('|', '>', $obj->batch);
                }
                
                $index = implode('|', $indexArr);
                if (!array_key_exists($index, $results)) {
                    $results[$index] = $obj;
                } else {
                    $results[$index]->quantity += $obj->quantity;
                    $results[$index]->amount += $obj->amount;
                }   
            }
        }
    }
    
    
    /**
     * Преди запис на документ, изчислява стойността на полето `isContable`
     *
     * @param core_Manager $mvc
     * @param stdClass     $rec
     */
    protected static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
        if ($rec->state == 'active' && $rec->brState != 'closed') {
            
            // Ако няма записани детайли извличаме актуалните
            $mvc->extractData($rec);
        }
        
        if (empty($rec->id)) {
            $rec->isContable = 'yes';
        }
    }
    
    
    /**
     * След създаване автоматично да се контира
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        // Форсиране на обновяването на нишката, ако гръмне по време на контирането, да не остане развалена нишката
        doc_Threads::doUpdateThread($rec->threadId);

        // Контиране на документа
        $mvc->conto($rec);
        
        // Еднократно оттегляме всички празни чернови бележки
        $mvc->rejectEmptyReceipts($rec);
    }
    
    
    /**
     * Маркира използваните артикули
     */
    private function markUsedProducts($rec, $remove = false)
    {
        // Записа се извлича наново, защото поради някаква причина при оттегляне е непълен
        $id = is_object($rec) ? $rec->id : $rec;
        $rec = $this->fetch($id, '*', false);
       
        if(countR($rec->details['receiptDetails'])){
            $affectedProducts = array();
            array_walk($rec->details['receiptDetails'], function ($a) use (&$affectedProducts){if($a->action == 'sale') {$affectedProducts[$a->value] = $a->value;}});
            
            // Ако има намерени продадени артикули се маркират/демаркират като използвани
            if(countR($affectedProducts)){
                foreach ($affectedProducts as $productId){
                    $pContainerId = cat_Products::fetchField($productId, 'containerId');
                    if($remove){
                        doclog_Used::remove($rec->containerId, $pContainerId);
                    } else {
                        doclog_Used::add($rec->containerId, $pContainerId);
                    }
                }
            }
        }
    }
    
    
    /**
     * Функция, която се извиква след активирането на документа
     */
    protected static function on_AfterActivation($mvc, &$rec)
    {
        // След контиране се маркират използваните артикули
        $mvc->markUsedProducts($rec);
    }
    
    
    /**
     * Изпълнява се преди оттеглянето на документа
     */
    protected static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
        // След оттегляне се махат използванията на артикулите
        $mvc->markUsedProducts($id, true);
    }
    
    
    /**
     * Оттегля всички празни чернови бележки в дадена точка от даден касиер
     *
     * @param stdClass $rec - ид на точка
     */
    private function rejectEmptyReceipts($rec)
    {
        $rQuery = pos_Receipts::getQuery();
        $rQuery->where("#pointId = {$rec->pointId} AND #state = 'draft' AND #total = 0");
        
        // Оттегляме само тези чернови чиято дата е преди тази на последната активна бележка
        $lastReceiptDate = $rec->details['receipts'][countR($rec->details['receipts']) - 1]->createdOn;
        $rQuery->where("#valior < '{$lastReceiptDate}'");
        
        $count = $rQuery->count();
        while ($rRec = $rQuery->fetch()) {
            pos_Receipts::reject($rRec);
        }
        
        if ($count) {
            core_Statuses::newStatus("|Оттеглени са|* {$count} |празни бележки|*");
        }
    }
    
    
    /**
     * След промяна в журнала със свързаното перо
     */
    protected static function on_AfterJournalItemAffect($mvc, $rec, $item)
    {
        if ($rec->state != 'draft' && $rec->state != 'closed') {
            $nextState = ($rec->state == 'active') ? 'closed' : 'waiting';
            $msg = ($rec->state == 'active') ? 'Приключени' : 'Активирани';

            // Всяка бележка в репорта се "затваря"
            $count = 0;
            $Receipts = cls::get('pos_Receipts');
            foreach ($rec->details['receipts'] as $receiptRec) {
                $state = pos_Receipts::fetchField($receiptRec->id, 'state');
                if ($state == $nextState) {
                    continue;
                }
                
                $receiptRec->modifiedBy = core_Users::getCurrent();
                $receiptRec->modifiedOn = dt::now();
                $receiptRec->exState = $receiptRec->state;
                $receiptRec->state = $nextState;

                $Receipts->save($receiptRec, 'state,modifiedOn,modifiedBy,exState');
                if($receiptRec->state == 'closed'){
                    store_StockPlanning::remove($Receipts, $receiptRec->id);
                }

                $count++;
            }
            
            if ($count) {
                core_Statuses::newStatus("|{$msg} бележки за продажба|*: {$count}");
            }
        }
    }
    
    
    /**
     * След обработка на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        // Никой не може да редактира бележка
        if ($action == 'activate' && !$rec) {
            $res = 'no_one';
        }
        
        if ($action == 'add' && isset($rec)) {
            if (empty($rec->pointId)) {
                $res = 'no_one';
            }
        }
        
        // Забраняваме оттеглянето на приключен отчет, за да се оттегли трябва първо да се активира
        if ($action == 'reject' && isset($rec)) {
            if ($rec->state == 'closed') {
                $res = 'no_one';
            }
        }
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        $cover = doc_Folders::getCover($folderId);
        
        return $cover->className == 'doc_UnsortedFolders' || $cover->className == 'pos_Points';
    }
    
    
    /**
     * Метод по подразбиране
     * Връща иконата на документа
     */
    protected static function on_AfterGetIcon($mvc, &$res, $id = null)
    {
        if (!$res) {
            $res = $mvc->singleIcon;
            if (log_Browsers::isRetina()) {
                $icon2 = str_replace('/16/', '/32/', $res);
                
                if (getFullPath($icon2)) {
                    $res = $icon2;
                }
            }
        }
    }
    
    
    /**
     * Перо в номенклатурите, съответстващо на този продукт
     *
     * Част от интерфейса: acc_RegisterIntf
     */
    public static function getItemRec($objectId)
    {
        $result = null;
        $self = cls::get(get_called_class());
        
        if ($rec = self::fetch($objectId)) {
            $result = (object) array(
                'num' => $objectId . ' ' . mb_strtolower($self->abbr),
                'title' => static::getRecTitle($rec, false),
            );
        }
        
        return $result;
    }
    
    
    /**
     * @see acc_RegisterIntf::itemInUse()
     *
     * @param int $objectId
     */
    public static function itemInUse($objectId)
    {
    }
    
    
    /**
     * Проверява може ли да се създаде отчет за този клиент. За създаване трябва
     * да е изпълнено:
     * 	1. Да има поне една активна (приключена) бележка за касиера и точката
     *  2. Да няма нито една започната, но неприключена бележка
     *
     * @param int $pointId           - ид на точка
     * @param null|string $operators - списък с оператори
     * @param null|string $msg       - съобщение за грешка
     *
     * @return bool
     */
    public static function canMakeReport($pointId, $operators = null, &$msg = null)
    {
        // Ако няма нито една активна бележка за посочената каса и касиер, не може да се създаде отчет
        $operatorArr = keylist::toArray($operators);
        $pQuery = pos_Receipts::getQuery();
        $pQuery->where("#pointId = {$pointId} AND #state = 'waiting'");
        if(countR($operatorArr)){
            $operatorStr = implode(',', $operatorArr);
            $pQuery->where("#waitingBy IN ($operatorStr) OR (#waitingBy IS NULL AND #createdBy IN ($operatorStr))");
        }
        if (!$pQuery->count()) {
            $msg = "Няма чакащи бележки от посочените оператори";
            return false;
        }

        // Ако има неприключена започната бележка в точката от касиера, също не може да се направи отчет
        $pQuery2 = pos_Receipts::getQuery();
        $pQuery2->where("#pointId = {$pointId} AND #total != 0 AND #state = 'draft'");
        if(countR($operatorArr)){
            $pQuery2->in('createdBy', $operatorArr);
        }

        if ($pQuery2->count()) {
            $msg = "Има започнати но неприключени бележки";
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Извиква се преди изпълняването на екшън
     *
     * @param core_Mvc $mvc
     * @param mixed    $res
     * @param string   $action
     */
    protected static function on_BeforeAction($mvc, &$res, $action)
    {
        if ($action == 'add') {
            if ($pointId = Request::get('pointId', 'key(mvc=pos_Points)')) {
                if (!self::canMakeReport($pointId)) {
                    
                    return followRetUrl(null, '|Не може да се направи отчет');
                }
            }
        }
    }
    
    
    /**
     * Крон метод за автоматично затваряне на стари периоди
     */
    public function cron_CloseReports()
    {
        // Ако няма репорти не правим нищо
        if (!pos_Reports::count()) {
            
            return;
        }
        
        // Селектираме всички активни отчети по стари от указаната дата
        $conf = core_Packs::getConfig('pos');
        $now = dt::mysql2timestamp(dt::now());
        $oldBefore = dt::timestamp2mysql($now - $conf->POS_CLOSE_REPORTS_OLDER_THAN);
        
        $query = pos_Reports::getQuery();
        $query->where("#state = 'active'");
        $query->where("#createdOn <= '{$oldBefore}'");
        $query->limit($conf->POS_CLOSE_REPORTS_PER_TRY);
        $now = dt::now();
        
        // Затваряме всеки отчет, след затварянето автоматично ще му се затвори и перото
        while ($rec = $query->fetch()) {
            $rec->brState = 'active';
            $rec->state = 'closed';
            $rec->closedOn = dt::addSecs(-1 * $conf->POS_CLOSE_REPORTS_OLDER_THAN, $now);
            $this->save($rec, 'state,brState,closedOn');
            $this->logWrite('Автоматично затваряне на отчет', $rec->id);
        }
    }


    /**
     * Какви записи ще се направят в делтите
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     * @return array $res
     */
    public function getDeltaRecs($rec)
    {
        $rec = $this->fetchRec($rec);
        
        $res = array();
        
        $valior = dt::verbal2mysql($rec->valior, false);
        $classId = pos_Reports::getClassId();
        
        // Обхождат се продадените артикули
        $inCharge = array();
        $dealers = core_Users::getUsersByRoles('sales');

        // Извличат се лицата на потребителите с powerUser
        $powerUsers = core_Users::getUsersByRoles('powerUser');
        $pQuery = crm_Profiles::getQuery();
        $pQuery->in('userId', array_keys($powerUsers));
        $pQuery->show('personId');
        $personIds = arr::extractValuesFromArray($pQuery->fetchAll(), 'personId');
        $personClassId = crm_Persons::getClassId();

        if(is_array($rec->details['receiptDetails'])){
            foreach ($rec->details['receiptDetails'] as $dRec){
                if($dRec->action != 'sale') continue;
                
                $r = (object) array('valior' => $valior,
                    'detailClassId' => $classId,
                    'detailRecId' => "{$rec->id}000{$dRec->value}",
                    'quantity' => $dRec->quantity * $dRec->quantityInPack,
                    'productId' => $dRec->value,
                    'state'    => 'active',
                    'isPublic' => cat_Products::fetchField($dRec->value, 'isPublic'),
                    'contragentId' => $dRec->contragentId,
                    'activatedOn' => $rec->activatedOn,
                    'contragentClassId' => $dRec->contragentClassId,);

                // Ако бележката е продадена на вътрешен потребител - оператора е търговец
                if($dRec->contragentClassId == $personClassId && in_array($dRec->contragentId, $personIds)){
                    $userId = $dRec->userId;
                } else {

                    // Ако бележката е на външен клиент - търговеца е отговорника на папката му
                    // ако има sales, ако няма е оператора на бележката
                    if(!isset($inCharge[$dRec->contragentClassId][$dRec->contragentId])){
                        $inCharge[$dRec->contragentClassId][$dRec->contragentId] = cls::get($dRec->contragentClassId)->fetchField($dRec->contragentId, 'inCharge');
                    }
                    $userId = $inCharge[$dRec->contragentClassId][$dRec->contragentId];
                    if(!array_key_exists($userId, $dealers)){
                        if(isset($dRec->userId)){
                            $userId = $dRec->userId;
                        }
                    }
                }

                if($r->quantity){
                    if($rec->chargeVat == 'no'){
                        $dRec->amount *= (1 + $dRec->param);
                    }
                    
                    $r->sellCost = $dRec->amount / $r->quantity;
                } else {
                    $r->sellCost = 0;
                    wp($r, $rec);
                }
                
                setIfNot($userId, $rec->createdBy);
                $r->dealerId = $userId;
                
                // Изчисляване на себестойността на артикула
                $productRec = cat_Products::fetch($dRec->value, 'isPublic,code,canStore');
                if ($productRec->code == 'surcharge') {
                    $r->primeCost = 0;
                } else {
                    $r->primeCost = cat_Products::getPrimeCost($dRec->value, $dRec->pack, $r->quantity, $valior, price_ListRules::PRICE_LIST_COST);
                }
                
                if($productRec->canStore == 'yes'){
                    $r->storeId = $dRec->storeId;
                }

                $res[] = $r;
            }
        }
        
        return $res;
    }


    /**
     * Помощна ф-я в кой пос отчет е включена въпросната бележка
     *
     * @param $receiptId
     * @return void
     */
    public static function getReportReceiptIsIn($receiptId)
    {
        $reportQuery = pos_Reports::getQuery();
        $reportQuery->where("#state = 'active' || #state = 'closed'");
        $reportQuery->show('details');

        // Опитваме се да намерим репорта в който е приключена бележката
        //@TODO не е много оптимално защото търсим в блоб поле...
        while ($rRec = $reportQuery->fetch()) {
            $found = array_filter($rRec->details['receipts'], function ($e) use (&$receiptId) {

                return $e->id == $receiptId;
            });

            if ($found) return $rRec->id;
        }
    }
}
