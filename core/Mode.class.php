<?php

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
     * Записва или прочита стойност на параметър. Ако $read е TRUE
     * тогава само се връща текъщата стойност на параметъра
     */
    function set($name, $value = TRUE, $read = FALSE)
    {
        static $mode;
        
        expect($name, 'Параметъра $name трябва да е непразен стринг', $mode);
        
        if (!is_array($mode)) {
            $mode = core_Session::get(EF_MODE_SESSION_VAR);
            
            if (!is_array($mode)) {
                $mode = array();
            }
        }
        
        if (!$read) {
            $mode[$name] = $value;
        }
        
        return $mode[$name];
    }
    
    
    
    /**
     * @todo Чака за документация...
     */
    function setPermanent($name, $value = TRUE)
    {
        // Запис в статичната памет
        Mode::set($name, $value);
        
        // Логваме, какво се записва в сесията
        // $Logs = cls::get('core_Logs');
        // $Logs->add('core_Mode', NULL, " $name => $value ");
        
        // Запис в сесията
        $pMode = core_Session::get(EF_MODE_SESSION_VAR);
        $pMode[$name] = $value;
        core_Session::set(EF_MODE_SESSION_VAR, $pMode);
    }
    
    
    
    /**
     * Връща стойостта на променлива от обкръжението
     */
    function get($name)
    {
        return Mode::set($name, NULL, TRUE);
    }
    
    
    
    /**
     * Сравнява стоността на променлива от обкръжението
     * с предварително зададена стойност
     */
    function is($name, $value = TRUE)
    {
        return Mode::get($name) == $value;
    }
    
    
    
    /**
     * Връща уникален, случаен ключ валиден по време на сесията
     */
    function getPermanentKey()
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
    function getProcessKey()
    {
        if (!$key = Mode::get('processKey')) {
            $key = str::getRand();
            Mode::set('processKey', $key);
        }
        
        return $key;
    }
    
    
    
    /**
     * Унищожава цялата перманетна информация
     */
    function destroy()
    {
        core_Session::set(EF_MODE_SESSION_VAR, NULL);
    }
}