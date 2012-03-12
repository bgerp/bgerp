<?php



/**
 * Клас 'tpl_Wrapper' - Опаковка на страниците
 *
 *
 * @category  ef
 * @package   tpl
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class tpl_Wrapper extends core_BaseClass {
    
    
    /**
     * Прави стандартна 'обвивка' на изгледа
     */
    function renderWrapping_($content)
    {
        if (!($tplName = Mode::get('wrapper'))) {
            $tplName = Mode::is('printing') ? 'tpl_PrintPage' : 'tpl_DefaultPage';
        }
        
        // Зареждаме опаковката 
        $wrapperTpl = cls::get($tplName);
        
        // Изпращаме на изхода опаковано съдържанието
        $wrapperTpl->output($content, 'PAGE_CONTENT');
    }
}