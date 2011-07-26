<?php


/**
 * Клас 'expert_Plugin' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    expert
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class expert_Plugin extends core_Plugin {
    
    
    /**
     *  Извиква се преди изпълняването на екшън
     */
    function on_BeforeAction(&$mvc, &$content, $act)
    {
        $method = 'exp_' . $act;
        
        if (method_exists($mvc, $method)) {
            
            // Създаваме експерта
            $exp = cls::get('expert_Expert', array('mvc' => $mvc));
            
            // Даваме му команда
            $content = $mvc->$method($exp);
            
            if(!$content) {
                $content = $exp->getResult();
            }
            
            return FALSE;
        }
    }
}