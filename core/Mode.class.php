<?php



/**
 * @todo Чака за документация...
 */
defIfNot('EF_MODE_SESSION_VAR', 'pMode');


/**
 * Клас 'core_Mode' ['Mode'] - Вътрешно състояние по време на хита и сесията
 *
 * Класът core_Mode предоставя функции за четене, писане и проверка на глобални параметри
 * Тези параметри могат да се задават както за текущия хит,
 * така и за текущата сесия.
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
class core_Mode
{
    
    /**
     * Масив в който се записват runtime стойностите на параметрите
     */
    static $mode;
    
    /**
     * Стек за запазване на старите стойности на параметрите от runtime обкръжението
     */
    static $stack = array();
    
    
    /**
     * Записва стойност на параметър na runtime обкръжението.
     */
    static function set($name, $value = TRUE)
    {
        
        expect($name, 'Параметъра $name трябва да е непразен стринг', $mode);
        
        self::$mode[$name] = $value;
        
        return self::$mode[$name];
    }
    
    
    /**
     * Вкарва в runtime променливите указаната двойка име=стойност,
     * като запомня старото и значение, което по-късно може да бъде възстановено с ::pop
     */
    static function push($name, $value)
    {
        $rec = new stdClass();
        $rec->name = $name;
        $rec->value = self::get($name);
        
        self::$stack[] = $rec;
        self::set($name, $value);
    }
    
    
    /**
     * Връща старото състояние на променливата от runtime-обкръжението
     */
    static function pop($name = NULL)
    {
        $rec = self::$stack[count(self::$stack)-1];
        
        if($name) expect($rec->name = $name);
        
        self::set($rec->name, $rec->value);
    }
    
    
    /**
     * Запис на стойност в сесията
     */
    static function setPermanent($name, $value = TRUE)
    {
        // Запис в статичната памет
        Mode::set($name, $value);
        
        // Запис в сесията
        $pMode = core_Session::get(EF_MODE_SESSION_VAR);
        $pMode[$name] = $value;
        core_Session::set(EF_MODE_SESSION_VAR, $pMode);
    }
    
    
    /**
     * Връща стойността на променлива от обкръжението
     */
    static function get($name)
    {
        // Инициализираме стойностите с данните от сесията
        if (!is_array(self::$mode)) {
            
            self::$mode = core_Session::get(EF_MODE_SESSION_VAR);
            
            if (!is_array(self::$mode)) {
                self::$mode = array();
            }
        }
        
        return self::$mode[$name];
    }
    
    
    /**
     * Сравнява стойността на променлива от обкръжението
     * с предварително зададена стойност
     */
    static function is($name, $value = TRUE)
    {
        return Mode::get($name) == $value;
    }
    
    
    /**
     * Връща уникален, случаен ключ валиден по време на сесията
     */
    static function getPermanentKey()
    {
        if (!$key = Mode::get('permanentKey')) {
            $key = str::getRand();
            Mode::setPermanent('permanentKey', $key);
        }
        
        return $key;
    }
    
    
    /**
     * Връща уникален ключ валиден в рамките на текущия процес
     */
    static function getProcessKey()
    {
        if (!$key = Mode::get('processKey')) {
            $key = str::getRand();
            Mode::set('processKey', $key);
        }
        
        return $key;
    }
    
    
    /**
     * Унищожава цялата перманентна информация
     */
    static function destroy()
    {
        core_Session::set(EF_MODE_SESSION_VAR, NULL);
    }
}