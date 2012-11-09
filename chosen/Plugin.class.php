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
class chosen_Plugin extends core_Plugin
{
    
    function on_BeforeRenderInput(&$invoker, &$tpl, $name, $value, $attr = array())
    {
        if(is_array($value) && isset($value['chosen'])) {
            unset($value['chosen']);
            foreach($value as $id => $v) {
                $value1[$v] = $v;
            }
            $value = $value1;
        }
    }

    /**
     * Изпълнява се след рендирането на input
     */
    function on_AfterRenderInput(&$invoker, &$tpl, $name, $value, $attr = array())
    {
        if(!$inviker->params['chisenMinItems']) {
            $conf = core_Packs::getConfig('chosen');
            $minItems = $conf->CHOSEN_MIN_ITEMS;
        } else {
            $minItems = $inviker->params['chisenMinItems'];
        }
    	
        // Ако нямаме JS или има много малко предложения - не правим нищо
        if (Mode::is('javascript', 'no') || ((count($invoker->suggestions)) < $minItems)) {
            return ;
        }

        $options = new ET();
        $mustCloseGroup = FALSE;

        foreach ($invoker->suggestions as $key => $val) {
            
            $attr = array();
                            

            if (is_object($val)) {
                if ($val->group) {
                    if($mustCloseGroup) {
                        $options->append("</optgroup>\n");
                    }
                    $options->append("<optgroup label=\"$val->title\">\n");
                    $mustCloseGroup = TRUE;
                    continue;
                } else {
                    $attr = $val->attr;
                    $val  = $val->title;
                }
            }
            
            $selected = '';
            
            $newKey = "|{$key}|";

            if (strstr($value, $newKey)) {
                $attr['selected'] = 'selected';
            }
            
            $attr['value'] = $key;
            
            $options->append(ht::createElement('option', $attr, $val));
        }

        if($mustCloseGroup) {
            $options->append("</optgroup>\n");
        }

        $attr = array();
        
        $attr['class'] = 'keylistChosen';
        $attr['multiple'] = 'multiple';
        $attr['name'] = $name . '[]';
        $attr['style'] = 'width:100%';
        
        $tpl = ht::createElement('select', $attr, $options);
        
        $tpl->append("<input type='hidden' name='{$name}[chosen]' value=1>");
        $JQuery = cls::get('jquery_Jquery');
        $JQuery->enable($tpl);
        $tpl->push(CHOSEN_PATH . "/chosen.css", "CSS");
        $tpl->push(CHOSEN_PATH . "/chosen.jquery.min.js", "JS");
        
        $JQuery->run($tpl, "$('.keylistChosen').data('placeholder', 'Избери...').chosen();");
        
        return FALSE;
    }
    
    
    /**
     * Преди преобразуване данните от вербална стойност
     */
    function on_BeforeFromVerbal($type, &$res, $value)
    {
        if ((count($value) > 1) && (isset($value['chosen']))) {
            unset($value['chosen']);
            
            foreach($value as $id => $val){
                if(!ctype_digit(trim($id))) {
                    $this->error = "Некоректен списък $id ";
                    
                    return FALSE;
                }
                
                $res .= "|" . $val;
            }
            $res = $res . "|";
            
            return FALSE;
        }
        
        if ((count($value) == 1) && (isset($value['chosen']))) {
            return FALSE;
        }
    }
}