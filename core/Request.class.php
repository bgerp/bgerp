<?php



/**
 * Клас 'core_Request' ['Request'] - Достъп до данните от заявката
 *
 * Могат да се правят вътрешни заявки
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
class core_Request
{
    
    /**
     * Масив от масиви с променлива => стойност
     * Стойностите от по-последните масиви са с по-висок приоритет
     */
    static $vars = array();
    
    /**
     * Масив с имена на променливи, които ще се предават/получават от клиента
     * чрез защита, непозволяваща тяхното манипулиране
     */
    static $protected;
    
    
    /**
     * Функция - флаг, че обектите от този клас са Singleton
     */
    function _Singleton() {}
    
    
    /**
     * Зарежда променливите от заявката в собствен стек
     */
    function init($params = array())
    {
        global $_GET, $_POST, $_COOKIE, $_REQUEST;
        
        // Избягваме кофти-ефекта на magic_quotes
        if (get_magic_quotes_gpc()) {
            self::push(array_map(array(
                        'core_Request',
                        '_stripSlashesDeep'
                    ), $_GET), '_GET');
            self::push(array_map(array(
                        'core_Request',
                        '_stripSlashesDeep'
                    ), $_POST), '_POST');
        } else {
            self::push($_GET, '_GET');
            self::push($_POST, '_POST');
        }
        
        // Ако имаме 'Protected' поле - декодираме го
        $prot = self::get('Protected');
        
        if ($prot) {
            $prot = str::checkHash($prot, 16);
            
            if ($prot) {
                $prot = base64_decode($prot);
                
                if ($prot) {
                    $prot = gzuncompress($prot);
                    
                    if ($prot) {
                        $prot = unserialize($prot);
                        
                        if (is_array($prot)) {
                            self::push($prot);
                        }
                    }
                }
            }
        }
    }
    
    
    /**
     * Премахва ескейпване с '\' в масив рекурсивно
     */
    function _stripSlashesDeep($value)
    {
        $value = is_array($value) ? array_map(array(
                'core_Request',
                '_stripSlashesDeep'
            ), $value) : stripslashes($value);
        
        return $value;
    }
    
    
    /**
     * Задава масив с полета, които ще се изпращат/получават към/от
     * клиента в защитено състояние. Тези полета могат да съдържат и
     * обекти/масиви. Системата гарантира, че клиента трудно може да промени
     * съдържанието на тези полета. По този начин те стават удобни да се предава
     * вътрешно състояние през URL или hidden полета на форма
     */
    static function setProtected($protArr)
    {
        self::$protected = arr::make($protArr, TRUE);
    }
    
    
    /**
     * Премахва от масива всички полета, които са декларирани в setProtected на тяхно
     * място създава нов индекс 'Protected' в който са записани стойностите им
     */
    static function doProtect(&$arr)
    {
        if (self::$protected) {
            foreach (arr::make(self::$protected) as $name) {
                if ($arr[$name]) {
                    $prot[$name] = $arr[$name];
                    unset($arr[$name]);
                }
            }
            
            if (is_array($prot)) {
                $prot = serialize($prot);
                $prot = gzcompress($prot);
                $prot = base64_encode($prot);
                $prot = str::addHash($prot, 16);
                $arr['Protected'] = $prot;
            }
        }
    }
    
    
    /**
     * Връща стойността на указаната променлива. Ако такава липсва в масивите
     * с входни променливи, то връща NULL
     */
    static function get($name, $type = NULL)
    {
        if ($type) {
            $inputType = core_Type::getByName($type);
            $value = self::get($name);
            $value = $inputType->fromVerbal($value);
            
            if ($inputType->error) {
                error("Некоректна стойност за входен параметър", array(
                        'input' => $name,
                        'error' => $inputType->error
                    ));
            } else {
                return $value;
            }
        }
        
        foreach (self::$vars as $arr) {
            if (isset($arr[$name])) {
                return $arr[$name];
            }
        }
        
        return NULL;
    }
    
    
    /**
     * Вкарва в стека масив с входни параметри - "променливи => стойности"
     */
    static function push($array, $name = NULL)
    {
        
        if ($name) {
            $element[$name] = $array;
        } else {
            $element[] = $array;
        }
        
        self::$vars = array_merge($element, self::$vars);
    }
    
    
    /**
     * Маха посочения масив с "променливи => стойности" или последно влезлия
     */
    static function pop($name = NULL)
    {
        if ($name) {
            unset(self::$vars[$name]);
        } else {
            array_shift(self::$vars);
        }
    }
    
    
    /**
     * Изпълнява вътрешна заявка, все едно, че е дошла от URL
     */
    static function forward($vars = array(), $prefix = 'act_')
    {
        $Request = & cls::get('core_Request');
        
        $vars = arr::make($vars, TRUE);
        
        if (count($vars)) {
            $Request->push($vars);
            $mustPop = TRUE;
        }
        
        //  
        if (defined('EF_CTR_NAME')) {
            $ctr = EF_CTR_NAME;
        } else {
            $ctr = $Request->get('Ctr');
            
            if (empty($ctr)) {
                $ctr = "Index";
            }
        }
        
        if (defined('EF_ACT_NAME')) {
            $act = EF_ACT_NAME;
        } else {
            $act = $Request->get('Act');
            
            if (empty($act)) {
                $act = "default";
            }
        }
        
        $method = $prefix . $act;
        
        $ctr = cls::getClassName($ctr);
        
        if (cls::load($ctr, TRUE)) {
            
            $mvc = & cls::get($ctr);
            $content = $mvc->action(strtolower($act));
        } else {
            error("Controller not found: {$ctr}", array(
                    'controller' => $ctr,
                    '$_GET' => $_GET,
                    '$_POST' => $_POST
                ));
        }
        
        if ($mustPop) {
            $Request->pop();
        }
        
        return $content;
    }
}