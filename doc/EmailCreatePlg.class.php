<?php



/**
 * Клас 'doc_EmailCreatePlg'
 *
 * Плъгин за добавяне на бутона Имейл
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_EmailCreatePlg extends core_Plugin
{
    
    
    /**
     * Добавя бутон за създаване на имейл
     * @param stdClass $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareSingleToolbar($mvc, $res, $data)
    {
        //Ако сме задали текста на бутона в класа използваме него
        $emailButtonText = $mvc->emailButtonText;
        
        //В противен случай използваме текста по подразбиране
        setIfNot($emailButtonText, 'Имейл');
        
        if (($data->rec->state != 'draft') && ($data->rec->state != 'rejected') && ($mvc->haveRightFor('email'))) {
            $retUrl = array($mvc, 'single', $data->rec->id);
            
            // Бутон за отпечатване
            $data->toolbar->addBtn($emailButtonText, array(
                    'email_Outgoings',
                    'add',
                    'originId' => $data->rec->containerId,
                    'ret_url'=>$retUrl
                ),
                'class=btn-email-create');
        }
    }
}