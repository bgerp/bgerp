<?php


/**
 * Родителски клас на всички изображения. Съдържа методите по подразбиране.
 * 
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Image extends fileman_webdrv_Generic
{
    
    
    /**
     * Кой таб да е избран по подразбиране
     * @Override
     * @see fileman_webdrv_Generic::$defaultTab
     */
    static $defaultTab = 'preview';


	/**
     * Връща всички табове, които ги има за съответния файл
     * 
     * @param object $fRec - Записите за файла
     * 
     * @return array
     * 
     * @Override
     * @see fileman_webdrv_Generic::getTabs
     */
    static function getTabs($fRec)
    {
        // Вземаме табовете от родителя
        $tabsArr = parent::getTabs($fRec);
        
        // URL за показване на преглед на файловете
        $previewUrl = toUrl(array(get_called_class(), 'preview', $fRec->fileHnd), TRUE);
        
        // Таб за преглед
		$tabsArr['preview'] = (object) 
			array(
				'title'   => 'Преглед',
				'html'    => "<div class='webdrvTabBody'><div class='webdrvFieldset'><div class='legend'>" . tr("Преглед") . "</div> <iframe src='{$previewUrl}' frameBorder='0' ALLOWTRANSPARENCY='true' class='webdrvIframe'> </iframe></div></div>",
				'order' => 2,
			);
        
		if (self::canShowTab($fRec->fileHnd, 'text') || self::canShowTab($fRec->fileHnd, 'textOcr', TRUE, TRUE)) {
		    // URL за показване на текстовата част на файловете
            $textPart = toUrl(array('fileman_webdrv_Pdf', 'text', $fRec->fileHnd), TRUE);
            
            // Таб за текстовата част
    		$tabsArr['text'] = new stdClass();
            $tabsArr['text']->title = 'Текст';
            $tabsArr['text']->html = "<div class='webdrvTabBody'><div class='webdrvFieldset'><div class='legend'>" . tr("Текст") . "</div><iframe src='{$textPart}' frameBorder='0' ALLOWTRANSPARENCY='true' class='webdrvIframe'></div></iframe></div>";
            $tabsArr['text']->order = 4;
		}
        
        return $tabsArr;
    }
    
	
	/**
     * Стартира извличането на информациите за файла
     * 
     * @param object $fRec - Записите за файла
     * 
     * @Override
     * @see fileman_webdrv_Generic::startProcessing
     */
    static function startProcessing($fRec) 
    {
        parent::startProcessing($fRec);
        static::extractText($fRec);
    }
    
    
    /**
     * Извлича текстовата част от файла
     * 
     * @param object $fRec - Записите за файла
     */
    static function extractText($fRec)
    {
        // Параметри необходими за конвертирането
        $params = array(
            'createdBy' => core_Users::getCurrent('id'),
            'type' => 'text',
        );
        
        $dId = self::prepareLockId($fRec);
        
        if (is_object($fRec)) {
            $params['dataId'] = $fRec->dataId;
        }
        
        // Променливата, с която ще заключим процеса
        $params['lockId'] = self::getLockId('text', $dId);
        
        // Проверявама дали няма извлечена информация или не е заключен
        if (fileman_Indexes::isProcessStarted($params)) return ;
        
        // Заключваме процеса за определено време
        if (core_Locks::get($params['lockId'], 100, 0, FALSE)) {
        	
            $text = '';
        	
            if (is_object($fRec)) {
                // Обновяваме данните за запис във fileman_Indexes
                $params['content'] = $text;
                fileman_Indexes::saveContent($params);
            }
        	
            // Отключваме процеса
            core_Locks::release($params['lockId']);
        	
            return $text;
        }
    }
    
    
    /**
     * Конвертиране в JPG формат
     * 
     * @param object $fRec - Записите за файла
     * @param string $callBack - Функцията, която ще се извика след приключване на процеса
     */
    static function convertToJpg($fRec, $callBack = 'fileman_webdrv_Image::afterConvertToJpg')
    {
        // Параметри необходими за конвертирането
        $params = array(
            'callBack' => $callBack,
            'dataId' => $fRec->dataId,
        	'asynch' => TRUE,
            'createdBy' => core_Users::getCurrent('id'),
            'type' => 'jpg',
        );
        
        // Променливата, с която ще заключим процеса
        $params['lockId'] = static::getLockId($params['type'], $fRec->dataId);

        // Проверявама дали няма извлечена информация или не е заключен
        if (fileman_Indexes::isProcessStarted($params)) return ;
        
        // Заключваме процеса за определено време
        if (core_Locks::get($params['lockId'], 100, 0, FALSE)) {
            
            // Стартираме конвертирането към JPG
            static::startConvertingToJpg($fRec, $params);    
        }
    }
    
    
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
        $outFilePath = $Script->tempDir . $name . '-%d.jpg';
        
        // Задаваме placeHolder' ите за входния и изходния файл
        $Script->setFile('INPUTF', $fRec->fileHnd);
        $Script->setFile('OUTPUTF', $outFilePath);
        
        $errFilePath = self::getErrLogFilePath($outFilePath);
        
        // Скрипта, който ще конвертира файла в JPG формат
        $Script->lineExec('convert -density 150 [#INPUTF#] [#OUTPUTF#]', array('errFilePath' => $errFilePath));
        
        // Функцията, която ще се извика след приключване на обработката на файла
        $Script->callBack($params['callBack']);
        
        $params['errFilePath'] = $errFilePath;
        
        // Други необходими променливи
        $Script->params = serialize($params);
        $Script->fName = $name;
        $Script->outFilePath = $outFilePath;
        $Script->fh = $fRec->fileHnd;

        $Script->setCheckProgramsArr('convert');
        // Стартираме скрипта Aсинхронно
        if ($Script->run() === FALSE) {
            fileman_Indexes::createError($params);
        }
    }
	
	
	/**
     * Функция, която получава управлението след конвертирането на файл в JPG формат
     * 
     * @param object $script - Обект със стойности
     * @param output $fileHndArr - Масив, в който след обработката ще се запишат получените файлове
     * 
     * @return boolean TRUE - Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
     * и записа от таблицата fconv_Process
     * 
     * @access protected
     */
    static function afterConvertToJpg($script, &$fileHndArr=array())
    {
        // Вземаме всички файлове във временната директория
        $allFilesArr = scandir($script->tempDir);
        
        // Шаблон за намиране на името на файла
        $pattern = "/^" . preg_quote($script->fName, "/") . "\-(?'num'[0-9]+)\.jpg$" . "/i";
        
        $matchedFilesArr = array();
        
        // От всички открити файлове вземаме само тези, които съвпадат с търсенето
        foreach ((array)$allFilesArr as $file) {
            
            if (!preg_match($pattern, $file, $matches)) continue;
            $matchedFilesArr[$matches['num']] = $file;
        }
        
        ksort($matchedFilesArr);
        
        foreach ($matchedFilesArr as $file) {
            
            try {
                // Качваме файла в кофата и му вземаме манипулатора
                $fileHnd = fileman::absorb($script->tempDir . $file, 'fileIndex'); 
            } catch (core_exception_Expect $e) {
                continue;
            }
            
            if ($fileHnd) {
                $fileHndArr[$fileHnd] = $fileHnd;    
            }
        }
        
        $params = unserialize($script->params);
        
        if (count($fileHndArr)) {
            
            $params['content'] = $fileHndArr;
    
            // Обновяваме данните за запис във fileman_Indexes
            $savedId = fileman_Indexes::saveContent($params);
        } else {
        
            // Проверяваме дали е имало грешка при предишното конвертиране
            $error = fileman_Indexes::haveErrors($script->outFilePath, $params);
        }
        
        // Отключваме процеса
        core_Locks::release($params['lockId']);
        
        // Ако има грешка кода не се изпълнява
        if ($error) {
            
            return FALSE;
        }
        
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
        
        return array($fileHnd);
    }
}
