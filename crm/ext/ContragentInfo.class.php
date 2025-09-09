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
    public $listFields = 'contragentId=Контрагент,customerSince=Клиент от,supplierSince=Доставчик от,haveOverdueSales=Просрочия,activeSalesCount,activeSalesAmount,totalSalesCount,totalSalesAmount,overdueSalesCount,overdueSalesAmount,overdueSalesThreshold,overdueSalesThresholdParam,activePurchaseCount,activePurchaseAmount,totalPurchaseCount,totalPurchaseAmount,createdBy';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('contragentClassId', 'int');
        $this->FLD('contragentId', 'int', 'tdClass=leftCol wrapText');
        $this->FLD('customerSince', 'date');
        $this->FLD('supplierSince', 'date');
        $this->FLD('haveOverdueSales', 'enum(yes=Да,no=Не)');
        $this->FLD('activeSalesCount', 'int', 'caption=Продажби (Активни)->Брой');
        $this->FLD('activeSalesAmount', 'double', 'caption=Продажби (Активни)->Сума');
        $this->FLD('totalSalesCount', 'int', 'caption=Продажби (Всички)->Брой');
        $this->FLD('totalSalesAmount', 'double', 'caption=Продажби (Всички)->Сума');
        $this->FLD('overdueSalesCount', 'int', 'caption=Продажби (Просрочени)->Брой');
        $this->FLD('overdueSalesAmount', 'double', 'caption=Продажби (Просрочени)->Сума');
        $this->FLD('overdueSalesThreshold', 'double', 'caption=Продажби (Просрочени)->дни × сума');
        $this->FLD('overdueSalesThresholdParam', 'double', 'caption=Продажби (Просрочени)->Праг');
        $this->FLD('activePurchaseCount', 'int', 'caption=Покупки (Активни)->Брой');
        $this->FLD('activePurchaseAmount', 'double', 'caption=Покупки (Активни)->Сума');
        $this->FLD('totalPurchaseCount', 'int', 'caption=Покупки (Всички)->Брой');
        $this->FLD('totalPurchaseAmount', 'double', 'caption=Покупки (Всички)->Сума');

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
        $baseCurrencyCode = acc_Periods::getBaseCurrencyCode();
        foreach (array('activeSalesAmount', 'totalSalesAmount', 'overdueSalesAmount', 'overdueSalesThreshold', 'activePurchaseAmount', 'totalPurchaseAmount') as $fld){
            $data->listFields[$fld] .= "|* <small style='font-weight:normal'>({$baseCurrencyCode})</small>";
        }
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
     * @param array $fields
     *
     * @return void
     */
    public static function extendRow($mvc, &$row, $rec, $fields = array())
    {
        $baseCurrencyCode = acc_Periods::getBaseCurrencyCode();
        $customerSince = crm_ext_ContragentInfo::getCustomerSince($mvc->getClassId(), $rec->id);
        if (!empty($customerSince)) {
            $row->customerSince = core_Type::getByName('date')->toVerbal($customerSince);
        }
       
        $supplierSince = crm_ext_ContragentInfo::getCustomerSince($mvc->getClassId(), $rec->id, 'supplier');
        if (!empty($supplierSince)) {
            $row->supplierSince = core_Type::getByName('date')->toVerbal($supplierSince);
        }
        
        if ($cInfo = crm_ext_ContragentInfo::getByContragent($mvc->getClassId(), $rec->id)) {
            foreach (array('activeSalesCount', 'totalSalesCount', 'overdueSalesCount', 'activePurchaseCount', 'totalPurchaseCount') as $countFld) {
                if (isset($cInfo->{$countFld})) {
                    $row->{$countFld} = core_Type::getByName('int')->toVerbal($cInfo->{$countFld});
                    $Cls = in_array($countFld, array('totalPurchaseCount', 'activePurchaseCount')) ? 'purchase_Purchases' : 'sales_Sales';

                    $type = ($countFld == 'overdueSalesCount') ? 'overdue' : ($countFld == 'activeSalesCount' ? 'active' : 'clAndAct');
                    if ($Cls::haveRightFor('list')) {
                        $row->{$countFld} = ht::createLink($row->{$countFld}, array($Cls, 'list', 'type' => $type, 'folder' => $rec->folderId, 'selectPeriod' => 'gr0'));
                    }
                }
            }

            foreach (array('activeSalesAmount', 'totalSalesAmount', 'overdueSalesAmount', 'activePurchaseAmount', 'totalPurchaseAmount', 'overdueSalesThreshold', 'overdueSalesThresholdParam') as $amountFld) {
                if (isset($cInfo->{$amountFld})) {
                    $row->{$amountFld} = core_Type::getByName('double(decimals=2)')->toVerbal($cInfo->{$amountFld});
                    if ($fields['-single']) {
                        $row->{$amountFld} = currency_Currencies::decorate($row->{$amountFld}, $baseCurrencyCode, true);
                    }

                    if(!haveRole('ceo,seePriceSale') || haveRole('noPrice')){
                         $row->{$amountFld} = doc_plg_HidePrices::getBuriedElement();
                    }
                }
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
        $res = array('sales' => array(), 'purchases' => array());
        
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
        $res = "Събиране на контрагентски данни";
        foreach ($contragentClasses as $classId) {
            $exRecs = (array_key_exists($classId, $existing)) ? $existing[$classId] : array();

            // За всички неоттеглени контрагенти
            $ContragentClass = cls::get($classId);

            $cQuery = $ContragentClass::getQuery();
            $cQuery->where('#folderId IS NOT NULL');
            $cQuery->where("#state != 'rejected'");
            $cQuery->show('folderId,id');
            
            // Дигане на тайм лимита за всеки случай
            $count = $cQuery->count() * 0.048;
            core_App::setTimeLimit($count, false, 300);
            
            // От кога са клиенти
            $datesArr = self::getFirstDates($classId);
            $dealData = self::getDealData($classId);

            // За всеки
            $newRecs = array();
            while ($cRec = $cQuery->fetch()) {
                $r = self::prepareNewRec($classId, $cRec->id, array('createdOn' => $now));
                $total = is_array($dealData['sales'][$cRec->id]['total']) ? $dealData['sales'][$cRec->id]['total'] : array();
                $overDues = is_array($dealData['sales'][$cRec->id]['overdue']) ? $dealData['sales'][$cRec->id]['overdue'] : array();
                $active = is_array($dealData['sales'][$cRec->id]['active']) ? $dealData['sales'][$cRec->id]['active'] : array();
                $purchasesTotal = is_array($dealData['purchases'][$cRec->id]['total']) ? $dealData['purchases'][$cRec->id]['total'] : array();
                $purchasesActive = is_array($dealData['purchases'][$cRec->id]['active']) ? $dealData['purchases'][$cRec->id]['active'] : array();

                $r->haveOverdueSales = 'no';
                $r->overdueSalesCount = $r->overdueSalesAmount = $r->overdueSalesThreshold = $r->overdueSalesThresholdParam =  null;
                if(countR($overDues)){
                    $r->overdueSalesCount = $overDues['count'];
                    $r->overdueSalesAmount = round($overDues['amount'], 2);
                    $r->overdueSalesThreshold = $overDues['threshold'];
                    $r->overdueSalesThresholdParam = $overDues['thresholdParamValue'];
                    if(round($overDues['threshold'], 2) > round($overDues['thresholdParamValue'], 2)){
                        $r->haveOverdueSales = 'yes';
                    }
                }

                $r->activeSalesCount = $r->activeSalesAmount = null;
                if(countR($active)){
                    $r->activeSalesCount = $active['count'];
                    $r->activeSalesAmount = round($active['amount'], 2);
                }

                $r->totalSalesCount = $r->totalSalesAmount = null;
                if(countR($total)){
                    $r->totalSalesCount = $total['count'];
                    $r->totalSalesAmount = round($total['amount'], 2);
                }

                $r->totalPurchaseCount = $r->totalPurchaseAmount = null;
                if(countR($purchasesTotal)){
                    $r->totalPurchaseCount = $purchasesTotal['count'];
                    $r->totalPurchaseAmount = round($purchasesTotal['amount'], 2);
                }

                $r->activePurchaseCount = $r->activePurchaseAmount = null;
                if(countR($purchasesActive)){
                    $r->activePurchaseCount = $purchasesActive['count'];
                    $r->activePurchaseAmount = round($purchasesActive['amount'], 2);
                }

                //..и е стар запис създаден от системата
                if (array_key_exists($cRec->id, $exRecs)) {
                    if (in_array($exRecs[$cRec->id]->createdBy, $uArr)) {
                        $r->customerSince = array_key_exists($cRec->id, $datesArr['sales']) ? $datesArr['sales'][$cRec->id] : null;
                    } else {
                        $r->customerSince = $exRecs[$cRec->id]->customerSince;
                    }
                } else {
                    $r->customerSince = array_key_exists($cRec->id, $datesArr['sales']) ? $datesArr['sales'][$cRec->id] : null;
                }
                
                $r->supplierSince = array_key_exists($cRec->id, $datesArr['purchases']) ? $datesArr['purchases'][$cRec->id] : null;
                $save = false;
                $fields = arr::make('customerSince,supplierSince,totalSalesCount,totalSalesAmount,totalPurchaseCount,totalPurchaseAmount,activePurchaseCount,activePurchaseAmount', true);
                foreach ($fields as $fld){
                    if(isset($r->{$fld})){
                        $save = true;
                        break;
                    }
                }

                if($save){
                    $newRecs[$r->contragentId] = $r;
                }
            }

            $sync = arr::syncArrays($newRecs, $exRecs, 'contragentClassId,contragentId', 'customerSince,supplierSince,haveOverdueSales,activeSalesCount,activeSalesAmount,totalSalesCount,totalSalesAmount,overdueSalesCount,overdueSalesAmount,overdueSalesThreshold,overdueSalesThresholdParam,totalPurchaseCount,totalPurchaseAmount,activePurchaseCount,activePurchaseAmount');

            $i = countR($sync['insert']);
            $u = countR($sync['update']);

            // Запис на новите данни
            if ($i) {
                $this->saveArray($sync['insert']);
            }

            if ($u) {
                $this->saveArray($sync['update'], 'id,customerSince,supplierSince,haveOverdueSales,activeSalesCount,activeSalesAmount,totalSalesCount,totalSalesAmount,overdueSalesCount,overdueSalesAmount,overdueSalesThreshold,overdueSalesThresholdParam,totalPurchaseCount,totalPurchaseAmount,activePurchaseCount,activePurchaseAmount');
            }

            $res .= "<br>{$ContragentClass->className}- I:{$i} / U: {$u}";
        }

        return $res;
    }
    
    
    /**
     * Всички просрочени продажби
     *
     * @param int $contragentClassId
     * @return array $res
     */
    private static function getDealData($contragentClassId)
    {
        $res = array();
        $baseCurrencyId = acc_Periods::getBaseCurrencyCode();
        foreach (array('sales' => 'sales_Sales', 'purchases' => 'purchase_Purchases') as $key => $Cls){
            
            // Сумиране и преброяване на всички сделки на контрагента
            $dQuery = $Cls::getQuery();
            $dQuery->where("#contragentClassId = {$contragentClassId}");
            $dQuery->where("#state IN ('active', 'closed')");
            $dQuery->show('amountDeal,overdueAmountPerDays,contragentId,paymentState,contragentClassId,currencyId,state,overdueAmount');

            $paramCache = array();
            while ($sRec = $dQuery->fetch()) {
                $periodCurrencyId = acc_Periods::getBaseCurrencyCode($sRec->valior);
                $amountInCurrentBaseCurrency = currency_CurrencyRates::convertAmount($sRec->amountDeal, null, $periodCurrencyId, $baseCurrencyId);

                $res[$key][$sRec->contragentId]['total']['count'] += 1;
                $res[$key][$sRec->contragentId]['total']['amount'] += $amountInCurrentBaseCurrency;

                if($sRec->state == 'active'){
                    $res[$key][$sRec->contragentId]['active']['count'] += 1;
                    $res[$key][$sRec->contragentId]['active']['amount'] += $amountInCurrentBaseCurrency;
                    if($key == 'sales'){

                        // Ако са продажби ще се смятат отделно активните и просрочените
                        if($sRec->paymentState == 'overdue'){
                            $amountInCurrentBaseCurrency = currency_CurrencyRates::convertAmount($sRec->overdueAmount, null, $periodCurrencyId, $baseCurrencyId);
                            $res[$key][$sRec->contragentId]['overdue']['count'] += 1;
                            $res[$key][$sRec->contragentId]['overdue']['amount'] += $amountInCurrentBaseCurrency;

                            // Колко леводни е просрочието
                            $amountInCurrentBaseCurrency = currency_CurrencyRates::convertAmount($sRec->overdueAmountPerDays, null, $periodCurrencyId, $baseCurrencyId);
                            $res[$key][$sRec->contragentId]['overdue']['threshold'] += $amountInCurrentBaseCurrency;
                            if(!array_key_exists($sRec->contragentId, $paramCache)){
                                $paramCache[$sRec->contragentId] = cond_Parameters::getParameter($sRec->contragentClassId, $sRec->contragentId, 'saleOverdueAmount');
                            }
                            $res[$key][$sRec->contragentId]['overdue']['thresholdParamValue'] = $paramCache[$sRec->contragentId];
                        }
                    }
                }
            }
        }

        return $res;
    }


    /**
     * Връща подходящата иконка за контрагента, спрямо състоянието на сделките
     *
     * @param core_Mvc $mvc
     * @param int $id
     * @return string $icon
     */
    public static function getContragentIcon($mvc, $id)
    {
        if (core_Users::isContractor()) return $mvc->icons['standart'];

        $icon = $mvc->icons['noDeals'];
        if ($extRec = crm_ext_ContragentInfo::getByContragent($mvc->getClassId(), $id)) {
            $total = $extRec->totalSalesCount + $extRec->totalPurchaseCount;
            if($extRec->haveOverdueSales == 'yes') {
                $icon = $mvc->icons['overdueSales'];
            } elseif($extRec->activeSalesCount || $extRec->activePurchaseCount) {
                $icon = $mvc->icons['activeDeals'];
            } elseif($total) {
                $icon = $mvc->icons['standart'];
            }
        }

        return $icon;
    }


    /**
     * Подготовка на филтър формата
     *
     * @param core_Mvc $mvc
     * @param StdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->FLD('folder', 'key2(mvc=doc_Folders,select=title,allowEmpty,coverInterface=crm_ContragentAccRegIntf)', 'caption=Контрагент');
        $data->listFilter->FLD('type', 'enum(all=Всички,overdue=Просрочия,empty=Празни,withoutActive=Без активни,havePurchase=С покупки,haveSales=С продажби)', 'caption=Вид');

        $data->listFilter->showFields = 'folder,type';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->input();
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');

        if($rec = $data->listFilter->rec){
            if(!empty($rec->folder)){
                $Cover = doc_Folders::getCover($rec->folder);
                $data->query->where("#contragentClassId = {$Cover->getClassId()} AND #contragentId = {$Cover->that}");
            }

            if($rec->type != 'all'){
                if($rec->type == 'haveSale'){
                    $data->query->where("#totalSalesCount > 0");
                } elseif($rec->type == 'havePurchase'){
                    $data->query->where("#totalPurchaseCount > 0");
                } elseif($rec->type == 'overdue'){
                    $data->query->where("#haveOverdueSales = 'yes'");
                } elseif($rec->type == 'empty'){
                    $data->query->where("#totalSalesCount IS NULL AND #totalPurchaseCount IS NULL");
                } elseif($rec->type == 'withoutActive'){
                    $data->query->where("#activeSalesCount IS NULL");
                }
            }
        }
    }
}
