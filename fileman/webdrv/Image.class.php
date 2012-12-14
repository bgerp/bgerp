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
        
        // Вземаме превюто на файла
        $preview = static::getThumbPrev($fRec);
        
        // Таб за преглед
		$tabsArr['preview'] = new stdClass();
        $tabsArr['preview']->title = 'Преглед';
        $tabsArr['preview']->html = "<div class='webdrvTabBody'><fieldset class='webdrvFieldset'><legend>Преглед</legend> {$preview} </fieldset></div>";
        $tabsArr['preview']->order = 2;
        
        // URL за показване на текстовата част на файловете
        $textPart = toUrl(array('fileman_webdrv_Pdf', 'text', $fRec->fileHnd), TRUE);
        
        // Таб за текстовата част
		$tabsArr['text'] = new stdClass();
        $tabsArr['text']->title = 'Текст';
        $tabsArr['text']->html = "<div class='webdrvTabBody'><fieldset class='webdrvFieldset'><legend>Текст</legend><iframe src='{$textPart}' frameBorder='0' ALLOWTRANSPARENCY='true' class='webdrvIframe'></fieldset></iframe></div>";
        $tabsArr['text']->order = 4;
        
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
            'callBack' => 'fileman_webdrv_Image::afterExtractText',
            'dataId' => $fRec->dataId,
        	'asynch' => TRUE,
            'createdBy' => core_Users::getCurrent('id'),
            'type' => 'text',
        );
        
        // Променливата, с която ще заключим процеса
        $params['lockId'] = static::getLockId($params['type'], $fRec->dataId);
        
        // Проверявама дали няма извлечена информация или не е заключен
        if (fileman_Indexes::isProcessStarted($params)) return ;
        
        // Заключваме процеса за определено време
        if (core_Locks::get($params['lockId'], 100, 0, FALSE)) {
            
            $script = new stdClass();
            $script->params = serialize($params);
    
            // Това е направено с цел да се запази логиката на работа на системата и възможност за раширение в бъдеще
            static::afterExtractText($script);    
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
        // Десериализираме нужните помощни данни
        $params = unserialize($script->params);
        
//        // Проверяваме дали е имало грешка при предишното конвертиране
//        if (fileman_Indexes::haveErrors($script->outFilePath, $params['type'], $params)) {
//            
//            // Отключваме процеса
//            core_Locks::release($params['lockId']);
//            
//            return FALSE;
//        }
        
        
        // Текстовата част
        $params['content'] = '';

        // Обновяваме данните за запис във fileman_Indexes
        $savedId = fileman_Indexes::saveContent($params);
        
        // Отключваме процеса
        core_Locks::release($params['lockId']);
        
        if ($savedId) {

            // Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
            // и записа от таблицата fconv_Process
            return TRUE;
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
        
        // Качваме файла в кофата и му вземаме манипулатора
        $fileHnd = $Fileman->addNewFile($script->outFilePath, 'fileIndex'); 
        
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
     * Връща шаблон с превюто на файла
     * 
     * @param object $fRec - Записите за файла
     * 
     * @return core_Et - Шаблон с превюто на файла
     */
    static function getThumbPrev($fRec)
    {
        //Вземема конфигурационните константи
        $conf = core_Packs::getConfig('fileman');
        
        // В зависимост от широчината на екрана вземаме размерите на thumbnail изображението
        if (mode::is('screenMode', 'narrow')) {
            $thumbWidth = $conf->FILEMAN_PREVIEW_WIDTH_NARROW;
            $thumbHeight = $conf->FILEMAN_PREVIEW_HEIGHT_NARROW;
        } else {
            $thumbWidth = $conf->FILEMAN_PREVIEW_WIDTH;
            $thumbHeight = $conf->FILEMAN_PREVIEW_HEIGHT;
        }
        
        //Размера на thumbnail изображението
        $size = array($thumbWidth, $thumbHeight);
        
        // Атрибути на thumbnail изображението
        $attr = array('baseName' => 'Preview', 'isAbsolute' => FALSE, 'qt' => '', 'style' => 'margin: 5px auto; display: block;');
        
        // Background' а на preview' то
        $bgImg = sbf('fileman/img/Preview_background.jpg');
        
        // Създаваме шаблон за preview на изображението
        $preview = new ET("<div style='background-image:url(" . $bgImg . "); padding: 5px 0; min-height: 590px;'><div style='margin: 0 auto; display:table;'>[#THUMB_IMAGE#]</div></div>");
        
        //Създаваме тумбнаил с параметрите
        $thumbnailImg = thumbnail_Thumbnail::getImg($fRec->fileHnd, $size, $attr);
        
        // Ако е обект и има съобщение за грешка
        if (!$thumbnailImg) {
            
            $thumbnailImg = 'Не може да се генерира изображението.';      
        }
        
        // Добавяме към preview' то генерираното изображение
        $preview->append($thumbnailImg, 'THUMB_IMAGE');
        
        return $preview;
    }
}