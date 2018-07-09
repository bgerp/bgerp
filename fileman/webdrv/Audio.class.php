<?php


/**
 * Драйвер за работа с аудио файлове.
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
class fileman_webdrv_Audio extends fileman_webdrv_Media
{
    /**
     * Кой таб да е избран по подразбиране
     *
     * @Override
     *
     * @see fileman_webdrv_Generic::$defaultTab
     */
    public static $defaultTab = 'audio';
    
    
    /**
     * Връща всички табове, които ги има за съответния файл
     *
     * @param object $fRec - Записите за файла
     *
     * @return array
     *
     * @Override
     *
     * @see fileman_webdrv_Generic::getTabs
     */
    public static function getTabs($fRec)
    {
        // Вземаме табовете от родителя
        $tabsArr = parent::getTabs($fRec);
        
        // Определяме широчината на видеото в зависимост от мода
        if (mode::is('screenMode', 'narrow')) {
            $width = 567;
        } else {
            $width = 868;
        }
        
        // Шаблона за видеото
        $audioTpl = mejs_Adapter::createAudio($fRec->fileHnd, array('width' => $width));
        
        // Таб за съдържанието
        $tabsArr['audio'] = (object)
            array(
                'title' => 'Аудио',
                'html' => "<div class='webdrvTabBody' style='white-space:pre-wrap;'><div class='webdrvFieldset'><div class='legend'>" . tr('Аудио') . "</div>{$audioTpl}</div></div>",
                'order' => 2,
                'tpl' => $audioTpl,
            );
        
        return $tabsArr;
    }
}
