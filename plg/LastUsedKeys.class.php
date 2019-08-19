<?php


/**
 * Клас 'plg_LastUsedKeys' - Кога за последно са използвани ключовете
 *
 * Прикача се към модел и му добавя следната функционалност:
 * След запис на данни в модела-домакин, проверява моделите на всички негови ключови полета (key и keylist)
 * тези модели, които съдържат полето 'lastUsedOn' се попълват с текущото време
 * Ключовите полета могат да бъдат изброени в списъка: var $lastUsedKeys
 *
 *
 * @category  ef
 * @package   plg
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class plg_LastUsedKeys extends core_Plugin
{
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    public function on_AfterSave($mvc, &$id, $rec, $fields = null)
    {
        // Ако липсва масив за полетата, на които трябва да се записва последното използване
        // той се съставя, като се обхождат всички ключови полета
        if (empty($mvc->lastUsedKeys)) {
            foreach ($mvc->fields as $name => $field) {
                if (($field->type instanceof type_Key) || ($field->type instanceof type_Key2) || ($field->type instanceof type_Keylist)) {
                    $mvc->lastUsedKeys[] = $name;
                }
            }
            $mvc->noCheckLastUsedField = false;
            $mvc->logDebug('Не е дефиниран lastUsedKeys');
        } else {
            $mvc->lastUsedKeys = arr::make($mvc->lastUsedKeys);
            
            foreach ($mvc->lastUsedKeys as $field) {
                expect(
                    isset($mvc->fields[$field]),
                    'Полето в lastUsedFields не принадлежи на модела',
                    $field
                );
                expect(
                    ($mvc->fields[$field]->type instanceof type_Key) ||
                    ($mvc->fields[$field]->type instanceof type_Keylist) || (($mvc->fields[$field]->type instanceof type_Key2)),
                    'Полето в lastUsedFields не е key или keylist',
                    $field
                );
            }
            if ($mvc->noCheckLastUsedField !== false) {
                $noCheckLastUsedField = true;
            } else {
                $noCheckLastUsedField = false;
            }
        }
        
        foreach ($mvc->lastUsedKeys as $field) {
            if ($rec->{$field}) {
                if (($mvc->fields[$field]->type instanceof type_Key) || ($mvc->fields[$field]->type instanceof type_Key2)) {
                    $usedClass = cls::get($mvc->fields[$field]->type->params['mvc']);
                    
                    if ($noCheckLastUsedField || isset($usedClass->fields['lastUsedOn'])) {
                        $usedRec = new stdClass();
                        
                        // id' то трябва да е над 0
                        // За случаи с createdBy = -1 или createdBy = 0
                        if ($rec->{$field} > 0) {
                            $usedRec->id = $rec->{$field};
                            $usedRec->lastUsedOn = dt::verbal2mysql();
                            $usedClass->save($usedRec, 'lastUsedOn');
                        }
                    }
                }
                
                if ($mvc->fields[$field]->type instanceof type_Keylist) {
                    $usedClass = cls::get($mvc->fields[$field]->type->params['mvc']);
                    
                    if ($noCheckLastUsedField || isset($usedClass->fields['lastUsedOn'])) {
                        $keysArr = keylist::toArray($rec->{$field});
                        
                        if (count($keysArr)) {
                            foreach ($keysArr as $key) {
                                $usedRec = new stdClass();
                                
                                // id' то трябва да е над 0
                                // За случаи с createdBy = -1 или createdBy = 0
                                if ($key > 0) {
                                    $usedRec->id = $key;
                                    $usedRec->lastUsedOn = dt::verbal2mysql();
                                    $usedClass->save($usedRec, 'lastUsedOn');
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
