<?php


/**
 * Драйвер за работа с PostScript файлове.
 *
 * @category  vendors
 * @package   fileman
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class fileman_webdrv_PostScript extends fileman_webdrv_Office
{
    /**
     * Конвертиране в JPG формат
     *
     * @param object $fRec - Записите за файла
     *
     * @Override
     *
     * @see fileman_webdrv_Office::convertToJpg
     *
     * @access protected
     */
    public static function convertToJpg($fRec)
    {
        // Параметри необходими за конвертирането
        $params = array(
            'callBack' => 'fileman_webdrv_PostScript::afterConvertToJpg',
            'dataId' => $fRec->dataId,
            'asynch' => true,
            'createdBy' => core_Users::getCurrent('id'),
            'type' => 'jpg',
        );
        
        // Променливата, с която ще заключим процеса
        $params['lockId'] = static::getLockId($params['type'], $params['dataId']);
        
        // Проверявама дали няма извлечена информация или не е заключен
        if (fileman_Indexes::isProcessStarted($params)) {
            
            return ;
        }
        
        static::convertPdfToJpg($fRec->fileHnd, $params);
    }
}
