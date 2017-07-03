<?php

/**
 * По подразбиране 120 секунди заключване на системата
 */
defIfNot('BGERP_SYSTEM_LOCK_TIME', 120);


/**
 * Клас 'core_SystemLock' - Заключване на системата по време на сетъпа
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class core_SystemLock
{

    static $isLocked = FALSE;

    /**
     * Връща пътя към временния файл
     */
    static function getPath()
    {   
        $file = str_replace('/', DIRECTORY_SEPARATOR, EF_TEMP_PATH . '/systemLock.txt');
         
        return $file;
    }


    /**
     * Запис в стъп-лога
     */
    static function block($msg, $time = BGERP_SYSTEM_LOCK_TIME)
    {
        $setupLockFile = self::getPath();
        $startTime = time();

        if($str = @file_get_contents($setupLockFile)) {
            list($startTimeEx, $lockTimeEx, $msgEx) = explode("\n", $str, 3);
            if($startTimeEx > 0 && ($startTime - $startTimeEx) < $time * 1.2) {
                $startTime = $startTimeEx;
            }
        }

        @file_put_contents($setupLockFile, "{$startTime}\n{$time}\n{$msg}");
        
        self::$isLocked = TRUE;
    }


    /**
     * Изчиства файла за сетъп-лог
     */
    static function remove()
    {
        if(self::$isLocked) {
            $setupLockFile = self::getPath();
            @unlink(realpath($setupLockFile));
            self::$isLocked = FALSE;
        }
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
            
            list($startTime, $lockTime, $msg) = explode("\n", @file_get_contents($setupLockFile), 3);
            //bp($startTime, $lockTime, $msg);
            if(!$lockTime > 0) {
                $lockTime = BGERP_SYSTEM_LOCK_TIME;
            }

            if($at >= 0 && $at < $lockTime) {
                
                return TRUE;
            } elseif(abs($at) > BGERP_SYSTEM_LOCK_TIME * 30) {
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
            list($startTime, $lockTime, $msg) = explode("\n", @file_get_contents($setupLockFile), 3);
            header('HTTP/1.1 503 Service Temporarily Unavailable');
            header('Status: 503 Service Temporarily Unavailable');
            header('Retry-After: ' . ($lockTime+100));

            if(strtoupper($_SERVER['REQUEST_METHOD']) == 'GET') {
                $refresh = "<meta http-equiv=\"refresh\" content=\"1\">";
            }

            $leftMin = round(($lockTime - (time() - $startTime))/60);

            if($leftMin == 1) {
                $after = "минута";
                $afterEn = "а minute";
            } elseif($leftMin > 1) {
                $after = "{$leftMin} минути";
                $afterEn = "{$leftMin} minutes";
            } else {
                $after = "малко";
                $afterEn = "a while";
            }
            echo "<html><head>
                    <meta charset=\"UTF-8\">
                    {$refresh}</head>
                    <body bgcolor='#000'><div style='font-family: Verdana,Geneva,sans-serif;color:white;position:fixed;top:50%;left:50%;-webkit-transform: translate(-50%, -50%); transform: translate(-50%, -50%);'>
                    <h1 style='border-bottom:solid 1px white;padding-bottom:10px;'>bgERP system maintenance</h1>
                    <h2>We'll be back in {$afterEn}...</h2>
                    <h2>Ще сме на разположение след {$after}...</h2>
                    <p2>{$msg}</hp>
                    </div></body>";
            die;
        }
    }
}