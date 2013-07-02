<?php



/**
 * Клас 'salecond_PaymentMethods' -
 *
 *
 * @category  bgerp
 * @package   salecond
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class salecond_PaymentMethods extends core_Master
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, salecond_Wrapper, plg_State';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, name';
    
    
    /**
     * Заглавие
     */
    var $title = 'Начини на плащане';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'bank_PaymentMethods';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, salecond';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin, salecond';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin, salecond';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin, salecond';
    
    
    /**
     * Шаблон за единичен изглед
     */
    var $singleLayoutFile = "salecond/tpl/SinglePaymentMethod.shtml";
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'name';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Име, mandatory');
        $this->FLD('description', 'varchar', 'caption=Описание, mandatory');
        
        $this->FLD('payAdvanceShare', 'percent(min=0,max=1)', 'caption=Авансово плащане->Дял,width=6em');
        $this->FLD('payAdvanceTerm', 'time(uom=days,suggestions=веднага|3 дни|5 дни|7 дни)', 'caption=Авансово плащане->Срок');
        
        $this->FLD('payBeforeReceiveShare', 'percent(min=0,max=1)', 'caption=Плащане преди получаване->Дял,width=6em');
        $this->FLD('payBeforeReceiveTerm', 'time(uom=days,suggestions=веднага|3 дни|5 дни|10 дни|15 дни|30 дни|45 дни)', 'caption=Плащане преди получаване->Срок');
        
        $this->FLD('payBeforeInvShare', 'percent(min=0,max=1)', 'caption=Плащане след фактуриране->Дял,width=6em');
        $this->FLD('payBeforeInvTerm', 'time(uom=days,suggestions=веднага|15 дни|30 дни|60 дни)', 'caption=Плащане след фактуриране->Срок');
        
        $this->FLD('state', 'enum(draft,closed)', 'caption=Състояние, input=none');
        $this->setDbUnique('name');
    }
    
    
    /**
     * Начин на плащане по подразбиране според клиента
     * 
     * @see doc_ContragentDataIntf
     * @param stdClass $contragentInfo
     * @return int key(mvc=salecond_PaymentMethods) 
     */
    public static function getDefault($contragentInfo)
    {
        // @TODO
        return static::fetchField("#name = 'COD'", 'id'); // за тест
    }
    
    
    /**
     * Сортиране по name
     */
    static function on_BeforePrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('#name');
    }
    
    
	/**
     * Зареждане на началните празници в базата данни
     */
    static function loadData()
    {
    	$csvFile = __DIR__ . "/csv/PaymentMethods.csv";
        $created = $updated = 0;
        if (($handle = @fopen($csvFile, "r")) !== FALSE) {
         	while (($csvRow = fgetcsv($handle, 2000, ",", '"', '\\')) !== FALSE) {
                $rec = new stdClass();
              	$rec->name= $csvRow[0];
               	$rec->description= $csvRow[1];
               	if($rec->id = static::fetchField(array("#name = '[#1#]'", $rec->name), 'id')){
               		$updated++;
               	} else {
               		$created++;
               	}
                static::save($rec);
            }
            
            fclose($handle);
            
            $res .= "<li style='color:green;'>Създадени са {$created} начина за плащане, обновени са {$updated}</li>";
        } else {
            $res = "<li style='color:red'>Не може да бъде отворен файла '{$csvFile}'";
        }
        
        return $res;
    }
    
    
    /**
     * Връща за дадена сделка конкретните дати и проценти за плащания по входни данни
     *
     * @param int $paymentMethodId
     * @param string $orderDate
     * @param string $transferDate
     * @retutn array $paymentDatesAndRates
     */
    function getPaymentDatesAndRate($paymentMethodId, $orderDate, $transferDate)
    {
        expect(is_int($paymentMethodId));
        expect(is_string($orderDate));
        expect(is_string($transferDate));
        
        // Вземаме детайлите (вноските) за конкретния метод
        $queryPaymentMethodDetails = $this->PaymentMethodDetails->getQuery();
        $where = "#paymentMethodId = {$paymentMethodId}";
        
        // брояч на вноските
        $j = 0;
        
        // за всяка вноска
        while($recPaymentMethodDetails = $queryPaymentMethodDetails->fetch($where)) {
            // base
            $payment[$j]['base'] = $recPaymentMethodDetails->base;
            
            // baseVerbal
            $payment[$j]['baseVerbal'] = $this->PaymentMethodDetails->getVerbal($recPaymentMethodDetails, 'base');
            
            // prepare $baseDate 
            // за beforeOrderDate и afterOrderDate - $orderDate; 
            // за beforeTransferDate и afterTransferDate - $transferDate
            switch ($recPaymentMethodDetails->base) {
                case beforeOrderDate :
                case afterOrderDate :
                    $baseDate = $orderDate;
                    $payment[$j]['baseDate'] = $baseDate;
                    break;
                case beforeTransferDate :
                case afterTransferDate :
                    $baseDate = $transferDate;
                    $payment[$j]['baseDate'] = $baseDate;
                    break;
            }
            
            // days
            $payment[$j]['days'] = $recPaymentMethodDetails->days;
            
            // BEGIN 'daysVerbal' and 'baseDatePaymentTerm'
            switch ($recPaymentMethodDetails->round) {
                case 'no' :
                    // Ако 'base' е before (преди), то addDays става отрицателно
                    switch($recPaymentMethodDetails->base) {
                        case 'beforeOrderDate' :
                        case 'beforeTransferDate' :
                            $addDays = $recPaymentMethodDetails->days * (-1);
                            break;
                        
                        case 'afterOrderDate' :
                        case 'afterTransferDate' :
                            $addDays = $recPaymentMethodDetails->days;
                            break;
                    }
                    
                    // ENDOF Ако 'base' е before (преди), то addDays става отрицателно
                    
                    // Изчислява дататa във формат 'd-m-Y' 
                    $baseDatePaymentTerm = dt::addDays($addDays, $baseDate);
                    $baseDatePaymentTerm = strtotime($baseDatePaymentTerm);
                    $baseDatePaymentTerm = date('d-m-Y', $baseDatePaymentTerm);
                    
                    // Ако дните са положителни
                    if ($recPaymentMethodDetails->days > 0) {
                        switch ($recPaymentMethodDetails->base) {
                            case 'beforeOrderDate' :
                            case 'beforeTransferDate' :
                                $payment[$j]['daysVerbal'] = "До {$recPaymentMethodDetails->days} дена преди \"{$payment[$j]['baseVerbal']}\"";
                                $payment[$j]['baseDatePaymentTerm'] = "До {$baseDatePaymentTerm}\"";
                                break;
                            
                            case 'afterOrderDate' :
                            case 'afterTransferDate' :
                                $payment[$j]['daysVerbal'] = "До {$recPaymentMethodDetails->days} дена след \"{$payment[$j]['baseVerbal']}\"";
                                $payment[$j]['baseDatePaymentTerm'] = "До {$baseDatePaymentTerm}\"";
                                break;
                        }
                    }
                    
                    // ENDOF Ако дните са положителни
                    
                    // Ако дните са нула
                    if ($recPaymentMethodDetails->days == 0) {
                        $payment[$j]['daysVerbal'] = "В деня на \"{$payment[$j]['baseVerbal']}\"";
                        $payment[$j]['baseDatePaymentTerm'] = "На {$baseDate}\"";
                    }
                    break;
                
                case 'eom' :
                    $lastDayOfMonth = date('t', strtotime($baseDatePaymentTerm));
                    $baseDatePaymentTerm = $lastDayOfMonth . "-" . substr($baseDate, 3, 7);
                    
                    $payment[$j]['daysVerbal'] = "До края на месеца";
                    $payment[$j]['baseDatePaymentTerm'] = "До {$baseDatePaymentTerm}";
                    break;
            }
            
            // END 'daysVerbal' and 'baseDatePaymentTerm'            
            
            // rate
            $payment[$j]['rate'] = $recPaymentMethodDetails->rate . " %";
            
            $j++;
        }
        unset($j);
        
        bp($payment);
    }
    
    
    /**
     * Action-а изпълнява метода getPaymentDatesAndRate() за тест цели
     */
    function act_GetP()
    {
        // Dummy data for test
        $orderDate = "01-09-2011";
        $transferDate = "20-09-2011";
        $paymentMethodId = 1;
        
        $this->getPaymentDatesAndRate($paymentMethodId, $orderDate, $transferDate);
    }
}