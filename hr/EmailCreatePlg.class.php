<?php


/**
 * Плъгин за изпращане на имейл
 *
 *
 * @category  bgerp
 * @package   hr
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class hr_EmailCreatePlg extends core_Plugin
{


    /**
     * Извиква се след описанието на модела
     */
    public function on_AfterDescription(&$mvc)
    {
        $mvc->load('doc_EmailCreatePlg');

        // Добавя интерфейс за генериране на имейл
        $mvc->interfaces = arr::make($mvc->interfaces);
        setIfNot($mvc->interfaces['email_DocumentIntf'], 'email_DocumentIntf');
        setIfNot($mvc->useOriginContragentData, true);
        setIfNot($mvc->getContragentDataFromLastDoc, false);
        setIfNot($mvc->canSendemail, 'user');
    }


    /**
     * Извиква се след изпълняването на екшън
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
        if (($rec->createdBy > 0) && $mvc->haveRightFor('sendEmail', $rec, $rec->createdBy)) {
            $cu = core_Users::getCurrent();

            $su = core_Users::sudo($rec->createdBy);

            $cData = $mvc->getContragentData($rec->id);

            // Подготовка на имейла
            $emailRec = (object) array(
                'body' => $mvc->getDefaultEmailBody($rec->id),
                'folderId' => $rec->folderId,
                'originId' => $rec->containerId,
                'threadId' => $rec->threadId,
                'state' => 'active',
                'email' => $cData->email,
                'subject' => $mvc->getDefaultEmailSubject($rec->id));

            email_Outgoings::setContragentDataToRec($cData, $emailRec);

            $pNotification = Mode::get('preventNotifications');

            Mode::set('preventNotifications', true);

            email_Outgoings::save($emailRec);

            email_Outgoings::logWrite('Създаване при одобрение', $emailRec->id, 360, $cu);
            cls::get('email_Outgoings')->invoke('AfterActivation', array(&$emailRec));
            email_Outgoings::logWrite('Активиране', $emailRec->id, 360, $cu);

            Mode::set('preventNotifications', $pNotification);

            if ($su) {
                core_Users::exitSudo();
            }
        }
    }


    /**
     * Връща контрагент данните
     *
     * @param core_Mvc $mvc
     * @param $data
     * @param $id
     */
    public function on_AfterGetContragentData($mvc, &$data, $id)
    {
        $personId = hr_Setup::get('EMAIL_TO_PERSON');
        if ($personId) {

            $data = crm_Persons::getContragentData($personId);
        }
    }


    /**
     * Връща тялото на имейла генериран от документа
     *
     * @param core_Mvc $mvc
     * @param null|string $res
     * @param int  $originId
     * @param bool $isForwarding
     *
     * @see email_DocumentIntf
     */
    public function on_AfterGetDefaultEmailBody($mvc, &$res, $originId, $isForwarding = false)
    {
        $handle = $mvc->getHandle($originId);
        $title = $mvc->singleTitle ? $mvc->singleTitle : $mvc->title;
        $title = mb_strtolower($title);

        $tpl = new ET(tr("Моля запознайте се с|* |{$title}|*") . ': #[#handle#]');
        $tpl->append($handle, 'handle');

        $res = $tpl->getContent();
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
        if ($action == 'sendemail' && isset($rec)) {
            if ($rec->state != 'active') {
                $requiredRoles = 'no_one';
            }

            if ($requiredRoles != 'no_one') {
                $defEmails = hr_Setup::get('EMAIL_TO_PERSON');
                if (!trim($defEmails)) {
                    $requiredRoles = 'no_one';
                }
            }

            // Ако има изпратен имейл в нишката, да не се показва бутона
            if ($requiredRoles != 'no_one') {
                if ($rec->threadId) {
                    if (email_Outgoings::fetch(array("#threadId = '[#1#]' && #state = 'closed'", $rec->threadId))) {
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }
    }
}
