<?php
/**
 * 
 * Базов клас за всички интерфейси
 *
 * @author Stefan Stefanov <stefan.bg@gmail.com>
 *
 */
class core_intf_Base 
{
    
    /**
     * "Слабо" извикване на метод от този интерфейс
     * 
     * Ако метода не е реализиран в класа, реализиращ този интерфейс, дава се шанс на плъгините 
     * на този клас да се изявят (чрез on_$method($mvc, $res, $args))
     *
     * @param string $method име на метод от този интерфейс
     * @param mixed $arg1, $arg2, ... оригиналните параметри, с които е извикан $method
     * 
     */
    protected function call($method)
    {
    	$args = func_get_args();
    	array_shift($args);
    	
    	if (method_exists($this->class, $method)) {
    		return call_user_func_array(array($this->class, $method), $args);
    	}

		$res  = null;
		array_unshift($args, &$res);

		$this->class->invoke($method, $args);
        
        return $res;
    }
}