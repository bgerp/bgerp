<?php


/**
 * Дали знака '@' преди функция да предизвиква подтискане на грешките в нея?
 */
defIfNot('CORE_ENABLE_SUPRESS_ERRORS', TRUE);

// Кои грешки да се показват?
if(defined('BGERP_GIT_BRANCH') && (BGERP_GIT_BRANCH == 'dev' || BGERP_GIT_BRANCH == 'test')) { 
    defIfNot('CORE_ERROR_REPORTING_LEVEL', E_ERROR | E_PARSE | E_CORE_ERROR | E_STRICT | E_COMPILE_ERROR | E_WARNING | E_NOTICE);
} else {
    defIfNot('CORE_ERROR_REPORTING_LEVEL', E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR);
    defIfNot('CORE_ERROR_LOGGING_LEVEL', E_ERROR | E_PARSE | E_CORE_ERROR | E_STRICT | E_COMPILE_ERROR | E_WARNING);
}


/**
 * Кои грешки да се логват
 */
defIfNot('CORE_ERROR_LOGGING_LEVEL', CORE_ERROR_REPORTING_LEVEL);


/**
 * Колко секунди да е валидно cookie за дебъг режим?
 */
defIfNot('DEBUG_COOKIE_LIFETIME', 3600 * 24 * 7); // Седмица


