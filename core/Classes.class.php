<?php

/**
 * Клас 'core_Classes' - Регистър на класовете, имащи някакви интерфейси
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
     *  Списък за начално зарежддане
     */
    var $loadList = 'plg_Created, plg_SystemWrapper, plg_State2, plg_RowTools';
    
    
    /**
     *  Заглавие на мениджъра
     */
    var $title = "Класове, имащи интерфейси";
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name',  'varchar(128)', 'caption=Клас,mandatory,width=100%');
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие,width=100%,oldField=info');
        $this->FLD('interfaces', 'keylist(mvc=core_Interfaces,select=name)', 'caption=Интерфейси');
        
        $this->setDbUnique('name');
        
        // Ако не сме в DEBUG-режим, класовете не могат да се редактират
        if(!isDebug()) {
            $this->canWrite = 'no_one';
        }
    }
    
	/**
	 * 
	 * Проверява дали може да се зареди редактираният клас
	 * и дава съобщение във формата при неуспех при ръчно добавяне на клас
	 * @param object $mvc
	 * @param object $form
	 */ 	
 	function on_AfterInputEditForm ($mvc, $form)
 	{
        if (!$form->isSubmitted()){
            return;
        }

        // Вземаме инстанция на core_Classes
        $Classes = cls::get('core_Classes');
		
        // Очакваме валидно име на клас
        if (!cls::getClassName($form->rec->name, TRUE)) {
        	$form->setError('name', 'Невалидно име на клас');
        	return;
        }
        
        // Очакваме този клас да може да бъде зареден
        if (!cls::load($form->rec->name, TRUE)) {
        	$form->setError('name', 'Класът не може да се зареди');
        	return;
        }
        
		$cls = cls::createObject($form->rec->name);
		
		if (method_exists($cls, 'setParams')) {
			$cls->setParams();
		}
 	}
 	
 	
    /**
     * Добавя информация за класа в регистъра
     */
    function add($class, $title = FALSE)
    {
        $rec = new stdClass();

        $rec->interfaces = core_Interfaces::getKeylist($class);

        if(!$rec->interfaces) return '';

        // Вземаме инстанция на core_Classes
        $Classes = cls::get('core_Classes');

        // Очакваме валидно име на клас
        expect($rec->name = cls::getClassName($class), $class);
        
        // Очакваме този клас да може да бъде зареден
        expect(cls::load($rec->name), $rec->name);
        
		$cls = cls::createObject($rec->name);
		
		if (method_exists($cls, 'setParams')) {
			$cls->setParams();
		}
		        
        $rec->title = $title ? $title : cls::getTitle($rec->name);
        
        $id = $rec->id = $Classes->fetchField("#name = '{$rec->name}'", 'id'); 
        
        $Classes->save($rec);
        
        if(!$id) {
            $res = "<li style='color:green;'>Класът {$rec->name} е добавен към мениджъра на класове</li>";
        } else {
            $res = "<li style='color:#660000;'>Информацията за класа {$rec->name} бе обновена в мениджъра на класове</li>";
        } 

        return $res;
    }
    
    
    /**
     * Връща $rec на устройството според името му
     */
    function fetchByName($name)
    {
        $query = self::getQuery();
                
        $query->show('id');
        
        $rec = $query->fetch(array("#name = '[#1#]'", $name));
        
        return $rec;
    }
    
    
    /**
     * Връща опции за селект с устройствата, имащи определения интерфейс
     */
    function getOptionsByInterface($interface, $title = 'name')
    {
        if($interface) {
            // Вземаме инстанция на core_Interfaces
            $Interfaces = cls::get('core_Interfaces');

            $interfaceId = $Interfaces->fetchByName($interface);
            
            // Очакваме валиден интерфeйс
            expect($interfaceId);
            
            $interfaceCond = " AND #interfaces LIKE '%|{$interfaceId}|%'";
        } else {
            $interfaceCond = '';
        }
        
        $options = $this->makeArray4Select($title, "#state = 'active'" . $interfaceCond);
        
        return $options;
    }
    
    /**
     * Връща ид на клас по (име | инстанция | ид)
     *
     * @param mixed $class string (име на клас) или object (инстанция) или int (ид на клас)
     * @return int ид на клас
     */
    static function getId($class) {
    	if (is_numeric($class)) {
    		$classId = $class;
    	} else {
    		if (is_object($class)) {
    			$className = $class->className;
    		} else {
    			$className = $class;
    		}
    		
    		$Classes = cls::get('core_Classes');
    		$classId = $Classes->fetchField(array("#name = '[#1#]'", $className), 'id');
    	}
    	
    	return $classId;
    }
}