<?php


/**
 * Толеранс за валутния курс
 */
defIfNot('EXCHANGE_DEVIATION', '0.05');


/**
 * class currency_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъра Currency
 *
 *
 * @category  bgerp
 * @package   currency
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class currency_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'currency_Currencies';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    public $depends = 'drdata=0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Валути и хронология на техните курсове';
    

    /**
     * Описание на конфигурационните константи за този модул
     */
    public $configDescription = array(
        'EXCHANGE_DEVIATION' => array('percent', 'mandatory, caption=Толеранс за валутния курс->Процент'),
);
    

    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
            'currency_Currencies',
            //'currency_CurrencyGroups',
            'currency_CurrencyRates',
            'currency_FinIndexes'
        );
    

    /**
     * Роли за достъп до модула
     */
    public $roles = 'currency';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
            array(2.2, 'Финанси', 'Валути', 'currency_Currencies', 'default', 'ceo,admin,cash,bank,currency,acc'),
        );

 

    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
}
