<?php


cls::load('type_Varchar');


/**
 * Клас 'gs1_TypeEan' - Тип за баркодовете на продуктите. Проверява
 * дали подаден стринг е Валиден ЕАН8, ЕАН13, ЕАН13+2 или ЕАН13+5 код.
 * При грешно подаден такъв изкарва и подсказка с правилно изчисления код
 *
 *
 * @category  bgerp
 * @package   gs1
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class gs1_TypeEan extends type_Varchar
{
    /**
     * Колко символа е дълго полето в базата
     */
    public $dbFieldLen = 18;
    
    
    /**
     * Празната стойност има смисъл на NULL
     */
    public $nullIfEmpty = true;
    
    
    /**
     * Колко символа е дълго полето в базата
     */
    const AUTO_GENERETE_STRING = 'AUTO';
    
    
    /**
     * Кеш на рейнджа
     */
    private $autoRange = array();
    
    
    /**
     * Инициализиране на обекта
     */
    public function init($params = array())
    {
        parent::init($params);
        $this->params['size'] = $this->params[0] = 18;
        
        // Добавяне на опция за автоматично генериране на ЕАН код
        if (isset($this->params['mvc'])) {
            $mvcName = $this->params['mvc'];
            expect(cls::get($mvcName));
            expect($this->params['field']);
            expect(method_exists($mvcName, 'getEanRange'));
            $range = $mvcName::getEanRange();
            
            if (count($range)) {
                $this->autoRange = $range;
                $this->suggestions = array('' => '', self::AUTO_GENERETE_STRING => tr('Автоматично'));
            }
        }
    }
    
    
    /**
     * Обръща във вътрешен формат
     */
    public function fromVerbal_($value)
    {
        // Ако има рейндж за генерирания баркод
        if (count($this->autoRange)) {
            
            // И е въведена специалната стойност, замества се с автоматичния баркод
            if ($value == self::AUTO_GENERETE_STRING) {
                $value = $this->getAutoNumber($this->params['mvc'], $this->params['field']);
            }
        }
        
        $value = parent::fromVerbal_($value);
        
        return $value;
    }
    
    
    /**
     * Автоматично генериран еан код
     *
     * @return string
     */
    protected function getAutoNumber($mvc, $field)
    {
        expect($range = $mvc::getEanRange());
        expect(count($range));
        
        $min = $range[0];
        $max = $range[1];
        
        // Най-големия баркод
        $query = $mvc::getQuery();
        $query->XPR('maxEan', 'int', "MAX(#{$field})");
        $query->between($field, $min, $max);
        if (!$maxEan = $query->fetch()->maxEan) {
            $maxEan = $min;
        }
        
        // От максималния баркод в посочения рейндж, се маха контролната сума
        // и се инкрементира, докато се получи валиден свободен ЕАН код.
        $maxEan = substr($maxEan, 0, -1);
        $newEan = str::increment($maxEan);
        $newEan = $this->eanCheckDigit($newEan);
        while ($mvc::fetchField(array("#{$field} = '[#1#]'", $newEan))) {
            $newEan = substr($newEan, 0, -1);
            $newEan = str::increment($maxEan);
            $newEan = $this->eanCheckDigit($newEan);
        }
        
        return $newEan;
    }
    
    
    /**
     * Към 12-цифрен номер, добавя 13-та цифра за д''а го направи EAN13 код
     *
     * @param string $digits - 12-те или 7-те цифри на кода
     * @param int    $n      - дали проверяваме за ЕАН8 или ЕАН13, ЕАН13 е по дефолт
     *
     * @return string - правилния ЕАН8 или ЕАН13 код
     */
    public function eanCheckDigit($digits, $n = 13)
    {
        $digits = (string) $digits;
        $oddSum = $evenSum = 0;
        
        foreach (array('even' => '0', 'odd' => '1') as $k => $v) {
            foreach (range($v, $n, 2) as ${"{$k}Num"}) {
                ${"{$k}Sum"} += $digits[${"{$k}Num"}];
            }
        }
        
        // Ако е ЕАН13 умножаваме нечетната сума по три иначе- четната
        ($n == 13) ? $oddSum = $oddSum * 3 : $evenSum = $evenSum * 3;
        $totalSum = $evenSum + $oddSum;
        $nextTen = (ceil($totalSum / 10)) * 10;
        $checkDigit = $nextTen - $totalSum;
        
        return $digits . $checkDigit;
    }
    
    
    /**
     * Проверка за валидност на EAN13 или EAN8 код
     *
     * @param string $value - подадената сума
     * @param int    $n     - дали е ЕАН13 или ЕАН8
     *
     * @return bool TRUE/FALSE
     */
    public function isValidEan($value, $n = 13)
    {
        $digits12 = substr($value, 0, $n - 1);
        $digits13 = $this->eanCheckDigit($digits12, $n);
        $res = ($digits13 == $value);
        
        return $res;
    }
    
    
    /**
     * Връща верен EAN 13 + 2/5, ако е подаден такъв
     *
     * @param string $value - 15 или 18 цифрен баркод
     * @param int    $n     - колко цифри са допълнителните към EAN13
     *
     * @return string $res - Подадения ЕАН13+2/5 код с правилна 13 цифра
     */
    public function eanSCheckDigit($value, $n)
    {
        $digits12 = substr($value, 0, 12);
        $supDigits = substr($value, 13, $n);
        $res = $this->eanCheckDigit($digits12);
        $res .= $supDigits;
        
        return $res;
    }
    
    
    /**
     * Проверка за валидност на първите 13 цифри от 15 или 18
     * цифрен баркод код, дали са валиден EAN13 код
     *
     * @param string $value - EAN код с повече от 13 цифри
     *
     * @return bool TRUE/FALSE
     */
    public function isValidEanS($value)
    {
        $digits13 = substr($value, 0, 13);
        if ($this->isValidEan($digits13)) {
            
            return true;
        }
        
        return false;
    }
    
    
    /**
     * Дефиниция на виртуалния метод на типа, който служи за проверка на данните
     *
     * @return stdClass $res - обект съдържащ информация за валидноста на полето
     */
    public function isValid($value)
    {
        if (!trim($value)) {
            
            return array('value' => '');
        }
        if (count($this->autoRange)) {
            if ($value == self::AUTO_GENERETE_STRING) {
                
                return array('value' => $value);
            }
        }
        
        $res = new stdClass();
        if (preg_match('/[^0-9]/', $value)) {
            $res->error .= 'Полето приема само цифри.';
        } else {
            $code = strlen($value);
            if ($this->params['gln']) {
                $code = 13;
            }
            
            // Взависимост от дължината на стринга проверяваме кода
            switch ($code) {
                case 13:
                    if (!$this->isValidEan($value)) {
                        (!$this->params['gln']) ? $type = 'EAN13' : $type = 'или непълен (13 цифрен) GLN';
                        $res->error = "Невалиден {$type} номер.";
                    }
                    break;
                case 7:
                    $res->value = $this->eanCheckDigit($value, 8);
                    $res->warning = "Въвели сте само 7 цифри. Пълният EAN8 код {$res->value} ли е?";
                    break;
                case 8:
                    if (!$this->isValidEan($value, 8)) {
                        $res->error = 'Невалиден EAN8 номер.';
                    }
                    break;
                case 15:
                    if (!$this->isValidEanS($value)) {
                        $res->value = $this->eanSCheckDigit($value, 2);
                        $res->error = "Невалиден EAN13+2 номер. Пълният EAN13+2 код {$res->value} ли е?";
                    }
                    break;
                case 18:
                    if (!$this->isValidEanS($value)) {
                        $res->value = $this->eanSCheckDigit($value, 5);
                        $res->error = "Невалиден EAN13+5 номер. Пълният EAN13+5 код {$res->value} ли е?";
                    }
                    break;
                case 12:
                    $res->value = $this->eanCheckDigit($value);
                    $res->warning = "Въвели сте само 12 цифри. Пълният EAN13 код {$res->value} ли е?";
                    break;
                default:
                    $res->error = 'Невалиден EAN13 номер. ';
                    $res->error .= "Въведения номер има |*{$code}| цифри.";
                    break;
            }
        }
        
        return (array) $res;
    }
}
