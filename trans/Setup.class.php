<?php


/**
 * Дефолтен текст за инструкции на изпращача
 */
defIfNot('TRANS_CMR_SENDER_INSTRUCTIONS', '');


/**
 * Дали да се показва бутона за ЧМР, ако не е избрано условие на доставка
 */
defIfNot('TRANS_CMR_SHOW_BTN', 'no');

/**
 * От коя дата да започнат да се изчисляват индикаторите за транспортните линии
 */
defIfNot('TRANS_DATE_FOR_TRANS_INDICATORS', '');


/**
 * Автоматично затваряне на транспортни линии активни от
 */
defIfNot('TRANS_LINES_ACTIVATED_AFTER', '5184000');


/**
 * Автоматично затваряне на транспортни линии чакащи с минала дата
 */
defIfNot('TRANS_LINES_PENDING_AFTER', '604800');


/**
 * Транспорт
 *
 *
 * @category  bgerp
 * @package   trans
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class trans_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'trans_Lines';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Организация на вътрешния транспорт';
    
    
    /**
     * Необходими пакети
     */
    public $depends = 'store=0.1';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'trans_Vehicles',
        'trans_Lines',
        'trans_Cmrs',
        'trans_TransportModes',
        'trans_TransportUnits',
        'trans_LineDetails',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'trans';
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'trans_Indicators,trans_reports_LinesByForwarder';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.3, 'Логистика', 'Транспорт', 'trans_Lines', 'default', 'trans, ceo'),
    );


    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'Close Trans Lines',
            'description' => 'Затваряне на транспортни линии',
            'controller' => 'trans_Lines',
            'action' => 'CloseTransLines',
            'period' => 1440,
            'timeLimit' => 360
        ),
    );


    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'TRANS_LINES_ACTIVATED_AFTER' => array('time','caption=Автоматично затваряне на транспортни линии->Активни от'),
        'TRANS_LINES_PENDING_AFTER' => array('time','caption=Автоматично затваряне на транспортни линии->Чакащи с минала дата'),
        'TRANS_CMR_SENDER_INSTRUCTIONS' => array('text(rows=2)','caption=ЧМР->13. Инструкции на изпращача'),
        'TRANS_CMR_SHOW_BTN' => array('enum(yes=Включено,no=Изключено)','caption=При липса на условие на доставка. Да се показва ли бутона за ЧМР->Избор'),
        'TRANS_DATE_FOR_TRANS_INDICATORS' => array('date', 'caption=От коя дата да се изчисляват индикатори транспортни линии->Дата'),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Добавяне на системна група за водачи на МПС
        $groupRec = (object)array('name' => 'Водачи МПС', 'sysId' => 'vehicleDrivers', 'allow' => 'persons');
        crm_Groups::forceGroup($groupRec);
        
        return $html;
    }
    
    
    /**
     * Ъпдейт на ЛЕ в ЕН
     */
    private function updateLu()
    {
        $so = cls::get('store_ShipmentOrders');
        $so->setupMvc();
        $sod = cls::get('store_ShipmentOrderDetails');
        $sod->setupMvc();
        
        $transUnits = cls::get('trans_TransportUnits')->makeArray4Select();
        
        $save = array();
        $dQuery = store_ShipmentOrderDetails::getQuery();
        $dQuery->FLD('transUnit', 'varchar', 'caption=Логистична информация->Единици,autohide,after=volume');
        $dQuery->FLD('info', 'text(rows=2)', 'caption=Логистична информация->Номера,after=transUnit,autohide,after=volume');
        $dQuery->where("#transUnit IS NOT NULL AND #transUnit != ''");
        $dQuery->show('transUnit,info,shipmentId');
        
        while ($dRec = $dQuery->fetch()) {
            if (is_numeric($dRec->transUnit)) {
                continue;
            }
            if (!empty($dRec->transUnitId)) {
                continue;
            }
            
            $unit = str::mbUcfirst(trim($dRec->transUnit));
            if (in_array($unit, array('Pallets', 'Палети', 'Палета', 'Палет'))) {
                $unit = 'Палета';
            } elseif (in_array($unit, array('Carton boxes', 'Кашони', 'Кашона', 'Кашон'))) {
                $unit = 'Кашона';
            }
            
            if (!in_array($unit, $transUnits)) {
                $transId = trans_TransportUnits::save((object) array('name' => $unit, 'pluralName' => $unit, 'abbr' => $unit));
                $transUnits[$transId] = $unit;
            } else {
                $transId = array_search($unit, $transUnits);
            }
            
            if (!empty($transId)) {
                $dRec->transUnitId = $transId;
                $luArr = self::getLUs($dRec->info);
                $count = !is_array($luArr) ? 1 : countR($luArr);
                $count = (empty($count)) ? 1 : $count;
                $dRec->transUnitQuantity = $count;
                $save[$dRec->id] = $dRec;
            }
        }
        
        $sod->saveArray($save, 'id,transUnitId,transUnitQuantity');
        
        wp('UPDATE LU COUNT' . countR($save));
    }
    
    
    /**
     * Обновява ЛЕ в складовите документи
     */
    private function updateStoreMasters()
    {
        $loadId = trans_TransportUnits::fetchIdByName('load');
        
        //, 'store_Receipts' => 'store_ReceiptDetails', 'store_Transfers' => 'store_TransfersDetails'
        foreach (array('store_ShipmentOrders' => 'store_ShipmentOrderDetails') as $Doc => $det) {
            $Document = cls::get($Doc);
            $Document->setupMvc();
            
            $Detail = cls::get($det);
            $Detail->setupMvc();
            
            $query = $Document->getQuery();
            $query->FLD('palletCountInput', 'double');
            
            $save = array();
            while ($dRec = $query->fetch()) {
                $dRec->transUnits = $Detail->getTransUnits($dRec);
                if ($dRec->palletCountInput && empty($dRec->transUnitsInput)) {
                    $dRec->transUnitsInput = array($loadId => $dRec->palletCountInput);
                } else {
                    $dRec->transUnitsInput = array();
                }
                $save[$dRec->id] = $dRec;
            }
            
            $Document->saveArray($save, 'id,transUnits,transUnitsInput');
        }
        
        wp('UPDATE SO COUNT' . countR($save));
    }
    
    
    /**
     * Добавяне на детайли на транс. линиите
     */
    private function addDetailsToLines()
    {
        foreach (array('store_ShipmentOrders', 'store_Receipts', 'store_Transfers', 'store_ConsignmentProtocols') as $Doc) {
            $D = cls::get($Doc);
            $D->setupMvc();
            
            $save = array();
            $query = $D->getQuery();
            $query->where('#lineId IS NOT NULL');
            while ($rec = $query->fetch()) {
                try {
                    $lRec = (object) array('lineId' => $rec->lineId, 'status' => 'ready', 'containerId' => $rec->containerId, 'classId' => $D->getClassId());
                    $lRec->documentLu = $lRec->readyLu = array();
                    if ($exRec = trans_LineDetails::fetch("#lineId = {$rec->lineId} AND #containerId = {$rec->containerId}", 'documentLu,readyLu')) {
                        $lRec->id = $exRec->id;
                        $lRec->documentLu = $exRec->documentLu;
                        $lRec->readyLu = $exRec->readyLu;
                    }
                    
                    $save[] = $lRec;
                } catch (core_exception_Expect $e) {
                    reportException($e);
                }
            }
            
            cls::get('trans_LineDetails')->saveArray($save);
        }
        
        wp('UPDATE ADDED LINE DETAILS' . countR($save));
    }
    
    
    /**
     * Парсира текст, въведен от потребителя в масив с номера на логистични единици
     * Връща FALSE, ако текста е некоректно форматиран
     */
    private static function getLUs($infoLU)
    {
        $res = array();
        
        $str = str_replace(array(',', '№'), array("\n", ''), $infoLU);
        $arr = explode("\n", $str);
        
        foreach ($arr as $item) {
            $item = trim($item);
            
            if (empty($item)) {
                continue;
            }
            
            if (strpos($item, '-')) {
                list($from, $to) = explode('-', $item);
                $from = trim($from);
                $to = trim($to);
                if (!ctype_digit($from) || !ctype_digit($to) || !($from < $to)) {
                    
                    return 'Непарсируем диапазон на колети|* "'. $item . '"';
                }
                for ($i = (int) $from; $i <= $to; $i++) {
                    if (isset($res[$i])) {
                        
                        return 'Повторение на колет|* №'. $i;
                    }
                    $res[$i] = $i;
                }
            } elseif (!ctype_digit($item)) {
                
                return 'Непарсируем номер на колет|* "'. $item . '"';
            } else {
                if (isset($res[$item])) {
                    
                    return 'Повторение на колет|* №'. $item;
                }
                $item = (int) $item;
                $res[$item] = $item;
            }
        }
        
        if (trim($infoLU) && !countR($res)) {
            
            return 'Грешка при парсиране на номерата на колетите';
        }
        
        asort($res);
        
        return $res;
    }
}
