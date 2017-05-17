<?php



/**
 * Мениджър на заплати
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Заплати
 */
class hr_Indicators extends core_Manager
{
    /**
     * Старо име на класа
     */
    public $oldClassname = 'trz_SalaryIndicators';
    

    /**
     * Заглавие
     */
    public $title = 'Показатели';
    
    
    /**
     * Заглавие в единично число
     */
    public $singleTitle = 'Показател';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_SaveAndNew, plg_RowTools2, hr_Wrapper';
                   

    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,hr';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'admin,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo,hr';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,hr';


    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,hr';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,hr';

    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'date, docId=Документ, personId, indicatorId, value';
    
     
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('date',    'date', 'caption=Дата,mandatory');

        $this->FLD('docId',    'int', 'caption=Документ->№,mandatory');
        $this->FLD('docClass', 'int', 'caption=Документ->Клас,silent,mandatory');

        $this->FLD('personId',    'key(mvc=crm_Persons,select=name,group=employees)', 'caption=Служител,mandatory');

        $this->FLD('indicatorId',    'int', 'caption=Индикатор,smartCenter,mandatory');
        $this->FLD('sourceClass',    'class(interface=hr_IndicatorsSourceIntf,select=title)', 'caption=Индикатор->Източник,smartCenter,mandatory');
        $this->FLD('value',    'double(smartRound,decimals=2)', 'caption=Стойност,mandatory');

        $this->setDbUnique('date,docId,docClass,indicatorId,sourceClass,personId');
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
        // Подготвяме масив с имената на индикаторите
        static $names;
        if(!$names) {
            $names = $mvc->getIndicatorNames();
        }

        // Ако имаме права да видим визитката
        if(crm_Persons::haveRightFor('single', $rec->personId)){
            $name = crm_Persons::fetchField("#id = '{$rec->personId}'", 'name');
            $row->personId = ht::createLink($name, array ('crm_Persons', 'single', 'id' => $rec->personId), NULL, 'ef_icon = img/16/vcard.png');
        }
        
        $dMvc = cls::get($rec->docClass); 
        $row->docId = $dMvc->getLinkForObject($rec->docId);

