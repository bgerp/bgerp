<?php



/**
 * @todo Чака за документация...
 */
defIfNot('EF_PASS_NO_CHANGE', 'no_change');


/**
 * Клас  'type_Password' - Тип за парола
 *
 *
 * @category  ef
 * @package   type
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class type_Password extends type_Varchar {
    
    
    /**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        $attr['type'] = 'password';
        
        if($value) $value = EF_PASS_NO_CHANGE;
        
        if(! ($this->params['autocomplete'] == 'autocomplete' || $this->params['autocomplete'] == 'on') || !isDebug()) {
            $attr['autocomplete'] = 'off';
        }
        
        $attr['onfocus'] = "if(this.value == '" . EF_PASS_NO_CHANGE . "') this.select();";
        
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
     * Превръща в mySQL подходяща за insert/update заявка
     */
    static function toMysql($value, $db, $notNull, $defValue)
    {
        if($value === NULL) return NULL;
        
        return parent::toMysql($value, $db, $notNull, $defValue);
    }
    
    
    /**
     * Преобразуване от вътрешно представяне към вербална стойност
     */
    function toVerbal($value)
    {
        return $value ? '********' : '';
    }
}