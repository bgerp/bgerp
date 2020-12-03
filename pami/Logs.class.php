<?php


/**
 * 
 *
 * @category  bgerp
 * @package   pami
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pami_Logs extends core_Manager
{
    /**
     * Заглавие на модела
     */
    public $title = 'Логове';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'pami, ceo, admin';
    
    
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
    public $canView = 'pami, ceo, admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'pami, ceo, admin';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * 
     */
    public $searchFields = 'log, createdOn';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'pami_Wrapper, plg_Created, plg_Search';
    
    
    /**
     * 
     * @var integer
     */
    public $listItemsPerPage = 100;
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('log', 'blob(compress, serialize, hideLevel=5)', 'caption=Лог');
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    public static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('createdOn', 'DESC');
        $data->query->orderBy('id', 'DESC');
        
        $data->listFilter->showFields = 'search';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    }
    
    
    /**
     * Крон метод за изтриване на стари записи
     */
    public function cron_deleteOldRecords()
    {
        if ($numRows = self::delete(array("#createdOn < '[#1#]'", dt::addDays(-1 * pami_Setup::get('LOG_KEEP_DAYS'))))) {
            
            $this->logInfo("Изтрити изтекли записи: {$numRows}");
        }
        
        return $info;
    }
    
    
    /**
     * Екшън за записване на логовете
     */
    function act_Log()
    {
        $key = Request::get('k');
        
        if ($key != pami_Setup::get('LOG_KEY')) {
            $this->logWarning('Грешен ключ');
            
            shutdown();
        }
        
        $log = @unserialize(gzuncompress(base64_decode(Request::get('data'))));
        
        if (!$log) {
            wp(Request::get('data'));
            
            shutdown();
        }
        
        try {
            $lArr = explode('\\',$log['__clsName']);
            $fncName = 'pami' . end($lArr);
            if (method_exists($this, $fncName)) {
                $this->{$fncName}($log);
            }
        } catch (Exception $e) {
            reportException($e);
        } catch (Throwable $t) {
            reportException($t);
        }
        
        if (pami_Setup::get('SAVE_TO_LOG') == 'yes') {
            $rec = new stdClass();
            $rec->log = $log;
            
            if ($rec->log !== false) {
                $this->save($rec);
            }
        }
        
        shutdown();
    }
    
    
    /**
     * Прихваща DialEvent от PAMI
     * 
     * @param array $log
     */
    protected function pamiDialEvent($log)
    {
        foreach ($log as $k => $v) {
            $k = strtolower($k);
            if (strpos($k, 'keys') !== false) {
                $subEvent = strtolower($v['subevent']);
                $dialStatus = strtolower($v['dialstatus']);
                $fromNum = strtolower($v['calleridnum']);
                $toNum = strtolower($v['dialstring']);
                $uniqId = $v['uniqueid'];
                $destUniqId = $v['destuniqueid'];
            }
            
            if (strpos($k, 'createddate') !== false) {
                $createdDate = strtolower($v);
            }
        }
        
        if ($subEvent == 'begin') {
            if (strpos($toNum, '/') !== false) {
                list(,$toNum) = explode('/', $toNum);
            }
            
            if (strpos($toNum, '@') !== false) {
                list($toNum) = explode('@', $toNum);
            }
            
            callcenter_Talks::registerBeginCall($uniqId, $fromNum, $toNum, $createdDate, null, $destUniqId);
        }
        
        if ($subEvent == 'end') {
            callcenter_Talks::registerEndCall($uniqId, $dialStatus, $createdDate);
        }
        
    }
    
    
    /**
     * Прихваща NewstateEvent от PAMI
     *
     * @param array $log
     */
    protected function pamiNewstateEvent($log)
    {
        $uniqId = null;
        foreach ($log as $k => $v) {
            $k = strtolower($k);
            
            if (strpos($k, 'keys') !== false) {
                $subEvent = strtolower($v['channelstatedesc']);
                
                if ($subEvent != 'up') {
                    
                    continue;
                }
                
                $uniqId = $v['uniqueid'];
            }
            
            if (strpos($k, 'createddate') !== false) {
                $createdDate = strtolower($v);
            }
        }
        
        if ($uniqId) {
            callcenter_Talks::registerAnswer($uniqId, $createdDate);
        }
    }
    
    
    /**
     * Прихваща BridgeEvent от PAMI
     *
     * @param array $log
     */
    protected function pamiBridgeEvent($log)
    {
        $uniqId1 = $uniqId2 = null;
        foreach ($log as $k => $v) {
            $k = strtolower($k);
            
            if (strpos($k, 'keys') !== false) {
                $bridgeState = strtolower($v['bridgestate']);
                
                if ($bridgeState != 'link') {
                    
                    continue;
                }
                
                $uniqId1 = $v['uniqueid1'];
                $uniqId2 = $v['uniqueid2'];
            }
            
            if (strpos($k, 'createddate') !== false) {
                $createdDate = strtolower($v);
            }
        }
        
        if ($uniqId1 && $uniqId2) {
            callcenter_Talks::registerBridge($uniqId1, $uniqId2);
        }
    }
}
