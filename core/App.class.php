<?php

class core_App
{
    
    public static $debugHandler = array(__CLASS__, 'bp');

    public static function run()
    {
        try
        {
            // Инициализираме функцията, която се изпълнява при core_App::debug()/wp()
            //self::setDebugHandler();
            
            // Ако имаме заявка за статичен ресурс, веднага го сервираме и
            // приключване. Ако не - продъжаваме със зареждането на фреймуърка
            if ($_GET[EF_SBF]) {
                static::_serveStaticBrowserResource($_GET[EF_SBF]);
            }

            // Зареждаме класа регистратор на плъгините
            core_Cls::get('core_Plugins');

            // Задаваме стойности по подразбиране на обкръжението
            if (!core_Mode::is('screenMode')) {
                core_Mode::set('screenMode', core_Browser::detectMobile() ? 'narrow' : 'wide');
            }
            
            // Генерираме съдържанието
            $content = core_Request::forward();
            
            // Ако не сме в DEBUG режим и заявката е по AJAX
            if (!isDebug() && $_SERVER['HTTP_X_REQUESTED_WITH']) {
                core_Logs::log("Стартиране на core_App::run() през AJAX");
                
                return ;
            }
            
            // Зарежда опаковката
            $Wrapper = core_Cls::get('page_Wrapper');

            $Wrapper->render($content);
     
            // Край на работата на скрипта
            static::shutdown();
        }
        catch (core_exception_Expect $e)
        {
            if($e->class == 'core_Db' && core_Db::databaseEmpty()) {
                // При празна база редиректваме безусловно към сетъп-а
                 redirect(array('Index', 'SetupKey' => setupKey()));
            }
            
            // Ако възникне грешка в core_Message
            // За да не влезе в безкраен редирект
            if ($e->class == 'core_Message') die(tr($e->getMessage()));
            
            $e->getAsHtml();
        }
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

            if(($pos = strpos($_GET['virtual_url'], $script)) === FALSE) {

                $pos = strpos($_GET['virtual_url'], '/?');
            }

            if($pos) {

                $_GET['virtual_url'] = substr($_GET['virtual_url'], 0, $pos + 1);
            }
        }

