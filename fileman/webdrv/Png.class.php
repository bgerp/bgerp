<?php

/**
 * Драйвер за работа с .png файлове.
 * 
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Png extends fileman_webdrv_Image
{
    
    
    /**
     * Връща всички табове, които ги има за съответния файл
     * 
     * @param object $fRec - Записите за файла
     * 
     * @return array
     * 
     * @Override
     * @see fileman_webdrv_Image::getTabs
     */
    static function getTabs($fRec)
    {
        // Вземаме табовете от родителя
        $tabsArr = parent::getTabs($fRec);
        
        $barcodeUrl = toUrl(array('fileman_webdrv_Png', 'barcodes', $fRec->fileHnd), TRUE);
        
        $tabsArr['barcodes'] = new stdClass();
        $tabsArr['barcodes']->title = 'Баркодове';
        $tabsArr['barcodes']->html = "<div class='webdrvTabBody'><fieldset class='webdrvFieldset'><legend>Баркодове</legend> <iframe src='{$barcodeUrl}' frameBorder='0' ALLOWTRANSPARENCY='true' class='webdrvIframe'> </iframe></fieldset></div>";
        $tabsArr['barcodes']->order = 3;

        return $tabsArr;
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
        parent::convertToJpg($fRec, 'fileman_webdrv_Png::afterConvertToJpg');
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

            // Това е нужно за да вземем всички баркодове
            
            $savedId = static::saveBarcodes($script, $fileHndArr);
            
            if ($savedId) {
    
                // Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
                // и записа от таблицата fconv_Process
                return TRUE;
            } else {
                
                $params = unserialize($script);
                
                // Записваме грешката в лога
                static::createErrorLog($params['dataId'], $params['type']);
            }
        }
    }
}