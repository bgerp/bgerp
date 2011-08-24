<?php

/**
 *  Клас 'core_Interfaces' - Регистър на интерфейсите
 *
 * @category   Experta Framework
 * @package    core
 * @author     Milen Georgiev
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @since      v 0.1
 */
class core_Interfaces extends core_Manager
{
    /**
     *  Плъгини и класове за начално зареждане
     */
    var $loadList = 'plg_Created, plg_SystemWrapper, plg_RowTools';
    
    /**
     *  Заглавие на мениджъра
     */
    var $title = "Интерфейси";
        
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(128)', 'caption=Интерфейс, mandatory,width=100%');
        $this->FLD('title', 'varchar(128)','caption=Заглавие,oldField=info');
        
        $this->setDbUnique('name');
        
        // Ако не сме в DEBUG-режим, интерфайсите не могат да се редактират
        if(!isDebug()) {
            $this->canWrite = 'no_one';
        }
    }
    

    /**
     * Добавя интерфайса в този регистър
     */
    function add($interface)
    {
        $rec = new stdClass();
        $rec->name  = $interface;
        $rec->title = cls::getClassName($interface);
        $rec->id    = $this->fetchField("#name = '{$interface}'", 'id');

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
	        $self = cls::get(__CLASS__); // Би било излишно, ако fetchByName стане static
	        
	        foreach($list as $key => $value) {
	            if(is_numeric($key)) {
	                $intfId   = $self->fetchByName($value);
	            } else {
	                $intfId   = $self->fetchByName($key);
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
     * След сетъп-а
     * @todo: Да се махне
     */
    function on_AfterSetupMVC($mvc, $html)
    {
        $delete = array('acc_RegisterIntf');

        foreach($delete as $name) {
            $mvc->delete("#name = '{$name}'");
        }

        $convert = array(
            'intf_TransactionSource' => 'acc_TransactionSourceIntf', 
            'intf_RegisterGroup' => NULL, 
            'intf_Register' => 'acc_RegisterIntf',
            'intf_IpCamera' => 'cams_DriverIntf',
            'intf_RemoteControl' => NULL,
            'intf_IpSensor' => 'sens_DriverIntf',
            'intf_IpRfid' => 'rfid_ReaderIntf',
            'intf_Settings' => 'settings_Intf',
            'intf_Contragent' => 'crm_ContragentAccRegIntf',
            'stores_RegisterIntf' => 'store_AccRegIntf'
        );

        foreach($convert as $old => $new) 
        {
            if($new) {
                $rec = $mvc->fetch("#name = '{$old}'");
                if($rec) {
                    $rec->name  = $new;
                    $rec->title = cls::getTitle($new);
                    $mvc->save($rec);
                }
            }
        }
    }
}