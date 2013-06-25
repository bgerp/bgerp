<?php 


/**
 * Защитен ключ за регистриране на обаждания
 */
defIfNot('CALLCENTER_PROTECT_KEY', md5(EF_SALT . 'callCenter'));


/**
 * Мениджър за записване на обажданията
 *
 * @category  bgerp
 * @package   callcenter
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class callcenter_Talks extends core_Master
{
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Разговори';
    
    
    /**
     * 
     */
    var $singleTitle = 'Разговор';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'user';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'user';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'user';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'admin';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'callcenter_Wrapper, plg_RowTools, plg_Printing, plg_Search, plg_Sorting, plg_RefreshRows';
    
    
    /**
     * 
     */
    var $refreshRowsTime = 3000;
    
    
    /**
     * Нов темплейт за показване
     */
//    var $singleLayoutFile = '';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
//    var $singleIcon = '';

    
    /**
     * Поле за търсене
     */
    var $searchFields = 'callerNum, calledNum, users, dialStatus, startTime';
    
    
    /**
     * 
     */
    var $listFields = 'id, callerNum=Инициатор->Номер, contragent=Инициатор->Име, calledNum=Потребител->Номер, users=Потребител->Потребители, dialStatus, startTime, duration';
    
    
    /**
     * Полетата, които ще се показват в единичния изглед
     */
//    var $singleFields = 'callerNum, contragent, calledNum, users, dialStatus, uniqId, startTime, answerTime, endTime, duration';
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('callerNum', 'drdata_PhoneType', 'caption=Позвъняващ, width=100%');
        $this->FLD('calledNum', 'varchar', 'caption=Търсен');
        $this->FLD('classId', 'key(mvc=core_Classes, select=name)', 'caption=Визитка->Клас');
        $this->FLD('contragentId', 'int', 'caption=Визитка->Номер');
