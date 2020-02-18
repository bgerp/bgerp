<?php


/**
 * Мениджър за "Точки на продажба"
 *
 *
 * @category  bgerp
 * @package   pos
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.11
 */
class pos_Points extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Точки на продажба';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, plg_Rejected, doc_FolderPlg,pos_Wrapper, plg_Printing, plg_Current, plg_State, plg_Modified, plg_Settings';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'POS';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name, caseId, storeId';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Да се създаде папка при създаване на нов запис
     */
    public $autoCreateFolder = 'instant';
    
    
    /**
     * Кой може да променя?
     */
    public $canWrite = 'ceo, posMaster';
    
    
    /**
     * Кой може да пише
     */
    public $canCreatenewfolder = 'ceo, pos';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, pos';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,pos';
    
    
    /**
     * Икона за единичен изглед
     */
    public $singleIcon = 'img/16/cash-register.png';
    
    
    /**
     * Кой може да го отхвърли?
     */
    public $canReject = 'admin, posMaster';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'pos/tpl/SinglePointLayout.shtml';
    
    
    /**
     * Кой може да селектира?
     */
    public $canSelect = 'ceo, pos';
    
    
    /**
     * Кой може да селектира всички записи
     */
    public $canSelectAll = 'ceo, posMaster';
    
    
    /**
     * Детайли на бележката
     */
    public $details = 'Receipts=pos_Receipts';
    
    
    /**
     * Полета за настройки
     * 
     * @see plg_Settings
     */
    public $settingFields = 'policyId,payments,theme,cashiers,setPrices,setDiscounts,usedDiscounts,maxSearchContragentStart,maxSearchContragent,otherStores,maxSearchProducts,maxSearchReceipts';
      
    
    /**
     * Полета за съответветствие с константите
     */
    private static $fieldMap = array('maxSearchContragentStart' => 'TERMINAL_MAX_SEARCH_CONTRAGENTS', 
                                     'maxSearchContragent' => 'TERMINAL_MAX_SEARCH_CONTRAGENTS', 
                                     'maxSearchProducts' => 'TERMINAL_MAX_SEARCH_PRODUCTS', 
                                     'maxSearchReceipts' => 'TERMINAL_MAX_SEARCH_RECEIPTS');
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar(16)', 'caption=Наименование, mandatory,oldFieldName=title');
        $this->FLD('caseId', 'key(mvc=cash_Cases, select=name)', 'caption=Каса, mandatory');
        $this->FLD('policyId', 'key(mvc=price_Lists, select=title)', 'caption=Настройки->Политика, mandatory');
        $this->FLD('payments', 'keylist(mvc=cond_Payments, select=title)', 'caption=Настройки->Безналични плащания,placeholder=Всички');
        $this->FLD('theme', 'enum(default=Стандартна,dark=Тъмна)', 'caption=Настройки->Тема,default=dark,mandatory');
        $this->FLD('cashiers', 'keylist(mvc=core_Users,select=nick)', 'caption=Настройки->Оператори, mandatory,optionsFunc=pos_Points::getCashiers');
        
        $this->FLD('setPrices', 'enum(yes=Разрешено,no=Забранено,ident=При идентификация)', 'caption=Ръчно задаване->Цени, mandatory,default=yes');
        $this->FLD('setDiscounts', 'enum(yes=Разрешено,no=Забранено,ident=При идентификация)', 'caption=Ръчно задаване->Отстъпки, mandatory,settings,default=yes');
        $this->FLD('usedDiscounts', 'table(columns=discount,captions=Отстъпки)', 'caption=Ръчно задаване->Използвани отстъпки');
        
        $this->FLD('maxSearchProducts', 'int(min=1)', 'caption=Максимален брой резултати в "Избор"->Артикули');
        $this->FLD('maxSearchReceipts', 'int(min=1)', 'caption=Максимален брой резултати в "Избор"->Бележки');
        $this->FLD('maxSearchContragentStart', 'int(min=1)', 'caption=Максимален брой резултати в "Избор"->(Клиенти) Първоначално');
        $this->FLD('maxSearchContragent', 'int(min=1)', 'caption=Максимален брой резултати в "Избор"->(Клиенти) При търсене');
        
        $this->FLD('storeId', 'key(mvc=store_Stores, select=name)', 'caption=Складове->Основен, mandatory');
        $this->FLD('otherStores', 'keylist(mvc=store_Stores, select=name)', 'caption=Складове->Допълнителни');
    }
    
    
    /**
     * Връща списъка с операторите
     * 
     * @return array $users
     */
    public static function getCashiers()
    {
        $users = core_Users::getUsersByRoles('pos,ceo');
        
        return $users;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;
        if($form->isSubmitted()){
            if(!empty($rec->otherStores) && keylist::isIn($rec->storeId, $rec->otherStores)){
                $form->setError('otherStores', 'Основният склад не може да е избран');
            }
        }
    }
     
     
    /**
     * Разрешените начини за плащане на ПОС-а
     *
     * @param int $pointId
     *
     * @return array $payments
     */
    public static function fetchSelected($pointId)
    {
        $paymentQuery = cond_Payments::getQuery();
        $paymentQuery->where("#state = 'active'");
        
        // Ако са посочени конкретни, само те се разрешават
        $payments = keylist::toArray(static::getSettings($pointId, 'payments'));
        if (countR($payments)) {
            $paymentQuery->in('id', $payments);
        }
        
        $payments = array();
        while ($paymentRec = $paymentQuery->fetch()) {
            $payments[$paymentRec->id] = tr($paymentRec->title);
        }
        
        return $payments;
    }
    
    
    /**
     * Създава дефолт контрагент за обекта, ако той вече няма създаден
     */
    protected static function on_AfterSave($mvc, &$id, $rec)
    {
        if (!static::defaultContragent($id)) {
            $defaultContragent = new stdClass();
            $defaultContragent->name = 'POS:' . $rec->id . '-Анонимен Клиент';
            $defaultContragent->country = crm_Companies::fetchOurCompany()->country;
            
            crm_Persons::save($defaultContragent);
        }
    }
    
    
    /**
     * Подготовка на формата за добавяне
     */
    protected static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $form = &$data->form;
        $form->setDefault('policyId', cat_Setup::get('DEFAULT_PRICELIST'));
        if(empty($form->rec->prototypeId)){
            
            // Задаване на плейсхолдъри
            foreach (static::$fieldMap as $field => $const){
                $defaultValue = pos_Setup::get($const);
                $form->setField($field, "placeholder={$defaultValue}");
            }
        }
    }
    
    
    /**
     * Намира кой е дефолт контрагента на Точката на продажба
     *
     * @param int $id - ид на точкта
     *
     * @return mixed $id/FALSE - ид на контрагента или FALSE ако няма
     */
    public static function defaultContragent($id = null)
    {
        ($id) ? $pos = $id : $pos = pos_Points::getCurrent();
        $query = crm_Persons::getQuery();
        $query->where("#name LIKE '%POS:{$pos}%'");
        if ($rec = $query->fetch()) {
            
            return $rec->id;
        }
        
        return false;
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;
        
        if ($mvc->haveRightFor('select', $rec->id) && pos_Receipts::haveRightFor('terminal')) {
            $urlArr = array('pos_Receipts', 'new', "pointId" => $rec->id);
            $data->toolbar->addBtn('Отвори', $urlArr, null, 'title=Отваряне на терминала за POS продажби,class=pos-open-btn,ef_icon=img/16/forward16.png,target=_blank');
        }
        
        $reportUrl = array();
        if (pos_Reports::haveRightFor('add', (object) array('pointId' => $rec->id)) && pos_Reports::canMakeReport($rec->id)) {
            $reportUrl = array('pos_Reports', 'add', 'pointId' => $rec->id, 'ret_url' => true);
        }
        
        $title = (count($reportUrl)) ? 'Направи отчет' : 'Не може да се генерира отчет. Възможна причина - неприключени бележки.';
        
        $data->toolbar->addBtn('Отчет', $reportUrl, null, "title={$title},ef_icon=img/16/report.png");
    }
    
    
    /**
     * Обработка по вербалното представяне на данните
     */
    protected static function on_AfterRecToVerbal(core_Mvc $mvc, &$row, $rec, $fields = array())
    {
        unset($row->currentPlg);
        if (empty($rec->payments)) {
            $row->payments = tr('Всички');
        }
        
        if(empty($rec->otherStores)){
            $row->otherStores = tr('Няма');
        }
        
        if (!Mode::is('text', 'xhtml') && !Mode::is('printing') && !Mode::is('pdf')) {
            if ($mvc->haveRightFor('select', $rec->id) && pos_Receipts::haveRightFor('terminal')) {
                $urlArr = array('pos_Receipts', 'new', "pointId" => $rec->id);
                $row->currentPlg = ht::createBtn('Отвори', $urlArr, null, true, 'title=Отваряне на терминала за POS продажби,class=pos-open-btn,ef_icon=img/16/forward16.png');
            }
        }
        
        $row->caseId = cash_Cases::getHyperlink($rec->caseId, true);
        $row->storeId = store_Stores::getHyperlink($rec->storeId, true);
        
        if ($fields['-single']) {
            if($rec->state != 'rejected'){
                $currentId = $mvc->getCurrent('id', false);
                $row->STATE_CLASS = ($rec->id == $currentId) ? 'state-active' : 'state-closed';
            }
            
            $row->policyId = price_Lists::getHyperlink($rec->policyId, true);
            if ($defaultContragent = self::defaultContragent($rec->id)) {
                $row->contragent = crm_Persons::getHyperlink($defaultContragent, true);
            }
            
            if(isset($rec->prototypeId)){
                $row->prototypeId = pos_Points::getHyperlink($rec->prototypeId, true);
            }
        }
        
        $inherited = new stdClass();
        $mvc->getSettings($rec, null, $inherited);
        foreach ((array)$inherited as $field){
            if(in_array($field, array('policyId', 'payments'))){
                $row->{$field} = ht::createHint($row->{$field}, 'Наследено е от прототипа', 'notice', false);
            }
        }
    }
    
    
    /**
     * След връщане на избраната точка
     */
    protected static function on_AfterGetCurrent($mvc, &$res, $part = 'id', $bForce = true)
    {
        // Ако сме се логнали в точка
        if ($res && $part == 'id') {
            $rec = $mvc->fetchRec($res);
            
            // .. и имаме право да изберем касата и, логваме се в нея
            if (cash_Cases::haveRightFor('select', $rec->caseId)) {
                cash_Cases::selectCurrent($rec->caseId);
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'select' && isset($rec)) {
            if (!self::canSelectPos($rec, $userId)) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Може ли потребителя да избере точката на продажба.
     * Може само ако има права да избира касата и склада в точката
     *
     * @param mixed       $rec    - ид или запис
     * @param string|NULL $userId - потребител, NULL за текущия
     *
     * @return bool $res       - може ли да избира точката на продажба
     */
    public static function canSelectPos($rec, $userId = null)
    {
        $userId = (isset($userId)) ? $userId : core_Users::getCurrent();
        
        $rec = static::fetchRec($rec);
        
        $canActivateCase = bgerp_plg_FLB::canUse('cash_Cases', $rec->caseId, $userId);
        $res = ($canActivateCase === true);
        
        return $res;
    }
    
    
    /**
     * Добавя филтър по точка към тулбар
     *
     * @param core_Fieldset $filter
     * @param core_Query    $query
     * @param string        $pointFld
     */
    public static function addPointFilter(core_Fieldset &$filter, core_Query &$query, $pointFld = 'pointId')
    {
        $filter->FNC('point', 'key(mvc=pos_Points, select=name, allowEmpty)', 'caption=Точка,width=12em,silent');
        $filter->showFields .= ',point';
        $filter->setDefault('point', static::getCurrent('id', false));
        $filter->input();
        
        if ($filterRec = $filter->rec) {
            if ($filterRec->point) {
                $query->where("#{$pointFld} = {$filterRec->point}");
            }
        }
    }
    
    
    /**
     * Връща разрешените складове
     *
     * @param int $pointId
     * @return array $stores
     */
    public static function getStores($pointId)
    {
        $rec = static::fetchRec($pointId);
        $otherStores = static::getSettings($pointId, 'otherStores');
        
        $stores = array($rec->storeId => $rec->storeId);
        $stores += keylist::toArray($otherStores);
        
        return $stores;
    }
    
    
    /**
     * След извличане на настройките
     */
    protected static function on_AfterGetSettings($mvc, &$res, $rec, $field = null, &$inherited = null)
    {
        $inherited = is_object($inherited) ? $inherited : new stdClass();
        
        if(isset($field)){
            if(array_key_exists($field, static::$fieldMap)){
                $res = pos_Setup::get(static::$fieldMap[$field]);
            }
        } else {
            foreach (static::$fieldMap as $field => $const){
                if(empty($res->{$field})){
                    $res->{$field} = pos_Setup::get($const);
                    $inherited->{$field} = $field;
                }
            }
        }
    }
}
