<?php



/**
 * Клас 'drdata_Address'
 *
 * функции за работа с адреси
 *
 *
 * @category  bgerp
 * @package   bglocal
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bglocal_Address extends core_MVC
{
    static $places = array (
        "aitos" => "Айтос",
        "alfatar" => "Алфатар",
        "alphatar" => "Алфатар",
        "arbanasi" => "Арбанаси",
        "arbanassi" => "Арбанаси",
        "asenovgrad" => "Асеновград",
        "aytos" => "Айтос",
        "b slatina" => "Бяла слатина",
        "balchik" => "Балчик",
        "bansko" => "Банско",
        "belene" => "Белене",
        "belogradchik" => "Белоградчик",
        "berkovica" => "Берковица",
        "berkovitsa" => "Берковица",
        "biala slatina" => "Бяла слатина",
        "biala" => "Бяла",
        "blagoevgrad" => "Благоевград",
        "botevgrad" => "Ботевград",
        "bourgas" => "Бургас",
        "bqla slatina" => "Бяла слатина",
        "bs" => "Бургас",
        "burgas" => "Бургас",
        "byala slatina" => "Бяла слатина",
        "byala" => "Бяла",
        "chepelare" => "Чепеларе",
        "cherven briag" => "Червен бряг",
        "cherven bryag" => "Червен бряг",
        "chiprovtsi" => "Чипровци",
        "chirpan" => "Чирпан",
        "devin" => "Девин",
        "dimitrovgrad" => "Димитровград",
        "dobrich" => "Добрич",
        "dryanovo" => "Дряново",
        "dupnica" => "Дупница",
        "dupnitsa" => "Дупница",
        "dzhebel" => "Джебел",
    	"dolna oryahovitsa" => "Долна Оряховица",
    	"d oryahovitsa" => "Долна Оряховица",
    	"debelets" => "Дебелец",
        "elena" => "Елена",
        "elhovo" => "Елхово",
        "etropole" => "Етрополе",
        "g delchev" => "Гоце Делчев",
        "g oriahovitsa" => "Г. Оряховица",
        "g oryahovitsa" => "Г. Оряховица",
        "g toshevo" => "Генерал Тошево",
        "gabrovo " => "Габрово",
        "gen toshevo" => "Генерал Тошево",
        "general toshevo" => "Генерал Тошево",
        "glavnica" => "Главница",
        "glavnitsa" => "Главница",
        "glavniza" => "Главница",
    	"gabrovo" => "Габрово",
        "goce delchev" => "Гоце Делчев",
        "gorna oriahovitsa" => "Г. Оряховица",
        "gorna oryahovitsa" => "Г. Оряховица",
        "gotse delchev" => "Гоце Делчев",
        "hackovo" => "Хасково",
        "harmanli" => "Харманли",
        "haskovo" => "Хасково",
        "iakoruda" => "Якоруда",
        "ihtiman" => "Ихтиман",
        "isperih" => "Исперих",
        "ivailovgrad" => "Ивайловград",
        "ivaylovgrad" => "Ивайловград",
        "jakoruda" => "Якоруда",
        "jambol" => "Ямбол",
        "kameno" => "Камено",
        "kardjali" => "Кърджали",
        "kardzhali" => "Кърджали",
        "karlovo" => "Карлово",
        "karnobat" => "Карнобат",
        "kavarna" => "Каварна",
        "kazanlak" => "Казанлък",
        "kn" => "Казанлък",
        "kneja" => "Кнежа",
        "koprivshtitsa" => "Копривщица",
        "kostinbrod" => "Костинброд",
        "kotel" => "Котел",
    	"kozludui" => "Козлoдуй",
    	"kozluduy" => "Козлoдуй",
        "kozlodui" => "Козлoдуй",
        "kozloduy" => "Козлoдуй",
        "krumovgrad" => "Крумовград",
        "kubrat" => "Кубрат",
        "kula" => "Кула",
        "kustendil" => "Кюстендил",
        "kyustendil" => "Кюстендил",
        "levski" => "Левски",
        "liaskovets" => "Лясковец",
        "lom" => "Лом",
        "london" => "Лондон",
        "lovech" => "Ловеч",
        "lukovit" => "Луковит",
        "lyaskovets" => "Лясковец",
        "madan" => "Мадан",
        "madrid" => "Мадрид",
        "mezdra" => "Мездра",
        "montana" => "Монтана",
        "n zagora" => "Нова загора",
        "nesebar" => "Несебър",
        "nesebur" => "Несебър",
        "nova zagora" => "Нова загора",
        "oriahovo" => "Оряхово",
        "oryahovo" => "Оряхово",
    	"omurtag" => "Омуртаг",
        "p trambesh" => "П. Трамбеш",
        "panagyurishte" => "Панагюрище",
        "paris" => "Париж",
        "parvomai" => "Първомай",
        "parvomaj" => "Първомай",
        "parvomay" => "Първомай",
        "pavlikeni" => "Павликени",
        "pazardjik" => "Пазарджик",
        "pazardzhik" => "Пазарджик",
        "pernik" => "Перник",
        "peshtera" => "Пещера",
        "petrich" => "Петрич",
        "pirdop" => "Пирдоп",
        "pld" => "Пловдив",
        "pleven" => "Плевен",
        "plovdiv" => "Пловдив",
        "polski trambesh" => "П. Трамбеш",
        "pomorie" => "Поморие",
        "popovo" => "Попово",
        "preslav" => "Велики Преслав",
        "pz" => "Пазарджик",
        "radnevo" => "Раднево",
        "radomir" => "Радомир",
        "rakovski" => "Раковски",
        "razgrad" => "Разград",
        "razlog" => "Разлог",
        "roman" => "Роман",
        "rousse" => "Русе",
        "rs" => "Русе",
        "ruse" => "Русе",
        "russe" => "Русе",
        "samokov" => "Самоков",
        "sandanski" => "Сандански",
        "sf" => "София",
        "shoumen" => "Шумен",
        "shumen" => "Шумен",
        "silistra" => "Силистра",
        "simitli" => "Симитли",
        "sliven" => "Сливен",
        "smolian" => "Смолян",
        "smolyan" => "Смолян",
        "sofia" => "София",
        "sofiq" => "София",
        "sofya" => "София",
        "sopot" => "Сопот",
    	"sevlievo" => "Севлиево",
        "sozopol" => "Созопол",
        "st zagora" => "Ст. Загора",
        "stara zagora" => "Ст. Загора",
        "straldzha" => "Стралджа",
        "stralja" => "Стралджа",
    	"strajitsa" => "Стражица",
        "stz" => "Ст. Загора",
        "svilengrad" => "Свиленград",
        "svishtov" => "Свищов",
        "svoge" => "Своге",
        "sylistra" => "Силистра",
        "targovishte" => "Търговище",
        "tervel" => "Тервел",
        "teteven" => "Тетевен",
        "troian" => "Троян",
        "troyan" => "Троян",
        "tsarevo" => "Царево",
        "tutrakan" => "Тутракан",
        "tvarditsa" => "Твърдица",
        "v preslav" => "Велики преслав",
        "v tarnovo" => "В. Търново",
        "v tyrnovo" => "В. Търново",
        "tarnovo" => "В. Търново",
        "tyrnovo" => "В. Търново",
        "valencia" => "Валенсия",
        "varna" => "Варна",
        "veliki preslav" => "Велики преслав",
        "veliko tarnovo" => "В. Търново",
        "veliko turnovo" => "В. Търново",
        "veliko tyrnovo" => "В. Търново",
        "velingrad" => "Велинград",
        "vidin" => "Видин",
        "vn" => "Варна",
        "vraca" => "Враца",
        "vratsa" => "Враца",
        "vratza" => "Враца",
        "vt" => "В. Търново",
        "xackovo" => "Хасково",
        "xaskovo" => "Хасково",
        "yakoruda" => "Якоруда",
        "yambol" => "Ямбол",
        "zarevo" => "Царево",
        "zlatica" => "Златица",
        "zlatitsa" => "Златица",
        "zlatograd" => "Златоград",
    );
    
    
    /**
     * Връща добре форматирано име на бг населено място
     */
    static function canonizePlace($place)
    {
        $place = trim($place);
        
        $placeL = strtolower(STR::utf2ascii($place));
        $placeL = trim(preg_replace('/[^a-zа-я]+/u', ' ', $placeL));
        $placeL = str_replace("gr ", "", $placeL);
        
        return self::$places[$placeL] ? self::$places[$placeL] : $place;
    }
    
    
    /**
     * Тестов екшън
     */
    function act_Test()
    {
    	$form = cls::get('core_Form');
        
        $form->FNC('text' , 'text' , 'caption=Текст,input');
        
        $form->input();
        
        if($form->isSubmitted()) {
            $this->extractContact($form->rec->text);
        }
        
        $form->title = 'Test';
        
        $form->toolbar->addSbBtn('Изпрати');
        
        return $form->renderHtml();
    }
    
    
    /**
     * Тестов екшън
     */
    function act_Test2()
    {
        $query = email_Incomings::getQuery();
        
        $query->limit(100);
        
        // $query->where("#id = 76");
        
        set_time_limit(100);
        
        $richText = cls::get('type_Richtext');
        
        while($rec = $query->fetch()) {
            
            //Емулираме текстов режим
            Mode::push('text', 'plain');
            
            //TODO променено от richtext2text
            $res = $this->extractContact($richText->toVerbal($rec->textPart));
            
            //Връщаме старата стойност на text
            Mode::pop('text');
            
            $html .= '<hr>' . $rec->id . "<br>";
            
            if(count($res)) {
                foreach($res as $key => $lines) {
                    $html .= "<li> $key </li>";
                    $html .= "<ol>";
                    
                    arsort($lines);
                    
                    foreach($lines as $l => $v) {
                        $html .= "<li> $l </li>";
                    }
                    
                    $html .= "</ol>";
                }
            }
        }
        
        return $html;
    }
    
    
    /**
     * Извличаме контакти
     */
    function extractContact($text)
    {
        // Зареждаме необходимите масиви
        static $regards, $companyTypes, $companyWords, $givenNames;
        
        if(empty($regards)) {
            $regards = getFileContent('bglocal/data/regards.txt');
        }
        
        if(empty($companyTypes)) {
            $companyTypes = getFileContent('bglocal/data/companyTypes.txt');
        }
        
        if(empty($companyWords)) {
            $companyWords = getFileContent('bglocal/data/companyWords.txt');
        }
        
        if(empty($givenNames)) {
            $givenNames = getFileContent('bglocal/data/givenNames.txt');
        }
        
        $div = array('@NO_DIV@', '|');
        
        // Правим масив с линиите, които имат някакъв текст
        $textLinesRow = explode("\n", $text);
        
        foreach($textLinesRow as $l) {
            $l = trim($l);
            
            if($l) {
                foreach($div as $d) {
                    $lArr = explode($d, $l);
                    
                    if(count($lArr) < 4) {
                        foreach($lArr as $lPart) {
                            $lPart = trim($lPart);
                            
                            if(strlen($lPart) > 4) {
                                $lines[$d][] = $lPart;
                            }
                        }
                    }
                }
            }
        }
        
        // Обикаляме всички линии и се опитваме от всяка една от тях да извлечем информация
        foreach($div as $d) {
            if(count($lines[$d])) {
                foreach($lines[$d] as $i => $L) {
                    if($L == 'Links:') break;
                    $this->extractContactData($L, $i, $result);
                }
            }
        }
        
        if(count($result)) {
            foreach($result as $key => $lines) {
                if($key == 'maxIndex') continue;
                
                foreach($lines as $l => $arr) {
                    expect(is_array($arr), $l, $arr);
                    $v = $this->calcMax($arr);
                    
                    if($v > 45) {
                        $res[$key][$l] = $v;
                    }
                }
            }
        }
        
        if(count($res['company'])) {
            foreach($res['company'] as $c => $i) {
                if(strpos($c, '@') || strpos($c, '>') || preg_match("/(many thanks|best wishes|regard|regards|pozdrav|pozdravi|поздрав|поздрави|поздрави|с уважение|пожелани|довиждане)/ui", $c)) {
                    unset($res['company'][$c]);
                }
            }
        }
        
        if(count($res['tel'])) {
            foreach($res['tel'] as $l => $cnt) {
                preg_match("/\b(t|p|phon|fon|tel|telefon|telephon|direct|switch)[^0-9\(\+]{0,3}([\d\- \(\)\.\+\/]{0,28}[\d|\)])/", strtolower(str::utf2ascii($l)), $m);
                $tel = trim($m[2]);
                $res['tel'][$tel] = $res['tel'][$l];
                
                if($l != $tel) {
                    unset($res['tel'][$l]);
                }
            }
        }
        
        if(count($res['fax'])) {
            foreach($res['fax'] as $l => $cnt) {
                preg_match("/\b(f|telefax|fax)[^0-9\(\+]{0,3}([\d\- \(\)\.\+\/]{8,28}[\d|\)])/", strtolower(str::utf2ascii($l)), $m);
                $fax = trim($m[2]);
                $res['fax'][$fax] = $res['fax'][$l];
                
                if($l != $fax) {
                    unset($res['fax'][$l]);
                }
            }
        }
        
        return $res;
    }
    
    
    /**
     * Опитва се да извлече данни и да ги сложи в резултата
     * $res['name'][] , $res['name']['maxIndex']
     */
    function extractContactData($line, $id, &$res)
    {
        // Зареждаме необходимите масиви
        static $regards, $companyTypes, $companyWords, $givenNames;
        
        if(empty($companyTypes)) {
            $companyTypes = getFileContent('bglocal/data/companyTypes.txt');
        }
        
        if(empty($companyWords)) {
            $companyWords = getFileContent('bglocal/data/companyWords.txt');
        }
        
        if(empty($givenNames)) {
            $givenNames = getFileContent('bglocal/data/givenNames.txt');
        }
        
        if(strpos(trim($line), '>') === 0) return;
        
        $lat = str::utf2ascii($line);
        
        $l = mb_strtolower($lat);
        
        $words = explode(' ', $l);
        
        foreach(array('name', 'regards', 'position') as $key) {
            if(isset($res['maxIndex'][$key])) {
                $res['maxIndex'][$key]++;
            } else {
                $res['maxIndex'][$key] = 999999999;
            }
        }
        
        $isTitleCase = preg_match("/(\b[A-Z][a-z]{2,18}\b)/u", $lat);
        $isUpperCase = preg_match("/(\b[A-Z]{2,24}\b)/u", $lat);
        $isLowerCase = preg_match("/(\b[a-z]{2,24}\b)/u", $lat);
        $isMixedCase = preg_match("/(\b[A-Za-z]+([a-z][A-Z]|[A-Z][a-z])[A-Za-z]*\b)/u", $lat);
        $isOnlyWords = preg_match("/(^[a-zA-Z \.\,\']{2,}$)/u", $lat);
        $wordsCnt    = count($words);
        
        if($wordsCnt < 8) {
            $cnt = 0;
            
            foreach($words as $w) {
                if(strpos($companyTypes, "|$w|") !== FALSE) {
                    $cnt++;
                }
                
                if($cnt) {
                    $res['company'][$line][] = 60 + $cnt;
                    $res['company'][$line][] = ($res['maxIndex']['regards'] < 5) ? 10 : 0;
                    $res['maxIndex']['company'] = 1;
                    $res['name'][$line][] = -20;
                }
            }
        }
        
        // Поздрав
        if(($wordsCnt < 4) && preg_match("/(many thanks|best wishes|regard|regards|pozdrav|pozdravi|поздрав|поздрави|поздрави|с уважение|пожелани|довиждане)/ui", $line)) {
            $res['regards'][$line][] = 70;
            $res['maxIndex']['regards'] = 1;
            $res['name'][$line][] = -60;
        }
        
        if($res['maxIndex']['regards'] > 20 || $res['maxIndex']['regards'] == 1) {
            $res['name'][$line][] = -20;
            $res['position'][$line][] = -20;
            $res['web'][$line][] = -20;
            $res['tel'][$line][] = -20;
            $res['fax'][$line][] = -20;
            $res['mob'][$line][] = -20;
            $res['company'][$line][] = -20;
        }
        
        if(($wordsCnt < 4) && preg_match("/(dear|скъп|здравей)/ui", $line)) {
            $res['name'][$line][] = -60;
        }
        
        // Има ли фамилно име
        if(($wordsCnt < 4) && (($res['maxIndex']['company'] != 1) && ((count($words) < 3) || ($res['maxIndex']['regards'] < 5)))) {
            if($isOnlyWords && preg_match("/.*(ova|eva|ska|ski|ov|ev)\b.*/", $l)) {
                $res['name'][$line][] = 60;
                $res['maxIndex']['name'] = 1;
                $res['name'][$line][] = ($res['maxIndex']['regards'] < 5) ? 10 : 0;
                $res['name'][$line][] = -10;
            } else {
                $cnt = 0;
                
                foreach($words as $w) {
                    if(strpos($givenNames, "|$w|") !== FALSE) {
                        $cnt++;
                    }
                }
                
                if($cnt) {
                    $res['name'][$line][] = $cnt * 50 + $wordsCnt;
                    $res['name'][$line][] = ($res['maxIndex']['regards'] < 5) ? 10 : 0;
                    $res['maxIndex']['name'] = 1;
                    $res['name'][$line][] = $isOnlyWords ? 2 : - 90;
                    $res['company'][$line][] = -10;
                }
            }
        }
        
        // Позиция
        if(preg_match("/(strategy|projects|purchaser|accountancy|design|sales|services|" .
                "purchasing|department|broker|secretary|agent|агент|assistant|key account|sales|" .
                "marketing|направление|operation|assistenz|търговски|експорт|импорт|логистика|dep\." .
                "|depart\.|manager|buyer|Direktorius|officer|support|обслужване|managing|executive|изпълнителен|" .
                "директор|отдел|department|изпълнителен|управител|специалист|мениджър|отдел|Корпоративни Клиенти)/ui", $line)) {
            $res['position'][$line][] = ($res['maxIndex']['name'] < 3) ? 10 : 5;
            $res['position'][$line][] =  45;
            
            if($wordsCnt == 1 || $wordsCnt == 2) {
                $res['position'][$line][] = 15;
            } elseif ($wordsCnt == 3 || $wordsCnt == 4) {
                $res['position'][$line][] = 5;
            } else {
                $res['position'][$line][] = -5;
            }
            
            $res['name'][$line][] = -20;
            
            $res['maxIndex']['position'] = 1;
        }
        
        // Предположение за име, само според позицията и кейса на буквите
        if(($res['maxIndex']['regards'] < 4) && ($res['maxIndex']['position'] > 2) && ($wordsCnt>0) && ($wordsCnt <= 4)) {
            $res['name'][$line][] = 40 + $wordsCnt;
            $res['name'][$line][] = $res['maxIndex']['regards'] < 5 ? 5 : 0;
            $res['name'][$line][] = ($isOnlyWords && $isTitleCase && !$isLowerCase && !$isUpperCase && !$isMixedCase) ? 5 : -20;
            $res['name'][$line][] = $isOnlyWords ? 2 : - 90;
            $res['maxIndex']['name'] = 1;
        }
        
        if(($wordsCnt >= 2) && ($wordsCnt <= 4) && ($isUpperCase || $isMixedCase || $isTitleCase) &&
            ($res['maxIndex']['regards'] < 10)) {
            $cnt = 0;
            
            foreach($words as $w) {
                if(strpos($companyWords, "|$w|") !== FALSE) {
                    $cnt++;
                }
            }
            
            if($cnt) {
                $res['company'][$line][] = ($isMixedCase || (!$isOnlyWords)) ? 5 : 0;
                $res['company'][$line][] =  50 + 3 * $cnt;
                $res['maxIndex']['company'] = 1;
                $res['name'][$line][] = -20;
            }
        }
        
        if(preg_match("/([\d\- \(\)\.]{8,18}\d)/", $l)) {
            
            // Дали това прилича на телефон 
            
            if(preg_match("/\b(t|p|phon|fon|tel|telefon|telephon|direct|switch)[^0-9\(\+]{0,3}([\d\- ()\.\+\/]{0,28}\d)/", $l, $m)) {
                $res['tel'][$line][] = 60;
                $res['tel'][$line][] = $res['maxIndex']['regards'] < 13 ? 5 : 0;
                $res['maxIndex']['tel'] = 1;
            }
            
            // Дали това прилича на fax
            if(preg_match("/\b(f|telefax|fax)[^0-9\(\+]{0,3}([\d\- \(\)\.\+\/]{8,28}\d)/", $l)) {
                $res['fax'][$line][] =  60;
                $res['fax'][$line][] = $res['maxIndex']['regards'] < 13 ? 5 : 0;
                $res['maxIndex']['fax'] = 1;
            }
            
            // Дали това прилича на GSM
            if(preg_match("/^(m|gsm|handy).*([\d\- \(\)\.]{8,18}\d)/", $l)) {
                $res['mob'][$line][] = 60;
                $res['mob'][$line][] = $res['maxIndex']['regards'] < 13 ? 5 : 0;
                $res['maxIndex']['mob'] = 1;
            }
        }
        
        if(preg_match("/([\s]*)([_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*([ ]+|)@([ ]+|)([a-zA-Z0-9-]+\.)+([a-zA-Z]{2,}))([\s]*)/i", $l)) {
            $res['email'][$line][] = 60;
            $res['email'][$line][] = $res['maxIndex']['regards'] < 15 ? 5 : 0;
            $res['maxIndex']['email'] = 1;
        }
        
        if(preg_match("/((http(s?):\/\/)|(www\.))([\w\.\/\&\=\?\%\(\)\;:~#\+{}-]+)/i", $l)) {
            $res['web'][$line][] = 60;
            $res['web'][$line][] = $res['maxIndex']['regards'] < 15 ? 5 : 0;
            $res['maxIndex']['web'] = 1;
        }
    }
    
    
    /**
     * Калкулира максималната вероятност от дадения масив, като ако има повече вероятности, то максималната се увеличава
     */
    function calcMax($arr)
    {
        if(!count($arr)) return 0;
        
        $s = 1;
        
        foreach($arr as $a) {
            $s = $s * (1 - $a / 100);
        }
        
        if($s > 1) $s = 1;
        
        $s = round(100 * (1-$s));
        
        return $s;
    }
    
    
    /**
     * Извличаме данни
     */
    function extractContactData1($text, $email = NULL, $country = NULL) {
    }
}