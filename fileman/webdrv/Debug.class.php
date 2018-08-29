<?php


/**
 * Драйвер за работа с .debug файлове.
 *
 * @category  bgerp
 * @package   fileman
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class fileman_webdrv_Debug extends fileman_webdrv_Txt
{
    /**
     * Кой таб да е избран по подразбиране
     *
     * @Override
     *
     * @see fileman_webdrv_Generic::$defaultTab
     */
    public static $defaultTab = 'preview';
    
    
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
        
        // URL за показване на преглед на файловете
        $previewUrl = toUrl(array(get_called_class(), 'preview', $fRec->fileHnd), true);
        
        // Таб за преглед
        $tabsArr['preview'] = (object)
        array(
            'title' => 'Преглед',
            'html' => "<div class='webdrvTabBody'><div class='webdrvFieldset'><div class='legend'>" . tr('Преглед') . "</div> <iframe src='{$previewUrl}' frameBorder='0' ALLOWTRANSPARENCY='true' class='webdrvIframe'> </iframe></div></div>",
            'order' => 2,
        );
        
        return $tabsArr;
    }
    
    
    /**
     * Екшън за показване превю
     */
    public function act_Preview()
    {
        // Очакваме да има права за виждане
        $this->requireRightFor('view');
        
        // Манипулатора на файла
        $fileHnd = Request::get('id');
        
        if (!$fileHnd) {
            $fileHnd = Request::get('fileHnd');
        }
        
        expect($fileHnd);
        
        // Вземаме записа за файла
        $fRec = fileman_Files::fetchByFh($fileHnd);
        
        expect($fRec);
        
        // Очакваме да има права за разглеждане на записа
        $this->requireRightFor('view', $fRec);
        
        $path = fileman::extract($fileHnd);
        
        $res = cls::get('log_Debug')->getDebugFileInfo($path);
        
        fileman::deleteTempPath($path);
        
        echo $res;
        
        shutdown();
    }
}
