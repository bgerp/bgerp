<?php



/**
 * Клас 'core_String' ['str'] - Функции за за работа със стрингове
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_String
{
    
    
    /**
     * Конвертира всички европейски азбуки,
     * включително и кирилицата, но без гръцката към латиница
     *
     * @param  string $text текст за конвертиране
     * @return string резултат от конвертирането
     * @access public
     */
    static function utf2ascii($text)
    {
        // Опитваме се да прихванем всички символи, които не са ASCII
        // Ако е подаден текст, който е изцяло от неаски символи, ще има само едно извикване на колбек функцията
        $me = get_called_class();
        $text = preg_replace_callback('/([^\x21-\x7F]+)/u', array($me, 'convertToAscii'), $text);
        
        return $text;
    }
    
    
    /**
     * Калбек функция, която конвертира текста в ASCII
     * 
     * @param array $match
     */
    protected static function convertToAscii($match)
    {
        $text = $match[0];
        
        static $trans = array();
        static $keys = array();
        
        if (!$trans || !$keys) {
            ob_start();
            require_once(dirname(__FILE__) . '/transliteration.inc.php');
            ob_end_clean();
            
            $trans = $code;
            $keys = array_keys($trans);
        }
        
        $text = str_replace($keys, $trans, $text);
        
        preg_match_all('/[A-Z]{2,3}[a-z]/', $text, $matches);
        
        foreach ($matches[0] as $upper) {
            $cap = ucfirst(strtolower($upper));
            $text = str_replace($upper, $cap, $text);
        }
        
//         $text = @iconv("UTF-8", "ASCII//TRANSLIT", $text);
        
        return $text;
    }
    
    
    /**
     * Прави първия символ на стринга главна буква (за многобайтови символи)
     * 
     * @param string $string - стринга който ще се рансформира
     * @return string $string - стринга с първа главна буква
     */
	public static function mbUcfirst($string) 
	{
		$string = trim($string);
		$string = mb_strtoupper(mb_substr($string, 0, 1)) . mb_substr($string, 1);
        
        return $string;
    }

    
    /**
     * Превръща UTF-9 в каноничен стринг, съдържащ само латински букви и числа
     * Всички символи, които не могат да се конвертират, се заместват с втория аргумент
     */
    public static function canonize($str, $substitute = '-')
    {
        $cStr = str::utf2ascii($str);

        $cStr = trim(preg_replace('/[^a-zA-Z0-9]+/', $substitute, " {$cStr} "), $substitute);
        
        return $cStr;
    }

    

    /**
     * Функция за генериране на случаен низ. Приема като аргумент шаблон за низа,
     * като символите в шаблона имат следното значение:
     *
     * '*' - Произволна латинска буква или цифра
     * '#' - Произволна цифра
     * '$' - Произволна буква
     * 'a' - Произволна малка буква
     * 'А' - Произволна голяма буква
     * 'd' - Малка буква или цифра
     * 'D' - Голяма буква или цифра
     */
    static function getRand($pattern = 'addddddd')
    {
        static $chars, $len;
        
        if(empty($chars)) {
            $chars['*'] = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $chars['#'] = "0123456789";
            $chars['$'] = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $chars['a'] = "abcdefghijklmnopqrstuvwxyz";
            $chars['A'] = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $chars['d'] = "0123456789abcdefghijklmnopqrstuvwxyz";
            $chars['D'] = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            
            // Генерираме $seed
            $seed = microtime() . EF_SALT;
            
            foreach($chars as $k => $str) {
                
                $r2 = $len[$k] = strlen($str);
                
                while($r2 > 0) {
                    $r1 = (abs(crc32($seed . $r2--))) % $len[$k];
                    $c = $chars[$k]{$r1};
                    $chars[$k]{$r1} = $chars[$k]{$r2};
                    $chars[$k]{$r2} = $c;
                }
            }
        }
        
        $pLen = strlen($pattern);
        
        for($i = 0; $i < $pLen; $i++) {
            
            $p = $pattern{$i};
            
            $rand = rand(0, $len[$p]-1);
            
            $rand1 = ($rand + 7) % $len[$p];
            
            $c = $chars[$p]{$rand};
            $chars[$p]{$rand} = $chars[$p]{$rand1};
            $chars[$p]{$rand1} = $c;
            
            $res .= $c;
        }
        
        return $res;
    }
    
    
    /**
     * 
     */
    static function cut($str, $beginMark, $endMark = '', $caseSensitive = FALSE)
    {
    
        return static::crop($str, $beginMark, $endMark, $caseSensitive);
    }
    
    
    /**
     * Отделя стринг, заключен между други два стринга
     */
    static function crop($str, $beginMark, $endMark = '', $caseSensitive = FALSE, &$offset = 0)
    {
        if (!$caseSensitive) {
            $sample = mb_strtolower($str);
            $beginMark = mb_strtolower($beginMark);
            $endMark = mb_strtolower($endMark);
        } else {
            $sample = $str;
        }
        
        $begin = mb_strpos($sample, $beginMark, $offset);
        
        if ($begin === FALSE) return FALSE;
        
        $begin = $begin + mb_strlen($beginMark);
        
        if ($endMark) {
            $end = mb_strpos($sample, $endMark, $begin);
            
            if ($end === FALSE) return FALSE;
            
            $result = mb_substr($str, $begin, $end - $begin);
            $offset = $end + mb_strlen($endMark);
        } else {
            $result = mb_substr($str, $begin);
            $offset = mb_strlen($str);
        }
        
        return $result;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function findOn($str, $match, $until = -1)
    {
        $str = mb_strtolower($str);
        $match = mb_strtolower($match);
        $find = mb_strpos($str, $match);
        
        if ($find === FALSE) {

            return FALSE;
        }
        
        if ($until < 0) {

            return TRUE;
        }
        
        if ($find <= $until) {
            return TRUE;
        } else {
        
            return FALSE;
        }
    }


    /**
     * Връща истина, само ако и двата стринга са не-нулеви и единият е по-стринг на другия
     */
    static function contained($str1, $str2)
    {
        if(strlen($str1) == 0 || strlen($str2) == 0) {

            return FALSE;
        }

        if(strpos($str1, $str2) !== FALSE || strpos($str2, $str1) !== FALSE) {

            return TRUE;
        }

        return FALSE;
    }
    
    
    /**
     * Допълва стринг с хеш, уникален за дадения стринг и за текущата конфигурация на системата (EF_SALT)
     * 
     * @param   string    $str        Стринга, който ще бъде допълван
     * @param   int       $length     Дължина на частта за допълване
     * @param   string    $moreSalt   Допълнителна сол за защита
     *
     * @return  string   Допълнения стринг
     */
    static function addHash($str, $length = 4, $moreSalt = '')
    {
        
        return $str . "_" . substr(md5(EF_SALT . $moreSalt . $str), 0, $length);
    }
    
    
    /**
     * Проверка, дали даден стринг преди това е бил допълван с функцията addHash
     * Връща оригиналния, не-допълван стирнг
     * 
     * @param   string    $str        Защитеният чрез addHash стринг
     * @param   int       $length     Дължина на частта за допълване
     * @param   string    $moreSalt   Допълнителна сол за защита
     *
     * @return  string               Оригиналния стринг или FALSE в случай на несъответствие
     */
    static function checkHash($str, $length = 4, $moreSalt = '')
    {
        $oStr = substr($str, 0, strlen($str) - $length - 1);
        
        if ($str == str::addHash($oStr, $length, $moreSalt) && substr($str, -1 - $length, 1) == "_") {

            return $oStr;
        }
        
        return FALSE;
    }
    
    
    /**
     * Конвертиране между PHP и MySQL нотацията
     */
    static function phpToMysqlName($name)
    {
        $name = trim($name);
        $lastC = '';
        $mysqlName = '';

        $strLen = strlen($name);
        for ($i = 0; $i < $strLen; $i++) {
            $c = $name{$i};
            
            if ((($lastC >= "a" && $lastC <= "z") || ($lastC >= "0" && $lastC <= "9")) && ($c >= "A" && $c <= "Z")) {
                $mysqlName .= "_";
            }
            $mysqlName .= $c;
            $lastC = $c;
        }
        
        return strtolower($mysqlName);
    }
    
    
    /**
     * Превръща mysql име (с подчертавки) към нормално име
     */
    static function mysqlToPhpName($name)
    {
        $cap = FALSE;
        
        for ($i = 0; $i < strlen($name); $i++) {
            $c = $name{$i};
            
            if ($c == "_") {
                $cap = TRUE;
                continue;
            }
            
            if ($cap) {
                $out .= strtoupper($c);
                $cap = FALSE;
            } else {
                $out .= strtolower($c);
            }
        }
        
        return $out;
    }
    
    
    /**
     * Конвертира стринг до уникален стринг с дължина, не по-голяма от указаната
     * Уникалността е много вероятна, но не 100% гарантирана ;)
     */
    static function convertToFixedKey($str, $length = 64, $md5Len = 32, $separator = "_")
    {
        if (strlen($str) <= $length) return $str;
        
        $strLen = $length - $md5Len - strlen($separator);
        
        // Дължината на MD5 участъка и разделителя е по-голяма от зададената обща дължина
        expect($strLen >= 0, $length, $md5Len);
        
        if (ord(substr($str, $strLen - 1, 1)) >= 128 + 64) {
            $strLen--;
            $md5Len++;
        }
        
        $md5 = substr(md5('_SALT_' . $str), 0, $md5Len);
        
        return substr($str, 0, $strLen) . $separator . $md5;
    }
    
    
    /**
     * Парсира израз, където променливите започват с #
     */
    static function prepareExpression($expr, $nameCallback)
    {
        $len = strlen($expr);
        $esc = FALSE;
        $isName = FALSE;
        $lastChar = '';
        $out = '';
        
        for ($i = 0; $i <= $len; $i++) {
            
            if($i == $len) {
                $c = '';
            } else {
                $c = $expr[$i];
            }

            if($lastChar == "\\") {
                $bckSl++;
            } else {
                $bckSl = 0;
            }

            if ($c == "'" && (($bckSl % 2) == 0)) {
                $esc = (!$esc);
            }
            
            if ($esc) {
                $out .= $c;
                $lastChar = $c;
                continue;
            }
            
            if ($isName) {
                if (($c >= 'a' && $c <= 'z') || ($c >= 'A' && $c <= 'Z') || ($c >= '0' && $c <= '9') || $c == '_') {
                    $name .= $c;
                    continue;
                } else {
                    // Край на името
                    $isName = FALSE;
                    $out .= call_user_func($nameCallback, $name);
                    $out .= $c;
                    $lastChar = $c;
                    continue;
                }
            } else {
                if ($c == '#') {
                    $name = '';
                    $isName = TRUE;
                    continue;
                } else {
                    $out .= $c;
                    $lastChar = $c;
                }
            }
        }
        
        return $out;
    }
    
    
    /**
     * Проверка дали символът е латинска буква
     */
    static function isLetter($c)
    {
        
        return ($c >= 'a' && $c <= 'z') || ($c >= 'A' && $c <= 'Z') || $c == '_';
    }
    
    
    /**
     * Проверка дали символът е цифра
     */
    static function isDigit($c)
    {
        return $c >= '0' && $c <= '9';
    }


    /**
     * Оставя само първите $length символа от дадения стринг
     */
    static function truncate($str, $length, $breakWords = TRUE, $append = '…')
    {
      $strLength = mb_strlen($str);

      if ($strLength <= $length) {
         return $str;
      }

      if (!$breakWords) {
           while(preg_match('/^[\pL\pN]/', mb_substr($str, $length, 1))) {
               $length--;
           }
      }

      return mb_substr($str, 0, $length) . $append;
    }
    
    
    /**
     * На по-големите от дадена дължина стрингове, оставя началото и края, а по средата ...
     * В допълнение, ако е необходимо, хифенира текста
     * 
     * @param string $str
     * @param integer $maxLen
     * @param integer $showEndFrom
     * @param string $dots
     * 
     * @return string
     */
    public static function limitLenAndHyphen($str, $maxLen, $showEndFrom = 20, $dots = " ... ")
    {
        
        return self::limitLen($str, $maxLen, $showEndFrom, $dots, TRUE);
    }
    
    
    /**
     * На по-големите от дадена дължина стрингове, оставя началото и края, а по средата ...
     * 
     * @param string $str
     * @param integer $maxLen
     * @param integer $showEndFrom
     * @param string $dots
     * @param boolean $hyphen
     * 
     * @return string
     */
    static function limitLen($str, $maxLen, $showEndFrom = 20, $dots = " ... ", $hyphen = FALSE)
    {
        if(Mode::is('screenMode', 'narrow')) {
            $maxLen = round($maxLen/1.25);
            $showEndFrom = round($showEndFrom/1.25);
        }
        if(mb_strlen($str) > $maxLen) {
            if($maxLen >= $showEndFrom) {
                $remain = (int) ($maxLen - 3) / 2;
                $str = mb_substr($str, 0, $remain) . $dots . mb_substr($str, -$remain);
            } else {
                $remain = (int) ($maxLen - 3);
                $str = mb_substr($str, 0, $remain) . $dots;
            }
        }
        if ($hyphen && (mb_strlen($str) > $maxLen/4)) {
            $str = str::hyphenText($str);
        }
        return $str;
    }
	
    
    /**
     * Проверява даден стринг и ако има в името му '< prefix >< number >' инкрементираме
     * < number >, ако не е намерено се добавя към края на стринга: '< prefix > < startNum >'
     * 
     * @param string $string - стринга който ще се мъчим да инкрментираме
     * @param string $prefix - за каква наставка ще проверяваме
     * @param string $startNum - от кой кое число да започваме
     * @return string - увеличения стринг
     */
    public static function addIncrementSuffix($string, $prefix = '' , $startNum = 1)
    {
    	preg_match("/{$prefix}(\d+)$/", $string, $matches);
    	if(count($matches) == 2){
    		
    		$number = $matches[1];
    		$number = self::increment($number);
    		
    		$offset = strlen($prefix);
    		$startTagPos = strrpos($string, "{$prefix}") + $offset;
    		
    		// Инкрементираме числото
    		$string = substr_replace($string, $number, $startTagPos);
    	} else {
    		
    		// Ако не е открит стринга добавяме `{$prefix}{$startNum}` в края му
    		$string .= "{$prefix}{$startNum}";
    	}
    	
    	return $string;
    }
    
    
    /**
     *  Инкрементиране с единица на стринг, чиято последна част е число
     *  Ако стринга не завършва на числова част връща се FALSE
     *  @param string $string - стринга който се подава
     *
     *  @return mixed string/FALSE - инкрементирания стринг или FALSE
     */
    public static function increment($str)
    {
    	if(is_string($str)){
    		
	    	//Разделяне на текста от последното число
	    	preg_match("/.+?(\d+)$/", $str, $match);
	    	
	    	//Ако е открито число
	        if (isset($match['1'])) {
	        	$numLen = strlen($match['1']);
	        	$numIndex = strrpos($str, $match['1']);
	        	$other = substr($str,0, $numIndex);
	        	
	            // Съединяване на текста с инкрементирана с единица стойност на последното число
	            return $other . str_pad(++$match['1'], $numLen, "0", STR_PAD_LEFT);
	        } else {
	        	
	        	// Ако целия стринг е число, инкрементираме го
	        	if(is_numeric($str)){
	        		$str += 1;
	        		return (string)$str;
	        	}
	        }
    	}
    	
        return FALSE;
    }

    
    /** 
     * Циклене по UTF-8 низове
     */
    static function nextChar($string, &$pointer)
    {
        $c = mb_substr(substr($string, $pointer, 5), 0, 1);

        $pointer += strlen($c);

        return $c;
    }


    /**
     * Парсиране на текст
     * Извлича думите и за всяка дума изпълнява $callback
     * Колбек функцията може да е array(class, mothod)
     * Тя може да има действие от вида:  $out = substr($out, 0, $len) . '<wrong>' . substr($out, $len) . '</wrong>';
     * 
     * @param string $string    Входящ стринг
     * @param string $out       Изходящ стринг
     * @param mixed  $callback  Колбек функция
     * @param array  $deviders  Символи, които разделят думите
     * @param boolean $html     Дали да се игнорират html таговете
     * 
     * @return array             Връща извлечените думи
     */
    static function parseWords($string, &$out = NULL, $callback = NULL, $deviders = NULL,  $html = TRUE)
    {
        $flagWord = TRUE;
        $flagHtml = FALSE;
        $pointer  = 0;
        setIfNot($deviders, array(' ', ',', '"', '\'', ';', '[', ']', '.', '<', '>', "\n", "\r", "\t", ':', '?', '!', '-', '(', ')', '“', '„', '…', '&', '_', '/', '=', '+', '*'));
        
        $res = array();
        
        while('' != ($c = self::nextChar($string, $pointer))) {
       
            if(in_array($c, $deviders)) {  
                if(!$flagHtml) {
                    if($lastLen !== FALSE && $lastLen < strlen($out)) {
                        // Записваме думата между $lastLen до края на аутпут буфера
                        $res[] = substr($out, $lastLen);

                        // Ако е зададен колбек - викаме го
                        if($callback) {
                            $callback($out, $lastLen, $lastTag);
                        }
                    }
                }
                $lastLen = FALSE;;
            } else {
                if($lastLen === FALSE) {
                    $lastLen = strlen($out);
                }
            }
            
            if($html) {
                if($c == '<') {
                    $flagHtml = TRUE;
                    $lastTag = '';
                }

                if($flagHtml) {
                    $lastTag .= $c;
                }

                if($c == '>') {
                    $flagHtml = FALSE;
                }
            }

            $out .= $c;
        }
        
        return $res;
    }


    /**
     * Опитва се да премахне от даден стринг, масив от под-стрингове, считано то началото му
     */
    static function removeFromBegin($str, $sub)
    {
        if(!is_array($sub)) {
            expect(is_scalar($sub));
            $sub = array($sub);
        }

        foreach($sub as $s) {
            if(stripos($str, $s) === 0) {
                $str = mb_substr($str, mb_strlen($s));
            }
        }

        return $str;
    }
    
    
    /**
     * Връща масив с гласните букви на латиница и кирилица
     */
    static function getVowelArr()
    {
        
        return array("a"=>"a", "e"=>"e", "i"=>"i", "o"=>"o", "u"=>"u",
    					"а"=>"а", "ъ"=>"ъ", "о"=>"о", "у"=>"у", "е"=>"е", "и"=>"и");
    }
    
    
    /**
     * Проверява даден символ дали е гласна буква
     * 
     * @param char $char - Симвът, който ще проверяваме
     * 
     * @return boolena - Ако е гласна връщаме TRUE
     */
    static function isVowel($char)
    {
        // Масива със съгласните букви
        static $vowelArr;
        
        // Ако не е сетнат
	    if (!$vowelArr) {
	        
	        // Вземаме масива
	        $vowelArr = static::getVowelArr();
	    }
	    
	    // Буквата в долен регистър
	    $char = mb_strtolower($char);
	    
	    // Ако е съгласна
	    return (boolean)$vowelArr[$char];
    }
    
    
    /**
     * Връща масив с съгласните букви на латиница и кирилица
     */
    static function getConsonentArr()
    {
        
        return array("б"=>"б","в"=>"в", "г"=>"г", "д"=>"д", "ж"=>"ж", "з"=>"з", "к"=>"к",
        				"л"=>"л", "м"=>"м", "н"=>"н", "п"=>"п", "р"=>"р", "с"=>"с", "т"=>"т",
        				"ф"=>"ф", "х"=>"х", "ц"=>"ц", "ч"=>"ч", "ш"=>"ш",
        				"b"=>"b", "c"=>"c", "d"=>"d", "f"=>"f", "g"=>"g", "h"=>"h", "j"=>"j",
        				"k"=>"k", "l"=>"l", "m"=>"m", "n"=>"n", "p"=>"p", "q"=>"q", "r"=>"r", "s"=>"s",
        				"t"=>"t", "v"=>"v", "x"=>"x", "z"=>"z");
    }
    
    
    /**
     * Проверява дали подадения символ е пунктуационен
     * 
     * @param char $char
     * 
     * @return boolean
     */
    static function isPunctuation($char)
    {
        
        $isPunctuation = in_array($char, array( '.',  ',',  '!',  '?',  ';',  ':'));
        
        return (boolean)$isPunctuation;
    }
    
    
    /**
     * Проверява даден символ дали е съгласна буква
     * 
     * @param char $char - Симвът, който ще проверяваме
     * 
     * @return boolena - Ако е съгласна връщаме TRUE
     */
	static function isConsonent($char)
	{
	    // Масива със съгласните букви
	    static $consonentArr;
	    
	    // Ако не е сетнат
	    if (!$consonentArr) {
	        
	        // Вземаме масива
	        $consonentArr = static::getConsonentArr();
	    }
	    
	    // Буквата в долен регистър
	    $char = mb_strtolower($char);
	    
	    // Ако е съгласна
	    return (boolean)$consonentArr[$char];
	}
	
	
	/**
	 * Всеки символ след празен да е в горния регистър
	 * 
	 * @param string $str
	 * 
	 * @return string
	 */
	static function stringToNameCase($str)
	{
	    $str = mb_convert_case($str, MB_CASE_TITLE, 'UTF-8');
	    
	    return $str;
	}
	
	
	/**
	 * Преобразува в първа главна буква първия елемент и всеки следващ след подадения разделител
	 * 
	 * @param string $str
	 * @param string $delimiter
	 */
    static function toUpperAfter($str, $delimiter=NULL)
    {
        // Ако е подаден разделите
        if ($delimiter) {
            
            // Разделяме стринга с разделителя
            $strArr = explode($delimiter, $str);
            
            // За всеки елемент в масива конвертираме към главна буква
            $strArr = array_map('static::mbUcfirst', $strArr);
            
            // Обединяваме масива в стринг
            $nStr = implode($delimiter, $strArr);
        } else {
            
            // Ако не е подаден разделител, само първата буква да е главна
            $nStr = static::mbUcfirst($str);
        }
        
	    return $nStr;
    }
    

    /**
     * Подготвя аритметичен израз за изчисляване
     */
    static function prepareMathExpr($expr)
    {
        // Remove whitespaces
        $expr = preg_replace('/\s+/', '', $expr);
                
        // What is a number
        $number = '((?:0|[1-9]\d*)(?:\.\d*)?(?:[eE][+\-]?\d+)?|pi|π|time)'; 
        
        // Allowed PHP functions
        $functions = '(?:sinh?|cosh?|tanh?|acosh?|asinh?|atanh?|exp|log(10)?|deg2rad|rad2deg|sqrt|pow|min|max|abs|intval|ceil|floor|round|(mt_)?rand|gmp_fact)';
        
        // Allowed math operators
        $operators = '[\/\*\^\+\-\,\%\>\<\=\&\|\!]{1,2}';
        
        // Final regexp, heavily using recursive patterns
        $regexp = '/^([+\-\!]?(' . $number . '|' . $functions . '\s*\((?1)+\)|\((?1)+\))(?:' . $operators . '(?1))?)+$/'; 

        if (preg_match($regexp, $expr)) {
            // Replace pi with pi function
            $result = preg_replace('!pi|π!', 'pi()', $expr); 
            $result = preg_replace('!time!', 'time()', $result);
            $result = preg_replace('!\<\>!', '!=', $result); 
        } else {
            $result = FALSE;
        }

        return $result;
    }


    /**
     * Изчислява аритметичен израз от стринг
     * Предварително израза трябва да се подготви 
     */
    static function calcMathExpr($expr, &$success = NULL)
    { 
        $expr = self::prepareMathExpr($expr);
        
        if(strlen($expr)) {
            $last = error_reporting(0);
            try {
                eval('$result = ' . $expr . ';');
            } catch (Throwable $t) {
                $result = NULL;
                $success = FALSE;
            }

        }

        return $result;
    }
    
    
    /**
     * Помощна функция за сортиране на масив
     */
    public static function sortByLengthReverse($a, $b)
    {
        return strlen($b) - strlen($a);
    }


    /**
     * Оцветява текст по относително уникален начин, в зависимост от съдържанието му
     */
    static function coloring($text, $colorFactor = NULL)
    {
        if(!$colorFactor) {
            $colorFactor = $text;
        }
        $txColor = str_pad(dechex(hexdec(substr($hash = md5($colorFactor), 0, 6)) & 0x7F7F7F), 6, '0', STR_PAD_LEFT);
        
        $bgColor = str_pad(dechex(hexdec(substr($hash, 6, 6)) | 0x808080), 6, '0', STR_PAD_LEFT);

        $text = "<span style='color:#{$txColor}; background-color:#{$bgColor}'>" . $text . "</span>";;

        return $text;
    }


    /**
     * Връща разширението на файла, от името му
     */
    static public function getFileExt($name)
    {
        if(($dotPos = mb_strrpos($name, '.')) !== FALSE) {
            $ext =  mb_strtolower(mb_substr($name, $dotPos + 1));
            $pattern = "/^[a-zA-Z0-9_\$]{1,10}$/i";
            if(!preg_match($pattern, $ext)) {
                $ext = '';
            }
        } else {
            $ext = '';
        }
        
        return $ext;
    }
    

    /**
     * Определя дали даден стринг отговаря едновременно на две условия:
     *  - Да не отговаря на определен "негативен" регулярен шаблон
     *  - Да отговаря на определен "позитивен" регулярен шаблон
     *
     * @param string $str               Стринга, който ще бъде изследван
     * @param string $negativePattern   Шаблон на който стринга не трябва да отговаря
     * @param string $positivePattern   Шаблон на който стринга трябва да отговаря
     *
     * @return boolean
     */
    static public function matchPatterns($str, $negativePattern = NULL, $positivePattern = NULL)
    {
        if($negativePattern && preg_match($negativePattern, $str)) return FALSE;
        if($positivePattern && !preg_match($positivePattern, $str)) return FALSE;

        return TRUE;
    }


   /**
    * Връща символ в UTF-8 енкодинг от уникод номера му
    *
    * @param int $u
    * @return char
    */
    public static function unichr($u) 
    {
        return mb_convert_encoding('&#' . intval($u) . ';', 'UTF-8', 'HTML-ENTITIES');
    }
    
    
    
    /**
     * Връща най-доброто съвпадение за дума от масива
     * 
     * @param array $wordsArr
     * @param strng $string
     * @param NULL|double $percent
     * @param boolean $ci
     * 
     * @return NULL|strng
     */
    public static function getClosestWord($wordsArr, $string, &$percent = NULL, $ci = FALSE)
    {
        $wordsArr = arr::make($wordsArr);
        
        // Ако в масива има търсения стринг
        if ($wordsArr[$string]) {
            $percent = 100;
            
            return $string;
        }
        
        $shortestL = NULL;
        $shortestW = '';
        if ($ci) {
            $string = mb_strtolower($string);
        }
        $strLen = mb_strlen($string);
        
        foreach ((array)$wordsArr as $word) {
            
            $oWord = $word;
            
            if ($ci) {
                $word = mb_strtolower($word);
            }
            
            // Ако стринга съвпада с търсения
            if ($word == $string) {
                
                $shortestW = $oWord;
                $percent = 100;
                
                break;
            }
            
            // Търсим най-доборто съвпадение
            $l = levenshtein($word, $string);
            
            if ((is_null($shortestL)) || $l <= $shortestL) {
                
                $nPercent = (1 - ($l / max($strLen, mb_strlen($word)))) * 100;
                
                if (!$percent || $percent <= $nPercent) {
                    $percent = $nPercent;
                    $shortestL = $l;
                    $shortestW = $oWord;
                }
            }
        }
        
        if ($percent) {
            $percent = round($percent, 2);
            
            return $shortestW;
        }
    }
    
    
    /**
     * Замества последното срещане на търсения стринг с друг стринг
     * 
     * @param string $string  - стринг в който търсим
     * @param string $search  - стринг, който търсим
     * @param string $replace - стринг, който да заместим
     * @return string $string - заместения стринг
     */
    public static function replaceLastOccurence($string, $search, $replace)
    {
    	if((($stringLen = strlen($string)) == 0) || (($searchLen = strlen($search)) == 0)) return $string;
    	$pos = strrpos($string, $search);
    
    	if($pos > 0) return substr($string,0,$pos) . $replace . substr($string, $pos + $searchLen, max(0, $stringLen - ($pos + $searchLen)));
    
    	return $string;
    }


    /**
     * 
     * Хифинира текст, така че да няма много дължи, не-пренодими думи
     * 
     * @param string $text
     * @param integer $minWordLenForHyphen
     * 
     * @return string
     */
    public static function hyphenText($text, $minWordLenForHyphen = 20)
    {
        $hyphSign = html_entity_decode('&#45;');
        
        $text = preg_replace_callback("/[^ \r\t\n{$hyphSign}]{" . $minWordLenForHyphen . ",}/s", array('core_String', 'hyphenWord'), $text);

        return $text;
    }
    
    
    /**
     * 
     * 
     * @param array $matches
     * 
     * @return string
     */
    private static function hyphenWord($matches, $minLen = 8, $maxLen = 32)
    {      
	
    	return hyphen_Plugin::getHyphenWord($matches[0], $minLen, $maxLen);
    }
    
    
    /**
     * Маха всички празни стрингове от стринга
     * 
     * @param varchar $string
     * @return varchar $string
     */
    public static function removeWhitespaces($string)
    {
    	return preg_replace('/\s+/', '', $string);
    }
    
    
    /**
     * Разбива текст по нови редове във масив
     * 
     * @param string $text
     * @return array $array
     */
    public static function text2Array($text)
    {
    	$text = preg_replace('/[\s|\,|\;]+/', "\n", $text);
    	$array = explode("\n", trim($text));
    	
    	return $array;
    }
}
