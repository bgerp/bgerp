<?php


/**
 * Клас 'fileman_Mimes' - Поддръжка на съответствие между mime и разширение на файл
 *
 *
 * @category  vendors
 * @package   fileman
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_Mimes extends core_Mvc {
    
    
    /**
     * Заглавие на модула
     */
    var $title = 'MIME <=> файлови разширения';
    
    
    /**
     * Масив със съответствие на разширения към mime типове
     */
    static $ext2mime;
    

    /**
     * Масив със съответствие на mime типове към разширения
     */
    static $mime2ext;

    
    /**
     * Зареждане на self::$ext2mime, ако не е зареден
     */
    static function loadExt2Mime()
    {
        if(!self::$ext2mime) {
            //Вземаме цялото име на файла
            $inc = getFullPath('fileman/data/ext2mime.inc.php');
            
            //Инклудваме го, за да можем да му използваме променливите
            include($inc);
            
            // Зареждаме масива в статична променлива
            self::$ext2mime = $ext2mime;
        }
    }
    
    
    /**
     * Зареждане на self::$mime2ext, ако не е зареден
     */
    static function loadMime2Ext()
    {
        if (!self::$mime2ext) {
            //Вземаме цялото име на файла
            $inc = getFullPath('fileman/data/mime2ext.inc.php');
            
            //Инклудваме го, за да можем да му използваме променливите
            include($inc);

            // Зареждаме масива в статична променлива
            self::$mime2ext = $mime2ext;
        }
    }


    /**
     * Връща най-подходящия mime тип за даденото разширение
     */
    static function getMimeByExt($ext)
    {
        if (!$ext) return;

        self::loadExt2Mime();

        $ext = trim(strtolower($ext));

        return self::$ext2mime[$ext];
    }
    

    /**
     * регенериране на ext2mime
     */
    function act_Ext()
    {
        requireRole('admin,debug');

        self::loadExt2Mime();
        self::loadMime2Ext();

        foreach(self::$mime2ext as $mime => $exts) {
            foreach(explode(' ', $exts) as $ext) {
                if(!isset(self::$ext2mime[$ext])) {
                    self::$ext2mime[$ext] = $mime;
                }
            }
        }
        
        ksort(self::$ext2mime);

        foreach(self::$ext2mime as $ext => $mime) {
            $res .= "    \"{$ext}\" => \"{$mime}\",<br>";
        }
        
        return $res;
    }
    

    /**
     * Връща масив с файловите разширения за даден mime тип
     * Първия елемент на масива е най-подходящото разширение
     */
    static function getExtByMime($mime)
    {
        if (!$mime) return;

        self::loadMime2Ext();

        $mime = trim(strtolower($mime));

        $exts = self::$mime2ext[$mime];

        if(!$exts) return array();

        return explode(' ', $exts);
    }

    
    /**
     * Добавя коректното разшитение на файл, като отчита неговия mime тип
     */
    static function addCorrectFileExt($fileName, $mime)
    {
        expect($fileName);

        if(!$mime) return $fileName;

        $extArr = self::getExtByMime($mime);
        
        if(count($extArr)) {
            
            $oFileName = $fileName;
            
            $ext = fileman_Files::getExt($fileName);
            
            $ext = mb_strtolower($ext);

            if(!$ext || !in_array($ext, $extArr)) {
                
                // TODO става много сложно
                // Може да се направи само ако няма разширение да се променя, но това ще позволи качването на файлове със сгрешени разширения
                
                // Масив с разширенията, на които вярваме и няма да се променят, ако mimeto им е в $noTrustMimeArr
                $trustExtArr = array('pdf', 'png', 'jpg', 'jpeg', 'doc', 'rar', 'zip', 'docx', 'txt', 'svg');
                
                // Масив с mime типове
                $noTrustMimeArr = array('application/octet-stream', 'application/x-httpd-php', 'text/x-c', 'text/x-c++', 'text/plain', 'application/zip');
                
                if (!$ext || (!in_array($mime, $noTrustMimeArr)) && (!in_array($ext, $trustExtArr)) ) {
                    
                    $useOldName = FALSE;
                    
                    if ($ext) {
                        // Миме типовете на разширенията
                        $extMime = self::getMimeByExt($ext);
                        $newExtMime = self::getMimeByExt($extArr[0]);
                        
                        // Ако новото разширение няма mime или и на двете разширения е text, да не се променя
                        if (!$newExtMime) {
                            $useOldName = TRUE;
                        } elseif ($extMime && $newExtMime) {
                            list($extMimePart) = explode('/', $extMime);
                            list($newExtMimePart) = explode('/', $newExtMime);
                            if (($extMimePart == 'text') && ($extMimePart == $newExtMimePart)) {
                                $useOldName = TRUE;
                            }
                        }
                    }
                    
                    if ($useOldName) {
                        $fileName = $oFileName;
                    } else {
                        $fileName .= '.' . $extArr[0];  
                    }
                }
            }
        }
        
        return $fileName;
    }
    
    
    /**
     * Проверява, дали разширението на файла е в допустимите за миме типа
     * 
     * @param string $mimeType - Миме типа на файла
     * @param string $ext - Разширението на файла
     * 
     * @return boolean
     */
    static function isCorrectExt($mimeType, $ext)
    {
        // Ако един от параметрите липсва
        if (!trim($mimeType) || !trim($ext)) return ;
        
        // Параметрите в долен регистър
        $ext = strtolower($ext);
        $mimeType = strtolower($mimeType);
        
        // Вземаме масива с всички позволените разширения, за съответния миме тип
        $extArr = static::getExtByMime($mimeType);
        
        // Ако е в масива с позволени разширения
        if (in_array($ext, $extArr)) {
            
            return TRUE;
        }
        
        return FALSE;
    }
}
