<?php


/**
 * Драйвер за работа с .Gvdot файлове.
 * 
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Gvdot extends fileman_webdrv_ImageT
{
    
    
    /**
     * Извлича текстовата част от файла
     * 
     * @param object $fRec - Записите за файла
     */
    static function extractText($fRec)
    {
        // Параметри необходими за конвертирането
        $params = array(
            'callBack' => 'fileman_webdrv_Gvdot::afterExtractText',
            'dataId' => $fRec->dataId,
        	'asynch' => TRUE,
            'createdBy' => core_Users::getCurrent('id'),
            'type' => 'text',
            'fileHnd' => $fRec->fileHnd,
        );
        
        // Променливата, с която ще заключим процеса
        $params['lockId'] = static::getLockId($params['type'], $fRec->dataId);
        
        // Проверявама дали няма извлечена информация или не е заключен
        if (fileman_Indexes::isProcessStarted($params)) return ;
        
        // Заключваме процеса за определено време
        if (core_Locks::get($params['lockId'], 100, 0, FALSE)) {
            
            $script = new stdClass();
            $script->params = serialize($params);
            
            try {
                // Това е направено с цел да се запази логиката на работа на системата и възможност за раширение в бъдеще
                static::afterExtractText($script);   
            } catch (core_exception_Expect $e) {
                
                return ;
            }
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
        
        // Ако няма подаден манипулатор на файла
        if (!($fh = $params['fileHnd'])) {
            
            // Вземаме манипулатора
            $fh = fileman::idToFh($params['dataId']);
        }
        
        // Екстрактваме файла и вземаме пътя
        $filePath = fileman::extract($fh);
        
        // Текстовата част
        $params['content'] = file_get_contents($filePath);
        
        // Изтриваме временния файл
        fileman::deleteTempPath($filePath);
        
        // Обновяваме данните за запис във fileman_Indexes
        $savedId = fileman_Indexes::saveContent($params);
        
        // Отключваме процеса
        core_Locks::release($params['lockId']);
        
        // Ако е записан успешно
        if ($savedId) {

            // Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
            // и записа от таблицата fconv_Process
            return TRUE;
        }
    }
}
