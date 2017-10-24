<?php



/**
 * Мениджър на машини за отдалечен достъп
 *
 *
 * @category  bgerp
 * @package   ssh
 * @author    Dimitar Minekov <mitko@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class ssh_Actions
{
    
    
    private $host;
    
    private $port = 22;
    
    private $user;
    
    private $pass;
    
    private $connection;
    
    
    /**
     * Конструктор
     */
    public function __construct($hostId)
    {
		expect($conf = ssh_Hosts::fetchConfig($hostId));
		
    	$this->host = $conf['ip'];
        $this->port = $conf['port'];
        $this->user = $conf['user'];
        $this->pass = $conf['pass'];
        
        $this->connect();
    }
    
    
    /**
     * Връща кънекшън ресурс
     * 
     * @return resource
     */
    private function connect ()
    {
        
        if ($this->connection) {
            
            return $this->connection;
        }

        // Проверяваме дали е достъпен
        $timeoutInSeconds = 1;
        if (!($fp = @fsockopen($this->host, $this->port, $errCode, $errStr, $timeoutInSeconds))) {
            throw new core_exception_Expect("@{$this->host}: не може да бъде достигнат");
        }
        fclose($fp);
        
        // Проверяваме има ли ssh2 модул инсталиран
        if (!function_exists('ssh2_connect')) {
            throw new core_exception_Expect("@Липсващ PHP модул: <b>`ssh2`</b>
                инсталирайте от командна линия с: apt-get install libssh2-php");
        }
        
        // Свързваме се по ssh
        $this->connection = @ssh2_connect($this->host, $this->port);
        if (!$this->connection) {
            throw new core_exception_Expect("@500 {$this->host}: няма ssh връзка");
        }
        
        if (!@ssh2_auth_password($this->connection, $this->user, $this->pass)) {
            throw new core_exception_Expect("@500 {$this->host}: грешен потребител или парола.");
        }
    }
    
    
    /**
     * Изпълнява команда на отдалечен хост
     *
     * @param string $command
     * @param string $output [optionаl]
     * @param string $errors [optionаl]
     * @param string $callBackUrl [optionаl]
     */
    public function exec($command, &$output=NULL, &$errors=NULL, $callBackUrl=NULL)
    {
		// Ако имаме callBackUrl изпълняваме командата асинхронно
		if ($callBackUrl) {
		    $cmd = "( " . $command . " ; wget --spider -q --no-check-certificate '" . $callBackUrl . "' > /dev/null 2>/dev/null) > /dev/null 2>/dev/null &";
		} else {
		    // Изпълняваме го синхронно
		    $cmd = $command;
		}
		
        // Изпълняваме командата
        $stream = ssh2_exec($this->connection, $cmd);
        $errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
        
        stream_set_blocking($stream, true);
        stream_set_blocking($errorStream, true);
        
        // Връщаме резултат
        $output = stream_get_contents($stream);
        $errors = stream_get_contents($errorStream);
        
        fclose($stream);
        fclose($errorStream);
    }

    /**
     * Качва файл на отдалечен хост
     *
     * @param string $localFileName - име на локалния файл
     */
    public function put($localFileName)
    {
        
        $remoteFileName = basename($localFileName);
        
        if (!ssh2_scp_send($this->connection, $localFileName, $remoteFileName)) {
            throw new core_exception_Expect("@Грешка при качване на файл на отдалечен хост.");
        }
    }
    
    /**
     * Връща съдържанието на файл от отдалечен хост
     *
     * @param string $remoteFileName - име на отдалечения файл
     * @return string $contents - съдържанието на отдалечения файл
     */
    public function getContents($remoteFileName)
    {
        if (!($localFileName = @tempnam(EF_TEMP_PATH, $remoteFileName))) {
        	throw new core_exception_Expect("@Грешка при създаване на временен файл.");
        }
        
        if (!@ssh2_scp_recv($this->connection, $remoteFileName, $localFileName)) {
            throw new core_exception_Expect("@Грешка при сваляне на файл от отдалечен хост.");
        }
        $contents = @file_get_contents($localFileName);
        @unlink($localFileName);
        
        return $contents;
    }
}
