<?php

cls::load('type_Varchar');

require_once 'php-iban-12/php-iban.php';


/**
 * Клас 'iban_Type' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    iban
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class iban_Type extends type_Varchar
{
    
    
    /**
     *  @todo Чака за документация...
     */
    var $dbFieldLen = 35;
    
    
    /**
     *  @todo Чака за документация...
     */
    function isValid($value)
    {
        if (empty($value)) {
            return;
        }
        
        $res->value = iban_to_machine_format($value);
        
        if (!empty($res->value) && !verify_iban($res->value)) {
            $res->error = 'Невалиден IBAN';
        }
        
        return (array)$res;
    }
}