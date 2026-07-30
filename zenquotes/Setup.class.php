<?php

/**
 * API ключ за ZenQuotes
 */
defIfNot('ZENQUOTES_API_KEY', ''); // сложи тук твоя API ключ

/**
 * class ZenQuotes_Setup
 *
 * Инсталиране/Деинсталиране на пакета за ZenQuotes
 *
 * @category  bgerp
 * @package   zenquotes
 *
 * @author    David Dimitriev
 * @license   GPL 3
 */
class zenquotes_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';

    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'zenquotes_Quotes';

    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';

    /**
     * Описание на модула
     */
    public $info = 'Цитати от ZenQuotes API';

    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        // API key за ZenQuotes
        'ZENQUOTES_API_KEY' => array('varchar', 'mandatory, caption=ZenQuotes API->Ключ'),
    );

    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'zenquotes_Quotes',
    );

    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'Get quotes from ZenQuotes',
            'description' => 'Извличане на цитати от ZenQuotes',
            'controller' => 'zenquotes_Quotes',
            'action' => 'getQuotes',
            'period' => 10,
            'offset' => 0,
            'delay' => 0,
            'timeLimit' => 20,
        ),
    );
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'zenquotes_drivers_UpdateQuotesBlock';

    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        return $html;
    }
}