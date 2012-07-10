<?php


/**
 * Клас 'fileman_viewer_GDocs'
 *
 * Плъгин за добавяне на бутона за преглед на документи в google docs
 * Разширения: pps,odt,ods,odp,sxw,sxc,sxi,wpd,rtf,csv,tsv
 *
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_viewer_Zoho extends core_Plugin
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
            
                if(in_array($ext,  arr::make('pps,odt,ods,odp,sxw,sxc,sxi,wpd,rtf,csv,tsv'))) {
                    $url = "https://viewer.zoho.com/docs/urlview.do?url=" . fileman_Download::getDownloadUrl($rec->fileHnd, 1);  
                    $img = sbf('fileman/img/zoho.png');
                    
                    // Добавяме бутона
                    $data->toolbar->addBtn('Zoho', $url, 
                    	"id='btn-zoho',class='btn-zoho', style=background-image: url(" . $img . ");", 
                        array('target'=>'_blank', 'order'=>'30')
                    ); 
                }
            } catch (core_Exception_Expect $expect) {}
        }
    }
}