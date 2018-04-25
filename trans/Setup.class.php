<?php


/**
 * Период, на който крона ще затваря миналите Линии и ще генерира нови
 */
defIfNot('TRANS_LINES_CRON_INTERVAL', 60 * 60);


/**
 * Дефолтен текст за инструкции на изпращача
 */
defIfNot('TRANS_CMR_SENDER_INSTRUCTIONS', '');


/**
 * Транспорт
 *
 *
 * @category  bgerp
 * @package   trans
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class trans_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'trans_Lines';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Организация на вътрешния транспорт";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'trans_Vehicles',
    		'trans_Lines',
    		'trans_Cmrs',
    		'migrate::updateVehicles',
    		'migrate::updateLineVehicles'
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'trans';

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.3, 'Логистика', 'Транспорт', 'trans_Lines', 'default', "trans, ceo"),
        );

    /**
	 * Описание на конфигурационните константи
	 */
	var $configDescription = array(
		'TRANS_LINES_CRON_INTERVAL' => array("time", 'caption=Период за генериране и затваряне на линии->Време'),
		'TRANS_CMR_SENDER_INSTRUCTIONS' => array('text(rows=2)' ,"caption=ЧМР->13. Инструкции на изпращача"),
	);
	
	
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
    
    
    /**
     * Ъпдейт на превозните средства
     */
    function updateVehicles()
    {
    	$query = trans_Vehicles::getQuery();
    	$query->where("#state != 'rejected' OR #state IS NULL");
    	while($rec = $query->fetch()){
    		try{
    			$rec->state = 'active';
    			trans_Vehicles::save($rec, 'state');
    		} catch(core_exception_Expect $e){
    			reportException($e);
    		}
    	}
    }
    
    
    /**
     * Обновява транспортните линии
     */
    function updateLineVehicles()
    {
    	foreach (array('store_ShipmentOrders', 'store_Receipts', 'store_Transfers', 'store_ConsignmentProtocols') as $Doc){
    		$D = cls::get($Doc);
    		$D->setupMvc();
    	}
    	
    	$Lines = cls::get('trans_Lines');
    	$Lines->setupMvc();
    	
    	$query = trans_Lines::getQuery();
    	$query->where("#vehicle IS NOT NULL");
    	
    	while($rec = $query->fetch()){
    		if(is_numeric($rec->vehicle)){
    			if($name = trans_Vehicles::fetchField($rec->vehicle, 'name')){
    				$rec->vehicle = $name;
    				$Lines->save($rec, 'vehicle');
    			}
    		}
    	}
    }
}