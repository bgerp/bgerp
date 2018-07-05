<?php


/**
 * Драйвер за работа с .tnef файлове.
 *
 * @category  bgerp
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Tnef extends fileman_webdrv_Generic
{
    
    
    /**
     * Кой таб да е избран по подразбиране
     * @Override
     * @see fileman_webdrv_Generic::$defaultTab
     */
    public static $defaultTab = 'content';
    
    
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
        
        // Вземаме съдържанието
        $contentStr = static::getFileContent($fRec);
        
        // Таб за съдържанието
        $tabsArr['content'] = (object)
            array(
                'title' => 'Съдържание',
                'html' => "<div class='webdrvTabBody' style='white-space:pre-wrap;'><div class='webdrvFieldset'><div class='legend'>" . tr('Съдържание') . "</div>{$contentStr}</div></div>",
                'order' => 7,
                'tpl' => $contentStr,
            );
        
        return $tabsArr;
    }
    
    
    /**
     * Връща съдържанието на архива в дървовидна структура
     *
     * @param object $fRec - Записите за файла
     */
    public static function getFileContent($fRec)
    {
        // Това е необходимо за да сработят плъгините, които са закачени към fileman_webdrv_Tnef
        $inst = cls::get('fileman_webdrv_Tnef');
        $filesArr = $inst->getFiles($fRec->fileHnd);
        
        $content = '';
        
        foreach ($filesArr as $fileHnd) {
            $link = fileman_Files::getLink($fileHnd);
            
            if (!$link) {
                continue;
            }
            
            $content .= ($content) ? '<br>' . $link : $link;
        }
        
        return $content;
    }
    
    
    /**
     *
     *
     * @param string $fileHnd
     */
    protected static function getFiles_($fileHnd)
    {
        return array();
    }
}
