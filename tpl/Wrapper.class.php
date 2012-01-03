<?php

/**
 * Клас 'tpl_Wrapper' - Опаковка на страниците
 *
 *
 * @category   Experta Framework
 * @package    tpl
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @link
 * @since      v 0.1
 */
class tpl_Wrapper extends core_BaseClass {

    function renderWrapping_($content)
    {
        if (!($tplName  = Mode::get('wrapper'))) {
           $tplName = Mode::is('printing') ? 'tpl_PrintPage' : 'tpl_DefaultPage';
        } 

        // Зареждаме опаковката 
        $wrapperTpl = cls::get( $tplName );
 
        // Изпращаме на изхода опаковано съдържанието
        $wrapperTpl->output($content, 'PAGE_CONTENT');
    }
}