<?php



/**
 * Мениджър на заплати
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Заплати
 */
class trz_PayrollDetails extends core_Detail
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Ведомост';
    
    
   /**
    * Име на поле от модела, външен ключ към мастър записа
    */
    public $masterKey = 'payrollId';
    
    
    /**
     * Заглавието в единично число
     */
    public $singleTitle = 'Ведомост';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Rejected,  plg_SaveAndNew, 
                    trz_Wrapper';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,trz';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,trz';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo,trz';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,trz';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,trz';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,personId,salary,bonus,sickday,order,trip,fines,amount';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'id';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
         // Ключ към мастъра
         $this->FLD('payrollId', 'key(mvc=trz_Payroll, select=periodId)', 'caption=Период,column=none,input=hidden,silent');
    	 $this->FLD('periodId',    'key(mvc=acc_Periods, select=title, where=#state !\\= \\\'closed\\\', allowEmpty=true)', 'caption=Период,width=100%');
    	 $this->FLD('personId',    'key(mvc=crm_Persons,select=name,group=employees)', 'caption=Лице,width=100%');
    	 $this->FLD('salary',    'double', 'caption=Заработка,width=100%');
    	 $this->FLD('bonus',    'double', 'caption=Бонус,width=100%');
    	 $this->FLD('sickday',    'double', 'caption=Болничен,width=100%');
    	 $this->FLD('order',    'double', 'caption=Отпуска,width=100%');
    	 $this->FLD('trip',    'double', 'caption=Командировка,width=100%');
    	 $this->FLD('fines',    'double', 'caption=Удръжки,width=100%');
    	 $this->FLD('amount',    'double', 'caption=Общо,mandatory,width=100%');
    	 
    	 $this->setDbUnique('periodId,personId');
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    /*public static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
        
        $masterRec = trz_Payroll::fetch($rec->payrollId, 'periodId');
  
        $mvc->fillData($masterRec);
    }*/
    
    
    static public function act_FillData()
    {

        $masterId = Request::get('payrollId');
        $masterRec = trz_Payroll::fetch($masterId, 'periodId');
        
        self::fillData($masterRec);

        $retUrl = array('trz_Payroll', 'single', $masterId);

        return new Redirect($retUrl);
    }
    
    
    static public function fillData($masterRec)
    {
        
        $period = acc_Periods::fetch($masterRec->periodId);
        
        $qSalary = trz_SalaryPayroll::getQuery();
        $qSalary->where("#periodId >= '{$period->start}' AND #periodId <= '{$period->end}'");

        $recs =  array();
    
        while($recSalary = $qSalary->fetch()){
            $debug['salary'][] = $recSalary;
            $index = $masterRec->periodId . "|" . $recSalary->personId;
            
            if(!array_key_exists($index, $recs)){
                $recs[$index] =
                (object) array ('payrollId' => $masterRec->id,
                                'periodId' => $masterRec->periodId,
                                'personId' => $recSalary->personId,
                                'salary' => $recSalary->sum,
                    );
            } else {
             
                $obj = &$recs[$index];
                $obj->payrollId = $masterRec->id;
                $obj->periodId = $masterRec->periodId;
                $obj->personId = $recSalary->personId;
                $obj->salary += $recSalary->sum;
            }
        }

        $qBonus= trz_Bonuses::getQuery();
        $qBonus->where("#periodId >= '{$period->start}' AND #periodId <= '{$period->end}'");
        
        while($recBonus = $qBonus->fetch()){
            $debug['bonus'][] = $recBonus;
            $index = $masterRec->periodId . "|" . $recBonus->personId;
            
            if(!array_key_exists($index, $recs)){
                $recs[$index] =
                (object) array ('payrollId' => $masterRec->id,
                                'periodId' => $masterRec->periodId,
                                'personId' => $recBonus->personId,
                                'bonus' => $recBonus->sum,
                        );
            }
            else {
                 
                $obj = &$recs[$index];
                $obj->payrollId = $masterRec->id;
                $obj->periodId = $masterRec->periodId;
                $obj->personId = $recBonus->personId;
                $obj->bonus += $recBonus->sum;
            }
        }
        
        
        $qSickday = trz_Sickdays::getQuery();
        $qSickday->where("#startDate >= '{$period->start}' AND #toDate  <= '{$period->end}'");
        
        while($recSickday = $qSickday->fetch()){
            $debug['sickday'][] = $recSickday;
            $index = $masterRec->periodId . "|" . $recSickday->personId;
        
            if(!array_key_exists($index, $recs)){
                $recs[$index] =
                (object) array ('payrollId' => $masterRec->id,
                    'periodId' => $masterRec->periodId,
                    'personId' => $recSickday->personId,
                    'sickday' => $recSickday->paidByEmployer,
                );
            }
            else {
                 
                $obj = &$recs[$index];
                $obj->payrollId = $masterRec->id;
                $obj->periodId = $masterRec->periodId;
                $obj->personId = $recSickday->personId;
                $obj->sickday += $recSickday->paidByEmployer;
            }
        }
        
        $qTrip = trz_Trips::getQuery();
        $qTrip->where("#startDate >= '{$period->start}' AND #toDate <= '{$period->end}'"); 
        
        while($recTrip = $qTrip->fetch()){
            $debug['trip'][] = $recTrip;
            $index = $masterRec->periodId . "|" . $recTrip->personId;
        
            if(!array_key_exists($index, $recs)){
                $recs[$index] =
                (object) array ('payrollId' => $masterRec->id,
                    'periodId' => $masterRec->periodId,
                    'personId' => $recTrip->personId,
                    'trip' => $recTrip->amountRoad + $recTrip->amountDaily + $recTrip->amountHouse,
                );
            }
            else {
                 
                $obj = &$recs[$index];
                $obj->payrollId = $masterRec->id;
                $obj->periodId = $masterRec->periodId;
                $obj->personId = $recTrip->personId;
                $obj->trip += $recTrip->amountRoad + $recTrip->amountDaily + $recTrip->amountHouse;
            }
        }
        
        $qFines = trz_Fines::getQuery();
        $qFines->where("#periodId >= '{$period->start}' AND #periodId <= '{$period->end}'");
        
        while($recFines = $qFines->fetch()){
            $debug['fines'][] = $recFines;
            
            $index = $masterRec->periodId . "|" . $recFines->personId;
            
            if(!array_key_exists($index, $recs)){
                $recs[$index] =
                (object) array ('payrollId' => $masterRec->id,
                    'periodId' => $masterRec->periodId,
                    'personId' => $recFines->personId,
                    'fines' => $recFines->sum,
                );
            }
            else {
                 
                $obj = &$recs[$index];
                $obj->payrollId = $masterRec->id;
                $obj->periodId = $masterRec->periodId;
                $obj->personId = $recFines->personId;
                $obj->fines += $recFines->sum;
            }
        } 

        $exRec = new stdClass();
        $self = cls::get(get_called_class());
        foreach($recs as $rec){
            $rec->amount = $rec->salary + $rec->sickday + $rec->order + $rec->trip + $rec->bonus - $rec->fines;
            // Ако имаме уникален запис го записваме
            // в противен слувай го ъпдейтваме
            $fields = array();
            if($self->isUnique($rec, $fields, $exRec)) {
                self::save($rec);
            } else { 
                $rec->id = $exRec->id;
                self::save($rec);
            };
        }
    }
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        
        foreach(array('salary', 'bonus', 'sickday', 'order', 'trip','fines', 'amount') as $fld){
            $row->{$fld} = $Double->toVerbal($rec->{$fld});
        }
        
        if(isset($rec->personId)) {
            // Ако имаме права да видим визитката
            if(crm_Persons::haveRightFor('single', $rec->personId)){
                $name = crm_Persons::fetchField("#id = '{$rec->personId}'", 'name');
                $row->personId = ht::createLink($name, array ('crm_Persons', 'single', 'id' => $rec->personId), NULL, 'ef_icon = img/16/vcard.png');
            }
        }

    }
}