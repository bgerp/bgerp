<?php



/**
 * Мениджър на заплати
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Заплати
 */
class trz_SalaryPayroll extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Сума';
    
    
    /**
     * Заглавието в единично число
     */
    public $singleTitle = 'Сума';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, plg_Rejected,  plg_SaveAndNew, 
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
    public $canAdd = 'ceo,trz';
    
    
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
    public $canDelete = 'ceo,trz';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,periodId,personId, rule, sum';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'id';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('periodId',    'date', 'caption=Период,width=100%');
        $this->FLD('personId',    'key(mvc=crm_Persons,select=name,group=employees)', 'caption=Служител,mandatory,width=100%');
        $this->FLD('rule',    'key(mvc=trz_SalaryRules, select=name)', 'caption=Правило,width=100%');
        $this->FLD('sum',    'double', 'caption=Стойност,width=100%');

        $this->setDbUnique('personId,rule');
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->fields['personId']->type->params['allowEmpty'] = 'allowEmpty';
        unset($data->listFilter->fields['personId']->type->params['mandatory']);
        $data->listFilter->fields['personId']->mandatory = '';
        
        $data->listFilter->fields['rule']->type->params['allowEmpty'] = 'allowEmpty';

        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    
        // Показваме само това поле. Иначе и другите полета
        // на модела ще се появят
        $data->listFilter->showFields = 'periodId, personId,rule';
    
        $data->listFilter->input('periodId, personId,rule', 'silent');
    
        $toDate = '';
        if($data->listFilter->rec->periodId) {
            $toDate = dt::getLastDayOfMonth($data->listFilter->rec->periodId);
            $data->query->where("#periodId = '{$toDate}'");
        }
    
        if($data->listFilter->rec->personId) {
            $data->query->where("#personId = '{$data->listFilter->rec->personId}'");
        }
        
        if($data->listFilter->rec->rule) {
            $data->query->where("#rule = '{$data->listFilter->rec->rule}'");
        }
    }
    
    
    /**
     * 
     * @param unknown $rec
     */
    static public function calculateSalary ()
    {
        // до края на текущия месец
        $to = dt::getLastDayOfMonth();
        
        $fromM =  date('m', time());
        $fromY =  date('Y', time());
        // от първия ден от текущия месец
        $from = dt::verbal2mysql(dt::timestamp2Mysql(strtotime("{$fromY}-$fromM-01")), FALSE);
   
        $queryRull = trz_SalaryRules::getQuery();
        
        // помощни масиви
        $cnt = array();
        $sum = array();
        
        // обикаляме по всички правила
        while($recRull = $queryRull->fetch()){

            $queryIndicator = trz_SalaryIndicators::getQuery();
            // търсим избрания индикатор за конкретните дати
            $queryIndicator->where("#indicator = '{$recRull->indicators}' AND (#date >= '{$from}' && #date <= '{$to}')");
            
            // обикаляме по таблицата от КПЕ
            while($recIndicator = $queryIndicator->fetch()){
                // за всеки служител|правило събираме индикатор->стойност
                $sum[$recIndicator->personId][$recRull->indicators] += $recIndicator->value;
                
                // и броим колко записа има за него
                $cnt[$recIndicator->personId][$recRull->indicators]++;
            }

            if (is_array($sum) && is_array($cnt)){
                //обикаляме по  помощния масива
                foreach($sum as $person => $indicatorArr) { 
                    foreach($indicatorArr as $indicator => $indicatorSum) {
                        // ще генерираме запис в таблицата
                        $rec = new stdClass();
                          
                        switch ($recRull->function) { 
                            // ако функцията е КОНСТАНТА
                            case 'const' :

                                $rec->periodId = $to;
                                $rec->personId = $person;
                                $rec->rule = $recRull->id;
                                $rec->sum = $recRull->factor;
                                    
                            break;

                            // ако функцията е СРЕДНО АРИТМЕТИЧНО
                            case 'average' :

                                $rec->periodId = $to;
                                $rec->personId = $person;
                                $rec->rule = $recRull->id;
                                
                                if($cnt[$person] !== 0) {
                                    $rec->sum  = $recRull->factor * ($indicatorSum / $cnt[$person][$indicator]);
                                } else {
                                    $rec->sum = 0;
                                }

                                break;

                            // ако функцията е СУМАРНО
                            case 'summary' :

                                $rec->periodId = $to;
                                $rec->personId = $person;
                                $rec->rule  = $recRull->id;
                                $rec->sum  = $recRull->factor * $indicatorSum;

                                break;
                        }
                            
                        $self = cls::get(get_called_class());
                	    $exRec = new stdClass();
                	    	
                	    // Ако имаме уникален запис го записваме
                	    // в противен слувай го ъпдейтваме
                       	if($self->isUnique($rec, $fields, $exRec)){
                    		self::save($rec);
                    	} else { 
                            $rec->id = $exRec->id;
                            self::save($rec);
                        }
                    }
                }
            }
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
        $Double = cls::get('type_Double', array('params' => array('decimals' => 2)));

        if ($rec->personId) {
            // Ако имаме права да видим визитката
            if(crm_Persons::haveRightFor('single', $rec->personId)){
                $name = crm_Persons::fetchField("#id = '{$rec->personId}'", 'name');
                $row->personId = ht::createLink($name, array ('crm_Persons', 'single', 'id' => $rec->personId), NULL, 'ef_icon = img/16/vcard.png');
            }
        }
        
        if ($rec->periodId) {
            $row->periodId = dt::mysql2verbal($rec->periodId, "F Y", NULL, FALSE);
        }

        $row->sum = $Double->toVerbal($rec->sum);
    }

    
    /**
     * Изпращане на нотификации за започването на задачите
     */
    function cron_CalcSalaryPay()
    {
        //$this->calculateSalary();
    }
}