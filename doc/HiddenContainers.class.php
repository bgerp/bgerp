<?php 

/**
 *
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doc_HiddenContainers extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Скриване/показване на документи в нишка';
    
    
    /**
     * Кой има право да чете?
     */
    protected $canRead = 'admin, debug';
    
    
    /**
     * Кой има право да променя?
     */
    protected $canEdit = 'debug';
    
    
    /**
     * Кой има право да добавя?
     */
    protected $canAdd = 'no_one';
    
    
    /**
     * Кой може да го види?
     */
    protected $canView = 'admin, debug';
    
    
    /**
     * Кой може да го разглежда?
     */
    protected $canList = 'admin, debug';
    
    
    /**
     * Кой може да го изтрие?
     */
    protected $canDelete = 'admin, debug';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_State';
    
    
    protected static $hiddenDocsArr = array();
    
    
    public static $haveRecInModeOrDB = false;
    
    
    /**
     * Описание на модела
     */
    protected function description()
    {
        $this->FLD('userId', 'user', 'caption=Потребите');
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'caption=Контейнер');
        $this->FLD('state', 'enum(opened=Отворено, closed=Затворено)', 'caption=Състояние');
        $this->FLD('date', 'datetime(format=smartTime)', 'caption=Създаване, notNull, input=none');
        
        $this->setDbIndex('containerId,userId');
    }
    
    
    /**
     * Проверява кои документи ще се скриват и вдига съответния флаг
     *
     * @param array $recs
     */
    public static function prepareDocsForHide($containerRecsArr)
    {
        $cnt = countR((array) $containerRecsArr);
        
        if (self::checkCntLimitForShow($cnt)) {
            
            return ;
        }
        
        // Условия за скриване/показване
        $conf = core_Packs::getConfig('doc');
        $begin = $conf->DOC_SHOW_DOCUMENTS_BEGIN;
        $end = $cnt - $conf->DOC_SHOW_DOCUMENTS_BEGIN;
        $from = dt::subtractSecs($conf->DOC_SHOW_DOCUMENTS_LAST_ON);
        
        $i = 0;
        $kId = 0;
        
        $userId = core_Users::getCurrent();
        
        if (!$userId) {
            
            return ;
        }
        
        $firstCid = null;
        
        $cKeys = array_keys($containerRecsArr);
        
        foreach ((array) $containerRecsArr as $cId => $cRec) {
            $i++;
            
            $nextKey = $cKeys[++$kId];
            $nextRec = $containerRecsArr[$nextKey];
            
            if (!$firstCid && $cRec->threadId) {
                $firstCid = doc_Threads::getFirstContainerId($cRec->threadId);
            }
            
            // Първия, да не се скрива, и да не може да се скрива
            if ($cId && ($cId == $firstCid)) {
                continue;
            }
            
            $mName = self::getModeName($cId, $userId);
            $modeStatus = Mode::get($mName);
            $rec = false;
            
            if (!$modeStatus) {
                $rec = self::fetch("#containerId = '{$cId}' AND #userId = '{$userId}'");
            }
            
            $hide = true;
            
            // Ако е зададено да се показва в модела
            if ($rec || $modeStatus) {
                self::$haveRecInModeOrDB = true;
                if ($rec->state == 'opened' || $modeStatus == 'opened') {
                    $hide = false;
                }
            } else {
                if ($cId) {
                    $rec = doc_Containers::fetch($cId, 'docClass, docId');
                    if ($rec->docClass && cls::load($rec->docClass, true)) {
                        $dInst = cls::get($rec->docClass);
                    }
                }
                
                // Скриваме само оттеглени, затворение и активни документи
                // Документи, на които им е зададено да не се скриват автоматично и тя нама да се скриват
                if (((($cRec->state == 'closed') || ($cRec->state == 'active')) && ($dInst->autoHideDoc !== false)) || (($cRec->state == 'rejected'))) {
                    if ($cRec->state != 'rejected') {
                        
                        // Ако следващия документ е създаден от същия потребител
                        // Не е оттеглен и съдържанието и състоянието е идентично
                        // Скриваме текущия документ, ако не е бил показан изрично
                        if (($nextRec && $nextRec->state != 'rejected')
                            && ($nextRec->docClass == $cRec->docClass)
                            && ($cRec->createdBy == $nextRec->createdBy)
                            && ($cRec->state == $nextRec->state)
                            && (cls::load($cRec->docClass, true))) {
                            $clsInst = cls::get($cRec->docClass);
                            
                            $cContentHash = $clsInst->getDocContentHash($cRec->docId);
                            $nContentHash = $clsInst->getDocContentHash($nextRec->docId);
                            
                            if ($cContentHash == $nContentHash) {
                                self::$hiddenDocsArr[$cId] = true;
                                continue;
                            }
                        }
                        
                        // По новите от да не се показват
                        if ($cRec->modifiedOn > $from) {
                            $hide = false;
                        }
                        
                        // Първите да не се скриват
                        if ($begin >= $i) {
                            $hide = false;
                        }
                        
                        // Последните да не се скриват
                        if ($end < $i) {
                            $hide = false;
                        }
                    }
                } else {
                    $hide = false;
                }
                
                if ($dInst) {
                    $docId = $rec->docId;
                    if (!$docId && $cId) {
                        $docId = doc_Containers::fetchField($cId, 'docId');
                    }
                    
                    $docHiddenStatus = $dInst->getDocHiddenStatus($docId, $hide);
                    if (isset($docHiddenStatus)) {
                        $hide = $docHiddenStatus;
                    }
                }
            }
            
            self::$hiddenDocsArr[$cId] = $hide;
        }
    }
    
    
    /**
     *
     * @return bool|NULL
     */
    public static function isHidden($cId)
    {
        $q = Request::get('Q');
        if (isset($q)) {
            
            return false;
        }
        
        return self::$hiddenDocsArr[$cId];
    }
    
    
    /**
     * Помощна функция, която показва дали има скрити/показани документи
     *
     * @param bool|NULL $type
     *
     * @return bool
     */
    public static function haveHiddenOrShowedDoc($type = null)
    {
        if (empty(self::$hiddenDocsArr)) {
            
            return false;
        }
        
        if (!isset($type)) {
            
            return true;
        }
        
        if (array_search($type, self::$hiddenDocsArr)) {
            
            return true;
        }
        
        return false;
    }
    
    
    /**
     * Премахва записа за съответното действие
     *
     * @param int         $cId
     * @param string      $userId
     * @param NULL|string $state
     * @param bool        $forced
     */
    public static function removeFromTemp($cId, $userId = null, $state = 'opened', $forced = true)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        $name = self::getModeName($cId, $userId);
        
        $delFlag = true;
        if ($state) {
            $delFlag = false;
            $modeStatus = Mode::get($name);
            
            if ($modeStatus == 'opened') {
                $delFlag = true;
            }
        }
        
        if ($delFlag) {
            Mode::setPermanent($name, null);
        }
        
        if ($forced) {
            self::deleteFromDb($cId, $userId, $state);
        }
    }
    
    
    /**
     * Премахва записа за съответното действие от модела
     *
     * @param int         $cId
     * @param string      $userId
     * @param NULL|string $state
     */
    public static function deleteFromDb($cId, $userId = null, $state = 'opened')
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        if ($state) {
            
            return self::delete(array("#containerId = '[#1#]' AND #userId = '[#2#]' AND #state = '[#3#]'", $cId, $userId, $state));
        }
        
        return self::delete(array("#containerId = '[#1#]' AND #userId = '[#2#]'", $cId, $userId));
    }
    
    
    /**
     * Скрива/показва подадения документ
     *
     * @param int       $id
     * @param bool|NULL $hide
     * @param bool      $temp
     * @param NULL|int  $userId
     */
    public static function showOrHideDocument($cId, $hide = false, $temp = false, $userId = null)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        if ($userId < 1) {
            
            return ;
        }
        
        // Когато документа трябва да е отворен/затворен само временно
        $name = self::getModeName($cId, $userId);
        
        if ($temp) {
            if ($hide) {
                Mode::setPermanent($name, 'closed');
                self::$hiddenDocsArr[$cId] = true;
            } elseif ($hide === false) {
                Mode::setPermanent($name, 'opened');
                self::$hiddenDocsArr[$cId] = false;
            } else {
                Mode::setPermanent($name, null);
            }
        } else {
            
            // Изтрваме временните настройки за показване на документа
            Mode::setPermanent($name, null);
            
            $rec = self::fetch(("#containerId = '{$cId}' AND #userId = '{$userId}'"));
            
            if (!$rec) {
                $rec = new stdClass();
                $rec->userId = $userId;
                $rec->containerId = $cId;
            }
            
            $rec->date = dt::now();
            
            if ($hide) {
                $rec->state = 'closed';
                self::save($rec);
                self::$hiddenDocsArr[$cId] = true;
            } elseif ($hide === false) {
                $rec->state = 'opened';
                self::save($rec);
                self::$hiddenDocsArr[$cId] = false;
            } else {
                if ($rec->id) {
                    self::delete($rec->id);
                }
            }
        }
    }
    
    
    /**
     * Връща името за името на сесията
     *
     * @param int      $cId
     * @param int|NULL $userId
     *
     * @return string
     */
    protected static function getModeName($cId, $userId = null)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        $name = 'HIDE_CONTAINERS_' . $cId . '|' . $userId;
        
        return $name;
    }
    
    
    /**
     * Проверва дали има нужда да се скриват/показват документи
     *
     * @param int $cnt
     *
     * @return int
     */
    protected static function checkCntLimitForShow($cnt)
    {
        if ($cnt > 1) {
            
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Изтрива стари записи
     */
    public function cron_DeleteOldRecs()
    {
        $days = 30;
        $lastDate = dt::addDays(-1 * $days);
        
        $res = $this->delete(array("#date < '[#1#]'", $lastDate));
        
        if ($res) {
            $this->logNotice("Бяха изтрити {$res} записа");
            
            return "Бяха изтрити {$res} записа от " . $this->className;
        }
    }
}