/**
 * Клас 'core_Debug' ['Debug'] - Функции за дебъг и настройка на приложения
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
class core_Debug
{
    
    
    /**
     * 
     */
	public static $startMicroTime;
    
	
	/**
	 * 
	 */
	public static $lastMicroTime;
    
	
	/**
	 * 
	 */
    public static $debugTime = array();
    
    
    /**
     * 
     */
    public static $timers = array();
    
    
    /**
     * Дали дебъгера да записва
     * Това е един начин, да се изключат логовете на дебъгера
     */
    public static $isLogging = TRUE;
    
    
    /**
     * Дали се рапортуват грешки на отдалечен компютър
     */
    public static $isErrorReporting = TRUE;
    
    
    /**
     * Кеш - дали се намираме в DEBUG режим
     */
    public static $isDebug;


    /**
     * При дъмп - колко нива преглеждаме
     */
    public static $dumpOpenLevels = 3;
    
    
    /**
     * При дъмп - колко нива са отворени
     */
    public static $dumpViewLevels = 5;


    /**
     * Функция - флаг, че обектите от този клас са Singleton
     */
    function _Singleton() {}
    
 
    /**
     * Инициализираме таймерите
     */
    static function init()
    {
        if (!self::$startMicroTime) {
            list($usec, $sec) = explode(" ", microtime());
            self::$startMicroTime = (float) $usec + (float) $sec;
            self::$lastMicroTime = 0;
        	self::$debugTime[] = (object) array('start' => 0, 'name' => 'Начало ' . date("Y-m-d H:i:s", time()));
        }
    }


    /**
     * Пускаме хронометъра за посоченото име
     */
    static function startTimer($name)
    {
        // Функцията работи само в режим DEBUG
        if(!isDebug()) return;
        
        self::init();
        
        if(!isset(self::$timers[$name])){
        	self::$timers[$name] = new stdClass();
        }
        
        self::$timers[$name]->start = core_DateTime::getMicrotime();
    }
    
    
    /**
     * Спираме хронометъра за посоченото име
     */
    static function stopTimer($name)
    {
        // Функцията работи само в режим DEBUG
        if(!isDebug()) return;
        
        self::init();
  
        if (self::$timers[$name]->start) {
            $workingTime = core_DateTime::getMicrotime() - self::$timers[$name]->start;
            self::$timers[$name]->workingTime += $workingTime;
            self::$timers[$name]->start = NULL;
        }
    }
    
    
    /**
     * Лог записи за текущия хит
     */
    static function log($name)
    {
        // Функцията работи само в режим DEBUG
        if(!isDebug() || !core_Debug::$isLogging) return;

        self::init();
        
        $rec = new stdClass();
        $rec->start = core_DateTime::getMicrotime() - self::$startMicroTime;
        $rec->name  = $name;

        self::$debugTime[] = $rec;
    }
    
    
    /**
     * Колко време е записано на това име?
     */
    static function getExecutionTime()
    {
        self::init();
        return number_format((core_DateTime::getMicrotime() - self::$startMicroTime), 5);
    }


    /**
     * Връща watch point лога
     */
    private static function getWpLog()
    {
        self::init();
        
        $html = '';

        if (count(self::$debugTime)) {
            self::log('Край ' . core_DateTime::now());

            $html .= "\n<div class='debug_block' style=''>" .
            "\n<div style='background-color:#FFFF33; padding:5px; color:black;'>Debug log</div><ul><li style='padding:15px 0px 15px 0px;'>";
            
            $html .= core_Html::mixedToHtml($_COOKIE) . "</li>";
                        
            foreach (self::$debugTime as $rec) {
                $rec->name = core_ET::escape($rec->name);
                $html .= "\n<li style='padding:15px 0px 15px 0px;border-top:solid 1px #cc3;'>" .  number_format(($rec->start ), 5) . ": " . @htmlentities($rec->name, ENT_QUOTES, 'UTF-8');
            }
            
            $html .= "\n</ul></div>";
        }

        return $html;
    }


    /**
     * Връща измерванията на таймерите
     */
    private static function getTimers()
    {
        $html = '';

        if (count(self::$timers)) {
            $html .= "\n<div style='padding:5px; margin:10px; border:solid 1px #777; background-color:#FFFF99; display:table;color:black;'>" .
            "\n<div style='background-color:#FFFF33; padding:5px;color:black;'>Timers info</div><ol>";
            
            arsort(self::$timers);

            foreach (self::$timers as $name => $t) {
                $html .= "\n<li> '{$name}' => " . number_format($t->workingTime, 5) . ' sec.';
            }
            
            $html .= "\n</ol></div>";
        }
        
        return $html;
    }
    
    
    /**
     * Връща лога за текущия хит
     */
    static function getLog()
    {
        $html = self::getWpLog() . self::getTimers();

        return $html;
    }



    /**
     * Показва страница с дебъг информация
     */
    public static function getInfoPage($html, $stack, $type = 'Прекъсване')
    {
        // Ако сме в работен, а не тестов режим, не показваме прекъсването
        if (!isDebug()) {
            error_log("Breakpoint on line $breakLine in $breakFile");
            return;
        }
 
        $errHtml = self::getErrorHtml($html, $stack, $type);
        
        $errHtml .= core_Debug::getLog();
        
        if (!file_exists(EF_TEMP_PATH) && !is_dir(EF_TEMP_PATH)) {
    		mkdir(EF_TEMP_PATH, 0777, TRUE);    
		}
        
        // Поставяме обвивка - html документ
        $page = core_Html::wrapMixedToHtml($errHtml, TRUE);
        
        // Записваме за всеки случай и като файл
        file_put_contents(EF_TEMP_PATH . '/err.log.html', $page . "\n\n");

        return  $page;
    }



    public static function getTraceAsHtml($trace)
    {
        $trace = self::prepareTrace($trace);

        $result = '';

        foreach ($trace as $row) {
            if($i++ % 2) {
                $bgk = '#ffd';
            } else {
                $bgk = '#e8e8ff';
            }
            $result .= "\n<tr style='background-color:{$bgk}'>";
            foreach ($row as $cell) {
                $result .= '<td>' . $cell . '</td>';
            }
            $result .= '</tr>';
        }

        $result = '<div><table border="0" style="border-collapse: collapse;" cellpadding="5">'. $result . '</table></div>';

        return $result;
    }


    /**
     * Подготвя за показване данни от подаден масив от данни отговарящи на работен стек
     */
    private static function prepareTrace($trace)
    {
        $rtn = array();

        foreach ($trace as $count => $frame) {
            $file = 'unknown';
            if (!empty($frame['file'])) { 
                $line = self::getEditLink($frame['file'], $frame['line']);
                $file = self::getEditLink($frame['file']);
                $file =  $file . ' : ' . $line;
                if($rUrl = self::getGithubSourceUrl($frame['file'], $frame['line'])) {
                    $githubLink = sprintf('<a target="_blank" class="octocat" href="%s" title="Отвори в GitHub"><img valign="middle" src=%s /></a>&nbsp;', $rUrl, '//bgerp.com/sbf/bgerp/img/16/github.png');
                } 
            } else {
                $githubLink = '';
            }
            $args = "";
            if (isset($frame['args'])) {
                $args = array();
                foreach ($frame['args'] as $arg) {
                    $args[] = self::formatValue($arg);
                }
                $args = join(", ", $args);
            }

            $rtn[] = array(
                $file,
                $githubLink,
                sprintf("%s(%s)",
                    isset($frame['class']) ?
                        $frame['class'].$frame['type'].$frame['function'] :
                        $frame['function'],
                     $args
                ),
            );
        }

        return $rtn;
    }


    /**
     * URL на сорс-код файл в централизирано репозитори
     *
     * @param string $file
     * @param int $line
     * @return string|boolean FALSE при проблем, иначе пълно URL
     */
    private static function getGithubSourceUrl($file, $line)
    { 
        $selfPath = str_replace("\\", '/', dirname(dirname(__FILE__)));

        $file = str_replace(array("\\", $selfPath), array('/', ''), $file);

        if(defined('BGERP_GIT_BRANCH')) {
            $branch = BGERP_GIT_BRANCH;
        } else {
            $branch = 'dev';
        }

        $url = "https://github.com/bgerp/bgerp/blob/{$branch}{$file}#L{$line}";

        return $url;
    }


    private static function formatValue($v)
    {
        $result = '';

        if (is_string($v)) {
            $result = "'" . htmlentities($v, ENT_COMPAT | ENT_IGNORE, 'UTF-8') . "'";
        } elseif (is_array($v)) {
            $result = self::arrayToString($v);
        } elseif (is_null($v)) {
            $result = 'NULL';
        } elseif (is_bool($v)) {
            $result = ($v) ? "TRUE" : "FALSE";
        } elseif (is_object($v)) {
            if(get_class($v) == 'stdClass') {
                $result = ht::createElement('span', array('title' => self::arrayToString($v)), get_class($v));
            } else {
                $result =  get_class($v);
            }
        } elseif (is_resource($v)) {
            $result = get_resource_type($v);
        } else {
            $result = $v;
        }

        return $result;
    }

    private static function arrayToString($arr)
    {   
        $nArr = array();

        if(is_object($arr)) {
            $arrNew = (array) $arr;
            foreach($arrNew as $key => $part) {
                if(isset($part)) {
                    if(is_scalar($part)) {
                        if($part === FALSE) {
                            $part = 'FALSE';
                        } elseif($part === TRUE) {
                            $part = 'TRUE';
                        } elseif(is_string($part) && empty($part)) {
                            $part = "'" . $part . "'";
                        }
                        $nArr[] = "{$key}={$part}";
                    } else {
                        if(is_object($part)) {
                            $nArr[] = "{$key}=" . get_class($part);
                        } else {
                            $nArr[] = "{$key}=" . gettype($part);
                        }
                    }
                }
            }
        } else {
            foreach ($arr as $i=>$v) {
                $nArr[$i] = self::formatValue($v);
            }
        }

        return '[' . implode(', ', $nArr) . ']';
    }



    /**
     * Връща кода от php файла, около посочената линия
     * Прави базово форматиране
     *
     * @param string $file Името на файла, съдържащ PHP код
     * @param int    $line Линията, около която търсим 
     */
    public static function getCodeAround($file, $line, $range = 4)
    {
        if(strpos($file, "eval()'d") !== FALSE) return;
		
        $code = "";
        
        if (!$file) return $code;
        
        $source = @file_get_contents($file);
                
        if (!$source) return $code;
        
        $lines = explode("\n", $source);
        
        $from = max($line - $range-1, 0);
        $to   = min($line + $range, count($lines));
        $padding = strlen($to);
        for($i = $from; $i < $to; $i++) {
            $l = str_pad($i+1, $padding, " ", STR_PAD_LEFT);
            $style = '';
            if($i+1 == $line) {
                $style = " class='debugErrLine' style='background-color:#ff9;'";
            }
            $l = "<span{$style}><span style='border-right:solid 1px #999;padding-right:5px;'>$l</span> ". 
                str_replace(array('&', '<'), array('&amp', '&lt;'), rtrim($lines[$i])) . "</span>\n";
            $code .= $l;
        }
        
        return $code;
    }


    /**
     * Анализира стека и премахва тази, част от него, която е създадена след прекъсването
     *
     * @param array $stack
     *
     * @return array [$stack, $breakFile, $breakLine]
     */
    private static function analyzeStack($stack)
    {
        // Вътрешни функции, чрез които може да се генерира прекъсване
        $intFunc = array(
            'bp:debug',
            'errorhandler:core_debug',
            'bp:',
            'wp:',
            'trigger:core_error',
            'error:',
            'expect:'
        );

        $breakpointPos = $breakFile = $breakLine = NULL;

        foreach ($stack as $i => $f) {
            if (in_array(strtolower($f['function'] . ':' . (isset($f['class']) ? $f['class'] : '')), $intFunc)) {
                $breakpointPos = $i;
            }
        }

        if (isset($breakpointPos)) {
            $breakLine = $stack[$breakpointPos]['line'];
            $breakFile = $stack[$breakpointPos]['file'];
            $stack = array_slice($stack, $breakpointPos+1);
        }

        return array($stack, $breakFile, $breakLine);
    }

    
    /**
     * Рендира стека
     */
    private static function renderStack($stack)
    {
        $result = '';

        foreach ($stack as $f) {
            $hash = md5($f['file']. ':' . $f['line']);
            $result .= "<hr><br><div id=\"{$hash}\">";
            $result .= core_Html::mixedToHtml($f);
            $result .= "</div>";
        }

        return $result;
    }


    /**
     * Подготвя HTML страница с дебъг информация за съответното състояние
     */
    public static function getDebugPage($state)
    {
        require_once(EF_APP_PATH . "/core/NT.class.php");
        require_once(EF_APP_PATH . "/core/ET.class.php");
        require_once(EF_APP_PATH . "/core/Sbf.class.php");
        require_once(EF_APP_PATH . "/core/Html.class.php");
        
        $data['tabContent'] = $data['tabNav'] = '';
        
        // Дъмп
        if(!empty($state['dump'])) {
            $data['tabNav'] .= ' <li><a href="#">Дъмп</a></li>';
            $data['tabContent'] .= '<div class="simpleTabsContent">' . core_Html::arrayToHtml($state['dump'], self::$dumpOpenLevels, self::$dumpViewLevels) . '</div>';
        }

        // Подготовка на стека
        if(isset($state['stack'])) {
            list($stack, $breakFile, $breakLine) = self::analyzeStack($state['stack']);
            $data['tabNav'] .= ' <li><a href="#">Стек</a></li>';
            $data['tabContent'] .= '<div class="simpleTabsContent">' . self::getTraceAsHtml($stack) . '</div>';
        }

        // Подготовка на кода
        if(!isset($breakFile) && isset($state['breakFile'])) {
            $breakFile = $state['breakFile'];
        }
        if(!isset($breakLine) && isset($state['breakLine'])) {
            $breakLine = $state['breakLine'];
        }

        if(isset($breakFile) && isset($breakLine)) { 
            $data['code'] = self::getCodeAround($breakFile, $breakLine);
        }
        
        // Контекст
        if(isset($state['contex'])) {
            $data['tabNav'] .= ' <li><a href="#">Контекст</a></li>';
            $data['tabContent'] .= '<div class="simpleTabsContent">' . core_Html::mixedToHtml($state['contex']) . '</div>';
        }
        
        // Лог
        if($wpLog = self::getwpLog()) {
            $data['tabNav'] .= ' <li><a href="#">Лог</a></li>';
            $data['tabContent'] .= '<div class="simpleTabsContent">' . $wpLog . '</div>';
        }
        
        // Времена
        if($timers = self::getTimers()) {
            $data['tabNav'] .= ' <li><a href="#">Времена</a></li>';
            $data['tabContent'] .= '<div class="simpleTabsContent">' . $timers . '</div>';
        }
        
        $data['httpStatusCode'] = $state['httpStatusCode'];
        $data['httpStatusMsg'] = $state['httpStatusMsg'];
        $data['background'] = $state['background'];

        if(isset($state['errTitle']) && $state['errTitle'][0] == '@') {
            $state['errTitle'] = substr($state['errTitle'], 1);
        }

        if(isset($state['errTitle'])) {
            $data['errTitle'] = $state['errTitle'];
        }

        $lineHtml = self::getEditLink($breakFile, $breakLine);
        $fileHtml = self::getEditLink($breakFile);
        
        if(isset($state['header'])) {
            $data['header'] = $state['header'];
        } else {
            $data['header'] = $state['errType'];
            if($breakLine && !strpos($fileHtml, "eval()'d code")) {
                $data['header'] .= " на линия <i>{$lineHtml}</i>";
            }
            if($breakFile) {
                $data['header'] .= " в <i>{$fileHtml}</i>";
            }
        }

        if(!empty($state['update'])) {
            $data['update'] = ht::createLink('Обновяване на системата', $state['update']);
        }


        $tpl = new core_NT(getFileContent('core/tpl/Debug.shtml'));

        $res = $tpl->render($data);

        return $res;        
    }
    

    /**
     * Рендира страница за грешка
     */
    private  static function getErrorPage(&$state)
    { 
        $tpl = new core_NT(getFileContent('core/tpl/Error.shtml'));
        if(isset($state['errTitle']) && $state['errTitle'][0] == '@') {
            $state['errTitle'] = $state['httpStatusMsgBg'];
        }

        if(!empty($state['update'])) {
            $state['update'] = ht::createLink('Инициализиране', $state['update'], NULL, 'ef_icon=img/16/refresh-img.png');
       	}
       	
        $state['back'] =  ht::createLink('Назад', "javascript:onclick=history.back(-1)", NULL, 'ef_icon=img/16/back-img.png');
        
        $state['forward'] = ht::createLink('Към сайта', array('Index'), NULL, 'ef_icon=img/16/next-img.png');

        $page = $tpl->render($state); 
 
        return $page;        
    }



    /**
     * Показва съобщението за грешка и евентуално дебъг информация
     *
     * @param $errType   string Тип на грешката ('E_STRICT', 'E_WARNING', 'Несъответствие', 'Изключение', 'Грешка', ...)
     * @param $errMsg    string Съобщение за грешка. Ако започва с число - то се приема за httpStatusCode
     * @param $errDetail string Детайла информация за грешката. Показва се само в дебъг режим
     * @param $dump      array  Масив с данни, които да се покажат в дебъг режим
     * @param $stack     array  Стека на изпълнение на програмата
     * @param $contex    array  Данни, които показват текущото състояние на машината
     * @param $breakFile string Файл, където е възникнало прекъсването
     * @param $breakLine int    Линия на която е възникнало прекъсването
     */
    public static function prepareErrorState($errType, $errTitle, $errDetail, $dump, $stack, $contex, $breakFile, $breakLine, $update = NULL)
    {
        // Добавяме времето и паметта от настройките и от хита към контекста
        if (is_array($contex)) {
            $contex['MEMORY_LIMIT_VERBAL'] = ini_get('memory_limit');
            $contex['MEMORY_LIMIT'] = core_Os::getBytesFromMemoryLimit($contex['MEMORY_LIMIT_VERBAL']);
            
            $realUsage = TRUE;
            
            $contex['PEAK_MEMORY_USAGE'] = memory_get_peak_usage($realUsage);
            
            if (is_numeric($contex['MEMORY_LIMIT'])) {
                $contex['PEAK_MEMORY_USAGE_PERCENT'] = number_format(($contex['PEAK_MEMORY_USAGE'] / $contex['MEMORY_LIMIT']) * 100, 2) . '%';
            }
            
            $contex['MEMORY_USAGE'] = memory_get_usage($realUsage);
            if (is_numeric($contex['MEMORY_LIMIT'])) {
                $contex['MEMORY_USAGE_PERCENT'] = number_format(($contex['MEMORY_USAGE'] / $contex['MEMORY_LIMIT']) * 100, 2) . '%';
            }
            
            $contex['MAX_EXECUTION_TIME'] = ini_get('max_execution_time');
            
            if (self::$startMicroTime) {
                $contex['DEBUG_LAST_TIMER'] = core_DateTime::getMicrotime() - self::$startMicroTime;
                if ($contex['MAX_EXECUTION_TIME']) {
                    $contex['EXECUTION_TIME_PERCENT'] = number_format(($contex['DEBUG_LAST_TIMER'] / $contex['MAX_EXECUTION_TIME']) * 100, 2) . '%';
                }
            }
            
            $contex['OS_INFO'] = php_uname();
            $contex['PHP_VERSION'] = phpversion();
            
            try {
                $contex['SQL_VERSION'] = cls::get('core_Db')->connect()->server_info;
            } catch(ErrorException $e) {
                // Не се прави нищо
            }
            
            $contex['EF_APP_NAME'] = EF_APP_NAME;
            
            // Пътища, които се използват
            $contex['EF_ROOT_PATH'] = EF_ROOT_PATH;
            $contex['EF_INDEX_PATH'] = EF_INDEX_PATH;
            $contex['EF_SBF_PATH'] = EF_SBF_PATH;
            $contex['EF_TEMP_PATH'] = EF_TEMP_PATH;
            $contex['EF_CONF_PATH'] = EF_CONF_PATH;
            $contex['EF_UPLOADS_BASE_PATH'] = EF_UPLOADS_BASE_PATH;
            $contex['EF_UPLOADS_PATH'] = EF_UPLOADS_PATH;
            $contex['FILEMAN_UPLOADS_PATH'] = FILEMAN_UPLOADS_PATH;
            $contex['EF_DOWNLOAD_DIR'] = EF_DOWNLOAD_DIR;
            $contex['FILEMAN_TEMP_PATH'] = FILEMAN_TEMP_PATH;
            $contex['THUMB_IMG_PATH'] = THUMB_IMG_PATH;
            
            $contex['EF_TIMEZONE'] = EF_TIMEZONE;
            
            $contex['GIT_BRANCH'] = BGERP_GIT_BRANCH;
            $contex['BGERP_LAST_STABLE_VERSION'] = '17.43-Orelyak';
        }
        
        $state = array( 'errType'   => $errType, 
                        'errTitle'  => $errTitle, 
                        'errDetail' => $errDetail, 
                        'dump'      => $dump, 
                        'stack'     => $stack, 
                        'contex'    => $contex, 
                        'breakFile' => $breakFile, 
                        'breakLine' => $breakLine,
                        'update'    => $update,
                        
            );
        
        // Изваждаме от титлата httpStatusCode, ако е наличен
        if($state['httpStatusCode'] = (int) $errTitle) {
            $pos = strpos($errTitle, $state['httpStatusCode']);
            $pos += strlen($state['httpStatusCode']);
            $state['errTitle'] = trim(substr($errTitle, $pos));
        } else {
            $state['httpStatusCode'] = 500;
        }

        list($state['httpStatusMsg'], $state['httpStatusMsgBg'], $state['background']) = self::getHttpStatusMsg($state['httpStatusCode']);

        return $state;        
    }

    
    /**
     * Показва състоянието за грешка или/и го записва в локална тем директория или на отдалечен сървър
     */
    public static function renderErrorState($state, $supressShowing = FALSE) 
    {
        if(isDebug() || defined('EF_DEBUG_LOG_PATH') || defined('EF_REMOTE_ERROR_REPORT_URL')) {
            $debugPage = core_Debug::getDebugPage($state);
        }
        
        // Ако не трябва да подтиснем показването на глешката и хедърите все още не са изпратени, показваме
        if(!$supressShowing && !headers_sent()) { 
            header($_SERVER["SERVER_PROTOCOL"]. " " . $state['httpStatusCode'] . " " . $state['httpStatusMsg']);
            header('Content-Type: text/html; charset=UTF-8');

            echo isDebug() ? $debugPage : self::getErrorPage($state); 
        }
        

        // Определяме заглавието на грешката в лога
        $ctr = $_GET['Ctr'] ? $_GET['Ctr'] : 'Index';
        $act = $_GET['Act'] ? $_GET['Act'] : 'default';
        $title = EF_DB_NAME . '_' . $ctr . '_' . $act . '_' . $state['httpStatusCode'];
        $title = preg_replace("/[^A-Za-z0-9_?!]/", '_', $title);

        // Ако е необходимо записваме дебъг информацията
        if(defined('EF_DEBUG_LOG_PATH')) {  
            if(!is_dir(EF_DEBUG_LOG_PATH)) {
                @mkdir(EF_DEBUG_LOG_PATH, 0777, TRUE);
            }
            @file_put_contents(EF_DEBUG_LOG_PATH . "/{$title}.html",  $debugPage);
        }
        
        // Логваме на отдалечен сървър
        if(defined('EF_REMOTE_ERROR_REPORT_URL') && self::$isErrorReporting) {
            $url = EF_REMOTE_ERROR_REPORT_URL;
            $data = array(  'data'   => gzcompress($debugPage), 
                            'domain' => $_SERVER['SERVER_NAME'], 
                            'errCtr' => $ctr, 
                            'errAct' => $act, 
                            'dbName' => EF_DB_NAME,
                            'title'  => ltrim($state['errTitle'], '@'),
                          );

            // use key 'http' even if you send the request to https://...
            $options = array(
                'http' => array(
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => http_build_query($data),
                ),
            );
            $context = stream_context_create($options);
            $result  = @file_get_contents($url, FALSE, $context);
        }
    }

 
    /**
     * Прихваща състоянията на грешка и завършването на програмата (в т.ч. и аварийно)
     */
    static function setErrorWaching()
    {   
        // От тук нататък спираме показването на грешки
        ini_set('display_errors', '0');

        // рапортуваме само тези, които са зададени в конфиг. константа
        set_error_handler(array('core_Debug', 'errorHandler'));

        register_shutdown_function(array('core_Debug', 'shutdownHandler'));
    }


    /**
     * Функция - обработвач на състоянията на грешки
     */
    static function errorHandler($errno, $errstr, $breakFile, $breakLine, $errcontext)
    {   
        // Ако грешката нито ще я показваме нито ще я логваме - връщаме управлението
        if(!($errno & CORE_ERROR_REPORTING_LEVEL) && !($errno & CORE_ERROR_LOGGING_LEVEL)) {
            return;
        }
        // Когато сме в режим на маскиране на грешките (@) да не показваме съобщение
        if(CORE_ENABLE_SUPRESS_ERRORS && error_reporting() == 0)  return;
        
        // Подготвяме състоянието, отговарящо на грешката
        $errType = self::getErrorLevel($errno);
        $state = self::prepareErrorState($errType, '500 @' . $errstr, $errstr, NULL, debug_backtrace(), $errcontext, $breakFile, $breakLine);

        // Ако грешката само ще я логваме, но няма да я показваме - поддтискаме показването
        if(!($errno & CORE_ERROR_REPORTING_LEVEL) && ($errno & CORE_ERROR_LOGGING_LEVEL)) {
            // Само логваме показването на грешката
            self::renderErrorState($state, TRUE);
        } else {
            self::renderErrorState($state);
            die;
        }
    }


    /**
     * Извиква се преди спиране на програмата. Ако има грешка - показва я.
     */
    static function shutdownHandler()
    {

        if ($error = error_get_last()) {
            
            if(!($error['type'] & CORE_ERROR_REPORTING_LEVEL)) return;
            

            $errType = self::getErrorLevel($error['type']);
            
            $state = self::prepareErrorState($errType, '500 @' . $error['message'], $error['message'], NULL, NULL, $_SERVER, $error['file'], $error['line']);
            self::renderErrorState($state);
            die;
        }
    }


    /**
     * Връща новото на грешката
     */
    private static function getErrorLevel($errorCode)
    {
        switch($errorCode){
                case E_ERROR:
                    $name = 'E_ERROR';
                    break;
                case E_WARNING:
                    $name = 'E_WARNING';
                    break;
                case E_PARSE:
                    $name = 'E_PARSE ERROR';
                    break;
                case E_NOTICE:
                    $name = 'E_NOTICE';
                    break;
                case E_CORE_ERROR:
                    $name = 'E_CORE_WARNING';
                    break;
                case E_CORE_WARNING:
                    $name = 'E_CORE_WARNING';
                    break;
                case E_COMPILE_ERROR:
                    $name = 'E_COMPILE_ERROR';
                    break;
                case E_USER_ERROR:
                    $name = 'E_USER_ERROR';
                    break;
                case E_USER_WARNING:
                    $name = 'E_USER_WARNING';
                    break;
                case E_STRICT:
                    $name = 'E_STRICT';
                    break;
                case E_USER_NOTICE:
                    $name = 'E_USER_NOTICE';
                    break;
                case E_RECOVERABLE_ERROR:
                    $name = 'E_RECOVERABLE_ERROR';
                    break;
                case E_DEPRECATED:
                    $name = 'E_DEPRECATED';
                    break;
                case E_USER_DEPRECATED:
                    $name = 'E_USER_DEPRECATED';
                    break;
                default:
                    $name = "ERROR №{$errorCode}";
        }
 
        return $name;
    }
  
    /**
     * Връща вербалния http статус на ексепшъна
     */
    private static function getHttpStatusMsg($httpStatusCode)
    {
        switch($httpStatusCode) {
            case 400: 
                $httpStatusMsg = 'Bad Request';
                $httpStatusMsgBg = 'Грешна заявка';
                $background      = '#c00';
                break;
            case 401: 
                $httpStatusMsg   = 'Unauthorized';
                $httpStatusMsgBg = 'Недостатъчни права';
                $background      = '#c60';
                break;
            case 403: 
                $httpStatusMsg = 'Forbidden';
                $httpStatusMsgBg = 'Забранен достъп';
                $background      = '#c06';
                break;
            case 404: 
                $httpStatusMsg = 'Not Found';
                $httpStatusMsgBg = 'Липсваща страница';
                $background      = '#c33';
                break;
            default:
                $httpStatusMsg = 'Internal Server Error';
                $httpStatusMsgBg = 'Грешка в сървъра';
                $background      = '#d22';
                break;
        }
        
        return array($httpStatusMsg, $httpStatusMsgBg, $background);
    }


    /**
     * Връща, ако може линк за редактиране на файла
     */
    private static function getEditLink($file, $line = NULL, $title = NULL)
    {  
        if(strpos($file, "eval()'d code")) {
            if(!$title) {
                $title = $file;
            }
            list($file, $line) = explode('(', $file);
            $line = (int) $line;
        }

        if(!$title) {
            if(!$line) {
                //$line = 1;
                $title = $file;
            } else {
                $title = $line;
            }
        }

        if(defined('EF_DEBUG_EDIT_URL')) {
            $fromTo = array('FILE' => urlencode($file));
            if($line) {
                $fromTo['LINE'] = urlencode($line);
            }
            $tpl = new core_NT(EF_DEBUG_EDIT_URL);
            $editUrl =  $tpl->render($fromTo);
        }
        
        if($editUrl) {
            $title = "<a href='{$editUrl}'>{$title}</a>";
        }

        return $title;
    }


    /**
     * Дали се намираме в DEBUG режим
     * Намираме се в DEBUG режим, ако е изпълнено едно от следните неща:
     * - Константата EF_DEBUG === TRUE
     * - Текущото ни IP се съдържа в списъка от IP-та, който се намира в константата EF_DEBUG
     * - Има куки със стойност = 1, чието име е хеш на конкатинираното ни IP, EF_SALT и 'DEBUG'
     */
    public static function isDebug()
    {
 
        // Връщаме кеширания резултат ако има такъв
        if(is_bool(self::$isDebug)) return self::$isDebug;
        
        // IP на потребителя
        $realIpAdd = $_SERVER['REMOTE_ADDR'];

        // Ако не е дефинирана константата или, ако e IP-то ни е от масива
        if (defined('EF_DEBUG') && (EF_DEBUG === TRUE || strpos('' . EF_DEBUG, $realIpAdd) !== FALSE)) {
            self::$isDebug = TRUE;

            return TRUE;
        }
        
        $cookieName = self::getDebugCookie();
 
        if($_COOKIE[$cookieName]) {
            self::$isDebug = TRUE;

            return TRUE;
        }

        // Не се намираме в DEBUG режим
        self::$isDebug = FALSE;

        return FALSE;
    }

    
    /**
     * Връща масив с два елемента - име на куки и стойност на куки, които са флаг за дебъг режим
     * Те за висят от IP адреса на потребителя и от EF_SALT
     */
    public static function getDebugCookie()
    {        
        $cookie = 'n' . md5(EF_SALT . $_SERVER['REMOTE_ADDR'] . 'DEBUG2');

        return $cookie;
    }


    /**
     * Задава куки за дебъг режим
     */
    public static function setDebugCookie()
    { 
        setcookie(self::getDebugCookie(), time(), time() + DEBUG_COOKIE_LIFETIME, '/');
 
        self::$isDebug = TRUE;
    }

}