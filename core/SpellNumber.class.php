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
            if (substr($text, $i - 2, 2) != " и")
            $text = substr($text, 0, $i) . " и" . substr($text, $i, $l - $i);
        }
        
        return $text;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function dig2Text($d2, $d1, $d0, $g = "n")
    {
        
        /**
         * @access private
         * @param int $d1 десетична цифра
         * @param int $d2 десетична цифра
         * @param int $d3 десетична цифра
         * @param string $G род
         * @return string Словесната форма за числото образувано от цифрите в указания род
         */
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
        
        return str_replace("_", " ", $this->insAnd(trim($N9 . " " . $N6 . " " . $N3 . " " . $N0)));
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
                $w = 'minus ';
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
    function asCurrency($num, $lg = "bg", $displayCurrency = TRUE)
    {
        $num = round($num, 2);
        
    	// Дали да показваме валутата в края на сумата в думи
        if($displayCurrency) {
        	$numBgn = " лeва";
        	$centBgn = " стотинки";
        	$numEuro = " EURO";
        	$centEuro = " CENTS";
        } else {
        	$numBgn = '';
        	$centBgn = '';
        	$numEuro = '';
        	$centEuro = '';
        }
        	
        if ($lg == "bg") {
        	
        	$text = $this->num2Text((int) $num) . $numBgn;
            $cents = round((($num - (int) $num) * 100));
            
            if ($cents > 0)
            	$text .= " и ," . ($cents) . " " . $centBgn;
            $text = str_replace(" и и ", " и ", $text);
            //$text .= " и " . $this->num2Text($cents) . $centBgn;
            
            return $text;
        } else {
        	
            $text = $this->int_to_words((int) $num) . $numEuro;
            $cents = round((($num - (int) $num) * 100));
            
            if ($cents > 0)
            	$text .= " and ," . $cents. " " . $centEuro;
            //$text .= " and " . $this->int_to_words($cents) . $centEuro;
            
            return $text;
        }
    }
}