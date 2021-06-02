<?php


/**
 * Драйвер за рутиране по първи външен имейл
 *
 * @category  bgerp
 * @package   payment
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Рутиране по първи външен имейл
 */
class email_drivers_RouteByFirstEmail extends core_BaseClass
{
    
    
    /**
     * Инрерфейси
     */
    public $interfaces = 'email_ServiceRulesIntf';


    /**
     * Добавяне на полета към наследниците
     */
    public static function addFields(&$mvc)
    {
    }


    /**
     *
     *
     * @param email_Mime  $mime
     * @param stdClass  $serviceRec
     *
     * @return string|null
     *
     * @see email_ServiceRulesIntf
     */
    public function process($mime, $serviceRec)
    {
        $resArr = array();

        // Приговяме "супа" от избрани полета на входящото писмо
        $soup = implode(
            ' ',
            array(
                $mime->getSubject(),
                $mime->textPart,
            )
        );

        // Извличаме всичко имейл адреси от супата ...
        $emails = type_Email::extractEmails($soup);

        // ... махаме нашите имейли
        $emails = $this->filterOurEmails($emails);

        // ... и махаме имейлите присъщи на услугата
        $emails = static::filterServiceEmails($emails, $serviceRec);

        // Ако нещо е останало ...
        if (countR($emails) > 0) {

            $fromName = $mime->getFromName();
            $fromEml = $mime->getFromEmail();

            // Ако първия имейл не се съдържа в изпращача
            if (strpos($fromEml, $emails[0]) === false) {

                // Задаваме първия имейл
                $resArr['preroute']['fromName'] = trim($emails[0]);
            } else {

                // Тримваме
                $resArr['preroute']['fromName'] = trim($fromName);
            }

            // Добавяме текста
            $resArr['preroute']['fromName'] .= ' чрез ' . $fromEml;

            // Задаваме първия имейл
            $resArr['preroute']['fromEml'] = $emails[0];
        }

        return $resArr;
    }


    /**
     * Премахва нашите имейли от зададен списък с имейли.
     *
     * "Нашите" имейли са адресите на вътрешните кутии от модела email_Inboxes.
     *
     * @param array $emails
     *
     * @return array
     */
    protected function filterOurEmails($emails)
    {
        $emails = array_filter($emails, function ($email) {
            $allInboxes = email_Inboxes::getAllInboxes();

            return !$allInboxes[strtolower(trim($email))];
        });

        return array_values($emails);
    }


    /**
     * @param $emails
     * @param $serviceRec
     *
     * @return array
     */
    protected static function filterServiceEmails($emails, $serviceRec)
    {
        $self = get_called_class();

        return array_filter($emails, function ($email) use ($serviceRec, $self) {

            return !$self::isServiceEmail($email, $serviceRec);
        });
    }


    /**
     * @param $email
     * @param $serviceRec
     * @return false
     */
    public static function isServiceEmail($email, $serviceRec)
    {
        return false; // @TODO
    }
}
