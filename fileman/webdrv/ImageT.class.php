<?php


/**
 * Родителски клас на всички изображения, на които не може да им се генерира thumbnail
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
class fileman_webdrv_ImageT extends fileman_webdrv_Image
{
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
        static::convertToJpg($fRec);
    }
    
    
    /**
     * Връща информация за съдържанието на файла
     * Вика се от fileman_Indexes, за файлове, които нямат запис в модела за съответния тип
     *
     *
     * @param string $fileHnd
     * @param string $type
     *
     * @Override
     *
     * @see fileman_webdrv_Image::getInfoContentByFh
     */
    public static function getInfoContentByFh($fileHnd, $type)
    {
        return false;
    }
}
