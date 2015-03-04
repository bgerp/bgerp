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
     * Името на hidden полето
     */
    protected static $hiddenName = 'select2key';
    
    
    /**
     * Дали да може да се въвежда повече от 1 елемент
     */
    protected static $isMultiple = FALSE;
    
    
    /**
     * Името на класа на елементите, за които ще се стартира плъгина
     */
    protected static $className = 'select2key';
    
    
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
     * @param core_Type $invoker
     * @param core_ET $tpl
     * @param string $name
     * @param string|array|NULL $value
     * @param array $attr
     */
    function on_AfterRenderInput(&$invoker, &$tpl, $name, $value, $attr = array())
    {   
        // За да не влиза в конфликт с комбо-бокса
        if($attr['ajaxAutoRefreshOptions']) {
            
            return;
        }
    	
        // Ако все още няма id
        if (!$attr['id']) {
            $attr['id'] = str::getRand('aaaaaaaa');
        }
        
        $conf = core_Packs::getConfig('select2');
        
        // Определяме при колко минимално опции ще правим chosen
        if(!$invoker->params['select2MinItems']) {
            $minItems = $conf->SELECT2_KEY_MIN_ITEMS;
        } else {
            $minItems = $invoker->params['chosenMinItems'];
        }
    	
        // Ако опциите са под минималното - нищо не правим
        if(count($invoker->options) < $minItems) return;
        
        // Ако имаме комбо - не правим select2
        if(count($invoker->suggestions)) return;
        
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
