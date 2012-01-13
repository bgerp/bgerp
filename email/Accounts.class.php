<?php 



/**
 * Акаунти за използване на имейли
 *
 * @category  bgerp
 * @package   email
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_Accounts
{
    function getQuery()
    {
        $query = email_Inboxes::getQuery();
        $query->where("#type IN ('pop3', 'imap')");
        
        return $query;
    }
}
