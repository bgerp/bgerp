<?php


/**
 * Клас 'doc_EmailCreatePlg'
 *
 * Плъгин за добавяне на бутона Имейл
 *
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doc_EmailCreatePlg extends core_Plugin
{
    /**
     * Извиква се след описанието на модела
     */
    public function on_AfterDescription(&$mvc)
    {
        // Добавя интерфейс за генериране на имейл
        $mvc->interfaces = arr::make($mvc->interfaces);
        setIfNot($mvc->interfaces['email_DocumentIntf'], 'email_DocumentIntf');
    }
    
    
    /**
     * Добавя бутон за създаване на имейл
     *
     * @param stdClass $mvc
     * @param stdClass $data
     */
    public function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        $emailButtonText = $mvc->emailButtonText;
        
        setIfNot($emailButtonText, 'Имейл');
        
        setIfNot($mvc->canEmailDraft, false);
        
        if (($data->rec->state != 'draft' || $mvc->canEmailDraft) && ($data->rec->state != 'rejected') && email_Outgoings::haveRightFor('add')) {
            $retUrl = array($mvc, 'single', $data->rec->id);
            
            if (email_Outgoings::haveRightFor('add', (object) array('originId' => $data->rec->containerId, 'threadId' => $data->rec->threadId))) {
                $data->toolbar->addBtn(
                    $emailButtonText,
                    array(
                        'email_Outgoings',
                        'add',
                        'originId' => $data->rec->containerId,
                        'ret_url' => $retUrl
                    ),
                        'ef_icon = img/16/email_edit.png,title=Изпращане на документа по имейл',
                    'onmouseup=saveSelectedTextToSession("' . $mvc->getHandle($data->rec->id) . '");'
                );
            }
        }
    }
}
