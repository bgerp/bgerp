<?php


/**
 * Клас 'calendarpicker_Plugin' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    calendarpicker
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class calendarpicker_Plugin extends core_Plugin {
    
    
    /**
     * Изпълнява се преди рендирането на input
     */
    function on_BeforeRenderInput(&$invoker, &$ret, $name, $value, &$attr = array())
    {
        if(Mode::is('screenMode', 'narrow')) return;
        ht::setUniqId($attr);
    }
    
    
    /**
     * Изпълнява се след рендирането на input
     */
    function on_AfterRenderInput(&$invoker, &$ret, $name, $value, $attr= array())
    {
        if(Mode::is('screenMode', 'narrow')) return;
        
        $CP = cls::get('calendarpicker_Import');
        
        $options = array();
        
        if($invoker->params['min']) {
            $options['min'] = dt::mysql2verbal('Ymd', $invoker->params['min']);
        }
        
        if($this->caller->params['max']) {
            $options['min'] = dt::mysql2verbal('Ymd', $invoker->params['max']);
        }
        
        $attr['name'] = $name;
        $attr['value'] = $value;
        
        $ret = $CP->render($ret, $attr);
        
        return TRUE;
    }
}