<?php 


/**
 * Колко дни да се пази в лога
 */
defIfNot('DOCLOG_DOCUMENTS_DAYS', 5);


/**
 * История от събития, свързани с документите
 *
 * Събитията са изпращане по имейл, получаване, връщане, печат, разглеждане, използване
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Stefan Stefanov <stefan.bg@gmail.com> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doclog_Documents extends core_Manager
{
    
    
    /**
     * Брой елементи на страница
     */
    var $itemsPerPage = 20;
    
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Лог на документи";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo';
    
    
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
    var $canView = 'ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created';
    
    
    /**
     * @todo Чака за документация...
     */
    var $listFields = 'createdOn, createdBy, action=Какво, containerId=Кое, dataBlob';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'log_Documents';
    
    
    /**
     * Масов-кеш за историите на контейнерите по нишки
     *
     * @var array
     */
    protected static $histories = array();
    
    
    /**
     * Домейн на записите в кеша
     *
     * @see core_Cache
     */
    const CACHE_TYPE = 'thread_history';
    
    
    /**
     * Екшъна за изпращане
     */
    const ACTION_SEND = 'send';
    
    
    /**
     * Екшъна за връщане
     */
    const ACTION_RETURN = '_returned';
    
    
    /**
     * Екшъна за получаване
     */
    const ACTION_RECEIVE = '_received';
    
    
    /**
     * Екшъна за отваряне
     */
    const ACTION_OPEN = 'open';
    
    
    /**
     * Екшъна за печатане
     */
    const ACTION_PRINT = 'print';
    
    
    /**
     * Екшъна за показване
     */
    const ACTION_DISPLAY = 'display';
    
    
    /**
     * Екшъна за факс
     */
    const ACTION_FAX = 'fax';
    
    
    /**
     * Екшъна за PDF
     */
    const ACTION_PDF = 'pdf';
    
    
    /**
     * Екшън за експортиране
     */
    const ACTION_EXPORT = 'export';
    
    
    /**
     * Екшъна за сваляне
     */
    const ACTION_DOWNLOAD = 'download';
    
    
    /**
     * Екшъна за действията/историята на документа
     */
    const ACTION_HISTORY = 'history';
    
    
    /**
     * Екшъна за промяна
     */
    const ACTION_CHANGE = 'changed';
    
    
    /**
     * Екшъна за препращане
     */
    const ACTION_FORWARD = 'forward';
    
    
    /**
     * Екшън за използване
     */
    const ACTION_USED = 'used';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        // enum полетата на екшъните
        $actionsEnum = array(
            self::ACTION_SEND    . '=имейл',
            self::ACTION_RETURN  . '=връщане',
            self::ACTION_RECEIVE . '=получаване',
            self::ACTION_OPEN    . '=показване',
            self::ACTION_PRINT   . '=отпечатване',
            self::ACTION_DISPLAY . '=разглеждане',
            self::ACTION_FAX     . '=факс',
            self::ACTION_PDF     . '=PDF',
            self::ACTION_EXPORT     . '=експорт',
            self::ACTION_DOWNLOAD . '=сваляне',
            self::ACTION_CHANGE . '=промяна',
            self::ACTION_FORWARD . '=препращане',
            self::ACTION_USED . '=използване',
        );
        
        // Тип на събитието
        $this->FLD("action", 'enum(' . implode(',', $actionsEnum) . ')', "caption=Действие");
        
        // Нишка на документа, за който се отнася събитието
        $this->FLD('threadId', 'key(mvc=doc_Threads)', 'caption=Нишка');
        
        // Документ, за който се отнася събитието
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'caption=Контейнер');
        
        // MID на документа
        $this->FLD('mid', 'varchar(8,ci)', 'input=none,caption=Ключ,column=none');
        
        $this->FLD('parentId', 'key(mvc=doclog_Documents, select=action)', 'input=none,caption=Основание');
        
