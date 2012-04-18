<?php
/**
 * Плъгин за рендиране на графики, използващ jqplot - http://www.jqplot.com/ 
 * 
 * @author Stefan Stefanov <stefan.bg@gmail.com>
 *
 */
class jqplot_Plugin extends core_Plugin
{
    /**
     * @param core_Mvc $mvc
     * @param core_ET $tpl
     */
    static function on_AfterRenderListTable($mvc, $tpl)
    {
        jqplot_Jqplot::setup($tpl, array('cursor','dragable'));
        
        $tpl->append(jqplot_Jqplot::chart(array(1, 2, 3, 4)));
    }
}