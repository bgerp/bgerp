<?php

/**
 * Клас  'type_Keylist' - Списък от ключове към редове от MVC модел
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
 */
class type_Keylist extends core_Type {
    
    
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
        
        $vals = explode($value{0}, $value);
        
        foreach($vals as $v) {
            if($v) {
                $res .= ($res?", ":'') . $this->getVerbal($v);
            }
        }
        
        return $res;
    }
    
    
    /**
     *  Връща вербалната стонкост на k
     */
    function getVerbal($k)
    {
        if(! round($k) > 0) return '';
        
        if($this->params['mvc']) {
            
            $mvc = &cls::get($this->params['mvc']);
            
            if(($part = $this->params['select']) && $part != '*') {
                
                $rec = $mvc->fetch($k);
                
                return $mvc->getVerbal($rec, $part);
            } else {
                $value = $mvc->getTitleById($k);
            }
        } elseif($this->params['function']) {
        
        } elseif($this->suggestions) {
            $value = $this->suggestions[$k];
        }
        
        return $value;
    }
    
    
    /**
     * Ако получи списък, вместо keylist, и в същото време
     * има select = конкретно поле от и mvc
     * @todo: да се направи конвертирането
     */
    function toMysql($value, $db)
    {
        return parent::toMysql($value, $db);
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function renderInput_($name, $value="", $attr = array())
    {
        // Ако няма списък с предложения - установяваме го
        if(!$this->suggestions) {
            if($select = $this->params['select']) {
                $mvc = &cls::get($this->params['mvc']);
                $query = $mvc->getQuery();
                
                if($select != "*") {
                    $query->show($select);
                    $query->show('id');
                    $query->orderBy($select);
                }
                
                // Ако имаме метод, за подготвяне на заявката - задействаме го
                if($onPrepareQuery = $this->params['prepareQuery']) {
                    cls::callFunctArr($onPrepareQuery, array($this, $query));
                }
                
                while($rec = $query->fetch()) {
                    if($select != "*") {
                        $this->suggestions[$rec->id] = $mvc->getVerbal($rec, $select);
                    } else {
                        $this->suggestions[$rec->id] = $mvc->getTitleById($rec->id);
                    }
                }
            }
        }
        
        if(!$value) {
            $values = array();
        } else {
            $values = explode($value{0}, trim($value, $value{0}));
        }
        
        $attr['type'] = 'checkbox';
        
        // Определяме броя на колоните, ако не са зададени.
        $col = $this->params['columns']?$this->params['columns']:
        min(max(4, $this->params['maxColumns']),
        round(sqrt(max(0, count($this->suggestions)+1))));
        
        if( $col > 1 ) {
            $tpl = "<table class='keylist'><tr>";
            
            for($i = 1; $i<=$col; $i++) {
                $tpl .= "<td valign=top>[#OPT" . ($i-1) . "#]</td>";
            }
            
            $tpl = new ET($tpl . "</tr></table>");
        } else {
            
            $tpl = new ET("[#OPT0#]");
            $tpl->append("", "OPT0");
        }
        
        $i = 0;
        
        if(count($this->suggestions)) {
            foreach($this->suggestions as $key => $v) {
                $attr['id'] = $name . "_" . $key;
                $attr['name'] = $name . "[{$key}]";
                $attr['value'] = $key;
                
                if(in_array($key, $values)) {
                    $attr['checked'] = 'checked';
                } else {
                    unset($attr['checked']);
                }
                
                $cb = ht::createElement('input', $attr);
                $cb->append("<label  for=\"" . $attr['id'] . "\">{$v}</label><br>");
                
                $tpl->append($cb, 'OPT'.($i%$col));
                
                $i++;
            }
        } else {
            for($i = 1; $i<=$col; $i++) {
                $tpl->append("", 'OPT'.$i);
            }
        }
        
        return $tpl;
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function fromVerbal($value)
    {
        if(!is_array($value) || !$value) return "";
        
        if(count($value)) {
            foreach($value as $id => $val)
            {
                if(!ctype_digit(trim($id))) {
                    $this->error = "Некоректен списък $id ";
                    
                    return FALSE;
                }
                
                $res .= "|" . $id;
            }
            $res = $res . "|";
        }
        
        return $res;
    }
    
    
    /**
     * Преобразува keylist към масив
     */
    function toArray($klist)
    {
        if(is_array($keylist)) {
            
            return $keylist;
        }
        
        if(empty($klist)) {
            return array();
        }
        
        $kArr = explode('|', $klist);
        
        foreach($kArr as $key) {
            if($key !== '') {
                $resArr[$key] = $key;
            }
        }
        
        return $resArr;
    }
    
    
    /**
     * Проверява дали ключът присъства в дадения keylist
     */
    function isIn($key, $list)
    {
        return strpos($list, '|' . $key . '|') !== FALSE;
    }
}