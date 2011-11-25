<?php 


/**
 * 
 * Апита за използване на IMAP
 *
 */
class email_Imap
{
	
	
	/**
	 * Пощенската кутия
	 */
    protected $mailBox = NULL;
	
    
    /**
     * Ресурс с връзката към пощенската кутия
     */
    var $connection;
	
        
    /**
     * Хоста, където се намира пощенската кутия
     */
    protected $host = NULL;
    
    
    /**
     * Порта, от който ще се свързваме
     */
    protected $port = NULL;
    
    
    /**
     * Потребителкото име за връзка
     */
    protected $user = NULL;
    
    
    /**
     * Паролата за връзка
     */
    protected $pass = NULL;
    
    
    /**
     * Субхоста, ако има такъв
     */
    protected $subHost = NULL;
    
    
    /**
     * Папката, от където ще се четата мейлите 
     */
    protected $folder = "INBOX";
    
    
    /**
     * SSL връзката, ако има такава
     */
    protected $ssl = NULL;
    
    
    /**
     * Държи инстанцията на връзката
     */
    static $staticConn;
    
    
    /**
     * Номера на съобщението
     */
    static $msgNum;
    
    
    /**
     * Изпълнява се при създаване на инстанция на класа.
     * Сетва пропортитата и извика методите за връзка с пощенската кутия.
     */
    function init($data)
    {
    	$this->host = $data['host'];
    	$this->port = $data['port'];
    	$this->user = $data['user'];
    	$this->pass = $data['pass'];
    	$this->subHost = $data['subHost'];
    	$this->folder = $data['fodler'];
    	$this->ssl = $data['ssl'];
    	
    	$this->makeMailBoxStr();
    	
    	$this->connect();
    	
    	self::$staticConn = $this->connection;
    }
    
    
    /**
     * Създава стринг с пощенската кутия
     */
    protected function makeMailBoxStr()
    {
   		if ($this->ssl) {
			$this->ssl = '/' . $this->ssl;
		}
		
		if ($this->subHost) {
			$this->subHost = '/' . $this->subHost;
		}
		
    	$this->mailBox = "{"."{$this->host}:{$this->port}{$this->subHost}{$this->ssl}"."}{$this->folder}";
    }
    
    
	/**
	 * Свързва се към пощенската кутия
	 */
	function connect()
	{		
		@$this->connection = imap_open($this->mailBox, $this->user, $this->pass);
		
		if ( $this->connection === false ) {
			email_Accounts::log("Не може да се установи връзка с пощенската кутия на: \"{$this->user}\". Грешка: " . imap_last_error());
	       
	       	return FALSE;
		}
	}
	
	
	/**
	 * Информация за съдържанието на пощенската кутия
	 * 
	 * @param resource $connection - Връзката към пощенската кутия
	 * 
	 * @return array
	 */
	function statistics()        
	{ 
	    $stat = imap_status(self::$staticConn, $this->mailBox, SA_MESSAGES);
	    
	    return $stat; 
	} 
	////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Връща състоянието на писмата или посоченото писмо
	 * 
	 * @param resource $connection - Връзката към пощенската кутия
	 * @param number   $messageId  - Номера на съобщението, което да се покаже
	 * 
	 * @return array
	 */
	function lists($messageId=FALSE) 
	{ 
		
	    if ($messageId) { 
	        $range=$messageId; 
	    } else { 
	        $MC = imap_check(self::$staticConn); 
	        $range = "1:".$MC->Nmsgs; 
	    } 
	    
	    $response = imap_fetch_overview(self::$staticConn,$range); 
	    foreach ($response as $msg) {
	    	$result[$msg->msgno]=(array)$msg; 
	    }
	    
	    return $result; 
	} 
	////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Връща хедъра на избраното съобщение
	 * 
	 * @param resource $connection - Връзката към пощенската кутия
	 * @param number   $messageId  - Номера на съобщението, което да се покаже
	 * 
	 * @return string
	 */
	static function header() 
	{ 
	    $header = imap_fetchheader(self::$staticConn, self::$msgNum, FT_PREFETCHTEXT);
		
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
	static function body() 
	{ 	
	    $body = imap_body(self::$staticConn, self::$msgNum);
	    
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
	function delete() 
	{
		$delete = imap_delete(self::$staticConn, self::$msgNum);
		
	    return $delete; 
	} 
	
	
	/**
	 * Изтрива e-мейлите, които са маркирани за изтриване
	 * 
	 * @param resource $connection - Връзката към пощенската кутия
	 * 
	 * @return boolean
	 */
	function expunge() 
	{ 
		$expunge = imap_expunge(self::$staticConn);
		
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
	function close($flag=0) 
	{ 
		$close = imap_close(self::$staticConn, $flag);
		
	    return $close; 
	}
	
	
	/**
	 * Фетча и връща структурата на мейла
	 */
	static function fetchStructure()
	{
		$structure = imap_fetchstructure(self::$staticConn, self::$msgNum);
		
		return $structure;
	}
	
	
	/**
	 * Фетчва избраната част от структурата на мейла
	 */
	static function fetchBody($prefix)
	{
		$fetchBody = imap_fetchbody(self::$staticConn, self::$msgNum, $prefix);
		
		return $fetchBody;
	}
}

?>