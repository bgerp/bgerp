<?php

/**
 * Драйвер за работа с .bmp файлове.
 * 
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Bmp extends fileman_webdrv_Image
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
     * Конвертиране в JPG формат
     * 
     * @param object $fRec - Записите за файла
     * 
     * @Override
     * @see fileman_webdrv_Image::convertToJpg
     */
    static function convertToJpg($fRec, $callBack = 'fileman_webdrv_Image::afterConvertToJpg')
    {
        parent::convertToJpg($fRec, 'fileman_webdrv_Tif::afterConvertToJpg');
    }
    
    
	/**
     * Функция, която получава управлението след конвертирането на файл в JPG формат
     * 
     * @param object $script - Обект със стойности
     * 
     * @return boolean TRUE - Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
     * и записа от таблицата fconv_Process
     * 
     * @access protected
     */
    static function afterConvertToJpg($script, &$fileHndArr = array())
    {
        // Извикваме родутелския метод
        if (parent::afterConvertToJpg($script, $fileHndArr)) {

            // Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
            // и записа от таблицата fconv_Process
            return TRUE;
        }
    }

    
    /**
     * Връща шаблон с превюто на файла
     * 
     * @param object $fRec - Записите за файла
     * 
     * @return core_Et - Шаблон с превюто на файла
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
            return $jpgArr->errorProc;
        }
        
        // Вземаме записа на JPG изображението
        $fRecJpg = fileman_Files::fetchByFh(key($jpgArr));
        
        // Генерираме съдържание от JPG файла
        return parent::getThumbPrev($fRecJpg);
    }
}