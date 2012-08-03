<?php


/**
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
        // Масив с всички табове
        $tabsArr = array();
        
        // URL за показване на преглед на файловете
        $previewUrl = toUrl(array('fileman_webdrv_Pdf', 'show', $fRec->fileHnd), TRUE);
        
        // Таб за преглед
        $tabsArr['preview']->title = 'Преглед';
        $tabsArr['preview']->html = "<div> <iframe src='{$previewUrl}' class='webdrvIframe'> </iframe> </div>";
        $tabsArr['preview']->order = 1;
        
        // URL за показване на текстовата част на файловете
        $textPart = toUrl(array('fileman_webdrv_Pdf', 'text', $fRec->fileHnd), TRUE);
        
        // Таб за текстовата част
        $tabsArr['text']->title = 'Текст';
        $tabsArr['text']->html = "<div> <iframe src='{$textPart}' class='webdrvIframe'> </iframe> </div>";
        $tabsArr['text']->order = 1;
        
        // URL за показване на информация за файла
        $infoUrl = toUrl(array('fileman_webdrv_Pdf', 'info', $fRec->fileHnd), TRUE);
        
        // Таб за информация
        $tabsArr['info']->title = 'Информация';
        $tabsArr['info']->html = "<div> <iframe src='{$infoUrl}' class='webdrvIframe'> </iframe> </div>";
        $tabsArr['info']->order = 2;

        return $tabsArr;
    }
    
    
    /**
     * Стартира извличането на информациите за файла
     * 
     * @param object $fRec - Записите за файла
     * 
     * @Override
     * @see fileman_webdrv_Generic::getTabs
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
        return ;
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
        // Вземаме съдъжанието на файла, който е генериран след обработката към .txt формат
        $text = file_get_contents($script->outFilePath);
        
        // Десериализираме нужните помощни данни
        $params = unserialize($script->params);

        // Записваме получения текс в модела
        $rec = new stdClass();
        $rec->dataId = $params['dataId'];
        $rec->type = $params['type'];
        $rec->content = serialize($text);
        $rec->createdBy = $params['createdBy'];
        fileman_Info1::save($rec);
        
        // Отключваме процеса
        core_Locks::release($params['lockId']);
        
        // Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
        // и записа от таблицата fconv_Process
        return TRUE;
    }   
    
    
	/**
     * Конвертиране в JPG формат
     * 
     * @param object $fRec - Записите за файла
     */
    static function convertToJpg($fRec)
    {
        return ;
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

        // Стартираме скрипта синхронно
        $Script->run($params['asynch']);
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
    static function afterConvertToJpg($script)
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
            $rec->content = serialize($fileHndArr);
            $rec->createdBy = $params['createdBy'];
            
            fileman_Info1::save($rec);    
        }
        
        // Отключваме процеса
        core_Locks::release($params['lockId']);
        
        // Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
        // и записа от таблицата fconv_Process
        return TRUE;
    }
}