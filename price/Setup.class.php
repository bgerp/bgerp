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
 * Складове, в които да се усреднява цената
 */
defIfNot('PRICE_STORE_AVERAGE_PRICES', '');


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
            'period' => 5,
            'timeLimit' => 360,
        ),
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
        'price_ListRules',
        'price_ListDocs',
        'price_ProductCosts',
        'price_Updates',
        'price_Cache',
        'migrate::migrateUpdates'
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
        'PRICE_STORE_AVERAGE_PRICES' => array('keylist(mvc=store_Stores,select=name)', 'caption=Складове за които да се записва осреднена цена->Избор,callOnChange=price_interface_AverageCostStorePricePolicyImpl::saveAvgPrices'),
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'price_reports_PriceList,price_AutoDiscounts,price_interface_AverageCostPricePolicyImpl,price_interface_LastAccCostPolicyImpl,price_interface_LastActiveDeliveryCostPolicyImpl,price_interface_LastDeliveryCostPolicyImpl,price_interface_LastActiveBomCostPolicy';

    
    
    /**
     * Миграция на правилата за обновяване на себестойности
     */
    public function migrateUpdates()
    {
        $Updates = cls::get('price_Updates');
        
        $Costs = cls::get('price_ProductCosts');
        $Costs->setupMvc();
        $Costs->truncate();
        
        if(!$Updates->count()){
            
            return;
        }
        
        core_Classes::add('price_interface_LastAccCostPolicyImpl');
        core_Classes::add('price_interface_LastActiveDeliveryCostPolicyImpl');
        core_Classes::add('price_interface_AverageCostPricePolicyImpl');
        core_Classes::add('price_interface_LastActiveBomCostPolicy');
        core_Classes::add('price_interface_LastDeliveryCostPolicyImpl');
        
        $map = array('accCost' => price_interface_LastAccCostPolicyImpl::getClassId(), 
                     'activeDelivery' => price_interface_LastActiveDeliveryCostPolicyImpl::getClassId(), 
                     'average' => price_interface_AverageCostPricePolicyImpl::getClassId(), 
                     'bom' => price_interface_LastActiveBomCostPolicy::getClassId(),
                     'lastQuote' => null,
                     'lastDelivery' => price_interface_LastDeliveryCostPolicyImpl::getClassId());
        
        $res = array();
        $query = $Updates->getQuery();
        $query->FLD('costSource1', 'enum(,accCost,lastDelivery,activeDelivery,lastQuote,bom,average)');
        $query->FLD('costSource2', 'enum(,accCost,lastDelivery,activeDelivery,lastQuote,bom,average)');
        $query->FLD('costSource3', 'enum(,accCost,lastDelivery,activeDelivery,lastQuote,bom,average)');
        $query->show('costSource1,costSource2,costSource3');
        
        while($rec = $query->fetch()){
            $rec->sourceClass1 = (!empty($rec->costSource1)) ? $map[$rec->costSource1] : null;
            $rec->sourceClass2 = (!empty($rec->costSource2)) ? $map[$rec->costSource2] : null;
            $rec->sourceClass3 = (!empty($rec->costSource3)) ? $map[$rec->costSource3] : null;
            $res[$rec->id] = $rec;
        }
        
        if(countR($res)){
            $Updates->saveArray($res, 'id,sourceClass1,sourceClass2,sourceClass3');
        }
        
        $datetime = dt::addMonths(-1 * 12); 
        price_ProductCosts::saveCalcedCosts($datetime);
    }
}
