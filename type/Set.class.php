<?php


/**
 * Клас  'type_Set' - Тип за множество
 *
 *
 * @category  ef
 * @package   type
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 * @todo      да стане като keylist
 */
class type_Set extends core_Type {
    
    
    
    /**
     * MySQL тип на полето в базата данни
     */
    var $dbFieldType = 'text';
    
    
    
    /**
     * Преобразуване от вътрешно представяне към вербална стойност
     */
    function toVerbal($value)
    {
        if(!$value) return NULL;
        
        $vals = explode(',', $value);
        
        foreach($vals as $v) {
            if($v) {
                $res .= ($res?",":'') . $this->getVerbal($v);
            }
        }
        
        return $res;
    }
    
    
    
    /**
     * Връща вербалната стонкост
     */
    function getVerbal($k)
    {
        return $this->params[$k];
    }
    
    
    
    /**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value="", $attr = array())
    {
        $values = explode(",", $value);
        $attr['type'] = 'checkbox';
        $tpl = new ET("[#OPT#]");
        
        foreach($this->params as $key => $v) {
            $attr['id'] = $name . "_" . $key;
            $attr['name'] = $name . "[{$key}]";
            $attr['value'] = $v;
            
            if(in_array($key, $values)) {
                $attr['checked'] = 'checked';
            } else {
                unset($attr['checked']);
            }
            
            $cb = ht::createElement('input', $attr);
            $cb->append("<label for=\"" . $attr['id'] . "\">{$v}</label><br>");
            
            $tpl->append($cb, 'OPT');
        }
        
        return $tpl;
    }
    
    
    
    /**
     * Конвертира от вербална стойност
     */
    function fromVerbal($value)
    {
        if (is_array($value)) {
            $res = implode(',', array_keys($value));
        }
        
        return $res;
    }
}