<?php 


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
     * Допустимото отклонение в секуди при регистриране на обажданията
     * 
     * @example - 1 час
     */
    const DEVIATION_BETWEEN_TIMES = 3600;
    
    
    
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
    var $canRead = 'powerUser';
    
    
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
    var $canView = 'powerUser';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'powerUser';
    
    
    /**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'powerUser';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'powerUser';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'callcenter_Wrapper, plg_RowTools, plg_Printing, plg_Search, plg_Sorting, plg_RefreshRows,bgerp_plg_GroupByDate,plg_AutoFilter';
    

    /**
     * Името на полито, по което плъгина GroupByDate ще групира редовете
     */
    var $groupByDateField = 'startTime';


    /**
     * 
     */
    var $refreshRowsTime = 3000;
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'callcenter/tpl/SingleLayoutTalks.shtml';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/incoming.png';

    
    /**
     * Поле за търсене
     */
    var $searchFields = 'externalNum, internalNum';
    
    
    /**
     * 
     */
    var $listFields = 'singleLink=-, externalData, externalNum, internalData, internalNum, startTime, duration';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsField = 'singleLink';
    
    
    /**
     * Полетата, които ще се показват в единичния изглед
     */
//    var $singleFields = 'externalNum, contragent, internalNum, users, dialStatus, uniqId, startTime, answerTime, endTime, duration';
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('externalNum', 'drdata_PhoneType', 'caption=Външен->Номер, width=100%, oldFieldName=callerNum');
        $this->FLD('externalData', 'key(mvc=callcenter_Numbers)', 'caption=Външен->Контакт, width=100%, oldFieldName=callerData');
        
        $this->FLD('internalNum', 'varchar', 'caption=Вътрешен->Номер, width=100%, oldFieldName=calledNum');
        $this->FLD('internalData', 'keylist(mvc=callcenter_Numbers)', 'caption=Вътрешен->Потребител, width=100%, oldFieldName=calledData');
        
