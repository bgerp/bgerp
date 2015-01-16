<?php


/**
 * Родителски клас на всички изображения, на които не може да им се генерира thumbnail
 * 
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_ImageT extends fileman_webdrv_Image
{
    
    
	/**
     * Стартира извличането на информациите за файла
     * 
     * @param object $fRec - Записите за файла
     * 
     * @Override
     * @see fileman_webdrv_Image::startProcessing
     */
    static function startProcessing($fRec) 
    {
        parent::startProcessing($fRec);
        static::convertToJpg($fRec);
    }
    
    
    /**
     * Връща шаблон с превюто на файла
     * 
     * @param object $fRec - Записите за файла
     * 
     * @return string|core_ET - Шаблон с превюто на файла
     * 
     * @Override
     * @see fileman_webdrv_Image::getThumbPrev
     */
    static function getThumbPrev($fRec) 
    {
        // Вземаме масива с изображенията
        $jpgArr = fileman_Indexes::getInfoContentByFh($fRec->fileHnd, 'jpg');

        // Ако няма такъв запис
        if ($jpgArr === FALSE) {
            
            // Ако файла все още не е готов
            return 'Моля презаредете...'; // TODO с AJAX - автоматично
        }
        
        // Ако е обект и има съобщение за грешка
        if (is_object($jpgArr) && $jpgArr->errorProc) {
            
            // Връщаме съобщението за грешка
            return tr($jpgArr->errorProc);
        }
        
        // Вземаме записа на JPG изображението
        $fRecJpg = fileman_Files::fetchByFh(key($jpgArr));
        
        // Генерираме съдържание от JPG файла
        return parent::getThumbPrev($fRecJpg);
    }
}