<?php


/**
 * Клас  'type_Set' - Тип за множество
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
 *
 * @todo      да стане като keylist
 */
class type_Set extends core_Type
{
    /**
     * MySQL тип на полето в базата данни
     */
    public $dbFieldType = 'text';
    
    
    /**
     * MySQL тип на полето в базата данни
     */
    protected $readOnly = array();
    
    
    /**
     * Преобразуване от вътрешно представяне към вербална стойност
     */
    public function toVerbal($value)
    {
        if (!isset($value)) {
            
            return;
        }
        
        $vals = explode(',', $value);
        
        foreach ($vals as $v) {
            if (isset($v)) {
                $verb = tr($this->getVerbal($v));
                if (!$verb) {
                    continue;
                }
                $res .= ($res ? ', ' : '') . $verb;
            }
        }
        
        return $res;
    }
    
    
    /**
     * Връща вербалната стойност
     */
    public function getVerbal($k)
    {
        return $this->suggestions[$k];
    }
    
    
    /**
     * Кои стойности да са само за четене
     */
    public function setDisabled($values)
    {
        $this->readOnly = arr::make($values, true);
    }
    
    
    /**
     * Рендира HTML инпут поле
     */
    public function renderInput_($name, $value = '', &$attr = array())
    {
        $values = type_Set::toArray($value);
        $attr['type'] = 'checkbox';
        $attr['class'] .= ' checkbox';
        
        // Определяме броя на колоните, ако не са зададени.
        $maxChars = $this->params['maxChars'];
        $displayHtml = $this->params['displayHtml'];
        $col = type_Keylist::getCol($this->suggestions, $maxChars);
        
        if (count($this->suggestions) < 4) {
            $className .= ' shrinked';
        }
        
        $tpl = new ET("\n<table class='keylist {$className}'>[#OPT#]\n</table>");
        
        $i = 0;
        $html = '';
        $trOpen = true;
        
        if (count($this->suggestions)) {
            if (count($this->suggestions) == 1 && $value === null && $this->params['mandatory'] && empty($displayHtml)) {
                $key = key($this->suggestions);
                $values[$key] = $key;
            }
            foreach ($this->suggestions as $key => $v) {
                
                // Ако имаме група, правим ред и пишем името на групата
                if (is_object($v) && $v->group) {
                    if ($trOpen) {
                        while ($i > 0) {
                            $html .= "\n    <td></td>";
                            $i++;
                            $i = $i % $col;
                        }
                        $html .= '</tr>';
                    }
                    $html .= "\n<tr><td class='keylist-group' colspan='" . $col . "'>" . $v->title . '</td></tr>';
                    $i = 0;
                } else {
                    $attr['id'] = $name . '_' . $key;
                    $attr['name'] = $name . "[{$key}]";
                    $attr['value'] = $key;
                    
                    if (is_array($values)) {
                        if (in_array($key, $values)) {
                            $attr['checked'] = 'checked';
                        } else {
                            unset($attr['checked']);
                        }
                    }
                    
                    // Ако е оказано стойността да е readOnly
                    if (isset($this->readOnly[$key])) {
                        $attr['onclick'] = 'return false;';
                        $attr['readonly'] = 'readonly';
                    }
                    
                    if (0.9 * $maxChars < mb_strlen($v)) {
                        $title = ' title="' . ht::escapeAttr($v) . '"';
                        $v = str::limitLen($v, $maxChars * 1.08);
                    } else {
                        $title = '';
                    }
                    
                    if ($displayHtml) {
                        $v = core_Type::getByName('type_Html')->toVerbal($v);
                    } else {
                        $v = type_Varchar::escape($v);
                    }
                    
                    $cb = ht::createElement('input', $attr);
                    $cb->append("<label {$title} data-colsInRow='" . $col . "' for=\"" . $attr['id'] . '">' . tr($v) . '</label>');
                    
                    // След рендиране на полето, махаме атрибутите за да не се принесат на другите опции
                    if (isset($this->readOnly[$key])) {
                        unset($attr['onclick']);
                        unset($attr['readonly']);
                    }
                    
                    if ($i == 0) {
                        $html .= "\n<tr>";
                        $trOpen = true;
                    }
                    
                    $html .= "\n    <td>" . $cb->getContent() . '</td>';
                    
                    if ($i == $col - 1) {
                        $html .= '</tr>';
                        $trOpen = false;
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
    public function fromVerbal($value)
    {
        // Ако има стойност и тя не е масив, правим я на масив
        if (!is_array($value) && isset($value)) {
            $value = self::toArray($value);
        }
        
        if (is_array($value)) {
            $res = implode(',', array_keys($value));
        }
        
        return $res;
    }
    
    
    /**
     * Преобразува set в масив
     */
    public static function toArray($set)
    {
        if (is_array($set)) {
            
            return $set;
        }
        
        if (!isset($set)) {
            
            return array();
        }
        
        $sArr = explode(',', $set);
        
        $resArr = array();
        
        foreach ($sArr as $set) {
            if ($set !== '') {
                $resArr[$set] = $set;
            }
        }
        
        return $resArr;
    }
    
    
    /**
     * Дали подадения ключ присъства в списъка
     *
     * @param mixed  $key     - ключ
     * @param string $setList - списък
     *
     * @return bool TRUE/FALSE - дали присъства или не
     */
    public static function isIn($key, $setList)
    {
        $arr = self::toArray($setList);
        
        return array_key_exists($key, $arr);
    }
}
