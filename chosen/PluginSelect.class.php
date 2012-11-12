<?php



/**
 * Пътя до външния код на chosen
 */
defIfNot('CHOSEN_PATH', 'chosen/0.9.8');


/**
 * Клас 'chosen_Plugin' - избор на дата
 *
 * Плъгин, който позволява избирането на keylist полета по много удобен начин
 *
 *
 * @category  vendors
 * @package   chosen
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class chosen_PluginSelect extends core_Plugin
{
    
    function on_BeforeRenderInput(&$invoker, &$tpl, $name, $value, &$attr = array())
    {
        
        ht::setUniqId($attr);
    }

    /**
     * Изпълнява се след рендирането на input
     */
    function on_AfterRenderInput(&$invoker, &$tpl, $name, $value, $attr = array())
    {
    	
        // Определяме при колко минимално опции ще правим chosen
        if(!$invoker->params['chosenMinItems']) {
            $conf = core_Packs::getConfig('chosen');
            $minItems = $conf->CHOSEN_MIN_ITEMS;
        } else {
            $minItems = $invoker->params['chosenMinItems'];
        }
    	
        // Ако опциите са под минималното - нищо не правим
        if(count($invoker->options) < $minItems) return;

        // Ако имаме комбо - не правим chosen
        if(count($invoker->suggestions)) return;
        
        // Ако няма JS нищо не правим
        if (Mode::is('javascript', 'no')) return;
        
        $JQuery = cls::get('jquery_Jquery');
        $JQuery->enable($tpl);
        $tpl->push(CHOSEN_PATH . "/chosen.css", "CSS");
        $tpl->push(CHOSEN_PATH . "/chosen.jquery.min.js", "JS");

        if($invoker->params['allowEmpty']) {
            $allowEmpty = ", allow_single_deselect: true";
        }
        $JQuery->run($tpl, "$('#" . $attr['id'] . "').data('placeholder', 'Избери...').chosen({no_results_text: \"Няма резултати\"{$allowEmpty}});");
        
         
    }
    
}