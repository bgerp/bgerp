<?php


/**
 * Клас 'drdata_PhoneType' - тип за телефонен(ни) номера
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    drdata
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class drdata_PhoneType extends type_Varchar {
    
    
    /**
     * Оправя телефонните номера
     */
    function toVerbal($telNumber)
    {
        if(!$telNumber) return NULL;
        
        $Phones = cls::get('drdata_Phones');
        
        $parsedTel = $Phones->parseTel($telNumber, '359');
        
        $telNumber = parent::toVerbal($telNumber);
        
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
                    $value .= ''. $t->areaCode;
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

                $title = str_replace(' ', '&nbsp;', $t->original);

                $res->append(ht::createLink($title, "tel:+" . $value, NULL, $attr));
                
                if($t->internal) {
                    $res->append( tr('вътр.') . $t->internal) ;
                }
                
                $add = ", ";
            }
        }
        
        return $res;
    }
}
