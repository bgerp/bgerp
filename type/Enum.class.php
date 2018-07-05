<?php



/**
 * Клас  'type_Enum' - Тип за изброими стойности
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
class type_Enum extends core_Type
{
    
    
    /**
     * MySQL тип на полето в базата данни
     */
    public $dbFieldType = 'enum';
    
    
    /**
     * Преобразуване от вътрешно представяне към вербална стойност
     */
    public function toVerbal($value)
    {
        if (!isset($value)) {
            return;
        }
        
        if (!isset($this->options[$value])) {
            return "{$value}?";
        }
        
        $options = $this->options;
        if (($div = $this->params['groupByDiv'])) {
            $options = ht::groupOptions($this->options, $div);
        }

        if (is_object($options[$value])) {
            $res = tr($options[$value]->title);
        } else {
            $res = tr($options[$value]);
        }

        return $res;
    }
    
    
    /**
     * Конвертира от вербална стойност
     */
    public function fromVerbal($value)
    {
        if (isset($value) && !isset($this->options[$value])) {
            $this->error = 'Недопустима стойност за изброим тип';
            
            return false;
        }
        
        if ($value === '') {
            return;
        }
        
        return $value;
    }
    
    
    /**
     * Рендира HTML инпут поле
     */
    public function renderInput_($name, $value = '', &$attr = array())
    {
        // TODO: да се махне хака със <style>
        if (count($this->options)) {
            if (count($this->options) == 2) {
                if ($this->options['off'] == 'off' && $this->options['off'] == 'off') {
                    $tpl = "<input type='checkbox' name='{$name}'  class='checkbox'" . ($value == 'on'? ' checked ' : '') . '>';

                    return $tpl;
                }
            }
            $options = $this->options;
            if ($div = $this->params['groupByDiv']) {
                $options = ht::groupOptions($this->options, $div);
            }

            $arr = array();

            foreach ($options as $id => $title) {
                if (is_object($title)) {
                    $arr[$id] = $title;
                    $arr[$id]->title = html_entity_decode(tr($arr[$id]->title));
                } else {
                    $t1 = explode('<style>', $title);
                    
                    if (count($t1) == 2) {
                        $arr[$id]->title = tr($t1[0]);
                        $arr[$id]->attr['style'] = $t1[1];
                    } else {
                        $arr[$id] = html_entity_decode(tr($title));
                    }
                }
            }
        }
        
        parent::setFieldWidth($attr, null, $arr);
 
        $tpl = ht::createSmartSelect(
 
            $arr,
 
            $name,
 
            $value,
 
            $attr,
            $this->params['maxRadio'],
            $this->params['maxColumns'],
            $this->params['columns']
 
        );

        return $tpl;
    }
    
    
    /**
     * Стойност по подразбиране за полетата от тип enum
     */
    public function defVal()
    {
        return key($this->options);
    }
}
