<?php

/**
 * Клас 'bank_PaymentMethods' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    common
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class bank_PaymentMethods extends core_Master
{
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, bank_Wrapper, plg_State,
                     PaymentMethodDetails=bank_PaymentMethodDetails';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id, name, tools=Пулт';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Начини на плащане';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $details = 'bank_PaymentMethodDetails';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canRead = 'admin, common';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin, common';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin, common';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin, common';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Име, mandatory');
        $this->FLD('state', 'enum(draft,closed)', 'caption=Състояние, input=none');
        $this->setDbUnique('name');
    }
    
    
    /**
     * Сортиране по name
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy('#name');
    }
    
    
    /**
     * Записи за инициализиране на таблицата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     */
    function on_AfterSetupMvc($mvc, &$res)
    {
        $data = array(
            array(
                'name' => 'COD',
                'description' => 'Cash'
            ),
            array(
                'name' => '50:50',
                'description' => '50%DP + 50%BS'
            ),
            array(
                'name' => '30:70',
                'description' => '30%DP + 70%BS'
            ),
            array(
                'name' => '20:80',
                'description' => '20%DP + 80%BS'
            ),
            array(
                'name' => '100DP',
                'description' => '100% DP'
            ),
            array(
                'name' => '100BS',
                'description' => '100% BS'
            ),
            array(
                'name' => 'Net3',
                'description' => 'Net 3'
            ),
            array(
                'name' => 'Net7',
                'description' => 'Net 7'
            ),
            array(
                'name' => 'Net10',
                'description' => 'Net 10'
            ),
            array(
                'name' => 'Net15',
                'description' => 'Net 15'
            ),
            array(
                'name' => 'Net21',
                'description' => 'Net 21'
            ),
            array(
                'name' => '1 m',
                'description' => 'Net 30'
            ),
            array(
                'name' => '1-10Net30',
                'description' => '1% 10 Net 30'
            ),
            array(
                'name' => '1,5-10Net30',
                'description' => '1,5% 10 Net 30'
            ),
            array(
                'name' => 'Net35',
                'description' => 'Net 35'
            ),
            array(
                'name' => 'Net40',
                'description' => 'Net 40'
            ),
            array(
                'name' => '2 m',
                'description' => 'Net 60'
            ),            
            array(
                'name' => '2-10Net60',
                'description' => '2% 10 Net 60'
            ),            
            array(
                'name' => 'Net70',
                'description' => 'Net 70'
            ),            
            array(
                'name' => '3 m',
                'description' => 'Net 90'
            ),            
            array(
                'name' => '3-10Net90',
                'description' => '3% 10 Net 90'
            ),            
            array(
                'name' => '5 MFI',
                'description' => '5 MFI'
            ),            
            array(
                'name' => '10 MFI',
                'description' => '10 MFI'
            ),
            array(
                'name' => 'EMFI',
                'description' => 'EMFI'
            ),                        
            array(
                'name' => '1-10EMFI',
                'description' => '1% 10, EMFI'
            ),            
            array(
                'name' => 'ESMFI',
                'description' => 'ESMFI'
            ),
            array(
                'name' => '2-10ESMFI',
                'description' => '2% 10, ESMFI'
            ),
            array(
                'name' => 'other',
                'description' => 'As agreed'
            ),
            array(
                'name' => 'L/C',
                'description' => 'L/C'
            ),
            array(
                'name' => 'L/C 30 d',
                'description' => 'L/C 30 days'
            ),
            array(
                'name' => 'L/C 45 d',
                'description' => 'L/C 45 days'
            ),                                                
            array(
                'name' => 'L/C 60 d',
                'description' => 'L/C 60 days'
            )            
        );
        
        if(!$mvc->fetch("1=1")) {
            
            $nAffected = 0;
            
            foreach ($data as $rec) {
                $rec = (object)$rec;
                
                if (!$this->fetch("#name='{$rec->name}'")) {
                    if ($this->save($rec)) {
                        $nAffected++;
                    }
                }
            }
        }
        
        if ($nAffected) {
            $res .= "<li>Добавени са {$nAffected} записа.</li>";
        }
    }
    
    
    /**
     * Подготвя шаблона за единичния изглед
     *
     * @param stdClass $data
     */
    function renderSingleLayout_($data)
    {
        if( count($this->details) ) {
            foreach($this->details as $var => $className) {
                $detailsTpl .= "[#Detail{$var}#]";
            }
        }
        
        return new ET("[#SingleToolbar#]<h2>[#SingleTitle#]</h2>{$detailsTpl}");
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
            	case beforeOrderDate:
            	case afterOrderDate:
		            $baseDate = $orderDate;
		            $payment[$j]['baseDate'] = $baseDate;
		            break;            		
                case beforeTransferDate:
                case afterTransferDate:
                    $baseDate = $transferDate;
                    $payment[$j]['baseDate'] = $baseDate;
                    break;		            
            }
            
            // days
            $payment[$j]['days'] = $recPaymentMethodDetails->days;

            // BEGIN 'daysVerbal' and 'baseDatePaymentTerm'
            switch ($recPaymentMethodDetails->round) {
                case 'no':
                	// Ако 'base' е before (преди), то addDays става отрицателно
                	switch($recPaymentMethodDetails->base) {
                        case 'beforeOrderDate':
                        case 'beforeTransferDate':
                        	$addDays = $recPaymentMethodDetails->days*(-1);
                        	break;

                        case 'afterOrderDate':
                        case 'afterTransferDate':
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
                    		case 'beforeOrderDate':
                    		case 'beforeTransferDate':
	                            $payment[$j]['daysVerbal'] = "До {$recPaymentMethodDetails->days} дена преди \"{$payment[$j]['baseVerbal']}\"";
	                            $payment[$j]['baseDatePaymentTerm'] = "До {$baseDatePaymentTerm}\"";
	                            break;

                    		case 'afterOrderDate':
                    		case 'afterTransferDate':
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
                
                case 'eom':
                    $lastDayOfMonth = date('t', strtotime($baseDatePaymentTerm));
                    $baseDatePaymentTerm = $lastDayOfMonth . "-".substr($baseDate, 3, 7);
                    
                    $payment[$j]['daysVerbal'] = "До края на месеца";
                    $payment[$j]['baseDatePaymentTerm'] = "До {$baseDatePaymentTerm}";
                    break;
            }
            // END 'daysVerbal' and 'baseDatePaymentTerm'            
            
            // rate
            $payment[$j]['rate'] = $recPaymentMethodDetails->rate. " %";
            
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
        $orderDate    = "01-09-2011";
        $transferDate = "20-09-2011";
        $paymentMethodId = 1;
        
        $this->getPaymentDatesAndRate($paymentMethodId, $orderDate, $transferDate);
    }
    
    
    /**
     * Метода се извиква автматично след промяна на детайла
     * Ако сбора от процентите на плащанията е 100, то state на метода става 'closed' 
     * 
     * @param $mvc core_Mvc
     * @param $res stdClass
     * @param $detailMvc stdClass
     * @param $masterId int
     * @param $action string
     * @param $detailIds array
     */
    function on_AfterDetailChanged($mvc, &$res, $detailMvc, $masterId, $action = 'edit', $detailIds = array())
    {
        $query = $detailMvc->getQuery();
        $where = "#paymentMethodId = {$masterId}";
        
        $totalRate = 0;
        while($recPaymentDetaisl = $query->fetch($where)) {
            $totalRate += $recPaymentDetaisl->rate;
        }
        // BEGIN смяна на 'state' на метода в зависимост сбора от вноските дали е 100%
        $recPaymentMethods = new stdClass;
        $recPaymentMethods = $mvc->fetch($masterId);
        
        if ($totalRate == 100) {
            $recPaymentMethods->state = 'closed';
        } else {
            $recPaymentMethods->state = 'draft';
        }
        
        $mvc->save($recPaymentMethods);
        // END смяна на 'state' на метода в зависимост сбора от вноските дали е 100%     
    }
    
    
    /**
     * Слага state = draft по default при нов запис
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {
    	if (!$data->form->rec->id) {
            $data->form->setDefault('state', 'draft');
        }
    }    
}