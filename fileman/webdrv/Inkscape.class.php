<?php




/**
 * Драйвер за работа с файлове поддържани от inkscape
 * 
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Inkscape extends fileman_webdrv_ImageT
{
    
    
    /**
     * Височина на експортиране
     */
    static $pngExportHeight = 2000;
    
    
    /**
     * Изходния тип на файла
     */
    static $fileType = 'png';
    
    
    /**
     * Преобразува подадения файл в PDF
     * 
     * @param string $file
     * @param boolean $cmyk
     * @param string $type
     * @param string $name
     * @param array $otherParam
     * 
     * @return string|NULL - Манипулатора на PDF файла
     */
    public static function toPdf($file, $cmyk = FALSE, $type = 'auto', $name = '', $otherParam = array())
    {
        
        return self::convertTo($file, 'pdf', $type, $name, $cmyk, $otherParam);
    }
    
    
    /**
     * Преобразува подадения файл в PNG
     *
     * @param string $file
     * @param string $type
     * @param string $name
     * @param array $otherParam
     *
     * @return string|NULL - Манипулатора на PNG файла
     */
    public static function toPng($file, $type = 'auto', $name = '', $otherParam = array())
    {
        
        return self::convertTo($file, 'png', $type, $name, FALSE, $otherParam);
    }
    
    
    /**
     * Преобразува подадения файл в различни формати
     *
     * @param string $file
     * @param string $file - pdf|png
     * @param string $type
     * @param string $name
     * @param boolean $cmyk
     * @param array $otherParam
     *
     * @return string|NULL - Манипулатора на PNG файла
     */
    protected static function convertTo($file, $to = 'pdf', $type = 'auto', $name = '', $cmyk = FALSE, $otherParam = array())
    {
        if (!$file) return ;
        
        expect(in_array($to, array('pdf', 'png')));
        
        $lineExec = "inkscape [#INPUTF#]  --export-text-to-path  --export-pdf=[#OUTPUTF#] --export-area-page";
        
        if ($to == 'png') {
            $height = static::$pngExportHeight;
            $lineExec = "inkscape [#INPUTF#] --export-png=[#OUTPUTF#] --export-area-drawing";
            if ($otherParam['exportHeight']) {
                $lineExec .= ' --export-height=' . $otherParam['exportHeight'];
            }
            
            if ($otherParam['exportWidth']) {
                $lineExec .= ' --export-width=' . $otherParam['exportWidth'];
            }
        }
        
        cls::load('fileman_Files');
        
        $fileType = self::getFileTypeFromStr($file, $type);
        
        if ($fileType == 'string') {
            $name = ($name) ? $name : 'file.svg';
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
        $outFilePath = $Script->tempDir . $name . '_to.' . $to;
        
        // Задаваме placeHolder' ите за входния и изходния файл
        $Script->setFile('INPUTF', $file);
        $Script->setFile('OUTPUTF', $outFilePath);
        
        $Script->setProgram('inkscape', fileman_Setup::get('INKSCAPE_PATH'));
        
        $errFilePath = self::getErrLogFilePath($outFilePath);
        
        // Скрипта, който ще конвертира файла в PNG формат
        $Script->lineExec($lineExec, array('errFilePath' => $errFilePath));
        
        // Стартираме скрипта синхронно
        $Script->run(FALSE);
        
        fileman_Indexes::haveErrors($outFilePath, array('type' => $to, 'errFilePath' => $errFilePath));
        
        $resFileHnd = NULL;
        
        if (is_file($outFilePath)) {
            if (!$cmyk) {
                $resFileHnd = fileman::absorb($outFilePath, 'fileIndex');
            } else {
                $resFileHnd = fileman_webdrv_Pdf::rgbToCmyk($outFilePath);
            }
        }
        
        if ($resFileHnd) {
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
        
        return $resFileHnd;
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
        
        try {
            self::getArchiveInst($fRec);
            
            // Директорията, в която се намираме вътре в архива
            $path = core_Type::escape(Request::get('path'));
            
            // Вземаме съдържанието
            $contentStr = self::getArchiveContent($fRec, $path);
            
            // Таб за съдържанието
    		$tabsArr['content'] = (object) 
    			array(
    				'title'   => 'Съдържание',
    				'html'    => "<div class='webdrvTabBody' style='white-space:pre-wrap;'><div class='webdrvFieldset'><div class='legend'>" . tr("Съдържание") . "</div>{$contentStr}</div></div>",
    				'order' => 7,
    			);
        } catch (fileman_Exception $e) {
            // Да не се показва таба за съръдържанието
        }
        
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
        $className = get_called_class();
        
        // Параметри необходими за конвертирането
        $params = array(
            'callBack' => "{$className}::afterConvertToPng",
            'dataId' => $fRec->dataId,
        	'asynch' => TRUE,
            'createdBy' => core_Users::getCurrent('id'),
            'type' => static::$fileType,
        );
        
        // Променливата, с която ще заключим процеса
        $params['lockId'] = static::getLockId($params['type'], $fRec->dataId);

        // Проверявама дали няма извлечена информация или не е заключен
        if (fileman_Indexes::isProcessStarted($params)) return ;
        
        // Заключваме процеса за определено време
        if (core_Locks::get($params['lockId'], 250, 0, FALSE)) {
            
            // Стартираме конвертирането към JPG
            static::startConvertingToPng($fRec, $params);    
        }
    }
    
    
    /**
     * Стартира конвертиране към PNG формат
     * 
     * @param object $fRec - Записите за файла
     * @param array $params - Допълнителни параметри
     */
    static function startConvertingToPng($fRec, $params)
    {
        // Инстанция на класа
        $Script = cls::get(fconv_Script);
        
        // Вземаме името на файла без разширението
        $name = fileman_Files::getFileNameWithoutExt($fRec->fileHnd);

        // Задаваме пътя до изходния файла
        $outFilePath = $Script->tempDir . $name . '.png';
        
        // Задаваме placeHolder' ите за входния и изходния файл
        $Script->setFile('INPUTF', $fRec->fileHnd);
        $Script->setFile('OUTPUTF', $outFilePath);
        
        $height = static::$pngExportHeight;
        
        $Script->setProgram('inkscape', fileman_Setup::get('INKSCAPE_PATH'));
        
        $errFilePath = self::getErrLogFilePath($outFilePath);
        
        // Скрипта, който ще конвертира файла в PNG формат
        $Script->lineExec("inkscape [#INPUTF#] --export-png=[#OUTPUTF#] --export-area-drawing --export-height={$height}", array('errFilePath' => $errFilePath));
        
        // Функцията, която ще се извика след приключване на обработката на файла
        $Script->callBack($params['callBack']);
        
        $params['errFilePath'] = $errFilePath;
        
        // Други необходими променливи
        $Script->params = serialize($params);
        $Script->fName = $name;
        $Script->outFilePath = $outFilePath;
        $Script->fh = $fRec->fileHnd;

        $Script->setCheckProgramsArr('inkscape');
        // Стартираме скрипта синхронно
        if ($Script->run() === FALSE) {
            fileman_Indexes::createError($params);
        }
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
    static function afterConvertToPng($script, &$fileHndArr = array())
    {
        // Десериализираме нужните помощни данни
        $params = unserialize($script->params);
        
        // Проверяваме дали е имало грешка при предишното конвертиране
        if (fileman_Indexes::haveErrors($script->outFilePath, $params)) {
            
            // Отключваме процеса
            core_Locks::release($params['lockId']);
            
            return FALSE;
        }
        
        // Ако възникне грешка при качването на файла (липса на права)
        try {
            
            // Качваме файла в кофата и му вземаме манипулатора
            $fileHnd = fileman::absorb($script->outFilePath, 'fileIndex');
        } catch (core_exception_Expect $e) {
            
            // Създаваме запис в модела за грешка
            fileman_Indexes::createError($params);
    
            // Записваме грешката в лога
            fileman_Indexes::createErrorLog($params['dataId'], $params['type']);
        
        }
        
        // Ако се качи успешно записваме манипулатора в масив
        if ($fileHnd) {
            
            // Масив с манипулатора на файла
            $fileHndArr[$fileHnd] = $fileHnd;
            
            // Текстовата част
            $params['content'] = $fileHndArr;
    
            // Обновяваме данните за запис във fileman_Indexes
            $savedId = fileman_Indexes::saveContent($params);
        }
        
        // Отключваме процеса
        core_Locks::release($params['lockId']);
        
        if ($savedId) {

            // Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
            // и записа от таблицата fconv_Process
            return TRUE;
        }
    
    }
    
    
    /**
     * Връща информация за съдържанието на файла
     * Вика се от fileman_Indexes, за файлове, които нямат запис в модела за съответния тип
     * 
     * @param string $fileHnd
     * @param string $type
     */
    public static function getInfoContentByFh($fileHnd, $type)
    {
        if ($type != 'jpg') return FALSE;
        
        return fileman_Indexes::getInfoContentByFh($fileHnd, static::$fileType);
    }
}
