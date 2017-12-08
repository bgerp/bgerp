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
     * Поддържани интерфейси
     */
    public $interfaces = 'support_IssueCreateIntf';
    
    
    /**
     * Разделител за края на uniqId, ако разговора е пренасочен
     */
    protected static $callUniqIdDelimiter = '|';
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Телефонни разговори';
    
    
    /**
     * Наименование на единичния обект
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
     * 
     */
    var $canAddto = 'powerUser';
    
    
    /**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'powerUser';
    
    
    /**
	 * Кой може да архивира разговорите?
	 */
	var $canArchivetalk = 'powerUser';
    
    
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
    var $loadList = 'plg_SelectPeriod,callcenter_Wrapper, plg_RowTools2, plg_Printing, plg_Sorting, plg_RefreshRows, plg_GroupByDate, callcenter_ListOperationsPlg';
    

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
     * 
     */
    var $listFields = '_rowTools, externalData, externalNum, singleLink=-, internalNum, internalData, startTime, duration';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'singleLink';
    
    
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
        $this->FLD('dialStatus', 'enum(NO ANSWER=Без отговор, FAILED=Прекъснато, BUSY=Заето, ANSWERED=Отговорено, UNKNOWN=Няма информация, REDIRECTED=Пренасочено)', 'allowEmpty, caption=Състояние, hint=Състояние на обаждането');
        $this->FLD('uniqId', 'varchar', 'caption=Номер');
        $this->FLD('startTime', 'datetime(format=smartTime)', 'caption=Време->Начало');
        $this->FLD('answerTime', 'datetime(format=smartTime)', 'allowEmpty, caption=Време->Отговор');
        $this->FLD('endTime', 'datetime(format=smartTime)', 'allowEmpty, caption=Време->Край');
        $this->FLD('callType', 'type_Enum(incoming=Входящ, outgoing=Изходящ)', 'allowEmpty, caption=Тип на разговора, hint=Тип на обаждането');
        $this->FLD('duration', 'time', 'caption=Време->Продължителност');
        
        $this->setDbUnique('uniqId');
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
        core_RowToolbar::createIfNotExists($row->_rowTools);
