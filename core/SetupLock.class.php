<?php

/**
 * По подразбиране 120 секунди заключване на системата
 */
defIfNot('BGERP_SETUP_LOCK_TIME', 120);


/**
 * Клас 'core_SetupLock' - Заключване на системата по време на сетъпа
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class core_SetupLock
{

    /**
     * Връща пътя към временния файл
     */
    static function getPath()
    {   
        $file = str_replace('/', DIRECTORY_SEPARATOR, EF_TEMP_PATH . '/setupLock.txt');
         
        return $file;
    }


    /**
     * Запис в стъп-лога
     */
    static function block($msg)
    {
        $setupLockFile = self::getPath();
        @file_put_contents($setupLockFile, $msg);
    }


    /**
     * Изчиства файла за сетъп-лог
     */
    static function remove()
    {
        $setupLockFile = self::getPath();
        @unlink(realpath($setupLockFile));
    }


    /**
     * Дали сетъп-лога е активен и не трябва да се изпълняват ивенти?
     */
    static function isBlocked()
    {
        $setupLockFile = self::getPath();
        if(@file_exists($setupLockFile)) {
            clearstatcache($setupLockFile);
            $at = time() - filemtime($setupLockFile);
            if($at >= 0 && $at < 120) {
                
                return TRUE;
            } elseif(abs($at) > 36000) {
                self::remove();
            }
        }
    }


    /**
     * Дали сетъп-лога е активен и не трябва да се изпълняват ивенти?
     */
    static function stopIfBlocked()
    {   
        if(self::isBlocked()) {
            $setupLockFile = self::getPath();
            $msg = @file_get_contents($setupLockFile);
            header('HTTP/1.1 503 Service Temporarily Unavailable');
            header('Status: 503 Service Temporarily Unavailable');
            header('Retry-After: 300'); //300 seconds

            if(strtoupper($_SERVER['REQUEST_METHOD']) == 'GET') {
                $refresh = "<meta http-equiv=\"refresh\" content=\"1\">";
            }
            echo "<html><head>
                    <meta charset=\"UTF-8\">
                    {$refresh}</head>
                    <body><h2>{$msg}</h2>
                    <p>We'll be back in a while...</p>
                    <p>Ще сме на разположение след малко...</p></body>";
            die;
        }
    }
}