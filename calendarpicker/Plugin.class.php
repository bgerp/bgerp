<?php



/**
 * Клас 'calendarpicker_Plugin' -
 *
 *
 * @category  vendors
 * @package   calendarpicker
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class calendarpicker_Plugin extends core_Plugin
{
    
    
    /**
     * Изпълнява се преди рендирането на input
     */
    public function on_BeforeRenderInput(&$invoker, &$ret, $name, $value, &$attr = array())
    {
        ht::setUniqId($attr);
    }
    
    
    /**
     * Изпълнява се след рендирането на input
     */
    public function on_AfterRenderInput(&$invoker, &$ret, $name, $value, $attr = array())
    {
        $CP = cls::get('calendarpicker_Import');
        
        $options = array();
        
        if ($invoker->params['min']) {
            $options['min'] = dt::mysql2verbal($invoker->params['min'], 'Ymd');
        }
        
        if ($this->caller->params['max']) {
            $options['min'] = dt::mysql2verbal($invoker->params['max'], 'Ymd');
        }
        
        $attr['name'] = $name;
        $attr['value'] = $value;
        
        $ret = $CP->render($ret, $attr);
        
        return true;
    }
}
