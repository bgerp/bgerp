<?php



/**
 * Клас 'drdata_Holidays' - Регистър на празнични дни
 *
 *
 * @category  all
 * @package   drdata
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class drdata_Holidays extends core_Manager
{
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = array(
        // Интерфейс на източник на събития за календара
        'crm_CalendarEventsSourceIntf',
    );
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'drdata_Wrapper';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('day', 'int', 'caption=Ден');
        $this->FLD('base', 'enum(01=Януари,
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
EST=Велик ден)', 'caption=База');
        $this->FLD('greeting', 'varchar', 'caption=Поздрав');
        $this->FLD('holidayName', 'varchar', 'caption=Празник->Име');
        $this->FLD('holidayType', 'enum(bulgarian,nameday,muslim,foreign)', 'caption=Празник->Тип');
        $this->FLD('holidayData', 'text', 'caption=Празник->Данни');
    }
    
    
    /**
     * Връща датата на ортодоксалния Великден за указаната година
     */
    function getOrthodoxEaster($year)
    {
        // echo date("d-m-Y", getOrthodoxEaster("2007"));
        $r1 = $year % 19;
        $r2 = $year % 4;
        $r3 = $year % 7;
        $ra = 19 * $r1 + 16;
        $r4 = $ra % 30;
        $rb = 2 * $r2 + 4 * $r3 + 6 * $r4;
        $r5 = $rb % 7;
        $rc = $r4 + $r5;
        
        //Orthodox Easter for this year will fall $rc days after April 3
        
        return strtotime("3 April $year + $rc days");
    }
    
    
    /**
     * Връща списък с имената, които имат именен ден за тази дата
     */
    function getNamedays($date)
    {
        $year = date("Y", $date);
        $day = date("d-m", $date);
        
        if($year<2000) return false;
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
    function isIslamicName($name) {
        if(!$name) return false;
        
        return strpos(" ,{$this->islamicNames},", ",$name,") > 0;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function isWomenName($name) {
        if(!$name) return false;
        
        return strpos(" ,{$this->womenNames},", ",$name,") > 0;
    }
    
    /****************************************************************************************
* *
* Реализация на интерфейса за календарните събития *
* *
****************************************************************************************/
    
    
    /**
     * Връща масив със събития за посочения човек
     */
    function getCalendarEvents_($objectId, $years = array())
    {
        // Ако липсва, подготвяме масива с годините, за които ще се запише събитието
        if(!count($years)) {
            $cYear = date("Y");
            $years = array($cYear, $cYear + 1, $cYear + 2);
        }
        
        $rec = $this->fetch($objectId);
        
        foreach($years as $y) {
            
            $calRec = new stdClass();
            
            if($rec->base > 0) {
                $calRec->date = "{$y}-{$rec->base}-{$rec->day}";
            } elseif($rec->base == 'EST') {
                $est = date('Y-m-d', $this->getOrthodoxEaster($y));
                $calRec->date = dt::addDays($rec->day, $est);
            }
            $calRec->type = 'holiday';
            
            $res[] = $calRec;
        }
        
        // Добавяме изтичанията на личните документи....
        
        return $res;
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
    
    
    /**
     * Изпълнява се всяка година и синхронизира националните празници в календара
     */
    static function addHolidaysToCalendar()
    {
        $query = self::getQuery();
        
        $Holidays = cls::get('drdata_Holidays');
        
        while($rec = $query->fetch("#holidayType = 'bulgarian' || #holidayType = 'nameday'")) {
            $eventsCnt += crm_Calendar::updateEventsPerObject($Holidays, $rec->id);
        }
        
        return "<li> Обновени са $eventsCnt празника</li>";
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    function on_AfterSetupMvc($mvc, &$res)
    {
        
        $holidays =
        "01|01|Нова година|Честита Нова [#year#] Година, [#name#]!
01|02|Втори ден на Нова Година|Много Здраве, Щастие и Успехи през [#year#] година, [#name#]!
03|03|Деня на Освобождението|Поздрави за Деня на Освобождението на България, [#name#]!
03|08|Международен ден на жената|Честит 8-ми Март, [#name#]!
05|01|Деня на Труда|Поздрави за Деня на Труда, [#name#]!
05|06|Гергьовден|Поздрави за Деня на Храбростта, [#name#]!
05|24|Деня на Славянската Писменост|Поздрави за Деня на Славянската Писменост, [#name#]!
09|06|Деня на Съединението|Поздрави за Деня на Съединението, [#name#]!
09|22|Деня на Независимостта|Поздрави за Деня на Независимостта, [#name#]!
11|01|Деня на народните будители|Поздрави за Деня на Народните Будители, [#name#]!
12|24|Бъдни вечер|Бъдни вечер наближава, чудеса на всеки подарява, [#name#]!
12|25|Коледа|Честито Рождество Христово, [#name#]!
12|26|Втори ден на Коледа|Честита Коледа, [#name#]!
EST|-2|Велики петък|
EST|-1|Велика събота|
EST|0|Великден|Христос Воскресе, [#name#]";
        
        $rows = explode("\n", $holidays);
        
        foreach($rows as $row) {
            $parts = explode("|", trim($row));
            
            $rec = $mvc->fetch("#holidayType = 'bulgarian' AND #base = '{$parts[0]}' AND #day = '{$parts[1]}'");
            $rec->base = $parts[0];
            $rec->day = $parts[1];
            $rec->holidayName = $parts[2];
            $rec->greeting = $parts[3];
            $rec->holidayType = 'bulgarian';
            
            if($rec->id) {
                $updated++;
            } else {
                $new++;
            }
            
            $mvc->save($rec);
        }
        
        // Добавяме именните дни
        foreach($mvc->fixedNamedays as $date => $names)
        {
            list($day, $month) = explode("-", $date);
            $rec = $mvc->fetch("#holidayType = 'nameday' AND #base = '{$month}' AND #day = '{$day}'");
            
            $rec->base = $month;
            $rec->day = $day;
            $data = explode("|", $names);
            
            if(count($data) == 2) {
                $rec->holidayName = trim($data[0]);
                $rec->holidayData = trim($data[1]);
            } else {
                $rec->holidayName = "Имен ден";
                $rec->holidayData = $names;
            }
            $rec->holidayData = strtolower(str::utf2ascii($rec->holidayData));
            $rec->greeting = "Честит имен ден, [#name#]!";
            $rec->holidayType = 'nameday';
            
            if($rec->id) {
                $updated++;
            } else {
                $new++;
            }
            
            $mvc->save($rec);
        }
        
        if($new) {
            $res = "<li style='color:green;'>Добавени {$new} празника</li>";
        }
        
        if($updated) {
            $res = "<li style='color:#660000;'>Обновени {$updated} празника</li>";
        }
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
    function drdata_Holidays() {
        
        // Фиксирани именни дни
        $this->fixedNamedays = array(
            '01-01' => 'Васильовден| vasil, vasilka, veselin, veselina, vesela, vesel, vesela, veselin, veselina, veselka, vesi, veska, vesko, veso, vessela, vesselin, vesselina, vesso, vessy, vesy, vasi, vasia, vasil, vasilena, vasilka, vaska, vasko, vaso, vassil, vasya',
            '02-01' => 'goran, goritsa, goriza, silvestar, silvester, sylvester, silvia, silvya, silva, silvana, silvina, silveto, silvi, silvija, silvina, silviq, silviya, ognian, ognyan, ogi, ogy, ognyana, ogniana, ognjan, ognqn, ognyan, plam, plama, plame, plamen, plamena, plamenka, plamenna, plami, plamka, plamcho, plamencho, serafim',
            '04-01' => 'tihomir, tisho, tihomira, tihomi, tixomir, tixomira',
            '06-01' => 'jordan, jordanka, yordan, yordanka, iordan, iordanka, dancho, dan4o, danka, bogoljub, bogoljuba, bojan, boyan, bojana, boyana, bozhan, bozhana, dana, bojan, bogomil, bojana, bogdan, bogdana, najden, naiden, nayden, teodosi, teodosii, teodosiy',
            '07-01' => 'Св. Йоан Кръстител (Ивановден)|ivan, ivana, ivancho, ivanela, ivanina, ivanka, vani, vania, vanina, vanio, vanja, vanka, vankata, vanko, vanq, vanya, vanyo, jan, jana, jane, janet, janeta, jani, janko, ioan, ioana, ioanna, yoan, yoana, yoanna, iovka, iovko, yovka, yovko, jovka, jovko, ivanichka, ivayla, ivaylo, ivailo, ivajlo, ivalin, ivalina, ivam, ivalyn, ivalyna, ivelin, ivelina, ivo, joncho, yoncho, ioncho, jonka, yonka, ionka, ionka, jonka, yonka, jonko, ionko, yonko, yoto, joto',
            '11-01' => 'Преп. Теодосий Велики|bogdan, bogdana, bogomila, teodosi, teodosyi, teodosii',
            '12-01' => 'Света Татяна|tatjana, tatyana, tatiana, tania, tanq, tatqna',
            '14-01' => 'Преп. Отци,избити в Синай и Раита. Св.Нина (Отдание на Богоявление)|adam',
            '17-01' => 'Преп. Антоний Велики (Антоновден)|anton, antoni, antonia, antonina, antonio, antoniq, antoniy, antoniya, antony, toncho, toni, tonia, tonislav, tonka, tonko, tony, tonya, andon, doncho, donka, donika',
            '18-01' => 'Атанасовден|atanas, atanaska, atnas, nasco, nasi, naska, nasko, naso, tinka, nacho, tinka, tinko, tanio, tanjo, tanyo',
            '20-01' => 'Св. Евтимий, патриарх Търновски|evtim, eftim, evtimii',
            '21-01' => 'Преп.Максим Изповедник; Мчк.Неофит|agnesa, agnes, maskim, max, maxi, maxim, valera, valeri, valeria, valeriq, valeriy, valeriya, valery',
            '22-01' => 'timotei, timotey',
            '23-01' => 'kristofar, hristofor, xristofor, hristofora, hrristofer, kristofera',
            '24-01' => 'aksenia, aksinia, ksenia, oksana',
            '25-01' => 'Св. Григорий Богослов, архиепископ на Цариград|grigo, grigor, grigorena',
            '26-01' => 'jivko, zhivko, jivka, zhivka, zoya, zoja, zoia',
            '01-02' => 'trifon, trifonka, lozan, lozana, lozka, triffon',
            '03-02' => 'mona, simo, simeon, moni, mony, simeona, simon, simona',
            '04-02' => 'jeko, zheko, zhelyazko, zhelqzko, zhechka, zhechko, zheko, jechka, jechko, jelyazko, jelyazko',
            '05-02' => 'agata, dobrinka',
            '06-02' => 'doroteya, doroteia, doroteq, svetla, svetlozar, svetlozara, fotij, fotyi, ognian, ognyan, ogi, ogy, ognyana, ogniana, ognjan, ognqn, ognyan, plam, plama, plame, plamen, plamena, plamenka, plamenna, plami, plamka, plamcho, plamencho',
            '09-02' => 'hristina, xristina',
            '10-02' => 'haralambi, haralampi, xaralambi, xaralampi, lambi, valentin, valentina, valia, valq',
            '13-02' => 'evlogi, zoq, zoya, zoia',
            '14-02' => 'Св. Валентин|valenti, valentin, valentina, valentino',
            '21-02' => 'evstati, ewstati, evstatija, evstatya, evstatiq',
            '01-03' => 'mart, marti, martina, martini, marto, evdokia',
            '04-03' => 'gerasim, gera, gercho',
            '06-03' => 'krasi, krasimir, krasimi, krasimira',
            '09-03' => 'mladen, mladenka, mladena',
            '10-03' => 'galia, galin, galina, genoveva',
            '13-03' => 'nikifor',
            '20-03' => 'svetlozar, svetlozara',
            '21-03' => 'jakov, iakov, yakov',
            '23-03' => 'lidia, lidija, lidiya',
            '24-03' => 'zahari, zaharin, zaharina, hari, xari, zaxari, zaxarin, zaxarina',
            '25-03' => 'Благовещение|blago, blagovest, blagovesta, blagoi, blagoj, blagoy, evangelina, vangel, mariana, marijana, marian, marijan',
            '26-03' => 'gavrail, gavril, gabriela',
            '28-03' => 'albena, beni, boyan, boian, boycho, boicho, boyana, boiana, boyko, boiko, boika, boyka, bojka, boncho',
            '06-04' => 'strahil, straxil, ctrahil, ctraxil',
            '14-04' => 'martin, martina',
            '18-04' => 'viktor, viktoria, victor, victoria',
            '21-04' => 'zhelio, jelio, jelyo, zhelyo',
            '25-04' => 'mark, marko',
            '28-04' => 'vitali, vitalyi, vitan',
            '01-05' => 'maia, maya, ermena, ermelina, ermenko, tamara',
            '02-05' => 'boris, borisla, borislav, borislava, boryana, boriana, borian',
            '05-05' => 'iren, irin, irina, irena, mira, miroslav, miroslava',
            '06-05' => 'Гергьовден|georgi, gergana, ginka, galia, gancho, galin, galina, genko, genoveva, gergin, gergina, giuro, zhordz, zhoro, joro',
            '09-05' => 'hristofor, christofor',
            '11-05' => 'kiril, metodi, metodii, metody',
            '17-05' => 'alexii, alexy',
            '21-05' => 'Св. Св. Константин и Елена|konstantin, koicho, koycho, kosta, kostadin, kostadina, dinka, dinko, elena, eli, elin, elka, ilona, kuncho, lenko, stamen, stoil, stoyan, stoian, stoyanka, traiko',
            '26-05' => 'karp, karpo',
            '30-05' => 'emil, emilia, emilian, emiliana',
            '04-06' => 'marta',
            '07-06' => 'valeri, valerii, valeria',
            '10-06' => 'antonina',
            '15-06' => 'avgustin, augustin, vitan, vitomir',
            '20-06' => 'biser, bisera, naum',
            '21-06' => 'iavor, iasen',
            '24-06' => 'Еньовден|enio, iasen, encho, enjo, yani, iani, ianislav',
            '28-06' => 'david',
            '29-06' => 'petar, petra, pavlin, pavlina, petrana, peco, petso, petia, petja, polina, kamen, pavel, peio, penka, pencho',
            '30-06' => 'Събор на Светите 12 апостоли|apostol',
            '01-07' => 'kozma, kuzman, dame, damian, krasimir, krasimira, krasina',
            '03-07' => 'anatoli, anatolii, anatoliy, anatoly',
            '06-07' => 'avakum',
            '07-07' => 'nedialko, nedyalko, nedialka, nedyalka, neda, delcho, deljo, delio, nedelcho, dedelia, nedelya, neshka, ianka, yanka, yanko, ianko',
            '11-07' => 'oleg, olga, olia, olya',
            '15-07' => 'vlada, vladimira, vladimir, vitomir, gospodin, gospodinka',
            '16-07' => 'juli, julia, julya, julka, iulia, iuliana, iulian, julian, julianka, youli, youlianka, yulian, iuli, iulia, iuli, iulian, iuliana, djuli, djulia, djuliana, juli, julia, julian, juliana, juliane, julie',
            '17-07' => 'marin, marina, marincho, marinka',
            '20-07' => 'ilia, iliya, ilian, ilyan, iliana, iliyana, ilko, ilka, lilo, ilianka',
            '22-07' => 'magda, magdalena, magdalen, magdalina, magi, magie, magito, magity, magy, manda, meglena, miglena',
            '25-07' => 'Успение на Св. Анна|ana, ani, anitsa, anna',
            '26-07' => 'paraskev, paraskeva, parashkev, parashkeva',
            '29-07' => 'goran, kalin',
            '26-08' => 'Мчц-и Адриан и Наталия|adrian, adriana',
            '30-08' => 'Св. Александър, Йоан и Павел-патриарси Цариградски|aleko,alexander,aleksandar,alexandra,aleksandra',
            '14-09' => 'Кръстовден|Кръстьо, Кръстина, Кръстил, Кръстила, Кръстилена, Кръстена, Кръстан, Кръстана',
            '16-09' => 'Св. Людмила Чешка|Людмил, Людмила',
            '17-09' => 'Вяра, Надежда и Любов|Вяра, Надежда, Любов, София, Софица, Софка, Софиян, Софияна, Люба, Любомир, Любчо, Любомил, Любослав, Любена, Верина, Верослав, Верослава, Нада, Надя, Надка, Надко, Надина',
            '01-10' => 'Покров Богородичен|Анани, Анания',
            '06-10' => 'св. Тома|Тома, Томислав, Томислава',
            '09-10' => 'Св. Ап.Иаков Алфеев. Преп. Андроник и Атанасия|avram',
            '14-10' => 'Петковден|Петко, Петка, Петкан, Петра, Петрана, Петрина, Петрия, Петричка, Петкана, Пенко, Пенка, Параскев, Параскева, Парашкев, Парашкева, Паруш, Кева',
            '26-10' => 'Димитровден|Димитър, Димитрина, Деметра, Димо, Дима, Димка, Димитричка, Димчо, Димана, Драган, Митко, Митка, Митра, Митрана, Мита',
            '30-11' => 'Св. Ап. Андрей Първозвани (Андреевден)|andrej, andrei, andrey',
            '08-11' => 'Събор на Св. Арх.Михаил; (Архангеловден)|angel, angelina, radka, radko',
            '06-12' => 'Никулден|Никола, Николай, Николина, Ненка, Нина, Натали',
            '27-12' => 'Стефановден|stanimir, stanimira,Стефан, Стефана, Стефания, Стефи, Фани, Венцислав, Венцислава, Запрян, Стамен, Стамена, Станимир, Станимира, Стоил, Стоилка, Станка, Станчо, Стоян, Стоянка, Таня'
        );
        
        //Преместваеми именни дни
        $this->movableNamedays = array (
            '39' => "sotir,spas,spasena,spaska,spasuna",
            '63' => "asen,asparyh,krym,asparuh,krum,panayot,panaiot,rumen,rumiana,rymen,rymiana,chavdar,kubrat,kybrat,desislava",
            '7' => "toma",
            '-8' => "lazar,lazarka,lacho,lazo",
            '-7' => "Цветница|aglika,aglyka,bozhura,grozdan,grozdanka,dalia,dalya,deliya,delia,zhasmin,zhasmina,zyumbyul,ziumbulka,iva,lora,malina,kalina,lili,lila,lilia,lora,liuliana,magnolia,petunia,ruzha,reneta,kitka,fidan,fidanka,tsvetoslav,jagoda,jasmina,karamfil,karamfilka,ventsislav,ventsislava,violeta,varban,girgin,delian,deliana,dilian,diliana,deian,zdravko,zdravka,latinka,liliana,margarita,nevena,ralitsa,roza,rosen,rositsa,trendafil,tsveta,tsvetan,tsvetanka,tsviatko,tseno,tsonko,javor,iavor,iasen,jasen",
            '-43' => "Тодоровден|todor,teodor,todora,teodora,totio,totjo,totyo,bozhil,bojil,bozhidar,bojidar,bojidara,bojan,bozhan,bozhidar,bozhidara,bozhko,bojko",
            '0' => "velika,velichka,velichko,velko,svetla,svetlozar,svetlozara"
        );
        
        // Ислямски имена
        $this->islamicNames = "mohamad,mohamed,ahmed,ahmet,aidan,aihan,ailin,aishe,aiten,ajda,alan,aldin,alkin,alper,altan,aly,amet,amir,amie,amy,anan,arif,asan,aydan,aydin,ayhan,aylia,aylin,aynur,aysel,ayshe,aysun,ayten,aytsan,aza,bahar,bahri,bahtiar,basri,bayram,behchet,beihan,bekir,belgin,belin,berkan,beyhan,bilgin,birsen,bjulent,djuneyt,edis,ediz,emel,emin,emine,emrah,emre,engin,enis,enver,eray,erdal,erdian,erdinch,erdoan,ergin,erhan,erian,erkan,erol,ersin,ertsan,ervin,eser,esin,fatime,fatme,felix,ferad,ferdi,ferhat,feri,fikret,gjunay,gjursel,gunay,gune,gursel,guz,gyoksel,habib,hachik,halil,halim,hamdi,hasan,hava,hjusein,husein,iamal,iames,ibrahim,ibraim,ibriam,ilhan,ilker,ilknur,imali,ismail,ismet,juksel,jusein,jusuf,kadir,kadri,kadrie,kemal,kenan,mahalia,mahmud,medjit,mehmed,mehmet,meliha,mersin,mertan,mertsedes,mesut,metin,mexmed,mjumjun,mohamad,mohamed,muharem,mumun,murad,murat,muri,musa,musi,mustafa,musti,muzafer,nazif,nazmi,nedret,neri,nermin,nesrin,nevzat,nihat,niksan,niksi,nuray,nurhan,nuri,nursel,nurten,omar,omer,onur,orhan,osman,rafet,ramadan,rasim,redjeb,redjep,reihan,remzi,reyhan,rufus,sabri,sadam,sali,salih,salim,samir,sarkis,sedat,sedef,sega,seher,seliahtin,selim,selin,selvi,semi,semiha,semra,serkan,sevgin,sevgul,seyhan,sezay,sezen,sezer,sezgin,shener,shengjul,sjuleyman,suleiman,suleyman,sunay,suray,tuntsay,turgay,turhan,tyler,yildiz,yilmaz,zamfir";
        
        // Женски имена
        $this->womenNames = "ailin,aishe,aiten,ajda,amie,amy,aylia,aylin,aynur,aysel,ayshe,ayten,emel,emine,fatime,fatme,gjursel,guz,kadrie,meliha,semiha,adela,adelina,adriana,albena,aleksandra,aleksandrina,alexandra,amy,ana,anelia,aneta,angelina,angelova,ani,anita,anjela,antoaneta,antonia,any,asia,atanaska,avril,baby,betina,biliana,blaga,blagovestina,boiana,boriana,borislava,boyka,broke,daiana,dana,dani,daniela,dany,dari,darina,deana,deliana,deni,denitsa,denka,desi,desislava,desita,detelina,diana,dida,didi,didka,diliana,dima,dimi,dimitria,dimitrina,dimitrinka,dimitrova,doni,donka,dora,doroteia,ekaterina,elena,eleonora,eli,elina,elis,elisaveta,elitsa,elizabet,emanuela,emi,emilia,eva,evelina,evgenia,fani,fikrie,gabi,gabriela,galia,galina,georgieva,gergana,geri,gery,ginka,hopa,hris,hrisi,hristiana,hristina,hristova,iana,ianeta,ianitsa,iliana,ina,ioana,iordanka,ira,irena,irina,iskra,iva,ivana,ivanina,ivanka,ivelina,ivka,jeni,jenia,jeny,jivka,joana,jordanka,julia,juliana,julieta,julito,kali,kalina,kaloiana,kamelia,karina,karolina,katerina,kateto,kati,katia,katina,katrin,klavdia,kostadinka,koteto,krasimira,kremena,kremi,krisi,kristiana,kristina,kunka,lia,lidia,lili,lilia,liliana,lily,lina,linda,ljubomira,ljudmila,lora,lubka,lubomira,lusi,magdalena,magi,maia,malvina,marchela,margarita,maria,mariana,mariela,marieta,marina,marinela,martina,mary,megi,mery,miglena,mihaela,mila,milena,militsa,milka,mimeto,mimi,mina,mira,mirela,mirena,miroslava,moni,monika,mumu,nadejda,nadezhda,nadia,nana,natali,natalia,nedelina,neli,nely,nevena,nia,nikol,nikoleta,nikolina,nikolinka,nikolova,nina,nora,ofelia,olga,ornela,paolina,paulina,pavlina,penka,pepa,petia,petkova,petrova,plamena,plami,poli,polia,polina,preslava,radina,radka,radoslava,radost,radostina,raia,raina,rali,ralitsa,rayna,reneta,reni,romina,rosi,rositsa,rozalina,rumi,rumiana,sabina,sara,sasha,sashka,sevda,sevdalina,severina,sexy,siana,silvana,silvia,simona,sirma,sisa,sisi,sladkoto,slavina,sneia,sneiana,sneji,sofia,sonia,stanimira,stanislava,stefani,stefi,stefka,stela,steliana,stiliana,svetla,svetlana,svetlozara,svetoslava,svety,svilena,talia,tamara,tania,tatiana,tedi,tedy,temenujka,teodora,tereza,tina,todorova,traiana,tsveta,tsvetana,tsvetanka,tsvetelina,tsveti,tsvetina,tsvetinka,tsvetomira,valentina,valeria,valia,vanesa,vania,vasilena,vasilka,velichka,velina,velislava,venera,veneta,veni,ventsislava,vera,veronika,veronitsa,vesela,veselina,veselka,vesi,viara,victoria,viki,vikitoria,viktoria,vili,violeta,violina,yoana,yordanka,zdravka,zlatina,zlatka,zoia,zori,zornitsa,zuzi,zvezdelina";
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
}

