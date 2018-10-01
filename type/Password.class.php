<?php
/**
 * Клас  'type_Password' - Тип за парола
 *
 *
 * @category  ef
 * @package   type
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class type_Password extends type_Varchar
{
    /**
     * Служебна константа, за стойност на инпута на паролата
     */
    const EF_PASS_NO_CHANGE = 'no_change';
    
    
    /**
     * Рендира HTML инпут поле
     */
    public function renderInput_($name, $value = '', &$attr = array())
    {
        $attr['type'] = 'password';
        
        // Само за дебъг
        // !isDebug() || $attr['title'] = $value;
        if ($this->params['show']) {
            $attr['type'] = 'text';
            $attr['style'] = ';color:#ccc; text-shadow: 0px 0px 5px #444;';
        } elseif ($value && !$this->params['allowEmpty']) {
            $value = self::EF_PASS_NO_CHANGE;
            $attr['onfocus'] = "if(this.value == '" . self::EF_PASS_NO_CHANGE . "') this.select();";
        } else {
            if ($value) {
                $attr['placeholder'] = html_entity_decode('&#x25CF;&#x25CF;&#x25CF;&#x25CF;&#x25CF;&#x25CF;&#x25CF;&#x25CF;&#x25CF;');
            }
            $value = '';
        }
        
        if ($value || $this->params['autocomplete'] == 'off') {
            $attr['autocomplete'] = 'off';
        }
        
        $this->params['noTrim'] = 'noTrim';
        
        $this->maxFieldSize = 10;
        
        return parent::renderInput_($name, $value, $attr);
    }
    
    
    /**
     * Конвертира от вербална стойност
     */
    public function fromVerbal($value)
    {
        if (!isset($value) || $value == self::EF_PASS_NO_CHANGE) {
            
            return;
        }
        
        return $value;
    }
    
    
    /**
     * Превръща в mySQL подходяща за insert/update заявка
     */
    public function toMysql($value, $db, $notNull, $defValue)
    {
        if ($value === null) {
            
            return;
        }
        
        return parent::toMysql($value, $db, $notNull, $defValue);
    }
    
    
    /**
     * Преобразуване от вътрешно представяне към вербална стойност
     */
    public function toVerbal($value)
    {
        return $value ? '********' : '';
    }
}
