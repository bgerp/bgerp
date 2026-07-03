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
        'store_ShipmentOrders' => 'storeAll',
        'store_Receipts' => 'storeAll',
        'store_Transfers' => 'storeAll',
        'store_ConsignmentProtocols' => 'storeAll',
        'store_InventoryNotes' => 'storeAll',
        'purchase_Services' => 'storeAll',
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
        'sales_Proformas' => 'saleAll,invoiceAll',
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
        
        setPartIfNot($mvc, 'canViewpsingle', 'powerUser');
        setPartIfNot($mvc, 'hidePeriodFilter', false);
        setPartIfNot($mvc, 'filterDateField', 'valior');
        setPartIfNot($mvc, 'orderByDateField', true);
        setPartIfNot($mvc, 'filterCurrencyField', 'currencyId');
        setPartIfNot($mvc, 'rememberListFilterFolderId', false);
        setPartIfNot($mvc, 'filterFieldUsers', 'createdBy');
        setPartIfNot($mvc, 'filterAllowState', true);
        setPartIfNot($mvc, 'defaultListFilterState', 'all');

        setPartIfNot($mvc, 'termDateFld', null);
        setPartIfNot($mvc, 'showNullDateFields', false);
        setPartIfNot($mvc, 'amountIsInNotInBaseCurrency', false);

        setPartIfNot($mvc, 'filterFieldDateTo', null);
        setPartIfNot($mvc, 'filterFieldDateFrom', null);
        setPartIfNot($mvc, 'filterDateFrom', null);
        setPartIfNot($mvc, 'filterDateTo', null);
        setPartIfNot($mvc, 'showFilterFolderField', true);

        $mvc->filterRolesForTeam ??= '';
        $mvc->filterRolesForTeam .= ',' . acc_Setup::get('SUMMARY_ROLES_FOR_TEAMS');
        $mvc->filterRolesForTeam = trim($mvc->filterRolesForTeam, ',');
        
        $mvc->filterRolesForAll ??= '';
        $mvc->filterRolesForAll .= ',' . acc_Setup::get('SUMMARY_ROLES_FOR_ALL');
        $mvc->filterRolesForAll = trim($mvc->filterRolesForAll, ',');
        
        // Добавяме глобалните роли за съответния клас да може да филтрират всички
        $rolesAll = self::$rolesAllMap[$mvc->className] ?? null;
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

        setPartIfNot($mvc, 'filterAutoDate', true);
        if(empty($mvc->hidePeriodFilter)){
            $mvc->_plugins = arr::combine(array('Избор на период' => cls::get('plg_SelectPeriod')), $mvc->_plugins);
        }

        if (empty($mvc->fields['createdOn'])) {
            $mvc->FLD('createdOn', 'datetime(format=smartTime)', 'caption=Създаване||Created, notNull, input=none');
        }

        if (empty($mvc->fields['createdBy'])) {
            $mvc->FLD('createdBy', 'key(mvc=core_Users)', 'caption=Създал||Creator, notNull, input=none');
        }

        $indexName = str::convertToFixedKey(str::phpToMysqlName(implode('_', arr::make('createdOn'))));
        if (empty($mvc->dbIndexes[$indexName])) {
            $mvc->setDbIndex('createdOn');
        }
    }


    /**
     * Изпълнява се преди запис
     */
    public function on_BeforeSave($mvc, $id, $rec, $fields = null)
    {
        // Ако създаваме нов документ и ...
        if (empty($rec->id)) {
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
            $rolesAll = self::$rolesAllMap[$mvc->className] ?? null;
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
        $showFilterDateField = $lastPeriod = $lastFolderId = null;
        $cKey = $mvc->className . '|' . core_Users::getCurrent();
        $userFields = array();

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
                    $fField = $data->listFilter->getField($f);
                    if(!empty($fField)){
                        $caption = $fField->caption;
                        if (strpos($caption, '->')) {
                            list($l, $r) = explode('->', $caption);
                            $caption = tr($l) . ' » ' . tr($r);
                        }
                        $opt[] = $f . '=' . $caption;
                    }
                }
                $data->listFilter->FLD('filterDateField', 'enum(' . implode(',', $opt) . ')', 'width=6em,caption=Филтър по||Filter by,silent,input');
                $showFilterDateField = ',filterDateField';
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
            $data->listFilter->showFields .= (!empty($data->listFilter->showFields) ? ',' : '') . 'search';
        }

        if(!$mvc->hidePeriodFilter){
            $data->listFilter->showFields .=  ((!empty($data->listFilter->showFields) ? ',' : '')) . 'from, to' . $showFilterDateField;
        }
        
        if ($isDocument = cls::haveInterface('doc_DocumentIntf', $mvc)) {

            // Филтър по "Наша фирма", ако е инсталиран пакета за многофирменост
            $mvc->invoke('afterGetDocumentSummaryListFields', array(&$data));
            $data->listFilter->FNC('users', "users(rolesForAll={$mvc->filterRolesForAll},rolesForTeams={$mvc->filterRolesForTeam}, showClosedGroups)", 'caption=Потребители,silent,autoFilter,remember');
            if(!empty($mvc->showFilterFolderField)){
                $data->listFilter->FNC('folder', 'key2(mvc=doc_FoldersProxy, allowEmpty, selectSourceArr=doc_Folders::getSelectArr, forceProxy)', 'caption=Папка,silent,after=users');
                $data->listFilter->showFields .= ',folder';
            }
            
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

        // Ако има поле по валута да се филтрира по нея
        if(!empty($mvc->currencyFld) && $mvc->getField($mvc->currencyFld, false)){
            $data->listFilter->mvc->toggableFieldsInVerticalListFilter = ($data->listFilter->mvc->toggableFieldsInVerticalListFilter ?? '') . ", {$mvc->currencyFld}";
            $data->listFilter->setFieldTypeParams($mvc->currencyFld, array('allowEmpty' => 'allowEmpty'));
            $data->listFilter->setField($mvc->currencyFld, "caption=Валута,input,formOrder=1000");
            $data->listFilter->showFields .= ",{$mvc->currencyFld}";
        }

        // Ако има поле за шаблон - по него
        if($mvc->hasPlugin('doc_plg_TplManager')){
            $templateOptions = doc_TplManager::getTemplates($mvc);
            if(countR($templateOptions)){
                $data->listFilter->mvc->toggableFieldsInVerticalListFilter = ($data->listFilter->mvc->toggableFieldsInVerticalListFilter ?? '') . ",template";
                $data->listFilter->setOptions('template', array('' => '') + $templateOptions);
                $data->listFilter->setField('template', "caption=Шаблон,formOrder=1002");
                $data->listFilter->showFields .= ",template";
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
                    if(isset($stateOptions['draft']) && isset($stateOptions['pending'])){
                        $stateOptions += array('draftAndPending' => 'Чернова+Заявка');
                    }
                    $stateOptionsString = arr::fromArray($stateOptions);
                    $data->listFilter->FNC('fState', "enum({$stateOptionsString})", 'caption=Състояние,input,silent');
                    $data->listFilter->showFields .= ',fState';
                    $data->listFilter->setDefault('fState', $mvc->defaultListFilterState);
                }
            }
        }

        // Активиране на филтъра
        $data->listFilter->input($data->listFilter->showFields, 'silent');
        $usedUsers = null;

        // Ако формата за търсене е изпратена
        if ($filter = $data->listFilter->rec) {
            if(!empty($mvc->currencyFld) && !empty($filter->{$mvc->currencyFld})){
                $data->query->where("#{$mvc->currencyFld} = '{$filter->{$mvc->currencyFld}}'");
            }

            if(!empty($filter->template)){
                $data->query->where("#template = '{$filter->template}'");
            }

            if(!empty($filter->fState)){
                if($filter->fState == 'draftAndPending'){
                    $data->query->in('state', array('draft', 'pending'));
                } elseif($filter->fState != 'all'){
                    $data->query->where("#state = '{$filter->fState}'");
                }
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
            if (!empty($filter->users) && $isDocument) {
                $userIds = keylist::toArray($filter->users);

                // Ако не се търси по всички
                if ($usedUsers != 'all_users') {
                    $userArr = implode(',', $userIds);

                    if(!empty($filter->filterDateField) && in_array($filter->filterDateField, $userFields)){
                        $data->query->where("#{$filter->filterDateField} IN ({$userArr})");
                    } else {
                        $map = array('createdOn' => 'createdBy', 'modifiedOn' => 'modifiedBy', 'activatedOn' => 'activatedBy');
                        $useUserField = $map[$filter->filterDateField ?? ''] ?? $mvc->filterFieldUsers;

                        $data->query->where("#{$useUserField} IN ({$userArr})");
                        if(!isset($map[$filter->filterDateField ?? '']) && $useUserField != $mvc->filterFieldUsers){
                            $data->query->orWhere("#{$mvc->filterFieldUsers} IS NULL AND #createdBy IN ({$userArr})");
                        }
                    }
                }
            }

            $dateRange = array(null, null);
            if (!empty($filter->from)) {
                $dateRange[0] = $filter->from;
            }
            
            if (!empty($filter->to)) {
                $dateRange[1] = $filter->to;
            }

            if ($dateRange[0] && $dateRange[1]) {
                sort($dateRange);
            }
            
            if ($showFilterDateField) {
                $fromField = ($filter->filterDateField ?? null) ?: $defaultFilterDateField;
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

                    if ($autoCalcField) {
                        $nullCond = " OR (#{$fromField} IS NULL AND #{$autoCalcField} IS NULL)";
                    } else {
                        $nullCond = " OR #{$fromField} IS NULL";
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
                    $fF = !empty($mvc->filterDateFrom) ? $mvc->filterDateFrom : 'from';
                    $fT = !empty($mvc->filterDateTo) ? $mvc->filterDateTo : 'to';
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

            if (!empty($filter->folder)) {
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
        if (empty($data->listSummary->query)) {
            
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

        // Основната валута за периода
        $baseCurrency = acc_Periods::getBaseCurrencyCode();
        $draftCount = $activeCount = $pendingCount = 0;

        $eurozoneDate = acc_Setup::getEurozoneDate();
        $data->hasDocumentBeforeEu = false;
        while ($rec = $sQuery->fetch()) {
            if(isset($mvc->valiorFld) && !empty($rec->{$mvc->valiorFld})) {
                if($rec->{$mvc->valiorFld} < $eurozoneDate){
                    $data->hasDocumentBeforeEu = true;
                }
            }
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
        if (!empty($data->listSummary->summary)) {
            $tpl = self::renderSummary($data);
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
        if (countR($fieldsArr) == 0) return;

        foreach ($fieldsArr as $fld) {
            if (!array_key_exists($fld->name, $res)) {
                $captionValue = (isset($fld->summaryCaption)) ? $fld->summaryCaption : $fld->caption;
                $captionArr = explode('->', $captionValue);
                $caption = (countR($captionArr) == 2) ? tr($captionArr[0]) . ': ' . tr($captionArr[1]) : tr($captionValue);
                $res[$fld->name] = (object) array('caption' => $caption, 'measure' => '', 'number' => 0, 'amount' => null, 'quantity' => null);
            }
            
            switch ($fld->summary) {
                case 'amount':
                    $baseAmount = $rec->{$fld->name};
                    if ($mvc->amountIsInNotInBaseCurrency === true) {
                        if(isset($rec->rate)){
                            $baseAmount *= $rec->rate;
                        }
                    }

                    if(isset($mvc->valiorFld)){
                        if($mvc->amountIsInNotInBaseCurrency){
                            $valior = $rec->{$mvc->valiorFld} ?? dt::today();
                        } else {
                            $valior = $rec->{$mvc->valiorFld} ?? dt::verbal2mysql($rec->createdOn, false);
                        }
                        $baseAmount = deals_Helper::getSmartBaseCurrency($baseAmount, $valior);
                    }

                    $res[$fld->name]->amount += $baseAmount;
                    $res[$fld->name]->measure = $currencyCode;
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
     * @param array $data - Масив от записи за показване
     * @return core_ET $tpl - Шаблон на обобщението
     */
    private static function renderSummary($data)
    {
        // Зареждаме и подготвяме шаблона
        $Double = core_Type::getByName('double(decimals=2)');
        $Int = core_Type::getByName('int');
        $tpl = new ET(tr('|*' . getFileContent('acc/plg/tpl/Summary.shtml')));
        $rowTpl = $tpl->getBlock('ROW');

        $summaryRecs = $data->listSummary->summary;

        if (countR($summaryRecs)) {
            foreach ($summaryRecs as $rec) {
                $row = new stdClass();

                $row->colspan = 1;
                if (isset($rec->amount)) {
                    if(!doc_plg_HidePrices::canSeePriceFields($data->query->mvc, $rec)){
                        $row->amount = doc_plg_HidePrices::getBuriedElement();
                    } else{
                        $row->amount = $Double->toVerbal($rec->amount);
                        $row->amount = currency_Currencies::decorate($row->amount,  $rec->measure, true);
                        $row->amount = ht::styleNumber($row->amount, $rec->amount);

                        if(!empty($data->hasDocumentBeforeEu)){
                            $amountBgn = currency_CurrencyRates::convertAmount($rec->amount, null, 'EUR', 'BGN');
                            $row->amountBgn = $Double->toVerbal($amountBgn);
                            $row->amountBgn = currency_Currencies::decorate($row->amountBgn, 'BGN');
                            $row->amountBgn = ht::styleNumber($row->amountBgn, $amountBgn);
                        }
                    }
                } elseif (isset($rec->quantity)) {
                    $row->measure = $rec->measure;
                    $row->quantity = $Int->toVerbal($rec->quantity);
                    $row->quantity = ($rec->quantity < 0) ? "<span style='color:red'>{$row->quantity}</span>" : $row->quantity;
                    if(!empty($data->hasDocumentBeforeEu)){
                        $row->colspan = 3;
                    }
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
