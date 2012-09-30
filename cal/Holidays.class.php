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
    	$this->FLD('key', 'varchar', 'caption=Ключ,export');
        $this->FLD('day', 'int', 'caption=Ден,export');
        $this->FLD('base', 'enum(0=&nbsp;,
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
        $this->FLD('weekday', 'enum(0=&nbsp;,
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
        $this->FLD('title', 'varchar', 'caption=Празник->Заглавие,export');
        $this->FLD('type', 'enum(0=&nbsp;,
        								holiday=Празник,
        								non-working=Неработен,
                                        workday=Отработване,
                                        orthodox=Православен,
                                        muslim=Мюсюлмански,
                                        international=Международен,
                                        AU=Австралия,
										AT=Австрия,
										AZ=Азербайджан,
										AL=Албания,
										DZ=Алжир,
										AO=Ангола,
										АТ=Ангуила,
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
										NZ=Нова Заландия,
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
										JP=Япония)', 'caption=Празник->Тип,export');
        $this->FLD('info', 'richtext', 'caption=Празник->Данни,export');
        
        $this->FLD('nameday', 'richtext', 'caption=Именници,export');
        
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
        
              $code2Cards = array();
         
                $card = self::bCards();
              //  bp($card);
                foreach($card as $id=>$persons){
                	foreach($persons as $key => $person){
                		$recCountry = drdata_Countries::fetch("#id = '{$id}'");
                		
                		//Array[двубуквен код на държавата][потребителско id]
                		$code2Cards[$recCountry->letterCode2][$key] = TRUE;
                	
                	}
                }
               
           //bp($code2Cards);
        
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
					$base = dt::firstDayOfMounthTms($month, $year, $rec->weekday);  
					$delta = 0;  
                } else {              	
                    $base = mktime(0, 0, 0, $rec->base, 1, $year);
                    $delta = -1;
                }
                
             
                
                $calRec = new stdClass();
               
                $calRec->key    = $prefix . $rec->key . $year;
                $calRec->time   = date('Y-m-d', $base + 24*60*60*($delta + $rec->day));
                $calRec->type   = $rec->type;
                /*if($calRec->type == 'nameday') {
                    $calRec->type = 'orthodox';
                }*/
                $calRec->allDay = 'yes';
                $calRec->title  = self::getVerbal($rec, 'title');
                if(strlen($rec->type) == 2) {
                    $calRec->title = self::getVerbal($rec, 'type') . ': ' . $calRec->title;
                }
                
	              foreach($code2Cards as $code2 => $users){
	              	
	                 	if($rec->type == $code2){
		                  foreach($users as $id => $u){
	                 		// Потребителите имащи право да виждат този празник са keylist
		               		$calRec->users = '|'.$id.'|';
		               		
		                  }
		               	
		               	}
	              }
                
                $calRec->url    = toUrl(array('cal_Holidays', 'single', $rec->id), 'local');
                
                     
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
 		// Изтриваме съдържанието й
		$mvc->db->query("TRUNCATE TABLE  `{$mvc->dbTableName}`");
		
    	$res .= static::loadData();
        $res .= "<li> " . static::updateCalendarHolidays() . "</li>";
    }
    
    
    /**
     * Зареждане на началните празници в базата данни
     */
    static function loadData()
    {
    	
        $csvFile = __DIR__ . "/data/Holidais.csv";
        
        $created = $updated = 0;
        
        if (($handle = @fopen($csvFile, "r")) !== FALSE) {
         
            while (($csvRow = fgetcsv($handle, 2000, ",", '"', '\\')) !== FALSE) {
               
                $rec = new stdClass();
              
                $rec->key = $csvRow[0]; 
                $rec->day = $csvRow[1];
                if($csvRow[2] > 0){
                	$rec->base = str_pad((int) $csvRow[2], 2, '0', STR_PAD_LEFT);
                } else {
                	$rec->base = $csvRow[2];
                }
                
                $rec->weekday = $csvRow[3]; 
                
                if($csvRow[4] != ''){
                	$rec->year = $csvRow[4];
                }
                
                $rec->title = $csvRow[5];
                $rec->type = $csvRow[6];
                $rec->info = str_replace('\"', '"', $csvRow[7]);
                $rec->nameday = $csvRow[8];             
                
                static::save($rec);

                $ins++;
            }
            
            fclose($handle);
            
            $res .= "<li style='color:green;'>Създадени са записи за {$ins} празници или специални дни</li>";
        } else {
            $res = "<li style='color:red'>Не може да бъде отворен файла '{$csvFile}'";
        }
        
        return $res;
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

            $pData->namesArr = self::getLatinNames($row->nameday);
           
            crm_Persons::prepareNamedays($pData);
            
            $tpl = crm_Persons::renderNamedays($pData);

            $row->nameday = new ET($row->nameday);

            $row->nameday->append($tpl);
        }
        
        $row->iconStyle = 'background-image:url(' . sbf('cal/icons/' . strtolower($rec->type) . '.png') . ');';

        if(strlen($rec->type) == 2) {
            $row->type = tr('Национален празник на') . ' <b>' . $row->type . '</b>';
        }
    }


    /**
     * Връща масив с имена на латиница от описание на именници
     */
    static function getLatinNames($names)
    {
	    	$needle = array('<div class="richtext">', "<br>
	</div>");
	    	$names = str_replace($needle, "", $names->content);
    	    $namesArr = explode(',', str::utf2ascii($names));

        foreach($namesArr as $n) {
            $n = strtolower(trim($n));
            $nArr = explode(' ', $n);
            $res[$nArr[0]] = $nArr[0];
        }

        return $res;
    }

    /**
     * Форма за търсене по дадена ключова дума
     */
    static function on_AfterPrepareListFilter($mvs, &$res, $data)
    {
        $data->listFilter->showFields = 'search, base, type';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        $data->listFilter->input('type, base', 'silent');
        
        if($type = $data->listFilter->rec->type){
            $data->query->where("#type = '{$type}'");
        }
        
        if($base = $data->listFilter->rec->base){
            $data->query->where("#base = '{$base}'");
        }
    }
    
    /**
     * Функция, която връща array[кода на държавата][потребителско id имащо собственост тази държава]
     */
    function bCards ()
    {
    	
    	$cards = array();
    	$profiles = array();
        $query = crm_Profiles::getQuery();
           	    	
    	while($rec = $query->fetch()){
    	
    		$profiles[$rec->personId][$rec->userId] = TRUE;
    			
    	}
    	
    	foreach($profiles as $id=>$profile){
    		
    		foreach($profile as $idProf => $person){
    		 
    			$recPerson = crm_Persons::fetch("#id = '{$id}'");
    		 	
    	     	$cards[$recPerson->country][$idProf] = TRUE;
    	     	     		
    		}
    	}
    	
         	return $cards;
    }

}
