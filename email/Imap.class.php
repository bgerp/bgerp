<?php 


/**
 * 
 * Апита за използване на IMAP
 *
 */
class email_Imap
{
    
	
	/**
	 * Свързва се към пощенската кутия
	 * 
	 * @param string $host    - Хоста, където се намира пощенската кутия
	 * @param number $port    - Порта през който ще се свързваме
	 * @param string $user    - Потребителското име /емйла/
	 * @param string $pass    - Паролата
	 * @param string $subHost - Допълнителната част в името на домейна след номера на порта
	 * @param string $folder  - Коя папка в пощенската кутия
	 * @param string $ssl     - SSL връзката, която се намира след subHost
	 * 
	 * @return resource
	 */
	function login($host, $port, $user, $pass, $subHost=FALSE, $folder="INBOX", $ssl=FALSE)
	{
		if ($ssl) {
			$ssl = '/' . $ssl;
		}
		
		if ($subHost) {
			$subHost = '/' . $subHost;
		}
		
		@$imap = imap_open("{"."{$host}:{$port}{$subHost}{$ssl}"."}{$folder}", $user, $pass);

		if ( $imap === false ) {
			email_Accounts::log("Не може да се установи връзка с пощенската кутия на: \"{$user}\". Грешка: " . imap_last_error());
	       
	       	return FALSE;
		}
		
    	return $imap;
	}
	
	
	/**
	 * Информация за съдържанието на пощенската кутия
	 * 
	 * @param resource $connection - Връзката към пощенската кутия
	 * 
	 * @return array
	 */
	function statistics($connection)        
	{ 
	    $check = imap_mailboxmsginfo($connection); 
	    
	    return (array)$check; 
	} 
	
	
	/**
	 * Връща състоянието на писмата или посоченото писмо
	 * 
	 * @param resource $connection - Връзката към пощенската кутия
	 * @param number   $messageId  - Номера на съобщението, което да се покаже
	 * 
	 * @return array
	 */
	function lists($connection, $messageId=FALSE) 
	{ 
	    if ($messageId) { 
	        $range=$messageId; 
	    } else { 
	        $MC = imap_check($connection); 
	        $range = "1:".$MC->Nmsgs; 
	    } 
	    
	    $response = imap_fetch_overview($connection,$range); 
	    foreach ($response as $msg) {
	    	$result[$msg->msgno]=(array)$msg; 
	    }
	    
	    return $result; 
	} 
	
	
	/**
	 * Връща хедъра на избраното съобщение
	 * 
	 * @param resource $connection - Връзката към пощенската кутия
	 * @param number   $messageId  - Номера на съобщението, което да се покаже
	 * 
	 * @return string
	 */
	function header($connection,$messageId) 
	{ 
	    $header = imap_fetchheader($connection,$messageId,FT_PREFETCHTEXT);
		
	    return $header; 
	} 
	
	
	/**
	 * Връща бодито на избраното съобщение
	 * 
	 * @param resource $connection - Връзката към пощенската кутия
	 * @param number   $messageId  - Номера на съобщението, което да се покаже
	 * 
	 * @return string
	 */
	function body($connection,$messageId) 
	{ 
	    $body = imap_body($connection,$messageId);
		
	    return $body; 
	} 
	
	
	/**
	 * Подготвя посоченото съобщение за изтриване
	 * 
	 * @param resource $connection - Връзката към пощенската кутия
	 * @param number   $messageId  - Номера на съобщението, което да се покаже
	 * 
	 *  @return boolean
	 */
	function delete($connection,$messageId) 
	{ 
		$delete = imap_delete($connection,$messageId);
		
	    return $delete; 
	} 
	
	
	/**
	 * Изтрива e-мейлите, които са маркирани за изтриване
	 * 
	 * @param resource $connection - Връзката към пощенската кутия
	 * 
	 * @return boolean
	 */
	function expunge($connection) 
	{ 
		$expunge = imap_expunge($connection);
		
	    return $expunge; 
	} 
	
	
	/**
	 * Затваря връзката
	 * 
	 * @param resource $connection - Връзката към пощенската кутия
	 * @param const    $flag       - Ако е CL_EXPUNGE тогава преди затварянето на конекцията 
	 * се изтриват всички е-мейли, които са маркирани за изтриване
	 * 
	 *  @return boolean
	 */
	function close($connection, $flag=0) 
	{ 
		$close = imap_close($connection, $flag);
		
	    return $close; 
	}
	
	
	
	
	
	
}

?>