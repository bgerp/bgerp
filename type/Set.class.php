<?php



/**
 * Клас  'type_Set' - Тип за множество
 *
 *
 * @category  all
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
                $res .= ($res ? "," : '') . $this->getVerbal($v);
            }
        }
        
        return $res;
    }
    
    
    /**
     * Връща вербалната стойност
     */
    function getVerbal($k)
    {
        return $this->params[$k];
    }
    
    
    /**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value = "", $attr = array())
    {
        $values = type_Keylist::toArray($value);
        $attr['type'] = 'checkbox';
        
        // Определяме броя на колоните, ако не са зададени.
        $col = $this->params['columns'] ? $this->params['columns'] :
        min(($this->params['maxColumns'] ? $this->params['maxColumns'] : 4),
            round(sqrt(max(0, count($this->suggestions) + 1))));
        
        $tpl = new ET("\n<table class='keylist'>[#OPT#]\n</table>");
        
        $i = 0; $html = ''; $trOpen = TRUE;
        
        if(count($this->suggestions)) {
            foreach($this->suggestions as $key => $v) {
                
                // Ако имаме група, правим ред и пишем името на групата
                if(is_object($v) && $v->group) {
                    if($trOpen) {
                        while($i > 0) {
                            $html .= "\n    <td></td>";
                            $i++;
                            $i = $i % $col;
                        }
                        $html .= '</tr>';
                    }
                    $html .= "\n<tr><td class='keylist-group' colspan='" . $col . "'>" . $v->title . "</td></tr>";
                    $i = 0;
                } else {
                    $attr['id'] = $name . "_" . $key;
                    $attr['name'] = $name . "[{$key}]";
                    $attr['value'] = $key;
                    
                    if(in_array($key, $values)) {
                        $attr['checked'] = 'checked';
                    } else {
                        unset($attr['checked']);
                    }
                    
                    $cb = ht::createElement('input', $attr);
                    $cb->append("<label  for=\"" . $attr['id'] . "\">{$v}</label>");
                    
                    if($i == 0) {
                        $html .= "\n<tr>";
                        $trOpen = TRUE;
                    }
                    
                    $html .= "\n    <td>" . $cb->getContent() . "</td>";
                    
                    if($i == $col -1) {
                        $html .= "</tr>";
                        $trOpen = FALSE;
                    }
                    
                    $i++;
                    $i = $i % $col;
                }
            }
        } else {
            $html = '<tr><td></td></tr>';
        }
        
        $tpl->append($html, 'OPT');
        
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
    
    
    /**
     * Преобразува set в масив
     */
    function toArray($set)
    {
        if (is_array($set)) {
            return $set;
        }
        
        if (empty($set)) {
            return array();
        }
        
        $sArr = explode(',', $set);
        
        foreach($sArr as $set) {
            if($set !== '') {
                $resArr[$set] = $set;
            }
        }
        
        return $resArr;
    }
}