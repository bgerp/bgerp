<?php


class core_App
{
    /**
     * Последното ръчно зададено максимално време за изпълнение на скрипта
     *
     * @var int
     */
    protected static $runningTimeLimit;
    
    
    /**
     * Кога е зададено последно увеличение на времето за изпълнение на скрипта
     *
     * @var int
     */
    protected static $timeSetTimeLimit;
    
    
    public static function run()
    {
        $boot = trim(getBoot(), '/\\');
        $vUrl = trim($_GET['virtual_url'], '/\\');
        if (!strlen($boot) || strlen($boot) && strpos($vUrl, $boot) === 0) {
            $filename = strtolower(trim(substr($vUrl, strlen($boot)), '/\\'));
        }
        
        if (preg_match('/^[a-z0-9_\\-]+\\.[a-z0-9]{2,4}$/i', $filename)) {
            
            // Ако имаме заявка за статичен файл от коренната директория на уеб-сървъра
            core_Webroot::serve($filename);
        } elseif (isset($_GET[EF_SBF]) && !empty($_GET[EF_SBF])) {
            // Ако имаме заявка за статичен ресурс, веднага го сервираме и
            // приключване. Ако не - продъжаваме със зареждането на фреймуърка
            
            core_Sbf::serveStaticFile($_GET[EF_SBF]);
        } else {
            
            // Зареждаме класа регистратор на плъгините
            core_Cls::get('core_Plugins');
            
            // Задаваме стойности по подразбиране на обкръжението
            if (!core_Mode::is('screenMode')) {
                core_Mode::set('screenMode', log_Browsers::detectMobile() ? 'narrow' : 'wide');
            }
            
            // Ако в момента се извършва инсталация - да не се изпълняват процесите
            core_SystemLock::stopIfBlocked();
            
            // Генерираме съдържанието
            $content = core_Request::forward();
            
            // Опакова съдържанието
            $Wrapper = core_Cls::get('core_page_Wrapper');
            $Wrapper->render($content);
        }
    }
    
    
    /**
     * Начално инициализиране на приложението и системата
     */
    public static function initSystem()
    {
        /**
         * Дефинира, ако не е зададено името на кода на приложението
         */
        defIfNot('EF_APP_CODE_NAME', 'bgerp');
        
        
        // Регистрираме функция за автоматично зареждане на класовете
        spl_autoload_register(array('core_App', 'classAutoload'), true, true);
        
        
        /**
         * Директорията с конфигурационните файлове
         */
        defIfNot('EF_CONF_PATH', EF_ROOT_PATH . '/conf');
        
        
        /**
         * По подразбиране от локалния хост се работи в режим DEBUG
         */
        defIfNot('EF_DEBUG_HOSTS', 'localhost,127.0.0.1,::1');
        
        
        // Ако index.php стои в директория с име, за което съществува конфигурационен
        // файл, приема се, че това име е името на приложението
        if (!defined('EF_APP_NAME') &&
            file_exists(EF_CONF_PATH . '/' . basename(EF_INDEX_PATH) . '.cfg.php')) {
            
            /**
             * Името на приложението. Използва се за определяне на други константи
             */
            DEFINE('EF_APP_NAME', basename(EF_INDEX_PATH));
        }
        
        
        /**
         * Базовото име на директорията за статичните браузърни файлове
         */
        defIfNot('EF_SBF', 'sbf');
        
        
        // Вътрешно кодиране
        mb_internal_encoding('UTF-8');
        
        
        // Локал за функции като basename
        setlocale(LC_ALL, 'en_US.UTF8');
    }
    
    
    /**
     * Вкарва контролерните параметри от $_POST заявката
     * и виртуалното URL в $_GET заявката
     *
     * @return array
     */
    public static function processUrl()
    {
        $q = array();
       
        // Подготвяме виртуалното URL
        if (!empty($_GET['virtual_url'])) {
            $dir = dirname($_SERVER['SCRIPT_NAME']);
            
            $len = ($dir == DIRECTORY_SEPARATOR) ? 1 : strlen($dir) + 1;
            
            $_GET['virtual_url'] = substr($_SERVER['REQUEST_URI'], $len);
            
            $script = '/' . basename($_SERVER['SCRIPT_NAME']);
            
            if (($pos = strpos($_GET['virtual_url'], $script)) === false) {
                $pos = strpos($_GET['virtual_url'], '?');
            }
            
            if ($pos !== false) {
                $_GET['virtual_url'] = rtrim(substr($_GET['virtual_url'], 0, $pos + 1), '?/') . '/';
            }
        }
     
        // Опитваме се да извлечем името на модула
        // Ако имаме виртуално URL - изпращаме заявката към него
        if (!empty($_GET['virtual_url'])) {
            
            // Ако виртуалното URL не завършва на'/', редиректваме към адрес, който завършва
            $vUrl = explode('/', $_GET['virtual_url']);
               
            // Премахваме последният елемент
            $cnt = count($vUrl);
            
            if (!strlen($vUrl[$cnt - 1])) {
                unset($vUrl[$cnt - 1]);
            }
            
            if (defined('EF_APP_NAME')) {
                $q['App'] = EF_APP_NAME;
            }
            
            if (defined('EF_CTR_NAME')) {
                $q['Ctr'] = EF_CTR_NAME;
            }
            
            if (defined('EF_ACT_NAME')) {
                $q['Act'] = EF_ACT_NAME;
            }
             
            foreach ($vUrl as $id => $prm) {
                // Определяме случая, когато заявката е за браузърен ресурс
                if ($id == 0 && $prm == EF_SBF) {
                    if (!isset($q['App'])) {
                        $q['App'] = $vUrl[1];
                    }
                    unset($vUrl[0], $vUrl[1]);
                    $q[EF_SBF] = implode('/', $vUrl);
                    break;
                }
                
                // Дали това не е името на приложението?
                if (!isset($q['App']) && $id == 0) {
                    $q['App'] = preg_replace("/[^a-zA-Z0-9_\-]*/", '', strtolower($prm));
                    continue;
                }
                
                // Дали това не е име на контролер?
                if (!isset($q['Ctr']) && $id < 2) {
                    if (!preg_Match('/([A-Z])/', $prm)) {
                        $last = strrpos($prm, '_');
                        
                        if ($last !== false && $last < strlen($prm)) {
                            $className{$last + 1} = strtoupper($prm{$last + 1});
                        } else {
                            $className{0} = strtoupper($prm{0});
                        }
                    }
                    $q['Ctr'] = preg_replace('/[^a-zA-Z0-9_]*/', '', $prm);
                    continue;
                }
                
                // Дали това не е име на екшън?
                if (!isset($q['Act']) && $id < 3) {
                    $q['Act'] = $prm;
                    continue;
                }
                
                if ((count($vUrl) - $id) % 2 || floor($prm) > 0) {
                    if (!isset($q['id']) && !$name) {
                        $q['id'] = decodeUrl($prm);
                    } else {
                        if ($name) {
                            $q[$name] = $prm;
                        }
                    }
                } else {
                    $name = $prm;
                }
            }
            
            // Вкарваме получените параметри от $_POST заявката
            // или от виртуалното URL в $_GET заявката
            foreach ($q as $var => $value) {
                if (!isset($_GET[$var]) || !$_GET[$var]) {
                    if (isset($_POST[$var]) && !empty($_POST[$var])) {
                        $_GET[$var] = $_POST[$var];
                    } elseif (isset($q[$var]) && (strlen($q[$var]))) {
                        $_GET[$var] = $q[$var];
                    }
                }
            }
        }
        
        // Възможно е App да бъде получено само от POST заявка
        if (empty($_GET['App']) && !empty($_POST['App'])) {
            $_GET['App'] = $_POST['App'];
        }
        
        // Абсолютен дефолт за името на приложението
        if (empty($_GET['App']) && defined('EF_DEFAULT_APP_NAME')) {
            $_GET['App'] = EF_DEFAULT_APP_NAME;
        }
        
        return $q;
    }
    
    
    /**
     * Зареждане на глобалните конфигурационни константи
     */
    public static function loadConfig()
    {
        // Вземаме името на приложението от параметрите на URL, ако не е дефинирано
        if (!defined('EF_APP_NAME')) {
            if (!$_GET['App']) {
                halt('Error: Unable to determinate application name (EF_APP_NAME)</b>');
            }
            
            
            /**
             * Името на приложението. Използва се за определяне на други константи.
             */
            defIfNot('EF_APP_NAME', $_GET['App']);
            
            
            /**
             * Дали името на приложението е зададено фиксирано
             */
            DEFINE('EF_APP_NAME_FIXED', false);
        } else {
            
            /**
             * Дали името на приложението е зададено фиксирано
             */
            DEFINE('EF_APP_NAME_FIXED', true);
        }
        
        
        // Зареждаме конфигурационния файл на приложението.
        // Ако липсва - показваме грешка.
        // Шаблон за този файл има в директорията [_docs]
        if ((include EF_CONF_PATH . '/' . EF_APP_NAME . '.cfg.php') === false) {
            halt('Error in boot.php: Missing configuration file: ' .
                EF_CONF_PATH . '/' . EF_APP_NAME . '.cfg.php');
        }
        
        
        /**
         * Пътя до директорията за статичните браузърни файлове към приложението
         */
        defIfNot('EF_SBF_PATH', EF_INDEX_PATH . '/' . EF_SBF . '/' . EF_APP_NAME);
        
        
        /**
         * Базова директория, където се намират под-директориите с временни файлове.
         * По подразбиране използваме системната директория за временни файлове.
         *
         * @see http://php.net/manual/en/function.sys-get-temp-dir.php
         */
        defIfNot('EF_TEMP_BASE_PATH', sys_get_temp_dir());
        
        
        /**
         * Директорията с временни файлове
         */
        defIfNot('EF_TEMP_PATH', EF_TEMP_BASE_PATH . '/' . EF_APP_NAME);
        
        
        // Подсигуряваме се, че временната директория съществува
        core_Os::forceDir(EF_TEMP_PATH);
        
        
        /**
         * Директорията с качените и генерираните файлове
         */
        if (!defined('EF_UPLOADS_PATH')) {
            if (defined('EF_UPLOADS_BASE_PATH')) {
                define('EF_UPLOADS_PATH', EF_UPLOADS_BASE_PATH . '/' . EF_APP_NAME);
            } elseif (defined('EF_ROOT_PATH')) {
                define('EF_UPLOADS_PATH', EF_ROOT_PATH . '/uploads/' . EF_APP_NAME);
            } else {
                die('Not possible to determine `EF_UPLOADS_PATH`');
            }
        }
        
        
        /**
         * Времева зона
         */
        defIfNot('EF_TIMEZONE', 'Europe/Sofia');
        
        
        // Сетваме времевата зона
        date_default_timezone_set(EF_TIMEZONE);
        
        // На кой бранч от репозиторито е кода?
        defIfNot('BGERP_GIT_BRANCH', 'dev');
        
        // Ако паметта за скрипта е под 512М я правим на 512М
        if (core_Os::getBytesFromMemoryLimit() < core_Os::getBytes('512M')) {
            ini_set('memory_limit', '512M');
        }
    }
    
    
    /**
     * Завършване на изпълнението на програмата
     *
     * @param bool $sendOutput
     */
    public static function shutdown($sendOutput = true)
    {
        // Флъшваме и затваряме връзката, като евентулано показваме съдържанието в буфера
        core_App::flushAndClose($sendOutput);
        
        // Генерираме събитието 'suthdown' във всички сингълтон обекти
        core_Cls::shutdown();
        
        // Проверяваме състоянието на системата и ако се налага репортва
        self::checkHitStatus();
        
        // Спираме изпълнението и излизаме
        self::exitScript();
    }
    
    
    /**
     * Излизаме от изпълнението на скрипта
     */
    public static function exitScript()
    {
        if (defined('DEBUG_FATAL_ERRORS_FILE')) {
            $debugCode = '2';
            $logHit = true;
            if (Request::get('ajax_mode')) {
                $debugCode = '8';
                $logHit = false;
                if (Request::get('logAjax') || (defined('DEBUG_FATAL_ERRORS_ON_AJAX') && DEBUG_FATAL_ERRORS_ON_AJAX)) {
                    $logHit = true;
                }
            }
            
            // Добавяме времето за изпълнение
            $execTime = Debug::getExecutionTime();
            $execTime = number_format($execTime, 0);
            
            if ($execTime >= 2) {
                $logHit = true;
            }
            
            // Времето за изпълнение го правим стъпково
            if ($execTime <= 1) {
                $execTime = 0;
            } elseif ($execTime <= 5) {
                $execTime = 5;
            } elseif ($execTime <= 20) {
                $execTime = 20;
            } else {
                $execTime = 50;
            }
            
            $execTime = str_pad($execTime, 2, '0', STR_PAD_LEFT);
            $debugCode .= $execTime;
            
            if ($logHit) {
                logHitState($debugCode);
            }
            
            @unlink(DEBUG_FATAL_ERRORS_FILE);
        }
        
        // Излизаме със зададения статус
        exit();
    }
    
    
    /**
     * Проверява състоянието на системата и ако се налага репортва
     */
    public static function checkHitStatus()
    {
        $memUsagePercentLimit = 80;
        $executionTimePercentLimit = 70;
        
        $memoryLimit = core_Os::getBytesFromMemoryLimit();
        
        $realUsage = true;
        
        $peakMemUsage = memory_get_peak_usage($realUsage);
        if (is_numeric($memoryLimit) && $memoryLimit) {
            $peakMemUsagePercent = ($peakMemUsage / $memoryLimit) * 100;
            
            // Ако сме доближили до ограничението на паметта
            if ($peakMemUsagePercent > $memUsagePercentLimit) {
                wp();
            }
        }
        
        $memUsage = memory_get_usage($realUsage);
        if (is_numeric($memUsage) && $memoryLimit) {
            $memUsagePercent = ($memUsage / $memoryLimit) * 100;
            
            // Ако сме доближили до ограничението на паметта
            if ($memUsagePercent > $memUsagePercentLimit) {
                wp();
            }
        }
        
        $maxExecutionTime = ini_get('max_execution_time');
        if ($maxExecutionTime && core_Debug::$startMicroTime) {
            if (core_Debug::$startMicroTime) {
                $executionTime = core_DateTime::getMicrotime() - core_Debug::$startMicroTime;
                
                $maxExecutionTimePercent = ($executionTime / $maxExecutionTime) * 100;
                
                // Ако сме доближили до ограничението за времето
                if ($maxExecutionTimePercent > $executionTimePercentLimit) {
                    wp();
                }
            }
        }
    }
    
    
    /**
     * Изпраща всичко буферирано към браузъра и затваря връзката
     */
    public static function flushAndClose($output = true)
    {
        static $oneTimeFlag;
        
        if ($oneTimeFlag) {
            
            return;
        }
        $oneTimeFlag = true;
        
        ignore_user_abort(true);
        
        // Освобождава манипулатора на сесията. Ако трябва да се правят
        // записи в сесията, то те трябва да се направят преди shutdown()
        core_Session::pause();
        
        if ($output) {
            $content = ob_get_contents();         // Get the content of the output buffer
            if (strlen($content) == 0) {
                $output = false;
            }
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
        }
        
        if (!headers_sent()) {
            header('Connection: close');
            if ($_SERVER['REQUEST_METHOD'] != 'HEAD' && $output) {
                $supportsGzip = strpos( $_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip' ) !== false;
                if ( $supportsGzip && strlen($content) > 1000) {
                    $content = gzencode($content);
                    header('Content-Encoding: gzip');
                }
                $len = strlen($content);
                header("Content-Length: ${len}");

            } else {
                if ($_SERVER['REQUEST_METHOD'] != 'HEAD') {
                    header('Content-Length: 2');
                    header("Content-Encoding: none");
                    $content = 'OK';
                    $len = 2;
                    $output = true;
                } else {
                    header('Content-Length: 0');
                }
            }
            
            
            // Добавяме допълнителните хедъри
            $aHeadersArr = self::getAdditionalHeadersArr();
            foreach ($aHeadersArr as $hStr) {
                header($hStr);
            }
        }
        
        if (($_SERVER['REQUEST_METHOD'] != 'HEAD') && $output && $len) {
            echo $content; // Output content
        } else {
            if (!headers_sent()) {
                header('Content-Encoding: none');
            }
        }
        
        if ($output) {
            ob_end_flush();
            ob_flush();
        }
        
        // Изпращаме съдържанието на изходния буфер
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        }
        
        flush();
    }
    
    
    /**
     * Връща допълнителните хедъри за страницата
     *
     * @return array
     */
    public static function getAdditionalHeadersArr()
    {
        $resArr = array();
        
        if ((BGERP_GIT_BRANCH == 'dev') || (BGERP_GIT_BRANCH == 'test')) {
            if (EF_HTTPS == 'MANDATORY') {
                $resArr[] = 'Strict-Transport-Security: max-age=86400';
            }
            
            $resArr[] = 'X-Frame-Options: sameorigin';
            $resArr[] = 'X-XSS-Protection: 1; mode=block';
            $resArr[] = 'X-Content-Type-Options: nosniff';
            $resArr[] = 'Expect-CT: max-age=86400, enforce';
            $resArr[] = "Feature-Policy: camera 'self'; microphone 'self'";
        }
        
        return $resArr;
    }
    
    
    /**
     * Спира обработката и извежда съобщение за грешка или го записв в errorLog
     */
    public static function halt($err)
    {
        if (isDebug()) {
            echo '<li>' . $err . ' | Halt on ' . date('d.m.Y H:i:s');
        } else {
            echo 'On ' . date('d.m.Y H:i:s') . ' a System Error has occurred';
        }
        
        error_log('HALT: ' . $err);
        
        exit(-1);
    }
    
    
    /**
     * Редиректва браузъра към посоченото URL
     * Добавя сесийния идентификатор, ако е необходимо
     */
    public static function redirect($url, $absolute = false, $msg = null, $type = 'notice')
    {
        // Очакваме най-много три символа (BOM) в буфера
        expect(ob_get_length() <= 3, array(ob_get_length(), ob_get_contents()));
        
        $hitId = Request::get('hit_id');
        
        if (isset($msg)) {
            $msgTrim = trim($msg);
            if (strlen($msgTrim)) {
                if (!$hitId) {
                    $hitId = str::getRand();
                }
                
                core_Statuses::newStatus($msg, $type, null, 60, $hitId);
            }
        }
        
        if ($hitId) {
            if (is_array($url)) {
                $url['hit_id'] = $hitId;
            } elseif ($url) {
                $url = core_Url::change($url, array('hit_id' => $hitId));
            }
        }
        
        $url = toUrl($url, $absolute ? 'absolute' : 'relative');
        
        if (Request::get('_bp') == 'redirect') {
            bp($url);
        }
        
        if (Request::get('ajax_mode')) {
            
            // Ако сме в Ajax_mode редиректа става чрез Javascript-а
            $resObj = new stdClass();
            $resObj->func = 'redirect';
            $resObj->arg = array('url' => $url);
            
            return self::outputJson(array($resObj));
        }
        
        // Забранява кеширането. Дали е необходимо тук?
        header('Cache-Control: no-cache, must-revalidate'); // HTTP 1.1.
        header('Expires: 0'); // Proxies.
        
        header("Location: ${url}", true, 302);
        
        static::shutdown(false);
    }
    
    
    /**
     * Проверява текущия хост (или ако е дефиниран, хоста от константа) дали е от частна мрежа
     *
     * @return bool
     */
    public static function checkCurrentHostIsPrivate()
    {
        static $status;
        
        if (!isset($status)) {
            $sHost = defined('BGERP_ABSOLUTE_HTTP_HOST') ? BGERP_ABSOLUTE_HTTP_HOST : $_SERVER['HTTP_HOST'];
            
            $status = core_Url::isPrivate($sHost);
        }
        
        return $status;
    }
    
    
    /**
     * Изкарва (към клиента) резултата, като JSON и спира процеса
     *
     * $resArr array
     */
    public static function outputJson($resArr, $sendOutupt = true)
    {
        // За да не се кешира
        header('Expires: Sun, 19 Nov 1978 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        
        // Указваме, че ще се връща JSON
        header('Content-Type: application/json');
        
        // Добавяме допълнителните хедъри
        $aHeadersArr = self::getAdditionalHeadersArr();
        foreach ($aHeadersArr as $hStr) {
            header($hStr);
        }
        
        // Връщаме резултата в JSON формат
        echo json_encode($resArr);
        
        // Прекратяваме процеса
        self::shutdown($sendOutupt);
    }
    
    
    /**
     * Връща текущото GET URL
     */
    public static function getCurrentUrl()
    {
        $parentUrlArr = array();
        
        // Ако заявката е по AJAX
        if (Request::get('ajax_mode')) {
            
            // Ако е зададено URL на страницата, от която се вика AJAX заявката
            $parentUrlStr = Request::get('parentUrl');
            if ($parentUrlStr) {
                
                // Парсираме URL-то
                $parentUrlArr = static::parseLocalUrl($parentUrlStr);
            }
        }
        
        if (!empty($parentUrlArr)) {
            $params = $parentUrlArr;
        } else {
            // Всички параметри в рекуеста
            $params = Request::getParams('_GET');
            $allParams = Request::getParams();
            
            foreach ($params as $key => $p) {
                $params[$key] = $allParams[$key];
            }
        }
        
        // Ако има параметри
        if (!empty($params)) {
            
            // Премахваме ненужните
            unset($params['virtual_url'], $params['ajax_mode']);
        } else {
            $params = array();
        }
        
        return $params;
    }
    
    
    /**
     *  Връща масив, който представлява вътрешното представяне на
     * локалното URL подадено като аргумент
     */
    public static function parseLocalUrl($str, $unprotect = true)
    {
        $get = array();
        if ($str) {
            $arr = explode('/', $str);
            
            $get['App'] = $arr[0];
            $get['Ctr'] = $arr[1];
            $get['Act'] = $arr[2];
            $begin = 3;
            
            $cnt = count($arr);
            
            if (count($arr) % 2 == (($begin - 1) % 2)) {
                $get['id'] = $arr[$begin];
                $begin++;
            }
            
            for ($i = $begin; $i < $cnt; $i += 2) {
                $key = $arr[$i];
                $value = $arr[$i + 1];
                $value = decodeUrl($value);
                $key = explode(',', $key);
                
                if (count($key) == 1) {
                    $get[$key[0]] = $value;
                } elseif (count($key) == 2) {
                    $get[$key[0]][$key[1]] = $value;
                } else {
                    // Повече от едномерен масив в URL-то не се поддържа
                    error('Повече от едномерен масив в URL-то не се поддържа', $key);
                }
            }
            
            // Премахваме защитата на id-то, ако има такава
            if ($get['id'] && $unprotect) {
                expect($get['id'] = Request::unprotectId($get['id'], $get['Ctr']), $get, core_Request::get('ret_url'));
            }
            
            if ($get['App']) {
                if ($app = Request::get('App')) {
                    $get['App'] = $app;
                }
            }
        }
        
        return $get;
    }
    
    
    /**
     * Връща масив, който представлява URL-то където трябва да
     * се използва за връщане след изпълнението на текущата задача
     */
    public static function getRetUrl()
    {
        $retUrl = core_Request::get('ret_url');
        
        $res = self::parseLocalUrl($retUrl);
        
        return $res;
    }
    
    
    /**
     * Пренасочва към RetUrl
     *
     * @see redirect()
     *
     * @param mixed  $defaultUrl използва този URL ако не може да установи RetUrl
     * @param string $msg        съобщение - нотификация
     * @param string $type       тип на нотификацията
     */
    public static function followRetUrl($defaultUrl = null, $msg = null, $type = 'notice')
    {
        if (!$retUrl = static::getRetUrl()) {
            $retUrl = $defaultUrl;
        }
        
        if (!$retUrl) {
            $retUrl = array(
                'Index',
                'default'
            );
        }
        
        static::redirect($retUrl, false, $msg, $type);
    }
    
    
    /**
     * @todo Чака за документация ...
     */
    public static function toLocalUrl($arr)
    {
        if (is_array($arr)) {
            if (!$arr['Act']) {
                $arr['Act'] = 'default';
            }
            
            $url .= $arr['App'];
            $url .= '/' . $arr['Ctr'];
            $url .= '/' . $arr['Act'];
            
            if (isset($arr['id'])) {
                $url .= '/' . $arr['id'];
            }
            unset($arr['App'], $arr['Ctr'], $arr['Act'], $arr['id']);
            
            foreach ($arr as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        if (is_array($v)) {
                            wp($v);
                        }
                        $url .= ($url ? '/' : '') . "{$key},{$k}/" . @urlencode($v);
                    }
                } else {
                    if (is_array($value)) {
                        wp($value);
                    }
                    $url .= ($url ? '/' : '') . "{$key}/" . @urlencode($value);
                }
            }
        } else {
            
            return $arr;
        }
        
