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
    static $mode = NULL;
    
    /**
     * Стек за запазване на старите стойности на параметрите от runtime обкръжението
     */
    static $stack = array();
    
    
    /**
     * Записва стойност на параметър na runtime обкръжението.
     */
    static function set($name, $value = TRUE)
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
    static function push($name, $value)
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
    static function pop($name = NULL, $force = NULL)
    {
        do {
            expect($rec = array_shift(self::$stack));
        } while($force && $rec->name != $name && count(self::$stack));
        
        
        if($name) expect($rec->name == $name, "Очаква се Mode::pop('{$rec->name}') а не Mode::pop('{$name}')", self::$stack);
        
        self::set($rec->name, $rec->value);
        
        return $rec->value;
    }
    
    
    /**
     * Запис на стойност в сесията
     */
    static function setPermanent($name, $value = TRUE)
    {
        // Запис в статичната памет
        static::set($name, $value);
        
        // Запис в сесията, ако потребителския агент не е бот
        if(!log_Browsers::detectBot()) {
            $pMode = core_Session::get(EF_MODE_SESSION_VAR);
            $pMode[$name] = $value;
            core_Session::set(EF_MODE_SESSION_VAR, $pMode);
        }
    }
    
    
    /**
     * Връща стойността на променлива от обкръжението
     */
    static function get($name, $offset = 0)
    {
        expect ($offset <= 0);
        
        if ($offset < 0) {
            foreach (self::$stack as $r) {
                if ($r->name == $name) {
                    $offset++;
                    if ($offset == 0) {
                        return $r->value;
                    }
                }
            }
            
            return NULL;
        }
        
        // Инициализираме стойностите с данните от сесията
        self::prepareMode();
        
        $res = NULL;
        if(isset(self::$mode[$name])) {
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
        self::$mode = NULL;
        self::$stack = array();
    }
    
    
    /**
     * Проверява дали режима е 'readOnly'
     */
    public static function isReadOnly()
    {
    	// Ако режима е xhtml, printing, pdf, inlineDocument
    	if(Mode::is('text', 'xhtml') || Mode::is('printing') || Mode::is('pdf') || Mode::is('inlineDocument')){
    		return TRUE;
    	}
    	
    	return FALSE;
    }
}