<?php


/**
 * Драйвер за работа с .tif файлове.
 *
 * @category  vendors
 * @package   fileman
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class fileman_webdrv_Tif extends fileman_webdrv_ImageT
{
    /**
     * Връща всички табове, които ги има за съответния файл
     *
     * @param object $fRec - Записите за файла
     *
     * @return array
     *
     * @Override
     *
     * @see fileman_webdrv_Image::getTabs
     */
    public static function getTabs($fRec)
    {
        // Вземаме табовете от родителя
        $tabsArr = parent::getTabs($fRec);
        
        if (self::canShowTab($fRec->fileHnd, 'barcodes')) {
            $barcodeUrl = toUrl(array('fileman_webdrv_Tif', 'barcodes', $fRec->fileHnd));
            $tabsArr['barcodes'] = new stdClass();
            $tabsArr['barcodes']->title = 'Баркодове';
            $tabsArr['barcodes']->html = "<div class='webdrvTabBody'><div class='legend'>" . tr('Баркодове') . "</div> <div class='webdrvFieldset'><iframe src='{$barcodeUrl}' frameBorder='0' ALLOWTRANSPARENCY='true' class='webdrvIframe'> </iframe></div></div>";
            $tabsArr['barcodes']->order = 6;
        }
        
        return $tabsArr;
    }
    
    
    /**
     * Стартира извличането на информациите за файла
     *
     * @param object $fRec - Записите за файла
     *
     * @Override
     *
     * @see fileman_webdrv_Image::startProcessing
     */
    public static function startProcessing($fRec)
    {
        parent::startProcessing($fRec);
        static::getBarcodes($fRec);
        static::convertToJpg($fRec);
    }
    
    
    /**
     * Дали може да се извлича баркод
     *
     * @return bool
     */
    public static function canGetBarcodes()
    {
        return true;
    }
}
