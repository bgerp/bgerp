<?php 


/**
 * Мениджира детайлите на стелажите (pallet_RackDetails)
 *
 *
 * @category  bgerp
 * @package   pallet
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class pallet_RackDetails extends core_Detail
{
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'store_RackDetails';
    
    
    /**
     * Заглавие
     */
    public $title = 'Детайли на стелаж';
    
    
    /**
     * Страница от менюто
     */
    public $pageMenu = 'Логистика';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, pallet_Wrapper';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'rackId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт, rackId, rRow, rColumn, action, metric';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, pallet';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, pallet';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, pallet';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, pallet';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('rackId', 'key(mvc=pallet_Racks)', 'caption=Позиция->Стелаж, input=hidden');
        $this->FLD('rRow', 'enum(A,B,C,D,E,F,G,H,ALL)', 'caption=Позиция->Ред');
        $this->FLD('rColumn', 'varchar(3)', 'caption=Позиция->Колона');
        $this->FLD('action', 'enum(outofuse=неизползваемо,
                                    reserved=резервирано,
                                    maxWeight=макс. тегло (кг), 
                                    maxWidth=макс. широчина (м),
                                    maxHeight=макс. височина (м))', 'caption=Параметър->Име');
        $this->FLD('metric', 'double(decimals=2)', 'caption=Параметър->Стойност');
    }
    
    
    /**
     * Prepare 'num'
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->rackId = pallet_Racks::fetchField("#id = {$rec->rackId}", 'num');
    }
    
    
    /**
     * При добавяне/редакция на палетите - данни по подразбиране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $rackId = $data->form->rec->rackId;
        $rackNum = pallet_Racks::fetchField("#id = {$rackId}", 'num');
        
        $data->formTitle = 'Добавяне на параметри за стелаж|* № ' . $rackNum;
        
        $rRows = pallet_Racks::fetchField("#id = {$rackId}", 'rows');
        $rColumns = pallet_Racks::fetchField("#id = {$rackId}", 'columns');
        
        for ($j = 1; $j <= $rRows; $j++) {
            $rRowsOpt[pallet_Racks::rackRowConv($j)] = pallet_Racks::rackRowConv($j);
        }
        $rRowsOpt['ALL'] = 'Всички';
        unset($j);
        
        for ($i = 1; $i <= $rColumns; $i++) {
            $rColumnsOpt[$i] = $i;
        }
        $rColumnsOpt['ALL'] = 'Всички';
        unset($i);
        
        $data->form->setOptions('rRow', $rRowsOpt);
        $data->form->setOptions('rColumn', $rColumnsOpt);
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    public static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        $data->form->title = $data->formTitle;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $rec = $form->rec;
            
            // Текущите детайли за стелажа
            $detailsForRackArr = pallet_RackDetails::getDetailsForRack($rec->rackId);
            
            // Палетите на (към) стелажа
            $palletsInStoreArr = pallet_Pallets::getPalletsInStore();
            
            // Параметри на стелажа
            $rackRows = pallet_Racks::fetchField("#id = {$rec->rackId}", 'rows');
            $rackColumns = pallet_Racks::fetchField("#id = {$rec->rackId}", 'columns');
            
            /* Проверки за други детайли, палети и движения към ПМ-то от новия детайл */
            // ред 'ALL' и колона 'ALL'
            if ($rec->rRow == 'ALL' && $rec->rColumn == 'ALL') {
                for ($r = 1; $r <= $rackRows; $r++) {
                    for ($c = 1; $c <= $rackColumns; $c++) {
                        // Проверка за палети/движения
                        if (isset($palletsInStoreArr[$rec->rackId][pallet_Racks::rackRowConv($r)][$c])) {
                            $form->setError('rRow,rColumn', 'Зададената област обхваща палет места, на които вече има|*, 
                                                 <br/>|палети или наредени движения|*!');
                            break 2;
                        }
                    }
                }
            }
            
            // ред 'ALL' и колона not 'ALL'
            if ($rec->rRow == 'ALL' && $rec->rColumn != 'ALL') {
                for ($r = 1; $r <= $rackRows; $r++) {
                    // Проверка за палети/движения
                    if (isset($palletsInStoreArr[$rec->rackId][pallet_Racks::rackRowConv($r)][$rec->rColumn])) {
                        $form->setError('rRow,rColumn', 'Зададената област обхваща палет места, на които вече има|*, 
                                             <br/>|палети или наредени движения|*!');
                        break;
                    }
                }
            }
            
            // ред not 'ALL' и колона 'ALL'
            if ($rec->rRow != 'ALL' && $rec->rColumn == 'ALL') {
                for ($c = 1; $c <= $rackColumns; $c++) {
                    // Проверка за палети/движения
                    if (isset($palletsInStoreArr[$rec->rackId][$rec->rRow][$c])) {
                        $form->setError('rRow,rColumn', 'Зададената област обхваща палет места, на които вече има|*, 
                                             <br/>|палети или наредени движения|*!');
                        break;
                    }
                }
            }
            
            // ред not 'ALL' и колона not 'ALL'
            if ($rec->rRow != 'ALL' && $rec->rColumn != 'ALL') {
                // Проверка за палети/движения
                if (isset($palletsInStoreArr[$rec->rackId][$rec->rRow][$rec->rColumn])) {
                    $form->setError('rRow,rColumn', 'На (към) зададената позиция има|*, 
                                         <br/>|палети или наредени движения|*!');
                }
                
                // Проверка дали има вече съществуващ детайл за тази клетка с този 'action'
                $existingDetailsRecId = pallet_RackDetails::fetchField("#rackId = {$rec->rackId} 
                                                                    AND #rRow = '{$rec->rRow}'
                                                                    AND #rColumn = '{$rec->rColumn}'
                                                                    AND #action = '{$rec->action}'", 'id');
                
                if ($existingDetailsRecId) {
                    $rec->id = $existingDetailsRecId;
                }
                
                // ENDOF Проверка дали има вече съществуващ детайл за тази клетка с този 'action'
                
                // Проверка, ако новия детайл не е 'outofuse', дали за това ПМ има вече дефиниран детайл 'outofuse'
                if ($rec->action != 'outofuse') {
                    if (isset($detailsForRackArr[$rec->rackId . '-' . $rec->rRow . '-' . $rec->rColumn]['outofuse'])) {
                        $form->setError('rRow,rColumn', 'Тази позиция вече е дефинирана като неизползваема!');
                    }
                }
                
                // ENDOF Проверка, ако новия детайл не е 'outofuse', дали за това ПМ има вече дефиниран детайл 'outofuse'
                
                // Проверка, ако новия детайл е 'outofuse' дали за това ПМ има вече дефинирани детайли, които не са 'outofuse'
                if ($rec->action == 'outofuse') {
                    $detailsActionsArr = array('reserved', 'maxWeight', 'maxWidth', 'maxHeight');
                    
                    foreach ($detailsActionsArr as $v) {
                        if (isset($detailsForRackArr[$rec->rackId . '-' . $rec->rRow . '-' . $rec->rColumn][$v])) {
                            $form->setWarning('rRow,rColumn', 'За тази позиция вече има дефинирани детайли!');
                            break;
                        }
                    }
                }
                
                // ENDOF Проверка, ако новия детайл е 'outofuse' дали за това ПМ има вече дефинирани детайли, които не са 'outofuse'
            }
            
            /* ENDOF Проверки за други детайли, палети и движения към ПМ-то от новия детайл */
        }
    }
    
    
    /**
     * Зарежда всички детайли за даден стелаж
     *
     * @param  int   $rackId
     * @return array $detailsForRackArr
     */
    public static function getDetailsForRack($rackId)
    {
        $rackRows = pallet_Racks::fetchField("#id = {$rackId}", 'rows');
        $rackColumns = pallet_Racks::fetchField("#id = {$rackId}", 'columns');
        
        $detailsArrBoolean = array('outofuse', 'reserved');
        $detailsArrFloat = array('maxWeight', 'maxWidth', 'maxHeight');
        
        $detailsResults = array();
        
        // Редове 'ALL' и колони 'ALL'
        $query = pallet_RackDetails::getQuery();
        $where = "#rackId = {$rackId} AND #rRow='ALL' AND #rColumn='ALL'";
        
        while ($rec = $query->fetch($where)) {
            $detailsResults[] = $rec;
        }
        unset($query, $where, $rec);
        
        // Редове 'ALL' и колони not 'ALL'
        $query = pallet_RackDetails::getQuery();
        $where = "#rackId = {$rackId} AND #rRow='ALL' AND #rColumn!='ALL'";
        
        while ($rec = $query->fetch($where)) {
            $detailsResults[] = $rec;
        }
        unset($query, $where, $rec);
        
        // Редове not 'ALL' и колони 'ALL'
        $query = pallet_RackDetails::getQuery();
        $where = "#rackId = {$rackId} AND #rRow!='ALL' AND #rColumn='ALL'";
        
        while ($rec = $query->fetch($where)) {
            $detailsResults[] = $rec;
        }
        unset($query, $where, $rec);
        
        // Редове not 'ALL' и колони not 'ALL'
        $query = pallet_RackDetails::getQuery();
        $where = "#rackId = {$rackId} AND #rRow!='ALL' AND #rColumn!='ALL'";
        
        while ($rec = $query->fetch($where)) {
            $detailsResults[] = $rec;
        }
        unset($query, $where, $rec);
        
        // foreach
        foreach ($detailsResults as $rec) {
            $palletPlace = $rec->rackId . '-' . $rec->rRow . '-' . $rec->rColumn;
            
            $detailsRec['action'] = $rec->action;
            $detailsRec['metric'] = $rec->metric;
            
            // ред 'ALL' и колона 'ALL'
            if ($rec->rRow == 'ALL' && $rec->rColumn == 'ALL') {
                for ($r = 1; $r <= $rackRows; $r++) {
                    for ($c = 1; $c <= $rackColumns; $c++) {
                        $pp = $rec->rackId . '-' . pallet_Racks::rackRowConv($r) . '-' . $c;
                        
                        if (in_array($detailsRec['action'], $detailsArrBoolean)) {
                            $detailsForRackArr[$pp][$detailsRec['action']] = 'YES';
                            continue;
                        }
                        
                        if (in_array($detailsRec['action'], $detailsArrFloat)) {
                            $detailsForRackArr[$pp][$detailsRec['action']] = $detailsRec['metric'];
                        }
                    }
                }
            }
            
            // ред 'ALL' и колона not 'ALL'
            if ($rec->rRow == 'ALL' && $rec->rColumn != 'ALL') {
                for ($r = 1; $r <= $rackRows; $r++) {
                    $pp = $rec->rackId . '-' . pallet_Racks::rackRowConv($r) . '-' . $rec->rColumn;
                    
                    if (in_array($detailsRec['action'], $detailsArrBoolean)) {
                        $detailsForRackArr[$pp][$detailsRec['action']] = 'YES';
                        continue;
                    }
                    
                    if (in_array($detailsRec['action'], $detailsArrFloat)) {
                        $detailsForRackArr[$pp][$detailsRec['action']] = $detailsRec['metric'];
                    }
                }
            }
            
            // ред not 'ALL' и колона 'ALL'
            if ($rec->rRow != 'ALL' && $rec->rColumn == 'ALL') {
                for ($c = 1; $c <= $rackColumns; $c++) {
                    $pp = $rec->rackId . '-' . $rec->rRow . '-' . $c;
                    
                    if (in_array($detailsRec['action'], $detailsArrBoolean)) {
                        $detailsForRackArr[$pp][$detailsRec['action']] = 'YES';
                        continue;
                    }
                    
                    if (in_array($detailsRec['action'], $detailsArrFloat)) {
                        $detailsForRackArr[$pp][$detailsRec['action']] = $detailsRec['metric'];
                    }
                }
            }
            
            // ред not 'ALL' и колона not 'ALL'
            if ($rec->rRow != 'ALL' && $rec->rColumn != 'ALL') {
                $pp = $rec->rackId . '-' . $rec->rRow . '-' . $rec->rColumn;
                
                if (in_array($detailsRec['action'], $detailsArrBoolean)) {
                    $detailsForRackArr[$pp][$detailsRec['action']] = 'YES';
                    continue;
                }
                
                if (in_array($detailsRec['action'], $detailsArrFloat)) {
                    $detailsForRackArr[$pp][$detailsRec['action']] = $detailsRec['metric'];
                }
            }
        }
        
        return $detailsForRackArr;
    }
    
    
    /**
     * Проверка дали тази позиция присъства в детайлите и дали е неизползваема
     * @param  int     $rackId
     * @param  string  $palletPlace
     * @return boolean
     */
    public static function checkIfPalletPlaceIsNotOutOfUse($rackId, $palletPlace)
    {
        $detailsForRackArr = pallet_RackDetails::getDetailsForRack($rackId);
        
        if (empty($detailsForRackArr)) {
            return true;
        }
        if (isset($detailsForRackArr[$palletPlace]['outofuse'])) {
            return false;
        }

        return true;
    }
    
    
    /**
     * Проверка дали тази позиция присъства в детайлите и дали е резервирана
     * @param  int     $rackId
     * @param  string  $palletPlace
     * @return boolean
     */
    public static function checkIfPalletPlaceIsNotReserved($rackId, $palletPlace)
    {
        $detailsForRackArr = pallet_RackDetails::getDetailsForRack($rackId);
        
        if (empty($detailsForRackArr)) {
            return true;
        }
        if (isset($detailsForRackArr[$palletPlace]['reserved'])) {
            return false;
        }

        return true;
    }
}
