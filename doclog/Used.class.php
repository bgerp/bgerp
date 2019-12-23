<?php 

/**
 * Лог за използванията
 *
 * @category  bgerp
 * @package   doclog
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doclog_Used extends core_Manager
{
    /**
     * Заглавие на таблицата
     */
    public $title = 'Използвани документи';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'debug';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'debug';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created';
    
    
    /**
     * Масив, който ще се добавя в on_Shutdown
     */
    protected static $usedArr = array();
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'caption=Контейнер->Документ');
        $this->FLD('usedContainerId', 'key(mvc=doc_Containers)', 'caption=Контейнер->Използван');
        
        $this->setDbIndex('usedContainerId');
        $this->setDbUnique('containerId, usedContainerId');
    }
    
    
    /**
     * Добавя запис
     *
     * @param int $cid
     * @param int $usedCid
     */
    public static function add($cid, $usedCid)
    {
        // За да се гарантира извикването на on_Shutdown
        cls::get(get_called_class());
        if (!$cid || !$usedCid) {
            error('Липсва стойност', $cid, $usedCid);
        }
        self::$usedArr[] = array('cid' => $cid, 'usedCid' => $usedCid);
    }
    
    
    /**
     * Премахва запис
     *
     * @param int $cid
     * @param int $usedCid
     */
    public static function remove($cid, $usedCid)
    {
        if (!$cid || !$usedCid) {
            
            return ;
        }
        
        self::delete("#containerId = {$cid} AND #usedContainerId = {$usedCid}");
        
        $threadId = doc_Containers::fetchField($usedCid, 'threadId');
        
        if ($threadId) {
            doclog_Documents::removeHistoryFromCache($threadId);
        }
    }
    
    
    /**
     * Подготвя записите за показване
     *
     * @param int             $cid
     * @param NULL|core_Pager $pager
     *
     * @return array
     */
    public static function prepareRecsFor($cid, &$pager = null)
    {
        $query = self::getQuery();
        $query->where(array("#usedContainerId = '[#1#]'", $cid));
        
        $query->orderBy('createdOn', 'DESC');
        
        // Ако е подаден обект за странициране
        if ($pager) {
            
            // Задаваме лимита за странициране
            $pager->setLimit($query);
        }
        
        $rowsArr = array();
        
        while ($rec = $query->fetch()) {
            
            // Добавяме в масива
            $rowsArr[] = self::recToVerbal($rec);
        }
        
        return $rowsArr;
    }
    
    
    /**
     * Връща броя на използваните документи
     *
     * @param int $cid
     *
     * @return int
     */
    public static function getUsedCount($cid)
    {
        $query = self::getQuery();
        $query->where(array("#usedContainerId = '[#1#]'", $cid));
        
        $cnt = $query->count();
        
        return $cnt;
    }
    
    
    /**
     * Връща броя на използваните документи за всичко контейнери
     *
     * @param array $cArr
     *
     * @return array
     */
    public static function getAllUsedCount($cArr)
    {
        $resArr = array();
        
        if (empty($cArr)) {
            
            return $resArr;
        }
        
        $query = self::getQuery();
        $query->in('usedContainerId', $cArr);
        $query->show('cnt, id, usedContainerId');
        $query->XPR('cnt', 'int', 'count(#usedContainerId)');
        
        $query->groupBy('usedContainerId');
        while ($rec = $query->fetch()) {
            $resArr[$rec->usedContainerId] = $rec->cnt;
        }
        
        return $resArr;
    }
    
    
    /**
     * При приключване на изпълнените на скрипта
     *
     * @param doclog_Used $mvc
     */
    public static function on_Shutdown($mvc)
    {
        foreach (self::$usedArr as $usedArr) {
            $rec = new stdClass();
            $rec->containerId = $usedArr['cid'];
            $rec->usedContainerId = $usedArr['usedCid'];
            
            if (!self::save($rec, null, 'IGNORE')) {
                continue;
            }
            
            // Добавяме използване на документа
            if ($usedArr['usedCid']) {
                $uDoc = doc_Containers::getDocument($usedArr['usedCid']);
                $uDoc->instance->logRead('Използване на документа', $uDoc->that);
                log_Data::flush();
            }
            
            try {
                $threadId = doc_Containers::fetchField($rec->usedContainerId, 'threadId');
                doclog_Documents::removeHistoryFromCache($threadId);
            } catch (ErrorException $e) {
                reportException($e);
                
                continue;
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param doclog_Used $mvc
     * @param stdClass    $row Това ще се покаже
     * @param stdClass    $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        try {
            $row->containerId = doc_Containers::getLinkForSingle($rec->containerId);
        } catch (ErrorException $e) {
            $row->containerId = tr('Грешка при показване');
        }
        
        try {
            $row->usedContainerId = doc_Containers::getLinkForSingle($rec->usedContainerId);
        } catch (ErrorException $e) {
            $row->usedContainerId = tr('Грешка при показване');
        }
    }
    
    
    /**
     * Подготовка на филтър формата
     *
     * @param doclog_Used $mvc
     * @param object      $data
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('createdOn', 'DESC');
    }
}
