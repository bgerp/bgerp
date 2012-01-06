<?php 

/**
 * 
 * Акаунти за използване на имейли
 *
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
