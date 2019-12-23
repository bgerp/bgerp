<?php


/**
 * Клас 'uiext_ObjectLabels'
 *
 * Мениджър за тагове на обектите
 *
 * @category  bgerp
 * @package   uiext
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class uiext_ObjectLabels extends core_Manager
{
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'uiext_DocumentLabels';
    
    
    /**
     * Заглавие
     */
    public $title = 'Тагове към редове от списък';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Таг на документ';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, cond_Wrapper,plg_Sorting';
    
    
    /**
     * Кой има право да гледа списъка?
     */
    public $canList = 'debug';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой може да избира етикет?
     */
    public $canSelectlabel = 'powerUser';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,objectId,hash,labels';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('classId', 'class', 'caption=Клас,mandatory');
        $this->FLD('objectId', 'int', 'caption=Ид,mandatory,tdClass=leftCol');
        
        $this->FLD('hash', 'varchar(32)', 'caption=Хеш,mandatory');
        $this->FLD('labels', 'keylist(mvc=uiext_Labels,select=title)', 'caption=Тагове,mandatory');
        
        $this->setDbUnique('classId,objectId,hash');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->objectId = cls::get($rec->classId)->getHyperlink($rec->objectId, true);
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'selectlabel' && isset($rec->classId)) {
            $Class = cls::get($rec->classId);
            if (!$Class->haveRightFor('single', $rec->objectId)) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Екшъм сменящ тага
     */
    public function act_saveLabels()
    {
        //core_Request::setProtected('containerId,hash');
        $masterClassId = Request::get('masterClassId', 'int');
        $objectId = Request::get('objectId', 'int');
        
        $hash = Request::get('hash', 'varchar');
        $classId = Request::get('classId', 'int');
        
        if (!$masterClassId || !$objectId || !$hash || !$classId) {
            core_Statuses::newStatus('|Невалиден ред|*!', 'error');
            
            return status_Messages::returnStatusesArray();
        }
        
        // Проверки
        $delete = false;
        $label = Request::get('label', 'int');
        if (!$label) {
            $delete = true;
        } else {
            if (!uiext_Labels::fetch($label)) {
                core_Statuses::newStatus('|Няма такъв таг|*!', 'error');
                
                return status_Messages::returnStatusesArray();
            }
        }
        
        // Подготовка на записа
        $rec = (object) array('classId' => $masterClassId, 'objectId' => $objectId, 'hash' => $hash);
        $rec->labels = keylist::addKey('', $label);
        if ($exRec = self::fetchByDoc($masterClassId, $objectId, $hash)) {
            $rec->id = $exRec->id;
        }
        
        if ($delete === true && isset($exRec->id)) {
            self::delete($exRec->id);
        } else {
            $this->save($rec);
        }
        
        if (Request::get('ajax_mode')) {
            
            // Заместваме клетката по AJAX за да визуализираме промяната
            $resObj = new stdClass();
            $resObj->func = 'html';
            
            $k = "{$masterClassId}|{$objectId}|{$classId}|{$hash}";
            $resObj->arg = array('id' => "charge{$k}", 'html' => uiext_Labels::renderLabel($masterClassId, $objectId, $classId, $hash), 'replace' => true);
            $res = array_merge(array($resObj));
            
            return $res;
        }
        
        $masterClass = cls::get($masterClassId);
        
        redirect($masterClass->getSingleUrlArray($objectId));
    }
    
    
    /**
     * Връща записа
     *
     * @param int $masterClassId
     * @param int $masterId
     * @param string $hash
     *
     * @return stdClass|FALSE
     */
    public static function fetchByDoc($masterClassId, $masterId, $hash)
    {
        return self::fetch(array("#classId = {$masterClassId} AND #objectId = {$masterId} AND #hash = '[#1#]'", $hash));
    }
}
