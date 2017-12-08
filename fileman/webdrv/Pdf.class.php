<?php


/**
 * Драйвер за работа с .pdf файлове.
 * 
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Pdf extends fileman_webdrv_Office
{
    
    
    /**
     * Преобразува цветовия модел на подадения PDF файл от RGB в CMYK
     * 
     * @param string $file
     * @param string $type
     * @param string $name
     * 
     * @return string|NULL
     */
    public static function rgbToCmyk($file, $type = 'auto', $name = '')
    {
        cls::load('fileman_Files');
        
        if (!$file) return ;
        
        $fileType = self::getFileTypeFromStr($file, $type);
        
        if ($fileType == 'string') {
            $name = ($name) ? $name : 'file.pdf';
            $file = fileman::addStrToFile($file, $name);
        }
		
        if (!$name) {
            // Вземаме името на файла без разширението
            $name = fileman_Files::getFileNameWithoutExt($file);
        } else {
            $nameAndExt = fileman_Files::getNameAndExt($name);
            $name = $nameAndExt['name'];
        }
        
        // Инстанция на класа
        $Script = cls::get('fconv_Script');
        
        // Задаваме пътя до изходния файла
        $outFilePath = $Script->tempDir . $name . '_CMYK.pdf';
        
        // Задаваме placeHolder' ите за входния и изходния файл
        $Script->setFile('INPUTF', $file);
        $Script->setFile('OUTPUTF', $outFilePath);
 
        $Script->setProgram('gs', fileman_Setup::get('GHOSTSCRIPT_PATH'));
        
        $errFilePath = self::getErrLogFilePath($outFilePath);
        
        // Скрипта, който ще конвертира файла в PNG формат
        $Script->lineExec("gs -dSAFER -dBATCH -dNOPAUSE -dNOCACHE -dCompatibilityLevel=1.5 -sDEVICE=pdfwrite -sColorConversionStrategy=CMYK -dProcessColorModel=/DeviceCMYK -sOutputFile=[#OUTPUTF#] [#INPUTF#]", array('errFilePath' => $errFilePath));
        
        // Стартираме скрипта синхронно
        $Script->run(FALSE);
        
        fileman_Indexes::haveErrors($outFilePath, array('type' => 'pdf', 'errFilePath' => $errFilePath));
        
        $nFileHnd = NULL;
        
        if (is_file($outFilePath)) {
            $nFileHnd = fileman::absorb($outFilePath, 'fileIndex');
        }
        
        if ($nFileHnd) {
            if ($Script->tempDir) {
                // Изтриваме временната директория с всички файлове вътре
                core_Os::deleteDir($Script->tempDir);
            }
            
            if ($fileType == 'string') {
                fileman::deleteTempPath($file);
            } 
        } else {
            if (is_file($errFilePath)) {
                $err = @file_get_contents($errFilePath);
                self::logErr('Грешка при конвертиране: ' . $errFilePath);
            }
        }
        
        return $nFileHnd;
    }
    
    
    /**
     * Връща всички табове, които ги има за съответния файл
     * 
     * @param object $fRec - Записите за файла
     * 
     * @return array
     * 
     * @Override
     * @see fileman_webdrv_Office::getTabs
     */
    static function getTabs($fRec)
    {
        // Вземаме табовете от родителя
        $tabsArr = parent::getTabs($fRec);
        
        if (self::canShowTab($fRec->fileHnd, 'barcodes')){
            $barcodeUrl = toUrl(array('fileman_webdrv_Pdf', 'barcodes', $fRec->fileHnd), TRUE);
            $tabsArr['barcodes'] = (object)
            array(
                    'title' => 'Баркодове',
                    'html'  => "<div class='webdrvTabBody'><div class='webdrvFieldset'><div class='legend'>" . tr("Баркодове") . "</div> <iframe src='{$barcodeUrl}' frameBorder='0' ALLOWTRANSPARENCY='true' class='webdrvIframe'> </iframe></div></div>",
                    'order' => 6,
            );
        }

        return $tabsArr;
    }
    
    
    /**
     * Стартира извличането на информациите за файла
     * 
     * @param object $fRec - Записите за файла
     * 
     * @Override
     * @see fileman_webdrv_Office::startProcessing
     */
    static function startProcessing($fRec) 
    {
        parent::startProcessing($fRec);
        static::getBarcodes($fRec);
    }
    
    
    /**
     * Извлича текстовата част от файла
     * 
     * @param object $fRec - Записите за файла
     * 
     * @Override
     * @see fileman_webdrv_Office::extractText
     */
    static function extractText($fRec)
    {
        // Параметри необходими за конвертирането
        $params = array(
            'callBack' => 'fileman_webdrv_Pdf::afterExtractText',
            'createdBy' => core_Users::getCurrent('id'),
            'type' => 'text',
        );
        
        if (is_object($fRec)) {
            $params['dataId'] = $fRec->dataId;
            $params['asynch'] = TRUE;
            $file = $fRec->fileHnd; 
        } else {
            $params['asynch'] = FALSE;
            $params['isPath'] = TRUE;
            $file = $fRec;
        }
        
        $lId = self::prepareLockId($fRec);
        // Променливата, с която ще заключим процеса
        $params['lockId'] = self::getLockId($params['type'], $lId);
        
        // Проверявама дали няма извлечена информация или не е заключен
        if (fileman_Indexes::isProcessStarted($params)) return ;
        
        // Заключваме процеса за определно време
        if (core_Locks::get($params['lockId'], 100, 0, FALSE)) {
            
            // Стартираме конвертирането
            return static::convertPdfToTxt($file, $params);   
        }
    }
    
    
	/**
     * Конвертиране на pdf документи към txt с помощта на pdftotext
     * 
     * @param fileHandler $fileHnd - Манупулатора на файла, който ще се конвертира
     * @param array $params - Други параметри
     * 				$params['callBack'] - Класа и функцията, която ще се извикат след приключване на конвертирането
     * 				$params['asynch'] - Дали скрипта да се стартира асинхронно или не
     * 				и др.
     * 
     * @return NULL|string
     */
    static function convertPdfToTxt($fileHnd, $params=array())
    {
        $text = docoffice_Pdf::convertPdfToTxt($fileHnd, $params);
        
        return $text;
    }
    
    
    /**
     * Конвертиране в JPG формат
     * 
     * @param object $fRec - Записите за файла
     * 
     * @Override
     * @see fileman_webdrv_Office::convertToJpg
     */
    static function convertToJpg($fRec)
    {
        // Параметри необходими за конвертирането
        $params = array(
            'callBack' => 'fileman_webdrv_Pdf::afterConvertToJpg',
            'dataId' => $fRec->dataId,
        	'asynch' => TRUE,
            'createdBy' => core_Users::getCurrent('id'),
            'type' => 'jpg',
        );
        
        // Променливата, с която ще заключим процеса
        $params['lockId'] = static::getLockId($params['type'], $fRec->dataId);

        // Проверявама дали няма извлечена информация или не е заключен
        if (fileman_Indexes::isProcessStarted($params)) return ;
        
        // Заключваме процеса за определно време
        if (core_Locks::get($params['lockId'], 100, 0, FALSE)) {
            
            // Стартираме конвертирането
            static::convertPdfToJpg($fRec->fileHnd, $params);    
        }
    }
    
    
    /**
     * Дали може да се извлича баркод
     * 
     * @return boolean
     */
    public static function canGetBarcodes()
    {
        
        return TRUE;
    }
}
