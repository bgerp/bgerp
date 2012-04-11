<?php



/**
 * Клас 'php_Interfaces' - Търсене на нереализирани методи
 *
 * Зарежда последователно всички класове от core_Classes.
 * Проверява техните интерфейси и плъгини за реализация
 * на интерфейсни методи. При липса на такава реализация 
 * @return array $missingMethod[$cInst->className][$iRefl->name] = $method;
 * Масив с името на класа, името на интерфейса и не реализирания метод
 *
 *
 * @category  vendors
 * @package   php
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class core_NoInterfaces extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = "Липсващи интерфейсни методи";
    
    var $loadList = 'plg_SystemWrapper,plg_Sorting,plg_Created';
    
    
    /**
     * Описанието на модела
     */
    function description()
    {
        $this->FLD('class', 'varchar(128)', 'caption=Клас');
        $this->FLD('interface', 'varchar(128)', 'caption=Интерфейс');
        $this->FLD('method', 'varchar(128)', 'caption=Метод');
        
        $this->setDbUnique('class');
     
    }
    
    /**
     * Проверка за реализация на интерфейсните методи.
     * Връща списък с всички липсващи методи
     * 
     */
    function act_InterfacesMethod()
    {
       //Зявки
    	$classesQuery    = core_Classes::getQuery();
    	    	
    	//Обикаляме core_Classes, за да разберем на класа кой интерфейс отговаря
    	while($classRec = $classesQuery->fetch()){

    		// echo "<li> Гледа Класът $classRec->name ";
    		
    		$className = $classRec->name; 

			//Зареждаме класа
    		if(!cls::load($className, TRUE)) {
    			//echo "<li> Класът $className не съществува";
    			continue;
    		}
    		
    	  	$cInst = cls::get($className);
    	  	
    		$interfArr = arr::make($cInst->interfaces, TRUE);
    		
    		//Обхождаме масива с интерфейсите
    		foreach($interfArr as $i) {
    		
    			
    			//Правим ReflectionClass от интерфейса	
    			$iRefl = new ReflectionClass ($i);
    			
    			//Взимаме всички методи в интерфейса
    			$iMethods = $iRefl->getMethods();
    			
   
				//Обхождаме масива с методи на интерфейса
 				foreach($iMethods as $rm){
 					
   			   		$method = $rm->name;
   			   		
   			   		
   			   		// echo "<li> $cInst->className => $method";
   			   		// echo "<li> $cInst->className => $method";
   			   		
   			   		//Взимаме всички методи на класа
 					$methods = get_class_methods($cInst);
     				$methods = array_map('strtolower', $methods);

     				//Проверка дали методите на интерфейса са реализирани и в класа
     				if(in_array(strtolower($method), $methods) || in_array(strtolower($method . '_'), $methods)) {
	 					continue;
     				}

     				//Взимаме плугините на класа
     				if(!count($plugins = $cInst->_plugins)) continue;
     				
     				
     				$before = 'on_before' . strtolower($method);
     				$after  = 'on_after' . strtolower($method);
     				$flagNotExists = TRUE;
     				
     				
     				//Обхождаме плъгините на класа
     				foreach($plugins as $plg) {
     					//Взимаме техните методи
     					$methods = get_class_methods($plg);
     					$methods = array_map('strtolower', $methods);
     					
     					//Считаме за реализиран метога, ако преди него има суфикс "on_before/on_after"
     					if(in_array($before, $methods) || in_array($after, $methods)) {
     						$flagNotExists = FALSE;
     						break;
     					}
     					
     				}
     				
     				//Ако никое от горните условия не сработи значи метода не е реализиран
     				if($flagNotExists) { 
     					//echo "<li> $cInst->className => $method";
     				
     					$missingMethod[$cInst->className][$iRefl->name] = $method;
     						
     				}
    			   	    
 				}
    			 
    		}  
    	}
    	
    	//Обхождаме масива с липсващите методи по име на клас
        foreach($missingMethod as $className=>$intf){
      
         	$count = count($missingMethod);
         	
        	$rec = new stdClass();
            $rec->class = $className;

            //Обхождаме масива с липсващите методи по име на интерфейс
           	foreach($intf as $intfName=>$method){
           						
           		$rec->interface = $intfName;
           		$rec->method = $method;
           		           		
           	}		
           	core_NoInterfaces::save($rec, NULL, 'IGNORE');
         
        }
        
     //bp(count($missingMethod),$missingMethod, $rec);
    	return new Redirect(array($this));
    	
    }

    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    function on_AfterPrepareListToolbar($mvc, $res, $data)
    {
        $data->toolbar->addBtn('Филтър', array($mvc, 'InterfacesMethod'));
    }
    

}