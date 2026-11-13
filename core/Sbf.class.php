<?php

/**
* Пътя до директорията за статичните браузърни файлове към приложението
*/
defIfNot('EF_SBF_PATH', EF_INDEX_PATH . '/' . EF_SBF . '/' . EF_APP_NAME);

/**
 * Клас 'core_Sbf'
 *
 *
 * @category  ef
 * @package   core
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class core_Sbf extends core_Mvc
{
    const CACHE_MAX_AGE = 31536000;


    public static function convertUrlToPath($url)
    {
        list($first, $last) = explode('/' . EF_SBF . '/' . EF_APP_NAME . '/', $url);
        $path = EF_SBF_PATH . '/' . $last;
        
        return $path;
    }
    
    
    /**
     * Записва посоченото съдържание на указания път
     * Връща FALSE при грешка или пълния път до новозаписания файл
     */
    public static function saveFile_($content, $path, $isFullPath = false)
    {
        if (!$isFullPath) {
            $path = EF_SBF_PATH . '/' . $path;
        }
        
        // Ако директорията не съществува
        if (!is_dir($dir = dirname($path))) {
            
            // Създаваме директория
            if (!core_Os::forceDir($dir)) {
                
                return false;
            }
        }
        
        // През временен файл, за да не се сервира/кешира наполовина записан файл при паралелни заявки
        $tmpPath = $path . '.' . getmypid() . '_' . mt_rand() . '.tmp';
        if (@file_put_contents($tmpPath, $content) !== false && @rename($tmpPath, $path)) {
            usleep(1000);
            
            return $path;
        }
        @unlink($tmpPath);
        
        return false;
    }
    
    
    /**
     * Връща съответстващия път в sbf на зададен вътрешен път
     */
    public static function getSbfFilePath_($path)
    {
        static $cache;
        
        // debug::log("getSbfFilePath {$path}");
        
        if (!($cache[$path] ?? null)) {
            $time = 0;
            if ($file = getFullPath($path)) {
                $time = filemtime($file);
            }
            $cache[$path] = self::getSbfPathByTime($path, $time);
        }
        
        return $cache[$path];
    }
    
    
    /**
     * Връща съотвестващия път на файла в sbf според времето на последна модификация (като параметър)
     *
     * @param string path
     * @param int    time
     *
     * @return string
     */
    public static function getSbfPathByTime($path, $time)
    {
        $pathArr = pathinfo($path);
        
        $timeSuffix = '';
        if ($time) {
            $timeSuffix = '_' . date('mdHis', $time);
        }
        $extension = isset($pathArr['extension']) ? '.' . $pathArr['extension'] : '';
        
        // Новото име на файла, зависещо от времето на последната му модификация
        $sbfPath = EF_SBF_PATH . '/' . $pathArr['dirname'] . '/' . $pathArr['filename'] . $timeSuffix . $extension;
        
        return $sbfPath;
    }
    
    
    /**
     * Връща URL на Browser Resource File, по подразбиране, оградено с кавички
     *
     * @param string $rPath    Релативен път до статичния файл
     * @param string $qt       Символ за ограждане на резултата
     * @param bool   $absolute Дали резултатното URL да е абсолютно или релативно
     *
     * @return string
     */
    public static function getUrl($rPath, $qt = '"', $absolute = false)
    {
        if (strpos($rPath, 'img/16or32/') !== false) {
            if (log_Browsers::isRetina()) {
                $rPath = str_replace('img/16or32/', 'img/32/', $rPath);
            } else {
                $rPath = str_replace('img/16or32/', 'img/16/', $rPath);
            }
        }
        
        // Ако файла съществува
        if (($sbfPath = self::getSbfFilePath($rPath)) && $rPath[0] != '_') {
            
            // Ако файла не съществува в SBF
            if (!file_exists($sbfPath)) {
                if (getFullPath($rPath)) {
                    $content = getFileContent($rPath);
                    
                    if (core_Sbf::saveFile($content, $sbfPath, true)) {
                        
                        // Записваме в лога, всеки път след като създадам файл в sbf
                        // self::logInfo("Генериране на файл в 'sbf' за '{$rPath}'", NULL, 5);
                        
                        // Пътя до файла
                        $sbfArr = pathinfo($sbfPath);
                        $rArr = pathinfo($rPath);
                        $rPath = $rArr['dirname'] . '/'. $sbfArr['basename'];
                    }
                    
                    // Записваме в лога
                         // self::logWarning("Файла не може да се запише в '{$sbfPath}'.");
                } else {
                    self::logNotice("Липсващ файл: {$rPath}");
                    $ext = str::getFileExt($rPath);
                    if (in_array($ext, array('jpg', 'jpeg', 'png', 'bmp'))) {
                        $rPath = 'img/1x1.gif';
                    } else {
                        $rPath = 'Missing_File/' . $rPath;
                    }
                }
            } else {
                $sbfArr = pathinfo($sbfPath);
                $rArr = pathinfo($rPath);
                $rPath = $rArr['dirname'] . '/'. $sbfArr['basename'];
            }
        }
        
        self::markUsed($sbfPath);
        $rPath = ltrim($rPath, '/');
        
        $res = $qt . core_App::getBoot($absolute) . '/' . EF_SBF . '/' . EF_APP_NAME . '/' . $rPath . $qt;
        
        return $res;
    }
    
    
    /**
     * Връща пътя до изходния файл на sbf копие, като маха времевия суфикс от getSbfPathByTime()
     *
     * @param string $name Път в sbf, напр. img/menu_0829142525.png
     *
     * @return string|null Напр. img/menu.png или null, ако името няма времеви суфикс
     */
    public static function getSourceName($name)
    {
        if (preg_match('~^(.*[^/])_[0-9]{10}(\.[a-z0-9]+)\z~i', (string) $name, $m) ||
            preg_match('~^(.*[^/])_[0-9]{10}_scssphp[0-9]+_[0-9]+_[0-9]+(\.css)\z~i', (string) $name, $m)) {
            return $m[1] . $m[2];
        }

        return null;
    }


    /**
     * Отчита използването и при noatime, без да променя версията на файла.
     */
    public static function markUsed($path)
    {
        if (is_string($path) && is_file($path) && @fileatime($path) < time() - 86400) {
            $mtime = @filemtime($path);
            if ($mtime !== false) {
                @touch($path, $mtime, time());
            }
        }
    }


    /**
     * Възстановява само генерирания от приложението адрес и запазва старите налични версии.
     *
     * @return array Файл с MIME тип, пренасочване или HTTP грешка
     */
    public static function resolveStaticFile($name)
    {
        $missing = array('status' => 404);
        if (!is_string($name) || $name === '' || $name[0] === '/' ||
            preg_match('~[\\\\\x00-\x1f?#%]|(?:^|/)\.\.?(?:/|$)~', $name)) {
            return $missing;
        }

        $mimeTypes = array(
            'css' => 'text/css', 'htm' => 'text/html', 'html' => 'text/html',
            'svg' => 'image/svg+xml', 'xml' => 'text/xml',
            'js' => 'application/javascript', 'map' => 'application/javascript',
            'swf' => 'application/x-shockwave-flash', 'jar' => 'application/x-java-applet',
            'java' => 'application/x-java-applet', 'png' => 'image/png',
            'jpe' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'jpg' => 'image/jpeg',
            'gif' => 'image/gif', 'ico' => 'image/vnd.microsoft.icon',
            'ttf' => 'application/x-font-ttf', 'eot' => 'application/vnd.ms-fontobject',
            'woff' => 'application/octet-stream', 'woff2' => 'application/octet-stream',
        );
        $ext = str::getFileExt($name);
        $mime = $mimeTypes[$ext] ?? null;
        if (!$mime) {
            return $missing;
        }

        $file = EF_SBF_PATH . '/' . $name;
        $sourceName = self::getSourceName($name);
        $isBundle = preg_match('~^(css|js)/[a-f0-9]{32}\.(css|js)\z~', $name, $bundle) && $bundle[1] === $bundle[2];
        $immutable = $sourceName !== null || $isBundle;
        if (!is_file($file)) {
            if ($isBundle) {
                $file = core_Packs::isInstalled('compactor') ? compactor_Plugin::restoreFile($name) : false;
                if (!$file) {
                    return array('status' => 410);
                }
            } else {
                // Точното име на оригинал има предимство пред разпознаването на суфикс.
                $original = getFullPath($name);
                if ($original && is_file($original)) {
                    $sourceName = $name;
                    $immutable = false;
                }
                if (!$sourceName && $ext === 'css' && getFullPath(substr($name, 0, -4) . '.scss')) {
                    $sourceName = $name;
                }
                if (!$sourceName || $sourceName[0] === '_') {
                    return $missing;
                }
                $original = getFullPath($sourceName);
                $scss = $ext === 'css' ? getFullPath(substr($sourceName, 0, -4) . '.scss') : false;
                if ((!$original || !is_file($original)) && (!$scss || !is_file($scss))) {
                    return $missing;
                }

                // getUrl() минава през същите SASS и minify плъгини като нормалното рендиране.
                $url = self::getUrl($sourceName, '');
                $file = self::convertUrlToPath($url);
                if (!is_file($file)) {
                    return $missing;
                }
                $currentName = substr($file, strlen(EF_SBF_PATH) + 1);
                $currentName = preg_replace('~(?:^|/)\./~', '/', $currentName);
                $currentName = ltrim($currentName, '/');
                if ($currentName !== $name) {
                    return array('status' => 302, 'url' => $url);
                }
                $immutable = self::getSourceName($currentName) !== null;
            }
        }

        self::markUsed($file);
        return array('status' => 200, 'file' => $file, 'mime' => $mime, 'immutable' => $immutable);
    }


    /**
     * Сервира наличния или възстановен статичен ресурс.
     */
    public static function serveStaticFile($name)
    {
        $result = self::resolveStaticFile($name);
        $status = $result['status'];
        $content = $status === 200 ? @file_get_contents($result['file']) : false;
        if ($status === 200 && $content === false) {
            $status = 404;
        }
        if ($status !== 200) {
            header('Cache-Control: no-store');
            if ($status === 302) {
                header('Location: ' . $result['url'], true, 302);
            } else {
                header($status === 410 ? 'HTTP/1.1 410 Gone' : 'HTTP/1.1 404 Not Found');
            }
        } else {
            $ctype = $result['mime'];
            header('Content-Type: ' . $ctype . '; charset=UTF-8');
            $maxAge = !empty($result['immutable']) ? self::CACHE_MAX_AGE : 0;
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');
            header('Cache-Control: ' . ($maxAge ? 'public, max-age=' . $maxAge . ', immutable' : 'no-cache'));
            header('Vary: Accept-Encoding');
            $isGzipSupported = in_array('gzip', array_map('trim', explode(',', $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '')), true);
            if ($isGzipSupported && (substr($ctype, 0, 5) === 'text/' || $ctype === 'application/javascript')) {
                $content = gzencode($content);
                header('Content-Encoding: gzip');
            }
            header('Content-Length: ' . strlen($content));
            if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'HEAD') {
                echo $content;
            }
            flush();
        }

        core_App::shutdown(false);
    }

}


/*
 Имаме заявка за sbf($file)

 1. Определяме новото име на филе, в зависимост от датата на последното модифициране
*/
