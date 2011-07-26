<?php

/**
 *  Клас 'core_Interfaces' - Регистър на интерфейси
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
class core_Interfaces extends core_Manager
{
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_SystemWrapper, plg_RowTools';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = "Регистрирани интерфейси";
    
    // Ръчната модификация е забранена
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Интерфейс, mandatory,width=100%');
        $this->FLD('info', 'text', 'caption=Информация');
        
        $this->setDbUnique('name');
        
        // Ако не сме в DEBUG-режим, интерфейсите не могат да се редактират
        if(!isDebug()) {
            $this->canWrite = 'no_one';
        }
    }
    
    
    /**
     * Връща id-то на посочения интерфейс
     */
    function fetchByName($name)
    {
        $rec->id = $this->fetchField("#name = '{$name}'", 'id');
        
        if(!$rec->id) {
            $rec->name = $name;
            
            if(cls::load($name, TRUE)) {
                $rfl = new ReflectionClass($name);
                $rec->info = $this->getFirstLineFromDocComment($rfl->getDocComment());
            } else {
                cls::load($name);
            }
            $this->save($rec);
        }
        
        return $rec->id;
    }
    
    
    /**
     * Връща keylist с посочените интерфейси
     */
    function getKeylist($list)
    {
        $list = arr::make($list, TRUE);
        
        // Очакваме поне един интерфейс
        expect(count($list));
        
        foreach($list as $name => $info) {
            $keylist[$this->fetchByName($name)] = TRUE;
        }
        
        $keylist = type_Keylist::fromVerbal($keylist);
        
        return $keylist;
    }
    
    
    /**
     * Връща първата линия с полезна информация от JavaDoc коментар
     */
    function getFirstLineFromDocComment($comment)
    {
        $comment = trim(substr($comment, 3, -2));
        
        $lines = explode("\n", $comment);
        
        foreach($lines as $l) {
            $l = ltrim($l, "\n* \r\t");
            
            if($l) return $l;
        }
    }
}