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
    public static $mode = null;
    
    /**
     * Стек за запазване на старите стойности на параметрите от runtime обкръжението
     */
    public static $stack = array();
    
    
    /**
     * Записва стойност на параметър na runtime обкръжението.
     */
    public static function set($name, $value = true)
    {
        expect($name, 'Параметъра $name трябва да е непразен стринг', self::$mode);
        
        self::prepareMode();
        
        self::$mode[$name] = $value;
        
        return self::$mode[$name];
    }
    
    
    /**
     * Вкарва в runtime променливите указаната двойка име=стойност,
     * като запомня старото и значение, което по-късно може да бъде възстановено с ::pop
     */
    public static function push($name, $value)
    {
        $rec = new stdClass();
        $rec->name = $name;
        $rec->value = self::get($name);
        
        array_unshift(self::$stack, $rec);
        
        self::set($name, $value);
    }
    
    
    /**
     * Връща старото състояние на променливата от runtime-обкръжението
     */
    public static function pop($name = null, $force = null)
    {
        do {
            expect($rec = array_shift(self::$stack));
        } while ($force && $rec->name != $name && count(self::$stack));
        
        
        if ($name) {
            expect($rec->name == $name, "Очаква се Mode::pop('{$rec->name}') а не Mode::pop('{$name}')", self::$stack);
        }
        
        self::set($rec->name, $rec->value);
        
        return $rec->value;
    }
    
    
    /**
     * Запис на стойност в сесията
     */
    public static function setPermanent($name, $value = true)
    {
        // Запис в статичната памет
        static::set($name, $value);
        
        // Запис в сесията, ако потребителския агент не е бот
        if (!log_Browsers::detectBot()) {
            $pMode = core_Session::get(EF_MODE_SESSION_VAR);
            $pMode[$name] = $value;
            core_Session::set(EF_MODE_SESSION_VAR, $pMode);
        }
    }
    
    
    /**
     * Връща стойността на променлива от обкръжението
     */
    public static function get($name, $offset = 0)
    {
        expect($offset <= 0);
        
        if ($offset < 0) {
            foreach (self::$stack as $r) {
                if ($r->name == $name) {
                    $offset++;
                    if ($offset == 0) {
                        return $r->value;
                    }
                }
            }
            
            return;
        }
        
        // Инициализираме стойностите с данните от сесията
        self::prepareMode();
        
        $res = null;
        if (isset(self::$mode[$name])) {
            $res = self::$mode[$name];
        }
        
        return $res;
    }
    
    
    /**
     * Подготвя масива със стойностите на `$mode`
     */
    protected static function prepareMode()
    {
        if (is_null(self::$mode)) {
            self::$mode = core_Session::get(EF_MODE_SESSION_VAR);
            
            if (!is_array(self::$mode)) {
                self::$mode = array();
            }
        }
    }
    
    
    /**
     * Сравнява стойността на променлива от обкръжението
     * с предварително зададена стойност
     */
    public static function is($name, $value = true)
    {
        return Mode::get($name) == $value;
    }
    
    
    /**
     * Връща уникален, случаен ключ валиден по време на сесията
     */
    public static function getPermanentKey()
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
    public static function getProcessKey()
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
    public static function destroy()
    {
        core_Session::set(EF_MODE_SESSION_VAR, null);
        self::$mode = null;
        self::$stack = array();
    }
    
    
    /**
     * Проверява дали режима е 'readOnly'
     */
    public static function isReadOnly()
    {
        // Ако режима е xhtml, printing, pdf, inlineDocument
        if (Mode::is('text', 'xhtml') || Mode::is('printing') || Mode::is('pdf') || Mode::is('inlineDocument')) {
            return true;
        }
        
        return false;
    }
}
