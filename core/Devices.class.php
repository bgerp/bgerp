<?php

/**
 * Клас 'core_Devices' - Регистър на устройства
 *
 * Устройствата са класове, които имат адаптери
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
class core_Devices extends core_Manager
{
    /**
     *  Списък за начално зарежддане
     */
    var $loadList = 'plg_Created, plg_SystemWrapper, plg_State2, plg_RowTools';
    
    
    /**
     *  Заглавие на мениджъра
     */
    var $title = "Регистрирани устройства";
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name',  'varchar(64)', 'caption=Устройство,mandatory,width=100%');
        $this->FLD('title', 'varchar(64)', 'caption=Информация,width=100%,oldField=info');
        $this->FLD('adapters', 'keylist(mvc=core_Adapters,select=name)', 'caption=Адаптери');
        
        $this->setDbUnique('name');
        
        // Ако не сме в DEBUG-режим, устройствата не могат да се редактират
        if(!isDebug()) {
            $this->canWrite = 'no_one';
        }
    }
    
    
    /**
     * Добавя информация за класа в регистъра
     */
    function add($class, $title = FALSE)
    {
        $rec->adapters = core_Adapters::getKeylist($class);

        if(!$rec->adapters) return '';

        // Вземаме инстанция на core_Devices
        $Devices = cls::get('core_Devices');

        // Очакваме валидно име на клас
        expect($rec->name = cls::getClassName($class), $class);
        
        // Очакваме този клас да може да бъде зареден
        expect(cls::load($rec->name), $rec->name);
        
        $rec->title = $title ? $title : $Devices->getClassTitle($rec->name);

        
        $id = $rec->id = $this->fetchField("#name = '{$name}'", 'id');
        
        $this->save($rec);
        
        if($id) {
            $res = "<li style='color:green;'>Класът {$rec->name} е добавен към мениджъра на устройствата</li>";
        } else {
            $res = "<li style='color:#660000;'>Информацията за класа {$rec->name} бе обновена в мениджъра на устройствата</li>";
        }

        return $res;
    }
    
    
    /**
     * Връща $rec на устройството според името му
     */
    function fetchByName($name)
    {
        // Вземаме инстанция на core_Devices
        $Devices = cls::get('core_Devices');
        
        $query = $Devices->getQuery();
                
        $query->show('id');
        
        $rec = $query->fetch(array("#name = '[#1#]'", $name));
        
        return $rec;
    }
    
    
    /**
     * Всъща опции за селект с устройствата, имащи определения адаптер
     */
    function getOptionsByAdapter($adapter, $title = 'name')
    {
        if($adapter) {
            // Вземаме инстанция на core_Adapters
            $Adapters = cls::get('core_Adapters');

            $adapterId = $Adapters->fetchByName($interface);
            
            // Очакваме валиден адаптер
            expect($adapterId);
            
            $adapterCond = " AND #adapters LIKE '%|{$adapterId}|%'";
        } else {
            $adapterCond = '';
        }
        
        $options = $this->makeArray4Select($title, "#state = 'active'" . $adapterCond);
        
        return $options;
    }

    
    /**
     * Връща заглавието на класа от JavaDoc коментар или от свойството $title
     */
    function getClassTitle($class)
    {
        $rfl = new ReflectionClass($class);
        
        $comment = $rfl->getDocComment();

        $comment = trim(substr($comment, 3, -2));
        
        $lines = explode("\n", $comment);
        
        foreach($lines as $l) {
            $l = ltrim($l, "\n* \r\t");
            
            if($firstLine && $l) {
                $firstLine = $l;
            }

            if(strpos($l, '@title:') === 0) {
                $titleLine = trim(substr($l, 7)); 
            }
        }

        if($titleLine) return $titleLine;
        
        $obj = cls::get($class);

        if($obj->title) return $obj->title;

        return $firstLine;
    }

}