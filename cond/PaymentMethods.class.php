<?php



/**
 * Клас 'cond_PaymentMethods' - Начини на плащане
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cond_PaymentMethods extends core_Master
{
    
    /**
     * Старо име на класа
     */
	var $oldClassName = 'salecond_PaymentMethods';
	
	
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, cond_Wrapper, plg_State';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, name, description';
    
    
    /**
     * Заглавие
     */
    var $title = 'Начини на плащане';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo, cond, admin';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,cond, admin';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,cond, admin';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo, cond, admin';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo, cond, admin';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo, cond, admin';
    
    
    /**
     * Шаблон за единичен изглед
     */
    var $singleLayoutFile = "cond/tpl/SinglePaymentMethod.shtml";
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'name';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        // Съкратено име на плащането
        $this->FLD('sysId', 'varchar(16)', 'caption=Системно ID, input=none');

        // Съкратено име на плащането
        $this->FLD('name', 'varchar(32)', 'caption=Име, mandatory');

        // Текстово описание
        $this->FLD('description', 'text', 'caption=Описание, mandatory,width=100%');
        
        // Процент на авансовото плащане
        $this->FLD('downpayment', 'percent(min=0,max=1)', 'caption=Авансово плащане->Дял,width=7em,hint=Процент,oldFieldName=payAdvanceShare');
        
        // Процент на плащане преди експедиция
        $this->FLD('paymentBeforeShipping', 'percent(min=0,max=1)', 'caption=Плащане преди получаване->Дял,width=7em,hint=Процент,oldFieldName=payBeforeReceiveShare');
        
        // Плащане при получаване
        $this->FLD('paymentOnDelivery', 'percent(min=0,max=1)', 'caption=Плащане преди получаване->Дял,width=7em,hint=Процент,oldFieldName=payOnDeliveryShare');
        
        // Колко дни след фактуриране да е балансовото плащане?
        $this->FLD('timeBalancePayment', 'time(uom=days,suggestions=веднага|15 дни|30 дни|60 дни)', 'caption=Плащане след фактуриране->Срок,width=7em,hint=дни,oldFieldName=payBeforeInvTerm');
        
        $this->setDbUnique('sysId');
        $this->setDbUnique('name');
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
	    	
            $rec = &$form->rec;
	    	
            $total = $rec->downpayment + $rec->paymentBeforeShipping + $rec->paymentOnDelivery;
	    	 
	    	if($total > 1){
	    		$form->setError('downpayment,paymentBeforeShipping,paymentOnDelivery', 'Въведените проценти не бива да надвишават 100%');
	    	}
    	}
    }
    

    /**
     * Дали подадения метод е Наложен платеж (Cash on Delivery)
     * @param mixed $payment - ид или име на метод
     * @return boolean
     */
    public static function isCOD($payment)
    {
    	$where = (is_numeric($payment)) ? $payment : "#name = '{$payment}'";
    	expect($name = static::fetchField($where, 'name'));
    	
    	return ($name == 'COD');
    }
    
    
    /**
     * Връща масив съдържащ плана за плащане
     * @param int $pmId - ид на метод
     * @param double $amount - сума
     * @param invoiceDate - дата на фактуриране (ако няма е датата на продажбата)
     * @return array $res - масив съдържащ информация за плащането
     * 
     * 		['downpayment'] 		      - сума за авансово плащане
     * 		['paymentBeforeShipping']     - сума за плащане преди експедиране
     * 		['paymentOnDelivery']         - сума за плащане при получаване
     * 		['paymentAfterInvoice']       - сума за плащане след фактуриране
     * 		['deadlineForBalancePayment'] - крайна дата за окончателно плащане
     */
    public static function getPaymentPlan($pmId, $amount, $invoiceDate, $verbal = FALSE)
    {
        expect($rec = self::fetch($pmId));
		
        if($rec->downpayment) {
            $res['downpayment'] = $rec->downpayment * $amount;
        }

        if($rec->paymentBeforeShipping) {
            $res['paymentBeforeShipping'] = $rec->paymentBeforeShipping * $amount;
        }

        if($rec->paymentOnDelivery) {
            $res['paymentOnDelivery'] = $rec->paymentOnDelivery * $amount;
        }

        $paymentAfterInvoice = 1 - $rec->paymentOnDelivery - $rec->paymentBeforeShipment - $rec->downpayment;
        
        if($paymentAfterInvoice > 0) {
            $res['paymentAfterInvoice']       = $paymentAfterInvoice * $amount;
            $res['deadlineForBalancePayment'] = dt::addSecs($rec->timeForBalancePayment, $invoiceDate);
        }

        if($verbal) {
        	if(!count($res)) return $res;
        	
        	$Double = cls::get('type_Double');
        	$Double->params['decimals'] = 2;
        	$Date = cls::get('type_Date');
        	
	        foreach($res as $key => &$value){
	        	if($key != 'deadlineForBalancePayment'){
	        		$value = $Double->toVerbal($value);
	        	} else {
	        		$value = $Date->toVerbal($value);
	        	}
	        }
        }
        
        return $res;
    }
    
    
    /**
     * Дали документа е пресрочен
     * @param array $payment - платежния план (@see static::getPaymentPlan)
     * @param double $restAmount - оставаща сума за плащане
     * @param datetime $today - дата
     * @return boolean
     */
    public static function isOverdue($payment, $restAmount, $today = NULL)
    {
    	expect(is_array($payment) && isset($restAmount));
    	if(!$today){
    		$today = dt::verbal2mysql();
    	}
    	
    	// Ако остатъка за плащане е 0 или по-малко
    	if($restAmount <= 0) return FALSE;
    	
    	// Ако текущата дата след крайния срок за плащане, документа е пресрочен
    	return ($today > $payment['deadlineForBalancePayment']);
    }
    
    
    /**
     * Сортиране по name
     */
    static function on_BeforePrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('#name');
    }
    
    
	/**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
    	$file = "cond/csv/PaymentMethods.csv";
    	$fields = array(
            0 => 'sysId',
	    	1 => 'name', 
	    	2 => 'description',
            3 => 'downpayment',
            4 => 'paymentBeforeShipping',
            5 => 'paymentOnDelivery',
            6 => 'daysForBalancePayment');
    	$cntObj = csv_Lib::importOnce($mvc, $file, $fields);
    	$res .= $cntObj->html;
    	
    	return $res;
    }
}