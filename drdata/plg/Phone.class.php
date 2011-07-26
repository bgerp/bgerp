<?php


/**
 * Клас 'drdata_plg_Phone' -
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
class drdata_plg_Phone extends core_Plugin {
    
    
    /**
     *  @todo Чака за документация...
     */
    function on_PhoneValidate(&$invoker, $telNumber, &$result)
    {
        
        $Phones = cls::get('drdata_Phones');
        $parsedTel = $Phones->parseTel($telNumber, '359');
        
        if ($parsedTel == FALSE) {
            $result['error'] = "Некоректен номер телефон";
        } else {
            foreach($parsedTel as $t) {
                
                if($result['value']) {
                    $result['value'] .= ', ';
                }
                
                if($t->countryCode) {
                    $result['value'] .= '00' . $t->countryCode;
                }
                
                if($t->areaCode) {
                    $result['value'] .= ' '. $t->areaCode;
                }
                
                if($t->number) {
                    $result['value'] .= ' ' .$t->number;
                }
                
                if($t->internal) {
                    $result['value'] .= ' ' . tr('вътр.') . $t->internal;
                }
            }
        }
    }
}