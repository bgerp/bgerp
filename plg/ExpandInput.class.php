<?php


/**
 * Добавя поле, в което се записват изчислените стойности на полето, които идват от бащата
 *
 * @category  bgerp
 * @package   plg
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class plg_ExpandInput extends core_Plugin
{
    
    
	/**
     * Извиква се след описанието на модела
     *
	 * @param core_Manager $mvc
	 */
    public static function on_AfterDescription(&$mvc)
    {
        // Име на полето, в което ще се записват всички
        setIfNot($mvc->expandFieldName, 'expand');
        
        // Име на полето, което ще се инпутва
        setIfNot($mvc->expandInputFieldName, $mvc->expandFieldName . 'Input');
        
        // Име на полето, към предшественика на полето
        setIfNot($mvc->expandParentFieldName, 'parentId');
        
        // Скриваме полето
        $expandField = $mvc->getField($mvc->expandFieldName);
        $mvc->setParams($mvc->expandFieldName, array('input' => 'none'));
        
        // Създаваме ново инпут поле
        if (!$mvc->getField($mvc->expandInputFieldName, FALSE)) {
            $pMvc = $expandField->type->params['mvc'];
            $select = $expandField->type->params['select'];
            $caption = 'caption=' . $expandField->caption;
            
            // Ако полето е мандатори
            if ($expandField->mandatory) {
                $caption .= ',mandatory';
            }
            
            $mvc->FLD($mvc->expandInputFieldName, "keylist(mvc={$pMvc}, select={$select})", $caption);
        }
        
        // Вземаме параметрите, от групата, която няма да се показва
        $eParams = $mvc->fields[$mvc->expandFieldName]->type->params;
        if ($eParams) {
            $iParams = $mvc->fields[$mvc->expandInputFieldName]->type->params;
            if (!is_array($iParams)) {
                $iParams = array();
            }
            $mvc->fields[$mvc->expandInputFieldName]->type->params = $iParams + $eParams;
        }
    }
    
    
    /**
     * Изпълнява се преди запис на ред в таблицата
     * 
     * @param core_Manager $mvc
     * @param NULL|integer $id
     * @param stdObject $rec
     * @param string|NULL $fields
     */
    static function on_BeforeSave($mvc, &$id, &$rec, &$fields = NULL)
    {
        // Ако е подадено да се записва само едното поле, записваме и двете
        if (isset($fields)) {
            $fieldsArr = arr::make($fields, TRUE);
        
            if ($fieldsArr[$mvc->expandFieldName] || $fieldsArr[$mvc->expandInputFieldName]) {
                $fieldsArr[$mvc->expandFieldName] = $mvc->expandFieldName;
                $fieldsArr[$mvc->expandInputFieldName] = $mvc->expandInputFieldName;
                $fields = implode(',', $fieldsArr);
            }
        }
        
        // Вземаме всички въведени от потребителя стойност
        $inputArr = type_Keylist::toArray($rec->{$mvc->expandInputFieldName});
        
        // Намираме всички свъразани
        $resArr = $mvc->expandInput($inputArr);
        
        // Добавяме го към полето, което няма да се показва на потребителите, но ще се извличат данните от това поле
        $expandField = $mvc->getField($mvc->expandFieldName);
        $rec->{$mvc->expandFieldName} = $expandField->type->fromArray($resArr);
    }
    
    
    /**
     * Намира бащата на подадените стойности
     * 
     * @param core_Manager $mvc
     * @param array|NULL $res
     * @param array $inputArr
     */
    public static function on_AfterExpandInput($mvc, &$res, $inputArr)
    {
        if (is_null($res)) {
            $res = array();
        }
        
        if (!is_array($inputArr)) {
            $inputArr = keylist::toArray($inputArr);
        }
        
        $inputField = $mvc->getField($mvc->expandInputFieldName);
        
        $iMvc = $inputField->type->params['mvc'];
        
        $inputInst = cls::get($iMvc);
        
        foreach ($inputArr as $inputId) {
            if (is_object($inputId)) {
                $rec = $inputId;
            } elseif (is_numeric($inputId)) {
                $rec = $inputInst->fetch($inputId);
            } else {
                $select = $inputInst->type->params['select'];
                $rec = $inputInst->fetch(array("#role = '[#1#]'", $select));
            }
    
            // Прескачаме несъсществуващите записи
            if(!$rec) continue;
            
            if ($rec && !isset($res[$rec->id])) {
                $res[$rec->id] = $rec->id;
                $res += $mvc->expandInput($rec->{$mvc->expandParentFieldName});
            }
        }
    }
    
    
	/**
     * Изпълнява се след създаването на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        $query = $mvc->getQuery();
        $query->where(array("#{$mvc->expandFieldName} IS NOT NULL"));
        $query->where(array("#{$mvc->expandFieldName} != ''"));
        
        $query->where(array("#{$mvc->expandInputFieldName} IS NULL"));
        $query->orWhere(array("#{$mvc->expandInputFieldName} = ''"));
        
        $cnt = 0;
        while ($rec = $query->fetch()) {
            
            $rec->{$mvc->expandInputFieldName} = $rec->{$mvc->expandFieldName};
            
            $mvc->save($rec, "{$mvc->expandInputFieldName}, {$mvc->expandFieldName}");
            $cnt++;
        }
        
        if ($cnt) {
            $res .= "<li>Мигрирани данни: " . $cnt;
        }
    }
}
