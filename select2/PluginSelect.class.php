<?php


/**
 * Плъгин за превръщане на key полетата в select2
 * 
 * @category  bgerp
 * @package   selec2
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class select2_PluginSelect extends core_Plugin
{
    
    
    /**
     * Дали може да се изчистват всичките записи едновременно
     */
    protected static $allowClear = FALSE;
    
    
    /**
     * Минималния брой елементи над които да се стартира select2
     */
    protected static $minItems = 1;
    
    
    /**
     * Броя на опциите, преди обработка
     */
    protected static $optCnt = NULL;
    
    
    /**
     * Изпълнява се преди рендирането на input
     * 
     * @param type_Key $invoker
     * @param core_ET $tpl
     * @param string $name
     * @param string|array|NULL $value
     * @param array $attr
     */
    public static function on_BeforeRenderInput(&$invoker, &$tpl, $name, $value, &$attr = array())
    {
        ht::setUniqId($attr);
        
        $invoker->options = $invoker->prepareOptions($value);
        self::$optCnt = count($invoker->options);
        
        $maxSuggestions = $invoker->getMaxSuggestions();
        
        if (self::$optCnt > $maxSuggestions) {
            if (!$value) {
                $value = $attr['value'];
            }
            
            // Избраната стойност да е на първо мяасто
            if ($value) {
                if (!isset($invoker->options[$value])) {
                    $allowedListArr = $invoker->getAllowedKeyVal($value);
                    $value = reset($allowedListArr);
                }
                
                if ($value) {
                    $valOptArr = array();
                    $valOptArr[$value] = is_array($invoker->options[$value]) ? $invoker->options[$value]['title'] : $invoker->options[$value];
                    if (isset($valOptArr[$value])) {
                        unset($invoker->options[$value]);
                        $invoker->options = $valOptArr + $invoker->options;
                    }
                }
            }
            
            $invoker->options = array_slice($invoker->options, 0, $maxSuggestions, TRUE);
        }
    }
    
    
    /**
     * Изпълнява се след рендирането на input
     * 
     * @param type_Key $invoker
     * @param core_ET $tpl
     * @param string $name
     * @param string|array|NULL $value
     * @param array $attr
     */
    public static function on_AfterRenderInput(&$invoker, &$tpl, $name, $value, &$attr = array())
    {
        if ($invoker->params['isReadOnly']) return ;
        
        // Ако все още няма id
        if (!$attr['id']) {
            $attr['id'] = str::getRand('aaaaaaaa');
        }
        
        $minItems = isset($invoker->params['select2MinItems']) ? $invoker->params['select2MinItems'] : self::$minItems;
        
        $optionsCnt = isset(self::$optCnt) ? self::$optCnt : count($invoker->options);

        // Ако опциите са под минималното - нищо не правим
        if($optionsCnt <= $minItems) return;
        
        // Ако имаме комбо - не правим select2
        // if(count($invoker->suggestions)) return;
        
        // Ако няма JS нищо не правим
        if (Mode::is('javascript', 'no')) return;
        
        $select = ($attr['placeholder']) ? ($attr['placeholder']) : '';
        
        if ($invoker->params['allowEmpty'] || isset($invoker->options['']) || isset($invoker->options[' '])) {
            $allowClear = true;
        } else {
            $allowClear = (self::$allowClear) ? (self::$allowClear) : false;
        }
        
        $maxSuggestions = $invoker->getMaxSuggestions();
        
        $ajaxUrl = '';
        
        if ($optionsCnt > $maxSuggestions) {
            $ajaxUrl = toUrl(array($invoker, 'getOptions', 'hnd' => $invoker->handler, 'maxSugg' => $maxSuggestions, 'ajax_mode' => 1));
        }
        
        // Добавяме необходимите файлове и стартирам select2
        select2_Adapter::appendAndRun($tpl, $attr['id'], $select, $allowClear, NULL, $ajaxUrl);
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
        
        select2_Adapter::getAjaxRes($invoker->selectOpt, $hnd, $q, $maxSuggestions);
        
        return FALSE;
    }
    
    
    /**
    * 
    * 
    * @param type_Key $invoker
    * @param integer|NULL $res
    */
    function on_AfterGetMaxSuggestions($invoker, &$res)
    {
       if (!isset($res)) {
           
           $res = 1000;
       }
    }
}
