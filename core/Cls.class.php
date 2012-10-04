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
            if($className->className) {
                
                return $className->className;
            } else {
                
                return get_class($className);
            }
        }
        
        // Ако името е число, тогава го вземаме от coreClass
        if(is_numeric($className)) {
            $Classes = cls::get('core_Classes');
            $className = $Classes->fetchField($className, 'name');
            
            if(!$className) return FALSE;
        }
        
        // Ако се използва съкратено име, то името на приложението
        // се прибавя като приставка и долна черта отпред
        if (strpos($className, '_') === FALSE) {
            $className = EF_APP_CODE_NAME . '_' . $className;
        }
        
        // Капитализираме буквата след последната черта
        if(($last = strrpos($className, '_')) > 0) {
            if ($last !== FALSE && $last < strlen($className)) {
                $className{$last + 1} = strtoupper($className{$last + 1});
            } else {
                error('Incorrect class name', $className);
            }
        }
        
        return $className;
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
                error("Няма такъв клас", "'{$className}'");
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
                error("Некоректно име на клас", "'{$className}'");
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
                error("Файлът с кода на класа не съществува или не е четим", $fileName);
            }
            
            return FALSE;
        }
        
        // Включваме файла
        if(!include_once($filePath)) {
            error("Не може да бъде парсиран файла", "'{$className}'  in '{$fileName}'");
        }
        
        // Проверяваме дали включения файл съдържа търсения клас
        if (!class_exists($fullClassName, FALSE)) {
            
            if (!$silent) {
                error("Не може да се намери класа в посочения файл", "'{$className}'  in '{$fileName}'");
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
                core_Cls::$singletons[$class] = &cls::createObject($class, $initArr);
            }
            
            $obj = &core_Cls::$singletons[$class];
        } else {
            $obj = &cls::createObject($class, $initArr);
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
            $Plugins->attach($obj);
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
        if(is_scalar($class)) {
            $classObj = cls::get($class);
        } else {
            $classObj = $class;
        }
        
        // Очакваме, че $classObj е обект
        expect(is_object($classObj), $class);
        
        $classObj->interfaces = arr::make($classObj->interfaces, TRUE);
        
        return isset($classObj->interfaces[$interface]) ;
    }
    
    
    /**
     * Връща заглавието на класа от JavaDoc коментар или от свойството $title
     */
    static function getTitle($class)
    {
        
        try {
            $rfl = new ReflectionClass($class);
        } catch(ReflectionException $e) {
            bp($e->getMessage());
        }
        
        $comment = $rfl->getDocComment();
        
        $comment = trim(substr($comment, 3, -2));
        
        $lines = explode("\n", $comment);
        
        foreach($lines as $l) {
            $l = ltrim($l, "\n* \r\t");
            
            if(!$firstLine && $l) {
                $firstLine = $l;
            }
            
            if(strpos($l, '@title') === 0) {
                $titleLine = trim(ltrim(substr($l, 6), ':'));
            }
        }
        
        if($titleLine) return $titleLine;
        
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
}

// Съкратено име, за по-лесно писане
class_alias('core_Cls', 'cls');