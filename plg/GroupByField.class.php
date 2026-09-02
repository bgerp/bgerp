<?php


/**
 * Клас 'plg_GroupByDate' - Плъгин за групиране на записите на модел по общо поле
 *
 * Трябва да е зададено ''. В таблицата на модела се добавя по един ред със стойността на това поле,
 * а всички записи които я имат са под нея, така имаме групирани записи.
 *
 *
 * @category  ef
 * @package   plg
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class plg_GroupByField extends core_Plugin
{
    /**
     *  Преди рендиране на лист таблицата
     */
    public static function on_BeforeRenderListTable($mvc, &$res, $data)
    {
        // Ако няма записи, не правим нищо
        if (!countR($data->recs)) {
            
            return;
        }
        if (!countR($data->rows)) {
            
            return;
        }
        
        $recs = &$data->recs;
        
        $field = $data->groupByField ?? ($mvc->groupByField ?? null);
        
        // Ако не е зададено поле за групиране, не правим нищо
        if (!$field) {
            
            return;
        }
        
        // Премахваме като колона полето, което ще групираме
        $originalFields = $data->listFields;
        unset($data->listFields[$field]);
        
        // Колко е броя на колоните
        $columns = countR($data->listFields);
        $groupByFieldStyles = $data->groupByFieldStyles ?? '';
        
        $groups = array();
        
        // Изчличаме в масив всички уникални стойностти на полето
        foreach ($recs as $index => $rec1) {
            $row = $data->rows[$index] ?? null;
            if (!is_object($row)) continue;

            // Полето може да е виртуално и да съществува само във вербалния ред
            $groupId = $rec1->{$field} ?? ($row->{$field} ?? null);
            $groupId = isset($groupId) ? $groupId : '';
            $groups[$groupId] = $row->{$field} ?? $groupId;
        }
        
        $rows = array();
        
        // За всяко поле за групиране
        foreach ($groups as $groupId => $groupVerbal) {
            $groupVerbal = $mvc->renderGroupName($data, $groupId, $groupVerbal);
            $rowAttr = array();
            
            // Създаваме по един ред с името му, разпънат в цялата таблица
            $rowAttr['class'] = 'group-by-field-row';
            
            if(array_key_exists($field, $originalFields)){
                $rows['|' . $groupId] = ht::createElement(
                    
                    'tr',
                    $rowAttr,
                    new ET("<td style='padding-top:9px;padding-left:5px;{$groupByFieldStyles}' colspan='{$columns}'>" . $groupVerbal . '</td>')
                    
                    );
            }
            
            // За всички записи
            foreach ($recs as $id => $rec) {
                $row = $data->rows[$id] ?? null;
                if (!is_object($row)) continue;
                $recGroupId = $rec->{$field} ?? ($row->{$field} ?? null);
                $recGroupId = isset($recGroupId) ? $recGroupId : '';
                
                // Ако стойността на полето им за групиране е същата като текущото
                if ($recGroupId == $groupId) {
                    
                    // Скриваме това поле от записа, и поставяме реда под групиращото поле
                    unset($row->{$field});
                    $rows[$id] = clone $row;

                    // Веднъж групирано, премахваме записа от старите записи
                    unset($data->rows[$id]);
                }
            }
        }
        
        $data->rows = $rows;
    }
    
    
    /**
     * След рендиране на името на групата
     *
     * @param core_Mvc $mvc             - модела
     * @param string   $res             - името на групата
     * @param stdClass $data            - датата
     * @param string   $groupName       - вътршното представяне на групата
     * @param string   $groupVerbalName - текущото вербално име на групата
     */
    public static function on_AfterRenderGroupName($mvc, &$res, $data, $groupName, $groupVerbalName)
    {
        if (!$res) {
            $res = isset($groupVerbalName) ? "<b>{$groupVerbalName}</b>" : '<div style="height:12px"></div>';
        }
    }
}
