<?php

 
/**
 * Клас 'plg_LastUsedKeys' - Кога за последно са използвани ключовете
 *
 * Прикача се към модел и му добавя следната функционалност:
 * След запис на данни в модела-домакин, проверява моделите на всички негови ключови полета (key и keylist)
 * тези модели, които съдържат полето 'lastUsedOn' се попълват с текущото време
 * Ключовите полета могат да бъдат изброени в списъка: var $lastUsedKeys
 *
 * @category   Experta Framework
 * @package    plg
 * @author     Milen Georgiev
 * @copyright  2006-2009 Experta Ltd.
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class plg_LastUsedKeys extends core_Plugin
{
    
    /**
     *  Извиква се преди вкарване на запис в таблицата на модела
     */
    function on_AfterSave(&$mvc, &$id, &$rec, &$fields = NULL)
    {   
        // Ако липсва масив за полетата, на които трябва да се записва последното използване
        // той се съставя, като се обхождат всички ключови полета
        if(empty($mvc->lastUsedKeys)) {
            foreach($mvc->fields as $name => $field) {
                if( ($field->type instanceof type_Key) || ($field->type instanceof type_Keylist) ) {
                    $mvc->lastUsedKeys[] = $name;
                }
            }
            $noCheckLastUsedField = FALSE;
        } else {
            $mvc->lastUsedKeys = arr::make($mvc->lastUsedKeys);
            $noCheckLastUsedField = TRUE;
        }

        foreach($mvc->lastUsedKeys as $field) {
            if($rec->{$field}) {
                if($mvc->fields[$field]->type instanceof type_Key) {
                    $usedClass = cls::get($mvc->fields[$field]->type->params['mvc']);
                    if($noCheckLastUsedField || isset($usedClass->fields['lastUsedOn'])) {
                        $usedRec = new stdClass();
                        $usedRec->id = $rec->{$field};
                        $usedRec->lastUsedOn = dt::verbal2mysql();
                        $usedClass->save($usedRec, 'lastUsedOn', 'DELAY');
                    }
                }
                if($mvc->fields[$field]->type instanceof type_Keylist) {
                    $usedClass = cls::get($mvc->fields[$field]->type->params['mvc']);
                    if($noCheckLastUsedField || isset($usedClass->fields['lastUsedOn'])) {
                        $keysArr = type_Keylist::toArray($rec->{$field});
                        if(count($keysArr)) {
                            foreach($keysArr as $key) {
                                $usedRec = new stdClass();
                                $usedRec->id = $key;
                                $usedRec->lastUsedOn = dt::verbal2mysql();
                                $usedClass->save($usedRec, 'lastUsedOn', 'DELAY');
                            }
                        }
                    }
                }
            }
        }
    }
}