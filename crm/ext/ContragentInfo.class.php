<?php


/**
 * Информация за контрагенти
 *
 * @category  bgerp
 * @package   crm
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     0.12
 */
class crm_ext_ContragentInfo extends core_manager
{
    /**
     * Заглавие
     */
    public $title = 'Информация за контрагенти';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Информация за контрагента';
    
    
    /**
     * Плъгини за зареждане
     *
     * var string|array
     */
    public $loadList = 'crm_Wrapper,plg_Created,plg_Sorting';
    
    
    /**
     * Кой може да редактира
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Кой може да редактира
     */
    public $canList = 'debug';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'contragentId=Контрагент,customerSince=Първо задание,overdueSales=Просрочени сделки,totalDealsCount=Продажби->Брой,totalDealsAmount=Продажби->Сума,overdueDealsCount=Просрочени продажби->Брой,overdueDealsAmount=Просрочени продажби->Сума,supplierSince=Доставчик от,totalPurchaseCount=Покупки->Брой,totalPurchaseAmount=Покупки->Сума,createdBy';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('contragentClassId', 'int');
        $this->FLD('contragentId', 'int', 'tdClass=leftCol wrapText');
        $this->FLD('customerSince', 'date');
        $this->FLD('overdueSales', 'enum(yes=Да)');
        $this->FLD('totalDeals', 'blob(serialize, compress)', 'caption=Продажби->Общо');
        $this->FLD('overdueDeals', 'blob(serialize, compress)', 'caption=Продажби->Просрочени');
        $this->FLD('supplierSince', 'date');
        $this->FLD('purchasesTotal', 'blob(serialize, compress)');
        
