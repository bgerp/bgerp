<?php


/**
 * Клас 'cond_PaymentMethods' - Начини на плащане
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cond_PaymentMethods extends embed_Manager
{
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, cond_Wrapper, plg_State2, plg_Translate';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'title, sysId, lastUsedOn=Последно, state, createdBy,createdOn';
    
    
    /**
     * Заглавие
     */
    public $title = 'Методи на плащане';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Метод на плащане';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,admin';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,admin';
    
    
    /**
     * Кой може да променя състоянието на Методите на плащане
     */
    public $canChangestate = 'ceo,admin';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,admin';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'ceo, admin';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, admin';
    
    
    /**
     * Дали при обновяване от импорт на същестуващ запис да се запази предишното състояние или не
     *
     * @see plg_State2
     */
    public $updateExistingStateOnImport = false;
    
    
    /**
     * Шаблон за единичен изглед
     */
    public $singleLayoutFile = 'cond/tpl/SinglePaymentMethod.shtml';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'lastUsedOn';
    
    
    /**
     * Свойство, което указва интерфейса на вътрешните обекти
     */
    public $driverInterface = 'cond_OnlinePaymentIntf';
    
    
    /**
     * Как се казва полето за избор на вътрешния клас
     */
    public $driverClassField = 'onlinePaymentDriver';
    
    
    /**
     * Задължително ли е полето за избор на драйвер
     */
    public $mandatoryDriverField = false;
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('sysId', 'varchar(16)', 'caption=Системно ID, input=none');
        $this->FLD('name', 'varchar', 'caption=Наименование');
        $this->FNC('title', 'varchar', 'caption=Описание, input=none, oldFieldName=description');
        $this->FLD('type', 'enum(,cash=В брой,bank=По банков път,intercept=С прихващане,card=С карта,factoring=Факторинг,postal=Пощенски паричен превод)', 'caption=Вид плащане');
        $this->FLD('onlinePaymentDriver', 'class(interface=cond_OnlinePaymentIntf,allowEmpty,select=title)', 'caption=Онлайн плащане->Вид,silent,removeAndRefreshForm=type');
        $this->FLD('onlinePaymentText', 'text(rows=3)', 'caption=Онлайн плащане->Текст');
        $this->FLD('downpayment', 'percent(min=0,max=1)', 'caption=Авансово плащане->Дял,hint=Процент,oldFieldName=payAdvanceShare');
        $this->FLD('paymentBeforeShipping', 'percent(min=0,max=1)', 'caption=Плащане преди получаване->Дял,hint=Процент,oldFieldName=payBeforeReceiveShare');
        $this->FLD('paymentOnDelivery', 'percent(min=0,max=1)', 'caption=Плащане при доставка->Дял,hint=Процент,oldFieldName=payOnDeliveryShare');
        $this->FLD('eventBalancePayment', 'enum(,invDate=след датата на фактурата||after invoice date,invEndOfMonth=след края на месеца на фактурата||after the end of invoice\'s month)', 'caption=Балансово плащане->Събитие');
        $this->FLD('timeBalancePayment', 'time(uom=days,suggestions=незабавно|15 дни|30 дни|60 дни)', 'caption=Балансово плащане->Срок,hint=дни,oldFieldName=payBeforeInvTerm');
        $this->FLD('discountPercent', 'percent(min=0,max=1)', 'caption=Отстъпка за предсрочно плащане->Процент,hint=Процент');
        $this->FLD('discountPeriod', 'time(uom=days,suggestions=незабавно|5 дни|10 дни|15 дни)', 'caption=Отстъпка за предсрочно плащане->Срок,hint=Дни');
        $this->FLD('lastUsedOn', 'datetime(format=smartTime)', 'caption=Последна употреба,input=none,column=none');
        
        $this->setDbUnique('sysId');
    }
    
    
    /**
     * Изчисляване на заглавието
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     *
     * @return void
     */
    protected static function on_CalcTitle($mvc, $rec)
    {
        if ($rec->name) {
            $rec->title = tr($rec->name);
            
            return;
        }
        
        Mode::push('text', 'plain');
        
        if ($rec->downpayment) {
            $title .= round($rec->downpayment * 100, 2). '% ' . tr('авансово||downpayment');
        }
        
        if ($rec->paymentBeforeShipping) {
            $title .= ($title ? ', ' : '') . round($rec->paymentBeforeShipping * 100, 2). '% ' . tr('преди експедиция||before shipment');
        }
        
        if ($rec->paymentOnDelivery) {
            $title .= ($title ? ', ' : '') . round($rec->paymentOnDelivery * 100, 2) . '% ' . tr('при доставка||after delivery');
        }
        
        if ($rec->timeBalancePayment) {
            $title .= ($title ? ', ' : '') .  round((1 - $rec->downpayment - $rec->paymentBeforeShipping - $rec->paymentOnDelivery) * 100, 2) . '% ' . tr('до||in') . ' ' . $mvc->getVerbal($rec, 'timeBalancePayment') . ' ' . $mvc->getVerbal($rec, 'eventBalancePayment');
            
            if ($rec->type && $rec->type != 'bank') {
                $title .= ', ' . mb_strtolower($mvc->getVerbal($rec, 'type'));
            }
        }
        
        if ($rec->discountPercent) {
            $title .= ($title ? ', ' : '') . tr('отстъпка||discount') . ' ' . round($rec->discountPercent * 100, 2) . '% ' . tr('при цялостно плащане до||if paid in full within') . ' ' . $mvc->getVerbal($rec, 'discountPeriod');
        }
        
        $rec->title = $title;
        
        Mode::pop('text');
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $rec = &$form->rec;
            $total = $rec->downpayment + $rec->paymentBeforeShipping + $rec->paymentOnDelivery;
            
            if ($total > 1) {
                $form->setError('downpayment,paymentBeforeShipping,paymentOnDelivery', 'Въведените проценти не бива да надвишават 100%');
            }
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = $form->rec;
        
        // Ако има избран драйвер за онлайн плащане, с дефиниран вид плащане задава се той
        if(isset($rec->onlinePaymentDriver)){
            if($Driver = self::getDriver($rec)){
                if($type = $Driver->getPaymentType($rec)){
                    $rec->type = $type;
                    $form->setReadOnly('type');
                }
            }
        }
        
        if(isset($rec->id) && $rec->createdBy == core_Users::SYSTEM_USER){
            foreach (array('name', 'type', 'downpayment', 'paymentBeforeShipping', 'discountPeriod', 'paymentOnDelivery', 'discountPercent', 'timeBalancePayment', 'eventBalancePayment') as $fld){
                $form->setReadOnly($fld);
            }
        }
    }
    
    
    /**
     * Дали подадения метод е Наложен платеж (Cash on Delivery)
     *
     * @param mixed $payment - ид или име на метод
     *
     * @return bool
     */
    public static function isCOD($payment)
    {
        // Ако няма избран метод се приема, че е COD
        if (!$payment) {
            
            return true;
        }
        
        $where = (is_numeric($payment)) ? $payment : "#sysId = '{$payment}'";
        $sysId = static::fetchField($where, 'sysId');
        
        return ($sysId == 'COD');
    }
    
    
    /**
     * Връща масив съдържащ плана за плащане
     *
     * @param int   $pmId   - ид на метод
     * @param float $amount - сума
     * @param invoiceDate - дата на фактуриране (ако няма е датата на продажбата)
     *
     * @return array $res - масив съдържащ информация за плащането
     *
     * 		['downpayment'] 		      - сума за авансово плащане
     * 		['paymentBeforeShipping']     - сума за плащане преди експедиране
     * 		['paymentOnDelivery']         - сума за плащане при получаване
     * 		['paymentAfterInvoice']       - сума за плащане след фактуриране
     * 		['deadlineForBalancePayment'] - крайна дата за окончателно плащане
     * 		['timeBalancePayment']        - срок за окончателно плащане
     */
    public static function getPaymentPlan($pmId, $amount, $invoiceDate)
    {
        expect($rec = self::fetch($pmId));
        $res = array();
        
        if ($rec->downpayment) {
            $res['downpayment'] = $rec->downpayment * $amount;
        }
        
        if ($rec->paymentBeforeShipping) {
            $res['paymentBeforeShipping'] = $rec->paymentBeforeShipping * $amount;
        }
        
        if ($rec->paymentOnDelivery) {
            $res['paymentOnDelivery'] = $rec->paymentOnDelivery * $amount;
        }
        
        $paymentAfterInvoice = 1 - $rec->paymentOnDelivery - $rec->paymentBeforeShipping - $rec->downpayment;
        $paymentAfterInvoice = round($paymentAfterInvoice * $amount, 4);
        $res['timeBalancePayment'] = $rec->timeBalancePayment;
        
        if ($paymentAfterInvoice > 0) {
            $res['paymentAfterInvoice'] = $paymentAfterInvoice;
            $res['deadlineForBalancePayment'] = dt::addSecs($rec->timeBalancePayment, $invoiceDate, false);
        }
        
        // Ако плащането е на момента, крайната дата за плащане е подадената дата
        if ($rec->sysId == 'COD') {
            $res['deadlineForBalancePayment'] = dt::verbal2mysql($invoiceDate, false);
        }
        
        return $res;
    }
    
    
    /**
     * Подготвя условията за плащане
     */
    public static function preparePaymentPlan(&$data, $pmId, $amount, $invoiceDate, $currencyId)
    {
        $planArr = self::getPaymentPlan($pmId, $amount, $invoiceDate);
        
        if (countR($planArr)) {
            $Double = cls::get('type_Double');
            $Double->params['decimals'] = 2;
            $Date = cls::get('type_Date');
            
            foreach ($planArr as $key => &$value) {
                if ($key != 'deadlineForBalancePayment') {
                    $value = $Double->toVerbal($value);
                    $value = "<span class='cCode'>{$currencyId}</span> {$value}";
                } else {
                    $value = $Date->toVerbal($value);
                }
            }
            
            $data->paymentPlan = $planArr;
        }
    }
    
    
    /**
     * Дали платежния план е просрочен
     *
     * @param array $payment    - платежния план (@see static::getPaymentPlan)
     * @param float $restAmount - оставаща сума за плащане
     *
     * @return bool
     */
    public static function isOverdue($payment, $restAmount)
    {
        expect(is_array($payment) && isset($restAmount));
        $today = dt::today();
        $restAmount = round($restAmount, 4);
        
        // Ако остатъка за плащане е 0 или по-малко
        if ($restAmount <= 0) {
            
            return false;
        }
        
        // Ако няма крайна дата на плащане, не е просрочена
        if (!$payment['deadlineForBalancePayment']) {
            
            return false;
        }
        
        // Ако текущата дата след крайния срок за плащане, документа е просрочен
        return ($today > $payment['deadlineForBalancePayment']);
    }
    
    
    /**
     * Дали платежния метод има авансова част
     *
     * @param int $id - ид на метод
     *
     * @return bool
     */
    public static function hasDownpayment($id)
    {
        // Ако няма избран метод се приема, че няма авансово плащане
        if (!$id) {
            
            return false;
        }
        
        expect($rec = static::fetch($id));
        
        return ($rec->downpayment) ? true : false;
    }
    
    
    /**
     * Сортиране по name
     */
    protected static function on_BeforePrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('#title');
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    protected static function on_AfterSetupMvc($mvc, &$res)
    {
        $file = 'cond/csv/PaymentMethods.csv';
        $fields = array(
            0 => 'sysId',
            1 => 'name',
            2 => 'downpayment',
            3 => 'paymentBeforeShipping',
            4 => 'paymentOnDelivery',
            5 => 'eventBalancePayment',
            6 => 'timeBalancePayment',
            7 => 'discountPercent',
            8 => 'discountPeriod',
            9 => 'type',
        );
        
        $cntObj = csv_Lib::importOnce($mvc, $file, $fields);
        
        $res .= $cntObj->html;
    }
    
    
    /**
     * Връща очакваното авансово плащане
     *
     * @param int   $id     - ид на платежен метод
     * @param float $amount - сума
     *
     * @return float $amount - сумата на авансовото плащане
     */
    public static function getDownpayment($id, $amount)
    {
        // Ако няма ид, няма очакван аванс
        if (!$id) {
            
            return;
        }
        
        // Ако сумата е 0, няма очакван аванс
        if ($amount == 0) {
            
            return;
        }
        
        // Трябва да са подадени валидни данни
        expect(is_numeric($amount));
        expect($rec = static::fetch($id));
        
        // Ако няма авансово плащане в метода, няма очакван аванс
        if (empty($rec->downpayment)) {
            
            return;
        }
        
        // Изчисляване на очаквания аванс
        $amount = $rec->downpayment * $amount;
        
        // Връщане на аванса
        return $amount;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if ($action == 'delete' && isset($rec->lastUsedOn)) {
            $res = 'no_one';
        }
    }
    
    
    /**
     * Кой е драйвера за онлайн плащането
     *
     * @param int $id
     * @return cond_OnlinePaymentIntf|false
     */
    public static function getOnlinePaymentDriver($id)
    {
        $onlinePaymentDriver = self::fetchField($id, 'onlinePaymentDriver');
        if(!empty($onlinePaymentDriver) && cls::load($onlinePaymentDriver, true)){
            
            return cls::getInterface('cond_OnlinePaymentIntf', $onlinePaymentDriver);
        }
        
        return false;
    }
    
    
    /**
     * След рендиране на еденичния изглед
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        $rec = $data->rec;
        
        if($Driver = $mvc->getDriver($rec)){
            $fields = $mvc->getDriverFields($Driver);
            if(is_array($fields)){
                foreach ($fields as $field => $caption){
                    $str = "<span class='quiet'>{$caption}</span>: {$data->row->{$field}}";
                    $tpl->append($str, 'DRIVER_DATA');
                }
            }
        }
    }
    
    
    /**
     * Модификация на изгледа на количката в е-шоп
     * 
     * @param int $id
     * @param stdClass $cartRec
     * @param stdClass $cartRow
     * @param core_ET $tpl
     * 
     * @return void
     */
    public static function addToCartView($id, $cartRec, $cartRow, &$tpl)
    {
        
    }
}
