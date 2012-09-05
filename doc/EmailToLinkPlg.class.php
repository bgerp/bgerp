<?php



/**
 * Клас 'doc_EmailToLinkPlg' - Превръща всички email и emails типове в линкове към създаване на постинг
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_EmailToLinkPlg extends core_Plugin
{
    
    
    /**
     * Преобразуваме имейл-а на потребителя към вътрешен линк към постинг.
     */
    function on_BeforeAddHyperlink($mvc, &$result, $email, $verbal)
    {   
        if(haveRole('ceo,manager,officer,executive')) {
            //Променяме полето от 'emailto:' в линк към email_Outgoings/add/
            $row = Ht::createLink($verbal, array('email_Outgoings', 'add', 'emailto' => str_replace('$54', '#', $email)), NULL, array('target'=>'_blank'));
        
            return FALSE;
        }
    }
}