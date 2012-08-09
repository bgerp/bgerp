<?php

/**
 * Плъгин позволяващ разширението на единичния изглед на мастъри с данни от други мениджъри 
 *
 *
 * @category  bgerp
 * @package   groups
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @see       https://github.com/bgerp/bgerp/issues/336
 */
class groups_Extendable extends core_Plugin
{
    /**
     * Преди подготовка на единичния изглед
     * 
     * @param core_Master $master
     * @param mixed $res
     * @param mixed $data
     */
    public function on_BeforePrepareSingle(core_Master $master, &$res, $data)
    {
        static::attachExtenders($master, $data->rec);
    }
    
    
    /**
     * Зарежда (като детайли) екстендерите на групите, в които се намира мастър-записа $rec
     * 
     * @param core_Master $master
     * @param stdClass $rec Запис от модела $master
     */
    protected static function attachExtenders(core_Master $master, $rec)
    {
        $extenders = static::getExtenders($master, $rec);
        
        $master->details = arr::make($master->details);
        
        //
        // Добавяме екстендерите като детайли на мастър класа
        //
        foreach ($extenders as $key => $ext) {
            $prefix    = $ext['prefix'];
            $className = $ext['className'];
            
            if (!isset($master->details[$prefix])) {
                $master->details[$prefix] = $className;
            }
        }
    }


    /**
     * Извлича описанията на екстендерите на групите, в които се намира записа $rec
     *
     * @param core_Master $master
     * @param stdClass $rec
     */
    protected static function getExtenders(core_Master $master, $rec)
    {
        $groupsFieldName = static::getGroupsFieldName($master);
        
        // ИД-тата на групите в които е записа $rec  
        $groupIds = type_Keylist::toArray($rec->{$groupsFieldName});
        
        expect($groupsField   = $master->getField($groupsFieldName));
        expect($GroupsManager = cls::get($groupsField->type->params['mvc']));
        
        return $GroupsManager->getExtenders($groupIds);
    }
    
    
    /**
     * Името на полето съдържащо информация за групите в които е съотв. запис на $master
     * 
     * @param core_Master $master
     * @return string
     */
    protected static function getGroupsFieldName(core_Master $master)
    {
        return !empty($master->groupsField) ? $master->groupsField : 'groupsId';
    }
}