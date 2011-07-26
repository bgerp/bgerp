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
     * Конструктор. Дава възможност за инициализация
     */
    function core_BaseClass($params = NULL)
    {
        if(isset($params)) {
            $this->init($params);
        }
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
     */
    function invoke($event, $args = array())
    {
        $method = 'on_' . $event;
        
        $args1 = array();
        
        for ($i = 0; $i < count($args); $i++) {
            $args1[$i] =& $args[$i];
        }
        
        array_unshift($args1, &$this);
        
        // Проверяваме дали имаме плъгин(и), който да обработва това събитие
        if (count($this->_plugins)) {
            
            $plugins = array_reverse($this->_plugins);
            
            foreach ($plugins as $plg) {
                if (method_exists($plg, $method)) {
                    
                    // Извикваме метода, прехванал обработката на това събитие
                    if (call_user_func_array(array($plg, $method), &$args1) === FALSE) return FALSE;
                }
            }
        }
        
        // Търсим обработвачите на събития по методите на този клас и предшествениците му
        $className = get_class($this);
        
        do {
            if (method_exists($className, $method)) {
                
                $RM = new ReflectionMethod($className, $method);
                
                if($className == $RM->class) {
                    if (call_user_func_array(array($className, $method), &$args1) === FALSE) {
                        
                        return FALSE;
                    }
                }
            }
            
            $res = strcasecmp($className = get_parent_class($className), __CLASS__);
        } while ($res);
        
        return TRUE;
    }
    
    
    /**
     * Рутинна процедура, която се задейства, ако извиквания метод липсва
     * Методи, които съдъжат в името си "_" ще бъдат извикани, ако без тази черта,
     * се получава точно името на търсения метод
     */
    function __call($method, $args)
    {
        if (!$this->dilatableMethods) {
            foreach (get_class_methods($this) as $mtd) {
                if (($i = strpos($mtd, '_')) && (strpos($mtd, 'on_') !== 0)) {
                    $this->dilatableMethods[strtolower(substr($mtd, 0, $i) . substr($mtd, $i + 1))] = $mtd;
                }
            }
        }
        
        $mtd = $this->dilatableMethods[strtolower($method)];
        
        if (!$mtd) {
            halt("Missing method " . cls::getClassName($this) . "::{$method}");
        }
        
        array_unshift($args, &$res);
        
        if ($this->invoke('Before' . $method, &$args) === FALSE) {
            $res = $args[0];
        } else {
            array_shift($args);
            $res = call_user_func_array(array(&$this, $mtd), &$args);
            array_unshift($args, &$res);
            
            $this->invoke('After' . $method, &$args);
        }
        
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