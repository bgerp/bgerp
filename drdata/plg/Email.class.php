<?php


/**
 * Клас 'drdata_plg_Email' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    drdata
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class drdata_plg_Email extends core_Plugin {
    
    
    /**
     *  @todo Чака за документация...
     */
    function on_EmailValidate(&$invoker, $email, &$result )
    {
        $drEmail = CLS::get('drdata_Emails');
        $drEmail->validate($email, &$result);
    }
}