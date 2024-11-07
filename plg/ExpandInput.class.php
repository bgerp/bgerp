<?php


/**
 * Добавя поле, в което се записват изчислените стойности на полето, които идват от бащата
 *
 * @category  bgerp
 * @package   plg
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
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
      
        $mvc->setExpandInputField($mvc, $mvc->expandInputFieldName, $mvc->expandFieldName);

        setIfNot($mvc->forceExpandInputFieldOnExport, true);
        setIfNot($mvc->useExpandInputTypeOnExport, false);

        $expand36Name = $mvc->getExpandFieldName36();

        $mvc->setDbIndex($expand36Name, null, 'FULLTEXT');            

    }


    /**
     * След като е готово вербалното представяне
     */
    public static function on_AfterGetCsvFieldSetForExport($mvc, &$fieldset)
    {
        if ($mvc->forceExpandInputFieldOnExport) {
            if (isset($fieldset->fields[$mvc->expandFieldName]) && !isset($fieldset->fields[$mvc->expandInputFieldName])) {
                $fNameCaption = $fieldset->fields[$mvc->expandFieldName]->caption;
                if (!$mvc->useExpandInputTypeOnExport) {
                    $fieldset->fields[$mvc->expandInputFieldName] = $fieldset->fields[$mvc->expandFieldName];
                } else {
                    $fieldset->fields[$mvc->expandInputFieldName] = $mvc->getField($mvc->expandInputFieldName, false);
                }

                unset($fieldset->fields[$mvc->expandFieldName]);
                if (isset($fieldset->fields[$mvc->expandInputFieldName])) {
                    $fieldset->fields[$mvc->expandInputFieldName]->caption = $fNameCaption;
                }
            }
        }
    }


    /**
     * Създава поле, върху посочения фиелдсет, което има свойствата на оригиналното, но отговаря за въвеждането на групите
     */
    public static function on_AfterSetExpandInputField($mvc, $plugin, &$fieldset, $inputFieldName, $originalFieldName)
    {  
        // Създаваме ново инпут поле
        if (!$fieldset->getField($inputFieldName, false)) { 
            $expandField = $mvc->getField($originalFieldName);
            $pMvc = $expandField->type->params['mvc'];
            $select = $expandField->type->params['select'];
            $caption = 'caption=' . $expandField->caption;
            
            // Ако полето е мандатори
            if ($expandField->mandatory) {
                $caption .= ',mandatory';
            }
            
            if (BGERP_GIT_BRANCH == 'dev') {
                $fieldset->FLD($inputFieldName, "keylist(mvc={$pMvc}, select={$select}, parentId={$mvc->expandParentFieldName})", $caption);
            } else {
                $fieldset->FLD($inputFieldName, "treelist(mvc={$pMvc}, select={$select}, parentId={$mvc->expandParentFieldName})", $caption);
            }

            $expand36Name = $mvc->getExpandFieldName36();
            $fieldset->FLD($expand36Name, "text", 'input=none');
         
            $fieldset->setFieldTypeParams($inputFieldName, $expandField->type->params);

        }
    }
    
    
    /**
     * Изпълнява се преди запис на ред в таблицата
     *
     * @param core_Manager $mvc
     * @param NULL|int     $id
     * @param stdClass     $rec
     * @param string|NULL  $fields
     */
    public static function on_BeforeSave($mvc, &$id, &$rec, &$fields = null)
    {
        $mustSave = true;
        $expand36Name = $mvc->getExpandFieldName36();
        // Ако е подадено да се записва само едното поле, записваме и двете
        if (isset($fields)) {
            $fieldsArr = arr::make($fields, true);
            $mustSave = false;
            if ($fieldsArr[$mvc->expandFieldName] || $fieldsArr[$mvc->expandInputFieldName]) {
                $fieldsArr[$mvc->expandFieldName] = $mvc->expandFieldName;
                $fieldsArr[$mvc->expandInputFieldName] = $mvc->expandInputFieldName;
                $fieldsArr[$expand36Name] = $expand36Name;
                $fields = implode(',', $fieldsArr);
                $mustSave = true;
            }
        }
        
        if($mustSave) {

            // Вземаме всички въведени от потребителя стойност
            $inputArr = type_Keylist::toArray($rec->{$mvc->expandInputFieldName});
            
            // Намираме всички свъразани
            $resArr = $mvc->expandInput($inputArr);
            
            // Добавяме го към полето, което няма да се показва на потребителите, но ще се извличат данните от това поле
            $expandField = $mvc->getField($mvc->expandFieldName);
            $rec->{$mvc->expandFieldName} = $expandField->type->fromArray($resArr);
            $rec->{$expand36Name} = $expandField->type->fromArray36($resArr);
            
        }
    }


    /**
     * Връща името на полето, което съхранява разпънатия кейлист в 36 броичен формат
     */
    public static function on_BeforeGetExpandFieldName36($mvc, &$res)
    {
        $res = $mvc->expandFieldName . '36';

        return true;
    }
    
    
    /**
     * Намира бащата на подадените стойности
     *
     * @param core_Manager $mvc
     * @param array|NULL   $res
     * @param array        $inputArr
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
            if (!$rec) {
                continue;
            }
            
            if ($rec && !isset($res[$rec->id])) {
                $res[$rec->id] = $rec->id;
                $res += $mvc->expandInput($rec->{$mvc->expandParentFieldName});
            }
        }
    }


    /**
     * Изпълнява се след създаването на модела
     */
    public static function on_AfterSetupMVC($mvc, &$res)
    {
        if ($mvc->fixExpandFieldOnSetup === false) {

            return ;
        }

        $query = $mvc->getQuery();
        $query->where(array("#{$mvc->expandFieldName} IS NOT NULL"));
        $query->where(array("#{$mvc->expandFieldName} != ''"));

        $query->where(array("#{$mvc->expandInputFieldName} IS NULL"));
        $query->orWhere(array("#{$mvc->expandInputFieldName} = ''"));

        $cnt = 0;
        while ($rec = $query->fetch()) {
            $rec->{$mvc->expandInputFieldName} = $rec->{$mvc->expandFieldName};

            try {
                $mvc->save($rec, "{$mvc->expandInputFieldName}, {$mvc->expandFieldName}");

                $mvc->logNotice('Поправка на полетата групите', $rec->id);

                $cnt++;
            } catch (Exception $e) {
                reportException($e);
            }
        }

        if ($cnt) {
            $res .= '<li>Мигрирани данни: ' . $cnt;
        }
    }


    /**
     * Преизчисляване на разпънатите полета
     *
     * @param $mvc
     * @param $res
     * @param $id
     * @return void
     */
    public static function on_AfterRecalcExpandedInput($mvc, &$res, $id = null)
    {
        if(!isset($res)){
            core_Debug::startTimer('recalcExpandedInputs');

            // За кои записи
            $query = $mvc->getQuery();
            $query->show("id,{$mvc->expandInputFieldName},{$mvc->expandFieldName}");
            if(isset($id)){
                $query->where("#id = {$id}");
            }

            $count = 0;
            $updateRecs = array();
            while($rec = $query->fetch()){
                $count++;

                // За всеки запис се гледа има ли промени при разпънатите полета
                $inputArr = type_Keylist::toArray($rec->{$mvc->expandInputFieldName});
                $resArr = $mvc->expandInput($inputArr);
                $expandField = $mvc->getField($mvc->expandFieldName);
                $oldVal = $rec->{$mvc->expandFieldName};
                $newVal = $expandField->type->fromArray($resArr);
                if($oldVal != $newVal){
                    $rec->{$mvc->expandFieldName} = $newVal;
                    $updateRecs[] = $rec;
                }
            }

            // Ако има промени само те ще се запишат
            if(countR($updateRecs)){
                $mvc->saveArray($updateRecs, "id,{$mvc->expandFieldName},{$mvc->expandInputFieldName}}");
            }
            core_Debug::stopTimer('recalcExpandedInputs');
            core_Debug::log("{$mvc->className} Total {$count} : REGEN FIELDS: " . round(core_Debug::$timers['recalcExpandedInputs']->workingTime, 2));
        }
    }


    /**
     * Извиква се от core_CallOnTime
     *
     * @see core_CallOnTime
     *
     * @param int $userId
     */
    public static function callback_recalcExpandInput($mvc)
    {
        $mvc = cls::get($mvc);
        $mvc->recalcExpandedInput();
    }


    /**
     * Преизчисляване на полето за улеснено двоично търсене
     *
     * @param $mvc
     * @return void
     */
    public static function callback_recalcExpand36Input($data)
    {
        $res = array();
        $mvc = cls::get($data->mvc);
        $expand36Name = $mvc->getExpandFieldName36();
        core_App::setTimeLimit('300');

        $query = $mvc->getQuery();
        $query->where("#{$mvc->expandFieldName} IS NOT NULL");
        $query->orderBy('id', 'ASC');
        $query->limit('50000');
        if(isset($data->lastId)){
            $query->where("#id > {$data->lastId}");
        }

        $count = $query->count();
        if(empty($count)){
            $mvc->logDebug('Приключи миграцията на разширените полета за двоично търсене');
            return;
        }

        $lastId = null;
        $expandType = $mvc->getFieldType($mvc->expandFieldName);
        while($rec = $query->fetch()){
            $inputArr = type_Keylist::toArray($rec->{$mvc->expandFieldName});
            $rec->{$expand36Name} = $expandType->fromArray36($inputArr);
            $res[$rec->id] = (object)array('id' => $rec->id, $expand36Name => $rec->{$expand36Name});
            $lastId = $rec->id;
        }

        if(countR($res)){
            $mvc->saveArray($res, "id,{$expand36Name}");
        }

        if(!empty($lastId)){
            $callOn = dt::addSecs(60);
            $newData = (object)array('mvc' => $data->mvc, 'lastId' => $lastId);
            core_CallOnTime::setOnce('plg_ExpandInput', 'recalcExpand36Input', $newData, $callOn);
        }
    }


    /**
     * Помощна ф-я за по-бързо търсене в разширените полета
     *
     * @param mixed $mvc                 - модел
     * @param core_Query $query          - заявка, която да се модифицира
     * @param mixed $value               - стойности за търсене масив/кейлист/ид
     * @param string|null $externalKey   - поле на външния ключ, ако разширеното поле го няма в заявката
     * @return void
     */
    public static function applyExtendedInputSearch($mvc, &$query, $value, $externalKey = null)
    {
        $mvc = cls::get($mvc);
        $valueArr = is_array($value) ? $value : (keylist::isKeylist($value) ? keylist::toArray($value) : arr::make($value, true));
        $useFullTextSearch = bgerp_Setup::get('USE_FULLTEXT_GROUP_SEARCH');
        if($useFullTextSearch == 'no'){
            if(!isset($query->fields[$mvc->expandFieldName])){
                expect($externalKey);
                $query->EXT($mvc->expandFieldName, $mvc->className, "externalName={$mvc->expandFieldName},externalKey={$externalKey}");
            }
            $query->likeKeylist($mvc->expandFieldName, keylist::fromArray($valueArr));
            return;
        }

        $field36 = $mvc->getExpandFieldName36();
        if(!isset($query->fields[$field36])){
            expect($externalKey);
            $query->EXT($field36, $mvc->className, "externalName={$field36},externalKey={$externalKey}");
        }

        $values = core_Type::getByName('type_Keylist')->fromArray36($valueArr);
        $query->where("MATCH(#{$field36}) AGAINST('{$values}' IN BOOLEAN MODE)");
    }
}
