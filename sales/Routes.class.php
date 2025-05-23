<?php


/**
 * Модел  за търговски маршрути
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sales_Routes extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Търговски маршрути';
    
    
    /**
     * Заглавие
     */
    public $singleTitle = 'Търговски маршрут';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'contragent=Контрагент,locationId,nextVisit=Посещения->Следващо,dateFld=Посещения->Начало,holidays,repeat=Посещения->Период,salesmanId,type,state,createdOn,createdBy';
    
    
    /**
     * Брой рецепти на страница
     */
    public $listItemsPerPage = '30';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, sales_Wrapper, plg_Created, plg_Printing, bgerp_plg_Blank, plg_Sorting, plg_Search, plg_Rejected, plg_State2';
    
    
    /**
     * Кой може да пише
     */
    public $canWrite = 'sales,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,sales';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,sales';
    
    
    /**
     * Кой може да пише
     */
    public $canAdd = 'sales,ceo';
    
    
    /**
     * Кой може да пише
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой може да пише
     */
    public $canReject = 'sales,ceo';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'locationId,salesmanId';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('locationId', 'key(mvc=crm_Locations, select=title,allowEmpty)', 'caption=Локация,mandatory,silent,tdClass=nowrap');
        $this->FLD('salesmanId', 'user(roles=sales|ceo,select=nick)', 'caption=Търговец,mandatory');
        $this->FLD('dateFld', 'date', 'caption=Посещения->Дата,hint=Кога е първото посещение,mandatory,smartCenter');
        $this->FLD('type', 'enum(visit=Посещение,delivery=Доставка,mixed=Посещение и доставка)', 'caption=Посещения->Вид,mandatory,notNull,default=visit');
        $this->FLD('repeat', 'time(suggestions=|1 седмица|2 седмици|3 седмици|4 седмици|1 месец)', 'caption=Посещения->Период, hint=на какъв период да е повторението на маршрута');
        $this->FLD('holidays', 'enum(include=Включително, skip=Пропускане,nextWorkDay=Следващ раб. ден,prevWorkDay=Предишен раб. ден)', 'caption=Посещения->Почивни дни,notNull,value=include');

        // Изчислимо поле за кога е следващото посещение
        $this->FLD('nextVisit', 'date(format=d.m.Y D)', 'caption=Посещения->Следващо,input=none,smartCenter');
        
        $this->setDbIndex('locationId,dateFld');
        $this->setDbIndex('locationId');
        $this->setDbIndex('salesmanId');
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    protected static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        // Добавяме името на контрагента към ключовите дум
        $locRec = crm_Locations::fetch($rec->locationId);
        $res .= ' ' . plg_Search::normalizeText(cls::get($locRec->contragentCls)->getVerbal($locRec->contragentId, 'name'));
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    protected static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $form = &$data->form;
        $form->setDefault('dateFld', dt::today());
        $form->setOptions('locationId', $mvc->getLocationOptions($form->rec));
        $form->setDefault('salesmanId', $mvc->getDefaultSalesman($form->rec));
    }
    
    
    /**
     * Всяка локация я представяме като "<локация> « <име на контрагент>"
     *
     * @param stdClass $rec - запис от модела
     *
     * @return array $options - Масив с локациите и новото им представяне
     */
    private function getLocationOptions($rec)
    {
        $options = array();
        $varchar = cls::get('type_Varchar');
        $locQuery = crm_Locations::getQuery();
        $locQuery->where("#state != 'rejected'");
        if ($locId = Request::get('locationId', 'int')) {
            $locQuery->where("#id = {$locId}");
        }
        
        while ($locRec = $locQuery->fetch()) {
            $locRec = crm_Locations::fetch($locRec->id);
            if (cls::load($locRec->contragentCls, true)) {
                $contragentCls = cls::get($locRec->contragentCls);
                $contragentName = $contragentCls->fetchField($locRec->contragentId, 'name');
                $lockName = $varchar->toVerbal($locRec->title) . ' « ' . $varchar->toVerbal($contragentName);
                $options[$locRec->id] = $lockName;
            }
        }
        
        return $options;
    }
    
    
    /**
     * Намираме кой е търговеца по подразбиране, връщаме ид-то на
     * потребителя в следния ред:
     *
     * 1. Търговеца от последния маршрут за тази локация (ако има права)
     * 2. Отговорника на папката на контрагента на локацията (ако има права)
     * 3. Търговеца от последния маршрут създаден от текущия потребителя
     * 4. Текущия потребител ако има права 'sales'
     * 5. NULL - ако никое от горните не е изпълнено
     *
     * @param stdClass $rec - запис от модела
     *
     * @return int - Ид на търговеца, или NULL ако няма
     */
    private function getDefaultSalesman($rec)
    {
        // Ако имаме локация
        if ($rec->locationId) {
            $query = $this->getQuery();
            $query->orderBy('#id', 'DESC');
            $query->where("#locationId = {$rec->locationId}");
            $lastRec = $query->fetch();
            
            if ($lastRec) {
                
                // Ако има последен запис за тази локация
                if (self::haveRightFor('add', null, $lastRec->salesmanId)) {
                    // ... има право да създава продажби
                    return $lastRec->salesmanId;
                }
            }
            
            // Ако отговорника на папката има права 'sales'
            $locRec = crm_Locations::fetch($rec->locationId);
            $contragentCls = cls::get($locRec->contragentCls);
            $inCharge = $contragentCls->fetchField($locRec->contragentId, 'inCharge');
            
            if (self::haveRightFor('add', null, $inCharge)) {
                // ... има право да създава продажби - той става дилър по подразбиране.
                return $inCharge;
            }
        }
        
        $currentUserId = core_Users::getCurrent('id');
        
        // Ако има последен запис от този потребител
        $query = $this->getQuery();
        $query->orderBy('#id', 'DESC');
        $query->where("#createdBy = {$currentUserId}");
        $lastRoute = $query->fetch();
        if ($lastRoute) {
            
            return $lastRoute->salesmanId;
        }
        
        // Текущия потребител ако има права
        if (self::haveRightFor('add', null, $currentUserId)) {
            
            return $currentUserId;
        }
        
        return null;
    }
    
    
    /**
     *  Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->FNC('user', 'user(roles=sales|ceo)', 'input,caption=Търговец,placeholder=Търговец,silent,autoFilter');
        $data->listFilter->FNC('date', 'date', 'input,caption=Дата,silent');
        $data->listFilter->setFieldType('type', "enum(all=Вид,visit=Посещение,delivery=Доставка,mixed=Посещение и доставка)");

        if(haveRole('officer')){
            $data->listFilter->setFieldTypeParams('user', array('allowEmpty' => 'allowEmpty'));
        } elseif(haveRole('sales')) {
            $data->listFilter->setDefault('user', core_Users::getCurrent());
        }
        $data->listFilter->setDefault('type', 'all');
        $data->listFilter->showFields = 'search,user,type,date';
        $data->listFilter->input();
        $data->query->orderBy('#nextVisit', 'ASC');
        
        // Филтриране по дата
        $filterRec = $data->listFilter->rec;
        if ($filterRec->date) {
            $data->query->where("#nextVisit = '{$filterRec->date}'");
            $data->query->XPR('dif', 'int', "DATEDIFF (#dateFld , '{$filterRec->date}')");
            $data->query->orWhere('MOD(#dif, round(#repeat / 86400 )) = 0');
        }
        
        // Филтриране по продавач
        if ($filterRec->user) {
            $data->query->where(array('#salesmanId = [#1#]', $filterRec->user));
        }

        if ($filterRec->type == 'mixed') {
            $data->query->where("#type = 'mixed'");
        } elseif ($filterRec->type == 'visit') {
            $data->query->where("#type IN ('visit', 'mixed')");
        } elseif ($filterRec->type == 'delivery') {
            $data->query->where("#type IN ('delivery', 'mixed')");
        }

        if (Mode::isReadOnly()) {
            unset($data->listFields['state']);
            unset($data->listFields['createdOn']);
            unset($data->listFields['createdBy']);
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->locationId = crm_Locations::getHyperLink($rec->locationId, true);
        
        if (!$rec->repeat) {
            $row->repeat = "<span class='quiet'>" . tr('n/a') . '</span>';
        }
        
        $locationRec = crm_Locations::fetch($rec->locationId);
        $row->contragent = cls::get($locationRec->contragentCls)->getHyperLink($locationRec->contragentId, true);
        
        if ($rec->state == 'active') {
            if (!Mode::isReadOnly()) {
                if (crm_Locations::haveRightFor('createsale', $rec->locationId)) {
                    core_RowToolbar::createIfNotExists($row->_rowTools);
                    $row->_rowTools->addLink('Продажба', array('crm_Locations', 'createSale', $rec->locationId, 'ret_url' => true), 'ef_icon=img/16/cart_go.png,title=Създаване на нова продажба към локацията');
                }
            }
        } else {
            unset($row->nextVisit);
        }
    }
    
    
    /**
     * Реализация по подразбиране на метода getEditUrl()
     */
    protected static function on_BeforeGetEditUrl($mvc, &$editUrl, $rec)
    {
        $editUrl['locationId'] = $rec->locationId;
    }
    
    
    /**
     * Подготовка на маршрутите, показвани в Single-a на локациите
     */
    public function prepareRoutes($data)
    {
        $data->rows = array();
        
        // Подготвяме маршрутите ако има налични за тази локация
        $query = $this->getQuery();
        $query->where(array('#locationId = [#1#]', $data->masterData->rec->id));
        $query->where("#state = 'active'");
        while ($rec = $query->fetch()) {
            $data->recs[$rec->id] = $rec;
            $data->rows[$rec->id] = static::recToVerbal($rec);
        }
        
        if ($this->haveRightFor('add', (object) (array('locationId' => $data->masterData->rec->id)))) {
            $data->addUrl = array('sales_Routes', 'add', 'locationId' => $data->masterData->rec->id, 'ret_url' => true);
        }
    }


    /**
     * Кога е следващото посещение по маршрута
     *
     * @param stdClass $rec
     * @return false|string
     */
    public function getNextVisit($rec)
    {
        $nowTs = dt::mysql2timestamp(dt::now());
        $interval = 24 * 60 * 60 * 5;

        if (!$rec->dateFld) {
            return false;
        }

        $startTs = dt::mysql2timestamp($rec->dateFld);
        $diff    = $nowTs - $startTs;

        // Ако още не е дошъл първия път
        if ($diff < 0) {
            $nextTs = $startTs;
        } else {
            // Ако няма повторения
            if (!$rec->repeat) {
                // Ако е точно днес
                if ($rec->dateFld == date('Y-m-d')) {
                    return $rec->dateFld;
                }
                return false;
            }

            // Изчисляваме колко седмици да прибавим
            $repeat   = $rec->repeat / (60 * 60 * 24 * 7);
            $interval = $interval * $repeat;
            $steps    = floor($diff / $interval) + 1;
            $nextTs   = $startTs + $steps * $interval;
        }

        // Връщаме mysql-формат
        $date = dt::timestamp2mysql($nextTs + 10 * 60 * 60);
        $date = dt::verbal2mysql($date, false);

        // Ако няма holiday-режим, просто даваме датата
        if (empty($rec->holidays) || $rec->holidays === 'include') return $date;

        // В противен случай влизаме в обработка на празниците
        while (cal_Calendar::isHoliday($date)) {
            switch ($rec->holidays) {
                case 'skip':

                    // Добавяме още един интервал и проверяваме пак
                    $nextTs = dt::mysql2timestamp($date) + $interval;
                    $date   = dt::timestamp2mysql($nextTs);

                    break;
                case 'nextWorkDay':
                    // Ако се иска следващия работен ден след празника - него
                    $nextWorkDay = cal_Calendar::nextWorkingDay($date, null, 1);

                    return dt::verbal2mysql($nextWorkDay, false);

                case 'prevWorkDay':
                    // Ако се иска предишния работен ден преди празника - него
                    $prevWorkDay = cal_Calendar::nextWorkingDay($date, null, -1);

                    return dt::verbal2mysql($prevWorkDay, false);
                default:
                    return dt::verbal2mysql($date, false);
            }
        }

        // Ако се стигне до тук значи не е празник
        $date = dt::verbal2mysql($date, false);

        return $date;
    }
    
    
    /**
     * Рендираме информацията за маршрутите
     */
    public function renderRoutes($data)
    {
        $tpl = getTplFromFile('sales/tpl/SingleLayoutRoutes.shtml');
        $title = tr($this->title);
        $listFields = arr::make('salesmanId=Търговец,repeat=Период,holidays=Почивни дни,nextVisit=Следващо посещение,type=Вид');
        
        if ($data->addUrl && !Mode::isReadOnly()) {
            $title .= ht::createLink('', $data->addUrl, null, array('ef_icon' => 'img/16/add.png', 'class' => 'addRoute', 'title' => 'Създаване на нов търговски маршрут'));
        }
        
        $tpl->replace($title, 'title');
        
        $table = cls::get('core_TableView');
        $data->listFields = $listFields;
        $this->invoke('BeforeRenderListTable', array($data, $data));
        
        $tableTpl = $table->get($data->rows, $data->listFields);
        $tpl->append($tableTpl, 'content');
        
        return $tpl;
    }
    
    
    /**
     * Модификация на ролите
     */
    protected static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if ($action == 'edit' && $rec->id) {
            if ($rec->state == 'rejected') {
                $res = 'no_one';
            }
        }
        
        if (($action == 'add' || $action == 'restore' || $action == 'changestate') && isset($rec->locationId)) {
            if (crm_Locations::fetchField($rec->locationId, 'state') == 'rejected') {
                $res = 'no_one';
            }
        }
        
        if ($action == 'reject' && isset($rec)) {
            if ($rec->state == 'closed') {
                $res = 'no_one';
            }
        }
    }
    
    
    /**
     * Променя състоянието на всички маршрути след промяна на това на локацията им
     *
     * @param int $locationId - id на локация
     */
    public function changeState($locationId)
    {
        $locationState = crm_Locations::fetchField($locationId, 'state');
        $query = $this->getQuery();
        $query->where("#locationId = {$locationId}");
        while ($rec = $query->fetch()) {
            $state = ($locationState == 'rejected') ? 'rejected' : 'active';
            $rec->state = $state;
            $this->save($rec);
        }
    }
    
    
    /**
     * Връща търговеца с най-близък маршрут
     *
     * @param int    $locationId - ид на локация
     * @param string|null $date       - дата, NULL за текущата дата
     *
     * @return int|null $salesmanId - ид на търговец
     */
    public static function getSalesmanId($locationId, $date = null)
    {
        $date = (isset($date)) ? $date : dt::today();
        $date2 = new DateTime($date);
        $cu = core_Users::getCurrent();
        
        $salesmanId = null;
        $arr = array();
        
        // Намираме и подреждаме всички маршрути към локацията
        $query = self::getQuery();
        $query->where("#locationId = '{$locationId}' AND #state != 'closed'");
        $query->orderBy('createdOn', 'DESC');
        
        // За всяка
        while ($rec = $query->fetch()) {
            
            // Ако маршрута е от текущия потребител, винаги е с приоритет
            if ($rec->salesmanId == $cu) {
                $date1 = $date;
            } else {
                // Ако има дата на доставка, нея, ако няма слагаме -10 години, за да излезе най-отдолу
                $date1 = (isset($rec->nextVisit)) ? $rec->nextVisit : dt::verbal2mysql(dt::addMonths(-1 * 10 * 12, $date), false);
            }
            
            // Колко е разликата между датите
            $date1 = new DateTime($date1);
            $interval = date_diff($date1, $date2);
            
            // Добавяме в масива
            $arr[] = (object) array('diff' => $interval->days, 'salesmanId' => $rec->salesmanId, 'id' => $rec->id);
        }
        
        // Ако няма маршрути, връщаме
        if (!countR($arr)) {
            
            return $salesmanId;
        }
        
        // Сортираме по разликата
        arr::sortObjects($arr, 'diff', 'asc');
        $first = $arr[key($arr)];
        $salesmanId = $first->salesmanId;
        
        // Връщаме най-новия запис с най-малка разлика
        return $salesmanId;
    }


    /**
     * Изчисляване на следващото посещение по разписание
     */
    public function cron_calcNextVisit()
    {
        $today = dt::today();
        
        $delayDays = sales_Setup::get('ROUTES_CLOSE_DELAY');
        $before = dt::addSecs(-1 * 60 * 60 * 24 * $delayDays, $today);
        $before = dt::verbal2mysql($before, false);
        
        $updateState = $updateNextVisit = array();
        $query = self::getQuery();
        $query->where("#state = 'active'");
        $query->where("#nextVisit IS NULL OR #nextVisit != '{$today}'");
        
        // Дигане на тайм лимита
        $count = $query->count();
        core_App::setTimeLimit(0.7 * $count);

        // За всеки запис
        while ($rec = $query->fetch()) {
            if (empty($rec->repeat) && $rec->dateFld < $before) {
                
                // Ако е еднократен и е минал, затваря се
                $rec->state = 'closed';
                $updateState[$rec->id] = $rec;
            } else {
                
                // Ако има повторение прави се опит за изчисляване на следващото посещение
                if ($next = $this->getNextVisit($rec)) {
                    $rec->nextVisit = $next;
                    $updateNextVisit[$rec->id] = $rec;
                }
            }
        }
        
        // Обновяване на състоянията
        if (countR($updateState)) {
            $this->saveArray($updateState, 'id,state');
        }
        
        // Обновяване на следващото изпълнение
        if (countR($updateNextVisit)) {
            $this->saveArray($updateNextVisit, 'id,nextVisit');
        }
    }
    
    
    /**
     * Преди запис на документ, изчислява стойността на полето `isContable`
     *
     * @param core_Manager $mvc
     * @param stdClass     $rec
     */
    protected static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
        if ($rec->state != 'rejected' || $rec->state != 'closed') {
            if ($next = $mvc->getNextVisit($rec)) {
                $rec->nextVisit = $next;
            }
        }
    }
    
    
    /**
     * Връща подходящо заглавие на маршрута
     * 
     * @param stdClass $rec - маршрут
     * @return string $smartTitle - заглавие от рода Понеделник (dd.mm.yy)
     */
    public static function getSmartTitle($rec)
    {
        $rec = self::fetchRec($rec);
        
        $dayName = dt::mysql2verbal($rec->nextVisit, 'l');
        $fullDate = dt::mysql2verbal($rec->nextVisit, 'd.m.Y');
        $smartTitle = "{$fullDate} ({$dayName})";
        
        return $smartTitle;
    }
    
    
    /**
     * Кои марршрути са допустими за избор
     *
     * @param int $locationId  - към коя локация
     * @param int $inDays      - в следващите колко дни? null за без ограничение
     * @param string $type     - вид посещение
     * @return string[] $routeOptions - опции от маршрути
     */
    public static function getRouteOptions($locationId, $inDays = null, $type = null)
    {
        $today = dt::today();

        $routeOptions = array();
        $rQuery = static::getQuery();
        $rQuery->where("#locationId = '{$locationId}' AND #nextVisit > '{$today}' AND #state != 'rejected'");
        if(isset($inDays)){
            $inDays = dt::addDays($inDays, $today, false);
            $rQuery->where("#nextVisit <= '{$inDays}'");
        }

        if ($type == 'mixed') {
            $rQuery->where("#type = 'mixed'");
        } elseif ($type == 'visit') {
            $rQuery->where("#type IN ('visit', 'mixed')");
        } elseif ($type == 'delivery') {
            $rQuery->where("#type IN ('delivery', 'mixed')");
        }

        $rQuery->show('id,nextVisit');
        $rQuery->orderBy('id', "ASC");
        $nextWorkingDay = cal_Calendar::nextWorkingDay();

        // Ако сме след зададения час се пропускат маршрутите, чието следващо посещение е следващия работен ден
        $skipNextWorkingDay = false;
        $eshopHours = eshop_Setup::get('TOMORROW_DELIVERY_DEADLINE');
        $hours = dt::mysql2verbal(null,'H:i');
        if($hours > $eshopHours){
            $skipNextWorkingDay = true;
        }

        while($rRec = $rQuery->fetch()){
            if($skipNextWorkingDay && $rRec->nextVisit == $nextWorkingDay) continue;

            $routeOptions[$rRec->id] = sales_Routes::getSmartTitle($rRec);
        }

        return $routeOptions;
    }


    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $filteredDate = $data->listFilter->rec->date;
        if(!empty($filteredDate)){
            $data->listTableMvc->FLD('plannedDate', 'date', 'smartCenter');
            arr::placeInAssocArray($data->listFields, array('plannedDate' => 'Посещения->Планувано'), 'nextVisit');
        }

        if(!countR($data->rows)) return;

        $today = dt::today();
        $locationIds = arr::extractValuesFromArray($data->recs, 'locationId');
        $routesByLocation = array();

        // Групиране на бъдещите посещения за филтрираните локации
        $query = static::getQuery();
        $query->in('locationId', $locationIds);
        $query->where("#nextVisit >= '{$today}'");
        if(!empty($filteredDate)){
            $query->XPR('dif', 'int', "DATEDIFF(#dateFld , '{$filteredDate}')");
            $query->where("(#repeat IS NOT NULL AND MOD(#dif, round(#repeat / 86400 )) = 0) OR #nextVisit = '{$filteredDate}'");
        }
        $query->where("#state = 'active'");

        while($fRec = $query->fetch()){
            $date = !empty($filteredDate) ? $filteredDate : $fRec->nextVisit;
            if(!array_key_exists("{$fRec->locationId}|{$date}", $routesByLocation)){
                $routesByLocation["{$fRec->locationId}|{$date}"] = (object)array('count' => 0, 'ids' => array());
            }

            // Преброява се за всяка локация колко бъдещи посещения има за същата дата
            $salesperson = core_Users::getNick($fRec->salesmanId);
            $routesByLocation["{$fRec->locationId}|{$date}"]->count++;
            $routesByLocation["{$fRec->locationId}|{$date}"]->ids[$fRec->id] = $salesperson;
        }

        foreach ($data->rows as $id => $row){
            $rec = $data->recs[$id];

            // За всяка активна локация се гледа има ли повече от 1 посещение за въпросната дата
            $dateFld = 'nextVisit';
            if(!empty($filteredDate)){

                $hint = null;
                $dateFld = 'plannedDate';
                if(cal_Calendar::isHoliday($filteredDate)){
                    switch($rec->holidays) {
                        case 'skip';
                            $hint = 'Планираният ден е почивен. Маршрутът няма да се изпълнява!';
                            break;
                        case 'prevWorkDay';
                            $prevDate = cal_Calendar::nextWorkingDay($filteredDate, null, -1);
                            $prevDateVerbal = $mvc->getFieldType('nextVisit')->toVerbal($prevDate);
                            $hint = "Планираният ден е почивен. Маршрутът ще се изпълнява на предишния работен ден|* {$prevDateVerbal}!";
                            break;
                        case 'nextWorkDay';
                            $nextDate = cal_Calendar::nextWorkingDay($filteredDate);
                            $nextDateVerbal = $mvc->getFieldType('nextVisit')->toVerbal($nextDate);
                            $hint = "Планираният ден е почивен. Маршрутът ще се изпълнява на следващия работен ден|* {$nextDateVerbal}!";
                            break;
                    }
                }

                $row->plannedDate = $mvc->getFieldType('nextVisit')->toVerbal($filteredDate);
                if(!empty($hint)){
                    $row->plannedDate = ht::createElement('span', array('style' => 'color:#1a6d00'),$row->plannedDate);
                    $row->plannedDate = ht::createHint($row->plannedDate, $hint, 'warning', false);
                }
                $rec->plannedDate = $filteredDate;
            }

            if($rec->state == 'active' && array_key_exists("{$rec->locationId}|{$rec->{$dateFld}}", $routesByLocation)){

                // Ако има показва се хинт с информация за другите посещение за този обект за тази дата
                if($routesByLocation["{$rec->locationId}|{$rec->{$dateFld}}"]->count > 1){
                    $ids = $routesByLocation["{$rec->locationId}|{$rec->{$dateFld}}"]->ids;
                    unset($ids[$rec->id]);
                    $word = (countR($ids) > 1) ? 'посещения' : 'посещение';
                    $msg = tr("Има {$word} на тази дата от|*: ") . implode(', ', $ids);
                    $row->{$dateFld} = ht::createHint($row->{$dateFld}, $msg, 'warning', false);
                }
            }
        }
    }
}
