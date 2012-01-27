<?php 


/**
 * Апита за използване на IMAP
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_Imap extends core_BaseClass
{
    
    
    /**
     * Пощенската кутия
     */
    var $mailBox = NULL;
    
    
    /**
     * Ресурс с връзката към пощенската кутия
     */
    var $connection;
    
    
    /**
     * Хоста, където се намира пощенската кутия
     */
    var $host = NULL;
    
    
    /**
     * Порта, от който ще се свързваме
     */
    var $port = NULL;
    
    
    /**
     * Потребителкото име за връзка
     */
    var $user = NULL;
    
    /**
     * Паролата за връзка
     */
    protected $pass = NULL;
    
    
    /**
     * Субхоста, ако има такъв
     */
    var $subHost = NULL;
    
    
    /**
     * Папката, от където ще се четата мейлите
     */
    var $folder = "INBOX";
    
    
    /**
     * SSL връзката, ако има такава
     */
    var $ssl = NULL;
    
    
    /**
     * Създава стринг с пощенската кутия
     */
    protected function getMailbox()
    {
        if ($this->ssl) {
            $this->ssl = '/' . ltrim($this->ssl, '/');
        } else {
            $this->ssl = '/novalidate-cert';
        }
        
        if ($this->subHost) {
            $this->subHost = '/' . ltrim($this->subHost, '/');
        }
        
        if ($this->port) {
            $this->port = ':' . ltrim($this->port, ':');
        }
        
        return "{" . "{$this->host}{$this->port}{$this->subHost}{$this->ssl}" . "}{$this->folder}";
    }
    
    
    /**
     * Свързва се към пощенската кутия
     */
    function connect()
    {
        $this->connection = imap_open($this->getMailbox(), $this->user, $this->pass);
        
        return $this->connection;
    }
    
    
    /**
     * Връща последната IMAP грешка
     */
    function getLastError()
    {
        return imap_last_error();
    }
    
    
    /**
     * Информация за съдържанието на пощенската кутия
     *
     * @param resource $connection - Връзката към пощенската кутия
     *
     * @return array
     */
    function getStatistic($varName = 'messages')
    {
        $this->statistic = imap_status($this->connection, $this->getMailbox(), SA_ALL);
        
        return $this->statistic->{$varName};
    }
    
    
    /**
     * Връща състоянието на писмата или посоченото писмо
     *
     * @param resource $connection - Връзката към пощенската кутия
     * @param number   $msgId      - Индекса на съобщението, което да се покаже
     *
     * @return array
     */
    function lists($msgId = FALSE)
    {
        
        if ($msgId) {
            $range = $msgId;
        } else {
            $MC = imap_check($this->connection);
            $range = "1:" . $MC->Nmsgs;
        }
        
        $response = imap_fetch_overview($this->connection, $range);
        
        foreach ($response as $msg) {
            $result[$msg->msgno] = (array) $msg;
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
    function getHeaders($msgId)
    {
        $header = imap_fetchheader($this->connection, $msgId, FT_INTERNAL);
        
        return $header;
    }
    
    
    /**
     * Връща бодито на избраното съобщение
     *
     * @param int $msgId - Индекса на съобщението, на което да се извлече тялото
     *
     * @return string
     */
    function getEml($msgId)
    {
        return imap_fetchbody($this->connection, $msgId, NULL);
    }
    
    
    /**
     * Подготвя посоченото съобщение за изтриване
     *
     * @param int $msgId - Индекса на съобщението, което да бъде изтрито
     *
     * @return boolean
     */
    function delete($msgId)
    {
        $res = imap_delete($this->connection, $msgId);
        
        return $res;
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
        $expunge = imap_expunge($this->connection);
        
        return $expunge;
    }
    
    
    /**
     * Затваря връзката
     *
     * @param resource $connection - Връзката към пощенската кутия
     * @param const    $flag       - Ако е CL_EXPUNGE тогава преди затварянето на конекцията
     * се изтриват всички имейли, които са маркирани за изтриване
     *
     * @return boolean
     */
    function close($flag = 0)
    {
        $close = imap_close($this->connection, $flag);
        
        return $close;
    }
    
    
    /**
     * Фетча и връща структурата на мейла
     */
    function fetchStructure($msgNum)
    {
        $structure = imap_fetchstructure($this->connection, $msgNum);
        
        return $structure;
    }
    
    
    /**
     * Фетчва избраната част от структурата на мейла
     */
    function getPartData($msgNum, $prefix)
    {
        $partData = imap_fetchbody($this->connection, $msgNum, $prefix);
        
        return $partData;
    }
}