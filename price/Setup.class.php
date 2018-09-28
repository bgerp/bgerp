<?php


/**
 * Начален номер на фактурите
 */
defIfNot('PRICE_SIGNIFICANT_DIGITS', '5');


/**
 * Краен номер на фактурите
 */
defIfNot('PRICE_MIN_DECIMALS', '2');


/**
 * Минимален % промяна за автоматичното обновяване на себе-ст
 */
defIfNot('PRICE_MIN_CHANGE_UPDATE_PRIME_COST', '0.03');


/**
 * Инсталиране на модул 'price'
 *
 * Ценови политики на фирмата
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class price_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'price_Lists';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Ценови политики, ценоразписи, разходни норми';
    
    
    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'Update primecosts',
            'description' => 'Обновяване на себестойностите',
            'controller' => 'price_Updates',
            'action' => 'Updateprimecosts',
            'period' => 60,
            'timeLimit' => 360,
        ),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'price_Lists',
        'price_ListToCustomers',
        'price_ListRules',
        'price_History',
        'price_ListDocs',
        'price_ProductCosts',
        'price_Updates',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = array(array('priceDealer'),
        array('price', 'priceDealer'),
        array('priceMaster', 'price'),
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(1.44, 'Артикули', 'Ценообразуване', 'price_Lists', 'default', 'price,sales, ceo'),
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'PRICE_SIGNIFICANT_DIGITS' => array('int(min=0)', 'caption=Закръгляне в ценовите политики (без себестойност)->Значещи цифри'),
        'PRICE_MIN_DECIMALS' => array('int(min=0)', 'caption=Закръгляне в ценовите политики (без себестойност)->Мин. знаци'),
        'PRICE_MIN_CHANGE_UPDATE_PRIME_COST' => array('percent(min=0,max=1)', 'caption=Автоматично обновяване на себестойностите->Мин. промяна'),
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
