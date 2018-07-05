<?php


/**
 * Клас 'jqdatepick_Plugin' - избор на дата
 *
 *
 * @category  vendors
 * @package   jqdatepick
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class jqdatepick_Plugin extends core_Plugin
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
    public function on_AfterRenderInput(&$invoker, &$tpl, $name, $value, $attr = array())
    {
        $conf = core_Packs::getConfig('jqdatepick');
        
        $tpl->push('jqdatepick/' . $conf->JQDATEPICKER_VERSION . '/jquery.datepick.css', 'CSS', true);
        $tpl->push('jqdatepick/' . $conf->JQDATEPICKER_VERSION . '/jquery.plugin.min.js', 'JS', true);
        $tpl->push('jqdatepick/' . $conf->JQDATEPICKER_VERSION . '/jquery.datepick.js', 'JS', true);
        $tpl->push('jqdatepick/' . $conf->JQDATEPICKER_VERSION . '/jquery.datepick.ext.js', 'JS', true);
        $tpl->push('jqdatepick/' . $conf->JQDATEPICKER_VERSION . '/jquery.datepick-' . core_Lg::getCurrent() . '.js', 'JS', true);

        // custom стилове за плъгина
        $tpl->push('jqdatepick/css/jquery.datepick-custom.css', 'CSS', true);
        
        $alignment = Mode::is('screenMode', 'narrow') ? 'top' : 'bottom';
        
        jquery_Jquery::run($tpl, "$('#" . $attr['id'] . "').datepick({ renderer: $.datepick.weekOfYearRenderer, dateFormat: 'dd.mm.yyyy', alignment: '{$alignment}'});");
    }
}
