<?php


/**
 * Клас  'type_Enum' - Тип за изброими стойности
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
        if (!isset($value)) return;
        
        if (!isset($this->options[$value])) {
            
            return "{$value}?";
        }
        
        $options = $this->options;
        if (($div = $this->params['groupByDiv'])) {
            $options = ht::groupOptions($this->options, $div);
        }

        $translate = $this->params['translate'] != 'no';
        if (is_object($options[$value])) {
            $res = $options[$value]->title;
        } else {
            $res = $options[$value];
        }
        $res = $translate ? tr($res) : $res;

        return $res;
    }
    
    
    /**
     * Конвертира от вербална стойност
     */
    public function fromVerbal($value)
    {
        // Ако има стойност и тя не е в опциите
        if (isset($value) && !isset($this->options[$value])) {
            
            // Ако стойността е празна да се върне null и да не се сетне грешка
            if(!strlen($value)){
                
                return;
            }
            
            $this->error = 'Недопустима стойност за изброим тип';
            
            return false;
        }
        
        // Ако е празен стринг и е в опциите да не се върне
        if($value === ''){
            
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
        if (countR($this->options)) {
            if (countR($this->options) == 2) {
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
            $translate = $this->params['translate'] != 'no';

            foreach ($options as $id => $title) {
                if (is_object($title)) {
                    $arr[$id] = $title;
                    $arr[$id]->title = html_entity_decode(tr($arr[$id]->title));
                } else {
                    $t1 = explode('<style>', $title);
                    
                    if (countR($t1) == 2) {
                        $arr[$id]->title = tr($t1[0]);
                        $arr[$id]->attr['style'] = $t1[1];
                    } else {
                        $translatedTitle = $translate ? tr($title) : $title;
                        $arr[$id] = html_entity_decode($translatedTitle);
                    }
                }
            }
        }

        parent::setFieldWidth($attr, null, $arr);
        
        if (isset($value) && !isset($arr[$value]) && strlen($value)) {
            if (!isset($arr[''])) {
                $arr = array('' => '') + $arr;
            }
            if (isset($value)) {
                $value = '';
            }
        }
        $countOptions = countR($arr);
        $maxRadio = $this->params['maxRadio'];
        if (!$attr['_isRefresh']){
            if (!strlen($maxRadio) && $maxRadio !== 0 && $maxRadio !== '0' && !$this->params['isHorizontal']) {
                if (arr::isOptionsTotalLenBellowAllowed($arr)) {
                    $maxRadio = 4;
                    $this->params['select2MinItems'] = 10000;
                    $this->params['columns'] = ($countOptions > 3) ? 4 : 3;
                }
            }
        }

        if($countOptions <= $maxRadio){
            if(isset($arr[''])){
                $attr['_isAllowEmpty'] = true;
                if(countR($arr) >= 2){
                    unset($arr['']);
                }
            }
        }
        $tpl = ht::createSmartSelect($arr, $name, $value, $attr, $maxRadio, $this->params['maxColumns'], $this->params['columns']);
        
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
