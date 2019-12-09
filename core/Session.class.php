<?php


/**
 * Клас 'core_Session' - Клас-манипулатор на потребителска сесия
 *
 *
 * @category  ef
 * @package   core
 *
 * @author    Stefan Stefanov <stefan.bg@gmail.com>, Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class core_Session
{

    /**
     * Флаг, дали глобално да се заглуши сесията
     */
    static $mute;


    /**
     * @var array
     * @access private
     */
    public $_headers;
    
    
    /**
     * @var bool
     * @access private
     */
    public $_started;
    
    
    /**
     * Префикс за декорация
     */
    protected static $decoratePrefix = 'sess_';
    
    
    /**
     * ID на сесията
     *
     * @var string
     */
    protected $sid;
    
    
    /**
     * Флаг - дали е спряна сесията
     *
     * @var bool
     */
    public $pause;
    
    
    /**
     * Функция - флаг, че обектите от този клас са Singleton
     */
    public function _Singleton()
    {
    }
    
    
    /**
     * Конструктор - създава обект за манипулация на сесията и нейните променливи.
     *
     * ВНИМАНИЕ: Този обект не е едно и също като обекта сесия! Обекта от клас
     * Session има живот само през време на изпълнение на съответните
     * PHP скриптове, докато обекта сесия "живее" и през останалото време.
     *
     * Гледаме на обект от клас Session като PHP интерфейс за връзка с
     * реалния обект сесия за времето на изпълнение PHP скрипта.
     *
     * Ако текущия PHP скрипт се изпълнява в контекста на сесия, т.е. чрез HTTP
     * заявката е зададена валидна стойност на идентификатора на сесията (през GET,
     * POST или COOKIE), то обекта се "прикача", автоматично зарежда съдържанието на
     * сесията със зададения идентификатор.
     *
     * @param string $name име на идентификатора на сесията (PHPSESSID)
     */
    public function __construct($name = 'SID')
    {
        if(self::$mute) return;

        ini_set('session.gc_maxlifetime', 7200);
        
        $this->sid = $_COOKIE[session_name()];
        
        session_name($name);
        
        $this->_start();
        
        unset($_REQUEST[session_name()]);
        unset($_GET[session_name()]);
        unset($_POST[session_name()]);
        unset($_COOKIE[session_name()]);
        unset($GLOBALS[session_name()]);
    }
    
    
    /**
     * Връща идентификатора на сесията, към която е прикачен обекта
     *
     * @return string
     */
    public function getSid()
    {
        if (isset($_COOKIE[session_name()])) {
            $sid = $_COOKIE[session_name()];
        }
        
        if (isset($sid)) {
            
            return $sid;
        }
        
        return session_id();
    }
    
    
    /**
     * Връща името на сесията (напр PHPSESSID, или SID), към която е прикачен обекта.
     *
     * @return string
     */
    public function getName()
    {
        return session_name();
    }
    
    
    /**
     * Стартирана ли е сесия в момента?
     *
     * @return bool
     */
    public function isStarted()
    {
        if (is_a($this, 'core_Session')) {
            $Session = $this;
        } else {
            $Session = cls::get('core_Session');
        }
        
        return $Session->_started;
    }
    
    
    /**
     * Връща стойността на променлива от сесията
     *
     * @param string $varName
     *
     * @return mixed
     */
    public static function get($varName, $part = null)
    {   
        if(self::$mute) return;

        $Session = cls::get('core_Session');
        
        if ($Session->_started) {
            $dv = $Session->_decorate($varName);
            
            if (isset($_SESSION[$dv])) {
                $var = $_SESSION[$dv];
                
                if ($part) {
                    if (is_array($var)) {
                        
                        return $var[$part];
                    } elseif (is_object($var)) {
                        
                        return $var->{$part};
                    }
                    error('@Опит за прочитане на част от скаларна сесийна променлива', $varName, $part);
                } else {
                    
                    return $var;
                }
            }
        }
    }
    
    
    public static function forcedStart()
    {
        $Session = cls::get('core_Session');
        
        $Session->_start(true);
    }
    
    
    /**
     * Задава стойност на променлива в сесията. Създава нова сесия ако няма вече стартирана.
     *
     * @param string $varName
     * @param mixed  $value
     */
    public static function set($varName, $value)
    {
        $Session = cls::get('core_Session');
        
        $Session->_start();     // Стартираме сесия, ако не е вече стартирана.
        $_SESSION[$Session->_decorate($varName)] = $value;
    }
    
    
    /**
     * Премахва променлива от сесията
     *
     * @param string $varName
     */
    public static function unsetVar($varName)
    {
        $Session = cls::get('core_Session');
        
        $Session->_start();     // Стартираме сесия, ако не е вече стартирана.
        $_SESSION[$Session->_decorate($varName)] = null;
    }
    
    
    /**
     * Унищожава сесията (не обекта от клас Session, а файла, съдържащ данните
     */
    public function destroy()
    {
    }
    
    
    public static function pause()
    {
        if (session_id()) {
            session_write_close();
            $Session = cls::get('core_Session');
            $Session->pause = true;
        }
    }
    
    /*
     * P R I V A T E   M E M B E R S
     */
    
    /**
     * @access private
     */
    public function _start($forced = false)
    {
        if (!$this->_started || $this->pause) {
            if (!headers_sent()) {
                @session_cache_limiter('nocache');
                @session_set_cookie_params(0);
                ini_set('session.cookie_httponly', 1);
                ini_set('session.use_only_cookies', 1);
                if (EF_HTTPS == 'MANDATORY') {
                    ini_set('session.cookie_secure', 1);
                }
            }
            if ($this->sid) {
                session_id($this->sid);
            }
        }
        
        if (!$this->_started || $forced) {
            @session_start();
            $this->_started = true;
            $this->pause = false;
        }
        
        if ($this->pause) {
            @session_start();
            $this->pause = false;
        }
    }
    
    
    /**
     * @access private
     *
     * @param string $varName
     */
    public function _decorate($varName)
    {
        static $prefix;
        
        if (!$prefix) {
            $prefix = strtolower(str_replace('www.', '', $_SERVER['HTTP_HOST']));
            
            $prefix = md5($prefix . EF_APP_NAME . EF_DB_NAME . EF_SALT);
            $prefix = substr($prefix, 0, 10);
        }
        
        $decoratedVar = core_Session::getDecoratePrefix() . $prefix . '_' . $varName;
        
        return $decoratedVar;
    }
    
    
    /**
     * Сеттър за decoratePrefix
     *
     * @param string $prefix
     */
    public static function setDecoratePrefix($prefix)
    {
        self::$decoratePrefix = $prefix;
    }
    
    
    /**
     * Геттър за decoratePrefix
     *
     * @return string
     */
    public static function getDecoratePrefix()
    {
        return self::$decoratePrefix;
    }
}
