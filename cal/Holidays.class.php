<?php



/**
 * Клас 'cal_Holidays' - Регистър на празнични дни
 *
 *
 * @category  vendors
 * @package   drdata
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class cal_Holidays extends core_Master
{

    /**
     * Плъгини за зареждане
     */
    var $loadList = 'cal_Wrapper, plg_RowTools, plg_Sorting, plg_Search';
    
    
    /**
     * Кой може да разглежда сингъла на документа
     */
    var $canSingle = 'powerUser';
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = 'Данни за празниците в календара';
    

    /**
     * полета от БД по които ще се търси
     */
    var $searchFields = 'day, title, base, type, year, info';
    
    
    /**
     * Полетата, които ще се показват в единичния изглед
     */
    var $singleFields = 'day, base, year, id, title, type, info, nameday';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'admin';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Събитие";
    
         
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/calendar_1.png';
    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'cal/tpl/SingleLayoutHolidays.shtml';

    var $canWrite = 'no_one';


    static $priorities = array(
        'holiday' => 79,
        'international' => 78,
        'bg' => 77,
        'non-working' => 76,
        'workday' => 75,
        'orthodox' => 74,
        'muslim' => 73);
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('key', 'varchar(32)', 'caption=Ключ,export');
        $this->FLD('day', 'int', 'caption=Ден,export');
        $this->FLD('base', 'enum(0=,
        						 01=Януари,
                                 02=Февруари,
                                 03=Март,
                                 04=Април,
                                 05=Май,
                                 06=Юни,
                                 07=Юли,
                                 08=Август,
                                 09=Септември,
                                 10=Октомври,
                                 11=Ноември,
                                 12=Декември,
                                 EST=Великден,
                                 CEST=Кат. Великден)', 'caption=База,export');
        $this->FLD('weekday', 'enum(0=,
        						 first-monday=Първи понеделник,
                                 last-monday=Последен понеделник,
                                 first-tuesday=Първи вторник,
                                 last-tuesday=Последен вторник,
                                 first-wednesday=Първа сряда,
                                 last-wednesday=Последна сряда,
                                 first-thursday=Първи четвъртък,
                                 last-thursday=Последен четвъртък,
                                 first-friday=Първи петък,
                                 last-friday=Последен петък,
                                 first-saturday=Първа събота,
                                 last-saturday=Последна събота,
                                 first-sunday=Първа неделя,
                                 last-sunday=Последна неделя,
                                 )', 'caption=Ден от седмицата,export');
        $this->FLD('year', 'int', 'caption=Година,export');
        $this->FLD('title', 'varchar', 'caption=Празник->Заглавие,placeholder=Заглавие,export');
        $this->FLD('type', 'enum(0=,
        								holiday=Празник,
        								non-working=Неработен,
                                        workday=Отработване,
                                        orthodox=Християнски,
                                        muslim=Мюсюлмански,
                                        international=Международен,
                                        AU=Австралия,
										AT=Австрия,
										AZ=Азербайджан,
										AL=Албания,
										DZ=Алжир,
										AO=Ангола,
										AI=Ангуила,
										AD=Андора,
										AR=Аржентина,
										AM=Армения,
										AF=Афганистан,
										BD=Бангладеш,
										BB=Барбадос,
										BS=Бахамските острови,
										BY=Беларус,
										BE=Белгия,
										BZ=Белиз,
										BJ=Бенин,
										BO=Боливия,
										BA=Босна и Херцеговина,
										BR=Бразилия,
										BF=Буркина Фасо,
										BI=Бурунди,
										BG=България,
										GB=Великобритания,
										VE=Венецуела,
										VN=Виетнам, 
										GM=Гамбия,
										GH=Гана,
										GT=Гватемала,  
										GY=Гвиана,                                 
										DE=Германия,
										GE=Грузия,
										GR=Гърция,                                     
										DK=Дания, 
										CD=Демократична република Конго,
										DJ=Джибути,
										DO=Доминиканската република,                                     
										EG=Египет,
										EC=Еквадор,
										EE=Естония,
										ET=Етиопия,
										IL=Израел,
										IN=Индия,  
										ID=Индонезия,
										IQ=Ирак,
										IR=Иран,
										IE=Ирландия,
										IS=Исландия,
										ES=Испания,
										IT=Италия,                                                                          
										JO=Йордания, 
										KZ=Казахстан, 
										KH=Камбоджа,                                                                 
										CA=Канада,
										QA=Катар,
										KE=Кения,
										CY=Кипър,
										KG=Киргизстан,
										CN=Китай,
										CR=Коста Рика,
										CI=Кот дИвоар,
										CO=Колумбия,
										KM=Коморските острови,
										KR=Корея,
										CU=Куба,
										KW=Кувейт,                                                            
										LV=Латвия,
										LR=Либерия,
										LY=Либия,
										LB=Ливан,
										LT=Литва,
										LI=Лихтенщайн,
										LU=Люксембург,
										MU=Мавриций, 
										MG=Мадагаскар,                                   
										MK=Македония,
										MY=Малайзия,
										MV=Малдиви,
										ML=Мали,
										MT=Малта,
										MA=Мароко,
										MX=Мексико,
										MZ=Мозамбик,
										MD=Молдова,
										MC=Монако,
										MN=Монголия,
										NA=Намибия,
										NR=Науру,
										NP=Непал,
										NE=Нигер, 
										NG=Нигерия,                         
										NZ=Нова Зeландия,
										NO=Норвегия,                                                                  
										AE=Обединени арабски емирства,                                                                            
										PK=Пакистан,
										PW=Палау,
										PA=Панама,
										PY=Парагвай,
										PE=Перу,
										PO=Полша,
										PT=Португалия,
										CG=Република Конго, 
										RO=Румъния,
										RU=Русия,         
										SM=Сан Марино,
										SA=Саудитска Арабия,
										US=САЩ,
										SG=Сингапур,
										SK=Словакия,
										SI=Словения,
										RS=Сърбия,
										TJ=Таджикистан,
										TW=Тайван,
										TH=Тайланд,
										TM=Туркменистан,
										TR=Турция,
										UZ=Узбекистан,
										UA=Украйна,
										HU=Унгария,
										UY=Уругвай,
										FJ=Фиджи,
										PH=Филипини,
										FI=Финландия,
										FR=Франция,                
										NL=Холандия,
										HN=Хондурас,
										HK=Хонконг,
										HR=Хърватия,
										CF=Централноафриканската република,
										TD=Чад,
										ME=Черна гора,
										CZ=Чехия,
										CL=Чили,
										CH=Швейцария,
										SE=Швеция,                                   
										ZA=ЮАР,
										JM=Ямайка,
										JP=Япония)', 'caption=Празник->Тип,placeholder=Тип на празника,export');
        $this->FLD('info', 'richtext(bucket=calTasks)', 'caption=Празник->Данни,export');
        
        $this->FLD('nameday', 'richtext(bucket=calTasks)', 'caption=Именници,export');
        
        $this->setDbUnique('key');
    }


    /**
     * Обновява празниците в календара
     */
    static function updateCalendarHolidays()
    {
        // Масив за празниците, които ще се добавят в календара
        $events = array();
        
        // Годината на датата от преди 30 дни е начална
        $cYear = date('Y', time() - 30 * 24 * 60 * 60);

        // Начална дата
        $fromDate = "{$cYear}-01-01";

        // Крайна дата
        $toDate = ($cYear + 2) . '-12-31';

        // Масив с години, за които ще се вземат рожденните дни
        $years = array($cYear, $cYear + 1, $cYear + 2);

        // Префикс на клучовете за рожденните дни на това лице
        $prefix = "HOLIDAY-";
        
        //$code2Cards = array();
         
        $card = self::bCards();
        
        $query = self::getQuery();

        while($rec = $query->fetch()) {
        
            if(!$rec->key) continue;

            foreach($years as $year) {
            	
            	$key = $rec->key;
                
                // Ако събитието има година и тя не е текущата разглеждана, то пропускаме
                if($rec->year && ($rec->year != $year)) continue;
                           
                if($rec->base == 'EST') {
                    $base = dt::getOrthodoxEasterTms($year);
                    $delta = 0;
                } elseif($rec->base == 'CEST') {
                    $base = dt::getEasterTms($year);
                    $delta = 0;
                } elseif($rec->weekday && $rec->base) {
                    $month = $rec->base;
					$base = dt::firstDayOfMonthTms($month, $year, $rec->weekday);  
					$delta = 0;  
                } else {              	
                    $base = mktime(23, 59, 59, $rec->base, 1, $year);
                    $delta = -1;
                }
                
                $calRec = new stdClass();
               
                $calRec->key    = $prefix . $rec->key . $year;
                $calRec->time   = date('Y-m-d', $base + 24*60*60*($delta + $rec->day));
                $calRec->type   = $rec->type;
                $calRec->allDay = 'yes';
                $calRec->title  = self::getVerbal($rec, 'title');
                if(strlen($rec->type) == 2) {
                    $calRec->title = self::getVerbal($rec, 'type') . ': ' . $calRec->title;
                }
                
                $calRec->url   = array('cal_Holidays', 'single', $rec->id);
                
                $calRec->users = $card[$rec->type];
                
                $calRec->priority = self::$priorities[$rec->type];

                if(!$calRec->priority) {
                    $calRec->priority = 71;
                }
               
                $events[] = $calRec;
            }
        }
        
        $resArr = cal_Calendar::updateEvents($events, $fromDate, $toDate, $prefix);

        $status = 'В календара са добавени ' . $resArr['new'] . ', обновени ' . $resArr['updated'] . 
            ' и изтрити ' . $resArr['deleted'] . ' празнични или специални дни';

        return $status;
    }
        

    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
    	
    	// Подготвяме пътя до файла с данните 
    	$file = "bglocal/data/Holidays.csv";
    	
    	// Кои колонки ще вкарваме
    	$fields = array( 
    		0 => "key", 
    		1 => "day",
    		2 => "csv_base",
    		3 => "weekday", 
    		4 => "csv_year", 
    		5 => "title",
    		6 => "type", 
    		7 => "csv_info", 
    		8 => "nameday",
    	);
    	
    	// Импортираме данните от CSV файла. 
    	// Ако той не е променян - няма да се импортират повторно 
    	$cntObj = csv_Lib::importOnce($mvc, $file, $fields, NULL, NULL); 
     	
    	// Записваме в лога вербалното представяне на резултата от импортирането 
    	$res .= $cntObj->html;
    	
        // Обновяваме празниците в календара
        $res .= "<li> " . static::updateCalendarHolidays() . "</li>"; 
 		
    }
    
    
    static function on_BeforeSave($mvc, $res, $rec)
    {
    	if(isset($rec->csv_base) && strlen($rec->csv_base) != 0){ 
    		if($rec->csv_base > 0){
    			$rec->base = str_pad((int) $rec->csv_base, 2, '0', STR_PAD_LEFT);
    		} else {
    			$rec->base = $rec->csv_base;
    		}
    	}
    	
    	if(isset($rec->csv_year)){
    		if($rec->csv_year != ""){
    			$rec->year = $rec->csv_year;
    		}
    	}
    	
    	if(isset($rec->csv_info) && strlen($rec->csv_info) != 0){
    		$rec->info = str_replace('\"', '"', $rec->csv_info);
    	}
    	
    }
    
   
    /**
     * Доподготвя вербалните стойности
     */
    function on_AfterRecToVerbal($mvc, $row, $rec, $fileds)
    {
        if(!trim($rec->nameday)) {
            $row->nameday = NULL;
        } else {
            $pData = new stdClass();

            $pData->namesArr = self::getLatinNames($rec->nameday);

            crm_Persons::prepareNamedays($pData);
            
            $tpl = crm_Persons::renderNamedays($pData);

            $row->nameday = new ET($row->nameday);

            $row->nameday->append($tpl);
        }
        
        $row->iconStyle = ht::getIconStyle('cal/icons/' . strtolower($rec->type) . '.png');

        if(strlen($rec->type) == 2) {
            $row->type = tr('Национален празник на') . ' <b>' . $row->type . '</b>';
        }
    }


    /**
     * Връща масив с имена на латиница от описание на именници
     */
    static function getLatinNames($names)
    {   
        // Това долното е недопустимо
	    // $needle = array('<div class="richtext">', "<br></div>");
	
        $names = str_replace(array('и др', '.'), array('', ''), $names);
        $namesArr = explode(',', str::utf2ascii($names));

        foreach($namesArr as $n) {
            $n = strtolower(trim($n));
            $res[$n] = $n;
        }

        return $res;
    }

    
    /**
     * Форма за търсене по дадена ключова дума
     */
    static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->listFilter->showFields = 'search, base, type';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input('type, base', 'silent');
        
        if($type = $data->listFilter->rec->type){
            $data->query->where("#type = '{$type}'");
        }
        
        if($base = $data->listFilter->rec->base){
            $data->query->where("#base = '{$base}'");
        }
    }
    
    
    /**
     * Функция, която връща array[кода на държавата] = списък на потребители
     */
    static function bCards()
    {
    	
    	$inChargePerCountry = array();
    	$profiles = array();
    	
        $query = crm_Persons::getQuery();
           	    	
    	while($rec = $query->fetch()){
    	
 			if($rec->country) {
    		
 				if($recCompanies->inCharge) {
    				$profiles[$rec->country][$rec->inCharge] = TRUE;
 				}
    			
    			if($rec->shared) {
	    			foreach(keylist::toArray($rec->shared) as $userId) {
	    				$profiles[$rec->country][$userId] = TRUE;
	    			}
    			}
 			}
    	
  
        }
        
        $queryCompanies = crm_Companies::getQuery();
    	$queryCompanies->show('id,country,inCharge,shared');

	    while($recCompanies = $queryCompanies->fetch()){
	    	
	    	if($recCompanies->country) {
	    		
	    		if($recCompanies->inCharge) {
	    			$profiles[$recCompanies->country][$recCompanies->inCharge] = TRUE;
	    		}
	    		
	    		if($recCompanies->shared) {
		    		foreach(keylist::toArray($recCompanies->shared) as $userId) {
		    			$profiles[$recCompanies->country][$userId] = TRUE;
		    		}
	    		}
	    	
	    	}
	    }
	    
    	foreach($profiles as $id=>$profile){
    		
    		$recPerson = drdata_Countries::fetch("#id = '{$id}'");
    		
    		$a = keylist::fromArray($profile);
    		
    		$inChargePerCountry[$recPerson->letterCode2] = $a;
    		
    	}

   		return $inChargePerCountry;
    }

}
