<?php


/**
 * Плъгин за превръщане на keylist полетата в select2
 * 
 * @category  bgerp
 * @package   selec2
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class select2_Plugin extends core_Plugin
{
    
    
    /**
     * Името на hidden полето
     */
    protected static $hiddenName = 'select2';
    
    
    /**
     * Дали да може да се въвежда повече от 1 елемент
     */
    protected static $isMultiple = TRUE;
    
    
    /**
     * Името на класа на елементите, за които ще се стартира плъгина
     */
    protected static $className = 'select2';
    
    
    /**
     * Дали може да се изчистват всичките записи едновременно
     */
    protected static $allowClear = TRUE;
    
    
    /**
     * Минималния брой елементи над които да се стартира select2
     */
    protected static $minItems = 1;
    

    /**
     * Броя на опциите, преди обработка
     */
    protected static $suggCnt = NULL;
    
    
    /**
     * Изпълнява се преди рендирането на input
     * 
     * @param type_Keylist $invoker
     * @param core_ET $tpl
     * @param string $name
     * @param string|array|NULL $value
     * @param array $attr
     */
    function on_BeforeRenderInput(&$invoker, &$tpl, $name, &$value, &$attr = array())
    {
        // Премамахваме от масива елемента от hidden полето
        if(is_array($value) && isset($value[self::$hiddenName])) {
            unset($value[self::$hiddenName]);
            $value1 = array();
            foreach($value as $id => $v) {
                $value1[$v] = $v;
            }
            $value = $value1;
        }
        
        ht::setUniqId($attr);
        
        if (!isset($invoker->suggestions)) {
            $invoker->prepareSuggestions();
            
            if (!$invoker->suggestions && isset($invoker->options)) {
                $invoker->suggestions = $invoker->options;
            }
        }
        
        self::$suggCnt = count($invoker->suggestions);
        
        $maxSuggestions = $invoker->getMaxSuggestions();
        
        // Ако няма да се показват всички възможност стойности, а ще се извличат по AJAX
        if (self::$suggCnt > $maxSuggestions) {
            
            // Подготвяме опциите за кеширане
            self::setHandler($invoker, $value);
            $cSugg = self::prepareSuggestionsForCache($invoker);
            core_Cache::set('keylist', $invoker->handler, $cSugg, 20, $invoker->params['mvc']);
            
            // Ако има избрани стойности, винаги да са включени в опциите и да се показват най-отгоре
            $sValArr = array();
            if (isset($value)) {
                
                $vArr = $invoker->toArray($value);
                
                foreach ($vArr as $v) {
                    $sValArr[$v] = $invoker->suggestions[$v];
                    unset($invoker->suggestions[$v]);
                }
            }
            
            // Опитваме се да покажем толкова на брой опции, колкото са зададени
            $rSugg = $maxSuggestions - count($sValArr);
                        
            if ($rSugg <= 0) {
                $rSugg = $maxSuggestions;
            }
            
            $invoker->suggestions = array_slice($invoker->suggestions, 0, $rSugg, TRUE);
            
            // Ако последният елемент е група, премахваме от списъка
            $endElement = end($invoker->suggestions);
            if ($rSugg > 2 && is_object($endElement) && $endElement->group) {
                array_pop($invoker->suggestions);
            }
            
            if (!empty($sValArr)) {
                $invoker->suggestions = $sValArr + $invoker->suggestions;
            }
        }
    }
    
    
    /**
     * Изпълнява се след рендирането на input
     * 
     * @param type_Keylist $invoker
     * @param core_ET $tpl
     * @param string $name
     * @param string|array|NULL $value
     * @param array $attr
     */
    function on_AfterRenderInput(&$invoker, &$tpl, $name, $value, &$attr = array())
    {
        if ($invoker->params['isReadOnly']) return ;
        
        $minItems = isset($invoker->params['select2MinItems']) ? $invoker->params['select2MinItems'] : self::$minItems;
    	
        $optArr = isset($invoker->suggestions) ? $invoker->suggestions : $invoker->options;
        
        $cnt = self::$suggCnt;
        
        if (!isset($cnt)) {
            if (isset($invoker->suggestions)) {
                $cnt = count($invoker->suggestions);
            } else {
                $cnt = count($invoker->options);
            }
        }
        
        // Ако нямаме JS или има много малко предложения - не правим нищо
        if (Mode::is('javascript', 'no') || (($cnt) <= $minItems)) {
            
            return ;
        }
        
        // Ако все още няма id
        if (!$attr['id']) {
            $attr['id'] = str::getRand('aaaaaaaa');
        }
        
        $options = new ET();
        $mustCloseGroup = FALSE;
        
        // Преобразуваме опциите в селекти
        foreach ((array)$optArr as $key => $val) {
            
            $optionsAttrArr = array();
                        
            if (is_object($val)) {
                if ($val->group) {
                    if($mustCloseGroup) {
                        $options->append("</optgroup>\n");
                    }
                    $val->title = htmlspecialchars($val->title);
                    $options->append("<optgroup label=\"$val->title\">\n");
                    $mustCloseGroup = TRUE;
                    continue;
                } else {
                    $optionsAttrArr = $val->attr;
                    $val  = $val->title;
                }
            }
            
            $newKey = "|{$key}|";
 
            if (is_array($value)) {
                if ($value[$key]) {
                    $optionsAttrArr['selected'] = 'selected';
                }

            } else {
                if (strstr($value, $newKey)) {
                    $optionsAttrArr['selected'] = 'selected';
                }
            }
            
            $optionsAttrArr['value'] = $key;
            
            $options->append(ht::createElement('option', $optionsAttrArr, $val));
        }
        
        if ($mustCloseGroup) {
            $options->append("</optgroup>\n");
        }
        
        // Създаваме нов select
        $selectAttrArray = array();
        if (isset($invoker->params['select2Multiple'])) {
            if ($invoker->params['select2Multiple']) {
                $selectAttrArray['multiple'] = 'multiple';
                       

            }
        } else if (self::$isMultiple) {
            $selectAttrArray['multiple'] = 'multiple';
        }
         
        $selectAttrArray['class'] = self::$className . ' ' . $attr['class'];
        $selectAttrArray['id'] = $attr['id'];
        $selectAttrArray['name'] = $name . '[]';
        $selectAttrArray['style'] = 'width:100%';
        $tpl = ht::createElement('select', $selectAttrArray, $options);
        
        $tpl->append("<input type='hidden' name='{$name}[" . self::$hiddenName . "]' value=1>");
        
        $select = ($attr['placeholder']) ? ($attr['placeholder']) : '';
        
        if ($invoker->params['allowEmpty']) {
            $allowClear = true;
        } else {
            if ($selectAttrArray['multiple']) {
                $allowClear = (self::$allowClear) ? (self::$allowClear) : false;
            } else {
                $allowClear = false;
            }
        }
        
        $maxSuggestions = $invoker->getMaxSuggestions();
        
        $ajaxUrl = '';
        
        if ($cnt > $maxSuggestions) {
            
            self::setHandler($invoker, $value);
            
            $ajaxUrl = toUrl(array($invoker, 'getOptions', 'hnd' => $invoker->handler, 'maxSugg' => $maxSuggestions, 'ajax_mode' => 1));
        }
        
        // Добавяме необходимите файлове и стартирам select2
        select2_Adapter::appendAndRun($tpl, $attr['id'], $select, $allowClear, NULL, $ajaxUrl);
        
        return FALSE;
    }
    
    
    /**
     * Задава манипулатор, който ще се използва за кеширане
     * 
     * @param type_Keylist $invoker
     * @param string|array|NULL $val
     */
    protected static function setHandler(&$invoker, $val)
    {
        if (isset($invoker->handler)) return ;
        
        $invoker->handler = md5(serialize($invoker->suggestions) . '|' . serialize($val) . '|' . core_Lg::getCurrent());
    }
    
    
    /**
     * Подготвяме опциите за кеширане
     * Нормализира текста, в който ще се търси
     * 
     * @param type_Keylist $invoker
     */
    protected static function prepareSuggestionsForCache(&$invoker)
    {
        $newSugg = array();
        foreach ($invoker->suggestions as $key => $sugg) {
            if (is_object($sugg)) {
                $suggV = $sugg->title;
            } else {
                $suggV = $sugg;
            }
            
            $newSugg[$key]['id'] = trim(preg_replace('/[^a-z0-9\*]+/', ' ', strtolower(str::utf2ascii($suggV))));
            $newSugg[$key]['title'] = $sugg;
        }
        
        return serialize($newSugg);
    }
    
    
    /**
     * Преди преобразуване данните от вербална стойност
     * 
     * @param core_Type $type
     * @param string $res
     * @param array $value
     */
    function on_BeforeFromVerbal($type, &$res, $value)
    {
        if (!is_array($value)) return ;
        
        // Преобразуваме масива с данни в keylist поле
        $valCnt = count($value);
        if (($valCnt > 1) && (isset($value[self::$hiddenName]))) {
            unset($value[self::$hiddenName]);
            
            foreach($value as $id => $val){
                if (!ctype_digit(trim($id))) {
                    $type->error = "Некоректен списък $id ";
                    
                    return FALSE;
                }
                
                $res .= "|" . $val;
            }
            $res = $res . "|";
            
            return FALSE;
        }
        
        if (($valCnt == 1) && (isset($value[self::$hiddenName]))) {
            
            return FALSE;
        }
    }
   
   
    /**
     * Връща максималния брой на опциите, които може да се избере
     * 
     * @param type_Key $invoker
     * @param integer|NULL $res
     */
    function on_AfterGetMaxSuggestions($invoker, &$res)
    {
        setIfNot($res, $invoker->params['maxSuggestions'], core_Setup::get('TYPE_KEY_MAX_SUGGESTIONS', TRUE), 1000);
    }
    
    
    /**
    * Отпечатва резултата от опциите в JSON формат
    * 
    * @param type_Key $invoker
    * @param string|NULL|core_ET $res
    * @param string $action
    */
    function on_BeforeAction($invoker, &$res, $action)
    {
        if ($action != 'getoptions') return ;
        
        if (!Request::get('ajax_mode')) return ;
        $hnd = Request::get('hnd');
        
        $maxSuggestions = Request::get('maxSugg', 'int');
        if (!$maxSuggestions) {
            $maxSuggestions = $invoker->getMaxSuggestions();
        }
        
        $q = Request::get('q');
        
        select2_Adapter::getAjaxRes('keylist', $hnd, $q, $maxSuggestions);
        
        return FALSE;
    }
}
