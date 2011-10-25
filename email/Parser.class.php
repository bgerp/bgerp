<?php 

 
/**
 * 
 * Парсиране на емейл съобщение
 *
 */
class email_Parser
{

    /**
     * Текста на хедърите
     */
    var $header; 
    

    /**
     * Текстовата част на писмото
     */
    var $text; 
 
    
    /**
     * HTML част на писмото
     */
    var $html; 


    /**
     * Масив за хедърите
     */
    var $headersArr = array();

    function setHeaders($header)
    {
        $this->headers = $header;
        $this->parseHeaders();
    }


    function setText($text)
    {
        $this->text = $text;
    }


    function setHtml($html)
    { 
        $this->html = $html;
    }

    
    /**
     * Парсира хедърите в масив
     */
    function parseHeaders()
    {
        // Очакваме хедърите да са сетнати
        expect(isset($this->headers));

        $headers = str_replace("\n\r", "\n", $this->headers);
        $headers = str_replace("\r\n", "\n", $headers);
        $headers = str_replace("\r", "\n", $headers);
        
        $headers = explode("\n", $headers);

        // парсира масив с хедъри на е-маил
        foreach($headers as $h) {
            if( substr($h, 0, 1) != "\t" && substr($h, 0, 1) != " ") {
                $pos = strpos($h, ":");
                $index = strtolower(substr($h, 0, $pos));

                $this->headersArr[$index][] = trim(substr($h, $pos - strlen($h) + 1));
          
            } else {
                $current = count($this->headersArr[$index]) - 1;
                $this->headersArr[$index][$current] .= "\n" . $h;  
            }
        }

    }


    /**
     * Връща указания хедър. Ако се очаква повече от един хедър с това име, може да се вземе
     * точно посочен номер. Ако номера е отрицателен, броенето започва от зад на пред.
     * Хедър с номер 0 е първия срещнат с това име, а хедър с номер -1 е последния срещнат
     */
    function getHeader($name, $id = 0)
    {
        $name = strtolower($name);
        if($id < 0) {
            $id = count($this->headersArr[$name]) + $id;
        }

        return $this->headersArr[$name][$id];
    }


    /**
     * Прави опит да намери IP адреса на изпращача
     */
    function getSenderIp()
    {
        $ip = trim($this->getHeader('X-Originating-IP'), '[]');
        
        if(empty($ip) || (!type_Ip::isPublic($ip))) {
            
            $ip = trim($this->getHeader('X-Sender-IP'), '[]');

        }

        if(empty($ip) || (!type_Ip::isPublic($ip))) {
            $regExp = '/Received:.*\[((?:\d+\.){3}\d+)]/';
            preg_match_all($regExp, $this->headers, $matches);  
            if($ipCnt = count($matches[1])) {
                 for($i = $ipCnt - 1; $i>=0; $i--) {
                     if(type_Ip::isPublic($matches[1][$i])) {
                         $ip = $matches[1][$i];
                         break;
                     }
                 }
            }
        }
            
        return $ip;
    }

}