//         $row->_rowTools->removeBtn("single{$rec->id}");
        
        // Информация за външния номер
        $externalNumArr = drdata_PhoneType::toArray($rec->externalNum);
        
        // Ако е валиден номер
        if ($externalNumArr) {
            
            // Ако е мобилен, класа също да е мобилен
            $externalClass = ($externalNumArr[0]->mobile) ? 'mobile' : 'telephone';
            
            // Добавяме стил за телефони        
            $row->externalNum = "<div class='{$externalClass} crm-icon'>" . $row->externalNum . "</div>";
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
                static::getTemplateForAddNum($row->_rowTools, $rec->externalNum, $uniqId, $externalNumArr);
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
                static::getTemplateForAddNum($row->_rowTools, $rec->internalNum, $uniqId, array());
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
                $row->externalNum .= $div . $row->externalData . "</div>";
                $row->internalNum .= $div . $row->internalData . "</div>";
            
                // Ако има продължителност
                if ($rec->duration) {
                    
                    // Ако няма вербална стойност
                    if (!($duration = $row->duration)) {
                     
                        // Вземаме вербалната стойност
                        $duration = static::getVerbal($rec, 'duration');
                    }
                    
                    // Добавяме след времето на позвъняване
                    $row->startTime .= $div . $duration . "</div>";
                }
            }
        }
        
        // В зависмост от състоянието на разгоравя, опделяме клас за реда в таблицата
        if (!$rec->dialStatus) {
            $row->DialStatusClass .= ' dialStatus-opened';
        } elseif ($rec->dialStatus == 'ANSWERED') {
            $row->DialStatusClass .= ' dialStatus-answered';
        } elseif ($rec->dialStatus == 'REDIRECTED') {
            $row->DialStatusClass .= ' dialStatus-redirected';
            
            // Ако няма продължителност на разговора
            if (!$rec->duration) {
                $row->duration = $mvc->getVerbal($rec, 'dialStatus');
            }
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
        
        // Номера, от който е редиректнат
        if ($rec->RedirectFrom = self::getRedirectedFromNum($rec)) {
            $row->RedirectFrom = type_Varchar::escape($rec->RedirectFrom);
        }
        
        // Номера, към който е редиректнат
        if ($rec->RedirectTo = self::getRedirectedToNum($rec)) {
            $row->RedirectTo = type_Varchar::escape($rec->RedirectTo);
        }
        
        // Добавяме бутон за създаване на сигнал
        if ($rec->id && cal_Tasks::haveRightFor('add')) {
            $urlArr = cal_Tasks::getUrlForCreate($rec->id, $mvc->className);
            $row->_rowTools->addLink('Сигнал', $urlArr, 'ef_icon=img/16/support.png, title=Създаване на сигнал от обаждане');
        }
        
        if ($mvc->haveRightFor('archivetalk', $rec)) {
            $row->_rowTools->addLink('Архивиране', array($mvc, 'archive', $rec->id, 'ret_url' => TRUE), 'ef_icon=img/16/upload.png, title=Архивиране на обаждането');
        }
    }
    
    
    /**
     * Екшън за архивиране на обаждането
     */
    function act_Archive()
    {
        $id = Request::get('id', 'int');
        
        expect($id);
        
        $rec = self::fetch($id);
        
        expect($rec);
        
        $this->requireRightFor('archivetalk', $rec);
        
        $archivePlacesArr = self::getDataForArchive($rec);
        
        $nRec = callcenter_Numbers::getRecForInternalNum($rec->internalNum);
        
        $fh = callcenter_Hosts::archiveTalk($nRec->host, $archivePlacesArr);
        
        if ($fh === FALSE) {
            
            $retUrl = getRetUrl();
            
            if (empty($retUrl)) {
                $retUrl = array($this, 'single', $id);
            }
            
            return new Redirect($retUrl, '|Грешка при архивиране на обаждането', 'error');
        } else {
            
            return new Redirect(array('fileman_Files', 'single', $fh), '|Успешно архивирахте обаждането');
        }
    }
    
    
    /**
     * Връща масив с данни, които ще се използват за определяне на файла при архивиране
     * 
     * @param stdObject $rec
     * 
     * @return array
     */
    protected static function getDataForArchive($rec) 
    {
        $recArr = (array)$rec;
        
        if (empty($recArr)) return $recArr;
        
        list($recArr['uniqId']) = explode('|', $recArr['uniqId']);
        
        return $recArr;
    }
    
    
    /**
     * 
     * 
     * @param callcenter_Talks $mvc
     * @param object $res
     * @param object $data
     */
    public static function on_AfterPrepareListRows($mvc, &$res, $data)
    {
        $dialStatusType = Request::get('dialStatusType');
        // Изчистваме нотификацията
        $url = array('callcenter_Talks', 'list', 'dialStatusType' => $dialStatusType);
        bgerp_Notifications::clear($url);
        
        // Добавяме номерата от които са пренасочени обажданията
        foreach ((array)$data->rows as $row) {
            if ($row->RedirectFrom) {
                $row->internalNum = $row->RedirectFrom . ' » ' . $row->internalNum;
            }
            
            if ($row->RedirectTo) {
                $row->internalNum = $row->internalNum . ' » ' . $row->RedirectTo;
            }
        }
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
        $conf = core_Packs::getConfig('callcenter');
        
        $url = toUrl(getCurrentUrl());
        
        self::logDebug('CALL BEGIN: ' . $url);
        
        // Масив с грешките
        $errArr = array();
        
        // Ключа за защита
        $protectKey = Request::get('p');
        
        // Проверяваме дали има права за добавяне на запис
        if (!static::isAuthorized($protectKey)) return FALSE;
        
        // Вземаме променливите
        $startTime = Request::get('starttime');
        $uniqId = Request::get('uniqueId');
        $outgoing = Request::get('outgoing');
        
        // Ако има подадено начално време
        if ($startTime) {
            
            // Вземаме текущото време
            $now = dt::now();
            
            // Вземаме разликата във времето на сървъра и на подадения стринг
            $deviationSecs = abs(dt::secsBetween($now, $startTime));
            
            // Ако разликата е над допустимите
            if (($deviationSecs) && ($deviationSecs > $conf->CALLCENTER_DEVIATION_BETWEEN_TIMES)) {
                
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
        $nRec->externalNum = drdata_PhoneType::getNumberStr($externalNum, 0);
        $nRec->internalNum = drdata_PhoneType::getNumberStr($internalNum, 0);
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
        
        // Когато uniqId съществува, предполагаме, че разговора е пренасочен
        if (!$savedId) {
            
            // Ако за този вътрешен номер няма запис, генерираме ново уникално ид и записваме
            if (!self::fetch(array("#internalNum = '[#1#]' AND #uniqId='[#2#]'", $nRec->internalNum, $nRec->uniqId))) {
                $newUniqId = self::getUniqId($uniqId);
                
                if ($newUniqId != $uniqId) {
                    $nRec->uniqId = $newUniqId;
                    $savedId = static::save($nRec, NULL, 'IGNORE');
                }
            } else {
                // Добавяме грешката
                $errArr[] = 'Дублирана заявка за регистриране на обаждане';
            }
        }
        
        // Ако записът не е успешен
        if (!$savedId) {
            
            // Добавяме грешката
            $errArr[] = 'Грешка при записване';
        } else {
            
            // Ако не е изходящо обаждане
            if (!$outgoing) {
                
                // Ако няма данни
                if (!$cRecArr[0]) {
                    
                    // Да се използва номера
                    $externalData = $externalNum;
                } else {
                    
                    $externalData = $cRecArr;
                }
                
                // Нотифицираме потребителя, за входящото обаждане
                static::notifyUsersForIncoming($externalData, $dRecArr, $savedId);
            }
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
        $conf = core_Packs::getConfig('callcenter');
        
        $url = toUrl(getCurrentUrl());
        
        self::logDebug('CALL END: ' . $url);
        
        // Масив с грешките
        $errArr = array();
        
        // Ключа за защита
        $protectKey = Request::get('p');
        
        // Проверяваме дали има права за добавяне на запис
        if (!static::isAuthorized($protectKey)) return FALSE;
        
        // Вземаме уникалното id на разговора
        $uniqId = Request::get('uniqueId');
        
        // Вземаме последния запис
        $rec = self::getLastTalksRec($uniqId);
        
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
            if (strpos($answerTime, '%')) {
                $errArr[] = 'Време->Отговор е двойно ескейпнат';
                $answerTime = urldecode($answerTime);
            }
            
            $endTime = Request::get('endtime');
            if (strpos($endTime, '%')) {
                $errArr[] = 'Време->Край е двойно ескейпнат';
                $endTime = urldecode($endTime);
            }
            
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
                if (($deviationsSecAnswEnd) && ($deviationsSecAnswEnd > $conf->CALLCENTER_DEVIATION_BETWEEN_TIMES)) {
                    
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
                if (($deviationSecsAnsw) && ($deviationSecsAnsw > $conf->CALLCENTER_DEVIATION_BETWEEN_TIMES)) {
                    
                    // Разликата във вербален вид
                    $deviationAnswVerbal = $TimeInst->toVerbal($deviationSecsAnsw);
                    
                    // Записваме в лога
                    $errArr[] = "Разминаване във времето на сървъра и подаденото в URL с {$deviationAnswVerbal} за Време->Отговор";
                    
                    // Задаваме текущото време
                    $answerTime = $now;
                }
            }
            
            // Ако има време на край
            if ($endTime) {
                
                // Вземаме разликата във времето на сървъра и на подадения стринг
                $deviationSecsEnd = abs(dt::secsBetween($now, $endTime));
                
                // Ако разликата е над допустимите
                if (($deviationSecsEnd) && ($deviationSecsEnd > $conf->CALLCENTER_DEVIATION_BETWEEN_TIMES)) {
                    
                    // Разликата във вербален вид
                    $deviationEndVerbal = $TimeInst->toVerbal($deviationSecsEnd);
                    
                    // Записваме в лога
                    $errArr[] = "Разминаване във времето на сървара и подаденото в URL с {$deviationEndVerbal} за Време->Край";
                    
                    // Задаваме текущото време
                    $endTime = $now;
                }
            }
            
            // Ако не е подаден статус на обаждането
            if (!($dialStatus = Request::get('dialstatus'))) {
                
                // Добавяме грешката
                $errArr[] = 'Не е подаден статус на обаждането';
            }
            
            if (trim($answerTime)) {
                $rec->answerTime = $answerTime;
            }
            
            if (trim($endTime)) {
                $rec->endTime = $endTime;
            }
            
            // Ако не е бил зададен отпреди
            if (!isset($rec->dialStatus)) {
                $rec->dialStatus = $dialStatus;
            } else {
            
                // Отбелязваме обаждането като пренасочено
                $rec->dialStatus = 'REDIRECTED';
            }
            
            // Определяме продължителнността на разговоря
            $rec->duration = self::getDuration($rec->answerTime, $rec->endTime);
            
            // Обновяваме записа
            $savedId = static::save($rec, NULL, 'UPDATE');
            
            // Добавяме нотификация
            static::addNotification($rec);
            
            if (self::isRedirected($rec)) {
                
                // Маркираме обаждането от което е пренасочено, като 'REDIRECTED'
                self::markParentAsRedirected($rec->uniqId);
            }
        } else {
            // Ако няма такъв запис
            
            // Добавяме грешката
            $errArr[] = 'Няма регистрирано начало на разговор';
        }
        
        // Ако има грешки, ги записваме в лога
        static::errToLog($errArr, $savedId, getSelfURL());
        
        // Връщаме
        return TRUE;
    }
    
    
    /**
     * Генерира ново уникално id
     * 
     * @param string $uniqId
     * 
     * @return string
     */
    protected static function getUniqId($uniqId)
    {
        $i = 0;
        do {
            
            if ($i++ > 100) error('@Unable to generate uniqId', $uniqId);
            
            $newUniqId = self::prepareUniqId($uniqId, $i);
            
        } while(self::fetch(array("#uniqId = '[#1#]'", $newUniqId)));
        
        return $newUniqId;
    }
    
    
    /**
     * Подготва стринг с uniqId
     * 
     * @param string $uniqId
     * @param integer $str
     * 
     * @return string
     */
    protected static function prepareUniqId($uniqId, $id)
    {
        $newUniq = $uniqId;
        
        if ($id) {
            $newUniq .= self::$callUniqIdDelimiter . $id;
        }
        
        return $newUniq;
    }
    

    /**
     * Проверява дали е редиректнато обаждането
     * 
     * @param object $rec
     * 
     * @return boolean
     */
    protected static function isRedirected($rec)
    {
        if (strpos($rec->uniqId, self::$callUniqIdDelimiter)) return TRUE;
        
        return FALSE;
    }
    
    
    /**
     * Маркира обаждането от което е пренасочено, като redirected
     * 
     * @param string $uniqId
     */
    protected static function markParentAsRedirected($uniqId)
    {
        $parentRec = self::getParentRecForUniqId($uniqId);
        
        if (!$parentRec) return;
        
        $parentRec->dialStatus = 'REDIRECTED';
        
        self::save($parentRec, 'dialStatus');
        
        // Добавяме нотификация
        self::addNotification($parentRec);
    }
    
    
    /**
     * Връща последния запис в модела за uniqId, като се броят и пренасочените
     * 
     * @param string $uniqId
     * 
     * @return object
     */
    protected static function getLastTalksRec($uniqId)
    {
        if (!$uniqId) return FALSE;
        
        $query = self::getQuery();
        $query->where(array("#uniqId LIKE '[#1#]%'", $uniqId . self::$callUniqIdDelimiter));
        $query->orWhere(array("#uniqId = '[#1#]'", $uniqId));
        
        $query->orderBy("id", 'DESC');
        $query->limit(1);
        $rec = $query->fetch();
        
        return $rec;
    }
    
    
    /**
     * Връща номера, от който е пренасочено обаждането
     * 
     * @param object $rec
     * 
     * @return string
     */
    protected static function getRedirectedFromNum($rec)
    {
        if (!self::isRedirected($rec)) return ;
        
        $parentRec = self::getParentRecForUniqId($rec->uniqId);
        
        if (!$parentRec) return ;
        
        return $parentRec->internalNum;
    }
    
    
    /**
     * Връща родителя от който е генерирано съответното uniqId
     * 
     * @param string $uniqId
     * 
     * @return object
     */
    protected static function getParentRecForUniqId($uniqId)
    {
        if (!strpos($uniqId, self::$callUniqIdDelimiter)) return FALSE;
        
        $uniqIdArr = explode(self::$callUniqIdDelimiter, $uniqId);
        
        $id = 0;
        $uniqId = '';
        
        if (count($uniqIdArr) > 1) {
            $id = array_pop($uniqIdArr);
        }
        
        $uniqId = implode(self::$callUniqIdDelimiter, $uniqIdArr);
        
        if ($id > 1) {
            $id--;
            $uniqId = self::prepareUniqId($uniqId, $id);
        }
        
        $rec = self::fetch(array("#uniqId = '[#1#]'", $uniqId));
        
        return $rec;
    }
    
    
    /**
     * Връща номера към който е пренасочено обаждането
     * 
     * @param object $rec
     * 
     * @return string
     */
    protected static function getRedirectedToNum($rec)
    {
        $parentRec = self::getChildRecForUniqId($rec->uniqId);
        
        if (!$parentRec) return ;
        
        return $parentRec->internalNum;
    }
    
    
    /**
     * Връща записа за новото uniqId, генерирано от подадения стринг
     * 
     * @param string $uniqId
     * 
     * @return object
     */
    protected static function getChildRecForUniqId($uniqId)
    {
        $uniqIdArr = explode(self::$callUniqIdDelimiter, $uniqId);
        
        if (count($uniqIdArr) > 1) {
            $id = array_pop($uniqIdArr);
        }
        
        $uniqId = implode(self::$callUniqIdDelimiter, $uniqIdArr);
        
        if (!$id) {
            $id = 0;
        }
        
        $id++;
        
        $uniqId = self::prepareUniqId($uniqId, $id);
        
        $rec = self::fetch(array("#uniqId = '[#1#]'", $uniqId));
        
        return $rec;
    }
    
    
    
    /**
     * Връща продълбителността на разговора
     * 
     * @param datetime $answerTime
     * @param datetime $endTime
     * 
     * @return NULL|integer
     */
    public static function getDuration($answerTime, $endTime)
    {
        $duration = NULL;
        if ($answerTime && $endTime) {
                
            $dateTime = cls::get('type_Datetime');
            $defVal = $dateTime->defVal();
            
            if (($answerTime != $defVal) && ($endTime != $defVal)) {
                $duration = dt::secsBetween($endTime, $answerTime);
            }
        }
        
        return $duration;
    }
    
    
    /**
     * Нотифицира потребителите за входящо обождане
     * 
     * @param mixed $externalData - Масив с данните за позвъняващия
     * @param array $internalDataArr - Масив с данни за търсените
     * @param integer $id - id на записа
     */
    static function notifyUsersForIncoming($externalData, $internalDataArr, $id)
    {
        // Обхождаме масива с вътрешните номера
        foreach ((array)$internalDataArr as $intData) {
            
            // Масив с номерата на позвъняващия
            $numbersArr = array();
            
            // Ако няма клас или контрагент за вътрешните номера
            if (!$intData->classId || !$intData->contragentId) continue;
            
            // Инстанция на класа
            $class = cls::get($intData->classId);
            
            // Ако класа не е профил, прескачаме
            if (!($class instanceof crm_Profiles)) continue;
            
            // id на потребител, който е от отговорниците за номера
            $userId = crm_Profiles::fetchField($intData->contragentId, 'userId');
            
            // Ако са подадени данни за номера - няма запис в callcenter_Numbers
            if (is_array($externalData)) {
            
                // Обхождаме всички външни номера / по принцип трябва да е един
                foreach ((array)$externalData as $data) {
                    $user = '';
                    $number = '';
                    // Името на позвъняващия
                    $name = callcenter_Numbers::getCallerName($data->id, $userId);
                    
                    // Ако има име
                    if ($data->classId && $data->contragentId && $name) {
                        
                        $extClass = cls::get($data->classId);
                        
                        // Ако имаме права за сингъл до името
                        if ($extClass->haveRightFor('single', $data->contragentId, $userId)) {
                            
                            // Името да сочи към сингъла
                            $user = ht::createLink($name, array($extClass, 'single', $data->contragentId));
                        }
                    }
                    
                    $number = $data->number;
                    
                    // Ако има права за листване на централата, линка да сочи там
                    if (static::haveRightFor('list', NULL, $userId)) {
                        $number = ht::createLink($number, array('callcenter_Talks', 'list', 'number' => $data->number));
                    }
                    
                    // Ако има права до сингъла на фирмата, линка да сочи там
                    $numberStr = $number;
                    if ($user) {
                        $numberStr = $user . ' - ' . $number;
                    }
                    
                    // Масив с номерата
                    $numbersArr[] = $numberStr;
                }
            } else {
                
                // Ако не е подаден и номер
                if ($externalData) {
                    
                    $number = $externalData;
                    
                    // Ако има права за листване на централата, линка да сочи там
                    if (static::haveRightFor('list', NULL, $userId)) {
                        $number = ht::createLink($number, array('callcenter_Talks', 'list', 'number' => $number));
                    }
                } else {
                    
                    $number = tr('Скрит номер');
                    
                    // Ако има права за листване на централата, линка да сочи там
                    if (static::haveRightFor('list', NULL, $userId)) {
                        $number = ht::createLink($number, array('callcenter_Talks', 'list'));
                    }
                }
                
                // Масив с номерата
                $numbersArr[] = $number;
            }
            
            // Стринг с номерата
            $numbersStr = implode(', ', $numbersArr);
            
            // Съобщението, което да се покаже
            $text = "|Входящо обаждане от|*: " . $numbersStr;
            
            // Добавяме известие към съответния потребител
            status_Messages::newStatus($text, 'notice', $userId);
        }
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
            self::logWarning($err, $err);
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
            
            $errMsg = 'Невалиден публичен ключ за обаждането';
            
            // Записваме в лога
            self::logErr($errMsg . ': ' . $protectKey);
            
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
                self::logErr('Недопустим IP адрес в конфигурацията');
                
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
        // Ако е изходящо обаждане или е отговорено
        if ($rec->dialStatus == 'ANSWERED' || $rec->callType == 'outgoing') return;
        
        $isRedirected = FALSE;
        
        // Параметри на нотификацията
        $priority = 'normal';
        $customUrl = array('callcenter_Talks', 'list');
        
        if ($rec->dialStatus == 'REDIRECTED') {
            // Линка да сочи към пренасочените повиквания
            $customUrl['dialStatusType'] = 'incoming_REDIRECTED';
            $isRedirected = TRUE;
        } else {
            // Линка да сочи към всички пропуснати повиквания
            $customUrl['dialStatusType'] = 'incoming_MISSED';
        }
        
        // Вземаме потребителите, които отговарят за съответния номер
        $usersArr = callcenter_Numbers::getUserForNum($rec->internalNum);
        
        // Обхождаме всички потребители
        foreach ((array)$usersArr as $user) {
            
            // Времето на последно виждане на листовия изглед
            $lastClosedTime = bgerp_Notifications::getLastClosedTime($customUrl, $user);
            
            // Ако няма време на последно виждане, максимум преди 1 седмица
            if (!$lastClosedTime) {
                $lastClosedTime = dt::subtractSecs(604800);
            }
            
            // Вземаме всички неотоговрени входящи обаждания към съответния номер след последното време за листване
            $query = static::getQuery();
            
            // Ако има време на последно затваряне
            if ($lastClosedTime) {
                
                // След последното виждане
                $query->where(array("#startTime > '[#1#]'", $lastClosedTime));
            }
            
            // Само входящи обаждания, къс съответния номер и без отговор
            $query->where(array("#internalNum = '[#1#]'", $rec->internalNum));
            $query->where("#dialStatus != 'ANSWERED'");
            $query->where("#callType = 'incoming'");
            
            // Последните обаждания са с по голям приоритет
            $query->orderBy('startTime', 'DESC');
            
            if ($isRedirected) {
                $query->where("#dialStatus = 'REDIRECTED'");
            }
            
            // Броя на пропуснатите обажданият от резултата
            $qCnt = $query->count();
            
            // Ако няма пропуснати повиквания, да прескача
            if (!$qCnt) continue;
            
            // В зависимост от броя, определяме стринга за съобещение
            if ($qCnt > 1) {
                
                $dialTypeText = ($isRedirected) ? 'пренасочени' : 'пропуснати';
                
                $message = "|Имате| {$qCnt} |{$dialTypeText} повиквания от|*";
            } else {
                
                $dialTypeText = ($isRedirected) ? 'пренасочено' : 'пропуснато';
                
                $message = "|Имате {$dialTypeText} повикване от|*";
            }
            
            // Нулираме стойностите
            $CallerArr = array();
            $namesStr = '';
            
            while ($nRec = $query->fetch()) {
                
                // Името, на позвъняващия
                $callerName = callcenter_Numbers::getCallerName($nRec->externalData, $user);
                
                // Ако не може да се определи име
                if (!$callerName) {
                    
                    // Използваме номера му
                    $callerName = $nRec->externalNum;
                }
                
                // Ако все още няма име, прескачаме
                if (!$callerName) continue;
                
                // Увеличваме брояча за номера в масива
                $CallerArr[$callerName]++;
            }
            
            // Броят на позвъняващите хора/номера
            $arrCnt = count($CallerArr);
            
            foreach ($CallerArr as $name => $cnt) {
                
                // Добавяме името към стринга
                $namesStr .= $name;
                
                // Ако има повече от едно позвъняване от съответния номер и
                // Ако има повече от един различен номер, който звъни
                if (($cnt > 1) && ($arrCnt > 1)) {
                    
                    // Добавяме броя на обажданият след името
                    $namesStr .= '(' . $cnt . ')';
                }
                
                $namesStr .= ', ';
            }
            
            // Премахваме, ненужните символи след края
            $namesStr = rtrim($namesStr, ', ');
            
            // Ограничаваме дължината, за да може да се побере в полето, което е 255
            $namesStr = str::limitLen($namesStr, 200);
            
            if (!$namesStr) {
                $namesStr = '"|Скрит номер|*"';
            }
            
            // Добавяме номерата/имената към съобщението
            $message = $message . ' ' . $namesStr;
            
            // Нотифицираме съответния потребител
            bgerp_Notifications::add($message, $customUrl, $user, $priority);
        }
    }
    
    
    /**
     * 
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Използваме собсвен лейаут за тъсене
        $data->listFilter->layout = new ET(tr('|*' . getFileContent('callcenter/tpl/TalksFilterForm.shtml')));
        
        // Поле за търсене по номера
        $data->listFilter->FNC('number', 'drdata_PhoneType', 'caption=Номер,input,silent, recently');
        
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('usersSearch', 'users(rolesForAll=ceo|callcenter, rolesForTeams=ceo|manager|callcenter)', 'caption=Потребител,input,silent,autoFilter');
        
        // Функционално поле за търсене по статус и тип на разговора
        $data->listFilter->FNC('dialStatusType', 'enum()', 'caption=Състояние,input,autoFilter');
        
        // Полета за търсене по дата
        $data->listFilter->FNC('from', 'date', 'width=6em,caption=От,input');
		$data->listFilter->FNC('to', 'date', 'width=6em,caption=До,input');
		
        // Опции за търсене
        $statusOptions[''] = '';
        
        // Опциите за входящи разговори
        $incomingsOptions = new stdClass();
        $incomingsOptions->title = tr('Входящи');
        $incomingsOptions->attr = array('class' => 'team');
        $incomingsOptions->keylist = 'incomings';
        
        $statusOptions['incoming'] = $incomingsOptions;
        $statusOptions['incoming_ANSWERED'] = tr('Отговорено');
        $statusOptions['incoming_REDIRECTED'] = tr('Пренасочени');
        $statusOptions['incoming_NO ANSWER'] = tr('Без отговор');
        $statusOptions['incoming_BUSY'] = tr('Заето');
        $statusOptions['incoming_FAILED'] = tr('Прекъснато');
        $statusOptions['incoming_MISSED'] = tr('Пропуснати');
        
        // Опциите за изходящи разговоири
        $outgoingsOptions = new stdClass();
        $outgoingsOptions->title = tr('Изходящи');
        $outgoingsOptions->attr = array('class' => 'team');
        $outgoingsOptions->keylist = 'outgoings';
        
        $statusOptions['outgoing'] = $outgoingsOptions;
        $statusOptions['outgoing_ANSWERED'] = tr('Отговорено');
        $statusOptions['outgoing_NO ANSWER'] = tr('Без отговор');
        $statusOptions['outgoing_BUSY'] = tr('Заето');
        $statusOptions['outgoing_FAILED'] = tr('Прекъснато');
        
        // Задаваме опциите
        $data->listFilter->setOptions('dialStatusType', $statusOptions);
        
        // Ако имаме тип на обаждането
        if ($typeOptions = &$data->listFilter->getField('callType')->type->options) {
            
            // Добавяме в началото празен стринг за всички
            $typeOptions = array('all' => '') + $typeOptions;
            
            // Избираме го по подразбиране
            $data->listFilter->setDefault('callType', 'all');
        }
        
        // В хоризонтален вид
        $data->listFilter->view = 'vertical';
        
        // Добавяме бутон
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields .= ($data->listFilter->showFields ? ',':'') . 'number, usersSearch, dialStatusType, from, to';
        
        // Инпутваме заявката
        $data->listFilter->input('number, usersSearch, dialStatusType, from, to', 'silent');
        
        // Ако не е избран потребител по подразбиране
        if(!$data->listFilter->rec->usersSearch) {
            
            // Да е текущия
            $data->listFilter->rec->usersSearch = '|' . core_Users::getCurrent() . '|';
        }
        
        // Сортиране на записите по num
        $data->query->orderBy('startTime', 'DESC');
        $data->query->orderBy('id', 'DESC');
        
        // Ако има филтър
        if($filter = $data->listFilter->rec) {
            
            // Ако се търси по номера
            if ($number = $filter->number) {
                
                // Премахваме нулите и + от началото на номера
                $number = ltrim($number, '0+');
                
                $number = str_replace('*', '%', $number);
                
                // Търсим във външните и вътрешните номера
                $data->query->where(array("#externalNum LIKE '%[#1#]'", $number));
                $data->query->orWhere(array("#internalNum LIKE '%[#1#]'", $number));
                
                if (!Request::get('usersSearch')) {
                    $userSearchType = $data->listFilter->getFieldType('usersSearch');
                    $filter->usersSearch = $userSearchType->fitInDomain('all_users');
                }
            }
            
            // Ако филтъра е по потребители
            if ($filter->usersSearch) {
                
    			// Ако се търси по всички и има права admin или ceo
    			if (strpos($filter->usersSearch, '|-1|') === FALSE) {
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
                    // Ако статуса е пропуснат
                    if ($dialStatus == 'MISSED') {
                        // Показва всички обаждания, които не са отговорени или пренасочени
                        $data->query->where("#dialStatus != 'ANSWERED'");
                        $data->query->where("#dialStatus != 'REDIRECTED'");
                    } else {
                        // Търсим по статус на обаждане
                        $data->query->where(array("#dialStatus = '[#1#]'", $dialStatus));
                    }
                }
            }
            
            // Масив с датите
            $dateRange = array();
	        
            // Ако е зададено от
	        if ($filter->from) {
	            
	            // Добавяме в масива
	            $dateRange[0] = $filter->from; 
	        }
	        
	        // Ако е зададено до
	        if ($filter->to) {
	            
	            // Добавяме в масива
	            $dateRange[1] = $filter->to; 
	        }
	        
	        // Ако има от и до
	        if (count($dateRange) == 2) {
	            
	            // Подреждаме масива
	            sort($dateRange);
	        }
	        
	        // Ако има от
            if($dateRange[0]) {
                
                // Разговори приети От дата
    			$data->query->where(array("#startTime >= '[#1#]'", $dateRange[0]));
    		}
            
    		// Ако има до
			if($dateRange[1]) {
			    
			    // Разговори До дата
    			$data->query->where(array("#startTime <= '[#1#] 23:59:59'", $dateRange[1]));
    		}
        }
    }
    
    
    /**
     * 
     * 
     * @param unknown_type $mvc
     * @param unknown_type $res
     * @param unknown_type $data
     */
    static function on_AfterPrepareListSummary($mvc, &$res, &$data)
    {
        // Ако няма заявка, да не се изпълнява
        if (!$data->listSummary->query) return ;
        
    	// Обхождаме всички клонирани записи
        while ($rec = $data->listSummary->query->fetch()) {
            
            // Статус на разговора
            $dialStatus = $rec->dialStatus;
            
            // Ако няма статус
            if (!$dialStatus) {
                $dialStatus = 'OTHER';
            } elseif ($dialStatus == 'NO ANSWER') {
                
                // Заместваме празния интервал с долна черта
                $dialStatus = 'NO_ANSWER';
            }
            
            // Добавяме продължителността
            $stat['duration'] += $rec->duration;
            
            // Увеличаваме броя на съответния статус
            $stat['dialStatus'][$dialStatus]++;
            
            // Увеличаваме броя на всички състояния
            $stat['dialStatus']['TOTAL']++;
        }
        
        // Статистика за разговорите
        $data->listSummary->stat = $stat;
        
        // Ако има продължителност
        if ($stat['duration']) {
            
            // Инстанция на класа
            $Time = cls::get('type_Time');
            
            // Вербално представяне
            $stat['duration'] = $Time->toVerbal($stat['duration']);
        } else {
            
            // Премахваме продължителността на разговора, ако е 0
            unset($stat['duration']);
        }
        
        // Добавяме вербалните стойности
        $data->listSummary->statVerb = $stat;
    }
    
    
    /**
     * 
     * 
     * @param unknown_type $mvc
     * @param unknown_type $tpl
     * @param unknown_type $data
     */
    static function on_AfterRenderListSummary($mvc, &$tpl, &$data)
    {
        // Ако няма данни, няма да се показва нищо
        if (!$data->listSummary->statVerb) return ;
        
    	// Зареждаме и подготвяме шаблона
    	$tpl = getTplFromFile(("callcenter/tpl/CallSummary.shtml"));
    	
    	// Заместваме продължителността на разговора
    	$tpl->append($data->listSummary->statVerb['duration'], 'duration');
    	
    	// Заместваме статусите на обажданията
    	$tpl->placeArray($data->listSummary->statVerb['dialStatus']);
    	
    	// Премахваме празните блокове
		$tpl->removeBlocks();
		$tpl->append2master();
    }
    
    
    /**
     * 
     * 
     * @param callcenter_Talks $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param NULL|stdObject $rec
     * @param NULL|integer $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Ако искаме да отворим сингъла на документа
        if ($rec->id && $action == 'single' && $userId) {
            
            // Ако нямаме роля CEO
            if (!haveRole('ceo, callcenter', $userId)) {
                
                // Ако сме мениджър
                if (haveRole('manager', $userId)) {
                    
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
        
        if ($requiredRoles != 'no_one') {
            if ($action == 'archivetalk') {
                if (!$mvc->haveRightFor('single', $rec, $userId)) {
                    $requiredRoles = 'no_one';
                } else {
                    if (!$rec->internalNum) {
                        $requiredRoles = 'no_one';
                    } else {
                        if (!callcenter_Numbers::canUseHostForNum($rec->internalNum)) {
                            $requiredRoles = 'no_one';
                        }
                    }
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
        } else {
            
            // Ако в входящо
            $this->singleIcon = 'img/16/incoming.png';
        }
        
        // Ако е отговорено
        if ($rec->dialStatus && ($rec->dialStatus != 'ANSWERED') && ($rec->dialStatus != 'REDIRECTED')) {
            
            // Ако е изходящо обаждане
            if ($rec->callType == 'outgoing') {
                
                // Икона за изходящо обаждане
                $this->singleIcon = 'img/16/outgoing-failed.png';
            } else {
                
                // Ако в входящо
                $this->singleIcon = 'img/16/incoming-failed.png';
            }
        } else if ($rec->dialStatus == 'REDIRECTED') {
            
            // Ако е изходящо обаждане
            if ($rec->callType != 'outgoing') {
                
                // Ако в входящо и е пренасочено
                $this->singleIcon = 'img/16/incoming-redirected.png';
            }
        }
        
        return $this->singleIcon;
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
            $data->listFields = arr::make('_rowTools, externalNum=Външен, singleLink=-, internalNum=Вътрешен, startTime=Време');
        }
    }
    
    
    /**
     * Връща стринг с линкове за добавяне на номера във фирма, лица или номера
     * 
     * @param core_RowToolbar $rowTools
     * @param string $num - Номера, за който се отнася
     * @param string $uniqId - Уникално id
     * @param string $numArr - Масив с номера
     * @param NULL|string $type
     */
    static function getTemplateForAddNum($rowTools, $num, $uniqId, $numArr = FALSE, $type = NULL)
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
            
            $rowTools->addLink('Добави', array('callcenter_Numbers', 'add', 'number' => $num, 'ret_url' => TRUE), 'ef_icon=img/16/telephone2-add.png, title=Добави към потребител');
        } else {
            
            if (isset($type)) {
                $personNumField = $type;
            } else {
                $personNumField = 'tel';
            }
            
            $rowTools->addLink('Нова фирма', array('crm_Companies', 'add', $personNumField => $num, 'ret_url' => TRUE), 'ef_icon=img/16/office-building-add.png, title=Създай нова фирма от номера');
            
            if (!isset($type)) {
                // Ако е мобилен номер, полето ще сочи към мобилен
                $personNumField = ($numArr[0]->mobile) ? 'mobile' : 'tel';
            }
            $rowTools->addLink('Ново лице', array('crm_Persons', 'add', $personNumField => $num, 'ret_url' => TRUE), 'ef_icon=img/16/vcard-add.png, title=Създай нова фирма от номера');
            
            if (self::haveRightFor('addto')) {
                
                Request::setProtected(array('num'));
                
                // Бутон за добавяне на номера към
                $rowTools->addLink('Добави към...', array(get_called_class(), 'addTo', 'num' => $num, 'ret_url' => TRUE), 'ef_icon=img/16/add.png, title=Добавяне на номера към съществуващ контрагент');
            }
        }
    }
    
    
    /**
     * Екшън за прикачане на номер към контрагент
     */
    function act_AddTo()
    {
        Request::setProtected(array('num'));
        
        $this->requireRightFor('addto');
        $form = cls::get('core_Form');
        $form->title = "Добавяне на номера към фирма или лице";
        
        $form->FNC('sel', 'enum(, company_tel=Телефон на фирма, company_fax=Факс на фирма,
                                  person_buzTel=Служебен номер на лице, person_buzFax=Служебен факс на лице,
                                  person_tel=Номер на лице, person_fax=Факс на лице, person_mobile=Мобилен на лице)', 'caption=Добавяне към, mandatory, input=input,refreshForm');
        
        $form->FNC('num', 'drdata_PhoneType');
        
        $form->input();
        
        $class = $field = $className = NULL;
        
        if ($form->rec->sel) {
            list($class, $field) = explode('_', $form->rec->sel);
        }
        
        // Ако е избран клас, показваме избор на контрагент
        if (isset($class)) {
            
            $className = 'crm_Persons';
            $caption = 'Лице';
            
            if ($class == 'company') {
                $className = 'crm_Companies';
                $caption = 'Фирма';
            }
            
            $form->FNC('contragentId', "key(mvc={$className}, select=name, restrictViewAccess=yes)", "caption={$caption}, input=input, mandatory");
            $form->input('contragentId');
        }
        
        $retUrl = getRetUrl();
        
        if (empty($retUrl)) {
            $retUrl = array(get_called_class(), 'list');
        }
        
        // Добавяме номера към съответния контрагент
        if($form->isSubmitted()){
            
            expect($className && $form->rec->num);
            
            $inst = cls::get($className);
            
            $iRec = $inst->fetch($form->rec->contragentId);
            
            expect($iRec);
            
            $iRec->{$field} .= trim($iRec->{$field}) ? ', ' : '';
            $iRec->{$field} .= $form->rec->num;
            
            if ($inst->save($iRec, $field)) {
                $inst->logWrite('Добавен номер', $iRec->id);
                $this->logWrite("Добавяне на телефонен номер към контрагент");
                
                return new Redirect($retUrl, 'Успешно добавихте номера');
            } else {
                $form->setError('sel', 'Възникна грешка при добавяне');
            }
        }
        
        // Добавяне на бутони
        $form->toolbar->addSbBtn('Добави', 'save', 'ef_icon = img/16/add.png, title=Запис на документа');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        return $this->renderWrapping($form->renderHtml());
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
        $query->where("#dialStatus IS NULL OR #dialStatus = ''");
        $query->where("#startTime < '$before'");
        
        // Обхождаме резултатите
        while ($rec = $query->fetch()) {
            
            // Ако е отговрено на обаждането
            if ($rec->answerTime && $rec->endTime) {
                
                // Променяме статуса на отговорено
                $rec->dialStatus = 'ANSWERED';
            } else {
                // Променяме статуса на пропуснат
                $rec->dialStatus = 'NO ANSWER';
            }
            
            // Записваме
            static::save($rec, 'dialStatus');
            
            // Добавяме нотификация
            static::addNotification($rec);
        }
    }
    
    
	/**
     * Изпълнява се след създаването на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        //Данни за работата на cron
        $rec = new stdClass();
        $rec->systemId = 'fixDialStatus';
        $rec->description = 'Променят се статусите на обажданията от "без статуси" на "без отговор"';
        $rec->controller = $mvc->className;
        $rec->action = 'FixDialStatus';
        $rec->period = 5;
        $rec->offset = 0;
        $rec->delay = 0;
        $rec->timeLimit = 100;
        
        $res .= core_Cron::addOnce($rec);

        // Миграция, за стари бази, където може да имаме сгрешени времена за разговорите
        $conf = core_Packs::getConfig('callcenter');
        
        // Ако има зададена стойност
        if ($conf->CALLCENTER_MAX_CALL_DURATION > 0) {
            
            // Всички разговори, които са над допостимата дължината
            $query = static::getQuery();
            $query->where(array("ADDDATE(#answerTime, INTERVAL [#1#] SECOND) < #endTime", $conf->CALLCENTER_MAX_CALL_DURATION));
            
            while($rec = $query->fetch()) {
                
                // Променяме края на разговора, да е в допустимите граници
                $rec->endTime = dt::addSecs($conf->CALLCENTER_MAX_CALL_DURATION, $rec->answerTime);
                
                // Записваме промените
                static::save($rec, 'endTime');
                
                // Масив с id-тата на променени разговори
                $changedTalksArr[$rec->id] = $rec->id;
            }
            
            // Ако има промение разговори
            if ($changedTalksArr) {
                
                // Броя на променените разговори
                $cnt = count($changedTalksArr);
                
                if ($cnt == 1) {
                    $word = 'разговор';
                } else { 
                    $word = 'разговорa';
                }
                
                $changetTalksStr = implode(', ', $changedTalksArr);
                
                $res .= "<li><span class=\"green\">Бяха съкратение времената на {$cnt} {$word} - {$changetTalksStr}</span></li>";
            }
        }
        
        // Ако има отговорени разговори с вкарани лоши данни
        
        // Всички отговорени разговори с некоректни времена за endTime или answerTime
        $nQuery = static::getQuery();
        $unixNull = date("Y-m-d h:i:s", 0);
        $nQuery->where("#dialStatus = 'ANSWERED'");
        $nQuery->where("#endTime <= '{$unixNull}'");
        $nQuery->orWhere("#answerTime <= '{$unixNull}'");
        
        while ($nRec = $nQuery->fetch()) {
            
            // Флаг, дали да се записва
            $save = FALSE;
            
            // Ако времето на отговор е лошо
            if ($nRec->answerTime <= $unixNull) {
                
                // Ако все пак има подадено време на край
                if ($nRec->endTime > $unixNull) {
                    
                    // От крайното време определяме началото
                    $nRec->answerTime = dt::subtractSecs($conf->CALLCENTER_MAX_CALL_DURATION, $nRec->endTime);
                    
                    // Вдигаме флага
                    $save = TRUE;
                } else {
                    
                    // Ако няма време на край, тогава използваме началото за позвъняване за начало на разговора
                    $nRec->answerTime = $nRec->startTime;
                }
            }
            
            // Ако времето за край на разговора е лошо
            if ($nRec->endTime <= $unixNull) {
                
                // Ако има добро време на начало на позвъняване
                if ($nRec->answerTime > $unixNull) {
                    
                    // Определяме времето за край на разговора
                    $nRec->endTime = dt::addSecs($conf->CALLCENTER_MAX_CALL_DURATION, $nRec->answerTime);
                    
                    // Вдигаме флага
                    $save = TRUE;
                }
            }
            
            // Ако флага е вдигнат
            if ($save) {
                
                // Записваме 
                static::save($nRec);
                
                // Добавяме в масива
                $nChangedTalksArr[$nRec->id] = $nRec->id;
            }
        }
        
        // Ако има промение разговори
        if ($nChangedTalksArr) {
            
            // Броя на променените разговори
            $cnt = count($nChangedTalksArr);
            
            if ($cnt == 1) {
                $word = 'разговор';
            } else { 
                $word = 'разговорa';
            }
            
            $changetTalksStr = implode(', ', $nChangedTalksArr);
            
            $res .= "<li><span class=\"green\">Бяха променени времената на {$cnt} {$word} - {$changetTalksStr}</span></li>";
        }
    }
    
    
    /**
     * Екшън за тестване
     * Генерира "фалшиви" обаждане
     */
    function act_Mockup()
    {
        requireRole('admin');
        
        // Вземам конфигурационните данни
        $conf = core_Packs::getConfig('callcenter');
        
        // Текущото време - времето на позвъняване
        $startTime = dt::now();
        
        if (!$status = Request::get('status')) {
            
            // Масив със статусите
            $staturArr = array('NO ANSWER', 'FAILED', 'BUSY', 'ANSWERED', 'UNKNOWN', 'ANSWERED', 'ANSWERED', 'ANSWERED', 'ANSWERED', 'ANSWERED', 'ANSWERED');
            
            // Избираме един случаен стату
            $status = $staturArr[rand(0, 10)];
        }
        
        // Ако е отговорен
        if ($status == 'ANSWERED') {
            
            // Времето в UNIX
            $unixTime = dt::mysql2timestamp($startTime);
            
            // Времето за отговор
            $answerTime = $unixTime + rand(3, 7);
            
            // Времето на края на разговора
            $endTime = $unixTime + rand(22, 388);
            
            // Преобразуваме ги в mySQL формат
            $myAnswerTime = dt::timestamp2Mysql($answerTime);
            $myEndTime = dt::timestamp2Mysql($endTime);
        }
        
        // Генерираме рандом чило за уникалното id
        $uniqId = rand();
        
        // Вътрешен номер
        if (!$extension = Request::get('extension')) {
            $extension = 540;
        }
        
        // Позвъняващ
        if (!$callerId = Request::get('callerId')) {
            $callerId = 539;
        }
        
        // Масив за линка
        $urlArr = array(
            'Ctr' => 'callcenter_Talks',
            'Act' => 'RegisterCall',
            'p' => $conf->CALLCENTER_PROTECT_KEY,
            'starttime' => $startTime,
            'extension' => $extension, // Вътрешен номер
            'callerId' => $callerId, // Позвъняващ
            'uniqueId' => $uniqId,
//            'outgoing' => 'outgoing',
        );
        
        // Ако е изходящо обаждане
        if (Request::get('outgoing')) {
            $urlArr['outgoing'] = 'outgoing';
        }
        
        // Вземаме абсолютния линк
        $url = toUrl($urlArr, 'absolute-force');
        
        $url = escapeshellarg($url);
        
        // Извикваме линка
        exec("wget -q --spider --no-check-certificate {$url}");
        
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
        
        // Ако е изходящо обаждане
        if (Request::get('outgoing')) {
            $urlArr['outgoing'] = 'outgoing';
        }
        
        // Вземаме абсолютния линк
        $url = toUrl($urlArr, 'absolute-force');
        
        $url = escapeshellarg($url);
        
        // Извикваме линка
        exec("wget -q --spider --no-check-certificate {$url}");
    }
    
    
    /**
     * Връща хеша за листовия изглед. Вика се от plg_RefreshRows
     * 
     * @param string $status
     * 
     * @return string
     * @see plg_RefreshRows
     */
    function getContentHash($status)
    {
        // Премахваме всички тагове
        $hash = md5(trim(strip_tags($status)));
        
        return $hash;
    }
    
    
	/**
     * Връща запис с подразбиращи се данни за сигнала
	 * 
	 * @param integer $id Кой е пораждащия комит
	 * 
	 * @return stdObject за cal_Tasks
	 * 
	 * @see support_IssueCreateIntf
	 */
    function getDefaultIssueRec($id)
    {
        if (!$id) return ;
        
        $rec = $this->fetch($id);
        
        if (!$rec) return ;
        
        $iRec = new stdClass();
        
        $phoneInfo = $rec->externalNum;
        
        if ($rec->externalData) {
            $phoneInfo = callcenter_Numbers::getCallerName($rec->externalData);
        }
        
        $iRec->title = tr('Разговор с') . ' "' . $phoneInfo . '"';
        $iRec->description = tr('Във връзка с') . ' ' . toUrl(array($this, 'Single', $id), TRUE);
        
        return $iRec;
    }
    
    
    /**
	 * След създаване на сигнал от документа
	 * 
	 * @param integer $id
	 * @param stdClass $iRec
	 * 
	 * @see support_IssueCreateIntf
	 */
    function afterCreateIssue($id, $iRec)
    {
        
        return ;
    }
    
	
    /**
     * След подготовка на тулбара на единичен изглед.
	 * 
	 * @param core_Mvc $mvc
	 * @param object $data
	 */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        if ($data->rec->id && cal_Tasks::haveRightFor('add')) {
            $urlArr = cal_Tasks::getUrlForCreate($data->rec->id, $mvc->className);
            $data->toolbar->addBtn('Сигнал', $urlArr,'ef_icon = img/16/support.png,title=Създаване на сигнал');
        }
    }
    
    
    /**
     * Преобразува линка към single' на файла richtext линк
     * 
     * @param integer $id - id на записа
     * 
     * @return string $res - Линка в richText формат
     */
    function getVerbalLinkFromClass($id)
    {
        
        return self::getLinkToSingle($id);
    }
}
