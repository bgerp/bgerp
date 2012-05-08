<?php

class core_App
{

    public static function run()
    {
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
        try
        {
            $content = core_Request::forward();

            // Зарежда опаковката
            $Wrapper = core_Cls::get('page_Wrapper');

            $Wrapper->render($content);

            static::shutdown();    // Край на работата на скрипта
        }
        catch (core_Exception_Expect $e)
        {
            echo $e->getAsHtml();
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
                        $q['id'] = $prm;
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
        if(defined('EF_DEBUG')) return EF_DEBUG;

        static $noDebugIp = FALSE;

        if(!$noDebugIp) {

            $hosts = core_Array::make(EF_DEBUG_HOSTS);

            if(in_array($_SERVER['HTTP_HOST'], $hosts)){


                /**
                 * Включен ли е дебъга? Той ще бъде включен и когато текущия потребител има роля 'tester'
                 */
                DEFINE('EF_DEBUG', TRUE);
                ini_set("display_errors", static::isDebug());
                ini_set("display_startup_errors", static::isDebug());
            } else {
                $noDebugIp = TRUE;
            }
        }

        return defined('EF_DEBUG') ? EF_DEBUG : FALSE;
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
        header("Cache-Control: max-age=3153600");

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
                    ob_start("ob_gzhandler");
                }
            }
        } else {
            header("Content-Length: " . filesize($file));
        }

        // Изпращаме съдържанието към браузъра
        readfile($file);
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
            // Изпращаме хедърите и казваме на браузъра да затвори връзката
            ob_end_flush();
            $size = ob_get_length();
            header("Content-Length: {$size}");
            header('Connection: close');

            // Изпращаме съдържанието на изходния буфер
            ob_end_flush();
            ob_flush();
            flush();
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
     * Спира обработката и извежда съобщение за грешка или го записв в errorLog
     */
    public static function halt($err)
    {
        if (static::isDebug()) {
            echo "<li>" . $err . " | Halt on " . date('d-m-Y H:i.s');
        } else {
            echo "On " . date('d-m-Y H:i.s') . ' a System Error has occurred';
        }

        error_log("HALT: " . $err);

        exit(-1);
    }


    /**
     * Редиректва браузъра към посоченото URL
     * Добавя сесийния идентификатор, ако е необходимо
     */
    public static function redirect($url, $absolute = FALSE, $msg = NULL, $type = 'info')
    {
        expect(ob_get_length() == 0, ob_get_length());

        $url = static::toUrl($url, $absolute ? 'absolute' : 'relative');

        if (class_exists('core_Session', FALSE)) {
            $url = core_Session::addSidToUrl($url);
        }

        if (isset($msg)) {
            $Nid = rand(1000000, 9999999);
            core_Mode::setPermanent('Notification_' . $Nid, $msg);
            core_Mode::setPermanent('NotificationType_' . $Nid, $type);

            $url = core_Url::addParams(toUrl($url), array('Nid' => $Nid));
        }

        header("Status: 302");
        header("Location: $url");

        static::shutdown(FALSE);
    }


    /**
     * @todo Чака за документация...
     */
    public static function getCurrentUrl()
    {
        global $_GET;

        if (count($_GET)) {
            $get = $_GET;
            unset($get['virtual_url'], $get['ajax_mode']);

            return $get;
        }
    }


    /**
     * Връща масив, който представлява URL-то където трябва да
     * се използва за връщане след изпълнението на текущата задача
     */
    public static function getRetUrl($retUrl = NULL)
    {
        if (!$retUrl) {
            $retUrl = core_Request::get('ret_url');
        }
        if ($retUrl) {
            $arr = explode('/', $retUrl);

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
                $value = urldecode($value);
                $key = explode(',', $key);

                if (count($key) == 1) {
                    $get[$key[0]] = $value;
                } elseif (count($key) == 2) {
                    $get[$key[0]][$key[1]] = $value;
                } else {
                    error('Повече от едномерен масив в URL-то не се поддържа', $key);
                }
            }

            return $get;
        }
    }


    /**
     * @todo Чака за документация...
     */
    public static function followRetUrl()
    {
        if (!$retUrl = static::getRetUrl()) {
            $retUrl = array(
                EF_DEFAULT_CTR_NAME,
                EF_DEFAULT_ACT_NAME
            );
        }

        static::redirect($retUrl);
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
     * $param string $type Може да бъде relative|absolute|internal
     */
    public static function toUrl($params = array(), $type = 'relative')
    {
        if(!$params) $params = array();

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

        $Request->doProtect($params);

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

        // Ако е необходимо локално URL, то то се генерира с помощна функция
        if($type == 'local') {
            return static::toLocalUrl($params);
        }

        // Зпочваме подготовката на URL-то

        if (EF_APP_NAME_FIXED !== TRUE) {
            $pre = '/' . ($params['App'] ? $params['App'] : EF_APP_NAME);
        }

        // Махаме префикса на пакета по подразбиране
        $appPref = EF_APP_NAME . '_';

        // Очакваме името на контролера да е стринг
        expect(is_string($params['Ctr']), $appPref, $Request, $params);

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

        foreach ($params as $name => $value) {

            if ($name == '#') continue;

            if ($value) {
                if (is_int($name)) {
                    $name = $value;
                    $value = $Request->get($name);
                }

                if (is_array($value)) {
                    foreach ($value as $key => $v) {
                        $v = urlencode($v);
                        $url .= ($url ? '&' : '?') . "{$name}[{$key}]={$v}";
                    }
                } else {
                    $value = urlencode($value);
                    $url .= ($url ? '&' : '?') . "{$name}={$value}";
                }
            }
        }

        switch($type) {
            case 'local' :
                $url1 = ltrim($pre . $url, '/');
                break;

            case 'relative' :
                $url1 = rtrim(static::getBoot(FALSE), '/') . $pre . $url;
                break;

            case 'absolute' :
                $url1 = rtrim(static::getBoot(TRUE), '/') . $pre . $url;
                break;
        }

        if ($params['#']) {
            $url1 .= '#' . $params['#'];
        }

        return $url1;
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

            return $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
        } else {

            $scriptName = $_SERVER['SCRIPT_NAME'];

            if (!isset($relativeWebRoot)) {
                $relativeWebRoot = str_replace('/index.php', '', $scriptName);
                if ($relativeWebRoot == '/') $relativeWebRoot = '';
            }

            return $relativeWebRoot;
        }
    }


    /**
     * Връща URL на Browser Resource File, по подразбиране, оградено с кавички
     */
    public static function sbf($rPath, $qt = '"', $absolute = FALSE)
    {
        $f = static::getFullPath($rPath);

        if($f && !is_dir($f)) {
            if (($dotPos = strrpos($rPath, '.')) !== FALSE) {
                $ext = mb_substr($rPath, $dotPos);
                $time = filemtime($f);
                $newFile = mb_substr($rPath, 0, $dotPos) . "_" . date("mdHis", $time) . $ext;
                $newPath = EF_SBF_PATH . "/" . $newFile;

                if(!file_exists($newPath)) {
                    if(!is_dir($dir = dirname($newPath))) {
                        if(!mkdir($dir, 0777, TRUE)) {
                            core_Debug::log("Не може да се създаде: {$dir}");
                        }
                    }

                    if(copy($f, $newPath)) {
                        $rPath = $newFile;
                    }
                } else {
                    $rPath = $newFile;
                }
            }
        }

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
        expect(strpos($shortPath, '../') === FALSE);

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


    public static function bp()
    {
        core_App::_bp(core_Html::arrayToHtml(func_get_args()), debug_backtrace());
    }

    public static function _bp($html, $stack)
    {
        $breakFile = $breakLine = NULL;

        $stack = static::prepareStack($stack, $breakFile, $breakLine);

        // Ако сме в работен, а не тестов режим, не показваме прекъсването
        if (!static::isDebug()) {
            error_log("Breakpoint on line $breakLine in $breakFile");
            return;
        }

        header('Content-Type: text/html; charset=UTF-8');

        echo "<head><meta http-equiv=\"Content-Type\" content=\"text/html;" .
        "charset=UTF-8\" /><meta name=\"robots\" content=\"noindex,nofollow\" /></head>" .
        "<h1>Прекъсване на линия <font color=red>$breakLine</font> в " .
        "<font color=red>$breakFile</font></h1>";

        echo $html;

        echo "<h2>Стек</h2>";

        echo core_Exception_Expect::getTraceAsHtml($stack);

        echo static::renderStack($stack);

        echo core_Debug::getLog();

        exit(-1);
    }



    /**
     * Показва грешка и спира изпълнението.
     */
    public static function error($errorInfo = NULL, $debug = NULL, $errorTitle = 'ГРЕШКА В ПРИЛОЖЕНИЕТО')
    {
//         if (static::isDebug() && isset($debug)) {
//             static::bp($errorTitle, $errorInfo, $debug);
//         }

        $text = static::isDebug() ? $errorInfo : $errorTitle;

        throw new core_exception_Expect($text, $debug, $errorTitle);

//         exit(-1);
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
            $result .= "<hr><br><pre id=\"{$f['file']}:{$f['line']}\">";
            $result .= core_Html::mixedToHtml($f);
            $result .= "</pre>";
        }

        return $result;
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
 * $param string $type Може да бъде relative|absolute|internal
 */
function toUrl($params = array(), $type = 'relative')
{
    return core_App::toUrl($params, $type);
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
function followRetUrl()
{
    core_App::followRetUrl();
}


/**
 * Редиректва браузъра към посоченото URL
 * Добавя сесийния идентификатор, ако е необходимо
 *
 *
 */
function redirect($url, $absolute = FALSE, $msg = NULL, $type = 'info')
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
 * @todo Чака за документация...
 * @deprecated
 */
function defineIfNot($name, $value)
{
    return core_App::defineIfNot($name, $value);
}
