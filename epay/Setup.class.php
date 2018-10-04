<?php


/**
 * MIN генерирана за плащане чрез ePay.bg
 */
defIfNot('EPAY_MIN', '');


/**
 * ЧЕКСУМА генерирана за плащане чрез ePay.bg
 */
defIfNot('EPAY_CHECKSUM', '');


/**
 * Сметка по която се очаква да пристигат плащанията от ePay.bg
 */
defIfNot('EPAY_OWN_ACCOUNT_ID', '');


/**
 * Име на подател на имейл, по който да се разпознава че е дошъл от ePay
 */
defIfNot('EPAY_EMAIL_DOMAIN', 'ntf@epay.bg');


/**
 * Максимален спам рейтинг, за проверка дали имейла е от ePay.bg
 */
defIfNot('EPAY_EMAIL_SPAM_SCORE', '3');


/**
 * Дали е задължително да се плати с ePay или е само опционално
 */
defIfNot('EPAY_MANDATORY_BEFORE_FINALIZATION', 'yes');


/**
 * Пакет за интеграция с ePay.bg
 *
 * @category  bgerp
 * @package   epay
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class epay_Setup extends core_ProtoSetup
{
    /**
     * Версията на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'In development. Интеграция с ePay.bg';
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'epay_driver_OnlinePayment';


    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'EPAY_MIN' => array('varchar', 'caption=Настройки за онлайн плащане->MIN'),
        'EPAY_CHECKSUM' => array('varchar', 'caption=Настройки за онлайн плащане->CHECKSUM'),
        'EPAY_MANDATORY_BEFORE_FINALIZATION' => array('enum(yes=Задължително,no=Опционално)', 'caption=Настройки за онлайн плащане->Онлайн плащане'),
        'EPAY_OWN_ACCOUNT_ID' => array('key2(mvc=bank_OwnAccounts,select=title,allowEmpty,selectSourceArr=epay_Setup::getOwnAccountsArr)', 'caption=Настройки за онлайн плащане->Сметка|* (BGN)'),
        'EPAY_EMAIL_DOMAIN' => array('varchar', 'caption=Имейл от който ще се очаква получаване на плащане->Подател'),
        'EPAY_EMAIL_SPAM_SCORE' => array('double', 'caption=Имейл от който ще се очаква получаване на плащане->Спам рейтинг'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'epay_Tokens',
    );
    
    
    /**
     * Връща опциите за сметки
     *
     * @param array          $params
     * @param NULL|int       $limit
     * @param string         $q
     * @param NULL|int|array $onlyIds
     * @param bool           $includeHiddens
     *
     * @return array
     */
    public static function getOwnAccountsArr($params, $limit = null, $q = '', $onlyIds = null, $includeHiddens = false)
    {
        // Само нашите сметки в BGN
        return bank_OwnAccounts::getOwnAccounts(true, 'BGN');
    }
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
       
        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->installPlugin('Проверка на постъпили плащания от ePay', 'epay_plg_CheckForPayments', 'email_Incomings', 'private');
        
        return $html;
    }
}

