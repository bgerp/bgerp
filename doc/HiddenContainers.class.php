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
    public $title = "Адреси, на които не се изпращат циркулярни имейли";
    
    
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
        
        $this->setDbIndex('userId');
        $this->setDbIndex('containerId');
        $this->setDbIndex('state');
        
        $this->setDbUnique('userId, containerId, state');
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
        
        ksort($containerRecsArr, SORT_NUMERIC);
        
        // Условия за скриване/показване
        $conf = core_Packs::getConfig('doc');
        $begin = $conf->DOC_SHOW_DOCUMENTS_BEGIN;
        $end = $cnt - $conf->DOC_SHOW_DOCUMENTS_BEGIN;
        $from = dt::subtractSecs($conf->DOC_SHOW_DOCUMENTS_LAST_ON);
        
        $i = 0;
        
        $firstId = key($containerRecsArr);
        
        $userId = core_Users::getCurrent();
        
        if (!$userId) return ;
        
        foreach ((array)$containerRecsArr as $cId => $cRec) {
            $i++;
            
            // Първия, да не се скрива, и да не може да се скрива
            if ($cId == $firstId) continue;
            
            $rec = self::fetch("#containerId = '{$cId}' AND #userId = '{$userId}'");
            
            $hide = TRUE;
            
            // Ако е зададено да се показва в модела
            if ($rec) {
                if ($rec->state == 'opened') {
                    $hide = FALSE;
                }
            } else {
                
                if ($cRec->state != 'rejected') {
                    
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
        
        return self::$hiddenDocsArr[$cId];
    }
    
    
    /**
     * Скрива/показва подадения документ
     * 
     * @param integer $id
     * @param boolean|NULL $hide
     */
    public static function showOrHideDocument($cId, $hide = FALSE)
    {
        $userId = core_Users::getCurrent();
        
        if (!$userId) return ;
        
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
