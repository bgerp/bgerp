<?php


/**
 * Клас за конвертиране на PDF документи
 *
 * @category  vendors
 * @package   docoffice
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class docoffice_Pdf
{
    
    
    /**
     * Конвертиране на офис документи с помощта на unoconv
     * 
     * @param fileHandler $fileHnd - Манупулатора на файла, който ще се конвертира
     * @param array $params - Други параметри
     * 				$params['callBack'] - Класа и функцията, която ще се извикат след приключване на конвертирането
     * 				$params['ext'] - Разширението, от което се конвертира /Разширението на файла/
     * 				$params['fileInfoId'] - id към bgerp_FileInfo
     * 				$params['asynch'] - Дали скрипта да се стартира асинхронно или не
     * 
     * @return NULL|string
     */
    static function convertPdfToTxt($fileHnd, $params=array())
    {
        // Инстанция на класа
        $Script = cls::get(fconv_Script);
        
        // Пътя до файла, в който ще се записва получения текст
        $outFilePath = $Script->tempDir . $Script->id . '.txt';
        
        // Задаваме placeHolder' и за входящия и изходящия файл
        $Script->setFile('INPUTF', $fileHnd);
        $Script->setFile('OUTPUTF', $outFilePath);
        
        $errFilePath = fileman_webdrv_Generic::getErrLogFilePath($outFilePath);
        
        // Скрипта, който ще конвертира
        $Script->lineExec('pdftotext -enc UTF-8 -nopgbrk [#INPUTF#] [#OUTPUTF#]', array('errFilePath' => $errFilePath));
        
        // Функцията, която ще се извика след приключване на операцията
        $Script->callBack($params['callBack']);
        
        // Други необходими променливи
        $Script->params = serialize($params);
        $Script->fileInfoId = $params['fileInfoId'];
        $Script->outFilePath = $outFilePath;
        
        $params['errFilePath'] = $errFilePath;
        
        if (!$params['isPath']) {
            $Script->fh = $fileHnd;
        }
        
        $Script->setCheckProgramsArr('pdftotext');
        // Стартираме скрипта синхронно
        if ($Script->run($params['asynch']) === FALSE) {
            fileman_Indexes::createError($params);
        }
        
        $text = '';
        if (!$params['asynch']) {
            $text = @file_get_contents($outFilePath);
            $text = i18n_Charset::convertToUtf8($text, 'UTF-8');
        }
        
        return $text;
    }
    
    
	/**
     * Конвертиране на офис документи с помощта на unoconv
     * 
     * @param fileHandler $fileHnd - Манупулатора на файла, който ще се конвертира
     * @param array $params - Други параметри
     * 				$params['callBack'] - Класа и функцията, която ще се извикат след приключване на конвертирането
     * 				$params['ext'] - Разширението, от което се конвертира /Разширението на файла/
     * 				$params['fileInfoId'] - id към bgerp_FileInfo
     * 				$params['asynch'] - Дали скрипта да се стартира асинхронно или не
     */
    static function convertPdfToJpg($fileHnd, $params=array())
    {
        // Инстанция на класа
        $Script = cls::get(fconv_Script);
        
        // Вземаме името на файла без разширението
        $name = fileman_Files::getFileNameWithoutExt($fileHnd);
        
        // Задаваме пътя до изходния файла
        $outFilePath = $Script->tempDir . $name . '-%d.jpg';
        
        // Задаваме placeHolder' ите за входния и изходния файл
        $Script->setFile('INPUTF', $fileHnd);
        $Script->setFile('OUTPUTF', $outFilePath);
        
        $errFilePath = fileman_webdrv_Generic::getErrLogFilePath($outFilePath);
        
        // Скрипта, който ще конвертира файла от PDF в JPG формат
        $Script->lineExec('convert -density 100 [#INPUTF#] [#OUTPUTF#]', array('errFilePath' => $errFilePath));
        
        // Функцията, която ще се извика след приключване на обработката на файла
        $Script->callBack($params['callBack']);
        
        // Други необходими променливи
        $Script->fileInfoId = $params['fileInfoId'];
        $Script->fName = $name;
        $Script->fh = $fileHnd;
        
        $params['errFilePath'] = $errFilePath;
        fileman_Indexes::haveErrors($outFilePath, $params);
        
        $Script->setCheckProgramsArr('convert');
        // Стартираме скрипта синхронно
        if ($Script->run($params['asynch']) === FALSE) {
            fileman_Indexes::createError($params);
        }
    }
}