//         $this->FLD('baseParentId', 'key(mvc=doclog_Documents, select=action)', 'input=none,caption=Основание');
        
        // Допълнителни обстоятелства, в зависимост от събитието (в PHP serialize() формат) и компресирани
        $this->FLD("dataBlob", "blob(serialize, compress)", 'caption=Обстоятелства,column=none');
        
        // Други функционални полета
        $this->FNC('data', 'text', 'input=none,column=none');
        $this->FNC('receivedOn', 'datetime(format=smartTime)', 'input=none');
        $this->FNC('returnedOn', 'datetime(format=smartTime)', 'input=none');
        $this->FNC('openAction', 'html', 'input=none');
        $this->FNC('time', 'datetime(format=smartTime)', 'input=none, caption=Време');
        $this->FNC('from', 'user', 'input=none');
        $this->FNC('ip', 'ip', 'input=none');
        $this->FNC('toEmail', 'emails', 'input=none');
        $this->FNC('fromEmail', 'key(mvc=email_Inboxes, select=email)', 'input=none');
        $this->FNC('cc', 'emails', 'input=none');
        $this->FNC('faxTo', 'drdata_PhoneType', 'input=none');
        $this->FNC('service', 'class(interface=email_SentFaxIntf, select=title)', 'input=none');
        
        $this->setDbIndex('containerId');
        $this->setDbIndex('mid');
        $this->setDbIndex('threadId');

        $this->setDbUnique('containerId, action, mid');
    } 
    
    
    /**
     * Изчислява data полето
     */
    function on_CalcData($mvc, $rec)
    {
        // Вземаме dataBlob
        $rec->data = $rec->dataBlob;
        
        // Ако е празно
        if (empty($rec->data)) {
            
            // Нов празен обект
            $rec->data = new StdClass();
        }
    }
    

    /**
     * Изчислява receivedOn
     */
    function on_CalcReceivedOn($mvc, $rec)
    {
        // Ако екшъна е изпращане и има receivedOn в data
		if ($rec->action == static::ACTION_SEND && !empty($rec->data->receivedOn)) {
		    
		    // Използваме него
			$rec->receivedOn = $rec->data->receivedOn;
		}
    }
    
    
    /**
     * Изчислява returnedOn
     */
    function on_CalcReturnedOn($mvc, $rec)
    {
        // Ако екшъна е изпращане и има returnedOn в data
		if ($rec->action == static::ACTION_SEND && !empty($rec->data->returnedOn)) {
		    
		    // Използваме него
			$rec->returnedOn = $rec->data->returnedOn;
		}
    }
    
    
    /**
     * След изчислянване на вербалната стойност
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        // Ако има from
        if ($rec->from) {
            
            // Линк към визитката
            $row->from = crm_Profiles::createLink($rec->from);
        }
        
        // Декорираме IP адреса
        if ($rec->ip) {
            $row->ip = ' ' . type_Ip::decorateIp($rec->ip, $rec->time, TRUE);
        }
    }
    
    
    /**
     * Подготвяне на данните за рендиране на детайла за препращания
     * 
     * @param object $data
     */
    function prepareForward($data)
    {
        // Ако сме в режим принтиране
        // Да не се изпълнява
        if (Request::get('Printing')) return ;
        
        // Вземаме cid от URL' то
        $cid = Request::get('Cid', 'int');
        
        // Ако не листваме данните за съответния контейнер
        if ($data->masterData->rec->containerId != $cid) return ;
        
        // Името на таба
        $data->TabCaption = tr('Препращания');
        
        // Екшъна
        $action = static::ACTION_FORWARD;
        
        // Вземаме записите
        $recs = static::getRecs($cid, $action);
        
        // Ако няма записи не се изпълнява
        if (empty($recs)) {
            
            // Бутона да не е линк
            $data->disabled = TRUE;
            
            return ;
        }
        
        // Масив с данните във вербален вид
        $rows = array();
        
        foreach ($recs as $rec) {
            krsort($rec->data->{$action});
        }
        
        $dataRecsArr = $this->getRecsForPaging($data, $recs, $action);
        
        // Обхождаме всички препратени записи
        foreach ($dataRecsArr as $forwardRec) {
            
            // Записите
            $row = (object)array(
                'time' => $forwardRec['on'],
                'from' => $forwardRec['from'],
            );

            // Записите във вербален вид
            $row = static::recToVerbal($row, array_keys(get_object_vars($row)));
            
            // Вземаме документите
            $doc = doc_Containers::getDocument($forwardRec['containerId']);

            // Ако имаме права за сингъл на документ
            if ($doc->haveRightFor('single')) {
            
                // Вербални данни на докуемент
                $docRow = $doc->getDocumentRow();
                
                // Създаваме линк към документа
                $row->document = ht::createLink($docRow->title, array($doc->className, 'single', $doc->that));    
            }
            
            // Добавяме в главния масив
            $rows[] = $row;    
        }

        // Заместваме данните за рендиране
        $data->rows = $rows; 
    }
    
    
    /**
     * Връща масив със записи за странира
     * 
     * @param object $data
     * @param array $recs
     * @param string $action
     * 
     * @return array
     */
    protected function getRecsForPaging(&$data, $recs, $action)
    {
        $resArr = array();
        
        $cid = $data->masterData->rec->containerId;
        
        // Създаваме странициране
        $data->pager = cls::get('core_Pager', array('itemsPerPage' => $this->itemsPerPage, 'pageVar' => 'P_doclog_Documents'));
        
        // URL' то където ще сочат
        $data->pager->url = toUrl(static::getLinkToSingle($cid, $action));
        
        $allDataAct = array();
        
        // Обхождаме записите
        foreach ($recs as $rec) {
            
            if (!$rec->data->{$action}) continue;
            
            foreach ($rec->data->{$action} as $actVal) {
                $allDataAct[] = $actVal;
            }
        }
        
        $cnt = count($allDataAct);
        
        if (!$cnt) return $resArr;
        
        $data->pager->itemsCount = $cnt;
        $data->pager->calc();
        
        $curr = 0;
        $showedCnt = 0;
        $limit = $data->pager->rangeEnd - $data->pager->rangeStart;
        
	    foreach ($allDataAct as $val) {
	        if (isset($data->pager->rangeStart) && isset($data->pager->rangeEnd)) {
                $curr++;
                
                if ($curr <= $data->pager->rangeStart) continue;
                
                if ($showedCnt >= $limit) break;
            }
            
            $resArr[] = $val;
            
            $showedCnt++;
	    }
	    
	    return $resArr;
    }
    
    
    /**
     * Рендиране на данните за шаблона на детайла за препращания
     * 
     * @param object $data
     */
    function renderForward($data)
    {
        // Ако няма записи
        if (!$data->rows) return ;
        
        // Вземаме шаблона за детайлите с попълнена титла
        $tpl = static::getLogDetailTpl();
        
        // Инстанция на класа
        $inst = cls::get('core_TableView');

        // Вземаме таблицата с попълнени данни
        $forwardTpl = $inst->get($data->rows, 'time=Дата, from=Потребител, document=Документ');
        
        // Заместваме в главния шаблон за детайлите
        $tpl->append($forwardTpl, 'content');
        
        // Добавяме странициране
        $tpl->append($data->pager->getHtml());
        
        return $tpl;
    }
    
    
    /**
     * Подготвяне на данните за рендиране на детайла за принтирания
     * 
     * @param object $data
     */
    function preparePrint($data)
    {
        // Ако сме в режим принтиране
        // Да не се изпълнява
        if (Request::get('Printing')) return ;
        
        // Вземаме cid от URL' то
        $cid = Request::get('Cid', 'int');
        
        // Ако не листваме данните за съответния контейнер
        if ($data->masterData->rec->containerId != $cid) return ;
        
        // Името на таба
        $data->TabCaption = tr('Отпечатвания');
        
        // Създаваме странициране
        $data->pager = cls::get('core_Pager', array('itemsPerPage' => $this->itemsPerPage, 'pageVar' => 'P_doclog_Documents'));
        
        // URL' то където ще сочат
        $data->pager->url = toUrl(static::getLinkToSingle($cid, static::ACTION_PRINT));
        
        // Екшъните
        $actionArr = array(static::ACTION_PRINT, static::ACTION_PDF, static::ACTION_EXPORT);
        
        // Вземаме записите
        $recs = static::getRecs($cid, $actionArr, NULL, $data->pager);
        
        // Ако няма записи не се изпълнява
        if (empty($recs)) {
            
            // Бутона да не е линк
            $data->disabled = TRUE;
            
            return ;
        }
        
        // Обхождаме записите
        foreach ($recs as $rec) {
            
            // Записите
            $row = (object)array(
                'time' => $rec->createdOn,
                'from' => $rec->createdBy,
                'action' => $rec->action,
            );

            // Записите във вербален вид
            $row = static::recToVerbal($row, array_keys(get_object_vars($row)));
            
            $row->action = tr($row->action);
            $row->action = str::mbUcfirst($row->action);
            
            // Екшъна за отваряне
            $openAction = static::ACTION_OPEN;
            
            // Състоянието
            $state = ($rec->data->{$openAction}) ? 'state-closed' : 'state-active';
            
            // Екшъна за отваряне
            $row->openAction = self::renderOpenActions($rec);
            
            // Добавяме индикатор за състоянието
            $time = "<div>";
            $time .= "<div class='stateIndicator {$state}'>";
            $time .= "</div> <div class='inline-date'>{$row->time}</div></div>";
            
            // Заместваме времето с индикатора и времето
            $row->time = $time;
            
            // Добавяме в главния масив
            $rows[$rec->id] = $row;    
        }

        // Сортираме по дата
        krsort($rows);
        
        // Заместваме данните за рендиране
        $data->rows = $rows; 
    }
    
    
    /**
     * Рендиране на данните за шаблона на детайла за принтирания
     * 
     * @param object $data
     */
    function renderPrint($data)
    {
        // Ако няма записи
        if (!$data->rows) return ;
        
        // Вземаме шаблона за детайлите с попълнена титла
        $tpl = static::getLogDetailTpl();
        
        // Инстанция на класа
        $inst = cls::get('core_TableView');
        
        // Вземаме таблицата с попълнени данни
        $printTpl = $inst->get($data->rows, 'time=Дата, from=Потребител, action=Действие, openAction=Видяно');
        
        // Заместваме в главния шаблон за детайлите
        $tpl->append($printTpl, 'content');
        
        // Добавяме странициране
        $tpl->append($data->pager->getHtml());
        
        return $tpl;
    }
    
    
    /**
     * Подготвяне на данните за рендиране на детайла за отваряния
     * 
     * @param object $data
     */
    function prepareOpen($data)
    {
        // Ако сме в режим принтиране
        // Да не се изпълнява
        if (Request::get('Printing')) return ;
        
        // Вземаме cid от URL' то
        $cid = Request::get('Cid', 'int');
        
        // Ако не листваме данните за съответния контейнер
        if ($data->masterData->rec->containerId != $cid) return ;
        
        // Името на таба
        $data->TabCaption = tr('Виждания');
        
        // Екшъна
        $action = static::ACTION_OPEN;
        
        // Вземаме записите
        $recs = static::getRecs($cid);

        // Ако няма записи не се изпълнява
        if (empty($recs)) {
            
            // Бутона да не е линк
            $data->disabled = TRUE;
            
            return ;
        }
        
        // Масив с данния във вербален вид
        $rows = array();
                
        $i = 0;
        
        $nRecsArr = array();
        
        foreach ($recs as $rec) {
            // Ако не виждан
            if (!$rec->data->{$action} || !count($rec->data->{$action}))  continue;
            
            foreach ($rec->data->{$action} as $ip => $act) {
                $act['ParentRec'] = $rec;
                $nRecsArr[$act['on'] . ' '. $i++] = $act;
            }
        }
        
        if (!$nRecsArr) {
            $data->disabled = TRUE;
            
            return ;
        }
        
        krsort($nRecsArr);
        
        // Създаваме странициране
        $data->pager = cls::get('core_Pager', array('itemsPerPage' => $this->itemsPerPage, 'pageVar' => 'P_doclog_Documents'));
        
        // URL' то където ще сочат
        $data->pager->url = toUrl(static::getLinkToSingle($cid, $action));
        
        $openCnt = count($nRecsArr);
        
        $data->pager->itemsCount = $openCnt;
        $data->pager->calc();
        
        $curr = 0;
        $showedCnt = 0;
        $limit = $data->pager->rangeEnd - $data->pager->rangeStart;
        
        foreach ($nRecsArr as $o) {
            
            if (isset($data->pager->rangeStart) && isset($data->pager->rangeEnd)) {
                $curr++;
                
                if ($curr <= $data->pager->rangeStart) continue;
                
                if ($showedCnt >= $limit) break;
            }
            
            $showedCnt++;
            
            // Данните, които ще се визуализрат
            $row = (object)array(
                'time' => $o['on'],
                'ip' => $o['ip'],
                'openAction' => static::formatViewReason($o['ParentRec'])
            );
            
            // Данните във вербален вид
            $row = static::recToVerbal($row, array_keys(get_object_vars($row)));
            
            // Добавяме в масива
            $rows[] = $row;
            
        }
        
        // Дабавяме в $data
        $data->rows = $rows; 
    }
    
    
	/**
     * Рендиране на данните за шаблона на детайла за отваряния
     * 
     * @param object $data
     */
    function renderOpen($data)
    {
        // Ако няма записи
        if (!$data->rows) return ;
        
        // Вземаме шаблона за детайлите с попълнена титла
        $tpl = static::getLogDetailTpl();
        
        // Инстанция на класа
        $inst = cls::get('core_TableView');
        
        // Вземаме таблицата с попълнени данни
        $openTpl = $inst->get($data->rows, 'time=Дата, ip=IP, openAction=Основание');
        
        // Заместваме в главния шаблон за детайлите
        $tpl->append($openTpl, 'content');
        
        // Добавяме странициране
        $tpl->append($data->pager->getHtml());
        
        return $tpl;
    }
    
    
    /**
     * Подготвяне на данните за рендиране на детайла за изпращания
     * 
     * @param object $data
     */
    function prepareSend($data)
    {
        // Ако сме в режим принтиране
        // Да не се изпълнява
        if (Request::get('Printing')) return ;
        
        // Вземаме cid от URL' то
        $cid = Request::get('Cid', 'int');
        
        // Ако не листваме данните за съответния контейнер
        if ($data->masterData->rec->containerId != $cid) return ;
        
        // Името на таба
        $data->TabCaption = tr('Изпращания');
        
        // Екшъните
        $actionArr = array(static::ACTION_SEND, static::ACTION_FAX);
        
        // Създаваме странициране
        $data->pager = cls::get('core_Pager', array('itemsPerPage' => $this->itemsPerPage, 'pageVar' => 'P_doclog_Documents'));
        
        // URL' то където ще сочат
        $data->pager->url = toUrl(static::getLinkToSingle($cid, static::ACTION_SEND));
        
        // Вземаме записите
        $recs = static::getRecs($cid, $actionArr, NULL, $data->pager);

        // Ако няма записи не се изпълнява
        if (empty($recs)) {
            
            // Бутона да не е линк
            $data->disabled = TRUE;
            
            return ;
        }
        
        // Вземаме всички записи
        foreach ($recs as $i=>$rec) {

            // Изчистваме нотификациите
            $linkArr = static::getLinkToSingle($rec->containerId, static::ACTION_SEND);
            bgerp_Notifications::clear($linkArr, $rec->createdBy);
            
            // Данните, които ще се визуализрат
            $row = (object)array(
                'time' => $rec->createdOn,
                'from' => $rec->createdBy,
            	'toEmail' => $rec->data->to,
                'cc' => $rec->data->cc,
                'returnedOn' => $rec->returnedOn,
                'fromEmail' => $rec->data->from,
            );
            
            // Ако е факс
            if ($rec->data->faxTo) {
                
                // Добавяме факса и услугата
                $row->faxTo = $rec->data->faxTo;
                $row->service = $rec->data->service;
            }
            
            // Записите във вербален вид
            $row = static::recToVerbal($row, array_keys(get_object_vars($row)));
            
            // Рендираме екшъна за виждане
            $row->receivedOn = self::renderOpenActions($rec, $rec->receivedOn);

            // Полето за върнато и получено
            $row->returnedAndReceived = $row->receivedOn;
            
            // Ако има връщане
            if ($row->returnedOn) {
                
                $returnedStr = '';
                
                // Ако има отворено
                if ($rec->data->receivedOn) {
                    
                    // Добавяме нов ред
                    $returnedStr = "<br />";
                }
                
                // Добавяме го
                $returnedStr .= tr("Върнато") . ": {$row->returnedOn}";
                
                // Ip от което е върнато
                if ($rec->data->returnedIp) {
                    $returnedStr .= ' ' . type_Ip::decorateIp($rec->data->returnedIp, $rec->data->returnedOn, TRUE);
                }
                
                $row->returnedAndReceived .=  $returnedStr;
            }
            
            // Имейлите До
            $row->emails = $row->toEmail;
            
            // Ако има копие
            if ($row->cc) {
                
                // Добавяме към имейлите
                $row->emails .= "<br />" . tr("Kп") . ": {$row->cc}";
            }
            
            // Добавяме имейла от който е изпратен
            if ($row->fromEmail && $rec->data->sendedBy) {
                $row->emails = $row->fromEmail . " -> " . $row->emails;
            }
            
            // Ако имаме факс номер
            if ($row->faxTo) {
                
                // Ако има имейл
                if ($row->emails) {
                    
                    // Добавяме нов ред
                    $row->emails .= "<br />";
                }
                
                // Добаваме факса
                $row->emails .= tr("Факс") . ": {$row->faxTo}";
                
                // Добавяме услугата
                $row->emails .= " ({$row->service})";
            }
            
            // Стейта на класа
            $stateClass = 'state-active';
            switch (true) {

                // Ако е получен
                case !empty($row->receivedOn):
                    $stateClass = 'state-closed';
                    break;
                    
                // Ако е върнато
                case !empty($row->returnedOn):
                    $stateClass = 'state-stopped';
                    
                    // На върнатите имейли целият ред да е оцветен
                    $row->ROW_ATTR['class'] = "row-state-returned";
                    break;
                
            }
            
            // Индикатор за състоянието
            $time = "<div>";
            $time .= "<div class='stateIndicator {$stateClass}'>";
            $time .= "</div> <div class='inline-date'>{$row->time}</div></div>";
            
            // Заместваме времето с индикатора и времето
            $row->time = $time;
            
            // Добавяме в масива
            $rows[$rec->id] = $row;
        }

        // Сортираме по дата
        krsort($rows);
        
        // Заместваме данните за рендиране
        $data->rows = $rows;
    }
    
    
    /**
     * Рендиране на данните за шаблона на детайла за изпращания
     * 
     * @param object $data
     */
    function renderSend($data)
    {
        // Ако няма записи
        if (!$data->rows) return ;
        
        // Вземаме шаблона за детайлите с попълнена титла
        $tpl = static::getLogDetailTpl();
        
        // Инстанция на класа
        $inst = cls::get('core_TableView');
        
        // Вземаме таблицата с попълнени данни
        $sendTpl = $inst->get($data->rows, 'time=Дата, from=Потребител, emails=До, returnedAndReceived=Получено');
        
        // Заместваме в главния шаблон за детайлите
        $tpl->append($sendTpl, 'content');
        
        // Добавяме странициране
        $tpl->append($data->pager->getHtml());
        
        return $tpl;
    }
    
    
    /**
     * Подготвяне на данните за рендиране на детайла за сваляния
     * 
     * @param object $data
     */
    function prepareDownload($data)
    {
        // Ако сме в режим принтиране
        // Да не се изпълнява
        if (Request::get('Printing')) return ;
        
        // Вземаме cid от URL' то
        $cid = Request::get('Cid', 'int');
        
        // Ако не листваме данните за съответния контейнер
        if ($data->masterData->rec->containerId != $cid) return ;
        
        // Името на таба
        $data->TabCaption = tr('Сваляния');
        
        // Екшъна
        $action = static::ACTION_DOWNLOAD;
        
        // Вземаме записите
        $recs = static::getRecs($cid, $action);

        // Ако няма записи не се изпълнява
        if (empty($recs)) {
            
            // Бутона да не е линк
            $data->disabled = TRUE;
            
            return ;
        }
       
        $rows = array();
        
        $i = 0;
        
        $nArr = array();
        foreach ($recs as $key => $rec) {
            foreach ($rec->data->{$action} as $fh => $rArr) {
                foreach ($rArr as $dArr) {
                    $dArr['fileHnd'] = $fh;
                    $nArr[$dArr['seenOnTime'] . ' ' . $i++] = $dArr;
                }
            }
        }
        $rec->data->{$action} = $nArr;
        
        krsort($rec->data->{$action});
        
        $dataRecsArr = $this->getRecsForPaging($data, $recs, $action);
        
        // Обхождаме всички сваляния
        foreach ($dataRecsArr as $downData) {
            // СЪздаваме обект със запсиите
            $nRec = (object)array(
                'time' => $downData['seenOnTime'],
                'from' => $downData['seenFrom'],
                'ip' => $downData['ip'],
            );
            
            // Вземаме вербалните стойности
            $row = static::recToVerbal($nRec, array_keys(get_object_vars($nRec)));
            
            // Превръщаме манипулатора, в линк за сваляне
            $row->fileHnd = fileman_Files::getLink( $downData['fileHnd']);
            
            // Ако потребител от системата е свалил файла, показваме името му, в противен случай IP' то
            $row->ip = $row->from ? $row->from : $row->ip;
            
            // Записваме в масив данните, с ключ датата
            $rows[] = $row;    
        }
        
        // Променяме всички вербални данни, да показват откритите от нас
        $data->rows = $rows;
    }
	
	
	/**
     * Рендиране на данните за шаблона на детайла за сваляния
     * 
     * @param object $data
     */
    function renderDownload($data)
    {
        // Ако няма записи
        if (!$data->rows) return ;
        
        // Вземаме шаблона за детайлите с попълнена титла
        $tpl = static::getLogDetailTpl();
        
        // Инстанция на класа
        $inst = cls::get('core_TableView');
        
        // Вземаме таблицата с попълнени данни
        $sendTpl = $inst->get($data->rows, 'time=Дата, ip=Свалено от, fileHnd=Файл');
        
        // Заместваме в главния шаблон за детайлите
        $tpl->append($sendTpl, 'content');
        
        $tpl->append($data->pager->getHtml());
        
        return $tpl;
    }
    
    
    /**
     * Подготвяне на данните за рендиране на детайла за промени
     * 
     * @param object $data
     */
    function prepareChanged($data)
    {
        // Ако сме в режим принтиране
        // Да не се изпълнява
        if (Request::get('Printing')) return ;
        
        // Вземаме cid от URL' то
        $cid = Request::get('Cid', 'int');
        
        // Ако не листваме данните за съответния контейнер
        if ($data->masterData->rec->containerId != $cid) return ;
        
        // Името на таба
        $data->TabCaption = tr('Промени');
        
        // Екшъна
        $action = static::ACTION_CHANGE;
        
        // Вземаме записите
        $recs = static::getRecs($cid, $action);

        // Ако няма записи не се изпълнява
        if (empty($recs)) {
            
            // Бутона да не е линк
            $data->disabled = TRUE;
            
            return ;
        }
       
        $rows = array();
        
        // Обхождаме записите
        foreach ($recs as $rec) {

            // Ако няма зададени действия прескачаме
            if (count($rec->data->{$action}) == 0) continue;

            // Обхождаме всички сваляния
            foreach ($rec->data->{$action} as $changeData) {
               
                // Ако няма docId или docClass прескачаме
                if (!$changeData['docId'] || !$changeData['docClass']) continue;
                
                // Вземаме запите
                $rows = change_Log::prepareLogRow($changeData['docClass'], $changeData['docId']);

                break;
            }
        }

        // Променяме всички вербални данни, да показват откритите от нас
        $data->rows = $rows;
    }
    
    
    /**
     * Рендиране на данните за шаблона на детайла за промени
     * 
     * @param object $data
     */
    function renderChanged($data)
    {
        // Ако няма записи
        if (!$data->rows) return ;
        
        // Вземаме шаблона за детайлите с попълнена титла
        $tpl = static::getLogDetailTpl();
        
        // Инстанция на класа
        $inst = cls::get('core_TableView');
        
        // Вземаме таблицата с попълнени данни
        $sendTpl = $inst->get($data->rows, 'createdOn=Дата, createdBy=От, Version=Версия');
        
        // Заместваме в главния шаблон за детайлите
        $tpl->append($sendTpl, 'content');
        
        return $tpl;
    }
    
    
    /**
     * Подготвяне на данните за рендиране на детайла за действията с документа
     * 
     * @param object $data
     */
    function prepareHistory($data)
    {
        // Ако сме в режим принтиране
        // Да не се изпълнява
        if (Request::get('Printing')) return ;
        
        // Вземаме cid от URL' то
        $cid = Request::get('Cid', 'int');
        
        // Ако не листваме данните за съответния контейнер
        if ($data->masterData->rec->containerId != $cid) return ;
        
        $data->TabCaption = tr('История');
        
        $action = static::ACTION_HISTORY;
        
        $document = doc_Containers::getDocument($cid);
        
        // Създаваме странициране
        $data->pager = cls::get('core_Pager', array('itemsPerPage' => $this->itemsPerPage, 'pageVar' => 'P_doclog_Documents'));
        
        // URL' то където ще сочат
        $data->pager->url = toUrl(static::getLinkToSingle($cid, static::ACTION_HISTORY));
        
        // Вземаме записите
        $recs = log_Data::getRecs($document, $document->that, $data->pager);
        
        // Ако няма записи бутона да не е линк
        if (empty($recs)) {
            
            $data->disabled = TRUE;
            
            return ;
        }
        
        $data->rows = log_Data::getRows($recs, array('actionCrc', 'actTime', 'userId', 'ROW_ATTR'));
    }
    
    
    /**
     * Рендиране на данните за шаблона на детайла за действията с документа
     * 
     * @param object $data
     */
    static function renderHistory($data)
    {
        // Ако няма записи
        if (!$data->rows) return ;
        
        // Вземаме шаблона за детайлите с попълнена титла
        $tpl = static::getLogDetailTpl();
        
        // Инстанция на класа
        $inst = cls::get('core_TableView');
        
        // Вземаме таблицата с попълнени данни
        $logTpl = $inst->get($data->rows, 'actTime=Дата, userId=Потребител, actionCrc=Действие');
        
        // Заместваме в главния шаблон за детайлите
        $tpl->append($logTpl, 'content');
        
        // Добавяме странициране
        $tpl->append($data->pager->getHtml());
        
        return $tpl;
    }
    
    
    /**
     * Връща шаблона на детайла
     * 
     * @return core_ET
     */
    static function getLogDetailTpl()
    {
        // Шаблона
        $tpl = getTplFromFile('doclog/tpl/LogDetail.shtml');
        
        return $tpl;
    }
    
    
    /**
     * Връща записа за съответния контейнер със съответния екшъна
     * 
     * @param integer|NULL $cid - containerId
     * @param mixed $action - Масив или стринг с екшъна
     * 
     * @return array $recsArr - Масив с намерените записи
     */
    static function getRecs($cid = NULL, $action = NULL, $threadId = NULL, &$pager = NULL)
    {
        // Очакваме да има $cid
        expect($cid || $threadId);
        
        // Вземаме всики със записис от съответния контейнер
        $query = static::getQuery();
        if ($cid) {
            $query->where("#containerId = '{$cid}'");
        } else if ($threadId) {
            $query->where("#threadId = '{$threadId}'");
        }
        
        // Ако има подаден action
        if ($action) {
            
            // Ако е масив
            if (is_array($action)) {
                
                // Добавяме екшъните с или
                $query->orWhereArr('action', $action);
            } else {
                
                // Ако не е масив, а стринг добавяме екшъна в клаузата
                $query->where("#action = '{$action}'");
            }
        }
        
        // Ако е подаден обект за странициране
        if ($pager) {
            
            // Задаваме лимита за странициране
            $pager->setLimit($query);
        }
        
        // Записите да се подреждат по дата в обратен ред
        $query->orderBy('createdOn', 'DESC');
        
        $recsArr = array();
        
        // Намираме всички записи, които отговарят на критериите ни
        while ($rec = $query->fetch()) {
            
            // Добавяме в масива
            $recsArr[] =  $rec;
        }
        
        return $recsArr;
    }

    
    /**
     * 
     */
    public static function saveAction($actionData)
    {
        $rec = (object)array_merge((array)static::getAction(), (array)$actionData);
        
        if (empty($rec->parentId)) {
            if (($parentAction = static::getAction(-1)) && !empty($parentAction->id) ) {
                $rec->parentId = $parentAction->id;
            }
        }
        
        expect($rec->containerId && $rec->action);
        
        if (empty($rec->threadId)) {
            expect($rec->threadId = doc_Containers::fetchField($rec->containerId, 'threadId'));
        }

        if (!$rec->mid && !in_array($rec->action, array(self::ACTION_DISPLAY, self::ACTION_RECEIVE, self::ACTION_RETURN, self::ACTION_DOWNLOAD, self::ACTION_CHANGE, self::ACTION_FORWARD, self::ACTION_HISTORY))) {
            $rec->mid = static::generateMid();
        }
        
        // Ако има изпращач
        if ($rec->data->sendedBy) {
            
            // Използваме него за createdBy
            $rec->createdBy = $rec->data->sendedBy;
        }
        
        /*
         * Забележка: plg_Created ще попълни полетата createdBy (ако не са зададени) и
         *             createdOn (кога е станало това)
         */
        
        if (static::save($rec)) {
			// Milen: Това какво прави? Супер неясно глобално предаване на параметри!!!
			if(static::getAction()) {
				static::getAction()->id = $rec->id;
				
			} else {
			    
			    // Ако няма пушнат екшън и има подаден екшън, пушваме го
			    if ($actionData) {
			        $actionData['id'] = $rec->id;
			    
			        static::pushAction($actionData);
			    }
			}
            
            return $rec->mid;
        }
        
        return FALSE;
    }
    
    
    /**
     * Вкарва екшъна
     */
    public static function pushAction($actionData)
    {
    	Mode::push('action', (object)$actionData);
    }
    
    
    /**
     * Изкарва екшъна
     */
    public static function popAction()
    {
        if ($action = static::getAction()) {
            Mode::pop('action');
        }
        
        return $action;
    }

    
    /**
     * Връща екшъна
     */
    public static function getAction($offset = 0)
    {
        return Mode::get('action', $offset);
    }

    
    /**
     * Проверява дали има екшън
     */
    public static function hasAction()
    {
        return Mode::get('action');
    }
    
    /**
     * Случаен уникален идентификатор на документ
     *
     * @return string
     */
    protected static function generateMid()
    {
        do {
            $mid = str::getRand('Aaaaaaaa');
        } while (static::fetch("#mid = '{$mid}'", 'id'));
    
        return $mid;
    }


    /**
     * Извлича записа по подаден $mid
     */
    public static function fetchByMid($mid)
    {
        return static::fetch(array("#mid = '[#1#]'", $mid));
    }


    /**
     * Достъпност на документ от не-идентифицирани посетители
     * 
     * @param int $cid key(mvc=doc_Containers)
     * @param string $mid
     * @return object|boolean запис на модела или FALSE
     * 
     */
    public static function fetchHistoryFor($cid, $mid)
    {
        return static::fetch(array("#mid = '[#1#]' AND #containerId = [#2#]", $mid, $cid));
    }
    
    
    /**
     * Извлича записите по подаден cid
     * 
     * @param integer $cid
     * @param NULL|string|array $action
     * 
     * @return array
     */
    public static function fetchByCid($cid, $action = NULL)
    {
        $resArr = array();
        
        if (!$cid) return $resArr;
        
        $query = self::getQuery();
        $query->where(array("#containerId = [#1#]", $cid));
        
        if (isset($action)) {
            $action = arr::make($action, TRUE);
            $query->orWhereArr('action', $action);
            
        }
        
        while ($rec = $query->fetch()) {
            $resArr[] = $rec;
        }
        
        return $resArr;
    }
    
    
    /**
     * Връща масив с IP-адреси от които е видян/свален документа
     * 
     * @param unknown $cid
     * @param NULL|string|array $action
     * 
     * @return array
     */
    public static function getViewIp($cid, $action = NULL)
    {
        $recsArr = self::fetchByCid($cid, $action);
        
        $viewIpArr = array();
        
        if (!$cid) return $viewIpArr;
        
        foreach ($recsArr as $recObj) {
            if (isset($recObj->data->seenFromIp)) {
                $viewIpArr[$recObj->data->seenFromIp] = $recObj->data->seenFromIp;
            }
            
            if (!empty($recObj->data->open)) {
                foreach ($recObj->data->open as $ip => $d) {
                    $viewIpArr[$ip] = $ip;
                }
            }
            
            if (!empty($recObj->data->download)) {
                foreach ($recObj->data->download as $fh => $dArr) {
                    if (!empty($dArr)) {
                        foreach ($dArr as $ip => $d) {
                            $viewIpArr[$ip] = $ip;
                        }
                    }
                }
            }
        }
        
        return $viewIpArr;
    }
    
    
    /**
     * Връща масив с имейлите до които е изпратен документа
     * 
     * @param integer $cid
     * 
     * @return array
     */
    public static function getSendEmails($cid)
    {
        $resArr = self::fetchByCid($cid, self::ACTION_SEND);
        
        $toEmails = '';
        
        foreach ($resArr as $recObj) {
            if (!isset($recObj->data->to)) continue ;
            
            $toEmails .= ($toEmails) ? ', ' : '';
            $toEmails .= $recObj->data->to;
        }
        
        $resArr = type_Emails::toArray($toEmails);
        
        return $resArr;
    }
    
    
    /**
     * Отбелязва имейла за върнат
     */
    public static function returned($mid, $date = NULL, $ip = NULL)
    {
        if (!($sendRec = static::getActionRecForMid($mid, static::ACTION_SEND))) {
            // Няма изпращане с такъв MID
            return FALSE;
        }
    
        if (!empty($sendRec->data->returnedOn)) {
            // Връщането на писмото вече е било отразено в историята; не правим нищо
            return TRUE;
        }

        if (!isset($date)) {
            $date = dt::now();
        }
        
        expect(is_object($sendRec->data), $sendRec);
    
        $sendRec->data->returnedOn = $date;
        $sendRec->data->returnedIp = $ip;
    
        static::save($sendRec);
        
        $retRec = (object)array(
            'action' => static::ACTION_RETURN,
            'containerId' => $sendRec->containerId,
            'threadId'    => $sendRec->threadId,
            'parentId'    => $sendRec->id
        );
        
        static::save($retRec);
        
		// Съобщение в лога
        $doc = doc_Containers::getDocument($sendRec->containerId);
        $docInst = $doc->getInstance();
        
        // Ако не е циркулярен имейл
        if (!($docInst instanceof blast_Emails)) {
            $msg = tr("Върнато писмо|*: ") . doc_Containers::getDocTitle($sendRec->containerId);
            
            // Нотификация за връщането на писмото до изпращача му
            $linkArr = static::getLinkToSingle($sendRec->containerId, static::ACTION_SEND);
            bgerp_Notifications::add(
                            $msg, // съобщение
                            $linkArr, // URL
                            $sendRec->createdBy, // получател на нотификацията
                            'alert' // Важност (приоритет)
            );
        }
        
		// Съобщение в лога
		$docInst->logInfo("Върнато писмо", $doc->that, DOCLOG_DOCUMENTS_DAYS);
        
        return TRUE;
    }

    
    /**
     * Отбелязва имейла за получен
     */
    public static function received($mid, $date = NULL, $IP = NULL)
    {
        if (!($sendRec = static::getActionRecForMid($mid, static::ACTION_SEND))) {
            // Няма изпращане с такъв MID
            return FALSE;
        }
    
        if (!empty($sendRec->data->receivedOn)) {
            // Връщането на писмото вече е било отразено в историята; не правим нищо
            return TRUE;
        }
    
        if (!isset($date)) {
            $date = dt::now();
        }

        expect(is_object($sendRec->data), $sendRec);
        
        $sendRec->data->receivedOn = $date;
        $sendRec->data->seenFromIp = $IP;
    
        static::save($sendRec);
    
        $rcvRec = (object)array(
            'action' => static::ACTION_RECEIVE,
            'containerId' => $sendRec->containerId,
            'threadId'    => $sendRec->threadId,
            'parentId'    => $sendRec->id
        );
    
        static::save($rcvRec);
        
        // Нотификация за получаване на писмото до адресата.
        /*
         * Засега отпада: @link https://github.com/bgerp/bgerp/issues/353#issuecomment-8531333
         *  
        $msg = tr("Потвърдено получаване|*: ") . doc_Containers::getDocTitle($sendRec->containerId);
        $linkArr = static::getLinkToSingle($sendRec->containerId, static::ACTION_SEND);
        bgerp_Notifications::add(
            $msg, // съобщение
            $linkArr, // URL
            $sendRec->createdBy, // получател на нотификацията
            'alert' // Важност (приоритет)
        );
        */
        
        // Съобщение в лога
        $doc = doc_Containers::getDocument($sendRec->containerId);
		$docInst = $doc->getInstance();
		$docInst->logInfo("Потвърдено получаване", $doc->that, DOCLOG_DOCUMENTS_DAYS);
        
        return TRUE;
    }
    
    
    /**
     * Преди показването на документ по MID
     * Отбелязва документа за видян
     * 
     * @param int $cid key(mvc=doc_Containers)
     * @param string $mid
     * @return stdClass
     */
    public static function opened($cid, $mid)
    {
        expect($parent = static::fetchByMid($mid));
        
        if ($parent->containerId != $cid) {
            // Заявен е документ, който не е собственик на зададения MID. В този случай заявения 
            // документ трябва да е свързан с (цитиран от) документа собственик на MID.
            $requestedDoc = doc_Containers::getDocument($cid);
            $midDoc       = doc_Containers::getDocument($parent->containerId);
            
            // Вземаме от парент записа id то на изпращача
            $fParent = $parent;
            
            // Ако е изпратен
            if ($fParent->action == static::ACTION_SEND) {
                $sendedAction = $fParent;
            }
            while ($fParent->parentId) {
                $fParent = static::fetch($fParent->parentId);
                
                // Ако е изпратен
                if (!$sendedAction && $fParent->action == static::ACTION_SEND) {
                    $sendedAction = $fParent;
                }
            }
            if ($fParent->data->sendedBy > 0) {
                $sendedBy = $fParent->data->sendedBy;
            }
            
            // Ако е изпратен или е системата - за бласт
            if ($sendedAction && (!$sendedBy || $sendedBy <= 0)) {
                
                // Използваме активатора на документа
                $sendContainerRec = doc_Containers::fetch($sendedAction->containerId);
                if ($sendContainerRec->activatedBy && $sendContainerRec->activatedBy > 0) {
                    $sendedBy = $sendContainerRec->activatedBy;
                }
            }
            
            $linkedDocs = $midDoc->getLinkedDocuments($sendedBy, $fParent->data);
            
            // свързан ли е?
            expect(isset($linkedDocs[$requestedDoc->getHandle()]));
            
            // До тук се стига само ако заявения е свързан.
            
            $action = static::fetch(array("#containerId = [#1#] AND #parentId = {$parent->id}", $cid));
            
            if (!$action || $action->action != self::ACTION_OPEN) {
                // Ако нямаме отбелязано виждане на заявения документ - създаваме нов запис
                $action = (object)array(
                    'action'      => self::ACTION_OPEN,
                    'containerId' => $cid,
                    'parentId'    => $parent->id,
                    'data'        => new stdClass(),
                );
            }
        } else {
            $action = $parent;
            $parent = NULL;
            
            if ($action->parentId) {
                // Ако текущото виждане има родител - подсигуряваме, че и родителя е маркиран 
                // като видян
                $parent = static::fetch($action->parentId);
            }
        }
        
        expect($action);
        
        return static::markAsOpened($action, $parent);
    }
    
    
    /**
     * Помощен метод - маркира запис като видян и го добавя в стека с действията.
     * 
     * Ако има зададен запис за родителско действие ($parent) и той се маркира като видян.
     * Стека с действията се пълни в паметта; записа му в БД става в края на заявката
     * @see doclog_Documents::on_Shutdown()
     * 
     * @param stdClass $action запис на този модел
     */
    protected static function markAsOpened($action, $parent)
    {
        if ($parent) {
            // Ако е зададено действие-родител - маркираме го като видяно.
            static::markAsOpened($parent, NULL);
        }
        
        $openAction = self::ACTION_OPEN;
        
        $ip = core_Users::getRealIpAddr();
        
        if (!isset($action->data->{$openAction}[$ip])) {
            $action->data->{$openAction}[$ip] = array(
                'on' => dt::now(true),
                'ip' => $ip
            );
        }
        
        static::pushAction($action);
        
        // Съобщение в лога
        $doc = doc_Containers::getDocument($action->containerId);
		$docInst = $doc->getInstance();
		$docInst->logInfo("Видян документ", $doc->that, DOCLOG_DOCUMENTS_DAYS);
        
        return $action;
    }
    
    
    /**
     * Отбелязва, когато препращаме имейл
     * 
     * @param object $eRec - Записа
     */
    public static function forward($eRec)
    {
        // От кой документ се създава записа
        $originId = $eRec->originId;
        
        // Ако няма originId
        if (!$originId) return ;
        
        // Екшъна за проманя
        $forwardAction = static::ACTION_FORWARD;
        
        // Вземаме записите за контейнера на документа, от който се създава имейла
        $cRec = doc_Containers::fetch($originId);
        
        // id на контейнера, който ще запишем в модела
        $containerId = $originId;
        
        // id на нишката, който ще запишем в модела
        $threadId = $cRec->threadId;
        
        // Вземаме записа, ако има такъв
        $rec = static::fetch("#containerId = '{$containerId}' AND #action = '{$forwardAction}'");
        
        // Ако няма запис
        if (!$rec) {
            
            // Създаваме обект с данни
            $rec = (object)array(
                'action' => $forwardAction,
                'containerId' => $containerId,
                'threadId' => $threadId,
                'data' => new stdClass(),
            );    
        }
        
        // Добавяме данните
        $rec->data->{$forwardAction}[] = array(
            'on' => dt::now(true),
            'from' => core_Users::getCurrent(),
            'containerId'  => $eRec->containerId
        );
        
        // Пушваме съответното действие
        static::pushAction($rec);

        // Съобщение в лога
        $doc = doc_Containers::getDocument($rec->containerId);
		$docInst = $doc->getInstance();
		$docInst->logInfo("Препратен имейл", $doc->that, DOCLOG_DOCUMENTS_DAYS);
		
        return $rec;
    }
    
    
    /**
     * Отбелязва като променен някой документ
     * 
     * @param object $logRecArr - Записа
     */
    public static function changed($logRecArr)
    {
        // Екшъна за проманя
        $changeAction = static::ACTION_CHANGE;
        
        // Обхождаме масива с логовете
        foreach ((array)$logRecArr as $logRec) {
            
            // Ако има docId и docClass
            if ($logRec->docId && $logRec->docClass) {
                
                // Инстанция на класа
                $docClass = cls::get($logRec->docClass);
                
                // Записите за съответния клас
                $dRec = $docClass->fetch($logRec->docId);
                
                // id на контейнера
                $containerId = $dRec->containerId;
                
                // id на треда
                $threadId = $dRec->threadId;
                
                // Ако няма запис
                if (!$rec) {
                    
                    // Вземаме записа, ако има такъв
                    $rec = static::fetch("#containerId = '{$containerId}' AND #action = '{$changeAction}'");
                    
                    // Ако няма запис
                    if (!$rec) {
                        
                        // Създаваме обект с данни
                        $rec = (object)array(
                            'action' => $changeAction,
                            'containerId' => $containerId,
                            'threadId' => $threadId,
                            'data' => new stdClass(),
                        );    
                    }
                }
                
                // Добавяме данните
                $rec->data->{$changeAction}[$logRec->id] = array(
                    'docId' => $logRec->docId,
                    'docClass' => $logRec->docClass
                );
            }
            
            // Пушваме съответното действие
            static::pushAction($rec);
        }
        
        return $rec;
    }
    
    
    /**
     * Маркира файла, че е свален
     * 
     * @param string $mid
     * @param fileHnd $fh - Манипулатор на файла, който се сваля
     * 
     * @return object|boolean $rec
     */
    public static function downloaded($mid, $fh)
    {
        $downloadAction = static::ACTION_DOWNLOAD;
        
        // Очакваме да има запис, в който е цитиран файла
        expect($sendRec = static::getActionRecForMid($mid, FALSE));
        expect(is_object($sendRec->data));
        
        // Вземаме записа, ако има такъв
        $rec = static::fetch("#containerId = '{$sendRec->containerId}' AND #action = '{$downloadAction}'");
        
        // IP' то на потребителя
        $ip = core_Users::getRealIpAddr();
        
        // id' то на текущия потребител
        $currUser = core_Users::getCurrent('id');
        
        // 
        $actionToken = ($currUser) ? $currUser : $ip;

        // Ако съответния потребител е свалял файла
        if (!empty($rec->data->{$downloadAction}[$fh][$actionToken])) {
            
            return TRUE;    
        }
        
        // Датата и часа
        $date = dt::now(true);
        
        // Ако няма запис
        if (!$rec) {
            
            // Създаваме обект с данни
            $rec = (object)array(
                'action' => $downloadAction,
                'containerId' => $sendRec->containerId,
                'threadId'    => $sendRec->threadId,
                'data' => new stdClass(),
            );    
        }
        
        // Добавяме данните
        $rec->data->{$downloadAction}[$fh][$actionToken] = array(
            'ip' => $ip,
            'seenOnTime' => $date,
        );
        
        // Ако има логнат потребител
        if ($currUser) {
            
            // Добавяме id' то му
            $rec->data->{$downloadAction}[$fh][$actionToken]['seenFrom'] = $currUser; 
        }

        // Пушваме съответното действие
        static::pushAction($rec);
        
        // Добавяме запис в лога
        $msg = tr("Свален файл|*: ") . fileman_Files::getLink($fh);
        
        // Съобщение в лога
        $doc = doc_Containers::getDocument($rec->containerId);
		$docInst = $doc->getInstance();
		$docInst->logInfo("Свален файл", $doc->that, DOCLOG_DOCUMENTS_DAYS);
        
        return $rec;
    }
    
        
    /**
     * Изпълнява се преди всеки запис в модела
     * 
     * @param unknown_type $mvc
     * @param unknown_type $id
     * @param unknown_type $rec
     */
    static function on_BeforeSave($mvc, &$id, $rec)
    {
        if (empty($rec->data)) {
            $rec->dataBlob = NULL;
        } else {
            if (is_array($rec->data)) {
                $rec->data = (object)$rec->data;
            }
        
            $rec->dataBlob = $rec->data;
        }
    }
    
    
    /**
     * Изпълнява се след всеки запис в модела
     *
     * @param doclog_Documents $mvc
     * @param int $id key(mvc=doclog_Documents)
     * @param stdClass $rec запис на модела, който е бил записан в БД
     */
    static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
        if ((!$rec->threadId) && ($rec->containerId)) {
            $rec->threadId = doc_Containers::fetchField($rec->containerId, 'threadId');
        }
        expect($rec->threadId);
        
        // Изчистваме кешираната история на треда, понеже тя току-що е била променена.
        $mvc::removeHistoryFromCache($rec->threadId);
    }
    
    
    /**
     * Подготовка на историята на цяла нишка
     *
     * Данните с историята на треда се кешират, така че многократно извикване с един и същ
     * параметър няма негативен ефект върху производителността.
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return array ключ е contanerId, стойност - историята на този контейнер
     */
    protected static function prepareThreadHistory($threadId)
    {
        if (!isset(static::$histories[$threadId])) {
            $cacheKey = static::getHistoryCacheKey($threadId);
        
            if (($history = core_Cache::get(static::CACHE_TYPE, $cacheKey)) === FALSE) {
                // Историята на този тред я няма в кеша - подготвяме я и я записваме в кеша
                $history = static::buildThreadHistory($threadId);
                core_Cache::set(static::CACHE_TYPE, $cacheKey, $history, 2 * 24 * 60);
            }       
            
            static::$histories[$threadId] = $history;
        }
        
        return static::$histories[$threadId];
    }
    
    
    /**
     * Изтрива от кеша записана преди история на нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     */
    static function removeHistoryFromCache($threadId)
    {
        $cacheKey = static::getHistoryCacheKey($threadId);
        
        core_Cache::remove(static::CACHE_TYPE, $cacheKey);
    }
    
    
    /**
     * Ключ, под който се записва историята на нишка в кеша
     *
     * @see core_Cache
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return string
     */
    protected static function getHistoryCacheKey($threadId)
    {
        return $threadId;
    }
    
    
    /**
     * Преизчислява историята на нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return array масив с ключ $containerId (на контейнерите от $threadId, за които има запис
     *                  в историята) и стойности - обекти (stdClass) със следната структура:
     *
     *  ->summary => array(
     *         [ACTION1] => брой,
     *         [ACTION2] => брой,
     *         ...
     *     )
     *         
     *  ->containerId - контейнера, чиято история се съдържа в обекта (за удобство)
     */
    protected static function buildThreadHistory($threadId)
    {
        $query = static::getQuery();
        $query->where("#threadId = {$threadId}");
        $query->orderBy('#createdOn');
        
        $open = self::ACTION_OPEN;
        $download = self::ACTION_DOWNLOAD;
        $change = self::ACTION_CHANGE;
        $forward = self::ACTION_FORWARD;
        $used = self::ACTION_USED;
        
        $data = array();   // Масив с историите на контейнерите в нишката
        $changesArr = array();
        while ($rec = $query->fetch()) {
            if (!isset($data[$rec->containerId])) {
                $data[$rec->containerId] = new stdClass();
            }
            if (($rec->action != $open) && ($rec->action != $download) && ($rec->action != $change) && ($rec->action != $forward) && ($rec->action != $used)) {
                $data[$rec->containerId]->summary[$rec->action] += 1;
            }
            
            // Ако екшъна е change
            if ($rec->action == $change) {
                
                // Обхождаме всички промени
                foreach ((array)$rec->data->{$change} as $changeDataArr) {
                    
                    // За да не обикаляме едни и същи данни повече от един път
                    $checkedChangesStr = $changeDataArr['docClass'] . '_' . $changeDataArr['docId'];
                    
                    // Ако ня сме търсили за този клас и документ
                    if (!$changesArr[$checkedChangesStr]) {
                        
                        // Вземаме броя на промените
                        $data[$rec->containerId]->summary[$change] += change_Log::getCountOfChange($changeDataArr['docClass'], $changeDataArr['docId']);
                        
                        // Отбелязваме в масива, за да го прескочим
                        $changesArr[$checkedChangesStr] = $checkedChangesStr;
                    }
                }
            }
            
            $data[$rec->containerId]->summary[$open] += count($rec->data->{$open});
            $data[$rec->containerId]->summary[$download] += static::getCountOfDownloads($rec->data->{$download});
            $data[$rec->containerId]->summary[$forward] += count($rec->data->{$forward});
            $data[$rec->containerId]->containerId = $rec->containerId;
        }
        
        // Показваме използванията
        $cArr = array();
        $contQuery = doc_Containers::getQuery();
        $contQuery->where("#threadId = {$threadId}");
        while ($cRec = $contQuery->fetch()) {
            if (!isset($data[$cRec->id])) {
                $data[$cRec->id] = new stdClass();
                $data[$cRec->id]->containerId = $cRec->id;
            }
            
            $cArr[$cRec->id] = $cRec->id;
        }
        
        $allUsedCount = doclog_Used::getAllUsedCount($cArr);
        foreach ($allUsedCount as $cId => $cnt) {
            $data[$cId]->summary[$used] = $cnt;
        }
        
        return $data;
    }
    
    
    /**
     * Връща броя на свалянията
     * 
     * @param array $data - Масив с данни, в които ще се търси
     * 
     * @return integer $downloadCount - Броя на свалянията на файловете
     */
    protected static function getCountOfDownloads($data)
    {
        // Ако е масив
        if (is_array($data)) {
            
            // Обхождаме масива
            foreach ($data as $downloadRec) {
                
                // Добавяме броя на свалянията към променливата
                $downloadCount += count($downloadRec);
            }  
        }

        return $downloadCount;
    }
    
    
    /**
     * Подготвя историята на един контейнер
     *
     * @param int $containerId key(mvc=doc_Containers)
     * @param int $threadId key(mvc=doc_Threads)
     */
    protected static function prepareContainerHistory($containerId, $threadId)
    {
        $threadHistory = static::prepareThreadHistory($threadId);
        
        return $threadHistory[$containerId];
    }

    
    /**
     * Рендира историята на действията
     */
    public static function renderSummary($data)
    {
    	static $wordings = NULL;
        static $wordingsTitle = NULL;
        
        static $actionToTab = NULL;
        
        if (empty($data->summary)) {
            return '';
        }
        
        if (!isset($wordings) || !isset($wordings)) {
            
            $wordings = array(
                static::ACTION_SEND    => array('изпращане', 'изпращания'),
                static::ACTION_RECEIVE => array('получаване', 'получавания'),
                static::ACTION_RETURN  => array('връщане', 'връщания'),
                static::ACTION_PRINT   => array('отпечатване', 'отпечатвания'),
                static::ACTION_OPEN   => array('виждане', 'виждания'),
                static::ACTION_DOWNLOAD => array('сваляне', 'сваляния'),
                static::ACTION_CHANGE => array('промяна', 'промени'),
                static::ACTION_FORWARD => array('препратен', 'препратени'),
                static::ACTION_USED => array('използване', 'използвания'),
                static::ACTION_FAX => array('факс', 'факс'),
                static::ACTION_PDF => array('pdf', 'pdf'),
                static::ACTION_EXPORT => array('експорт', 'експорта'),
            );
            
            $wordingsTitle = $wordings;
            
            if (Mode::is('screenMode', 'narrow')) {
                
                $wordings = array(
                    static::ACTION_SEND    => array('изпр', 'изпр'),
                    static::ACTION_RECEIVE => array('пол', 'пол'),
                    static::ACTION_RETURN  => array('вр', 'вр'),
                    static::ACTION_PRINT   => array('отп', 'отп'),
                    static::ACTION_OPEN   => array('виж', 'виж'),
                    static::ACTION_DOWNLOAD => array('св', 'св'),
                    static::ACTION_CHANGE => array('пром', 'пром'),
                    static::ACTION_FORWARD => array('преп', 'преп'),
                    static::ACTION_USED => array('изп', 'изп'),
                    static::ACTION_FAX => array('факс', 'факс'),
                    static::ACTION_PDF => array('pdf', 'pdf'),
                    static::ACTION_EXPORT => array('експ', 'експ'),
                );
            }
        }
        
        if (!isset($actionToTab)) {
            $actionToTab = array(
                static::ACTION_SEND    => static::ACTION_SEND,
                static::ACTION_FAX     => static::ACTION_SEND,
                static::ACTION_RECEIVE => static::ACTION_SEND,
                static::ACTION_RETURN  => static::ACTION_SEND,
                static::ACTION_PRINT   => static::ACTION_PRINT,
                static::ACTION_PDF     => static::ACTION_PRINT,
                static::ACTION_EXPORT     => static::ACTION_PRINT,
                static::ACTION_OPEN    => static::ACTION_OPEN,
                static::ACTION_DOWNLOAD    => static::ACTION_DOWNLOAD,
                static::ACTION_CHANGE    => static::ACTION_CHANGE,
                static::ACTION_FORWARD    => static::ACTION_FORWARD,
                static::ACTION_USED => static::ACTION_USED,
            );
        }
        
        $html = '';
        
        foreach ($data->summary as $action=>$count) {
            if ($count == 0) {
                continue;
            }
            $actionVerbal = $action;
            $actionTitle = $action;
            
            if (isset($wordings[$action])) {
                $actionVerbal = $wordings[$action][intval($count > 1)];
            }
            
            if (isset($wordingsTitle[$action])) {
                $actionTitle = $wordingsTitle[$action][intval($count > 1)];
            }
            
            $actionVerbal = tr($actionVerbal);
            
            $linkArr = array();
            try {
                if ($data->containerId) {
                    $document = doc_Containers::getDocument($data->containerId);
                }
                if($document->haveRightFor('single') && !core_Users::haveRole('partner')){
                    $linkArr = static::getLinkToSingle($data->containerId, $actionToTab[$action]);
                }
            } catch (core_exception_Expect $e) {
                reportException($e);
            }
            
	        $link = ht::createLink("<b>{$count}</b><span>{$actionVerbal}</span>", $linkArr, FALSE, array('title' => $actionTitle));
            $html .= "<li class=\"action {$action}\">{$link}</li>";
        }
        
        return $html;
    }
    
    
    /**
     * Връща линк към сингъла на документа
     * 
     * @param unknown_type $cid
     * @param unknown_type $action
     */
    static function getLinkToSingle($cid, $action)
    {
        $document = doc_Containers::getDocument($cid);
        $detailTab = ucfirst(strtolower($action));
        
    	$link = array(
    			$document->className,
    			'single',
    			$document->that,
    			'Cid' => $cid,
    			'Tab' => $detailTab,
    	);
    	
    	if($topTab = Request::get('TabTop')){
    		$link['TabTop'] = $topTab;
    	}
        
        return $link;
    }
    
    
    /**
     * Шаблон (ET) съдържащ обобщената историята на документа в този контейнер.
     *
     * @param int $containerId key(mvc=doc_Containers)
     * @param int $threadId key(mvc=doc_Thread) нишката,в която е контейнера
     * 
     * @return core_ET
     */
    public static function getSummary($containerId, $threadId)
    {
        $data = static::prepareContainerHistory($containerId, $threadId);
        
        $html = static::renderSummary($data);
        
        $html .= doc_ExpensesSummary::getSummary($containerId);
        
        if(strlen($html) != 0){
        	$html = "<ul class=\"history summary\">{$html}</ul>";
        }
        
        return $html;
    }

    
    /**
     * Връща форматирано виждането на документа
     * 
     * @param unknown_type $rec
     * @param unknown_type $deep
     */
    protected static function formatViewReason($rec, $deep = TRUE)
    {
        switch ($rec->action) {
            case static::ACTION_SEND:
                $row = (object)array('toEmail' => $rec->data->to);
                $row = static::recToVerbal($row, array_keys(get_object_vars($row)));
                return tr('Имейл до|* ') . $row->toEmail . ' / ' . static::getVerbal($rec, 'createdOn');
            case static::ACTION_PRINT:
                return tr('Отпечатване|* / ') . static::getVerbal($rec, 'createdOn');
            case static::ACTION_EXPORT:
                return tr('Експортиране|* / ') . static::getVerbal($rec, 'createdOn');
            case static::ACTION_OPEN:
                if ($deep && !empty($rec->parentId)) {
                    $parentRec = static::fetch($rec->parentId);
                    $res = static::formatViewReason($parentRec, FALSE);
                } else {
                    $linkArr = static::getLinkToSingle($rec->containerId, static::ACTION_OPEN);
                    $doc = doc_Containers::getDocument($rec->containerId);
                    $docRow = $doc->getDocumentRow();
                    $res = tr('Показване на|* ') . ht::createLink($docRow->title, $linkArr) . ' / ' . static::getVerbal($rec, 'createdOn');
                }
                return $res;
            default:
                return strtoupper($rec->action) . ' / ' . static::getVerbal($rec, 'createdOn');
        }
    }
    
    
    
    /**
     * Помощен метод - рендира историята на разглежданията на документ
     * 
     * @param stdClass $rec
     * @param string $date
     * @return string HTML 
     */
    private static function renderOpenActions($rec, $date = NULL, $brief = TRUE)
    {
        $openActionName = static::ACTION_OPEN;

        $html = '';
        
        if ($rec->data->receivedOn && $rec->data->seenFromIp) {
            $firstOpen = array();
            $firstOpen['ip'] = $rec->data->seenFromIp;
            $firstOpen['on'] = $rec->data->receivedOn;
        }
        
        if (!empty($rec->data->{$openActionName})) {
            $firstOpen = reset($rec->data->{$openActionName});
        }
        
        $_r = $rec->receivedOn;
        
        if (!empty($firstOpen) && (empty($date) || $firstOpen['on'] < $date)) {
            $rec->receivedOn = $firstOpen['on'];
        } else {
            $rec->receivedOn = $date;
        }
        
        $html .= static::getVerbal($rec, 'receivedOn');
        $linkArr = static::getLinkToSingle($rec->containerId, static::ACTION_OPEN);
        
        if (!empty($firstOpen)) {
            $html .= ' ' . type_Ip::decorateIp($firstOpen['ip'], $firstOpen['on'], TRUE);
            $cnt = count($rec->data->{$openActionName});
            if ($cnt) {
                $html .= ht::createLink(
                    $cnt,
                    $linkArr,
                    FALSE,
                    array(
                        'class' => 'badge',
                    )
                );
            }
        }
        
        $rec->receivedOn = $_r;
        
        return $html;
    }
    

    /**
     * Връща cid' а на документа от URL.
     * 
     * Проверява URL' то дали е от нашата система.
     * Проверява дали cid' а и mid'а си съвпадат.
     * Ако открие записа на документа проверява дали има родител.
     * Ако има родител връща cid'а на родителя. 
     * 
     * @param URL $url - URL от системата, в който ще се търси
     * 
     * @return integer|NULL $cid - Container id на документа
     */
    static function getDocumentCidFromURL($url)
    {
        // Проверяваме дали URL' то е от нашата система
        if (!core_Url::isLocal($url, $rest)) return ;
        
        $urlArr = type_Richtext::parseInternalUrl($rest);
        
        $cid = $urlArr['id'];
        $mid = $urlArr['m'];
        
        if (!$cid || !$mid) return ;
        
        // Вземам записа за съответния документ в лога
        $rec = doclog_Documents::fetchHistoryFor($cid, $mid);
        
        // Ако няма запис - mid' а не е правилен
        if (!$rec) return ;
        
        // Ако записа има parentId
        if ($rec->parentId) {
            
            // Задаваме cid'a да е containerId' то на родителския документ
            $cid = doclog_Documents::fetchField($rec->parentId, 'containerId');
        } else {
            
            $cid = $rec->containerId;
        }
        
        return $cid;
    }
    
    
    /**
     * Връща записа за съответния екшън и мид
     * 
     * @param string $mid - Mid' а на действието
     * @param string $action - Действието, което искаме да търсим
     * 
     * @return object|FALSE - Обект с данни
     */
    static function getActionRecForMid($mid, $action=NULL)
    {
        // Ако не сме задали да не се проверява 
        if ($action === FALSE) {
            
            $query = self::getQuery();
            $query->where(array("#mid = '[#1#]'", $mid));
            
            // Трябва да има един такъв екшън
            while ($rec = $query->fetch()) {
                if (in_array($rec->action, array(self::ACTION_SEND, self::ACTION_OPEN, self::ACTION_PRINT, self::ACTION_FAX, self::ACTION_PDF, self::ACTION_EXPORT, self::ACTION_USED))) {
                    
                    return $rec;
                }
            }
            
            return FALSE;
        } else {
            
            // Акшъна по подразбиране да е send
            setIfNot($action, static::ACTION_SEND);
    
            // Вземаме записа, ако има такъв
            $rec = static::fetch(array("#mid = '[#1#]' AND #action = '{$action}'", $mid));
        }
        
        return $rec;
    }
    
    
    /**
     * При приключване на изпълнените на скрипта
     */
    public static function on_Shutdown($mvc)
    {
        // Записва в БД всички действия от стека
        static::flushActions();
    }
    
    
    /**
     * Записва в БД всички действия от стека
     */
    public static function flushActions()
    {
        $count = 0;
        
        while ($action = static::popAction()) {
            
            if (!$action->threadId && $action->containerId) {
                $action->threadId = doc_Containers::fetchField($action->containerId, 'threadId');
            }
            
            static::save($action);
            $count++;
        }

        if($count > 0) {
            self::logInfo("Записани {$count} действия", NULL, DOCLOG_DOCUMENTS_DAYS);
        }
    }
    
    
    /**
     * Проверява дали е изпратен имейл от този контейнер към имейлите
     * 
     * @param integer $containerId - id на контейнера
     * @param integer|NULL $resendingSecs - Секунди, преди които ще се счита за изпратено
     * @param string|FALSE $emailTo - Имейли в to
     * @param string|NULL $emailCc - Имейли в cc
     * 
     * @return boolean
     */
    static function isSended($containerId, $resendingSecs=NULL, $emailTo=FALSE, $emailCc=NULL)
    {
        // Ако не е подадено $containerId
        if (!$containerId) return FALSE;
        
        // Екшъна за изпращане
        $sendAction = static::ACTION_SEND;
        
        // Извличаме всички изпратени имейли от този контейнер
        $query = static::getQuery();
        $query->where("#containerId = '{$containerId}'");
        $query->where("#action = '{$sendAction}'");
        
        // Ако са зададени секунди
        if ($resendingSecs) {
            
            // Премахваме секундите
            $resendingTime = dt::subtractSecs($resendingSecs);
            
            // изпратени преди датата за повторно изпращане
            $query->where("#createdOn < '{$resendingTime}'");
        }
        
        // Обхождаме всички записи
        while ($rec = $query->fetch()) {
            
            // Ако имейлите до са зададени
            if ($emailTo !== FALSE) {
                
                // Ако има CC имейли
                if ($emailCc) {
                    
                    // Проверяваме to и cc дали съвпадат
                    if (($rec->data->cc == $emailCc) && ($rec->data->to == $emailTo)) return TRUE;
                } else {
                    
                    // Ако няма CC, проверяваме само to
                    if ($rec->data->to == $emailTo) return TRUE;    
                }
            } elseif ($rec) {
                
                // Ако няма to имйел, но има изпращане
                return TRUE;
            }    
        }

        return FALSE;
    }
    
    
    /**
     * Подготовка на таба за използвания
     */
	function prepareUsed($data)
    {
    	// Ако сме в режим принтиране
        // Да не се изпълнява
        if (Request::get('Printing')) return ;
        
        // Вземаме cid от URL' то
        $cid = Request::get('Cid', 'int');
        
        // Ако не листваме данните за съответния контейнер
        if ($data->masterData->rec->containerId != $cid) return ;
        
        // Името на таба
        $data->TabCaption = tr('Използване');
        
        // Създаваме странициране
        $data->pager = cls::get('core_Pager', array('itemsPerPage' => $this->itemsPerPage, 'pageVar' => 'P_doclog_Documents'));
        
        // URL' то където ще сочат
        $data->pager->url = toUrl(static::getLinkToSingle($cid, static::ACTION_USED));
        
        $data->rows = doclog_Used::prepareRecsFor($cid, $data->pager);
        
        if (empty($data->rows)) {
        
            // Бутона да не е линк
            $data->disabled = TRUE;
        }
    }
    
    
    /**
     * Рендиране на таба за използвания
     */
	function renderUsed($data)
    {
    	// Ако няма записи
        if (!$data->rows) return ;
        
        // Вземаме шаблона за детайлите с попълнена титла
        $tpl = static::getLogDetailTpl();
        
        // Инстанция на класа
        $inst = cls::get('core_TableView');
        
        // Вземаме таблицата с попълнени данни
        $sendTpl = $inst->get($data->rows, 'createdOn=Дата, containerId=Документ, createdBy=От');
        
        // Заместваме в главния шаблон за детайлите
        $tpl->append($sendTpl, 'content');
        
        // Добавяме странициране
        $tpl->append($data->pager->getHtml());
        
        return $tpl;
    }
    
    
    /**
     * Ф-я за изтриване на използване от лога
     * @see static::used
     */
    public static function cancelUsed(core_Master $usedClass, $usedId, core_Manager $docClass, $docId)
    {
    	return static::used($usedClass, $usedId, $docClass, $docId, TRUE);
    }
    
    
    /**
     * Маркира даден документ като използван в друг
     * @param core_Master $usedClass - Инстанция на класа,
     *  който ще се отбелязва
     * @param int $usedId - ид на изпозлвания документ
     * @param core_Manager $docClass - инстанция на класа,
     * в който е вкаран
     * @param int $docId - ид на документа в който участва другия
     * @param boolean $isRejected - Дали документа се оттегля или не
     */
    public static function used(core_Master $usedClass, $usedId, core_Manager $docClass, $docId, $isRejected = FALSE)
    {
    	$action = static::ACTION_USED;
    	$uRec = $usedClass->fetch($usedId);
    	$docRow = $docClass->getDocumentRow($docId);
    	
    	$inClass = (object)array(
    				'class' => $docClass->className, 
    				'id' => $docId,
    				'icon' => sbf($docClass->singleIcon),
    				'title' => $docRow->title,
    				'author' => $docRow->author,
    				'lastUsedOn' => dt::now(),);
    	
    	$query = static::getQuery();
    	$query->where("#containerId = '{$uRec->containerId}' AND #action = '{$action}'");
    	$allRecs = $query->fetchAll();
    	if(!count($allRecs)){
    			
    			// Създаваме обект с данни
    			$allRecs[] = (object)array(
    					'action' => $action,
    					'containerId' => $uRec->containerId,
    					'threadId' => $uRec->threadId,
    					'data' => new stdClass(),
    			);
    	}
    	
    	foreach ($allRecs as $rec){
    		
    		if($isRejected){
    			 
    			// При оттегляне се изтрива записа от лога
    			static::removeUsed($rec, $inClass);
    			$msg = "Изтрито използване на документ";
    		} else {
    			 
    			// При активация/възстановяване се вкарва запис в лога
    			$rec->data->{$action}[] = $inClass;
    			$msg = "Използване";
    		}
    		
    		// Пушваме съответното действие
    		static::pushAction($rec);
    		
    		// Съобщение в лога
    		$doc = doc_Containers::getDocument($rec->containerId);
    		$docInst = $doc->getInstance();
    		$docInst->logRead($msg, $doc->that, DOCLOG_DOCUMENTS_DAYS);
    	}
    	
        return $rec;
    }
    
    
    /**
     * Изтрива използването на даден документ от лога
     * @param stdClass $rec - запис от лога
     * @param stdClass $inClass - запис на конкретно използване
     */
    private static function removeUsed($rec, $inClass)
    {
    	if(count($rec->data->{static::ACTION_USED})){
	    	foreach ($rec->data->{static::ACTION_USED} as $i => $lRec){
	    		$clone = clone $lRec;
	    		$cloneComp = clone($inClass);
	    		unset($clone->lastUsedOn, $cloneComp->lastUsedOn);
	    		
	    		if($clone == $cloneComp){
	    			unset($rec->data->{static::ACTION_USED}[$i]);
	    		}
	    	}
    	}
    }
}
