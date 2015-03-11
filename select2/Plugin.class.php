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
     * Изпълнява се преди рендирането на input
     * 
     * @param core_Type $invoker
     * @param core_ET $tpl
     * @param string $name
     * @param string|array|NULL $value
     * @param array $attr
     */
    function on_BeforeRenderInput(&$invoker, &$tpl, $name, &$value, $attr = array())
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
    }
    
    
    /**
     * Изпълнява се след рендирането на input
     * 
     * @param core_Type $invoker
     * @param core_ET $tpl
     * @param string $name
     * @param string|array|NULL $value
     * @param array $attr
     */
    function on_AfterRenderInput(&$invoker, &$tpl, $name, $value, $attr = array())
    {
        $minItems = $invoker->params['select2MinItems'] ? $invoker->params['select2MinItems'] : self::$minItems;
    	
        if (!is_null($invoker->suggestions)) {
            $cnt = count($invoker->suggestions);
            $optArr = $invoker->suggestions;
        } else {
            $cnt = count($invoker->options);
            $optArr = $invoker->options;
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
        
        $selectAttrArray['class'] = self::$className;
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
        
        // Добавяме необходимите файлове и стартирам select2
        select2_Adapter::appendAndRun($tpl, $attr['id'], $select, $allowClear);
        
        return FALSE;
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
                    $this->error = "Некоректен списък $id ";
                    
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
}
