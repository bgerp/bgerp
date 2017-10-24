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
     * Кой таб да е избран по подразбиране
     * @Override
     * @see fileman_webdrv_Generic::$defaultTab
     */
    static $defaultTab = 'text';


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
        
        if (self::canShowTab($fRec->fileHnd, 'text') || self::canShowTab($fRec->fileHnd, 'textOcr', TRUE, TRUE)) {
            // URL за показване на текстовата част на файловете
            $textPart = toUrl(array('fileman_webdrv_Office', 'text', $fRec->fileHnd), TRUE);
            
            // Таб за текстовата част
            $tabsArr['text'] = (object)
            array(
                    'title' => 'Текст',
                    'html'  => "<div class='webdrvTabBody'><div class='webdrvFieldset'><div class='legend'>" . tr("Текст") . "</div> <iframe src='{$textPart}' frameBorder='0' ALLOWTRANSPARENCY='true' class='webdrvIframe'> </iframe></div></div>",
                    'order' => 4,
            );
        }
        
        return $tabsArr;
    }
    
    
	/**
     * Извлича текстовата част от файла
     * 
     * @param object|string $fRec - Записите за файла
     * 
     * @return NULL|string
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
            $params['fileHnd'] = $fRec->fileHnd;
        }
        
        // Променливата, с която ще заключим процеса
        $params['lockId'] = self::getLockId('text', $dId);
        
        // Проверявама дали няма извлечена информация или не е заключен
        if (fileman_Indexes::isProcessStarted($params)) return ;
        
        // Заключваме процеса за определено време
        if (core_Locks::get($params['lockId'], 100, 0, FALSE)) {
            
            // Вземаме съдържанието на файла
            if ($params['fileHnd']) {
                $text = fileman_Files::getContent($params['fileHnd']);
            } else {
                $text = @file_get_contents($fRec);
            }
            
            $text = mb_strcut($text, 0, 1000000);
            $text = i18n_Charset::convertToUtf8($text);
            
            if ($params['fileHnd']) {
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
