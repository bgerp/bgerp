<?php



/**
 * Клас 'core_Interfaces' - Регистър на интерфейсите
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class core_Interfaces extends core_Manager
{
    
    
    /**
     * Плъгини и класове за начално зареждане
     */
    var $loadList = 'plg_Created, plg_SystemWrapper, plg_RowTools';
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Интерфейси";
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(128)', 'caption=Интерфейс, mandatory,width=100%');
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие,oldField=info');
        
        $this->setDbUnique('name');
        
        // Ако не сме в DEBUG-режим, интерфайсите не могат да се редактират
        if(!isDebug()) {
            $this->canWrite = 'no_one';
        }
    }
    
    
    /**
     * Добавя интерфейса в този регистър
     */
    function add($interface)
    {
        $rec = new stdClass();
        $rec->name = $interface;
        $rec->title = cls::getTitle($interface);
        $rec->id = $this->fetchField("#name = '{$interface}'", 'id');
        
        $this->save($rec);
        
        return $rec->id;
    }
    
    
    /**
     * Връща id-то на посочения интерфейс
     */
    function fetchByName($name)
    {
        $id = $this->add($name);
        
        expect($id, 'Липсващ интерфейс', $name);
        
        return $id;
    }
    
    
    /**
     * Връща масив с ид-та на поддържаните от класа интерфeйси
     *
     * @param mixed $class string (име на клас) или object (инстанция) или int (ид на клас)
     * @return array ключове - ид на интерфейси, стойности - TRUE
     */
    static function getInterfaceIds($class)
    {
        if(is_scalar($class)) {
            $instance = cls::get($class);
        } else {
            $instance = $class;
        }
        
        // Очакваме, че $class е обект
        expect(is_object($instance), $class);
        
        $list = $instance->interfaces = arr::make($instance->interfaces);
        
        $result = array();
        
        if(count($list)) {
            // Вземаме инстанция на core_Interfaces
            $self = cls::get(__CLASS__);     // Би било излишно, ако fetchByName стане static
            foreach($list as $key => $value) {
                if(is_numeric($key)) {
                    $intfId = $self->fetchByName($value);
                } else {
                    $intfId = $self->fetchByName($key);
                }
                
                // Добавяме id в списъка
                $result[$intfId] = TRUE;
            }
        }
        
        return $result;
    }
    
    
    /**
     * Връща keylist с поддържаните от класа интерфeйси
     *
     * @param mixed $class string (име на клас) или object (инстанция) или int (ид на клас)
     * @return string keylist от ид-тата на интерфейсите
     */
    static function getKeylist($class)
    {
        $keylist = self::getInterfaceIds($class);
        $keylist = type_Keylist::fromArray($keylist);
        
        return $keylist;
    }
    
    
    /**
     * Рутинен метод, премахва интерфейсите, които са от посочения пакет или няма код за тях
     */
    static function deinstallPack($pack)
    {
        $query = self::getQuery();
        $preffix = $pack . "_";
        
        while($rec = $query->fetch()) {
            if(strpos($rec->name, $preffix) === 0 || (!cls::load($rec->name, TRUE))) {
                core_Interfaces::delete($rec->id);
            }
        }
    }
}