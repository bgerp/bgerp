<?php

cls::load('type_Varchar');


/**
 * Клас 'type_VatType' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    type
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class type_VatType extends type_Varchar
{
    
    /**
     *  Колко символа е дълго полето в базата
     */
    var $dbFieldLen = 13;
    
    
    /**
     *  @todo Чака за документация...
     */
    function isValid($value)
    {
        if(!$value) return;
        
        $Vats = cls::get('common_Vats');
        
        $res = array();
        $res['value'] = $Vats->canonize($value);
        
        $status = $Vats->check($res['value']);
        
        if ($status == 'unknown') {
            $res['warning'] = $status;
        } elseif ($status != 'valid') {
            $res['error'] = $status;
        }
        
        return $res;
    }
}