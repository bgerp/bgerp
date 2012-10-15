<?php


/**
 * Драйвер за работа с .text файлове.
 * 
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Text extends fileman_webdrv_Generic
{


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
        
        // URL за показване на текстовата част на файловете
        $textPart = toUrl(array('fileman_webdrv_Office', 'text', $fRec->fileHnd), TRUE);
        
        // Таб за текстовата част
        $tabsArr['text'] = (object) 
			array(
				'title' => 'Текст',
				'html'  => "<div class='webdrvTabBody'><fieldset class='webdrvFieldset'><legend>Текст</legend> <iframe src='{$textPart}' frameBorder='0' ALLOWTRANSPARENCY='true' class='webdrvIframe'> </iframe></fieldset></div>",
				'order' => 2,
			);
        
        return $tabsArr;
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
//            'callBack' => 'fileman_webdrv_Txt::afterExtractText',
            'dataId' => $fRec->dataId,
//        	'asynch' => TRUE,
            'createdBy' => core_Users::getCurrent('id'),
            'type' => 'text',
            'fileHnd' => $fRec->fileHnd,
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

        // Проверяваме дали е имало грешка при предишното конвертиране
        if (static::haveErrors($params['fileHnd'], $params['type'], $params)) {
            
            // Отключваме предишния процес
            core_Locks::release($params['lockId']);
            
            return FALSE;
        }
        
        // Вземаме съдържанието на файла
        $text = fileman_Files::getContent($params['fileHnd']);
        
        // Проверяваме енкодинга
        $res = lang_Encoding::analyzeCharsets($text);
        $charset = arr::getMaxValueKey($res->rates);
            
        // Декодираме стринга към UTF-8, ако той не е в тази кодировка
        if($charset && ($charset != 'UTF-8')) {
            $text = iconv($charset, 'UTF-8//IGNORE', $text);
        }
        
        // Сериализираме масива и обновяваме данните за записа в fileman_Indexes
        $rec = new stdClass();
        $rec->dataId = $params['dataId'];
        $rec->type = $params['type'];
        $rec->content = static::prepareContent($text);
        $rec->createdBy = $params['createdBy'];
        
        // Записваме данните
        $saveId = fileman_Indexes::save($rec);    
        
        // Отключваме процеса
        core_Locks::release($params['lockId']);
        
        if ($saveId) {

            // Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
            // и записа от таблицата fconv_Process
            return TRUE;
        }
    }
}