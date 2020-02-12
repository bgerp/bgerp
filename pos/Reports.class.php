<?php


/**
 * Модел Отчети за POS продажби
 *
 *
 * @category  bgerp
 * @package   pos
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
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
    public $loadList = 'pos_Wrapper, plg_Printing, sales_plg_CalcPriceDelta, acc_plg_Contable, doc_DocumentPlg, bgerp_plg_Blank, doc_plg_Close, acc_plg_Registry, acc_plg_DocumentSummary, plg_Search, plg_Sorting';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Отчет за POS продажби';
    
    
    /**
     * Икона на единичния обект
     */
    public $singleIcon = 'img/16/report.png';
    
    
    /**
     * Брой продажби на страница
     */
    public $listDetailsPerPage = '50';
    
    
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
     * Полета, които ще се показват в листов изгле,д
     */
    public $listFields = 'id, title=Заглавие, pointId, total, paid, state, createdOn, createdBy';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'pointId';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $valiorFld = 'createdOn';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '3.5|Търговия';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('pointId', 'key(mvc=pos_Points, select=name)', 'caption=Точка, width=9em, mandatory,silent');
        $this->FLD('paid', 'double(decimals=2)', 'caption=Сума->Платено, input=none, value=0, summary=amount');
        $this->FLD('total', 'double(decimals=2)', 'caption=Сума->Продадено, input=none, value=0, summary=amount');
        $this->FLD('state', 'enum(draft=Чернова,active=Активиран,rejected=Оттеглена,closed=Приключен,stopped=Спряно)', 'caption=Състояние,input=none,width=8em');
        $this->FLD('details', 'blob(serialize,compress)', 'caption=Данни,input=none');
        $this->FLD('closedOn', 'datetime', 'input=none');
        
        $this->FLD('dealerId', "user(roles=ceo|sales|pos,allowEmpty)", 'caption=Търговец,mandatory');
    }
    
    
    /**
     * Подготовка на формата за добавяне
     */
    protected static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $data->form->setReadOnly('pointId');
        
        if(haveRole('pos,sales')){
            $data->form->setDefault('dealerId', core_Users::getCurrent());
        }
    }
    
    
    /**
     *  Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->query->orderBy('#createdOn', 'DESC');
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
        $row->to = dt::mysql2verbal($rec->details['receipts'][count($rec->details['receipts']) - 1]->createdOn, 'd.m.Y H:i');
        
        if ($fields['-single']) {
            $pointRec = pos_Points::fetch($rec->pointId);
            $row->storeId = store_Stores::getHyperLink($pointRec->storeId, true);
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
        if (count($reportData['receiptDetails'])) {
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
        // Рендираме продажбите
        $tpl->append($mvc->renderListTable($data->rec->details), 'SALES');
        if ($data->rec->details->pager) {
            $tpl->append($data->rec->details->pager->getHtml(), 'SALE_PAGINATOR');
        }
        
        // Рендираме обобщената информация за касиерите
        if (count($data->row->statisticArr)) {
            $block = $tpl->getBlock('ROW');
            
            foreach ($data->row->statisticArr as $statRow) {
                $rowTpl = clone $block;
                $rowTpl->placeObject($statRow);
                
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
        arr::sortObjects($detail->receiptDetails, 'action');
        
        // Табличната информация и пейджъра на плащанията
        $detail->listFields = "value=Действие,pack=Мярка, quantity=Количество, amount=Сума ({$data->row->baseCurrency})";
        $detail->rows = $detail->receiptDetails;
        $mvc->prepareDetail($detail);
        $data->rec->details = $detail;
        
        $receiptIds = arr::extractValuesFromArray($detail->receipts, 'id');
        $data->row->receiptIds = array();
        foreach ($receiptIds as $receiptId){
            $data->row->receiptIds[$receiptId] = pos_Receipts::getHyperlink($receiptId)->getContent();
        }

        if(count($data->row->receiptIds)){
            $data->row->receiptIds = implode(' <span class="quiet small" style="display: inline-block;margin: 0 3px;"> | </span> ', $data->row->receiptIds);
        }
        
        /*
    	 * Обработваме статистиката за това всеки касиер, колко е продал
    	 */
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        $data->row->statisticArr = array();
        foreach ($detail->receipts as $receiptRec) {
            if (!array_key_exists($receiptRec->createdBy, $data->row->statisticArr)) {
                $data->row->statisticArr[$receiptRec->createdBy] = (object) array('receiptBy' => crm_Profiles::createLink($receiptRec->createdBy),
                    'receiptTotal' => $receiptRec->total);
            } else {
                $data->row->statisticArr[$receiptRec->createdBy]->receiptTotal += $receiptRec->total;
            }
        }
        
        foreach ($data->row->statisticArr as &$rRec) {
            $rRec->receiptTotal = $Double->toVerbal($rRec->receiptTotal);
        }
    }
    
    
    /**
     * Инстанциране на пейджъра и модификации по данните спрямо него
     *
     * @param stdClass $detail - Масив с детайли на отчета (плащания или продажби)
     */
    public function prepareDetail(&$detail)
    {
        $newRows = array();
        
        // Инстанцираме пейджър-а
        $Pager = cls::get('core_Pager', array('itemsPerPage' => $this->listDetailsPerPage));
        $Pager->itemsCount = count($detail->rows);
        $Pager->calc();
        
        // Добавяме всеки елемент отговарящ на условието на пейджъра в нов масив
        if ($detail->rows) {
             
             // Подготвяме поле по което да сортираме
            foreach ($detail->rows as &$value) {
                if ($value->action == 'sale') {
                    $value->sortString = mb_strtolower(cat_Products::fetchField($value->value, 'name'));
                }
            }
            
            usort($detail->rows, array($this, 'sortResults'));
            
            // Обръщаме във вербален вид
            $start = $Pager->rangeStart;
            $end = $Pager->rangeEnd - 1;
            $rowsCnt = count($detail->rows);
            for ($i = 0; $i < $rowsCnt; $i++) {
                if ($i >= $start && $i <= $end) {
                    $keys = array_keys($detail->rows);
                    $newRows[] = $this->getVerbalDetail($detail->rows[$keys[$i]]);
                }
            }
            
            // Заместваме стария масив с новия филтриран
            $detail->rows = $newRows;
            
            // Добавяме пейджъра
            $detail->pager = $Pager;
        }
    }
    
    
    /**
     * Сортира масива първо по код после по сума (ако кодовете съвпадат)
     */
    private function sortResults($a, $b)
    {
        return strcmp($a->sortString, $b->sortString);
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
     * Функция обработваща детайл на репорта във вербален вид
     *
     * @param stdClass $rec-> запис на продажба или плащане
     *
     * @return stdClass $row-> вербалния вид на записа
     */
    private function getVerbalDetail($obj)
    {
        $row = new stdClass();
        
        $double = core_Type::getByName('double(decimals=2)');
        $currencyCode = acc_Periods::getBaseCurrencyCode($obj->date);
        $row->quantity = "<span style='float:right'>" . $double->toVerbal($obj->quantity) . '</span>';
        if ($obj->action == 'sale') {
            
            // Ако детайла е продажба
            $row->ROW_ATTR['class'] = 'report-sale';
            
            $row->pack = cat_UoM::getShortName($obj->pack);
            deals_Helper::getPackInfo($row->pack, $obj->value, $obj->pack, $obj->quantityInPack);
            
            $row->value = cat_Products::getHyperlink($obj->value, true);
            $obj->amount *= 1 + $obj->param;
        } else {
            
            // Ако детайла е плащане
            $row->pack = $currencyCode;
            $value = ($obj->value != -1) ? cond_Payments::getTitleById($obj->value) : tr('В брой');
            $row->value = "<b>" . tr('Плащане') . "</b>: &nbsp;<i>{$value}</i>";
            $row->ROW_ATTR['class'] = 'report-payment';
            unset($row->quantity);
            
            if($obj->value != '-1'){
                $obj->amount = cond_Payments::toBaseCurrency($obj->value, $obj->amount, $obj->date);
            }
        }
        
        $row->value = "<span style='white-space:nowrap;'>{$row->value}</span>";
        $row->amount = "<span style='float:right'>" . $double->toVerbal($obj->amount) . '</span>';
        
        return $row;
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow($id)
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
                $index = implode('|', array($obj->action, $obj->pack, $obj->contragentClassId, $obj->contragentId, $obj->value));
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
     * Оттегля всички празни чернови бележки в дадена точка от даден касиер
     *
     * @param int $pointId - ид на точка
     */
    private function rejectEmptyReceipts($rec)
    {
        $rQuery = pos_Receipts::getQuery();
        $rQuery->where("#pointId = {$rec->pointId} AND #state = 'draft' AND #total = 0");
        
        // Оттегляме само тези чернови чиято дата е преди тази на последната активна бележка
        $lastReceiptDate = $rec->details['receipts'][count($rec->details['receipts']) - 1]->createdOn;
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
            foreach ($rec->details['receipts'] as $receiptRec) {
                $state = pos_Receipts::fetchField($receiptRec->id, 'state');
                if ($state == $nextState) {
                    continue;
                }
                
                $receiptRec->modifiedBy = core_Users::getCurrent();
                $receiptRec->modifiedOn = dt::now();
                $receiptRec->state = $nextState;
                pos_Receipts::save($receiptRec, 'state,modifiedOn,modifiedBy');
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
                'title' => static::getRecTitle($rec),
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
        if (!pos_Receipts::fetch("#pointId = {$pointId} AND #state = 'waiting'")) {
            
            return false;
        }
        
        // Ако има неприключена започната бележка в тачката от касиера, също не може да се направи отчет
        if (pos_Receipts::fetch("#pointId = {$pointId} AND #total != 0 AND #state = 'draft'")) {
            
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
        
        $valior = dt::verbal2mysql($rec->activatedOn, false);
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
                    $r->sellCost = $dRec->amount / $r->quantity;
                } else {
                    $r->sellCost = 0;
                    wp($r, $rec);
                }
                
                $dealerId = $rec->dealerId;
                setIfNot($dealerId, $rec->createdBy);
                $r->dealerId = $dealerId;
                
                // Изчисляване на себестойността на артикула
                $productRec = cat_Products::fetchField($dRec->value, 'isPublic,code');
                if ($productRec->code == 'surcharge') {
                    $r->primeCost = 0;
                } else {
                    $r->primeCost = cat_Products::getPrimeCost($dRec->value, $dRec->pack, $r->quantity, $valior, price_ListRules::PRICE_LIST_COST);
                }
                
                $res[] = $r;
            }
        }
        
        return $res;
    }
}
