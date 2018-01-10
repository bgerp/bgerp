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
     * Ресурс с връзката към пощенската кутия
     */
    var $connection;
    
    
    /**
     * Информация за сметката, към която ще се свързваме
     */
    var $accRec;
    
    
    /**
     * Създава стринг с пощенската кутия
     */
    protected function getServerString()
    {
        $accRec = $this->accRec;

        // Определяне на хоста и евентуално порта
        $hostArr = explode(':', $accRec->server);
        
        if(count($hostArr) == 2) {
            $host = $hostArr[0];
            $port = $hostArr[1];
        } else {
            $host = $hostArr[0];
        }

        expect($host);
        
        // Определяне на порта, ако не е зададен в хоста
        if(!$port) {
            if($accRec->protocol == 'imap') {
                if($accRec->security == 'ssl') {
                    $port = '993';
                } else {
                    $port = '143';
                }
            }
            if($accRec->protocol == 'pop3') {
                if($accRec->security == 'ssl') {
                    $port = '995';
                } else {
                    $port = '110';
                }
            }
        }
            
        expect($port);

        $portArr = explode('/', $port, 2);

        if(count($portArr) == 2) {
            $port = $portArr[0];
            $params = $portArr[1];
        }
        
        if(!$params) {
            $params = $this->getParams($accRec);
        }

        $str =  '{' . "{$host}:{$port}/{$params}" . '}' . $accRec->folder;
        
        return $str;
    }
    
    
    /**
     * Връща стринга с допълнителни параметри за IPAM/POP3 връзката
     *
     */
    protected function getParams($accRec)
    {
        expect(in_array($protocol = $accRec->protocol, array('imap', 'pop3')));
        expect(in_array($security = $accRec->security, array('default', 'tls', 'notls', 'ssl')));
        expect(in_array($cert = $accRec->cert, array('noValidate', 'validate')));

        if($cert == 'noValidate' || $security == 'notls') {
            $cert = '/novalidate-cert';
        } else {
            $cert = '';
        }
        
        // Стринг за метода за аутенти
        if($security == 'default') {
            $security = '';
        } else {
            $security = "/{$security}";
        }

        $params = $protocol . $security . $cert;

        return $params;
    }
    

    /**
     * Свързва се към пощенската кутия
     */
    public function connect()
    {
        $this->connection = @imap_open($this->getServerString(), $this->accRec->user, $this->accRec->password);
        
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
        $this->statistic = imap_status($this->connection, $this->getServerString(), SA_ALL);
        
        return $this->statistic->{$varName};
    }


    /**
     * Еръща UID на съобщението с пореден номер $msgNo
     * Не работи с POP3 сървери
     */
    function getUid($msgNo)
    {
        return imap_uid($this->connection, $msgNo);
    }
    
    
    /**
     * Еръща $msgNoна съобщението със зададения UID
     * Не работи с POP3 сървери
     */
    function getMsgNo($uid)
    {
        return imap_msgno($this->connection, $uid);
    }


    /**
     * Връща състоянието на писмата или посоченото писмо
     *
     * @param resource $connection - Връзката към пощенската кутия
     * @param number   $msgId      - Индекса на съобщението, което да се покаже
     *
     * @return array
     */
    function lists($msgNo = FALSE)
    {
        
        if ($msgNo) {
            $range = $msgNo;
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
     * Връща хедър-а на избраното съобщение
     *
     * @param resource $connection - Връзката към пощенската кутия
     * @param number   $messageId  - Номера на съобщението, което да се покаже
     *
     * @return string
     */
    function getHeaders($msgNo)
    {
        $header = trim(imap_fetchheader($this->connection, $msgNo, FT_INTERNAL));

        return $header;
    }
    
    
    /**
     * Връща бодито на избраното съобщение
     *
     * @param int $msgId - Индекса на съобщението, на което да се извлече тялото
     *
     * @return string
     */
    function getEml($msgNo)
    {
        return imap_fetchbody($this->connection, $msgNo, NULL);
    }
    
    
    /**
     * Подготвя посоченото съобщение за изтриване
     *
     * @param int $msgId - Индекса на съобщението, което да бъде изтрито
     *
     * @return boolean
     */
    function delete($msgNo)
    {
        $res = imap_delete($this->connection, "{$msgNo}:{$msgNo}");
        
        return $res;
    }
    

    /**
     * Поставя флага, че съобщението е прочетено
     */
    function markSeen($msgNo)
    {
        return imap_setflag_full($this->connection, "{$msgNo}:{$msgNo}", '\\Seen');
    }
    

    /**
     * Изтрива флага, че съобщението е прочетено
     */
    function unmarkSeen($msgNo)
    {
        return imap_clearflag_full($this->connection, "{$msgNo}:{$msgNo}", '\\Seen');
    }

    
    /**
     * Изтрива e които са маркирани за изтриване
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
     * и връща структурата на имейл-а
     */
    function fetchStructure($msgNo)
    {
        $structure = imap_fetchstructure($this->connection, $msgNo);
        
        return $structure;
    }
    
    
    /**
     * Фетчва избраната част от структурата на имейл-а
     */
    function getPartData($msgNo, $prefix)
    {
        $partData = imap_fetchbody($this->connection, $msgNo, $prefix);
        
        return $partData;
    }

}