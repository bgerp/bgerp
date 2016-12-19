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
            
            if (is_object($fRec)) {
                $filePath = fileman::extract($fRec->fileHnd);
            } else {
                $filePath = $fRec;
            }
            
            $text = @file_get_contents($filePath);
            
            if (is_object($fRec)) {
                
                fileman::deleteTempPath($filePath);
                
                // Обновяваме данните за запис във fileman_Indexes
                $params['content'] = $text;
                fileman_Indexes::saveContent($params);
            }
        
            // Отключваме процеса
            core_Locks::release($params['lockId']);
        
            return $text;
        }
        
    }
}
