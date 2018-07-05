<?php


/**
 * Драйвер за работа с видео файлове.
 *
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Video extends fileman_webdrv_Media
{
    
    
    /**
     * Кой таб да е избран по подразбиране
     * @Override
     * @see fileman_webdrv_Generic::$defaultTab
     */
    public static $defaultTab = 'video';
    
    
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
    public static function getTabs($fRec)
    {
        // Вземаме табовете от родителя
        $tabsArr = parent::getTabs($fRec);
        
        // Определяме широчината на видеото в зависимост от мода
        if (mode::is('screenMode', 'narrow')) {
            $width = 567;
            $height = 400;
        } else {
            $width = 868;
            $height = 500;
        }
        
        // Шаблона за видеото
        $videoTpl = mejs_Adapter::createVideo($fRec->fileHnd, array('width' => $width, 'height' => $height));
        
        // Таб за съдържанието
        $tabsArr['video'] = (object)
            array(
                'title' => 'Видео',
                'html' => "<div class='webdrvTabBody' style='white-space:pre-wrap;'><div class='webdrvFieldset'><div class='legend'>" . tr('Видео') . "</div>{$videoTpl}</div></div>",
                'order' => 2,
                'tpl' => $videoTpl,
            );
            
        return $tabsArr;
    }
}
