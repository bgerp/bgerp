<?php

defIfNot('EF_MODE_SESSION_VAR', 'pMode');


/**
 * Клас 'core_Mode' ['Mode'] - Вътрешно състояние по време на хита и сесията
 *
 * Класът core_Mode предоставя функции за четене, писане и проверка на глобални параметри
 * Тези параметри могат да се задават както за текущия хит,
 * така и за текущата сесия.
 *
 * @category   Experta Framework
 * @package    core
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2009 Experta Ltd.
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
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
     *  @todo Чака за документация...
     */
    function setPermanent($name, $value = TRUE)
    {
        // Запис в статичната памет
        Mode::set($name, $value);
        
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
            $key = str::getUniqId();
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
            $key = str::getUniqId();
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