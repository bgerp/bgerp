<?php 

/**
 * Лог на изпратените писма
 *
 * @category  bgerp
 * @package   blast
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.11
 */
class blast_EmailSend extends core_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Лог на изпращаните писма';
    
    
    /**
     * Кой има право да чете?
     */
    protected $canRead = 'ceo, blast, admin';
    
    
    /**
     * Кой има право да променя?
     */
    protected $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    protected $canAdd = 'no_one';
    
    
    /**
     * Кой може да го види?
     */
    protected $canView = 'ceo, blast, admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    protected $canList = 'ceo, blast, admin';
    
    
    /**
     * Кой може да го изтрие?
     */
    protected $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'blast_Wrapper, plg_Created';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'emailId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'email, sentOn, state, stateAct';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 20;
    
    
    public $canActivate = 'ceo, blast, admin';
    
    
    public $canStop = 'ceo, blast, admin';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'blast_ListSend';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('emailId', 'key(mvc=blast_Emails, select=subject)', 'caption=Списък');
        $this->FLD('data', 'blob(serialize, compress)', 'caption=Данни');
        $this->FLD('state', 'enum(pending,waiting=Чакащо,sended=Изпратено)', 'caption=Изпращане->Състояние, input=none');
        $this->FLD('stateAct', 'enum(active=Активно, stopped=Спряно)', 'caption=Изпращане->Действие, input=none, notNull');
        $this->FLD('sentOn', 'datetime(format=smartTime)', 'caption=Изпратено->На, input=none');
        $this->FLD('email', 'emails', 'caption=Изпратено->До, input=none');
        $this->FLD('hash', 'varchar(32)', 'caption=Хеш, input=none');
        $this->FLD('sendOrder', 'int(min=0)', 'caption=Изпращане->Ред, input=none, notNull, value=0');
        
        // @deprecated
        $this->FLD('dataId', 'int', 'caption=Списък данни');
        
        $this->setDbUnique('hash, emailId');
        $this->setDbIndex('emailId,state,stateAct,sendOrder,id');
        $this->setDbIndex('emailId,state,sentOn,sendOrder,id');
    }
    
    
    /**
     * Обновява списъка
     *
     * @param int   $emailId          - id на мастер (blast_Emails)
     * @param array $dataArr          - Масив с данните - ключ id на източника и стойност самите данни
     * @param array $emailFieldsArr   - Масив с полета, които се използва за имейл
     * @param array $negativeEmailArr - Масив с имейли, които да се изключат
     *
     * @return array - Броят на добавените и премахнатите записи
     */
    public static function updateList($emailId, $dataArr, $emailFieldsArr, $negativeEmailArr = array())
    {
        $canUnsubscribe = blast_Emails::fetchField($emailId, 'canUnsubscribe');

        $addCnt = $rCnt = $allCnt = 0;
        $existingHashes = array();
        $newRecs = array();

        // Запомняме съществуващите записи, за да подредим само тези, които реално ще бъдат добавени
        $existingQuery = self::getQuery();
        $existingQuery->where(array("#emailId = '[#1#]'", $emailId));
        $existingQuery->show('hash');

        while ($existingRec = $existingQuery->fetch()) {
            $existingHashes[$existingRec->hash] = true;
        }
        
        // Обхождаме масива с данните
        foreach ((array) $dataArr as $data) {
            $emailStr = '';
            
            $nRec = new stdClass();
            $nRec->emailId = $emailId;
            $nRec->data = $data;
            $nRec->state = 'waiting';
            $nRec->stateAct = 'active';
            
            // Ако са подадени полета, които да се използват за имейли
            if ($emailFieldsArr) {
                
                // Генерира стринг от всички имейли
                foreach ((array) $emailFieldsArr as $name => $type) {
                    if (isset($data[$name])) {
                        $emailStr .= $emailStr ? ', ' . $data[$name] : $data[$name];
                    }
                }
            }
            
            if (!$emailStr) {
                continue;
            }
            
            // Масив с всички възможни имейли
            $emailsArr = type_Emails::toArray($emailStr);
            $toEmail = '';
            
            // Добавяме първия имейл, който не е списъка с блокирани
            foreach ((array) $emailsArr as $email) {
                if ($canUnsubscribe != 'no') {
                    if (email_AddressesInfo::isBlocked($email)) {
                        continue;
                    }
                }

                // Ако е в отрицателния списък - просто го игнорираме
                $negativeKey = str::convertToFixedKey(mb_strtolower(trim($email)));
                if (isset($negativeEmailArr[$negativeKey]) || isset($negativeEmailArr[$email])) {
                    $nRec->email = $email;
                    
                    // Хеша на имейла
                    $nRec->hash = self::getHash($email);
                    
                    if (self::delete(array("#emailId = '[#1#]' AND #hash = '[#2#]'", $nRec->emailId, $nRec->hash))) {
                        $rCnt++;
                        unset($existingHashes[$nRec->hash]);
                    }
                    
                    continue;
                }
                
                $toEmail = $email;
                break;
            }
            
            // Ако няма имейл за добавяне
            if (!$toEmail) {
                continue;
            }
            
            // Добаваме стринга с имейлите
            $nRec->email = $toEmail;
            
            // Хеша на имейла
            $nRec->hash = self::getHash($toEmail);

            $allCnt++;

            // INSERT IGNORE би пропуснал тези записи. Не трябва да участват в подреждането.
            if (isset($existingHashes[$nRec->hash]) || isset($newRecs[$nRec->hash])) {
                continue;
            }

            $newRecs[$nRec->hash] = $nRec;
        }

        // Записваме новите записи физически в реда с максимално отстояние между еднакви домейни
        $newRecs = self::spreadByDomain(array_values($newRecs));

        foreach ($newRecs as $nRec) {
            // Нулата означава, че глобалният ред още не е финализиран
            $nRec->sendOrder = 0;
            $id = self::save($nRec, null, 'IGNORE');

            if ($id) {
                $addCnt++;
                email_AddressesInfo::addEmail($nRec->email, false);
            }
        }

        // Включваме и съществуващите чакащи записи в устойчивия логически ред
        self::reorderWaitingByDomain($emailId);
        
        $mRec = new stdClass();
        $mRec->id = $emailId;
        $mRec->allMailCnt = $allCnt;
        blast_Emails::save($mRec, 'allMailCnt');
        
        return array('add' => $addCnt, 'remove' => $rCnt);
    }


    /**
     * Подрежда записи така, че минималното отстояние между еднакви домейни да е максимално
     *
     * @param array    $records
     * @param int|null $minDistance    Постигнатото минимално отстояние
     * @param array    $previousRecords Последните вече изпратени записи
     *
     * @return array
     */
    public static function spreadByDomain($records, &$minDistance = null, $previousRecords = array())
    {
        $records = array_values((array) $records);
        $total = count($records);

        if (!$total) {
            $minDistance = 0;

            return array();
        }

        $buckets = array();

        foreach ($records as $index => $rec) {
            $domain = self::getRecordDomain($rec, 'pending' . $index);
            $buckets[$domain][] = $rec;
        }

        $maxCount = 0;
        $maxCountDomains = 0;

        foreach ($buckets as $bucket) {
            $count = count($bucket);

            if ($count > $maxCount) {
                $maxCount = $count;
                $maxCountDomains = 1;
            } elseif ($count == $maxCount) {
                $maxCountDomains++;
            }
        }

        if ($maxCount <= 1) {
            $maxDistance = $total;
        } else {
            $maxDistance = intdiv($total - $maxCountDomains, $maxCount - 1);
            $maxDistance = max(1, $maxDistance);
        }

        $previousRecords = array_values((array) $previousRecords);
        $previousCount = count($previousRecords);
        $lastPositions = array();

        foreach ($previousRecords as $index => $rec) {
            $domain = self::getRecordDomain($rec, 'previous' . $index);
            $lastPositions[$domain] = $index - $previousCount;
        }

        // Без историческа граница теоретичната горна граница винаги е постижима
        if (!$previousCount) {
            $minDistance = $maxDistance;

            return self::constructSpreadOrder($buckets, $maxDistance, array());
        }

        $lowDistance = 1;
        $highDistance = $maxDistance;
        $bestDistance = 0;
        $bestResult = false;

        // Допустимостта е монотонна, затова намираме оптималното отстояние с двоично търсене
        while ($lowDistance <= $highDistance) {
            $distance = intdiv($lowDistance + $highDistance, 2);
            $result = self::constructSpreadOrder($buckets, $distance, $lastPositions);

            if ($result !== false) {
                $bestDistance = $distance;
                $bestResult = $result;
                $lowDistance = $distance + 1;
            } else {
                $highDistance = $distance - 1;
            }
        }

        if ($bestResult !== false) {
            $minDistance = $bestDistance;

            return $bestResult;
        }

        throw new RuntimeException('Unable to construct the email ordering.');
    }


    /**
     * Конструира подредба за зададено минимално отстояние
     *
     * @param array $buckets
     * @param int   $distance
     * @param array $lastPositions
     *
     * @return array|false
     */
    private static function constructSpreadOrder($buckets, $distance, $lastPositions)
    {
        $total = 0;
        $remaining = array();
        $nextIndex = array();
        $heap = new SplPriorityQueue();
        $heap->setExtractFlags(SplPriorityQueue::EXTR_BOTH);
        $tieBreaker = count($buckets);
        $delayed = array();

        foreach ($buckets as $domain => $bucket) {
            $remaining[$domain] = count($bucket);
            $nextIndex[$domain] = 0;
            $total += $remaining[$domain];
            $readyAt = isset($lastPositions[$domain]) ? $lastPositions[$domain] + $distance : 0;
            $priorityTieBreaker = $tieBreaker--;

            if ($readyAt <= 0) {
                $heap->insert($domain, array($remaining[$domain], $priorityTieBreaker));
            } else {
                $delayed[] = array(
                    'domain' => $domain,
                    'remaining' => $remaining[$domain],
                    'readyAt' => $readyAt,
                    'tieBreaker' => $priorityTieBreaker,
                );
            }
        }

        $cooldown = new SplQueue();
        $result = array();

        usort($delayed, function ($a, $b) {
            if ($a['readyAt'] == $b['readyAt']) {
                return 0;
            }

            return ($a['readyAt'] < $b['readyAt']) ? -1 : 1;
        });

        foreach ($delayed as $entry) {
            $cooldown->enqueue($entry);
        }

        for ($position = 0; $position < $total; $position++) {
            while (!$cooldown->isEmpty() && $cooldown->bottom()['readyAt'] <= $position) {
                $released = $cooldown->dequeue();
                $heap->insert(
                    $released['domain'],
                    array($released['remaining'], $released['tieBreaker'])
                );
            }

            if ($heap->isEmpty()) {
                return false;
            }

            $entry = $heap->extract();
            $domain = $entry['data'];
            $result[] = $buckets[$domain][$nextIndex[$domain]++];
            $remaining[$domain]--;

            if ($remaining[$domain] > 0) {
                $cooldown->enqueue(array(
                    'domain' => $domain,
                    'remaining' => $remaining[$domain],
                    'readyAt' => $position + $distance,
                    'tieBreaker' => $entry['priority'][1],
                ));
            }
        }

        return $result;
    }


    /**
     * Връща нормализирания домейн на запис
     *
     * @param object $rec
     * @param string $fallbackKey
     *
     * @return string
     */
    private static function getRecordDomain($rec, $fallbackKey)
    {
        $domain = type_Email::domain($rec->email);

        if ($domain === false) {
            // Невалидните стари записи не трябва да се приемат като един общ домейн
            return '__invalid__' . $fallbackKey;
        }

        return mb_strtolower($domain);
    }


    /**
     * Преизчислява реда на активните чакащи записи за циркулярен имейл
     *
     * @param int $emailId
     *
     * @return int Брой променени записи
     */
    public static function reorderWaitingByDomain($emailId)
    {
        $records = array();
        $query = self::getQuery();
        $query->where(array("#emailId = '[#1#]'", $emailId));
        $query->where("#state = 'waiting'");
        $query->where("#stateAct = 'active'");
        $query->orderBy('id', 'ASC');
        $query->show('id,email,sendOrder');

        while ($rec = $query->fetch()) {
            $records[] = $rec;
        }

        $previousRecords = array();

        if (count($records)) {
            // Последните успешни изпращания пазят от повторение на домейна на границата между партидите
            $sentQuery = self::getQuery();
            $sentQuery->where(array("#emailId = '[#1#]'", $emailId));
            $sentQuery->where("#state = 'sended'");
            $sentQuery->where("#sentOn IS NOT NULL");
            $sentQuery->orderBy('sentOn', 'DESC');
            $sentQuery->orderBy('sendOrder', 'DESC');
            $sentQuery->orderBy('id', 'DESC');
            $sentQuery->limit(count($records));
            $sentQuery->show('id,email');

            while ($rec = $sentQuery->fetch()) {
                $previousRecords[] = $rec;
            }

            $previousRecords = array_reverse($previousRecords);
        }

        $distance = null;
        $records = self::spreadByDomain($records, $distance, $previousRecords);
        $updates = array();
        $unfinalizedUpdates = array();

        foreach ($records as $index => $rec) {
            $sendOrder = $index + 1;

            if ((int) $rec->sendOrder == $sendOrder) {
                continue;
            }

            $update = new stdClass();
            $update->id = $rec->id;
            $update->sendOrder = $sendOrder;

            // Нулевите записи остават като маркер до успешното обновяване на старите редове
            if ((int) $rec->sendOrder === 0) {
                $unfinalizedUpdates[] = $update;
            } else {
                $updates[] = $update;
            }
        }

        $updates = array_merge($updates, $unfinalizedUpdates);

        if (count($updates)) {
            expect(self::updateSendOrders($updates), 'Грешка при подреждане на опашката за циркулярен имейл');
        }

        return count($updates);
    }


    /**
     * Обновява реда пакетно и само за все още съществуващи записи
     *
     * @param array $updates
     *
     * @return bool
     */
    private static function updateSendOrders($updates)
    {
        $mvc = cls::get(__CLASS__);

        foreach (array_chunk($updates, 1000) as $chunk) {
            $cases = array();
            $ids = array();

            foreach ($chunk as $rec) {
                $id = (int) $rec->id;
                $sendOrder = (int) $rec->sendOrder;
                $cases[] = "WHEN {$id} THEN {$sendOrder}";
                $ids[] = $id;
            }

            $sql = "UPDATE {$mvc->dbTableName} SET send_order = CASE id " .
                implode(' ', $cases) .
                ' ELSE send_order END WHERE id IN (' . implode(',', $ids) . ')';

            if (!$mvc->db->query($sql, false, $mvc->doReplication)) {
                return false;
            }
        }

        $mvc->dbTableUpdated();

        return true;
    }
    
    
    /**
     * Връща данните за подадения emailId
     *
     * @param int        $emailId     - id на мастер (blast_Emails)
     * @param int        $count       - Дали да има ограничени в броя на записите
     * @param array|null $recipientArr - Избраните получатели, индексирани по id на детайла
     *
     * @return array
     */
    public static function getDataArrForEmailId($emailId, $count = null, &$recipientArr = null)
    {
        $resArr = array();
        $recipientArr = array();

        // Старите и недовършените опашки получават устойчив ред преди четене
        $needsReorder = self::fetch(
            array(
                "#emailId = '[#1#]' AND #state = 'waiting' AND #stateAct = 'active' AND #sendOrder = 0",
                $emailId,
            ),
            'id',
            false
        );

        if ($needsReorder) {
            self::reorderWaitingByDomain($emailId);
        }
        
        // Вземаме всички записи, които не са използвани
        $query = self::getQuery();
        $query->where(array("#emailId = '[#1#]'", $emailId));
        $query->where("#state = 'waiting'");
        $query->where("#stateAct = 'active'");
        $query->where("#sendOrder > 0");
        $query->orderBy('sendOrder', 'ASC');
        $query->orderBy('id', 'ASC');
        
        // Ако има ограничение
        if ($count) {
            $query->limit = $count;
        }
        
        // Обхождаме всички резултати и ги добавяме в масива
        while ($rec = $query->fetch()) {
            $resArr[$rec->id] = $rec->data;
            $recipientArr[$rec->id] = $rec->email;
        }
        
        return $resArr;
    }
    
    
    /**
     * Връща данните за подаденот id
     *
     * @param int $id
     *
     * @return array
     */
    public static function getDataArr($id)
    {
        $dataArr = self::fetchField($id, 'data');
        
        return (array) $dataArr;
    }
    
    
    /**
     * Маркира като изпратени
     *
     * @param array $dataArr
     */
    public static function markAsSent($dataArr)
    {
        $dataArr = arr::make($dataArr);
        
        // Маркира всички подадени записи, като изпратени
        foreach ((array) $dataArr as $id => $dummy) {
            $nRec = new stdClass();
            $nRec->id = $id;
            $nRec->state = 'sended';
            
            self::save($nRec, null, 'UPDATE');
        }
    }
    
    
    /**
     * Премахва маркирането като изпратени
     *
     * @param array $dataArr
     */
    public static function removeMarkAsSent($dataArr)
    {
        $dataArr = arr::make($dataArr);
        
        // Маркира всички подадени записи, като изпратени
        foreach ((array) $dataArr as $id => $dummy) {
            $nRec = new stdClass();
            $nRec->id = $id;
            $nRec->state = 'waiting';
            
            self::save($nRec, null, 'UPDATE');
        }
    }
    
    
    /**
     * Променя времето на изпращане и имейла
     *
     * @param array $idsArr
     */
    public static function setTimeAndEmail($idsArr)
    {
        $idsArr = arr::make($idsArr);
        
        // Променя времето и имейла на всички подадени записи
        foreach ((array) $idsArr as $id => $email) {
            $nRec = new stdClass();
            $nRec->id = $id;
            $nRec->sentOn = dt::now();
            $nRec->email = $email;
            
            self::save($nRec, null, 'UPDATE');
        }
    }
    
    
    /**
     * Връща хеша за имейал
     *
     * @param string $email
     *
     * @return string
     */
    public static function getHash($email)
    {
        $hash = md5($email);
        
        return $hash;
    }
    
    
    /**
     * Връща прогреса на изпращанията
     *
     * @param int $emailId
     *
     * @return int
     */
    public static function getSendingProgress($emailId)
    {
        $query = self::getQuery();
        $query->where("#emailId = '{$emailId}'");
        
        $allCnt = $query->count();
        
        if (!$allCnt) {
            
            return 0;
        }
        
        $query->where("#state = 'sended'");
        
        $sendedCnt = $query->count();
        
        $progress = $sendedCnt / $allCnt;
        
        if ($progress > 1) {
            $progress = 1;
        }
        
        return $progress;
    }
    
    
    /**
     * След подготвяне на формата за филтриране
     *
     * @param blast_EmailSend $mvc
     * @param stdClass        $data
     */
    public function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('stateAct', 'ASC');
        $data->query->orderBy('state', 'ASC');

        // Ако има изчислен ред, показваме записите според него
        $data->query->XPR('hasSendOrder', 'int', 'IF(#sendOrder > 0, 1, 0)');
        $data->query->orderBy('hasSendOrder', 'DESC');
        $data->query->orderBy('sendOrder', 'ASC');


        $data->query->orderBy('createdOn', 'DESC');
        $data->query->orderBy('sentOn', 'DESC');
        $data->query->orderBy('id', 'DESC');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param blast_EmailSend $mvc
     * @param stdClass        $row Това ще се покаже
     * @param stdClass        $rec Това е записа в машинно представяне
     */
    public function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        // В зависимост от състоянието променяме класа на реда
        if ($rec->state == 'sended') {
            $row->ROW_ATTR['class'] .= ' state-closed';
        } else {
            $row->ROW_ATTR['class'] .= ' state-pending';
        }
        
        if ($rec->stateAct != 'stopped') {
            $stopUrl = array();
            if ($mvc->haveRightFor('stop', $rec)) {
                $stopUrl = array($mvc, 'stop', $rec->id, 'ret_url' => true);
            }
            
            // Бутон за спиране
            $row->stateAct = ht::createBtn('Спиране', $stopUrl, false, false, 'title=Прекратяване на изпращане към този имейл');
        } else {
            $activateUrl = array();
            if ($mvc->haveRightFor('activate', $rec)) {
                $activateUrl = array($mvc, 'activate', $rec->id, 'ret_url' => true);
            }
            
            // Бутон за активиране
            $row->stateAct = ht::createBtn('Активиране', $activateUrl, false, false, 'title=Започване на изпращане към този имейл');
            
            $row->ROW_ATTR['class'] .= ' state-stopped';
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($rec && ($requiredRoles != 'no_one')) {
            if ($action == 'stop' || $action == 'activate') {
                if ($rec->state == 'sended') {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Екшън за спиране
     */
    public function act_Stop()
    {
        // id' то на записа
        $id = Request::get('id', 'int');
        
        expect($id);
        
        // Очакваме да има такъв запис
        $rec = $this->fetch($id);
        expect($rec, 'Няма такъв запис.');
        
        // Очакваме да имаме права за записа
        $this->requireRightFor('stop', $rec);
        
        // Смяняме състоянието на спряно
        $nRec = new stdClass();
        $nRec->id = $id;
        $nRec->stateAct = 'stopped';
        $this->save($nRec);

        self::reorderWaitingByDomain($rec->emailId);
        
        return new Redirect(getRetUrl(), '|Успешно спряхте изпращането до имейл|* ' . $rec->email);
    }
    
    
    /**
     * Екшън за активиране
     */
    public function act_Activate()
    {
        // id' то на записа
        $id = Request::get('id', 'int');
        
        expect($id);
        
        // Очакваме да има такъв запис
        $rec = $this->fetch($id);
        expect($rec, 'Няма такъв запис.');
        
        // Очакваме да имаме права за записа
        $this->requireRightFor('activate', $rec);
        
        // Смяняме състоянието на спряно
        $nRec = new stdClass();
        $nRec->id = $id;
        $nRec->stateAct = 'active';
        $this->save($nRec);

        self::reorderWaitingByDomain($rec->emailId);
        
        $eRec = blast_Emails::fetch($rec->emailId);
        
        // Ако състоянието е затворено, активираме имейла
        if ($eRec->state == 'closed') {
            $nERec = new stdClass();
            $nERec->id = $eRec->id;
            $nERec->state = 'active';
            blast_Emails::save($nERec);
        }
        
        return new Redirect(getRetUrl(), '|Успешно активирахте изпращането до имейл|* ' . $rec->email);
    }
}
