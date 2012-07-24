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
class fileman_Mimes extends core_BaseClass {
    
    
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
        if(!self::$mime2ext) {
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
        if(!$ext) return;

        self::loadExt2Mime();

        $ext = trim(strtolower($ext));

        return self::$ext2mime[$ext];
    }


    /**
     * Връща масив с файловите разширения за даден mime тип
     * Първия елемент на масива е най-подходящото разширение
     */
    static function getExtByMime($mime)
    {
        if(!$mime) return;

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

        if($mime) {
            $extArr = self::getExtByMime($mime);
            
            if(count($extArr)) {
                $ext = fileman_Files::getExt($fileName);

                if(!$ext || !in_array($ext, $extArr)) {
                    $fileName .= '.' . $extArr[0];
                }
            }
        }

        return $fileName;
    }



 }