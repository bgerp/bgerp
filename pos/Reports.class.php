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
        'dealerId' => 'lastDocUser|lastDoc',
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
    public $details = 'total=pos_ReportDetails,receipts=pos_ReportDetails';


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
        $this->FLD('dealerId', "user(roles=ceo|sales|pos,allowEmpty)", 'caption=Търговец,mandatory');
        $this->FLD('chargeVat', 'enum(yes=Начисляване,no=Без начисляване)', 'caption=Допълнително->ДДС,notNull,value=yes');
    }
    
    
    /**
     * Подготовка на формата за добавяне
     */
    protected static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $data->form->setReadOnly('pointId');
        $data->form->setField('valior', "placeholder=" . dt::mysql2verbal(dt::today(), 'd.m.Y'));
        
        if(haveRole('pos,sales')){
            $data->form->setDefault('dealerId', core_Users::getCurrent());
        }
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
        $row->from = dt::mysql2verbal($rec->details['receipts'][0]->createdOn, 'd.m.Y H:i');
        $row->to = dt::mysql2verbal($rec->details['receipts'][countR($rec->details['receipts']) - 1]->createdOn, 'd.m.Y H:i');
        
        if ($fields['-single']) {
            $pointRec = pos_Points::fetch($rec->pointId);
            $row->caseId = cash_Cases::getHyperLink($pointRec->caseId, true);
            $row->baseCurrency = acc_Periods::getBaseCurrencyCode($rec->createdOn);
            setIfNot($row->dealerId, $row->createdBy);
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните
     */
    protected static function on_AfterInputEditForm($mvc, core_Form &$form)
    {
        if ($form->isSubmitted()) {
            
            // Можем ли да създадем отчет за този касиер или точка
            if (!self::canMakeReport($form->rec->pointId)) {
                $form->setError('pointId', 'Не може да създадете отчет за тази точка');
            }
            
            // Ако няма грешки, форсираме отчета да се създаде в папката на точката
            if (!$form->gotErrors()) {
                $form->rec->folderId = pos_Points::forceCoverAndFolder($form->rec->pointId);
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
        $reportData = $this->fetchData($rec->pointId);
        
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
        // Рендираме обобщената информация за касиерите
        if (countR($data->row->statisticArr)) {
            $block = $tpl->getBlock('ROW');

            foreach ($data->row->statisticArr as $statRow) {
                $rowTpl = clone $block;
                $rowTpl->placeObject($statRow);
                $rowTpl->removeBlocks();
                $rowTpl->append2master();
            }
        }

        if (countR($data->row->paymentSummary)) {
            $block = $tpl->getBlock('PAYMENTS');

            foreach ($data->row->paymentSummary as $payRow) {
                $rowTpl = clone $block;
                $rowTpl->placeObject($payRow);
                $rowTpl->removeBlocks();
                $rowTpl->append2master();
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
        $data->row->statisticArr = $data->row->paymentSummary = array();
        foreach ($detail->receipts as $receiptRec) {
            if (!array_key_exists($receiptRec->createdBy, $data->row->statisticArr)) {
                $data->row->statisticArr[$receiptRec->createdBy] = (object) array('receiptBy' => crm_Profiles::createLink($receiptRec->createdBy),
                    'receiptTotal' => $receiptRec->total);
            } else {
                $data->row->statisticArr[$receiptRec->createdBy]->receiptTotal += $receiptRec->total;
            }
        }

        // Сумиране по видове плащания
        $paymentsRecs = array_filter($data->rec->details['receiptDetails'], function($a) {return $a->action == 'payment';});
        foreach ($paymentsRecs as $paymentRec){
            if (!array_key_exists($paymentRec->value, $data->row->paymentSummary)) {
                $value = ($paymentRec->value != -1) ? cond_Payments::getTitleById($paymentRec->value) : tr('В брой');
                $data->row->paymentSummary[$paymentRec->value] = (object) array('paymentType' => $value, 'paymentTotal' => 0);
            }
            $data->row->paymentSummary[$paymentRec->value]->paymentTotal += $paymentRec->amount;
        }

        $Double = core_Type::getByName('double(decimals=2)');
        foreach ($data->row->statisticArr as &$rRec) {
            $rRec->receiptTotal = $Double->toVerbal($rRec->receiptTotal);
        }
        foreach ($data->row->paymentSummary as &$pRec) {
            $pRec->paymentTotal = $Double->toVerbal($pRec->paymentTotal);
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
     * @param int $pointId - Ид на точката на продажба
     *
     * @return array $result - масив с резултати
     * */
    private function fetchData($pointId)
    {
        $details = $receipts = array();
        $query = pos_Receipts::getQuery();
        $query->where("#pointId = {$pointId}");
        $query->where("#state = 'waiting'");
        
        // извличаме нужната информация за продажбите и плащанията
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
            
            // запомняме кои бележки сме обиколили
            $receipts[] = (object) array('id' => $rec->id, 'createdOn' => $rec->createdOn, 'createdBy' => $rec->createdBy, 'total' => $rec->total);
            
            // Добавяме детайлите на бележката
            $data = pos_ReceiptDetails::fetchReportData($rec->id);
            
            foreach ($data as $obj) {
                $indexArr = array($obj->action, $obj->pack, $obj->contragentClassId, $obj->contragentId, $obj->value, $obj->param, $obj->storeId);
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
        // Контираме документа
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
                core_Statuses::newStatus("|{$msg} са|* '{$count}' |бележки за продажба|*");
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
     * @param int $pointId - ид на точка
     *
     * @return bool
     */
    public static function canMakeReport($pointId)
    {
        // Ако няма нито една активна бележка за посочената каса и касиер, не може да се създаде отчет
        if (!pos_Receipts::fetchField("#pointId = {$pointId} AND #state = 'waiting'")) {
            
            return false;
        }
        
        // Ако има неприключена започната бележка в тачката от касиера, също не може да се направи отчет
        if (pos_Receipts::fetchField("#pointId = {$pointId} AND #total != 0 AND #state = 'draft'")) {
            
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
                    
                    return followRetUrl(null, 'Не може да се направи отчет');
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
                    'contragentClassId' => $dRec->contragentClassId,);
                
                if($r->quantity){
                    if($rec->chargeVat == 'no'){
                        $dRec->amount *= (1 + $dRec->param);
                    }
                    
                    $r->sellCost = $dRec->amount / $r->quantity;
                } else {
                    $r->sellCost = 0;
                    wp($r, $rec);
                }
                
                $dealerId = $rec->dealerId;
                setIfNot($dealerId, $rec->createdBy);
                $r->dealerId = $dealerId;
                
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
