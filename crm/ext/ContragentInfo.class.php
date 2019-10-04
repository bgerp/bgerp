<?php


/**
 * Информация за контрагенти
 *
 * @category  bgerp
 * @package   crm
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
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
    public $listFields = 'contragentId=Контрагент,customerSince=Първо задание,overdueSales=Просрочени сделки,totalDealsCount=Общо сделки->Брой,totalDealsAmount=Общо сделки->Сума,overdueDealsCount=Просрочени сделки->Брой,overdueDealsAmount=Просрочени сделки->Сума,currencyId=Валута,createdBy';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('contragentClassId', 'int');
        $this->FLD('contragentId', 'int', 'tdClass=leftCol wrapText');
        $this->FLD('customerSince', 'date');
        $this->FLD('overdueSales', 'enum(yes=Да)');
        $this->FLD('totalDeals', 'blob(serialize, compress)', 'caption=Сделки->Общо');
        $this->FLD('overdueDeals', 'blob(serialize, compress)', 'caption=Сделки->Просрочени');
        
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
     * Връща датата на активиране на най-старата продажба
     *
     * @param int $contragentClassId - ид на класа на контрагента
     * @param int $contragentId      - ид на контрагента
     *
     * @return datetime|null - най-ранната дата от която е клиент
     */
    private static function getFirstSaleDate($contragentClassId, $contragentId)
    {
        // намиране на най-старата активна/приключена сделка на контрагента
        $saleQuery = sales_Sales::getQuery();
        $saleQuery->XPR('customerSince', 'date', 'MIN(DATE(COALESCE(#activatedOn, #valior)))');
        $saleQuery->where("#contragentClassId = {$contragentClassId} AND #contragentId = {$contragentId}");
        $saleQuery->where("#state = 'active' || #state = 'closed'");
        $saleQuery->show('customerSince');
        
        $found = $saleQuery->fetch();
        
        return (is_object($found)) ? $found->customerSince : null;
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
        
        if ($cInfo = crm_ext_ContragentInfo::getByContragent($mvc->getClassId(), $rec->id)) {
            $currencyId = acc_Periods::getBaseCurrencyCode();
            
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
     *
     * @return datetime|null - най-ранната дата от която е клиент
     */
    public static function getCustomerSince($contragentClassId, $contragentId)
    {
        $exRec = self::getByContragent($contragentClassId, $contragentId);
        
        if (empty($exRec->customerSince)) {
            $customerSince = self::getFirstSaleDate($contragentClassId, $contragentId);
            if (!empty($customerSince)) {
                if (is_object($exRec)) {
                    $exRec->customerSince = $customerSince;
                    $fields = 'customerSince';
                } else {
                    $fields = null;
                    $exRec = self::prepareNewRec($contragentClassId, $contragentId, array('customerSince' => $customerSince));
                }
                
                self::save($exRec, $fields);
            }
        }
        
        return $exRec->customerSince;
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
     * Всички дати от кога са клиентите
     *
     * @param int $contragentClassId
     */
    private static function getFirstSaleDates($contragentClassId)
    {
        $res = array();
        $saleQuery = sales_Sales::getQuery();
        $saleQuery->XPR('customerSince', 'date', 'MIN(DATE(COALESCE(#activatedOn, #valior)))');
        $saleQuery->where("#state = 'active' || #state = 'closed'");
        $saleQuery->where("#contragentClassId = {$contragentClassId}");
        $saleQuery->show('contragentId,customerSince');
        $saleQuery->groupBy('contragentId');
        
        while ($sRec = $saleQuery->fetch()) {
            if (!empty($sRec->customerSince)) {
                $res[$sRec->contragentId] = $sRec->customerSince;
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
            $customersSince = self::getFirstSaleDates($classId);
            $sales = self::getSalesdata($classId);
            
            // За всеки
            while ($cRec = $cQuery->fetch()) {
                if (array_key_exists($cRec->id, $exRecs)) {
                    $r = $exRecs[$cRec->id];
                } else {
                    $r = self::prepareNewRec($classId, $cRec->id, array('createdOn' => $now));
                }
                
                $total = is_array($sales[$cRec->id]['total']) ? $sales[$cRec->id]['total'] : array();
                $overdues = is_array($sales[$cRec->id]['overdue']) ? $sales[$cRec->id]['overdue'] : array();
                
                $r->overdueSales = count($overdues) ? 'yes' : null;
                $r->totalDeals = count($total) ? $total : null;
                $r->overdueDeals = count($overdues) ? $overdues : null;
                
                //..и е стар запис създаден от системата
                if (array_key_exists($cRec->id, $exRecs)) {
                    if (in_array($exRecs[$cRec->id]->createdBy, $uArr)) {
                        $r->customerSince = array_key_exists($cRec->id, $customersSince) ? $customersSince[$cRec->id] : null;
                    }
                }
                
                if (isset($r->overdueSales) || isset($r->customerSince) || isset($r->id)) {
                    $saveArray[$cRec->id] = $r;
                }
            }
            
            // Запис на новите данни
            if (count($saveArray)) {
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
    private static function getSalesdata($contragentClassId)
    {
        $res = array();
        
        // Сумиране и преброяване на всички сделки на контрагента
        $saleQuery = sales_Sales::getQuery();
        $saleQuery->XPR('count', 'int', 'COUNT(#id)');
        $saleQuery->XPR('amount', 'int', 'SUM(#amountDeal)');
        $saleQuery->where("#contragentClassId = {$contragentClassId}");
        $saleQuery->where("#state = 'active' OR #state = 'closed'");
        $saleQuery->show('count,amount,contragentId');
        $saleQuery->groupBy('contragentId');
        while ($sRec = $saleQuery->fetch()) {
            $res[$sRec->contragentId]['total']['count'] = $sRec->count;
            $res[$sRec->contragentId]['total']['amount'] = $sRec->amount;
        }
        
        // Сумиране и преброяване на всички просрочени сделки на контрагента
        $saleQuery2 = sales_Sales::getQuery();
        $saleQuery2->XPR('count', 'int', 'COUNT(#id)');
        $saleQuery2->XPR('amount', 'int', 'SUM(#amountBl)');
        $saleQuery2->where("#contragentClassId = {$contragentClassId}");
        $saleQuery2->where("#state = 'active' AND #paymentState = 'overdue'");
        $saleQuery2->show('count,amount,contragentId');
        $saleQuery2->groupBy('contragentId');
        while ($sRec2 = $saleQuery2->fetch()) {
            $res[$sRec2->contragentId]['overdue']['count'] = $sRec2->count;
            $res[$sRec2->contragentId]['overdue']['amount'] = $sRec2->amount;
        }
        
        return $res;
    }
}
