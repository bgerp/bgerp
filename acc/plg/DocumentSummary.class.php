<?php


/**
 * Плъгин за филтриране на документи с вальор по ключови думи и дата,
 * показва и Обобщение на резултатите от списъчния изглед
 *
 * За Обобщението: Показва в малка таблица над списъчния изглед обобщена
 * информация за намерените резултати като брой и други.
 * За да се посочи в модела, че на дадено поле трябва да се извади
 * обобщаваща информация е нужно да се дефинира параметър "summary="
 *
 *
 * Възможни стойности на 'summary':
 * summary = amount - Служи за обобщение на числово поле което представлява
 * парична сума. Обощения резултат се показва в неговата равностойност
 * в основната валута за периода. По дефолт се приема, че полето в което
 * е описано в коя валута е сумата е 'currencyId'. Ако полето се казва
 * другояче се дефинира константата 'filterCurrencyField' със стойност
 * името на полето съдържащо валутата.
 * summary = quantity - изчислява сумарната стойност на поле което съдържа
 * някаква бройка (като брой продукти и други)
 *
 *
 * За Филтър формата:
 * Създава филтър форма която филтрира документите по зададен времеви период
 * и пълнотекстото поле (@see plg_Search). По дефолт приема, че полето
 * по която дата ще се търси е "valior". За документи където полето
 * се казва по друг начин се дефинира константата 'filterDateField' която
 * показва по кое поле ще се филтрира
 *
 * За търсене по дата, когато документа има начална и крайна дата се дефинират
 * 'filterFieldDateFrom' и 'filterFieldDateTo'
 *
 * За кое поле да се изпозлва за търсене по потребители се дефинира - 'filterFieldUsers'
 *
 * Дали да се попълва филтъра на дата по дефолт 'filterAutoDate'
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class acc_plg_DocumentSummary extends core_Plugin
{
    /**
     * Масив с класове и съответните роли, които се изискват за private single на документа
     */
    public static $rolesAllMap = array(
        'purchase_Invoices' => 'invoiceAll',
        'sales_Invoices' => 'invoiceAll',
        'sales_Proformas' => 'invoiceAll',
        'store_ShipmentOrders' => 'storeAll',
        'store_Receipts' => 'storeAll',
        'store_Transfers' => 'storeAll',
        'store_ConsignmentProtocols' => 'storeAll',
        'store_InventoryNotes' => 'storeAll',
        'purchase_Services' => 'storeAll',
        'sales_Services' => 'storeAll',
        'rack_Movements' => 'storeAll',
        'store_Stores' => 'storeAll',
        'bank_IncomeDocuments' => 'bankAll',
        'bank_SpendingDocuments' => 'bankAll',
        'bank_ExchangeDocument' => 'bankAll',
        'bank_InternalMoneyTransfer' => 'bankAll',
        'cash_Pko' => 'cashAll',
        'cash_Rko' => 'cashAll',
        'cash_InternalMoneyTransfer' => 'cashAll',
        'cash_ExchangeDocument' => 'cashAll',
        'sales_Sales' => 'saleAll',
        'sales_Quotations' => 'saleAll',
        'sales_Proformas' => 'saleAll',
        'sales_Services' => 'saleAll',
        'purchase_Purchases' => 'purchaseAll',
        'planning_DirectProductionNote' => 'planningAll,storeAll',
        'planning_ConsumptionNotes' => 'planningAll,storeAll',
        'planning_ReturnNotes' => 'planningAll,storeAll',
        'planning_Jobs' => 'planningAll',
        'planning_Tasks' => 'planningAll',
        'hr_Sickdays' => 'hrAll',
        'hr_Trips' => 'hrAll',
        'hr_Leaves' => 'hrAll',
    );
    
    
    /**
     * Извиква се след описанието на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        // Проверка за приложимост на плъгина към зададения $mvc
        static::checkApplicability($mvc);
        
        setIfNot($mvc->canViewpsingle, 'powerUser');
        setIfNot($mvc->hidePeriodFilter, false);
        setIfNot($mvc->filterDateField, 'valior');
        setIfNot($mvc->orderByDateField, true);
        setIfNot($mvc->filterCurrencyField, 'currencyId');
        setIfNot($mvc->rememberListFilterFolderId, false);
        setIfNot($mvc->filterFieldUsers, 'createdBy');
        setIfNot($mvc->filterAllowState, true);
        setIfNot($mvc->defaultListFilterState, 'all');

        setIfNot($mvc->termDateFld, null);
        setIfNot($mvc->showNullDateFields, false);

        $mvc->filterRolesForTeam .= ',' . acc_Setup::get('SUMMARY_ROLES_FOR_TEAMS');
        $mvc->filterRolesForTeam = trim($mvc->filterRolesForTeam, ',');
        
        $mvc->filterRolesForAll .= ',' . acc_Setup::get('SUMMARY_ROLES_FOR_ALL');
        $mvc->filterRolesForAll = trim($mvc->filterRolesForAll, ',');
        
        // Добавяме глобалните роли за съответния клас да може да филтрират всички
        $rolesAll = self::$rolesAllMap[$mvc->className];
        if ($rolesAll) {
            $rolesAllArr = explode(',', $rolesAll);
            foreach ($rolesAllArr as $roleStr) {
                $roleStr = trim($roleStr);
                $mvc->filterRolesForAll .= ',' . $roleStr . 'Global';
                $mvc->filterRolesForTeam .= ',' . $roleStr;
            }
        }
        $mvc->filterRolesForTeam = trim($mvc->filterRolesForTeam, ',');
        $rolesForTeamsArr = arr::make($mvc->filterRolesForTeam, true);
        $mvc->filterRolesForTeam = implode('|', $rolesForTeamsArr);

        $mvc->filterRolesForAll = trim($mvc->filterRolesForAll, ',');
        $rolesForAllArr = arr::make($mvc->filterRolesForAll, true);
        $mvc->filterRolesForAll = implode('|', $rolesForAllArr);

        setIfNot($mvc->filterAutoDate, true);
        if(!$mvc->hidePeriodFilter){
            $mvc->_plugins = arr::combine(array('Избор на период' => cls::get('plg_SelectPeriod')), $mvc->_plugins);
        }

        if (!$mvc->fields['createdOn']) {
            $mvc->FLD('createdOn', 'datetime(format=smartTime)', 'caption=Създаване||Created, notNull, input=none');
        }

        if (!$mvc->fields['createdBy']) {
            $mvc->FLD('createdBy', 'key(mvc=core_Users)', 'caption=Създал||Creator, notNull, input=none');
        }

        $indexName = str::convertToFixedKey(str::phpToMysqlName(implode('_', arr::make('createdOn'))));
        if (!$mvc->dbIndexes[$indexName]) {
            $mvc->setDbIndex('createdOn');
        }
    }


    /**
     * Изпълнява се преди запис
     */
    public function on_BeforeSave($mvc, $id, $rec, $fields = null)
    {
        // Ако създаваме нов документ и ...
        if (!$rec->id) {
            // Задаваме стойностите на created полетата
            if (!isset($rec->createdBy)) {
                $rec->createdBy = Users::getCurrent() ? Users::getCurrent() : 0;
            }

            if (!isset($rec->createdOn)) {
                $rec->createdOn = dt::verbal2Mysql();
            }
        }
    }
    
    
    /**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        // Проверка за права за частния сингъл
        if ($action == 'viewpsingle') {
            $rolesAll = self::$rolesAllMap[$mvc->className];
            if (!$rolesAll || !haveRole($rolesAll, $userId)) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Проверява дали този плъгин е приложим към зададен мениджър
     *
     * @param core_Mvc $mvc
     *
     * @return bool
     */
    protected static function checkApplicability($mvc)
    {
        // Прикачане е допустимо само към наследник на core_Manager ...
        if (!$mvc instanceof core_Manager) {
            
            return false;
        }
        
        if (!$mvc->getInterface('acc_TransactionSourceIntf')) {
            
            return false;
        }
        
        return true;
    }


    /**
     * Подготовка на филтър формата
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->layout = new ET(tr('|*' . getFileContent('acc/plg/tpl/FilterForm.shtml')));
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');

        if(!$mvc->hidePeriodFilter){
            $data->listFilter->FNC('from', 'date', 'width=6em,caption=От,silent');
            $data->listFilter->FNC('to', 'date', 'width=6em,caption=До,silent');

            $dateFields = arr::make($mvc->filterDateField, true);
            $userFields = arr::make($mvc->filterFieldUsers, true);
            $userFields = (countR($userFields) && array_key_exists('createdBy', $userFields)) ? array() : $userFields;
            if(countR($userFields) && isset($mvc->fields['createdBy']) && !array_key_exists('createdBy', $userFields)){
                $userFields['createdBy'] = 'createdBy';
            }

            $flds = $dateFields + $userFields;

            if (countR($flds)) {
                $opt = array();
                $defaultFilterDateField = null;
                foreach ($flds as $f) {
                    if (!$defaultFilterDateField) {
                        $defaultFilterDateField = $f;
                    }
                    $caption = $data->listFilter->getField($f)->caption;
                    if (strpos($caption, '->')) {
                        list($l, $r) = explode('->', $caption);
                        $caption = tr($l) . ' » ' . tr($r);
                    }
                    $opt[] = $f . '=' . $caption;
                }
                $data->listFilter->FLD('filterDateField', 'enum(' . implode(',', $opt) . ')', 'width=6em,caption=Филтър по||Filter by,silent,input');
                $showFilterDateField = ',filterDateField';
            } else {
                $showFilterDateField = null;
            }

            if (!isset($data->listFilter->fields['Rejected'])) {
                $data->listFilter->FNC('Rejected', 'int', 'input=hidden');
            }
            $data->listFilter->setDefault('Rejected', Request::get('Rejected', 'int'));

            if ($mvc->filterAutoDate === true) {
                $data->listFilter->setDefault('from', date('Y-m-01'));
                $data->listFilter->setDefault('to', date('Y-m-t', strtotime(dt::now())));
            }
        }
        
        $fields = $data->listFilter->selectFields();
        
        if (isset($fields['search'])) {
            $data->listFilter->showFields .= 'search';
        }

        if(!$mvc->hidePeriodFilter){
            $data->listFilter->showFields .=  ((!empty($data->listFilter->showFields) ? ',' : '')) . 'from, to' . $showFilterDateField;
        }
        
        if ($isDocument = cls::haveInterface('doc_DocumentIntf', $mvc)) {

            // Филтър по "Наша фирма", ако е инсталиран пакета за многофирменост
            $mvc->invoke('afterGetDocumentSummaryListFields', array(&$data));
            $data->listFilter->FNC('users', "users(rolesForAll={$mvc->filterRolesForAll},rolesForTeams={$mvc->filterRolesForTeam}, showClosedGroups)", 'caption=Потребители,silent,autoFilter,remember');
            $data->listFilter->FNC('folder', 'key2(mvc=doc_FoldersProxy, allowEmpty, selectSourceArr=doc_Folders::getSelectArr, forceProxy)', 'caption=Папка,silent,after=users');
            $data->listFilter->showFields .= ',folder';
            
            $cKey = $mvc->className . '|' . core_Users::getCurrent();
            $haveUsers = false;
            
            if ($lastUsers = core_Permanent::get('userFilter' . $cKey)) {
                $usedUsers = $lastUsers;
                $type = $data->listFilter->getField('users')->type;
                $type->prepareOptions();
                foreach ($type->options as $key => $optObj) {
                    if ($lastUsers == $optObj->keylist || $key == $lastUsers) {
                        $lastUsers = $optObj->keylist;
                        $haveUsers = true;
                        break;
                    }
                }
            }

            if(!$mvc->hidePeriodFilter){
                if ($lastPeriod = core_Permanent::get('periodFilter' . $cKey)) {
                    if (!Request::get('selectPeriod')) {
                        Request::push(array('selectPeriod' => $lastPeriod));
                        list($from, $to) = plg_SelectPeriod::getFromTo($lastPeriod);
                        $data->listFilter->rec->from = $from;
                        $data->listFilter->rec->to = $to;
                    }
                }
            }
            
            if ($mvc->filterFieldUsers !== false) {
                if (!$haveUsers) {
                    $data->listFilter->setDefault('users', keylist::addKey('', core_Users::getCurrent()));
                } else {
                    $data->listFilter->setDefault('users', $lastUsers);
                }
                
                $data->listFilter->showFields .= ',users';
            }

            if($mvc->rememberListFilterFolderId) {
                if ($lastFolderId = core_Permanent::get('folderFilter' . $cKey)) {
                    $data->listFilter->setDefault('folder', $lastFolderId);
                }
            }
        }

        // Добавяме към формата за търсене търсене и по Състояние
        if (!Request::get('Rejected', 'int')) {
            if($mvc->filterAllowState){
                if($mvc->getField('state', false)){
                    $stateOptions = $mvc->getFieldType('state')->options;
                    $stateOptions = array_intersect_key($stateOptions, arr::make(array('draft', 'pending', 'active', 'waiting', 'stopped', 'wakeup', 'closed', 'template'), true));
                    foreach ($stateOptions as $k => $v){
                        $stateOptions[$k] = is_object($v) ? $v->title : $v;
                    }
                    $stateOptions = array('all' => 'Всички') + $stateOptions;
                    $stateOptionsString = arr::fromArray($stateOptions);
                    $data->listFilter->FNC('fState', "enum({$stateOptionsString})", 'caption=Състояние,input,silent');
                    $data->listFilter->showFields .= ',fState';
                    $data->listFilter->setDefault('fState', $mvc->defaultListFilterState);
                }
            }
        }

        // Активиране на филтъра
        $data->listFilter->input($data->listFilter->showFields, 'silent');
        
        // Ако формата за търсене е изпратена
        if ($filter = $data->listFilter->rec) {
            if(!empty($filter->fState) && $filter->fState != 'all'){
                $data->query->where("#state = '{$filter->fState}'");
            }

            // Записваме в кеша последно избраните потребители
            if ($data->listFilter->isSubmitted() && ($usedUsers = $filter->users)) {
                if (($requestUsers = Request::get('users')) && !is_numeric(str_replace('_', '', $requestUsers))) {
                    $usedUsers = $requestUsers;
                }
                core_Permanent::set('userFilter' . $cKey, $usedUsers, 24 * 60 * 100);
            }
            
            if (($thisPeriod = Request::get('selectPeriod')) && ($thisPeriod != $lastPeriod)) {
                core_Permanent::set('periodFilter' . $cKey, $thisPeriod, 24 * 60 * 100);
            }
            
            // Филтрираме по потребители
            if ($filter->users && $isDocument) {
                $userIds = keylist::toArray($filter->users);

                // Ако не се търси по всички
                if ($usedUsers != 'all_users') {
                    $userArr = implode(',', $userIds);

                    if(in_array($filter->filterDateField, $userFields)){
                        $data->query->where("#{$filter->filterDateField} IN ({$userArr})");
                    } else {
                        $map = array('createdOn' => 'createdBy', 'modifiedOn' => 'modifiedBy', 'activatedOn' => 'activatedBy');
                        $useUserField = isset($map[$filter->filterDateField]) ? $map[$filter->filterDateField] : $mvc->filterFieldUsers;

                        $data->query->where("#{$useUserField} IN ({$userArr})");
                        if(!isset($map[$filter->filterDateField]) && $useUserField != $mvc->filterFieldUsers){
                            $data->query->orWhere("#{$mvc->filterFieldUsers} IS NULL AND #createdBy IN ({$userArr})");
                        }
                    }
                }
            }

            $dateRange = array();
            
            if ($filter->from) {
                $dateRange[0] = $filter->from;
            }
            
            if ($filter->to) {
                $dateRange[1] = $filter->to;
            }

            if (countR($dateRange) == 2) {
                sort($dateRange);
            }
            
            if ($showFilterDateField) {
                $fromField = $filter->filterDateField ? $filter->filterDateField : $defaultFilterDateField;
                if(in_array($fromField, $userFields)){
                    $fromField = $defaultFilterDateField;
                }
                
                $toField = $fromField;
                if($mvc->orderByDateField){
                    $data->query->orderBy($fromField, 'DESC');
                }
            } else {
                $fromField = ($mvc->filterFieldDateTo) ? $mvc->filterFieldDateTo : $mvc->filterDateField;
                $toField = ($mvc->filterFieldDateFrom) ? $mvc->filterFieldDateFrom : $mvc->filterDateField;
            }


            if ($dateRange[0] || $dateRange[1]) {
                $nullCond = '';
                $where = '';

                if ($fromField) {
                    $autoCalcField = $data->listFilter->getFieldParam($fromField, 'autoCalcDateField');

                    if ($dateRange[0] && $dateRange[1]) {

                        $where = "(#{$fromField} >= '[#1#]' AND #{$fromField} <= '[#2#] 23:59:59')";
                        if($autoCalcField){
                            $where .= " OR (#{$fromField} IS NULL AND #{$autoCalcField} >= '[#1#]' AND #{$autoCalcField} <= '[#2#] 23:59:59')";
                            $where = "({$where})";
                        }
                    } else {
                        if ($dateRange[0]) {
                            $where .= "(#{$fromField} >= '[#1#]')";
                            if($autoCalcField){
                                $where .= " OR (#{$fromField} IS NULL AND #{$autoCalcField} >= '[#1#]')";
                                $where = "({$where})";
                            }

                            $oldestAvailableDate = plg_SelectPeriod::getOldestAvailableDate();
                            if($dateRange[0] == $oldestAvailableDate && empty($dateRange[1])){
                                $where .= " OR (#{$fromField} IS NULL)";
                                $where = "({$where})";
                            }
                        }

                        if ($dateRange[1]) {
                            $where .= "(#{$fromField} <= '[#2#] 23:59:59')";
                            if($autoCalcField){
                                $where .= " OR (#{$fromField} IS NULL AND #{$autoCalcField} <= '[#2#] 23:59:59')";
                                $where = "({$where})";
                            }
                        }
                    }

                    if($autoCalcField){
                        $nullCond = " OR #{$fromField} IS NULL";
                    } else {
                        $nullCond = " OR (#{$fromField} IS NULL AND #{$autoCalcField} IS NULL)";
                    }
                }

                if ($toField && ($toField != $fromField)) {
                    if ($dateRange[0] && $dateRange[1]) {
                        $where .= " OR (#{$toField} >= '[#1#]' AND #{$toField} <= '[#2#] 23:59:59')";
                    } else {
                        if ($dateRange[0]) {
                            $where .= " OR (#{$toField} >= '[#1#]')";
                        }

                        if ($dateRange[1]) {
                            $where .= " OR (#{$toField} <= '[#2#] 23:59:59')";
                        }
                    }

                    $nullCond = " OR (#{$fromField} IS NULL AND #{$toField} IS NULL)";
                }

                if (($fromField == 'createdOn') || ($toField == 'createdOn')) {
                    $fF = $mvc->filterDateFrom ? $mvc->filterDateFrom : 'from';
                    $fT = $mvc->filterDateTo ? $mvc->filterDateTo : 'to';
                    if ($data->listFilter->rec->{$fF} || $data->listFilter->rec->{$fT}) {
                        $indexName = str::convertToFixedKey(str::phpToMysqlName(implode('_', arr::make('createdOn'))));
                        $data->query->useIndex($indexName);
                    }
                }

                // NULL записите се показват само ако изрично се изисква
                if($mvc->showNullDateFields === true){
                    $where .= $nullCond;
                }

                $data->query->where(array($where, $dateRange[0], $dateRange[1]));
            }

            if (isset($filter->folder)) {
                unset($data->listFields['folderId']);
                $data->query->where("#folderId = '{$filter->folder}'");
                if($mvc->rememberListFilterFolderId) {
                    if ($filter->folder != $lastFolderId) {
                        core_Permanent::set('folderFilter' . $cKey, $filter->folder, 24 * 60 * 100);
                    }
                }
            }
        }
    }
    
    
    /**
     * След подготовка на записите
     */
    public static function on_AfterPrepareListSummary($mvc, &$res, &$data)
    {
        // Ако няма заявка, да не се изпълнява
        if (!$data->listSummary->query) {
            
            return ;
        }
        
        // Да не се показва при принтиране
        if (Mode::is('printing')) {
            
            return ;
        }
        
        // Ще се преброяват всички неоттеглени документи
        core_Debug::startTimer('RENDER_SUMMARY');
        $sQuery = clone $data->listSummary->query;
        $sQuery->where("#state != 'rejected' OR #state IS NULL");

        $data->listSummary->summary = array();
        $fieldsArr = $data->listSummary->mvc->selectFields('#summary');
        $showFields = arr::make(array_keys($fieldsArr), true);
        $showFields['state'] = 'state';
        if($mvc->getField('rate', false)){
            $showFields['rate'] = 'rate';
        }
        $showFields = implode(',', $showFields);
        $sQuery->show($showFields);


        // Основната валута за периода
        $baseCurrency = acc_Periods::getBaseCurrencyCode();
        $draftCount = $activeCount = $pendingCount = 0;
        while ($rec = $sQuery->fetch()) {
            $mvc->fillSummaryRec($rec, $fieldsArr);
            self::prepareSummary($mvc, $fieldsArr, $rec, $data->listSummary->summary, $baseCurrency);
            if($rec->state == 'draft'){
                $draftCount++;
            } elseif($rec->state == 'pending'){
                $pendingCount++;
            } elseif(in_array($rec->state, array('closed', 'active'))){
                $activeCount++;
            }
        }

        // Добавяне в обобщението на броя активирани и броя чернови документи
        $data->listSummary->summary['countA'] = (object) array('caption' => "<span style='float:right'>" . tr('Активирани') . '</span>', 'measure' => tr('бр') . '.', 'quantity' => $activeCount);
        $data->listSummary->summary['countC'] = (object) array('caption' => "<span style='float:right'>" . tr('Заявки') . '</span>', 'measure' => tr('бр') . '.', 'quantity' => $pendingCount);
        $data->listSummary->summary['countB'] = (object) array('caption' => "<span style='float:right'>" . tr('Чернови') . '</span>', 'measure' => tr('бр') . '.', 'quantity' => $draftCount);
        core_Debug::stopTimer('RENDER_SUMMARY');
    }


    /**
     * Метод по подразбиране допълващ полетата за филтриране
     */
    public static function on_AfterFillSummaryRec($mvc, $res, &$rec, &$summaryFields)
    {

    }


    /**
     * След рендиране на List Summary-то
     */
    public static function on_AfterRenderListSummary($mvc, &$tpl, $data)
    {
        if ($data->listSummary->summary) {
            $tpl = self::renderSummary($data->listSummary->summary);
        }
        
        if(isset($tpl)){
            jquery_Jquery::run($tpl, 'setFormElementsWidth();');
        }
    }
    
    
    /**
     * Подготвя обощаващата информация
     *
     * @param core_Mvc $mvc          - Класа към който е прикачен плъгина
     * @param array    $fieldsArr    - Поле от модела имащо атрибут "summary"
     * @param stdClass $rec          - Запис от модела
     * @param array    $res          - Масив в който ще върнем резултатите
     * @param string   $currencyCode - основната валута за периода
     */
    private static function prepareSummary($mvc, $fieldsArr, $rec, &$res, $currencyCode)
    {
        if (countR($fieldsArr) == 0) {
            
            return;
        }
        
        foreach ($fieldsArr as $fld) {
            if (!array_key_exists($fld->name, $res)) {
                $captionValue = (isset($fld->summaryCaption)) ? $fld->summaryCaption : $fld->caption;
                $captionArr = explode('->', $captionValue);
                $caption = (countR($captionArr) == 2) ? tr($captionArr[0]) . ': ' . tr($captionArr[1]) : tr($captionValue);
                $res[$fld->name] = (object) array('caption' => $caption, 'measure' => '', 'number' => 0);
            }
            
            switch ($fld->summary) {
                case 'amount':
                    $baseAmount = $rec->{$fld->name};
                    if ($mvc->amountIsInNotInBaseCurrency === true && isset($rec->rate)) {
                        $baseAmount *= $rec->rate;
                    }
                    
                    $res[$fld->name]->amount += $baseAmount;
                    $res[$fld->name]->measure = "<span class='cCode'>{$currencyCode}</span>";
                    break;
                case 'quantity':
                    $res[$fld->name]->quantity += $rec->{$fld->name};
                    $res[$fld->name]->measure = tr('бр');
                    break;
            }
        }
    }
    
    
    /**
     * Рендира обобщението
     *
     * @param array $res - Масив от записи за показване
     *
     * @return core_ET $tpl - Шаблон на обобщението
     */
    private static function renderSummary($res)
    {
        // Зареждаме и подготвяме шаблона
        $double = cls::get('type_Double');
        $int = cls::get('type_Int');
        $double->params['decimals'] = 2;
        $tpl = new ET(tr('|*' . getFileContent('acc/plg/tpl/Summary.shtml')));
        $rowTpl = $tpl->getBlock('ROW');
        
        if (countR($res)) {
            foreach ($res as $rec) {
                $row = new stdClass();
                $row->measure = $rec->measure;
                
                if (isset($rec->amount)) {
                    $row->amount = $double->toVerbal($rec->amount);
                    $row->amount = ($rec->amount < 0) ? "<span style='color:red'>{$row->amount}</span>" : $row->amount;
                } elseif (isset($rec->quantity)) {
                    $row->quantity = $int->toVerbal($rec->quantity);
                    $row->quantity = ($rec->quantity < 0) ? "<span style='color:red'>{$row->quantity}</span>" : $row->quantity;
                }
                
                $row->caption = $rec->caption;
                
                $rowTpl->placeObject($row);
                $rowTpl->removeBlocks();
                $rowTpl->append2master();
            }
        }
        
        return $tpl;
    }
}
