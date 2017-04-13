<?php



/**
 * Клас 'drdata_Address' функции за работа с адреси
 *
 *
 * @category  vendors
 * @package   drdata
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class drdata_Address extends core_MVC
{
    static $regards, $companyTypes, $companyWords, $givenNames, $addresses, $titles, $noStart;

    /**
     * Конвертира дадения текст към масив от не-празни, тримнати линии
     */
    static function textToLines($text)
    {
        $res = array();
        
        $text = str_replace(array("\r\n", "\n\r", "\r"), array("\n", "\n", "\n"), trim($text));
        
        $lines = explode("\n", $text);

        foreach($lines as $l) {
            if($l = trim($l)) {
                $res[] = $l;
            }
        }

        return $res;
    }


    /**
     * Разбива линия на думи, разделени с интервали
     */
    static function lineToWords($line)
    {   
        $line = str_replace(array('|', ',', '-'), array(' ', ' ', ' '), $line);
        $line = str_replace(array(',', '(', ')'), array(', ', ' (', ') '), $line);

        $words = explode(' ', $line);

        foreach($words as $w) {
            if($w = trim($w, " \t.;'\"-*!:/\\")) {
                $res[] = $w;
            }
        }

        return $res;
    }

    
    /**
     * Извлича телефонни номера
     */
    static function extractTelNumbers($line, $negativeList = array())
    {
        preg_match_all("/[^a-zA-Z]([\d\(\+][\d\- \,\(\)\.\+\/]{7,27}[\d\)])/", " {$line} ", $matches);
        
        $res = array();

        if(is_array($matches[1])) {

            foreach($matches[1] as $id => $tel) {
                if(!$negativeList[$tel]) {
                    $res[$id] = $tel;
                }
            } 
            
            return self::filterTel($res);
        }

       
    }
    
    
    /**
     * Извлича факсови номера
     */
    static function extractFaxNumbers($line)
    {
        preg_match_all('/\b(f|telefax|fax|faks)[^0-9\(\+]{0,6}([\d\(\+][\d\- \(\)\.\+\/]{7,27}[\d\)])/',  '-' . $line, $matches);

        return self::filterTel($matches[2]);
    }
    

    /**
     * Извлича мобилни номера
     */
    static function extractMobNumbers($line)
    {
        preg_match_all("/\b(m|gsm|mobile|mtel|mobiltel|vivacom|vivatel|globul|mob)[^0-9\(\+]{0,4}([\d\(\+][\d\- \,\(\)\.\+\/]{7,27}[\d\)])/", $line, $matches);
        
        return self::filterTel($matches[2]);
    }


    
    /**
     * Филтрира телефонните номера, като ги канонизира
     */
    static function filterTel($arr)
    {   
  
        $Phones = cls::get('drdata_Phones');
        
        $ret = array();
 
        foreach($arr as $id => $tel) {
            
            // Ако имаме точно две години с тере - или наклонена черта - не става
            if(preg_match('/^(19|20)[0-9]{2}\s*[\/\-\\\]\s*(19|20)[0-9]{2}$/i', $tel)) {
                continue;
            }

            $res = $Phones->parseTel($tel, '359');


            foreach($res as $telInfo) {
                if($telInfo->area && $telInfo->area != 'Unknown') {
                    $ret[$tel] .= ($ret[$id] ? ', ' : '' ) . '+' . $telInfo->countryCode . ' ' . $telInfo->areaCode . ' ' . trim($telInfo->number);
                };
            }
        }
        
        return $ret;
    }



    /**
     * Извлича данните за контакт от даден текст
     * 
     * @param $text string текста за конвертиране
     * @param $assumed array масив с предварително очаквани данни за контрагента
     * @param $avoid array масив с данни за контрагенти, които не би трябвало да са сред извлечените
     */
    static function extractContact1($text, $assumed = array(), $avoid = array())
    {
        // Добавяме стринговете, които се избягват в адреса от конфигурационните данни
        $conf = core_Packs::getConfig('drdata');
        if($avoidLines = $conf->DRDATA_AVOID_IN_EXT_ADDRESS) {
            $avoidLines = explode("\n", $avoidLines);
            foreach($avoidLines as $l) {
                $avoid[] = trim($l, "\r");
            }
        }


        // Какви неща ще откриваме?
        // $obj->regards - Поздрав
        // $obj->company - Компания
        // $obj->person - Лице
        // $obj->job - Позиция
        // $obj->country - Държава
        // $obj->place   - Място
        // $obj->code    - Код
        // $obj->address - Адрес
        // $obj->tel     - Телефон
        // $obj->fax     - Факс
        // $obj->web     - Сайт
        // $obj->email   - Имейл


        // Зареждане на масивите
        static $regards, $companyTypes, $companyWords, $givenNames, $addresses, $titles, $regards, $noStart;
        
         
        
        if(empty($givenNames)) {
        	$givenNames = ' ' . getFileContent('drdata/data/givenNames.txt');
        }
        
        if(empty($givenNames)) {
        	$givenNames = ' ' . getFileContent('drdata/data/givenNames.txt');
        }

        if(empty($addresses)) {
        	$addresses = ' ' . getFileContent('drdata/data/addresses.txt');
        }
        
        if(empty($titles)) {
        	$titles = ' ' . getFileContent('drdata/data/titles.txt');
        }
        
        if(empty($regards)) {
        	$regards =  ' ' . str::utf2ascii(getFileContent('drdata/data/regards.txt'));
        }

        // Парсираме по линии
        $lines = self::textToLines($text);

        foreach($lines as $l) {
            $obj = new stdClass();
            
            $obj->line = $l;
            
            $obj->line = preg_replace("/[ \t]+/", ' ', $obj->line);
            $obj->_lineLat = trim(str::utf2ascii($obj->line), ' *');
            $obj->_lineLower = strtolower($obj->_lineLat);

            $obj->_words = self::lineToWords($obj->line);
            $obj->_wordsLat = self::lineToWords($obj->_lineLat);
            $obj->_wordsLower = self::lineToWords($obj->_lineLower);

            preg_match_all("/\b([A-Z][a-z]+)\b/", $obj->_lineLat, $matches);
            $obj->titleCaseCnt = count($matches[1]);
            
            preg_match_all("/\b([A-Z]{2,})\b/", $obj->_lineLat, $matches);
            $obj->upperCaseCnt = count($matches[1]);

            // Проверка за фирми
            self::rateCompany($obj);
            
            // Рейтинг за име на човек
            self::ratePerson($obj);
            
            // Рейтинг за поздрави
            self::rateRegards($obj);
            
            // Ако рейтинга за поздрави е по-голям от 40 и е по-голям от фирмания или персоналния рейтинг, той ги подтиска
            if($obj->regardsRate > 40) {
                $obj->companyRate = round($obj->companyRate / (1 + 2*$obj->regardsRate/100));
                $obj->personRate  = round($obj->personRate  / (1 + 2*$obj->regardsRate/100));
            }

            $obj->fax = self::extractFaxNumbers($obj->_lineLower);
            $obj->mob = self::extractMobNumbers($obj->_lineLower, $obj->fax);
            $obj->tel = self::extractTelNumbers($obj->_lineLower, $obj->fax + $obj->mob);
            $obj->email = type_Email::extractEmails($obj->_lineLower);
            $obj->web = core_Url::extractWebAddress($obj->_lineLower);
            
            // Ако рейтинга за поздрави е по-голям от 40 и е по-голям от фирмания или персоналния рейтинг, той ги подтиска
            if($obj->fax || $obj->mob || $obj->email || $obj->web) {
                $obj->companyRate = 0;
                $obj->personRate  = 0;
            }

            // Проверяваме за начален поздрав
            self::rateGreeting($obj);

            $res[] = $obj;
        }

        $tbl = self::renderTable($res);

       return $tbl;
 
    }


    /**
     * Определяне на рейтинг на текста за име на фирма
     */
    static function rateCompany(&$obj)
    {
        if(empty(self::$companyTypes)) {
        	self::$companyTypes = ' ' . getFileContent('drdata/data/companyTypes.txt');
        }
        
        if(empty(self::$companyWords)) {
        	self::$companyWords = ' ' . getFileContent('drdata/data/companyWords.txt'); 
        }
        
        if(!is_array($obj->_wordsLower)) return;

        foreach($obj->_wordsLower as $w) {
            if(strpos(self::$companyTypes, "|$w|")) {
                $cnt += 3; 
            } elseif(strpos(self::$companyWords, "|$w|")) {
                $cnt += 1;
            }
        }

        if($obj->titleCaseCnt) {
            $cnt += 1;
        }

        if($obj->upperCaseCnt) {
            $cnt += 2;
        }

        if($c = ($obj->upperCaseCnt + $obj->titleCaseCnt)) {
             $cnt *= ($c / count($obj->_wordsLower) + 0.5);
        }

        if(strpos($obj->line, '?')) {
            $cnt -= 2;
        }

        $obj->companyRate += round(min(100, 25 * ($cnt / count($obj->_wordsLower))));
    }


    /**
     * Определя рейтинга дали даден текст е име на човек
     */
    static function ratePerson($obj)
    {

        if(empty(self::$givenNames)) {
        	self::$givenNames = ' ' . getFileContent('drdata/data/givenNames.txt');
        }
        
        if(empty(self::$titles)) {
        	self::$titles = ' ' . getFileContent('drdata/data/titles.txt'); 
        }
        
        if(!is_array($obj->_wordsLower)) return;

        foreach($obj->_wordsLower as $w) {

            if(strpos(self::$givenNames, "|$w|")) { 
                $cnt += 3;
            } elseif(strpos(self::$titles, "|$w|")) {
                $cnt += 2;
            } elseif(strpos($titles, "|$w|")) {
                $cnt += 2;
            } elseif(preg_match("/[a-zA-Z]{2,15}(ov|ova|ev|eva)$/", $w)) {
                $cnt += 2;
            } elseif(preg_match("/^[A-Z][a-z]{0,2}\.$/", $w)) {
                $cnt += 2;
            } elseif(strlen($w) == 1 || strlen($w) > 15) {
                $cnt -= 2;
            }
        }

        if($obj->titleCaseCnt) {
            $cnt += ($obj->titleCaseCnt / count($obj->_wordsLower)) * 1.5;
            $cnt *= ($obj->titleCaseCnt / count($obj->_wordsLower) + 0.5);
        }

        
        $obj->personRate += round(min(100, 25 * ($cnt / count($obj->_wordsLower))));

    }



    /**
     * Определя рейтинга дали даден текст е име на човек
     */
    static function rateRegards($obj)
    {

        if(empty(self::$regards)) {
        	self::$regards = ' ' . getFileContent('drdata/data/regards.txt');
        }
        
        if(!is_array($obj->_wordsLower)) return;

        foreach($obj->_wordsLower as $w) {
            if(strpos(self::$regards, "|$w|")) {  
                $cnt += 2;
            }
        }

        if($obj->titleCaseCnt) {
            $cnt *= 2;
        }

        if(preg_match("/^(sardechni pozdravi|kind regards|best regards|pozdravi|pozdrav|mit freundlichen|saludos)/", $obj->_lineLower) ) { 
            $cnt += 10;  
        }
        
        $obj->regardsRate += round(min(100, 15 * ($cnt / count($obj->_wordsLower))));

    }


    static function rateGreeting($obj)
    {
        if(preg_match("/^(zdravey|zdraveyte|dear|hi|uvazhaemi|hello)/", $obj->_lineLower) ) { 
            $obj->greetingRate = 50;
            $obj->companyRate -= 10;
            if($obj->personRate > 50) {  
                $obj->personRate = round($obj->personRate / 3);
                $obj->companyRate = round($obj->companyRate / 3);
            }
        }

    }


  
    /**
     * Извлича данните за контакт от даден текст
     * 
     * @param $text string текста за конвертиране
     * @param $assumed array масив с предварително очаквани данни за контрагента
     * @param $avoid array масив с данни за контрагенти, които не би трябвало да са сред извлечените
     * 
     * @return stdClass
     */
    function extractContact($text, $assumed = array(), $avoid = array())
    {
        // Добавяме стринговете, които се избягват в адреса от конфигурационните данни
        $conf = core_Packs::getConfig('drdata');
        if($avoidLines = $conf->DRDATA_AVOID_IN_EXT_ADDRESS) {
            $avoidLines = explode("\n", $avoidLines);
            foreach($avoidLines as $l) {
                $avoid[] = trim($l, "\r");
            }
        }
        
        $lines = self::textToLines($text);
        
        if (!$lines) return new stdClass();
        
        static $regards, $companyTypes, $companyWords, $givenNames, $addresses, $titles, $regards, $noStart;
        
         
        if(empty($companyTypes)) {
        	$companyTypes = ' ' . getFileContent('drdata/data/companyTypes.txt');
        }
        
        if(empty($companyWords)) {
        	$companyWords = ' ' . getFileContent('drdata/data/companyWords.txt'); 

        }
        
        if(empty($givenNames)) {
        	$givenNames = ' ' . getFileContent('drdata/data/givenNames.txt');
        }
        
        if(empty($givenNames)) {
        	$givenNames = ' ' . getFileContent('drdata/data/givenNames.txt');
        }

        if(empty($addresses)) {
        	$addresses = ' ' . getFileContent('drdata/data/addresses.txt');
        }
        
        if(empty($titles)) {
        	$titles = ' ' . getFileContent('drdata/data/titles.txt');
        }
        
        if(empty($regards)) {
        	$regards =  ' ' . str::utf2ascii(getFileContent('drdata/data/regards.txt'));
        }

        $expected = array();
        
        $noStart = "/(obyava|de|joboffer|to|from|subject|price|sent|size|quantity|material|tsena|type=mx|date|till|fran|amne|skickat|.+wrote|" .
                    "data|printing|print|description|re|do|cc|delivery|de|qty|handles|objet|age|file|envoye|mail\.bg|sendt|fra|til|emne|type|back|face|ref)/ui";
        
      /*  if(preg_match("/(strategy|projects|purchaser|accountancy|design|sales|services|" .
                "purchasing|department|broker|secretary|agent|агент|assistant|key account|sales|" . 
                "marketing|направление|operation|assistenz|търговски|експорт|импорт|логистика|dep\." .
                "|depart\.|manager|buyer|Direktorius|officer|support|обслужване|managing|executive|изпълнителен|" .
                "директор|отдел|department|изпълнителен|управител|специалист|мениджър|отдел|Корпоративни Клиенти)/ui", $line)) {*/
        
        $res = array();
        
        foreach($lines as $id => $l) {
            
            $aL = preg_replace("/[ \t]+/", ' ', $aL);
            $aL = trim(str::utf2ascii($l), ' *');
            $lN = strtolower($aL);
            $res[$id] = new drdata_AddrRec($avoid);
            $res[$id]->distance = (strlen($aL) + 20) / 20;

            if($l{0} == '>') {
                $res[$id]->distance *= 2;
                continue;
            }
            
            if($p = strpos($lN, ':')) {
                if($p < 25 && $p >= 2 && ($ind = trim(substr($lN, 0, $p)))) {
                    $ind = trim(strtolower(str::utf2ascii($ind)), ' *');                       
                    if(preg_match($noStart, $ind)) {
                        $res[$id]->distance *= 2;
                        continue;
                    }                         
                }
            }

            $first5 = substr($l, 0, 5);

            if($first5 == '_____' || $first5 == '=====' || $first5 == '-----') {
                $res[$id]->distance *= 2;
                continue;
            }

            $res[$id]->add('fax', $faxArr = self::extractFaxNumbers($lN));
            $res[$id]->add('mob', $mobArr = self::extractMobNumbers($lN));
            $res[$id]->add('tel', $telArr = self::extractTelNumbers($lN));
            $res[$id]->add('email', $emailArr = type_Email::extractEmails($lN));
            $res[$id]->add('web', $webArr = core_Url::extractWebAddress($lN));
            
            $j = 0;
            
            $i = $companyCnt = $nameCnt = $addressCnt = $regardsCnt = 0;

            foreach($telArr as $oVal => $val) {
                $from[$j] = $oVal;
                $to[$j] = '';
                $j++;
                $addressCnt--;
            }

            foreach($faxArr as $oVal => $val) {
                $from[$j] = $oVal;
                $to[$j] = '';
                $j++;
                $addressCnt--;
            }

            foreach($mobArr as $oVal => $val) {
                $from[$j] = $oVal;
                $to[$j] = '';
                $j++;
                $addressCnt--;
            }
            
            foreach($emailArr as $val) {
                $from[$j] = $val;
                $to[$j] = '';
                $j++;
                $addressCnt--;
            }

            $lN = trim(str_ireplace($from, $to, $lN));
            $l  = trim(str_ireplace($from, $to, $l));
            $aL = trim(str_ireplace($from, $to, $aL));


            $words = self::lineToWords($lN);
            //$res[$id]->add('words', $words);
            
            preg_match_all("/\b([A-Z][a-z]+)\b/", $aL, $matches);
            $titleCaseCnt = count($matches[1]);
            
            preg_match_all("/\b([A-Z]{2,})\b/", $aL, $matches);
            $upperCaseCnt = count($matches[1]);
            
            preg_match_all("/\b([0-9]{1,3}[a-zA-Z]{0,3})\b/", $aL, $matches);
            $streetAddrCnt = count($matches[1]);  

            //echo "<li>$titleCaseCnt $upperCaseCnt $aL </li>";

            $wordsCnt = count($words);

            if($wordsCnt > 0 && $wordsCnt <10) {
                                
                foreach($words as $w) {
                    
                    $i++;
                    
                    $strlen = strlen($w);
                    
                    if(strpos($companyTypes, "|$w|")) {
                        $companyCnt += 3;
                    }
                    
                    if(strpos($companyWords, "|$w|")) {
                        // echo "<li>  $w $l";
                        $companyCnt += 1;
                    }
                    
                    if($strlen > 3 && strpos($givenNames, "|$w|")) { 
                        $nameCnt += $strlen > 5 ? 1.5 : 1.2; // echo "<li> $w $l";
                    }

                    if($strlen <= 3 && substr($w, -1) == '.') {
                        $nameCnt += 0.8;
                    }

                    if($strlen <5 && strpos($titles, "|$w|")) {
                        $nameCnt += 1;
                    }
                   
                    if(preg_match("/[a-zA-Z]{2,15}(ov|ova|ev|eva)$/", $w) ) { 
                        $nameCnt += $strlen > 6 ? 0.6 : 0.4; //echo "<li> $w $l";
                    }
                    
                    if(preg_match("/(zdravey|zdraveyte|dear|hi|uvazhaemi|hello)$/", $w) ) { 
                        $nameCnt -= 1;
                    }

                    if(strpos($addresses, "|$w|")) {  
                        $addressCnt += $strlen > 1 ? 1 : 0.2;
                    }
                    // echo "<li> $w";
                    if(strpos($regards, "|$w|")) {  
                        $regardsCnt += $strlen > 6 ? 1.2 : 1;
                    }

                    
                    if(substr($l, -1) == ',' || substr($l, -1) == '!') { // 
                        $regardsCnt += 0.2;
                    }

                }

                

                if($wordsCnt < 3) {
                    $regardsCnt *= 1.3;
                } elseif($wordsCnt < 4) {
                    $regardsCnt *= 1.1;
                }
                
                $addrRatio = $streetAddrCnt / $wordsCnt;

                if($addrRatio <= 0.5 && $addrRatio >= 0.1) {
                    $addressCnt += $streetAddrCnt;
                }
                
                // Ако съдържа числя, вероятността да е име на човек е по-малка.
                if(preg_match("/[0-9]/", $lN)) {
                    $nameCnt -=2;
                }


                // Капитализирани думи и Титлови думи, ако са над 3/4 има вероятност да е фирма
                if($titleCaseCnt && $upperCaseCnt && ($titleCaseCnt + $upperCaseCnt)/$wordsCnt > 0.75 ) {
                    $companyCnt += 0.5;
                    $companyCnt *= 1.2;
                }

                if($upperCaseCnt && ($upperCaseCnt < $wordsCnt) && $wordsCnt < 5) {
                    $companyCnt += 0.5;
                    $companyCnt *= 1.2;
                }
                

                // Знака кавичка се среща по-често в имената на фирмите и адресите, но не се среща в имената на ходата
                if(strpos($l, '"') !== FALSE) {
                    $companyCnt += 0.1;
                    $addressCnt += 0.2;
                    $nameCnt -= 0.2;
                }
                
                // Знакът № се среща предимно в адресите
                if(strpos($l, '№') !== FALSE) {
                    $companyCnt -= 0.1;
                    $nameCnt -= 0.2;
                    $addressCnt += 0.2;
                }


                if($expected['country'] && $wordsCnt < 5) {
                    
                    $country = self::extractCountry($l);
                        
                    if($country) {
                        $res[$id]->add('country', array(trim($country)), 10);
                        $expected['country'] = 0;
                        $nameCnt -= 2.5;
                    }
                    
                }


                if(($r = $regardsCnt/$i) > 0.6) {
                    $res[$id]->add('regards', array(trim($l)), round($r, 2));
                    $expected['name'] = 2;
                    $expected['country'] = 12;
                    
                    $companyCnt -= 3.2;
                    $addressCnt -= 3.2;
                    $nameCnt -= 1.2;
                }
                
                // Ако е линк, намаляме вероятността да е адрес
                if ($webArr) {
                    $addressCnt -= 0.5;
                }

                if(($r = ($companyCnt + 0.03 * $titleCaseCnt + 0.1 * $upperCaseCnt)/(($i == $wordsCnt) ? ($i*($i/10)) : $i) + ($expected['company'] ? 0.2 : 0)) > 0.65) {
                    $res[$id]->add('company', array(trim($l)), round($r, 2));
                    $expected['address'] = 5;
                    $expected['country'] = 10;
                    $expected['company'] = 0;
                    $expected['name'] = 2;
                }


                if(($r = ($nameCnt + 0.05 * $titleCaseCnt + 0.02 * $upperCaseCnt)/$i + ($expected['name'] ? 0.2 : 0) ) > 0.65) {
                    $res[$id]->add('name', array(trim($l)), round($r, 2));
                    $expected['address'] = 4;
                    $expected['country'] = 10;
                    $expected['name'] = 0;
                    $expected['company'] = 2;
                }
                
                if(($r = ($addressCnt)/$i + ($expected['address'] ? 0.2 : 0)) > 0.6) {
                    //echo "<li> $addressCnt + min($streetAddrCnt,2))/$i + {$expected['address']} $l";
                    $res[$id]->add('address', array(trim($l)), round($r, 2));
                }

            }

            
            // Намаляваме с 1 expected
            foreach($expected as $key => $cnt) {
                $expected[$key]--;
                if($expected[$key] < 0) {
                    $expected[$key] = 0;
                }
            }

        }
        
        // Отделяме блоковете с данни
        $blocks = array();
        $i = 1;
        foreach($res as $l) {
            if(!is_array($blocks[$i])) {
                $blocks[$i] = array();
            }

            $data = $l->getData(); 
           
            if(!$data) {
                if(count($blocks[$i])) {
                    $empty += $l->distance;
                }
            } else {
                if($empty > 10) $i++;
                $empty = 0;
                foreach($data as $item) {

                    if(is_array($blocks[$i][$item[0]]) && count($blocks[$i][$item[0]])) {
                        //tel, fax, mob - по-голямото е по-добре
                        if($item[0] == 'tel' || $item[0] == 'tel' || $item[0] == 'tel') {
                            foreach($blocks[$i][$item[0]] as $id => $exValue) {  
                                if(strpos($exValue, $item[1]) !== FALSE) {
                                    unset($item[1]);
                                    break;
                                }
                                if(strpos($item[1], $exValue) !== FALSE) {
                                    unset($blocks[$i][$item[0]][$id]);
                                    break;
                                }
                            }
                        }

                        //email, web по-малкото е по-добре
                        if($item[0] == 'email' || $item[0] == 'web') {
                            foreach($blocks[$i][$item[0]] as $id => $exValue) {

                                if(strpos($exValue, $item[1]) !== FALSE) {
                                    unset($blocks[$i][$item[0]][$id]);
                                    break;
                                }
                                if(strpos($item[1], $exValue) !== FALSE) {
                                    unset($item[1]);
                                    break;
                                }

                            }
                        }
                        
                    }

                    // TODO
                    if($item[0] == 'company' || $item[0] == 'name') {
                        $maxKey = 'max-' . $item[0];

                        if($blocks[$i][$maxKey] < $item[2]) {
                            // $blocks[$i][$item[0]] = array();
                            $blocks[$i][$maxKey] = $item[2];
                        };

                        if($blocks[$i][$maxKey] > $item[2]) {
                            unset($item[1]);
                        };

                    }
                    
                    if($item[1]) {
                        $blocks[$i][$item[0]][] = $item[1];
                    }
                }
            }
        }


        $points = array (
            'company' => 10,
            'name'    => 8,
            'country' => 5,
            'address' => 5,
            'tel'     => 5,
            'fax'     => 7,
            'mob'     => 7,
            'email'   => 5,
            'web'     => 5
             
            );

        // Avoid
        foreach($blocks as $id => $b) {
            $total = 0;
            foreach($points as $name => $score) {
                if(count($b[$name])) {
                    //echo "<li> $name {$b[$name][0]} $total";
                    $total += $score;
                }
            }

            if($total > $maxTotal) {
                $maxBlock = $b;
                $maxTotal = $total;
            }
        }
        
        $res = new stdClass();

        if(is_array($maxBlock) && count($maxBlock)) {
            if(is_array($maxBlock['company']) && count($maxBlock['company'])) {
                $res->company = trim($maxBlock['company'][0], '*;,-#$<> \t\n\r');
            }
            if(is_array($maxBlock['tel']) && count($maxBlock['tel'])) {
                $res->tel = implode(', ', $maxBlock['tel']);
            }
            if(is_array($maxBlock['fax']) && count($maxBlock['fax'])) {
                $res->fax = implode(', ', $maxBlock['fax']);
            }
            if(is_array($maxBlock['email']) && count($maxBlock['email'])) {
                $res->email = implode(', ', $maxBlock['email']);
            }
            if(is_array($maxBlock['address']) && count($maxBlock['address'])) {
                $res->address = $maxBlock['address'][0];
            }
            if(is_array($maxBlock['country']) && count($maxBlock['country'])) {
                $res->country = $maxBlock['country'][0];
            }
            if(is_array($maxBlock['pCode']) && count($maxBlock['pCode'])) {
                $res->pCode = $maxBlock['pCode'][0];
            }
            if(is_array($maxBlock['place']) && count($maxBlock['place'])) {
                $res->place = $maxBlock['place'][0];
            }
            if(is_array($maxBlock['web']) && count($maxBlock['web'])) {
                $res->web = $maxBlock['web'][0];
            }
            if(is_array($maxBlock['name']) && count($maxBlock['name'])) {
                $res->person = trim($maxBlock['name'][0], '*;,-#$<> \t\n\r');
            }
            if(is_array($maxBlock['mob']) && count($maxBlock['mob'])) {
                $res->mob = implode(', ', $maxBlock['mob']);
            }
        }
 
        return $res;
    }
    
    
     
    /**
     * @todo Чака за документация...
     */
    static function extractCountry($text) 
    {
        $regExpr = "/\b(abkhazia|afghanistan|aland|albania|algeria|american samoa|andorra|angola|anguilla|antarctica|antigua and barbuda|argentina|armenia|aruba|ascension|ashmore and cartier islands|australia|australian antarctic territory|austria|azerbaijan|azerbaijan9|bahamas, the|bahrain|baker island|bangladesh|barbados|belarus|belgique|belgium|belgium\\.|belize|benin|bermuda|bhutan|bolivia|bosnia and hercegowina|bosnia and herzegovina|botswana|bouvet island|brazil|british antarctic territory|british indian ocean territory|british sovereign base areas|british virgin islands|brunei|builgaria and uk|bulgaria|burkina faso|burundi|cambodia|cameroon|canada|cape verde|cayman islands|central african republic|ch|chad|chez republic|chile|china|christmas island|clipperton island|cocos islands|colombia|comoros|cook islands|coral sea islands|costa rica|cote d\\'ivoire|creece|croatia|cuba|cyprus|cz|czech  republic|czech rep\\.|czech republic|danmark|denmark|deutschland|djibouti|dominica|dominican republic|dr congo|east timor|ecuador|egypt|el salvador|england|equatorial guinea|eritrea|espana|españa|estona|estonia|ethiopia|falkland islands|faroe islands|fiji|finand|finland|france|francе|french guiana|french polynesia|french southern and antarctic lands|gabon|gambia, the|gemany|georgia|german|germany|ghana|gibraltar|greece|greeece|greek|greenland|greese|grenada|grèce|guadeloupe|guam|guatemala|guernsey|guinea|guinea-bissau|guyana|haiti|heard island and mcdonald islands|hellas|holland|honduras|hong kong|howland island|hungary|iceland|india|indonesia|iraland|iran|iraq|ireland|isle of man|israel|italia|italy|ittaly|jamaica|japan|jarvis island|jersey|johnston atoll|jordan|kazakhstan|kenya|kingman reef|kiribati|kosovo|kuwait|kyrgyzstan|laos|latvia|latvijas|lebanon|lesotho|liberia|libya|liechtenstein|lithuania|lituanie|luxembourg|luxemburg|macau|macedonia|madagascar|makedonija|malawi|malaysia|maldives|mali|mallorca - spain|malta|marseille france|marseillr france|marshall islands|martinique|mauritania|mauritius|mayotte|mexico|micronesia|midway islands|moldova|monaco|mongolia|montenegro|montserrat|morocco|mozambique|myanmar|nagorno-karabakh|namibia|nauru|navassa island|nederland|nederlands|nepal|netherlands|netherlands antilles|new caledonia|new zealand|nicaragua|niger|nigeria|niue|norawy|norfolk island|north ireland|north korea|northern cyprus|northern ireland|northern mariana islands|norvège|norway|oman|pakistan|palau|palestina|palmyra atoll|panama|papua new guinea|paraguay|peru|peter i island|philippines|pitcairn islands|poland|portugal|pridnestrovie|puerto rico|qatar|queen maud land|republic of korea|republic of the congo|reunion|roamnia|romania|ross dependency|roumanie|russia|rwanda|saint barthelemy|saint helena|saint kitts and nevis|saint lucia|saint martin|saint pierre and miquelon|saint vincent and the grenadines|samoa|san marino|sao tome and principe|saudi arabia|scotland|senegal|serbia|sewden|seychelles|sierra leone|singapore|skopje macedonia|slovak republic|slovakia|slovaquie|slovenia|slovenija|slovenska republika|slowakische republik|slowenia|solomon islands|somalia|somaliland|south africa|south georgia and the south sandwich islands|south ossetia|spain|sri lanka|sudan|suriname|svalbard|svitzerland|svizzera|swaziland|sweden|swiss|switerland|switzerland|swizerland|syria|taiwan|tajikistan|tanzania|thailand|the netherlands|togo|tokelau|tonga|trinidad and tobago|tristan da cunha|tunisia|turkey|turkmenistan|turks and caicos islands|tuvalu|u\\.s\\. virgin islands|uganda|uk|uk and spain|ukraine|united arab emirates|united kingdom|united kingdom, england|united kongdom|united states|untited kingdom|uruguay|uzbekistan|vanuatu|vatican city|venezuela|vietnam|wake island|wallis and futuna|western sahara|yemen|yougoslavie|yugoslavia|zambia|zimbabwe|Абхазия|Австралийската антарктическа територия|Австралия|Австрия|Азербайджан|Акротири и Декелия|Аландски острови|Албания|Алжир|Американска Самоа|Американски Вирджински острови|Ангола|Ангуила|Андора|Антарктида|Антигуа и Барбуда|Аржентина|Армения|Аруба|Атол Джонстън|Афганистан|Бангладеш|Барбадос|Бахамските острови|Бахрейн|Беларус|Белгия|Белиз|Бенин|Бермуда|Боливия|Босна и Херцеговина|Ботсуана|Бразилия|Британска антарктическа територия|Британската територия в Индийския океан|Британски Вирджински острови|Бруней|Буркина Фасо|Бурунди|Бутан|България|Вануату|Ватикана|Великобритания|Венецуела|Виетнам|Габон|Гамбия|Гана|Гваделупа|Гватемала|Гвиана|Гвинея|Гвинея-Бисау|Германия|Гибралтар|Гренада|Гренландия|Грузия|Гуам|Гърнси|Гърция|ДР Конго|Дания|Департаментът Джърси|Джибути|Доминика|Доминиканската република|Египет|Еквадор|Екваториална Гвинея|Ел Салвадор|Еритрея|Естония|Етиопия|Замбия|Западна Сахара|Земята на кралица Мод|Зимбабве|Израел|Източен Тимор|Индия|Индонезия|Ирак|Иран|Ирландия|Исландия|Испания|Италия|Йемен|Йордания|Кабо Верде|Казахстан|Каймановите острови|Камбоджа|Камерун|Канада|Катар|Кения|Кингман|Кипър|Киргизстан|Кирибати|Китай|Кокосови острови|Колумбия|Коморските острови|Коралови острови|Косово|Коста Рика|Кот д\\'Ивоар|Куба|Кувейт|Лаос|Латвия|Лесото|Либерия|Либия|Ливан|Литва|Лихтенщайн|Люксембург|Мавритания|Мавриций|Мадагаскар|Майот|Макао|Македония|Малави|Малайзия|Малдивите|Мали|Малта|Мароко|Мартиника|Маршаловите острови|Мексико|Мианмар|Микронезия|Мозамбик|Молдова|Монако|Монголия|Монсерат|Наваса|Нагорни Карабах|Намибия|Науру|Непал|Нигер|Нигерия|Нидерландските Антили|Никарагуа|Ниуе|Нова Зеландия|Нова Каледония|Норвегия|Норфолк|Обединените арабски емирства|Оман|Остров Бейкър|Остров Буве|Остров Възнесение|Остров Джарвис|Остров Клипертон|Остров Ман|Остров Петър I|Остров Рождество|Коледен остров|Остров Рос |Остров Хауланд|Острови Ашмор и Картие|Острови Кук|Острови Питкерн|Пакистан|Палау|Палестина|Палмира|Панама|Папуа-Нова Гвинея|Парагвай|Перу|Полша|Португалия|Приднестровието|Пуерто Рико|Република Конго|Реюнион|Руанда|Румъния|Русия|САЩ|Самоа|Сан Марино|Сао Томе и Принсипи|Саудитска Арабия|Свазиленд|Свалбард|Света Елена|Северен Кипър|Северна Корея|Северни Мариански острови|Сейнт Винсент и Гренадини|Сейнт Китс и Невис|Сейнт Лусия|Сейшелите|Сен Бартелеми|Сен Мартен|Сен Пиер и Микелон|Сенегал|Сиера Леоне|Сингапур|Сирия|Словакия|Словения|Соломоновите острови|Сомалиленд|Сомалия|Среден Атол|Судан|Суринам|Сърбия|Таджикистан|Тайван|Тайланд|Танзания|Того|Токелау|Тонга|Тринидад и Тобаго|Тристан да Куня|Тувалу|Тунис|Туркменистан|Турция|Търкс и Кайкос|Уганда|Уейк|Узбекистан|Украйна|Унгария|Уолис и Футуна|Уругвай|Фарьорските острови|Фиджи|Филипини|Финландия|Фолклендски острови|Франция|Френска Гвиана|Френска Полинезия|Френски южни и антарктически територии|Хаити|Холандия|Хондурас|Хонконг|Хърватия|Хърд и Макдоналд острови|Централноафриканска република|Чад|Черна гора|Чехия|Чили|Швейцария|Швеция|Шри Ланка|Южна Африка|Южна Джорджия и Южни Сандвичеви острови|Южна Корея|Южна Осетия|Ямайка|Япония|македонија|румъния|холандия)\b/ui";

        if (preg_match($regExpr, $text, $matches)) {

            return $matches[1];
        }
    }

    /**
     * Функция, която рендира резултатния масив от обекти
     */
    static function renderTable($arr)
    {
        // Определяне на заглавията
        $headers = array(); 
        foreach($arr as $obj) {
            $names = get_object_vars($obj);
            foreach($names as $n => $v) {
                if(($n{0} != '_') && (!in_array($n, $headers))) {
                    $headers[] = $n;
                }
            }
        }

        // Започваме таблицата
        $res = "\n<table border='1' cellpadding='3' style='border-collapse: collapse;' class='listTable'>";

        // Отпечатваме заглавията
        $res .= "\n<tr>";
        foreach($headers as $h) {
            $res .= "<th style='text-align:left'>{$h}</th>";
        }
        $res .= "</tr>";
        
        
        // Отпечатваме съдържанието
        foreach($arr as $obj) {
            $res .= "\n<tr>";
            foreach($headers as $h) {
                if(is_array($obj->{$h})) {
                    $obj->{$h} = implode(', ', $obj->{$h});
                }
                $res .= "<td>" . ($obj->{$h} ?  $obj->{$h} : '&nbsp;') . "</td>";
            }
            $res .= "</tr>";
        }
        
        // Завършваме таблицата
        $res .= "\n</table>";

        return $res;
    }


    /**
     * Парсира място, като се опитва да извлече държава и код
     * 
     * @return object ->pCode & ->countryId
     */
    public static function parsePlace($str)
    {
        $div = array(',', ';', '-', ' ');
        $best = NULL;

        foreach($div as $d) {
            $arr = explode($d, $str);
            $o = new stdClass();
            foreach($arr as $part) {
                $part = trim($part, ",;- \t\n\r");
                if(preg_match("/[0-9]/", $part)) {
                    $o->pCode = $part;
                    continue;
                }
                if($countryId = drdata_Countries::getIdByName($part)) {
                    $o->countryId = $countryId;
                }
                if($o->countryId && $o->code) break;
            }

            if(!$best && $o->countryId) {
                $best = new stdClass();
                $best = $o;
            }

            if($o->countryId && $o->pCode) {
                $best = $o;
            }
        }

        return $best;
    }
}