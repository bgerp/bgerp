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
    
    var $listFields = 'containerId, date, createdBy=Кой, action=Какво, feedback=Резултат';

    
    /**
     * Масов-кеш за историите на контейнерите по нишки
     *
     * @var array
     */
    protected static $histories = array();
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        // Дата на събитието
        $this->FLD("date", "datetime", "caption=На");
        
        // Тип на събитието
        $this->FLD("action", "enum(sent, printed, shared)", "caption=Действие");
        
        // Нишка на документа, за който се отнася събитието
        $this->FLD('threadId', 'key(mvc=doc_Threads)', 'caption=Нишка');
        
        // Документ, за който се отнася събитието
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'caption=Контейнер');
        
        // Само за събитие `sent`: дата на получаване на писмото
        $this->FLD('receivedOn', 'datetime', 'caption=Получено->На');
        
        // Само за събитие `sent`: IP от което е получено писмото
        $this->FLD('receivedIp', 'ip', 'caption=Получено->IP');
        
        // Само за събитие `sent`: дата на връщане на писмото (в случай, че не е получено)
        $this->FLD('returnedOn', 'datetime', 'input=none,caption=Върнато на');
        
        // MID на документа
        $this->FLD('mid', 'varchar', 'input=none,caption=Ключ,column=none');
        
        // Само за събитие `shared`: Потребител, с който е споделен документа
        $this->FLD("userId", "key(mvc=core_Users)", 'caption=Потребител,column=none');
        
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
            $messageRec->threadId    = doc_Containers::fetchField($messageRec->containerId, 'threadId');
        }
        
        expect($messageRec->threadId);
        
        $rec = new stdClass();
        
        $rec->date        = dt::now();
        $rec->action      = 'sent';
        $rec->containerId = $messageRec->containerId;
        $rec->threadId    = $messageRec->threadId;
        $rec->mid         = $messageRec->mid;
        $rec->data        = array(
            'boxFrom' => $messageRec->boxFrom,
            'toEml'   => $messageRec->toEml,
            'subject' => $messageRec->subject,
            'options' => $messageRec->options,
        );
        
        $rec->data = serialize($rec->data);
        
        return static::save($rec);
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

        if (!isset($date)) {
            $date = dt::now();
        }
        
        $rec->returnedOn = $date;
        
        return static::save($rec);
    }
    
    
    /**
     * Отразява факта, че документ е споделен
     *
     * @param int $userId key(mvc=core_Users) с кого е споделен документа
     * @param int $containerId key(mvc=doc_Containers)
     * @param int $threadId key(mvc=doc_Threads)
     */
    public static function shared($userId, $containerId, $threadId = NULL)
    {
        expect($userId);
        expect($containerId);
        
        if (empty($threadId)) {
            $threadId = doc_Containers::fetchField($containerId, 'threadId');
        }
        
        expect($threadId);
        
        $rec = new stdClass();
        
        $rec->date        = dt::now();
        $rec->action      = 'shared';
        $rec->containerId = $containerId;
        $rec->threadId    = $threadId;
        $rec->userId      = $userId;
        
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
            $threadId = doc_Containers::fetchField($containerId, 'threadId');
        }
        
        expect($threadId);
        
        $rec = new stdClass();
        
        $rec->date        = dt::now();
        $rec->action      = 'printed';
        $rec->containerId = $containerId;
        $rec->threadId    = $threadId;
        $rec->userId      = core_Users::getCurrent();
        
        return static::save($rec);
        
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
                    case 'shared':
                        break;
                    case 'printed':
                        break;
                    default:
                        expect(FALSE, "Неочаквана стойност: {$rec->action}");
                }
                
                $data[$rec->containerId]->summary[$rec->action] += 1;
    
                $data[$rec->containerId]->recs[] = $rec;
            }
            
            static::$histories[$threadId] = $data;
        }
        
        return static::$histories[$threadId];
    }
    
    
    /**
     * Подготвя историята на един контейнер
     *
     * @param int $containerId key(mvc=doc_Containers)
     * @param int $threadId key(mvc=doc_Threads)
     */
    static public function prepareContainerHistory($containerId, $threadId)
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
            $mvc->formatAction($rec, $row);
        }
        
    }
    
    
    function formatAction($rec, $row)
    {
        $row->feedback = '-';
        
        switch ($rec->action) {
            case 'sent':
                $rec->data   = unserialize($rec->data);
                $row->action = 
                	'<div class="sent action">'
                        . '<span class="verbal">'
                            . tr('изпрати до')
                        . '</span>'
                        . ' '  
                        . '<span class="email">'
                            . $rec->data['toEml']
                        . '</span>'
                        . '<small>' 
                            . $rec->data['subject'] 
                        . '</small>'
                    . '</div>';
                
                $row->feedback = '';
                
                if ($rec->receivedOn) {
                    $row->feedback .= 
                    	'<div class="received">'
                            . '<span class="verbal">' 
                                . tr('получено на') 
                            . '</span>'
                            . ' '
                            . '<span class="date">'
                            	. $this->getVerbal($rec, 'receivedOn')
                            . '</span>'
                        . '</div>';
                }
                if ($rec->returnedOn) {
                    $row->feedback .= 
                    	'<div class="returned">'
                        . '<span class="verbal">' 
                            . tr('върнато на') 
                        . '</span>'
                        . ' '
                        . '<span class="date">'
                        	. $this->getVerbal($rec, 'returnedOn')
                        . '</span>'
                        . '</div>';
                }
                break;
            case 'shared':
                $row->action = 
                	'<div class="shared action">'
                        . '<span class="verbal">'
                	        . tr('сподели с')  
                        . '</span>'
                        . ' '
            	        . '<span class="user">'
                            . $this->getVerbal($rec, 'userId')
                        . '</span>'
                    . '</div>';
                break;
            case 'printed':
                $row->action = 
                	'<div class="print action">'
                        . '<span class="verbal">'
                	        . tr('отпечата')  
                        . '</span>'
                    . '</div>';
                break;
            default:
                expect(FALSE, "Неочаквана стойност: {$action}");
        }
        
    }
    
    
    function on_AfterPrepareListFields($mvc, $data)
    {
        if ($containerId = Request::get('containerId', 'key(mvc=doc_Containers)')) {
            unset($data->listFields['containerId']);
            $data->query->where("#containerId = {$containerId}");
        }
    }
    
    
    function on_AfterPrepareListTitle($mvc, $data)
    {
        if ($containerId = Request::get('containerId', 'key(mvc=doc_Containers)')) {
            $data->title = "История";
        }
    }
}
