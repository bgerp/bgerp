<?php


/**
 * Път до програмата
 */
defIfNot('INKSCAPE_PATH', 'inkscape');


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
     * 
     * @return string - Манипулатора на PDF файла
     */
    public static function toPdf($file, $cmyk = FALSE)
    {
        if (!$file) return ;
        
        cls::load('fileman_Files');
        
        if ((strlen($file) == FILEMAN_HANDLER_LEN) && (strpos($file, '/') === FALSE)) {
            $file = fileman_Files::fetchByFh($file, 'path');
    	}
        
        // Инстанция на класа
        $Script = cls::get('fconv_Script');
        
        // Вземаме името на файла без разширението
        $name = fileman_Files::getFileNameWithoutExt($file);
        
        // Задаваме пътя до изходния файла
        $outFilePath = $Script->tempDir . $name . '.pdf';
        
        // Задаваме placeHolder' ите за входния и изходния файл
        $Script->setFile('INPUTF', $file);
        $Script->setFile('OUTPUTF', $outFilePath);
        
        $Script->setProgram('inkscape', INKSCAPE_PATH);
        
        // Скрипта, който ще конвертира файла в PNG формат
        $Script->lineExec("inkscape [#INPUTF#] --export-pdf=[#OUTPUTF#] --export-area-drawing");
        
        // Стартираме скрипта синхронно
        $Script->run(FALSE);
        
        if (!$cmyk) {
            $resFileHnd = fileman::absorb($outFilePath, 'fileIndex');
        } else {
            $resFileHnd = fileman_webdrv_Pdf::rgbToCmyk($outFilePath);
        }
        
        if ($Script->tempDir) {
            // Изтриваме временната директория с всички файлове вътре
            core_Os::deleteDir($Script->tempDir);
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
        
        $Script->setProgram('inkscape', INKSCAPE_PATH);
        
        // Скрипта, който ще конвертира файла в PNG формат
        $Script->lineExec("inkscape [#INPUTF#] --export-png=[#OUTPUTF#] --export-area-drawing --export-height={$height}");
        
        // Функцията, която ще се извика след приключване на обработката на файла
        $Script->callBack($params['callBack']);
        
        // Други необходими променливи
        $Script->params = serialize($params);
        $Script->fName = $name;
        $Script->outFilePath = $outFilePath;
        $Script->fh = $fRec->fileHnd;
        
        // Стартираме скрипта Aсинхронно
        $Script->run();
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
        if (fileman_Indexes::haveErrors($script->outFilePath, $params['type'], $params)) {
            
            // Отключваме процеса
            core_Locks::release($params['lockId']);
            
            return FALSE;
        }
        
        // Инстанция на класа
        $Fileman = cls::get('fileman_Files');
        
        // Ако възникне грешка при качването на файла (липса на права)
        try {
            
            // Качваме файла в кофата и му вземаме манипулатора
            $fileHnd = $Fileman->addNewFile($script->outFilePath, 'fileIndex');
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
