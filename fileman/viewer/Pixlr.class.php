<?php


/**
 * Клас 'fileman_viewer_Pixlr'
 *
 * Плъгин за добавяне на бутона за преглед на документи в google docs
 * Разширения: jpg,jpeg,bmp,gif,png,psd,pxd
 *
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_viewer_Pixlr extends core_Plugin
{
    
    
    /**
     * Добавя бутон за разглеждане на документи
     */
    function on_AfterPrepareSingleToolbar($mvc, &$res, &$data)
    {
        if ($mvc->haveRightFor('single', $data->rec)) {
            try {
                $rec = $data->rec;
                
                //Разширението на файла
                $ext = fileman_Download::getExt($rec->name);
            
                if(in_array($ext,  arr::make('jpg,jpeg,bmp,gif,png,psd,pxd'))) { 
                    $url = "http://pixlr.com/editor/?s=c&image=" . fileman_Download::getDownloadUrl($rec->fileHnd, 1) . "&title=" . urlencode($rec->name) . "&target=" . '' . "&exit=" . '' . "";
                    $img = sbf('fileman/img/pixlr.png');
                    
                    // Добавяме бутона
                    $data->toolbar->addBtn('Преглед', $url, 
                    	"id='btn-review',class='btn-review', style=background-image: url(" . $img . ");", 
                        array('target'=>'_blank', 'order'=>'30')
                    ); 
                }
            } catch (core_Exception_Expect $expect) {}
        }
    }
}