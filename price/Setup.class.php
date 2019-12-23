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
        'migrate::updateGroupNames',
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
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'price_reports_PriceList';
    
    
    /**
     * Добавя шаблони на ценоразписите
     */
    public function updateGroupNames()
    {
        $Groups = cls::get('cat_Groups');
        $Groups->setupMvc();
        
        $toSave = array();
        $query = $Groups->getQuery();
        $query->where("#nameEn = '' OR #nameEn IS NULL");
        $query->show('name,nameEn');
        while ($rec = $query->fetch()) {
            if (strpos($rec->name, '||') !== false) {
                list($nameBg, $nameEn) = explode('||', $rec->name);
                $rec->name = $nameBg;
                $rec->nameEn = $nameEn;
                $toSave[$rec->id] = $rec;
            }
        }
        
        if (count($toSave)) {
            $Groups->saveArray($toSave, 'id,name,nameEn');
        }
    }
}
