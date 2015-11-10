<?php 


/**
 * Харесвания на документите
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_Likes extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = "Харесвания";
    
    
    /**
     * Кой има право да го променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'debug';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'debug';
    
    
    /**
     * Кой има право да изтрива?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'doc_Wrapper, plg_Created';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'caption = Контейнер');
        
        $this->setDbUnique('containerId, createdBy');
    }
    
    
    /**
     * Отбелязва докумена, като харесан
     * 
     * @param integer $cid
     * @param NULL|integer $userId
     * 
     * @return integer
     */
    public static function like($cid, $userId = NULL)
    {
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        $rec = new stdClass();
        $rec->containerId = $cid;
        $rec->createdBy = $userId;
        
        $savedId = self::save($rec, NULL, 'IGNORE');
        
        return $savedId;
    }
    
    
    /**
     * Премахва харесването
     * 
     * @param integer $cid
     * @param NULL|integer $userId
     * 
     * @return integer
     */
    public static function dislike($cid, $userId = NULL)
    {
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        $delCnt = self::delete(array("#containerId = '[#1#]' AND #createdBy = '[#2#]'", $cid, $userId));
        
        return $delCnt;
    }
    
    
    /**
     * Проверява дали има харесване за документа
     * 
     * @param integer $cid
     * @param integer $userId
     * 
     * @return boolean
     */
    public static function isLiked($cid, $userId = NULL)
    {
        if ($userId) {
            $id = self::fetchField(array("#containerId = '[#1#]' AND #createdBy = '[#2#]'", $cid, $userId), 'id');
        } else {
            $id = self::fetchField(array("#containerId = '[#1#]'", $cid), 'id');
        }
        
        return (boolean) $id;
    }
    
    
    /**
     * Връща всички харесвания
     * 
     * @param integer $cid
     * 
     * @return array
     */
    public static function getLikedArr($cid)
    {
        $query = self::getQuery();
        $query->where(array("#containerId = [#1#]", $cid));
        $query->orderBy('createdOn', 'ASC');
        $resArr = $query->fetchAll();
        
        return $resArr;
    }
}
