<?php 

/**
 * 
 *
 * @category  bgerp
 * @package   borsa
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class borsa_Lots extends core_Master
{
    
    /**
     * Заглавие на модела
     */
    public $title = 'Лотове';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'borsa, ceo';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'borsa, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'borsa, ceo';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'borsa, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'borsa, ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    public $canReject = 'borsa, ceo';
    public $canRestore = 'borsa, ceo';
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'cms_SourceIntf';
    
    
    
    protected $powerRoles = 'borsa, ceo';
    
    protected $profileModeName = 'borsaProfile';
    
    protected $allowedProdModeName = 'allowedProd';
    
//     /**
//      * Кой може да променя състоянието на документите
//      *
//      * @see plg_State2
//      */
//     public $canChangestate = 'borsa, ceo';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'borsa_Wrapper, plg_Rejected, plg_Created, plg_State2, plg_RowTools';
//     public $loadList = 'borsa_Wrapper, plg_RowTools2, plg_State2, plg_Created, plg_Modified, plg_Search, plg_Sorting';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
//     public $searchFields = 'pattern';
    
    
    public function description()
    {
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,hasProperties=fixedAsset,maxSuggestions=100,forceAjax)', 'class=w100,caption=Артикул,mandatory,silent,removeAndRefreshForm=basePrice');
        $this->FLD('periodType', 'enum(day=Ден, week=Седмица, month=Месец)', 'caption=Вид период,mandatory');
        $this->FLD('basePrice', 'double(smartRound,decimals=2)', 'caption=Базова цена,mandatory');
        $this->FLD('quantity', 'double', 'caption=Количество,mandatory,hint=Количество за всеки период');
        $this->FLD('priceChange', 'table(columns=period|priceChange,captions=Период|Промяна %,validate=borsa_Lots::priceChangeValidate)', 'caption=Промяна на цена');
        
        $this->FNC('productName', 'varchar');
        
        $this->setDbUnique('productId');
    }
    
    function on_CalcProductName($mvc, $rec)
    {
        if ($rec->productId) {
            $rec->productName = cat_Products::fetchField($rec->productId, 'name');
        }
    }
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        if ($data->form->rec->productId) {
            $data->form->setDefault('basePrice', cls::get('cat_Products')->getDefaultCost($data->form->rec->productId, 1));
        }
    }
    
    
    /**
     * Помощна функция за валидиране на стойностите за промяна на цена
     * 
     * @param array $values
     * @param type_Table $Table
     * 
     * @return array
     */
    public static function priceChangeValidate($values, $Table)
    {
        $resArr = array();
        
        $pArr = array();
        foreach ((array)$values['period'] as $key => $val) {
            if (isset($pArr[$val])) {
                $msg = 'Дублирана стойност';
                $resArr['errorFields']['period'][$key] = $msg;
                $resArr['error'] .= "|*<li>| {$msg}";
            }
            
            if (!ctype_digit($val)) {
                $msg = 'Полето трябва да съдържа само цели числа';
                $resArr['errorFields']['period'][$key] = $msg;
                $resArr['error'] .= "|*<li>| {$msg}";
            }
            
            if ($val <= 0) {
                $msg = 'Стойността трябва да е положително число';
                $resArr['errorFields']['period'][$key] = $msg;
                $resArr['error'] .= "|*<li>| {$msg}";
            }
            
            if (!trim($val)) {
                $msg = 'Не е попълнена стойност';
                $resArr['errorFields']['period'][$key] = $msg;
                $resArr['error'] .= "|*<li>| {$msg}";
            }
            
            $pArr[$val] = $val;
        }
        
        foreach ((array)$values['priceChange'] as $key => $val) {
            if (!is_numeric($val)) {
                $msg = 'Полето трябва да съдържа само цели числа';
                $resArr['errorFields']['priceChange'][$key] = $msg;
                $resArr['error'] .= "|*<li>| {$msg}";
            }
            
            if (!trim($val)) {
                $msg = 'Не е попълнена стойност';
                $resArr['errorFields']['priceChange'][$key] = $msg;
                $resArr['error'] .= "|*<li>| {$msg}";
            }
            
            if ($val <= -100) {
                $msg = 'Много ниска стойност';
                $resArr['errorFields']['priceChange'][$key] = $msg;
                $resArr['error'] .= "|*<li>| {$msg}";
            }
        }
        
        return $resArr;
    }
    
    
    /**
     * Колбек функция, която се извиква от линковете в изпратените писма
     */
    public static function callback_openBid($data)
    {
        $me = cls::get(get_called_class());
        
        if(!haveRole($me->powerRoles)) {
            
            if (Mode::get($me->profileModeName) != $data['id']) {
                Mode::setPermanent($me->profileModeName, $data['id']);
                
                $company = borsa_Companies::getVerbal($data['id'], 'companyId');
                
                $title = core_Setup::get('EF_APP_TITLE', true);
                
                $status = "|*<div style='margin-bottom:5px;'>|Вход в|* <b>{$title}:</b></div><div><b>{$company}</b></div>";
                                status_Messages::newStatus($status);
                                vislog_History::add('Логване в борсата');
            }
            
            $me->setAllowedProdIds($data['id']);
        }
        
        redirect(array('borsa_Lots', 'Show'));
    }
    
    
    
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if ($rec->productId && cat_Products::haveRightFor('single', $rec->productId)) {
            $row->productId = cat_Products::getLinkToSingle($rec->productId, 'name');
        }
    }
    
    
    
    
    function act_List()
    {
        if (!$this->haveRightFor('list')) {
            
            if (Mode::get($this->profileModeName)) {
                
                return new Redirect(array($this, 'Show'));
            }
        }
        
        return parent::act_List();
    }
    
    protected function setAllowedProdIds($cId = null)
    {
        if (!isset($cId)) {
            $cId = Mode::get($this->profileModeName);
        }
        
        expect($cId);
        
        $cRec = borsa_Companies::fetch($cId);
        
        $qProd = borsa_Lots::getQuery();
        $qProd->where("#state != 'rejected'");
        
        $qProd->show('productId');
        
        if ($cRec->allowedProducts) {
            $qProd->in('productId', $cRec->allowedProducts);
        }
        
        $resArr = array();
        while ($qRec = $qProd->fetch()) {
            $resArr[$qRec->id] = $qRec->id;
        }
        
        Mode::setPermanent($this->allowedProdModeName, $resArr);
    }
    
    protected function getAllowedProdId($cId = null)
    {
        $res = Mode::get($this->allowedProdModeName, $resArr);
        
        if (!isset($res)) {
            $this->setAllowedProdIds($cId);
        }
        
        return Mode::get($this->allowedProdModeName, $resArr);
    }
    
    
    function act_Bid()
    {
        $id = Request::get('id', 'int');
        $period = Request::get('period', 'int');
        
        expect($id && isset($period));
        
        $rec = $this->fetch($id);
        
        if ($this->haveRightFor('single', $rec)) {
            
            return new Redirect(array($this, 'Single', $rec->id));
        }
        
        $cId = Mode::get($this->profileModeName);
        
        expect($cId);
        
        $prodOptArr = $this->getAllowedProdId($cId);
        
        expect(isset($prodOptArr[$id]));
        
        $pArr = $this->getPeriods($id);
        
        expect($pArr[$period]);
        
        $form = cls::get('core_Form');
        
        $form->layout = new ET("<div><form method=\"post\" action=\"{$act}\"><!--ET_BEGIN FORM_ERROR-->\n<div class=\"formError\" style='margin-top:10px'>[#FORM_ERROR#]</div><!--ET_END FORM_ERROR-->[#FORM_FIELDS#][#FORM_TOOLBAR#]</form></div>");
        
        $form->FNC('qBid', 'double(min=0)', 'caption=Количество,input, mandatory');
        $form->FNC('note', 'text(rows=2)', 'caption=Забележка,input');
        
        $noteStr = tr('Забележка');
        $qStr = tr('Количество');
        
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png, title = Подаване на оферта');
        
        
        $tpl = $form->renderHtml();
        
        $form->input();
        
        $perRec = borsa_Periods::fetch(array("#lotId = '[#1#]' AND #from = '[#2#]' AND #to = '[#3#]'", $id, $pArr[$period]['bPeriod'], $pArr[$period]['ePeriod']));
        
        if ($form->isSubmitted()) {
            
            if (!$perRec) {
                $perRec = new stdClass();
                $perRec->lotId = $id;
                $perRec->from = $pArr[$period]['bPeriod'];
                $perRec->to = $pArr[$period]['ePeriod'];
            }
            
            $perRec->qBooked += $form->rec->qBid;
            borsa_Periods::save($perRec);
            
            $nRec = new stdClass();
            $nRec->lotId = $id;
            $nRec->periodId = $perRec->id;
            $nRec->price = $pArr[$period]['price'];
            $nRec->quantity = $form->rec->qBid;
            $nRec->companyId = $cId;
            $nRec->ip = core_Users::getRealIpAddr();
            $nRec->brid = log_Browsers::getBrid();
            $nRec->state = 'draft';
            
            borsa_Bids::save($nRec);
            
            return new Redirect(array($this, 'Show', 'productId' => $id), '|Успешно оферирахте');
        }
        
        $bQuery = borsa_Bids::getQuery();
        $bQuery->where(array("#periodId = '[#1#]'", $perRec->id));
        $bQuery->where(array("#lotId = '[#1#]'", $id));
        $bQuery->show('companyId, quantity, state');
        $bQuery->orderBy('createdOn', 'DESC');
        while ($bRec = $bQuery->fetch()) {
            $v = borsa_Bids::recToVerbal($bRec, 'companyId, quantity, state');
            if ($cId != $bRec->companyId) {
                $v->companyId = '******';
            }
            $rows[$bRec->id] = $v;
        }
        
        $table = cls::get('core_TableView', array('mvc' => $this));
        $tpl->append($table->get($rows, "companyId=Фирма, quantity=Оферирано"));
        
        return $this->getExternalLayout($tpl, $data->pageTitle);
        
    }
    
    protected function getExternalLayout($tpl, $pageTitle = null)
    {
        Mode::set('wrapper', 'cms_page_External');
        
        $classId = $this->getClassId();
        if ($classId && ($menuId = cms_Content::fetchField("#source = {$classId}", 'id'))) {
            cms_Content::setCurrent($menuId);
        }
        
        if(Mode::is('screenMode', 'narrow')) {
            $layout = getTplFromFile('cms/themes/default/ArticlesNarrow.shtml');
        } else {
            $layout = getTplFromFile('cms/themes/default/Articles.shtml');
        }
        
        $layout->replace($tpl, 'PAGE_CONTENT');
        
        $layout->removeBlocks();
        
        if (isset($pageTitle)) {
            // Добавяме титлата на страницата
            $layout->prepend($pageTitle . ' « ', 'PAGE_TITLE');
        }
        
        return $layout;
    }
    
    
    function act_Show()
    {
        if ($this->haveRightFor('list')) {
            
            return new Redirect(array($this, 'List'));
        }
        
        $cId = Mode::get($this->profileModeName);
        
        expect($cId);
        
        $form = cls::get('core_Form');
        
        $act = toUrl(array($this, 'Show'));
        
        $form->layout = new ET("<div><form method=\"post\" action=\"{$act}\" [#FORM_ATTR#]><!--ET_BEGIN FORM_ERROR-->\n<div class=\"formError\" style='margin-top:10px'>[#FORM_ERROR#]</div><!--ET_END FORM_ERROR-->[#FORM_FIELDS#]</form></div>");
        
        $form->FNC('productId', 'key(mvc=borsa_Lots, select=productName)', 'input,caption=Продукт,removeAndRefreshForm,silent,submitFormOnRefresh');
        
        $form->formAttr['submitFormOnRefresh'] = 'submitFormOnRefresh';
        
        $prodOptArr = $this->getAllowedProdId($cId);
        
        if ($prodOptArr) {
            $pOptArr = $form->fields['productId']->type->prepareOptions();
            foreach ($prodOptArr as $pId) {
                $nProdArr[$pId] = $pOptArr[$pId];
            }
            $form->setDefault('productId', key($nProdArr));
        } else {
            $nProdArr = array();
        }
        
        $form->fields['productId']->type->options = $nProdArr;
        
        $form->input('productId', true);
        
        $tpl = $form->renderHtml();
        
        $table = cls::get('core_TableView', array('mvc' => $this, 'tableId' => 'periodTable'));
        
        $rows = array();
        
        $baseCurrencyCode = acc_Periods::getBaseCurrencyCode();
        
        $pRec = $this->fetch($form->rec->productId);
        
        $Double = cls::get('type_Double');
        $Double->params['smartRound'] = 'smartRound';
        $Double->params['minDecimals'] = 2;
        $Double->params['maxDecimals'] = 4;
        
        $this->FLD('qAviable', 'double(smartRound,decimals=2)');
        $this->FLD('qBooked', 'double(smartRound,decimals=2)');
        $this->FLD('qConfirmed', 'double(smartRound,decimals=2)');
        $this->FLD('qAll', 'double(smartRound,decimals=2)');
        
        $qAll = $pRec->quantity ? $pRec->quantity : 0;
        $qAll = $this->fields['qAll']->type->toVerbal($qAll);
        
        $pArr = $this->getPeriods($form->rec->productId);
        
        foreach ($pArr as $pId => $pVal) {
            
            $rows[$pId] = new stdClass();
            
            if ($pVal['bPeriod'] == $pVal['ePeriod']) {
                $rows[$pId]->period = core_DateTime::mysql2verbal($pVal['bPeriod'], 'smartDate');
            } else {
                $rows[$pId]->period = core_DateTime::mysql2verbal($pVal['bPeriod'], 'smartDate') . ' - ' . core_DateTime::mysql2verbal($pVal['ePeriod'], 'smartDate');
            }
            
            $rows[$pId]->period = ht::createLinkRef($rows[$pId]->period, array($this, 'bid', $form->rec->productId, 'period' => $pId));
            
            $rows[$pId]->price = $Double->toVerbal($pVal['price']);
            
            
            $perRec = borsa_Periods::fetch(array("#lotId = '[#1#]' AND #from = '[#2#]' AND #to = '[#3#]'", $form->rec->productId, $pArr[$pId]['bPeriod'], $pArr[$pId]['ePeriod']));
            
            $qAviable = $perRec->qAviable ? $perRec->qAviable : 0;
            $qAviable = $this->fields['qAviable']->type->toVerbal($qAviable);
            
            $qBooked = $perRec->qBooked ? $perRec->qBooked : 0;
            $qBooked = $this->fields['qBooked']->type->toVerbal($qBooked);
            
            $qConfirmed = $perRec->qConfirmed ? $perRec->qConfirmed : 0;
            $qConfirmed = $this->fields['qConfirmed']->type->toVerbal($qConfirmed);
            
            
            $rows[$pId]->qAviable = $qAviable;
            $rows[$pId]->qBooked = $qBooked;
            $rows[$pId]->qConfirmed = $qConfirmed;
            $rows[$pId]->qAll = $qAll;
        }
        
        $table = $table->get($rows, "period=Период, price=Цена|* {$baseCurrencyCode} (|Без ДДС|*), qAviable=Количество->Оферирано, qBooked=Количество->Запазено, qConfirmed=Количество->Потвърдено, qAll=Количество->Общо");
        $tpl->append($table);
        
//         if ($ajaxMode) {
//             $resObj = new stdClass();
//             $resObj->func = 'replaceById';
//             $resObj->arg = array('html' => $table->getContent(), 'Ids' => 'periodTable');
            
//             core_App::outputJson(array($resObj));
//         }
        
        return $this->getExternalLayout($tpl, $data->pageTitle);
        
    }
    
    protected function getPeriods($id)
    {
        $mArr = array();
        
        if (!$id || (!$pRec = $this->fetchRec($id))) {
            
            return $mArr;
        }
        
        if ($pRec->priceChange) {
            $pChange = @json_decode($pRec->priceChange);
        }
        $period = $pChange->period;
        $priceChange = $pChange->priceChange;
        $period[-1] = $priceChange[-1] = 0;
        
        if ($period) {
                
            ksort($period);
            $now = dt::now(false);
            
            
            
            foreach ($period as $pId => $pVal) {
                $beginPeriod = $endPeriod = '';
                
                if ($pRec->periodType == 'day') {
                    $endPeriod = $beginPeriod = dt::addDays($pVal, $now, false);
                } elseif ($pRec->periodType == 'week') {
                    $date = new DateTime($now);
                    $date->modify("+{$pVal} weeks");
                    $date->modify("monday this week");
                    $beginPeriod = $date->format('Y-m-d');
                    $date->modify("sunday this week");
                    $endPeriod = $date->format('Y-m-d');
                } elseif ($pRec->periodType == 'month') {
                    $date = new DateTime($now);
                    $date->modify("+{$pVal} months");
                    $beginPeriod = $date->format('Y-m-01');
                    $date->modify("last day of this month");
                    $endPeriod = $date->format('Y-m-d');
                } else {
                    expect(false, $pRec);
                }
                
                $mArr[$pId]['bPeriod'] = $beginPeriod;
                $mArr[$pId]['ePeriod'] = $endPeriod;
                
                $price = $pRec->basePrice;
                if ($priceChange[$pId]) {
                    $price = $price + ($price * $priceChange[$pId]/100);
                }
                
                $mArr[$pId]['price'] = $price;
            }
        }
        
        
        return $mArr;
    }
    
    
    /**
     * Връща URL към съдържание в публичната част, което отговаря на посоченото меню
     */
    public function getUrlByMenuId($cMenuId)
    {
        return array('borsa_Lots', 'Show', 'menuId' => $cMenuId);
    }
    
    
    /**
     * Връща URL към съдържание в публичната част, което отговаря на посочения запис
     */
    public function getUrlByRec($rec)
    {
        return array('borsa_Lots');
    }
    
    
    /**
     * Връща URL към съдържание във вътрешната част (работилницата), което отговаря на посоченото меню
     */
    public function getWorkshopUrl($cMenuId)
    {
        return array('borsa_Lots');
    }
    
    
    /**
     * Връща връща масив със заглавия и URL-ta, които отговарят на търсенето
     */
    public function getSearchResults($menuId, $q, $maxLimit = 10)
    {
        
        return array();
    }
    
    
    /**
     * Връща връща масив със обекти, съдържащи връзки към публичните страници, генерирани от този обект
     */
    public function getSitemapEntries($menuId)
    {
        
        return array();
    }
    
    
    
    
    
    
    
    
    
}