        $sMvc = cls::get($rec->sourceClass);
        $row->indicatorId = $names[$rec->sourceClass][$rec->indicatorId];
    }
 
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListFilter1($mvc, $data)
    {
        
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('from', 'date', 'caption=Дата->От,input,silent, width = 150px');
        $data->listFilter->FNC('to', 'date', 'caption=Дата->До,input,silent, width = 150px');
        $data->listFilter->FNC('person', 'key(mvc=crm_Persons,select=name,group=employees, allowEmpty=true)', 'caption=Служител,input,silent, width = 150px');

        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->listFilter->showFields = 'from, to, person, indicators';
        $data->listFilter->input('from, to, person', 'silent');
        
        $from = $data->listFilter->rec->from;
        $to = $data->listFilter->rec->to;
        $person = $data->listFilter->rec->person;
        $indicators = $data->listFilter->rec->indicators;
        $group = $data->listFilter->rec->group;

        if($from && $to){
            if($from > $to){
                $newFrom = $from;
                $from = $to;
                $to = $newFrom;
            }
            $data->query->where("#date >= '{$from}' AND #date <= '{$to}'");
        }
        
        if($from){
            $data->query->where("#date >= '{$from}'");
            
        }
        
        if($to){
            $data->query->where("#date <= '{$to}'");
        }
        
        if($person){
            $data->query->where("#personId = '{$person}'");
        }
        
    }
    
    
    /**
     * Изпращане на данните към показателите
     */
    public static function cron_Update()
    { 
        $timeline = dt::addSecs(-(hr_Setup::INDICATORS_UPDATE_PERIOD + 10000) * 60);
       
        $periods = self::saveIndicators($timeline);
        foreach($periods as $id => $rec) {
            self::calcPeriod($rec);
        }
 
    }
    
     
    /**
     * Събиране на информация от всички класове
     * имащи интерфейс hr_IndicatorsSourceIntf
     * 
     * @param date $date
     * @return array $indicators
     */
    public static function saveIndicators($timeline)
    {  
        // Записите за кои документи, трябва да почистим (id-та в ключовете), 
        // оставяйки определени записи (id-та в масива - стойност)
        $forClean = array();
        
        // Масив със записи на счетоводни периоди, които връщаме
        $periods = array();
        
        // Намираме всички класове съдържащи интерфейса
        $docArr = core_Classes::getOptionsByInterface('hr_IndicatorsSourceIntf');
        
        // Ако нямаме източници - нищо не правим
        if(!is_array($docArr) || !count($docArr)) return;

        // Зареждаме всеки един такъв клас
        foreach ($docArr as $class){
            
            $sMvc = cls::get($class);
            
            // Взимаме връщания масив от интерфейсния метод
            $data = $sMvc->getIndicatorValues($timeline);
           
            if (is_array($data) && count($data)) {
           
                // Даваме време
                core_App::setTimeLimit(count($data) + 10);

                // По id-то на служителя, намираме от договора му
                // в кой отдел и на каква позиция работи
                foreach($data as $id => $rec){
                    
                    $key = $rec->docClass . '::' . $rec->docId;
                    
                    if(!isset($forClean[$key])) {
                        $forClean[$key] = array();
                    }

                    $periodRec = acc_Periods::fetchByDate($rec->date);
                 
                    // Запомняме за кой период е документа
                    $periods[$periodRec->id] = $periodRec;
                    
                    // Оттеглените източници ги записваме само за почистване
                    if($rec->state == 'rejected') continue;
                    
                    $rec->sourceClass = core_Classes::getId($class);

                    $exRec = self::fetch(array("#docClass = {$rec->docClass} AND #docId = {$rec->docId} 
                                                AND #personId = {$rec->personId} 
                                                AND #indicatorId = '{$rec->indicatorId}' AND #sourceClass = {$rec->sourceClass}
                                                AND #date = '{$rec->date}'"));
 
                    if($exRec) {
                        $rec->id = $exRec->id;
                        $forClean[$key][$rec->id] = $rec->id;
                        if($rec->value == $exRec->value) {
                            // Ако съществува идентичен стар запис - прескачаме
                            continue;
                        }
                    }
           
                    // Ако имаме уникален запис го записваме
                    self::save($rec);
                    $forClean[$key][$rec->id] = $rec->id;
                }
            }
        }
        
        // Почистване на непотвърдените записи
        foreach($forClean as $doc => $ids) { 
            list($docClass, $docId) = explode('::', $doc); 
            $query = self::getQuery();
            $query->where("#docClass = {$docClass} AND #docId = {$docId}");
            if(count($ids)) {
                $query->where("#id NOT IN (" . implode(',', $ids) . ")");
            }
            $query->delete();
        }

        return $periods;
    }
    
    /**
     * Калкулира заплащането на всички, които имат трудов договор за посочения период
     */
    static function calcPeriod($pRec)
    { 
        // Намираме последните, активни договори за назначения, които се засичат с периода
        $ecQuery = hr_EmployeeContracts::getQuery();
        $ecQuery->where("#state = 'active' OR #state = 'closed'");
        $ecQuery->where("#startFrom <= '{$pRec->end}'");
        $ecQuery->where("(#endOn IS NULL) OR (#endOn >= '{$pRec->start}')");
        $ecQuery->orderBy("#dateId", 'DESC');
        
        $ecArr = array();

        while($ecRec = $ecQuery->fetch()) {
            if(!isset($ecArr[$ecRec->personId])) {
                $ecArr[$ecRec->personId] = $ecRec;
            }
        }
        

        $query = self::getQuery();
        $query->where("#date >= '{$pRec->start}' AND #date <= '{$pRec->end}'");
        $query->groupBy("personId");
        while($rec = $query->fetch()) {
            if(!isset($ecArr[$rec->personId])) {
                $ecArr[$rec->personId] = new stdClass();
            }
        }

        // Дали да извадим формулата от длъжността
        $replaceFormula = dt::now() < $pRec->end;


        // Подготвяме масив с нулеви стойности
        $names = self::getIndicatorNames();
        foreach($names as $class => $nArr) {
            foreach($nArr as $n) {
                $zeroInd[$n] = 0;
            }
        }

 
        // За всеки един договор, се опитваме да намерим формулата за заплащането от позицията.
        foreach($ecArr as $personId => $ecRec) {

            $res = (object) array(
                'personId' => $personId,
                'periodId' => $pRec->id,
                );
            
            $sum = array();
 
            if(isset($ecRec->positionId)) {
                $posRec = hr_Positions::fetch($ecRec->positionId);
                if(!empty($ecRec->salaryBase)) {
                    $sum['$BaseSalary'] = $ecRec->salaryBase;
                }
            }
         
            $names = self::getIndicatorNames();
            $query = self::getQuery();
            $query->where("#date >= '{$pRec->start}' AND #date <= '{$pRec->end}'");
            $query->where("#personId = {$personId}");
            while($rec = $query->fetch()) {
                $indicator = $names[$rec->sourceClass][$rec->indicatorId];
                $sum[$indicator] += $rec->value;
            }
            
            $prlRec = hr_Payroll::fetch("#personId = {$personId} AND #periodId = {$pRec->id}");
            
            if(empty($prlRec)) {
                $prlRec = new stdClass();
                $prlRec->personId = $personId;
                $prlRec->periodId = $pRec->id;
            }
            
     
            if($replaceFormula && $ecRec->positionId) {  
                $prlRec->formula = hr_Positions::fetchField($ecRec->positionId, 'formula');
            }

            // Изчисляване на заплатата
            $prlRec->salary = NULL;
            if($prlRec->formula) {
                $contex = array();
                foreach($zeroInd as $name => $zero) {
                    if(strpos($prlRec->formula, $name) !== FALSE) {
                        $contex['$' . $name] = $sum[$name] + $zero;
                    }
                }
                
                uksort($contex, "str::sortByLengthReverse");

                // Заместваме променливите и индикаторите
                $expr  = strtr($prlRec->formula, $contex);
        
                if(str::prepareMathExpr($expr) === FALSE) {
                    $prlRec->error = 'Невъзможно изчисление';
                } else {
                    $prlRec->salary = str::calcMathExpr($expr, $success);

                    if($success === FALSE) {
                        $prlRec->error = 'Грешка в калкулацията';
                    }
                }

            } 

            $prlRec->indicators = $sum;

            hr_Payroll::save($prlRec);

        }

        // Ако не успеем - заплащането е базовото от договора

        // Извличаме и сумираме всички индикатори за дадения човек, за дадения период

        // Заместваме във формулата и получаваме  резултата

        // В записа записваме и формулата и заместените велични
    }
    
    /**
     * Извличаме имената на идикаторите
     */
    public static function getIndicatorNames()
    {
        // Масив за резултата
        $names = array();

        // Намираме всички класове съдържащи интерфейса
        $docArr = core_Classes::getOptionsByInterface('hr_IndicatorsSourceIntf');
        
        // Ако нямаме източници - нищо не правим
        if(!is_array($docArr) || !count($docArr)) return;

        // Зареждаме всеки един такъв клас
        foreach ($docArr as $class){
            $sourceClass = core_Classes::getId($class);
            $sMvc = cls::get($class);
            $names[$sourceClass] = $sMvc->getIndicatorNames();
        }

        return $names;
    }
}