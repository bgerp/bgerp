<?php


/**
 * Клас  'type_Table' - Въвеждане на таблични данни
 *
 *
 * @category  bgerp
 * @package   type
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 *
 * Атрибути: fields=f1|f2|f3,captions=cq,c2,c3
 */
class type_Table extends type_Blob
{
    /**
     * Стойност по подразбиране
     */
    public $defaultValue = '';
    
    
    /**
     * Индивидуални полета, в които има грешки
     */
    public $errorFields = array();
    
    
    /**
     * Инициализиране на типа
     */
    public function init($params = array())
    {
        setIfNot($params['params']['serialize'], 'serialize');
        
        parent::init($params);
    }
    
    
    /**
     * Рендира HTML инпут поле
     */
    public function renderInput_($name, $value = '', &$attrDiv = array())
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }
        
        if (!is_array($value)) {
            $value = array();
        }
        
        $columns = $this->getColumns();
        $opt = array();
        foreach ($columns as $field => $fObj) {
            if (empty($this->params['noCaptions'])) {
                $row0 .= "<td class='formTypeTable'>{$fObj->caption}</td>";
            }
            
            $attr[$field] = array('name' => $name . '[' . $field . '][]');
            
            // При натискане на ентер да се добавя нов ред
            $attr[$field]['onkeypress'] = "if (event && (event.which == 13)) { if ($(event.target).closest('tr').is(':last-child')) { $('#dblRow_{$name}').click();} $(event.target).closest('tr').nextAll('tr').find('td :input').first().focus(); return false;}";
            
            if ($fObj->width) {
                $attr[$field]['style'] .= ";width:{$fObj->width}";
            }
            
            $selOpt = $field . '_opt';
            $suggestOpt = $field . '_sgt';
            $readOnlyFld = $field . '_ro';
            
            if ($this->params[$selOpt]) {
                if (is_string($this->params[$selOpt])) {
                    $opt = explode('|', $this->params[$selOpt]);
                    foreach ($opt as $o) {
                        $opt[$field][$o] = $o;
                    }
                } else {
                    $opt[$field] = $this->params[$selOpt];
                }
                $tpl .= '<td>' . ht::createSelect($attr[$field]['name'], $opt[$field], null, $attr[$field]) . '</td>';
                $row1 .= '<td>' . ht::createSelect($attr[$field]['name'], $opt[$field], strip_tags($value[$field][0]), $attr[$field]) . '</td>';
            } elseif ($this->params[$suggestOpt]) {
                if (!is_array($this->params[$suggestOpt])) {
                    $sgt = (strpos($this->params[$suggestOpt], '=') !== false) ? arr::make($this->params[$suggestOpt]) : explode('|', $this->params[$suggestOpt]);
                } else {
                    $sgt = $this->params[$suggestOpt];
                }
                
                foreach ($sgt as $o) {
                    $o1 = strip_tags($o);
                    $sgt[$field][$o1] = $o1;
                }
                
                $datalistTpl = ht::createDataList("{$name}List", $sgt[$field]);
                $attr[$field]['list'] = "{$name}List";
                $tpl .= '<td>' . ht::createCombo($attr[$field]['name'], null, $attr[$field], $sgt[$field]) . '</td>';
                
                if ($this->params[$readOnlyFld] == 'readonly' && isset($value[$field][0]) && empty($this->errorFields[$field][0])) {
                    $row1 .= '<td>' . ht::createElement('input', $attr[$field] + array('class' => 'readonlyInput', 'style' => 'float:left;text-indent:2px', 'readonly' => 'readonly', 'value' => strip_tags($value[$field][0]))) . '</td>';
                } else {
                    $row1 .= '<td>' . ht::createCombo($attr[$field]['name'], $value[$field][0], $attr[$field] + $this->getErrorArr($field, 0), array('' => '') + $sgt[$field]) . '</td>';
                }
            } else {
                $tpl .= '<td>' . ht::createElement('input', $attr[$field]) . '</td>';
                
                if ($this->params[$readOnlyFld] == 'readonly' && isset($value[$field][0]) && empty($this->errorFields[$field][0])) {
                    $row1 .= '<td>' . ht::createElement('input', $attr[$field] + array('class' => 'readonlyInput', 'style' => 'float:left;text-indent:2px', 'readonly' => 'readonly', 'value' => strip_tags($value[$field][0]))) . '</td>';
                } else {
                    $row1 .= '<td>' . ht::createElement('input', $attr[$field] + array('value' => $value[$field][0]) + $this->getErrorArr($field, 0)) . '</td>';
                }
            }
        }
        
        $i = 1;
        $rows = '';
        do {
            $used = false;
            $empty = true;
            $row = '';
            foreach ($columns as $field => $fObj) {
                if (isset($opt[$field])) {
                    $row .= '<td>' . ht::createSelect($attr[$field]['name'], $opt[$field], strip_tags($value[$field][$i]), $attr[$field]) . '</td>';
                } else {
                    $readOnlyFld = $field . '_ro';
                    if ($this->params[$readOnlyFld] == 'readonly' && isset($value[$field][$i]) && empty($this->errorFields[$field][$i])) {
                        $row .= '<td>' . ht::createElement('input', $attr[$field] + array('class' => 'readonlyInput', 'style' => 'float:left;text-indent:2px', 'readonly' => 'readonly', 'value' => strip_tags($value[$field][$i]))) . '</td>';
                    } else {
                        $row .= '<td>' . ht::createElement('input', $attr[$field] + array('value' => strip_tags($value[$field][$i])) + $this->getErrorArr($field, $i)) . '</td>';
                    }
                }
                if (isset($value[$field][$i])) {
                    $used = true;
                }
                if (strlen($value[$field][$i])) {
                    $empty = false;
                }
            }
            if (!$empty) {
                $rows .= "<tr>{$row}</tr>";
            }
            $i++;
        } while ($used);
        
        $tpl = str_replace('"', '\\"', "<tr>{$tpl}</tr>");
        $tpl = str_replace("\n", '', $tpl);
        
        $id = 'table_' . $name;
        $btn = ht::createElement('input', array('id' => 'dblRow_' . $name, 'type' => 'button', 'value' => '+ ' . tr('Нов ред||Add row'), 'onclick' => "dblRow(\"{$id}\", \"{$tpl}\")"));
        
        $attrTable = array();
        $attrTable['class'] = 'listTable typeTable ' . $attrTable['class'];
        $attrTable['style'] .= ';margin-bottom:5px;';
        $attrTable['id'] = $id;
        unset($attrTable['value']);
        
        $res = ht::createElement('table', $attrTable, "<tr style=\"background-color:rgba(200, 200, 200, 0.3);\">{$row0}</tr><tr>{$row1}</tr>{$rows}");
        $res = "<div class='scrolling-holder'>" . $res . '</div>';
        $res .= "\n{$btn}\n";
        $res = ht::createElement('div', $attrDiv, $res);
        
        $res = new ET($res);
        if (is_object($datalistTpl)) {
            $res->append($datalistTpl);
        }
        
        return $res;
    }
    
    
    /**
     * Помощна ф-я сетваща определено поле като грешно
     */
    private function getErrorArr($column, $i)
    {
        $errorArr = array();
        if (is_array($this->errorFields[$column]) && array_key_exists($i, $this->errorFields[$column])) {
            $errorArr['class'] = ' inputError';
            $errorArr['errorClass'] = ' inputError';
        }
        
        return $errorArr;
    }
    
    
    public function isValid($value)
    {
        if (empty($value)) {
            
            return;
        }
        
        if ($columns = $this->params['mandatory']) {
            $value = self::toArray($value);
            $columns = explode('|', $columns);
            $errFld = array();
            foreach ($value as $r => $obj) {
                foreach ($columns as $c) {
                    if (strlen($obj->{$c}) == 0) {
                        $errFld[$c][$r] = true;
                    }
                }
            }
            
            if (count($errFld)) {
                $res['error'] = 'Непопълнено задължително поле';
                $this->errorFields = $res['errorFields'] = $errFld;
                
                return $res;
            }
        }
        
        if ($this->params['validate']) {
            $valueToValidate = @json_decode($value, true);
            $res = call_user_func_array($this->params['validate'], array($valueToValidate, $this));
            
            if (isset($res['errorFields'])) {
                $this->errorFields = $res['errorFields'];
            }
            
            return $res;
        }
    }
    
    
    /**
     * Връща вербално представяне на стойността на двоичното поле
     */
    public function toVerbal($value)
    {
        if (empty($value)) {
            
            return;
        }
        
        if (is_string($value)) {
            $value = @json_decode($value, true);
        }
        
        if ($this->params['render']) {
            $res = call_user_func_array($this->params['render'], array($value, $this));
            
            return $res;
        }
        
        if (is_array($value)) {
            $columns = $this->getColumns();
            $opt = $this->getOptions();
            
            foreach ($columns as $field => $fObj) {
                $row0 .= html_entity_decode("<td class='formTypeTable'>{$fObj->caption}</td>", ENT_QUOTES, 'UTF-8');
            }
            
            $i = 0;
            do {
                $isset = false;
                $empty = true;
                $row = '';
                foreach ($columns as $field => $fObj) {
                    if (isset($opt[$field])) {
                        $row .= '<td>' . $opt[$field][$value[$field][$i]] . '</td>';
                    } else {
                        $row .= '<td>' . $value[$field][$i] . '</td>';
                    }
                    if (isset($value[$field][$i])) {
                        $isset = true;
                    }
                    if (strlen($value[$field][$i])) {
                        $empty = false;
                    }
                }
                
                if (!$empty) {
                    $rows .= "<tr>{$row}</tr>";
                }
                
                $i++;
            } while ($isset);
            
            $res = "<table class='listTable typeTable'><tr>{$row0}</tr>{$rows}</table>";
        }
        
        return $res;
    }
    
    
    /**
     * Показва таблицата
     */
    public function fromVerbal($value)
    {
        if (is_string($value)) {
            $len = strlen($value);
            
            if (!$len) {
                
                return;
            }
            
            $value = @json_decode($value, true);
        }
        
        $columns = $this->getColumns();
        
        if ($len && !is_array($value)) {
            $this->error = 'Некоректни таблични данни';
            
            return false;
        }
        
        // Нормализираме индексите
        $i = 0;
        $res = array();
        do {
            $isset = false;
            $empty = true;
            
            foreach ($columns as $field => $fObj) {
                if (isset($value[$field][$i])) {
                    $isset = true;
                }
                if (strlen($value[$field][$i])) {
                    $empty = false;
                }
            }
            
            if (!$empty) {
                foreach ($columns as $field => $fObj) {
                    $res[$field][] = trim($value[$field][$i]);
                }
            }
            
            $i++;
        } while ($isset);
        
        $res = @json_encode($res);
        
        if ($res == '[]') {
            $res = null;
        }
        
        return $res;
    }
    
    
    /**
     * Връща колоните на таблицата
     */
    public function getColumns()
    {
        $colsArr = explode('|', $this->params['columns']);
        if (core_Lg::getCurrent() != 'bg' && $this->params['captionsEn']) {
            $captionArr = explode('|', $this->params['captionsEn']);
        } else {
            $captionArr = explode('|', $this->params['captions']);
        }
        
        $widthsArr = array();
        if (isset($this->params['widths'])) {
            $widthsArr = explode('|', $this->params['widths']);
        }
        
        $res = array();
        
        foreach ($colsArr as $i => $c) {
            $obj = new stdClass();
            $obj->caption = $captionArr[$i] ? $captionArr[$i] : $c;
            $obj->width = $widthsArr[$i];
            $res[$c] = $obj;
        }
        
        return $res;
    }
    
    
    /**
     * Подготвя опциите
     */
    public function getOptions()
    {
        $opt = array();
        $columns = $this->getColumns();
        foreach ($columns as $field => $fObj) {
            $selOpt = $field . '_opt';
            
            if ($this->params[$selOpt]) {
                if (is_string($this->params[$selOpt])) {
                    $opt = explode('|', $this->params[$selOpt]);
                    foreach ($opt as $o) {
                        $opt[$field][$o] = $o;
                    }
                } else {
                    $opt[$field] = $this->params[$selOpt];
                }
            }
        }
        
        return $opt;
    }
    
    
    /**
     * Преобразува Json представяне на типа към PHP масив
     *
     * [0] object(col1, col2, col3, ...)
     * [1] object(col1, col2, col3, ...)
     * ......
     */
    public static function toArray($value)
    {
        $res = array();
        
        if (!empty($value)) {
            if (is_string($value)) {
                $value = @json_decode($value, true);
            }
            
            $r = 0;
            
            do {
                $empty = true;
                $obj = new StdClass();
                foreach ($value as $f => $arr) {
                    if (isset($arr[$r])) {
                        $obj->{$f} = $arr[$r];
                        $empty = false;
                    }
                }
                if (!$empty) {
                    $res[$r] = $obj;
                }
                $r++;
            } while (!$empty);
        }
        
        return $res;
    }
}
