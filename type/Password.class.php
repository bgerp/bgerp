<?php

/**
 * Клас  'type_Password' - Тип за парола
 *
 *
 * @category   Experta Framework
 * @package    type
 * @author     Milen Georgiev
 * @copyright  2006-2010 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class type_Password extends type_Varchar {
    
    
    /**
     *  @todo Чака за документация...
     */
    function renderInput_($name, $value="", $attr = array())
    {
        $attr['type'] = 'password';
        
        if($value) $value = '';
        
        if(! ($this->params['autocomplete'] == 'autocomplete' || $this->params['autocomplete'] == 'on') || !isDebug() ) {
            $attr['autocomplete'] = 'off';
        }
        
        return parent::renderInput_($name, $value, $attr);
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function toVerbal($value)
    {
        return '';
    }
}