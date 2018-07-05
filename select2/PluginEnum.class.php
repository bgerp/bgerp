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
class select2_PluginEnum extends core_Plugin
{
    
    
    /**
     * Дали може да се изчистват всичките записи едновременно
     */
    protected static $allowClear = false;
    
    
    /**
     * Минималния брой елементи над които да се стартира select2
     */
    protected static $minItems = 1;
    
    
    /**
     * Изпълнява се преди рендирането на input
     *
     * @param core_Type         $invoker
     * @param core_ET           $tpl
     * @param string            $name
     * @param string|array|NULL $value
     * @param array             $attr
     */
    public function on_BeforeRenderInput(&$invoker, &$tpl, $name, $value, &$attr = array())
    {
        ht::setUniqId($attr);
    }
    

    /**
     * Изпълнява се след рендирането на input
     *
     * @param type_Key          $invoker
     * @param core_ET           $tpl
     * @param string            $name
     * @param string|array|NULL $value
     * @param array             $attr
     */
    public function on_AfterRenderInput(&$invoker, &$tpl, $name, $value, &$attr = array())
    {
        if ($invoker->params['isReadOnly']) {
            return ;
        }
        
        // Ако все още няма id
        if (!$attr['id']) {
            $attr['id'] = str::getRand('aaaaaaaa');
        }
        
        $minItems = isset($invoker->params['select2MinItems']) ? $invoker->params['select2MinItems'] : self::$minItems;
        
        $optionsCnt = count($invoker->options);
        
        // Ако опциите са под минималното - нищо не правим
        if ($optionsCnt <= $minItems) {
            return;
        }
        
        // Ако няма JS нищо не правим
        if (Mode::is('javascript', 'no')) {
            return;
        }
        
        // Ако ще са радиобутони
        if ($invoker->params['maxRadio'] && ($invoker->params['maxRadio'] >= $optionsCnt)) {
            return ;
        }
        
        $select = ($attr['placeholder']) ? ($attr['placeholder']) :  '';
        
        if ($invoker->params['allowEmpty'] || isset($invoker->options['']) || isset($invoker->options[' '])) {
            $allowClear = true;
        } else {
            $allowClear = (self::$allowClear) ? (self::$allowClear) : false;
        }
        
        // Добавяме необходимите файлове и стартирам select2
        select2_Adapter::appendAndRun($tpl, $attr['id'], $select, $allowClear);
    }
}
