<?php

/**
 *  Клас 'core_Adapters' - Регистър на адаптерите
 *
 *
 * @category   Experta Framework
 * @package    core
 * @author     Milen Georgiev
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class core_Adapters extends core_Manager
{
    
    
    /**
     *  Плъгини и класове за начално зареждане
     */
    var $loadList = 'plg_Created, plg_SystemWrapper, plg_RowTools';
    
    /**
     *  Заглавие на мениджъра
     */
    var $title = "Адаптери";
        
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Адаптер, mandatory,width=100%');
        $this->FLD('title', 'text', 'caption=Заглавие,oldField=info');
        
        $this->setDbUnique('name');
        
        // Ако не сме в DEBUG-режим, адаптерите не могат да се редактират
        if(!isDebug()) {
            $this->canWrite = 'no_one';
        }
    }
    

    /**
     * Добавя адаптера в списъка от адаптери
     */
    function add($adapter, $title)
    {
        $rec = new stdClass();
        $rec->name  = $adapter;
        $rec->title = $title;
        $rec->id    = $this->fetchField("#name = '{$adapter}'", 'id');

        $this->save($rec);
    }
    
    
    /**
     * Връща id-то на посочения адаптер
     */
    function fetchByName($name)
    {
        $id = $this->fetchField("#name = '{$name}'", 'id');
        
        expect($id, 'Липсващ адаптер', $name);

        return $id;
    }
    
    
    /**
     * Връща keylist с поддържаните от класа адаптери
     */
    function getKeylist($class)
    {
        if(is_string($class)) {
            $instance = cls::get($class);
        } else {
            $instance = $class;
        }
        

        // Очакваме, че $clsss е обект
        expect(is_object($instance), $class);

        $list = $instance->adapters = arr::make($instance->adapters);
        
        // Ако няма декларирани никакви адаптери - връщаме празен keylist
        if(!count($list)) return '';
        
        // Вземаме инстанция на core_Adapters
        $Adapters = cls::get('core_Adapters');
        
        foreach($list as $key => $value) {
            if(is_numeric($key)) {
                $keylist[$Adapters->fetchByName($value)] = TRUE;
            } else {
                $keylist[$Adapters->fetchByName($key)] = TRUE;
            }
        }
        
        $keylist = type_Keylist::fromVerbal($keylist);
        
        return $keylist;
    }
    
}