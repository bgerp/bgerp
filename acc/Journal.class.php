<?php


/**
 * Мениджър Журнал
 *
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class acc_Journal extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Журнал със счетоводни транзакции';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_State, plg_RowTools2, plg_Search,acc_Wrapper, plg_Sorting';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, valior, docType, totalAmount';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'acc_JournalDetails';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Счетоводна статия';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,acc';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,acc';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,acc';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'acc/tpl/SingleLayoutJournal.shtml';
    
    
    /**
     * Полета за търсене
     */
    public $searchFields = 'reason';
    
    
    /**
     * Кеш на афектираните пера
     */
    public $affectedItems = array();
    
    
    /**
     * Кои полета да се извличат при изтриване
     */
    public $fetchFieldsBeforeDelete = 'id';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        // Ефективна дата
        $this->FLD('valior', 'date', 'caption=Вальор,mandatory');
        $this->FLD('docType', 'class(interface=acc_TransactionSourceIntf)', 'caption=Документ,input=none');
        $this->FLD('docId', 'int', 'input=none,column=none');
        $this->FLD('totalAmount', 'double(decimals=2)', 'caption=Оборот,input=none');
        $this->FLD('reason', 'varchar', 'caption=Основание,input=none');
        $this->FLD('state', 'enum(draft=Чернова,active=Активна,revert=Сторнирана)', 'caption=Състояние,input=none');
        
        $this->setDbUnique('docId,docType,state');
        $this->setDbIndex('valior');
        $this->setDbIndex('state,createdOn');
        $this->setDbIndex('createdOn');
    }
    
    
    /**
     * Малко манипулации след подготвянето на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->class = 'simpleForm';
        $data->listFilter->FNC('dateFrom', 'date', 'input,caption=От');
        $data->listFilter->FNC('dateTo', 'date', 'input,caption=До');
        $data->listFilter->FNC('accounts', 'acc_type_Accounts', 'input,caption=Сметки');
        
        $data->listFilter->setDefault('dateFrom', date('Y-m-01'));
        $data->listFilter->setDefault('dateTo', date('Y-m-t', strtotime(dt::now())));
        
        $data->listFilter->showFields = 'search,dateFrom,dateTo,accounts';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list', 'show' => Request::get('show')), 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Активиране на филтъра
        $data->listFilter->input(null, 'silent');
        
        $data->query->orderBy('id', 'DESC');
        
        if ($rec = $data->listFilter->rec) {
            
            // Ако се търси по стринг
            if ($rec->search) {
                
                // и този стринг отговаря на хендлър на документ в системата
                $doc = doc_Containers::getDocumentByHandle($rec->search);
                
                if (is_object($doc)) {
                    
                    // Показваме документа и другите документи в нишката му
                    $data->query->orWhere("#docId = '{$doc->that}' AND #docType = '{$doc->getClassId()}'");
                    
                    $chain = $doc->getDescendants();
                    
                    if (countR($chain)) {
                        foreach ($chain as $desc) {
                            $data->query->orWhere("#docId = '{$desc->that}' AND #docType = '{$desc->getClassId()}'");
                        }
                    }
                }
            }
            
            // Филтер по начална дата
            if ($rec->dateFrom) {
                $data->query->where(array("#valior >= '[#1#]'", $rec->dateFrom));
            }
            
            // Филтър по крайна дата
            if ($rec->dateTo) {
                $data->query->where(array("#valior <= '[#1#] 23:59:59'", $rec->dateTo));
            }
            
            // Ако има филтър по сметки
            if (!empty($rec->accounts)) {
                $accounts = implode(',', keylist::toArray($rec->accounts));
                $dQuery = acc_JournalDetails::getQuery();
                $dQuery->where("#debitAccId IN ({$accounts}) || #creditAccId IN ({$accounts})");
                $dQuery->show('journalId');
                $dQuery->groupBy('journalId');
                $foundIds = arr::extractValuesFromArray($dQuery->fetchAll(), 'journalId');
                
                // Само записите, в чиито детайли участват избраните сметки
                if (countR($foundIds)) {
                    $foundIds = implode(',', $foundIds);
                    $data->query->where("#id IN ({$foundIds})");
                } else {
                    $data->query->where('1 = 2');
                }
            }
        }
    }
    
    
    /**
     * След всеки запис в журнала
     *
     * @param core_Mvc $mvc
     * @param int      $id
     * @param stdClass $rec
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $fields = null, $mode = null)
    {
        if ($rec->state != 'draft') {
            $fields = arr::make($fields, true);
            
            // Инвалидираме балансите, които се променят от този вальор
            acc_Balances::alternate($rec->valior, $rec->docType, $rec->docId);
        }
        
        // След активиране, извличаме всички записи от журнала и запомняме кои пера са вкарани
        if ($rec->state == 'active') {
            $dQuery = acc_JournalDetails::getQuery();
            $dQuery->where("#journalId = {$rec->id}");
            
            while ($dRec = $dQuery->fetch()) {
                foreach (array('debitItem1', 'debitItem2', 'debitItem3', 'creditItem1', 'creditItem2', 'creditItem3') as $item) {
                    if (isset($dRec->{$item})) {
                        $mvc->affectedItems[$dRec->{$item}] = $dRec->{$item};
                        acc_Items::updateEarliestUsedOn($dRec->{$item}, $rec->valior);
                    }
                }
            }
        }
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    protected static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
        $row->totalAmount = '<strong>' . $row->totalAmount . '</strong>';
        
        if ($rec->docType && cls::load($rec->docType, true)) {
            $mvc = cls::get($rec->docType);
            $doc = new core_ObjectReference($rec->docType, $rec->docId);
            
            if ($doc) {
                try {
                    $row->docType = $doc->getLink();
                } catch (core_exception_Expect $e) {
                    $row->docType = "{$rec->docType}:{$rec->docId}";
                }
            }
        }
        
        if ($fields['-list']) {
            $dQuery = acc_JournalDetails::getQuery();
            $dQuery->where("#journalId = {$rec->id}");
            $details = $dQuery->fetchAll();
            
            $row->docType = $row->docType . " <a href=\"javascript:toggleDisplay('{$rec->id}inf')\"  style=\"background-image:url(" . sbf('img/16/toggle1.png', "'") . ');" class=" plus-icon more-btn"> </a>';
            
            $row->docType .= "<ol style='margin-top:2px;margin-top:2px;margin-bottom:2px;color:#888;display:none' id='{$rec->id}inf'>";
            
            foreach ($details as $decRec) {
                $dAcc = acc_Accounts::getNumById($decRec->debitAccId);
                $cAcc = acc_Accounts::getNumById($decRec->creditAccId);
                $row->docType .= '<li>' . tr('Дебит') . ": <b>{$dAcc}</b> <span style='margin-left:20px'>" . tr('Кредит') . ": <b>{$cAcc}</b></span></li>";
            }
            $row->docType .= '</ol>';
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на титлата в единичния изглед
     */
    protected static function on_AfterPrepareSingleTitle($mvc, &$res, $data)
    {
        $data->title .= ' (' . $mvc->getVerbal($data->rec, 'state') . ')';
    }
    
    
    /**
     * Контиране на счетоводен документ.
     *
     * Документа се задава чрез двойката параметри в URL `docId` и `docType`. Класът, зададен
     * в `docType` трябва да поддържа интерфейса `acc_TransactionSourceIntf`
     *
     * @param int   $docId   (от URL)
     * @param mixed $docType (от URL) ид или име на клас поддържащ интерфейса
     *                       `acc_TransactionSourceIntf`
     */
    public function act_Conto()
    {
        expect($docId = Request::get('docId', 'int'));
        expect($docClassId = Request::get('docType', 'class(interface=acc_TransactionSourceIntf)'));
        $mvc = cls::get($docClassId);
        $mvc->requireRightFor('conto', $docId);
        
        // Контиране на документа
        $mvc->conto($docId);
        
        // Редирект към сингъла
        $retUrl = getRetUrl();
        $redirectUrl = !empty($retUrl) ? $retUrl : $mvc->getSingleUrlArray($docId);
        
        return new Redirect($redirectUrl);
    }
    
    
    /**
     * Сторниране на счетоводен документ.
     *
     * Документа се задава чрез двойката параметри в URL `docId` и `docType`. Класът, зададен
     * в `docType` трябва да поддържа интерфейса `acc_TransactionSourceIntf`
     *
     * @param int   $docId   (от URL)
     * @param mixed $docType (от URL) ид или име на клас поддържащ интерфейса
     *                       `acc_TransactionSourceIntf`
     */
    public function act_Revert()
    {
        expect($docId = Request::get('docId', 'int'));
        expect($docClassId = Request::get('docType', 'class(interface=acc_TransactionSourceIntf)'));
        
        $mvc = cls::get($docClassId);
        
        $mvc->requireRightFor('revert', $docId);
        
        if (!$result = self::rejectTransaction($docClassId, $docId)) {
            core_Message::redirect(
                'Невъзможно сторниране',
                'page_Error',
                null,
                getRetUrl()
            );
        }
        
        list($docClassId, $docId) = $result;
        
        // Записваме, че потребителя е разглеждал този списък
        $mvc->logWrite('Сторниране на документ', $docId);
        
        return new Redirect(array($docClassId, 'single', $docId));
    }
    
    
    /**
     * Записва счетоводната транзакция, породена от документ
     *
     * Документът ($docClassId, $docId) ТРЯБВА да поддържа интерфейс acc_TransactionSourceIntf
     *
     * @param mixed      $docClassId     - класа на документа
     * @param int|object $docId          - ид на документа
     * @param bool       $notifyDocument - да нотифицира ли документа, че транзакцията е приключена
     */
    public static function saveTransaction($docClassId, $docId, $notifyDocument = true)
    {
        $mvc = cls::get($docClassId);
        $docClass = cls::getInterface('acc_TransactionSourceIntf', $mvc);
        $docRec = $mvc->fetchRec($docId);
        
        try {
            Mode::push('saveTransaction', true);
            $transaction = $mvc->getValidatedTransaction($docRec);
            Mode::pop('saveTransaction');
        } catch (acc_journal_Exception $ex) {
            $tr = $docClass->getTransaction($docRec->id);
            reportException($ex);
            $mvc->logErr("Грешка при контиране на документ", $docRec->id);
            error($ex->getMessage(), $tr, $ex->getMessage());
        }
        
        $transaction->rec->docType = $mvc->getClassId();
        $transaction->rec->docId = $docRec->id;
        
        if ($success = $transaction->save()) {
            
            // Нотифицира мениджъра на документа за успешно приключилата транзакция, ако сме задали
            if ($notifyDocument === true) {
                $docClass->finalizeTransaction($docRec);
            }
            
            // Нотифицираме документа че транзакцията му е записана
            $mvc->invoke('AfterSaveJournalTransaction', array($success, $docRec));
        }
        
        return $success;
    }
    
    
    /**
     * Валидира един по един списък от редове на транзакция
     *
     * @param stdClass $transaction
     *
     * @return bool
     */
    protected static function validateTransaction($transaction)
    {
        $transaction = new acc_journal_Transaction($transaction);
        
        return $transaction->check();
    }
    
    
    /**
     * Отменя контирането на счетоводен документ
     *
     * Ако периода, в който е бил контиран документа е отворен - транзакцията се изтрива от
     * журнала. Ако периода е приключен (т.е. затворен), то в текущия период се създава нова
     * транзакция, обратна на тази, генерирана при контирането на документа.
     *
     * @param int $docClassId
     * @param int $docId
     *
     * @return bool
     */
    public static function rejectTransaction($docClassId, $docId)
    {
        if (!($rec = self::fetchByDoc($docClassId, $docId))) {
            
            return false;
        }
        
        if (!($periodRec = acc_Periods::fetchByDate($rec->valior))) {
            
            return false;
        }
        
        if ($periodRec->state == 'closed') {
            
            return acc_Articles::createReverseArticle($rec);
        }
        
        return static::deleteTransaction($docClassId, $docId);
    }
    
    
    /**
     * Връща записа в журнала породен от подадения документ
     *
     * @param mixed $doc   - документа
     * @param int   $docId - ид на документа
     *
     * @return stdClass|FALSE - намерения запис
     */
    public static function fetchByDoc($doc, $docId)
    {
        $docClassId = cls::get($doc)->getClassId();
        
        return self::fetch("#docId = {$docId} AND #docType = {$docClassId}");
    }


    /**
     * Изтриване на транзакцията
     *
     * @param mixed $docClassId         - документ
     * @param int $docId                - ид на документ
     * @param stdClass|null $deletedRec - изтрития запис
     * @return array
     */
    public static function deleteTransaction($docClassId, $docId, &$deletedRec = null)
    {
        $docClassId = cls::get($docClassId)->getClassId();
        $query = static::getQuery();
        $query->where("#docId = {$docId} AND #docType = {$docClassId}");
        
        // Изтриваме всички записи направени в журнала от документа
        while ($rec = $query->fetch()) {
            // Запомняне на изтрития запис от журнала + детайлите
            $deletedRec = $rec;
            $dQuery = acc_JournalDetails::getQuery();
            $dQuery->where("#journalId = {$rec->id}");
            $dQuery->orderBy('id', 'ASC');
            $deletedRec->_details = $dQuery->fetchAll();

            acc_JournalDetails::delete("#journalId = {$rec->id}");
            static::delete($rec->id);
            
            // Инвалидираме балансите, които се променят от този вальор
            acc_Balances::alternate($rec->valior, $docClassId, $docId);
        }
        
        // Нотифицираме документа че транзакцията му е записана
        $DocClass = cls::get($docClassId);
        $DocClass->invoke('AfterTransactionIsDeleted', array($DocClass->fetchRec($docId)));
        
        return array($docClassId, $docId);
    }
    
    
    /**
     * След изтриване на запис
     */
    protected static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {
        foreach ($query->getDeletedRecs() as $id => $rec) {
            
            // Ако вече са заопашени ид-та за обновяване, махаме ги от опашката след като са изтрити
            if (isset($mvc->updateQueue[$id])) {
                unset($mvc->updateQueue[$id]);
            }
        }
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    protected static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        // Думите за търсене са името на документа-основания
        $object = new core_ObjectReference($rec->docType, $rec->docId);
        
        if ($object->haveInterface('doc_DocumentIntf')) {
            $title = $object->getDocumentRow()->title;
            $res .= ' ' . plg_Search::normalizeText($title);
        }
    }
    
    
    /**
     * Метод връщащ урл-то за контиране на документ
     *
     * @param core_Manager $mvc   - мениджър
     * @param int          $recId - ид на записа, който ще се контира
     */
    public static function getContoUrl(core_Manager $mvc, $recId)
    {
        $contoUrl = array('acc_Journal',
            'conto',
            'docId' => $recId,
            'docType' => $mvc->className,
            'ret_url' => true
        );
        
        return $contoUrl;
    }
    
    
    /**
     * Ф-я извикваща се след генерирането на баланса, чисти всички замърсени данни
     */
    public static function clearDrafts()
    {
        // Избиране на всички чернови в журнала, По стари от 5 минути
        $query = self::getQuery();
        $query->where("#state = 'draft'");
        $before5m = dt::addSecs(-5*60);
        $query->where("#createdOn < '{$before5m}'");
        
        while ($rec = $query->fetch()) {
            try {
                $document = new core_ObjectReference($rec->docType, $rec->docId);
            } catch (core_exception_Expect $e) {
                continue;
            }
            
            // Ако състоянието на документа е чернова
            $state = $document->fetchField('state', false);
            
            if ($state == 'draft') {
                
                // Изтриване на замърсените данни
                acc_JournalDetails::delete("#journalId = {$rec->id}");
                acc_Journal::delete("#id = {$rec->id}");
                
                // Логваме в журнала
                self::logWrite('Изтрит ред от журнала на документ', $rec->id);
            }
        }
    }
    
    
    /**
     * Връща всички записи от журнала където в поне един ред на кредита и дебита на една сметка
     * се среща зададеното перо
     *
     * @param mixed    $item        - масив с име на мениджър и ид на запис, или ид на перо
     * @param stdClass $itemRec     - върнатия запис на перото
     * @param bool     $showAllRecs - дали да се връщат само записите с перата или всички записи от д-те в чиято транзакция
     *                              участва посоченото перо
     *
     * @return array $res - извлечените движения
     */
    public static function getEntries($item, &$itemRec = null, $showAllRecs = false)
    {
        expect($item);
        
        // Ако е подаден масив, опитваме се да намерим кое е перото
        if (is_array($item)) {
            $Class = cls::get($item[0]);
            expect($Class->fetch($item[1]));
            $item = acc_Items::fetchItem($Class->getClassId(), $item[1]);
            
            if (!$item) {
                
                return;
            }
        }
        
        // Извличаме ид-та на журналите, имащи ред с участник това перо
        expect($itemRec = acc_Items::fetchRec($item));
        $jQuery = acc_JournalDetails::getQuery();
        
        acc_JournalDetails::filterQuery($jQuery, null, null, null, $itemRec->id);
        
        // Искаме вальора да е след първия ден от периода, в който е датата на създаване на перото за което търсим
        $fromDate = dt::mysql2verbal($itemRec->earliestUsedOn, 'Y-m-01');
        $fromDate = dt::verbal2mysql($fromDate, false);
        $jQuery->where("#valior >= '{$fromDate}'");
        
        if ($showAllRecs === false) {
            
            return $jQuery->fetchAll();
        }
        
        $jIds = array();
        $jQuery->show('journalId');
        
        while ($jRec = $jQuery->fetch()) {
            $jIds[$jRec->journalId] = $jRec->journalId;
        }
        
        $now = dt::now();
        
        // Извличаме всички транзакции на намерените журнали
        $jQuery = acc_JournalDetails::getQuery();
        $jQuery->EXT('docType', 'acc_Journal', 'externalKey=journalId');
        $jQuery->EXT('docId', 'acc_Journal', 'externalKey=journalId');
        $jQuery->where("#createdOn BETWEEN '{$itemRec->createdOn}' AND '{$now}'");
        $jQuery->orderBy('#id', 'ASC');
        
        if (countR($jIds)) {
            $jQuery->in('journalId', $jIds);
            
            return $jQuery->fetchAll();
        }
        
        // Връщаме извлечените записи
        return $jIds;
    }
    
    
    /**
     * Афектираните пера, нотифицират мениджърите си
     */
    public static function on_Shutdown($mvc)
    {
        // Всяко афектирано перо, задейства ивент в мениджъра си
        if (countR($mvc->affectedItems)) {
            
            // Увеличаваме времето за изпълнение според броя афектирани пера
            $timeLimit = countR($mvc->affectedItems) * 10;
            core_App::setTimeLimit($timeLimit);
            
            foreach ($mvc->affectedItems as $rec) {
                acc_Items::notifyObject($rec);
            }
        }
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
        if (!$rec) {
            
            return;
        }
        
        $rec->totalAmount = 0;
        
        $dQuery = acc_JournalDetails::getQuery();
        $dQuery->where("#journalId = {$rec->id}");
        $dQuery->show('amount');
        
        while ($dRec = $dQuery->fetch()) {
            $rec->totalAmount += $dRec->amount;
        }
        
        $id = $this->save_($rec, 'totalAmount');
        
        // Нотифицираме документа породил записа в журнала, че журнала му е променен
        if (cls::load($rec->docType, true)) {
            cls::get($rec->docType)->invoke('AfterJournalUpdated', array($rec->docId, $rec->id));
        }
        
        return $id;
    }
    
    
    /**
     * Метод реконтиращ всички документи в посочените дати съдържащи определени сметки
     * Намира всички документи, които имат записи в журнала. Изтриват им се транзакциите
     * и се записват на ново
     *
     * @param mixed $accSysIds - списък от систем ид-та на сметки
     * @param datetime  $from      - от коя дата
     * @param datetime  $to        - до коя дата
     *
     * @return int - колко документа са били реконтирани
     */
    private function recontoAll($accSysIds, $from = null, $to = null, $types = array())
    {
        // Дигаме времето за изпълнение на скрипта
        core_App::setTimeLimit(1500);
        
        // Филтрираме записите в журнала по подадените параметри
        $to = (!$to) ? dt::today() : $to;
        $query = acc_JournalDetails::getQuery();
        acc_JournalDetails::filterQuery($query, $from, $to);
        
        $accSysIds = array_values($accSysIds);
        foreach ($accSysIds as $index => $sysId) {
            $or = ($index == 0) ? false : true;
            $acc = acc_Accounts::getRecBySystemId($sysId);
            $query->where("#debitAccId = {$acc->id} OR #creditAccId = {$acc->id}", $or);
        }
        
        if (countR($types)) {
            $query->in('docType', $types);
        }
        
        // Групираме записите по документи
        $query->show('docId,docType,valior');
        $query->groupBy('docId,docType');
        $recs = $query->fetchAll();

        // За всеки запис ако има
        $count = 0;
        $deletedRecs = array();
        if (countR($recs)) {
            foreach ($recs as $rec) {
                
                // Ако е в затворен период, пропускаме го
                $periodState = acc_Periods::fetchByDate($rec->valior)->state;
                if ($periodState == 'closed') {
                    continue;
                }
                
                // Изтриваме му транзакцията
                $deletedRec = null;
                acc_Journal::deleteTransaction($rec->docType, $rec->docId, $deletedRec);
                $deletedRecs[$rec->docType][$rec->docId] = $deletedRec;
            }

            // Преизчисляване на балансите
            cls::get('acc_Balances')->recalc();
            foreach ($recs as $rec) {
                try{
                    if(is_object($deletedRecs[$rec->docType][$rec->docId])){
                        Mode::push('recontoWithCreatedOnDate', $deletedRecs[$rec->docType][$rec->docId]->createdOn);
                    }
                    $this->recalcDoc($rec->docType, $rec->docId, $rec->valior);
                    $count++;
                    if(is_object($deletedRecs[$rec->docType][$rec->docId])){
                        Mode::pop('recontoWithCreatedOnDate');
                    }
                } catch(core_exception_Expect $e){
                    if(is_object($deletedRecs[$rec->docType][$rec->docId])){
                        acc_Journal::restoreDeleted($rec->docType, $rec->docId, $deletedRecs[$rec->docType][$rec->docId], $deletedRecs[$rec->docType][$rec->docId]->_details);
                    }
                    wp($e);
                }
            }
        }

        if (countR($types)) {
            foreach ($types as $type){

                // Добавен фикс ако има контиращи документи без контировка поради някаква причина
                $Doc = cls::get($type);
                $query = $Doc->getQuery();

                // Ако е приключване на сделка, да се взимат само тези записи от този мениджър
                if($Doc instanceof deals_ClosedDeals){
                    $query->where("#classId = {$type}");
                }

                $query->EXT('journalId', 'acc_Journal', array('externalName' => 'id', 'onCond' => "#acc_Journal.docId = #id AND #acc_Journal.docType = {$type}", 'join' => 'right'));
                $query->where("#journalId IS NULL AND (#state = 'active' || #state = 'closed')");
                $query->where("#{$Doc->valiorFld} BETWEEN '{$from}' AND '{$to}'");
                $query->show("id,{$Doc->valiorFld},state,journalId");

                // Да се реконтират и те
                while($dRec = $query->fetch()){
                    try{
                        $this->recalcDoc($Doc, $dRec->id, $dRec->{$Doc->valiorFld});
                        $count++;
                    } catch(core_exception_Expect $e){
                        wp($e);
                    }
                }
            }
        }

        // Реконтираните документи
        return $count;
    }


    /**
     * Рекондира един документ
     *
     * @param $docType
     * @param $docId
     * @param null $valior
     */
    private function recalcDoc($docType, $docId, $valior = null)
    {
        $Document = cls::get($docType);
        if(empty($valior)){
            $valior = $Document->fetchField($docId, $Document->valiorFld);
        }

        // Ако е в затворен период, пропускаме го
        $periodState = acc_Periods::fetchByDate($valior)->state;
        if ($periodState == 'closed') return;

        // Преконтираме документа
        Mode::push('recontoTransaction', true);
        acc_Journal::saveTransaction($Document, $docId, false);
        Mode::pop('recontoTransaction');
        $Document->logWrite('Реконтиране от настройките', $docId);
    }


    /**
     * Екшън реконтиращ всички документи където участва дадена сметка
     * в даден интервал
     */
    public function act_Reconto()
    {
        requireRole('debug');
        
        $form = cls::get('core_Form');
        $form->title = tr('Реконтиране на документи');
        $form->FLD('from', 'date', 'caption=От,mandatory');
        $form->FLD('to', 'date', 'caption=До,mandatory');
        $form->FLD('accounts', 'acc_type_Accounts', 'caption=Сметки');
        $form->FLD('types', 'keylist(mvc=core_Classes)', 'caption=Документи');

        $form->setDefault('from', Mode::get('recontoJournalLastDateFrom'));
        $form->setDefault('to', Mode::get('recontoJournalLastDateTo'));
        $form->setSuggestions('types', core_Classes::getOptionsByInterface('acc_TransactionSourceIntf', 'title'));
        
        $form->input();
        
        if ($form->isSubmitted()) {
            $rec = &$form->rec;
            
            // Трябва баланса да е преизчислен за да продължим
            if (core_Locks::isLocked(acc_Balances::saveLockKey)) {
                
                return followRetUrl(null, tr('|Балансът се преизчислява в момента. Моля, изчакайте!'));
            }
            
            if ($rec->from > $rec->to) {
                $form->setError('from', 'Началната дата трябва да е по-малка от крайната');
            }
            
            if (empty($rec->accounts) && empty($rec->types)) {
                $form->setError('accounts,types', 'Трябва да е избрано, поне едно от двете полета');
            }
            
            if (!$form->gotErrors()) {
                $accounts = keylist::toArray($rec->accounts);
                $types = type_Keylist::toArray($rec->types);
                foreach ($accounts as $id => $accId) {
                    $accounts[$id] = acc_Accounts::fetchField($accId, 'systemId');
                }
                $res = $this->recontoAll($accounts, $rec->from, $rec->to, $types);
                $this->logDebug("Реконтирани са {$res} документа");

                Mode::setPermanent('recontoJournalLastDateFrom', $rec->from);
                Mode::setPermanent('recontoJournalLastDateTo', $rec->to);

                return followRetUrl(null, tr("|Реконтирани са|* {$res} |документа|*"), 'warning');
            }
        }
        
        $form->toolbar->addSbBtn('Реконтиране', 'save', 'ef_icon = img/16/arrow_refresh.png, title = Реконтиране');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        $tpl = $this->renderWrapping($form->renderHtml());
        
        // Записваме, че потребителя е разглеждал този списък
        $this->logRead('Разглеждане на реконтиране на документ', $form->rec->id);
        
        return $tpl;
    }
    
    
    /**
     * Връща сумите от журнала за посочената кореспонденция
     *
     * @param datetime   $from        - начална дата
     * @param datetime   $to          - крайна дата
     * @param string $debitSysId  - систем ид на сметка в дебита
     * @param string $creditSysId - систем ид на сметка в кредита
     * @param array  $items       - масив със стойности на пера с ключове на коя позиция се намират (debitItem1, debitItem2 ... creditItem1 ....)
     *
     * @return stdClass $res - масив с сумарните стойностти
     *                  ->debitQuantity  - Обща сума на дебитното к-во
     *                  ->creditQuantity - Обща сума на кредитното к-во
     *                  ->amount         - Обща сума
     */
    public static function getJournalSums($from, $to, $debitSysId = null, $creditSysId = null, $items = array())
    {
        // Подготвяме заявката
        $dQuery = acc_JournalDetails::getQuery();
        acc_JournalDetails::filterQuery($dQuery, $from, $to);
        
        if ($debitSysId) {
            expect($debitAccId = acc_Accounts::fetchField(array("#systemId = '[#1#]'", $debitSysId), 'id'), "Няма сметка със систем ид {$debitAccId}");
            $dQuery->where("#debitAccId = {$debitAccId}");
        }
        
        if ($creditSysId) {
            expect($creditAccId = acc_Accounts::fetchField(array("#systemId = '[#1#]'", $creditSysId), 'id'), "Няма сметка със систем ид {$creditSysId}");
            $dQuery->where("#creditAccId = {$creditAccId}");
        }
        
        // Задаваме да се извлекат сумираните стойностти на някои полета
        $dQuery->XPR('sumDebitQuantity', 'double', 'ROUND(SUM(#debitQuantity), 2)');
        $dQuery->XPR('sumCreditQuantity', 'double', 'ROUND(SUM(#creditQuantity), 2)');
        $dQuery->XPR('sumAmount', 'double', 'ROUND(SUM(#amount), 2)');
        
        // Ако има зададени пера, допълваме ограниченията на заявката
        $itemsArr = arr::make($items, true);
        if (countR($itemsArr)) {
            foreach (array('debitItem1', 'debitItem2', 'debitItem3', 'creditItem1', 'creditItem2', 'creditItem3') as $el) {
                if (isset($itemsArr[$el])) {
                    $dQuery->where("#{$el} = {$itemsArr[$el]}");
                }
            }
        }
        
        $dRec = $dQuery->fetch();
        
        $res = new stdClass();
        $res->debitQuantity = $dRec->sumDebitQuantity;
        $res->creditQuantity = $dRec->sumCreditQuantity;
        $res->amount = $dRec->sumAmount;
        
        return $res;
    }
    
    
    /**
     * След подготовка на полетата
     */
    protected static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {
        $baseCode = acc_Periods::getBaseCurrencyCode();
        $data->listFields['totalAmount'] .= "|* ({$baseCode})";
    }
    
    
    /**
     * Реконтиране на документ по контейнер
     *
     * @param int $containerId - ид на контейнер
     * @param boolean $notify - да се нотифицра ли документа, че е реконтиран
     *
     * @return bool $success - резултат
     */
    public static function reconto($containerId, $notify = false)
    {
        // Оригиналния документ трябва да не е в затворен период
        $origin = doc_Containers::getDocument($containerId);
        if (acc_Periods::isClosed($origin->fetchField($origin->valiorFld))) {
            
            return;
        }
        
        // Изтриване на старата транзакция на документа
        acc_Journal::deleteTransaction($origin->getClassId(), $origin->that);
        
        // Записване на новата транзакция на документа
        Mode::push('recontoTransaction', true);
        $success = acc_Journal::saveTransaction($origin->getClassId(), $origin->that, $notify);
        Mode::pop('recontoTransaction');
        
        expect($success, $success);
        
        // Инвалидиране на кеш
        doc_DocumentCache::cacheInvalidation($containerId);
        doc_DocumentCache::invalidateByOriginId($containerId);
        
        return $success;
    }


    /**
     * Кои документи имат в журнала брой десетични символи над указания
     *
     * @param $valior                - от коя дата насетне
     * @param $number                - брой десетични знаци
     * @param $documentClasses       - от коит документи
     * @param mixed $journalFields   - кои полета
     * @return array $res            - контейнерите на намерените документи
     */
    public static function getDocsByDigitCounts($valior, $number, $documentClasses, $journalFields = 'debitQuantity,creditQuantity')
    {
        $classes = array();
        $documentClasses = arr::make($documentClasses, true);
        $journalFields = arr::make($journalFields, true);
        foreach ($documentClasses as $doc) {
            $classId = $doc::getClassId();
            $classes[$classId] = $classId;
        }

        $number += 1;
        $query = acc_JournalDetails::getQuery();
        $query->EXT('valior', 'acc_Journal', 'externalKey=journalId,externalName=valior');
        $query->EXT('docType', 'acc_Journal', 'externalKey=journalId,externalName=docType');
        $query->EXT('docId', 'acc_Journal', 'externalKey=journalId,externalName=docId');
        $query->where("#valior > '{$valior}'");

        $number += 1;
        $whereArr = array();
        foreach ($journalFields as $field){
            $query->XPR("{$field}Length", 'double', 'LENGTH(SUBSTR(#' . $field . ', INSTR(#' . $field . ',".")))');
            $whereArr[] = "#{$field}Length >= {$number}";
        }

        $where = implode(' OR ', $whereArr);
        $query->where($where);

        $query->in('docType', $classes);
        $query->groupBy('docType,docId');
        $query->show('docType, docId, journalId');

        $res = array();
        while($rec = $query->fetch()){
            $res[$rec->journalId] = cls::get($rec->docType)->fetchField($rec->docId, 'containerId');
        }

        return $res;
    }


    /**
     * Помощен метод проверяващ дали да се троуне грешка при опит за контиране
     *
     * @return bool
     */
    public static function throwErrorsIfFoundWhenTryingToPost()
    {
        return (Mode::is('saveTransaction') && !Mode::is('recontoTransaction') && !Mode::is('closedDealCall'));
    }


    /**
     * Възстановяване на изтрит журнал
     *
     * @param core_Mvc $mvc
     * @param int $id
     * @param stdClass $deletedRec
     * @param array $deletedDetails
     * @return void
     */
    public static function restoreDeleted($mvc, $id, $deletedRec, $deletedDetails)
    {
        // Ако документа няма журнал - възстановява се стария му
        if(!acc_Journal::fetchByDoc($mvc, $id)){
            unset($deletedRec->id);
            unset($deletedRec->_details);
            cls::get('acc_Journal')->save_($deletedRec);
            if(is_array($deletedDetails)){
                array_walk($deletedDetails, function($a) use ($deletedRec){unset($a->id); $a->journalId = $deletedRec->id;});
                cls::get('acc_JournalDetails')->saveArray($deletedDetails);
            }
        }
    }


    /**
     * Дебъг екшън за поправяне на документи
     */
    function act_fixDocsWithoutJournal()
    {
        requireRole('debug');
        $documents = static::getPostedDocumentsWithoutJournal();
        if(!countR($documents)) followRetUrl(null, "|Няма контирани документи без журнал|*!");

        $tpl = new core_ET("");
        foreach ($documents as $class => $res){
            $Class = cls::get($class);
            foreach ($res as $docId){
                $tpl->append("<li>");
                $tpl->append($Class->getLink($docId, 0));
                if($Class->haveRightFor('debugreconto', $docId)){
                    $tpl->append(" -> ");
                    $tpl->append(ht::createLink('', array($Class, 'debugreconto', $docId, 'ret_url' => true), 'Наистина ли желаете да реконтирате документа|*?', 'ef_icon=img/16/arrow_refresh.png,title=Реконтиране на документа'));
                }
            }
        }

        return $tpl;
    }


    /**
     * Кои са контираните документи без журнал
     *
     * @param array $res
     */
    private static function getPostedDocumentsWithoutJournal()
    {
        $documents = array('sales_Sales', 'purchase_Purchases', 'store_ShipmentOrders', 'store_Receipts', 'store_ConsignmentProtocols', 'sales_Invoices', 'purchase_Invoices', 'sales_Services', 'purchase_Services', 'acc_Articles', 'planning_DirectProductionNote', 'planning_ConsumptionNotes', 'planning_ReturnNotes', 'bank_IncomeDocuments', 'bank_SpendingDocuments', 'cash_Pko', 'cash_Rko');

        $res = array();
        foreach ($documents as $docId){
            $Class = cls::get($docId);

            $jRecs = array();
            $jQuery = acc_Journal::getQuery();
            $jQuery->where("#docType = {$Class->getClassId()}");
            $jQuery->show('id,docId');
            while($jRec = $jQuery->fetch()){
                $jRecs[$jRec->docId] = $jRec->docId;
            }

            if(countR($jRecs)){
                $query = $Class->getQuery();
                $query->in("state", array('closed', 'active'));
                $query->show('id');
                $query->notIn('id', $jRecs);
                if($Class instanceof sales_Sales || $Class instanceof purchase_Purchases){
                    $query->show('id,contoActions');
                    $query->where("#contoActions != '' AND #contoActions != 'activate'");
                }

                while($rec = $query->fetch()){
                    if($Class instanceof store_ShipmentOrders || $Class instanceof store_Receipts){
                        $Detail = cls::get($Class->mainDetail);
                        if(!$Detail->count("#{$Detail->masterKey} = {$rec->id}")) continue;
                    }
                    if(!array_key_exists($Class->className, $res)){
                        $res[$Class->className] = array();
                    }
                    $res[$Class->className][$rec->id] = $rec->id;
                }
            }
        }

        return $res;
    }


    /**
     * Екшън визуализиращ приключените сделки с активни пера
     */
    function act_findDeals()
    {
        requireRole('debug');

        $listId = acc_Lists::fetchBySystemId('deals')->id;
        $tpl = new core_ET("");
        foreach (array('purchase_Purchases', 'sales_Sales', 'findeals_Deals') as $class){
            $Class = cls::get($class);
            $classId = $Class->getClassId();

            $iQuery = acc_Items::getQuery();
            $iQuery->where("#state = 'active' AND #classId = {$classId}");
            $ids = arr::extractValuesFromArray($iQuery->fetchAll(), 'objectId');

            $query = $Class->getQuery();
            $query->in('id', $ids);
            $query->where("#state != 'active'");
            while($rec = $query->fetch()){
                $tpl->append("<li>");
                $tpl->append($Class->getLink($rec->id, 0));

                $handle = "#" . $Class->getHandle($rec->id);
                $itemLink = array('acc_Items', 'list', 'listId' => $listId, 'search' => $handle);
                $tpl->append(" -> " . ht::createLink('Перо', $itemLink));
            }
        }

        return $tpl;
    }


    /**
     * Репликираща миграция реконтиране на активни документи, който да може да се вика по разписание
     *
     * @param stdClass $data
     * @return void
     */
    public function callback_recontoActiveDocuments($data)
    {
        // Взимат се записите от документа с вальор след последния затворен период
        $lastClosedPeriod = acc_Periods::getLastClosed();
        $Class = cls::get($data->class);
        $query = $Class->getQuery();
        if(is_object($lastClosedPeriod)){
            $query->where("#{$Class->valiorFld} > '{$lastClosedPeriod->end}'");
        }

        // Ако е стигнато до определено ид - да се продължи след него
        if(isset($data->lastId)){
            $query->where("#id > '{$data->lastId}'");
        }

        // Само активните документи отговарящи на условията
        $query->where("#state = 'active'");
        foreach ($data->fields as $fld => $value){
            $query->where("#{$fld} = '{$value}'");
        }
        $query->orderBy('id', 'ASC');
        $query->limit(150);

        $recs = $query->fetchAll();
        $count = countR($recs);
        core_App::setTimeLimit($count * 0.4, false, 300);

        // Реконтиране
        $data->lastId = null;
        foreach ($recs as $rec){
            try{
                acc_Journal::reconto($rec->containerId);
                $Class->logWrite('Реконтиране от миграция', $rec->id);
            } catch(core_exception_Expect $e){
                reportException($e);
            }
            $data->lastId = $rec->id;
        }

        // Само репликиране на миграцията ако има все пак поне едно обходено ид
        if(isset($data->lastId)){
            $callOn = dt::addSecs(120);
            core_CallOnTime::setCall('acc_Journal', 'recontoActiveDocuments', $data, $callOn);
        }
    }
}
