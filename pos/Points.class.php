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
    public $loadList = 'plg_Created, plg_RowTools2, plg_Rejected, doc_FolderPlg,
                     pos_Wrapper, plg_Sorting, plg_Printing, plg_Current,plg_State, plg_Modified';
    
    
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
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar(16)', 'caption=Наименование, mandatory,oldFieldName=title');
        $this->FLD('caseId', 'key(mvc=cash_Cases, select=name)', 'caption=Каса, mandatory');
        $this->FLD('storeId', 'key(mvc=store_Stores, select=name)', 'caption=Склад, mandatory');
        $this->FLD('policyId', 'key(mvc=price_Lists, select=title)', 'caption=Политика, silent, mandotory');
        $this->FLD('payments', 'keylist(mvc=cond_Payments, select=title)', 'caption=Безналични налични на плащане->Позволени,placeholder=Всички');
        $this->FLD('theme', 'enum(default=Стандартна,dark=Тъмна)', 'caption=Тема,notNull,value=default');
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
        $paymentIds = keylist::toArray(pos_Points::fetchField($pointId, 'payments'));
        if (count($paymentIds)) {
            $paymentQuery->in('id', $paymentIds);
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
        $data->form->setDefault('policyId', cat_Setup::get('DEFAULT_PRICELIST'));
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
            
            // .. и имаме право да изберем склада и, логваме се в него
            if (store_Stores::haveRightFor('select', $rec->storeId)) {
                store_Stores::selectCurrent($rec->storeId);
            }
            
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
        $canActivateStore = bgerp_plg_FLB::canUse('store_Stores', $rec->storeId, $userId);
        $canActivateCase = bgerp_plg_FLB::canUse('cash_Cases', $rec->caseId, $userId);
        $res = ($canActivateStore === true && $canActivateCase === true);
        
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
}
