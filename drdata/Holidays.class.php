<?php



/**
 * Клас 'drdata_Holidays' - Регистър на празнични дни
 *
 *
 * @category  vendors
 * @package   drdata
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class drdata_Holidays extends core_Master
{

    /**
     * Плъгини за зареждане
     */
    var $loadList = 'drdata_Wrapper, plg_RowTools, plg_Sorting, plg_Search, plg_State';
    

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
    var $singleLayoutFile = 'drdata/tpl/SingleLayoutHolidays.shtml';
    
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
                                        nameday=Имен ден,
                                        orthodox=Православен,
                                        muslim=Мюсюлмански,
                                        foreign=Чуждестранен,
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
        $this->FLD('info', 'text', 'caption=Празник->Данни,export');
        
        $this->FLD('nameday', 'text', 'caption=Именници,export');
        
        $this->setDbUnique('key');
    }
    
    
    
    /**
     * Зареждане на началните празници в базата данни
     */
    static function loadData()
    {
    	
        $csvFile = self::getCsvFile();
        
        $created = $updated = 0;
        
        if (($handle = @fopen($csvFile, "r")) !== FALSE) {
         
            while (($csvRow = fgetcsv($handle, 1000, ",")) !== FALSE) {
               
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
                $rec->info = $csvRow[7];
                $rec->nameday = $csvRow[8];
    
               
                // Ако има запис с това 'id'
               
                if ($rec->id = drdata_Holidays::fetchField(array("#day = '[#1#]' AND #base = '[#2#]' AND #type = '[#3#]'", $rec->day, $rec->base, $rec->type), 'id')) {
                    $updated++;
                } else {
                    $created++;
                }
                
                
                drdata_Holidays::save($rec, NULL, 'IGNORE');
            }
            
            fclose($handle);
            
            $res = $created ? "<li style='color:green;'>" : "<li style='color:#660000'>";
            $res .= "Създадени {$created} нови празника, обновени {$updated} съществуващи празника.</li>";
        } else {
            $res = "<li style='color:red'>Не може да бъде отворен файла '{$csvFile}'";
        }
        
        return $res;
    }
    
    
    
    /**
     * Връща датата на православния Великден за указаната година
     */
    function getOrthodoxEaster($year)
    {
        $r1 = $year % 19;
        $r2 = $year % 4;
        $r3 = $year % 7;
        $ra = 19 * $r1 + 16;
        $r4 = $ra % 30;
        $rb = 2 * $r2 + 4 * $r3 + 6 * $r4;
        $r5 = $rb % 7;
        $rc = $r4 + $r5;
        
        // Православния Великден за тази година се пада $rc дни след 3-ти Април
        return strtotime("3 April $year + $rc days");
    }
    

    /**
     * Връща датата на западния Великден за указаната година
     */
    function getEaster($year)
    {
        return strtotime("{$year}-03-21 +".easter_days($year)." days");
    }


    
    /**
     * Връща списък с имената, които имат именен ден за тази дата
     */
    function getNamedays($date)
    {
        $year = date("Y", $date);
        $day = date("d-m", $date);
        
        // Не поддържаме информация за преди 2000 год
        if($year<2000) return FALSE;

        $names = $this->fixedNamedays[$day];

        $easter = $this->getOrthodoxEaster($year);
        foreach($this->movableNamedays as $days => $n) {
            if (date("d-m", $easter + 24 * 3600 * $days) == $day) {
                $names .= ($names ? "," : "") . $n;
            }
        }
        
        return $names;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function isIslamicName($name)
    {
        if(!$name) return false;
        
        return strpos(" ,{$this->islamicNames},", ",$name,") > 0;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function isWomenName($name)
    {
        if(!$name) return false;
        
        return strpos(" ,{$this->womenNames},", ",$name,") > 0;
    }
    

    /****************************************************************************************
    *                                                                                       *
    * Реализация на интерфейса за календарните събития                                      *
    *                                                                                       *
    ****************************************************************************************/
    
    
    /**
     * Връща масив със събития за посочения човек
     */
    function eventsForCalendar()
    {
        // Подготвяме масива с годините, за които ще се запише събитието
        $cYear = date("Y");
        $years = array($cYear, $cYear + 1, $cYear + 2);
        
        $query = self::getQuery();

        while($rec = $query->fetch()) {
            foreach($years as $year) {
            	
            	$key = $rec->key;
                
                // Ако събитието има година и тя не е текъщата разглеждана, то пропускаме
                if($rec->year && ($rec->year != $year)) continue;

                           
                if($rec->base == 'EST') {
                    $base = drdata_Holidays::getOrthodoxEaster($year);
                    $delta = 0;
                } elseif($rec->base == 'CEST') {
                    $base = drdata_Holidays::getEaster($year);
                    $delta = 0;
                } elseif($rec->weekday && $rec->base) {
                    $month = $rec->base;
               
					$day = drdata_Holidays::firstDayOfMounth($month, $year, $rec->weekday);  
					$base = mktime(0, 0, 0, $month, $day, $year);
					$delta = 0;  
                } else {              	
                	
                	//? expects parameter 4 to be long, string given ?
                	//echo "<li> $rec->base";
                    $base = mktime(0, 0, 0, $rec->base, 1, $year);
                    $delta = -1;
                }
                
                
                $calRec = new stdClass();
               
                $calRec->key = $rec->key.$year;
                $calRec->date = date('Y-m-d', $base + 24*60*60*($delta + $rec->day));
                $calRec->type = $rec->type;
                $calRec->allDay = 'yes';
                $calRec->title = $rec->title;
                $calRec->users = '';
                $calRec->url = toUrl(array('drdata_Holidays', 'single', $rec->id), 'local');
               
                
                $calEvents[] = $calRec;
         
            }
      
        }
      //  bp($calEvents);
        return $calEvents;
    }
    
    
    /**
     * Връща вербалното име на посоченото събитие за посочения обект
     */
    function getVerbalCalendarEvent($type, $objectId, $date)
    {
        $rec = $this->fetch($objectId);
        
        if($rec->holidayType == 'bulgarian') {
            $event = "<div style='color:green'><b>{$rec->holidayName}</b></div>";
        } elseif($rec->holidayType == 'nameday') {
            $event = "<a 1style='color:blue' href='" .
            toUrl(array('crm_Persons', 'list', 'names' => $rec->holidayData, 'date' => $date)) .
            "'>{$rec->holidayName}</a>";
        }
        
        return $event;
    }
    
    static function on_AfterPrepareSingle($mvc, $data)
    {
    	//Намираме id-то на текущия потребител
    	$idCurrentPerson = core_Users::getCurrent('id');
    	
    	$data->row->iconStyle = 'background-image:url(' . sbf($mvc->singleIcon) . ');';
    	
    	  	
    	$queryHoliday = parent::getQuery();
    	
    	while ($nameday = $queryHoliday->fetch("#nameday != '' AND  #day = '{$data->rec->day}' AND #base = '{$data->rec->base}'")){
    		
    		//Правим масив от списъка с имена записани в полето nameday
    		$personNameday = split(",", $nameday->nameday);
    		
    		//Вземаме всички визитки принадлежащи на текущия потребител
	    	$query = crm_Persons::getQuery();
	    	
	    	while ($person = $query->fetch("#inCharge = '{$idCurrentPerson}'")){
	    		
	    		//Взимаме само първото име на човека
	    		$name = strstr($person->name, " ", TRUE);
	    		$nameCyrillic = strtolower(core_String::utf2ascii($name));
			
	    		foreach($personNameday as $pn){
	    		
	    			$pn = strtolower(core_String::utf2ascii($pn));
	    			$pn = strtolower(str::trim($pn));
	    			$name = strtolower(str::trim($name));
	    			$nameCyrillic = strtolower(str::trim($nameCyrillic));
	    			
	    			if(($pn == $name) || ($pn == $nameCyrillic)){
	    			
	    				$data->row->urlPerson .= ht::createLink($person->name,array('crm_Persons', 'single', $person->id))."<br>";
	    				
	    			}
	    			
	    		}

	    	}
    	}
    	
    }
    
   
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
 		// Изтриваме съдържанието й
		$mvc->db->query("TRUNCATE TABLE  `{$mvc->dbTableName}`");
		
    	drdata_Holidays::loadData();
           
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function extractName($name) {
        $textEncoding = getInstance("TextEncoding");
        $name = trim(mb_strtolower($name));
        $name = $textEncoding->utf2ascii($name);
        $name = str_replace(
            array("4", "6", "w", "ja", "jq", "yq", "iq", "q" , "tz", "iya", "ya", "yu", "ce", "co", "ci", "ca", "cu", "cv", "th"),
            array("ch", "sh", "v", "ia", "ia", "ia", "ia", "ia", "ts", "ia", "ia", "ju", "tse", "tso", "tsi", "tsa", "tsu", "tsv", "t"),
            $name);
        $name = str_replace(
            array("aa", "bb", "cc", "dd", "ee", "ff", "gg", "hh" , "ii", "jj", "kk", "ll", "mm", "nn", "oo", "pp", "qq", "rr", "ss", "tt", "uu", "vv", "ww", "xx", "yy", "zz"),
            array("a", "b", "c", "d", "e", "f", "g", "h" , "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z"),
            $name);
        $name = preg_replace('/[^a-zа-я]+/u', ' ', $name);
        $nameArr = explode(" ", $name);
        
        if(mb_strlen($nameArr[0]) > 2 && $nameArr[0] != "eng") {
            return $nameArr[0];
        } elseif(mb_strlen($nameArr[1]) > 2) {
            return $nameArr[1];
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function extractCity($name) {
        $textEncoding = getInstance("TextEncoding");
        $name = trim(mb_strtolower($name));
        $name = "#" . $textEncoding->utf2ascii($name);
        $name = str_replace(
            array("#grad", "#gr.", "4", "6", "w", "ja", "jq", "yq", "iq", "q" , "tz", "iya", "ya", "yu", "ce", "co", "ci", "ca", "cu", "cv", "th"),
            array("", "", "ch", "sh", "v", "ia", "ia", "ia", "ia", "ia", "ts", "ia", "ia", "ju", "tse", "tso", "tsi", "tsa", "tsu", "tsv", "t"),
            $name);
        $name = str_replace(
            array("aa", "bb", "cc", "dd", "ee", "ff", "gg", "hh" , "ii", "jj", "kk", "ll", "mm", "nn", "oo", "pp", "qq", "rr", "ss", "tt", "uu", "vv", "ww", "xx", "yy", "zz"),
            array("a", "b", "c", "d", "e", "f", "g", "h" , "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z"),
            $name);
        $name = preg_replace('/[^a-zа-я]+/u', ' ', $name);
        $name = trim($name);
        
        return $name;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getCorrectCityName($city) {
        
        $c = $this->goodCityNames[$city];
        
        if($c) return $c;
        
        return ucwords($city);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function isCityFeast($city, $date, $year) {
        if(!$city) return false;
        $cites = $this->cityDay[$date];
        
        if (strpos(" ,$cites,", ",$city,") > 0) return true;
        
        return false;
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
     * @todo Чака за документация...
     */
    static private function getCsvFile()
    {
        return __DIR__ . "/data/Holidais.csv";
    }
    
    
    function firstDayOfMounth ($month, $year, $wDay)
    {
  	
    				//Определяме първия ден от месеца, какъв ден от седмиата е, като резултата е в
                    // числов вид: 0-неделя ... 6-събота
                    $firstDayM = date("w", mktime(0, 0, 0, $month, 1, $year));
                   
                    if($firstDayM > 1){

                    	//Първият понеделни на месеца
                    	$fMonday = date("d", mktime(0,0,0,$month,1,$year)+(86400*(8-$firstDayM)));
                        
                    	$fSaturday = date("d", mktime(0,0,0,$month,1,$year)+(86400*(6-$firstDayM)));
		                $fSunday = date("d", mktime(0,0,0,$month,1,$year)+(86400*(7-$firstDayM)));
                    	
                    	if($fMonday == 7){
                    		$fTuesday = date("d", mktime(0,0,0,$month,1,$year)+(86400*(2-$firstDayM)));
                    	} else {
                    		$fTuesday = date("d", mktime(0,0,0,$month,1,$year)+(86400*(9-$firstDayM)));
                    	}
                    	
                    	if($fMonday == 3){
                    		$fFriday = date("d", mktime(0,0,0,$month,1,$year)+(86400*(12-$firstDayM)));
                    	} else {
                    		$fFriday = date("d", mktime(0,0,0,$month,1,$year)+(86400*(5-$firstDayM)));
                    	}
                    	
	                    //Проверяваме дали понеделника е 7-ми ден от месеца. Ако е - вторник е 1
	                    if($fMonday == 7 || $fMonday == 6){
                    	
		                    $fWednesday = date("d", mktime(0,0,0,$month,1,$year)+(86400*(3-$firstDayM)));
		                	$fThursday = date("d", mktime(0,0,0,$month,1,$year)+(86400*(4-$firstDayM)));
		                	
		                   // bp($fMonday, $fTuesday, $fWednesday, $fThursday, $fFriday, $fSaturday, $fSunday);
		                    	
                    	 } elseif ($fMonday == 5) {
                    	
		                    $fWednesday = date("d", mktime(0,0,0,$month,1,$year)+(86400*(10-$firstDayM)));
		                    $fThursday = date("d", mktime(0,0,0,$month,1,$year)+(86400*(4-$firstDayM)));
		                	
		                	// bp($fMonday, $fTuesday, $fWednesday, $fThursday, $fFriday, $fSaturday, $fSunday);
		                	 
                   	     } elseif ($fMonday == 4 || $fMonday == 3) {
                    	
		                    $fWednesday = date("d", mktime(0,0,0,$month,1,$year)+(86400*(10-$firstDayM)));
		                    $fThursday = date("d", mktime(0,0,0,$month,1,$year)+(86400*(11-$firstDayM)));
		                	
		                	//bp($fMonday, $fTuesday, $fWednesday, $fThursday, $fFriday, $fSaturday, $fSunday);
                   	     } 
                  
                     //Първия ден от месеца е точно първият понеделник
                     //или първият ден от месеца е неделя, следователно 2-ри ще е първият понеделник
                    } elseif ($firstDayM == 1 || $firstDayM == 0){
                    	
	                	$fMonday = date("d", mktime(0,0,0,$month,1,$year)+(86400*(1-$firstDayM)));
	                	$fTuesday = date("d", mktime(0,0,0,$month,1,$year)+(86400*(2-$firstDayM)));
	                	$fWednesday = date("d", mktime(0,0,0,$month,1,$year)+(86400*(3-$firstDayM)));
	                	$fThursday = date("d", mktime(0,0,0,$month,1,$year)+(86400*(4-$firstDayM)));
	                	$fFriday = date("d", mktime(0,0,0,$month,1,$year)+(86400*(5-$firstDayM)));
	                	$fSaturday = date("d", mktime(0,0,0,$month,1,$year)+(86400*(6-$firstDayM)));
	                		if($firstDayM == 1){
	                			$fSunday = date("d", mktime(0,0,0,$month,1,$year)+(86400*(7-$firstDayM)));
	                		} else {
	                			$fSunday = date("d", mktime(0,0,0,$month,1,$year)+(86400*($firstDayM)));
	                		}
	                	//bp($fMonday, $fTuesday, $fWednesday, $fThursday, $fFriday, $fSaturday, $fSunday);
	                
                    } 
                    
                    if($wDay == 'first-monday'){
                    	return $fMonday;
                    } elseif($wDay == 'first-tuesday'){
                    	return $fTuesday;
                    }elseif($wDay == 'first-wednesday'){
                    	return $fWednesday;
                    }elseif($wDay == 'first-thursday'){
                    	return $fThursday;
                    }elseif($wDay == 'first-friday'){
                    	return $fFriday;
                    }elseif($wDay == 'first-saturday'){
                    	return $fSaturday;
                    }elseif($wDay == 'first-sunday'){
                    	return $fSunday;
                    }
                  
    }
}

