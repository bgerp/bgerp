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
    		'trans_TransportModes',
    		'trans_TransportUnits',
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
    			try{
    				if($name = trans_Vehicles::fetchField($rec->vehicle, 'name')){
    					$rec->vehicle = $name;
    					$Lines->save($rec, 'vehicle');
    				}
    			} catch(core_exception_Expect $e){
    				reportException($e);
    			}
    		}
    	}
    }
    
    
    public function updateLu()
    {
    	$so = cls::get('store_ShipmentOrders');
    	$so->setupMvc();
    	$sod = cls::get('store_ShipmentOrderDetails');
    	$sod->setupMvc();
    	 
    	$transUnits = cls::get(trans_TransportUnits)->makeArray4Select();
    	 
    	$save = array();
    	$dQuery = store_ShipmentOrderDetails::getQuery();
    	$dQuery->FLD('transUnit', 'varchar', 'caption=Логистична информация->Единици,autohide,after=volume');
    	$dQuery->FLD('info', "text(rows=2)", 'caption=Логистична информация->Номера,after=transUnit,autohide,after=volume');
    	$dQuery->where("#transUnit IS NOT NULL AND #transUnit != '' AND #transUnitId IS NULL");
    	$dQuery->show('transUnit,info');
    	while($dRec = $dQuery->fetch()){
    		if(is_numeric($dRec->transUnit)) continue;
    		$unit = str::mbUcfirst($dRec->transUnit);
    		if($unit == 'Pallets'){
    			$unit = 'Палети';
    		}elseif($unit == 'Carton boxes'){
    			$unit = 'Кашони';
    		}
    	
    		if(!in_array($unit, $transUnits)){
    			$transId = trans_TransportUnits::save((object)array('name' => $unit, 'pluralName' => $unit, 'abbr' => $unit));
    			$transUnits[$transId] = $unit;
    		} else {
    			$transId = array_search($unit, $transUnits);
    		}
    	
    		if(!empty($transId)){
    			$dRec->transUnitId = $transId;
    			$luArr = self::getLUs($dRec->info);
    			$count = !is_array($luArr) ? 1 : count($luArr);
    			$count = (empty($count)) ? 1 : $count;
    			$dRec->transUnitQuantity = $count;
    			$save[$dRec->id] = $dRec;
    		}
    	}
    	 
    	$sod->saveArray($save, 'id,transUnitId,transUnitQuantity');
    }
    
    
    public function updateStoreTransUnits()
    {
    	$arr = array('store_ShipmentOrders', 'store_Receipts', 'store_ConsignmentProtocols', 'store_Transfers');
    	 
    	$palletItd = trans_TransportUnits::fetchField("#name = 'Палети'");
    	if(empty($palletItd)){
    		$palletItd = trans_TransportUnits::save((object)array('name' => 'Палети', 'pluralName' => 'Палети', 'abbr' => 'Палети'));
    	}
    	 
    	foreach ($arr as $doc){
    		$Document = cls::get($doc);
    		$Document->setupMvc();
    	
    		$save = array();
    		$query = $Document->getQuery();
    		$query->FLD('palletCountInput', 'double');
    		$query->where("#palletCountInput IS NOT NULL AND #palletCountInput != ''");
    		$query->show('palletCountInput,transUnits');
    		while($r = $query->fetch()){
    			if($r->palletCountInput && empty($r->transUnits)){
    				$newArr = array('unitId' => array('0' => $palletItd), 'quantity' => array('0' => $r->palletCountInput));
    				$r->transUnits = core_Type::getByName('table(columns=unitId|quantity)')->fromVerbal($newArr);
    	
    				$save[$r->id] = $r;
    			}
    		}
    	
    		$Document->saveArray($save, 'id,transUnits');
    	}
    }
    
    
    
    public function updateStoreDocuments()
    {
    	
    	$this->updateLu();
    	$this->updateStoreTransUnits();
    }
    
    
    
    /**
     * Парсира текст, въведен от потребителя в масив с номера на логистични единици
     * Връща FALSE, ако текста е некоректно форматиран
     */
    private static function getLUs($infoLU)
    {
    	$res = array();
    
    	$str = str_replace(array(",", '№'), array("\n", ''), $infoLU);
    	$arr = explode("\n", $str);
    
    	foreach($arr as $item) {
    		$item = trim($item);
    
    		if(empty($item)) continue;
    
    		if(strpos($item, '-')) {
    			list($from, $to) = explode('-', $item);
    			$from = trim($from);
    			$to   = trim($to);
    			if(!ctype_digit($from) || !ctype_digit($to) || !($from < $to)) {
    				return "Непарсируем диапазон на колети|* \"". $item . '"';
    			}
    			for($i = (int) $from; $i <= $to; $i++) {
    				if(isset($res[$i])) {
    					return "Повторение на колет|* №". $i;
    				}
    				$res[$i] = $i;
    			}
    		} elseif(!ctype_digit($item)) {
    
    			return "Непарсируем номер на колет|* \"". $item . '"';
    		} else {
    			if(isset($res[$item])) {
    				return "Повторение на колет|* №". $item;
    			}
    			$item = (int) $item;
    			$res[$item] = $item;
    		}
    	}
    
    	if(trim($infoLU) && !count($res)) {
    		return "Грешка при парсиране на номерата на колетите";
    	}
    
    	asort($res);
    
    	return $res;
    }
}