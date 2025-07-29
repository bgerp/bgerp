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
 * @title     Правила при изпращане на имейли » Блокиране на изпращане на циркулярен имейл
 */
class email_drivers_BlockBlastEmails extends email_drivers_OutgoingEmails
{

    /**
     * Поле, което се обновява
     */
    protected $updateField = 'fState=state';


    /**
     * Добавяне на полета към наследниците
     */
    public static function addFields(&$mvc)
    {
        $mvc->FLD('fState', 'enum(blocked=Блокирано, ok=OK, error=Грешка)', 'caption=Състояние, after=email, mandatory, silent');
    }
}
