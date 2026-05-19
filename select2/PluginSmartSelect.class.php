<?php


/**
 * Плъгин за превръщане на SmartSelect полетата в select2
 *
 * @category  bgerp
 * @package   selec2
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class select2_PluginSmartSelect extends core_Plugin
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
     *
     *
     * @param core_Form $invoker
     * @param core_ET   $input
     * @param core_Type $type
     * @param array     $options
     * @param string    $name
     * @param string    $value
     * @param array     $attr
     */
    public function on_BeforeCreateSmartSelect($invoker, $input, $type, $options, $name, $value, &$attr)
    {
        ht::setUniqId($attr);
    }
    
    
    /**
     *
     *
     * @param core_Form $invoker
     * @param core_ET   $input
     * @param core_Type $type
     * @param array     $options
     * @param string    $name
     * @param string    $value
     * @param array     $attr
     */
    public function on_AfterCreateSmartSelect($invoker, $input, $type, $options, $name, $value, &$attr)
    {
        if ($invoker->params['isReadOnly'] ?? null) {
            
            return ;
        }
        
        // Ако все още няма id
        if (!$attr['id']) {
            $attr['id'] = str::getRand('aaaaaaaa');
        }
        
        $minItems = isset($type->params['select2MinItems']) ? $type->params['select2MinItems'] : self::$minItems;
        
        $optionsCnt = countR($options);
        
        // Ако опциите са под минималното - нищо не правим
        if ($optionsCnt <= $minItems) {
            
            return;
        }
        
        // Ако няма JS нищо не правим
        if (Mode::is('javascript', 'no')) {
            
            return;
        }
        
        // Ако ще са радиобутони
        if (($type->params['maxRadio'] ?? null) && (($type->params['maxRadio'] ?? null) >= $optionsCnt)) {
            
            return ;
        }
        
        $select = ($attr['placeholder'] ?? null) ?: '';

        if (($attr['allowEmpty'] ?? null) || ($type->params['allowEmpty'] ?? null) || isset($options['']) || isset($options[' '])) {
            $allowClear = true;
        } else {
            $allowClear = (self::$allowClear) ? (self::$allowClear) : false;
        }

        $invoker->params['forceOpen'] ??= $type->params['forceOpen'] ?? null;

        $minimumResultsForSearch = isset($invoker->params['minimumResultsForSearch']) ? $invoker->params['minimumResultsForSearch'] : null;
        $minimumResultsForSearch = isset($type->params['minimumResultsForSearch']) ? $type->params['minimumResultsForSearch'] : $minimumResultsForSearch;

        $matchOnlyStartsWith = ($invoker->params['find'] ?? null) == 'everywhere' ? false : true;

        // Добавяме необходимите файлове и стартирам select2
        select2_Adapter::appendAndRun($input, $attr['id'], $select, $allowClear, null, '', false, $invoker->params['forceOpen'], $minimumResultsForSearch, $matchOnlyStartsWith);
    }
}
