<?php

defIfNot('CHOSEN_PATH', 'chosen/0.9.3');
defIfNot('EF_MIN_COUNT_LIST_CHOSEN', 16);
/**
 * Клас 'chosen_Plugin' - избор на дата
 * Плъгин, който позволява избирането на keylist полета по много удобен начин
 *
 * @category   Experta Framework
 * @package    jqdatepick
 * @author     Yusein Yuseinov
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class chosen_Plugin extends core_Plugin 
{
	
    
    /**
     * Изпълнява се след рендирането на input
     */
    function on_AfterRenderInput(&$invoker, &$tpl, $name, $value, $attr= array())
    {
    	if (Mode::is('javascript', 'no') || ((count($invoker->suggestions))<EF_MIN_COUNT_LIST_CHOSEN)) {
    		return ;
    	}

        $options = new ET();

      	foreach ($invoker->suggestions as $key => $val) {
      		
      		$attr = array();

            if (is_object($val)) {
                if ($val->group) {
                    $attr = $val->attr;
                    $attr['label'] = $val->title;
                    $optgroup = ht::createElement('optgroup', $attr, '' , TRUE);
                    $options->append($optgroup);
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

        $attr = array();

      	$attr['class'] = 'keylistChosen'; 
      	$attr['multiple'] = 'multiple';
      	$attr['name'] = $name.'[]';
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
    
	function on_BeforeFromVerbal($type, $res ,$value)
	{
		
		if ((count($value)>1) && (isset($value['chosen']))) {
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
		unset($value['chosen']);
		
		return ;
	}
	
}