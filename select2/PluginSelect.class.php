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
    function on_AfterRenderInput(&$invoker, &$tpl, $name, $value, &$attr = array())
    {   
        // Ако все още няма id
        if (!$attr['id']) {
            $attr['id'] = str::getRand('aaaaaaaa');
        }
        
        $minItems = $invoker->params['select2MinItems'] ? $invoker->params['select2MinItems'] : self::$minItems;
    	
        $optionsCnt = count($invoker->options);
        
        // Ако опциите са под минималното - нищо не правим
        if($optionsCnt <= $minItems) return;
        
        // Ако имаме комбо - не правим select2
        if(count($invoker->suggestions)) return;
        
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
            $ajaxUrl = toUrl(array($invoker, 'getOptions', 'hnd' => $invoker->handler, 'maxSugg' => $maxSuggestions, 'ajax_mode' => 1), 'absolute');
        }
        
        // Добавяме необходимите файлове и стартирам select2
        select2_Adapter::appendAndRun($tpl, $attr['id'], $select, $allowClear, NULL, $ajaxUrl);
   }
   
   
   /**
    * Отпечатва резултата от опциите в JSON формат
    * 
    * @param core_Type $invoker
    * @param string|NULL|core_ET $res
    * @param string $action
    */
   function on_BeforeAction($invoker, &$res, $action)
   {
        if ($action != 'getoptions') return ;
       
        if (!Request::get('ajax_mode')) return ;
       
        $q = Request::get('q');
        $q = plg_Search::normalizeText($q);
        $q = '/[ \"\'\(\[\-\s]' . str_replace(' ', '.* ', $q) . '/';
        
        $hnd = Request::get('hnd');
        core_Logs::add($invoker, NULL, "ajaxGetOptions|{$hnd}|{$q}", 1);
        if (!$hnd || !($options = unserialize(core_Cache::get($invoker->selectOpt, $hnd)))) {
            
            core_App::getJson(array(
                (object)array('name' => 'Липсват допълнителни опции')
            ));
            
            return FALSE;
        }
        
        $resArr = array();
        
        $cnt = 0;
        
        if (!($maxSuggestions = Request::get('maxSugg', 'int'))) {
            $maxSuggestions = $invoker->getMaxSuggestions();
        }
        $group = FALSE;
        foreach ($options as $key => $titleArr) {
            $isGroup=FALSE;
            
            $title = $titleArr['title'];
            $titleNormalized = $titleArr['id'];
            
            $attr = array();
            
            if ($key == '') continue;
            
            if(!isset($title->group) && $q && (!preg_match($q, ' ' . $titleNormalized)) ) continue;
            
            $r = new stdClass();
            $r->id = $key;
            
            if (is_object($title)) {
                $r->name = $title->title;
                
                $r->class = $title->attr['class'];
                
                if ($title->group) {
                    
                    $r->class .= ($r->class) ? ' ' : '';
                    $r->class .= 'group';
                    
                    $r->id = NULL;
                    $group = $r;
                    $isGroup = TRUE;
                }
            } else {
                $r->name = $title;
            }
            
            // Предпазва от добавяне на група без елементи в нея
            if ($isGroup && $group) continue;
            if (!$isGroup && $group) {
                $resArr[] = $group;
                $group = FALSE;
            }
            
            $resArr[] = $r;
            
            $cnt++;
            
            if ($cnt >= $maxSuggestions) break;
        }
        
        core_App::getJson($resArr);
        
        return FALSE;
   }
   
   
   /**
    * 
    * 
    * @param core_Type $invoker
    * @param integer|NULL $res
    */
   function on_AfterGetMaxSuggestions($invoker, &$res)
   {
       if (!isset($res)) {
           
           $res = 1000000;
       }
   }
}
