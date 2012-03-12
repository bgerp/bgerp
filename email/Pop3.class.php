<?php 


/**
 * Максималното време за изчакване на буфера
 */
defIfNot('POP3_TIMEOUT', 2);


/**
 * Клас 'email_Pop3' - Използване на pop3
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_Pop3
{
    
    
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
     * Грешки при свързване
     */
    var $err = NULL;
    
    
    /**
     * Дали потребителското име и паралата съвпадат
     */
    var $logged = NULL;
    
    
    /**
     * При създаване на инстанция на класа, създава и връзка с пощенската кутия
     */
    function init($data)
    {
        $this->host = $data['host'];
        $this->port = $data['port'];
        $this->user = $data['user'];
        $this->pass = $data['pass'];
        
        $this->connect();
    }
    
    
    /**
     * Установяваме връзката
     */
    function connect()
    {
        @$this->connection = fsockopen($this->host, $this->port, $this->err['no'], $this->err['str'], POP3_TIMEOUT);
        
        if ($this->connection === false) {
            email_Inboxes::log("Не може да се установи връзка с пощенската кутия на: 
                            \"{$this->user}\". Грешка: " . $this->err['no'] . " - " . $this->err['str']);
            
            return FALSE;
        }
        
        //Прочита и изчиства буфера
        $this->getBuffer();
        
        //Свързваме със пощенската кутия
        $this->login();
        
        if (!$this->logged) {
            email_Inboxes::log("Не може да се установи връзка с пощенската кутия на: 
                            \"{$this->user}\". Потребителското име и/или паролата са грешни.");
        }
        
        return TRUE;
    }
    
    
    /**
     * Свързваме се с пощенската кутия
     */
    protected function login()
    {
        $user = "USER " . $this->user;
        $userStr = $this->setBuffer($user);
        
        $pass = "PASS " . $this->pass;
        $passStr = $this->setBuffer($pass);
        
        $this->logged = $this->checkIsCorrect($passStr);
    }
    
    
    /**
     * Изпраща данните към сървъра
     */
    protected function setBuffer($data)
    {
        $data .= "\r\n";
        fputs($this->connection, $data, strlen($data));
        $buffer = $this->getBuffer();
        
        return $buffer;
    }
    
    
    /**
     * Прочита и изчиства съдържанието на буфера
     */
    protected function getBuffer()
    {
        stream_set_timeout($this->connection, POP3_TIMEOUT);
        
        $buffer = '';
        
        while (($conn = fgets($this->connection, 1024)) !== false) {
            $buffer .= $conn;
        }
        
        return $buffer;
    }
    
    
    /**
     * Проверява дали върнатия резултата е +ОК
     */
    protected function checkIsCorrect($str)
    {
        if (stristr($str, '+OK') !== FALSE) {
            
            return TRUE;
        }
        
        return FALSE;
    }
    
    
    /**
     * Връща хедъра на мейла
     */
    function getHeader($msgId)
    {
        $header = "TOP {$msgId} 0";
        
        $headerStr = $this->setBuffer($header);
        
        return $headerStr;
    }
    
    
    /**
     * Връща броя на съобщенията
     */
    function getStat()
    {
        $stat = "STAT";
        $statStr = $this->setBuffer($stat);
        
        if ($this->checkIsCorrect($statStr)) {
            $arr = explode(" ", $statStr);
            $numMsg = $arr[1];
        }
        
        return $numMsg;
    }
    
    
    /**
     * Прочита и връща съдържанието на съобщението
     */
    function readMsg($msgId)
    {
        $read = "RETR {$msgId}";
        $readStr = $this->setBuffer($read);
        
        if (!$this->checkIsCorrect($readStr)) {
            
            return FALSE;
        }
        
        return $readStr;
    }
    
    
    /**
     * Маркира съобщението за изтриване
     */
    function delMsg($msgId)
    {
        $del = "DELE {$msgId}";
        $delStr = $this->setBuffer($del);
        
        return $this->checkIsCorrect($delStr);
    }
    
    
    /**
     * Ако има съобщение, маркирано за изтриване, премахва маркера
     */
    function undelMsg()
    {
        $rset = "RSET";
        
        $rsetStr = $this->setBuffer($rset);
        
        return $this->checkIsCorrect($rsetStr);
    }
    
    
    /**
     * Затваря връзката, и ако има съобщения маркирани за изтриване ги изтрива
     */
    function closeConn()
    {
        $quit = "QUIT";
        
        $quitStr = $this->setBuffer($quit);
        
        @fclose($socket);
        
        return $this->checkIsCorrect($quitStr);
    }
}