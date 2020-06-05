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
    
    
    /**
     * Името на сесийната променлива за записване на профила
     */
    protected $profileModeName = 'borsaProfile';
    
    
    /**
     * Името на сесийната променлива за записване на позволените продукти
     */
    protected $allowedProdModeName = 'allowedProd';
    
    
    /**
     * През колко време да се обновява по AJAX
     */
    protected $lotAjaxRefreshTime = 5000;
    
    
    /**
     * Кой може да променя състоянието на документите
     *
     * @see plg_State2
     */
    public $canChangestate = 'borsa, ceo';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'borsa_Wrapper, plg_Rejected, plg_Created, plg_State2, plg_RowTools2, plg_Modified';
    
    
    /**
     * 
     */
    public function description()
    {
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,maxSuggestions=100,forceAjax)', 'class=w100,caption=Артикул,mandatory,silent,removeAndRefreshForm=basePrice');
        $this->FLD('periodType', 'enum(day=Ден, week=Седмица, month=Месец)', 'caption=Вид период,mandatory');
        $this->FLD('basePrice', 'double(smartRound,decimals=2)', 'caption=Базова цена,mandatory');
        $this->FNC('quantity', 'double(Min=0)', 'caption=Количество,hint=Количество по подразбиране за офериране,input');
        $this->FLD('priceChange', 'table(columns=period|priceChange,captions=Период|Промяна %,validate=borsa_Lots::priceChangeValidate, period_opt=|01|02|03|04|05|06|07|08|09|10|11|12)', 'caption=Промяна на цена');
        $this->FLD('canConfirm', 'userList(roles=sales)', array('caption' => 'Потребители, които могат да одобряват заявките->Потребители'));
        $this->FLD('formInfo', 'text(rows=4)', 'caption=Допълнителна информация във формата->Български');
        $this->FLD('formInfoEn', 'text(rows=4)', 'caption=Допълнителна информация във формата->Английски');
        
        $this->FNC('productName', 'varchar');
        
        $this->setDbUnique('productId');
    }
    
    
    /**
     * Изчисляване на името на продукта
     * 
     * @param borsa_Lots $mvc
     * @param stdClass $rec
     */
    function on_CalcProductName($mvc, $rec)
    {
        if ($rec->productId) {
            $rec->productName = cat_Products::getVerbal($rec->productId, 'name');
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
            
            $mId = cat_Products::fetchField($data->form->rec->productId, 'measureId');
            
            if ($mId) {
                $sName = cat_UoM::getShortName($mId);
                $data->form->setField('quantity', array('unit' => $sName));
            }
        }
        
        $data->form->setDefault('formInfo', borsa_Setup::get('ADD_BID_INFO'));
        $data->form->setDefault('formInfoEn', tr(borsa_Setup::get('ADD_BID_INFO')));
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
            if (trim($val) && !is_numeric($val)) {
                $msg = 'Полето трябва да съдържа само цели числа';
                $resArr['errorFields']['priceChange'][$key] = $msg;
                $resArr['error'] .= "|*<li>| {$msg}";
            }
            
            if (!strlen(trim($val))) {
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
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc     $mvc     Мениджър, в който възниква събитието
     * @param int          $id      Първичния ключ на направения запис
     * @param stdClass     $rec     Всички полета, които току-що са били записани
     * @param string|array $fields  Имена на полетата, които sa записани
     * @param string       $mode    Режим на записа: replace, ignore
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        $pArr = $this->getChangePeriods($rec->id);
        
        // Добавяме периоди с количества по подразбиране
        foreach ($pArr as $pVal) {
            $pRec = borsa_Periods::getPeriodRec($rec->id, $pVal['bPeriod'], $pVal['ePeriod']);
            if (!$pRec) {
                $pRec = new stdClass();
                $pRec->lotId = $rec->id;
                $pRec->from = $pVal['bPeriod'];
                $pRec->to = $pVal['ePeriod'];
                $pRec->qAvailable = $rec->quantity ? $rec->quantity : 0;
                $pRec->qBooked = 0;
                $pRec->qConfirmed = 0;
                $pRec->state = 'active';
                borsa_Periods::save($pRec);
            }
        }
    }
    
    
    /**
     * Колбек функция, която се извиква от линковете в изпратените писма
     */
    public static function callback_openBid($data)
    {
        $me = cls::get(get_called_class());
        
        if(!self::haveRightFor('list')) {
            
            $me->setAllowedProdIds($data['id']);
            
            if (Mode::get($me->profileModeName) != $data['id']) {
                Mode::setPermanent($me->profileModeName, $data['id']);
                
                $company = borsa_Companies::getVerbal($data['id'], 'companyId');
                
                $title = core_Setup::get('EF_APP_TITLE', true);
                
                $status = "|*<div style='margin-bottom:5px;'>|Вход в|* <b>{$title}:</b></div><div><b>{$company}</b></div>";
                                status_Messages::newStatus($status);
                                vislog_History::add('Логване в борсата');
            }
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
    
    
    /**
     * 
     * 
     * @see core_Manager::act_List()
     */
    function act_List()
    {
        if (!$this->haveRightFor('list')) {
            
            if (Mode::get($this->profileModeName)) {
                
                return new Redirect(array($this, 'Show'));
            }
        }
        
        return parent::act_List();
    }
    
    
    /**
     * Екшън за показване на всички периоди за съответния продукт
     */
    function act_Show()
    {
        if ($this->haveRightFor('list')) {
            
            return new Redirect(array($this, 'List'));
        }
        
        $cId = Mode::get($this->profileModeName);
        
        expect($cId);
        
        $prodOptArr = $this->getAllowedProdId($cId);
        
        if (!$lotId = Request::get('id', 'int')) {
            $lotId = key($prodOptArr);
        }
        
        if ($lotId && !$prodOptArr[$lotId]) {
            $lotId = key($prodOptArr);
        }
        
        if (!$lotId || !$prodOptArr[$lotId] || !($lRec = $this->fetch($lotId))) {
            $tpl = new ET("<h1>" . tr('Няма опции за офериране') . "</h1>");
            
            return $this->getExternalLayout($tpl);
        }
        
        unset($prodOptArr[$lotId]);
        
        $tpl = new ET('<h2>[#LOT_INFO#]</h2><div id="prodDesc">[#PROD_DESC#]</div> <div>[#TABLE#]</div><!--ET_BEGIN OTHER_LOTS--><div class="otherLots"><h3>[#OTHER_LOTS_INFO#]</h3>[#OTHER_LOTS#]</div><!--ET_END OTHER_LOTS-->');
        
        $links .= '';
        foreach ((array)$prodOptArr as $pId) {
            $pLRec = $this->fetch($pId);
            $pLRec->productName = trim($pLRec->productName) ? $pLRec->productName : tr("Продукт") . $pId;
            $links .= "<li>" . ht::createLink($pLRec->productName, array($this, 'show', $pId)) . "</li>";
        }
        
        if ($links) {
            $tpl->replace($links, 'OTHER_LOTS');
            $tpl->replace(tr('Други продукти за офериране'), 'OTHER_LOTS_INFO');
        }
        
        $tpl->replace(tr(borsa_Setup::get('LOT_INFO')), 'LOT_INFO');
        
        $rows = array();
        
        $baseCurrencyCode = acc_Periods::getBaseCurrencyCode();
        
        $Double = cls::get('type_Double');
        $Double->params['smartRound'] = 'smartRound';
        $Double->params['minDecimals'] = 2;
        $Double->params['maxDecimals'] = 4;
        
        $this->FNC('qAvailable', 'double(smartRound,decimals=2)');
        $this->FNC('qBooked', 'double(smartRound,decimals=2)');
        $this->FNC('qConfirmed', 'double(smartRound,decimals=2)');
        $this->FNC('qFree', 'double(smartRound,decimals=2)');
        $this->FNC('price', 'double(smartRound,decimals=2)');
        
        $sName = '';
        $mId = cat_Products::fetchField($lRec->productId, 'measureId');
        if ($mId) {
            $sName = cat_UoM::getShortName($mId);
        }
        
        $table = new ET('<table class="listTable" id="lotTable"> [#PERIOD#] </table>');
        
        // За всеки период, добавяме по един ред в таблицата
        $pArr = $this->getPeriods($lotId);
        foreach ($pArr as $pId => $pVal) {
            $perRec = $pVal['rec'];
            
            $pRow = new ET("<tr> <td colspan=3 class='periodHead'> [#DATE#] <span class='priceTag'>[#PRICE#]</span> </td> </tr> <tr> <td class='newsCol'> [#QUANTITY#] </td> <td class='state-active'> [#CBIDS#] </td> <td class='state-draft' id='pId{$pId}'> [#QBIDS#] </td> </tr>");

            // Дата
            $pRow->replace($this->getPeriodVerb($pVal), 'DATE');
            
            // Цена
            $price = $this->fields['price']->type->toVerbal($pVal['price']);
            $price =  "<div class='priceBlock'><span class='priceInfo'> {$price} <span class='small'>{$baseCurrencyCode}</span></span>" . ' ' . tr('за') . ' ' . $sName . " ". tr('без ДДС') . "</div>";
            $pRow->replace($price, 'PRICE');
            
            // Количества
            $qAvailable = $perRec->qAvailable ? $perRec->qAvailable : 0;
            $qAvailable = $this->fields['qAvailable']->type->toVerbal($qAvailable);
            
            $qBooked = $perRec->qBooked ? $perRec->qBooked : 0;
            $qBooked = $this->fields['qBooked']->type->toVerbal($qBooked);
            
            $qConfirmed = $perRec->qConfirmed ? $perRec->qConfirmed : 0;
            $qConfirmed = $this->fields['qConfirmed']->type->toVerbal($qConfirmed);
            
            $qFree = $perRec->qAvailable - $perRec->qConfirmed;
            $haveQuantity = ($qFree <= 0) ? false : true;
            $qFree = $this->fields['qFree']->type->toVerbal($qFree);

            $quantity = "<table>";
            $quantity .= "<tr><th colspan='2'>" .  tr('Количества') . "</td></tr>";
            $quantity .= "<tr><td class='name'>" . tr('Общо') . ":</td><td class='value'>" . $qAvailable . "</td></tr>";
            $quantity .= "<tr><td class='name'>" . tr('Заявено') . ":</td><td class='value'>" . $qBooked . "</td></tr>";
            $quantity .= "<tr><td class='name'>" . tr('Потвърдено') . ":</td><td class='value'>" . $qConfirmed . "</td></tr>";
            $quantity .= "<tr><td class='name'>" . tr('Свободно') . ":</td><td class='value'>" . $qFree . "</td></tr>";
            $quantity .= "</table>";
            $pRow->replace($quantity, 'QUANTITY');
            
            // Потвърдени и заявени количества

            $cBidsRows = '<table>';
            $cBidsRows .= "<tr><th colspan='2'>" . tr('Потвърдени') . "</th></tr>";
            

            $qBidsRows = '<table>';
            $qBidsRows .= "<tr><th colspan='2'>" . tr('Заявени') . "</th></tr>";
            
            $bQuery = borsa_Bids::getQuery();
            $bQuery->where(array("#periodId = '[#1#]'", $perRec->id));
            $bQuery->where(array("#lotId = '[#1#]'", $lotId));
            $bQuery->show('companyId, quantity, state, createdOn');
            $bQuery->orderBy('createdOn', 'ASC');
            
            while ($bRec = $bQuery->fetch()) {
                
                $v = borsa_Bids::recToVerbal($bRec, 'companyId, quantity, state');
                if ($cId != $bRec->companyId) {
                    $v->companyId = '*************';
                }
                
                $v->quantity = ht::createHint($v->quantity, dt::mysql2verbal($bRec->createdOn, 'd.m.Y H:i:s'));
                
                $rowStr = "<tr><td>{$v->companyId}</td><td class='quantityField'>{$v->quantity}</td></tr>";
                if ($bRec->state == 'draft') {
                    $qBidsRows .= $rowStr;
                }
                
                if ($bRec->state == 'active') {
                    $cBidsRows .= $rowStr;
                }
            }
            
            if ($haveQuantity) {
                $qBidsRows .= "<tr><td colspan=2 align='center'>" . ht::createBtn('Заяви', array($this, 'Bid', $lotId, 'period' => $pId), false, false, 'title=Добавяне на заявка') . "</td></tr>";
            }
            
            $qBidsRows .= '</table>';
            $cBidsRows .= '</table>';
            
            $pRow->replace($cBidsRows, 'CBIDS');
            $pRow->replace($qBidsRows, 'QBIDS');
            
            $table->append($pRow, 'PERIOD');
        }
        
        if (empty($pArr)) {
            // Добавяме таблица
            $tpl->replace("<h3>" . tr('Няма периоди за офериране') . "</h3>", 'TABLE');
        } else {
            // Добавяме таблица
            $tpl->replace($table, 'TABLE');
        }
        
        // Добавяме описание на продукта
        Mode::push('text', 'xhtml');
        $dDesc = cat_Products::getAutoProductDesc($lRec->productId, null, 'detailed', 'public', core_Lg::getCurrent());
        Mode::pop('text');
        $tpl->replace($dDesc, 'PROD_DESC');
        
        $flashId = Request::get('flash');
        if ($flashId) {
            jquery_Jquery::run($tpl, "flashDocInterpolation('{$flashId}');", true);
        }
        
        $tpl = $this->getExternalLayout($tpl, $lRec->productName);
        
        if (Request::get('ajax_mode')) {
            // По AJAX подменяме само някои от елементите
            $res = array();
            $resObj = new stdClass();
            $resObj->func = 'replaceById';
            $resObj->arg = array('html' => (string) $tpl, 'Ids' => 'lotTable,prodDesc');
            
            core_App::outputJson(array($resObj));
        } else {
            core_Ajax::subscribe($tpl, getCurrentUrl(), 'updateLots', $this->lotAjaxRefreshTime);
        }
        
        return $tpl;
    
    }
    
    
    /**
     * Екшън за добавяне на заявка
     *
     * @return Redirect|core_Et
     */
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
        
        $bQurr = acc_Periods::getBaseCurrencyCode();

        $mId = cat_Products::fetchField($rec->productId, 'measureId');
        if ($mId) {
            $sName = cat_UoM::getShortName($mId);
        }
        
        $available = $pArr[$period]['rec']->qAvailable - $pArr[$period]['rec']->qConfirmed;
        
        $form->FNC('qBid', "double(Min=0, max={$available})", "caption=Количество,input, mandatory,unit={$sName}");
        $form->FNC('price', 'double(smartRound,decimals=4)', "caption=Цена,input,unit={$bQurr}");
        $form->FNC('note', 'text(rows=2)', 'caption=Забележка,input');
        
        $form->setReadOnly('price', $pArr[$period]['price']);
        
        $retUrl = array($this, 'Show', $id, 'flash' => 'pId' . $period, '#' => 'pId' . $period);
        
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png, title = Подаване на оферта');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        $form->input();
        
        if ($form->isSubmitted()) {
            $nRec = new stdClass();
            $nRec->lotId = $id;
            $nRec->periodId = $pArr[$period]['rec']->id;
            $nRec->price = $pArr[$period]['price'];
            $nRec->quantity = $form->rec->qBid;
            $nRec->note = $form->rec->note;
            $nRec->companyId = $cId;
            $nRec->ip = core_Users::getRealIpAddr();
            $nRec->brid = log_Browsers::getBrid();
            $nRec->state = 'draft';
            
            borsa_Bids::save($nRec);
            
            $msg = '';
            if ($nRec->id) {
                $msg = '|Вашата заявка беше добавена успешно';
            }
            
            return new Redirect($retUrl, $msg);
        }
        
        $form->title = 'Добавяне на оферта за|* ' . mb_strtolower($this->getPeriodVerb($pArr[$period]));
        
        $formInfo = '';
        if (core_Lg::getCurrent() == 'bg') {
            if ($rec->formInfo) {
                $formInfo = $this->getVerbal($rec, 'formInfo');
            }
        } else {
            if ($rec->formInfoEn) {
                $formInfo = $this->getVerbal($rec, 'formInfoEn');
            }
        }
        
        if ($formInfo) {
            $form->info = '<b>' . $formInfo . '</b>';
        }
        
        $tpl = $form->renderHtml();
        
        return $this->getExternalLayout($tpl, $data->pageTitle);
    
    }
    
    
    /**
     * Задаване на позволените продукти за покзване към този профил
     * 
     * @param null|integer $cId
     */
    protected function setAllowedProdIds($cId = null)
    {
        if (!isset($cId)) {
            $cId = Mode::get($this->profileModeName);
        }
        
        expect($cId);
        
        $cRec = borsa_Companies::fetch($cId);
        
        expect($cRec);
        
        $qProd = borsa_Lots::getQuery();
        $qProd->where("#state != 'rejected'");
        
        $qProd->show('productId');
        
        if ($cRec->allowedLots) {
            $qProd->in('id', type_Keylist::toArray($cRec->allowedLots));
        }
        
        $resArr = array();
        while ($qRec = $qProd->fetch()) {
            $resArr[$qRec->id] = $qRec->id;
        }
        
        Mode::setPermanent($this->allowedProdModeName, $resArr);
    }
    
    
    /**
     * Връща масив с позволените продикти за офериране
     * 
     * @param null|integer $cId
     * 
     * @return null|array
     */
    protected function getAllowedProdId($cId = null)
    {
        $res = Mode::get($this->allowedProdModeName, $resArr);
        
        if (!isset($res) || (rand(1,10) == 5)) {
            $this->setAllowedProdIds($cId);
        }
        
        return Mode::get($this->allowedProdModeName, $resArr);
    }
    
    
    /**
     * Помощна функция за вземане на вербално представяне на съответния период
     * 
     * @param array $pVal
     * @param string $mask
     * 
     * @return string
     */
    protected static function getPeriodVerb($pVal, $mask = 'd.m.Y, l')
    {
        $keySel = null;
        $periodArr = plg_SelectPeriod::getOptions($keySel, $pVal['bPeriod'], $pVal['ePeriod']);
        
        if ($keySel && $periodArr[$keySel]) {
            $period = $periodArr[$keySel];
        }
        
        if (!$period) {
            if ($pVal['bPeriod'] == $pVal['ePeriod']) {
                $period = core_DateTime::mysql2verbal($pVal['bPeriod'], $mask);
            } else {
                $period = core_DateTime::mysql2verbal($pVal['bPeriod'], $mask) . ' - ' . core_DateTime::mysql2verbal($pVal['ePeriod'], $mask);
            }
        }
        
        return $period;
    }
    
    
    /**
     * Помощна функция за задаване на врапер за външната част
     * 
     * @param core_ET $tpl
     * @param null|string $pageTitle
     * 
     * @return core_ET
     */
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
            $layout = getTplFromFile('borsa/tpl/content.shtml');
        }
        
        $layout->replace($tpl, 'PAGE_CONTENT');
        
        $layout->removeBlocks();
        
        if (isset($pageTitle)) {
            // Добавяме титлата на страницата
            $layout->prepend($pageTitle . ' « ', 'PAGE_TITLE');
        }
        
        return $layout;
    }
    
    
    protected function getPeriods($id)
    {
        $pArr = $this->getChangePeriods($id);
        
        $resArr = array();
        
        foreach ($pArr as $pVal) {
            $pRec = borsa_Periods::getPeriodRec($id, $pVal['bPeriod'], $pVal['ePeriod'], 'active');
            if (!$pRec) {
                
                continue ;
            }
            $resArr[$pRec->id] = $pVal;
            $resArr[$pRec->id]['rec'] = $pRec;
        }
        
        return $resArr;
    }
    
    
    /**
     * Помощна функция за вземане на съответните периоди за лота
     * 
     * @param integer $id
     * 
     * @return array
     */
    protected function getChangePeriods($id)
    {
        $mArr = array();
        
        if (!$id || (!$pRec = $this->fetchRec($id))) {
            
            return $mArr;
        }
        
        if ($pRec->priceChange) {
            $pChange = @json_decode($pRec->priceChange);
        }
        
        // Добавяме текущия
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
     * 
     * @see cms_SourceIntf
     */
    public function getUrlByMenuId($cMenuId)
    {
        return array('borsa_Lots', 'Show', 'menuId' => $cMenuId);
    }
    
    
    /**
     * Връща URL към съдържание в публичната част, което отговаря на посочения запис
     * 
     * @see cms_SourceIntf
     */
    public function getUrlByRec($rec)
    {
        return array('borsa_Lots');
    }
    
    
    /**
     * Връща URL към съдържание във вътрешната част (работилницата), което отговаря на посоченото меню
     * 
     * @see cms_SourceIntf
     */
    public function getWorkshopUrl($cMenuId)
    {
        return array('borsa_Lots');
    }
    
    
    /**
     * Връща връща масив със заглавия и URL-ta, които отговарят на търсенето
     * 
     * @see cms_SourceIntf
     */
    public function getSearchResults($menuId, $q, $maxLimit = 10)
    {
        return array();
    }
    
    
    /**
     * Връща връща масив със обекти, съдържащи връзки към публичните страници, генерирани от този обект
     * 
     * @see cms_SourceIntf
     */
    public function getSitemapEntries($menuId)
    {
        return array();
    }
}
