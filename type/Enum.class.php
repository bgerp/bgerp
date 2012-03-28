<?php



/**
 * Клас  'type_Enum' - Тип за изброими стойности
 *
 *
 * @category  all
 * @package   type
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class type_Enum extends core_Type {
    
    
    /**
     * MySQL тип на полето в базата данни
     */
    var $dbFieldType = 'enum';
    
    
    /**
     * Преобразуване от вътрешно представяне към вербална стойност
     */
    function toVerbal($value)
    {
        if(empty($value)) return NULL;
        
        if(!isset($this->options[$value])) return "{$value}?";
        
        return $this->options[$value];
    }
    
    
    /**
     * Конвертира от вербална стойност
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
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value = "", $attr = array())
    {
        // TODO: да се махне хака със <style>
        if(count($this->options)) {
            foreach($this->options as $id => $title) {
                $t1 = explode('<style>', $title);
                
                if(count($t1) == 2) {
                    $arr[$id]->title = tr($t1[0]);
                    $arr[$id]->attr['style'] = $t1[1];
                } else {
                    $arr[$id] = tr($title);
                }
            }
        }
        
        $tpl = ht::createSmartSelect($arr, $name, $value, $attr,
            $this->params['maxRadio'],
            $this->params['maxColumns'],
            $this->params['columns']);
        
        return $tpl;
    }
    
    
    /**
     * Стойност по подразбиране за полетата от тип enum
     */
    function defVal()
    {
        
        return key($this->options);
    }
}