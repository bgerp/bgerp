<?php


/**
 * Плъгин позволяващ разширението на единичния изглед на мастъри с данни от други мениджъри
 *
 *
 * @category  bgerp
 * @package   groups
 *
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see       https://github.com/bgerp/bgerp/issues/336
 */
class groups_Extendable extends core_Plugin
{
    /**
     * Преди подготовка на единичния изглед
     *
     * @param core_Master $master
     * @param mixed       $res
     * @param mixed       $data
     */
    public static function on_BeforePrepareSingle(core_Master $master, &$res, $data)
    {
        static::attachExtenders($master, $data->rec);
    }
    
    
    /**
     * Реализация на метода $master::addExtender() по подразбиране
     *
     *  Така обектите с прикачен плъгин groups_Extendable получават нов метод - addExtender().
     *  Извикването му води до регистриране на нов екстендер в груповия мениджър на $master по
     *  време на изпълнението.
     *
     *  Този механизъм позволява на плъгините да регистрират екстендери на класовете, към които
     *  са прикачени.
     *
     * @param core_Master $master
     * @param mixed       $res           резултата, който връща $master::addExtender()
     * @param string      $xtName        кодово име на екстендер
     * @param array       $xtDescription
     */
    public static function on_AfterAddExtender(core_Master $master, &$res, $xtName, $xtDescription)
    {
        $groups = static::getGroupsManager($master);
        
        $res = $groups->addExtender($xtName, $xtDescription);
    }
    
    
    /**
     * Зарежда (като детайли) екстендерите на групите, в които се намира мастър-записа $rec
     *
     * @param core_Master $master
     * @param stdClass    $rec    Запис от модела $master
     */
    protected static function attachExtenders(core_Master $master, $rec)
    {
        $extenders = $master::getExtenders($rec);
        
        //
        // Добавяме екстендерите като детайли на мастър класа
        //
        $details = array();
        
        foreach ($extenders as $key => $ext) {
            $prefix = $ext['prefix'];
            $className = $ext['className'];
            
            $details[$prefix] = $className;
        }
        
        $master->attachDetails($details);
    }
    
    
    /**
     * Извлича описанията на екстендерите на групите, в които се намира записа $rec
     *
     * @param core_Master $master
     * @param stdClass    $rec
     */
    public static function on_BeforeGetExtenders(core_Master $master, &$extenders, $rec)
    {
        $groupsFieldName = static::getGroupsFieldName($master);
        
        // ИД-тата на групите в които е записа $rec
        $groupIds = keylist::toArray($rec->{$groupsFieldName});
        
        expect($GroupsManager = static::getGroupsManager($master));
        
        $extenders = $GroupsManager->getExtenders($groupIds);
    }
    
    
    public static function getGroupsManager(core_Master $master)
    {
        $groupsFieldName = static::getGroupsFieldName($master);
        
        expect($groupsField = $master->getField($groupsFieldName));
        expect($GroupsManager = cls::get($groupsField->type->params['mvc']));
        
        return $GroupsManager;
    }
    
    
    /**
     * Името на полето съдържащо информация за групите в които е съотв. запис на $master
     *
     * @param core_Master $master
     *
     * @return string
     */
    protected static function getGroupsFieldName(core_Master $master)
    {
        return !empty($master->groupsField) ? $master->groupsField : 'groupsId';
    }
    
    
    public static function on_AfterSave(core_Master $master, &$id, $rec, $saveFields = null)
    {
        $extenders = $master::getExtenders($rec);
        
        foreach ($extenders as $ext) {
            $extender = cls::get($ext['className']);
            $extender->invoke('afterMasterSave', array($rec, $master));
        }
    }
}
