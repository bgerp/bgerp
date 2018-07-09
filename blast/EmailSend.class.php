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
        
        // @deprecated
        $this->FLD('dataId', 'int', 'caption=Списък данни');
        
        $this->setDbUnique('hash, emailId');
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
        $addCnt = $rCnt = 0;
        
        // Обхождаме масива с данните
        foreach ((array) $dataArr as $data) {
            $emailStr = '';
            
            $nRec = new stdClass();
            $nRec->emailId = $emailId;
            $nRec->data = $data;
            $nRec->state = 'waiting';
            
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
                if (blast_BlockedEmails::isBlocked($email)) {
                    continue;
                }
                
                // Ако е в отрицателния списък - просто го игнорираме
                if ($negativeEmailArr[$email]) {
                    $nRec->email = $email;
                    
                    // Хеша на имейла
                    $nRec->hash = self::getHash($email);
                    
                    if (self::delete(array("#emailId = '[#1#]' AND #hash = '[#2#]'", $nRec->emailId, $nRec->hash))) {
                        $rCnt++;
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
            
            // За всеки нов запис увеличаваме брояча
            $id = self::save($nRec, null, 'IGNORE');
            
            if ($id) {
                $addCnt++;
                blast_BlockedEmails::addEmail($toEmail, false);
            }
        }
        
        return array('add' => $addCnt, 'remove' => $rCnt);
    }
    
    
    /**
     * Връща данните за подадения emailId
     *
     * @param int $emailId - id на мастер (blast_Emails)
     * @param int $count   - Дали да има ограничени в броя на записите
     *
     * @return array
     */
    public static function getDataArrForEmailId($emailId, $count = null)
    {
        $resArr = array();
        
        // Вземаме всички записи, които не са използвани
        $query = self::getQuery();
        $query->where(array("#emailId = '[#1#]'", $emailId));
        $query->where("#state = 'waiting'");
        $query->where("#stateAct != 'stopped'");
        
        // Ако има ограничение
        if ($count) {
            $query->limit = $count;
        }
        
        // Обхождаме всички резултати и ги добавяме в масива
        while ($rec = $query->fetch()) {
            $resArr[$rec->id] = $rec->data;
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
        // Подреждаме записите, като неизпратените да се по-нагоре
        $data->query->orderBy('stateAct', 'ASC');
        $data->query->orderBy('state', 'ASC');
        $data->query->orderBy('createdOn', 'DESC');
        $data->query->orderBy('sentOn', 'DESC');
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
        $this->requireRightFor('activate', $rec);
        
        // Смяняме състоянието на спряно
        $nRec = new stdClass();
        $nRec->id = $id;
        $nRec->stateAct = 'stopped';
        $this->save($nRec);
        
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
        $this->requireRightFor('single', $rec);
        
        // Смяняме състоянието на спряно
        $nRec = new stdClass();
        $nRec->id = $id;
        $nRec->stateAct = 'active';
        $this->save($nRec);
        
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
