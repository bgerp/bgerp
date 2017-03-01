<?php 


/**
 * Лог на документи за контрактори
 * 
 * @category  bgerp
 * @package   colab
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class colab_DocumentLog extends core_Manager
{
    
    
    /**
     * Брой елементи на страница
     */
    var $itemsPerPage = 20;
    
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Лог на документи за контрактори";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo, admin';
    
    
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
    var $canList = 'ceo, admin';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created';
    
    
    /**
     * 
     */
    public $canRenderview = 'powerUser';
    
    
    /**
     * Екшъна за виждане
     */
    const ACTION_VIEW = 'view';
    
    
    /**
     * 
     */
    static $recForAdd = array();
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        // enum полетата на екшъните
        $actionsEnum = array(
            self::ACTION_VIEW . ' = Виждане'
        );
        
        // Тип на събитието
        $this->FLD("action", 'enum(' . implode($actionsEnum) . ')', "caption = Действие");
        
        // Документ, за който се отнася събитието
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'caption = Контейнер');
        
        $this->FLD('cnt', 'int', 'caption=Брой, notNull');
        
        $this->setDbIndex('containerId');
        
        $this->setDbUnique('action, containerId, createdBy');
    }
    
    
    /**
     * Отбелязва документа, като видян
     * 
     * @param integer $containerId
     * @param integer $userId
     */
    public static function markAsViewed($containerId, $userId = NULL)
    {
        // За да може да се извика on_ShutDown
        cls::get('colab_DocumentLog');
        
        $rec = new stdClass();
        $rec->action = self::ACTION_VIEW;
        $rec->containerId = $containerId;
        $rec->createdBy = core_Users::getCurrent();
        
        $document = doc_Containers::getDocument($containerId);
        $document->instance->logRead('Виждане', $document->that);
        
        self::$recForAdd[] = $rec;
    }
    
    
    /**
     * Връща линк с икона, за показванията
     * 
     * @param integer $containerId
     */
    public static function renderViewedLink($containerId)
    {
        if (self::haveRightFor('renderview')) {
            
            $attr = array('title' => tr('Документът е видим за партньори'), 'class' => 'eyeIcon');
    		$attr = ht::addBackgroundIcon($attr, 'img/16/eye-open.png');

            $viewLink = ht::createElement('span', $attr, '', TRUE);
        }
        
        if ($actArr = self::getActions($containerId, self::ACTION_VIEW, 1)) {
            $rec = array_pop($actArr);
            
            if (self::haveRightFor('renderview', $rec)) {
                
                $viewCnt = self::getViewCount($containerId);
                
                if ($viewCnt) {
                    $attr = array();
                    $attr['class'] = 'showViewed docSettingsCnt tooltip-arrow-link';
                    $attr['title'] = tr('Виждания от колаборатори');
                    $attr['data-url'] = toUrl(array(get_called_class(), 'showViewed', 'containerId' => $containerId), 'local');
                    $attr['data-useHover'] = '1';
                    $attr['data-useCache'] = '1';
                    
                    $viewCntLink = ht::createElement('span', $attr, "<span>" . $viewCnt . "</span>", TRUE);
                    
                    $viewCntLink = '<div class="pluginCountButtonNub"><s></s><i></i></div>' . $viewCntLink;
                    
                    $elemId = self::getElemId($containerId);
                    
                    $viewLink .= $viewCntLink . "<div class='additionalInfo-holder'><span class='additionalInfo' id='{$elemId}'></span></div>";
                }
            }
        }
        
        $viewLink = "<span>" . $viewLink . "</span>";
        
        return $viewLink;
    }
    
    
    /**
     * 
     */
    function act_ShowViewed()
    {
        expect(Request::get('ajax_mode'));
        
        $this->requireRightFor('renderview');
        
        $cid = Request::get('containerId', 'int');
        
        $rec = self::fetch("#containerId = '{$cid}'");
        
        expect($rec);
        
        $this->requireRightFor('renderview', $rec);
        
        $html = $this->getViewHtml($cid);
        
        $resObj = new stdClass();
		$resObj->func = "html";
		$resObj->arg = array('id' => self::getElemId($cid), 'html' => $html, 'replace' => TRUE);
		
        $res = array($resObj);
        
        return $res;
    }
    
    
    /**
     * Подготвя лога за виждания
     * 
     * @param integer $cid
     * 
     * @return string
     */
    protected static function getViewHtml($cid)
    {
        $actArr = self::getActions($cid, self::ACTION_VIEW);
        
        $resStr = '';
        
        // Обхождаме записите
        foreach ($actArr as $rec) {
            
            // Записите
            $row = (object)array(
                'createdOn' => $rec->createdOn,
                'createdBy' => $rec->createdBy,
                'cnt' => $rec->cnt
            );
            
            // Записите във вербален вид
            $row = self::recToVerbal($row, array_keys(get_object_vars($row)));
            
            $viewTimes = $row->cnt . ' ' . tr('пъти');
            
            $resStr .= "<div class='nowrap'>{$row->createdBy} ({$row->createdOn}) - {$viewTimes}</div>";
        }
        
        return $resStr;
    }
    
    
    /**
     * Връща id за html елемент
     * 
     * @param stdObject $rec
     * 
     * @return string
     */
    protected static function getElemId($cid)
    {
        
        return 'showViewed_' . $cid;
    }
    
    
    /**
     * Връща броя на вижданията на документа от колабораторите
     * 
     * @param integer $cid
     * @param boolean $group
     * 
     * @return integer
     */
    protected static function getViewCount($cid, $group = TRUE)
    {
        $query = self::getQuery();
        $query->where(array("#containerId = '[#1#]'", $cid));
        
        $cnt = 0;
        
        if ($group) {
            $cnt = $query->count();
        } else {
            while ($rec = $query->fetch()) {
                $cnt += $rec->cnt;
            }
        }
        
        return $cnt;
    }
    
    
    /**
     * Проверява дали има съответното действия за съответния документ
     * 
     * @param integer $containerId
     * @param string $action
     * 
     * @return boolean
     */
    protected static function haveAction($containerId, $action)
    {
        if (self::getActions($containerId, $action, 1)) {
            
            return TRUE;
        }
        
        return FALSE;
    }
    
    
    /**
     * Връща всички дейстив за документа
     * 
     * @param integer $containerId
     * @param string $action
     * @param integer limit
     * 
     * @return array
     */
    protected static function getActions($containerId, $action = NULL, $limit=NULL)
    {
        $resArr = array();
        
        $query = self::getQuery();
        $query->where("#containerId = {$containerId}");
        
        if (isset($action)) {
            $query->where(array("#action = '[#1#]'", $action));
        }
        
        if ($limit) {
            $query->limit(1);
        }
        
        while ($rec = $query->fetch()) {
            $resArr[$rec->id] = $rec;
        }
        
        return $resArr;
    }
    
    
    /**
     * 
     * 
     * @param colab_DocumentLog $mvc
     */
    protected static function on_Shutdown($mvc)
    {
        if (!self::$recForAdd) return ;
        
        foreach (self::$recForAdd as $rec) {
            
            if (!($nRec = self::fetch(array("#containerId = [#1#] AND #action = '[#2#]' AND #createdBy = [#3#]", $rec->containerId, $rec->action, $rec->createdBy)))) {
                $nRec = $rec;
            }
            
            $nRec->cnt++;
            
            $mvc->save($nRec);
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if (($requiredRoles != 'no_one') && $rec && $action == 'renderview') {
            $doc = doc_Containers::getDocument($rec->containerId);
            if (!$doc->instance->haveRightFor('single', $doc->that)) {
                
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * След изчислянване на вербалната стойност
     * 
     * @param colab_DocumentLog $mvc
     * @param object $row
     * @param object $rec
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        // Ако има from
        if ($rec->createdBy) {
            
            // Линк към визитката
            $row->createdBy = crm_Profiles::createLink($rec->createdBy);
        }
    }
}
