<?php


/**
 * Клас 'fileman_SetExtensionPlg2' - Проверка и коригиране на разширението на файла
 * 
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_SetExtensionPlg2 extends core_Plugin
{
    
    
    /**
     * Подготвя името на файла
     */
    function on_PrepareFileName($mvc, &$inputFileName, $dataId)
    {
        // Ако няма подадено име или id за данни на файла
        if (!$dataId || !$inputFileName) return ;
        
        // Записа за данните
        $dataRec = fileman_Data::fetch($dataId);
        
        // Очакваме да е валиден път иначе се отказваме
        if(!fileman::isCorrectPath($dataRec->path)) return FALSE;

        // Вземаме mime типа на данните
        $fileMimeType = fileman::getMimeTypeFromFilePath($dataRec->path);
        
        // Добавяне коректно разширение за името на файла в зависимост от миме типа
        $inputFileName = fileman_mimes::addCorrectFileExt($inputFileName, $fileMimeType);
        
        // Ако няма разширение
        if (!fileman_Files::getExt($inputFileName)) {
            
            // Разширението на файла
            $mvc->invoke('identifyFileExt', array(&$ext, $dataRec->path));
            
            // Ако има разширение
            if ($ext) {
                
                // Добавяме след файла
                $inputFileName .= '.' . $ext;
            }
        }
    }
}
