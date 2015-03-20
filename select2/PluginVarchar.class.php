<?php


/**
 * Плъгин за превръщане на enum полетата в select2
 * 
 * @category  bgerp
 * @package   selec2
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class select2_PluginVarchar extends core_Plugin
{
    
    
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
    function on_BeforeRenderInput(&$invoker, &$tpl, $name, $value, &$attr = array())
    {
        ht::setUniqId($attr);
    }
    

    /**
     * Изпълнява се след рендирането на input
     * 
     * @param type_Varchar $invoker
     * @param core_ET $tpl
     * @param string $name
     * @param string|array|NULL $value
     * @param array $attr
     */
    function on_AfterRenderInput(&$invoker, &$tpl, $name, $value, &$attr = array())
    {   
        // Ако все още няма id
        if (!$attr['id']) {
            $attr['id'] = str::getRand('aaaaaaaa');
        }
        
        $minItems = $invoker->params['select2MinItems'] ? $invoker->params['select2MinItems'] : self::$minItems;
        
        $optionsCnt = count($invoker->suggestions);
        
        // Ако опциите са под минималното - нищо не правим
        if ($optionsCnt <= $minItems) return;
        
        // Ако няма JS нищо не правим
        if (Mode::is('javascript', 'no')) return;
        
        $select = ($attr['placeholder']) ? ($attr['placeholder']) : '';
        
        if ($invoker->params['allowEmpty'] || isset($invoker->suggestions['']) || isset($invoker->suggestions[' '])) {
            $allowClear = true;
        } else {
            $allowClear = (self::$allowClear) ? (self::$allowClear) : true;
        }
        
        // Добавяме необходимите файлове и стартирам select2
        select2_Adapter::appendAndRun($tpl, "autoElement2_comboSelect", $select, $allowClear);
   }
}
