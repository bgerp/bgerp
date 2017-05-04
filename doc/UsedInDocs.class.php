<?php


/**
 * Обекти, използвани в документите
 * 
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class doc_UsedInDocs extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = "Използвани обекти в документите";
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';
    
    
    /**
     * Кой може да добавя
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го редактира
     */
    public $canEdit = 'no_one';
    
    
    /**
     * 
     * @var array
     */
    protected $objectArr = array();
    
    
    /**
     * 
     */
    function description()
    {
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'caption=Контейнер, input=none');
        $this->FLD('data', 'blob(compress, serialize)', 'caption=Данни, input=none');
        $this->FLD('userId', 'key(mvc=core_Users)', 'caption=Потребител, input=none');
        $this->FLD('last', 'datetime', 'caption=Последно, input=none');
        
        $this->setDbUnique('userId, containerId');
    }
    
    
    /**
     * 
     * 
     * @param mixed $val
     * @param integer $cId
     * @param string $type
     */
    public static function addObject($val, $cId, $type)
    {
        $me = cls::get(get_called_class());
        
        $cu = core_Users::getCurrent();
        
        $me->objectArr[$cu][$cId][$type][] = $val;
    }
    
    
    /**
     * Добавяне контейнера към списъка с проверени, ако няма запис
     * При флъшване, ако няма запис - ще се изтрие
     * 
     * @param integer $cId
     */
    public static function addToChecked($cId)
    {
        $me = cls::get(get_called_class());
        
        $cu = core_Users::getCurrent();
        
        if (!isset($me->objectArr[$cu][$cId])) {
            $me->objectArr[$cu][$cId] = array();
        }
    }
    
    
    /**
     * 
     * 
     * @param integer $cId
     * @param integer|NULL $userId
     * @param NULL|string $type
     * 
     * @param string $type
     */
    public static function getObjectVals($cId, $userId, $type = NULL)
    {
        if (isset($userId)) {
            $where = array("#containerId = '[#1#]' AND #userId = '[#2#]'", $cId, (integer)$userId);
        } else {
            $where = array("#containerId = '[#1#]'", $cId);
        }
        
        $rec = self::fetch($where);
        
        if (!$rec) return ;
        
        if (!$type) {
            
            return $rec->data;
        } else {
            return $rec->data[$type];
        }
    }
    
    
    /**
     * Записва подадения генерирания масив с данни
     */
    public static function flushArr()
    {
        $me = cls::get(get_called_class());
        if (empty($me->objectArr)) return ;
        
        foreach ($me->objectArr as $userId => $cidDataArr) {
            if (!isset($cidDataArr)) continue;
        
            foreach ($cidDataArr as $cId => $dataArr) {
        
                $rec = self::fetch(array("#userId = '[#1#]' AND #containerId = '[#2#]'", $userId, $cId));
        
                if (!$rec) {
        
                    if (!$dataArr) continue;
        
                    $rec = new stdClass();
                } else {
                    if (!$dataArr && $rec->id) {
                        $me->delete($rec->id);
						
                        continue;
                    }
                }
        
                $rec->userId = $userId;
                $rec->data = $dataArr;
                $rec->containerId = $cId;
                $rec->last = dt::now();
        
                $me->save($rec);
            }
        }
        
        $me->objectArr = array();
    }
    
    
    /**
     * 
     * 
     * @param doc_UsedInDocs $mvc
     */
    public static function on_Shutdown($mvc)
    {
        $mvc->flushArr();
    }
    
    // TODO - изтриване на старите
}
