<?php


/**
 * Родителски клас на всички офис документа. Съдържа методите по подразбиране.
 *
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Office extends fileman_webdrv_Generic
{
    

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
        $previewUrl = toUrl(array('fileman_webdrv_Pdf', 'preview', $fRec->fileHnd), TRUE);
        
        // Таб за преглед
		$tabsArr['preview'] = (object) 
			array(
				'title'   => 'Преглед',
				'html'    => "<div> <iframe src='{$previewUrl}' class='webdrvIframe'> </iframe> </div>",
				'preview' => 1,
			);
        
        // URL за показване на текстовата част на файловете
        $textPart = toUrl(array('fileman_webdrv_Pdf', 'text', $fRec->fileHnd), TRUE);
        
        // Таб за текстовата част
        $tabsArr['text'] = (object) 
			array(
				'title' => 'Текст',
				'html'  => "<div> <iframe src='{$textPart}' class='webdrvIframe'> </iframe> </div>",
				'order' => 2,
			);
        
        // URL за показване на информация за файла
        $infoUrl = toUrl(array('fileman_webdrv_Pdf', 'info', $fRec->fileHnd), TRUE);
        
        // Таб за информация
        $tabsArr['info'] = (object) 
			array(
				'title' => 'Информация',
				'html'  => "<div> <iframe src='{$infoUrl}' class='webdrvIframe'> </iframe> </div>",
				'order' => 4,
			);


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
        static::extractText($fRec);
        static::convertToJpg($fRec);
    }
    
    
    /**
     * Извлича текстовата част от файла
     * 
     * @param object $fRec - Записите за файла
     */
    static function extractText($fRec)
    {
        /*
         * @todo
         * @see https://github.com/dagwieers/unoconv/issues/73
           Версия на unoconv 0.3-6
           Има проблвем с конвертирането на файлове, които съдържат латиница.
		   Когато се конвертира от .odt или .doc към .txt формат вместо текста се изписват въпросителни.
		   При конвертиране към .pdf формат или някой друг всичко си работи коректно.
		   Временно решение може да е да се конвертира към .pdf и от него да се извлече текстовата част.
         */
        
        
        // Параметри необходими за конвертирането
        $params = array(
            'callBack' => 'fileman_webdrv_Office::afterExtractText',
            'dataId' => $fRec->dataId,
        	'asynch' => TRUE,
            'createdBy' => core_Users::getCurrent('id'),
            'type' => 'text',
        );
        
        // Променливата, с която ще заключим процеса
        $params['lockId'] = static::getLockId($params['type'], $fRec->dataId);

        // Проверявама дали няма извлечена информация или не е заключен
        if (static::isProcessStarted($params)) return ;
        
        // Заключваме процеса за определено време
        if (core_Locks::get($params['lockId'], 100, 0, FALSE)) {
            // Конфигурационните константи
            $conf = core_Packs::getConfig('docoffice');
            
            // Класа, който ще конвертира
            $ConvClass = $conf->OFFICE_CONVERTER_CLASS;
            
            // Стартираме конвертирането
            $ConvClass::convertDoc($fRec->fileHnd, 'txt', $params);    
        } else {
            
            // Записваме грешката
            static::createErrorLog($params['dataId'], $params['type']);
        }
    }
    
    
    /**
     * Извиква се след приключване на извличането на текстовата част
     * 
     * @param object $script - Данни необходими за извличането и записването на текста
     * 
     * @return TRUE - Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
     * и записа от таблицата fconv_Process
     * 
     * @access protected
     */
    static function afterExtractText($script)
    {
        // 
//        docoffice_Office::unlockOffice();
        
        // Вземаме съдъжанието на файла, който е генериран след обработката към .txt формат
        $text = file_get_contents($script->outFilePath);
        
        // Поправяме текста, ако има нужда
        $text = lang_Encoding::repairText($text);
        // TODO $text трябва да се направи проверка дали е енкоднат коректно
        
        // Десериализираме нужните помощни данни
        $params = unserialize($script->params);

        // Записваме получения текс в модела
        $rec = new stdClass();
        $rec->dataId = $params['dataId'];
        $rec->type = $params['type'];
        $rec->content = static::prepareContent($text);
        $rec->createdBy = $params['createdBy'];
        $saveId = fileman_Indexes::save($rec);
        
        // Отключваме процеса
        core_Locks::release($params['lockId']);
        
        if ($saveId) {

            // Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
            // и записа от таблицата fconv_Process
            return TRUE;
        } else {

            // 
            static::createErrorLog($params['dataId'], $params['type']);
        }
    }   
    
    
	/**
     * Конвертиране в JPG формат
     * 
     * @param object $fRec - Записите за файла
     */
    static function convertToJpg($fRec)
    {
        // Параметри необходими за конвертирането
        $params = array(
            'callBack' => 'fileman_webdrv_Office::afterConvertDocToPdf',
            'dataId' => $fRec->dataId,
        	'asynch' => TRUE,
            'createdBy' => core_Users::getCurrent('id'),
            'type' => 'docToPdf',
        );
        
        // Променливата, с която ще заключим процеса
        $params['lockId'] = static::getLockId($params['type'], $fRec->dataId);

        // Проверявама дали няма извлечена информация или не е заключен
        if (static::isProcessStarted($params)) return ;
        
        // Параметри за проверка дали е стартиран процеса на конвертиране на получения pdf документ към jpg
        $paramsJpg = $params;
        $paramsJpg['type'] = 'jpg';
        $paramsJpg['lockId'] = static::getLockId($paramsJpg['type'], $fRec->dataId);
        
        // Проверявама дали няма извлечена информация или не е заключен
        if (static::isProcessStarted($paramsJpg)) return ;

        // Заключваме процеса за определено време
        if (core_Locks::get($params['lockId'], 100, 0, FALSE)) {
            // Конфигурационните константи
            $conf = core_Packs::getConfig('docoffice');
            
            // Класа, който ще конвертира
            $ConvClass = $conf->OFFICE_CONVERTER_CLASS;
            
            // Стартираме конвертирането
            $ConvClass::convertDoc($fRec->fileHnd, 'pdf', $params);    
        } else {
            
            // Записваме грешката
            static::createErrorLog($params['dataId'], $params['type']);
        }
    }
    
    
    /**
     * Функция, която получава управлението след конвертирането на офис докуемнта към PDF
     * 
     * @param object $script - Обект със стойности
     * 
     * @return boolean TRUE - Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
     * и записа от таблицата fconv_Process
     * 
     * @access protected
     */
    static function afterConvertDocToPdf($script)
    {
        // Десериализираме параметрите
        $params = unserialize($script->params);
        
        // Отключваме предишния процес
        core_Locks::release($params['lockId']);
        
        // Параметри необходими за конвертирането
        $params['callBack'] = 'fileman_webdrv_Office::afterConvertToJpg';
        $params['type'] = 'jpg';
        $params['runSync'] = TRUE;
        
        // Променливата, с която ще заключим процеса
        $params['lockId'] = static::getLockId($params['type'], $params['dataId']);

        // Проверявама дали няма извлечена информация или не е заключен
        if (static::isProcessStarted($params)) return ;
        
        // Заключваме процеса за определно време
        if (core_Locks::get($params['lockId'], 100, 0, FALSE)) {
            
            // Стартираме конвертирането
            $started = static::convertPdfToJpg($script->outFilePath, $params);
    
            // Отключваме заключения процес за конвертиране от офис към pdf формат
            core_Locks::release($params['lockId']);
        }
        
        if ($started) {

            // Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
            // и записа от таблицата fconv_Process
            return TRUE;
        } else {

            // Записваме грешката в лога
            static::createErrorLog($params['dataId'], $params['type']);
        }
    }
    
    
	/**
     * Конвертиране на PDF документи към JPG с помощта на imageMagic
     * 
     * @param fileHandler $fileHnd - Манупулатора на файла, който ще се конвертира
     * @param array $params - Други параметри
     * 				$params['callBack'] - Класа и функцията, която ще се извикат след приключване на конвертирането
     * 				$params['asynch'] - Дали скрипта да се стартира асинхронно или не
     * 				и др.
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
        $Script->lineExec('convert -density 150 [#INPUTF#] [#OUTPUTF#]');
        
        // Функцията, която ще се извика след приключване на обработката на файла
        $Script->callBack($params['callBack']);
        
        // Други необходими променливи
        $Script->params = serialize($params);
        $Script->fName = $name;
        $Script->fh = $fileHnd;
        
        // Ако е подаден параметър за стартиране синхронно
        // Когато се геририра от офис документи PDF, и от полученич файл
        // се генерира JPG тогава трябва да се стартира синхронно
        // В другите случаи трябва да е асинхронно за да не чака потребителя
        $aSync = $params['runSync'] ? FALSE : TRUE;
        
        $Script->run($aSync);
        
        return TRUE;
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
        $files = scandir($script->tempDir);
       
        // Инстанция на класа
        $Fileman = cls::get('fileman_Files');
        
        // Брояч за файла
        $i=0;
        
        // Генерираме името на файла след конвертиране
        $fn = $script->fName . '-' .$i . '.jpg';

        // Докато има файл
        while (in_array($fn, $files)) {
            
            // Качваме файла в кофата и му вземаме манипулатора
            $fileHnd = $Fileman->addNewFile($script->tempDir . $fn, 'fileInfo'); 
            
            // Ако се качи успешно записваме манипулатора в масив
            if ($fileHnd) {
                $fileHndArr[$fileHnd] = $fileHnd;    
            }
            
            // Генерираме ново предположение за конвертирания файл, като добавяме единица
            $fn = $script->fName . '-' . ++$i . '.jpg';
        }
        
        // Ако има генерирани файлове, които са качени успешно
        if (count($fileHndArr)) {
            
            // Десериализираме нужните помощни данни
            $params = unserialize($script->params);
            
            // Сериализираме масива и обновяваме данните за записа в fileman_Info
            $rec = new stdClass();
            $rec->dataId = $params['dataId'];
            $rec->type = $params['type'];
            $rec->content = static::prepareContent($fileHndArr);
            $rec->createdBy = $params['createdBy'];
            
            $savedId = fileman_Indexes::save($rec);    
        }
        
        // Отключваме процеса
        core_Locks::release($params['lockId']);
        
        if ($savedId) {

            // Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
            // и записа от таблицата fconv_Process
            return TRUE;
        } else {

            // Записваме грешката в лога
            static::createErrorLog($params['dataId'], $params['type']);
        }
    }
}