<?php



/**
 * Клас 'core_Request' ['Request'] - Достъп до данните от заявката
 *
 * Могат да се правят вътрешни заявки
 *
 *
 * @category  all
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
     * @todo Чака за документация...
     */
    var $vars = array();
    
    
    /**
     * Функция - флаг, че обектите от този клас са Singleton
     */
    function _Singleton() {}
    
    
    /**
     * Зарежда променливите от заявката в собствен стек
     */
    function core_Request()
    {
        global $_GET, $_POST, $_COOKIE, $_REQUEST;
        
        // Избягваме кофти-ефекта на magic_quotes
        if (get_magic_quotes_gpc()) {
            $this->push(array_map(array(
                        $this,
                        '_stripSlashesDeep'
                    ), $_GET), '_GET');
            $this->push(array_map(array(
                        $this,
                        '_stripSlashesDeep'
                    ), $_POST), '_POST');
        } else {
            $this->push($_GET, '_GET');
            $this->push($_POST, '_POST');
        }
        
        // Ако имаме 'Protected' поле - декодираме го
        $prot = $this->get('Protected');
        
        if ($prot) {
            $prot = str::checkHash($prot, 16);
            
            if ($prot) {
                $prot = base64_decode($prot);
                
                if ($prot) {
                    $prot = gzuncompress($prot);
                    
                    if ($prot) {
                        $prot = unserialize($prot);
                        
                        if (is_array($prot)) {
                            $this->push($prot);
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
                $this,
                'stripSlashesDeep'
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
        $Request = & cls::get('core_Request');
        $Request->protected = arr::make($protArr, TRUE);
    }
    
    
    /**
     * Премахва от масива всички полета, които са декларирани в setProtected на тяхно
     * място създава нов индекс 'Protected' в който са записани стойностите им
     */
    function doProtect(&$arr)
    {
        $Request = & cls::get('core_Request');
        
        if ($Request->protected) {
            foreach (arr::make($Request->protected) as $name) {
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
    function get($name, $type = NULL)
    {
        if (is_a($this, 'core_Request')) {
            $Request = & $this;
        } else {
            $Request = & cls::get('core_Request');
        }
        
        if ($type) {
            $inputType = core_Type::getByName($type);
            $value = $Request->get($name);
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

        foreach ($Request->vars as $arr) {
            if (isset($arr[$name])) {
                return $arr[$name];
            }
        }
        
        return NULL;
    }
    
    
    /**
     * Вкарва в стека масив с входни параметри - "променливи => стойности"
     */
    function push($array, $name = NULL)
    {
        if (is_a($this, 'core_Request')) {
            $Request = & $this;
        } else {
            $Request = & cls::get('core_Request');
        }
        
        if ($name) {
            $element[$name] = $array;
        } else {
            $element[] = $array;
        }
        
        $Request->vars = array_merge($element, $Request->vars);
    }
    
    
    /**
     * Маха посочения масив с "променливи => стойности" или последно влезлия
     */
    static function pop($name = NULL)
    {
        $Request = & cls::get('core_Request');
        
        if ($name) {
            unset($Request->vars[$name]);
        } else {
            array_shift($Request->vars);
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