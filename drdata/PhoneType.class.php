<?php


/**
 * Клас 'drdata_PhoneType' - тип за телефонен(ни) номера
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
class drdata_PhoneType extends type_Varchar
{
	
	/**
	 * Параметър определящ максималната широчина на полето
	 */
	var $maxFieldSize = 80;
    
	
    /**
     * Връща подадения номер като стринг като пълен номер
     * 
     * @param string $number - Номера
     * @param mixed $arrayKey - Ако трябва да връща само един от номерата
     * 
     * @return string $numStr - Номера в пълен формат
     */
    public static function getNumberStr($number, $arrayKey = FALSE, $prefix = '+')
    {
        // Вземаме номера
        $numArr = drdata_PhoneType::toArray($number);
        
        // Ако не е валиден номер
        if (!$numArr || !count($numArr)) {
            
            return $number;
        }
        
        // Ако ще се връщат всички номера
        if ($arrayKey === FALSE) {
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
     * @param   string  $number     Списъка с номера
     * @param   string  $new        Новия номер
     * @param   string  $mode       Режим на добавяне - отпред/отзад - prepend/append
     * @param   string  $devider    Раздлител между номерата
     */
    public static function insert($numbers, $new, $mode = 'append', $devider = ',')
    {
        $nubersStr = self::getNumberStr($numbers);
        $newStr    = self::getNumberStr($new, 0);

        if(strpos($nubersStr, $newStr) === FALSE) {
            if($mode == 'append') {
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
        if($this->params['type'] == 'fax') {
            $this->maxFieldSize = 14;
        }

        if(isset($this->params[0])) {
            $this->maxFieldSize = $this->params[0];
        }

        if(isset($this->params['size'])) {
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
        if (!is_object($numObj)) return $numObj;
        
        // Генерираме пълния номер
        $callerNumStr = $phoneCodePrefix . $numObj->countryCode . $numObj->areaCode . $numObj->number;
        
        return $callerNumStr;
    }
    
    
    /**
     * Оправя телефонните номера
     */
    function toVerbal_($telNumber)
    {   
        $telNumber = trim($telNumber);

        if(!$telNumber) return NULL;
        
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

        if ($parsedTel == FALSE) {

            return "<span class='red' title='" . tr('Неразпознаваем телефонен номер||Unrecognizable phone number') . "'>{$telNumber}</span>";
        } else {
            $res = new ET();
            $value = '';

            foreach($parsedTel as $t) {

                $res->append($add);

                $value = '';

                if($t->countryCode) {
                    $value .= $t->countryCode;
                }

                if($t->areaCode) {
                    $value .= $t->areaCode;
                }

                if($t->number) {
                    $value .= $t->number;
                }

                $attr = array();

                if(($t->country != 'Unknown') && ($t->area != 'Unknown') && $t->area && $t->country) {
                    $attr['title'] = "{$t->country}, {$t->area}";
                } elseif(($t->country != 'Unknown') && $t->country) {
                    $attr['title'] = "{$t->country}";
                }
                
                $title = $t->original;
                
                //$res->append(ht::createLink($title, 'tel:00'. $value, NULL, $attr));
                $res->append(self::getLink($title, $value, FALSE, $attr));

                if($t->internal) {
                    $res->append(tr('вътр.') . $t->internal) ;
                }

                $add = ", ";
            }
        }

        return $res;
    }


    /**
     * Конвертира списък от телефонни номера до масив
     *
     * @param string $str
     * @param array $params
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
     * @param varchar $verbal
     * @param drdata_PhoneType $canonical
     * @param boolean $isFax
     */
    static public function getLink_($verbal, $canonical, $isFax = FALSE, $attr = array())
    {
    	
       if($isFax) { 
	  		$res = ht::createLink($verbal, NULL, NULL, $attr); 
	   } else {
		    $res = ht::createLink($verbal, "tel:00" . $canonical, NULL, $attr);     			
	   }
	   
	   return $res;
    }
}
