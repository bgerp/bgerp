<?php



/**
 * Клас 'core_BaseClass' - прототип за класове поддържащи събития и инициализиране
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
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
     * Списък от заредените инстанции на плъгини
     * 
     * @var array
     */
    protected $_plugins = array();
    
    
    /**
     * Списък от поддъраните от класа интерфейси
     * 
     * @var string|array
     */
    public $interfaces;
    
    
    /**
     * Параметри за инициализиране на обекта
     */
    public $params = array();
    

    /**
     * Кеш с обработвачите на събития в обекта
     */
    private $_listenerCache = array();


    /**
     * Конструктор. Дава възможност за инициализация
     */
    function __construct($params = NULL)
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
                $this->{$name} = & $params[$name];
            }
        }
    }
    
    
    /**
     * Зарежда само един клас, плъгин или MVC в полета-свойства на обекта
     *
     * @param string $name име под което класът трябва да бъде зареден, ако е плъгин или mvc
     * @param string $class името на класа
     */
    function loadSingle($name, $class = '')
    {
        expect($name);
        
        if(!$class) {
            $class = $name;
        }

        $class = cls::getClassName($class);
        
        // Ако е подклас на core_Mvc, записваме го като член на този клас 
        if (!isset($this->{$name}) && cls::isSubclass($class, 'core_Mvc')) {
            $this->{$name} = &cls::get($class);
        }
        
        // Ако има интерфейс на плъгин, записваме го в масива на плъгините
        if (!isset($this->_plugins[$name]) && cls::isSubclass($class, 'core_Plugin')) {
            $this->_plugins[$name] = &cls::get($class);
            
            // Ако има плъгини закачени за плъгина
            if (isset($this->_plugins[$name]->loadInMvc)) {
                $this->load($this->_plugins[$name]->loadInMvc);
            }
            
            $this->_listenerCache = array();
        }
    }


    /**
     * Премахва посочения плъгин плъгин
     *
     * @param string $name Име на клас, съдържащ плъгина или името под което е регистриран
     */
    function unloadPlugin($name)
    {
        if(isset($this->_plugins[$name])) {
            unset($this->_plugins[$name]);
            $this->_listenerCache = array();
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
     * се изисква спиране на последващите обработки
     */
    function invoke($event, $args = array())
    {
        $method = 'on_' . strtolower($event);
        
         // Ако нямаме - генерираме кеша с обработвачите
        if(!isset($this->_listenerCache[$method])) {
            
            $this->_listenerCache[$method] = array();

            // Проверяваме дали имаме плъгин(и), който да обработва това събитие
            if (count($this->_plugins)) {
                $plugins = array_reverse($this->_plugins);
                foreach ($plugins as $plg) {
                    if (method_exists($plg, $method)) {
                        $this->_listenerCache[$method][] = $plg;
                    }
                }
            }
            
            // Търсим обработвачите на събития по методите на този клас и предшествениците му
            $className = get_class($this);
            $first = TRUE;
            do {
                if (method_exists($className, $method)) {
                    $RM = new ReflectionMethod($className, $method);
                    if($className == $RM->class) {
                         $this->_listenerCache[$method][] = $first ? $this : $className;
                    }
                }
                $first = FALSE;
                $flag = strcasecmp($className = get_parent_class($className), __CLASS__);
            } while ($flag);

        }
        
        // Използваме кеша за извикаване на обработвачите
        if(count($this->_listenerCache[$method])) {
            
            $args1 = array(&$this);
            $cntArgs = count($args);
            for ($i = 0; $i < $cntArgs; $i++) {
                $args1[] = & $args[$i];
            }

            foreach($this->_listenerCache[$method] as $subject) {
                if(call_user_func_array(array($subject, $method),  $args1) === FALSE) return FALSE;
            }

            return TRUE;
        }

        return -1;
    }



    /**
     * Рутинна процедура, която се задейства, ако извиквания метод липсва
     * Методи, които съдържат в името си "_" ще бъдат извикани, ако без тази черта,
     * се получава точно името на търсения метод
     */
    function __call($method, $args)
    {
        
        $argsHnd = array(&$res);
        $argsMtd = array();
        
        $cntArgs = count($args);
        for ($i = 0; $i < $cntArgs; $i++) {
            $argsHnd[] = & $args[$i];
            $argsMtd[] = & $args[$i];
        }
        
        /**
         *     $args:            $args[0] |   $args[1] | ... |   $args[n]
         *  $argsMtd:          & $args[0] | & $args[1] | ... | & $args[n]
         *  $argsHnd: & $res | & $args[0] | & $args[1] | ... | & $args[n]
         */
        
        $beforeStatus = $this->invoke('Before' . $method,  $argsHnd);
        
        if ($beforeStatus !== FALSE) {
            if (method_exists($this, $mtd = $method . '_')) {
                $flag = TRUE;
                $res = call_user_func_array(array(&$this, $mtd),  $argsMtd);
            }
            
            $afterStatus = $this->invoke('After' . $method, $argsHnd);
        }
        
        // Очакваме поне един обработвач или самия извикван метод да е сработил
        if($beforeStatus === -1 && $afterStatus === -1 && !$flag) {
            expect(FALSE, "Missing method " . cls::getClassName($this) . "::{$method}", $beforeStatus, $afterStatus, $mtd);
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
            error('404 Липсваща страница', array("Липсващ метод: {$method} на " . cls::getClassName($this)));
        }
        
        $res = $this->{$method}();
        
        return $res;
    }
    
    
    /**
     * Помощен метод за определяне дали класа поддържа зададен интерфейс.
     * 
     * @param string $interface
     * @return string|boolean
     */
    public function getInterface($interface)
    {
        $this->interfaces = arr::make($this->interfaces, TRUE);
        
        if (!isset($this->interfaces[$interface])) {
            return FALSE;
        }
        
        return $this->interfaces[$interface];
    }
    
    
    /**
     * Помощен метод за деклариране на нов интерфейс на класа
     * 
     * Ако не е деклариран интерфейса, метода го добавя, иначе не прави нищо
     * 
     * @param string $interface
     * @param string $implementationClass име на клас-имплементация
     */
    public function declareInterface($interface, $implementationClass = NULL)
    {
        if (!isset($implementationClass)) {
            $implementationClass = $interface;
        }
        
        if ($this->getInterface($interface) !== FALSE) {
            return;
        }
        
        $this->interfaces[$interface] = $implementationClass;
    }
    
    
    /**
     * Връща масив от заредените инстанции на плъгини
     * @return array - масив от инстанции на плъгини
     */
    public function getPlugins()
    {
    	return $this->_plugins;
    }
    
    
    /**
     * Дали класа има закачен плъгин
     * 
     * @param string $name - име на плъгин за който проверяваме
     * @return boolean
     */
    public function hasPlugin($name)
    {
    	$res = isset($this->_plugins[$name]);
    	
    	return $res;
    }


    /**
     * Създава инстанция на себе си в посочената променлива
     */
    public static function createIfNotExists(&$var)
    {
        $me = get_called_class();

        if(isset($var)) {
            expect($var instanceOf $me, $var);
            return;
        }

        $var = cls::get($me);
    }
}
