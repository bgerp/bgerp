<?php


/**
 * Клас 'drdata_PhoneType' - тип за телефонен(ни) номера
 *
 *
 * @category  vendors
 * @package   drdata
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class drdata_PhoneType extends type_Varchar
{
    /**
     * Параметър определящ максималната широчина на полето
     */
    public $maxFieldSize = 80;
    
    
    /**
     * Константа за текста за неразпознат номер
     */
    const UNRECOGNIZED_TEXT = 'Неразпознаваем телефонен номер||Unrecognizable phone number';
    
    
    /**
     * Инициализиране на типа
     */
    public function init($params = array())
    {
        setIfNot($params['params']['inputmode'], 'tel');
        parent::init($params);
    }
    
    /**
     * Връща подадения номер като стринг като пълен номер
     *
     * @param string $number   - Номера
     * @param mixed  $arrayKey - Ако трябва да връща само един от номерата
     *
     * @return string $numStr - Номера в пълен формат
     */
    public static function getNumberStr($number, $arrayKey = false, $prefix = '+')
    {
        // Вземаме номера
        $numArr = drdata_PhoneType::toArray($number);
        
        // Ако не е валиден номер
        if (!$numArr || !countR($numArr)) {
            
            return $number;
        }
        
        // Ако ще се връщат всички номера
        if ($arrayKey === false) {
            foreach ($numArr as $num) {
                
                // Вземаме пълния стринг за номера
                $numStr = static::getNumStrFromObj($num);
                
                $resNumStr .= ($resNumStr) ? ', ' . $numStr : $numStr;
            }
        } else {
            $resNumStr = static::getNumStrFromObj($numArr[$arrayKey], $prefix);
        }
        
        return $resNumStr;
    }
    
    
    /**
     * Добавя еднократно новият номер към списъка с номера
     *
     * @param string $number  Списъка с номера
     * @param string $new     Новия номер
     * @param string $mode    Режим на добавяне - отпред/отзад - prepend/append
     * @param string $devider Раздлител между номерата
     */
    public static function insert($numbers, $new, $mode = 'append', $devider = ',')
    {
        $nubersStr = self::getNumberStr($numbers);
        $newStr = self::getNumberStr($new, 0);
        
        if (strpos($nubersStr, $newStr) === false) {
            if ($mode == 'append') {
                $numbers .= ', ' . $new;
            } else {
                $numbers = $new . ', ' . $numbers;
            }
        }
        
        return $numbers;
    }
    
    
    /**
     * Рендиране на input-поле
     */
    public function renderInput_($name, $value = '', &$attr = array())
    {
        if ($this->params['type'] == 'fax') {
            $this->maxFieldSize = 14;
        }
        
        if (isset($this->params[0])) {
            $this->maxFieldSize = $this->params[0];
        }
        
        if (isset($this->params['size'])) {
            $this->maxFieldSize = $this->params['size'];
        }
        
        return parent::renderInput_($name, $value, $attr);
    }
    
    
    /**
     * Връща пълния номер от подадения обект
     *
     * @param object $numObj - Обект, генериран от drdata_PhoneType
     *
     * @return string $callerNumStr - Стринг с пълния номер
     */
    public static function getNumStrFromObj($numObj, $phoneCodePrefix = '+')
    {
        // Ако не е обект, връщаме
        if (!is_object($numObj)) {
            
            return $numObj;
        }
        
        // Генерираме пълния номер
        $callerNumStr = $phoneCodePrefix . $numObj->countryCode . $numObj->areaCode . $numObj->number;
        
        return $callerNumStr;
    }
    
    
    /**
     * Оправя телефонните номера
     */
    public function toVerbal_($telNumber)
    {
        $telNumber = trim($telNumber);
        
        if (!$telNumber) {
            
            return;
        }
        
        if (Mode::is('text', 'plain') || Mode::is('text', 'pdf') || Mode::is('text', 'xhtml')) {
            
            return $telNumber;
        }
        
        $conf = core_Packs::getConfig('drdata');
        
        $desktop = $conf->TEL_LINK_WIDE;
        $mobile = $conf->TEL_LINK_NARROW;
        
        if ($desktop == 'none' && Mode::is('screenMode', 'wide')) {
            
            return $telNumber;
        }
        
        if ($mobile == 'none' && Mode::is('screenMode', 'narrow')) {
            
            return $telNumber;
        }
        
        $parsedTel = static::toArray($telNumber, $this->params);
        
        $telNumber = parent::toVerbal_($telNumber);
        
        if ($parsedTel == false) {
            
            return "<span class='red' title='" . tr(self::UNRECOGNIZED_TEXT) . "'>{$telNumber}</span>";
        }
        $res = new ET();
        $value = '';
        
        foreach ($parsedTel as $t) {
            $res->append($add);
            
            $value = '';
            
            if ($t->countryCode) {
                $value .= $t->countryCode;
            }
            
            if ($t->areaCode) {
                $value .= $t->areaCode;
            }
            
            if ($t->number) {
                $value .= $t->number;
            }
            
            $attr = array();
            
            if (($t->country != 'Unknown') && ($t->area != 'Unknown') && $t->area && $t->country) {
                $attr['title'] = "{$t->country}, {$t->area}";
            } elseif (($t->country != 'Unknown') && $t->country) {
                $attr['title'] = "{$t->country}";
            }
            
            $title = $t->original;
            
            //$res->append(ht::createLink($title, 'tel:00'. $value, NULL, $attr));
            $res->append(self::getLink($title, $value, false, $attr));
            
            if ($t->internal) {
                $res->append(tr('вътр.') . $t->internal) ;
            }
            
            $add = ', ';
        }
        
        return $res;
    }
    
    
    /**
     * Добавя, ако е необходимо, кода на държавата
     */
    public static function setCodeIfMissing($val, $code)
    {
        $save = $val;

        if (substr($val, 0, 1) != '+' && substr($val, 0, 2) != '00') {
            $params = array('countryPhoneCode' => $code);
            if ($code == '359') {
                $params['areaPhoneCode'] = 2;
            }
            
            // В Италия се запазва нулата
            if ($code != '39') {
                $val = ltrim($val, '0');
            }
            
            $val1 = '00' . $code . $val;
            
            $parsedTel = static::toArray($val, $params);
            
            if (is_array($parsedTel) && countR($parsedTel)) {
                $val = $val1;
            } else {
                $val = $save;
            }
            
            return $val;
        }
    }
    
    
    /**
     * Конвертира списък от телефонни номера до масив
     *
     * @param string $str
     * @param array  $params
     *
     * @return array резултата е същия като на @see drdata_Phones::parseTel()
     */
    public static function toArray($str, $params = array())
    {
        $Phones = cls::get('drdata_Phones');
        $conf = core_Packs::getConfig('drdata');
        
        // Ако не е подаден телефонния код на държавата, ще се използва от конфигурационната константа
        if (!($code = $params['countryPhoneCode'])) {
            $code = $conf->COUNTRY_PHONE_CODE;
        }
        $desktop = $conf->TEL_LINK_WIDE;
        $mobile = $conf->TEL_LINK_NARROW;
        
        if ($desktop == 'none' && Mode::is('screenMode', 'wide')) {
            
            return $str;
        }
        
        if ($mobile == 'none' && Mode::is('screenMode', 'narrow')) {
            
            return $str;
        }
        
        $result = $Phones->parseTel($str, $code, $params['areaPhoneCode']);
        
        return $result;
    }
    
    
    /**
     * Превръщане на телефонните номера и факсове в линкове
     *
     * @param string           $verbal
     * @param drdata_PhoneType $canonical
     * @param bool             $isFax
     */
    public static function getLink_($verbal, $canonical, $isFax = false, $attr = array())
    {
        if ($isFax) {
            $res = ht::createLink($verbal, null, null, $attr);
        } else {
            $res = ht::createLink($verbal, 'tel:00' . $canonical, null, $attr);
        }
        
        return $res;
    }
    
    
    /**
     * Проверява зададената стойност дали е допустима за този тип.
     * Стойността е във вътрешен формат (MySQL)
     * Връща масив с ключове 'warning', 'error' и 'value'.
     * Ако стойността е съмнителна 'warning' съдържа предупреждение
     * Ако стойността е невалидна 'error' съдържа съобщение за грешка
     * Ако стойността е валидна или съмнителна във 'value' може да се
     * съдържа 'нормализирана' стойност
     */
    public function isValid($value)
    { 
        if ($value !== null) {
            $res = array();
            
            if (!empty($value) && isset($this->params['unrecognized'])) {
                $parsedTel = static::toArray($value, $this->params);
                if ($parsedTel == false || !countR($parsedTel)) {
                    if ($this->params['unrecognized'] == 'warning') {
                        $res['warning'] = self::UNRECOGNIZED_TEXT;
                    } else {
                        $res['error'] = self::UNRECOGNIZED_TEXT;
                    }
                }
                
                if ($res['error']) {
                    $this->error = $res['error'];
                }
            }
            
            return $res;
        }
    }
}
