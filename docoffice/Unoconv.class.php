<?php


/**
 * Плъгин за конвертиране на офис документи с помощта на unoconv
 *
 * @category  vendors
 * @package   docoffice
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class docoffice_Unoconv extends core_Manager
{
    
    
    /**
     * 
     */
    var $interfaces = 'docoffice_ConverterIntf';
    
    
    /**
     * 
     */
    var $title = 'Unoconv';
    
    
    /**
     * Конвертиране на офис документи с помощта на unoconv
     * 
     * @param fileHandler $fileHnd - Манупулатора на файла, който ще се конвертира
     * @param string $toExt - Разширението, в което ще се конвертира
     * @param array $params - Други параметри
     * 				$params['callBack'] - Класа и функцията, която ще се извикат след приключване на конвертирането
     * 				$params['ext'] - Разширението, от което се конвертира /Разширението на файла/
     * 				$params['fileInfoId'] - id към bgerp_FileInfo
     * 				$params['asynch'] - Дали скрипта да се стартира асинхронно или не
     */
    static function convertDoc($fileHnd, $toExt, $params=array())
    {
        // Разширението да е в дония регистър
        $toExt = strtolower($toExt);
        
        // Process id' то на office пакета
        $officePid = docoffice_Office::getStartedOfficePid();

        // Ако не е стартиране
        if (!$officePid) {    
            
            // Стартираме офис пакета
            docoffice_Office::startOffice();        
        } else {
            
            // Ако е стартиран проверяваме дали не трябва да се рестартира
            docoffice_Office::checkRestartOffice();
        }
        
        // Константите, които ще използваме
        $conf = core_Packs::getConfig('docoffice');
        $pythonPath = $conf->OFFICE_CONVERTER_PYTHON;
        $unoconv = $conf->OFFICE_CONVERTER_UNOCONV;
        
        // Инстанция на класа
        $Script = cls::get(fconv_Script);
        
        // Пътя до файла, в който ще се записва получения текст
        $outFilePath = $Script->tempDir . fileman_Files::getFileNameWithoutExt($fileHnd) . ".{$toExt}";

        // Задаваме файловете и параметрите
        $Script->setFile('INPUTF', $fileHnd);
        $Script->setParam('TOEXT', $toExt, TRUE);
        $Script->setParam('UNOCONV', $unoconv, TRUE);
        
        // Добавяме към изпълнимия скрипт
        $lineExecStr = "[#UNOCONV#] -f [#TOEXT#] [#INPUTF#]";
        
        // Ако е дефиниранеп пътя до PYTHON
        if ($pythonPath) {
            
            // Задаваме параметъра за питон
            $Script->setParam('PYTHON', $pythonPath, TRUE);
            
            // Добавяме в началото на изпълнимия скрипт placeHolder за питон
            $lineExecStr = "[#PYTHON#] {$lineExecStr}";
        }
        
        // Скрипта, който ще конвертира
        $Script->lineExec($lineExecStr);

        // Функцията, която ще се извика след приключване на операцията
        if ($params['callBack']) {
            $Script->callBack($params['callBack']);    
        }

        // Други необходими променливи
        $Script->ext = $params['ext'];
        $Script->fileInfoId = $params['fileInfoId'];
        $Script->outFilePath = $outFilePath;
        $Script->fh = $fileHnd;
        
        // Увеличаваме броя на направените конвертирания с единица
        docoffice_Office::increaseConvertCount();
        
        // Заключваме офис пакета
        docoffice_Office::lockOffice(50, 35);
        
        // Стартираме скрипта синхронно
        $Script->run($params['asynch']);
        
        // Това трябва да е в callBack функцията, за да може да отключим процеса
        // дори и скрипта да е стартиран асинхронно
        // docoffice_Office::unlockOffice();
    }
}