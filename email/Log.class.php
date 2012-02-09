<?php 
/**
 * История от събития, свързани с изпращането и получаването на писма
 * 
 * @category   bgerp
 * @package    email
 * @author     Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @since      v 0.1
 *
 */
class email_Log extends core_Manager
{
    /**
     * Заглавие на таблицата
     */
    var $title = "Лог за имейли";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, email';
    
    
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
    var $canView = 'admin, email';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin, email';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'admin, email';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'email_Wrapper,  plg_Created';
    
    var $listFields = 'createdOn=Кога, createdBy=Кой/Какво, containerId=Кое, actionText=Резултат';

    
    /**
     * Масов-кеш за историите на контейнерите по нишки
     *
     * @var array
     */
    protected static $histories = array();
    
    const CACHE_TYPE = 'thread_history';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        // Тип на събитието
        $this->FLD("action", "enum(sent, printed, viewed)", "caption=Действие");
        
        // Нишка на документа, за който се отнася събитието
        $this->FLD('threadId', 'key(mvc=doc_Threads)', 'caption=Нишка');
        
        // Документ, за който се отнася събитието
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'caption=Контейнер');
        
        // Само за събитие `sent`: дата на получаване на писмото
        $this->FLD('receivedOn', 'datetime(format=smartTime)', 'caption=Получено->На');
        
        // Само за събитие `sent`: IP от което е получено писмото
        $this->FLD('receivedIp', 'ip', 'caption=Получено->IP');
        
        // Само за събитие `sent`: дата на връщане на писмото (в случай, че не е получено)
        $this->FLD('returnedOn', 'datetime(format=smartTime)', 'input=none,caption=Върнато на');
        
        // MID на документа
        $this->FLD('mid', 'varchar', 'input=none,caption=Ключ,column=none');
        
        // Допълнителни обстоятелства, в зависимост от събитието (в PHP serialize() формат)
        $this->FLD("data", "blob", 'caption=Обстоятелства,column=none');
        
        $this->setDbIndex('containerId');
    }
    
    
    /**
     * Отразява в историята акта на изпращане на писмо
     *
     * @param stdClass $messageRec
     */
    public static function sent($messageRec)
    {
        expect($messageRec->containerId);
        expect($messageRec->mid);

        if (empty($messageRec->threadId)) {
            // Извличаме $threadId, в случай, че не е зададено
            $messageRec->threadId    = doc_Containers::fetchField($messageRec->containerId, 'threadId');
        }
        
        expect($messageRec->threadId);
        
        $rec = new stdClass();
        
        $rec->action      = 'sent';
        $rec->containerId = $messageRec->containerId;
        $rec->threadId    = $messageRec->threadId;
        $rec->mid         = $messageRec->mid;
        $rec->data        = array(
            'boxFrom' => $messageRec->boxFrom,
            'toEml'   => $messageRec->emailTo,
            'subject' => $messageRec->subject,
            'options' => $messageRec->options,
        );
        
        $rec->data = serialize($rec->data);
        
        /*
         * Забележка: plg_Created ще попълни полетата createdBy (кой е изпратил писмото) и 
         * 			  createdOn (кога е станало това)
         */
        
        return static::save($rec, NULL, 'IGNORE');
    } 
    
    
    /**
     * Отразява в историята факта, че (по-рано изпратено от нас) писмо е видяно от получателя си
     *
     * @param string $mid
     * @param string $date
     * @param string $ip
     */
    public static function received($mid, $date = NULL, $ip = NULL)
    {
        if ( !($rec = static::fetch("#mid = '{$mid}'")) ) {
            return FALSE;
        }
        

        if (!empty($rec->receivedOn) && $rec->ip == $ip) {
            // Получаването на писмото (от това IP) вече е било отразено в историята; не правим нищо
            return;
        }
                
        if (!isset($date)) {
            $date = dt::now();
        }
        
        $rec->receivedOn = $date;
        $rec->receivedIp = $ip;
        
        return static::save($rec);
    } 
    
    
    /**
     * Отрязава в историята факта че (по-рано изпратено от нас) писмо не е доставено до получателя си
     *
     * @param string $mid
     * @param string $date дата на върнатото писмо
     */
    public static function returned($mid, $date = NULL)
    {
        if ( !($rec = static::fetch("#mid = '{$mid}'")) ) {
            return FALSE;
        }

        if (!empty($rec->returnedOn)) {
            // Връщането на писмото вече е било отразено в историята; не правим нищо
            return;
        }
        
        if (!isset($date)) {
            $date = dt::now();
        }
        
        $rec->returnedOn = $date;
        
        return static::save($rec);
    }
    
    
    /**
     * Отразява факта, че споделен документ е видян от текущия потребител.
     * 
     * Ако документа е споделен с текущия потребител, метода отразява виждането му в историята.
     *
     * @param int $containerId key(mvc=doc_Containers)
     * @param int $threadId key(mvc=doc_Threads)
     */
    public static function viewed($containerId, $threadId = NULL)
    {
        expect($containerId);
        
        // С кои потребители е споделен документа
        $sharedWith = doc_Containers::getShared($containerId);
        $currentUserId = core_Users::getCurrent();
        
        if (!type_Keylist::isIn($currentUserId, $sharedWith)) {
            // Документа не е споделен с текущия потребител - не правим нищо
            return;
        }
        
        if (empty($threadId)) {
            // Извличаме $threadId, в случай, че не е подадено като параметър
            $threadId = doc_Containers::fetchField($containerId, 'threadId');
        }
        
        expect($threadId);
        
        /*
         * Проверка дали текущия потребител е виждал този документ и преди
         * 
         */ 
        
        $viewedBefore = FALSE;
        
        // Първо проверяваме кешираната история
        if ($histRecs = static::$histories[$threadId][$containerId]->recs) {
            // Имаме кеширана история на документа
            foreach ($histRecs as $r) {
                if ($r->action == 'viewed' && $r->createdBy == $currentUserId) {
                    // Документа е бил виждан преди от текущия потребител
                    $viewedBefore = TRUE;
                    break;
                }
            }
        } else {
            // Няма кешинара история - проверяваме директно в БД
            if (static::fetch(
            		"#containerId = {$containerId} 
        		    AND #action = 'viewed' 
        		    AND #createdBy = {$currentUserId}")) {
                // Документа е бил виждан преди от текущия потребител
                $viewedBefore = TRUE;
            }
        }
        
        if ($viewedBefore) {
            // Документа е бил виждан преди от текущия потребител и това е отразено в историята
            // Не правим нищо.
            return;
        }
        
        $rec = new stdClass();
        
        $rec->action      = 'viewed';
        $rec->containerId = $containerId;
        $rec->threadId    = $threadId;
        
        /*
         * Забележка: plg_Created ще попълни полетата createdBy (кой е видял документа) и 
         * 			  createdOn (кога е станало това)
         */
        
        return static::save($rec);
    }
    
    
    /**
     * Отразява факта, че документ е отпечатан
     *
     * @param int $containerId key(mvc=doc_Containers)
     * @param int $threadId key(mvc=doc_Threads)
     */
    public static function printed($containerId, $threadId = NULL)
    {
        expect($containerId);
        
        if (empty($threadId)) {
            // Извличаме $threadId, в случай, че не е подадено като параметър
            $threadId = doc_Containers::fetchField($containerId, 'threadId');
        }
        
        expect($threadId);
        
        $rec = new stdClass();
        
        $rec->action      = 'printed';
        $rec->containerId = $containerId;
        $rec->threadId    = $threadId;
        $rec->userId      = core_Users::getCurrent();
        
        /*
         * Забележка: plg_Created ще попълни полетата createdBy (кой е отпечатал документа) и 
         * 			  createdOn (кога е станало това)
         */
        
        return static::save($rec);
        
    }
    
    
    /**
     * Изпълнява се след всеки запис в модела
     *
     * @param email_Log $mvc
     * @param int $id key(mvc=email_Log)
     * @param stdClass $rec запис на модела, който е бил записан в БД
     */
    function on_AfterSave($mvc, $id, $rec)
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
    public static function prepareThreadHistory($threadId)
    {
        if (!isset(static::$histories[$threadId])) {
            static::$histories[$threadId] = static::loadHistoryFromCache($threadId);
        }
        
        return static::$histories[$threadId];
    }
    
    
    /**
     * Зарежда историята на нишка от кеша. Ако я няма там преизчислява я и я записва в кеша
     * 
     * @see core_Cache
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return array историята на нишката, във вида в който я връща @link email_Log::prepareThreadHistory()
     */
    protected static function loadHistoryFromCache($threadId)
    {
        $cacheKey = static::getHistoryCacheKey($threadId);
        
        if (($history = core_Cache::get(static::CACHE_TYPE, $cacheKey)) === FALSE) {
            // Историята на този тред я няма в кеша - подготвяме я и я записваме в кеша
            $history = static::buildThreadHistory($threadId);
            core_Cache::set(static::CACHE_TYPE, $cacheKey, $history);
        }
        
        return $history;
    }
    
    
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
     * @param unknown_type $threadId
     */
    protected static function buildThreadHistory($threadId)
    {
        $query = static::getQuery();
        $query->where("#threadId = {$threadId}");
        $query->orderBy('#createdOn');
        
        $data          = array(); // Масив с историите на контейнерите в нишката
        
        while ($rec = $query->fetch()) {
            switch ($rec->action) {
                case 'sent':
                    $rec->data = unserialize($rec->data);
                    if (isset($rec->returnedOn)) {
                        $data[$rec->containerId]->summary['returned'] += 1;
                    }
                    if (isset($rec->receivedOn)) {
                        $data[$rec->containerId]->summary['received'] += 1;
                    }
                    break;
                case 'viewed':
                    break;
                case 'printed':
                    break;
                default:
                    expect(FALSE, "Неочаквана стойност: {$rec->action}");
            }
            
            $data[$rec->containerId]->summary[$rec->action] += 1;

            $data[$rec->containerId]->recs[] = $rec;
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
    public static function prepareContainerHistory($containerId, $threadId)
    {
        $threadHistory = static::prepareThreadHistory($threadId);
        
        return $threadHistory[$containerId];
    }
    
    
    /**
     * Шаблон (@link core_ET) с историята на документ.
     * 
     * @param stdClass $data обект, който вече е бил подготвен чрез @link email_Log::prepareHistory()
     * @return core_ET
     */
    public static function renderHistory($data)
    {
        $tpl = new core_ET();
        
        $tplString = <<<EOT
        	<ul class="history detailed">
        		<!--ET_BEGIN ROW-->
        			<li class="row [#action#]">
        				<span class="verbal">На</span>
        				<span class="date">[#date#]</span> 
        				<span class="user">[#createdBy#]</span>
        				<span class="action">[#actionText#]</span>
        			</li>
        		<!--ET_END ROW-->
        	</ul>
EOT;

        $tpl = new core_ET($tplString);
        
        // recToVerbal
        $rows = array();
        if ($data->recs) {
            foreach ($data->recs as $i=>$rec) {
                static::formatAction($rec, $rows[$i]);
            }
        } else {
            return '';
        }
        
        $rowTpl = &$tpl->getBlock('ROW');
        foreach ($rows as $i=>$row) {
            $rowTpl->placeObject($row);
            $rowTpl->append2Master();
        }
        
        $tpl->removeBlocks();
        
        return $tpl;
    }
    
    
    public static function renderSummary($data)
    {
        static $wordings = NULL;
        
        $tplString = <<<EOT
        	<ul class="history summary">
        		<!--ET_BEGIN sent-->
        			<li class="sent"><b>[#sent#]</b> <span>[#sentVerbal#]</span></li>
        		<!--ET_END sent-->
        		<!--ET_BEGIN received-->
        			<li class="received"><b>[#received#]</b> <span>[#receivedVerbal#]</span></li>
        		<!--ET_END received-->
        		<!--ET_BEGIN returned-->
        			<li class="returned"><b>[#returned#]</b> <span>[#returnedVerbal#]</span></li>
        		<!--ET_END returned-->
        		<!--ET_BEGIN printed-->
        			<li class="printed"><b>[#printed#]</b> <span>[#printedVerbal#]</span></li>
        		<!--ET_END printed-->
        		<!--ET_BEGIN shared-->
        			<li class="shared"><b>[#shared#]</b> <span>[#sharedVerbal#]</span></li>
        		<!--ET_END shared-->
        		<!--ET_BEGIN detailed-->
        			<li class="detailed"><b>&nbsp;&nbsp;</b> [#detailed#]</li>
        		<!--ET_END detailed-->
        	</ul>
EOT;

        $tpl = new core_ET($tplString);
        
        if (!isset($wordings)) {
            $wordings = array(
                'sent'     => array('изпращане', 'изпращания'),
                'received' => array('получаване', 'получавания'),
                'returned' => array('връщане', 'връщания'),
                'printed'  => array('отпечатване', 'отпечатвания'),
                'shared'   => array('споделяне', 'споделяния'),
            );
        }
        
        if ($data) {
            foreach ($data->summary as $n=>$v) {
                if ($v) {
                    $data->summary["{$n}Verbal"] = tr($wordings[$n][intval($v > 1)]);
                }
            }
            
            if (!empty($data->summary)) {
                $data->summary['detailed'] = ht::createLink('хронология ...', array('email_Log', 'list', 'containerId'=>$data->containerId));
            }
            
            $tpl->placeObject($data->summary);
        }
        
        $tpl->removeBlocks();
        
        return $tpl;
    }
    
    
    /**
     * Шаблон (ET) съдържащ историята на документа в този контейнер.
     * 
     * @param int $container key(mvc=doc_Containers)
     * @param int $threadId key(mvc=doc_Thread) нишката,в която е контейнера 
     * @return core_ET
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
     * @return core_ET
     */
    public static function getSharingHistory($containerId, $threadId)
    {
        // Цялата история на документа
        $history    = static::prepareContainerHistory($containerId, $threadId);
        
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
            $tpl = static::renderSharedHistory($sharedWith);
        } else {
            $tpl = new core_ET('<i>Няма споделяния</i>');
        }
        
        return $tpl;
    }
    
    
    static function renderSharedHistory($sharedWith)
    {
        expect(count($sharedWith));
        
        $tplString = <<< EOT
        	<ul>
        	<!--ET_BEGIN ROW-->
        		<li class="[#class#]"><span class="user">[#user#]</span>: [#seenStatus#]</li>
        	<!--ET_END ROW-->
        	</ul>
EOT;

        $tpl = new core_ET($tplString);
        
        $rowTpl = $tpl->getBlock('ROW');
        foreach ($sharedWith as $userId => $seenDate) {
            $userRec = core_Users::fetch($userId);
            $row = new stdClass();
            $row->user = core_Users::getVerbal($userRec, 'names');
            
            if ($userId == $seenDate) {
                $row->seenStatus = 'не е отварян';
                $row->class = 'unseen';
            } else {
                $row->seenStatus = 'видян (' . $seenDate . ')';
                $row->class = 'seen';
            }
            
            $rowTpl->placeObject($row);
            $rowTpl->append2master();
        }
        
        $tpl->removeBlocks();
        
        return $tpl;
    }
    
    
    function on_AfterPrepareListRows($mvc, $data)
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
    
    
    static function formatAction($rec, &$row)
    {
        $row->createdOn = static::getVerbal($rec, 'createdOn');
        $row->createdBy = static::getVerbal($rec, 'createdBy');
        $row->action    = $rec->action;
        
        switch ($rec->action) {
            case 'sent':
                $row->createdBy .= ' '
                        . '<span class="verbal">'
                            . tr('изпрати до')
                        . '</span>'
                        . ' '  
                        . '<span class="email">'
                            . $rec->data['toEml']
                        . '</span>';
                
                if ($rec->receivedOn) {
                    $row->actionText .= 
                    	'<b class="received">'
                            . '<span class="verbal">' 
                                . tr('получено') 
                            . '</span>'
                            . ': '
                            . '<span class="date">'
                            	. static::getVerbal($rec, 'receivedOn')
                            . '</span>'
                        . '</b>';
                }
                if ($rec->returnedOn) {
                    $row->actionText .= 
                    	'<b class="returned">'
                        . '<span class="verbal">' 
                            . tr('върнато') 
                        . '</span>'
                        . ': '
                        . '<span class="date">'
                        	. static::getVerbal($rec, 'returnedOn')
                        . '</span>'
                        . '</b>';
                }
                break;
            case 'viewed':
                $row->createdBy .= ' ' 
                        . '<span class="verbal">'
                	        . tr('видя')  
                        . '</span>';
                break;
            case 'printed':
                $row->createdBy .= ' ' 
                	    . '<span class="print action">'
                        . '<span class="verbal">'
                	        . tr('отпечата')  
                        . '</span>'
                    . '</span>';
                break;
            default:
                expect(FALSE, "Неочаквана стойност: {$rec->action}");
        }
        
        $row->createdBy = '<div style="text-align: right;">' . $row->createdBy . '</div>';
    }
    
    
    function on_AfterPrepareListFields($mvc, $data)
    {
        if ($containerId = Request::get('containerId', 'key(mvc=doc_Containers)')) {
            unset($data->listFields['containerId']);
            $data->query->where("#containerId = {$containerId}");
            $data->doc = doc_Containers::getDocument($containerId, 'doc_DocumentIntf');
        }
        
        $data->query->orderBy('#createdOn', 'DESC');
    }
    
    
    function on_AfterPrepareListTitle($mvc, $data)
    {
        if ($containerId = Request::get('containerId', 'key(mvc=doc_Containers)')) {
            $data->title = "История";
        }
    }
    
    
    function on_AfterRenderListTitle($mvc, $tpl, $data)
    {
        if ($data->doc) {
            $row = $data->doc->getDocumentRow();
            $tpl = '<div class="listTitle">История на документ "<b>' . $row->title . '</b>"</div>';
        }
    }
    
    
    
    function on_AfterRenderListTable($mvc, $tpl, $data)
    {
        if ($data->doc) {
            $tpl->append($data->doc->getDocumentBody());
        }
    }
}