//        $this->FLD('mp3', 'varchar', 'caption=Аудио');
        $this->FLD('dialStatus', 'enum(NO ANSWER=Без отговор, FAILED=Прекъснато, BUSY=Заето, ANSWERED=Отговорено, UNKNOWN=Няма информация)', 'allowEmpty, caption=Състояние, hint=Състояние на обаждането,autoFilter');
        $this->FLD('uniqId', 'varchar', 'caption=Номер');
        $this->FLD('startTime', 'datetime(format=smartTime)', 'caption=Време->Начало');
        $this->FLD('answerTime', 'datetime(format=smartTime)', 'allowEmpty, caption=Време->Отговор');
        $this->FLD('endTime', 'datetime(format=smartTime)', 'allowEmpty, caption=Време->Край');
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
            $duration = dt::secsBetween($rec->endTime, $rec->answerTime);
            
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
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {   
        // Информация за външния номер
        $externalNumArr = drdata_PhoneType::toArray($rec->externalNum);
        
        // Ако е валиден номер
        if ($externalNumArr) {
            
            // Ако е мобилен, класа също да е мобилен
            $externalClass = ($externalNumArr[0]->mobile) ? 'mobile' : 'telephone';
            
            // Добавяме стил за телефони        
            $row->externalNum = "<div class='{$externalClass}'>" . $row->externalNum . "</div>";
        } else {
            
            // Вероятно е обаждане от вътрешен номер. Да няма оцветяване.
            $row->externalNum = core_Type::escape($rec->externalNum);
        }
        
        // Ако има данни за търсещия
        if ($rec->externalData) {
         
            // Вземаме записа
            $numRec = callcenter_Numbers::fetch($rec->externalData);
            
            // Вербалния запис
            $externalNumRow = callcenter_Numbers::recToVerbal($numRec);
            
            // Ако има открити данни
            if ($externalNumRow->contragent) {
                
                // Флаг, за да отбележим, че има данни
                $haveExternalData = TRUE;
                
                // Добавяме данните
                $row->externalData = $externalNumRow->contragent;
            }
        } 
        
        // Ако флага не е дигнат
        if (!$haveExternalData) {
            
            // Ако има номер
            if ($rec->externalNum) {
                // Уникално id
                $uniqId = $rec->id . 'caller';
                
                // Добавяме линка
                $row->externalData = static::getTemplateForAddNum($rec->externalNum, $uniqId, $externalNumArr);
            }
        }
        
        // Ако има данни за търсения
        if ($rec->internalData) {
            
            // Нулираме полето
            $row->internalData = NULL;
            
            // Масив с всички данни
            $internalDataArr = type_Keylist::toArray($rec->internalData);
            
            // Обхождаме масива
            foreach ($internalDataArr as $internalData) {
                
                // Вземаме записа
                $numRec = callcenter_Numbers::fetch($internalData);
                
                // Вербалния запис
                $internalNumRow = callcenter_Numbers::recToVerbal($numRec);
                
                // Ако има открити данни
                if ($internalNumRow->contragent) {
                     
                    // Флаг, за да отбележим, че има данни
                    $haveInternalData = TRUE;
                    
                    // Добавяме данните
                    $row->internalData .= ($row->internalData) ? (", {$internalNumRow->contragent}") : $internalNumRow->contragent;
                }
            }
        }
        
        // Ако флага не е дигнат 
        if (!$haveInternalData) {
            
            // Ако има номер
            if ($rec->internalNum) {
                // Уникално id
                $uniqId = $rec->id . 'called';
                
                // Добавяме линка
                $row->internalData = static::getTemplateForAddNum($rec->internalNum, $uniqId, array());
            }
        }
        
        // Ако сме в тесен режим
        if (mode::is('screenMode', 'narrow')) {
            
            // Ако не сме в сингъла
            // Добавяме данните към номера
            if(!$fields['-single']) {
                
                // Дива за разстояние
                $div = "<div style='margin-top:5px;'>";
                
                // Добавяме данните към номерата
                $row->externalNum .=  $div. $row->externalData . "</div>";
                $row->internalNum .= $div . $row->internalData . "</div>";
            
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
        }
        
        // В зависмост от състоянието на разгоравя, опделяме клас за реда в таблицата
        if (!$rec->dialStatus) {
            $row->DialStatusClass .= ' dialStatus-opened';
        } elseif ($rec->dialStatus == 'ANSWERED') {
            $row->DialStatusClass .= ' dialStatus-answered';
        } else {
            $row->DialStatusClass .= ' dialStatus-failed';
            $row->duration = $mvc->getVerbal($rec, 'dialStatus');
        }
        
        // Добавяме класа
        $row->ROW_ATTR['class'] = $row->DialStatusClass;
        
        // Ако не може да се определи номера
        if (!$rec->externalNum) {
            
            // Добавяме, че е скрит номер
            $row->externalNum = tr('Скрит номер');
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
     */
    static function updateRecsForNum($numStr)
    {
        // Вземаме всички записи за съответния номер
        $query = static::getQuery();
        $query->where(array("#externalNum = '[#1#]' || #internalNum = '[#1#]'", $numStr));
        
        // Вземаме всички записи за съответния номер
        $nRecArr = callcenter_Numbers::getRecForNum($numStr, FALSE, TRUE);
        
        // Обхождаме резултатите
        while ($rec = $query->fetch()) {
            
            // Ако номера на позвъняващия отговара
            if ($rec->externalNum == $numStr) {
                
                // Променяме данните
                $rec->externalData = $nRecArr[0]->id;
            }
            
            // Ако номера на търсения отговаря
            if ($rec->internalNum == $numStr) {
                
                // Обхождаме масива с резултатите
                foreach ($nRecArr as $nRec) {
                    
                    // Ако е вътрешен
                    if ($nRec->type == 'internal') {
                        
                        // Добавяме в масива
                        $numIdArr[$nRec->id] = $nRec->id;
                    }
                }
                
                // Променяме данните
                $rec->internalData = type_Keylist::fromArray($numIdArr);
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
        // Масив с грешките
        $errArr = array();
        
        // Ключа за защита
        $protectKey = Request::get('p');
        
        // Проверяваме дали има права за добавяне на запис
        if (!static::isAuthorized($protectKey)) return FALSE;
        
        // Вземаме променливите
        $startTime = Request::get('starttime');
        $dialStatus = Request::get('dialstatus');
        $uniqId = Request::get('uniqueId');
        $outgoing = Request::get('outgoing');
        
        // Ако има подадено начално време
        if ($startTime) {
            
            // Вземаме текущото време
            $now = dt::now();
            
            // Вземаме разликата във времето на сървъра и на подадения стринг
            $deviationSecs = abs(dt::secsBetween($now, $startTime));
            
            // Ако разликата е над допустимите
            if (($deviationSecs) && ($deviationSecs > static::DEVIATION_BETWEEN_TIMES)) {
                
                // Инстанция на класа
                $TimeInst = cls::get('type_Time');
                
                // Разликата във вербален вид
                $deviationVerbal = $TimeInst->toVerbal($deviationSecs);
                
                // Добавяме грешката
                $errArr[] = "Разминаване във времето на сървара и подаденото в URL с {$deviationVerbal}";
                
                // Задаваме текущото време за начало на позвъняване
                $startTime = $now;
            }
        } else {
            
            // Ако няма време
            
            // Задаваме текущото време за начало на позвъняване
            $startTime = dt::now();
            
            // Добавяме грешката
            $errArr[] = "Не е подадено начално време";
        }

        // Ако е изходящо обаждане
        if ($outgoing) {
            $internalNum = Request::get('callerId');
            $externalNum = Request::get('extension');
        } else {
            
            // Ако е входящо обаждане
            
            $internalNum = Request::get('extension');
            $externalNum = Request::get('callerId');
        }
        
        // Ако не е подаден вътрешен номер
        if (!$internalNum) {
            
            // Записваме грешката
            $errArr[] = 'Не е подаден вътрешен номер';
        } else {
            
            // Ако не е число
            if (!is_numeric($internalNum)) {
                
                // Добавяме грешката
                $errArr[] = 'Вътрешния номер не е число';
            }
        }
        
        // Проверяваме номера на контрагент
        if ($externalNum && !is_numeric($internalNum)) {
            
            // Добавяме грешка
            $errArr[] = 'Номерът на контрагента не е число';
        }
        
        // Създаваме обекта, който ще използваме
        $nRec = new stdClass();
        
        // Вземаме записите за позвъняващия номера
        $cRecArr = callcenter_Numbers::getRecForNum($externalNum);
        
        // Ако има такъв запис
        if ($cRecArr[0]) {
            
            // Вземаме данните за контрагента
            $nRec->externalData = $cRecArr[0]->id;
        }
        
        // Вземаме записите за търсения номера
        $dRecArr = callcenter_Numbers::getRecForNum($internalNum, 'internal', TRUE);

        // Обхождаме резултата
        foreach ((array)$dRecArr as $dRec) {
            
            // Ако има такъв запис
            if ($dRec) {
                
                // Добавяме в масива
                $dRecIdArr[$dRec->id] = $dRec->id;
            }
        }
        
        // Вземаме данните за контрагента
        $nRec->internalData = type_Keylist::fromArray($dRecIdArr);
        
        // Добавяме останалите променливи
        $nRec->externalNum = callcenter_Numbers::getNumberStr($externalNum);
        $nRec->internalNum = callcenter_Numbers::getNumberStr($internalNum);
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
        $savedId = static::save($nRec, NULL, 'IGNORE');
        
        // Ако записът не е успешен
        if (!$savedId) {
            
            // Добавяме грешката
            $errArr[] = 'Грешка при записване';
        }
        
        // Ако има грешки, ги записваме в лога
        static::errToLog($errArr, $savedId, getSelfURL());
        
        return TRUE;
    }
    
    
    /**
     * Екшън за отбелязване на край на разговора
     */
    function act_RegisterEndCall()
    {
        // Масив с грешките
        $errArr = array();
        
        // Ключа за защита
        $protectKey = Request::get('p');
        
        // Проверяваме дали има права за добавяне на запис
        if (!static::isAuthorized($protectKey)) return FALSE;
        
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
            $answerTime = Request::get('answertime');
            $endTime = Request::get('endtime');
            
            // Вземаме текущото време
            $now = dt::now();
            
            // Инстанция на класа
            $TimeInst = cls::get('type_Time');
            
            // Ако има време отговор и край
            if ($endTime && $answerTime) {
                
                // Ако времето на отговор е след времето на край
                if ($answerTime > $endTime) {
                    
                    // Добавяме грешка
                    $errArr[] = 'Време->Отговор е по - голямо от Време->Край';
                }
                
                // Вземамем разликата във времето между отговор и край
                $deviationsSecAnswEnd = dt::secsBetween($endTime, $answerTime);
                
                // Ако разликата е над допустимите
                if (($deviationsSecAnswEnd) && ($deviationsSecAnswEnd > static::DEVIATION_BETWEEN_TIMES)) {
                    
                    // Разликата във вербален вид
                    $deviationAnswEndVerbal = $TimeInst->toVerbal($deviationsSecAnswEnd);
                    
                    // Добавяме грешката
                    $errArr[] = "Прекалено дълго време за разговор - {$deviationAnswEndVerbal}";
                }
            }
            
            // Ако има начало
            if ($rec->startTime) {
                
                // Ако времето на отговор е преди началото
                if ($answerTime && ($rec->startTime > $answerTime)) {
                    
                    // Добавяме грешка
                    $errArr[] = 'Време->Начало е по - голямо от Време->Отговор';
                }
                
                // Ако времето за край е преди началот
                if ($endTime && ($rec->startTime > $endTime)) {
                    
                    // Добавяме грешка
                    $errArr[] = 'Време->Начало е по - голямо от Време->Край';
                }
            }
            
            // Ако има време на отговор
            if ($answerTime) {
                
                // Вземаме разликата във времето на сървъра и на подадения стринг
                $deviationSecsAnsw = abs(dt::secsBetween($now, $answerTime));
            
                // Ако разликата е над допустимите
                if (($deviationSecsAnsw) && ($deviationSecsAnsw > static::DEVIATION_BETWEEN_TIMES)) {
                    
                    // Разликата във вербален вид
                    $deviationAnswVerbal = $TimeInst->toVerbal($deviationSecsAnsw);
                    
                    // Записваме в лога
                    $errArr[] = "Разминаване във времето на сървара и подаденото в URL с {$deviationAnswVerbal} за Време->Отговор";
                    
                    // Задаваме текущото време
                    $answerTime = $now;
                }
            }
            
            // Ако има време на край
            if ($endTime) {
                
                // Вземаме разликата във времето на сървъра и на подадения стринг
                $deviationSecsEnd = abs(dt::secsBetween($now, $endTime));
                
                // Ако разликата е над допустимите
                if (($deviationSecsEnd) && ($deviationSecsEnd > static::DEVIATION_BETWEEN_TIMES)) {
                    
                    // Разликата във вербален вид
                    $deviationEndVerbal = $TimeInst->toVerbal($deviationSecsEnd);
                    
                    // Записваме в лога
                    $errArr[] = "Разминаване във времето на сървара и подаденото в URL с {$deviationEndVerbal} за Време->Край";
                    
                    // Задаваме текущото време
                    $endTime = $now;
                }
            }
            
            // Добавяме в rec
            $rec->answerTime = $answerTime;
            $rec->endTime = $endTime;
            $rec->dialStatus = Request::get('dialstatus');
            
            // Обновяваме записа
            $savedId = static::save($rec, NULL, 'UPDATE');
            
            // Добавяме нотификация
            static::addNotification($rec);
            
        } else {
            // Ако няма такъв запис
            
            // Добавяме грешката
            $errArr[] = 'Няма такъв запис';
        }
        
        // Ако има грешки, ги записваме в лога
        static::errToLog($errArr, $savedId, getSelfURL());
        
        // Връщаме
        return TRUE;
    }
    
    
    /**
     * Записва грешките в масива в лога
     * 
     * @param array $errArr
     * @param intege $id
     * @param URL $url
     */
    static function errToLog($errArr, $id=FALSE, $url=FALSE)
    {
        // Обхождаме подадения масив
        foreach ((array)$errArr as $err) {
            
            // Ако има URL
            if ($url) {
                
                // Добавяме към грешката
                $err .= ": " . $url;
            }
            
            // Записваме грешката
            static::log($err, $id);
        }
    }
    
    
    /**
     * Проверява дали имаме права за регистриране на обаждане
     * 
     * @param string $protectKey - Защитен ключ
     * 
     * @retun boolean - Ако нямаме права, връща FALSE
     */
    static function isAuthorized($protectKey)
    {
        // Вземам конфигурационните данни
        $conf = core_Packs::getConfig('callcenter');
        
        // Ако не отговаря на посочения от нас
        if ($protectKey != $conf->CALLCENTER_PROTECT_KEY) {
            
            // Записваме в лога
            static::log('Невалиден публичен ключ за обаждането: ' . $protectKey);
            
            // Връщаме
            return FALSE;
        }
        
        // Масив с разрешените IP' та
        $allowedIpArr = arr::make($conf->CALLCENTER_ALLOWED_IP_ADDRESS, TRUE);
        
        // Ако е зададено
        if (count($allowedIpArr)) {
            
            // Вземаме IP' то на извикщия
            $ip = core_Users::getRealIpAddr();
            
            // Ако не е в листата на разрешените IP' та
            if (!$allowedIpArr[$ip]) {
                
                // Записваме в лога
                static::log('Недопустим IP адрес: ' . $ip);
                
                return FALSE;
            }
        }
        
        // Ако проверките минат успешно
        return TRUE;
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
        
        $internalNum = $rec->internalNum;
        
        // Вземаме потребителите, които отговарят за съответния номер
        $usersArr = callcenter_Numbers::getUserForNum($internalNum);
        
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
        
        // Функционално поле за търсене по статус и тип на разговора
        //$data->listFilter->FNC('dialStatusType', 'enum()', 'caption=Състояние,input');
        
        // Опции за търсене
        $statusOptions[''] = '';
        
        // Опциите за входящи разговори
        $incomingsOptions = new stdClass();
        $incomingsOptions->title = tr('Входящи');
        $incomingsOptions->attr = array('class' => 'team');
        $incomingsOptions->keylist = 'incomings';
        
        $statusOptions['incoming'] = $incomingsOptions;
        $statusOptions['incoming_ANSWERED'] = tr('Отговорено');
        $statusOptions['incoming_NO ANSWER'] = tr('Без отговор');
        $statusOptions['incoming_BUSY'] = tr('Заето');
        $statusOptions['incoming_FAILED'] = tr('Прекъснато');
        
        // Опциите за изходящи разговоири
        $outgoingsOptions = new stdClass();
        $outgoingsOptions->title = tr('Изходящи');
        $outgoingsOptions->attr = array('class' => 'team');
        $incomingsOptions->keylist = 'outgoings';
        
        $statusOptions['outgoing'] = $outgoingsOptions;
        $statusOptions['outgoing_ANSWERED'] = tr('Отговорено');
        $statusOptions['outgoing_NO ANSWER'] = tr('Без отговор');
        $statusOptions['outgoing_BUSY'] = tr('Заето');
        $statusOptions['outgoing_FAILED'] = tr('Прекъснато');
        
        // Задаваме опциите
        $data->listFilter->setOptions('dialStatus', $statusOptions);
        
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
        $data->listFilter->showFields = 'search, usersSearch, dialStatus';
        
        $data->listFilter->input('search, usersSearch, dialStatus', 'silent');
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
        			    $data->query->orWhereArr('internalNum', $numbersArr);
                    } else {
                        
                        // Не показваме нищо
                        $data->query->where("1=2");
                    }
                }
    		}
    		
            // Ако се търси по статус или вид
            if ($filter->dialStatusType) {
                
                $dialStatusType = $filter->dialStatusType;
                
                // Разделяме статуса от типа
                list($callType, $dialStatus) = explode('_', $dialStatusType);
                
                // Търсим по типа
                $data->query->where(array("#callType = '[#1#]'", $callType));
                
                // Ако търсим по входящи
                if ($callType == 'incoming') {
                    
                    // Търсим по статус
                    $data->query->orWhere("#callType IS NULL");
                }
                
                // Ако е избран статуса на разговора
                if ($dialStatus) {
                    
                    // Търсим по статус на обаждане
                    $data->query->where(array("#dialStatus = '[#1#]'", $dialStatus));
                }
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
            
                // Ако има търсещ номер и е в масива
                if (!($rec->internalNum && in_array($rec->internalNum, $numbersArr))) {
                    
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
        
        // Ако е отговорено
        if (!$rec->dialStatus || $rec->dialStatus == 'ANSWERED') {
            
            // Ако е изходящо обаждане
            if ($rec->callType == 'outgoing') {
                
                // Икона за изходящо обаждане
                $this->singleIcon = 'img/16/outgoing.png';
            } else {
                
                // Ако в входящо
                $this->singleIcon = 'img/16/incoming.png';
            }
        } else {
            
            // Ако е изходящо обаждане
            if ($rec->callType == 'outgoing') {
                
                // Икона за изходящо обаждане
                $this->singleIcon = 'img/16/outgoing-failed.png';
            } else {
                
                // Ако в входящо
                $this->singleIcon = 'img/16/incoming-failed.png';
            }
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
            $data->listFields = arr::make('singleLink=-, externalNum=Външен, internalNum=Вътрешен, startTime=Време');
        }
    }
    
    
    /**
     * Връща стринг с линкове за добавяне на номера във фирма, лица или номера
     * 
     * @param string $num - Номера, за който се отнася
     * @param string $uniqId - Уникално id
     * @param string $numArr - Масив с номера
     * 
     * @return string - Тага за заместване
     */
    static function getTemplateForAddNum($num, $uniqId, $numArr = FALSE)
    {
        // Ако не е подаден масив с номера
        if ($numArr === FALSE) {
            
            // Вземаме масива
            $numArr = drdata_PhoneType::toArray($num);
        }
        
        // Ако не е валиден номер
        // Третираме го като вътрешен
        if (!$numArr) {
        
            // Ако няма роля admin, да не се показва шаблона за нов
            if (!haveRole('admin')) return ;
            
            // Аттрибути за стилове 
            $numbersAttr['title'] = tr('Добави към потребител');
            
            // Икона на телефон
            $phonesImg = "<img src=" . sbf('img/16/telephone2-add.png') . " width='16' height='16'>";
            
            // Създаваме линк
            $text = ht::createLink($phonesImg, array('callcenter_Numbers', 'add', 'number' => $num, 'ret_url' => TRUE), FALSE, $numbersAttr);
        } else {
            
            // Аттрибути за стилове 
            $companiesAttr['title'] = tr('Нова фирма');
            
            // Икона на фирмите
            $companiesImg = "<img src=" . sbf('img/16/office-building-add.png') . " width='16' height='16'>";
            
            // Добавяме линк към създаване на фирми
            $text = ht::createLink($companiesImg, array('crm_Companies', 'add', 'tel' => $num, 'ret_url' => TRUE), FALSE, $companiesAttr);
            
            // Аттрибути за стилове 
            $personsAttr['title'] = tr('Ново лице');
            
            // Икона на изображенията
            $personsImg = "<img src=" . sbf('img/16/vcard-add.png') . " width='16' height='16'>";
            
            // Ако е мобилен номер, полето ще сочи към мобилен
            $personNumField = ($numArr[0]->mobile) ? 'mobile' : 'tel';
            
            // Добавяме линк към създаване на лица
            $text .= " | ". ht::createLink($personsImg, array('crm_Persons', 'add', $personNumField => $num, 'ret_url' => TRUE), FALSE, $personsAttr);
        }
        
        // Дали да се показва или не
        $visibility = (mode::is('screenMode', 'narrow')) ? 'visible' : 'hidden';
        
        // Ако сме в мобилен режим
        if (mode::is('screenMode', 'narrow')) {
            
            // Не се добавя JS
            $res = "<div id='{$uniqId}'>{$text}</div>";
        } else {
            
            // Ако не сме в мобилен режим
            
            // Скриваме полето и добавяме JS за показване
            $res = "<div onmouseover=\"changeVisibility('{$uniqId}', 'visible');\" onmouseout=\"changeVisibility('{$uniqId}', 'hidden');\">
        		<div style='visibility:hidden;' id='{$uniqId}'>{$text}</div></div>";
        }
        
        return $res;
    }
    
    
    /**
     * Извиква се от крона. Променя статуса на разговорите без статус на без отговор
     */
    function cron_FixDialStatus()
    {
        // Вземаме конфигурационните данни
        $conf = core_Packs::getConfig('callcenter');
        
        // Вземаме секундите
        $secs = $conf->CALLCENTER_DRAFT_TO_NOANSWER;
        
        // Изваждаме секундите
        $secsBefore = -1 * $secs;
        $before = dt::addSecs($secsBefore);
        
        // Вземаме всички записи, които нямат dialStatus и са по стари от посоченото време
        $query = static::getQuery();
        $query->where("#dialStatus IS NULL");
        $query->where("#startTime < '$before'");
        
        // Обхождаме резултатите
        while ($rec = $query->fetch()) {
            
            // Променяне статуса
            $rec->dialStatus = 'NO ANSWER';
            
            // Записваме
            static::save($rec);
        }
    }
    
    
	/**
     * Изпълнява се след създаването на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        $res .= "<p><i>Нагласяне на Cron</i></p>";
        
        //Данни за работата на cron
        $rec = new stdClass();
        $rec->systemId = 'fixDialStatus';
        $rec->description = 'Променя статуса на обажданията без статуси на без отговор';
        $rec->controller = $mvc->className;
        $rec->action = 'FixDialStatus';
        $rec->period = 5;
        $rec->offset = 0;
        $rec->delay = 0;
        $rec->timeLimit = 100;
        
        $Cron = cls::get('core_Cron');
        
        if ($Cron->addOnce($rec)) {
            $res .= "<li><font color='green'>Задаване на крон да променя статуса на обажданията без статуси на без отговор.</font></li>";
        } else {
            $res .= "<li>Отпреди Cron е бил нагласен да променя статуса на обажданията без статуси на без отговор.</li>";
        }
    }
    
    
    /**
     * Екшън за тестване
     * Генерира обаждане
     */
    function act_Mockup()
    {
        // Вземам конфигурационните данни
        $conf = core_Packs::getConfig('callcenter');
        
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
            'p' => $conf->CALLCENTER_PROTECT_KEY,
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
            'p' => $conf->CALLCENTER_PROTECT_KEY,
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
