<?php


/**
 * Драйвер за работа с .txt файлове.
 * 
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Txt extends fileman_webdrv_Office
{
    
    
    /*
     * @todo
     * @see https://github.com/dagwieers/unoconv/issues/86
       Има проблем при конвертирането на текстови файлове в pdf файлове, ако нямат разширение в оригиналния файл.
     */    
    
    
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
        // Масива с параметрите
        $params = unserialize($script->params);
        
        // Пътя до файла
//        expect($path = fileman_Files::fetchByFh($params['fileHnd'], 'path'));
        expect($path = fileman_Download::getDownloadUrl($params['fileHnd']));
        
        // Вземаме съдържанието на файла
        $text = file_get_contents($path);
        
        // Сериализираме масива и обновяваме данните за записа в fileman_Info
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
        } else {

            // 
            static::createErrorLog($params['dataId'], $params['type']);
        }
    }
}