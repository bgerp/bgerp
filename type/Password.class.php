<?php

defIfNot('EF_PASS_NO_CHANGE', 'no_change');
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
        
        if($value) $value = EF_PASS_NO_CHANGE;
        
        if(! ($this->params['autocomplete'] == 'autocomplete' || $this->params['autocomplete'] == 'on') || !isDebug() ) {
            $attr['autocomplete'] = 'off';
        }

        $attr['onfocus'] = "this.select();";
       
        return parent::renderInput_($name, $value, $attr);
    }


    /**
     * Конвертира от вербална стойност
     */
    function fromVerbal($value)
    {
        if(!isset($value) || $value == EF_PASS_NO_CHANGE) return NULL;
        
//        if(strpos($value, substr(EF_PASS_NO_CHANGE, 0, 1)) !== FALSE) {
//            $this->error = 'Недопустими символи в паролата. Въведете я пак';
//
//            return FALSE;;
//        }

        return $value;
    }
    

    /**
     * Превръща в mySQL тойност, подходяща за insert/update заявка
     */
    function toMysql($value, $db, $notNull, $defValue)
    { 
        if($value === NULL) return NULL;

        return parent::toMysql($value, $db, $notNull, $defValue);
    }

    
    /**
     *  @todo Чака за документация...
     */
    function toVerbal($value)
    {
        return $value ? '********' : '';
    }
}