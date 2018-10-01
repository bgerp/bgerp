<?php


/**
 * Клас 'vtotal_Plugin'
 *
 * Плъгин за добавяне на бутона за ръчно сканирване на файл към VirusTotal(vtotal)
 * Разширения: Всички
 *
 * @category  vendors
 * @package   vtotal
 *
 * @author    Christian Serafimov <kristian.plamenov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     Ком
 */
class vtotal_Plugin extends core_Plugin
{
    /**
     * Добавя бутон за ръчно сканирване на файл
     */
    public function on_AfterPrepareSingleToolbar($mvc, &$res, &$data)
    {
        if ($mvc->haveRightFor('single', $data->rec) && haveRole('admin, debug')) {
            try {
                $rec = $data->rec;
                
                $vtotalFilemanDataObject = fileman_Data::fetch($rec->dataId);
                $url = array('vtotal_Checks', 'manualCheck', 'md5' => $vtotalFilemanDataObject->md5, 'fileHnd' => $rec->fileHnd);
                
                // Добавяме бутона
                $data->toolbar->addBtn(
                    'VT Scan',
                    $url,
                    "id='btn-vtotal', ef_icon=/img/16/shield-icon.png",
                    array('target' => '_blank', 'order' => '90', 'title' => 'Добавяне към проверка')
                );
            } catch (core_Exception_Expect $expect) {
            }
        }
    }
}
