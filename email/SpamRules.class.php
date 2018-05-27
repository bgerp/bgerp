<?php


/**
 * Потребителски правила за СПАМ рейтинг
 * 
 * @category  bgerp
 * @package   email
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_SpamRules extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_State2, email_Wrapper, plg_RowTools, plg_Clone';
    
    
    /**
     * Заглавие
     */
    var $title = "Потребителски правила за определяне на СПАМ рейтинг";
	
     
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = 'Правило за рутиране по СПАМ';
    
    
    /**
     * 
     */
    var $canList = 'admin, email';
    
    
    /**
     * 
     */
    var $canEdit = 'admin, email';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead   = 'admin';
    
    
    /**
     * Кой има право да пише?
     */
    var $canWrite  = 'admin';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'admin';
    
    
    /**  
     * Кой има право да променя системните данни?  
     */  
    var $canEditsysdata = 'admin';
    
    
    /**
     * 
     */
    var $listFields =  'id, email, subject, body, points, note, state, createdOn, createdBy';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'createdOn, createdBy, state, systemId';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('systemId' ,  'varchar(32)', 'caption=Ключ,input=none');
        $this->FLD('email' , 'varchar', 'caption=Условие->Изпращач', array('attr'=>array('style'=>'width: 350px;')));
        $this->FLD('subject' , 'varchar', 'caption=Условие->Относно', array('attr'=>array('style'=>'width: 350px;')));
        $this->FLD('body' , 'varchar', 'caption=Условие->Текст', array('attr'=>array('style'=>'width: 350px;')));
        $this->FLD('points' , 'double(min=-100, max=100, decimals=2, smartRound)', 'caption=Точки, mandatory');
        $this->FLD('note' , 'text', 'caption=@Забележка', array('attr'=>array('style'=>'width: 100%;', 'rows'=>4)));
        
        $this->setDbUnique('systemId');
    }
    
    
    /**
     * 
     * 
     * @param NULL|email_Mime $mime
     * @param NULL|stdClass $rec
     * 
     * @return double|NULL
     */
    public static function getSpamScore($mime, $rec)
    {
        if (!$mime && !$rec) return ;
        
        $sDataArr = array();
        if ($rec) {
            $sDataArr['body'] = $rec->textPart;
            $sDataArr['email'] = $rec->fromEml;
            $sDataArr['subject'] = $rec->subject;
        } elseif ($mime) {
            $sDataArr['body'] = $mime->textPart;
            $sDataArr['email'] = $mime->getFromEmail();
            $sDataArr['subject'] = $mime->getSubject();
        }
        
        if (empty($sDataArr)) return ;
        
        static $allFilters = NULL;
        
        if (!isset($allFilters)) {
            $query = static::getQuery();
            
            $query->where("#state = 'active'");
            
            // Зареждаме всички активни филтри
            $allFilters = $query->fetchAll();
        }
        
        if (!$allFilters) return ;
        
        $points = NULL;
        
        foreach ($allFilters as $filterRec) {
            if (email_Filters::match($sDataArr, $filterRec)) {
                
                $points += $filterRec->points;
            }
        }
        
        return $points;
    }
    
    
    /**
     *
     *
     * @param object $rec
     */
    public static function getSystemId($rec)
    {
        if ($rec->systemId) return $rec->systemId;
        
        $str = trim($rec->email) . '|' . trim($rec->subject) . '|' . trim($rec->body);
        $systemId = md5($str);
        
        return $systemId;
    }
    
    
    /**
     * Преди запис на документ, изчислява стойността на полето `isContable`
     *
     * @param email_Filters $mvc
     * @param stdClass $res
     * @param stdClass $rec
     * 
     * @return NULL|double
     */
    public static function on_BeforeSave($mvc, $res, $rec)
    {
        if (!$rec->systemId) {
            $rec->systemId = $mvc->getSystemId($rec);
        }
    }
}
