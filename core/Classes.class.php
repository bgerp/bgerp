<?php

/**
 *  Клас 'class_Manager' - Регистър на класове
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
class core_Classes extends core_Manager
{
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_SystemWrapper, Interfaces=core_Interfaces, plg_State2, plg_RowTools';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = "Регистрирани класове";
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Клас,mandatory,width=100%');
        $this->FLD('info', 'text', 'caption=Информация,width=100%');
        $this->FLD('interfaces', 'keylist(mvc=core_Interfaces,select=name)', 'caption=Интерфейси');
        
        $this->setDbUnique('name');
        
        // Ако не сме в DEBUG-режим, интерфейсите не могат да се редактират
        if(!isDebug()) {
            $this->canWrite = 'no_one';
        }
    }
    
    
    /**
     * Добавя информация за класа в регистъра
     */
    function addClass($class)
    {
        // Очакваме валидно име на клас
        expect($name = cls::getClassName($class));
        
        // Очакваме този клас да може да бъде зареден
        expect(cls::load($name));
        
        $rfl = new ReflectionClass($name);
        
        $interfacesArr = $rfl->getInterfaces();
        
        if(count($interfacesArr)) {
            foreach($interfacesArr as $interface => $rClass) {
                $interfaces .= ($interfaces ? ',' : '') . $interface;
            }
            
            $info = core_Interfaces::getFirstLineFromDocComment($rfl->getDocComment());
            
            $Classes = cls::get(__CLASS__);
            
            return $Classes->saveClass($name, $info, $interfaces);
        }
        
        return '';
    }
    
    
    /**
     * Записва посочения клас
     */
    function saveClass($name, $info, $interfaces)
    {
        expect($name);
        
        $rec->name = $name;
        $rec->info = $info;
        
        if($interfaces) {
            $rec->interfaces = $this->Interfaces->getKeylist($interfaces);
        }
        
        $id = $rec->id = $this->fetchField("#name = '{$name}'", 'id');
        
        $this->save($rec, NULL);
        
        return $id ? NULL : $rec->id;
    }
    
    
    /**
     * Връща id на класа според името му. Ако е посочен интерфейс,
     * то трябва класа да има този интерфейс
     */
    function fetchByName($name, $interface = NULL)
    {
        $Classes = cls::get(__CLASS__);
        
        $query = $Classes->getQuery();
        
        if($interface) {
            $Interfaces = cls::get('core_Interfaces');
            $interfaceId = $Interfaces->fetchByName($interface);
            $query->likeKeylist('interface', $interfaceId);
        }
        
        $query->show('id');
        
        $rec = $query->fetch(array("#name = '[#1#]'", $name));
        
        return $rec;
    }
    
    
    /**
     * Всъща опции за селект с класовете, имащи определения интерфейс
     */
    function getOptionsByInterface($interface, $title = 'name')
    {
        if($interface) {
            $interfaceId = $this->Interfaces->fetchByName($interface);
            
            // Очакваме валиден интерфейс
            expect($interfaceId);
            
            $interfaceCond = " AND #interfaces LIKE '%|{$interfaceId}|%'";
        } else {
            $interfaceCond = '';
        }
        
        $options = $this->makeArray4Select($title, "#state = 'active'" . $interfaceCond);
        
        return $options;
    }
}