        $this->setDbIndex('contragentClassId');
        $this->setDbUnique('contragentClassId,contragentId');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        try {
            $ContragentClass = cls::get($rec->contragentClassId);
            $row->contragentId = $ContragentClass->getHyperlink($rec->contragentId, true);
            
            $cRec = $ContragentClass->fetch($rec->contragentId);
            self::extendRow($ContragentClass, $row, $cRec);
        } catch (core_exception_Expect $e) {
            $row->contragentId = "<span class='red'>" . tr('Проблем с показването') . '</span>';
        }
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $data->listTableMvc->FNC('totalDealsCount', 'int');
        $data->listTableMvc->FNC('totalDealsAmount', 'int');
        $data->listTableMvc->FNC('overdueDealsCount', 'int');
        $data->listTableMvc->FNC('overdueDealsAmount', 'int');
    }
    
    
    /**
     * След подготовка на тулбара
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        if (haveRole('debug')) {
            $rec = core_Cron::getRecForSystemId('Gather_contragent_info');
            $url = array('core_Cron', 'ProcessRun', str::addHash($rec->id), 'forced' => 'yes');
            $data->toolbar->addBtn('Преизчисляване', $url, 'title=Преизчисляване на баланса,ef_icon=img/16/arrow_refresh.png,target=cronjob');
        }
    }
    
    
    /**
     * Връща датата на активиране на най-старата сделка
     *
     * @param int $contragentClassId - ид на класа на контрагента
     * @param int $contragentId      - ид на контрагента
     * @param int $type              - покупка или продажба
     *
     * @return datetime|null - най-ранната дата от която е клиент
     */
    private static function getFirstDate($contragentClassId, $contragentId, $type)
    {
        $Class = ($type == 'client') ? 'sales_Sales' : 'purchase_Purchases';
        
        // намиране на най-старата активна/приключена сделка на контрагента
        $saleQuery = $Class::getQuery();
        $saleQuery->XPR('since', 'date', 'MIN(DATE(COALESCE(#activatedOn, #valior)))');
        $saleQuery->where("#contragentClassId = {$contragentClassId} AND #contragentId = {$contragentId}");
        $saleQuery->where("#state = 'active' || #state = 'closed'");
        $saleQuery->show('since');
        
        $found = $saleQuery->fetch();
        
        return (is_object($found)) ? $found->since : null;
    }
    
    
    /**
     * Връща екстендъра на контрагента
     *
     * @param int $contragentClassId - ид на класа на контрагента
     * @param int $contragentId      - ид на контрагента
     *
     * @return stdClass|FALSE - намерения запис
     */
    public static function getByContragent($contragentClassId, $contragentId)
    {
        return self::fetch("#contragentClassId = {$contragentClassId} AND #contragentId = {$contragentId}");
    }
    
    
    /**
     * Разширяване на вербалното показване на контрагента
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     *
     * @return void
     */
    public static function extendRow($mvc, &$row, $rec)
    {
        $customerSince = crm_ext_ContragentInfo::getCustomerSince($mvc->getClassId(), $rec->id);
        if (!empty($customerSince)) {
            $row->customerSince = core_Type::getByName('date')->toVerbal($customerSince);
        }
       
        $supplierSince = crm_ext_ContragentInfo::getCustomerSince($mvc->getClassId(), $rec->id, 'supplier');
        if (!empty($supplierSince)) {
            $row->supplierSince = core_Type::getByName('date')->toVerbal($supplierSince);
        }
        
        if ($cInfo = crm_ext_ContragentInfo::getByContragent($mvc->getClassId(), $rec->id)) {
            $currencyId = acc_Periods::getBaseCurrencyCode();
            
            if (isset($cInfo->purchasesTotal)) {
                $row->totalPurchaseCount = core_Type::getByName('int')->toVerbal($cInfo->purchasesTotal['count']);
                $row->totalPurchaseAmount = core_Type::getByName('double(decimals=2)')->toVerbal($cInfo->purchasesTotal['amount']) . " {$currencyId}";
            }
            
            if (isset($cInfo->totalDeals)) {
                $row->totalDealsCount = core_Type::getByName('int')->toVerbal($cInfo->totalDeals['count']);
                $row->totalDealsAmount = core_Type::getByName('double(decimals=2)')->toVerbal($cInfo->totalDeals['amount']) . " {$currencyId}";
            }
            
            if (isset($cInfo->overdueDeals)) {
                $row->overdueDealsCount = core_Type::getByName('int')->toVerbal($cInfo->overdueDeals['count']);
                $row->overdueDealsAmount = core_Type::getByName('double(decimals=2)')->toVerbal($cInfo->overdueDeals['amount']) . " {$currencyId}";
            }
        }
    }
    
    
    /**
     * Връща датата от която е клиент контрагента
     *
     * @param int $contragentClassId - ид на класа на контрагента
     * @param int $contragentId      - ид на контрагента
     * @param string $type           - клиент или доставчик
     * 
     * @return datetime|null - най-ранната дата от която е клиент
     */
    public static function getCustomerSince($contragentClassId, $contragentId, $type = 'client')
    {
        $val = ($type == 'client') ? 'customerSince' : 'supplierSince';
        $exRec = self::getByContragent($contragentClassId, $contragentId);
        
        if (empty($exRec->{$val})) {
            $since = self::getFirstDate($contragentClassId, $contragentId, $type);
            
            if (!empty($since)) {
                if (is_object($exRec)) {
                    $exRec->{$val} = $since;
                    $fields = $val;
                } else {
                    $fields = null;
                    $exRec = self::prepareNewRec($contragentClassId, $contragentId, array($val => $since));
                }
                
                self::save($exRec, $fields);
            }
        }
        
        return $exRec->{$val};
    }
    
    
    /**
     * Всички записи от модела
     *
     * @return array $res - записите, групирани по контрагенти
     */
    private static function getAll()
    {
        $res = array();
        
        // Съществуващите записи
        $query = self::getQuery();
        $query->where('#contragentClassId IS NOT NULL');
        while ($rec = $query->fetch()) {
            $res[$rec->contragentClassId][$rec->contragentId] = $rec;
        }
        
        return $res;
    }
    
    
    /**
     * Всички дати от кога са клиенти или доставчици
     *
     * @param int $contragentClassId
     * 
     * @return array $res
     */
    private static function getFirstDates($contragentClassId)
    {
        $res = array();
        
        foreach (array('sales' => 'sales_Sales', 'purchases' => 'purchase_Purchases') as $key => $Class){
            $dQuery = $Class::getQuery();
            $dQuery->XPR('since', 'date', 'MIN(DATE(COALESCE(#activatedOn, #valior)))');
            $dQuery->where("#state = 'active' || #state = 'closed'");
            $dQuery->where("#contragentClassId = {$contragentClassId}");
            $dQuery->show('contragentId,since');
            $dQuery->groupBy('contragentId');
            
            while ($sRec = $dQuery->fetch()) {
                if (!empty($sRec->since)) {
                    $res[$key][$sRec->contragentId] = $sRec->since;
                }
            }
        }
        
        return $res;
    }
    
    
    /**
     * Подготвя нов запис
     *
     * @param int   $contragentClassId
     * @param int   $contragentId
     * @param array $params
     *
     * @return StdClass
     */
    private static function prepareNewRec($contragentClassId, $contragentId, $params = array())
    {
        $newArr = array('contragentId' => $contragentId, 'contragentClassId' => $contragentClassId, 'createdBy' => core_Users::SYSTEM_USER);
        
        if (is_array($params)) {
            $newArr += $params;
        }
        
        $newRec = (object) $newArr;
        if (empty($newRec->createdOn)) {
            $newRec->createdOn = dt::now();
        }
        
        return $newRec;
    }
    
    
    /**
     * Събиране на информация за контрагентите
     */
    public function cron_GatherInfo()
    {
        $now = dt::now();
        $existing = self::getAll();
        
        $uArr = array(core_Users::ANONYMOUS_USER, core_Users::SYSTEM_USER);
        $contragentClasses = core_Classes::getOptionsByInterface('crm_ContragentAccRegIntf', 'id');
        
        // За всички контрагенти
        foreach ($contragentClasses as $classId) {
            $saveArray = array();
            $exRecs = (array_key_exists($classId, $existing)) ? $existing[$classId] : array();
            
            // За всички неоттеглени контрагенти
            $ContragentClass = cls::get($classId);
            $cQuery = $ContragentClass::getQuery();
            $cQuery->where('#folderId IS NOT NULL');
            $cQuery->where("#state != 'rejected'");
            $cQuery->show('folderId,id');
            
            // Дигане на тайм лимита за всеки случай
            $count = $cQuery->count() * 0.032;
            core_App::setTimeLimit($count, false, 300);
            
            // От кога са клиенти
            $datesArr = self::getFirstDates($classId);
            $dealData = self::getDealData($classId);
            
            // За всеки
            while ($cRec = $cQuery->fetch()) {
                if (array_key_exists($cRec->id, $exRecs)) {
                    $r = $exRecs[$cRec->id];
                } else {
                    $r = self::prepareNewRec($classId, $cRec->id, array('createdOn' => $now));
                }
                
                $total = is_array($dealData['sales'][$cRec->id]['total']) ? $dealData['sales'][$cRec->id]['total'] : array();
                $overdues = is_array($dealData['sales'][$cRec->id]['overdue']) ? $dealData['sales'][$cRec->id]['overdue'] : array();
                $purchasesTotal = is_array($dealData['purchases'][$cRec->id]['total']) ? $dealData['purchases'][$cRec->id]['total'] : array();
                
                $r->purchasesTotal = countR($purchasesTotal) ? $purchasesTotal : null;
                $r->overdueSales = countR($overdues) ? 'yes' : null;
                $r->totalDeals = countR($total) ? $total : null;
                $r->overdueDeals = countR($overdues) ? $overdues : null;
                
                //..и е стар запис създаден от системата
                if (array_key_exists($cRec->id, $exRecs)) {
                    if (in_array($exRecs[$cRec->id]->createdBy, $uArr)) {
                        $r->customerSince = array_key_exists($cRec->id, $datesArr['sales']) ? $datesArr['sales'][$cRec->id] : null;
                    }
                }
                
                $r->supplierSince = array_key_exists($cRec->id, $datesArr['purchases']) ? $datesArr['purchases'][$cRec->id] : null;
                
                if (isset($r->overdueSales) || isset($r->customerSince) || isset($r->id) || isset($r->supplierSince)) {
                    $saveArray[$cRec->id] = $r;
                }
            }
            
            // Запис на новите данни
            if (countR($saveArray)) {
                $this->saveArray($saveArray);
            }
        }
    }
    
    
    /**
     * Всички просрочени продажби
     *
     * @param int $contragentClassId
     *
     * @return array $res
     */
    private static function getDealData($contragentClassId)
    {
        $res = array();
       
        foreach (array('sales' => 'sales_Sales', 'purchases' => 'purchase_Purchases') as $key => $Cls){
            
            // Сумиране и преброяване на всички сделки на контрагента
            $dQuery = $Cls::getQuery();
            $dQuery->XPR('count', 'int', 'COUNT(#id)');
            $dQuery->XPR('amount', 'int', 'SUM(COALESCE(#amountDeal, 0))');
            $dQuery->where("#contragentClassId = {$contragentClassId}");
            $dQuery->where("#state = 'active' OR #state = 'closed'");
            $dQuery->show('id,count,amount,contragentId');
            $dQuery->groupBy('contragentId');
            
            while ($sRec = $dQuery->fetch()) {
                $res[$key][$sRec->contragentId]['total']['count'] = $sRec->count;
                $res[$key][$sRec->contragentId]['total']['amount'] = $sRec->amount;
            }
            
            // Сумиране и преброяване на всички просрочени сделки на контрагента
            $dQuery2 = $Cls::getQuery();
            $dQuery2->XPR('count', 'int', 'COUNT(#id)');
            $dQuery2->XPR('amount', 'int', 'SUM(COALESCE(#amountBl, 0))');
            $dQuery2->where("#contragentClassId = {$contragentClassId}");
            $dQuery2->where("#state = 'active' AND #paymentState = 'overdue'");
            $dQuery2->show('count,amount,contragentId');
            $dQuery2->groupBy('contragentId');
            while ($sRec2 = $dQuery2->fetch()) {
                $res[$key][$sRec2->contragentId]['overdue']['count'] = $sRec2->count;
                $res[$key][$sRec2->contragentId]['overdue']['amount'] = $sRec2->amount;
            }
        }
        
        return $res;
    }
}
