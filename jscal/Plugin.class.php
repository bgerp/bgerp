<?php



/**
 * Клас 'jscal_Plugin' -
 *
 *
 * @category  vendors
 * @package   jscal
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class jscal_Plugin extends core_Plugin {
    
    
    /**
     * Извиква се преди рендирането на HTML input
     */
    function on_BeforeRenderInput(&$invoker, &$ret, $name, $value, &$attr)
    {
        ht::setUniqId($attr);
    }
    
    
    /**
     * Извиква се след рендирането на HTML input
     */
    function on_AfterRenderInput(&$invoker, &$ret, $name, $value, $attr, $options = array())
    {
        if(Mode::is('screenMode', 'narrow')) return;
        
        $JSCal = cls::get('jscal_Import');
        
        $options = array('weekNumbers' => TRUE, 'animation' => FALSE, 'align' => "Bl/Tl/Tl/T/r");
        
        if($invoker->params['min']) {
            $options['min'] = dt::mysql2verbal($invoker->params['min'], 'Ymd');;
        }
        
        if($invoker->params['max']) {
            $options['min'] = dt::mysql2verbal($invoker->params['max'], 'Ymd');;
        }
        
        $ret = $JSCal->renderHtml($ret, $attr, $options);
        
        return TRUE;
    }
}