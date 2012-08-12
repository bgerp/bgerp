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
		$tabsArr = array();
		$tabsArr['preview'] = new stdClass();
        $tabsArr['preview']->title = 'Преглед';
        $tabsArr['preview']->html = "<div> <iframe src='{$previewUrl}' class='webdrvIframe'> </iframe> </div>";
        $tabsArr['preview']->order = 1;
        
        // URL за показване на текстовата част на файловете
        $textPart = toUrl(array('fileman_webdrv_Pdf', 'text', $fRec->fileHnd), TRUE);
        
        // Таб за текстовата част
		$tabsArr['text'] = new stdClass();
        $tabsArr['text']->title = 'Текст';
        $tabsArr['text']->html = "<div> <iframe src='{$textPart}' class='webdrvIframe'> </iframe> </div>";
        $tabsArr['text']->order = 2;
        
        // URL за показване на информация за файла
        $infoUrl = toUrl(array('fileman_webdrv_Pdf', 'info', $fRec->fileHnd), TRUE);
        
        // Таб за информация
		$tabsArr['info'] = new stdClass();
        $tabsArr['info']->title = 'Информация';
        $tabsArr['info']->html = "<div> <iframe src='{$infoUrl}' class='webdrvIframe'> </iframe> </div>";
        $tabsArr['info']->order = 4;

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
        // Параметри необходими за конвертирането
        $params = array(
//            'callBack' => 'fileman_webdrv_Image::afterExtractText',
            'dataId' => $fRec->dataId,
//        	'asynch' => TRUE,
            'createdBy' => core_Users::getCurrent('id'),
            'type' => 'text',
        );
        
        // Променливата, с която ще заключим процеса
        $params['lockId'] = static::getLockId($params['type'], $fRec->dataId);
        
        // Проверявама дали няма извлечена информация или не е заключен
        if (static::isProcessStarted($params)) return ;
        
        // Заключваме процеса за определено време
        if (core_Locks::get($params['lockId'], 100, 0, FALSE)) {
            
            $script = new stdClass();
            $script->params = serialize($params);
    
            // Това е направено с цел да се запази логиката на работа на системата и възможност за раширение в бъдеще
            static::afterExtractText($script);    
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
        // Масив с параметрите
        $params = unserialize($script->params);
        
        // Текстовата част
        $text = '';
        
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
        if (static::isProcessStarted($params)) return ;
        
        // Заключваме процеса за определено време
        if (core_Locks::get($params['lockId'], 100, 0, FALSE)) {
            
            // Стартираме конвертирането към JPG
            static::startConvertingToJpg($fRec, $params);    
        } else {
            
            // Записваме грешката
            static::createErrorLog($params['dataId'], $params['type']);
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
        $outFilePath = $Script->tempDir . $name . '.jpg';
        
        // Задаваме placeHolder' ите за входния и изходния файл
        $Script->setFile('INPUTF', $fRec->fileHnd);
        $Script->setFile('OUTPUTF', $outFilePath);
        
        // Скрипта, който ще конвертира файла в JPG формат
        $Script->lineExec('convert -density 150 [#INPUTF#] [#OUTPUTF#]');
        
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
     * @param output $fileHndArr - Масив, в който след обработката ще се запишат получените файлове
     * 
     * @return boolean TRUE - Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
     * и записа от таблицата fconv_Process
     * 
     * @access protected
     */
    static function afterConvertToJpg($script, &$fileHndArr=array())
    {
        // Инстанция на класа
        $Fileman = cls::get('fileman_Files');
        
        // Качваме файла в кофата и му вземаме манипулатора
        $fileHnd = $Fileman->addNewFile($script->outFilePath, 'fileInfo'); 
        
        // Десериализираме нужните помощни данни
        $params = unserialize($script->params);
        
        // Ако се качи успешно записваме манипулатора в масив
        if ($fileHnd) {
            
            $fileHndArr[$fileHnd] = $fileHnd;
            
            // Сериализираме масива и обновяваме данните за записа в fileman_Info
            $rec = new stdClass();
            $rec->dataId = $params['dataId'];
            $rec->type = $params['type'];
            $rec->content = static::prepareContent($fileHndArr);
            $rec->createdBy = $params['createdBy'];
            
            $saveId = fileman_Indexes::save($rec);      
        }
            
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
}