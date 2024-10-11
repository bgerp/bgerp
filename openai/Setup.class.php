<?php


/**
 * Парола
 */
defIfNot('OPENAI_TOKEN', '');


/**
 * Парола
 */
defIfNot('OPENAI_BASE_URL', 'https://api.openai.com/v1');


/**
 * Версия
 */
defIfNot('OPENAI_VERSION', '0.1.6');


/**
 * Настройка на API
 * model
 */
defIfNot('OPENAI_API_MODEL_VERSION', 'openai_TextDavinci003');


/**
 * Настройка на API
 * api_temperature
 */
defIfNot('OPENAI_API_TEMPERATURE', 0.7);


/**
 * Настройка на API
 * api_max_tokens
 */
defIfNot('OPENAI_API_MAX_TOKENS', 256);


/**
 * Настройка на API
 * api_top_p
 */
defIfNot('OPENAI_API_TOP_P', 1);


/**
 * Настройка на API
 * api_frequency_penalty
 */
defIfNot('OPENAI_API_FREQUENCY_PENALTY', 0);


/**
 * Настройка на API
 * api_presence_penalty
 */
defIfNot('OPENAI_API_PRESENCE_PENALTY', 0);


/**
 *
 *
 * @category  bgerp
 * @package   openai
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class openai_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Интеграция с openai API';

    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'OPENAI_TOKEN' => array('password(show)', 'caption=Ключ,class=w100'),
        'OPENAI_API_MODEL_VERSION' => array('class(interface=openai_GPTIntf,select=title)', 'caption=API настройка->Модел'),
        'OPENAI_API_TEMPERATURE' => array('double', 'caption=API настройка->temperature'),
        'OPENAI_API_MAX_TOKENS' => array('int', 'caption=API настройка->max_tokens'),
        'OPENAI_API_TOP_P' => array('int', 'caption=API настройка->top_p'),
        'OPENAI_API_FREQUENCY_PENALTY' => array('int', 'caption=API настройка->frequency_penalty'),
        'OPENAI_API_PRESENCE_PENALTY' => array('int', 'caption=API настройка->presence_penalty'),
    );


    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'openai_TextDavinci003, openai_GPT35Turbo, openai_GPT4, openai_GPT4Turbo, openai_GPT4o, openai_GPT4omini';


    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'openai_Cache';


    /**
     * Роли за достъп до модула
     */
    public $roles = array(
        array('openai'),
    );


    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'openai_Cache',
        'openai_Prompt',
        'migrate::promptTruncate2313',
        'migrate::promptAddIgnoreWords2401',
        'migrate::promptAddIgnoreWordsFromEmail2314',
        'migrate::fixDefaultPromptClass2350',
    );


    /**
     * Миграция за изчистване на данните
     */
    public static function promptTruncate2313()
    {
        openai_Prompt::delete(array("#systemId = '[#1#]'", openai_Prompt::$extractContactDataBg));
        openai_Prompt::delete(array("#systemId = '[#1#]'", openai_Prompt::$extractContactDataEn));

        openai_Prompt::addDefaultParams();
    }


    /**
     * Миграция за попълване на данните за игнориране от данните на компанията в резултата при парсиране на имейла
     */
    public static function promptAddIgnoreWords2401()
    {
        $query = openai_Prompt::getQuery();
        while ($rec = $query->fetch()) {
            if (!$rec->ignoreWords) {
                $rec->ignoreWords = implode("\n", array('-', 'none', 'N/A', 'Unknown', 'Not Specified', '*not provided*', 'not mentioned', '*не е посочен*', '*липсва информация*', 'няма информация'));
                openai_Prompt::save($rec, 'ignoreWords');
            }
        }
    }


    /**
     * Миграция за попълване на данните за игнориране от данните на компанията в имейла
     */
    public static function promptAddIgnoreWordsFromEmail2314()
    {
        $query = openai_Prompt::getQuery();
        while ($rec = $query->fetch()) {
            $lg = 'bg';
            if ($rec->systemId == openai_Prompt::$extractContactDataEn) {
                $lg = 'en';
            }

            core_Lg::push($lg);
            $oRec = crm_Companies::fetchOwnCompany();
            core_Lg::pop();

            $aArr = array();
            foreach (array('company', 'companyVerb', 'email', 'website', 'groupEmails', 'tel', 'fax', 'eori', 'uicId', 'vatNo') as $fld) {
                $oRec->{$fld} = trim($oRec->{$fld});
                if (empty($oRec->{$fld})) continue;
                $aArr[$oRec->{$fld}] = $oRec->{$fld};
            }

            if (!$rec->emailIgnoreWords) {
                $rec->emailIgnoreWords = implode("\n", $aArr);
                openai_Prompt::save($rec, 'emailIgnoreWords');
            }
        }
    }


    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();

        // Инсталиране на плъгин за превод на входящата поща
        $html .= core_Plugins::installPlugin('Email parse contragent data', 'openai_plugins_IncomingsContragentData', 'email_Incomings', 'private');

        return $html;
    }


    /**
     * Оправя старите настройки за модела
     */
    public static function fixDefaultPromptClass2350()
    {
        $conf = core_Packs::getConfig('openai');

        if ($conf->_data['OPENAI_API_MODEL_VERSION']) {
            $dArr = array();
            if ($conf->_data['OPENAI_API_MODEL_VERSION'] == 'GPT 3.5 TURBO') {
                $dArr['OPENAI_API_MODEL_VERSION'] = core_Classes::getId('openai_GPT35Turbo');
            } elseif ($conf->_data['OPENAI_API_MODEL_VERSION'] == 'TEXT DAVINCI 003') {
                $dArr['OPENAI_API_MODEL_VERSION'] = core_Classes::getId('openai_TextDavinci003');
            }

            if (!empty($dArr)) {
                core_Packs::setConfig('openai', $dArr);
            }
        }
    }
}
