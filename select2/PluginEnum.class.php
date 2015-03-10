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
     * Името на hidden полето
     */
    protected static $hiddenName = 'select2enum';
    
    
    /**
     * Дали да може да се въвежда повече от 1 елемент
     */
    protected static $isMultiple = FALSE;
    
    
    /**
     * Името на класа на елементите, за които ще се стартира плъгина
     */
    protected static $className = 'select2enum';
    
    
    /**
     * Дали може да се изчистват всичките записи едновременно
     */
    protected static $allowClear = FALSE;
    

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
     * @param type_Key $invoker
     * @param core_ET $tpl
     * @param string $name
     * @param string|array|NULL $value
     * @param array $attr
     */
    function on_AfterRenderInput(&$invoker, &$tpl, $name, $value, $attr = array())
    {   
        // Ако все още няма id
        if (!$attr['id']) {
            $attr['id'] = str::getRand('aaaaaaaa');
        }
        
        $conf = core_Packs::getConfig('select2');
        
        // Определяме при колко минимално опции ще правим chosen
        if(!$invoker->params['select2MinItems']) {
            $minItems = $conf->SELECT2_ENUM_MIN_ITEMS;
        } else {
            $minItems = $invoker->params['chosenMinItems'];
        }
    	
        $optionsCnt = count($invoker->options);
        
        // Ако опциите са под минималното - нищо не правим
        if($optionsCnt < $minItems) return;
        
        // Ако няма JS нищо не правим
        if (Mode::is('javascript', 'no')) return;
        
        $select = ($attr['placeholder']) ? ($attr['placeholder']) : '';
        
        if ($invoker->params['allowEmpty']) {
            $allowClear = TRUE;
        } else {
            $allowClear = (self::$allowClear) ? (self::$allowClear) : false;
        }
        
        // Добавяме необходимите файлове и стартирам select2
        select2_Adapter::appendAndRun($tpl, $attr['id'], $select, $allowClear);
   }
}
