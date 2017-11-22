<?php


/**
 * 
 * 
 * @category  bgerp
 * @package   support
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class support_TaskType extends core_Mvc
{
    
    
    /**
     * 
     */
    public $interfaces = 'cal_TaskTypeIntf';
    
    
    /**
     * 
     */
    public $title = 'Сигнал';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('typeId', 'key(mvc=support_IssueTypes, select=type)', 'caption=Тип, mandatory, width=100%, silent, after=title');
        $fieldset->FLD('componentId', 'key(mvc=support_Components,select=name,allowEmpty)', 'caption=Компонент, after=typeId, refreshForm, silent');
        $fieldset->FLD('systemId', 'key(mvc=support_Systems, select=name)', 'caption=Система, input=hidden, silent');
    }
    
    
    /**
     * Може ли вградения обект да се избере
     * 
     * @param NULL|integer $userId
     * 
     * @return boolean
     */
    public function canSelectDriver($userId = NULL)
    {
        
        return TRUE;
    }
    
    
    /**
     * Връща подсказките за добавяне на прогрес
     * 
     * @param  stdClass $tRec
     * 
     * @return array
     */
    public function getProgressSuggestions($tRec)
    {
        $progressArr = array();
        
        $progressArr['0 %'] = '0 %';
        $progressArr['10 %'] = 'Информация';
        $progressArr['40 %'] = 'Корекция';
        $progressArr['60 %'] = 'Превенция';
        $progressArr['80 %'] = 'Оценка';
        $progressArr['100 %'] = 'Резолюция';
        
        return $progressArr;
    }
    
    
    /**
     * 
     * 
     * @param support_TaskType $Driver
     * @param cal_Tasks $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($Driver, $mvc, &$res, $data)
    {
        $systemId = Request::get('systemId', 'key(mvc=support_Systems, select=name)');
        
        if (!$systemId && $data->form->rec->folderId) {
            $coverClassRec = doc_Folders::fetch($data->form->rec->folderId);
            
            if ($coverClassRec->coverClass && (cls::get($coverClassRec->coverClass) instanceof support_Systems)) {
                $systemId = $coverClassRec->coverId;
            }
        }
        
        // Ограничаваме избора на компоненти и типове, само до тези, които ги има в системата
        if ($systemId) {
            $allSystemsArr = array();
            if ($systemId) {
                $allSystemsArr = support_Systems::getSystems($systemId);
            }
            
            $componentsArr = array();
            $typesArr = array();
            
            if (!empty($allSystemsArr)) {
                $componentQuery = support_Components::getQuery();
                
                $componentQuery->orWhereArr('systemId', $allSystemsArr);
                
                if ($data->form->rec->componentId) {
                    $componentQuery->orWhere(array("#id = '[#1#]'", $data->form->rec->componentId));
                }
                
                while ($cRec = $componentQuery->fetch()) {
                    $componentsArr[$cRec->id] = $cRec->name;
                }
                
                $allowedTypesArr = support_Systems::getAllowedFieldsArr($allSystemsArr);
                
                if ($data->form->rec->typeId) {
                    $allowedTypesArr[$data->form->rec->typeId] = $data->form->rec->typeId;
                }
                
                foreach ($allowedTypesArr as $allowedType) {
                    $typesArr[$allowedType] = support_IssueTypes::fetchField($allowedType, 'type');
                }
            }
            
            if (!empty($componentsArr)) {
                $componentsArr = array_unique($componentsArr);
                asort($componentsArr);
            }
            $data->form->setOptions('componentId', $componentsArr);
            
            if (!empty($typesArr)) {
                $typesArr = array_unique($typesArr);
                asort($typesArr);
            }
            
            $data->form->setOptions('typeId', $typesArr);
        }
        
        if ($data->form->cmd == 'refresh') {
            // При избор на компонент, да са избрани споделените потребители, които са отговорници
            if ($data->form->rec->componentId) {
                $maintainers = support_Components::fetchField($data->form->rec->componentId, 'maintainers');
                $maintainers = keylist::removeKey($maintainers, core_Users::getCurrent());
                $data->form->setDefault('sharedUsers', $maintainers);
            }
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param support_TaskType $Driver
     * @param cal_Tasks $mvc
     * @param int $id първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_AfterSave($Driver, $mvc, &$id, $rec)
    {
        if ($rec->componentId) {
            support_Components::markAsUsed($rec->componentId);
        }
    }
    
    
    /**
     * Добавя допълнителни полетата в антетката
     *
     * @param support_TaskType $Driver
     * @param cal_Tasks $mvc
     * @param NULL|array $resArr
     * @param object $rec
     * @param object $row
     */
    public static function on_AfterGetFieldForLetterHead($Driver, $mvc, &$resArr, $rec, $row)
    {
        if ($row->systemId) {
            $resArr['systemId'] =  array('name' => tr('Система'), 'val' =>"[#systemId#]");
        }
        
        if ($row->componentId) {
            $resArr['componentId'] =  array('name' => tr('Компонент'), 'val' =>"[#componentId#]");
        }
        
        if ($row->typeId) {
            $resArr['typeId'] =  array('name' => tr('Тип'), 'val' =>"[#typeId#]");
        }
    }
}
