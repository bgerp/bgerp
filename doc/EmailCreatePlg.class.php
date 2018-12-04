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
        setIfNot($mvc->canSendemail, 'powerUser');
        
        if ($mvc->haveRightFor('sendemail', $data->rec)) {
            $retUrl = array($mvc, 'single', $data->rec->id);
            $emailUrl = array('email_Outgoings', 'add', 'originId' => $data->rec->containerId, 'ret_url' => $retUrl);
            
            $data->toolbar->addBtn($emailButtonText,$emailUrl,
                        'ef_icon = img/16/email_edit.png,title=Изпращане на документа по имейл',
                        'onmouseup=saveSelectedTextToSession("' . $mvc->getHandle($data->rec->id) . '");'
            );
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'sendemail' && isset($rec)){
            if (!(($rec->state != 'draft' || $mvc->canEmailDraft) && ($rec->state != 'rejected') && email_Outgoings::haveRightFor('add'))) {
                $requiredRoles = 'no_one';
            } elseif (!email_Outgoings::haveRightFor('add', (object) array('originId' => $rec->containerId, 'threadId' => $rec->threadId))) {
                $requiredRoles = 'no_one';
            }
        }
    }
}
