<?php

/**
 * Клас  'type_Enum' - Тип за изброими стойности
 *
 *
 * @category   Experta Framework
 * @package    type
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2010 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class type_Enum extends core_Type {
    
    
    /**
     *  @todo Чака за документация...
     */
    var $dbFieldType = 'enum';
    
    
    /**
     *  @todo Чака за документация...
     */
    function toVerbal($value)
    {
        if(empty($value)) return NULL;
        
        if(!isset($this->options[$value])) return "{$value}?";
        
        return $this->options[$value];
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function fromVerbal($value)
    {
        if(!isset($this->options[$value])) {
            $this->error = "Недопустима стойност за изброим тип";
            
            return FALSE;
        }
        
        return $value;
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function renderInput_($name, $value="", $attr = array())
    {
        // TODO: да се махне хака със <style>
        if(count($this->options)) {
            foreach($this->options as $id => $title) {
                $t1 = explode('<style>', $title);
                
                if(count($t1) == 2) {
                    $arr[$id]->title = $t1[0];
                    $arr[$id]->attr['style'] = $t1[1];
                } else {
                    $arr[$id] = $title;
                }
            }
        }
        
        $tpl = ht::createSmartSelect($arr, $name, $value, $attr,
        $this->params['maxRadio'],
        $this->params['maxColumns'],
        $this->params['columns']);
        
        return $tpl;
    }
}