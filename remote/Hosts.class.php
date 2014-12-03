<?php



/**
 * Мениджър на машини за отдалечен достъп
 *
 *
 * @category  bgerp
 * @package   remote
 * @author    Dimitar Minekov <mitko@experta.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class remote_Hosts extends core_Master
{
    
    
    /**
     * Необходими плъгини
     */
    public $loadList = 'plg_Created, plg_Rejected, plg_RowTools, plg_State2, remote_Wrapper';
                      
    
    /**
     * Заглавие
     */
    public $title = 'Отдалечени машини';
    
    
    /**
     * Права за писане
     */
    public $canWrite = 'ceo, remote, admin';
    
    
    /**
     * Права за запис
     */
    public $canRead = 'ceo, remote, admin';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'remote, debug';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, remote, admin';


	/**
	 * Кой може да разглежда сингъла на машините?
	 */
	public $canSingle = 'ceo, remote, admin';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(255)', 'caption=Наименование, mandatory,notConfig');
        $this->FLD('config', 'blob(serialize, compress)', 'caption=Конфигурация,input=none,single=none,column=none');
        $this->FLD('state', 'enum(active=Активен, closed=Спрян)', 'caption=Състояние,input=none');
        $this->FNC('ip', 'ip()', 'caption=IP aдрес, input, mandatory');
        $this->FNC('port', 'int(min=1,max=9999)', 'caption=Порт, input, mandatory');
        $this->FNC('user', 'varchar(255)', 'caption=Потребител, input, mandatory');
        $this->FNC('pass', 'password()', 'caption=Парола, input');
        
        $this->setDbUnique('name');
    }
    

    /**
     * Изпълнява се след въвеждането на данните от заявката във формата
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $form->rec->config = array('name' => $form->rec->name, 'ip' => $form->rec->ip,
                                      'port' => $form->rec->port, 'user' => $form->rec->user, 'pass' => $form->rec->pass);
         }
    }

	/**
	 * След подготвяне на формата добавяне/редакатиране
	 */
	public static function on_AfterPrepareEditForm($mvc, &$data)
	{
	    $form = &$data->form;
	    $rec  = &$form->rec;
	    bp($form);
        foreach ($rec->config as $name => $value) {
            $form->setDefault($name, $value);
        }
    }
    
    
    /**
     * Показваме актуални данни за всеки от записите
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    }
    
    /**
     * Извлича запис по име
     */
    private static function fetchByName($name)
    {
        return self::fetch(array ("#name = '[#1#]' COLLATE utf8_general_ci", $name));
    }
    
    /**
     * Изпълнява команда на отдалечен хост
     *
     * @param string $host
     * @param string $command
     * @param string $output [optionаl]
     * @param string $errors [optionаl]
     */
    public static function exec($host, $command, &$output=NULL, &$errors=NULL)
    {
        // Извличаме данните за хоста
        if (!$hostConfig = self::fetchByName($host)) {
            throw new core_exception_Expect("{$host}: не се съдържа в базата!");
        }
        // Проверяваме дали е достъпен
        $timeoutInSeconds = 1;
        if (!($fp = @fsockopen($hostConfig->ip,$hostConfig->port,$errCode,$errStr,$timeoutInSeconds))) {
            throw new core_exception_Expect("{$hostConfig->name}: не може да бъде достигнат ");
        }
        fclose($fp);        
        
        // Свързваме се по ssh
        $connection = @ssh2_connect($hostConfig->ip, $hostConfig->port);
        if (!$connection) {
            throw new core_exception_Expect("{$hostConfig->name}: няма ssh връзка");
        }
        
        if (!@ssh2_auth_password($connection, $hostConfig->user, $hostConfig->pass)) {
            throw new core_exception_Expect("{$hostConfig->name}: грешен потребител или парола.");
        }
        
        // Изпълняваме командата
        $stream = ssh2_exec($connection, $command);
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
     * @param string $host
     * @param string $file - път до файла
     */
    public static function put($host, $file)
    {
        
    }
    
    /**
     * Смъква файл от отдалечен хост
     *
     * @param string $host
     * @param string $file път до отдалечения файл
     */
    public static function get($host, $file)
    {
    
    }
    
}
