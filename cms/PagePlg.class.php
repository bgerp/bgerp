<?php



/**
 * Клас 'cms_PagePlg' - Плъгин за промяна на страницата
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_PagePlg extends core_Plugin
{
    
    /**
     * Прихваща рендирането на главната опаковка (страницата)
     */
    function on_BeforeRender($wrapper, &$content)
    {
        expect($wrapper instanceof page_Wrapper);

        if (!($tplName = Mode::get('wrapper')) && !Mode::is('printing')) {
            if(!haveRole('admin,ceo,manager,officer,executive')) {
                Mode::set('wrapper', 'cms_tpl_Page');
            }
        }

    }
}
