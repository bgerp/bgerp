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
    public $interfaces = 'docoffice_ConverterIntf';
    
    
    
    public $title = 'Unoconv';
    
    
    /**
     * Конвертиране на офис документи с помощта на unoconv
     *
     * @param fileHandler $fileHnd - Манупулатора на файла, който ще се конвертира
     * @param string      $toExt   - Разширението, в което ще се конвертира
     * @param array       $params  - Други параметри
     *                             $params['callBack'] - Класа и функцията, която ще се извикат след приключване на конвертирането
     *                             $params['fileInfoId'] - id към bgerp_FileInfo
     *                             $params['asynch'] - Дали скрипта да се стартира асинхронно или не
     */
    public static function convertDoc($fileHnd, $toExt, $params = array())
    {
        // Разширението да е в дония регистър
        $toExt = strtolower($toExt);
        
        // Стартираме или рестартираме офис пакета
        docoffice_Office::prepareOffice();
        
        // Константите, които ще използваме
        $conf = core_Packs::getConfig('docoffice');
        $pythonPath = $conf->OFFICE_CONVERTER_PYTHON;
        $unoconv = $conf->OFFICE_CONVERTER_UNOCONV;
        
        // Инстанция на класа
        $Script = cls::get(fconv_Script);
        
        // Пътя до файла, в който ще се записва получения текст
        $outFilePath = $Script->tempDir . fileman_Files::getFileNameWithoutExt($fileHnd) . ".{$toExt}";
        
        // Вземаме порта на който слуша офис пакета
        $port = docoffice_Office::getOfficePort();
        
        // Задаваме файловете и параметрите
        $Script->setFile('INPUTF', $fileHnd);
        $Script->setParam('TOEXT', $toExt, true);
        $Script->setParam('UNOCONV', $unoconv, true);
        $Script->setParam('PORT', $port, true);
        
        // Ако има зададен порт
        if ($port) {
            
            // Добавяме към изпълнимия скрипт
            $lineExecStr = '[#UNOCONV#] -f [#TOEXT#] -p [#PORT#] [#INPUTF#]';
        } else {
            
            // Добавяме към изпълнимия скрипт
            $lineExecStr = '[#UNOCONV#] -f [#TOEXT#] [#INPUTF#]';
        }
        
        // Ако е дефиниранеп пътя до PYTHON
        if ($pythonPath) {
            
            // Задаваме параметъра за питон
            $Script->setParam('PYTHON', $pythonPath, true);
            
            // Добавяме в началото на изпълнимия скрипт placeHolder за питон
            $lineExecStr = "[#PYTHON#] {$lineExecStr}";
        }
        
        $errFilePath = fileman_webdrv_Generic::getErrLogFilePath($outFilePath);
        
        // Скрипта, който ще конвертира
        $Script->lineExec($lineExecStr, array('LANG' => 'en_US.UTF-8', 'HOME' => $Script->tempPath, 'errFilePath' => $errFilePath));

        // Функцията, която ще се извика след приключване на операцията
        $Script->callBack('docoffice_Unoconv::afterConvertDoc');
        
        $params['errFilePath'] = $errFilePath;
        
        // Други необходими променливи
        $Script->params = serialize($params);
        $Script->outFilePath = $outFilePath;
        $Script->fh = $fileHnd;
        
        // Заключваме unoconv
        static::lockUnoconv(100, 60);
        
        // Заключваме офис пакета
        docoffice_Office::lockOffice(100, 60);
        
        // Увеличаваме броя на направените конвертирания с единица
        docoffice_Office::increaseConvertCount();

        $Script->setCheckProgramsArr($unoconv);
        
        // Стартираме скрипта синхронно
        if ($Script->run($params['asynch']) === false) {
            if ($params['outType']) {
                $params['type'] = $params['outType'];
            }
            fileman_Indexes::createError($params);
            
            return;
        }
        
        return $Script->outFilePath;
    }
    
    
    /**
     * Получава управелението след приключване на конвертирането.
     *
     * @param fconv_Script $script - Парамтри
     *
     * @return boolean
     */
    public static function afterConvertDoc($script)
    {
        // Отключва офис пакета
        docoffice_Office::unlockOffice();
        
        // Отключваме unoconv
        docoffice_Unoconv::unlockUnoconv();
        
        // Десериализираме параметрите
        $params = unserialize($script->params);
        
        // Ако има callBack функция
        if ($params['callBack']) {
            
            // Разделяме класа от метода
            $funcArr = explode('::', $params['callBack']);
            
            // Обект на класа
            $object = cls::get($funcArr[0]);
            
            // Метода
            $method = $funcArr[1];
            
            // Извикваме callBack функцията и връщаме резултата
            $result = call_user_func_array(array($object, $method), array($script));
            
            return $result;
        }
    }

    
    
    /**
     * Заключваме UNOCONV
     *
     * @param int $maxDuration - Максималното време за което ще се опитаме да заключим
     * @param int $maxTray     - Максималният брой опити, за заключване
     */
    public static function lockUnoconv($maxDuration = 50, $maxTray = 30)
    {
        core_Locks::get('unoconv', $maxDuration, $maxTray, false);
    }
    
    
    /**
     * Отключваме UNOCONV
     */
    public static function unlockUnoconv()
    {
        core_Locks::release('unoconv');
    }
}
