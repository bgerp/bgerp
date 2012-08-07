<?php

/**
 * Драйвер за работа с .png файлове.
 * 
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Png //extends fileman_Image
{
    
    /**
     * 
     */
    static function getTabs($fRec)
    {
        $showUrl = toUrl(array('fileman_Image', 'show', $fRec->fileHnd), TRUE);
        
        $tabsArr['show']->title = 'Преглед';
        $tabsArr['show']->html = "<div> <iframe src='{$showUrl}'> </iframe> </div>";
        $tabsArr['show']->order = 1;
        
        $infoUrl = toUrl(array('fileman_Image', 'show', $fRec->fileHnd), TRUE);
        
        $tabsArr['info']->title = 'Информация';
        $tabsArr['info']->html = "<div> <iframe src='{$infoUrl}'> </iframe> </div>";
        $tabsArr['info']->order = 2;

        return $tabsArr;
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
}