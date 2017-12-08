<?php



/**
 * Клас 'core_SpellNumber' - Вербално представяне на числа
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
class core_SpellNumber
{
    
    
    /**
     * Вмъква съюза `и` преди последната дума, ако липсва
     *
     * @access private
     * @param string $Text Последователност от думи, разделени с интервали
     * @return string
     */
    function insAnd($text)
    {
        $i = strrpos($text, " ");
        $l = strlen($text);
        
        if ($i >= 3) {
            if (substr($text, $i - 3, 3) != " и")
            $text = substr($text, 0, $i) . " и" . substr($text, $i, $l - $i);
        }
        
        return $text;
    }
    
    
   /**
    * @access private
    * @param int $d1 десетична цифра
    * @param int $d2 десетична цифра
    * @param int $d3 десетична цифра
    * @param string $G род
    * @return string Словесната форма за числото образувано от цифрите в указания род
    */
    function dig2Text($d2, $d1, $d0, $g = "n")
    {
        $text = NULL;

        switch ($d2) {
            case 1 : {
                    $text .= "сто";
                    break;
                }
            case 2 : {
                    $text .= "двеста";
                    break;
                }
            case 3 : {
                    $text .= "триста";
                    break;
                }
            case 4 : {
                    $text .= "четиристотин";
                    break;
                }
            case 5 : {
                    $text .= "петстотин";
                    break;
                }
            case 6 : {
                    $text .= "шестстотин";
                    break;
                }
            case 7 : {
                    $text .= "седемстотин";
                    break;
                }
            case 8 : {
                    $text .= "осемстотин";
                    break;
                }
            case 9 : {
                    $text .= "деветстотин";
                    break;
                }
        }
        
        switch ($d1) {
            case 1 : {
                    switch ($d0) {
                        case 1 :
                            $text .= " единадесет";
                            break;
                        case 2 :
                            $text .= " дванадесет";
                            break;
                        case 3 :
                            $text .= " тринадесет";
                            break;
                        case 4 :
                            $text .= " четиринадесет";
                            break;
                        case 5 :
                            $text .= " петнадесет";
                            break;
                        case 6 :
                            $text .= " шестнадесет";
                            break;
                        case 7 :
                            $text .= " седемнадесет";
                            break;
                        case 8 :
                            $text .= " осемнадесет";
                            break;
                        case 9 :
                            $text .= " деветнадесет";
                            break;
                        case 0 :
                            $text .= " десет";
                            break;
                    }
                    break;
                }
            case 2 :
                $text .= " двадесет";
                break;
            case 3 :
                $text .= " тридесет";
                break;
            case 4 :
                $text .= " четиридесет";
                break;
            case 5 :
                $text .= " петдесет";
                break;
            case 6 :
                $text .= " шестдесет";
                break;
            case 7 :
                $text .= " седемдесет";
                break;
            case 8 :
                $text .= " осемдесет";
                break;
            case 9 :
                $text .= " деветдесет";
                break;
        }
        
        if ($d1 != 1 && $d0 > 0) {
            switch ($d0) {
                case 1 :
                    if ($g == "m") {
                        $text .= " един";
                        break;
                    }
                    
                    if ($g == "f") {
                        $text .= " една";
                        break;
                    }
                    $text .= " едно";
                    break;
                case 2 :
                    if ($g == "m") {
                        $text .= " два";
                        break;
                    }
                    $text .= " две";
                    break;
                case 3 :
                    $text .= " три";
                    break;
                case 4 :
                    $text .= " четири";
                    break;
                case 5 :
                    $text .= " пет";
                    break;
                case 6 :
                    $text .= " шест";
                    break;
                case 7 :
                    $text .= " седем";
                    break;
                case 8 :
                    $text .= " осем";
                    break;
                case 9 :
                    $text .= " девет";
                    break;
            }
        }
        
        return $this->insAnd(trim($text));
    }
    
    
    /**
     * Превръща цяло не отрицателно число от цифрова в словесна форма.
     *
     * @param int $NUMBER положително число, с максимум 12 цифри
     * @param string $G определя граматическия род: m - мъжки, f - женски, n - среден
     * @return string Словесната форма за числото  в указания род.
     */
    function num2Text($NUMBER, $g = 'n')
    {
        if ($NUMBER == 0)
        return "нула";
        $N = str_pad(abs($NUMBER), 12, "0", STR_PAD_LEFT);
        $l = strlen($N) - 1;
        $N9 = $this->dig2Text($N{$l - 11}, $N{$l - 10}, $N{$l - 9}, "m");
        
        if ($N9 != "") {
            if ($N9 != "един") {
                $N9 = $N9 . "_милиарда ";
            } else
            $N9 = $N9 . "_милиард ";
        }
        $N6 = $this->dig2Text($N{$l - 8}, $N{$l - 7}, $N{$l - 6}, "m");
        
        if ($N6 != "") {
            if ($N6 != "един")
            $N6 = $N6 . "_милиона ";
            else
            $N6 = $N6 . "_милион ";
        }
        $N3 = $this->dig2Text($N{$l - 5}, $N{$l - 4}, $N{$l - 3}, "f");
        
        if ($N3 != "") {
            if ($N3 != "една")
            $N3 = $N3 . "_хиляди ";
            else
            $N3 = "_хиляда ";
        }
        $N0 = $this->dig2Text($N{$l - 2}, $N{$l - 1}, $N{$l}, $g);
        $res = $this->insAnd(trim($N9 . " " . $N6 . " " . $N3 . " " . $N0));
        $res = str_replace("_", " ", $res);
        $res = trim($res);
         
        return $res;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function int_to_words($x)
    {
        $nwords = array(
            "zero",
            "one",
            "two",
            "three",
            "four",
            "five",
            "six",
            "seven",
            "eight",
            "nine",
            "ten",
            "eleven",
            "twelve",
            "thirteen",
            "fourteen",
            "fifteen",
            "sixteen",
            "seventeen",
            "eighteen",
            "nineteen",
            "twenty",
            30 => "thirty",
            40 => "forty",
            50 => "fifty",
            60 => "sixty",
            70 => "seventy",
            80 => "eighty",
            90 => "ninety"
        );
        
        if (!is_numeric($x)) {
            $w = '#';
        } else if (fmod($x, 1) != 0) {
            $w = '#';
        } else {
            if ($x < 0) {
                $w = '';
                $x = -$x;
            } else {
                $w = '';
            }
            
            if ($x < 21) {
                $w .= $nwords[$x];
            } else if ($x < 100) {
                $w .= $nwords[10 * floor($x / 10)];
                $r = fmod($x, 10);
                
                if ($r > 0) {
                    $w .= '-' . $nwords[$r];
                }
            } else if ($x < 1000) {
                $w .= $nwords[floor($x / 100)] . ' hundred';
                $r = fmod($x, 100);
                
                if ($r > 0) {
                    $w .= ' and ' . $this->int_to_words($r);
                }
            } else if ($x < 1000000) {
                $w .= $this->int_to_words(floor($x / 1000)) . ' thousand';
                $r = fmod($x, 1000);
                
                if ($r > 0) {
                    $w .= ' ';
                    
                    if ($r < 100) {
                        $w .= 'and ';
                    }
                    $w .= $this->int_to_words($r);
                }
            } else {
                $w .= $this->int_to_words(floor($x / 1000000)) . ' million';
                $r = fmod($x, 1000000);
                
                if ($r > 0) {
                    $w .= ' ';
                    
                    if ($r < 100) {
                        $word .= 'and ';
                    }
                    $w .= $this->int_to_words($r);
                }
            }
        }
        
        return $w;
    }
    
    
    /**
     * Входна фунция
     * @param int $num Сума за превръщане
     * @param string $lg Език на който да е изписан текста
     * @param boolean $displayCurrency Дали да върне валутата
     * @return text $text подадената сума изписана с думи
     */
    function asCurrency($num, $lg = NULL, $displayCurrency = TRUE, $showCurrencyCode = NULL)
    {
        // Ако не е зададен език, взима се текущия от сесията
    	if(empty($lg)){
        	$lg = core_Lg::getCurrent();
        }
        
    	$num = round($num, 2);
    	
    	// Дали да показваме валутата в края на сумата в думи
        if($displayCurrency) {
        	$numBgn = " лева";
        	$centBgn = " стотинки";
        	$numEuro = " EURO";
        	$centEuro = " CENTS";
        } else {
        	$numBgn = '';
        	$centBgn = '';
        	$numEuro = '';
        	$centEuro = '';
        }
        
        $Double = cls::get('type_Double', array('params' => array('decimals' => 2)));
       
        if ($lg == "bg") {
        	$numCur = $numBgn;
        	$andStr = 'и';
        	$centCur = $centBgn;
        	$text = $this->num2Text((int) $num, 'm');
        	
        	// Заобикаляне на проблема с двойното `и`
        	$text = str_replace(' и и ', ' и ', $text);
        } else {
        	$numCur = $numEuro;
        	$andStr = 'and';
        	$centCur = $centEuro;
        	$text = $this->int_to_words((int) $num);
        }
        
        $text .= $numCur;
        $cent = abs($num - (int) $num);
        $cents = $Double->toVerbal($cent);
        
        if($showCurrencyCode){
            $text .= " <span class='cCode'>{$showCurrencyCode}</span>";
        }
		
        if ($cent > 0){
        	$text .= " {$andStr} {$cents}" . $centCur;
        }
       
        if($num < 0){
        	if ($lg == "bg") {
        		$text = "минус" . " " . $text;
        	} else {
        		$text = "minus" . " " . $text;
        	}
        }
        
        return $text;
    }
}
