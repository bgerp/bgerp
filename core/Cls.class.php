<?php



/**
 * Клас 'core_Cls' ['cls'] - Функции за работа с класове
 *
 * Класът core_Cls предоставя няколко полезни функции:
 * - динамично зареждане на класове и създаване на обекти
 * - поддържа информация за оригиналните имена на класовете
 * - динамично свързва плъгините с новосъздадените обекти
 * - намира дали даден клас/клас на обект е подклас на друг
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
class core_Cls
{
    
    /**
     * Масив в който се съхраняват всички инстанси на сингълтон обекти
     */
    static $singletons = array();
    
    
    /**
     * Връща името на класа, от който е този обект или
     * прави стринга да отговаря на стандартите за име
     * на клас във фреймуърка:
     * - минимум един префикс (този на пакета)
     * - главна буква преди собственото име на класа
     *
     * @param mixed $class
     * @param boolean $save
     * @return string
     */
    static function getClassName($className)
    {
        if(is_object($className)) {
            if(isset($className->className)) {

                return $className->className;
            } else {

                return get_class($className);
            }
        }

        static $classNames = array();
        
        if (!stripos($className, '_')) {
            $className = ucfirst($className);
        }
        
        $cln = $className;

        if(!isset($classNames[$cln])) {
        
            // Ако името е число, тогава го вземаме от coreClass
            if(is_numeric($className)) {
                $className = core_Classes::getName($className);
                
                if(!$className) return FALSE;
            }
            
            // Ако се използва съкратено име, то името на приложението
            // се прибавя като приставка и долна черта отпред
            if (($last = strrpos($className, '_')) === FALSE) {
                $className = EF_APP_CODE_NAME . '_' . $className;
            } elseif($last > 0) {
                // Капитализираме буквата след последната черта
                if ($last < strlen($className)) {
                    $className{$last + 1} = strtoupper($className{$last + 1});
                } else {
                    // Некоректно има на клас
                    error("@Некоректно има на клас", $className);
                }
            }

            $classNames[$cln] = $className;
        }
        
        return $classNames[$cln];
    }
    
    
    /**
     * Зарежда указания клас. Името на класа $class може да съдържа само
     * букви, цифри и долна черта
     *
     * Пътя и суфикса не се проверяват за допустимост
     *
     * @param string $class
     * @param string $patch
     * @param string $suffix
     * @return mixed
     */
    static function load($className, $silent = FALSE, $suffix = ".class.php")
    {
        $fullClassName = cls::getClassName($className);
        
        if($fullClassName === FALSE) {
            
            if (!$silent) {
                // Няма такъв клас
                error('Няма такъв клас', $className);
            }
            
            return FALSE;
        }
        
        // Проверяваме дали класа вече не съществува, и ако е така не правим нищо
        if (class_exists($fullClassName, FALSE)) {
            
            return TRUE;
        }
        
        // Проверяваме дали името на класа съдържа само допустими символи
        if (!preg_match("/^[a-z0-9_]+$/i", $fullClassName)) {
            
            if (!$silent) {
                error("@Некоректно име на клас", "'{$className}'");
            }
            
            return FALSE;
        }
        
        // Определяме името на файла, в който трябва да се намира класа
        $fileName = str_replace('_', '/', $fullClassName) . $suffix;
        
        // Определяме пълния път до файла, където трябва да се намира класа
        $filePath = getFullPath($fileName);
        
        // Връщаме грешка, ако файлът не съществува или не може да се чете
        if (!$filePath) {
            
            if (!$silent) {
                error("@Файлът с кода на класа не съществува или не е четим", "'{$fileName}'");
            }
            
            return FALSE;
        }
        
        // Включваме файла
        if(!include_once($filePath)) {
            error("@Не може да бъде парсиран файла", "'{$className}'", "'{$fileName}'");
        }
        
        // Проверяваме дали включения файл съдържа търсения клас
        if (!class_exists($fullClassName, FALSE)) {
            
            if (!$silent) {
                error("@Не може да се намери класа в посочения файл", "'{$className}'", "'{$fileName}'");
            }
            
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    /**
     * Връща инстанция на обект от указания клас
     * Ако класът има интерфейс "Singleton", то ако няма преди създаден
     * обект - създава се, а ако има връща се вече създадения
     *
     * @param string $class
     * @param array  $initArr
     * @return object
     */
    static function &get($class, $initArr = NULL)
    {
        $class = cls::getClassName($class);
        
        cls::load($class);
        
        if (cls::isSingleton($class)) {
            if (!isset(core_Cls::$singletons[$class])) {
                core_Cls::$singletons[$class] = new stdClass();
                core_Cls::$singletons[$class] = cls::createObject($class, $initArr);
                
                // Ако класа е наследник на core_BaseClass предизвикваме събитие, че е бил инстанциран
                if(core_Cls::$singletons[$class] instanceof core_BaseClass){
                	core_Cls::$singletons[$class]->invoke('AfterInstance');
                }
            }
            
            $obj = &core_Cls::$singletons[$class];
        } else {
            $obj = &cls::createObject($class, $initArr);
        }

        if(isset($obj->newClassName)) {

            return self::get($obj->newClassName, $initArr);
        }
        
        return $obj;
    }
    
    
    /**
     * Връща инстанция на обект от указания клас
     * Ако класът има интерфейс "Singleton", то ако няма преди създаден
     * обект - създава се, а ако има връща се вече създадения
     *
     * @param string $class
     * @param array  $initArr
     * @return object
     */
    static function &createObject($class, &$initArr = NULL)
    {
        $obj = new $class;
        
        // Прикача плъгините, които са регистрирани за този клас
        $Plugins = cls::get('core_Plugins');
        
        if (is_a($Plugins, 'core_Plugins')) {
            try {
            	$Plugins->attach($obj);
            } catch (core_exception_Expect $e) {}
        }
        
        // Ако има допълнителни параметри - използва ги за инициализиране
        if (is_callable(array($obj, 'init'))) {
            
            $res = call_user_func(array(&$obj, 'init'), $initArr);
            
            // Ако в резултат на инициализацията е върнат 
            // обект, то той се връща като резултат
            if (is_object($res)) {
            	
                return $res;
            }
        }
        
        return $obj;
    }
    
    
    /**
     * Проверява дали даден клас трябва да е сингълтон
     *
     * @param string $class
     * @param string $interface
     * @return boolean
     */
    static function isSingleton($class)
    {
        return is_callable(array($class, '_Singleton'));
    }
    
    
    /**
     * Връща истина, ако указаният клас е подклас на класа
     * посочен във втория стрингов параметър
     * за разлика от вградените функции работи със стрингови параметри
     *
     * @param mixed  $class
     * @param string $parrentClass
     * @return boolean
     */
    static function isSubclass($class, $parrentClass)
    {
        if (is_object($class)) {
            $className = strtolower(get_class($class));
        } else {
            cls::load($class);
            $className = strtolower($class);
        }
        
        $parrentClassLw = strtolower($parrentClass);
        
        do {
            if ($parrentClassLw === $className)
            
            return TRUE;
        } while (FALSE != ($className = strtolower(get_parent_class($className))));
        
        return FALSE;
    }
    
    
    /**
     * Вика функция с аргументи посочения масив
     * Формат1 за името на функцията: име_на_клас->име_на_метод
     * Формат2 за името на функцията: име_на_клас::име_на_статичен_метод
     */
    static function callFunctArr($name, $arr)
    {
        $call = explode("->", $name);
        
        if (count($call) == 2) {
            $call[0] = cls::get($call[0]);
        } else {
            $call = explode("::", $name);
            
            $call = $name;
        }
        
        return call_user_func_array($call, $arr);
    }
    
    
    /**
     * Връща обект - адаптер за интерфейса към посочения клас
     */
    static function getInterface($interface, $class, $params = NULL, $silent = FALSE)
    {
        if(is_scalar($class)) {
            $classObj = cls::get($class, $params);
        } else {
            $classObj = $class;
        }
        
        // Очакваме, че $classObj е обект
        expect(is_object($classObj), $class);
        
        $classObj->interfaces = arr::make($classObj->interfaces, TRUE);
        
        if(isset($classObj->interfaces[$interface])) {
            $interfaceObj = cls::get($classObj->interfaces[$interface]);
        } elseif(!$silent) {
            expect(FALSE, "Адаптера за интерфейса {$interface} не се поддържа от класа " . cls::getClassName($class));
        } else {
            return FALSE;
        }
        
        $interfaceObj->class = $classObj;
        
        return $interfaceObj;
    }
    
    
    /**
     * Проверява дали посочения клас има дадения интерфейс
     */
    static function haveInterface($interface, $class)
    {
        if(is_numeric($interface)){
    		
    		// Ако е подадено ид, намираме името на интерфейса с това ид
    		$interface = core_Interfaces::fetchField($interface, 'name');
    	}
    	
    	/* @var $classObj core_BaseClass */
    	
        if(is_scalar($class)) {
            $classObj = cls::get($class);
        } else {
            $classObj = $class;
        }
        
        // Очакваме, че $classObj е обект
        expect(is_object($classObj), $class);
        
        return $classObj->getInterface($interface);
    }
    
    
    /**
     * Връща заглавието на класа от JavaDoc коментар или от свойството $title
     */
    static function getTitle($class)
    {
        
        $rfl = new ReflectionClass($class);
        
        $comment = $rfl->getDocComment();
        
        $comment = trim(substr($comment, 3, -2));
        
        $lines = explode("\n", $comment);
        
        foreach($lines as $l) {
            $l = ltrim($l, "\n* \r\t");
            
            if(!isset($firstLine) && $l) {
                $firstLine = $l;
            }
            
            if(strpos($l, '@title') === 0) {
                $titleLine = trim(ltrim(substr($l, 6), ':'));
            }
        }
        
        if(isset($titleLine)) return $titleLine;
        
        $obj = cls::get($class);
        
        if($obj->title) return $obj->title;
        
        return $firstLine;
    }
    
    
    /**
     * Генерира последователно 'shutdown' събития във всички singleton класове
     */
    static function shutdown()
    {
        if(count(core_Cls::$singletons)) {
            foreach(core_Cls::$singletons as $name => $instance) {
                if($instance instanceof core_BaseClass) {
                    $instance->invoke('shutdown');
                }
            }
        }
    }
    
    
    /**
     * Показва дали даден клас имплементира даден метод.
     * проверява дали:
     * 1. съществуват методи  '<$methodName>' или '<$methodName>_'
     * 2. дефолт метод с име 'on_After<$methodName>'
     * 3. проверява във всеки плъгин закачен към класа дали
     * има метод 'on_After<$methodName>'
     * 
     * @param mixed $class - име на клас или негова инстанция
     * @param string $methodName - име на метода
     * @return boolean TRUE/FALSE - дали дадения метод е имплементиран
     */
    public static function existsMethod($class, $methodName)
    {
    	if(is_scalar($class)) {
            $classObj = cls::get($class);
        } else {
            $classObj = $class;
        }
        
        // Очакваме, че $classObj е обект
        expect(is_object($classObj), $class);
        
        // Ако има такъв метод в класа или неговото име с долна черта
        if(method_exists($classObj, $methodName) || method_exists($classObj, "{$methodName}_")){
        	
            return TRUE;
        }
        
        // Ако има on_After метод по подразбиране
        if(method_exists($classObj, "on_After{$methodName}")){
        	
            return TRUE;
        }
        
        if(is_a($classObj, 'core_BaseClass')) {
            $plugins = $classObj->getPlugins();
            
            if(count($plugins)){
                foreach ($plugins as $name){
                    if(method_exists($name, "on_After{$methodName}")){
                        return TRUE;
                    }
                }
            }
        }
        
        return FALSE;
    }
    
    
    /**
     * Връща всички методи, които могат да се извикат от даден клас.
     * Връща неговите методи, наследените методи и методите от
     * неговите плъгини
     * @param mixed $class - име или инстанция на клас
     * @param boolean $onlyStatic
     * 
     * @return param $array - всички достъпни методи за класа
     */
    public static function getAccessibleMethods($class, $onlyStatic = FALSE)
    {
    	expect($Class = static::get($class));
    	$accessibleMethods = array();
    	$Ref = new ReflectionClass($class);
    	
    	$refMet = ReflectionMethod::IS_ABSTRACT | ReflectionMethod::IS_FINAL | ReflectionMethod::IS_PRIVATE | ReflectionMethod::IS_PROTECTED | ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC;
    	
    	if ($onlyStatic) {
    	    $refMet = ReflectionMethod::IS_STATIC;
    	}
    	
    	$methodsArr = $Ref->getMethods($refMet);
    	
    	// Нормализиране на името на методите
    	if(count($methodsArr)){
	    	foreach ($methodsArr as $m){
	    		$name = str_replace("on_Before", "", $m->name);
	    		$name = str_replace("on_After", "", $name);
	    		$name = rtrim($name, '_');
	    		$name = lcfirst($name);
	    		$accessibleMethods[$name] = $name;
	    	}
    	}
    	
    	// За всеки закачен плъгин (ако има) рекурсивно се извиква ф-ята
    	if(method_exists($Class, 'getPlugins')){
	    	$plugins = $Class->getPlugins();
	    	if(count($plugins)){
	    		foreach ($plugins as $name => $Plugin){
	    			$plgMethodsArr = static::getAccessibleMethods($Plugin, $onlyStatic);
	    			
	    			// Мърджване на методите на плъгина с тези на класа
	    			$accessibleMethods = array_merge($accessibleMethods, $plgMethodsArr);
	    		}
	    	}
    	}
    	
    	return  $accessibleMethods;
    }
    
    
    /**
     * Връща заредените класове-сингълтони
     */
    public static function getSingletons()
    {
    	return count(self::$singletons) ? self::$singletons : NULL;
    }
}

// Съкратено име, за по-лесно писане
class_alias('core_Cls', 'cls');