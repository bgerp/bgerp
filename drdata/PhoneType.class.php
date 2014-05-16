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
class drdata_PhoneType extends type_Varchar {


    /**
     * Оправя телефонните номера
     */
    function toVerbal_($telNumber)
    {
        if(!$telNumber) return NULL;
        
        if (Mode::is('text', 'plain')) {
            
            return $telNumber;
        }
        
        $parsedTel = static::toArray($telNumber, $this->params);

        $telNumber = parent::toVerbal_($telNumber);

        if ($parsedTel == FALSE) {
            return "<font color='red'>{$telNumber}</font>";
        } else {
            $res = new ET();
            $value = '';

            foreach($parsedTel as $t) {

                $res->append($add);

                $value = '';

                if($t->countryCode) {
                    $value .= '' . $t->countryCode;
                }

                if($t->areaCode) {
                    $value .= '' . $t->areaCode;
                }

                if($t->number) {
                    $value .= '' . $t->number;
                }

                $attr = array();

                if(($t->country != 'Unknown') && ($t->area != 'Unknown') && $t->area && $t->country) {
                    $attr['title'] = "{$t->country}, {$t->area}";
                } elseif(($t->country != 'Unknown') && $t->country) {
                    $attr['title'] = "{$t->country}";
                }

                if(!Mode::is('text', 'plain')) {
            		$title = str_replace(' ', '&nbsp;', $t->original);
        		}
        		//bp($this);
        		if($this->params['type'] != 'fax') { 
                	$res->append(ht::createLink($title, "tel:00" . $value, NULL, $attr));
        		} else {
        			$res->append(ht::createLink($title, NULL, NULL, $attr));       			
        		}

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
        
        // Ако не е подаден телефонния код на държавата, ще се използва от конфигурационната константа
        if (!($code = $params['countryPhoneCode'])) {
            
            $conf = core_Packs::getConfig('drdata');
        
            $code = $conf->COUNTRY_PHONE_CODE;
        }
        
        $result = $Phones->parseTel($str, $code);

        return $result;
    }
}