        // Опитваме се да извлечем името на модула
        // Ако имаме виртуално URL - изпращаме заявката към него
        if (!empty($_GET['virtual_url'])) {

            // Ако виртуалното URL не завършва на'/', редиректваме към адрес, който завършва
            $vUrl = explode('/', $_GET['virtual_url']);

            // Премахваме последният елемент
            $cnt = count($vUrl);

            if (empty($vUrl[$cnt - 1])) {
                unset($vUrl[$cnt - 1]);
            } else {
                if ($vUrl[0] != EF_SBF && (strpos($vUrl[$cnt - 1], '?') === FALSE)) {
                    // Ако не завършва на '/' и не става дума за статичен ресурс
                    // редиректваме към каноничен адрес
                    static::redirect(static::getSelfURL() . '/');
                }
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
                    if (!$q['App']) {
                        $q['App'] = $vUrl[1];
                    }
                    unset($vUrl[0], $vUrl[1]);
                    $q[EF_SBF] = implode('/', $vUrl);
                    break;
                }

                // Дали това не е името на приложението?
                if (!$q['App'] && $id == 0) {
                    $q['App'] = strtolower($prm);
                    continue;
                }

                // Дали това не е име на контролер?
                if (!$q['Ctr'] && $id < 2) {
                    if (!preg_Match("/([A-Z])/", $prm)) {
                        $last = strrpos($prm, '_');

                        if ($last !== FALSE && $last < strlen($prm)) {
                            $className{$last + 1} = strtoupper($prm{$last + 1});
                        } else {
                            $className{0} = strtoupper($prm{0});
                        }
                    }
                    $q['Ctr'] = $prm;
                    continue;
                }

                // Дали това не е име на екшън?
                if (!$q['Act'] && $id < 3) {
                    $q['Act'] = $prm;
                    continue;
                }

                if ((count($vUrl) - $id) % 2) {
                    if (!$q['id'] && !$name) {
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
                if (!$_GET[$var]) {
                    if ($_POST[$var]) {
                        $_GET[$var] = $_POST[$var];
                    } elseif ($q[$var]) {
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
     * Дали се намираме в DEBUG режим
     */
    public static function isDebug()
    {
        // Ако не е дефинирана константата
        if (!defined('EF_DEBUG')) return FALSE;
        
        // Ако e TRUE
        if (EF_DEBUG === TRUE) return TRUE;
        
        // Дали трябва да се пусне дебъг
        static $efDebug = FALSE;
        
        // Флаг, указващ дали сме търсили за хостове
        static $hostsFlag = FALSE;
        
        // Ако за първи път флизаме във функцията
        if (!$hostsFlag) {
            
            $debugArr = explode(':', EF_DEBUG);
            
            // Ако е зададен хоста
            if (strtolower($debugArr[0]) == 'hosts') {
                
                // Масив с хостовете
                $hostsArr = core_Array::make($debugArr[1]);
                
                // IP на потребителя
                $realIpAdd = core_Users::getRealIpAddr();
                
                // Обхождаме масива с хостовете
                foreach ((array)$hostsArr as $host) {
                    
                    // Ако се съдържа в нашия списък
                    if (stripos($realIpAdd, $host) === 0) {
                        
                        // Пускаме дебъг режима
                        ini_set("display_errors", TRUE);
                        ini_set("display_startup_errors", TRUE);
                        $efDebug = TRUE;
                        
                        break;
                    }
                }
            }
            
            // Вдигаме флага
            $hostsFlag = TRUE;
        }
        
        return $efDebug;
    }


    /**
     * Функция, която проверява и ако се изисква, сервира
     * браузърно съдържание html, css, img ...
     */
    protected static function _serveStaticBrowserResource($name)
    {
        $file = static::getFullPath($name);

        // Грешка. Файла липсва
        if (!$file) {
            error_log("EF Error: Mising file: {$name}");

            if (static::isDebug()) {
                error_log("EF Error: Mising file: {$name}");
                header('Content-Type: text/html; charset=UTF-8');
                header("Content-Encoding: none");
                echo "<script type=\"text/javascript\">\n";
                echo "alert('Error: " . str_replace("\n", "\\n", addslashes("Липсващ файл: *{$name}")) . "');\n";
                echo "</script>\n";
                exit();
            } else {
                header('HTTP/1.1 404 Not Found');
                exit();
            }
        }

        // Файла съществува и трябва да бъде сервиран
        // Определяне на Content-Type на файла
        $fileExt = strtolower(substr(strrchr($file, "."), 1));
        $mimeTypes = array(
            'css' => 'text/css',
            'htm' => 'text/html',
            'svg' => 'image/svg+xml',
            'html' => 'text/html',
            'xml' => 'text/xml',
            'js' => 'application/javascript',
            'swf' => 'application/x-shockwave-flash',
            'jar' => 'application/x-java-applet',
            'java' => 'application/x-java-applet',

            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'ico' => 'image/vnd.microsoft.icon'
        );

        $ctype = $mimeTypes[$fileExt];

        if (!$ctype) {
            if (static::isDebug()) {
                header('Content-Type: text/html; charset=UTF-8');
                header("Content-Encoding: none");
                echo "<script type=\"text/javascript\">\n";
                echo "alert('Error: " . str_replace("\n", "\\n", addslashes("Unsuported file extention: $file ")) . "');\n";
                echo "</script>\n";
                exit();
            } else {
                header('HTTP/1.1 404 Not Found');
                exit();
            }
        }

        header("Content-Type: $ctype");

        // Хедъри за управлението на кеша в браузъра
        header("Expires: " . gmdate("D, d M Y H:i:s", time() + 3153600) . " GMT");
        header("Cache-Control: public, max-age=3153600");

        if (substr($ctype, 0, 5) == 'text/' || $ctype == 'application/javascript') {
            $gzip = in_array('gzip', array_map('trim', explode(',', @$_SERVER['HTTP_ACCEPT_ENCODING'])));

            if ($gzip) {
                header("Content-Encoding: gzip");

                // Търсим предварително компресиран файл
                if (file_exists($file . '.gz')) {
                    $file .= '.gz';
                    header("Content-Length: " . filesize($file));
                } else {
                    // Компресираме в движение
                    // ob_start("ob_gzhandler");
                }
            }
        } else {
            header("Content-Length: " . filesize($file));
        }
 
        // Изпращаме съдържанието към браузъра
        readfile($file);
        
        flush();

        // Копираме файла за директно сервиране от Apache
        // @todo: Да се минимализират .js и .css
        if(!isDebug()) {
            $sbfPath = EF_SBF_PATH . '/' . $name;

            $sbfDir = dirname($sbfPath);

            mkdir($sbfDir, 0777, TRUE);

            @copy($file, $sbfPath);
        }

        exit();
    }


    /**
     * Завършване на изпълнението на програмата
     *
     * @param bool $sendOutput
     */
    public static function shutdown($sendOutput = TRUE)
    {
        
        if (!static::isDebug() && $sendOutput) {
            self::flushAndClose();
        }


        // Освобождава манипулатора на сесията. Ако трябва да се правят
        // записи в сесията, то те трябва да се направят преди shutdown()
        if (session_id()) session_write_close();

 
        // Генерираме събитието 'suthdown' във всички сингълтон обекти
        core_Cls::shutdown();

        // Излизаме със зададения статус
        exit($status);
    }

    
    /**
     * Изпраща всичко буферирано към браузъра и затваря връзката
     */
    static function flushAndClose()
    {
        $content = ob_get_contents();         // Get the content of the output buffer
         
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $len = strlen($content);             // Get the length
        header("Content-Length: $len");     // Close connection after $size characters
        header('Cache-Control: no-cache, must-revalidate'); // HTTP 1.1.
        header('Pragma: no-cache'); // HTTP 1.0.
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Proxies.
        header('Connection: close');
        
        echo $content;                       // Output content
            
        // Изпращаме съдържанието на изходния буфер
        ob_end_flush();
        flush();
    }


    /**
     * Спира обработката и извежда съобщение за грешка или го записв в errorLog
     */
    public static function halt($err)
    {
        if (static::isDebug()) {
            echo "<li>" . $err . " | Halt on " . date('d.m.Y H:i:s');
        } else {
            echo "On " . date('d.m.Y H:i:s') . ' a System Error has occurred';
        }

        error_log("HALT: " . $err);

        exit(-1);
    }


    /**
     * Редиректва браузъра към посоченото URL
     * Добавя сесийния идентификатор, ако е необходимо
     */
    public static function redirect($url, $absolute = FALSE, $msg = NULL, $type = 'notice')
    { 
    	expect(ob_get_length() <= 3, ob_get_length(), ob_get_contents());

    	$url = static::toUrl($url, $absolute ? 'absolute' : 'relative');
    	
    	if(Request::get('ajax_mode')){
    		
    		// Ако сме в Ajax_mode редиректа става чрез Javascript-а
    		$resObj = new stdClass();
    		$resObj->func = "redirect";
    		$resObj->arg = array('url' => $url);
    			
    		echo json_encode(array($resObj));
    	} else {
    		
    		if (isset($msg)) {
    			core_Statuses::newStatus($msg, $type);
    		}
    		
    		header("Status: 302");
    		
    		// Забранява кеширането. Дали е необходимо тук?
    		header('Cache-Control: no-cache, must-revalidate'); // HTTP 1.1.
    		header('Pragma: no-cache'); // HTTP 1.0.
    		header('Expires: 0'); // Proxies.
    		
    		header("Location: $url");
    	}

        static::shutdown(FALSE);
    }


    /**
     * Връща текущото GET URL
     */
    public static function getCurrentUrl()
    {
        // Ако заявката е по AJAX
        if (Request::get('ajax_mode')) {
            
            // Ако е зададено URL на страницата, от която се вика AJAX заявката
            $parentUrlStr = Request::get('parentUrl');
            if ($parentUrlStr) {
                
                // Парсираме URL-то
                $parentUrlArr = static::parseLocalUrl($parentUrlStr);
            }
        }
        
        if ($parentUrlArr) {
            $params = $parentUrlArr;
        } else {
            // Всички параметри в рекуеста
            $params = Request::getParams();
        }
        
        // Ако има параметри
        if ($params) {
            
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
    public static function parseLocalUrl($str, $unprotect = TRUE)
    {   
        $get = array();
        if ($str) {
            $arr = explode('/', $str);

            $get['App'] = $arr[0];
            $get['Ctr'] = $arr[1];
            $get['Act'] = $arr[2];
            $begin = 3;

            $cnt = count($arr);

            if (count($arr) % 2 == (($begin-1) % 2)) {
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
                    error('Повече от едномерен масив в URL-то не се поддържа', $key);
                }
            }
            
            // Премахваме защитата на id-то, ако има такава
            if($get['id'] && $unprotect) {
                expect($get['id'] = Request::unprotectId($get['id'], $get['Ctr']), $get, core_Request::get('ret_url'));
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
     * @param mixed $defaultUrl използва този URL ако не може да установи RetUrl
     * @param string $msg съобщение - нотификация
     * @param string $type тип на нотификацията
     */
    public static function followRetUrl($defaultUrl = NULL, $msg = NULL, $type = 'notice')
    {
        if (!$retUrl = static::getRetUrl()) {
            $retUrl = $defaultUrl;
        }
        
        if (!$retUrl) {
            $retUrl = array(
                EF_DEFAULT_CTR_NAME,
                EF_DEFAULT_ACT_NAME
            );
        }
        
        static::redirect($retUrl, FALSE, $msg, $type);
    }


    /**
     * @todo Чака за документация ...
     */
    public static function toLocalUrl($arr)
    {
        if (is_array($arr)) {
            if (!$arr['Act']) $arr['Act'] = 'default';

            $url .= $arr['App'];
            $url .= "/" . $arr['Ctr'];
            $url .= "/" . $arr['Act'];

            if (isset($arr['id'])) {
                $url .= "/" . $arr['id'];
            }
            unset($arr['App'], $arr['Ctr'], $arr['Act'], $arr['id']);

            foreach ($arr as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        $url .= ($url ? '/' : '') . "{$key},{$k}/" . urlencode($v);
                    }
                } else {
                    $url .= ($url ? '/' : '') . "{$key}/" . urlencode($value);
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
     * @param array $params
     * @param string $type Може да бъде relative|absolute|internal
     * @param boolean $protect
     * @param array $preParamsArr - Масив с имената на параметрите, които да се добавят в pre, вместо като GET
     * 
     * @return string
     */
    public static function toUrl($params = array(), $type = NULL, $protect = TRUE, $preParamsArr = array())
    {
        if(!$params) $params = array();
        
        if($type === NULL) {
            if(Mode::is('text', 'xhtml') || Mode::is('text', 'plain') || Mode::is('pdf')) {
                $type = 'absolute';
            } else {
                $type = 'relative';
            }
        }

        // TRUE == 'absolute', FALSE == 'relative'
        if($type === TRUE) {
            $type = 'absolute';
        } elseif($type === FALSE) {
            $type = 'relative';
        }

        // Ако параметъра е стринг - нищо не правим
        if (is_string($params)) return $params;

        // Очакваме, че параметъра е масив
        expect(is_array($params), $params, 'toUrl($params) Очаква  масив');
        
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

        if(is_string($params['Ctr']) && !$params['Ctr']) {
            $params['Ctr'] = EF_DEFAULT_CTR_NAME;
        }

        if(is_string($params['Act']) && !$params['Act']) {
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
        if(TRUE === $params['ret_url']) {
            $params['ret_url'] = static::getCurrentUrl();
        }

        // Ако ret_url е масив - кодирамего към локално URL
        if(is_array($params['ret_url'])) {
            $params['ret_url'] = static::toUrl($params['ret_url'], 'local');
        }
        
        if($protect) {
            $Request->doProtect($params);
        }

        // Ако е необходимо локално URL, то то се генерира с помощна функция
        if($type == 'local') {

            return static::toLocalUrl($params);
        }  

        // Зпочваме подготовката на URL-то

        if (EF_APP_NAME_FIXED !== TRUE) {
            $pre = '/' . ($params['App'] ? $params['App'] : EF_APP_NAME);
        }

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
        if ($preParamsArr) {
            
            // [^A-Za-z0-9_\-\.]
            $regExp = '/[^\w\-\.]/';
            
            // Обхождаме всички параметри
            foreach ($preParamsArr as $param) {
                
                // Ако има стойност
                if (isset($params[$param])) {
                    
                    // Ако не отоговаря на регулярния израз, да се остави за GET
                    if (preg_match($regExp, $param) || preg_match($regExp, $params[$param])) {
                        
                        continue;   
                    }
                    
                    // Добавяме към стринга
                    $pre .= urlencode($param) . '/' . urlencode($params[$param]) . '/';
                    
                    // Премахваме от масива
                    unset($params[$param]);
                }
            }
        }
        
        if($urlHash = $params['#']) {
            unset($params['#']);
        }
        
        if(count($params)) {
            $urlQuery =  http_build_query($params);
        }

        if($urlQuery) {
            $urlQuery = '?' . $urlQuery;
        }

        if ($urlHash) {
            $urlQuery .= '#' . $urlHash;
        }

        switch($type) {
            case 'local' :
                $url = ltrim($pre . $urlQuery, '/');
                break;

            case 'relative' :
                $url = rtrim(static::getBoot(FALSE), '/') . $pre . $urlQuery;
                break;

            case 'absolute' :
                $url = rtrim(static::getBoot(TRUE), '/') . $pre . $urlQuery;
                break;
        }

        
        return $url;
    }


    /**
     * Връща целия текущ URL адрес
     */
    public static function getSelfURL()
    {
        $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
        $slashPos = strpos($_SERVER["SERVER_PROTOCOL"], '/');
        $protocol = substr(strtolower($_SERVER["SERVER_PROTOCOL"]), 0, $slashPos) . $s;

        return $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }


    /**
     * Връща относително или пълно URL до папката на index.php
     *
     * @param boolean $absolute;
     * @return string
     */
    public static function getBoot($absolute = FALSE)
    {
        static $relativeWebRoot = NULL;

        if ($absolute) {
            $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
            $slashPos = strpos($_SERVER["SERVER_PROTOCOL"], '/');
            $protocol = substr(strtolower($_SERVER["SERVER_PROTOCOL"]), 0, $slashPos) . $s;
            
            $dirName = dirname($_SERVER['SCRIPT_NAME']);
            
            $dirName = str_replace(DIRECTORY_SEPARATOR, '/', $dirName);
            
            defIfNot('BGERP_ABSOLUTE_HTTP_HOST', $_SERVER['HTTP_HOST']);
            
            $boot = $protocol . "://" . BGERP_ABSOLUTE_HTTP_HOST . $dirName;
        } else {

            $scriptName = $_SERVER['SCRIPT_NAME'];

            if (!isset($relativeWebRoot)) {
                $relativeWebRoot = str_replace('/index.php', '', $scriptName);
                if ($relativeWebRoot == '/') $relativeWebRoot = '';
            }

            $boot = $relativeWebRoot;
        }
        
        $boot = rtrim($boot, '/');

        return $boot;
    }


    /**
     * Връща URL на Browser Resource File, по подразбиране, оградено с кавички
     */
    public static function sbf($rPath, $qt = '"', $absolute = FALSE)
    {
        // Взема пътя до файла, ако той не е в служебна под-директория на sbf
        if($rPath{0} != '_') {
            $f = static::getFullPath($rPath);
        }
        
        // Ако няма файл или не е директория
        if (!$f || !is_dir($f)) {
            
            // Ако има разширение файла
            if (($dotPos = strrpos($rPath, '.')) !== FALSE) {
                
                // Разширението на файла
                $ext = mb_substr($rPath, $dotPos);
                
                // Пътя до файла, без разширенито
                $filePath = mb_substr($rPath, 0, $dotPos);
                
                // Ако няма файл
                if (!$f) {
                    
                    // Ако разшиернието е .csss
                    if (strtolower($ext) == '.css') {
                        
                        // Новото разширение
                        $nExt = '.scss';
                        
                        // Новия файл
                        $nPath = $filePath . $nExt;
                        
                        // Пътя до файла
                        $f = static::getFullPath($nPath);
                        
                        // Ако файла съществува
                        if ($f) {
                            
                            // Сетваме флаговете
                            $fileExist =  TRUE;
                            $convertCss = TRUE;
                            $checkDir = TRUE;
                        }
                    }
                } else {
                    
                    // Ако съществува
                    $fileExist = TRUE;
                }
            }
        }
        
        // Ако файла съществува
        if ($fileExist) {
            
            // Ако е зададено да се провери директорията за промени и да се вземе времето на последната промяна на файла
            if ($checkDir) {
                
                // Времето на последната промяна в директорията
                $time = core_Os::getLastModified(dirname($f));    
            } else {
                
                // Датата на последна модификация
                $time = filemtime($f);
            }
            
            // Новия файл
            $newFile = $filePath . "_" . date("mdHis", $time) . $ext;
            
            // Новия път до SBF на файла
            $newPath = EF_SBF_PATH . "/" . $newFile;
                    

            // Ако файла не съществува в SBF
            if(!file_exists($newPath)) {
                
                // Ако директорията не съществува
                if(!is_dir($dir = dirname($newPath))) {
                    
                    // Създаваме директория
                    if(!@mkdir($dir, 0777, TRUE)) {
                        
                        // Ако възникне грешка при създаването, записваме в лога
                        core_Logs::add(get_called_class(), NULL, "Не може да се създаде: {$dir}");
                    }
                }
                
                // Ако трябва да се конвертира css файла
                if ($convertCss) {
                    
                    // TODO след промяна на import' натите файлове, без оригиналния, все още ще работи със стария код
                    // Конвертираме файла и вземаме CSS' а
                    $css = core_Converter::convertSass($f, 'scss');  
                    
                    // Записваме в лога, след конвертиране
                    core_Logs::add(get_called_class(), NULL, "Конвертиране на 'scss' към 'css' - {$nPath}");
                    
                    // Ако няма програма за конвертиране
                    if ($css !== FALSE) {
                        
                        // Ако няма резултат записваме в лога
                        if (!trim($css)) {
                            
                            // Записваме в лога
                            core_Logs::add(get_called_class(), NULL, "Генерирания CSS от '{$nPath}' е празен стринг.");
                        } 
    
                        // Записваме файла
                        if (core_Sbf::saveFile($css, $newFile) !== FALSE) {
                            
                            // Задаваме пътя
                            $rPath = $newFile;  
                        } else {
                            
                             // Записваме в лога
                            core_Logs::add(get_called_class(), NULL, "Генерирания CSS не може да се запише в '$newPath'.");
                        }    
                    }
                } else {
                    
                    // Ако не трябва да се конвертира, записваме новия файл
                    $content = file_get_contents($f);

                    if(core_Sbf::saveFile($content, $newFile)) {
                        
                        // Записваме в лога, всеки път след като създадам файл в sbf
                        core_Logs::add(get_called_class(), NULL, "Генериране на файл в 'sbf' за '{$rPath}'", 5);
                        
                        // Пътя до новия файл
                        $rPath = $newFile;
                    } else {
                        
                         // Записваме в лога
                        core_Logs::add(get_called_class(), NULL, "Файла не може да се запише в '$newPath'.");
                    }   
                }
            } else {
                
                // Пътя до файла
                $rPath = $newFile;
            }
        }
        
        $rPath = ltrim($rPath, '/');
        
        return $qt . static::getBoot($absolute) . '/' . EF_SBF . '/' . EF_APP_NAME . '/' . $rPath . $qt;
    }


    /**
     * Тази функция определя пълния път до файла.
     * Като аргумент получава последната част от името на файла
     * Файла се търси в EF_APP_PATH, EF_EF_PATH, EF_VENDORS_PATH
     * Ако не бъде открит, се връща FALSE
     */
    static public function getFullPath($shortPath)
    {
        // Не може да има връщане назад, в името на файла
        expect(!preg_match('/\.\.(\\\|\/)/', $shortPath));

	if (is_readable($shortPath)) return $shortPath;

        if(defined('EF_PRIVATE_PATH')) {
            $pathsArr = array(EF_PRIVATE_PATH, EF_APP_PATH, EF_EF_PATH, EF_VENDORS_PATH);
        } else {
            $pathsArr = array(EF_APP_PATH, EF_EF_PATH, EF_VENDORS_PATH);
        }

        foreach($pathsArr as $base) {
            $fullPath = $base . '/' . $shortPath;

            if(is_readable($fullPath)) return $fullPath;
        }

        return FALSE;
    }


    /**
     * Връща съдържанието на файла, като стринг
     * Пътя до файла може да е указан само от пакета нататък
     */
    static public function getFileContent($shortPath)
    {
        expect($fullPath = static::getFullPath($shortPath));

        return file_get_contents($fullPath);
    }
    
    
    /**
     * Watch point
     * 
     * Записва съдържанието на аргументите си в HTTP хедъри, които (хедъри) се визуализират в
     * конзолата на браузъра, с помощта на браузърни разширения.
     *  
     * Може да се използва с Firefox с инсталиран FirePHP или в Google Chrome с инсталиран
     * ChromeLog или FirePHP4Chrome
     * 
     * За да работи коректно, е необходимо да се добави следния ред във файла 
     * {{EF_CONF_PATH}}/{{EF_APP_NAME}}.boot.php:
     * 
     * chromephp_ChromePHP::setup(); // в случая GoogleChrome + ChromeLog
     * 
     *     или
     * 
     * firephp_FirePHP::setup(); // В случаите Firefox + FirePHP или Chrome + FirePHP4Chrome
     * 
     * Отделно от това, в браузъра трябва да се инсталира съответното бразърно разширение. 
     * Възможностите за това са:
     * 
     * 1. Браузър Google Chrome - две възможности:
     * 
     *    1.1. ChromeLog (@link https://chrome.google.com/webstore/detail/php-console/nfhmhhlpfleoednkpnnnkolmclajemef)
     *    
     *         В този случай в conf/{app}.boot.php трябва да се постави реда `chromephp_ChromePHP::setup();`
     *         
     *    1.2. FirePHP4Chrome (@link https://chrome.google.com/webstore/detail/firephp4chrome/gpgbmonepdpnacijbbdijfbecmgoojma)
     *    
     *         В този случай в conf/{app}.boot.php трябва да се постави реда `firephp_FirePHP::setup();`
     *         
     * 2. Браузър Mozilla Firefox - в този случай възможността е само една - инсталиране на
     *    разширенията FireBug (v1.9+) (@link https://addons.mozilla.org/en-US/firefox/addon/firebug/) и
     *    след това инсталиране на FirePHP (@link https://addons.mozilla.org/en-US/firefox/addon/firephp/)
     *    
     */
    public static function debug()
    {
        $args = func_get_args();
        $bt   = debug_backtrace();
        
        while ($where = array_shift($bt)) {
            if (empty($where['line'])) {
                continue;
            }
            if ($where['function'] == __FUNCTION__ && $where['class'] == __CLASS__) {
                break;
            }
            if ($where['function'] == 'wp' && empty($where['class'])) {
                break;
            }
        }
        
        $file = $where['file'];
        $line = $where['line'];
        
        if (!empty($file)) {
            $file = str_replace(EF_ROOT_PATH, '', $file);
            $file = ltrim($file, '/');
        }
        
        $where = "{$file}:{$line}";

        $args = array($args, $where);
        
        return call_user_func_array(self::$debugHandler, $args);
    }


    public static function bp()
    {
        core_App::_bp(core_Html::arrayToHtml(func_get_args()), debug_backtrace());
    }


    public static function _bp($html, $stack)
    {
        $breakFile = $breakLine = NULL;
	
        // Ако сме в работен, а не тестов режим, не показваме прекъсването
        if (!static::isDebug()) {
            error_log("Breakpoint on line $breakLine in $breakFile");
            return;
        }

        $errHtml = static::getErrorHtml($html, $stack, $breakFile, $breakLine);
        
        $errHtml .= core_Debug::getLog();
        
        if (!file_exists(EF_TEMP_PATH) && !is_dir(EF_TEMP_PATH)) {
    		mkdir(EF_TEMP_PATH, 0777, TRUE);    
		}
        
        // Сигнал за външния свят, че нещо не е наред
        header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request', TRUE, 500);

        header('Content-Type: text/html; charset=UTF-8');
        
        // Поставяме обвивка - html документ
        $page = ht::wrapMixedToHtml($errHtml, TRUE);
        
        // Записваме за всеки случай и като файл
        file_put_contents(EF_TEMP_PATH . '/err.log.html', $page . "\n\n");

        echo $page;
        
        exit(-1);
    }

    
    /**
     * Връща html-a на грешката
     */
	public static function getErrorHtml($html, $stack, $breakFile = NULL, $breakLine = NULL)
	{
		$stack = static::prepareStack($stack, $breakFile, $breakLine);

        $errHtml .= "<h2>Прекъсване на линия <span style=\"color:red\">$breakLine</span> в " .
        "<span style=\"color:red\">$breakFile</span></h2>";

        $errHtml .= self::getCodeAround($breakFile, $breakLine);

        $errHtml .= $html;

        $errHtml .= "<h2>Стек</h2>";

        $errHtml .= core_Exception_Expect::getTraceAsHtml($stack);
		
        $errHtml .= static::renderStack($stack);
        
        return $errHtml;
	}


    /**
     * Връща кода от php файла, около посочената линия
     * Прави базово форматиране
     *
     * @param string $file Името на файла, съдържащ PHP код
     * @param int       $line Линията, около която търсим 
     */
    public static function getCodeAround($file, $line, $range = 4)
    {
        $source = file_get_contents($file);

        $lines = explode("\n", $source);

        $from = max($line - $range-1, 0);
        $to   = min($line + $range, count($lines));
        $code = "<pre>";
        $padding = strlen($to);
        for($i = $from; $i < $to; $i++) {
            $l = str_pad($i+1, $padding, " ", STR_PAD_LEFT);
            $style = '';
            if($i+1 == $line) {
                $style = " style='background-color:#ff9;'";
            }
            $l = "<span{$style}><span style='border-right:solid 1px #999;padding-right:5px;'>$l</span> ". str_replace('<', '&lt;', rtrim($lines[$i])) . "</span>\n";
            $code .= $l;
        }
        $code .= "</pre>";
        
        return $code;
    }

	
    /**
     * Показва грешка и спира изпълнението.
     */
    public static function error($errorInfo = NULL, $debug = NULL, $errorTitle = 'ГРЕШКА В ПРИЛОЖЕНИЕТО')
    {
        $text = static::isDebug() ? $errorInfo : $errorTitle;
        
        if(Request::get('ajax_mode')) {
        	core_Statuses::newStatus($text, 'error');
        }
		
        throw new core_exception_Expect($text, $debug, $errorTitle);
    }


    /**
     * Задава стойността(ите) от втория параметър на първия,
     * ако те не са установени
     * @todo: използва ли се тази функция за масиви?
     */
    public static function setIfNot(&$p1, $p2)
    {
        $args = func_get_args();

        for ($i = 1; $i < func_num_args(); $i++) {
            $new = $args[$i];

            if (is_array($p1)) {
                if (!count($new))
                continue;

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
     * Дефинира константа, ако преди това не е била дефинирана
     * Ако вторият и аргумент започва с '[#', то изпълнението се спира
     * с изискване за дефиниция на константата
     */
    public static function defIfNot($name, $value = NULL)
    {
        if(!defined($name)) {
            if(substr($name, 0, 2) == '[#') {
                static::halt("Constant '{$name}' is not defined. Please edit: " . EF_CONF_PATH . '/' . EF_APP_NAME . '.cfg.php');
            } else {
                define($name, $value);
            }
        }
    }


    /**
     * @deprecated
     */
    public static function defineIfNot($name, $value)
    {
        return static::defIfNot($name, $value);
    }


    private static function prepareStack($stack, &$breakFile, &$breakLine)
    {
        // Вътрешни функции, чрез които може да се генерира прекъсване
        $intFunc = array(
            'bp:debug',
            'bp:',
            'trigger:core_error',
            'error:',
            'expect:'
        );

        $breakpointPos = NULL;

        foreach ($stack as $i => $f) {
            if (in_array(strtolower($f['function'] . ':' . $f['class']), $intFunc)) {
                $breakpointPos = $i;
            }
        }

        if (isset($breakpointPos)) {
            $breakLine = $stack[$breakpointPos]['line'];
            $breakFile = $stack[$breakpointPos]['file'];
            $stack = array_slice($stack, $breakpointPos+1);
        }

        return $stack;
    }

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
     * Зарежда от конфигурацията (ако има) хендлър на core_App::debug()/wp()
     */
    private static function setDebugHandler()
    {
        $coreConfig = core_Packs::getConfig('core');
        
        if ($coreConfig->debugHandler) {
            self::$debugHandler = $coreConfig->debugHandler;
        }
    }

}

/****************************************************************************************
*                                                                                       *
*      Глобални функции-псевдоними на често използвани статични методи на core_App      *
*                                                                                       *
****************************************************************************************/

/**
 * Тази функция определя пълния път до файла.
 * Като аргумент получава последната част от името на файла
 * Файла се търси в EF_APP_PATH, EF_EF_PATH, EF_VENDORS_PATH
 * Ако не бъде открит, се връща FALSE
 */
function getFullPath($shortPath)
{
    return core_App::getFullPath($shortPath);
}


/**
 * Връща съдържанието на файла, като стринг
 * Пътя до файла може да е указан само от пакета нататък
 */
function getFileContent($shortPath)
{
    return core_App::getFileContent($shortPath);
}


/**
 * Връща URL на Browser Resource File, по подразбиране, оградено с кавички
 */
function sbf($rPath, $qt = '"', $absolute = FALSE)
{
    return core_App::sbf($rPath, $qt, $absolute);
}


/**
 * Създава URL от параметрите
 *
 * @param array $params
 * @param string $type Може да бъде relative|absolute|internal
 * @param boolean $protect
 * @param array $preParamsArr - Масив с имената на параметрите, които да се добавят в pre вместо, като GET
 * 
 * @return string
 */
function toUrl($params = array(), $type = 'relative', $protect = TRUE, $preParamsArr = array())
{
    return core_App::toUrl($params, $type, $protect, $preParamsArr);
}


/**
 * Също като toUrl, но връща ескейпнат за html атрибут стринг
 */
function toUrlEsc($params = array(), $type = NULL, $protect = TRUE, $preParamsArr = array())
{
    return ht::escapeAttr(toUrl($params, $type, $protect, $preParamsArr));
}


/**
 * @todo Чака за документация...
 */
function toLocalUrl($arr)
{
    return core_App::toLocalUrl($arr);
}


/**
 * Връща относително или пълно URL до папката на index.php
 *
 * Псевдоним на @link core_App::getBoot()
 */
function getBoot($absolute = FALSE)
{
    return core_App::getBoot($absolute);
}


/**
 * @todo Чака за документация...
 */
function getCurrentUrl()
{
    return core_App::getCurrentUrl();
}


/**
 *  Връща масив, който представлява вътрешното представяне на 
 * локалното URL подадено като аргумент
 */
function parseLocalUrl($str, $unprotect = TRUE)
{
    return core_App::parseLocalUrl($str, $unprotect);
}


/**
 * Връща масив, който представлява URL-то където трябва да
 * се използва за връщане след изпълнението на текущата задача
 */
function getRetUrl($retUrl = NULL)
{
    return core_App::getRetUrl($retUrl);
}


/**
 * @todo Чака за документация...
 */
function followRetUrl($url = NULL, $msg = NULL, $type = 'notice')
{
    core_App::followRetUrl($url, $msg, $type);
}


/**
 * Редиректва браузъра към посоченото URL
 * Добавя сесийния идентификатор, ако е необходимо
 *
 *
 */
function redirect($url, $absolute = FALSE, $msg = NULL, $type = 'notice')
{
    return core_App::redirect($url, $absolute, $msg, $type);
}


/**
 * Връща целия текущ URL адрес
 */
function getSelfURL()
{
    return core_App::getSelfURL();
}



/**
 * Функция за завършване на изпълнението на програмата
 *
 * @param bool $sendOutput
 */
function shutdown($sendOutput = TRUE)
{
    core_App::shutdown();
}


/**
 * Дали се намираме в DEBUG режим
 */
function isDebug()
{  
    return core_App::isDebug();
}


/**
 * Спира обработката и извежда съобщение за грешка или го записв в errorLog
 */
function halt($err)
{
    return core_App::halt($err);
}


/**
 * Точка на прекъсване. Има неограничен брой аргументи.
 * Показва съдържанието на аргументите си и текущия стек
 * Сработва само в режим на DEBUG
 */
function bp()
{
    call_user_func_array(array('core_App', 'bp'), func_get_args());
}


/**
 * Watch Point - съкратено извикване на core_App::debug()
 * 
 * @see core_App::debug
 */
function wp()
{
    call_user_func_array(array('core_App', 'debug'), func_get_args());
}


/**
 * Показва грешка и спира изпълнението. Използва core_Message
 */
function error($errorInfo = NULL, $debug = NULL, $errorTitle = 'ГРЕШКА В ПРИЛОЖЕНИЕТО')
{
    return core_App::error($errorInfo, $debug, $errorTitle);
}


/**
 * Задава стойността(ите) от втория параметър на първия,
 * ако те не са установени
 * @todo: използва ли се тази функция за масиви?
 */
function setIfNot(&$p1, $p2)
{
    $args = func_get_args();
    $args[0] = &$p1;

    return call_user_func_array(array('core_App', 'setIfNot'), $args);
}


/**
 * Дефинира константа, ако преди това не е била дефинирана
 * Ако вторият и аргумент започва с '[#', то изпълнението се спира
 * с изискване за дефиниция на константата
 */
function defIfNot($name, $value = NULL)
{
    return core_App::defIfNot($name, $value);
}


/**
 * Аналогична фунция на urldecode()
 * Прави опити за конвертиране в UTF-8. Ако не успее връща оригиналното URL.
 * 
 * @param URL $url
 * 
 * @return URL
 */ 
function decodeUrl($url)
{
    return core_Url::decodeUrl($url);
}


/**
 * @todo Чака за документация...
 * @deprecated
 */
function defineIfNot($name, $value)
{
    return core_App::defineIfNot($name, $value);
}
