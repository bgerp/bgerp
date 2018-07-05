<?php


/**
 * Клас 'pixlr_Plugin'
 *
 * Плъгин за добавяне на бутона за преглед на документи в pixlr.com
 * Разширения: jpg,jpeg,bmp,gif,png,psd,pxd
 *
 * @category  vendors
 * @package   pixlr
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class pixlr_Plugin extends core_Plugin
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
            
                if (in_array($ext, arr::make('jpg,jpeg,bmp,gif,png,psd,pxd'))) {
                    $url = 'http://pixlr.com/editor/?s=c&image=' . fileman_Download::getDownloadUrl($rec->fileHnd, 1) . '&title=' . urlencode($rec->name) . '&target=' . '' . '&exit=' . '' . '';
                     
                    // Добавяме бутона
                    $data->toolbar->addBtn(
                        'Pixlr',
                        $url,
                        "id='btn-pixlr', checkPrivateHost, ef_icon=pixlr/img/pixlr.png",
                        array('target' => '_blank', 'order' => '30')
                    );
                }
            } catch (core_Exception_Expect $expect) {
            }
        }
    }
}
