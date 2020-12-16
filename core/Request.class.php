<?php


/**
 * Клас 'core_Request' ['Request'] - Достъп до данните от заявката
 *
 * Могат да се правят вътрешни заявки
 *
 *
 * @category  ef
 * @package   core
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class core_Request
{
    /**
     * Масив от масиви с променлива => стойност
     * Стойностите от по-последните масиви са с по-висок приоритет
     */
    public static $vars = array();
    
    
    /**
     * Масив с променливи, които трябва да се игнорират при вземането с getParams()
     */
    protected static $ignoreArr = array();
    
    
    /**
     * Масив с имена на променливи, които ще се предават/получават от клиента
     * чрез защита, непозволяваща тяхното манипулиране
     */
    public static $protected = array();
    
    
    /**
     * Функция - флаг, че обектите от този клас са Singleton
     */
    public function _Singleton()
    {
    }
    
    
    /**
     * Зарежда променливите от заявката в собствен стек
     */
    public function init($params = array())
    {
        global $_GET, $_POST, $_COOKIE, $_REQUEST;

        self::push($_GET, '_GET', false, true);
        self::push($_POST, '_POST', false, true);

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
                            self::push($prot, 'protected', true);
                        }
                    }
                }
            }
        }
        
        // Декодира защитеното id
        if (($id = self::get('id')) && ($ctr = self::get('Ctr'))) {
            $id = self::unprotectId($id, $ctr);
            self::push(array('id' => $id));
        }
    }
    
    
    /**
     * Декодира защитеното id
     */
    public static function unprotectId($id, $mvc)
    {
        // Има вероятност да се подаден несъществуващ клас
        
        if (is_string($mvc) && cls::load($mvc, true)) {
            $mvc = cls::get($mvc);
            
            return $mvc->unprotectId($id);
        }
        
        return false;
    }
    
    
    /**
     * Връща сесийно валиден хеш на подаденото съдържание
     */
    public static function getSessHash($c, $len = 4)
    {
        $res = substr(base64_encode(md5(Mode::getPermanentKey() . $c)), 0, $len);
        
        return $res;
    }
    
    
    /**
     * Проверка дали заявката съдържа код за сесийно потвърждаване
     */
    public static function isConfirmed()
    {
        $id = self::get('id');
        $act = self::get('Act');
        
        if (self::get('Cf') === self::getSessHash($act . $id)) {
            
            return true;
        }
        
        return false;
    }
    
    
    /**
     * Премахва ескейпване с '\' в масив рекурсивно
     */
    public function _stripSlashesDeep($value)
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
    public static function setProtected($protArr)
    {
        self::$protected += arr::make($protArr, true);
    }
    
    
    /**
     * Премахва защитени полета от урл-то
     *
     * @param mixed $protArr - масив с полета за премахване
     *
     * @return void
     */
    public static function removeProtected($protArr)
    {
        $protArr = arr::make($protArr, true);
        
        foreach ($protArr as $value) {
            unset(self::$protected[$value]);
        }
    }
    
    
    /**
     * Премахва от масива всички полета, които са декларирани в setProtected на тяхно
     * място създава нов индекс 'Protected' в който са записани стойностите им
     */
    public static function doProtect(&$arr)
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
        
        // Защита на ИД-то
        if ($arr['id'] && $arr['Ctr']) {
            $mvc = cls::get($arr['Ctr']);
            
            $arr['id'] = $mvc->protectId($arr['id']);
        }
    }
    
    
    /**
     * Връща стойността на указаната променлива. Ако такава липсва в масивите
     * с входни променливи, то връща NULL
     */
    public static function get($name, $type = null)
    {
        $value = null;
        
        foreach (self::$vars as $key => $arr) {
            if (self::$protected[$name] && ($key == '_POST' || $key == '_GET')) {
                continue;
            }
            
            if (isset($arr[$name])) {
                $value = $arr[$name];
                break;
            }
        }
        
        if ($type) {
            $inputType = core_Type::getByName($type);
            $value = $inputType->fromVerbal($value);
            if ($inputType->error) {
                error('@Некоректна стойност за входен параметър', $name, $inputType->error);
            }
        }
        
        return $value;
    }
    
    
    /**
     * Връща масив от стойностите на променливите, чието име започва с $nameStart
     */
    public static function getVarsStartingWith($nameStart)
    {
        $res = array();
        
        foreach (self::$vars as $key => $arr) {
            foreach ($arr as $name => $val) {
                if (strpos($name, $nameStart) === 0) {
                    if (!isset($res[$name])) {
                        $res[$name] = $val;
                    }
                }
            }
        }
        
        return $res;
    }
    
    
    /**
     * Връща масив с всички параметри в рекуеста,
     * като по - началните в стека с по - голямо предимство
     *
     * @return array
     */
    public static function getParams($push = '')
    {
        $paramsArr = array();
        
        foreach ((array) self::$vars as $dummy => $arr) {
            if ($push && ("{$push}" != "{$dummy}")) {
                continue;
            }
            
            foreach ((array) $arr as $name => $val) {
                
                // Ако преди не е сетната стойността и не е игнорирана, тогава я добавяме в масива
                if (!isset($paramsArr[$name]) && !isset(self::$ignoreArr[$name])) {
                    $paramsArr[$name] = $val;
                }
            }
        }
        
        return $paramsArr;
    }
    
    
    /**
     * Вкарва в стека масив с входни параметри - "променливи => стойности"
     */
    public static function push($array, $name = null, $unShift = false, $mustValidUrlHash = false)
    {
        self::checkUrlHash($array, $mustValidUrlHash);
        
        if ($name) {
            $element[$name] = $array;
        } else {
            $element[count(self::$vars)] = $array;
        }
        
        if ($unShift) {
            self::$vars = array_merge(self::$vars, $element);
        } else {
            self::$vars = array_merge($element, self::$vars);
        }
    }
    
    
    /**
     * Добавя елементи които да се игнораират при вземането с getParams
     *
     * @param array $array
     */
    public static function ignoreParams($array)
    {
        self::$ignoreArr = array_merge($array, self::$ignoreArr);
    }
    
    
    /**
     * Нулира масива за игнориране
     */
    public static function resetIgnoreParams()
    {
        self::$ignoreArr = array();
    }
    
    
    /**
     * Маха посочения масив с "променливи => стойности" или последно влезлия
     */
    public static function pop($name = null)
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
    public static function forward($vars = array(), $prefix = 'act_')
    {
        static $count = 0;
        $count++;
        $varsName = 'forward' . $count;
        
        // Преобразуваме от поредни към именовани параметри
        if (isset($vars[0]) && !isset($vars['Ctr'])) {
            $vars['Ctr'] = $vars[0];
        }
        if (isset($vars[1]) && !isset($vars['Act'])) {
            $vars['Act'] = $vars[1];
        }
        if (isset($vars[2]) && !isset($vars['id'])) {
            $vars['id'] = $vars[2];
        }
        
        $point = self::get('Ctr') . '::' . self::get('Act') . '::' . self::get('id');
        Debug::log('Forward => ' . $point);
        
        try {
            // Ако не е бил сетнат
            if (!Mode::get('hitTime')) {
                
                // Записваме времето на извикване
                Mode::set('hitTime', dt::mysql2timestamp());
            }
        } catch (Exception $e) {
        }
        
        $Request = & cls::get('core_Request');
        
        $ctr = $Request::get('Ctr');
        
        // Проверяваме за криптиран линк
        if (!$Request::get('Act') &&
            strlen($ctr) == core_Forwards::CORE_FORWARD_SYSID_LEN &&
            preg_match('/^[a-z]+$/', $ctr)) {
            
            return core_Forwards::go($ctr);
        }
        
        $vars = arr::make($vars, true);
        
        if (count($vars)) {
            $Request->push($vars, $varsName);
            $mustPop = true;
        }
        
        //
        if (defined('EF_CTR_NAME')) {
            $ctr = EF_CTR_NAME;
        } else {
            $ctr = $Request->get('Ctr');
            
            if (empty($ctr)) {
                $ctr = 'Index';
            }
        }
        
        if (defined('EF_ACT_NAME')) {
            $act = EF_ACT_NAME;
        } else {
            $act = $Request->get('Act');
            
            if (empty($act)) {
                $act = 'default';
            }
        }
        
        $method = $prefix . $act;
        
        $ctr = cls::getClassName($ctr);
        
        if (cls::load($ctr, true) && ($mvc = & cls::get($ctr)) && (is_subclass_of($mvc, 'core_BaseClass'))) {
            $content = $mvc->action(strtolower($act));
        } else {
            error('404 @Липсваща страница', $ctr, $_GET, $_POST);
        }
        
        if ($mustPop) {
            $Request->pop($varsName);
        }
        
        Debug::log('Forward <= ' . $point);
        
        return $content;
    }
    
    
    /**
     * Защитава служебно някои параметри на URL-то
     */
    public static function addUrlHash(&$params, $mustValidUrlHash = false)
    {
        if (!is_array($params)) {
            
            return;
        }
        
        if (isset($params['ret_url'])) {
            if (is_array($params['ret_url'])) {
                $params['ret_url'] = toUrl($params['ret_url'], 'local');
            }
            if (!str::checkHash($params['ret_url'], 8, 'ret_url')) {
                $params['ret_url'] = str::addHash($params['ret_url'], 8, 'ret_url');
            }
        }
    }
    
    
    /**
     * Проверява дали в посочените параметри, служебно защитените променливи са с коректен хеш
     *
     * Ако $mustValidUrlHash == TRUE, тогава в случай на несъответсвие, изтрива параметрите с лош хеш
     */
    public static function checkUrlHash(&$params, $mustValidUrlHash = false)
    {
        if (!is_array($params)) {
            
            return;
        }
        
        if (isset($params['ret_url'])) {
            $retUrl = str::checkHash($params['ret_url'], 8, 'ret_url');
            
            if ($retUrl) {
                $params['ret_url'] = $retUrl;
            } elseif ($mustValidUrlHash) {
                $params['ret_url'] = $retUrl;
            }
        }
    }
}
