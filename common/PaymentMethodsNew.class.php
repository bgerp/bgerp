<?php


/**
 * Клас 'common_PaymentMethodsNew' -
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
class common_PaymentMethodsNew extends core_Master
{
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, common_Wrapper, plg_State,
                          PaymentMethodDetails=common_PaymentMethodDetails';
    
    
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
    var $details = 'common_PaymentMethodDetails';
    
    
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
     * Преди извличане на записите от БД
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
                'name' => 'в брой',
                'description' => 'в брой'
            ),
            array(
                'name' => 'по банков път',
                'description' => 'в брой'
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
    function getPaymentDatesAndRate($paymentMethodId, $orderDate = NULL, $transferDate = NULL)
    {
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
            
            // baseDate
            $baseDate = $$payment[$j]['base'];
            $payment[$j]['baseDate'] = $baseDate;
            
            // days
            $payment[$j]['days'] = $recPaymentMethodDetails->days;
            
            switch ($recPaymentMethodDetails->round) {
                case 'no':
                    // BEGIN 'daysVerbal' and 'baseDatePaymentTerm'
                    $baseDatePaymentTerm = dt::addDays($recPaymentMethodDetails->days, $baseDate);
                    $baseDatePaymentTerm = strtotime($baseDatePaymentTerm);
                    $baseDatePaymentTerm = date('d-m-Y', $baseDatePaymentTerm);
                    
                    if ($recPaymentMethodDetails->days > 0) {
                        $payment[$j]['daysVerbal'] = "До {$recPaymentMethodDetails->days} дена след \"{$payment[$j]['baseVerbal']}\"";
                        $payment[$j]['baseDatePaymentTerm'] = "От {$baseDate} до {$baseDatePaymentTerm}\"";
                    }
                    
                    if ($recPaymentMethodDetails->days == 0) {
                        $payment[$j]['daysVerbal'] = "В деня на \"{$payment[$j]['baseVerbal']}\"";
                        $payment[$j]['baseDatePaymentTerm'] = "На {$baseDate}\"";
                    }
                    
                    if ($recPaymentMethodDetails->days < 0) {
                        $payment[$j]['days'] = "До " . abs($recPaymentMethodDetails->days) . " дена преди \"{$payment[$j]['baseVerbal']}\"";
                        $payment[$j]['baseDatePaymentTerm'] = "От {$baseDatePaymentTerm} до {$baseDate}\"";
                    }
                    // END 'daysVerbal' and 'baseDatePaymentTerm'
                    break;
                
                case 'eom':
                    // BEGIN 'daysVerbal' and 'baseDatePaymentTerm'
                    $lastDayOfMonth = date('t', strtotime($baseDatePaymentTerm));
                    $baseDatePaymentTerm = $lastDayOfMonth . "-".substr($baseDate, 3, 7);
                    
                    $payment[$j]['daysVerbal'] = NULL;
                    $payment[$j]['baseDatePaymentTerm'] = "От {$baseDate} до {$baseDatePaymentTerm}";
                    // END 'daysVerbal' and 'baseDatePaymentTerm'                  
                    break;
            }
            
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
        $orderDate = "21-07-2011";
        $transferDate = "01-08-2011";
        $paymentMethodId = 7;
        
        $this->getPaymentDatesAndRate($paymentMethodId, $orderDate, $transferDate);
    }
    
    
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
    
    
    function on_BeforeSave($mvc, &$id, $rec)
    {
        if (!$rec->state) $rec->state = 'draft';
    }    
}