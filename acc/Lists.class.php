<?php


/**
 * Клас 'acc_Lists' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    acc
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class acc_Lists extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'acc_Wrapper, Items=acc_Items,plg_RowTools,plg_State2, plg_Sorting';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdmin = 'admin,acc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Номенклатури';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $currentTab = 'acc_Lists';
    
    
    /**
     * Инстанция на детайл-мениджъра на пера.
     *
     * @var acc_Items
     */
    var $Items;
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'num,nameLink=Наименование,regClassId,dimensional,itemsCnt,itemMaxNum,lastUseOn,tools=Пулт';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        // Трибуквен, уникален номер
        $this->FLD('num', 'int(3,size=3)', 'caption=Номер,remember=info,mandatory,notNull');
        
        // Име на номенклатурата
        $this->FLD('name', 'varchar', 'caption=Номенклатура,mandatory,remember=info,mandatory,notNull');
        
        // Регистър на бизнес обекти - източник на пера в тази номенклатура
        $this->FLD('regClassId', 'class(interface=intf_Register,select=info,allowEmpty)', 'caption=Регистър');
        
        // Дали перата в номенклатурата имат размерност (измерими ли са?). 
        // Например стоките и продуктите са измерими, докато контрагентите са не-измерими
        $this->FLD('dimensional', 'enum(no=Не,yes=Да)', 'caption=Измерима,remember,mandatory');
        
        // Колко пера има в тази номенклатура?
        $this->FLD('itemsCnt', 'int', 'caption=Пера->Брой,input=none');
        
        // Максимален номер използван за перата
        $this->FLD('itemMaxNum', 'int', 'caption=Пера->Макс. ном.,input=none');
        
        // Последно използване
        $this->FLD('lastUseOn', 'datetime', 'caption=Последно,input=none');
        
        // Състояние на номенклатурата
        $this->FLD('state', 'enum(active=Активна,closed=Затворена)', 'caption=Състояние,input=none');
        
        // Заглавие 
        $this->FNC('caption', 'html', 'column=none');
        
        // Титла - хипервръзка
        $this->FNC('nameLink', 'html', 'column=none');
        
        // Уникални индекси
        $this->setDbUnique('num');
        $this->setDbUnique('name');
    }
    
    
    /**
     * Изчислява полето 'caption', като конкатинира номера с името на номенклатурата
     */
    function on_CalcCaption($mvc, $rec)
    {
        $rec->caption = $mvc->getVerbal($rec, 'name') . "&nbsp;(" . $mvc->getVerbal($rec, 'num') . ")";
    }
    
    
    /**
     * Изчислява полето 'nameLink', като име с хипервръзка към перата от тази номенклатура
     */
    function on_CalcNameLink($mvc, $rec)
    {
        $name = $mvc->getVerbal($rec, 'name') ;
        
        $rec->nameLink = ht::createLink($name, array('acc_Items', 'list', 'listId' => $rec->id));
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function fetchByName($name)
    {
        return $this->fetch(array("#name = '[#1#]' COLLATE utf8_general_ci", $name));
    }
    
    
    /**
     * Изпълнява се преди запис на номенклатурата
     */
    function on_BeforeSave($mvc, $id, $rec)
    {
        if(!$rec->id) {
            $rec->itemCount = 0;
        }
    }
    
    
    /**
     *  Извиква се след изчисляването на необходимите роли за това действие
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL)
    {
        if ($action == 'delete') {
            if ($rec->id && !isset($rec->itemsCnt)) {
                $rec = $mvc->fetch($rec->id);
            }
            
            if ($rec->itemsCnt || $rec->lastUseOn) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовка на формата за редактиране
     */
    function on_AfterPrepareEditForm($mvc, $data)
    {
        if($data->form->rec->id && $data->form->rec->itemsCnt) {
            $data->form->setReadonly('regClassId');
            $data->form->setReadonly('dimensional');
        }
    }
    
    
    /**
     * Предизвикава обновяване на обобщената информация за
     * номенклатура с посоченото id
     */
    function updateSummary($id)
    {
        $rec = $this->fetch($id);
        
        $itemsQuery = $this->Items->getQuery();
        $itemsQuery->where("#state = 'active'");
        $itemsQuery->where("#listId = {$id}");
        $rec->itemsCnt = $itemsQuery->count();
        
        $itemsQuery->XPR('maxNum', 'int', 'max(#num)');
        
        $rec->itemMaxNum = $itemsQuery->fetch()->maxNum;
        
        $this->save($rec);
    }
    
    
    /**
     * Интелигентно изтриване на номенклатура
     */
    function deleteByGroup($groupId, $regClassId)
    {
        $rec = $mvc->fetch("#regClassId = {$regClassId} AND #groupId = {$groupId}");
        
        if(!$rec) return;
        
        $itemsQuery = $this->Items->getQuery();
        
        $isAnyItemInUse = FALSE;
        
        // Изтриваме или затваряме перата
        while($itemRec = $itemsQuery->fetch("#listId = {$rec->id}")) {
            if(!$itemRec->lastUseOn) {
                $this->Items->delete($itemRec->id);
            } else {
                $itemRec->state = 'closed';
                $this->Items->save($itemRec);
                $isAnyItemInUse = TRUE;
            }
        }
        
        // Изтриваме или затваряме номенклатурата
        if($rec->lastUseOn || $isAnyItemInUse) {
            $rec->state = 'closed';
            $rec->groupId = NULL;
            $this->save($rec);
        } else {
            $this->delete($rec->id);
        }
    }
    
    
    /**
     * Изпълнява се преди подготовката на показваните редове
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        $data->query->orderBy('num');
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function getRegisterInstance($rec)
    {
        $result = FALSE;
        
        if ($rec->regClassId) {
            $Classes = &cls::get('core_Classes');
            $regClassName = $Classes->fetchField($rec->regClassId, 'name');
            $result = &cls::get($regClassName);
            
            expect($result instanceof intf_Register);
        }
        
        return $result;
    }
    
    
    /**
     * Връща подгрупите, в които могат да се групират обектите от този списък.
     *
     * Обръща се към съответния регистър за да осигури информацията.
     *
     * @param StdClass $rec запис на модела @link acc_Lists
     * @return array асоциативен масив с индекс - специфичен за регистъра стринг, който
     *                     еднозначно определя групата и стойност - титлата на групата
     * @deprecated
     */
    function getGroupTypes($rec)
    {
        $result = FALSE;
        
        if ($register = $this->getRegisterInstance($rec)) {
            $result = $register->getGroupTypes();
        }
        
        return $result;
    }
    
    
    /**
     *
     * Enter description here ...
     * @param unknown_type $rec
     * @param unknown_type $groupType
     * @deprecated
     */
    function getGroups($rec, $groupType)
    {
        $result = FALSE;
        
        if ($register = $this->getRegisterInstance($rec)) {
            $result = $register->getGroups($groupType);
        }
        
        return $result;
    }
    
    
    /**
     * Enter description here ...
     * @param unknown_type $rec
     * @param unknown_type $groupType
     * @param unknown_type $groupId
     * @deprecated
     */
    function getGroupObjects($rec, $groupType, $groupId = NULL)
    {
        $result = FALSE;
        
        if ($register = $this->getRegisterInstance($rec)) {
            if ($result = $register->getGroupObjects($groupType, $groupId)) {
                foreach ($result as $groupId => &$keys) {
                    $keys = $this->Items->getItemsKeys($keys, $rec->id);
                }
            }
        }
        
        return $result;
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function getFeatures($rec)
    {
        $result = FALSE;
        
        if ($register = $this->getRegisterInstance($rec)) {
            $result = $register->getFeatures();
        }
        
        return $result;
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function getGroupOf($rec, $itemId, $featureId)
    {
        $featureValue = NULL;
        
        if ($register = $this->getRegisterInstance($rec)) {
            $featureObj = $register->features[$featureId];
            $objectId = $this->Items->fetchField($itemId, 'objectId');
            $featureValue = $featureObj->valueOf($objectId);
        }
        
        return $featureValue;
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function getItemsByGroup($rec, $featureId, $featureValue)
    {
        $ids = array();
        $flag = FALSE;
        
        if ($register = $this->getRegisterInstance($rec)) {
            $query = $register->getQuery();
            $query->EXT('objectId', 'acc_Items', 'externalName=objectId');
            $query->EXT('listId', 'acc_Items', 'externalName=listId');
            $query->EXT('itemId', 'acc_Items', 'externalName=id');
            $query->where("#objectId = #id");
            $query->where("#listId = {$rec->id}");
            
            $featureObj = $register->features[$featureId];
            
            $featureObj->prepareGroupQuery($featureValue, $query);
            
            while ($r = $query->fetch()) {
                $ids[] = $r->itemId;
            }
        }
        
        return $ids;
    }
}