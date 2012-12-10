<?php



/**
 * Служебна константа, за стойност на инпута на паролата
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
        
        // Само за дебъг
        // !isDebug() || $attr['title'] = $value;

        if($value && !$this->params['allowEmpty']) {
            $value = EF_PASS_NO_CHANGE;
            $attr['onfocus'] = "if(this.value == '" . EF_PASS_NO_CHANGE . "') this.select();";
        } else {
            $value = '';
        }
        
        if($value || $this->params['autocomplete'] == 'off') {
            $attr['autocomplete'] = 'off';
        }
                
        return parent::renderInput_($name, $value, $attr);
    }
    
    
    /**
     * Конвертира от вербална стойност
     */
    function fromVerbal($value)
    {
        if(!isset($value) || $value == EF_PASS_NO_CHANGE) return NULL;
                
        return $value;
    }
    
    
    /**
     * Превръща в mySQL подходяща за insert/update заявка
     */
    public function toMysql($value, $db, $notNull, $defValue)
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