//        $this->FLD('mp3', 'varchar', 'caption=Аудио');
        $this->FLD('users', 'keylist(mvc=core_Users, select=nick)', 'caption=Потребители');
        $this->FLD('dialStatus', 'enum(NO ANSWER=Без отговор, FAILED=Прекъснато, BUSY=Заето, ANSWERED=Отговорено, UNKNOWN=Няма информация)', 'allowEmpty, caption=Състояние');
        $this->FLD('uniqId', 'varchar', 'caption=Номер');
        $this->FLD('startTime', 'datetime', 'caption=Време->Начало');
        $this->FLD('answerTime', 'datetime', 'allowEmpty, caption=Време->Отговор');
        $this->FLD('endTime', 'datetime', 'allowEmpty, caption=Време->Край');
        
        $this->FNC('contragent', 'varchar', 'caption=Контрагент');
        $this->FNC('duration', 'time', 'caption=Време->Продължителност');
        
        $this->setDbUnique('uniqId');
    }
    
    
    /**
     * 
     */
    function on_CalcDuration($mvc, &$rec) 
    {
        // Ако е отговорени и затворено
        if ($rec->answerTime && $rec->endTime) {
            
            // Продължителност на разговора
            $duration = dt::secBetwen($rec->endTime, $rec->answerTime);
            
            // Ако има
            if ($duration) {
                
                // Добавяме към записа
                $rec->duration = $duration;
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
         // Ако има клас   
         if ($rec->classId) {
             
            // Инстанция на класа
            $class = cls::get($rec->classId);
            
            // Ако класа е профил
            if (strtolower($class->className) == 'crm_profiles') {
                
                // Вземаме линк към профила
                $card = crm_Profiles::createLink($rec->contragentId);
            } else {
                
                // Вземаме записите за съответния клас
                $cardRec = $class->fetch($rec->contragentId);
                
                // Ако имаме права за сингъл
                if ($class->haveRightFor('single', $cardRec)) {
                    
                    // Вземаме линка към сингъла
                    $card = ht::createLink($cardRec->name, array($class, 'single', $rec->contragentId)) ;
                } else {
                    
                    // Ако нямаме права
                    
                    // Вземаме линк към профила на отговорника
                    $inChargeLink = crm_Profiles::createLink($cardRec->inCharge);
                    
                    // Добавяме линка
                    $card = $class->getVerbal($cardRec, 'name') . " - {$inChargeLink}";
                }
            }
            
            // Добавяме линка към контрагента
            $row->contragent = $card;
        }
        
        // Ако има потребител
        if ($rec->users) {
            
            // Потребители в масив
            $usersArr = type_Keylist::toArray($rec->users);
            
            // Обхождаме масива
            foreach ($usersArr as $userId) {
                
                // Създаваме линк към профила му
                $profile = crm_Profiles::createLink($userId);
                
                // Ако няма профил, прескачавам
                if (!$profile) continue;
                
                // Добавяме профила, при другите
                $usersRow .= ($usersRow) ? ", $profile" : $profile;
            }
            
            // Добавяме профилите към записа
            $row->users = $usersRow;
        }
        
        // В зависмост от състоянието на разгоравя, опделяме клас за реда в таблицата
        if (!$rec->dialStatus) {
            $row->ROW_ATTR['class'] = 'dialStatus-opened';
        } elseif ($rec->dialStatus == 'ANSWERED') {
            $row->ROW_ATTR['class'] = 'dialStatus-answered';
        } elseif ($rec->dialStatus == 'FAILED') {
            $row->ROW_ATTR['class'] = 'dialStatus-failed';
        } elseif ($rec->dialStatus == 'BUSY') {
            $row->ROW_ATTR['class'] = 'dialStatus-busy';
        } elseif ($rec->dialStatus == 'NO ANSWER') {
            $row->ROW_ATTR['class'] = 'dialStatus-noanswer';
        } else {
            $row->ROW_ATTR['class'] = 'dialStatus-unknown';
        }
        
        // Ако не може да се определи номера
        if (!$rec->callerNum) {
            
            // Добавяме, че е скрит номер
            $row->callerNum = tr('Скрит номер');
        }
    }
    
    
    /**
     * 
     */
    public static function on_AfterPrepareListRows($mvc, $data)
    {
        // Изчистваме нотификацията
        $url = array('callcenter_Talks', 'list');
        bgerp_Notifications::clear($url);  
    }
    
    
    /**
     * Екшън за регистриран на обаждане
     */
    function act_RegisterCall()
    {
        // Ключа за защита
        $protectKey = Request::get('p');
        
        // Ако не отговаря на посочения от нас
        if ($protectKey != CALLCENTER_PROTECT_KEY) {
            
            // Записваме в лога
            static::log('Невалиден публичен ключ за обаждането');
            
            // Връщаме
            return FALSE;
        }
        
        // Вземаме променливите
        $startTime = Request::get('starttime');
        $calledNum = Request::get('extension');
        $callerNum = Request::get('callerId');
        $dialStatus = Request::get('dialstatus');
        $uniqId = Request::get('uniqueId');
        
        // Вземаме номера, на инициатора
        $callerNumArr = drdata_PhoneType::toArray($callerNum);
        
        // Създаваме обекта, който ще използваме
        $nRec = new stdClass();
        
        // Ако има номер
        if ($callerNumArr[0]) {
            
            // Вземаме стринга на номера
            $callerNumStr =  $callerNumArr[0]->countryCode . $callerNumArr[0]->areaCode . $callerNumArr[0]->number;
            
            // Вземаме последния номер, който сме регистрирали
            $extQuery = callcenter_ExternalNum::getQuery();
            $extQuery->where(array("#number = '[#1#]'", $callerNumStr));
            $extQuery->orderBy('id', 'DESC');
            $extQuery->limit(1);
            
            // Вземаме записа
            $cRec = $extQuery->fetch();
            
            // Ако има такъв запис
            if ($cRec) {
                
                // Вземаме данните за контрагента
                $nRec->classId = $cRec->classId;
                $nRec->contragentId = $cRec->contragentId;
            }
            
        } else {
            
            // Ако е вътрешен номер
            
            // Вземаме последния регистриран
            $intQuery = callcenter_InternalNum::getQuery();
            $intQuery->where(array("#number = '[#1#]'", $callerNum));
            $intQuery->orderBy('id', 'DESC');
            $intQuery->limit(1);
            
            $cRec = $intQuery->fetch();
            
            // Ако има запис
            if ($cRec) {
                
                // Добавяме данните на профила
                $nRec->classId = core_Classes::getId('crm_Profiles');
                $nRec->contragentId = crm_Profiles::fetch("#userId = {$cRec->userId}")->id;
            }
        }
        
        // Ако има търсен номер
        if ($calledNum) {
            
            // Вземаме всички записи за този номер
            $calledQuery = callcenter_InternalNum::getQuery();
            $calledQuery->where(array("#number = '[#1#]'", $calledNum));
            
            // Обхождаме резултатите
            while ($calledRec = $calledQuery->fetch()) {
                
                // Добавяме търсените номера в масива
                $calledUserArr[$calledRec->userId] = $calledRec->userId;
            }
            
            // Добавяме търсените номера
            $nRec->users = type_Keylist::fromArray($calledUserArr);
        }
        
        // Добавяме останалите променливи
        $nRec->callerNum = $callerNum;
        $nRec->calledNum = $calledNum;
        $nRec->uniqId = $uniqId;
        $nRec->startTime = $startTime;
        
        // Записваме
        static::save($nRec);
        
        return TRUE;
    }
    
    
    /**
     * Екшън за отбелязване на край на разговора
     */
    function act_RegisterEndCall()
    {
        // Ключа за защита
        $protectKey = Request::get('p');
        
        // Ако не отговаря на посочения от нас
        if ($protectKey != CALLCENTER_PROTECT_KEY) {
            
            // Записваме в лога
            static::log('Невалиден публичен ключ за обаждането');
            
            // Връщаме
            return FALSE;
        }
        
        // Вземаме уникалното id на разговора
        $uniqId = Request::get('uniqueId');
        
        // Вземаме записа
        $rec = static::fetch(array("#uniqId = '[#1#]'", $uniqId));
        
        // Ако има такъв запис
        if ($rec->id) {
            
            // Вземаме другите променливи
            $rec->answerTime = Request::get('answertime');
            $rec->endTime = Request::get('endtime');
            $rec->dialStatus = Request::get('dialstatus');
            
            // Обновяваме записа
            static::save($rec, NULL, 'UPDATE');
            
            // Добавяме нотификация
            static::addNotification($rec);
            
            // Връщаме
            return TRUE;
        }
    }
    
    
    /**
     * Добавяме нотификация, за пропуснато повикване
     */
    static function addNotification($rec)
    {
        // Ако няма потребители на този номер или е отговорено
        if (!$rec->users || $rec->dialStatus == 'ANSWERED') return;
        
        // Параметри на нотификацията
        $message = "|Имате пропуснато повикване";
        $priority = 'normal';
        $url = array('callcenter_Talks', 'list');
        $customUrl = $url;
        
        // Масив с отговорниците на номера
        $usersArr = type_Keylist::toArray($rec->users);
        
        // Обхождаме всички потребители
        foreach ($usersArr as $user) {
            
            // Добавяме им нотификация
            bgerp_Notifications::add($message, $url, $user, $priority, $customUrl);
        }
    }
    
    
    /**
     * 
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('usersSearch', 'users(rolesForAll=ceo, rolesForTeams=ceo|manager)', 'caption=Потребител,input,silent', array('attr' => array('onchange' => 'this.form.submit();')));
        
        // В хоризонтален вид
        $data->listFilter->view = 'horizontal';
        
        // Добавяме бутон
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'search,usersSearch';
        
        $data->listFilter->input('search,usersSearch', 'silent');
    }

    
    /**
     * 
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        // Сортиране на записите по num
        $data->query->orderBy('startTime', 'DESC');
        
        // Ако има филтър
        if($filter = $data->listFilter->rec) {
            
            // Ако филтъра е по потребители
            if($filter->usersSearch) {
                
                // Показваме само на потребителя
    			$data->query->likeKeylist('users', $filter->usersSearch);
                
    			// Ако се търси по всички и има права admin или ceo
    			if ((strpos($filter->usersSearch, '|-1|') !== FALSE) && (haveRole('ceo, admin'))) {
    			    
    			    // Показваме и празните резултати 
                    $data->query->orWhere("#users IS NULL");
                }
    		}
        }
    }
    
    
    /**
     * Екшън за тестване
     * Генерира обаждане
     */
    function act_Mockup()
    {
        // Текущото време - времето на позвъняване
        $startTime = dt::now();
        
        // Масив със статусите
        $staturArr = array('NO ANSWER', 'FAILED', 'BUSY', 'ANSWERED', 'UNKNOWN', 'ANSWERED', 'ANSWERED', 'ANSWERED', 'ANSWERED', 'ANSWERED', 'ANSWERED');
        
        // Избираме един случаен стату
        $status = $staturArr[rand(0, 10)];
        
        // Ако е отговорен
        if ($status == 'ANSWERED') {
            
            // Времето в UNIX
            $unixTime = dt::mysql2timestamp($startTime);
            
            // Времето за отговор
            $answerTime = $unixTime + rand(3, 7);
            
            // Времето на края на разговора
            $endTime = $unixTime + rand(22, 88);
            
            // Преобразуваме ги в mySQL формат
            $myAnswerTime = dt::timestamp2Mysql($answerTime);
            $myEndTime = dt::timestamp2Mysql($endTime);
        }
        
        // Генерираме рандом чило за уникалното id
        $uniqId = rand();
        
        // Масив за линка
        $urlArr = array(
            'Ctr' => 'callcenter_Talks',
            'Act' => 'RegisterCall',
            'p' => CALLCENTER_PROTECT_KEY,
            'starttime' => $startTime,
            'extension' => '540',
            'callerId' => '539',
            'uniqueId' => $uniqId,
        );
        
        // Вземаме абсолютния линк
        $url = toUrl($urlArr, 'absolute');
        
        // Извикваме линка
        exec("wget -q --spider '{$url}'");
        
        // Масив за линка
        $urlArr = array(
            'Ctr' => 'callcenter_Talks',
            'Act' => 'RegisterEndCall',
            'p' => CALLCENTER_PROTECT_KEY,
            'answertime' => $myAnswerTime,
            'endtime' => $myEndTime,
            'dialstatus' => $status,
            'uniqueId' => $uniqId,
        );
        
        // Вземаме абсолютния линк
        $url = toUrl($urlArr, 'absolute');
        
        // Извикваме линка
        exec("wget -q --spider '{$url}'");
    }
}
