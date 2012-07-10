<?php


/**
 * Клас 'doc_IncomingCreatePlg'
 *
 * Плъгин за добавяне на бутона за създаване на входящ документ
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_IncomingCreatePlg extends core_Plugin
{
    
    
    /**
     * Добавя бутон за създаване на входящ документ
     */
    function on_AfterPrepareSingleToolbar($mvc, &$res, &$data)
    {
        // Ако имаме права за сингъл на файла
        if (fileman_Files::haveRightFor('single', $data->rec)) {

            // id от fileman_Data
            $dataId = fileman_Files::fetchByFh($data->rec->fileHnd, 'dataId');

            // Проверяваме дали има вече създаден документ от файла
            if ($dRec = doc_Incomings::fetch("#dataId='{$dataId}'")) {
                
                if (doc_Incomings::haveRightFor('single', $dRec)) {
                    
                    // Добавяме бутон за разглеждане на single'a на документа, ако имаме права
                    $viewDocUrl = toUrl(array('doc_Incomings', 'single', $dRec->id, 'ret_url' => TRUE), FALSE);
                    $data->toolbar->addBtn('Документ', $viewDocUrl, 'id=btn-docIncomings,class=btn-docIncomings', 'order=50');
                }
            } else {
                
                // Добавяме бутон за създаване на входящ документ
                $createDocUrl = toUrl(array('doc_Incomings', 'add', 'fh' => $data->rec->fileHnd, 'ret_url' => TRUE), FALSE);
                $data->toolbar->addBtn('Документ', $createDocUrl, 'id=btn-New,class=btn-docIncomingsNew', 'order=50');
            }
        }
    }
}