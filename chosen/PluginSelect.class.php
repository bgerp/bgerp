<?php


/**
 * Клас 'chosen_Plugin'
 *
 * Плъгин, който позволява избирането на key полета по много удобен начин
 *
 *
 * @category  vendors
 * @package   chosen
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class chosen_PluginSelect extends core_Plugin
{
    public function on_BeforeRenderInput(&$invoker, &$tpl, $name, $value, &$attr = array())
    {
        ht::setUniqId($attr);
    }
    
    
    /**
     * Изпълнява се след рендирането на input
     */
    public function on_AfterRenderInput(&$invoker, &$tpl, $name, $value, $attr = array())
    {
        // За да не влиза в конфликт с комбо-бокса
        if ($attr['ajaxAutoRefreshOptions']) {
            
            return;
        }
        
        $conf = core_Packs::getConfig('chosen');
        
        // Определяме при колко минимално опции ще правим chosen
        if (!$invoker->params['chosenMinItems']) {
            $minItems = $conf->CHOSEN_MIN_ITEMS;
        } else {
            $minItems = $invoker->params['chosenMinItems'];
        }
        
        // Ако опциите са под минималното - нищо не правим
        if (count($invoker->options) < $minItems) {
            
            return;
        }
        
        // Ако имаме комбо - не правим chosen
        if (count($invoker->suggestions)) {
            
            return;
        }
        
        // Ако няма JS нищо не правим
        if (Mode::is('javascript', 'no')) {
            
            return;
        }
        
        $tpl->push($conf->CHOSEN_PATH . '/chosen.css', 'CSS');
        $tpl->push($conf->CHOSEN_PATH . '/chosen.jquery.js', 'JS');
        
        if ($invoker->params['allowEmpty']) {
            $allowEmpty = ', allow_single_deselect: true';
        }
        
        $choose = tr('Избери');
        $noResults = tr('Няма резултати');
        jquery_Jquery::run($tpl, "$('#" . $attr['id'] . "').data('placeholder', '{$choose}...').chosen({no_results_text: \"{$noResults}\"{$allowEmpty}});");
    }
}
