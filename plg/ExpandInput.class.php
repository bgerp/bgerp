<?php


/**
 * Добавя поле, в което се записват изчислените стойности на полето, които идват от бащата
 *
 * @category  bgerp
 * @package   plg
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
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
        setPartIfNot($mvc, 'expandFieldName', 'expand');
        
        // Име на полето, което ще се инпутва
        setPartIfNot($mvc, 'expandInputFieldName', $mvc->expandFieldName . 'Input');
        
        // Име на полето, към предшественика на полето
        setPartIfNot($mvc, 'expandParentFieldName', 'parentId');
        
        // Входното поле наследява видимостта преди скриването на разпънатото.
        $mvc->setExpandInputField($mvc, $mvc->expandInputFieldName, $mvc->expandFieldName);
        $mvc->setParams($mvc->expandFieldName, array('input' => 'none'));

        setPartIfNot($mvc, 'forceExpandInputFieldOnExport', true);
        setPartIfNot($mvc, 'useExpandInputTypeOnExport', false);

        $expand36Name = $mvc->getExpandFieldName36();

        $mvc->setDbIndex($expand36Name, null, 'FULLTEXT');            

    }


    /**
     * Старите групи са достъпни и във формата, преди фоновото попълване на ново входно поле.
     */
    public static function on_AfterRead($mvc, &$rec)
    {
        $inputName = $mvc->expandInputFieldName ?? 'expandInput';
        $expandName = $mvc->expandFieldName ?? 'expand';
        if (property_exists($rec, $inputName) && self::needsLegacyInput($mvc, $rec->{$inputName}, $rec->{$expandName} ?? null)) {
            $rec->{$inputName} = $rec->{$expandName};
        }
    }


    /**
     * След като е готово вербалното представяне
     */
    public static function on_AfterGetCsvFieldSetForExport($mvc, &$fieldset)
    {
        unset($fieldset->fields[$mvc->getExpandFieldName36()]);
        $expandName = $mvc->expandFieldName ?? 'expand';
        $inputName = $mvc->expandInputFieldName ?? ($expandName . 'Input');
        if ($mvc->forceExpandInputFieldOnExport ?? true) {
            if (isset($fieldset->fields[$expandName]) && !isset($fieldset->fields[$inputName])) {
                $fNameCaption = $fieldset->fields[$expandName]->caption ?? '';
                if (empty($mvc->useExpandInputTypeOnExport)) {
                    $fieldset->fields[$inputName] = $fieldset->fields[$expandName];
                } else {
                    $fieldset->fields[$inputName] = $mvc->getField($inputName, false);
                }

                unset($fieldset->fields[$expandName]);
                if (isset($fieldset->fields[$inputName])) {
                    $fieldset->fields[$inputName]->caption = $fNameCaption;
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
            $sourceField = $mvc->getField($inputFieldName, false) ?: $expandField;
            $pMvc = $sourceField->type->params['mvc'] ?? '';
            $select = $sourceField->type->params['select'] ?? '';
            $caption = 'caption=' . ($sourceField->caption ?? '');
            
            // Ако полето е мандатори
            if ($sourceField->mandatory ?? null) {
                $caption .= ',mandatory';
            }
            
            $parentId = $mvc->expandParentFieldName ?? 'parentId';
            $type = $sourceField === $expandField ? "keylist(mvc={$pMvc}, select={$select}, parentId={$parentId})" : clone $sourceField->type;
            $fieldset->FLD($inputFieldName, $type, $caption);
            $fieldset->setFieldTypeParams($inputFieldName, $sourceField->type->params ?? array());

            // При отделна форма се използва вече описаното входно поле на модела.
            foreach (array('input', 'single', 'column', 'readOnly') as $param) {
                if (isset($sourceField->{$param})) {
                    $fieldset->setParams($inputFieldName, array($param => $sourceField->{$param}));
                }
            }
        }

        $expand36Name = $mvc->getExpandFieldName36();
        if (!$fieldset->getField($expand36Name, false)) {
            $fieldset->FLD($expand36Name, 'text', 'input=none,column=none,single=none');
        } else {
            $fieldset->setParams($expand36Name, array('input' => 'none', 'column' => 'none', 'single' => 'none'));
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
        $expandName = $mvc->expandFieldName ?? 'expand';
        $inputFieldName = $mvc->expandInputFieldName ?? ($expandName . 'Input');
        $expand36Name = $mvc->getExpandFieldName36();
        // Ако е подадено да се записва само едното поле, записваме и двете
        if (isset($fields)) {
            $fieldsArr = arr::make($fields, true);
            $mustSave = false;
            if (!empty($fieldsArr[$expandName]) || !empty($fieldsArr[$inputFieldName])) {
                $fieldsArr[$expandName] = $expandName;
                $fieldsArr[$inputFieldName] = $inputFieldName;
                $fieldsArr[$expand36Name] = $expand36Name;
                $fields = implode(',', array_keys($fieldsArr));
                $mustSave = true;
            }
        }
        
        if($mustSave) {
            // При програмни записи input полето може да не е заредено
            if (!property_exists($rec, $inputFieldName)) {
                if (!empty($rec->id)) {
                    $rec->{$inputFieldName} = $mvc->fetchField($rec->id, $inputFieldName, false);
                    // Новодобавеното входно поле може още да чака фоновото попълване.
                    if (empty($rec->{$inputFieldName}) && ($mvc->fixExpandFieldOnSetup ?? true) !== false) {
                        $expanded = $rec->{$expandName} ?? $mvc->fetchField($rec->id, $expandName, false);
                        if (self::needsLegacyInput($mvc, $rec->{$inputFieldName}, $expanded)) $rec->{$inputFieldName} = $expanded;
                    }
                } else {
                    $rec->{$inputFieldName} = $rec->{$expandName} ?? null;
                }
            }

            // Вземаме всички въведени от потребителя стойност
            $inputArr = type_Keylist::toArray($rec->{$inputFieldName});
            
            // Намираме всички свъразани
            $resArr = $mvc->expandInput($inputArr);
            
            // Добавяме го към полето, което няма да се показва на потребителите, но ще се извличат данните от това поле
            $expandField = $mvc->getField($expandName);
            $rec->{$expandName} = $expandField->type->fromArray($resArr);
            $rec->{$expand36Name} = $expandField->type->fromArray36($resArr);
            
        }
    }


    /**
     * Синхронизира индекса след всички BeforeSave обработчици и при директен save_().
     */
    public static function on_BeforeSaveFields($mvc, &$rec, &$fields)
    {
        $expandName = $mvc->expandFieldName ?? 'expand';
        if (!isset($fields[$expandName])) {
            return;
        }

        $expand36Name = $mvc->getExpandFieldName36();
        $field = $mvc->getField($expandName);
        $expanded = $rec->{$expandName} ?? null;
        // toMysql заменя NULL с default при NOT NULL поле.
        if ($expanded === null && !empty($field->notNull)) $expanded = $field->value ?? '';
        $rec->{$expand36Name} = $expanded === null ? null : $field->type->fromArray36(type_Keylist::toArray($expanded));
        $fields[$expand36Name] = $expand36Name;
    }


    /**
     * Масовият запис запазва същата зависимост, без да изпълнява бизнес Save обработчици.
     */
    public static function on_BeforeSaveArrayFields($mvc, &$recs, &$fields)
    {
        $expandName = $mvc->expandFieldName ?? 'expand';
        if (countR($fields) && empty($fields[$expandName])) {
            return;
        }

        $allFields = !countR($fields);
        foreach ($recs as $rec) {
            $recFields = array($expandName => $expandName);
            self::on_BeforeSaveFields($mvc, $rec, $recFields);
        }
        if (!$allFields) {
            $expand36Name = $mvc->getExpandFieldName36();
            $fields[$expand36Name] = $expand36Name;
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
        if (self::scheduleRebuild($mvc)) {
            $res .= '<li>Планирано фоново попълване на разширените полета';
        }
    }


    /**
     * Състоянието е отделно за всеки модел и набор полета, включително новодобавени.
     */
    private static function getSetupKey($mvc)
    {
        return 'ExpandInput1|' . $mvc->className . '|' . ($mvc->dbTableName ?? '') . '|' .
            ($mvc->expandInputFieldName ?? '') . '|' . ($mvc->expandFieldName ?? '') . '|' . $mvc->getExpandFieldName36();
    }


    /**
     * Проверява готовността веднъж за модела в текущата заявка.
     */
    private static function isIndexReady($mvc)
    {
        if (!isset($mvc->_expandInput36Ready)) {
            $mvc->_expandInput36Ready = core_Permanent::get(self::getSetupKey($mvc)) === 'done';
        }

        return $mvc->_expandInput36Ready;
    }


    /**
     * Ново NOT NULL входно поле може да има празен низ вместо NULL при старите записи.
     */
    private static function needsLegacyInput($mvc, $input, $expanded)
    {
        if (($mvc->fixExpandFieldOnSetup ?? true) === false || empty($expanded)) return false;

        return $input === null || ($input === '' && !self::isIndexReady($mvc));
    }


    /**
     * Инициализацията не обхожда таблицата; незавършена обработка продължава от последния id.
     */
    public static function scheduleRebuild($mvc)
    {
        $mvc = cls::get($mvc);
        $setupKey = self::getSetupKey($mvc);
        $state = core_Permanent::get($setupKey);
        if ($state === 'done') {
            return false;
        }

        $data = (object) array('mvc' => $mvc->className, 'lastId' => (int) $state, 'setupKey' => $setupKey);
        core_CallOnTime::setOnce('plg_ExpandInput', 'recalcExpand36Input', $data, dt::addSecs(60));

        return true;
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
            $expandName = $mvc->expandFieldName ?? 'expand';
            $inputName = $mvc->expandInputFieldName ?? ($expandName . 'Input');
            $expand36Name = $mvc->getExpandFieldName36();
            $query = $mvc->getQuery();
            $query->show("id,{$inputName},{$expandName},{$expand36Name}");
            if(isset($id)){
                $query->where(array("#id = '[#1#]'", $id));
            }

            $count = 0;
            $updates = array();
            while($rec = $query->fetch(null, true)){
                $count++;

                // За всеки запис се гледа има ли промени при разпънатите полета
                $oldVal = $rec->{$expandName} ?? null;
                $oldInput = $input = $rec->{$inputName} ?? null;
                if (self::needsLegacyInput($mvc, $input, $oldVal)) $input = $oldVal;
                $inputArr = type_Keylist::toArray($input);
                $resArr = $mvc->expandInput($inputArr, $rec);
                $expandField = $mvc->getField($expandName);
                $newVal = $expandField->type->fromArray($resArr);
                $newVal36 = $expandField->type->fromArray36($resArr);
                if($oldVal != $newVal || $oldInput !== $input || ($rec->{$expand36Name} ?? null) !== $newVal36){
                    $values = array($oldVal, $oldInput, $newVal, $input, $newVal36, true);
                    $key = serialize($values);
                    if (!isset($updates[$key])) $updates[$key] = array('values' => $values, 'ids' => array());
                    $updates[$key]['ids'][] = (int) ($rec->id ?? 0);
                }
                if ($count % 50000 === 0) {
                    self::saveRebuiltFields($mvc, $updates);
                    $updates = array();
                }
            }

            self::saveRebuiltFields($mvc, $updates);
            core_Debug::stopTimer('recalcExpandedInputs');
            core_Debug::log("{$mvc->className} Total {$count} : REGEN FIELDS: " . round(core_Debug::$timers['recalcExpandedInputs']->workingTime ?? 0, 2));
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
        $mvc = cls::get($data->mvc ?? null);
        $expandName = $mvc->expandFieldName ?? 'expand';
        $inputName = $mvc->expandInputFieldName ?? ($expandName . 'Input');
        $expand36Name = $mvc->getExpandFieldName36();
        $setupKey = $data->setupKey ?? null;
        $lastId = (int) ($data->lastId ?? 0);
        if ($setupKey) {
            // Останала задача за предишно поле не трябва да обработва новото.
            if ($setupKey !== self::getSetupKey($mvc)) return;
            $state = core_Permanent::get($setupKey);
            if ($state === 'done' || (int) $state > $lastId) return;
        }
        core_App::setTimeLimit(300);

        $query = $mvc->getQuery();
        $query->show("id,{$expandName},{$inputName},{$expand36Name}");
        $query->orderBy('id', 'ASC');
        $query->limit(50000);
        $query->where(array("#id > '[#1#]'", $lastId));

        // Съвместимост със задачите от по-стари миграции.
        if(!empty($data->onlyEmpty)){
            $query->where("#{$expandName} != '' AND (#{$expand36Name} IS NULL OR #{$expand36Name} = '')");
        }

        $updates = array();
        $nextId = null;
        $expandType = $mvc->getFieldType($expandName);
        // За условния запис са нужни действителните стойности, без AfterRead заместване.
        while($rec = $query->fetch(null, true)){
            $nextId = (int) ($rec->id ?? 0);
            $oldExpanded = $expanded = $rec->{$expandName} ?? null;
            $oldInput = $input = $rec->{$inputName} ?? null;
            $initialize = $setupKey && ($mvc->fixExpandFieldOnSetup ?? true) !== false && empty($input) && !empty($expanded);
            if ($initialize) {
                $input = $expanded;
                $expanded = $expandType->fromArray($mvc->expandInput(type_Keylist::toArray($input), $rec));
            }
            $encoded = $expanded === null ? null : $expandType->fromArray36(type_Keylist::toArray($expanded));
            if (($rec->{$expand36Name} ?? null) === $encoded && !$initialize) continue;

            $values = array($oldExpanded, $oldInput, $expanded, $input, $encoded, (bool) $initialize);
            $key = serialize($values);
            if (!isset($updates[$key])) {
                $updates[$key] = array('values' => $values, 'ids' => array());
            }
            $updates[$key]['ids'][] = $nextId;
        }

        self::saveRebuiltFields($mvc, $updates);

        if ($nextId === null) {
            if ($setupKey) {
                core_Permanent::set($setupKey, 'done', core_Permanent::FOREVER_VALUE);
                $mvc->_expandInput36Ready = true;
            }
            $mvc->logDebug('Приключи попълването на разширените полета');
            return;
        }

        $newData = clone $data;
        $newData->lastId = $nextId;
        core_CallOnTime::setOnce('plg_ExpandInput', 'recalcExpand36Input', $newData, dt::addSecs(60));
        if ($setupKey) core_Permanent::set($setupKey, $nextId, core_Permanent::FOREVER_VALUE);
    }


    /**
     * Условният UPDATE пази паралелните редакции и не възстановява изтрити записи.
     */
    private static function saveRebuiltFields($mvc, $updates)
    {
        $expandName = str::phpToMysqlName($mvc->expandFieldName ?? 'expand');
        $inputName = str::phpToMysqlName($mvc->expandInputFieldName ?? 'expandInput');
        $field36 = str::phpToMysqlName($mvc->getExpandFieldName36());
        $db = $mvc->db;
        $quote = function ($value) use ($db) {
            return $value === null ? 'NULL' : "'" . $db->escape($value) . "'";
        };
        foreach ($updates as $update) {
            list($oldExpanded, $oldInput, $expanded, $input, $encoded, $updateSource) = $update['values'];
            $set = "`{$field36}` = " . $quote($encoded);
            $where = "`{$expandName}` <=> " . $quote($oldExpanded);
            if ($updateSource) {
                $set .= ", `{$expandName}` = " . $quote($expanded) . ", `{$inputName}` = " . $quote($input);
                $where .= " AND `{$inputName}` <=> " . $quote($oldInput);
            }
            foreach (array_chunk($update['ids'], 1000) as $ids) {
                $idList = implode(',', $ids);
                expect($db->query("UPDATE `{$mvc->dbTableName}` SET {$set} WHERE `id` IN ({$idList}) AND {$where}", false, $mvc->doReplication ?? null));
                $mvc->dbTableUpdated();
            }
        }
    }


    /**
     * Помощна ф-я за по-бързо търсене в разширените полета на модела
     * взависимост избраната константа се търси или пълнотекстовото или с LOCATE
     *
     * @param mixed $mvc                 - модел
     * @param core_Query $query          - заявка, която да се модифицира
     * @param mixed $value               - стойности за търсене масив/кейлист/ид
     * @param string|null $externalKey   - поле на външния ключ, ако разширеното поле го няма в заявката
     * @param boolean $not               - дали да е отрицателна заявката
     * @return void
     */
    public static function applyExtendedInputSearch($mvc, &$query, $value, $externalKey = null, $not = false)
    {
        $mvc = cls::get($mvc);

        // Обръщане на стойностите в масив
        $valueArr = is_array($value) ? $value : (keylist::isKeylist($value) ? keylist::toArray($value) : arr::make($value, true));
        $useFullTextSearch = bgerp_Setup::get('USE_FULLTEXT_GROUP_SEARCH');
        $indexReady = $useFullTextSearch != 'no' && self::isIndexReady($mvc);

        // Ако е избрано обикновенното търсене, то става с LOCATE в полето $mvc->expandFieldName
        // Празният MATCH има постоянен резултат и не зависи от попълването на индекса.
        if($useFullTextSearch == 'no' || (!$indexReady && countR($valueArr))){
            $expandName = $mvc->expandFieldName ?? 'expand';

            // Ако в заявката го няма полето, то се очаква да има външен ключ и се добавя към заявката
            if(!isset($query->fields[$expandName])){
                expect($externalKey);
                $query->EXT($expandName, $mvc->className, "externalName={$expandName},externalKey={$externalKey}");
            }

            // Избор на ф-я за търсене взависимост дали се търси положителна или отрицателна заявка
            $functionName = ($not) ? 'notLikeKeylist' : 'likeKeylist';
            $query->{$functionName}($expandName, keylist::fromArray(array_fill_keys($valueArr, true)));
            // NOT MATCH включва и записите с NULL; временният LOCATE трябва да прави същото.
            if ($not && $useFullTextSearch != 'no' && countR($valueArr)) {
                $query->orWhere("#{$expandName} IS NULL");
            }
            return;
        }

        // Ако е избрано пълнотекстовото търсене, то се взима изчислимото поле
        $field36 = $mvc->getExpandFieldName36();

        // Ако го няма в заявката, се добавя служебно
        if(!isset($query->fields[$field36])){
            expect($externalKey);
            $query->EXT($field36, $mvc->className, "externalName={$field36},externalKey={$externalKey}");
        }

        // Стойностите се обръщат в 35-чна бройна система и се търси с MATCH в булеан режим
        $values = core_Type::getByName('type_Keylist')->fromArray36($valueArr);
        if($not){
            $query->where("NOT MATCH(#{$field36}) AGAINST('{$values}' IN BOOLEAN MODE)");
        } else {
            $query->where("MATCH(#{$field36}) AGAINST('{$values}' IN BOOLEAN MODE)");
        }
    }
}
