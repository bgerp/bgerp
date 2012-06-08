<?php





/**
 * Клас  'type_Key' - Ключ към ред от MVC модел
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
class type_Key extends type_Int {
    
    
    /**
     * Атрибути на елемента "<TD>" когато в него се записва стойност от този тип
     */
    var $cellAttr = 'align="left"';
    
    
    /**
     * Конвертира стойността от вербална към (int) - ключ към core_Interfaces
     */
    function toVerbal_($value)
    {
        
        if(empty($value)) return NULL;
        
        if($this->params['mvc']) {
            $mvc = &cls::get($this->params['mvc']);
            
            if(($part = $this->params['select']) && $part != '*') {
                
                $rec = $mvc->fetch($value);
                
                $v = $mvc->getVerbal($rec, $part);
                
                return $v;
            } else {
                if($this->params['title']) {
                    $field = $this->params['title'];
                    $value = $mvc->fetchField($value, $field);
                    $value = $mvc->fields[$field]->type->toVerbal($value);
                } else {
                    $value = $mvc->getTitleById($value);
                }
            }
        }
        
        return $value;
    }
    
    
    /**
     * Връща вътрешното представяне на вербалната стойност
     */
    function fromVerbal_($value)
    {
    	$conf = core_Packs::getConfig('core');
    	
        if(empty($value)) return NULL;
        
        $mvc = &cls::get($this->params['mvc']);
        
        setIfNot($maxSuggestions, $this->params['maxSuggestions'], $conf->TYPE_KEY_MAX_SUGGESTIONS);
        
        $options = $this->options;
        
        if(($field = $this->params['select']) && (!count($options))) {
            $options = $mvc->makeArray4select($field);
        }
        
        if(!is_numeric($value)) {
            foreach($options as $id => $v) {
                if (!is_string($v)) {
                    if(!$v->group) {
                        $optionsR[trim($v->title)] = $id;
                    }
                } else {
                    
                    /**
                     * $v (косвено) се сравнява с субмитнатата чрез HTML `<select>` елемент
                     * стойност $value. Оказа се, че (поне при някои браузъри) специалните HTML
                     * символи (`&amp;`, `&nbsp` и пр.) биват декодирани при такъв субмит. Така
                     * ако $v съдържа такива символи, сравнението ще пропадне, въпреки, че
                     * стойностите може да изглеждат визуално еднакви. Напр. ако
                     *
                     *  $v = "Тестов&nbsp;пример"; $value = "Тестов пример"
                     *
                     *  очевидно $v != $value
                     *
                     *  По тази причина декодираме специалните символи предварително.
                     *
                     */
                    $v = html_entity_decode($v, ENT_NOQUOTES, 'UTF-8');
                    $optionsR[trim($v)] = $id;
                }
            }
            
            $value = $optionsR[trim($value)];
        }
        
        $value = (int) $value;
        
        $rec = $mvc->fetch($value);
        
        if(!$rec) {
            $this->error = 'Несъществуващ обект';
            
            return FALSE;
        } else {
            
            return $value;
        }
    }
    
    
    /**
     * Рендира HTML поле за въвеждане на данни чрез форма
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        $conf = core_Packs::getConfig('core');
        
        expect($this->params['mvc']);
        
        $mvc = cls::get($this->params['mvc']);
        
        setIfNot($maxSuggestions, $this->params['maxSuggestions'], $conf->TYPE_KEY_MAX_SUGGESTIONS);
        
        if(!$value) {
            $value = $attr['value'];
        }
        
        if($this->params['select'] || count($this->options)) {
            
            if($this->params['select'] == '*') {
                $field = NULL;
            } else {
                $field = $this->params['select'];
            }
            
            if($this->params['allowEmpty']) {
                $options = array('' => '');
            }
            
            if ($this->params['where']) {
                $where = $this->params['where'];
            }
            
            Debug::startTimer('prepareOPT ' . $this->params['mvc']);
            
            if (!is_array($this->options)) {
                foreach($mvc->makeArray4select($field, $where) as $id => $v) {
                    $options[$id] = $v;
                }
                $handler = md5($field . $where . $this->params['mvc']);
            } else {
                foreach($this->options as $id => $v) {
                    $options[$id] = $v;
                }
                $handler = md5(json_encode($options[$id]));
            }
            
            Debug::stopTimer('prepareOPT ' . $this->params['mvc']);
            
            // Ако трябва да показваме combo-box
            if(count($options) > $maxSuggestions) {
                
                // Генериране на cacheOpt ако не са в кеша
                if(FALSE === ($cacheOpt = (array) json_decode(core_Cache::get('SelectOpt', $handler, 20, array($this->params['mvc']))))) {
                    
                    foreach($options as $key => $v) {
                        
                        if (!is_string($v)) {
                            $title = $v->title;
                        } else {
                            $title = $v;
                        }
                        
                        $vNorm = strtolower(str::utf2ascii(trim($title)));
                        
                        if($cacheOpt[$vNorm]) {
                            
                            $title = "{$title} ({$key})";
                            
                            $vNorm = strtolower(str::utf2ascii(trim("{$title} {$key}")));
                        }
                        
                        if(is_object($v)) {
                            $v->title = $title;
                        } else {
                            $v = $title;
                        }
                        
                        $cacheOpt[$vNorm] = $v;
                    }
                    
                    core_Cache::set('SelectOpt', $handler, json_encode($cacheOpt), 20, array($this->params['mvc']));
                }
                
                if($this->suggestions) {
                    $suggestions = $this->suggestions;
                } else {
                    $suggestions = array_slice($cacheOpt, 0, $maxSuggestions, TRUE);
                }
                
                foreach($suggestions as $key => $v) {
                    
                    $key = is_object($v) ? $v->title : $v;
                    
                    $key = html_entity_decode($key, ENT_NOQUOTES, 'UTF-8');
                    
                    $selOpt[trim($key)] = $v;
                }
                
                $selOpt[$options[$value]] = $options[$value];
                
                $attr['ajaxAutoRefreshOptions'] = "{Ctr:\"type_Key\"" .
                ", Act:\"ajax_GetOptions\", hnd:\"{$handler}\", maxSugg:\"{$maxSuggestions}\"}";
                
                $tpl = ht::createCombo($name, $options[$value], $attr, $selOpt);
            } else {
                
                if(count($options) == 0 && $mvc->haveRightFor('list')) {
                    $msg = "Липсва избор за |* \"" . $mvc->title . "\".";
                    
                    if(!$mvc->fetch("1=1")) {
                        $msg .= " Моля въведете началните данни.";
                    }
                    
                    return new Redirect(array($mvc, 'list'), tr($msg));
                }
                
                $tpl = ht::createSmartSelect($options, $name, $value, $attr,
                    $this->params['maxRadio'],
                    $this->params['maxColumns'],
                    $this->params['columns']);
            }
        } else {
            
            if(method_exists($mvc, 'act_ajax_GetOptions')) {
                $attr['ajaxAutoRefreshOptions'] = "{Ctr:\"{$this->params['mvc']}\"" .
                ", Act:\"ajax_GetOptions\"}";
            }
            
            if($this->params['size']) {
                $attr['size'] = $this->params['size'];
            }
            
            $tpl = ht::createCombo($name, $value, $attr, $this->suggestions);
        }
        
        return $tpl;
    }
    
    
    /**
     * Връща списък е елементи <option> при ajax заявка
     */
    function act_ajax_GetOptions()
    {
        $conf = core_Packs::getConfig('core');
        
        Mode::set('wrapper', 'page_Ajax');
        
        // Приключваме, ако няма заявка за търсене
        $hnd = Request::get('hnd');
        
        $q = Request::get('q');
        
        $q = strtolower(str::utf2ascii(trim($q)));
        
        core_Logs::add('type_Key', NULL, "ajaxGetOptions|{$hnd}|{$q}", 1);
        
        if (!$hnd) {
            return array(
                'error' => 'Липсват допълнителни опции'
            );
        }
        
        setIfNot($maxSuggestions, Request::get('maxSugg', 'int'), $conf->TYPE_KEY_MAX_SUGGESTIONS);
        
        $select = new ET('<option value="">&nbsp;</option>');
        
        $options = (array) json_decode(core_Cache::get('SelectOpt', $hnd));
        
        $cnt = 0;
        
        if (is_array($options)) {
            
            foreach ($options as $id => $title) {
                
                $attr = array();
                
                if($q && (strpos(" " . $id , " " . $q) === FALSE) && (!is_object($title) && !isset($title->group))) continue;
                
                $element = 'option';
                
                if (is_object($title)) {
                    if ($title->group) {
                        if ($openGroup) {
                            // затваряме групата                
                            $select->append('</optgroup>');
                        }
                        $element = 'optgroup';
                        $attr = $title->attr;
                        $attr['label'] = $title->title;
                        $newGroup = ht::createElement($element, $attr);
                        continue;
                    } else {
                        if($newGroup) {
                            $select->append($newGroup);
                            $newGroup = NULL;
                            $openGroup = TRUE;
                        }
                        $attr = $title->attr;
                        $title = $title->title;
                    }
                } else {
                    if($newGroup) {
                        $select->append($newGroup);
                        $newGroup = NULL;
                        $openGroup = TRUE;
                    }
                }
                
                $attr['value'] = $title;
                
                if ($title == $selected) {
                    $attr['selected'] = 'selected';
                }
                $option = ht::createElement($element, $attr, $title);
                $select->append($option);
                
                $cnt++;
                
                if($cnt >= $maxSuggestions) break;
            }
        }
        
        return array(
            'content' => $select->getContent()
        );
    }
}