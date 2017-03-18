<?php



/**
 * Клас 'drdata_Phones' -
 *
 *
 * @category  vendors
 * @package   drdata
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class drdata_Phones extends core_Manager {
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'admin';
    
    
    /**
     * @todo Чака за документация...
     */
    var $canNew = 'admin';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin';
    
    
    /**
     * Заглавие
     */
    var $title = 'Телефонни номера';
    
    
    /**
     * Страница от менюто
     */
    var $pageMenu = 'Членове';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('md5', 'varchar(32)', 'caption=MD5');
        $this->FLD('number', 'text', 'caption=Номер');
        $this->FLD('dCC', 'varchar(8)', 'caption=Код по подразбиране->Страна');
        $this->FLD('dAC', 'varchar(16)', 'caption=Код по подразбиране->Регион');
        $this->FLD('parsedObject', 'text', 'caption=Код по подразбиране->Регион');
        $this->FLD('lifeDays', 'int', 'caption=Дни живот');
        $this->load('plg_Created,DialCodes=drdata_DialCodes');
    }
    
    
    /**
     * Връща масив с всички варииации на телофона, кода и града
     * 
     * @param array $numberArr
     * 
     * @return arrray
     */
    public static function getVariationsNumberArr($numbersArr)
    {
        $allArr = array();
        $setArr = array();
        
        foreach ((array)$numbersArr as $key => $numberObj) {
            
            // Масив с варициите на частите на номера
            $countryVarArr = self::getCountryCodeVariation($numberObj->countryCode);
            $areaVarArr = self::getAreaCodeVariation($numberObj->areaCode);
            $numberVarArr = self::getNumberVariation($numberObj->number);
            
            foreach ($countryVarArr as $countryCode) {
                foreach ($areaVarArr as $areaCode) {
                    foreach ($numberVarArr as $number) {
                        
                        // Ако кода на региона започва с 0, да не се добавя кода на държавата
                        if ($areaCode{0} === '0') {
                            $hash = $areaCode . '|' . $number;
                            if (!$setArr[$hash]) {
                                $setArr[$hash] = TRUE;
                                $allArr[] = $areaCode . $number;
                            }
                        } else {
                            $allArr[] = $countryCode . $areaCode . $number;
                        }
                    }
                }
            }
        }
        
        return $allArr;
    }


    /**
     * Връща наличната информация в базата за този код
     */
    function getMobile($countryCode)
    {
        // Зареждаме инфото, което имаме за тази $countryCode
        static $mobileInfo;
        
        if($countryCode && !$mobileInfo[$countryCode]) {
            $query = $this->DialCodes->getQuery();
            
            while($rec = $query->fetch("#countryCode = '{$countryCode}'")) {
                if($rec->areaCode) {
                    $mobileInfo[$countryCode][] = $rec->areaCode;
                }
            }
        }
        
        return $mobileInfo[$countryCode];
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function parseTel($tel, $dCC = '', $dAC = '', $useCache = TRUE)
    {
        // Добавка за българските телефони
        if($dCC == '359' && !$dAC) {
            $dAC = '2';
        }
        
        if($useCache) {
            $telSave = $tel;
            $dCCSave = $dCC;
            $dACSave = $dAC;
            $res = drdata_PhoneCache::get($tel, $dCC, $dAC);
        }
        
        if($res === NULL) {
            // Основната идея е да направим различни разбивки на номера и да 
            // проверим за всяка една от тях дали се съдържа САМО реални номера
            // Реалните номера предполагаме, че са: ... [Код на страна] [Код на регион] номер [вътрешен] ...
             
            $tel = str_replace(array('(0)', '[0]', '++'), array('', '', '+') , $tel);
            
            $tel = preg_replace("/ +\- +/", '-', $tel);
            
            $from = array(' fax', ' факс', ' f.', ' ф.', ' Fax',  ' Факс',  ' F.', ' Ф.', ' FAX', ' ФАКС',
                          ' Mob', ' mob', ' m.', ' m ', ' моб', ' Моб', ' Тел.', ' тел.', '+');

            if($defaultCountryCode) {
                $from[] = ' 00' . $defaultCountryCode;
                $from[] = ' 00 ' . $defaultCountryCode;
            }
            if($defaultAreaCode) {
                $from[] = ' 0' . $defaultAreaCode;
            }
   
            $sepArr = array(';', ',', ' ', '.', '/', "\\");      // възможни сепаратори

            foreach($sepArr as $sep) {
                
                $to = array();
                foreach($from as $c) {
                    $to[] = $sep . $c;
                }
                $tel = str_replace($from, $to, $tel);

                $test[] = explode($sep, $tel);
                if($sep != ';' && strpos($tel, ';')) {
                    $test[] = explode($sep, str_replace(';', $sep, $tel));
                }
            }
 
            foreach($test as $telArr) {
                
                $error = FALSE;
                $res = array();
                
                $defaultCountryCode = $dCC;
                $defaultAreaCode = $dAC;
                
                foreach($telArr as $t) {
                    
                    // Имаме ли нещо?
                    $t = trim($t, ',;\\/ ');
                    
                    if(!$t || !preg_match("/[0-9]/", $t)) continue;
                    
                    // Нулираме обекта
                    $obj = new stdClass();
                    $obj->original = $t;
                    
                    // правим го с малки букви, на латиница
                    $t1 = trim(strtolower(str::utf2ascii($t)));
                    
                    // Имаме ли факс?
                    
                    if((strpos($t1, 'faks') !== FALSE) ||
                        (strpos($t1, 'f.') !== FALSE) ||
                        (strpos($t1, 'fax') !== FALSE)
                    ) {
                        $obj->fax = TRUE;
                    }
                    
                    // Отделяме, ако има вътрешен номер
                    foreach(array('v.', 'vtr', 'int', 'internal', 'vatre', 'vatr') as $w) {
                        if(($p = strpos($t1, $w)) !== FALSE) {
                            $rest = substr($t1, $p + strlen($w));
                            $rest = preg_replace('/[^0-9]/', '', $rest);
                            
                            if(strlen($rest) > 1 && strlen($rest) < 5) {
                                $obj->internal = $rest;
                                $t1 = substr($t1, 0, $p);
                            }
                        }
                    }
                    
                    // Отделяме ако има префикс
                    
                    // Отделяме, ако има суфикс
                    
                    // Ако има знак '+', заменяме го с две нули
                    $t1 = str_replace('+', '00', $t1);
                    
                    if(!$t1) continue;
                    
                    // Оставяме само цифрите
                    $t1 = preg_replace('/[^0-9\+]+/', '', $t1);
                    
                    // Започваме да разсъждаваме над цифрите
                    
                    // Ако първата цифра на телефона е >0, обаче той е много, много дълъг, за 
                    // да бъде локален телефон, проверяваме дали не започва директно с код
                    // на държавата по дефолт. Ако е така, добавяме 2 нули
                    if($t1{0} > '0' && strlen($t1) >= 10 && $defaultCountryCode) {
                        if(strpos($t1, $defaultCountryCode) === 0) {
                            $t1 = '00' . $t1;
                        }
                    }
                    
                    // Ако телефонът започва с единица и в оригиналния си вид
                    // след единицата не е цифра, значи предполагаме, че телефонът е американски
                    if($t1{0} == '1' && strlen($t1) > 0) {
                        $onePos = strpos($t, '1');
                        $second = @substr($t, $onePos, 1);
                        
                        if($second > '9' || $second < '0') {
                            $t1 = '00' . $t1;
                        }
                    }
                    
                    // Ако първата цифра на телефона е >0, обаче той е  дълъг, за 
                    // да бъде локален телефон, проверяваме дали не започва директно с код
                    // на региона по дефолт или на мобилни оператори от държавата. Ако е така, добавяме 1 нула
                    if($t1{0} > '0' && strlen($t1) >= 8 && strlen($t1) < 15) {
                        if($defaultAreaCode && strpos($t1, $defaultAreaCode) === 0) {
                            $t1 = '0' . $t1;
                        } else {
                            
                            $mobArr = $this->getMobile($defaultCountryCode);
                            
                            if(count($mobArr)) {
                                foreach($mobArr as $mCode) {
                                    if(strpos($t1, $mCode) === 0) {
                                        $t1 = '0' . $t1;
                                    }
                                }
                            }
                        }
                    }
                    
                    // Ако първата цифра е 0, но втората не е и все пак, телефона е дълъг за да бъде регионален,
                    // Проверяваме дали не започва с националния код, и ако е така, отпред добавяме една 0
                    if($t1{0} == '0' && $t1{1} > '0' && strlen($t1) >= 9 && $defaultCountryCode) {
                        if(substr($t1, 1, strlen($defaultCountryCode)) == $defaultCountryCode) {
                            $t1 = '0' . $t1;
                        }
                    }
                    
                    // Ако номера започва с две нули, вадим кода на страната от него
                    if(substr($t1, 0, 2) == '00') {
                        $query = $this->DialCodes->getQuery();
                        $rec = $query->fetch("'{$t1}' LIKE CONCAT('00', #countryCode, '%')   AND !#areaCode  AND #countryCode != ''");
                        
                        if($rec) {
                            $obj->countryCode = $rec->countryCode;
                            $obj->country = $rec->country;
                        } else {
                            //приемаме, че е непозната държава
                            $obj->countryCode = substr($t1, 2, 1);
                            $obj->country = 'Unknown';
                        }
                        
                        // Правим телефона да изглежда като локален
                        $t1 = '0' . substr($t1, 2 + strlen($obj->countryCode));
                    }
                    
                    // ако номера започва с една нула приемаме, че страната е по дефоулт, и се опитваме да извадим
                    // населеното място
                    if(substr($t1, 0, 1) == '0') {
                        if(!$obj->countryCode) {
                            
                            $obj->countryCode = $defaultCountryCode;
                            $rec = $this->DialCodes->fetch(" #countryCode = '{$obj->countryCode}'  AND !#areaCode ");
                            $obj->country = $rec->country;
                        }
                        
                        // само за италия
                        if($obj->countryCode == '39' && $t1{1} == '0') {
                            $t1 = substr($t1, 1);
                        }
                        
                        $query = $this->DialCodes->getQuery();
                        
                        $rec = $query->fetch("'{$t1}' LIKE CONCAT('0', #areaCode, '%') AND #countryCode = '{$obj->countryCode}' AND #areaCode != ''");
                        
                        if($rec) {
                            $obj->areaCode = $rec->areaCode;
                            $obj->area = $rec->area;
                            $obj->country = $rec->country;
                        } else {
                            // Приемаме, че е непознат регион
                            if($obj->countryCode == '49') {
                                // В германия кодовете са по-дълги
                                $areaCodeLen = 4;
                            } else {
                                $areaCodeLen = 2;
                            }
                            
                            if($t1{$areaCodeLen + 1} == '0') $areaCodeLen++;
                            
                            if($t1{$areaCodeLen + 1} == '0') $areaCodeLen++;
                            
                            $obj->areaCode = substr($t1, 1, $areaCodeLen);
                            
                            $obj->area = 'Unknown';
                        }
                        
                        $t1 = substr($t1, 1 + strlen($obj->areaCode));
                    }
                    
                    // Само за италия
                    if($obj->countryCode == '39' && ($obj->areaCode{0} != '0') && ($obj->areaCode{0} != '3')) {
                        $obj->areaCode = '0' . $obj->areaCode;
                    }
                    
                    // Тука вече се предполага, че става дума за локален телефон
                    
                    // Ако нямаме страна?
                    if(!$obj->countryCode) {
                        if($defaultCountryCode && $defaultAreaCode) {
                            $rec = $this->DialCodes->fetch("#countryCode = '{$defaultCountryCode}' AND #areaCode = '{$defaultAreaCode}'");
                            
                            if($rec) {
                                $obj->countryCode = $rec->countryCode;
                                $obj->country = $rec->country;
                                $obj->areaCode = $rec->areaCode;
                                $obj->area = $rec->area;
                            }
                        }
                    }
                    
                    // Ако все още нямаме страна
                    if(!$obj->countryCode) {
                        if($defaultCountryCode) {
                            $rec = $this->DialCodes->fetch("#countryCode = '{$defaultCountryCode}' AND !#areaCode ");
                            
                            if($rec) {
                                $obj->countryCode = $rec->countryCode;
                                $obj->country = $rec->country;
                            } else {
                                $obj->countryCode = $defaultCountryCode;
                            }
                        }
                    }
                    
                    // Ако имаме страна, но нямаме регион
                    if($obj->countryCode && !$obj->areaCode) {
                        if($obj->countryCode && $defaultAreaCode) {
                            $rec = $this->DialCodes->fetch("#countryCode = '{$obj->countryCode}' AND #areaCode = '{$defaultAreaCode}'");
                            
                            if($rec) {
                                $obj->areaCode = $rec->areaCode;
                                $obj->area = $rec->area;
                            } else {
                                $obj->areaCode = $defaultAreaCode;
                            }
                        }
                    }
                    
                    $obj->number = $t1;
                    $this->debug("<li> $t1 [ " . $obj->countryCode . "-" . $obj->areaCode . "-" . $obj->number . "-" . $ok . " ] ( $defaultCountryCode ) ( $defaultAreaCode ) $obj->area ");
                    
                    if(strpos($obj->area, 'Cellular') !== FALSE) {
                        $obj->mobile = TRUE;
                    }
                    
                    // Хайде сега да видим дали са ОК дължините
                    $error = FALSE;
                    
                    if(strlen($obj->number) < 3) {
                        $this->debug(" [<3] ");
                        $error = TRUE;
                    }
                    
                    // Прекалено дълъг номер
                    if(strlen($obj->number . $obj->areacode) > 13) {
                        $this->debug(" [>13] ");
                        $error = TRUE;
                    }
                    
                    if($obj->countryCode == '352') {
                        $maxAreaNum = 6;
                    } else {
                        $maxAreaNum = 7;
                    }
                    
                    if(strlen($obj->areaCode . $obj->number) < $maxAreaNum) {
                        $this->debug(" [<{$maxAreaNum}] ");
                        $error = TRUE;
                    }
                    
                    // Прекалено дълги номера за България
                    if($obj->countryCode == '359' && $obj->areaCode && strlen($obj->number) > 7) {
                        $this->debug(" [BG7] ");
                        $error = TRUE;
                    }
                    
                    // Неточен брой цифри на мобилни номера
                    if($obj->countryCode == '359' &&
                        ($obj->areaCode == '87' || $obj->areaCode == '88' || $obj->areaCode == '89') &&
                        strlen($obj->number) != 7) {
                        
                        $this->debug("[BG MOB] ");
                        $error = TRUE;
                    }
                    
                    //    print_r($obj);
                    
                    if(!$error) {
                        // Няма грешка засега
                        $res[] = $obj;
                        
                        if(!$obj->mobile) {
                            $defaultCountryCode = $obj->countryCode;
                            $defaultAreaCode = $obj->areaCode;
                        }
                    } else {
                        // По време на обработката е настъпила грешка
                        break;
                    }
                }
                
                if(!$error) break;
            }

            if($useCache) {
                drdata_PhoneCache::set($telSave, $dCCSave, $dACSave, $res);
            }
        }
   
        return $res;
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    static function on_BeforeSave(&$invoker, &$id, &$rec)
    {
        if($invoker->fetch("#countryCode = '{$rec->countryCode}' AND #areaCode = '{$rec->areaCode}'")) {
            
            return FALSE;
        }
        
        $rec->country = trim(str::utf2ascii($rec->country));
        $rec->countryCode = trim($rec->countryCode);
        $rec->area = trim(str::utf2ascii($rec->area));
        
        if((strpos(strtolower($rec->area), 'russia') !== FALSE || !trim($rec->area)) && $rec->countryCode == '7') {
            $rec->country = "Russia";
        }
        
        if($rec->country == 'Balgaria')
        {
            $rec->country = 'Bulgaria';
        }
        
        $rec->areaCode = trim($rec->areaCode);
        
        $rec->order = strlen($rec->countryCode) + strlen($rec->areaCode) ;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function debug($str) {
        if($this->test) {
            echo $str;
        }
    }
}