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
        
        self::$recForAdd[] = $rec;
    }
    
    
    /**
     * Връща линк с икона, за показванията
     * 
     * @param integer $containerId
     */
    public static function renderViewedLink($containerId)
    {
        $url = array();
        
        $attr = array();
        $attr['ef_icon'] = 'img/16/eye-close-icon.png';
        $attr['class'] = 'fright';
        $attr['style'] = 'display: inline-block; height: 16px;';
        
        if ($actArr = self::getActions($containerId, self::ACTION_VIEW, 1)) {
            $rec = array_pop($actArr);
            
            if (self::haveRightFor('renderview', $rec)) {
                
                $attr['ef_icon'] = 'img/16/eye-icon.png';
                
                $url = doclog_Documents::getLinkToSingle($containerId, self::ACTION_VIEW);
            }
        }
        
        return ht::createLink('', $url, NULL, $attr);
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
     * Подготвяне на данните за рендиране на детайла за принтирания
     * 
     * @param object $data
     */
    function prepareView($data)
    {
        // Ако сме в режим принтиране
        // Да не се изпълнява
        if (Request::get('Printing')) return ;
        
        // Вземаме cid от URL' то
        $cid = Request::get('Cid', 'int');
        
        // Ако не листваме данните за съответния контейнер
        if ($data->masterData->rec->containerId != $cid) return ;
        
        $actArr = self::getActions($cid, self::ACTION_VIEW);
        
        // Името на таба
        $data->TabCaption = 'Виждания (К)';
        
        if (!$actArr) {
            
            // Бутона да не е линк
            $data->disabled = TRUE;
            
            return ;
        } else {
            $rec = reset($actArr);
            if (!self::haveRightFor('renderview', $rec)) {
                unset($data->TabCaption);
                return ;
            }
        }
        
        $rows = array();
        
        // Обхождаме записите
        foreach ($actArr as $rec) {
            
            // Записите
            $row = (object)array(
                'createdOn' => $rec->createdOn,
                'createdBy' => $rec->createdBy,
                'action' => $rec->action,
                'cnt' => $rec->cnt
            );
            
            // Записите във вербален вид
            $row = self::recToVerbal($row, array_keys(get_object_vars($row)));
            
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
    function renderView($data)
    {
        // Ако няма записи
        if (!$data->rows) return ;
        
        // Вземаме шаблона за детайлите с попълнена титла
        $tpl = doclog_Documents::getLogDetailTpl();
        
        // Инстанция на класа
        $inst = cls::get('core_TableView');
        
        // Вземаме таблицата с попълнени данни
        $viewTpl = $inst->get($data->rows, 'createdOn=Дата, createdBy=Потребител, action=Действие, cnt=Брой');
        
        // Заместваме в главния шаблон за детайлите
        $tpl->append($viewTpl, 'content');
        
        return $tpl;
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
