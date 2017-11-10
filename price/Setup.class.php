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
 * Инсталиране на модул 'price'
 *
 * Ценови политики на фирмата
 *
 * @category  bgerp
 * @package   price
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class price_Setup extends core_ProtoSetup
{
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'price_Lists';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Ценови политики, ценоразписи, разходни норми";
    
    
    /**
     * Настройки за Cron
     */
    var $cronSettings = array(
    		array(
    			'systemId'    => "Update primecosts",
    			'description' => "Обновяване на себестойностите",
    			'controller'  => "price_Updates",
    			'action'      => "Updateprimecosts",
    			'period'      => 60,
    			'timeLimit'   => 360,
    		),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
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
    var $roles = array(array('priceDealer'),
    				   array('price', 'priceDealer'),
    				   array('priceMaster', 'price'),
    );
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(1.44, 'Артикули', 'Ценообразуване', 'price_Lists', 'default', "price,sales, ceo"),
        );
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
    		'PRICE_SIGNIFICANT_DIGITS' => array("int(min=0)", "caption=Закръгляне в ценовите политики (без себестойност)->Значещи цифри"),
    		'PRICE_MIN_DECIMALS'       => array("int(min=0)", 'caption=Закръгляне в ценовите политики (без себестойност)->Мин. знаци'),
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
}
