<?php


/**
 * Политики: значещи цифри
 */
defIfNot('PRICE_SIGNIFICANT_DIGITS', '5');


/**
 * Политики: десетични знаци
 */
defIfNot('PRICE_MIN_DECIMALS', '2');


/**
 * Минимален % промяна за автоматичното обновяване на себе-ст
 */
defIfNot('PRICE_MIN_CHANGE_UPDATE_PRIME_COST', '0.03');


/**
 * Складове, в които да се усреднява цената
 */
defIfNot('PRICE_STORE_AVERAGE_PRICES', '');


/**
 * На колко време да се кешират и обновяват себестойностите
 */
defIfNot('PRICE_CRON_UPDATE_PRIME_COST', '5');


/**
 * На колко време да се кешират и обновяват рецептите
 */
defIfNot('PRICE_CRON_UPDATE_BOM_COST', '11');


/**
 * Инсталиране на модул 'price'
 *
 * Ценови политики на фирмата
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2021 Experta OOD
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
            'systemId' => 'Clean cached prices',
            'description' => 'Изтриване на кешираните цени',
            'controller' => 'price_Cache',
            'action' => 'RemoveExpiredPrices',
            'period' => 180,
            'offset' => 77,
            'timeLimit' => 10
        ),
    );


    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'price_Lists',
        'price_ListToCustomers',
        'price_ListVariations',
        'price_ListRules',
        'price_ListDocs',
        'price_ProductCosts',
        'price_Updates',
        'price_Cache',
        'price_ListBasicDiscounts',
        'price_DiscountsPerDocuments',
        'migrate::updateCostList2524v2',
    );


    /**
     * Роли за достъп до модула
     */
    public $roles = array(array('priceDealer'),
        array('noPrice'),
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
        'PRICE_MIN_DECIMALS' => array('int(min=0)', 'caption=Ценови политики: закръгляне за избрания вид (с/без ДДС) цени (2 и 1 за цена Х.хх)->Десетични знаци', "unit= (|желан брой цифри след десетичната запетая|*)"),
        'PRICE_SIGNIFICANT_DIGITS' => array('int(min=0)', 'caption=Ценови политики: закръгляне за избрания вид (с/без ДДС) цени (2 и 1 за цена Х.хх)->Значещи цифри', "unit= (|но минимален брой цифри различни от|* 0)"),
        'PRICE_MIN_CHANGE_UPDATE_PRIME_COST' => array('percent(Min=0,max=1)', 'caption=Автоматично обновяване на себестойностите->Мин. промяна'),
        'PRICE_STORE_AVERAGE_PRICES' => array('keylist(mvc=store_Stores,select=name)', 'caption=Изчисляване на "Осреднена за избрани складове" себестойност->За складове,callOnChange=price_interface_AverageCostStorePricePolicyImpl::saveAvgPrices'),
        'PRICE_CRON_UPDATE_PRIME_COST' => array('int(min=0)', 'caption=Настройки на крона за обновяване на себестойностите->Минути'),
        'PRICE_CRON_UPDATE_BOM_COST' => array('int(min=0)', 'caption=Настройки на крона за обновяване на себестойностите на рецептите->Минути'),
    );


    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'price_reports_PriceList,price_interface_AverageCostPricePolicyImpl,price_interface_LastAccCostPolicyImpl,price_interface_LastActiveDeliveryCostPolicyImpl,price_interface_LastDeliveryCostPolicyImpl,price_interface_LastActiveBomCostPolicy,price_interface_ListRulesImport,price_interface_AverageCostStorePricePolicyImpl,price_interface_LastQuotationFromSupplier,price_interface_LastActiveBomCostWithExpenses,price_interface_LastManifacturePrice';


    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();

        // Залагаме в cron
        $rec = new stdClass();
        $rec->systemId =  'Update primecosts';
        $rec->description = 'Обновяване на себестойностите';
        $rec->controller = 'price_Updates';
        $rec->action = 'Updateprimecosts';
        $rec->period = static::get('CRON_UPDATE_PRIME_COST');
        $rec->timeLimit = 360;
        $html .= core_Cron::addOnce($rec);

        $rec = new stdClass();
        $rec->systemId =  'Update bom costs';
        $rec->description = 'Обновяване на кешираните цени по рецепти';
        $rec->controller = 'price_interface_LastActiveBomCostPolicy';
        $rec->action = 'updateCachedBoms';
        $rec->period = static::get('CRON_UPDATE_BOM_COST');
        $rec->timeLimit = 360;
        $html .= core_Cron::addOnce($rec);

        return $html;
    }


    /**
     * Миграция на замърсени каталози
     */
    public function updateCostList2524v2()
    {
        $rec = price_Lists::fetch(price_ListRules::PRICE_LIST_CATALOG);
        if($rec->parent == price_ListRules::PRICE_LIST_CATALOG){
            $rec->parent = price_ListRules::PRICE_LIST_COST;
            price_Lists::save($rec, 'parent');
        }
    }
}
