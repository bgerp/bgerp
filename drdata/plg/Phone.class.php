<?php



/**
 * Клас 'drdata_plg_Phone' -
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
class drdata_plg_Phone extends core_Plugin
{
    
    
    /**
     * @todo Чака за документация...
     */
    public function on_PhoneValidate(&$invoker, $telNumber, &$result)
    {
        $Phones = cls::get('drdata_Phones');
        $parsedTel = $Phones->parseTel($telNumber, '359');
        
        if ($parsedTel == false) {
            $result['error'] = 'Некоректен номер телефон';
        } else {
            foreach ($parsedTel as $t) {
                if ($result['value']) {
                    $result['value'] .= ', ';
                }
                
                if ($t->countryCode) {
                    $result['value'] .= '00' . $t->countryCode;
                }
                
                if ($t->areaCode) {
                    $result['value'] .= ' ' . $t->areaCode;
                }
                
                if ($t->number) {
                    $result['value'] .= ' ' . $t->number;
                }
                
                if ($t->internal) {
                    $result['value'] .= ' ' . tr('вътр.') . $t->internal;
                }
            }
        }
    }
}
