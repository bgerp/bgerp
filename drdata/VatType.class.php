<?php


/**
 * Клас 'drdata_VatType' -
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
class drdata_VatType extends type_Varchar
{
    
    /**
     *  Колко символа е дълго полето в базата
     */
    var $dbFieldLen = 18;
    
    
    /**
     *  @todo Чака за документация...
     */
    function isValid($value)
    {
        if(!$value) return NULL;
        
        $Vats = cls::get('drdata_Vats');
        
        $res = array();
        $res['value'] = strtoupper(trim($value));
        
        $status = $Vats->check($res['value']);
        
        if ($status == 'unknown') {
            $res['warning'] = $status;
        } elseif ($status != 'valid' && $status != 'not_vat') {
            $res['error'] = $status;
        }
        
        return $res;
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function toVerbal($value)
    {
        if(!$value) return NULL;
        
        $Vats = cls::get('drdata_Vats');
        
        $status = $Vats->check($value);
        
        switch($status) {
            case 'unknown': $color = "#339900"; break;
            case 'valid' : $color = "#000000"; break;
            case 'invalid': $color = "#ff3300"; break;
            case 'syntax' : $color = "#990066"; break;
            case 'not_vat' : $color = "#3300ff"; break;
        }
        
        if($status == 'not_vat') {
            
            return "<font color='{$color}'>{$value}</font>";
        } else {
            
            return "<font color='{$color}'>{$value}</font>";
        }
    }
}