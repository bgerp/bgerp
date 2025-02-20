<?php


/**
 * Драйвер за оп
 *
 * @category  bgerp
 * @package   email
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Правила при изпращане на имейли » Пренасочване към друг имейл
 */
class email_drivers_RouteOutgoingEmails extends email_drivers_OutgoingEmails
{

    /**
     * Поле, което се обновява
     */
    protected $updateField = 'redirection=redirection';

    /**
     * Добавяне на полета към наследниците
     */
    public static function addFields(&$mvc)
    {
        $mvc->FLD('redirection', 'email', 'caption=Пренасочване, after=email');
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param tremol_FiscPrinterDriverWeb $Driver
     * @param peripheral_Devices     $Embedder
     * @param core_Form         $form
     * @param stdClass          $data
     */
    protected static function on_AfterPrepareEditForm($Driver, embed_Manager $Embedder, &$data)
    {
        if ($docId = $data->form->rec->docId) {
            $docObj = doc_Containers::getDocument($docId);
            if ($docObj->instance instanceof email_Incomings) {
                $eRec = $docObj->fetch();
                if (email_Incomings::haveRightFor('single', $eRec)) {
                    // Вземаме имейлите от копи и до
                    email_Incomings::calcAllToAndCc($eRec);
                    $allEmailsArr = array_merge($eRec->AllTo, $eRec->AllCc);
                    $emailArr = array();
                    foreach ($allEmailsArr as $allTo) {
                        $email = $allTo['address'];
                        $email = trim($email);
                        $emailArr[$email] = $email;
                    }

                    // Вземаме имейлите от текстовата част
                    $emailsFromText = email_Mime::getAllEmailsFromStr($eRec->textPart, true);
                    $emailsFromTextArr = type_Emails::toArray($emailsFromText);
                    $emailsFromTextArr = arr::make($emailsFromTextArr, true);
                    $emailArr = array_merge($emailArr, $emailsFromTextArr);

                    unset($emailArr[$eRec->fromEml]);

                    if (!empty($emailArr)) {
                        $emailArr = email_Inboxes::removeOurEmails($emailArr);
                    }

                    if (!empty($emailArr)) {
                        $data->form->setDefault('redirection', key($emailArr));
                        array_unshift($emailArr , '');
                        $emailArr = arr::make($emailArr, true);
                        $data->form->setSuggestions('redirection', $emailArr);
                    }
                }
            }
        }
    }
}
