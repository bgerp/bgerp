<?php



/**
 * Клас 'core_Session' - Клас-манипулатор на потребителска сесия
 *
 *
 * @category  all
 * @package   core
 * @author    Stefan Stefanov <stefan.bg@gmail.com>, Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Session {
    
    
    /**
     * @var array
     * @access private
     */
    var $_headers;
    
    
    /**
     * @var bool
     * @access private
     */
    var $_started;
    
    
    /**
     * @var bool
     * @access private
     */
    var $_resumed;
    
    
    /**
     * Функция - флаг, че обектите от този клас са Singleton
     */
    function _Singleton() {}
    
    
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
     * @param    string    $name    име на идентификатора на сесията (PHPSESSID)
     */
    function core_Session($name = "SID")
    {
        // HTTP header-и непозволяващи кеширането на документ-а
        $this->_headers["Expires"] = "Mon, 26 Jul 1997 05:00:00 GMT";    // Date in the past
        $this->_headers["Last-Modified"] = gmdate("D, d M Y H:i:s") . " GMT";    // always modified
        $this->_headers["Cache-Control"] = "no-cache, must-revalidate";    // HTTP/1.1
        $this->_headers["Pragma"] = "no-cache";    // HTTP/1.0
        ini_set('session.gc_maxlifetime', 7200);
        session_name($name);
        $this->_started = FALSE;
        
        // Проверка за съществуваща сесия
        $sid = $this->getSid();
        
        $resumeSession = isset($sid) && preg_match("/^[0-9a-z]{5,}$/i", $sid);
        
        $this->_resumed = FALSE;
        
        if($resumeSession) {
            $this->_start();
            $this->_resumed = isset($_SESSION['session_is_valid']);
            
            if(!$this->_resumed) {
                $this->destroy();
            }
        }
        
        if(!$this->_resumed) {
            unset($_REQUEST[session_name()]);
            unset($_GET[session_name()]);
            unset($_POST[session_name()]);
            unset($_COOKIE[session_name()]);
            unset($GLOBALS[session_name()]);
        }
    }
    
    
    /**
     * Връща идентификатора на сесията, към която е прикачен обекта
     *
     * @return string
     */
    function getSid()
    {
        if (isset($_COOKIE[session_name()])) {
            $sid = $_COOKIE[session_name()];
        } elseif(isset($_REQUEST[session_name()])) {
            $sid = $_REQUEST[session_name()];
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
    function getName()
    {
        return session_name();
    }
    
    
    /**
     * Стартирана ли е сесия в момента?
     *
     * @return bool
     */
    function isStarted()
    {
        if(is_a($this, 'core_Session')) {
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
     * @return mixed
     */
    function get($varName, $part = NULL)
    {
        if(is_a($this, 'core_Session')) {
            $Session = $this;
        } else {
            $Session = cls::get('core_Session');
        }
        
        if($Session->_started) {
            $dv = $Session->_decorate($varName);
            
            if(isset($_SESSION[$dv])) {
                $var = $_SESSION[$dv];
                
                if($part) {
                    if(is_array($var)) {
                        
                        return $var[$part];
                    } elseif(is_object($var)) {
                        
                        return $var->{$part};
                    } else {
                        error("Опит за прочитане на част от скаларна сесийна променлива", array('varName' => $varName, 'part' => $part));
                    }
                } else {
                    
                    return $var;
                }
            }
        }
    }
    
    
    /**
     * Задава стойност на променлива в сесията. Създава нова сесия ако няма вече стартирана.
     *
     * @param string $varName
     * @param mixed $value
     */
    function set($varName, $value)
    {
        if(is_a($this, 'core_Session')) {
            $Session = $this; bp();
        } else {
            $Session = cls::get('core_Session');
        }
        
        $Session->_start();    // Стартираме сесия, ако не е вече стартирана.
        $_SESSION[$Session->_decorate($varName)] = $value;
    }
    
    
    /**
     * Премахва променлива от сесията
     *
     * @param string $varName
     */
    function unsetVar($varName)
    {
        $_SESSION[$this->_decorate($varName)] = NULL;
    }
    
    
    /**
     * Добавя идентификатора на сесията в query частта на $url, ако това е необходимо.
     *
     * @param string $url
     */
    static function addSidToUrl($url)
    {
        if(is_a($this, 'core_Session')) {
            $Session = $this;
        } else {
            $Session = cls::get('core_Session');
        }
        
        if ($sid = $Session->getSid()) {
            
            $name = $Session->getName();
            
            if(!isset($_COOKIE[$name])) {
                // SID-а е не е дошъл от cookie, значи клиента не поддържа cookies,
                // затова трябва да добавим сесията в URL-то
                $url = Url::addParams($url, array($name => $sid));
            }
        }
        
        return $url;
    }
    
    
    /**
     * Унищожава сесията (не обекта от клас Session, а файла, съдържащ данните
     */
    function destroy()
    {
        if(is_a($this, 'core_Session')) {
            $Session = $this;
        } else {
            $Session = cls::get('core_Session');
        }
        
        if($Session->_started) {
            session_regenerate_id();
            @session_unset();
            @session_destroy();
            unset($_SESSION);
            unset($_COOKIES);
        }
    }
    
    /*
    * P R I V A T E   M E M B E R S
    */
    
    
    /**
     * @access private
     */
    function _start()
    {
        if(!$this->_started) {
            @session_cache_limiter('nocache');
            @session_set_cookie_params(0);
            @session_start();
            
            if(!$this->_resumed) {
                $_SESSION['session_is_valid'] = time();
            }
            
            foreach($this->_headers as $hdrName=>$hdrValue) {
                header("$hdrName: $hdrValue");
            }
            
            $this->_started = TRUE;
        }
    }
    
    
    /**
     * @access private
     * @param string $varName
     */
    function _decorate($varName)
    {
        return 'sess_' . EF_APP_NAME . '_' . $varName;
    }
}