<?php


/**
 * Смяна на езика на английски
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_En extends core_Manager
{
    
    /**
     * Заглавие
     */
    var $title = 'Смяна на езика на английски';
    
    
    /**
     * Екшън по подразбиране, който сменя езика на английски
     */
    function act_Default()
    {
        // Изискваме да е логнат потребител
        requireRole('user');
        
        // Сменяме езика на английски
        core_Lg::set('en');
        
        // Редиректваме
        return redirect(array('bgerp_Portal', 'Show'));
    }
}