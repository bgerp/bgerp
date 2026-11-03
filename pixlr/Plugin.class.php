<?php


/**
 * Клас 'pixlr_Plugin'
 *
 * Плъгин за добавяне на бутона за преглед на документи в pixlr.com
 * Разширения: jpg,jpeg,bmp,gif,png,psd,pxd
 *
 * @category  vendors
 * @package   pixlr
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pixlr_Plugin extends core_Plugin
{
    /**
     * Добавя бутон за разглеждане на документи
     */
    public function on_AfterPrepareSingleToolbar($mvc, &$res, &$data)
    {
        $rec = $data->rec ?? null;
        if (is_object($rec) && $mvc->haveRightFor('single', $rec)) {
            try {
                //Разширението на файла
                $ext = fileman_Files::getExt($rec->name ?? '');
                
                if (in_array($ext, arr::make('jpg,jpeg,bmp,gif,png,psd,pxd'))) {
                    $downloadUrl = fileman_Download::getDownloadUrl($rec->fileHnd ?? '', 1);
                    if (!$downloadUrl) {
                        return;
                    }
                    $url = 'https://pixlr.com/editor/?' . http_build_query(array(
                        'file' => $downloadUrl,
                        'name' => $rec->name ?? '',
                    ), '', '&', PHP_QUERY_RFC3986);
                    
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