        return $url;
    }
    
    
    /**
     * Създава URL от параметрите
     *
     * @param array  $params
     * @param string $type         Може да бъде relative|absolute|internal|local
     * @param bool   $protect
     * @param array  $preParamsArr - Масив с имената на параметрите, които да се добавят в pre, вместо като GET
     *
     * @return string
     */
    public static function toUrl($params = array(), $type = null, $protect = true, $preParamsArr = array())
    {
        if (!$params) {
            $params = array();
        }
        
        if ($type === null) {
            if (Mode::is('text', 'xhtml') || Mode::is('text', 'plain') || Mode::is('pdf') || Mode::is('BGERP_CURRENT_DOMAIN')) {
                $type = 'absolute';
            } else {
                $type = 'relative';
            }
        }
        
        // TRUE == 'absolute', FALSE == 'relative'
        if ($type === true) {
            $type = 'absolute';
        } elseif ($type === false) {
            $type = 'relative';
        }
        
        // Ако параметъра е стринг - нищо не правим
        if (is_string($params)) {
            
            return $params;
        }
        
        // Очакваме, че параметъра е масив
        expect(is_array($params), $params, 'toUrl($params) Очаква масив');
        
        $Request = core_Cls::get('core_Request');
        
        if ($params[0]) {
            $params['Ctr'] = $params[0];
            unset($params[0]);
        }
        
        if (is_object($params['Ctr'])) {
            $params['Ctr'] = core_Cls::getClassName($params['Ctr']);
        }
        
        if ($params[1]) {
            $params['Act'] = $params[1];
            unset($params[1]);
        }
        
        if ($params[2]) {
            $params['id'] = $params[2];
            unset($params[2]);
        }
        
        if (!$params['App']) {
            $params['App'] = $Request->get('App');
        }
        
        if (is_string($params['Ctr']) && !$params['Ctr']) {
            $params['Ctr'] = EF_DEFAULT_CTR_NAME;
        }
        
        if (is_string($params['Act']) && !$params['Act']) {
            $params['Act'] = EF_DEFAULT_ACT_NAME;
        }
        
        if (!$params['Ctr']) {
            $params['Ctr'] = $Request->get('Ctr');
            
            if (!$params['Ctr']) {
                $params['Ctr'] = 'Index';
            }
            
            if (!$params['Act']) {
                $params['Act'] = $Request->get('Act');
            }
        }
        
        // Ако има параметър ret_url - адрес за връщане, след изпълнение на текущата операция
        // И той е TRUE - това е сигнал да вземем текущото URL
        if ($params['ret_url'] === true) {
            if ($retUrl = Mode::get('ret_url')) {
                $params['ret_url'] = $retUrl;
            } else {
                $params['ret_url'] = self::getCurrentUrl();
            }
        }
        
        // Ако ret_url е масив - кодирамего към локално URL
        core_Request::addUrlHash($params);
        
        if ($protect) {
            $Request->doProtect($params);
        }
        
        // Ако е необходимо локално URL, то то се генерира с помощна функция
        if ($type == 'local') {
            
            return static::toLocalUrl($params);
        }
        
        // Зпочваме подготовката на URL-то
        
        // Махаме префикса на пакета по подразбиране
        $appPref = EF_APP_CODE_NAME . '_';
        
        // Очакваме името на контролера да е стринг
        expect(is_string($params['Ctr']), $appPref, $Request, $params);
        
        // Маха префикса, ако той съвпада с името на кода
        if (strpos($params['Ctr'], $appPref) === 0) {
            $params['Ctr'] = substr($params['Ctr'], strlen($appPref));
        }
        
        // Задължително слагаме контролера
        $pre .= '/' . $params['Ctr'] . '/';
        
        if ($params['Act'] && (strtolower($params['Act']) !== 'default' || $params['id'])) {
            $pre .= $params['Act'] . '/';
        }
        
        if ($params['id']) {
            $pre .= urlencode($params['id']) . '/';
        }
        
        unset($params['Ctr'], $params['App'], $params['Act'], $params['id']);
        
        // Ако е сетнат масива
        if (!empty($preParamsArr)) {
            
            // В пътя допускаме само букви, цифри , тере, долна черта и точка
            $pattern = "/^[A-Za-z0-9_\-\.]*$/";
            
            // Обхождаме всички параметри
            foreach ($preParamsArr as $param) {
                
                // Ако има стойност
                if (isset($params[$param])) {
                    
                    // Ако не отоговаря на регулярния израз, да се остави за GET
                    if (preg_match($pattern, $param) && preg_match($pattern, $params[$param])) {
                        
                        // Добавяме към стринга
                        $pre .= urlencode($param) . '/' . urlencode($params[$param]) . '/';
                        
                        // Премахваме от масива
                        unset($params[$param]);
                    }
                }
            }
        }
        
        if ($urlHash = $params['#']) {
            unset($params['#']);
        }
        
        if (count($params)) {
            $urlQuery = http_build_query($params);
        }
        
        if ($urlQuery) {
            $urlQuery = '/?' . $urlQuery;
        }
        
        if ($urlHash) {
            $urlQuery .= '#' . $urlHash;
        }
        
        $pre = rtrim($pre, '/');
        
        switch ($type) {
            case 'local':
                $url = ltrim($pre . $urlQuery, '/');
                break;
            
            case 'relative':
                $url = rtrim(static::getBoot(false, false, true), '/') . $pre . $urlQuery;
                break;
            
            case 'absolute':
                $url = rtrim(static::getBoot(true, false, true), '/') . $pre . $urlQuery;
                break;
            
            case 'absolute-force':
                $url = rtrim(static::getBoot(true, true, true), '/') . $pre . $urlQuery;
                break;
        }
        
        return $url;
    }
    
    
    /**
     * Връща целия текущ URL адрес
     */
    public static function getSelfURL()
    {
        $s = empty($_SERVER['HTTPS']) ? '' : ($_SERVER['HTTPS'] == 'on') ? 's' : '';
        $slashPos = strpos($_SERVER['SERVER_PROTOCOL'], '/');
        $protocol = substr(strtolower($_SERVER['SERVER_PROTOCOL']), 0, $slashPos) . $s;
        
        return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
    
    
    /**
     * Връща относително или пълно URL до папката на index.php
     *
     * @param bool $absolute
     * @param bool $forceHttpHost
     *
     * @return string
     */
    public static function getBoot($absolute = false, $forceHttpHost = false, $addAppName = false)
    {
        static $relativeWebRoot = null;
        
        if ($absolute) {
            $s = empty($_SERVER['HTTPS']) ? '' : ($_SERVER['HTTPS'] == 'on') ? 's' : '';
            $slashPos = strpos($_SERVER['SERVER_PROTOCOL'], '/');
            $protocol = substr(strtolower($_SERVER['SERVER_PROTOCOL']), 0, $slashPos) . $s;
            
            $dirName = dirname($_SERVER['SCRIPT_NAME']);
            
            $dirName = str_replace(DIRECTORY_SEPARATOR, '/', $dirName);
            
            if ($username = $_SERVER['PHP_AUTH_USER']) {
                $password = $_SERVER['PHP_AUTH_PW'];
                $auth = $username . ':' . $password . '@';
            } else {
                $auth = '';
            }
            
            if ($domain = Mode::get('BGERP_CURRENT_DOMAIN')) {
                $boot = $protocol . '://' . $auth . $domain . $dirName;
            } elseif (defined('BGERP_ABSOLUTE_HTTP_HOST') && !$forceHttpHost) {
                $boot = $protocol . '://' . $auth . BGERP_ABSOLUTE_HTTP_HOST . $dirName;
            } else {
                $boot = $protocol . '://' . $auth . $_SERVER['HTTP_HOST'] . $dirName;
            }
        } else {
            $scriptName = $_SERVER['SCRIPT_NAME'];
            
            if (!isset($relativeWebRoot)) {
                $relativeWebRoot = str_replace('/index.php', '', $scriptName);
                if ($relativeWebRoot == '/') {
                    $relativeWebRoot = '';
                }
            }
            
            $boot = $relativeWebRoot;
        }
        
        $boot = rtrim($boot, '/');
        
        if (EF_APP_NAME_FIXED !== true && $addAppName) {
            $boot .= '/' . (Request::get('App') ? Request::get('App') : EF_APP_NAME);
        }
        
        return $boot;
    }
    
    
    /**
     * Връща масив с пътищата до всички репозиторита, които участват в системата, като ключове и
     * '' или името на бранча, ако е аргумента $ branches
     *
     * @return array
     */
    public static function getRepos()
    {
        static $repos;
        
        static $havePrivate = false;
        
        if (!is_array($repos)) {
            $repos = array();
            $repos = self::getReposByPathAndBranch(EF_APP_PATH, defined('BGERP_GIT_BRANCH') ? BGERP_GIT_BRANCH : null);
        }
        
        if (!$havePrivate && defined('EF_PRIVATE_PATH')) {
            $repos = self::getReposByPathAndBranch(EF_PRIVATE_PATH, defined('PRIVATE_GIT_BRANCH') ? PRIVATE_GIT_BRANCH : (defined('BGERP_GIT_BRANCH') ? BGERP_GIT_BRANCH : null)) + $repos;
            $havePrivate = true;
        }
        
        return $repos;
    }
    
    
    /**
     * При зададени списъци с пътища и бранчове, връща масив в който ключове са пътищата, а стойности - бранчовета
     */
    private static function getReposByPathAndBranch($paths, $branches)
    {
        $pathArr = explode(',', str_replace(array('\\', ';'), array('/', ','), $paths));
        $pathArr = array_filter($pathArr, function ($value) {
            
            return trim($value) !== '';
        });
        $branchArr = explode(',', str_replace(array('\\', ';'), array('/', ','), $branches));
        
        $branchArr = array_filter($branchArr, function ($value) {
            
            return trim($value) !== '';
        });
        $cntBranches = count($branchArr);
        
        $res = array();
        foreach ($pathArr as $i => $line) {
            if (strpos($line, '=')) {
                list($p, $b) = explode('=', $line);
            } else {
                $p = $line;
                $b = null;
            }
            
            if (empty($b) && $cntBranches) {
                $b = $branchArr[min($i, $cntBranches - 1)];
            }
            $res[$p] = $b;
        }
        
        return $res;
    }
    
    
    /**
     * Тази функция определя пълния път до файла.
     * Като аргумент получава последната част от името на файла
     * Файла се търси в EF_PRIVATE_PATH, EF_APP_PATH
     * Ако не бъде открит, се връща FALSE
     */
    public static function getFullPath($shortPath)
    {
        // Не може да има връщане назад, в името на файла
        expect(!preg_match('/\.\.(\\\|\/)/', $shortPath));
        
        $repos = self::getRepos();
        
        foreach (array_keys($repos) as $base) {
            $fullPath = $base . '/' . $shortPath;
            
            if (@is_readable($fullPath)) {
                
                return $fullPath;
            } elseif (stripos($shortPath, $base) === 0 && @is_readable($shortPath)) {
                
                return $shortPath;
            }
        }
        
        return false;
    }
    
    
    /**
     * Връща съдържанието на файла, като стринг
     * Пътя до файла може да е указан само от пакета нататък
     */
    public static function getFileContent($shortPath)
    {
        if (!$shortPath) {
            
            return;
        }
        
        expect($fullPath = static::getFullPath($shortPath));
        
        return file_get_contents($fullPath);
    }
    
    
    /**
     * Задава стойността(ите) от втория параметър на първия,
     * ако те не са установени
     *
     * @todo: използва ли се тази функция за масиви?
     */
    public static function setIfNot(&$p1, $p2)
    {
        $args = func_get_args();
        
        for ($i = 1; $i < func_num_args(); $i++) {
            $new = $args[$i];
            
            if (is_array($p1)) {
                if (!count($new)) {
                    continue;
                }
                
                foreach ($new as $key => $value) {
                    if (!isset($p1[$key])) {
                        $p1[$key] = $value;
                    }
                }
            } else {
                if (!isset($p1)) {
                    $p1 = $new;
                } else {
                    
                    return $p1;
                }
            }
        }
        
        return $p1;
    }
    
    
    /**
     * Осигурява автоматичното зареждане на класовете
     */
    private static function classAutoload($className)
    {
        $aliases = array(
            'arr' => 'core_Array',
            'dt' => 'core_DateTime',
            'keylist' => 'type_Keylist',
            'ht' => 'core_Html',
            'et' => 'core_ET',
            'str' => 'core_String',
            'debug' => 'core_Debug',
            'mode' => 'core_Mode',
            'redirect' => 'core_Redirect',
            'request' => 'core_Request',
            'url' => 'core_Url',
            'users' => 'core_Users',
            'ut' => 'unit_Tests',
            'fileman' => 'fileman_Files',
        );
        
        if (isset($aliases[strtolower($className)]) && $fullName = $aliases[strtolower($className)]) {
            if (core_Cls::load($fullName)) {
                class_alias($fullName, $className);
                
                return true;
            }
        } else {
            
            return core_Cls::load($className, true);
        }
    }
    
    
    /**
     * Увеличава времето за изпълнение на скрипта, само ако
     * вече не е зададено по-голямо време
     *
     * @param int  $time    - времето за увеличение в секунди
     * @param bool $force   - форсиране или не
     * @param int  $minTime - минимално време, използва се, ако $time е по-малко от него
     *
     * @return void
     */
    public static function setTimeLimit($time, $force = false, $minTime = 20)
    {
        expect(is_numeric($time));
        
        // Подсигуряване че времето не е много малко
        $time = max($time, $minTime);
        
        $now = time();
        
        // Ако форсираме или новото максимално време за изпълнение е по-голямо от старото задаваме го
        if ($force || (self::$timeSetTimeLimit + self::$runningTimeLimit) < ($now + $time)) {
            
            // Увеличава времето за изпълнение
            set_time_limit($time);
            
            // Записваме последното зададено време за изпълнение;
            self::$runningTimeLimit = $time;
            
            // Записваме времето на последното увеличаване на времето за изпълнение на скрипта
            self::$timeSetTimeLimit = $now;
        }
    }
    
    
    /**
     * Проверка дали връзката е по https
     *
     * @return bool
     */
    public static function isConnectionSecure()
    {
        static $isSecure = null;
        
        if (!isset($isSecure)) {
            $isSecure = (boolean) ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443);
        }
        
        return $isSecure;
    }
}
