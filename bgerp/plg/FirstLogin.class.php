<?php


/**
 * Плъгин за прихващане на първото логване на потребител в системата.
 *
 * @category  bgerp
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgerp_plg_FirstLogin extends core_Plugin
{
    /**
     * Прихващаме всяко логване в системата.
     */
    public function on_AfterLogin($mvc, $userRec, $inputs, $refresh)
    {
        // Ако не се логва, а се рефрешва потребителя
        if ($refresh) {
            return;
        }

        if (!$userRec->lastLoginTime && haveRole('admin') && 1 == core_Users::count('1=1')) {
            //Зарежда данните за "Моята фирма"
            crm_Companies::loadData();

            email_Accounts::loadData();

            $mvc->logRead('Зареждане на данни за фирмите и акаунтите след първото логване');

            // Добавяме еднократно изивкване на функцията по крон
            $callOn = dt::addSecs(120);
            $currUserId = core_Users::getCurrent();
            core_CallOnTime::setOnce('bgerp_plg_FirstLogin', 'welcomeNote', $userRec->id, $callOn);

            $callOn = dt::addSecs(60);
            core_CallOnTime::setOnce('bgerp_plg_FirstLogin', 'retrieveCurrencyRates', null, $callOn);
        }
    }

    /**
     * Извиква се от core_CallOnTime.
     *
     * @see core_CallOnTime
     */
    public static function callback_retrieveCurrencyRates()
    {
        $Rates = cls::get('currency_CurrencyRates');
        $Rates->retrieveCurrenciesFromEcb();
    }

    /**
     * Извиква се от core_CallOnTime.
     *
     * @see core_CallOnTime
     *
     * @param int $userId
     */
    public static function callback_welcomeNote($userId)
    {
        // Очакваме да е подаден валиден потребител
        if ($userId <= 0) {
            return;
        }

        // Форсираме правата на потребителя
        core_Users::sudo($userId);

        // Езика на потребител
        $lg = core_Lg::getCurrent();

        // Оптиваме се да определим пътя до файла в зависимост от езика
        $filePath = '';
        $filePathBegin = '/bgerp/tpl/WelcomeNote';
        $filePathEnd = '.txt';
        if ($lg) {
            $lgU = strtoupper($lg);

            // Ако има файл за текущия език да се използва той
            $pFilePath = $filePathBegin.'_'.$lgU.$filePathEnd;
            if (getFullPath($pFilePath)) {
                $filePath = $pFilePath;
            } else {
                // Ако текущия език не е bg, опитваме да използваме английския вариант
                if (('bg' != $lg) && ('en' != $lg)) {
                    $pFilePath = $filePathBegin.'_EN'.$filePathEnd;
                    if (getFullPath($pFilePath)) {
                        $filePath = $pFilePath;
                    }
                }
            }
        }

        // Ако не сме определили пътя до файла, използваме по-подразбиране
        if (!$filePath) {
            $filePath = $filePathBegin.$filePathEnd;
        }

        // Превеждаме заглавието
        $subject = tr('Първи стъпки с bgERP');

        // Спираме
        core_Users::exitSudo();

        // Папката на потребителя
        $folderId = doc_Folders::getDefaultFolder($userId);

        // Вземаме съдържанието на файла
        $tpl = getTplFromFile($filePath);
        expect($tpl);

        // Активираме бележка със съдържание на файла
        $nRec = new stdClass();
        $nRec->subject = $subject;
        $nRec->body = $tpl->getContent();
        $nRec->folderId = $folderId;
        $nRec->sharedUsers = type_UserList::fromArray(array($userId => $userId));
        $nRec->version = 0;
        $nRec->subVersion = 1;
        $nRec->state = 'active';

        doc_Notes::save($nRec);
    }
}
