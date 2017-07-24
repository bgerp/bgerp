<?php 


/**
 * 
 * 
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_HiddenContainers extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = "Скриване/показване на документи в нишка";
    
    
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
    
    
    /**
     * 
     */
    protected static $hiddenDocsArr = array();
    
    
    /**
     * Описание на модела
     */
    protected function description()
    {
        $this->FLD('userId', 'user', 'caption=Потребите');
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'caption=Контейнер');
        $this->FLD('state', 'enum(opened=Отворено, closed=Затворено)', 'caption=Състояние');
        
        $this->setDbIndex('containerId,userId');
    }
    

    
    
    /**
     * Проверява кои документи ще се скриват и вдига съответния флаг
     * 
     * @param array $recs
     */
    public static function prepareDocsForHide($containerRecsArr)
    {
        $cnt = count((array)$containerRecsArr);
        
        if (self::checkCntLimitForShow($cnt)) return ;
        
        // Условия за скриване/показване
        $conf = core_Packs::getConfig('doc');
        $begin = $conf->DOC_SHOW_DOCUMENTS_BEGIN;
        $end = $cnt - $conf->DOC_SHOW_DOCUMENTS_BEGIN;
        $from = dt::subtractSecs($conf->DOC_SHOW_DOCUMENTS_LAST_ON);
        
        $i = 0;
        $kId = 0;
        
        $userId = core_Users::getCurrent();
        
        if (!$userId) return ;
        
        $firstCid = NULL;
        
        $cKeys = array_keys($containerRecsArr);
        
        foreach ((array)$containerRecsArr as $cId => $cRec) {
            $i++;
            
            $nextKey = $cKeys[++$kId];
            $nextRec = $containerRecsArr[$nextKey];
            
            if (!$firstCid && $cRec->threadId) {
                $firstCid = doc_Threads::getFirstContainerId($cRec->threadId);
            }
            
            // Първия, да не се скрива, и да не може да се скрива
            if ($cId && ($cId == $firstCid)) continue;
            
            $mName = self::getModeName($cId, $userId);
            $modeStatus = Mode::get($mName);
            $rec = FALSE;
            
            if (!$modeStatus) {
                $rec = self::fetch("#containerId = '{$cId}' AND #userId = '{$userId}'");
            }
            
            $hide = TRUE;
            
            // Ако е зададено да се показва в модела
            if ($rec || $modeStatus) {
                
                if ($rec->state == 'opened' || $modeStatus == 'opened') {
                    $hide = FALSE;
                }
            } else {
                
                if ($cId) {
                    $rec = doc_Containers::fetch($cId, 'docClass');
                    if ($rec->docClass && cls::load($rec->docClass, TRUE)) {
                        $dInst = cls::get($rec->docClass);
                    }
                }
                
                // Скриваме само оттеглени, затворение и активни документи
                // Документи, на които им е зададено да не се скриват автоматично и тя нама да се скриват
                if ((($cRec->state == 'rejected') || ($cRec->state == 'closed') || ($cRec->state == 'active')) && ($dInst->autoHideDoc !== FALSE)) {
                    if ($cRec->state != 'rejected') {
                        
                        // Ако следващия документ е създаден от същия потребител
                        // Не е оттеглен и съдържанието и състоянието е идентично
                        // Скриваме текущия документ, ако не е бил показан изрично
                        if (($nextRec && $nextRec->state != 'rejected') 
                            && ($nextRec->docClass == $cRec->docClass)
                            && ($cRec->createdBy == $nextRec->createdBy)
                            && ($cRec->state == $nextRec->state)
                            && (cls::load($cRec->docClass, TRUE))) {
                                $clsInst = cls::get($cRec->docClass);
                                
                                $cContentHash = $clsInst->getDocContentHash($cRec->docId);
                                $nContentHash = $clsInst->getDocContentHash($nextRec->docId);
                                
                                if ($cContentHash == $nContentHash) {
                                    self::$hiddenDocsArr[$cId] = TRUE;
                                    continue;
                                }
                        }
                        
                        // По новите от да не се показват
                        if ($cRec->modifiedOn > $from) {
                            $hide = FALSE;
                        }
                        
                        // Първите да не се скриват
                        if ($begin >= $i) {
                            $hide = FALSE;
                        }
                        
                        // Последните да не се скриват
                        if ($end < $i) {
                            $hide = FALSE;
                        }
                    }
                } else {
                    $hide = FALSE;
                }
            }
            
            self::$hiddenDocsArr[$cId] = $hide;
        }
    }
    
    
    /**
     * 
     * @return boolean|NULL
     */
    public static function isHidden($cId)
    {
        $q = Request::get('Q');
        if (isset($q)) return FALSE;
        
        return self::$hiddenDocsArr[$cId];
    }
    
    
    /**
     * Скрива/показва подадения документ
     * 
     * @param integer $id
     * @param boolean|NULL $hide
     * @param boolean $temp
     * @param NULL|integer $userId
     */
    public static function showOrHideDocument($cId, $hide = FALSE, $temp = FALSE, $userId = NULL)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        if ($userId < 1) return ;
        
        // Когато документа трябва да е отворен/затворен само временно
        $name = self::getModeName($cId, $userId);
        
        if ($temp) {
            if ($hide) {
                Mode::setPermanent($name, 'closed');
                self::$hiddenDocsArr[$cId] = TRUE;
            } elseif ($hide === FALSE) {
                Mode::setPermanent($name, 'opened');
                self::$hiddenDocsArr[$cId] = FALSE;
            } else {
                Mode::setPermanent($name, NULL);
            }
        } else {
            
            // Изтрваме временните настройки за показване на документа
            Mode::setPermanent($name, NULL);
            
            $rec = self::fetch(("#containerId = '{$cId}' AND #userId = '{$userId}'"));
            
            if (!$rec) {
                $rec = new stdClass();
                $rec->userId = $userId;
                $rec->containerId = $cId;
            }
            
            if ($hide) {
                $rec->state = 'closed';
                self::save($rec);
                self::$hiddenDocsArr[$cId] = TRUE;
            } elseif ($hide === FALSE) {
                $rec->state = 'opened';
                self::save($rec);
                self::$hiddenDocsArr[$cId] = FALSE;
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
     * @param integer $cId
     * @param integer|NULL $userId
     * 
     * @return string
     */
    protected static function getModeName($cId, $userId = NULL)
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
     * @param integer $cnt
     * 
     * @return integer
     */
    protected static function checkCntLimitForShow($cnt)
    {
        if ($cnt > 1) return FALSE;
        
        return TRUE;
    }
}
