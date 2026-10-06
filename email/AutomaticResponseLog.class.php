<?php


/**
 * Успешни автоматични отговори за ограничаването по правило и получател.
 * Отделен регистър, за да не се обхожда/индексира email_Outgoings за броенето.
 *
 * @category bgerp
 * @package email
 * @license GPL 3
 */
class email_AutomaticResponseLog extends core_Manager
{
    public $title = 'Изпратени автоматични отговори';

    public $canRead = 'debug';

    public $canWrite = 'no_one';

    public $listFields = 'ruleId,recipient,sentOn';


    public function description()
    {
        $this->FLD('ruleId', 'key(mvc=email_AutomaticResponse)', 'caption=Правило,mandatory');
        $this->FLD('recipient', 'varchar(255)', 'caption=Получател,mandatory');
        $this->FLD('sentOn', 'datetime', 'caption=Изпратено на,mandatory');
        $this->setDbIndex('ruleId,recipient,sentOn');
    }


    /**
     * Записва се само след потвърдено успешно изпращане, под заключването на правилото.
     */
    public static function record($ruleId, $recipient)
    {
        return self::save((object) array(
            'ruleId' => $ruleId,
            'recipient' => drdata_Emails::normalize($recipient),
            'sentOn' => dt::now(),
        ));
    }


    public static function isLimitReached($ruleId, $recipient, $maxCount, $period)
    {
        $query = self::getQuery();
        $query->where(array("#ruleId = [#1#] AND #recipient = '[#2#]' AND #sentOn >= '[#3#]'",
            $ruleId, drdata_Emails::normalize($recipient), dt::addSecs(-$period)));
        // Достатъчни са maxCount попадения, без преброяване на цялата история.
        $query->show('id');
        $query->limit($maxCount);

        return countR($query->fetchAll()) >= $maxCount;
    }
}
