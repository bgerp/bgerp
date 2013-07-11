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
    var $singleIcon = 'img/16/incoming.png';

    
    /**
     * Поле за търсене
     */
    var $searchFields = 'callerNum, calledNum, dialStatus, startTime';
    
    
    /**
     * 
     */
    var $listFields = 'id, callerNum, callerData, calledNum, calledData, startTime, duration';
    
    
    /**
     * Полетата, които ще се показват в единичния изглед
     */
//    var $singleFields = 'callerNum, contragent, calledNum, users, dialStatus, uniqId, startTime, answerTime, endTime, duration';
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('callerNum', 'drdata_PhoneType', 'caption=Позвъняващ->Номер, width=100%');
        $this->FLD('callerData', 'key(mvc=callcenter_Numbers)', 'caption=Позвъняващ->Име, width=100%');
        
        $this->FLD('calledNum', 'drdata_PhoneType', 'caption=Търсен->Номер, width=100%');
        $this->FLD('calledData', 'key(mvc=callcenter_Numbers)', 'caption=Търсен->Име, width=100%');
        
//        $this->FLD('mp3', 'varchar', 'caption=Аудио');
        $this->FLD('dialStatus', 'enum(NO ANSWER=Без отговор, FAILED=Прекъснато, BUSY=Заето, ANSWERED=Отговорено, UNKNOWN=Няма информация)', 'allowEmpty, caption=Състояние, hint=Състояние на обаждането');
        $this->FLD('uniqId', 'varchar', 'caption=Номер');
        $this->FLD('startTime', 'datetime', 'caption=Време->Начало');
        $this->FLD('answerTime', 'datetime', 'allowEmpty, caption=Време->Отговор');
        $this->FLD('endTime', 'datetime', 'allowEmpty, caption=Време->Край');
        $this->FLD('callType', 'type_Enum(incoming=Входящ, outgoing=Изходящ)', 'allowEmpty, caption=Тип на разговора, hint=Тип на обаждането');
        
        $this->FNC('duration', 'time', 'caption=Време->Продължителност');
        
        $this->setDbUnique('uniqId');
    }
    
    
    /**
     * 
     */
    function on_CalcDuration($mvc, &$rec) 
    {
        // Ако е отговорено и затворено
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
        // Ако има данни за търсещия
        if ($rec->callerData) {
         
            // Вземаме записа
            $numRec = callcenter_Numbers::fetch($rec->callerData);
            
            // Вербалния запис
            $callerNumRow = callcenter_Numbers::recToVerbal($numRec);
            
            // Ако има открити данни
            if ($callerNumRow->contragent) {
             
                // Добавяме данните
                $row->callerData = $callerNumRow->contragent;
            }
        }
        
        // Ако има данни за търсения
        if ($rec->calledData) {
         
            // Вземаме записа
            $numRec = callcenter_Numbers::fetch($rec->calledData);
            
            // Вербалния запис
            $calledNumRow = callcenter_Numbers::recToVerbal($numRec);
            
            // Ако има открити данни
            if ($calledNumRow->contragent) {
             
                // Добавяме данните
                $row->calledData = $calledNumRow->contragent;
            }
        }
        
        // Ако сме в тесен режим
        if (mode::is('screenMode', 'narrow')) {
         
            // Дива за разстояние
            $div = "<div style='margin-top:5px;'>";
            
            // Добавяме данните към номерата
            $row->callerNum .=  $div. $row->callerData . "</div>";
            $row->calledNum .= $div . $row->calledData . "</div>";
            
            // Ако има продължителност
            if ($rec->duration) {
             
                // Ако няма вербална стойност
                if (!$duration = $row->duration) {
                 
                    // Вземаме вербалната стойност
                    $duration = static::getVerbal($rec, 'duration');
                }
                
                // Добавяме след времето на позвъняване
                $row->startTime .= $div . $duration;
            }
        }
        
        // Добавяме стил за телефони        
        $row->callerNum = "<div class='telephone'>" . $row->callerNum . "</div>";
        $row->calledNum = "<div class='telephone'>" . $row->calledNum . "</div>";
        
        // В зависмост от състоянието на разгоравя, опделяме клас за реда в таблицата
        if (!$rec->dialStatus) {
            $row->ROW_ATTR['class'] .= ' dialStatus-opened';
        } elseif ($rec->dialStatus == 'ANSWERED') {
            $row->ROW_ATTR['class'] .= ' dialStatus-answered';
        } else {
            $row->ROW_ATTR['class'] .= ' dialStatus-failed';
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
     * Обновява записите за съответния номер
     * 
     * @param string $numStr - Номера
     * @param integer $numId - id на номера
     */
    static function updateRecsForNum($numStr, $numId=NULL)
    {
        // Вземаме всички записи за съответния номер
        $query = static::getQuery();
        $query->where(array("#callerNum = '[#1#]' || #calledNum = '[#1#]'", $numStr));
        
        // Ако id на номера
        if (!$numId) {
            
            // Вземаме последното id
            $nRec = callcenter_Numbers::getRecForNum($numStr);
            $numId = $nRec->id;
        }
        
        // Обхождаме резултатите
        while ($rec = $query->fetch()) {
            
            // Ако номера на позвъняващия отговара
            if ($rec->callerNum == $numStr) {
                
                // Променяме данните
                $rec->callerData = $numId;
            }
            
            // Ако номера на търсения отговара
            if ($rec->calledNum == $numStr) {
                
                // Променяме данните
                $rec->calledData = $numId;
            }
            
            // Записваме
            static::save($rec);
        }
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
        $outgoing = Request::get('outgoing');
                
        // Създаваме обекта, който ще използваме
        $nRec = new stdClass();
        
        // Вземаме записите за позвъняващия номера
        $cRec = callcenter_Numbers::getRecForNum($callerNum);
        
        // Ако има такъв запис
        if ($cRec) {
            
            // Вземаме данните за контрагента
            $nRec->callerData = $cRec->id;
        }
        
        // Вземаме записите за търсения номера
        $dRec = callcenter_Numbers::getRecForNum($calledNum);
        
        // Ако има такъв запис
        if ($dRec) {
            
            // Вземаме данните за контрагента
            $nRec->calledData = $dRec->id;
        }
        
        // Добавяме останалите променливи
        $nRec->callerNum = callcenter_Numbers::getNumberStr($callerNum);
        $nRec->calledNum = callcenter_Numbers::getNumberStr($calledNum);
        $nRec->uniqId = $uniqId;
        $nRec->startTime = $startTime;
        
        // Ако е изходящо обаждане
        if ($outgoing) {
            
            // Отбелязваме типа
            $nRec->callType = 'outgoing';
        } else {
            $nRec->callType = 'incoming';
        }
        
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
            
            // Типа на обаждането
            $outgoing = Request::get('outgoing');
            
            // Ако е изходящо
            if ($outgoing) {
                
                // Отбелязваме
                $rec->callType = 'outgoing';
            }
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
        if ($rec->dialStatus == 'ANSWERED' || $rec->callType == 'outgoing') return;
        
        // Параметри на нотификацията
        $message = "|Имате пропуснато повикване";
        $priority = 'normal';
        $url = array('callcenter_Talks', 'list');
        $customUrl = $url;
        
        $calledNum = $rec->calledNum;
        
        // Вземаме потребителите, които отговарят за съответния номер
        $usersArr = callcenter_Numbers::getUserForNum($calledNum);
        
        // Обхождаме всички потребители
        foreach ((array)$usersArr as $user) {
            
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
        
        // Ако имаме тип на обаждането
        if ($typeOptions = &$data->listFilter->getField('callType')->type->options) {
            
            // Добавяме в началото празен стринг за всички
            $typeOptions = array('all' => '') + $typeOptions;
            
            // Избираме го по подразбиране
            $data->listFilter->setDefault('callType', 'all');
        }
        
        // Ако имаме статуси
        if ($typeOptions = &$data->listFilter->getField('dialStatus')->type->options) {
            
            // Добавяме в началото празен стринг за всички
            $typeOptions = array('all' => '') + $typeOptions;
            
            // Избираме го по подразбиране
            $data->listFilter->setDefault('dialStatus', 'all');
        }
        
        // В хоризонтален вид
        $data->listFilter->view = 'horizontal';
        
        // Добавяме бутон
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'search, usersSearch, dialStatus, callType';
        
        $data->listFilter->input('search, usersSearch, dialStatus, callType', 'silent');
    }

    
    /**
     * 
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        // Ако не е избран потребител по подразбиране
        if(!$data->listFilter->rec->usersSearch) {
            
            // Да е текущия
            $data->listFilter->rec->usersSearch = '|' . core_Users::getCurrent() . '|';
        }
        
        // Сортиране на записите по num
        $data->query->orderBy('startTime', 'DESC');
        
        // Ако има филтър
        if($filter = $data->listFilter->rec) {
            
            // Ако филтъра е по потребители
            if($filter->usersSearch) {
                
    			// Ако се търси по всички и има права admin или ceo
    			if ((strpos($filter->usersSearch, '|-1|') !== FALSE) && (haveRole('ceo, admin'))) {
    			    // Търсим всичко
                } else {
                    
                    // Масив с потребителите
                    $usersArr = type_Keylist::toArray($filter->usersSearch);
                    
                    // Масив с номерата на съответните потребители
                    $numbersArr = callcenter_Numbers::getInternalNumbersForUsers($usersArr);
                    
                    // Ако има такива номера
                    if (count((array)$numbersArr)) {
                        
                        // Показваме обажданията към и от тях
                        $data->query->orWhereArr('callerNum', $numbersArr);
        			    $data->query->orWhereArr('calledNum', $numbersArr, TRUE);
                    } else {
                        
                        // Не показваме нищо
                        $data->query->where("1=2");
                    }
                }
    		}
    		
            // Ако филтрираме по тип на звънене
            if($filter->callType && $filter->callType != 'all') {
                
                // Търсим по тип на звънене
                $data->query->where("#callType = '{$filter->callType}'");
                
                // Ако търсим по входящи
                if ($filter->callType == 'incoming') {
                    
                    // Търсим по статус
                    $data->query->orWhere("#callType IS NULL");
                }
            }
    		
            // Ако филтрираме по статус на обаждане
            if($filter->dialStatus && $filter->dialStatus != 'all') {
                
                // Търсим по статус на обаждане
                $data->query->where("#dialStatus = '{$filter->dialStatus}'");
            }
        }
    }
    
    
    /**
     * 
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Ако искаме да отворим сингъла на документа
        if ($rec->id && $action == 'single' && $userId) {
            
            // Ако нямаме роля CEO
            if (!haveRole('ceo')) {
                
                // Ако сме мениджър
                if (haveRole('manager')) {
                    
                    // Вземаме хората от нашия екип
                    $teemMates = core_Users::getTeammates($userId);
                    
                    // Съотборниците в масив
                    $teemMatesArr = type_Keylist::toArray($teemMates);
                    
                    // Връща номерата на всички съотборници
                    $numbersArr = callcenter_Numbers::getInternalNumbersForUsers($teemMatesArr);
                    
                } else {
                    
                    // Връща номерата на потребителя
                    $numbersArr = callcenter_Numbers::getInternalNumbersForUsers($userId);
                }
            
                // Ако има търсен номер и е в масива
                if ($rec->callerNum && in_array($rec->callerNum, $numbersArr)) {
                    
                    // Имаме права
                    $haveRole = TRUE;
                }
                
                // Ако има търсещ номер и е в масива
                if ($rec->calledNum && in_array($rec->calledNum, $numbersArr)) {
                    
                    // Имаме права
                    $haveRole = TRUE;
                }
                
                // Ако флага не е вдингнат
                if (!$haveRole) {
                    
                    // Нямаме права
                    $requiredRoles = 'no_one';
                }
            }
        } 
    }

    
    /**
     * 
     */
    function getIcon($id)
    {
        // Ако няма id връщаме
        if (!$id) return ;
        
        // Вземаме записа
        $rec = static::fetch($id);
        
        // Ако е изходящо обаждане
        if ($rec->callType == 'outgoing') {
            
            // Икона за изходящо обаждане
            $this->singleIcon = 'img/16/outgoing.png';
        } elseif (!$rec->dialStatus || $rec->dialStatus == 'ANSWERED') {
            
            // Ако в входящо
            $this->singleIcon = 'img/16/incoming.png';
        } else {
            
            // Ако е входящо и пропуснато
            $this->singleIcon = 'img/16/missed.png';
        }
    }
    
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $mvc
     * @param unknown_type $data
     */
    static function on_AfterPrepareListFields($mvc, $data)
    {
        // Ако сме в тесен режим
        if (mode::is('screenMode', 'narrow')) {
            
            // Променяме полетата, които ще се показват
            $data->listFields = arr::make('id=№, callerNum=Позвъняващ, calledNum=Търсен, startTime=Време');
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
//            'outgoing' => 'outgoing',
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
//            'outgoing' => 'outgoing'
        );
        
        // Вземаме абсолютния линк
        $url = toUrl($urlArr, 'absolute');
        
        // Извикваме линка
        exec("wget -q --spider '{$url}'");
    }
}
