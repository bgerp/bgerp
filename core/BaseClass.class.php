<?php

/**
 * Клас 'core_BaseClass' - прототип за класове поддържащи събития и инициализиране
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
class core_BaseClass
{
    
    
    /**
     * Плъгини и MVC класове за предварително зареждане
     */
    var $loadList;
    
    
    /**
     * Масив с плъгини, които ще работят съвместно с класа
     */
    var $pluginsList;
    
    /**
     * Масив с имена на методи, позволени за извикване дори при липса на имплементация
     *
     * @var array
     */
    var $invocableMethods = array();
    
    
    /**
     * Конструктор. Дава възможност за инициализация
     */
    function core_BaseClass($params = NULL)
    {
        if(isset($params)) {
            $this->init($params);
        }
    }
    
    
    /**
     * Връща id-то на текущия клас, ако има такова
     */
    static function getClassId()
    {
        return core_Classes::fetchField(array("#name = '[#1#]'" , get_called_class()), 'id');
    }
   
    /**
     * Начално инициализиране на обект
     * Параметрите се предават по следния начин:
     * $obj = cls::get($className, $params = array())
     */
    function init($params = array())
    {
        $params = arr::make($params);
        
        foreach ($params as $name => $value) {
            if (is_int($name)) {
                $this->params[$name] = $value;
            } else {
                $this->{$name} =& $params[$name];
            }
        }
    }
    
    
    /**
     * Зарежда само един клас, плъгин или MVC в полета-свойства на обекта
     *
     * @param string $name име под което класът трябва да бъде зареден,
     *                     ако е плъгин или mvc
     * @param string $class името на класа
     */
    function loadSingle($name, $class)
    {
        $class = cls::getClassName($class);
        
        expect($name);

        // Ако е подклас на core_Mvc, записваме го като член на този клас 
        if (!($this->{$name}) && cls::isSubclass($class, 'core_Mvc')) {
            $this->{$name} =& cls::get($class);
        }
        
        // Ако има интерфейс на плъгин, записваме го в масива на плъгините
        if (!($this->_plugins[$name]) && cls::isSubclass($class, 'core_Plugin')) {
            $this->_plugins[$name] =& cls::get($class);
        }
    }
    
    
    /**
     * Зарежда списък с класове, mvc или плъгини в полета-свойства на обекта
     *
     * @param string|array $classesList списък с класове, които трябва да се заредят
     */
    function load($classesList)
    {
        $classesList = arr::make($classesList, TRUE);
        
        foreach ($classesList as $var => $class) {
            // Зареждаме класа. Ако никое от по-долните не се 
            // изпълни, най-малкото ще имаме зареден този клас
            $this->loadSingle($var, $class);
        }
    }
    
    
    /**
     * Генерира събитие с посоченото име и параметри
     *
     * @param string    $event  име на събитието
     * @param array     $args   аргументи на събитието
     * @return mixed (TRUE, FALSE, -1)
     * $status == -1 означава, че никой не е обработил това събитие
     * $status == TRUE означава, че събитието е обработено нормално
     * $status == FALSE означава, че събитието е обработено и 
     *            се изисква спиране на последващите обработки
     */
    function invoke($event, $args = array())
    {
        $method = 'on_' . $event;

        $args1 = array();
        
        $status = -1;
        
        for ($i = 0; $i < count($args); $i++) {
            $args1[$i] =& $args[$i];
        }
        
        array_unshift($args1, &$this);
        
        // Проверяваме дали имаме плъгин(и), който да обработва това събитие
        if (count($this->_plugins)) {
            
            $plugins = array_reverse($this->_plugins);
            
            foreach ($plugins as $plg) {
                if (method_exists($plg, $method)) {
                    
                    $status = TRUE;

                    // Извикваме метода, прехванал обработката на това събитие
                    if (call_user_func_array(array($plg, $method), &$args1) === FALSE) return FALSE;
                }
            }
        }
        
        // Търсим обработвачите на събития по методите на този клас и предшествениците му
        $className = get_class($this);
        
        do {
            if (method_exists($className, $method)) {
                
                $status = TRUE;

                $RM = new ReflectionMethod($className, $method);
                
                if($className == $RM->class) {
                    if (call_user_func_array(array($className, $method), &$args1) === FALSE) {
                        
                        return FALSE;
                    }
                }
            }
            
            $res = strcasecmp($className = get_parent_class($className), __CLASS__);
        } while ($res);
        
        return $status;
    }
    
    
    /**
     * Рутинна процедура, която се задейства, ако извиквания метод липсва
     * Методи, които съдъжат в името си "_" ще бъдат извикани, ако без тази черта,
     * се получава точно името на търсения метод
     */
    function __call($method, $args)
    {
        $missingMethod = TRUE;

    	if (method_exists($this, $method . '_')) {
    		$mtd = $method . '_';
            $missingMethod = FALSE;
    	}

        if (!in_array($method, $this->invocableMethods) && !$mtd) {
            
        }
    	        
        array_unshift($args, &$res);
        
        $beforeStatus = $this->invoke('Before' . $method, &$args);
        
        if ($beforeStatus === FALSE) {
            $res = $args[0];
        } else {
            if ($mtd) {
	        	array_shift($args);
	            $res = call_user_func_array(array(&$this, $mtd), &$args);
	            array_unshift($args, &$res);
            }
            
            $afterStatus = $this->invoke('After' . $method, &$args);
        }
        
        // Очакваме поне един обработвач или самия извикван метод да е сработил
        expect( ($beforeStatus !== -1) || ($afterStatus !== -1) || $mtd, 
                "Missing method " . cls::getClassName($this) . "::{$method}");

        return $res;
    }
    
    
    /**
     * Изпълнява посочения екшън от текущия обект
     * Тази функция се използва за да се генерират събития beforeAction и afterAction
     * По този начин могат да бъдат прихванати извиквания на нови, непознати екшъни
     */
    function action_($act)
    {
        if (!$act) $act = 'default';
        
        $method = 'act_' . $act;
        
        if (!method_exists($this, $method)) {
            error("Липсващ метод:|* $method |на|* " . cls::getClassName($this));
        }
        
        return $this->{$method}();
    }
}