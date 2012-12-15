<?php


/**
 * Клас 'incoming_CreateDocumentPlg'
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
class incoming_CreateDocumentPlg extends core_Plugin
{
    
    
    /**
     * Добавя бутон за създаване на входящ документ
     */
    function on_AfterPrepareSingleToolbar($mvc, &$res, &$data)
    {
        // id от fileman_Data
        $dataId = fileman_Files::fetchByFh($data->rec->fileHnd, 'dataId');

        // Проверяваме дали има вече създаден документ от файла
        if ($dRec = incoming_Documents::fetch("#dataId='{$dataId}'")) {
            
            if (incoming_Documents::haveRightFor('single', $dRec)) {
                
                // Добавяме бутон за разглеждане на single'a на документа, ако имаме права
                $viewDocUrl = toUrl(array('incoming_Documents', 'single', $dRec->id, 'ret_url' => TRUE), FALSE);
                $data->toolbar->addBtn('Документ', $viewDocUrl, 'id=btn-docIncomings,class=btn-docIncomings', 'order=50');
            }
        } else {
            
            // Добавяме бутон за създаване на входящ документ
            $createDocUrl = toUrl(array('incoming_Documents', 'add', 'fh' => $data->rec->fileHnd, 'ret_url' => TRUE), FALSE);
            $data->toolbar->addBtn('Документ', $createDocUrl, 'id=btn-New,class=btn-docIncomingsNew', 'order=50');
        }
    }
}