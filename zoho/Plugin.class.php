<?php


/**
 * Клас 'zoho_Plugin'
 *
 * Плъгин за добавяне на бутона за преглед на документи в zoho.com
 * Разширения: pps,odt,ods,odp,sxw,sxc,sxi,wpd,rtf,csv,tsv
 *
 * @category  vendors
 * @package   zoho
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class zoho_Plugin extends core_Plugin
{
    
    
    /**
     * Добавя бутон за разглеждане на документи
     */
    public function on_AfterPrepareSingleToolbar($mvc, &$res, &$data)
    {
        if ($mvc->haveRightFor('single', $data->rec)) {
            try {
                $rec = $data->rec;
                
                //Разширението на файла
                $ext = fileman_Files::getExt($rec->name);
            
                if (in_array($ext, arr::make('pps,odt,ods,odp,sxw,sxc,sxi,wpd,rtf,csv,tsv'))) {
                    $url = 'https://viewer.zoho.com/docs/urlview.do?url=' . fileman_Download::getDownloadUrl($rec->fileHnd, 1);
                    $img = sbf('zoho/img/zoho.png');
                    
                    // Добавяме бутона
                    $data->toolbar->addBtn(
                        'Zoho',
                        $url,
                        'id=btn-zoho, class=linkWithIcon, checkPrivateHost, style=background-image: url(' . $img . ');',
                        array('target' => '_blank', 'order' => '30')
                    );
                }
            } catch (core_Exception_Expect $expect) {
            }
        }
    }
}
