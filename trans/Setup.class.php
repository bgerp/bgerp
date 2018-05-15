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
     * Необходими пакети
     */
    var $depends = 'store=0.1';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'trans_Vehicles',
    		'trans_Lines',
    		'trans_Cmrs',
    		'trans_TransportModes',
    		'trans_TransportUnits',
    		'trans_LineDetails',
    		'migrate::updateVehicles',
    		'migrate::updateLineVehicles',
    		'migrate::updateStoreDocuments'
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
    	 
    	$transUnits = cls::get('trans_TransportUnits')->makeArray4Select();
    	
    	$save = array();
    	$dQuery = store_ShipmentOrderDetails::getQuery();
    	$dQuery->FLD('transUnit', 'varchar', 'caption=Логистична информация->Единици,autohide,after=volume');
    	$dQuery->FLD('info', "text(rows=2)", 'caption=Логистична информация->Номера,after=transUnit,autohide,after=volume');
    	$dQuery->where("#transUnit IS NOT NULL AND #transUnit != ''");
    	$dQuery->show('transUnit,info,shipmentId');
    	
    	while($dRec = $dQuery->fetch()){
    		if(is_numeric($dRec->transUnit)) continue;
    		if(!empty($dRec->transUnitId)) continue;
    		
    		$unit = str::mbUcfirst(trim($dRec->transUnit));
    		if(in_array($unit, array('Pallets', 'Палети', 'Палета', 'Палет'))){
    			$unit = 'Палета';
    		} elseif(in_array($unit, array('Carton boxes', 'Кашони', 'Кашона', 'Кашон'))){
    			$unit = 'Кашона';
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
    	
    	wp("UPDATE LU COUNT" . count($save));
    }
    
    
    public function updateStoreMasters()
    {
    	$arr = array('store_ShipmentOrders');
    	$sod = cls::get('store_ShipmentOrderDetails');
    	$loadId = trans_TransportUnits::fetchIdByName('load');
    	
    	//, 'store_Receipts' => 'store_ReceiptDetails', 'store_Transfers' => 'store_TransfersDetails'
    	foreach (array('store_ShipmentOrders' => 'store_ShipmentOrderDetails') as $Doc => $det){
    		$Document = cls::get($Doc);
    		$Document->setupMvc();

    		$Detail = cls::get($det);
    		$Detail->setupMvc();
    		
    		$query = $Document->getQuery();
    		$query->FLD('palletCountInput', 'double');
    		
    		$save = array();
    		while($dRec = $query->fetch()){
    			$dRec->transUnits = $Detail->getTransUnits($dRec);
    			if($dRec->palletCountInput && empty($dRec->transUnitsInput)){
    				$dRec->transUnitsInput = array($loadId => $dRec->palletCountInput);
    			} else {
    				$dRec->transUnitsInput = array();
    			}
    			$save[$dRec->id] = $dRec;
    		}
    		
    		$Document->saveArray($save, 'id,transUnits,transUnitsInput');
    	}
    	
    	wp("UPDATE SO COUNT" . count($save));
    }
    
    public function addDetailsToLines()
    {
    	$lines = array();
    	foreach (array('store_ShipmentOrders', 'store_Receipts', 'store_Transfers', 'store_ConsignmentProtocols') as $Doc){
    		$D = cls::get($Doc);
    		$D->setupMvc();
    		
    		$save = array();
    		$query = $D->getQuery();
    		$query->where("#lineId IS NOT NULL");
    		while($rec = $query->fetch()){
    			
    			try{
    				$lRec = (object)array('lineId' => $rec->lineId, 'status' => 'ready', 'containerId' => $rec->containerId, 'classId' => $D->getClassId());
    				$lRec->documentLu = $lRec->readyLu = array();
    				if($exRec = trans_LineDetails::fetch("#lineId = {$rec->lineId} AND #containerId = {$rec->containerId}", 'documentLu,readyLu')){
    					$lRec->id = $exRec->id;
    					$lRec->documentLu = $exRec->documentLu;
    					$lRec->readyLu = $exRec->readyLu;
    				}
    				 
    				$save[] = $lRec;
    			} catch(core_exception_Expect $e){
    				reportException($e);
    			}
    		}
    		
    		cls::get('trans_LineDetails')->saveArray($save);
    	}
    	
    	wp("UPDATE ADDED LINE DETAILS" . count($save));
    }
    
    public function updateStoreDocuments()
    {
    	core_Cron::delete("#systemId = 'CreateNewLines'");
    	core_App::setTimeLimit(1200);
    	
    	$Tld = cls::get('trans_LineDetails');
    	$Tld->setupMvc();
    	
    	$Tl = cls::get('trans_Lines');
    	$Tl->setupMvc();
    	
    	$Tm = cls::get('trans_TransportModes');
    	$Tm->setupMvc();
    	
    	$Tu = cls::get('trans_TransportUnits');
    	$Tu->setupMvc();
    	$Tu->loadSetupData();
    	
    	$this->updateLu();
    	$this->updateStoreMasters();
    	$this->addDetailsToLines();
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