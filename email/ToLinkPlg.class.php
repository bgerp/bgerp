<?php



/**
 * Клас 'email_plg_ToLink' - Превръща всички email и emails типове в линкове към създаване на постинг
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_ToLinkPlg extends core_Plugin
{
    
    /**
     * Преобразуваме имейл-а на потребителя към вътрешен линк към постинг.
     */
    function on_BeforeAddHyperlink($mvc, &$res, $email)
    {
        if(Mode::is('text', 'html') || !Mode::is('text')) {
            //Променяме полето от 'emailto:' в линк към email_Outgoings/add/
            $res = Ht::createLink($email, array('email_Outgoings', 'add', 'emailto' => $email));
            
            return FALSE;
        }
    }
}