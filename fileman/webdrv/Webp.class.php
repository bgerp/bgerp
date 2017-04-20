<?php


/**
 * Драйвер за работа с .webp файлове.
 * 
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Webp extends fileman_webdrv_ImageT
{
    
    
    /**
     * Стартира конвертиране към JPG формат
     * 
     * @param object $fRec - Записите за файла
     * @param array $params - Допълнителни параметри
     */
    static function startConvertingToJpg($fRec, $params)
    {
        // Инстанция на класа
        $Script = cls::get(fconv_Script);
        
        // Вземаме името на файла без разширението
        $name = fileman_Files::getFileNameWithoutExt($fRec->fileHnd);
		
        // Задаваме пътя до изходния файла
        $outFilePath = $Script->tempDir . $name . '-0.jpg';
        
        // Задаваме placeHolder' ите за входния и изходния файл
        $Script->setFile('INPUTF', $fRec->fileHnd);
        $Script->setFile('OUTPUTF', $outFilePath);
        
        $errFilePath = self::getErrLogFilePath($outFilePath);
        
        // Скрипта, който ще конвертира файла в JPG формат
        $Script->lineExec('dwebp [#INPUTF#] -o [#OUTPUTF#]', array('errFilePath' => $errFilePath));
        
        // Функцията, която ще се извика след приключване на обработката на файла
        $Script->callBack($params['callBack']);
        
        $params['errFilePath'] = $errFilePath;
        
        // Други необходими променливи
        $Script->params = serialize($params);
        $Script->fName = $name;
        $Script->outFilePath = $outFilePath;
        $Script->fh = $fRec->fileHnd;
		
        $Script->setCheckProgramsArr('dwebp');
        // Стартираме скрипта Aсинхронно
        if ($Script->run() === FALSE) {
            fileman_Indexes::createError($params);
        }
    }
}
