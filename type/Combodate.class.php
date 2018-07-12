<?php


/**
 * Клас  'type_Combodate' - Представя дати с избираеми по отделно части (Д/М/Г)
 *
 *
 * @category  ef
 * @package   type
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class type_Combodate extends type_Varchar
{
    /**
     * Дължина на полето в mySql таблица
     */
    public $dbFieldLen = 10;     // XXXX-XX-XX
    
    
    /**
     * Клас за <td> елемент, който показва данни от този тип
     */
    public $tdClass = 'centerCol';
    
    
    /**
     * Разделител във вътрешното представяне на датата
     */
    const DIV = '-';
    
    
    /**
     * Символ за запълване на неизвестните части на датата
     */
    const UNKNOWN = '*';
    
    
    /**
     * Получава дата от трите входни стойности
     */
    public function fromVerbal($value)
    {
        if (count($value) == 3) {
            $y = $value[2];
            $m = $value[1];
            $d = $value[0];
            
            $this->prepareOpt();
            
            if (!isset($this->days[$d]) ||
                !isset($this->months[$m]) ||
                !isset($this->years[$y])) {
                $this->error = 'Недопустими данни';
                
                return false;
            }
            
            $date = self::create($y, $m, $d);
            
            // Ако имаме всички данни
            if ($d > 0 && $m > 0 && $y > 0) {
                if (!checkdate($m, $d, $y)) {
                    $this->error = 'Няма толкова дни в посочения месец/година';
                    
                    return false;
                }
                
                // Ако имаме само месеца и годината
            } elseif ($m > 0 && $y > 0) {
            
            
            // Ако имаме само деня и месеца
            } elseif ($d > 0 && $m > 0) {
                if (!checkdate($m, $d, '2004')) {
                    $this->error = 'Няма толкова дни в посочения месец';
                    
                    return false;
                }
                
                // Ако имаме само годината, но без деня, това е ОК
            } elseif ($y > 0 && $d <= 0) {
            
            // Ако нямаме нито една от частите на датата, това е NULL
            } elseif ($y <= 0 && $m <= 0 && $d <= 0) {
                
                return;
            
            // Недостатъчно дани. Генерираме съобщение за грешка
            } else {
                $this->error = 'Недостатъчни данни за датата';
                
                return false;
            }
            
            return $date;
        }
    }
    
    
    /**
     * Показва датата във вербален формат
     *
     * @param string $value
     * @param string|array масив или псевдо-масив от PHP date() съвместими форматиращи полета за
     *                     ден, месец и година
     *
     */
    public function toVerbal($value)
    {
        if (empty($value)) {
            
            return;
        }
        
        list($y, $m, $d) = self::toArray($value);
        
        // Ако имаме всички данни
        if ($d > 0 && $m > 0 && $y > 0) {
            $res = "{$d}-{$m}-{$y}";
        
        // Ако имаме само месеца и годината
        } elseif ($m > 0 && $y > 0) {
            $m = dt::getMonth($m, 'FM');
            
            $res = "{$m}, {$y}";
        
        // Ако имаме само деня и месеца
        } elseif ($d > 0 && $m > 0) {
            $m = dt::getMonth($m, 'FM');
            
            $res = "{$d} {$m}";
        
        // Ако имаме само годината
        } elseif ($y > 0) {
            $res = "{$y} " . tr('г.');
        } else {
            $res = null;
        }
        
        return $res;
    }
    
    
    /**
     * Създава дата от посочените части
     *
     * @param $y int Година
     * @param $m int Месец
     * @param $d int Ден
     *
     * @return string
     */
    public static function create($y, $m, $d)
    {
        static::padParts($y, $m, $d);
        
        $u = self::UNKNOWN;
        
        $div = self::DIV;
        
        $date = "{$y}{$div}{$m}{$div}{$d}";
        
        // Очакваме, че датата е получена във вътрешния ни формат
        expect(preg_match("/^[\{${u}}0-9]{4}{$div}[\{${u}}0-9]{2}{$div}[\{${u}}0-9]{2}$/", $date), $date);
        
        return $date;
    }
    
    
    /**
     * Парсира вътрешното представяне на Combodate
     *
     * @param $cDate string Дата от вида ????-02-23, 2003-02-??, 2003-02-23
     *
     * @return array подреден масив (година, месец, ден)
     */
    public static function toArray($cDate)
    {
        $div = self::DIV;
        
        if ($cDate) {
            list($y, $m, $d) = explode($div, $cDate);
        }
        
        if (strlen($d) > 2) {
            $t = $d;
            $d = $y;
            $y = $t;
        }
        
        static::padParts($y, $m, $d);
        
        return array($y, $m, $d);
    }
    
    
    /**
     * Форматира частите на комбодатата
     */
    public static function padParts(&$y, &$m, &$d)
    {
        if ($d > 0) {
            $d = str_pad($d, 2, '0', STR_PAD_LEFT);
        } else {
            $d = str_pad('', 2, self::UNKNOWN, STR_PAD_LEFT);
        }
        
        if ($m > 0) {
            $m = str_pad($m, 2, '0', STR_PAD_LEFT);
        } else {
            $m = str_pad('', 2, self::UNKNOWN, STR_PAD_LEFT);
        }
        
        if ($y > 0) {
            $y = str_pad($y, 4, '0', STR_PAD_LEFT);
        } else {
            $y = str_pad('', 4, self::UNKNOWN, STR_PAD_LEFT);
        }
    }
    
    
    /**
     * Генерира поле за въвеждане на дата, състоящо се от
     * селектори за годината, месеца и деня
     */
    public function renderInput_($name, $value = '', &$attr = array())
    {
        $div = self::DIV;
        
        if (is_array($value)) {
            list($d, $m, $y) = $value;
        } else {
            list($y, $m, $d) = self::toArray($value);
        }
        
        $this->prepareOpt();
        
        $tpl = ht::createSelect($name . '[]', $this->days, $d, $attr);
        $tpl->append(ht::createSelect($name . '[]', $this->months, $m, $attr));
        $tpl->append(ht::createSelect($name . '[]', $this->years, $y, $attr));
        $tpl = new ET('<span style="white-space:nowrap;">[#1#]</span>', $tpl);
        
        return $tpl;
    }
    
    
    /**
     * Подготвя опциите за дните, месеците и годините
     */
    public function prepareOpt()
    {
        if ($this->days) {
            
            return;
        }
        
        $y = 0;
        $m = 0;
        $d = 0;
        
        static::padParts($y, $m, $d);
        
        // Подготовка на дните
        $this->days = array($d => '');
        for ($i = 1; $i <= 31; $i++) {
            $this->days[$i] = $i;
        }
        
        // Подготовка на месеците
        $this->months = array($m => '') + dt::getMonthOptions('FM');
        
        // Подготовка на годините
        $min = $this->params['minYear'] ? $this->params['minYear'] : 1900;
        $max = $this->params['maxYear'] ? $this->params['maxYear'] : 2030;
        $cur = date('Y');
        for ($i = $max; $i > $cur; $i--) {
            $this->years[$i] = $i;
        }
        $this->years[$y] = '';
        for ($i = $cur; $i >= $min; $i--) {
            $this->years[$i] = $i;
        }
    }
}
