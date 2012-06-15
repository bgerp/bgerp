<?php 


/**
 * История от събития, свързани с документите
 *
 * Събитията са изпращане по имейл, получаване, връщане, печат, разглеждане
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class log_Documents extends core_Manager
{
    
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Лог на документи";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, doc';
    
    
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
    var $canView = 'admin, doc';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin, doc';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'no_one';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'log_Wrapper,  plg_Created';
    
    
    /**
     * @todo Чака за документация...
     */
    var $listFields = 'userNdate=Кой/кога, action=Какво, containerId=Кое, data=Обстоятелства';
    
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
    
    const ACTION_SEND    = 'send';
    const ACTION_RETURN  = '_returned';
    const ACTION_RECEIVE = '_received';
    const ACTION_OPEN    = 'open';
    const ACTION_PRINT   = 'print';
    const ACTION_DISPLAY = 'display';
    const ACTION_FAX     = 'fax';
    const ACTION_PDF     = 'pdf';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $actionsEnum = array(
            self::ACTION_SEND    . '=изпращане',
            self::ACTION_RETURN  . '=връщане',
            self::ACTION_RECEIVE . '=получаване',
            self::ACTION_OPEN    . '=показване',
            self::ACTION_PRINT   . '=отпечатване',
            self::ACTION_DISPLAY . '=разглеждане',
            self::ACTION_FAX     . '=факс',
            self::ACTION_PDF     . '=PDF',
        );
        
        // Тип на събитието
        $this->FLD("action", 'enum(' . implode(',', $actionsEnum) . ')', "caption=Действие");
        
        // Нишка на документа, за който се отнася събитието
        $this->FLD('threadId', 'key(mvc=doc_Threads)', 'caption=Нишка');
        
        // Документ, за който се отнася събитието
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'caption=Контейнер');
        
        // MID на документа
        $this->FLD('mid', 'varchar', 'input=none,caption=Ключ,column=none');
        
        $this->FLD('parentId', 'key(mvc=log_Documents, select=action)', 'input=none,caption=Основание');
        
        // Допълнителни обстоятелства, в зависимост от събитието (в PHP serialize() формат)
        $this->FLD("data", "blob", 'caption=Обстоятелства,column=none');
        
        $this->setDbIndex('containerId');
        $this->setDbUnique('mid');
    }
    
    
    /**
     * Добавя запис в историята на документ
     * 
     * @param string $action
     * @param int    $cid key(mvc=doc_Containers)
     * @param int    $parentId key(mvc=log_Documents)
     * @param mixed  $details
     * @return string|boolean MID на новосъздадения запис или FALSE при неуспех
     */
    public static function add($action, $cid, $parentId = NULL, $details = NULL)
    {
        bp('deprecated');
        $tid = doc_Containers::fetchField($cid, 'threadId');
        
        // Валидация на $parentId - трябва да е ключ на запис в историята или NULL
        expect(!isset($parentId) || static::fetch($parentId));
        

        // Създаваме нов запис 
        $rec = new stdClass();
        
        $rec->action      = $action;
        $rec->containerId = $cid;
        $rec->threadId    = $tid;
        $rec->parentId    = $parentId;
        $rec->details     = serialize($details);
        
        if (!in_array($action, array(self::ACTION_DISPLAY, self::ACTION_RECEIVE, self::ACTION_RETURN))) {
            $rec->mid = static::generateMid();
        }
        
        /*
         * Забележка: plg_Created ще попълни полетата createdBy (кой е отпечатал документа) и
         *             createdOn (кога е станало това)
         */
        
        if (static::save($rec)) {
            return $rec->mid;
        }
        
        return FALSE;
        
    }
    
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

        if (!in_array($rec->action, array(self::ACTION_DISPLAY, self::ACTION_RECEIVE, self::ACTION_RETURN))) {
            $rec->mid = static::generateMid();
        }
        
        
        $rec->data     = serialize($rec->data);
        
        /*
         * Забележка: plg_Created ще попълни полетата createdBy (кой е отпечатал документа) и
         *             createdOn (кога е станало това)
         */
        
        if (static::save($rec)) {
            static::getAction()->id = $rec->id;
            
            return $rec->mid;
        }
        
        return FALSE;
    }
    
    
    public static function pushAction($actionData)
    {
        Mode::push('action', (object)$actionData);
    }
    
    
    public static function popAction()
    {
        return Mode::pop('action');
    }

    
    public static function getAction($offset = 0)
    {
        return Mode::get('action', $offset);
    }

    
    public static function hasAction()
    {
        return Mode::get('action');
    }
    
    
    /**
     * Достъпност на документ от не-идентифицирани посетители
     * 
     * @param int $cid key(mvc=doc_Containers)
     * @param string $mid
     * @return object|boolean запис на модела или FALSE
     */
    public static function fetchHistoryFor($cid, $mid)
    {
        $rec = static::fetch(array("#mid = '[#1#]'", $mid));
        
        if (!$rec) {
            $rec = FALSE;
        }
        
        if ($rec && $rec->containerId != $cid) {
            $doc = doc_Containers::getDocument($cid);
            
            //$linkedDocs = $doc->getLinkedDocuments($rec->containerId);
            
            if (!isset($linkedDocs[$cid])) {
                // Временно не правим нищо, докато не реализираме getLinkedDocuments()
                // $rec = FALSE;
            }
        }
        
        return $rec;
    }


    public static function returned($mid, $date = NULL)
    {
        if (!($sendRec = static::fetch(array("#mid = '[#1#]' AND #action = '" . static::ACTION_SEND . "'", $mid)))) {
            // Няма изпращане с такъв MID
            return FALSE;
        }
    
        $sendRec->data = @unserialize($sendRec->data);
        
        if (!$sendRec->data) {
            $sendRec->data = new stdClass;
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
        $sendRec->data = serialize($sendRec->data);
    
        static::save($sendRec);
    
        $retRec = (object)array(
            'action' => static::ACTION_RETURN,
            'containerId' => $sendRec->containerId,
            'threadId'    => $sendRec->threadId,
            'parentId'    => $sendRec->id
        );
    
        static::save($retRec);
    
        // Нотификация за връщането на писмото до изпращача му
        bgerp_Notifications::add(
            'Върнати писма', // съобщение
            array('doc_Containers', 'list', 'threadId'=>$sendRec->threadId, 'containerId'=>$sendRec->containerId), // URL
            $sendRec->createdBy, // получател на нотификацията
            'alert' // Важност (приоритет)
        );
    
        return TRUE;
    }


    public static function received($mid, $date = NULL)
    {
        if (!($sendRec = static::fetch(array("#mid = '[#1#]' AND #action = '" . static::ACTION_SEND . "'", $mid)))) {
            // Няма изпращане с такъв MID
            return FALSE;
        }
    
        $sendRec->data = @unserialize($sendRec->data);
        
        if (!$sendRec->data) {
            $sendRec->data = new stdClass;
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
        $sendRec->data = serialize($sendRec->data);
    
        static::save($sendRec);
    
        $rcvRec = (object)array(
            'action' => static::ACTION_RECEIVE,
            'containerId' => $sendRec->containerId,
            'threadId'    => $sendRec->threadId,
            'parentId'    => $sendRec->id
        );
    
        static::save($rcvRec);
    
        // Нотификация за връщането на писмото до изпращача му
        bgerp_Notifications::add(
            'Получени писма', // съобщение
            array('doc_Containers', 'list', 'threadId'=>$sendRec->threadId, 'containerId'=>$sendRec->containerId), // URL
            $sendRec->createdBy, // получател на нотификацията
            'alert' // Важност (приоритет)
        );
    
        return TRUE;
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
     * Изпълнява се след всеки запис в модела
     *
     * @param log_Documents $mvc
     * @param int $id key(mvc=log_Documents)
     * @param stdClass $rec запис на модела, който е бил записан в БД
     */
    static function on_AfterSave($mvc, $id, $rec)
    {
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
                core_Cache::set(static::CACHE_TYPE, $cacheKey, $history, '2 дена');
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
        static::log('Регенериране на историята на нишка', $threadId, 3);
        
        $query = static::getQuery();
        $query->where("#threadId = {$threadId}");
        $query->orderBy('#createdOn');
        
        $data = array();   // Масив с историите на контейнерите в нишката
        while ($rec = $query->fetch()) {
            $data[$rec->containerId]->summary[$rec->action] += 1;
            $data[$rec->containerId]->containerId = $rec->containerId;
        }
        
        return $data;
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
     * @todo Чака за документация...
     */
    public static function renderSummary($data)
    {
        static $wordings = NULL;
        
        if (empty($data->summary)) {
            return '';
        }
        
        if (!isset($wordings)) {
            $wordings = array(
                static::ACTION_SEND    => array('изпращане', 'изпращания'),
                static::ACTION_RECEIVE => array('получаване', 'получавания'),
                static::ACTION_RETURN  => array('връщане', 'връщания'),
                static::ACTION_PRINT   => array('отпечатване', 'отпечатвания'),
                static::ACTION_OPEN   => array('показване', 'показвания'),
            );
        }
        
        $html = '';
        
        foreach ($data->summary as $action=>$count) {
            $actionVerbal = $action;
            if (isset($wordings[$action])) {
                $actionVerbal = $wordings[$action][intval($count > 1)];
            }
            
            $link = ht::createLink(
                "<b>{$count}</b> <span>{$actionVerbal}</span>", 
                array(get_called_class(), 'list', 'containerId'=>$data->containerId)
            );
            $html .= "<li class=\"action {$action}\">{$link}</li>";
        }
        
        $html = "<ul class=\"history summary\">{$html}</ul>";
        
        return $html;
    }
    
    
    /**
     * Шаблон (ET) съдържащ историята на документа в този контейнер.
     *
     * @param int $container key(mvc=doc_Containers)
     * @param int $threadId key(mvc=doc_Thread) нишката,в която е контейнера
     * @return core_ET
     * @deprecated
     */
    public static function getHistory($containerId, $threadId)
    {
        $data = static::prepareContainerHistory($containerId, $threadId);
        
        return static::renderHistory($data);
    }
    
    
    /**
     * Шаблон (ET) съдържащ обобщената историята на документа в този контейнер.
     *
     * @param int $container key(mvc=doc_Containers)
     * @param int $threadId key(mvc=doc_Thread) нишката,в която е контейнера
     * @return core_ET
     */
    public static function getSummary($containerId, $threadId)
    {
        $data = static::prepareContainerHistory($containerId, $threadId);
        
        return static::renderSummary($data);
    }
    
    
    /**
     * Шаблон, съдържащ потребителите и датите, в които документа е бил видян след споделяне.
     *
     * @param int $container key(mvc=doc_Containers)
     * @param int $threadId key(mvc=doc_Thread) нишката,в която е контейнера
     * @return core_ET NULL ако документа не е споделен с никого
     */
    public static function getSharingHistory($containerId, $threadId)
    {
        // Цялата история на документа
        $history = static::prepareContainerHistory($containerId, $threadId);
        
        // С кого е бил споделен този документ?
        $sharedWith = doc_Containers::getShared($containerId);
        
        if ($sharedWith) {
            $sharedWith = type_Keylist::toArray($sharedWith);
        } else {
            $sharedWith = array();
        }
        
        if (count($history->recs)) {
            foreach ($history->recs as $rec) {
                if ($rec->action == 'viewed') {
                    $sharedWith[$rec->createdBy] = $rec->createdOn;
                }
            }
        }
        
        if (count($sharedWith)) {
            $tpl = new core_ET(static::renderSharedHistory($sharedWith));
        } else {
            $tpl = NULL;
        }
        
        return $tpl;
    }
    
    
    /**
     * Помощен метод: рендира историята на споделянията и вижданията
     *
     * @param array $sharedWith масив с ключ ИД на потребител и стойност - дата
     * @return string
     */
    static function renderSharedHistory($sharedWith)
    {
        expect(count($sharedWith));
        
        $first = TRUE;
        $html = '';
        
        $html = array();
        
        foreach ($sharedWith as $userId => $seenDate) {
            $userRec = core_Users::fetch($userId);
            $nick = mb_convert_case(core_Users::getVerbal($userRec, 'nick'), MB_CASE_TITLE, "UTF-8");
            
            if ($userId == $seenDate) {
                $html[] = $nick;
            } else {
                $seenDate = mb_strtolower(core_DateTime::mysql2verbal($seenDate, 'smartTime'));
                $html[] = "<span style='color:black;'>" . $nick . "</span>({$seenDate})";
            }
        }
        
        return implode(', ', $html);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_AfterPrepareListRows($mvc, $data)
    {
        $rows = $data->rows;
        $recs = $data->recs;
        
        if (empty($data->recs)) {
            return;
        }
        
        foreach ($recs as $i=>$rec) {
            $row = $rows[$i];
            
            if ($row->containerId) {
                $row->containerId = ht::createLink($row->containerId, array($mvc, 'list', 'containerId'=>$rec->containerId));
            }
            $rec->data = @unserialize($rec->data);
            $mvc->formatAction($rec, $row);
        }
    }
    
    
    /**
     * Форматира запис от историята в лесно четим вид.
     *
     * @param stdClass $rec запис от този модел
     * @param stdClass $row резултата
     */
    static function formatAction($rec, &$row)
    {
        $row->createdOn = static::getVerbal($rec, 'createdOn');
        $row->createdBy = static::getVerbal($rec, 'createdBy');
        $row->action    = $rec->action;
        
        $row->createdBy = '<div>' . $row->createdBy . '</div>';
        
        $row->userNdate = $row->createdBy . $row->createdOn;
        
        ob_start();
        print_r($rec->data);
        $dataStr = ob_get_clean();
        
        $row->data = "<pre>{$dataStr}</pre>";
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_AfterPrepareListFields($mvc, $data)
    {
        if ($containerId = Request::get('containerId', 'key(mvc=doc_Containers)')) {
            unset($data->listFields['containerId']);
            $data->query->where("#containerId = {$containerId}");
            $data->doc = doc_Containers::getDocument($containerId, 'doc_DocumentIntf');
        }
        
        $data->query->orderBy('#createdOn', 'DESC');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_AfterPrepareListTitle($mvc, $data)
    {
        if ($containerId = Request::get('containerId', 'key(mvc=doc_Containers)')) {
            $data->title = "История";
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_AfterRenderListTitle($mvc, &$tpl, $data)
    {
        /* @var $doc doc_DocumentIntf */
        $doc = $data->doc;
        
        if ($doc) {
            $row = $doc->getDocumentRow();
            $tpl = '<div class="listTitle">' . $doc->getLink() . '</div>';
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_AfterRenderListTable($mvc, &$tpl, $data)
    {
        if ($data->doc) {
            $tpl->append($data->doc->getDocumentBody());
        }
    }
}
