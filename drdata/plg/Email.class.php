<?php



/**
 * Клас 'drdata_plg_Email' -
 *
 *
 * @category  vendors
 * @package   drdata
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class drdata_plg_Email extends core_Plugin
{
    
    
    /**
     * @todo Чака за документация...
     */
    public function on_EmailValidate(&$invoker, $email, &$result)
    {
        $drEmail = cls::get('drdata_Emails');
        $drEmail->validate($email, $result);
    }
}
