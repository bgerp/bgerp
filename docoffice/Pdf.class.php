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
        
        // Скрипта, който ще конвертира
        $Script->lineExec('pdftotext -nopgbrk [#INPUTF#] [#OUTPUTF#]');
        
        // Функцията, която ще се извика след приключване на операцията
        $Script->callBack('bgerp_FileInfo::afterGetContentFrom');
        
        // Други необходими променливи
        $Script->ext = $params['ext'];
        $Script->fileInfoId = $params['fileInfoId'];
        $Script->outFilePath = $outFilePath;
        $Script->fh = $fileHnd;

        // Стартираме скрипта синхронно
        $Script->run($params['asynch']);
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
        
        // Скрипта, който ще конвертира файла от PDF в JPG формат
        $Script->lineExec('convert -density 100 [#INPUTF#] [#OUTPUTF#]');
        
        // Функцията, която ще се извика след приключване на обработката на файла
        $Script->callBack('bgerp_FileInfo::afterConvertFileToJpg');
        
        // Други необходими променливи
        $Script->ext = $params['ext'];
        $Script->fileInfoId = $params['fileInfoId'];
        $Script->fName = $name;
        $Script->fh = $fileHnd;

        // Стартираме скрипта синхронно
        $Script->run($params['asynch']);
    }
}