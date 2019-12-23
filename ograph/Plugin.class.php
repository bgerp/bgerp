<?php


/**
 * Клас 'ograph_Plugin'
 *
 *
 * @category  bgerp
 * @package   ograph
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class ograph_Plugin extends core_Plugin
{
    /**
     * Извиква се след подготовката на seo-атрибутите
     */
    public function on_AfterPrepareSEO(&$mvc, &$ret, $rec, $suggestions = array())
    {
        ograph_Factory::prepareOgraph($rec);
    }
    
    
    /**
     * Извиква се след поставянето на seo-атрибутите
     */
    public function on_AfterRenderSEO(&$mvc, &$ret, $content, $rec)
    {
        ograph_Factory::renderOgraph($content, $rec);
    }

}
