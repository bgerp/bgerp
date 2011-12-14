<?php

/**
 * Клас  'type_Set' - Тип за множество
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
 * @todo       да стане като keylist
 */
class type_Set extends core_Type {
    
    
    /**
     *  @todo Чака за документация...
     */
    var $dbFieldType = 'text';
    
    
    /**
     *  @todo Чака за документация...
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
     *  @todo Чака за документация...
     */
    function getVerbal($k)
    {
        return $this->params[$k];
    }
    
    
    /**
     *  @todo Чака за документация...
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
     *  @todo Чака за документация...
     */
    function fromVerbal($value)
    {
    	if (is_array($value)) {
    		$res = implode(',', array_keys($value));
    	}
        
        return $res;
    }
}