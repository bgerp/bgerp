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
        if ($mvc->haveRightFor('single', $data->rec)) {
            
            // Добавяме бутон за създаване на входящ документ
            $createDocUrl = toUrl(array('doc_Incomings', 'add', 'fh' => $data->rec->fileHnd, 'ret_url' => TRUE), FALSE);
            $data->toolbar->addBtn('Документ', $createDocUrl, 'id=btn-docIncomings,class=btn-docIncomings', 'order=50');
        }
    }
}