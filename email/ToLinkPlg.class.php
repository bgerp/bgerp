<?php


/**
 * Клас 'email_plg_ToLink' - Превръща всички email и emails типове в линкове към създаване на постинг
 *
 *
 * @category  bgerp
 * @package   email
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class email_ToLinkPlg extends core_Plugin
{
    const AT_ESCAPE = '*';
    
    
    /**
     * Преобразуваме имейл-а на потребителя към вътрешен линк към постинг.
     */
    public function on_BeforeAddHyperlink($mvc, &$res, $email, $verbal)
    {
        if (haveRole('ceo,manager,officer,executive') && (Mode::is('text', 'html') || !Mode::is('text'))) {
            //Променяме полето от 'emailto:' в линк към email_Outgoings/add/
            $res = Ht::createLink($email, array('email_Outgoings', 'add', 'emailto' => str_replace('@', self::AT_ESCAPE, $email)), null, array('title' => 'Създаване на имейл към този адрес'));
            
            return false;
        }
    }
}
