<?php



/**
 * Клас  'type_Table' - Въвеждане на таблични данни
 *
 * 
 * @category  bgerp
 * @package   type
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 *
 * Атрибути: fields=f1|f2|f3,captions=cq,c2,c3
 */
class type_Table extends type_Blob {
    
    
    /**
     * Стойност по подразбиране
     */
    var $defaultValue = '';
    


    /**
     * Инициализиране на типа
     */
    function init($params = array())
    {
        setIfNot($params['params']['serialize'], 'serialize');
        
        parent::init($params);
    }



    /**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value = "", &$attr = array())
    {

        if(is_string($value)) {
            $value = json_decode($value);
        }

        if(!is_array($value)) {
            $value = array();
        }

        $columns = $this->getColumns();

        foreach($columns as $field => $fObj) {
            $row0 .= "<td class='formTypeTable'>{$fObj->caption}</td>";
            $attr[$field] = array('name' => $name . '[' . $field . '][]');
            if($fObj->width) {
                $attr[$field]['style'] .= ";width:{$fObj->width}";
            }

            $selOpt = $field . '_opt';

            if($this->params[$selOpt]) {
                $opt = explode('|', $this->params[$selOpt]);
                foreach($opt as $o) {
                    $opt[$field][$o] = $o;
                }
                $tpl  .= "<td>" . ht::createSelect($attr[$field]['name'], $opt[$field], NULL, $attr[$field]) . "</td>";
                $row1 .= "<td>" . ht::createSelect($attr[$field]['name'], $opt[$field], $value[$field][0], $attr[$field]) . "</td>";
            } else {
                $tpl  .= "<td>" . ht::createElement('input', $attr[$field]) . "</td>";
                $row1 .= "<td>" . ht::createElement('input', $attr[$field] + array('value' => $value[$field][0])) . "</td>";
            }
        }

        $i = 1;
        $rows = '';
        do{
            $used = FALSE;
            $empty = TRUE;
            $row = '';
            foreach($columns as $field => $fObj) {
                if(isset($opt[$field])) {
                    $row .= "<td>" . ht::createSelect($attr[$field]['name'], $opt[$field], $value[$field][0], $attr[$field]) . "</td>";
                } else {
                    $row .= "<td>" . ht::createElement('input', $attr[$field] + array('value' => $value[$field][$i])) . "</td>";
                }
                if(isset($value[$field][$i])) {
                    $used = TRUE;
                }
                if(strlen($value[$field][$i])) {
                    $empty = FALSE;
                }
            }
            if(!$empty) {
                $rows .= "<tr>{$row}</tr>";
            }
            $i++;
            
        } while($used);
        
        $tpl = str_replace("\"", "\\\"", "<tr>{$tpl}</tr>");
        $tpl = str_replace("\n", "", $tpl);
 
        $id = 'table_' . $name;
        $btn = ht::createElement('input', array('type' => 'button', 'value' => '+ Нов ред', 'onclick' => "dblRow(\"{$id}\", \"{$tpl}\")"));  
        $res = "<table class='listTable typeTable'   style='margin-bottom:5px;' id='{$id}'><tr style=\"background-color:rgba(200, 200, 200, 0.3);\">{$row0}</tr><tr>{$row1}</tr>{$rows}</table>\n{$btn}\n";
        
        $res = new ET($res);
        
        return $res;
    }
    

    /**
     * Връща вербално представяне на стойността на двоичното поле
     */
    function toVerbal($value)
    {
        if(empty($value)) return NULL;
        
        if(is_string($value)) {
            $value = @json_decode($value);
        }
        
        if($this->params['render']) {

            $res = call_user_func_array($this->params['render'], array($value, $this));

            return $res;
        }

        if(is_array($value)) {
            $columns = $this->getColumns();
            
            foreach($columns as $field => $fObj) {
                $row0 .= "<td class='formTypeTable'>{$fObj->caption}</td>";
            }
 
            $i = 0;
            do {
                $isset = FALSE;
                $empty = TRUE;
                $row = '';
                foreach($columns as $field => $fObj) {
                    $row .= "<td>" . $value[$field][$i] . "</td>";
                    if(isset($value[$field][$i])) {
                        $isset = TRUE;
                    }
                    if(strlen($value[$field][$i])) {
                        $empty = FALSE;
                    }
                }

                if(!$empty) {
                    $rows .= "<tr>{$row}</tr>";
                }

                $i++;

            } while($isset);
            
            $res = "<table class='listTable typeTable'><tr>{$row0}</tr>{$rows}</table>";
        }
        
        return $res;
    }


    /**
     * Показва таблицата
     */
    function fromVerbal($value)
    {
        if(is_string($value)) {
            $len = strlen($value);

            if(!$len) return NULL;

            $value = @json_decode($value);
        }
        
        $columns = $this->getColumns();

        if($len && !is_array($value)) {
            $this->error = "Некоректни таблични данни";
                
            return FALSE;
        }
        
        // Нормализираме индексите
        $i = 0;
        $res = array();
        do {
            $isset = FALSE;
            $empty = TRUE;

            foreach($columns as $field => $fObj) {
                if(isset($value[$field][$i])) {
                    $isset = TRUE;
                }
                if(strlen($value[$field][$i])) {
                    $empty = FALSE;
                }
            }

            if(!$empty) {
                foreach($columns as $field => $fObj) {
                    $res[$field][] = $value[$field][$i];
                }
            }

            $i++;

        } while($isset);

 
        return $res;
    }


    /**
     * Връща колоните на таблицата
     */
    function getColumns()
    {
        $colsArr = explode('|', $this->params['columns']);
        if(core_Lg::getCurrent() != 'bg' && $this->params['captionsEn']) {
            $captionArr = explode('|', $this->params['captionsEn']);
        } else {
            $captionArr = explode('|', $this->params['captions']);
        }
        
        $widthsArr = array();
        if(isset($this->params['widths'])) {
            $widthsArr = explode('|', $this->params['widths']);
        }
        
        $res = array();
 
        foreach($colsArr as $i => $c) {
            $obj = new stdClass();
            $obj->caption = $captionArr[$i] ? $captionArr[$i] : $c;
            $obj->width = $widthsArr[$i];
            $res[$c] = $obj;
        }
 
        return $res;
    }
    
    

}