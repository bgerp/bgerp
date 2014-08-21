<?php

/**
 * Клас  'type_CustomKey' - Поле за външен ключ към произволен уникален ключ на друг модел
 *
 * Този клас е обобщение на класа type_Key. Също като type_Key, той представлява поле-външен 
 * ключ към друг модел. Разликата е, че докато при type_Key стойностите съответстват на 
 * първичния ключ на другия модел, при type_CustomKey стойностите съответстват на произволен
 * уникален ключ на другия модел. Името на този уникален ключ се задава с параметъра на типа
 * `key`.
 * 
 * Пример:
 * 
 * <code>
 *     $this->FLD('field', 'customKey(mvc=OtherModel, key=other_model_key_field, select=title)
 * </code>
 * 
 * В този пример полето `field` е външен ключ към модела `OtherModel` по неговото поле
 * `other_model_key_field` 
 * 
 * @TODO По идея тази фукционалност трябва да се премести в самия type_Key. Поради комплексността
 * на промените обаче, приемаме по-консервативен подход - клонираме класа type_Key, разработваме
 * и тестваме type_CustomKey и след това интегрираме промените обратно в type_Key. 
 *  
 *
 * @category  ef
 * @package   type
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class type_CustomKey extends type_Key 
{
    /**
    
     * MySQL тип на полето в базата данни
    
     */
  
    var $dbFieldType = 'varchar';
    
    
    
    

    /**
    
     * Дължина на полето в mySql таблица
    
     */
    
    var $dbFieldLen = '32';
    
    
    


    /**
    
     * Стойност по подразбиране
    
     */
    
    var $defaultValue = '';

    

    /**
     * Конвертира стойността от вербална към
     */
    function toVerbal_($value)
    {
        
        if(empty($value)) return NULL;
        
        $verbalValue = '??????????????';
            
        if ($this->params['mvc']) {
            // "Другия" модел
            $mvc = cls::get($this->params['mvc']);
            
            // Име на поле - уникален ключ на другия модел
            $keyField = 'id';
            
            // Име на поле от другия модел, което се използва за вербални стойности на ключа
            $titleField = NULL;

            if (!empty($this->params['key'])) {
                $keyField = $this->params['key'];
                
                // $keyField трябва да е поле на $mvc и да има дефининиран уникален индекс по него
                // @TODO
            }
            
            if (!empty($this->params['select'])) {
                $titleField = $this->params['select'];
            } elseif (!empty($this->params['title'])) {
                $titleField = $this->params['title'];
            }
            
            if (isset($titleField)) {
                if ($rec = $this->fetchForeignRec($value)) {
                    
                    $verbalValue = $mvc->getVerbal($rec, $titleField);
                }
            } else {
                // Не е зададено поле за вербална стойност. Това е валиден сценарий само ако 
                // полето ключ се казва 'id'
                expect($keyField == 'id');
                
                $verbalValue = $mvc->getTitleById($value);
            }
        } else {
            // @TODO Какво да става ако не е зададен 'mvc' параметър на типа?
        }
        
        return $verbalValue;
    }
    
    
    /**
     * Връща вътрешното представяне на вербалната стойност
     */
    function fromVerbal_($value)
    {
        if (empty($value)) {
            return NULL;
        }
        
        $conf = core_Packs::getConfig('core');
        
        setIfNot($maxSuggestions, $this->params['maxSuggestions'], $conf->TYPE_KEY_MAX_SUGGESTIONS);
        
        if (count($this->options) == 0 && !empty($this->params['select'])) {
            $mvc     = $this->getForeignModel();
            $options = $mvc->makeArray4select($this->params['select'], '', $this->getKeyField());
        } else {
            $options = $this->options;
        }
        
        $value = trim($value);

        if (count($options) > $maxSuggestions) {
            // $value е вербална стойност, търсим на кой ключ съответства.
            foreach($options as $id => $verbal) {
                if (!is_string($verbal)) {
                    if ($verbal->group) {
                        // Групите ги пропускаме
                        continue;
                    }
                    
                    $verbal = $verbal->title;
                } else {
                    /**
                     * $verbal (косвено) се сравнява с субмитнатата чрез HTML `<select>` елемент
                     * стойност $value. Оказа се, че (поне при някои браузъри) специалните HTML
                     * символи (`&amp;`, `&nbsp` и пр.) биват декодирани при такъв субмит. Така
                     * ако $verbal съдържа такива символи, сравнението ще пропадне, въпреки, че
                     * стойностите може да изглеждат визуално еднакви. Напр. ако
                     *
                     *  $verbal = "Тестов&nbsp;пример"; $value = "Тестов пример"
                     *
                     *  очевидно $v != $value
                     *
                     *  По тази причина декодираме специалните символи предварително.
                     *
                     */
                    $verbal = html_entity_decode($verbal, ENT_NOQUOTES, 'UTF-8');
                }
                
                if ($value == trim($verbal)) {
                    // Намерихме вербалната стойност в опциите, ключа е в $id
                    $keyValue = $id;
                    break;
                }
            }
        } else {
            // Това е сигнал, че renderInput() е генерирал <select>, следователно $value не е
            // вербална стойност на полето, а директно стойността на ключовото поле.
            $keyValue = $value;
        }

        if (isset($keyValue)) {
            if ($rec = $this->fetchForeignRec($keyValue)) {
		        $keyName = $this->getKeyField();
		        expect ($keyValue == $rec->{$keyName});
		        
                return $rec->{$keyName};
            }
        }
        
        // Грешка - не е намерен ключ за зададената вербална стойност.
        $this->error = 'Несъществуващ обект';
        
        return FALSE;
    }


    /**
     *
   
     * @return core_Mvc
    
     */
    
    protected function getForeignModel()
   
    {
        
         return cls::get($this->params['mvc']);
   
    }
    
    

    /**
     * 
     * @param mixed $keyValue
     * @return stdClass
     */
    
     protected function fetchForeignRec($keyValue)
 
    {
        
        $foreignModel = $this->getForeignModel();
        $keyField     = $this->getKeyField();
        
        return $foreignModel->fetch(array("#{$keyField} = '[#1#]'", $keyValue));
     }
    
    
    /**
     * @return string
     */
    protected function getKeyField()
    {
        $keyField = 'id';
         
        if (!empty($this->params['key'])) {
            $keyField = $this->params['key'];
        
            // @TODO:
            // $keyField трябва да е поле на $foreignModel и да има дефининиран уникален индекс
            // по него.
        }
        
        return $keyField;
    }
    
    
    /**
     * Рендира HTML поле за въвеждане на данни чрез форма
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        $conf = core_Packs::getConfig('core');
        
        $mvc = $this->getForeignModel();
        
        if (empty($value)) {
            $value = $attr['value'];
        }
        
        if ($this->params['select'] || count($this->options)) {
            
            if($this->params['select'] == '*') {
                $field = NULL;
            } else {
                $field = $this->params['select'];
            }
            
            if ($this->params['where']) {
                $where = $this->params['where'];
            }
            
            $keyField = $this->getKeyField();
            
            Debug::startTimer('prepareOPT ' . $mvc->className);
            
            $options = array();
            
            $mvc->invoke('BeforePrepareKeyOptions', array(&$options, $this));

            if (!count($options)) {
                if (!is_array($this->options)) {
                    $options = $mvc->makeArray4select($field, $where, $keyField); 
                    $handler = md5($field . $where . $keyField . $mvc->className);
                } else {
                    $options = $this->options;
                }

                if ($this->params['allowEmpty']) {
                    // Добавяме празен избор в началото на опциите
                    $options = arr::combine(array(' ' => ' '), $options);
                }
            }
            
            $this->options = &$options;

            $mvc->invoke('AfterPrepareKeyOptions', array(&$this->options, $this));
            
            
            setIfNot($handler, md5(json_encode($this->options)));
            
            setIfNot($maxSuggestions, $this->params['maxSuggestions'], $conf->TYPE_KEY_MAX_SUGGESTIONS);

            Debug::stopTimer('prepareOPT ' . $mvc->className);
             
            // Ако трябва да показваме combo-box
            if(count($options) > $maxSuggestions) {
                
                // Генериране на cacheOpt ако не са в кеша
            	$cacheOpt = core_Cache::get('SelectOpt', $handler, 20, array($mvc->className));
            	
                if(FALSE === $cacheOpt) {
                    
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
                    
                    core_Cache::set('SelectOpt', $handler, serialize($cacheOpt), 20, array($mvc->className));
                } else {
                	$cacheOpt = (array) json_decode($cacheOpt);
                }
                
   
                
                if($this->suggestions) {
                    $suggestions = $this->suggestions;
                } else {
                    $suggestions = array_slice($cacheOpt, 0, $maxSuggestions, TRUE);
                }
                
                foreach($suggestions as $key => $v) {
                    
                    $key = is_object($v) ? $v->title : $v;
                    
                    $selOpt[trim($key)] = $v;
                }
                
                $selOpt[$options[$value]] = $options[$value];
                
                $this->options = $selOpt;

                $attr['ajaxAutoRefreshOptions'] = "{Ctr:\"type_Key\"" .
                ", Act:\"ajax_GetOptions\", hnd:\"{$handler}\", maxSugg:\"{$maxSuggestions}\", ajax_mode:1}";
                
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
            
            expect(FALSE);
        }
        
        return $tpl;
    }

    
    /**
     * Връща атрибутите на MySQL полето
     */
    public function getMysqlAttr()
    {
        // Извикваме базовата имплементация (дефинирана в core_Type), за да прескочим 
        // имплементацията на type_Int
        return $this->_baseGetMysqlAttr();
    }
    
    
	/**
     * Транслитерира масив с опции, като запазва възможността някои от тях да са обекти
     */
    static function transliterateOptions($options)
    {
        foreach($options as &$opt) {
            if(is_object($opt)) {
                $opt->title = transliterate($opt->title);
            } else {
                $opt = transliterate($opt);
            }
        }

        return $options;
    }
    
    
	/**
     * Превежда масив с опции, като запазва възможността някои от тях да са обекти
     */
    static function translateOptions($options)
    {
        foreach($options as &$opt) {
            if(is_object($opt)) {
                $opt->title = tr($opt->title);
            } else {
                $opt = tr($opt);
            }
        }

        return $options;
    }
}