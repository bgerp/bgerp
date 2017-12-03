<?php


/**
 * Начален номер на фактурите
 */
defIfNot('TCOST_TRANSPORT_PRODUCTS_ID', '');


/**
 * Надценка за транспорт - твърда
 */
defIfNot('TCOST_ADD_TAX', 0);

/**
 * Надценка за транспорт - твърда
 */
defIfNot('TCOST_ADD_PER_KG', 0);

/**
 * Калкулиране на транспорт
 *
 *
 * @category  bgerp
 * @package   tcost
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class tcost_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'cat=0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'tcost_FeeZones';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Калкулиране на цени за транспорт";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'tcost_FeeZones',
            'tcost_Zones',
            'tcost_Fees',
    		'tcost_Calcs',
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'tcost';

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.5, 'Логистика', 'Навла', 'tcost_FeeZones', 'default', "tcost, ceo"),
        );
	
	
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
    		'TCOST_TRANSPORT_PRODUCTS_ID' => array("keylist(mvc=cat_Products,select=name)", 'mandatory,caption=Артикули за транспорт,optionsFunc=tcost_Setup::getPossibleTransportProducts'),
            'TCOST_ADD_TAX' => array("double(smartRound=2)", 'caption=Надценки за транспорт->Твърда,unit=BGN'),
            'TCOST_ADD_PER_KG' => array("double(smartRound=2)", 'caption=Надценки за транспорт->За 1 кг,unit=BGN'),

    );
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
    
    
    /**
     * Кои артикули могат да се избират като транспорт
     * 
     * @return array $suggestions - списък с артикули
     */
    public static function getPossibleTransportProducts()
    {
    	$suggestions = array();
    	$pQuery = cat_Products::getQuery();
    	$pQuery->where("#canStore = 'no'");
    	$pQuery->show('name');
    	
    	while($pRec = $pQuery->fetch()){
    		$suggestions[$pRec->id] = $pRec->name;
    	}
    	
    	return $suggestions;
    }
    
    
    /**
     * След първоначално зареждане на данните
     */
    function loadSetupData($itr = '')
    {
    	$res = parent::loadSetupData($itr);
    	
    	// Ако няма посочени от потребителя сметки за синхронизация
    	$config = core_Packs::getConfig('tcost');
    	if(strlen($config->TCOST_TRANSPORT_PRODUCTS_ID) === 0){
    		$transportId = cat_Products::fetchField("#code = 'transport'", 'id');
    		if($transportId){
    			$products = array($transportId => $transportId);
    			
    			core_Packs::setConfig('tcost', array('TCOST_TRANSPORT_PRODUCTS_ID' => keylist::fromArray($products)));
    			$res .= "<li style='color:green'>Добавени са дефолтни артикули за транспорт</b></li>";
    		}
    	}
    
    	return $res;
    }
    
    
    /**
     * Добавяне на валута към навлата
     */
    public function addFeeCurrencies()
    {
    	if(cls::load('tcost_Fees', TRUE)){
    		try{
    			$Fees = cls::get('tcost_Fees');
    			$Fees->setupMVC();
    			 
    			$currencyId = acc_Periods::getBaseCurrencyCode();
    			$query = $Fees->getQuery();
    			$query->where("#currencyId IS NULL");
    			while($rec = $query->fetch()){
    				$rec->currencyId = $currencyId;
    				$Fees->save($rec, 'currencyId');
    			}
    		} catch(core_exception_Expect $e){
    			reportException($e);
    		}
    	}
    }
}